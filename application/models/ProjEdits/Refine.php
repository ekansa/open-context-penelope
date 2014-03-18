<?php
/*
This class calls the Open Refine API

I'm including it, since it may be useful to adapt to other projects

*/
class ProjEdits_Refine  {
    
	 public $db;
	 public $projectUUID;
	 public $refineProjectID;
	 public $localTableID; 
	 
	 
	 function loadRefineData($clearTableFirst = true){
		  
		  $output = array();
		  $output["count"] = 0;
		  $db = $this->startDB();
		  
		  if($clearTableFirst){
				$sql = "TRUNCATE TABLE ".$this->localTableID;
				$db->query($sql, 2);
		  }
		  
		  $start = 0;
		  $limit = 500;
		  $modelURL = "http://127.0.0.1:3333/command/core/get-models?project=".$this->refineProjectID;
		  $jsonBase = "http://127.0.0.1:3333/command/core/get-rows?project=".$this->refineProjectID;
		  $done = false;
		  
		  $modelString = file_get_contents($modelURL);
		  $model = Zend_Json::decode($modelString);
		  $fieldIndexCellIndex = array();
		  $fieldIndex = 1;
		  foreach($model["columnModel"]["columns"] as $col){
			   $fieldIndexCellIndex[$fieldIndex] = $col["cellIndex"];
			   $fieldIndex++;
		  }
		  
		  $recordCount = 0;
		  while(!$done){
			   $url = $jsonBase."&start=".$start."&limit=".$limit;
			   
			   $jsonString = file_get_contents($url);
			   $json = Zend_Json::decode($jsonString);
				$filtered = $json["filtered"];
				
			   if(isset($json["rows"])){
				    unset($jsonString);
				    $start = $start + $limit;
				    $output["count"]++;
				    foreach($json["rows"] as $row){
						$data = array();
						$cells = $row["cells"];
						
						foreach($fieldIndexCellIndex as $fieldIndex => $cellIndex){
							 $fieldName = "field_".$fieldIndex;
							 
							 $cell = $cells[$cellIndex];
							 $data[$fieldName] = "";
							 if(isset($cells[$cellIndex])){
								if(is_array($cell)){
									 $data[$fieldName] = $cell["v"];
								}
							 }
						}
						$data["id"] = $row["i"]+1;
						
						try{
							 $db->insert($this->localTableID, $data);
						}catch (Exception $e) {
							 //$done = true;
						}
						
						if($row["i"] >= $json["filtered"] -1){
						  $done = true;
						  break;
						}
						
						$recordCount++;
						if($recordCount>= $json["filtered"]){
						  $done = true;
						  break;
						}
				    } 
			   }
			   else{
				    $done = true;
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
