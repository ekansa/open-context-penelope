<?php
abstract class Item
{
    // Force Extending class to define these methods:
    abstract protected function getItemType();
    abstract protected function getUUID();
    abstract protected function getProjectUUID();
    //abstract protected function getObservations();
    
    // Force Extending class to define these variables:

    // Variables implemented by this abstract class:
    public $observations    = array();
    public $notes           = array();
    public $links           = array();
    protected $project;
    public $xDoc;
    
    // Methods implemented by this abstract class:
    public function getLinks($obsNum=1)
    {
        $db = Zend_Registry::get('db');
        $select = $db->select()
            ->distinct()
            ->from(
                array('l' => 'links'),
                array('l.targ_uuid', 'l.targ_type', 'l.link_type')
            )
            ->where ('l.origin_uuid = ?', $this->getUUID())            
            ->where ('l.origin_obs = ?', $obsNum)           
            ->where ('l.targ_obs = ?', $obsNum);
        $rows = $db->query($select)->fetchAll();
        //echo 'links...';
        //Zend_Debug::dump($rows);
        Zend_Loader::loadClass('Link'); 
        foreach($rows as $row)
        {
            array_push($this->links, new Link($this, $row['targ_uuid'], $row['targ_type'], $row['link_type'], $this->project));
        }
        //Zend_Debug::dump($this->links);
    }
    
    public function getNotes($obsNum=1)
    {
        if(sizeof($this->notes) > 0)
            return $this->notes;
        
        //query for notes:
        $db = Zend_Registry::get('db');
        $select = $db->select()
            ->from(
                array('o' => 'observe'),
                array('o.property_uuid')
            )                       
            ->join(
                array('p' => 'properties'),
                   'o.property_uuid = p.property_uuid',
                   array('p.val_num')
            )                                  
            ->join(
                array('val' => 'val_tab'),
                   'val.value_uuid = p.value_uuid',
                   array('val.val_text')
            )
            ->where('o.subject_uuid = ?', $this->getUUID())
            ->where('p.variable_uuid = ?', 'NOTES')
            ->where('o.obs_num = ?', $obsNum);
        $rows = $db->query($select)->fetchAll();
        //Zend_Debug::dump($rows);
        
        Zend_Loader::loadClass('Note');
        foreach($rows as $row)
            array_push($this->notes, new Note($this, $row['val_text']));
        return $this->notes;
    }
    
    public function getObservations()
    {
        if(sizeof($this->observations) > 0)
            return $this->observations;
        
        //query for observations:
        $db             = Zend_Registry::get('db');       
        $select = $db->select()
            ->distinct()
            ->from(
                array('o' => 'observe'),
                array('o.obs_num', 'o.project_id')
            )
            ->where('o.subject_uuid = ?', $this->getUUID());
        $rows = $db->query($select)->fetchAll();
        //Zend_Debug::dump($rows);
        
        Zend_Loader::loadClass('Observation');
        foreach($rows as $row)
            array_push($this->observations, new Observation($this, $row['obs_num']));
        return $this->observations;
    }
    
    public function getObservation()
    {
        $observations = $this->getObservations();
        //Zend_Debug::dump($observations);
        if(sizeof($observations) > 0)
            return $observations[0];
        return null;
    }
}
?>