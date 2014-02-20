<?php

error_reporting(E_ALL);
require_once 'App/Controller/PenelopeController.php';   //handles the functionality common across controllers

class ClassifyController extends App_Controller_PenelopeController
{
    function indexAction()
    {
        //call to process query parameters:
        parent::indexAction();
        $this->view->title = "Data Importer";
    }
    
    
    function getFieldSummaryDatastoreAction()
    {
        //this line is necessary for ajax calls:
        $this->_helper->viewRenderer->setNoRender();        
        
        //get selected project
        $dataTableName = $_REQUEST['dataTableName'];
        $this->autoClassify($dataTableName);

        Zend_Loader::loadClass('Layout_DataGridHelper');
        Zend_Loader::loadClass('Table_FieldSummary');
        $dgHelper = new Layout_DataGridHelper();
        
        //1.  set data records:
        $fieldSummaryTable = new Table_FieldSummary();
        $whereClause = ("source_id = '" . $dataTableName . "'");
        $rows = $fieldSummaryTable->fetchAll($whereClause, "field_num ASC");
        $dgHelper->setDataRecords($rows, "pk_field");
        
        //2.  define a layout:
        //2.a). get field types:
        Zend_Loader::loadClass('Table_FieldType');
        $fieldTypesTable = new Table_FieldType();
        $rows = $fieldTypesTable->fetchAll();
        $fieldOptions = array();
        $i = 0;
        foreach ($rows as $row) 
        {
            $fieldOptions[$i] = $row->FIELD_TYPE_NAME;
            ++$i;
        }        
        
        //2.b) get property types:
        Zend_Loader::loadClass('Table_PropertyType');
        $fieldPropsTable = new Table_PropertyType();
        $rows = $fieldPropsTable->fetchAll();
        $propOptions = array();
        $propOptions[0] = "";
        $i = 1;
        foreach ($rows as $row) 
        {
            $propOptions[$i] = $row->NAME;
            ++$i;
        } 
        
        //2.c)
        $layout = array();      
        array_push($layout, array(
            'field'     =>  'field_label',
            'name'      =>  'Field Label',
            'width'     =>  '100px',
            'editable'  =>  true
         ));
        array_push($layout, array(
            'field'     =>  'field_type',
            'name'      =>  'Field Type',
            'width'     =>  '100px',
            'type'      =>  'dojox.grid.cells.Select',
            'cellType'  =>  'dojox.grid.cells.Bool',
            'options'   =>  $fieldOptions,
            'editable'  =>  true
         ));
        array_push($layout, array(
            'field'         =>  'prop_desc',
            'name'          =>  'Field Description',
            'width'         =>  '150px',
            'editable'      =>  true
            //'editor'        =>  'dojox.grid.editors.Dijit',
            //'editorClass'   =>  'dijit.form.Textarea'
         ));
        array_push($layout, array(
            'field'         =>  'prop_type',
            'name'          =>  'Property Type',
            'width'         =>  '80px',
            'type'          =>  'dojox.grid.cells.Select',
            'options'       =>  $propOptions,
            'editable'      =>  true
         ));

        $dgHelper->layout = $layout;
        header('Content-Type: application/json; charset=utf8');
        echo Zend_Json::encode($dgHelper);
    }

    function getFieldTypesAction()
    {
        //this line is necessary for ajax calls:
        $this->_helper->viewRenderer->setNoRender();
        Zend_Loader::loadClass('Table_FieldType');
        Zend_Loader::loadClass('FieldType');
        $fieldTypesTable = new Table_FieldType();
        $rows = $fieldTypesTable->fetchAll();
        $fieldTypes = array();
        $i = 0;
        foreach ($rows as $row) 
        {
            $fieldTypes[$i] = new FieldType($row);
            ++$i;
        }
        header('Content-Type: application/json; charset=utf8');
        echo Zend_Json::encode($fieldTypes);
    }
    
    function calcDataTypesAction()
    {
        //this line is necessary for ajax calls:
        $this->_helper->viewRenderer->setNoRender();
        
        $dataTableName = $_REQUEST['dataTableName'];
        
        //check to see that the property type field isn't already populated:
        $db = Zend_Registry::get('db');
        $select = $db->select()
            ->from  (
                        array('f' => 'field_summary'),
                        array('prop_type')           
            )
            ->where ("f.prop_type <> '' and f.source_id = '" . $dataTableName . "'");
        $stmt           = $db->query($select);
        $distinctRows   = $stmt->fetchAll();
        $doAutoCalculation  = sizeof($distinctRows) == 0;
        
        //$doAutoCalculation = false;
        if($doAutoCalculation)
        {
            //import relevant libraries:
            require_once 'App/Util/TableFunctions.php';        
            echo TableFunctions::updateDataTypes($dataTableName);
            
        }
        else
        {
            echo sizeof($distinctRows) . " Auto-calculation not needed.";   
        }
        
    }
    
    
    function saveFieldsDatastoreAction()
    {
        //this line is necessary for ajax calls:
        $this->_helper->viewRenderer->setNoRender();
        
        Zend_Loader::loadClass('Table_FieldSummary');
        $fieldSummaryTable = new Table_FieldSummary();
        
        //echo "data made it!";
        //return;
        
        $cnt = 0;
        $recordArray = Zend_Json::decode($_REQUEST['datastore'], Zend_Json::TYPE_OBJECT);
        //Zend_Debug::dump($recordArray);
        foreach ($recordArray as $record) 
        {
            //Zend_Debug::dump($record);
            $where = $fieldSummaryTable->getAdapter()->quoteInto('pk_field = ?', $record->pk_field);
            $data = array('field_type'  => $record->field_type,
                          'field_label' => $record->field_label,
                          'prop_desc'   => $record->prop_desc,
                          'prop_type'   => $record->prop_type);
            $fieldSummaryTable->update($data, $where);
            ++$cnt;
        } 
        echo $cnt;
    }
    
    
    
    //this function cleans field names
    private function cleanField($pk_field, $bad_field_label){
        
        if(substr_count($bad_field_label, " ")>0){
            $bad_field_array = explode(" ", $bad_field_label);
        }
        else{
            $bad_field_array = array();
            $bad_field_array[] = $bad_field_label;
        }
        
        $goodLabel = "";
        $first = true;
        foreach($bad_field_array as $badpart){
            //$badpart = strtolower($badpart);
            //$badpart = ucwords($badpart);
            if($badpart == "L"){
                $badpart = "Left";
            }
            elseif(($badpart == "R")||($badpart == "Rt")||($badpart == "RT")){
                $badpart = "Right";
            }
            elseif(($badpart == "And")||($badpart == "&")){
                $badpart = "and";
            }
            elseif(($badpart == "(Mm)")||($badpart == "Mm")||($badpart == "MM")){
                $badpart = "(mm)";
            }
            elseif(($badpart == "(long")){
                $badpart = "(Long";
            }
            
            if($first){
                $goodLabel = $badpart;
            }
            else{
                $goodLabel .= " ".$badpart;
            }
            
        $first = false;
        }
        
        return $goodLabel; 
    }
    
    
    
    private function autoClassifyNeeded($dataTableName){
        
        $output = true;
        $db = Zend_Registry::get('db');
        $db->getConnection();
        
        $sql = 'SELECT *
        FROM field_summary
        WHERE field_summary.source_id = "'.$dataTableName.'"
        ';
        
        $result = $db->fetchAll($sql);
        $i = 0;
        foreach($result as $row){
            if(strlen($row["field_type"])>0){
                $output = false; // some fields already classified, no need to auto-classify
            }
            
            if($i<=20){
                $indexSQL = "CREATE INDEX fInd_".($i+1)." ON ".$dataTableName."(".$row["field_name"]."(10));";
                //$db->query($indexSQL);
            }
            
            $goodLabel = $this->cleanField($row["pk_field"], $row["field_label"]);
            
            //echo $goodLabel;
            
            if($goodLabel != $row["field_label"]){
                $where = array();
                $data = array("field_label" => $goodLabel );
                $where[] = "source_id = '" . $dataTableName . "'";
                $where[] = "pk_field = ".$row["pk_field"];
                $n = $db->update('field_summary', $data, $where);
            }
            
        $i++;    
        }
        
            
        $db->closeConnection();
        
        return $output;
    }
    
    
    private function autoClassify($actTabID){
        
        $doAutoClassify = $this->autoClassifyNeeded($actTabID);
        
        if($doAutoClassify){
            $db = Zend_Registry::get('db');
            $db->getConnection();
            
            $sql = 'SELECT *
            FROM file_summary
            WHERE file_summary.source_id = "'.$actTabID.'"
            ';
            
            $fileResult = $db->fetchAll($sql);
            $projectUUID = $fileResult[0]["project_id"];
            $n = $db->update('field_summary', array("project_id"=>$projectUUID), 'field_summary.source_id = "'.$actTabID.'"');
            
    
            $sql = 'SELECT *
            FROM field_summary
            WHERE field_summary.source_id = "'.$actTabID.'"
            ';
            $actTable = $db->fetchAll($sql, 2);
            
            $sql = 'SELECT *
            FROM field_summary
            WHERE field_summary.project_id = "'.$projectUUID.'"
            AND field_summary.source_id != "'.$actTabID.'"
            ';
            
            $updatesCNT = 0;
            $result = $db->fetchAll($sql, 2);
            if($result){
                //there are other tables imported in this project
                foreach($result as $row){
                    
                    $i=1;
                    foreach($actTable AS $actTableField){
                    
                        //echo $actLabel." ";
                        if(isset($actTableField["field_type"])){
                            $actLabel = $actTableField["field_label"];
                        }
                        else{
                            $actLabel = false;
                        }
                        
                        if(strtolower($row["field_label"]) ==  strtolower($actLabel)){
                            //a previous import matched a field name for the current import
                            $data = array("field_type"=>$row["field_type"],
                                          );
                            if(strlen($row["field_lab_com"])>0){
                                $data["field_lab_com"] = $row["field_lab_com"];
                            }
                            if(strlen($row["prop_desc"])>0){
                                $data["prop_desc"] = $row["prop_desc"];
                            }
                            if(strlen($row["fk_class_uuid"])>0){
                                $data["fk_class_uuid"] = $row["fk_class_uuid"];
                            }
                            if(strlen($row["field_notes"])>0){
                                $data["field_notes"] = $row["field_notes"];
                            }
                            if(strlen($row["geo_type"])>0){
                                $data["geo_type"] = $row["geo_type"];
                            }
                            $where = array();
                            $where[] = "project_id = '".$projectUUID."'";
                            $where[] = "source_id = '".$actTabID."'";
                            $where[] = "field_label = '".$actLabel."'";
                            
                            $n = $db->update('field_summary', $data, $where); //update database to autocomplete
                            //Zend_Debug::dump($data);
                            $updatesCNT++;
                        }
                        
                    $i++;    
                    }//end loop through this import
                    
                }//end loop through previous imports
                
                
            }//end case with a result
        }//endcase to autoclass
        else{
            $updatesCNT = 0;
        }
        
        return $updatesCNT;
        
    }//end function
    
    //this fixes a problem interpreting excel dates
    private function excel_date($serial){
        //from http://richardlynch.blogspot.com/2007/07/php-microsoft-excel-reader-and-serial.html
        // Excel/Lotus 123 have a bug with 29-02-1900. 1900 is not a
        // leap year, but Excel/Lotus 123 think it is...
        if ($serial == 60) {
            $day = 29;
            $month = 2;
            $year = 1900;
            
            return sprintf('%02d/%02d/%04d', $month, $day, $year);
        }
        else if ($serial < 60) {
            // Because of the 29-02-1900 bug, any serial date 
            // under 60 is one off... Compensate.
            $serial++;
        }
        
        // Modified Julian to DMY calculation with an addition of 2415019
        $l = $serial + 68569 + 2415019;
        $n = floor(( 4 * $l ) / 146097);
        $l = $l - floor(( 146097 * $n + 3 ) / 4);
        $i = floor(( 4000 * ( $l + 1 ) ) / 1461001);
        $l = $l - floor(( 1461 * $i ) / 4) + 31;
        $j = floor(( 80 * $l ) / 2447);
        $day = $l - floor(( 2447 * $j ) / 80);
        $l = floor($j / 11);
        $month = $j + 2 - ( 12 * $l );
        $year = 100 * ( $n - 49 ) + $i + $l;
        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }
    
    function dateFixAction(){
        $dataTableName = $_REQUEST['dataTableName'];
        $fieldNum = $_REQUEST['fNum'];
        $fieldLabel = "field_".$fieldNum;
        
        $this->_helper->viewRenderer->setNoRender(); 
        $db = Zend_Registry::get('db');
        $db->getConnection();
        
        $sql = "SELECT DISTINCT $fieldLabel AS fixField
        FROM $dataTableName
        ";
        $result = $db->fetchAll($sql, 2);
        $i=0;
        foreach($result as $row){
            $actVal = "0".$row["fixField"];
            
            if(is_numeric($actVal)){
                $fixed_value = $this->excel_date($actVal);
            }
            
            $data = array($fieldLabel => $fixed_value);
            $where = $fieldLabel." = '".$row["fixField"]."' ";
            
            $db->update($dataTableName, $data, $where);
            $i++;
        }
        
        echo $i;
    }
    
}