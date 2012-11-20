<?php
require_once 'Item.php';
require_once 'App/Xml/XmlGenerator.php';
class Diary extends Item
{
    public $project; 
    public $projectUUID;    
    public $diaryUUID;
    public $diaryText;
    public $dataTableName;
    public $timestamp;
    public $links = array();
    public $notes   = array();
    
    function Diary($diaryUUID, $project)
    {
        Zend_Loader::loadClass('Table_Diary');
        $this->diaryUUID    = $diaryUUID;
        $this->project      = $project;
        $diary              = new Table_Diary();
        $diaryRow           = $diary->fetchRow("uuid  = '" . $this->diaryUUID . "'");
        $this->projectUUID  = $diaryRow->project_id;
        $this->diaryText    = $diaryRow->diary_text_original;
        $this->timestamp    = $diaryRow->last_modified_timestamp;
        $this->dataTableName= $diaryRow->source_id;
    }
    
    //required to be implemented by the "Item" abstract class:
    public function getItemType() { return App_Constants::DIARY; }
    public function getUUID() { return $this->diaryUUID; }
    public function getProjectUUID() { return $this->projectUUID; }
    //public function getObservations() { return $this->_getObservations($this->diaryUUID); }
       
    
}
