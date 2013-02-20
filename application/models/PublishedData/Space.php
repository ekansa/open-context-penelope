<?php

class PublishedData_Space  {
    
    public $itemUUID;
    public $label;
    public $projectUUID;
    public $sourceID;
    public $originType = "Locations or Objects";
	 
    /*
    Location / object specific
    */
    public $classID; //identifier for a class
    public $className; //name for a class
    
    public $containHash;
	 public $contextPath; 
    public $children; //array of child items
   
	 public $baseMediaURI;
	
    public $db;
    public $errors;
	 
	 const fullContextDelimiter = "|xx|"; // deliminator for context paths
	 
	 
	 function addFullSpace($itemXML){
		  
		  $errors = array();
		  $db = $this->startDB();
		  $spaceXML = new dbXML_xmlSpace;
		  $namespaces = $spaceXML->nameSpaces();
		  foreach($namespaces as $prefix => $nsURI){
				$itemXML->registerXPathNamespace($prefix, $nsURI);
		  }
		  $this->addBasic($itemXML);
		  $this->addChildren($itemXML);
		  
		  $obsObj = new PublishedData_Observe;
		  $obsObj->db = $db;
		  $obsObj->itemUUID = $this->itemUUID;
		  $obsObj->projectUUID = $this->projectUUID;
		  $obsObj->sourceID = $this->sourceID;
		  $obsObj->addObservations($itemXML, $this->originType);
		  $errors["obs"] = $obsObj->errors; 
		  unset($obsObj);
		  
		  $propsObj = new PublishedData_Properties;
		  $propsObj->db = $db;
		  $propsObj->itemUUID = $this->itemUUID;
		  $propsObj->projectUUID = $this->projectUUID;
		  $propsObj->sourceID = $this->sourceID;
		  $properties = $propsObj->itemPropsRetrieve($itemXML);
		  $propsObj->saveData($properties);
		  $notes = $propsObj->getNotes($itemXML, $this->originType);
		  $propsObj->saveData($notes);
		  $errors["props"] = $propsObj->errors; 
		  
		  $linksObj = new PublishedData_Links;
		  $linksObj->db = $db;
		  $linksObj->baseMediaURI = $this->baseMediaURI;
		  $linksObj->originUUID = $this->itemUUID;
		  $linksObj->projectUUID = $this->projectUUID;
		  $linksObj->sourceID = $this->sourceID;
		  $linksObj->getAddLinks($itemXML, $this->originType);
		  
		  $this->noteErrors($errors);
	 }
	 
	 
	 
	 
	 
    function addBasic($itemXML){
		  $errors = array();
		  //get item UUID
		  foreach ($itemXML->xpath("/arch:spatialUnit/@UUID") as $xpathResult){
				$this->itemUUID = (string)$xpathResult;
		  }
		
		  //item label
		  foreach ($itemXML->xpath("/arch:spatialUnit/arch:name/arch:string") as $xpathResult){
				$this->label = (string)$xpathResult;
		  }
		
		  //original data source for the item
		  $this->sourceID = "OpenContext";
		  foreach ($itemXML->xpath("//oc:metadata/oc:sourceID") as $xpathResult){
				$this->sourceID = (string)$xpathResult;
		  }
		
		  //project id
		  foreach ($itemXML->xpath("/arch:spatialUnit/@ownedBy") as $xpathResult){
			  $this->projectUUID = (string)$xpathResult;
		  }
		
		  //item class id
		  foreach ($itemXML->xpath("/arch:spatialUnit/oc:item_class/oc:name") as $xpathResult){
			  $space_class = (string)$xpathResult;
			  $this->className = $space_class;
			  $this->classID = $this->classLabelIDGet($space_class);
		  }
		
		  //come up with hash value to insure unique context
		  $default_context_path = "";
		  if($itemXML->xpath("/arch:spatialUnit/oc:context/oc:tree[@id='default']/oc:parent/oc:name")){
			  foreach ($itemXML->xpath("/arch:spatialUnit/oc:context/oc:tree[@id='default']/oc:parent/oc:name") as $path) {
				  $default_context_path .= $path . self::fullContextDelimiter;
			  }	
		  }
		  $default_context_path .= $this->label; //Penelope see state manager line 564
		  $this->contextPath = $default_context_path;
		  $this->containHash = md5($this->projectUUID . "_" . $default_context_path);
		
		  $db = $this->startDB();
		  $data = array("uuid" => $this->itemUUID,
							 "project_id" => $this->projectUUID,
							 "source_id" => $this->sourceID,
							 "hash_fcntxt" => $this->containHash,
							 "space_label" => $this->label,
							 "full_context" => $this->contextPath,
							 "class_uuid" => $this->classID
							 );
		  
		  try{
				$db->insert('space', $data);
		  }
		  catch (Exception $e) {
				$e = (string)$e;
				if(!stristr($e, "SQLSTATE[23000]")){
					 $errors[] = $e;
				}
		  }
		  
		  $this->noteErrors($errors);
	 }//end function
    
	
	 //process children items
	 function addChildren($itemXML){
		  $errors = array();
		  $originUUID = $this->itemUUID;
		  $projectUUID = $this->projectUUID;
		  $sourceID = $this->sourceID;
		  
		  $children = array();
		  $childData = array();
		  foreach ($itemXML->xpath("//oc:children/oc:tree") as $act_tree){
			  
				foreach ($act_tree->xpath("@id") as $act_tree_id){
					$actTreeId = $act_tree_id."";
				}
				
				foreach($act_tree->xpath("oc:child") as $act_child){
				
					 foreach($act_child->xpath("oc:id") as $act_child_result){
						  $actChild_uuid = (string)$act_child_result;
					 }
					 
					 $actChildData = array();
					 $actChildData["project_id"] = $projectUUID;
					 $actChildData["hash_all"] = sha1($originUUID."_".$actChild_uuid);
					 $actChildData["source_id"] = $sourceID;
					 $actChildData["parent_uuid"] = $originUUID;
					 $actChildData["child_uuid"] = $actChild_uuid;
					 $children[] = $actChild_uuid;
					 $childData[] = $actChildData;
					 unset($actChildData);
				}//end loop through children
		  }//end loop through trees
		  
		  $okInserts = 0;
		  $db = $this->startDB();
		  foreach($childData as $conData){
			  
				try{
					 $db->insert('space_contain', $conData);
					 $okInserts++;
				}
				catch (Exception $e) {
					 $e = (string)$e;
					 if(!stristr($e, "SQLSTATE[23000]")){
						  $errors[] = $e;
					 }	
				}
		  }
		  $this->children = $children;
		  $this->noteErrors($errors);
		  return $okInserts ;
 
	 }//end function
	
	 //get the class label
    public function classLabelIDGet($classID){
        $db = $this->startDB();
        
        $sql = "SELECT *
        FROM sp_classes
        WHERE class_uuid LIKE '".$classID."'
        OR class_label LIKE '".$classID."'
        ";
        
        $result = $db->fetchAll($sql, 2);
        if($result){
            return $result[0]["class_uuid"];
        }
    }
	 
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
