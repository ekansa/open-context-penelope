<?php

// increase the memory limit
ini_set("memory_limit", "2048M");
// set maximum execution time to no limit
ini_set("max_execution_time", "0");

class EditorialController extends Zend_Controller_Action{
	 
    public $host = "http://penelope.oc";

    function init(){
		  $this->host = "http://".$_SERVER['SERVER_NAME'];
        $this->view->baseUrl = $this->_request->getBaseUrl();
        require_once 'App/Util/GenericFunctions.php';
    }
    
	 function indexAction(){
		  
		  if(isset($_REQUEST["projectUUID"])){
            $projectUUID = $_REQUEST["projectUUID"];
        }
        else{
            $projectUUID = false;
        }
        
        if(isset($_REQUEST["itemUUID"])){
            $itemUUID = $_REQUEST["itemUUID"];
        }
        else{
            $itemUUID = false;
        }
		  
		  $requestParams =  $this->_request->getParams();
		  Zend_Loader::loadClass('dataEdit_Variable');
		  $varObj = new dataEdit_Variable;
		  $varObj->requestParams = $requestParams;
		  
		  $this->view->varObj = $varObj;
		  $this->view->projectUUID = $projectUUID;
		  $this->view->itemUUID = $itemUUID;
		  $this->view->host = $this->host;
		  
	 }
	 
	  function variablesAction(){
		  
        if(isset($_REQUEST["varUUID"])){
            $varUUID = $_REQUEST["varUUID"];
        }
        else{
            $varUUID = false;
        }
		  
		  $requestParams =  $this->_request->getParams();
		  Zend_Loader::loadClass('dataEdit_Variable');
		  Zend_Loader::loadClass('dataEdit_Property');
		  $varObj = new dataEdit_Variable;
		  $varObj->varUUID = $varUUID;
		  $varObj->requestParams = $requestParams;
		  $varObj->getVariable();
		  
		  $this->view->requestParams = $requestParams;
		  $this->view->varObj = $varObj;
		  $this->view->host = $this->host;
		  
	 }
	 
	 
	 //gets JSON data for a list of variables
	 function varLookupAction(){
		  $this->_helper->viewRenderer->setNoRender();
		  $requestParams =  $this->_request->getParams();
		 
		  Zend_Loader::loadClass('dataEdit_Variable');
		  $varObj = new dataEdit_Variable;
		  $varObj->requestParams = $requestParams;
		  $output = $varObj->getVarList();
		 
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_Json::encode($output);
	 }
	 
	 
	 //adds a note to a variable, redirects back to the variable when completed.
	 function varAddNoteAction(){
		  $this->_helper->viewRenderer->setNoRender();
		  $requestParams =  $this->_request->getParams();
		 
		  Zend_Loader::loadClass('dataEdit_Variable');
		  Zend_Loader::loadClass('dataEdit_Property');
		  
		  if(isset($_REQUEST["varUUID"])){
            $varUUID = $_REQUEST["varUUID"];
				$noteText = false;
				if(isset($_REQUEST["varNote"])){
					 $noteText = $_REQUEST["varNote"];
				}
				
				$varObj = new dataEdit_Variable;
				$varObj->addVariableNote($varUUID, $noteText);
				
				$location = "../editorial/variables?tab=notes&varUUID=".$varUUID;
        }
        else{
            $location = "../editorial/variables";
        }
		 
		  
		  header("Location: ".$location);
	 }
	 
	 
}