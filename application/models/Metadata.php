<?php
class Metadata
{
    private $project;
    public $metadataItems   = array();
    public $creators        = array();
    public $subjects        = array();
    public $subjectsString  = '';
    public $creatorsString  = '';
    public $hasMetadata     = false;

    function Metadata($project) //where $_data is a "ResultRowObject"
    {
        //Zend_Debug::dump($project);
        $this->project      = $project;
        Zend_Loader::loadClass('Table_Metadata');
        Zend_Loader::loadClass('MetadataItem');
        $metadata           = new Table_Metadata();
        $whereClause        = "project_id = '" . $project->uuID . "'";
        $orderBy            = array('dc_field', 'dc_value');        
        $rows               = $metadata->fetchAll($whereClause, $orderBy);
        foreach($rows as $row)
        {
            $this->hasMetadata = true;
            array_push($this->metadataItems, new MetadataItem($row));
        }

        foreach($this->metadataItems as $item)
        {
            /*
            dc:title
            dc:date
            dc:coverage
            dc:creator
            dc:format
            dc:language
            dc:publisher
            dc:rights
            dc:subject
            dc:identifier
            */
            if($item->dcField == "dc:creator") { array_push($this->creators, $item->dcValue); }
            if($item->dcField == "dc:subject") { array_push($this->subjects, $item->dcValue); }
        }
        $this->creatorsString = implode('; ', $this->creators);
        $this->subjectsString = implode('; ', $this->subjects);
    }
    
    public function generateGenericXML($parentNode, $item, $namespaceOC)
    {
        $namespaceDC = App_Constants::DC_NS_URI;
        
        //add Dublin Core Metadata:
        foreach($this->metadataItems as $metadataItem)
            $parentNode->addChild($metadataItem->dcField, $metadataItem->dcValue, $namespaceDC);
        //add generic OpenContext Metadata:
        $nameNode = $parentNode->addChild('project_name', $this->project->name, $namespaceOC);
        $nameNode->addAttribute("href", App_Constants::PROJECT_URI . $this->project->uuID);
        $nameNode = $parentNode->addChild('primary_xsl', 'default/', $namespaceOC);
        if(isset($item->dataTableName))
            $parentNode->addChild('sourceID', $item->dataTableName, $namespaceOC);
        else
            $parentNode->addChild('sourceID', 'user input', $namespaceOC);
        $parentNode->addChild('pub_date', $item->timestamp, $namespaceOC);
        $parentNode->addChild('no_props', $this->project->noDataMessage, $namespaceOC);
        
        //add creative commons license:
        $this->project->setLicense();
        $lic = $this->project->license;
        $copyrightNode = $parentNode->addChild('copyright_lic', '', $namespaceOC);
        $copyrightNode->addChild('copyright_lic', $lic->name, $namespaceOC);
        $copyrightNode->addChild('lic_vers', $lic->version, $namespaceOC);
        $copyrightNode->addChild('lic_URI', $lic->licenseURL, $namespaceOC);
        $copyrightNode->addChild('lic_icon_URI', $lic->imageLink, $namespaceOC);
        //todo: geospatial stuff!
    }
    
}