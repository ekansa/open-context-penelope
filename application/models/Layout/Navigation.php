<?php

class Layout_Navigation
{
    public $currentPageID;
    public $enabledPageIDs;    
    public $pages;
    public $checklist           = array();
    public $errorMessages       = array();
    public $warningMessages     = array();
    public $successMessages     = array();
    public $controllerName;
    
    public $hasBeenClassified   = false;    //only relevant if dataTable is selected
    public $hasBeenAnnotated    = false;    //only relevant if dataTable is selected
    public $hasBeenLinked       = false;    //only relevant if dataTable is selected
    public $hasBeenAssignedProps= false;    //only relevant if dataTable is selected
    public $hasBeenTransformed  = false;    //only relevant if dataTable is selected
    
    const PROJECT_PAGE_ID       = 0;
    const METADATA_PAGE_ID      = 1;
    const DATATABLE_PAGE_ID     = 2;
    const LICENSE_PAGE_ID       = 3;
    const USER_PAGE_ID          = 4;
    const CLASSIFY_PAGE_ID      = 5;
    const ANNOTATE_PAGE_ID      = 6;
    const RELATIONSHIPS_PAGE_ID = 7;
    const PROPERTY_PAGE_ID      = 8;
    const TRANSFORM_PAGE_ID     = 9;
    const EDIT_PAGE_ID          = 10;
    const XMLFEED_PAGE_ID       = 11;
    
    /*
                        array(
                              Layout_Navigation::LICENSE_PAGE_ID, METADATA_PAGE_ID , Layout_Navigation::DATATABLE_PAGE_ID, LICENSE_PAGE_ID,
                              USER_PAGE_ID , CLASSIFY_PAGE_ID, ANNOTATE_PAGE_ID, RELATIONSHIPS_PAGE_ID,
                              PROPERTY_PAGE_ID, TRANSFORM_PAGE_ID, EDIT_PAGE_ID, XMLFEED_PAGE_ID);*/
    
    
    private $project;
    private $datatable;
    
    function Layout_Navigation($controllerName, $project=null)
    {
        //1) initialize variables:
        $this->controllerName   = $controllerName;
        $this->project          = $project;
        $this->pages            =  array('Project',
                                         'Metadata',
                                         'Data',
                                         'License',
                                         'Author',
                                         'Classify',
                                         'Annotate',
                                         'Link',
                                         'Properties',
                                         'Finalize',
                                         'Review',
                                         'Post'
                                  );
                  
        //2) determine the page number the wizard is on:
        $this->setPageID();
        
        //3) if a project is selected, initialize corresponding messages:
        $this->initProjectLevelMessages();
        
        //4) if a table is selected, initialize corresponding messages:
        $this->initTableLevelMessages();
        
        //5) determine which pages are enabled:
        $this->setEnabledPages();
    }
    
    public static function getPagesRequiringProject()
    {
        return array('metadata', 'datatable', 'license', 'user', 
            'classify', 'annotate', 'relationships',
            'property', 'transform', 'edit', 'xmlfeed');
    }
    
    public static function getPagesRequiringDataTable()
    {
        return array('license', 'user', 'classify', 'annotate', 'relationships', 'property');
    }
    
    private function setPageID()
    {
        switch($this->controllerName)
        {
            case 'project':
                $this->currentPageID = Layout_Navigation::PROJECT_PAGE_ID;
                break;
            case 'metadata':
                $this->currentPageID = Layout_Navigation::METADATA_PAGE_ID;
                break;
            case 'datatable':
                $this->currentPageID = Layout_Navigation::DATATABLE_PAGE_ID;
                break;
            case 'license':
                $this->currentPageID = Layout_Navigation::LICENSE_PAGE_ID;
                break;
            case 'user':
                $this->currentPageID = Layout_Navigation::USER_PAGE_ID;
                break;
            case 'classify':
                $this->currentPageID = Layout_Navigation::CLASSIFY_PAGE_ID;
                break;
            case 'annotate':
                $this->currentPageID = Layout_Navigation::ANNOTATE_PAGE_ID;
                break;
            case 'relationships':
                $this->currentPageID = Layout_Navigation::RELATIONSHIPS_PAGE_ID;
                break;
            case 'property':
                $this->currentPageID = Layout_Navigation::PROPERTY_PAGE_ID;
                break;
            case 'transform':
                $this->currentPageID = Layout_Navigation::TRANSFORM_PAGE_ID;
                break;
            case 'edit':
                $this->currentPageID = Layout_Navigation::EDIT_PAGE_ID;
                break;
            case 'xmlfeed':
                $this->currentPageID = Layout_Navigation::XMLFEED_PAGE_ID;
                break;                
            default:
                $this->currentPageID = Layout_Navigation::PROJECT_PAGE_ID;;
                break;
        }      
    }
    
    function initProjectLevelMessages()
    {
        if(!isset($this->project))
            return;
        
         //  a) check metadata
        $this->project->setMetadataInformation();
        if($this->project->metadata->hasMetadata)
            $this->successMessages['Metadata'] = 'Metadata has been defined for "' . $this->project->name . '"';
        else
            $this->warningMessages['Metadata'] = 'Please assign metadata for "' . $this->project->name . '"';
        
        
        //  b) check check to see if there are tables which have not been transformed (datatable flag):
        if($this->project->numTablesUnprocessed > 0)
            $this->warningMessages['Data'] = '"' . $this->project->name . '" has ' . $this->project->numTablesUnprocessed . ' unprocessed table(s)';
        else if($this->project->numTablesProcessed > 0)
            $this->successMessages['Data'] = ' All ' . $this->project->numTablesProcessed . ' tables for "' . $this->project->name . '" have been processed';
        else
            $this->warningMessages['Data'] = 'Please upload data for "' . $this->project->name . '"';
            
        //  c) check for transformed records for which no XML files exist
        if($this->project->isReadyToBeReviewed())
        {
            $this->warningMessages['Post'] = 'Records exist for "' . $this->project->name . '" which have not yet been posted to OpenContext.';
        }
    }
    
    function initTableLevelMessages()
    {
        if(!isset($this->project->dataTableName))
            return;
        
        //-----------------------
        //  a) license assigned?
        //-----------------------
        if(isset($this->project->licenseID))
            $this->successMessages['License'] = 'A license has been assigned to "' . $this->project->dataTableDesc . '"';
        else
            $this->warningMessages['License'] = 'Please assign a license to "' . $this->project->dataTableDesc . '"';
        
        //--------------------
        //  b) uses assigned?
        //--------------------
        Zend_Loader::loadClass('UserController');
        $rows = UserController::getPeopleAssignedToProject($this->project->dataTableName);
        if(sizeof($rows) == 0)
            $this->warningMessages['Author'] = 'Please assign a person to "' . $this->project->dataTableDesc . '"';
        else
        {
            if(sizeof($rows) == 1)
                $this->successMessages['Author'] = '1 person has been assigned to "' . $this->project->dataTableDesc . '"';
            else
                $this->successMessages['Author'] = sizeof($rows) . ' people have been assigned to "' . $this->project->dataTableDesc . '"';
        }
        
        if(isset($this->project) && isset($this->project->dataTableName))
        {
            //  c) classified?
            $this->setClassificationStatus();
            
            //  d) annotated?
            $this->setAnnotationStatus();
            
            //  e) relationships established?
            $this->setRelationshipStatus();
            
            //  f) properties assigned?
            $this->setPropertyAssignmentStatus();
            
            //  g) has table been transformed?
            $this->setTransformationStatus();
        }
        
    }
    
    //This function determines the other pages that the user is allowed to access,
    //given the current state of the data upload / mapping process:
    function setEnabledPages()
    {
        //1) the project page is always enabled:
        $this->enabledPageIDs = array(Layout_Navigation::PROJECT_PAGE_ID);
        
        //2) if a project is selected, metadata and upload data pages are always enabled:
        if(isset($this->project))
        {
            array_push($this->enabledPageIDs, Layout_Navigation::METADATA_PAGE_ID);
            array_push($this->enabledPageIDs, Layout_Navigation::DATATABLE_PAGE_ID);
        }
        
        //3) if a data table is selected, license, user, and classify pages are always enabled:
        if(isset($this->project) && isset($this->project->dataTableName))
        {
            array_push($this->enabledPageIDs, Layout_Navigation::LICENSE_PAGE_ID);
            array_push($this->enabledPageIDs, Layout_Navigation::USER_PAGE_ID);
            array_push($this->enabledPageIDs, Layout_Navigation::CLASSIFY_PAGE_ID);
        }
        
        //4) determine the rest based on the Navigation object flags, which are initialized
        //      in the constructor function.
        if($this->hasBeenClassified)
            array_push($this->enabledPageIDs, Layout_Navigation::ANNOTATE_PAGE_ID);
            
        if($this->hasBeenClassified && $this->hasBeenAnnotated)
            array_push($this->enabledPageIDs, Layout_Navigation::RELATIONSHIPS_PAGE_ID);
            
        if($this->hasBeenLinked)
            array_push($this->enabledPageIDs, Layout_Navigation::PROPERTY_PAGE_ID);
            
        if($this->hasBeenAssignedProps || (isset($this->project) && $this->project->numTablesProcessed > 0))
            array_push($this->enabledPageIDs, Layout_Navigation::TRANSFORM_PAGE_ID);
        
        
        if(isset($this->project) && $this->project->isReadyToBeReviewed() && $this->currentPageID != Layout_Navigation::EDIT_PAGE_ID)
            array_push($this->enabledPageIDs, Layout_Navigation::EDIT_PAGE_ID);
            
        if(isset($this->project) && $this->project->isReadyToBeReviewed() && $this->currentPageID != Layout_Navigation::XMLFEED_PAGE_ID)
            array_push($this->enabledPageIDs, Layout_Navigation::XMLFEED_PAGE_ID);
    }
    
    private function setClassificationStatus()
    {
        Zend_Loader::loadClass('Table_FieldSummary');        
        $fieldSummaryTable = new Table_FieldSummary();
        $whereClause = ("source_id = '" . $this->project->dataTableName . "'");
        $rows = $fieldSummaryTable->fetchAll($whereClause);
        $numFieldsSet       = 0;
        $numFieldsDescribed = 0;
        foreach($rows as $row)
        {
            if(isset($row->field_type) && strlen($row->field_type) > 0) { ++$numFieldsSet; }
            if(isset($row->field_lab_com) && strlen($row->field_lab_com) > 0) { ++$numFieldsDescribed; }    
        }
        $message                = 'All fields have been classified.';
        $isComplete             = (count($rows) == $numFieldsSet);
        $isDescriptionComplete  = (count($rows) == $numFieldsDescribed);
        
        if($isComplete && !$isDescriptionComplete)
        {
            $this->successMessages['Classify'] = 'All fields have been classified, but ' . $numFieldsDescribed . ' out of ' . count($rows) . ' fields have been described for "' . $this->project->dataTableDesc . '"';
            $this->hasBeenClassified = true;
        }
        if(!$isComplete)
        {
            $this->warningMessages['Classify'] = $numFieldsSet . ' out of ' . count($rows) . ' fields have been classified for "' . $this->project->dataTableDesc . '"';   
        }
    }
    
    private function setAnnotationStatus()
    {        
        $db = Zend_Registry::get('db');
        $select = $db->select()
            ->from  (
                        array('f' => 'field_summary'),
                        array('f.fk_class_uuid')
            )
            ->where ("f.source_id = '" . $this->project->dataTableName . "' and f.field_type = '" . App_Constants::SPATIAL . "'");
        $stmt = $db->query($select);
        $rows = $stmt->fetchAll();
        if(sizeof($rows) == 0)
            $this->warningMessages['Annotate'] = 'All ' . App_Constants::SPATIAL . ' fields still must be assigned to a class.';    
        $annotatedFields = 0;
        foreach($rows as $row)
        {
            if($row['fk_class_uuid'] != null)
                ++$annotatedFields;
        }
        if($annotatedFields != sizeof($rows))
        {            
            $this->warningMessages['Annotate'] = (sizeof($rows) - $annotatedFields) . ' ' . App_Constants::SPATIAL . ' fields need to be assigned to a class.';
        }
        else
        {
            $this->hasBeenAnnotated = true;
            $this->successMessages['Annotate'] = 'All ' . App_Constants::SPATIAL . ' fields have been assigned to a class.';
        }    
    }
    
    private function setRelationshipStatus()
    {
        Zend_Loader::loadClass('Table_FieldLink');        
        $fieldLinkTable = new Table_FieldLink();
        $whereClause = ("source_id = '" . $this->project->dataTableName . "'");
        $rows = $fieldLinkTable->fetchAll($whereClause);
        if(sizeof($rows) == 0)
        {
            $this->warningMessages['Link'] = 'No relationships have been established.';
        }
        else
        {
            $this->hasBeenLinked = true;
            $this->successMessages['Link'] = sizeof($rows) . ' relationships have been established.';
        }
    }
    
    private function setPropertyAssignmentStatus()
    {
        Zend_Loader::loadClass('Table_FieldSummary');        
        $fieldSummaryTable = new Table_FieldSummary();
        $whereClause = ("source_id = '" . $this->project->dataTableName . "' and field_type = 'Property'");
        $rows = $fieldSummaryTable->fetchAll($whereClause);
        
        $propertiesAssigned = 0;
        foreach($rows as $row)
        {
            if($row->fk_field_describes != null)
                ++$propertiesAssigned;   
        }
        if(sizeof($rows) == $propertiesAssigned && sizeof($rows) > 0)
        {
            $this->hasBeenAssignedProps = true;
            $this->successMessages['Properties'] = 'All properties have been assigned for "' . $this->project->dataTableDesc . '"';    
        }
        else
        {
            $this->warningMessages['Properties'] = $propertiesAssigned . ' out of ' . sizeof($rows) . ' properties have been assigned for "' . $this->project->dataTableDesc . '"';     
        }    
    }
    
    private function setTransformationStatus()
    {
        Zend_Loader::loadClass('Table_FileSummary');        
        $fileSummary = new Table_FileSummary();
        $row = $fileSummary->fetchRow("source_id = '" . $this->project->dataTableName . "'");
        if($row->process_order == null)
        {
            $this->warningMessages['Finalize'] = '"' . $this->project->dataTableDesc . '" has not been finalized.';
        }
        else
        {
            $this->hasBeenTransformed = true;
            $this->successMessages['Finalize'] = '"' . $this->project->dataTableDesc . '" has been finalized and imported into the system';
        }
    }
    
    private function readyToBeFinalized()
    {
        return true;
        $c = $this->checklist;
        $isCurrentTableReady    = $c['Data'] && $c['License'] && $c['Author'] && $c['Classify'] && $c['Annotate'] && $c['Link'] && $c['Properties'];
        $canTablesBeUndone      = $this->project->numTablesProcessed > 0;
        return $isCurrentTableReady || $canTablesBeUndone;
    }

}