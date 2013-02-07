<?php

// increase the memory limit
ini_set("memory_limit", "2048M");
// set maximum execution time to no limit
ini_set("max_execution_time", "0");

class EditDatasetController extends Zend_Controller_Action
{
    
    //public $host = "http://penelope.opencontext.org";
    public $host = "http://penelope.oc";
    //public $host = "http://".$_SERVER["SERVER_NAME"];
	 
	 public $counter = 0;
    function init()
    {  
        $this->view->baseUrl = $this->_request->getBaseUrl();
        require_once 'App/Util/GenericFunctions.php';
        Zend_Loader::loadClass('Zend_Debug');
        Zend_Loader::loadClass('ContextItem');
        Zend_Loader::loadClass('Table_Property');
        Zend_Loader::loadClass('Table_Value');
        Zend_Loader::loadClass('Table_Variable');
        Zend_Loader::loadClass('Table_Observe');
        Zend_Loader::loadClass('Table_Diary');
        Zend_Loader::loadClass('Table_Resource');
        Zend_Loader::loadClass('Table_LinkRelationship');
        Zend_Loader::loadClass('Table_User');
    }
    
    
    function boneFixAction(){
        $this->_helper->viewRenderer->setNoRender();
        $db = Zend_Registry::get('db');
        
        /*
        $bad_data_uri = "http://about.oc/oc_xmlgen/xml_docs/badData.xml";
        $bad_data_string = file_get_contents($bad_data_uri); //good data is SAVED in the published docs table.
        $xml = simplexml_load_string($bad_data_string);
        unset($good_data_string);
        
        foreach($xml->xpath("//uuid") as $item_result) {
	    $uuid = $item_result."";
            $data = array("uuid" => $uuid);
            try{
                $db->insert('badBones', $data);
            } catch (Exception $e) {
                    echo $e->getMessage(), "\n";
                }
        }
        */
        
        /*
        $good_data_uri = "http://about.oc/oc_xmlgen/xml_docs/goodData.xml";
        $good_data_string = file_get_contents($good_data_uri); //good data is SAVED in the published docs table.
        $xml = simplexml_load_string( $good_data_string);
        unset($good_data_string);
        
        foreach($xml->xpath("//uuid") as $item_result) {
	    $uuid = $item_result."";
            $data = array("uuid" => $uuid);
            try{
                $db->insert('goodBones', $data);
            } catch (Exception $e) {
                    //echo $e->getMessage(), "\n";
                }
        }
        */
        
        
        $sql = "DELETE published_docs
        FROM published_docs
        WHERE published_docs.item_type = 'space'
        AND published_docs.item_uuid NOT IN (
        SELECT goodbones.uuid
        FROM goodbones)
        ";
        
        $sql = "SELECT *
        FROM badbones";
        
        $results  = $db->fetchAll($sql);
        foreach($results as $row){
            $pubDestA = "http://opencontext/publish/itempublish";
            $pubDestB = "http://opencontext.org/publish/itempublish";
            
            $hashA = md5($pubDestA.$row["uuid"]);
            $hashB = md5($pubDestB.$row["uuid"]);
            
            $data = array(
                    "hash_key" => $hashA,
                    "pubdest" => $pubDestA,
                    "project_id" => "B7047162-6906-4A5E-13C0-B5B86A108510",
                    "item_uuid" => $row["uuid"],
                    "item_type" => "space",
                    "status" => "skip publication"
                    );
            
            $db->insert('published_docs', $data);
            
            $data["hash_key"] = $hashB;
            $data["pubdest"] = $pubDestB;
            
            $db->insert('published_docs', $data);
            
        }
        
        
    }
    
    
    
    
    
    //this function makes values of a given variable the basis of links
    function variableLinkAction(){
        $this->_helper->viewRenderer->setNoRender();
        $db = Zend_Registry::get('db');
        
        $projectUUID = $_REQUEST['projectUUID'];
        
        if(isset($_REQUEST["originType"])){
            $originType = $_REQUEST["originType"];
        }
        else{
            $originType = "Locations or Objects";
        }
        
        if(isset($_REQUEST["targType"])){
            $targType = $_REQUEST["targType"];
        }
        else{
            $targType = "Locations or Objects";
        }
        
        if(isset($_REQUEST["linkRel"])){
            $linkRel = $_REQUEST["linkRel"];
        }
        else{
            $linkRel = false;
        }
       
        if(isset($_REQUEST["gPrefix"])){
            $gPrefix = $_REQUEST["gPrefix"];
        }
        else{
            $gPrefix = false;
        }
       
        if(isset($_REQUEST["bPrefix"])){
            $bPrefix = $_REQUEST["bPrefix"];
        }
        else{
            $bPrefix = false;
        }
       
	$where = "source_id = 'link-from-prop' ";
	//$db->delete("links", $where);
	unset($where);
       
        
        $variableUUID = $_REQUEST["varUUID"];
        
        $sql = "SELECT DISTINCT properties.property_uuid, val_tab.val_text, var_tab.var_label
        FROM properties
        JOIN val_tab ON val_tab.value_uuid = properties.value_uuid
        JOIN var_tab ON properties.variable_uuid = var_tab.variable_uuid
        WHERE properties.variable_uuid = '$variableUUID'
        ";
        
        $result = $db->fetchAll($sql, 2);
        
        if(!$linkRel){
            $linkRel = $result[0]['var_label'];
        }
        
        $linkCount = 0;
        foreach($result as $row){
            $propUUID = $row['property_uuid'];
            $rawText = $row['val_text'];
            $allText = $this->parse_multi_item($rawText);
            
            echo "<br/><strong>".$rawText."</strong>";
            if($allText != false){
                
                //now get the origin UUID
                $sql = "SELECT DISTINCT subject_uuid, obs_num, source_id
                FROM observe
                WHERE project_id = '$projectUUID'
                AND property_uuid = '$propUUID'
                ";
                
                $obsResult = $db->fetchAll($sql, 2);
                foreach($obsResult as $obsRow){
		    $originUUID = $obsRow['subject_uuid'];
		    $ObsNumber = $obsRow['obs_num'];
		    $source_id = $obsRow['source_id'];
		    $source_id = "link-from-prop";
                
		    $sql = "SELECT parent_uuid FROM space_contain WHERE child_uuid = '$originUUID' LIMIT 1";
		
		    $parResult = $db->fetchAll($sql, 2);
		    if($parResult){
			
			$parentUUID = $parResult[0]["parent_uuid"];
		
			foreach($allText as $actText){
			    $itemFind = $this->find_uuid($actText, $gPrefix, $bPrefix, $targType, $projectUUID, $db, $parentUUID);
			    echo "<br/>".$itemFind["item"]." :UUID ".$itemFind["uuid"];
			    if($itemFind["uuid"] != false){
				$targUUID = $itemFind["uuid"];
				$newLinkID = $this->addLinkingRel($originUUID, $originType, $targUUID, $targType, $linkRel, $projectUUID, $source_id, $ObsNumber);
				$linkCount++;
			    }
		    
			}//end loop through array of multiple labels, comma seperated.
		    }
		}//end loop through "origin items"
		
                
            }
            
        }
        
    }//end function
    
    
    
    
    private function find_uuid($rawLabel, $gPrefix, $bPrefix, $itemType, $projectUUID, $db, $parentUUID = false){
        
        if($bPrefix != false){
            if(stristr($rawLabel, $bPrefix)){
                $rawLabel = str_ireplace($bPrefix, $gPrefix." ", $rawLabel);
            }
        }
        
        if($gPrefix != false){
            if(!stristr($rawLabel, $gPrefix)){
                $itemLabel = $gPrefix." ".$rawLabel;
            }
            else{
                $itemLabel = $rawLabel;
            }
        }
        else{
            $itemLabel = $rawLabel;
        }
        
        if($itemType == "Locations or Objects" && !$parentUUID){
            $sql = "SELECT uuid as itemUUID
            FROM space
            WHERE project_id = '$projectUUID'
            AND space_label = '$itemLabel'
            LIMIT 1
            ";
        }
	elseif($itemType == "Locations or Objects" && $parentUUID != false){
            
	    //use this query to insure that the found spatial item has a certain parent context
	    $sql = "SELECT space.uuid as itemUUID
            FROM space
	    JOIN space_contain ON space.uuid = space_contain.child_uuid
            WHERE space.project_id = '$projectUUID'
            AND space.space_label = '$itemLabel'
	    AND space_contain.parent_uuid = '$parentUUID'
            LIMIT 1
            ";
        }
        elseif($itemType == "Resource"){
            $sql = "SELECT uuid as itemUUID
            FROM resource
            WHERE project_id = '$projectUUID'
            AND res_label = '$itemLabel'
            LIMIT 1
            ";
        }
        else{
           $sql = "SELECT uuid as itemUUID
            FROM persons
            WHERE project_id = '$projectUUID'
            AND (combined_name = '$itemLabel'
            OR
            initials = '$itemLabel')
            LIMIT 1
            "; 
        }
        
        $result = $db->fetchAll($sql, 2);
        if($result){
            $output = array("item" => $itemLabel, "uuid" => $result[0]['itemUUID']);
        }
        else{
            $output = array("item" => $itemLabel, "uuid" => false);
        }
        
        return $output;
    }
    
    
    
    
    //this function breaks apart a raw item from non-containment fields and
   //returns an array of the item 
   private function parse_multi_item($rawItem, $preArray = false){
    
    
    $preArray = array("/", "-");

    if($rawItem != null){
        $itemArray = array();
        $itemArray[] = $rawItem;
        
        $delimiters = array(",",
                            "&",
                            ";",
                            " ",
                            "and");
        
        $deleteText = array("(",
                            ")",
                            "?",
                            "`");
        
        
        foreach($delimiters as $delim){
            $newItems = array();
            foreach($itemArray as $actItem){
                if(stristr($actItem, $delim)){
                    //echo "<br/>Delim is:".$delim." found in: ".$actItem ;
                    $exploded = explode($delim, $actItem);
                    foreach($exploded as $actExp){
                        $actItem = trim($actExp);
                        if(strlen($actItem)>0){
                            $newItems[] = $actItem;
                        }
                    }
                }
                else{
                   $actItem = trim($actItem);
                    if(strlen($actItem)>0){
                        $newItems[] = $actItem;
                    }
                }
            }
            unset($itemArray);
            $itemArray = $newItems;
            unset($newItems);
        }//end loop through delimiters
        
        $output = $itemArray;
    }
    else{
        $output = false;
    }
    
    
    if($output !=false){
        
        $newItems = array();
        foreach($output as $item){
            foreach($deleteText as $delChar){
                $item = str_replace($delChar, "", $item);
            }
            $newItems[] = $item;
        }
        unset($output);
        $output = $newItems;
        unset($newItems);
    }//end case to delete bad characters
    
    
    //go through and split items that have a prefix dependent delimiter
    if($output != false && $preArray != false){
        foreach($preArray as $preDelim){
            $output = $this->prefix_parse($output, $preDelim);
        }//end loop
    }
    
    return $output;
   }//end function
    
   
   
   //this finds delimiters that get assume some text is used as a prefix
   private function prefix_parse($itemArray, $preDelim){
    
    $final_new_items = array();
    foreach($itemArray as $item){    
        
        if(stristr($item, $preDelim)){
            $w_newItems = explode($preDelim, $item);
            $w_newItemsB = array();
            foreach($w_newItems as $newItem){
                $w_newItemsB[] = trim($newItem);
            }//end loop
            
            unset($w_newItems);
            $w_newItems = $w_newItemsB;
            unset($w_newItemsB);
                    
            $lengthFirst = strlen($w_newItems[0]);
                    
            $newItems = array();
            foreach($w_newItems as $newItem){
                $lenItem = strlen($newItem);
                if($lenItem < $lengthFirst){
                    $difLen = $lengthFirst - $lenItem;
                    $prefix = substr($w_newItems[0], 0, $difLen);
                }
                else{
                    $prefix = "";
                }
                    $newItems[] = $prefix.$newItem;
            }//end loop
            unset($w_newItems);
                    
                    
            foreach($newItems as $newItem){
                if(!in_array($newItem, $final_new_items)){
                    $final_new_items[] = $newItem;
                }
            }
            unset($newItems);
                    
        }    
        else{
            $final_new_items[] = $item;
        }
                
    }//end loop though items
    
    return  $final_new_items;
   }//end function
   
    
    
    
    
    function chronoTagPropAction(){
        $this->_helper->viewRenderer->setNoRender();
        $propertyUUID = $_REQUEST['propertyUUID'];
        $tStart = $_REQUEST['tStart'];
        $tEnd = $_REQUEST['tEnd'];
        
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
        
        $db = Zend_Registry::get('db');
     
        //first create undo and redo for removing the old item
        $select = $db->select()
            ->distinct()
            ->from(
                array('cc' => 'observe'),
                    array('cc.project_id',
                          'cc.subject_type',
                          'cc.subject_uuid')
            )
        //    ->where('cc.property_uuid LIKE "'.$propertyUUID.'"' );
            ->where('cc.property_uuid =?', $propertyUUID);
        
        
        $sql = $select->__toString();
        //echo "$sql\n";
        $stmt = $db->query($select);
        $results = $stmt->fetchAll();
        //$dataRows = $db->query($select)->fetchAll();
        $cCnt = 0;
        
        
        if(count($results)<1){
            
            $sql = 'SELECT observe.subject_uuid, observe.project_id, observe.subject_type
            FROM observe
            WHERE observe.property_uuid = "'.$propertyUUID.'"
            GROUP BY observe.subject_uuid
            ';
            
            //echo "$sql\n";
            $results  = $db->fetchAll($sql);
            
        }
       
        
        foreach($results as $actRow){
            $projectUUID = $actRow["project_id"];
            $itemUUID = $actRow["subject_uuid"];
            $itemType = $actRow["subject_type"];
            
            if($itemType == 'spatial' || $itemType == 'Locations or Objects' ){
            
                $where = array();
                $where[] = "project_id  = '".$projectUUID."' ";
                $where[] = "uuid  = '".$itemUUID."' ";
                $db->delete('initial_chrono_tag', $where);
                
                $data = array('project_id'=> $projectUUID,
                'uuid'=> $itemUUID,
                'creator_uuid'=> 'oc',
                'label'=> $dateLabel,
                'start_time'=> $tStart,
                'end_time'=> $tEnd,
                'note_id'=> 'Default set',
                'public'=> 1
                );
                
                $db->insert('initial_chrono_tag', $data);
                
                $cCnt++;        
            } 
        }
        
        $output = array("count"=>$cCnt, "label"=>$dateLabel);
        
        echo  Zend_JSON::encode($output);
    }
    
    
    
    //this makes a pretty looking date
    private function makeNiceDate($dec_time){
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
    
    
    //this tags all members of a class with a begin and end date
    function itemChronoTagAction(){
        $this->_helper->viewRenderer->setNoRender();
        $projectUUID = $_REQUEST['projectUUID'];
        
        $tStart = $_REQUEST['tStart'];
        $tEnd = $_REQUEST['tEnd'];
        
        if(isset($_REQUEST['itemUUID'])){
            $itemUUID =  $_REQUEST['itemUUID'];
        }
        else{
             $itemUUID = false;
        }
        
        
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
    
        $db = Zend_Registry::get('db');
        
        if($itemUUID != false){
	    $sql = 'SELECT space.uuid
		FROM space
		WHERE space.project_id = "'.$projectUUID.'"
		AND space.uuid = "'.$itemUUID.'"
		LIMIT 1; ';
		
		
	    //echo "$sql\n";
	    $results  = $db->fetchAll($sql);
	    $cCnt = 0;
	    foreach($results as $actRow){
		
		$itemUUID = $actRow["uuid"];
		
		$where = array();
		$where[] = "project_id  = '".$projectUUID."' ";
		$where[] = "uuid  = '".$itemUUID."' ";
		$db->delete('initial_chrono_tag', $where);
		
		$data = array('project_id'=> $projectUUID,
		'uuid'=> $itemUUID,
		'creator_uuid'=> 'oc',
		'label'=> $dateLabel,
		'start_time'=> $tStart,
		'end_time'=> $tEnd,
		'note_id'=> 'Default set',
		'public'=> 1
		);
		
		$db->insert('initial_chrono_tag', $data);
		
		$cCnt++;        
	    }
	    
	    $output = array("count"=>$cCnt, "label"=>$dateLabel);
	}
	else{
	    $output = false;
	}
	
        echo  Zend_JSON::encode($output);
        
        
    }//end function
    
    
    //this tags all members of a class with a begin and end date
    function classContextTagAction(){
        $this->_helper->viewRenderer->setNoRender();
        $projectUUID = $_REQUEST['projectUUID'];
        
        $tStart = $_REQUEST['tStart'];
        $tEnd = $_REQUEST['tEnd'];
        
        if(isset($_REQUEST['classUUID'])){
            $classWhere = " AND space.class_uuid = '".$_REQUEST['classUUID']."' ";
        }
        else{
            $classWhere = " ";
        }

        if(isset($_REQUEST['context'])){
            $contextWhere = " AND space.full_context LIKE '".$_REQUEST['context']."%' ";
        }
        else{
            $contextWhere = " ";
        }
        
        
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
    
        $db = Zend_Registry::get('db');
        
        
        $sql = 'SELECT space.uuid
            FROM space
            WHERE space.project_id = "'.$projectUUID.'"
            '.$classWhere.$contextWhere;
            
        //echo "$sql\n";
        $results  = $db->fetchAll($sql);
        $cCnt = 0;
        foreach($results as $actRow){
            
            $itemUUID = $actRow["uuid"];
            
            $where = array();
            $where[] = "project_id  = '".$projectUUID."' ";
            $where[] = "uuid  = '".$itemUUID."' ";
            $db->delete('initial_chrono_tag', $where);
            
            $data = array('project_id'=> $projectUUID,
            'uuid'=> $itemUUID,
            'creator_uuid'=> 'oc',
            'label'=> $dateLabel,
            'start_time'=> $tStart,
            'end_time'=> $tEnd,
            'note_id'=> 'Default set',
            'public'=> 1
            );
            
            $db->insert('initial_chrono_tag', $data);
            
            $cCnt++;        
        }
        
        $output = array("count"=>$cCnt, "label"=>$dateLabel);
        
        echo  Zend_JSON::encode($output);
        
        
    }//end function
    
    
    //this tags all members of a class with a begin and end date
    function classContextGeoAction(){
        $this->_helper->viewRenderer->setNoRender();
        $projectUUID = $_REQUEST['projectUUID'];
        
        $lat = $_REQUEST['lat'];
        $lon = $_REQUEST['lon'];
        
        if(isset($_REQUEST['classUUID'])){
            $classWhere = " AND space.class_uuid = '".$_REQUEST['classUUID']."' ";
        }
        else{
            $classWhere = " ";
        }

        if(isset($_REQUEST['context'])){
            $contextWhere = " AND space.full_context LIKE '".$_REQUEST['context']."%' ";
        }
        else{
            $contextWhere = " ";
        }
        
        
        if(!is_numeric($lat)){
            $lat = false;
        }
        if(!is_numeric($lon)){
            $lon = false;
        }
        
        
        $db = Zend_Registry::get('db');
        
        
        $sql = 'SELECT space.uuid
            FROM space
            WHERE space.project_id = "'.$projectUUID.'"
            '.$classWhere.$contextWhere;
            
        //echo "$sql\n";
        $results  = $db->fetchAll($sql);
        $cCnt = 0;
        
        if(!$lat||!$lon){
            $results = null;
        }
        
        
        foreach($results as $actRow){
            
            $itemUUID = $actRow["uuid"];
            
            $where = array();
            $where[] = "project_id  = '".$projectUUID."' ";
            $where[] = "uuid  = '".$itemUUID."' ";
            $db->delete('geo_space', $where);
            
            $data = array('project_id'=> $projectUUID,
            'source_id' => 'manual',
            'uuid'=> $itemUUID,
            'latitude' => $lat,
            'longitude' => $lon
            );
            
            $db->insert('geo_space', $data);
            
            $cCnt++;        
        }
        
        $output = array("count"=>$cCnt, "label"=>("Lat:".$lat." Lon:".$lon));
        
        echo  Zend_JSON::encode($output);
        
        
    }//end function
    
    
    
    
    
    
    
    
    
    
    
    function propChronoTagAction(){
        $this->_helper->viewRenderer->setNoRender();
        $projectUUID = $_REQUEST['projectUUID'];
        $propUUID = $_REQUEST['propUUID'];
        
        $tStart = $_REQUEST['tStart'];
        $tEnd = $_REQUEST['tEnd'];
        
        
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
    
        $db = Zend_Registry::get('db');
        
        
        $sql = 'SELECT DISTINCT space.uuid
            FROM space
            JOIN observe ON space.uuid = observe.subject_uuid
            WHERE space.project_id = "'.$projectUUID.'"
            AND observe.property_uuid = "'.$propUUID.'"';
            
        //echo "$sql\n";
        $results  = $db->fetchAll($sql);
        $cCnt = 0;
        foreach($results as $actRow){
            
            $itemUUID = $actRow["uuid"];
            
            $where = array();
            $where[] = "project_id  = '".$projectUUID."' ";
            $where[] = "uuid  = '".$itemUUID."' ";
            $db->delete('initial_chrono_tag', $where);
            
            $data = array('project_id'=> $projectUUID,
            'uuid'=> $itemUUID,
            'creator_uuid'=> 'oc',
            'label'=> $dateLabel,
            'start_time'=> $tStart,
            'end_time'=> $tEnd,
            'note_id'=> 'Default set',
            'public'=> 1
            );
            
            $db->insert('initial_chrono_tag', $data);
            
            $cCnt++;        
        }
        
        $output = array("count"=>$cCnt, "label"=>$dateLabel);
        
        echo  Zend_JSON::encode($output);
    }
    
    
    
    function varValsChronoTagAction(){
        $this->_helper->viewRenderer->setNoRender();
        $projectUUID = $_REQUEST['projectUUID'];
        $varUUID = $_REQUEST['varUUID'];
        
        $db = Zend_Registry::get('db');
        $sql = "SELECT DISTINCT properties.property_uuid,
        val_tab.val_text
        FROM properties
        JOIN val_tab ON properties.value_uuid = val_tab.value_uuid
        WHERE properties.variable_uuid = '".$varUUID."'
        ";
        
        $resultsA  = $db->fetchAll($sql);
        $cCnt = 0;
        $dateGoodArray = array();
        $dateBadArray = array();
        foreach($resultsA as $aRow){
            $goodDate = false;
            $propUUID =   $aRow["property_uuid"];
            $valText = $aRow["val_text"];
            if(strstr($valText, "-")){
                $dateArray = explode("-", $valText);
                $tStart = $dateArray[0];
                $tEnd = $dateArray[1];
            }
            else{
                $tStart = $valText;
                $tEnd = $valText;
            }
            
            if(is_numeric($tStart) && is_numeric($tEnd)){
                $goodDate = true;
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
                
                $sql = 'SELECT DISTINCT space.uuid
                    FROM space
                    JOIN observe ON space.uuid = observe.subject_uuid
                    WHERE space.project_id = "'.$projectUUID.'"
                    AND observe.property_uuid = "'.$propUUID.'"';
                    
                //echo "$sql\n";
                $results  = $db->fetchAll($sql);
                foreach($results as $actRow){
                    
                    $itemUUID = $actRow["uuid"];
                    
                    $where = array();
                    $where[] = "project_id  = '".$projectUUID."' ";
                    $where[] = "uuid  = '".$itemUUID."' ";
                    $db->delete('initial_chrono_tag', $where);
                    
                    $data = array('project_id'=> $projectUUID,
                    'uuid'=> $itemUUID,
                    'creator_uuid'=> 'oc',
                    'label'=> $dateLabel,
                    'start_time'=> $tStart,
                    'end_time'=> $tEnd,
                    'note_id'=> 'Default set',
                    'public'=> 1
                    );
                    
                    $db->insert('initial_chrono_tag', $data);
                    $cCnt++;        
                }
                $dateGoodArray[] = array("propUUID"=>$propUUID, "text"=> $valText);
            }
            else{
                $dateBadArray[] = array("propUUID"=>$propUUID, "text"=> $valText);
            }
        }
    
        
        $output = array("count"=>$cCnt, "goodDates"=>$dateGoodArray, "badDates" => $dateBadArray);
        
        echo  Zend_JSON::encode($output);
    }
    
    
    
    //this tags all members of a class with a begin and end date
    function varsChronoTagAction(){
        $this->_helper->viewRenderer->setNoRender();
        $projectUUID = $_REQUEST['projectUUID'];
        
        $tStartVarID = $_REQUEST['tStartVarID'];
        $tEndVarID = $_REQUEST['tEndVarID'];
        
        if(isset($_REQUEST['posBCE'])){
            $posBCE = true;
        }
        else{
            $posBCE = false;
        }

        $db = Zend_Registry::get('db');
        
        $sql = "SELECT DISTINCT space.uuid, stProps.val_num AS StartDate, endProps.val_num AS EndDate
                FROM space
                JOIN observe AS stObs ON (space.uuid = stObs.subject_uuid AND space.project_id = '$projectUUID')
                JOIN properties AS stProps ON stObs.property_uuid = stProps.property_uuid 
                JOIN observe AS endObs ON (space.uuid = endObs.subject_uuid AND space.project_id = '$projectUUID')
                JOIN properties AS endProps ON endObs.property_uuid = endProps.property_uuid 
                WHERE  stProps.variable_uuid = '$tStartVarID'
                AND endProps.variable_uuid = '$tEndVarID'
                AND space.project_id = '$projectUUID'
                ";    
                
        //echo $sql;
        $results  = $db->fetchAll($sql);
        $cCnt = 0;
        $dateLabelArray = array();
        foreach($results as $actRow){
            
            $itemUUID = $actRow["uuid"];
            $tStart = $actRow["StartDate"];
            $tEnd = $actRow["EndDate"];
            
            if(($tStart != 0)&&($tEnd != 0)){
                if(is_numeric($tStart) && is_numeric($tEnd)){
                    if($posBCE){
                        $tStart = $tStart * -1;
                        $tEnd = $tEnd * -1;
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
                    
                    $dateLabelArray[] = $dateLabel;
                    
                    $where = array();
                    $where[] = "project_id  = '".$projectUUID."' ";
                    $where[] = "uuid  = '".$itemUUID."' ";
                    $db->delete('initial_chrono_tag', $where);
                    
                    $data = array('project_id'=> $projectUUID,
                    'uuid'=> $itemUUID,
                    'creator_uuid'=> 'oc',
                    'label'=> $dateLabel,
                    'start_time'=> $tStart,
                    'end_time'=> $tEnd,
                    'note_id'=> 'Default set',
                    'public'=> 1
                    );
                    
                    $db->insert('initial_chrono_tag', $data);
                    
                    $cCnt++;
                }//end case with numeric
            }//end case with non zero
        }//end loop
   
        $output = array("count"=>$cCnt, "labels"=>$dateLabelArray);
        
        echo  Zend_JSON::encode($output);
                
    }//end function
    
    
    
    
    
    
    function reviewPropsAction(){
        $this->_helper->viewRenderer->setNoRender();
        $projectUUID = $_REQUEST['projectUUID'];
        
        if(isset($_REQUEST['notes'])){
            $doNotes = true;
        }
        else{
            $doNotes = false;
        }
        
        
        $db = Zend_Registry::get('db');
        
        $sql = "SELECT var_tab.var_label, var_tab.variable_uuid, var_tab.var_type
        FROM var_tab
        WHERE var_tab.project_id = '$projectUUID' ";
    
        $output = "";
        $varRows = $db->fetchAll($sql, 2);
        
        if($doNotes){
            unset($varRows);
            $varRows = array();
            $varRows[0]["var_label"] = "Notes";
            $varRows[0]["variable_uuid"] = "NOTES";
            $varRows[0]["var_type"] = "NOTES";
        }
        
        
        foreach($varRows as $actVarRow){
            
            $actVar = $actVarRow["var_label"];
            $actVarID = $actVarRow["variable_uuid"];
            $actVarType = $actVarRow["var_type"];
            
            $output .= "<h2>$actVar ($actVarType)</h2>";
            $output .= "";
            
            
            $sql = "SELECT properties.property_uuid, properties.value_uuid,
            val_tab.val_text
            FROM properties
            JOIN val_tab ON properties.value_uuid = val_tab.value_uuid
            WHERE properties.variable_uuid = '$actVarID'
            AND  properties.project_id = '$projectUUID' ";
            
            if($actVarType == "Nominal" || $actVarType == "NOTES" || $actVarType == "Calendric"){
                 $propRows = $db->fetchAll($sql, 2);
            }
            else{
                $propRows = false;
            }
            
            if($propRows){
                foreach($propRows as $actPropRow){
                    $actPropId =  $actPropRow["property_uuid"];
                    $actText = $actPropRow["val_text"];
                    
                    $output .= "<p>$actText</p><p>";
                    $output .= "<form action='http://penelope.oc/edit-dataset/chrono-tag-prop' method='get'>";
                    $output .= "<input type='hidden' value='$actPropId' name='propertyUUID' />";
                    $output .= "Time Start: <input type='text' name='tStart' />, ";
                    $output .= "Time End: <input type='text' name='tEnd' />";
                    $output .= "<input type='submit' value='Submit' />";
                    $output .= "</form>";
                    $output .= "</p>";
                }
            }
            
            $output .= "<br/>";
            
        }    
    
        echo $output;
    
    }
    
    
    //this action orders the variables for each class in order of their import
    function varOrderAction(){
        
        $this->_helper->viewRenderer->setNoRender();
        $projectUUID = $_REQUEST['projectUUID'];
        $db = Zend_Registry::get('db');
        
        
        $sql = "SELECT DISTINCT var_tab.var_label, var_tab.variable_uuid, var_tab.var_type, field_summary.field_num
        FROM var_tab
        LEFT JOIN field_summary ON (var_tab.var_label = field_summary.field_label
            AND var_tab.project_id = field_summary.project_id
            AND (field_summary.field_type = 'Property' OR field_summary.field_type = 'Variable'))
        LEFT JOIN file_summary ON file_summary.source_id = var_tab.source_id
        WHERE var_tab.project_id = '$projectUUID'
        ORDER BY file_summary.last_modified_timestamp, field_summary.field_num";
    
        $output = "";
        $varOrder = 1;
        $doneVarIDs = array();
        $varRows = $db->fetchAll($sql, 2);
        foreach($varRows as $actVarRow){
            
            $actVar = $actVarRow["var_label"];
            $actVarID = $actVarRow["variable_uuid"];
            $actFNum = $actVarRow["field_num"];
            
            if(!in_array($actVarID,$doneVarIDs)){
            
                $output .= "<p>$varOrder: $actVar (Field Number: $actFNum) <em>$actVarID</em></p>";
                $doneVarIDs[] = $actVarID;
                
                $where = array();
                $where[] = "project_id  = '".$projectUUID."' ";
                $where[] = "variable_uuid  = '".$actVarID."' ";
				$where[] = "sort_order = 0 ";
                
                $data = array("sort_order"=> $varOrder);
                
                $db->update('var_tab', $data, $where);
                
                $varOrder++;
            }
            
            
        }//end loop through variables
        
        echo $output;
    }//end function
    
    
    
    //this makes new links beteween space items created by an uploaded table, and a person
    //do this for globally assigning media for an import
    function obsMetadataAction(){
    
        $this->_helper->viewRenderer->setNoRender();
        $projectUUID = $_REQUEST["projectUUID"];
        
        if(isset($_REQUEST["rowData"])){
            $rowDataString = $_REQUEST["rowData"];
            $rowData = Zend_Json::decode($rowDataString);
            $source_id = $rowData["source_id"];
            $obs_num = $rowData["obs_num"];
        }
        else{
            $source_id = $_REQUEST["tabID"];
            $obs_num = $_REQUEST["obs"];
        }
        
        $obs_name = $_REQUEST["name"];
        $obs_type = $_REQUEST["type"];
        $obs_note = $_REQUEST["note"];
    
        $obsHash = md5($projectUUID."_".$source_id."_".$obs_num);
        
        $db = Zend_Registry::get('db');
        $where = array();
        $where[] = "obs_id  = '$obsHash' ";
        $db->delete("obs_metadata", $where);
        
        $data = array("obs_id" => $obsHash,
                      "project_id"=> $projectUUID,
                      "source_id"=> $source_id,
                      "obs_num"=> $obs_num,
                      "obs_name"=> $obs_name,
                      "obs_type"=> $obs_type,
                      "obs_notes"=>$obs_note
                      );
        
        $db->insert("obs_metadata", $data);
        
        echo "done";
        
    }
    
    
    private function item_obs_data($itemUUID){
        
         $db = Zend_Registry::get('db');
         $sql = "SELECT DISTINCT observe.source_id, observe.obs_num, file_summary.description 
         FROM observe
         LEFT JOIN file_summary ON (observe.project_id = file_summary.project_id
            AND observe.source_id = file_summary.source_id )
         WHERE observe.subject_uuid = '$itemUUID'";
        
        $resRows = $db->fetchAll($sql, 2);
        if(count($resRows)>0){
            return $resRows;
        }
        else{
            return false;
        }
    }
    
    
    
    //this makes new links beteween space items created by an uploaded table, and a person
    //do this for globally assigning media for an import
    function linkImportAction(){
    
        $this->_helper->viewRenderer->setNoRender();
        $projectUUID = $_REQUEST["projectUUID"];
        $source_id = $_REQUEST["tabID"];
        $targUUID = $_REQUEST["targUUID"];
        $linkRel = $_REQUEST["linkRel"];
        if(strlen($_REQUEST["linkObs"])>0){
            $linkObs = true;
        }
        else{
            $linkObs = false;
        }
        
        if(strlen($_REQUEST["obsNum"])>0){
            $ObsNumber = $_REQUEST["obsNum"];
        }
        else{
            $ObsNumber = 1;
        }
        
        
        //default is location and object
        if(strlen($_REQUEST["targType"])>0){
            $targType = $_REQUEST["targType"];
        }
        else{
            $targType = 'Persons';
        }
    
        $db = Zend_Registry::get('db');
        
        $sql = "SELECT space.uuid
                FROM space
                WHERE (space.source_id  = '$source_id' )
                AND space.project_id = '$projectUUID' ";
        
        
        if($linkObs){
            $sql = "SELECT DISTINCT observe.subject_uuid AS uuid, observe.obs_num
                FROM observe
                WHERE (observe.source_id  = '$source_id' )
                AND observe.project_id = '$projectUUID' ";
        }
        
        
        
        //echo $sql;
        $linkCount = 0;
        $resRows = $db->fetchAll($sql, 2);
        foreach($resRows as $row){
            $originUUID = $row["uuid"]; // already exists at this ID
            $ObsNumber = $row["obs_num"];
            $newLinkID = $this->addLinkingRel($originUUID , 'Locations or Objects', $targUUID, $targType, $linkRel, $projectUUID, $source_id, $ObsNumber);
            $linkCount++;
        }
    
        echo $linkCount;
    }
    
    
    
    
    //this makes new links beteween space items created by an uploaded table, and a person
    //do this for globally assigning media for an import
    function tableObsAction(){
    
        $this->_helper->viewRenderer->setNoRender();
        $projectUUID = $_REQUEST["projectUUID"];
        $source_id = $_REQUEST["tabID"];
        $newTab = $_REQUEST["NewTabID"];
        $obsNum = $_REQUEST["obsNum"];
        
        
        $db = Zend_Registry::get('db');
        
        //select items that have new observations
        $sql = "SELECT DISTINCT observe.subject_uuid
                FROM observe
                WHERE (observe.source_id  = '$newTab' )
                AND observe.project_id = '$projectUUID' ";
        
        echo $sql;
        
        $resRows = $db->fetchAll($sql, 2);
        $i=0;
        foreach($resRows as $row){
            $itemUUID = $row["subject_uuid"]; // already exists at this ID
        
            $where = array();
            $where[] = "subject_uuid  = '$itemUUID' ";
            $where[] = "source_id  = '$source_id' ";
            $where[] = "project_id = '$projectUUID' ";
            $data = array("obs_num" => $obsNum);
            $db->update("observe", $data, $where);
            unset($where);
            unset($data);
            $where = array();
            $where[] = "origin_uuid  = '$itemUUID' ";
            $where[] = "source_id  = '$source_id' ";
            $where[] = "project_id = '$projectUUID' ";
            $data= array("origin_obs" =>$obsNum);
            $db->update("links", $data, $where);
            
            $i++;
        }
        
        echo $i;
    }
    
    
    
    
    
    
    
    
    
    //this makes new links beteween media items created by an uploaded table, and a person
    //do this for globally assigning media for an import
    function tabMediaLinkAction(){
    
        $this->_helper->viewRenderer->setNoRender();
        $projectUUID = $_REQUEST["projectUUID"];
        $source_id = $_REQUEST["tabID"];
        $targUUID = $_REQUEST["targUUID"];
        $linkRel = $_REQUEST["linkRel"];
        
        //default is location and object
        if(strlen($_REQUEST["targType"])>0){
            $targType = $_REQUEST["targType"];
        }
        else{
            $targType = 'Persons';
        }
    
        $db = Zend_Registry::get('db');
        
        $sql = "SELECT resource.uuid
                FROM resource
                WHERE (resource.source_id  = '$source_id' )
                AND resource.project_id = '$projectUUID' ";
        
        
        //echo $sql;
        $linkCount = 0;
        $resRows = $db->fetchAll($sql, 2);
        foreach($resRows as $row){
            $originUUID = $row["uuid"]; // already exists at this ID
            $newLinkID = $this->addLinkingRel($originUUID , 'Media (various)', $targUUID, $targType, $linkRel, $projectUUID, $source_id);
            $linkCount++;
        }
    
        echo $linkCount;
    }
    
    
    //this makes a new link beteween two items. defaults origin as spatial, target as media
    function linkItemAction(){
        
        $this->_helper->viewRenderer->setNoRender();
        $projectUUID = $_REQUEST["projectUUID"];
        $originUUID = $_REQUEST["originUUID"];
        $originRel = $_REQUEST["originRel"];
        $targUUID = $_REQUEST["targUUID"];
        
		  $nextprev = false;
		  if(isset($_REQUEST["nextprev"])){
			  if($_REQUEST["nextprev"] == "np"){
				  $nextprev = true;
				  $originRel = "Next";
			  }
		  }
		
		  if(isset($_REQUEST['source'])){
            $source = $_REQUEST['source'];
        }
        else{
            $source = "manual";
        }
		
		
        //default is location and object
        if(strlen($_REQUEST["originType"])>0){
            $originType = $_REQUEST["originType"];
        }
        else{
            $originType = 'Locations or Objects';
        }
        
        //default is media
        if(strlen($_REQUEST["targType"])>0){
            $targType = $_REQUEST["targType"];
        }
        else{
            $targType = 'Media (various)';
        }
        
        $newLinkID = $this->addLinkingRel($originUUID, $originType, $targUUID, $targType, $originRel, $projectUUID, $source);
		  $output = array("linkID" => $newLinkID);
		
        if($nextprev){
			//add the reciprocal "Previous" link
			$newLinkID = $this->addLinkingRel($targUUID, $targType, $originUUID, $originType, "Previous", $projectUUID, $source);
		  }
		
        echo  Zend_JSON::encode($output);
        
    }//end function
    
    
    
    
    
    //this makes a new link beteween two items. defaults origin as Person, target as media
    function linkClassAction(){
        
        $this->_helper->viewRenderer->setNoRender();
        $projectUUID = $_REQUEST["projectUUID"];
        
        $originRel = $_REQUEST["originRel"];
        $classUUID = $_REQUEST["classUUID"];
        
        $targUUID = $_REQUEST["targUUID"];
        
        //default is location and object
        if(strlen($_REQUEST["originType"])>0){
            $originType = $_REQUEST["originType"];
        }
        else{
            $originType = 'Locations or Objects';
        }
        
        //default is media
        if(strlen($_REQUEST["targType"])>0){
            $targType = $_REQUEST["targType"];
        }
        else{
            $targType = 'Person';
        }
        
        $newLinkIDs = array();
        $classWhere = " AND space.class_uuid = '".$_REQUEST['classUUID']."' ";
        
        $db = Zend_Registry::get('db');
        $sql = 'SELECT space.uuid
            FROM space
            WHERE space.project_id = "'.$projectUUID.'"
            '.$classWhere;
            
        
        $results  = $db->fetchAll($sql);
        $cCnt = 0;
        foreach($results as $actRow){
            
            $originUUID = $actRow["uuid"];
            $newLinkIDs[] = $this->addLinkingRel($originUUID, $originType, $targUUID, $targType, $originRel, $projectUUID);
        
        }//end loop
        
        
        echo  Zend_JSON::encode(array("linkIDs"=>$newLinkIDs));
        
    }//end function
    
    
    //this makes a new link beteween two items. defaults origin as Person, target as media
    function linkClassDeleteAction(){
        
        $this->_helper->viewRenderer->setNoRender();
        $projectUUID = $_REQUEST["projectUUID"];
        
        $originRel = $_REQUEST["originRel"];
        $classUUID = $_REQUEST["classUUID"];
        
        $targUUID = $_REQUEST["targUUID"];
        
        //default is location and object
        if(strlen($_REQUEST["originType"])>0){
            $originType = $_REQUEST["originType"];
        }
        else{
            $originType = 'Locations or Objects';
        }
        
        //default is media
        if(strlen($_REQUEST["targType"])>0){
            $targType = $_REQUEST["targType"];
        }
        else{
            $targType = 'Person';
        }
        
        $newLinkIDs = array();
        $classWhere = " AND space.class_uuid = '".$_REQUEST['classUUID']."' ";
        
        $db = Zend_Registry::get('db');
        $sql = 'SELECT space.uuid
            FROM space
            WHERE space.project_id = "'.$projectUUID.'"
            '.$classWhere;
            
        
        $results  = $db->fetchAll($sql);
        $cCnt = 0;
        foreach($results as $actRow){
            
            $originUUID = $actRow["uuid"];
            $where = array();
            $where[] = "project_id  = '".$projectUUID."' ";
            $where[] = "origin_uuid  = '".$originUUID."' ";
            $where[] = "targ_uuid  = '".$targUUID."' ";
            $db->delete('links', $where);
        
        }//end loop
        
        
        echo "Done";
        
    }//end function
    
    
    
    //this function adds a note to an item
    function addNoteClassAction(){
        $this->_helper->viewRenderer->setNoRender();
        $newText =  $_REQUEST['newText'];
        $classUUID = $_REQUEST['classUUID'];
        $projectUUID = $_REQUEST['projectUUID'];
        if(isset($_REQUEST['itemType'])){
            $itemType = $_REQUEST['itemType'];
        }
        else{
            $itemType = "Locations or Objects";
        }
        
    
        $propUUID = false;
    
        if(!$itemType == false){
            $db = Zend_Registry::get('db');
            $valueUUID = $this->get_make_ValID($newText, $projectUUID);
            $propUUID = $this->get_make_PropID("NOTES", $valueUUID, $projectUUID);
            
            
            $classWhere = " AND space.class_uuid = '".$_REQUEST['classUUID']."' ";
            $sql = 'SELECT space.uuid
            FROM space
            WHERE space.project_id = "'.$projectUUID.'"
            '.$classWhere;
            
            $results  = $db->fetchAll($sql);
            $cCnt = 0;
            foreach($results as $actRow){
            
                $itemUUID = $actRow["uuid"];
                $obsHashText = md5($projectUUID . "_" . $itemUUID . "_" . "1" . "_" . $propUUID);
                        
                $data = array("project_id"=> $projectUUID,
                          "source_id"=> 'manual',
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
        
        echo Zend_JSON::encode(array('NumberNotes'=>$cCnt, 'itemType'=>$itemType, 'propUUID'=>$propUUID));

    }
    
    
    
    
    
    
    
    
    
    function containLinkAction(){
        
        $this->_helper->viewRenderer->setNoRender();
        $projectUUID = $_REQUEST["projectUUID"];
        
        $parentUUID = $_REQUEST["parentUUID"];
        $childUUID = $_REQUEST["childUUID"];
        
        
        if(isset($_REQUEST["addLink"])){
            $addLink = true;
        }
        else{
            $addLink = false;
        }
        
        
        $db = Zend_Registry::get('db');
        
        if(!$addLink){
            //delete other containment relations, if addlink is false
            $where = array();
            $where[] = "project_id  = '".$projectUUID."' ";
            $where[] = "child_uuid  = '".$childUUID."' ";
            $db->delete('space_contain', $where);
        }
        
        
        $containHash = md5($parentUUID . '_' . $childUUID);
        $data = array("project_id" => $projectUUID,
                      "source_id" => "manual",
                      "hash_all" => $containHash,
                      "parent_uuid" => $parentUUID,
                      "child_uuid" => $childUUID
                      );
        
        try{            
            $db->insert("space_contain", $data);
            echo  Zend_JSON::encode(array("added"=>1));
        } catch (Exception $e) {
            echo  Zend_JSON::encode(array("added"=>0));
        }
        
    }//end function
    
    
    
    
    
    
    
    
    //this gets a new media item
    function addMediaAction(){
        
        $this->_helper->viewRenderer->setNoRender();
        $projectUUID = $_REQUEST["projectUUID"];
        if(isset($_REQUEST["fullURI"])){
            $fullURI = $_REQUEST["fullURI"];
            $previewURI = $_REQUEST["previewURI"];
            $thumbURI = $_REQUEST["thumbURI"];
            $fName = $_REQUEST["fName"];
            $URIsent = true;
        }
        else{
            $URIsent = false;
            $file = $_FILES["file"];
            $fName = $_FILES['file']['name'];
        }
        
        $fLabel = $_REQUEST["fLabel"];
        
         
        $fNote = $_REQUEST["fNote"];
        
        $originUUID = $_REQUEST["originUUID"];
        $originType = $_REQUEST["originType"];
        $originRel = $_REQUEST["originRel"];
        
        $personUUID =  $_REQUEST["personUUID"];
        $personRel =  $_REQUEST["persRel"];
        
	if(isset($_REQUEST["tabID"])){
	    $source_id = $_REQUEST["tabID"];
	}
	else{
	    $source_id = "manual";
	}
	
        
        $db = Zend_Registry::get('db');
        
        $sql = "SELECT resource.uuid
                FROM resource
                WHERE (resource.ia_fullfile = '$fullURI'
                OR resource.res_filename = '$fName' )
                AND resource.project_id = '$projectUUID' ";
        
        
        //echo $sql;
        
        $resRows = $db->fetchAll($sql, 2);
        if($resRows){
            $resUUID = $resRows[0]["uuid"]; // already exists at this ID
        }
        else{
            //make a new resource
            $resUUID = GenericFunctions::generateUUID(); 
            $data = array('project_id' => $projectUUID ,
                      'source_id' => $source_id,
                      'uuid' => $resUUID,
                      'res_label' => $fLabel,
                      'res_filename'  => $fName,
                      'ia_thumb' => $thumbURI,
                      'ia_preview' => $previewURI,
                      'ia_fullfile' => $fullURI);
            
            $db->insert("resource", $data);
            
            if(strlen($fNote)>1){
                $valueUUID = $this->get_make_ValID($fNote, $projectUUID);
                $propUUID = $this->get_make_PropID("NOTES", $valueUUID, $projectUUID);
                $obsHashText = md5($projectUUID . "_" . $resUUID . "_" . "1" . "_" . $propUUID);
                        
                $data = array("project_id"=> $projectUUID,
                          "source_id"=> $source_id,
                          "hash_obs" => $obsHashText,
                          "subject_type" => "Media (various)",
                          "subject_uuid" => $resUUID,
                          "obs_num" => 1,
                          "property_uuid" => $propUUID);
                try{            
                    $db->insert("observe", $data); 
                } catch (Exception $e) {
                    echo $e->getMessage(), "\n";
                }
            }
            
            
        }
        
        $mediaOfLinkID = $this->addLinkingRel($originUUID, $originType, $resUUID, 'Media (various)', $originRel, $projectUUID);
        $personOfLinkID = $this->addLinkingRel($resUUID, 'Media (various)', $personUUID, 'Person', $personRel, $projectUUID);
        $output = array("resourceUUID"=>$resUUID,
                        "mediaLink"=> $mediaOfLinkID,
                        "personLink" => $personOfLinkID,
                        "label" => $fLabel,
                        "links" => array("thumb"=>$thumbURI,
                                         "preview"=>$previewURI,
                                         "full"=>$fullURI)
                        );
	
	header('Content-Type: application/json; charset=utf8');
        echo Zend_JSON::encode($output);
    }
    
    
    
    
    
    
    
    
    
    
    
    function setPropChangeAction(){
        
        $this->_helper->viewRenderer->setNoRender();
        
        if(isset($_REQUEST["projectUUID"])){
            $projectUUID = $_REQUEST["projectUUID"];
        }
        else{
            $projectUUID = "";
        }
        
        if(isset($_REQUEST["itemUUID"])){
            $itemUUID = $_REQUEST["itemUUID"];
            $obsArray = $this->item_obs_data($itemUUID);
        }
        else{
            $itemUUID = "";
            $obsArray = false;
        }
        
        $this->host = "http://".$_SERVER['SERVER_NAME'];
        
        
         $output ="
        <br/>
        <h2>Add observation metadata</h2>
        <form action='".$this->host."/edit-dataset/obs-metadata' method='post'>
        <p>Project UUID: <input name='projectUUID' type='text' value='".$projectUUID."' size='60'  /></p>";
        
        if($obsArray != false){
            foreach($obsArray as $row){
                $rowData = Zend_Json::encode(array("source_id"=>$row["source_id"], "obs_num"=>$row["obs_num"] ));
                $output .= "<p>".$row["description"]." <em>".$row["source_id"]." ".$row["obs_num"];
                $output .= "</em><input name='rowData' type='radio' value ='".$rowData."' /><p>";
            }
        }
        
        $output .= "
        <p>Observation Name <input name='name' type='text' size='60' ></p>
        <p>Observation Type <input name='type' type='text' size='60' ></p>
        <p>Observation note:</p>
        <textarea name ='note' rows='10' cols='50'></textarea>
        <p><input name='submit' type='submit'></p>
        </form>
    
        
        ";
        
        echo $output;
        
        
        
        $output ="
        <br/>
        <h2>Change an Existing Property</h2>
        <form action='".$this->host."/edit-dataset/global-prop-change' method='post'>
        <p>Property UUID: <input name='propertyUUID' type='text' size='60' ></p>
        <p>New Text:</p>
        <textarea name ='newText' rows='10' cols='50'>
            Enter your new text
        </textarea>
        <p>Append? <input name='append' type='text' size='60' ></p>
        <p><input name='submit' type='submit'></p>
        </form>
    
        
        ";
        
        //echo $output;
        
        $output ="
        <br/>
        <h2>Add a note to an item</h2>
        <form action='".$this->host."/edit-dataset/add-note' method='post'>
        <p>Item UUID: <input name='itemUUID' type='text' value='".$itemUUID."' size='60'></p>
        <p>Item Type: <input name='itemType' type='text' size='60' ></p>
        <p>Project UUID: <input name='projectUUID' type='text' value='".$projectUUID."' size='60'  /></p>
        <p>New Text:</p>
        <textarea name ='newText' rows='10' cols='50'>
            Enter your new text
        </textarea>
        <p><input name='submit' type='submit'></p>
        </form>
    
        
        ";
        
        echo $output;
        
        
        $output ="
        <br/>
        <h2>Add a note to a Whole Class of Items</h2>
        <form action='".$this->host."/edit-dataset/add-note-class' method='post'>
        <p>Class UUID: <input name='classUUID' type='text' size='60' ></p>
        <p>Item Type: <input name='itemType' type='text' value='Locations or Objects'></p>
        <p>Project UUID: <input name='projectUUID' type='text' value='".$projectUUID."' size='60'  /></p>
        <p>New Text:</p>
        <textarea name ='newText' rows='10' cols='50'>
            Enter your new text
        </textarea>
        <p><input name='submit' type='submit'></p>
        </form>
    
        
        ";
        
        echo $output;
        
        
        
        $output ="
        <br/>
        <h2>Upload File</h2>
        <form action='".$this->host."/edit-dataset/add-media' method='post' enctype='multipart/form-data'>
        <p>Project UUID: <input name='projectUUID' type='text' value='".$projectUUID."' size='60'  /></p>
        <p>Choose a file to upload: <input name='file' type='file' /></p>
        <p><em>...Or provide Full URI</em>: <input name='fullURI' type='text' size='75'/></p>
        <p><em>...Or provide Preview URI</em>: <input name='previewURI' type='text' size='75'/></p>
        <p><em>...Or provide Thumbnail URI</em>: <input name='thumbURI' type='text' size='75'/></p>
        <p><em>...Or provide Filename </em>: <input name='fName' type='text' /></p>
        <p>Media Label: <input name='fLabel' type='text' /></p>
        <p>Media Note: <input name='fNote' type='text' /></p>
        <br/>
        <p>Link to (UUID): <input name='originUUID' type='text' value='".$itemUUID."' size='60'></p>
        <p>Link to (type): <input name='originType' type='text' size='60' ></p>
        <p>Linking Relationship: <input name='originRel' type='text' size='60' ></p>
        
        <br/>
        <p>Link to Person (UUID): <input name='personUUID' type='text' size='60' ></p>
        <p>Person Linking Relationship: <input name='persRel' type='text' size='60' ></p>
        
        <p><input name='submit' type='submit'></p>
        </form>
        ";
        
        echo $output;
        
        $output ="
        <br/>
        <h2>Add Space Containment Relation</h2>
        <form action='".$this->host."/edit-dataset/contain-link' method='post'>
        <p>Project UUID: <input name='projectUUID' type='text' value='".$projectUUID."' size='60'  /></p>
        <p>Parent (UUID): <input name='parentUUID' type='text' size='60' ></p>
        <p>Child (UUID): <input name='childUUID' type='text' size='60' ></p>
        <p>Add relation ?: <input name='addLink' type='text' size='60' ></p>
        <p><input name='submit' type='submit'></p>
        </form>
    
        
        ";
        
        echo $output;
        
        
        
        
        $output ="
        <br/>
        <h2>Add Linking Relation to an Item</h2>
        <form action='".$this->host."/edit-dataset/link-item' method='post'>
        <p>Project UUID: <input name='projectUUID' type='text' value='".$projectUUID."' size='60'  /></p>
        <p>Link Origin (UUID): <input name='originUUID' type='text' value='".$itemUUID."' size='60'></p>
        <p>Link Origin (type): <input name='originType' type='text' size='60' ></p>
        <p>Linking Relationship: <input name='originRel' type='text' size='60' ></p>
		<p>Check for 'next', 'previous' reciprocal like: <input name='nextprev' type='checkbox' value='np' ></p>
        <p>Link Target(UUID): <input name='targUUID' type='text' size='60' ></p>
        <p>Link Target (type): <input name='targType' type='text' size='60' ></p>
        <p><input name='submit' type='submit'></p>
        </form>
    
        
        ";
        
        echo $output;
        
        
        $output ="
        <br/>
        <h2>Add Linking Relation for whole Class</h2>
        <form action='".$this->host."/edit-dataset/link-class' method='post'>
        <p>Project UUID: <input name='projectUUID' type='text' value='".$projectUUID."' size='60'  /></p>
        <p>Link Origin Class (UUID): <input name='classUUID' type='text' size='60' ></p>
        <p>Link Origin (type): <input name='originType' type='text' value='Locations or Objects'></p>
        <p>Linking Relationship: <input name='originRel' type='text' size='60' ></p>
        <p>Link Target(UUID): <input name='targUUID' type='text' size='60' ></p>
        <p>Link Target (type): <input name='targType' type='text' size='60' ></p>
        <p><input name='submit' type='submit'></p>
        </form>
    
        
        ";
        
        echo $output;
        
        
        
         $output ="
        <br/>
        <h2>Use Variable for Linking Relations</h2>
        <form action='".$this->host."/edit-dataset/variable-link' method='post'>
        <p>Project UUID: <input name='projectUUID' type='text' value='".$projectUUID."' size='60'  /></p>
        <p>Link Origin (type): <input name='originType' type='text' value='Locations or Objects'></p>
        <p>Link Target (type): <input name='targType' type='text' size='60' ></p>
        <p>Linking Relationship: <input name='linkRel' type='text' size='60' ></p>
        <p>Variable UUID: <input name='varUUID' type='text' size='60' ></p>
        <p>Item Label Prefix ('Lot', 'Feature', etc.): <input name='gPrefix' type='text' size='60' ></p>
        <p>Ignore bad item label prefix ('L', 'F', etc.): <input name='bPrefix' type='text' size='60' ></p>
        <p><input name='submit' type='submit'></p>
        </form>
    
        
        ";
        
        echo $output;
        
        
        
        
        $output ="
        <br/>
        <h2>DELETE Linking Relation for whole Class</h2>
        <form action='".$this->host."/edit-dataset/link-class-delete' method='post'>
        <p>Project UUID: <input name='projectUUID' type='text' value='".$projectUUID."' size='60'  /></p>
        <p>Link Origin Class (UUID): <input name='classUUID' type='text' value='".$itemUUID."' size='60'></p>
        <p>Link Origin (type): <input name='originType' type='text' value='Locations or Objects'></p>
        <p>Linking Relationship: <input name='originRel' type='text' size='60' ></p>
        <p>Link Target(UUID): <input name='targUUID' type='text' size='60' ></p>
        <p>Link Target (type): <input name='targType' type='text' size='60' ></p>
        <p><input name='submit' type='submit'></p>
        </form>
    
        
        ";
        
        echo $output;
        
        
        
        
        
        
        $output ="
        <br/>
        <h2>Change Obs for an Imported Table</h2>
        <form action='".$this->host."/edit-dataset/table-obs' method='post'>
        <p>Project UUID: <input name='projectUUID' type='text' value='".$projectUUID."' size='60'  /></p>
        <p>Old Table ID: <input name='tabID' type='text' size='60' ></p>
        <p>New Table ID: <input name='NewTabID' type='text' size='60' ></p>
        <p>Obs Number for Old Table: <input name='obsNum' type='text' size='60' ></p>
        <p><input name='submit' type='submit'></p>
        </form>
        ";
        
        echo $output;
        
        
        
        $output ="
        <br/>
        <h2>Add Linking Relation for an Imported Table</h2>
        <form action='".$this->host."/edit-dataset/link-import' method='post'>
        <p>Project UUID: <input name='projectUUID' type='text' value='".$projectUUID."' size='60'  /></p>
        <p>Table ID: <input name='tabID' type='text' size='60' ></p>
        <p>Linking Relationship: <input name='linkRel' type='text' size='60' ></p>
        <p>Link Target(UUID): <input name='targUUID' type='text' size='60' ></p>
        <p>Link Target (type): <input name='targType' type='text' size='60' ></p>
        <p>Do for all obs, not just new items?: <input name='linkObs' type='text' size='60' ></p>
        <p>Specific Observation?: <input name='obsNum' type='text' size='60' ></p>
        <p><input name='submit' type='submit'></p>
        </form>
    
        
        ";
        
        echo $output;
        
        
        
        
        $output ="
        <br/>
        <h2>Add Relation to All Media Created by Table (Media is origin, target is provided below)</h2>
        <form action='".$this->host."/edit-dataset/tab-media-link' method='post'>
        <p>Project UUID: <input name='projectUUID' type='text' value='".$projectUUID."' size='60'  /></p>
        <p>Link Target (UUID): <input name='targUUID' type='text' size='60' ></p>
        <p>Link Target (type): <input name='targType' type='text' size='60' ></p>
        <p>Linking Relationship: <input name='linkRel' type='text' size='60' ></p>
        <p>Do for Table(ID): <input name='tabID' type='text' size='60' ></p>
        <p><input name='submit' type='submit'></p>
        </form>
    
        
        ";
        
        echo $output;
        
        
        $output ="
        <br/>
        <h2>Add a Geo Reference to a Class</h2>
        <form action='".$this->host."/edit-dataset/class-context-geo' method='post'>
        <p>Project UUID: <input name='projectUUID' type='text' value='".$projectUUID."' size='60'  /></p>
        <p>Class (UUID): <input name='classUUID' type='text' size='60' ></p>
        <p>Latitude: <input name='lat' type='text' size='60' ></p>
        <p>Longitude: <input name='lon' type='text' size='60' ></p>
        <p><input name='submit' type='submit'></p>
        </form>
        ";
        
        echo $output;
        
        
        
        $output ="
        <br/>
        <h2>Add a Chronological Tag to an Item</h2>
        <form action='".$this->host."/edit-dataset/item-chrono-tag' method='post'>
        <p>Project UUID: <input name='projectUUID' type='text' value='".$projectUUID."' size='60'  /></p>
        <p>Item(UUID): <input name='itemUUID' type='text' size='60' ></p>
        <p>Early Date (start): <input name='tStart' type='text' size='60' ></p>
        <p>Late Date (end): <input name='tEnd' type='text' size='60' ></p>
        <p><input name='submit' type='submit'></p>
        </form>
    
        
        ";
        
        echo $output;
	
        
        $output ="
        <br/>
        <h2>Add a Chronological Tag to a Class</h2>
        <form action='".$this->host."/edit-dataset/class-context-tag' method='post'>
        <p>Project UUID: <input name='projectUUID' type='text' value='".$projectUUID."' size='60'  /></p>
        <p>Class (UUID): <input name='classUUID' type='text' size='60' ></p>
        <p>Early Date (start): <input name='tStart' type='text' size='60' ></p>
        <p>Late Date (end): <input name='tEnd' type='text' size='60' ></p>
        <p><input name='submit' type='submit'></p>
        </form>
    
        
        ";
        
        echo $output;
        
        
        $output ="
        <br/>
        <h2>Use Values for a Variable for Chronological Tags</h2>
        <form action='".$this->host."/edit-dataset/var-vals-chrono-tag' method='post'>
        <p>Project UUID: <input name='projectUUID' type='text' value='".$projectUUID."' size='60'  /></p>
        <p>Variable (UUID): <input name='varUUID' type='text' size='60' ></p>
        <p><input name='submit' type='submit'></p>
        </form>
    
        
        ";
        
        echo $output;
        
        
        $output ="
        <br/>
        <h2>Add a Chronological Tag to a Property</h2>
        <form action='".$this->host."/edit-dataset/prop-chrono-tag' method='post'>
        <p>Project UUID: <input name='projectUUID' type='text' value='".$projectUUID."' size='60'  /></p>
        <p>Property (UUID): <input name='propUUID' type='text' size='60' ></p>
        <p>Early Date (start): <input name='tStart' type='text' size='60' ></p>
        <p>Late Date (end): <input name='tEnd' type='text' size='60' ></p>
        <p><input name='submit' type='submit'></p>
        </form>
    
        
        ";
        
        echo $output;
        
        
        
        $output ="
        <br/>
        <h2>Add a Chronological Tag for two Variables</h2>
        <form action='".$this->host."/edit-dataset/vars-chrono-tag' method='post'>
        <p>Project UUID: <input name='projectUUID' type='text' value='".$projectUUID."' size='60'  /></p>
        <p>Early Date VarID (start): <input name='tStartVarID' type='text' size='60' ></p>
        <p>Late Date VarID (end): <input name='tEndVarID' type='text' size='60' ></p>
        <p>BCE Dates stated as positive numbers: <input name='posBCE' type='text' value='yes'></p>
        <p><input name='submit' type='submit'></p>
        </form>
    
        
        ";
        
        echo $output;
        
        
        $output ="
        <br/>
        <h2>Add a Note to a Variable</h2>
        <form action='".$this->host."/edit-dataset/var-note' method='post'>
        <p>Project UUID: <input name='projectUUID' type='text' value='".$projectUUID."' size='60'  /></p>
        <p>Variable UUID: <input name='variableUUID' type='text' size='60' ></p>
        <br/>
        <textarea name ='newNote' rows='10' cols='50'>
            Enter your new note
        </textarea>
        <p><input name='submit' type='submit'></p>
        </form>
        ";
        
        echo $output;
        
        
        
        
        $output ="
        <br/>
        <h2>Delete A Property</h2>
        <form action='".$this->host."/edit-dataset/delete-prop' method='post'>
        <p>Project UUID: <input name='projectUUID' type='text' value='".$projectUUID."' size='60'  /></p>
        <p>Property UUID: <input name='propertyUUID' type='text' size='60' ></p>
        <p><input name='submit' type='submit'></p>
        </form>
    
        
        ";
        
        echo $output;
        
        
        $output ="
        <br/>
        <h2>Change a Property Text</h2>
        <form action='".$this->host."/edit-dataset/global-prop-change' method='post'>
		<p>Project UUID: <input name='projectUUID' type='text' value='".$projectUUID."' size='60'  /></p>
        <p>Property UUID: <input name='propertyUUID' type='text' size='60' ></p>
        <p>New Text: <input name='newText' type='text' size='60' ></p>
        <p><input name='submit' type='submit'></p>
        </form>
    
        
        ";
        
        echo $output;
		
		
		$output ="
        <br/>
        <h2>Combine / Merge by string case for Variable </h2>
        <form action='".$this->host."/edit-dataset/var-caps-merge' method='post'>
        <p>Project UUID: <input name='projectUUID' type='text' value='".$projectUUID."' size='60'  /></p>
        <p>Variable UUID: ('all' for do all in project) <input name='varUUID' type='text' size='60' ></p>
        <p><input name='submit' type='submit'></p>
        </form>
    
        
        ";
        
        echo $output;
		
		
		
		$output ="
        <br/>
        <h2>Combine / Merge two properties</h2>
        <form action='".$this->host."/edit-dataset/global-prop-merge' method='post'>
        <p>Old (Delete) Property UUID: <input name='OldPropertyUUID' type='text' size='60' ></p>
        <p>Keep Property UUID: <input name='KeepPropertyUUID' type='text' size='60' ></p>
        <p><input name='submit' type='submit'></p>
        </form>
    
        
        ";
        
        echo $output;
		
        
        
	$output ="
        <br/>
        <h2>Add Linked Data to a Variable</h2>
        <form action='".$this->host."/linked-data/var' method='get'>
        <p>Variable UUID: <input name='varUUID' type='text' size='60' ></p>
        <p><input name='submit' type='submit'></p>
        </form>
    
        
        ";
        
        echo $output;
	
	
	/*
	 
	SELECT  `source_id` ,  `variable_uuid` ,  `var_label` , COUNT(  `source_id` ) AS TabCount
	FROM  `var_tab` 
	GROUP BY  `var_label` 
	ORDER BY COUNT(  `source_id` ) DESC ,  `var_label`
	
	*/
	
	$output ="
        <br/>
        <h2>Combine Two Variables</h2>
        <form action='".$this->host."/edit-dataset/merge-vars' method='get'>
        <p>Old (get rid of) Variable UUID: <input name='oldvarUUID' type='text' size='60' ></p>
	<p>New (keep) Variable UUID: <input name='newvarUUID' type='text' size='60' ></p>
        <p><input name='submit' type='submit'></p>
        </form>
    
        
        ";
        
        echo $output;
	
	
	//add property
	$output ="
        <br/>
        <h2>Add a Property to an Item</h2>
        <form action='".$this->host."/edit-dataset/add-prop' method='post'>
        <p>Project UUID: <input name='projectUUID' type='text' value='".$projectUUID."' size='60'  /></p>
	<p>Item UUID: <input name='itemUUID' type='text' value='".$itemUUID."' size='60' /></p>
	<p>Property UUID: <input name='propUUID' type='text' value=''  size='60' /></p>
        <p><input name='submit' type='submit'></p>
        </form>
    
        
        ";
        
        echo $output;
	
	//add a value
	$output ="
        <br/>
        <h2>Add a Value to a Variable to an Item</h2>
        <form action='".$this->host."/edit-dataset/add-var-val' method='post'>
        <p>Project UUID: <input name='projectUUID' type='text' value='".$projectUUID."' size='60'  /></p>
		<p>Item UUID: <input name='itemUUID' type='text' value='".$itemUUID."' size='60' /></p>
		<p>Variable UUID: <input name='varUUID' type='text' value=''  size='60' /></p>
		<p>Value Text: <input name='valText' type='text' value=''  size='60' /></p>
        <p><input name='submit' type='submit'></p>
        </form>
    
        
        ";
        
        echo $output;
	
	
        $output ="
        <br/>
        <h2>Merge A Person, Deleting A Bad Person ID and replace links with a Good Person ID</h2>
        <form action='".$this->host."/edit-transformed-data/multipersonsplit' method='post'>
        <p>Project UUID: <input name='projectUUID' type='text' value='".$projectUUID."' size='60'  /></p>
        <p>Bad (Remove) Person UUID: <input name='badID' type='text' size='60' ></p>
        <p>Good (Keep) Person UUID: <input name='goodID' type='text' size='60' ></p>
        <p><input name='submit' type='submit'></p>
        </form>
    
        
        ";
        
        echo $output;
        
        
        $output ="
        <br/>
        <h2>Check for Entity Integrity (multiple instances of a variable used on the same item)</h2>
        <form action='".$this->host."/edit-dataset/field-integrity' method='get'>
        <p>Project UUID: <input name='projectUUID' type='text' value='".$projectUUID."' size='60'  /></p>
        <p>Critical Variable UUID (should not be used more than 1 time): <input name='varID' type='text' size='60' ></p>
        <p><input name='submit' type='submit'></p>
        </form>
    
        
        ";
        
        echo $output;
        
        
        $output ="
        <br/>
        <h2>Increment up Obs Numbers from a Table</h2>
        <form action='".$this->host."/edit-dataset/obs-add' method='post'>
        <p>Project UUID: <input name='projectUUID' type='text' value='".$projectUUID."' size='60'  /></p>
        <p>Table ID: <input name='tabName' type='text' size='60' ></p>
        <p>Number to add to Obs Num: <input name='obsAdd' type='text' size='60' ></p>
        <p>Modifiy Obs Metadata: <input name='obsMeta' type='text' value='true'></p>
        <p><input name='submit' type='submit'></p>
        </form>
    
        
        ";
        
        echo $output;
        
	
	$output ="
        <br/>
        <h2>Merge Items Action</h2>
        <form action='".$this->host."/edit-transformed-data/merge-items' method='post'>
        <p>Project UUID: <input name='projectUUID' type='text' value='".$projectUUID."' size='60'  /></p>
        <p>Old ID (item to be moved, then deleted): <input name='oldID' type='text' size='60' ></p>
        <p>Keep ID (item to be kept): <input name='keepID' type='text' size='60' ></p>
        <p><input name='submit' type='submit'></p>
        </form>
    
        
        ";
        
        echo $output;
	
	
		$output ="
        <br/>
        <h2>Move Media Files (Server and DB)</h2>
        <form action='move-media' method='post'>
        <p>Project UUID: <input name='projectUUID' type='text' value='".$projectUUID."' size='60'  /></p>
		<p>Media UUID: <input name='itemUUID' type='text' value='".$itemUUID."' size='60' /></p>
        <p><em>New Full URI</em>: <input name='fullURI' type='text' size='120'/></p>
        <p><em>New Preview URI</em>: <input name='previewURI' type='text' size='120'/></p>
        <p><em>New Thumbnail URI</em>: <input name='thumbURI' type='text' size='120'/></p>
        <p><input name='submit' type='submit'></p>
        </form>
    
        
        ";
        
        echo $output;
	
	
	
	
		  $output ="
        <br/>
        <h2>Use Variable UUID for Describing Linked or Contained Items</h2>
        <form action='var-link-label' method='post'>
        <p>Project UUID: <input name='projectUUID' type='text' value='".$projectUUID."' size='60'  /></p>
		  <p>Labeling Var UUID: <input name='varUUID' type='text' value='' size='60' /></p>
        <p>Linking relations ('link' or 'contain'): <input name='relType' type='text' size='60'/></p>
        <p><em>Optional</em> ClassUUID: <input name='classUUID' type='text' size='60'/></p>
        <p><em>Optional</em> Document Type <input name='docType' type='text' size='60'/></p>
		  <p><em>Optional</em> Table ID <input name='tabName' type='text' size='60'/></p>
        <p><input name='submit' type='submit'></p>
        </form>
    
        
        ";
        
        echo $output;
	
	
    }
    
    
	function moveMediaAction(){
        $db = Zend_Registry::get('db');
		Zend_Loader::loadClass('dataEdit_editMedia');
        $this->_helper->viewRenderer->setNoRender();
        
		$projectUUID = $_REQUEST['projectUUID'];
        $itemUUID = $_REQUEST['itemUUID'];
        $newThumb =  $_REQUEST['thumbURI'];
		$newPreview =  $_REQUEST['previewURI'];
		$newFull =  $_REQUEST['fullURI'];
		$output = array();
		$output["reqUUID"] = $itemUUID;
		
		$editMedia = new dataEdit_editMedia;
		$editMedia->initialize($db);
		if($editMedia->getByID($itemUUID)){
			$output["label"] = $editMedia->label;
			$output["response"] = $editMedia->updateFiles($newThumb, $newPreview, $newFull);
		}
		
		header('Content-Type: application/json; charset=utf8');
		echo json_encode($output);
	
	}
	
    function obsAddAction(){
        $this->_helper->viewRenderer->setNoRender();
        
        $projectUUID = $_REQUEST['projectUUID'];
        $source_id = $_REQUEST['tabName'];
        $obsAdd = $_REQUEST['obsAdd'];
        
        
        if(isset($_REQUEST['obsMeta'])){
            $metaMod = true;
        }
        else{
            $metaMod = false;
        }
        
        $db = Zend_Registry::get('db');
        $sql = "SELECT DISTINCT observe.obs_num
                FROM observe
                WHERE (observe.source_id  = '$source_id' )
                AND observe.project_id = '$projectUUID' ";
        
        echo $sql;
        
        $resRows = $db->fetchAll($sql, 2);
        $i=0;
        foreach($resRows as $row){
            $oldObs = $row["obs_num"]; // already exists at this ID
            $new_obsNum = $oldObs + $obsAdd;
        
            $where = array();
            $where[] = "obs_num  = $oldObs ";
            $where[] = "source_id  = '$source_id' ";
            $where[] = "project_id = '$projectUUID' ";
            $data = array("obs_num" => $new_obsNum);
            $db->update("observe", $data, $where);
            
            unset($where);
            unset($data);
            $where = array();
            $where[] = "origin_obs  = $oldObs ";
            $where[] = "source_id  = '$source_id' ";
            $where[] = "project_id = '$projectUUID' ";
            $data= array("origin_obs" => $new_obsNum);
            $db->update("links", $data, $where);
            
            if($metaMod){
                unset($where);
                unset($data);
                $where = array();
                $where[] = "obs_num  = $oldObs ";
                $where[] = "source_id  = '$source_id' ";
                $where[] = "project_id = '$projectUUID' ";
                $data= array("obs_num" => $new_obsNum);
                $db->update("obs_metadata", $data, $where);
            }
            
            $i++;
        }
        
        
        echo $i;    
    }//end function
    
    function fieldIntegrityAction(){
        
        $this->_helper->viewRenderer->setNoRender();
        
        $projectUUID = $_REQUEST['projectUUID'];
        $varUUID = $_REQUEST['varID'];
        
        $db = Zend_Registry::get('db');
        
        $sql="SELECT space.space_label, observe.subject_uuid, var_tab.var_label, count(observe.property_uuid) as varCount  
        FROM observe 
        JOIN space on (space.uuid = observe.subject_uuid AND observe.project_id = '$projectUUID')
        JOIN properties ON (observe.property_uuid = properties.property_uuid)
        JOIN var_tab ON properties.variable_uuid = var_tab.variable_uuid
        WHERE observe.project_id = '$projectUUID' 
        AND properties.variable_uuid = '$varUUID'
        GROUP BY observe.subject_uuid, properties.variable_uuid
        ORDER BY count(observe.property_uuid) DESC
        ";
        
        $maxLimit = 100;
        $results  = $db->fetchAll($sql);
        $i = 0;
        $problemCount = 1;
        $output = "";
        $dateLabelArray = array();
        $lastID = false;
        $numCounter = 1;
        foreach($results as $row){
            if($row["varCount"]>1){
                $problemCount++;
                $output.= "<br/>".$row["space_label"]." <em>".$row["subject_uuid"]."</em> ";
                $output.= "<a href='http://penelope.oc/preview/space?UUID=".$row["subject_uuid"]."'><strong>Count: ".$row["varCount"]."</strong></a> ";
                
                if($row["space_label"] != $lastID){
                    $numCounter = 1;
                }
                else{
                    $numCounter++;
                }
            }
            
            if($i >= $maxLimit){
                break;
            }
            
        $i++;    
        }
        
        echo $sql;
        echo "<br/>Problems found: ".$problemCount;
        echo "<br/>";
        echo $output;
        
    }
    
    
    
    //determines the item type based on its id
    private function itemTypeCheck($itemUUID){
        $db = Zend_Registry::get('db');
        
        $typeArray = array("diary"        => array("id"=>"uuid", "type"=>"Diary / Narrative"),
                           "users"          => array("id"=>"uuid", "type"=>"Person"),
                           "persons"   => array("id"=>"uuid", "type"=>"Person"),
                           "resource"     => array("id"=>"uuid", "type"=>"Media (various)"),
                           "space"        => array("id"=>"uuid", "type"=>"Locations or Objects"),
                           "properties"   => array("id"=>"property_uuid", "type"=> "Property"),
                           "var_tab"      => array("id"=>"variable_uuid", "type"=>"Variable"),
                           "project_list"   => array("id"=>"project_id", "type"=>"Project"));
        
        $found = false;
        foreach($typeArray AS $table => $typeArray){
            
            if(!$found){
                $sql = "SELECT ".$typeArray["id"]." AS id FROM ".$table." WHERE ".$typeArray["id"]." = '$itemUUID' LIMIT 1";
                $idRows = $db->fetchAll($sql, 2);
                if($idRows){
                    $found = $typeArray["type"];
                }
            }
            
        }
        
        return $found;
        
    }
    
    
    //this function adds a note to an item
    function addPropAction(){
        $this->_helper->viewRenderer->setNoRender();
        $propUUID =  $_REQUEST['propUUID'];
        $itemUUID = $_REQUEST['itemUUID'];
        $projectUUID = $_REQUEST['projectUUID'];
        if(isset($_REQUEST['itemType'])){
            $itemType = $_REQUEST['itemType'];
        }
        else{
            $itemType = $this->itemTypeCheck($itemUUID);
        }
        
        if(strlen($itemType)<1){
            $itemType = $this->itemTypeCheck($itemUUID);
        }
    
        if(!$itemType == false){
            $db = Zend_Registry::get('db');
            
            $obsHashText = md5($projectUUID . "_" . $itemUUID . "_" . "1" . "_" . $propUUID);
                        
            $data = array("project_id"=> $projectUUID,
                          "source_id"=> 'manual',
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
        
	header('Content-Type: application/json; charset=utf8');
        echo Zend_JSON::encode(array('itemUUID'=>$itemUUID, 'itemType'=>$itemType, 'propUUID'=>$propUUID));

    }
    
    
    
	
    function addVarValAction(){
        $this->_helper->viewRenderer->setNoRender();
        $variableUUID =  $_REQUEST['varUUID'];
        $itemUUID = $_REQUEST['itemUUID'];
		  $valText = $_REQUEST['valText'];
        $projectUUID = $_REQUEST['projectUUID'];
        if(isset($_REQUEST['itemType'])){
            $itemType = $_REQUEST['itemType'];
        }
        else{
            $itemType = $this->itemTypeCheck($itemUUID);
        }
        
        if(strlen($itemType)<1){
            $itemType = $this->itemTypeCheck($itemUUID);
        }
    
        if(!$itemType == false && strlen($variableUUID)>1){
            
			$changes = array();
			$db = Zend_Registry::get('db');
            
			//check to see if the item already has a property for this variable
			
			$sql = "SELECT observe.property_uuid
			FROM observe
			JOIN properties ON properties.property_uuid = observe.property_uuid
			WHERE observe.subject_uuid = '$itemUUID'
			AND properties.variable_uuid = '$variableUUID'
			LIMIT 1;
			";
			
			$oldPropUUID = false;
			$result = $db->fetchAll($sql, 2);
			if($result){
				$oldPropUUID = $result[0]["property_uuid"];
			}
			$valNum = NULL;
			if(is_numeric($valText)){
				$valNum = $valText+0;
			}
			
			$valueUUID = $this->get_make_ValID($valText, $projectUUID); //get a value uuid
			
			if(!is_numeric($valNum)){
				$valNumShow = "NULL";
			}
			else{
				$valNumShow = $valNum;
			}
			
			//so we can see and reuse the same query
			$changes[] = "INSERT IGNORE INTO val_tab (project_id, source_id, text_scram, val_text, value_uuid, val_num)
			VALUES ('$projectUUID', 'manual', '".md5($valText)."', '".addslashes($valText)."', '$valueUUID', $valNumShow); ";

			$propUUID = $this->get_make_PropID($variableUUID, $valueUUID, $projectUUID);
			//so we can reuse the query
			$propHash   = md5($projectUUID . $variableUUID . $valueUUID);
			$changes[] = "INSERT IGNORE INTO properties (project_id, source_id, prop_hash, property_uuid, variable_uuid, value_uuid, val_num)
			VALUES ('$projectUUID', 'manual', '$propHash', '$propUUID', '$variableUUID', '$valueUUID', $valNumShow); ";

            $obsHashText = md5($projectUUID . "_" . $itemUUID . "_" . "1" . "_" . $propUUID);
                        
            $data = array("project_id"=> $projectUUID,
                          "source_id"=> 'manual',
                          "hash_obs" => $obsHashText,
                          "subject_type" => $itemType,
                          "subject_uuid" => $itemUUID,
                          "obs_num" => 1,
                          "property_uuid" => $propUUID);
			
			if(!$oldPropUUID){
				try{
					$changeType = "New prop added.";
					$db->insert("observe", $data);
					
					$changes[] = "INSERT IGNORE INTO observe (project_id, source_id, hash_obs, subject_type, subject_uuid, obs_num, property_uuid)
									VALUES ('$projectUUID', 'manual', '$obsHashText', '$itemType', '$itemUUID', 1, '$propUUID'); ";
					
				} catch (Exception $e) {
					echo $e->getMessage(), "\n";
				}
			}
			else{
				//change the old prop to the new prop
				$changeType = "Old prop ($oldPropUUID) altered.";
				$where = array();
				$where[] = "subject_uuid = '$itemUUID' ";
				$where[] =  "property_uuid = '$oldPropUUID' ";
				$db->update("observe", $data, $where);
				
				$changes[] = "UPDATE observe SET property_uuid = '$propUUID' WHERE subject_uuid = '$itemUUID' AND property_uuid = '$oldPropUUID' ; ";
				
			}
			
        }
        
		if(!isset($_REQUEST["reqURI"])){
		    /*
			echo Zend_JSON::encode(array('itemUUID'=>$itemUUID,
										 'itemType'=>$itemType,
										 'propUUID'=>$propUUID,
										 'changeType' => $changeType
										 ));
			echo "<br/><br/>";
		    */
			foreach($changes as $change){
				echo "<br/><br/>".$change;
			}
			
		}
		else{
			print 'Updated '.$itemUUID.' to have <strong>'.$valText.'</strong>, redirecting...';
			header('Refresh:2 ; URL='.$_REQUEST["reqURI"]);
		}

    }
	
	
    
    //this function adds a note to an item
    function addNoteAction(){
        $this->_helper->viewRenderer->setNoRender();
        $newText =  $_REQUEST['newText'];
        $itemUUID = $_REQUEST['itemUUID'];
        $projectUUID = $_REQUEST['projectUUID'];
        if(isset($_REQUEST['source'])){
            $source = $_REQUEST['source'];
        }
        else{
            $source = "manual";
        }
		  if(isset($_REQUEST['itemType'])){
            $itemType = $_REQUEST['itemType'];
        }
        else{
            $itemType = $this->itemTypeCheck($itemUUID);
        }
        
        if(strlen($itemType)<1){
            $itemType = $this->itemTypeCheck($itemUUID);
        }
    
        $propUUID = false;
    
        if(!$itemType == false){
            $db = Zend_Registry::get('db');
            $valueUUID = $this->get_make_ValID($newText, $projectUUID, $source);
            $propUUID = $this->get_make_PropID("NOTES", $valueUUID, $projectUUID, $source);
            $obsHashText = md5($projectUUID . "_" . $itemUUID . "_" . "1" . "_" . $propUUID);
                        
            $data = array("project_id"=> $projectUUID,
                          "source_id"=> $source,
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
        
	header('Content-Type: application/json; charset=utf8');
        echo Zend_JSON::encode(array('itemUUID'=>$itemUUID, 'itemType'=>$itemType, 'propUUID'=>$propUUID));

    }
    
    
    
    //add note to a variable
    function varNoteAction(){
        
        $this->_helper->viewRenderer->setNoRender();
        //$projectUUID = $_REQUEST['projectUUID'];
        $variableUUID = $_REQUEST['variableUUID'];
        $projectUUID = $_REQUEST['projectUUID'];
        $newNote = $_REQUEST['newNote'];
        
        $db = Zend_Registry::get('db');
        $where = array();
        $where[] = "project_id  = '".$projectUUID."' ";
        $where[] = "variable_uuid  = '".$variableUUID."' ";
        $db->delete('var_notes', $where);
        
        $data = array("project_id" => $projectUUID,
                      "source_id" => "manual",
                      "variable_uuid" => $variableUUID,
                      "note_uuid" => md5($newNote),
                      "note_text" => $newNote
                      );
        
        $db->insert('var_notes', $data);
        
        echo "Done";
        
    }
    
    
    
    function deletePropAction(){
        $this->_helper->viewRenderer->setNoRender();
        //$projectUUID = $_REQUEST['projectUUID'];
        $propUUID = $_REQUEST['propertyUUID'];
        $projectUUID = $_REQUEST['projectUUID'];
        $db = Zend_Registry::get('db');
        $where = array();
        $where[] = "project_id  = '".$projectUUID."' ";
        $where[] = "property_uuid  = '".$propUUID."' ";
        $db->delete('observe', $where);
        $db->delete('properties', $where);
        echo "Done";
    }//end function
    
    
	function varCapsMergeAction(){
		
		$this->_helper->viewRenderer->setNoRender();
        $projectUUID = $_REQUEST['projectUUID'];
		$varUUID = $_REQUEST['varUUID'];
		
		$db = Zend_Registry::get('db');
		
		$output = array();
		$varArray = array();
		
		$urlPrefix = "http://".$_SERVER['SERVER_NAME']."/edit-dataset/global-prop-merge";
		
		if($varUUID == "all"){
			
			$sql = "SELECT DISTINCT variable_uuid, var_label
			FROM var_tab
			WHERE project_id = '$projectUUID'
			AND var_type = 'Nominal' OR var_type = 'Ordinal' OR  var_type = 'Boolean'
			";
			
			$varRes = $db->fetchAll($sql);
			foreach($varRes as $varRow){
				$varArray[] = $varRow["variable_uuid"];
			}
			
		}
		else{
			$varArray[] = $varUUID;
		}
		
		
		foreach($varArray as $variableUUID){
			
			$textArray = array();
			
			
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
				WHERE properties.variable_uuid = '$variableUUID'
				GROUP BY observe.property_uuid
				ORDER BY count(observe.subject_uuid) DESC
					";
		
			$results =  $db->fetchAll($sql);
			foreach($results as $row){
				
				$output[$variableUUID]["label"] = $row["var_label"];
				$propUUID = $row["property_uuid"];
				$actText = $row["val_text"];
				$subCount = $row["subCount"];
				
				$lowerText = strtolower($actText);
				
				if(!array_key_exists($lowerText, $textArray)){
					$textArray[$lowerText] = array("keepProp" => $propUUID, "goodCount" => $subCount, "bestText" => $actText);
				}
				else{
					$keepProp = $textArray[$lowerText]["keepProp"];
					
					$url = $urlPrefix."?KeepPropertyUUID=".$keepProp."&OldPropertyUUID=".$propUUID;
					@$change = file_get_contents($url );
					if($change){
						$changeObj = Zend_JSON::decode($change);
					}
					else{
						$changeObj = false;
					}
					
					$output[$variableUUID]["actions"][] = array("bad" => array("OldPropertyUUID" => $propUUID, "BadCount" => $subCount, "badText" => $actText),
													 "good" => $textArray[$lowerText],
													 "change" => $changeObj,
													 "url" => $url
													 );
				}
			}
		
			unset($results);
			unset($textArray);
		}//end loop through variables
		
		header('Content-Type: application/json; charset=utf8');
		echo Zend_JSON::encode($output);
	}
	
	
	function globalPropChangeAction(){
        
        $this->_helper->viewRenderer->setNoRender();
        $projectUUID = $_REQUEST['projectUUID'];
        $propUUID = $_REQUEST['propertyUUID'];
        $newText =  $_REQUEST['newText'];
        //$Append =  $_REQUEST['append'];
        //$newText = addslashes($newText);
        
        if(isset($_REQUEST['append'])){
            $append = true;
        }
        else{
            $append = false;
        }
        
        $propComps = $this->propID_Components($propUUID); //get the variable and value id
        
        if($append){
            $newText = $propComps["valText"].$newText;
        }
        
        $altValID = $this->alter_ValID($newText, $propComps["valueUUID"], $propComps["projectUUID"]); //update value id for new text
        $altPropID = $this->alter_propID_valID($propComps["valueUUID"], $altValID, $propComps["variableUUID"], $propUUID, $propComps["projectUUID"]); //update propids
        $altPropComps = $this->propID_Components($altPropID);
    
	
	
		$db = Zend_Registry::get('db');
		if($newText != $altPropComps["valText"]){
			
			//can't just change the value, since it's used elsewhere. switch to an existing value id
			$newValUUID = $this->get_make_ValID($newText, $projectUUID);
			$data = array("value_uuid" => $newValUUID);
			$where = "property_uuid = '$propUUID' ";
			$db->update("properties", $data, $where);
			$altPropComps = $this->propID_Components($propUUID);
		}
	
	
	
		//change var totals
		$data = array("var_sum" => "");
		$where = "variable_uuid = '".$propComps["variableUUID"]."' ";
		$db->update("var_tab", $data, $where);
		
		$output = array();	
		$output["oldPropComponents"] = $propComps;
		$output["newPropComponents"] = $altPropComps;
			
		//remove properties and subjects from published list
		$sql = "SELECT DISTINCT subject_uuid as itemUUID
		FROM observe
		WHERE property_uuid = '$propUUID'
		
		UNION
		
		SELECT DISTINCT  links.targ_uuid as itemUUID
		FROM observe
		JOIN links ON observe.subject_uuid = links.origin_uuid
		WHERE observe.property_uuid = '$propUUID'
		
		
		
		UNION
		
		SELECT property_uuid as itemUUID
		FROM properties
		WHERE variable_uuid = '".$propComps["variableUUID"]."' 
		";
		
		$result = $db->fetchAll($sql, 2);
		$output["num_Props_Subs_Changed"] = count($result);
		foreach($result as $row){
			
			$itemUUID = $row["itemUUID"];
			$where = "item_uuid = '$itemUUID' ";
			$db->delete("published_docs", $where);
			
		}
		
		header('Content-Type: application/json; charset=utf8');
		echo Zend_JSON::encode($output);

    }
    
	
	
	function globalPropMergeAction(){
        
		$output = array();
        $this->_helper->viewRenderer->setNoRender();
        //$projectUUID = $_REQUEST['projectUUID'];
        $OldPropUUID = $_REQUEST['OldPropertyUUID'];
        $KeepPropUUID =  $_REQUEST['KeepPropertyUUID'];
        
        $propComps = $this->propID_Components($OldPropUUID); //get the variable and value id
        $newPropComps = $this->propID_Components($KeepPropUUID);
		
		if(($OldPropUUID != $KeepPropUUID) && (strlen($OldPropUUID)>4 && strlen($KeepPropUUID)>4 )) {
			$db = Zend_Registry::get('db');
			
			//change var totals
			$data = array("var_sum" => "");
			$where = "variable_uuid = '".$propComps["variableUUID"]."' ";
			$db->update("var_tab", $data, $where);
			
			$output["oldPropComponents"] = $propComps;
			$output["keepPropComponents"] = $newPropComps;
			
			//remove properties and subjects from published list
			$sql = "SELECT DISTINCT subject_uuid as itemUUID
			FROM observe
			WHERE property_uuid = '$OldPropUUID'
			
			UNION
			
			SELECT DISTINCT  links.targ_uuid as itemUUID
			FROM observe
			JOIN links ON observe.subject_uuid = links.origin_uuid
			WHERE observe.property_uuid = '$OldPropUUID'
			
			
			UNION
			
			SELECT property_uuid as itemUUID
			FROM properties
			WHERE variable_uuid = '".$propComps["variableUUID"]."' 
			";
			
			$result = $db->fetchAll($sql, 2);
			$output["num_Props_Subs_Changed"] = count($result);
			foreach($result as $row){
				
				$itemUUID = $row["itemUUID"];
				$where = "item_uuid = '$itemUUID' ";
				$db->delete("published_docs", $where);
				
			}
			
			
			//now update the subjects
			$data = array("property_uuid" => $KeepPropUUID);
			$where = "property_uuid = '$OldPropUUID' ";
			$numSubUpdates = $db->update("observe", $data, $where);
			
			//now delete the old offending property
			$db->delete("properties", $where);
		}
		
		header('Content-Type: application/json; charset=utf8');
		echo Zend_JSON::encode($output);
    }
	
	
	function varLinkLabelAction(){
        
		  $output = array("done" => true);
		  $this->_helper->viewRenderer->setNoRender();
        $projectUUID = $_REQUEST['projectUUID'];
		  $varUUID = $_REQUEST['varUUID'];
		  $relType = $_REQUEST['relType'];  //type of linking relation to annotate, contain or link
		  
		  $data = array("project_id" => $projectUUID,
							 "labelVarUUID" => $varUUID,
							 "relType" => $relType
							 );
		  
		  $classUUID = "";
        if(isset($_REQUEST['classUUID'])){
				if(strlen($_REQUEST['classUUID'])>0){
					 $data["classUUID"] = $_REQUEST['classUUID'];
					 $classUUID = $_REQUEST['classUUID'];
				}
		  }
		  
		  $docType = "";
		  if(isset($_REQUEST['docType'])){
				if(strlen($_REQUEST['docType'])>0){
					 $data["doc_type"] = $_REQUEST['docType'];
					 $docType = $_REQUEST['docType'];
				}
		  }

		  //tab_name
		  $tabName = "";
		  if(isset($_REQUEST['tabName'])){
				if(strlen($_REQUEST['tabName'])>0){
					 $data["tab_name"] = $_REQUEST['source_id'];
					 $tabName = $_REQUEST['source_id'];
				}
		  }
		  
		  
		  $db = Zend_Registry::get('db');
		  $hashID = md5($projectUUID.$relType.$varUUID.$classUUID.$docType.$tabName );
		  $data["hashID"] = $hashID;
		  $where = "hashID = '".$hashID."' ";
		  $db->delete("labeling_options", $where);
		  $db->insert("labeling_options", $data);
		
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_JSON::encode($output);
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
    
    //this function changes text associated with a given valueid
    //it returns a different valueid if the text already exists in a project
    private function alter_ValID($valNewText, $oldValID, $projectUUID, $dataTableName = 'manual'){
        
        $db = Zend_Registry::get('db');
        
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
    private function alter_propID_valID($oldValID, $newValID, $variableUUID, $propUUID, $projectUUID, $subjectUUID = false){
        
        $db = Zend_Registry::get('db');
    
        if($oldValID != $newValID){
            
            $CheckPropUUID = $this->propID_VarVal($variableUUID, $newValID, $projectUUID);
            
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
    
    
    function formMergeVarsAction(){
        
        $this->_helper->viewRenderer->setNoRender();
        
        $output = "<p>Merge Variables</p><p>";
        $output .= "<form action='merge-vars' method='get'>";
        $output .= "Old (remove) Variable ID: <input type='text' name='oldvarUUID' />, ";
        $output .= "New (keep) Variable ID: <input type='text' name='newvarUUID' />";
        $output .= "<input type='submit' value='Submit' />";
        $output .= "</form>";
        $output .= "</p>";
        echo $output;
        
    }
    
    
    
    //
    function mergeVarsAction(){
        
         $this->_helper->viewRenderer->setNoRender();
        
	//$projectUUID = $_REQUEST['projectUUID'];
        $OldVariableUUID = $_REQUEST['oldvarUUID'];
        $NewVariableUUID =  $_REQUEST['newvarUUID'];
        
        $db = Zend_Registry::get('db');
        
        $sql = "SELECT var_tab.var_label
        FROM var_tab
        WHERE var_tab.variable_uuid = '$OldVariableUUID'
        ";
        
        $varRows = $db->fetchAll($sql, 2);
        if($varRows){
            $oldVarLabel = $varRows[0]["var_label"];
            $oldVar = true;
        }
        else{
            $oldVar = false;
        }
        
        unset($varRows);
        
        $sql = "SELECT var_tab.var_label
        FROM var_tab
        WHERE var_tab.variable_uuid = '$NewVariableUUID'
        ";
        
        $varRows = $db->fetchAll($sql, 2);
        if($varRows){
            $newVarLabel = $varRows[0]["var_label"];
            $newVar = true;
        }
        else{
            $newVar = false;
        }
        
        
        //if($oldVar && $newVar){
        if($newVar){
            $sql = "SELECT properties.property_uuid, properties.project_id
            FROM properties
            WHERE properties.variable_uuid = '$OldVariableUUID' ";
            
            $propRows = $db->fetchAll($sql, 2);
            
            $newProps = 0;
            foreach($propRows as $actProp){
                $propUUID = $actProp["property_uuid"];
                $projectUUID = $actProp["project_id"];
                $newPropID = $this->alter_propID_varID($OldVariableUUID, $NewVariableUUID, $propUUID, $projectUUID);
            
                if($newPropID != $propUUID){
                    $newProps++;
                }
            }
            
            $output = array("oldVar"=>$oldVarLabel, "newVar"=>$newVarLabel, "numProps"=> count($propRows), "altProps"=> $newProps );
            
            if($NewVariableUUID != $OldVariableUUID){
                $where = array();
                $where[] = "variable_uuid = '$OldVariableUUID' ";
                $where[] = "project_id = '$projectUUID' ";
                $db->delete("var_tab", $where);                
            }
            echo Zend_JSON::encode($output);    
        }
        else{
            echo "error";
        }
        
    }
    
    
    
    //this function changes a the value ID associated with a prop id
    private function alter_propID_varID($OldVariableUUID, $NewVariableUUID, $propUUID, $projectUUID, $subjectUUID = false){
        
        $db = Zend_Registry::get('db');
    
        if($OldVariableUUID != $NewVariableUUID){
            
            $propParts = $this->propID_Components($propUUID);
            $valueUUID = $propParts["valueUUID"]; 
            
            $CheckPropUUID = $this->propID_VarVal($NewVariableUUID, $valueUUID, $projectUUID);
            
            if(!$CheckPropUUID){
                //the new variable id / value id pair does not exist.
                //set the existing prop id to have the new variable uuid
                $AlterPropHash = md5($projectUUID . $NewVariableUUID . $valueUUID); 
                $where = array();
                $where[] = " property_uuid = '$propUUID' ";
                $where[] = " project_id = '$projectUUID' ";
                
                $data = array("prop_hash" => $AlterPropHash,
                              "variable_uuid" => $NewVariableUUID);
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
                foreach($obsRows as $actObs){
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
                    
                    try {
                        $db->update("observe", $data, $where);
                    } catch (Exception $e) {
                        
                    }

                }
                
                unset($where);
                $where = array();
                $where[] = "property_uuid = '$propUUID' ";
                $where[] = "project_id = '$projectUUID' ";
                $db->delete("properties", $where);
                
                return $CheckPropUUID;
            }
        }
        else{
            return $propUUID;
        }
        
    }//end function
    
    
    
    
    
    
    
    
    
    
    
    
    
}