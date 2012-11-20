<?php
class Value
{
    /*
    corresponds to val_tab
    ------------------------
    project_id, source_id, text_scram, val_text, value_uuid, val_num, last_modified_timestamp
    */
    
    //public variables
    public $valueUUID;
    public $valText;
    public $valNum;
    public $projectUUID;
    public $timestamp;
    
    function Value($valueUUID)
    {
        Zend_Loader::loadClass('Table_Value');
        $value              = new Table_Value();
        $valRecord          = $value->fetchRow("value_uuid= '" . $valueUUID . "'");
        
        $this->valueUUID    = $valueUUID;
        $this->valText      = $valRecord->val_text;
        $this->valNum       = $valRecord->val_num;
        $this->projectUUID  = $valRecord->project_id;
        $this->timestamp    = $valRecord->last_modified_timestamp;
    }
}


