<?php

class dataEdit_VarPropNotes  {
    
    public $db; //database
	 public $showPropCounts = true;
	 public $alphaSort = false;
	 
	 public $varUUID; //uuid for the varible
	 public $varLabel; //label or name for the variable
	 public $projUUID; //project UUID
	 public $varLinkURI; //linked URI for the variable
	 public $varLinkLabel; //linked data label for the variable
	 
	 public $propData; //data about linking relations for properties
    
    
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
				 linked_data.linkedURI,
				 properties.note
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
				 linked_data.linkedURI,
				 properties.note
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
    
	 //update the note for a property
	 function updatePropNote($propertyUUID, $note){
		  
		  $db = $this->startDB();
		  $note = trim($note);
		  $note = addslashes($note);
		  
		  $data = array("note" => $note);
		  $where = "property_uuid = '$propertyUUID' ";
		  $db->update("properties", $data, $where);
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
