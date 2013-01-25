<?php

class TabOut_Table  {
    
    
	 public $actProjectIDs; //array of active projects
	 public $actVariables; //array of active variables (variable_uuid, var_label, sort_order)
	 public $actVarIDs; //array of active variableIDs
	 
	 public $maxContextFields; //number of context depth
	 public $linkedFields; //standard fields from ontology links
	 
	 public $db;
	 
	 function getClass($classUUID){
		  
		  $errors = array();
		  $db = $this->startDB();
		  
		  $sql = "SELECT uuid, space_label, full_context
		  FROM space
		  WHERE class_uuid = '$classUUID';
		  ";
		  
		  $result =  $db->fetchAll($sql);
		  foreach($result as $row){
				
		  }
		  
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
						  $actField["weirdSub"] = "http://penelope.oc/preview/space?UUID=".$result[0]["subject_uuid"]; //for debugging
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
	 function getMaxContext($classUUID){
		  $db = $this->startDB();
		  
		  $sql = "SELECT (LENGTH(full_context) - LENGTH(REPLACE(full_context, '|xx|', ''))) / LENGTH('|xx|') AS cnt
		  FROM space
		  WHERE class_uuid = '$classUUID'
		  ORDER BY cnt DESC
		  LIMIT 1;
		  ";
		  
		  $result =  $db->fetchAll($sql);
		  if($result){
				$this->maxContextFields = $result[0]["cnt"];
		  }
		  else{
				$this->maxContextFields = 0;
		  }
	 }
	 
	 //get the active projects
	 function getProjects($classUUID){
		  
		  if(!is_array($this->actProjectIDs)){
				$db = $this->startDB();
				
				$sql = "SELECT DISTINCT project_id
				FROM space
				WHERE class_uuid = '$classUUID' AND project_id != '0' ";
				
				$result =  $db->fetchAll($sql);
				if($result){
					 $actProjectIDs = array();
					 foreach($result as $row){
						  $actProjectIDs[] = $row["project_id"];
					 }
					 $this->actProjectIDs = $actProjectIDs;
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
