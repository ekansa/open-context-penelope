<?php
class Table_FileSummary extends Zend_Db_Table_Abstract
{
    protected $_name = 'file_summary';   
    protected $_primary = 'pk_file_summary'; 
    protected $_dependentTables = array('Table_FieldSummary', 'Table_License');
    
    //association to the 'project_list' table:
    protected $_referenceMap = array(
        'FK' => array(
            'columns' => 'fk_project',
            'refTableClass' => 'Table_Project',
            'refColumns' => 'pk_project'
        )        
    );
}