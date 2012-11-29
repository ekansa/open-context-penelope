<?php

class Importer_TransformDataController extends Zend_Controller_Action
{
    public $relationshipInserts = 0;
    public $levels              = 0;
    function init()
    {  
        $this->view->baseUrl = $this->_request->getBaseUrl();
        Zend_Loader::loadClass('Zend_Debug');
        
        require_once 'App/Util/GenericFunctions.php';
        Zend_Loader::loadClass('User');
        Zend_Loader::loadClass('Table_Project');
        Zend_Loader::loadClass('Table_FileSummary');
        Zend_Loader::loadClass('Table_FieldSummary');
        Zend_Loader::loadClass('Table_Variable');
        Zend_Loader::loadClass('Table_VariableNotes');
        Zend_Loader::loadClass('Table_Value');
        Zend_Loader::loadClass('Table_ValueLookup');
        Zend_Loader::loadClass('Table_Property');
        
        Zend_Loader::loadClass('Table_Space');
        Zend_Loader::loadClass('Table_SpaceContain');
        Zend_Loader::loadClass('Table_SpaceLookup');
        Zend_Loader::loadClass('Table_Observe');
        Zend_Loader::loadClass('Table_PersonLookup');
        Zend_Loader::loadClass('Table_LinkRelationship');
        
        Zend_Loader::loadClass('Table_ResourceLookup');
        Zend_Loader::loadClass('Table_Resource');
        Zend_Loader::loadClass('Table_Diary');
        Zend_Loader::loadClass('Table_DiaryLookup');        
        
        Zend_Loader::loadClass('Layout_DataGridHelper');
        //Zend_Loader::loadClass('SpatialContainmentProcessingStateManager');
        Zend_Loader::loadClass('SpatialContainment_StateManager');
        Zend_Loader::loadClass('SpatialContainment_StackObject');
    }
    
    
    private function deleteRecords($dataTableName)
    {
        $db = Zend_Registry::get('db');
        
        //Remove any records which are currently associated with the table:
        $db->delete('var_tab',        "source_id = '" . $dataTableName . "'");
        $db->delete('var_notes',      "source_id = '" . $dataTableName . "'");
        $db->delete('w_val_ids_lookup', "source_id = '" . $dataTableName . "'");
        $db->delete('val_tab',        "source_id = '" . $dataTableName . "'");
        $db->delete('properties',     "source_id = '" . $dataTableName . "'");
        $db->delete('space',          "source_id = '" . $dataTableName . "'");
        $db->delete('space_contain',  "source_id = '" . $dataTableName . "'");
        $db->delete('space_lookup',   "source_id = '" . $dataTableName . "'");
        $db->delete('observe',        "source_id = '" . $dataTableName . "'");
        $db->delete('links',          "source_id = '" . $dataTableName . "'");
        $db->delete('persons_lookup',  "source_id = '" . $dataTableName . "'");
        //todo:  check with Eric about the users table:
        $db->delete('users',            "source_id = '" . $dataTableName . "'");
        
        $db->delete('diary',          "source_id = '" . $dataTableName . "'");
        $db->delete('diary_lookup',   "source_id = '" . $dataTableName . "'");        
        $db->delete('resource',       "source_id = '" . $dataTableName . "'");
        $db->delete('resource_lookup',"source_id = '" . $dataTableName . "'");
        
        $fileSummary = new Table_FileSummary();
        $data = array('imp_done_timestamp' => null, 'process_order' => null);
        $fileSummary->update($data, "source_id = '" . $dataTableName . "'");
    }
    
    function undoLastImportAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $dataTableName   = $_REQUEST['dataTableName'];
        $this->deleteRecords($dataTableName);
        echo 'undone';
    }
    
    /***********/
    function getProcessedTablesAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $projectUUID    = $_REQUEST['projectUUID'];
        $db = Zend_Registry::get('db');        
        
        $select = $db->select()
            ->distinct()
            ->from(
                array('f' => 'file_summary'),
                array(
                      'id' => 'f.pk_file_summary',
                      'f.filename', 'f.source_id',
                      'f.numrows',
                      'f.imp_done_timestamp',
                      'f.process_order'
                )
            )
            ->where('imp_done_timestamp is not null')
            ->where('f.project_id = ?', $projectUUID)
            ->order(array('f.process_order'));
        $rows = $db->query($select)->fetchAll();
        if(sizeof($rows) == 0)
            return "";
        //echo Zend_Json::encode($rows);
        //return;
        
        $layout = array();
        //array_push($layout, array('formatter' => 'undoImport', 'name' => '&nbsp;', 'width' => '40px'));
        array_push($layout, array('field' => 'process_order', 'name' => 'Order', 'width' => '30px'));
        array_push($layout, array('field' => 'filename', 'name' => 'Filename', 'width' => '100px'));
        array_push($layout, array('field' => 'source_id', 'name' => 'Table', 'width' => '90px'));
        array_push($layout, array('field' => 'numrows', 'name' => 'Rows', 'width' => '25px')); 
        array_push($layout, array('field' => 'imp_done_timestamp', 'name' => 'Date', 'width' => '40px'));
        
        $dgHelper = new Layout_DataGridHelper();
        $dgHelper->setDataRecords($rows, "id");
        $dgHelper->layout = $layout;
        echo Zend_Json::encode($dgHelper);
    }
    
            
    
    /*************/
    /* VARIABLES */
    /*************/
    function transformVariablesAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $dataTableName = $_REQUEST['dataTableName'];
        
        //delete all records associated with this table:
        $this->deleteRecords($dataTableName);
        
        $db = Zend_Registry::get('db');
        //Get all of $dataTableName's variables in the field_summary table:        
        //retrieve the file_summary record:
        $fileSummary    = new Table_FileSummary();
        $fileSummaryRow = $fileSummary->fetchRow("source_id = '" . $dataTableName . "'");

        //retrieve the associated field_summary records that are designated as 'Property':
        $fieldSummary    = new Table_FieldSummary();
        $select = $fieldSummary->select()->where('field_type = ?', 'Property');
        $fieldRows      = $fileSummaryRow->findDependentRowset('Table_FieldSummary', null, $select);
        
        //retrieve the parent project record:
        $projectRow     = $fileSummaryRow->findParentRow('Table_Project');
        $projectUUID    = $projectRow->project_id;
        
        //iterate through each file_summary record and insert into var_tab
        //(if doesn't already exist)
        $insertedFields = array();
        $updatedFields = array();
        foreach($fieldRows as $fieldRow)
        {
            $propType   = $fieldRow->prop_type;
            $fieldLabel = $fieldRow->field_label;
            $varNotes   = $fieldRow->prop_desc;
            $fieldNum   = $fieldRow->field_num;
            $varhash    = md5($projectUUID . $fieldLabel . $propType);
            
            //check to see if record exists before inserting:
            $variable       = new Table_Variable();
            $variableNotes  = new Table_VariableNotes();

            $whereClause = "project_id = '" . $projectUUID . "' and var_label = '" . $fieldLabel. "'";
            $varRecord  = $variable->fetchRow($whereClause);

            //if the variable is brand new, insert it:
            $varUUID = null;
            if($varRecord == null)
            {
                $varUUID = GenericFunctions::generateUUID();
                $data = array(
                   'project_id'=> $projectUUID,
                   'source_id'       => $dataTableName,
                   'var_hash'       => $varhash,
                   'variable_uuid'  => $varUUID,
                   'var_label'      => $fieldLabel,
                   'var_type'       => $propType
                );
                $variable->insert($data);
                array_push($insertedFields, $fieldLabel);
            }
            //otherwise:
            else
            {
                $varUUID = $varRecord->variable_uuid;
                //compare data types -- see if any updates are needed:
                if($varRecord->var_type == $propType)
                {
                    //do nothing, the record already exists
                }
                else
                {
                    //determine which variable *type* overrides
                    $oldFileSummary     = new Table_FileSummary();
                    $oldFileSummaryRow  = $oldFileSummary->fetchRow("source_id = '" . $varRecord->source_id . "'");
                    $recordCountNew     = $oldFileSummaryRow->numrows;
                    $recordCountCurrent = $fileSummaryRow->numrows;
                    
                    //if the new record has at least twice as many records as the original,
                    //over-write the record with the new value
                    if($recordCountNew*2 > $recordCountCurrent)
                    {
                        //update current record with a new $propType:
                        $data = array(
                            'project_id'=> $projectUUID,
                            'source_id'       => $dataTableName,
                            'var_hash'       => $varhash,
                            'variable_uuid'  => '',
                            'var_label'      => $fieldLabel,
                            'var_type'       => $propType
                         );
                        $variable->update($data, $whereClause);
                        array_push($updatedFields, $fieldLabel);
                    }
                }
            }
            //Now add var_notes:
            //echo $varNotes;
            if($varNotes != null && strlen($varNotes) > 0)
            {
                $whereClause = "project_id = '" . $projectUUID . "' and note_text = '" . $varNotes. "' and variable_uuid='" . $varUUID . "'";
                $varNotesRecord  = $variableNotes->fetchRow($whereClause);
                if($varNotesRecord == null)
                {
                    $noteUUID = GenericFunctions::generateUUID();
                    $data = array(
                        'project_id'   => $projectUUID,
                        'source_id'          => $dataTableName,
                        'variable_uuid'     => $varUUID,
                        'note_uuid'         => $noteUUID,
                        'note_text'         => $varNotes,
                        'field_num'         => $fieldNum
                     );
                    $variableNotes->insert($data);
                }
            }
            
            
        }
        //echo "Variables have been transformed";
        
        
        //Add process_notes to file summary table:
        $notes = sizeof($insertedFields) . " records were inserted and " . sizeof($updatedFields) . " records were updated in the var_tab table.";
        $fileSummary    = new Table_FileSummary();
        $fileSummaryRow = $fileSummary->fetchRow("source_id = '" . $dataTableName . "'");
        $data = array('process_notes' => $notes);
        $fileSummary->update($data, "source_id = '" . $dataTableName . "'");
        
        $finalArray = array();
        $finalArray[0] = $insertedFields;
        $finalArray[1] = $updatedFields;
                
        echo Zend_Json::encode($finalArray);
    }
    
    
    
    /**********
     * VALUES *
     **********
     * This function:
     * 1)   iterates through each field in the selected table that has been designated
     *      as a "property" field.
     * 2)   queries the selected table for the records associated with the particular
     *      property.  These are the property's "values."
     * 3)   insert a new value into the val_tab table, if it doesn’t already exist
     *      for the project.
     * 4)   checks to see if the variable – value pair exists in the properties table.
     *      If no property record exists:
     *      a) inserts the new variable–value pair into properties.
     *      b) inserts the new variable–value pair into w_val_ids_lookup.  w_val_ids_lookup
     *         links the property to a field number and row number so that it can be linked
     *         to the source data.   
     */
    function transformValuesAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $dataTableName = $_REQUEST['dataTableName'];
        
        //retrieve the file_summary record:
        $fileSummary    = new Table_FileSummary(); //instantiate a 'file_summary' Zend table object
        $fileSummaryRow = $fileSummary->fetchRow("source_id = '" . $dataTableName . "'");
        
        //retrieve the parent project record (the record of the 'project_list'
        //table that is associated with this 'file_summary' record):
        $projectRow     = $fileSummaryRow->findParentRow('Table_Project');
        $projectUUID    = $projectRow->project_id;

        //get all of the field records associated with the selected *project*
        //remember that variable names are at the project level, not the data
        //table level:
        $db = Zend_Registry::get('db');
        $select = $db->select()
        ->distinct()
        ->from  (
                    array('s' => 'field_summary'),
                    array('s.field_name', 's.field_label', 's.field_num')
        )
        ->join  (
                    array('f' => 'file_summary'),
                    'f.source_id = s.source_id',
                    array('f.fk_project')
        )
        ->join  (
                    array('p' => 'project_list'),
                    'p.pk_project = f.fk_project',
                    array('p.project_id')
        )
        ->join  (
                    array('v' => 'var_tab'),
                    'p.project_id = v.project_id and s.field_label = v.var_label',
                    array('v.variable_uuid')
        )
        ->where ("s.source_id = '" . $dataTableName . "'");
        
        $stmt = $db->query($select);
        //Zend_Debug::dump($stmt);
        $fieldRows = $stmt->fetchAll();
        //Zend_Debug::dump($fieldRows);
        //return;
        
        //Instantiate the relevant Zend Table Objects: 
        $value      = new Table_Value();            // gives us access to 'val_tab' table functionality
        $valueLookup= new Table_ValueLookup();      // gives us access to 'w_val_ids_lookup' table functionality
        $property   = new Table_Property();         // gives us access to 'properties' table functionality
        
        $returnArray = array();
        
        foreach($fieldRows as $row) //foreach property field in the table
        {
            $fieldNumber    = $row['field_num'];
            $fieldName      = $row['field_name'];
            $fieldLabel     = $row['field_label'];
            $variableUUID   = $row['variable_uuid'];
            
            //querying the records associated with the "fieldName" property (one query per property):
            $select = $db->select()
                ->distinct()
                ->from( array('t' => $dataTableName), array('t.' . $fieldName));
                //->group('t.' . $fieldName);
            $stmt = $db->query($select);
            $varRows = $stmt->fetchAll();
            
            //looping through each property value here...
            $cntValues = 0;
            $cntProperties = 0;
            foreach($varRows as $varRow) //foreach property record in the table:
            {
                if($varRow[$fieldName] != null)
                {
                    //first, check to see if the value already exists in the table:
                    $theText = trim($varRow[$fieldName]);
                    //$fieldID = $varRow['id'];
                    $whereClause = "project_id = '" . $projectUUID . "' and val_text = '" . $theText . "'";
                    $valRecord  = $value->fetchRow($whereClause);
                    $valueUUID  = "";
                    $numval     = null;
                    
                    //if it's a new value, insert into 'val_tab'...
                    if($valRecord == null)
                    {
                        $valueUUID  = GenericFunctions::generateUUID();
                        $valScram   = md5($theText . $projectUUID);
                        
                        //determine whether the value's a double?  Don't really understand this
                        //particular piece of code...
                        $numval = null;
                        if(strlen($theText) > 0)
                        {
                            $numcheck = "0".$theText;
                            if(is_numeric($numcheck))
                                $numval = $numcheck;
                        }
                        
                        //insert the value into the val_tab table:
                        $data = array(
                            'project_id'   => $projectUUID,
                            'source_id'          => $dataTableName,
                            'text_scram'        => $valScram,
                            'val_text'          => $theText,
                            'value_uuid'        => $valueUUID,
                            'val_num'           => $numval
                         );
                        $value->insert($data);
                        
                        ++$cntValues;
                    }
                    //if it's not a new value, no need to insert, but populate local variables:
                    else
                    {
                        $valueUUID  = $valRecord->value_uuid;
                        $numval     = $valRecord->val_num;
                    }//end if
                    
                    //check to see if there's already an entry in the properties table:
                    $whereClause = "project_id = '" . $projectUUID . "' and value_uuid = '" . $valueUUID . "' and variable_uuid = '" . $variableUUID . "'";
                    $propertyRecord  = $property->fetchRow($whereClause);
                    if($propertyRecord == null)
                    {
                        $propHash   = md5($projectUUID . $variableUUID . $valueUUID);
                        $propUUID   = GenericFunctions::generateUUID();
                        //insert the property into the properties table:
                        $data = array(
                            'project_id'   => $projectUUID,
                            'source_id'          => $dataTableName,
                            'prop_hash'         => $propHash,
                            'property_uuid'     => $propUUID,
                            'variable_uuid'     => $variableUUID,
                            'value_uuid'        => $valueUUID,
                            'val_num'           => $numval
                         );
                        $property->insert($data);                       
                                                   
                        // get all of the other records in $dataTableName with the same value
                        // as that which is associated with $valueUUID.  Insert all of these
                        // records into the w_val_ids_lookup table:
                        $select = $db->select()
                            ->distinct()
                            ->from(
                                    array('t' => $dataTableName),
                                    array('t.id', 't.' . $fieldName)
                            )
                            ->where('t.' . $fieldName . ' = ?', $theText);
                        $lookupRows = $db->query($select)->fetchAll();
                        foreach($lookupRows as $lookupRow)
                        {
                            $fieldID = $lookupRow['id'];
                            $data = array(
                                'source_id'          => $dataTableName,
                                'variable_uuid'     => $variableUUID,
                                'value_uuid'        => $valueUUID,
                                'field_num'         => $fieldNumber,
                                'row_num'           => $fieldID
                             );
                            $valueLookup->insert($data);    
                        }
                        ++$cntProperties;
                    }
                } //end if value not null
            } //end foreach value
            array_push($returnArray, array( 'property' => $fieldLabel, 'numValues' => $cntValues, 'numProperties' => $cntProperties));
        } //end foreach property
        echo Zend_Json::encode($returnArray);
    }
    
    
    
    /********************************
     * SPATIAL CONTAINMENT - PART I *
     ********************************
     *
     * transformSpatialRelationshipsAction() works by:
     * 1)   finding the top node for each spatial containment relationship in
     *      the project.
     * 2)   iterating through each top node.
     * 3)   calling the "transformData" function for each node (which is a recursive)
     *      function that actually performs the spatial containment logic.
     *
   */
    function transformSpatialRelationshipsAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $dataTableName = $_REQUEST['dataTableName'];
        
        $stateManager = new SpatialContainment_StateManager(true);
        $stateManager->initTransformSpatialContainment($dataTableName);
        $stateManager->clearReturnMessage();
        $stateManager->setHasEchoed(false);
    }
    
    
    function continueTransformingSpatialRelationshipsAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $batchSize      = $_REQUEST['batchSize'];
        $dataTableName  = $_REQUEST['dataTableName'];
        $stateManager   = new SpatialContainment_StateManager(false);
        $stateManager->clearReturnMessage();
        $stateManager->setHasEchoed(false);
        $returnArray = array();
        
        if(sizeof($stateManager->getStack()) == 0)
        {
            $stateManager->continueToNextParent($dataTableName);
        }
        for($i=0; $i < $batchSize; ++$i)
        {
            if(sizeof($stateManager->getStack()) == 0)
            {
                if(!$stateManager->getHasEchoed())
                {
                    array_push($returnArray, 'Total number of records processed: ' . $stateManager->getNumRecordsProcessed());
                    array_push($returnArray, $stateManager->getReturnMessage());
                    echo Zend_Json::encode($returnArray);
                }
                return;
            }
            $stateManager   = new SpatialContainment_StateManager(false);
            $stateManager->transformCurrentNode();
        }
        if(!$stateManager->getHasEchoed())
        {
            array_push($returnArray, 'Total number of records processed: ' . $stateManager->getNumRecordsProcessed());
            array_push($returnArray, $stateManager->getReturnMessage());
            echo Zend_Json::encode($returnArray);
        }
    }
    
    
    
    /*****************************
     * transformMediaDiaryAction *
     *****************************
     *
     * This function inserts media items and diary items into 
     * their corresponding tables.  
   */
    function transformMediaDiaryAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $dataTableName = $_REQUEST['dataTableName'];        
        $projectUUID    = $_REQUEST['projectUUID'];
        
        $returnArray = array();
        $numRecsInserted = 0;
        
        $resource       = new Table_Resource();
        $resourceLookup = new Table_ResourceLookup();
        $diary          = new Table_Diary();
        $diaryLookup    = new Table_DiaryLookup();
        $numMediaRecsAdded = 0;
        $db = Zend_Registry::get('db');
        
        //1)    get all of the field names where field_type = "Media (various)"
        //      or field_type = "Diary / Narrative":
        $select = $db->select()
            ->distinct()
            ->from  (
                        array('f' => 'field_summary'),
                        array('f.field_name', 'f.field_num', 'f.field_type', 'f.field_label')
            )
            ->where("f.source_id = ?", $dataTableName)
            ->where("f.field_type = 'Media (various)' or f.field_type = 'Diary / Narrative'");
        $stmt = $db->query($select);
        $fieldRecs = $stmt->fetchAll();
        //Zend_Debug::dump($fieldRecs);
        foreach($fieldRecs as $fieldRec)
        {
            $numRecsInserted= 0;
            $fieldName      = $fieldRec['field_name'];
            $fieldNum       = $fieldRec['field_num'];
            $fieldLabel     = $fieldRec['field_label'];
            $fieldType      = $fieldRec['field_type'];
            
            switch($fieldType)
            {
                case "Media (various)":
                    //2) get all of the *distinct* media / diary records from the data table:
                    $select = $db->select()
                        ->distinct()
                        ->from  (
                                array('d' => $dataTableName),
                                array('d.' . $fieldName)
                        )
                        ->where('d.' . $fieldName . ' IS NOT NULL');
                        
                    $dataRecs = $db->query($select)->fetchAll();
                    //Zend_Debug::dump($dataRecs);
                    //3)    iterate through each record and insert into resource
                    //      and resource_lookup if necessary:
                    foreach($dataRecs as $dataRec)
                    {
                        $filenameAndPath= $dataRec[$fieldName];
                        //check to see if the resource already exists for the project:
                        $resourceRow = $resource->fetchRow("res_path_source='" . $filenameAndPath . "' and project_id='" . $projectUUID . "'");
                        //Zend_Debug::dump($resourceRow);
                        if($resourceRow == null)
                        {
                            $resUUID        = GenericFunctions::generateUUID();
                            //4) determine the file name:                            
                            $separators = array('\\', '/'); //possible file path separators (could be different, depending on data table)
                            $resFilename = $filenameAndPath;
                            foreach($separators as $separator)
                            {
                                $tokenArray     = explode($separator, $filenameAndPath);
                                if(sizeof($tokenArray) > 1)
                                {
                                    $resFilename    = $tokenArray[sizeof($tokenArray)-1];
                                    break;
                                }
                            }
                            
                            //5) determine the file type:
                            $resFormat  = null;
                            if(!(strpos($resFilename, '.') === false))
                            {
                                $resFormatArray = explode('.', $resFilename);
                                $resFormat      = $resFormatArray[1];
                            }
                            //6) insert into resource if record doesn't exist:
                            $data = array(
                                'project_id'       => $projectUUID,
                                'source_id'              => $dataTableName,
                                'uuid'              => $resUUID,
                                'res_path_source'       => $filenameAndPath,
                                'res_filename'          => $resFilename,
                                'mime_type'            => $resFormat
                            );
                            //Zend_Debug::dump($data);
                            $resource->insert($data);
                            ++$numRecsInserted;
                            
                            //7) insert all associated records into the lookup table:
                            $selectLU = $db->select()
                                ->from  (
                                        array('d' => $dataTableName),
                                        array('d.id')
                                )
                                ->where('d.' . $fieldName . ' = ?', $filenameAndPath);
                            
                            $luRecs = $db->query($selectLU)->fetchAll();
                            foreach($luRecs as $luRec)
                            {
                                $rowNumber = $luRec['id'];
                                $whereLU =  "source_id='" . $dataTableName . "' and uuid='" . $resUUID .
                                    "' and field_num =" . $fieldNum . " and row_num = " . $rowNumber;
                                $rowLU = $resourceLookup->fetchRow($whereLU);
                                if($rowLU == null)
                                {
                                    $data = array(
                                            'source_id'      => $dataTableName,
                                            'uuid'      => $resUUID,
                                            'field_num'     => $fieldNum,
                                            'row_num'       => $rowNumber
                                    );
                                    $resourceLookup->insert($data);
                                }
                            }
                        }
                    }
                    array_push($returnArray, $numRecsInserted . ' \'Media\' records were inserted for the \'' . $fieldLabel . '\' field.');
                    break;
                case "Diary / Narrative":
                    //2) get all of the diary records from the data table.
                    //   Note:  If it's a diary item, don't query distinct.  Since
                    //   text matching for long query strings seems to be very
                    //   complicated in Zend, just assume that each diary is
                    //   unique:
                    $select = $db->select()
                        ->from  (
                                array('d' => $dataTableName),
                                array('d.' . $fieldName, 'd.id')
                        )
                        ->where('d.' . $fieldName . ' IS NOT NULL');
                    //Zend_Debug::dump($dataTableName);
                    //Zend_Debug::dump($fieldRec);
                    //return;
                    $dataRecs = $db->query($select)->fetchAll();
                    //3)    iterate through each record and insert into diary
                    //      and diary_lookup if necessary.  Note that the lookup
                    //      table for diary items isn't really necessary, but let's
                    //      use it for consistency, or in case diary entries are
                    //      consolidated in the future:
                    foreach($dataRecs as $dataRec)
                    {
                        $rowNumber          = $dataRec['id'];
                        $diaryText          = $dataRec[$fieldName];
                        $diaryTextEscaped   = htmlspecialchars($diaryText);
                        $diaryHash          = $diaryText;
                        if(strlen($diaryHash) > 100)
                            $diaryHash = substr($diaryHash, 0, 100);
                        $diaryHash  = md5($projectUUID . '_' . $diaryHash);
                        $diaryRow   = $diary->fetchRow("diary_hash='" . $diaryHash . "'");
                        if($diaryRow == null)
                        {
                            $diaryUUID        = GenericFunctions::generateUUID();
                            // insert records into the 'main' diary table:
                            $data = array(
                                'uuid'            => $diaryUUID,
                                'project_id'       => $projectUUID,
                                'source_id'              => $dataTableName,
                                'diary_hash'            => $diaryHash,
                                'diary_text_original'   => $diaryText,
                                'diary_text_escaped'    => $diaryTextEscaped
                            );
                            $diary->insert($data);
                            ++$numRecsInserted;

                            // insert all associated records into the lookup table:
                            $data = array(
                                            'source_id'      => $dataTableName,
                                            'uuid'    => $diaryUUID,
                                            'field_num'     => $fieldNum,
                                            'row_num'       => $rowNumber
                                    );
                            //Zend_Debug::dump($data);
                            $diaryLookup->insert($data);
                        }
                    }
                    array_push($returnArray, $numRecsInserted . ' \'Diary / Narrative\' records were inserted for the \'' . $fieldLabel . '\' field.');
                    break;
            }//end switch
            
        }
        echo Zend_Json::encode($returnArray);
    }
    
    
    
    
    /*************************
     * transformPeopleAction *
     *************************
     *
     * This function transforms people by adding them to w_person and
     * persons_lookup.  This function addresses new people who are
     * added through the "Author" tab as well as new people who are
     * specified in the data table itself.
     *
   */
    function transformPeopleAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        
        $projectUUID    = $_REQUEST['projectUUID'];
        $dataTableName  = $_REQUEST['dataTableName'];
        
        //assign people globally (if applicable):
        $messageGlobal  = $this->assignPeopleGlobally($projectUUID, $dataTableName);
        
        //assign people as specified in the data table (if applicable):
        $messageTable   = $this->assignPeopleFromTable($projectUUID, $dataTableName);
        
        $returnArray = array();
        if($messageGlobal != null)
            array_push($returnArray, $messageGlobal);
        if($messageTable != null)
            array_push($returnArray, $messageTable);
        echo Zend_Json::encode($returnArray);
    }
    
    
    
    /*************************
     * assignPeopleGlobally *
     *************************
     *
     * This function addresses new people who are added through the "Author" tab.
     * 'Role' records are queried from the persons_st_des table and linked to:
     * 1)   Locations or Objects records,
     * 2)   Media records,
     * 3)   Narrative records
     *
   */
    private function assignPeopleGlobally($projectUUID, $dataTableName)
    {        
        //get person and role from persons_st_des for the table
        $db = Zend_Registry::get('db');
        $select = $db->select()
            ->distinct()
            ->from  (
                array('p' => 'persons_st_des')
            )
            ->where ('p.source_id = ?', $dataTableName)
            ->where ('p.stnd_var = ?', 'role')
            ->where ('p.link_all = ?', 1);
        $stmt = $db->query($select);
        $personRecords = $stmt->fetchAll();
        //Zend_Debug::dump($projectUUID);
        //Zend_Debug::dump($dataTableName);
        //Zend_Debug::dump($personRecords);
        $numPeople = sizeof($personRecords);
        if($numPeople > 0)
        {        
            //for each global person:
            foreach($personRecords as $personRecord)
            {
                $personUUID = $personRecord['uuid'];
                $roleName   = $personRecord['stnd_vals'];
                $obsNum     = 1;
                
                //1) query for all of the $spaceUUIDs associated with this table:
                 $selectSpatial = $db->select()
                    ->distinct()
                    ->from  (
                                array('s' => 'space'),
                                array('s.uuid')
                    )
                    ->where ('s.source_id = ?', $dataTableName);
                $stmtSpatial    = $db->query($selectSpatial);
                $spatialRecords = $stmtSpatial->fetchAll();
                
                //Zend_Debug::dump($spatialRecords);
                //for each spatial containment record:
                foreach($spatialRecords as $spatialRecord)
                {
                    $spaceUUID = $spatialRecord['uuid'];
                    $linkUUID   = GenericFunctions::generateUUID();
                    $hashLink   = md5($spaceUUID . '_' . $obsNum . '_' . $personUUID . '_' . $roleName);
            
                    $data = array(
                        'project_id'   => $projectUUID,
                        'source_id'          => $dataTableName,
                        'hash_link'         => $hashLink,
                        'link_type'         => $roleName,
                        'link_uuid'         => $linkUUID,
                        'origin_type'       => 'Locations or Objects',         
                        'origin_uuid'       => $spaceUUID,              
                        'origin_obs'        => $obsNum,
                        'targ_type'         => 'Person',        
                        'targ_uuid'         => $personUUID,         
                        'targ_obs'          => $obsNum 
                    );
                    $db->insert('links', $data);
                    //Zend_Debug::dump($data);
                }//end for each spatial containment record
                
                //2)    TODO:  query for all of the resources and also insert into links
                //      See "gen_final_auths_links.php" for more details.
                
            }//end for each global person record
            
            //if statement for good grammar:
            if($numPeople > 1)
                return $numPeople . " people were associated with every location, object, and resource (at the global level).";
            else
                return $numPeople . " person was associated with every location, object, and resource (at the global level).";        
        }
        else
        {
            return null;
        }
    }
    
    
    
    /*************************
     * assignPeopleFromTable *
     *************************
     *
     * This function addresses new people who are added within the data table itself.
     * New 'Person' records are generated and inserted into the 'users' and
     * persons_lookup' tables.  Unlike the "assignPeopleGlobally" function,
     * linking relationships in the 'data table' case are established in the
     * "transformLinksAction" function.
   */
    private function assignPeopleFromTable($projectUUID, $dataTableName)
    {
        $numPeopleAdded = 0;
        $db = Zend_Registry::get('db');
        
        //1) get all of the field names where field_type = "Person":
        $select = $db->select()
            ->from  (
                        array('f' => 'field_summary'),
                        array('f.field_name', 'f.field_num')
            )
            ->where("f.source_id = ?", $dataTableName)
            ->where("f.field_type = ?", "Person");
        $stmt = $db->query($select);
        $personFieldRecs = $stmt->fetchAll();
        foreach($personFieldRecs as $personFieldRec)
        {
            $personField    = $personFieldRec['field_name'];
            $personFieldNum = $personFieldRec['field_num'];
            //2) get all of the *distinct* full name records from the data table:
            $selectFullNames = $db->select()
                ->distinct()
                ->from  (
                        array('d' => $dataTableName),
                        array('combined_name' => 'd.' . $personField)
                )
                ->where('d.' . $personField . ' IS NOT NULL');
                
            $stmtFullNames = $db->query($selectFullNames);
            $fullNameRecs = $stmtFullNames->fetchAll();
            foreach($fullNameRecs as $fullNameRec)
            {
                $fullName   = $fullNameRec['combined_name'];
                //3) check to see if this person is already defined in the 'users' table:
                $selectUser = $db->select()
                    ->distinct()
                    ->from  (
                        array('u' => 'users')
                    )
                    ->where ('u.combined_name = ?', $fullName);
                
                $personObject = $db->query($selectUser)->fetchObject();
                $personUUID = GenericFunctions::generateUUID();
                //Zend_Debug::dump($personObject);
                if($personObject != null)
                {
                    $personUUID = $personObject->uuid;
                }
                else
                {
                    //3)a.  if not, insert the new user now:
                    //note, source_id is only populated here if this is a table_generated
                    //user.  Otherwise, this relationship is specified in the 'persons_st_des' table.
                    $data = array(
                            'combined_name'             => $fullName,
                            'source_id'              => $dataTableName,
                            'uuid'           => $personUUID,
                            'fk_user_last_modified' => User::getCurrentUser()->id
                    );
                    $db->insert('users', $data);
                    ++$numPeopleAdded;
                }
                
                //4)    insert all associated $dataTableName records that have the
                //      same '$fullName' value for the $personField into the
                //      persons_lookup table -- they should all be associated with
                //      the same $personUUID from the $users table:
                $selectLU = $db->select()
                    ->from  (
                            array('d' => $dataTableName),
                            array('d.id')
                    )
                    ->where('d.' . $personField . ' = ?', $fullName);
                
                $luRecs = $db->query($selectLU)->fetchAll();
                $personLookup = new Table_PersonLookup();
                foreach($luRecs as $luRec)
                {
                    $rowNumber = $luRec['id'];
                    $whereLU =  "source_id='" . $dataTableName . "' and uuid='" . $personUUID .
                        "' and field_num =" . $personFieldNum . " and row_num = " . $rowNumber;
                    $rowLU = $personLookup->fetchRow($whereLU);
                    //Zend_Debug::dump($rowLU);
                    if($rowLU == null)
                    {
                        $data = array(
                                'source_id'      => $dataTableName,
                                'uuid'   => $personUUID,
                                'field_num'     => $personFieldNum,
                                'row_num'       => $rowNumber
                        );
                        $personLookup->insert($data);
                    }
                }
            }     
        }
        return $numPeopleAdded . " new people were added to the system.";
    }
    
    
    
    /************************
     * transformLinksAction *
     ************************
     *
     * This function transforms all relationships that aren't spatial relationships.
    */
    function transformLinksAction()
    {
        $returnArray = array();
        $numRelationshipsAdded = 0;
        $this->_helper->viewRenderer->setNoRender();
            
        $projectUUID    = $_REQUEST['projectUUID'];
        $dataTableName  = $_REQUEST['dataTableName'];
        
        //before establishing links, process all non-containment locations and object values:
        $this->addNonContainmentLocationsObjects($projectUUID, $dataTableName);
        
        $db = Zend_Registry::get('db');
        
        
        //echo "Query for all non-containment Locations or Objects fields...";
        // query for all relationships that aren't containment relationships:
        $select = $db->select()
            ->distinct()
            ->from(
                    array('f' => 'field_links'),
                    array(
                         'fieldID'          => 'f.fk_field_parent',
                         'targetID'         => 'f.fk_field_child',
                         'linkID'           => 'f.fk_field_link',
                         'linkTypeID'       => 'f.fk_link_type',
                         'fieldName'        => 'f.field_parent_name',
                         'targetFieldName'  => 'f.field_child_name',
                         'linkFieldName'    => 'f.field_link_name'
                    )
            )
            //linking with additional information about the 'origin' field:
            ->join(
                       array('origin' => 'field_summary'),
                       'f.fk_field_parent = origin.pk_field and f.source_id = origin.source_id',
                       array('originLabel' => 'field_label', 'originType' => 'field_type', 'originFieldNum' => 'field_num')
            )
            //linking with additional information about the 'target' field:
            ->join(
                       array('target' => 'field_summary'),
                       'f.fk_field_child = target.pk_field and f.source_id = target.source_id',
                       array('targetLabel' => 'field_label', 'targetType' => 'field_type', 'targetFieldNum' => 'field_num')
            )
            //linking with additional information about the 'linking' field, if applicable:
            ->joinLeft(
                       array('link' => 'field_summary'),
                       'f.fk_field_link = link.pk_field and f.source_id = link.source_id',
                       array('linkLabel' => 'field_label', 'linkFieldNum' => 'field_num')
            )
            //linking with the 'relationship types lookup table: 
            ->join(
                       array('lu' => 'w_lu_relationship_types'),
                       'f.fk_link_type = lu.pk_relationship_type',
                       array('verb' => 'RELATIONSHIP_VERB', 'relationship' => 'RELATIONSHIP_TYPE')
            )
            ->where ("f.source_id = '" . $dataTableName . "'")
            ->where('fk_link_type <> ?', 1) //everything but containment relationships:
            //->where('target.field_type = ?', 'Locations or Objects')
            //->orWhere('target.field_type = ?', 'Person')
            ->order(array('relationship', 'fieldName', 'targetFieldName'));
        
        $stmt   = $db->query($select);
        $relationshipRows   = $stmt->fetchAll();
        //Zend_Debug::dump($relationshipRows);
        //return;
        
        //Note:  there are 4 possible relationships in the "$relationshipRows" record set:
        //  1) Data-Table-Defined Relationship:
        //     a) origin:  Location or Object | target:  Location or Object relationship
        //     b) origin:  Location or Object | target:  Person
        //  2) Application-Defined Relationship (as specified in w_lu_relationship_types):
        //     a) origin:  Location or Object | target:  Location or Object relationship
        //     b) origin:  Location or Object | target:  Person

        foreach($relationshipRows as $relationshipRow)
        {
            ++$numRelationshipsAdded;
            $numLinksAdded      = 0;
            $obsNum             = 1;
            $originID           = $relationshipRow['fieldID'];
            $targetID           = $relationshipRow['targetID'];
            $linkID             = $relationshipRow['linkID'];
            $linkTypeID         = $relationshipRow['linkTypeID'];
            
            $originType         = $relationshipRow['originType'];
            $targetType         = $relationshipRow['targetType'];
            
            $fieldName          = $relationshipRow['fieldName'];
            $targetFieldName    = $relationshipRow['targetFieldName'];
            $linkFieldName      = $relationshipRow['linkFieldName'];
            
            $originFieldNum     = $relationshipRow['originFieldNum'];
            $targetFieldNum     = $relationshipRow['targetFieldNum'];
            $linkFieldNum       = $relationshipRow['linkFieldNum'];
            
            $relationshipVerb   = $relationshipRow['relationship'];
            
            $fieldLabel         = $relationshipRow['originLabel'];
            $targetFieldLabel   = $relationshipRow['targetLabel'];
            
            $originTableName    = null;
            $originUUIDFieldName= null;
            $targetTableName    = null;
            $targetUUIDFieldName= null;
            
            //switch statement to determine the origin table and origin UUID
            //field to query:
            switch($originType)
            {
                case "Locations or Objects":
                    $originTableName        = 'space_lookup';
                    $originUUIDFieldName    = 'uuid';
                    break;
                case "Person":
                    $originTableName        = 'persons_lookup';
                    $originUUIDFieldName    = 'uuid';
                    break;
                case "Diary / Narrative":
                    $originTableName        = 'diary_lookup';
                    $originUUIDFieldName    = 'uuid';
                    break;
                case "Media (various)":
                    $originTableName        = 'resource_lookup';
                    $originUUIDFieldName    = 'uuid';
                    break;
            }
            
            //switch statement to determine the target table and target UUID
            //field to query:
            switch($targetType)
            {
                case "Locations or Objects":
                    $targetTableName        = 'space_lookup';
                    $targetUUIDFieldName    = 'uuid';
                    break;
                case "Person":
                    $targetTableName        = 'persons_lookup';
                    $targetUUIDFieldName    = 'uuid';
                    break;
                case "Diary / Narrative":
                    $targetTableName        = 'diary_lookup';
                    $targetUUIDFieldName    = 'uuid';
                    break;
                case "Media (various)":
                    $targetTableName        = 'resource_lookup';
                    $targetUUIDFieldName    = 'uuid';
                    break;
            }
            //echo $originTableName . ' -- ' . $originUUIDFieldName . ' -- ' . $targetTableName . ' -- ' . $targetUUIDFieldName . '<br />';
            
            //switch statement that processes the type of non-containment linking
            //(field-defined versus statically defined in w_lu_relationship_types):
            switch($linkTypeID)
            {
                case "2": //linking relationship defined by a field:

                    
                    $dataRows = null;                    
                    //query for all of the data and associated spaceIDs
                     $selectData = $db->select()
                    ->distinct()
                    ->from  (
                            array('d' => $dataTableName),
                            array('id', 'origin' => $fieldName, 'target' => $targetFieldName, 'link' => $linkFieldName)
                    )
                    //link $dataTableName's origin field with the corresponding lookup to get the 
                    //UUID values for the particular field number and row number:
                    ->join(
                            array('originLU' => $originTableName),
                            'd.id = originLU.row_num and originLU.field_num = ' . $originFieldNum,
                            array('originUUID' => $originUUIDFieldName)
                    )
                    //link $dataTableName's target field with the corresponding lookup to get the
                    //UUID values for the particular field number and row number:
                    ->join(
                            array('targetLU' => $targetTableName),
                            'd.id = targetLU.row_num and targetLU.field_num = ' . $targetFieldNum,
                            array('targetUUID' => $targetUUIDFieldName)
                    )
                    ->where($fieldName . " is not null and " . $targetFieldName . " is not null and " . $linkFieldName . " is not null")
                    ->order(array($fieldName, $targetFieldName, $linkFieldName));
                    $dataRows = $db->query($selectData)->fetchAll();
                    //Zend_Debug::dump($dataRows);
                    break;
                default:    //linking relationship defined in w_lu_relationship_types:
                    //query for all of the data and associated spaceIDs
                     $selectData = $db->select()
                    ->distinct()
                    ->from  (
                            array('d' => $dataTableName),
                            array('id', 'origin' => $fieldName, 'target' => $targetFieldName)
                    )
                    //link $dataTableName's origin field with the corresponding lookup to get the 
                    //UUID values for the particular field number and row number:
                    ->join(
                            array('originLU' => $originTableName),
                            'd.id = originLU.row_num and originLU.field_num = ' . $originFieldNum,
                            array('originUUID' => $originUUIDFieldName)
                    )
                    //link $dataTableName's target field with the corresponding lookup to get the
                    //UUID values for the particular field number and row number:
                    ->join(
                            array('targetLU' => $targetTableName),
                            'd.id = targetLU.row_num and targetLU.field_num = ' . $targetFieldNum,
                            array('targetUUID' => $targetUUIDFieldName)
                    )
                    ->where($fieldName . " is not null and " . $targetFieldName . " is not null")
                    ->order(array($fieldName, $targetFieldName));
                    $dataRows = $db->query($selectData)->fetchAll();
                    break;
            }//end switch statement
            
            // The $dataRows record array was populated during the switch statement.  Now
            // insert into w_lins for each data record:
            $linkRelationship = new Table_LinkRelationship();
            foreach($dataRows as $dataRow)
            {                        
                $originUUID = $dataRow['originUUID'];
                $targetUUID = $dataRow['targetUUID'];
                
                // if the relationship isn't defined in the data, that is, 
                // w_lu_relationship_types.pk_relationship_type != 2, then
                // use the global $relationshipVerb for the $linkFieldValue:
                if($linkTypeID != "2")
                    $linkFieldValue = $relationshipVerb;
                else
                    $linkFieldValue = $dataRow['link'];   
                    
                $hashLink       = md5($originUUID . '_' . $obsNum . '_' . $targetUUID . '_' . $linkFieldValue);
                
                //check that we're not inserting a duplicate key:
                $linkRow = $linkRelationship->fetchRow("hash_link='" . $hashLink . "'");
                if($linkRow == null)
                {
                    $linkUUID       = GenericFunctions::generateUUID();                            
                    $data = array(
                        'project_id'   => $projectUUID,
                        'source_id'          => $dataTableName,
                        'hash_link'         => $hashLink,
                        'link_type'         => $linkFieldValue,
                        'link_uuid'         => $linkUUID,
                        'origin_type'       => 'Locations or Objects',         
                        'origin_uuid'       => $originUUID,              
                        'origin_obs'        => $obsNum,
                        'targ_type'         => $targetType,        
                        'targ_uuid'         => $targetUUID,         
                        'targ_obs'          => $obsNum 
                    );
                    //Zend_Debug::dump($data);
                    $linkRelationship->insert($data);
                    ++$numLinksAdded;
                }
            }
            array_push($returnArray, $numLinksAdded . ' new data links were added for a new \'' . $fieldLabel . '\' -> \'' . $targetFieldLabel . '\' linking relationship.');
        }//end for each linking relationship defined
        
        //echo $numRelationshipsAdded . " linking relationships and " . $numLinksAdded . " new data links were added.";
        echo Zend_Json::encode($returnArray);
    }
    
    
    /*************************************
     * addNonContainmentLocationsObjects *
     *************************************
     *
     * This function processes all 'Locations or Objects' that are not
     * spatial containments.  They're all inserted into space and
     * space_lookup for now, but might need to be moved.
    */
    private function addNonContainmentLocationsObjects($projectUUID, $dataTableName)
    { 
        $db = Zend_Registry::get('db');
        // before proceeding, iterate through all of the non-spatial containment
        // 'Location or Object' fields and add them to space and space_lookup:
        $select = $db->select()
            ->distinct()
            ->from(
                    array('f' => 'field_links'),
                    array(
                         'f.fk_field_parent',
                         'f.fk_field_child',
                    )
            )
            ->where('f.source_id = ?', $dataTableName)
            ->where('fk_link_type = ?', 1);
        $containmentLinkRows = $db->query($select)->fetchAll();
        //Zend_Debug::dump($containmentLinkRows);
        $containmentArray = array();
        foreach($containmentLinkRows as $containmentLinkRow)
        {
            if(!in_array($containmentLinkRow['fk_field_child'], $containmentArray))
                array_push($containmentArray, $containmentLinkRow['fk_field_child']);
            if(!in_array($containmentLinkRow['fk_field_parent'], $containmentArray))
                array_push($containmentArray, $containmentLinkRow['fk_field_parent']);
        }

        $select = $db->select()
            ->distinct()
            ->from(
                    array('f' => 'field_summary'),
                    array(
                         'f.pk_field',
                         'f.field_num',
                         'f.field_label',
                         'f.field_type',
                         'f.field_name',
                         'f.field_notes',
                         'f.fk_class_uuid'
                    )
            )
            ->where('f.field_type = ?', 'Locations or Objects')
            ->where('f.source_id = ?', $dataTableName);
        if(sizeof($containmentArray) > 0)
            $select->where('f.pk_field not in (?)', $containmentArray);
        $locObjRecs = $db->query($select)->fetchAll();
        //echo "Locations or Objects that are not containment relations: ";
        //Zend_Debug::dump($locObjRecs);
        //iterate through all non-spatial containment fields and add them
        //to space and space_lookup:
        foreach($locObjRecs as $locObjRec)
        {
            $fieldName  = $locObjRec['field_name'];
            $fieldNum   = $locObjRec['field_num'];            
            $fieldAlias = $locObjRec['field_label'];
            $fieldNotes = $locObjRec['field_notes'];
            $classUUID  = $locObjRec['fk_class_uuid'];
            $select = $db->select()
                ->distinct()
                ->from  (
                        array('d' => $dataTableName),
                        array($fieldName)
                )
                ->where ($fieldName . ' is not null');
            $distinctLocObjDataRecs =  $db->query($select)->fetchAll();
            //Zend_Debug::dump($distinctLocObjDataRecs);
            $space          = new Table_Space();
            $spaceContain   = new Table_SpaceContain();
            $observe        = new Table_Observe();
            $spaceLookup    = new Table_SpaceLookup();
            foreach($distinctLocObjDataRecs as $rec)
            {
                $theValue   = $rec[$fieldName];                
                $hashTxt    = md5($projectUUID . "_" . $theValue);

                //check to see if the parent record exists in the space table:
                $spaceRow = $space->fetchRow("hash_fcntxt = '" . $hashTxt . "'");
    
                if($spaceRow == null)
                {
                    //insert into space
                    $spaceUUID      = GenericFunctions::generateUUID();
                    $spaceLabel     = $theValue;
                    if($fieldAlias != null)
                        $spaceLabel =   $fieldAlias . ' ' . $spaceLabel;      
                    $data = array(
                        'project_id'   => $projectUUID,
                        'source_id'          => $dataTableName,
                        'hash_fcntxt'       => $hashTxt,
                        'uuid'        => $spaceUUID,
                        'space_label'       => $spaceLabel,             
                        //'full_context'      => $theValue, //leave as null for now
                        'sample_des'        => '',
                        'class_uuid'        => $classUUID             
                     );
                    $space->insert($data);
                    
                    //insert into space_contain (as a root node):
                    $rootID = '[ROOT]:' . $projectUUID;
                    $hashAll = md5($rootID . '_' . $spaceUUID);
                    $spaceContainRow = $spaceContain->fetchRow("hash_all = '" . $hashAll . "'");
                    if($spaceContainRow == null)
                    {
                        $data = array(
                            'project_id'   => $projectUUID,
                            'source_id'          => $dataTableName,
                            'hash_all'          => $hashAll,                    
                            'parent_uuid'       => $rootID,              
                            'child_uuid'        => $spaceUUID
                         );
                        $spaceContain->insert($data);
                    }
                    if($fieldNotes != null && strlen($fieldNotes) > 0)
                    {
                        //add notes to val_tab:
                        $valueUUID      = $this->addNotesValueToTable($projectUUID, $dataTableName, $fieldNotes);
                        //add notes to properties and w_val_ids_lookup:
                        $notesPropUUID  = $this->addPropertyToTable($projectUUID, $dataTableName, $valueUUID, 'NOTES', $fieldNum, null);
                        
                        //add notes observation for the $spaceUUID (if it doesn't already exist):
                        if($notesPropUUID != null)
                        {
                            $obsNum         = 1; //hardcode as 1 for now.
                            $obsHashText    = md5($projectUUID . "_" . $spaceUUID . "_" . $obsNum . "_" . $notesPropUUID);
                            $observeRow     = $observe->fetchRow("hash_obs = '" . $obsHashText . "'");
                            if($observeRow == null)
                            {
                                $data = array(
                                    'project_id'   => $projectUUID,
                                    'source_id'          => $dataTableName,
                                    'hash_obs'          => $obsHashText, 
                                    'subject_type'      => 'Locations or Objects',
                                    'subject_uuid'      => $spaceUUID, //reference to the space table.
                                    'obs_num'           => $obsNum,
                                    'property_uuid'     => $notesPropUUID
                                 );
                                $observe->insert($data); 
                            }
                        }
                    }

                    //get all lookup records that are associated with this value:
                    $select = $db->select()
                        ->from  (
                                array('d' => $dataTableName),
                                array('d.id', $fieldName)
                        )
                        ->where($fieldName . ' = ?', $theValue);
                    $LURecs =  $db->query($select)->fetchAll();
                    //echo "LU Records for " . $fieldName . ' = ' . $theValue . ": ";
                    //Zend_Debug::dump($LURecs);
                    foreach($LURecs as $LURec)
                    {
                        $rowNumber = $LURec['id'];
                        $whereLU =  "source_id='" . $dataTableName . "' and uuid='" . $spaceUUID .
                                    "' and field_num =" . $fieldNum . " and row_num = " . $rowNumber;                        
                        $rowLU = $spaceLookup->fetchRow($whereLU);  
                        if($rowLU == null)
                        {
                            $data = array(
                                'source_id'          => $dataTableName,
                                'uuid'        => $spaceUUID, 
                                'field_num'         => $fieldNum,
                                'row_num'           => $rowNumber
                            );
                            $spaceLookup->insert($data);     
                        }    
                    } //end foreach record having $theValue
                } //end if
            } //end foreach distinct field value
        } //end foreach non-spatial containment Locations or Objects record.
    }
    
    
    
    function processObservationsAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $projectUUID    = $_REQUEST['projectUUID'];
        $dataTableName  = $_REQUEST['dataTableName'];        
        
        $observe        = new Table_Observe();  
        $numObsAdded    = 0;
        $returnArray    = array();
        
        $db = Zend_Registry::get('db');        
        $select = $db->select()
            ->distinct()
            // the property:
            ->from  (
                        array('f1' => 'field_summary'),
                        array('f1.fk_field_describes', 'propNum' => 'f1.field_num', 'propName' => 'f1.field_name', 'propLabel' => 'f1.field_label')            
            )
            // the non-property being described:
            ->joinLeft(
                       array('f2' => 'field_summary'),
                       'f2.pk_field = f1.fk_field_describes',
                        array('objNum' => 'f2.field_num', 'objName' => 'f2.field_name', 'objType' => 'f2.field_type', 'objLabel' => 'f2.field_label') 
                )
            ->where ('f1.source_id = ?', $dataTableName)
            ->where ('f1.fk_field_describes is not null')
            ->order ('objName');
        $stmt           = $db->query($select);
        $objFieldRows   = $stmt->fetchAll();
        
        //iterate through the record set of property / non-property mappings,
        //query for data records and corresponding uuIDs and insert into observe
        foreach($objFieldRows as $objFieldRow)
        {
            $numObsAdded = 0;
            $propNum    = $objFieldRow['propNum'];
            $propName   = $objFieldRow['propName'];
            $propLabel  = $objFieldRow['propLabel'];
            $objNum     = $objFieldRow['objNum'];
            $objName    = $objFieldRow['objName'];
            $objType    = $objFieldRow['objType'];
            $objLabel   = $objFieldRow['objLabel'];
            $obsGroup   = 1;
            
            $dataRows = array();
            //Zend_Debug::dump($objType);
            //echo $objType  . ' - ' . $dataTableName . ' - ' . $propNum  . ' - ' . $objNum . '<br />';
            switch($objType)
            {
                //join with space_lookup:
                case "Locations or Objects":
                    $dataRows = $this->getPropertyObjectMappingData($dataTableName, 'space_lookup', 'uuid', $propNum, $objNum);
                    break;
                //join with persons_lookup:
                case "Person":
                    $dataRows = $this->getPropertyObjectMappingData($dataTableName, 'persons_lookup', 'uuid', $propNum, $objNum);
                    //Zend_Debug::dump($dataRows);
                    break;
                //join with resource_lookup:
                case "Media (various)":
                    $dataRows = $this->getPropertyObjectMappingData($dataTableName, 'resource_lookup', 'uuid', $propNum, $objNum);
                    break;
                //join with diary_lookup:
                case "Diary / Narrative":
                    $dataRows = $this->getPropertyObjectMappingData($dataTableName, 'diary_lookup', 'uuid', $propNum, $objNum);
                    break;  
                    
            }
            //echo $objType . ': <br />';
            //Zend_Debug::dump($dataRows);
             
            foreach($dataRows as $dataRow)
            {
                $propUUID   = $dataRow['property_uuid'];
                $objectUUID = $dataRow['objectUUID'];
                $obsHashText = md5($projectUUID . "_" . $objectUUID . "_" . $obsGroup . "_" . $propUUID);
                $observeRow = $observe->fetchRow("hash_obs = '" . $obsHashText . "'");
                if($observeRow == null)
                {
                    $data = array(
                        'project_id'   => $projectUUID,
                        'source_id'          => $dataTableName,
                        'hash_obs'          => $obsHashText, 
                        'subject_type'      => $objType,
                        'subject_uuid'      => $objectUUID,
                        'obs_num'           => $obsGroup,
                        'property_uuid'     => $propUUID
                     );
                    $observe->insert($data);
                    ++$numObsAdded;
                }
            }
            array_push($returnArray, array( 'numValues' => $numObsAdded, 'propertyName' => $propLabel, 'objectName' => $objLabel));
            //array_push($returnArray, $numObsAdded . ' new observations were added for the \'' . $propLabel . '\' -> \'' . $objLabel . '\' mapping.');
        }
        $this->markDataTableAsComplete($projectUUID, $dataTableName);
        echo Zend_Json::encode($returnArray);    
    }
    
    
    private function getPropertyObjectMappingData($dataTableName, $objectTableName, $objectUUIDFieldName, $propNum, $objNum)
    {
        $db = Zend_Registry::get('db'); 
        $select = $db->select()
            ->from (
                array('lu_obj' => $objectTableName),
                array('objectUUID' => 'lu_obj.' . $objectUUIDFieldName)            
            )
            ->join(
                array('lu_prop' => 'w_val_ids_lookup'),
                'lu_obj.source_id = lu_prop.source_id and lu_obj.row_num = lu_prop.row_num',
                array('lu_prop.value_uuid', 'lu_prop.variable_uuid') 
            )
            ->join(
                array('p' => 'properties'),
                'lu_prop.value_uuid = p.value_uuid and lu_prop.variable_uuid = p.variable_uuid',
                array('p.property_uuid') 
            )
            ->where('lu_obj.source_id = ?', $dataTableName)
            ->where('lu_obj.field_num = ?', $objNum)
            ->where('lu_prop.field_num = ?', $propNum);
        //Zend_Debug::dump($db->query($select));
        return $db->query($select)->fetchAll();
    }
    
    
    private function markDataTableAsComplete($projectUUID, $dataTableName)
    {
        $db = Zend_Registry::get('db');
        $result     = $db->fetchOne("select max(process_order) from file_summary where project_id ='" .  $projectUUID. "'");// this is a single string valueecho $result;
        $processNumber  = (($result == null) ? 1 : (((int)$result)+1));
        
        //update the file_summary table:
        $fileSummary    = new Table_FileSummary();        
        $whereClause    = "source_id = '" . $dataTableName . "'";
        $data = array(
            'process_order' => $processNumber,
            'imp_done_timestamp' => new Zend_Db_Expr('NOW()'));
        $fileSummary->update($data, $whereClause);
    }

    

        /************************
     * addNotesValueToTable *
     ************************
     *
     * If the person who imported the data included notes about the field in the
     * 'field_notes' field of the 'table_summary' table, make that note record a
     * value in the value table.
   */
    private function addNotesValueToTable($projectUUID, $dataTableName, $parentFieldNotes)
    {
        $value          = new Table_Value();
        $whereClause = "project_id = '" . $projectUUID . "' and val_text = '" . $parentFieldNotes . "'";
        $valRecord  = $value->fetchRow($whereClause);
        $valueUUID  = "";
        $numval     = null;
        
        //if it's a new value...
        if($valRecord == null)
        {
            $valueUUID  = GenericFunctions::generateUUID();
            $valScram   = md5($parentFieldNotes . $projectUUID);
            
            //insert the value into the val_tab table:
            $data = array(
                'project_id'   => $projectUUID,
                'source_id'          => $dataTableName,
                'text_scram'        => $valScram,
                'val_text'          => $parentFieldNotes,
                'value_uuid'        => $valueUUID,
                'val_num'           => null
             );
            $value->insert($data);
        }
        else
        {
            $valueUUID  = $valRecord->value_uuid;
        }
        return $valueUUID;
    }
    
    /**********************
     * addPropertyToTable *
     **********************
     *
     * This function adds a property to a table (once the variable and value have
     * been defined).
     *
   */
    private function addPropertyToTable($projectUUID, $dataTableName, $valueUUID, $variableUUID, $fieldNum, $numval)
    {        
        $property       = new Table_Property();        
        $valueLookup    = new Table_ValueLookup();
        
        //check to see if there's already an entry in the properties table:
        $whereClause = "project_id = '" . $projectUUID . "' and value_uuid = '" . $valueUUID . "' and variable_uuid = '" . $variableUUID . "'";
        $propertyRecord  = $property->fetchRow($whereClause);
        $propUUID = null;
        if($propertyRecord == null)
        {
            $propHash   = md5($projectUUID . $variableUUID . $valueUUID);
            $propUUID   = GenericFunctions::generateUUID();
            //insert the property into the properties table:
            $data = array(
                'project_id'   => $projectUUID,
                'source_id'          => $dataTableName,
                'prop_hash'         => $propHash,
                'property_uuid'     => $propUUID,
                'variable_uuid'     => $variableUUID,
                'value_uuid'        => $valueUUID,
                'val_num'           => $numval
             );
            $property->insert($data);
            
        }
        else
        {
            $propUUID = $propertyRecord->property_uuid;   
        }
        return $propUUID;
    }
}