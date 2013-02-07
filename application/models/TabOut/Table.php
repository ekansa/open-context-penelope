<?php

class TabOut_Table  {
    
    public $tabArray;
	 public $projectNames; //array of project names
	 public $actProjectIDs; //array of active projects
	 public $actVariables; //array of active variables (variable_uuid, var_label, sort_order)
	 public $actVarIDs; //array of active variableIDs
	 public $actVarLabels; //array of active variable labels (for whole table, across multiple projects)
	 
	 public $maxContextFields; //number of context depth
	 
	 public $showFieldURIs = false; //show the URIs of fields for linked data
	 public $linkedFields; //standard fields from ontology links
	 public $LFtypeCount = 0; //count of linked fields of type type
	 public $LFunitTypeCount = 0; //count of linked fields of type unit-type
	 
	 public $db;
	 
	 public $page;
	 public $recStart;
	 public $setSize;
	 
	 const OCspatialURIbase = "http://opencontext.org/subjects/";
	 const OCprojectURIbase = "http://opencontext.org/projects/";
	 const contextDelim = "|xx|";
	 
	 
	 public $linkedTypeConfigs = array("http://opencontext.org/vocabularies/open-context-zooarch/zoo-0077" =>
												  array(0 => array(	"labeling" => " (distal)",
																		   "cond" => " AND linked_data.linkedLabel LIKE '%distal%'"),
														  1 => array(	"labeling" => " (proximal)",
																				"cond" => " AND linked_data.linkedLabel LIKE '%proximal%'")
														 )
												  );
	 
	 
	 
	 function makeTableArray($classUUID){
		  
		  $this->getMaxContextDepth($classUUID); //get the maximum context depth
		  $this->getProjects($classUUID);
		  $projectNames = $this->projectNames;
		  $this->getLinkedVariables($classUUID);
		  
		  $result = $this->getClass($classUUID); //get the list of items, their labels, and their context
		  if($result){
				$tabArray = array();
				foreach($result as $row){
					 $actRecord = array();
					 $itemUUID = $row["uuid"];
					 $uuidKey = self::OCspatialURIbase.$row["uuid"];
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
					 
					 $actRecord["Item label"] = $label;
					 $actRecord["Project URI"] = $projectURI;
					 $actRecord["Project name"] = $projectName;
					 
					 
					 $rawContext = $row["full_context"];
					 $rawContextArray = explode(self::contextDelim, $rawContext);
					 $i = 0;
					 while($i < $this->maxContextFields){
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
					 
					 $actRecord = $this->tableAddLinkedFields($itemUUID, $actRecord); //add the linked data fields
					 $actRecord = $this->tableAddSourceFields($itemUUID, $actRecord); //add the source data fields
					 
					 $tabArray[$uuidKey] = $actRecord;
				}
				
				return $tabArray;
		  }
		  else{
				return false;
		  }
		  
	 }
	 
	 
	 //this adds linked data to a table record. 
	 function tableAddLinkedFields($itemUUID, $actRecord){
		  $linkedTypeConfigs = $this->linkedTypeConfigs;
		  $linkedFields = $this->linkedFields;
		  
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
						  $linkedObject = $this->itemLinkedTypeValues($itemUUID, $lf["varIDs"], $lf["cond"]);
						  
						  if($this->showFieldURIs){
								$propKeyA = "URI: ".$lf["linkedLabel"]." (".$lf["linkedURI"].")";
								$propKeyB = "Label: ".$lf["linkedLabel"]." (".$lf["linkedURI"].")";
						  }
						  else{
								$propKeyA = $lf["linkedLabel"]." [URI]";
								$propKeyB = $lf["linkedLabel"]." [Label]";
						  }
						  if(!$linkedObject){
								$actRecord[$propKeyA] = "";
								$actRecord[$propKeyB] = "";
						  }
						  else{
								$actRecord[$propKeyA] = $linkedObject[0]["linkedURI"];
								$actRecord[$propKeyB] = $linkedObject[0]["linkedLabel"];
						  }
					 }
					 
				}
				elseif($linkedField["linkedType"] == "unit-type"){
					 $linkedVal= $this->itemLinkedUnitTypeValues($itemUUID, $linkedField["varIDs"]);
					 if($this->showFieldURIs){
						  $propKeyA = $linkedField["linkedLabel"]." (".$linkedField["linkedURI"].")";
					 }
					 else{
						  $propKeyA = $linkedField["linkedLabel"];
					 }
					 $actRecord[$propKeyA] = $linkedVal;
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
		  
		  $this->recStart = ($this->page - 1) * $this->setSize;
		  
		  $sql = "SELECT uuid, project_id, space_label, full_context
		  FROM space
		  WHERE class_uuid = '$classUUID'
		  ORDER BY project_id, full_context
		  LIMIT ".($this->recStart ).",".($this->setSize)."
		  ;
		  ";
		  
		  $result =  $db->fetchAll($sql);
		  return $result;
	 }
	 
	 
	 
	 
	 
	 
	 
	 //get the active variables used in a class
	 function getVariables($classUUID){
		  
		  if(!is_array($this->actVarIDs)){
				$db = $this->startDB();
				
				$sql = "SELECT round(COUNT(observe.subject_uuid)/10,0) as sCount, var_tab.variable_uuid, var_tab.var_label, var_tab.sort_order
				FROM space
				JOIN observe ON observe.subject_uuid = space.uuid
				JOIN properties ON observe.property_uuid = properties.property_uuid
				JOIN var_tab ON properties.variable_uuid = var_tab.variable_uuid
				WHERE space.class_uuid = '$classUUID'
				GROUP BY var_tab.variable_uuid
				ORDER BY sCount DESC, var_tab.sort_order, var_tab.var_label
				";
				
				$result =  $db->fetchAll($sql);
				$actVarLabels = array();
				if($result){
					 $this->actVariables = $result;
					 $actVarIDs = array(); //array of active variableIDs
					 $actVarLabels = array(); //temporary array of active var labels
					 foreach($result as $row){
						  $actVarIDs[] = $row["variable_uuid"];
						  $varLabel = $row["var_label"];
						  if(!in_array($varLabel, $actVarLabels)){
								$actVarLabels[] = $varLabel;
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
				  linked_data.linkedType = 'unit-type',
					 AVG(var_tab.sort_order) + 100,
					 AVG(var_tab.sort_order) 
					 ) AS  fSort
				FROM linked_data
				JOIN var_tab ON linked_data.itemUUID = var_tab.variable_uuid
				WHERE linked_data.linkedType != 'unit' AND ($varCondition)
				GROUP BY linked_data.linkedURI
				ORDER BY fSort
				";
		  
				$result =  $db->fetchAll($sql);
				if($result){
					 $linkedFields = array();
					 foreach($result as $row){
						  
						  if($row["linkedType"] == "type"){
								$this->LFtypeCount++;
						  }
						  elseif($row["linkedType"] == "unit-type"){
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
		  
		  $sql = "SELECT (LENGTH(full_context) - LENGTH(REPLACE(full_context, '".self::contextDelim."', ''))) / LENGTH('".self::contextDelim."') AS cnt
		  FROM space
		  WHERE class_uuid = '$classUUID'
		  ORDER BY cnt DESC
		  LIMIT 1;
		  ";
		  
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
				
				
				$sql = "SELECT DISTINCT space.project_id, project_list.project_name
				FROM space
				JOIN project_list ON space.project_id = project_list.project_id
				WHERE space.class_uuid = '$classUUID' AND space.project_id != '0' ";
				
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
