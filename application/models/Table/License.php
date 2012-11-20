<?php
class Table_License extends Zend_Db_Table
{
    protected $_name = 'w_lu_creative_commons';
    
    //association to the 'file_summary' table:
    protected $_referenceMap = array(
        'FK' => array(
            'columns' => 'fk_license',
            'refTableClass' => 'Table_FileSummary',
            'refColumns' => 'pk_license'
        )        
    );
}