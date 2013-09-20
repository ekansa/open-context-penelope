<?php

//fix space entities where the source datatable did not have unique labeling
//and we should not have repeating variables
class dataEdit_Document  {
    
    public $db;
	 public $itemUUID;
	 public $requestParams; //parameters sent in a request 
	 public $errors;
	 
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
				$data["diary_label"] = $actValue;
		  }
		  
		  $actValue = $this->checkExistsNonBlank("content", $requestParams);
		  if($actValue != false){
				$data["diary_text_original"] = $actValue;
				$data["diary_hash"] = sha1($data["diary_label"].$data["diary_text_original"]);
		  }
		  
		  if(count($errors)<1){
				try{
					 $db->insert("diary", $data); //fullfile not already in. URI is unique
					 $this->errors = false;
				}
				catch (Exception $e) {
					 $uuid = false;
					 $errors[] =  $e->getMessage();
				}
				
				if(!$this->errors){
					 if($this->checkExistsNonBlank("linkedUUID", $requestParams)){
						  //add a linking relationship to the newly created item
						  $linkObj = new dataEdit_Link;
						  $linkObj->requestParams = $requestParams;
						  $output = $linkObj->createItemLinkingRel($uuid, "Diary / Narrative");
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
	 
	 
	 //checks to see if the XHTML is valid
	 function XHTMLvalid($xhtml){
		  
		  @$xml = simplexml_load_string($xhtml);
		  if($xml){
				return true;
		  }
		  else{
				return false;
		  }
		  
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
