<?php
/*
This looks for media files to associate with space items

*/
class ProjEdits_Media  {
    
	 public $spaceLabelPrefix = "";
	 public $spaceMatchLimit = "1";
	 public $mediaFileBaseURL = "";
	 public $mediaSearchDir = "";
	 public $projectUUID;
	 public $sourceID = "filename id match";
	 
	 
	 public $imageFileCheckLimit = "1";
    public $db;
	 
	 public $fileNumDelimiters = array(" ", "-");
	 public $mediaTypeArray = array(); 
	 
	 
	 function findLinkCreateMedia(){
		  $output = array();
		  $files = $this->directoryToArray($this->mediaSearchDir, true);
		  foreach($files as $filePath){
				$numbers = $this->findNumericValues($filePath);
				$localPath = str_replace($this->mediaSearchDir, "", $filePath);
				$spaceArray = array();
				foreach($numbers as $idNumber){
					 $spaceUUID = $this->spaceIdentifierMatch($idNumber);
					 if($spaceUUID != false){
						  $spaceArray[] = $spaceUUID;
					 }
				}
				
				if(count($spaceArray)>0){
					 //we have matching items!
					 $mediaUUID = $this->createMediaResource($filePath);
					 if($mediaUUID != false){
						  foreach($spaceArray as $originUUID){
								$linkUUID = $this->addLinkingRel($originUUID, "Locations or Objects", $mediaUUID, "Media (various)", "link", $this->projectUUID);
								$output[$localPath][$mediaUUID][] = array("space" => $originUUID, "link" => $linkUUID);
						  }
					 }
					 
				}
				else{
					 $output[$localPath] = "No linked space items found, no media resource created. ";
				}
				unset($spaceArray);
				
		  }
		  
		  return $output;
	 }
	 
	 
	 
	 
	 function createLinkMediaFile($linkedUUIDs, $filePath){
		  
		  if(stristr($filePath, "/")){
				$filePathEx = explode("/", $filePath);
				$file = $filePathEx[(count($filePathEx)-1)];
		  }
		  else{
				$file = $filePath;
		  }
		  
		  
		  
		  
	 }
	 
	 
	 
	 function spaceIdentifierMatch($idNumber){
		  $db = $this->startDB();
		  $output = array();
		  
		  $label = $this->spaceLabelPrefix.$idNumber;
		  $sql = "SELECT uuid
		  FROM space
		  WHERE space_label = '$label'
		  AND (".$this->spaceMatchLimit.")
		  ";
		  
		  $result = $db->fetchAll($sql, 2);
		  if($result){
				return $result[0]["uuid"];
		  }
		  else{
				return false;
		  }
		  
	 }
	 
	 //checks on the availability of media resources on a remote server
	 
	 
	 function imageXMLCheck(){
		  $db = $this->startDB();
		  $output = array();
		  
		  $opts = array('http' =>
				array(
				  'timeout' => 60
				)
		  );
		  $context  = stream_context_create($opts);
		  
		  $sql = "SELECT uuid
		  FROM resource
		  WHERE (".$this->imageFileCheckLimit.")
		  
		  ";
		  
		  $result = $db->fetchAll($sql, 2);
		  foreach($result as $row){
				sleep(.33);
				$itemUUID = $row["uuid"];
				$xmlURL = "http://penelope.oc/xml/media?xml=1&id=".$itemUUID;
				$output[$itemUUID]["url"] = $xmlURL;
				@$xmlString = file_get_contents($xmlURL, false, $context);
				if($xmlString != false){
					 @$xml = simplexml_load_string($xmlString);
					 if($xml){
						  $output[$itemUUID]["ok"] = "Good item";
					 }
					 else{
						  $output[$itemUUID]["errors"][] = "XML error $xmlURL ";
					 }
				}
				else{
					 $output[$itemUUID]["errors"][] = "HTTP errror on $xmlURL ";
				}
		  }
		  return $output;
	 }
	 
	 function imageFileCheck(){
		  $db = $this->startDB();
		  $output = array();
		  
		  $sql = "SELECT *
		  FROM resource
		  WHERE  resource.ia_meta !=  'fixed'
		  AND (".$this->imageFileCheckLimit.")
		  ";
		 
		  $result = $db->fetchAll($sql, 2);
		  foreach($result as $row){
				sleep(.33);
				$itemUUID = $row["uuid"];
				$thumbOK = $this->checkFileOK($row["ia_thumb"]);
				$previewOK = $this->checkFileOK($row["ia_preview"]);
				$fullOK = $this->checkFileOK($row["ia_fullfile"]);
				if(!$thumbOK || !$previewOK || !$fullOK){
					 $sad = array("t" => $thumbOK, "p" => $previewOK, "f" => $fullOK);
					 $output[$itemUUID] = $sad ;
					 $sadSave = Zend_Json::encode($sad );
					 $data = array("ia_meta" => $sadSave);
					 
					 $data = $this->altCapsFiles($row["ia_thumb"], "ia_thumb", $data);
					 $data = $this->altCapsFiles($row["ia_preview"], "ia_preview", $data);
					 $data = $this->altCapsFiles($row["ia_fullfile"], "ia_fullfile", $data);
					 
					 $where = "uuid = '$itemUUID' ";
					 $db->update("resource", $data, $where);
				}
				else{
					 $data = array("ia_meta" => 'fixed');
					 $where = array();
					 $where[] = "uuid = '$itemUUID' ";
					 $where[] = "ia_meta != '' ";
					 $db->update("resource", $data, $where);
					 $output[$itemUUID] = "Images good";
				}
				
		  }//end loop
	 
		  return $output;
	 }//end function
	 
	 
	 function createMediaResource($filePath){
		  
		  if(stristr($filePath, "/")){
				$filePathEx = explode("/", $filePath);
				$file = $filePathEx[(count($filePathEx)-1)];
		  }
		  else{
				$file = $filePath;
		  }
		  
		  
		  $localPath = str_replace($this->mediaSearchDir, "", $filePath);
		  $localPath = str_replace(" ", "%20", $localPath);
		  $thumbURI = $this->mediaFileBaseURL."thumbs".$localPath;
		  $previewURI = $this->mediaFileBaseURL."preview".$localPath;
		  $fullURI = $this->mediaFileBaseURL."full".$localPath;
		  
		  $db = $this->startDB();
		  $itemUUID = GenericFunctions::generateUUID();  
		  $data = array("uuid" => $itemUUID,
							 "project_id" => $this->projectUUID,
							 "source_id" => $this->sourceID,
							 "res_label" => $file,
							 "ia_thumb" => $thumbURI,
							 "ia_preview" => $previewURI,
							 "ia_fullfile" =>  $fullURI 
							 );

		  //get the extenstion make mimetypes
		  if(stristr($file, ".")){
				$mediaTypeArray = $this->mediaTypeArray;
				$fileEx = explode(".", $file);
				$extension = $fileEx[(count($fileEx)-1)];
				$lowerExtension = strtolower(".".$extension);
				if(array_key_exists($lowerExtension, $mediaTypeArray)){
					 $data["res_archml_type"] = $mediaTypeArray[$lowerExtension]["archaeoML"];
					 $data["mime_type"] = $mediaTypeArray[$lowerExtension]["mime"];
				}
		  }				 
		  
		  //check file names / paths
		  if(!$this->checkFileOK($thumbURI)){
				$data = $this->altCapsFiles($thumbURI, "ia_thumb", $data);
		  }
		  if(!$this->checkFileOK($previewURI)){
				$data = $this->altCapsFiles($previewURI, "ia_preview", $data);
		  }
		  if(!$this->checkFileOK($fullURI)){
				$data = $this->altCapsFiles($fullURI, "ia_fullfile", $data);
		  }

		  try{
				$db->insert('resource', $data);
		  }
		  catch (Exception $e) {
				$itemUUID =  false;
		  }
		  return $itemUUID;
	 }
	 
	 
	 
	 //add a linking relation
	 function addLinkingRel($originUUID, $originType, $targetUUID, $targetType, $linkFieldValue, $projectUUID, $dataTableName = 'manual', $obsNum = 1){
        
        $db = $this->startDB();
        
        //add origin and targget for this resource
        $hashLink       = md5($originUUID . '_' . $obsNum . '_' . $targetUUID . '_' . $linkFieldValue);
        
        $sql = "SELECT links.link_uuid
        FROM links
        WHERE links.project_id = '$projectUUID'
        AND links.hash_link = '$hashLink '
        ";
        
        $linkRows = $db->fetchAll($sql, 2);
        if($linkRows ){
            $linkUUID = $linkRows [0]["link_uuid"];
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

				
				
				
            $db->insert("links", $data);
                   
        }//end addition of new object linking
        
        return $linkUUID;
    }

	 //try looking for a file based on alternative capitalizations of ".JPG" (or other extensions)
	 function altCapsFiles($url, $urlType, $data){
		  sleep(.1);
		  
		  $url = str_replace(" ", "", $url);
		  $url = str_replace("%20", "", $url);
		  
		  $urlTest = strtolower($url);
		  $urlOK = $this->checkFileOK($urlTest);
		  
		  if(!$urlOK){
				sleep(.33);
				if(stristr($url, ".")){
					 $urlEx = explode(".", $url);
					 $extension = $urlEx[(count($urlEx)-1)];
					 $lowerExtension = strtolower($extension);
					 $upperExtension = strtoupper($extension);
					 if(strtolower($extension) == $extension){ //lowercase
						  $urlTest = str_replace($lowerExtension, $upperExtension, $url);
					 }
					 else{
						  $urlTest = str_replace($upperExtension, $lowerExtension, $url);
					 }
					 
					 $urlOK = $this->checkFileOK($urlTest);
					 if(!$urlOK){
						  sleep(.33);
						  if(strstr($url, $upperExtension)){
								$urlTest = strtolower($url);
								$urlTest = str_replace($lowerExtension, $upperExtension, $url);
								$urlOK = $this->checkFileOK($urlTest);
						  }
					 }
				}
		  }
		  
		  if($urlOK){
				$data[$urlType] = $urlTest;
		  }
		  
		  return $data;
	 }
	 
	 
	 
	 //checks to see if the file is actually on a remote server
	 function checkFileOK($url){
	 
		  stream_context_set_default(
				array(
					 'http' => array(
						  'method' => 'HEAD'
					 )
				)
		  );
		  $headers = get_headers($url);
		  if ($headers[0] == 'HTTP/1.1 200 OK') {
				return true;
		  }
		  else{
				return false;
		  }
	 }
	 
	 //makes an array of files in a directory, can be recursive
	 
	 //returns an array of numeric values found in a file name or filename and path
	 function findNumericValues($filePath){
		  $output = array();
		  if(stristr($filePath, "/")){
				$filePathEx = explode("/", $filePath);
				$file = $filePathEx[(count($filePathEx)-1)];
		  }
		  else{
				$file = $filePath;
		  }
		  
		  $fileEx = $this->explodeX($this->fileNumDelimiters,  $file);
		  if(!is_array($fileEx)){
				$fileEx = array($file);
		  }
		  
		  foreach($fileEx as $part){
				$number = preg_replace('/[^0-9]/', '', $part);
				if(is_numeric($number)){
					 $output[] = $number;
				}
		  }
		  return $output;
	 }
	 
	 
	 function directoryToArray($directory, $recursive) {
		  $array_items = array();
		  if ($handle = opendir($directory)) {
			  while (false !== ($file = readdir($handle))) {
				  if ($file != "." && $file != "..") {
					  if (is_dir($directory. "/" . $file)) {
						  if($recursive) {
							  $array_items = array_merge($array_items, $this->directoryToArray($directory. "/" . $file, $recursive));
						  }
						  $file = $directory . "/" . $file;
						  $array_items[] = preg_replace("/\/\//si", "/", $file);
					  } else {
						  $file = $directory . "/" . $file;
						  $array_items[] = preg_replace("/\/\//si", "/", $file);
					  }
				  }
			  }
			  closedir($handle);
		  }
		  return $array_items;
	 }
	 
	 function explodeX($delimiters,$string){
		  $return_array = Array($string); // The array to return
		  $d_count = 0;
		  while (isset($delimiters[$d_count])) // Loop to loop through all delimiters
		  {
				$new_return_array = Array(); 
				foreach($return_array as $el_to_split) // Explode all returned elements by the next delimiter
				{
					 $put_in_new_return_array = explode($delimiters[$d_count],$el_to_split);
					 foreach($put_in_new_return_array as $substr) // Put all the exploded elements in array to return
					 {
						  $new_return_array[] = $substr;
					 }
				}
				$return_array = $new_return_array; // Replace the previous return array by the next version
				$d_count++;
		  }
		  return $return_array; // Return the exploded elements
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
