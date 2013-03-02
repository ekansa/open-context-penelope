<?php

/*
 Interacts with the British Museum
 right now it uses a combination of web scraping (yech) and sparql queries to get the data we need.
 
 It uses the normal collections search because I couldn't figure out how to do a good sparql search against wildcards (with stemming and all that).
 So, this first does a keyword search, and scrapes the HTML results for objectid's
 These object id's then go into a sparql query sent to the BM's sparql endpoint
 
 Thesaurus concept URIs are retrieved. If we get more than 1, then we find the term that approximately matches the initial keyword of our search
 
*/

class linkedData_LinkVar {
 
public $db; //database
public $showPropCounts = true;
public $alphaSort = false;

public $varUUID; //uuid for the varible
public $varLabel; //label or name for the variable
public $projUUID; //project UUID
public $varLinkURI; //linked URI for the variable
public $varLinkLabel; //linked data label for the variable

public $propData; //data about linking relations for properties
const BMrequestDelay = 1; //delay in loop for making british museum requests

function getProperties($varID){
	 
	 $this->varUUID = $varID;
	 $db = $this->startDB();
	 
	 $sql = "SELECT var_tab.project_id, var_tab.var_label, linked_data.linkedLabel, linked_data.linkedURI
		  FROM var_tab
		  LEFT JOIN linked_data ON var_tab.variable_uuid = linked_data.itemUUID
		  WHERE var_tab.variable_uuid = '$varID'
		  ";
		  
		  $resultA =  $db->fetchAll($sql);
		  $this->varLabel = $resultA[0]["var_label"];
		  $this->projUUID = $resultA[0]["project_id"];
		  $this->varLinkURI = $resultA[0]["linkedURI"];
		  $this->varLinkLabel = $resultA[0]["linkedLabel"];
		  
		  
		  if($this->showPropCounts){
				
				if($this->alphaSort){
					 $sort = " val_tab.val_text, count(observe.subject_uuid) DESC ;";
				}
				else{
					 $sort = " count(observe.subject_uuid) DESC, val_tab.val_text DESC ;";
				}
				
				$sql = "SELECT var_tab.var_label,
				 val_tab.val_text,
				 properties.property_uuid,
				 count(observe.subject_uuid) as subCount,
				 properties.project_id,
				 linked_data.linkedLabel,
				 linked_data.linkedURI
				FROM properties
				JOIN val_tab ON val_tab.value_uuid = properties.value_uuid
				JOIN var_tab ON var_tab.variable_uuid = properties.variable_uuid
				JOIN observe ON properties.property_uuid = observe.property_uuid
				LEFT JOIN linked_data ON properties.property_uuid = linked_data.itemUUID
				WHERE properties.variable_uuid = '$varID'
				GROUP BY observe.property_uuid
				ORDER BY $sort
				";
		  }
		  else{
				$sql = "SELECT var_tab.var_label,
				 val_tab.val_text,
				 properties.property_uuid,
				 ' ' as subCount,
				 properties.project_id,
				 linked_data.linkedLabel,
				 linked_data.linkedURI
				FROM properties
				JOIN val_tab ON val_tab.value_uuid = properties.value_uuid
				JOIN var_tab ON var_tab.variable_uuid = properties.variable_uuid
				LEFT JOIN linked_data ON properties.property_uuid = linked_data.itemUUID
				WHERE properties.variable_uuid = '$varID'
				ORDER BY val_tab.val_text
					  ";
		  }
		  
		  $results =  $db->fetchAll($sql);
		  $this->propData = $results ;
	 //end case where the variable is ok
}//end function


function BM_link_types(){
	 if(is_array($this->propData)){
		  $db = $this->startDB();
		  foreach($this->propData as $row){
				if(strlen($row["linkedURI"])<2){
					 //no linked data, so make a request
					 $propertyUUID = $row["property_uuid"];
					 $rawMatch = $row["val_text"];
					 if(strstr($rawMatch, "::")){
						  $matchEx = explode("::", $rawMatch);
						  $keyword = $matchEx[count($matchEx) - 1];
					 }
					 else{
						  $keyword = $rawMatch;
					 }
					 
					 echo "<br/>Searching for: $keyword";
					 
					 $BMobj = new LinkedData_BritishMuseum;
					 $BMobj->getItemIDsByKeyword($keyword);
					 $BMobj->getTypologyThesaurusLD();
					 
					 $output = array("colExampleURI" => $BMobj->colExampleURI,
										  "LDcolExampleURI" => $BMobj->LDcolExampleURI,
										  "LDthesaurusURI" => $BMobj->LDthesaurusURI,
										  "LDthesaurusLabel" => $BMobj->LDthesaurusLabel,
										  "results" => $BMobj->jsonObj,
										  "sparql" => $BMobj->sparql
										  );
					 
					 if($BMobj->LDthesaurusURI != false){
						  //we found a URI!
						  $hash = md5($propertyUUID."_".$BMobj->LDthesaurusURI);
						  $where = array();
						  $where[] = "hashID = '$hash' ";
						  $db->delete("linked_data", $where);
						  
						  echo "... found <em>".$BMobj->LDthesaurusLabel."</em><br/>";
						  
						  $data = array("hashID" => $hash,
									 "fk_project_uuid" => $this->projUUID ,
									 "source_id" => "BM services" ,
									 "itemUUID" => $propertyUUID,
									 "itemType" => "property",
									 "linkedLabel" => $BMobj->LDthesaurusLabel,
									 "linkedType" => "type",
									 "linkedURI" => $BMobj->LDthesaurusURI
									 );
						  
						  $db->insert("linked_data", $data);
					 }
					 unset($BMobj);
					 sleep(self::BMrequestDelay); //sleep so as not to over tax the BM server
				}
				
		  }//end loop through properties
		  
	 }
}




function BM_link_materials(){
	 if(is_array($this->propData)){
		  $db = $this->startDB();
		  foreach($this->propData as $row){
				if(strlen($row["linkedURI"])<2){
					 //no linked data, so make a request
					 $propertyUUID = $row["property_uuid"];
					 $rawMatch = $row["val_text"];
					 if(strstr($rawMatch, "::")){
						  $matchEx = explode("::", $rawMatch);
						  $keyword = $matchEx[count($matchEx) - 1];
					 }
					 else{
						  $keyword = $rawMatch;
					 }
					 
					 echo "<br/>Searching for: $keyword";
					 
					 $BMobj = new LinkedData_BritishMuseum;
					 $BMobj->getItemIDsByKeyword($keyword);
					 $BMobj->getMaterialsThesaurusLD();
					 
					 $output = array("colExampleURI" => $BMobj->colExampleURI,
										  "LDcolExampleURI" => $BMobj->LDcolExampleURI,
										  "LDthesaurusURI" => $BMobj->LDthesaurusURI,
										  "LDthesaurusLabel" => $BMobj->LDthesaurusLabel,
										  "results" => $BMobj->jsonObj,
										  "sparql" => $BMobj->sparql
										  );
					 
					 if($BMobj->LDthesaurusURI != false){
						  //we found a URI!
						  $hash = md5($propertyUUID."_".$BMobj->LDthesaurusURI);
						  $where = array();
						  $where[] = "hashID = '$hash' ";
						  $db->delete("linked_data", $where);
						  
						  echo "... found <em>".$BMobj->LDthesaurusLabel."</em><br/>";
						  
						  $data = array("hashID" => $hash,
									 "fk_project_uuid" => $this->projUUID ,
									 "source_id" => "BM services" ,
									 "itemUUID" => $propertyUUID,
									 "itemType" => "property",
									 "linkedLabel" => $BMobj->LDthesaurusLabel,
									 "linkedType" => "type",
									 "linkedURI" => $BMobj->LDthesaurusURI
									 );
						  
						  $db->insert("linked_data", $data);
					 }
					 unset($BMobj);
					 sleep(self::BMrequestDelay); //sleep so as not to over tax the BM server
				}
				
		  }//end loop through properties
		  
	 }
}


function makeExampleLink($propertyUUID){
	 $db = $this->startDB();
	 $sql = "SELECT subject_uuid FROM observe WHERE property_uuid = '$propertyUUID' LIMIT 1; ";
	 $result =  $db->fetchAll($sql);
	 if($result){
		  return "http://".$_SERVER['SERVER_NAME']."/preview/space?UUID=".$result[0]["subject_uuid"];
	 }
	 else{
		  return false;
	 }
}





//updates a lable to a link
function link_label_Update($label, $linkURI, $projectUUID, $db){
	 $db = $this->startDB();
	 $where = array();
	 $where[] = "linkedURI = '$linkURI' ";
	 $where[] = "fk_project_uuid = '$projectUUID' ";
	 $data = array("linkedLabel" => $label);
	 $db->update("linked_data", $data, $where);
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


}//end class

?>
