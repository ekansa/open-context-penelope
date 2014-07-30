<?php

/** Zend_Controller_Action */
//require_once 'Zend/Controller/Action.php';
//require_once 'OpenContext/Controller/Action/Helper/SolrAccess.php';

//error_reporting(E_ALL ^ E_NOTICE);
// increase the memory limit
ini_set("memory_limit", "1024M");
// set maximum execution time to no limit
ini_set("max_execution_time", "0");

class PyexController extends Zend_Controller_Action {
    
    function init()
    {  
        require_once 'App/Util/GenericFunctions.php';
    }
	
	function getRecordsAction(){
		$this->_helper->viewRenderer->setNoRender();
		$requestParams =  $this->_request->getParams();
		Zend_Loader::loadClass('PyExport_PyData');  
		$pyObj = new PyExport_PyData;
		$output = $pyObj->getData($requestParams);
		if(isset($output['errors'])){
			header('HTTP/ 400 Bad Request');
		}
		header('Content-Type: application/json; charset=utf8');  
		echo $pyObj->JSONoutputString($output);
	}
	
	function getProjectsAction(){
		$this->_helper->viewRenderer->setNoRender();
		$requestParams =  $this->_request->getParams();
		Zend_Loader::loadClass('PyExport_PyProjects');
		Zend_Loader::loadClass('PyExport_PyData');
		$pyData = new PyExport_PyData;
		$pyObj = new PyExport_PyProjects;
		$output = $pyObj->get_descriptions();
		header('Content-Type: application/json; charset=utf8');  
		echo $pyData->JSONoutputString($output);
	}
	
	function projectsMetaAction(){
		$this->_helper->viewRenderer->setNoRender();
		$requestParams =  $this->_request->getParams();
		Zend_Loader::loadClass('PyExport_PyProjects');
		Zend_Loader::loadClass('PyExport_PyData');
		$pyData = new PyExport_PyData;
		$pyObj = new PyExport_PyProjects;
		$output = $pyObj->prep_annotations();
		header('Content-Type: application/json; charset=utf8');  
		echo $pyData->JSONoutputString($output);
	}
	
	function projectsDesAction(){
		$this->_helper->viewRenderer->setNoRender();
		$requestParams =  $this->_request->getParams();
		Zend_Loader::loadClass('PyExport_PyProjects');
		Zend_Loader::loadClass('PyExport_PyData');
		$pyData = new PyExport_PyData;
		$pyObj = new PyExport_PyProjects;
		$output = $pyObj->prep_descriptions();
		header('Content-Type: application/json; charset=utf8');  
		echo $pyData->JSONoutputString($output);
	}
	
}//end class