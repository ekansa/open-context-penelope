<?php
/*
This is for doing some random edits to space items

*/
class dataEdit_Link  {
    
	 public $projectUUID;
    public $db;
	 
	 function addLinkingRel($originUUID, $originType, $targetUUID, $targetType, $linkFieldValue, $dataTableName = 'manual', $obsNum = 1){
                
        $db = $this->startDB();
		  $projectUUID = $this->projectUUID;
		  
		  
        //add origin and targget for this resource
        $hashLink       = md5($originUUID . '_' . $obsNum . '_' . $targetUUID . '_' . $linkFieldValue);
        
        $sql = "SELECT links.link_uuid
        FROM links
        WHERE links.project_id = '$projectUUID'
        AND links.hash_link = '$hashLink '
        ";
        
        $linkRows = $db->fetchAll($sql, 2);
        if($linkRows ){
            $linkUUID = $linkRows [0]["link_uuid"];
        }
        else{
            $linkUUID       = GenericFunctions::generateUUID();                            
            $data = array(
                        'project_id'   => $projectUUID,
                        'source_id'          => $dataTableName,
                        'hash_link'         => $hashLink,
                        'link_type'         => $linkFieldValue,
                        'link_uuid'         => $linkUUID,
                        'origin_type'       => $originType,         
                        'origin_uuid'       => $originUUID,              
                        'origin_obs'        => $obsNum,
                        'targ_type'         => $targetType,        
                        'targ_uuid'         => $targetUUID,         
                        'targ_obs'          => $obsNum 
                    );
                    //Zend_Debug::dump($data);
            $db->insert("links", $data);
                   
        }//end addition of new object linking
        
        return $linkUUID;
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
