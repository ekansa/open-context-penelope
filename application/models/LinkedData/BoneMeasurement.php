<?php

class LinkedData_BoneMeasurement  {
	 
	 public $projectUUID;
	 public $ontology; //ontology object
	 public $doShortVariableLabels = true;
	 
	 public $termDelims = array("/", "#", "");
	 
	 public $db;
	 const OC_zooarch_ontology_base = "http://opencontext.org/vocabularies/open-context-zooarch/";
	 const OC_zooarchJSON = "http://opencontext.org/vocabularies/open-context-zooarch.json";
	 
	 function getVarTableList($tableID){
		  
		  $db = $this->startDB();
		  
		  if($this->doShortVariableLabels){
				$sql = "SELECT variable_uuid, var_label, project_id
				FROM var_tab
				WHERE source_id = '$tableID'
				AND var_type = 'Decimal'
				AND CHAR_LENGTH(var_label) <= 4
				";
		  }
		  else{
				$sql = "SELECT variable_uuid, var_label, project_id
				FROM var_tab
				WHERE source_id = '$tableID'
				AND var_type = 'Decimal'
				";
		  }
		  
		  $result =  $db->fetchAll($sql);
		  if($result){
				$output = array();
				foreach($result as $row){
					 $this->projectUUID = $row["project_id"];
					 $uuidKey = $row["variable_uuid"];
					 $output[$uuidKey] = $row["var_label"];
				}
				return $output;
		  }
		  else{
				return false;
		  }
		  
	 }
	 
	 
	 function processVars($varList){
		  $doneVars = false;
		  if(is_array($varList)){
				@$ontoJSON = file_get_contents(self::OC_zooarchJSON);
				if($ontoJSON){
					 @$this->ontology = Zend_Json::decode($ontoJSON);
					 if(is_array($this->ontology)){
						  $doneVars = array();
						  foreach($varList as $varUUID => $varLabel){
								$okURL = $this->checkAddTerm($varUUID, $varLabel);
								$doneVars[] = array("varUUID" => $varUUID, "label"=> $varLabel, "linkURI" => $okURL);
						  }
					 }
					 else{
						  $doneVars["errors"][] = "Bad ontology JSON";
					 }
				}
				else{
					 $doneVars["errors"][] = "Ontology not retrieved";
				}
		  }
		  else{
				$doneVars["errors"][] = "No list to process";
		  }
		  return $doneVars;
	 }
	 
	 function OLDcheckAddTerm($varUUID, $varLabel){
		  $output = false;
		  $ontology = $this->ontology;
		  $classes = $ontology["classes"];
		  foreach($this->termDelims as $delim){
				$termID = $delim.$varLabel;
				if(array_key_exists($termID, $classes)){
					 if($delim != "/"){
						  $url = self::OC_zooarch_ontology_base.$termID;
					 }
					 else{
						  $url = self::OC_zooarch_ontology_base.$varLabel;
					 }
					 $annotations = $classes[$termID];
					 $label = $varLabel;
					 foreach($annotations as $noteKey => $note){
						  if(stristr($noteKey, "label")){
								$label = $note;
						  }
					 }
					 
					 $db = $this->startDB();
					 $output = $url;
					 $hash = md5($varUUID."_".$url);
					 $data = array('hashID' => $hash,
										'fk_project_uuid' =>  $this->projectUUID,
										'source_id' => 'abbrev. match',
										'itemUUID' => $varUUID,
										'itemType' => 'variable',
										'linkedType' => 'unit-type',
										'linkedLabel' => $label,
										'linkedURI' => $url,
										'vocabulary' => 'Open Context Zooarchaeology Annotations',
										'vocabURI' => self::OC_zooarch_ontology_base
										);
					 try{
						  $db->insert('linked_data', $data);
						  }
					 catch (Exception $e) {
								
					 }
					 
				}
		  }
		  
		  return $output;
	 }
	 
	 
	 function checkAddTerm($varUUID, $varLabel){
		  $output = false;
		  $ontology = $this->ontology;
		  $classes = $ontology["classes"];
		 
		  foreach($classes as $iriKey => $annotations){
				foreach($annotations as $annos){
					 foreach($annos as $annoKey => $value){
						  //echo "<br> $annoKey : $value".chr(13).chr(13);
						  $checkVal = strtolower($value);
						  
						  if(stristr($varLabel, "::")){
								$varEx = explode("::", $varLabel);
								if(stristr($varEx[1],"standard")){
									 $checkLabel = strtolower(trim($varEx[0]));
								}
								else{
									 $checkLabel = "We're not gona take it!";
								}
						  }
						  else{
								$checkLabel = strtolower($varLabel);
						  }
					 
						  
						  //if($annoKey == "rdfs:label" && $value == $varLabel){
						  if($annoKey == "rdfs:label" && $checkVal == $checkLabel){
								//found the concept!
								if(substr(self::OC_zooarch_ontology_base, -1, 1) == "/" && substr($iriKey, 0, 1) == "/"){
									 $url = substr(self::OC_zooarch_ontology_base, 0, (strlen(self::OC_zooarch_ontology_base)-1)).$iriKey;
								}
								else{
									 $url = self::OC_zooarch_ontology_base.$iriKey;
								}
								
								$label = $value;
								$db = $this->startDB();
								$output = $url;
								$hash = md5($varUUID."_".$url);
								$data = array('hashID' => $hash,
												  'fk_project_uuid' =>  $this->projectUUID,
												  'source_id' => 'abbrev. match',
												  'itemUUID' => $varUUID,
												  'itemType' => 'variable',
												  'linkedType' => 'unit-type',
												  'linkedLabel' => $label,
												  'linkedURI' => $url,
												  'vocabulary' => 'Open Context Zooarchaeology Annotations',
												  'vocabURI' => self::OC_zooarch_ontology_base
												  );
								try{
									 $db->insert('linked_data', $data);
									 }
								catch (Exception $e) {
	 
								}
						  }//case where the concept label matches the variable label
					 }//loop through an annotation
				}//loop through annotations of a concept
		  }//loop through all class concepts
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
	 
	 
	 //preps for utf8
	 function setUTFconnection($db){
		  $sql = "SET collation_connection = utf8_unicode_ci;";
		  $db->query($sql, 2);
		  $sql = "SET NAMES utf8;";
		  $db->query($sql, 2);
	 }



}  
