<?php
class Table_FieldSummary extends Zend_Db_Table
{
    protected $_name    = 'field_summary';
    protected $_primary = 'source_id'; 
    protected $_dependentTables = array('Table_Class'); //not sure this is being used
    
    //association to the 'file_summary' table:
    protected $_referenceMap = array(
        'FK' => array(
            'columns' => 'source_id',
            'refTableClass' => 'Table_FileSummary',
            'refColumns' => 'source_id'
        )        
    );
    
    public function init()
    {
        $db = Zend_Registry::get('db');
        $sql = "SET collation_connection = utf8_unicode_ci;";
		  $db->query($sql, 2);
		  $sql = "SET NAMES utf8;";
		  $db->query($sql, 2);
    }
    
}