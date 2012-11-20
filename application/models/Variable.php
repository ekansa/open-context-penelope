<?php
class Variable
{
    /*
    corresponds to var_tab
    ------------------------
    project_id, source_id, var_hash, variable_uuid, var_label, var_type, last_modified_timestamp
    */
    
    //public variables
    public $variableUUID;
    public $varLabel;
    public $varType;
    public $projectUUID;
    public $timestamp;
    
    
    function Variable($variableUUID)
    {
        Zend_Loader::loadClass('Table_Variable');
        $variable           = new Table_Variable();
        $varRecord          = $variable->fetchRow("variable_uuid= '" . $variableUUID . "'");
        if(isset($varRecord))
        {
            $this->variableUUID = $variableUUID;
            $this->varLabel     = $varRecord->var_label;
            $this->varType      = $varRecord->var_type;
            $this->projectUUID  = $varRecord->project_id;
            $this->timestamp    = $varRecord->last_modified_timestamp;
        }
        else
        {
            echo $variableUUID . ' cannot be found in the var_tab table';
            if($variableUUID == 'NOTES')
                echo '<br />WARNING:  The Variable.php object isn\'t really meant for NOTES variables.';
        }
    }
}


