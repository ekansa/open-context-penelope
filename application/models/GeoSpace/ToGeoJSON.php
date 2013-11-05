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

	 
	 //gets the centroid (center point of a GeoJSON polygon);
	 function GeoJSONcentroid($geoJSON){
		  $output = false;
		  if(!is_array($geoJSON)){
				$geoJSON = Zend_Json::decode($geoJSON);
		  }
		  
		  if(isset($geoJSON["geometry"]["coordinates"])){
				bcscale(30);
				$lats = 0;
				$lons = 0;
				$i = 0;
				foreach($geoJSON["geometry"]["coordinates"] as $coorsArray){
					 $center = $this->centroid($coorsArray);
					 $lats = bcadd($lats, $center[1]);
					 $lons = bcadd($lons, $center[0]);
					 $i++;
				}
				
				if($i>0){
					 $output = array("latitude" => bcdiv($lats, $i), "longitude" => bcdiv($lons, $i) );
				}
		  }
		  
		  return $output;
	 }
	 
	 
	 function simplePolygonCentroid($points){
		  $sumY = 0;
		  $sumX = 0;
		  $partialSum = 0;
		  $sum = 0;
    
		  $n = count($points);
		  
		  //close polygon
		  $points[] = $points[0];
    
		  for ($i=0; $i<$n; $i++) {
				$partialSum = $points[$i][0] * $points[$i+1][1] - $points[$i+1][0]*$points[$i][1];
				$sum += $partialSum;
				$sumX += ($points[$i][0] + $points[$i+1][0]) * $partialSum;
				$sumY += ($points[$i][1] + $points[$i+1][1]) * $partialSum;
		  }
		  
		  $area = 0.5*$sum;
		  
		  return array($sumY/(6/$area),$sumX/(6/$area));
	 }
	 
	 
	 function centroid($polygon) {
		  bcscale(200);
		  $n = count($polygon);
		  
		  $polygon[] = $polygon[0]; 
		  $a = $this->area($polygon, $n);
	  
	  
		  $cx = 0;
		  $cy = 0;
		  
		  //$polygon = array_chunk($polygon,2);
		  
		  for ($i=0; $i<$n; $i++) {
				$j = ($i + 1);
				$newX = bcmul(bcadd($polygon[$i][0],$polygon[$j][0]), bcsub(bcmul($polygon[$i][0],$polygon[$j][1]), bcmul($polygon[$j][0],$polygon[$i][1])));
				$newY = bcmul(bcadd($polygon[$i][1],$polygon[$j][1]), bcsub(bcmul($polygon[$i][0],$polygon[$j][1]), bcmul($polygon[$j][0],$polygon[$i][1])));
				
				$cx = bcadd($cx, $newX);
				$cy = bcadd($cy, $newY);
				//$cx += ($polygon[$i][0] + $polygon[$j][0]) * ( ($polygon[$i][0]*$polygon[$j][1]) - ($polygon[$j][0]*$polygon[$i][1]) );
				//$cy += ($polygon[$i][1] + $polygon[$j][1]) * ( ($polygon[$i][0]*$polygon[$j][1]) - ($polygon[$j][0]*$polygon[$i][1]) );
		  }

		  $xOut = bcmul($cx, (1/(6*$a))); 
		  $yOut = bcmul($cy, (1/(6*$a)));
		  
		  return array($xOut, $yOut);
		  
		  //return(array( (1/(6*$a))*$cx,(1/(6*$a))*$cy));
		  
	 }
	 
	 function area($polygon,$n) {
		  bcscale(200); //very high precision math!
		  $area = 0;
		  
		  for ($i=0;$i<$n;$i++) {
				$j = ($i + 1);
				//$area += $polygon[$i][0] * $polygon[$j][1];
				//$area -= $polygon[$i][1] * $polygon[$j][0];
				$area = bcadd($area, bcmul($polygon[$i][0], $polygon[$j][1]));
				$area = bcsub($area, bcmul($polygon[$i][1], $polygon[$j][0]));
				
		  }
		  //$area /= 2;
		  $area = bcmul( $area , .5);
		  return(abs($area));
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
