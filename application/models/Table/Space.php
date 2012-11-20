<?php
class Table_Space extends Zend_Db_Table_Abstract
{
    protected $_name = 'space';
    protected $_primary = 'hash_fcntxt';
    protected $_dependentTables = array('Table_Class');
}