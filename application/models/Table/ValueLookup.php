<?php
class Table_ValueLookup extends Zend_Db_Table_Abstract
{
    protected $_name = 'w_val_ids_lookup';
    protected $_primary = 'value_uuid'; // really this should be a composite primary key! 

}