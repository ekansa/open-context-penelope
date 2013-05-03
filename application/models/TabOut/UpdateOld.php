<?php

class TabOut_UpdateOld  {
    
	 public $db;
	 public $oldURI;
	 public $oldTableID;
	 public $oldTableData;
	 public $newMetadata;
	 
	 public $tablePage = 1; //current page for the table, default to 1
	 public $totalTabs = 1; //the total number of table segments
	 
	 public $requestParams; //request parameters
	 
	 public $tablePubObj; //object for table metadata to publish
	 
	 public $tempProjects; //temporary project array
	 
	 public $linkedVarTypeFields; //fields for linked Type fields
	 public $itemLinkedData; //linked data associated with items
	 
	 const defaultSample = 50;
	 const personBaseURI = "http://opencontext.org/persons/";
	 const projectBaseURI = "http://opencontext.org/projects/";
	 const subjectBaseURI = "http://opencontext.org/subjects/";
	 
	 //get the table ID from the URL
	 function tableIDfromURI(){
		  if($this->oldURI){
				$uriExp = explode("/", $this->oldURI);
				$this->oldTableID = $uriExp[count($uriExp)-1]; //last part of URL is the ID (unless paging)
				
				if(strlen($this->oldTableID)<4){
					 $this->oldTableID = $uriExp[count($uriExp)-2]; //last part of URL is the ID, this captures the paging issue
				}
				
		  }
	 }
	 
	 
	 //get the old JSON file by URI
	 function getParseJSON(){
		  
		  if($this->oldURI){
				
				$this->tableIDfromURI();
				if(!stristr($this->oldURI, ".json")){
					 $this->oldURI = $this->oldURI.".json";
				}
				
				$tablePubObj = new TabOut_TablePublish;
				$metaFound = $tablePubObj->getSavedMetadataByURI($this->oldURI);
				if($metaFound){
					 $this->newMetadata = $tablePubObj->metadata;
					 unset($tablePubObj);
				}
				else{
					
					 $client = new Zend_Http_Client();
					 $client->setUri($this->oldURI);
					 $client->setConfig(array(
						  'maxredirects' => 0,
						  'timeout'      => 280));
					 $response = $client->request('GET');
					 if($response){
						  $oldJSON = $response->getBody();
						  $this->oldTableData = Zend_Json::decode($oldJSON);
					 }
				}
		  }
		  
		  
	 }//end function
	 
	 
	 //no go ahead and make new metadata from the old
	 function processOldData(){
		  
		  if(is_array($this->oldTableData)){
				
				$tablePubObj = new TabOut_TablePublish;
				$this->tablePubObj = $tablePubObj;
				
				$this->processOldMetadata(); //extract old metadata and make in new
				$this->processProjects(); //extract all the projects referenced in the dataset
				$this->processPeople(); //extract all persons referenced in the dataset
				$this->modFieldsAddURIs(); //modify field names, add URIs to the output table
				
				$tablePubObj = $this->tablePubObj;
				$tablePubObj->getProjectCreators(); //get dublin-core creator information from the project metadata
				$tablePubObj->generateJSON();
				$this->tablePubObj = $tablePubObj;
				$this->newMetadata = $tablePubObj->metadata;
				
				$this->truncateOldRecords(); //show only a sample of the old records
		  }
 
	 }
	 
	 
	 
	 //this function simply goes through a table and saves its record associations without 
	 function processOldRecords(){
		  if(is_array($this->oldTableData) && $this->oldTableData){
				
				$oldData = $this->oldTableData;
				if(isset($oldData["meta"]["table_segments"]["currentTab"])){
					 $this->tablePage = $oldData["meta"]["table_segments"]["currentTab"] + 0;
				}
				if(isset($oldData["meta"]["table_segments"]["totalTabs"])){
					 $this->totalTabs = $oldData["meta"]["table_segments"]["totalTabs"] + 0;
				}
				$this->purgeTableRecords();				
				foreach($oldData["records"] as $uuid => $record){
					 $this->insertTabRecord($uuid); //save the association between a UUID and a tableID
				}
		  }
	 }
	 
	 
	 
	 //get some of the old metadata put into new
	 function processOldMetadata(){
		  
		  $tablePubObj = $this->tablePubObj;
		  $oldTableData = $this->oldTableData;
		  
		  $tablePubObj->tableName = $oldTableData["meta"]["table_name"];
		  $tablePubObj->tableDesciption = $oldTableData["meta"]["table_description"];
		  $tablePubObj->recordCount = $oldTableData["meta"]["numFound"];
		  
		  if(strlen($oldTableData["meta"]["tagstring"])>0){
				if(stristr($oldTableData["meta"]["tagstring"], " ")){
					 $tags = explode(" ", $oldTableData["meta"]["tagstring"]);
				}
				else{
					 $tags = array($oldTableData["meta"]["tagstring"]);
				}
				$tablePubObj->tableTags = $tags;
		  }
		  
		  if(isset($oldTableData["meta"]["table_segments"]["currentTab"])){
				$this->tablePage = $oldTableData["meta"]["table_segments"]["currentTab"] + 0;
		  }
		  if(isset($oldTableData["table_segments"]["totalTabs"])){
				$this->totalTabs = $oldTableData["meta"]["table_segments"]["totalTabs"] + 0;
		  }
		  $this->tablePubObj = $tablePubObj;
	 }
	 
	 
	 //trim the sample records for display
	 function truncateOldRecords(){
		  if($this->oldTableData){
				$oldData = $this->oldTableData;
				$sampleList = array();
				$i = 0;
				foreach($oldData["records"] as $uuidKey => $record){
					 $sampleList[$uuidKey] = $record;
					 if($i >= self::defaultSample){
						  break;
					 }
					 $i++;
				}
				unset($oldData["records"]);
				$oldData["records"] = $sampleList;
				$this->oldTableData = $oldData;
		  }
	 }
	 
	 
	 //add links to context and projects, modify field names
	 function modFieldsAddURIs(){
		  
		  $db = $this->startDB();
		  if($this->oldTableData){
				
				$this->purgeTableRecords(); //delete the old associations between UUIDs and this tableID.
				
				$this->checkLinkedTypeData();
				$linkedVarTypeFields = $this->linkedVarTypeFields;
				$itemLinkedData = $this->itemLinkedData;
				
				$oldData = $this->oldTableData;
				$newRecords = array();
				foreach($oldData["records"] as $uuid => $record){
					 $newRecord = array();
					 $contextFound = false;
					 $labelFound = false;
					 foreach($record as $fieldKey => $value){
						  //echo " $fieldKey ";
						  if($fieldKey == "proj"){
								$uri = $this->getProjURIfromName($value);
								$newRecord["Project URI"] = $uri;
								$fieldKey = "Project name";
						  }
						  elseif(stristr($fieldKey, "def_context")){
								$contextFound = true;
								$numContext = str_replace("def_context_", "", $fieldKey) + 1;
								$fieldKey = "Context ($numContext)";
						  }
						  elseif(!stristr($fieldKey, "def_context") && $contextFound){
								$contextFound = false;
								$newRecord["Context URI"] = $this->getParentURI($uuid);
						  }
						  elseif($fieldKey == "person"){
								$fieldKey = "Related person(s)";
						  }
						  elseif($fieldKey == "pub_date"){
								$fieldKey = "Publication date";
						  }
						  elseif($fieldKey == "update"){
								$fieldKey = "Last updated";
						  }
						  
						  if($fieldKey == "pub_date"){
								$fieldKey = "Publication date";
						  }
						  elseif($fieldKey == "category"){
								$fieldKey = "Category";
						  }
						  elseif($fieldKey == "label"){
								$fieldKey = "Item label";
								$labelFound = true;
						  }
						  elseif($fieldKey != "label" && $labelFound){
								$labelFound = false;
								
								if(is_array($linkedVarTypeFields)){
									 foreach($linkedVarTypeFields as $varKey => $varArray){
										  $LDnum = 1;
										  foreach($varArray as $varLDNum => $varLDLabel){
												
												$varURI_field = $varLDLabel." [URI]";
												$varLabel_field = $varLDLabel." [Label]";
												if($LDnum > 1){
													 $varURI_field .= " ($LDnum)";
													 $varLabel_field .= " ($LDnum)";
												}
												
												$ldValueURI = "";
												$ldValueLabel = "";
												if(isset($itemLinkedData[$uuid][$varKey][$varLDNum])){
													 //$ldValueURI = $itemLinkedData[$uuid][$varKey][$varLDNum]["propLinkedURI"]." ".$itemLinkedData[$uuid][$varKey][$varLDNum]["propUUID"];
													 $ldValueURI = $itemLinkedData[$uuid][$varKey][$varLDNum]["propLinkedURI"];
													 $ldValueLabel = $itemLinkedData[$uuid][$varKey][$varLDNum]["propLinkedLabel"];
												}
												
												$newRecord[$varURI_field] = $ldValueURI;
												$newRecord[$varLabel_field] = $ldValueLabel;
												
												$LDnum++;
										  }//end loop through linking fields
									 }//end loop through linked type data
								}//end case with linked type data
						  }
						  
						  
						  $newRecord[$fieldKey] = $value;
					 }
					 $this->getLinkedTypes($uuid);
					 $newRecords[$uuid] = $newRecord;
					 $this->insertTabRecord($uuid); //save the association between a UUID and a tableID
				}//end loop
				$oldData["records"] = $newRecords;
				$this->oldTableData = $oldData;
		  }
		  
	 }
	 
	 
	 //delete associations between uuids and a table ID. 
	 function purgeTableRecords(){
		  
		  if($this->oldTableID){
				$db = $this->startDB();
				$where =  "tableID = '".$this->oldTableID."' AND page = ".$this->tablePage;
				$db->delete("export_tabs_records", $where );
		  }
	 }
	 
	 
	 //add record of UUID's association to a table
	 function insertTabRecord($uuid){
		  if($this->oldTableID){
				$db = $this->startDB();
				
				$data = array("hashID" => md5($uuid."_".$this->oldTableID),
								  "uuid" => $uuid,
								  "tableID" => $this->oldTableID,
								  "page" => $this->tablePage
								  );
				
				try{
					 $db->insert("export_tabs_records", $data);
				}
				catch (Exception $e)  {
					 
				}
		  }
	 }
	 
	 
	 //get UUID from project name
	 function getProjURIfromName($projectName){
		  $uri = false;
		  $tempProjects = $this->tempProjects;
		  if(array_key_exists($projectName, $tempProjects )){
				$uuid = $tempProjects[$projectName];
				$uri = self::projectBaseURI.$uuid;
		  }
		  return $uri;
	 }
	 
	 
	 //get parent context URI
	 function getParentURI($uuid){
		  $uri = false;
		  $db = $this->startDB();
		  $sql = "SELECT parent_uuid FROM space_contain WHERE child_uuid = '$uuid' LIMIT 1; ";
		  $result = $db->fetchAll($sql);
		  if($result){
				$uuid = $result[0]["parent_uuid"];
				$uri = self::subjectBaseURI.$uuid;
		  }
		  return $uri;
	 }
	 
	 
	 //loop through the records, check for items with type linked data 
	 function checkLinkedTypeData(){
		  
		  if($this->oldTableData){
				$itemLinkedData = array();
				$oldData = $this->oldTableData;
				foreach($oldData["records"] as $uuid => $record){
					 $itemLinkedData[$uuid] = $this->getLinkedTypes($uuid);
				}
				$this->itemLinkedData = $itemLinkedData;
		  }
	 }
	 
	 
	 //get linked data (types)
	 function getLinkedTypes($uuid){
		  
		  $db = $this->startDB();
		  
		  $linkedVarTypeFields = $this->linkedVarTypeFields;
		  if(!is_array($linkedVarTypeFields)){
				$linkedVarTypeFields = array();
		  }
		  
		  $output = array();
		  
		  $sql = "SELECT properties.variable_uuid, linked_data.linkedLabel, linked_data.linkedURI
		  FROM observe
		  JOIN properties ON observe.property_uuid = properties.property_uuid
		  JOIN linked_data ON (linked_data.itemUUID = properties.variable_uuid
				AND linked_data.linkedType = 'type')
		  WHERE observe.subject_uuid = '$uuid'
		  ";
		  
		  $result = $db->fetchAll($sql);
		  if($result){
				foreach($result AS $row){
					 $varUUID = $row["variable_uuid"];
					 $varLinkedLabel = $row["linkedLabel"];
					 $varLinkedURI = $row["linkedURI"];
					 
					 $sql = "SELECT properties.property_uuid, linked_data.linkedLabel, linked_data.linkedURI
					 FROM observe
					 JOIN properties ON observe.property_uuid = properties.property_uuid
					 JOIN linked_data ON (linked_data.itemUUID = properties.property_uuid)
					 WHERE observe.subject_uuid = '$uuid' AND properties.variable_uuid = '$varUUID'
					 ";
					 
					 $result = $db->fetchAll($sql);
					 if($result){
						  $prop = 0;
						  foreach($result AS $row){
								$propUUID = $row["property_uuid"];
								$propLinkedLabel = $row["linkedLabel"];
								$propLinkedURI = $row["linkedURI"];
								
								if(!array_key_exists($varLinkedURI, $linkedVarTypeFields)){
									 $linkedVarTypeFields[$varLinkedURI][$prop] = $varLinkedLabel;
								}
								else{
									 if(!array_key_exists($prop, $linkedVarTypeFields[$varLinkedURI])){
										  $linkedVarTypeFields[$varLinkedURI][$prop] = $varLinkedLabel;
									 }
								}
								
								$output[$varLinkedURI][$prop] = array(
														  "propUUID" => $propUUID,
														  "varLinkedURI" => $varLinkedURI,
														  "varLinkedLabel" => $varLinkedLabel,
														  "propLinkedURI" => $propLinkedURI,
														  "propLinkedLabel" => $propLinkedLabel
														);
								$prop++;
						  }
					 }
				}
		  }//end case with linked data type
		  
		  $this->linkedVarTypeFields = $linkedVarTypeFields;
		  return $output;
	 }//end functions
	 
	 
	 
	 //get a list of the projects
	 function processProjects(){
		  
		  $db = $this->startDB();
		  $projects = false;
		  if($this->oldTableData){
				$oldData = $this->oldTableData;
				$rawProjects = array();
				foreach($oldData["records"] as $uuid => $record){
					 if(isset($record["proj"])){
						  $actProject = $record["proj"];
						  if(!array_key_exists($actProject, $rawProjects)){
								$rawProjects[$actProject] = array("name" => $actProject, "count" => 1);
						  }
						  else{
								$rawProjects[$actProject]["count"] = $rawProjects[$actProject]["count"] + 1; 
						  }
					 }
				}
				
				$projects = array();
				$tempProjects = array();
				foreach($rawProjects as $nameKey => $projArray){
					
					 $sql = "SELECT project_id
					 FROM pubprojects
					 WHERE proj_name = '$nameKey'
					 LIMIT 1;
					 ";
					 
					 $result = $db->fetchAll($sql);
					 if($result){
						  $uuid = $result[0]["project_id"];
						  $uri = self::projectBaseURI.$uuid;
						  $projects[$uri] = $projArray;
						  $tempProjects[$nameKey] = $uuid;
					 } 
				}
				
				$this->tempProjects = $tempProjects;
		  }
		  
		  if($this->tablePubObj){
				$tablePubObj = $this->tablePubObj;
				$tablePubObj->projects = $projects;
				$this->tablePubObj = $tablePubObj;
		  }
		  
		  return $projects;
	 }//end function
	 
	 
	 
	 
	 //get the person by their project affiliation
	 function processPeople(){
		  
		  $db = $this->startDB();
		  
		  if($this->oldTableData){
				$tablePubObj = $this->tablePubObj;
				$tempProjects = $this->tempProjects;
				$oldData = $this->oldTableData;
				$persons = array();
				$creators = array();
				$contributors = array();
				
				$linksObj = new dbXML_dbLinks;
				$creatorRels = $linksObj->relToCreator;
				$contribRels = $linksObj->relToContributor;
				
				foreach($oldData["records"] as $uuid => $record){
					
					 $sql = "SELECT persons.project_id, links.targ_uuid, links.link_type,
						  persons.combined_name, persons.last_name, persons.first_name, persons.mid_init
						  FROM links
						  JOIN persons ON persons.uuid = links.targ_uuid
						  WHERE links.targ_type LIKE '%person%' AND links.origin_uuid = '$uuid' ;
						  ";
					 
					 $result = $db->fetchAll($sql);
					 if($result){
						  $tempPersons = array();
						  foreach($result as $row){
								$ukey = md5($uuid.$row["targ_uuid"].$row["link_type"]);
								$tempPersons[$ukey] = $row;
						  }
						  
						  foreach($tempPersons as $row){
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
								
						  }//end loop through de-duped persons associated with an item
						  
					 }//end case with persons associated with an item
					 else{
						  $client = new Zend_Http_Client();
						  $client->setUri("http://penelope.oc/zoo/add-space-hierarchy?root=".$uuid);
						  $client->setConfig(array(
								'maxredirects' => 0,
								'timeout'      => 360));
						  $response = $client->request('GET');
					 }
					 
					 
				}//end loop through all records
				
				$tablePubObj->creators = $creators;
				$tablePubObj->contributors = $contributors;
				$tablePubObj->linkedPersons = $persons;
				$this->tablePubObj = $tablePubObj;
				
		  }//end case where there's an array of old metadata
		  
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
