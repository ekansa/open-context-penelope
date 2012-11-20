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
	
		  $id = $_REQUEST['varUUID'];
		  if(isset($_REQUEST['sort'])){
				$sort = " val_tab.val_text, count(observe.subject_uuid) DESC ;";
		  }
		  else{
				$sort = " count(observe.subject_uuid) DESC, val_tab.val_text DESC ;";
		  }
		  
		  $this->view->varUUID = $id;
		  
		  $sql = "SELECT var_tab.project_id, var_tab.var_label, linked_data.linkedLabel, linked_data.linkedURI
		  FROM var_tab
		  LEFT JOIN linked_data ON var_tab.variable_uuid = linked_data.itemUUID
		  WHERE var_tab.variable_uuid = '$id'
		  ";
		  
		  $resultA =  $db->fetchAll($sql);
		  $this->view->varURI = $resultA[0]["linkedURI"];
		  $this->view->projUUID = $resultA[0]["project_id"];
		  $this->view->varLinkLabel = $resultA[0]["linkedLabel"];
		  
				 $sql = "SELECT var_tab.var_label,
				  val_tab.val_text,
				  properties.property_uuid,
				  count(observe.subject_uuid) as subCount,
				  properties.project_id,
				  linked_data.linkedLabel,
				  linked_data.linkedURI
		  FROM properties
		  JOIN val_tab ON val_tab.value_uuid = properties.value_uuid
		  JOIN var_tab ON var_tab.variable_uuid = properties.variable_uuid
		  JOIN observe ON properties.property_uuid = observe.property_uuid
		  LEFT JOIN linked_data ON properties.property_uuid = linked_data.itemUUID
		  WHERE properties.variable_uuid = '$id'
		  GROUP BY observe.property_uuid
		  ORDER BY $sort
				 ";
		  
		  $results =  $db->fetchAll($sql);
		  $this->view->data = $results ;
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
			  Zend_Loader::loadClass('linkedData_units');
			  $unitsObj = new linkedData_units;
			  $unit = $unitsObj->get_unit_from_abrev($linkedAbrev);
			  if($unit != false){
				  $linkedType = "unit";
				  $linkedLabel = $unit["name"];
				  $linkURI = $unit["uri"];
			  }
		  }
		  
		  if(!$unit){
			  //search based on label (user can enter 'mm' and assign to millimeters)
			  Zend_Loader::loadClass('linkedData_units');
			  $unitsObj = new linkedData_units;
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
			  $headerLink = "var?varUUID=".$varUUID;
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
		  
		  $headerLink = "var?varUUID=".$varUUID;
		  header("Location: $headerLink");
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
	 
	 
	 function bmAction(){
		  $this->_helper->viewRenderer->setNoRender();
		  
		  if(isset($_REQUEST["q"])){
				$keyword = $_REQUEST["q"];
		  }
		  else{
				$keyword = "spindle whorl";
		  }
		  
		  Zend_Loader::loadClass('linkedData_BritishMuseum');
		  Zend_Loader::loadClass('linkedData_ApproximateSearch');
		  Zend_Loader::loadClass('Zend_Cache');
		  Zend_Loader::loadClass('Zend_Json');
		  
		  $BMobj = new linkedData_BritishMuseum;
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
	 
	 
    
    
}