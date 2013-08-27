<?php

//fix space entities where the source datatable did not have unique labeling
//and we should not have repeating variables
class dataEdit_SpaceTime  {
    
    public $db;
	 public $projectUUID;
	
	
	 public $requestParams; //request parameters
	 
	 function chrontoTagItem($uuid = false){
		  
		  $requestParams = $this->requestParams;
		  $tStart = $_REQUEST['tStart'];
        $tEnd = $_REQUEST['tEnd'];
		  
		  if(!$uuid){
				if(isset($_REQUEST['uuid'])){
					 $uuid =  $_REQUEST['uuid'];
				}
		  }
		  
		  if($uuid != false){
				if(!is_numeric($tStart)){
					 $tStart = 0;
				}
				if(!is_numeric($tEnd)){
					 $tEnd = 0;
				}
				
				if($tEnd < $tStart){
					 $tHold = $tStart;
					 $tStart = $tEnd;
					 $tEnd = $tHold;
				}
				
				if($tEnd != $tStart){        
					 $dateLabel = "(".$this->makeNiceDate($tStart)." - ".$this->makeNiceDate($tEnd).")";
				}
				else{
					 $dateLabel = "(".$this->makeNiceDate($tStart).")";
				}
		  
				if(!isset($requestParams["projectUUID"])){
					 $projectUUID = $this->getProjectUUID($uuid);
				}
		  
				$db = $this->startDB();
				$where = array();
				$where[] = "uuid  = '".$uuid."' ";
				$db->delete('initial_chrono_tag', $where);
				
				$data = array('project_id'=> $projectUUID,
				'uuid'=> $uuid,
				'creator_uuid'=> 'oc',
				'label'=> $dateLabel,
				'start_time'=> $tStart,
				'end_time'=> $tEnd,
				'note_id'=> 'Default set',
				'public'=> 1
				);
				
				$db->insert('initial_chrono_tag', $data);
		  }

	 }
	 
	 //selects items by assocsiation with a property uuid, then chronology tags them
	 function chrontoTagByProperty($propUUID){
		  
		  $output = array();
		  $db = $this->startDB();
		  
		  $sql = "SELECT DISTINCT subject_uuid AS itemUUID
		  FROM observe
		  WHERE property_uuid = '$propUUID'
		  ";
		  
		  $result =  $db->fetchAll($sql);
		  if($result){
				$publishedObj = new dataEdit_Published;
				foreach($result as $row){
					 $itemUUID = $row["itemUUID"];
					 $this->chrontoTagItem($itemUUID);
					 $publishedObj->deleteFromPublishedDocsByParentUUID($itemUUID); //deletes the item and it's children from the list of published items
					 $output[] = $itemUUID ;
				}
		  }
		  
		  return $output;
	 }
	 
	 
	 function getProjectUUID($uuid){
		  if(!$this->projectUUID){
				$db = $this->startDB();
				$sql = "SELECT project_id FROM space WHERE uuid = '$uuid' LIMIT 1; ";
				$result =  $db->fetchAll($sql);
				if($result){
					 $this->projectUUID = $result[0]["project_id"];
				}
		  }
		  
		  return $this->projectUUID;
	 }
	 
	 
	 function makeNiceDate($dec_time){
		  //this function creates human readible dates, with a CE, BCE notation
		  //large values have a K for thousands or an M for millions appended()
		  
		  $abs_time = abs($dec_time);
		 
		  if($dec_time<0){
				$suffix = " BCE";
		  }
		  else{
				$suffix = " CE";
		  }
		  
		  if($abs_time<10000){
				if($dec_time<0){
					 $output = (number_format($abs_time)).$suffix;
				}
				else{
					 $output = round($abs_time,0).$suffix;
				}
		  }//end case with less than 10,000
		  else{
			  
				if($abs_time<1000000){
					 $rnd_time = round($abs_time/1000,2);
					 $output = (number_format($rnd_time))."K".$suffix;
				}
				else{
					 $rnd_time = round($abs_time/1000000,2);
					 $output = (number_format($rnd_time))."M".$suffix;
				}
		  }
	
		  return $output;

	 }//end function
	 
	 
	
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
    
}  
