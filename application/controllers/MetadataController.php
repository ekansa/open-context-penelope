<?php

require_once 'App/Controller/PenelopeController.php';   //handles the functionality common across controllers

class MetadataController extends App_Controller_PenelopeController
{
    function indexAction()
    {
        parent::indexAction();
        $this->view->title = "Data Importer";
    }
    
    function getProjectAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $projectUUID    = $_REQUEST['projectUUID'];
        $project        = Project::getProjectByUUID($projectUUID);
        $project->setMetadataInformation();
        //Zend_Debug::dump($project);
        echo Zend_Json::encode($project);
    }
    
    function describeProjectAction()
    {
        //this line is necessary for ajax calls:
        $this->_helper->viewRenderer->setNoRender();
        //echo 'hello!';
        //return;
        $projectID = $_REQUEST['projectUUID'];
        $db = Zend_Registry::get('db');
        $sql = "SET collation_connection = utf8_unicode_ci;";
		  $db->query($sql, 2);
		  $sql = "SET NAMES utf8;";
		  $db->query($sql, 2);
        $sql = 'SELECT * FROM project_list WHERE project_id = "'.$projectID.'"';
        $results = $db->fetchAll($sql, 2);
        
        if($results){
            $result_string = Zend_Json::encode($results[0]);
            $result_string = str_replace('"abstract":', '"long_des":', $result_string);
        }
        else{
            $result_string = null;
        }
        echo $result_string;
    }
    
    function editProjectAction()
    {
        //this line is necessary for ajax calls:
        $this->_helper->viewRenderer->setNoRender();
        
        $projectID = $_REQUEST['projectUUID'];
        $data = array();
        
        
        if(($_REQUEST['projName']) != null){
            $data['project_name'] = $_REQUEST['projName'];
        }
        
        if(($_REQUEST['projSDes']) != null){    
            $data['short_des'] = $_REQUEST['projSDes'];
        }
        
        if(($_REQUEST['projLDes'])!= null){
            $data['abstract'] = $_REQUEST['projLDes'];
        }
        
        if(($_REQUEST['rootName'])!= null){
            $data['parcontext_name'] = $_REQUEST['rootName'];
        }
        
        if(($_REQUEST['rootID'])!= null){
            $data['parcontext_id'] = $_REQUEST['rootID'];
        }
        
        if(($_REQUEST['rootClass'])!= null){
            $data['parcontext_class'] = $_REQUEST['rootClass'];
        }
        
        if(($_REQUEST['noData'])!= null){
            $data['noprop_mes'] = $_REQUEST['noData'];
        }
        
        $DCcreators = false;
        if(($_REQUEST['projCreat'])!= null)
        {
            $DCcreators_string = $_REQUEST['projCreat'];
            $DCcreators = explode(";", $DCcreators_string);
        }
        
        $DCsubjects = false;
        if(($_REQUEST['projSubs'])!= null)
        {
            $DCsubjects_string = $_REQUEST['projSubs'];
            $DCsubjects = explode(";", $DCsubjects_string);
        }
        
        $db = Zend_Registry::get('db');
        $sql = "SET collation_connection = utf8_unicode_ci;";
		  $db->query($sql, 2);
		  $sql = "SET NAMES utf8;";
		  $db->query($sql, 2);
        
        if(count($data)>=1)
        {
            $n = $db->update('project_list', $data, 'project_id = "'.$projectID.'"');
        }
        
        if($DCcreators)
        {
            
            $meta_tag = "dc:creator";
            $where = array();
            $where[] = 'project_id = "'.$projectID.'"';
            $where[] = 'dc_field = "'.$meta_tag.'"';
            $db->delete('dcmeta_proj', $where);
            $sort = 1;
            foreach($DCcreators as $act_meta){
                $act_meta = trim($act_meta);
                $dc_hash = md5($projectID."_".$meta_tag."_".$act_meta);
                $dc_data = array('hash_id' => $dc_hash, 
                                 'project_id' => $projectID,
                                 'sort' => $sort,
                                 'dc_field' => $meta_tag,
                                 'dc_value' => $act_meta
                                 );
                try
                {
                    $db->insert('dcmeta_proj', $dc_data);
                }
                catch(Exception $e)
                {
                    Zend_Debug::dump($e);
                }
                $sort++;
            }     
        }
        
        if($DCsubjects)
        {            
            $meta_tag = "dc:subject";
            $where = array();
            $where[] = 'project_id = "'.$projectID.'"';
            $where[] = 'dc_field = "'.$meta_tag.'"';
            $db->delete('dcmeta_proj', $where);
            $sort = 1;
            foreach($DCsubjects as $act_meta)
            {
                $act_meta = trim($act_meta);
                $dc_hash = md5($projectID."_".$meta_tag."_".$act_meta);
                $dc_data = array('hash_id' => $dc_hash, 
                                 'project_id' => $projectID,
                                 'sort' => $sort,
                                 'dc_field' => $meta_tag,
                                 'dc_value' => $act_meta
                                 );
                try
                {
                    $db->insert('dcmeta_proj', $dc_data);
                }
                catch(Exception $e)
                {
                    Zend_Debug::dump($e);
                }
                $sort++;
            }
              
        }        
        echo Zend_Json::encode($data);
        
    }//end project edit
}