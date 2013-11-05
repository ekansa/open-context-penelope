<?php
/*
This class process a work book full of measurements (as saved in a "fods" xml format from Libre Office)
It's specifically designed to parse through the Catalhoyuk type schema for describing measurements

I'm including it, since it may be useful to adapt to other projects

*/
class ProjEdits_Nippur  {
    
	 public $projectUUID;
	 public $db;
   
	 const objectPrefix = "Cat # ";
	 const imageBaseURL = "http://artiraq.org/static/opencontext/nippur-weights/";
	 
	 function getImages($directory){
		  $output = array();
		 
		  $imgObj = new Images_ThumbPreviewSize;
		  $fileArray = $imgObj->directoryToArray($directory, true);
		  foreach($fileArray as $pathfile){
				$newMediaUUID = false;
				$mediaResponse = false;
				$pathfileEx = explode("/", $pathfile);
				$file = $pathfileEx[1];
				$fileEx = explode(".", $file);
				$fileID = $fileEx[0];
				$spaceUUID = $this->getObjectUUID($fileID);
				if($spaceUUID != false){
					 $newMediaUUID = GenericFunctions::generateUUID();
					 $requestParams = array("newUUID" => $newMediaUUID,
													"projUUID" => $this->projectUUID,
													"sourceID" => "directory scan",
													"fullfile" => self::imageBaseURL."full/".$file,
													"preview" => self::imageBaseURL."preview/".$file,
													"thumb" => self::imageBaseURL."thumbs/".$file,
													"label" => $fileID,
													"filename" => $file,
													"actItemUUID" => $newMediaUUID,
													"actItemType" => "media",
													"linkedItemPosition" => "origin",
													"linkedUUID" => $spaceUUID,
													"linkedItemType" => "subject"
													);
					 $mediaObj = new dataEdit_Media;
					 $mediaObj->requestParams = $requestParams;
					 $mediaResponse = $mediaObj->createMediaItem();
					 unset($mediaObj);
				}
				
				
				$output[] = array("file" => $file,
										"fileID" => $fileID,
										"spaceUUID" => $spaceUUID,
										"mediaUUID" => $newMediaUUID,
										"mediaResp" => $mediaResponse
										);
		  }
		  
		  
		  return $output;
	 }
	 
	 
	 
	 
	
	
	 //get links to uuid 
	 function getObjectUUID($fileID){
		  $output = false;
		  $db = $this->startDB();
		  
		  $itemID = self::objectPrefix.$fileID;
		  
		  $sql = "SELECT uuid
		  FROM space
		  WHERE space_label = '$itemID'
		  LIMIT 1;
		  ";
		  
		  $result = $db->fetchAll($sql, 2);
        if($result){
				$output =  $result[0]["uuid"];
		  }
		  else{
				$file = $fileID.".JPG";
				$fileLower = $fileID.".jpg";
				
				$sql = "SELECT obs.subject_uuid
				FROM observe AS obs
				JOIN properties AS props ON obs.property_uuid = props.property_uuid
				JOIN val_tab AS vals ON props.value_uuid = vals.value_uuid
				WHERE (vals.val_text = '$file' OR vals.val_text = '$fileLower')
				AND props.variable_uuid = '40D2A687-6FF0-41D4-2002-FEF79C4496F3'
				LIMIT 1;
				";
				
				//echo $sql;
				//die;
				$result = $db->fetchAll($sql, 2);
				if($result){
					 $output =  $result[0]["subject_uuid"];
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
