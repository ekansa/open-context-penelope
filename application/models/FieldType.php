<?php
class FieldType
{
    public $id;
    public $name;
    public $description;
    
    function FieldType($_data) //where $_data is a "ResultRowObject"
    {
        $this->id           = $_data->PK_FIELD_TYPE;
        $this->name         = $_data->FIELD_TYPE_NAME;
        $this->description  = $_data->FIELD_TYPE_DESCRIPTION;
    }
}