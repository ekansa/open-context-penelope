<?php
require_once 'Item.php';
require_once 'App/Xml/XmlGenerator.php';
class Person extends Item implements App_Xml_XmlGenerator
{
    public $projectUUID;    
    public $personUUID;
    public $name;
    public $firstName;
    public $lastName;
    public $timestamp;
    public $links = array();
    public $notes   = array();
    public $namespaceArch = App_Constants::ARCHAEOML_NS_URI_PERSON;
    public $namespaceOC = App_Constants::OC_NS_URI_PERSON;
    
    function Person($personUUID, $project)
    {
        $this->personUUID   = $personUUID;
        $this->project      = $project;
        $db             = Zend_Registry::get('db');   
        $select = $db->select()
            ->distinct()
            ->from(
                array('p' => 'users'),
                array('p.combined_name',
                      'p.last_name',
                      'p.first_name')
            )
            ->join(
                array('at' => 'persons_st_des'),
                'at.uuid = p.uuid',
                array('at.project_id')
            )
            ->where ('p.uuid = ?', $this->personUUID)
            ->where ('at.project_id = ?', $this->project->uuID)
            ->limit(1, 0);
        $rows = $db->query($select)->fetchAll();
        //Zend_Debug::dump($rows);
        foreach($rows as $row)
        {
            $this->name         = $row['combined_name'];
            $this->firstName    = $row['first_name'];
            $this->lastName     = $row['last_name'];
            $this->projectUUID  = $row['project_id'];
        }
    }
    
    //required to be implemented by the "Item" abstract class:
    public function getItemType() { return App_Constants::PERSON; }
    public function getUUID() { return $this->personUUID; }
    public function getProjectUUID() { return $this->projectUUID; }
    //public function getObservations() { return $this->_getObservations($this->personUUID); }r
       
    public function generateXML()
    {
        if(isset($this->xDoc))
            return $this->xDoc;
        $strXML = '<arch:person 
                xmlns:arch="'   . App_Constants::ARCHAEOML_NS_URI_PERSON . '"
                xmlns:oc="'     . App_Constants::OC_NS_URI_PERSON .'" 
                xmlns:gml="'    . App_Constants::GML_NS_URI . '" 
                xmlns:dc="'     . App_Constants::DC_NS_URI . '"
                />';
                
        $this->xDoc = new SimpleXMLElement($strXML);
        $this->generateGenericXML($this->xDoc);
        $this->generateLinksXML($this->xDoc);
        $this->generateMetadataXML($this->xDoc);
        return $this->xDoc;
    }
    
    
    /*
    
     <?xml version="1.0" ?> 
        - <arch:person xmlns:arch="http://ochre.lib.uchicago.edu/schema/Person/Person.xsd" xmlns:oc="http://about.opencontext.org/schema/person_schema_v1.xsd" xmlns:gml="http://www.opengis.net/gml" xmlns:dc="http://purl.org/dc/elements/1.1/" UUID="AE915DED-1050-4D3B-7FA0-42C7409E1F08" ownedBy="8BB32E7E-3B17-49AF-3384-8551C1C35A78">
        - <arch:name>
          <arch:string>Catherine P. Foster</arch:string> 
          </arch:name>
        - <arch:personInfo spaceCount="58">
          <arch:firstName>Catherine</arch:firstName> 
          <arch:lastName>Foster</arch:lastName> 
          </arch:personInfo>
        - <oc:manage_info>
          <oc:pers_Queryval>Catherine+P.+Foster</oc:pers_Queryval> 
          </oc:manage_info>
          <arch:links /> 
    
    
    */
     
    private function generateGenericXML($parentNode)
    {
        //add top-level attributes:
        $parentNode->addAttribute("UUID", $this->getUUID());
        $parentNode->addAttribute("ownedBy", $this->projectUUID);
        
        $nameNode = $parentNode->addChild('name', '', $this->namespaceArch);
        $nameNode->addChild('string', $this->name, $this->namespaceArch);
                
        $personNode = $parentNode->addChild('personInfo', '', $this->namespaceArch);
        //Todo:  how is spaceCount determined?
        $personNode->addAttribute('spaceCount', '');
        $personNode->addChild("firstName", $this->firstName, $this->namespaceArch);
        $personNode->addChild("lastName", $this->lastName, $this->namespaceArch);
        
        $manageNode = $parentNode->addChild('manage_info', '', $this->namespaceArch);
        $manageNode->addChild("pers_Queryval", str_replace(' ', '+', $this->name), $this->namespaceArch);
    }
    
    //note that a single ContextItem may have multiple links:
    private function generateLinksXML($parentNode)
    {
        $linksNode = $parentNode->addChild('links', '', $this->namespaceArch);
        if(sizeof($this->links) == 0)
            $this->getLinks();
        
        foreach($this->links as $link)
        {
            //each ContextItem type implements its own linkXML
            if(isset($link->target))
                $link->target->generateTargetLinkXML($linksNode, $link->linkType, $this->namespaceOC);
        }     
    }
    
    public function generateTargetLinkXML($linksNode, $linkType, $namespaceOC)
    {
        $spaceLinksNode = null;
        $nodes = $linksNode->xpath('//oc:person_links');
        if(empty($nodes))
            $spaceLinksNode = $linksNode->addChild('person_links', '', $namespaceOC);
        else
            $spaceLinksNode = $nodes[0];
        
        $linkNode = $spaceLinksNode->addChild('link', '', $namespaceOC);
        $linkNode->addChild('name', $this->name, $namespaceOC);
        $linkNode->addChild('id', $this->getUUID(), $namespaceOC);
        
        $href = App_Constants::PERSONS_URI . $this->getUUID();
        $linkNode->addAttribute('href', $href);
        
        //Todo:  how is cite determined?
        $linkNode->addAttribute('cite', 'false');
        
        $relation = App_Xml_Generic::parseXMLcoding($linkType);
        $linkNode->addChild('relation', $relation, $namespaceOC);
    }
    
    private function generateMetadataXML($parentNode)
    {
        $this->project->setMetadataInformation();
        $metadataNode = $parentNode->addChild('metadata', '', $this->namespaceOC);
        $this->project->metadata->generateGenericXML($metadataNode, $this, $this->namespaceOC);
    }
    
    /*private function generateObservationsXML($parentNode)
    {
         //add observations node:
        $observationsNode   = $parentNode->addChild('observations', '', $this->namespaceArch);
        
        //query for observation numbers:
        $this->getObservations($this->spaceUUID);
        
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
            $observation->getProperties();
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
    
    public function getMetadata($objectUUID)
    {                
        $select = $db->select()
            ->distinct()
            ->from(
                array('p' => 'users'),
                array('name' => 'p.combined_name',
                      'label_2' => new Zend_Db_Expr('\'Participant\''),
                      'p.last_name',
                      'p.first_name')
            )
            ->join(
                array('at' => 'persons_st_des'),
                'at.uuid = p.uuid',
            )
            ->join(
                array('p' => 'project_list'),
                'at.project_id = p.project_id',
                array('p.project_id',
                      'p.project_name',
                      'p.thumb_root',
                      'license_id'  => new Zend_Db_Expr('\'2_License\''),       //returns literal string
                      'pubdate'     => new Zend_Db_Expr('CURDATE()'),           //returns MySQL date
                      'c_pubdate'   => new Zend_Db_Expr('CURDATE()'),           //returns MySQL date
                )
            )
            ->where ('p.uuid = ?', $objectID)
            ->limit(1, 0);
        $rows = $db->query($select)->fetchAll();
        Zend_Debug::dump($rows);
    }*/
    
}
