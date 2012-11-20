<?php

/** Zend_Controller_Action */
//require_once 'Zend/Controller/Action.php';
//require_once 'OpenContext/Controller/Action/Helper/SolrAccess.php';

//error_reporting(E_ALL ^ E_NOTICE);
// increase the memory limit
ini_set("memory_limit", "1024M");
// set maximum execution time to no limit
ini_set("max_execution_time", "0");

class kenanOldController extends Zend_Controller_Action {
    
    function init()
    {  
        require_once 'App/Util/GenericFunctions.php';
    }
    
    //make sure all connections are UTF-8 OK
    private function setUTFconnection($db){
	$sql = "SET collation_connection = utf8_unicode_ci;";
	$db->query($sql, 2);
	$sql = "SET NAMES utf8;";
	$db->query($sql, 2);
    }
    
    
   //develop day plan metadata
   function dayPlansAction(){
	$this->_helper->viewRenderer->setNoRender();
	$db = Zend_Registry::get('db');
	
	$sql = "SELECT *
	FROM z_mediafiles
	WHERE file LIKE 'fullDayplans%'";
	
	$result = $db->fetchAll($sql, 2);
	foreach($result as $row){
	    $id = $row['id'];
	    $file = $row['file'];
	    $filename = $row['filename'];
	    
	    
	    
	    $fileArray = explode("/", $file);
	    $year = $fileArray[1] + 0;
	    
	    if(stristr($filename, ".JPG")){
	    
		$data = array();
		
		$data["type"] = "Day plan";
		
		if($filename == "E205162005A.jpg"){
		    $filename = "E2DP05162005A.jpg";
		}
	    
		if(stristr($filename, "Overview")){
		   
		    $area = "A";
		    $trench = null;
		    
		    echo "<br/>Overview Area $area / trench: $trench final: $id file: $filename ";
		    $data["area"] = $area;
		    $data["comment"] = "Overview";
		}
		elseif(stristr($filename, "final")){
		    
		    if($filename == "A10FINALDAYPLAN2002.JPG"){
			$area = "A";
			$trench = "10";
		    }
		    elseif($filename == "F14FINALDAYPLAN2002.JPG"){
			$area = "F";
			$trench = "14";
		    }
		    elseif($filename == "F7DPFINALTRENCHPLAN.jpg"){
			$area = "F";
			$trench = "7";
		    }
		    elseif($filename == "D6FinalDailyPlan2005A.JPG"){
			$area = "D";
			$trench = "6";
			$data["extra"] = "A";
		    }
		    
		    echo "<br/>Area $area / trench: $trench final: $id file: $filename ";
		    
		    $data["area"] = $area;
		    $data["trench"] = $trench;
		    
		}
		else{
		    
		    // A1DP07212000A.JPG
		    $filenameArray = explode("DP", strtoupper($filename));
		    $fContext = $filenameArray[0];
		    $fDate = $filenameArray[1];
		    if($fDate == "070102004A.JPG"){
			$fDate = "07112004A.JPG";
		    }
		    elseif($fDate == "0722A2004.JPG"){
			$fDate = "07222004A.JPG";
		    }
		    
		    $fDate = str_replace(".JPG", "", $fDate);
		    
		    $area = substr($fContext, 0, 1);
		    $trench = str_replace($area, "", $fContext);
		    
		    $month = substr($fDate, 0, 2);
		    $day = substr($fDate, 2, 2);
		    $yearB = substr($fDate, 4, 4);
		    $yearB = $yearB + 0;
		    if($yearB != $year){
			
			if(!stristr($fDate, $year)){
			    echo "<br/>HELP! $id fdate: $fDate  year: $year yearB: $yearB";
			}
			else{
			    $yearB = $year;
			}
		    }
		    
		    $data["year"] = $year;
		    $data["area"] = $area;
		    $data["trench"] = $trench;
		    $data["date"] = $year."-".$month."-".$day;
		    $data["extra"] = $this->getTrailingExtra($fDate);
		    
		}
		
		$where = array();
		$where[] = "id = ".$id;
		$db->update("z_mediafiles", $data, $where);
		
		
		
	    }
	    
	    
	}
	
    }
    
    //get a trailing string, returns FALSE if only numeric
    private function getTrailingExtra($string){
	$numberTest = "0.".$string;
	if(is_numeric($numberTest)){
	    return null;
	}
	else{
	    $i=0;
	    $strLen = strlen($string);
	    $alphaFound = false;
	    $output = "";
	    while($i < $strLen){
		$actChar = substr($string, $i, 1);
		if(!is_numeric($actChar)){
		    $alphaFound = true;
		}
		
		if($alphaFound){
		    $output .= $actChar;
		}
		
		$i++;
	    }
	    
	    return $output;
	}
    }
    
    
    //remove a trailing string after alphanumeric
    private function removeTrailingExtra($string){
	$i=0;
	$strLen = strlen($string);
	$alphaFound = false;
	$output = "";
	while($i < $strLen){
	    $actChar = substr($string, $i, 1);
	    if(!is_numeric($actChar)){
		$alphaFound = true;
	    }
	    
	    if(!$alphaFound){
		$output .= $actChar;
	    }
	    
	    $i++;
	}
	
	return $output;
    }
    
    
    //develop field photo metadata
   function fieldPhotosAction(){
	$this->_helper->viewRenderer->setNoRender();
	$db = Zend_Registry::get('db');
	
	$sql = "SELECT *
	FROM z_mediafiles
	WHERE file LIKE 'fullFieldphotos%'
	AND (id < 8302 OR id > 8304)
	AND id != 7465
	";
	
	$result = $db->fetchAll($sql, 2);
	foreach($result as $row){
	    $id = $row['id'];
	    $file = $row['file'];
	    $filename = $row['filename'];
	    
	    
	    
	    $fileArray = explode("/", $file);
	    $year = $fileArray[1] + 0;
	    
	    if(stristr($filename, ".JPG") && (!stristr($file, "Tepe")) && (!stristr($file, "Utarp")) ){
	    
		$data = array();
		
		$data["type"] = "Field Photo";
		$data["fieldtype"] = null;
		$locus = null;
		$extra = null;
		$pContext = null;
		$sContext = null;
		
		if(true){
		    
		    if($year == 2005){
			if(stristr($filename, "0528")){
			    $filename = str_replace("0528", "05285", $filename);
			}
			if($id == 12668){
			    $filename = "G7L06015D3.JPG";
			}
		    }
		    if($id == 6109 || $id == 6110){
			$filename = str_replace("0252", "07252", $filename);
		    }
		    elseif($id == 6316){
			$filename = "C3L07112V10.JPG";
		    }
		    elseif($id == 6317){
			$filename = "C3L07112V11.JPG";
		    }
		    elseif($id == 6318){
			$filename = "C3L07112V9.JPG";
		    }
		    elseif($id == 6493){
			$filename = "C4L07112V6.JPG";
		    }
		    elseif($id == 9136){
			$filename = "D9L07134G2.JPG";
		    }
		    elseif($id == 7704){
			$filename = "F15U08252Y69.JPG";
		    }
		    elseif($id == 9209){
			$filename = "E2L07134D7.JPG";
		    }
		    
		    $filenameTest = strtoupper($filename);
		    $filenameTest = str_replace(".JPG", "", $filenameTest);
		    $fileNameFront = substr($filenameTest,0,4); //only look for letters in the first four characters, otherwise get mixed up with camera letters
		    unset($filenameArray);
		    if(stristr($fileNameFront, "L")){
			$filenameArray = explode("L", $filenameTest);
			$data["fieldtype"] = "Locus";
		    }
		    elseif(stristr($fileNameFront, "P")){
			$filenameArray = explode("P", $filenameTest);
			$data["fieldtype"] = "Plan";
		    }
		    elseif(stristr($fileNameFront, "S")){
			$filenameArray = explode("S", $filenameTest);
			$data["fieldtype"] = "Section";
		    }
		    elseif(stristr($fileNameFront, "U")){
			$oPos = stripos($filenameTest, "U");
			$filenameArray = array();
			$fileLen = strlen($filenameTest);
			$filenameArray[0] = substr($filenameTest, 0, $oPos);
			$filenameArray[1] = substr($filenameTest, $oPos+1, $fileLen-($oPos+1));
			//echo "<br/>$filename : ".$filenameArray[0]." and ".$filenameArray[1];
			$data["fieldtype"] = "Utarp";
		    }
		    
		    if($data["fieldtype"] == null){
			echo "<br/>$id <strong>NO TYPE!</strong>: $filename ($file)";
		    }
		    else{
			if(count($filenameArray)>2){
			    $ii=2;
			    while($ii<count($filenameArray)){
				$filenameArray[1] .= $filenameArray[$ii]; // add the stuff after the first break letter to the second array part.
			    }
			}
			
			$fContext = $filenameArray[0];
			$fPart2= $filenameArray[1];
			$findLen = 5;
			if(strlen($fPart2)>$findLen){
			    $findLen = strlen($fPart2);
			}
			$dateraw = substr($fPart2, 0, $findLen);
			$dateraw = $this->removeTrailingExtra($dateraw);
			$extra = $this->getTrailingExtra($fPart2);
			
			if($year>=2001){
			
			    $month = substr($dateraw, 0, 2);
			    $day =  substr($dateraw, 2, 2);
			    $yearS =  substr($dateraw, 4, 1);
			    $yearS  = $yearS +2000;
			    if($yearS != $year){
				$yearS =  substr($dateraw, 4, 2);
				$yearS  = $yearS +2000;
				if($yearS != $year){
				    echo "<br/><strong>WHAT THE HELL! ($id) ($fPart2)</strong> ".$filename." ($file)";
				}
				$data["date"] = $year."-".$month."-".$day;
			    }
			    else{
				$data["date"] = $yearS."-".$month."-".$day;
			    }
			}
			else{
			    $data["date"] = null;
			    $extra = $dateraw + 0;
			}
			    
			$fContext = $filenameArray[0];
			$area = substr($fContext, 0, 1);
			$trench = str_replace($area, "", $fContext);
			
			$data["year"] = $year;
			$data["area"] = $area;
			$data["trench"] = $trench;
			$data["extra"] = $extra;
			$data["photoID"] = $area."-".$trench."-".$data["date"]."-".$data["fieldtype"]."-".$extra;
			
			if(!isset($data["fieldtype"])){
			echo "<br/>ID: $id Year: $year Area: $area Trench: $trench FieldType: ".$data["fieldtype"]." Date: ".$data["date"]." Extra: $extra ($filename)";
			}
			else{
			    echo "<br/>ID: $id ".$data["photoID"];
			}
		    }
		
		}
		
		$where = array();
		$where[] = "id = ".$id;
		$db->update("z_mediafiles", $data, $where);
		
		
	    }
	    
	    
	}
	
    }//end function
    
    
     //develop field photo metadata
   function itemImagesAction(){
	$this->_helper->viewRenderer->setNoRender();
	$db = Zend_Registry::get('db');
	
	$sql = "SELECT *
	FROM z_find_images
	WHERE (file LIKE 'fullFindsphotos%'
	OR file LIKE 'fullFindsdrawings%')
	";
	
	$result = $db->fetchAll($sql, 2);
	foreach($result as $row){
	    $id = $row['id'];
	    $file = $row['file'];
	    $filename = $row['filename'];
	    
	    
	    
	    $fileArray = explode("/", $file);
	    $year = $fileArray[1] + 0;
	    
	    if(stristr($filename, ".JPG") && (!stristr($file, "Tepe")) && (!stristr($file, "Utarp")) ){
	    
		$data = array();
		
		if(stristr($file, "Findsphotos")){
		    $data["image_type"] = "Find Photo";
		}
		else{
		    $data["image_type"] = "Find Drawing";
		}
		
		//echo "<br/>".$filename;
		$filenameID = str_replace("D1", "D_1", $filename);
		$filenameID = str_replace(".JPG", "", $filename);
		$filenameID = str_replace(".jpg", "", $filenameID);
		$fileArray = explode("_", $filenameID);
		
		$data["area"] = $fileArray[0];
		$data["trench"] = $fileArray[1];
		$data["locus"] = $fileArray[2];
		$data["findsbag"] = $fileArray[3];
		$data["find"] = $fileArray[4];
		if(isset($fileArray[6])){
		    $data["index"] = $fileArray[6];
		}
		elseif(isset($fileArray[5])){
		    $data["index"] = $fileArray[5];
		}
		
		$data["imageFindID"] = $data["area"]."-".$data["trench"]."-".$data["locus"]."-".$data["findsbag"];
		$data["imageID"] = $data["imageFindID"]."-".$data["find"];
		
		$where = array();
		$where[] = "id = ".$id;
		$db->update("z_find_images", $data, $where);

	    }
	    
	    
	}
	
    }//end function
    
    
    
    //develop field photo metadata
   function fieldPhotosSizeAction(){
	$this->_helper->viewRenderer->setNoRender();
	$db = Zend_Registry::get('db');
	
	$sql = "SELECT *
	FROM resource
	WHERE size < 1
	AND source_id = 'z_1_a5a31a91f'
	";
	
	$result = $db->fetchAll($sql, 2);
	foreach($result as $row){
	    $id = $row['uuid'];
	    $json = file_get_contents("http://penelope2.oc/xml/media?id=".$id );
	    echo "<br/>Done: ".$id;
	}
	
    }//end function
    
    
     //develop field photo metadata
   function timeAction(){
	$this->_helper->viewRenderer->setNoRender();
	$db = Zend_Registry::get('db');
	
	echo "Start: ".microtime(true);
	sleep(1);
	echo "<br/>End: ".microtime(true);
	
	echo "<br/><br/>Start: ".time(true);
	sleep(1);
	echo "<br/>End: ".time(true);
	
    }//end function
    
    
    
     //develop field photo metadata
   function journalMetadataAction(){
	$this->_helper->viewRenderer->setNoRender();
	$db = Zend_Registry::get('db');
	
	$sql = "SELECT *
	FROM  z_journalfiles
	";
	
	$result = $db->fetchAll($sql, 2);
	foreach($result as $row){
	    $htmlPath = $row['html_file'];
	    if(strlen($htmlPath)>2){
		
		$htmlPathArray = explode("/", $htmlPath);
		
		if(!isset($htmlPathArray[1])){
		    echo "<br/>Problem with: ".$htmlPath;
		    break;
		}
		else{
		    $year = $htmlPathArray[1] + 0;
		    unset($htmlPathArray);
		}
		
		$htmlFileName = $row['html_filename'];
		$htmlFileName = str_replace(".html", "", $htmlFileName);
		$data = array();
		$data["area"] = substr($htmlFileName, 0, 1);
		$data["trench"] = substr($htmlFileName, 1, 1);
		$twoDigitTrench = false;
		if(is_numeric(substr($htmlFileName, 2, 1))){
		    $twoDigitTrench = true;
		    $data["trench"] = substr($htmlFileName, 1, 2);
		}
		
		$data["year"] = $year;
		
		if((!stristr($htmlFileName, "summary")) && (!stristr($htmlFileName, "area"))){
		    
		    if($twoDigitTrench){
			$initialsStart = 3;
			$RawDateStart = 5;
		    }
		    else{
			$initialsStart = 2;
			$RawDateStart = 4;
		    }
		    
		    $data["initials"] = substr($htmlFileName, $initialsStart, 2);
		    $dateRaw = substr($htmlFileName, $RawDateStart, 8);
		    
		    $month = substr($dateRaw, 0, 2);
		    $day = substr($dateRaw, 2, 2);
		    $year = substr($dateRaw, 4, 4);
		    $data["log_date"] = $year."-".$month."-".$day;
		    $data["log_name"] = $data["area"]."-".$data["trench"]."-".$data["log_date"];
		}
		elseif(stristr($htmlFileName, "area")){
		    $data["area"] = substr($htmlFileName, 5, 1); //Area A Tr 1 ktJournal.html
		    if(stristr($htmlFileName, "tr")){
			$data["trench"] = substr($htmlFileName, 10, 1); //Area A Tr 1 ktJournal.html
			if(stristr($htmlFileName, "summary")){
			    $data["log_name"] = $data["area"]."-".$data["trench"]."-".$year."-Summary";
			}
			elseif(stristr($htmlFileName, "journal")){
			    $data["log_name"] = $data["area"]."-".$data["trench"]."-".$year."-Journal";
			}
			else{
			    $data["log_name"] = $data["area"]."-".$data["trench"]."-".$year."-Log";
			}
		    }
		    else{
			 $data["log_name"] = $data["area"]."-".$year."-Log";
		    }
		    echo "<br/><em>".$data["log_name"]."</em>: ".$htmlFileName;
		    
		}
		else{
		    $data["initials"] = null;
		    $data["log_date"] = null;
		    if(stristr($htmlFileName, "summary")){
			$data["log_name"] = $data["area"]."-".$data["trench"]."-".$year."-Summary";
		    }
		    else{
			$data["log_name"] = $data["area"]."-".$data["trench"]."-".$year."-Log";
		    }
		}
		
		$htmlPathUse = str_replace("fullJournals/", "http://about.oc/kenan/full/Journals/", $htmlPath);
		$htmlPathUse = str_replace(".html.html", ".html", $htmlPathUse);
		$htmlPathUse = str_replace(" ", "%20", $htmlPathUse);
		@$html = file_get_contents($htmlPathUse);
		if(!$html){
		    $DOCFileName = $htmlFileName.".DOC";
		    $makeHTML_URL = "http://about.oc/kenan/single_word_to_html.php?year=".$year."&docfile=".$DOCFileName;
		    $makeHTML_URL = str_replace(" ", "%20", $makeHTML_URL);
		    echo "<br/> Trying: <a href='".$makeHTML_URL."'>".$makeHTML_URL."</a><br/>";
		    echo file_get_contents($makeHTML_URL);
		    @$html = file_get_contents($htmlPathUse);
		}
		
		
		if($html){
		    $html = str_replace('<p dir="ltr"', '<p', $html);
		    $html = str_replace('<span xml:lang="en-US" lang="en-US">', '<span>', $html);
		    
		    $doc = new DOMDocument();
		    $doc->loadHTML($html);
		    $doc->formatOutput = true;
		    $xpath = new DOMXPath($doc);
		    $query = "//body/div";
		    $bodyNodeList = $xpath->query($query, $doc);
		    $bodyNode = $bodyNodeList->item(0);
		    $bodyText = $doc->saveXML($bodyNode);
		    
		    $where = array();
		    $where[] = "id = ".$row["id"];
		    
		    $data["html"] = $bodyText;
		    $db->update("z_journalfiles", $data, $where);
		    
		    //echo "<br/><br/>";
		    //echo "Area: ".$data["area"]." trench: ".$data["trench"]." initials: ".$data["initials"]." date: ".$data["log_date"];
		
		}
		else{
		    echo "<br/><br/><strong>";
		    echo "Area: ".$data["area"]." trench: ".$data["trench"]." initials: ".$data["initials"]." date: ".$data["log_date"];
		    echo "<br/>Problem: ".$htmlPath;
		    echo "</strong>";
		}
	    
	    }
	}
	
    }//end function
    
    
    
     //develop field photo metadata
   function diaryLinkAction(){
	$this->_helper->viewRenderer->setNoRender();
	$db = Zend_Registry::get('db');
	
	$sql = "SELECT uuid, diary_label, project_id
	FROM diary
	
	ORDER BY diary_label
	";
    
	$result = $db->fetchAll($sql, 2);
	$lookupArray = $result;
	$maxIndex = count($lookupArray)-1;
	
	$i = 0;
	foreach($result as $row){
	    $projectUUID = $row['project_id'];
	    $label = $row['diary_label'];
	    $originUUID = $row['uuid'];
	    
	    $current_isDated = $this->diary_date_check($label);
	    $current_prefix = $this->diaryContextPrefix($label);
	    $currentDate = $this->diary_date_make($label);
	    
	    echo "<br/><br/>".$label." IsDated: $current_isDated ($currentDate) Prefix: $current_prefix ";
	    
	    if($current_isDated){
		if($i>=1){
		    $previousLabel = $lookupArray[$i-1]['diary_label'];
		    $targetUUID = $lookupArray[$i-1]['uuid'];
		    $previous_isDated = $this->diary_date_check($previousLabel);
		    $previous_prefix = $this->diaryContextPrefix($previousLabel);
		    
		    if($previous_isDated && ($current_prefix == $previous_prefix)){
			$previousDate = $this->diary_date_make($previousLabel);
			
			if($previousDate != false){
			    if(strtotime($previousDate)<strtotime($currentDate)){
				echo "[Add Previous: ".$previousLabel." Prefix: $previous_prefix ]";
				//add a "previous" link
				$this->addLinkingRel($originUUID, 'Diary / Narrative', $targetUUID, 'Diary / Narrative', 'Previous', $projectUUID);
			    }
			    elseif(strtotime($previousDate) == strtotime($currentDate)){
				 echo "<strong>[Add Concurrent (p): ".$previousLabel." Prefix: $previous_prefix ]</strong>";
				//add a "previous" link
				$this->addLinkingRel($originUUID, 'Diary / Narrative', $targetUUID, 'Diary / Narrative', 'Concurrent', $projectUUID);
			    }
			}
		    }
		}
		if($i < $maxIndex){
		    $nextLabel = $lookupArray[$i+1]['diary_label'];
		    $targetUUID = $lookupArray[$i+1]['uuid'];
		    $next_isDated = $this->diary_date_check($nextLabel);
		    $next_prefix = $this->diaryContextPrefix($nextLabel);
		    
		    
		    if($next_isDated && ($current_prefix == $next_prefix)){
			$nextDate = $this->diary_date_make($nextLabel);
			
			if($nextDate != false){
			    if(strtotime($nextDate) > strtotime($currentDate)){
				echo "[Add Next: ".$nextLabel." Prefix: $next_prefix ]";
				$this->addLinkingRel($originUUID, 'Diary / Narrative', $targetUUID, 'Diary / Narrative', 'Next', $projectUUID);
			    }
			    elseif(strtotime($nextDate) == strtotime($currentDate)){
				echo "<strong>[Add Concurrent (n): ".$nextLabel." Prefix: $next_prefix ]</strong>";
				$this->addLinkingRel($originUUID, 'Diary / Narrative', $targetUUID, 'Diary / Narrative', 'Concurrent', $projectUUID);
			    }
			}
			
		    }
		}
	    }
	    
	    
	$i++;
	}//end loop through diaries
	
    }//end function
    
    
    
     //develop field photo metadata
   function dayplanLinkAction(){
	$this->_helper->viewRenderer->setNoRender();
	$db = Zend_Registry::get('db');
	
	$sql = "SELECT uuid, res_label, project_id
	FROM resource
	WHERE res_label LIKE 'Dayplan%'
	ORDER BY res_label	
	";
    
	$result = $db->fetchAll($sql, 2);
	$lookupArray = $result;
	$maxIndex = count($lookupArray)-1;
	
	$i = 0;
	foreach($result as $row){
	    $projectUUID = $row['project_id'];
	    $originalLabel = $row['res_label'];
	    $originUUID = $row['uuid'];
	    
	    $label = str_replace("Dayplan-", "", $originalLabel);
	    
	    $current_isDated = $this->diary_date_check($label);
	    $current_prefix = $this->diaryContextPrefix($label);
	    
	    echo "<br/><br/>$originalLabel (".$label.") IsDated: $current_isDated Prefix: $current_prefix ";
	    
	    if($current_isDated){
		if($i>=1){
		    $previousLabel = str_replace("Dayplan-", "", $lookupArray[$i-1]['res_label']);
		    $targetUUID = $lookupArray[$i-1]['uuid'];
		    $previous_isDated = $this->diary_date_check($previousLabel);
		    $previous_prefix = $this->diaryContextPrefix($previousLabel);
		    
		    if($previous_isDated && ($current_prefix == $previous_prefix)){
			echo "[Add Previous: ".$previousLabel." Prefix: $previous_prefix ]";
			//add a "previous" link
			$this->addLinkingRel($originUUID, 'Media (various)', $targetUUID, 'Media (various)', 'Previous', $projectUUID);
		    }
		}
		if($i < $maxIndex){
		    $nextLabel = str_replace("Dayplan-", "", $lookupArray[$i+1]['res_label']);
		    $targetUUID = $lookupArray[$i+1]['uuid'];
		    $next_isDated = $this->diary_date_check($nextLabel);
		    $next_prefix = $this->diaryContextPrefix($nextLabel);
		    
		    
		    if($next_isDated && ($current_prefix == $next_prefix)){
			//add a "next" link
			echo "[Add Next: ".$nextLabel." Prefix: $next_prefix ]";
			$this->addLinkingRel($originUUID, 'Media (various)', $targetUUID, 'Media (various)', 'Next', $projectUUID);
		    }
		}
	    }
	    
	$i++;
	}//end loop through diaries
	
    }//end function
    
    
    
    
     //develop field photo metadata
   function dayplanDiaryLinkAction(){
	$this->_helper->viewRenderer->setNoRender();
	$db = Zend_Registry::get('db');
	
	$sql = "SELECT uuid, res_label, project_id
	FROM resource
	WHERE res_label LIKE 'Dayplan%'
	ORDER BY res_label	
	";
    
	$result = $db->fetchAll($sql, 2);
	
	foreach($result as $row){
	    $projectUUID = $row['project_id'];
	    $originalLabel = $row['res_label'];
	    $resUUID = $row['uuid'];
	    
	    $label = str_replace("Dayplan-", "", $originalLabel);
	    
	    $current_isDated = $this->diary_date_check($label);
	    $current_prefix = $this->diaryContextPrefix($label);
	    
	    echo "<br/><br/>$originalLabel (".$label.") IsDated: $current_isDated Prefix: $current_prefix ";
	    
	    if($current_isDated){
		
		$currentArray = explode("-", $label);
		$currentDate = $currentArray[2]."-".$currentArray[3]."-".$currentArray[4];
		echo " Current Date: ".$currentDate;
		
		$diaryLabel = $current_prefix."-".$currentDate;
		echo " <strong>".$diaryLabel."</strong>";
		
		$sql = "SELECT uuid FROM diary WHERE diary_label = '$diaryLabel' LIMIT 1";
		$resultB = $db->fetchAll($sql, 2);
		if($resultB){
		    $diaryUUID = $resultB[0]['uuid'];
		    echo ": ".$diaryUUID;
		    $this->addLinkingRel($resUUID, 'Media (various)', $diaryUUID , 'Diary / Narrative', 'link', $projectUUID);
		    $this->addLinkingRel($diaryUUID , 'Diary / Narrative', $resUUID, 'Media (various)', 'link', $projectUUID);
		}
		
	    }
	    
	}//end loop through diaries
	
    }//end function
    
    
    
    
    
    
    
    private function diaryContextPrefix($label){
	$labelArray = explode("-", $label);
	if(count($labelArray)>1){
	    $output = $labelArray[0]."-".$labelArray[1];
	}
	else{
	    $output = $label;
	}
	return $output;
    }
    
    
    //checks to see if a diary label has a specific date
    private  function diary_date_check($label){
	
	$labelArray = explode("-", $label);
	
	$isDated = false;
	if(count($labelArray)>3){
	    if(is_numeric($labelArray[2])){
		if($labelArray[2]>=2000){
		    if(is_numeric($labelArray[3])){
			$isDated = true;
		    }
		}
	    }
	}
	
	return $isDated;
    }
    
    private function diary_date_make($label){
	
	$label = str_replace("Dayplan-", "", $label);
	$labelArray = explode("-", $label);
	
	$output = false;
	if(count($labelArray)>4){
	    $labelArray[4] = str_replace("(1)", "", $labelArray[4]);
	    $labelArray[4] = str_replace("(2)", "", $labelArray[4]);
	    $labelArray[4] = str_replace("(3)", "", $labelArray[4]);
	    $labelArray[4] = trim($labelArray[4]);
	    $checkDate = $labelArray[2]."-".$labelArray[3]."-".$labelArray[4];
	    $olderThen = "2000-1-1";
	    if(strtotime($checkDate) > strtotime($olderThen)){
		$output = $checkDate;
	    }
	}
	
	return $output;
    }
    
    
    private function addLinkingRel($originUUID, $originType, $targetUUID, $targetType, $linkFieldValue, $projectUUID, $dataTableName = 'manual', $obsNum = 1){
        
        $db = Zend_Registry::get('db');
        
        //add origin and targget for this resource
        $hashLink       = md5($originUUID . '_' . $obsNum . '_' . $targetUUID . '_' . $linkFieldValue);
        
        $sql = "SELECT links.link_uuid
        FROM links
        WHERE links.project_id = '$projectUUID'
        AND links.hash_link = '$hashLink '
        ";
        
        $linkRows = $db->fetchAll($sql, 2);
        if($linkRows ){
            $linkUUID = $linkRows [0]["link_uuid"];
        }
        else{
            $linkUUID       = GenericFunctions::generateUUID();                            
            $data = array(
                        'project_id'   => $projectUUID,
                        'source_id'          => $dataTableName,
                        'hash_link'         => $hashLink,
                        'link_type'         => $linkFieldValue,
                        'link_uuid'         => $linkUUID,
                        'origin_type'       => $originType,         
                        'origin_uuid'       => $originUUID,              
                        'origin_obs'        => $obsNum,
                        'targ_type'         => $targetType,        
                        'targ_uuid'         => $targetUUID,         
                        'targ_obs'          => $obsNum 
                    );
                    //Zend_Debug::dump($data);
            $db->insert("links", $data);
                   
        }//end addition of new object linking
        
        return $linkUUID;
    }
    
    
    
    
    //this fixes duplicate journal labels
    function journalLabelAction(){
	
	$this->_helper->viewRenderer->setNoRender();
	$db = Zend_Registry::get('db');
	
	$sql = "SELECT count(field_4) as labelCount, field_4 as label
	FROM z_1_1e3575e43
	GROUP BY field_4
	ORDER BY count(field_4) DESC
	";
	
	$resultA = $db->fetchAll($sql, 2);
	foreach($resultA as $rowA){
	    if($rowA["labelCount"]>1){
		
		//duplicate labels that need fixing
		
		$label = $rowA["label"];
		$sql = "SELECT id, field_1 as filePath FROM z_1_1e3575e43
		WHERE field_4 = '$label'
		";
		$result = $db->fetchAll($sql, 2);
		
		$priorAreaTrench = "";
		$priorLabel = "";
		foreach($result as $row){
		    $id = $row["id"];
		    $filePath = $row["filePath"];
		    $filePathArray =  explode("/", $filePath);
		    $year = $filePathArray[1];
		    $file = $filePathArray[2];
		    $htmlFileName = str_replace(".html", "", $file);
		    
		  
		    $data = array();
		    $data["area"] = substr($htmlFileName, 0, 1);
		    $data["trench"] = substr($htmlFileName, 1, 1);
		    $twoDigitTrench = false;
		    if(is_numeric(substr($htmlFileName, 2, 1))){
			$twoDigitTrench = true;
			$data["trench"] = substr($htmlFileName, 1, 2);
		    }
		    
		    $areaTrench = $data["area"] ."-". $data["trench"];
		    if($priorAreaTrench != $areaTrench){
			$priorAreaTrench = $areaTrench;
			$i = 1;
		    }
		    else{
			$i++;
		    }
		    
		    
		    $data["year"] = $year;
		    
		    if(!stristr($htmlFileName, "summary")){
			if($twoDigitTrench){
			    $initialsStart = 3;
			    $RawDateStart = 5;
			}
			else{
			    $initialsStart = 2;
			    $RawDateStart = 4;
			}
			    
			$data["initials"] = substr($htmlFileName, $initialsStart, 2);
			if(!is_numeric(substr($htmlFileName, $initialsStart+2, 1))){
			    $RawDateStart = $RawDateStart + 1;
			}
			
			if(is_numeric($data["initials"])){
			    $RawDateStart = $RawDateStart - 2;
			    $data["initials"] = "";
			}
			
			$dateRaw = substr($htmlFileName, $RawDateStart, 8);
			$month = substr($dateRaw, 0, 2);
			$day = substr($dateRaw, 2, 2);
			$year = substr($dateRaw, 4, 4);
			
			if(checkdate($month, $day, $year)){
			    $data["log_date"] = $year."-".$month."-".$day;
			}
			else{
			    $data["log_date"] = $data["year"];
			}
			
			$data["log_name"] = $data["area"]."-".$data["trench"]."-".$data["log_date"];
			
			if($data["log_name"] != $priorLabel){
			    $priorLabel = $data["log_name"];
			    $jj = 1;
			}
			else{
			    $jj++;
			    $data["log_name"] .= " ($jj)";
			}
			
			
		    }
		    else{
			
			$data["log_date"] = $data["year"];
			$data["initials"] = "";
			if(stristr($htmlFileName, "final")){
			    $data["log_name"] = $data["area"]."-".$data["trench"]."-".$data["year"]."-Final Summary";
			}
			else{
			    $data["log_name"] = $data["area"]."-".$data["trench"]."-".$data["year"]."-Summary";	
			}
			
			if($i>1){
			    $data["log_name"] .= " ($i)";
			}
			
		    }
		    
		    
		    
		    
		    
		    
		    echo "<br/><br/>";
		    echo "<br/>".$htmlFileName." ($label)";
		    echo "<br/>";
		    echo Zend_Json::encode($data);
		    
		    $upData = array("field_2" => $data["area"],
				    "field_3" => $data["trench"],
				    "field_4" => $data["log_name"],
				    "field_5" => $data["log_date"],
				    "field_6" => $data["initials"],
				    "field_7" => $data["year"],
				    );
		    
		    $where = array();
		    $where[] = "id = $id ";
		    $db->update("z_1_1e3575e43", $upData, $where);
		    
		}//end loop through duplicate names
		
		
	    
	    }//end checking on labels
	}//end loop
	
    }
    
    
    
    //this fixes duplicate journal labels
    function journalLabelFixAction(){
	
	$delimiters = array(",",
                            "&",
                            ";",
                            ".",
                            ":",
			    "ending");

	$this->_helper->viewRenderer->setNoRender();
	$db = Zend_Registry::get('db');
	$this->setUTFconnection($db);
	
	/*
	
	UPDATE 
	z_1_1e3575e43
	SET field_8 = replace(field_8, 'Â', '')
	
	*/
	
	
	$sql = "SELECT *
	FROM z_1_1e3575e43
	";
	
	$result = $db->fetchAll($sql, 2);
	foreach($result as $row){
    
	    $id = $row["id"];
	    $label = $row["field_4"];
	    $html = $row["field_8"];
	    $labelArray = explode("-", $label);
	    
	    $year = $row["field_7"];
	    $area = $labelArray[0];
	    $trench = $labelArray[1];
	    
	    if(($labelArray[2]>=2000)||(stristr($label, "summary"))){
		
	    }
	    else{
		echo "<br/><br/><br/>($id) $label";
		$goodDate = false;
		$xml = simplexml_load_string($html);
		if($xml){
		    $xresult = $xml->xpath('//p/span');
		    foreach($xresult as $span){
			$span = $span."";
			$span = str_replace("  ", " ", $span);
			$span = str_replace(", 20", "[[[year]]]20", $span);
			$spanArray = array();
			foreach($delimiters as $delimiter){
			    $tempArray = explode($delimiter, $span);
			    if(count($tempArray)>0){
				foreach($tempArray as $temp){
				    $spanArray[] = str_replace("[[[year]]]20", ", 20", $temp);
				}
			    }
			}
			
			foreach($spanArray as $part){
			    //echo "<br/>Part: ".$part;
			    $part = trim($part);
			    $dateTest = strtotime($part);
			    $minDate = strtotime("January 1, 2000");
			    if($dateTest>$minDate){
				$goodDate = date("Y-m-d", $dateTest); 
			    }
			}
		    }
		    
		    if($goodDate != false){
			echo " :".$goodDate;
			
			$newLabel = $area."-".$trench."-".$goodDate;
			$sql = "SELECT * FROM z_1_1e3575e43 WHERE field_4 LIKE '$newLabel%' ;";
			
			$resultB = $db->fetchAll($sql, 2); 
			if($resultB){
			    $oldCount = count($resultB);
			    $newLabel.= " ($oldCount)";
			}
			
			$data = array("field_4" => $newLabel, "field_5" => $goodDate);
			$where = "id = $id ";
			$db->update("z_1_1e3575e43", $data, $where);
			
		    }
		    else{
			echo " (no date)";
			
			$newLabel = $area."-".$trench."-".$year."(S)";
			$sql = "SELECT * FROM z_1_1e3575e43 WHERE field_4 LIKE '$newLabel%' ;";
			
			$resultB = $db->fetchAll($sql, 2); 
			if($resultB){
			    $oldCount = count($resultB);
			    $newLabel.= " ($oldCount)";
			}
			
			$data = array("field_4" => $newLabel);
			$where = "id = $id ";
			$db->update("z_1_1e3575e43", $data, $where);
			
		    }
		}
		else{
		    echo " <strong>BAD XML!</strong>";
		}
	    }
	}
    }//end function
    
   
   
   //this fixes duplicate journal labels
    function journalLabelFixNewAction(){
	
	$delimiters = array(",",
                            "&",
                            ";",
                            ".",
                            ":",
			    "ending");

	$this->_helper->viewRenderer->setNoRender();
	$db = Zend_Registry::get('db');
	$this->setUTFconnection($db);
	
	/*
	
	UPDATE 
	z_1_1e3575e43
	SET field_8 = replace(field_8, 'Â', '')
	
	*/
	
	
	$sql = "SELECT *
	FROM z_1_1e3575e43
	";
	
	$result = $db->fetchAll($sql, 2);
	foreach($result as $row){
    
	    $id = $row["id"];
	    $label = $row["field_4"];
	    $logDate = $row["field_5"];
	    $html = $row["field_8"];
	    $filePath = $row["field_1"];
	    $filePathArray = explode("/", $filePath);
	    $year = $filePathArray[1];
	    $file = $filePathArray[2];
	    $htmlFileName = str_replace(".html", "", $file);
	    
	    $twoDigitTrench = false;
	    if(stristr($htmlFileName, "area")){
		$fpArea = substr($htmlFileName, 5, 1); //Area A Tr 1 ktJournal.html
		if(stristr($htmlFileName, "tr")){
		    $fpTrench = substr($htmlFileName, 10, 1); //Area A Tr 1 ktJournal.html
		    if(is_numeric(substr($htmlFileName,	11, 1))){
			$twoDigitTrench = true;
			$singleTrench = $fpTrench;
			$fpTrench = substr($htmlFileName, 10, 2);
		    }
		}
	    }
	    else{
		
		$fpArea = substr($htmlFileName, 0, 1);
		$fpTrench = substr($htmlFileName, 1, 1);
		if(is_numeric(substr($htmlFileName,	2, 1))){
		    $twoDigitTrench = true;
		    $singleTrench = $fpTrench;
		    $fpTrench = substr($htmlFileName, 1, 2);
		    if($fpTrench != 16 && $fpTrench != 17){
			if(is_numeric(substr($htmlFileName,	3, 1))){
			    $twoDigitTrench = false;
			    $fpTrench = $singleTrench;
			}
		    }
		}
	    }
	    
	    $fpAreaTrench = $fpArea ."-".  $fpTrench;
	    
	    
	    
	    $labelArray = explode("-", $label);
	    
	    $year = $row["field_7"];
	    $area = $labelArray[0];
	    $trench = $labelArray[1];
	    $areaTrench = $area ."-".$trench;
	    
	    
	    if($areaTrench == $fpAreaTrench && $fpTrench == $row["field_3"]){
		
	    }
	    else{
		
		echo "<br/><br/><br/>($id) <em>$filePath</em> $label";
		
		$htmlStart = substr($html, 0, 300);
		$htmlFix = str_replace($areaTrench, $fpAreaTrench, $htmlStart);
		
		if($twoDigitTrench){
		    $htmlFix = str_replace($area."-".$singleTrench, $fpAreaTrench, $htmlFix);
		}
		
		if($htmlFix != $htmlStart){
		    $html = str_replace($htmlStart, $htmlFix, $html);
		    echo " [html fix] ";
		}
		
		$checkDateOK = false;
		$initials = false;
		
		
		if(strlen($logDate)>1){
		    $goodDate = $logDate;
		}
		else{
		    $goodDate = $year;
		}
		
		
		if(true){
		    if($twoDigitTrench){
			$initialsStart = 3;
			$RawDateStart = 5;
		    }
		    else{
			$initialsStart = 2;
			$RawDateStart = 4;
		    }
		    
		    $initials = substr($htmlFileName, $initialsStart, 2);
		   
		    if(stristr($htmlFileName, "JWS")||stristr($htmlFileName, "JWP")){
			$initials == "JW";
			$RawDateStart++;
		    }
		    else{
			if(!is_numeric(substr($htmlFileName, $initialsStart+2, 1))){
			    $initials = false;
			}
		    }
		    
		    //echo " try initials: ".$initials;
		    
		    if(is_numeric($initials) && $initials != false){
			$RawDateStart = $RawDateStart - 2;
			$initials = false;
		    }
		    
		    if($initials != false){
			 echo " initials: ".$initials;
		    }
		    
		    
		    $dateRaw = substr($htmlFileName, $RawDateStart, 8);
		    
		    $month = substr($dateRaw, 0, 2);
		    $day = substr($dateRaw, 2, 2);
		    $fpYear = substr($dateRaw, 4, 4);
		    $checkDate = $fpYear."-".$month."-".$day;
		    if((strtotime($checkDate)>strtotime("2000-1-1")) && (strtotime($checkDate) < strtotime("2009-1-1")) ){
			$goodDate = $checkDate;
			$checkDateOK = true;
		    }
		    
		    if(strlen($goodDate) <= strlen("02-05-20")){
			$goodDate = $year;
		    }
		    
		}
		
		
		
		
		$newLabel = $area."-".$fpTrench."-".$goodDate;
		
		if(stristr($htmlFileName, "journal")){
		    $newLabel .= "-Journal";
		}
		elseif(stristr($htmlFileName, "summary")){
		    $newLabel .= "-Summary";
		}
		elseif(!$checkDateOK){
		    $newLabel .= "-Log";
		}
		
		
		$sql = "SELECT * FROM z_1_1e3575e43 WHERE field_4 LIKE '$newLabel%' ;";
		
		$resultB = $db->fetchAll($sql, 2); 
		if($resultB){
		    $oldCount = count($resultB);
		    if($oldCount >1){
			$newLabel.= " ($oldCount)";
		    }
		}
		
		echo " New Label: ".$newLabel;
		
		$data = array("field_4" => $newLabel, "field_3" => $fpTrench, "field_8" => $html);
		
		if($initials != false){
		    echo ", good initials: ". $initials;
		    $data["field_6"] = $initials;
		}
		else{
		    $data["field_6"] = "";
		}
		
		if(is_numeric($fpTrench)){
		    echo ", good trench: ".$fpTrench;
		    $where = "id = $id ";
		    $db->update("z_1_1e3575e43", $data, $where);
		}
	    }
	}
    }//end function
   
   
   
   //this fixes duplicate journal labels
    function journalDateFixAction(){

	$this->_helper->viewRenderer->setNoRender();
	$db = Zend_Registry::get('db');
	$this->setUTFconnection($db);
	
	
	
	$sql = "SELECT count(field_4) as labelCount, field_4 as label
	FROM z_1_1e3575e43
	GROUP BY field_4
	ORDER BY count(field_4) DESC
	";
	
	$result = $db->fetchAll($sql, 2);
	foreach($result as $row){
    
	    $countLab = $row["labelCount"];
	    $label = $row["label"];
	    
	    if($countLab > 1){
		
		$sql = "SELECT * FROM z_1_1e3575e43 WHERE field_4 = '$label' ;";
		
		$resultB = $db->fetchAll($sql, 2); 
		$i = 1;
		foreach($resultB as $rowB){
		    $id = $rowB["id"];
		    $newLabel = $label." ($i)";
		    $data = array("field_4" => $newLabel);
		    echo "<br/>($id) $label should now be: ".$newLabel;
		    $where = "id = $id ";
		    
		    if($i>1){
			$db->update("z_1_1e3575e43", $data, $where);
		    }
		    $i++;
		}
		
	    }
	    
	    
	}
    }
   






    function locusJournalLinkAction(){
	
	$this->_helper->viewRenderer->setNoRender();
	$db = Zend_Registry::get('db');
	$this->setUTFconnection($db);
	
	
	
	$sql = "SELECT space.uuid, space_contain.parent_uuid, space.project_id
	FROM space
	JOIN space_contain ON space.uuid = space_contain.child_uuid
	WHERE space.space_label LIKE 'Locus%'
	
	";
	
	$result = $db->fetchAll($sql, 2);
	foreach($result as $row){
	    
	    $itemUUID = $row["uuid"];
	    $trenchUUID  = $row["parent_uuid"];
	    $projectUUID = $row["project_id"];
	    
	    //start date
	    $sql = "SELECT val_tab.val_text
	    FROM observe
	    JOIN properties ON observe.property_uuid = properties.property_uuid
	    JOIN val_tab ON properties.value_uuid = val_tab.value_uuid
	    WHERE observe.subject_uuid = '$itemUUID'
	    AND properties.variable_uuid = '667B68A9-7283-4DA5-86CB-2742586DB672'
	    LIMIT 1
	    ";
	    
	    //echo "<br/><br/>".$sql;
	
	    $startRaw = false;
	    $resultS = $db->fetchAll($sql, 2);
	    if($resultS){
		$startRaw = $resultS[0]["val_text"];
	    }
	    
	    //end date
	    $sql = "SELECT val_tab.val_text
	    FROM observe
	    JOIN properties ON observe.property_uuid = properties.property_uuid
	    JOIN val_tab ON properties.value_uuid = val_tab.value_uuid
	    WHERE observe.subject_uuid = '$itemUUID'
	    AND properties.variable_uuid = 'F63A8CB7-1F50-4F45-4D8D-E23462DA4755'
	    LIMIT 1
	    ";
	    
	    //echo "<br/><br/>".$sql;
	
	    $endRaw = false;
	    $resultE = $db->fetchAll($sql, 2);
	    if($resultE){
		$endRaw = $resultE[0]["val_text"];
	    }
	    
	    if($startRaw != false && $endRaw != false){
		
		/*
		$startExplode = explode("/", $startRaw);
		$startDate = $startExplode[2]."-".$startExplode[1]."-".$startExplode[0];
		$endExplode = explode("/", $endRaw);
		$endDate = $endExplode[2]."-".$endExplode[1]."-".$endExplode[0];
		
		$startNumDate = strtotime($startDate);
		$endNumDate = strtotime($endDate);
		
		//get diaries associated with the trench in question
		$sql = "SELECT links.targ_uuid, diary.diary_label as linkedLabel, 'Diary / Narrative' as type
		FROM links
		JOIN diary ON links.targ_uuid = diary.uuid
		WHERE links.origin_uuid = '$trenchUUID'
		AND (links.targ_type = 'Diary / Narrative')
		
		UNION
		
		SELECT links.targ_uuid, resource.res_label as linkedLabel, 'Media (various)' as type
		FROM links
		JOIN resource ON links.targ_uuid = resource.uuid
		WHERE links.origin_uuid = '$trenchUUID'
		AND (links.targ_type = 'Media (various)')
		";
		
		//echo "<br/><br/>".$sql;
		
		$resultB = $db->fetchAll($sql, 2);
		$linkArray = array();
		foreach($resultB as $rowB){
		    
		    $targUUID = $rowB["targ_uuid"];
		    $linkedName = $rowB["linkedLabel"];
		    $targType = $rowB["type"];
		    
		    $targDate = false;
		    //if($targType == "Diary / Narrative"){
		    if(true){
			$targDate = $this->diary_date_make($linkedName);
		    }    
		    
		    if($targDate != false){
			$targNumDate = strtotime($targDate);
			if(($startNumDate <= $targNumDate) && ($endNumDate >= $targNumDate)){
			    //echo "<br/> FOUUND! ($targUUID) $targType: ".$linkedName. " Date: $targDate";
			    //echo "<br/><br/>Locus ($itemUUID) $startDate to $endDate LINKS with ($targUUID), dating to: $targDate";
			    $sortKey = $targNumDate;
			    if($targType == "Diary / Narrative"){
				$sortKey = "A-".$sortKey;
			    }
			    else{
				$sortKey = "B-".$sortKey;
			    }
			    $linkArray[$sortKey] = array("targ_uuid" => $targUUID, "label" => $linkedName, "type" => $targType, "date" => $targDate);
			}
		    }
		}
		
		if(count($linkArray)>0){
		    ksort($linkArray);
		    foreach($linkArray as $link){
			$targUUID = $link["targ_uuid"];
			$targDate = $link["date"];
			$targLabel = $link["label"];
			$targType = $link["type"];
			echo "<br/><br/>Locus ($itemUUID) $startDate to $endDate LINKS with $targLabel ($targUUID), dating to: $targDate";
			$this->addLinkingRel($itemUUID , 'Locations or Objects', $targUUID, $targType, 'link', $projectUUID, 'manual-locus');
		    }
		
		}
		
		*/
		
	    }
	    elseif($startRaw != false || $endRaw != false){
		
		echo "<br/>Start: $startRaw End: $endRaw ";
		
		if($startRaw == false){
		    $startRaw = $endRaw;
		}
		
		if($endRaw == false){
		    $endRaw = $startRaw;
		}
		
		if(stristr($startRaw, "/")){
		    $startExplode = explode("/", $startRaw);
		    $startDate = $startExplode[2]."-".$startExplode[1]."-".$startExplode[0];
		}
		else{
		     $startDate = $startRaw;
		}
		if(stristr($endRaw, "/")){
		    $endExplode = explode("/", $endRaw);
		    $endDate = $endExplode[2]."-".$endExplode[1]."-".$endExplode[0];
		}
		else{
		    $endDate = $endRaw;
		}
		
		$startNumDate = strtotime($startDate);
		$endNumDate = strtotime($endDate);
		
		//get diaries associated with the trench in question
		$sql = "SELECT links.targ_uuid, diary.diary_label as linkedLabel, 'Diary / Narrative' as type
		FROM links
		JOIN diary ON links.targ_uuid = diary.uuid
		WHERE links.origin_uuid = '$trenchUUID'
		AND (links.targ_type = 'Diary / Narrative')
		
		UNION
		
		SELECT links.targ_uuid, resource.res_label as linkedLabel, 'Media (various)' as type
		FROM links
		JOIN resource ON links.targ_uuid = resource.uuid
		WHERE links.origin_uuid = '$trenchUUID'
		AND (links.targ_type = 'Media (various)')
		";
		
		//echo "<br/><br/>".$sql;
		
		$resultB = $db->fetchAll($sql, 2);
		$linkArray = array();
		foreach($resultB as $rowB){
		    
		    $targUUID = $rowB["targ_uuid"];
		    $linkedName = $rowB["linkedLabel"];
		    $targType = $rowB["type"];
		    
		    $targDate = false;
		    //if($targType == "Diary / Narrative"){
		    if(true){
			$targDate = $this->diary_date_make($linkedName);
		    }    
		    
		    if($targDate != false){
			$targNumDate = strtotime($targDate);
			if(($startNumDate <= $targNumDate) && ($endNumDate >= $targNumDate)){
			    //echo "<br/> FOUUND! ($targUUID) $targType: ".$linkedName. " Date: $targDate";
			    //echo "<br/><br/>Locus ($itemUUID) $startDate to $endDate LINKS with ($targUUID), dating to: $targDate";
			    $sortKey = $targNumDate;
			    if($targType == "Diary / Narrative"){
				$sortKey = "A-".$sortKey;
			    }
			    else{
				$sortKey = "B-".$sortKey;
			    }
			    $linkArray[$sortKey] = array("targ_uuid" => $targUUID, "label" => $linkedName, "type" => $targType, "date" => $targDate);
			}
		    }
		}
		
		if(count($linkArray)>0){
		    ksort($linkArray);
		    foreach($linkArray as $link){
			$targUUID = $link["targ_uuid"];
			$targDate = $link["date"];
			$targLabel = $link["label"];
			$targType = $link["type"];
			echo "<br/><br/>Locus ($itemUUID) $startDate to $endDate LINKS with $targLabel ($targUUID), dating to: $targDate";
			$this->addLinkingRel($itemUUID , 'Locations or Objects', $targUUID, $targType, 'link', $projectUUID, 'manual-locus-b');
		    }
		
		}
		
	    }
	}
	
	
    }







private function directoryToArray($directory, $recursive) {
	$array_items = array();
	if ($handle = opendir($directory)) {
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != "..") {
				if (is_dir($directory. "/" . $file)) {
					if($recursive) {
						$array_items = array_merge($array_items, $this->directoryToArray($directory. "/" . $file, $recursive));
					}
					$file = $directory . "/" . $file;
					$array_items[] = preg_replace("/\/\//si", "/", $file);
				} else {
					$file = $directory . "/" . $file;
					$array_items[] = preg_replace("/\/\//si", "/", $file);
				}
			}
		}
		closedir($handle);
	}
	return $array_items;
}



function findsCompressAction(){
	
    $this->_helper->viewRenderer->setNoRender();
    $fileArray = $this->directoryToArray("C:\\about_opencontext\\kenan\\thumbs\\", true);
    $fileCount = count($fileArray);
    echo "<h1>Files Found: $fileCount</h1>";
    echo "<table>";
    foreach($fileArray as $filename){
	$picture = false;
	if(stristr($filename, ".jpg")||stristr($filename, ".tif")){
	    $picture = true;
	}
	
	if($picture){
	     
	    list($width, $height) = getimagesize($filename);
	    //echo $width." by ".$height;
	    echo "<tr><td>$filename</td><td>$width</td><td>$height</td></tr>".chr(13);
	    //now make 480
	    $newwidth = 120;
	    $percent =  $newwidth / $width;
	    
	    $newheight = $height * $percent;
	    
	    // Load
	    $thumb = imagecreatetruecolor($newwidth, $newheight);
	    $source = imagecreatefromjpeg($filename);
	    
	    // Resize
	    imagecopyresized($thumb, $source, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
	    
	    // Output
	    //imagejpeg($thumb);
	    $destination_file = $filename;
	    imagejpeg($thumb, $destination_file, 100 );
	    
	    //memory clean up.
	    imagedestroy($source);
	    imagedestroy($thumb);

	}
    
    
    }
    echo "</table>";
}




    function sdacPixAction(){
	
	$sites = array();
	$sites[] = array('site' => 'CA-SCLI-533', 'cat' => '483'); 
	$sites[] = array('site' => 'CA-SCLI-1359', 'cat' => '198'); 
	$sites[] = array('site' => 'CA-SCLI-1294', 'cat' => '205'); 
	$sites[] = array('site' => 'CA-SDI-14693', 'cat' => '2'); 
	$sites[] = array('site' => 'CA-SCLI-1623', 'cat' => '199'); 
	$sites[] = array('site' => 'CA-SDI-30', 'cat' => 'S-34'); 
	$sites[] = array('site' => 'CA-SCLI-401', 'cat' => '203'); 
	$sites[] = array('site' => 'CA-SCLI-1644', 'cat' => '190'); 
	$sites[] = array('site' => 'CA-SDI-15254', 'cat' => '156'); 
	$sites[] = array('site' => 'CA-SDI-15254', 'cat' => '145'); 
	$sites[] = array('site' => 'CA-SCLI-1965', 'cat' => '15'); 
	$sites[] = array('site' => 'CA-SCLI-MISC', 'cat' => '14'); 
	$sites[] = array('site' => 'CA-SCLI-MISC', 'cat' => '22'); 
	$sites[] = array('site' => 'CA-SCLI-MISC', 'cat' => '23'); 
	$sites[] = array('site' => 'CA-SCLI-117', 'cat' => '00178'); 
	$sites[] = array('site' => 'CA-SCLI-1215', 'cat' => '00191'); 
	$sites[] = array('site' => 'CA-SCLI-1298', 'cat' => '00192'); 
	$sites[] = array('site' => 'CA-SCLI-1317', 'cat' => '00183'); 
	$sites[] = array('site' => 'CA-SCLI-1389', 'cat' => '00182'); 
	$sites[] = array('site' => 'CA-SCLI-1423', 'cat' => '00200'); 
	$sites[] = array('site' => 'CA-SCLI-1447', 'cat' => '00281'); 
	$sites[] = array('site' => 'CA-SCLI-1515', 'cat' => '00189'); 
	$sites[] = array('site' => 'CA-SCLI-152', 'cat' => '00174'); 
	$sites[] = array('site' => 'CA-SCLI-1540', 'cat' => '00185'); 
	$sites[] = array('site' => 'CA-SCLI-1673', 'cat' => '00201'); 
	$sites[] = array('site' => 'CA-SCLI-1700', 'cat' => '00193'); 
	$sites[] = array('site' => 'CA-SCLI-241', 'cat' => '00186'); 
	$sites[] = array('site' => 'CA-SCLI-29', 'cat' => '00030'); 
	$sites[] = array('site' => 'CA-SCLI-401', 'cat' => '00187'); 
	$sites[] = array('site' => 'CA-SCLI-750', 'cat' => '00188'); 
	$sites[] = array('site' => 'CA-SCLI-986', 'cat' => '00177'); 
	$sites[] = array('site' => 'CA-SDI-8330', 'cat' => '2987'); 
	$sites[] = array('site' => 'CA-SDI-681', 'cat' => '1738'); 
	$sites[] = array('site' => 'CA-SDI-681', 'cat' => '1740'); 
	$sites[] = array('site' => 'CA-SCLI-1209', 'cat' => '25'); 
	$sites[] = array('site' => 'CA-SCLI-1385', 'cat' => '00197'); 
	$sites[] = array('site' => 'CA-SCLI-1556', 'cat' => '00053'); 
	$sites[] = array('site' => 'CA-SCLI-262', 'cat' => '29'); 
	$sites[] = array('site' => 'CA-SCLI-42', 'cat' => '195'); 
	$sites[] = array('site' => 'CA-SCLI-581', 'cat' => '00026'); 
	$sites[] = array('site' => 'Not given', 'cat' => '00180'); 
	$sites[] = array('site' => 'Not given', 'cat' => '00632'); 
	$sites[] = array('site' => 'SCLI-MISC', 'cat' => '16'); 
	$sites[] = array('site' => 'CA-SCLI-1215', 'cat' => '00028'); 
	$sites[] = array('site' => 'CA-SCLI-1499', 'cat' => '00181'); 
	$sites[] = array('site' => 'SDI-8330', 'cat' => '3509'); 
 

	$this->_helper->viewRenderer->setNoRender();
	$db = Zend_Registry::get('db');
	$this->setUTFconnection($db);
	
	$fileArray = $this->directoryToArray("C:\\about_opencontext\\sdac\\full", false);
	$fileCount = count($fileArray);
	echo "<h1>Files Found: $fileCount</h1>";
	echo "<table>";
	foreach($fileArray as $file){
	    //echo "<br/>".$file;
	    $fileFix = str_replace("C:\\about_opencontext\\sdac\\full/", "http://about.oc/sdac/full/", $file);
	    $fileName = str_replace("http://about.oc/sdac/full/", "", $fileFix);
	    //echo "<br/>".$fileName; 
	    
	    $lowResName = str_replace(".JPG", ".jpg", $fileName);
	    $lowResName = str_replace(".tif", ".jpg", $lowResName);
	    
	    $fileNameParse = str_replace(".JPG", "", $fileName);
	    $fileNameParse = str_replace(".tif", "", $fileNameParse);
	    $fileNameParse = str_replace(".jpg", "", $fileNameParse);
	    //$fileNameParse = str_replace("_", "-", $fileNameParse);
	    

	    
	    
	    $sql= "SELECT * FROM resource
	    WHERE project_id = '3FAAA477-5572-4B05-8DC1-CA264FE1FC10'
	    AND ia_fullfile LIKE '%$fileName'
	    LIMIT 1;";
	    
	    $result = $db->fetchAll($sql, 2);
	    
	    if($result){
		
		
	    }
	    else{
		//echo "<br/>".$fileName. " ($fileNameParse)";
		$useFileName = str_replace("SCLICat", "SCLI_Cat_", $fileNameParse);
		$useFileName = str_replace("-", "_", $useFileName);
		
		$fileNameArray = explode("_", $useFileName);
		$firstNumber = "";
		$secondNumber = "";
		
		foreach($fileNameArray as $namePart){
		    if(is_numeric($namePart)){
			$namePart = $namePart +0;
			
			if(strlen($firstNumber)<1){
			    $firstNumber = $namePart;
			}
			elseif(strlen($secondNumber)<1){
			    $secondNumber = $namePart;
			}
			
		    }
		}
		
		if(strlen($firstNumber)>1 && strlen($secondNumber)<1){
		    $secondNumber = $firstNumber;
		    $firstNumber = "";
		}
		
		$sql = "SELECT * FROM space
		WHERE project_id = '3FAAA477-5572-4B05-8DC1-CA264FE1FC10'
		AND space_label LIKE '%$secondNumber%'
		AND full_context LIKE '%$firstNumber%'
		AND class_uuid = '9fac2800-3e80-11df-9879-0800200c9a66'
		LIMIT 1
		";
		
		$resultB = $db->fetchAll($sql, 2);
		if($resultB){
		    $itemName = $resultB[0]["space_label"];
		    $itemContext = $resultB[0]["full_context"];
		    
		    $contextArray = explode("|xx|", $itemContext);
		    $state = $contextArray[2];
		    $site = $contextArray[3];
		    
		}
		else{
		     $itemName = "";
		     $itemContext = "";
		     $state = "";
		     $site = "";
		}
		
		
		
		
		echo "<tr><td>$fileName</td><td>$fileNameParse</td><td>$lowResName</td><td>$state</td><td>$site</td><td>$itemName</td></tr>";
		
		
		
	    }
	    
	    
	    
	    $siteFound = false;
	    $catFound  = false;
	    foreach($sites as $siteItem){
		$site = $siteItem["site"];
		$catItem = $siteItem["cat"];
		if(stristr($fileNameParse, $site)){
		    $siteFound = $site;
		    if($siteFound == "SDI-30"){
			$siteFound = "CA-".$siteFound;
		    }
		    
		    if(stristr($fileNameParse, $catItem)){
			$catFound = $catItem;
		    }
		    
		}
	    }
	    if($siteFound != false && $catFound != false){
		//echo " <strong>[Site: ".$siteFound."], [$catFound]</strong>";
	    }
	    
	}
	
	echo "</table>";

    }



    function sdacPixFixAction(){
	
	$this->_helper->viewRenderer->setNoRender();
	$db = Zend_Registry::get('db');
	$this->setUTFconnection($db);
    
	$sql = "SELECT * FROM resource WHERE project_id = '3FAAA477-5572-4B05-8DC1-CA264FE1FC10' ; ";

	$result = $db->fetchAll($sql, 2);
	$fCount = 0;
	foreach($result as $row){
	    $itemUUID = $row["uuid"];
	    $projectUUID = "3FAAA477-5572-4B05-8DC1-CA264FE1FC10";
	    
	    @$itemXML = file_get_contents("http://opencontext.org/media/".$itemUUID .".xml");
	    if(!$itemXML){
		echo "<br/>Missing: $itemUUID  <a href='http://penelope2.oc/preview-edit/media?id=".$itemUUID."'>Do something</a>";
	    }
	    else{
		$fCount++;
		echo "<br/>Found ($fCount): $itemUUID";
	    }
	    
	    sleep(.5);
	    /*
	    $subObs = 1;
	    $propUUID = "A74AFD97-C58D-4C7E-D8BF-16D84FFF794B";
	    
	    $itemType = "Media (various)";
	    $obsHashText = md5($projectUUID . "_" . $itemUUID . "_" . $subObs . "_" . $propUUID);
	    
	    $data = array("project_id"=> $projectUUID,
                          "source_id"=> 'z_45_b0ed45684',
                          "hash_obs" => $obsHashText,
                          "subject_type" => $itemType,
                          "subject_uuid" => $itemUUID,
                          "obs_num" => 1,
                          "property_uuid" => $propUUID);
	    
	    try{            
                $db->insert("observe", $data); 
            } catch (Exception $e) {
                    echo $e->getMessage(), "\n";
            }
	    */
	    
	}
    
    
    }





}