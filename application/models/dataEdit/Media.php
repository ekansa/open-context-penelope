<?php

//fix space entities where the source datatable did not have unique labeling
//and we should not have repeating variables
class dataEdit_Media  {
    
    public $db;
	 public $itemUUID;
	 public $requestParams; //parameters sent in a request 
	 public $errors;
	 
	  //checks on media files
	 function createMediaItem(){
		  
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
		  
		  $fullExName = false;
		  $actValue = $this->checkExistsNonBlank("fullfile", $requestParams);
		  if($actValue != false){
				$data["ia_fullfile"] = $actValue;
				$fullEx = explode("/",$actValue);
				$fullExName = $fullEx[count($fullEx) - 1];
		  }
		  else{
				$errors[] = "Need a fullfile";
		  }
		  
		  $actValue = $this->checkExistsNonBlank("preview", $requestParams);
		  if($actValue != false){
				$data["ia_preview"] = $actValue;
		  }
		  else{
				$errors[] = "Need a preview";
		  }
		  
		  $actValue = $this->checkExistsNonBlank("thumb", $requestParams);
		  if($actValue != false){
				$data["ia_thumb"] =  $actValue;
		  }
		  else{
				$errors[] = "Need a thumb";
		  }
		  
		  $actValue = $this->checkExistsNonBlank("label", $requestParams);
		  if($actValue != false){
				$data["res_label"] = $actValue;
		  }
		  else{
				$data["res_label"] = $fullExName;
		  }
		  
		  $actValue = $this->checkExistsNonBlank("filename", $requestParams);
		  if($actValue != false){
				$data["res_filename"] = $actValue;
		  }
		  else{
				$data["res_filename"] = $fullExName;
		  }
		  
		  if(count($errors)<1){
				
				$sql = "SELECT * FROM resource WHERE ia_fullfile = '".$data["ia_fullfile"]."' LIMIT 1;";
				$result = $db->fetchAll($sql, 2);
				if(!$result){
					 $db->insert("resource", $data); //fullfile not already in. URI is unique
					 $this->errors = false;
				}
				else{
					 $errors[] = "Fullfile already associated with a media resource: ".$result[0]["uuid"];
					 $this->errors = $errors;
					 $uuid = false;
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
				$value = $requestParams[$key];
				if(strlen($value)<1){
					 $value = false;
				}
		  }
		  return $value;
	 }
	 
	 
	 //checks on media files
	 function checkMediaFiles(){
		  
		  $requestParams = $this->requestParams;
		  $fileArray = array();
		  if(isset($requestParams["fullfile"])){
				$fileArray["fullfile"] = $requestParams["fullfile"];
		  }
		  if(isset($requestParams["preview"])){
				$fileArray["preview"] = $requestParams["preview"];
		  }
		  if(isset($requestParams["thumb"])){
				$fileArray["thumb"] = $requestParams["thumb"];
		  }
		  if(isset($requestParams["itemUUID"])){
				$uuid = $requestParams["itemUUID"];
				$db = $this->startDB();
				
				$sql = "SELECT ia_fullfile as fullfile, ia_preview as preview, ia_thumb as thumb
				FROM resource
				WHERE uuid = '$uuid ' LIMIT 1; ";
				
				$result = $db->fetchAll($sql, 2);
				if($result){
					 foreach($result[0] as $key => $value){
						  if(strlen($value)> 0){
								$fileArray[$key] = $value;
						  }
					 }
				}
		  }
		  
		  $output = array();
		  $dbMediaObj = new dbXML_dbMedia;
		  foreach($fileArray as $key => $uri){
				$actOut = array("filetype" => $key,
									 "uri" => $uri);
				$actOut["bytes"] = $dbMediaObj->remote_filesize($uri);
				$actOut["human"]  = $this->human_filesize($actOut["bytes"]);
				$output[] = $actOut;
				unset($actOut);
		  }
		  
		  return $output;
	 }
	 
	 
	 
	 //convert bytes into something easy to read
	 function human_filesize($bytes, $decimals = 2) {
		  $sz = 'BKMGTP';
		  $factor = floor((strlen($bytes) - 1) / 3);
		  return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
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
