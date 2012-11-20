<?php
class Table_Project extends Zend_Db_Table_Abstract
{
    protected $_name = 'project_list';
    protected $_primary = 'pk_project';
    protected $_dependentTables = array('Table_FileSummary', 'Table_Variable', 'Table_Metadata');
}