<?php
class Table_VariableNotes extends Zend_Db_Table_Abstract
{
    protected $_name = 'var_notes';
    protected $_primary = 'note_uuid';
    protected $_referenceMap = array(
        'notes' => array(
            'columns' => 'variable_uuid',         //foreign key
            'refTableClass' => 'Table_Variable',
            'refColumns' => 'variable_uuid'       //primary key
        )
    );
}

