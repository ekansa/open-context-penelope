<?php

class Importer_UtilsController extends Zend_Controller_Action
{        
    function getUserAction()
    {
        //this line is necessary for ajax calls:
        $this->_helper->viewRenderer->setNoRender();

        $auth = Zend_Auth::getInstance();
        $user = new User($auth->getIdentity());       
        echo Zend_Json::encode($user);
    }
    
    function getUserProjectsAction()
    {
        //this line is necessary for ajax calls:
        $this->_helper->viewRenderer->setNoRender();
        
        $user = new User();
        $dojoDataStore = new Zend_Dojo_Data();
        $dojoDataStore->setIdentifier("id");
        $dojoDataStore->setItems($user->projects);
        echo Zend_Json::encode($dojoDataStore);
        //Zend_Debug::dump($dojoDataStore);
        //echo $dojoDataStore;
        //echo "hello world!";
    }
}