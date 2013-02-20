<?php

/** Zend_Controller_Action */
//require_once 'Zend/Controller/Action.php';
//require_once 'OpenContext/Controller/Action/Helper/SolrAccess.php';

//error_reporting(E_ALL ^ E_NOTICE);
// increase the memory limit
ini_set("memory_limit", "2024M");
// set maximum execution time to no limit
ini_set("max_execution_time", "0");

class TableController extends Zend_Controller_Action {
    
    function init()
    {  
        require_once 'App/Util/GenericFunctions.php';
    }
    
	 
	 
	  //load up old space data from XML documents
	 function classAction(){
		          
        
        //class of items to export in a table
        $classUUID = "881CEDA3-C445-4C9C-4D4B-634BD2963892"; //animal bone
		  $page = 1;
		  $setSize = 500;
		  
        if(isset($_REQUEST["classUUID"])){
				$classUUID = $_REQUEST["classUUID"];
		  }
		  if(isset($_REQUEST["page"])){
				$page = $_REQUEST["page"];
		  }
		  if(isset($_REQUEST["setSize"])){
				$setSize = $_REQUEST["setSize"];
		  }
		  $format = "json";
		  if(isset($_REQUEST["format"])){
				$format = $_REQUEST["format"];
		  }
		  
		  Zend_Loader::loadClass('TabOut_Table');
		  $tableArray = false;
		  $linkedFields = false;
		  $limitingProjArray = array("731B0670-CE2A-414A-8EF6-9C050A1C60F5");
		  
		  $tableObj = new TabOut_Table;
		  $tableObj->setSize = $setSize;
		  $tableObj->page = $page;
		  $tableObj->limitingProjArray = $limitingProjArray;
		  $tableObj->showSourceFields = false;
		  $tableObj->showLDSourceValues = true;
		  $linkedFields = $tableObj->getLinkedVariables($classUUID);
		  $tableArray = $tableObj->makeTableArray($classUUID);
		  
		  if($format == "json"){
				$this->_helper->viewRenderer->setNoRender();
				$output = array("tableData" => $tableArray, "linkedFields" => $linkedFields);
				header('Content-Type: application/json; charset=utf8');
				echo Zend_Json::encode($output);
		  }
		  else{
				$this->view->tableArray = $tableArray;
		  }
	 }
	 

	 





}//end class