<?php
require_once 'Item.php';
require_once 'App/Xml/XmlGenerator.php';
class Resource extends Item implements App_Xml_XmlGenerator
{
    public $projectUUID;    
    public $resUUID;
    public $timestamp;
    public $name;
    public $label;
    public $pathSource;
    public $pathDestination;
    public $format;
    public $links = array();
    public $notes   = array();
    public $namespaceArch = App_Constants::ARCHAEOML_NS_URI_RESOURCE;
    public $namespaceOC = App_Constants::OC_NS_URI_RESOURCE;
    
    function Resource($resUUID, $project)
    {
        Zend_Loader::loadClass('Table_Resource');
        $this->resUUID          = $resUUID;
        $this->project          = $project;
        $resource               = new Table_Resource();
        $resourceRow            = $resource->fetchRow("uuid  = '" . $this->resUUID . "'");
        $this->projectUUID      = $resourceRow->project_id;
        $this->name             = $resourceRow->res_filename;
        $this->timestamp        = $resourceRow->last_modified_timestamp;
        $this->label            = $resourceRow->res_label;
        $this->pathSource       = $resourceRow->res_path_source;
        $this->pathDestination  = $resourceRow->res_path_destination;  
        $this->format           = $resourceRow->res_format;
        /*
        project_id
        source_id	  	  	 
        uuid  	 
        res_number	  	 
        res_label	  	  	 
        res_path_source 
        res_filename
        res_path_destination 
        res_archml_type  	 
        res_format	 
        res_thumb	  	  	 
        res_preview	  	  	 
        res_fullfile	  	  	 
        ia_meta	  	  	 
        ia_thumb	  	  	 
        ia_preview	  	  	 
        ia_fullfile	  	  	 
        last_modified_timestamp
        
        */
    }
    
    //required to be implemented by the "Item" abstract class:
    public function getItemType() { return App_Constants::MEDIA; }
    public function getUUID() { return $this->resUUID; }
    public function getProjectUUID() { return $this->projectUUID; }
     
    //required to be implemented by the App_Xml_XmlGenerator interface:
    public function generateXML()
    {
        $strXML = '<arch:resource 
                xmlns:arch="'   . $this->namespaceArch . '"
                xmlns:oc="'     . $this->namespaceOC .'" 
                xmlns:gml="'    . App_Constants::GML_NS_URI . '" 
                xmlns:dc="'     . App_Constants::DC_NS_URI . '"
                />';
                
        $this->xDoc = new SimpleXMLElement($strXML);
        $this->generateGenericXML($this->xDoc);
        $this->generatePropertiesXML($this->xDoc);
        $this->generateLinksXML($this->xDoc);
        $this->generateMetadataXML($this->xDoc);
        return $this->xDoc;
    }
    
    private function generateGenericXML($parentNode)
    {
        //add top-level attributes:
        $parentNode->addAttribute("UUID", $this->getUUID());
        $parentNode->addAttribute("ownedBy", $this->projectUUID);
        
        $nameNode = $parentNode->addChild('name', '', $this->namespaceArch);
        $nameNode->addChild('string', $this->name, $this->namespaceArch);
                
    }
    
    private function generatePropertiesXML($parentNode)
    {
        //query for observation numbers:
        $this->getObservations(); //in Item.php
        $propertiesNode     = $parentNode->addChild('properties', '', $this->namespaceArch);
        foreach($this->observations as $observation)
        {
            foreach($observation->properties as $property)
                $property->generatePropertyXML($propertiesNode, $this->namespaceArch, $this->namespaceOC);
        }
    }
    
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
    
    //if an item links to this item, this function will be called
    public function generateTargetLinkXML($linksNode, $linkType, $namespaceOC)
    {
        $mediaLinksNode = null;
        $nodes = $linksNode->xpath('//oc:media_links');
        if(empty($nodes))
            $mediaLinksNode = $linksNode->addChild('media_links', '', $namespaceOC);
        else
            $mediaLinksNode = $nodes[0];
        
        $mediaLinkNode = $mediaLinksNode->addChild('link', '', $namespaceOC);
        $mediaLinkNode->addChild('name', $this->name, $namespaceOC);
        $mediaLinkNode->addChild('id', $this->spaceUUID , $namespaceOC);
        
        $href = App_Constants::MEDIA_URI . $this->spaceUUID;
        $mediaLinkNode->addAttribute('href', $href);
        
        $relation = App_Xml_Generic::parseXMLcoding($linkType);
        $mediaLinkNode->addChild('relation', $relation, $namespaceOC);
        
        $mediaLinkNode->addChild('type', $this->format, $namespaceOC);
        $mediaLinkNode->addChild('path', $this->pathDestination, $namespaceOC);
        $mediaLinkNode->addChild('filename', '', $namespaceOC);
        $mediaLinkNode->addChild('thumbnailURI', '', $namespaceOC);
    }
    
    private function generateMetadataXML($parentNode)
    {
        $this->project->setMetadataInformation();
        $metadataNode = $parentNode->addChild('metadata', '', $this->namespaceOC);
        $this->project->metadata->generateGenericXML($metadataNode, $this, $this->namespaceOC);
    }
    
}
