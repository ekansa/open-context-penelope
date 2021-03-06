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
	 
	 
	 function getExampleLink($varUUID){
		  $db = $this->startDB();
		  
		  $sql = "SELECT observe.subject_uuid
		  FROM observe
		  JOIN properties ON observe.property_uuid = properties.property_uuid
		  WHERE properties.variable_uuid = '$varUUID'
		  LIMIT 1;
		  ";
		  
		  $result =  $db->fetchAll($sql);
		  if($result){
				return "http://penelope.oc/preview/space?UUID=".$result[0]["subject_uuid"];
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
								$doneVars[] = array("varUUID" => $varUUID,
														  "label"=> $varLabel,
														  "var-use-example" => $this->getExampleLink($varUUID),
														  "linkURI" => $okURL);
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
		  $this->addLinkedMetadata();
		  return $doneVars;
	 }
	 
	 
	 
	 function fixCapitalsVars($varList){
		  $doneVars = false;
		  if(is_array($varList)){
				$db = $this->startDB();
				foreach($varList as $varUUID => $varLabel){
					 $sql = "SELECT linkedLabel FROM linked_data WHERE itemUUID = '$varUUID' AND (linkedType = 'Measurement type' OR linkedType = 'unit-type') LIMIT 1; ";
					 
					 $result =  $db->fetchAll($sql);
					 if($result){
						  
						  $goodLabel = $result[0]["linkedLabel"];
						  if($varLabel != $goodLabel){
								$data = array("var_label" => $goodLabel);
								$where = "variable_uuid = '$varUUID' ";
								$db->update("var_tab", $data, $where);
						  }
					 }
				}
		  }
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
						  if($checkVal == "dd"){
								$checkVal = $value; //kludge so DD and Dd aren't done the same way
						  }
						  
						  
						  if(stristr($varLabel, "::")){
								$varEx = explode("::", $varLabel);
								if(stristr($varEx[1],"standard")){
									 $checkLabel = strtolower(trim($varEx[0]));
									 if($checkLabel == "dd"){
										  $checkLabel = trim($varEx[0]); //kludge so DD and Dd aren't done the same way
									 }
								}
								else{
									 $checkLabel = "We're not gona take it!";
								}
						  }
						  else{
								$checkLabel = strtolower($varLabel);
								if($checkLabel == "dd"){
									 $checkLabel = $varLabel; //kludge so DD and Dd aren't done the same way
								}
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
								$insertOK = false;
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
									 $insertOK = true;
								}
								catch (Exception $e) {
									 $insertOK = false;
								}
								
								if($insertOK){
									 $this->deleteAssociatedPublishedRecords($varUUID);
								}
								
						  }//case where the concept label matches the variable label
					 }//loop through an annotation
				}//loop through annotations of a concept
		  }//loop through all class concepts
		  
		  return $output;
	 }
	 
	 
	 
	 //this gets rid of the record of publication for items with new variable annotations
	 function deleteAssociatedPublishedRecords($varUUID){
		  $db = $this->startDB();
		  $sql = "SELECT DISTINCT observe.subject_uuid
		  FROM observe
		  JOIN properties ON properties.property_uuid = observe.property_uuid
		  WHERE properties.variable_uuid = '$varUUID';
		  ";
		  
		  $result =  $db->fetchAll($sql);
		  if($result){
				foreach($result as $row){
					 $itemUUID = $row["subject_uuid"];
					 $where = "item_uuid = '$itemUUID' ";
					 $db->delete("published_docs", $where);
				}
		  }
	 }
	 
	 function addLinkedMetadata(){
		  $db = $this->startDB();
		  $sql = "UPDATE linked_data
					 JOIN linked_data as ld ON (linked_data.linkedURI = ld.linkedURI
					 AND ld.linkedLabel != '')
					 SET linked_data.linkedLabel = ld.linkedLabel
					 WHERE linked_data.linkedLabel = '' ;
					 
					 UPDATE linked_data
					 JOIN linked_data as ld ON (linked_data.linkedURI = ld.linkedURI
					 AND ld.vocabURI != '')
					 SET linked_data.vocabURI = ld.vocabURI
					 WHERE linked_data.vocabURI = '' ;
					 
					 UPDATE linked_data
					 JOIN linked_data as ld ON (linked_data.linkedURI = ld.linkedURI
					 AND ld.vocabulary != '')
					 SET linked_data.vocabulary = ld.vocabulary
					 WHERE linked_data.vocabulary = '' ;
					 
					 
					 UPDATE linked_data
					 JOIN linked_data as ld ON (LEFT(linked_data.linkedURI,19) = LEFT(ld.linkedURI, 19)
					 AND ld.vocabURI != '')
					 SET linked_data.vocabURI = ld.vocabURI
					 WHERE linked_data.vocabURI = '' ;
					 
					 
					 UPDATE linked_data
					 JOIN linked_data as ld ON (LEFT(linked_data.linkedURI,19) = LEFT(ld.linkedURI, 19)
					 AND ld.vocabulary != '')
					 SET linked_data.vocabulary = ld.vocabulary
					 WHERE linked_data.vocabulary = '' ;
					 
					 
					 UPDATE linked_data
					 SET linkedURI = TRIM(linkedURI);
					 UPDATE linked_data
					 SET linkedLabel = TRIM(linkedLabel);
					 UPDATE linked_data
					 SET vocabulary = TRIM(vocabulary);
					 UPDATE linked_data
					 SET vocabURI = TRIM(vocabURI);

					 
					 
					 UPDATE linked_data
					 SET linkedType = 'Measurement type'
					 WHERE linkedType = 'unit-type';
					 
					 ";
					 
		  $db->query($sql, 2);
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
