<?php

require_once 'App/Controller/PenelopeController.php';   //handles the functionality common across controllers

class LicenseController extends App_Controller_PenelopeController
{
    function indexAction()
    {
        //call to process query parameters:
        parent::indexAction();
        $this->view->title = "Data Importer";
    }
    
    function getLicenseByIdAction()
    {
        //this line is necessary for ajax calls:
        $this->_helper->viewRenderer->setNoRender();        
        
        Zend_Loader::loadClass('Table_License');
        Zend_Loader::loadClass('License');
        
        $licenseID = $_REQUEST['licenseID'];        
        $licenseTable = new Table_License();
        $licenseRow = $licenseTable->fetchRow("PK_LICENSE = " . $licenseID);
        echo Zend_Json::encode(new License($licenseRow));
    }
    
    function getLicenseAction()
    {
        //this line is necessary for ajax calls:
        $this->_helper->viewRenderer->setNoRender();
        
        Zend_Loader::loadClass('Table_License');
        Zend_Loader::loadClass('License');
        
        $isCommercial = $_REQUEST['isCommercial'];
        $reproductionLevel = $_REQUEST['reproductionLevel'];
        
        //Zend_Loader::loadClass('Project');
        //echo "Returning from server: " . $isCommercial . " - " . $reproductionLevel;
        
        $licensesTable = new Table_License();
        $row = $licensesTable->fetchRow("CAN_BE_COMMERCIAL = " . $isCommercial . " and MODIFIED_REQUIREMENT = '" . $reproductionLevel . "'");
        //$row = $licensesTable->fetchRow('CAN_BE_COMMERCIAL = ?', $isCommercial, 'MODIFIED_REQUIREMENT = ?', $reproductionLevel);
        //echo $row->CAN_BE_COMMERCIAL;
        //Zend_Debug::dump($row);
        //Zend_Debug::dump(new License($row));
        echo Zend_Json::encode(new License($row));
    }
    
    function setLicenseAction()
    {
        //this line is necessary for ajax calls:
        $this->_helper->viewRenderer->setNoRender();
        
        Zend_Loader::loadClass('Table_FileSummary');        
        Zend_Loader::loadClass('Table_License');
        Zend_Loader::loadClass('License');
        
        $licenseID = $_REQUEST['licenseID'];
        $dataTableName = $_REQUEST['dataTableName'];
        
        $fileSummaryTable = new Table_FileSummary();
        //$fileSummaryRow = $fileSummaryTable->fetchRow("fk_project = '" . $projectID . "'");
        
        $data = array('fk_license'  => $licenseID);
        $where = $fileSummaryTable->getAdapter()->quoteInto('source_id = ?', $dataTableName);
        $fileSummaryTable->update($data, $where);
        
        echo "license successfully updated!";
    }
    
}