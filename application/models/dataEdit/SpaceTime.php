<?php

//fix space entities where the source datatable did not have unique labeling
//and we should not have repeating variables
class dataEdit_SpaceTime  {
    
    public $db;
	 public $projectUUID;
	 public $requestParams; //request parameters
	 public $errros;
	 
	 //add a geo reference to an item
	 function geoTagItem($uuid = false, $sourceID = "manual"){
		  
		  $requestParams = $this->requestParams;
		  $errors = array();
		  $data = array();
		  
		  if(!$uuid){
				$actValue = $this->checkExistsNonBlank("uuid", $requestParams);
				if($actValue != false){
					 $data["uuid"] = $actValue;
					 $uuid = $actValue;
				}
		  }
		  else{
				$data["uuid"] = $uuid;
		  }
		  
		  if(!$uuid){
				$errors[] = "Need an item UUID";
		  }
		  
		  $actValue = $this->checkExistsNonBlank("projUUID", $requestParams);
		  if($actValue != false){
				if(stristr($actValue, "oc")){
					 $actValue = 0;
				}
				$data["project_id"] = $actValue;
		  }
		  else{
				$data["project_id"] = $this->getProjectUUID($uuid);
		  }
		  
		  $data["source_id"] = $sourceID;
		  
		  $lat = false;
		  $actValue = $this->checkExistsNonBlank("lat", $requestParams);
		  if($actValue != false){
				if($actValue >= -180 && $actValue <= 180){
					 $lat = $actValue + 0;
				}
		  }
		  $lon = false;
		  $actValue = $this->checkExistsNonBlank("lon", $requestParams);
		  if($actValue != false){
				if($actValue >= -180 && $actValue <= 180){
					 $lon = $actValue + 0;
				}
		  }
		  
		  $geoObj = new GeoSpace_ToGeoJSON;
		  $geoLatLon = false;
		  $geoKML = $this->checkExistsNonBlank("geoKML", $requestParams);
		  $geoJSON  = $this->checkExistsNonBlank("geoJSON", $requestParams);
		  if($geoKML != false){
				if(!$geoJSON){
					 $geoJSONString = $geoObj->kml_to_geojson($geoKML);
					 $geoJSONObj = $geoObj->package_GeoJSON($geoJSONString);
					 $geoJSON = Zend_Json::encode($geoJSONObj);
					 $geoLatLon = $geoObj->GeoJSONcentroid($geoJSON);
					 //$geoLatLon = array("latitude" => 1, "longitude" => 1);
					 //echo print_r($geoLatLon);
					 //die;
					 $lat = $geoLatLon["latitude"];
					 $lon = $geoLatLon["longitude"];
				}
		  }
		  
		  if($lat != false && $lon != false){
				$data["latitude"] = $lat;
				$data["longitude"] =  $lon; 
		  }
		  else{
				$errors[] = "Need valid Lat / Lon decimal degrees";
		  }
		  
		  if(count($errors)<1){
				$geodata = $data;
				if($geoKML != false){
					 $data["kml_data"] = $geoKML;
				}
				
				$db = $this->startDB();
				$where = array();
				$where[] = "uuid  = '".$uuid."' ";
				$db->delete('geo_space', $where);
				$db->insert('geo_space', $data);
				$pubObj = new dataEdit_Published;
				$pubObj->deleteFromPublishedDocsByParentUUID($uuid); //since chronology is inherited, delete the children and this item from the published list
				
				if($geoJSON != false){
					 unset($geodata["source_id"]);
					 $subObj = new dataEdit_Subject;
					 $geodata["path"] = $subObj->determineContextFromContainRelations($uuid, "/");
					 $geodata["geoJSON"] = $geoJSON;
					 $db->delete('geodata', $where);
					 $db->insert('geodata', $geodata);
				}
				
		  }
		  
		  return array("data"=>$data, "errors" => $errors);
	 }
	 
	 //add a chronological tag / time range to an item
	 function chrontoTagItem($uuid = false){
		  
		  $requestParams = $this->requestParams;
		  $tStart = $_REQUEST['tStart'];
        $tEnd = $_REQUEST['tEnd'];
		  
		  if(!$uuid){
				if(isset($_REQUEST['uuid'])){
					 $uuid =  $_REQUEST['uuid'];
				}
		  }
		  
		  if($uuid != false){
				if(!is_numeric($tStart)){
					 $tStart = 0;
				}
				if(!is_numeric($tEnd)){
					 $tEnd = 0;
				}
				
				if($tEnd < $tStart){
					 $tHold = $tStart;
					 $tStart = $tEnd;
					 $tEnd = $tHold;
				}
				
				if($tEnd != $tStart){        
					 $dateLabel = "(".$this->makeNiceDate($tStart)." - ".$this->makeNiceDate($tEnd).")";
				}
				else{
					 $dateLabel = "(".$this->makeNiceDate($tStart).")";
				}
		  
				$projectUUID = false;
				if(!isset($requestParams["projUUID"])){
					 $projectUUID = $this->getProjectUUID($uuid);
				}
				
				if(!$projectUUID){
					 $projectUUID = "0";
				}
		  
				$db = $this->startDB();
				$where = array();
				$where[] = "uuid  = '".$uuid."' ";
				$db->delete('initial_chrono_tag', $where);
				
				$data = array('project_id'=> $projectUUID,
					 'uuid'=> $uuid,
					 'creator_uuid'=> 'oc',
					 'label'=> $dateLabel,
					 'start_time'=> $tStart,
					 'end_time'=> $tEnd,
					 'note_id'=> 'Default set',
					 'public'=> 1
				);
				
				$db->insert('initial_chrono_tag', $data);
				$pubObj = new dataEdit_Published;
				$pubObj->deleteFromPublishedDocsByParentUUID($uuid); //since chronology is inherited, delete the children and this item from the published list
				
		  }

	 }
	 
	 
	 //assign a chronological tag via values associated with two variable UUIDs
	 function chronoTagByTwoVariables($AvarUUID, $BvarUUID, $bceNegative = true){
		  
		  $output = array();
		  $db = $this->startDB();
		  
		  $sql = "SELECT DISTINCT observe.project_id,
		  observe.subject_uuid AS itemUUID,
		  AvarProps.val_num AS aValnum,
		  BvarProps.val_num AS bValnum,
		  aVals.val_text AS aValText,
		  bVals.val_text AS bValText
		  FROM observe
		  JOIN properties AS AvarProps ON (AvarProps.property_uuid = observe.property_uuid
				AND AvarProps.variable_uuid = '$AvarUUID')
		  JOIN val_tab AS aVals ON AvarProps.value_uuid = aVals.value_uuid
		  JOIN properties AS BvarProps ON (AvarProps.property_uuid = observe.property_uuid
				AND BvarProps.variable_uuid = '$BvarUUID')
		  JOIN val_tab AS bVals ON BvarProps.value_uuid = bVals.value_uuid	
		  ";
		  
		  $sql = "SELECT DISTINCT observe.project_id,
		  observe.subject_uuid AS itemUUID,
		  AvarProps.val_num AS aValnum,
		  aVals.val_text AS aValText
		  FROM observe
		  JOIN properties AS AvarProps ON (AvarProps.property_uuid = observe.property_uuid
				AND AvarProps.variable_uuid = '$AvarUUID')
		  JOIN val_tab AS aVals ON AvarProps.value_uuid = aVals.value_uuid
		  ";
		  
		  $result =  $db->fetchAll($sql);
		  if($result){
				foreach($result AS $row){
					 
					 $uuid = $row["itemUUID"];
					 
					 $sql = "SELECT BvarProps.val_num AS bValnum,
					 bVals.val_text AS bValText
					 FROM observe
					 JOIN properties AS BvarProps ON (BvarProps.property_uuid = observe.property_uuid)
					 JOIN val_tab AS bVals ON BvarProps.value_uuid = bVals.value_uuid
					 WHERE observe.subject_uuid = '$uuid'
					 AND BvarProps.variable_uuid = '$BvarUUID'
					 LIMIT 1;
					 ";
					 
					 $resultB =  $db->fetchAll($sql);
					 if($resultB){
						  $tStart = $row["aValText"] + 0 ;
						  $tEnd = $resultB[0]["bValText"] + 0 ;
						  
						  if(!$bceNegative){
								$tStart = $tStart * -1;
								$tEnd = $tEnd * -1;
						  }
						  
						  if($tStart > $tEnd){
								//insure the aDate is less than the bDate
								$tDate = $tEnd;
								$tEnd = $tStart;
								$tStart = $tDate;
						  }
						  
						  if($tEnd != $tStart){        
								$dateLabel = "(".$this->makeNiceDate($tStart)." - ".$this->makeNiceDate($tEnd).")";
						  }
						  else{
								$dateLabel = "(".$this->makeNiceDate($tStart).")";
						  }
						  
						  $projectUUID = $row["project_id"];
						  $where = array();
						  $where[] = "uuid  = '".$uuid."' ";
						  $db->delete('initial_chrono_tag', $where);
						  
						  $data = array('project_id'=> $projectUUID,
								'uuid'=> $uuid,
								'creator_uuid'=> 'oc',
								'label'=> $dateLabel,
								'start_time'=> $tStart,
								'end_time'=> $tEnd,
								'note_id'=> 'Default set',
								'public'=> 1
						  );
						  
						  $db->insert('initial_chrono_tag', $data);
						  $pubObj = new dataEdit_Published;
						  $pubObj->deleteFromPublishedDocsByParentUUID($uuid); //since chronology is inherited, delete the children and this item from the published list
	  
						  $output[$dateLabel][] = $uuid;
					 }
				}
		  
		  }
		  
		  return $output;
	 }
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 //selects items by assocsiation with a property uuid, then chronology tags them
	 function chronoTagByProperty($propUUID){
		  
		  $output = array();
		  $db = $this->startDB();
		  
		  $sql = "SELECT DISTINCT subject_uuid AS itemUUID
		  FROM observe
		  WHERE property_uuid = '$propUUID'
		  ";
		  
		  $result =  $db->fetchAll($sql);
		  if($result){
				$publishedObj = new dataEdit_Published;
				foreach($result as $row){
					 $itemUUID = $row["itemUUID"];
					 $this->chrontoTagItem($itemUUID);
					 $publishedObj->deleteFromPublishedDocsByParentUUID($itemUUID); //deletes the item and it's children from the list of published items
					 $output[] = $itemUUID ;
				}
		  }
		  
		  return $output;
	 }
	 
	 
	 function getProjectUUID($uuid){
		  if(!$this->projectUUID){
				$db = $this->startDB();
				$sql = "SELECT project_id FROM space WHERE uuid = '$uuid' LIMIT 1; ";
				$result =  $db->fetchAll($sql);
				if($result){
					 $this->projectUUID = $result[0]["project_id"];
				}
		  }
		  
		  return $this->projectUUID;
	 }
	 
	 
	 function makeNiceDate($dec_time){
		  //this function creates human readible dates, with a CE, BCE notation
		  //large values have a K for thousands or an M for millions appended()
		  
		  $abs_time = abs($dec_time);
		 
		  if($dec_time<0){
				$suffix = " BCE";
		  }
		  else{
				$suffix = " CE";
		  }
		  
		  if($abs_time<10000){
				if($dec_time<0){
					 $output = (number_format($abs_time)).$suffix;
				}
				else{
					 $output = round($abs_time,0).$suffix;
				}
		  }//end case with less than 10,000
		  else{
			  
				if($abs_time<1000000){
					 $rnd_time = round($abs_time/1000,2);
					 $output = (number_format($rnd_time))."K".$suffix;
				}
				else{
					 $rnd_time = round($abs_time/1000000,2);
					 $output = (number_format($rnd_time))."M".$suffix;
				}
		  }
	
		  return $output;

	 }//end function
	 
	 
	 function checkExistsNonBlank($key, $requestParams){
		  $value = false;
		  if(isset($requestParams[$key])){
				$value = $requestParams[$key];
				if(strlen($value)<1){
					 $value = false;
				}
		  }
		  return $value;
	 }
	
	//startup the database
	 function startDB(){
		  if(!$this->db){
				$db = Zend_Registry::get('db');
				$this->setUTFconnection($db);
				$this->db = $db;
				return $db;
		  }
		  else{
				return $this->db;
		  }
	 }
	 
	 
	 //preps for utf8
	 function setUTFconnection($db){
		  $sql = "SET collation_connection = utf8_unicode_ci;";
		  $db->query($sql, 2);
		  $sql = "SET NAMES utf8;";
		  $db->query($sql, 2);
	 }
    
}  
