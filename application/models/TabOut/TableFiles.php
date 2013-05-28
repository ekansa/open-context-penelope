<?php
/* This class creates files based on data saved for export.
 * It makes csv, zip (with csv), and gzipped csv files.
 * 
 */

class TabOut_TableFiles  {
    
	 public $db; //database connection object
	 
	 public $penelopeTabID; //name of the table in Penelope
	 public $tableID; //ID for the table
	 
	 public $savedFileSizes; //array of the saved files names and sizes in bytes
	 
	 
	 public $fileExtensions = array("csv" => ".csv",
											  "zip" => ".zip",
											  "gzip" => ".csv.gz"
											  );
	 
	 const maxRecordSize = 50000;
	 const CSVdirectory = "csv-export";
	 
	 function makeSaveCSV(){
		  
		  $tablePublishObj = new TabOut_TablePublish;
		  $tablePublishObj->penelopeTabID = $this->penelopeTabID;
		  
		  $tablePublishObj->getSavedMetadata();
		  
		  $baseFilename = $tablePublishObj->tableID;
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
		  
		  $this->saveCSV(self::CSVdirectory, $baseFilename, $data);
		  $this->saveGZIP(self::CSVdirectory, $baseFilename, $data);
		  $this->saveZIP(self::CSVdirectory, $baseFilename, $data);
		  
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
	 function saveCSV($itemDir, $baseFilename, $csv){
		
		  $fileExtensions = $this->fileExtensions;
		  $success = false;
		
		  try{
				iconv_set_encoding("internal_encoding", "UTF-8");
				iconv_set_encoding("output_encoding", "UTF-8");
				$fp = fopen($itemDir."/".$baseFilename.$fileExtensions["csv"], 'w');
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
	 
	 
	 //save the file in a ZIP directory correct correct directory
	 function saveZIP($itemDir, $baseFilename, $csv){
		
		  $fileExtensions = $this->fileExtensions;
		  $success = false;
		
		  try{
				$zip = new ZipArchive();
				$zipFileName = $itemDir."/".$baseFilename.$fileExtensions["zip"];
				$csvFileName = $itemDir."/".$baseFilename.$fileExtensions["csv"];
				if($zip->open($zipFileName, ZipArchive::CREATE)!==TRUE){
					 echo "can't create a zip file";
					 die;
				}
				
				iconv_set_encoding("internal_encoding", "UTF-8");
				iconv_set_encoding("output_encoding", "UTF-8");
				
				$zip->addFromString($csvFileName, $csv);
				$zip->close();
				
				$success = true;
		  }
		  catch (Zend_Exception $e){
				$success = false; //save failure
				echo (string)$e;
				die;
		  }
		
		  return $success;
	 }
	 
	 
	 //save the file as a GZIP file
	 function saveGZIP($itemDir, $baseFilename, $csv){
		
		  $fileExtensions = $this->fileExtensions;
		  $success = false;
		
		  try{
				
				$gzFileName = $itemDir."/".$baseFilename.$fileExtensions["gzip"];
				
				iconv_set_encoding("internal_encoding", "UTF-8");
				iconv_set_encoding("output_encoding", "UTF-8");
				
				$gz = gzopen($gzFileName,'w9');
				gzwrite($gz, $csv);
				gzclose($gz);
				
				$success = true;
		  }
		  catch (Zend_Exception $e){
				$success = false; //save failure
				echo (string)$e;
				die;
		  }
		
		  return $success;
	 }
	 
	 
	 
	 //populate the saveFileSizes array with saved filesizes
	 function getAllFileSizes($baseFilename){
		  
		  $fileExtensions = $this->fileExtensions;
		  foreach($fileExtensions as $fileType => $ext){
				$this->getFileSize(self::CSVdirectory, $baseFilename, $fileType);
		  }
	 }
	 
	 
	 
	 //get the filesize of a given file, returns false if not found or wrong extension
	 function getFileSize($itemDir, $baseFilename, $fileType){
		  
		  $savedFileSizes = $this->savedFileSizes;
		  if(!is_array($savedFileSizes)){
				$savedFileSizes  = array();
		  }
		  
		  $fileExtensions = $this->fileExtensions;
		  if(array_key_exists($fileType, $fileExtensions)){
				
				$filename = $baseFilename.$fileExtensions[$fileType];
				$dirFilename = $itemDir."/".$filename ;
				if(!file_exists($dirFilename)){
					 $fileSize = false;
					 $fileSizeHuman = false;
				}
				else{
					 $fileSize = filesize($dirFilename);
					 $fileSizeHuman = $this->human_filesize($fileSize,1);
					 $sha1 = sha1_file($dirFilename);
				}
				
				$savedFileSizes[$fileType] = array("filename" => $filename,
															  "bytes" => $fileSize,
															  "size-note" => $fileSizeHuman,
															  "sha1-checksum" => $sha1);
				
				$this->savedFileSizes = $savedFileSizes;
				
				return $fileSize;
		  }
		  else{
				return false;
		  }
		  
	 }
	 
	 
	 //convert bytes into something easy to read
	 function human_filesize($bytes, $decimals = 2) {
		  $sz = 'BKMGTP';
		  $factor = floor((strlen($bytes) - 1) / 3);
		  return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
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
