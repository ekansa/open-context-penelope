<?php
class SpatialClass
{
    /*
    corresponds to sp_classes
    -----------------------
    pk_class, project_id, source_id, class_uuid, class_label, class_icon, sm_class_icon
    */
    public $classUUID;
    public $name;
    public $icon;
    public $iconSmall;
    
    function SpatialClass($classUUID)
    {
        $this->classUUID = $classUUID;
        
        //query table and populate properties:
        Zend_Loader::loadClass('Table_Class');        
        $class              = new Table_Class();
        $classRow           = $class->fetchRow("class_uuid  = '" . $classUUID . "'");
        $this->name         = $classRow->class_label;
        $this->icon         = $classRow->class_icon;
        $this->iconSmall    = $classRow->sm_class_icon;
    }
}


