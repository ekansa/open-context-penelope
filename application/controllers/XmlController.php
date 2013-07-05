<?php

/** Zend_Controller_Action */
//require_once 'Zend/Controller/Action.php';
//require_once 'OpenContext/Controller/Action/Helper/SolrAccess.php';

//error_reporting(E_ALL ^ E_NOTICE);

class xmlController extends Zend_Controller_Action {
    
    //make sure all connections are UTF-8 OK
    private function setUTFconnection($db){
	$sql = "SET collation_connection = utf8_unicode_ci;";
	$db->query($sql, 2);
	$sql = "SET NAMES utf8;";
	$db->query($sql, 2);
    }
    
    
    public function spaceAction(){
	$this->_helper->viewRenderer->setNoRender();
	Zend_Loader::loadClass('dbXML_dbSpace');
	Zend_Loader::loadClass('dbXML_dbLinks');
	Zend_Loader::loadClass('dbXML_dbProperties');
	Zend_Loader::loadClass('dbXML_dbMetadata');
	Zend_Loader::loadClass('dbXML_xmlSpace');
	Zend_Loader::loadClass('dbXML_xmlProperties');
	Zend_Loader::loadClass('dbXML_xmlLinks');
	Zend_Loader::loadClass('dbXML_xmlNotes');
	Zend_Loader::loadClass('dbXML_xmlContext');
	Zend_Loader::loadClass('dbXML_xmlMetadata');
	
	$db = Zend_Registry::get('db');
	$this->setUTFconnection($db);
	
	$itemObj = new dbXML_dbSpace;
	$itemObj->initialize($db);
	$itemObj->dbPenelope = true;
	$itemObj->getByID($_GET['id']);
	$itemObj->getChildren();
	$itemObj->getParents();
	$itemObj->getObs();
	$itemObj->getGeo();
	$itemObj->getChrono();
	
	$propsObj = new dbXML_dbProperties;
	$propsObj->initialize($db);
	$propsObj->dbPenelope = true;
	$propsObj->getProperties($itemObj->itemUUID, $itemObj->obsNumbers);
	$itemObj->propertiesObj = $propsObj;
	
	$linksObj = new dbXML_dbLinks;
	$linksObj->initialize($db);
	$linksObj->dbPenelope = true;
	$linksObj->getLinks($itemObj->itemUUID, $itemObj->obsNumbers);
	$itemObj->linksObj = $linksObj;
	
	$metadataObj = new dbXML_dbMetadata;
	$metadataObj->initialize($db);
	$metadataObj->dbPenelope = true;
	$metadataObj->getMetadata($itemObj->projectUUID, $itemObj->sourceID);
	$itemObj->metadataObj = $metadataObj;
	
	if(!isset($_GET['xml'])){
		header('Content-Type: application/json; charset=utf8');
		echo Zend_Json::encode($itemObj);
	}
	else{
		$xmlItem = new dbXML_xmlSpace;
		$xmlItem->itemObj = $itemObj;
		$xmlItem->initialize();
		$xmlItem->addNameClass();
		$xmlItem->addObsPropsLinks();
		$xmlItem->addContext();
		$xmlItem->addChildren();
		$xmlItem->addMetadata();
		$doc = $xmlItem->doc;
		header('Content-Type: application/xml; charset=utf8');
		echo $doc->saveXML();
	}
    }
    
    
    public function mediaAction(){
	$this->_helper->viewRenderer->setNoRender();
	Zend_Loader::loadClass('dbXML_dbMedia');
	Zend_Loader::loadClass('dbXML_dbSpace');
	Zend_Loader::loadClass('dbXML_dbLinks');
	Zend_Loader::loadClass('dbXML_dbProperties');
	Zend_Loader::loadClass('dbXML_dbMetadata');
	Zend_Loader::loadClass('dbXML_xmlMedia');
	Zend_Loader::loadClass('dbXML_xmlProperties');
	Zend_Loader::loadClass('dbXML_xmlLinks');
	Zend_Loader::loadClass('dbXML_xmlNotes');
	Zend_Loader::loadClass('dbXML_xmlContext');
	Zend_Loader::loadClass('dbXML_xmlMetadata');
	
	$db = Zend_Registry::get('db');
	$this->setUTFconnection($db);
	
	$itemObj = new dbXML_dbMedia;
	$itemObj->initialize($db);
	$itemObj->dbPenelope = true;
	$itemObj->getByID($_GET['id']);
	
	$propsObj = new dbXML_dbProperties;
	$propsObj->initialize($db);
	$propsObj->dbPenelope = true;
	$propsObj->getProperties($itemObj->itemUUID);
	$itemObj->propertiesObj = $propsObj;
	
	$linksObj = new dbXML_dbLinks;
	$linksObj->initialize($db);
	$linksObj->dbPenelope = true;
	$linksObj->getLinks($itemObj->itemUUID);
	$linksObj->getSpaceFromTarg($itemObj->itemUUID);
	$linksObj->makeImplicitSpatial($itemObj->itemUUID);
	
	$itemObj->linksObj = $linksObj;
	
	if(is_array($linksObj->firstSpaceObj)){
		$firstSpace = $linksObj->firstSpaceObj;
		$spaceObj = new dbXML_dbSpace;
		$spaceObj->initialize($db);
		$spaceObj->dbPenelope = true;
		$spaceObj->getByID($firstSpace["linkedUUID"]);
		$spaceObj->getParents();
		$spaceObj->getGeo();
		$spaceObj->getChrono();
		
		$itemObj->geoLat = $spaceObj->geoLat;
		$itemObj->geoLon = $spaceObj->geoLon;
		$itemObj->geoGML = $spaceObj->geoGML;
		$itemObj->geoKML = $spaceObj->geoKML;
		$itemObj->geoSource = $spaceObj->geoSource;
		$itemObj->geoSourceName = $spaceObj->geoSourceName;
		$itemObj->chronoArray = $spaceObj->chronoArray; //array of chronological tags, handled differently from Geo because can have multiple
		unset($spaceObj);
	}
	
	
	
	$metadataObj = new dbXML_dbMetadata;
	$metadataObj->initialize($db);
	$metadataObj->dbPenelope = true;
	$metadataObj->getMetadata($itemObj->projectUUID, $itemObj->sourceID);
	$itemObj->metadataObj = $metadataObj;
	
	if(!isset($_GET['xml'])){
		header('Content-Type: application/json; charset=utf8');
		echo Zend_Json::encode($itemObj);
	}
	else{
		$xmlItem = new dbXML_xmlMedia;
		$xmlItem->itemObj = $itemObj;
		$xmlItem->initialize();
		$xmlItem->addName();
		$xmlItem->addPropsLinks();
		$xmlItem->addFileInfo();
		$xmlItem->addMetadata();
		
		$doc = $xmlItem->doc;
		header('Content-Type: application/xml; charset=utf8');
		echo $doc->saveXML();
	}
    }
    
    
    
    public function projectAction(){
			$this->_helper->viewRenderer->setNoRender();
			Zend_Loader::loadClass('dbXML_dbProject');
			Zend_Loader::loadClass('dbXML_dbSpace');
			Zend_Loader::loadClass('dbXML_dbLinks');
			Zend_Loader::loadClass('dbXML_dbProperties');
			Zend_Loader::loadClass('dbXML_dbMetadata');
			Zend_Loader::loadClass('dbXML_xmlProject');
			Zend_Loader::loadClass('dbXML_xmlProperties');
			Zend_Loader::loadClass('dbXML_xmlLinks');
			Zend_Loader::loadClass('dbXML_xmlNotes');
			Zend_Loader::loadClass('dbXML_xmlContext');
			Zend_Loader::loadClass('dbXML_xmlMetadata');
			
			$db = Zend_Registry::get('db');
			$this->setUTFconnection($db);
			
			$itemObj = new dbXML_dbProject;
			$itemObj->initialize($db);
			$itemObj->dbPenelope = true;
			$itemObj->getByID($_GET['id']);
			
			$propsObj = new dbXML_dbProperties;
			$propsObj->initialize($db);
			$propsObj->dbPenelope = true;
			$propsObj->getProperties($itemObj->itemUUID);
			$itemObj->propertiesObj = $propsObj;
			
			$linksObj = new dbXML_dbLinks;
			$linksObj->initialize($db);
			$linksObj->dbPenelope = true;
			$linksObj->makeProjRootLinks($itemObj->projRootItems); //make links for root spatial items
			$linksObj->makeProjPersonLinks($itemObj->itemUUID); //make links for all people
			$linksObj->getLinks($itemObj->itemUUID);
			$itemObj->linksObj = $linksObj;
			
			$metadataObj = new dbXML_dbMetadata;
			$metadataObj->initialize($db);
			$metadataObj->dbPenelope = true;
			$metadataObj->getMetadata($itemObj->projectUUID);
			$itemObj->metadataObj = $metadataObj;
			
			if(!isset($_GET['xml'])){
				header('Content-Type: application/json; charset=utf8');
				echo Zend_Json::encode($itemObj);
			}
			else{
				$xmlItem = new dbXML_xmlProject;
				$xmlItem->itemObj = $itemObj;
				$xmlItem->initialize();
				$xmlItem->addName();
				$xmlItem->addProjectInfo();
				$xmlItem->addPropsLinks();
				$xmlItem->addMetadata();
				
				$doc = $xmlItem->doc;
				header('Content-Type: application/xml; charset=utf8');
				echo $doc->saveXML();
			}
    }
    
    
    
public function propertyAction(){
	$this->_helper->viewRenderer->setNoRender();
	Zend_Loader::loadClass('dbXML_dbProject');
	Zend_Loader::loadClass('dbXML_dbPropitem');
	Zend_Loader::loadClass('dbXML_dbLinks');
	Zend_Loader::loadClass('dbXML_dbProperties');
	Zend_Loader::loadClass('dbXML_dbMetadata');
	Zend_Loader::loadClass('dbXML_xmlProperty');
	Zend_Loader::loadClass('dbXML_xmlProperties');
	Zend_Loader::loadClass('dbXML_xmlLinks');
	Zend_Loader::loadClass('dbXML_xmlNotes');
	Zend_Loader::loadClass('dbXML_xmlContext');
	Zend_Loader::loadClass('dbXML_xmlMetadata');
	$db = Zend_Registry::get('db');
	$this->setUTFconnection($db);
	
	$itemObj = new dbXML_dbPropitem;
	$itemObj->initialize($db);
	$itemObj->dbPenelope = true;
	$itemObj->getByID($_GET['id']);
	$itemObj->propertySummary();
	
	$propsObj = new dbXML_dbProperties;
	$propsObj->initialize($db);
	$propsObj->dbPenelope = true;
	$propsObj->getProperties($itemObj->itemUUID);
	$itemObj->propertiesObj = $propsObj;
	
	$linksObj = new dbXML_dbLinks;
	$linksObj->initialize($db);
	$linksObj->dbPenelope = true;
	$linksObj->getLinks($itemObj->itemUUID);
	$itemObj->linksObj = $linksObj;
	
	$metadataObj = new dbXML_dbMetadata;
	$metadataObj->initialize($db);
	$metadataObj->dbPenelope = true;
	$metadataObj->getMetadata($itemObj->projectUUID);
	$itemObj->metadataObj = $metadataObj;
	
	$itemObj->makeQueryVal();
	
	if(!isset($_GET['xml'])){
		header('Content-Type: application/json; charset=utf8');
		echo Zend_Json::encode($itemObj);
	}
	else{
		$xmlItem = new dbXML_xmlProperty;
		$xmlItem->itemObj = $itemObj;
		$xmlItem->initialize();
		$xmlItem->addName();
		$xmlItem->addPropDetails();
		$xmlItem->addPropsLinks();
		$xmlItem->addMetadata();
		
		$doc = $xmlItem->doc;
		header('Content-Type: application/xml; charset=utf8');
		echo $doc->saveXML();
	}
    }
    
    
    public function personAction(){
	$this->_helper->viewRenderer->setNoRender();
	Zend_Loader::loadClass('dbXML_dbPerson');
	Zend_Loader::loadClass('dbXML_dbSpace');
	Zend_Loader::loadClass('dbXML_dbLinks');
	Zend_Loader::loadClass('dbXML_dbProperties');
	Zend_Loader::loadClass('dbXML_dbMetadata');
	Zend_Loader::loadClass('dbXML_xmlPerson');
	Zend_Loader::loadClass('dbXML_xmlProperties');
	Zend_Loader::loadClass('dbXML_xmlLinks');
	Zend_Loader::loadClass('dbXML_xmlNotes');
	Zend_Loader::loadClass('dbXML_xmlContext');
	Zend_Loader::loadClass('dbXML_xmlMetadata');
	
	$db = Zend_Registry::get('db');
	$this->setUTFconnection($db);
	
	$itemObj = new dbXML_dbPerson;
	$itemObj->initialize($db);
	$itemObj->dbPenelope = true;
	$itemObj->getByID($_GET['id']);
	$itemObj->getLinkCounts();
	
	$propsObj = new dbXML_dbProperties;
	$propsObj->initialize($db);
	$propsObj->dbPenelope = true;
	$propsObj->getProperties($itemObj->itemUUID);
	$itemObj->propertiesObj = $propsObj;
	
	$linksObj = new dbXML_dbLinks;
	$linksObj->initialize($db);
	$linksObj->dbPenelope = true;
	$linksObj->getLinks($itemObj->itemUUID);
	//$linksObj->getSpaceFromTarg($itemObj->itemUUID);
	$itemObj->linksObj = $linksObj;
	
	$metadataObj = new dbXML_dbMetadata;
	$metadataObj->initialize($db);
	$metadataObj->dbPenelope = true;
	$metadataObj->getMetadata($itemObj->projectUUID);
	$itemObj->metadataObj = $metadataObj;
	
	if(!isset($_GET['xml'])){
		header('Content-Type: application/json; charset=utf8');
		echo Zend_Json::encode($itemObj);
	}
	else{
		$xmlItem = new dbXML_xmlPerson;
		$xmlItem->itemObj = $itemObj;
		$xmlItem->initialize();
		$xmlItem->addName();
		$xmlItem->addPersonInfo();
		$xmlItem->addPropsLinks();
		$xmlItem->addMetadata();
		
		$doc = $xmlItem->doc;
		header('Content-Type: application/xml; charset=utf8');
		echo $doc->saveXML();
	}
    }
    
    
    
     public function documentAction(){
	$this->_helper->viewRenderer->setNoRender();
	Zend_Loader::loadClass('dbXML_dbDocument');
	Zend_Loader::loadClass('dbXML_dbSpace');
	Zend_Loader::loadClass('dbXML_dbLinks');
	Zend_Loader::loadClass('dbXML_dbProperties');
	Zend_Loader::loadClass('dbXML_dbMetadata');
	Zend_Loader::loadClass('dbXML_xmlDocument');
	Zend_Loader::loadClass('dbXML_xmlProperties');
	Zend_Loader::loadClass('dbXML_xmlLinks');
	Zend_Loader::loadClass('dbXML_xmlNotes');
	Zend_Loader::loadClass('dbXML_xmlContext');
	Zend_Loader::loadClass('dbXML_xmlMetadata');
	
	$db = Zend_Registry::get('db');
	$this->setUTFconnection($db);
	
	$itemObj = new dbXML_dbDocument;
	$itemObj->initialize($db);
	$itemObj->dbPenelope = true;
	$itemObj->getByID($_GET['id']);
	
	$propsObj = new dbXML_dbProperties;
	$propsObj->initialize($db);
	$propsObj->dbPenelope = true;
	$propsObj->getProperties($itemObj->itemUUID);
	$itemObj->propertiesObj = $propsObj;
	$itemObj->setDocumentText();
	
	
	$linksObj = new dbXML_dbLinks;
	$linksObj->initialize($db);
	$linksObj->dbPenelope = true;
	$linksObj->getLinks($itemObj->itemUUID);
	$linksObj->getSpaceFromTarg($itemObj->itemUUID);
	$itemObj->linksObj = $linksObj;
	
	if(is_array($linksObj->firstSpaceObj)){
		
		$firstSpace = $linksObj->firstSpaceObj;
		$spaceObj = new dbXML_dbSpace;
		$spaceObj->initialize($db);
		$spaceObj->dbPenelope = true;
		$spaceObj->getByID($firstSpace["linkedUUID"]);
		$spaceObj->getParents();
		$spaceObj->getGeo();
		$spaceObj->getChrono();
		
		$itemObj->geoLat = $spaceObj->geoLat;
		$itemObj->geoLon = $spaceObj->geoLon;
		$itemObj->geoGML = $spaceObj->geoGML;
		$itemObj->geoKML = $spaceObj->geoKML;
		$itemObj->geoSource = $spaceObj->geoSource;
		$itemObj->geoSourceName = $spaceObj->geoSourceName;
		$itemObj->chronoArray = $spaceObj->chronoArray; //array of chronological tags, handled differently from Geo because can have multiple
		unset($spaceObj);
	}
	
	$metadataObj = new dbXML_dbMetadata;
	$metadataObj->initialize($db);
	$metadataObj->dbPenelope = true;
	$metadataObj->getMetadata($itemObj->projectUUID, $itemObj->sourceID);
	$itemObj->metadataObj = $metadataObj;
	
	if(!isset($_GET['xml'])){
		header('Content-Type: application/json; charset=utf8');
		echo Zend_Json::encode($itemObj);
	}
	else{
		$xmlItem = new dbXML_xmlDocument;
		$xmlItem->itemObj = $itemObj;
		$xmlItem->initialize();
		$xmlItem->addName();
		$xmlItem->addPropsLinks();
		$xmlItem->addDocText();
		$xmlItem->addMetadata();
		
		$doc = $xmlItem->doc;
		header('Content-Type: application/xml; charset=utf8');
		echo $doc->saveXML();
	}
    }
    
    
    

}