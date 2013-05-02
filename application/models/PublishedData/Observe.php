<?php

class PublishedData_Observe  {
    
    public $itemUUID;
    public $projectUUID;
    public $sourceID;
   
    public $db;
	 public $errors;
	 
	 function addObservations($itemXML, $originType){
		
		  $originUUID = $this->itemUUID;
		  $projectUUID = $this->projectUUID;
		  $sourceID = $this->sourceID;
		
		  $obsData = array();
		  $obsCount = 1;
		  if ($itemXML->xpath("//arch:observation")) {   //case where properties are in observations
			  foreach ($itemXML->xpath("//arch:observation") as $act_obs){
				  
				  $obsNumber = 1;
				  if($act_obs->xpath("@obsNumber")){
					  foreach($act_obs->xpath("@obsNumber") as $obsNumber){
						  $obsNumber = $obsNumber +0;
					  }
				  }
				  
				  $pp = 1;
				  foreach ($act_obs->xpath("//arch:property") as $act_prop){
					  $actObsData = array();
					  $actObsData["project_id"] = $projectUUID;
					  $actObsData["source_id"] = $sourceID;
					  $actObsData["subject_type"] = $originType;
					  $actObsData["subject_uuid"] = $originUUID;
					  $actObsData["obs_num"] = $obsNumber;
					  $actObsData["property_uuid"] = false;
					  foreach($act_prop->xpath("oc:propid") as $act_prop_id){
						  $actObsData["property_uuid"] = $act_prop_id."";
					  }
					  if(!$actObsData["property_uuid"]){
						  $actObsData["property_uuid"] = "gen-".$pp;
					  }
					  
					  $actObsData["hash_obs"] = md5($projectUUID . "_" . $actObsData["subject_uuid"] . "_" . $actObsData["obs_num"] . "_" . $actObsData["property_uuid"]);
					  $obsData[] = $actObsData;
					  unset($actObsData);
					  $pp++;
				  }
				  $obsCount++;
			  }
		  }
		  else{
				$obsCount = 1;
				foreach ($itemXML->xpath("//arch:property") as $act_prop){
					  $actObsData = array();
					  $actObsData["project_id"] = $projectUUID;
					  $actObsData["source_id"] = $sourceID;
					  $actObsData["subject_type"] = $originType;
					  $actObsData["subject_uuid"] = $originUUID;
					  $actObsData["obs_num"] = $obsCount;
						
					  foreach($act_prop->xpath("oc:propid") as $act_prop_id){
						  $actObsData["property_uuid"] = $act_prop_id."";
					  }
					  $actObsData["hash_obs"] = md5($projectUUID . "_" . $actObsData["subject_uuid"] . "_" . $actObsData["obs_num"] . "_" . $actObsData["property_uuid"]);
					  
					  $obsData[] = $actObsData;
					  unset($actObsData);
				}
		  }
		
		  $db = $this->startDB();
		  //get rid of old observations
		  $where = array();
		  $where[] = "project_id = '$projectUUID' ";
		  $where[] = "subject_uuid = '$originUUID' ";
		  $db->delete('observe', $where);
		  
		  $okInserts = 0;
		  foreach($obsData as $act_obs){
				try{
					$db->insert('observe', $act_obs);
					$okInserts++;
				}
				catch (Exception $e) {
					 $e = (string)$e;
					 if(!stristr($e, "SQLSTATE[23000]")){
						  $errors[] = $e;
					 }	
				}
		  }//end loop
		  
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
