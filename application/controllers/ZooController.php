<?php

/** Zend_Controller_Action */
//require_once 'Zend/Controller/Action.php';
//require_once 'OpenContext/Controller/Action/Helper/SolrAccess.php';

//error_reporting(E_ALL ^ E_NOTICE);
// increase the memory limit
ini_set("memory_limit", "1024M");
// set maximum execution time to no limit
ini_set("max_execution_time", "0");

class ZooController extends Zend_Controller_Action {
    
    function init()
    {  
        require_once 'App/Util/GenericFunctions.php';
    }
    
	 
	 //load up old space data from XML documents
	 function addSpaceHierarchyAction(){
		  
		  //this line is necessary for ajax calls:
        $this->_helper->viewRenderer->setNoRender();        
        
        //get selected root item then add it and all children to database
        $baseURL = "http://opencontext/subjects/";
        $rootUUID = "63431873-B6CB-4F38-5EB2-F667EA2BAF76";
		  
		  Zend_Loader::loadClass('PublishedData_Hierarchy');
        Zend_Loader::loadClass('PublishedData_Space');
        Zend_Loader::loadClass('PublishedData_Observe');
		  Zend_Loader::loadClass('PublishedData_Properties');
		  Zend_Loader::loadClass('PublishedData_Links');
		  Zend_Loader::loadClass('dbXML_xmlSpace');
		  
		  $hierarchyObj = new PublishedData_Hierarchy;
		  $hierarchyObj->baseSpaceURI = $baseURL;
		  $hierarchyObj->addHierarchy($rootUUID);
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_Json::encode(array("done" => $hierarchyObj->doneURIs, "errors" => $hierarchyObj->errors));
	 }
	 


	  //load up old space data from XML documents
	 function linkBoneOntologyAction(){
		  
		  //this line is necessary for ajax calls:
        $this->_helper->viewRenderer->setNoRender();        
        
        //get selected root item then add it and all children to database
        $tableID = "z_4_aa1c977a0";
        
		  Zend_Loader::loadClass('LinkedData_BoneMeasurement');
		  
		  $linkingObj = new LinkedData_BoneMeasurement;
		  $varList = $linkingObj->getVarTableList($tableID);
		  $doneList = $linkingObj->processVars($varList);
		 
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_Json::encode($doneList);
	 }
	 

	 //check identifier uniqueness by seeing if the same variable is used more than once
	 function idCheckAction(){
		  
		  //this line is necessary for ajax calls:
        $this->_helper->viewRenderer->setNoRender();        
        
        //get selected root item then add it and all children to database
        $projUUID = "731B0670-CE2A-414A-8EF6-9C050A1C60F5";
        
		  Zend_Loader::loadClass('dataEdit_SpaceIdentity');
		  
		  $editObj = new dataEdit_SpaceIdentity;
		  $editObj->projUUID = $projUUID;
		  //$editObj->storeIDsWithDuplicatingVars();
		  $sourceIDs = $editObj->getSourceDataIDs();
		  $UUIDsources = $editObj->fixIdentities();
		 
		  $output = array($UUIDsources,$sourceIDs);
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_Json::encode($output);
	 }


	 
	 



}//end class