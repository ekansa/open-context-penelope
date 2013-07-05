<?php
require_once 'App/Controller/PenelopeController.php';   //handles the functionality common across controllers
require_once 'App/Util/GenericFunctions.php';
ini_set("max_execution_time", "0");
class TransformController extends App_Controller_PenelopeController
{
    public $relationshipInserts = 0;
    public $levels              = 0;
    
     //make sure all connections are UTF-8 OK
    private function setUTFconnection($db){
		  $sql = "SET collation_connection = utf8_unicode_ci;";
		  $db->query($sql, 2);
		  $sql = "SET NAMES utf8;";
		  $db->query($sql, 2);
    }
    
    
    function init()
    {
        Zend_Loader::loadClass('User'); //defined in User.php        
        Zend_Loader::loadClass('Project');
        Zend_Loader::loadClass('Form_Login');
        Zend_Loader::loadClass('Table_Project');
        Zend_Loader::loadClass('Zend_Debug');
        Zend_Loader::loadClass('Zend_Dojo_Data');
        Zend_Loader::loadClass('Form_Upload');
        Zend_Loader::loadClass('Zend_Layout');
        Zend_Loader::loadClass('Layout_Navigation');
        
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
    
    
    function indexAction()
    {
        
        parent::indexAction();
        $this->view->title = "Data Importer";
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
        $db->delete('diary',          "source_id = '" . $dataTableName . "'");
        $db->delete('diary_lookup',   "source_id = '" . $dataTableName . "'");
		  $db->delete('diary_lookup',   "source_id != '" . $dataTableName . "'");
        $db->delete('resource',       "source_id = '" . $dataTableName . "'");
        $db->delete('resource_lookup',"source_id = '" . $dataTableName . "'");
        $db->delete('resource_lookup',"source_id != '" . $dataTableName . "'");
        $db->delete('space_lookup',   "source_id != '" . $dataTableName . "'");
        
        //update field summary table so that prefixes for location and objects are
        //don't have instructions added
        $data = array("field_lab_com" => null);
        $where[] = "source_id = '" . $dataTableName . "'";
        $where[] = "field_lab_com = '<em>click to edit...</em>' ";
        $n = $db->update('field_summary', $data, $where);
        
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
    
    function hasTableBeenProcessedAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $dataTableName   = $_REQUEST['dataTableName'];
        
        Zend_Loader::loadClass('Table_FileSummary');        
        $fileSummary = new Table_FileSummary();
        $row = $fileSummary->fetchRow("source_id = '" . $dataTableName . "'");
        if($row->process_order == null)
            echo "false";
        else
            echo "true";
    }
    
    /***********/
    function getProcessedTablesAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $projectUUID    = $_REQUEST['projectUUID'];
        $db = Zend_Registry::get('db');        
        $this->setUTFconnection($db);
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
    
            
    
    
    private function noteClean($string){
        
        //$output = md5($string);
        /*
        $output = str_replace("'", " ", $string);
        $output = str_replace('"', " ", $output);
        $output = str_replace('/', " ", $output);
        $output = str_replace('=', " ", $output);
        $output = str_replace('(', " ", $output);
        $output = str_replace(')', " ", $output);
        $output = str_replace('-', " ", $output);
        */
        $output = substr($string, 0,342);
        
        $output = $string;
        return $output;
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
        $this->setUTFconnection($db);
        //Get all of $dataTableName's variables in the field_summary table:        
        //retrieve the file_summary record:
        $fileSummary    = new Table_FileSummary();
        $fileSummaryRow = $fileSummary->fetchRow("source_id = '" . $dataTableName . "'");

        //retrieve the associated field_summary records that are designated as 'Property':
        $fieldSummary    = new Table_FieldSummary();
        $select = $fieldSummary->select()->where('field_type = ?', 'Property')->order("field_num ASC");
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
            $varNotes   = $this->noteClean($fieldRow->prop_desc);
            //echo $fieldLabel."<br/>".$varNotes."<br/><br/><br/>";
            $fieldNum   = $fieldRow->field_num;
            $varhash    = md5($projectUUID . $fieldLabel . $propType);
            
            //check to see if record exists before inserting:
            $variable       = new Table_Variable();
            $variableNotes  = new Table_VariableNotes();

            $whereClause = "project_id = '" . $projectUUID . "' and var_label = '" . $fieldLabel. "'";
            //echo $whereClause;
            //echo "<br/> HM!";
            
            @$varRecord  = $variable->fetchRow($whereClause);
            if(!$varRecord){
                //echo $whereClause;
                //echo print_r($varRecord);
                //break;
            }

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
                try{
                    $variable->insert($data);
                } catch (Exception $e) {
                    
                }
                
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
                    $data = array('variable_uuid' => $varUUID
                                  );
                    $whereUP = array();
                    $whereUP[] = "source_id = '$dataTableName'";
                    $whereUP[] = "field_label = '$fieldLabel'";
                    $db = Zend_Registry::get('db');
                    $n = $db->update('field_summary', $data, $whereUP);
                    
                }
                else
                {
                    //determine which variable *type* overrides
                    $oldFileSummary     = new Table_FileSummary();
                    $oldFileSummaryRow  = $oldFileSummary->fetchRow("source_id = '" . $varRecord->source_id . "'");
                    @$recordCountNew     = $oldFileSummaryRow->numrows;
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
                    
                    $data = array('variable_uuid' => $varUUID
                                  );
                    $whereUP = array();
                    $whereUP[] = "source_id = '$dataTableName'";
                    $whereUP[] = "field_label = '$fieldLabel'";
                    $db = Zend_Registry::get('db');
                    $n = $db->update('field_summary', $data, $whereUP);
                    
                }
            }
            //Now add var_notes:
            //echo $varNotes;
            if($varNotes != null && strlen($varNotes) > 0)
            {
                $qVarNotes = substr($varNotes,0,200);
				$qVarNotes = addslashes($qVarNotes);
				$whereClause = "project_id = '" . $projectUUID . "' and note_text LIKE '" . $qVarNotes. "%' and variable_uuid='" . $varUUID . "'";
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
                    //$variableNotes->insert($data);
                    try{
                        $db->insert("var_notes", $data);
                    } catch (Exception $e) {
                        
                    }
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
    
    
    //this returns an array of the property field numbers
    function getPropFieldsAction(){
        
        $this->_helper->viewRenderer->setNoRender();
        $dataTableName = $_REQUEST['dataTableName'];
        $db = Zend_Registry::get('db');
        $this->setUTFconnection($db);
        
        //first make darn sure all the data for this table is gone
        $db->delete('val_tab',        "source_id = '" . $dataTableName . "'");
        $db->delete('properties',     "source_id = '" . $dataTableName . "'");
        //$db->delete('var_tab',     "source_id = '" . $dataTableName . "'");
            
        /*
        $stmt = $db->query(
            'OPTIMIZE TABLE val_tab'
        );
        $stmt = $db->query(
            'OPTIMIZE TABLE properties'
        );
        */

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
        //Zend_Debug::dump($select);
        
        $stmt = $db->query($select);
        //Zend_Debug::dump($stmt);
        $fieldRows = $stmt->fetchAll();
        $fieldArray = array();
        foreach($fieldRows as $row) //foreach property field in the table
        {
            $fieldNumber    = $row['field_num'];
            $fieldArray[] = $fieldNumber;
        }
    
    
        //this looks for and processes properties where
        //variables and values are in two fields
        $this->duo_field_do($dataTableName);
        
        echo Zend_json::encode($fieldArray);
    }
    
	 
	 //checks to see if a date is valid
	 private function dateValidate($value){
		  $cal_test_string = str_replace("/", "-", $value);
		  if (($timestamp = strtotime($cal_test_string)) === false) {
				return false;
		  }
		  else{
				return date("Y-m-d H:i:s", strtotime($cal_test_string)); //mysql formatted date
		  }
	 }
	 
	 private function isCalendarVar($variableUUID, $db){
		 $output = false;
		 $sql = "SELECT var_type FROM var_tab WHERE variable_uuid = '$variableUUID' LIMIT 1";
		 $result = $db->fetchAll($sql, 2);
		 if($result){
				if(stristr($result[0]["var_type"], "calend")){
					 $output = true;
				}
		 }
		 return $output;
	 }
	 
    
    function transformValuesAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $dataTableName = $_REQUEST['dataTableName'];
        $activeField = $_REQUEST['field'];
        
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
        $this->setUTFconnection($db);
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
        ->where ("s.source_id = '" . $dataTableName . "'")
        ->where ("s.field_num = ".$activeField);
        
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
        
        $DoneValArray = array();
        //echo "done!";
        
        foreach($fieldRows as $row) //foreach property field in the table
        {
            $fieldNumber    = $row['field_num'];
            $fieldName      = $row['field_name'];
            $fieldLabel     = $row['field_label'];
            $variableUUID   = $row['variable_uuid'];
            
            //check for missing variableUUIDs
            $variableUUID = $this->checkVarID($variableUUID, $fieldLabel, $projectUUID, $db);
            $calendVar = $this->isCalendarVar($variableUUID, $db);
            
            //querying the records associated with the "fieldName" property (one query per property):
            $select = $db->select()
                ->distinct()
                ->from( array('t' => $dataTableName), array('t.' . $fieldName));
                //->group('t.' . $fieldName);
                //->limit($startRec, $limitRec);
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
                    
                    //$theText = utf8_encode($theText);
                    
                    //note: this is a hack for text with single quotes, which is not well liked by Zend DB. May want to replace
                    //the code for inserts with a normal SQL insert to get around this.
                    $theText = str_replace("'", '"', $theText);
                    //$whereClause = "project_id = '" . $projectUUID . "' and val_text = '" . $theText . "'";
                    
                    //SV: to escape apostrophes in the raw data, use "quoteInto
                    //$whereClause =    $db->quoteInto('project_id = ?', $projectUUID)
                    //                . $db->quoteInto('AND val_text = ?', $theText);

                    $valDate = false;
						  if($calendVar){
								$valDate = $this->dateValidate($theText);
						  }
						  
                    $valScram   = md5($theText . $projectUUID);
                        
                    $whereClause = "text_scram = '" . $valScram . "'";
                    $valRecord  = $value->fetchRow($whereClause);
                    //$valueUUID  = "";
                    $numval     = null;
                    
                    if($valRecord == null){
                        sleep(.05);
                        $valRecord  = $value->fetchRow($whereClause);
                    }
                    
                    //if it's a new value, insert into 'val_tab'...
                    if($valRecord == null)
                    {
                        
                        if(array_key_exists($valScram, $DoneValArray)){
                            $act_val = $DoneValArray[$valScram];
                            $valueUUID = $act_val["id"];
                            $numval = $act_val["num"];
                        }//value record exists, even though it was not found
                        else{
                            $valueUUID  = GenericFunctions::generateUUID();
                            
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
                            
                            $act_val = array("id"=> $valueUUID, "num"=>$numval);
                            $DoneValArray[$valScram] = $act_val;
                            
                        }//make new value record!!
                    }
                    //if it's not a new value, no need to insert, but populate local variables:
                    else
                    {
                        if(array_key_exists($valScram, $DoneValArray)){
                            $act_val = $DoneValArray[$valScram];
                            $valueUUID = $act_val["id"];
                            $numval = $act_val["num"];
                        }
                        else{    
                            $valueUUID  = $valRecord->value_uuid;
                            $numval     = $valRecord->val_num;
                            $act_val = array("id"=> $valueUUID, "num"=>$numval);
                            $DoneValArray[$valScram] = $act_val;
                        }
                    }//end if
                    
                    
                    
                    
                    
                    
                    //check to see if there's already an entry in the properties table:
                    //$whereClause = "project_id = '" . $projectUUID . "' and value_uuid = '" . $valueUUID . "' and variable_uuid = '" . $variableUUID . "'";
                    $propHash   = md5($projectUUID . $variableUUID . $valueUUID);
                    $whereClause = "prop_hash = '" . $propHash . "'";
                    
                    $propertyRecord  = $property->fetchRow($whereClause);
                    
                    //Zend_Debug::dump($propertyRecord);
                    
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
                            'val_num'           => $numval,
									 'val_date'				=> $valDate
                         );
                        $property->insert($data);                       
                    }
                    
                    
                        // get all of the other records in $dataTableName with the same value
                        // as that which is associated with $valueUUID.  Insert all of these
                        // records into the w_val_ids_lookup table:
                    
                   
                    //can't query for too long text
                    if(strlen($theText)<200){
                        $longTextChecking = false;
                        
                         $select = $db->select()
                                ->distinct()
                                ->from(
                                        array('t' => $dataTableName),
                                        array('t.id', 't.' . $fieldName)
                                )
                                ->where('t.' . $fieldName . ' = ?', $theText);
                        $lookupRows = $db->query($select)->fetchAll();
                    }
                    else{
                        $longTextChecking = true;
                        
                        $sql = "SELECT t.id, t.$fieldName
                        FROM $dataTableName AS t
                        WHERE t.$fieldName LIKE '".addslashes(substr($theText,0,200))."%'
                        ";
                        
                        $lookupRows = $db->fetchAll($sql, 2);    
                    }
                   
						 
                    foreach($lookupRows as $lookupRow){
								$insertTextLookUp = true;
								$fieldID = $lookupRow['id'];
								$checkText = $lookupRow[$fieldName];
								
								if($longTextChecking){
									 if(sha1(trim($checkText)) != sha1(trim($theText))){
										  $insertTextLookUp = false;
										  //the text is not exactly the same
									 }
								}
								
								//echo $checkText.sha1(trim($checkText));
								//echo $theText.sha1(trim($theText));
								//die;
								//echo "<br/>Checking on text sameness: ".$insertTextLookUp." for '".substr($checkText,0,200)."' and '".substr($theText,0,200)."' ";
								
								//check for missing variableUUIDs
								$variableUUID = $this->checkVarID($variableUUID, $fieldLabel, $projectUUID, $db);
								
								
								$data = array(
									 'source_id'          => $dataTableName,
									 'variable_uuid'     => $variableUUID,
									 'value_uuid'        => $valueUUID,
									 'field_num'         => $fieldNumber,
									 'row_num'           => $fieldID
								 );
								
								if($insertTextLookUp){ //only add if the lookup text is exactly the same as the text. Important for long text
									 try{
										  $valueLookup->insert($data);
									 } catch (Exception $e) {
									 
									 }
								}
						  }//end loop through lookups
						  //die;
                      
						  if(!$lookupRows){
								$this->val_lookup_alt($dataTableName, $fieldNumber, $fieldName, $variableUUID, $valueUUID, $theText);
								//echo "<br/><br/>";
						  }
						  
						  ++$cntProperties;
                    
                    
                } //end if value not null
            } //end foreach value
            array_push($returnArray, array( 'property' => $fieldLabel, 'numValues' => $cntValues, 'numProperties' => $cntProperties));
        } //end foreach property
        echo Zend_Json::encode($returnArray);
    }
    
    //this function has more relaxed lookups of values for filling the lookup table
    private function val_lookup_alt($dataTableName, $fieldNumber, $fieldName, $variableUUID, $valueUUID, $theText){
        
        $db = Zend_Registry::get('db');
        $this->setUTFconnection($db);
       
        $qText = addslashes(substr($theText,0,200));
       
       
        $sql = "SELECT $dataTableName.id
        FROM $dataTableName
        WHERE $dataTableName.$fieldName LIKE '%$qText%'
        ";
        
        //echo $sql;
        
                        //$lookupRows = $db->query($select)->fetchAll();
                        
        $lookupRows = $db->fetchAll($sql, 2);
        foreach($lookupRows as $lookupRow){
                            $fieldID = $lookupRow['id'];
                            
                            if(strlen($variableUUID)<2){
                                $sql = "SELECT field_summary.variable_uuid
                                FROM field_summary
                                WHERE field_summary.source_id = '$dataTableName'
                                AND field_summary.field_num = $fieldNumber
                                ";
                                
                                $result = $db->fetchAll($sql, 2);
                                if($result){
                                    $variableUUID = $result[0]["variable_uuid"];
                                }
                                
                            }
                            
                            $data = array(
                                'source_id'          => $dataTableName,
                                'variable_uuid'     => $variableUUID,
                                'value_uuid'        => $valueUUID,
                                'field_num'         => $fieldNumber,
                                'row_num'           => $fieldID
                             );
                            
                            try{
                                $db->insert('w_val_ids_lookup', $data);
                            } catch (Exception $e) {
                            
                            }
        }

    }//end private function
    
    
    
    //checks if the variableUUID is missing. If it is, then find it or make a new one!
    private function checkVarID($variableUUID, $fieldLabel, $projectUUID, $db){
        
        if(strlen($variableUUID)<2){
                
                $fieldLabel = trim($fieldLabel);
                
                $sql = "SELECT var_tab.variable_uuid
                FROM var_tab
                WHERE var_tab.var_label LIKE '$fieldLabel'
                AND var_tab.project_id = '$projectUUID'
                LIMIT 1
                ";
                
                $result = $db->fetchAll($sql, 2);
                if($result){
                    $variableUUID = $result[0]["variable_uuid"];
                    
                    if(strlen($variableUUID)<2){
                        //add a variable UUID to the stupid blank
                        
                        $variableUUID = $this->tabFieldsVarID($fieldLabel, $projectUUID, $db, true);
                        $where = array();
                        $where[] = "project_id  = '".$projectUUID."' ";
                        $where[] = "var_label LIKE '".$fieldLabel."' ";
                        $data = array("variable_uuid" => $variableUUID);
                        $db->update('var_tab', $data, $where);
                    }//end update of blank variable_uuid
                }//end retrieval of missing variable_uuid
                
        }
        else{
            
            //now check to see if the variable_id actually corresponds to a variable in the variable tab
            //if not, update the variable id to look like 
            
            $sql = "SELECT var_tab.variable_uuid
                FROM var_tab
                WHERE var_tab.variable_uuid =  '$variableUUID'
                AND var_tab.project_id = '$projectUUID'
                LIMIT 1
                ";
                
            $result = $db->fetchAll($sql, 2);
            if($result){
            
            }
            else{
                
                $sql = "SELECT var_tab.variable_uuid
                FROM var_tab
                WHERE var_tab.var_label LIKE '$fieldLabel'
                AND var_tab.project_id = '$projectUUID'
                LIMIT 1
                ";
                
                $result = $db->fetchAll($sql, 2);
                if($result){
                    $variableUUID = $result[0]["variable_uuid"];
                }
                
            }
            
        }
        

        return $variableUUID;
    }//end function checking if variableUUID is OK
    
    
    //check to see if a variableUUID is available for this fieldlabel
    private function tabFieldsVarID($fieldLabel, $projectUUID, $db, $makeNewVar = false){
        
        $sql = "SELECT field_summary.variable_uuid
                FROM field_summary
                WHERE field_summary.field_label LIKE '$fieldLabel'
                AND field_summary.project_id = '$projectUUID'
                LIMIT 1
                ";
                
        $result = $db->fetchAll($sql, 2);
        if($result){
            $variableUUID = $result[0]["variable_uuid"];
            if((strlen($variableUUID)<2) && $makeNewVar){
                //add a variable UUID to the stupid blank
                $variableUUID   = GenericFunctions::generateUUID();
                $where = array();
                $where[] = "project_id  = '".$projectUUID."' ";
                $where[] = "field_label LIKE '".$fieldLabel."' ";
                $data = array("variable_uuid" => $variableUUID);
                $db->update('field_summary', $data, $where);
            }//end update of blank variable_uuid
        }
        
        return $variableUUID;
    }
    
    
    
    
    
    /*
    Sometimes a data table will have properties (variables and values in two fields)
    This processes a variable field and a related value field so they can
    have properties
    */
    private function duo_field_do($dataTableName, $projectUUID = false){
        $db = Zend_Registry::get('db');
        $this->setUTFconnection($db);
        
        if(!$projectUUID){
            $sql = "SELECT file_summary.project_id AS projectUUID
            FROM file_summary
            WHERE file_summary.source_id = '$dataTableName'
            LIMIT 1";
            
            //echo $sql ;
            
            $result = $db->fetchAll($sql, 2);
            $projectUUID = $result[0]["projectUUID"];
        }
        
        
        $sql = "SELECT  field_summary.field_num AS var_fnum,
                    field_summary.fk_field_describes AS var_fdesc,
                    val_field.field_num AS val_fnum
                FROM field_summary
                JOIN field_summary AS val_field
                    ON (field_summary.source_id = val_field.source_id
                    AND field_summary.pk_field = val_field.fk_field_describes
                    AND val_field.field_type = 'Value')
                WHERE field_summary.source_id = '$dataTableName'
                AND field_summary.project_id = '$projectUUID'
                AND field_summary.field_type = 'Variable'
                ";
        
        //echo $sql ;
                
        $VarValFields = $db->fetchAll($sql, 2);
        if($VarValFields){
            foreach($VarValFields AS $ActVarVal){
                $var_fieldNum = $ActVarVal["var_fnum"]; //field number for the variable in prop with var/vals in 2 fields
                $val_fieldNum = $ActVarVal["val_fnum"]; //field number for the value in prop with var/vals in 2 fields
                $var_fieldDes = $ActVarVal["var_fdesc"]; //field number for the field described by a prop, in prop with var/vals in 2 fields
                
                $var_fieldName = "field_".$var_fieldNum;
                $val_fieldName = "field_".$val_fieldNum;
                
                //get unique variables
                $sql = "SELECT DISTINCT
                    $dataTableName.$var_fieldName as var_field
                FROM $dataTableName
                WHERE CHAR_LENGTH($dataTableName.$var_fieldName)>0
                AND CHAR_LENGTH($dataTableName.$val_fieldName)>0
                ";
                
                //echo $sql."<br/><br/>" ;
                $VarValRows = $db->fetchAll($sql);
                
                foreach($VarValRows as $ActVarVal){
                    
                    $act_var = $ActVarVal["var_field"];
                    $act_var = trim($act_var);
                    $act_varType = $this->duo_field_varClassify($act_var, $var_fieldName, $val_fieldName, $dataTableName);
                    $act_varID = $this->duo_field_getVarId($act_var, $act_varType, $dataTableName, $projectUUID);
                    //echo $act_var." (".$act_varID.") is ".$act_varType."<br/>";
                    $this->duo_field_valueDo($act_varID, $act_var, $var_fieldNum, $var_fieldName, $val_fieldName, $dataTableName, $projectUUID);
                    
                }//end loop through the individual variables

            }//end loop through all variable fields
        }//end case with var_val_fields
    }
    
    //this processes values and properties in properties where two fields make a var/val pair
    private function duo_field_valueDo($act_varID, $act_var, $var_fieldNum, $var_fieldName, $val_fieldName, $dataTableName, $projectUUID){
        $db = Zend_Registry::get('db');
        $act_var = addslashes($act_var);
		  
        $sql = "SELECT DISTINCT $dataTableName.$val_fieldName AS val
        FROM $dataTableName
        WHERE $dataTableName.$var_fieldName = '$act_var'
        AND CHAR_LENGTH($dataTableName.$val_fieldName)>0
        ";
        
        $VarValRows = $db->fetchAll($sql, 2);
        $uniqueValCount = count($VarValRows);
        //$valArray = array();
        foreach($VarValRows as $ActVarVal){
            
            $act_val = $ActVarVal["val"];
            $act_val = trim($act_val);
			$act_val = addslashes($act_val);
            $act_valID = $this->duo_field_getValId($act_val, $dataTableName, $projectUUID);
            $act_propID = $this->duo_field_getPropId($act_val, $act_valID, $act_varID, $dataTableName, $projectUUID);
            
            //add ids to array, so we can use for look up table
            //$valArray[] = array("act_val"=>$act_val, "act_valID"=>$act_valID, "act_propID"=>$act_propID); 
            
            //echo "<br/>".$act_val." ValID:".$act_valID;
            
            $this->duo_field_lookupSet($act_var, $act_val, $act_varID, $act_valID, $var_fieldNum, $var_fieldName, $val_fieldName, $dataTableName);
        }//end loop through values
        
        /*
        foreach($valArray as $activeValue){
            $act_val = $activeValue["act_val"];    
            $act_valID = $activeValue["act_valID"];
            $act_propID = $activeValue["act_propID"];
            
        }//end loop through 
        */
        
    }

    //this function adds data to the value loopkup table where two fields make a var/val pair
    private function duo_field_lookupSet($act_var, $act_val, $act_varID, $act_valID, $var_fieldNum, $var_fieldName, $val_fieldName, $dataTableName){
        $db = Zend_Registry::get('db');
        $this->setUTFconnection($db);
        
        $sql = "SELECT $dataTableName.id
        FROM $dataTableName
        WHERE $dataTableName.$var_fieldName = '$act_var'
        AND $dataTableName.$val_fieldName = '$act_val'
        ";
        
        //echo $sql;
        
        $VarValRows = $db->fetchAll($sql, 2);
        foreach($VarValRows as $ActVarVal){
            $act_ID = $ActVarVal["id"];
            $data = array("source_id" => $dataTableName,
                          "variable_uuid" => $act_varID,
                          "value_uuid" => $act_valID,
                          "field_num" => $var_fieldNum,
                          "row_num" => $act_ID
                          );
            try{
                $db->insert('w_val_ids_lookup', $data);
            }
            catch (Exception $e) {
                        
            }
        }//end loop through lookup inserts
        
    }
    
    
    //this classifies variables in properties where two fields make a var/val pair
    private function duo_field_varClassify($act_var, $var_fieldName, $val_fieldName, $dataTableName){
        
        $db = Zend_Registry::get('db');
        $this->setUTFconnection($db);
        $act_var = addslashes($act_var);
		  
        $sql = "SELECT $dataTableName.$val_fieldName AS val
        FROM $dataTableName
        WHERE $dataTableName.$var_fieldName = '$act_var'
        AND CHAR_LENGTH($dataTableName.$val_fieldName)>0
        ";
        
        $AllVarValRows = $db->fetchAll($sql, 2);
        $AllValCount = count($AllVarValRows);
        
        
        $sql = "SELECT DISTINCT $dataTableName.$val_fieldName AS val
        FROM $dataTableName
        WHERE $dataTableName.$var_fieldName = '$act_var'
         AND CHAR_LENGTH($dataTableName.$val_fieldName)>0
        ";
        
        $VarValRows = $db->fetchAll($sql, 2);
        $uniqueValCount = count($VarValRows);
        $isDecimal = true; //until there's a counter example
        $isInteger = true; //until there's a counter example
        
        foreach($VarValRows as $ActVarVal){
            $act_val = $ActVarVal["val"];
                    
            if(!is_numeric($act_val)){
                $isDecimal = false;
            }
            else{
                if(($act_val+0) != round(($act_val+0),0)){
                    $isInteger = false;
                }
            }
                        
        }//end loop through values
        
        $varType = "Nominal"; //default
        if($uniqueValCount>10){
            $varType = "Alphanumeric"; 
            if( ($uniqueValCount/$AllValCount) <= .5){
                $varType = "Nominal"; //mostly the same text
            }
        }//lots of text values   
                    
        if($isDecimal){
            $varType = "Decimal"; //numeric data
        }                  
        if($isInteger){
            $varType = "Integer"; //integer data
        }

        return $varType;
    }
    
    
    //get or make a new variable id for properties where var/vals are in two fields
    private function duo_field_getVarId($act_var, $act_varType, $dataTableName, $projectUUID){
        
        $db = Zend_Registry::get('db');
        $this->setUTFconnection($db);
        $act_var = addslashes($act_var);
		  
        $sql = "SELECT var_tab.variable_uuid
        FROM var_tab
        WHERE var_tab.var_label = '$act_var'
        AND var_tab.project_id = '$projectUUID'
        LIMIT 1
        ";
        
        $VarIDRow = $db->fetchAll($sql, 2);
        if($VarIDRow){
            $varUUID = $VarIDRow[0]['variable_uuid']; //record already exists
        }
        else{ //create a new variable record
            $varUUID   = GenericFunctions::generateUUID();
            $varhash    = md5($projectUUID . $act_var . $act_varType);
            $data = array("project_id" => $projectUUID,
                          "source_id" => $dataTableName,
                          "var_hash" => $varhash,
                          "variable_uuid" => $varUUID,
                          "var_label" => $act_var,
                          "var_type" => $act_varType);
				try{
					 $db->insert('var_tab', $data);
				}
            catch (Exception $e) {
                        
            }
        }
        
        return $varUUID;
    }
    
    
    //get or make a new value id for properties where var/vals are in two fields
    private function duo_field_getValId($act_val, $dataTableName, $projectUUID){
        
        $db = Zend_Registry::get('db');
        $this->setUTFconnection($db);
        
        $num_val = false;
        $ValNumCond = "";
        
        if(is_numeric($act_val)){
            $num_val = $act_val + 0;
            $ValNumCond = " OR val_tab.val_num = ".$num_val;
        }
        
        $valScram   = md5($act_val . $projectUUID);
        
        /*
        $sql = "SELECT val_tab.value_uuid
            FROM val_tab
            WHERE val_tab.project_id = '$projectUUID' AND (val_tab.val_text = '$act_val' $ValNumCond)
            LIMIT 1
            ";
        */
        
        $sql = "SELECT val_tab.value_uuid
        FROM val_tab
        WHERE val_tab.text_scram = '$valScram'
        LIMIT 1
        ";
        
        //echo $sql."<br/><br/>";
        //$valUUID = "blubby";
        
        $ValIDRow = $db->fetchAll($sql, 2);
        if($ValIDRow){
            $valUUID = $ValIDRow[0]['value_uuid']; //record already exists
        }
        else{
        
                //create a new variable record
                $valUUID   = GenericFunctions::generateUUID();
                $valScram   = md5($act_val . $projectUUID);
                $data = array("project_id" => $projectUUID,
                              "source_id" => $dataTableName,
                              "text_scram" => $valScram,
                              "val_text" => $act_val,
                              "value_uuid" => $valUUID);
                
                if($num_val != false){
                    $data["val_num"] =  $num_val;
                }
                
                $db->insert('val_tab', $data);
            
        }
        
        return $valUUID;
    }
    
    
    //get or make a new value id for properties where var/vals are in two fields
    private function duo_field_getPropId($act_val, $act_valID, $act_varID, $dataTableName, $projectUUID){
        
        $db = Zend_Registry::get('db');
        $this->setUTFconnection($db);
        
        $sql = "SELECT properties.property_uuid
        FROM properties
        WHERE properties.variable_uuid = '$act_varID'
        AND properties.value_uuid = '$act_valID'
        AND properties.project_id = '$projectUUID'
        LIMIT 1
        ";
        
        $PropIDRow = $db->fetchAll($sql, 2);
        if($PropIDRow){
            $propUUID = $PropIDRow[0]['property_uuid']; //record already exists
        }
        else{ //create a new variable record
            $propUUID   = GenericFunctions::generateUUID();
            $propHash   = md5($projectUUID . $act_varID . $act_valID);
            $data = array("project_id" => $projectUUID,
                          "source_id" => $dataTableName,
                          "prop_hash" => $propHash,
                          "property_uuid" => $propUUID,
                          "variable_uuid" => $act_varID,
                          "value_uuid" => $act_valID);
            
            if(is_numeric($act_val)){
                $data["val_num"] =  $act_val + 0;
            }
            
            $db->insert('properties', $data);    
        }
        
        return $propUUID;
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
        
        $doSpaceContain = $this->spatial_noContain_check($dataTableName);
        //$doSpaceContain = true;
        
        //$doSpaceContain = false;
        
        if($doSpaceContain){
            //echo "here";
            $stateManager = new SpatialContainment_StateManager(true);
            $stateManager->initTransformSpatialContainment($dataTableName);
            $stateManager->clearReturnMessage();
            $stateManager->setHasEchoed(false);
        }
        else{
            //echo "here";
            //no spatial containment relations, in this case just match labels of spatial items
            $idCount = $this->spatial_noContain_do($dataTableName);
            //echo  '["Total number of records processed: '.$idCount.'",""]';
            $returnArray = array();
            //echo "here";
            echo Zend_Json::encode($returnArray);
        }
    
    }

    
    private function spatial_noContain_check($dataTableName){
        $db = Zend_Registry::get('db');
        $this->setUTFconnection($db);
        $sql = "SELECT field_links.field_parent_name
        FROM field_links
        WHERE field_links.fk_link_type = '1'
        AND field_links.source_id = '$dataTableName'
        ";
        
        //echo $sql;
        
        $ContainRows = $db->fetchAll($sql, 2);
        if($ContainRows){
            //echo $sql;
            return true;
        }
        else{ //no spatial containment
            //do alternative method, which is match name of spatial items
            return false;
        }
        
    }
    
    
    //process spatial items with no cotainment hierarchy
    //no new spatial items are created, matches on class and item label
    private function spatial_noContain_do($dataTableName, $projectUUID = false){
        $db = Zend_Registry::get('db');
        $this->setUTFconnection($db);
        if(!$projectUUID){
            $sql = "SELECT file_summary.project_id AS projectUUID
            FROM file_summary
            WHERE file_summary.source_id = '$dataTableName'
            LIMIT 1";
            
            //echo $sql ;
            
            $result = $db->fetchAll($sql, 2);
            $projectUUID = $result[0]["projectUUID"];
        }
        
        $sql = "SELECT field_summary.field_num,
        field_summary.field_name,
        field_summary.field_lab_com,
        field_summary.fk_class_uuid
        FROM field_summary
        WHERE field_summary.field_type = 'Locations or Objects'
        AND field_summary.source_id = '$dataTableName'
        ";
        
        $idCount = 0;
        $SpatialRows = $db->fetchAll($sql, 2);
        if($SpatialRows){
            foreach($SpatialRows as $actSpatial){
                
                $act_fieldNum = $actSpatial["field_num"];
                $act_fieldName = $actSpatial["field_name"];
                $act_fieldLabel = $actSpatial["field_lab_com"];
                $act_fieldClass = $actSpatial["fk_class_uuid"];
                if(strlen($act_fieldLabel)<1){
                    $act_fieldLabel = "";
                }
                else{
                    $act_fieldLabel .= " ";
                }
                $idCount = $this->spatial_noContain_idMap($act_fieldNum, $act_fieldName, $act_fieldLabel, $act_fieldClass, $dataTableName, $projectUUID);
                
            }//end loop through spatial fields
        }
        
        return $idCount;
    }
    
    
    private function spatial_noContain_idMap($act_fieldNum, $act_fieldName, $act_fieldLabel, $act_fieldClass, $dataTableName, $projectUUID){
        
        $idCount = 0;
        $db = Zend_Registry::get('db');
		  $this->setUTFconnection($db);
        $sql = "SELECT DISTINCT $dataTableName.$act_fieldName AS spfield
        FROM $dataTableName
        WHERE CHAR_LENGTH($dataTableName.$act_fieldName) > 0
        ";
        
        //echo $sql;
        $SpatialRows = $db->fetchAll($sql, 2);
        foreach($SpatialRows as $actSpatial){
            
            $spatialRaw = $actSpatial["spfield"];
            $spatialName = $act_fieldLabel.(trim($spatialRaw)); //add label prefix
            $spatialName = str_replace($act_fieldLabel.$act_fieldLabel, $act_fieldLabel, $spatialName);
            
            
            $sql = "SELECT space.uuid
            FROM space
            WHERE space.project_id = '$projectUUID'
            AND space.class_uuid = '$act_fieldClass'
            AND space.space_label LIKE '$spatialName'
            LIMIT 1
            ";
            
            $sql = "SELECT space.uuid
            FROM space
            WHERE space.project_id = '$projectUUID'
            AND space.space_label LIKE '$spatialName'
            LIMIT 1
            ";
            
            //echo $sql."<br/><br/>";
            
            $SpatialIDs = $db->fetchAll($sql, 2);
            //$SpatialIDs = false;
            if($SpatialIDs){
                
                $act_uuid = $SpatialIDs[0]["uuid"];
                $idCount ++;
                        
                $sql = "SELECT $dataTableName.id AS rowID
                FROM $dataTableName
                WHERE $dataTableName.$act_fieldName = '$spatialRaw'
                ";
                
                $SpatialRowIDs = $db->fetchAll($sql, 2);
                foreach($SpatialRowIDs as $actRow){
                    $actRowID = $actRow["rowID"];
                    
                    $data = array("source_id"=> $dataTableName,
                                  "uuid"=> $act_uuid,
                                  "field_num"=> $act_fieldNum,
                                  "row_num"=> $actRowID);
                    
                    $db->insert('space_lookup', $data); 
                    
                }//end loop through row ids
                
            }
            
        }//end loop through spatial items
        
        return $idCount;
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
    
    
    //this function gets a title for a given media file 
    private function getmediaTile($dataTableName, $mediaFieldNum, $recordNum, $resUUID){
         $db = Zend_Registry::get('db');
			$this->setUTFconnection($db);
         $select = $db->select()
            ->distinct()
            ->from  (
                        array('f' => 'field_summary'),
                        array('f.field_name', 'f.field_num', 'f.field_type', 'f.field_label')
            )
            ->where("f.source_id = ?", $dataTableName)
            ->where("f.field_type = 'Media (title)'")
            ->where("f.fk_field_describes = $mediaFieldNum");
        $stmt = $db->query($select);
        $fieldRec = $stmt->fetchAll();
        $output = false;
        if(count($fieldRec)>0){
            
            $titleFieldNum = $fieldRec[0]["field_num"]; // this is the field where the title info exists
            $titleField = "field_".$titleFieldNum;
            
            $select = $db->select()
            ->distinct()
            ->from  (
                        array('t' => $dataTableName),
                        array('t.'.$titleField)
            )
            ->where("t.id = ?", $recordNum);
            $stmt = $db->query($select);
            $tableRecs = $stmt->fetchAll();
            foreach($tableRecs as $actRec){
                $actTitle = $actRec[$titleField];
                
                $data = array('res_label'=> $actTitle);
                $where = 'uuid = "'.$resUUID.'"';
                $n = $db->update('resource', $data, $where);
                $output = $actTitle;
            }
        }
        else{
            $output = false;
        }
        return $output;
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
                        array('f.field_name', 'f.field_num',
                              'f.field_type', 'f.field_label',
                              'p.thumb_root', 'p.prev_root',
                              'p.full_root', 'p.doc_root')
            )
            ->join(
                        array('p'=>'project_list'),
                        'f.project_id = p.project_id'
            )
            ->where("f.source_id = ?", $dataTableName)
            ->where("f.field_type = '" . App_Constants::MEDIA . "' or f.field_type = '" . App_Constants::DIARY . "'");
        $stmt = $db->query($select);
        //echo $select->__toString();
        
        $fieldRecs = $stmt->fetchAll();
        //Zend_Debug::dump($fieldRecs);
        foreach($fieldRecs as $fieldRec)
        {
            $numRecsInserted= 0;
            $fieldName      = $fieldRec['field_name'];
            $fieldNum       = $fieldRec['field_num'];
            $fieldLabel     = $fieldRec['field_label'];
            $fieldType      = $fieldRec['field_type'];
            $ThumbRoot      = $fieldRec['thumb_root'];
            $PreviewRoot    = $fieldRec['prev_root'];
            $FullRoot       = $fieldRec['full_root'];
            $DocRoot        = $fieldRec['doc_root'];
            
            switch($fieldType)
            {
                case App_Constants::MEDIA:
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
                                'res_label'             => $resFilename,
                                'res_path_source'       => $filenameAndPath,
                                'res_filename'          => $resFilename,
                                'mime_type'            => $resFormat
                            );
                            
                            $file_types = array("jpg" => "image",
                                                "tif" => "image",
                                                "png" => "image",
                                                "pdf" => "doc",
                                                "doc" => "doc"
                                                );
                            
                            $resType = false;
                            
                            foreach($file_types AS $ext=>$ftype){
                                if($ext == strtolower($resFormat)){
                                    $resType = $ftype;
                                }
                            }
                            
                            if($resType == "image"){
                                $data["ia_thumb"] = str_replace(" ", "%20", $ThumbRoot.$filenameAndPath);
                                $data["ia_preview"] = str_replace(" ", "%20", $PreviewRoot.$filenameAndPath);
                                $data["ia_fullfile"] = str_replace(" ", "%20", $FullRoot.$filenameAndPath);
                            }
                            elseif($resType == "doc"){
                                $data["ia_thumb"] = "http://www.opencontext.org/database/ui_images/oc_icons/sm_document_icon.jpg";
                                $data["ia_preview"] = "http://www.opencontext.org/database/ui_images/oc_icons/lg_document_icon.jpg";
                                $data["ia_fullfile"] = str_replace(" ", "%20", $DocRoot.$filenameAndPath);
                            }
                            
                            
                            
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
                                    $this->getmediaTile($dataTableName, $fieldNum, $rowNumber, $resUUID); //this adds title information if available
                                }
                            }
                        }//end case where item does not exist
                        else{
                            
                            //echo print_r($resourceRow );
                            $resUUID =  $resourceRow->uuid;
                            
                            // 1) insert all associated records into the lookup table:
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
                                    $this->getmediaTile($dataTableName, $fieldNum, $rowNumber, $resUUID); //this adds title information if available
                                }
                            }
                            
                            
                            
                        }//end case where item already exists
                    
                    }
                    array_push($returnArray, $numRecsInserted . ' \'Media\' records were inserted for the \'' . $fieldLabel . '\' field.');
                    break;
                case App_Constants::DIARY:
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
                        /*
                        if(strlen($diaryHash) > 100){
                            $diaryHash = substr($diaryHash, 0, 100);
                        }
                        */
                        $diaryHash  = md5($projectUUID . '_' . $diaryHash);
								
								if(strlen($diaryText)>140){
									 $diaryRow   = $diary->fetchRow("diary_hash='" . $diaryHash . "' ");
								}
								else{
									 $diaryRow   = $diary->fetchRow("diary_label = '" . $diaryText . "' ");
								}
                        
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
								else{
									 
									 //echo print_r($diaryRow );
									 
                            $diaryUUID =  $diaryRow->uuid;
									 $diaryLabel = $diaryRow->diary_label;
									 $diaryText = $diaryRow->diary_text_original;
									 
									 // 1) insert all associated records into the lookup table:
                            $selectLU = $db->select()
										  ->from  (
													 array('d' => $dataTableName),
													 array('d.' . $fieldName, 'd.id')
										  )
										  ->where('d.' . $fieldName . ' = "'.$diaryLabel.'" ');
									 //Zend_Debug::dump($dataTableName);
									 //Zend_Debug::dump($fieldRec);
									 //return;
								 
                            $luRecs = $db->query($selectLU)->fetchAll();
                            foreach($luRecs as $luRec){
                                $rowNumber = $luRec['id'];
									 
                                $sql =  "SELECT * FROM diary_lookup WHERE source_id='" . $dataTableName . "' and uuid='" . $diaryUUID .
                                    "' and field_num =" . $fieldNum . " and row_num = " . $rowNumber;
												
                                $rowLU = $db->fetchAll($sql);
                                if(!$rowLU){
                                    $data = array(
                                            'source_id'      => $dataTableName,
                                            'uuid'      => $diaryUUID,
                                            'field_num'     => $fieldNum,
                                            'row_num'       => $rowNumber
                                    );
												
												$db->insert("diary_lookup", $data);
                                }
                            }
									 
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
     * 1)   Locations / Objects records,
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
                    try{
                        $db->insert('links', $data);
                    }
                    catch (Exception $e) {
                        
                    }
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
        
        //echo $projectUUID;
        
        
        //before establishing links, process all non-containment locations and object values:
        $this->addNonContainmentLocationsObjects($projectUUID, $dataTableName);
        
        
        $db = Zend_Registry::get('db');
        
        
        //echo "Query for all non-containment Locations / Objects fields...";
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
            
            
            $targetTableName = false;
            $originTableName = false;
            
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
            
            $doQueryOK = false;
            if($targetTableName != false && $originTableName !=false){
                $doQueryOK = true;
            }
            
            
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
                    
                    if($doQueryOK){
                        $dataRows = $db->query($selectData)->fetchAll();
                    }
                    else{
                        $dataRows = array();
                    }
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
                    if($doQueryOK){
                        $dataRows = $db->query($selectData)->fetchAll();
                    }
                    else{
                        $dataRows = array();
                    }
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
        
        
        //fix a bug where origins and targets type got mixed up
        $this->link_type_fix($projectUUID,$dataTableName);
        
        
        //echo $numRelationshipsAdded . " linking relationships and " . $numLinksAdded . " new data links were added.";
        echo Zend_Json::encode($returnArray);
        
        
    }
    
    
    //this executes a number of queries to fix origin and target types
    private function link_type_fix($projectUUID,$dataTableName){
        $db = Zend_Registry::get('db');
        $sql = "UPDATE links
        JOIN resource ON links.origin_uuid = resource.uuid
        SET links.origin_type = 'Media (various)'
        WHERE links.project_id = '$projectUUID'
        AND links.source_id = '$dataTableName'
        ";
        
        $stmt = $db->query($sql);
        $stmt->execute();
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
                         'field_lab_com',
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
            $fieldAlias = $locObjRec['field_lab_com'];
            
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
            
            $firstLoop = true;
            foreach($distinctLocObjDataRecs as $rec)
            {
                $theValue   = $rec[$fieldName];                
                
                if($fieldAlias != null){
                        $theValue_Prefix =   $fieldAlias . ' ' . $rec[$fieldName];
                        //$theValue  = str_replace($fieldAlias.$fieldAlias, $fieldAlias, $theValue);
                }
                else{
                        $theValue_Prefix = $theValue;
                }
                
                $hashTxt    = md5($projectUUID . "_" . $theValue_Prefix);

                //check to see if the parent record exists in the space table:
                $spaceRow = $space->fetchRow("hash_fcntxt = '" . $hashTxt . "' AND project_id = '".$projectUUID."'");
    
                if($spaceRow == null){
                    $spaceRow = $space->fetchRow("space_label = '" . $theValue_Prefix . "' AND project_id = '".$projectUUID."' AND source_id = '".$dataTableName."' ");
                }
    
    
                $makeNew = false; //add records without containment relations (false)
                if($spaceRow == null)
                {
                    //insert into space
                    $spaceUUID      = GenericFunctions::generateUUID();
                    $spaceLabel     = $theValue;
                    if($fieldAlias != null){
                        $spaceLabel =   $fieldAlias . ' ' . $spaceLabel;
                        $spaceLabel = str_replace($fieldAlias.$fieldAlias, $fieldAlias, $spaceLabel);
                    }
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
                    
                    if($makeNew){
                        $space->insert($data);
                    }
                    
                    
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
                        if($makeNew){
                            $spaceContain->insert($data);
                        }
                    }
                    if($fieldNotes != null && strlen($fieldNotes) > 0)
                    {
                        //add notes to val_tab:
                        try{
                            $valueUUID      = $this->addNotesValueToTable($projectUUID, $dataTableName, $fieldNotes);
                        }
                        catch (Exception $e) {
                            $valueUUID = null;
                        }
                        //add notes to properties and w_val_ids_lookup:
                        try{
                            $notesPropUUID  = $this->addPropertyToTable($projectUUID, $dataTableName, $valueUUID, 'NOTES', $fieldNum, null);
                        }
                        catch (Exception $e) {
                            $notesPropUUID = null;
                        }
                        
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
                    /*
                    if($firstLoop){
                        echo "LU Records for " . $fieldName . ' = ' . $theValue . ": ";
                        Zend_Debug::dump($LURecs);
                    }
                    */
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
                            
                            if($makeNew){
                                $spaceLookup->insert($data);
                            } 
                        }    
                    } //end foreach record having $theValue
                    $firstLoop = false;
                    
                } //end if
                else{
                    //echo var_dump($spaceRow);
                    
                    $spaceUUID = $spaceRow->uuid;
                    
                    //get all lookup records that are associated with this value:
                    $select = $db->select()
                        ->from  (
                                array('d' => $dataTableName),
                                array('d.id', $fieldName)
                        )
                        ->where($fieldName . ' = ?', $theValue);
                    $LURecs =  $db->query($select)->fetchAll();
                    
                    //$LURecs = array();
                    
                    /*
                    if($firstLoop){
                        echo "LU Records for " . $fieldName . ' = ' . $theValue . ": ";
                        Zend_Debug::dump($LURecs);
                    }
                    */
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
                    
                }
            } //end foreach distinct field value
        } //end foreach non-spatial containment Locations / Objects record.
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
            
            $obsGroupsCheck = $this->getObsGroups($dataTableName);
            if(!$obsGroupsCheck){
                $obsGroups = array( 0 => false);
            }
            else{
                $obsGroups = $obsGroupsCheck["groups"];
            }
            
            foreach($obsGroups as $actObs){ 
            
                if(!$obsGroupsCheck){
                    $doObs = false;
                }
                else{
                    $doObs = array("groupField"=> $obsGroupsCheck["groupField"], "value" => $actObs);
                }
                
                $dataRows = array();
                //Zend_Debug::dump($objType);
                //echo $objType  . ' - ' . $dataTableName . ' - ' . $propNum  . ' - ' . $objNum . '<br />';
                switch($objType)
                {
                    //join with space_lookup:
                    case "Locations or Objects":
                        $dataRows = $this->getPropertyObjectMappingData($dataTableName, 'space_lookup', 'uuid', $propNum, $objNum, $doObs);
                        break;
                    //join with persons_lookup:
                    case "Person":
                        $dataRows = $this->getPropertyObjectMappingData($dataTableName, 'persons_lookup', 'uuid', $propNum, $objNum);
                        //Zend_Debug::dump($dataRows);
                        break;
                    //join with resource_lookup:
                    case "Media (various)":
                        $dataRows = $this->getPropertyObjectMappingData($dataTableName, 'resource_lookup', 'uuid', $propNum, $objNum);
                        //Zend_Debug::dump($dataRows);    
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
            
            
            $obsGroup++;
            }//end loop through obs groups
        
        }
        $this->markDataTableAsComplete($projectUUID, $dataTableName);
        echo Zend_Json::encode($returnArray);    
    }
    
    private function getPropertyObjectMappingData($dataTableName, $objectTableName, $objectUUIDFieldName, $propNum, $objNum, $doObs = false)
    {
        $db = Zend_Registry::get('db');
        if(!$doObs){
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
        }
        else{
            $groupField = $doObs["groupField"];
            $groupValue = $doObs["value"];
            
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
            ->join(
                array('ztab' => $dataTableName),
                'lu_obj.row_num = ztab.id',
                array('ztab.id')
            )
            ->where('lu_obj.source_id = ?', $dataTableName)
            ->where('lu_obj.field_num = ?', $objNum)
            ->where('lu_prop.field_num = ?', $propNum)
            ->where('ztab.'.$groupField.' = ?', $groupValue);
        }
        //Zend_Debug::dump($db->query($select));
        return $db->query($select)->fetchAll();
    }
    
    //this function returns distinct observation groups, if the table has multiple distinct observations in it 
    private function getObsGroups($dataTableName){
        
        $output = false;
        $db = Zend_Registry::get('db');
        $sql = "SELECT project_id, obs_group_field FROM file_summary WHERE source_id = '$dataTableName'";
        $result = $db->fetchAll($sql);
        $projectUUID = $result[0]["project_id"];
        $groupField = $result[0]["obs_group_field"];
        if(strlen($groupField)>2){
           
            $valueArray = array();
            
            $sql = "SELECT DISTINCT $groupField
            FROM $dataTableName ";
            
            $result = $db->fetchAll($sql);
            $obsNum = 1;
            foreach($result as $row){
                $valueArray[] = $row[$groupField];
                $this->makeObsGroupMetadata($row[$groupField], $groupField, $obsNum, $dataTableName, $projectUUID, $db);
                $obsNum++;
            }
            $output = array("groupField" => $groupField, "groups" => $valueArray);
        }
        
        return $output;
    }
    
    private function makeObsGroupMetadata($groupValue, $groupField, $obsNum, $source_id, $projectUUID, $db){
        
        $obsHash = md5($projectUUID."_".$source_id."_".$obsNum);
        
        $sql = "SELECT obs_id FROM obs_metadata WHERE obs_id = '$obsHash' ";
        $result = $db->fetchAll($sql);
        $addNew = false;
        
        if($result){
            $addNew = false;    
        }
        else{
            $addNew = true;    
        }
        
        if($addNew){
            
            $sql = "SELECT field_label FROM field_summary
            WHERE project_id = '$projectUUID'
            AND source_id = '$source_id'
            AND field_name = '$groupField'
            LIMIT 1";
            
            $fresult = $db->fetchAll($sql);
            
            if($fresult){
            
                $obs_name = $fresult[0]["field_label"].": ".$groupValue;
                $obs_type = "Primary";
                
                $data = array("obs_id" => $obsHash,
                              "project_id"=> $projectUUID,
                              "source_id"=> $source_id,
                              "obs_num"=> $obsNum,
                              "obs_name"=> $obs_name,
                              "obs_type"=> $obs_type,
                              "obs_notes"=> "Auto generated from imported table"
                              );
                $db->insert("obs_metadata", $data);
            
            }
        }
        
    }
    
    
    
    private function markDataTableAsComplete($projectUUID, $dataTableName)
    {
        $db = Zend_Registry::get('db');
		  $this->setUTFconnection($db);
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
	 
	
}