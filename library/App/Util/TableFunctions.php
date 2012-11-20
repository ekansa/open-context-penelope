<?php

class TableFunctions
{
    /** This function was derived from Eric Kansa's
     * prop_gen_types.php file.
     */
    static function updateDataTypes($tableName)    
    {
        //Zend_Debug::dump($tableName);
        Zend_Loader::loadClass('Zend_Debug');
        
        //1.  get fields to query from field_summary table:
        $db = Zend_Registry::get('db');
        $select = $db->select()
            ->from  (
                        array('f' => 'field_summary'),
                        array('f.field_name', 'f.field_label')
            )
            ->where ("f.source_id = '" . $tableName . "'");
            //->where ("f.field_type = 'Property' and f.source_id = '" . $tableName . "'");
        $stmt = $db->query($select);
        $rows = $stmt->fetchAll();
        
        //if(sizeof($rows) == 0)
        //    return "No 'Property' fields have been specified.";
        
        //2.  convert this into a query string to query the data table itself:
        $fieldNameArray = array();
        $fieldLabelArray = array();
        $i = 0;
        foreach($rows as $row) 
        {
            $fieldNameArray[$i] = "f." . $row['field_name'];
            $fieldLabelArray[$i] = $row['field_label'];
            ++$i;            
        }        
        //Zend_Debug::dump($fieldArray);
        
        //3.  select all of the archeology data from the table for the "Property" fields:
        $select = $db->select()
            ->from  (
                        array('f' => $tableName),
                        $fieldNameArray
            );
        $stmt = $db->query($select);
        $rows = $stmt->fetchAll();
        $numRecords = sizeof($rows);
        
        //4.  iterate through the recordset to determine the datatype for each column:
        for($i = 0; $i < sizeof($fieldNameArray); $i++)
        {
            $columnName = substr($fieldNameArray[$i],2);            
            //set data type flags:
            $numFloats  = 0;
            $numInts    = 0;
            $numDates   = 0;
            $numStrings = 0;
            
            //Todo:  query for the number of unique values in each column (to determine which of the variables are nominal)
            $select = $db->select()
            ->distinct()
            ->from  (
                        array('f' => $tableName),
                        array($columnName)
            );
            $stmt           = $db->query($select);
            $distinctRows   = $stmt->fetchAll();
            $numUniqueVals  = sizeof($distinctRows);
        
            
            //we're going to iterate through the records 1 column at a time:
            foreach($rows as $row) 
            {
                $cellValue = trim($row[$columnName]);
                if(is_numeric($cellValue))
                {
                    if(intval($cellValue) == $cellValue)
                        ++$numInts;
                    else
                        ++$numFloats;   
                }
                else if(strtotime($cellValue))
                {
                    ++$numDates;
                }
                else
                {
                    ++$numStrings;
                }
            }
            //once we've iterated through the entire column, determine what kind
            //of data that column stores:
            
            $intRatio       = $numInts      / $numRecords;
            $floatRatio     = $numFloats    / $numRecords;            
            $dateRatio      = $numDates     / $numRecords;
            $stringRatio    = $numStrings   / $numRecords;
            
            $isMostlyStrings    = ($stringRatio > $intRatio) && ($stringRatio > $floatRatio) && ($stringRatio > $dateRatio);
            $isMostlyDates      = $dateRatio > .90;
            $isMostlyNumeric    = $intRatio + $floatRatio > .50;
            
            $proptype = "Alphanumeric";
            if($isMostlyStrings && ($numRecords / $numUniqueVals) >= 2)
                $proptype = "Nominal";  //there are a finite number of choices
            else if($isMostlyDates)
                $proptype = "Calendric";
            else if($intRatio > .98)
                $proptype = "Integer";
            else if($isMostlyNumeric)
                $proptype = "Decimal";
                
            // Update the Property Types:
            Zend_Loader::loadClass('Table_FieldSummary');
            $fieldSummaryTable = new Table_FieldSummary();
            $where =    $fieldSummaryTable->getAdapter()->quoteInto('field_name = ?', $columnName)
                    .   $fieldSummaryTable->getAdapter()->quoteInto('AND source_id = ?', $tableName);
            $data = array('prop_type'  => $proptype);
            $fieldSummaryTable->update($data, $where);
        
            //echo $numFloats . " " . $numInts . " " . $numDates . " " . $numStrings . " " . $numUniqueVals . " " . $proptype . "\n";
            echo $fieldLabelArray[$i] . ": " . $proptype . "\n";
        }
    }    
}
