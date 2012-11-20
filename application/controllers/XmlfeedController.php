<?php
require_once 'App/Controller/PenelopeController.php';   //handles the functionality common across controllers
require_once 'App/XMLHttpRequest.php';   //handles the functionality common across controllers


class XmlfeedController extends App_Controller_PenelopeController
{
    /*
        update `project_list` set `has_processed` = 0;
        update `space` set `has_processed` = 0;
        update `persons_st_des` set `has_processed` = 0;
        update `properties` set `has_processed` = 0;
        update `resource` set `has_processed` = 0;
    */
    
    /*function init()
    {
        $this->initView();
        $this->view->baseUrl = $this->_request->getBaseUrl();
        
        //library references:
        require_once 'App/Xml/Feed/Metadata.php';
        require_once 'App/Xml/Feed/Observations.php';
    }*/
    
    function indexAction()
    {
        parent::indexAction();        
        $this->view->title = "Data Importer";
    }
    
    function getCountsAction()
    {
        $this->_helper->viewRenderer->setNoRender();        
        $projectUUID    = $_REQUEST['projectUUID'];
        $this->project  = Project::getProjectByUUID($projectUUID);
        
        //get processing status:
        $processingCounts = $this->getCounts(0, array());
        $processingCounts = $this->getCounts(1, $processingCounts);       
        
        //Zend_Debug::dump($processingCounts);
        
        echo Zend_Json::encode($processingCounts);
    }
    
    private function getCounts($processedFlag, $processingCounts)
    {
        $suffix = '_completed';
        if($processedFlag == 0)
            $suffix = '_pending';
            
        $db = Zend_Registry::get('db');
        
        //1) DEFINE SELECT STATEMENTS:
        //determine whether the project has been processed:
        $selectProject = $db->select()
            ->from( 'project_list', array('cnt' => 'count(project_id)'))
            ->where('project_id  = ?', $this->project->uuID)
            ->where('has_processed = ?', $processedFlag); 
        
        //get number of space items to be processed:
        $selectSpace = $db->select()
            ->from( 'space', array('cnt' => 'count(uuid)'))
            ->where('project_id  = ?', $this->project->uuID)
            ->where('has_processed = ?', $processedFlag);            
            
        //get number of diary items to be processed:
        /*$selectDiary = $db->select()
            ->from( 'diary', array('cnt' => 'count(uuid)'))
            ->where('project_id  = ?', $this->project->uuID)
            ->where('has_processed = ?', $processedFlag);*/          
            
        //get number of people to be processed:
        $selectPeople = $db->select()
            ->from( 'persons_st_des', array('cnt' => 'count(distinct uuid)'))
            ->where('project_id  = ?', $this->project->uuID)
            ->where('has_processed = ?', $processedFlag);            
            
        //get number of resources to be processed:
        $selectResources = $db->select()
            ->from( 'resource', array('cnt' => 'count(uuid)'))
            ->where('project_id  = ?', $this->project->uuID)
            ->where('has_processed = ?', $processedFlag);
        
        $selectProperties = $db->select()
            ->from( 'properties', array('cnt' => 'count(property_uuid)'))
            ->where('project_id  = ?', $this->project->uuID)
            ->where('has_processed = ?', $processedFlag);   
        
        //2) EXECUTE QUERIES:
        $rows = $db->query($selectProject)->fetchAll();
        foreach($rows as $row) { $processingCounts['project_count' . $suffix] = $row['cnt']; }
        
        $rows = $db->query($selectSpace)->fetchAll();
        foreach($rows as $row) { $processingCounts['space_count' . $suffix] = $row['cnt']; }
        
        /*$rows = $db->query($selectDiary)->fetchAll();
        foreach($rows as $row) { $processingCounts['diary_count' . $suffix] = $row['cnt']; }*/
        
        $rows = $db->query($selectPeople)->fetchAll();
        foreach($rows as $row) { $processingCounts['person_count' . $suffix] = $row['cnt']; }
        
        $rows = $db->query($selectResources)->fetchAll();
        foreach($rows as $row) { $processingCounts['resource_count' . $suffix] = $row['cnt']; }
        
        $rows = $db->query($selectProperties)->fetchAll();
        foreach($rows as $row) { $processingCounts['property_count' . $suffix] = $row['cnt']; }
            
        //return array:
        return $processingCounts;
    }
    
    function processProjectAction()
    {
        //update `project_list` set `has_processed` = 0
        $this->_helper->viewRenderer->setNoRender();        
        $projectUUID    = $_REQUEST['projectUUID'];
        $this->project  = Project::getProjectByUUID($projectUUID);
        
        $fileNames = array();
        //echo $projectUUID;
        //return;
        $xDoc = $this->project->generateXML();
        $fileName = App_Xml_Generic::generateFileName($projectUUID, App_Constants::PROJECT); 
        $this->createFile($fileName, $xDoc->asXML());
        array_push($fileNames, $fileName);
        $this->publish($this->project, false);
        //$this->publish($this->project, true);
            
        //set the 'has_processed' flag
        Zend_Loader::loadClass('Table_Project');
        $projectTable = new Table_Project();
        $data = array('has_processed' => 1);
        $where = "project_id = '" . $projectUUID . "'";
        $projectTable->update($data, $where);
        
        echo Zend_Json::encode($fileNames);
    }
    
    function processSpaceAction()
    {
        //update `space` set `has_processed` = 0
        $this->_helper->viewRenderer->setNoRender();        
        $projectUUID    = $_REQUEST['projectUUID'];
        $this->project  = Project::getProjectByUUID($projectUUID);
        Zend_Loader::loadClass('Space');
        $fileNames = array();
        
        $db = Zend_Registry::get('db');
        $selectSpace = $db->select()
            ->from( 'space', array('uuid'))
            ->where('project_id  = ?', $projectUUID)
            ->where('has_processed  = ?', 0)
            ->limit(10,0);        
        $rows = $db->query($selectSpace)->fetchAll();
        
        foreach($rows as $row)
        {
            $spaceUUID =   $row['uuid'];
            $space = new Space($spaceUUID, $this->project);
            $xDoc = $space->generateXML();
            
            $fileName = App_Xml_Generic::generateFileName($spaceUUID, App_Constants::SPATIAL); 
            $this->createFile($fileName, $xDoc->asXML());
            array_push($fileNames, $fileName);
            $this->publish($space, false);
            
            //set the 'has_processed' flag
            $space = new Table_Space();
            $data = array('has_processed' => 1);
            $where = "uuid = '" . $spaceUUID . "'";
            $space->update($data, $where);
        }
        
        echo Zend_Json::encode($fileNames);
    }
    
    function processPeopleAction()
    {
        //update `persons_st_des` set `has_processed` = 0
        $this->_helper->viewRenderer->setNoRender();        
        $projectUUID    = $_REQUEST['projectUUID'];
        $this->project  = Project::getProjectByUUID($projectUUID);
        Zend_Loader::loadClass('Person');
        Zend_Loader::loadClass('Table_UserStandardDescription');
        $fileNames = array();
        
        $db = Zend_Registry::get('db');
        $selectPeople = $db->select()
            ->distinct()
            ->from( 'persons_st_des', 'uuid')
            ->where('project_id  = ?', $this->project->uuID)
            ->where('has_processed = ?', 0)
            ->limit(10,0);  
              
        $rows = $db->query($selectPeople)->fetchAll();
        
        foreach($rows as $row)
        {
            $personUUID =   $row['uuid'];
            
            $person = new Person($personUUID, $this->project);
            $xDoc = $person->generateXML();
            
            
            $fileName = App_Xml_Generic::generateFileName($personUUID, App_Constants::PERSON); 
            $this->createFile($fileName, $xDoc->asXML());
            array_push($fileNames, $fileName);
            $this->publish($person, false);
            
            //set the 'has_processed' flag
            $user = new Table_UserStandardDescription();
            $data = array('has_processed' => 1);
            $where = "uuid = '" . $personUUID . "' and project_id = '" . $this->project->uuID . "'";
            $user->update($data, $where);
        }
        
        echo Zend_Json::encode($fileNames);
    }
    
    function processResourcesAction()
    {
        $this->_helper->viewRenderer->setNoRender();        
        $projectUUID    = $_REQUEST['projectUUID'];
        $this->project  = Project::getProjectByUUID($projectUUID);
        Zend_Loader::loadClass('Resource');     
        $fileNames = array();
        
        $db = Zend_Registry::get('db');
        $selectResources = $db->select()
            ->distinct()
            ->from( 'resource', 'uuid')
            ->where('project_id  = ?', $this->project->uuID)
            ->where('has_processed = ?', 0)
            ->limit(10,0);
               
        $rows = $db->query($selectResources)->fetchAll();
        
        foreach($rows as $row)
        {
            $resUUID    = $row['uuid'];
            $resource   = new Resource($resUUID, $this->project);
            $xDoc       = $resource->generateXML();
            
            $fileName = App_Xml_Generic::generateFileName($resUUID, App_Constants::MEDIA); 
            $this->createFile($fileName, $xDoc->asXML());
            array_push($fileNames, $fileName);
            $this->publish($resource, false);
            
            //set the 'has_processed' flag
            $resRec = new Table_Resource();
            $data = array('has_processed' => 1);
            $where = "uuid = '" . $resUUID . "'";
            $resRec->update($data, $where);
        }
        
        echo Zend_Json::encode($fileNames);
    }
    
    function processPropertiesAction()
    {
        //update `properties` set `has_processed` = 0
        $this->_helper->viewRenderer->setNoRender();        
        $projectUUID    = $_REQUEST['projectUUID'];
        $this->project  = Project::getProjectByUUID($projectUUID);
        Zend_Loader::loadClass('Property');
        $fileNames = array();
        
        $db = Zend_Registry::get('db');
        $selectProperties = $db->select()
            ->from( 'properties', array('property_uuid'))
            ->where('project_id  = ?', $projectUUID)
            ->where('has_processed  = ?', 0)
            ->where('variable_uuid <> ?', 'NOTES')
            ->limit(10,0);        
        $rows = $db->query($selectProperties)->fetchAll();
        
        foreach($rows as $row)
        {
            $propUUID   = $row['property_uuid'];
            $property   = new Property($propUUID, $this->project);
            $xDoc       = $property->generateXML();
            
            $fileName = App_Xml_Generic::generateFileName($propUUID, App_Constants::PROPERTY); 
            $this->createFile($fileName, $xDoc->asXML());
            array_push($fileNames, $fileName);
            $this->publish($property, false);
            
            //set the 'has_processed' flag
            $propRec = new Table_Property();
            $data = array('has_processed' => 1);
            $where = "property_uuid = '" . $propUUID . "'";
            $propRec->update($data, $where);
        }
        
        echo Zend_Json::encode($fileNames);
    }
    
    private function createFile($fileName, $stringXML)
    {
        $fileName = App_Constants::OUTPUT_DIRECTORY . '\\' . $fileName;
        $handle = fopen($fileName, 'w') or die("can't open file");
        fwrite($handle, $stringXML);
        fclose($handle);
    }
    
    function getErrorCodes()
    {
        return
            array(
                200 => 'OK',
                201 => 'Created',
                202 => 'Accepted',
                400 => 'Bad Request',
                401 => 'Unauthorized',
                403 => 'Forbidden',
                404 => 'Not Found',
                409 => 'Entry Already Exists'
            );
    }
    
    /**
     * This function publishes the XML file to the AtomPub Server.  See the
     * AtomPub readme file in the OpenContext repository for more information
     * but quickly, if you need to debug:
     * - atom entries are posted to:
     *     - {open-context-directory}/public/store (actual files)
     *     - {open-context-directory}/public/lists (adds entry to the list)
     *     - {open-context-directory}/public/cache (caches entries)
     * - currently, atom entries are deleted and refreshed if they already
     *   exist on the file system.
     */
    private function publish($item, $asAtomEntry)
    {
        $xDoc       = $item->xDoc;
        $title      = $item->name . ' (' . $item->getUUID() . ')';
        $slugName   = $this->getSlugName($item);
        $xmlString  = $xDoc->asXML();
        if($asAtomEntry)
        {
            $xmlString  = '<entry xmlns="http://www.w3.org/2005/Atom" 
                    xmlns:arch="'   . $item->namespaceArch . '"
                    xmlns:oc="'     . $item->namespaceOC .'" 
                    xmlns:gml="'    . App_Constants::GML_NS_URI . '" 
                    xmlns:dc="'     . App_Constants::DC_NS_URI . '"
                    >
                    <title>' . $title . '</title>
                    <id>urn:uuid:1225c695-cfb8-4ebb-aaaa-80da344efa6a</id>
                    <updated>2003-12-13T18:30:02Z</updated>
                    <author><name>Sarah Van Wart</name></author>
                    <content>This is an open context entry</content>' .
                    str_replace('<?xml version="1.0"?>', '', $xDoc->asXML()) .
                    '</entry>';
        }
                        
        Zend_Loader::loadClass('App_XMLHttpRequest');
        $ajax = new App_XMLHttpRequest();
        $url = "http://opencontext/publish1/";
        switch($item->getItemType())
        {
            case App_Constants::PROJECT:
                $url .= 'project/';
                break;
            case App_Constants::SPATIAL:
                $url .= 'space/';
                break;
            case App_Constants::PERSON:
                $url .= 'people/';
                break;
            case App_Constants::PROPERTY:
                $url .= 'property/';
                break;
            case App_Constants::MEDIA:
                $url .= 'media/';
                break;
            default:
                $url .= 'test/';
                break;
        }
        $ajax->open("POST", $url );
        $ajax->setRequestHeader("Slug", App_Xml_Generic::escapeSpacesAndSlashes(urlencode($slugName)));
        if($asAtomEntry)
            $ajax->setRequestHeader("Content-Type", "application/atom+xml;type=entry");
        else            
            $ajax->setRequestHeader("Content-Type", "application/xml;");
        $ajax->send($xmlString);

        $codes = $this->getErrorCodes();
        $success = false;
        switch($ajax->status)
        {
            case 409:
                $this->deleteEntry($item, $asAtomEntry);
                //remove and re-add atom entry
                break;
            case 201:
                //created successfully
                $success = true;
                break;
        }
        /*
        if(isset($codes[$ajax->status]))
            echo 'Http Status Code ' . $ajax->status . ': ' . $codes[$ajax->status];
        else
            echo 'Unknown code: ' , $ajax->status;
        echo $ajax->responseText;*/
        return $success;
    }
    
    private function deleteEntry($item, $asAtomEntry)
    {
        $xDoc       = $item->xDoc;
        $title      = $item->name . ' (' . $item->getUUID() . ')';
        $slugName   = $this->getSlugName($item);
        $entryName = $slugName . '.atomentry';
        Zend_Loader::loadClass('App_XMLHttpRequest');
        $ajax = new App_XMLHttpRequest();
        $url = "http://opencontext/publish1/";
        switch($item->getItemType())
        {
            case App_Constants::PROJECT:
                $url .= 'project/';
                break;
            case App_Constants::SPATIAL:
                $url .= 'space/';
                break;
            case App_Constants::PERSON:
                $url .= 'people/';
                break;
            case App_Constants::PROPERTY:
                $url .= 'property/';
                break;
            case App_Constants::MEDIA:
                $url .= 'media/';
                break;
            default:
                $url .= 'test/';
                break;
        }
        $url .= $entryName;
        
        //echo $url;
        $ajax->open("DELETE", $url);
        $ajax->setRequestHeader("Slug", $slugName);
        $ajax->setRequestHeader("Content-Type", "application/atom+xml;type=entry");
        $ajax->send();
        $codes = $this->getErrorCodes();
        $success = false;
        if($ajax->status == 200)
        {
            //since you've deleted the item, re-add the newer version:
            $this->publish($item, $asAtomEntry);
        }
        else
        {
            if(isset($codes[$ajax->status]))
                echo 'Http Status Code ' . $ajax->status . ': ' . $codes[$ajax->status];
            else
                echo 'Unknown code: ' , $ajax->status;
            echo $ajax->responseText;
        }
    }
    
    private function getSlugName($item)
    {
        //$slugName   = str_replace(' ', '-', $item->name) . '-' . $item->getUUID();
        $slugName   = str_replace(' ', '_', $item->getItemType()) . '-' . $item->getUUID();
        $slugName   = str_replace('/', '-', $slugName);
        $slugName   = str_replace('(', '', $slugName);
        $slugName   = str_replace(')', '', $slugName);
        return $slugName;
    }
    
    
    
}