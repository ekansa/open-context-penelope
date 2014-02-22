<?php
/*
This class process a work book full of measurements (as saved in a "fods" xml format from Libre Office)
It's specifically designed to parse through the Catalhoyuk type schema for describing measurements

I'm including it, since it may be useful to adapt to other projects

*/
class ProjEdits_Bade  {
    
	 public $projectUUID = "B4345F6A-F926-4062-144E-3FBC175CC7B6";
	 public $db;
   
	 const objectPrefix = "Cat # ";
	 const imageBaseURL = "http://artiraq.org/static/opencontext/psr-bade/";
	 const sourceID = 'rev-bade';
	 
	 function addSpace(){
		  
		  $output = false;
		  $db = $this->startDB();
		  
		  $sql = "SELECT * FROM z_t_space WHERE uuid != '' ;";
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				$output = array();
				foreach($result as $row){
					 
					 $uuid = $row["uuid"];
					 $sql = "SELECT uuid FROM space WHERE uuid = '$uuid' LIMIT 1; ";
					 $resultB = $db->fetchAll($sql, 2);
					 if(!$resultB){
						  $fc = $row["full_context"];
						  $label = $row["label"];
						  $classUUID = $row["classUUID"];
						  $hashTxt    = md5($this->projectUUID . "_" . $fc);
						  
						  $data = array(
								'project_id'   => $this->projectUUID,
								'source_id'          => self::sourceID,
								'hash_fcntxt'       => $hashTxt,                    // md5($projectUUID . "_" . $fullContextCurrent); 
								'uuid'        => $uuid,                 // generated from uuID function    
								'space_label'       => $label,                 // Bone# 263       
								'full_context'      => $fc,//$fullContextCurrent,                // AM95|xx|Area E-1|xx|Locus 103|xx|1016|xx|Bone# 263 
								'sample_des'        => '',
								'class_uuid'        => $classUUID             // field_summary.class_uuid
							);
						  
						  try{
								$db->insert("space", $data);
								$success = true;
						  } catch (Exception $e) {
								$success = false;
						  }
						  
						  if($success){
								$output[$fc] = $uuid;
						  }
						  else{
								$output[$fc] = false;
						  }
						  
					 }
					 
				}
		  }
		  
		  
		  return $output;
	 }
	 
	 
	 
	 function knownParents($recursive = true){
		  $output = false;
		  $db = $this->startDB();
		  
		  $sql = "SELECT * FROM z_t_space WHERE parentUUID != '' ;";
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				$output = array();
				foreach($result as $row){
					 
					 $fc = $row["full_context"];
					 $parentUUID = $row["parentUUID"];
					 $hashId = $row["hashID"];
					 $oldUUID = $row["uuid"];
					 if(strlen($oldUUID)< 2){
						  $where = "hashID = '$hashId' ";
						  $uuid = GenericFunctions::generateUUID();
						  $hashAll = md5($parentUUID . '_' . $uuid);
						  $data = array(
										  'project_id'   => $this->projectUUID,
										  'source_id'          => self::sourceID,
										  'hash_all'          => $hashAll,                    
										  'parent_uuid'       => $parentUUID ,              
										  'child_uuid'        => $uuid
									  );
							try{
								$db->insert("space_contain", $data);
								$success = true;
						  } catch (Exception $e) {
								$success = false;
						  }
						  
						  unset($data);
						  $data = array("uuid" => $uuid);
						  $db->update("z_t_space", $data, $where);
						  $output[$fc] = $uuid;
					 }
					 else{
						  $uuid = $oldUUID;
					 }
					
					 
					 $sql = "SELECT * FROM z_t_space WHERE parentContext = '$fc' AND parentUUID = '' ";
					 
					 $resultB = $db->fetchAll($sql, 2);
					 if($resultB){
						  foreach($resultB as $rowB){
								$B_hashId = $rowB["hashID"];
								$whereB = "hashID = '$B_hashId' ";
								unset($data);
								$data = array("parentUUID" => $uuid);
								$db->update("z_t_space", $data, $whereB);
						  }
					 }
					 
				}
				
				if($recursive){
					 $rOut = $this->knownParents($recursive);
					 if(is_array($rOut)){
						  foreach($rOut as $fcKey => $uuidVal){
								$output[$fcKey] = $uuidVal;
						  }
					 }
					 else{
						  $recursive = false;
					 }
				}
		  }
		  
		  return $output;
	 }
	 
	 
	 
	 function assignUUIDs(){
		  $output = false;
		  $db = $this->startDB();
		  
		  $sql = "SELECT * FROM z_t_space WHERE uuid = '' ;";
		  
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				$output = array();
				foreach($result as $row){
					 $fc = $row["full_context"];
					 $label = $row["label"];
					 $hashId = $row["hashID"];
					 $where = "hashID = '$hashId' ";
					 
					 $sql = "SELECT uuid FROM space WHERE project_id = '".$this->projectUUID."' AND full_context = '".$fc."' AND space_label = '$label' LIMIT 1; ";
					 $resultB = $db->fetchAll($sql, 2);
					 if($resultB){
						  $uuid = $resultB[0]["uuid"];
						  $parentUUID = false;
						  $sql = "SELECT parent_uuid FROM space_contain WHERE child_uuid = '$uuid' LIMIT 1; ";
						  $resultC = $db->fetchAll($sql, 2);
						  if($resultC){
								$parentUUID = $resultC[0]["parent_uuid"];
						  }
						  
						  $data = array("uuid" => $uuid,
											 "parentUUID" => $parentUUID);
						  $db->update("z_t_space", $data, $where);
						  $output[$fc] = $data;
					 }
					 else{
						  $output[$fc] = "not found";
					 }
					 
					 $parentContextEx = explode("|xx|",$fc);
					 unset($parentContextEx[count($parentContextEx)-1]); //remove the last context
					 $parentContext = implode("|xx|", $parentContextEx);
					 $data = array("parentContext" => $parentContext);
					 $db->update("z_t_space", $data, $where);
					 
				}
		  }
		  
		  return $output;
	 }
	 
	 
	 
	 
	 
	 
	 
	 
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
				$spaceUUID = $this->getObjectUUID($file);
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
	 function getObjectUUID($file){
		  $output = false;
		  $db = $this->startDB();
		  
		  $itemID = 
		  
		  $sql = "SELECT *
		  FROM z_bade_pix
		  WHERE image = '$file' OR imlow = '$file'
		  LIMIT 1;
		  ";
		  
		  $result = $db->fetchAll($sql, 2);
        if($result){
				$itemName =  $result[0]["object"];
				
				$sql = "SELECT uuid FROM space WHERE project_id = '".$this->projectUUID."' AND space_label = '$itemName' LIMIT 1; ";
				$resultB = $db->fetchAll($sql, 2);
				if($resultB){
					 $output = $resultB[0]["uuid"];
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
