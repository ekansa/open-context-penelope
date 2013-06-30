<?php

// increase the memory limit
ini_set("memory_limit", "2048M");
// set maximum execution time to no limit
ini_set("max_execution_time", "0");

class ImageProcessController extends Zend_Controller_Action
{
    
   
	function init()
    {  
        $this->view->baseUrl = $this->_request->getBaseUrl();
        require_once 'App/Util/GenericFunctions.php';
        Zend_Loader::loadClass('Images_ThumbPreviewSize');
    }
    
    //convert all the image files in a directory to preview size images
    function dirToPreviewAction(){
        $this->_helper->viewRenderer->setNoRender();
        
		  //$directory = "C:\\about_opencontext\\kenan\\thumbs\\";
		  $directory = "E:\\PC2013\\trenchbooks\\preview\\";
		  $imageObj = new Images_ThumbPreviewSize;
		  $fileArray = $imageObj->makeDirectoryPreviews($directory);
		  
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_Json::encode($fileArray);
    }
	 
    //convert all the image files in a directory to thumbnail size images
    function dirToThumbsAction(){
        $this->_helper->viewRenderer->setNoRender();
		  
		  //$directory = "C:\\about_opencontext\\kenan\\thumbs\\";
		  
        $directory = "E:\\PC2013\\trenchbooks\\thumbs\\";
		  $imageObj = new Images_ThumbPreviewSize;
		  $fileArray = $imageObj->makeDirectoryThumbs($directory);
		  
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_Json::encode($fileArray);
    }


}