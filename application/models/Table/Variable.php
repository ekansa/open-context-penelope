<?php
class Table_Variable extends Zend_Db_Table_Abstract
{
    protected $_name = 'var_tab';
    protected $_primary = 'variable_uuid';
    protected $_dependentTables = array('Table_VariableNotes');
    
    //association to the 'project_list' table:
    protected $_referenceMap = array(
        'FK' => array(
            'columns' => 'project_id',
            'refTableClass' => 'Table_Project',
            'refColumns' => 'project_id'
        ),
        'prop' => array(
            'columns' => 'variable_uuid',         //foreign key
            'refTableClass' => 'Table_Property',
            'refColumns' => 'variable_uuid'       //primary key
        )
    );
}