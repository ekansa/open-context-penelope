<?php
/* This class creates files based on data saved for export.
 * It makes csv, zip (with csv), and gzipped csv files.
 * 
 */

class DBexport_PenToOC  {
    
	 public $db; //database connection object
	 
	 public $limitingProjArray = false; //make an array of project UUIDs to limit the results to
	 
	 
	 public $fileExtensions = array("sql" => ".sql",
											  "zip" => ".zip",
											  "gzip" => ".sql.gz"
											  );
	 
	 const DBdirectory = "db-export";
	 
	 function makeSaveSQL(){
		  
		  $data = "";
		  $data .= $this->makeSaveProps();
		  $data .= $this->makeSaveVars();
		  $data .= $this->makeSaveVarNotes();
		  $data .= $this->makeSaveVals();
		  $this->saveGZIP(self::DBdirectory, "all-dump", $data);
		  unset($data);
	 }
	 
	  //saves a file for moving variables
	 function makeSaveProps(){
		  
		  $db = $this->startDB();
		  
		  $projCondition = "1";
		  if(is_array($this->limitingProjArray)){
				$projCondition = $this->makeORcondition($this->limitingProjArray, "project_id", "properties");
		  }
		  
		  
		  $sql = "SELECT 
		  property_uuid,
		  project_id,
		  source_id,
		  variable_uuid,
		  value_uuid,
		  val_num,
		  val_date,
		  note,
		  last_modified_timestamp as created,
		  last_modified_timestamp as updated,
		  '' as prop_archaeoml,
		  '' as prop_atom
		  FROM properties
		  WHERE $projCondition
		  
		  ;
		  
		  ";
		  
		  $result = $db->fetchAll($sql);
		  $data = " SET collation_connection = utf8_unicode_ci; SET NAMES utf8; ";
		  if($result){
				$prefix = $this->makeInsertPrefix($result[0], "properties");
				foreach($result as $row){
					 $insertVals = $this->makeInsertValues($row);
					 $data .= $prefix.$insertVals;
				}
				unset($result);
				$this->saveSQL(self::DBdirectory, "exp-props", $data);
		  }
		  
		  return $data;
	 }
	 
	 
	 
	 
	 //saves a file for moving variables
	 function makeSaveVarNotes(){
		  
		  $db = $this->startDB();
		  
		  $projCondition = "1";
		  if(is_array($this->limitingProjArray)){
				$projCondition = $this->makeORcondition($this->limitingProjArray, "project_id", "var_notes");
		  }
		  
		  
		  $sql = "SELECT MD5(CONCAT(variable_uuid, '_', note_text)) AS hashID,
		  project_id,
		  source_id,
		  variable_uuid,
		  note_uuid,
		  note_text,
		  field_num
		  FROM var_notes

		  WHERE $projCondition
		  
		  ;
		  
		  ";
		  
		  $result = $db->fetchAll($sql);
		  $data = " SET collation_connection = utf8_unicode_ci; SET NAMES utf8; ";
		  if($result){
				$prefix = $this->makeInsertPrefix($result[0], "var_notes");
				foreach($result as $row){
					 $insertVals = $this->makeInsertValues($row);
					 $data .= $prefix.$insertVals;
				}
				unset($result);
				$this->saveSQL(self::DBdirectory, "exp-var-notes", $data);
		  }
		  
		  return $data;
	 }
	 
	 
	 
	 
	 
	 
	 //saves a file for moving variables
	 function makeSaveVars(){
		  
		  $db = $this->startDB();
		  
		  $projCondition = "1";
		  if(is_array($this->limitingProjArray)){
				$projCondition = $this->makeORcondition($this->limitingProjArray, "project_id", "var_tab");
		  }
		  
		  
		  $sql = "SELECT variable_uuid,
		  project_id,
		  source_id,
		  var_type,
		  var_label,
		  '' as var_des,
		  sort_order,
		  hideLink,
		  '' as var_sum,
		  '' as unitURI,
		  last_modified_timestamp as updated,
		  last_modified_timestamp as created

		  FROM var_tab 

		  WHERE $projCondition
		  
		  ;
		  
		  ";
		  
		  $result = $db->fetchAll($sql);
		  $data = " SET collation_connection = utf8_unicode_ci; SET NAMES utf8; ";
		  if($result){
				$prefix = $this->makeInsertPrefix($result[0], "var_tab");
				foreach($result as $row){
					 $insertVals = $this->makeInsertValues($row);
					 $data .= $prefix.$insertVals;
				}
				unset($result);
				$this->saveSQL(self::DBdirectory, "exp-vars", $data);
		  }
		  
		  return $data;
	 }
	 
	 
	 //saves a file for moving values
	 function makeSaveVals(){
		  
		  $db = $this->startDB();
		  
		  $projCondition = "1";
		  if(is_array($this->limitingProjArray)){
				$projCondition = $this->makeORcondition($this->limitingProjArray, "project_id", "val_tab");
		  }
		  
		  
		  $sql = "SELECT value_uuid, project_id, source_id, val_text,last_modified_timestamp AS updated, last_modified_timestamp AS created
		  FROM val_tab
		  WHERE $projCondition
		  
		  ;
		  
		  ";
		  
		  $result = $db->fetchAll($sql);
		  $data = " SET collation_connection = utf8_unicode_ci; SET NAMES utf8; ";
		  if($result){
				$prefix = $this->makeInsertPrefix($result[0], "val_tab");
				foreach($result as $row){
					 $insertVals = $this->makeInsertValues($row);
					 $data .= $prefix.$insertVals;
				}
				unset($result);
				$this->saveSQL(self::DBdirectory, "exp-vals", $data);
		  }
		  
		  return $data;
	 }
	 
	 
	 
	 
	 function makeInsertPrefix($row, $insertTabName, $insertIgnore = true){
		  
		  if($insertIgnore){
				$output = "INSERT IGNORE INTO `".$insertTabName."` ";
		  }
		  else{
				$output = "INSERT INTO `".$insertTabName."` ";
		  }
		  
		  $firstLoop = true;
		  foreach($row as $fieldKey => $value){
				$field = "`".$fieldKey."`";
				if($firstLoop){
					 $output .= "(".$field;
					 $firstLoop = false;
				}
				else{
					 $output .= ", ".$field;
				}
				
		  }
		  
		  $output .= ") \n";
		  
		  return $output;
	 }
	 
	 function makeInsertValues($row){
		  
		  $firstLoop = true;
		  foreach($row as $fieldKey => $value){
				$value= "'".addslashes($value)."'";
				if($firstLoop){
					 $output = "VALUES (".$value;
					 $firstLoop = false;
				}
				else{
					 $output .= ", ".$value;
				}
				
		  }
		  $output .= "); \n\n";
		  return $output;
	 }
	 
	 
	 //makes an OR condition for a given value array, field, and maybe table
	 function makeORcondition($valueArray, $field, $table = false){
		  
		  if(!is_array($valueArray)){
				$valueArray = array(0 => $valueArray);
		  }
		  
		  if(!$table){
				$fieldPrefix = $field;
		  }
		  else{
				$fieldPrefix = $table.".".$field;
		  }
		  $allCond = false;
		  foreach($valueArray as $value){
				$actCond = "$fieldPrefix = '$value'";
				if(!$allCond ){
					 $allCond  = $actCond;
				}
				else{
					 $allCond  .= " OR ".$actCond;
				}
		  }
		  return $allCond ;
	 }
	 
	 
	 

	 //save the file in the correct correct directory
	 function saveSQL($itemDir, $baseFilename, $fileText){
		
		  $fileExtensions = $this->fileExtensions;
		  $success = false;
		
		  try{
				iconv_set_encoding("internal_encoding", "UTF-8");
				iconv_set_encoding("output_encoding", "UTF-8");
				$fp = fopen($itemDir."/".$baseFilename.$fileExtensions["sql"], 'w');
				//fwrite($fp, iconv("ISO-8859-7","UTF-8",$xml));
				//fwrite($fp, utf8_encode($xml));
				fwrite($fp, $fileText);
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
	 
	 
	 
	 
	 //save the file as a GZIP file
	 function saveGZIP($itemDir, $baseFilename, $fileText){
		
		  $fileExtensions = $this->fileExtensions;
		  $success = false;
		
		  try{
				
				$gzFileName = $itemDir."/".$baseFilename.$fileExtensions["gzip"];
				
				iconv_set_encoding("internal_encoding", "UTF-8");
				iconv_set_encoding("output_encoding", "UTF-8");
				
				$gz = gzopen($gzFileName,'w9');
				gzwrite($gz, $fileText);
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
