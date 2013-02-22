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
		  $classUUID = "A2017643-0086-4D98-4932-E4AD3884E99D"; //pottery
		  
		  $page = 1;
		  $setSize = 1500;
		  
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
		  $limitingProjArray = array("1B426F7C-99EC-4322-4069-E8DBD927CCF1");
		  
		  $tableObj = new TabOut_Table;
		  $tableObj->setSize = $setSize;
		  $tableObj->page = $page;
		  //$tableObj->limitingProjArray = $limitingProjArray;
		  $tableObj->showSourceFields = true;
		  $tableObj->showBP = false;
		  $tableObj->showLDSourceValues = true;
		  $tableObj->sortForSourceVars = " var_tab.sort_order, sCount DESC, var_tab.var_label ";
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