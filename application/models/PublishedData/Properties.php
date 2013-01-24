<?php

class PublishedData_Properties {
    
	 public $itemUUID;
    public $projectUUID;
    public $sourceID;
	 public $db;
	 
	 public $errors;
	 
	 function itemPropsRetrieve($itemXML){
		  
		  $projectUUID = $this->projectUUID;
		  $sourceID = $this->sourceID;
		  $propData = array();
		  $varData = array();
		  $valData = array();
		  
		  foreach ($itemXML->xpath("//arch:property") as $act_prop){
				$actPropData = array();
				$actPropData["project_id"] = $projectUUID;
				$actPropData["source_id"] = $sourceID;
				$actVarData = $actPropData;
				$actValData = $actPropData;
				
				$doVal = true;
						
						
				foreach($act_prop->xpath("oc:propid") as $act_prop_result){
					$actPropData["property_uuid"] = $act_prop_result."";
				}
				
				foreach($act_prop->xpath("arch:variableID") as $act_prop_result){
					$actPropData["variable_uuid"] = $act_prop_result."";
					$actVarData["variable_uuid"] = $actPropData["variable_uuid"];
				}
				
				
				if($act_prop->xpath("arch:valueID")){
					 foreach($act_prop->xpath("arch:valueID") as $act_prop_result){
						 $actPropData["value_uuid"] = $act_prop_result."";
						 $actValData["value_uuid"] = $actPropData["value_uuid"];
					 }
					 $actPropData["prop_hash"] = md5($projectUUID . $actPropData["variable_uuid"] . $actPropData["value_uuid"]);
				}
				else{
					if(($act_prop->xpath("arch:integer"))||($act_prop->xpath("arch:decimal"))){
						$actPropData["value_uuid"] = "number";
						foreach($act_prop->xpath("arch:integer") as $act_prop_result){
							$actPropData["val_num"] = $act_prop_result."";
						}
						foreach($act_prop->xpath("arch:decimal") as $act_prop_result){
							$actPropData["val_num"] = $act_prop_result."";
						}
						$doVal = false;
						
						
					}
				}
				
				if($act_prop->xpath("oc:var_label")){
					foreach($act_prop->xpath("oc:var_label") as $act_prop_result){
						$actVarData["var_label"] = $act_prop_result."";
					}
				}
				
				if($act_prop->xpath("oc:show_val")){
					 foreach($act_prop->xpath("oc:show_val") as $act_prop_result){
						  $actValData["val_text"] = $act_prop_result."";
						  $actValData["text_scram"] = md5($actValData["val_text"] . $projectUUID);
					 }
				}

				if($act_prop->xpath("oc:var_label/@type")){
					 foreach($act_prop->xpath("oc:var_label/@type") as $act_prop_result){
						  $actVarData["var_type"] = $act_prop_result."";
						  $actVarData["var_type"] = strtolower($actVarData["var_type"]);
					 }
					 
					 $actVarData["var_hash"]   = md5($projectUUID . $actVarData["var_label"] . $actVarData["var_type"]);
					 
					 if(stristr($actVarData["var_type"], "calend") && isset($actValData["val_text"])){
						  $cal_test_string = str_replace("/", "-", $actValData["val_text"]);
						  if (($timestamp = strtotime($cal_test_string)) === false) {
								$calendardTest = false;
						  }
						  else{
								$calendardTest = true;
						  }
						  
						  if($calendardTest && strlen($actValData["val_text"])<1){
								$actPropData["val_date"] = date("Y-m-d", strtotime($cal_test_string));
						  }
						  
					 }
					 else{
						 $valueDate = false;
					 }
					 
				}
				
				$propData[] = $actPropData;
				unset($actPropData);
				$varData[] = $actVarData;
				unset($actVarData);
				if($doVal){
					$valData[] = $actValData;
				}
				unset($actValData);
		  }
		  
		  return array("properties"=>$propData, "var_tab" => $varData, "val_tab" => $valData);
	}
	 
	 
	 
	 function getNotes($itemXML, $originType){
		  
		  $itemUUID = $this->itemUUID;
		  $projectUUID = $this->projectUUID;
		  $sourceID = $this->sourceID;
		  $obsData = array();
		  $propData = array();
		  $valData = array();
	 
		  //notes need to be added to add to the database
		  if ($itemXML->xpath("//arch:notes/arch:note")) {
				foreach ($itemXML->xpath("//arch:notes/arch:note/arch:string") as $note) {
					 $note = (string)$note;
					 $note = $this->htmlCheckClean($note);
					 $textScram = md5($note . $projectUUID);
					 
					 $actPropData = array();
					 $actPropData["project_id"] = $projectUUID;
					 $actPropData["source_id"] = $sourceID;
					 $actObsData = $actPropData;
					 $actValData = $actPropData;
					 
					 $noteExists = $this->checkNoteExists($textScram);
					 if(!$noteExists){
						  $actPropData["property_uuid"] = GenericFunctions::generateUUID();
						  $actPropData["value_uuid"] = GenericFunctions::generateUUID();
						  $actValData["value_uuid"] = $actPropData["value_uuid"];
					 }
					 else{
						  $actPropData["property_uuid"] = $noteExists["property_uuid"];
						  $actPropData["value_uuid"] = $noteExists["value_uuid"];
						  $actValData["value_uuid"] = $noteExists["value_uuid"];
					 }
					 
					 //finish property data
					 $actPropData["variable_uuid"] = "NOTES";
					 $actPropData["prop_hash"] = md5($projectUUID . $actPropData["variable_uuid"] . $actPropData["value_uuid"]);
					 
					 //finish obs data
					 $actObsData["subject_type"] = $originType;
					 $actObsData["subject_uuid"] = $this->itemUUID;
					 $actObsData["obs_num"] = 1;
					 $actObsData["property_uuid"] = $actPropData["property_uuid"] ;
					 $actObsData["hash_obs"] = md5($projectUUID . "_" . $actObsData["subject_uuid"] . "_" . $actObsData["obs_num"] . "_" . $actObsData["property_uuid"]);
					 
					 
					 //finish val_text
					 $actValData["value_uuid"] =  $actPropData["value_uuid"];
					 $actValData["val_text"] = $note ;
					 $actValData["text_scram"] = $textScram;
					 
					 $propData[] = $actPropData;
					 unset($actPropData);
					 $obsData[] = $actObsData;
					 unset($actObsData);
					 $valData[] = $actValData;
					 unset($actValData);
				} 
		  }
		  
		  return array("properties"=>$propData, "observe" => $obsData, "val_tab" => $valData);
	 }
	 
	 
	 function checkNoteExists($textScram){
		  $db = $this->startDB();
		  $sql = "SELECT val_tab.value_uuid, properties.property_uuid
					 FROM val_tab
					 JOIN properties ON properties.value_uuid = val_tab.value_uuid
					 WHERE val_tab.text_scram = '$textScram'
					 LIMIT 1;
					 ";
					 
		   $result = $db->fetchAll($sql, 2);
        if($result){
            return $result[0];
        }
		  else{
				return false;
		  }
	 }
	 
	 
	 
	 function htmlCheckClean($string){
		  $string = html_entity_decode($string);
		  $validXHTML = false;
		  $xmlNote = "<div>".chr(13);
		  $xmlNote .= $string.chr(13);
		  $xmlNote .= "</div>".chr(13);
		  @$xml = simplexml_load_string($xmlNote);
		  if($xml){
				return $string;
		  }
		  else{
				$string = tidy_repair_string($xmlNote,
						  array( 
								'doctype' => "omit",
								'input-xml' => true,
								'output-xml' => true 
						  ));
					 
				@$xml = simplexml_load_string($string);
				if($xml){
					return $string;
				}
		  }
		  return $string;
	 }
	 
	 
	 
	 
	 //save data
	 function saveData($saveArray){
		  $okInserts = 0;
		  $errors = array();
		  if(is_array($saveArray)){
				$db = $this->startDB();
				foreach($saveArray as $tableKey => $dataArray){
					 foreach($dataArray as $data){
						  try{
								$db->insert($tableKey, $data);
								$okInserts++;
						  }
						  catch (Exception $e) {
								$e = (string)$e;
								if(!stristr($e, "SQLSTATE[23000]")){
									 $errors[] = $e;
								}
						  }
					 }
				}
		  }
		  $this->noteErrors($errors);
		  return $okInserts;
	 }
	 

	 function noteErrors($errors){
		  if(is_array($errors)){
				if(count($errors)>0){
					 if(!is_array($this->errors)){
						  $this->errors = $errors;
					 }
					 else{
						  $allErrors = $this->errors;
						  foreach($errors as $newError){
								$allErrors[] = $newError;
						  }
						  $this->errors = $allErrors;
					 }
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
