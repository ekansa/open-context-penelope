<?php

class Importer_LicenseController extends Zend_Controller_Action
{
    function init()
    {
        $this->view->baseUrl = $this->_request->getBaseUrl();
        //Zend_Loader::loadClass('User');
        //Zend_Loader::loadClass('Form_Licensing');
        Zend_Loader::loadClass('Table_License');
        Zend_Loader::loadClass('License');
        //Zend_Loader::loadClass('Zend_Debug');
        //Zend_Loader::loadClass('App_Util_Excel_TableImporter');
    }

    /*function indexAction()
    {
        $this->view->pageTitle = "Creative Commons Licensing";
        $this->view->bodyCopy = "<p>Select a license...</p>";

        $form = new Form_Licensing();
        $this->view->form = $form;
        
        
        $licensesTable = new Table_License();
        $this->view->availLicenses = $licensesTable->fetchAll();
        //Zend_Debug::dump($this->view->availLicenses);
    }*/
    
    function getLicenseByIdAction()
    {
        //this line is necessary for ajax calls:
        $this->_helper->viewRenderer->setNoRender();
        
        $licenseID = $_REQUEST['licenseID'];        
        $licenseTable = new Table_License();
        $licenseRow = $licenseTable->fetchRow("PK_LICENSE = " . $licenseID);
        echo Zend_Json::encode(new License($licenseRow));
    }
    
    function getLicenseAction()
    {
        //this line is necessary for ajax calls:
        $this->_helper->viewRenderer->setNoRender();
        
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
        $licenseID = $_REQUEST['licenseID'];
        $dataTableName = $_REQUEST['dataTableName'];
        
        Zend_Loader::loadClass('Table_FileSummary');
        $fileSummaryTable = new Table_FileSummary();
        //$fileSummaryRow = $fileSummaryTable->fetchRow("fk_project = '" . $projectID . "'");
        
        $data = array('fk_license'  => $licenseID);
        $where = $fileSummaryTable->getAdapter()->quoteInto('source_id = ?', $dataTableName);
        $fileSummaryTable->update($data, $where);
        
        echo "license successfully updated!";
    }
    
    
}