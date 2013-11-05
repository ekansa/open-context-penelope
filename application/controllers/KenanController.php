<?php

/** Zend_Controller_Action */
//require_once 'Zend/Controller/Action.php';
//require_once 'OpenContext/Controller/Action/Helper/SolrAccess.php';

//error_reporting(E_ALL ^ E_NOTICE);
// increase the memory limit
ini_set("memory_limit", "1024M");
// set maximum execution time to no limit
ini_set("max_execution_time", "0");

class kenanController extends Zend_Controller_Action {
    
    function init()
    {  
        require_once 'App/Util/GenericFunctions.php';
    }
    
    //make sure all connections are UTF-8 OK
    private function setUTFconnection($db){
	$sql = "SET collation_connection = utf8_unicode_ci;";
	$db->query($sql, 2);
	$sql = "SET NAMES utf8;";
	$db->query($sql, 2);
    }
    
    
    
    //clean up date strings
   function dateCleanAction(){
	$this->_helper->viewRenderer->setNoRender();
	$db = Zend_Registry::get('db');
	$this->setUTFconnection($db);
	
	$projectUUID = '3DE4CD9C-259E-4C14-9B03-8B10454BA66E';
	
	
	
	$sql = "SELECT DISTINCT properties.property_uuid, properties.variable_uuid,
	val_tab.value_uuid, val_tab.val_text
	FROM properties 
	JOIN val_tab ON properties.value_uuid = val_tab.value_uuid
	WHERE properties.variable_uuid = '8FCB621F-4414-4E1A-FF5F-5AE534959361'
	";
    
	$result = $db->fetchAll($sql, 2);
	
	foreach($result as $row){
	    
	    $propertyUUID = $row["property_uuid"];
	    $variableUUID = $row["variable_uuid"];
	    $valueUUID = $row["value_uuid"];
	    $valText = $row["val_text"];
	    
	     echo "<br/><br/><br/>checking: $valText (ValID: $valueUUID, VarID: $variableUUID, propID: $propertyUUID )";
	    
	    if(stristr($valText , "/")){
		$valTextArray = explode("/", $valText);
		
		$fixedDate = $valTextArray[2]."-".$valTextArray[1]."-".$valTextArray[0];
		echo "<br/><strong>Convert: $valText to $fixedDate </strong>";
		
		$sql = "SELECT val_tab.value_uuid, properties.property_uuid, properties.variable_uuid
		FROM val_tab
		JOIN properties ON properties.value_uuid = val_tab.value_uuid 
		WHERE val_tab.val_text = '$fixedDate'
		AND (properties.variable_uuid = '$variableUUID')
		";
		
		$resultB = $db->fetchAll($sql, 2);
		if($resultB){
		    foreach($resultB as $rowB){
			
			echo "<br/> This variable already has a value / property id for: $fixedDate.";
			
			$goodValUUID =  $rowB["value_uuid"];
			$goodPropUUID =  $rowB["property_uuid"];
			
			$this->observe_prop_change($goodPropUUID, $propertyUUID, $projectUUID, $db);
		    }
		    
		}
		else{
		    
		    echo "<br/> This variable does not already have a property id for: $fixedDate.";
		    
		    $sql = "SELECT val_tab.value_uuid
			    FROM val_tab 
			    WHERE val_tab.val_text = '$fixedDate'
			    AND val_tab.value_uuid != '$valueUUID' 
			    ";
			    
		    $resultB = $db->fetchAll($sql, 2);
		    if($resultB){
			
			echo "<br/> The value: $fixedDate already exits, updating property valueID associations. ";
			
			$goodValUUID = $resultB[0]["value_uuid"];
			
			$propHash   = md5($projectUUID . $variableUUID . $goodValUUID);
			$data = array("prop_hash" => $propHash, "value_uuid" => $goodValUUID);
			$where = array();
			$where[] = "value_uuid = '$valueUUID' ";
			$where[] = "variable_uuid = '$variableUUID' ";
			
			try{
			    $db->update("properties", $data, $where);
			}catch (Exception $e) {
			    
			    $sql = "SELECT * FROM properties WHERE value_uuid = '$goodValUUID' AND variable_uuid = '$variableUUID' ";
			    
			    $resultC = $db->fetchAll($sql, 2);
			    if($resultC){
				foreach($resultC as $rowC){
				    $goodPropUUID = $rowC["property_uuid"];    
				    $this->observe_prop_change($goodPropUUID, $propertyUUID, $projectUUID, $db);
				    
				}
			    }
			}  
			
		    }
		    else{
			
			echo "<br/> The value: $fixedDate DOES NOT exit, updating valueID association. ";
			
			$data = array("val_text" => $fixedDate);
			$where = array();
			$where[] = "value_uuid = '$valueUUID' ";
			$db->update("val_tab", $data, $where);
			
		    }
		}
		
	    }
	    
	    
	}//end loop through props
	
    }//end function
    
    
    private function observe_prop_change($goodPropUUID, $badPropertyUUID, $projectUUID, $db){
	
	if($goodPropUUID != $badPropertyUUID){
			    
	    $sql = "SELECT *
	    FROM observe
	    WHERE property_uuid = '$badPropertyUUID' ";
	    
	    $resultC = $db->fetchAll($sql, 2);
	    foreach($resultC as $rowC){
		
		$subjectUUID = $rowC["subject_uuid"];
		$obsNum = 1;
		$obsHash = md5($projectUUID . "_" . $subjectUUID . "_" . $obsNum . "_" . $goodPropUUID);
		$data = array("hash_obs" => $obsHash, "property_uuid" => $goodPropUUID);
		$where = array();
		$where[] = "property_uuid = '$badPropertyUUID' ";
		$where[] = "subject_uuid = '$subjectUUID' ";
		try{
		    echo "<br/> Observe prop updated";
		    $db->update("observe", $data, $where);
		}catch (Exception $e) {
		    echo "<br/> Duplicated";
		    $obsHash .= "dup:".$obsHash;
		    $data = array("hash_obs" => $obsHash, "subject_uuid" => "dup:".$subjectUUID);
		    $db->update("observe", $data, $where);
		}
		echo "<br/> Update: $subjectUUID, prop: $badPropertyUUID to prop: $goodPropUUID";
		unset($where);
	    }
	    
	    echo "<br/><em>Deleting old Property ID...</em>";
	    unset($where);
	    $where = array();
	    $where[] = "property_uuid = '$badPropertyUUID' ";
	    $db->delete("properties", $where);
	}
	
    }//end function
    
    
    function dpDateAddAction(){
	$this->_helper->viewRenderer->setNoRender();
	$db = Zend_Registry::get('db');
	$this->setUTFconnection($db);
	
	/*
	DELETE  FROM `observe` WHERE `source_id` = 'day-plan-date';

	DELETE  FROM `properties` WHERE `source_id` = 'day-plan-date';

	DELETE FROM val_tab WHERE `source_id` = 'day-plan-date';
 
	*/ 
	
	
	$sql = "SELECT project_id, uuid AS itemUUID, res_label as itemLabel
	FROM resource
	WHERe res_label LIKE 'Dayplan%' ;
	";
	
	$result = $db->fetchAll($sql, 2);
	foreach($result as $row){
	    $itemUUID = $row["itemUUID"];
	    $itemLabel = $row["itemLabel"];
	    $projectUUID = $row["project_id"];
	    
	    $labArray = explode("-", $itemLabel);
	    $year = $labArray[3];
	    $month = $labArray[4];
	    $day = $labArray[5];
	    
	    $planDate = $year."-".$month."-".$day;
	    $dataTab = "day-plan-date";
	    $itemType = "Media (various)";
	    
	    $valueUUID = $this->get_make_ValID($planDate, $projectUUID, $dataTab);
            $propUUID = $this->get_make_PropID("9ED499E4-5B88-4BA4-7BE2-647A027C12D0", $valueUUID, $projectUUID, $dataTab);
            $obsHashText = md5($projectUUID . "_" . $itemUUID . "_" . "1" . "_" . $propUUID);
                        
            $data = array("project_id"=> $projectUUID,
                          "source_id"=> $dataTab,
                          "hash_obs" => $obsHashText,
                          "subject_type" => $itemType,
                          "subject_uuid" => $itemUUID,
                          "obs_num" => 1,
                          "property_uuid" => $propUUID);
            try{            
                $db->insert("observe", $data); 
            } catch (Exception $e) {
                echo $e->getMessage(), "\n";
            }
	    
	    
	}
	
    }
    
    
    //this makes a new property or returns an existing property id for a given
    //variable id and value id pair
    private function get_make_PropID($variableUUID, $valueUUID, $projectUUID, $dataTableName = 'manual'){
        
        $db = Zend_Registry::get('db');
        
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
    private function propID_VarVal($variableUUID, $valueUUID, $projectUUID){
        
        $db = Zend_Registry::get('db');
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
    private function propID_Components($propUUID){
        
        $db = Zend_Registry::get('db');
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
    
    
        //this function gets a valueID or makes a new valueID for a given string of text
    private function get_make_ValID($valText, $projectUUID, $dataTableName = 'manual'){
        
        $db = Zend_Registry::get('db');
        
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
        
    }
    
    
    
    
    
    /*
     function dateCleanAction(){
	$this->_helper->viewRenderer->setNoRender();
	$db = Zend_Registry::get('db');
	$this->setUTFconnection($db);
	
	$projectUUID = '3DE4CD9C-259E-4C14-9B03-8B10454BA66E';
	$variableUUID = 'F63A8CB7-1F50-4F45-4D8D-E23462DA4755';
	
	
	$sql = "SELECT DISTINCT observe.property_uuid, val_tab.value_uuid, val_tab.val_text
	FROM observe
	JOIN properties ON observe.property_uuid = properties.property_uuid
	JOIN val_tab ON properties.value_uuid = val_tab.value_uuid
	WHERE properties.variable_uuid = '$variableUUID'
	";
    
	$result = $db->fetchAll($sql, 2);
	
	foreach($result as $row){
	    
	    $propertyUUID = $row["property_uuid"];
	    $valueUUID = $row["value_uuid"];
	    $valText = $row["val_text"];
	    
	    if(stristr($valText , "/")){
		$valTextArray = explode("/", $valText);
		
		$fixedDate = $valTextArray[2]."-".$valTextArray[1]."-".$valTextArray[0];
		echo "<br/><br/><strong>Convert: $valText to $fixedDate </strong>";
		
		$sql = "SELECT val_tab.value_uuid, properties.property_uuid, properties.variable_uuid
		FROM val_tab
		JOIN properties ON properties.value_uuid = val_tab.value_uuid 
		WHERE val_tab.val_text = '$fixedDate'
		";
		
		$resultB = $db->fetchAll($sql, 2);
		
		if($resultB){
		    foreach($resultB as $rowB){
			
			$keepValUUID = $rowB["value_uuid"];
			$keepPropUUID = $rowB["property_uuid"];
			$keepVarUUID = $rowB["variable_uuid"];
			
			$propHash   = md5($projectUUID . $keepVarUUID . $keepValUUID);
			$data = array("value_uuid" => $keepValUUID, "prop")
			
			
			//
			
			
			
			
			
			$sql = "SELECT *
			FROM observe
			WHERE property_uuid = '$propertyUUID' ";
			
			$resultC = $db->fetchAll($sql, 2);
			foreach($resultC as $rowC){
			    
			    $subjectUUID = $rowC["subject_uuid"];
			    $obsNum = 1;
			    $obsHash = md5($projectUUID . "_" . $subjectUUID . "_" . $obsNum . "_" . $keepPropUUID);
			    $data = array("hash_obs" => $obsHash, "property_uuid" => $keepPropUUID);
			    $where = array();
			    $where[] = "property_uuid = '$propertyUUID' ";
			    $where[] = "subject_uuid = '$subjectUUID' ";
			    try{
				$db->update("observe", $data, $where);
			    }catch (Exception $e) {
				echo "<br/> Duplicated";
				$db->delete("observe", $where);
			    }
			    echo "<br/> Update: $subjectUUID, prop: $propertyUUID to prop: $keepPropUUID";
			    unset($where);
			}
			
			echo "<br/><em>Deleting old Property ID...</em>";
			unset($where);
			$where = array();
			$where[] = "property_uuid = '$propertyUUID' ";
			$db->delete("properties", $where);
			
			echo "<br/><em>Deleting old value ID...</em>";
			unset($where);
			$where = array();
			$where[] = "value_uuid = '$valueUUID' ";
			$db->delete("val_tab", $where);
		    }
		    
		}
		else{
		    //$keepPropHash   = md5($projectUUID . $variableUUID . $keepValUUID);
		    $data = array("val_text" => $fixedDate);
		    $where = array();
		    $where[] = "value_uuid = '$valueUUID' ";
		    $db->update("val_tab", $data, $where);
		    echo "<br/> Update Value: $valueUUID, to $fixedDate";
		}
		
		
	    }
	    
	    
	}//end loop through props
	
    }//end function
    */
    
    private function addLinkingRel($originUUID, $originType, $targetUUID, $targetType, $linkFieldValue, $projectUUID, $dataTableName = 'manual', $obsNum = 1){
        
        $db = Zend_Registry::get('db');
        
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
    
 
 
 function dpPersonAction(){
	$this->_helper->viewRenderer->setNoRender();
	$db = Zend_Registry::get('db');
	$this->setUTFconnection($db);
	
	/*
	DELETE  FROM `observe` WHERE `source_id` = 'day-plan-date';

	DELETE  FROM `properties` WHERE `source_id` = 'day-plan-date';

	DELETE FROM val_tab WHERE `source_id` = 'day-plan-date';
 
	*/ 
	
	
	$sql = "SELECT uuid AS itemUUID
	FROM resource
	WHERE res_label LIKE '%Dayplan%'
	;
	";
	
	$result = $db->fetchAll($sql, 2);
	foreach($result as $row){
	    $itemUUID = $row["itemUUID"];
 
	    $sql = "SELECT DISTINCT plinks.link_type, plinks.targ_uuid
	    FROM space
	    JOIN links ON space.uuid = links.origin_uuid
	    JOIN links AS plinks ON (links.origin_uuid = plinks.origin_uuid AND plinks.targ_type = 'Person')
	    WHERE space.space_label LIKE '%locus%'
	    AND links.targ_uuid = '$itemUUID';
	    ";
 
	    $resultB = $db->fetchAll($sql, 2);
	    $personLinks = array();
	    if($resultB){
		foreach($resultB as $rowB){
		    $linkRel = $rowB["link_type"];
		    $personUUID = $rowB["targ_uuid"];
		    if($linkRel != "Director"){
			
			$hashKey = md5($linkRel.$personUUID.$itemUUID);
			if(!array_key_exists($hashKey, $personLinks)){
			    $personLinks[$hashKey] = array("rel" => $linkRel, "targ" => $personUUID);
			}
			
		    } 
		}
		
		if(count($personLinks)>0){
		    
		    $originUUID = $itemUUID; //diary is origin item
		    $projectUUID = "3DE4CD9C-259E-4C14-9B03-8B10454BA66E";
		   
		    foreach($personLinks as $key => $linkArray){
			
			$linkFieldValue = $linkArray["rel"];
			$targetUUID = $linkArray["targ"];
			$this->addLinkingRel($originUUID, "Media (various)", $targetUUID, "Person", $linkFieldValue, $projectUUID, 'dp-person-manual');

		    }
		    
		}
		
	    }
	    unset($resultB);
	    unset($personLinks);
 
	}//end loop through journal entries
	
 }//end function
  
   
   //this fixes duplicate journal labels
    function journalDateFixAction(){

	$this->_helper->viewRenderer->setNoRender();
	$db = Zend_Registry::get('db');
	$this->setUTFconnection($db);
	
	
	
	$sql = "SELECT count(field_4) as labelCount, field_4 as label
	FROM z_1_1e3575e43
	GROUP BY field_4
	ORDER BY count(field_4) DESC
	";
	
	$result = $db->fetchAll($sql, 2);
	foreach($result as $row){
    
	    $countLab = $row["labelCount"];
	    $label = $row["label"];
	    
	    if($countLab > 1){
		
		$sql = "SELECT * FROM z_1_1e3575e43 WHERE field_4 = '$label' ;";
		
		$resultB = $db->fetchAll($sql, 2); 
		$i = 1;
		foreach($resultB as $rowB){
		    $id = $rowB["id"];
		    $newLabel = $label." ($i)";
		    $data = array("field_4" => $newLabel);
		    echo "<br/>($id) $label should now be: ".$newLabel;
		    $where = "id = $id ";
		    
		    if($i>1){
			$db->update("z_1_1e3575e43", $data, $where);
		    }
		    $i++;
		}
		
	    }
	    
	    
	}
    }
   






    function locusJournalLinkAction(){
	
	$this->_helper->viewRenderer->setNoRender();
	$db = Zend_Registry::get('db');
	$this->setUTFconnection($db);
	
	
	
	$sql = "SELECT space.uuid, space_contain.parent_uuid, space.project_id
	FROM space
	JOIN space_contain ON space.uuid = space_contain.child_uuid
	WHERE space.space_label LIKE 'Locus%'
	
	";
	
	$result = $db->fetchAll($sql, 2);
	foreach($result as $row){
	    
	    $itemUUID = $row["uuid"];
	    $trenchUUID  = $row["parent_uuid"];
	    $projectUUID = $row["project_id"];
	    
	    //start date
	    $sql = "SELECT val_tab.val_text
	    FROM observe
	    JOIN properties ON observe.property_uuid = properties.property_uuid
	    JOIN val_tab ON properties.value_uuid = val_tab.value_uuid
	    WHERE observe.subject_uuid = '$itemUUID'
	    AND properties.variable_uuid = '667B68A9-7283-4DA5-86CB-2742586DB672'
	    LIMIT 1
	    ";
	    
	    //echo "<br/><br/>".$sql;
	
	    $startRaw = false;
	    $resultS = $db->fetchAll($sql, 2);
	    if($resultS){
		$startRaw = $resultS[0]["val_text"];
	    }
	    
	    //end date
	    $sql = "SELECT val_tab.val_text
	    FROM observe
	    JOIN properties ON observe.property_uuid = properties.property_uuid
	    JOIN val_tab ON properties.value_uuid = val_tab.value_uuid
	    WHERE observe.subject_uuid = '$itemUUID'
	    AND properties.variable_uuid = 'F63A8CB7-1F50-4F45-4D8D-E23462DA4755'
	    LIMIT 1
	    ";
	    
	    //echo "<br/><br/>".$sql;
	
	    $endRaw = false;
	    $resultE = $db->fetchAll($sql, 2);
	    if($resultE){
		$endRaw = $resultE[0]["val_text"];
	    }
	    
	    if($startRaw != false && $endRaw != false){
		
		$startExplode = explode("/", $startRaw);
		$startDate = $startExplode[2]."-".$startExplode[1]."-".$startExplode[0];
		$endExplode = explode("/", $endRaw);
		$endDate = $endExplode[2]."-".$endExplode[1]."-".$endExplode[0];
		
		$startNumDate = strtotime($startDate);
		$endNumDate = strtotime($endDate);
		
		//get diaries associated with the trench in question
		$sql = "SELECT links.targ_uuid, diary.diary_label as linkedLabel, 'Diary / Narrative' as type
		FROM links
		JOIN diary ON links.targ_uuid = diary.uuid
		WHERE links.origin_uuid = '$trenchUUID'
		AND (links.targ_type = 'Diary / Narrative')
		
		UNION
		
		SELECT links.targ_uuid, resource.res_label as linkedLabel, 'Media (various)' as type
		FROM links
		JOIN resource ON links.targ_uuid = resource.uuid
		WHERE links.origin_uuid = '$trenchUUID'
		AND (links.targ_type = 'Media (various)')
		";
		
		//echo "<br/><br/>".$sql;
		
		$resultB = $db->fetchAll($sql, 2);
		$linkArray = array();
		foreach($resultB as $rowB){
		    
		    $targUUID = $rowB["targ_uuid"];
		    $linkedName = $rowB["linkedLabel"];
		    $targType = $rowB["type"];
		    
		    $targDate = false;
		    //if($targType == "Diary / Narrative"){
		    if(true){
			$targDate = $this->diary_date_make($linkedName);
		    }    
		    
		    if($targDate != false){
			$targNumDate = strtotime($targDate);
			if(($startNumDate <= $targNumDate) && ($endNumDate >= $targNumDate)){
			    //echo "<br/> FOUUND! ($targUUID) $targType: ".$linkedName. " Date: $targDate";
			    //echo "<br/><br/>Locus ($itemUUID) $startDate to $endDate LINKS with ($targUUID), dating to: $targDate";
			    $sortKey = $targNumDate;
			    if($targType == "Diary / Narrative"){
				$sortKey = "A-".$sortKey;
			    }
			    else{
				$sortKey = "B-".$sortKey;
			    }
			    $linkArray[$sortKey] = array("targ_uuid" => $targUUID, "label" => $linkedName, "type" => $targType, "date" => $targDate);
			}
		    }
		}
		
		if(count($linkArray)>0){
		    ksort($linkArray);
		    foreach($linkArray as $link){
			$targUUID = $link["targ_uuid"];
			$targDate = $link["date"];
			$targLabel = $link["label"];
			$targType = $link["type"];
			echo "<br/><br/>Locus ($itemUUID) $startDate to $endDate LINKS with $targLabel ($targUUID), dating to: $targDate";
			$this->addLinkingRel($itemUUID , 'Locations or Objects', $targUUID, $targType, 'link', $projectUUID, 'manual-locus');
		    }
		
		}
		
	    }
	    
	}
	
	
    }






    function siteMapAction(){
	$this->_helper->viewRenderer->setNoRender();
	$db = Zend_Registry::get('db');
	$this->setUTFconnection($db);
	
	
	
	$sql = "SELECT space.uuid, space.space_label
	FROM space
	WHERE space.project_id =  '3FAAA477-5572-4B05-8DC1-CA264FE1FC10'
	AND space.class_uuid =  '66F3BD1C-55F6-4C48-E76A-25F6176E1409'
	";
	
	$result = $db->fetchAll($sql, 2);
	foreach($result as $row){
	    $itemUUID = $row["uuid"];
	    $itemLabel = $row["space_label"];
	    
	    $sql = "SELECT * FROM geo_space WHERE uuid = '$itemUUID' LIMIT 1";
	    
	    $resultB = $db->fetchAll($sql, 2);
	    if($resultB){
		//echo "<br/> $itemLabel ($itemUUID) has geo";
	    }
	    else{
		//echo "<br/> $itemLabel ($itemUUID) <strong>has NO Geo</strong>";
		$itemURI = "http://opencontext.org/subjects/".$itemUUID.".atom";
		echo "<br/> $itemLabel URI: $itemURI";
		
		
		@$atomString = file_get_contents($itemURI);
		$pointString = false;
		if($atomString){
		    $itemXML = simplexml_load_string($atomString);
		    $itemXML->registerXPathNamespace("atom", "http://www.w3.org/2005/Atom");
		    $itemXML->registerXPathNamespace("georss", "http://www.georss.org/georss");
		    
		    foreach ($itemXML->xpath("//georss:point") as $xpathResult){
			$pointString = (string)$xpathResult;
		    }
		    
		    
		}
		
		if($pointString != false){
		    $lanLon = explode(" ", $pointString);
		    $lat = $lanLon[0];
		    $lon = $lanLon[1];
		    
		    echo "-> $itemLabel is at $lat, $lon ($itemUUID )";
		    
		    $data = array("project_id" => '3FAAA477-5572-4B05-8DC1-CA264FE1FC10',
				  "source_id" => 'manual',
				  "uuid" => $itemUUID,
				  "latitude" => $lat,
				  "longitude" => $lon);
		    
		    $db->insert("geo_space", $data);
		    
		    unset($data);
		    $data = array("source_id" => "public");
		    $where = "uuid = '$itemUUID' ";
		    $db->update("space", $data, $where);
		}
		
		sleep(.5);
	    }
	    
	    /*
	    if(true){
		
		// 33.291388,-117.344511

		
		$lat = 33.291388;
		$lon = -117.344511;
		
		echo "$itemLabel is at $lat, $lon ($itemUUID )";
		
		$data = array("project_id" => '3FAAA477-5572-4B05-8DC1-CA264FE1FC10',
			      "source_id" => 'manual',
			      "uuid" => $itemUUID,
			      "latitude" => $lat,
			      "longitude" => $lon);
		
		$db->insert("geo_space", $data); 
		
	    }
	    */
	
	}
	
	
	
    }


	/* This function finds numeric characters in a name
     so that they can be used for sorting
    */
	
 private function explodeX($delimiters,$string){
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
 
 
 
 
 function labelAction(){
	$this->_helper->viewRenderer->setNoRender();
	$db = Zend_Registry::get('db');
	$this->setUTFconnection($db);
	
	
	
	
	
	$sql = "SELECT space.uuid, space.space_label, full_context
	FROM space
	WHERE source_id = 'z_1_456098914'
		OR source_id = 'z_1_0226254e9' 
	";
	
	$sql = "SELECT space.uuid, space.space_label, full_context
	FROM space
	WHERE project_id = '3F6DCD13-A476-488E-ED10-47D25513FCB2'
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
			
			/*
			if(stristr("_", $labelContext)){
				if(strlen("Bone 1002_100") == strlen($labelContext)){
					$replacer = "0";
				}
				elseif(strlen("Bone 1002_10") == strlen($labelContext)){
					$replacer = "00";
				}
				elseif(strlen("Bone 1002_1") == strlen($labelContext)){
					$replacer = "000";
				}
				$labelContext = str_replace("_", $replacer, $labelContext);
			}
			*/
			
			/*
			if(stristr($labelAll, " ")){
				$contextPartArray = explode(" ", $labelContext);
			}
			elseif(stristr($labelAll, "-")){
				$contextPartArray = explode("-", $labelContext);
			}
			else{
				$contextPartArray  = array();
				$contextPartArray[] = $labelContext;
			}
			*/
			
			$delimiters = array(" ", "-");
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
	    echo "<br/>$itemUUID Sort $fullNumber context: $labelAll";
	    
	    $data = array("label_sort" => $fullNumber);
	    $where = array();
	    $where[] = "uuid = '".$itemUUID."' ";
	    $db->update("space", $data, $where);
	}
 }


    private function addZeroPrefix($number, $totalLen = 4){
	$numberLen = strlen($number);
	if($numberLen < $totalLen){
	    while(strlen($number) < $totalLen){
		$number = "0".$number;
	    }
	}
	return $number;
    }





    function findDateAction(){
	$this->_helper->viewRenderer->setNoRender();
	$db = Zend_Registry::get('db');
	$this->setUTFconnection($db);
	
	$sql = "SELECT id, field_10
	FROM  z_1_f1171314d_2
	";
	
	$result = $db->fetchAll($sql, 2);
	foreach($result as $row){
	    
	    $dateOld = $row["field_10"];
	    $id = $row["id"];
	    
	    if(stristr($dateOld, "/")){
		$dateArray = explode("/", $dateOld);
		
		$dateVal = $dateArray[2]."-".$dateArray[1]."-".$dateArray[0];
		
		
		$data = array("field_10" => $dateVal);
		$where = array();
		$where[] = "id = $id  ";
		$db->update("z_1_f1171314d_2", $data, $where);
	    }
	}
 }


function imageCheckAction(){

    $this->_helper->viewRenderer->setNoRender();
    $db = Zend_Registry::get('db');
    $this->setUTFconnection($db);

    $sql = "SELECT uuid
    FROM resource
    WHERE source_id = 'z_1_033224118' 
    ";
	
    $result = $db->fetchAll($sql, 2);
    foreach($result as $row){
	 $itemUUID = $row["uuid"];
	 $url = "http://penelope2.oc/preview/media?format=xml&UUID=".$itemUUID ;
	 @$xmlString = file_get_contents($url);
	 if($xmlString){
		  @$xml = simplexml_load_string($xmlString);
		  if($xml){
		 unset($xml);
		 echo "<br/>Item: ".$itemUUID." OK";
		  }
		  else{
		 echo "<br/><h1>Item: ".$itemUUID." BAD XML</h1>";
		  }
	 }
	 else{
		  echo "<br/><h1>Item: ".$itemUUID." BAD String!! </h1>";
	 }
	 unset($xmlString);
    }//end loop

}//end function


function imageFileCheckAction(){

    $this->_helper->viewRenderer->setNoRender();
    $db = Zend_Registry::get('db');
    $this->setUTFconnection($db);

	 
    $sql = "SELECT *
    FROM resource
    WHERE  resource.ia_meta !=  'fixed'
	 AND resource.ia_meta !=  ''
	 AND resource.project_id =  'DF043419-F23B-41DA-7E4D-EE52AF22F92F'
    ";
	
    $result = $db->fetchAll($sql, 2);
    foreach($result as $row){
		  sleep(.33);
		  $itemUUID = $row["uuid"];
		  $thumbOK = $this->checkFileOK($row["ia_thumb"]);
		  $previewOK = $this->checkFileOK($row["ia_preview"]);
		  $fullOK = $this->checkFileOK($row["ia_fullfile"]);
		  if(!$thumbOK || !$previewOK || !$fullOK){
				$sad = array("t" => $thumbOK, "p" => $previewOK, "f" => $fullOK);
				$sadSave = Zend_Json::encode($sad );
				$data = array("ia_meta" => $sadSave);
				
				$data = $this->altCapsFiles($row["ia_thumb"], "ia_thumb", $data);
				$data = $this->altCapsFiles($row["ia_preview"], "ia_preview", $data);
				$data = $this->altCapsFiles($row["ia_fullfile"], "ia_fullfile", $data);
				
				$where = "uuid = '$itemUUID' ";
				$db->update("resource", $data, $where);
		  }
		  else{
				$data = array("ia_meta" => 'fixed');
				$where = "uuid = '$itemUUID' ";
				$db->update("resource", $data, $where);
		  }
		  
    }//end loop

}//end function


private function altCapsFiles($url, $urlType, $data){
	 sleep(.33);
	 
	 $urlTest = strtolower($url);
	 $urlOK = $this->checkFileOK($urlTest);
	 
	 if(!$urlOK){
		  sleep(.33);
		  if(strstr($url, ".JPG")){
				$urlTest = str_replace(".JPG", ".jpg", $url);
				$urlOK = $this->checkFileOK($urlTest);
		  }elseif(strstr($url, ".jpg")){
				$urlTest = str_replace(".jpg", ".JPG", $url);
				$urlOK = $this->checkFileOK($urlTest);
		  }
		  
		  if(!$urlOK){
				sleep(.33);
				if(strstr($url, ".JPG")){
					 $urlTest = strtolower($url);
					 $urlTest = str_replace(".jpg", ".JPG", $url);
					 $urlOK = $this->checkFileOK($urlTest);
				}
		  }
		  
	 }
	 
	 if($urlOK){
		  $data[$urlType] = $urlTest;
	 }
	 
	 return $data;
}





private function checkFileOK($url){
	 
	 stream_context_set_default(
		  array(
				'http' => array(
					 'method' => 'HEAD'
				)
		  )
	 );
	 $headers = get_headers($url);
	 if ($headers[0] == 'HTTP/1.1 200 OK') {
		  return true;
	 }
	 else{
		  return false;
	 }
}







function findsPropAction(){
    
    $this->_helper->viewRenderer->setNoRender();
    $db = Zend_Registry::get('db');
    $this->setUTFconnection($db);

    $sql = "
SELECT space.uuid as parentID, child.uuid AS childID
FROM space
JOIN space_contain ON space_contain.parent_uuid = space.uuid
JOIN space AS child ON space_contain.child_uuid = child.uuid
WHERE space.space_label LIKE 'Finds Bag%'
AND child.source_id = 'z_1_fbe02e911'
AND space.space_label = 'Finds Bag 2579'
    ";
    
    $result = $db->fetchAll($sql, 2);
    $prevParent = false;
    foreach($result as $row){
	
	$parentID = $row["parentID"];
	$childID = $row["childID"];
	
	$data = array("project_id" => "3DE4CD9C-259E-4C14-9B03-8B10454BA66E",
		      "source_id" => "z_1_fbe02e911",
		      "subject_type" => "Locations or Objects",
		      "subject_uuid" => $childID,
		      "obs_num" => 1
		      );
	
	
	if($parentID != $prevParent){
	    $sql = "SELECT observe.property_uuid
	    FROM observe
	    JOIN properties ON properties.property_uuid =  observe.property_uuid
	    WHERE observe.subject_uuid = '$parentID'
	    AND ( properties.variable_uuid = '7DD2B84F-9000-4DD7-B476-29ABD219BC43'
	    OR properties.variable_uuid = 'CB45A1E9-7511-4983-31BF-7EDD912E52A6')
	    ";
	
	    $resultB = $db->fetchAll($sql, 2);
	}
	
	foreach($resultB as $rowB){
	    
	    $data["property_uuid"] = $rowB["property_uuid"];
	    $data["hash_obs"] = md5($childID."_1_".$rowB["property_uuid"]);
	   $db->insert("observe", $data);
	    echo "<br/> done: ".$childID." prop: ".$rowB["property_uuid"];
	}
	
	$prevParent = $parentID; 
    }
}


function sherdRenameAction(){
    
    $this->_helper->viewRenderer->setNoRender();
    $db = Zend_Registry::get('db');
    $this->setUTFconnection($db);

    $projectID = "3DE4CD9C-259E-4C14-9B03-8B10454BA66E";
    
    $sql = "
    SELECT space.uuid as parentID
    FROM space
    JOIN observe ON space.uuid = observe.subject_uuid
    WHERE space.space_label LIKE 'Finds Bag%'
    AND observe.property_uuid = 'D018982F-BFE6-4CD8-968B-7575E0B9A3CE'
    ";
    
    
    $result = $db->fetchAll($sql, 2);
    foreach($result as $row){
	
	$parentID = $row["parentID"];
	
	$sql = "SELECT space.uuid AS childID, space.space_label AS childName
	FROM space_contain
	JOIN space ON space_contain.child_uuid = space.uuid
	WHERE space_contain.parent_uuid = '$parentID'
	";
	
	$resultB = $db->fetchAll($sql, 2);
	
	$sherdArray = array();
	$objectArray = array();
	
	foreach($resultB as $rowB){
	    $childID = $rowB["childID"];
	    $childName = $rowB["childName"];
	    
	    if(stristr($childName, "Sherd")  && !stristr($childName, "Sherd Group")){
		$sherdArray[$childID] = $childName;
	    }
	    elseif(stristr($childName, "ceramic")){
		$objectArray[$childID] = $childName;
	    }
	}
	
	if(count($sherdArray) > 0 && count($objectArray) > 0 ){
	    //we've got sherds that can be linked
	    foreach($sherdArray as $uuid => $sherdName){
		$sherdNameArray = explode("-",  $sherdName);
		$sherdNumber = $sherdNameArray[count($sherdNameArray)-1];
		
		foreach($objectArray as $objUUID => $obName){
		    $obNameArray = explode(" ", $obName);
		    $obNumber = $obNameArray[count($obNameArray)-1];
		    
		    if($sherdNumber == $obNumber){
				echo "<br/><br/>$sherdName ($uuid), remove IS  $obName ($objUUID), keep.";
				
				$data = array("subject_uuid" => $uuid);
				$where = array();
				$where = "subject_uuid = '$objUUID' ";
				$db->update("observe", $data, $where);
				
				$url = "http://".$_SERVER['SERVER_NAME']."/edit-transformed-data/merge-items?projectUUID=";
				$url .= $projectID."&keepID=".$uuid."&oldID=".$objUUID;
				@$outcome = file_get_contents($url);
				if($outcome){
					echo "<br/> Change we can believe in!";
				}
				else{
					echo "<br/>change failed on: ".$url;
				}
				
		    }
		    
		}
		
	    }
	    
	}
	
	
	
	
    }//end loop thorugh parents
}



function objectMergeAction(){
    
    $this->_helper->viewRenderer->setNoRender();
    $db = Zend_Registry::get('db');
    $this->setUTFconnection($db);

    $projectID = "3DE4CD9C-259E-4C14-9B03-8B10454BA66E";
    
    $sql = "
    SELECT space.uuid as parentID
    FROM space
    JOIN space_contain ON space.uuid = space_contain.parent_uuid
	JOIN space AS OchildSpace ON space_contain.child_uuid = OchildSpace.uuid
    WHERE OchildSpace.space_label LIKE 'Object%'
    ";
    
    
    $result = $db->fetchAll($sql, 2);
    foreach($result as $row){
	
	$parentID = $row["parentID"];
	
	$sql = "SELECT space.uuid AS childID, space.space_label AS childName
	FROM space_contain
	JOIN space ON space_contain.child_uuid = space.uuid
	WHERE space_contain.parent_uuid = '$parentID'
	";
	
	$resultB = $db->fetchAll($sql, 2);
	
	$itemArray = array();
	$objectArray = array();
	
	foreach($resultB as $rowB){
	    $childID = $rowB["childID"];
	    $childName = $rowB["childName"];
	    
	    if(stristr($childName, "Sherd")  && !stristr($childName, "Sherd Group")){
			$itemArray[$childID] = $childName;
	    }
	    elseif(stristr($childName, "object")){
			$objectArray[$childID] = $childName;
	    }
	}
	
	if(count($itemArray) > 0 && count($objectArray) > 0 ){
	    //we've got items that can be linked
	    foreach($itemArray as $uuid => $itemName){
			$itemNameArray = explode(" ",  $itemName);
			$itemNumber = $itemNameArray[count($itemNameArray)-1];
		
			foreach($objectArray as $objUUID => $obName){
				$obNameArray = explode(" ", $obName);
				$obNumber = $obNameArray[count($obNameArray)-1];
				
				if($itemNumber  == $obNumber){
				echo "<br/><br/>Keep $itemName ($uuid), remove $obName ($objUUID).";
				
				$url = "http://".$_SERVER['SERVER_NAME']."/edit-transformed-data/merge-items?projectUUID=";
				$url .= $projectID."&keepID=".$uuid."&oldID=".$objUUID;
				@$outcome = file_get_contents($url);
				if($outcome){
					echo "<br/> Change we can believe in!";
				}
				else{
					echo "<br/>change failed on: ".$url;
				}
				
				$where = array();
				$where = "subject_uuid = '$objUUID' ";
				$db->delete("observe", $where);
				}
				
			}
		
	    }
	    
	}
	
	
	
	
    }//end loop thorugh parents
}








function newFindsAction(){
	
    $this->_helper->viewRenderer->setNoRender();
    $db = Zend_Registry::get('db');
    $this->setUTFconnection($db);

  
    $url = "http://artiraq.org/ktbd/ktdata/json/kt-findsgroups-images.json";
    $jsonString = file_get_contents($url);
    $files = json_decode($jsonString, 1);
    unset($jsonString);

    $jj = 1;
    foreach($files as $file){
	
	$data = array();
	$i = 1;
	foreach($file as $key => $value){
	    
	    $fieldName = "field_".$i;
	    if($i == 10){
		if(strlen($value)<1){
		    $value = "";
		}
		else{
		    $value = str_replace(".jpg", "", $value);   
		}
	    }
	    
	    $data[$fieldName] = $value;
	    
	$i++;
	}
	
	
	/*
	if(!isset($data["field_12"])){
	    $data["field_12"] = "";
	}
	else{
	    if(strlen($data["field_12"])<1){
		$data["field_12"] = "";
	    }
	}
	*/
	
	if(!stristr($data["field_10"], ".jpg")){
	    $db->insert("z_finds_groups_best", $data);
	    echo "<br/>Inserted file: ".$jj;
	}
	else{
	    echo "<br/><h1> SKIPPED file: ".$jj."</h1>";
	}
    
    $jj++;
    }
    
}//end function


 function deDupePropsAction(){
	
        $this->_helper->viewRenderer->setNoRender();
        $db = Zend_Registry::get('db');
        $this->setUTFconnection($db);
        
        $sql = "SELECT uuid as itemUUID
				FROM space
				";
    
        $result = $db->fetchAll($sql, 2);
        foreach($result as $row){
            
			$itemUUID = $row["itemUUID"];
			
			$sql = "SELECT properties.property_uuid,
				properties.variable_uuid AS propVarID,
				properties.val_num, 
				'' as xml_date, 
				var_tab.var_label, 
				val_tab.val_text, 
				IF (
				val_tab.val_text IS NULL , (
					IF (
					properties.val_num =0, properties.val_num, properties.val_num)
					), 
					val_tab.val_text
					) AS allprop, 
				var_tab.var_type, 
				var_tab.variable_uuid, 
				val_tab.value_uuid
			
				FROM observe
				LEFT JOIN properties ON observe.property_uuid = properties.property_uuid
				LEFT JOIN var_tab ON properties.variable_uuid = var_tab.variable_uuid
				LEFT JOIN val_tab ON properties.value_uuid = val_tab.value_uuid
				LEFT JOIN var_notes ON var_tab.variable_uuid = var_notes.variable_uuid
				WHERE observe.subject_uuid = '$itemUUID'
				";
				
			$resultB = $db->fetchAll($sql, 2);
			
			$varVals = array();
			
			if($resultB){
				foreach($resultB as $rowB){
					
					$varID = $rowB["variable_uuid"];
					$valID = $rowB["value_uuid"];
					$text = $rowB["allprop"];
					$varLabel = $rowB["var_label"];
					
					$propID =  $rowB["property_uuid"];
					$varValKey = $varID."_".$valID;
					if(array_key_exists($varValKey, $varVals)){
						$sql = "UPDATE observe
						SET subject_uuid = 'rem_".$itemUUID."'
						WHERE subject_uuid = '$itemUUID'
						AND property_uuid = '$propID'
						LIMIT 1;";
						
						$db->query($sql, 2);
						echo "<br/> Remove Duplicate property ($varLabel : $text) (Prop: $propID Var: $varID Val: $valID)";
					}
					else{
						$varVals[$varValKey] = $propID;
					}
					
					if(!$valID || $propID == "AE0B5744-CBE5-43D4-1235-6D7EA30FFA9C"){
						$where = array();
						$where[] = "subject_uuid = '$itemUUID' ";
						$where[] = "property_uuid = '$propID' ";
						$data = array("subject_uuid" => "rem_".$itemUUID);
						$db->update("observe", $data, $where);
						echo "<br/> Deactivated property with missing value ($varLabel : $text) (Prop: $propID Var: $varID Val: $valID)";
					}
					
					
				}
			}
			
			unset($varVals);
        }//end loop through items
 }//end function



function itemNoteAction(){
	
	$this->_helper->viewRenderer->setNoRender();
	$db = Zend_Registry::get('db');
	$this->setUTFconnection($db);
	
	$projectUUID = '3DE4CD9C-259E-4C14-9B03-8B10454BA66E';
	$propUUID = "24BABD8F-C055-4758-CD37-2C82F673D743";
	$itemType = "Locations or Objects";
	
	$sql = "SELECT uuid AS uuid
			FROM space
			WHERE (space_label LIKE 'Sherd%'
			OR space_label LIKE 'Bone%'
			OR space_label LIKE 'Metal%'
			OR space_label LIKE 'Object%'
			OR space_label LIKE 'Seeds%'
			OR space_label LIKE 'Item%'
			)
			";

	$result = $db->fetchAll($sql, 2);
	foreach($result as $row){
		
		$itemUUID = $row["uuid"];
		$obsHashText = md5($projectUUID . "_" . $itemUUID . "_" . "1" . "_" . $propUUID);
                        
                $data = array("project_id"=> $projectUUID,
                          "source_id"=> 'item-note',
                          "hash_obs" => $obsHashText,
                          "subject_type" => $itemType,
                          "subject_uuid" => $itemUUID,
                          "obs_num" => 1,
                          "property_uuid" => $propUUID);


		try{
			$db->insert("observe", $data);
		}catch (Exception $e) {
			
		}

		$where = array();
		$where[] = "subject_uuid = '$itemUUID' ";
		$where[] = "property_uuid = '$itemUUID' ";

	}//end loop through items

}






function desBagAction(){
	
    $this->_helper->viewRenderer->setNoRender();
    $db = Zend_Registry::get('db');
    $this->setUTFconnection($db);

	$projectUUID = '3DE4CD9C-259E-4C14-9B03-8B10454BA66E';
	$variableUUID = "F4177018-3916-415F-117A-3BDC23F6871E";
	$itemType = "Locations or Objects";
	$tabName = "Finds Bag Des";

	$sql = "SELECT * FROM z_1_f1171314d";
	
	$result = $db->fetchAll($sql, 2);
	foreach($result as $row){
		$area = "Area ".$row["field_1"];
		$trench = "Trench ".$row["field_2"];
		$locus = "Locus ".$row["field_3"];
		$bag = "Finds Bag ".$row["field_4"];
		$path = "Kenan Tepe|xx|".$area."|xx|".$trench."|xx|".$locus."|xx|".$bag;
		$des = trim($row["field_15"]);
		
		if(strlen($des)>0){
			$sql = "SELECT uuid as uuid
			FROM space
			WHERE full_context = '$path' LIMIT 1;";
	
			$resultB = $db->fetchAll($sql, 2);
			if($resultB){
				$itemUUID = $resultB[0]["uuid"];
				echo "<br/>Found: ".$itemUUID ."  for $path ";
				$valueUUID = $this->get_make_ValID($des, $projectUUID, $tabName);
				$propUUID = $this->get_make_PropID($variableUUID, $valueUUID, $projectUUID, $tabName);
				$obsHashText = md5($projectUUID . "_" . $itemUUID . "_" . "1" . "_" . $propUUID);
							
					$data = array("project_id"=> $projectUUID,
							  "source_id"=> $tabName,
							  "hash_obs" => $obsHashText,
							  "subject_type" => $itemType,
							  "subject_uuid" => $itemUUID,
							  "obs_num" => 1,
							  "property_uuid" => $propUUID);
	
				try{
					$db->insert("observe", $data);
					echo " <em>Prop Added</em>";
				}catch (Exception $e) {
					echo " <strong>Prop Exists</strong>";
				}
			}
			else{
				echo "<h3>Missing:  for $path </h3>";
			}
		}
		else{
			echo "<br/>No description  for $path </h3>";
		}
	}//end loop
}//end function




function unitVarAction(){
	
	$this->_helper->viewRenderer->setNoRender();
	$link = array();
	 $link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=CD22C3F1-8B8D-4DB9-D6EC-A7518A2A7958&linkedLabel=cm' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=53E06011-941D-46F2-95E8-FA64C5404723&linkedLabel=cm' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=4FC506B9-30A1-482B-DC99-F776B10A4D21&linkedLabel=cm' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=8246DCBA-E180-487C-22DD-2FE84B5BF9AE&linkedLabel=alt+(m)' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=646C73DE-F5C3-4B9C-49EC-112B58B2A62B&linkedLabel=alt+(m)' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=CDAA5BB3-2F4B-4D90-19AA-E3F500BE1B35&linkedLabel=alt+(m)' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=2242017E-E250-4819-BDE5-A2DAEB4535FF&linkedLabel=alt+(m)' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=771DD980-9E3E-400C-F980-6C011C95EA26&linkedLabel=alt+(m)' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=5C2A2690-9C43-4D11-79F9-7C1A01BD7E8D&linkedLabel=cm' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=354DCD23-980F-4BEC-FBE8-B511B089E661&linkedLabel=alt+(m)' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=E08F102A-6BFA-4BA6-A237-84BB74F4078D&linkedLabel=alt+(m)' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=4D917959-EF43-4754-FA64-1A9F5F88F281&linkedLabel=alt+(m)' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=A76F7E15-BA44-4383-7EA4-60F8F716FF3F&linkedLabel=alt+(m)' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=C84B0B63-BB78-4619-4BCF-80CC9E2B72D2&linkedLabel=alt+(m)' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=E30528B4-7E28-4366-2D59-68DA53EE797D&linkedLabel=alt+(m)' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=C7C11BA3-0036-475F-59EA-02C3EFA92B53&linkedLabel=alt+(m)' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=F11D6294-2E34-4247-C802-117C73369BC1&linkedLabel=alt+(m)' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=50945E2D-6FD8-4C79-1895-B3E9F9F16289&linkedLabel=alt+(m)' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=7EB73578-C412-4D3D-0E0C-8B5018E6D265&linkedLabel=grams+per+liter' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=90BD98FF-B8E7-4D25-DBB1-BD5BBCBD67DE&linkedLabel=count+per+liter' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=D2A05738-C0FA-42FF-7B3F-8CEA3916F6F6&linkedLabel=grams+per+liter' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=02320DF1-ABFA-46B6-C5E6-A6FBA7C2E4CD&linkedLabel=count+per+liter' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=9633A540-20BE-42FF-222D-CDDD1C76D4E9&linkedLabel=grams+per+liter' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=C49C817A-BD99-4015-77E3-6A77468CF5E3&linkedLabel=count+per+liter' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=81970609-AFE8-4610-2001-F85C47D1DBBF&linkedLabel=grams+per+liter' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=2B170E52-4E18-4E31-4ACB-5D0D0904FFB6&linkedLabel=g' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=9AF57B91-CC76-46DA-5CD8-B98DC63C8AAB&linkedLabel=count+per+liter' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=F679E832-165D-4D8B-5FDA-64A96F60F557&linkedLabel=grams+per+liter' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=4F0BC68B-06B7-421E-FB97-EE80999F11FD&linkedLabel=count+per+liter' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=EBA5A41E-991C-44F0-BA8A-27A99393D0B5&linkedLabel=grams+per+liter' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=554C4A2F-9D09-4BEB-1414-73E3AFD297E6&linkedLabel=count+per+liter' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=FBD7631D-E930-46D6-9917-A4E4ABBFB748&linkedLabel=grams+per+liter' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=665C3663-6CCF-46E5-0948-21D1C19D1677&linkedLabel=item+count+per+liter' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=B6674523-9D56-4B80-4CC3-3A3E2C6AFE06&linkedLabel=grams+per+liter' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=4D16C0BF-A7CD-4052-32C8-ABFA53125939&linkedLabel=count+per+liter' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=155E05EC-3F9D-477B-D075-9092E581A8FA&linkedLabel=grams+per+liter' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=53125AD0-AECF-4FE2-FC52-16133591C6ED&linkedLabel=count+per+liter' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=1ED30030-852A-472A-270D-A744F167FA9E&linkedLabel=grams+per+liter' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=BBF96B94-5680-4D60-FE4E-CD14339DF346&linkedLabel=count+per+liter' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=1A257B19-041C-4D33-794B-5B1674A9567F&linkedLabel=grams+per+liter' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=B7829B7A-1873-4A34-F15D-5770F1441C8D&linkedLabel=count+per+liter' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=22C589D2-E487-45CC-3EB8-F325D75ED688&linkedLabel=grams+per+liter' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=84B3FBCF-29E6-455F-AB73-950FAC2CF409&linkedLabel=count+per+liter' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=4501D182-7C38-4A59-135A-38C7CA6CE113&linkedLabel=grams+per+liter' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=201B734A-3F74-4F17-26FB-3F5BAE645AA9&linkedLabel=count+per+liter' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=B895B702-6C4A-46F3-419D-9ED82DA2E083&linkedLabel=grams+per+liter' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=C492978E-110E-4217-C7D7-FB382DD16553&linkedLabel=g' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=6C53B12F-A097-407A-25FE-E09905CB9C53&linkedLabel=count+per+liter' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=3D38A7E9-5DB7-4CA9-E43D-341634D4B25D&linkedLabel=grams+per+liter' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=7FEC4A46-30F7-4A7F-CEBD-C1EE38275CC7&linkedLabel=count+per+liter' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=5162D22E-B3AC-4373-31D0-632F8B5894EE&linkedLabel=grams+per+liter' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=AADD471D-25E9-47CC-8B5E-1E17D5614F95&linkedLabel=liter' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=E19948ED-BD18-4AE6-81DF-82CBADF91856&linkedLabel=count+per+liter' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=68590B35-2869-461C-3C10-8A0026526703&linkedLabel=grams+per+liter' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=25624F49-BF95-4E80-D8A0-DC040285DC01&linkedLabel=count+per+liter' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=22CAB0D9-ED80-430A-703E-A68C9D9A6517&linkedLabel=grams+per+liter' ; 
$link[] = 'http://penelope2.oc/linked-data/var-link?projectUUID=3DE4CD9C-259E-4C14-9B03-8B10454BA66E&varUUID=686C84F0-0E5C-437B-119B-43375D6DDA47&linkedLabel=liter' ; 

	
	foreach($link as $url){
		
		$outcome = file_get_contents($url."&dir=false");
		echo "<br/>".$outcome ;
		
	}
	
	
}


function varNoteAction(){
	
	$this->_helper->viewRenderer->setNoRender();
	$varNote['C604A5BF-E7F2-4878-B718-FDE3B5B8E119'] = 'Notes ' ; 


	$projectUUID = '3DE4CD9C-259E-4C14-9B03-8B10454BA66E';
	$db = Zend_Registry::get('db');
	$this->setUTFconnection($db);
	
	
	foreach($varNote as $varKey => $note){
		
		
		$valUUID = $this->get_make_ValID($note, $projectUUID, 'variable note b');
		$data = array("project_id" => $projectUUID,
					  "source_id" => 'variable note b',
					  "variable_uuid" => $varKey,
					  "note_uuid" => $valUUID,
					  "note_text" => $note);
		
		$db->insert("var_notes", $data);
		echo "<br/><br/>  $varKey : ".$note;
	}
	
}





function kmlAction(){
	
	$this->_helper->viewRenderer->setNoRender();
	
	$kmlfile = file_get_contents("http://about.oc/kenan/AreaFTrenches.kml");

	$projectUUID = '3DE4CD9C-259E-4C14-9B03-8B10454BA66E';
	$db = Zend_Registry::get('db');
	$this->setUTFconnection($db);
	
	$areaMaxLon = -1000;
	$areaMinLon = 1000;
	$areaMaxLat = -1000;
	$areaMinLat = 1000;
	
	$xml = simplexml_load_string($kmlfile);
	$xml->registerXPathNamespace('kml', 'http://www.opengis.net/kml/2.2'); 
	foreach($xml->xpath('//kml:Placemark') as $place) {
		$place->registerXPathNamespace('kml', 'http://www.opengis.net/kml/2.2'); 
		foreach($place->xpath('kml:name') as $pName){
			$placeName = (string)$pName;
		}
		
		
		foreach($place->xpath('kml:MultiGeometry/kml:Polygon') as $geo){
			$geoOut = $geo->asXML();;
		}
		
		
		$maxLon = -1000;
		$minLon = 10000;
		
		$maxLat = -1000;
		$minLat = 1000;
		
		foreach($place->xpath('kml:MultiGeometry/kml:Polygon/kml:outerBoundaryIs/kml:LinearRing/kml:coordinates') as $pCoordinates){
			$coordString = trim($pCoordinates);
			$coords = explode(" ",$coordString);
			foreach($coords as $actCoord){
				
				$actLatLon = explode("," , $actCoord);
				$actLat = $actLatLon[1];
				$actLon =  $actLatLon[0];
				
				if($actLat < $minLat){
					$minLat = $actLat;
				}
				if($actLat > $maxLat){
					$maxLat = $actLat;
				}
				if($actLon < $minLon){
					$minLon = $actLon;
				}
				if($actLon > $maxLon){
					$maxLon = $actLon;
				}
				
				
				if($actLat < $areaMinLat){
					$areaMinLat = $actLat;
				}
				if($actLat > $areaMaxLat){
					$areaMaxLat = $actLat;
				}
				if($actLon < $areaMinLon){
					$areaMinLon = $actLon;
				}
				if($actLon > $areaMaxLon){
					$areaMaxLon = $actLon;
				}
				
			}//end loop through coordinates
			
		}
	
	
		$meanLat = ($minLat + $maxLat) / 2;
		$meanLon = ($minLon + $maxLon) / 2;
	
		$nameQuery = str_replace("F", "Kenan Tepe|xx|Area F|xx|Trench ", $placeName);
		$nameQuery = str_replace("2005", "", $nameQuery);
		$nameQuery = trim(str_replace("- Exc", "", $nameQuery));
		
		if($placeName != 'F6 2001- Exc'){
			if(stristr($placeName, "Exc") ){
				echo "<br/><br/><br/>".$placeName." ($nameQuery) Lat: $meanLat Lon: $meanLon <a href='http://maps.google.com?q=$meanLat,$meanLon'>Link</a> UUID: " ;
				
				$sql = "SELECT uuid FROM space WHERE full_context = '$nameQuery' LIMIT 1; ";
				
				$result = $db->fetchAll($sql, 2);
				if($result){
					$uuid = $result[0]["uuid"];
					echo $uuid;
				}
				
				echo "<br/>".htmlentities($geoOut);
				
				$data = array("project_id" => $projectUUID,
							  "source_id" => "AreaFTrenches.kml",
							  "uuid" => $uuid,
							  "latitude" => $meanLat,
							  "longitude" => $meanLon,
							  "kml_data" => $geoOut
							  );
				try{
					$db->insert("geo_space", $data);
				}
				catch(Exception $e){
					
				}
			}
				
		}
		
		unset($data);
		
		$meanAreaLat = ($areaMaxLat + $areaMinLat) / 2;
		$meanAreaLon = ($areaMaxLon + $areaMinLon) / 2;
		
		$data = array("project_id" => $projectUUID,
							  "source_id" => "AreaFTrenches.kml",
							  "uuid" => '85B36DE2-96FC-40AF-B3FB-D35CCDE2189E',
							  "latitude" => $meanAreaLat,
							  "longitude" => $meanAreaLon
							  );
		
		$where = array();
		$where = "uuid = '85B36DE2-96FC-40AF-B3FB-D35CCDE2189E' ";
		$db->update("geo_space", $data, $where);
	
	
	}//end loop through placemarks
	
}


function uuidAction(){
	
	$this->_helper->viewRenderer->setNoRender();
	echo GenericFunctions::generateUUID();

}


function areaSupAction(){
	
	$this->_helper->viewRenderer->setNoRender();
	$db = Zend_Registry::get('db');
	$this->setUTFconnection($db);
	
	$projectUUID = '3DE4CD9C-259E-4C14-9B03-8B10454BA66E';
	/*
	$personLinks = array(2000 => "9C80BF6B-7BDE-4295-E7D9-695845A44846",
						 2001 => "9C80BF6B-7BDE-4295-E7D9-695845A44846",
						 2002 => "9C80BF6B-7BDE-4295-E7D9-695845A44846",
						 2004 => "9C80BF6B-7BDE-4295-E7D9-695845A44846",
						 2005 => "98B37A9C-7277-4ECE-B34D-146CFC49D976"
						 );
	
	$personLinks = array(
						 2002 => "B14E7E07-078B-432B-CAA0-607A1FF9532C",
						 2004 => "B14E7E07-078B-432B-CAA0-607A1FF9532C"
						 );
	*/
	
	$personLinks = array(
						 2000 => "4499C94B-8EB5-4E30-07CC-DD3A98561FB6",
						 2001 => "4499C94B-8EB5-4E30-07CC-DD3A98561FB6"
						 );
	
	$sql = "SELECT uuid
	FROM space
	WHERE (
	full_context LIKE 'Kenan Tepe|xx|Area A%'
	OR
	full_context LIKE 'Kenan Tepe|xx|Area B%'
	OR
	full_context LIKE 'Kenan Tepe|xx|Area C%'
	OR
	full_context LIKE 'Kenan Tepe|xx|Area D%'
	OR
	full_context LIKE 'Kenan Tepe|xx|Area E%'
	OR
	full_context LIKE 'Kenan Tepe|xx|Area G%'
	)
	AND (space.class_uuid = 'AD005BC5-0AAA-42F8-B254-E07919FCC82B'
		OR
		space.class_uuid = 'B1C59769-CEE4-427A-E84F-0EFB2CEDBA06'
		OR
		space.class_uuid = 'BDC99674-2A97-4031-5DE8-F95DA727A83D'
		OR
		space.class_uuid = 'F54F3EA6-B894-4F5C-6803-6C12A4DFD549'
		)	
	";
	
	$result = $db->fetchAll($sql, 2);
	foreach($result as $row){
		$itemUUID = $row["uuid"];
		
		//check date
		$sql = "SELECT val_tab.val_text
		FROM val_tab
		JOIN properties ON properties.value_uuid = val_tab.value_uuid
		JOIN observe ON properties.property_uuid = observe.property_uuid
		WHERE (properties.variable_uuid = '667B68A9-7283-4DA5-86CB-2742586DB672'
		OR
		properties.variable_uuid = 'F63A8CB7-1F50-4F45-4D8D-E23462DA4755'
		OR
		properties.variable_uuid = '9ED499E4-5B88-4BA4-7BE2-647A027C12D0'
		)
		AND observe.subject_uuid = '$itemUUID'
		LIMIT 1;
		";
		
		$dateString = false;
		$resultB = $db->fetchAll($sql, 2);
		if($resultB){
			$dateString = $resultB[0]["val_text"];
		}
		else{
			
			$json = file_get_contents("http://".$_SERVER['SERVER_NAME']."/xml/space?id=".$itemUUID);
			$itemObj = json_decode($json, true);
			if(isset($itemObj["linksObj"]["mediaLinks"]["1"])){
				foreach($itemObj["linksObj"]["mediaLinks"]["1"] as $key => $link){
					if(isset($link["descriptor"])){
						if (($timestamp = strtotime($link["descriptor"])) === false) {
							$calendardTest = false;
							}
						else{
							$calendardTest = true;
						}
						
						if($calendardTest){
							$dateString  = $link["descriptor"];
							break;
						}
					}
				}
			}
			elseif(isset($itemObj["linksObj"]["documentLinks"]["1"])){
				
				foreach($itemObj["linksObj"]["documentLinks"]["1"] as $key => $link){
					$label = $link["linkedName"];
					$lArray = explode("-", $label);
					echo ", ".$label;
					if(count($lArray)>4){
						$dateTest = $lArray[2]."-".$lArray[3]."-".$lArray[4];
						if (($timestamp = strtotime($dateTest)) === false) {
							$calendardTest = false;
							}
						else{
							$calendardTest = true;
						}
						
						if($calendardTest){
							$dateString  = $dateTest;
							break;
						}
					}
				}

			}
			
		}
		
		echo "<br/>$itemUUID  ($dateString)";
		
		if($dateString != false){
			
			echo " Check: ";
			$checkAr = explode("-", $dateString);
			$checkYear = $checkAr[0];
			foreach($personLinks as $yearKey => $personUUID){
				echo ", ".$yearKey;
				if($checkYear == $yearKey){
					$this->addLinkingRel($itemUUID, "Locations or Objects", $personUUID, "Person", "Area supervisor", $projectUUID, "area bp sup link");
					echo "- added supervisor link based on year ".$yearKey;
					break;
				}
			}
		}
		
		
	}//end loop through items
	
}//end area sup function




function areaPubPrepAction(){
	
	$this->_helper->viewRenderer->setNoRender();
	$db = Zend_Registry::get('db');
	$this->setUTFconnection($db);
	
	$projectUUID = '3DE4CD9C-259E-4C14-9B03-8B10454BA66E';

	$sql = "SELECT uuid FROM space
	WHERE full_context LIKE 'Kenan Tepe|xx|Area F%'
	OR space_label = 'Kenan Tepe'
	";

	$result = $db->fetchAll($sql, 2);
	
	echo "<br/>Adding space: ".count($result);
	
	foreach($result as $row){
		$itemUUID = $row["uuid"];
		$data = array("uuid" => $itemUUID,
					  "projectUUID" => $projectUUID,
					  "itemType" => "Locations or Objects"
					  );
		
		try{
			$db->insert("publish_to_do", $data);
		}
		catch(Exception $e){
			
		}
		
	}
	
	unset($result);
	
	//now add media
	$sql = "SELECT DISTINCT resource.uuid AS itemUUID
	FROM resource
	JOIN links ON links.targ_uuid = resource.uuid
	JOIN publish_to_do ON links.origin_uuid = publish_to_do.uuid
	";
	
	$result = $db->fetchAll($sql, 2);
	
	echo "<br/>Adding media: ".count($result);
	foreach($result as $row){
		$itemUUID = $row["itemUUID"];
		$data = array("uuid" => $itemUUID,
					  "projectUUID" => $projectUUID,
					  "itemType" => "Media (various)",
					  );
		
		try{
			$db->insert("publish_to_do", $data);
		}
		catch(Exception $e){
			
		}	
	}//end loop through media
	

	
	//now add documents
	$sql = "SELECT DISTINCT diary.uuid AS itemUUID
	FROM diary
	JOIN links ON links.targ_uuid = diary.uuid
	JOIN publish_to_do ON links.origin_uuid = publish_to_do.uuid
	";
	
	$result = $db->fetchAll($sql, 2);
	
	echo "<br/>Adding documents: ".count($result);
	foreach($result as $row){
		$itemUUID = $row["itemUUID"];
		$data = array("uuid" => $itemUUID,
					  "projectUUID" => $projectUUID,
					  "itemType" => "Diary / Narrative"
					  );
		
		try{
			$db->insert("publish_to_do", $data);
		}
		catch(Exception $e){
			
		}	
	}//end loop through media
	
	
	//now add people
	$sql = "SELECT DISTINCT users.uuid AS itemUUID
	FROM users
	JOIN links ON links.targ_uuid = users.uuid
	JOIN publish_to_do ON links.origin_uuid = publish_to_do.uuid
	";
	
	$result = $db->fetchAll($sql, 2);
	
	echo "<br/>Adding people: ".count($result);
	foreach($result as $row){
		$itemUUID = $row["itemUUID"];
		$data = array("uuid" => $itemUUID,
					  "projectUUID" => $projectUUID,
					  "itemType" => "Person"
					  );
		
		try{
			$db->insert("publish_to_do", $data);
		}
		catch(Exception $e){
			
		}	
	}//end loop through media
	
	
	
	
	
	//now add properties
	$sql = "SELECT DISTINCT observe.property_uuid AS itemUUID
	FROM observe
	JOIN publish_to_do ON observe.subject_uuid = publish_to_do.uuid
	";

	$result = $db->fetchAll($sql, 2);
	
	echo "<br/>Adding properties: ".count($result);
	foreach($result as $row){
		$itemUUID = $row["itemUUID"];
		$data = array("uuid" => $itemUUID,
					  "projectUUID" => $projectUUID,
					  "itemType" => "Property"
					  );
		
		try{
			$db->insert("publish_to_do", $data);
		}
		catch(Exception $e){
			
		}	
	}//end loop through properties
	
}





function logDateAddAction(){
	
	$this->_helper->viewRenderer->setNoRender();
	$db = Zend_Registry::get('db');
	$this->setUTFconnection($db);
	
	$projectUUID = '3DE4CD9C-259E-4C14-9B03-8B10454BA66E';
	
	
	
	
}//end function










}//end class