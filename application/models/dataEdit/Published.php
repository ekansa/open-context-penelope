<?php

//A handy  class for removing items that have been updated from the published list
//that way, it is easier to note an edit
class dataEdit_Published  {
    
    public $db;
	 public $itemUUID;
	 
	 
	 //delete an item and all of its children from the published docs list,
	 //this is important for updates on records that have spatial containment dependencies
	 function deleteFromPublishedDocsByParentUUID($parentUUID){
		  
		  $this->deleteFromPublishedDocsByUUID($parentUUID);
		  $spContainObj = new dataEdit_SpaceContain;
		  $children = $spContainObj->getChildItemsByUUID($parentUUID);
		  if(is_array($children)){
				foreach($children as $childUUID){
					 $this->deleteFromPublishedDocsByUUID($childUUID);
				}
		  }
		  
	 }
	 
	 
	 //this deletes the parent of a given item from the published doc list
	 function deleteFromPublishedDocsByChildUUID($itemUUID){
		  
		  $this->deleteFromPublishedDocsByUUID($itemUUID);
		  $spContainObj = new dataEdit_SpaceContain;
		  $parents = $spContainObj->getParentItemsByUUID($itemUUID);
		  if(is_array($parents)){
				foreach($parents as $parentUUID){
					 $this->deleteFromPublishedDocsByUUID($parentUUID);
				}
		  }
		  
	 }
	 
	 
	 //this deletes items from the published list based on their association with a given property
	 function deleteFromPublishedDocsByObservationProperty($propertyUUID){
		  
		  $db = $this->startDB();
		  
		  $sql = "SELECT DISTINCT subject_uuid
		  FROM observe
		  WHERE observe.property_uuid = '$propertyUUID'
		  ";
		  
		  $result = $db->fetchAll($sql, 2);
		  foreach($result as $row){
				$itemUUID = $row["subject_uuid"];
				$this->deleteFromPublishedDocsByUUID($itemUUID);
		  }
		  
		  return count($result); //returns the count of items marked for removal from the published list
	 }
	 
	 
	 //delete a single item from the published docs list, this makes it easier to know what needs to be
	 //updated in Open Context
	 function deleteFromPublishedDocsByUUID($itemUUID){
		  $db = $this->startDB();
		  $where = "item_uuid = '$itemUUID' ";
		  $db->delete("published_docs", $where);
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
