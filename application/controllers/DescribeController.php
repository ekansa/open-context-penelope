<?php

// increase the memory limit
ini_set("memory_limit", "2048M");
// set maximum execution time to no limit
ini_set("max_execution_time", "0");

class DescribeController extends Zend_Controller_Action
{
    
    //public $host = "http://penelope.opencontext.org";
    public $host = "http://".$_SERVER['SERVER_NAME'];
    public $counter = 0;
    
	
	function init()
    {  
        $this->view->baseUrl = $this->_request->getBaseUrl();
        require_once 'App/Util/GenericFunctions.php';
        Zend_Loader::loadClass('Zend_Debug');
        Zend_Loader::loadClass('ContextItem');
        Zend_Loader::loadClass('Table_Property');
        Zend_Loader::loadClass('Table_Value');
        Zend_Loader::loadClass('Table_Variable');
        Zend_Loader::loadClass('Table_Observe');
        Zend_Loader::loadClass('Table_Diary');
        Zend_Loader::loadClass('Table_Resource');
        Zend_Loader::loadClass('Table_LinkRelationship');
        Zend_Loader::loadClass('Table_User');
		  Zend_Loader::loadClass('dataEdit_VarPropNotes');
		  Zend_Loader::loadClass('dataEdit_VarPropNotes');
    }
    
     //make sure all connections are UTF-8 OK
    private function setUTFconnection($db){
		  $sql = "SET collation_connection = utf8_unicode_ci;";
		  $db->query($sql, 2);
		  $sql = "SET NAMES utf8;";
		  $db->query($sql, 2);
    }
    
    //add notes describing properties
    function varPropsAction(){
        //$this->_helper->viewRenderer->setNoRender();
        $db = Zend_Registry::get('db');
        $this->setUTFconnection($db);
		  
		  $VarPropObj = new dataEdit_VarPropNotes;
		  $varUUID = $_REQUEST['varUUID'];
		  if(isset($_REQUEST['sort'])){
				$VarPropObj->alphaSort = $_REQUEST['sort'];
		  }
		  if(isset($_REQUEST['showPropCounts'])){
				$VarPropObj->showPropCounts = $_REQUEST['showPropCounts'];
		  }
		  
		  $this->view->varUUID = $varUUID;
		  $VarPropObj->getProperties($varUUID);
		  $this->view->VarPropObj = $VarPropObj;
    }
    
    //receive POST requests to add a note to describe a property
    function propNoteAction(){
        $this->_helper->viewRenderer->setNoRender();
        $db = Zend_Registry::get('db');
		  $this->setUTFconnection($db);
		  $propertyUUID = $_REQUEST['propertyUUID'];
		  $note = $_REQUEST['note'];
		  $projectUUID = $_REQUEST['projectUUID'];
		  $varUUID = $_REQUEST['varUUID'];
		  
		  $VarPropObj = new dataEdit_VarPropNotes;
		  $VarPropObj->updatePropNote($propertyUUID, $note);
	 
		  $headerLink = "var-props?varUUID=".$varUUID."&showPropCounts=".$_REQUEST['showPropCounts'];
		  header("Location: $headerLink");
    }
    
    
	 function poggioLinkAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  $db = Zend_Registry::get('db');
		  $this->setUTFconnection($db);
		  
		  $sql = "SELECT * FROM space
		  WHERE (class_uuid = '8299f420-62af-11de-8a39-0800200c9a66'
		  OR class_uuid = '8d962170-4579-11df-9879-0800200c9a66'
		  OR class_uuid = 'A2017643-0086-4D98-4932-E4AD3884E99D'
		  OR class_uuid = 'D9AE02E5-C3F2-41D0-EB3A-39798F63F6C4')
		  AND sample_des = ''
		  ORDER BY space_label
		  ";
		  
		  $itemType = urlencode("Locations or Objects");
		  $results =  $db->fetchAll($sql);
		  $i = 1;
		  $poggioURLbase = "http://poggiocivitate.classics.umass.edu/catalog/viewartifactcatalog.asp?fid=";
		  foreach($results as $row){
				
				
				$projectUUID = $row["project_id"];
				$itemUUID = $row["uuid"];
				$penURL = "http://penelope.oc/preview/space?UUID=".$itemUUID;
				$itemLabel = $row["space_label"];
				$itemLabel = str_replace(" ", "", $itemLabel);
				
				if(strlen($itemLabel) < 12){
					 $labLen = strlen($itemLabel);
					 while($labLen < 12){
						  $itemLabel .= "0";
						  $labLen = strlen($itemLabel);
					 }
				}
				
				$url = $poggioURLbase.$itemLabel;
				@$html = file_get_contents($url);
				if($html){
					 if(stristr($html, "A record with this ID does not exist or is not viewable at this time.")){
						  echo "<h4>No record for <a href=\"".$url."\">$itemLabel</a>, see <a href=\"".$penURL."\">Penelope</a></h4>";
						  $linkNote = "none";
					 }
					 else{
						  echo "<p>A record for <a href=\"".$url."\">$itemLabel</a> exists, see <a href=\"".$penURL."\">Penelope</a></p>";
						  $linkNote = $url;
						  
						  $noteText = "<p id=\"link-".$itemLabel."\">The Poggio Civitate Excavation Project website originally included this object at this address: <br/>".chr(13);
						  $noteText .= "<a href=\"".$url."\">$url</a>".chr(13);
						  $noteText .= "</p>".chr(13);
						  
						  $noteURL = "http://penelope.oc/edit-dataset/add-note?";
						  $noteURL .= "projectUUID=".$projectUUID;
						  $noteURL .= "&itemType=".$itemType;
						  $noteURL .= "&source=pc-http-check";
						  $noteURL .= "&itemUUID=".$itemUUID;
						  $noteURL .= "&newText=".urlencode($noteText);
						  @$noteOK = file_get_contents($noteURL);
					 }
				}
				else{
					 echo "<h3>HTTP error for <a href=\"".$url."\">$itemLabel</a>, see <a href=\"".$penURL."\">Penelope</a></h3>";
					 $linkNote = "none";
				}
				
				$where = "uuid = '$itemUUID' ";
				$data = array("sample_des" => $linkNote);
				$db->update("space", $data, $where);
				
				$i++;
				if($i > 50){
					 //break;
				}
				sleep(1);
				
		  }
		  
		  
		  
	 }
	 
	 
    function objectRefsAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  $db = Zend_Registry::get('db');
		  $this->setUTFconnection($db);
		  
		  $sql = "SELECT ArtifactID, field_15 as ref
		  FROM  z_artifacts
		  WHERE field_15 IS NOT NULL
		  AND ArtifactID = '19690166'
		  LIMIT 1000
		  ";
		  
		  $sql = "SELECT ArtifactID, field_15 as ref
		  FROM  z_artifacts
		  WHERE field_15 IS NOT NULL
		  ";
		  
		  $itemType = urlencode("Locations or Objects");
		  $results =  $db->fetchAll($sql);
		  foreach($results as $row){
				$originName = trim($row["ArtifactID"]);
				$rawRef = $row["ref"];
				
				$rawRef = strtolower($rawRef);
				$rawRef = str_replace("absorbed into", "absorbed-into", $rawRef);
				$rawRef = str_replace("absorbed by", "absorbed-by", $rawRef);
				$rawRef = str_replace("<p>", " ", $rawRef);
				$rawRef = str_replace("</p>", " ", $rawRef);
				
				if(strstr($rawRef, " ")){
					 $refArray = explode(" ", $rawRef);
				}
				else{
					 $refArray = array(0 =>$rawRef);
				}
				echo "<h2>$originName</h2>";
				echo "<p>Raw ref: $rawRef</p>";
				
				$allRefs = array();
				$nextAbsorbInto = false;
				$nextAbsorbBy = false;
				$nextAbsorbs = false;
				$nextJoins = false;
				$i = 0;
				
				$wordIndex = 0;
				foreach($refArray as $seg){
					 if(stristr($seg, "absorbed-into")){
						  $nextAbsorbInto = true;
						  $nextAbsorbBy = false;
						  $nextAbsorbs = false;
						  $nextJoins = false;
					 }
					 elseif(stristr($seg, "absorbed-by")){
						  $nextAbsorbBy = true;
						  $nextAbsorbInto = false;
						  $nextAbsorbs = false;
						  $nextJoins = false;
					 }
					 elseif(stristr($seg, "absorbs") || $seg == "absorb"){
						  $nextAbsorbs = true;
						  $nextAbsorbInto = false;
						  $nextAbsorbBy = false;
						  $nextJoins = false;
					 }
					 elseif($seg == "joins"){
						  $nextJoins = true;
						  $nextAbsorbInto = false;
						  $nextAbsorbBy = false;
						  $nextAbsorbs = false;
					 }
					 
					 /*
					 echo "<h5>$seg</h5>";
					 echo "<br/>nextAbsorbInto: ".$nextAbsorbInto;
					 echo "<br/>nextAbsorbBy: ".$nextAbsorbBy;
					 echo "<br/>nextAbsorbs: ".$nextAbsorbs;
					 echo "<br/>nextJoins: ".$nextJoins;
					 */
					 
					 $numSeg = preg_replace('/[^0-9]/s', '', $seg);
					 $lenSeg = strlen($numSeg);
					 if($lenSeg >= 8){
						  
						  if($nextAbsorbInto){
								$nextAbsorbInto = $this->toggleToFalse($refArray, $wordIndex);
								$priorAbsorb = false;
								if($i>0){
									 
									 $priorAbsorb = $allRefs[$i-1]["label"];
									 
									 if($numSeg == $originName){
										  unset($allRefs[$i-1]);
									 }
									 elseif($priorAbsorb == $numSeg){
										  $priorAbsorb = false;
									 }
									 
								}
								$type = "absorbs";
								if(!$priorAbsorb){
									 $priorAbsorb = $originName;
									 $type = "absorbed by";
								}
								if($priorAbsorb != $numSeg){
									 if($numSeg != $originName){
										  $allRefs[$i] = array("label" => $numSeg, "type" => $type , "prior" => $priorAbsorb);
									 }
									 else{
										  $allRefs[$i] = array("prior" => $numSeg, "type" => $type , "label" => $priorAbsorb);
									 }
									 $i++;
								}
						  }
						  else{
								if($numSeg !=  $originName){
									 if($nextAbsorbBy){
										  $allRefs[$i] = array("label" => $numSeg, "type" => "absorbed by", "prior" => false);
										  $nextAbsorbBy = $this->toggleToFalse($refArray, $wordIndex);
									 }
									 elseif($nextAbsorbs){
										  $allRefs[$i] = array("label" => $numSeg, "type" => "absorbs", "prior" => $originName);
										  $nextAbsorbs = $this->toggleToFalse($refArray, $wordIndex);
									 }
									 elseif($nextJoins){
										  $allRefs[$i] = array("label" => $numSeg, "type" => "joins", "prior" => $originName);
										  $nextJoins = $this->toggleToFalse($refArray, $wordIndex);
									 }
									 else{
										  $allRefs[$i] = array("label" => $numSeg, "type" => "reference", "prior" => false);
									 }
									 $i++;
								}
						  }
					 }
					 $wordIndex++;
				}
				
				$processedRefs = array();
				foreach($allRefs as $actRef){
					 $actProcessed = array();
					 if($actRef["type"] == "absorbs"){
						  //echo "<br/>Origin: ".$actRef["prior"]." => absorbs => ".$actRef["label"];
						  if($actRef["prior"] == $originName){
								$actProcessed["originLabel"] = $actRef["prior"];
								$actProcessed["originUUID"] = $this->getPCuuid($actRef["prior"], $db);
								$actProcessed["linkType"] = "absorbs";
								$actProcessed["targLabel"] = $actRef["label"];
								$actProcessed["targUUID"] = $this->getPCuuid($actRef["label"], $db);
						  }
						  else{
								$actProcessed["originLabel"] = $originName;
								$actProcessed["originUUID"] = $this->getPCuuid($originName, $db);
								$actProcessed["linkType"] = "absorbed by";
								$actProcessed["targLabel"] = $actRef["label"];
								$actProcessed["targUUID"] = $this->getPCuuid($actRef["label"], $db);
						  }
						  
					 }
					 else{
						  //echo "<br/>Origin: ".$originName." => refs => ".$actRef["label"];
						  $actProcessed["originLabel"] = $originName;
						  $actProcessed["originUUID"] = $this->getPCuuid($originName, $db);
						  $actProcessed["linkType"] = $actRef["type"];
						  $actProcessed["targLabel"] = $actRef["label"];
						  $actProcessed["targUUID"] = $this->getPCuuid($actRef["label"], $db);
					 }
					 if($actProcessed["originUUID"] != false && $actProcessed["targUUID"] != false && $actProcessed["originUUID"] != $actProcessed["targUUID"] ){
						  $processedRefs[] = $actProcessed;
					 }
				}
				
				$projectUUID = "DF043419-F23B-41DA-7E4D-EE52AF22F92F";
				
				foreach($processedRefs as $actRef){
					 echo "<br/>Origin ".$actRef["originLabel"]." (".$actRef["originUUID"].") => ".$actRef["linkType"]." => ";
					 echo "Target ".$actRef["targLabel"]." (".$actRef["targUUID"].")";
					 $linkURL = "http://penelope.oc/edit-dataset/link-item?";
					 $linkURL .= "projectUUID=".$projectUUID;
					 $linkURL .= "&source=ref-scrape";
					 $linkURL .= "&originUUID=".$actRef["originUUID"];
					 $linkURL .= "&originType=".$itemType;
					 $linkURL .= "&targUUID=".$actRef["targUUID"];
					 $linkURL .= "&targType=".$itemType;
					 $linkURL .= "&originRel=".urlencode($actRef["linkType"]);
					 @$linkOK = file_get_contents($linkURL);
				}
				
				
				unset($absorbedArray);
				unset($refsArray);
				unset($allRefs);
		  }
	 
		  //field_15 is "References"
		  //field_16 is "Published"
	 
	 }
	 
	 private function toggleToFalse($refArray, $wordIndex){
		  $output = false;
		  $wordIndex = $wordIndex + 1;
		  if(isset($refArray[$wordIndex])){
				$seg = $refArray[$wordIndex];
				if($seg == "and"){
					 $output = true;
				}
				$numSeg = preg_replace('/[^0-9]/s', '', $seg);
				$lenSeg = strlen($numSeg);
				if($lenSeg >= 8){
					 $output = true;
				}
		  }
		  return $output;
	 }
	 
	 
	 private function getPCuuid($numericLabel, $db){
		  $output = false;
		  $label = "PC ".$numericLabel;
		  $sql = "SELECT uuid FROM space WHERE space_label = '$label' LIMIT 1;";
		  $result =  $db->fetchAll($sql);
		  if($result){
				$output = $result[0]["uuid"];
		  }
		  return $output;
	 }
	 
	 

	 function geoJsonAction(){
		  $this->_helper->viewRenderer->setNoRender();
		  if(isset($_REQUEST["jsonfile"])){
				$jsonFile = $_REQUEST["jsonfile"];
		  }
		  else{
				$jsonFile = false;
		  }
		  $flipLatLon = true;
		  
		  $geoJSON = false;
		  if($jsonFile != false){
				@$jsonString = file_get_contents($jsonFile);
				if($jsonString != false){
					 @$geoJSON = Zend_Json::decode($jsonString);
					 if(is_array($geoJSON)){
						  
						  $db = Zend_Registry::get('db');
						  $this->setUTFconnection($db);
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
						  
						  
						  
						  
						  
						  
						  
						  header('Content-Type: application/json; charset=utf8');
						  echo Zend_Json::encode($idFeatures);
					 }
				}
		  }
		  if(!$geoJSON){
				echo "<h2>Post a Valid JSON file</h2>";
				echo "<form action='geo-json' method='POST' >";
				echo "<input type='text' size='60' name='jsonfile' />";
				echo "<input type='submit' />";
				echo "</form>";
				
				echo "$jsonFile is not valid geoJSON";
		  }
		  
	 }
	 


}