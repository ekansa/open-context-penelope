<?php
class Link
{
    /*
    corresponds to links
    ------------------------
    project_id, source_id, hash_link, link_type, link_uuid, origin_type, origin_uuid, origin_obs, targ_type, targ_uuid, targ_obs, last_modified_timestamp    */
    
    //public variables
    public $origin;
    public $target;
    public $linkType;
    
    //private variables:
    private $targetUUID;
    public $targetType;
    
    function Link($origin, $targetUUID, $targetType, $linkType, $project)
    {
        //var_dump($project);
        $this->origin       = $origin;
        $this->targetUUID   = $targetUUID;
        $this->targetType   = $targetType;
        $this->linkType     = str_replace(' ', '', $linkType);
        
        //echo $targetType;
        switch($targetType)
        {
            case App_Constants::SPATIAL:
                Zend_Loader::loadClass('Space'); 
                $this->target = new Space($targetUUID, $project);
                break;
            case App_Constants::PERSON:
                Zend_Loader::loadClass('Person');  
                $this->target = new Person($targetUUID, $project);
                break;
            case App_Constants::PROJECT:
                Zend_Loader::loadClass('Project');  
                $this->target = Project::getProjectByUUID($targetUUID);
                break;
        }
    }
    
}


