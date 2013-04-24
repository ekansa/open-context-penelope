<?php
/* This class makes a table object for Open Context
 * based on a table created by the TabOut_Table class
 * it generates appropriate metadata as well as JSON data for Open Context's table
 */

class TabOut_TablePublish  {
    
	 public $db; //database connection object
	 
	 public $penelopeTabID; //name of the table in Penelope
	 public $requestParams; //parameters sent in a post request (for updating table metadata)
	 
	 public $setURI; //URI that can be used to duplicate the table. false if it can't be duplicated with a query
	 public $numFound; //number of records in the total set
	 
	 public $linkedPersons; //array and count of associated records of individuals in the table
	 public $creators; //array and count of associated records of dublin core creators
	 public $contributors; //array and count of associated records of dublin core contributors
	 public $linkedPersonURIs; //array and count of associated records of individuals in the table
	 public $creatorURIs; //array and count of associated records of dublin core creators
	 public $contributorURIs; //array and count of associated records of dublin core contributors
	 
	 public $cache_id; //id for the table
	 public $setLastPublished; //publication time for the set
	 public $setLastUpdated; //last update time for the set
	 
	 public $tableName;
	 public $tableDesciption;
	 public $tableTags;
	 public $numSegments; //number of segments a large table is divided into
	 public $recsPerTable; //number of records per table
	 
	 public $zippedFile; //filename for a zip compression CSV version.
	 
	 public $tableFieldsTemp; //array of table fields, temporary for internal use.
	 public $tableFields; //array of table fields
	 public $sampleRecords; //array of sample records (not the full set)
	 public $records; //array of all data records
	 
	 const defaultSample = 50;
	 
	 
	 //get the table field names
	 function getTableFields(){
		  
		  $db = $this->startDB();
		  $sql = "SELECT field_name, field_label
		  FROM export_tabs_fields
		  WHERE source_id = '".$this->penelopeTabID."' ORDER BY field_num ; ";
		  
		  $result = $db->fetchAll($sql);
		  if($result){
				$tableFields = array();
				$tableFieldsTemp = array();
				foreach($result as $row){
					 $tableFields[] = $row["field_label"];
					 $tableFieldsTemp[$row["field_name"]] = $row["field_label"];
				}
				$this->tableFieldsTemp = $tableFieldsTemp;
				$this->tableFields = $tableFields;
		  }
		  else{
				return false;
		  }
		  
	 }
	 
	 
	 
	 function getSampleRecords(){
		  
		  $db = $this->startDB();
		  
		  $sql = "SELECT *
		  FROM ".$this->penelopeTabID."
		  LIMIT 1,".self::defaultSample." ;
		  ";
		  
		  $result = $db->fetchAll($sql);
		  if($result){
				$this->sampleRecords = $result;
		  }
		  else{
				return false;
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
