<?php
class Table_UserRoleTable extends Zend_Db_Table
{
    protected $_name = 'at_user_role_table';
    
    public function init()
    {
        $db = Zend_Registry::get('db');
        $sql = "SET collation_connection = utf8_unicode_ci;";
		  $db->query($sql, 2);
		  $sql = "SET NAMES utf8;";
		  $db->query($sql, 2);
    }
}