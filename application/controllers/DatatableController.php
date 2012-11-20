<?php

require_once 'App/Controller/PenelopeController.php';   //handles the functionality common across controllers

class DatatableController extends App_Controller_PenelopeController
{
    function indexAction()
    {
        //call to process query parameters:
        parent::indexAction();
        $this->view->title = "Data Importer";
    }
    
    function getDataTableListAction()
    {
        $this->_helper->viewRenderer->setNoRender();        
        $projectID = $_REQUEST['projectID'];
        
        Zend_Loader::loadClass('Table_Project');
        Zend_Loader::loadClass('Table_FileSummary');
        
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
    
    function displayDataAction()
    {
        //this line is necessary for ajax calls:
        $this->_helper->viewRenderer->setNoRender();
        $dataTableName = $_REQUEST['dataTableName'];
        
        Zend_Loader::loadClass('Layout_DataGridHelper');
        $dgHelper = new Layout_DataGridHelper();
        $dgHelper->setLayoutFromTableFieldSummary($dataTableName);
        $dgHelper->setDataFromDataTable($dataTableName, null); //null for where clause
        echo Zend_Json::encode($dgHelper);     
    }
    
}