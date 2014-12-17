<?php

class dataEdit_editProperties  {
    
    public $projectUUID;
    public $dataTableName = 'manual';
    
    
	public function get_var_list(){
		$projectUUID = $this->projectUUID;
		$dataTableName = $this->dataTableName;
	    
		$db = Zend_Registry::get('db');
		$this->setUTFconnection($db);
		
		$sql = "SELECT variable_uuid, var_label, var_type
		FROM var_tab
		WHERE project_id = '$projectUUID' ;
		";
		
		$output = array();
		$varRows = $db->fetchAll($sql, 2);
		if(!$varRows){
			return false;
		}
		else{
			foreach($varRows as $row){
				$actVar = $row;
				$varUUID = $row["variable_uuid"];
				
			}	
			
		}
		
	}
    
    
	//this makes a new property or returns an existing property id for a given
	//variable id and value id pair
	public function get_make_PropID($variableUUID, $valueUUID){
	    
	    $projectUUID = $this->projectUUID;
	    $dataTableName = $this->dataTableName;
	    
	    $db = Zend_Registry::get('db');
	    $this->setUTFconnection($db);
	    
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
		    'source_id'          => $dataTableName,
		    'prop_hash'         => $propHash,
		    'property_uuid'     => $propUUID,
		    'variable_uuid'     => $variableUUID,
		    'value_uuid'        => $valueUUID
		);
		
		$db->insert('properties', $data);
	    }
	    
	   return $propUUID;
	    
	}
    
    
    
	//this function returns the variable and value id for a property
	public function propID_VarVal($variableUUID, $valueUUID){
	    
	    $db = Zend_Registry::get('db');
	    $this->setUTFconnection($db);
	    
	    $projectUUID = $this->projectUUID;
	    
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
	public function propID_Components($propUUID){
	    
	    $db = Zend_Registry::get('db');
	    $this->setUTFconnection($db);
	    
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
    
	//this function changes text associated with a given valueid
	//it returns a different valueid if the text already exists in a project
	public function alter_ValID($valNewText, $oldValID){
	    
	    $projectUUID = $this->projectUUID;
	    $dataTableName = $this->dataTableName;
	    
	    $db = Zend_Registry::get('db');
	    $this->setUTFconnection($db);
	    
	    $valText = trim($valNewText);
	    $valScram   = md5($valNewText . $projectUUID);
	    $qvalText = addslashes($valNewText);
	    
	    $sql = "SELECT val_tab.value_uuid
	    FROM val_tab
	    WHERE ((val_tab.val_text = '$qvalText'
	    AND val_tab.project_id = '$projectUUID')
	    OR val_tab.text_scram = '$valScram')
	    AND val_tab.value_uuid != '$oldValID'
	    ";
	    
	    $valRows = $db->fetchAll($sql, 2);
	    if($valRows){
		$valueUUID = $valRows[0]["value_uuid"]; //there's already a value-id for this new Text
		return $valueUUID;
	    }
	    else{
		//the value is new, so can alter the existing value-id
		$where = array();
		$where[] = "value_uuid = '$oldValID' ";
		$where[] = "project_id = '$projectUUID' ";
		$data = array('text_scram' => $valScram,
				'val_text' => $valNewText);
		$db->update("val_tab", $data, $where);
		return $oldValID;
	    }
	    
	}//end function
    
    
	//this function changes a the value ID associated with a prop id
	public function alter_propID_valID($oldValID, $newValID, $variableUUID, $propUUID, $subjectUUID = false){
	    
	    $projectUUID = $this->projectUUID;
	    
	    $db = Zend_Registry::get('db');
	    $this->setUTFconnection($db);
	
	    if($oldValID != $newValID){
		
		$CheckPropUUID = $this->propID_VarVal($variableUUID, $newValID);
		
		if(!$CheckPropUUID){
		    //the new variable id / value id pair does not exist.
		    //set the existing prop id to have the new value id
		    $AlterPropHash = md5($projectUUID . $variableUUID . $newValID); 
		    $where = array();
		    $where[] = " property_uuid = '$propUUID' ";
		    $where[] = " project_id = '$projectUUID' ";
		    
		    $data = array("prop_hash" => $AlterPropHash);
		    $db->update("properties", $data, $where);
		    return $propUUID;
		}
		else{
		    //the new variable id / value id pair already exists as $CheckPropUUID
		    //this means that the altered property id ($propUUID) needs to be deleted
		    //and that all uses of it ($propUUID) in the obs table needs to be updated to the
		    //altered property id ($CheckPropUUID)
		    
		    
		    //if a subjectUUID is present, then limit all alterations to 1 item
		    if($subjectUUID != false){
			$subTermA = " AND observe.subject_uuid = '$subjectUUID' ";
		    }
		    else{
			$subTermA = "";
		    }
		    
		    $sql = "SELECT observe.subject_uuid, observe.obs_num
		    FROM observe
		    WHERE observe.property_uuid = '$propUUID'
		    AND observe.project_id = '$projectUUID'
		    $subTermA
		    ";
	    
		    $obsRows = $db->fetchAll($sql, 2);
		    foreach($obsRow as $actObs){
			$objectUUID = $actObs["subject_uuid"];
			$subObs = $actObs["obs_num"];
			$obsHashText = md5($projectUUID . "_" . $objectUUID . "_" . $subObs . "_" . $CheckPropUUID);
			$where = array();
			$where[] = "subject_uuid = '$objectUUID' ";
			$where[] = "property_uuid = '$propUUID' ";
			$where[] = "project_id = '$projectUUID' ";
			$where[] = "obs_num = '$subObs' ";
			
			if($subjectUUID != false){
			    $where[] = "subject_uuid = '$subjectUUID' ";
			}
			
			
			$data = array("hash_obs" => $obsHashText,
				      "property_uuid" => $CheckPropUUID);
			
			$db->update("observe", $data, $where);   
		    }
		    
		    return $CheckPropUUID;
		}
	    }
	    else{
		return $propUUID;
	    }
	    
	}//end function
    
    
	//this function gets a valueID or makes a new valueID for a given string of text
	public function get_make_ValID($valText){
	    
	    $projectUUID = $this->projectUUID;
	    $dataTableName = $this->dataTableName;
	    
	    $db = Zend_Registry::get('db');
	    $this->setUTFconnection($db);
	    
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
			'source_id'          => $dataTableName,
			'text_scram'        => $valScram,
			'val_text'          => $valText,
			'value_uuid'        => $valueUUID,
			'val_num'           => $numval
		);
		
		$db->insert('val_tab', $data);
	    }
	    
	   return $valueUUID;
	    
	}//end function
	
	
	public function setUTFconnection($db){
		$sql = "SET collation_connection = utf8_unicode_ci;";
		$db->query($sql, 2);
		$sql = "SET NAMES utf8;";
		$db->query($sql, 2);
	}//end function
    
}  
