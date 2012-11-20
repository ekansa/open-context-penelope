<?php

require_once 'App/Controller/PenelopeController.php';   //handles the functionality common across controllers

class AnnotateController extends App_Controller_PenelopeController
{
    function indexAction()
    {
        //call to process query parameters:
        parent::indexAction();
        $this->view->title = "Data Importer";
    }
    
    function getFieldSummaryObjectDatastoreeAction()
    {
        /**
         * pk_field:        unique identifier
         * field_num:       unique identifier for the given table
         * field_label:     label of the field
         * prop_desc:       description of the field
         * class_uuid:      determines what image class
         * field_notes:     more descriptive notes about the field
         * field_lab_com:   prefix that appears before the data value
         */
        
        //this line is necessary for ajax calls:
        $this->_helper->viewRenderer->setNoRender();        
        
        //get selected project
        $dataTableName = $_REQUEST['dataTableName'];
        
        Zend_Loader::loadClass('Layout_DataGridHelper');
        Zend_Loader::loadClass('Table_FieldSummary');
        $dgHelper = new Layout_DataGridHelper();
        
        //1.  set data records:
        $db = Zend_Registry::get('db');
        $select = $db->select()
            ->from  (
                        array('f' => 'field_summary'),
                        array('f.pk_field', 'f.field_num', 'f.field_label', 'f.prop_desc', 'f.fk_class_uuid', 'f.field_lab_com',
                              'f.field_notes', 'c.class_label', 'c.sm_class_icon', 'c.class_icon')
            )
            ->joinLeft  (
                     array('c' => 'sp_classes'),
                    'f.fk_class_uuid = c.class_uuid'
            )
            ->where ("f.source_id = '" . $dataTableName . "' and f.field_type = 'Locations or Objects'")
            ->group('f.pk_field');
            ;
            
        $sql = $select->__toString();
        echo "Here: $sql\n";
    
    }
    
    function getFieldSummaryObjectDatastoreAction()
    {
        /**
         * pk_field:        unique identifier
         * field_num:       unique identifier for the given table
         * field_label:     label of the field
         * prop_desc:       description of the field
         * class_uuid:      determines what image class
         * field_notes:     more descriptive notes about the field
         * field_lab_com:   prefix that appears before the data value
         */
        
        //this line is necessary for ajax calls:
        $this->_helper->viewRenderer->setNoRender();        
        
        //get selected project
        $dataTableName = $_REQUEST['dataTableName'];
        
        Zend_Loader::loadClass('Layout_DataGridHelper');
        Zend_Loader::loadClass('Table_FieldSummary');
        $dgHelper = new Layout_DataGridHelper();
        
        //1.  set data records:
        $db = Zend_Registry::get('db');
        $select = $db->select()
            ->from  (
                        array('f' => 'field_summary'),
                        array('f.pk_field', 'f.field_num', 'f.field_label', 'f.prop_desc', 'f.fk_class_uuid', 'f.field_lab_com',
                              'f.field_notes', 'c.class_label', 'c.sm_class_icon', 'c.class_icon')
            )
            ->joinLeft  (
                     array('c' => 'sp_classes'),
                    'f.fk_class_uuid = c.class_uuid'
            )
            ->where ("f.source_id = '" . $dataTableName . "' and f.field_type = 'Locations or Objects'")
            ->group('f.pk_field');
            ;
            
        //$sql = $select->__toString();
        //echo "Here: $sql\n";
        
        
        $stmt = $db->query($select);
        
        $rows = $stmt->fetchAll();
        
        //$rows = null;

        /*$fieldSummaryTable = new Table_FieldSummary();
        $whereClause = ("source_id = '" . $dataTableName . "' and field_type = 'Locations or Objects'");
        $rows = $fieldSummaryTable->fetchAll($whereClause);*/
        $dgHelper->setDataRecords($rows, "pk_field");

        //2.  set layout
        $layout = array();
        /*$layoutItem0 = array(
            'field'     =>  'field_num',
            'name'      =>  'ID',
            'width'     =>  '30px'
         );*/       
        $layoutItem0 = array(
            'field'     =>  'field_label',
            'name'      =>  'Field Name',
            'width'     =>  '75px'
         );
        $layoutItem1 = array(
            'field'     =>  'field_lab_com',
            'name'      =>  'Item Label (Editable)',
            'width'     =>  '75px',
            'editable'  =>  true
         );
        $layoutItem2 = array(
            'field'         =>  'sm_class_icon',
            'name'          =>  'Class',
            'width'         =>  '50px',
            'formatter'     =>  'formatClassColumn'            
         );
        $layoutItem3 = array(
            'field'         =>  'class_label',
            'name'          =>  'Class',
            'width'         =>  '50px',         
         );
        $layoutItem4 = array(
            'field'         =>  'field_notes',
            'name'          =>  'Field Notes (Editable)',
            'width'         =>  '100px',
            'editable'      =>  true //,
            //'type'          =>  'dojox.grid.cells.Editor' 
         );
        $layoutItem5 = array(
            'field'         =>  'examples', //note that this is just a placeholder field.
                                            //"examples" doesn't really exist.
            'name'          =>  'Examples',
            'width'         =>  '200px'
            //'editor'        =>  'dojox.grid.editors.Dijit',
            //'editorClass'   =>  'dijit.form.Textarea'
         );
        
        $layout[0] = $layoutItem0;
        $layout[1] = $layoutItem1;
        $layout[2] = $layoutItem2;
        $layout[3] = $layoutItem3;
        $layout[4] = $layoutItem4;
        $layout[5] = $layoutItem5;

        $dgHelper->layout = $layout;
        echo Zend_Json::encode($dgHelper);
    }
    
    
    
    function getClassLookupAction()
    {
        //this line is necessary for ajax calls:
        $this->_helper->viewRenderer->setNoRender();
        
        //echo("getClassLookupAction");
        
        Zend_Loader::loadClass('Layout_DataGridHelper');
        Zend_Loader::loadClass('Table_Class');
        $dgHelper = new Layout_DataGridHelper();
        
        $classesTable = new Table_Class();
        $rows = $classesTable->fetchAll();
        
        //echo print_r($rows);
        //$rows = $rows->get_object_vars(); 
        $dgHelper->setDataRecords($rows, "class_uuid");
        
        //2.  set layout
        $layout = array();
        $layoutItem0 = array(
            'field'     =>  'class_label',
            'name'      =>  'Class',
            'width'     =>  '60px'
         );        
        $layoutItem1 = array(
            'field'     =>  'sm_class_icon',
            'name'      =>  'Image',
            'width'     =>  '65px',
            'formatter' =>  'showImage'
         );
        
        $layout[0] = $layoutItem0;
        $layout[1] = $layoutItem1;

        $dgHelper->layout = $layout;
        echo Zend_Json::encode($dgHelper);
        
    }
    
    function getSampleDataAction()
    {
        //this line is necessary for ajax calls:
        $this->_helper->viewRenderer->setNoRender();

        //1.  get selected project:
        $dataTableName = $_REQUEST['dataTableName'];
        //Zend_Debug::dump($dataTableName);
        
        //2.  declare the return object.  Note:  the return object is
        //    an array of arrays, where each sub-array has 3 properties:
        //  - field_name    (name of field)
        //  - field_label   (alias of field)
        //  - samples       (array of 5 sample items)
        $returnObject = array();
        
        //3.  query the field summary table to see which are location fields:
        $db = Zend_Registry::get('db');
        $select = $db->select()
            ->from  (
                        array('f' => 'field_summary'),
                        array('f.field_name', 'f.field_label' )
            )
            ->where ("f.source_id = '" . $dataTableName . "' and f.field_type = '" . App_Constants::SPATIAL . "'"); 
        
        $stmt = $db->query($select);
        $fieldRows = $stmt->fetchAll();
        
        //4.  get the field list to query the sample records table:
        $i = 0;
        $fieldList = array();
        foreach($fieldRows as $fieldRow)
        {
            $fieldList[$i]                  = "d." . $fieldRow['field_name'];
            $returnObjectItem               = array('field_name'    => $fieldRow['field_name'],
                                                    'field_label'   => $fieldRow['field_label']);
            $returnObject[$i]               = $returnObjectItem;
            ++$i;
        }

        //5.  query the sample records table:
        $select = $db->select()
        ->from  (
                    array('d' => $dataTableName),
                    $fieldList
        )
        //->where ("d.id < 4");
        ->limit(3, 0);
        $stmt = $db->query($select);        
        $rows = $stmt->fetchAll();
        
        //6.  populate the return object
        $i = 0;
        foreach($fieldRows as $fieldRow)
        {
            $j = 0;
            $itemArray = array();
            foreach($rows as $row)
            {
                $itemArray[$j] = $row[$returnObject[$i]['field_name']]; //get the object for the field type
                ++$j;
            }
            $returnObject[$i]['samples'] = $itemArray;
            ++$i;
        }
        
        //returning an array of arrays, where 
        echo Zend_Json::encode($returnObject);  
        
    }
    
    function saveClassDatastoreAction()
    {
        //this line is necessary for ajax calls:
        $this->_helper->viewRenderer->setNoRender();
        
        Zend_Loader::loadClass('Table_FieldSummary');
        $fieldSummaryTable = new Table_FieldSummary();
        
        $recordArray = Zend_Json::decode($_REQUEST['datastore'], Zend_Json::TYPE_OBJECT);
        foreach ($recordArray as $record) 
        {
            //Zend_Debug::dump($record);
            $where = $fieldSummaryTable->getAdapter()->quoteInto('pk_field = ?', $record->pk_field);
            $data = array('field_lab_com'   => $record->field_lab_com,
                          'fk_class_uuid'   => $record->fk_class_uuid,
                          'field_notes'     => $record->field_notes);
            $fieldSummaryTable->update($data, $where);

             //Zend_Debug::dump($record);
        } 
        echo "Data successfully updated!";   
    }
    
}