<?php
class Table_Class extends Zend_Db_Table
{
    protected $_name = 'sp_classes';
    protected $_referenceMap = array (
        'fieldType' => array(
            'columns'       => array('fk_class'),       //foreign key in FieldSummary table
            'refTableClass' => 'Table_FieldSummary',
            'refColumns'    => 'pk_class'               //primary key in Class table
        ),
        'classType' => array(
            'columns' => 'class_uuid',                  //foreign key in FieldSummary table
            'refTableClass' => 'Table_Space',
            'refColumns' => 'class_uuid'                //primary key in Class table
        )        
    );
    
    /*$clquery = "SELECT sp_classes.sm_class_id, sp_classes.class_label, sp_classes.sm_class_icon
    FROM sp_classes
    ORDER BY sp_classes.group, sp_classes.sm_class_icon, sp_classes.class_label
    ";*/
}


