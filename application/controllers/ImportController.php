<?php

class ImportController extends Zend_Controller_Action
{
    function init()
    {
        $this->view->baseUrl = $this->_request->getBaseUrl();
        Zend_Loader::loadClass('User'); //defined in User.php
        Zend_Loader::loadClass('Form_Login'); //defined in User.php
        Zend_Loader::loadClass('Table_Project');
        Zend_Loader::loadClass('Zend_Debug');
        Zend_Loader::loadClass('Zend_Dojo_Data');
        Zend_Loader::loadClass('Form_Upload');
    }
    
    //redirect to login page if not logged in:
    function preDispatch()
    {
        $auth = Zend_Auth::getInstance();
        if (!$auth->hasIdentity())
            $this->_redirect('auth/login');
    }

    function indexAction()
    {
        $this->view->title = "Data Importer";

        //add "User" object to the view:
        $user = User::getCurrentUser();
        $user->getProjects();
        $this->view->user = $user;
        
        //add projects datastore:
        $projectsDataStore = new Zend_Dojo_Data();
        $projectsDataStore->setIdentifier("id");
        $projectsDataStore->setItems($user->projects);
        $this->view->projectsDataStore = $projectsDataStore;
    }
    

}