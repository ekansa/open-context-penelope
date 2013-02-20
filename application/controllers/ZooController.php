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
		  $baseMediaURL = "http://opencontext/media/";
        $rootUUID = "1_DT_Spatial";
		  
		  Zend_Loader::loadClass('PublishedData_Hierarchy');
        Zend_Loader::loadClass('PublishedData_Space');
        Zend_Loader::loadClass('PublishedData_Observe');
		  Zend_Loader::loadClass('PublishedData_Properties');
		  Zend_Loader::loadClass('PublishedData_Links');
		  Zend_Loader::loadClass('PublishedData_Resource');
		  Zend_Loader::loadClass('dbXML_xmlSpace');
		  Zend_Loader::loadClass('dbXML_xmlMedia');
		  
		  $hierarchyObj = new PublishedData_Hierarchy;
		  $hierarchyObj->baseSpaceURI = $baseURL;
		  $hierarchyObj->baseMediaURI = $baseMediaURL;
		  $hierarchyObj->addHierarchy($rootUUID);
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_Json::encode(array("done" => $hierarchyObj->doneURIs, "errors" => $hierarchyObj->errors));
	 }
	 


	  //load up old space data from XML documents
	 function linkBoneOntologyAction(){
		  
		  //this line is necessary for ajax calls:
        $this->_helper->viewRenderer->setNoRender();        
        
        //get selected root item then add it and all children to database
        $tableID = "OpenContext";
        
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
        $projUUID = "BC90D462-6639-4087-8527-6BB9E528E07D";
        
		  Zend_Loader::loadClass('dataEdit_SpaceIdentity');
		  
		  $editObj = new dataEdit_SpaceIdentity;
		  $editObj->projUUID = $projUUID;
		  //$editObj->storeIDsWithDuplicatingVars();
		  $sourceIDs = $editObj->getSourceDataIDs();
		  //$output = $editObj->fixIdentities();
		 
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_Json::encode($sourceIDs);
	 }

	 //check identifier uniqueness by seeing if the same variable is used more than once
	 function idCheckFixAction(){
		  
		  //this line is necessary for ajax calls:
        $this->_helper->viewRenderer->setNoRender();        
        
        //get selected root item then add it and all children to database
        $projUUID = "731B0670-CE2A-414A-8EF6-9C050A1C60F5";
        
		  Zend_Loader::loadClass('dataEdit_SpaceIdentity');
		  
		  $editObj = new dataEdit_SpaceIdentity;
		  $editObj->projUUID = $projUUID;
		  $editObj->storeIDsWithDuplicatingVars();
		  $sourceIDs = $editObj->getSourceDataIDs();
		  $output = $editObj->fixIdentities();
		 
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_Json::encode($output);
	 }
	 
	 
	 
	 function alterPropLinksAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();     
		  
		  $labelPrefix = "Bone ";
		  $changeArray = array(
		   'bone label' => ' uri '
		 );
		  
		  Zend_Loader::loadClass('LinkedData_PropSplitLink');
		  $propSplitObj = new LinkedData_PropSplitLink ;
		  $propSplitObj->projectUUID = '';
		  $propSplitObj->classUUID = '881CEDA3-C445-4C9C-4D4B-634BD2963892'; //animal bones
		  $propSplitObj->predicateURI = 'http://opencontext.org/vocabularies/open-context-zooarch/zoo-0079'; //has anatomical id
		  $propSplitObj->getVarUUIDfromPredicateURI();
		  
		  $output = array("varUUID" => $propSplitObj->actVarUUID);
		  foreach($changeArray as $itemLabel => $newURI){
				$propSplitObj->resetForLoop();
				$itemLabel = $labelPrefix.trim($itemLabel);
				$propSplitObj->newLinkURI = trim($newURI);
				$subjectUUID =  $propSplitObj->getSpaceUUIDfromLabel($itemLabel);
				$propSplitObj->subjectUUID = $subjectUUID;
				$oldPropUUID =  $propSplitObj->getPropertyUUIDfromObsVarUUID();
				$propSplitObj->oldPropUUID = $oldPropUUID;
				$oldPropertyUpdated = $propSplitObj->oldPropertyLinkURIUpToDate();
				$propSplitObj->alterObsNewLinkingProperty();
				
				$output["props"][] = array(
										  "itemLabel" => $itemLabel,
										  "subjectUUID" => $subjectUUID,
										  "link" => "http://penelope.oc/preview/space?UUID=".$subjectUUID,
										"oldPropUUID" => $oldPropUUID,
										"oldPropUpdated" => $oldPropertyUpdated,
									   "newPropUUID" => $propSplitObj->newPropUUID,
										"newLinkURI" => $propSplitObj->newLinkURI
				);
		  }
		  $output["errors"] = $propSplitObj->errors;
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_Json::encode($output);
		  
	 }
	 
	 



}//end class