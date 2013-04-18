<?php

// increase the memory limit
ini_set("memory_limit", "2048M");
// set maximum execution time to no limit
ini_set("max_execution_time", "0");

class LinkedDataController extends Zend_Controller_Action
{
    
    //public $host = "http://penelope.opencontext.org";
    public $host = "http://penelope2.oc";
    public $counter = 0;
    
	
	function init()
    {  
        $this->view->baseUrl = $this->_request->getBaseUrl();
        require_once 'App/Util/GenericFunctions.php';
        Zend_Loader::loadClass('Zend_Debug');
        Zend_Loader::loadClass('ContextItem');
        Zend_Loader::loadClass('Table_Property');
        Zend_Loader::loadClass('Table_Value');
        Zend_Loader::loadClass('Table_Variable');
        Zend_Loader::loadClass('Table_Observe');
        Zend_Loader::loadClass('Table_Diary');
        Zend_Loader::loadClass('Table_Resource');
        Zend_Loader::loadClass('Table_LinkRelationship');
        Zend_Loader::loadClass('Table_User');
		  Zend_Loader::loadClass('LinkedData_LinkVar');
    }
    
     //make sure all connections are UTF-8 OK
    private function setUTFconnection($db){
		  $sql = "SET collation_connection = utf8_unicode_ci;";
		  $db->query($sql, 2);
		  $sql = "SET NAMES utf8;";
		  $db->query($sql, 2);
    }
    
    private function link_label_Update($label, $linkURI, $projectUUID, $db){
	
		  $where = array();
		  $where[] = "linkedURI = '$linkURI' ";
		  $where[] = "fk_project_uuid = '$projectUUID' ";
		  $data = array("linkedLabel" => $label);
		  $db->update("linked_data", $data, $where);
    }
    
    
    
     function varAction(){
        //$this->_helper->viewRenderer->setNoRender();
        $db = Zend_Registry::get('db');
        $this->setUTFconnection($db);
		  
		  $linkVarObj = new LinkedData_LinkVar;
		  $varUUID = $_REQUEST['varUUID'];
		  if(isset($_REQUEST['sort'])){
				$linkVarObj->alphaSort = $_REQUEST['sort'];
		  }
		  if(isset($_REQUEST['showPropCounts'])){
				$linkVarObj->showPropCounts = $_REQUEST['showPropCounts'];
		  }
		  
		  $this->view->varUUID = $varUUID;
		  $linkVarObj->getProperties($varUUID);
		  $this->view->linkVarObj = $linkVarObj;
    }
    
    function varLinkAction(){
        $this->_helper->viewRenderer->setNoRender();
        $db = Zend_Registry::get('db');
		  $this->setUTFconnection($db);
		  
		  $projectUUID = $_REQUEST['projectUUID'];
		  $varUUID = $_REQUEST['varUUID'];
		  
		  if(isset($_REQUEST['uri'])){
			  $linkURI = $_REQUEST['uri'];
		  }
		  else{
			  $linkURI = false;
		  }
		  
		  if(isset($_REQUEST['linkedLabel'])){
			  $linkedLabel = $_REQUEST['linkedLabel'];
		  }
		  else{
			  $linkedLabel = false;
		  }
		  
		  if(isset($_REQUEST['linkType'])){
			  $linkedType = $_REQUEST['linkType'];
		  }
		  else{
			  $linkedType = "type";
		  }
		  
		  if(isset($_REQUEST['linkedAbrev'])){
			  $linkedAbrev = $_REQUEST['linkedAbrev'];
		  }
		  else{
			  $linkedAbrev = false;
		  }
		  
		  $dir  = true; //redirect back to form
		  if(isset($_REQUEST['dir'])){
			  $dir = false;
		  }
		  
		  $unit = false;
		  //get standard measurement units
		  if($linkedType != "type" && $linkedAbrev != false){
			  Zend_Loader::loadClass('LinkedData_Units');
			  $unitsObj = new LinkedData_Units;
			  $unit = $unitsObj->get_unit_from_abrev($linkedAbrev);
			  if($unit != false){
				  $linkedType = "unit";
				  $linkedLabel = $unit["name"];
				  $linkURI = $unit["uri"];
			  }
		  }
		  
		  if(!$unit){
			  //search based on label (user can enter 'mm' and assign to millimeters)
			  Zend_Loader::loadClass('LinkedData_Units');
			  $unitsObj = new LinkedData_Units;
			  $unit = $unitsObj->get_unit_from_abrev($linkedLabel);
			  if($unit != false){
				  $linkedType = "unit";
				  $linkedLabel = $unit["name"];
				  $linkURI = $unit["uri"];
				  $linkedAbrev = $unit["abrv"];
			  }
		  }
		  
		  
		  $hash = md5($varUUID."_".$linkURI);
		  
		  $where = array();
		  $where[] = "itemUUID = '$varUUID' ";
		  $db->delete("linked_data", $where);
		  
		  $data = array("hashID" => $hash,
					 "fk_project_uuid" => $projectUUID ,
					 "source_id" => "manual" ,
					 "itemUUID" => $varUUID,
					 "itemType" => "variable",
					 "linkedLabel" => $linkedLabel,
					 "linkedType" => $linkedType,
					 "linkedAbrv" => $linkedAbrev,
					 "linkedURI" => $linkURI
					 );
		  
		  if(strlen($linkURI)>2){
			  $db->insert("linked_data", $data);
			  $this->link_label_Update($linkedLabel, $linkURI, $projectUUID, $db);
		  }
		  
		  if($dir){
			  $headerLink = "var?varUUID=".$varUUID."&showPropCounts=".$_REQUEST['showPropCounts'];
			  header("Location: $headerLink");
		  }
		  else{
			  echo "<br/>".$varUUID." is ".$linkedLabel." ($linkURI)";
		  }
    }
    
    
     function propLinkAction(){
        $this->_helper->viewRenderer->setNoRender();
        $db = Zend_Registry::get('db');
		  $this->setUTFconnection($db);
		  $propertyUUID = $_REQUEST['propertyUUID'];
		  $projectUUID = $_REQUEST['projectUUID'];
		  $linkURI = $_REQUEST['uri'];
		  $varUUID = $_REQUEST['varUUID'];
		  $linkedLabel = $_REQUEST['linkedLabel'];
		  $linkedType = "type";
		  
		  //echo $propertyUUID ." is ". $linkURI;
		  
		  $hash = md5($propertyUUID."_".$linkURI);
		  
		  $where = array();
		  $where[] = "hashID = '$hash' ";
		  $db->delete("linked_data", $where);
		  
		  $data = array("hashID" => $hash,
					 "fk_project_uuid" => $projectUUID ,
					 "source_id" => "manual" ,
					 "itemUUID" => $propertyUUID,
					 "itemType" => "property",
					 "linkedLabel" => $linkedLabel,
					 "linkedType" => "type",
					 "linkedURI" => $linkURI
					 );
		  
		  $db->insert("linked_data", $data);
		  $this->link_label_Update($linkedLabel, $linkURI, $projectUUID, $db);
		  
		  $headerLink = "var?varUUID=".$varUUID."&showPropCounts=".$_REQUEST['showPropCounts'];
		  header("Location: $headerLink");
    }
    
	 
	 function deletePropLinkAction(){
        $this->_helper->viewRenderer->setNoRender();
        $db = Zend_Registry::get('db');
		  $this->setUTFconnection($db);
		  $propertyUUID = $_REQUEST['propertyUUID'];
		  $linkURI = $_REQUEST['uri'];
		  
		  $hash = md5($propertyUUID."_".$linkURI);
		  
		  $where = array();
		  $where[] = "hashID = '$hash' ";
		  $db->delete("linked_data", $where);
		  
		  echo "Link $linkURI deleted for $propertyUUID ";
    }
	 
    
    
    //interface to update open context with linked data
    function indexAction(){
		  $this->view->host = $_SERVER['SERVER_NAME']."/";
	
	
    }//end function
    
    
    //get list of properties from OpenContext
    function getPropertyListAction(){
	
		  $this->_helper->viewRenderer->setNoRender();
		  $baseURI = $_REQUEST["baseURI"];
		  
		  header('Content-Type: application/json; charset=utf8');
		  echo file_get_contents($baseURI."/link/get-links");
	
    }
    
     //get list of not done properties from OpenContext
    function notDoneLinksAction(){
	
		  $this->_helper->viewRenderer->setNoRender();
		  $baseURI = $_REQUEST["baseURI"];
		  
		  header('Content-Type: application/json; charset=utf8');
		  echo file_get_contents($baseURI."/link/not-done-links");
	
    }
    
    //prepare a property in OpenContext for linking
    function prepPropertyAction(){
	
		  $this->_helper->viewRenderer->setNoRender();
		  $baseURI = $_REQUEST["baseURI"];
		  $id = $_REQUEST["id"];
		  
		  header('Content-Type: application/json; charset=utf8');
		  echo file_get_contents($baseURI."/link/prep-prop?id=".$id);
	
    }
    
     //process a property in OpenContext for linking
    function processPropertyAction(){
	
		  $this->_helper->viewRenderer->setNoRender();
		  $baseURI = $_REQUEST["baseURI"];
		  $id = $_REQUEST["id"];
		  
		  @$response = file_get_contents($baseURI."/link/link-prop?id=".$id);
		  if(!$response){
				$output = array("propertyUUID" => $id,
					  "varRelations" => false,
					  "valRelations" => false,
					  "numDone" => 10,
					  "errors" => array(0=>"took too long")
					  ); 
				$response = Zend_Json::encode($output);
		  }
		  
		  header('Content-Type: application/json; charset=utf8');
		  echo $response;
    }
    
    
    function updatePropItemsAction(){
	
		  $this->_helper->viewRenderer->setNoRender();
		  $db = Zend_Registry::get('db');
        $this->setUTFconnection($db);
		  $propID = $_REQUEST["id"];
	
		  $sql = "SELECT DISTINCT project_id, subject_uuid
		  FROM observe
		  WHERE property_uuid = '$propID'
		  ";
		  
		  $result =  $db->fetchAll($sql);
		  foreach($result as $row){
				$spaceUUID = $row["subject_uuid"];
				$projectUUID = $row["project_id"];
				$url = "http://penelope2.oc/publish/publishdoc?projectUUID=".$projectUUID."&itemUUID=".$spaceUUID."&itemType=space&doUpdate=true&pubURI=http://opencontext.org/publish/itempublish";
				@$attempt = file_get_contents($url);
				if($attempt){
					 echo "<br/>Success on: ".$spaceUUID;
				}
				else{
					 echo "<br/><strong>Failure on: ".$spaceUUID."</strong>";
				}
		  }
	
    }
	 
	 //get british museum classification / typology term URIs
	 function bmAction(){
		  $this->_helper->viewRenderer->setNoRender();
		  
		  if(isset($_REQUEST["q"])){
				$rawMatch = $_REQUEST["q"];
		  }
		  else{
				$rawMatch = "spindle whorl";
		  }
		  
		  if(strstr($rawMatch, "::")){
				$matchEx = explode("::", $rawMatch);
				$keyword = $matchEx[count($matchEx) - 1];
		  }
		  else{
				$keyword = $rawMatch;
		  }
		  
		  Zend_Loader::loadClass('LinkedData_BritishMuseum');
		  Zend_Loader::loadClass('LinkedData_ApproximateSearch');
		  Zend_Loader::loadClass('Zend_Cache');
		  Zend_Loader::loadClass('Zend_Json');
		  
		  $BMobj = new LinkedData_BritishMuseum;
		  $BMobj->getItemIDsByKeyword($keyword);
		  $BMobj->getTypologyThesaurusLD();
		  
		  $output = array("colExampleURI" => $BMobj->colExampleURI,
								"LDcolExampleURI" => $BMobj->LDcolExampleURI,
								"LDthesaurusURI" => $BMobj->LDthesaurusURI,
								"LDthesaurusLabel" => $BMobj->LDthesaurusLabel,
								"results" => $BMobj->jsonObj,
								"sparql" => $BMobj->sparql
								);
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_Json::encode($output);
		  
	 }
	 
	 
	 //get british museum material term URIs
	 function bmMaterialAction(){
		  $this->_helper->viewRenderer->setNoRender();
		  
		  if(isset($_REQUEST["q"])){
				$rawMatch = $_REQUEST["q"];
		  }
		  else{
				$rawMatch = "silver";
		  }
		  
		  if(strstr($rawMatch, "::")){
				$matchEx = explode("::", $rawMatch);
				$keyword = $matchEx[count($matchEx) - 1];
		  }
		  else{
				$keyword = $rawMatch;
		  }
		  
		  Zend_Loader::loadClass('LinkedData_BritishMuseum');
		  Zend_Loader::loadClass('LinkedData_ApproximateSearch');
		  Zend_Loader::loadClass('Zend_Cache');
		  Zend_Loader::loadClass('Zend_Json');
		  
		  $BMobj = new LinkedData_BritishMuseum;
		  $BMobj->getItemIDsByKeyword($keyword);
		  $BMobj->getMaterialsThesaurusLD();
		  
		  $output = array("colExampleURI" => $BMobj->colExampleURI,
								"LDcolExampleURI" => $BMobj->LDcolExampleURI,
								"LDthesaurusURI" => $BMobj->LDthesaurusURI,
								"LDthesaurusLabel" => $BMobj->LDthesaurusLabel,
								"results" => $BMobj->jsonObj,
								"sparql" => $BMobj->sparql
								);
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_Json::encode($output);
		  
	 }
	 
	 
	 //add British Museum thesaurus terms for object-types
	 function bmLinkTypesAction(){
		  $this->_helper->viewRenderer->setNoRender();
		  
		  Zend_Loader::loadClass('LinkedData_BritishMuseum');
		  Zend_Loader::loadClass('LinkedData_ApproximateSearch');
		  Zend_Loader::loadClass('Zend_Cache');
		  Zend_Loader::loadClass('Zend_Json');
		  
		  $linkVarObj = new LinkedData_LinkVar;
		  $varUUID = $_REQUEST['varUUID'];
		  $linkVarObj->showPropCounts = false;
		  $linkVarObj->getProperties($varUUID);
		  $linkVarObj->BM_link_types();
		  echo "Done, view results <a href='var?varUUID=".$varUUID."'>[HERE]</a>";
	 }
	 
	 
	 //add British Museum thesaurus terms for materials
	 function bmLinkMaterialsAction(){
		  $this->_helper->viewRenderer->setNoRender();
		  
		  Zend_Loader::loadClass('LinkedData_BritishMuseum');
		  Zend_Loader::loadClass('LinkedData_ApproximateSearch');
		  Zend_Loader::loadClass('Zend_Cache');
		  Zend_Loader::loadClass('Zend_Json');
		  
		  $linkVarObj = new LinkedData_LinkVar;
		  $varUUID = $_REQUEST['varUUID'];
		  $linkVarObj->showPropCounts = false;
		  $linkVarObj->getProperties($varUUID);
		  $linkVarObj->BM_link_materials();
		  echo "Done, view results <a href='var?varUUID=".$varUUID."'>[HERE]</a>";
	 }
	 
    //display decimal variables that may need to be linked to unit-types (like a zooarch measurement)
	 function varUnitTypeAction(){
		  //$this->_helper->viewRenderer->setNoRender();
		  
		  Zend_Loader::loadClass('LinkedData_UnitTypeVar');
		  
		  $linkVarObj = new LinkedData_UnitTypeVar;
		  $linkVarObj->projUUID = $_REQUEST['projectUUID'];
		  $linkVarObj->getDecimalVariables();
		  $this->view->linkVarObj = $linkVarObj;
		  //echo print_r( $linkVarObj->variables);
	 }
	 
	  function varUnitTypeAddAction(){
		  $this->_helper->viewRenderer->setNoRender();
		  
		  Zend_Loader::loadClass('LinkedData_UnitTypeVar');
		  
		  $linkVarObj = new LinkedData_UnitTypeVar;
		  $linkVarObj->projUUID = $_REQUEST['projectUUID'];
		  $linkVarObj->addUnitType($_REQUEST);
		  $linkVarObj->getDecimalVariables();
		  $this->view->linkVarObj = $linkVarObj;
		  //return $this->render("varUnitType");
		  
		  $headerLink = "var-unit-type?projectUUID=".$_REQUEST['projectUUID'];
		  header("Location: $headerLink");
		  
		  
	 }
	 
	 function reDoOwlAction(){
		  $this->_helper->viewRenderer->setNoRender();
		   Zend_Loader::loadClass('LinkedData_UnitTypeVar');
		  
		  $linkVarObj = new LinkedData_UnitTypeVar;
		  echo $linkVarObj->redoOWL();
	 }
}