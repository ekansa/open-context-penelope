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
	 const tagDelim = ";"; //delimiter for tags
	 
	 
	 
	 
	 // this function is the main function for updating metadata records for a table
	 function addUpdateMetadata(){
	 
		  $chValue = $this->checkParam("note"); 
		  if($chValue != false ){
				$this->tableName = $chValue;
		  }
		  
		  $chValue = $this->checkParam("description"); 
		  if($chValue != false ){
				$this->tableDesciption = $chValue;
		  }
		  
		  $chValue = $this->checkParam("tags"); 
		  if($chValue != false ){
				if(stristr($chValue, self::tagDelim)){
					 $rawTags = explode(self::tagDelim, $chValue);
				}
				else{
					 $rawTags = array($chValue);
				}
				$tags = array();
				foreach($rawTags as $tag){
					 $tags[] = trim($tag);
				}
				$this->tableTags = $tags;
		  }
		  
	 }
	 
	 
	 //this function makes some metadata automatically, based on the table's associations
	 function autoMetadata(){
		  
	 }
	 
	 
	 //get dublin-core creator information
	 function getPersons(){
		  
		  $linksObj = new dbXML_dbLinks;
		  $creatorRels = $linksObj->relToCreator;
		  $contribRels = $linksObj->relToContributor;
		  
		  $db = $this->startDB();
		  
		  $sql = "SELECT links.targ_uuid, links.link_type,
		  persons.combined_name, persons.last_name, persons.first_name, persons.mid_init
		  FROM links 
		  JOIN persons ON persons.uuid = links.targ_uuid
		  JOIN ".$this->penelopeTabID." AS actTab ON actTab.uuid = links.origin_uuid
		  WHERE links.targ_type LIKE '%person%'
			
		  UNION
			
		  SELECT links.targ_uuid, links.link_type, 
		  users.combined_name, users.last_name, users.first_name, users.mid_init
		  FROM links 
		  JOIN users ON users.uuid = links.targ_uuid
		  JOIN ".$this->penelopeTabID." AS actTab ON actTab.uuid = links.origin_uuid
		  WHERE links.targ_type LIKE '%person%'
			
		  ";
		  
		  $result = $db->fetchAll($sql);
		  if($result){
				foreach($result as $row){
					 
					 
					 
					 
				}
		  }
		  
	 }
	 
	 
	 
	 //post parameter checking
	 function checkParam($key, $requestParams = false){
		  
		  if(!$requestParams){
				$requestParams = $this->requestParams;
		  }
	 
		  $output = false;
		  
		  if(array_key_exists($key, $requestParams)){
				if(strlen($requestParams[$key]) > 0){
					 $output = $requestParams[$key];
				}
		  }
		  return $output;
	 }
	 
	 
	 
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
