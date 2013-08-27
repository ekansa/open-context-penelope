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
