<?php
class MetadataItem
{
    public $hashID;
    public $projectUUID;    
    public $dcField;
    public $dcValue;

    function MetadataItem($_data) //where $_data is a "ResultRowObject"
    {
        $this->hashID       = $_data->hash_id;
        $this->projectUUID  = $_data->project_id;
        $this->dcField      = $_data->dc_field;
        $this->dcValue      = $_data->dc_value;
    }
}