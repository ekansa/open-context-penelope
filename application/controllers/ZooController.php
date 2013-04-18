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
        $rootUUID = "ROOTPRJ0000000006";
		  
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
        $tableID = "z_21_df38a0fd9";
        
		  Zend_Loader::loadClass('LinkedData_BoneMeasurement');
		  
		  $linkingObj = new LinkedData_BoneMeasurement;
		  $linkingObj->doShortVariableLabels = false;
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
        $projUUID = "";
        
		  Zend_Loader::loadClass('dataEdit_SpaceIdentity');
		  
		  $editObj = new dataEdit_SpaceIdentity;
		  $editObj->projUUID = $projUUID;
		  $editObj->storeIDsWithDuplicatingVars();
		  $sourceIDs = $editObj->getSourceDataIDs();
		  $output = $editObj->fixIdentities();
		 
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_Json::encode($sourceIDs);
	 }

	 //check identifier uniqueness by seeing if the same variable is used more than once
	 function idCheckFixAction(){
		  
		  //this line is necessary for ajax calls:
        $this->_helper->viewRenderer->setNoRender();        
        
        //get selected root item then add it and all children to database
        //$projUUID = "D297CD29-50CA-4B2C-4A07-498ADF3AF487";
        
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
	 
	 

	 function catalAction(){
		 
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Catal');
		  
		  $catalObj = New ProjEdits_Catal;
		  $catalObj->workbookFile =  "http://penelope.oc/public/xml/catal-c-use.fods";
		  $catalObj->importTableName = "z_13_457009575";
		  //$catalObj->importTableName = false;
		  $catalObj->doCommentUpdate = true;
		  //$records = $catalObj->loadParseSaveXML();
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_Json::encode($records);
	 }
	 
	 function catalLinkAction(){
		 
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Catal');
		  $classUUID = "881CEDA3-C445-4C9C-4D4B-634BD2963892"; //animal bones
		  $projectUUID = "1B426F7C-99EC-4322-4069-E8DBD927CCF1"; //catal project id
		  $catalObj = New ProjEdits_Catal;
		  //$records = $catalObj->parentContextSelect( $classUUID, $projectUUID);
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_Json::encode($records);
	 }
	 
	 
	 function splitProjAction(){
		 
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_SplitProject');
		  
		  $projObj = New ProjEdits_SplitProject;
		  $projObj->oldProjectUUID = "731B0670-CE2A-414A-8EF6-9C050A1C60F5";
		  $projObj->newProjectUUID = "8894EEC0-DC96-4304-1EFC-4572FD91717A";
		  $projObj->oldContainText = "Turkey|xx|Okuzini Cave";
		  $projObj->newContainText = "Turkey|xx|Öküzini Cave";
		  
		  $projObj->getDistinctProperties("Turkey|xx|Okuzini Cave");
		  $records = $projObj->updateSpaceObs("Turkey|xx|Okuzini Cave");
		  $output = array("queries" => $projObj->queries, "recs" => $records);
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_Json::encode($output);
	 }
	 
	 
	 
	 
	 
	 function spaceSortAction(){
		 
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Space');
		  
		  $sortObj = New ProjEdits_Space;
		  $records = $sortObj->spaceLabelSorting();
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_Json::encode($records);
	 }

	 
	 function mediaFindLinkAction(){
		 
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Media');
		  Zend_Loader::loadClass('dbXML_dbMedia');
		  
		  $dbMedia = New dbXML_dbMedia;
		  
		  
		  $mediaObj = New ProjEdits_Media;
		  $mediaObj->mediaTypeArray = $dbMedia->mediaTypeArray;
		  $mediaObj->spaceLabelPrefix = "UNE ";
		  $mediaObj->projectUUID = "4B16F48E-6F5D-41E0-F568-FCE64BE6D3FA";
		  $mediaObj->mediaFileBaseURL = "http://artiraq.org/static/opencontext/stoneware-media/";
		  $mediaObj->mediaSearchDir = "C:\\Users\\Eric C. Kansa\\Documents\\OC Imports\\Peter Grave Data\\stoneware-media\\full\\";
		  //$directory = "C:\\about_opencontext\\kenan\\thumbs\\";
		 
		  $output = $mediaObj->findLinkCreateMedia();
		  
		  
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_Json::encode($output);
	 }
	 
	 function mediaCheckAction(){
		 
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Media');
		  Zend_Loader::loadClass('dbXML_dbMedia');
		  
		  $dbMedia = New dbXML_dbMedia;
		  
		  
		  $mediaObj = New ProjEdits_Media;
		  $mediaObj->mediaTypeArray = $dbMedia->mediaTypeArray;
		  $mediaObj->imageFileCheckLimit = " resource.project_id = 'CF6E1364-D6EF-4042-B726-82CFB73F7C9D' ";
		  $output = $mediaObj->imageFileCheck();
		  
		  
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_Json::encode($output);
	 }
	 

}//end class