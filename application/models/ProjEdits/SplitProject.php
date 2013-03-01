<?php
/*
This class process a work book full of measurements (as saved in a "fods" xml format from Libre Office)
It's specifically designed to parse through the Levent Atici's data

I'm including it, since it may be useful to adapt to other projects

*/
class ProjEdits_SplitProject  {
    
   
    public $db;
	 public $oldProjectUUID;
	 public $newProjectUUID;
	 public $oldContainText;
	 public $newContainText;
	 public $queries = array();
	
	
	 function recordQuery($sql){
		  $queries = $this->queries;
		  $queries[] = $sql;
		  $this->queries = $queries;
	 }
	 
	//get the properties for a project
	 function getDistinctProperties($rootContext){
		  $db = $this->startDB();
		  $sql = "SELECT observe.property_uuid
		  FROM observe
		  JOIN space ON space.uuid = observe.subject_uuid
		  WHERE space.project_id = '".$this->oldProjectUUID."'
		  AND space.full_context LIKE 'Turkey|xx|Okuzini Cave%'
		  GROUP BY observe.property_uuid
		  ";
		  $this->recordQuery($sql);
		  $result = $db->fetchAll($sql, 2);
		  $output = array();
		  foreach($result as $row){
				$oldPropertyUUID = $row["property_uuid"];
				$newPropertyUUID = $this->getCloneProperty($oldPropertyUUID);
				$output[] = array("old" => $oldPropertyUUID, "new" => $newPropertyUUID);
		  }
        return $output;
	 }
	 
	 
	 function updateSpaceObs($rootContext){
		  
		  $db = $this->startDB();
		  $sql = "SELECT observe.subject_uuid, observe.property_uuid, space.full_context
		  FROM observe
		  JOIN space ON space.uuid = observe.subject_uuid
		  WHERE space.project_id = '".$this->oldProjectUUID."'
		  AND space.full_context LIKE '".$rootContext."%'
		  ";
		  $this->recordQuery($sql);
		  $result = $db->fetchAll($sql, 2);
		  $output = array();
		  foreach($result as $row){
				$subjectUUID = $row["subject_uuid"];
				$oldPropertyUUID = $row["property_uuid"];
				$fullcontext = $row["full_context"];
				$newPropertyUUID = $this->getCloneProperty($oldPropertyUUID);
				$this->updateAnySpaceContainProject($subjectUUID);
				$this->updateSpaceProject($subjectUUID, $fullcontext);
				$this->updateObservation($subjectUUID, $oldPropertyUUID, $newPropertyUUID);
				$output[] = $subjectUUID ;
		  }
        return $output;
		  
		  
		  
	 }
	 
	
	
	 function updateObservation($subjectUUID, $oldPropertyUUID, $newPropertyUUID){
		  
		  $db = $this->startDB();
		  $sql = "SELECT * FROM observe WHERE subject_uuid = '$subjectUUID' AND property_uuid = '$oldPropertyUUID' LIMIT 1;";
		  $this->recordQuery($sql);
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				$data = $result[0];
				$where = array();
				$data["hash_obs"] = md5($this->newProjectUUID . "_" . $subjectUUID . "_" . $data["obs_num"] . "_" . $newPropertyUUID);
				$data["project_id"] = $this->newProjectUUID;
				$data["property_uuid"] = $newPropertyUUID;
				$where[] = "subject_uuid = '$subjectUUID' ";
				$where[] = "property_uuid = '$oldPropertyUUID' ";
				$db->update("observe", $data, $where);
		  }
	 }
	
	
	
	 function getCloneProperty($oldPropertyUUID){
		  
		  $newUUID = $this->getCloneRecord($oldPropertyUUID);
		  if(!$newUUID){
				$newUUID = $this->cloneProperty($oldPropertyUUID);
		  }
		  return $newUUID;
	 }
	
	
	 function cloneProperty($oldPropertyUUID){
		  $newUUID = false;
		  $db = $this->startDB();
		  $sql = "SELECT * FROM properties WHERE property_uuid = '$oldPropertyUUID' LIMIT 1; ";
		  
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				
				$oldVarUUID = $result[0]["variable_uuid"];
				$oldValueUUID = $result[0]["value_uuid"];
				$newVarUUID = $this->getCloneVariable($oldVarUUID);
				$newValUUID = $this->getCloneValue($oldValueUUID );
				
				$newUUID = GenericFunctions::generateUUID();
				$propHash   = md5($this->newProjectUUID . $newVarUUID . $newValUUID);
				$data = $result[0];
				$data["project_id"] = $this->newProjectUUID;
				$data["prop_hash"] = $propHash;
				$data["variable_uuid"] = $newVarUUID;
				$data["value_uuid"] = $newValUUID;
				$data["property_uuid"] = $newUUID;
				$db->insert("properties", $data );
				$this->cloneLinkedData($oldPropertyUUID, $newUUID);
				$this->addCloneRecord($oldPropertyUUID, $newUUID, "property");
		  }
		  
		  return $newUUID;
	 }
	
	
	
	
	 function getCloneVariable($oldVarUUID){
		  $newUUID = $this->getCloneRecord($oldVarUUID);
		  if(!$newUUID){
				$newUUID = $this->cloneVariable($oldVarUUID);
		  }
		  return $newUUID;
	 }
	
	 
	 function getCloneValue($oldValueUUID){
		  $newUUID = $this->getCloneRecord($oldValueUUID);
		  if(!$newUUID){
				$newUUID = $this->cloneValue($oldValueUUID);
		  }
		  return $newUUID;
	 }
	
	 
	
	 function cloneVariable($oldVarUUID){
		  $newUUID = false;
		  $db = $this->startDB();
		  $sql = "SELECT * FROM var_tab WHERE variable_uuid = '$oldVarUUID' LIMIT 1; ";
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				$newUUID = GenericFunctions::generateUUID();
				$data = $result[0];
				$varhash    = md5($this->newProjectUUID . $data["var_label"] . $data["var_type"]);
				unset($data["pk_var"]);
				$data["var_hash"] = $varhash;
				$data["project_id"] = $this->newProjectUUID;
				$data["variable_uuid"] = $newUUID;
				$db->insert("var_tab", $data );
				$this->cloneLinkedData($oldVarUUID, $newUUID);
				$this->addCloneRecord($oldVarUUID, $newUUID, "variable");
		  }
		  return $newUUID;
	 }
	 
	
	 function cloneValue($oldValueUUID){
		  $newUUID = false;
		  $db = $this->startDB();
		  $sql = "SELECT * FROM val_tab WHERE value_uuid = '$oldValueUUID' LIMIT 1; ";
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				$newUUID = GenericFunctions::generateUUID();
				$data = $result[0];
				$valScram   = md5($data["val_text"] . $this->newProjectUUID);
				$data["text_scram"] = $valScram;
				$data["project_id"] = $this->newProjectUUID;
				$data["value_uuid"] = $newUUID;
				$db->insert("val_tab", $data );
				$this->addCloneRecord($oldValueUUID, $newUUID, "value");
		  }
		  return $newUUID;
	 }
	
	
	 function cloneLinkedData($oldUUID, $newUUID){
		 
		  $db = $this->startDB();
		  $sql = "SELECT * FROM linked_data WHERE itemUUID = '$oldUUID' ";
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				foreach($result as $row){
					 $data = $row;
					 $hash = md5($newUUID."_".$data["linkedURI"]);
					 $data["hashID"] = $hash;
					 $data["itemUUID"] = $newUUID;
					 $data["fk_project_uuid"] = $this->newProjectUUID;
					 $db->insert("linked_data", $data);
				}
		  }
	 }
	
	 
	 function getCloneRecord($oldUUID){
		  
		  $db = $this->startDB();
		  $sql = "SELECT newUUID FROM splitids WHERE oldUUID = '$oldUUID' LIMIT 1; ";
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				return $result[0]["newUUID"];
		  }
		  else{
				return false;
		  }
	 }
	 
	 
	 
	 
	 //get a clone record
	 function addCloneRecord($oldUUID, $newUUID, $type){
		  $db = $this->startDB();
		  $data = array("oldUUID" => $oldUUID,
							 "newUUID" => $newUUID,
							 "type" => $type
							 );
		  
		  $db->insert("splitids", $data);
	 }
	
	 
	 //get links to child items
	 function getChildItems($parentUUID, $items = array(), $recursive = false){
		  $db = $this->startDB();
		  
		  $sql = "SELECT DISTINCT space_contain.child_uuid
		  FROM space_contain
		  WHERE space_contain.parent_uuid = '$parentUUID'
		  ";
		  
		  $result = $db->fetchAll($sql, 2);
        if($result){
				foreach($result as $row){
					 $childUUID = $row["child_uuid"];
					 if($recursive){
						  $items = $this->getChildItems($childUUID, $items, true);
					 }
					 $items[] = $childUUID;
				}
		  }
		  return $items;
	 }
	 
	 
	 //update space containment
	 function updateSpaceContainProject($parentUUID, $childUUID){
		  $db = $this->startDB();
		  $where = array();
		  $where[] = "parent_uuid = '$parentUUID' ";
		  $where[] = "child_uuid = '$childUUID' ";
		  $data = array("project_id" => $this->newProjectUUID);
		  $db->update("space_contain", $data, $where);
	 }
	 
	 //update space containment
	 function updateAnySpaceContainProject($itemUUID){
		  $db = $this->startDB();
		  $where = array();
		  $where[] = "parent_uuid = '$itemUUID' OR child_uuid = '$itemUUID' ";
		  $data = array("project_id" => $this->newProjectUUID);
		  $db->update("space_contain", $data, $where);
	 }
	 
	 
	 //update a project space ID
	 function updateSpaceProject($spaceUUID, $fullcontext){
		  $db = $this->startDB();
		  $data = array("project_id" => $this->newProjectUUID);
		  
		  if($this->oldContainText && $this->newContainText ){
				$fullcontext = str_replace($this->oldContainText, $this->newContainText, $fullcontext);
				$data["full_context"] = $fullcontext;
		  }
		  
		  $hashTxt    = md5($this->newProjectUUID . "_" . $fullcontext);
		  $data["hash_fcntxt" ] = $hashTxt;
		  $data["source_id" ] = "sp-".$data["source_id"];
		  $where = array();
		  $where[]= "uuid = '$spaceUUID' ";
		  $db->update("space", $data, $where);
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
