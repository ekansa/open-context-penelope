<?php

/** Zend_Controller_Action */
//require_once 'Zend/Controller/Action.php';
//require_once 'OpenContext/Controller/Action/Helper/SolrAccess.php';

//error_reporting(E_ALL ^ E_NOTICE);
// increase the memory limit
ini_set("memory_limit", "6024M");
// set maximum execution time to no limit
ini_set("max_execution_time", "0");

class ExportController extends Zend_Controller_Action {
    
    function init()
    {  
        require_once 'App/Util/GenericFunctions.php';
    }
    
	 
	 //get all table - record associations {
	  function penToOcAction(){
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('DBexport_PenToOC');
		  
		  $projects = array('D42FC0EB-61B0-4937-700E-4EFEAB008677');
		  //$projects = false;
		  
		  $exportObj = new DBexport_PenToOC;
		  $exportObj->limitingProjArray = $projects;
		  $exportObj->makeSaveSQL();
		  $output = array("done");
		  
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_Json::encode($output);
		  
	 }
	 
	 
	 
	 
}//end class