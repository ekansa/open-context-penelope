<?php

class TabOut_Table  {
    
    public $tabArray;
	 public $projectNames; //array of project names
	 public $actProjectIDs; //array of active projects
	 public $actVariables; //array of active variables (variable_uuid, var_label, sort_order)
	 public $actVarIDs; //array of active variableIDs
	 
	 public $maxContextFields; //number of context depth
	 public $linkedFields; //standard fields from ontology links
	 
	 public $db;
	 
	 public $page;
	 public $recStart;
	 public $setSize;
	 
	 const OCspatialURIbase = "http://opencontext.org/subjects/";
	 const OCprojectURIbase = "http://opencontext.org/projects/";
	 const contextDelim = "|xx|";
	 
	 
	 function makeTableArray($classUUID){
		  
		  $this->getMaxContextDepth($classUUID); //get the maximum context depth
		  $this->getProjects($classUUID);
		  $projectNames = $this->projectNames;
		  $result = $this->getClass($classUUID); //get the list of items, their labels, and their context
		  if($result){
				$tabArray = array();
				foreach($result as $row){
					 $actRecord = array();
					 
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
					 
				
					 $actRecord["Parent context URI"] = $parentURI;
					 
					 $tabArray[$uuidKey] = $actRecord;
				}
				
				return $tabArray;
		  }
		  else{
				return false;
		  }
		  
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
				
				$sql = "SELECT DISTINCT var_tab.variable_uuid, var_tab.var_label, var_tab.sort_order
				FROM space
				JOIN observe ON observe.subject_uuid = space.uuid
				JOIN properties ON observe.property_uuid = properties.property_uuid
				JOIN var_tab ON properties.variable_uuid = var_tab.variable_uuid
				WHERE space.class_uuid = '$classUUID'
				";
				
				$result =  $db->fetchAll($sql);
				if($result){
					 $this->actVariables = $result;
					 $actVarIDs = array(); //array of active variableIDs
					 foreach($result as $row){
						  $actVarIDs[] = $row["variable_uuid"];
					 }
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
				AVG(var_tab.sort_order) as fSort
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
