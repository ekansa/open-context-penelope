<?php
/*
This class process a work book full of measurements (as saved in a "fods" xml format from Libre Office)
It's specifically designed to parse through the Catalhoyuk type schema for describing measurements

I'm including it, since it may be useful to adapt to other projects

*/
class ProjEdits_Dinaa  {
    
	 public $db;
	 public $projectUUID;
	 
	 
	 const GeoUsername = "ekansa"; //geonames API name
    const APIsleep = .5; //
    const GeoNamesBaseURI = "http://www.geonames.org/";
	 const GeoNamesBaseAPI = "http://api.geonames.org/";
	 
	 function altIowaDates(){
		  
		  $output = array();
		  $output["found"] = 0;
		  $spaceTimeObj = new  dataEdit_SpaceTime;
		  $spaceTimeObj->projectUUID = $this->projectUUID;
		  
		  $ldObj = new dataEdit_LinkedData;
		  $ldObj->projectUUID = $this->projectUUID;
		  
		  $periodVarUUID ='852FD445-E7F9-48C4-1C0F-6539399AB57A';
		  
		  $db = $this->startDB();
		  
		  $sql = "SELECT DISTINCT observe.subject_uuid
		  FROM observe
		  JOIN properties ON observe.property_uuid = properties.property_uuid
		  LEFT JOIN initial_chrono_tag ON observe.subject_uuid = initial_chrono_tag.uuid
		  WHERE properties.variable_uuid = '$periodVarUUID'
		  AND initial_chrono_tag.uuid IS NULL
		  ";
		  
		   $resultA = $db->fetchAll($sql, 2);
		  if($resultA){
				foreach($resultA as $rowA){
					 $itemUUID = $rowA["subject_uuid"];
					 
					 $sql = "SELECT MAX(grg.startBP) as tStart, MIN(grg.endBP) as tEnd
					 FROM observe
					 JOIN properties ON observe.property_uuid = properties.property_uuid
					 JOIN linked_data ON properties.property_uuid = linked_data.itemUUID
					 JOIN z_iowa_dates AS grg ON grg.dinaa_uri = linked_data.linkedURI
					 WHERE properties.variable_uuid = '$periodVarUUID'
					 AND observe.subject_uuid = '$itemUUID'
					 ";
					 
					 
					 $result = $db->fetchAll($sql, 2);
					 if($result){
						  $requestParams = array();
						  $requestParams["uuid"] = $itemUUID;
						  $requestParams["projUUID"] = $this->projectUUID;
						  $requestParams["tStart"] = 1950 - $result[0]["tStart"];
						  $requestParams["tEnd"] = 1950 - $result[0]["tEnd"];
						  $spaceTimeObj->requestParams = $requestParams;
						  $spaceTimeObj->chrontoTagItem();
						  
						  $output["found"]++;
						  
					 }
					 else{
						  $output["missing"][] = $itemUUID;
					 }
				}
		  }
		  
		  return $output;
	 }
	 
	 
	 function iowaObsDates(){
		  
		  $spaceTimeObj = new  dataEdit_SpaceTime;
		  $spaceTimeObj->projectUUID = $this->projectUUID;
		  
		  $ldObj = new dataEdit_LinkedData;
		  $ldObj->projectUUID = $this->projectUUID;
		  
		  $periodVarUUID ='BCE3FC06-6BBA-4AC9-8239-0E73E6A94726';
		  
		  $db = $this->startDB();
		  $doneLabels = array();
		  
		  $sql = "SELECT id, field_5 AS label
		  FROM z_6_ae54e13de
		  WHERE 1
		  ORDER BY label, id
		  ";
		  
		  $resultA = $db->fetchAll($sql, 2);
		  if($resultA){
				foreach($resultA as $rowA){
					 $label = $rowA["label"];
					 if(!in_array($label, $doneLabels)){
						  
						  $sql = "SELECT id
						  FROM z_6_ae54e13de
						  WHERE field_5 = '$label'
						  ORDER BY id
						  ";
						  
						  $result = $db->fetchAll($sql, 2);
						  $lCount = count($result);
						  if($lCount > 1){
								$i = 1;
								foreach($result as $row){
									 $data = array("obs" => $i);
									 $where = "id = ".$row["id"];
									 $db->update("z_6_ae54e13de", $data, $where);
									 $i++;
								}
						  }
					 
						  $doneLabels[] = $label;
					 }
				}
		  }
		  
		  return $doneLabels;
	 }
	 
	 
	 
	 function iowaDates(){
		  $output = array();
		  $output["found"] = 0;
		  $spaceTimeObj = new  dataEdit_SpaceTime;
		  $spaceTimeObj->projectUUID = $this->projectUUID;
		  
		  $ldObj = new dataEdit_LinkedData;
		  $ldObj->projectUUID = $this->projectUUID;
		  
		  $periodVarUUID ='BCE3FC06-6BBA-4AC9-8239-0E73E6A94726';
		  
		  $db = $this->startDB();
		  
		  $sql = "SELECT DISTINCT observe.subject_uuid
		  FROM observe
		  JOIN properties ON observe.property_uuid = properties.property_uuid
		  WHERE properties.variable_uuid = '$periodVarUUID'
		  ";
		  
		  $resultA = $db->fetchAll($sql, 2);
		  if($resultA){
				foreach($resultA as $rowA){
					 $itemUUID = $rowA["subject_uuid"];
					 //$where = "uuid = '$itemUUID' ";
					 //$db->delete("initial_chrono_tag", $where);
					 
					 $sql = "SELECT MAX(grg.start_bp) as tStart, MIN(grg.end_bp) as tEnd, properties.property_uuid, grg.dinaa_uri
					 FROM observe
					 JOIN properties ON observe.property_uuid = properties.property_uuid
					 JOIN val_tab ON val_tab.value_uuid = properties.value_uuid
					 JOIN z_missouri_dates AS grg ON grg.label = val_tab.val_text
					 WHERE properties.variable_uuid = '$periodVarUUID'
					 AND observe.subject_uuid = '$itemUUID'
					 ";
					 
					 $sql = "SELECT MAX(grg.startBP) as tStart, MIN(grg.endBP) as tEnd, properties.property_uuid
					 FROM observe
					 JOIN properties ON observe.property_uuid = properties.property_uuid
					 JOIN z_iowa_dates AS grg ON grg.culture_propUUID = properties.property_uuid
					 WHERE properties.variable_uuid = '$periodVarUUID'
					 AND observe.subject_uuid = '$itemUUID'
					 ";
					 
					 
					 $result = $db->fetchAll($sql, 2);
					 if($result){
						  $requestParams = array();
						  $requestParams["uuid"] = $itemUUID;
						  $requestParams["projUUID"] = $this->projectUUID;
						  $requestParams["tStart"] = 1950 - $result[0]["tStart"];
						  $requestParams["tEnd"] = 1950 - $result[0]["tEnd"];
						  $spaceTimeObj->requestParams = $requestParams;
						  $spaceTimeObj->chrontoTagItem();
						  
						  $output["found"]++;
						  
					 }
					 else{
						  $output["missing"][] = $itemUUID;
					 }
				}
		  }
		  
		  return $output;
	 }
	 
	 function iowaPeriodLink(){
		  
		  $output = array();
		  $ldObj = new dataEdit_LinkedData;
		  $ldObj->projectUUID = $this->projectUUID;
		  
		  $db = $this->startDB();
		  
		  $sql = "SELECT * FROM z_iowa_dates WHERE 1; ";
		  
		  $result = $db->fetchAll($sql, 2);
		  foreach($result as $row){
			   $requestParams = array();
			   $requestParams["subjectUUID"] = $row["culture_propUUID"];
			   $requestParams["subjectType"] = "property";
			   $requestParams["predicateURI"] = "type";
			   $requestParams["objectURI"] = $row["dinaa_uri"];
			   $requestParams["objectLabel"] = $row["dinaa_label"];
			   $requestParams["projectUUID"] = $this->projectUUID;
			   
			   $ldObj->requestParams = $requestParams;
			   $resp = $ldObj->addUpdateLinkedData();
			   
			   $output[$row["culture_propUUID"]] = $resp;
			   
		  }
		  
		  return $output;
	 }
	 
	 function illDates(){
		  $output = array();
		  $output["found"] = 0;
		  $spaceTimeObj = new  dataEdit_SpaceTime;
		  $spaceTimeObj->projectUUID = $this->projectUUID;
		  
		  $ldObj = new dataEdit_LinkedData;
		  $ldObj->projectUUID = $this->projectUUID;
		  
		  $periodVarUUID ='D981C5F4-A978-434E-DA1A-20BA2A68178C';
		  
		  $db = $this->startDB();
		  
		  $sql = "SELECT DISTINCT observe.subject_uuid
		  FROM observe
		  JOIN properties ON observe.property_uuid = properties.property_uuid
		  WHERE properties.variable_uuid = '$periodVarUUID'
		  ";
		  
		  $resultA = $db->fetchAll($sql, 2);
		  if($resultA){
				foreach($resultA as $rowA){
					 $itemUUID = $rowA["subject_uuid"];
					 //$where = "uuid = '$itemUUID' ";
					 //$db->delete("initial_chrono_tag", $where);
					 
					 $sql = "SELECT MAX(grg.start_bp) as tStart, MIN(grg.end_bp) as tEnd, properties.property_uuid, grg.dinaa_uri
					 FROM observe
					 JOIN properties ON observe.property_uuid = properties.property_uuid
					 JOIN val_tab ON val_tab.value_uuid = properties.value_uuid
					 JOIN z_missouri_dates AS grg ON grg.label = val_tab.val_text
					 WHERE properties.variable_uuid = '$periodVarUUID'
					 AND observe.subject_uuid = '$itemUUID'
					 ";
					 
					 $sql = "SELECT MAX(grg.startBP) as tStart, MIN(grg.endBP) as tEnd, properties.property_uuid
					 FROM observe
					 JOIN properties ON observe.property_uuid = properties.property_uuid
					 JOIN z_ill_dates AS grg ON grg.propertyUUID = properties.property_uuid
					 WHERE properties.variable_uuid = '$periodVarUUID'
					 AND observe.subject_uuid = '$itemUUID'
					 ";
					 
					 
					 $result = $db->fetchAll($sql, 2);
					 if($result){
						  $requestParams = array();
						  $requestParams["uuid"] = $itemUUID;
						  $requestParams["projUUID"] = $this->projectUUID;
						  $requestParams["tStart"] = 1950 - $result[0]["tStart"];
						  $requestParams["tEnd"] = 1950 - $result[0]["tEnd"];
						  $spaceTimeObj->requestParams = $requestParams;
						  $spaceTimeObj->chrontoTagItem();
						  
						  $output["found"]++;
						  
					 }
					 else{
						  $output["missing"][] = $itemUUID;
					 }
				}
		  }
		  
		  return $output;
	 }
	 
	 
	 
	 
	 function illGeo(){
		  
		  $output = array();
		  $output["count"] = 0;
		  $db = $this->startDB();
		  $sql = "SELECT * FROM z_9_459db9e4a_all
		  WHERE 1
		  GROUP BY field_2
		  ";
		  
		  $result = $db->fetchAll($sql, 2);
        if($result){
				foreach($result as $row){
					 //$site = "Site ".trim($row["field_2"]);
					 $site = trim($row["field_2"]);
					 //$site = str_replace("_", "-", $site);
					 $uuid = $this->getSiteUUID($site);
					 if($uuid != false){
						  
						  $where = "uuid  = '$uuid' ";
						  $db->delete("geo_space", $where);
						  
						  $lat = $row["field_14"];
						  $lon = $row["field_15"];
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
								$output["count"]++; 
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
	 
	 
	 
	 function kyDates(){
		  $output = array();
		  $output["found"] = 0;
		  $spaceTimeObj = new  dataEdit_SpaceTime;
		  $spaceTimeObj->projectUUID = $this->projectUUID;
		  
		  $ldObj = new dataEdit_LinkedData;
		  $ldObj->projectUUID = $this->projectUUID;
		  
		  $periodVarUUID ='F249B33E-E496-43DD-E9A5-5BF79DE00A91';
		  
		  $db = $this->startDB();
		  
		  $sql = "SELECT DISTINCT observe.subject_uuid
		  FROM observe
		  JOIN properties ON observe.property_uuid = properties.property_uuid
		  WHERE properties.variable_uuid = '$periodVarUUID'
		  ";
		  
		  $resultA = $db->fetchAll($sql, 2);
		  if($resultA){
				foreach($resultA as $rowA){
					 $itemUUID = $rowA["subject_uuid"];
					 //$where = "uuid = '$itemUUID' ";
					 //$db->delete("initial_chrono_tag", $where);
					 
					 $sql = "SELECT MAX(grg.start_bp) as tStart, MIN(grg.end_bp) as tEnd, properties.property_uuid, grg.dinaa_uri
					 FROM observe
					 JOIN properties ON observe.property_uuid = properties.property_uuid
					 JOIN val_tab ON val_tab.value_uuid = properties.value_uuid
					 JOIN z_missouri_dates AS grg ON grg.label = val_tab.val_text
					 WHERE properties.variable_uuid = '$periodVarUUID'
					 AND observe.subject_uuid = '$itemUUID'
					 ";
					 
					 $sql = "SELECT MAX(grg.startBP) as tStart, MIN(grg.endBP) as tEnd, properties.property_uuid
					 FROM observe
					 JOIN properties ON observe.property_uuid = properties.property_uuid
					 JOIN z_kentucky_dates AS grg ON grg.propertyUUID = properties.property_uuid
					 WHERE properties.variable_uuid = '$periodVarUUID'
					 AND observe.subject_uuid = '$itemUUID'
					 ";
					 
					 
					 $result = $db->fetchAll($sql, 2);
					 if($result){
						  $requestParams = array();
						  $requestParams["uuid"] = $itemUUID;
						  $requestParams["projUUID"] = $this->projectUUID;
						  $requestParams["tStart"] = 1950 - $result[0]["tStart"];
						  $requestParams["tEnd"] = 1950 - $result[0]["tEnd"];
						  $spaceTimeObj->requestParams = $requestParams;
						  $spaceTimeObj->chrontoTagItem();
						  
						  $output["found"]++;
						  
					 }
					 else{
						  $output["missing"][] = $itemUUID;
					 }
				}
		  }
		  
		  return $output;
	 }
	 
	 
	 function kyPeriodLink(){
		  
		  $output = array();
		  $ldObj = new dataEdit_LinkedData;
		  $ldObj->projectUUID = $this->projectUUID;
		  
		  $db = $this->startDB();
		  
		  $sql = "SELECT * FROM z_kentucky_dates WHERE 1; ";
		  
		  $result = $db->fetchAll($sql, 2);
		  foreach($result as $row){
			   $requestParams = array();
			   $requestParams["subjectUUID"] = $row["propertyUUID"];
			   $requestParams["subjectType"] = "property";
			   $requestParams["predicateURI"] = "type";
			   $requestParams["objectURI"] = $row["uri"];
			   $requestParams["objectLabel"] = $row["dinaa_label"];
			   $requestParams["projectUUID"] = $this->projectUUID;
			   
			   $ldObj->requestParams = $requestParams;
			   $resp = $ldObj->addUpdateLinkedData();
			   
			   $output[$row["propertyUUID"]] = $resp;
			   
		  }
		  
		  
	 }
	 
	 
	 
	 function kyGeo(){
		  
		  $output = array();
		  $output["count"] = 0;
		  $db = $this->startDB();
		  $sql = "SELECT * FROM z_8_e21861fce
		  WHERE 1
		  GROUP BY field_2
		  ";
		  
		  $result = $db->fetchAll($sql, 2);
        if($result){
				foreach($result as $row){
					 //$site = "Site ".trim($row["field_2"]);
					 $site = trim($row["field_2"]);
					 //$site = str_replace("_", "-", $site);
					 $uuid = $this->getSiteUUID($site);
					 if($uuid != false){
						  
						  $where = "uuid  = '$uuid' ";
						  $db->delete("geo_space", $where);
						  
						  $lat = $row["field_12"];
						  $lon = $row["field_13"];
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
								$output["count"]++; 
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
	 
	 function iowaGeo(){
		  
		  $output = array();
		  $output["count"] = 0;
		  $db = $this->startDB();
		  $sql = "SELECT * FROM z_6_cedd958e8
		  WHERE 1
		  GROUP BY field_2
		  ";
		  
		  $result = $db->fetchAll($sql, 2);
        if($result){
				foreach($result as $row){
					 //$site = "Site ".trim($row["field_2"]);
					 $site = trim($row["field_2"]);
					 //$site = str_replace("_", "-", $site);
					 $uuid = $this->getSiteUUID($site);
					 if($uuid != false){
						  
						  $where = "uuid  = '$uuid' ";
						  $db->delete("geo_space", $where);
						  
						  $lat = $row["field_12"];
						  $lon = $row["field_13"];
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
								$output["count"]++; 
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
	 
	 
	 function alabamaGeo(){
		  
		  $output = array();
		  $output["count"] = 0;
		  $db = $this->startDB();
		  $sql = "SELECT * FROM z_7_6e98f6fc9
		  WHERE 1
		  GROUP BY field_2
		  ";
		  
		  $result = $db->fetchAll($sql, 2);
        if($result){
				foreach($result as $row){
					 //$site = "Site ".trim($row["field_2"]);
					 $site = trim($row["field_2"]);
					 $site = str_replace("_", "-", $site);
					 $uuid = $this->getSiteUUID($site);
					 if($uuid != false){
						  
						  $where = "uuid  = '$uuid' ";
						  $db->delete("geo_space", $where);
						  
						  $lat = $row["field_11"];
						  $lon = $row["field_12"];
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
								$output["count"]++; 
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
	 
	 
	 function moDates(){
		  $output = array();
		  $output["found"] = 0;
		  $spaceTimeObj = new  dataEdit_SpaceTime;
		  $spaceTimeObj->projectUUID = $this->projectUUID;
		  
		  $db = $this->startDB();
		  
		  $sql = "SELECT DISTINCT observe.subject_uuid
		  FROM observe
		  JOIN properties ON observe.property_uuid = properties.property_uuid
		  WHERE properties.variable_uuid = '188E4300-87AA-44E4-CCC7-185032762F40'
		  ";
		  
		  $resultA = $db->fetchAll($sql, 2);
		  if($resultA){
				foreach($resultA as $rowA){
					 $itemUUID = $rowA["subject_uuid"];
					 //$where = "uuid = '$itemUUID' ";
					 //$db->delete("initial_chrono_tag", $where);
					 
					 $sql = "SELECT MAX(grg.start_bp) as tStart, MIN(grg.end_bp) as tEnd, properties.property_uuid, grg.dinaa_uri
					 FROM observe
					 JOIN properties ON observe.property_uuid = properties.property_uuid
					 JOIN val_tab ON val_tab.value_uuid = properties.value_uuid
					 JOIN z_missouri_dates AS grg ON grg.label = val_tab.val_text
					 WHERE properties.variable_uuid = '188E4300-87AA-44E4-CCC7-185032762F40'
					 AND observe.subject_uuid = '$itemUUID'
					 ";
					 
					 $result = $db->fetchAll($sql, 2);
					 if($result){
						  $requestParams = array();
						  $requestParams["uuid"] = $itemUUID;
						  $requestParams["projUUID"] = $this->projectUUID;
						  $requestParams["tStart"] = 1950 - $result[0]["tStart"];
						  $requestParams["tEnd"] = 1950 - $result[0]["tEnd"];
						  $spaceTimeObj->requestParams = $requestParams;
						  $spaceTimeObj->chrontoTagItem();
						  $output["found"]++;
						  
						  $propUUID = $result[0]["property_uuid"];
						  $linkURI = $result[0]["dinaa_uri"];
						  $hashID = sha1($propUUID. $linkURI);
						  $data = array("hashID" => $hashID ,
									 "fk_project_uuid" => $this->projectUUID ,
									 "source_id" => "table" ,
									 "itemUUID" =>   $propUUID ,
									 "itemType" => "property",
									 "linkedLabel" => "",
									 "linkedType" => "type",
									 "linkedURI" => $linkURI
									 );
						  try{
								$db->insert("linked_data", $data);
						  }
						  catch (Exception $e){
								
						  }
						  
					 }
					 else{
						  $output["missing"][] = $itemUUID;
					 }
				}
		  }
		  
		  return $output;
	 }
	 
	 function indianaDates(){
		  $output = array();
		  $output["found"] = 0;
		  $spaceTimeObj = new  dataEdit_SpaceTime;
		  $spaceTimeObj->projectUUID = $this->projectUUID;
		  
		  $periodVarUUID ='38144DED-E43F-4F59-E2C0-8F785197F46D';
		  
		  $db = $this->startDB();
		  
		  $sql = "SELECT DISTINCT observe.subject_uuid
		  FROM observe
		  JOIN properties ON observe.property_uuid = properties.property_uuid
		  WHERE properties.variable_uuid = '$periodVarUUID'
		  ";
		  
		  $resultA = $db->fetchAll($sql, 2);
		  if($resultA){
				foreach($resultA as $rowA){
					 $itemUUID = $rowA["subject_uuid"];
					 //$where = "uuid = '$itemUUID' ";
					 //$db->delete("initial_chrono_tag", $where);
					 
					 $sql = "SELECT MAX(grg.start_bp) as tStart, MIN(grg.end_bp) as tEnd, properties.property_uuid, grg.dinaa_uri
					 FROM observe
					 JOIN properties ON observe.property_uuid = properties.property_uuid
					 JOIN val_tab ON val_tab.value_uuid = properties.value_uuid
					 JOIN z_missouri_dates AS grg ON grg.label = val_tab.val_text
					 WHERE properties.variable_uuid = '$periodVarUUID'
					 AND observe.subject_uuid = '$itemUUID'
					 ";
					 
					 $sql = "SELECT MAX(grg.startBP) as tStart, MIN(grg.endBP) as tEnd, properties.property_uuid
					 FROM observe
					 JOIN properties ON observe.property_uuid = properties.property_uuid
					 JOIN z_indiana_dates AS grg ON grg.propertyUUID = properties.property_uuid
					 WHERE properties.variable_uuid = '$periodVarUUID'
					 AND observe.subject_uuid = '$itemUUID'
					 ";
					 
					 
					 $result = $db->fetchAll($sql, 2);
					 if($result){
						  $requestParams = array();
						  $requestParams["uuid"] = $itemUUID;
						  $requestParams["projUUID"] = $this->projectUUID;
						  $requestParams["tStart"] = 1950 - $result[0]["tStart"];
						  $requestParams["tEnd"] = 1950 - $result[0]["tEnd"];
						  $spaceTimeObj->requestParams = $requestParams;
						  $spaceTimeObj->chrontoTagItem();
						  $output["found"]++;
						  
					 }
					 else{
						  $output["missing"][] = $itemUUID;
					 }
				}
		  }
		  
		  return $output;
	 }
	 
	 function indianageo(){
		  
		  $output = array();
		  $output["count"] = 0;
		  $db = $this->startDB();
		  $sql = "SELECT * FROM z_5_30a61ec52
		  WHERE 1
		  GROUP BY field_1
		  ";
		  
		  $result = $db->fetchAll($sql, 2);
        if($result){
				foreach($result as $row){
					 
					 $site = "Site ".trim($row["field_1"]);
					 $site = str_replace("_", "-", $site);
					 $uuid = $this->getSiteUUID($site);
					 if($uuid != false){
						  
						  $where = "uuid  = '$uuid' ";
						  $db->delete("geo_space", $where);
						  
						  $lat = $row["field_11"];
						  $lon = $row["field_12"];
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
								$output["count"]++; 
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
	 
	 
	 
	 function MOgeo(){
		  
		  $output = array();
		  $db = $this->startDB();
		  $sql = "SELECT * FROM z_4_9b1e456b2
		  WHERE 1
		  GROUP BY field_3
		  ";
		  
		  $result = $db->fetchAll($sql, 2);
        if($result){
				foreach($result as $row){
					 
					 $site = trim($row["field_3"]);
					 $site = str_replace("_", "-", $site);
					 $uuid = $this->getSiteUUID($site);
					 if($uuid != false){
						  
						  $where = "uuid  = '$uuid' ";
						  $db->delete("geo_space", $where);
						  
						  $lat = $row["field_14"];
						  $lon = $row["field_15"];
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
								$output["count"]++; 
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
	 
	 
	 function scDates(){
		  $output = array();
		  $output["found"] = 0;
		  $spaceTimeObj = new  dataEdit_SpaceTime;
		  $spaceTimeObj->projectUUID = $this->projectUUID;
		  
		  $db = $this->startDB();
		  
		  $sql = "SELECT DISTINCT observe.subject_uuid
		  FROM observe
		  JOIN properties ON observe.property_uuid = properties.property_uuid
		  WHERE properties.variable_uuid = 'CB162430-02A5-413F-D036-92E45EC96E50'
		  ";
		  
		  $resultA = $db->fetchAll($sql, 2);
		  if($resultA){
				foreach($resultA as $rowA){
					 $itemUUID = $rowA["subject_uuid"];
					 //$where = "uuid = '$itemUUID' ";
					 //$db->delete("initial_chrono_tag", $where);
					 
					 $sql = "SELECT MAX(grg.beginBP) as tStart, MIN(grg.endBP) as tEnd
					 FROM observe
					 JOIN properties ON observe.property_uuid = properties.property_uuid
					 JOIN val_tab ON val_tab.value_uuid = properties.value_uuid
					 JOIN z_sc_dates AS grg ON grg.label = val_tab.val_text
					 WHERE properties.variable_uuid = 'CB162430-02A5-413F-D036-92E45EC96E50'
					 AND observe.subject_uuid = '$itemUUID'
					 ";
					 
					 $result = $db->fetchAll($sql, 2);
					 if($result){
						  $requestParams = array();
						  $requestParams["uuid"] = $itemUUID;
						  $requestParams["projUUID"] = $this->projectUUID;
						  $requestParams["tStart"] = 1950 - $result[0]["tStart"];
						  $requestParams["tEnd"] = 1950 - $result[0]["tEnd"];
						  $spaceTimeObj->requestParams = $requestParams;
						  $spaceTimeObj->chrontoTagItem();
						  $output["found"]++;
					 }
					 else{
						  $output["missing"][] = $itemUUID;
					 }
				}
		  }
		  
		  return $output;
	 }
	
	 
	 
	 function floridaMissingRepub(){
		  
		  $localPubBaseURI = "http://penelope.oc/publish/publishdoc?projectUUID=".$this->projectUUID."&itemType=space&doUpdate=true&itemUUID=";
			
		  $output = array();
		  $output["redoCount"] = 0;
		  $db = $this->startDB();
		  
		  
		  $sql = "SELECT space.uuid
		  FROM space
		  LEFT JOIN fsites ON space.uuid = fsites.uuid
		  WHERE fsites.uuid IS NULL
		  AND space.project_id = '81204AF8-127C-4686-E9B0-1202C3A47959'";
		  
		  $resultA = $db->fetchAll($sql, 2);
		  foreach($resultA as $rowA){
				$uuid = $rowA["uuid"];
				$resp = file_get_contents($localPubBaseURI.$uuid);
				$output["redone"][$uuid] = Zend_Json::decode($resp);
				
		  }
		  $output["redoCount"] = count($output["redone"]);
		  return $output;
	 }
	 
	 
	 function floridaCountyRepub(){
		  
		  $localPubBaseURI = "http://penelope.oc/publish/publishdoc?projectUUID=".$this->projectUUID."&itemType=space&doUpdate=true&itemUUID=";
			
		  $output = array();
		  $output["redoCount"] = 0;
		  $db = $this->startDB();
		  
		  $url = "http://opencontext/sets/United+States/Florida.json?cat=Site";
		  $jString = file_get_contents($url);
		  $json = Zend_Json::decode($jString );
		  $contexts = $json["facets"]["context"];
		  unset($json );
		  unset($jString );
		  
		  $sql = "SELECT COUNT( uuid ) AS itemCount, field_7
		  FROM  z_ex_florida_sites_main
		  WHERE 1 
		  GROUP BY field_7
		  ORDER BY itemCount DESC";
		  
		  $resultA = $db->fetchAll($sql, 2);
		  foreach($resultA as $rowA){
				$county = $rowA["field_7"];
				$countyCount = $rowA["itemCount"];
				$redoCounty = true;
				foreach( $contexts as $context){
					 if($county == $context["name"]){
						  if($countyCount == $context["count"]){
								$redoCounty = false;
								break;
						  }
					 }
				}
				if($redoCounty){
					 $output["redoCount"] = $output["redoCount"] + $countyCount;
					 $output[$county] = $countyCount;
					 $sql = "SELECT uuid FROM z_ex_florida_sites_main WHERE field_7 = '$county' ";
					 $result = $db->fetchAll($sql, 2);
					 foreach($result as $row){
						  $uuid = $row["uuid"];
						  $urlItem = $url."&q=".$uuid;
						  $jString = file_get_contents($url);
						  $json = Zend_Json::decode($jString );
						  if($json["numFound"] < 1){
								$resp = file_get_contents($localPubBaseURI.$uuid);
								$output["redone"][$uuid] = Zend_Json::decode($resp);
						  }
					 }
				}
				
		  }	
		  return $output;
	 }
	 
	 
	 function georgiaCountyRepub(){
		  
		  $localPubBaseURI = "http://penelope.oc/publish/publishdoc?projectUUID=".$this->projectUUID."&itemType=space&doUpdate=true&itemUUID=";
			
		  $output = array();
		  $output["redoCount"] = 0;
		  $db = $this->startDB();
		  
		  $url = "http://opencontext/sets/United+States/Georgia.json?proj=Georgia+SHPO&cat=Site";
		  $jString = file_get_contents($url);
		  $json = Zend_Json::decode($jString );
		  $contexts = $json["facets"]["context"];
		  unset($json );
		  unset($jString );
		  
		  $sql = "SELECT COUNT( uuid ) AS itemCount, field_7
		  FROM  z_ex_georgia_sites 
		  WHERE 1 
		  GROUP BY field_7
		  ORDER BY itemCount DESC";
		  
		  $resultA = $db->fetchAll($sql, 2);
		  foreach($resultA as $rowA){
				$county = $rowA["field_7"];
				$countyCount = $rowA["itemCount"];
				$redoCounty = true;
				foreach( $contexts as $context){
					 if($county == $context["name"]){
						  if($countyCount == $context["count"]){
								$redoCounty = false;
								break;
						  }
					 }
				}
				if($redoCounty){
					 $output["redoCount"] = $output["redoCount"] + $countyCount;
					 $output[$county] = $countyCount;
					 $sql = "SELECT uuid FROM z_ex_georgia_sites WHERE field_7 = '$county' ";
					 $result = $db->fetchAll($sql, 2);
					 foreach($result as $row){
						  $uuid = $row["uuid"];
						  $urlItem = $url."&q=".$uuid;
						  $jString = file_get_contents($url);
						  $json = Zend_Json::decode($jString );
						  if($json["numFound"] < 1){
								$resp = file_get_contents($localPubBaseURI.$uuid);
								$output["redone"][$uuid] = Zend_Json::decode($resp);
						  }
					 }
				}
				
		  }	
		  return $output;
	 }
	 
	 
	 
	 
	 function floridaGeo(){
		  
		  $output = array();
		  $db = $this->startDB();
		  $sql = "SELECT * FROM z_florida_geo
		  WHERE 1
		  GROUP BY siteID
		  ";
		  
		  $result = $db->fetchAll($sql, 2);
        if($result){
				foreach($result as $row){
					 
					 $site = trim($row["siteID"]);
					 $uuid = $this->getSiteUUID($site);
					 if($uuid != false){
						  $lat = $row["lat"];
						  $lon = $row["lon"];
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
	 
	 
	  function SCgeo(){
		  
		  $output = array();
		  $db = $this->startDB();
		  $sql = "SELECT * FROM z_scarolina_geo
		  WHERE 1
		  GROUP BY siteID
		  ";
		  
		  $result = $db->fetchAll($sql, 2);
        if($result){
				foreach($result as $row){
					 
					 $site = trim($row["siteID"]);
					 $site = str_replace("_", "-", $site);
					 $uuid = $this->getSiteUUID($site);
					 if($uuid != false){
						  
						  $where = "uuid  = '$uuid' ";
						  $db->delete("geo_space", $where);
						  
						  $lat = $row["lat"];
						  $lon = $row["lon"];
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
	 
	 
	 function countyGeo($state){
		  $db = $this->startDB();
		  
		  $vars = array("County name" => "87556028-677C-4C59-BA35-AE509BC825AD",
					  "Gazetteer reference" => "B90CA9CC-B313-4525-8F26-0896AE7D3ED2"
					  );
		  
		  
		  $output = array();
		  $propObj = new dataEdit_Property;
		  $LDobj = new dataEdit_LinkedData;
		  
		  $sql = "SELECT space.uuid, space.space_label, space.project_id
		  FROM space
		  LEFT JOIN geo_space ON geo_space.uuid = space.uuid
		  WHERE space.class_uuid = '34B626E9-5188-427B-C732-6BFFFA998C64'
		  AND space.full_context LIKE 'United States|xx|".$state."%'
		  AND space.space_label != '$state'
		  AND geo_space.uuid IS NULL
		  ORDER BY space.space_label
		  ";
		  
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				$urlBase = self::GeoNamesBaseAPI."searchJSON?formatted=true&maxRows=30&lang=en&style=full&username=".self::GeoUsername;
				foreach($result as $row){
					 $actout = array();
					 $uuid = $row["uuid"];
					 $name = $row["space_label"];
					 $projectUUID = $row["project_id"];
					 $url = $urlBase."&q=".urlencode($name." County")."+".$state;
					 $actout["oc-name"] = $name;
					 $actout["url"] = $url;
					 @$jsonString = file_get_contents($url);
					 if($jsonString != false){
						  $geoAll = Zend_Json::decode($jsonString);
						  if(is_array($geoAll)){
								$geo = $geoAll["geonames"];
								$actout["geo"] = $geo;
								$i = 0;
								$goodGeo = false;
								foreach($geoAll["geonames"] as $checkGeo){
									 if((strtolower($checkGeo["name"]) == strtolower($name." county") || strtolower($checkGeo["toponymName"]) == strtolower($name." county")) && isset($checkGeo["bbox"]) && $checkGeo["fcodeName"] != "airport" && !stristr($checkGeo["toponymName"], "airport")){
										  if(!$goodGeo){
												$goodGeo = $checkGeo;
												break;
										  }
									 }
									 elseif(strtolower($checkGeo["name"]) == strtolower(str_replace("St.", "Saint",$name)." county") && isset($checkGeo["bbox"]) && $checkGeo["fcodeName"] != "airport" && !stristr($checkGeo["toponymName"], "airport")){
										  if(!$goodGeo){
												$goodGeo = $checkGeo;
												break;
										  }
									 }
									 elseif(strtolower($checkGeo["name"]) == strtolower(str_replace(" ", "", $name)." county") && isset($checkGeo["bbox"]) && $checkGeo["fcodeName"] != "airport" && !stristr($checkGeo["toponymName"], "airport")){
										  if(!$goodGeo){
												$goodGeo = $checkGeo;
												break;
										  }
									 }
								}
								
								if(!is_array($goodGeo)){
									 $spName = str_replace(" ", "", $name);
									 $url = $urlBase."&q=".urlencode($spName." County")."+".$state;
									 @$jsonString = file_get_contents($url);
									 if($jsonString != false){
										  $geoAll = Zend_Json::decode($jsonString);
										  if(is_array($geoAll)){
												$goodGeo = false;
												$geo = $geoAll["geonames"];
												$actout["geo"] = $geo;
												foreach($geoAll["geonames"] as $checkGeo){
													 if((strtolower($checkGeo["name"]) == strtolower($name." county") || strtolower($checkGeo["toponymName"]) == strtolower($name." county")) && isset($checkGeo["bbox"]) && $checkGeo["fcodeName"] != "airport" && !stristr($checkGeo["toponymName"], "airport")){
														  if(!$goodGeo){
																$goodGeo = $checkGeo;
																break;
														  }
													 }
													 elseif(strtolower($checkGeo["name"]) == strtolower(str_replace("St.", "Saint",$name)." county") && isset($checkGeo["bbox"]) && $checkGeo["fcodeName"] != "airport" && !stristr($checkGeo["toponymName"], "airport")){
														  if(!$goodGeo){
																$goodGeo = $checkGeo;
																break;
														  }
													 }
													 elseif(strtolower($checkGeo["name"]) == strtolower(str_replace(" ", "", $name)." county") && isset($checkGeo["bbox"]) && $checkGeo["fcodeName"] != "airport" && !stristr($checkGeo["toponymName"], "airport")){
														  if(!$goodGeo){
																$goodGeo = $checkGeo;
																break;
														  }
													 }
												}
										  }
									 }
								}
								
								
								if(is_array($goodGeo)){
									 $countyName = $goodGeo["name"];
									 
									 $propObj->add_obs_varUUID_value($countyName, "87556028-677C-4C59-BA35-AE509BC825AD", $uuid, "Locations or Objects", 1, $projectUUID, 'api.geonames.org');
									 $geoRef = $countyName . " (GeoNames:".$goodGeo["geonameId"].")";
									 $geoURI = self::GeoNamesBaseURI.$goodGeo["geonameId"];
									 $propertyUUID = $propObj->add_obs_varUUID_value($geoRef, "B90CA9CC-B313-4525-8F26-0896AE7D3ED2", $uuid, "Locations or Objects", 1, $projectUUID, 'api.geonames.org'); 
									 $requestParams = array();
									 $requestParams["projectUUID"] = $projectUUID;
									 $requestParams["subjectUUID"] = $propertyUUID;
									 $requestParams["subjectType"] = "property";
									 $requestParams["sourceID"] = "api.geonames.org";
									 $requestParams["predicateURI"] = "type";
									 $requestParams["objectURI"] = $geoURI;
									 $requestParams["objectLabel"] = $countyName;
									 $requestParams["replacePredicate"] = "1";
									 $LDobj->requestParams = $requestParams;
									 $actout["ld"] = $LDobj->addUpdateLinkedData();
									 
									 if(is_array($goodGeo["bbox"])){
										  $lat =  ($goodGeo["bbox"]["south"] + $goodGeo["bbox"]["north"] )/2;
										  $lon =  ($goodGeo["bbox"]["east"] + $goodGeo["bbox"]["west"] )/2;
										  $propObj->add_obs_varUUID_value($countyName, "87556028-677C-4C59-BA35-AE509BC825AD", $uuid, "Locations or Objects", 1, $projectUUID, 'api.geonames.org');
										  $geoRef = $countyName . " (GeoNames:".$goodGeo["geonameId"].")";
										  $geoURI = self::GeoNamesBaseURI.$goodGeo["geonameId"];
										  $propertyUUID = $propObj->add_obs_varUUID_value($geoRef, "B90CA9CC-B313-4525-8F26-0896AE7D3ED2", $uuid, "Locations or Objects", 1, $projectUUID, 'api.geonames.org'); 
										  $requestParams = array();
										  $requestParams["projectUUID"] = $projectUUID;
										  $requestParams["subjectUUID"] = $propertyUUID;
										  $requestParams["subjectType"] = "property";
										  $requestParams["sourceID"] = "api.geonames.org";
										  $requestParams["predicateURI"] = "type";
										  $requestParams["objectURI"] = $geoURI;
										  $requestParams["objectLabel"] = $countyName;
										  $requestParams["replacePredicate"] = "1";
										  $LDobj->requestParams = $requestParams;
										  $ld = $LDobj->addUpdateLinkedData();
										  
										  $data = array("uuid" => $uuid,
														"project_id" => $projectUUID,
														"source_id" => "api.geonames.org",
														"latitude" => $lat,
														"longitude" => $lon,
														"specificity" => -1,
														"note" => "Data from geonames.org"
														);
								
										  try{
												$db->insert("geo_space", $data);
												$actout["geo_space"] = true;
										  }
										  catch(Exception $e){
												$actout["geo_space"] = "Geo for already in"; 
										  }
									 }
									 else{
										  $actout["geo_space"] = "No Bounding Box!!";
									 }//end case with no bounding box
								}//end case with good geo found
								else{
									 $actout["error"] = "No match found";
								}
						  }
					 }
					 $output[$uuid] = $actout;
					 sleep(self::APIsleep);
				}
		  }
		  
		  return $output;
	 }
	 
	 
	 
	 function floridaDateFix(){
		  $output = array();
		  $db = $this->startDB();
		  $sql = "SELECT properties.property_uuid, val_tab.val_text
		  FROM properties
		  JOIN val_tab ON val_tab.value_uuid = properties.value_uuid
		  WHERE properties.variable_uuid = 'EBBEDF7D-46FD-4C44-2C94-A337D4A3B4F1'
		  ";
		  
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				
				$proObj = new dataEdit_Property;
				foreach($result as $row){
					 $propertyUUID = $row["property_uuid"];
					 $excelDate = $row["val_text"]; 
					 $unixDate = ($excelDate - 25569) * 86400;
					 $OCdate = gmdate("Y-m-d", $unixDate);
					 $proObj->updatePropertyValue($OCdate, $propertyUUID);
					 $output[$propertyUUID] = array("excel" => $excelDate, "oc" => $OCdate);
				}	 
		  }
		  
		  return $output;
	 }
	 
	 
	 function floridaDates(){
		  $output = array();
		  $output["found"] = 0;
		  $spaceTimeObj = new  dataEdit_SpaceTime;
		  $spaceTimeObj->projectUUID = $this->projectUUID;
		  
		  $db = $this->startDB();
		  
		  $sql = "SELECT DISTINCT observe.subject_uuid
		  FROM observe
		  JOIN properties ON observe.property_uuid = properties.property_uuid
		  WHERE properties.variable_uuid = '6C9781E9-D3CF-422C-51FB-FAF7A91ABAE0'
		  ";
		  
		  $resultA = $db->fetchAll($sql, 2);
		  if($resultA){
				foreach($resultA as $rowA){
					 $itemUUID = $rowA["subject_uuid"];
					 //$where = "uuid = '$itemUUID' ";
					 //$db->delete("initial_chrono_tag", $where);
					 
					 $sql = "SELECT MIN(fl.startBCE) as tStart, MAX(fl.endBCE) as tEnd
					 FROM observe
					 JOIN properties ON observe.property_uuid = properties.property_uuid
					 JOIN val_tab ON val_tab.value_uuid = properties.value_uuid
					 JOIN z_florida_dates AS fl ON (fl.label = val_tab.val_text OR fl.label_n = val_tab.val_text)
					 WHERE properties.variable_uuid = '6C9781E9-D3CF-422C-51FB-FAF7A91ABAE0'
					 AND observe.subject_uuid = '$itemUUID'
					 ";
					 
					 $result = $db->fetchAll($sql, 2);
					 if($result){
						  $requestParams = array();
						  $requestParams["uuid"] = $itemUUID;
						  $requestParams["projUUID"] = $this->projectUUID;
						  $requestParams["tStart"] = $result[0]["tStart"];
						  $requestParams["tEnd"] = $result[0]["tEnd"];
						  $spaceTimeObj->requestParams = $requestParams;
						  $spaceTimeObj->chrontoTagItem();
						  $output["found"]++;
					 }
					 else{
						  $output["missing"][] = $itemUUID;
					 }
				}
		  }
	 
		  return $output;
	 }
	 
	 
	 function floridaVarTypes(){
		  $db = $this->startDB();
		  $output = array();
		  $varTypes = array("Artifact Category" => "Nominal",
								  "Artifact Disposition" => "Nominal",
								  "Diagnostic Type" => "Nominal",
								 );
		  
		  
		  foreach($varTypes as $varKey => $varType){
				$sql = "SELECT var_tab.variable_uuid
				FROM var_tab
				WHERE var_tab.var_label LIKE '".$varKey."%' AND var_tab.var_label NOT LIKE '%Count%'
				AND var_tab.project_id = '81204AF8-127C-4686-E9B0-1202C3A47959'
				";
				
				$result = $db->fetchAll($sql, 2);
				if($result){
					 foreach($result as $row){
						  $uuid = $row["variable_uuid"];
						  $where = "variable_uuid = '$uuid' ";
						  $data = array("var_type" => $varType);
						  $db->update("var_tab", $data, $where);
						  $output[$uuid] =  $varType;
					 }
				}
		  }
		 
		  return $output;
	 }
	 
	 
	 function floridaDisp(){
		  $db = $this->startDB();
		  $output = array();
		  $output["varTypes"] = $this->floridaVarTypes();
		  
		  $newVals = array("A" => "all of category was collected (Code: \"A\")",
								 "ALL" => "all of category was collected",
								 "OBSV" => "observed by not collected",
								 "SOME" => "items in this category collected",
								 "INFO" => "category reported second hand",
								 "OBCO" => "observed or collected per early FMSF records",
								 "UNSP" => "unspecified on form",
								 "REBU" => "collected and subsequently reburied at the site",
								 "UNKN" => "unknown",
								 "OTHR" => "other"
								 );
		  
		  
		  
		  $sql = "SELECT properties.property_uuid, val_tab.val_text
		  FROM properties
		  JOIN var_tab ON var_tab.variable_uuid = properties.variable_uuid
		  JOIN val_tab ON val_tab.value_uuid = properties.value_uuid
		  WHERE var_tab.var_label LIKE 'Artifact Disposition%'
		  AND properties.project_id = '81204AF8-127C-4686-E9B0-1202C3A47959'
		  ";
		  
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				
				$proObj = new dataEdit_Property;
				foreach($result as $row){
					 $propertyUUID = $row["property_uuid"];
					 $oldVal = $row["val_text"];
					 if(array_key_exists($oldVal, $newVals)){
						  $newValue = $newVals[$oldVal];
						   $output["updated"][] = $proObj->updatePropertyValue($newValue, $propertyUUID);
					 }
					 else{
						  $output["missing"][] = $row;
					 }
				}
		  }
		  
		  return $output;
	 }
	 
	 
	 function floridaVarSort(){
		  $db = $this->startDB();
		  
		  
		  /*
		  $sql = "SELECT * FROM z_3_da588ef3f ORDER BY field_1, id";
		  
		  $result = $db->fetchAll($sql, 2);
		  $prevSiteID = false;
		  $c = 1;
		  $s = 1;
		  foreach($result as $row){
				$id = $row["id"];
				$siteID = $row["field_1"];
				if($prevSiteID != $siteID){
					 if(stristr($row["field_4"], "Culture")){
						  $c = 1;
						  $s = 0;
					 }
					 else{
						  $c = 0;
						  $s = 1;
					 }
					 
					 $field3 = $row["field_3"]." (1)";
					 $field4 = $row["field_4"]." (1)";
				}
				else{
					 if(stristr($row["field_4"], "Culture")){
						  $c++;
						  $field3 = $row["field_3"]." ($c)";
						  $field4 = $row["field_4"]." ($c)";
					 }
					 if(stristr($row["field_4"], "Site")){
						  $s++; 
						  $field3 = $row["field_3"]." ($s)";
						  $field4 = $row["field_4"]." ($s)";
					 }
				}
				$prevSiteID = $siteID;
				$data = array("field_3" => $field3,
								  "field_4" => $field4
								  );
				$where = "id = $id ";
				$db->update("z_3_da588ef3f", $data, $where);
		  }
		  */
		  
		  
		  
		  $output = array();
		  $sources = array("z_3_489edaa4d" => 1,
								 "z_3_da588ef3f" => 1000,
								 "z_3_44a78fbb5" => 5000,
								 "z_3_22f80063a" => 10000,
								 "z_3_2e33eaf43" => 150
								 );
		  
		  
		  $varBases = array("Diagnostic Type" => 10,
								  "Diagnostic Type Count" => 11,
								"Artifact Category Code" => 10,
								"Artifact Category" => 11,
								"Artifact Disposition" => 12,
								"Culture Code" => 4,
								"Culture" => 5,
								"Site Type Code" => 6,
								"Site Type" => 7
		  );
		  
	
		  foreach($sources as $sourceID => $sourceBaseSort){
				
				$sql = "SELECT * FROM var_tab WHERE source_id = '$sourceID' ";
				
				$result = $db->fetchAll($sql, 2);
				if($result){
					 foreach($result as $row){
						  
						  $uuid = $row["variable_uuid"];
						  $varLabel = $row["var_label"];
						  foreach($varBases as $base => $varBaseOrder){
								if(stristr($varLabel, $base)){
									 $varNum = str_replace($base, "", $varLabel);
									 $varNum = str_replace("(", "", $varNum );				  
									 $varNum = str_replace(")", "", $varNum );
									 $varNum = trim($varNum);
									 if(is_numeric($varNum)){
										  
										  $sort = $sourceBaseSort + $varBaseOrder + (5 * $varNum);
										  $where = "variable_uuid = '$uuid' ";
										  $data = array("sort_order" => $sort);
										  $db->update("var_tab", $data, $where);
										  $output[] = array("varLabel" => $varLabel, "base of" => $base, "varNum" => $varNum, "finalSort"=> $sort );
									 }
									 
								}
						  }
					 }
				
				
				}
		  }
		  
		  return $output;
	 }
	 
	 
	 
	 
	 
	 function georgiaDateFix(){
		  $output = array();
		  $db = $this->startDB();
		  $sql = "SELECT properties.property_uuid, val_tab.val_text
		  FROM properties
		  JOIN val_tab ON val_tab.value_uuid = properties.value_uuid
		  WHERE properties.variable_uuid = '8AEC0EB1-4CA8-46CD-BCF0-327941B87EF3'
		  ";
		  
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				
				$proObj = new dataEdit_Property;
				foreach($result as $row){
					 $propertyUUID = $row["property_uuid"];
					 $oldDate = $row["val_text"]; 
					 $OCdate = date("Y-m-d", strtotime($oldDate));
					 $proObj->updatePropertyValue($OCdate, $propertyUUID);
					 $output[$propertyUUID] = array("old" => $oldDate, "oc" => $OCdate);
				}	 
		  }
		  
		  return $output;
	 }
	 
	 
	 
	 function georgiaDates(){
		  $output = array();
		  $output["found"] = 0;
		  $spaceTimeObj = new  dataEdit_SpaceTime;
		  $spaceTimeObj->projectUUID = $this->projectUUID;
		  
		  $db = $this->startDB();
		  
		  $sql = "SELECT DISTINCT observe.subject_uuid
		  FROM observe
		  JOIN properties ON observe.property_uuid = properties.property_uuid
		  WHERE properties.variable_uuid = '9AD6D837-C89F-4F6F-08C6-34ACB757EDC9'
		  ";
		  
		  $resultA = $db->fetchAll($sql, 2);
		  if($resultA){
				foreach($resultA as $rowA){
					 $itemUUID = $rowA["subject_uuid"];
					 //$where = "uuid = '$itemUUID' ";
					 //$db->delete("initial_chrono_tag", $where);
					 
					 $sql = "SELECT MAX(grg.beginBP) as tStart, MIN(grg.endBP) as tEnd
					 FROM observe
					 JOIN properties ON observe.property_uuid = properties.property_uuid
					 JOIN val_tab ON val_tab.value_uuid = properties.value_uuid
					 JOIN z_georgia_dates AS grg ON grg.label = val_tab.val_text
					 WHERE properties.variable_uuid = '9AD6D837-C89F-4F6F-08C6-34ACB757EDC9'
					 AND observe.subject_uuid = '$itemUUID'
					 ";
					 
					 $result = $db->fetchAll($sql, 2);
					 if($result){
						  $requestParams = array();
						  $requestParams["uuid"] = $itemUUID;
						  $requestParams["projUUID"] = $this->projectUUID;
						  $requestParams["tStart"] = 1950 - $result[0]["tStart"];
						  $requestParams["tEnd"] = 1950 - $result[0]["tEnd"];
						  $spaceTimeObj->requestParams = $requestParams;
						  $spaceTimeObj->chrontoTagItem();
						  $output["found"]++;
					 }
					 else{
						  $output["missing"][] = $itemUUID;
					 }
				}
		  }
		  
		  return $output;
	 }
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 
	
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
