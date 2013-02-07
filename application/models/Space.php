<?php
require_once 'Item.php';
require_once 'App/Xml/XmlGenerator.php';
class Space extends Item implements App_Xml_XmlGenerator
{
    /*
    corresponds to space
    -----------------------
    project_id
    source_id	  	  	 
    hash_fcntxt	  	 
    uuid   	 
    space_label	  	 
    full_context 
    sample_des 
    class_uuid 		  	  	 
    last_modified_timestamp 
    */
    
    public $projectUUID;    
    public $spaceUUID;
    public $name;
    public $fullContext;
    public $timestamp;
    public $spatialClass;
    public $dataTableName;
    public $namespaceArch = App_Constants::ARCHAEOML_NS_URI_SPACE;
    public $namespaceOC = App_Constants::OC_NS_URI_SPACE;
    
    public $level;
    public $parents     = array();
    
    function Space($spaceUUID, $project)
    {
        $this->spaceUUID    = $spaceUUID;
        $this->project      = $project;
        
        //query table and populate properties:
        Zend_Loader::loadClass('Table_Space');        
        Zend_Loader::loadClass('SpatialClass');
        $space              = new Table_Space();
        $spaceRow           = $space->fetchRow("uuid  = '" . $spaceUUID . "'");
        
        $this->projectUUID  = $spaceRow->project_id;
        $this->dataTableName= $spaceRow->source_id;
        $this->name         = $spaceRow->space_label;
        $this->fullContext  = $spaceRow->full_context;
        $this->classUUID    = $spaceRow->class_uuid;
        $this->timestamp    = $spaceRow->last_modified_timestamp;
        
        //every space object has a corresponding class:
        $this->spatialClass = new SpatialClass($spaceRow->class_uuid);
        $this->project->setFileSummaryInformation($this->dataTableName);
        
    }
    
    //required to be implemented by the "Item" abstract class:
    public function getItemType() { return App_Constants::SPATIAL; }
    public function getUUID() { return $this->spaceUUID; }
    public function getProjectUUID() { return $this->projectUUID; }
    //public function getObservations() { return $this->_getObservations($this->spaceUUID); }
       
    
    public function getParent()
    {
        $db             = Zend_Registry::get('db');
        $this->setUTFconnection($db);
        $select = $db->select()
            ->distinct()
            ->from(
                array('c' => 'space_contain'),
                array('c.parent_uuid')
            )
            ->where('c.child_uuid = ?', $this->spaceUUID)
            ->limit(1, 0);
        $rows = $db->query($select)->fetchAll();
        //Zend_Debug::dump($rows);
        if(sizeof($rows) == 0)
        {
            return null;
        }
        else
        {
            $index = strpos($rows[0]['parent_uuid'], ']:');
            if(!$index)
                return new Space($rows[0]['parent_uuid'], $this->project);
            return null;
        }
    }
    
    private function getContextLineage()
    {
        $obj = $this->getParent();
        while(isset($obj))
        {
            array_unshift($this->parents, $obj);
            $obj = $obj->getParent();
        }
        for($i=0; $i < sizeof($this->parents); ++$i)
            $this->parents[$i]->level = ($i+1);    
        
        //Zend_Debug::dump($this->parents);
    }
    
    public function generateXML()
    {
        if(isset($this->xDoc))
            return $this->xDoc;
        $strXML = '<arch:spatialUnit 
                        xmlns:arch="'   . App_Constants::ARCHAEOML_NS_URI_SPACE . '"
                        xmlns:oc="'     . App_Constants::OC_NS_URI_SPACE .'" 
                        xmlns:gml="'    . App_Constants::GML_NS_URI . '" 
                        xmlns:dc="'     . App_Constants::DC_NS_URI . '"
                        />';
        $this->xDoc = new SimpleXMLElement($strXML);
        $this->generateGenericXML($this->xDoc);
        $this->generateObservationsXML($this->xDoc);
        $this->generateLinksXML($this->xDoc);
        $this->generateNotesXML($this->xDoc);
        $this->generateContextXML($this->xDoc);
        
        //Todo:  add metadata and social usage XML
        $this->generateMetadataXML($this->xDoc);
        return $this->xDoc;
    }
     
    private function generateGenericXML($parentNode)
    {
        //add top-level attributes:
        $parentNode->addAttribute("UUID", $this->spaceUUID);
        $parentNode->addAttribute("ownedBy", $this->projectUUID);
        
        //add child metadata:
        $nameXML = $parentNode->addChild('name', '', $this->namespaceArch);
        $nameXML->addChild('string', $this->name, $this->namespaceArch);
                
        $classNode = $parentNode->addChild('item_class', '', $this->namespaceArch);
        $classNode->addChild("name", $this->spatialClass->name, $this->namespaceArch);
        $classNode->addChild("iconURI", App_Constants::THUMB_URI . $this->spatialClass->icon, $this->namespaceArch);   
    }
    
    //note that a single ContextItem may have multiple links:
    private function generateLinksXML($parentNode)
    {
        $linksNode = $parentNode->addChild('links', '', $this->namespaceArch);
        if(sizeof($this->links) == 0)
            $this->getLinks();
        //echo 'number of links: ' . sizeof($this->links);
        foreach($this->links as $link)
        {
            //echo ' targetType: ' . $link->targetType . '<br />';
            //NOTE:  each ContextItem type implements its own linkXML
            if(isset($link->target))
                $link->target->generateTargetLinkXML($linksNode, $link->linkType, $this->namespaceOC);
        }     
    }
    
    public function generateTargetLinkXML($linksNode, $linkType, $namespaceOC)
    {
        $spaceLinksNode = null;
        $nodes = $linksNode->xpath('//oc:space_links');
        if(empty($nodes))
            $spaceLinksNode = $linksNode->addChild('space_links', '', $namespaceOC);
        else
            $spaceLinksNode = $nodes[0];
        
        $spaceLinkNode = $spaceLinksNode->addChild('link', '', $namespaceOC);
        $spaceLinkNode->addChild('name', $this->name, $namespaceOC);
        $spaceLinkNode->addChild('id', $this->spaceUUID , $namespaceOC);
        
        $href = App_Constants::SPACE_URI . $this->spaceUUID;
        $spaceLinkNode->addAttribute('href', $href);
        
        $relation = App_Xml_Generic::parseXMLcoding($linkType);
        $spaceLinkNode->addChild('relation', $relation, $namespaceOC);
    }
    
    
    private function generateObservationsXML($parentNode)
    {
         //add observations node:
        $observationsNode   = $parentNode->addChild('observations', '', $this->namespaceArch);
        
        //query for observation numbers:
        $this->getObservations();
        
        //if there are no properties:
        if(sizeof($this->observations) == 0)
        { 
            $observationNode    = $observationsNode->addChild('observation', '', $this->namespaceArch);
            $propertiesNode     = $observationNode->addChild('no_props', '', $this->namespaceArch);
            return;
        }
        
        //now add observation data:
        foreach($this->observations as $observation)
        {
            $observationNode    = $observationsNode->addChild('observation', '', $this->namespaceArch);
            $propertiesNode     = $observationNode->addChild('properties', '', $this->namespaceArch);
            
            //getProperties(arg1, arg2) is implemented in the abstract class Item.php:
            foreach($observation->properties as $property)
                $property->generatePropertyXML($propertiesNode, $this->namespaceArch, $this->namespaceOC);
        }
    }
    
    private function generateNotesXML($parentNode)
    {
        $this->getNotes();
        if(sizeof($this->notes) == 0)
        {
            $notesNode = $parentNode->addChild('no_notes', '', $this->namespaceArch);
            return;
        }
        $notesNode = $parentNode->addChild('notes', '', $this->namespaceArch);
        foreach($this->notes as $note)
            $note->generateNoteXML($notesNode, $this->namespaceArch);
    }
    
    public function generateContextXML($parentNode)
    {
        $this->getContextLineage();
        if(($this->parents) == 0)
            return;
        $contextNode    = $parentNode->addChild('context', '', $this->namespaceOC);
        $treeNode       = $contextNode->addChild('tree', '', $this->namespaceOC);
        foreach($this->parents as $parent)
        {            
            $contextNode   = $treeNode->addChild('parent', '', $this->namespaceOC);     
            $contextNode->addAttribute('href', App_Constants::SPACE_URI . $parent->getUUID());
            $contextNode->addChild('name', $parent->name, $this->namespaceOC);
            $contextNode->addChild('id', $parent->getUUID(), $this->namespaceOC);
            $contextNode->addChild('level', $parent->level, $this->namespaceOC);
            
            $classNode = $contextNode->addChild('item_class', '', $this->namespaceOC);
            $classNode->addChild("name", $parent->spatialClass->name, $this->namespaceOC);
            $classNode->addChild("iconURI", App_Constants::THUMB_URI . $parent->spatialClass->icon, $this->namespaceOC);   
        }
    }
    
    public function generateMetadataXML($parentNode)
    {
        $this->project->setMetadataInformation();
        $metadataNode = $parentNode->addChild('metadata', '', $this->namespaceOC);
        $this->project->metadata->generateGenericXML($metadataNode, $this, $this->namespaceOC);
    }
    
    //preps for utf8
	 private function setUTFconnection($db){
		  $sql = "SET collation_connection = utf8_unicode_ci;";
		  $db->query($sql, 2);
		  $sql = "SET NAMES utf8;";
		  $db->query($sql, 2);
	 }
}


