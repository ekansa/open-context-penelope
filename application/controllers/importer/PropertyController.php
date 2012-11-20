<?php

class Importer_PropertyController extends Zend_Controller_Action
{
    public $counter = 0;
    function init()
    {  
        $this->view->baseUrl = $this->_request->getBaseUrl();
        Zend_Loader::loadClass('Zend_Debug');
    }
    
    function getPropertyDataAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $dataTableName  = $_REQUEST['dataTableName'];
        
        $db = Zend_Registry::get('db');
        $select = $db->select()
            ->distinct()
            ->from  (
                        array('f1' => 'field_summary'),
                        array('id' => 'f1.field_num', 'propName' => 'f1.field_label')           
            )
            ->joinLeft(
                   array('f2' => 'field_summary'),
                   'f2.pk_field = f1.fk_field_describes',
                    array('objName' => 'f2.field_label') 
            )
            ->where ("f1.field_type = 'Property' and f1.source_id = '" . $dataTableName . "'");
        $stmt           = $db->query($select);
        $rows   = $stmt->fetchAll();
        
        $layout = array();       
        /*$layout[0] = array(
            'field'     =>  'id',
            'name'      =>  'ID',
            'width'     =>  '40px',
            'editable'  =>  false
        );*/
        $layout[0] = array(
            'field'     =>  'propName',
            'name'      =>  'Property Field',
            'width'     =>  '100px',
            'editable'  =>  false
        );
        $layout[1] = array(
            'field'     =>  'objName',
            'name'      =>  'Describes Field',
            'width'     =>  '100px',
            'editable'  =>  true
        );
        
        Zend_Loader::loadClass('Layout_DataGridHelper');
        $dgHelper = new Layout_DataGridHelper();
        $dgHelper->setDataRecords($rows, "id");
        $dgHelper->layout = $layout;
        echo Zend_Json::encode($dgHelper);
    }
    
    function getNonpropertyDataAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $dataTableName  = $_REQUEST['dataTableName'];
        
        $db = Zend_Registry::get('db');
        $select = $db->select()
            ->distinct()
            ->from  (
                        array('f1' => 'field_summary'),
                        array('id' => 'f1.pk_field',
                              'propName1' => 'f1.field_label',
                              'f1.field_type',
                              'propName' => new Zend_Db_Expr("CONCAT(f1.field_label, ' (', f1.field_type, ')')")
                        )         
            )
            ->where ("f1.field_type <> 'Property' and f1.field_type <> 'Ignore' and f1.source_id = '" . $dataTableName . "'");
        $stmt           = $db->query($select);
        $rows   = $stmt->fetchAll();
        
        $layout = array();
        array_push($layout, array('field' =>  'propName', 'name' => 'Field Name', 'width' => '200px', 'editable' => false));
        /*$layout[0] = array(
            'field'     =>  'propName',
            'name'      =>  'Field Name',
            'width'     =>  '80px',
            'editable'  =>  false
        );
        $layout[1] = array(
            'field'     =>  'field_type',
            'name'      =>  'Field Type',
            'width'     =>  '150px',
            'editable'  =>  false
        );*/
        
        Zend_Loader::loadClass('Layout_DataGridHelper');
        $dgHelper = new Layout_DataGridHelper();
        $dgHelper->setDataRecords($rows, "id");
        $dgHelper->layout = $layout;
        echo Zend_Json::encode($dgHelper);
    }
    
    function savePropertyMappingsAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $dataTableName  = $_REQUEST['dataTableName']; 
        $nonPropID      = $_REQUEST['nonPropID']; 
        $recordArray    = Zend_Json::decode($_REQUEST['datastore'], Zend_Json::TYPE_OBJECT);
        
        Zend_Loader::loadClass('Table_FieldSummary');
        $fieldSummaryTable = new Table_FieldSummary();
        foreach ($recordArray as $record) 
        {
            $where = "field_num = " . $record->propID . " and source_id = '" . $dataTableName . "'";
            $data = array('fk_field_describes'   => $nonPropID);
            $fieldSummaryTable->update($data, $where);
        }
        echo "success!";
    }
    
    function getSamplePropertyDataAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $dataTableName  = $_REQUEST['dataTableName'];
        
        $samplesArray = array();
        
        $db = Zend_Registry::get('db');        
        $select = $db->select()
            ->distinct()
            ->from  (
                        array('f1' => 'field_summary'),
                        array('f1.fk_field_describes')            
            )
            ->joinLeft(
                       array('f2' => 'field_summary'),
                       'f2.pk_field = f1.fk_field_describes',
                        array('objField' => 'f2.field_name', 'objName' => 'f2.field_label') 
                )
            ->where ('f1.source_id = ?', $dataTableName)
            ->where ('f1.fk_field_describes is not null')
            ->order ('objName');
        $stmt           = $db->query($select);
        $objFieldRows   = $stmt->fetchAll();
        //echo Zend_Json::encode($objFieldRows);
        foreach ($objFieldRows as $objFieldRow) 
        {
            $db = Zend_Registry::get('db');
            $select = $db->select()
                ->distinct()
                ->from  (
                            array('f1' => 'field_summary'),
                            array('propField' => 'f1.field_name', 'propName' => 'f1.field_label')           
                )
                ->joinLeft(
                       array('f2' => 'field_summary'),
                       'f2.pk_field = f1.fk_field_describes',
                        array('objField' => 'f2.field_name', 'objName' => 'f2.field_label') 
                )
                ->where ('f2.source_id = ?', $dataTableName)
                ->where ('f2.field_name = ?', $objFieldRow['objField']);
            $stmt   = $db->query($select);
            $rows   = $stmt->fetchAll();
            
            $fieldsToQuery  = array();
            array_push($fieldsToQuery, $objFieldRow['objField']);
            
            $headerRow      = array();            
            $headerRow[0] = $objFieldRow['objName'];
            $i = 1;
            foreach ($rows as $row) 
            {
                array_push($fieldsToQuery, $row['propField']);                
                $headerRow[$i] = $row['propName'];
                ++$i;
            }            
            //echo Zend_Json::encode($fieldsToQuery);
            
            //query for data:
            $select = $db->select()
                ->distinct()
                ->from  (
                            array('f1' => $dataTableName),
                            $fieldsToQuery          
                )
                ->limit(3, 0); //count of rows, number of rows to skip
            $stmt   = $db->query($select);
            $dataRows   = $stmt->fetchAll();
            $i = 0;
            
            $records = array();
            foreach($dataRows as $row)
            {
                $record = array();
                foreach($fieldsToQuery as $field)
                {
                    array_push($record, $row[$field]);
                }
                array_push($records, $record);
            }
            array_unshift($records, $headerRow);
            array_push($samplesArray, $records);
            //$samplesArray[0] = $dataRows;
            //echo Zend_Json::encode($headerRow);
        }        
        echo Zend_Json::encode($samplesArray);
    }
}
