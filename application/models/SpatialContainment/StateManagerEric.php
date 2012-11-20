<?php
class SpatialContainment_StateManager extends Zend_Db_Table
{
    public $ns;
    
    function SpatialContainment_StateManager($reset)
    {
        require_once 'Zend/Session/Namespace.php';
        Zend_Loader::loadClass('SpatialContainment_StackObject');
        $this->ns = new Zend_Session_Namespace('OC');
        
        if(!isset($this->ns->numRecordsProcessed) || $reset)
            $this->ns->numRecordsProcessed = 0;
        
        if(!isset($this->ns->numParentNodes) || $reset)
            $this->ns->numParentNodes = 0;
            
        if(!isset($this->ns->parentNodeIndex) || $reset)
            $this->ns->parentNodeIndex = 0;
        
        if(!isset($this->ns->numChildNodes) || $reset)
            $this->ns->numChildNodes = 0;
            
        if(!isset($this->ns->childNodeIndex) || $reset)
            $this->ns->childNodeIndex = 0;
        
        if(!isset($this->ns->hasEchoed) || $reset)
            $this->ns->hasEchoed = false;
            
        $this->initStateManager($reset);
    }
    
    private function initStateManager($reset)
    {
        if(!isset($this->ns->containmentStack) || $reset)
            $this->ns->containmentStack = array();

        if(!isset($this->ns->containmentLevels) || $reset)
            $this->ns->containmentLevels = 0;
            
        if(!isset($this->ns->returnMessage) || $reset)
            $this->ns->returnMessage = "";
            
        $this->ns->isDone = false;
        
        //$this->ns->hasEchoed = false;  
    }
    
    public function isDone() { return $this->ns->isDone; }
    public function setIsDone($value) { $this->ns->isDone = $value; }
    
    public function getLevels() { return $this->ns->containmentLevels; }
    public function setLevels($value) { $this->ns->containmentLevels = $value; }
    public function incLevels($value)
    {
        if($this->ns->containmentLevels < $value)
            $this->ns->containmentLevels = $value;    
    }
    
    public function getNumRecordsProcessed() { return $this->ns->numRecordsProcessed; }
    public function incNumRecordsProcessed() { ++$this->ns->numRecordsProcessed; }
    
    public function getReturnMessage() { return $this->ns->returnMessage; }
    public function clearReturnMessage() { return $this->ns->returnMessage = ""; }
    public function updateReturnMessage($value)
    {
        if(strlen($this->ns->returnMessage) > 0)
            $this->ns->returnMessage .= '<br />';
        $this->ns->returnMessage .= str_ireplace('|xx|', ' >> ', $value) . '<br />';
    }
    
    public function getNumParentNodes() { return $this->ns->numParentNodes; }
    public function setNumParentNodes($value) { $this->ns->numParentNodes = $value; }
    
    public function getParentNodeIndex() { return $this->ns->parentNodeIndex; }
    public function incParentNodeIndex() { ++$this->ns->parentNodeIndex; }
    public function setParentNodeIndex($value) { $this->ns->parentNodeIndex = $value; }
    
    public function getNumChildNodes() { return $this->ns->numChildNodes; }
    public function setNumChildNodes($value) { $this->ns->numChildNodes = $value; }
    
    public function getChildNodeIndex() { return $this->ns->childNodeIndex; }
    public function incChildNodeIndex() { ++$this->ns->childNodeIndex; }
    public function setChildNodeIndex($value) { $this->ns->childNodeIndex = $value; }
    
    public function getHasEchoed() { return $this->ns->hasEchoed; }
    public function setHasEchoed($value) { $this->ns->hasEchoed = $value; }
    
    public function getStack() { return $this->ns->containmentStack; }
    public function clearStack() { $this->ns->containmentStack = array(); }
    public function addToStack($stackObject) { array_push($this->ns->containmentStack, $stackObject); }
    public function popStack() { array_pop($this->ns->containmentStack); }    
    public function getStackObject()
    {
        $s = $this->getStack();
        if($s != null)
            return $s[(sizeof($s) - 1)];
        return null;
    }
    public function getStackObjectParent()
    {
        $s = $this->getStack();
        if($s != null)
        {
            if( (sizeof($s)-1) > 0)
                return $s[(sizeof($s) - 2)];
        } 
        return null;
    }
    
    
    
    /********************************
     * SPATIAL CONTAINMENT - PART I *
     ********************************
     *
     * transformSpatialRelationshipsAction() works by:
     * 1)   finding the top node for each spatial containment relationship in
     *      the project.
     * 2)   iterating through each top node.
     * 3)   calling the "transformData" function for each node (which is a recursive)
     *      function that actually performs the spatial containment logic.
     *
   */
    public function initTransformSpatialContainment($dataTableName)
    {       
        //  -------------------------------------------------------
        //  Step 1
        //  -------------------------------------------------------
        //  Determine the "top" node(s) of spatial containment
        //  (note that there can be multiple top nodes):
        //     - gets a distinct list of child properties
        //     - uses child property list to determine the top-level
        //       parents (top-level parents are never children).
        //  -------------------------------------------------------
        //retrieve the file_summary record:
        
        $fileSummary    = new Table_FileSummary();
        $fileSummaryRow = $fileSummary->fetchRow("source_id = '" . $dataTableName . "'");
        
        /*
        catch (Exception $e) {
                        
            }
        */
        
        //retrieve the parent project record:
        $projectRow     = $fileSummaryRow->findParentRow('Table_Project');
        $projectUUID    = $projectRow->project_id;
       
       
        $parents = $this->getParents($dataTableName);
        $this->setNumParentNodes(sizeof($parents));
        
        if(sizeof($parents) == 0)
            return "";
        
        //  -------------------------------------------------------
        //  Step 2
        //  -------------------------------------------------------
        //  For each of the top level nodes, traverse the spatial
        //  containment data (using recursion) to populate space,
        //  space_contain, and space_lookup tables:
        //  -------------------------------------------------------
        //echo 'number of parents' . sizeof($parents);
        
        
        $parent = $parents[$this->getParentNodeIndex()];
        $fieldRows = $this->getFieldRows($dataTableName, $parent['field_parent_name']);
        $this->setNumChildNodes(sizeof($fieldRows));
        $row = $fieldRows[$this->getChildNodeIndex()];
        //$row = $fieldRows[1];
        
        $stackObject = new SpatialContainment_StackObject(
            $projectUUID,
            $dataTableName,
            $row,
            0,
            array(),
            array(),
            null                
        );
        
        //echo var_dump($stackObject);
        
        $this->addToStack($stackObject);
        $this->transformCurrentNode();
        
        if(!$this->getHasEchoed())
        {
            $returnArray = array();
            array_push($returnArray, 'Total number of records processed: ' . $this->getNumRecordsProcessed());
            array_push($returnArray, $this->getReturnMessage());
            $this->setHasEchoed(true);
            echo Zend_Json::encode($returnArray);
        }
        
    }
    
    public function continueToNextParent($dataTableName)
    {
        if($this->getChildNodeIndex() == ($this->getNumChildNodes()-1))
        {
            //iterate to next parent index (if applicable):
            if($this->getParentNodeIndex() == ($this->getNumParentNodes()-1))
            {
                //we're done!
                if(!$this->getHasEchoed())
                {
                    $returnArray = array();
                    echo Zend_Json::encode($returnArray);
                    $this->setHasEchoed(true);
                }
                return;
            }
            else
            {
                $this->incParentNodeIndex();
                //$this->setChildNodeIndex(0);
                $this->initStateManager(true);
                $this->initTransformSpatialContainment($dataTableName);
            }
        }
        else
        {
            $this->incChildNodeIndex();
            //$this->setChildNodeIndex(0);
            $this->initStateManager(true);            
            $this->initTransformSpatialContainment($dataTableName);
        }
    }
    
    private function getParents($dataTableName)
    {
        $db = Zend_Registry::get('db');
        //1. get distinct list of child properties
        $subselect = $db->select()
            ->from  (
                        array('f' => 'field_links'),
                        array('f.fk_field_child')
            )
            ->where ("f.source_id = '" . $dataTableName . "' and f.fk_link_type = 1");
        
        $stmt = $db->query($subselect);
        $rows = $stmt->fetchAll();
        
        //if no rows, then no spatial containment has been specified:
        if(sizeof($rows) == 0)
            return "";

        $parameterList = array();
        $i=0;
        foreach ($rows as $row) 
            array_push($parameterList, $row['fk_field_child']);
        
        //Get top-level tree node pair (any rows where the parents aren't also children):
        $select = $db->select()
            ->distinct()
            ->from(
                    array('f' => 'field_links'),
                    array('f.field_parent_name')
            )
            ->where("f.source_id = '" . $dataTableName . "' and f.fk_link_type = 1 and " .
                    "fk_field_parent NOT IN (" . join(',', $parameterList) . ")");    
        $stmt = $db->query($select);
        return $stmt->fetchAll();
    }
    
    /*private function addSpaceRecord()
    {
        $so             = $this->getStackObject();
        $level          = $so->level;
        $db             = Zend_Registry::get('db');
        $space          = new Table_Space();
        $spaceContain   = new Table_SpaceContain();
        
        //  -------------------------------------------------------
        //  Step 3.(a):  Parent Logic 
        //  -------------------------------------------------------
        //  i.  Check to see if the parent item is in the space
        //      table.  If it's not in there, add it.
        //  -------------------------------------------------------
        $parentDataItem = null;
        $dataRow        = $dataRows[$so->index];
        $parentDataItem = $dataRow[$parentFieldName];
        
        if(strlen($fullContextCurrent) == 0)
        {   if($parentAlias != null && strlen($parentAlias > 0))
                $fullContextCurrent .= $parentAlias . ' ';
            $fullContextCurrent .= $parentDataItem;
        }
        
        //if the $parentDataItem is null, get the first element in the element's
        //hierarchy that isn't null:
        //todo:  more testing needed for this if-statement - might cause an infinite loop:
        if($parentDataItem == null)
        {
            $hierarchy = explode($fullContextDelimiter, $fullContextCurrent);
            $parentDataItem = $hierarchy[sizeof($hierarchy)-1];
            //echo 'Parent Data Item: ' . $parentDataItem . '\n';
        }
        
        $hashTxt    = md5($projectUUID . "_" . $fullContextCurrent);

        //check to see if the parent record exists in the space table:
        $spaceRow = $space->fetchRow("hash_fcntxt = '" . $hashTxt . "'");
        //Zend_Debug::dump($projectUUID . "_" . $fullContextCurrent);

        $parentUUID = null;
        if($spaceRow == null)
        {
            //insert parent into space
            $parentUUID             = GenericFunctions::generateUUID();
            $spaceLabel = $parentDataItem;
            if($parentAlias != null)
                $spaceLabel = $parentAlias . ' ' . $spaceLabel;
            // --------------------------
            // Establish Root Node Here -
            // --------------------------
            if($level == 0)// $level==0 means it's the top-level node
            {
                $rootID = '[ROOT]:' . $projectUUID;
                $hashAll = md5($rootID . '_' . $parentUUID);
                $spaceContainRow = $spaceContain->fetchRow("hash_all = '" . $hashAll . "'");
                if($spaceContainRow == null)
                {
                    $data = array(
                        'project_id'   => $projectUUID,
                        'source_id'          => $dataTableName,
                        'hash_all'          => $hashAll,                    
                        'parent_uuid'       => $rootID,              
                        'child_uuid'        => $parentUUID
                     );
                    $spaceContain->insert($data);
                }
                $fc = $spaceLabel;
            }
            else
            {
                $fc = $fullContextCurrent;    
            }
            // --------------------------
            
            $data = array(
                'project_id'   => $projectUUID,
                'source_id'          => $dataTableName,
                'hash_fcntxt'       => $hashTxt,                    // md5($projectUUID . "_" . $fullContextCurrent); 
                'uuid'        => $parentUUID,                 // generated from uuID function    
                'space_label'       => $spaceLabel,                 // Bone# 263       
                'full_context'      => $fc,//$fullContextCurrent,                // AM95|xx|Area E-1|xx|Locus 103|xx|1016|xx|Bone# 263 
                'sample_des'        => '',
                'class_uuid'        => $parentClassUUID             // field_summary.class_uuid
             );
            //Zend_Debug::dump($data);
            $space->insert($data);
            $this->incNumRecordsProcessed();            
            $this->updateReturnMessage($fullContextCurrent);
            
            //  -------------------------------------------------------
            //  ii. Check to see if the parent item is in the 
            //      space_lookup table.  If it's not in there, add it.
            //      The space_lookup table tracks the source of the
            //      input.
            //  -------------------------------------------------------                
            //echo "addToSpatialLookup";
            $this->addToSpatialLookup( $dataTableName,
                                $dataRow,
                                $parentDataItem,
                                $parentFieldNum,
                                $parentFieldName,
                                $fieldList,
                                $parentUUID);
        }
        else
        {
            $parentUUID = $spaceRow->uuid;  
        }
    }
    
    
    
    */
    
    public function transformCurrentNode()
    {
        //remember that this is a reference to an object in session:
        $so = $this->getStackObject();
        
        $projectUUID    = $so->projectUUID;
        $dataTableName  = $so->dataTableName;
        $fieldRow       = $so->fieldRow;
        $level          = $so->level;
        $fieldList      = $so->fieldList;
        $aliasList      = $so->aliasList;
        $dataRow        = $so->dataRow;
        $index          = $so->index;
        $this->incLevels($level);
        
        $parentFieldName    = $fieldRow['field_parent_name'];
        $parentAlias        = $fieldRow['parent_alias'];
        $parentClassUUID    = $fieldRow['parent_class'];
        $parentFieldNum     = $fieldRow['parent_field_num'];
        $parentFieldNotes   = $fieldRow['parent_field_notes'];
               
        $childFieldName     = $fieldRow['field_child_name'];
        $childAlias         = $fieldRow['child_alias'];
        $childClassUUID     = $fieldRow['child_class'];
        $childFieldNum      = $fieldRow['child_field_num'];
        $childFieldNotes    = $fieldRow['child_field_notes'];

        $fc = null;
        
    
        
        
        
        //echo var_dump($fieldRow)."<br/><br/>";
        
        
        //  -------------------------------------------------------
        //  Step 1:  Persist Where Clause Criteria
        //  -------------------------------------------------------
        // 1.   Add the next two fields onto the end of the $fieldList stack.
        //      The field list stack is used to keep track of the "where clause."
        //      That is, as we dive deeper and deeper into spatial containment, we
        //      only want to query for child records that correspond to the parent trail:
        //      (where field_1 = 'top node data value' and field_4 = 'next data value'...)
        //  -------------------------------------------------------
        if(!in_array($parentFieldName, $fieldList))
            array_push($fieldList, $parentFieldName);
        if(!in_array($parentAlias, $aliasList))
            array_push($aliasList, $parentAlias);
        
        
        //  -------------------------------------------------------
        //  Step 2:  Query for Child Records
        //  -------------------------------------------------------
        // 2.(a).   Begin constructing the where clause (to get the correct child data values):
        $whereClause = "";
        if($dataRow == null)
            $whereClause = 'd.'.$parentFieldName . ' is not null and ' . 'd.'.$childFieldName . ' is not null';

        // 2.(b).   Once where clause has been created, add the childFieldName to
        //          the fieldList so that the query for the next level of containment
        //          can incorporated the childFieldName into it's "where clause."
        if(!in_array($childFieldName, $fieldList))
            array_push($fieldList, $childFieldName);
        if(!in_array($childAlias, $aliasList))
            array_push($aliasList, $childAlias);
        
        // 2.(c).   Begin constructing the SQL selection criteria for the data:
        $queryFieldList = array();
        foreach($fieldList as $f)
            array_push($queryFieldList, $f);
        
        $db = Zend_Registry::get('db');
        $select = $db->select()
        ->distinct()
        ->from  (
                array('d' => $dataTableName),
                $queryFieldList
        );
        
        // 2.(d).   Finish creating the "where clause" and append to the SQL
        //          selection criteria:
        $fullContext = "";
        $fullContextDelimiter = "|xx|";
        
        $sql = "SELECT project_list.parcontext_name
        FROM project_list
        WHERE project_list.project_id = '" . $projectUUID . "'";
        
        //echo $sql;
        $ProjRows = $db->fetchAll($sql, 2);
        $proj_root_name = $ProjRows[0]["parcontext_name"];
        if(strlen($proj_root_name)>0){
            $proj_root_name = $proj_root_name.$fullContextDelimiter;
            //echo $fullContext ;
        }
        else{
            $proj_root_name = "";
        }
        
        //Eric added. This fixes a problem with the alias list for parent items
        $sql = "SELECT field_summary.field_name, field_summary.field_lab_com
        FROM field_summary
        WHERE field_summary.field_type = 'Locations or Objects'  
        AND field_summary.source_id = '$dataTableName'
        ";
        
        $aliasRows = $db->fetchAll($sql, 2);
        $parPrefix = array();
        foreach($aliasRows as $actAlias){
            $fieldName = $actAlias["field_name"];
            $parPrefix[$fieldName] = $actAlias["field_lab_com"];
        }
        unset($aliasRows);
        
        
        //the following bit checks to see if child items need to be added to the lookup table, even if it is not new
        $sql = "SELECT DISTINCT field_summary.field_num
            FROM field_summary 
            JOIN field_summary as PropJoin ON (field_summary.pk_field = PropJoin.fk_field_describes AND field_summary.source_id = PropJoin.source_id)
            WHERE field_summary.source_id = '$dataTableName'";
            
        $desRows = $db->fetchAll($sql, 2);
        $foundChNum = false;
        foreach($desRows as $actDesRow){
            if($childFieldNum == $actDesRow["field_num"]){
                $foundChNum = true; //this means that a data needs to be added to the lookup table, even if not a new location/object
            }
        }
        
        //the following bit checks to see if child items need to be added to the lookup table, even if it is not new
        $sql = "SELECT DISTINCT field_links.field_parent_name, field_links.field_child_name
            FROM field_links 
            WHERE (field_links.field_parent_name = '$childFieldName' OR field_links.field_child_name = '$childFieldName')
            AND field_links.fk_link_type != 1
            AND field_links.source_id = '$dataTableName'";
            
        $linksRows = $db->fetchAll($sql, 2);
        if($linksRows){
            $foundChNum = true; //child item is in some linking relationship, needs space look up
        }
        
        
        
        //the following bit checks to see if there are only two location and object fields, if so, then
        //allow partial matches of containment paths
        $sql = "SELECT count(field_summary.field_type) as LocCount
        FROM field_summary 
        WHERE field_summary.source_id = '$dataTableName'
        AND field_summary.field_type = 'Locations or Objects'";
            
        $typeRows = $db->fetchAll($sql, 2);
        $twoLocs = false;
        if($typeRows[0]["LocCount"] == 2){
             $twoLocs = true;
        }
        
        
        
        if($whereClause != "")
        {
           $select->where($whereClause);
        }
        else
        {
            $stopIdx = sizeof($fieldList) - 1;
            for($k=0; $k < $stopIdx; ++$k)
            {
                $select->where($fieldList[$k] . ' = ?', $dataRow[$fieldList[$k]]);
                
                //build the $fullContext variable.  Note that the $fullContext
                //variable is the "trail" that the data followed:
                if($k > 0){
                    $fullContext .= $fullContextDelimiter;
                }
                
                $actPrefix = @$parPrefix[$fieldList[$k]];
                if($actPrefix == "null"){
                    $actPrefix = "";
                }
                if(strlen($actPrefix) > 0){
                    
                    //echo "hit";
                    //keeps situations like "Locus Locus" from happening
                    if((substr_count($actPrefix,"click to edit")< 1)&&(substr_count($dataRow[$fieldList[$k]], $actPrefix) < 1)){
                        $fullContext .= $actPrefix . ' ';
                    }
                 
                }

                $fullContext .= $dataRow[$fieldList[$k]];
                //echo $fullContext."<br/><br/>";
            }
        }
        
        //$fullContext = $proj_root_name.$fullContext;
        //echo var_dump($aliasList)."<br/><br/>";
        //echo "-----------------------------------------------------------------<br/>";
        //echo $fullContext."<br/>";
        
        // 2.(e).   Retrieve all of the data:
        
        //$sql = $select->__toString();
        //echo "$sql\n";
        
        $stmt = $db->query($select);
        //Zend_Debug::dump($stmt);
        //return;
        try
        {
            $dataRows = $stmt->fetchAll();
            $so->numDataRecords = sizeof($dataRows);
            //echo '<br />number of rows' . sizeof($dataRows) . '<br />';
        }
        catch(Exception $e)
        {
            Zend_Debug::dump($stmt);
        }

        
        //  -------------------------------------------------------
        //  Step 3:  Insert Containment Data 
        //  -------------------------------------------------------
        //  Iterate through the containment data and insert the data 
        //  that isn't yet in the database:
        //  -------------------------------------------------------
        
        $space          = new Table_Space();            // gives us access to 'space' table functionality
        $spaceContain   = new Table_SpaceContain();     // gives us access to 'space_contain' table functionality
        $value          = new Table_Value();            // gives us access to 'val_tab' table functionality
        $property       = new Table_Property();         // gives us access to 'properties' table functionality
        $observe        = new Table_Observe();          // gives us access to 'observe' table functionality
        //  -------------------------------------------------------
        //  Step 3.(a):  Parent Logic 
        //  -------------------------------------------------------
        //  i.  Check to see if the parent item is in the space
        //      table.  If it's not in there, add it.
        //  -------------------------------------------------------
        $parentDataItem = null;
        $dataRow        = $dataRows[$so->index];
        $fullContextCurrent = $fullContext;
        $parentDataItem = $dataRow[$parentFieldName];
        
        
        
        if(strlen($fullContextCurrent) == 0)
        {
            if($parentAlias != null && strlen($parentAlias > 0) && $parentDataItem != null){
                if(substr_count($parentDataItem, $parentAlias) == 0){    
                    $fullContextCurrent .= $parentAlias . ' ';
                }
            }
            
            $fullContextCurrent .= $parentDataItem;
        }
        
        
         
        //javascript:showDetail('E9C618A8-B877-4E56-7355-05B3C8DC41F3',%20'Locations%20/%20Objects')
        //if the $parentDataItem is null, get the first element in the element's
        //hierarchy that isn't null:
        //todo:  more testing needed for this if-statement - might cause an infinite loop:
        if($parentDataItem == null)
        {
            $hierarchy = explode($fullContextDelimiter, $fullContextCurrent);
            $parentDataItem = $hierarchy[sizeof($hierarchy)-1];
            //echo 'Parent Data Item: ' . $parentDataItem . '\n';
        }
        
        //remove this! for working imports of data not from Petra
        //$fullContextCurrent = "Jordan|xx|".$fullContextCurrent;
        
        //$hashTxt    = md5($projectUUID . "_" . $fullContextCurrent);
        $hashTxt    = md5($projectUUID . "_" . $proj_root_name.$fullContextCurrent);

        //check to see if the parent record exists in the space table:
        $spaceRow = $space->fetchRow("hash_fcntxt = '" . $hashTxt . "'");
        
        if($spaceRow == null)
        {
            $spaceRow = $space->fetchRow("full_context LIKE '" . trim($proj_root_name.$fullContextCurrent) . "'");
            if($twoLocs){
                $spaceRow = $space->fetchRow("project_id = '$projectUUID' AND full_context LIKE '%" . trim($fullContextCurrent) . "'");
            } 
        }
        
        //Zend_Debug::dump($projectUUID . "_" . $fullContextCurrent);

        $parentUUID = null;
        if($spaceRow == null)
        {
            //insert parent into space
            $parentUUID             = GenericFunctions::generateUUID();
            $spaceLabel = $parentDataItem;
            if($parentAlias == "null"){
                    $parentAlias = null;
                }
            
            if($parentAlias != null && !strpos($spaceLabel, $fullContextDelimiter))
                
                if(substr_count($spaceLabel, $parentAlias) == 0){
                    $spaceLabel = $parentAlias . ' ' . $spaceLabel;
                }
                
            // --------------------------
            // Establish Root Node Here -
            // --------------------------
            if($level == 0)// $level==0 means it's the top-level node
            {
                $rootID = '[ROOT]:' . $projectUUID;
                $hashAll = md5($rootID . '_' . $parentUUID);
                $spaceContainRow = $spaceContain->fetchRow("hash_all = '" . $hashAll . "'");
                if($spaceContainRow == null)
                {
                    $data = array(
                        'project_id'   => $projectUUID,
                        'source_id'          => $dataTableName,
                        'hash_all'          => $hashAll,                    
                        'parent_uuid'       => $rootID,              
                        'child_uuid'        => $parentUUID
                     );
                    $spaceContain->insert($data);
                }
                $fc = $spaceLabel;
            }
            else
            {
                $fc = $fullContextCurrent . $fullContextDelimiter . $spaceLabel;    
            }
            
            $fc = $proj_root_name.$fullContextCurrent; // this is for projects with a root in spatial items not added by the project
            
            // --------------------------
            
            $data = array(
                'project_id'   => $projectUUID,
                'source_id'          => $dataTableName,
                'hash_fcntxt'       => $hashTxt,                    // md5($projectUUID . "_" . $fullContextCurrent); 
                'uuid'        => $parentUUID,                 // generated from uuID function    
                'space_label'       => $spaceLabel,                 // Bone# 263       
                'full_context'      => $fc,//$fullContextCurrent,                // AM95|xx|Area E-1|xx|Locus 103|xx|1016|xx|Bone# 263 
                'sample_des'        => '',
                'class_uuid'        => $parentClassUUID             // field_summary.class_uuid
             );
            //Zend_Debug::dump($data);
            $space->insert($data);
            $this->incNumRecordsProcessed();            
            $this->updateReturnMessage($fc);
            
            //  -------------------------------------------------------
            //  ii. Check to see if the parent item is in the 
            //      space_lookup table.  If it's not in there, add it.
            //      The space_lookup table tracks the source of the
            //      input.
            //  -------------------------------------------------------                
            //echo "addToSpatialLookup";
            $this->addToSpatialLookup( $dataTableName,
                                $dataRow,
                                $parentDataItem,
                                $parentFieldNum,
                                $parentFieldName,
                                $fieldList,
                                $parentUUID);
        }
        else
        {
            $parentUUID = $spaceRow->uuid;
            
            if($twoLocs){
                $fullContextCurrent = $spaceRow->full_context;
                $fullContextCurrent = str_replace($proj_root_name, "", $fullContextCurrent);
                $fc = $spaceRow->full_context;
            }
        }
        
        
        //  -------------------------------------------------------
        //  iii.Check to seef if any field notes need to get added
        //      to the val_tab and properties tab that relate
        //      to the parent item.
        //  -------------------------------------------------------
        $notesPropUUID  = null;
        if($parentFieldNotes != null && strlen($parentFieldNotes) > 0)
        {
            //add notes to val_tab:
            $valueUUID      = $this->addNotesValueToTable($projectUUID, $dataTableName, $parentFieldNotes);
            //add notes to properties and w_val_ids_lookup:
            $notesPropUUID  = $this->addPropertyToTable($projectUUID, $dataTableName, $valueUUID, 'NOTES', $parentFieldNum, null);
        }
        //add notes observation for the $parentUUID (if it doesn't already exist):
        if($notesPropUUID != null)
        {
            $obsNum         = 1; //hardcode as 1 for now.
            $obsHashText    = md5($projectUUID . "_" . $parentUUID . "_" . $obsNum . "_" . $notesPropUUID);
            $observeRow     = $observe->fetchRow("hash_obs = '" . $obsHashText . "'");
            if($observeRow == null)
            {
                $data = array(
                    'project_id'   => $projectUUID,
                    'source_id'          => $dataTableName,
                    'hash_obs'          => $obsHashText, 
                    'subject_type'      => 'Locations or Objects',
                    'subject_uuid'      => $parentUUID, //reference to the space table.
                    'obs_num'           => $obsNum,
                    'property_uuid'     => $notesPropUUID
                 );
                $observe->insert($data); 
            }
            //Zend_Debug::dump($data);
        }
    
    
        //  -------------------------------------------------------
        //  Step 3.(b):  Child Logic 
        //  -------------------------------------------------------
        //  i.  Check to see if the child item is in the space
        //      table.  If it's not in there, add it.
        //  -------------------------------------------------------
        //$rowNumber          = $dataRow['id'];
        $childDataItem      = $dataRow[$childFieldName];
        $PrefixChildDataItem = trim($childDataItem);
        
        //eric edit. This next part makes sure matches for items with aliased label prefixes
        if($childAlias == "null"){
            $childAlias = null;
        }
        
        if($childAlias != null){        
                if(substr_count($childDataItem, $childAlias) == 0){
                    $PrefixChildDataItem = $childAlias . ' ' . trim($PrefixChildDataItem);
                }
        }
        
        $fullContextChild   = $proj_root_name.$fullContextCurrent . $fullContextDelimiter . $PrefixChildDataItem;
        $hashTxt            = md5($projectUUID . "_" . $fullContextChild);
        //check to see if the child record exists in the space table:
        $spaceRow           = $space->fetchRow("hash_fcntxt = '" . $hashTxt . "'");
        
        $childUUID = null;
        if($spaceRow == null)
        {
            //insert child into space
            $childUUID = GenericFunctions::generateUUID();
            $spaceLabel = $childDataItem;
            if($childAlias != null){
                
                if(substr_count($spaceLabel, $childAlias) == 0){
                    $spaceLabel = $childAlias . ' ' . trim($spaceLabel);
                }
            }
                
            if($fc == null)
            {
                //query for parent's context:
                $parentSpaceRow     = $space->fetchRow("uuid = '" . $parentUUID . "'");
                $fc = $parentSpaceRow->full_context . $fullContextDelimiter . $spaceLabel;   
            }
            else
            {
                $fc .= $fullContextDelimiter . $spaceLabel;   
            }
            
            //if the $parentDataItem is null, get the first element in the element's
            //hierarchy that isn't null:
            //todo:  more testing needed for this if-statement - might cause an infinite loop:
            if($childDataItem == null)
            {
                $hierarchy = explode($fullContextDelimiter, $fullContextCurrent);
                $childDataItem = $hierarchy[sizeof($hierarchy)-1];
                //echo 'Child Data Item: ' . $childDataItem . '\n ';
            }
            $data = array(
                'project_id'   => $projectUUID,
                'source_id'          => $dataTableName,
                'hash_fcntxt'       => $hashTxt,                    // md5($projectUUID . "_" . $fullContextCurrent); 
                'uuid'        => $childUUID,                  // generated from uuID function    
                'space_label'       => $spaceLabel,                 // Bone# 263       
                'full_context'      => $fc, //$fullContextChild,           // AM95|xx|Area E-1|xx|Locus 103|xx|1016|xx|Bone# 263 
                'sample_des'        => '',
                'class_uuid'        => $childClassUUID              // field_summary.class_uuid
             );
            $space->insert($data);
            $this->incNumRecordsProcessed();
            $this->updateReturnMessage($fc);
        }
        else
        {
            $childUUID = $spaceRow->uuid;
            
        }
        
        
        //  -------------------------------------------------------
        //  ii.Check to seef if any field notes need to get added
        //      to the val_tab and properties tab that relate
        //      to the parent item.
        //  -------------------------------------------------------
        $notesPropUUID  = null;
        //Zend_Debug::dump($childFieldNotes);
        if($childFieldNotes != null && strlen($childFieldNotes) > 0)
        {
            //add notes to val_tab:
            $valueUUID      = $this->addNotesValueToTable($projectUUID, $dataTableName, $childFieldNotes);
            //add notes to properties and w_val_ids_lookup:
            $notesPropUUID  = $this->addPropertyToTable($projectUUID, $dataTableName, $valueUUID, 'NOTES', $childFieldNum, null);
        }
        //add notes observation for the $parentUUID (if it doesn't already exist):
        if($notesPropUUID != null)
        {
            $obsNum         = 1; //hardcode as 1 for now.
            $obsHashText    = md5($projectUUID . "_" . $childUUID . "_" . $obsNum . "_" . $notesPropUUID);
            $observeRow     = $observe->fetchRow("hash_obs = '" . $obsHashText . "'");
            if($observeRow == null)
            {
                $data = array(
                    'project_id'   => $projectUUID,
                    'source_id'          => $dataTableName,
                    'hash_obs'          => $obsHashText, 
                    'subject_type'      => 'Locations or Objects',
                    'subject_uuid'      => $childUUID, //reference to the space table.
                    'obs_num'           => $obsNum,
                    'property_uuid'     => $notesPropUUID
                 );
                $observe->insert($data); 
            }
            //Zend_Debug::dump($data);
        }
        
        //  -------------------------------------------------------
        //  iii. Insert linking relationships into space_contain (if
        //      they don't already exist).
        //  -------------------------------------------------------


        $hashAll = md5($parentUUID . '_' . $childUUID);
        //check to see if the linking record exists in the space_contain table:
        $spaceContainRow = $spaceContain->fetchRow("hash_all = '" . $hashAll . "'");
        if($spaceContainRow == null)
        {
            $data = array(
                'project_id'   => $projectUUID,
                'source_id'          => $dataTableName,
                'hash_all'          => $hashAll,                    
                'parent_uuid'       => $parentUUID,              
                'child_uuid'        => $childUUID
             );
            $spaceContain->insert($data);
            
            //  -------------------------------------------------------
            //  iv. Check to see if the child item is in the 
            //      space_lookup table.  If it's not in there, add it.
            //      The space_lookup table tracks the source of the
            //      input.
            //  -------------------------------------------------------
            
            $this->addToSpatialLookup($dataTableName,
                                $dataRow,
                                $childDataItem,
                                $childFieldNum,
                                $childFieldName,
                                $fieldList,
                               $childUUID);
            
            //  -------------------------------------------------------
            //  v.  Check to see if any field notes need to get added
            //      to the val_tab and properties tab that relate
            //      to the child item.
            //  -------------------------------------------------------
            if($childFieldNotes != null && strlen($childFieldNotes) > 0)
            {
                //add notes to val_tab:
                $valueUUID  = $this->addNotesValueToTable($projectUUID, $dataTableName, $childFieldNotes);
                //add notes to properties and w_val_ids_lookup:
                $notesPropUUID   = $this->addPropertyToTable($projectUUID, $dataTableName, $valueUUID, 'NOTES', $childFieldNum, null);
                //add notes to observe:
                $obsHashText = md5($projectUUID . "_" . $childUUID . "_1_" . $notesPropUUID);
                $observeRow = $observe->fetchRow("hash_obs = '" . $obsHashText . "'");
                if($observeRow == null)
                {
                    $data = array(
                        'project_id'   => $projectUUID,
                        'source_id'          => $dataTableName,
                        'hash_obs'          => $obsHashText, 
                        'subject_type'      => 'Locations or Objects',
                        'subject_uuid'      => $childUUID,
                        'obs_num'           => 1,
                        'property_uuid'     => $notesPropUUID
                     );
                    $observe->insert($data); 
                }
            }
        }
        
        
        
        if($foundChNum){ //if the child field is has descriptions, add to lookup
                $this->addToSpatialLookup($dataTableName,
                                $dataRow,
                                $childDataItem,
                                $childFieldNum,
                                $childFieldName,
                                $fieldList,
                               $childUUID);
        }
        
        
        
        
        //  -------------------------------------------------------
        //  Step 4:  Call This Function Recursively
        //  -------------------------------------------------------
        //  Recurse and get the children's children:
        //  -------------------------------------------------------
        $childRows  = $this->getFieldRows($dataTableName, $childFieldName);
        foreach($childRows as $childRow)
        {          
            //create a new stack object
            $newLevel = $level+1;
            $newStackObject = new SpatialContainment_StackObject(
                $projectUUID,
                $dataTableName,
                $childRow,
                $newLevel,
                $fieldList,
                $aliasList,
                $dataRow
            );
            $this->addToStack($newStackObject);
            $this->transformCurrentNode();
        } //foreach childRow
        
        if(!$this->isDone())
        {
            while(!$this->isDone())
            {
                if($so->index == ($so->numDataRecords-1))
                {
                    $this->popStack();
                    if(sizeof($this->getStack()) > 0)
                        $so = $this->getStackObject();
                    else
                        $this->setIsDone(true);
                }
                else
                {
                    ++$so->index;
                    $this->setIsDone(true);
                } 
            }
        }
        //echo sizeof($this->getStack()) . '<br />';
        //return sizeof($this->getStack());
        
    }
    
    
    /**********************
     * addToSpatialLookup *
     **********************
     *
     * This function adds all records that correspond to a $spaceUUID for a
     * particular level in the containment hierarchy to the space_lookup
     * table.  It checks for duplicates before adding a new record.  Used
     * during the spatial containment transformations.
     *
   */
    private function addToSpatialLookup($dataTableName, $dataRow, $dataItem, $fieldNum, $fieldName, $fieldList, $spaceUUID)
    {        
        //echo $dataTableName." ".$dataRow." ".$dataItem." ".$fieldNum." ".$fieldName." ".$fieldList." ".$spaceUUID;
        
        $spaceLookup    = new Table_SpaceLookup();      // gives us access to 'space_lookup' table functionality
        $db = Zend_Registry::get('db');
        $selectLU = $db->select()
            ->from(
                    array('t' => $dataTableName),
                    array('t.id', 't.' . $fieldName)
            )
            ->where('t.' . $fieldName . ' = ?', $dataItem);
        $stopIdx = sizeof($fieldList) - 1;
        if($stopIdx > 0 && $dataRow != null)
        {
            for($k=0; $k < $stopIdx; ++$k)
                $selectLU->where($fieldList[$k] . ' = ?', $dataRow[$fieldList[$k]]);
        }
        
        $dataRecs = $db->query($selectLU)->fetchAll();
        foreach($dataRecs as $dataRec)
        {
            $rowNumber = $dataRec['id'];
            $whereLU =  "source_id='" . $dataTableName . "' and uuid='" . $spaceUUID .
                        "' and field_num =" . $fieldNum . " and row_num = " . $rowNumber;
            //Zend_Debug::dump($whereLU);
            
            $rowLU = $spaceLookup->fetchRow($whereLU);  
            if($rowLU == null)
            {
                $data = array(
                    'source_id'          => $dataTableName,
                    'uuid'        => $spaceUUID, 
                    'field_num'         => $fieldNum,
                    'row_num'           => $rowNumber
                );
                $spaceLookup->insert($data);     
            }
        }
    }
    
    
    
    /************************
     * addNotesValueToTable *
     ************************
     *
     * If the person who imported the data included notes about the field in the
     * 'field_notes' field of the 'table_summary' table, make that note record a
     * value in the value table.
   */
    private function addNotesValueToTable($projectUUID, $dataTableName, $parentFieldNotes)
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
    private function addPropertyToTable($projectUUID, $dataTableName, $valueUUID, $variableUUID, $fieldNum, $numval)
    {        
        $property       = new Table_Property();        
        $valueLookup    = new Table_ValueLookup();
        
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
    
    private function getFieldRows($dataTableName, $fieldName)
    {
        $db = Zend_Registry::get('db');
        
        $select = $db->select()
        ->distinct()
        ->from(
                array('f' => 'field_links'),
                array('f.field_parent_name', 'f.field_child_name')
        )
        ->joinLeft(
               array('s1' => 'field_summary'),
               'f.field_parent_name = s1.field_name and f.source_id = s1.source_id',
               array('parent_alias' => 'field_lab_com', 'parent_class' => 'fk_class_uuid',
                     'parent_field_num' => 'field_num', 'parent_field_notes' => 'field_notes')
        )
        ->joinLeft(
               array('s2' => 'field_summary'),
               'f.field_child_name = s2.field_name and f.source_id = s2.source_id',
               array('child_alias' => 'field_lab_com', 'child_class' => 'fk_class_uuid',
                     'child_field_num' => 'field_num','child_field_notes' => 'field_notes')
        )
        ->where("f.source_id = '" . $dataTableName . "' and f.fk_link_type = 1 and " .
                "field_parent_name = '" . $fieldName . "'");
        $stmt = $db->query($select);
        $fieldRows = $stmt->fetchAll();
        
        
        return $fieldRows;
    }
    
}

