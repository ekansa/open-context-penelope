<?php
class Layout_DataGridHelper
{
    public $layout;
    public $dataRecords;
    //public $tableName;
    
    function Layout_DataGridHelper()
    {
        //$this->tableName = $_tableName;
    }
    
    public function setLayoutFromTableFieldSummary($dataTableName)
    {        
        Zend_Loader::loadClass('Table_FieldSummary');
        $fieldNameTable = new Table_FieldSummary();
        $fieldNameRows = $fieldNameTable->fetchAll("source_id = '" . $dataTableName . "'");
        $this->layout = array();
        $i = 0;
        foreach ($fieldNameRows as $row) 
        { 
            $layoutItem = array(
                'field'     =>  $row->field_name,
                'name'      =>  $row->field_label
             );
            $this->layout[$i] = $layoutItem;
            ++$i;
        }  
    }
    
    public function setDataFromDataTable($dataTableName, $whereClause)
    {        
        //echo $this->tableName;
        Zend_Loader::loadClass('Zend_Dojo_Data');
        Zend_Loader::loadClass('Table_Dynamic');
        
        $this->dataRecords = new Zend_Dojo_Data();

        //make a reference to the data table using the "TableDynamic" class:
        $dataTableArgs = array( 'name' => $dataTableName);
        
        $dataTable = new Table_Dynamic($dataTableArgs);
        $this->dataRecords->setIdentifier("id");
        if($whereClause == null)
            $rows = $dataTable->fetchAll($dataTable->select()->limit(10, 0));
        else
            $rows = $dataTable->fetchAll($dataTable->select()->where($whereClause)->limit(10, 0));   
        $this->dataRecords->setItems($rows);
        
        //HACK:  Since this object is created in serialized form already,
        //we have to de-serialize it so that is can be re-serialized along with
        //the other properties of DataGridHelper in the final AJAX return:
        $this->dataRecords = Zend_Json::decode($this->dataRecords); 
    }
    
    function setDataRecords($rows, $id)
    {
        Zend_Loader::loadClass('Zend_Dojo_Data');
        $this->dataRecords = new Zend_Dojo_Data();
        $this->dataRecords->setIdentifier($id);
        $this->dataRecords->setItems($rows);
        //HACK:  Since this object is created in serialized form already,
        //we have to de-serialize it so that is can be re-serialized along with
        //the other properties of DataGridHelper in the final AJAX return:
        $this->dataRecords = Zend_Json::decode($this->dataRecords);    
    }
}