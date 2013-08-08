<?php

class GeoSpace_ToGeoJSON  {

	 public $db; //database object
	 
	 
	 //finds KML in teh geodata geojson field and converts it to real geojson
	 function convertKMLtoGeoJSON(){
		  
		  $db = $this->startDB();
		  $sql = "SELECT * FROM geodata WHERE geoJSON LIKE '%<coordinates>%' "; 
		  $result =  $db->fetchAll($sql);
		  $output = array();
		  foreach($result as $row){
				$text = $row["geoJSON"];
				$jsonString = $this->kml_to_geojson($text);
				$geoJSON = $this->package_GeoJSON($jsonString);
				if($geoJSON){
					 $data = array();
					 $data["geoJSON"] = Zend_Json::encode($geoJSON);
					 $where = "uuid = '".$row["uuid"]."' ";
					 $db->update("geodata", $data, $where);
				}
				$output[] = $geoJSON;
		  }
		  
		  return $output;
	 }
	 
	 //package the GeoJSON into a geometry
	 function package_GeoJSON($jsonString){
		  
		  $geoJSONfrag = Zend_Json::decode($jsonString);
		  if(is_array($geoJSONfrag)){
				$output = array();
				$output["geometry"] = $geoJSONfrag;
		  }
		  else{
				$output = false;
		  }
		  
		  return $output;
	 }
	 
	 
	 //does the actual work of converting KML to GeoJSON, note the class dependency
	 function kml_to_geojson ($text) {
		  $decoder = new gisconverter\KML();
		  return $decoder->geomFromText($text)->toGeoJSON();
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
