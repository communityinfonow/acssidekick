<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Dataset;
use App\Concept; 
use App\Geography;
use App\Variable;

class getdata extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sidekick:getdata {dataset=none} {action=none} {table=none} {state=none}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Loads data from Census API into ACS Sidekick';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
		// Capture args as static
		$dataset = $this->argument('dataset');
		$action = $this->argument('action');
		$table = $this->argument('table');
		$state = $this->argument('state');

		// DB Credentials
		$server = '127.0.0.1';
		$user = env('DB_USERNAME');
		$password = env('DB_PASSWORD'); 

		// Other static config 
		$apikey=env('CENSUS_API_KEY'); // Your API key from Census.gov

		if ($dataset == 'list') {
			// Configured datasets
			foreach (config('datasets') as $key => $arr) {
				$this->info($key.": ".$arr['label']);
			}
			exit;
		} elseif (!in_array($dataset, array_keys(config('datasets')))) {
			$this->usage();
		} 

		// Valid dataset - read in variables
		$arrDSConfig=config('datasets.'.$dataset);
		foreach ($arrDSConfig['titles'] as $titlename => $title) {
			$vars = json_decode(file_get_contents($title['variable_file']), true);

			foreach ($vars['variables'] as $varcode => $var) {
				
				if (isset($var['predicateOnly'])) {
					// Skip these, they are not really variables
					continue;
				} 
				if (strpos($varcode, '_') !== false && isset($var['concept'])) {

					// This is a column (variable) in a table (concept)
					$conceptcode=substr($varcode, 0, strpos($varcode, '_'));
					$conceptlabel=trim(substr($var['concept'], strpos($varcode, '_')+1));
		
					$concepts[$conceptcode]=$conceptlabel;
		
					$cols[$conceptcode][$varcode]=$var['label'];		
					$title[$conceptcode]=$titlename; // so we can get back to the title config later
		
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
							$cols[$conceptcode][$moecode]=preg_replace('/^Estimate!!/', 'Margin of Error For!!', $var['label']);
							$count[$conceptcode]++;
						}
					}
				}
			}
		}
		ksort($cols);

		if ($action == 'list') {
			foreach ($cols as $tbl => $tblcols) {
				$this->info("$tbl");
			}
			exit;
		} elseif ($action != 'load') {
			$this->usage();
		}

		// Handle load for specific tables
		$arrtables = explode(",",$table);
		foreach ($arrtables as $tbl) {
			if ($tbl == 'none') {
				$this->usage();
			} elseif (trim($tbl) == 'all') {
				$loadtables = $cols;
			} elseif (trim($tbl) == 'resume') {
				// Get the last table loaded
				try {
					$dbtemp = new \mysqli($server, $user, $password, $dataset); 
				} catch (\Exception $e) {
					$this->info("Failed to access database.");
					$this->usage();
				}
				$loaded = array_column(mysqli_fetch_all($dbtemp->query('SHOW TABLES')),0);
				foreach ($loaded as $loadedtbl) {
					$loadedcode=substr($loadedtbl, strpos($loadedtbl, '_') +1);
					$cols_loaded[$loadedcode]='1';
				}
				ksort($cols_loaded); // just to be sure because $cols is ksorted

				// We want to reload the last one, so remove if
				array_pop($cols_loaded);

				// Now created a loadcols with loaded cols omitted
				$loadtables = $cols;
				foreach ($cols_loaded as $code => $val) {
					if (isset($loadtables[$code])) {
						unset($loadtables[$code]);
					}
				}
			} elseif (isset($cols[trim($tbl)])) {
				$loadtables[trim($tbl)]=$cols[trim($tbl)];
			} else {
				$this->info("Invalid table: ".trim($tbl)."\n");
				$this->usage();
			} 
		}

		// Check state.  For now we require one.  Grab a list from census.gov
		$lines=explode("\n",file_get_contents('https://www2.census.gov/geo/docs/reference/state.txt'));
		foreach ($lines as $line) {
			if (trim($line) != '') {
				$arr=explode("|", $line);
				if ($arr[0] != 'STATE') { // strip away the header
					$valid_states[$arr[0]]=$arr[2];
				}
			}
		}
		if ($state == "none") {
			$this->usage();
		} elseif ($state == "list") {
			foreach ($valid_states as $statenum => $statename) {
				$this->info($statenum.": ".$statename);
			}
			exit;
		} elseif (!in_array($state, array_keys($valid_states))) {
			$this->info("Invalid state code: ".$state);
		}

		// Now that we have a valid state, get some geography relationships.  Note,
		// we use the 2010 ZCTA to County Relationship File as the ZCTA to Place file doesn't
		// include all ZCTAs.
		$lines=explode("\n",trim(file_get_contents('http://www2.census.gov/geo/docs/maps-data/data/rel/zcta_county_rel_10.txt')));
		foreach ($lines as $line) {
			$arr=explode(",", $line);
			if ($arr[1] == $state) {
				$state_zctas[]=$arr[0];
			}		
		}	
		$state_zctas=array_unique($state_zctas);

		// Proceed with load.  First set up some stuff that doesn't need to be done
		// in a loop.

		// Establish a DB connection or advise.

		try {
			$db = new \mysqli($server, $user, $password, $dataset); 
		} catch (\Exception $e) {
			$this->info($e->getMessage());
			$this->info("Please confirm database exists and access is granted to ".$user."@localhost or create it with:\n");
			$this->info("CREATE DATABASE ".$dataset.";");
			$this->info("GRANT ALL PRIVILEGES ON ".$dataset.".* TO '".$user."'@'localhost' IDENTIFIED BY '".$password."';");
			exit;
		}
		$db->set_charset("utf8"); // or results with special chars will fail

		// Look through the tables we want to bring in.
		foreach ($loadtables as $tbl => $tblcols) {
			ksort($tblcols);
			$this->info("Importing ".$tbl." ...");
		
			// Loop through each configured geography
			foreach ($arrDSConfig['geographies'] as $geoname => $geo_predicate) {
				// Build the schema				
				$tblname=$geoname."_".$tbl;

				$qry  = "CREATE TABLE ".$tblname." (\n";
				$qry .= " ID INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,\n";
				$qry .= " ".$tbl."_".$geoname." VARCHAR(255) NOT NULL, \n";
				$qry .= " ".$tbl."_".$geoname."NAME VARCHAR(255) NOT NULL, \n";
				if (isset($arrDSConfig['geo_parents'][$geoname])) { // Add in supporting cols for geography dependencies
					foreach ($arrDSConfig['geo_parents'][$geoname] as $parent) {
						$qry .= " ".$tbl."_".$parent." VARCHAR(255), \n";
					}
				}
				$ct=1;
				foreach ($tblcols as $colname => $col) {
					$qry .= " ".$this->str_lreplace("_", "", $colname) ." ".$arrDSConfig['titles'][$title[$tbl]]['value_type'].",";
					if (($ct % 8 == 0) || $ct == count($tblcols)) {
						$qry .= "\n";
					}
					$ct++;
				}
				$qry .= " UNIQUE KEY geo (".$tbl."_".$geoname.", ".$tbl."_".$geoname."NAME)\n";
				if ($ct > 999) { // Too many damn columns. Current Innodb has a hard limit of 1017, earlier version 1000. 
					$qry .= ") ENGINE = MYISAM;\n";
				} else {
					$qry .= ") DEFAULT CHARSET=utf8;\n";
				}
				
				// Create the table
				$this->info("  Creating table ".$tblname." ...");

				if (!$db->query("DROP TABLE IF EXISTS ".$tblname.";")) {
					$this->info($db->error);
					exit;
				}
				if (!$db->query($qry)) {
					$this->info($db->error);
					exit;
				}

				// Get data
				
				// You can only get 50 vars at a time, including predicates 
				// get in chunks of 40 to give some headroom.  First build an array of requests.
	
				$varct=0;
				$reqct=0;
				if (isset($requests)) { unset($requests); }
				$requests[$reqct] = $arrDSConfig['titles'][$title[$tbl]]['base_url']."?get=NAME";
				foreach($tblcols as $colname => $col) {
					$varct++;
					if ($varct > 40) {
						$reqct++;
						$varct=1;
						$requests[$reqct] = $arrDSConfig['titles'][$title[$tbl]]['base_url']."?get=NAME"; 
					}
					$requests[$reqct] .= ",".$colname;
					$varct++;
				}

				// Add geo, predicates, and key
				foreach($requests as $idx => $req) {
					if (array_search($geo_predicate, array('us', 'region', 'division', 'state')) !== false) {
						$requests[$idx]=$req."&for=".$geo_predicate.":*&key=".$apikey;
					} elseif (!isset($statenum)) {
						$requests[$idx]=$req."&for=".$geo_predicate.":*&key=".$apikey;
					} elseif ($geo_predicate == 'zip+code+tabulation+area') { // These are handled specially as in=state:## isn't supported :(
						$requests[$idx]=$req."&for=".$geo_predicate.":*&key=".$apikey;	
						// Works but takes too damn long.  

					} else  { // For sub state level geos, only get specified state's data
						$requests[$idx]=$req."&for=".$geo_predicate.":*&in=state:".$statenum."&key=".$apikey;
					} 
				}

				// Loop through requests grabbing data
				print "    Retrieving data ...";
				foreach($requests as $req) {
					print ".";
					$response=json_decode(file_get_contents($req), true);
					//print "\n"; print_r($response);
					//	
   					//	[0] => Array (
            		//		[0] => NAME
            		//		[1] => B17024_001E
            		//		[2] => B17024_001M
            		//		[3] => B17024_002E
            		//		[4] => zip code tabulation area
        			//	)			
					//
					// parse into upsert queries
					foreach ($response as $idx => $row) {
						if ($idx === 0) { // our column headers for the rest of the rows
							unset($cols);
							foreach ($row as $key => $val) {
								switch($val) {
									case 'NAME': 
										$cols[$key]=$tbl."_".$geoname."NAME";
										break;
									case urldecode($geo_predicate):
										$cols[$key]=$tbl."_".$geoname;
										break;
									case (isset($arrDSConfig['geo_parents'][$geoname]) && in_array(strtoupper($val), $arrDSConfig['geo_parents'][$geoname])):
										// A variable that matches an upstream GEO
										$cols[$key]=$tbl."_".strtoupper($val);
										break;
									default:
										$cols[$key]=$val;
								}
							}		
						} else {
						 	// Special handling for ZCTA where state filter is set
							if ($geoname == 'ZCTA' && isset($statenum)) {
								// Find the ZCTA column and the ZCTA
								foreach ($cols as $colnum => $colname) {
									if ($colname == $tbl."_ZCTA") {
										if (in_array($row[$colnum], $state_zctas) !== true) {
											// This record is for a ZCTA that is not in our state list.
											continue(2); // Break out of this and the next outer loop
										}
									}
								}
							}

							$qry = "INSERT INTO ".$tblname." (";
							$colct=1;
							foreach($cols as $colname) {
								if (isset($statenum) && $colname == 'state') {
									continue; // This is our state filter being returned - do not try to add it to the DB!
								}
								if ($colct > 1) {
									$qry .= ', ';
								}
								$qry .= $this->str_lreplace("_", "", $colname);
								$colct++;
							}
							$qry .= ') VALUES (';
							$valct=1;
							foreach($row as $colnum => $val) {
								if (isset($statenum) && $cols[$colnum] == 'state') {
									continue; // This is our state filter being returned - do not try to add it to the DB!
								}
								//file_put_contents('values.debug', $val."\n", FILE_APPEND);
								if ($valct > 1) {
									$qry .= ', ';
								}
								if ($cols[$colnum] == $tbl."_".$geoname."NAME" || $cols[$colnum] == $tbl."_".$geoname) {
									// Our varchars
									$qry .= "'".str_replace("'","''",$val)."'";
								} else {
									// Value cols
									if ($val == '') { 
										$qry .= "NULL";
									} else {
										$qry .= $val;
									}
								}
								
								$valct++;
							}
							$qry .= ') ON DUPLICATE KEY UPDATE ';
							$valct=1;
							foreach($row as $colnum => $val) {
								if ($cols[$colnum] != $tbl."_".$geoname."NAME" && $cols[$colnum] != $tbl."_".$geoname) {
									if ($valct > 1 && (!isset($statenum) || $cols[$colnum] != 'state')) {
										$qry .= ', ';
									}
									// Value cols
									if (isset($statenum) && $cols[$colnum] == 'state') {
										true; // This is our state filter being returned - do not try to add it to the DB!
									} elseif ($val == '') {
										$qry .= $this->str_lreplace("_", "", $cols[$colnum]).' = NULL';
									} else {
										$qry .= $this->str_lreplace("_", "", $cols[$colnum]).' = '.$val;
									}
									$valct++;
								}
							}
							$qry .= ';';
						
							if (!$db->query($qry)) {
								$this->info($db->error);
								exit;
							}
						} // if $idx != 0
					} // foreach $response 
					print "+";
				} // foreach $requests
				print "\n";
			} // foreach $arrDSConfig['geographies']
		} // foreach $loadtables
    }

	private function str_lreplace($search, $replace, $subject) {
    	$pos = strrpos($subject, $search);
    	if($pos !== false && substr_count($subject, '_') >= 2) {
        	$subject = substr_replace($subject, $replace, $pos, strlen($search));
    	}
    	return $subject;
	}

	private function usage() 
	{
		$this->info("usage: ".$this->argument('command')."[dataset] [action] [table] [state]");
		$this->info("  where [dataset] = key from config/datasets or 'list'"); 
		$this->info("  where [action] = 'load' to load table data or 'list' to list table codes");
		$this->info("  where [table] = a comma seperated list of valid table codes for the specified"); 
		$this->info("    dataset, 'all' for all table codes, or 'resume' to continue an aborted 'all' load");
		$this->info("    (note: 'resume' reloads the last attempted table)");
		$this->info("  where [state] = a valid state code (##) or 'list'");
		exit;
	}
}
