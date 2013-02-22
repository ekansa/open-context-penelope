<?php

/*
 Interacts with the British Museum
 right now it uses a combination of web scraping (yech) and sparql queries to get the data we need.
 
 It uses the normal collections search because I couldn't figure out how to do a good sparql search against wildcards (with stemming and all that).
 So, this first does a keyword search, and scrapes the HTML results for objectid's
 These object id's then go into a sparql query sent to the BM's sparql endpoint
 
 Thesaurus concept URIs are retrieved. If we get more than 1, then we find the term that approximately matches the initial keyword of our search
 
*/

class linkedData_UnitTypeVar {
 
public $db; //database
public $variables; 

public $varUUID; //uuid for the varible
public $varLabel; //label or name for the variable
public $projUUID; //project UUID
public $varLinkURI; //linked URI for the variable
public $varLinkLabel; //linked data label for the variable

public $propData; //data about linking relations for properties
const BMrequestDelay = 1; //delay in loop for making british museum requests

function getDecimalVariables(){
	 
	 $db = $this->startDB();
	 
	 $sql = "SELECT var_tab.project_id, var_tab.variable_uuid, var_tab.var_label,
		  linked_data.linkedLabel, linked_data.linkedURI,
		  ld.linkedLabel AS unitType, ld.linkedURI AS unitTypeURI
		  FROM var_tab
		  LEFT JOIN linked_data ON (var_tab.variable_uuid = linked_data.itemUUID AND linked_data.linkedType = 'unit')
		  LEFT JOIN linked_data AS ld ON (var_tab.variable_uuid = ld.itemUUID AND ld.linkedType = 'unit-type')
		  WHERE var_tab.var_type = 'Decimal' AND var_tab.project_id = '".$this->projUUID."'
		  ORDER BY var_tab.var_label
		  ";
	 
	 $result =  $db->fetchAll($sql);
	 if($result){
		  $this->variables = $result;
		  return $result;
	 }
	 else{
		  $this->variables = false;
		  return false;
	 }
	 
	 //end case where the variable is ok
}//end function


function addUnitType($params){
	 $db = $this->startDB();
	 
	 $keyArray = array("varUUID" => "itemUUID",
						    "projectUUID" => "fk_project_uuid");
	 
	 $UTkeys = array("unitTypeURI" => "linkedURI",
							 "unitType" => "linkedLabel");
	 
	 $Ukeys = array( "unitURI" => "linkedURI",
							 "unit" => "linkedLabel"
							 );
	 
	 if(isset($params["varUUID"])){
		 
		  $dataUT = array("fk_project_uuid" => $this->projUUID,
								"source_id" => "manual-form",
								"itemType" => "variable");
		  $dataU = $dataUT;
		  
		  foreach($keyArray as $paramKey => $dbField){
				$dataUT[$dbField] = $params[$paramKey];
				$dataU[$dbField] = $params[$paramKey];
		  }
		  
		  $dataUT["linkedType"] = "unit-type";
		  $dataU["linkedType"] = "unit";
		  
		  foreach($UTkeys as $paramKey => $dbField){
				$dataUT[$dbField] = $params[$paramKey];
		  }
		 
		  $dataUT["hashID"] = md5($dataUT["itemUUID"]."_".$dataUT["linkedURI"]);
		 
		  foreach($Ukeys as $paramKey => $dbField){
				$dataU[$dbField] = $params[$paramKey];
		  }
		  
		  $dataU["hashID"] = md5($dataU["itemUUID"]."_".$dataU["linkedURI"]);
		  
		  try{
				$db->insert("linked_data", $dataUT); //add the unit-type
		  }
        catch (Exception $e) {
 
        }
		  
		  try{
				$db->insert("linked_data", $dataU); //add the unit
		  }
        catch (Exception $e) {
		  
        }
		  
	 }
	 
	 
	 
	 
}




//startup the database
function startDB(){
	 if(!$this->db){
		  $db = Zend_Registry::get('db');
        $this->setUTFconnection($db);
		  $this->db = $db;
		  return $db;
	 }
	 else{
		  return $this->db;
	 }
}


//preps for utf8
function setUTFconnection($db){
	 $sql = "SET collation_connection = utf8_unicode_ci;";
	 $db->query($sql, 2);
	 $sql = "SET NAMES utf8;";
	 $db->query($sql, 2);
}






//this fixes old IRIs in the ontology
function redoOWL(){
	 
	 $baseOWL = "http://opencontext.org/vocabularies/open-context-zooarch";
	 $owlJSONurl = "http://opencontext.org/vocabularies/open-context-zooarch.json";
	 $owlFile = "C:\\GitHub\\oc-ontologies\\vocabularies\\zooarchaeology.owl";
	 $NewOwlFile = "C:\\GitHub\\oc-ontologies\\vocabularies\\zooarchaeology-2.owl";
	 $owlString = $this->loadFile($owlFile);
	 $owlJSONstring = file_get_contents($owlJSONurl);
	 $owlJSON = Zend_Json::decode($owlJSONstring);
	 $iriCount = 1;
	 foreach($owlJSON["classes"] as $iriKey => $classDes){
		  if(!stristr($iriKey, "http://")){
				$newIRI = $this->makeNewIRI($iriCount);
				$owlString = $this->iriReplace($iriKey, $newIRI, $owlString);
				$this->URIreplaceDB($baseOWL.$iriKey, $baseOWL.$newIRI);
				$iriCount++;
		  }
	 }
	 foreach($owlJSON["properties"] as $iriKey => $classDes){
		  if(!stristr($iriKey, "http://")){
				$newIRI = $this->makeNewIRI($iriCount);
				$owlString = $this->iriReplace($iriKey, $newIRI, $owlString);
				$this->URIreplaceDB($baseOWL.$iriKey, $baseOWL.$newIRI);
				$iriCount++;
		  }
	 }
	 
	 $this->saveFile($NewOwlFile, $owlString);
	 return $owlString;
}



function URIreplaceDB($oldURI, $newURI){
	 
	 $db = $this->startDB();
	 $sql = "SELECT * FROM linked_data WHERE linkedURI = '$oldURI' ";
	 echo $sql;
	 $result = $db->fetchAll($sql);
	 if($result){
		  foreach($result as $row){
				$oldHashID = $row["hashID"];
				$newHashID = md5($row["itemUUID"]."_".$newURI);
				$where = "hashID = '$oldHashID' ";
				$data = array("hashID" => $newHashID, "linkedURI" => $newURI);
				$db->update("linked_data", $data, $where);
		  }
	 }

}










function iriReplace($oldIRI, $newIRI, $owlString){
	 $owlString = str_replace("<IRI>".$oldIRI."</IRI>", "<IRI>".$newIRI."</IRI>", $owlString);
	 $owlString = str_replace('IRI="'.$oldIRI.'"', 'IRI="'.$newIRI.'"', $owlString);
	 return $owlString;
}


function makeNewIRI($iriCount){
	 $neededLen = 4;
	 $newIRI = $iriCount;
	 $iriLen = strlen($newIRI);
	 while($iriLen < $neededLen){
		  $newIRI = "0".$newIRI;
		  $iriLen = strlen($newIRI);
	 }
	 $newIRI = "/zoo-".$newIRI;
	 return $newIRI;
}



function loadFile($sFilename, $sCharset = 'UTF-8'){
        
	 if (!file_exists($sFilename)){
		  return false;
	 }
	 $rHandle = fopen($sFilename, 'r');
	 if (!$rHandle){
		  return false;
	 }
	 $sData = '';
	 while(!feof($rHandle)){
		  $sData .= fread($rHandle, filesize($sFilename));
	 }
	 fclose($rHandle);
	 /*
	 if ($sEncoding = mb_detect_encoding($sData, 'auto', true) != $sCharset){
		  $sData = mb_convert_encoding($sData, $sCharset, $sEncoding);
	 }
	 */
	 return $sData;
}

function saveFile($sFilename, $stringData){
	 $fh = fopen($sFilename, 'w') or die("can't open file");
	 fwrite($fh, $stringData);
	 fclose($fh);
}





}//end class

?>
