<?php

class PublishedData_Hierarchy {
    
	 public $baseSpaceURI;
	 public $db;
	 public $errors;
	 public $doneURIs = array();
	 
	 
	 function addHierarchy($rootUUID){
		 
		  $doneURIs = $this->doneURIs;
		  $errors = array();
		  $db = $this->startDB();
		  $itemURL = $this->baseSpaceURI.$rootUUID.".xml";
		  @$xmlString = file_get_contents($itemURL);
		  if($xmlString != false){
				@$itemXML = simplexml_load_string($xmlString);
				if($itemXML != false){
					 
					 $spaceObj = new PublishedData_Space;
					 $spaceObj->db = $db;
					 $spaceObj->addFullSpace($itemXML);
					 if(is_array($spaceObj->errors)){
						 $errors[] =  $spaceObj->errors;
					 }
					 if(is_array($spaceObj->children)){
						  foreach($spaceObj->children as $childUUID){
								$doneChild = $this->addHierarchy($childUUID);
								if($doneChild != false){
									 if(!in_array($doneChild, $doneURIs)){
										  $doneURIs[] = $doneChild;
									 }
								}
						  }
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
