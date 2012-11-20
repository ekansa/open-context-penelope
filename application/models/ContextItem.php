<?php
class ContextItem
{
    public $uuID;
    public $objectType;
    public $name;
    public $classLabel;
    public $classIcon;
    public $classIconSmall;
    public $notes;
    public $objectSpecificProperties= array();  //an array of arrays of name-value pairings
    public $descriptiveProperties   = array();    
    public $people                  = array();    
    public $locationsObjects        = array();    
    public $diaries                 = array();   
    public $resources               = array();     
    public $childrenItems           = array();
    public $parentItems           = array();
    
    function ContextItem($_uuID, $_objectType) //where $_data is a "ResultRowObject"
    {
        $db = Zend_Registry::get('db');
        //make class references:
        Zend_Loader::loadClass('Table_Space');
        Zend_Loader::loadClass('Table_User');
        Zend_Loader::loadClass('Table_Resource');
        Zend_Loader::loadClass('Table_Diary');
        //$space = new Table_Space();
        
        $this->uuID         = $_uuID;
        $this->objectType   = $_objectType;
        
        if(!$_objectType){
            $this->objectType = App_Constants::SPATIAL;
        }
        
        // 1.   Query for name and class (if applicable):
        $this->getNameAndClass();

        // 2.   Query for associated properties and NOTES in the observe table
        //      (and populate object properties):
        $this->getNotesAndPropertiesEric();
        
        // 3.   Query for associated people in the users table
        //      (and populate object properties):
        $this->getPeople();
        
        // 4.   Query for associated Locations or Objects in the links table
        //      (and populate object properties):
        $this->getLinkedLocationsObjects();
        
        // 5.   Query for associated Media and Resources in the resource table
        //      (and populate object properties):
        $this->getLinkedResources();
        
        // 6.   Query for associated Diary Items in the diary table
        //      (and populate object properties):
        $this->getLinkedDiaries();

        // 7.   Query for children items
        $this->getChildrenItems();
        
        // 8.   Query for parent items
        $this->getParentItem();
    }
    
    private function getParentItem(){
        
        $childUUID = $this->uuID;
        $db = Zend_Registry::get('db');
        $select = $db->select()
            ->distinct()
            ->from(
                    array('c' => 'space_contain'),
                    array('c.parent_uuid')
            )
            ->join(
                    array('s' => 'space'),
                    'c.parent_uuid = s.uuid',
                    array('s.uuid', 'data_value' => 's.space_label')
            )
            ->where('c.child_uuid = ?', $childUUID)
            ->order('data_value');
        $dataRows = $db->query($select)->fetchAll();

        //Iterate through the child items:        
        foreach ($dataRows as $dataRow) 
        {
            $parentUUID     = $dataRow['uuid'];
            $parentValue    = $dataRow['data_value'];
            
            array_push($this->parentItems,
                            array(
                                'id'    => $parentUUID,
                                'name'   =>  $parentValue,
                                'linkName'  => "parent",
                                'class'         => 'do later',
                                'objectType'    => $this->objectType
                            )
                        );
        }
    }
    
    
    
    private function getChildrenItems(){
        
        $parentUUID = $this->uuID;
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
                    array('sc' => 'sp_classes'),
                    'sc.class_uuid = s.class_uuid',
                    array('class' => 'sc.class_label')
            )
            ->joinLeft(
                    array('sc2' => 'sp_classes'),
                    'sc2.class_label = s.class_uuid',
                    array('classAlt' => 'sc2.class_label')
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
            $childClass = $dataRow['class'];
            if(!$childClass){
                $childClass = $dataRow['classAlt'];
            }
            
            array_push($this->childrenItems,
                            array(
                                'id'    => $childUUID,
                                'name'   =>  $childValue,
                                'linkName'  => $childCNT." items",
                                'linkClass'         => $childClass,
                                'objectType'    => $this->objectType
                            )
                        );
        }
    }
    
    
    private function getNameAndClass()
    {
        switch($this->objectType)
        {
            case App_Constants::SPATIAL:
                $space = new Table_Space();
                $spaceRow = $space->fetchRow("uuid  = '" . $this->uuID . "'");
                $this->name = $spaceRow->space_label;
                
                // 2.  Query for associated sp_classes record (and populate object properties):
                $classRows   = $spaceRow->findDependentRowset('Table_Class');
                //only 1 class per spaceUUID, even though we're using foreach loop:
                foreach($classRows as $classRow)
                {
                    //array_push($this->objectSpecificProperties, array('Class', $classRow->class_label));
                    //array_push($this->objectSpecificProperties, array('Class Icon', $classRow->class_icon));
                    //array_push($this->objectSpecificProperties, array('Class Icon Small', $classRow->sm_class_icon));
                    $this->classLabel       = $classRow->class_label;
                    $this->classIcon        = $classRow->class_icon;
                    $this->classIconSmall   = $classRow->sm_class_icon;
                }
                
                //case where there's a problem and the class is mapped to the class label
                if(!($this->classLabel)){
                    $db = Zend_Registry::get('db');
                    $select = $db->select()
                    ->distinct()
                    ->from(
                            array('s' => 'space')
                    )
                    ->join(
                            array('sc2' => 'sp_classes'),
                            'sc2.class_label = s.class_uuid',
                            array('classLabel' => 'sc2.class_label',
                                  'classIcon' => 'sc2.class_icon',
                                  'classIconSmall' => 'sc2.sm_class_icon')
                    )    
                    ->where('s.uuid = ?', $this->uuID);
                    $dataRows = $db->query($select)->fetchAll();
                    foreach ($dataRows as $dataRow) 
                    {
                        $this->classLabel       = $dataRow['classLabel'];
                        $this->classIcon        = $dataRow['classIcon'];
                        $this->classIconSmall   = $dataRow['classIconSmall'];   
                    }
                    
                }//end case with bad class
                
                break;
            case App_Constants::PERSON:
                /*$person             = new Table_User();
                $personRow          = $person->fetchRow("uuid  = '" . $this->uuID . "'");
                $this->name         = $personRow->combined_name;*/
                $db = Zend_Registry::get('db');
                $select = $db->select()
                    ->distinct()
                    ->from(
                        array('l' => 'links'),
                        array('roleName' => 'l.link_type', )
                    )
                    ->join(
                        array('u' => 'users'),
                        'l.targ_uuid = u.uuid',
                        array('u.combined_name')
                    )
                    ->where('u.uuid = ?', $this->uuID);
                $personRows = $db->query($select)->fetchAll();
                $this->classLabel   = $this->objectType;
                foreach($personRows as $personRow)
                {
                    $this->name         = $personRow['combined_name'];
                    $doAdd = true;
                    foreach($this->objectSpecificProperties as $prop)
                    {
                        if($prop['value'] == $personRow['combined_name'])
                        {
                            $doAdd = false;
                            break;
                        }
                    }
                    if($doAdd === true)
                    {
                        array_push($this->objectSpecificProperties,
                            array(
                                'objectUUID'    => $this->uuID,
                                'variableName'   => 'Full Name',
                                'value'         => $personRow['combined_name'],
                                'field'         => 'combined_name',
                                'objectType'    => $this->objectType
                            )
                        );
                    }
                    array_push($this->objectSpecificProperties,
                        array(
                            'objectUUID'    => $this->uuID,
                            'variableName'   => 'Role',
                            'value'         => $personRow['roleName'],
                            'field'         => 'link_type',
                            'objectType'    => $this->objectType
                        )
                    );
                }
                break;
            case App_Constants::MEDIA:                
                $resource           = new Table_Resource();
                $resourceRow        = $resource->fetchRow("uuid  = '" . $this->uuID . "'");
                $this->name         = $resourceRow->res_filename;
                $this->classLabel   = $this->objectType;
                array_push($this->objectSpecificProperties,
                    array(
                        'objectUUID'    => $this->uuID,
                        'variableName'   => 'File Name',
                        'value'         => $resourceRow->res_filename,
                        'field'         => 'res_filename',
                        'objectType'    => $this->objectType
                    )
                );
                array_push($this->objectSpecificProperties,
                    array(
                        'objectUUID'    => $this->uuID,
                        'variableName'   => 'Full File Path',
                        'value'         => $resourceRow->res_path_source,
                        'field'         => 'res_path_source',
                        'objectType'    => $this->objectType
                    )
                );
                break;
            case App_Constants::DIARY:
                $diary              = new Table_Diary();
                $diaryRow           = $diary->fetchRow("uuid  = '" . $this->uuID . "'");
                $this->name         = 'Diary / Narrative: ' . $diaryRow->diary_text_original;                
                if(strlen($this->name) > 40)
                    $this->name     = substr($this->name, 0, 40) . '...';
                $this->classLabel   = $this->objectType;
                array_push($this->objectSpecificProperties,
                    array(
                        'objectUUID'    => $this->uuID,
                        'variableName'  => 'Narrative Entry',
                        'value'         => $diaryRow->diary_text_original,
                        'field'         => 'diary_text_original',
                        'objectType'    => $this->objectType
                    )
                );
                break;
        }
    }
    
    private function getNotesAndProperties()
    {
        Zend_Loader::loadClass('Diary');
        Zend_Loader::loadClass('Space');
        Zend_Loader::loadClass('Person');
        Zend_Loader::loadClass('Resource');
        
        $item = null;
        switch($this->objectType)
        {
            case App_Constants::SPATIAL:
                $item = new Space($this->uuID, $this->project);
                break;   
            case App_Constants::DIARY:
                $item = new Diary($this->uuID, $this->project);
                break;   
            case App_Constants::PERSON:
                $item = new Person($this->uuID, $this->project);
                break;   
            case App_Constants::MEDIA:
                $item = new Resource($this->uuID, $this->project);
                break;   
        }
        $observation   = $item->getObservation();
        $notes          = $item->getNotes();
        if($observation != null)
        {
            foreach($observation->properties as $property)
            {
                array_push($this->descriptiveProperties ,
                    array(
                        'propertyUUID'  => $property->propertyUUID,
                        'objectUUID'    => $this->uuID,
                        'variableName'  => $property->variable->varLabel,
                        'value'         => $property->value->valText,
                        'objectType'    => $this->objectType
                    )
                );
            }
        }
        
        foreach($notes as $note)
            $this->notes = $note->noteText;           
    }

    private function getNotesAndPropertiesEric()
    {
        $db = Zend_Registry::get('db');
        //v_observe is a view that joins observations to their corresponding variable and value data
        $select = $db->select()
            ->from(
                array('s' => 'space')
            )
            ->join(
                   array('o' => 'observe'),
                   's.uuid = o.subject_uuid',
                   array('o.property_uuid', 'o.subject_uuid')
            )
            ->join(
                   array('p' => 'properties'),
                   'o.property_uuid = p.property_uuid',
                   array('p.variable_uuid')
            )
            ->join(
                   array('v' => 'var_tab'),
                   'p.variable_uuid = v.variable_uuid',
                   array('v.var_label')
            )
            ->join(
                   array('vl' => 'val_tab'),
                   'p.value_uuid = vl.value_uuid',
                   array('vl.val_text')
            )
            ->where('s.uuid = ?', $this->uuID);
        
        //$db->getProfiler()->setEnabled(true);
        //$profiler = $db->getProfiler();
        
        $observationRows = $db->query($select)->fetchAll();
        
        //$query = $profiler->getLastQueryProfile();
        //echo $query->getQuery();
        //javascript:showDetail('CDC978CD-4A67-45A6-3C2B-C823B0E49A3E',%20'Locations%20/%20Objects')
        
        foreach($observationRows as $observationRow)
        {
            //propertyUUID, objectUUID, varText, valText, objectType
            if($observationRow['variable_uuid'] != 'NOTES')
            {
                array_push($this->descriptiveProperties ,
                    array(
                        'propertyUUID'  => $observationRow['property_uuid'],
                        'objectUUID'    => $observationRow['subject_uuid'],
                        'variableName'  => $observationRow['var_label'],
                        'value'         => $observationRow['val_text'],
                        'objectType'    => $this->objectType
                    )
                );
            }
            else
            {
                $this->notes = $observationRow['val_text'];    
            }
        }

    }
    
        
    private function getPeople()
    {
        $db = Zend_Registry::get('db');
        $select = $db->select()
            ->distinct()
            ->from(
                array('l' => 'links'),
                array('l.targ_uuid', 'role' => 'l.link_type')
            )
            ->join(
                   array('p' => 'users'),
                   'p.uuid = l.targ_uuid',
                   array('p.combined_name')
            )
            /*->joinLeft(
                array('r' => 'persons_st_des'),
                'r.uuid = l.targ_uuid',
                array('r.stnd_vals')
            )*/
            //->where('r.stnd_var = ?', 'role')
            //->orWhere('r.stnd_var is null')
            ->where('l.origin_uuid = ?', $this->uuID);
        $peopleRows = $db->query($select)->fetchAll();
        foreach($peopleRows as $peopleRow)
        {
            array_push($this->people ,
                array(
                    'id'            => $peopleRow['targ_uuid'],
                    'linkName'      => $peopleRow['role'],
                    'name'          => $peopleRow['combined_name'],
                    'objectType'    => App_Constants::PERSON
                    
                )
            );
        }      
    }
    
    private function getLinkedLocationsObjects()
    {
        $db = Zend_Registry::get('db');
        $select = $db->select()
            ->distinct()
            ->from(
                array('l' => 'links'),
                array('l.targ_uuid', 'l.link_type')
            )
            ->join(
                   array('s' => 'space'),
                   's.uuid = l.targ_uuid',
                   array('s.space_label', 's.uuid')
            )
            ->where('l.origin_uuid = ?', $this->uuID);
        $locationObjectRows = $db->query($select)->fetchAll();
        //Zend_Debug::dump($db->query($select));
        foreach($locationObjectRows as $locationObjectRow)
        {
            $locationObjectItem = array(
                'linkName' => $locationObjectRow['link_type'],
                'name' => $locationObjectRow['space_label'],
                'id' => $locationObjectRow['uuid'],
                'objectType' => App_Constants::SPATIAL);
            array_push($this->locationsObjects , $locationObjectItem);    
        }
    }
    
    private function getLinkedResources()
    {
        $db = Zend_Registry::get('db');
        $select = $db->select()
            ->distinct()
            ->from(
                array('l' => 'links'),
                array('l.targ_uuid', 'l.link_type')
            )
            ->join(
                   array('r' => 'resource'),
                   'r.uuid = l.targ_uuid',
                   array('r.res_filename', 'r.uuid')
            )
            ->where('l.origin_uuid = ?', $this->uuID);
        $resourceRows = $db->query($select)->fetchAll();
        //Zend_Debug::dump($resourceRows);
        foreach($resourceRows as $resourceRow)
        {
            $resourceItem = array(
                'linkName' => $resourceRow['link_type'],
                'name' => $resourceRow['res_filename'],
                'id' => $resourceRow['uuid'],
                'objectType' => 'Media (various)');
            array_push($this->resources , $resourceItem);    
        }
    }
    
    private function getLinkedDiaries()
    {
        $db = Zend_Registry::get('db');
        $select = $db->select()
            ->distinct()
            ->from(
                array('l' => 'links'),
                array('l.targ_uuid', 'l.link_type')
            )
            ->join(
                   array('d' => 'diary'),
                   'd.uuid = l.targ_uuid',
                   array('d.diary_text_original', 'd.uuid')
            )
            ->where('l.origin_uuid = ?', $this->uuID);
        $diaryRows = $db->query($select)->fetchAll();
        //Zend_Debug::dump($db->query($select));
        foreach($diaryRows as $diaryRow)
        {
            $name = $diaryRow['diary_text_original'];
            if(strlen($name) > 20)
                $name = substr($name, 0, 20) . '...';  
            $diaryItem = array(
                'linkName' => $diaryRow['link_type'],
                'name' => $name,
                'id' => $diaryRow['uuid'],
                'objectType' => 'Diary / Narrative');
            array_push($this->diaries , $diaryItem);    
        }
    }
}