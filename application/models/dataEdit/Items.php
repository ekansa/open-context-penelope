<?php

//fix space entities where the source datatable did not have unique labeling
//and we should not have repeating variables
class dataEdit_Items  {
    
    public $db;
	 public $itemUUID;
	 public $itemType;
	 public $itemTypeJSON;
	 public $itemLabel;
	 public $itemPreview;
	 public $itemXMLdata;
	 public $itemJSONdata;
	 public $projectUUID;
	 public $projectName;
	 public $itemData;
	 public $host;
	 
	 public $requestParams; //parameters sent in a request 
	 
	 public $typeArray = array("diary" => array("id" =>"uuid",
													 "type" => "Diary / Narrative",
													 "json" => "document"
													 ),
							  
                           "users"		=> array(	"id" => "uuid",
																"type" => "Person",
																"json"=> "person"
																),
									
                           "persons"	=> array(	"id"=>"uuid",
																	 "type"=>"Person",
																	 "json"=> "person"
																	 ),
									
                           "resource"	=> array(	"id"=>"uuid",
																	"type"=>"Media (various)",
																	"json"=> "media"
																	),
									
                           "space"  	=> array(	"id"=>"uuid",
																	 "type"=>"Locations or Objects",
																	 "json"=> "space"),
									
                           "properties"   => array("id"=>"property_uuid",
																	"type"=> "Property",
																	"json" => false
																	),
									
                           "var_tab"      => array("id"=>"variable_uuid",
																	"type"=>"Variable",
																	"json" => false
																	),
									
                           "project_list"   => array("id"=>"project_id",
																	  "type" => "Project",
																	  "json" => "project"
																	  )
									
									);
	 
	 
	 
	 const defaultActiveTab = "basicData";
	 const defaultContainDelim = "|xx|";
	 
	 function getItem($itemUUID, $itemType = false){
		  
		  $output = false;
		  if(!$itemType){
				$itemType = $this->itemTypeCheck($itemUUID);
		  }
		  
		  if($itemType != false){
				$this->itemType = $itemType;
				$this->itemUUID = $itemUUID;
				
				if(!$this->itemTypeJSON){
					 $this->itemTypeCheck($itemUUID);
				}
				
				if($this->itemTypeJSON){
					 $this->itemPreview = $this->host."/preview/".$this->itemTypeJSON."?UUID=".$itemUUID;
					 $url = $this->host."/xml/".$this->itemTypeJSON."?id=".$itemUUID;
					 $this->itemJSONdata = $this->host."/xml/".$this->itemTypeJSON."?id=".$itemUUID;
					 $this->itemXMLdata = $this->itemJSONdata."&xml=1";
					 $itemJSON = file_get_contents($url);
					 $this->itemData = Zend_Json::decode($itemJSON);
					 $this->getBasicData();
				}
		  }
		  
		  return $output;
	 }
	 
	 //get basic information from the JSON object of an item
	 function getBasicData(){
		  
		  if($this->itemData){
				$itemData = $this->itemData;
				$this->itemUUID = $itemData["itemUUID"];
				$this->itemLabel = $itemData["label"];
				$this->projectUUID = $itemData["projectUUID"];
				$this->projectName = $itemData["metadataObj"]["projectName"];
		  }
		  
	 }
	 
	 
	 
	 function itemTypeCheck($itemUUID){
        $db = $this->startDB();
        
        $typeArray = $this->typeArray;
        
        $found = false;
        foreach($typeArray AS $table => $typeArray){
            
            if(!$found){
                $sql = "SELECT ".$typeArray["id"]." AS id FROM ".$table." WHERE ".$typeArray["id"]." = '$itemUUID' LIMIT 1";
                $idRows = $db->fetchAll($sql, 2);
                if($idRows){
                    $found = $typeArray["type"];
						  $this->itemTypeJSON = $typeArray["json"];
                }
            }
            
        }
        
        return $found;
        
    }
	 
	 
	 function updateItemLabel($newLabel, $itemUUID, $itemType = false){
		  
		  $newLabel = trim($newLabel);
		  if(strlen($newLabel) > 0){ 
				if(!$itemType){
					 $itemType = $this->itemTypeCheck($itemUUID);
				}
				if(!$this->itemTypeJSON){
					 $this->itemTypeCheck($itemUUID);
				}
				
				if($this->itemTypeJSON == "space"){
					 $this->updateSpaceLabel($newLabel, $itemUUID);
				}
		  }
	 }
	 
	 
	 
	 function updateSpaceLabel($newLabel, $itemUUID){
		  
		  $db = $this->startDB();
		  
		  $sql = "SELECT space_label, full_context
		  FROM space
		  WHERE uuid = '$itemUUID'
		  LIMIT 1;
		  ";
		  
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				$oldLabel = $result[0]["space_label"];
				if($oldLabel != $newLabel){
					 $oldContainPath = $result[0]["full_context"];
					 if(strstr($oldContainPath, self::defaultContainDelim)){
						  $containEx = explode(self::defaultContainDelim, $oldContainPath);
					 }
					 else{
						  $containEx = array( 0 => $oldContainPath);
					 }
					 
					 $lastIndex = count($containEx) - 1;
					 $containEx[$lastIndex] = $newLabel;
					 $newContainPath = implode(self::defaultContainDelim, $containEx);
					 
					 
					 $where = "uuid = '$itemUUID' ";
					 $data = array("space_label" => $newLabel,
										"full_context" => $newContainPath);
					 $db->update("space", $data, $where);
					 
					 $publishedObj = new dataEdit_Published;
					 $publishedObj->deleteFromPublishedDocsByParentUUID($itemUUID); //delete item and all children from published list
					 $publishedObj->deleteFromPublishedDocsByChildUUID($itemUUID); //delete the parent of the changed item from the published doc list, since this carries over
					 
					 $sql = "SELECT uuid, full_context  FROM space WHERE full_context LIKE '".$oldContainPath."%'";
					 $resultUp = $db->fetchAll($sql, 2);
					 if($resultUp){
						  foreach($resultUp as $row){
								$pathItemUUID = $row["uuid"];
								$oldContain = $row["full_context"];
								$newContain = str_replace($oldContainPath, $newContainPath, $oldContain);
								
								$where = "uuid = '$pathItemUUID' ";
								$data = array("full_context" => $newContain);
								$db->update("space", $data, $where);
								$publishedObj->deleteFromPublishedDocsByUUID($pathItemUUID); //just to be sure, delete updated item from published list
						  }
					 }
				
				}//end case where old UUID is different from the new UUID
				
		  }//end case where UUID is found
		  
	 }
	 
	 
	 function updateClassUUID($itemUUID, $classUUID){
		  
		  $db = $this->startDB();
		  
		  $sql = "SELECT class_uuid
				FROM sp_classes
				WHERE class_uuid = '$classUUID'
				LIMIT 1;
		  ";
		  
		  $result = $db->fetchAll($sql);
		  if($result){
				//valid class, so we can update
				$where = "uuid = '$itemUUID' ";
				$data = array("class_uuid" => $classUUID);
				$db->update("space", $data, $where);
				
				$publishedObj = new dataEdit_Published;
				$publishedObj->deleteFromPublishedDocsByParentUUID($itemUUID); //delete item and all children from published list, since this carries over
				$publishedObj->deleteFromPublishedDocsByChildUUID($itemUUID); //delete the parent of the changed item from the published doc list, since this carries over
		  }
		  
	 }
	 
	 
	 
	 
	 //get variable_types represented in the current database
	 function getRepresentedClasses($getCount = false){
		  
		  $db = $this->startDB();
		  $requestParams = $this->requestParams;
		  
		  $projCond  = "";
		  if($this->projectUUID){
				$projCond = $this->makeORcondition($this->projectUUID, "project_id", "space");
				$projCond = " AND (".$projCond.") ";
		  }
		  
		  if($getCount){
				$sql = "SELECT count(space.uuid) AS idCount, sp_classes.class_uuid AS classUUID, sp_classes.class_label AS classLabel
				FROM sp_classes
				LEFT JOIN space ON space.class_uuid = sp_classes.class_uuid
				WHERE 1 $projCond
				GROUP BY space.class_uuid
				ORDER BY idCount DESC, classLabel
				";
		  }
		  else{
				$sql = "SELECT DISTINCT 0 AS idCount, sp_classes.class_uuid AS classUUID, sp_classes.class_label AS classLabel
				FROM sp_classes
				WHERE 1
				ORDER BY classLabel
				";
		  }
		  
		  $result =  $db->fetchAll($sql);
		  return $result;
		  
	 }
	 
	 
	 
	 
	 
	 
	 
	 
	 //makes an OR condition for a given value array, field, and maybe table
	 function makeORcondition($valueArray, $field, $table = false, $like = false){
		  
		  if(!is_array($valueArray)){
				$valueArray = array(0 => $valueArray);
		  }
		  
		  if(!$table){
				$fieldPrefix = $field;
		  }
		  else{
				$fieldPrefix = $table.".".$field;
		  }
		  $allCond = false;
		  foreach($valueArray as $value){
				if(!$like){
					 $actCond = "$fieldPrefix = '$value'";
				}
				else{
					 $value = addslashes($value);
					 $actCond = "$fieldPrefix LIKE '%".$value."%'";
				}
				
				if(!$allCond ){
					 $allCond  = $actCond;
				}
				else{
					 $allCond  .= " OR ".$actCond;
				}
		  }
		  return $allCond ;
	 }
	 
	 
	 
	 
	 
	 
	 
	 
	 //for displaying tabs about an item on the edit page for an item
	 function makeTabLiClass($tabName){
		  
		  $activeTab = self::defaultActiveTab; //set a default active tab
		  $requestParams = $this->requestParams; 
		  
		  if(isset($requestParams["tab"])){
				$activeTab = $requestParams["tab"];
		  }

		  if($tabName == $activeTab){
				return " class=\"active\" ";
		  }
		  else{
				return "";
		  }
	 }
	 
	  //for displaying tabs about an item on the edit page for an item
	 function makeTabDivClass($tabName){
		  
		  $activeTab = self::defaultActiveTab; //set a default active tab
		  $requestParams = $this->requestParams;
		  
		  if(isset($requestParams["tab"])){
				$activeTab = $requestParams["tab"];
		  }
		  
		  if($tabName == $activeTab){
				return " class=\"tab-pane active\" ";
		  }
		  else{
				return " class=\"tab-pane\" ";
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
