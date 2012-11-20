<?php

require_once 'App/Controller/PenelopeController.php';   //handles the functionality common across controllers

class ProjectController extends App_Controller_PenelopeController
{
    function indexAction()
    {
        //call to process query parameters:
        parent::indexAction();
        //Zend_Debug::dump($this->_request);
        $this->view->title = "Data Importer";
        
        //get projects associated with the user:
        $this->user->getProjects();
                
        //add projects datastore:
        $projectsDataStore = new Zend_Dojo_Data();
        $projectsDataStore->setIdentifier("id");
        $projectsDataStore->setItems($this->user->projects);
        $this->view->projectsDataStore = $projectsDataStore;
    }
    
    function addProjectAction()
    {
        //this line is necessary for ajax calls:
        $this->_helper->viewRenderer->setNoRender();
        
        $projectName = $_REQUEST['projectName'];
        
        Zend_Loader::loadClass('Project');
        $newProject = Project::addProject($projectName);
        echo Zend_Json::encode($newProject);
        //Zend_Debug::dump($newProject);
    }

    function removeProjectAction()
    {
        //this line is necessary for ajax calls:
        $this->_helper->viewRenderer->setNoRender();
        
        $projectID = $_REQUEST['projectID'];
        
        Zend_Loader::loadClass('Project');
        $projectID = Project::removeProject($projectID);
        echo $projectID;
        //Zend_Debug::dump($newProject);
    }
    
    public static function isComplete($project, $dataTableName, $navigationObject)
    {
        //$navigationObject->currentPageID
        
        //1) determine which pages should be enabled:
        if(isset($project) && isset($dataTableName))
        {
            $navigationObject->enabledPageIDs =
                array(
                      Layout_Navigation::METADATA_PAGE_ID, Layout_Navigation::DATATABLE_PAGE_ID, Layout_Navigation::LICENSE_PAGE_ID,
                      Layout_Navigation::USER_PAGE_ID , Layout_Navigation::CLASSIFY_PAGE_ID, Layout_Navigation::ANNOTATE_PAGE_ID, Layout_Navigation::RELATIONSHIPS_PAGE_ID,
                      Layout_Navigation::PROPERTY_PAGE_ID);
        }
        else
        {
            $navigationObject->enabledPageIDs = array(Layout_Navigation::METADATA_PAGE_ID);
        }
        return $navigationObject;
    }
}