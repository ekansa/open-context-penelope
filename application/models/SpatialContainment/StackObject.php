<?php
class SpatialContainment_StackObject extends Zend_Db_Table
{
    public $projectUUID;
    public $dataTableName;
    public $fieldRow;
    public $level;
    public $fieldList;
    public $aliasList;
    public $dataRow;
    public $index;      //used to keep track of which record is currently being processed
                        //from the records yielded by the query parameters
    public $numDataRecords = -1;
    
    function SpatialContainment_StackObject($projectUUID, $dataTableName, $fieldRow, $level, $fieldList, $aliasList, $dataRow)
    {
        //constructor
        $this->projectUUID      = $projectUUID;
        $this->dataTableName    = $dataTableName;
        $this->fieldRow         = $fieldRow;
        $this->level            = $level;
        $this->fieldList        = $fieldList;
        $this->aliasList        = $aliasList;
        $this->dataRow          = $dataRow;   
        $this->index            = 0;                
    }
}

