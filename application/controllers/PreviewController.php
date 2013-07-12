<?php

// increase the memory limit
ini_set("memory_limit", "2048M");
// set maximum execution time to no limit
ini_set("max_execution_time", "0");

class PreviewController extends Zend_Controller_Action
{
    public $OChost =  "http://opencontext";
    public $baseURL = "http://penelope.oc";
    //public $baseURL = "http://penelope.opencontext.org";
    public $xslURL = "http://penelope.oc/xsl/";
    //public $xslURL = "http://penelope.opencontext.org/public/xsl/";
    
    public $format = "xhtml"; //default format
    
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
    
    private function makeDataURL($itemType){
        
        $itemUUID = $_REQUEST["UUID"];
        $dataURL = "http://".$_SERVER['SERVER_NAME']."/xml/".$itemType."?xml=1&id=".$itemUUID;
        if(isset($_REQUEST["format"])){
            
            if($_REQUEST["format"] == "atom"){
                $this->format = "atom";
            }
            elseif($_REQUEST["format"] == "xml"){
                $this->format = "xml";
            }
            elseif($_REQUEST["format"] == "edit"){
                $this->format = "edit";
            }
            elseif($_REQUEST["format"] == "json"){
                $this->format = "json";
                $dataURL = "http://".$_SERVER['SERVER_NAME']."/xml/".$itemType."?id=".$itemUUID;
            }
        }
        return $dataURL;
    }
    
    
    function spaceAction(){
        
        $dataURL = $this->makeDataURL("space");
        $data = file_get_contents($dataURL);
       
        if($this->format == "xml"){
            $this->_helper->viewRenderer->setNoRender();
            header("Content-type: application/xml");
            echo  $data;
        }
        elseif($this->format == "json"){
            $this->_helper->viewRenderer->setNoRender();
            header('Content-Type: application/json; charset=utf8');
            echo $data;
        }
        else{
            $this->view->OChost = $this->OChost;
            $this->view->XML  = $data;
            $this->render("view-space");
        }
       
    }
    
    
    function mediaAction(){
        
        $dataURL = $this->makeDataURL("media");
        $data = file_get_contents($dataURL);
       
        if($this->format == "xml"){
            $this->_helper->viewRenderer->setNoRender();
            header("Content-type: application/xml");
            echo  $data;
        }
        elseif($this->format == "json"){
            $this->_helper->viewRenderer->setNoRender();
            header('Content-Type: application/json; charset=utf8');
            echo $data;
        }
        else{
            $this->view->OChost = $this->OChost;
            $this->view->XML  = $data;
            $this->render("view-media");
        }
       
    }
    
    function documentAction(){
        
        $dataURL = $this->makeDataURL("document");
        $data = file_get_contents($dataURL);
       
        if($this->format == "xml"){
            $this->_helper->viewRenderer->setNoRender();
            header("Content-type: application/xml");
            echo  $data;
        }
        elseif($this->format == "json"){
            $this->_helper->viewRenderer->setNoRender();
            header('Content-Type: application/json; charset=utf8');
            echo $data;
        }
        else{
            
            $urlFixes = array("http://opencontext.org/subjects/" => "/preview/space?UUID=",
                              "http://opencontext.org/media/" => "/preview/media?UUID=",
                              "http://opencontext.org/documents/" => "/preview/document?UUID="
                              );
            
            foreach($urlFixes as $searchKey => $replace){
                $data = str_replace($searchKey, $this->baseURL.$replace, $data);
            }
            
            $this->view->OChost = $this->OChost;
            $this->view->XML  = $data;
            $this->render("view-document");
        }
       
    }
    
    function personAction(){
        
        $dataURL = $this->makeDataURL("person");
        $data = file_get_contents($dataURL);
       
        if($this->format == "xml"){
            $this->_helper->viewRenderer->setNoRender();
            header("Content-type: application/xml");
            echo  $data;
        }
        elseif($this->format == "json"){
            $this->_helper->viewRenderer->setNoRender();
            header('Content-Type: application/json; charset=utf8');
            echo $data;
        }
        else{
            $this->view->OChost = $this->OChost;
            $this->view->XML  = $data;
            $this->render("view-person");
        }
       
    }
    
    function projectAction(){
        
        $dataURL = $this->makeDataURL("project");
        $data = file_get_contents($dataURL);
       
        if($this->format == "xml"){
            $this->_helper->viewRenderer->setNoRender();
            header("Content-type: application/xml");
            echo  $data;
        }
        elseif($this->format == "json"){
            $this->_helper->viewRenderer->setNoRender();
            header('Content-Type: application/json; charset=utf8');
            echo $data;
        }
        else{
            $this->view->OChost = $this->OChost;
            $this->view->XML  = $data;
            $this->render("view-project");
        }
       
    }
    

    function propertyAction(){
        $this->_helper->viewRenderer->setNoRender();
        $propUUID = $_REQUEST["UUID"];
        
        
        $xslFile = "preview_defaultProperty.xsl";
        $propURI = "http://".$_SERVER['SERVER_NAME']."/xml/property?xml=1&id=".$propUUID ;
        $propString = file_get_contents($propURI);
        
        
        if(isset($_REQUEST["format"])){
            header("Content-type: application/xml");
            echo $propString;
        }
        else{
            
            $doc = new DOMDocument();
            
            if($this->xslURL != false){
                //$xslString = file_get_contents($this->xslURL.$xslFile);
                $doc->load($this->xslURL.$xslFile);
            }
            else{
                $doc->load("xsl/".$xslFile);
            }
           
            $proc = new XSLTProcessor();
            $xslt = $proc->importStylesheet($doc);
            $atomDoc = new DomDocument();
            $atomDoc->loadXML($propString);
            
            header('Content-type: application/xhtml+xml', true);
            echo $proc->transformToXML($atomDoc);
        }
    }



    
    
    function propCheckAction(){
	
        $this->_helper->viewRenderer->setNoRender();
        $db = Zend_Registry::get('db');
        
        $host = "http://".$_SERVER['SERVER_NAME'];

        if(isset($_REQUEST["propUUID"])){
            $propUUID = $_REQUEST["propUUID"];
            $propJSON = file_get_contents($host."/xml/property?id=".$propUUID); 
            $prop = json_decode($propJSON, true);
            $propLabel = $prop["label"];
            unset($prop);
            unset($propJSON);
            $propArray = array();
            $propArray[] = $propUUID;
        }
        elseif(isset($_REQUEST["taxa"])){
            
            $propArray = array();
             
            $taxa = $_REQUEST["taxa"][0];
            $taxaArray = explode("::", $taxa);
            $varLabel = $taxaArray[0];
            $value = $taxaArray[1];
            $propLabel = $taxa;
            
            if(!stristr($value, "<") && !stristr($value, ">") && !stristr($value, ",")){
                $range = false;
                
                $sql = "SELECT properties.property_uuid, properties.variable_uuid
                    FROM properties
                    JOIN val_tab ON val_tab.value_uuid = properties.value_uuid
                    JOIN var_tab ON var_tab.variable_uuid =  properties.variable_uuid
                    WHERE var_tab.var_label LIKE '".addslashes($varLabel)."'
                    AND val_tab.val_text LIKE '".addslashes($value)."'
                ";
            }
            else{
                
                
                $range = array();
                $valArray = explode(",", $value);
                $valMin = $valArray[0];
                $valMin = str_replace(">", "", $valMin);
                $valMin = str_replace("=", "", $valMin);
                $valMax = $valArray[1];
                $valMax = str_replace("<", "", $valMax);
                $valMax = str_replace("=", "", $valMax);
                
                if(is_numeric($valMin) && is_numeric($valMax)){
                    
                   
                    
                    $sql = "SELECT properties.property_uuid, properties.val_num, properties.variable_uuid
                        FROM properties
                        JOIN var_tab ON var_tab.variable_uuid =  properties.variable_uuid
                        WHERE var_tab.var_label LIKE '".addslashes($varLabel)."'
                        AND properties.val_num >= $valMin
                        AND properties.val_num <= $valMax
                        ORDER BY properties.val_num
                    ";
                    
                    echo $sql;
                }
                
            }
            
            $res = $db->fetchAll($sql, 2);
            foreach($res as $rowB){
                $propArray[] = $rowB["property_uuid"];
                $variableUUID = $rowB["variable_uuid"];
                if(is_array($range)){
                    $range[$rowB["property_uuid"]] = $rowB["val_num"];
                }
            }
            
        }
        
        
        echo "<h1>Links to items described by '".$propLabel."'</h1>";
        
        foreach($propArray as $propUUID){
            
            $sql = "SELECT DISTINCT observe.subject_uuid AS itemUUID, observe.project_id,
            observe.subject_type
            FROM observe
            LEFT JOIN space ON observe.subject_uuid = space.uuid
            WHERE observe.property_uuid = '$propUUID'
            ORDER BY observe.subject_type, space.label_sort
            ";
            
            $result = $db->fetchAll($sql, 2);
            
            
            echo "<br/><br/>";
            echo "<p>These links are provided to help edit/debug items with this property (Prop-UUID: $propUUID ) </p>";
            echo "<p>Number of Items: ".count($result);
            if(is_array($range)){
                echo ", <strong>$varLabel</strong> value: ".$range[$propUUID];
            }
            echo "</p>";
            
            
    
            $rowData = "";
            $i = 0;
            foreach($result as $row){
                $itemUUID = $row["itemUUID"];
                $itemType = $row["subject_type"];
                $projectUUID = $row["project_id"];
                
                $url = $host."/preview/";
                
                if(stristr($itemType, "location")){
                    $url .= "space?UUID=".$itemUUID;
                    $sql = "SELECT space_label AS label, full_context AS note FROM space WHERE uuid = '$itemUUID' LIMIT 1; ";
                }
                elseif(stristr($itemType, "media")){
                    $url .= "media?UUID=".$itemUUID;
                    $sql = "SELECT res_label AS label, mime_type AS note FROM resource WHERE uuid = '$itemUUID' LIMIT 1; ";
                }
                elseif(stristr($itemType, "diary")){
                    $url .= "document?UUID=".$itemUUID;
                    $sql = "SELECT diary_label AS label, '' AS note FROM diary WHERE uuid = '$itemUUID' LIMIT 1; ";
                }
                elseif(stristr($itemType, "person")){
                    
                    $url .= "person?UUID=".$itemUUID;
                    
                    $sql = "SELECT persons.combined_name AS label, '' AS note
                            FROM persons
                            WHERE persons.uuid = '$itemUUID'
                            UNION
                            SELECT users.combined_name AS label, '' AS note
                            FROM users
                            WHERE users.uuid = '$itemUUID'
                            
                            ";
                }
                
                $resultB = $db->fetchAll($sql, 2);
                if($resultB){
                    $label = $resultB[0]["label"];
                    $note = $resultB[0]["note"];
                    $note  = str_replace("|xx|", "/", $note);
                    
                    
                    if ($i % 2){
                        $rowData .= "<tr>";
                    }
                    else{
                        $rowData .= "<tr style='background-color:#F0F0F0; ' >";
                    }
                    $rowData .="<td style='font-size:xx-small; padding:4px;'>".$itemType."</td>";
                    $rowData .= "<td style='padding:4px;' >".$label."</td><td style='padding:4px;'>".$note."</td>";
                    $rowData .= "<td style='padding:4px;'><a href=\"".$url."\">".$url."</a></td>".chr(13);
                    $rowData .= "<form action='".$host."/edit-dataset/add-var-val' method='post'>";
                    $rowData .= "<input type='hidden' name='reqURI' value='".$host.$_SERVER['REQUEST_URI']."' />";
                    $rowData .= "<input type='hidden' name='projectUUID' value='".$projectUUID."' />";
                    $rowData .= "<input type='hidden' name='varUUID' value='".$variableUUID."' />";
                    $rowData .= "<input type='hidden' name='itemType' value='".$itemType."' />";
                    $rowData .= "<input type='hidden' name='itemUUID' value='".$itemUUID."' />";
                    $rowData .= "<td padding:4px;>";
                    $rowData .= "<span style='font-size:x-small;'>New Value for <strong>".$label."</strong>:</span><br/>";
                    $rowData .= "<input name='valText' type='text' value=''  size='50' />";
                    $rowData .= "</td>";
                    $rowData .= "<td>";
                    $rowData .= "<input name='submit' type='submit'>";
                    $rowData .= "</td>";
                    $rowData .= "</form>";
                    $rowData .= "</tr>".chr(13);
                    $i++;
                }
            }
           
            echo "<table style='background-color:#C0C0C0;'>";
            echo "<tr>
                    <th>Type</th>
                    <th>Item</th>
                    <th>Note</th>
                    <th>Link</th>";
            
            if(isset($varLabel)){
                echo  "<th>New Value for <em>$varLabel</em></th>";
            }
            else{
                 echo  "<th>New Value</th>";
            }
            echo    "<th>Do Update</th>
                </tr>";
            echo $rowData;
            echo "</table>";
        }
        
    }//end function


}