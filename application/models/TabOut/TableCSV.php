<?php
/* This class makes a table object for Open Context
 * based on a table created by the TabOut_Table class
 * it generates appropriate metadata as well as JSON data for Open Context's table
 */

class TabOut_TableCSV  {
    
	 public $db; //database connection object
	 
	 public $penelopeTabID; //name of the table in Penelope
	 public $tableID; //ID for the table
	 
	 const maxRecordSize = 50000;
	 const CSVdirectory = "csv-export";
	 
	 function makeSaveCSV(){
		  
		  $tablePublishObj = new TabOut_TablePublish;
		  $tablePublishObj->penelopeTabID = $this->penelopeTabID;
		  $tablePublishObj->getSavedMetadata();
		  
		  $filename = $tablePublishObj->tableID;
		  $tablePublishObj->getTableFields();
		  
		  $data = "OpenContext URI,";
		  $fieldCount = count($tablePublishObj->tableFields);
		  $i = 1;
		  foreach($tablePublishObj->tableFields as $field){
				$data.= $this->clean_csv($field);
				if($i < $fieldCount){
					 $data .= ",";	 
				}
				$i++;
		  }
		  
		  $data.="\n";
		  
		  $records = $tablePublishObj->getAllRecords();
		  //$records = $tablePublishObj->getSampleRecords();
		  foreach($records as $row){
				
				$data .= $row["uri"].",";
				$i = 1;
				foreach($row as $fieldKey => $value){
					 if(array_key_exists($fieldKey, $tablePublishObj->tableFieldsTemp)){
						  $data.= $this->escape_csv_value($value);
						  if($i < $fieldCount){
								$data .= ",";	 
						  }
						  $i++;
					 }
				}
				
				$data.="\n";
		  }
		  
		  $this->saveCSV(self::CSVdirectory, $filename, $data);
		  
		  return $data;
	 }
	 
	 
	 
	 
	 
	 
	 // clean_csv function
	 //
	 // * uses double-quotes as enclosure when necessary
	 // * uses double double-quotes to escape double-quotes 
	 // * uses CRLF as a line separator
	 //
	 function clean_csv( $field ){
		
		  if ( preg_match( '/\\r|\\n|,|"/', $field ) )
		  {
			 $field = '"' . str_replace( '"', '""', $field ) . '"';
		  }
		
		return $field;
	 }

  
	 //escape function
	 function escape_csv_value($value) {
		  $value = str_replace('"', '""', $value); // First off escape all " and make them ""
		  $value = utf8_decode($value);
		  if(preg_match('/,/', $value) or preg_match("/\n/", $value) or preg_match('/"/', $value)) { // Check if I have any commas or new lines
				return '"'.$value.'"'; // If I have new lines or commas escape them
		  } else {
				return $value; // If no new lines or commas just return the value
		  }
	 }
	 
	 
	 //save the file in the correct correct directory
	 function saveCSV($itemDir, $filename, $csv){
		
		  $success = false;
		
		  try{
				iconv_set_encoding("internal_encoding", "UTF-8");
				iconv_set_encoding("output_encoding", "UTF-8");
				$fp = fopen($itemDir."/".$filename.'.csv', 'w');
				//fwrite($fp, iconv("ISO-8859-7","UTF-8",$xml));
				//fwrite($fp, utf8_encode($xml));
				fwrite($fp, $csv);
				fclose($fp);
				$success = true;
		  }
		  catch (Zend_Exception $e){
				$success = false; //save failure
				echo (string)$e;
				die;
		  }
		
		  return $success;
	 }
	 
	 
	 
	 
	 function startDB(){
		  if(!$this->db){
				$db = Zend_Registry::get('db');
				$this->setUTFconnection($db);
				$this->db = $db;
		  }
		  else{
				$db = $this->db;
		  }
		  
		  return $db;
	 }
	 
	 function setUTFconnection($db){
		  $sql = "SET collation_connection = utf8_unicode_ci;";
		  $db->query($sql, 2);
		  $sql = "SET NAMES utf8;";
		  $db->query($sql, 2);
    }
	 

    
}  
