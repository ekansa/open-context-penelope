<?php

// increase the memory limit
ini_set("memory_limit", "2048M");
// set maximum execution time to no limit
ini_set("max_execution_time", "0");

class PreviewEditController extends Zend_Controller_Action
{
    
    public $baseURL = "http://penelope.oc";
    
    function init()
    {
        $this->baseURL = "http://".$_SERVER['SERVER_NAME'];
        $this->view->baseUrl = $this->_request->getBaseUrl();
        Zend_Loader::loadClass('User'); //defined in User.php
        Zend_Loader::loadClass('Form_Login'); //defined in User.php
        Zend_Loader::loadClass('Table_Project');
        Zend_Loader::loadClass('Zend_Debug');
        Zend_Loader::loadClass('Zend_Dojo_Data');
        Zend_Loader::loadClass('Form_Upload');
        Zend_Loader::loadClass('Zend_Cache');
        require_once 'App/Util/AtomMake.php';
    }
    
    function spaceAction(){
        $this->_helper->viewRenderer->setNoRender();
        $itemUUID = $_REQUEST["UUID"];
        if(isset($_REQUEST["format"])){
            if($_REQUEST["format"] == "atom"){
                $doAtom = true;
                $doXML = false;
                $doEdit = false;
            }
            if($_REQUEST["format"] == "xml"){
                $doAtom = false;
                $doXML = true;
                $doEdit = false;
            }
            if($_REQUEST["format"] == "edit"){
                $doAtom = false;
                $doXML = false;
                $doEdit = true;
            }
        }
        else{
            $doAtom = false;
            $doXML = false;
            $doEdit = false;
        }
        
        $config = new Zend_Config_Ini('/application/config.ini', 'general');
        $dbName = $config->db->config->dbname;
      
        $xslFile = "edit_preview_defaultSpatial_rdfa.xsl";
        $spaceURI = "http://".$_SERVER["SERVER_NAME"]."/xml/space?xml=1&id=".$itemUUID;
        $xslString = file_get_contents("http://".$_SERVER["SERVER_NAME"]."/xsl/".$xslFile );
        $spaceString = file_get_contents($spaceURI);
        
        //$this->_helper->viewRenderer->setNoRender();
        //echo $spaceString;
        
        $AtomString = AtomMake::spatialAtomCreate($spaceString);
        
        $AtomString = str_replace("http://about.opencontext.org/schema/space_schema_v1.xsd",
                                  "http://www.opencontext.org/database/schema/space_schema_v1.xsd", $AtomString);
        
        $atom = simplexml_load_string($AtomString);
        
        if (!$doAtom && !$doXML && !$doEdit) {        
            $doc = new DOMDocument();
            //$doc->load($xslString);
            $doc->load("xsl/".$xslFile);
            $proc = new XSLTProcessor();
            $xslt = $proc->importStylesheet($doc);
            $atomDoc = new DomDocument();
            $atomDoc->loadXML($atom->asXML());
            
            header('Content-type: application/xhtml+xml', true);
            echo $proc->transformToXML($atomDoc);
        }
        elseif($doEdit){
            $doc = new DOMDocument();
            //$doc->load($xslString);
            $doc->load("xsl/editSpatial.xsl");
            $proc = new XSLTProcessor();
            $xslt = $proc->importStylesheet($doc);
            $atomDoc = new DomDocument();
            $atomDoc->loadXML($atom->asXML());
            echo $proc->transformToXML($atomDoc);
        }
        else{
            header("Content-type: application/xml");
            if(!$doXML){
                echo $AtomString;
            }
            else{
                echo $spaceString;
            }
        }
        
        
    }//end space action
    

    function propertyAction(){
        $this->_helper->viewRenderer->setNoRender();
        $propUUID = $_REQUEST["UUID"];
        
        $config = new Zend_Config_Ini('/application/config.ini', 'general');
        $dbName = $config->db->config->dbname;
        
        //$propURI = "http://about.oc/oc_xmlgen/property.php?imp=".$dbName."&item=";
        $propURI = "http://".$_SERVER["SERVER_NAME"]."/xml/property?xml=1&id=";
        $xslString = file_get_contents("http://".$_SERVER["SERVER_NAME"]."/xsl/defaultMedia.xsl");
        
        $propString = file_get_contents($propURI.$propUUID);
        header("Content-type: application/xml");
        echo $propString;
    }


    function mediaAction(){
        $this->_helper->viewRenderer->setNoRender();
        $itemUUID = $_REQUEST["UUID"];
        if(isset($_REQUEST["format"])){
            if($_REQUEST["format"] == "atom"){
                $doAtom = true;
                $doXML = false;
                $doEdit = false;
            }
            if($_REQUEST["format"] == "xml"){
                $doAtom = false;
                $doXML = true;
                $doEdit = false;
            }
            if($_REQUEST["format"] == "edit"){
                $doAtom = false;
                $doXML = false;
                $doEdit = true;
            }
        }
        else{
            $doAtom = false;
            $doXML = false;
            $doEdit = false;
        }
        
        $xslFile = "edit_preview_defaultMedia.xsl";
        $itemURI = "http://".$_SERVER["SERVER_NAME"]."/xml/media?xml=1&id=";
        $xslString = file_get_contents("http://".$_SERVER["SERVER_NAME"]."/xsl/".$xslFile );
        $itemString = file_get_contents($itemURI.$itemUUID);
        //$doXML = true;
        
        $AtomString = AtomMake::resourceAtomCreate($itemString);
        $atom = simplexml_load_string($AtomString);
        
        
        if (!$doAtom && !$doXML && !$doEdit) {        
            $doc = new DOMDocument();
            //$doc->load($xslString);
            $doc->load("xsl/".$xslFile );
            $proc = new XSLTProcessor();
            $xslt = $proc->importStylesheet($doc);
            $atomDoc = new DomDocument();
            $atomDoc->loadXML($atom->asXML());
            echo $proc->transformToXML($atomDoc);
        }
        elseif($doEdit){
            $doc = new DOMDocument();
            //$doc->load($xslString);
            $doc->load("xsl/editMedia.xsl");
            $proc = new XSLTProcessor();
            $xslt = $proc->importStylesheet($doc);
            $atomDoc = new DomDocument();
            $atomDoc->loadXML($atom->asXML());
            echo $proc->transformToXML($atomDoc);
        }
        else{
            header("Content-type: application/xml");
            if(!$doXML){
                echo $AtomString;
            }
            else{
                echo $itemString;
            }
        }
        
    }//end media action


    function documentAction(){
        $this->_helper->viewRenderer->setNoRender();
        $itemUUID = $_REQUEST["UUID"];
        if(isset($_REQUEST["format"])){
            if($_REQUEST["format"] == "atom"){
                $doAtom = true;
                $doXML = false;
                $doEdit = false;
            }
            if($_REQUEST["format"] == "xml"){
                $doAtom = false;
                $doXML = true;
                $doEdit = false;
            }
            if($_REQUEST["format"] == "edit"){
                $doAtom = false;
                $doXML = false;
                $doEdit = true;
            }
        }
        else{
            $doAtom = false;
            $doXML = false;
            $doEdit = false;
        }
        
        $xslFile = "preview_defaultDiary.xsl";
        //$xslFile = "preview_defaultMedia.xsl";
        $itemURI = "http://".$_SERVER["SERVER_NAME"]."/xml/document?xml=1&id=";
        $xslString = file_get_contents("http://".$_SERVER["SERVER_NAME"]."/xsl/".$xslFile );
        $itemString = file_get_contents($itemURI.$itemUUID);
        //$doXML = true;
        
        $AtomString = ($itemString);
        $atom = simplexml_load_string($AtomString);
        
        
        if (!$doAtom && !$doXML && !$doEdit) {        
            $doc = new DOMDocument();
            //$doc->load($xslString);
            $doc->load("xsl/".$xslFile );
            $proc = new XSLTProcessor();
            $xslt = $proc->importStylesheet($doc);
            $atomDoc = new DomDocument();
            $atomDoc->loadXML($atom->asXML());
            echo $proc->transformToXML($atomDoc);
        }
        elseif($doEdit){
            $doc = new DOMDocument();
            //$doc->load($xslString);
            $doc->load("xsl/editMedia.xsl");
            $proc = new XSLTProcessor();
            $xslt = $proc->importStylesheet($doc);
            $atomDoc = new DomDocument();
            $atomDoc->loadXML($atom->asXML());
            echo $proc->transformToXML($atomDoc);
        }
        else{
            header("Content-type: application/xml");
            if(!$doXML){
                echo $AtomString;
            }
            else{
                echo $itemString;
            }
        }
        
    }//end media action



    


}