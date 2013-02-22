<?php
/*
This is for doing some random edits to space items

*/
class ProjEdits_Space  {
    
	 public $sortDelimiters = array(" ", "-");
	 public $sortLabelLimit = "1";
    public $db;
	 
	 function spaceLabelSorting(){
		  $db = $this->startDB();
		  $output = array();
		  $sql = "SELECT space.uuid, space.space_label, full_context
		  FROM space
		  WHERE '".$this->sortLabelLimit."'
		  ";
		  
		  $result = $db->fetchAll($sql, 2);
		  foreach($result as $row){
				
				$itemUUID = $row["uuid"];
				$labelAll = $row["full_context"];
				
				$labelAll = str_replace("_",  "|xx|", $labelAll);
			  
				if(stristr($labelAll, "|xx|")){
					 $labelContextArray = explode("|xx|", $labelAll);
				}
				else{
					 $labelContextArray = array();
					 $labelContextArray[] = $labelAll;
				}
				
				$labelArray = array();
				$contextDepth = count($labelContextArray);
				$cdCount = 0;
				foreach($labelContextArray as $labelContext){
				  
					 $delimiters =  $this->sortDelimiters;
					 $contextPartArray = $this->explodeX($delimiters , $labelContext);
						 
					 if($cdCount + 1 == $contextDepth){
						 //use only the LAST part of the name for sorting
						 $limContextPart = array();
						 $limContextPart[] = $contextPartArray[(count($contextPartArray)-1)];
						 $contextPartArray = $limContextPart;
						 unset($limContextPart);
					 }
					 
					 foreach($contextPartArray as $contextPart){
						 $labelArray[] =  $contextPart;
					 }
					 $cdCount ++;
				}
				
				$fullNumber = "";
				foreach($labelArray as $labelPart){
					 //if(strlen($fullNumber)<=10){
					 if(true){
						  if(is_numeric("0".$labelPart)){
							  $labelPart = $labelPart + 0;
							  $fullNumber .= $this->addZeroPrefix($labelPart);
						  }
						  elseif(strlen($labelPart) == 1){
							  $labelPartNum = ord($labelPart);
							  $fullNumber .= $this->addZeroPrefix($labelPartNum, 3);
						  }
					 }
					 else{
						  if(is_numeric("0".$labelPart)){
							  $labelPart = $labelPart + 0;
							  $fullNumber .= $this->addZeroPrefix($labelPart, 2);
						  }
						  elseif(strlen($labelPart) == 1){
							  $labelPartNum = ord($labelPart);
							  $fullNumber .= $this->addZeroPrefix($labelPartNum, 2);
						  }
					 }
				}
				$fullNumber = ".".$fullNumber;
				$fullNumber = $fullNumber."";
				$output[] = array("uuid" => $itemUUID,
										"sort"=> $fullNumber,
										"context" => $labelAll);
				
				$data = array("label_sort" => $fullNumber);
				$where = array();
				$where[] = "uuid = '".$itemUUID."' ";
				$db->update("space", $data, $where);
		  }
		  return $output;
	 }
	 
	 function explodeX($delimiters,$string){
		  $return_array = Array($string); // The array to return
		  $d_count = 0;
		  while (isset($delimiters[$d_count])) // Loop to loop through all delimiters
		  {
				$new_return_array = Array(); 
				foreach($return_array as $el_to_split) // Explode all returned elements by the next delimiter
				{
					 $put_in_new_return_array = explode($delimiters[$d_count],$el_to_split);
					 foreach($put_in_new_return_array as $substr) // Put all the exploded elements in array to return
					 {
						  $new_return_array[] = $substr;
					 }
				}
				$return_array = $new_return_array; // Replace the previous return array by the next version
				$d_count++;
		  }
		  return $return_array; // Return the exploded elements
	  }
	 
	 
	 function addZeroPrefix($number, $totalLen = 4){
		  $numberLen = strlen($number);
		  if($numberLen < $totalLen){
				while(strlen($number) < $totalLen){
					 $number = "0".$number;
				}
		  }
		  return $number;
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
