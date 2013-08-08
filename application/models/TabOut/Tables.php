<?php

class TabOut_Tables  {
	 
	 public $db;
	 public $tables;
	 public $projects;
	 public $classes;
	 public $sourceTables;
	 
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
		  
		  return $this->projects;
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
		  
		  return $this->classes;
	 }
	 
	 
	 function getSourceTablesListCount(){
		  
		  $db = $this->startDB();
		  
		  $sql = "SELECT project_list.project_name, file_summary.source_id,  file_summary.description
		  
		  FROM file_summary
		  JOIN project_list ON file_summary.project_id = project_list.project_id
		  ORDER BY project_list.project_name
		  ";
		  
		  $result = $db->fetchAll($sql);
		  if($result){
				$reResult = array();
				foreach($result as $row){
					 $sourceID = $row["source_id"];
					 $sql = "SELECT count(variable_uuid) as varCount FROM var_tab WHERE source_id = '$sourceID' LIMIT 1; ";
					 $resB = $db->fetchAll($sql);
					 if($resB){
						  $row["varCount"] = $resB[0]["varCount"];
					 }
					 else{
						  $row["varCount"] = 0;
					 }
					 $reResult[] = $row;
				}
				$this->sourceTables = $reResult;
		  }
		  else{
				$this->sourceTables = array();
		  }
		  
		  return $this->sourceTables;
	 }
	 
	 
	 
	 function deleteTable($tableID){
		  
		  $db = $this->startDB();
		  
		  $where = "source_id = '$tableID' ";
		  $whereB = "tableID = '$tableID' ";
		  
		  $db->delete("export_tabs_fields", $where);
		  $db->delete("export_tabs_meta", $where);
		  $db->delete("export_tabs_records", $whereB);
		  
		  $sql = "DROP TABLE $tableID ";
		  $db->query($sql);
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
