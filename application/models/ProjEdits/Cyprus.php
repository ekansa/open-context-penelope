<?php
/*
This class process a work book full of measurements (as saved in a "fods" xml format from Libre Office)
It's specifically designed to parse through the Catalhoyuk type schema for describing measurements

I'm including it, since it may be useful to adapt to other projects

*/
class ProjEdits_Cyprus  {
    
	 public $projectUUID;
	 public $db;
   
	 public $lastSQL;
	 const unitPrefix = "Unit ";
	 const batchPrefix = "Batch ";
	 const findPrefix = "Artifact ";
	 const imageBaseURL = "http://artiraq.org/static/opencontext/pkap-cyprus/";
	 const kmlDir = "db-export/kml/";
	 
	 
	 function UNdotHandle(){
		  $db = $this->startDB();
		  
		  $output = array();
		  $propertyUUID = "0544FF1F-EC84-4028-6899-41F9ABC5344A";
		  $propObj = new dataEdit_Property;
		  $contObj = new dataEdit_SpaceContain;
		  $pubObj = new dataEdit_Published;

		  
		  $sql = "SELECT DISTINCT space.space_label, obs.subject_uuid, val_tab.val_text
		  FROM observe AS obs
		  JOIN space ON obs.subject_uuid = space.uuid
		  JOIN properties ON obs.property_uuid = properties.property_uuid
		  JOIN val_tab ON val_tab.value_uuid = properties.value_uuid
		  WHERE properties.variable_uuid = '86910B06-0743-4CE9-0882-D9E6ADE3C342'
		  AND val_tab.val_text LIKE '%.%'
		  
		  ";
		  
		  $result = $db->fetchAll($sql);
		  foreach($result as $row){
				$spaceLabel = $row["space_label"];
				$subjectUUID = $row["subject_uuid"];
				$propObj->add_obs_property($propertyUUID, $subjectUUID, "Locations or Objects", 1, $this->projectUUID, 'manual-added');
				$parentUUID = $contObj->getParentItemsByUUID($subjectUUID, false);
				$pubObj->deleteFromPublishedDocsByUUID($parentUUID[0]);
				
				$where = "uuid = '$subjectUUID' ";
				
				$data = array("space_label" => $spaceLabel." (exp)");
				$db->update("space", $data, $where);
				
				$contObj->itemParentUUIDs = array();
				$path = $contObj->makeParentPath($subjectUUID, "|xx|");
				$data = array("full_context" => $path);
				$db->update("space", $data, $where);
				$output[$subjectUUID] = $path;
		  }
		  
		  return $output;
	 }
	 
	 
	 
	 function fixPaths(){
		  $db = $this->startDB();
		  $output = array();
		  $contObj = new dataEdit_SpaceContain;
		  
		  $sql = "SELECT space.uuid
		  FROM space
		  WHERE project_id = '".$this->projectUUID."'
		 
		  ";
		  
		  $result =  $db->fetchAll($sql);
		  foreach($result as $row){
				$uuid = $row["uuid"];
				$contObj->itemParentUUIDs = array();
				$path = $contObj->makeParentPath($uuid, "|xx|");
				$where = "uuid = '$uuid' ";
				$data = array("full_context" => $path);
				$db->update("space", $data, $where);
				$output[$uuid] = $path;
		  }
		  
		  return $output;
	 }
	 
	 
	 
	 function getFindsImages($directory, $subDirectories){
		  $output = array();
		  if(!is_array($subDirectories)){
				$subDirectories = array( 0=> $subDirectories);
		  }
		  
		 
		  $imgObj = new Images_ThumbPreviewSize;
		  foreach($subDirectories as $subDirectory){
				
				$fileArray = $imgObj->directoryToArray($directory."\\".$subDirectory."\\", true);
				foreach($fileArray as $pathfile){
					 $newMediaUUID = false;
					 $mediaResponse = false;
					 $pathfileEx = explode("/", $pathfile);
					 $file = $pathfileEx[1];
					 if(stristr($file, ".jpg")){
						  $UCfile = strtoupper($file);
						  $fileID  = str_replace(".JPG", "", $UCfile);
						
						  if(stristr($fileID, "_")){
								
								$fileIDex = explode("_", $fileID);
								$rawUnit = $fileIDex[0];
								$unitID = str_replace("[", "", $rawUnit);
								$unitID = str_replace("]", "", $unitID);
								
								$rawBatch = $fileIDex[1];
								$rawBatch = str_replace("[", "_", $rawBatch);
								$rawBatch = str_replace("]", "_", $rawBatch);
								$rawBatch = str_replace("-", "_", $rawBatch);
								
								/*
								$letter = 65; //A
								while($letter <=90){
									 $rawBatch = str_replace(chr($letter), "", $rawBatch);
									 $letter++;
								}
								*/
								
								$rawBatchEx = explode("_", $rawBatch);
								$batchIDLetter = $rawBatchEx[0];
								$batchID =  preg_replace("/[^0-9]/", "", $batchIDLetter);
								
								$sqls = array();
								$spaceUUID = $this->getBatchUUID($unitID, $batchID);
								$mediaResponse = false;
								$newMedia = array();
								$requestParams = array();
								if($spaceUUID != false){
									 $newMediaUUID = GenericFunctions::generateUUID();
									 $requestParams = array("newUUID" => $newMediaUUID,
																	"projUUID" => $this->projectUUID,
																	"sourceID" => "directory scan",
																	"fullfile" => self::imageBaseURL."full/".$subDirectory."/".$file,
																	"preview" => self::imageBaseURL."preview/".$subDirectory."/".$file,
																	"thumb" => self::imageBaseURL."thumbs/".$subDirectory."/".$file,
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
									 
									 $newMedia[$spaceUUID] =  array("type" => "Batch",
																			  "mediaUUID" => $newMediaUUID,
														"mediaResp" => $mediaResponse);
								}
								else{
									 $sqls[] = $this->lastSQL;
								}
								
								$findUUID = $this->getFindUUID($unitID, $rawUnit, $batchID, $batchIDLetter);
								if($findUUID  != false){
									 if(!$spaceUUID){
										  $newMediaUUID = GenericFunctions::generateUUID();
										  $requestParams = array("newUUID" => $newMediaUUID,
																		 "projUUID" => $this->projectUUID,
																		 "sourceID" => "directory scan",
																		 "fullfile" => self::imageBaseURL."full/".$subDirectory."/".$file,
																		 "preview" => self::imageBaseURL."preview/".$subDirectory."/".$file,
																		 "thumb" => self::imageBaseURL."thumbs/".$subDirectory."/".$file,
																		 "label" => $fileID,
																		 "filename" => $file,
																		 "actItemUUID" => $newMediaUUID,
																		 "actItemType" => "media",
																		 "linkedItemPosition" => "origin",
																		 "linkedUUID" => $findUUID ,
																		 "linkedItemType" => "subject"
																		 );
										  $mediaObj = new dataEdit_Media;
										  $mediaObj->requestParams = $requestParams;
										  $mediaResponse = $mediaObj->createMediaItem();
										  unset($mediaObj);
										  $newMedia[$findUUID] =  array("type" => "Find",
																				  "mediaUUID" => $newMediaUUID,
															 "mediaResp" => $mediaResponse);
									 }
									 else{
										  //just add a link then, using the request params from the creation of the media resource and the findsUUID
										  $requestParams["linkedUUID"] = $findUUID;
										  $linkObj = new dataEdit_Link;
										  $linkObj->requestParams = $requestParams;
										  $newMedia[$findUUID] = $linkObj->createItemLinkingRel($newMediaUUID, "Media (various)");
									 }
								}
								else{
									 $sqls[] = $this->lastSQL;
								}
								
								$output[$subDirectory][] = array("file" => $file,
														"fileID" => $fileID,
														"media" => $newMedia,
														"sqls" => $sqls
														);
								
								unset($sqls);
						  
						  }
					 }
				}
		  
		  }
		  return $output;
	 }
	 
	 
	 
	 function getImages($directory, $subDirectory){
		  $output = array();
		 
		  $imgObj = new Images_ThumbPreviewSize;
		  $fileArray = $imgObj->directoryToArray($directory."\\".$subDirectory."\\", true);
		  foreach($fileArray as $pathfile){
				$newMediaUUID = false;
				$mediaResponse = false;
				$pathfileEx = explode("/", $pathfile);
				$file = $pathfileEx[1];
				if(stristr($file, ".jpg")){
					 
					 $fileEx = explode(".", $file);
					 $fileID = $fileEx[0];
					 if(stristr($fileID, "_")){
						  $fileIDex = explode("_", $fileID);
						  $unitID = $fileIDex[0];
						  
						  $spaceUUID = $this->getObjectUUID($unitID);
						  if($spaceUUID != false){
								$newMediaUUID = GenericFunctions::generateUUID();
								$requestParams = array("newUUID" => $newMediaUUID,
															  "projUUID" => $this->projectUUID,
															  "sourceID" => "directory scan",
															  "fullfile" => self::imageBaseURL."full/".$subDirectory."/".$file,
															  "preview" => self::imageBaseURL."preview/".$subDirectory."/".$file,
															  "thumb" => self::imageBaseURL."thumbs/".$subDirectory."/".$file,
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
				}
		  }
		  
		  
		  return $output;
	 }
	 
	 
	 function getKmz($directory){
		  $output = array();
		 
		  $imgObj = new Images_ThumbPreviewSize;
		  $spaceTimeObj = new dataEdit_SpaceTime;
		  
		  $fileArray = $imgObj->directoryToArray($directory, true);
		  iconv_set_encoding("internal_encoding", "UTF-8");
		  iconv_set_encoding("output_encoding", "UTF-8");
		  
		  foreach($fileArray as $pathfile){
				
				if(stristr($pathfile, ".kmz")){
					 $pathfileEx = explode("/", $pathfile);
					 $file = $pathfileEx[1];
					 $fileEx = explode(".", $file);
					 $fileID = $fileEx[0];
					
					 $pathfileKML = self::kmlDir.$fileID;
					 $this->extractZip($pathfile, $pathfileKML);
					 $kmlFile = $pathfileKML."/doc.kml";
					 $kmlRaw = '';
					 $rHandle = fopen($kmlFile, 'r');
					 if ($rHandle){
						  while(!feof($rHandle)){
								$kmlRaw .= fread($rHandle, filesize($kmlFile));
						  }
						  fclose($rHandle);
						  unset($rHandle);
					 }
					 
					 $spaceTimeParams = false;
					 $geoData = false;
					 $uuid = $this->getObjectUUID($fileID);
					 @$kml = simplexml_load_string($kmlRaw);
					 if($uuid != false && $kml != false){
						  
						  unset($kml);
						  $kmlDom = new DOMDocument();
						  $kmlDom->loadXML($kmlRaw);
						  $geom = $kmlDom->getElementsByTagName('Polygon');
						  $kml = $kmlDom->saveXML($geom->item(0));
						  
						  $spaceTimeParams = array("uuid" => $uuid,
															"projUUID" => $this->projectUUID,
															"geoKML" => $kml
															);
						  
						  $spaceTimeObj->projectUUID = $this->projectUUID;
						  $spaceTimeObj->requestParams = $spaceTimeParams;
						  $geoData = $spaceTimeObj->geoTagItem($uuid, "manual");
					 }
					 
					 $output[$file] = array("kmlData" => $spaceTimeParams, "geoData" => $geoData);
				}
				if(count($output)>3){
					 //break;
				}
		  
		  }
		  return $output;
	 }
	 
	 
	 function extractZip($src, $dest)
    {
        $zip = new ZipArchive;
        if ($zip->open($src)===true)
        {
            $zip->extractTo($dest);
            $zip->close();
            return true;
        }
		  else{
				echo "sucks!";
				die;
		  }
        return false;
    }
	 
	 
	 function getFindUUID($unitID, $rawUnit, $batchID, $batchLetter){
		  $output = false;
		  $db = $this->startDB();
		  $unitLabel = self::unitPrefix.$unitID;
		  $batchLabel = self::batchPrefix.$batchID;
		  
		  $artifactLabel = self::findPrefix.$rawUnit.".".$batchLetter;
		  
		  $sql = "SELECT uuid, project_id
		  FROM space
		  WHERE space_label = '$artifactLabel'
		  AND full_context LIKE '%". $unitLabel."|xx|".$batchLabel."%'
		  LIMIT 1;
		  ";
		  $this->lastSQL = $sql;
		  $result = $db->fetchAll($sql, 2);
        if($result){
				$output =  $result[0]["uuid"];
				if(!$this->projectUUID){
					 $this->projectUUID = $result[0]["project_id"];
				}
		  }
		  
		  return $output;
		  
	 }
	 
	 function getBatchUUID($unitID, $batchID){
		  $output = false;
		  $db = $this->startDB();
		  $unitLabel = self::unitPrefix.$unitID;
		  $batchLabel = self::batchPrefix.$batchID;
		  
		  $sql = "SELECT uuid, project_id
		  FROM space
		  WHERE space_label = '$batchLabel'
		  AND full_context LIKE '%". $unitLabel."|xx|".$batchLabel."'
		  LIMIT 1;
		  ";
		  
		  $this->lastSQL = $sql;
		  $result = $db->fetchAll($sql, 2);
        if($result){
				$output =  $result[0]["uuid"];
				if(!$this->projectUUID){
					 $this->projectUUID = $result[0]["project_id"];
				}
		  }
		  
		  return $output;
		  
	 }
	 
	
	 //get links to uuid 
	 function getObjectUUID($fileID){
		  $output = false;
		  $db = $this->startDB();
		  
		  $itemID = self::unitPrefix.$fileID;
		  
		  $sql = "SELECT uuid, project_id
		  FROM space
		  WHERE space_label = '$itemID'
		  LIMIT 1;
		  ";
		  
		  $result = $db->fetchAll($sql, 2);
        if($result){
				$output =  $result[0]["uuid"];
				if(!$this->projectUUID){
					 $this->projectUUID = $result[0]["project_id"];
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
