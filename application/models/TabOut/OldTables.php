<?php

class TabOut_OldTables  {
	 
	 public $db;
	 
	 public $JSONpage = 1;
	 
	 public $rawTableURIs;
	 
	 public $URIstartTableJSON; //start point (end point in SOA world) for getting JSON list of tables
	 
	 //master function to save all table record associations
	 function saveAllTableRecordAssociations(){
		  $this->pageThroughJSONlist(); //page through search JSON to get list of table URLs
		  $this->saveTableRecords(); //get JSON for each table (and table segments) to save record associations
	 }
	 
	 public $doneList; //list of processed URIs
	 
	 public $segmentCount;
	 
	 function pageThroughJSONlist(){
		  
		  if($this->URIstartTableJSON){
				$continue = true;
				$JSONuri = $this->URIstartTableJSON;
				while($continue){
					 $listData = $this->getTableJSONlist($JSONuri);
					 if(!$listData){
						  $continue = false;
					 }
					 else{
						  
						  $this->getRawTableURIs($listData); //extract URLs for tables from this array
						  
						  if($listData["paging"]["next"] != false){
								$JSONuri = $listData["paging"]["next"]; //there's a next page
						  }
						  else{
								$continue = false; //no more pages to loop through
						  }
						  
						  sleep(1);
						  //$continue = false; //no more pages to loop through
					 }
				}//end loop through lists
				
		  }
		  
	 }
	 
	 // make array of table URLs
	 function getRawTableURIs($listData){
		  $rawTableURIs = $this->rawTableURIs;
		  if(!is_array($rawTableURIs)){
				$rawTableURIs = array();
		  }
		  
		  foreach($listData["results"] as $tabData){
				$url =  $tabData["href"];
				if(!in_array($url, $rawTableURIs)){
					 $rawTableURIs[] =$url;
				}
		  }
		  
		  $this->rawTableURIs = $rawTableURIs;
	 }
	 
	 
	 //loop through all the table URIs save assocations between their records and the table IDs to the database
	 function saveTableRecords(){
		  if(is_array($this->rawTableURIs)){
				foreach($this->rawTableURIs as $tableURL){
					 $this->segmentCount = 1;
					 $this->processTableURL($tableURL, 1);
				}
		  }
		  
	 }
	 
	 //recursive function to get table JSON, save all the records for a table, and do it for all the segments
	 function processTableURL($tableURL, $segmentPage){
		  
		  $doneList = $this->doneList;
		  if(!is_array($doneList)){
				$doneList = array();
		  }
		  
		  if($segmentPage > 1){
				$useTableURL = $tableURL."/".$segmentPage;
		  }
		  else{
				$useTableURL = $tableURL;
		  }
		  
		  sleep(1);
		  $tableOld = new TabOut_UpdateOld;
		  $tableOld->oldURI = $useTableURL ;
		  $tableOld->tableIDfromURI();
		  
		  if($this->checkNeedsSaving($tableOld->oldTableID, $tableOld->tablePage)){
				
				if($tableOld->getParseJSON()){
					 $tableOld->processOldRecords();
					 
					 $doneList[$tableURL][] = $tableOld->tablePage;
					 $this->doneList = $doneList; //save the fact that you did this
					 
					 unset($tableOld);
					 if($this->segmentCount < 12 && $segmentPage < 12){
						  $this->segmentCount = $this->segmentCount +  1;
						  $this->processTableURL($tableURL , $segmentPage + 1);
					 }
				}
		  }
		 
		  unset($tableOld);
		 
	 }
	 
	 
	 function checkNeedsSaving($tableID, $page){
		  $db = $this->startDB();
		  $sql = "SELECT * FROM export_tabs_records WHERE tableID = '$tableID' AND page = ".$page." LIMIT 1;";
		 
		  $result = $db->fetchAll($sql);
		  if($result){
				return false; // does NOT need saving
		  }
		  else{
				return true; //needs saving
		  }
	 }
	 


	 function getTableJSONlist($JSONuri){
		  $output = false;
		  
		  $client = new Zend_Http_Client();
		  $client->setUri($JSONuri);
		  $client->setConfig(array(
				'maxredirects' => 0,
				'timeout'      => 280));
		  $response = $client->request('GET');
		  if($response){
				$JSON = $response->getBody();
				$output = Zend_Json::decode($JSON);
				if(!is_array($output)){
					 $output = false;
				}
		  }

		  return $output;
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
