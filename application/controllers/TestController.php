<?php

/** Zend_Controller_Action */
//require_once 'Zend/Controller/Action.php';
//require_once 'OpenContext/Controller/Action/Helper/SolrAccess.php';

//error_reporting(E_ALL ^ E_NOTICE);

class testController extends Zend_Controller_Action {
    
    
    
    //make sure all connections are UTF-8 OK
    private function setUTFconnection($db){
	$sql = "SET collation_connection = utf8_unicode_ci;";
	$db->query($sql, 2);
	$sql = "SET NAMES utf8;";
	$db->query($sql, 2);
    }
    
    
    public function testAction(){
	$this->_helper->viewRenderer->setNoRender();
	Zend_Loader::loadClass('SpatialContainment_NewSpaceContain');
	
	$contain = new SpatialContainment_NewSpaceContain;
	$contain->projectUUID = "D6B25EC9-2884-4E3C-00E8-0C5A6472FA63"; //idof active project
	$contain->dataTableName = "z_1_a2502fe20"; //name of active data table
	$contain->space_contain_setup();
	$contain->process_all_spatial(0);
	
	echo print_r($contain);
	
    }//end function
    
    
   
    

}