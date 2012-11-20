<?php

// increase the memory limit
ini_set("memory_limit", "2048M");
// set maximum execution time to no limit
ini_set("max_execution_time", "0");

class Importer_EditTransformedDataController extends Zend_Controller_Action
{
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
    
    /**********************
     * populateTreeAction *
     **********************
     *
     * Creates the browsing tree.  Todo:  make this tree lazy load.
     */
    function populateTreeAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $projectUUID    = $_REQUEST['projectUUID'];
        $dataStores     = array();
        $idx            = 0;
        
        //Get Locations / Objects that have containment:
        //$items = $this->getContainmentItems($projectUUID);
        $items = $this->LazyGetContainmentItems($projectUUID);
        if(sizeof($items > 0))
        {
            $dataStores[$idx] = array(
                'dataStoreName' => 'Containment Locations / Objects',
                'dataStore' => array(
                    'label' => 'name',
                    'identifier' => 'id',
                    'items' => $items
                )
            );
            ++$idx;
        }
        
        
        //Get other Locations / Objects that do not have containment:         
        $items = $this->getNonContainmentItems($projectUUID);
        if(sizeof($items > 0))
        {
            $dataStores[$idx] = array(
                'dataStoreName' => 'Other Locations / Objects',
                'dataStore' => array(
                    'label' => 'name',
                    'identifier' => 'id',
                    'items' => $items
                )
            );
            ++$idx;
        }
        
        //Get People elements:
        $items = $this->getPeopleItems($projectUUID);
        if(sizeof($items > 0))
        {
            $dataStores[$idx] = array(
                'dataStoreName' => 'People',
                'dataStore' => array(
                    'label' => 'name',
                    'identifier' => 'id',
                    'items' => $items
                )
            );
            ++$idx;
        }
        
        //Get Media (various) elements:
        /*
        $items = $this->getResourceItems($projectUUID);
        if(sizeof($items > 0))
        {
            $dataStores[$idx] = array(
                'dataStoreName' => 'Media (various)',
                'dataStore' => array(
                    'label' => 'name',
                    'identifier' => 'id',
                    'items' => $items
                )
            );
            ++$idx;
        }
        */
        
        //Get Diary / Narrative elements:
        $items = $this->getDiaryItems($projectUUID);
        $dataStores[$idx] = array(
            'dataStoreName' => 'Diary / Narrative',
            'dataStore' => array(
                'label' => 'name',
                'identifier' => 'id',
                'items' => $items
            )
        );
        
        
        
        echo Zend_Json::encode($dataStores);
    }
    
    
    private function getItems($projectUUID, $parentUUID, $items, $level)
    {
        ++$level;
        //get the parent items that will reference subsequent child items:
        $parentItemIndex = sizeof($items)-1;
        
        // get data for the child nodes:
        $db = Zend_Registry::get('db');
        $select = $db->select()
            ->distinct()
            ->from(
                    array('c' => 'space_contain'),
                    array('c.parent_uuid')
            )
            ->join(
                    array('s' => 'space'),
                    'c.child_uuid = s.uuid',
                    array('s.uuid', 'data_value' => 's.space_label', 'hash_id' => 's.hash_fcntxt')
            )
            ->where('c.parent_uuid = ?', $parentUUID)
            ->order('data_value');
        $dataRows = $db->query($select)->fetchAll();

        //Iterate through the child items:        
        foreach ($dataRows as $dataRow) 
        {
            $childUUID     = $dataRow['uuid'];
            $childValue    = $dataRow['data_value'];
            
            //push reference onto parent's child array:
            array_push($items[$parentItemIndex]['children'], array('_reference' => $childUUID));

            //add the child item to the $items array
            array_push($items,
                array(
                    'id'        => $childUUID,
                    'name'      => $childValue,
                    'type'      => $level,
                    'objectType'=> 'Locations / Objects',
                    'children'  => array()
                )
            );
            
            //get children's children (recurse):
            if($level<1){
                $items = $this->getItems($projectUUID, $childUUID, $items, $level);
            }
            
        }//end foreach $dataRow
        return $items;
    }
    
    
    private function getContainmentItems($projectUUID)
    {
        $db = Zend_Registry::get('db');
        
        $root_item = substr("[ROOT]:".$projectUUID,0,40);
        //get all Locations / Objects that are spatially contained:
        $select = $db->select()
            ->distinct()
            ->from(
                    array('c' => 'space_contain'),
                    array('c.parent_uuid')
            )
            ->join(
                    array('s' => 'space'),
                    'c.child_uuid = s.uuid',
                    array('s.uuid', 'data_value' => 's.space_label', 'hash_id' => 's.hash_fcntxt')
            )
            ->joinLeft(
                    array('c1' => 'space_contain'),
                    'c.child_uuid = c1.parent_uuid',
                    array('c1.parent_uuid')
            )  
            ->where ('c.project_id = ?', $projectUUID)        
            ->where ('c.parent_uuid = ?', $root_item)       
            ->where ('c1.parent_uuid is not null')
            ->order('data_value');
            
        //$db->getProfiler()->setEnabled(true);
        //$profiler = $db->getProfiler();
        $dataRows = $db->query($select)->fetchAll();
        
        //$query = $profiler->getLastQueryProfile();
        //echo $query->getQuery();
        
        //Zend_Debug::dump(sizeof($dataRows));
        //return;
        
        //Zend_Debug::dump(sizeof($dataRows));
        //return;
        $dataStores = array();
        $items      = array();
        $level      = 0;
        foreach ($dataRows as $dataRow) 
        {
            $parentUUID     = $dataRow['uuid'];
            $parentValue    = $dataRow['data_value'];
            //$parentValue .= " <a href=\"javascript:showDetail('".$parentUUID."', 'Locations / Objects')\">View</a>";
            
            //add top-level nodes for each item:
            array_push($items,
                array(
                    'id'        => $parentUUID,
                    'name'      => $parentValue,
                    'type'      => $level,
                    'objectType'=> 'Locations / Objects',
                    'top'       => 'true',
                    'children'  => array()
                )
            );
            
            $items = $this->getItems($projectUUID, $parentUUID, $items, $level);
        }
        return $items;
    }
    
    
    private function LazyGetContainmentItems($projectUUID)
    {
        $db = Zend_Registry::get('db');
        
        $root_item = substr("[ROOT]:".$projectUUID,0,40);
        //get all Locations / Objects that are spatially contained:
        $select = $db->select()
            ->distinct()
            ->from(
                    array('c' => 'space_contain'),
                    array('c.parent_uuid')
            )
            ->join(
                    array('s' => 'space'),
                    'c.child_uuid = s.uuid',
                    array('s.uuid', 'data_value' => 's.space_label', 'hash_id' => 's.hash_fcntxt')
            )
            ->joinLeft(
                    array('c1' => 'space_contain'),
                    'c.child_uuid = c1.parent_uuid',
                    array('c1.parent_uuid')
            )  
            ->where ('c.project_id = ?', $projectUUID)        
            ->where ('c.parent_uuid = ?', $root_item)       
            ->where ('c1.parent_uuid is not null')
            ->order('data_value');
            
        //$db->getProfiler()->setEnabled(true);
        //$profiler = $db->getProfiler();
        $dataRows = $db->query($select)->fetchAll();
        
        //$query = $profiler->getLastQueryProfile();
        //echo $query->getQuery();
        
        //Zend_Debug::dump(sizeof($dataRows));
        //return;
        
        //Zend_Debug::dump(sizeof($dataRows));
        //return;
        $dataStores = array();
        $items      = array();
        $level      = 0;
        foreach ($dataRows as $dataRow) 
        {
            $parentUUID     = $dataRow['uuid'];
            $parentValue    = $dataRow['data_value'];
            //$parentValue .= " <a href=\"javascript:showDetail('".$parentUUID."', 'Locations / Objects')\">View</a>";
            
            //add top-level nodes for each item:
            array_push($items,
                array(
                    'id'        => $parentUUID,
                    'name'      => $parentValue,
                    'type'      => $level,
                    'objectType'=> 'Locations / Objects',
                    'top'       => 'true',
                    'isStub'    => 'false',
                    'children'  => array()
                )
            );
            
            $items = $this->LazyGetChildItems($projectUUID, $parentUUID, $items, $level);
        }
        return $items;
    }
    
    
    private function LazyGetChildItems($projectUUID, $parentUUID, $items, $level, $last = 1)
    {
        ++$level;
        //get the parent items that will reference subsequent child items:
        $parentItemIndex = sizeof($items)-1;
        
        // get data for the child nodes:
        $db = Zend_Registry::get('db');
        $select = $db->select()
            ->distinct()
            ->from(
                    array('c' => 'space_contain'),
                    array('c.parent_uuid')
            )
            ->join(
                    array('s' => 'space'),
                    'c.child_uuid = s.uuid',
                    array('s.uuid', 'data_value' => 's.space_label', 'hash_id' => 's.hash_fcntxt')
            )
             ->joinLeft(
                    array('c1' => 'space_contain'),
                    'c.child_uuid = c1.parent_uuid',
                    array('child_cnt' => 'COUNT(c1.child_uuid)' )
            )        
            ->where('c.parent_uuid = ?', $parentUUID)
            ->group('c.child_uuid')
            ->order('data_value');
        $dataRows = $db->query($select)->fetchAll();

        //Iterate through the child items:        
        foreach ($dataRows as $dataRow) 
        {
            $childUUID     = $dataRow['uuid'];
            $childValue    = $dataRow['data_value'];
            $childCNT = $dataRow['child_cnt'];
            
            if($childCNT>0){
                $isStub = true;
            }
            else{
                $isStub = false;
            }
            
            //push reference onto parent's child array:
            array_push($items[$parentItemIndex]['children'], array('_reference' => $childUUID));

            //add the child item to the $items array
            array_push($items,
                array(
                    'id'        => $childUUID,
                    'name'      => $childValue,
                    'type'      => $level,
                    'isStub'    => $isStub,
                    'objectType'=> 'Locations / Objects',
                    'children'  => array()
                )
            );
            
            //get children's children (recurse):
            if($level<1){
                $items = $this->getItems($projectUUID, $childUUID, $items, $level);
            }
            
        }//end foreach $dataRow
        return $items;
    }
    
    function obtainChildrenAction(){
        $this->_helper->viewRenderer->setNoRender();
        $spaceUUID    = $_REQUEST['uuID'];
        $obtype = $_REQUEST['objectType'];
        $level = $_REQUEST['level'];
        $obtype = 'Locations / Objects';
        $db = Zend_Registry::get('db');
        $level = $level + 1;
        if($obtype == 'Locations / Objects'){
            $select = $db->select()
                ->distinct()
                ->from(
                        array('c' => 'space_contain'),
                        array('c.parent_uuid')
                )
                ->join(
                        array('s' => 'space'),
                        'c.child_uuid = s.uuid',
                        array('s.uuid', 'data_value' => 's.space_label', 'hash_id' => 's.hash_fcntxt')
                )
                 ->joinLeft(
                        array('c1' => 'space_contain'),
                        'c.child_uuid = c1.parent_uuid',
                        array('child_cnt' => 'COUNT(c1.child_uuid)' )
                )        
                ->where('c.parent_uuid = ?', $spaceUUID)
                ->group('c.child_uuid')
                ->order('data_value');
            $dataRows = $db->query($select)->fetchAll();
            $items      = array();
            //Iterate through the child items:        
            foreach ($dataRows as $dataRow) 
            {
                $childUUID     = $dataRow['uuid'];
                $childValue    = $dataRow['data_value'];
                $childCNT = $dataRow['child_cnt'];
                
                if($childCNT>0){
                    $isStub = true;
                }
                else{
                    $isStub = false;
                }
                
                //add the child item to the $items array
                array_push($items,
                    array(
                        'id'        => $childUUID,
                        'name'      => $childValue,
                        'type'      => $level,
                        'isStub'    => $isStub,
                        'objectType'=> 'Locations / Objects',
                        'children'  => array()
                    )
                );
                
            }//end foreach $dataRow
            
            echo Zend_json::encode($items);
        }
    }//end public function to get child data
    
    /*
    function editTreeAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $db = Zend_Registry::get('db');
        $projectUUID    = $_REQUEST['projectID'];
        $root_item = substr("[ROOT]:".$projectUUID,0,40);
        //get all Locations / Objects that are spatially contained:
        $select = $db->select()
            ->distinct()
            ->from(
                    array('c' => 'space_contain'),
                    array('c.parent_uuid')
            )
            ->join(
                    array('s' => 'space'),
                    'c.child_uuid = s.uuid',
                    array('s.uuid', 'data_value' => 's.space_label', 'hash_id' => 's.hash_fcntxt')
            )
            ->join(
                        array('sp' => 'space'),
                        'c.parent_uuid = sp.uuid',
                        array('par_hash' => 'sp.hash_fcntxt')
                )
            ->joinLeft(
                    array('c1' => 'space_contain'),
                    'c.child_uuid = c1.parent_uuid',
                    array('c1.parent_uuid', 'child_cnt' => 'COUNT(c1.child_uuid)' )
            )  
            ->where ('c.project_id = ?', $projectUUID)        
            ->where ('c.parent_uuid = ?', $root_item)       
            ->where ('c1.parent_uuid is not null')
            ->group('c.child_uuid')
            ->order('data_value');
            
        //$db->getProfiler()->setEnabled(true);
        //$profiler = $db->getProfiler();
        $dataRows = $db->query($select)->fetchAll();
        
        //$query = $profiler->getLastQueryProfile();
        //echo $query->getQuery();
        
        //Zend_Debug::dump(sizeof($dataRows));
        //return;
        
        //Zend_Debug::dump(sizeof($dataRows));
        //return;
        $dataStores = array();
        $items      = array();
        $level      = 0;
        foreach ($dataRows as $dataRow) 
        {
            $parentUUID     = $dataRow['par_hash'];
            $parentValue    = $dataRow['data_value'];
            //$parentValue .= " <a href=\"javascript:showDetail('".$parentUUID."', 'Locations / Objects')\">View</a>";
            $childCNT = $dataRow['child_cnt'];
            if($childCNT>0){
                    $isStub = true;
                }
                else{
                    $isStub = false;
                }
            
            
            //add top-level nodes for each item:
            array_push($items,
                array(
                    'id'        => $parentUUID,
                    'name'      => $parentValue,
                    'type'      => $level,
                    'objectType'=> 'Locations / Objects',
                    'top'       => 'true',
                    'isStub'    => $isStub,
                    'children'  => array()
                )
            );
            
            //$items = $this->LazyGetChildItems($projectUUID, $parentUUID, $items, $level);
        }
        echo Zend_json::encode($items);
    }
    
    
    function editChildrenAction(){
        $this->_helper->viewRenderer->setNoRender();
        $spaceHash    = $_REQUEST['hashID'];
        //$obtype = $_REQUEST['objectType'];
        $level = $_REQUEST['level'];
        $obtype = 'Locations / Objects';
        $db = Zend_Registry::get('db');
        $level = $level + 1;
        if($obtype == 'Locations / Objects'){
            $select = $db->select()
                ->distinct()
                ->from(
                        array('c' => 'space_contain'),
                        array('c.parent_uuid')
                )
                ->join(
                        array('s' => 'space'),
                        'c.child_uuid = s.uuid',
                        array('s.uuid', 'data_value' => 's.space_label', 'hash_id' => 's.hash_fcntxt')
                )
                ->join(
                        array('sp' => 'space'),
                        'c.parent_uuid = sp.uuid'
                )
                 ->joinLeft(
                        array('c1' => 'space_contain'),
                        'c.child_uuid = c1.parent_uuid',
                        array('child_cnt' => 'COUNT(c1.child_uuid)' )
                )        
                ->where('sp.hash_fcntxt = ?', $spaceHash)
                ->group('c.child_uuid')
                ->order('data_value');
            $dataRows = $db->query($select)->fetchAll();
            $items      = array();
            //Iterate through the child items:        
            foreach ($dataRows as $dataRow) 
            {
                $childHash     = $dataRow['hash_id'];
                $childValue    = $dataRow['data_value'];
                $childCNT = $dataRow['child_cnt'];
                
                if($childCNT>0){
                    $isStub = true;
                }
                else{
                    $isStub = false;
                }
                
                //add the child item to the $items array
                array_push($items,
                    array(
                        'id'        => $childHash,
                        'name'      => $childValue,
                        'type'      => $level,
                        'isStub'    => $isStub,
                        'objectType'=> 'Locations / Objects',
                        'children'  => array()
                    )
                );
                
            }//end foreach $dataRow
            
            echo Zend_json::encode($items);
        }
    }//end public function to get child data
    */
    
    
    private function getNonContainmentItems($projectUUID)
    {
        $db = Zend_Registry::get('db');
        $select = $db->select()
            ->distinct()
            ->from(
                    array('c' => 'space_contain'),
                    array('c.parent_uuid')
            )
            ->join(
                    array('s' => 'space'),
                    'c.child_uuid = s.uuid',
                    array('s.uuid', 'data_value' => 's.space_label', 'hash_id' => 's.hash_fcntxt')
            )
            ->joinLeft(
                    array('c1' => 'space_contain'),
                    'c.child_uuid = c1.parent_uuid',
                    array('c1.parent_uuid')
            )  
            ->where ('c.project_id = ?', $projectUUID)        
            ->where ('c.parent_uuid like ?', '[ROOT]%')       
            ->where ('c1.parent_uuid is null')
            ->order('data_value');
        $dataRows = $db->query($select)->fetchAll();
        $items      = array();
        $level      = 0;
        foreach ($dataRows as $dataRow) 
        {
            $parentUUID     = $dataRow['uuid'];
            $parentValue    = $dataRow['data_value'];
            
            //add top-level nodes for each item:
            array_push($items,
                array(
                    'id'        => $parentUUID,
                    'name'      => $parentValue,
                    'type'      => $level,
                    'objectType'=> 'Locations / Objects',
                    'top'       => 'true'
                )
            );
        }
        return $items;
    }
    
    private function getPeopleItems($projectUUID)
    {
        $db = Zend_Registry::get('db');
        //query where the person is the target:
        $select = $db->select()
            ->distinct()
            ->from(
                array('l' => 'links'),
                array('l.targ_uuid')
            )
            ->join(
                   array('p' => 'users'),
                   'p.uuid = l.targ_uuid',
                   array('p.uuid', 'p.combined_name')
            )
            ->where('l.project_id  = ?', $projectUUID);
        $dataRows = $db->query($select)->fetchAll();
        $items      = array();
        $level      = 0;
        $personUUIDs = array();
        foreach ($dataRows as $dataRow) 
        {
            $uuID     = $dataRow['uuid'];
            $value    = $dataRow['combined_name'];
            
            //add top-level nodes for each item:
            array_push($items,
                array(
                    'id'        => $uuID,
                    'name'      => $value,
                    'type'      => $level,
                    'objectType'=> 'People',
                    'top'       => 'true'
                )
            );
            array_push($personUUIDs, $uuID);
        }
        
        //query where the person is the origin:
        $select = $db->select()
            ->distinct()
            ->from(
                array('l' => 'links'),
                array('l.origin_uuid')
            )
            ->join(
                   array('p' => 'users'),
                   'p.uuid = l.origin_uuid',
                   array('p.uuid', 'p.combined_name')
            )
            ->where('l.project_id  = ?', $projectUUID);
        if(sizeof($personUUIDs) > 0)
            $select->where('p.uuid not in (?)', $personUUIDs);
        $dataRows = $db->query($select)->fetchAll();
        foreach ($dataRows as $dataRow) 
        {
            $uuID     = $dataRow['uuid'];
            $value    = $dataRow['combined_name'];
            
            //add top-level nodes for each item:
            array_push($items,
                array(
                    'id'        => $uuID,
                    'name'      => $value,
                    'type'      => $level,
                    'objectType'=> 'People',
                    'top'       => 'true'
                )
            );
            array_push($personUUIDs, $uuID);
        }
        return $items;
    }
    
    
    
    
    private function getResourceItems($projectUUID)
    {
        $db = Zend_Registry::get('db');
        $select = $db->select()
            ->distinct()
            ->from(
                array('r' => 'resource'),
                array('r.uuid', 'r.res_filename')
            )
            ->where('r.project_id  = ?', $projectUUID);
        $dataRows = $db->query($select)->fetchAll();
        //Zend_Debug::dump($dataRows);
        $items      = array();
        $level      = 0;
        foreach ($dataRows as $dataRow) 
        {
            $uuID     = $dataRow['uuid'];
            $value    = $dataRow['res_filename'];
            
            //add top-level nodes for each item:
            array_push($items,
                array(
                    'id'        => $uuID,
                    'name'      => $value,
                    'type'      => $level,
                    'objectType'=> 'Media (various)',
                    'top'       => 'true'
                )
            );
        }
        //Zend_Debug::dump($items);
        return $items;
    }
    
    private function getDiaryItems($projectUUID)
    {
        $db = Zend_Registry::get('db');
        $select = $db->select()
            ->distinct()
            ->from(
                array('d' => 'diary'),
                array('d.uuid', 'd.diary_text_original')
            )
            ->where('d.project_id  = ?', $projectUUID);
        $dataRows = $db->query($select)->fetchAll();
        //Zend_Debug::dump($dataRows);
        $items      = array();
        $level      = 0;
        foreach ($dataRows as $dataRow) 
        {
            $uuID     = $dataRow['uuid'];
            $value    = $dataRow['diary_text_original'];
            if(strlen($value) > 20)
                $value = substr($value, 0, 20) . '...';
            //add top-level nodes for each item:
            array_push($items,
                array(
                    'id'        => $uuID,
                    'name'      => $value,
                    'type'      => $level,
                    'objectType'=> 'Diary / Narrative',
                    'top'       => 'true'
                )
            );
        }
        //Zend_Debug::dump($items);
        return $items;
    }
    
    
    function getContextDetailsAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $uuID  = $_REQUEST['uuID'];
        $objectType = $_REQUEST['objectType'];
        
        $contextItem = new ContextItem($uuID, $objectType);
        echo Zend_Json::encode($contextItem);
    }
    
    function getContextAttributeAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        
        $objectUUID     = $_REQUEST['objectUUID'];
        $propertyUUID   = $_REQUEST['propertyUUID'];
        $objectType     = $_REQUEST['objectType'];
        $updateType     = $_REQUEST['updateType'];
        $fieldName      = $_REQUEST['fieldName'];
        $valText        = $_REQUEST['valText'];
        
        $db = Zend_Registry::get('db');
        $returnObject = array();        
        switch($updateType)
        {
            case 'Property':
                //v_observe is a view that joins observations to their corresponding variable and value data
                $select = $db->select()
                    ->from(
                        array('o' => 'v_observe'),
                        array('o.property_uuid', 'o.uuid', 'o.var_label', 'o.val_text', 'o.variable_uuid', 'cnt' => 'count(o.uuid)')
                    )
                    //->where('o.uuid = ?', $objectUUID)
                    ->where('o.property_uuid = ?', $propertyUUID);
                $observationRows = $db->query($select)->fetchAll();
                foreach($observationRows as $observationRow)
                {
                    $returnObject = array(
                        'propertyUUID'  => $observationRow['property_uuid'],
                        'objectUUID'    => $observationRow['uuid'],
                        'variableName'  => $observationRow['var_label'],
                        'value'         => $observationRow['val_text'],
                        'objectType'    => $objectType,
                        'updateType'    => $updateType,
                        'valCount'      => $observationRow['cnt']
                    );
                }
                break;
            case 'Diary / Narrative':
                $select = $db->select()
                    ->from(
                        array('d' => 'diary'),
                        array('d.' . $fieldName)
                    )
                    ->where('d.uuid = ?', $objectUUID);
                $observationRows = $db->query($select)->fetchAll();
                foreach($observationRows as $observationRow)
                {
                    $returnObject = array(
                        'objectUUID'    => $objectUUID,
                        'value'         => $observationRow[$fieldName],
                        'objectType'    => $objectType,
                        'updateType'    => $updateType,
                        'field'         => $fieldName
                    );
                }
                break;
            case 'People':
                if($fieldName == 'combined_name')
                {
                    //no querying necessary:
                    $returnObject = array(
                            'objectUUID'    => $objectUUID,
                            'value'         => $valText,
                            'objectType'    => $objectType,
                            'updateType'    => $updateType,
                            'field'         => $fieldName,
                            'valCount'      => 1
                        );
                }
                else if($fieldName == 'link_type')
                {
                    //query to get the count of how many link values we'll be changing:
                     $select = $db->select()
                        ->from(
                            array('l' => 'links'),
                            array('roleName' => 'l.link_type', 'cnt' => 'count(l.link_type)')
                        )
                        ->where('l.targ_uuid = ?', $objectUUID)
                        ->where('l.link_type = ?', $valText)
                        ->group(array('l.link_type'));
                    $personRows = $db->query($select)->fetchAll();
                    foreach($personRows as $personRow)
                    {
                        $returnObject = array(
                            'objectUUID'    => $objectUUID,
                            'value'         => $personRow['roleName'],
                            'objectType'    => $objectType,
                            'updateType'    => $updateType,
                            'field'         => $fieldName,
                            'valCount'      => $personRow['cnt']
                        );
                    }    
                }
                break;
            case 'Media (various)':
                $select = $db->select()
                    ->from(
                        array('r' => 'resource'),
                        array('r.' . $fieldName)
                    )
                    ->where('r.uuid = ?', $objectUUID);
                $observationRows = $db->query($select)->fetchAll();
                foreach($observationRows as $observationRow)
                {
                    $returnObject = array(
                        'objectUUID'    => $objectUUID,
                        'value'         => $observationRow[$fieldName],
                        'objectType'    => $objectType,
                        'updateType'    => $updateType,
                        'field'         => $fieldName
                    );
                }
                break;
        }
        echo Zend_Json::encode($returnObject);
    }
    
    function updateContextItemAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $propertyUUID           = $_REQUEST['propertyUUID'];
        $objectUUID             = $_REQUEST['objectUUID'];   
        $objectType             = $_REQUEST['objectType'];   
        $updateType             = $_REQUEST['updateType'];   
        $fieldName              = $_REQUEST['fieldName'];        
        $updateAll              = $_REQUEST['updateAll'];
        $varText                = $_REQUEST['varText'];
        $valText                = $_REQUEST['valText'];
        $oldValText             = $_REQUEST['oldValText'];
        
        /*Zend_Debug::dump($propertyUUID);
        Zend_Debug::dump($objectUUID);
        Zend_Debug::dump($objectType);
        Zend_Debug::dump($updateType);
        Zend_Debug::dump($updateAll);
        Zend_Debug::dump($varText);
        Zend_Debug::dump($valText);
        return;*/
    
        switch($updateType)
        {
            case 'Property':
                return $this->updateProperty($propertyUUID, $objectUUID, $objectType, $updateAll, $varText, $valText);
            case 'Diary / Narrative':
                return $this->updateDiaryNarrative($objectUUID, $objectType, $updateAll, $fieldName, $valText);
            case 'People':
                return $this->updatePeople($objectUUID, $objectType, $updateAll, $fieldName, $valText, $oldValText);
            case 'Media (various)':
                return $this->updateResource($objectUUID, $objectType, $updateAll, $fieldName, $valText);
        }       
    }
    
    private function updateResource($objectUUID, $objectType, $updateAll, $fieldName, $valText)
    {
        $resource = new Table_Resource();
        $data = array($fieldName => $valText);
        $where = "uuid = '" . $objectUUID . "'";
        $resource->update($data, $where);
        
        // Return object:
        $returnVal = array('uuID' => $objectUUID, 'objectType' => $objectType);
        echo Zend_Json::encode($returnVal);      
    }

    private function updatePeople($objectUUID, $objectType, $updateAll, $fieldName, $valText, $oldValText)
    {
        if($fieldName == 'combined_name')
        {
            $user = new Table_User();
            $data = array($fieldName => $valText);
            $where = "uuid = '" . $objectUUID . "'";
            $user->update($data, $where);
        }
        else if($fieldName == 'link_type')
        {
            //Note that this is a global update for all linking relationships for this particular
            //object:
            $link = new Table_LinkRelationship();
            $data = array('link_type' => $valText);
            $where = "targ_uuid = '" . $objectUUID . "' and link_type='" . $oldValText . "'";
            $link->update($data, $where);
        }
        $returnVal = array('uuID' => $objectUUID, 'objectType' => $objectType);
        echo Zend_Json::encode($returnVal);   
    }
    
    private function updateDiaryNarrative($objectUUID, $objectType, $updateAll, $fieldName, $valText)
    {
        $diary = new Table_Diary();
        $data = array($fieldName => $valText);
        $where = "uuid = '" . $objectUUID . "'";
        $diary->update($data, $where);
        /*Zend_Debug::dump($objectUUID);
        Zend_Debug::dump($objectType);
        Zend_Debug::dump($fieldName);
        Zend_Debug::dump($valText);*/
        
        // Return object:
        $returnVal = array('uuID' => $objectUUID, 'objectType' => $objectType);
        echo Zend_Json::encode($returnVal);      
    }
    
    
    private function updateProperty($propertyUUID, $objectUUID, $objectType, $updateAllProperties, $varText, $valText)
    {
        $property               = new Table_Property();
        $variable               = new Table_Variable();
        $value                  = new Table_Value();                  
        $observe                = new Table_Observe();
        $propertyRow = $property->fetchRow("property_uuid  = '" . $propertyUUID . "'");
        
        // 1.  Query for associated variable and value rows:
        $variableRows   = $propertyRow->findDependentRowset('Table_Variable');
        $valueRows      = $propertyRow->findDependentRowset('Table_Value');
        $variableRow    = null;
        $valueRow       = null;
        foreach($variableRows as $varRow)
            $variableRow = $varRow;
        foreach($valueRows as $valRow)
            $valueRow = $valRow;
            
        // 2.  Update the variable if it has been modified:
        if($variableRow->var_label != $varText)
        {
            $data       = array('var_label' => $varText);
            $where      = "variable_uuid = '" . $variableRow->variable_uuid . "'";
            $variable->update($data, $where);  
        }
        
        // 3.  Update the value:
        $valueUUID              = null;
        $valScram               = md5($valText . $valueRow->project_id);
        //check to see if this value is already in the database:
        $whereClause            = "text_scram = '" . $valScram . "'";
        $valRecord              = $value->fetchRow($whereClause);
        $numval = null;
        if(strlen($valText) > 0)
        {
            $numcheck = "0".$valText;
            if(is_numeric($numcheck))
                $numval = $numcheck;
        }
        //if the value record doesn't exist, insert it:
        if($valRecord == null)
        {
            $valueUUID              = GenericFunctions::generateUUID();
            //create new value record, based on the old value record:
            $data = array(
                'project_id'   => $valueRow->project_id,
                'source_id'          => $valueRow->source_id,
                'text_scram'        => $valScram,
                'val_text'          => $valText,
                'value_uuid'        => $valueUUID,
                'val_num'           => $numval
             );
            $value->insert($data);
        }
        else
        {
            $valueUUID = $valRecord->value_uuid;
        }
        
        // 4.  Update the property:
        $propHash   = md5($propertyRow->project_id . $variableRow->variable_uuid . $valueUUID);
        //check to see if this value is already in the database:
        $whereClause            = "prop_hash = '" . $propHash . "'";
        $propertyRecord         = $property->fetchRow($whereClause);
        if($updateAllProperties == 'true')
        {
            if($propertyRecord == null)
            {
                //update the property itself:
                $data       = array('value_uuid' => $valueUUID, 'prop_hash' => $propHash);
                $where      = "property_uuid = '" . $propertyRow->property_uuid . "'";
                $property->update($data, $where);
            }
            else
            {
                //otherwise, switch all w_observation records to point to the new property:
                $where      = "property_uuid = '" . $propertyRow->property_uuid . "'";
                $data       = array('property_uuid' => $propertyRecord->property_uuid);
                $observe->update($data, $where);   
            }
        }
        else
        {
            $propUUID   = null;
            if($propertyRecord == null)
            {
                $propUUID   = GenericFunctions::generateUUID();
                //create a new properties record:
                $data = array(
                    'project_id'   => $propertyRow->project_id,
                    'source_id'          => $propertyRow->source_id,
                    'prop_hash'         => $propHash,
                    'property_uuid'     => $propUUID,                   //new property uuid
                    'variable_uuid'     => $variableRow->variable_uuid, //old variable uuid
                    'value_uuid'        => $valueUUID,                  //new value uuid
                    'val_num'           => $numval
                 );
                $property->insert($data);            
            }
            else
            {
                //point to a different property record:
                $propUUID = $propertyRecord->property_uuid;            
            }
            
            // 5.  Update the corresponding observation:
            $where      = "property_uuid = '" . $propertyRow->property_uuid . "' and subject_uuid = '" . $objectUUID . "'";
            $data       = array('property_uuid' => $propUUID);

            $observe->update($data, $where);

        }
        // 6.  Return object:
        $returnVal = array('uuID' => $objectUUID, 'objectType' => $objectType);
        echo Zend_Json::encode($returnVal);    
    }
 
    function addObjectLinkAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        
        $projectUUID            = $_REQUEST['projectUUID'];
        $originUUID             = $_REQUEST['originUUID'];
        $targetUUID             = $_REQUEST['targetUUID'];   
        $linkType               = $_REQUEST['linkType'];
        $originObjectType       = $_REQUEST['originObjectType'];
	$targetObjectType       = $_REQUEST['targetObjectType'];
        $obsNum                 = 1;
        $linkUUID               = GenericFunctions::generateUUID();        
        $hashLink               = md5($originUUID . '_' . $obsNum . '_' . $targetUUID . '_' . $linkType);
        
        $link                   = new Table_LinkRelationship();
        $whereClause            = "hash_link = '" . $hashLink . "'";
        $linkRecord             = $link->fetchRow($whereClause);
        //Zend_Debug::dump($linkRecord);
        if($linkRecord == null)
        {
            $data = array('project_id' => $projectUUID,
                'hash_link' => $hashLink,
                'link_type' => $linkType,
                'link_uuid' => $linkUUID,
                'origin_type' => $originObjectType,
                'origin_uuid' => $originUUID,
                'origin_obs' => $obsNum,
                'targ_type' => $targetObjectType,
                'targ_uuid' => $targetUUID,
                'targ_obs' => $obsNum);
            
            $link->insert($data);
            //Zend_Debug::dump($data);
        }
        
        $returnVal = array('uuID' => $originUUID, 'objectType' => $originObjectType);
        echo Zend_Json::encode($returnVal);   
    }
    
    function removeLinkAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        
        $projectUUID            = $_REQUEST['projectUUID'];
        $originObjectType       = $_REQUEST['originObjectType'];
        $originUUID             = $_REQUEST['originUUID'];
        $targetUUID             = $_REQUEST['targetUUID'];   
        $linkType               = $_REQUEST['linkType'];
        $obsNum                 = 1;
        $hashLink               = md5($originUUID . '_' . $obsNum . '_' . $targetUUID . '_' . $linkType);
        
        $link                   = new Table_LinkRelationship();
        $whereClause            = "hash_link = '" . $hashLink . "'";
        //$whereClause            = "origin_uuid = '" . $originUUID . "' and targ_uuid='" . $targetUUID . "' and link_type ='" . $linkType . "'";
        $link->delete($whereClause);
        
        $returnVal = array('uuID' => $originUUID, 'objectType' => $originObjectType);
        echo Zend_Json::encode($returnVal);     
    }
    
    function removePropertyAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        
        $projectUUID            = $_REQUEST['projectUUID'];
        $originObjectType       = $_REQUEST['originObjectType'];
        $uuID                   = $_REQUEST['uuID'];
        $propertyUUID           = $_REQUEST['propertyUUID'];
        $obsNum                 = 1;
        $obsHashText            = md5($projectUUID . "_" . $uuID . '_' . $obsNum . '_' . $propertyUUID);
        
        $observe                = new Table_Observe();
        $whereClause            = "hash_obs = '" . $obsHashText . "'";
        $observe->delete($whereClause);
        
        $returnVal = array('uuID' => $uuID, 'objectType' => $originObjectType);
        echo Zend_Json::encode($returnVal);     
    }
    
    
    //this function merges two items, moving all properties and relations from the old_id to the keep_id
    function mergeItemsAction(){
        $this->_helper->viewRenderer->setNoRender();
        $old_uuid = $_REQUEST['oldID'];
        $keep_uuid = $_REQUEST['keepID'];
        $projectUUID = $_REQUEST['projectUUID'];
        $returnVal = $this->execute_item_merge($old_uuid, $keep_uuid, $projectUUID);
        echo $returnVal;
    }
    
    
    //this function changes spatial containment relations for an item
    function changeParentsAction(){
        $this->_helper->viewRenderer->setNoRender();
        $child_uuid = $_REQUEST['childID'];
        $par_uuid = $_REQUEST['parentID'];
        $projectUUID = $_REQUEST['projectUUID'];
        $returnVal = $this->execute_parent_change($child_uuid, $par_uuid, $projectUUID);
        echo $returnVal;
        
    }
    
    
    private function execute_parent_change($child_uuid, $new_par_uuid, $projectUUID){    
        $undoQueries = array(); //store data for undoing the change
        $redoQueries = array(); //store data for doing & redoing the change
        
        $db = Zend_Registry::get('db');
     
        //now do spatial containment changes
        $select = $db->select()
            ->distinct()
            ->from(
                    array('c' => 'space_contain'),
                    array('c.project_id', 'c.source_id', 'c.hash_all', 'c.parent_uuid')
            )
            ->where('c.child_uuid = ?', $child_uuid)
            ->where ('c.project_id = ?', $projectUUID);
        $dataRows = $db->query($select)->fetchAll();
        
        //Iterate through the old parent items:
        
        if(count($dataRows)>0){
            foreach ($dataRows as $dataRow) {
                
                $foundProjectUUID = $dataRow['project_id'];
                $tabname = $dataRow['source_id'];
                $hash_all = $dataRow['hash_all'];
                $old_parent_uuid = $dataRow['parent_uuid'];
                
                //this creates the queries to undo the change
                $where = array();            
                $where[] = "project_id = '".$foundProjectUUID."' ";
                $where[] = "parent_uuid  = '".$new_par_uuid."' ";
                $where[] = "child_uuid  = '".$child_uuid."' ";
                
                $UnDOquery = array("type" => "delete", "table" => "space_contain", "where" => $where);
                unset($where);
                $undoQueries[] = $UnDOquery; //add to the array
                
    
                $UnDo_data = array('project_id' => $foundProjectUUID,
                                   'source_id' => $tabname,
                                   'hash_all' => $hash_all,
                                   'parent_uuid' => $old_parent_uuid,
                                   'child_uuid' => $child_uuid
                                   );
                
                $UnDOquery = array("type" => "insert", "table" => "space_contain", "data" => $UnDo_data);
           
                $undoQueries[] = $UnDOquery; //add to the array
                //end making the undo queries
                
                
                //now prepare the do and redo queries
                $where = array();            
                $where[] = "project_id = '".$foundProjectUUID."' ";
                $where[] = "parent_uuid  = '".$old_parent_uuid."' ";
                $where[] = "child_uuid  = '".$child_uuid."' ";
                
                $ReDOquery = array("type" => "delete", "table" => "space_contain", "where" => $where);
                unset($where);
                $redoQueries[] = $ReDOquery; //add to the array
                
                $new_hash_all = md5($new_par_uuid . '_' .$child_uuid);
                $ReDo_data = array('project_id' => $foundProjectUUID,
                                   'source_id' => 'manual',
                                   'hash_all' => $new_hash_all,
                                   'parent_uuid' => $new_par_uuid,
                                   'child_uuid' => $child_uuid
                                   );
                
                $ReDOquery = array("type" => "insert", "table" => "space_contain", "data" => $ReDo_data );
           
                $redoQueries[] = $ReDOquery; //add to the array
                
            }//end loop through spatial containment
        }
        else{
            //this creates the queries to undo the change
            $where = array();            
            $where[] = "project_id = '".$projectUUID."' ";
            $where[] = "parent_uuid  = '".$new_par_uuid."' ";
            $where[] = "child_uuid  = '".$child_uuid."' ";
                
            $UnDOquery = array("type" => "delete", "table" => "space_contain", "where" => $where);
            unset($where);
            $undoQueries[] = $UnDOquery; //add to the array
            
            $new_hash_all = md5($new_par_uuid . '_' .$child_uuid);
            $ReDo_data = array('project_id' => $projectUUID,
                                   'source_id' => 'manual',
                                   'hash_all' => $new_hash_all,
                                   'parent_uuid' => $new_par_uuid,
                                   'child_uuid' => $child_uuid
                                   );
                
            $ReDOquery = array("type" => "insert", "table" => "space_contain", "data" => $ReDo_data );
           
            $redoQueries[] = $ReDOquery; //add to the array    
                
        }
        
        $JSON_undos = Zend_Json::encode($undoQueries);
        $JSON_redos = Zend_Json::encode($redoQueries);
        
        $data = array(
                'projectUUID'      => $projectUUID,
                'undo' => $JSON_undos,
                'redo'      => $JSON_redos
            );
        $db->insert('undo_redo', $data);
        
        //now commit the changes!
        $this->execute_undo_redo($redoQueries);
        
        
        $returnVal = array('oldID' => $JSON_undos, 'keepID' => $JSON_redos);
        
        return Zend_Json::encode($returnVal);
    }//end function for execute_item_merge
    
    
    
    
    
    //----------------------------------------------
    //this function finds all duplicate spatial items (same context and name)
    //and merges them with already created items.
    //this is needed because some imported spreadsheets have unseen weird characters
    //that falsely mislable the same spatial items as different
    function mergeTabdupesAction(){
        $this->_helper->viewRenderer->setNoRender();
        $actTable = $_REQUEST['actTable'];
        //$projectUUID = $_REQUEST['projectUUID'];
        
        $db = Zend_Registry::get('db');
        $select = $db->select()
            ->distinct()
            ->from(
                    array('c' => 'space'),
                    array('old_uuid' => 'c.uuid', 'c.space_label', 'c.full_context', 'c.project_id')
            )
            ->join(
                   array('old' => 'space'),
                   'c.space_label = old.space_label
                   AND c.full_context = old.full_context
                   AND old.source_id != "'.$actTable.'" ',
                   array('keep_uuid' => 'old.uuid', 'keep_label' => 'old.space_label', 'keep_context' => 'old.full_context')
            )
            ->where('c.source_id = ?', $actTable);
            
            $dataRows = $db->query($select)->fetchAll();
            //$sql = $select->__toString();
            //echo $sql;
            
            $done_count = count($dataRows);
            //Iterate through the items to change:        
            foreach ($dataRows as $dataRow) 
            {
                $old_uuid = $dataRow['old_uuid'];
                $keep_uuid = $dataRow['keep_uuid'];
                $projectUUID = $dataRow['project_id'];
                $returnVal = $this->execute_item_merge($old_uuid, $keep_uuid, $projectUUID);
            }
        
            echo $done_count." duplicates merged";
    }//end mergeTableDups
    
    
    
    
    //this function adds geospatial data to an item
    function addGeoAction(){
        $this->_helper->viewRenderer->setNoRender();
        $itemID = $_REQUEST['itemID'];
        $geoLat = $_REQUEST['geoLat'];
        $geoLon = $_REQUEST['geoLon'];
        $projectUUID = $_REQUEST['projectUUID'];
        
        //now prepare the undo and redo queries
        $where = array();            
        $where[] = "project_id = '".$projectUUID."' ";
        $where[] = "uuid  = '".$itemID."' ";
            
        $UnDOquery = array("type" => "delete", "table" => "geo_space", "where" => $where);
        unset($where);
        $undoQueries[] = $UnDOquery; //add to the array
        
        $ReDo_data = array('project_id' => $projectUUID,
                               'source_id' => 'manual',
                               'uuid' => $itemID,
                               'latitude' => $geoLat,
                               'longitude' => $geoLon
                               );
            
        $ReDOquery = array("type" => "insert", "table" => "geo_space", "data" => $ReDo_data);
       
        $redoQueries[] = $ReDOquery; //add to the array
        
        //save undo redo queries
        $JSON_undos = Zend_Json::encode($undoQueries);
        $JSON_redos = Zend_Json::encode($redoQueries);
        
        $db = Zend_Registry::get('db');
        $data = array(
            'projectUUID'      => $projectUUID,
            'undo' => $JSON_undos,
            'redo'      => $JSON_redos
        );
        $db->insert('undo_redo', $data);        
        
        
        //now commit the changes!
        $this->execute_undo_redo($undoQueries);
        $this->execute_undo_redo($redoQueries);
        $returnVal = array('undo' => $JSON_undos, 'redo' => $JSON_redos);
        echo Zend_JSON::encode($returnVal);
    }
    
    
    
    //this function adds chronological data to an item
    function addTimeAction(){
        $this->_helper->viewRenderer->setNoRender();
        $itemID = $_REQUEST['itemID'];
        $chronoStart = $_REQUEST['chronoStart'];
        $chronoEnd = $_REQUEST['chronoEnd'];
        $projectUUID = $_REQUEST['projectUUID'];
        
        if($chronoEnd < $chronoStart){
            $chronoEnd = $_REQUEST['chronoStart'];
            $chronoStart = $_REQUEST['chronoEnd'];
        }
        
        //now prepare the undo and redo queries
        $where = array();            
        $where[] = "project_id = '".$projectUUID."' ";
        $where[] = "uuid  = '".$itemID."' ";
            
        $UnDOquery = array("type" => "delete", "table" => "initial_chrono_tag", "where" => $where);
        unset($where);
        $undoQueries[] = $UnDOquery; //add to the array
        
        
        require_once 'App/Util/DateRange.php';
        $App_DateRange = new DateRange();
        $timeLabel = "(".$App_DateRange->bce_ce_note($chronoStart)." - ".$App_DateRange->bce_ce_note($chronoEnd).")";
        
        $ReDo_data = array('project_id' => $projectUUID,
                               'uuid' => $itemID,
                               'creator_uuid' => 'oc',
                               'label' => $timeLabel,
                               'start_time' => $chronoStart,
                               'end_time' => $chronoEnd,
                               'note_id' => 'Default set',
                               'public' => 1
                               );
            
        $ReDOquery = array("type" => "insert", "table" => "initial_chrono_tag", "data" => $ReDo_data);
       
        $redoQueries[] = $ReDOquery; //add to the array
        
        //save undo redo queries
        $JSON_undos = Zend_Json::encode($undoQueries);
        $JSON_redos = Zend_Json::encode($redoQueries);
        
        $db = Zend_Registry::get('db');
        $data = array(
            'projectUUID'      => $projectUUID,
            'undo' => $JSON_undos,
            'redo'      => $JSON_redos
        );
        $db->insert('undo_redo', $data);        
        
        
        //now commit the changes!
        $this->execute_undo_redo($undoQueries);
        $this->execute_undo_redo($redoQueries);
        $returnVal = array('undo' => $JSON_undos, 'redo' => $JSON_redos);
        echo Zend_JSON::encode($returnVal);
    }
    
    
    
    function getpeoplelistAction(){
        $this->_helper->viewRenderer->setNoRender();
        $projectUUID = $_REQUEST['projectUUID'];
        $items = $this->getPeopleItems($projectUUID);
        echo Zend_JSON::encode($items);
    }
    
    /*
     
    http://penelope.oc/importer/edit-transformed-data/multipersonsplit?projectUUID=&A5DDBEA2-B3C8-43F9-8151-33343CBDC857&badID=4ADC50BD-E55D-4421-131B-602196B6B89F&goodID[]=1EFB20BC-BE05-486A-C434-988892277ED5
    
    */
    
    //this function deletes a bad person id, and re-assigns links to an array of good people
    function multipersonsplitAction(){
        $this->_helper->viewRenderer->setNoRender();
        $bad_person_id = $_REQUEST['badID'];
        $good_pers_id_array = $_REQUEST['goodID'];
        
        //case where a comma is used to seperate out different values
        if(count($_REQUEST['goodID'])==1){
            if(substr_count($good_pers_id_array,",")>0){
                $good_pers_id_array = explode(",", $good_pers_id_array);
            }
        }
        
        if(!is_array($good_pers_id_array)){
            $good_pers_id_array = array();
            $good_pers_id_array[] =  $_REQUEST['goodID'];
        }
        
        
        
        $projectUUID = $_REQUEST['projectUUID'];
        $returnVal = $this->execute_multiperson_fix($bad_person_id, $good_pers_id_array, $projectUUID);
        //echo $returnVal;
        echo $good_pers_id_array[0];
    }
    
    
    function removeblankpeopleAction(){
        $this->_helper->viewRenderer->setNoRender();
        $projectUUID = $_REQUEST['projectUUID'];
        $db = Zend_Registry::get('db');
        
        $sql = "SELECT users.uuid, users.combined_name, users.first_name, users.last_name,
        COUNT(links.targ_uuid) AS links_count
        FROM users
        JOIN links ON links.targ_uuid = users.uuid
        WHERE users.uuid NOT IN (SELECT persons.uuid
                        FROM persons)
        GROUP BY links.targ_uuid
        ";
        
        $blankCount = 0;
        $stmt = $db->query($sql);
        $rows = $stmt->fetchAll();
        foreach($rows as $row){
            
            if($row['combined_name'] == null){
                $blankCount ++;
                $where = array();
                $where[] = "project_id  = '".$projectUUID."' ";
                $where[] = "targ_uuid  = '".$row['uuid']."' ";
                //$db->delete('links', $where;
                unset($where);
            }
            
        }
        
        echo $blankCount;
    }
    
    
    
    function undoLastEditAction(){
        $this->_helper->viewRenderer->setNoRender();
        $projectUUID = $_REQUEST['projectUUID'];
        $db = Zend_Registry::get('db');
     
        //first create undo and redo for removing the old item
        $select = $db->select()
            ->distinct()
            ->from(
                array('c' => 'undo_redo'),
                    array('c.projectUUID',
                          'c.undo',
                          'c.ID',
                          'c.status')
            )
            ->where('c.projectUUID = ?', $projectUUID)
            ->where('c.status != "undone"')
            ->order('c.ID DESC');
        
        $dataRows = $db->query($select)->fetchAll();
        if(count($dataRows)>0){
            $undo_string = $dataRows[0]['undo'];
            $undo_JSON = Zend_JSON::decode($undo_string);
            $this->execute_undo_redo($undo_JSON);
            $data = array('status' => 'undone');
            $where = array();            
            $where[] = "projectUUID  = '".$projectUUID."' ";
            $where[] = "ID  = '".$dataRows[0]['ID']."' ";
            $db->update('undo_redo', $data, $where);
        }
        echo $undo_string;
    }
    
    
    
    
    
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
                array('c' => 'observe'),
                    array('c.project_id',
                          'c.subject_type',
                          'c.subject_uuid')
            )
            ->where('c.property_uuid =?', $propertyUUID);
        
        $dataRows = $db->query($select)->fetchAll();
        $cCnt = 0;
        foreach($dataRows as $actRow){
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
                    $rnd_time = round($abs_time/1000,1);
                    $output = (number_format($rnd_time))."K".$suffix;
                }
                else{
                    $rnd_time = round($abs_time/1000000,2);
                    $output = (number_format($rnd_time))."M".$suffix;
                }
	}
	
	return $output;

}//end function
    
    
    
    
    
    
    private function execute_item_merge($old_uuid, $keep_uuid, $projectUUID){    
        $undoQueries = array(); //store data for undoing the change
        $redoQueries = array(); //store data for doing & redoing the change
        
        $db = Zend_Registry::get('db');
     
        //first create undo and redo for removing the old item
        $select = $db->select()
            ->distinct()
            ->from(
                    array('c' => 'space'),
                    array('c.project_id',
                          'c.source_id',
                          'c.hash_fcntxt',
                          'c.space_label',
                          'c.full_context',
                          'c.sample_des',
                          'c.class_uuid'
                          )
            )
            ->where('c.uuid = ?', $old_uuid)
            ->where ('c.project_id = ?', $projectUUID);
        $dataRows = $db->query($select)->fetchAll();
        
        //Iterate through the old items:        
        foreach ($dataRows as $dataRow) 
        {
            $foundProjectUUID = $dataRow['project_id'];
            $tabname = $dataRow['source_id'];
            $hash_fcntxt = $dataRow['hash_fcntxt'];
            $space_label = $dataRow['space_label'];
            $full_context = $dataRow['full_context'];
            $sample_des = $dataRow['sample_des'];
            $class_uuid = $dataRow['class_uuid'];
        
            $UnDo_data = array('project_id' => $foundProjectUUID,
                               'source_id' => $tabname,
                               'hash_fcntxt' => $hash_fcntxt,
                               'uuid' => $old_uuid,
                               'space_label' => $space_label,
                               'full_context' => $full_context,
                               'sample_des' => $sample_des,
                               'class_uuid' => $class_uuid
                               );
            
            $UnDOquery = array("type" => "insert", "table" => "space", "data" => $UnDo_data);
       
            $undoQueries[] = $UnDOquery; //add to the array
        

            $where = array();            
            $where[] = "project_id = '".$foundProjectUUID."' ";
            $where[] = "uuid = '".$old_uuid."' ";
            
            $ReDOquery = array("type" => "delete", "table" => "space", "where" => $where);
            
            unset($where);
            
            $redoQueries[] = $ReDOquery; //add to the array
        
        }//end loop through item label
        
        
        
        //now do spatial containment changes
        $select = $db->select()
            ->distinct()
            ->from(
                    array('c' => 'space_contain'),
                    array('c.project_id', 'c.source_id', 'c.hash_all', 'c.child_uuid')
            )
            ->where('c.parent_uuid = ?', $old_uuid)
            ->where ('c.project_id = ?', $projectUUID);
        $dataRows = $db->query($select)->fetchAll();
        
        //Iterate through the child items:        
        foreach ($dataRows as $dataRow) 
        {
            $foundProjectUUID = $dataRow['project_id'];
            $tabname = $dataRow['source_id'];
            $hash_all = $dataRow['hash_all'];
            $child_uuid = $dataRow['child_uuid'];
            
            //this creates the queries to undo the change
            $where = array();            
            $where[] = "project_id = '".$foundProjectUUID."' ";
            $where[] = "parent_uuid  = '".$keep_uuid."' ";
            $where[] = "child_uuid  = '".$child_uuid."' ";
            
            $UnDOquery = array("type" => "delete", "table" => "space_contain", "where" => $where);
            unset($where);
            $undoQueries[] = $UnDOquery; //add to the array
            

            $UnDo_data = array('project_id' => $foundProjectUUID,
                               'source_id' => $tabname,
                               'hash_all' => $hash_all,
                               'parent_uuid' => $old_uuid,
                               'child_uuid' => $child_uuid
                               );
            
            $UnDOquery = array("type" => "insert", "table" => "space_contain", "data" => $UnDo_data);
       
            $undoQueries[] = $UnDOquery; //add to the array
            //end making the undo queries
            
            
            //now prepare the do and redo queries
            $where = array();            
            $where[] = "project_id = '".$foundProjectUUID."' ";
            $where[] = "parent_uuid  = '".$old_uuid."' ";
            $where[] = "child_uuid  = '".$child_uuid."' ";
            
            $ReDOquery = array("type" => "delete", "table" => "space_contain", "where" => $where);
            unset($where);
            $redoQueries[] = $ReDOquery; //add to the array
            
            $new_hash_all = md5($keep_uuid . '_' .$child_uuid);
            $ReDo_data = array('project_id' => $foundProjectUUID,
                               'source_id' => $tabname,
                               'hash_all' => $new_hash_all,
                               'parent_uuid' => $keep_uuid,
                               'child_uuid' => $child_uuid
                               );
            
            $ReDOquery = array("type" => "insert", "table" => "space_contain", "data" => $ReDo_data );
       
            $redoQueries[] = $ReDOquery; //add to the array
            
        }//end loop through spatial containment
        
        
        //now do linking changes
        $link_changes = $this->alter_old_links($old_uuid, $keep_uuid, $projectUUID, $undoQueries, $redoQueries);
        $undoQueries = $link_changes["undo"];
        $redoQueries = $link_changes["redo"];
        unset($link_changes);
        
        $JSON_undos = Zend_Json::encode($undoQueries);
        $JSON_redos = Zend_Json::encode($redoQueries);
        
        $data = array(
            'projectUUID'      => $projectUUID,
            'undo' => $JSON_undos,
            'redo'      => $JSON_redos
        );
        $db->insert('undo_redo', $data);        
        
        
        //now commit the changes!
        $this->execute_undo_redo($redoQueries);
        
        
        $returnVal = array('oldID' => $JSON_undos, 'keepID' => $JSON_redos);
        
        return Zend_Json::encode($returnVal);
    }//end function for execute_item_merge
    
    
    //this function removes links for an item that you want to remove
    //it returns an array of undo and redo queries
    private function remove_unwanted_links($old_uuid, $projectUUID, $undoQueries, $redoQueries){
        
        $db = Zend_Registry::get('db');
        
        //now do linking changes
        $select = $db->select()
            ->distinct()
            ->from(
                    array('c' => 'links'),
                    array('c.project_id',
                          'c.source_id',
                          'c.hash_link',
                          'c.link_type',
                          'c.link_uuid',
                          'c.origin_type',
                          'c.origin_uuid',
                          'c.origin_obs',
                          'c.targ_type',
                          'c.targ_uuid',
                          'c.targ_obs'
                          )
            )
            ->where('c.origin_uuid = "'.$old_uuid.'" OR c.targ_uuid = "'.$old_uuid.'" ')
            ->where ('c.project_id = ?', $projectUUID);
        $dataRows = $db->query($select)->fetchAll();
        
        //Iterate through the child items:        
        foreach ($dataRows as $dataRow) 
        {
            $foundProjectUUID = $dataRow['project_id'];
            $origin_uuid = $dataRow['origin_uuid'];
            $targ_uuid = $dataRow['targ_uuid'];
            
            //this creates the queries to undo the change
            $UnDo_data = array('project_id' => $foundProjectUUID,
                               'source_id' => $dataRow['source_id'],
                               'hash_link' => $dataRow['hash_link'],
                               'link_type' => $dataRow['link_type'],
                               'link_uuid' => $dataRow['link_uuid'],
                               'origin_type' => $dataRow['origin_type'],
                               'origin_uuid' => $dataRow['origin_uuid'],
                               'origin_obs' => $dataRow['origin_obs'],
                               'targ_type' => $dataRow['targ_type'],
                               'targ_uuid' => $dataRow['targ_uuid'],
                               'targ_obs' => $dataRow['targ_obs']
                               );
            
            $UnDOquery = array("type" => "insert", "table" => "links", "data" => $UnDo_data);
       
            $undoQueries[] = $UnDOquery; //add to the array
            //end making the undo queries
            
            
            //now prepare the do and redo queries
            $where = array();            
            $where[] = "project_id = '".$foundProjectUUID."' ";
            $where[] = "origin_uuid  = '".$origin_uuid."' ";
            $where[] = "targ_uuid  = '".$targ_uuid."' ";
            
            $ReDOquery = array("type" => "delete", "table" => "links", "where" => $where);
            unset($where);
            $redoQueries[] = $ReDOquery; //add to the array
            
        }//end loop through linking relations
        
        return array("undo" => $undoQueries, "redo"=> $redoQueries);
    }
    
    
    private function alter_old_links($old_uuid, $keep_uuid, $projectUUID, $undoQueries, $redoQueries){
        
        $db = Zend_Registry::get('db');
        //now do linking changes
        $select = $db->select()
            ->distinct()
            ->from(
                    array('c' => 'links'),
                    array('c.project_id',
                          'c.source_id',
                          'c.hash_link',
                          'c.link_type',
                          'c.link_uuid',
                          'c.origin_type',
                          'c.origin_uuid',
                          'c.origin_obs',
                          'c.targ_type',
                          'c.targ_uuid',
                          'c.targ_obs'
                          )
            )
            ->where('c.origin_uuid = "'.$old_uuid.'" OR c.targ_uuid = "'.$old_uuid.'" ')
            ->where ('c.project_id = ?', $projectUUID);
        $dataRows = $db->query($select)->fetchAll();
        
        //Iterate through the child items:        
        foreach ($dataRows as $dataRow) 
        {
            $foundProjectUUID = $dataRow['project_id'];
            $origin_uuid = $dataRow['origin_uuid'];
            $targ_uuid = $dataRow['targ_uuid'];
            
            //this creates the queries to undo the change
            
            if($old_uuid == $origin_uuid){
                $where = array();            
                $where[] = "project_id = '".$foundProjectUUID."' ";
                $where[] = "origin_uuid  = '".$keep_uuid."' ";
                $where[] = "targ_uuid  = '".$targ_uuid."' ";
                
                $UnDOquery = array("type" => "delete", "table" => "links", "where" => $where);
                unset($where);
                $undoQueries[] = $UnDOquery; //add to the array
                
            }//end case where the old item is the origin item
            
            if($old_uuid == $targ_uuid){
                $where = array();            
                $where[] = "project_id = '".$foundProjectUUID."' ";
                $where[] = "origin_uuid  = '".$origin_uuid."' ";
                $where[] = "targ_uuid  = '".$keep_uuid."' ";
                
                $UnDOquery = array("type" => "delete", "table" => "links", "where" => $where);
                unset($where);
                $undoQueries[] = $UnDOquery; //add to the array
            }//end case where the old item is the target item
            
            $UnDo_data = array('project_id' => $foundProjectUUID,
                               'source_id' => $dataRow['source_id'],
                               'hash_link' => $dataRow['hash_link'],
                               'link_type' => $dataRow['link_type'],
                               'link_uuid' => $dataRow['link_uuid'],
                               'origin_type' => $dataRow['origin_type'],
                               'origin_uuid' => $dataRow['origin_uuid'],
                               'origin_obs' => $dataRow['origin_obs'],
                               'targ_type' => $dataRow['targ_type'],
                               'targ_uuid' => $dataRow['targ_uuid'],
                               'targ_obs' => $dataRow['targ_obs']
                               );
            
            $UnDOquery = array("type" => "insert", "table" => "links", "data" => $UnDo_data);
       
            $undoQueries[] = $UnDOquery; //add to the array
            //end making the undo queries
            
            
            //now prepare the do and redo queries
            $where = array();            
            $where[] = "project_id = '".$foundProjectUUID."' ";
            $where[] = "origin_uuid  = '".$origin_uuid."' ";
            $where[] = "targ_uuid  = '".$targ_uuid."' ";
            
            $ReDOquery = array("type" => "delete", "table" => "links", "where" => $where);
            unset($where);
            $redoQueries[] = $ReDOquery; //add to the array
            
            if($old_uuid == $origin_uuid){
                $hashLink   = md5($keep_uuid . '_' . $dataRow['origin_obs'] . '_' . $targ_uuid . '_' . $dataRow['link_type']);
                $ReDo_data = array('project_id' => $foundProjectUUID,
                                   'source_id' => $dataRow['source_id'],
                                   'hash_link' => $hashLink,
                                   'link_type' => $dataRow['link_type'],
                                   'link_uuid' => $dataRow['link_uuid'],
                                   'origin_type' => $dataRow['origin_type'],
                                   'origin_uuid' => $keep_uuid,
                                   'origin_obs' => $dataRow['origin_obs'],
                                   'targ_type' => $dataRow['targ_type'],
                                   'targ_uuid' => $targ_uuid,
                                   'targ_obs' => $dataRow['targ_obs']
                                   );
            }
            
            if($old_uuid == $targ_uuid){
                $hashLink   = md5($origin_uuid. '_' . $dataRow['origin_obs'] . '_' . $keep_uuid  . '_' . $dataRow['link_type']);
                $ReDo_data = array('project_id' => $foundProjectUUID,
                                   'source_id' => $dataRow['source_id'],
                                   'hash_link' => $hashLink,
                                   'link_type' => $dataRow['link_type'],
                                   'link_uuid' => $dataRow['link_uuid'],
                                   'origin_type' => $dataRow['origin_type'],
                                   'origin_uuid' => $origin_uuid,
                                   'origin_obs' => $dataRow['origin_obs'],
                                   'targ_type' => $dataRow['targ_type'],
                                   'targ_uuid' => $keep_uuid,
                                   'targ_obs' => $dataRow['targ_obs']
                                   );
            }
            
            
            $ReDOquery = array("type" => "insert", "table" => "links", "data" => $ReDo_data );
       
            $redoQueries[] = $ReDOquery; //add to the array
            
        }//end loop through linking relations
    
        return array("undo" => $undoQueries, "redo"=> $redoQueries);
    }
    
    
    
    //this function takes links to a "bad" person and divides them among good people
    //the record for the bad person is then deleted
    private function execute_multiperson_fix($bad_person_id, $good_pers_id_array, $projectUUID){
        $undoQueries = array(); //store data for undoing the change
        $redoQueries = array(); //store data for doing & redoing the change
        
        
        //this removes the records for the bad person on the links table
        //it also updates links from the bad person to each member of the good person array
        foreach($good_pers_id_array AS $keep_uuid){
            $link_changes = $this->alter_old_links($bad_person_id, $keep_uuid, $projectUUID, $undoQueries, $redoQueries);
            $undoQueries = $link_changes["undo"];
            $redoQueries = $link_changes["redo"];
            unset($link_changes);
        }
        
        
        $person_remove = $this->bad_person_remove($bad_person_id, $projectUUID, $undoQueries, $redoQueries);
        $undoQueries = $person_remove["undo"];
        $redoQueries = $person_remove["redo"];
        unset($person_remove);
        
        
        $JSON_undos = Zend_Json::encode($undoQueries);
        $JSON_redos = Zend_Json::encode($redoQueries);
        
        $data = array(
            'projectUUID'      => $projectUUID,
            'undo' => $JSON_undos,
            'redo'      => $JSON_redos
        );
        
        if(count($good_pers_id_array)>0){
            $db = Zend_Registry::get('db');
            $db->insert('undo_redo', $data);        
            
            //now commit the changes!
            $this->execute_undo_redo($redoQueries);
        }
        
        $returnVal = array('undo' => $JSON_undos, 'redo' => $JSON_redos);
        return Zend_Json::encode($returnVal);
    }
    
    
    private function bad_person_remove($bad_person_id, $projectUUID, $undoQueries, $redoQueries, $doLinks = false){
        
        if($doLinks){
            $link_changes = $this->remove_unwanted_links($bad_person_id, $projectUUID, $undoQueries, $redoQueries);
            $undoQueries = $link_changes["undo"];
            $redoQueries = $link_changes["redo"];
            unset($link_changes);
        }
        
        $db = Zend_Registry::get('db');
     
        //second create undo and redo for the bad person in the user table
        $select = $db->select()
            ->distinct()
            ->from(
                    array('c' => 'users'),
                    array('c.pk_user',
                          'c.uuid',
                          'c.combined_name',
                          'c.first_name',
                          'c.last_name',
                          'c.mid_init',
                          'c.initials',
                          'c.email',
                          'c.username',
                          'c.password',
                          'c.fk_user_last_modified',
                          'c.last_modified_timestamp',
                          'c.source_id'
                          )
            )
            ->where('c.uuid = "'.$bad_person_id.'" ');
        $dataRows = $db->query($select)->fetchAll();
        
        $UnDo_data = array();
        //Iterate through the old items:        
        foreach ($dataRows as $field => $value) 
        {
            $UnDo_data[$field] = $value;
        }
        $UnDOquery = array("type" => "insert", "table" => "users", "data" => $UnDo_data);
        
        if(count($dataRows)>0){
            $undoQueries[] = $UnDOquery;
        }
        
        //now prepare the do and redo queries
        $where = array();            
        $where[] = "uuid  = '".$bad_person_id."' ";
        $ReDOquery = array("type" => "delete", "table" => "users", "where" => $where);
        unset($where);
        $redoQueries[] = $ReDOquery; //add to the array
        
        
        //second create undo and redo for the bad person in the user table
        $select = $db->select()
            ->distinct()
            ->from(
                    array('c' => 'persons'),
                    array('c.project_id',
                          'c.source_id',
                          'c.uuid',
                          'c.shrt_pers_id',
                          'c.combined_name',
                          'c.first_name',
                          'c.last_name',
                          'c.mid_init',
                          'c.initials',
                          'c.org_name'
                          )
            )
            ->where('c.uuid = "'.$bad_person_id.'" ');
        $dataRows = $db->query($select)->fetchAll();
        
        $UnDo_data = array();
        //Iterate through the old items:        
        foreach ($dataRows as $field => $value) 
        {
            $UnDo_data[$field] = $value;
        }
        $UnDOquery = array("type" => "insert", "table" => "persons", "data" => $UnDo_data);
         if(count($dataRows)>0){
            $undoQueries[] = $UnDOquery;
        }
        
        //now prepare the do and redo queries
        $where = array();            
        $where[] = "uuid  = '".$bad_person_id."' ";
        $ReDOquery = array("type" => "delete", "table" => "persons", "where" => $where);
        unset($where);
        $redoQueries[] = $ReDOquery; //add to the array
        
        return array("undo" => $undoQueries, "redo"=> $redoQueries);
    }
    
    
    
    
    
    //this function executes the undo and redo queries
    private function execute_undo_redo($query_data_array){
        
        
        $db = Zend_Registry::get('db');
        
        foreach($query_data_array as $actDO_query){
            
            if($actDO_query['type'] == 'insert'){
                try{
                    $db->insert($actDO_query['table'], $actDO_query['data']);
                }
                catch(Exception $e){
                
                }
            }
            
            if($actDO_query['type'] == 'delete'){
                $db->delete($actDO_query['table'], $actDO_query['where']);
            }
            
        }//end loop through queries
        
        
        
    }//end do execute_undo_redo
    
    
    
    
    
    
    
    
    
}