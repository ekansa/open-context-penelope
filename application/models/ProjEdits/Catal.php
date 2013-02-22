<?php
/*
This class process a work book full of measurements (as saved in a "fods" xml format from Libre Office)
It's specifically designed to parse through the Catalhoyuk type schema for describing measurements

I'm including it, since it may be useful to adapt to other projects

*/
class ProjEdits_Catal  {
    
   
	
    public $db;
	 public $workbookFile; //filename to parse and load into the database
	 public $importTableName; //name of the import table to add these data too
	 public $doInsert = false;
	 public $doCommentUpdate = false;
	
	 public $nsArray = array("office" => "urn:oasis:names:tc:opendocument:office:1.0",
								 "style" => "urn:oasis:names:tc:opendocument:style:1.0",
								 "text" => "urn:oasis:names:tc:opendocument:text:1.0",
								 "table" => "urn:oasis:names:tc:opendocument:table:1.0",
								 "draw" => "urn:oasis:names:tc:opendocument:drawing:1.0",
								 "fo" => "urn:oasis:names:tc:opendocument:xsl-fo-compatible:1.0",
								 "xlink" => "http://www.w3.org/1999/xlink",
								 "dc" => "http://purl.org/dc/elements/1.1/",
								 "meta" => "urn:oasis:names:tc:opendocument:meta:1.0",
								 "number" => "urn:oasis:names:tc:opendocument:datastyle:1.0",
								 "presentation" => "urn:oasis:names:tc:opendocument:presentation:1.0",
								 "svg" => "urn:oasis:names:tc:opendocument:svg-compatible:1.0",
								 "chart" => "urn:oasis:names:tc:opendocument:chart:1.0",
								 "dr3d" => "urn:oasis:names:tc:opendocument:dr3d:1.0",
								 "math" => "http://www.w3.org/1998/Math/MathML",
								 "form" => "urn:oasis:names:tc:opendocument:form:1.0",
								 "script" => "urn:oasis:names:tc:opendocument:script:1.0",
								 "config" => "urn:oasis:names:tc:opendocument:config:1.0",
								 "ooo" => "http://openoffice.org/2004/office",
								 "ooow" => "http://openoffice.org/2004/writer",
								 "oooc" => "http://openoffice.org/2004/calc",
								 "dom" => "http://www.w3.org/2001/xml-events",
								 "xforms" => "http://www.w3.org/2002/xforms",
								 "xsd" => "http://www.w3.org/2001/XMLSchema",
								 "xsi" => "http://www.w3.org/2001/XMLSchema-instance",
								 "rpt" => "http://openoffice.org/2005/report",
								 "of" => "urn:oasis:names:tc:opendocument:of:1.2",
								 "xhtml" => "http://www.w3.org/1999/xhtml",
								 "grddl" => "http://www.w3.org/2003/g/data-view#",
								 "tableooo" => "http://openoffice.org/2009/table",
								 "drawooo" => "http://openoffice.org/2010/draw",
								 "calcext" => "urn:org:documentfoundation:names:experimental:calc:calcext:1.0",
								 "field" => "urn:openoffice:names:experimental:ooo-ms-interop:field:1.0",
								 "formx" => "urn:openoffice:names:experimental:ooxml-odf-interop:form:1.0",
								 "css3t" => "http://www.w3.org/TR/css3-text/");
	 
	 public $commentMeasuresPrefixes = array("easurements ",
												"easurement ",
												"easure ",
												"mm ",
												"mm",
												"m ",
												"m",
												" and ",
												" + ",
												"MM ",
												"M ",
												"M",
												" "
												);
	 
	 public $commentMeasureSuffixes = array(" ", ".", ", ", ":");
	 
	 function loadParseSaveXML(){
		  
		  $xmlString = file_get_contents($this->workbookFile);
		  $db = $this->startDB();
		  $doc = new DOMDocument();
		  $doc->loadXML($xmlString);
		  $xpath = new DOMXpath($doc);
		  foreach($this->nsArray as $nsKey => $ns){
				$xpath->registerNamespace( $nsKey, $ns);
		  }
		  $query = "//table:table";
		  $tables = $xpath->query($query);
		  $records = array();
		  foreach($tables as $table){
			
				$query = "@table:name";
				$name = $xpath->query($query, $table);
				$tname = $name->item(0)->nodeValue;  //name of the table
				
				$firstRow = true;
				$fields = array();
				$measureNames = array();
				$query = "table:table-row";
				$rows = $xpath->query($query, $table);
				foreach($rows as $row){
					 if($firstRow){
						  $firstRow = false;
						  $query = "table:table-cell/text:p";
						  $headCells = $xpath->query($query, $row);
						  foreach($headCells as $headcell){
								$headCell = $headcell->nodeValue;
								if(strstr($headCell, ":")){
									 $headEx = explode(":", $headCell);
									 $headCell = trim($headEx[1]);
									 $headNum = str_replace("M", "", $headEx[0]);
									 $measureNames[$headNum] = $headCell;
									 $this->labelSortUpdate($headCell, $headNum);
								}
								$fields[] = $headCell;
						  }
						  $fieldCount = count($fields);
						  //$records["fields"][$tname] = $fields;
					 }
					 else{
						  $tempData = array();
						  $varVals = array();
						  $useFields = array();
						  $cellIndex = 0;
						  $query = "table:table-cell";
						  $dataCells = $xpath->query($query, $row);
						  $previousFieldVar = false;
						  $actMod = 1;
						  foreach($dataCells as $datacell){
								
								$currentMod = false;
								$dataVal = false;
								$query = "text:p";
								$cellContents = $xpath->query($query, $datacell);
								foreach($cellContents as $cellContent){
									 $dataVal = $cellContent->nodeValue;
								}
								if(!$dataVal){
									 $query = "@table:number-columns-repeated";
									 $repeats = $xpath->query($query, $datacell);
									 foreach($repeats as $repItem){
										  $repeatNum = $repItem->nodeValue;  //number of repeated cells
										  $cellIndex = $cellIndex + $repeatNum - 1;
										  if($cellIndex >= 	$fieldCount-1){
												$cellIndex = $fieldCount-1;
										  }
									 }
								}
								
								if($dataVal != false){
									 if(isset($fields[$cellIndex])){
										  $actField = $fields[$cellIndex];
										  
										  //check to see if this is a modification field
										  $modCheck = str_replace("Mod", "", $actField);
										  if(is_numeric($modCheck)){
												if($modCheck >= $actMod){
													 $actMod = $modCheck;
													 $currentMod  = true;
												}
										  }

										  if($actField == "GID"){
												$tempData["GID"] = $dataVal;
												$tempData["type"] = $tname;
										  }
										  elseif($currentMod){
												$actVar = $fields[$cellIndex + 1]."::".$dataVal;
												$useFields[$actMod] = $fields[$cellIndex + 1];
												$previousFieldVar = true;
										  }
										  elseif($previousFieldVar && is_numeric($dataVal)){
												$varVals[$actVar] = $dataVal+0;
												$previousFieldVar = false;
										  }
										  elseif(stristr($actField, "comment")){
												$tempData["comment"] = $dataVal;
										  }
										  else{
												$tempData["other"] = $dataVal;
										  }
									 }
									 else{
										  $tempData["funny-index-".$cellIndex] = $dataVal;
									 }
								}
								$cellIndex++;
						  }
						  //$records["cell-counts"][$tname][] = $cellIndex;
						  $upComment = false;
						  if(isset($tempData["comment"]) && $this->doCommentUpdate){
								if(strlen($tempData["comment"])>2){
									 $upComment = $tempData["comment"];
									 foreach($measureNames as $numKey => $fieldName){
										  foreach($this->commentMeasuresPrefixes as $prefix){
												foreach($this->commentMeasureSuffixes as $suffix){
													 $search = $prefix.$numKey.$suffix;
													 if(strstr($tempData["comment"], $search )){
														  //echo $search." ";
														  if(!strstr($tempData["comment"], $search."cm" )){
																$replace = $prefix.$numKey." [$fieldName]".$suffix;
																//echo "Search: $search Replace: $replace ";
																$upComment = str_replace($search, $replace,  $upComment);
														  }
													 }
													 elseif(substr($tempData["comment"], -(strlen($prefix.$numKey)), strlen($prefix.$numKey)) == $prefix.$numKey){
														  $upComment = $upComment." [$fieldName]";
													 }
												}
										  }
										  $upComment = str_replace("[$fieldName] [$fieldName]", "[$fieldName]",  $upComment);
										  $upComment = str_replace("[$fieldName] [$fieldName]", "[$fieldName]",  $upComment);
									 }
									 
									 if($upComment != $tempData["comment"]){
										  $AllComment = "<h5>Original Comment</h5>".chr(13)."<p>".$tempData["comment"]."</p>".chr(13);
										  $AllComment .= "<h5>Editorial Change</h5>".chr(13)."<p>".$upComment."</p>".chr(13)."<p>[Editor note: during data import, we automatically generated labeling
										  text inside square brackets to clarify references to specific measurements in this comment]</p>";
										  $upComment = $AllComment;
										  $upData = array();
										  $upData["field_5"] =  $upComment;
										  $where = array();
										  $where[] = "field_1 = '".$tempData["GID"]."' ";
										  $where[] = "field_5 != '' ";
										  //$db->update($this->importTableName, $upData, $where);
										  //$success = $this->commentNoteUpdate($tempData["GID"], $upComment, "7ABB7861-03CA-46A8-3A85-ABDD13D4CE9F");
										  $success = false;
										  if($success){
												$upComment .= " SUCCESS UPDATED";
										  }
									 }
								}
						  }
						  
						  
						  
						  /*
						  if( $tempData["GID"] == "2959.F214" ){
								echo print_r($tempData);
								echo print_r($varVals);
								die;
						  }
						  */
						  $doneComments = array();
						  foreach($varVals as $var => $val){
								$data = array();
								$data["field_1"] = $tempData["GID"];
								$data["field_2"] = $tempData["type"];
								$data["field_3"] = $var;
								$data["field_4"] = $val;
								if(isset($tempData["comment"])){
									 if(!in_array($tempData["comment"], $doneComments)){
										  $data["field_5"] = $tempData["comment"];
										  $doneComments[] = $tempData["comment"];
									 }
									 else{
										  $data["field_5"] = "";
									 }
								}
								else{
									 $data["field_5"] = "";
								}
								
								if($this->importTableName && $this->doInsert){
									 $records["data"][] = $data;
									 try{
										  //$db->insert($this->importTableName, $data);
									 }
									 catch (Exception $e) {
										  echo (string)$e;
										  
									 }
								}
								if( $upComment!= false && $data["field_5"] != ""){
									 if($data["field_5"] != $upComment){
										  $data["newComment"] = $upComment;
									 }
									 $records["data"][] = $data;
								}
								unset($data);
						  }
						  
						  unset($doneComments);
						  unset($tempData);
						  unset($varVals);
					 }
				}
				
		  }
		  return $records;
	 }
	 
	 
	 function commentNoteUpdate($label, $newComment, $commentVarID){
		  
		  $db = $this->startDB();
		  
		  $sql = "SELECT properties.value_uuid
		  FROM properties
		  JOIN observe ON observe.property_uuid = properties.property_uuid
		  JOIN space ON observe.subject_uuid = space.uuid
		  WHERE properties.variable_uuid = '$commentVarID'
		  AND properties.source_id = '".$this->importTableName."'
		  AND space.space_label = '$label'
		  LIMIT 1;
		  ";
		  
		  $result = $db->fetchAll($sql, 2);
        if($result){
				$valUUID = $result[0]["value_uuid"];
				$data = array("val_text" => $newComment);
				$where = "value_uuid = '$valUUID' ";
				$db->update("val_tab", $data, $where);
				return true;
		  }
		  else{
				return false;
		  }
	 }
	 
	 
	 function labelSortUpdate($label, $fieldNum){
		  
		  $db = $this->startDB();
		  $label = addslashes($label);
		  $sql = "SELECT var_label, variable_uuid, sort_order
		  FROM var_tab
		  WHERE source_id = '".$this->importTableName."'
		  AND var_label LIKE '$label%'
		  ";
		  
		  $result = $db->fetchAll($sql, 2);
        if($result){
				foreach($result as $row){
					 $varUUID = $row["variable_uuid"];
					 $sort = $row["sort_order"];
					 if($sort <= 300){
						  $newSort = $fieldNum + 300;
					 }
					 else{
						  $newSort = ($sort + ($fieldNum + 300))/2;
						  $newSort = round($newSort, 0);
					 }
					 
					 $data = array("sort_order" => $newSort);
					 $where = "variable_uuid = '$varUUID' ";
					 $db->update("var_tab", $data, $where);
				}
		  }
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