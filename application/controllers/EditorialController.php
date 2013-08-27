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
	 
	 //get individual item, make ready for display of editorial functions
	 function itemsAction(){
		  
        if(isset($_REQUEST["uuid"])){
            $itemUUID = $_REQUEST["uuid"];
        }
        else{
            $itemUUID = false;
        }
		  
		  if(isset($_REQUEST["itemType"])){
            $itemType = $_REQUEST["itemType"];
        }
        else{
            $itemType = false;
        }
		  
		  $requestParams =  $this->_request->getParams();
		  Zend_Loader::loadClass('dataEdit_Items');
		  $itemsObj = new dataEdit_Items;
		  $itemsObj->host = $this->host;
		  $itemsObj->requestParams = $requestParams;
		  $itemsObj->getItem($itemUUID, $itemType);
		  
		  $this->view->requestParams = $requestParams;
		  $this->view->itemsObj = $itemsObj;
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
	 
	 function varValsAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  $requestParams =  $this->_request->getParams();
		 
		  Zend_Loader::loadClass('dataEdit_Variable');
		  $varObj = new dataEdit_Variable;
		  $varObj->requestParams = $requestParams;
		  $output = $varObj->getVarValues();
		 
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
	 
	 //adds a chronology tag to a given item
	 function chronoTagItemAction(){
		  $this->_helper->viewRenderer->setNoRender();
		  $requestParams =  $this->_request->getParams();
		 
		  Zend_Loader::loadClass('dataEdit_SpaceTime');
		  Zend_Loader::loadClass('dataEdit_SpaceContain');
		  Zend_Loader::loadClass('dataEdit_Published');
		  
		  if(isset($_REQUEST["uuid"])){
            $itemUUID = $_REQUEST["uuid"];
				$spaceTimeObj = new dataEdit_SpaceTime;
				$spaceTimeObj->requestParams =  $requestParams;
				$spaceTimeObj->chrontoTagItem($itemUUID);
				
				$publishedObj = new dataEdit_Published;
				$publishedObj->deleteFromPublishedDocsByParentUUID($parentUUID); //deletes the item and it's children from the list of published items
				
				$location = "../editorial/items?tab=chrono&uuid=".$itemUUID;
        }
        else{
            $location = "../editorial/items";
        }
		 
		  
		  header("Location: ".$location);
	 }
	 
	 //adds a chronology tag to a given item
	 function chronoTagByPropAction(){
		  $this->_helper->viewRenderer->setNoRender();
		  $requestParams =  $this->_request->getParams();
		 
		  Zend_Loader::loadClass('dataEdit_SpaceTime');
		  Zend_Loader::loadClass('dataEdit_SpaceContain');
		  Zend_Loader::loadClass('dataEdit_Published');
		  
		  if(isset($_REQUEST["propUUID"])){
            $propUUID = $_REQUEST["propUUID"];
				$spaceTimeObj = new dataEdit_SpaceTime;
				$spaceTimeObj->requestParams =  $requestParams;
				$output = $spaceTimeObj->chrontoTagByProperty($propUUID);
				header('Content-Type: application/json; charset=utf8');
				echo Zend_Json::encode($output);
        }
        else{
            $location = "../editorial/items";
				header("Location: ".$location);
        }
	 }
	 
	 
	 //adds a chronology tag to a given item
	 function updateLabelAction(){
		  $this->_helper->viewRenderer->setNoRender();
		  $requestParams =  $this->_request->getParams();
		 
		  Zend_Loader::loadClass('dataEdit_Items');
		  Zend_Loader::loadClass('dataEdit_SpaceTime');
		  Zend_Loader::loadClass('dataEdit_SpaceContain');
		  Zend_Loader::loadClass('dataEdit_Published');
		  
		  
		  if(isset($_REQUEST["uuid"])){
            $itemUUID = $_REQUEST["uuid"];
        }
        else{
            $itemUUID = false;
        }
		  
		  if(isset($_REQUEST["itemType"])){
            $itemType = $_REQUEST["itemType"];
        }
        else{
            $itemType = false;
        }
		  
		  if(isset($_REQUEST["label"])){
            $newLabel = $_REQUEST["label"];
        }
        else{
            $newLabel = "";
        }
		  
		  
		  if($itemUUID != false){
				$itemsObj = new dataEdit_Items;
				$itemsObj->host = $this->host;
				$itemsObj->requestParams = $requestParams;
				$itemsObj->updateItemLabel($newLabel, $itemUUID, $itemType);
				
				$location = "../editorial/items?uuid=".$itemUUID;
        }
        else{
            $location = "../editorial/items";
        }
		 
		  
		  header("Location: ".$location);
	 }
	 
	 
	 
	 //adds a chronology tag to a given item
	 function updateClassAction(){
		  $this->_helper->viewRenderer->setNoRender();
		  $requestParams =  $this->_request->getParams();
		 
		  Zend_Loader::loadClass('dataEdit_Items');
		  Zend_Loader::loadClass('dataEdit_SpaceTime');
		  Zend_Loader::loadClass('dataEdit_SpaceContain');
		  Zend_Loader::loadClass('dataEdit_Published');
		  
		  
		  if(isset($_REQUEST["uuid"])){
            $itemUUID = $_REQUEST["uuid"];
        }
        else{
            $itemUUID = false;
        }
		  
		  if(isset($_REQUEST["classUUID"])){
            $classUUID = $_REQUEST["classUUID"];
        }
        else{
            $classUUID = false;
        }
		  
		  
		  if($itemUUID != false){
				$itemsObj = new dataEdit_Items;
				$itemsObj->host = $this->host;
				$itemsObj->requestParams = $requestParams;
				$itemsObj->updateClassUUID($itemUUID, $classUUID);
				
				$location = "../editorial/items?uuid=".$itemUUID;
        }
        else{
            $location = "../editorial/items";
        }
		 
		  
		  header("Location: ".$location);
	 }
	 
	 
}