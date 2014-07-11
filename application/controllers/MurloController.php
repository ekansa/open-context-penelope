<?php

/** Zend_Controller_Action */
//require_once 'Zend/Controller/Action.php';
//require_once 'OpenContext/Controller/Action/Helper/SolrAccess.php';

//error_reporting(E_ALL ^ E_NOTICE);
// increase the memory limit
ini_set("memory_limit", "1024M");
// set maximum execution time to no limit
ini_set("max_execution_time", "0");

class MurloController extends Zend_Controller_Action {
    
    function init()
    {  
        require_once 'App/Util/GenericFunctions.php';
    }
	
	function makeKmlAction(){
		$this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Murlo');
		  Zend_Loader::loadClass('dataEdit_Property');
		  require ('/application/models/GeoSpace/gisconverter.php'); // first, include gisconverter.php library, but not as a Zend include
		  
		  $url = "http://127.0.0.1:8000/subjects/B1AAE78E-ACD4-4B1F-CA46-EF64F7310195.json";
		  $murloObj = new ProjEdits_Murlo;
		  echo $murloObj->convertGeoJSON($url);
	}
	
	function pcLookAgainAction(){
		$this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Murj->lo');
		  Zend_Loader::loadClass('dataEdit_Property');
		  
		  $murloObj = new ProjEdits_Murlo;
		  $output = array();
		  //$output['missing'] = $murloObj->TBmissingGet();
		  $output['content'] = $murloObj->TBprocessMissing(false);
		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);	
	}
	
	
	function pcImageScrapeAction(){
		$this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Murlo');
		  Zend_Loader::loadClass('Images_ThumbPreviewSize');
		  Zend_Loader::loadClass('dataEdit_Link');
		Zend_Loader::loadClass('dataEdit_Published');
		Zend_Loader::loadClass('dataEdit_SpaceTime');
		Zend_Loader::loadClass('dataEdit_SpaceContain');
		Zend_Loader::loadClass('dataEdit_Subject');
		Zend_Loader::loadClass('dataEdit_Property');
		Zend_Loader::loadClass('dataEdit_LinkedData');
		  
		  $murloObj = new ProjEdits_Murlo;
		  $output = array();
		 //$output['scrape'] = $murloObj->imageScrape();
		 //$output['copies'] = $murloObj->imagesCopy();
		  //$output['fname'] = $murloObj->imagesFname();
		  //$output['exists'] = $murloObj->imagesFexists();
		  //$output['links'] = $murloObj->imagesSpaceLink();
		  //$output['media'] = $murloObj->imagesMintNew();
		  $output['fimage'] = $murloObj->imageFirstImage();
		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);	
	}
	
	function pcMagScrapeAction(){
		$this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Murlo');
		  Zend_Loader::loadClass('Images_ThumbPreviewSize');
		  
		  $murloObj = new ProjEdits_Murlo;
			$output = $murloObj->magScrape();
		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);	
	}
	
	function pcGetMissingImagesAction(){
		$this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Murlo');
		  Zend_Loader::loadClass('Images_ThumbPreviewSize');
		  
		  $murloObj = new ProjEdits_Murlo;
		  $imgObj = new Images_ThumbPreviewSize;
		  $s = "http://gigante/catalog/trenchbooks/trenchbookimages/uncropped/SCummerI_90,91.JPG";
		  $d = "pc-scrape/test.jpg";
		  $imgObj->fullfileSaveImage($s, $d);
		  $output = array();
		  //$output['missing'] = $murloObj->TBmissingGet();
		  //$output['content'] = $murloObj->TBprocessHideMissing();
		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);	
	}
	
	
	function pcMissingManageAction(){
		$this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Murlo');
		  Zend_Loader::loadClass('dataEdit_Property');
		  
		  $murloObj = new ProjEdits_Murlo;
		  $output = array();
		  //$output['missing'] = $murloObj->TBmissingGet();
		  $output['content'] = $murloObj->TBprocessMissing(true);
		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
		
		
	}
	
	
	function pcHtmlContentAction(){
		$this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Murlo');
		  Zend_Loader::loadClass('dataEdit_Property');
		  
		  $murloObj = new ProjEdits_Murlo;
		  $output = array();
		  //$output['missing'] = $murloObj->TBmissingGet();
		  $output['content'] = $murloObj->TBprocessCompressed();
		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
		
		
	}
	
	function pcTbMissingLinksAction(){
	   //adds missing location link relations for diary entries
	   //at PC
	   $this->_helper->viewRenderer->setNoRender();
	   Zend_Loader::loadClass('ProjEdits_Murlo');
	   Zend_Loader::loadClass('dataEdit_Link');
	   Zend_Loader::loadClass('dataEdit_Published');
	   Zend_Loader::loadClass('dataEdit_SpaceTime');
	   Zend_Loader::loadClass('dataEdit_SpaceContain');
	   Zend_Loader::loadClass('dataEdit_Subject');
	   Zend_Loader::loadClass('dataEdit_Property');
	   Zend_Loader::loadClass('dataEdit_LinkedData');
	   $pObj = new ProjEdits_Murlo;
	   $output = array();
	   $output["data"] = $pObj->TBMissingLinkMatch();
	   header('Content-Type: application/json; charset=utf8');
	   echo Zend_Json::encode($output);
    }
    
    function pcTbContentsAction(){
	   //adds media linking relations for diary entries
	   //at PC
	   $this->_helper->viewRenderer->setNoRender();
	   Zend_Loader::loadClass('ProjEdits_Murlo');
	   Zend_Loader::loadClass('dataEdit_Link');
	   Zend_Loader::loadClass('dataEdit_Published');
	   Zend_Loader::loadClass('dataEdit_SpaceTime');
	   Zend_Loader::loadClass('dataEdit_SpaceContain');
	   Zend_Loader::loadClass('dataEdit_Subject');
	   Zend_Loader::loadClass('dataEdit_Property');
	   Zend_Loader::loadClass('dataEdit_LinkedData');
	   $pObj = new ProjEdits_Murlo;
	   $output = array();
	   $output["data"] = $pObj->TBFindsMatch();
	   //$output["data"] = $pObj->TBContentsExtract();
	   header('Content-Type: application/json; charset=utf8');
	   echo Zend_Json::encode($output);
	   
    }
    
    function pcMediaPagesAction(){
	   //adds media linking relations for diary entries
	   //at PC
	   $this->_helper->viewRenderer->setNoRender();
	   Zend_Loader::loadClass('ProjEdits_Murlo');
	   Zend_Loader::loadClass('dataEdit_Link');
	   Zend_Loader::loadClass('dataEdit_Published');
	   Zend_Loader::loadClass('dataEdit_SpaceTime');
	   Zend_Loader::loadClass('dataEdit_SpaceContain');
	   Zend_Loader::loadClass('dataEdit_Subject');
	   Zend_Loader::loadClass('dataEdit_Property');
	   Zend_Loader::loadClass('dataEdit_LinkedData');
	   $pObj = new ProjEdits_Murlo;
	   $output = array();
	   $output["data"] = $pObj->TBscanPageRangeExtract();
	   header('Content-Type: application/json; charset=utf8');
	   echo Zend_Json::encode($output);
	   
    }
    
    function pcDiaryMediaLinksAction(){
	   //adds media linking relations for diary entries
	   //at PC
	   $this->_helper->viewRenderer->setNoRender();
	   Zend_Loader::loadClass('ProjEdits_Murlo');
	   Zend_Loader::loadClass('dataEdit_Link');
	   Zend_Loader::loadClass('dataEdit_Published');
	   Zend_Loader::loadClass('dataEdit_SpaceTime');
	   Zend_Loader::loadClass('dataEdit_SpaceContain');
	   Zend_Loader::loadClass('dataEdit_Subject');
	   Zend_Loader::loadClass('dataEdit_Property');
	   Zend_Loader::loadClass('dataEdit_LinkedData');
	   $pObj = new ProjEdits_Murlo;
	   $output = array();
	   $output["data"] = $pObj->TBmediaLinks();
	   header('Content-Type: application/json; charset=utf8');
	   echo Zend_Json::encode($output);
	   
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
	 

	 

}//end class