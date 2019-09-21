<?php

namespace App;

class ACSQuery {
	public function __construct() {

	}

	private function getMOECol($col, $ds) {
		// Returns the Margin of Error variable code for a given estimate variable code
		// if one exists in the Variables tables or false if none exists.

		
		$dsid = Dataset::where('code',$ds)->first()->id;

		if (substr($col, -1) == 'E') { // works for PE or E to PM or M
			$moecol=substr($col, 0, -1).'M';
		} else {
			$moecol='';
		}

		if (Variable::where('code', $moecol)->where('dataset_id', $dsid)->exists()) {
			return $moecol;
		} else {
			return false;
		}
	}

	private function getColType($col) {
		// returns 'ref' or 'val' depending on col type
		$varcode=substr($col,strrpos($col, "_")+1);
		if (Geography::where('code', '=', $varcode)->exists()) {
			return 'ref'; // A known geography
		}

		if (Geography::where('code', '=', str_replace('NAME','',$varcode))->exists()) {
			return 'ref'; // A known geography
		}

		return 'val';	
	}

	public function generateSQL($qryobj) {
		$qry = '';

		$firstfilter = true;
		$jointables = array();

		// $qry .= "/*\n"; $qry .= print_r($qryobj, true)."\n"; $qry .= "*/\n\n";
		if (count($qryobj['cols']) > 0) {
			// SELECT ...
				
			$qry .= "SELECT\n";
			$i=0;
			foreach ($qryobj['cols'] as $col) {
				// Add to table list
				$coltable=strtok($col, '.');
				if ($coltable != $qryobj['table'] && !in_array($coltable, $jointables)) {
					$jointables[]=$coltable;
				}
				
				$colvarcode=substr($col, strpos($col, ".") +1); // Srip off table prefix
				$moecode=$this->getMOECol($colvarcode, $qryobj['db']);	

				$coltype=$this->getColType($col);

				// See if we have an alias
				$label="";
				$moelabel="";
				if (isset($qryobj['labels'][$colvarcode])) { // if the column has a label
					$label=" AS `".$qryobj['labels'][$colvarcode]."`";
					$moelabel=" AS `".$qryobj['labels'][$colvarcode]." (MOE)`";
				}
				$i++;
				$qry .= " ".$col.$label.",\n";
			
				if ($qryobj['denominator'] != '' && $coltype == 'val') {	
					$qry .= " ROUND((".$col." * 100) / ".strtok($col, '.').".".$qryobj['denominator'].",2)";
					$qry .= " AS `".$qryobj['labels'][$colvarcode]." (PCT)`,\n";
				}
		
				if ($moecode) {
					$qry .= " ".strtok($col, '.').".".$moecode.$moelabel.",\n";

					// Calculate ratio MOE if appropriate.
					if ($qryobj['denominator'] != '' && $coltype == 'val') { // We have a value type denominator
						if ($denommoecode = $this->getMOECol($qryobj['denominator'], $qryobj['db'])) { // The denominator has a specified MOE
							// oi.  restate some statics to make slightly more scrutable the inscrutable
							$nvalcol = $col;
							$dvalcol = strtok($col, '.').".".$qryobj['denominator'];
							$nmoecol = strtok($col, '.').".".$moecode;
							$dmoecol = strtok($col, '.').".".$denommoecode;
							
							$qry .= " ROUND(";	
							$qry .= "(1/".$dvalcol.") * SQRT(POW(".$nmoecol.",2)+(POW((".$nvalcol."/".$dvalcol."),2) * POW(".$dmoecol.",2)))";
							$qry .= "*100,2)";
										
							$qry .= str_replace('MOE', 'MOE PCT', $moelabel).",\n";
						}
					}
				}
			}

			// Add in column aggregates, if any.
			if (isset($qryobj['customaggs']) && count($qryobj['customaggs'])>0) {
				foreach ($qryobj['customaggs'] as $customagg) {
					if ($customagg['type'] == 'col') {

						// The aggregated values
						$nvalexpr = " (";
						foreach ($customagg['cols'] as $member) {
							$colvartbl=strtok($qryobj['table'], "_")."_".strtok($member['column'], '_');
							$nvalexpr .= $colvartbl.'.'.$member['column'] .' + ';
						}
						$nvalexpr = trim($nvalexpr, "+ ");
						$nvalexpr .= ")";
						$qry .= $nvalexpr . " AS `".$customagg['alias']."`,\n"; 

						if ($qryobj['denominator'] != '') {	
							$qry .= " ROUND((((";
							foreach ($customagg['cols'] as $member) {
								$colvartbl=strtok($qryobj['table'], "_")."_".strtok($member['column'], '_');
								$qry .= $colvartbl.'.'.$member['column'] .' + ';
							}
							$qry = trim($qry, "+ ");
							$qry .= ") * 100) / ".strtok($col, '.').".".$qryobj['denominator']."),2)";
							$qry .= " AS `".$customagg['alias']." (PCT)`,\n";
						}
			
						// The aggregated MOE
						$nmoeexpr = " (ROUND(SQRT(";
						foreach ($customagg['cols'] as $member) {
							$colvartbl=strtok($qryobj['table'], "_")."_".strtok($member['column'], '_');
							$nmoeexpr .= 'POW('.$colvartbl.'.'.$this->getMOECol($member['column'], $qryobj['db']) .',2) + ';
						}
						$nmoeexpr = trim($nmoeexpr, "+ ");
						$nmoeexpr .= ")))";
						$qry .= $nmoeexpr ." AS `".$customagg['alias']." (MOE)`,\n";
						
						// Calculate ratio MOE if appropriate.
						if ($qryobj['denominator'] != '') { // We have a value type denominator
							if ($denommoecode = $this->getMOECol($qryobj['denominator'], $qryobj['db'])) { // The denominator has a specified MOE
								// oi.  restate some statics to make slightly more scrutable the inscrutable
								$nvalcol = $nvalexpr;
								$dvalcol = strtok($col, '.').".".$qryobj['denominator'];
								$nmoecol = $nmoeexpr; 
								$dmoecol = strtok($col, '.').".".$denommoecode;

								/*
								$qry .= " ROUND(";	
								$qry .= "(1/".$dvalcol.") * SQRT(POW(".$nmoecol.",2)+(POW((".$nvalcol."/".$dvalcol."),2) * POW(".$dmoecol.",2)))";
								$qry .= "*100,2)";
								*/
								
								// Use jeremy's simplified formula	
								$qry .= " ROUND(";
								$qry .= "SQRT(";
								$qry .= "POW(SQRT(SUM(POW(".$nmoecol.",2))),2) - "; 
								$qry .= "POW((".$nvalcol." * 100) / SUM(".$dvalcol."),2) * "; 
								$qry .= "POW(SQRT(SUM(POW(".$dmoecol.",2)))*.01,2) ";	
								$qry .= ") / SUM(".$dvalcol.") * 100";
								$qry .= ", 2)";

								$qry .= " AS `".$customagg['alias']." (MOE PCT)`,\n";
							}
						}
					}
				}
			}

			// trim off last comma
			$qry = trim($qry, ",\n")."\n";
			
			// FROM
			$qry .= "FROM ".$qryobj['table'];

			// JOINS
			$jointblcode=substr($qryobj['table'], strpos($qryobj['table'], "_") +1);
			$joincol=strtok($qryobj['table'],'_'); // the Geo part of the table name
			foreach ($jointables as $table) {
				$tblcode=substr($table, strpos($table, "_") +1);
				$qry .= "\nJOIN ".$table." ON ".$qryobj['table'].".".$jointblcode."_".$joincol;
				$qry .= " = ".$table.".".$tblcode."_".$joincol;
			}

			if (count($qryobj['filters']) > 0) {
				foreach ($qryobj['filters'] as $filter) {
					// Create the clause keyword
					if ($firstfilter) {
						$qry .= "\nWHERE ";
						$firstfilter = false;
					} else {
						$qry .= "\nAND ";
					}

					// Create the clause, depending on expression
					switch ($filter['expr']) {
						case "is":
							$qry .= $joincol."_".strtok($filter['variable'], '_').".".$filter['variable']." = '".$filter['value']."'";
							break;
						case "in":
							$qry .= $joincol."_".strtok($filter['variable'], '_').".".$filter['variable']." in ".$filter['value'];
							break;
						case "in list":
							$li = ItemListItem::where('list_id', $filter['value'])->get();

							$list = '';
							foreach ($li as $i) {
								$list .= "'".$i->item."', ";	
							}
							$list = trim($list); // trim off the trailing space 
							$list = trim($list, ','); // trim off the trailing comma
							
							$qry .= $joincol."_".strtok($filter['variable'], '_').".".$filter['variable']." in (".$list.")"; 
							break;
					}
				}
			}

			// Handle custom row aggregations 
			// Check if there are any row aggs
			$rowaggs=false;
			if (isset($qryobj['customaggs']) && count($qryobj['customaggs'])>0) {
				foreach ($qryobj['customaggs'] as $customagg) {
					if ($customagg['type'] == 'row') {
						$rowaggs=true;
					}
				}
			}
			if ($rowaggs) {
				$cg=0;
				foreach($qryobj['customaggs'] as $customagg) {
					if ($customagg['type'] == 'row') {
						$cg++;
						$qry .= "\nUNION\nSELECT\n";

						$i=0;
						foreach ($qryobj['cols'] as $col) {
							$colvarcode=substr($col, strpos($col, ".") +1); // Srip off table prefix
							$moecode=$this->getMOECol($colvarcode, $qryobj['db']);

							// See if we have an alias
							$label="";
							$moelabel="";
							if (isset($qryobj['labels'][$colvarcode])) { // if the column has a label
								$label=" AS `".$qryobj['labels'][$colvarcode]."`";
								$moelabel=" AS `".$qryobj['labels'][$colvarcode]." (MOE)`";
							}
							$i++;

							// Handle the custom agg alias
							if ($colvarcode == $customagg['key']) {
								$qry .= "'".$customagg['alias']."'".$label.",\n";
							} elseif (isset($qryobj['labels'][$colvarcode]) && $qryobj['labels'][$colvarcode] == $customagg['keyname']." NAME") {
								$qry .= " '".$customagg['alias']."'".$label.",\n";
							} elseif ($this->getColType($col) == 'ref') {
								$qry .= "'VARIES'".$label.",\n";
							} else {
								$qry .= " SUM(".$col.")".$label.",\n";
								if ($qryobj['denominator'] != '') {	
									$qry .= " ROUND((SUM(".$col.") * 100) / SUM(".strtok($col, '.').".".$qryobj['denominator']."),2)";
									$qry .= " AS `".$qryobj['labels'][$colvarcode]." (PCT)`,\n";
								}
							}

							if ($moecode) {
								$qry .= " ROUND(SQRT(SUM(POW(".strtok($col, '.').".".$moecode.",2))))".$moelabel.",\n";

								// Calculate ratio MOE if appropriate.
								if ($qryobj['denominator'] != '') { // We have a value type denominator
									if ($denommoecode = $this->getMOECol($qryobj['denominator'], $qryobj['db'])) { // The denominator has a specified MOE
										// oi.  restate some statics to make slightly more scrutable the inscrutable
										$nvalcol = "SUM(".$col.")";
										$dvalcol = strtok($col, '.').".".$qryobj['denominator'];
										$nmoecol = strtok($col, '.').".".$moecode; 
										$dmoecol = strtok($col, '.').".".$denommoecode;

										/*
										$qry .= " ROUND(";	
										$qry .= "(1/".$dvalcol.") * SQRT(POW(".$nmoecol.",2)+(POW((".$nvalcol."/".$dvalcol."),2) * POW(".$dmoecol.",2)))";
										$qry .= "*100,2)";
										*/
										
										// Use jeremy's simplified formula	
										$qry .= " ROUND(";
										$qry .= "SQRT(";
										$qry .= "POW(SQRT(SUM(POW(".$nmoecol.",2))),2) - "; 
										$qry .= "POW((".$nvalcol." * 100) / SUM(".$dvalcol."),2) * "; 
										$qry .= "POW(SQRT(SUM(POW(".$dmoecol.",2)))*.01,2) ";	
										$qry .= ") / SUM(".$dvalcol.") * 100";
										$qry .= ", 2)";
										
										$qry .= " AS `".$qryobj['labels'][$colvarcode]." (MOE PCT)`,\n";
									}
								}
							}
						}
			
						// Add in column aggregates, if any.
						foreach ($qryobj['customaggs'] as $colagg) {
							if ($colagg['type'] == 'col') {
			
								// The aggregated values
								$nvalexpr = " SUM(";
								foreach ($colagg['cols'] as $member) {
									$colvartbl=strtok($qryobj['table'], "_")."_".strtok($member['column'], '_');
									$nvalexpr .= $colvartbl.'.'.$member['column'] .' + ';
								}
								$nvalexpr = trim($nvalexpr, "+ ");
								$nvalexpr .= ")";
								$qry .= $nvalexpr." AS `".$colagg['alias']."`,\n";
								
								/*
								$qry .= " SUM(";
								foreach ($colagg['cols'] as $member) {
									$colvartbl=strtok($qryobj['table'], "_")."_".strtok($member['column'], '_');
									$qry .= $colvartbl.'.'.$member['column'] .' + ';
								}
								$qry = trim($qry, "+ ");
								$qry .= ") AS `".$colagg['alias']."`,\n";
								*/

								if ($qryobj['denominator'] != '') {
									$qry .= " ROUND((SUM(";
									foreach ($colagg['cols'] as $member) {
										$colvartbl=strtok($qryobj['table'], "_")."_".strtok($member['column'], '_');
										$qry .= $colvartbl.'.'.$member['column'] .' + ';
									}
									$qry = trim($qry, "+ ");
									$qry .= ") * 100) / SUM(".strtok($col, '.').".".$qryobj['denominator']."),2) AS `".$colagg['alias']."`,\n";
								}

								// The aggregated MOE
								$nmoeexpr = " ROUND(SQRT(SUM(POW((ROUND(SQRT(";
								foreach ($colagg['cols'] as $member) {
									$colvartbl=strtok($qryobj['table'], "_")."_".strtok($member['column'], '_');
									$nmoeexpr .= 'POW('.$colvartbl.'.'.$this->getMOECol($member['column'],$qryobj['db']) .',2) + ';
								}
								$nmoeexpr = trim($nmoeexpr, "+ ");
								$nmoeexpr .= "))),2))))";
								$qry .= $nmoeexpr." AS `".$colagg['alias']." (MOE)`,\n";
								
								/*
								$qry .= " ROUND(SQRT(SUM(POW((ROUND(SQRT(";
								foreach ($colagg['cols'] as $member) {
									$colvartbl=strtok($qryobj['table'], "_")."_".strtok($member['column'], '_');
									$qry .= 'POW('.$colvartbl.'.'.$this->getMOECol($member['column'],$qryobj['db']) .',2) + ';
								}
								$qry = trim($qry, "+ ");
								$qry .= "))),2)))) AS `".$colagg['alias']." (MOE)`,\n";
								*/

								// Calculate ratio MOE if appropriate.
								if ($qryobj['denominator'] != '') { // We have a value type denominator
									if ($denommoecode = $this->getMOECol($qryobj['denominator'], $qryobj['db'])) { // The denominator has a specified MOE
										// oi.  restate some statics to make slightly more scrutable the inscrutable
										$nvalcol = $nvalexpr;
										$dvalcol = strtok($col, '.').".".$qryobj['denominator'];
										$nmoecol = $nmoeexpr; 
										$dmoecol = strtok($col, '.').".".$denommoecode;
		
										$qry .= " ROUND(";	
										$qry .= "(1/".$dvalcol.") * SQRT(POW(".$nmoecol.",2)+(POW((".$nvalcol."/".$dvalcol."),2) * POW(".$dmoecol.",2)))";
										$qry .= "*100,2)";
										$qry .= " AS `".$colagg['alias']." (MOE PCT)`,\n";
									}
								}
							}
						}
						// trim off last comma
						$qry = trim($qry, ",\n")."\n";

						// FROM
						$qry .= "FROM ".$qryobj['table'];

						// JOINS
						foreach ($jointables as $table) {
							$tblcode=substr($table, strpos($table, "_") +1);
							$qry .= "\nJOIN ".$table." ON ".$qryobj['table'].".".$jointblcode."_".$joincol;
							$qry .= " = ".$table.".".$tblcode."_".$joincol;
						}

						// WHERE
						$qry .= "\nWHERE ".$customagg['key']." in ".$customagg['vals'];
					}
				}
			}
		}
		//$qry = print_r($qryobj, true)."\n".$qry;
		return $qry;
	}

	public function executeSQL($qryobj, $limit=false) {
		// Get an outside connection - we do not use eloquent for the ACS data
		$server = '127.0.0.1';
		$user = env('DB_USERNAME');
		$password = env('DB_PASSWORD'); 
		$database = $qryobj['db'];

		$acsdb = new \mysqli($server, $user, $password, $database);
		$acsdb->set_charset("utf8"); // or results with special chars will fail

		$qry = $this->generateSQL($qryobj);
		if ($limit) {
			$qry .= "\nLIMIT ".$limit;
		}

		$res = $acsdb->query($qry);

		$rows = array();
		while($row = $res->fetch_assoc()) {
			$rows[]=$row;
		}
		return json_encode($rows); 
	}

	public function saveObject($objname, $obj, $userid, $confirmoverwrite = 0) {
		// Save queries
		if ($objname == "query") {
			// Check if query exists
			$q = Query::where('owner_id', $userid)->where('name', $obj['name'])->first();
			if ($q === null) {
				// No saved query by this user and name
				$q = new Query;
				$q->owner_id = $userid;
				$q->name = $obj['name'];
				$q->description = $obj['desc'];
				$q->public = ($obj['ispublic'] == 'on' ? true : false);
				$q->object = json_encode($obj);

				if ($q->save()) {
					print '<div class="alert alert-success text-center" role="alert">Saved</div>'; 
				} else {
					print '<div class="alert alert-danger text-center" role="alert">Save failed</div>';
				};
			} else {
				print '<div class="alert alert-warning text-center" role="alert"><b>'.$obj['name'].'</b> already exists.</div>';
			}
		}

		// Save Lists
		if ($objname == "list") {
			// Check items to make sure we really have some
			$items=array();
			foreach ($obj['items'] as $item) {
				if (trim($item) != '') {
					$items[]=trim($item);
				}
			}

			if (count($items) > 0) {
				// Check of query exists
				$isnew=false;
				$l = ItemList::where('owner_id', $userid)->where('name', $obj['name'])->first();
			
				if ($l === null) { // A new list
					$isnew=true;
					$l = new ItemList; 
				} 
			
				$l->owner_id = $userid;
				$l->name = $obj['name'];
				$l->description = $obj['name'];
				$l->public = ($obj['ispublic'] == 'on' ? true : false);
				$l->save();

				if (!$isnew) {
					// Delete old list items
					ItemListItem::where('list_id', $l->id)->delete();
				}

				foreach($items as $item) {
					$i = new ItemListItem;
					$i->list_id = $l->id;
					$i->item = $item;
					$i->save();
				}

				if ($isnew) {
					print '<div class="alert alert-success text-center" role="alert">Saved</div>';	
				} else {
					print '<div class="alert alert-success text-center" role="alert">Updated</div>';
				}

			} else {
				print '<div class="alert alert-warning text-center" role="alert">List as no items.</div>';
			}
		}
	}
}
