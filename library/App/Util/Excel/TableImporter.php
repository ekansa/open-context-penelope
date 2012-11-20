<?php
/**
 *--------
 *Summary:
 *--------
 *This class parses an excel spreadsheet into an easy-to-use data structure, and has
 *some helper functions to assist in generating a new table.
 *
 *-------------
 *Dependencies:
 *-------------
 *  - App/Util/Excel/Reader.php  (which in turn references 'oleread.inc').
 *  
 *--------------
 *Special Notes:
 *--------------
 */
class TableImporter
{
    public $_projID;
    public $_projUUID;
    public $_description;
    public $_user;
    public $_fileNameAndPath;
    public $_readFileName;
    public $_fileName;
    public $_tableName;
    public $_numColumns;
    public $_numRows;
    public $_sheet;
    public $_headerRow = array();
    public $_headerAliasRow = array();
    public $_dataRecords = array();
    public $_columnFormats = array();
    
    function TableImporter($fileNameAndPath, $projID, $projectUUID, $description, $ReadFilename)
    {
        //populate private data:
        $this->_fileNameAndPath = $fileNameAndPath;
        $this->_projID = $projID;
        $this->_projUUID = $projectUUID;
        $this->_description = $description;
        $this->_readFileName = $ReadFilename;
        
        //trim path:
        $arrTmp = explode("\\", $fileNameAndPath);
        $this->_fileName = $arrTmp[sizeof($arrTmp)-1];
        
        //generate table name:
        $this->_tableName = $this->generateTableName($ReadFilename);
        //echo "<br />" . $this->_tableName;
        
        
        Zend_Loader::loadClass('User');
        $this->_user = User::getCurrentUser();
    }
    
    /**-------------------------------------------------------------------------
     * function loadData()
     * -------------------------------------------------------------------------
     * Uses the Excel Reader Utility to parses Excel records into this object.
     * Note that the Excel Reader has a 1-based index, but these arrays
     * have a 0-based index.
     */
    function loadData()
    {
        //1.  initialize error reporting settings:
        error_reporting(E_ALL ^ E_NOTICE); // supresses the "null value in position X" messages:
        
        //2.  parse excel file:
        require_once 'App/Util/Excel/Reader.php';        
        //require_once 'App/Util/StringFunctions.php';
        $data = new Spreadsheet_Excel_Reader();
        //$data->setOutputEncoding('CP1251');
        $data->setOutputEncoding('UTF-8');
        $data->read($this->_fileNameAndPath);
        //dump column formats here:
               
        /*Zend_Debug::dump($data->dateFormats);        
        Zend_Debug::dump($data->formatRecords); 
        Zend_Debug::dump($data);*/
        
        
        
        //3.  populate private variables:
        $this->_sheet = $data->sheets[0];
        $this->_numColumns = $this->_sheet['numCols'];
        $this->_numRows = $this->_sheet['numRows'];
         
        /*
        //3.5. determine the datatypes for each of the excel columns
        //by iterating through the calculated datatypes:
        // - float takes precedence over int
        // - string takes precedence over everything
        
        //Zend_Debug::dump($this->_sheet['cellsInfo']);
        
        //initialize formats to text:
        for ($i = 0; $i <= $this->_numColumns; $i++)
            $this->_columnFormats[$i] = "text";
            
        //if 'cellsInfo' indicates that the data types are
        //dates, ints, or floats, update the _columnFormats
        //array appropriately:
        for($i=1; $i <= $this->_numRows; $i++)
        {
            //echo $this->_sheet['cellsInfo'][$i][2];
            //Zend_Debug::dump($this->_sheet['cellsInfo'][$i]);
            if($this->_sheet['cellsInfo'][$i] != null)
            {
                $entries = $this->_sheet['cellsInfo'][$i];
                Zend_Debug::dump($entries); 
                for($j=1; $j <= $this->_numColumns; $j++)
                {
                    if($entries[$j] != null)
                    {
                        $entry = $entries[$j];
                        //determine the data type of the field:
                        $isInt      = ((strpos($entry['raw'], 'int(')) != -1) ? true : false;
                        $isFloat    = ((strpos($entry['raw'], 'float(')) != -1) ? true : false; 
                        $isDate     = ((strpos($entry['raw'], 'date(')) != -1) ? true : false;
                        
                        $text = $entry['raw'];
                        $p = strrpos($text,"i");
                        print $entry['raw'];
                        //print "$location_of_character";
                        //$p = strrpos($entry['raw'], "i"); // . "<br />";
                        print $p . "<br />";
                        if($isInt)
                        {
                            echo $j-1 . " isINT <br />";
                            if($this->_columnFormats[$j-1] != "float")
                                $this->_columnFormats[$j-1] = "int";
                        }
                        else if($isFloat)
                        {
                            echo $j-1 . " isFloat <br />";
                            $this->_columnFormats[$j-1] = "float";
                        }
                        else if($isDate)
                        {
                            
                            echo $j-1 . " isDate <br />";
                            $this->_columnFormats[$j-1] = "date";
                        }  
                    }
                }
            }
        }
        Zend_Debug::dump($this->_columnFormats);
        */
         
         //4.  populate column header and header aliases:                     
         for ($i = 1; $i <= $this->_numColumns; $i++)
         {
            if(strlen($this->_sheet['cells'][1][$i])<1){
                $this->_headerAliasRow[$i-1] = "Blank_".$i; //avoid error if field heading is blank
            }
            else{
                $this->_headerAliasRow[$i-1]  = $this->_sheet['cells'][1][$i];
            }
            $this->_headerRow[$i-1]       = "field_" . $i;
         }
         
         //5.  populate data:
         for ($i = 2; $i <= $this->_numRows; $i++) // iterate through each row (starting with the second row)         
         {
            $dataRecord = array();            
            for ($j = 1; $j <= $this->_numColumns; $j++){ //iterate through each column
                //@$cleanData = utf8_encode($this->_sheet['cells'][$i][$j]);
                //$cleanData = mb_convert_encoding($this->_sheet['cells'][$i][$j], 'UTF8');
                $cleanData = ($this->_sheet['cells'][$i][$j]);
                if(!$cleanData){
                    $cleanData = $this->_sheet['cells'][$i][$j];
                }
                $dataRecord[$j-1] = $cleanData;
            }
            $this->_dataRecords[$i-2] = $dataRecord;
         }
         
         //$this->writeDataToBrowser();
    }
    
    /**-------------------------------------------------------------------------
     * function commitDataToDB()
     * -------------------------------------------------------------------------
     * Takes the data from the $_headerRow, $_headerAliasRow, and $_dataRecords
     * arrays and populates the relevant database tables:
     * - field_summary
     * - file_summary
     * - dynamic table that's created on-the-fly
     */
    function commitDataToDB()
    {
        //include references to needed objects:
        require_once 'App/Util/StringFunctions.php';
        Zend_Loader::loadClass('Table_FileSummary');
        Zend_Loader::loadClass('Table_FieldSummary');
        Zend_Loader::loadClass('Table_Dynamic');
        
        if($this->_dataRecords == null || $this->_headerRow == null)
        {
            echo "Please initialize data before trying to add it to the database.";
            return;
        }

        //update temporary values regarding user's state:        
        $this->updateUserSession();
        
        //1.  insert record into file_summary table:
        $data = array(
               'fk_project' =>  $this->_projID,
               'project_id' => $this->_projUUID,
               'fk_user' =>     $this->_user->id,
               'filename' =>    $this->_readFileName,
               'source_id' =>    $this->_tableName,
               'description' => $this->_description,
               'numrows' =>     $this->_numRows,
               'numcols' =>     $this->_numColumns,
               'active_tab' =>  'yes',
               'process_step' =>'upload'
            );
        $tableFileSummary = new Table_FileSummary();
        $tableFileSummary->insert($data);
        //Zend_Debug::dump($data);

        
        //2.  dynamically create a new table:
        $fieldsToCreate = implode(" text,", $this->_headerRow) ." text CHARACTER SET utf8";
        $schemaSql = "CREATE TABLE $this->_tableName  (
					id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
					$fieldsToCreate
					)ENGINE=MyISAM DEFAULT CHARSET=utf8;
					";
        //todo:  add the indexes when you create the table.
        //echo $schemaSql;
        $db = Zend_Registry::get('db');
        $db->getConnection()->exec($schemaSql);
        
        $sql = "SET collation_connection =  utf8_general_ci;";
	$db->query($sql, 2);
	$sql = "SET NAMES utf8;";
	$db->query($sql, 2);
        
        $alterSQL = "ALTER TABLE ".$this->_tableName." DEFAULT CHARACTER SET utf8 COLLATE  utf8_general_ci;";
        $db->query($alterSQL);
        
        //3.  insert the newly generated fields (and their aliases) into the field_summary table:
        for ($i = 0; $i < $this->_numColumns; $i++)
        {
            $data = array(
                'project_id'   => $this->_projUUID,
                'source_id'          =>  $this->_tableName,
                'field_num'         =>  $i+1,
                'field_name'        =>  $this->_headerRow[$i],
                'field_label'       =>  $this->_headerAliasRow[$i]
             );
            $tableFieldSummary = new Table_FieldSummary();
            $tableFieldSummary->insert($data);
            //Zend_Debug::dump($data);
            
            if($i<20){
                $indexSQL = "CREATE INDEX fInd_".($i+1)." ON ".$this->_tableName."(".$this->_headerRow[$i]."(10));";
                $db->query($indexSQL);
            }
            
        }
        
        //4.  make a reference to the newly-created table using the "TableDynamic" class:
        $newTableArgs = array( 'name' => $this->_tableName);
        $newTable = new Table_Dynamic($newTableArgs);
        
        //5.  insert the data records into the new table:
        for ($i = 0; $i < sizeof($this->_dataRecords); $i++) // iterate through each row (starting with the second row)         
        {
            $data = array();
            for ($j = 0; $j < $this->_numColumns; $j++) //iterate through each column
                $data[$this->_headerRow[$j]] = $this->_dataRecords[$i][$j];
            
            $newTable->insert($data);
            //Zend_Debug::dump($data);
        }
        return $this->_tableName;
    }
    
    /**-------------------------------------------------------------------------
     * function generateTableName()
     * -------------------------------------------------------------------------
     * Generates and arbitrary table name.
     */
    function generateTableName($ReadFilename)
    {
        //generate arbitrary table name:
        $randomString = "";
        for($i=0; $i<3; $i++)
            $randomString .= chr(rand(0,25)+65);
            
        $tableSuffix = $this->genID();
        
        $endTab = md5($ReadFilename);
        $endTab = substr($endTab,0,9);
        
        $firstProjChar = substr($this->_projID,0,4);        
        return "z_" . $firstProjChar . "_" . $endTab;
    }
    
    /**-------------------------------------------------------------------------
     * function genID()
     * -------------------------------------------------------------------------
     * Generates UIDs.  Not yet sure of their significance.
     */
    function genID()
    {
        list($usec, $sec) = explode(' ', microtime());
        $make_seed = (float) $sec + ((float) $usec * 100000);
        mt_srand($make_seed);
        $string = "1234567890";
        $length = 6;
        $key = "";
        for($i=0; $i < $length; $i++)
        {
            $key .= $string{mt_rand(0,strlen($string)-1)};
        }
        return $key;
    }
    
        
    /**-------------------------------------------------------------------------
     * function writeDataToBrowser()
     * -------------------------------------------------------------------------
     * Outputs the $this->_dataRecords array to the browser (for debugging
     * purposes).
     */
    function writeDataToBrowser()
    {
        echo "Writing data to the browser...<br />" ;
        echo "Length: " . sizeof($this->_dataRecords) . "<br />";
        error_reporting(E_ALL ^ E_NOTICE);
        echo "<table border='1'>";
        
        //output header row:
        echo "<tr>";
        for ($i = 0; $i < sizeof($this->_headerAliasRow); $i++)
        {
            echo "<th>";
            if($this->_headerAliasRow[$i] != NULL)
                echo $this->_headerAliasRow[$i];
            else
                echo "empty";
            echo "</th>";
        }
        echo "</tr>";
        
        //output data rows:
        for ($i = 0; $i < sizeof($this->_dataRecords); $i++)
        {
            echo "<tr>";
            for ($j = 0; $j < sizeof($this->_dataRecords[$i]); $j++)
            {
                echo "<td>";
                if($this->_dataRecords[$i][$j] != NULL)
                    echo $this->_dataRecords[$i][$j];
                else
                    echo "empty";
                echo "</td>";                
            }
            echo "</tr>";
        }
        echo "</table>";
    }
    
    function updateUserSession()
    {
        //Taken from Eric's excel_read.php file.  Looks like it's keeping track
        //user's interim work (not yet completed);
        
        /*
        
            //this deactivates previous tables imported in this session
            $de_act_old ="UPDATE file_summary
            SET file_summary.active_tab = 'no'
            WHERE file_summary.project_id = '$projid'
            ";
            
            mysql_query($de_act_old);
            
           $delete_tacker = "DELETE FROM
            progress_track
            WHERE progress_track.userid = '$user'
            ";
            
            mysql_query($delete_tacker);
            
            $add_tacker = "INSERT INTO progress_track (userid, session_id, project_id, source_id, active_page)
            VALUES ('$user', '$sesid', '$projid', '$tabname', 'excel_read')
            ";
            
            mysql_query($add_tacker);
            
        */
        
    }
    
    
    
    function excel_date($serial){
        //from http://richardlynch.blogspot.com/2007/07/php-microsoft-excel-reader-and-serial.html
        // Excel/Lotus 123 have a bug with 29-02-1900. 1900 is not a
        // leap year, but Excel/Lotus 123 think it is...
        if ($serial == 60) {
            $day = 29;
            $month = 2;
            $year = 1900;
            
            return sprintf('%02d/%02d/%04d', $month, $day, $year);
        }
        else if ($serial < 60) {
            // Because of the 29-02-1900 bug, any serial date 
            // under 60 is one off... Compensate.
            $serial++;
        }
        
        // Modified Julian to DMY calculation with an addition of 2415019
        $l = $serial + 68569 + 2415019;
        $n = floor(( 4 * $l ) / 146097);
        $l = $l - floor(( 146097 * $n + 3 ) / 4);
        $i = floor(( 4000 * ( $l + 1 ) ) / 1461001);
        $l = $l - floor(( 1461 * $i ) / 4) + 31;
        $j = floor(( 80 * $l ) / 2447);
        $day = $l - floor(( 2447 * $j ) / 80);
        $l = floor($j / 11);
        $month = $j + 2 - ( 12 * $l );
        $year = 100 * ( $n - 49 ) + $i + $l;
        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }
    
    
    
    
}