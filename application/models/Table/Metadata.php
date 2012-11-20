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
}