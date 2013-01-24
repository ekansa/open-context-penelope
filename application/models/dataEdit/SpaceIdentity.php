<?php

class dataEdit_SpaceIdentity  {
    
    public $db;
	 public $projUUID;
	 
	 
	 
	 function fixIdentities(){
		  
		  $probItems = $this->getSourceDataIDs();
		  $output = array();
		  foreach($probItems as $probItem){
				
				$itemUUID = $probItem["uuid"];
				$sourceID = $probItem["source_id"];
				$sourceIDs = $probItem["source-ids"];
				$UUIDsources = $this->itemDuplicate($itemUUID, $sourceIDs);
				$repeatedVars = $this->repeatedVariables($itemUUID);
				
				foreach($UUIDsources as $itemKey => $sourceData){
					 
				}
				
				$output[] = array("repeats" => $repeatedVars, "sources" => $UUIDsources);
		  }
		  
		  return $output;
	 }
	 
	 
	 //this finds duplicate variables, removes values not tied to the items source record
	 function cleanProperties($subjectUUID, $sourceID, $sourceRecID){
		  
		  
		  
		  
		  
	 }
	 
	 
	 
	 
	 
	 
	 //duplicate an item and it's observations and links
	 function itemDuplicate($itemUUID, $sourceIDs){
		  
		  $db = $this->startDB();
		  $sql = "SELECT * FROM space WHERE uuid = '$itemUUID' LIMIT 1;";
		  $resA = $db->fetchAll($sql);
		  $originalSpace = $resA[0];
		  
		  $charID = 97; //code for ASCII "a"
		  $i = 0;
		  $UUIDsources = array();
		  foreach($sourceIDs as $sourceItem){
				
				if($i>0){
					 //don't make a new item for the first record, since we already have it. make it for the second record and after
					 $newSpace = $originalSpace;
					 $newUUID = GenericFunctions::generateUUID();
					 $increment = chr($charID);
					 $newSpace["uuid"] = $newUUID;
					 $newSpace["space_label"] = $newSpace["space_label"]."-".$increment;
					 $newSpace["full_context"] = $newSpace["full_context"]."-".$increment;
					 $newSpace["hash_fcntxt"] = $newSpace["hash_fcntxt"]."-".$increment;
					 $newSpace["sample_des"] = Zend_Json::encode(array("src" => $sourceItem, "srcID" => $itemUUID));
					 $itemOK = false;
					 try{
						  //$db->insert("space", $newSpace); //add the new space item, duplicating the old
						  $itemOK = true;
					 }
					 catch (Exception $e) {

					 }
					 if($itemOK){
						  $UUIDsources[$newUUID] = $sourceItem;
						  $this->duplicateItemObs($itemUUID, $newUUID, $increment);
						  $this->duplicateItemLinks($itemUUID, $newUUID, $increment);
					 }
				}
				else{
					 $UUIDsources[$itemUUID] = $sourceItem; 
				}
				
				$i++;
				$charID++;
		  }
		  
		  return $UUIDsources;
	 }
	 
	 
	 
	 //this function is used to duplicate an item's observations
	 function duplicateItemObs($oldUUID, $newUUID, $increment){
		  
		  $db = $this->startDB();
		  $sql = "SELECT * FROM observe WHERE subject_uuid = '$oldUUID' ";
		  
		  $result = $db->fetchAll($sql);
		  foreach($result as $row){
				$data = $row;
				$data["hash_obs"] = $data["hash_obs"]."-".$increment; 
				$data["subject_uuid"] = $newUUID;
				try{
					 //$db->insert("observe", $data); //add list of subject items with multiple of the same var
				}
				catch (Exception $e) {
				
				}
		  }
		  
	 }
	 
	 
	 
	 //this function is used to duplicate an item's linking relations
	 function duplicateItemLinks($oldUUID, $newUUID, $increment){
		  
		  $db = $this->startDB();
		  $sql = "SELECT * FROM links WHERE origin_uuid = '$oldUUID' OR targ_uuid = '$oldUUID' ";
		  
		  $result = $db->fetchAll($sql);
		  foreach($result as $row){
				$data = $row;
				$data["hash_obs"] = $data["hash_link"]."-".$increment;
				$data["link_uuid"] = GenericFunctions::generateUUID();
				if($data["origin_uuid"] == $oldUUID){
					 $data["origin_uuid"] = $newUUID;
				}
				if($data["targ_uuid"] == $oldUUID){
					 $data["targ_uuid"] = $newUUID;
				}
				
				try{
					 //$db->insert("links", $data); //add list of subject items with multiple of the same var
				}
				catch (Exception $e) {
				
				}
		  }
		  
	 }
	 
	 
	 //get an array of the variables that are repeated for a given item
	 function repeatedVariables($itemUUID){
		  $allprops = $this->itemProperties($itemUUID);
		  $varProps = array();
		  foreach($allprops as $prop){
				$varUUID = $prop["variable_uuid"];
				$varProps[$varUUID][] = $prop;
		  }
		  $repeatedVars = array();
		  foreach($varProps as $varKey => $varPropArray){
				if(count($varPropArray)>1){
					 $repeatedVars[$varKey] = $varPropArray;
				}
		  }
		  
		  return $repeatedVars;
	 }
	 
	 
	 
	 
	 
	 //get the properties for a given itemUUID
	 function itemProperties($itemUUID){
		  $db = $this->startDB();
		  
		  $sql = "SELECT properties.variable_uuid, properties.property_uuid,  
				  var_tab.var_label, 
				  IF (
				  val_tab.val_text IS NULL , (
					  IF (
					  properties.val_num =0, properties.val_num, properties.val_num)
					  ), 
					  val_tab.val_text
					  ) AS allprop
			  FROM observe
			  LEFT JOIN properties ON observe.property_uuid = properties.property_uuid
			  LEFT JOIN var_tab ON properties.variable_uuid = var_tab.variable_uuid
			  LEFT JOIN val_tab ON properties.value_uuid = val_tab.value_uuid
			  WHERE observe.subject_uuid = '$itemUUID' 
			  ORDER BY var_tab.sort_order";
		  
		  return $db->fetchAll($sql);
		  
	 }
	 
	 
	 
	 
	 
	 
	 
	 
	 //get data source record IDs
	 function getSourceDataIDs(){
		  $db = $this->startDB();
		  $output = array();
		  
		  $sql = "SELECT * FROM dupsubjects WHERE 1 ";
		  $result =  $db->fetchAll($sql);
		  if($result){
				foreach($result as $row){
					 $itemUUID = $row["uuid"];
					 $itemLabel = $row["label"];
					 $classUUID = $row["class_uuid"];
					 $sourceID = $row["source_id"];
		  
					 $actItem = $row;
					 $actItem["source-ids"] = $this->getSourceIDs($itemLabel, $sourceID, $classUUID);
					 $output[] = $actItem;
				}
		  }
		  
		  return $output;
	 }
	 
	 //stores a list of items that may have identiy problems, where the same variable is used more than once
	 function storeIDsWithDuplicatingVars(){
		  $db = $this->startDB();
		  
		  //clean the table
		  $sql = "TRUNCATE TABLE  dupsubjects";
		  $db->query($sql, 2);
		  
		  $sql = "SELECT variable_uuid
		  FROM var_tab
		  WHERE project_id = '".$this->projUUID."' ";
		  
		  $result =  $db->fetchAll($sql);
		  if($result){
				foreach($result as $row){
					 $varID = $row["variable_uuid"];
					 $sql = "SELECT count(observe.property_uuid) as fCount, observe.subject_uuid,
								space.space_label, space.source_id, space.class_uuid
								FROM observe
								JOIN properties ON observe.property_uuid = properties.property_uuid
								JOIN space ON observe.subject_uuid = space.uuid
								WHERE observe.project_id = '".$this->projUUID."' 
								AND (properties.variable_uuid = '$varID')
								GROUP BY observe.subject_uuid
								ORDER BY fCount DESC";
								
					 $resultB = $db->fetchAll($sql);
					 if($resultB){
						  foreach($resultB as $rowB){
								$itemUUID = $rowB["subject_uuid"];
								$fCount = $rowB["fCount"];
								if($fCount > 1){
									 $data = array("uuid" => $itemUUID);
									 $data["source_id"] = $rowB["source_id"];
									 $data["label"] = $rowB["space_label"];
									 $data["class_uuid"] = $rowB["class_uuid"];
									 try{
										  $db->insert("dupsubjects", $data); //add list of subject items with multiple of the same var
									 }
									 catch (Exception $e) {
							
									 }
								}
								else{
									 break; //done with duplicates
								}
						  }
					 }
				}
		  }
	 }


	 //looks up an item lable, its class, and its source table to get its original source table record ID
	 function getSourceIDs($itemLabel, $sourceID, $classUUID){
		  
		  $db = $this->startDB();
		  
		  //get labeling prefix
		  $sql = "SELECT field_name, field_lab_com
		  FROM field_summary
		  WHERE source_id = '$sourceID'
		  AND fk_class_uuid = '$classUUID'
		  LIMIT 1;
		  ";
		  
		  $resultB = $db->fetchAll($sql);
		  if($resultB){
				$labelCom = $resultB[0]["field_lab_com"];
				$sourceField = $resultB[0]["field_name"];
				
				$originalID  = str_replace($labelCom, "", $itemLabel);
				$originalID = trim($originalID);
				
				$sql = "SELECT id, $sourceField
				FROM $sourceID
				WHERE ".$sourceField." = '$originalID' ";
				
				$resultC = $db->fetchAll($sql);
				if($resultC){
					 return $resultC;
				}
				else{
					 return false;
				}
		  }
		  else{
				return false;
		  }
		  
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
