<?php

// increase the memory limit
ini_set("memory_limit", "512M");
// set maximum execution time to no limit
ini_set("max_execution_time", "0");


class PublishController extends Zend_Controller_Action
{
    
    const xmlRoot = "http://about.oc/oc_xmlgen/";
    const defDest = "http://opencontext/publish/itempublish";
    public $hostRoot = "http://penelope.oc";
    const mediaOnly = false;
    
    function init()
    {
        $this->hostRoot = "http://".$_SERVER['SERVER_NAME'];
        $this->view->baseUrl = $this->_request->getBaseUrl();
        Zend_Loader::loadClass('User'); //defined in User.php
        Zend_Loader::loadClass('Form_Login'); //defined in User.php
        Zend_Loader::loadClass('Table_Project');
        Zend_Loader::loadClass('Zend_Debug');
        Zend_Loader::loadClass('Zend_Dojo_Data');
        Zend_Loader::loadClass('Form_Upload');
        Zend_Loader::loadClass('Zend_Cache');
    }
    
    function indexAction()
    {
        $projectUUID = $_REQUEST["projectUUID"];
       
       
        $this->view->host = $this->hostRoot;       
        $this->view->publishURI = self::defDest;

        $this->view->projectUUID = $projectUUID;
       

        //add "User" object to the view:
        //$user = User::getCurrentUser();
        //$user->getProjects();
        //$this->view->user = $user;
        
        $db = Zend_Registry::get('db');
        
        $select = $db->select()
            ->distinct()
            ->from(
                    array('c' => 'project_list'),
                    array('c.project_name')
            )
            ->where('c.project_id = ?', $projectUUID);
        
        $dataRows = $db->query($select)->fetchAll();
        $this->view->projectName = $dataRows[0]["project_name"];
        
        $select = $db->select()
            ->distinct()
            ->from(
                    array('c' => 'file_summary'),
                    array('c.source_id', 'c.description', 'c.last_modified_timestamp')
            )
            ->where('c.project_id = ?', $projectUUID);
        
        $dataRows = $db->query($select)->fetchAll();
        $this->view->tableRows = $dataRows;
        
    }//end index action
    

    function publishdocAction(){
        
        $this->_helper->viewRenderer->setNoRender();
        $host = "http://".$_SERVER['SERVER_NAME'];
        $projectUUID = $_REQUEST["projectUUID"];
        $itemUUID = $_REQUEST["itemUUID"];
        $itemType = $_REQUEST["itemType"];
        
        $useURI = false;
        $getXML = true;
        if(isset($_REQUEST["prefixURI"])){
            if(strlen($_REQUEST["prefixURI"])>4){
                $useURI = $_REQUEST["prefixURI"];
                $useURI .= $itemUUID; //add the item ID to the URI prefix
                
                if(isset($_REQUEST["suffixURI"])){
                    $useURI .= $_REQUEST["suffixURI"];
                }
                $getXML = false;
            }
        }
        
        if(isset($_REQUEST["pubURI"])){
            $clientURI = $_REQUEST["pubURI"];
        }
        else{
            $clientURI = self::defDest;
        }
        
        $doClientURI = str_replace("itempublish", "item-publish", $clientURI);
        
        
        if(isset($_REQUEST["doUpdate"])){
            $doUpdate = $_REQUEST["doUpdate"];
            if($doUpdate == "false"){
                $doUpdate = false;
            }
            if($doUpdate == "true"){
                $doUpdate = true;
                //sleep(.15);
            }
        }
        else{
            $doUpdate = false;
        }
        
        
        if($itemType == "upSpace" || $itemType == "linkedSpace"){
            $itemType = "space";
            $doUpdate = true;
        }
        
        
        $limitList = 0;
        if(isset($_REQUEST["limitList"])){
            if($_REQUEST["limitList"] == 1){
                $limitList = 1;
            }
        }
        
        
        if($getXML){
            $db = Zend_Registry::get('db');
            $xmlGenURL = false;
            if($itemType == "space"){
                $xmlGenURL = "http://".$_SERVER['SERVER_NAME']."/xml/space?xml=1&id=".$itemUUID."&limitList=".$limitList;
                $xmlData = file_get_contents($xmlGenURL);
            }
            if($itemType == "person"){
                $xmlGenURL = "http://".$_SERVER['SERVER_NAME']."/xml/person?xml=1&id=".$itemUUID;
                $xmlData = file_get_contents($xmlGenURL);
            }
            if($itemType == "NCprop"){
                $xmlGenURL = "http://".$_SERVER['SERVER_NAME']."/xml/property?xml=1&id=".$itemUUID."&limitList=".$limitList;
                //$xmlData = file_get_contents($xmlGenURL);
                $xmlData = false;
            }
            if($itemType == "prop"){
                $xmlGenURL = "http://".$_SERVER['SERVER_NAME']."/xml/property?xml=1&id=".$itemUUID."&limitList=".$limitList;
                $xmlData = file_get_contents($xmlGenURL);
            }
            if($itemType == "media"){
                $xmlGenURL = "http://".$_SERVER['SERVER_NAME']."/xml/media?xml=1&id=".$itemUUID;
                $xmlData = file_get_contents($xmlGenURL);
            }
            if($itemType == "proj"){
                $xmlGenURL = "http://".$_SERVER['SERVER_NAME']."/xml/project?xml=1&id=".$itemUUID;
                $xmlData = file_get_contents($xmlGenURL);
            }
            if($itemType == "doc"){
                $xmlGenURL = "http://".$_SERVER['SERVER_NAME']."/xml/document?xml=1&id=".$itemUUID;
                $xmlData = file_get_contents($xmlGenURL);
            }
            
            @$xml = simplexml_load_string($xmlData);
        }//end case where XML is send in request
        
        
        $client = new Zend_Http_Client($doClientURI, array(
                'maxredirects' => 0,
                'timeout'      => 20));
        
        if($getXML){
            $clientParams = array(
                'xml'  => $xmlData,
                'itemType'   => $itemType,
                'itemUUID' => $itemUUID
            );
        }
        else{
            $clientParams = array(
                'useURI'  => $useURI,
                'itemType'   => $itemType,
                'itemUUID' => $itemUUID
            );
        }
        
        
        if($doUpdate){
            $clientParams['doUpdate'] = $doUpdate; 
        }
        
        $client->setParameterPost($clientParams);
        $data = false;
        $output = array(    "itemUUID" => $itemUUID,
                            "itemType" => $itemType);
         
        @$response = $client->request('POST');
        if(!$response->isError()){
            $responseJSON = $response->getBody();
            @$responseObj = Zend_Json::decode($responseJSON);
            $output["serverResp"] = $responseObj;
            if(!$responseObj){
                $output["OKserverJSON"] = false;
                $output["serverError"] = $response->getStatus().": ".$response->getMessage();
                $output["serverURI"] = $doClientURI;
                $output["sentParams"] = $clientParams;
                $output["respBody"] = $response->getBody();
            }
            else{
                $output["OKserverJSON"] = true;
                
                $db = Zend_Registry::get('db');
                $hashKey = md5($clientURI.$itemUUID);
                
                $data = array(  "hash_key" => $hashKey,
                                "pubdest" => $clientURI,
                                "project_id" => $projectUUID,
                                "item_uuid" => $itemUUID,
                                "item_type" => $itemType);
                
                if($responseObj["pubOK"]){
                    $output["pubStatus"] = "OK message";
                    $output["error"] = "No errors";
                    $data["status"] = "published";
                }
                else{//case where publishing worked
                    $output["pubStatus"] = "Open Context sends error.";
                    if(isset($responseObj["errors"])){
                        if(is_array($responseObj["errors"])){
                            if(count($responseObj["errors"])>0){
                                $output["status"] = "Errors: ".implode(", ", $responseObj["errors"]);
                                $output["error"] = "Errors: ".implode(", ", $responseObj["errors"]);
                            }
                        }
                    }
                    $data["status"] = "Error adding data";
                }
               
            }
            
            
            if(is_array($data)){
                try{
                    $db->insert('published_docs', $data);
                }
                catch (Exception $e) {
                    $output["pubStatus"] =  $output["pubStatus"] . " Penelope already thinks this is published.";
                }
            }
            
        }//end case with response
        else{
            $output["serverError"] = $response->getStatus().": ".$response->getMessage();
            $output["serverURI"] = $doClientURI;
            $output["serverResp"] = "HTTP error!";
            $hashKey = md5($clientURI.$itemUUID);
            $data = array("hash_key" => $hashKey,
                          "pubdest" => $clientURI,
                          "project_id" => $projectUUID,
                              "item_uuid" => $itemUUID,
                              "item_type" => $type);
            $data["status"] = "Error HTTP bad response";
            $output["error"] = "Error HTTP bad response";
            $output["pubStatus"] = "Error HTTP bad response";
        }
        
        $output["req_uri"] = $host.$_SERVER['REQUEST_URI'];
        $output["xml_gen_uri"] = $xmlGenURL;
        
        header('Content-Type: application/json; charset=utf8');
        echo Zend_Json::encode($output) ;
    }


    private function makeTabCond($itemTable, $tabIDs){
        $where = "";
        
        if(!is_array($tabIDs)){
            if(strlen( $tabIDs)>3){
                $where .= $itemTable.".source_id = '".$tabIDs."' ";
            }
            else{
                $where = " 1 = 1 ";
            }
        }
        else{
            $i = 0;
            foreach($tabIDs as $actTab){
                if($i>0){
                   $where .= " OR "; 
                }
                $where .= $itemTable.".source_id = '".$actTab."' ";
            $i++;
            }
        }
        if(strlen($where)>3){
            $where = " (".$where.") AND ";
        }
        
        return $where;
    }


    //a limit list narrows down the range of items to publish. The list is popualted elsewhere. Only
    //items in the limit list will be selected for publication
    private function makeLimitListJoin($tabName, $limitList){
        $output = "";
        
        if($limitList){
            if($tabName == "users"){
                $output = " JOIN publish_to_do ON users.uuid = publish_to_do.uuid ";
            }
            elseif($tabName == "persons"){
                $output = " JOIN  publish_to_do ON persons.uuid = publish_to_do.uuid ";
            }
            elseif($tabName == "space"){
                $output = " JOIN  publish_to_do ON space.uuid = publish_to_do.uuid ";
            }
            elseif($tabName == "resource"){
                $output = " JOIN  publish_to_do ON resource.uuid = publish_to_do.uuid ";
            }
            elseif($tabName == "diary"){
                $output = " JOIN  publish_to_do ON diary.uuid = publish_to_do.uuid ";
            }
            elseif($tabName == "properties"){
                $output = " JOIN  publish_to_do ON properties.property_uuid = publish_to_do.uuid ";
            }
            elseif($tabName == "var_tab"){
                $output = " JOIN  publish_to_do ON var_tab.variable_uuid = publish_to_do.uuid ";
            }
        }
        
        return $output;
    }//end function


     private function makeLinkTabCond($itemTable, $tabIDs){
        $where = "";
        $whereLink = "";
        
        if(!is_array($tabIDs)){
            if(strlen($tabIDs)>3){
                $where .= $itemTable.".source_id != '".$tabIDs."' ";
                $whereLink .= "links.source_id = '".$tabIDs."' ";
            }
            else{
                $where = " ";
                $whereLink = " ";
            }
        }
        else{
            $i = 0;
            foreach($tabIDs as $actTab){
                if($i>0){
                   $where .= " AND ";
                   $whereLink .= " OR ";
                }
                $where .= $itemTable.".source_id != '".$actTab."' ";
                $whereLink .= "links.source_id = '".$actTab."' ";
            $i++;
            }
        }
        if(strlen($where)>3){
            $where = " (".$where.") AND ";
        }
        if(strlen($whereLink)>3){
            $whereLink = " (".$whereLink.") AND ";
        }
        
        $output = $where.$whereLink; 
        return $output;
    }

    
    private function tableIDclean($Raw_tabIDs_string){
        
        if($Raw_tabIDs_string != "undefined"){
            $Raw_tabIDs = explode(";", $Raw_tabIDs_string);
            $tabIDs = array();
            if(count($Raw_tabIDs)>1){
                foreach($Raw_tabIDs as $actTab){
                    if((strlen($actTab)>0)&&($actTab != "undefined")){
                        if(!in_array($actTab, $tabIDs)){
                            $tabIDs[] = $actTab;
                        }
                    }
                }
            }
            else{
                $tabIDs = $Raw_tabIDs;
            }
        }
        else{
            $tabIDs = false;
        }
        
        return $tabIDs;
    }
    
    
    
    
    //get a summary of items ready to publish
    function itemSumAction(){
        
        $queries = array(); //for debugging
        $batchSize = 50;
        $this->_helper->viewRenderer->setNoRender();
        $projectUUID = $_REQUEST["projectUUID"];
        $Raw_tabIDs_string = $_REQUEST["tabIDs"];
        if(isset($_REQUEST["pubURI"])){
            $clientURI = $_REQUEST["pubURI"];
        }
        else{
            $clientURI = self::defDest;
        } 
        
        $limitList = false;
        if(isset($_REQUEST["limitList"])){
            if($_REQUEST["limitList"] == 1){
                $limitList = true;
            }
        }
          
        $tabIDs = $this->tableIDclean($Raw_tabIDs_string);
        $itemCounts = array();
        
        $db = Zend_Registry::get('db');
        $whereTabs = $this->makeTabCond("persons", $tabIDs);
        $limitListJoin = $this->makeLimitListJoin("persons", $limitList);
        
        $sql = "SELECT count(persons.uuid) as itemCount
                FROM persons
                LEFT JOIN published_docs ON (persons.uuid = published_docs.item_uuid AND published_docs.pubdest = '$clientURI')
                $limitListJoin
                WHERE $whereTabs published_docs.item_uuid IS NULL AND
                persons.project_id = '".$projectUUID."'
                ";
        
        $queries[] =  $sql;
        $result = $db->fetchAll($sql, 2);
        $itemCounts["person"] = $result[0]["itemCount"];
        if(self::mediaOnly){
             $itemCounts["person"] = 0;
        }
        
        
        $whereTabs = $this->makeTabCond("links", $tabIDs);
        $limitListJoin = $this->makeLimitListJoin("users", $limitList);
        $sql = "SELECT DISTINCT users.uuid
                FROM links 
                JOIN users ON users.uuid = links.targ_uuid
                $limitListJoin
                LEFT JOIN published_docs ON (links.targ_uuid = published_docs.item_uuid AND published_docs.pubdest = '$clientURI') 
                LEFT JOIN persons ON (users.uuid  = persons.uuid)
                WHERE $whereTabs published_docs.item_uuid IS NULL 
                AND persons.uuid IS NULL 
                AND links.project_id = '".$projectUUID."'
                ";
        
        $queries[] =  $sql;
        $result = $db->fetchAll($sql, 2);
        $itemCounts["person"] += count($result);
        if(self::mediaOnly){
             $itemCounts["person"] = 0;
        }
        
        
        $whereTabs = $this->makeTabCond("space", $tabIDs);
        $limitListJoin = $this->makeLimitListJoin("space", $limitList);
        $sql = "SELECT count(space.uuid) as itemCount
                FROM space
                $limitListJoin
                LEFT JOIN published_docs ON (space.uuid = published_docs.item_uuid AND published_docs.pubdest = '$clientURI')
                WHERE $whereTabs published_docs.item_uuid IS NULL AND
                space.project_id = '".$projectUUID."'
                AND space.uuid != '0'
                ";
        
        $queries[] =  $sql;
        $result = $db->fetchAll($sql, 2);
        $itemCounts["space"] = $result[0]["itemCount"];
        if(self::mediaOnly){
             $itemCounts["space"] = 0;
        }
        
        $whereTabs = $this->makeTabCond("properties", $tabIDs);
        $limitListJoin = $this->makeLimitListJoin("properties", $limitList);
        $sql = "SELECT count(properties.property_uuid) as itemCount
                FROM properties
                $limitListJoin
                LEFT JOIN published_docs ON (properties.property_uuid = published_docs.item_uuid AND published_docs.pubdest = '$clientURI')
                JOIN var_tab ON properties.variable_uuid = var_tab.variable_uuid
                WHERE $whereTabs published_docs.item_uuid IS NULL AND
                properties.project_id = '".$projectUUID."'
                AND (var_tab.var_type = 'Nominal'
                OR var_tab.var_type = 'Boolean'
                OR var_tab.var_type = 'Ordinal'
                )
                
                ";
                
        $queries[] =  $sql;
        $result = $db->fetchAll($sql, 2);
        $itemCounts["prop"] = $result[0]["itemCount"];
        if(self::mediaOnly){
             $itemCounts["prop"] = 0;
        }
         $itemCounts["prop"] = 0;
        
        $whereTabs = $this->makeTabCond("properties", $tabIDs);
        $limitListJoin = $this->makeLimitListJoin("properties", $limitList);
        $sql = "SELECT DISTINCT properties.variable_uuid
                FROM properties
                $limitListJoin
                LEFT JOIN published_docs ON (properties.property_uuid = published_docs.item_uuid AND published_docs.pubdest = '$clientURI')
                JOIN var_tab ON properties.variable_uuid = var_tab.variable_uuid
                WHERE $whereTabs published_docs.item_uuid IS NULL AND
                properties.project_id = '".$projectUUID."'
                AND (var_tab.var_type != 'Nominal'
                AND var_tab.var_type != 'Boolean'
                AND var_tab.var_type != 'Ordinal'
                )
                ";
                
        $queries[] =  $sql;
        $result = $db->fetchAll($sql, 2);
        $itemCounts["NCprop"] = count($result);
        if(self::mediaOnly){
             $itemCounts["NCprop"] = 0;
        }
        $itemCounts["NCprop"] = 0;
        
        $whereTabs = $this->makeTabCond("resource", $tabIDs);
        $limitListJoin = $this->makeLimitListJoin("resource", $limitList);
        $sql = "SELECT count(resource.uuid) as itemCount
                FROM resource
                $limitListJoin
                LEFT JOIN published_docs ON (resource.uuid = published_docs.item_uuid AND published_docs.pubdest = '$clientURI')
                WHERE $whereTabs published_docs.item_uuid IS NULL AND
                resource.project_id = '".$projectUUID."'
                AND resource.uuid != '0'
                ";
        
        $queries[] =  $sql;
        $result = $db->fetchAll($sql, 2);
        $itemCounts["media"] = $result[0]["itemCount"];
        
        
        $whereTabs = $this->makeTabCond("diary", $tabIDs);
        $limitListJoin = $this->makeLimitListJoin("diary", $limitList);
        $sql = "SELECT count(diary.uuid) as itemCount
                FROM diary
                $limitListJoin
                LEFT JOIN published_docs ON (diary.uuid = published_docs.item_uuid AND published_docs.pubdest = '$clientURI')
                WHERE $whereTabs published_docs.item_uuid IS NULL AND
                diary.project_id = '".$projectUUID."'
                ";
        $queries[] =  $sql;
        $result = $db->fetchAll($sql, 2);
        $itemCounts["doc"] = $result[0]["itemCount"];
        if(self::mediaOnly){
             $itemCounts["doc"] = 0;
        }
        
        
        
        $whereTabs = $this->makeLinkTabCond("space", $tabIDs);
        $limitListJoin = $this->makeLimitListJoin("space", $limitList);
        
        $sql = "SELECT DISTINCT links.origin_uuid
                FROM links
                JOIN space ON (space.uuid = links.origin_uuid)
                $limitListJoin
                WHERE $whereTabs 
                space.project_id = '".$projectUUID."'
                ";
        
        if(is_array($tabIDs)){
            if(count($tabIDs)>0){
                $first = true;
                $obsWhere = "(";
                $JoinCon = "(";
                foreach($tabIDs as $actTab){
                    
                    if($first){
                        $obsWhere .= "observe.source_id = '$actTab' ";
                        $JoinCon .= "space.source_id != '$actTab' ";
                    }
                    else{
                        $obsWhere .= " OR observe.source_id = '$actTab' ";
                        $JoinCon .= " AND space.source_id != '$actTab' ";
                    }
                    
                    $first = false;
                }
                $obsWhere .= ") AND ";
                $JoinCon .= ")";
            }
            else{
                $obsWhere = "";
                $JoinCon = " 1 = 1 ";
            }
        }
        else{
            $obsWhere = "";
            $JoinCon = " 1 = 1 ";
        }
        
        $sql = "SELECT DISTINCT observe.subject_uuid
                FROM observe
                LEFT JOIN published_docs ON (observe.subject_uuid = published_docs.item_uuid AND published_docs.pubdest = '$clientURI')
                JOIN space ON (observe.subject_uuid = space.uuid AND $JoinCon)
                $limitListJoin
                WHERE $obsWhere (observe.subject_type = 'spatial' OR observe.subject_type = 'Locations or Objects') AND
                observe.project_id = '".$projectUUID."' 
                ";
        
        $queries[] =  $sql;
        $result = $db->fetchAll($sql, 2);
        $itemCounts["upSpace"] = count($result);
        if(self::mediaOnly){
            $itemCounts["upSpace"] = 0;
        }
        
        $itemCounts["queries"] = $queries;
        
        header('Content-Type: application/json; charset=utf8');
        echo Zend_Json::encode($itemCounts);
    }
    
    
    
    
    
    
    
    //get information about the next spatail item to publish
    function itemListAction(){
        
        
        $batchSize = 25;
        
        $this->_helper->viewRenderer->setNoRender();
        $projectUUID = $_REQUEST["projectUUID"];
        $Raw_tabIDs_string = $_REQUEST["tabIDs"];
        $itemType = $_REQUEST["itemType"];
        $tabIDs = $this->tableIDclean($Raw_tabIDs_string);
        if(isset($_REQUEST["pubURI"])){
            $clientURI = $_REQUEST["pubURI"];
        }
        else{
            $clientURI = self::defDest;
        }
        
        $limitList = false;
        if(isset($_REQUEST["limitList"])){
            if($_REQUEST["limitList"] == 1){
                $limitList = true;
            }
        }
        
        if(isset($_REQUEST["batch"])){
            $batch = $_REQUEST["batch"];    
        }
        else{
            $batch = 0;
        }
    
        $startNum = $batch * $batchSize;
        
        if($itemType == "person"){
            
            $whereTabs = $this->makeTabCond("persons", $tabIDs);
            $whereLinks = $this->makeTabCond("links", $tabIDs);
            $limitListJoinP = $this->makeLimitListJoin("persons", $limitList);
            $limitListJoinU = $this->makeLimitListJoin("users", $limitList);
           
            $sql = "SELECT persons.uuid as itemUUID
            FROM persons
            $limitListJoinP
            LEFT JOIN published_docs ON (persons.uuid = published_docs.item_uuid AND published_docs.pubdest = '$clientURI')
            WHERE $whereTabs published_docs.item_uuid IS NULL AND
            persons.project_id = '".$projectUUID."'
            LIMIT $startNum, $batchSize
            ";
            
            $sql = "(SELECT persons.uuid as itemUUID
            FROM persons
            $limitListJoinP
            LEFT JOIN published_docs ON (persons.uuid = published_docs.item_uuid AND published_docs.pubdest = '$clientURI')
            WHERE $whereTabs published_docs.item_uuid IS NULL AND
            persons.project_id = '".$projectUUID."'
            )
            UNION
            (SELECT DISTINCT users.uuid
                FROM links 
                JOIN users ON users.uuid = links.targ_uuid
                $limitListJoinU
                LEFT JOIN published_docs ON (links.targ_uuid = published_docs.item_uuid AND published_docs.pubdest = '$clientURI') 
                LEFT JOIN persons ON (users.uuid  = persons.uuid)
                WHERE $whereLinks published_docs.item_uuid IS NULL 
                AND persons.uuid IS NULL 
                AND links.project_id = '".$projectUUID."'
                )
            LIMIT $startNum, $batchSize
            ";
            
            //echo $sql;
        }
        
        if($itemType == "space"){
            
            $whereTabs = $this->makeTabCond("space", $tabIDs);
            $limitListJoin = $this->makeLimitListJoin("space", $limitList);
            
            $sql = "SELECT space.uuid as itemUUID
                FROM space
                $limitListJoin
                LEFT JOIN published_docs ON (space.uuid = published_docs.item_uuid AND published_docs.pubdest = '$clientURI')
                WHERE $whereTabs published_docs.item_uuid IS NULL AND
                space.project_id = '".$projectUUID."'
                ORDER BY space.label_sort, space.class_uuid, space.space_label
                LIMIT $startNum, $batchSize
                ";
            
             $sql = "SELECT space.uuid as itemUUID
                FROM space
                $limitListJoin
                WHERE space.uuid NOT IN (SELECT published_docs.item_uuid FROM published_docs WHERE published_docs.pubdest = '$clientURI')
                AND
                $whereTabs
                space.project_id = '".$projectUUID."'
                AND space.uuid != '0'
                LIMIT $startNum, $batchSize
                ";    
        }
        
        if($itemType == "prop"){
            
            $whereTabs = $this->makeTabCond("properties", $tabIDs);
            $limitListJoin = $this->makeLimitListJoin("properties", $limitList);
            
            $sql = "SELECT properties.property_uuid as itemUUID
                FROM properties
                $limitListJoin
                LEFT JOIN published_docs ON (properties.property_uuid = published_docs.item_uuid AND published_docs.pubdest = '$clientURI')
                 JOIN var_tab ON properties.variable_uuid = var_tab.variable_uuid
                WHERE $whereTabs published_docs.item_uuid IS NULL AND
                properties.project_id = '".$projectUUID."'
                AND (var_tab.var_type = 'Nominal'
                OR var_tab.var_type = 'Boolean'
                OR var_tab.var_type = 'Ordinal'
                )
                LIMIT $startNum, $batchSize
                ";
            /*
            $sql = "SELECT properties.property_uuid as itemUUID
                FROM properties
                WHERE $whereTabs properties.project_id = '".$projectUUID."'
                AND properties.property_uuid NOT IN (select published_docs.item_uuid FROM published_docs WHERE published_docs.pubdest = '$clientURI')
                LIMIT $startNum, $batchSize
                ";
            */
            
                
            //echo $sql;
        }
        
        if($itemType == "NCprop"){
            
            $whereTabs = $this->makeTabCond("properties", $tabIDs);
            $limitListJoin = $this->makeLimitListJoin("properties", $limitList);
            
            $sql = "SELECT properties.property_uuid as itemUUID
                FROM properties
                $limitListJoin
                LEFT JOIN published_docs ON (properties.property_uuid = published_docs.item_uuid AND published_docs.pubdest = '$clientURI')
                JOIN var_tab ON properties.variable_uuid = var_tab.variable_uuid
                WHERE $whereTabs published_docs.item_uuid IS NULL AND
                properties.project_id = '".$projectUUID."'
                AND (var_tab.var_type != 'Nominal'
                AND var_tab.var_type != 'Boolean'
                AND var_tab.var_type != 'Ordinal'
                )
                GROUP BY properties.variable_uuid
                LIMIT $startNum, $batchSize
                ";
        }
        
        
        if($itemType == "media"){
            
            $whereTabs = $this->makeTabCond("resource", $tabIDs);
            $limitListJoin = $this->makeLimitListJoin("resource", $limitList);
            
            $sql = "SELECT resource.uuid as itemUUID
                FROM resource
                $limitListJoin
                LEFT JOIN published_docs ON (resource.uuid = published_docs.item_uuid AND published_docs.pubdest = '$clientURI')
                WHERE $whereTabs published_docs.item_uuid IS NULL AND
                resource.project_id = '".$projectUUID."'
                LIMIT $startNum, $batchSize
                ";
            
            /*
            $sql = "SELECT resource.uuid as itemUUID
                FROM resource
                $limitListJoin
                WHERE resource.uuid NOT IN (SELECT published_docs.item_uuid FROM published_docs WHERE published_docs.pubdest = '$clientURI')
                AND
                $whereTabs
                resource.project_id = '".$projectUUID."'
                ORDER BY resource.uuid
                LIMIT $startNum, $batchSize
                ";
                */
        }
        
        if($itemType == "doc"){
            
            $whereTabs = $this->makeTabCond("diary", $tabIDs);
            $limitListJoin = $this->makeLimitListJoin("diary", $limitList);
            
            $sql = "SELECT diary.uuid as itemUUID
                FROM diary
                $limitListJoin
                LEFT JOIN published_docs ON (diary.uuid = published_docs.item_uuid AND published_docs.pubdest = '$clientURI')
                WHERE $whereTabs published_docs.item_uuid IS NULL AND
                diary.project_id = '".$projectUUID."'
                LIMIT $startNum, $batchSize
                "; 
        }
        
        if($itemType == "linkedSpace"){
            
            $sql = "SELECT DISTINCT observe.subject_uuid
                FROM linked_data
                JOIN observe ON observe.property_uuid = linked_data.itemUUID
                WHERE linked_data.project_id = '".$projectUUID."'
                LIMIT $startNum, $batchSize
                ";
        
        }
        
        
        if($itemType == "upSpace"){
            
            $whereTabs = $this->makeLinkTabCond("space", $tabIDs);
            $limitListJoin = $this->makeLimitListJoin("space", $limitList);
        
            $sql = "
                SELECT DISTINCT space.uuid as itemUUID
                FROM space
                $limitListJoin
                JOIN links ON (space.uuid = links.origin_uuid)
                WHERE $whereTabs 
                space.project_id = '".$projectUUID."'
                LIMIT $startNum, $batchSize
                ";
            
            $obsWhere = " ";
            $JoinCon = " ";
            if(is_array($tabIDs)){
                if(count($tabIDs)>0){
                    $first = true;
                    $obsWhere = "(";
                    $JoinCon = "(";
                    foreach($tabIDs as $actTab){
                        
                        if($first){
                            $obsWhere .= "observe.source_id = '$actTab' ";
                            $JoinCon .= "space.source_id != '$actTab' ";
                        }
                        else{
                            $obsWhere .= " OR observe.source_id = '$actTab' ";
                            $JoinCon .= " AND space.source_id != '$actTab' ";
                        }
                        
                        $first = false;
                    }
                    $obsWhere .= ") AND ";
                    $JoinCon .= ")";
                }
            }
            
            $sql = "
                SELECT DISTINCT observe.subject_uuid as itemUUID
                FROM observe
                LEFT JOIN published_docs ON (observe.subject_uuid = published_docs.item_uuid AND published_docs.pubdest = '$clientURI')
                JOIN space ON (observe.subject_uuid = space.uuid AND $JoinCon)
                $limitListJoin
                WHERE $obsWhere published_docs.item_uuid IS NULL AND
                observe.project_id = '".$projectUUID."' AND (observe.subject_type = 'spatial' OR observe.subject_type = 'Locations or Objects')
                LIMIT $startNum, $batchSize
                ";
            
            
        }
        
        $db = Zend_Registry::get('db');
        
        $result = $db->fetchAll($sql, 2); 
        
        $itemList = array("proj"=>$projectUUID, "itemType"=>$itemType, "tabs"=>$tabIDs, "items"=>$result, "query" => $sql);
        
        header('Content-Type: application/json; charset=utf8');
        echo Zend_Json::encode($itemList);
    }


}