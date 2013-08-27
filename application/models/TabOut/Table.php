<?php

class TabOut_Table  {
    
	 public $DBtableID; //tableID for the database saved output
	 public $tableID;
    public $tabArray;
	 public $projectNames; //array of project names
	 public $actProjectIDs; //array of active projects
	 public $actVariables; //array of active variables (variable_uuid, var_label, sort_order)
	 public $actVarIDs; //array of active variableIDs
	 public $actVarLabels; //array of active variable labels (for whole table, across multiple projects)
	 
	 public $maxContextFields; //number of context depth
	 
	 public $showTable = true; //show the output table?
	 public $showSourceFields = true;   //show the original fields and values from the source data
	 public $showLinkedFields = true;   //show linked data annotating the record
	 public $showUnitTypeFields = true;  //show standards-annotated measuresuments (with "Measurement type")
	 public $limitUnitTypeFields = false;  //limit records for export to those that have standards-annotated measurements
	 public $showBCE = true;
	 public $showBP = true;
	 
	 public $showFieldURIs = false; //show the URIs of fields for linked data
	 public $showUnitTypeURIs = false; //show the URIs of fields for unit-type linked data
	 public $showLDSourceValues = false; //show original / source values for linked data?
	 public $sortForSourceVars = "sCount DESC, var_tab.sort_order, var_tab.var_label";
	 
	 public $linkedFields; //standard fields from ontology links
	 public $LFtypeCount = 0; //count of linked fields of type type
	 public $LFunitTypeCount = 0; //count of linked fields of type unit-type
	 
	 public $db;
	 
	 public $page;
	 public $recStart;
	 public $setSize;
	 public $ExcelSanitize = false;
	 
	 public $limitingProjArray = false; //make an array of project UUIDs to limit the results to
	 public $limitingVarArray = false; //an array of variables that limit the output
	 public $limitingSourceTabArray = false;
	 public $limitingTypeURIs = false; //an array of URIs (for certain taxa, elements say) to limit the output
	 
	 public $geoTimeArray; //array of lat / lon and start / end values for different projects and containment paths
	 
	 const OCspatialURIbase = "http://opencontext.org/subjects/";
	 const OCprojectURIbase = "http://opencontext.org/projects/";
	 const contextDelim = "|xx|";
	 
	 public $queries = array();
	 public $recordQueries = false;
	 
	 public $linkedTypeConfigs = array("http://opencontext.org/vocabularies/open-context-zooarch/zoo-0077" =>
												  array(0 => array(	"labeling" => " (distal)",
																		   "cond" => " AND linked_data.linkedLabel LIKE '%distal%'"),
														  1 => array(	"labeling" => " (proximal)",
																				"cond" => " AND linked_data.linkedLabel LIKE '%proximal%'")
														 )
												  );
	 
	 
	 public $outputGT = array("Latitude (WGS84)" => "",
								"Longitude (WGS84)" => "",
								"Early (Cal. CE/BCE)" => "",
								"Late (Cal. CE/BCE)" => "",
								"Early (Cal. BP)" => "",
								"Late (Cal. BP)" => ""
								);
	 
	 function makeTableArray($classUUID){
		  
		  $this->getMaxContextDepth($classUUID); //get the maximum context depth
		  $this->getProjects($classUUID);
		  $this->getLinkedVariables($classUUID);
		  $this->getGeoTime(); //make an array of geo and time metadata
		  
		  if(!$this->limitUnitTypeFields && !$this->limitingVarArray){
				$result = $this->getClass($classUUID); //get the list of items, their labels, and their context
		  }
		  elseif($this->limitUnitTypeFields && !$this->limitingVarArray && !$this->limitingTypeURIs){
				$result = $this->getClassLinkedMeasurements($classUUID); //get list of items that have standard measurements, annotated with "measurment type"
		  }
		  elseif(is_array($this->limitingVarArray && !$this->limitingTypeURIs)){
				$result = $this->getClassVarLimited($classUUID); //get list of items that have standard measurements, annotated with "measurment type"
		  }
		  elseif(is_array($this->limitingTypeURIs)){
				$result = $this->getClassLinkTypeLimited($classUUID); //get list of items that are linked to certain type URIs
		  }
		  if($result){
				
				if($this->DBtableID){
					 $row = $result[0];
					 $firstRecord = $this->makeTableArrayRecord($row);
					 $this->createExportTable($firstRecord);
				}
				
				$tabArray = array();
				foreach($result as $row){
					 
					 $itemUUID = $row["uuid"];
					 $uuidKey = self::OCspatialURIbase.$row["uuid"];
					 $actRecord = $this->makeTableArrayRecord($row);
					 if($this->DBtableID){
						  $this->saveActRecord($itemUUID, $actRecord);
					 }
					 if($this->showTable){
						  $tabArray[$uuidKey] = $actRecord;
					 }
					 unset($actRecord);
				}
				
				return $tabArray;
		  }
		  else{
				return false;
		  }
		  
	 }
	 
	 
	 //make a record to populate the output table
	 function makeTableArrayRecord($row){
		  $projectNames = $this->projectNames;
		  $actRecord = array();
		  $itemUUID = $row["uuid"];
		  $projectUUID = $row["project_id"];
		  $projectURI = self::OCprojectURIbase.$projectUUID;
		  $projectName = $projectNames[$projectUUID];
		  
		  $label = $row["space_label"];
		  $parent = $this->getParentID($row["uuid"]); 
		  if($parent != false){
				$parentURI = self::OCspatialURIbase.$parent["uuid"];
		  }
		  else{
				$parentURI = "";
		  }
		  
		  $this->addTableContainRecord($itemUUID , $projectUUID); //add record to a table that notes the space-uuid and project-id and table-id assocation
		  
		  
		  $actRecord["Item label"] = $label;
		  $actRecord["Project URI"] = $projectURI;
		  $actRecord["Project name"] = $projectName;
		  $names = $this->getLinkedPeople($itemUUID);
		  $actRecord["Related person(s)"] = implode(", ", $names);
		  
		  $rawContext = $row["full_context"];
		  $rawContextArray = explode(self::contextDelim, $rawContext);
		  $i = 0;
		  while($i <= $this->maxContextFields){
				$contextLabelKey = "Context (".($i + 1).")";
				if(isset($rawContextArray[$i])){
					 if($rawContextArray[$i] != $label){
						  $actRecord[$contextLabelKey] = $rawContextArray[$i];
					 }
					 else{
						  $actRecord[$contextLabelKey] = "";
					 }
				}
				else{
					 $actRecord[$contextLabelKey] = "";
				}
		  $i++;
		  }
		  
	 
		  $actRecord["Context URI"] = $parentURI;
		  $actRecord = $this->tableAddGeoTimeFields($row["uuid"], $rawContext, $projectUUID, $actRecord);
		  
		  if($this->showLinkedFields){
				$actRecord = $this->tableAddLinkedFields($itemUUID, $actRecord); //add the linked data fields
		  }
		  if($this->showSourceFields){
				$actRecord = $this->tableAddSourceFields($itemUUID, $actRecord); //add the source data fields
		  }
		  
		  return $actRecord;
	 }
	 
	 
	 
	 
	 function getLinkedPeople($itemUUID, $namesOnly = true){
		  
		  $db = $this->startDB();
		  
		  $sql = "SELECT links.targ_uuid, persons.combined_name AS name
		  FROM links
		  JOIN persons ON persons.uuid = links.targ_uuid
		  WHERE links.origin_uuid = '$itemUUID'
		  AND links.targ_type = 'person'
		  
		  UNION
		  
		  SELECT links.targ_uuid, users.combined_name AS name
		  FROM links
		  JOIN users ON users.uuid = links.targ_uuid
		  WHERE links.origin_uuid = '$itemUUID'
		  AND links.targ_type = 'person'
		  ";
		  
		  $result = $db->fetchAll($sql);
		  $output = array();
		  $names = array();
		  foreach($result as $row){
				$personUUID = $row["targ_uuid"];
				$output[$personUUID] = $row["name"];
				if(!in_array($row["name"], $names)){
					 $names[] = $row["name"];
				}
		  }
		  
		  if($namesOnly){
				$output = $names;
		  }
		  
		  return $output;
	 }
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 //this puts geographic and chronology fields into a record
	 function tableAddGeoTimeFields($itemUUID, $itemContext, $projectUUID, $actRecord){
		  $outputGT = $this->outputGT;
		  $geoFound = false;
		  $timeFound = false;
		  if(is_array($this->geoTimeArray)){
				$geoTime = $this->geoTimeArray;
				if(isset($geoTime[$projectUUID])){
					 if(is_array($geoTime[$projectUUID]["geo"])){
						  foreach($geoTime[$projectUUID]["geo"] as $contextKey => $coords){
								$keyLen = strlen($contextKey);
								$searchContext = substr($itemContext, 0, $keyLen); // check to see if the context key is in the first part of the item's context
								if($searchContext == $contextKey){
									 $outputGT["Latitude (WGS84)"] = $coords["latitude"];
									 $outputGT["Longitude (WGS84)"] = $coords["longitude"];
									 $geoFound = true;
									 break; //no need to keep on through the loop
								}
						  }
					 }
					 if(is_array($geoTime[$projectUUID]["time"])){
						  foreach($geoTime[$projectUUID]["time"] as $contextKey => $time){
								$keyLen = strlen($contextKey);
								$searchContext = substr($itemContext, 0, $keyLen); // check to see if the context key is in the first part of the item's context
								if($searchContext == $contextKey){
									 $outputGT["Early (Cal. CE/BCE)"] = $time["start_time"];
									 $outputGT["Late (Cal. CE/BCE)"] = $time["end_time"];
									 if($time["start_time"]<0){
										  $outputGT["Early (Cal. BP)"] = abs($time["start_time"]) + 1950 ;
									 }
									 else{
										  $outputGT["Early (Cal. BP)"] = 1950 - $time["start_time"];
									 }
									 if($time["end_time"]<0){
										  $outputGT["Late (Cal. BP)"] = abs($time["end_time"]) + 1950 ;
									 }
									 else{
										  $outputGT["Late (Cal. BP)"] = 1950 - $time["end_time"];
									 }
									 $timeFound = true;
									 break; //no need to keep on through the loop
								}
						  }
					 }
				}
		  }
		  
		  foreach($outputGT as $fieldKey => $value){
				$actRecord[$fieldKey] = $value;
		  }
		  
		  if(!$geoFound){
				//didn't find it through context matching, try via contain relations
				$actRecord = $this->containRelationGeo($itemUUID, $actRecord);
		  }
		  if(!$timeFound){
				$actRecord = $this->containRelationTime($itemUUID, $actRecord);
		  }
	 
		  if(!$this->showBCE){
				unset($actRecord["Early (Cal. CE/BCE)"]);
				unset($actRecord["Late (Cal. CE/BCE)"]);
		  }
		  if(!$this->showBP){
				unset($actRecord["Early (Cal. BP)"]);
				unset($actRecord["Late (Cal. BP)"]);
		  }
	 
		  return $actRecord;
	 }
	 
	 
	 //uses recurssive containment relations to find the parent geo coordinates, used if edits disrupted containment paths
	 function containRelationTime($itemUUID, $actRecord){
		  
		  $db = $this->startDB();
		  $sql = "SELECT start_time, end_time FROM initial_chrono_tag WHERE uuid = '$itemUUID' LIMIT 1; ";
		  $result = $db->fetchAll($sql);
		  if($result){
				$actRecord["Early (Cal. CE/BCE)"] = $result[0]["start_time"];
				$actRecord["Late (Cal. CE/BCE)"] = $result[0]["end_time"];
				if($result[0]["start_time"]<0){
					 $actRecord["Early (Cal. BP)"] = abs($result[0]["start_time"]) + 1950 ;
				}
				else{
					 $actRecord["Early (Cal. BP)"] = 1950 - $result[0]["start_time"];
				}
				if($result[0]["end_time"]<0){
					 $actRecord["Late (Cal. BP)"] = abs($result[0]["end_time"]) + 1950 ;
				}
				else{
					 $actRecord["Late (Cal. BP)"] = 1950 - $result[0]["end_time"];
				}
		  }
		  else{
				$parent = $this->getParentID($itemUUID);
				if($parent != false){
					 $actRecord = $this->containRelationTime($parent["uuid"], $actRecord);
				}
		  }
		  return $actRecord;
	 }
	 
	 function addTableContainRecord($spaceUUID, $projectUUID){ 
		  if($this->tableID){
				$db = $this->startDB();
				$hashID = sha1($spaceUUID."_".$this->tableID);
				$data = array("hashID" => $hashID,
								  "space_uuid" => $spaceUUID,
								  "project_uuid" => $projectUUID,
								  "table_id" => $this->tableID
								  );
				
				try{
					 $db->insert('tablecontents', $data);
					
				}
				catch (Exception $e) {
					 
				}
		  }
	 }
	 
	 
	 
	 //uses recurssive containment relations to find the parent time spans, used if edits disrupted containment paths
	 function containRelationGeo($itemUUID, $actRecord){
		  $db = $this->startDB();
		  $sql = "SELECT latitude, longitude FROM geo_space WHERE uuid = '$itemUUID' LIMIT 1; ";
		  $result = $db->fetchAll($sql);
		  if($result){
				$actRecord["Latitude (WGS84)"] = $result[0]["latitude"];
				$actRecord["Longitude (WGS84)"] = $result[0]["longitude"];
		  }
		  else{
				$parent = $this->getParentID($itemUUID);
				if($parent != false){
					 $actRecord = $this->containRelationGeo($parent["uuid"], $actRecord);
				}
		  }
		  return $actRecord;
	 }
	 
	 
	 
	 
	 
	 
	 
	 
	 //this adds linked data to a table record. 
	 function tableAddLinkedFields($itemUUID, $actRecord){
		  $linkedTypeConfigs = $this->linkedTypeConfigs;
		  $linkedFields = $this->linkedFields;
		  
		  if(is_array($linkedFields)){
				foreach($linkedFields as $linkedField){
					 
					 if($linkedField["linkedType"] == "type"){
						  if(array_key_exists($linkedField["linkedURI"], $linkedTypeConfigs)){
								//there's some special configuration for this linkeduri field!
								$actLF = array();
								foreach($linkedTypeConfigs[$linkedField["linkedURI"]] as $config){
									 $actConfig = $linkedField;
									 $actConfig["linkedLabel"] = $actConfig["linkedLabel"].$config["labeling"];
									 $actConfig["cond"] = $config["cond"];
									 $actLF[] = $actConfig;
								}
						  }
						  else{
								$actLF = array();
								$actConfig = $linkedField;
								$actConfig["cond"] = "";
								$actLF[] = $actConfig;
						  }
						  
						  foreach($actLF as $lf){
								
								$newFields = array();
								if($this->showFieldURIs){
									 $newFields["linkedURI"] = "URI: ".$lf["linkedLabel"]." (".$lf["linkedURI"].")";
									 $newFields["linkedLabel"] = "Label: ".$lf["linkedLabel"]." (".$lf["linkedURI"].")";
									 $newFields["val_text"] = "Source value: ".$lf["linkedLabel"]." (".$lf["linkedURI"].")";
								}
								else{
									 $newFields["linkedURI"] = $lf["linkedLabel"]." [URI]";
									 $newFields["linkedLabel"] = $lf["linkedLabel"]." [Label]";
									 $newFields["val_text"] = $lf["linkedLabel"]." [Source value]";
								}
								
								if($this->showLDSourceValues){
									 $linkedObject = $this->itemLinkedTypeValuesPlusSourceVals($itemUUID, $lf["varIDs"], $lf["cond"]);
								}
								else{
									 $linkedObject = $this->itemLinkedTypeValues($itemUUID, $lf["varIDs"], $lf["cond"]);
									 unset($newFields["val_text"]);
								}
								
								foreach($newFields as $fieldKey => $fieldLabel){
									 if(!$linkedObject){
										  $actRecord[$fieldLabel] = "";
									 }
									 else{
											$actRecord[$fieldLabel] = $linkedObject[0][$fieldKey]; //add the value returned from the database for a given field key
									 }
								}//end loop through field keys
						  }
						  
					 }
					 elseif($this->showUnitTypeFields && (strtolower($linkedField["linkedType"]) == "unit-type" || strtolower($linkedField["linkedType"]) == "measurement type")){
						  $linkedVal= $this->itemLinkedUnitTypeValues($itemUUID, $linkedField["varIDs"]);
						  if($this->showFieldURIs || $this->showUnitTypeURIs){
								$propKeyA = $linkedField["linkedLabel"]." (".$linkedField["linkedURI"].")";
						  }
						  else{
								$propKeyA = $linkedField["linkedLabel"];
						  }
						  $actRecord[$propKeyA] = $linkedVal;
					 }
					 
				}
		  }
		  return $actRecord;
	 }
	 
	 
	 //this adds source data to a table record. 
	 function tableAddSourceFields($itemUUID, $actRecord){
		  
		  $props = $this->itemProperties($itemUUID);
		  foreach($this->actVarLabels as $actLabel){
				$tabField = $actLabel." [Source]";
				$tabCell = "";
				foreach($props as $row){
					 if($row["var_label"] == $actLabel){
						  $tabCell = $row["val"];
						  break;
					 }
				}
				$actRecord[$tabField] = $tabCell;
		  }
		  
		  return $actRecord;
	 }
	 
	 
	 
	 function itemLinkedTypeValues($itemUUID, $actVarIDs, $optCondition = ""){
		  $db = $this->startDB();
		  
		  $varCondition = $this->makeORcondition($actVarIDs, "variable_uuid", "properties");
		  
		  $sql = "SELECT properties.property_uuid, linked_data.linkedLabel, linked_data.linkedURI
		  FROM observe
		  JOIN properties ON observe.property_uuid = properties.property_uuid
		  JOIN linked_data ON properties.property_uuid = linked_data.itemUUID
		  WHERE observe.subject_uuid = '$itemUUID' AND ($varCondition) $optCondition
		  ORDER BY linked_data.linkedLabel
		  ";
		  
		  $result = $db->fetchAll($sql);
		  return $result;
		  
	 }
	 
	 //get an array of the variables that are used as "type" variables in linked data
	 function getProjectsLinkedTypeVariables(){
		  $output = false;
		  $db = $this->startDB();
		  $projCondition = "";
		  if(is_array($this->limitingProjArray)){
				$projCondition = $this->makeORcondition($this->limitingProjArray, "fk_project_uuid", "linked_data");
				$projCondition = " AND (". $projCondition .") ";
		  }
		  
		  $sql = "SELECT itemUUID
		  FROM linked_data
		  WHERE itemType = 'variable' AND linkedType = 'type' $projCondition ;";
		  
		  $result = $db->fetchAll($sql);
		  if($result){
				$output = array();
				foreach($result as $row){
					 $output[] = $row["itemUUID"];
				}
		  }
		  
		  return $output;
	 }
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 function itemLinkedTypeValuesPlusSourceVals($itemUUID, $actVarIDs, $optCondition = ""){
		  $db = $this->startDB();
		  
		  $varCondition = $this->makeORcondition($actVarIDs, "variable_uuid", "properties");
		  
		  $sql = "SELECT properties.property_uuid, linked_data.linkedLabel, linked_data.linkedURI, val_tab.val_text
		  FROM observe
		  JOIN properties ON observe.property_uuid = properties.property_uuid
		  JOIN val_tab ON properties.value_uuid = val_tab.value_uuid
		  JOIN linked_data ON properties.property_uuid = linked_data.itemUUID
		  WHERE observe.subject_uuid = '$itemUUID' AND ($varCondition) $optCondition
		  ORDER BY linked_data.linkedLabel
		  ";
		  
		  $result = $db->fetchAll($sql);
		  return $result;
		  
	 }
	 
	 
	 
	 function itemLinkedUnitTypeValues($itemUUID, $actVarIDs){
		  $db = $this->startDB();
		  
		  $varCondition = $this->makeORcondition($actVarIDs, "variable_uuid", "properties");
		  
		   $sql = "SELECT  
				  IF (
				  val_tab.val_text IS NULL , (
					  IF (
					  properties.val_num =0, properties.val_num, properties.val_num)
					  ), 
					  val_tab.val_text
					  ) AS val
			  FROM observe
			  LEFT JOIN properties ON observe.property_uuid = properties.property_uuid
			  LEFT JOIN var_tab ON properties.variable_uuid = var_tab.variable_uuid
			  LEFT JOIN val_tab ON properties.value_uuid = val_tab.value_uuid
			  WHERE observe.subject_uuid = '$itemUUID'
			  AND ($varCondition)";
		  
		  $result = $db->fetchAll($sql);
		  if($result){
				return $result[0]["val"];
		  }
		  else{
				return "";
		  }
		  
	 }
	 
	 
	 
	 
	 //get the original (non ontology) properties for a given itemUUID
	 function itemProperties($itemUUID){
		  $db = $this->startDB();
		  
		  $sql = "SELECT properties.variable_uuid, properties.property_uuid,  
				  var_tab.var_label, 
				  IF (
				  val_tab.val_text IS NULL , (
					  IF (
					  properties.val_num =0, properties.val_num, properties.val_num)
					  ), 
					  val_tab.val_text
					  ) AS val, observe.source_id
			  FROM observe
			  LEFT JOIN properties ON observe.property_uuid = properties.property_uuid
			  LEFT JOIN var_tab ON properties.variable_uuid = var_tab.variable_uuid
			  LEFT JOIN val_tab ON properties.value_uuid = val_tab.value_uuid
			  WHERE observe.subject_uuid = '$itemUUID' 
			  ORDER BY var_tab.sort_order";
		  
		  return $db->fetchAll($sql);
		  
	 }
	 
	 
	 
	 
	 
	 
	 
	 //get a list of items in a class
	 function getClass($classUUID){
		  
		  $errors = array();
		  $db = $this->startDB();
		  
		  $projCondition = "";
		  $limitingSourceTabCoundition = "";
		  $limitingSourceTabJoin = "";
		  if(is_array($this->limitingProjArray)){
				$projCondition = $this->makeORcondition($this->limitingProjArray, "project_id", "space");
				$projCondition = " AND (". $projCondition .") ";
		  }
		  if(is_array($this->limitingSourceTabArray)){
				/*
				$limitingSourceTabJoin = " JOIN observe ON space.uuid = observe.subject_uuid ";
				$limitingSourceTabCoundition = $this->makeORcondition($this->limitingSourceTabArray, "source_id", "observe");
				$limitingSourceTabCoundition = " AND (". $limitingSourceTabCoundition .") ";
				//$limitingSourceTabJoin = " JOIN observe ON (space.uuid = observe.subject_uuid $limitingSourceTabCoundition )";
				*/
		  }
		  
		  $this->recStart = ($this->page - 1) * $this->setSize;
		  
		  $sql = "SELECT space.uuid, space.project_id, space.space_label, space.full_context
		  FROM space
		  $limitingSourceTabJoin
		  WHERE space.class_uuid = '$classUUID'  $projCondition $limitingSourceTabCoundition
		  ORDER BY space.project_id, space.label_sort, space.full_context
		  LIMIT ".($this->recStart ).",".($this->setSize)."
		  ;
		  ";
		  
		  if($this->DBtableID){
				if($this->checkExTableExists($this->DBtableID)){
					 //only change the SQL if the table has already been created
					 $sql = "SELECT space.uuid, space.project_id, space.space_label, space.full_context
					 FROM space
					 LEFT JOIN ".$this->DBtableID." AS ex ON space.uuid = ex.uuid
					 WHERE space.class_uuid = '$classUUID' AND ex.uuid IS NULL  $projCondition
					 ORDER BY space.project_id, space.label_sort, space.full_context
					 LIMIT ".($this->recStart ).",".($this->setSize)."
					 ;
					 ";
				}
		  }
		  
		  
		  $result =  $db->fetchAll($sql);
		  
		  if(is_array($this->limitingSourceTabArray)){
				//cull results without observations. this is slow, but for giant tables, memory issues mean it can't be done with a join
				$limitingSourceTabCoundition = $this->makeORcondition($this->limitingSourceTabArray, "source_id", "observe");
				$limitingSourceTabCoundition = " AND (". $limitingSourceTabCoundition .") ";
				$oldResult = $result;
				unset($result);
				$result = array();
				foreach($oldResult as $row){
					 $uuid = $row["uuid"];
					 $sql = "SELECT subject_uuid FROM observe WHERE subject_uuid = '$uuid' $limitingSourceTabCoundition LIMIT 1; ";
					 $resB = $db->fetchAll($sql);
					 if($resB){
						  $result[] = $row;
					 }
				}
				
		  }
		  return $result;
	 }
	 
	 
	 
	 function getClassLinkedMeasurements($classUUID){
		  
		  $errors = array();
		  $db = $this->startDB();
		  
		  $projCondition = "";
		  if(is_array($this->limitingProjArray)){
				$projCondition = $this->makeORcondition($this->limitingProjArray, "project_id", "space");
				$projCondition = " AND (". $projCondition .") ";
		  }
		  $limitingSourceTabCoundition = "";
		  if(is_array($this->limitingSourceTabArray)){
				$limitingSourceTabCoundition = $this->makeORcondition($this->limitingSourceTabArray, "source_id", "observe");
				$limitingSourceTabCoundition = " AND (". $limitingSourceTabCoundition .") ";
		  }
		  
		  $this->recStart = ($this->page - 1) * $this->setSize;
		  
		  $sql = "SELECT DISTINCT space.uuid, space.project_id, space.space_label, space.full_context
		  FROM space
		  JOIN observe ON space.uuid = observe.subject_uuid
		  JOIN properties ON properties.property_uuid = observe.property_uuid
		  JOIN linked_data ON (properties.variable_uuid = linked_data.itemUUID AND linked_data.linkedType LIKE '%Measurement type%')
		  WHERE space.class_uuid = '$classUUID'  $projCondition $limitingSourceTabCoundition
		  ORDER BY space.project_id, space.label_sort, space.full_context
		  LIMIT ".($this->recStart ).",".($this->setSize)."
		  ;
		  ";
		  
		  if($this->DBtableID){
				if($this->checkExTableExists($this->DBtableID)){
					 //only change the SQL if the table has already been created
					 $sql = "SELECT DISTINCT space.uuid, space.project_id, space.space_label, space.full_context
					 FROM space
					 JOIN observe ON space.uuid = observe.subject_uuid
					 JOIN properties ON properties.property_uuid = observe.property_uuid
					 JOIN linked_data ON (properties.variable_uuid = linked_data.itemUUID AND linked_data.linkedType LIKE '%Measurement type%')
					 LEFT JOIN ".$this->DBtableID." AS ex ON space.uuid = ex.uuid
					 WHERE space.class_uuid = '$classUUID' AND ex.uuid IS NULL $projCondition $limitingSourceTabCoundition
					 ORDER BY space.project_id, space.label_sort, space.full_context
					 LIMIT ".($this->recStart ).",".($this->setSize)."
					 ;
					 ";
					 
				}
		  }
		  
		  $result =  $db->fetchAll($sql);
		  return $result;
	 }
	 
	 
	 function getClassVarLimited($classUUID){
		  
		  $errors = array();
		  $db = $this->startDB();
		  
		  $projCondition = "";
		  $varCondition = "";
		  if(is_array($this->limitingProjArray)){
				$projCondition = $this->makeORcondition($this->limitingProjArray, "project_id", "space");
				$projCondition = " AND (". $projCondition .") ";
		  }
		  if(is_array($this->limitingVarArray)){
				$varCondition = $this->makeORcondition($this->limitingVarArray, "variable_uuid", "properties");
				$varCondition = " AND (". $varCondition .") ";
		  }
		  $limitingSourceTabCoundition = "";
		  if(is_array($this->limitingSourceTabArray)){
				$limitingSourceTabCoundition = $this->makeORcondition($this->limitingSourceTabArray, "source_id", "observe");
				$limitingSourceTabCoundition = " AND (". $limitingSourceTabCoundition .") ";
		  }
		  
		  $this->recStart = ($this->page - 1) * $this->setSize;
		  
		  $sql = "SELECT DISTINCT space.uuid, space.project_id, space.space_label, space.full_context
		  FROM space
		  JOIN observe ON space.uuid = observe.subject_uuid
		  JOIN properties ON properties.property_uuid = observe.property_uuid
		  WHERE space.class_uuid = '$classUUID'  $projCondition $varCondition $limitingSourceTabCoundition
		  ORDER BY space.project_id, space.label_sort, space.full_context
		  LIMIT ".($this->recStart ).",".($this->setSize)."
		  ;
		  ";
		  
		  if($this->DBtableID){
				if($this->checkExTableExists($this->DBtableID)){
					 //only change the SQL if the table has already been created
					 $sql = "SELECT DISTINCT space.uuid, space.project_id, space.space_label, space.full_context
					 FROM space
					 JOIN observe ON space.uuid = observe.subject_uuid
					 JOIN properties ON properties.property_uuid = observe.property_uuid
					 LEFT JOIN ".$this->DBtableID." AS ex ON space.uuid = ex.uuid
					 WHERE space.class_uuid = '$classUUID' AND ex.uuid IS NULL $projCondition $varCondition $limitingSourceTabCoundition
					 ORDER BY space.project_id, space.label_sort, space.full_context
					 LIMIT ".($this->recStart ).",".($this->setSize)."
					 ;
					 ";
					 
				}
		  }
		  
		  $result =  $db->fetchAll($sql);
		  return $result;
	 }
	 
	 
	 
	 
	 function getClassLinkTypeLimited($classUUID){
		  
		  $errors = array();
		  $db = $this->startDB();
		  
		  $projCondition = "";
		  $varCondition = "";
		  $limitingTypeCondition = "";
		  $obsJoins = "";
		  if(is_array($this->limitingProjArray)){
				$projCondition = $this->makeORcondition($this->limitingProjArray, "project_id", "space");
				$projCondition = " AND (". $projCondition .") ";
		  }
		  if(is_array($this->limitingVarArray)){
				$obsJoins = " JOIN observe ON space.uuid = observe.subject_uuid JOIN properties ON properties.property_uuid = observe.property_uuid ";
				$varCondition = $this->makeORcondition($this->limitingVarArray, "variable_uuid", "properties");
				$varCondition = " AND (". $varCondition .") ";
		  }
		  $limitingSourceTabCoundition = "";
		  if(is_array($this->limitingSourceTabArray)){
				if(strlen($obsJoins)< 2){
					 $obsJoins = " JOIN observe ON space.uuid = observe.subject_uuid ";
				}
				$limitingSourceTabCoundition = $this->makeORcondition($this->limitingSourceTabArray, "source_id", "observe");
				$limitingSourceTabCoundition = " AND (". $limitingSourceTabCoundition .") ";
		  }
		  if(is_array($this->limitingTypeURIs)){
				$varCondition = "";
				$obsJoins = "";
				$i = 1;
				foreach($this->limitingTypeURIs as $typeKey => $typeURIs){
					 $obsJoins .= "  JOIN observe AS obs_$i ON space.uuid = obs_$i.subject_uuid  JOIN linked_data AS ld_$i ON obs_$i.property_uuid = ld_$i.itemUUID ";  
					 $condition = $this->makeORcondition($typeURIs, "linkedURI", "ld_$i");
					 $limitingTypeCondition .= " AND (". $condition .") ";
					 $i++;
				}
		  }
		  
		  $this->recStart = ($this->page - 1) * $this->setSize;
		  
		  $sql = "SELECT DISTINCT space.uuid, space.project_id, space.space_label, space.full_context
		  FROM space
		  $obsJoins
		  WHERE space.class_uuid = '$classUUID'  $projCondition $varCondition $limitingTypeCondition $limitingSourceTabCoundition
		  ORDER BY space.project_id, space.label_sort, space.full_context
		  LIMIT ".($this->recStart ).",".($this->setSize)."
		  ;
		  ";
		  
		  
		  
		  if($this->DBtableID){
				if($this->checkExTableExists($this->DBtableID)){
					 //only change the SQL if the table has already been created
					 $sql = "SELECT DISTINCT space.uuid, space.project_id, space.space_label, space.full_context
					 FROM space
					 JOIN observe ON space.uuid = observe.subject_uuid
					 JOIN properties ON properties.property_uuid = observe.property_uuid
					 JOIN linked_data ON observe.property_uuid = linked_data.itemUUID
					 LEFT JOIN ".$this->DBtableID." AS ex ON space.uuid = ex.uuid
					 WHERE space.class_uuid = '$classUUID' AND ex.uuid IS NULL $projCondition $varCondition  $limitingTypeCondition $limitingSourceTabCoundition
					 ORDER BY space.project_id, space.label_sort, space.full_context
					 LIMIT ".($this->recStart ).",".($this->setSize)."
					 ;
					 ";
					 
				}
		  }
		  
		  $result =  $db->fetchAll($sql);
		  return $result;
	 }
	 
	 
	 
	 
	 
	 
	 //get the active variables used in a class
	 function getVariables($classUUID){
		  
		  if(!is_array($this->actVarIDs)){
				$db = $this->startDB();
				
				$projCondition = "";
				$varCondition = "";
				$linkedDataJoin = " ";
				$limitingTypeCondition = "";
				$limitingSourceTabCoundition = "";
				if(is_array($this->limitingProjArray)){
					 $projCondition = $this->makeORcondition($this->limitingProjArray, "project_id", "space");
					 $projCondition = " AND (". $projCondition .") ";
				}
				if(is_array($this->limitingVarArray)){
					 
					 //if we're limiting to a certain set of variables in the output, also add the linked type data
					 $additionalTypeVars = $this->getProjectsLinkedTypeVariables();
					 if(is_array($additionalTypeVars)){
						  $setVars = array_merge($this->limitingVarArray, $additionalTypeVars);
					 }
					 else{
						  $setVars = $this->limitingVarArray;
					 }
					 
					 $varCondition = $this->makeORcondition($setVars, "variable_uuid", "var_tab");
					 $varCondition  = " AND (". $varCondition  .") ";
				}
				if(is_array($this->limitingTypeURIs)){
					 $linkedDataJoin = "";
					 $i = 1;
					 foreach($this->limitingTypeURIs as $typeKey => $typeURIs){
						  $linkedDataJoin .= "  JOIN observe AS obs_$i ON space.uuid = obs_$i.subject_uuid  JOIN linked_data AS ld_$i ON obs_$i.property_uuid = ld_$i.itemUUID ";  
						  $condition = $this->makeORcondition($typeURIs, "linkedURI", "ld_$i");
						  $limitingTypeCondition .= " AND (". $condition .") ";
						  $i++;
					 }
				}
				if(is_array($this->limitingSourceTabArray)){
					 $limitingSourceTabCoundition = $this->makeORcondition($this->limitingSourceTabArray, "source_id", "var_tab");
					 $limitingSourceTabCoundition = " AND (". $limitingSourceTabCoundition .") ";
				}
				
				$sql = "SELECT round(COUNT(observe.subject_uuid)/10,0) as sCount, var_tab.variable_uuid, var_tab.var_label, var_tab.sort_order
				FROM space
				JOIN observe ON observe.subject_uuid = space.uuid
				JOIN properties ON observe.property_uuid = properties.property_uuid
				JOIN var_tab ON properties.variable_uuid = var_tab.variable_uuid
				$linkedDataJoin
				WHERE space.class_uuid = '$classUUID' $projCondition $varCondition $limitingTypeCondition $limitingSourceTabCoundition
				GROUP BY var_tab.variable_uuid
				ORDER BY sCount DESC, var_tab.sort_order, var_tab.var_label
				";
				
				$sql = "SELECT round(COUNT(observe.subject_uuid)/10,0) as sCount, var_tab.variable_uuid, var_tab.var_label, var_tab.sort_order
				FROM space
				JOIN observe ON observe.subject_uuid = space.uuid
				JOIN properties ON observe.property_uuid = properties.property_uuid
				JOIN var_tab ON properties.variable_uuid = var_tab.variable_uuid
				$linkedDataJoin
				WHERE space.class_uuid = '$classUUID' $projCondition $varCondition $limitingTypeCondition $limitingSourceTabCoundition
				GROUP BY var_tab.variable_uuid
				ORDER BY ".$this->sortForSourceVars."
				";
				
				$this->recordQuery($sql);
				$result =  $db->fetchAll($sql);
				$actVarLabels = array();
				if($result){
					 $this->actVariables = $result;
					 $actVarIDs = array(); //array of active variableIDs
					 $actVarLabels = array(); //temporary array of active var labels
					 foreach($result as $row){
						  if($row["sCount"]>0){
								$actVarIDs[] = $row["variable_uuid"];
								$varLabel = $row["var_label"];
								if(!in_array($varLabel, $actVarLabels)){
									 $actVarLabels[] = $varLabel;
								}
						  }
					 }
					 $this->actVarLabels = $actVarLabels;
					 $this->actVarIDs = $actVarIDs;
					 return $this->actVarIDs;
				}
				else{
					 $this->actVariables = false;
					 $this->actVarIDs = false;
					 return false;
				}
		  }
		  else{
				return $this->actVarIDs;
		  }
	 }
	 
	 //this function makes a list of unique properties and measurement types from
	 //external vocabularies referenced in the dataset. The variable UUIDs linked to these external concepts are provided
	 function getLinkedVariables($classUUID){
		  if(!is_array($this->linkedFields)){
				$db = $this->startDB();
				$actVarIDs = $this->getVariables($classUUID);
				$varCondition = $this->makeORcondition($actVarIDs, "variable_uuid", "var_tab");
				$itemCondition = $this->makeORcondition($actVarIDs, "itemUUID");
				
				$sql = "SELECT linked_data.linkedURI, linked_data.linkedLabel, linked_data.linkedType,
				IF (
				  linked_data.linkedType = 'unit',
					 AVG(var_tab.sort_order) + 10000,
					 AVG(var_tab.sort_order) 
					 ) AS  fSort
				FROM linked_data
				JOIN var_tab ON linked_data.itemUUID = var_tab.variable_uuid
				WHERE linked_data.linkedType != 'unit' AND ($varCondition)
				GROUP BY linked_data.linkedURI
				ORDER BY fSort
				";
		  
				$sql = "SELECT linked_data.linkedURI, linked_data.linkedLabel, linked_data.linkedType,
				CASE
					 WHEN linked_data.linkedType = 'type' THEN  AVG(var_tab.sort_order) - 10000
					 WHEN linked_data.linkedType = 'unit' THEN  AVG(var_tab.sort_order) + 10000
					 ELSE AVG(var_tab.sort_order)
				END AS  fSort
				FROM linked_data
				JOIN var_tab ON linked_data.itemUUID = var_tab.variable_uuid
				WHERE linked_data.linkedType != 'unit' AND ($varCondition)
				GROUP BY linked_data.linkedURI
				ORDER BY fSort
				";
		  
		  
				$this->recordQuery($sql);
				$result =  $db->fetchAll($sql);
				if($result){
					 $linkedFields = array();
					 foreach($result as $row){
						  
						  if($row["linkedType"] == "type"){
								$this->LFtypeCount++;
						  }
						  elseif($row["linkedType"] == "unit"){
								$this->LFunitTypeCount++;
						  }
						  
						  $actLinkedData = $row;
						  $actURI = $row["linkedURI"];
						  $sql = "SELECT itemUUID FROM linked_data WHERE linkedURI = '$actURI' AND ($itemCondition) ";
						  $resultB = $db->fetchAll($sql);
						  foreach($resultB as $rowB){
								$actLinkedData["varIDs"][] = $rowB["itemUUID"];
						  }
						  $linkedFields[] = $actLinkedData;
					 }
					 
					 $this->linkedFields = $linkedFields;
					 $this->countLinkedFieldValues();
					 return $this->linkedFields;
				}
				else{
					 $this->linkedFields = false;
					 return false;
				}
		  }
		  else{
				return $this->linkedFields;
		  }
	 }
	 
	 
	 function countLinkedFieldValues(){
		  
		  if(is_array($this->linkedFields)){
				$db = $this->startDB();
				$newLinkedFields = array();
				foreach($this->linkedFields as $lField){
					 $actField = $lField;
					 $varIDs = $lField["varIDs"];
					 $varCond = $this->makeORcondition($varIDs, "variable_uuid", "properties");
					 
					 $sql = "SELECT count(observe.property_uuid) as fCount, observe.subject_uuid
					 FROM observe
					 JOIN properties ON observe.property_uuid = properties.property_uuid
					 WHERE ($varCond)
					 GROUP BY observe.subject_uuid
					 ORDER BY fCount DESC
					 LIMIT 1;
					 ";
					 
					 $result =  $db->fetchAll($sql);
					 if($result){
						  $actField["fCount"] = $result[0]["fCount"];
						  $actField["example"] = "http://penelope.oc/preview/space?UUID=".$result[0]["subject_uuid"]; //for debugging
					 }
					 else{
						  $actField["fCount"] = false;
					 }
					 
					 $newLinkedFields[] = $actField;
				}
				
				$this->linkedFields = $newLinkedFields;
		  }
		  
	 }
	 
	 
	 
	 
	 
	 //makes an OR condition for a given value array, field, and maybe table
	 function makeORcondition($valueArray, $field, $table = false){
		  
		  if(!is_array($valueArray)){
				$valueArray = array(0 => $valueArray);
		  }
		  
		  if(!$table){
				$fieldPrefix = $field;
		  }
		  else{
				$fieldPrefix = $table.".".$field;
		  }
		  $allCond = false;
		  foreach($valueArray as $value){
				$actCond = "$fieldPrefix = '$value'";
				if(!$allCond ){
					 $allCond  = $actCond;
				}
				else{
					 $allCond  .= " OR ".$actCond;
				}
		  }
		  return $allCond ;
	 }
	 
	 
	 
	 
	 //get the uuid for the parent item
	 function getParentID($childUUID){
		  $db = $this->startDB();
		  
		  $sql = "SELECT space.uuid, space.space_label
		  FROM space_contain
		  JOIN space ON space.uuid = space_contain.parent_uuid
		  WHERE space_contain.child_uuid = '$childUUID'
		  LIMIT 1;
		  ";
		  
		  $result =  $db->fetchAll($sql);
		  if($result){
				return $result[0];
		  }
		  else{
				return false;
		  }
	 }
	 
	 //get the number of fields needed for the deepest containment hierarchy
	 function getMaxContextDepth($classUUID){
		  $db = $this->startDB();
		  
		  $projCondition = "";
		  if(is_array($this->limitingProjArray)){
				$projCondition = $this->makeORcondition($this->limitingProjArray, "project_id");
				$projCondition = " AND (". $projCondition .") ";
		  }
		  
		  $sql = "SELECT (LENGTH(full_context) - LENGTH(REPLACE(full_context, '".self::contextDelim."', ''))) / LENGTH('".self::contextDelim."') AS cnt
		  FROM space
		  WHERE class_uuid = '$classUUID' $projCondition
		  ORDER BY cnt DESC
		  LIMIT 1;
		  ";
		  
		  $this->recordQuery($sql);
		  $result =  $db->fetchAll($sql);
		  if($result){
				$this->maxContextFields = $result[0]["cnt"] - 1; //the minus one is there because the last is the item label
		  }
		  else{
				$this->maxContextFields = 0;
		  }
	 }
	 
	 //get the active projects
	 function getProjects($classUUID){
		  
		  if(!is_array($this->actProjectIDs)){
				$db = $this->startDB();
				
				$projCondition = "";
				if(is_array($this->limitingProjArray)){
					 $projCondition = $this->makeORcondition($this->limitingProjArray, "project_id", "project_list");
					 $projCondition = " AND (". $projCondition .") ";
				}
				
				$sql = "SELECT DISTINCT space.project_id, project_list.project_name
				FROM space
				JOIN project_list ON space.project_id = project_list.project_id
				WHERE space.class_uuid = '$classUUID' AND space.project_id != '0' $projCondition ";
				
				$this->recordQuery($sql);
				$result =  $db->fetchAll($sql);
				if($result){
					 $actProjectIDs = array();
					 $projectNames = array();
					 foreach($result as $row){
						  $projectUUID = $row["project_id"];
						  $actProjectIDs[] = $projectUUID;
						  $projectNames[$projectUUID] = $row["project_name"];
					 }
					 $this->actProjectIDs = $actProjectIDs;
					 $this->projectNames = $projectNames;
					 return $actProjectIDs;
				}
				else{
					 return false;
				}
		  }
		  else{
				return $this->actProjectIDs;
		  }
	 }
	 
	 
	 //this function gets geo-coordinates, uuids, and context-paths for all space items having coordinates in each a project
	 function getGeoTime(){
		  
		  if(is_array($this->actProjectIDs)){
				$db = $this->startDB();
				$geoTime = array();
				foreach($this->actProjectIDs as $projectID){
					 
					 $sql = "SELECT space.uuid, space.space_label, space.full_context, geo_space.latitude, geo_space.longitude,
					 (LENGTH(space.full_context) - LENGTH(REPLACE(space.full_context, '".self::contextDelim."', ''))) / LENGTH('".self::contextDelim."') AS cnt
					 FROM space
					 JOIN geo_space ON geo_space.uuid = space.uuid
					 WHERE space.project_id = '$projectID'
					 ORDER BY cnt DESC
					 LIMIT 500
					 ";
					 $this->recordQuery($sql);
					 $result =  $db->fetchAll($sql);
					 if($result){
						  foreach($result as $row){
								$context = $row["full_context"];
								$geoTime[$projectID]["geo"][$context] = array("latitude" => $row["latitude"], "longitude" => $row["longitude"]);
						  }
						  
					 }
					 else{
						  $geoTime[$projectID]["geo"] = false;
					 }
					 
					 
					 $sql = "SELECT space.uuid, space.space_label, space.full_context, initial_chrono_tag.start_time, initial_chrono_tag.end_time,
					 (LENGTH(space.full_context) - LENGTH(REPLACE(space.full_context, '".self::contextDelim."', ''))) / LENGTH('".self::contextDelim."') AS cnt
					 FROM space
					 JOIN initial_chrono_tag ON initial_chrono_tag.uuid = space.uuid
					 WHERE space.project_id = '$projectID'
					 ORDER BY cnt DESC
					 LIMIT 500
					 ";
					 $this->recordQuery($sql);
					 $result =  $db->fetchAll($sql);
					 if($result){
						  foreach($result as $row){
								$context = $row["full_context"];
								$geoTime[$projectID]["time"][$context] = array("start_time" => $row["start_time"], "end_time" => $row["end_time"]);
						  }
						  
					 }
					 else{
						  $geoTime[$projectID]["time"] = false;
					 } 
				}
				
				$this->geoTimeArray = $geoTime;
		  }

	 }
	 
	 //for debugging
	 function recordQuery($sql){
		  if($this->recordQueries){
				$queries = $this->queries;
				$queries[] = $sql;
				$this->queries = $queries;
		  }
	 }
	 
	 
	 
	 function makeHTML($tableArray){
		  
		  $output = '
		  <html>
    <head>
        <meta charset="UTF-8" />
        <title>Table Output</title>
        <style type="text/css">
            table, th, td {
                border:1px solid #C1CDCD;
                border-collapse:collapse;
                font-size:0.8em;
                font-family:Arial,Helvetica,sans-serif;
                padding: 2px;
            }
            th{
                background-color:#E0EEEE;
            }
        </style>
    </head>
    <body>
        <table>
            <thead>
                <tr>';
					 
		  foreach($tableArray as $recordkey => $fieldVals){
				$output .= "<th>Record URI</th>".chr(13);
				foreach($fieldVals as $fieldKey => $value){
					 if($this->ExcelSanitize){
						  $output .= "<th>=\"".$fieldKey."\"</th>".chr(13);
					 }
					 else{
						  $output .= "<th>$fieldKey</th>".chr(13);
					 }
				}
            break; //no need to do the whole array, we're setting up the heading
        }//end loop through the table array  
		  
		  $output .= "</tr>
					 </thead>
					 <tbody>";
        
		  $i = 1;
        foreach($tableArray as $recordkey => $fieldVals){
				
            $output .= '<tr id="rec-'.$i.'" >'.chr(13);
            $output .= '<td>'.$recordkey.'</td>';
            foreach($fieldVals as $fieldKey => $value){
					 if($this->ExcelSanitize){
						  $output .= "<td>=\"".$value."\"</td>";
					 }
					 else{
						  $output .= "<td>".$value."</td>";
					 }
            }//end loop through the fields and values
            $output .= '</tr>'.chr(13);
				$i++;
        }//end loop through the table array 
       
		   $output .= "
						  </tbody>
					 </table>
				</body>
		  </html>";
	 
	 return $output;  
	 }
	 
	 //save the file
	 function saveFile($itemDirFile, $text){
		  $success = false; //save failure
		  try{
			  iconv_set_encoding("internal_encoding", "UTF-8");
			  iconv_set_encoding("output_encoding", "UTF-8");
			  $fp = fopen($itemDirFile, 'w');
			  fwrite($fp, $text);
			  fclose($fp);
			  $success = true;
		  }
		  catch (Zend_Exception $e){
			  $success = false; //save failure
		  }
		  return $success;
	 }
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 //save a record to the output data table
	 function saveActRecord($uuid, $actRecord){
		  $output = false;
		  if($this->DBtableID){
				$db = $this->startDB();
				$i = 1;
				$data = array("uuid" => $uuid,
								  "uri" => self::OCspatialURIbase.$uuid
								  );
				foreach($actRecord as $fieldKey => $value){
					 $dataField = "field_".$i ;
					 $data[$dataField] = $value;
					 $i++;
				}
				
				try{
					 $db->insert($this->DBtableID, $data); //add the unit
					 $output = true;
				}
				catch (Exception $e) {
					 $output = false;
				}
		  }
		  
		  return $output;
	 }
	 
	 function checkExTableExists($tab){
		  $db = $this->startDB();
		  $sql = "SELECT 1 FROM ".$tab." LIMIT 1;";
		  $tableExists = false;
		  try{
				$result =  $db->fetchAll($sql);
				$tableExists = true;
		  }
		  catch (Exception $e) {
				$tableExists = false;
		  }
		  
		  return $tableExists;
	 }
	 
	 
	 
	 function createExportTable($actRecord){
		  
		  if($this->DBtableID){
				$db = $this->startDB();
				
				$tableExists = $this->checkExTableExists($this->DBtableID);
				
				if(!$tableExists){
					//make the table
					 
					 $where = "source_id = '".$this->DBtableID."' ";
					 $db->delete("export_tabs_fields", $where);
					 
					 $i = 1;
					 $data = array("source_id" => $this->DBtableID);
					 $createFields = array();
					 $fieldErrors = false;
					 foreach($actRecord as $fieldKey => $value){
						  $dataField = "field_".$i ;
						  $createFields[] = $dataField ;
						  
						  //save the export field names
						  $data = array("source_id" => $this->DBtableID,
											 "field_num" => $i,
											 "field_name" => $dataField,
											 "field_label" => $fieldKey
											 );
						  
						  try{
								$db->insert("export_tabs_fields", $data); //add the unit
						  }
						  catch (Exception $e) {
								$fieldErrors = true;
						  }
						  
						  unset($data);
						  $i++;
					 }
					 
					 if(!$fieldErrors){
						  
						  $fieldsToCreate = implode(" text,", $createFields) ." text CHARACTER SET utf8";
						  $schemaSql = "CREATE TABLE ".$this->DBtableID."  (
										 id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
										 uuid varchar(50) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
										 uri varchar(200) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
										 $fieldsToCreate,
										 UNIQUE KEY uuid (uuid)
										 )ENGINE=MyISAM DEFAULT CHARSET=utf8;
										 ";
						  
						  $db->query($schemaSql);
						  $alterSQL = "ALTER TABLE ".$this->DBtableID." DEFAULT CHARACTER SET utf8 COLLATE  utf8_general_ci;";
						  $db->query($alterSQL);
						  
						  $j = 1;
						  while($j < $i){
								if(isset($createFields[$j]) && $j <= 20){
									 $indexSQL = "CREATE INDEX fInd_".$j." ON ".$this->DBtableID."(".$createFields[$j]."(20));";
									 $db->query($indexSQL);
								}
								$j++;
						  }						  						  
					 }
				}
				
		  }
		  
	 }
	 
	 
	 
	 
	 
	 
	 function startDB(){
		  if(!$this->db){
				$db = Zend_Registry::get('db');
				$this->setUTFconnection($db);
				$this->db = $db;
		  }
		  else{
				$db = $this->db;
		  }
		  
		  return $db;
	 }
	 
	 function setUTFconnection($db){
		  $sql = "SET collation_connection = utf8_unicode_ci;";
		  $db->query($sql, 2);
		  $sql = "SET NAMES utf8;";
		  $db->query($sql, 2);
    }
	 

    
}  
