<?php
class Table_Property extends Zend_Db_Table_Abstract
{
    protected $_name = 'properties';
    protected $_primary = 'prop_hash';
    protected $_dependentTables = array('Table_Variable', 'Table_Value');
}