<?php
/* This class makes a table object for Open Context
 * based on a table created by the TabOut_Table class
 * it generates appropriate metadata as well as JSON data for Open Context's table
 */

class TabOut_TablePublish  {
    
	 public $metadata; //metadata array
	 public $JSON_LD; //JSON_LD metadata array
	 
	 public $db; //database connection object
	 
	 public $penelopeTabID; //name of the table in Penelope
	 public $publishedURI; //published URI
	 public $requestParams; //parameters sent in a post request (for updating table metadata)
	 
	 public $setURI; //URI that can be used to duplicate the table. false if it can't be duplicated with a query
	 public $recordCount; //number of records in the total set
	 public $published; // boolean true or false, is the table published
	 public $pubCreated; //date-time for the initial publication data table
	 public $pubUpdate; //date-time for the last update of the published table
	 
	 
	 public $rawLinkedPersons; //array and count of associated records of individuals in the table
	 public $rawCreators; //array and count of associated records of dublin core rawCreators
	 public $rawContributors; //array and count of associated records of dublin core rawContributors
	 public $projects; //array and count of associated projects represented in a table
	 public $tableID; //ID for the table
	 public $tablePage; //page number for the table.
	 public $tableGroupID; //id for the table group used where a single dataset is broken into multiple tables
	 
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
	 
	 public $versionControl; //link to a the data under version control (like GitHub)
	 public $license; //array with URI to the copyright license for the table
	 public $files; //array of filenames and their sizes
	 
	 public $tableFieldsTemp; //array of table fields, temporary for internal use.
	 public $tableFields; //array of table fields
	 public $sampleRecords; //array of sample records (not the full set)
	 public $records; //array of all data records
	 
	 const defaultSample = 50;
	 const tagDelim = ";"; //delimiter for tags
	 
	 const primaryKeyFieldLabel = "Open Context URI";
	 const primaryKeyField = "uri";
	 
	 const personBaseURI = "http://opencontext.org/persons/";
	 const projectBaseURI = "http://opencontext.org/projects/";
	 const tableBaseURI = "http://opencontext.org/tables/";
	 
	 const closeMatchURI = "http://www.w3.org/2004/02/skos/core#closeMatch";
	 const closeMatch = "closeMatch";
	 
	 const JSONldVersion = .5;
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
				
				if(strlen($result[0]["tableID"])>1){
					 $this->tableID = $result[0]["tableID"];
				}
				else{
					 $this->tableID = false;
				}
				$this->tablePage = $result[0]["page"] +0 ;
				if($this->tablePage > 0 || strstr($this->tableID, "/")){
					 if(strlen($result[0]["tableGroupID"])>1){
						  $this->tableGroupID = $result[0]["tableGroupID"];
					 }
					 else{
						  $tabIDex = explode("/", $this->tableID);
						  $this->tableGroupID = $tabIDex[0];
						  $this->tablePage  = $tabIDex[1];
					 }
				}
				else{
					 $this->tableGroupID = $this->tableID;
				}
		  
				$this->published = $result[0]["published"];
				$this->publishedURI = $result[0]["publishedURI"];
				if($this->published){
					 $this->pubCreated = $result[0]["pub_created"];
					 $this->pubUpdate = $result[0]["pub_update"];
				}
				else{
					 if(substr($result[0]["pub_created"],0,4) != "0000"){
						  $this->pubCreated = $result[0]["pub_created"];
					 }
					 else{
						  $this->pubCreated = false;
					 }
					 $this->pubUpdate = true;
				}
				$metadataJSON = $result[0]["metadata"];
				@$metadata = Zend_Json::decode($metadataJSON);
				if(is_array($metadata)){
					 $metadata["tableID"] = $this->tableID;
					 $this->metadata = $metadata;
					 $this->checkSavedFiles();
				}
				else{
					 $this->autoMetadataOnly();
					 
				}
				return true;
		  }
		  else{
				return false;
		  }
	 }
	 
	 //check to see if files were created.
	 function checkSavedFiles(){
		  
		  $tableFiles = new TabOut_TableFiles;
		  $baseFileName = $this->tableID;
		  $tableFiles->getAllFileSizes($baseFileName);
		  $this->files = $tableFiles->savedFileSizes;
		  $metadata = $this->metadata;
		  $tableFields = $metadata["tableFields"];
		  unset($metadata["tableFields"]);
		  $metadata["files"] = $this->files;
		  $metadata["tableFields"] = $tableFields; //just for sake of order! :)
		  $this->metadata = $metadata;
		  
	 }
	 
	 
	 
	 
	 //get saved metadata from the database, from already published URI
	 function getSavedMetadataByURI($uri){
		  
		  $db = $this->startDB();
		  
		  $sql = "SELECT *
		  FROM export_tabs_meta
		  WHERE publishedURI = '".$uri."'
		  LIMIT 1;
		  ";
		  
		  $result = $db->fetchAll($sql);
		  if($result){
				$this->tableID = $result[0]["tableID"];
				$this->tablePage = $result[0]["page"] +0 ;
				$this->published = $result[0]["published"];
				$this->publishedURI = $uri;
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
				$this->checkSavedFiles();
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
		  $this->recordCount = $metadata["recordCount"]+0;
		  $this->rawCreators = $metadata["rawCreators"];
		  $this->rawContributors = $metadata["rawContributors"];
		  $this->rawLinkedPersons = $metadata["rawLinkedPersons"];
		  $this->projects = $metadata["projects"];
		  
		  if(isset($metadata["versionControl"])){
				$this->versionControl = $metadata["versionControl"];
		  }
		  
		  if(isset($metadata["tableFields"])){
				 $this->tableFields = $metadata["tableFields"];
		  }
		  if(isset($metadata["license"])){
				$this->license = $metadata["license"];
		  }
		  else{
				$this->license = $this->DBgetLicense();
				$metadata["license"] = $this->license;
				$this->metadata = $metadata;
				$this->saveMetadata(); //save the results
		  }
		  
		  if(!$this->tableFields){
				$this->getTableFields();
				$metadata["tableFields"] = $this->tableFields;
				$this->metadata = $metadata;
				$this->saveMetadata();
		  }
		  
	 }
	 
	 
	 //save metadata to the database
	 function saveMetadata(){
		  
		  $db = $this->startDB();
		  $metadataJSON = $this->generateJSON();
		  
		  $where = "source_id = '".$this->penelopeTabID."' ";
		  $db->delete("export_tabs_meta", $where);
		  
		  if($this->tablePage < 1){
				if(strstr($this->tableID, "/")){
					 $tableEx = explode("/", $this->tableID);
					 $this->tablePage = $tableEx[1];
					 $this->tableGroupID = $tableEx[0];
				}
		  }
		  if(strstr($this->tableGroupID, "/")){
				$tableEx = explode("/", $this->tableGroupID);
				$this->tableGroupID = $tableEx[0];
		  }
		  
		  $data = array(	"source_id" => $this->penelopeTabID,
								"tableID" => $this->tableID,
								"tableGroupID" => $this->tableGroupID,
								"page" => $this->tablePage,
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
		  $metadata["tableID"] = $this->tableID;
		  $metadata["tableGroupID"] = $this->tableGroupID;
		  $metadata["tablePage"] = $this->tablePage +0 ;
		  $metadata["title"] = $this->tableName;
		  $metadata["description"] = $this->tableDesciption;
		  $metadata["tags"] = $this->tableTags;
		  $metadata["doi"] = $this->tableDOI;
		  $metadata["ark"] = $this->tableARK;
		  $metadata["versionControl"] = $this->versionControl;
		  $metadata["license"] = $this->license;
		  $metadata["recordCount"] = $this->recordCount+0;
		  $metadata["rawCreators"] = $this->rawCreators;
		  $metadata["rawContributors"] = $this->rawContributors;
		  $metadata["rawLinkedPersons"] = $this->rawLinkedPersons;
		  $metadata["projects"] = $this->projects;
		  $metadata["files"] = $this->files;
		  $metadata["tableFields"] = $this->tableFields;
		  $this->metadata = $metadata;
		  return Zend_Json::encode($metadata);
	 }
	 
	 
	 //this makes JSON-LD from JSON
	 function generateJSON_LD(){
		  
		  $metadata = $this->metadata;
		  $JSON_LD = array();
		  
		  $JSON_LD["metadata-version"] = self::JSONldVersion;
		  $JSON_LD["@context"] = array(

				"type" => "@type",
				"id" => "@id",
				"tableID" => "http://purl.org/dc/elements/1.1/identifier",
				"title" => "http://purl.org/dc/elements/1.1/title",
				"description" => "http://purl.org/dc/terms/abstract",
				"published" => "http://purl.org/dc/terms/issued",
				"updated" => "http://purl.org/dc/terms/modified",
				"doi" => "http://purl.org/ontology/bibo/doi",
				"ark" => "http://en.wikipedia.org/wiki/Archival_Resource_Key",
				"versionControl" => "http://purl.org/dc/terms/hasVersion",
				//"editorList" => array("@id" => "http://purl.org/ontology/bibo/editorList", "@container" => "@list"),
				"editorList" => "http://purl.org/ontology/bibo/editorList",
				"editor" => "http://purl.org/ontology/bibo/editor",
				//"contributorList" => array("@id" => "http://purl.org/ontology/bibo/contributorList", "@container" => "@list"),
				"contributorList" => "http://purl.org/ontology/bibo/contributorList",
				"contributor" => "http://purl.org/dc/terms/contributor",
				"references" => "http://purl.org/dc/terms/references",
				"name" => "http://www.w3.org/2000/01/rdf-schema#label",
				"spatial" => "http://purl.org/dc/terms/spatial",
				"temporal" => "http://purl.org/dc/terms/temporal",
				"recordCount" => "http://rdfs.org/ns/void#entities", //number of entities
				"fieldCount" => "http://rdfs.org/ns/void#properties", //number of properties
				"license" => "http://www.w3.org/1999/xhtml/vocab/#license", //copyright license
				"publisher" => "http://purl.org/dc/terms/publisher",
				"partOf" => "http://purl.org/dc/terms/isPartOf"
				);
		  
		  $JSON_LD["id"] = $this->generateTableURI();
		  $JSON_LD["tableID"] = $this->getGenerateTableID();
		  $JSON_LD["title"] = $metadata["title"];
		  $JSON_LD["description"] = $metadata["description"];
		  if($this->pubCreated){
				$JSON_LD["published"] = date("Y-m-d\TH:i:s\-07:00", strtotime($this->pubCreated));
		  }
		  else{
				$JSON_LD["published"] = date("Y-m-d\TH:i:s\-07:00", time());
		  }
		  $JSON_LD["updated"] = date("Y-m-d\TH:i:s\-07:00", time());
		  $JSON_LD["recordCount"] = $metadata["recordCount"];
		  $JSON_LD["fieldCount"] = count($metadata["tableFields"]);
		  $JSON_LD["publisher"] = array("name" => "Open Context",
												  "id" => "http://opencontext.org");
		  $JSON_LD["license"] = $metadata["license"];
		  
		  if($metadata["doi"] != false){
				$JSON_LD["doi"] = $metadata["doi"];
		  }
		  if($metadata["ark"] != false){
				$JSON_LD["ark"] = $metadata["ark"];
		  }
		  if($this->tablePage > 1){
				$JSON_LD["partOf"] = $this->generateParentPartURI();
		  }
		  if(isset($metadata["versionControl"])){
				if($metadata["versionControl"] != false){
					 $JSON_LD["versionControl"] = $metadata["versionControl"];
				}
		  }
		  
		  if(count($metadata["rawCreators"])>0){
				$JSON_LD["editorList"]["@id"] = "#editor-list";
				foreach($metadata["rawCreators"] as $uriKey => $nameArray){
					 $JSON_LD["editorList"]["editor"][] = array("name" => $nameArray["name"],
																			  "count" => $nameArray["count"],
																			  "id" => $uriKey
																			  );
					 
				}
		  }
		  if(count($metadata["rawContributors"])>0){
				$JSON_LD["contributorList"]["@id"] = "#contributor-list";
				foreach($metadata["rawContributors"] as $uriKey => $nameArray){
					 $JSON_LD["contributorList"]["contributor"][] = array("name" => $nameArray["name"],
																			  "count" => $nameArray["count"],
																			  "id" => $uriKey
																			  );
					 
				}
		  }
		  if(count($metadata["projects"])>0){
				foreach($metadata["projects"] as $uriKey => $nameArray){
					 $JSON_LD["references"][] = array("name" => $nameArray["name"],
																			  "count" => $nameArray["count"],
																			  "type" => "http://opencontext/about/concepts#projects",
																			  "id" => $uriKey
																			  );
					 
				}
		  }
		  
		  $JSON_LD["files"] = $metadata["files"];
		  $JSON_LD["tableFields"] = $metadata["tableFields"];
		  $this->JSON_LD = $JSON_LD;
		  return $JSON_LD;
	 }
	 
	 
	 function generateTableURI(){
		  $tableURI = self::tableBaseURI.($this->tableID);
		  if($this->tablePage > 1 && !strstr($this->tableID, "/")){
				$tableURI .= "/".($this->tablePage);
		  }
		  return $tableURI;
	 }
	 
	 function generateParentPartURI(){
		  $tableURI = self::tableBaseURI.($this->tableGroupID);
		  return $tableURI;
	 }
	 
	 
	 //publish the table metadata to a URI
	 function publishTableJSON($destinationURI){
		  $output = false;
		  
		  $client = new Zend_Http_Client();
		  $client->setUri($destinationURI);
		  $client->setConfig(array(
				'maxredirects' => 0,
				'timeout'      => 280));
		  
		  $clientParams = array("json" => Zend_Json::encode($this->JSON_LD));
		  
		  $client->setParameterPost($clientParams);
		  $response = $client->request('POST');
		  if($response){
				$JSON = $response->getBody();
				$output = Zend_Json::decode($JSON);
				if(!is_array($output)){
					 $output = array("error" => true, "response" => $JSON);
				}
				else{
					 $output = $this->OKpublishProces($output);
				}
		  }

		  return $output;
	 }
	 
	 function OKpublishProces($resultObj){
		  
		  $db = $this->startDB();
		  $where = "source_id = '".$this->penelopeTabID."' ";	 
		  $data = array();
		  
		  if(isset( $resultObj["id"])){
				$data["published"] = true;
				$data["pub_created"] = $resultObj["published"];
				$data["pub_update"] = $resultObj["updated"];
				$data["publishedURI"] = $resultObj["id"];
				
				$db->update("export_tabs_meta", $data, $where);
				return $resultObj;
		  }
		  else{
				$resultObj["error"] = true;
				$resultObj["message"] = "Strange JSON";
				return $resultObj;
		  }
	 }
	 
	 
	 
	 
	 //make a table ID if you don't have one yet. 
	 function getGenerateTableID(){
		  
		  if(!$this->tableID){
				
				$db = $this->startDB();
				$where = "source_id = '".$this->penelopeTabID."' ";	 
				$metadataString = Zend_Json::encode($this->metadata);
				$this->tableID = md5($this->penelopeTabID.$metadataString);
				
				$data = array("tableID" => $this->tableID);
				$db->update("export_tabs_meta", $data, $where);
		  }
		  
		  return $this->tableID;
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
		  
		  $chValue = $this->checkParam("versionControl");
		  if($chValue != false){
				$this->versionControl = $chValue;
		  }
		  
		  $chValue = $this->checkParam("pubCreated");
		  if($chValue != false){
				$this->pubCreated = $chValue;
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
				$this->tableID = false;
				$this->tableName = "[Not titled]";
				$this->tableDesciption = "[Not described]";
				$this->tableTags = false;
				$this->tableDOI = false;
				$this->tableARK = false;
				$this->published = false;
				$this->pubCreated = false;
				$this->pubUpdate = false;
				$this->license = $this->DBgetLicense();
				$this->getProjects();
				$this->getPersons();
				$this->getProjectCreators();
				$this->getGenerateTableID(); //create a table ID based in the metadata for the table
				$this->saveMetadata(); //save the results
		  }
		  else{
				$this->metadataParse(); //get saved metadata ready to use
		  }
		  $this->getTableSize(); //get the number of records in a table
		  $this->getTableFields(); //get the fields in a table.
		  $this->getSampleRecords(); //get the fields in a table.
	 }
	 
	 function autoMetadataOnly(){
		  
		  $this->tableName = "[Not titled]";
		  $this->tableDesciption = "[Not described]";
		  $this->tableTags = false;
		  $this->tableDOI = false;
		  $this->tableARK = false;
		  $this->published = false;
		  $this->pubCreated = false;
		  $this->pubUpdate = false;
		  $this->license = $this->DBgetLicense();
		  $this->getProjects();
		  $this->getPersons();
		  $this->getProjectCreators();
		  $this->getGenerateTableID(); //create a table ID based in the metadata for the table
		  $this->saveMetadata(); //save the results
		  
		  $this->getTableSize(); //get the number of records in a table
		  $this->getTableFields(); //get the fields in a table.
		  $this->getSampleRecords(); //get the fields in a table.
		  
	 }
	 
	 
	 
	 //this function makes some metadata automatically, based on the table's associations
	 function autoMetadata(){
		  
		  $this->getMakeMetadata();
		  $this->getProjects();
		  $this->getPersons();
		  $this->getProjectCreators();
		  $this->saveMetadata(); //save the results
	 }
	 
	 
	 
	 //get the most restrictive license for a dataset
	 function DBgetLicense(){
		  
		  $db = $this->startDB();
		  
		  $sql = "SELECT fs.fk_license, cc.LINK_DEED as id, cc.NAME as name
		  FROM ".$this->penelopeTabID." AS extab
		  JOIN space ON extab.uuid = space.uuid
		  JOIN file_summary AS fs ON space.source_id = fs.source_id
		  JOIN w_lu_creative_commons AS cc ON fs.fk_license = cc.PK_LICENSE
		  GROUP BY fs.source_id
		  ORDER BY fs.fk_license DESC
		  ";
		  
		  $result = $db->fetchAll($sql); 
		  if($result){
				$output = $result[0];
				if(stristr($output["id"], "creativecommons")){
					 $output["name"] = "Creative Commons ".$output["name"];
				}
				unset($output["fk_license"]);
				return $output;
		  }
		  else{
				return array("id" => "http://creativecommons.org/licenses/by/3.0",
								 "name" => "Creative Commons Attribution"
								 );
		  }
		  
		  
	 }
	 
	 
	 
	 //this function makes some metadata automatically, based on the table's associations
	 function removePerson(){
		  
		  $this->getMakeMetadata();
		  $changesMade = false;
		  $requestParams = $this->requestParams;
		  if(isset($requestParams["uri"]) && isset($requestParams["role"])){
				
				$personURI = $requestParams["uri"];
				if($requestParams["role"] == "creator"){
					 $persons = $this->rawCreators;
					 unset($persons[$personURI]);
					 $this->rawCreators = $persons;
					 $changesMade = true;
				}
				elseif($requestParams["role"] == "contributor"){
					 $persons = $this->rawContributors;
					 unset($persons[$personURI]);
					 $this->rawContributors = $persons;
					 $changesMade = true;
				}
				elseif($requestParams["role"] == "person"){
					 $persons = $this->rawLinkedPersons;
					 unset($persons[$personURI]);
					 $this->rawLinkedPersons = $persons;
					 $changesMade = true;
				}
				else{
					
				}
		  }
		 
		  if($changesMade){
				$this->saveMetadata(); //save the results
		  }
		  return $changesMade;
	 }
	 
	 
	 //consolidate persons
	 function consolidatePersons(){
		  
		  $this->getMakeMetadata();
		  $changesMade = false;
		  $requestParams = $this->requestParams;
		  if(isset($requestParams["role"])){
				
				if($requestParams["role"] == "creator"){
					 $persons = $this->rawCreators;
					 $persons = $this->consolidateRelatedURIs($persons);
					 $persons = $this->orderURIs($persons);
					 $this->rawCreators = $persons;
					 $changesMade = true;
				}
				elseif($requestParams["role"] == "contributor"){
					 $persons = $this->rawContributors;
					 $persons = $this->consolidateRelatedURIs($persons);
					 $persons = $this->orderURIs($persons);
					 $this->rawContributors = $persons;
					 $changesMade = true;
				}
				elseif($requestParams["role"] == "person"){
					 $persons = $this->rawLinkedPersons;
					 $persons = $this->consolidateRelatedURIs($persons);
					 $persons = $this->orderURIs($persons);
					 $this->rawLinkedPersons = $persons;
					 $changesMade = true;
				}
				else{
					
				}
		  }
		 
		  if($changesMade){
				$this->saveMetadata(); //save the results
		  }
		  return $changesMade;
	 }
	 
	 
	 
	 
	 //this function makes some metadata automatically, based on the table's associations
	 function addPerson(){
		  
		  $this->getMakeMetadata();
		  $changesMade = false;
		  $requestParams = $this->requestParams;
		  if(isset($requestParams["uuid"]) && isset($requestParams["role"])){
				
				$personUUID = trim($requestParams["uuid"]);
				$uri = self::personBaseURI.$personUUID;
				
				if(isset($requestParams["rank"])){
					 $rank = $requestParams["rank"];
				}
				else{
					 $rank = false;
				}
				
				$pObj = new dbXML_dbPerson;
				$pObj->initialize();
				$pObj->dbPenelope = true;
				$pFound = $pObj->getByID($personUUID);
				if($pFound){
					 $name = $pObj->label;
					 if($requestParams["role"] == "creator"){
						  $persons = $this->rawCreators;
						  $persons = $this->addPersonRank($persons, $name, $uri, $rank);
						  $this->rawCreators = $persons;
						  $changesMade = true;
					 }
					 elseif($requestParams["role"] == "contributor"){
						  $persons = $this->rawContributors;
						  $persons = $this->addPersonRank($persons, $name, $uri, $rank);
						  $this->rawContributors = $persons;
						  $changesMade = true;
					 }
					 elseif($requestParams["role"] == "person"){
						  $persons = $this->rawLinkedPersons;
						  $persons = $this->addPersonRank($persons, $name, $uri, $rank);
						  $this->rawLinkedPersons = $persons;
						  $changesMade = true;
					 }
					 else{
						 
					 }
				}
		  }
		 
		  if($changesMade){
				$this->saveMetadata(); //save the results
		  }
		  return $changesMade;
	 }
	 
	 
	 
	 //add a person to an array of people, put that fellow in the right order
	 function addPersonRank($persons, $name, $uri, $rank = false){
		  
		  $persCount = count($persons);
		  if(!$rank){
				$rank = $persCount;
		  }
		  if(!is_numeric($rank)){
				$rank = $persCount;
		  }
		  else{
				$rank = round($rank, 0);
		  }
		  
		  if($rank < 1){
				$rank = 1;
		  }
		  
		  if($rank < $persCount){
				$newPersons = array();
				$i = 1;
				foreach($persons as $uriKey => $pArray){
					 if($i == $rank){
						  $newPersons[$uri] = array("name" => $name, "count" => false); //add the new person at the right rank
					 }
					 $newPersons[$uriKey] = $pArray; //add existing person
				$i++;
				}
				unset($persons);
				$persons = $newPersons;
		  }
		  else{
				$persons[$uri] = array("name" => $name, "count" => false); //add the new person at the end of the array
		  }
		  
		  return $persons;
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
				
				$projects = $this->orderURIs($projects);
				$this->projects = $projects;
		  }//end case with results
	 
	 }//end function
	 
	 
	 //get dublin core rawCreators associated with the project
	 function getProjectCreators(){
		  
		  $rawCreators = $this->rawCreators;
		  if(!is_array($rawCreators)){
				$rawCreators = array();
		  }
		  
		  $db = $this->startDB();
		  if(is_array($this->projects)){
				$projects = $this->projects;
				foreach($projects as $uriKey => $pArray){
					 $uriEx = explode("/", $uriKey);
					 $projectUUID = $uriEx[count($uriEx) - 1]; //last member of the array is the UUID
					 $projectCount = $pArray["count"];
					 
					 
					 $sql = "SELECT dc_field, dc_value
					 FROM dcmeta_proj
					 WHERE project_id = '$projectUUID'";
					 
					 $result = $db->fetchAll($sql, 2);
					 if($result){
						  foreach($result as $row){
								$rawField = trim($row["dc_field"]);
								$DCvalue = trim($row["dc_value"]);
								
								if(stristr($rawField, "creator")){
									 $personUUID = $this->getPersonID($projectUUID, $DCvalue);
									 $personURI = self::personBaseURI.$personUUID;
									 $rawCreators[$personURI] = array("name" => $DCvalue,
																			 "count" => $projectCount + 0 );
									 $rawCreators[$personURI]["rel"] = $this->getLinkedPerson($personUUID);
								}
						  }
					 }
				}
				
				$rawCreators = $this->consolidateRelatedURIs($rawCreators);
				$rawCreators = $this->orderURIs($rawCreators);
				
				$this->rawCreators = $rawCreators;
		  }

	 }//end function
	 
	 
	 //does what it says. gets a person's uuid based on their name and project information
	 function getPersonID($projectUUID, $personName){
	
		  $db = $this->startDB();
		  
		  $sql = "SELECT persons.uuid
					FROM persons
					WHERE persons.project_id = '".$projectUUID."'
					AND persons.combined_name LIKE '%".$personName."%'	
					LIMIT 1
					
					UNION
					
					SELECT users.uuid AS uuid
					FROM users 
					WHERE users.combined_name LIKE '%".$personName."%'	
					LIMIT 1
					
					";
		 
		  $result = $db->fetchAll($sql, 2);
		  $personID = false;
		  if($result){
				$personID = $result[0]["uuid"];
		  }
		  
		  return $personID;
        
    } //end function
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 
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
				
				$rawCreators = array();
				$rawContributors = array();
				$persons = array();
				foreach($result as $row){
					 $uuid = $row["targ_uuid"];
					 $uri = self::personBaseURI.$uuid;
					 $name = $row["combined_name"];
					 $linkType = $row["link_type"];
					 if(in_array($linkType, $creatorRels)){
						  if(!array_key_exists($uri, $rawCreators)){
								$rawCreators[$uri] = array("name" => $name, "count" => 1);
								$rawCreators[$uri]["rel"] = $this->getLinkedPerson($uuid);
						  }
						  else{
								$rawCreators[$uri]["count"] ++ ;  
						  }
					 }
					 elseif(in_array($linkType, $contribRels)){
						  if(!array_key_exists($uri, $rawContributors)){
								$rawContributors[$uri] = array("name" => $name, "count" => 1);
								$rawContributors[$uri]["rel"] = $this->getLinkedPerson($uuid);
						  }
						  else{
								$rawContributors[$uri]["count"] = $rawContributors[$uri]["count"] + 1; 
						  }
					 }
					 
					 if(!array_key_exists($uri, $persons)){
						  $persons[$uri] = array("name" => $name, "count" => 1);
						  $persons[$uri]["rel"] = $this->getLinkedPerson($uuid);
					 }
					 else{
						  $persons[$uri]["count"] ++ ;  
					 }
					 
				}
				
				
				
				//combine URIs for the same person, choose the URI with the most associated items
				$rawCreators = $this->consolidateRelatedURIs($rawCreators);
				$rawContributors = $this->consolidateRelatedURIs($rawContributors);
				$persons = $this->consolidateRelatedURIs($persons);
				
				//sort the array by count, from high to low
				$rawCreators = $this->orderURIs($rawCreators);
				$rawContributors = $this->orderURIs($rawContributors);
				$persons = $this->orderURIs($persons);
				
				$this->rawCreators = $rawCreators;
				$this->rawContributors = $rawContributors;
				$this->rawLinkedPersons = $persons;
				
		  }
	 }//end function
	 
	 
	 
	 
	 function getLinkedPerson($personUUID){
		  
		  $db = $this->startDB();
		  
		  $personURI = self::personBaseURI.$personUUID;
		  
		  $sql = "SELECT CONCAT('".self::personBaseURI."', itemUUID) as itemURI, linkedURI
		  FROM linked_data
		  WHERE
		  (
				(itemUUID = '$personUUID' AND linkedURI LIKE '".self::personBaseURI."%')
				OR
				(linkedURI = '".$personURI."')
		  )
		  AND (linkedType = '".self::closeMatch."' OR linkedType = '".self::closeMatchURI."' )
		  ";
		  
		  $result = $db->fetchAll($sql);
		  $output = false;
		  if($result){
				$output = array();
				foreach($result as $row){
					 if($row["itemURI"] != $personURI && !in_array($row["itemURI"], $output)){
						  $output[] = $row["itemURI"];
					 }
					 if($row["linkedURI"] != $personURI && !in_array($row["linkedURI"], $output)){
						  $output[] = $row["linkedURI"];
					 }
				}
		  }
		  
		  return $output;
	 }
	 
	 //combines results where there a person has more than 1 URI, choses the URI with the largest number of results
	 function consolidateRelatedURIs($persArray){
		  
		  if(is_array($persArray)){
				$newPersArray = array();
				$doneURIs = array();
				foreach($persArray as $uriKey => $person){
					 $doneRelURIs = array();
					 $uriEx = explode("/",$uriKey);
					 $personUUID = $uriEx[count($uriEx) -1];
					 $actName = $person["name"];
					 $actCount = $person["count"];
					 $linkedPersonURIs = $this->getLinkedPerson($personUUID);
					 
					 if(isset($person["rel"]) ||  is_array($linkedPersonURIs)){
						  
						  if(!isset($person["rel"]) && is_array($linkedPersonURIs)){
								$person["rel"] = $linkedPersonURIs;
						  }
						  
						  if(is_array($person["rel"])){
								$maxCount = $actCount;
								foreach($person["rel"] as $relURI){
									 if(array_key_exists($relURI, $persArray) && !in_array($relURI, $doneURIs)){
										  $relCount = $persArray[$relURI]["count"];
										  if($relCount >  $maxCount){
												$doneURIs[] = $uriKey;
												$uriKey = $relURI;
										  }
										  $actCount = $actCount + $relCount;
									 }
									 $doneRelURIs[] = $relURI;
								}
						  }
					 }
					 
					 if(!in_array($uriKey, $doneURIs)){
						  $doneURIs[] = $uriKey;
						  $newPersArray[$uriKey] = array("name" => $actName, "count" => $actCount);
					 }
					 
					 foreach($doneRelURIs as $doneRel){
						  if(!in_array($doneRel, $doneURIs)){
								$doneURIs[] = $doneRel;
						  }
					 }
				}
		  }
		  else{
				$newPersArray = $persArray;
		  }
		  
		 
		  return $newPersArray;
		  //return $persArray;
	 }
	 
	 
	 //order the array by the count from highest count to lowest
	 function orderURIs($actArray){
		  if(is_array($actArray)){
				if(count($actArray) > 0){
					 $countURIs = array();				
					 foreach($actArray as $uriKey => $actItem){
						  $actCount = $actItem["count"];
						  $countURIs[$uriKey] = $actCount;
					 }
					 arsort($countURIs); //reverse sort, highest to lowest
					 $newArray = array();
					 foreach($countURIs as $uriKey  => $count){
						  $newArray[$uriKey] = $actArray[$uriKey];
					 }
					 unset($actArray);
					 $actArray = $newArray;
				}
		  }
		  return $actArray;
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
				$tableFields[] = self::primaryKeyFieldLabel; //always start with the primary key
				
				$tableFieldsTemp = array();
				$tableFieldsTemp[self::primaryKeyField] = self::primaryKeyFieldLabel; //always start with the primary key
		  
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
				$this->recordCount = $result[0]["IDcount"]+0;
		  }
		  else{
				return false;
		  }
	 }
	 
	 
	 function getSampleRecords($start = 0){
		  
		  $db = $this->startDB();
		  
		  $sql = "SELECT *
		  FROM ".$this->penelopeTabID."
		  LIMIT ".$start.",".self::defaultSample." ;
		  ";
		  
		  $result = $db->fetchAll($sql);
		  if($result){
				$this->sampleRecords = $result;
				return $result;
		  }
		  else{
				return false;
		  }

	 }
	 
	 //return the number of records in the sample
	 function getDefaultSampleSize(){
		  return self::defaultSample;
	 }
	 
	 
	 
	 function getAllRecords(){
		  
		  $db = $this->startDB();
		  
		  
		  $sql = "SELECT *
		  FROM ".$this->penelopeTabID."
		  ";
		  
		  $result = $db->fetchAll($sql);
		  if($result){
				return $result;
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
