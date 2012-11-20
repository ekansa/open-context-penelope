<?php

class UtilsController extends Zend_Controller_Action
{
    
    function getNavigationAction()
    {        
        $this->_helper->viewRenderer->setNoRender();
        $projectUUID        = $_REQUEST['projectUUID'];
        $dataTableName      = $_REQUEST['dataTableName'];        
        $controllerName     = $_REQUEST['controllerName'];
        
        //Zend_Debug::dump($dataTableName);
        //return;
        $project = null;
        if(isset($projectUUID) && intval($projectUUID) != -1)
        {
            Zend_Loader::loadClass('Table_Project');     
            Zend_Loader::loadClass('Project');
            $project = Project::getProjectByUUID($projectUUID);
            if(isset($dataTableName))
                $project->setFileSummaryInformation($dataTableName); 
        }
        //init navigation object:
        Zend_Loader::loadClass('Layout_Navigation');
        $navigation = new Layout_Navigation($controllerName, $project);
        echo Zend_Json::encode($navigation);   
    }
    
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