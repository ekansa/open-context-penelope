<?php

class Importer_RelationshipsController extends Zend_Controller_Action
{
    public $counter = 0;
    function init()
    {  
        $this->view->baseUrl = $this->_request->getBaseUrl();
        Zend_Loader::loadClass('Zend_Debug');
    }
    
    function queryForRelationshipsAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $dataTableName = $_REQUEST['dataTableName'];
        $db = Zend_Registry::get('db');

        $select = $db->select()
        ->distinct()
        ->from(array('f' => 'field_links'), array('id' => new Zend_Db_Expr("CONCAT(f.fk_field_parent, '_', f.fk_field_child, '_', f.fk_link_type)")))
        ->joinLeft(
                   array('s1' => 'field_summary'),
                   'f.field_parent_name = s1.field_name and f.source_id = s1.source_id',
                   array('parent_name' => 'field_label')
        )
        ->joinLeft(
                   array('s2' => 'field_summary'),
                   'f.field_child_name = s2.field_name and f.source_id = s2.source_id',
                   array('child_name' => 'field_label')
        )
        ->joinLeft(
                   array('lu' => 'w_lu_relationship_types'),
                   'f.fk_link_type = lu.pk_relationship_type',
                   array('verb' => 'RELATIONSHIP_VERB', 'relationship' => 'RELATIONSHIP_TYPE')
        )
        ->where ("f.source_id = '" . $dataTableName . "'")
        ->order(array('relationship', 'parent_name', 'child_name'));
        //->where ("f.source_id = '" . $dataTableName . "' and f.fk_link_type = 1");

        $stmt = $db->query($select);
        $rows = $stmt->fetchAll();
        
        if(sizeof($rows) == 0)
            return "";
        //echo Zend_Json::encode($rows);
        //return;
        
        $layout = array();
        
        $layout[0] = array(
            'name'      =>  '&nbsp;',
            'width'     =>  '60px',
            'formatter' => 'deleteRelBut'
        );
        $layout[1] = array(
            'field'     =>  'parent_name',
            'name'      =>  'Origin',
            'width'     =>  '90px'
        );
        $layout[2] = array(
            'field'     =>  'verb',
            'name'      =>  'Relation',
            'width'     =>  '120px'
        );
        $layout[3] = array(
            'field'     =>  'child_name',
            'name'      =>  'Target',
            'width'     =>  '90px'
        );
        
        Zend_Loader::loadClass('Layout_DataGridHelper');
        $dgHelper = new Layout_DataGridHelper();
        $dgHelper->setDataRecords($rows, "id");
        $dgHelper->layout = $layout;
        echo Zend_Json::encode($dgHelper);
    }
    
    /**This function returns an array of sample
     * links which are not containment links
     */
    function getOtherRelationshipsAction()
    {
        $this->_helper->viewRenderer->setNoRender();
            
        $dataTableName  = $_REQUEST['dataTableName'];
        
        $db = Zend_Registry::get('db');
        // query for all relationships that aren't containment relationships:
        $select = $db->select()
            ->distinct()
            ->from(
                   array('f' => 'field_links'),
                   array('f.field_parent_name', 'f.field_child_name', 'f.field_link_name')
            )
            ->joinLeft(
                       array('s1' => 'field_summary'),
                       'f.field_parent_name = s1.field_name and f.source_id = s1.source_id',
                       array('parent_name' => 'field_label')
            )
            ->joinLeft(
                       array('s2' => 'field_summary'),
                       'f.field_child_name = s2.field_name and f.source_id = s2.source_id',
                       array('child_name' => 'field_label')
            )                    
            ->joinLeft(
                       array('s3' => 'field_summary'),
                       'f.field_link_name = s3.field_name and f.source_id = s3.source_id',
                       array('link_name' => 'field_label')
            )
            ->joinLeft(
                       array('lu' => 'w_lu_relationship_types'),
                       'f.fk_link_type = lu.pk_relationship_type',
                       array('verb' => 'RELATIONSHIP_VERB', 'relationship' => 'RELATIONSHIP_TYPE')
            )
            ->where ("f.source_id = '" . $dataTableName . "'")
            ->where('fk_link_type <> ?', 1) //everything but containment relationships:                    
            ->order(array('relationship', 'parent_name', 'child_name'));
        
        $stmt   = $db->query($select);
        $relationshipRows   = $stmt->fetchAll();

        $rowsArray = array();
        foreach($relationshipRows as $relationshipRow)
        {
            $originField    = $relationshipRow['field_parent_name'];
            $targetField    = $relationshipRow['field_child_name'];
            $linkField      = $relationshipRow['field_link_name'];
            
            //echo $linkField;
            $select = $db->select()
                ->distinct()
                ->from  (
                        array('d' => $dataTableName),
                        array('origin' => $originField, 'target' => $targetField)
                )
                ->where($originField . " is not null and " . $targetField . " is not null")
                ->order(array($originField, $targetField))
                ->limit(3, 0); //count of rows, number of rows to skip
            
            //if a link field exists, query for it:            
            if(sizeof($linkField) > 0)
            {
                $select = $db->select()
                    ->distinct()
                    ->from  (
                            array('d' => $dataTableName),
                            array('origin' => $originField, 'target' => $targetField, 'verb' => $linkField)
                    )
                    ->where($originField . " is not null and " . $targetField . " is not null")
                    ->order(array($originField, $targetField))
                    ->limit(3, 0); //count of rows, number of rows to skip
            }
            $stmt   = $db->query($select);
            $rows   = $stmt->fetchAll();
            //Zend_Debug::dump($rows);

            $headerRow = array(
                               'origin'     => $relationshipRow['parent_name'],
                               'target'     => $relationshipRow['child_name'],
                               'verb'       => $relationshipRow['verb'],
                               'fieldLink'  => 'Is Field Link'
                            );
            array_unshift($rows,$headerRow);
            for($i = 0; $i < sizeof($rows); ++$i)
            {
                $rows[$i]['id']         = ($i + 1);
                //establish the relationship:
                if($relationshipRow['link_name'] == null)
                {
                    $rows[$i]['verb']       = $relationshipRow['verb'];
                    $rows[$i]['fieldLink']  = false;
                }
                else
                {
                    $rows[0]['verb']        = $relationshipRow['link_name'];
                    $rows[$i]['fieldLink']  = true;   
                }
            }
            array_push($rowsArray, $rows);
        }
        
        echo Zend_Json::encode($rowsArray);
          
    }
    
    
    function populateTreeAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $dataTableName = $_REQUEST['dataTableName'];
        
        //  Step 1:
        //  -------------------------------------------------------
        //  Determine the "top" nodes of spatial containment
        //  (note that there can be multiple top nodes):
        //   - gets a distinct list of child properties
        //   - uses child property list to determine the top-level
        //     parents (top-level parents are never children).
        //  -------------------------------------------------------
        
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
        if(sizeof($rows) == 0)
            return "";
        $parameterList = array();
        foreach ($rows as $row) 
            array_push($parameterList, $row['fk_field_child']);
        
        $select = $db->select()
            ->distinct()
            ->from(
                    array('f' => 'field_links'),
                    array('f.field_parent_name')
            )
            ->where('f.source_id = ?', $dataTableName)
            ->where('f.fk_link_type = ?', 1)            
            ->where('fk_field_parent not in (?)', $parameterList);
            
        $stmt = $db->query($select);
        $parents = $stmt->fetchAll();
        if(sizeof($parents) == 0)
            return "";
        
        $dataStores = array();
        $i = 0;
        foreach ($parents as $parent)
        {
            $items = array();
            $select = $db->select()
            ->distinct()
            ->from(
                    array('f' => 'field_links'),
                    array('f.field_parent_name', 'f.field_child_name')
            )
            ->joinLeft(
                   array('s1' => 'field_summary'),
                   'f.field_parent_name = s1.field_name and f.source_id = s1.source_id',
                   array('parent_alias' => 'field_label')
            )
            ->joinLeft(
                   array('s2' => 'field_summary'),
                   'f.field_child_name = s2.field_name and f.source_id = s2.source_id',
                   array('child_alias' => 'field_label')
            )
            ->where("f.source_id = '" . $dataTableName . "' and f.fk_link_type = 1 and " .
                    "field_parent_name = '" . $parent['field_parent_name'] . "'");
            $stmt = $db->query($select);
            $fieldRows = $stmt->fetchAll();

            $j = 0;
            foreach ($fieldRows as $row) 
            {
                //if($j > 0)
                    $items = $this->getItems($dataTableName, $row, $items, 0, array(), null);
                ++$j;
            }
            $dataStores[$i] = array('label' => 'name', 'identifier' => 'name',
                'items' => $items                        
            );
            ++$i;
            //break;
        }
        echo Zend_Json::encode($dataStores);
    }
    
    
    
    function getItems($dataTableName, $fieldRow, $items, $level, $fieldList, $dataRow) //returns $datastore
    {
        ++$this->counter;
        $parentFieldName    = $fieldRow['field_parent_name'];
        $parentAlias        = $fieldRow['parent_alias'];
        $childFieldName     = $fieldRow['field_child_name'];
        $childAlias         = $fieldRow['child_alias'];
        
        // 1.  add the next two fields onto the end of the $fieldList stack.
        if(!in_array($parentFieldName, $fieldList))
            array_push($fieldList, $parentFieldName);
        
        // 2.  construct the where clause:
        $whereClause = "";
        $whereClauses = array();
        if($dataRow == null)
            $whereClause = $parentFieldName . ' is not null and ' . $childFieldName . ' is not null';

        //once where clause has been created, add the childFieldName to the fieldList
        if(!in_array($childFieldName, $fieldList))
            array_push($fieldList, $childFieldName); 
        
        // 3.  get sample data for the tree!
        $db = Zend_Registry::get('db');
        $select = $db->select()
        ->distinct()
        ->from  (
                array('d' => $dataTableName),
                $fieldList
        )
        ->order($fieldList)
        ->limit(5, 0); //count of rows, number of rows to skip
        
        if($whereClause != "")
        {
           $select->where($whereClause);
        }
        else
        { 
            for($k=0; $k < sizeof($fieldList) - 1; ++$k)
                $select->where($fieldList[$k] . ' = ?', $dataRow[$fieldList[$k]]);
        }
        
        try
        {
            $stmt = $db->query($select);
            $dataRows = $stmt->fetchAll();
            //$stmt->execute(array(':reporter' => 'goofy', ':status' => 'FIXED'));

        }
        catch(Exception $e)
        {
            //Zend_Debug::dump($e);
            Zend_Debug::dump($select);
            //Zend_Debug::dump($stmt);   
        }

        //Iterate through the 10 sample rows to create a sample tree:
        $previousParentCategory = "";
        $childArray = array();
        
        foreach ($dataRows as $dataRow) 
        {
            if($previousParentCategory != $dataRow[$parentFieldName] && $previousParentCategory != "")
            {
                //If the parent item already exists, create or append the children:
                $elementExists = false;
                for($k=0; $k < sizeof($items); ++$k)
                {
                    //if the item already exists, append (or create) children:
                    if($items[$k]['name'] == $previousParentCategory)
                    {
                        $elementExists = true;
                        if(array_key_exists('children', $items[$k]))
                        {
                            $oldArray = $items[$k]['children'];
                            $childExists = false;
                            foreach ($childArray as $child)
                            {
                                foreach($oldArray as $oldArrayItem)
                                {
                                    if($oldArrayItem['_reference'] == $child['_reference'])
                                    {
                                        $childExists = true;
                                        break;
                                    }
                                }                                
                                if(!$childExists)
                                    array_push($items[$k]['children'], $child); 
                            } 
                        }
                        else
                        {
                            $items[$k]['children'] = $childArray;
                        }
                        $childArray = array();
                        break;
                    }//end if equals $previousParentCategory
                }//end foreach item
                //otherwise, add the parent item:
                if(!$elementExists)
                {
                    array_push($items, array('name' => $previousParentCategory, 'type' => $level, 'children' => $childArray));
                    $childArray = array();
                }
                
            }//end if new tree node needed

            array_push($childArray, array('_reference' => $fieldRow['child_alias'] . ': ' . $dataRow[$childFieldName]));
            //add child item (if not already in $items array):
            $elementExists = false;
            foreach($items as $item)
            {
                if($item['name'] == $fieldRow['child_alias'] . ': ' . $dataRow[$childFieldName]) // && $items[$k]['type'] == ($level+1)
                {
                    $elementExists = true;
                    break;
                }
            }                
            if(!$elementExists)
            {
                array_push($items, array('name' => $fieldRow['child_alias'] . ': ' . $dataRow[$childFieldName], 'type' => $level+1));
                
                // 4.a)i.  add the parent value to the value list:
                //array_push($valueList, $parentFieldName);
                //array_push($valueList, $childFieldName);
                //check to see if there are any children of this child:
                $select = $db->select() 
                ->from  (
                        array('f' => 'field_links'),
                        array('f.field_parent_name', 'f.field_child_name')
                )
                ->joinLeft(
                        array('s1' => 'field_summary'),
                        'f.field_parent_name = s1.field_name and f.source_id = s1.source_id',
                        array('parent_alias' => 'field_label')
                )
                ->joinLeft(
                        array('s2' => 'field_summary'),
                        'f.field_child_name = s2.field_name and f.source_id = s2.source_id',
                        array('child_alias' => 'field_label')
                )
                ->where("f.source_id = '" . $dataTableName . "' and f.fk_link_type = 1 and " .
                        "f.field_parent_name = '" . $childFieldName . "'");    
                $stmt = $db->query($select);
                $childRows = $stmt->fetchAll();
                foreach($childRows as $childRow)
                {
                    //call "createTree" recursively!
                    //$items = $this->getItems($dataTableName, $childRow, $items, $level+1, array(), array());
                    //Zend_Debug::dump($fieldList);
                    //Zend_Debug::dump($dataRow);
                    //array_push($valueList, $dataRow[$childFieldName]);
                    $items = $this->getItems($dataTableName, $childRow, $items, $level+1, $fieldList, $dataRow);
                    //array_pop($valueList);
                }
            }
            $previousParentCategory = $parentAlias . ': ' . $dataRow[$parentFieldName];
        }//end foreach $dataRow
        
        //----------- final append ----------//
        $elementExists = false;
        for($k=0; $k < sizeof($items); ++$k)
        {
            if($items[$k]['name'] == $previousParentCategory) // && $item['type'] == ($level+1)
            {
                $elementExists = true;
                //append:
                if(array_key_exists('children', $items[$k]))
                {
                    $oldArray = $items[$k]['children'];
                    $childExists = false;
                    foreach ($childArray as $child)
                    {
                        foreach($oldArray as $oldArrayItem)
                        {
                            if($oldArrayItem['_reference'] == $child['_reference'])
                            {
                                $childExists = true;
                                break;
                            }
                        }                                
                        if(!$childExists)
                            array_push($items[$k]['children'], $child); 
                    } 
                }
                //create:
                else
                {
                    $items[$k]['children'] = $childArray;
                }
                $childArray = array();
                break;
            }
        }                
        if(!$elementExists)
        {
            array_push($items, array('name' => $previousParentCategory, 'type' => $level, 'children' => $childArray));
            $childArray = array();
        }
        //----------- end final append ----------//
        //Zend_Debug::dump($items);
        return $items;
    }
    
    function isValidRelationshipAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        
        $dataTableName  = $_REQUEST['dataTableName'];
        $fieldOrigin    = $_REQUEST['origin'];
        $relationship   = $_REQUEST['relationship'];
        $fieldTarget    = $_REQUEST['target'];
        $dialogType     = $_REQUEST['dialogType'];
        
        
        //use string split functions:
        
        $fieldOriginArray   = explode(".", $fieldOrigin);
        $fieldTargetArray   = explode(".", $fieldTarget);
        
        $fieldOriginID      = $fieldOriginArray[0]; 
        $fieldOriginName    = $fieldOriginArray[1];
        $fieldTargetID      = $fieldTargetArray[0]; 
        $fieldTargetName    = $fieldTargetArray[1];
        
        $db = Zend_Registry::get('db');
        //1.  make sure that the origin / target combination don't already exist:
        $select = $db->select()
                    ->from  (
                                array('f' => 'field_links')
                    )
                    ->where('source_id = ?', $dataTableName)
                    ->where('fk_field_parent = ?', $fieldOriginID)
                    ->where('fk_field_child = ?', $fieldTargetID);
        
        $stmt   = $db->query($select);
        $rows   = $stmt->fetchAll();
        if(sizeof($rows) > 0)
        {
            echo "A relationship between the following two fields already exists in the database: ";
            return;
        }
        
        //2.  make sure that the target / origin combination don't exist
        //   (would kill the recursion function):
        $select = $db->select()
                    ->from  (
                                array('f' => 'field_links')
                    )
                    ->where('source_id = ?', $dataTableName)
                    ->where('fk_field_parent = ?', $fieldTargetID)
                    ->where('fk_field_child = ?', $fieldOriginID);
        
        $stmt   = $db->query($select);
        $rows   = $stmt->fetchAll();
        if(sizeof($rows) > 0)
        {
            echo "A relationship between the following two fields already exists in the database: ";
            return;
        }
        echo "";
    }
    
    function addRelationshipAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        
        $dataTableName  = $_REQUEST['dataTableName'];
        $fieldOrigin    = $_REQUEST['origin'];
        $relationship   = $_REQUEST['relationship'];
        $fieldTarget    = $_REQUEST['target'];
        $dialogType     = $_REQUEST['dialogType'];
        
        
        //use string split functions:
        
        $fieldOriginArray   = explode(".", $fieldOrigin);
        $fieldTargetArray   = explode(".", $fieldTarget);
        
        $fieldOriginID      = $fieldOriginArray[0]; 
        $fieldOriginName    = $fieldOriginArray[1];
        $fieldTargetID      = $fieldTargetArray[0]; 
        $fieldTargetName    = $fieldTargetArray[1];
        
        
        /*echo "dataTableName: " . $dataTableName . "\n";
        echo "fkFieldParent: " . $fkFieldParent . "\n";
        echo "fkFieldParentName: " . $fkFieldParentName . "\n";
        echo "relationship: " . $relationship . "\n";
        echo "fkFieldChild: " . $fkFieldChild . "\n";
        echo "fkFieldPChildName: " . $fkFieldChildName . "\n";*/
        
        $relationshipID = 0;
        $fieldLinkID    = null;
        $fieldLinkName  = null;
        $db = Zend_Registry::get('db');
        $data = array();
        switch($dialogType)
        {
            case "db":
                $relationshipID = 2; //id for "dataset link field"
                
                $whereClause = "source_id = '" . $dataTableName . "' and field_type = 'Links'";
                $select = $db->select()
                    ->from  (
                                array('f' => 'field_summary'),
                                array('id' => 'f.pk_field', 'f.field_name')
                    )
                    ->where($whereClause); 
                
                $stmt           = $db->query($select);
                $rows           = $stmt->fetchAll();
                $fieldLinkID    = $rows[0]['id'];
                $fieldLinkName  = $rows[0]['field_name'];
                $data = array(
                    'source_id'          => $dataTableName,
                    'fk_field_parent'   => $fieldOriginID,
                    'fk_field_child'    => $fieldTargetID,
                    'field_parent_name' => $fieldOriginName,
                    'field_child_name'  => $fieldTargetName,
                    'fk_link_type'      => $relationshipID,
                    'fk_field_link'     => $fieldLinkID,    // null unless it's a field link
                    'field_link_name'   => $fieldLinkName   // null unless it's a field link
                );
                break;
            case "containment":
            case "link":
                //check to see if the relationship exists in the database:
                $select = $db->select()
                    ->from  (
                                array('f' => 'w_lu_relationship_types'),
                                array('f.PK_RELATIONSHIP_TYPE', 'f.RELATIONSHIP_TYPE', 'f.RELATIONSHIP_VERB' )
                    )
                    ->where('RELATIONSHIP_TYPE = ?', $relationship);
                $stmt = $db->query($select);
                $rows = $stmt->fetchAll();
                Zend_Debug::dump($rows);
                
                //if the relationship doesn't exist in the database, add it
                if(sizeof($rows) == 0)
                {
                    $select = $db->select()
                    ->from  (
                                array('f' => 'w_lu_relationship_types'),
                                array('max' => 'max(f.PK_RELATIONSHIP_TYPE)')
                    );
                    $stmt = $db->query($select);
                    $rows = $stmt->fetchAll();
                    $relationshipID = $rows[0]['max'] + 1;
                    //echo "\n" . $relationshipID . "\n";
                    
                    //insert to the database:
                    $data = array(
                        'PK_RELATIONSHIP_TYPE'  => $relationshipID,
                        'RELATIONSHIP_TYPE'     => $relationship,
                        'RELATIONSHIP_VERB'     => $relationship);        
                    $db = Zend_Registry::get('db');
                    $db->insert('w_lu_relationship_types', $data);    
                }
                else
                {
                    $relationshipID =  $rows[0]['PK_RELATIONSHIP_TYPE'];
                }
                //insert to the database:
                $data = array(
                    'source_id'          => $dataTableName,
                    'fk_field_parent'   => $fieldOriginID,
                    'fk_field_child'    => $fieldTargetID,
                    'field_parent_name' => $fieldOriginName,
                    'field_child_name'  => $fieldTargetName,
                    'fk_link_type'      => $relationshipID
                );
                break;
        }

        //insert new link:
        $db->insert('field_links', $data);
        
        //return the new data record so that it can be added to the data grid:
        $select = $db->select()
        ->distinct()
        ->from(array('f' => 'field_links'), array('id' => new Zend_Db_Expr("CONCAT(f.fk_field_parent, '_', f.fk_field_child, '_', f.fk_link_type)")))
        ->joinLeft(
                   array('s1' => 'field_summary'),
                   'f.field_parent_name = s1.field_name and f.source_id = s1.source_id',
                   array('parent_name' => 'field_label')
        )
        ->joinLeft(
                   array('s2' => 'field_summary'),
                   'f.field_child_name = s2.field_name and f.source_id = s2.source_id',
                   array('child_name' => 'field_label')
        )
        ->joinLeft(
                   array('lu' => 'w_lu_relationship_types'),
                   'f.fk_link_type = lu.pk_relationship_type',
                   array('verb' => 'RELATIONSHIP_VERB', 'relationship' => 'RELATIONSHIP_TYPE')
        )
        ->where ("f.source_id = ?", $dataTableName)
        ->where ("f.field_parent_name = ?", $fieldOriginName)
        ->where ("f.field_child_name = ?", $fieldTargetName)
        ->where ("f.fk_link_type = ?", $relationshipID);
        //->where ("f.source_id = '" . $dataTableName . "' and f.fk_link_type = 1");

        $stmt = $db->query($select);
        $rows = $stmt->fetchAll();
        echo Zend_Json::encode($rows[0]);
        
        //Zend_Debug::dump($rows);
        //echo "success!";
        
    }
    
    function getFieldsForDropdownsAction()
    {
        //this line is necessary for ajax calls:
        $this->_helper->viewRenderer->setNoRender();
        
        //get selected data table
        $dataTableName  = $_REQUEST['dataTableName'];
        $allProperties  = $_REQUEST['allProperties'];
        $excludeValue   = $_REQUEST['excludeValue'];    
        
        $db = Zend_Registry::get('db');
        $select = $db->select()
            ->from  (
                        array('f' => 'field_summary'),
                        array('id' => new Zend_Db_Expr("CONCAT(f.pk_field, '.', f.field_name)"), 'f.field_label' )
            )
            ->where ("f.source_id = ?", $dataTableName);
        if($allProperties != "true")
        {
            $select->where("f.field_type = ?", "Locations or Objects"); 
        }
        else
        {
             $select->where("f.field_type <> ?", "Property");
             $select->where("f.field_type <> ?", "Links");  
             $select->where("f.field_type <> ?", "Ignore");  
        }        
        if($excludeValue != null && $excludeValue != "")
            $select->where ("f.field_label <> ?", $excludeValue);
        
        $stmt = $db->query($select);
        $rows = $stmt->fetchAll();
        if(sizeof($rows) == 0)
            return "";
        
        //Zend_Debug::dump($rows);
        Zend_Loader::loadClass('Zend_Dojo_Data');
        $dataRecords = new Zend_Dojo_Data();
        $dataRecords->setIdentifier("id");
        $dataRecords->setItems($rows);
        echo $dataRecords;
    }
    
    function getRelationshipsForDropdownAction()
    {
        //this line is necessary for ajax calls:
        $this->_helper->viewRenderer->setNoRender();
        
        //get parameters:
        $dataTableName  = $_REQUEST['dataTableName'];
        $dialogType     = $_REQUEST['dialogType'];
        $whereClause    = "";
        
        //echo $dialogType . "\n";
        
        $db = Zend_Registry::get('db');
        switch($dialogType)
        {
            case "containment":
            case "link":
                if($dialogType == "containment")
                    $whereClause = "RELATIONSHIP_VERB = 'contains'";
                else
                    $whereClause = "RELATIONSHIP_VERB <> 'contains'";
                $select = $db->select()
                    ->from  (
                                array('f' => 'w_lu_relationship_types'),
                                array('f.PK_RELATIONSHIP_TYPE', 'f.RELATIONSHIP_TYPE', 'f.RELATIONSHIP_VERB' )
                    )
                    ->where($whereClause) 
                    ->where("PK_RELATIONSHIP_TYPE <> 2"); //exclude "links to a field" type.
                $stmt = $db->query($select);
                $rows = $stmt->fetchAll();
                break;
            case "db":
                $whereClause = "source_id = '" . $dataTableName . "' and field_type = 'Links'";
                $select = $db->select()
                    ->from  (
                                array('f' => 'field_summary'),
                                array('PK_RELATIONSHIP_TYPE' => 'f.pk_field', 'RELATIONSHIP_TYPE' => 'f.field_label' )
                    )
                    ->where($whereClause); 
                
                $stmt = $db->query($select);
                $rows = $stmt->fetchAll();
                break;
        }
        
        Zend_Loader::loadClass('Zend_Dojo_Data');
        $dataRecords = new Zend_Dojo_Data();
        $dataRecords->setIdentifier("PK_RELATIONSHIP_TYPE");
        $dataRecords->setItems($rows);
        echo $dataRecords;
    }
    
    function hasFieldLinksAction()
    {
        //this line is necessary for ajax calls:
        $this->_helper->viewRenderer->setNoRender();
        
        //get parameters:
        $dataTableName  = $_REQUEST['dataTableName'];
        
        $db = Zend_Registry::get('db');
        $select = $db->select()
            ->from  (
                        array('f' => 'field_summary'),
                        array('PK_RELATIONSHIP_TYPE' => 'f.pk_field', 'RELATIONSHIP_TYPE' => 'f.field_label' )
            )
            ->where("source_id = '" . $dataTableName . "' and field_type = 'Links'"); 
        
        $stmt = $db->query($select);
        $rows = $stmt->fetchAll();

        echo sizeof($rows);
    }
    
    function removeRelationshipAction()
    {
        //this line is necessary for ajax calls:
        $this->_helper->viewRenderer->setNoRender();
        
        //get selected data table
        $dataTableName  = $_REQUEST['dataTableName'];
        $id             = $_REQUEST['id'];
        $fieldArray     = explode("_", $id);
        $fieldOriginID  = $fieldArray[0]; 
        $fieldTargetID  = $fieldArray[1];
        
        //echo $relationship;
        //return;
        
        //delete from database:      
        $db = Zend_Registry::get('db');
        $db->delete('field_links', "fk_field_parent = '" . $fieldOriginID . "' and fk_field_child = '" . $fieldTargetID . "' and source_id = '" . $dataTableName . "'");
        
        echo "The relationship was successfully deleted.";
    }
    
}