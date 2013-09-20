<?php

//fix space entities where the source datatable did not have unique labeling
//and we should not have repeating variables
class dataEdit_Subject  {
    
    public $db;
	 public $itemUUID;
	 public $requestParams; //parameters sent in a request 
	 public $errors;
	 
	 const contextDeliminator = "|xx|";
	 
	 function createItem(){
		  
		  $db = $this->startDB();
		  $requestParams = $this->requestParams;
		  
		  $data = array();
		  $errors = array();
		  $uuid = false;
		  $actValue = $this->checkExistsNonBlank("newUUID", $requestParams);
		  if($actValue != false){
				$data["uuid"] = $actValue;
				$uuid = $actValue;
		  }
		  else{
				$errors[] = "Need an itemUUID";
		  }
		  
		  $actValue = $this->checkExistsNonBlank("projUUID", $requestParams);
		  if($actValue != false){
				if(stristr($actValue, "oc")){
					 $actValue = 0;
				}
				$data["project_id"] = $actValue;
		  }
		  else{
				$errors[] = "Need an projUUID";
		  }
		  
		  $actValue = $this->checkExistsNonBlank("sourceID", $requestParams);
		  if($actValue != false){
				$data["source_id"] =$actValue;
		  }
		  else{
				$data["source_id"] = "manual";
		  }
		  
		  
		  $actValue = $this->checkExistsNonBlank("label", $requestParams);
		  if($actValue != false){
				$data["space_label"] = $actValue;
		  }
		  else{
				$errors[] = "Need a label";
		  }
		  
		  $actValue = $this->checkExistsNonBlank("classUUID", $requestParams);
		  if($actValue != false){
				$data["class_uuid"] = $actValue;
		  }
		  else{
				$errors[] = "Need a classUUID";
		  }
		  
		  $parentUUID = false;
		  $actValue = $this->checkExistsNonBlank("parentUUID", $requestParams);
		  if($actValue != false && !stristr($actValue,"none")){
				if($this->getItemNameByUUID($actValue) != false){
					 //yes, the uuid does actually have an subject / space item associated with it
					 $parentUUID = $actValue;
					 $data["full_context"] = $this->determineContextFromContainRelations($parentUUID);
				
					 if(strlen($data["full_context"])>0){
						  $data["full_context"] .= self::contextDeliminator.$data["space_label"];
					 }
					 else{
						  $data["full_context"] = $data["space_label"];
					 }
				}
		  }
		  
		  
		  
		  if(!$parentUUID){
				$data["full_context"] = $data["space_label"];
		  }
		  
		  if(count($errors)<1){
				$data["hash_fcntxt"] = md5($data["project_id"] . "_" . $data["full_context"]);
				
				try{
					 $db->insert("space", $data); //fullfile not already in. URI is unique
					 $this->errors = false;
				}
				catch (Exception $e) {
					 $uuid = false;
					 $errors[] =  $e->getMessage();
					 $this->errors = $errors;
				}
				
				if(!$this->errors && $parentUUID != false){
					 $containObj = new dataEdit_SpaceContain;
					 $containObj->addContainRelation($parentUUID, $data["uuid"], $data["project_id"], $data["source_id"]);
					 $pubObj = new dataEdit_Published;
					 $pubObj->deleteFromPublishedDocsByUUID($parentUUID); //since the parent is changed, take it off the done publishing list
				}
				
				if(!$this->errors){
					 if($this->checkExistsNonBlank("linkedUUID", $requestParams)){
						  //add a linking relationship to the newly created item
						  $linkObj = new dataEdit_Link;
						  $linkObj->requestParams = $requestParams;
						  $output = $linkObj->createItemLinkingRel($uuid, "Locations or Objects");
					 }
				}
		  }
		  else{
				$this->errors = $errors;
				$uuid = false;
		  }

		  return array("data"=>$data, "errors" => $errors);
		  //return $uuid;
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
	 
	 
	 //get the item label from the UUID
	 function getItemNameByUUID($uuid){
		  $db = $this->startDB();
		  $output = false;
		  $sql = "SELECT space_label as label FROM space WHERE uuid = '$uuid' LIMIT 1;";
		  $result = $db->fetchAll($sql, 2);
        if($result){
				$output = $result[0]["label"];
		  }
		  return $output;
	 }
	 
	 //get the full-context path for an item based on containment relations
	 function determineContextFromContainRelations($uuid){
		  $output = false;
		  $containObj = new dataEdit_SpaceContain;
		  $parentParents = $containObj->getParentItemsByUUID($uuid, true); //recursively get an array of the parents of a parent item
		  $parentParents[] = $uuid;
		  foreach($parentParents as $pUUID){
				$pName = $this->getItemNameByUUID($pUUID);
				if(!$output){
					 $output = $pName;
				}
				else{
					 $output .= self::contextDeliminator.$pName;
				}
		  }
		  
		  return $output;
	 }
	 
	 
	 
	 
	 
	//startup the database
	 function startDB(){
		  if(!$this->db){
				$db = Zend_Registry::get('db');
				$this->setUTFconnection($db);
				$this->db = $db;
				return $db;
		  }
		  else{
				return $this->db;
		  }
	 }
	 
	 
	 //preps for utf8
	 function setUTFconnection($db){
		  $sql = "SET collation_connection = utf8_unicode_ci;";
		  $db->query($sql, 2);
		  $sql = "SET NAMES utf8;";
		  $db->query($sql, 2);
	 }
    
}  
