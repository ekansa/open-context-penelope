<?php
/*
This class process a work book full of measurements (as saved in a "fods" xml format from Libre Office)
It's specifically designed to parse through the Catalhoyuk type schema for describing measurements

I'm including it, since it may be useful to adapt to other projects

*/
class ProjEdits_Periodo  {
    
	 public $db;
	 public $projectUUID;
	 
	 
	 const GeoUsername = "ekansa"; //geonames API name
	 const APIsleep = .5; //
	 const GeoNamesBaseURI = "http://www.geonames.org/";
	 const GeoNamesBaseAPI = "http://api.geonames.org/";
	 
	 function countries(){
		 
		  $output = array();
		  $db = $this->startDB();
		  
		  $sql = "SELECT DISTINCT country_code
		  FROM z_pleiades
		  WHERE country_code != ''
		  AND country_uri = ''
		  ";
		  
		  $result = $db->fetchAll($sql, 2);
		  foreach($result as $row){
			   $code = $row['country_code'];
			   $where = array();
			   $where[] = 'country_code = "'.$code.'"';
			   $apiURL = self::GeoNamesBaseAPI.'/countryInfoJSON?country='.$code.'&username='.self::GeoUsername;
			   @$json = file_get_contents($apiURL);
			   if($json != false){
					$jdata = json_decode($json, 1);
					if(is_array($jdata)){
						 if(isset($jdata["geonames"])){
							  foreach($jdata["geonames"] as $rec){
								   if(isset($rec["geonameId"])){
										 $data = array('country_uri' => "http://www.geonames.org/".$rec["geonameId"]);
										 $db->update('z_pleiades', $data, $where);
										 $output[$code] =  $data;
										 break;
								   }
							  }
						 }
					}
			   }
			   sleep(self::APIsleep);
		  }
		  
		  return $output;
	 }
	 
	 
	 
	 function periodCountries(){
		 
		  $output = array();
		  $db = $this->startDB();
		  
		  $sql = "SELECT DISTINCT lat, lon
		  FROM z_pleiades
		  WHERE country_code = ''
		  ORDER BY lat, lon
		  ";
		  
		  $result = $db->fetchAll($sql, 2);
		  foreach($result as $row){
			   $lat = $row['lat'];
			   $lon = $row['lon'];
			   $where = array();
			   $where[] = 'lat = '.$lat;
			   $where[] = 'lon = '.$lon;
			   $apiURL = self::GeoNamesBaseAPI.'/countrySubdivisionJSON?lat='.$lat.'&lng='.$lon.'&username='.self::GeoUsername;
			   @$json = file_get_contents($apiURL);
			   if($json != false){
					$jdata = json_decode($json, 1);
					if(isset($jdata["countryName"]) && isset($jdata["countryCode"])){
						 $data = array('country_label' => $jdata["countryName"],
									   'country_code' => $jdata["countryCode"]);
						 $db->update('z_pleiades', $data, $where);
						 $output[$lat.", ".$lon] = $jdata["countryName"];
					}
			   }
			   sleep(self::APIsleep);
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
