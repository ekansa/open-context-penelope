<?php
class Table_Project extends Zend_Db_Table_Abstract
{
    protected $_name = 'project_list';
    protected $_primary = 'pk_project';
    protected $_dependentTables = array('Table_FileSummary', 'Table_Variable', 'Table_Metadata');
    
    public function init()
    {
        $db = Zend_Registry::get('db');
        $sql = "SET collation_connection = utf8_unicode_ci;";
		  $db->query($sql, 2);
		  $sql = "SET NAMES utf8;";
		  $db->query($sql, 2);
    }
}