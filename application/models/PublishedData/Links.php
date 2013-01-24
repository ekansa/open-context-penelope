<?php

class PublishedData_Links {
    
	 public $originUUID;
    public $projectUUID;
    public $sourceID;
	 public $db;
	 
	 public $errors;
	 
	 function getAddLinks($itemXML, $originType){
		  $errors = array();
		  $projectUUID = $this->projectUUID;
		  $sourceID = $this->sourceID;
		  $originUUID = $this->originUUID;
		  
		  $linksData = array();
		  foreach ($itemXML->xpath("//arch:links/arch:docID") as $links_result){
				$actLink = array();
				$actLink["project_id"] = $projectUUID;
				$actLink["source_id"] = $sourceID;
				$actLink["link_uuid"] = GenericFunctions::generateUUID();
				$actLink["origin_type"] = $originType;
				$actLink["origin_uuid"] = $originUUID;
				$actLink["origin_obs"] = 1;
				$actLink["targ_uuid"] = $links_result."";
				foreach ($links_result->xpath("@type") as $sub_result){
					$actLink["targ_type"] = $sub_result."";
				}
				foreach ($links_result->xpath("@info") as $sub_result){
					$actLink["link_type"] = $sub_result."";
				}
				$actLink["targ_obs"] = 1;
				$actLink["hash_link"]= md5($originUUID . '_' . 1 . '_' . $actLink["targ_uuid"]  . '_' . $actLink["link_type"]);
				$linksData[] = $actLink;
				unset($actLink);
		  }
		  
		  //get rid of old links
		  $db = $this->startDB();
		  $where = array();
		  $where[] = "project_id = '$projectUUID' ";
		  $where[] = "origin_uuid = '$originUUID' ";
		  $db->delete('links', $where);
		  
		  $okInserts = 0;
		  foreach($linksData as $actData){
				try{
					$db->insert('links', $actData);
					$okInserts++;
				}
				catch (Exception $e) {
					 $e = (string)$e;
					 if(!stristr($e, "SQLSTATE[23000]")){
						  $errors[] = $e;
					 }	
				}
		  }//end loop
		  $this->noteErrors($errors);
		  return $linksData;
	 }
	 
	 
	 
	
	 
	 
	 
	 
	 //save data
	 function saveData($saveArray){
		  $okInserts = 0;
		  $errors = array();
		  if(is_array($saveArray)){
				$db = $this->startDB();
				foreach($saveArray as $tableKey => $dataArray){
					 foreach($dataArray as $data){
						  try{
								$db->insert($tableKey, $data);
								$okInserts++;
						  }
						  catch (Exception $e) {
								$errors[] = (string)$e;	
						  }
					 }
				}
		  }
		  $this->noteErrors($errors);
		  return $okInserts;
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
