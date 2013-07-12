<?php
/*
This is for doing some random edits to space items

*/
class dataEdit_Property  {
    
	 public $projectUUID;
	 public $sourceID;
    public $db;
	 
	 
	 //if you know the variable UUID, and the subjectUUID you can create and add a property.
	 function add_obs_varUUID_value($valText, $variableUUID, $subjectUUID, $subjectType, $obs = 1, $projectUUID = false, $sourceID = 'manual'){
		  
		  if($this->projectUUID){
				$projectUUID = $this->projectUUID;
		  }
		  if($this->sourceID){
				$sourceID = $this->sourceID;
		  }
		  
		  $valueUUID = $this->get_make_ValID($valText, $projectUUID, $sourceID); //get the UUID for the value
		  $propertyUUID = $this->get_make_PropID($variableUUID, $valueUUID, $projectUUID, $sourceID);
		  $output = $this->add_obs_property($propertyUUID, $subjectUUID, $subjectType, $obs, $projectUUID, $sourceID);
		  
		  return $output;
	 }
	 
	 //delete a given variable from the obserevations
	 function delete_obs_varUUID($variableUUID, $subjectUUID){
		  
		  $db = $this->startDB();
		  
		  $sql = "DELETE observe
		  FROM observe
		  JOIN properties ON properties.property_uuid = observe.property_uuid
		  WHERE properties.variable_uuid = '$variableUUID'
		  AND observe.subject_uuid = '$subjectUUID'
		  ";
		  
		  $db->query($sql);
	 }
	 
	 
	 
	 //add a property to an observation
	 function add_obs_property($propertyUUID, $subjectUUID, $subjectType, $obs = 1, $projectUUID = false, $sourceID = 'manual'){
		  
		  if($this->projectUUID){
				$projectUUID = $this->projectUUID;
		  }
		  if($this->sourceID){
				$sourceID = $this->sourceID;
		  }
		  
        $db = $this->startDB();
		  $data = array();
		  $data["project_id"] = $projectUUID;
		  $data["source_id"] = $sourceID;
		  $data["subject_type"] = $subjectType;
		  $data["subject_uuid"] = $subjectUUID;
		  $data["obs_num"] = $obs;
		  $data["property_uuid"] = $propertyUUID;
		  $data["hash_obs"] = md5($projectUUID . "_" . $subjectUUID . "_" . $obs . "_" . $propertyUUID);
		  
		  try{
				$db->insert("observe", $data);
				$output = true;
		  } catch (Exception $e) {
				$output = false;
		  }
		  return $output;
	 }
	 
	 
	 
	 //get make a property
	 function get_make_PropID($variableUUID, $valueUUID, $projectUUID = false, $sourceID = 'manual'){
        
		  if($this->projectUUID){
				$projectUUID = $this->projectUUID;
		  }
		  if($this->sourceID){
				$sourceID = $this->sourceID;
		  }
		  
		  
        $db = $this->startDB();
		  
        $propHash   = md5($projectUUID . $variableUUID . $valueUUID);
        
        $sql = "SELECT properties.property_uuid
        FROM properties
        WHERE (properties.variable_uuid = '$variableUUID'
        AND properties.value_uuid = '$valueUUID'
        AND properties.project_id = '$projectUUID')
        OR properties.prop_hash = '$propHash'
        ";
        
        $propRows = $db->fetchAll($sql, 2);
        if($propRows){
            $propUUID = $propRows[0]["property_uuid"];
        }
        else{
            
            $propUUID   = GenericFunctions::generateUUID();
                        //insert the property into the properties table:
            $data = array(
                'project_id'   => $projectUUID,
                'source_id'          => $sourceID,
                'prop_hash'         => $propHash,
                'property_uuid'     => $propUUID,
                'variable_uuid'     => $variableUUID,
                'value_uuid'        => $valueUUID
            );
            
            $db->insert('properties', $data);
        }
        
		  return $propUUID;  
    }
	 
	 
	 
    //this function gets a valueID or makes a new valueID for a given string of text
    function get_make_ValID($valText, $projectUUID = false, $sourceID = 'manual'){
        
		  if($this->projectUUID){
				$projectUUID = $this->projectUUID;
		  }
		  if($this->sourceID){
				$sourceID = $this->sourceID;
		  }
		  
        $db = $this->startDB();
		  
        $valText = trim($valText);
        $qvalText = addslashes($valText);
        $qvalShort = addslashes(substr($valText,0,199));
        
        if(strlen($qvalText)<200){
            $textCond = "val_tab.val_text = '$qvalText' ";
        }
        else{
            
            $textCond = "val_tab.val_text LIKE '$qvalShort%' ";
        }
        
        $valScram   = md5($valText . $projectUUID);
        
        $sql = "SELECT val_tab.value_uuid
        FROM val_tab
        WHERE ($textCond
        AND val_tab.project_id = '$projectUUID')
        OR val_tab.text_scram = '$valScram'
        ";
        
        $valRows = $db->fetchAll($sql, 2);
        if($valRows){
            $valueUUID = $valRows[0]["value_uuid"];
        }
        else{
            $valueUUID = GenericFunctions::generateUUID();
            $numval = null;
            if(strlen($valText) > 0){
                $numcheck = "0".$valText;
                if(is_numeric($numcheck)){
                    $numval = $numcheck;
                }
            }
            
            //insert the value into the val_tab table:
            $data = array(
                    'project_id'   => $projectUUID,
                    'source_id'          => $sourceID,
                    'text_scram'        => $valScram,
                    'val_text'          => $valText,
                    'value_uuid'        => $valueUUID,
                    'val_num'           => $numval
            );
            
            $db->insert('val_tab', $data);
        }
        
       return $valueUUID;
    }
	 
	 
	 
	 //this function returns the variable and value id for a property
    function propID_VarVal($variableUUID, $valueUUID, $projectUUID = false){
        
		  if($this->projectUUID){
				$projectUUID = $this->projectUUID;
		  }
		  
		  
        $db = $this->startDB();
		  
        $sql = "SELECT properties.property_uuid
        FROM properties
        WHERE properties.value_uuid = '$valueUUID'
        AND properties.variable_uuid = '$variableUUID'
        AND properties.project_id = '$projectUUID'
        ";
        
        $propRows = $db->fetchAll($sql, 2);
        $propUUID = false;
        if($propRows){
            $propUUID = $propRows[0]["property_uuid"];
        }
        return $propUUID;
    }
	 
	 //this function returns the variable and value id for a property
	 function propID_Components($propUUID){
        
        $db = $this->startDB();
		  
        $sql = "SELECT properties.variable_uuid, properties.value_uuid,
        properties.project_id, val_tab.val_text, var_tab.var_label
        FROM properties
        JOIN val_tab ON properties.value_uuid = val_tab.value_uuid
        LEFT JOIN var_tab ON properties.variable_uuid = var_tab.variable_uuid
        WHERE properties.property_uuid = '$propUUID'
        ";
        
        $propRows = $db->fetchAll($sql, 2);
        $output = false;
        if($propRows){
            $output = array();
            $output["variableUUID"] = $propRows[0]["variable_uuid"];
            $output["valueUUID"] = $propRows[0]["value_uuid"];
            $output["projectUUID"] = $propRows[0]["project_id"];
            $output["valText"] = $propRows[0]["val_text"];
            $output["varLabel"] = $propRows[0]["val_text"];
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
