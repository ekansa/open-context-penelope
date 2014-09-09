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
	
	function pleiadesCountriesAction(){
		$this->_helper->viewRenderer->setNoRender();
		Zend_Loader::loadClass('ProjEdits_Periodo');
		$pObj = new ProjEdits_Periodo;
		$output = $pObj->countries();
		header('Content-Type: application/json; charset=utf8');
		echo Zend_Json::encode($output);
	}
	
	
	function pleiadesPeriodsAction(){
		$this->_helper->viewRenderer->setNoRender();
		Zend_Loader::loadClass('ProjEdits_Periodo');
		$pObj = new ProjEdits_Periodo;
		$output = $pObj->periodCountries();
		header('Content-Type: application/json; charset=utf8');
		echo Zend_Json::encode($output);
	}
	
	
	
	function alabDatesAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Dinaa');
		  Zend_Loader::loadClass('dataEdit_Link');
		  Zend_Loader::loadClass('dataEdit_Published');
		  Zend_Loader::loadClass('dataEdit_SpaceTime');
		  Zend_Loader::loadClass('dataEdit_SpaceContain');
		  Zend_Loader::loadClass('dataEdit_Subject');
		  Zend_Loader::loadClass('dataEdit_Property');
		  Zend_Loader::loadClass('dataEdit_LinkedData');
		  
		  $pObj = new ProjEdits_Dinaa;
		  $pObj->projectUUID = 'F905BD53-C843-4349-0A46-48FF32B5F1BE';
		  $output = array();
		  $output["dates"] = $pObj->alabamaDates(); 
		  
		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
	   }
	
	function vaDatesAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Dinaa');
		  Zend_Loader::loadClass('dataEdit_Link');
		  Zend_Loader::loadClass('dataEdit_Published');
		  Zend_Loader::loadClass('dataEdit_SpaceTime');
		  Zend_Loader::loadClass('dataEdit_SpaceContain');
		  Zend_Loader::loadClass('dataEdit_Subject');
		  Zend_Loader::loadClass('dataEdit_Property');
		  Zend_Loader::loadClass('dataEdit_LinkedData');
		  
		  $pObj = new ProjEdits_Dinaa;
		  $pObj->projectUUID = 'AF0D2F67-2EAB-4C28-9C61-0F019CBF628E';
		  $output = array();
		  $output["dates"] = $pObj->vaDates(); 
		  
		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
	   }
    
    function mdGeoAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Dinaa');
		  Zend_Loader::loadClass('dataEdit_Link');
		  Zend_Loader::loadClass('dataEdit_Published');
		  Zend_Loader::loadClass('dataEdit_SpaceTime');
		  Zend_Loader::loadClass('dataEdit_SpaceContain');
		  Zend_Loader::loadClass('dataEdit_Subject');
		  Zend_Loader::loadClass('dataEdit_Property');
		  Zend_Loader::loadClass('dataEdit_LinkedData');
		  
		  $pObj = new ProjEdits_Dinaa;
		  $pObj->projectUUID = 'F9970276-8636-478D-A3F0-08CC7EFEAD4F';
		  $output = array();
		  $output["geo"] = $pObj->mdGeo(); 
		  $output["county"] = $pObj->countyGeo('Maryland'); 
		  
		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
	}
    
    
    
    function ncGeoAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Dinaa');
		  Zend_Loader::loadClass('dataEdit_Link');
		  Zend_Loader::loadClass('dataEdit_Published');
		  Zend_Loader::loadClass('dataEdit_SpaceTime');
		  Zend_Loader::loadClass('dataEdit_SpaceContain');
		  Zend_Loader::loadClass('dataEdit_Subject');
		  Zend_Loader::loadClass('dataEdit_Property');
		  Zend_Loader::loadClass('dataEdit_LinkedData');
		  
		  $pObj = new ProjEdits_Dinaa;
		  $pObj->projectUUID = '5F2D4172-D823-4F7F-D3A8-4BD68ED1369D';
		  $output = array();
		  $output["geo"] = $pObj->ncGeo(); 
		  $output["county"] = $pObj->countyGeo('North Carolina'); 
		  
		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
	}
    
    
    function pennGeoAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Dinaa');
		  Zend_Loader::loadClass('dataEdit_Link');
		  Zend_Loader::loadClass('dataEdit_Published');
		  Zend_Loader::loadClass('dataEdit_SpaceTime');
		  Zend_Loader::loadClass('dataEdit_SpaceContain');
		  Zend_Loader::loadClass('dataEdit_Subject');
		  Zend_Loader::loadClass('dataEdit_Property');
		  Zend_Loader::loadClass('dataEdit_LinkedData');
		  
		  $pObj = new ProjEdits_Dinaa;
		  $pObj->projectUUID = '766698E3-2E79-4A78-B0BC-245FF435BBBD';
		  $output = array();
		  $output["geo"] = $pObj->pennGeo(); 
		  $output["county"] = $pObj->countyGeo('Pennsylvania'); 
		  
		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
	}
	
	
	function ohioGeoAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Dinaa');
		  Zend_Loader::loadClass('dataEdit_Link');
		  Zend_Loader::loadClass('dataEdit_Published');
		  Zend_Loader::loadClass('dataEdit_SpaceTime');
		  Zend_Loader::loadClass('dataEdit_SpaceContain');
		  Zend_Loader::loadClass('dataEdit_Subject');
		  Zend_Loader::loadClass('dataEdit_Property');
		  Zend_Loader::loadClass('dataEdit_LinkedData');
		  
		  $pObj = new ProjEdits_Dinaa;
		  $pObj->projectUUID = 'EDDA846F-7225-495E-AB77-7314C256449A';
		  $output = array();
		  $output["geo"] = $pObj->ohioGeo(); 
		  $output["county"] = $pObj->countyGeo('Ohio'); 
		  
		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
	}
	
	function laGeoAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Dinaa');
		  Zend_Loader::loadClass('dataEdit_Link');
		  Zend_Loader::loadClass('dataEdit_Published');
		  Zend_Loader::loadClass('dataEdit_SpaceTime');
		  Zend_Loader::loadClass('dataEdit_SpaceContain');
		  Zend_Loader::loadClass('dataEdit_Subject');
		  Zend_Loader::loadClass('dataEdit_Property');
		  Zend_Loader::loadClass('dataEdit_LinkedData');
		  
		  $pObj = new ProjEdits_Dinaa;
		  $pObj->projectUUID = '56110B30-3C18-49F9-BDA9-550FE8E28450';
		  $output = array();
		  $output["geo"] = $pObj->laGeo(); 
		  $output["county"] = $pObj->countyGeo('Louisiana', 'Parish'); 
		  
		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
	}
	
	function vaGeoAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Dinaa');
		  Zend_Loader::loadClass('dataEdit_Link');
		  Zend_Loader::loadClass('dataEdit_Published');
		  Zend_Loader::loadClass('dataEdit_SpaceTime');
		  Zend_Loader::loadClass('dataEdit_SpaceContain');
		  Zend_Loader::loadClass('dataEdit_Subject');
		  Zend_Loader::loadClass('dataEdit_Property');
		  Zend_Loader::loadClass('dataEdit_LinkedData');
		  
		  $pObj = new ProjEdits_Dinaa;
		  $pObj->projectUUID = 'AF0D2F67-2EAB-4C28-9C61-0F019CBF628E';
		  $output = array();
		  $output["geo"] = $pObj->vaGeo(); 
		  $output["county"] = $pObj->countyGeo('Virginia'); 
		  
		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
	}
	
	function allDinaaDatesAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Dinaa');
		  Zend_Loader::loadClass('dataEdit_Link');
		  Zend_Loader::loadClass('dataEdit_Published');
		  Zend_Loader::loadClass('dataEdit_SpaceTime');
		  Zend_Loader::loadClass('dataEdit_SpaceContain');
		  Zend_Loader::loadClass('dataEdit_Subject');
		  Zend_Loader::loadClass('dataEdit_Property');
		  Zend_Loader::loadClass('dataEdit_LinkedData');
		  
		  $pObj = new ProjEdits_Dinaa;
		  $output = array();
		  $output["dates"] = $pObj->all_date_gather(); 
		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
	   }
	
	
	  function altIowaDatesAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Dinaa');
		  Zend_Loader::loadClass('dataEdit_Link');
		  Zend_Loader::loadClass('dataEdit_Published');
		  Zend_Loader::loadClass('dataEdit_SpaceTime');
		  Zend_Loader::loadClass('dataEdit_SpaceContain');
		  Zend_Loader::loadClass('dataEdit_Subject');
		  Zend_Loader::loadClass('dataEdit_Property');
		  Zend_Loader::loadClass('dataEdit_LinkedData');
		  
		  $pObj = new ProjEdits_Dinaa;
		  $pObj->projectUUID = '8492AEC3-E406-44C6-03CA-2BF280D8F5B0';
		  $output = array();
		  $output["dates"] = $pObj->altIowaDates(); 
		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
	   }
    
	
	function catalTpDatesAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Catal');
		  Zend_Loader::loadClass('dataEdit_Link');
		  Zend_Loader::loadClass('dataEdit_Published');
		  Zend_Loader::loadClass('dataEdit_SpaceTime');
		  Zend_Loader::loadClass('dataEdit_SpaceContain');
		  Zend_Loader::loadClass('dataEdit_Subject');
		  Zend_Loader::loadClass('dataEdit_Property');
		  Zend_Loader::loadClass('dataEdit_LinkedData');
		  
		  $pObj = new ProjEdits_Catal;
		  $output = $pObj->tp_area_chrono();
		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
	   }
    
	
	
    function iowaObsDatesAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Dinaa');
		  Zend_Loader::loadClass('dataEdit_Link');
		  Zend_Loader::loadClass('dataEdit_Published');
		  Zend_Loader::loadClass('dataEdit_SpaceTime');
		  Zend_Loader::loadClass('dataEdit_SpaceContain');
		  Zend_Loader::loadClass('dataEdit_Subject');
		  Zend_Loader::loadClass('dataEdit_Property');
		  Zend_Loader::loadClass('dataEdit_LinkedData');
		  
		  $pObj = new ProjEdits_Dinaa;
		  $pObj->projectUUID = '8492AEC3-E406-44C6-03CA-2BF280D8F5B0';
		  $output = array();
		  $output["dates"] = $pObj->iowaObsDates(); 
		  //$output["links"] = $pObj->iowaPeriodLink();
		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
	   }
	 
	 
    function illDatesAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Dinaa');
		  Zend_Loader::loadClass('dataEdit_Link');
		  Zend_Loader::loadClass('dataEdit_Published');
		  Zend_Loader::loadClass('dataEdit_SpaceTime');
		  Zend_Loader::loadClass('dataEdit_SpaceContain');
		  Zend_Loader::loadClass('dataEdit_Subject');
		  Zend_Loader::loadClass('dataEdit_Property');
		  Zend_Loader::loadClass('dataEdit_LinkedData');
		  
		  $pObj = new ProjEdits_Dinaa;
		  $pObj->projectUUID = 'B7F85EB6-4BF5-43FA-98E7-FF8FAF1AA452';
		  $output = array();
		  $output["dates"] = $pObj->illDates(); 
		  
		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
	   }
    
    
    function loadRefineAction(){
	   $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Refine');
		  
		  $pObj = new ProjEdits_Refine;
		  $pObj->projectUUID = '8492AEC3-E406-44C6-03CA-2BF280D8F5B0';
		  $pObj->refineProjectID = '1576467418410';
		  $pObj->localTableID = 'z_6_399512215';
		  $output = array();
		  $output = $pObj->loadRefineData(); 
		  
		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
    }
    
    //link geo with items
    function illGeoAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Dinaa');
		  Zend_Loader::loadClass('dataEdit_Link');
		  Zend_Loader::loadClass('dataEdit_Published');
		  Zend_Loader::loadClass('dataEdit_SpaceTime');
		  Zend_Loader::loadClass('dataEdit_SpaceContain');
		  Zend_Loader::loadClass('dataEdit_Subject');
		  Zend_Loader::loadClass('dataEdit_Property');
		  Zend_Loader::loadClass('dataEdit_LinkedData');
		  
		  $pObj = new ProjEdits_Dinaa;
		  $pObj->projectUUID = 'B7F85EB6-4BF5-43FA-98E7-FF8FAF1AA452';
		  $output = array();
		  $output["geo"] = $pObj->illGeo(); 
		  $output["county"] = $pObj->countyGeo('Illinois'); 
		  
		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
	 }
    
	 
	//link geo with items
	   function kyDatesAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Dinaa');
		  Zend_Loader::loadClass('dataEdit_Link');
		  Zend_Loader::loadClass('dataEdit_Published');
		  Zend_Loader::loadClass('dataEdit_SpaceTime');
		  Zend_Loader::loadClass('dataEdit_SpaceContain');
		  Zend_Loader::loadClass('dataEdit_Subject');
		  Zend_Loader::loadClass('dataEdit_Property');
		  Zend_Loader::loadClass('dataEdit_LinkedData');
		  
		  $pObj = new ProjEdits_Dinaa;
		  $pObj->projectUUID = "F05ACE4F-9B55-48A0-D640-5276B8B899C7";
		  $output = array();
		  $output["dates"] = $pObj->kyDates(); 
		  $output["links"] = $pObj->kyPeriodLink();
		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
	   } 
	 
	 
	 //link geo with items
	 function badeUuidsAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Bade');
		  Zend_Loader::loadClass('Images_ThumbPreviewSize');
		  Zend_Loader::loadClass('dataEdit_Media');
		  Zend_Loader::loadClass('dataEdit_Link');
		  Zend_Loader::loadClass('dataEdit_Published');
		  
		  $directory = "C:\\Users\\Eric C. Kansa\\Documents\\OC Imports\\Bade Revisions\\Bade -new data\\thumbs\\";
		  $pObj = new ProjEdits_Bade;
		 
		  $output = array();
		  $output["uuids"] = $pObj->getImages($directory); 
		  
		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
	 }
	 
	 
	  //link geo with items
	 function iowaGeoAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Dinaa');
		  Zend_Loader::loadClass('dataEdit_Link');
		  Zend_Loader::loadClass('dataEdit_Published');
		  Zend_Loader::loadClass('dataEdit_SpaceTime');
		  Zend_Loader::loadClass('dataEdit_SpaceContain');
		  Zend_Loader::loadClass('dataEdit_Subject');
		  Zend_Loader::loadClass('dataEdit_Property');
		  Zend_Loader::loadClass('dataEdit_LinkedData');
		  
		  $pObj = new ProjEdits_Dinaa;
		  $pObj->projectUUID = "8492AEC3-E406-44C6-03CA-2BF280D8F5B0";
		  $output = array();
		  $output["geo"] = $pObj->iowaGeo(); 
		  $output["county"] = $pObj->countyGeo('Iowa'); 
		  
		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
	 }
	 
	  //link geo with items
	 function alabamaGeoAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Dinaa');
		  Zend_Loader::loadClass('dataEdit_Link');
		  Zend_Loader::loadClass('dataEdit_Published');
		  Zend_Loader::loadClass('dataEdit_SpaceTime');
		  Zend_Loader::loadClass('dataEdit_SpaceContain');
		  Zend_Loader::loadClass('dataEdit_Subject');
		  Zend_Loader::loadClass('dataEdit_Property');
		  Zend_Loader::loadClass('dataEdit_LinkedData');
		  
		  $pObj = new ProjEdits_Dinaa;
		  $pObj->projectUUID = "F905BD53-C843-4349-0A46-48FF32B5F1BE";
		  $output = array();
		  $output["geo"] = $pObj->alabamaGeo(); 
		  $output["county"] = $pObj->countyGeo('Alabama'); 
		  
		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
	 }
	 
	  //link geo with items
	 function indianaGeoAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Dinaa');
		  Zend_Loader::loadClass('dataEdit_Link');
		  Zend_Loader::loadClass('dataEdit_Published');
		  Zend_Loader::loadClass('dataEdit_SpaceTime');
		  Zend_Loader::loadClass('dataEdit_SpaceContain');
		  Zend_Loader::loadClass('dataEdit_Subject');
		  Zend_Loader::loadClass('dataEdit_Property');
		  Zend_Loader::loadClass('dataEdit_LinkedData');
		  
		  $pObj = new ProjEdits_Dinaa;
		  $pObj->projectUUID = "D42FC0EB-61B0-4937-700E-4EFEAB008677";
		  $output = array();
		  $output["geo"] = $pObj->indianageo(); 
		  $output["county"] = $pObj->countyGeo('Indiana'); 
		  
		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
	 }
	 
	 
	  //link geo with items
	 function moDatesAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Dinaa');
		  Zend_Loader::loadClass('dataEdit_Link');
		  Zend_Loader::loadClass('dataEdit_Published');
		  Zend_Loader::loadClass('dataEdit_SpaceTime');
		  Zend_Loader::loadClass('dataEdit_SpaceContain');
		  Zend_Loader::loadClass('dataEdit_Subject');
		  Zend_Loader::loadClass('dataEdit_Property');
		  Zend_Loader::loadClass('dataEdit_LinkedData');
		  
		  $pObj = new ProjEdits_Dinaa;
		  $pObj->projectUUID = "4B5721E9-2BB3-423F-5D04-1B948FA65FAB";
		  $output = array();
		  $output["dates"] = $pObj->moDates(); 
		  
		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
	 }
	 
	 
	  //link geo with items
	 function moGeoAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Dinaa');
		  Zend_Loader::loadClass('dataEdit_Link');
		  Zend_Loader::loadClass('dataEdit_Published');
		  Zend_Loader::loadClass('dataEdit_SpaceTime');
		  Zend_Loader::loadClass('dataEdit_SpaceContain');
		  Zend_Loader::loadClass('dataEdit_Subject');
		  Zend_Loader::loadClass('dataEdit_Property');
		  Zend_Loader::loadClass('dataEdit_LinkedData');
		  
		  $pObj = new ProjEdits_Dinaa;
		  $pObj->projectUUID = "4B5721E9-2BB3-423F-5D04-1B948FA65FAB";
		  $output = array();
		  $output["geo"] = $pObj->MOgeo(); 
		  $output["county"] = $pObj->countyGeo('Missouri'); 
		  
		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
	 }
	 
	 
	 //link pictures with items
	 function reloadFromJsonAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('PublishedData_JSONaccession');
		  Zend_Loader::loadClass('PublishedData_Space');
		  Zend_Loader::loadClass('PublishedData_Observe');
		  Zend_Loader::loadClass('PublishedData_Properties');
		  Zend_Loader::loadClass('PublishedData_Links');
		  Zend_Loader::loadClass('PublishedData_Resource');
		  Zend_Loader::loadClass('dataEdit_Items');
		  Zend_Loader::loadClass('dbXML_xmlSpace');
		  
		  Zend_Loader::loadClass('dbXML_xmlMedia');

		  
		  
		  $listURL = "http://opencontext/sets/.json?proj=Petra+Great+Temple+Excavations&recs=100";
		  $JSONaccessionObj = new PublishedData_JSONaccession;
		  $JSONaccessionObj->baseSpaceURI = "http://opencontext/subjects/";
		  $JSONaccessionObj->baseMediaURI = "http://opencontext/media/";
		  $JSONaccessionObj->JSONlist($listURL);
		  
		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($JSONaccessionObj);
	 }
	 
	 
	 function scDatesAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Dinaa');
		  Zend_Loader::loadClass('dataEdit_Property');
		  Zend_Loader::loadClass('dataEdit_SpaceTime');
		  Zend_Loader::loadClass('dataEdit_Published');
		  Zend_Loader::loadClass('dataEdit_SpaceContain');
		  
		  $pObj = new ProjEdits_Dinaa;
		  $pObj->projectUUID = "0EE6A09E-62E5-45F0-1CB9-F5CDA44F4D9E";
		  $output = $pObj->scDates(); 

		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
	 }
	 
	  //link geo with items
	 function scGeoAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Dinaa');
		  Zend_Loader::loadClass('dataEdit_Link');
		  Zend_Loader::loadClass('dataEdit_Published');
		  Zend_Loader::loadClass('dataEdit_SpaceTime');
		  Zend_Loader::loadClass('dataEdit_SpaceContain');
		  Zend_Loader::loadClass('dataEdit_Subject');
		  Zend_Loader::loadClass('dataEdit_Property');
		  Zend_Loader::loadClass('dataEdit_LinkedData');
		  
		  $pObj = new ProjEdits_Dinaa;
		  $pObj->projectUUID = "0EE6A09E-62E5-45F0-1CB9-F5CDA44F4D9E";
		  $output = array();
		  $output["geo"] = $pObj->SCgeo(); 
		  $output["county"] = $pObj->countyGeo('South Carolina'); 
		  
		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
	 }
	 
	 
	 
	  //link geo with items
	 function floridaGeoAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Dinaa');
		  Zend_Loader::loadClass('dataEdit_Link');
		  Zend_Loader::loadClass('dataEdit_Published');
		  Zend_Loader::loadClass('dataEdit_SpaceTime');
		  Zend_Loader::loadClass('dataEdit_SpaceContain');
		  Zend_Loader::loadClass('dataEdit_Subject');
		  
		  $pObj = new ProjEdits_Dinaa;
		  $pObj->projectUUID = "81204AF8-127C-4686-E9B0-1202C3A47959";
		  $output = $pObj->floridaGeo(); 

		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
	 }
	 
	 
	 
	 //link pictures with items
	 function dinaaCountyAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Dinaa');
		  Zend_Loader::loadClass('dataEdit_Property');
		  Zend_Loader::loadClass('dataEdit_Published');
		  Zend_Loader::loadClass('dataEdit_SpaceContain');
		  Zend_Loader::loadClass('dataEdit_LinkedData');
		  $state = "Florida";
		  $pObj = new ProjEdits_Dinaa;
		  $output = $pObj->countyGeo($state); 

		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
	 }
	 
	 
	 
	 
	 //link pictures with items
	 function floridaDatesAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Dinaa');
		  Zend_Loader::loadClass('dataEdit_Property');
		  Zend_Loader::loadClass('dataEdit_SpaceTime');
		  Zend_Loader::loadClass('dataEdit_Published');
		  Zend_Loader::loadClass('dataEdit_SpaceContain');
		  
		  $pObj = new ProjEdits_Dinaa;
		  $pObj->projectUUID = "81204AF8-127C-4686-E9B0-1202C3A47959";
		  $output = $pObj->floridaDates(); 

		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
	 }
	 
	 
	 //link pictures with items
	 function floridaDispAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Dinaa');
		  Zend_Loader::loadClass('dataEdit_Property');
		  Zend_Loader::loadClass('dataEdit_Published');
		  
		  $pObj = new ProjEdits_Dinaa;
		  $pObj->projectUUID = "81204AF8-127C-4686-E9B0-1202C3A47959";
		  $output = $pObj->floridaDisp(); 

		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
	 }
	 
	 
	 
	 //link pictures with items
	 function floridaVarSortAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Dinaa');
		 
		  
		  $pObj = new ProjEdits_Dinaa;
		  $pObj->projectUUID = "81204AF8-127C-4686-E9B0-1202C3A47959";
		  $output = $pObj->floridaVarSort(); 

		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
	 }
	 
	 
	  //link pictures with items
	 function floridaMissingRepubAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Dinaa');
		  
		  $pObj = new ProjEdits_Dinaa;
		  $pObj->projectUUID = "81204AF8-127C-4686-E9B0-1202C3A47959";
		  $output = $pObj->floridaMissingRepub(); 

		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
	 }
	 
	 
	 //link pictures with items
	 function georgiaCountyRepubAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Dinaa');
		  
		  $pObj = new ProjEdits_Dinaa;
		  $pObj->projectUUID = "64013C33-4039-46C9-609A-A758CE51CA49";
		  $output = $pObj->georgiaCountyRepub(); 

		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
	 }
	 
	 
	 
	 //link pictures with items
	 function georgiaDateFixAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Dinaa');
		  Zend_Loader::loadClass('dataEdit_Property');
		  Zend_Loader::loadClass('dataEdit_SpaceTime');
		  Zend_Loader::loadClass('dataEdit_Published');
		  Zend_Loader::loadClass('dataEdit_SpaceContain');
		  
		  $pObj = new ProjEdits_Dinaa;
		  $pObj->projectUUID = "64013C33-4039-46C9-609A-A758CE51CA49";
		  $output = $pObj->georgiaDateFix(); 

		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
	 }
	 
	 
	 function georgiaDatesAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Dinaa');
		  Zend_Loader::loadClass('dataEdit_Property');
		  Zend_Loader::loadClass('dataEdit_SpaceTime');
		  Zend_Loader::loadClass('dataEdit_Published');
		  Zend_Loader::loadClass('dataEdit_SpaceContain');
		  
		  $pObj = new ProjEdits_Dinaa;
		  $pObj->projectUUID = "64013C33-4039-46C9-609A-A758CE51CA49";
		  $output = $pObj->georgiaDates(); 

		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
	 }
	 
	 
	 //link pictures with items
	 function georgiaGeoAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Dinaa');
		  Zend_Loader::loadClass('dataEdit_Link');
		  Zend_Loader::loadClass('dataEdit_Published');
		  Zend_Loader::loadClass('dataEdit_SpaceTime');
		  Zend_Loader::loadClass('dataEdit_SpaceContain');
		  Zend_Loader::loadClass('dataEdit_Subject');
		  
		  $pObj = new ProjEdits_Dinaa;
		  $pObj->projectUUID = "64013C33-4039-46C9-609A-A758CE51CA49";
		  $output = $pObj->georgiaGeo(); 

		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
	 }
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 //link pictures with items
	 function nippurPixAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Nippur');
		  Zend_Loader::loadClass('Images_ThumbPreviewSize');
		  Zend_Loader::loadClass('dataEdit_Media');
		  Zend_Loader::loadClass('dataEdit_Link');
		  Zend_Loader::loadClass('dataEdit_Published');
		  
		  $directory = "C:\\Users\\Eric C. Kansa\\Documents\\OC Imports\\Nippur Weights\\nippur-weights\\full\\";
		  $nippurObj = new ProjEdits_Nippur;
		  $nippurObj->projectUUID = "8F947319-3C69-4847-B7A2-09E00ED90B32";
		  $output = $nippurObj->getImages($directory); 

		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
	 }
	 
	 
	 //fix paths messed up
	 function cyprusPathFixAction(){
		  
		   $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Cyprus');
		  Zend_Loader::loadClass('dataEdit_SpaceContain');
		  
		  $pObj = new ProjEdits_Cyprus;
		  $pObj->projectUUID = "3F6DCD13-A476-488E-ED10-47D25513FCB2";
		  $output = $pObj->fixPaths();

		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
		  
	 }
	 
	  //fix paths messed up
	 function cyprusDotBatchesAction(){
		  
		   $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Cyprus');
		  Zend_Loader::loadClass('dataEdit_SpaceContain');
		  Zend_Loader::loadClass('dataEdit_Property');
		  Zend_Loader::loadClass('dataEdit_Published');
		  
		  $pObj = new ProjEdits_Cyprus;
		  $pObj->projectUUID = "3F6DCD13-A476-488E-ED10-47D25513FCB2";
		  $output = $pObj->UNdotHandle();

		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
		  
	 }
	 
	 
	 
	 
	 //link finds pox for cyprus
	 function cyprusFindsPixAction(){
		  
		   $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Cyprus');
		  Zend_Loader::loadClass('Images_ThumbPreviewSize');
		  Zend_Loader::loadClass('dataEdit_Media');
		  Zend_Loader::loadClass('dataEdit_Link');
		  Zend_Loader::loadClass('dataEdit_Published');
		  
		  $directory = "C:\\GitHub\\open-context-penelope\\db-export\\cyprus\\full\\";
		  $subDirectories = array("artifacts-1", "artifacts-2", "artifacts-3", "artifacts-4", "artifacts-5");
		  $pObj = new ProjEdits_Cyprus;
		  $pObj->projectUUID = "3F6DCD13-A476-488E-ED10-47D25513FCB2";
		  $output = $pObj->getFindsImages($directory, $subDirectories);

		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
		  
	 }
	 
	 
	 //link pictures for cyprus
	 function cyprusPixAction(){
		  
		   $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Cyprus');
		  Zend_Loader::loadClass('Images_ThumbPreviewSize');
		  Zend_Loader::loadClass('dataEdit_Media');
		  Zend_Loader::loadClass('dataEdit_Link');
		  Zend_Loader::loadClass('dataEdit_Published');
		  
		  $directory = "C:\\GitHub\\open-context-penelope\\db-export\\cyprus\\full\\";
		  $subDirectory = "500-1404-discovery";
		  $pObj = new ProjEdits_Cyprus;
		  $pObj->projectUUID = "3F6DCD13-A476-488E-ED10-47D25513FCB2";
		  $output = $pObj->getImages($directory, $subDirectory); 

		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
		  
	 }
	 
	 //link pictures with items
	 function cyprusKmzAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Cyprus');
		  Zend_Loader::loadClass('Images_ThumbPreviewSize');
		  Zend_Loader::loadClass('dataEdit_Media');
		  Zend_Loader::loadClass('dataEdit_Link');
		  Zend_Loader::loadClass('dataEdit_Published');
		  Zend_Loader::loadClass('dataEdit_SpaceTime');
		  Zend_Loader::loadClass('GeoSpace_ToGeoJSON');
		  Zend_Loader::loadClass('dataEdit_SpaceContain');
		  Zend_Loader::loadClass('dataEdit_Subject');
		  
		  require ('/application/models/GeoSpace/gisconverter.php'); // first, include gisconverter.php library, but not as a Zend include
		  
		  $directory = "C:\\GitHub\\open-context-penelope\\db-export\\cyprus\\";
		  $pObj = new ProjEdits_Cyprus;
		  $pObj->projectUUID = "3F6DCD13-A476-488E-ED10-47D25513FCB2";
		  $output = $pObj->getKmz($directory); 

		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
	 }
	 
	 
	 function redoObjAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();    
		  
		  $badUUIDs = array(
	 		'7FF17B39-BFD9-4F41-A5FE-6420C09F9860'  
		  );
		  
		  $db = Zend_Registry::get('db');
		  Zend_Loader::loadClass('dataEdit_SpaceIdentity');
		  Zend_Loader::loadClass('dataEdit_Property');
		  Zend_Loader::loadClass('dataEdit_Published');
		  Zend_Loader::loadClass('dataEdit_SpaceContain');
		  
		  $sql = "SELECT field_summary.field_name, field_summary.field_label,
					 var_tab.variable_uuid, field_summary.project_id
					 FROM field_summary
					 LEFT JOIN var_tab ON (
						  var_tab.var_label LIKE field_summary.field_label
						  AND var_tab.project_id = field_summary.project_id
						  )
					 WHERE field_summary.source_id = 'z_5_c01d889bc'
					 AND field_summary.field_type = 'Property'
					 ";
		  $tabFields = array();
		  $rawTabFields = $db->fetchAll($sql);
		  foreach($rawTabFields as $field){
				if($field["variable_uuid"]){
					 $fieldLabel = $field["field_name"];
					 $tabFields[$fieldLabel] = $field["variable_uuid"];
				}
		  }
		  
		  
		  $spaceEdit = new dataEdit_SpaceIdentity;
		  $propEdit = new dataEdit_Property;
		  $pubObj = new dataEdit_Published;
		  
		  $spaceEdit->actSourceTab = 'z_5_c01d889bc';
		  
	 
		  
		  $output = array();
		  foreach($badUUIDs as $uuid){
				
				$pubObj->deleteFromPublishedDocsByUUID($uuid);
				$pubObj->deleteFromPublishedDocsByChildUUID($uuid);
				
				
				$sql = "UPDATE space SET source_id = 'z_5_c01d889bc' WHERE uuid = '".$uuid."' LIMIT 1;";
				$db->query($sql, 2);
				
				$sql = "SELECT source_id, space_label, full_context, class_uuid, project_id
				FROM space
				WHERE uuid = '$uuid' LIMIT 1;
				";
				
				$result = $db->fetchAll($sql);
				$result = false;
				
				
				if($result){
					 $itemLabel = $result[0]["space_label"];
					 $itemContext = $result[0]["full_context"];
					 $sourceID = $result[0]["source_id"];
					 $classUUID = $result[0]["class_uuid"];
					 $projectUUID = $result[0]["project_id"];
					 
					 $sourceIDs = $spaceEdit->getSourceIDs($itemLabel, $itemContext, $sourceID, $classUUID);
					 $sourceData = $spaceEdit->itemDuplicateNoObs($uuid, $sourceIDs);
					 
					 //echo print_r($sourceIDs);
					 //echo print_r($sourceData);
					 //die;
					 foreach($sourceData as $subjectUUID => $idArray){
						  
						  //delete the old observations
						  $where = "subject_uuid = '$subjectUUID' ";
						  $db->delete("observe", $where);
						  
						  $id = $idArray["id"];
						  
						  $sql = "SELECT * FROM z_5_c01d889bc AS otab WHERE id = $id LIMIT 1;";
						  //echo " ".$sql." ";
						  $originalData = $db->fetchAll($sql);
						  foreach($originalData as $oRow){
								foreach($oRow as $fieldKey => $value){
									 if($value){
										  if(array_key_exists($fieldKey, $tabFields)){
												$variableUUID = $tabFields[$fieldKey];
												$valueUUID = $propEdit->get_make_ValID($value, $projectUUID);
												$propUUID = $propEdit->get_make_PropID($variableUUID, $valueUUID, $projectUUID);
												
												$hashObs = md5($projectUUID . "_" . $subjectUUID . "_" . 1 . "_" . $propUUID);
												$data = array("project_id" => $projectUUID,
																  "source_id" => $sourceID,
																  "hash_obs" => $hashObs,
																  "subject_type" => "Locations or Objects",
																  "subject_uuid" => $subjectUUID,
																  "obs_num" => 1,
																  "property_uuid" => $propUUID
																  );
												try{
													 $db->insert('observe', $data );
												}
												catch (Exception $e) {
												
												}
												
												$output[$uuid][$id][$fieldKey] = array("link" => "http://penelope.oc/preview/space?UUID=".$subjectUUID,
																									"subjectUUID" => $subjectUUID,
																									"propUUID" => $propUUID,
																									"variableUUID" => $variableUUID,
																									"valueUUID" => $valueUUID,
																									 "value" => $value);
										  }
									 }
								}
						  }
						  
						  $firstLoop = false;
					 }//array of sourceData
					 
				}
				
		  }//end loop
		  
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_Json::encode($output);
		  
	 }
	 
	  //add links from media items back to diary items
	 function indianaArtAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Dinaa');
		  
		  $dinaaObj = new ProjEdits_Dinaa;
		  $ngram = 3;
		  $output = $dinaaObj->getIndianaArtifacts($ngram);
		  
		  //header('Content-Type: application/json; charset=utf8');
		  //echo Zend_Json::encode($output);
		  echo "<table>";
		  foreach($output[$ngram."grams"] as $key => $val){
				if($val>1){
					 echo "<tr>";
					 echo "<td>$key</td><td>$val</td>";
					 echo "</tr>";
				}
		  }
		  echo "</table>";
	 }
	 
	 
	 
	 function catalMetricsDocAction(){
		  $this->_helper->viewRenderer->setNoRender();
		  
		  $teeth = array(" C ",
							  " C::",
							  " C,",
							  "tooth",
							  "teeth",
							  "M1 ",
							  "M1::",
							  "P1 ",
							  "M2 ",
							  "M2::",
							  "P2 ",
							  "P3 ",
							  "M3 ",
							  "M3::",
							  "M3-P2",
							  "P4 ",
							  "P2-P4",
							  "P2&P3",
							  "dp3"
							  );
		  
		  $cranialNote =
		  '
<div>
	 <p>The document <a href="http://opencontext.org/documents/C34ECB9E-33C9-43CB-906D-24410F85ED0F">Cranial Elements Data Documentation</a> has additional information about
the fields used to describe cranial and tooth measurements at Çatalhöyük.
	 </p>
</div>';
		  
		  $postCranialNote =
		  '
<div>
	 <p>The document <a href="http://opencontext.org/documents/D1B075BF-DD3E-4824-0957-310263DEEFA1">Post-Cranial Elements Data Documentation</a> has additional information about
the fields used to describe post-cranial element measurements at Çatalhöyük.
	 </p>
</div> ';
		  
		  $rawFieldList = file_get_contents("http://penelope.oc/editorial/var-lookup?q=%3A%3A&projectUUID=1B426F7C-99EC-4322-4069-E8DBD927CCF1&varType=0&classUUID=0");
		  $fieldList = Zend_Json::decode($rawFieldList);
		  foreach($fieldList as $field){
				$actNote = $postCranialNote;
				$pc = true;
				foreach($teeth as $tooth){
					 if(strstr($field["varLabel"], $tooth)){
						  $actNote = $cranialNote;
						  $pc = false;
					 }
				}
				$noteReq = "http://penelope.oc/editorial/var-add-note?varUUID=".$field["varUUID"]."&varNote=".urlencode($actNote);
				
				if($pc){
					 echo "<p>Post cranial: ".$field["varLabel"]." ";
				}
				else{
					  echo "<p><b>Cranial: ".$field["varLabel"]."</b> ";
				}
				$done = file_get_contents($noteReq);
				echo $done." </p>";
		  }
		  echo "done";
	 }
	 
	 
	 
	 
	 
	 
	 
	 
	  //add links from media items back to diary items
	 function pcTbScrapePropsAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Murlo');
		  Zend_Loader::loadClass('dataEdit_Property');
		  
		  $murloObj = new ProjEdits_Murlo;
		  
		  $output = $murloObj->TBaddDiaryProperties();
		  
		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
	 }
	 
	 
	 
	 
	 
	 
	 
	  //add links from media items back to diary items
	 function pcTbScrapePagesDiaryAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Murlo');
		  Zend_Loader::loadClass('dataEdit_Link');
		  
		  $murloObj = new ProjEdits_Murlo;
		  
		  $output = $murloObj->TBscrapeDiary();
		  
		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
	 }
	 
	 
	 
	 
	 
	 
	 
	  //add links from media items back to diary items
	 function pcTbScrapeCleanAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Murlo');
		  
		  $murloObj = new ProjEdits_Murlo;
		  
		  $output = $murloObj->TBScrapeClean();
		  
		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
	 }
	 
    
	 
	  //add links from media items back to diary items
	 function pcTbScrapeParseAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Murlo');
		  
		  $murloObj = new ProjEdits_Murlo;
		  $murloObj->linkFix();
		  $output = $murloObj->TBscrapeParse();
		  
		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
	 }
	 
	 
	  //add links from media items back to diary items
	 function pcTbAttributeAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Murlo');
		  Zend_Loader::loadClass('dataEdit_Link');
		  
		  $murloObj = new ProjEdits_Murlo;
		  $typeToAttribute = 'Diary / Narrative';
		  //$typeToAttribute = 'Media (various)';
		  
		  $output = $murloObj->TBauthorLink($typeToAttribute );
		  
		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
	 }
	 
	 
	 
	 //add links from media items back to diary items
	 function pcTbMediaLinkAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Murlo');
		  Zend_Loader::loadClass('dataEdit_Link');
		  
		  $murloObj = new ProjEdits_Murlo;
		  $output = $murloObj->TBmediaLink();
		  
		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
	 }
	 
	 
	 
	  function pcTbTextCleanAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Murlo');
		  $jsonURL = "http://penelope.oc/csv-export/TrenchesGeo.geojson";
		  //$jsonURL = "http://penelope.oc/csv-export/murlo-trenches-b.txt";
		  
		  $murloObj = new ProjEdits_Murlo;
		  $output = $murloObj->TBtransClean();
		  
		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
	 }
	 
	 
	 function pcTbNamesAction(){
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Murlo');
		  $murloObj = new ProjEdits_Murlo;
		  header('Content-Type: text/html; charset=utf8');
		  echo $murloObj->TBauthors();
	 }
	 
	 
	 function pcTbImagesAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Murlo');
		  $jsonURL = "http://penelope.oc/csv-export/TrenchesGeo.geojson";
		  //$jsonURL = "http://penelope.oc/csv-export/murlo-trenches-b.txt";
		  
		  $murloObj = new ProjEdits_Murlo;
		  $output = $murloObj->TBimagePageNumbers();
		  
		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
	 }
	 
	 
	 
	 
	 
	 function pcGeoJsonFindsAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Murlo');
		  $jsonURL = "http://penelope.oc/csv-export/ArtifactsGeoJSON.json";
		  //$jsonURL = "http://penelope.oc/csv-export/murlo-trenches-b.txt";
		  
		  $murloObj = new ProjEdits_Murlo;
		  $output = $murloObj->findsGeoJsonAdd($jsonURL);
		  
		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
	 }
	 
	 function pcGeoAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Murlo');
		  $jsonURL = "http://penelope.oc/csv-export/TrenchesGeo.geojson";
		  //$jsonURL = "http://penelope.oc/csv-export/murlo-trenches-b.txt";
		  
		  $murloObj = new ProjEdits_Murlo;
		  $output = $murloObj->geoJsonAdd($jsonURL);
		  
		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
	 }
	 
	 //use a solr query to republish a list of items
	 function republishSolrAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  /*
		  $solrQuery = "http://opencontext.org:8983/solr/select?facet=true&facet.mincount=1&fq=%7B%21cache%3Dfalse%7Dproject_name%3AMurlo++%26%26+NOT+project_id%3A0+NOT+def_context_0%3AItaly+%26%26+%28+%28item_type%3Aspatial%29+%29&facet.field=def_context_0&facet.field=project_name&facet.field=item_class&facet.field=time_span&facet.field=geo_point&facet.query=image_media_count%3A%5B1+TO+%2A%5D&facet.query=other_binary_media_count%3A%5B1+TO+%2A%5D&facet.query=diary_count%3A%5B1+TO+%2A%5D&sort=interest_score+desc&wt=json&json.nl=map&q=%2A%3A%2A&start=0&rows=200";
		  
		  $solrQuery = "http://localhost:8983/solr/select?facet=true&facet.mincount=1&fq=%7B%21cache%3Dfalse%7DNOT+project_id%3A0+%26%26+%28+%28item_type%3Aspatial%29+%29&facet.field=def_context_1&facet.field=project_name&facet.field=item_class&facet.field=time_span&facet.field=geo_point&facet.field=geo_path&facet.query=image_media_count%3A%5B1+TO+%2A%5D&facet.query=other_binary_media_count%3A%5B1+TO+%2A%5D&facet.query=diary_count%3A%5B1+TO+%2A%5D&sort=interest_score+desc&wt=json&json.nl=map&q=%28+%28default_context_path%3AItaly%2F%2A+%29+%7C%7C+%28default_context_path%3AItaly+%29%29+%26%26+%28geo_path%3A12023202222130310%2A%29&start=0&rows=400";
		  */
		  
		  //context missng
		  $solrQuery = "http://localhost:8983/solr/select?facet=true&facet.mincount=1&fq=%7B%21cache%3Dfalse%7Ditem_class%3A*++%26%26+NOT+project_id%3A0+%26%26+%28+%28item_type%3Aspatial%29+%29&facet.field=def_context_0&facet.field=project_name&facet.field=item_class&facet.field=time_path&facet.field=geo_point&facet.field=top_taxon&facet.field=geo_path&facet.query=image_media_count%3A%5B1+TO+%2A%5D&facet.query=other_binary_media_count%3A%5B1+TO+%2A%5D&facet.query=diary_count%3A%5B1+TO+%2A%5D&sort=interest_score+desc&wt=json&json.nl=map&q=%28%2A%3A%2A%29+%26%26+%28geo_path%3A0%2A%29-def_context_0%3A%5B%22%22+TO+*%5D&start=0&rows=1500";
		  
		  //geo tile wrong
		  
		  $solrQuery = "http://localhost:8983/solr/select?facet=true&facet.mincount=1&fq=%7B%21cache%3Dfalse%7DNOT+project_id%3A0+%26%26+%28+%28item_type%3Aspatial%29+%29&facet.field=def_context_0&facet.field=project_name&facet.field=item_class&facet.field=time_path&facet.field=geo_point&facet.field=geo_path&facet.query=image_media_count%3A%5B1+TO+%2A%5D&facet.query=other_binary_media_count%3A%5B1+TO+%2A%5D&facet.query=diary_count%3A%5B1+TO+%2A%5D&sort=interest_score+desc&wt=json&json.nl=map&q=%28%2A%3A%2A%29+%26%26+%28geo_path%3A0%29&start=0&rows=10";
		  
		
		  
		  //$solrQuery = "http://opencontext.org/all/solr";
		  
		  $respJSONstring = file_get_contents($solrQuery);
		  $solrJSON = Zend_Json::decode($respJSONstring);
		  $projectUUID = false;
		  $output = array();
		  $localPubBaseURI = "http://penelope.oc/publish/publishdoc?projectUUID=".$projectUUID."&itemType=space&doUpdate=true&itemUUID=";
		  $ocPubBaseURI = "http://penelope.oc/publish/publishdoc?projectUUID=".$projectUUID."&itemType=space&doUpdate=true&pubURI=http://opencontext.org/publish/item-publish&itemUUID=";
		  
		  foreach($solrJSON["response"]["docs"] as $doc){
				
				$uuid = $doc["uuid"];
				$projectName = $doc["project_name"];
				$pubResp = array();
				$resp = file_get_contents($localPubBaseURI.$uuid);
				$pubResp["local"] = Zend_Json::decode($resp);
				//sleep(1);
				
				//$resp = file_get_contents($ocPubBaseURI.$uuid);
				//$pubResp["oc"] = Zend_Json::decode($resp);
				
				$output[$uuid] = $pubResp;
				unset($pubResp);
		  }
		  
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_Json::encode($output);
	 }
	 
	 
	 function republishListAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  $badUUIDs = array("DCA11CDF-3E87-475F-C789-60DA86FBBE54",
								"DB066511-7C7E-436C-9D8E-16BC1C07DE48",
								"C854CEE1-69A7-4D95-8238-B4C5351E2194",
								"E1A77095-6911-4F2B-B289-03E82E080B32",
								"BFA9D025-CD31-4843-8668-A99F0398FBEE",
								"E0945E36-640E-4DB9-9656-1817735584C7",
								"0BAFF277-EE4B-4126-516A-262A3FDCA290",
								"F64769C5-F02B-4B7D-8BBA-5E12A6B1875B",
								"45C2AF9E-B8D8-48ED-6737-5E78FCD133B1",
								"090D74C0-D96B-4E85-4821-9609C7D62478",
								"713EB6BC-C6EE-40B3-E3D0-A261E318AF1C",
								"80916EB2-1FFD-4A36-9F12-79725B29AA65",
								"FE5E81DA-CE4C-4316-0C7B-96B0789F72B4",
								"274BE5F6-5D40-410A-4C06-F1535284DDA2",
								"2E678FF9-0B67-4EB3-DA97-632944DA6A93",
								"60597BB4-ED03-42B4-36E5-38918F901CE5",
								"C459F053-A3D1-4466-659D-EB5EF0A3676A",
								"0D200853-7A31-48C0-4828-4686B5982C65"
		  );
		  
		  $projectUUID = '8F947319-3C69-4847-B7A2-09E00ED90B32';
		  $output = array();
		  $localPubBaseURI = "http://penelope.oc/publish/publishdoc?projectUUID=".$projectUUID."&itemType=space&doUpdate=true&itemUUID=";
		  $ocPubBaseURI = "http://penelope.oc/publish/publishdoc?projectUUID=".$projectUUID."&itemType=space&doUpdate=true&pubURI=http://opencontext.org/publish/item-publish&itemUUID=";
		  
		  foreach($badUUIDs as $uuid){
				
				$pubResp = array();
				$resp = file_get_contents($localPubBaseURI.$uuid);
				$pubResp["local"] = Zend_Json::decode($resp);
				sleep(1);
				
				$resp = file_get_contents($ocPubBaseURI.$uuid);
				$pubResp["oc"] = Zend_Json::decode($resp);
				
				$output[$uuid] = $pubResp;
				unset($pubResp);
		  }
		  
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_Json::encode($output);
		  
	 }
	 
	 
	 
	 function finishIndexAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Murlo');
		  $jsonURL = "http://opencontext.org/publish/index-update";
		  //$jsonURL = "http://opencontext.org/publish/index-update";
		  //$jsonURL = "http://penelope.oc/csv-export/murlo-trenches-b.txt";
		  $itemCount = 1;
		  $loopCount = 0;
		  while($itemCount > 0){
				
				sleep(.1);
				$json = file_get_contents($jsonURL);
				$jsonArray = Zend_Json::decode($json);
				$itemCount = count($jsonArray["indexItems"]);
				if(!$jsonArray["indexItems"]){
					 break;
				}
				$loopCount++;
		  }
		  
		  
		  
		  
		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($loopCount);
	 }
	 
	 
	 
	 
	 
	 
	 
	 function republishSolrImagesAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  
		  $solrQuery = "http://localhost:8983/solr/select?facet=true&facet.mincount=1&fq=%7B%21cache%3Dfalse%7DNOT+project_id%3A0+%26%26+%28+%28item_type%3Aspatial%29+%29+%26%26+799fdbd6e04acf4144f6292d3c6fdd98a11df31a_lent_taxon%3Ahttp%5C%3A%2F%2Feol.org%2Fpages%2F2851411%2F%23sheepgoat++%26%26+top_lrel_taxon%3Ahttp%5C%3A%2F%2Fpurl.org%2FNET%2Fbiol%2Fns%23term_hasTaxonomy+&facet.field=e1ebfc4569d81cbc4d21b9ca08bc3e85ce09262d_lent_taxon&facet.field=799fdbd6e04acf4144f6292d3c6fdd98a11df31a_lent_taxon&facet.field=top_taxon&facet.field=def_context_0&facet.field=project_name&facet.field=item_class&facet.field=time_span&facet.field=geo_point&facet.field=geo_path&facet.query=image_media_count%3A%5B1+TO+%2A%5D&facet.query=other_binary_media_count%3A%5B1+TO+%2A%5D&facet.query=diary_count%3A%5B1+TO+%2A%5D&sort=interest_score+desc&wt=json&json.nl=map&q=%28%2A%3A%2A%29+%26%26+%28geo_path%3A1%2A%29&start=0&rows=10";
		  
		  /*
		  $solrQuery = "http://localhost:8983/solr/select?facet=true&facet.mincount=1&fq=%7B%21cache%3Dfalse%7DNOT+project_id%3A0+%26%26+%28+%28item_type%3Aspatial%29+%29&facet.field=def_context_1&facet.field=project_name&facet.field=item_class&facet.field=time_span&facet.field=geo_point&facet.field=geo_path&facet.query=image_media_count%3A%5B1+TO+%2A%5D&facet.query=other_binary_media_count%3A%5B1+TO+%2A%5D&facet.query=diary_count%3A%5B1+TO+%2A%5D&sort=interest_score+desc&wt=json&json.nl=map&q=%28+%28default_context_path%3AItaly%2F%2A+%29+%7C%7C+%28default_context_path%3AItaly+%29%29+%26%26+%28geo_path%3A12023202222130310%2A%29&start=0&rows=400";
		  */
		  
		  $respJSONstring = file_get_contents($solrQuery);
		  $solrJSON = Zend_Json::decode($respJSONstring);
		  $output = array();
		  $localPubBaseURI = "http://penelope.oc/publish/publishdoc?projectUUID=DF043419-F23B-41DA-7E4D-EE52AF22F92F&itemType=media&doUpdate=true&itemUUID=";
		  $ocPubBaseURI = "http://penelope.oc/publish/publishdoc?projectUUID=DF043419-F23B-41DA-7E4D-EE52AF22F92F&itemType=media&doUpdate=true&pubURI=http://opencontext.org/publish/item-publish&itemUUID=";
		  
		  foreach($solrJSON["response"]["docs"] as $doc){
				
				$uuid = $doc["uuid"];
				$pubResp = array();
				$resp = file_get_contents($localPubBaseURI.$uuid);
				$pubResp["local"] = Zend_Json::decode($resp);
				sleep(1);
				
				$resp = file_get_contents($ocPubBaseURI.$uuid);
				$pubResp["oc"] = Zend_Json::decode($resp);
				
				$output[$uuid] = $pubResp;
				unset($pubResp);
		  }
		  
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_Json::encode($output);
	 }
	 
	 function addSpaceApiAction(){
		  
		  //this line is necessary for ajax calls:
        $this->_helper->viewRenderer->setNoRender();        
        
        //get selected root item then add it and all children to database
        $baseURL = "http://opencontext/subjects/";
		  $baseMediaURL = "http://opencontext/media/";
       
		  Zend_Loader::loadClass('PublishedData_Hierarchy');
        Zend_Loader::loadClass('PublishedData_Space');
        Zend_Loader::loadClass('PublishedData_Observe');
		  Zend_Loader::loadClass('PublishedData_Properties');
		  Zend_Loader::loadClass('PublishedData_Links');
		  Zend_Loader::loadClass('PublishedData_Resource');
		  Zend_Loader::loadClass('dbXML_xmlSpace');
		  Zend_Loader::loadClass('dbXML_xmlMedia');
		  
		  
		  $this->_helper->viewRenderer->setNoRender();
		  
		  $solrQuery = "http://localhost:8983/solr/select?facet=true&facet.mincount=1&fq=NOT+project_id%3A0+%26%26+%28+%28item_type%3Aspatial%29+%29&facet.field=def_context_1&facet.field=project_name&facet.field=item_class&facet.field=time_span&facet.field=geo_point&facet.query=image_media_count%3A%5B1+TO+%2A%5D&facet.query=other_binary_media_count%3A%5B1+TO+%2A%5D&facet.query=diary_count%3A%5B1+TO+%2A%5D&sort=interest_score+desc&wt=json&json.nl=map&q=+%28default_context_path%3AIsrael%2F%2A+%29+%7C%7C+%28default_context_path%3AIsrael+%29&start=0&rows=10000";
		  
		  $respJSONstring = file_get_contents($solrQuery);
		  $solrJSON = Zend_Json::decode($respJSONstring);
		  $output = array();
		  $hierarchyObj = new PublishedData_Hierarchy;
		  $hierarchyObj->baseSpaceURI = $baseURL;
		  $hierarchyObj->baseMediaURI = $baseMediaURL;
		  foreach($solrJSON["response"]["docs"] as $doc){
				
				$uuid = $doc["uuid"];
				$hierarchyObj->addHierarchy($uuid);
		  }
		  
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_Json::encode(array("done" => $hierarchyObj->doneURIs, "errors" => $hierarchyObj->errors));
		  
		  
	 }
	 
	 
	 //load up old space data from XML documents
	 function addSpaceHierarchyAction(){
		  
		  //this line is necessary for ajax calls:
        $this->_helper->viewRenderer->setNoRender();        
        
        //get selected root item then add it and all children to database
        $baseURL = "http://opencontext/subjects/";
		  $baseMediaURL = "http://opencontext/media/";
        $rootUUID = "HazorZooSPA00000main";
		  
		  if(isset($_GET["root"])){
				$rootUUID = $_GET["root"];
		  }
		  
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
	 
	 
	 function propertyGetAction(){
		  $baseURL = "http://opencontext/subjects/";
		  Zend_Loader::loadClass('PublishedData_Space');
        Zend_Loader::loadClass('PublishedData_Observe');
		  Zend_Loader::loadClass('PublishedData_Properties');
		  Zend_Loader::loadClass('dbXML_xmlSpace');
		  
		  $spaceXML = new dbXML_xmlSpace;
		  $namespaces = $spaceXML->nameSpaces();
		  
		  $this->_helper->viewRenderer->setNoRender();        
		  $db = Zend_Registry::get('db');
		  $sql = "SELECT uuid, project_id, source_id
		  FROM space
		  WHERE (project_id = '3' OR project_id = 'TESTPRJ0000000004') AND class_uuid = '881CEDA3-C445-4C9C-4D4B-634BD2963892' ";
		  
		  $sql = "SELECT uuid, project_id, source_id
		  FROM space
		  WHERE 1 ";
		  
		  $sql = "SELECT uuid, project_id, source_id
		  FROM space
		  WHERE uuid = 'HazorZooSPA0000008447' ";
		  
		  
		  $result = $db->fetchAll($sql);
		  foreach($result as $row){
				$uuid = $row["uuid"];
				$projectID = $row["project_id"];
				$sourceID = $row["source_id"];
				
				$itemURL = $baseURL.$uuid.".xml";
				@$xmlString = file_get_contents($itemURL);
				if($xmlString != false){
					 echo "here";
					 die;
					 @$itemXML = simplexml_load_string($xmlString);
					 if($itemXML != false){
						  
						  foreach($namespaces as $prefix => $nsURI){
								$itemXML->registerXPathNamespace($prefix, $nsURI);
						  }
						  
						  $propsObj = new PublishedData_Properties;
						  $propsObj->startDB();
						  $propsObj->itemUUID = $uuid ;
						  $propsObj->projectUUID = $projectID;
						  $propsObj->sourceID = $sourceID;
						  $properties = $propsObj->itemPropsRetrieve($itemXML);
						  $propsObj->saveData($properties);
						  
						  echo"<br/> Done with: <a href='http://penelope.oc/preview/space?UUID=".$uuid."'>".$uuid."</a>";
					 }
				}
		  }//end loop
	 }//end function
	 
	 
	 
	 
	 
	 function redoBoneAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();    
		  
		  $badUUIDs = array(
	 		'01441BAE-754C-4E62-6BA4-124BFDF3EEBC', 
		  '0200669B-35A6-43EB-7A41-91AE643D382D', 
		  '047AFCCA-AA60-43D4-047E-FF7DA51B593E', 
		  '04A63CD6-7FF8-4180-7D79-BA3C3C66595D', 
		  '059E4CDF-DDA2-49BF-A8D2-9AD85D77ADF6', 
		  '0607E92B-3D69-4AA5-53DE-4E6FB67FC9B9', 
		  '08A06A64-93E9-4931-E79E-F0617D31D4F7', 
		  '0C4B1D47-540C-4761-2AD6-E414A852A3D6', 
		  '108D6BEC-E96F-4A1D-AEAE-4F6DAC86A014', 
		  '12C8FAD4-2A68-4FCF-4ACD-5746EA5D9AB9', 
		  '14724176-1470-4EAB-3AA5-F080E24DF4BC', 
		  '15C344F0-44E2-4BDE-9506-51C8AA994541', 
		  '16963252-985A-464F-EB3E-F51DEE2B33DB', 
		  '16F42A27-CE3E-4CAF-FBA8-77A9A2C9C36C', 
		  '181C0884-910C-42E7-6D9B-7510C49D8565', 
		  '19163DDE-37AF-4D61-1E8A-AB8346D7605B', 
		  '1CCD6761-12C8-401F-0F2F-4C6C48041AF2', 
		  '1CDBFC9D-6E1C-42DA-AD11-7756A9B42DEF', 
		  '1FA1323E-5674-4603-2F40-7849CF8E5DBB', 
		  '21988064-0F1F-4A5D-AECD-BF55BB0CA2E1', 
		  '242F2DA4-EB88-4A0C-0826-A96A247BC69B', 
		  '250EA659-3C7C-4CB0-E6E9-2A84B33946D8', 
		  '25139E00-1569-4586-5597-4FF7B72335D4', 
		  '2823BAC8-7571-4FFC-D2FD-B31E7440F60C', 
		  '292ECCF7-63A4-43CD-8605-56411AF81D12', 
		  '2DEFF61C-7016-4BFE-9E1B-B1666527C021', 
		  '32B7EC55-8D57-4F2C-A627-368CFB35C354', 
		  '340D2E85-3FA2-499C-BDC6-7D42DE8B3F1C', 
		  '343871AE-758F-44C1-B4E8-41DE73246F15', 
		  '348E355C-67CE-4B8A-2BA2-9D3BBB350BCC', 
		  '363D0E3E-BFBB-48CC-D743-ACED753F69A2', 
		  '3849D365-513B-44CC-28B9-349BAA2BD0FB', 
		  '38DFEAA4-E8E3-4A50-ED0D-406B1A8D183B', 
		  '3ACA5C4D-DC04-4FAF-83C4-EF2BDE9E8C6F', 
		  '3B70AE9C-3874-4503-C019-D8E790ABBF54', 
		  '3E15833D-6BBF-46AA-8B39-6F939495C52A', 
		  '41530EDC-6017-40D9-57E8-0158E8A37882', 
		  '4224A848-447A-45D6-17C6-0F58A32F28FE', 
		  '4316715A-17DF-416E-A02D-EF6CAE9D147A', 
		  '44C5229B-F1D7-4D7A-E397-DE61DA0D8F68', 
		  '4639210E-7107-44F3-8909-160F07518857', 
		  '46F8E4F3-E16F-4B98-4CEB-061C4EAFCA17', 
		  '4D62F3A0-AB5C-4281-A6A9-8EF358F66A56', 
		  '4FA60232-AE64-4229-3C66-CCB484A4DD7A', 
		  '5446567D-CD42-4528-BFDF-01430C8B2F89', 
		  '567FCC18-03F7-49B4-ED61-F0346BD57CD1', 
		  '5AD7AE95-F044-4646-8AC7-4295E4EF3724', 
		  '5D1853DB-439A-40C9-6318-2C3A0D6AE54A', 
		  '66CF332B-D95B-4286-B771-228F90B2EE9D', 
		  '67388B7F-4C27-4F4F-C0D4-7268E6BFBDED', 
		  '688E93C7-5E6D-42AE-B7F3-FC1618ED2E73', 
		  '6C64F66E-8DC0-4972-7EA3-135C6702C4F1', 
		  '6E1F08CE-8FD0-43D2-3A21-1B5887204EA7', 
		  '6E44EEDD-C941-4841-E1ED-B0589BB86832', 
		  '6F07D707-9F61-4327-684C-BCB675F2AE08', 
		  '6F0F00AB-91DE-4D94-1139-6C88903D383E', 
		  '76F0D616-250D-4AC4-466E-BB2CB6BE6376', 
		  '7E6A88E7-F3E3-49F5-793D-E54E3FF4265B', 
		  '83B92423-794A-4FF4-5569-0E1341E7E675', 
		  '84345D49-2DB3-4C7A-05EF-8C1E817E0C6A', 
		  '8779B999-D1B8-4694-DDB7-CE5F6E61D7BE', 
		  '889763AA-0D0E-4D55-C3F8-95C543B59489', 
		  '8913B5ED-5C7C-4BB7-7DB7-06B1BDBFFB1F', 
		  '8C6F0BC8-3189-49CD-7928-C2B7E062E719', 
		  '8CCF6C8E-55C0-4D91-83ED-C298645998DE', 
		  '8EC793FE-1FA8-45C6-EB12-0C3D90E2795F', 
		  '8FE01A8F-B20A-4348-4D34-03F365CFD3A1', 
		  '912D181E-DFE8-4965-D83E-B6C98C3B72EB', 
		  '914E4FDE-8B9A-4602-D3F8-21FDB3788E45', 
		  '92587F94-E920-48D2-4FF1-B4D4F6B9C361', 
		  '94819171-7079-4024-3B22-8C8E7E3C9B5F', 
		  '96F898BF-A473-42FF-C6F1-89ECCDB1F39C', 
		  '9745844F-6CBB-4A6B-13B3-1FC7FAB3EA32', 
		  '9822EE58-1E2F-42F9-6502-F5EAE0D2CA39', 
		  '9B81BC5D-2723-4B96-910C-FB306BFEA7CA', 
		  'A3A5684C-9B04-4629-5B9D-E7181EE6AB9E', 
		  'A500860F-2349-423D-788F-E28D465E054B', 
		  'A85C37A5-BC95-4338-99DC-99DE61B41EB6', 
		  'AFDADE4D-258E-4CA5-E9EC-4816CC4F70EF', 
		  'B54F1D45-AA29-4CBF-8029-3870E47AEDBD', 
		  'B56D1197-8E1E-47FD-DF69-EBD0A5BC1543', 
		  'B6E99761-7C2F-4783-E854-E666058F9536', 
		  'B89AF4FA-BD0B-448E-E955-E129229209E1', 
		  'B95A67B3-89E8-4BBD-74A1-419A3A9A1206', 
		  'BA6761E0-F87D-444E-0590-56E883251CCC', 
		  'C277864E-B0F9-4E3F-5D6D-4E1068C94152', 
		  'C486EB00-6290-478B-D1E2-E1E00F41895C', 
		  'C49F1AA0-0E50-40BF-7E1A-115BB32BD7BA', 
		  'C78FACC3-4B62-436F-3CCB-D150457C8BFE', 
		  'CA804F46-3C71-454D-FF31-E5B2384B7468', 
		  'CB513F76-084E-45F5-39EA-878FD0FC8F48', 
		  'CB879D7C-3524-4968-C1B8-DA9109FEBD49', 
		  'CD2B56AE-619E-46B6-5B26-55F3A9FD9029', 
		  'CDA374A3-7F5A-4F76-C479-27BA66872D94', 
		  'D07ADDC8-3496-4EA4-10BB-39D03F9C507F', 
		  'D2D8FA29-7D25-4901-A435-84128A78277F', 
		  'D300D36C-922B-461F-B467-A6E352D06773', 
		  'DBE40725-677B-4C26-92C9-CB8352A69C7C', 
		  'DF4C063E-7CE2-4955-C882-3CA420ECE0AA', 
		  'E10C2368-6A4B-4528-28FE-673F06218358', 
		  'E119757F-13BB-4CD6-FDCF-70C11B1C7EA2', 
		  'E7465FF9-B1BE-4503-3DD0-6560548EAA23', 
		  'EF221176-6028-436D-3541-C89A2AE9F20E', 
		  'F2A34C09-E652-4C1E-D1DA-A37663EED096', 
		  'F84014C3-33E1-4FDF-35C2-EB00A1535D76', 
		  'FC2C7F03-C8CA-487C-BC0E-7787371A3278', 
		  'FC504975-D305-4949-F781-92A9B53E2220', 
		  'FE70CC58-72ED-4AAD-AB6A-519699FB47CD',  
		  );
		  
		  $db = Zend_Registry::get('db');
		  Zend_Loader::loadClass('dataEdit_SpaceIdentity');
		  Zend_Loader::loadClass('dataEdit_Property');
		  Zend_Loader::loadClass('dataEdit_Published');
		  Zend_Loader::loadClass('dataEdit_SpaceContain');
		  
		  $sql = "SELECT field_summary.field_name, field_summary.field_label,
					 var_tab.variable_uuid, field_summary.project_id
					 FROM field_summary
					 LEFT JOIN var_tab ON (
						  var_tab.var_label LIKE field_summary.field_label
						  AND var_tab.project_id = field_summary.project_id
						  )
					 WHERE field_summary.source_id = 'z_1_ee76ce40e'
					 AND field_summary.field_type = 'Property'
					 ";
		  $tabFields = array();
		  $rawTabFields = $db->fetchAll($sql);
		  foreach($rawTabFields as $field){
				if($field["variable_uuid"]){
					 $fieldLabel = $field["field_name"];
					 $tabFields[$fieldLabel] = $field["variable_uuid"];
				}
		  }
		  
		  
		  $spaceEdit = new dataEdit_SpaceIdentity;
		  $propEdit = new dataEdit_Property;
		  $pubObj = new dataEdit_Published;
		  
		  $spaceEdit->actSourceTab = 'z_1_ee76ce40e';
		  
	 
		  
		  $output = array();
		  foreach($badUUIDs as $uuid){
				
				$pubObj->deleteFromPublishedDocsByUUID($uuid);
				$pubObj->deleteFromPublishedDocsByChildUUID($uuid);
				
				
				$sql = "UPDATE space SET source_id = 'z_1_ee76ce40e' WHERE uuid = '".$uuid."' LIMIT 1;";
				$db->query($sql, 2);
				
				$sql = "SELECT source_id, space_label, full_context, class_uuid, project_id
				FROM space
				WHERE uuid = '$uuid' LIMIT 1;
				";
				
				$result = $db->fetchAll($sql);
				$result = false;
				
				
				if($result){
					 $itemLabel = $result[0]["space_label"];
					 $itemContext = $result[0]["full_context"];
					 $sourceID = $result[0]["source_id"];
					 $classUUID = $result[0]["class_uuid"];
					 $projectUUID = $result[0]["project_id"];
					 
					 $sourceIDs = $spaceEdit->getSourceIDs($itemLabel, $itemContext, $sourceID, $classUUID);
					 $sourceData = $spaceEdit->itemDuplicateNoObs($uuid, $sourceIDs);
					 
					 //echo print_r($sourceIDs);
					 //echo print_r($sourceData);
					 //die;
					 foreach($sourceData as $subjectUUID => $idArray){
						  
						  //delete the old observations
						  $where = "subject_uuid = '$subjectUUID' ";
						  $db->delete("observe", $where);
						  
						  $id = $idArray["id"];
						  
						  $sql = "SELECT * FROM z_1_ee76ce40e AS otab WHERE id = $id LIMIT 1;";
						  //echo " ".$sql." ";
						  $originalData = $db->fetchAll($sql);
						  foreach($originalData as $oRow){
								foreach($oRow as $fieldKey => $value){
									 if($value){
										  if(array_key_exists($fieldKey, $tabFields)){
												$variableUUID = $tabFields[$fieldKey];
												$valueUUID = $propEdit->get_make_ValID($value, $projectUUID);
												$propUUID = $propEdit->get_make_PropID($variableUUID, $valueUUID, $projectUUID);
												
												$hashObs = md5($projectUUID . "_" . $subjectUUID . "_" . 1 . "_" . $propUUID);
												$data = array("project_id" => $projectUUID,
																  "source_id" => $sourceID,
																  "hash_obs" => $hashObs,
																  "subject_type" => "Locations or Objects",
																  "subject_uuid" => $subjectUUID,
																  "obs_num" => 1,
																  "property_uuid" => $propUUID
																  );
												try{
													 $db->insert('observe', $data );
												}
												catch (Exception $e) {
												
												}
												
												$output[$uuid][$id][$fieldKey] = array("link" => "http://penelope.oc/preview/space?UUID=".$subjectUUID,
																									"subjectUUID" => $subjectUUID,
																									"propUUID" => $propUUID,
																									"variableUUID" => $variableUUID,
																									"valueUUID" => $valueUUID,
																									 "value" => $value);
										  }
									 }
								}
						  }
						  
						  $firstLoop = false;
					 }//array of sourceData
					 
				}
				
		  }//end loop
		  
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_Json::encode($output);
		  
	 }
	 
	 
	 
	   //load up old space data from XML documents
	 function repubListAction(){
		  
		  //this line is necessary for ajax calls:
        $this->_helper->viewRenderer->setNoRender();        
        
		  $badListURL = "http://opencontext.org/sets/Turkey/Pinarbasi.json?projID=TESTPRJ0000000004&cat=Animal+Bone&recs=100";
		  
		  $basePublishURL = "http://penelope.oc/publish/publishdoc";
		  $params = array(
				"projectUUID" => "TESTPRJ0000000004",
				"pubURI" => "http://opencontext.org/publish/item-publish",
				"update" => "true",
				"itemType" => "space");
		  
		  $badJSON = file_get_contents($badListURL);
		  $badObj = Zend_Json::decode($badJSON);
		  $output = array();
		  foreach($badObj["results"] as $result){
				sleep(.25);
				$uri = $result["uri"];
				$uriEx = explode("/",  $uri);
				$uuid = $uriEx[(count($uriEx)-1)];
				
				$params["itemUUID"] = $uuid;
				$actURL =  $basePublishURL . "?" . http_build_query($params);
				
				$resp = file_get_contents($actURL);
				$respObj = Zend_Json::decode($resp);
				$output[] = $respObj;
		  }
		  
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_Json::encode($output);
	 }

	 
	 //publish space items associated with images
	 function spaceMediaPubAction(){
		  
		  //this line is necessary for ajax calls:
        $this->_helper->viewRenderer->setNoRender();        
        
		  $badListURL = "http://opencontext.org/lightbox/Italy.json?recs=100&page=257";
		  
		  $basePublishURL = "http://penelope.oc/publish/publishdoc";
		  $params = array(
				"projectUUID" => "DF043419-F23B-41DA-7E4D-EE52AF22F92F",
				"pubURI" => "http://opencontext.org/publish/item-publish",
				"update" => "true",
				"itemType" => "space");
		  
		  $paramsB = array(
				"projectUUID" => "DF043419-F23B-41DA-7E4D-EE52AF22F92F",
				"pubURI" => "http://opencontext/publish/item-publish",
				"update" => "true",
				"itemType" => "space");
		  
		  $badJSON = file_get_contents($badListURL);
		  $badObj = Zend_Json::decode($badJSON);
		  
		  $output = array();
		  $output["queries"] = "";
		  foreach($badObj["results"] as $jresult){
				sleep(.25);
				if($jresult["project"]=="Murlo"){
					 $uri = $jresult["uri"];
					 $uriEx = explode("/",  $uri);
					 $resUUID = $uriEx[(count($uriEx)-1)];
					 
					 $db = Zend_Registry::get('db');
					 $sql = "SELECT origin_uuid as uuid FROM links WHERE targ_uuid = '$resUUID' AND origin_type LIKE '%location%' ";
					 $result = $db->fetchAll($sql);
					 foreach($result as $row){
						  
						  $params["itemUUID"] = $row["uuid"];
						  $actURL =  $basePublishURL . "?" . http_build_query($params);
						  
						  $resp = file_get_contents($actURL);
						  $respObj = Zend_Json::decode($resp);
						  $output[] = $respObj;
						  
						  $paramsB["itemUUID"] = $row["uuid"];
						  $actURL =  $basePublishURL . "?" . http_build_query($paramsB);
						  
						  $resp = file_get_contents($actURL);
						  $respObj = Zend_Json::decode($resp);
						  $output[] = $respObj;
						  sleep(.25);
					 }
					 
					 $output["queries"] .= " UPDATE noid_bindings SET solr_indexed = 0 WHERE itemUUID = '$resUUID' LIMIT 1; ";
					 //echo "UPDATE noid_bindings SET solr_indexed = 0 WHERE itemUUID = '$resUUID' LIMIT 1;";
					 
				}
		  }
		 
		  header('Content-Type: application/json; charset=utf8');
		 echo Zend_Json::encode($output);
	 }
	 
	 
	 
	  //publish space items associated with images
	 function personPubAction(){
		  
		  //this line is necessary for ajax calls:
        $this->_helper->viewRenderer->setNoRender();        
		  
		  $basePublishURL = "http://penelope.oc/publish/publishdoc";
		  $allParams = array();
		  $allParams[0] = array(
				"projectUUID" => "DF043419-F23B-41DA-7E4D-EE52AF22F92F",
				"pubURI" => "http://opencontext.org/publish/item-publish",
				"update" => "true",
				"itemType" => "person");
		  
		  $allParams[1]  = array(
				"projectUUID" => "DF043419-F23B-41DA-7E4D-EE52AF22F92F",
				"pubURI" => "http://opencontext/publish/item-publish",
				"update" => "true",
				"itemType" => "person");
		  
		
		  $output = array();
		  $output["queries"] = "";
		 
		  $db = Zend_Registry::get('db');
		  $sql = "SELECT uuid, project_id FROM persons WHERE
		  project_id = 'CDD40C27-62ED-4966-AF3D-E781DD0D4846'
		  OR
		  project_id = '05F5B702-2967-49B1-FEAA-9B2AA0184513'
		  OR
		  project_id = '74749949-4FD4-4C3E-C830-5AA75703E08E'
		  OR
		  project_id = 'BC90D462-6639-4087-8527-6BB9E528E07D'
		  ";
		  
		  $sql = "SELECT users.uuid, links.project_id FROM users
		  JOIN links ON users.uuid = links.targ_uuid
		  WHERE
		  users.uuid = '62EE9ABC-AD45-4F92-5A7B-B16A092CB5C2'
		  ";
		  
		  $result = $db->fetchAll($sql);
		  foreach($result as $row){
				
				foreach($allParams as $params){
					 
					 $params["itemUUID"] = $row["uuid"];
					 $params["projectUUID"] = $row["project_id"];
					 $actURL =  $basePublishURL . "?" . http_build_query($params);
					 
					 $resp = file_get_contents($actURL);
					 $respObj = Zend_Json::decode($resp);
					 $output[] = $respObj;
					
					 sleep(.25);
					 
					 $data = array("hash_key" => md5($row["uuid"]."_".$params["pubURI"]),
										"pubdest" => $params["pubURI"],
										"project_id" => $row["project_id"],
										"item_uuid" => $row["uuid"],
										"item_type" => $params["itemType"],
										"status" => "ok"
										);
					 
					 try{
						  $db->insert("published_docs", $data);
						  }
					 catch (Exception $e)  {
								//echo (string)$e;
								//die;
					 }

				}
				
		  }
		  header('Content-Type: application/json; charset=utf8');
		 echo Zend_Json::encode($output);
	 }
	 
	 

	  //load up old space data from XML documents
	 function linkBoneOntologyAction(){
		  
		  //this line is necessary for ajax calls:
        $this->_helper->viewRenderer->setNoRender();        
        
        //get selected root item then add it and all children to database
        $tableID = "z_24_9c70c5804";
        
		  Zend_Loader::loadClass('LinkedData_BoneMeasurement');
		  
		  $linkingObj = new LinkedData_BoneMeasurement;
		  $linkingObj->doShortVariableLabels = false;
		  $varList = $linkingObj->getVarTableList($tableID);
		  $linkingObj->fixCapitalsVars($varList);
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
        $projUUID = "3DE4CD9C-259E-4C14-9B03-8B10454BA66E";
        
		  Zend_Loader::loadClass('dataEdit_SpaceIdentity');
		  
		  $editObj = new dataEdit_SpaceIdentity;
		  $editObj->projUUID = $projUUID;
		  $editObj->sourceLimit = "z_1_ee76ce40e"; 
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