<?php
/*
This class process a work book full of measurements (as saved in a "fods" xml format from Libre Office)
It's specifically designed to parse through the Catalhoyuk type schema for describing measurements

I'm including it, since it may be useful to adapt to other projects

*/
class ProjEdits_Dinaa  {
    
	 public $db;
	 public $projectUUID;
	 
	
	 function georgiaGeo(){
		  
		  $output = array();
		  $db = $this->startDB();
		  $sql = "SELECT * FROM z_georgia_geo
		  WHERE 1
		  GROUP BY site
		  ";
		  
		  $result = $db->fetchAll($sql, 2);
        if($result){
				foreach($result as $row){
					 
					 $site = trim($row["site"]);
					 $uuid = $this->getSiteUUID($site);
					 if($uuid != false){
						  $lat = $row["latitude"];
						  $lon = $row["longitude"];
						  $data = array("uuid" => $uuid,
											 "project_id" => $this->projectUUID,
											 "source_id" => "geo-tile approx",
											 "latitude" => $lat,
											 "longitude" => $lon,
											 "specificity" => -11,
											 "note" => "Approximated by geotile to zoom level 11"
											 );
						  
						  try{
								$db->insert("geo_space", $data);
								//$output[$site][] = "Geo $uuid added"; 
						  }
						  catch(Exception $e){
								$output[$site][] = "Geo for $uuid already in"; 
						  }
						  
					 }
					 else{
						  $output[$site] = "Error! No UUID";
					 }
					 
					 
				}
		  
		  
		  }
		  
		  return $output;
	 }
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	  //get links to uuid 
	 function getSiteUUID($site){
		  $output = false;
		  $db = $this->startDB();
		  
		  $sql = "SELECT uuid, project_id
		  FROM space
		  WHERE space_label = '$site'
		  AND project_id = '".$this->projectUUID."'
		  LIMIT 1;
		  ";
		  
		  $result = $db->fetchAll($sql, 2);
        if($result){
				$output =  $result[0]["uuid"];
		  }
		  else{
				
		  }
		  return $output;
	 }
	
	
	
	 function getIndianaArtifacts($nGramCount = 2){
		  
		  $db = $this->startDB();
		  
		  $sql = "SELECT *
		  FROM z_indiana_artifacts
		  WHERE 1
		  ";
		  
		  $allTokens = array();
		  $allNgrams = array();
		  $result = $db->fetchAll($sql, 2);
        if($result){
				foreach($result as $row){
					 $rawString = $row["Artifacts"];
					 $string = preg_replace("/[^A-Za-z ]/", '', $rawString);
					 $stringEx = explode(" ", $string);
					 $nGramArray = array();
					 foreach($stringEx as $token){
						  $token = trim($token);
						  if(strlen($token)>2){
								$sql = "SELECT * FROM z_stop_words WHERE word LIKE '$token' LIMIT 1;";
								$resB = $db->fetchAll($sql, 2);
								if(!$resB){
									 
									 if((strtoupper($token) != $token) || strlen($token)>=5){
										  $token = strtolower($token);
									 }
									 
									 if(array_key_exists($token, $allTokens)){
										  $allTokens[$token] = $allTokens[$token] + 1;
									 }
									 else{
										  $allTokens[$token] = 1;
									 }
									 
									 if(is_array($nGramArray)){
										  $nGramArray[] = $token;
										  $nGramArrayCount = count($nGramArray);
										  if($nGramArrayCount >= $nGramCount){
												$actNgram = implode(" ", $nGramArray);
												if(array_key_exists($actNgram, $allNgrams)){
													 $allNgrams[$actNgram] = $allNgrams[$actNgram] + 1;
												}
												else{
													 $allNgrams[$actNgram] = 1;
												}
												$newNgramArray = array();
												$firstNgramLoop = true;
												foreach($nGramArray as $ng){
													 if(!$firstNgramLoop){
														  $newNgramArray[] = $ng;
													 }
													 $firstNgramLoop = false;
												}
												unset($nGramArray);
												$nGramArray = $newNgramArray;
												unset($newNgramArray);
										  }
									 }
									 else{
										  $nGramArray= array();
										  $nGramArray[] = $token;
									 }
									 
								}
						  }
					 }
					
				}
		  }

		  arsort($allTokens);
		  arsort($allNgrams);
		  return array("single" => $allTokens, $nGramCount."grams" => $allNgrams) ;
	 }
	
	
	 //get links to child items
	 function getChildItems($parentUUID){
		  $db = $this->startDB();
		  
		  $sql = "SELECT DISTINCT space_contain.child_uuid
		  FROM space_contain
		  WHERE space_contain.parent_uuid = '$parentUUID'
		  ";
		  
		  $output = array();
		  $result = $db->fetchAll($sql, 2);
        if($result){
				foreach($result as $row){
					 $output[] = $row["child_uuid"];
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
