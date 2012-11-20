<?php
class SpatialContainment_NewSpaceContain{
   
   const pathDelimiter = "|xx|";
   public $db; //database object
   
   public $projectUUID; //idof active project
   public $dataTableName; //name of active data table
   
   //for storing general information about fields
   public $doingContain; //true (importer is processing fields with contain relations) or false (processing fields without cointain relations)
   public $rankedContain; //array of field containment relations $key is parent field => $value is child field
   public $countContainFields; // count of the number of containment relations
   
   public $noContainSpatial; //array of fields NOT in spatial containment relations
   public $countNoContainSpatial; //cound of number of fields without containment relations 
   public $currentFieldSettings; //settings for the current field, needed for making queries for entities in non-contain relations 

   public $projRootContext; //name of the project root context
   public $projRootID; //ID of the project root context

   public $startNum; //query limit start
   public $endNum; //query limit end
   
   //get data about location and object fields
   public function space_contain_setup(){
    $db = Zend_Registry::get('db');
    $this->db = $db;
    
    $this->get_contain_fields(); // find fields in containment relations
    $this->get_noContain_spatial_fields(); // find other location and object fields
   }
   
   public function get_noContain_spatial_fields(){
    $db = $this->db;
    $db = $this->db;
    $dataTableName = $this->dataTableName;
    $projectUUID = $this->projectUUID;
    
    $sql = "SELECT field_name
    FROM field_summary
    WHERE source_id = '".$dataTableName."'
    AND field_type LIKE 'Locations%'
    ";
    
    $noContainFields = array();
    $rankedContain = $this->rankedContain;
    $result = $db->fetchAll($sql, 2);
    foreach($result as $row){
        $field = $row["field_name"];
        if((!array_key_exists($field, $rankedContain)) && !(in_array($field, $rankedContain))){
            $noContainFields[] = $field;
        }
    }//end loop
    
    $this->noContainSpatial = $noContainFields;
    $this->countNoContainSpatial = count($noContainFields);
    
   }
   
   
   //find and rank order of fields with spatial containment relations
   public function get_contain_fields(){
     
    $db = $this->db;
    $sql = "SELECT
    par.fk_field_child AS parent_parent,
    field_links.fk_field_parent,
    field_links.fk_field_child,
    field_links.field_parent_name,
    field_links.field_child_name
    FROM field_links
    LEFT JOIN field_links AS par ON (par.fk_field_child = field_links.fk_field_parent
                                    AND field_links.source_id = par.source_id)
    WHERE field_links.source_id = '".$this->dataTableName."'
    AND field_links.fk_link_type = 1
    ";
    
    $result = $db->fetchAll($sql, 2);
    
    $rankedContain = array();
    foreach($result as $row){
        $parentParent = $row["parent_parent"];
        $fkParent = $row["fk_field_parent"];
        $fkChild = $row["fk_field_child"];
        $fieldParent = $row["field_parent_name"];
        $fieldChild = $row["field_child_name"];
        if($parentParent == null){
            $rankedContain["root"] = $fieldParent;   
        }
        else{
            $rankedContain[$fieldParent] = $fieldChild;
        }
        
    }//end loop

    $this->rankedContain = $rankedContain;
    $this->countContainFields = count($rankedContain);
   }//end function
   
   
   
   
   
   //get project root context information
   function get_project_root(){
    $projectUUID = $this->projectUUID;
    $db = $this->db;
    $sql = "SELECT *
    FROM project_list
    WHERE project_id = '".$projectUUID."'
    LIMIT 1
    ";
    
    $result = $db->fetchAll($sql, 2);
    $projRootContext = trim($result[0]["parcontext_name"]);
    $projRootID = trim($result[0]["parcontext_id"]);
    
    if(strlen($projRootContext) >= 1 && strlen($projRootID) >= 1 ){
        $this->projRootContext = $projRootContext;
        $this->projRootID = $projRootID;
    }
    else{
        $this->projRootContext = false;
        $this->projRootID = false;
    }
    
   }//end function
   
   
   
   function process_all_spatial($batch){
        $batchSize = 250;
        $startNum = ($batchSize * $batch) + 1;
        $endNum = $batch * $batchSize;
    
        $this->startNum = $startNum;
        $this->endNum = $endNum;
    
        $this->get_project_root();
        $db = $this->db;
    
        if($this->countContainFields > 0){
            //containment relations defined for the table. By default, new spatial items can be minted
            //during import
            $this->doingContain = true;
            //$newItems = $this->process_contained_spatial();
        }
        
        if($this->countNoContainSpatial > 0){
            //some location /object fields have no spatial containment relations
            //by default, no new spatial ids get minted, instead, only attempt to identify items based on previous imports 
            $this->doingContain = false;
            //$idItems = $this->process_noContain_spatial();
        }
        
   }
   
   
   /*
   
   //process fields that are not in spatial containment relations
   function process_noContain_spatial(){
    
    $db = $this->db;
    $startNum = $this->startNum;
    $endNum = $this->endNum;
    $dataTableName = $this->dataTableName;
    $noContainFields = $this->noContainSpatial;
    
    foreach($noContainFields as $currentField){
        
        $fieldSettings = $this->get_field_settings($currentField);
        $this->currentFieldSettings = $fieldSettings;
        
        $sql = "SELECT id, $currentField AS curField
            FROM $dataTableName
            WHERE ($dataTableName.id >= $startNum AND $dataTableName.id <= $endNum)
            ORDER BY curField, id
            ";
        
        $rawItemArray = array();
        $lastRawItem = "_bwelqjwlqwj_203948-09810491049skfjlnlkalkjdljae__blubbie";
    
        foreach($result as $row){
            $id = $row["id"];
            $rawItem = $row["curField"];
            $itemHash = md5($rawItem);
            
            if($lastRawItem  == $rawItem){
                $actID_array = $rawItemArray[$itemHash]["ids"];
                $actID_array[] = $id;
                $rawItemArray[$itemHash]["ids"] = $actID_array;
            }
            else{
                
                $lastRawItem  = $rawItem;
                $actID_array = array();
                $actID_array[] = $id;
                $labeled_item = trim($rawItem);
                if($fieldPrefix != false && strlen($labeled_item)>0 ){
                    if(!stristr($labeled_item, $fieldPrefix)){
                        //only add the prefix if it is not already in the item label
                        $labeled_item = $fieldPrefix." ".$labeled_item;
                    }
                }
                
                if(strlen($labeled_item)<1){
                    $labeled_item = false;
                    $spaceUUID = false;
                }
                else{
                    $spaceItem = $this->get_make_uuid($labeled_item, $fieldSettings, $parentUUID, $parentArray); //get or make new uuid
                    $spaceUUID = $spaceItem["spaceUUID"];
                    $newItems[] = $spaceItem;
                }
                
                $rawItemArray[$itemHash] = array("rawItem" => $rawItem,
                                                 "labeledItem" => $labeled_item,
                                                 "uuid" => $spaceUUID,
                                                 "ids" => $actID_array);
            }
        }//end loop    
    
        if($fieldSettings["linked"]){
        //this field is linked by other fields. it needs to populate the lookup table
            foreach($rawItemArray as $itemArray){
                $spaceUUID = $itemArray["uuid"];
                $rowIDArray = $itemArray["ids"];
                $this->add_lookup_fromArray($spaceUUID, $rowIDArray, $fieldSettings);
            }
        }
        
    }//end loop through fields
    
   }//end function
   
   
   //this function breaks apart a raw item from non-containment fields and
   //returns an array of the item 
   function parse_multi_item($rawItem){

      if($rawItem != null){
         $delimiters = array(",",
                            "&",
                            ";",
                            " ",
                            "and");
        
        $itemArray = array();
        $itemArray[] = $rawItem;
         foreach($delimiters as $delim){
            $newItems = array();
            foreach($itemArray as $actItem){
               if(stristr($actItem, $delim)){
                  $exploded = explode($delim, $actItem);
                  foreach($exploded as $actExp){
                     $actItem = trim($actItem);
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
    else{
        $output = false;
    }
    
    $output = $rawItem;
    return $output;
   }//end function
   
   
   
   
   
   
   
   
   
   function process_contained_spatial(){
    
    $db = $this->db;
    $startNum = $this->startNum;
    $endNum = $this->endNum;
    $dataTableName = $this->dataTableName;
    
    $rankedContain = $this->rankedContain;
    $currentField = $rankedContain["root"];
    
    $fieldSettings = $this->get_field_settings($rootParent);
    $fieldPrefix = $fieldSettings["prefix"];
    
    
    $newItems = array(); //output array of spatial items
    
    $childFieldBelow = false;
    $parentArray = array();
    $parentUUID = $this->projRootID;
    $parentArray[] = array("field" => false, "rawItem" => false, "labeledItem" => $this->projRootContext, "uuid" => $this->projRootID);
    
    $newItems = array();
    $newItems = $this->process_contain_fields($newItems, $parentUUID, $currentField, $parentArray);
    
    return $newItems;
    
   }//end function
   
   
   
   
   //this function is a recursive function that gets extracts spatial entities from child fields
   //it queries the imported data table. Parent fields and values are query conditions so that
   //spatial containment paths are used in entity identification (allowing for differetition between Bone 1 in Locus 1, and Bone 1 in Locus 2)
   function process_contain_fields($newItems, $parentUUID, $currentField, $parentArray){
    $rankedContain = $this->rankedContain;
    
    $db = $this->db;
    $dataTableName = $this->dataTableName;
    $projectUUID = $this->projectUUID;
    $this->startNum = $startNum;
    $this->endNum = $endNum;
    
    $fieldSettings = $this->get_field_settings($currentField);
    $fieldPrefix = $fieldSettings["prefix"];
    
    $parentCondition = "";
    if(is_array($parentArray)){
        $firstLoop = true;
        foreach($parentArray as $parentItem){
            
            //don't make query terms for project root level (field = false) items
            if($parentItem["field"] != false){
                $term = " ".$dataTableName.".".$parentItem["field"]." = '".$parentItem["rawItem"]."' ";
                
                if($firstLoop){
                    $parentCondition = $term;
                    $firstLoop = false;
                }
                else{
                    $parentCondition .= " AND ".$term;
                }
            }
        }
        $parentCondition = "AND (".$parentCondition.") ";
    }
    else{
        $parentArray = array();
    }
   
   
    $childFieldBelow = false;
    if(array_key_exists($currentField, $rankedContain)){
        $childFieldBelow = $rankedContain[$currentField];
    }
   
   
    $sql = "SELECT id, $currentField AS curField
    FROM $dataTableName
    WHERE ($dataTableName.id >= $startNum AND $dataTableName.id <= $endNum)
    $parentCondition
    ORDER BY curField, id
    ";
   
    $rawItemArray = array();
    $lastRawItem = "_bwelqjwlqwj_203948-09810491049skfjlnlkalkjdljae__blubbie";
    
    foreach($result as $row){
        $id = $row["id"];
        $rawItem = $row["curField"];
        $itemHash = md5($rawItem);
        
        if($lastRawItem  == $rawItem){
            $actID_array = $rawItemArray[$itemHash]["ids"];
            $actID_array[] = $id;
            $rawItemArray[$itemHash]["ids"] = $actID_array;
        }
        else{
            $lastRawItem  = $rawItem;
            $actID_array = array();
            $actID_array[] = $id;
            $labeled_item = trim($rawItem);
            if($fieldPrefix != false && strlen($labeled_item)>0 ){
                if(!stristr($labeled_item, $fieldPrefix)){
                    //only add the prefix if it is not already in the item label
                    $labeled_item = $fieldPrefix." ".$labeled_item;
                }
            }
            
            $spaceItem = false;
            if(strlen($labeled_item)<1){
                $labeled_item = false;
                $spaceUUID = false;
            }
            else{
                $spaceItem = $this->get_make_uuid($labeled_item, $fieldSettings, $parentUUID, $parentArray); //get or make new uuid
               
                if(!$spaceItem){
                    //entity not found 
                    $newItems[] = array("spaceUUID" => false, "path" => "No path: ".$labeled_item, "newMinted" => "Entity not found");
                else{
                    $spaceUUID = $spaceItem["spaceUUID"];
                    $newItems[] = $spaceItem;
                }
            }
            
            if(!$spaceItem){
                //entity not found, add record of failure to database for future trouble shooting
               $this->no_entity_found($rawItem, $labeled_item, $id, $fieldSettings);
            }
            else{
            //entity found 
                $rawItemArray[$itemHash] = array("rawItem" => $rawItem,
                                             "labeledItem" => $labeled_item,
                                             "uuid" => $spaceUUID,
                                             "ids" => $actID_array);
            }
            
        }
    }    
    
    if($fieldSettings["linked"]){
    //this field is linked by other fields. it needs to populate the lookup table
        foreach($rawItemArray as $itemArray){
            $spaceUUID = $itemArray["uuid"];
            $rowIDArray = $itemArray["ids"];
            $this->add_lookup_fromArray($spaceUUID, $rowIDArray, $fieldSettings);
        }
    }
    
    if($childFieldBelow != false){
        foreach($rawItemArray as $itemArray){
            $spaceUUID = $itemArray["uuid"];
            $rawItem = $itemArray["rawItem"];
            $labeled_item = $itemArray["labeledItem"];
            $nextParentArray = $parentArray;
            $nextParentArray[] = array("field" => $currentField, "rawItem" => $rawItem, "labeledItem" => $labeled_item, "uuid" => $spaceUUID);
            if($spaceUUID != false){
                $nextParentUUID = $spaceUUID; //parent of children item is the current spatial item
            }
            else{
                $nextParentUUID = $parentUUID; //since their is no spatial item at this level, use the parent item as parent of the child below
            }
            
            $newItems = $this->process_contain_fields($newItems, $nextParentUUID, $childFieldBelow, $nextParentArray);
        }
    }//end case with children items
    
    
    return $newItems;
    
   }//end function
   
   
   
   
   //this function adds spaital units to the lookup table if they are needed
   //by linking relations 
   function add_lookup_fromArray($spaceUUID, $rowIDArray, $fieldSettings){
        $db = $this->db;
        $dataTableName = $this->dataTableName;
        $projectUUID = $this->projectUUID;
        $this->startNum = $startNum;
        $this->endNum = $endNum;
        
        $spaceField = $fieldSettings["name"];
        $spaceFieldNum = $fieldSettings["field_num"];
        
        foreach($rowIDArray as $rowNumber){            
            $data = array(
                    'source_id'          => $dataTableName,
                    'uuid'        => $spaceUUID, 
                    'field_num'         => $spaceFieldNum,
                    'row_num'           => $rowNumber
                );
            
            $db->insert("space_lookup", $data);    
            
        }//end loop

   }//end function
   
   
   
   
   
   
   
   
   //this function adds spaital units to the lookup table if they are needed
   //by linking relations 
   function add_to_lookup($spaceUUID, $spaceRawValue, $fieldSettings, $parentCondition = ""){
    
    if($fieldSettings["linked"]){
        $db = $this->db;
        $dataTableName = $this->dataTableName;
        $projectUUID = $this->projectUUID;
        $this->startNum = $startNum;
        $this->endNum = $endNum;
        
        
        $spaceField = $fieldSettings["name"];
        $spaceFieldNum = $fieldSettings["field_num"];
        
        //first do some cleanup and delete to make sure we're inserting unique data
        $where = array();
        $where[] = "source_id = '$dataTableName' ";
        $where[] = "uuid = '$spaceUUID' ";
        $where[] = "field_num = $spaceFieldNum ";
        $where[] = "row_num >=  $startNum ";
        $where[] = "row_num <=  $endNum ";
        $db->delete("space_lookup", $where); 
        
        //now query the imported table to get row IDs for the current spacial item
        $sql = "SELECT $dataTableName.id
        FROM $dataTableName
        WHERE $dataTableName.$spaceField = '$spaceRawValue'
        AND ($dataTableName.id >= $startNum AND $dataTableName.id <= $endNum)
        $parentCondition
        ";
        
        $result = $db->fetchAll($sql, 2);
        foreach($result as $row){
            
            $rowNumber = $row["id"];
            
            $data = array(
                    'source_id'          => $dataTableName,
                    'uuid'        => $spaceUUID, 
                    'field_num'         => $spaceFieldNum,
                    'row_num'           => $rowNumber
                );
            $db->insert("space_lookup", $data);    
            
            
        }//end loop
    }//end case where lookup is needed

   }//end function
   
   
   
   
   
   
   
   
   
   //this function gets the label prefix (if a prefix exists)
   //the class, notes, and if the field is linked to properties or other fields
   function get_field_settings($field){
    $db = $this->db;
    $dataTableName = $this->dataTableName;
    $sql = "SELECT field_lab_com,
                fk_class_uuid,
                field_notes,
                field_num
            FROM field_summary
            WHERE field_name = '".$field."'
            AND source_id = '".$dataTableName."'
            LIMIT 1; ";
   
    $result = $db->fetchAll($sql, 2);
    $prefix = trim($result[0]["field_lab_com"]);
    $field_notes = trim($results[0]["field_notes"]);
    $classUUID = trim($result[0]["fk_class_uuid"]);
    
    $field_num = $result[0]["field_num"];
    
    //check to see if the current field 
    $sql = "SELECT field_name AS link_field
    FROM field_summary
    WHERE fk_field_describes = $field_num AND source_id = '".$dataTableName."'
    UNION
    SELECT field_child_name AS link_field
    FROM field_links
    WHERE (fk_field_parent = $field_num OR fk_field_child = $field_num)
    AND source_id = '".$dataTableName."'
    AND fk_link_type != 1
    ";
    
    $linkedField = false;
    $result2 = $db->fetchAll($sql, 2);
    if($result2){
        if(count($result2)>0){
            $linkedField = true;
        }
    }
    
    
    if(strlen($prefix)<1){
        $prefix = false;
    }
    elseif(stristr($prefix, "click to edit")){
        $prefix = false;
    }
    
    //check to see if the field has associated notes with it
    if(strlen($field_notes)<1){
        $notePropID = false;
    }
    else{
        $valueUUID  = $this->addNotesValueToTable($projectUUID, $dataTableName, $childFieldNotes);
        $notePropID = $this->addPropertyToTable($projectUUID, $dataTableName, $valueUUID, 'NOTES', null);
    }
   
   
    return array("field_num" => $field_num,
                 "name" => $field,
                 "prefix" => $prefix,
                 "class" => $classUUID,
                 "notePropID" => $notePropID,
                 "linked" => $linkedField);
   }//end function 
   

    
   
   
   
   
   //this function looks up the UUID of a spatial unit. If a UUID does not exist, a new one is minted
   function get_make_uuid($labeled_item, $fieldSettings, $parentUUID = false, $parentArray = false){
    
    $db = $this->db;
    $dataTableName = $this->dataTableName;
    $projectUUID = $this->projectUUID;
   
    $noEntityNoMint = false;
    $sql = $this->build_entity_check_query($labeled_item, $parentArray, $fieldSettings);
   
    $result = $db->fetchAll($sql, 2);
    if($result){
        //item exists, no need to mint a new item
        $spaceUUID = $result[0]["uuid"];
        $this->add_observation($spaceUUID, $fieldSettings["notePropID"]); // add note observation, if their is a note
        $newMinted = false;
    }
    elseif($this->doingContain && $parentUUID != false){
        //item does not exist, create a new item
        $newMinted = true;
        $spaceUUID = GenericFunctions::generateUUID(); //mint new UUID
        $itemClass = $fieldSettings["class"]; //get class
        
        $data = array(
                    'project_id'   => $projectUUID,
                    'source_id'          => $dataTableName,
                    'hash_fcntxt'       => $hashContext,                    // md5($projectUUID . "_" . $fullContextCurrent); 
                    'uuid'        => $spaceUUID,                 // generated from uuID function    
                    'space_label'       => $labeled_item,                 // Bone# 263       
                    'full_context'      => $fullContextCurrent,//$fullContextCurrent,                // AM95|xx|Area E-1|xx|Locus 103|xx|1016|xx|Bone# 263 
                    'class_uuid'        => $itemClass             // field_summary.class_uuid
                 );
        
        $db->insert("space", $data);
        
        $this->add_containment_record($parentUUID, $spaceUUID); //add a containment record
        $this->add_observation($spaceUUID, $fieldSettings["notePropID"]); // add note observation, if there is a note
        
    }//end case for making a new spatial item, when doing fields in containment relations
    else{
        $noEntityNoMint = true; //not enough context to mint a new entity, but no matches found.
    } 
    
    if($noEntityNoMint){
        $output = false;    //not enough context to mint a new entity, but no matches found. 
    }
    else{
        $output = array("spaceUUID" => $spaceUUID, "path" => $fullContextCurrent, "newMinted" => $newMinted);
    }
    
    return $output;
   }//end function
   
   
   
   
   //this function generates the appropriate SQL query to check the space table to see
   //if the current item has already been imported and identified.
   function build_entity_check_query($labeled_item, $parentArray, $fieldSettings){
    
    $projectUUID = $this->projectUUID;
    
    
    if($this->doingContain && $this->countContainFields > 1){
        //cases where there are more 2 fields in a containment hierarchy
        //this is the ideal case, since we have full context paths to match spatial items
        
        $fullContextCurrent = $this->make_context_path($labeled_item, $parentArray);
        $hashContext    = md5($projectUUID . "_" . $fullContextCurrent);
       
        $sql = "SELECT uuid
        FROM space
        WHERE (hash_fcntxt = '".$hashContext."' 
        )
        AND project_id = '".$projectUUID."'
        LIMIT 1;
        ";
    }
    elseif($this->doingContain && $this->countContainFields == 1){
        
        $partContextCurrent = $this->make_context_path($labeled_item, $parentArray);
        $pathDelimiter = self::pathDelimiter;
        $projRootContext = $this->projRootContext;
        if($projRootContext != false){
            $partContextCurrent = str_replace($partContextCurrent.$pathDelimiter, "", $partContextCurrent);
        }
        
        
        $sql = "SELECT uuid
        FROM space
        WHERE (full_context LIKE '%".$partContextCurrent."' 
        AND space_label = '".$labeled_item."'
        )
        AND project_id = '".$projectUUID."'
        LIMIT 1;
        ";   
    }
    elseif(!$this->doingContain){
        
        $classUUID = $fieldSettings["class"];
        
        $sql = "SELECT uuid
        FROM space
        WHERE (class_uuid = '".$classUUID."' 
        AND space_label = '".$labeled_item."'
        )
        AND project_id = '".$projectUUID."'
        LIMIT 1;
        ";  
        
    }
    
    return $sql;
   }//end function
   
   
   //add data to error table, so records of entities not matched to previous
   //imports can be saved and troubleshooted later
   function no_entity_found($rawItem, $labeled_item, $id, $fieldSettings){
    $db = $this->db;
    $dataTableName = $this->dataTableName;
    $projectUUID = $this->projectUUID;
    
    if(!$this->doingContain){
        $note = "Field not in containment relation, cannot find clear match for entity";
    }
    elseif($this->countContainFields == 1){
        $note = "Field not in containment relation, cannot find clear match for entity";
    }
    
    $tab_field_row_key = md5($dataTableName."_".$fieldSettings["field_num"]."_".$id);
    $data = array(  'tab_field_row_key' => $tab_field_row_key,
                    'project_id'   => $projectUUID,
                    'source_id'          => $dataTableName,
                    'type'              => 'Locations or Objects',                    
                    'field'             => $fieldSettings["field_num"],                
                    'row'               => $id,                    
                    'raw_value'         => $rawItem,
                    'final_value'       => $labeled_item,
                    'note'              => $note
                 );
    try{    
        $db->insert("w_missing_entities", $data);
    catch(Exception $e){
            //this entity already noted as missing
    }
    
   }
   
   
   
   
   //add obseration on spatial item
   function add_observation($spaceUUID, $propUUID, $obsNum = 1){
    $db = $this->db;
    $dataTableName = $this->dataTableName;
    $projectUUID = $this->projectUUID;
    
    if($propUUID != false){
        try{
            $obsHashText = md5($projectUUID . "_" . $spaceUUID . "_1_" . $propUUID);
            $data = array(
                                'project_id'   => $projectUUID,
                                'source_id'          => $dataTableName,
                                'hash_obs'          => $obsHashText, 
                                'subject_type'      => 'Locations or Objects',
                                'subject_uuid'      => $spaceUUID,
                                'obs_num'           => $obsNum,
                                'property_uuid'     => $propUUID
                             );
            
            $db->insert("observe", $data);
        }
        catch(Exception $e){
            //this observation relation already exists
        }
    }
    
   }//end function
   
   
   
   
   
   
   
   //this function makes a context path for a labeled spacial item
   function make_context_path($labeled_item, $parentArray){
    $pathDelimiter = self::pathDelimiter;
    if($parentArray != false){
        $firstLoop = true;
        foreach($parentArray as $parentItem){
            if($parentItem["labeledItem"] != false){
                if($firstLoop){
                    $context_path = $parentItem["labeledItem"];
                    $firstLoop = false;
                }
                else{
                    $context_path .= $pathDelimiter.$parentItem["labeledItem"];
                }
            }
        }//end loop
        
        if($firstLoop){
            //the parent array was empty
            $context_path = $labeled_item; 
        }
        else{
            //the parent array had parent items
            $context_path .= $pathDelimiter.$labeled_item;
        }
        
    }//end case with parent path
    else{
        $context_path = $labeled_item;
    }
    
    return $context_path;
   }//end function
   
   
   //add data to containment table
   function add_containment_record($parentUUID, $childUUID){
    if($parentUUID != false){
        $db = $this->db;
        $dataTableName = $this->dataTableName;
        $projectUUID = $this->projectUUID;
        
        
        $hashAll = md5($parentUUID . '_' .$childUUID);
        $data = array(
                        'project_id'   => $projectUUID,
                        'source_id'          => $dataTableName,
                        'hash_all'          => $hashAll,                    
                        'parent_uuid'       => $parentUUID,              
                        'child_uuid'        => $childUUID
                     );
        try{
            $db->insert("space_contain", $data);
        }
        catch(Exception $e){
            //this containment relation already exists
        } 
    }
    
   }//end function
   
   
   
   
   */
   
   
   
   
   
    
    /************************
     * addNotesValueToTable *
     ************************
     *
     * If the person who imported the data included notes about the field in the
     * 'field_notes' field of the 'table_summary' table, make that note record a
     * value in the value table.
   */
    function addNotesValueToTable($projectUUID, $dataTableName, $parentFieldNotes)
    {
        $value          = new Table_Value();
        $whereClause = "project_id = '" . $projectUUID . "' and val_text LIKE '" . substr($parentFieldNotes,0,200) . "%'";
        $valRecord  = $value->fetchRow($whereClause);
        $valueUUID  = "";
        $numval     = null;
        
        //if it's a new value...
        if($valRecord == null)
        {
            $valueUUID  = GenericFunctions::generateUUID();
            $valScram   = md5($parentFieldNotes . $projectUUID);
            
            //insert the value into the val_tab table:
            $data = array(
                'project_id'   => $projectUUID,
                'source_id'          => $dataTableName,
                'text_scram'        => $valScram,
                'val_text'          => $parentFieldNotes,
                'value_uuid'        => $valueUUID,
                'val_num'           => null
             );
            $value->insert($data);
        }
        else
        {
            $valueUUID  = $valRecord->value_uuid;
        }
        return $valueUUID;
    }

    
    
    /**********************
     * addPropertyToTable *
     **********************
     *
     * This function adds a property to a table (once the variable and value have
     * been defined).
     *
   */
    function addPropertyToTable($projectUUID, $dataTableName, $valueUUID, $variableUUID, $numval = null)
    {        
        $property       = new Table_Property();        
        
        //check to see if there's already an entry in the properties table:
        $whereClause = "project_id = '" . $projectUUID . "' and value_uuid = '" . $valueUUID . "' and variable_uuid = '" . $variableUUID . "'";
        $propertyRecord  = $property->fetchRow($whereClause);
        $propUUID = null;
        if($propertyRecord == null)
        {
            $propHash   = md5($projectUUID . $variableUUID . $valueUUID);
            $propUUID   = GenericFunctions::generateUUID();
            //insert the property into the properties table:
            $data = array(
                'project_id'   => $projectUUID,
                'source_id'          => $dataTableName,
                'prop_hash'         => $propHash,
                'property_uuid'     => $propUUID,
                'variable_uuid'     => $variableUUID,
                'value_uuid'        => $valueUUID,
                'val_num'           => $numval
             );
            $property->insert($data);
            
        }
        else
        {
            $propUUID = $propertyRecord->property_uuid;   
        }
        return $propUUID;
    }
   
   
   
   
}

