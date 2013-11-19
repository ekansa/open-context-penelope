<?php
/*
This is for doing some random edits to space items

*/
class dataEdit_LinkedData  {
    
	 public $requestParams;
	 public $projectUUID;
    public $db;
	 public $errors;
	 
	 
	 public $UItoInternalTypes = array("subject" => "Locations or Objects",
												  "media" => "Media (various)",
												  "document" => "Diary / Narrative",
												  "project" => "Project",
												  "person" => "Person"
												  );
	 
	 
	 
	 
	 //adding or updating linked data
	 function addUpdateLinkedData(){
		  
		  $db = $this->startDB();
		  $requestParams = $this->requestParams;
		  $errors = array();
		  
		  
		  $actValue = $this->checkExistsNonBlank("projectUUID", $requestParams);
		  if($actValue != false){
				$projectUUID = $actValue;
		  }
		  else{
				$errors[] = "Need a projectUUID";
		  }

		  $subjectUUID = false;
		  $actValue = $this->checkExistsNonBlank("subjectUUID", $requestParams);
		  if($actValue != false){
				$subjectUUID = $actValue;
		  }
		  else{
				$errors[] = "Need a subjectUUID";
		  }
	 
		  $subjectType = false;	  
		  $actValue = $this->checkExistsNonBlank("subjectType", $requestParams);
		  if($actValue != false){
				$subjectType = $actValue;
		  }
		  else{
				if($subjectUUID != false){
					 $itemObj = new dataEdit_Items;
					 $subjectType = $itemObj->itemTypeCheck($subjectUUID);
				}
		  }
		  
		  if(!$subjectType){
				$errors[] = "Need a subjectType";
		  }
		  
		  
		  $sourceID = "manual";
		  $actValue = $this->checkExistsNonBlank("sourceID", $requestParams);
		  if($actValue != false){
				$sourceID = $actValue;
		  }
		  
		  
		  $actValue = $this->checkExistsNonBlank("predicateURI", $requestParams);
		  if($actValue != false){
				$predicateURI = $actValue;
		  }
		  else{
				$errors[] = "Need a predicateURI";
		  }
		  
		  $objectURI = false;
		  $actValue = $this->checkExistsNonBlank("objectURI", $requestParams);
		  if($actValue != false){
				$objectURI = $actValue;
		  }
		  else{
				$errors[] = "Need a objectURI";
		  }
		  
		  $objectLabel = "";
		  $actValue = $this->checkExistsNonBlank("objectLabel", $requestParams);
		  if($actValue != false){
				$objectLabel = $actValue;
		  }
		  
		  
		  $replacePredicate = true;
		  $actValue = $this->checkExistsNonBlank("replacePredicate", $requestParams);
		  if($actValue != false){
				if( $actValue == "1"){
					 $replacePredicate = true;
				}
				else{
					 $replacePredicate = false;
				}
		  }
		  
		  if($replacePredicate){
				$this->deleteLinkedData();
		  }
		  
		  $addType = false;
		  if(count($errors)<1){
				
				if($predicateURI != "doi" && $predicateURI != "ark"){
					 $addType = "linked_data";
					 $hashID = md5($subjectUUID."_".$predicateURI."_".$objectURI);
					 
					 $data = array("fk_project_uuid" => $projectUUID,
										"hashID" => $hashID,
										"source_id" => $sourceID,
										"itemUUID" => $subjectUUID,
										"itemType" => $subjectType,
										"linkedType" => $predicateURI,
										"linkedLabel" => $objectLabel,
										"linkedURI" => $objectURI
										);
					 try{
						  $db->insert("linked_data", $data);
						  $pubObj = new dataEdit_Published;
						  $pubObj->deleteFromPublishedDocsByUUID($subjectUUID); //since linked data relations are changed, take it off the done publishing list	
					 }catch (Exception $e) {
						  $errors[] = (string)$e;
					 }
				}
				else{
					 $addType = "itemids";
					 $data = array("uuid" => $subjectUUID,
										"project_id" => $projectUUID,
										"itemType" => $subjectType,
										"stableID" => $objectURI,
										"stableType" => $predicateURI
										);
					 
					 $db->insert("itemids", $data);
					 $pubObj = new dataEdit_Published;
					 $pubObj->deleteFromPublishedDocsByUUID($subjectUUID); //since linked data relations are changed, take it off the done publishing list	
				}
		  }
		  
		  return array("type" => $addType, "errors" => $errors);
	 }
	 
	 
	 
	 
	 
	 
	 //delete linked data information
	 function deleteLinkedData(){
		  $db = $this->startDB();
		  $requestParams = $this->requestParams;
		  $errors = array();

		  $actValue = $this->checkExistsNonBlank("subjectUUID", $requestParams);
		  if($actValue != false){
				$subjectUUID = $actValue;
		  }
		   else{
				$errors[] = "Need a subjectUUID";
		  }
		  
		  $actValue = $this->checkExistsNonBlank("predicateURI", $requestParams);
		  if($actValue != false){
				$predicateURI = $actValue;
		  }
		  else{
				$errors[] = "Need a predicateURI";
		  }
		  
		  $objectURI = false;
		  $actValue = $this->checkExistsNonBlank("objectURI", $requestParams);
		  if($actValue != false){
				$objectURI = $actValue;
		  }
		  
		  $deleteType = false;
		  if(count($errors)<1){
				$errors = false;
				
				if($predicateURI != "doi" && $predicateURI != "ark"){
					 $where = array();
					 $where[] = "itemUUID = '$subjectUUID' ";
					 $where[] = "linkedType = '$predicateURI' ";
					 if($objectURI != false){
						  $where[] = "linkedURI = '$objectURI' ";
					 }
					 $db->delete("linked_data", $where);
					 $deleteType = "linked_data";
				}
				else{
					 $where = array();
					 $where[] = "uuid = '$subjectUUID' ";
					 $where[] = "stableType = '$predicateURI' ";
					 if($objectURI != false){
						  $where[] = "stableID = '$objectURI' ";
					 }
					 $db->delete("itemids", $where);
					 $deleteType = "itemids";
				}
				$pubObj = new dataEdit_Published;
				$pubObj->deleteFromPublishedDocsByUUID($subjectUUID); //since linked data relations are changed, take it off the done publishing list				
		  }
		  
		  $this->errors = $errors;
		  return array("type" => $deleteType, "errors" => $errors);
	 }
	 
	 
	 
	 function checkExistsNonBlank($key, $requestParams){
		  $value = false;
		  if(isset($requestParams[$key])){
				$value = trim($requestParams[$key]);
				if(strlen($value)<1){
					 $value = false;
				}
		  }
		  return $value;
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
