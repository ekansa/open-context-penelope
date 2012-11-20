<?php

class Importer_ClassifyController extends Zend_Controller_Action
{
    function init()
    {  
        $this->view->baseUrl = $this->_request->getBaseUrl();
    }
    
    function getFieldSummaryDatastoreAction()
    {
        //this line is necessary for ajax calls:
        $this->_helper->viewRenderer->setNoRender();        
        
        //get selected project
        $dataTableName = $_REQUEST['dataTableName'];

        Zend_Loader::loadClass('Layout_DataGridHelper');
        Zend_Loader::loadClass('Table_FieldSummary');
        $dgHelper = new Layout_DataGridHelper();
        
        //1.  set data records:
        $fieldSummaryTable = new Table_FieldSummary();
        $whereClause = ("source_id = '" . $dataTableName . "'");
        $rows = $fieldSummaryTable->fetchAll($whereClause);
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
        $layoutItem0 = array(
            'field'     =>  'field_num',
            'name'      =>  'ID',
            'width'     =>  '30px'
         );        
        $layoutItem1 = array(
            'field'     =>  'field_label',
            'name'      =>  'Field Label',
            'width'     =>  '100px',
            'editable'  =>  true
         );
        $layoutItem2 = array(
            'field'     =>  'field_type',
            'name'      =>  'Field Type',
            'width'     =>  '150px',
            'type'      =>  'dojox.grid.cells.Select',
            'cellType'  =>  'dojox.grid.cells.Bool',
            'options'   =>  $fieldOptions,
            'editable'  =>  true
         );
        $layoutItem3 = array(
            'field'         =>  'prop_desc',
            'name'          =>  'Field Description',
            'width'         =>  '200px',
            'editable'      =>  true
            //'editor'        =>  'dojox.grid.editors.Dijit',
            //'editorClass'   =>  'dijit.form.Textarea'
         );
        $layoutItem4 = array(
            'field'         =>  'prop_type',
            'name'          =>  'Property Type',
            'width'         =>  '100px',
            'type'          =>  'dojox.grid.cells.Select',
            'options'       =>  $propOptions,
            'editable'      =>  true
         );
        
        $layout[0] = $layoutItem0;
        $layout[1] = $layoutItem1;
        $layout[2] = $layoutItem2;
        $layout[3] = $layoutItem3;
        $layout[4] = $layoutItem4;

        $dgHelper->layout = $layout;
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
}