<?php
 class WizardController extends Zend_Controller_Action
 {
   function init()
   {    
      $this->view->baseUrl = $this->_request->getBaseUrl();      
   }

   //Default Action, if none specified: 
   function indexAction()
   {
      $this->view->title = "Wizard Start Page";  
   }
    
   function testAction()
   {
      $server = new Zend_Json_Server();
      
      //specfy SMD metadata
      //$server->setTransport('POST');
      //       ->setTarget('/unit-test/json-rpc')
      //       ->setEnvelope[Zend_Json_server_Smd::ENV)JSONRPC_2];
      //$server->setClass['My_TestRunner'];
      
   }
    
 }//end class