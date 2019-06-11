<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Dataset;
use App\Concept; 
use App\Geography;
use App\Variable;

class GetMeta extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sidekick:getmeta {dataset}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Adds dataset metadata to the database';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

	private function str_lreplace($search, $replace, $subject) {
        $pos = strrpos($subject, $search);
        if($pos !== false && substr_count($subject, '_') >= 2) {
            $subject = substr_replace($subject, $replace, $pos, strlen($search));
        }
        return $subject;
	}

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
		//$arrDebugTables = array('DP04'); 
		$dataset_code = $this->argument('dataset');

		// Dataset must be configured in config/datasets.php
		if (!$config = config('datasets.'.$dataset_code)) {
			$this->error('Dataset '.$dataset_code.' not configured. Valid datasets are:');
			foreach(config('datasets') as $code => $config) {
				$this->error(' '.$code);
			}
			exit(1);
		}

		// Loop through titles
		foreach ($config['titles'] as $title) {
			$variable_file = $title['variable_file'];
		
			// Read in vars file or bail
			if (!$vars = json_decode(file_get_contents($variable_file), true)) {
				$this->error('Failed to decode variable file '.$variable_file);
				exit(1);
			} 

			// Create the db entry for the dataset
			if (!$dataset = Dataset::where('code', $dataset_code)->first()) {
				$this->info('Creating entry for '.$dataset_code.' ...');
				$dataset = new Dataset;
			} else {
				$this->info('Updating entry for '.$dataset_code.' ...');
			}

			$dataset->code = $dataset_code;
			$dataset->label = $config['label'];
			$dataset->save();

			foreach ($vars['variables'] as $varcode => $var) {
				$varcode=$this->str_lreplace('_','',$varcode);
				if (!isset($var['predicateOnly']) && isset($var['concept'])) {
				//if (isset($var['predicateType']) && isset($var['concept'])) {
					if (strpos($varcode, '_') !== false) {
						// This is a column (variable) in a table (concept)
						$conceptcode=substr($varcode, 0, strpos($varcode, '_'));
						$conceptlabel=trim(substr($var['concept'], strpos($varcode, '_')+1));
		
						$concept_labels[$conceptcode]=$conceptlabel;

						// Correct labeling for Total
						if ($var['label'] == 'Estimate!!Total') {
							$var['label'] = 'Estimate!!Total!!Total'; 
						}
				
						$cols[$conceptcode][$varcode]=$var['label'];
		
						if (!isset($count[$conceptcode])) {
							$count[$conceptcode]=1;
						} else {
							$count[$conceptcode]++;
						}

						// Handle attributes
						if (isset($var['attributes'])) {
							$moecode=substr($varcode, 0, -1).'M'; // I.e., change B01001_001E to B01001_001M
							$arrAttributes=explode(",", $var['attributes']);
							if (in_array($moecode, $arrAttributes)) {
								// Our predected MOE code exists as an attribute
								// $cols[$conceptcode][$moecode]=preg_replace('/^Estimate!!/', 'Margin of Error For!!', $var['label']);
								$cols[$conceptcode][$moecode]=$var['label']; // just reuse the estimate label.  We will change it below.
								$count[$conceptcode]++;
							}
						}
					}
				}
			}

			// Debugging - strip down to one table
			// foreach($cols as $tbl => $tblcols) if ($tbl != 'B01001') unset($cols[$tbl]);

			// Loop through and clean up variable lables
			foreach($cols as $tbl => $tblcols) {
				// Get common prefix - see https://stackoverflow.com/a/35838357/3212940
				$tmpcols=$tblcols; // the sort will screw up our keys
				sort($tmpcols);
				$l1 = $tmpcols[0]; // First label 
				$l2 = $tmpcols[count($tmpcols)-1]; // Last label 
				$min = min(strlen($l1), strlen($l2));

				for($i=0; $i<$min && $l1[$i]==$l2[$i]; $i++); // we only need the exit value of $i

				// Strip off prefix
				foreach ($tblcols as $varcode => $label) {
					$label = substr($label, $i);
					// Relable margins of error if needed
					if (substr($varcode, -1) == 'M') {
						if (substr($label, 0, 22) != 'Margin Of Error For!!') {
							$label='Margin Of Error For!!'.$label;
						}
					}
					$cols[$tbl][$varcode]=$label;
				}
			}

			foreach($cols as $tbl => $tblcols) {
				ksort($tblcols);
				foreach($config['geographies'] as $geoname => $geo_predicate) {
					if (!isset($arrDebugTables) || in_array($tbl, $arrDebugTables)) {
						$this->info('Processing '.$geoname."_".$tbl." ...");
						// Add the geography record
						if (!$geography = Geography::where('dataset_id', $dataset->id)->where('code', $geoname)->first()) {
							$geography = new Geography;
							$geography->dataset_id = $dataset->id;
							$geography->code = $geoname;
							$geography->label = $geo_predicate;
							$geography->save();

							$this->info(' Created geography '.$dataset->code.'/'.$geography->code.' ...');
						} 
						// Add the concept record
						if (!$concept = Concept::where('dataset_id', $dataset->id)->where('code', $tbl)->first()) {
							$concept = New concept;
							$concept->dataset_id = $dataset->id;
							$concept->code = $tbl;
							$concept->label = $concept_labels[$tbl];
							$concept->save();

							$this->info(' Created concept '.$dataset->code.'/'.$concept->code.' ...');
						}

						// Add the variable records
						foreach ($tblcols as $colname => $col) {
							// Get type code and label
							if (substr($colname, -2) == 'EA') {
								$typecode='EA';
								$typelabel='Estimate Annotation';
							} elseif (substr($colname, -2) == 'MA') {
								$typecode='MA';
								$typelabel='Margin of Error Annotation';
							} elseif (substr($colname, -2) == 'PE') {
								$typecode='PE';
								$typelabel='Percentage Estimate';
							} elseif (substr($colname, -2) == 'PM') {
								$typecode='PM';
								$typelabel='Percentage Margin of Error';
							} elseif (substr($colname, -1) == 'E') {
								$typecode='E';
								$typelabel='Estimate';
							} elseif (substr($colname, -1) == 'M') {
								$typecode='M';
								$typelabel='Margin of Error';
							} else { // failsafe to clear old values
								$typecode='';
								$typelabel='';
							}
						
							if (!$variable = Variable::where('dataset_id', $dataset->id)->where('code', $colname)->first()) {
								$variable = new Variable;
								$variable->dataset_id = $dataset->id;
								$variable->concept_id = $concept->id;
								$variable->code = $colname;
								$variable->label = str_replace(':','',str_replace('!!','->',$col));
								$variable->typecode = $typecode;
								$variable->typelabel = $typelabel;
								$variable->save();
	
								$this->info('  Create variable '.$dataset->code.'/'.$variable->code.' ...');
							}
						}
					}
				}
			}
		}


	} // /handle
} // /classdef
