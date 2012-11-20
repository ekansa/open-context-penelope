<?php
class Observation
{
    /*
    corresponds to observe
    ------------------------
    project_id, source_id, hash_obs, subject_type, subject_uuid, obs_num, property_uuid
    */
    
    //public variables
    public $obsNum;
    public $properties  = array();
    public $notes       = array();
    public $subject;
    
    //Note: all $subjects implement the Item.php abstract class
    //      and share common methods.  Each observation number is
    //      associated with one or more corresponding properties.
    function Observation($subject, $obsNum)
    {
        $this->subject      = $subject;
        $this->obsNum       = $obsNum;
        $this->getProperties();
        //$this->getNotes();
    }
    
    private function getProperties()
    {
        $db         = Zend_Registry::get('db');
        //query for observations which are not notes:
        $select = $db->select()
            ->from(
                array('o' => 'observe'),
                array('o.property_uuid', 'o.project_id')
            )                       
            ->join(
                array('p' => 'properties'),
                   'o.property_uuid = p.property_uuid',
                   array('p.variable_uuid')
            )
            //(join to the var_tab so can sort by var_label)
            ->join(
                array('v' => 'var_tab'),
                   'p.variable_uuid = v.variable_uuid',
                   array('v.var_label')
            )
            ->where('o.subject_uuid = ?', $this->subject->getUUID())
            ->where('p.variable_uuid <> ?', 'NOTES')
            ->where('o.obs_num = ?', $this->obsNum)
            ->order('v.var_label');
        
        $rows = $db->query($select)->fetchAll();
        //Zend_Debug::dump($rows);
        
        if(sizeof($rows) == 0)
            return;
        
        Zend_Loader::loadClass('Property');
        foreach($rows as $row)
            array_push($this->properties, new Property($row['property_uuid'], Project::getProjectByUUID($row['project_id'])));
    }
    
    /*private function getNotes()
    {
        $db         = Zend_Registry::get('db');
        //query for observations which are not notes:
        $select = $db->select()
            ->from(
                array('o' => 'observe'),
                array('o.property_uuid')
            )                       
            ->join(
                array('p' => 'properties'),
                   'o.property_uuid = p.property_uuid',
                   array('p.variable_uuid')
            )                       
            ->join(
                array('val' => 'val_tab'),
                   'val.value_uuid = p.value_uuid',
                   array('val.val_text')
            )
            ->where('o.subject_uuid = ?', $this->subject->getUUID())
            ->where('p.variable_uuid = ?', 'NOTES')
            ->where('o.obs_num = ?', $this->obsNum);
        
        $rows = $db->query($select)->fetchAll();
        //Zend_Debug::dump($rows);
        
        if(sizeof($rows) == 0)
            return;
        
        Zend_Loader::loadClass('Property');
        foreach($rows as $row)
            array_push($this->notes, $row['val_text']));
    }*/
}


