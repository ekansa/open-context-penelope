<?php

class TabOut_Tables  {
	 
	 public $db;
	 public $tables;
	 public $projects;
	 public $classes; 
	 
	 //get tables for outputs
	 function getTables(){
		  
		  $db = $this->startDB();
		  
		  $sql = "SELECT exp.source_id, exp.updated,
		  expMeta.title, COUNT(exp.field_name) AS fieldCount
		  FROM export_tabs_fields AS exp
		  LEFT JOIN export_tabs_meta AS expMeta ON expMeta.source_id = exp.source_id
		  GROUP BY exp.source_id
		  ORDER BY exp.updated;
		  ";
		  
		  $result = $db->fetchAll($sql);
		  if($result){
				$this->tables = $result;
		  }
		  else{
				$this->tables = array();
		  }
	 }
	 
	 function getProjectListCount(){
		  
		  $db = $this->startDB();
		  
		  $sql = "SELECT count(space.uuid) as itemCount, space.project_id, project_list.project_name
		  
		  FROM space
		  JOIN project_list ON space.project_id = project_list.project_id
		  GROUP BY space.project_id
		  ORDER BY itemCount DESC
		  ";
		  
		  $result = $db->fetchAll($sql);
		  if($result){
				$this->projects = $result;
		  }
		  else{
				$this->projects = array();
		  }
		  
	 }
	 
	 
	 function getClassListCount(){
		  
		  $db = $this->startDB();
		  
		  $sql = "SELECT count(space.uuid) as itemCount, space.class_uuid, sp_classes.class_label
		  
		  FROM space
		  JOIN sp_classes ON space.class_uuid = sp_classes.class_uuid
		  GROUP BY space.class_uuid
		  ORDER BY itemCount DESC
		  ";
		  
		  $result = $db->fetchAll($sql);
		  if($result){
				$this->classes = $result;
		  }
		  else{
				$this->classes = array();
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
