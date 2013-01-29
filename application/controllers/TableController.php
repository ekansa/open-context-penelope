<?php

/** Zend_Controller_Action */
//require_once 'Zend/Controller/Action.php';
//require_once 'OpenContext/Controller/Action/Helper/SolrAccess.php';

//error_reporting(E_ALL ^ E_NOTICE);
// increase the memory limit
ini_set("memory_limit", "1024M");
// set maximum execution time to no limit
ini_set("max_execution_time", "0");

class TableController extends Zend_Controller_Action {
    
    function init()
    {  
        require_once 'App/Util/GenericFunctions.php';
    }
    
	 
	 
	  //load up old space data from XML documents
	 function classAction(){
		  
		  //this line is necessary for ajax calls:
        $this->_helper->viewRenderer->setNoRender();        
        
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
		  
		  Zend_Loader::loadClass('TabOut_Table');
		  $tableArray = false;
		  $linkedFields = false;
		  
		  $tableObj = new TabOut_Table;
		  $tableObj->setSize = $setSize;
		  $tableObj->page = $page;
		  $linkedFields = $tableObj->getLinkedVariables($classUUID);
		  $tableArray = $tableObj->makeTableArray($classUUID);
		  
		  $output = array("tableData" => $tableArray, "linkedFields" => $linkedFields);
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_Json::encode($output);
	 }
	 

	 





}//end class