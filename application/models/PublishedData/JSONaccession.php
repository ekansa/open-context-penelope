<?php

class PublishedData_JSONaccession {
    
	 public $baseSpaceURI;
	 public $baseMediaURI;
	 public $db;
	 public $errors;
	 public $doneURIs = array();
	 public $existingURIs = array();
	 
	 
	 function  JSONlist($listURL){
		  $errors = array();
		  @$jsonString = file_get_contents($listURL);
		  if($jsonString){
				$api =  Zend_Json::decode($jsonString);
				if(is_array($api)){
					 foreach($api["results"] as $record){
						  $uri = $record["uri"];
						  $uriEx = explode("/", $uri);
						  $itemUUID = $uriEx[count($uriEx) -1]; // last part of the URI
						  $this->addItem($itemUUID);
					 }
					 if($api["paging"]["next"] != false){
						  $nextURL = $api["paging"]["next"];
						  $this->JSONlist($nextURL);
					 }
				}
				else{
					 $errors[] = "Error in reading List: ".$listURL;
				}
		  
		  }
		  else{
				$errors[] = "Could not get List: ".$listURL;
		  }
	 }
	 
	 
	 
	 
	 
	 function addItem($itemUUID){
		 
		  $doneURIs = $this->doneURIs;
		  $existingURIs = $this->existingURIs;
		  $errors = array();
		  $itemURL = $this->baseSpaceURI.$itemUUID.".xml";
		  if(!$this->checkItemExits($itemUUID)){
				$db = $this->startDB();
				@$xmlString = file_get_contents($itemURL);
				if($xmlString != false){
					 @$itemXML = simplexml_load_string($xmlString);
					 if($itemXML != false){
						  
						  $spaceObj = new PublishedData_Space;
						  $spaceObj->db = $db;
						  $spaceObj->baseMediaURI = $this->baseMediaURI;
						  $spaceObj->addFullSpace($itemXML);
						  if(is_array($spaceObj->errors)){
							  $errors[] =  $spaceObj->errors;
						  }
						  if(!in_array($itemURL, $doneURIs)){
								$doneURIs[] = $itemURL;
						  }
						  $this->doneURIs = $doneURIs;
						  return $itemURL;
					 }
					 else{
						  $errors[] = "$itemURL has bad XML";
						  return false;
					 }
				}
				else{
					 $errors[] = "$itemURL cannot be found";
					 return false;
				}
		  
				$this->noteErrors($errors);
		  }
		  else{
				$existingURIs[] = $itemURL;
				$this->existingURIs = $existingURIs;
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
	 
	 
	 function checkItemExits($itemUUID){
		  $itemsObj = new dataEdit_Items;
		  
		  if(!$itemsObj->itemTypeCheck($itemUUID)){
				return false;
		  }
		  else{
				return true;
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
