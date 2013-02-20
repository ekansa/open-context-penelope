<?php

class LinkedData_PropSplitLink  {
    
    public $db;
    public $errors;
	 
	 public $predicateURI; //URI for the predicate to find the variable that contains properties we want to alter
	 public $actVarUUID; //UUID for the varible that contains properties we want to alter
	 
	 public $oldPropUUID; //uuid for the old property that will be spit into a new one
	 public $oldPropertyUpToDate; //boolean! is the old property up to date and already associated with the newLinkURI
	 public $newPropUUID; //uuid for the new property value that will be created based on the old
	 public $newLinkURI; //URI for the new link
	 
	 public $subjectUUID; //uuid for the subject item that will be changed
	 public $classUUID; //uuid for the class to search for subjectIDs from labels
	 public $projectUUID; //uuid of the project
	
	 
	 function resetForLoop(){
		  $this->oldPropUUID = false;
		  $this->subjectUUID = false;
		  $this->newPropUUID = false;
		  $this->newLinkURI = false;
	 }
	
	 
	 
	 function alterObsNewLinkingProperty(){
		  $db = $this->startDB();
		  
		  if($this->subjectUUID != false && $this->oldPropUUID != false){
				$sql = "SELECT *
				FROM observe
				WHERE subject_uuid = '".$this->subjectUUID."'
				AND property_uuid = '".$this->oldPropUUID."'
				LIMIT 1;
				";
				
				$result = $db->fetchAll($sql, 2);
				if($result){
					 $newPropUUID = $this->getMakeNewPropertyURI();
					 if($newPropUUID != false && $newPropUUID != $this->oldPropUUID){
						  $where = "hash_obs = '".$result[0]["hash_obs"]."' ";
						  $newObsHash = md5($this->projectUUID . "_" . $this->subjectUUID . "_" . $result[0]["obs_num"] . "_" . $newPropUUID);
						  $data = array("hash_obs" => $newObsHash,
											 "property_uuid" => $newPropUUID);
						  $db->update("observe", $data, $where);
						  $this->deleteFromPublished($this->subjectUUID); //delete from the published list, good for making updates
					 }
				}
				else{
					 $errors[] = "Empty: ".$sql;
					 $this->noteErrors($errors);
					 return false;
				}
		  }
	 }
	 
	 function deleteFromPublished($itemUUID){
		  $db = $this->startDB();
		  $where = "item_uuid = '$itemUUID' ";
		  $db->delete("published_docs", $where);
	 }
	 
	 
	 function getMakeNewPropertyURI(){
		 
		  $oldPropertyUpToDate = $this->oldPropertyLinkURIUpToDate();
		  if(!$oldPropertyUpToDate && strlen($this->newLinkURI)>4 && $this->subjectUUID != false){
				$newExists = $this->checkNewPropExists();
				if(!$newExists){
					 $newPropUUID = $this->duplicateOldProp($this->oldPropUUID);
					 $this->newPropUUID = $newPropUUID;
					 $this->addLinkURItoProperty($newPropUUID, $this->newLinkURI);
				}
				else{
					 $newPropUUID = $this->newPropUUID;
				}
				return $newPropUUID;
		  }
		  else{
				return false;
		  }
	 }
	 
	 function checkNewPropExists(){
		  
		  $db = $this->startDB();
		  
		  $sql = "SELECT linked_data.itemUUID
		  FROM linked_data
		  JOIN properties ON linked_data.itemUUID = properties.property_uuid
		  JOIN properties AS oldprop ON (oldprop.variable_uuid = properties.variable_uuid AND oldprop.value_uuid = properties.value_uuid)
		  WHERE linked_data.fk_project_uuid = '".$this->projectUUID."'
		  AND linked_data.linkedURI = '".$this->newLinkURI."'
		  AND oldprop.property_uuid = '".$this->oldPropUUID."'
		  LIMIT 1;
		  ";
		  
		  $result = $db->fetchAll($sql, 2);
        if($result){
				$this->newPropUUID =  $result[0]["itemUUID"];
				return $result[0]["itemUUID"]; // the new property UUID
		  }
		  else{
				return false;
		  }
	 }
	 
	 
	 function addLinkURItoProperty($propertyUUID, $linkURI, $sourceID = "alt-URI"){
		  
		  $errors = array();
		  $db = $this->startDB();
		  
		  if(strlen($linkURI)>2){
				$hash = md5($propertyUUID."_".$linkURI);
				
				$where = array();
				$where[] = "itemUUID = '$propertyUUID' ";
				$db->delete("linked_data", $where);
				
				$data = array("hashID" => $hash,
						  "fk_project_uuid" => $this->projectUUID ,
						  "source_id" => $sourceID,
						  "itemUUID" => $propertyUUID,
						  "itemType" => "property",
						  "linkedType" => "type",
						  "linkedURI" => $linkURI
						  );
		  
				$db->insert("linked_data", $data);
				return true;
		  }
		  else{
				$errors[] = "Empty URI for linking!";
				$this->noteErrors($errors);
				return false;
		  }
	 }
	 
	 
	 function duplicateOldProp($oldPropUUID){
		  
		  $errors = array();
		  $db = $this->startDB();
		  
		  $sql = "SELECT *
		  FROM properties
		  WHERE property_uuid = '$oldPropUUID'
		  AND project_id = '".$this->projectUUID."'
		  LIMIT 1;
		  ";
		  
		  $result = $db->fetchAll($sql, 2);
        if($result){
				$data = $result[0];
				$newPropUUID = GenericFunctions::generateUUID();
				$newHash = "nl-".$data["prop_hash"];
				$data["prop_hash"] = "nl-".substr(md5($this->newLinkURI),0,5)."-".$data["prop_hash"];
				$data["property_uuid"] = $newPropUUID;
				$data["source_id"] = "Alt-URI";
				
				$newNote = "<p>This property was generated from <a href='http://opencontext.org/properties/".$oldPropUUID."'>$oldPropUUID</a>. ";
				$newNote .= "Editorial review determined that, in some cases, this property linked to a different concept (<a href='".$this->newLinkURI."'>".$this->newLinkURI."</a>) in a controlled vocabulary. </p>";
				if(strlen($data["note"])>0){
					 $data["note"] .= $newNote;
				}
				else{
					 $data["note"] = $newNote;
				}
				
				
				try{
					$db->insert('properties', $data);
					$this->newPropUUID = $newPropUUID;
					return $newPropUUID;
				}
				catch (Exception $e) {
					 $e = (string)$e;
					 $errors[] = $e;
					 $this->noteErrors($errors);
					 return false;
				}
        }
		  else{
				$errors[] = "Property $oldPropUUID not found!";
				$this->noteErrors($errors);
				return false;
		  }
		  
	 }
	 
	 
	 function getPropertyUUIDfromObsVarUUID($setOldPropertyUUID = false){
		  
		  $db = $this->startDB();
		  
		  $sql = "SELECT observe.property_uuid
		  FROM observe 
		  JOIN properties ON observe.property_uuid = properties.property_uuid
		  WHERE
		  observe.subject_uuid = '".$this->subjectUUID."'
		  AND properties.variable_uuid = '".$this->actVarUUID."'
		  ";
		  
		  $result = $db->fetchAll($sql, 2);
        if($result){
				if($setOldPropertyUUID){
					 $this->oldPropUUID = $result[0]["property_uuid"];
				}
				return $result[0]["property_uuid"];
		  }
		  else{
				return false;
		  }
	 }
	 
	 function oldPropertyLinkURIUpToDate(){
		  
		  $db = $this->startDB();
		  
		  $sql = "SELECT linkedURI
		  FROM linked_data
		  WHERE itemUUID = '".$this->oldPropUUID."'
		  ";
		  $newLinkURIFound = false;
		  $result = $db->fetchAll($sql, 2);
        if($result){
            foreach($result as $row){
					 $oldLinkURI = $row["linkedURI"];
					 if($oldLinkURI == $this->newLinkURI){
						  $newLinkURIFound  = true;
					 }
				}
        }
		  
		  $this->oldPropertyUpToDate = $newLinkURIFound;
		  return $newLinkURIFound ;
	 }
	 
	 
	 
	 function getSpaceUUIDfromLabel($itemLabel){
		  
		  $errors = array();
		  $db = $this->startDB();
		  
		  if($this->classUUID){
				$classLimit = " AND class_uuid = '".$this->classUUID."' ";
		  }
		  
		  $sql = "SELECT uuid
		  FROM space
		  WHERE space_label = '$itemLabel'
		  AND project_id = '".$this->projectUUID."'
		  $classLimit
		  LIMIT 1;
		  ";
		  
		  $result = $db->fetchAll($sql, 2);
        if($result){
            return $result[0]["uuid"];
        }
		  else{
				$errors = array();
				$errors[] = "Subject UUID not found! $sql";
				$this->noteErrors($errors);
				return false;
		  }
	 }
	 
	 
	 
	 function getVarUUIDfromPredicateURI(){
		  
		  $db = $this->startDB();
		  $sql = "SELECT itemUUID
		  FROM linked_data
		  WHERE fk_project_uuid	= '".$this->projectUUID."'
		  AND linkedURI = '".$this->predicateURI."'
		  ";
		  
		  $result = $db->fetchAll($sql, 2);
        if($result){
				$this->actVarUUID = $result[0]["itemUUID"];
            return $result[0]["itemUUID"];
        }
		  else{
				return false;
		  }
	 }
	 
    
	 
	 function noteErrors($errors){
		  if(is_array($errors)){
				if(count($errors)>0){
					 if(!is_array($this->errors)){
						  $this->errors = $errors;
					 }
					 else{
						  $allErrors = $this->errors;
						  foreach($errors as $newError){
								$allErrors[] = $newError;
						  }
						  $this->errors = $allErrors;
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
