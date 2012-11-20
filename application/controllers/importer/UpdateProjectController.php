<?php

class Importer_UpdateProjectController extends Zend_Controller_Action
{
    function init()
    {
        $this->view->baseUrl = $this->_request->getBaseUrl();
        Zend_Loader::loadClass('Table_Project');
        Zend_Loader::loadClass('Table_FileSummary');
    }
    
    function displayDataAction()
    {
        //this line is necessary for ajax calls:
        $this->_helper->viewRenderer->setNoRender();
        $dataTableName = $_REQUEST['dataTableName'];
        
        //Zend_Loader::loadClass('Project');
        //$projectID = $_REQUEST['projectID'];
        //$selectedProject = Project::getProjectById($projectID);
        //echo Zend_Json::encode($selectedProject->getDataGridHelper());

        Zend_Loader::loadClass('Layout_DataGridHelper');
        $dgHelper = new Layout_DataGridHelper();
        $dgHelper->setLayoutFromTableFieldSummary($dataTableName);
        $dgHelper->setDataFromDataTable($dataTableName, null); //null for where clause
        echo Zend_Json::encode($dgHelper);     
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
    
    function getDataTableListAction()
    {
        $this->_helper->viewRenderer->setNoRender();        
        $projectID = $_REQUEST['projectID'];
        
        Zend_Loader::loadClass('Table_Project');
        $project = new Table_Project();
        $projectRow = $project->fetchRow('pk_project = ' . $projectID);
        
        //get all of the tables associated with this project that have not already been processed:
        $fileSummary    = new Table_FileSummary();
        $select = $fileSummary->select()->where('imp_done_timestamp is null');
        $dataTables = $projectRow->findDependentRowset('Table_FileSummary', null, $select);
        
        $dataStore = array('label' => 'source_id', 'identifier' => 'source_id',
            'items' => $dataTables->toArray() //use toArray() since a Zend_Db_Table_Row object is different than a typical array                      
        );
        echo Zend_Json::encode($dataStore);
 
    }
    
}