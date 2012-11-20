<?php
require_once 'Item.php';
require_once 'App/Xml/XmlGenerator.php';
class Property extends Item implements App_Xml_XmlGenerator
{
    /*
    corresponds to w_property
    -----------------------
    project_id, source_id, prop_hash, property_uuid, variable_uuid, value_uuid, val_num, last_modified_timestamp`
    */
    
    //public variables
    public $propertyUUID;
    public $projectUUID;
    public $valNum;
    public $timestamp;
    public $variable;
    public $value;
    public $name;
    
    //private variables
    private $variableUUID;
    private $valueUUID;
    public $namespaceArch   = App_Constants::ARCHAEOML_NS_URI_PROPERTY;
    public $namespaceOC     = App_Constants::OC_NS_URI_PROPERTY;
    
    function Property($propertyUUID, $project)
    {
        Zend_Loader::loadClass('Table_Property');        
        Zend_Loader::loadClass('Variable');        
        Zend_Loader::loadClass('Value');
        $this->projectUUID  = $propertyUUID;
        $this->project      = $project;
        $property           = new Table_Property();
        $propertyRow        = $property->fetchRow("property_uuid  = '" . $propertyUUID . "'");
        
        //Zend_Debug::dump($propertyRow);
        $this->propertyUUID = $propertyUUID;
        $this->projectUUID  = $propertyRow->project_id;
        $this->valNum       = $propertyRow->val_num;
        $this->timestamp    = $propertyRow->last_modified_timestamp;
        $this->variableUUID = $propertyRow->variable_uuid;
        $this->valueUUID    = $propertyRow->value_uuid;
        //echo 'valueUUID: ' . $this->valueUUID;
        
        $this->value        = new Value($this->valueUUID);
        $this->variable     = new Variable($this->variableUUID);
        $this->name         = $this->variable->varLabel . ': ' . $this->value->valText;
    }
    
    public function getItemType() { return App_Constants::PROPERTY; }
    public function getUUID() { return $this->propertyUUID; }
    public function getProjectUUID() { return $this->projectUUID; }
    
    public function generatePropertyXML($propertiesNode, $namespaceArch, $namespaceOC)
    {
        $propertyNode = $propertiesNode->addChild('property', '', $namespaceArch);
        $this->validateValues($propertyNode, $namespaceArch);
        $this->addOCNSData($propertyNode, $namespaceOC);
    }
    
    private function validateValues($propertyNode, $namespaceArch)
    {
        $valTypeOK  = false;           
        $propertyNode->addChild('variableID' , $this->variableUUID, $namespaceArch);
        switch($this->variable->varType)
        {
            case App_Constants::INTEGER:
                $valTypeOK  = true;
                $intOK      = false;
                $this->value->valText = $this->value->valText + 0;
                               
                if (intval($this->value->valText) === $this->value->valText)
                    $intOK = true;
                
                if(is_int($this->value->valText) || $intOK)
                {
                    $propertyNode->addChild($this->varType, $this->value->valText, $namespaceArch);  
                }
                else
                {
                    $this->variable->varType = App_Constants::ALPHANUMERIC;
                    $propertyNode->addChild('valueID' , $this->valueUUID, $namespaceArch);         
                }
                break;
            case App_Constants::DECIMAL:
                $valTypeOK = true;
                $this->value->valText = $this->value->valText + 0;
                
                if(is_numeric($this->value->valText))
                {
                    $propertyNode->addChild($this->varType, $this->value->valText, $namespaceArch);  
                }
                else
                {
                    $this->variable->varType = App_Constants::ALPHANUMERIC;
                    $propertyNode->addChild('valueID' , $this->valueUUID, $namespaceArch);           
                }
                break;
            case App_Constants::BOOLEAN:
                $valTypeOK = true;
                $propertyNode->addChild($this->varType, $this->value->valText, $namespaceArch);  
                break;
            case App_Constants::CALENDAR:
                $valTypeOK  = true;                
                $xmlValOK   = date("Y-m-d\TH:i:s\Z", strtotime($this->value->valText));
                
                if($this->value->valText != '0000-00-00')
                {
                    $propertyNode->addChild('date', $xmlValOK, $namespaceArch);
                }
                else
                {
                    $this->var_type = App_Constants::ALPHANUMERIC;
                    $propertyNode->addChild('valueID', $this->valueUUID, $namespaceArch);         
                }
                break;
            case App_Constants::ALPHANUMERIC:
            case App_Constants::NOMINAL:
            case App_Constants::ORDINAL:
                $valTypeOK = true;
                $propertyNode->addChild('valueID', $this->valID, $namespaceArch);            
                break;
        }

        if(!$valTypeOK)
        {
            $this->variable->varType = App_Constants::ALPHANUMERIC;	
            $propertyNode->addChild('valueID', $this->valueUUID, $namespaceArch);          
        }        
            
    }//end Assign_validate_value function
    
    
    
    //this function only creates Open Context elements if 
    //a standard ArchaeoML document is NOT requested
    private function addOCNSData($propertyNode, $namespaceOC)
    {
        $varLabel   = App_Xml_Generic::parseXMLcoding($this->variable->varLabel);
        $showVal    = App_Xml_Generic::parseXMLcoding($this->value->valText);
        
        $propertyIdNode = $propertyNode->addChild('propid', $this->propertyUUID, $namespaceOC);
        $propertyIdNode->addAttribute('href', App_Constants::PROPERTY_URI . $this->propertyUUID);
        
        $varNode = $propertyNode->addChild('var_label', $this->variable->varLabel, $namespaceOC);
        $varNode->addAttribute('type', $this->variable->varType);
        
        $propertyNode->addChild("show_val", $this->value->valText, $namespaceOC);
    }
    
    public function generateXML()
    {
        if(isset($this->xDoc))
            return $this->xDoc;
        $strXML = '<arch:property 
                xmlns:arch="'   . $this->namespaceArch . '"
                xmlns:oc="'     . $this->namespaceOC .'" 
                xmlns:gml="'    . App_Constants::GML_NS_URI . '" 
                xmlns:dc="'     . App_Constants::DC_NS_URI . '"
                />';
                
        $this->xDoc = new SimpleXMLElement($strXML);
        $this->generateGenericXML($this->xDoc);
        $this->generateMetadataXML($this->xDoc);
        return $this->xDoc;
    }
    
    private function generateGenericXML($parentNode)
    {
        //add top-level attributes:
        $parentNode->addAttribute("UUID", $this->getUUID());
        $parentNode->addAttribute("ownedBy", $this->projectUUID);
        
        $nameNode = $parentNode->addChild('name', '', $this->namespaceArch);
        $nameNode->addChild('string', $this->value->valText, $this->namespaceArch);
                
        $manageInfoNode = $parentNode->addChild('manage_info', '', $this->namespaceOC);
        $manageInfoNode->addAttribute("variableID", $this->variableUUID);
        $manageInfoNode->addChild("valueID", $this->valueUUID);
        
        $manageInfoNode->addChild("queryVal", "", $this->namespaceOC);
        $manageInfoNode->addChild("varType", $this->variable->varType, $this->namespaceOC);
        $manageInfoNode->addChild("propVariable", $this->variable->varLabel, $this->namespaceOC);
        $manageInfoNode->addChild("propVariable", $this->variable->varLabel, $this->namespaceOC);
        $manageInfoNode->addChild("propStats", "", $this->namespaceOC);
        $manageInfoNode->addChild("graphData", "", $this->namespaceOC);
    }
    
    private function generateMetadataXML($parentNode)
    {
        $this->project->setMetadataInformation();
        $metadataNode = $parentNode->addChild('metadata', '', $this->namespaceOC);
        $this->project->metadata->generateGenericXML($metadataNode, $this, $this->namespaceOC);
    }
}


