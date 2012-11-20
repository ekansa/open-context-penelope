<?php
require_once 'Item.php';
require_once 'App/Xml/XmlGenerator.php';
class Project extends Item implements App_Xml_XmlGenerator
{
    public $id;
    public $uuID;
    public $name;
    public $projectName;
    public $shortDesc;
    public $longDesc;
    public $timestamp;
    public $parentContextName;
    public $parentContextID;
    public $parentContextClass;
    public $noDataMessage;
    
    public $userID;
    public $hasDataRecords;
    public $projectStatusMessage;
    
    public $dataTableName;
    public $dataTableDesc;
    public $fileName;
    public $processingMessage;
    public $numRows;
    public $numCols;
    public $numTablesProcessed = 0;
    public $numTablesUnprocessed = 0;
    public $numTables = 0;
    //public $selectedFileID;
    public $licenseID;
    public $license;
    public $metadata;
    
    //variables relating to the XML transformation:
    public $namespaceArch = App_Constants::ARCHAEOML_NS_URI_PROJECT;
    public $namespaceOC = App_Constants::OC_NS_URI_PROJECT;
    public $spaceCount;
    public $diaryCount;
    public $mediaCount;
    
    function Project($_data) //where $_data is a "ResultRowObject"
    {
        //echo 'outputting projectID...<br />';
        //Zend_Debug::dump($_data->pk_project);
        //return;
        $this->id                   = $_data->pk_project;
        $this->uuID                 = $_data->project_id;
        $this->name                 = $_data->project_name;
        $this->projectName          = $_data->project_name;
        $this->shortDesc            = $_data->short_des;
        $this->longDesc             = $_data->abstract;
        $this->parentContextName    = $_data->parcontext_name;
        $this->parentContextID      = $_data->parcontext_id;
        $this->parentContextClass   = $_data->parcontext_class;
        $this->noDataMessage        = $_data->noprop_mes;
        $this->timestamp            = $_data->created;
        $this->userID               = $_data->person_id;
        $this->setFileSummaryInformation();
        
        //for the "Item" abstract class:
        $this->project = $this;
        //Zend_Debug::dump($this);
    }
    
    public function getProjectName()
    {
        return $this->projectName;
    }
    
    //required to be implemented by the "Item" abstract class:
    public function getItemType() { return App_Constants::PROJECT; }
    public function getUUID() { return $this->uuID; }
    public function getProjectUUID() { return $this->uuID; }

    /**
     * Determines the table name
     */
    public function setFileSummaryInformation($dataTableName=null)
    {
        //get all of the tables associated with this project that have not already been processed:
        Zend_Loader::loadClass('Table_FileSummary');
        $fileSummary        = new Table_FileSummary();
        $whereClause        = "project_id = '" . $this->uuID . "' and imp_done_timestamp is null";
        $orderBy            = "description asc";        
        $unprocessedTables  = $fileSummary->fetchAll($whereClause, $orderBy);
        $this->numTablesUnprocessed = sizeof($unprocessedTables);
        
        $whereClause        = "project_id = '" . $this->uuID . "' and imp_done_timestamp is not null";
        $processedTables  = $fileSummary->fetchAll($whereClause, $orderBy);
        
        //Zend_Debug::dump($whereClause);
        $this->numTablesProcessed   = sizeof($processedTables);
        $this->numTables            = (sizeof($unprocessedTables) + sizeof($processedTables));
        $this->processingMessage    = $this->numTablesProcessed . '/' . $this->numTables  . ' tables processed';
        $this->hasDataRecords       = count($unprocessedTables) > 0;
        if($this->hasDataRecords)
            $this->projectStatusMessage = "** action required";
        else if($this->numTables > 0)
            $this->projectStatusMessage = "completed";
        else
            $this->projectStatusMessage = "** upload data";
        foreach($unprocessedTables as $fileSummaryRow)
        {
            /*$doUpdate = true;
            if(isset($dataTable))
            {
                if($this->dataTableName != $dataTableName)
                    $doUpdate = false;
            }
            if($doUpdate)*/
            if(isset($dataTableName) && $fileSummaryRow->source_id == $dataTableName)
            {                    
                $this->dataTableName    = $fileSummaryRow->source_id;
                $this->dataTableDesc    = $fileSummaryRow->description;
                $this->fileName         = $fileSummaryRow->filename;
                $this->numRows          = $fileSummaryRow->numrows;
                $this->numCols          = $fileSummaryRow->numcols;
                $this->licenseID        = $fileSummaryRow->fk_license;
            }
        }
        
        //if the $dataTableName passed refers to a table that has already been processed:
        if($dataTableName != null && !isset($this->dataTableName))
        {
            $fileSummary = new Table_FileSummary();
            $fileSummaryRow = $fileSummary->fetchRow("source_id = '" . $dataTableName . "'");
            if(isset($fileSummaryRow))
            {
                $this->dataTableName    = $fileSummaryRow->source_id;
                $this->dataTableDesc    = $fileSummaryRow->description;
                $this->fileName         = $fileSummaryRow->filename;
                $this->numRows          = $fileSummaryRow->numrows;
                $this->numCols          = $fileSummaryRow->numcols;
                $this->licenseID        = $fileSummaryRow->fk_license;
            }
            else
            {
                echo 'WARNING:  The table specified in the URL is not defined.';
            }
        }
    }
    
    public function setMetadataInformation()
    {
        if(!isset($this->metadata))
        {
            Zend_Loader::loadClass('Metadata');
            $this->metadata = new Metadata($this);
        }
        //return $this->metadata;
    }
    
    public function setLicense()
    {
        //Zend_Debug::dump($this->licenseID);
        //return;
        Zend_Loader::loadClass('Table_License');
        Zend_Loader::loadClass('License');
        //  Note that licenseID is set at the table level:  if no table
        //  is selected, pick an arbitrary table from the list of processed tables:
        if(!isset($this->licenseID))
        {           
            $db = Zend_Registry::get('db');
            $this->licenseID = $db->fetchOne("SELECT max(fk_license) FROM file_summary WHERE project_id = '" . $this->uuID . "'");
        }
        $licenseTable   = new Table_License();
        $licenseRow     = $licenseTable->fetchRow("PK_LICENSE = " . $this->licenseID);
        $this->license  = new License($licenseRow);
    }
    
    public function getDataGridHelper()
    {
        if(!$this->hasDataRecords)
            return null;
        Zend_Loader::loadClass('Layout_DataGridHelper');
        $dgHelper = new Layout_DataGridHelper();
        $dgHelper->setLayoutFromTableFieldSummary($this->dataTableName);
        $dgHelper->setDataFromDataTable($this->dataTableName, null); //null for where clause
        return $dgHelper;        
    }
    
    public static function addProject($_projectName)
    {
        Zend_Loader::loadClass('User');        
        Zend_Loader::loadClass('Table_Project');
        require_once 'App/Util/GenericFunctions.php';
        $uuID = GenericFunctions::generateUUID();
        
        $user = User::getCurrentUser();
        
        //insert to the database:
        $data = array(
            'project_id'  => $uuID,
            'project_name'  => $_projectName,
            'person_id'     => $user->getId(),
            'person_uuid'   => $user->getUuid());        
        $db = Zend_Registry::get('db');
        $db->insert('project_list', $data);
        
        //now query the table to get the record that was just inserted:
        $projectsTable = new Table_Project();
        $newProjectRow = $projectsTable->fetchRow("project_name = '" . $_projectName . "'");
        return new Project($newProjectRow);
    }
    
    public static function getProjectById($_id)
    {
        $projectsTable = new Table_Project();
        $newProjectRow = $projectsTable->fetchRow("pk_project = '" . $_id . "'");
        return new Project($newProjectRow);   
    }
    
    public static function getProjectByUUID($_uuid)
    {
        //echo $_uuid;
        $_uuid = (string)$_uuid;        
        
        $projectsTable = new Table_Project();
        $newProjectRow = $projectsTable->fetchRow("project_id = '" . $_uuid . "'");
        //Zend_Debug::dump($newProjectRow);
        return new Project($newProjectRow);
        
        //Error with "fetchRow" in returning blob values.  Workaround:
        /*$db = Zend_Registry::get('db');
        $select = $db->select()
            ->from  ('project_list')
            ->where ('project_id = ?', $_uuid);
        $rows = $db->query($select)->fetchAll();
        Zend_Debug::dump($rows[0])
        return new Project($rows[0]);*/   
    }
    
    public static function removeProject($_projectID)
    {   
        Zend_Loader::loadClass('Table_Project');
        
        //delete from database:      
        $db = Zend_Registry::get('db');
        $db->delete('project_list', "pk_project = " . $_projectID);
        return $_projectID;
    }
    
    public function isReadyToBeReviewed()
    {
        $this->initProcessingStatusCounts();
        return $this->spaceCount > 0 || $this->diaryCount > 0 || $this->mediaCount > 0;  
    }
    
    private function initProcessingStatusCounts()
    {
        //query for data:
        $db = Zend_Registry::get('db');
        $this->spaceCount = $db->fetchOne("SELECT count(uuid) FROM space WHERE project_id = '" . $this->uuID . "'");
        $this->diaryCount = $db->fetchOne("SELECT count(uuid) FROM diary WHERE project_id = '" . $this->uuID . "'");
        $this->mediaCount = $db->fetchOne("SELECT count(uuid) FROM resource WHERE project_id = '" . $this->uuID . "'");
    }
    
    public function generateXML()
    {
        if(isset($this->xDoc))
            return $this->xDoc;
        $strXML = '<arch:project 
                        xmlns:arch="'   . $this->namespaceArch . '"
                        xmlns:oc="'     . $this->namespaceOC .'" 
                        xmlns:gml="'    . App_Constants::GML_NS_URI . '" 
                        xmlns:dc="'     . App_Constants::DC_NS_URI . '"
                        />';
        $this->xDoc = new SimpleXMLElement($strXML);
        $this->generateGenericXML($this->xDoc);
        $this->generateLinksXML($this->xDoc);
        $this->generateNotesXML($this->xDoc);
        $this->generateMetadataXML($this->xDoc);
        return $this->xDoc;
    }
    
    private function generateGenericXML($parentNode)
    {
        $this->initProcessingStatusCounts();
        
        //add top-level attributes:
        $parentNode->addAttribute("UUID", $this->uuID);
        $parentNode->addAttribute("ownedBy", $this->uuID);
        
        //add child nodes
        $manageInfoXML = $parentNode->addChild('manage_info', '', $this->namespaceOC);
        $manageInfoXML->addChild('queryVal', str_replace(' ', '+', $this->name), $this->namespaceOC);
        //add root path:
        $manageInfoXML->addChild('rootPath', 'Eric, I\'m not sure what a rootPath is!', $this->namespaceOC);
        $manageInfoXML->addChild('spaceCount', $this->spaceCount, $this->namespaceOC);
        $manageInfoXML->addChild('diaryCount', $this->diaryCount, $this->namespaceOC);
        $manageInfoXML->addChild('mediaCount', $this->mediaCount, $this->namespaceOC);  
    }
    
    private function generateLinksXML($parentNode)
    {
        $linksNode = $parentNode->addChild('links', '', $this->namespaceArch);
        if(sizeof($this->links) == 0)
            $this->getLinks();
            
        //set spatial root:
        $docIDNode = $linksNode->addChild('docID', '???How is the project root determined???', $this->namespaceArch);
        $docIDNode->addAttribute("type", "spatialUnit");
        $docIDNode->addAttribute("info", "project root");
        
        //set people:
        foreach($this->links as $link)
        {
            if(isset($link->target) && $link->target->getItemType() == App_Constants::PERSON)
            {
                $docIDNode = $linksNode->addChild('docID', $link->target->getUUID(), $this->namespaceArch);
                $docIDNode->addAttribute("type", "person");
                $docIDNode->addAttribute("info", "Project Participant");
            }
        }
        
        //todo:  set space links and tree:
        //question:  not sure how the project root is determined, or how far to
        //           go down in terms of tree traversal.
            
        //2) add remaining links to linksNode:
        foreach($this->links as $link)
        {
            //NOTE:  each ContextItem type implements its own linkXML
            if(isset($link->target))
                $link->target->generateTargetLinkXML($linksNode, $link->linkType, $this->namespaceOC);
        }     
    }
    
    public function generateNotesXML($parentNode)
    {
        
        $notesNode  = $parentNode->addChild('notes', '', $this->namespaceArch);
        
        //add short description node:
        $noteNode   = $notesNode->addChild('note', '', $this->namespaceArch);
        $noteNode->addAttribute("type", "short_des");
        $noteNode->addChild('string', $this->shortDesc, $this->namespaceArch);
        
        //add long description node:
        $noteNode   = $notesNode->addChild('note', '', $this->namespaceArch);
        $noteNode->addAttribute("type", "long_des");
        $noteNode->addChild('string', $this->longDesc, $this->namespaceArch);
    }
    
    public function generateMetadataXML($parentNode)
    {
        $this->setMetadataInformation();
        $metadataNode = $parentNode->addChild('metadata', '', $this->namespaceOC);
        $this->metadata->generateGenericXML($metadataNode, $this, $this->namespaceOC);
    }
}