<?php

class PublishedData_Resource  {
    
    public $itemUUID;
    public $label;
    public $projectUUID;
    public $sourceID;
    public $itemType = "Media (various)";
	 
	 public $thumbURI;
	 public $previewURI;
	 public $fullURI;
   
    public $db;
    public $errors;
	 
	
	 function addFullMedia($itemXML){
		  
		  $errors = array();
		  $db = $this->startDB();
		  $mediaXML = new dbXML_xmlMedia;
		  $namespaces =  $mediaXML->nameSpaces();
		  foreach($namespaces as $prefix => $nsURI){
				$itemXML->registerXPathNamespace($prefix, $nsURI);
		  }
		  $this->addBasic($itemXML);
		 
		  $obsObj = new PublishedData_Observe;
		  $obsObj->db = $db;
		  $obsObj->itemUUID = $this->itemUUID;
		  $obsObj->projectUUID = $this->projectUUID;
		  $obsObj->sourceID = $this->sourceID;
		  $obsObj->addObservations($itemXML, $this->itemType);
		  $errors["obs"] = $obsObj->errors; 
		  unset($obsObj);
		  
		  $propsObj = new PublishedData_Properties;
		  $propsObj->db = $db;
		  $propsObj->itemUUID = $this->itemUUID;
		  $propsObj->projectUUID = $this->projectUUID;
		  $propsObj->sourceID = $this->sourceID;
		  $properties = $propsObj->itemPropsRetrieve($itemXML);
		  $propsObj->saveData($properties);
		  $notes = $propsObj->getNotes($itemXML, $this->itemType);
		  $propsObj->saveData($notes);
		  $errors["props"] = $propsObj->errors; 
		  
		  $linksObj = new PublishedData_Links;
		  $linksObj->db = $db;
		  $linksObj->originUUID = $this->itemUUID;
		  $linksObj->projectUUID = $this->projectUUID;
		  $linksObj->sourceID = $this->sourceID;
		  $linksObj->getAddLinks($itemXML, $this->itemType);
		  
		  $this->noteErrors($errors);
	 }
	 
	 
	 
	 
	 
    function addBasic($itemXML){
		  $errors = array();
		  //get item UUID
		  foreach ($itemXML->xpath("/arch:resource/@UUID") as $xpathResult){
				$this->itemUUID = (string)$xpathResult;
		  }
		
		  //item label
		  foreach ($itemXML->xpath("/arch:resource/arch:name/arch:string") as $xpathResult){
				$this->label = (string)$xpathResult;
		  }
		
		  //original data source for the item
		  $this->sourceID = "OpenContext";
		  foreach ($itemXML->xpath("//oc:metadata/oc:sourceID") as $xpathResult){
				$this->sourceID = (string)$xpathResult;
		  }
		
		  //project id
		  foreach ($itemXML->xpath("/arch:resource/@ownedBy") as $xpathResult){
			  $this->projectUUID = (string)$xpathResult;
		  }
		
		  //item full uri
		  foreach ($itemXML->xpath("//arch:externalFileInfo/arch:resourceURI") as $xpathResult){
			  $fullURI = (string)$xpathResult;
			  $this->fullURI = $fullURI;
		  }
		  //preview
		  foreach ($itemXML->xpath("//arch:externalFileInfo/arch:previewURI") as $xpathResult){
				$previewURI = (string)$xpathResult;
				$this->previewURI = $previewURI;
		  }
		  //thumbnail
		  foreach ($itemXML->xpath("//arch:externalFileInfo/arch:thumbnailURI") as $xpathResult){
				$thumbURI = (string)$xpathResult;
				$this->thumbURI = $thumbURI;
		  }
		
		  $db = $this->startDB();
		  $data = array("uuid" => $this->itemUUID,
							 "project_id" => $this->projectUUID,
							 "source_id" => $this->sourceID,
							 "res_label" => $this->label,
							 "ia_thumb" => $this->thumbURI,
							 "ia_preview" => $this->previewURI,
							 "ia_fullfile" => $this->fullURI 
							 );
		  
		  try{
				$db->insert('resource', $data);
		  }
		  catch (Exception $e) {
				$e = (string)$e;
				if(!stristr($e, "SQLSTATE[23000]")){
					 $errors[] = $e;
				}
				//echo "Bad: ".$this->itemUUID." ".$e;
				//die;
		  }
		  
		  $this->noteErrors($errors);
	 }//end function
    
	
	 
	 function noteErrors($errors){
		  if(is_array($errors)){
				if(count($errors)>0){
					 if(!is_array($this->errors)){
						  $this->errors = $errors;
					 }
					 else{
						  $allErrors = $this->errors;
						  foreach($errors as $newError){
								$allErrors[] = $newError;
						  }
						  $this->errors = $allErrors;
					 }
				}
		  }
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
