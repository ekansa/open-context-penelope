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
	 
	 
	 
	function tp_area_chrono(){
		$db = $this->startDB();
		$output = array();
		$tpProj = "02594C48-7497-40D7-11AE-AB942DC513B8";
		$spaceTimeObj = new  dataEdit_SpaceTime;
		$spaceTimeObj->projectUUID = $tpProj;
		
		$tabs = array("z_ex_catal_tp_age",
					  "z_ex_catal_tp_age_gc",
					  "z_ex_catal_tp_main",
					  "z_ex_catal_tp_main_gc",
					  "z_ex_catal_tp_measurements",
					  "z_ex_catal_tp_measurements_gc"
					  );
		
		
		$sql = "SELECT DISTINCT unit, uuid,
		(field_3 * -1) as bc_early,
		(field_4 * -1) AS bc_late,
		bp_early, bp_late
		FROM z_20_68d76efa0
		WHERE uuid != '';
		";
	
		$result = $db->fetchAll($sql, 2);
		foreach($result as $row){
			
			$unit = $row["unit"];
			$uuid = $row["uuid"];
			$start = $row["bc_early"];
			$end = $row["bc_late"];
			$startBP = $row["bp_early"];
			$endBP = $row["bp_late"];
			
			$requestParams = array();
			$requestParams["uuid"] = $uuid;
			$requestParams["projUUID"] = $tpProj;
			$requestParams["tStart"] = $start;
			$requestParams["tEnd"] = $end;
			$spaceTimeObj->requestParams = $requestParams;
			$spaceTimeObj->chrontoTagItem();
			$output[] = $requestParams;
			
			foreach($tabs as $tab){
				$where = "field_9 = '$unit' ";
				$data = array("field_13" => $startBP,
							  "field_14" => $endBP);
				$db->update($tab, $data, $where);
			}
			
			
		}
	
		return $output;
	}
	 
	 
	 
	 
	 
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
	 
	 //get parent uuids for items of a given class, project
	 function parentContextSelect($childClassUUID, $projectUUID){
		  $db = $this->startDB();
		  
		  $sql = "SELECT DISTINCT space_contain.parent_uuid
		  FROM space_contain
		  JOIN space ON space_contain.child_uuid = space.uuid
		  WHERE space.project_id = '$projectUUID'
		  AND space.class_uuid = '$childClassUUID'
		  ";
		  
		  $output = array();
		  $result = $db->fetchAll($sql, 2);
        if($result){
				foreach($result as $row){
					 $parentUUID = $row["parent_uuid"];
					 $parentPersonLinks = $this->getPersonLinks($parentUUID);
					 $childItems = $this->getChildItems($parentUUID);
					 foreach($childItems as $childUUID){
						  foreach($parentPersonLinks as $personLink){
								$originUUID = $childUUID;
								$originType = $personLink["origin_type"];
								$targetUUID = $personLink["targ_uuid"];
								$targetType = $personLink["targ_type"];
								$linkFieldValue = $personLink["link_type"];
								$dataTableName = "contain-infered";
								$newLink = $this->addLinkingRel($originUUID, $originType, $targetUUID, $targetType, $linkFieldValue, $projectUUID, $dataTableName);
								$output[$parentUUID][] = array("childUUID" => $originUUID, "personUUID"=> $targetUUID, "linkUUID" => $newLink);
						  }
					 }
				}
		  }
		  
		  return $output;
	 }
	 
	 
	 //add a link
	 function addLinkingRel($originUUID, $originType, $targetUUID, $targetType, $linkFieldValue, $projectUUID, $dataTableName = 'manual', $obsNum = 1){
                
		  $db = $this->startDB();
        
        //add origin and targget for this resource
        $hashLink       = md5($originUUID . '_' . $obsNum . '_' . $targetUUID . '_' . $linkFieldValue);
        
        $sql = "SELECT links.link_uuid
        FROM links
        WHERE links.project_id = '$projectUUID'
        AND links.hash_link = '$hashLink '
        ";
        
        $linkRows = $db->fetchAll($sql, 2);
        if($linkRows ){
            $linkUUID = $linkRows [0]["link_uuid"];
        }
        else{
            $linkUUID       = GenericFunctions::generateUUID();                            
            $data = array(
                        'project_id'   => $projectUUID,
                        'source_id'          => $dataTableName,
                        'hash_link'         => $hashLink,
                        'link_type'         => $linkFieldValue,
                        'link_uuid'         => $linkUUID,
                        'origin_type'       => $originType,         
                        'origin_uuid'       => $originUUID,              
                        'origin_obs'        => $obsNum,
                        'targ_type'         => $targetType,        
                        'targ_uuid'         => $targetUUID,         
                        'targ_obs'          => $obsNum 
                    );
                    //Zend_Debug::dump($data);
            $db->insert("links", $data);
                   
        }//end addition of new object linking
        
        return $linkUUID;
    }
	 
	 
	 //get links to persons from a spatial unit
	 function getPersonLinks($spaceUUID){
		  $db = $this->startDB();
		  $sql = "SELECT * FROM links WHERE origin_uuid = '$spaceUUID' AND targ_type = 'person' ";
		  $result = $db->fetchAll($sql, 2);
		  return $result;
	 }
	 
	 //get links to child items
	 function getChildItems($parentUUID){
		  $db = $this->startDB();
		  
		  $sql = "SELECT DISTINCT space_contain.child_uuid
		  FROM space_contain
		  WHERE space_contain.parent_uuid = '$parentUUID'
		  ";
		  
		  $output = array();
		  $result = $db->fetchAll($sql, 2);
        if($result){
				foreach($result as $row){
					 $output[] = $row["child_uuid"];
				}
		  }
		  return $output;
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
