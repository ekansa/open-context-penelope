<?php

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
        
        //Get Locations or Objects that have containment:
        $items = $this->getContainmentItems($projectUUID);
        if(sizeof($items > 0))
        {
            $dataStores[$idx] = array(
                'dataStoreName' => 'Containment Locations or Objects',
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
                'dataStoreName' => 'Other Locations or Objects',
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
                    'objectType'=> App_Constants::SPATIAL,
                    'children'  => array()
                )
            );
            
            //get children's children (recurse):
            $items = $this->getItems($projectUUID, $childUUID, $items, $level);
            
        }//end foreach $dataRow
        return $items;
    }
    
    
    private function getContainmentItems($projectUUID)
    {
        $db = Zend_Registry::get('db');
        
        //get all Locations or Objects that are spatially contained:
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
            ->where ('c1.parent_uuid is not null')
            ->order('data_value');
        $dataRows = $db->query($select)->fetchAll();
        //Zend_Debug::dump(sizeof($dataRows));
        //return;
        $dataStores = array();
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
                    'objectType'=> App_Constants::SPATIAL,
                    'top'       => 'true',
                    'children'  => array()
                )
            );
            
            $items = $this->getItems($projectUUID, $parentUUID, $items, $level);
        }
        return $items;
    }
    
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
                    'objectType'=> App_Constants::SPATIAL,
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
        $uuID           = $_REQUEST['uuID'];
        $objectType     = $_REQUEST['objectType'];        
        $projectUUID    = $_REQUEST['projectUUID'];
        $this->project  = Project::getProjectByUUID($projectUUID);
        
        $contextItem = new ContextItem($uuID, $objectType, $this->project);
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
    
}