<?php
/* This class creates files based on data saved for export.
 * It makes csv, zip (with csv), and gzipped csv files.
 * 
 */

class TabOut_TableFiles  {
    
	 public $db; //database connection object
	 
	 public $penelopeTabID; //name of the table in Penelope
	 public $tableID; //ID for the table
	 public $tablePage = 1; //table for the table segment.
	 
	 public $projects; //table projects
	 
	 public $savedFileSizes; //array of the saved files names and sizes in bytes
	 
	 public $actFileHandle; //file handle for the active file
	 
	 public $tableByteSize; //estimated size of a table (bytes), based on database query
	 public $recordCount; //number of records in the dataset
	
	 public $actGitFilePartIndex = 1; //index for the current github file part
	 public $gitFileRowBatchSize; //number of records per file in for GitHub files
	 public $gitFileHandles = false; //array of git file handles
	 
	 public $files; //array of saved files
	 
	 public $fileExtensions = array("csv" => ".csv",
											  "zip" => ".zip",
											  "gzip" => ".csv.gz",
											  "json" => ".json",
											  "json-prev" => "-prev.json"
											  );
	 
	 const maxRecordSize = 50000;
	 const previewSize = 500;
	 const CSVdirectory = "csv-export";
	 
	 const maxGitHubSize = 40000000;
	 
	 function makeSaveFiles(){
		  
		  $tablePublishObj = new TabOut_TablePublish;
		  $tablePublishObj->penelopeTabID = $this->penelopeTabID;
		  
		  $tablePublishObj->getSavedMetadata();
		  $tablePublishObj->getTableSize();
		  $recordCount = $tablePublishObj->recordCount; //total number of records
		  $this->recordCount = $recordCount;
		  $baseFilename = $tablePublishObj->tableID;
		  $baseFilename = str_replace("/", "_", $baseFilename); //do this for tables broken into different parts
		  
		  $sampleBatchSize = $tablePublishObj->getDefaultSampleSize(); //get the total number of records retrieved in a sample
		  $this->tableID = $tablePublishObj->tableID;
		  $this->projects  =  $tablePublishObj->projects;

		  $tablePublishObj->getTableFields();
		  $this->tablePublishObj = $tablePublishObj;
		  
		  $tableBytes = $this->estimateTableByteSize(); //estimate the total number of bytes needed for a table
		  
		  
		  $this->startCSVfileHandle(self::CSVdirectory, $baseFilename); //start saving the CSV file
		  $this->startGitFileHandles(self::CSVdirectory, $baseFilename); //start Git (CSV) files, if needed (segmented for big datasets)
		  
		  $data = "";
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
		  $this->saveAppendCSV($data); // save the first row of data, these are collumn names
		  $this->addGitFileFieldNames($data); //save the fieldnames to each Git (CSV) file, if needed
		  unset($data);
		  
		  
		  
		  $JSONrecs = array();
		  $previewData = false;
		  $doneRecords = 0;
		  while($doneRecords < $recordCount){
				$records = $tablePublishObj->getSampleRecords($doneRecords);
				foreach($records as $row){
					 $actJSONrec = array();
					 foreach($tablePublishObj->tableFieldsTemp as $fieldKey => $fieldLabel){
						  if($doneRecords == 0){
								//first few rows have all the field names, even if blank
								$actJSONrec[$fieldLabel] = $row[$fieldKey];
						  }
						  else{
								if(strlen($row[$fieldKey])>0){
									 //skip blanks to save memory
									 $actJSONrec[$fieldLabel] = $row[$fieldKey];
								}
						  }
					 }
					
					 $JSONrecs[] = $actJSONrec;
					 $this->insertTabRecord($row['uuid']); //save record of item association to a record.
					 
					 $i = 1;
					 $data = "";
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
					 $this->saveAppendCSV($data); // save the next row of data
					 $this->saveAppendGitCSV($data, $this->actGitFilePartIndex); //save data to the appropriate Git file (if needed)
					 unset($data);
					 
				}//end loop through row of sample records
				unset($records);
				$doneRecords = count($JSONrecs);
				if($doneRecords <= self::previewSize){
					 $previewData = $JSONrecs;
					 
					 if($doneRecords == self::previewSize){
						  $this->saveJSONprev(self::CSVdirectory, $baseFilename, $previewData); //save preview version
						  $previewData = false; //a little memory help.
					 }
				}
				
				if($this->gitFileHandles != false){
					 if($doneRecords >= ($this->gitFileRowBatchSize * $this->actGitFilePartIndex)){
						  $this->actGitFilePartIndex = $this->actGitFilePartIndex + 1; //go up an index
					 }
				}
				
		  }//end loop through 
		  
		  $this->closeCSVfileHandle();
		  $this->closeGitFileHandles();
		  
		  if(is_array($previewData)){
				$this->saveJSONprev(self::CSVdirectory, $baseFilename, $previewData); //save preview version, if not already saved
		  }
		  $this->saveJSON(self::CSVdirectory, $baseFilename, $JSONrecs);
		  unset($JSONrecs);
		  $this->CSVcompressCopies(self::CSVdirectory, $baseFilename);
		 
		  return true;
	 }
	 
	 
	 
	 function estimateTableByteSize(){
		  
		  $db = $this->startDB();
		  
		  $sql = "SELECT SUM( Data_length ) as TabBytes
		  FROM INFORMATION_SCHEMA.PARTITIONS
		  WHERE TABLE_NAME = '".$this->penelopeTabID."'; ";

		  $result =  $db->fetchAll($sql);
		  $this->tableByteSize = $result[0]["TabBytes"];
		  return $this->tableByteSize;
	 }
	 
	 
	 
	 
	 
	 //save record of the item uuid
	 //add record of UUID's association to a table
	 function insertTabRecord($uuid){
		  if($this->tableID){
				
				$db = $this->startDB();
				
				$data = array("hashID" => md5($uuid."_".$this->tableID),
								  "uuid" => $uuid,
								  "project_id" => $this->getItemProjectID($uuid),
								  "tableID" => $this->tableID,
								  "page" => $this->tablePage
								  );
				
				try{
					 $db->insert("export_tabs_records", $data);
				}
				catch (Exception $e)  {
					 //echo (string)$e;
					 //die;
				}
		  }
	 }
	 
	 
	 //get the item's project ID.
	 function getItemProjectID($uuid){
		  $output = false;
		  if(is_array($this->projects)){
				if(count($this->projects) == 1){
					 foreach($this->projects as $uri => $projArray){
						  $outEx = explode("/", $uri);
						  $output = $outEx[count($outEx) - 1 ]; //last part of a URI construction
					 }
				}
		  }
		  if(!$output){
				
				$db = $this->startDB();
				$sql = "SELECT project_id FROM space WHERE uuid = '$uuid' LIMIT 1; ";
				
				$result = $db->fetchAll($sql);
				if($result){
					 $output = $result[0]["project_id"];
				}
		  }
		  return $output;
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
		  //$value = utf8_decode($value);
		  if(preg_match('/,/', $value) or preg_match("/\n/", $value) or preg_match('/"/', $value)) { // Check if I have any commas or new lines
				return '"'.$value.'"'; // If I have new lines or commas escape them
		  } else {
				return $value; // If no new lines or commas just return the value
		  }
	 }
	 
	 
	 
	 
	 
	 //save the file in the correct correct directory
	 function saveJSONprev($itemDir, $baseFilename, $JSONarray){
		
		  $fileExtensions = $this->fileExtensions;
		  $success = false;
		
		  try{
				
				iconv_set_encoding("internal_encoding", "UTF-8");
				$JSON = Zend_Json::encode($JSONarray);
				//iconv_set_encoding("internal_encoding", "UTF-8");
				//iconv_set_encoding("output_encoding", "UTF-8");
				$fp = fopen($itemDir."/".$baseFilename.$fileExtensions["json-prev"], 'w');
				//fwrite($fp, iconv("ISO-8859-7","UTF-8",$JSON));
				//fwrite($fp, utf8_encode($JSON));
				fwrite($fp, $JSON);
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
	 
	 
	 //save the file in the correct correct directory
	 function saveJSON($itemDir, $baseFilename, $JSONarray){
		
		  $fileExtensions = $this->fileExtensions;
		  $success = false;
		
		  try{
				
				iconv_set_encoding("internal_encoding", "UTF-8");
				$JSON = Zend_Json::encode($JSONarray);
				//iconv_set_encoding("internal_encoding", "UTF-8");
				//iconv_set_encoding("output_encoding", "UTF-8");
				$fp = fopen($itemDir."/".$baseFilename.$fileExtensions["json"], 'w');
				//fwrite($fp, iconv("ISO-8859-7","UTF-8",$JSON));
				//fwrite($fp, utf8_encode($JSON));
				fwrite($fp, $JSON);
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
				if(file_exists($zipFileName)){
					 unlink($zipFileName); 
				}
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
				if(file_exists($gzFileName)){
					 unlink($gzFileName); 
				}
				
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
	 
	 //copy the CSV file, save as a GZIP file
	 function CSVcompressCopies($itemDir, $baseFilename){
		
		  $fileExtensions = $this->fileExtensions;
		  $success = false;
		
		  try{
				
				$csvFileName = $itemDir."/".$baseFilename.$fileExtensions["csv"];
				
				iconv_set_encoding("internal_encoding", "UTF-8");
				iconv_set_encoding("output_encoding", "UTF-8");
				
				$fileOK = file_exists($csvFileName);
				if($fileOK){
					 $rHandle = fopen($csvFileName, 'r');
					 if ($rHandle){
						  $csv = '';
						  while(!feof($rHandle)){
								$csv .= fread($rHandle, filesize($csvFileName));
						  }
						  fclose($rHandle);
						  unset($rHandle);
						  $this->saveGZIP($itemDir, $baseFilename, $csv);
						  $this->saveZIP($itemDir, $baseFilename, $csv);
					 }
				}
				else{
					 echo $csvFileName. " not found!";
					 die;
				}
				
		  }
		  catch (Zend_Exception $e){
				$success = false; //save failure
				echo (string)$e;
				die;
		  }
		
		  return $success;
	 }
	 
	 
	 
	 
	 // open a new file handle to append
	 function startCSVfileHandle($itemDir, $baseFilename){
		  
		  iconv_set_encoding("internal_encoding", "UTF-8");
		  iconv_set_encoding("output_encoding", "UTF-8");
		  $fileExtensions = $this->fileExtensions;
		  $files = $this->files;
		  
		  $csvFileName = $itemDir."/".$baseFilename.$fileExtensions["csv"];
		  if(file_exists($csvFileName)){
				unlink($csvFileName); 
		  }
		  
		  $fh = fopen($csvFileName, 'ab') or die("can't open file");
		  $files[] = $csvFileName;
		  $this->files = $files;
		  
		  fwrite($fh, "\xEF\xBB\xBF"); //utf8 mark
		  $this->actFileHandle = $fh;
	 }
	 
	 
	  // open a new git file handle to append
	 function startGitFileHandles($itemDir, $baseFilename){
		  
		  if($this->tableByteSize >= (self::maxGitHubSize * .75)){
				
				iconv_set_encoding("internal_encoding", "UTF-8");
				iconv_set_encoding("output_encoding", "UTF-8");
				$fileExtensions = $this->fileExtensions;
				$files = $this->files;
				
				$this->actGitFilePartIndex = 1;
				$byteRatio = $this->tableByteSize / ((self::maxGitHubSize * .75));
				$this->gitFileRowBatchSize = round($this->recordCount / $byteRatio, -3); //number of records per part, rounded to the nearest thousand records
				$rawNumberGitFiles = $this->recordCount / $this->gitFileRowBatchSize;
				$numberGitFiles  = round($rawNumberGitFiles, 0);
				if($numberGitFiles < $rawNumberGitFiles ){
					 $numberGitFiles  = $numberGitFiles + 1;
				}
				
				
				$gitFileHandles = array();
				$gitFileNumber = 1;
				while($gitFileNumber <= $numberGitFiles){
					 $actFileName = $itemDir."/".$baseFilename."-git-".$gitFileNumber.$fileExtensions["csv"];
					 if(file_exists($actFileName)){
						  unlink($actFileName); 
					 }
					 
					 $gitFileHandles[$gitFileNumber] = fopen($actFileName, 'ab') or die("can't open file");
					 fwrite($gitFileHandles[$gitFileNumber], "\xEF\xBB\xBF"); //utf8 mark
					 
					 $gitFileNumber++; 
				}//loop through all git files needed
				
				$this->gitFileHandles = $gitFileHandles;
		  }
		  else{
				$this->gitFileHandles = false;
		  }
		  
	 }
	 
	 //add the field names to each of the git files, it's passed as "data"
	 function addGitFileFieldNames($data){
		  if($this->gitFileHandles != false){
				$gitFileHandles = $this->gitFileHandles;
				foreach($gitFileHandles as $gitFileNumber => $gitFileHandle){
					 $this->saveAppendGitCSV($data, $gitFileNumber);
				}
		  }
	 }
	 
	 
	 //save append Git CSV data
	 function saveAppendGitCSV($data, $gitFileNumber){
		  if($this->gitFileHandles != false){
				$gitFileHandles = $this->gitFileHandles;
				iconv_set_encoding("internal_encoding", "UTF-8");
				iconv_set_encoding("output_encoding", "UTF-8");
				fwrite($gitFileHandles[$gitFileNumber], $data);
				
				$this->gitFileHandles = $gitFileHandles;
		  }
	 }
	 
	 // close git file handles
	 function closeGitFileHandles(){
		  if($this->gitFileHandles != false){
				$gitFileHandles = $this->gitFileHandles;
				foreach($gitFileHandles as $gitFileNumber => $gitFileHandle){
					 fclose($gitFileHandle);
				}
				$this->gitFileHandles = false;
		  }
	 }
	 
	 
	 //now append the data
	 function saveAppendCSV($data){
		  
		  iconv_set_encoding("internal_encoding", "UTF-8");
		  iconv_set_encoding("output_encoding", "UTF-8");
		  
		  //$data = mb_convert_encoding( $data, 'UTF-16LE', 'UTF-8'); 
		  $fh = $this->actFileHandle;
		  fwrite($fh, $data);
		  
		  $this->actFileHandle = $fh;
		  
	 }
	 
	 // close the file handle
	 function closeCSVfileHandle(){
		  $fh = $this->actFileHandle;
		  fclose($fh);
		  $this->actFileHandle = false;
	 }
	 
	 
	 
	 //populate the saveFileSizes array with saved filesizes
	 function getAllFileSizes($baseFilename){
		  $baseFilename = str_replace("/", "_", $baseFilename); //do this for tables broken into different parts
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
					 $sha1 = false;
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
