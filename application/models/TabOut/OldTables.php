<?php

class TabOut_OldTables  {
	 
	 public $db;
	 
	 public $JSONpage = 1;
	 
	 public $rawTableURIs;
	 
	 public $URIstartTableJSON; //start point (end point in SOA world) for getting JSON list of tables
	 
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
	 
	 
	 
	 function saveTableRecords(){
		  
		  
		  
	 }
	 
	 
	 
	 
	 
	 
	 
	 
	 //recursive function to get table JSON, save all the records for a table, and do it for all the segments
	 function processTableURL($tableURL){
		  
		  $tableOld = new TabOut_UpdateOld;
		  $tableOld->oldURI = $tableURL;
		  $tableOld->getParseJSON();
		  $tableOld->processOldRecords();
		  if($tableOld->tablePage < $tableOld->totalTabs){
				$newSegmentURL = $tableURL."/". $tableOld->tablePage + 1;
				$this->processTableURL($newSegmentURL);
		  }
	 }
	 
	 
	 
	 
	 
	 
	 
	 

	 function getTableJSONlist($JSONuri){
		  $output = false;
		  
		  $client = new Zend_Http_Client();
		  $client->setUri($this->oldURI);
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

		  return $output
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
