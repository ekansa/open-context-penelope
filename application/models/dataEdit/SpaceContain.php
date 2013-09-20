<?php

//manipulate items based on spatial containment relationships

class dataEdit_SpaceContain  {
    
    public $db;
	 public $itemUUID;
	 public $itemChildrenUUIDs = array();
	 public $itemParentUUIDs = array();
	 
	 function getChildItemsByUUID($parentUUID, $recursive = true){
		  
		  $db = $this->startDB();
		  
		  $sql = "SELECT child_uuid FROM space_contain WHERE parent_uuid = '$parentUUID' ; ";
		  $result =  $db->fetchAll($sql);
		  if($result){
				$itemChildrenUUIDs = $this->itemChildrenUUIDs;
				foreach($result as $row){
					 $childUUID = $row["child_uuid"];
					 if(!in_array($childUUID, $itemChildrenUUIDs)){
						  $itemChildrenUUIDs[] = $childUUID;
						  $this->itemChildrenUUIDs = $itemChildrenUUIDs;
						  if($recursive){
								$this->getChildItemsByUUID($childUUID, $recursive);	
						  }
					 }
				}
		  }
		  
		  return $this->itemChildrenUUIDs;
	 }
	 
	 
	 function getParentItemsByUUID($childUUID, $recursive = false){
		  
		  $db = $this->startDB();
		  
		  $sql = "SELECT parent_uuid FROM space_contain WHERE child_uuid = '$childUUID' ; ";
		  $result =  $db->fetchAll($sql);
		  if($result){
				$itemParentUUIDs = $this->itemParentUUIDs;
				foreach($result as $row){
					 $parentUUID = $row["parent_uuid"];
					 if($recursive){
						  $itemParentUUIDs = $this->getParentItemsByUUID($parentUUID, $recursive);	
					 }
					 if(!in_array($parentUUID, $itemParentUUIDs)){
						  $itemParentUUIDs[] = $parentUUID;
						  $this->itemParentUUIDs = $itemParentUUIDs;
					 }
				}
		  }
		  
		  return $this->itemParentUUIDs;
	 }
	 
	 
	 //add a new containment relation
	 function addContainRelation($parentUUID, $childUUID, $projectUUID, $sourceID = "manual"){
		  $output = false;
		  $db = $this->startDB();
		  
		  $data = array("hash_all" =>  md5($parentUUID . '_' . $childUUID),
							 "project_id" => $projectUUID,
							 "source_id" => $sourceID,
							 "parent_uuid" => $parentUUID,
							 "child_uuid" => $childUUID
							 );
		  
		  try{
				$db->insert("space_contain",$data);
				$output = true;
		  }
		  catch (Exception $e) {
				 $output = false;
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
