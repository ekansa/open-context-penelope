<?php
/*
This is for doing some random edits to space items

*/
class dataEdit_Link  {
    
	 public $requestParams;
	 public $projectUUID;
    public $db;
	 public $errors;
	 
	 
	 public $UItoInternalTypes = array("subject" => "Locations or Objects",
												  "media" => "Media (various)",
												  "document" => "Diary / Narrative",
												  "project" => "Project",
												  "person" => "Person"
												  );
	 
	 
	 //add a new linking relationship for a newly created item or an actively selected item (the $actItemType, $actItemUUID parameters)
	 function createItemLinkingRel($actItemUUID = false, $actItemType = false){
		  
		  $requestParams = $this->requestParams;
		  $errors = array();
		  $linkUUID = false;
		  
		  if(!$actItemUUID || !$actItemType){
				$actValue = $this->checkExistsNonBlank("actItemUUID", $requestParams);
				if($actValue != false){
					 $actItemUUID = $actValue;
				}
				else{
					 $errors[] = "Need a target or origin uuid";
				}
				$actValue = $this->checkExistsNonBlank("actItemType", $requestParams);
				if($actValue != false){
					 $actItemType = $actValue;
				}
				else{
					 $errors[] = "Need a target or origin type";
				}
		  }
		  

		  $actValue = $this->checkExistsNonBlank("linkedItemPosition", $requestParams);
		  if($actValue != false){
				$linkedItemPosition = $actValue;
		  }
		  else{
				$linkedItemPosition = "target"; //default to the linkedUUID being a target
		  }
		  
		  $originUUID = false; //the "linkedUUID" is a target, implying the "newUUID" is the origin
		  $targetUUID = false; 
		  $actLinkedUUID = $this->checkExistsNonBlank("linkedUUID", $requestParams);
		  $actLinkedType = $this->checkExistsNonBlank("linkedItemType", $requestParams);
		  if($actItemUUID != false && $actItemType != false && $actLinkedUUID != false && $actLinkedType != false){
				
				$actItemType = $this->convertUItoInternalType($actItemType);
				$actLinkedType = $this->convertUItoInternalType($actLinkedType);
				
				if($linkedItemPosition == "target"){
					 $originUUID = $actItemUUID; //the "linkedUUID" is a target, implying the $actItemUUID is the origin
					 $originType = $actItemType;
					 $targetUUID = $actLinkedUUID;
					 $targetType = $actLinkedType;
				}
				else{
					 $originUUID = $actLinkedUUID; //the "linkedUUID" is a origin, implying the $actItemUUID is the target
					 $originType = $actLinkedType;
					 $targetUUID = $actItemUUID;
					 $targetType = $actItemType;
				}
		  }
		  else{
				$errors[] = "Need origin and target UUIDs and types";
		  }
		  
		  $actValue = $this->checkExistsNonBlank("projUUID", $requestParams);
		  if($actValue != false){
				$this->projectUUID = $actValue;
		  }
		  else{
				if(!$this->projectUUID){
					 $errors[] = "Need an projUUID";
				}
		  }
		  
		  $actValue = $this->checkExistsNonBlank("sourceID", $requestParams);
		  if($actValue != false){
				$dataTableName = $actValue;
		  }
		  else{
				$dataTableName = "manual";
		  }
		  
		  $actValue = $this->checkExistsNonBlank("linkType", $requestParams);
		  if($actValue != false){
				$linkFieldValue = $actValue;
		  }
		  else{
				$linkFieldValue = "link";
		  }
		  
		  if(count($errors)<1){
				$linkUUID = $this->addLinkingRel($originUUID, $originType, $targetUUID, $targetType, $linkFieldValue, $dataTableName);
				
				if($linkUUID != false){
					 $pubObj = new dataEdit_Published;
					 $pubObj->deleteFromPublishedDocsByUUID($originUUID); //since linking relations are changed, take it off the done publishing list
					 $pubObj->deleteFromPublishedDocsByUUID($targetUUID); //since linking relations are changed, take it off the done publishing list
				}
				
		  }
		  
		  return array("data"=>$linkUUID, "errors" => $errors);
		  
	 }
	 
	 //updates a linking relation
	 function updateLinkRelationType(){
		  
		  $requestParams = $this->requestParams;
		  $where = false;
		  $data = array();
		  $errors = array();
		  
		  $originUUID = false; //the "linkedUUID" is a target, implying the "newUUID" is the origin
		  $targetUUID = false; 
		  $actValue = $this->checkExistsNonBlank("originUUID", $requestParams);
		  if($actValue != false){
				$originUUID  = $actValue;
		  }
		  $actValue = $this->checkExistsNonBlank("targUUID", $requestParams);
		  if($actValue != false){
				$targetUUID  = $actValue;
		  }
		  
		  
		  $actValue = $this->checkExistsNonBlank("linkRelUUID", $requestParams);
		  if($actValue != false){
				$linkRelUUID = $actValue;
				$where = "link_uuid = '$linkRelUUID' ";
		  }
		  else{
				
				//we don't have a link_uuid value, so we need to have a more complicated where condition
				//note that the origin and target uuid's can be mixed up
				
				$linkType = false;
				$actValue = $this->checkExistsNonBlank("linkType", $requestParams);
				if($actValue != false){
					 $linkType = $actValue;
				}
				$where = "(origin_uuid = '$originUUID' AND targ_uuid = '$targetUUID' AND link_type = '".addslashes($linkType)."') OR ";
				$where .= "(targ_uuid = '$originUUID' AND origin_uuid = '$targetUUID' AND link_type = '".addslashes($linkType)."')";
		  }

		  $actValue = $this->checkExistsNonBlank("newLinkType", $requestParams);
		  if($actValue != false){
				$data["link_type"] =  $actValue;
		  }
		  else{
				$errors[] = "Need an updated, newLinkType";
		  }
		  
		  if(count($errors)<1){
				$db = $this->startDB();
				$db->update("links", $data, $where);
				
				$pubObj = new dataEdit_Published;
				$pubObj->deleteFromPublishedDocsByUUID($originUUID); //since linking relations are changed, take it off the done publishing list
				$pubObj->deleteFromPublishedDocsByUUID($targetUUID); //since linking relations are changed, take it off the done publishing list
		  }
		  
		  return array("data"=>$data, "errors" => $errors);
	 }
	 
	 //deletes a linking relation
	 function deleteLink(){
		  
		  $requestParams = $this->requestParams;
		  $where = false;
		  $data = array();
		  $errors = array();
		  
		  $originUUID = false; //the "linkedUUID" is a target, implying the "newUUID" is the origin
		  $targetUUID = false; 
		  $actValue = $this->checkExistsNonBlank("originUUID", $requestParams);
		  if($actValue != false){
				$originUUID  = $actValue;
		  }
		  $actValue = $this->checkExistsNonBlank("targUUID", $requestParams);
		  if($actValue != false){
				$targetUUID  = $actValue;
		  }
		  
		  
		  $actValue = $this->checkExistsNonBlank("linkRelUUID", $requestParams);
		  if($actValue != false){
				$linkRelUUID = $actValue;
				$where = "link_uuid = '$linkRelUUID' ";
		  }
		  else{
				
				//we don't have a link_uuid value, so we need to have a more complicated where condition
				//note that the origin and target uuid's can be mixed up
				
				$linkType = false;
				$actValue = $this->checkExistsNonBlank("linkType", $requestParams);
				if($actValue != false){
					 $linkType = $actValue;
				}
				$where = "(origin_uuid = '$originUUID' AND targ_uuid = '$targetUUID' AND link_type = '".addslashes($linkType)."') OR ";
				$where .= "(targ_uuid = '$originUUID' AND origin_uuid = '$targetUUID' AND link_type = '".addslashes($linkType)."')";
		  }

		  
		  if($where != false){
				$db = $this->startDB();
				$db->delete("links", $where);
				
				$pubObj = new dataEdit_Published;
				$pubObj->deleteFromPublishedDocsByUUID($originUUID); //since linking relations are changed, take it off the done publishing list
				$pubObj->deleteFromPublishedDocsByUUID($targetUUID); //since linking relations are changed, take it off the done publishing list
		  }
		  
		  return array("data"=>$data, "errors" => $errors);
	 }
	 
	 
	 
	 //a crappy function that converts between the itemtypes exposed to the user and the internal itemtypes of the database. 
	 function convertUItoInternalType($itemType){
		  $output = $itemType;
		  $UItoInternalTypes = $this->UItoInternalTypes;
		  if(array_key_exists($itemType, $UItoInternalTypes)){
				$output = $UItoInternalTypes[$itemType];
		  }
		  
		  return $output;
	 }
	 
	 
	 function addLinkingRel($originUUID, $originType, $targetUUID, $targetType, $linkFieldValue, $dataTableName = 'manual', $obsNum = 1){
                
        $db = $this->startDB();
		  $projectUUID = $this->projectUUID;
		  
		  
        //add origin and targget for this resource
        $hashLink       = md5($originUUID . '_' . $obsNum . '_' . $targetUUID . '_' . $linkFieldValue);
        
        $sql = "SELECT links.link_uuid
        FROM links
        WHERE links.project_id = '$projectUUID'
        AND links.hash_link = '$hashLink '
        ";
        
        $linkRows = $db->fetchAll($sql, 2);
        if($linkRows ){
            return false;
        }
        else{
            $linkUUID       = GenericFunctions::generateUUID();                            
            $data = array(
                        'project_id'   => $projectUUID,
                        'source_id'          => $dataTableName,
                        'hash_link'         => $hashLink,
                        'link_type'         => $linkFieldValue,
                        'link_uuid'         => $linkUUID,
                        'origin_type'       => $originType,         
                        'origin_uuid'       => $originUUID,              
                        'origin_obs'        => $obsNum,
                        'targ_type'         => $targetType,        
                        'targ_uuid'         => $targetUUID,         
                        'targ_obs'          => $obsNum 
                    );
                    //Zend_Debug::dump($data);
            $db->insert("links", $data);
                   
        }//end addition of new object linking
        
        return $linkUUID;
    }
	 
	 
	 function checkExistsNonBlank($key, $requestParams){
		  $value = false;
		  if(isset($requestParams[$key])){
				$value = trim($requestParams[$key]);
				if(strlen($value)<1){
					 $value = false;
				}
		  }
		  return $value;
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
