<?php
class License
{
    const LICENSE_IMAGE_URL = '';
    public $id;
    public $name;
    public $description;
    public $version;
    public $canBeCommercial; 
    public $modRequirement;  
    public $imageFileName;   
    public $licenseURL;  
    public $legalURL;
    public $imageLink;
    
    function License($_data) //where $_data is a "ResultRowObject"
    {
        $this->id               = $_data->PK_LICENSE;
        $this->name             = $_data->NAME;
        $this->description      = $_data->DESCRIPTION;
        $this->version          = $_data->VERSION;
        $this->canBeCommercial  = $_data->CAN_BE_COMMERCIAL;
        $this->modRequirement   = $_data->MODIFIED_REQUIREMENT;
        $this->imageFileName    = $_data->IMAGE_FILE_NAME;
        $this->licenseURL       = $_data->LINK_DEED;
        $this->legalURL         = $_data->LINK_LEGAL;
        $this->imageLink        = $_data->IMAGE_LINK;
    }

    
}