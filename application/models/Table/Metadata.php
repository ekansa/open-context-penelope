<?php
class Table_Metadata extends Zend_Db_Table_Abstract
{
    protected $_name = 'dcmeta_proj';
    protected $_primary = 'hash_id';
    
    //association to the 'project_list' table:
    protected $_referenceMap = array(
        'FK' => array(
            'columns' => 'project_id',
            'refTableClass' => 'Table_Project',
            'refColumns' => 'project_id'
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