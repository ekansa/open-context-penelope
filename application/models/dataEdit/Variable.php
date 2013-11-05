<?php
/*
This is for doing some random edits to space items

*/
class dataEdit_Variable  {
    
    public $db;
	 public $requestParams; //parameters sent in a request 
	 
	 public $varUUID;
	 public $projectUUID;
	 public $projectName;
	 public $sourceID;
	 public $varLabel;
	 public $varType;
	 public $varSort;
	 public $varNote;
	 public $varNoteXHTMLvalid;
	 public $varLinks;
	 
	 
	 const defaultActiveTab = "varsList";
	 
	 
	 function getVariable($varUUID = false){
		  
		  if($varUUID != false){
				$this->varUUID = $varUUID;
		  }
		  
		  if($this->varUUID){
				$db = $this->startDB();
				
				$sql = "SELECT DISTINCT project_list.project_name AS projectName,
				var_tab.project_id AS projectUUID,
				var_tab.source_id AS sourceID,
				var_tab.variable_uuid AS varUUID,
				var_tab.var_label AS varLabel,
				var_tab.var_type AS varType,
				var_tab.sort_order AS sortOrder
				FROM var_tab
				JOIN project_list ON project_list.project_id = var_tab.project_id
				WHERE var_tab.variable_uuid = '".$this->varUUID."' LIMIT 1;";
				
				$result =  $db->fetchAll($sql);
				if($result){
					 
					 $this->projectName = $result[0]["projectName"];
					 $this->projectUUID = $result[0]["projectUUID"];
					 $this->sourceID = $result[0]["sourceID"];
					 $this->varLabel = $result[0]["varLabel"];
					 $this->varType = $result[0]["varType"];
					 $this->varSort = $result[0]["sortOrder"];
					 
					 $this->varNote = $this->getVariableNote($this->varUUID);
					 $this->getVariableLinks($varUUID);
					 return true;
				}
				else{
					 $this->varUUID = false;
					 return false;
				}
		  }
		  
	 }
	 
	 //used for displaying which tab is active
	 function makeTabLiClass($tabName){
		  
		  $activeTab = self::defaultActiveTab; //set a default active tab
		  $requestParams = $this->requestParams; 
		  
		  if(isset($requestParams["tab"])){
				$activeTab = $requestParams["tab"];
		  }

		  if($tabName == $activeTab){
				return " class=\"active\" ";
		  }
		  else{
				return "";
		  }
	 }
	 
	 
	 function makeTabDivClass($tabName){
		  
		  $activeTab = self::defaultActiveTab; //set a default active tab
		  $requestParams = $this->requestParams;
		  
		  if(isset($requestParams["tab"])){
				$activeTab = $requestParams["tab"];
		  }
		  
		  if($tabName == $activeTab){
				return " class=\"tab-pane active\" ";
		  }
		  else{
				return " class=\"tab-pane\" ";
		  }
	 }
	 
	 
	 //get a note for a variable
	 function getVariableNote($varUUID){
		  
		  $db = $this->startDB();
		  
		  $sql = "SELECT * FROM var_notes WHERE variable_uuid = '".$varUUID."' LIMIT 1;";
		  
		  $result =  $db->fetchAll($sql);
		  if($result){
				$output = $result[0]["note_text"];
				$xmlCheck = "<div>".$output."</div>";
				@$xml = simplexml_load_string($xmlCheck);
				if($xml){
					 $this->varNoteXHTMLvalid = true;
				}
				else{
					 $this->varNoteXHTMLvalid = false;
				}
				unset($xml);
				unset($xmlCheck);
		  }
		  else{
				$output = false;
		  }
		  
		  return $output;
	 }
	 
	 
	 function getVariableLinks($varUUID){
		  
		  $db = $this->startDB();
		  
		  $sql = "SELECT * FROM linked_data WHERE itemUUID = '".$varUUID."' ";
		  
		  $result =  $db->fetchAll($sql);
		  if($result){
				$this->varLinks = $result;
				return $result;
		  }
		  else{
				$this->varLinks = false;
				return false;
		  }
		  
	 }
	 
	 
	 
	 //update a note for a variable
	 function addVariableNote($varUUID, $newNoteText){
		  
		  $db = $this->startDB();
		  
		  if($this->getVariable($varUUID)){
				$newNoteText = trim($newNoteText);
				$where = "variable_uuid = '$varUUID' ";
				
				if(strlen($newNoteText)<2){
					 $newNoteText = false;
				}
				
				if(!$newNoteText){
					 $db->delete("var_notes", $where);
				}
				else{
					 $data = array("note_text" => $newNoteText);
					 
					 if(!$this->varNote){
						  $data["project_id"] = $this->projectUUID;
						  $data["source_id"] = $this->sourceID;
						  $data["variable_uuid"] = $varUUID;
						  $data["note_uuid"] = GenericFunctions::generateUUID();
						  $db->insert("var_notes", $data);
					 }
					 else{
						  $db->update("var_notes", $data, $where);
					 }
				}
		  }
	 }
	 
	 
	 
	 
	 //get a list of variables, their names and types
	 function getVarList(){
		  
		  $db = $this->startDB();
		  
		  $requestParams = $this->requestParams;
		  
		  $projCond = "";
		  $classJoin = "";
		  $typeCond = "";
		  $textCond = "";
		  
		  if(isset($requestParams["projectUUID"])){
				$projCond = $this->makeORcondition($requestParams["projectUUID"], "project_id", "var_tab");
				$projCond = " AND (".$projCond.") ";
		  }
		  if(isset($requestParams["classUUID"])){
				if(strlen($requestParams["classUUID"])>1){
					 $classCond = $this->makeORcondition($requestParams["classUUID"], "class_uuid", "space");
					 $classJoin = " JOIN properties ON var_tab.variable_uuid = properties.variable_uuid ";
					 $classJoin .= " JOIN observe ON properties.property_uuid = observe.property_uuid ";
					 $classJoin .= " JOIN space ON (observe.subject_uuid = space.uuid AND ( $classCond )) ";
				}
		  }
		  if(isset($requestParams["varType"])){
				if(strlen($requestParams["varType"])>1){
					 $typeCond = $this->makeORcondition($requestParams["varType"], "var_type", "var_tab");
					 $typeCond = " AND (".$typeCond.") ";
				}
		  }
		  if(isset($requestParams["q"])){
				$textCondA = $this->makeORcondition($requestParams["q"], "var_label", "var_tab", true);
				$textCondB = $this->makeORcondition($requestParams["q"], "variable_uuid", "var_tab");
				$textCond = " AND ((".$textCondA.") OR (".$textCondB."))";
		  }
		  
		  $sql = "SELECT DISTINCT project_list.project_name AS projectName, var_tab.project_id AS projectUUID, var_tab.variable_uuid AS varUUID, var_tab.var_label AS varLabel, var_tab.var_type AS varType
		  FROM var_tab
		  JOIN project_list ON project_list.project_id = var_tab.project_id
		  $classJoin
		  WHERE 1 $projCond $typeCond $textCond
		  ORDER BY var_tab.sort_order, var_tab.var_label
		  ";
		  
		  //echo $sql;
		  //die;
		  
		  $result =  $db->fetchAll($sql);
		  return $result;
	 }
	 
	 
	 //get classes represented in the current database
	 function getRepresentedVarTypes(){
		  
		  $redo = false;
		  $db = $this->startDB();
		  $requestParams = $this->requestParams;
		  
		  $projCond  = "";
		  if(isset($requestParams["projectUUID"])){
				$projCond = $this->makeORcondition($requestParams["projectUUID"], "project_id", "var_tab");
				$projCond = " AND (".$projCond.") ";
		  }
		  
		  $sql = "SELECT DISTINCT var_tab.var_type AS varType
		  FROM var_tab
		  WHERE 1 $projCond
		  ";
		  
		  $result = false;
		  $rawResult =  $db->fetchAll($sql);
		  if($rawResult){
				$result = array();
				foreach($rawResult as $row){
					 $ucType = ucwords($row["varType"]);
					 if($ucType != $row["varType"] ){
						  //need to fix inconsistencies!
						  $where = "var_type = '".$row["varType"]."' ";
						  $data = array("var_type" => $ucType);
						  $db->update("var_tab", $data, $where); //fix it so the data is upper case!
						  $redo = true;
					 }
					 $result[] = $ucType;
				}
				
				if($redo){
					 $this->getRepresentedVarTypes(); //get the data now that it is fixed! :)
				}
				
		  }
		  
		  return $result;
		  
	 }
	 
	 
	 
	 //get variable_types represented in the current database
	 function getRepresentedClasses($getCount = false){
		  
		  $db = $this->startDB();
		  $requestParams = $this->requestParams;
		  
		  $projCond  = "";
		  if(isset($requestParams["projectUUID"])){
				$projCond = $this->makeORcondition($requestParams["projectUUID"], "project_id", "space");
				$projCond = " AND (".$projCond.") ";
		  }
		  
		  if($getCount){
				$sql = "SELECT count(space.uuid) AS idCount, space.class_uuid AS classUUID, sp_classes.class_label AS classLabel
				FROM space
				JOIN sp_classes ON space.class_uuid = sp_classes.class_uuid
				WHERE 1 $projCond
				GROUP BY space.class_uuid
				ORDER BY idCount DESC
				";
		  }
		  else{
				$sql = "SELECT DISTINCT 0 AS idCount, space.class_uuid AS classUUID, sp_classes.class_label AS classLabel
				FROM space
				JOIN sp_classes ON space.class_uuid = sp_classes.class_uuid
				WHERE 1 $projCond
				GROUP BY space.class_uuid
				ORDER BY classLabel
				";
		  }
		  
		  $result =  $db->fetchAll($sql);
		  return $result;
		  
	 }
	 
	 
	 
	 //get the projects represented in the current database
	 function getRepresentedProjects(){
		  
		  $db = $this->startDB();
		  $requestParams = $this->requestParams;
		  
		  
		  $sql = "SELECT DISTINCT project_list.project_name AS projectName, project_list.project_id AS projectUUID
		  FROM project_list
		  WHERE 1 
		  ORDER BY project_list.project_name
		  ";
		  
		  $result =  $db->fetchAll($sql);
		  return $result;
		  
	 }
	 
	 
	 
	 
	 
	 //get a list of values associated with a variable
	 function getVarValues(){
		  
		  $output = false;
		  $db = $this->startDB();
		  
		  $requestParams = $this->requestParams;
		  if(isset($requestParams["varUUID"])){
				
				$varUUID = $requestParams["varUUID"];
				$valLimit = " ";
				if(isset($requestParams["q"])){
					 if(strlen(trim($requestParams["q"]))>0){
						  $qLimit = addslashes($requestParams["q"]);
						  $valLimit = " AND val_tab.val_text LIKE '%".$qLimit."%' ";
					 }
				}
				
				
				$sql = "SELECT properties.property_uuid AS propUUID,
				properties.value_uuid as valUUID,
				val_tab.val_text,
				properties.val_num,
				properties.val_date
				FROM properties
				LEFT JOIN val_tab ON properties.value_uuid = val_tab.value_uuid
				WHERE properties.variable_uuid = '$varUUID'
				$valLimit
				ORDER BY val_tab.val_text, properties.val_num, properties.val_date 
				";
				
				$result =  $db->fetchAll($sql);
				if($result){
					 $output = array();
					 foreach($result as $row){
						  $actOutput = $row;
						  unset($actOutput["val_text"]);
						  unset($actOutput["val_num"]);
						  unset($actOutput["val_date"]);
						  $actVal = $row["val_text"];
						  if(!strstr($row["val_date"], "0000")){
								$actVal = $row["val_date"];
						  }
						  elseif(!is_null($row["val_num"])){
								$actVal = $row["val_num"];
						  }
						  $actOutput["val"] = $actVal;
						  $actOutput["exampleUUID"] = $this->getPropertyExample($row["propUUID"]);
						  $output[] = $actOutput;
					 }
				}
				
		  }
		  return $output;
	 }//end function
	 
	 
	 //get an example item uuid using a property
	 function getPropertyExample($propertyUUID){	  
		  $output = false;
		  $db = $this->startDB();
		  $sql = "SELECT subject_uuid FROM observe WHERE property_uuid = '$propertyUUID' LIMIT 1; ";
		  $result =  $db->fetchAll($sql);
		  if($result){
				$output = $result[0]["subject_uuid"];
		  } 
		  return $output;
	 }
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 //makes an OR condition for a given value array, field, and maybe table
	 function makeORcondition($valueArray, $field, $table = false, $like = false){
		  
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
				if(!$like){
					 $actCond = "$fieldPrefix = '$value'";
				}
				else{
					 $value = addslashes($value);
					 $actCond = "$fieldPrefix LIKE '%".$value."%'";
				}
				
				if(!$allCond ){
					 $allCond  = $actCond;
				}
				else{
					 $allCond  .= " OR ".$actCond;
				}
		  }
		  return $allCond ;
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
