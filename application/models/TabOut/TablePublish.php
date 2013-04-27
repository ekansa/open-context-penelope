<?php
/* This class makes a table object for Open Context
 * based on a table created by the TabOut_Table class
 * it generates appropriate metadata as well as JSON data for Open Context's table
 */

class TabOut_TablePublish  {
    
	 public $metadata; //metadata array
	 
	 public $db; //database connection object
	 
	 public $penelopeTabID; //name of the table in Penelope
	 public $requestParams; //parameters sent in a post request (for updating table metadata)
	 
	 public $setURI; //URI that can be used to duplicate the table. false if it can't be duplicated with a query
	 public $recordCount; //number of records in the total set
	 public $published; // boolean true or false, is the table published
	 public $pubCreated; //date-time for the initial publication data table
	 public $pubUpdate; //date-time for the last update of the published table
	 
	 
	 public $linkedPersons; //array and count of associated records of individuals in the table
	 public $creators; //array and count of associated records of dublin core creators
	 public $contributors; //array and count of associated records of dublin core contributors
	 public $projects; //array and count of associated projects represented in a table
	 
	 public $cache_id; //id for the table
	 public $setLastPublished; //publication time for the set
	 public $setLastUpdated; //last update time for the set
	 
	 public $tableName;
	 public $tableDesciption;
	 public $tableTags;
	 public $tableDOI; //doi for the table
	 public $tableARK; //ark for the table
	 public $numSegments; //number of segments a large table is divided into
	 public $recsPerTable; //number of records per table
	 
	 public $zippedFile; //filename for a zip compression CSV version.
	 
	 public $tableFieldsTemp; //array of table fields, temporary for internal use.
	 public $tableFields; //array of table fields
	 public $sampleRecords; //array of sample records (not the full set)
	 public $records; //array of all data records
	 
	 const defaultSample = 50;
	 const tagDelim = ";"; //delimiter for tags
	 
	 const personBaseURI = "http://opencontext.org/persons/";
	 const projectBaseURI = "http://opencontext.org/projects/";
	 
	 //get saved metadata from the database
	 function getSavedMetadata(){
		  
		  $db = $this->startDB();
		  
		  $sql = "SELECT *
		  FROM export_tabs_meta
		  WHERE source_id = '".$this->penelopeTabID."'
		  LIMIT 1;
		  ";
		  
		  $result = $db->fetchAll($sql);
		  if($result){
				$this->published = $result[0]["published"];
				if($this->published){
					 $this->pubCreated = $result[0]["pub_created"];
					 $this->pubUpdate = $result[0]["pub_update"];
				}
				else{
					 $this->pubCreated = false;
					 $this->pubUpdate = true;
				}
				$metadataJSON = $result[0]["metadata"];
				$metadata = Zend_Json::decode($metadataJSON);
				$this->metadata = $metadata;
				return true;
		  }
		  else{
				return false;
		  }
	 }
	 

	 //parse metadata array
	 function metadataParse(){
		  $metadata = $this->metadata;
		  $this->tableName = $metadata["title"];
		  $this->tableDesciption = $metadata["description"];
		  $this->tableTags = $metadata["tags"];
		  $this->tableDOI = $metadata["doi"];
		  $this->tableARK = $metadata["ark"];
		  $this->recordCount = $metadata["recordCount"];
		  $this->creators = $metadata["creators"];
		  $this->contributors = $metadata["contributors"];
		  $this->persons = $metadata["persons"];
		  $this->projects = $metadata["projects"];
		  $this->tableFields = $metadata["tableFields"];
	 }
	 
	 
	 //save metadata to the database
	 function saveMetadata(){
		  
		  $db = $this->startDB();
		  $metadataJSON = $this->generateJSON();
		  
		  $where = "source_id = '".$this->penelopeTabID."' ";
		  $db->delete("export_tabs_meta", $where);
		  
		  $data = array(	"source_id" => $this->penelopeTabID,
								"title" => $this->tableName,
								"published" => $this->published,
								"pub_created" => $this->pubCreated,
								"pub_update" => $this->pubUpdate,
								"metadata" => $metadataJSON
						  );
		  
		  $db->insert("export_tabs_meta", $data);
	 }//end function
	 
	 
	 // this makes JSON
	 function generateJSON(){
		  $metadata = array();
		  $metadata["title"] = $this->tableName;
		  $metadata["description"] = $this->tableDesciption;
		  $metadata["tags"] = $this->tableTags;
		  $metadata["doi"] = $this->tableDOI;
		  $metadata["ark"] = $this->tableARK;
		  $metadata["recordCount"] = $this->recordCount;
		  $metadata["creators"] = $this->creators;
		  $metadata["contributors"] = $this->contributors;
		  $metadata["persons"] = $this->linkedPersons;
		  $metadata["projects"] = $this->projects;
		  $metadata["tableFields"] = $this->tableFields;
		  $this->metadata = $metadata;
		  return Zend_Json::encode($metadata);
	 }
	 
	 
	 
	 
	 // this function is the main function for updating metadata records for a table
	 function addUpdateMetadata(){
	 
		  $this->getMakeMetadata(); //get previously saved metadata
		  
		  $chValue = $this->checkParam("title"); 
		  if($chValue != false ){
				$this->tableName = $chValue;
		  }
		  
		  $chValue = $this->checkParam("description"); 
		  if($chValue != false ){
				$this->tableDesciption = $chValue;
		  }
		  
		  $chValue = $this->checkParam("doi"); 
		  if($chValue != false ){
				$this->tableDOI = $chValue;
		  }
		  
		  $chValue = $this->checkParam("ark"); 
		  if($chValue != false ){
				$this->tableARK = $chValue;
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
		  
		  $this->saveMetadata(); //save the upated metadata
	 }
	 
	 
	 //get saved metadata if it exists, if not make new metadata record
	 function getMakeMetadata(){
		  if(!$this->getSavedMetadata()){
				$this->tableName = "[Not titled]";
				$this->tableDesciption = "[Not described]";
				$this->tableTags = false;
				$this->tableDOI = false;
				$this->tableARK = false;
				$this->published = false;
				$this->pubCreated = false;
				$this->pubUpdate = false;
				$this->getProjects();
				$this->getPersons();
				$this->saveMetadata(); //save the results
		  }
		  else{
				$this->metadataParse(); //get saved metadata ready to use
		  }
		  $this->getTableSize(); //get the number of records in a table
		  $this->getTableFields(); //get the fields in a table.
		  $this->getSampleRecords(); //get the fields in a table.
	 }
	 
	 
	 //this function makes some metadata automatically, based on the table's associations
	 function autoMetadata(){
		  
		  $this->getMakeMetadata();
		  $this->getProjects();
		  $this->getPersons();
		  $this->saveMetadata(); //save the results
	 }
	 
	 
	 
	 //get associated projects with a table
	 function getProjects(){
		  
		  $db = $this->startDB();
		  $result = false;
		  
		  $sql = "SELECT count(actTab.uuid) AS recCount, project_list.project_id, project_list.project_name
		  FROM ".$this->penelopeTabID." AS actTab
		  JOIN space ON actTab.uuid = space.uuid
		  JOIN project_list ON space.project_id = project_list.project_id
		  GROUP BY space.project_id 
		  ";
		  
		  $result = $db->fetchAll($sql);
		  if($result){
				$projects = array();
				foreach($result as $row){
					 $name = $row["project_name"];
					 $count = $row["recCount"] + 0;
					 $uuid = $row["project_id"];
					 $uri = self::projectBaseURI.$uuid;
					 $projects[$uri] = array("name" => $name, "count" => $count);
				}//end loop
				
				$this->projects = $projects;
		  }//end case with results
	 
	 }//end function
	 
	 
	 //get dublin-core creator, contributor, and related people information
	 function getPersons(){
		  
		  $linksObj = new dbXML_dbLinks;
		  $creatorRels = $linksObj->relToCreator;
		  $contribRels = $linksObj->relToContributor;
		  
		  $db = $this->startDB();
		  $result = false;
		  
		  $sql = "SELECT actTab.uuid, links.targ_uuid, links.link_type,
		  persons.combined_name, persons.last_name, persons.first_name, persons.mid_init
		  FROM ".$this->penelopeTabID." AS actTab
		  JOIN links ON actTab.uuid = links.origin_uuid
		  JOIN persons ON persons.uuid = links.targ_uuid
		  WHERE links.targ_type LIKE '%person%' ;
		  ";
		  
		  $resultA = $db->fetchAll($sql);
		 
		  $sql =	"	
				SELECT actTab.uuid, links.targ_uuid, links.link_type, 
				users.combined_name, users.last_name, users.first_name, users.mid_init
				FROM ".$this->penelopeTabID." AS actTab
				JOIN links ON actTab.uuid = links.origin_uuid
				JOIN users ON users.uuid = links.targ_uuid
				WHERE links.targ_type LIKE '%person%'
				 
				";
		  
		  $resultB = $db->fetchAll($sql);
		  if($resultA && $resultB){
				$result = array();
				foreach($resultA as $row){
					 $ukey = md5($row["uuid"].$row["targ_uuid"].$row["link_type"]);
					 $result[$ukey] = $row;
				}
				foreach($resultB as $row){
					 $ukey = md5($row["uuid"].$row["targ_uuid"].$row["link_type"]);
					 if(!array_key_exists($ukey, $result)){
						  $result[$ukey] = $row;
					 }
				}
		  }
		  elseif($resultB && !$resultA){
				$result = $resultB;
		  }
		  elseif(!$resultB && $resultA){
				$result = $resultA;
		  }
		  
		  if($result){
				$creators = array();
				$contributors = array();
				$persons = array();
				foreach($result as $row){
					 $uuid = $row["targ_uuid"];
					 $uri = self::personBaseURI.$uuid;
					 $name = $row["combined_name"];
					 $linkType = $row["link_type"];
					 if(in_array($linkType, $creatorRels)){
						  if(!array_key_exists($uri, $creators)){
								$creators[$uri] = array("name" => $name, "count" => 1);
						  }
						  else{
								$creators[$uri]["count"] ++ ;  
						  }
					 }
					 elseif(in_array($linkType, $contribRels)){
						  if(!array_key_exists($uri, $contributors)){
								$contributors[$uri] = array("name" => $name, "count" => 1);
						  }
						  else{
								$contributors[$uri]["count"] = $contributors[$uri]["count"] + 1; 
						  }
					 }
					 
					 if(!array_key_exists($uri, $persons)){
						  $persons[$uri] = array("name" => $name, "count" => 1);
					 }
					 else{
						  $persons[$uri]["count"] ++ ;  
					 }
					 
				}
				
				$this->creators = $creators;
				$this->contributors = $contributors;
				$this->linkedPersons = $persons;
				
		  }
	 }//end function
	 
	 
	 
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
	 
	 //get the number of records in a table
	 function getTableSize(){
		  $db = $this->startDB();
		  
		  $sql = "SELECT count(id) as IDcount
		  FROM ".$this->penelopeTabID."
		  ";
		  
		  $result = $db->fetchAll($sql);
		  if($result){
				$this->recordCount = $result[0]["IDcount"];
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
