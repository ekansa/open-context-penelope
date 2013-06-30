<?php
/*
This class edits Murlo data

I'm including it, since it may be useful to adapt to other projects

*/
class ProjEdits_Murlo  {
    
   
	
    public $db;
	 
	 
	 //get the UUID for an artifact numeric label
	 function getPCuuid($numericLabel){
		  $db = $this->startDB();
		  $output = false;
		  $label = "PC ".$numericLabel;
		  $sql = "SELECT uuid FROM space WHERE space_label = '$label' LIMIT 1;";
		  $result =  $db->fetchAll($sql);
		  if($result){
				$output = $result[0]["uuid"];
		  }
		  return $output;
	 }
	 
	 
	 function geoJsonAdd($jsonFile){
		  
		  $output = false;
		  $db = $this->startDB
		  $flipLatLon = true;
		  $geoJSON = false;
		  if($jsonFile != false){
				@$jsonString = file_get_contents($jsonFile);
				if($jsonString != false){
					 @$geoJSON = Zend_Json::decode($jsonString);
					 if(is_array($geoJSON)){
						  
						  $idFeatures = array();
						  $missingArray = array();
						  foreach($geoJSON["features"] as $feature){
								
								$rawTrench = round($feature["properties"]["TrenchID"],0);
								$trenchID = "Tr-ID ".$rawTrench;
								$sql = "SELECT uuid, project_id FROM space WHERE space_label = '$trenchID' LIMIT 1;";
								//echo "<br/>$sql<br/>";
								$results =  $db->fetchAll($sql);
								if($results){
									 $spaceUUID = $results[0]["uuid"];
									 $projectID = $results[0]["project_id"];
									 //echo "<br/>$trenchID is $spaceUUID ";
									 $idFeatures["found"][$spaceUUID]["label"] = $trenchID;
									 $idFeatures["found"][$spaceUUID]["project_id"] = $projectID;
									 
									 
									 //fix reversed coordinates. lon needs to be before lat
									 if($flipLatLon){
										  $newGeo = $feature["geometry"];
										  unset($newGeo["coordinates"]);
										  
										  $newPolyCoords = array();
										  foreach($feature["geometry"]["coordinates"] as $polyCoordinates){
												$newCoordnates = array();
												foreach($polyCoordinates as $coordinates){
													 if(!is_array($coordinates[0])){
														  //see geoJSON spec, lon in first position: http://www.geojson.org/geojson-spec.html#positions
														  $newCoordinate = array($coordinates[1], $coordinates[0]);
														  $newCoordnates[] = $newCoordinate;
													 }
													 else{
														  $newSubCoordnates = array();
														  foreach($coordinates as $actcoords){
																//see geoJSON spec, lon in first position: http://www.geojson.org/geojson-spec.html#positions
																$newCoordinate = array($actcoords[1], $actcoords[0]);
																$newSubCoordnates[] =  $newCoordinate;
														  }
														  $newCoordnates[] = $newSubCoordnates; 
													 }
													 
												}
												$newPolyCoords[] = $newCoordnates;
										  }
										  $feature["geometry"]["coordinates"] = $newPolyCoords;
									 }
									 
									 $idFeatures["found"][$spaceUUID]["features"][]["geometry"] = $feature["geometry"];
									 
									 $sumLat = 0;
									 $sumLon = 0;
									 $coordCount = 0;
									 foreach($idFeatures["found"][$spaceUUID]["features"] as $geometries){
										  foreach($geometries["geometry"]["coordinates"] as $polyCoordinates){
												foreach($polyCoordinates as $coordinates){
													 if(!is_array($coordinates[0])){
														  $coordCount++;
														  $sumLon = $sumLon  + $coordinates[0]; //see geoJSON spec, lon in first position: http://www.geojson.org/geojson-spec.html#positions
														  $sumLat = $sumLat + $coordinates[1]; //see geoJSON spec, lat in second position: http://www.geojson.org/geojson-spec.html#positions
													 }
													 else{
														  foreach($coordinates as $actcoords){
																$coordCount++;
																$sumLon = $sumLon  + $actcoords[0]; //see geoJSON spec, lon in first position: http://www.geojson.org/geojson-spec.html#positions
																$sumLat = $sumLat + $actcoords[1]; //see geoJSON spec, lat in second position: http://www.geojson.org/geojson-spec.html#positions
														  }
													 }
												}
										  }
									 }
									 
									 if($coordCount > 0){
										  $idFeatures["found"][$spaceUUID]["meanLon"] = $sumLon / $coordCount;
										  $idFeatures["found"][$spaceUUID]["meanLat"] = $sumLat / $coordCount;
									 }
									 else{
										  $idFeatures["found"][$spaceUUID]["meanLon"] = false;
										  $idFeatures["found"][$spaceUUID]["meanLat"] = false;
									 }
									 
								}
								else{
									 //echo "<h4>$trenchID has no UUID </h4>";
									 $missingArray[] =  $trenchID;;
								}
						  }
						  $idFeatures["missing"] = $missingArray;
						  
						  
						  //convert multiple geographic features into a multipolygon
						  $fixFound = array();
						  foreach($idFeatures["found"] as $spaceUUID => $geoArray){
								$newGeoArray = $geoArray;
								unset($newGeoArray["features"]);
								if(count($geoArray["features"])>1){
									 
									 $allPolygons = array();
									 foreach($geoArray["features"] as $geometries){
										  $newPolygon = array();
										  foreach($feature["geometry"]["coordinates"] as $polyCoordinates){
												$newRing = array();
												foreach($polyCoordinates as $coordinates){
													 if(!is_array($coordinates[0])){
														  $newRing[] = $coordinates;
													 }
													 else{
														  $newSubRing = array();
														  foreach($coordinates as $actcoords){
																$newSubRing[] = $actcoords;
														  }
														  $newRing = array_merge($newRing, $newSubRing);
													 }
												}
												$newPolygon[] = $newRing;
										  }
										  $allPolygons[] = $newPolygon;
									 }
									 unset($geoArray["features"][0]["geometry"]["coordinates"]);
									 $newGeoArray["features"]["geometry"] = $geoArray["features"][0]["geometry"];
									 $newGeoArray["features"]["geometry"]["type"] = "MultiPolygon";
									 $newGeoArray["features"]["geometry"]["coordinates"] = $allPolygons;
								}
								else{
									 $newGeoArray["features"]["geometry"] = $geoArray["features"][0]["geometry"];
								}
								
								$fixFound[$spaceUUID] = $newGeoArray;
						  }
						  $idFeatures["found"] = $fixFound;
						  $childArray = array();
						  foreach($idFeatures["found"] as $spaceUUID => $geoArray){
								$childArray[] = "space_contain.child_uuid = '$spaceUUID' ";
								
								$data = array("uuid" => $spaceUUID,
												  "project_id" => $geoArray["project_id"],
												  "source_id" => "JSONfile",
												  "latitude" => $geoArray["meanLat"],
												  "longitude" => $geoArray["meanLon"],
												  "geojson_data"	=> Zend_Json::encode($geoArray["features"])
												  );
								
								$where = "uuid = '$spaceUUID' ";
								$db->delete("geo_space", $where);
								$db->insert("geo_space", $data);
						  }
						  
						  $topTree = false;
						  $level = 0;
						  while(!$topTree){
								$qTerms = implode(" OR ", $childArray);
								$sql = "SELECT AVG(geo_space.latitude) as meanLat,
										  AVG(geo_space.longitude) as meanLon, space_contain.parent_uuid
										  FROM geo_space
										  JOIN space_contain ON space_contain.child_uuid = geo_space.uuid
										  WHERE $qTerms
										  GROUP BY space_contain.parent_uuid
										  ";
								$results =  $db->fetchAll($sql);
								if($results){
									 $level++;
									 unset($childArray);
									 $childArray = array();
									 foreach($results as $row){
										  $parentUUID = $row["parent_uuid"];
										  $childArray[] = "space_contain.child_uuid = '$parentUUID' ";
								
										  $data = array("uuid" => $parentUUID,
															 "project_id" => $geoArray["project_id"],
															 "source_id" => "JSONfile-mean-".$level,
															 "latitude" => $row["meanLat"],
															 "longitude" => $row["meanLon"]
															 );
										  
										  $where = "uuid = '$parentUUID' ";
										  $db->delete("geo_space", $where);
										  $db->insert("geo_space", $data);
									 }
								}
								else{
									 $topTree = true;
								}
						  }

						  $output = $idFeatures;
					 }
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
