<?php

// increase the memory limit
ini_set("memory_limit", "2048M");
// set maximum execution time to no limit
ini_set("max_execution_time", "0");

class DescribeController extends Zend_Controller_Action
{
    
    //public $host = "http://penelope.opencontext.org";
    public $host = "http://penelope2.oc";
    public $counter = 0;
    
	
	function init()
    {  
        $this->view->baseUrl = $this->_request->getBaseUrl();
        require_once 'App/Util/GenericFunctions.php';
        Zend_Loader::loadClass('Zend_Debug');
        Zend_Loader::loadClass('ContextItem');
        Zend_Loader::loadClass('Table_Property');
        Zend_Loader::loadClass('Table_Value');
        Zend_Loader::loadClass('Table_Variable');
        Zend_Loader::loadClass('Table_Observe');
        Zend_Loader::loadClass('Table_Diary');
        Zend_Loader::loadClass('Table_Resource');
        Zend_Loader::loadClass('Table_LinkRelationship');
        Zend_Loader::loadClass('Table_User');
		  Zend_Loader::loadClass('dataEdit_VarPropNotes');
    }
    
     //make sure all connections are UTF-8 OK
    private function setUTFconnection($db){
		  $sql = "SET collation_connection = utf8_unicode_ci;";
		  $db->query($sql, 2);
		  $sql = "SET NAMES utf8;";
		  $db->query($sql, 2);
    }
    
    
    function varPropsAction(){
        //$this->_helper->viewRenderer->setNoRender();
        $db = Zend_Registry::get('db');
        $this->setUTFconnection($db);
		  
		  $VarPropObj = new dataEdit_VarPropNotes;
		  $varUUID = $_REQUEST['varUUID'];
		  if(isset($_REQUEST['sort'])){
				$VarPropObj->alphaSort = $_REQUEST['sort'];
		  }
		  if(isset($_REQUEST['showPropCounts'])){
				$VarPropObj->showPropCounts = $_REQUEST['showPropCounts'];
		  }
		  
		  $this->view->varUUID = $varUUID;
		  $VarPropObj->getProperties($varUUID);
		  $this->view->VarPropObj = $VarPropObj;
    }
    
    
    function propNoteAction(){
        $this->_helper->viewRenderer->setNoRender();
        $db = Zend_Registry::get('db');
		  $this->setUTFconnection($db);
		  $propertyUUID = $_REQUEST['propertyUUID'];
		  $note = $_REQUEST['note'];
		  $projectUUID = $_REQUEST['projectUUID'];
		  $varUUID = $_REQUEST['varUUID'];
		  
		  $VarPropObj = new dataEdit_VarPropNotes;
		  $VarPropObj->updatePropNote($propertyUUID, $note);
	 
		  $headerLink = "var-props?varUUID=".$varUUID."&showPropCounts=".$_REQUEST['showPropCounts'];
		  header("Location: $headerLink");
    }
    
     
    
}