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
				$publishedObj->deleteFromPublishedDocsByParentUUID($itemUUID); //deletes the item and it's children from the list of published items
				
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
				$output = $spaceTimeObj->chronoTagByProperty($propUUID);
				header('Content-Type: application/json; charset=utf8');
				echo Zend_Json::encode($output);
        }
        else{
            $location = "../editorial/items";
				header("Location: ".$location);
        }
	 }
	 
	 //adds a chronology tag to a given item
	 function chronoTagByVarsAction(){
		  $this->_helper->viewRenderer->setNoRender();
		  $requestParams =  $this->_request->getParams();
		 
		  Zend_Loader::loadClass('dataEdit_SpaceTime');
		  Zend_Loader::loadClass('dataEdit_SpaceContain');
		  Zend_Loader::loadClass('dataEdit_Published');
		  
		  if(isset($_REQUEST["AvarUUID"]) && isset($_REQUEST["BvarUUID"])){
            $AvarUUID = $_REQUEST["AvarUUID"];
				$BvarUUID = $_REQUEST["BvarUUID"];
				$spaceTimeObj = new dataEdit_SpaceTime;
				$output = $spaceTimeObj->chronoTagByTwoVariables($AvarUUID, $BvarUUID);
				header('Content-Type: application/json; charset=utf8');
				echo Zend_Json::encode($output);
        }
        else{
            $location = "../editorial/items";
				header("Location: ".$location);
        }
	 }
	 
	 
	 //adds a chronology tag to a given item
	 function geoTagItemAction(){
		  $this->_helper->viewRenderer->setNoRender();
		  $requestParams =  $this->_request->getParams();
		 
		  require ('/application/models/GeoSpace/gisconverter.php'); // first, include gisconverter.php library, but not as a Zend include
		  Zend_Loader::loadClass('GeoSpace_ToGeoJSON');
		  Zend_Loader::loadClass('dataEdit_SpaceTime');
		  Zend_Loader::loadClass('dataEdit_SpaceContain');
		  Zend_Loader::loadClass('dataEdit_Subject');
		  Zend_Loader::loadClass('dataEdit_Published');
		  
		  if(isset($_REQUEST["uuid"])){
            $itemUUID = $_REQUEST["uuid"];
				$spaceTimeObj = new dataEdit_SpaceTime;
				$spaceTimeObj->requestParams =  $requestParams;
				$spaceTimeObj->geoTagItem($itemUUID);
				
				$publishedObj = new dataEdit_Published;
				$publishedObj->deleteFromPublishedDocsByParentUUID($itemUUID); //deletes the item and it's children from the list of published items
				
				$location = "../editorial/items?tab=itemGeo&uuid=".$itemUUID;
        }
        else{
            $location = "../editorial/items";
        }
		 
		  
		  header("Location: ".$location);
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
	 
	 
	 
	 //changes a class for a given item
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
	 
	 
	 //deletes a property from an item redirects back to the item when completed.
	 function deleteItemPropAction(){
		  $this->_helper->viewRenderer->setNoRender();
		  $requestParams =  $this->_request->getParams();
		 
		  Zend_Loader::loadClass('dataEdit_Property');
		  Zend_Loader::loadClass('dataEdit_Published');
		  
		  if(isset($_REQUEST["propUUID"]) && isset($_REQUEST["itemUUID"])){
            
				$propObj = new dataEdit_Property;
				$propObj->delete_item_property($_REQUEST["propUUID"], $_REQUEST["itemUUID"]);
				
				$location = "../editorial/items?tab=itemDes&uuid=".$_REQUEST["itemUUID"];
        }
        else{
            $location = "../editorial/items";
        }
		 
		  
		  header("Location: ".$location);
	 }
	 
	 
	 
	   //update the relationship type for a specific link
	 function updatePropValAction(){
		  $this->_helper->viewRenderer->setNoRender();
		  $requestParams =  $this->_request->getParams();
		  
		  Zend_Loader::loadClass('dataEdit_Property');
		  Zend_Loader::loadClass('dataEdit_Published');
		  
		  $output = array();
		  
		  if(isset($requestParams["propUUID"]) && isset($requestParams["newPropValue"])){
				$propObj = new dataEdit_Property;
				$output = array();
				$output["changedSubjects"] = $propObj->updatePropertyValue($requestParams["newPropValue"], $requestParams["propUUID"]);
				$output["errors"] = false;
		  }
		  else{
				$output["changedSubjects"] = 0;
				$output["errors"] = "Need a 'propUUID' and 'newPropValue' parameter";
		  }
		  
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_Json::encode($output);
	 }
	 
	 
	 
	 
	 function createSubjectItemAction(){
		  $this->_helper->viewRenderer->setNoRender();
		  $requestParams =  $this->_request->getParams();
		  
		  Zend_Loader::loadClass('dataEdit_Subject');
		  Zend_Loader::loadClass('dataEdit_Link');
		  Zend_Loader::loadClass('dataEdit_SpaceContain');
		  Zend_Loader::loadClass('dataEdit_Published');
		  
		  $subjectObj = new dataEdit_Subject;
		  $subjectObj->requestParams = $requestParams;
		  $output = $subjectObj->createItem();
		  
		  if(!$output["errors"]){
				$location = "../editorial/items?tab=itemDes&uuid=".$output["data"]["uuid"];
		  }
		  else{
				$location = "../editorial/items?tab=itemNew";
		  }
		  //header("Location: ".$location);
		  
		  
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_Json::encode($output);
		  
	 }
	 
	 
	 
	 
	 //checks on the size of media files, if present
	 function checkMediaFilesAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  $requestParams =  $this->_request->getParams();
		  Zend_Loader::loadClass('dataEdit_Media');
		  Zend_Loader::loadClass('dbXML_dbMedia');
		  
		  $mediaObj = new dataEdit_Media;
		  $mediaObj->requestParams = $requestParams;
		  $output = $mediaObj->checkMediaFiles();
		  
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_Json::encode($output);
	 }
	 
	 function createMediaItemAction(){
		  $this->_helper->viewRenderer->setNoRender();
		  $requestParams =  $this->_request->getParams();
		  Zend_Loader::loadClass('dataEdit_Media');
		  Zend_Loader::loadClass('dataEdit_Link');
		  Zend_Loader::loadClass('dataEdit_Published');
		  
		  $mediaObj = new dataEdit_Media;
		  $mediaObj->requestParams = $requestParams;
		  $uuid = $mediaObj->createMediaItem();
		  
		  if($uuid != false){
				$location = "../editorial/items?tab=itemDes&uuid=".$uuid;
		  }
		  else{
				$location = "../editorial/items?tab=itemNew";
		  }
		  header("Location: ".$location);
		  
		  /*
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_Json::encode($uuid);
		  */
	 }
	 
	 //create a new document item
	 function createDocumentItemAction(){
		  $this->_helper->viewRenderer->setNoRender();
		  $requestParams =  $this->_request->getParams();
		  Zend_Loader::loadClass('dataEdit_Document');
		  Zend_Loader::loadClass('dataEdit_Link');
		  Zend_Loader::loadClass('dataEdit_Published');
		  
		  $docObj = new dataEdit_Document;
		  $docObj->requestParams = $requestParams;
		  $output = $docObj->createItem();
		  
		  if(!$output["errors"]){
				$location = "../editorial/items?tab=itemDes&uuid=".$output["data"]["uuid"];
		  }
		  else{
				$location = "../editorial/items?tab=itemNew";
		  }
		  header("Location: ".$location);
		  
		  /*
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_Json::encode($uuid);
		  */
	 }
	 
	 //validates the XHTML of a document
	 function validateXhtmlAction(){
		  $this->_helper->viewRenderer->setNoRender();
		  $requestParams =  $this->_request->getParams();
		  Zend_Loader::loadClass('dataEdit_Document');
		  $output = array("valid" => false);
		  if(isset($_REQUEST["xhtml"])){
				$xhtml = $_REQUEST["xhtml"];
				$docObj = new dataEdit_Document;
				$output["valid"] = $docObj->XHTMLvalid($xhtml);
		  }
		  
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_Json::encode($output);
	 }
	 
	 
	  //create a new person item
	 function createPersonItemAction(){
		  $this->_helper->viewRenderer->setNoRender();
		  $requestParams =  $this->_request->getParams();
		  Zend_Loader::loadClass('dataEdit_Person');
		  Zend_Loader::loadClass('dataEdit_Link');
		  Zend_Loader::loadClass('dataEdit_Published');
		  
		  $persObj = new dataEdit_Person;
		  $persObj->requestParams = $requestParams;
		  $output = $persObj->createItem();
		  
		  if(!$output["errors"]){
				$location = "../editorial/items?tab=itemDes&uuid=".$output["data"]["uuid"];
		  }
		  else{
				$location = "../editorial/items?tab=itemNew";
		  }
		  header("Location: ".$location);
		  
		  
		  //header('Content-Type: application/json; charset=utf8');
		  //echo Zend_Json::encode($output);
		  
	 }
	 
	 //create a new linking relationship for an item
	 function createItemLinkAction(){
		  $this->_helper->viewRenderer->setNoRender();
		  $requestParams =  $this->_request->getParams();
		  
		  Zend_Loader::loadClass('dataEdit_Link');
		  Zend_Loader::loadClass('dataEdit_Published');
		  
		  $linkObj = new dataEdit_Link;
		  $linkObj->requestParams = $requestParams;
		  $output = $linkObj->createItemLinkingRel();
		  
		  if(isset($requestParams["actItemUUID"])){
				$location = "../editorial/items?tab=itemLinks&uuid=".$requestParams["actItemUUID"];
		  }
		  else{
				$location = "../editorial/items";
		  }
		  header("Location: ".$location);
		  
		  /*
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_Json::encode($uuid);
		  */
	 }
	 
	 
	  //create a new linking relationship for a list of items that have a given property
	 function createLinksByPropertyAction(){
		  $this->_helper->viewRenderer->setNoRender();
		  $requestParams =  $this->_request->getParams();
		  
		  Zend_Loader::loadClass('dataEdit_Property');
		  Zend_Loader::loadClass('dataEdit_Link');
		  Zend_Loader::loadClass('dataEdit_Published');
		  
		  if(isset($requestParams["propUUID"])){
				$propertyUUID = $requestParams["propUUID"];
				$propObj = new dataEdit_Property;
				$propObj->requestParams = $requestParams;
				$linkedItems = $propObj->createLinksByPropertyID($propertyUUID);	
				$output = array("count" => count($linkedItems), "linkedItems" => $linkedItems);
		  }
		  
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_Json::encode($output);
	 }
	 
	  
	  //update the relationship type for a specific link
	 function updateItemLinkAction(){
		  $this->_helper->viewRenderer->setNoRender();
		  $requestParams =  $this->_request->getParams();
		  
		  Zend_Loader::loadClass('dataEdit_Link');
		  Zend_Loader::loadClass('dataEdit_Published');
		  
		  $linkObj = new dataEdit_Link;
		  $linkObj->requestParams = $requestParams;
		  $output = $linkObj->updateLinkRelationType();
		  
		  if(isset($requestParams["originUUID"])){
				$location = "../editorial/items?tab=itemLinks&uuid=".$requestParams["originUUID"];
		  }
		  else{
				$location = "../editorial/items";
		  }
		  header("Location: ".$location);
		  
		  /*
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_Json::encode($uuid);
		  */
	 }
	 
	  //delete a specific link
	 function deleteItemLinkAction(){
		  $this->_helper->viewRenderer->setNoRender();
		  $requestParams =  $this->_request->getParams();
		  
		  Zend_Loader::loadClass('dataEdit_Link');
		  Zend_Loader::loadClass('dataEdit_Published');
		  
		  $linkObj = new dataEdit_Link;
		  $linkObj->requestParams = $requestParams;
		  $output = $linkObj->deleteLink();
		  
		  if(isset($requestParams["originUUID"])){
				$location = "../editorial/items?tab=itemLinks&uuid=".$requestParams["originUUID"];
		  }
		  else{
				$location = "../editorial/items";
		  }
		  header("Location: ".$location);
		  
		  /*
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_Json::encode($uuid);
		  */
	 }
	 
	 
	 
	 function deleteLinkedDataAction(){
		  $this->_helper->viewRenderer->setNoRender();
		  $requestParams =  $this->_request->getParams();
		  
		  Zend_Loader::loadClass('dataEdit_Items');
		  Zend_Loader::loadClass('dataEdit_LinkedData');
		  Zend_Loader::loadClass('dataEdit_Published');
		  
		  $linkDataObj = new dataEdit_LinkedData;
		  $linkDataObj->requestParams = $requestParams;
		  $output = $linkDataObj->deleteLinkedData();
		  
		  if(isset($requestParams["subjectUUID"])){
				$location = "../editorial/items?tab=linkedData&uuid=".$requestParams["subjectUUID"];
		  }
		  else{
				$location = "../editorial/items";
		  }
		  header("Location: ".$location);
		  
		  /*
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_Json::encode($uuid);
		  */
	 }
	 
	 function addUpdateLinkedDataAction(){
		  $this->_helper->viewRenderer->setNoRender();
		  $requestParams =  $this->_request->getParams();
		  
		  Zend_Loader::loadClass('dataEdit_Items');
		  Zend_Loader::loadClass('dataEdit_LinkedData');
		  Zend_Loader::loadClass('dataEdit_Published');
		  
		  $linkDataObj = new dataEdit_LinkedData;
		  $linkDataObj->requestParams = $requestParams;
		  $output = $linkDataObj->addUpdateLinkedData();
		  
		  if(isset($requestParams["subjectUUID"])){
				$location = "../editorial/items?tab=linkedData&uuid=".$requestParams["subjectUUID"];
		  }
		  else{
				$location = "../editorial/items";
		  }
		  header("Location: ".$location);
		  
		  
		  //header('Content-Type: application/json; charset=utf8');
		  //echo Zend_Json::encode($output );
		  
	 }
	 
	 
	 
}