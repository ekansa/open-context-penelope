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
    
    public function init()
    {
        $db = Zend_Registry::get('db');
        $sql = "SET collation_connection = utf8_unicode_ci;";
		  $db->query($sql, 2);
		  $sql = "SET NAMES utf8;";
		  $db->query($sql, 2);
    }
}