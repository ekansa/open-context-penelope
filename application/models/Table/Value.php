<?php
class Table_Value extends Zend_Db_Table_Abstract
{
    protected $_name = 'val_tab';
    protected $_primary = 'text_scram';
    protected $_referenceMap = array (
        'prop' => array(
            'columns' => 'value_uuid',         //foreign key
            'refTableClass' => 'Table_Property',
            'refColumns' => 'value_uuid'       //primary key
        )      
    );
}