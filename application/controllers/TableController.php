<?php

/** Zend_Controller_Action */
//require_once 'Zend/Controller/Action.php';
//require_once 'OpenContext/Controller/Action/Helper/SolrAccess.php';

//error_reporting(E_ALL ^ E_NOTICE);
// increase the memory limit
ini_set("memory_limit", "6024M");
// set maximum execution time to no limit
ini_set("max_execution_time", "0");

class TableController extends Zend_Controller_Action {
    
    function init()
    {  
        require_once 'App/Util/GenericFunctions.php';
    }
    
	 
	 
	 
	 //this saves a table to the database limited by the variable_uuids
	 //http://penelope.oc/table/class-db-save-vars
	 
	 function classDbSaveVarsAction(){
		          
        
        //class of items to export in a table
        $classUUID = "881CEDA3-C445-4C9C-4D4B-634BD2963892"; //animal bone
		  //$classUUID = "A2017643-0086-4D98-4932-E4AD3884E99D"; //pottery
		  
		  $page = 1;
		  //$setSize = 300000;
		  $setSize = 3000000;
		  
        if(isset($_REQUEST["classUUID"])){
				$classUUID = $_REQUEST["classUUID"];
		  }
		  if(isset($_REQUEST["page"])){
				$page = $_REQUEST["page"];
		  }
		  if(isset($_REQUEST["setSize"])){
				$setSize = $_REQUEST["setSize"];
		  }
		  $format = "json";
		  if(isset($_REQUEST["format"])){
				$format = $_REQUEST["format"];
		  }
		  
		  Zend_Loader::loadClass('TabOut_Table');
		  $tableArray = false;
		  $linkedFields = false;
		  
		  $limitingProjArray = array();
		  
		  $projArray = array("99BDB878-6411-44F8-2D7B-A99384A6CA21" => "Ulucak",
									"731B0670-CE2A-414A-8EF6-9C050A1C60F5" => "Karain",
									"BC90D462-6639-4087-8527-6BB9E528E07D" => "Çukuriçi Höyük",
									"74749949-4FD4-4C3E-C830-5AA75703E08E" => "Barçın Höyük",
									"3" => "Domuztepe",
									"D297CD29-50CA-4B2C-4A07-498ADF3AF487" => "Ilıpınar",
									"1B426F7C-99EC-4322-4069-E8DBD927CCF1" => "Çatalhöyük (main)",
									"8894EEC0-DC96-4304-1EFC-4572FD91717A" => "Öküzini",
									"05F5B702-2967-49B1-FEAA-9B2AA0184513" => "Köşk Höyük Faunal Data",
									"CDD40C27-62ED-4966-AF3D-E781DD0D4846" => "Erbaba Höyük",
									"02594C48-7497-40D7-11AE-AB942DC513B8" => "Çatalhöyük Area TP",
									"TESTPRJ0000000004" => 'Pinarbasi'
									);
		 
		  foreach($projArray as $key => $name){
				$limitingProjArray[] = $key;
		  }
		 
		  
		  $limitVars = array(
				0 => '826BA030-82B5-423C-2B6F-CD2175EA51B4', 
				1 => 'F6969D83-ABCF-4380-3870-946C051214AC', 
				2 => '38708EAD-EC3E-41D2-6844-43A31083E06C', 
				3 => 'C3ACF0CD-E7ED-4ACE-0819-23E521CEDF4E', 
				4 => 'EAB01691-D57B-4056-0EB0-1D937D4AC0A3', 
				5 => '5F12A304-A483-457D-BDB3-93031BAD506D', 
				6 => 'CFAFEE06-B090-4D88-E8FE-210D4A4D47D5', 
				7 => 'B456DA90-411E-470B-D5C0-B005B98ACDC7', 
				8 => 'D14E9A37-B791-447D-14D5-E5AF8A0396E5', 
				9 => '9D4624E3-B303-4870-843D-C4EC5E32285C', 
				10 => '766A41EB-2052-4F1A-32FD-3387065C981C', 
				11 => '8F9E66FE-6185-4C21-2E26-72FE40DB98F3', 
				12 => 'C13598EF-BBB9-43E6-2DEF-A44B95FFC23E', 
				13 => '707C66EC-1892-4D63-6074-DCAA569A3502', 
				14 => '3F2DA6B9-D3FA-4B1C-5D9A-74EE66773CDB', 
				15 => '7A9268C0-6C97-4004-0E89-449D45DE0E47', 
				16 => 'D6E3B1F4-D5CF-4F0F-BD50-AFE39C83D081', 
				17 => 'A9A6CA3E-838B-461B-C3CC-90EDD2B8347A', 
				18 => 'E236C2AB-1876-44A2-F0AC-3360DB64D55E', 
				19 => '38C3E8C8-4A37-4496-AF41-C17FBB9D1A8B', 
				20 => 'EA7718AA-10A4-48C5-58D7-56E2133D0C7E', 
				21 => '1331B965-BE90-493F-D413-09CF4D2C5A13', 
				22 => 'CEEB64A8-B415-43CC-80BF-F0C71785A5B8', 
				23 => '6A02AE7B-B85B-4D35-E424-AC0F9D1C61A8', 
				24 => 'D596B2F6-807D-496F-EE67-CFBD975D43C4', 
				25 => '9122C711-2373-4E70-269A-C182CBDD6917'
		  );
		 
		 
		 
		 
		  $tableObj = new TabOut_Table;
		  $tableObj->setSize = $setSize;
		  $tableObj->page = $page;
		  $tableObj->limitingProjArray = $limitingProjArray;
		  $tableObj->limitingVarArray = $limitVars ;
		  $tableObj->showUnitTypeFields = false;
		  $tableObj->limitUnitTypeFields = false;
		  $tableObj->showSourceFields = true;
		  $tableObj->showBP = false;
		  $tableObj->showBCE = true;
		  $tableObj->showLDSourceValues = true;
		  $tableObj->tableID = false;
		  $tableObj->DBtableID = "z_ex_teeth";
		  
		  //$tableObj->recordQueries = true;
		  $tableObj->sortForSourceVars = " var_tab.sort_order, sCount DESC, var_tab.var_label ";
		  $linkedFields = $tableObj->getLinkedVariables($classUUID);
		  $tableArray = $tableObj->makeTableArray($classUUID);
		  
		  if($format == "json"){
				$this->_helper->viewRenderer->setNoRender();
				$output = array("linkedFields" => $linkedFields);
				header('Content-Type: application/json; charset=utf8');
				echo Zend_Json::encode($output);
		  }
	 }
	 

	 
	 
	 
	  //save all the data to a table
	  // penelope.oc/table/class-db-save-unit-type
	  
	 function createTableAction(){
		          
		  //parameters needing boolean values
		  $booleanParams = array("showUnitTypeFields",
										 "limitUnitTypeFields",
										 "showSourceFields",
										 "showLDSourceValues",
										 "showBPdates",
										 "showBCEdates"
										 );
		  
		  
		  $page = 1;
		  $setSize = 3000000;
		  $showUnitTypeFields = false; //boolean, show unit type fields (for measurement types, as in zooarchaeology standard measurements)
		  $limitUnitTypeFields = false; //boolean, limit outputs to only items with unit type fields.
		  $showSourceFields = true; //boolean, show the original data (source fields)
		  $showLDSourceValues = true; //boolen, show the original value linked to linked data
		  $showBPdates = false;
		  $showBCEdates = true;
		  
		  $tableName = "z_ex_".(substr(md5( (date("Y-M-D", time()))  . (rand(0,10000)) ),8));
		  
		  $rawRequestParams =  $this->_request->getParams();
		  $requestParams = array(); 
		  foreach($rawRequestParams as $paramKey => $value){
				if(!is_array($value) && in_array($paramKey, $booleanParams)){
					 if($value == "1"){
						  $value = true;
					 }
					 elseif($value == "0"){
						  $value = false;
					 }
				}
				$requestParams[$paramKey] = $value;
		  }
		  
        if(isset($requestParams["classUUID"])){
				$classUUID = $requestParams["classUUID"];
		  }
		  if(isset($requestParams["page"])){
				$page = $requestParams["page"];
		  }
		  if(isset($requestParams["setSize"])){
				$setSize = $requestParams["setSize"];
		  }
		  if(isset($requestParams["setSize"])){
				$setSize = $requestParams["setSize"];
		  }
		  if(isset($requestParams["showUnitTypeFields"])){
				$showUnitTypeFields = $requestParams["showUnitTypeFields"];
		  }
		  if(isset($requestParams["limitUnitTypeFields"])){
				$limitUnitTypeFields = $requestParams["limitUnitTypeFields"];
		  }
		  if(isset($requestParams["showSourceFields"])){
				$showSourceFields = $requestParams["showSourceFields"];
		  }
		  if(isset($requestParams["showLDSourceValues"])){
				$showLDSourceValues = $requestParams["showLDSourceValues"];
		  }
		  if(isset($requestParams["showBPdates"])){
				$showBPdates = $requestParams["showBPdates"];
		  }
		  if(isset($requestParams["showBCEdates"])){
				$showBCEdates = $requestParams["showBCEdates"];
		  }
		  if(isset($requestParams["tableName"])){
				if(strlen($requestParams["tableName"])>1){
					 $tableName = $requestParams["tableName"];
				}
		  }
		  
		  if(isset($_REQUEST["sourceID"])){
				$sourceIDarray = $requestParams["sourceID"];
		  }
		  else{
				$sourceIDarray = false;
		  }
		  
		  if(isset($_REQUEST["projectUUID"])){
				$projArray = $requestParams["projectUUID"];
		  }
		  else{
				return $this->_forward('index');
		  }
		  
		  if($showBPdates){
				$showBCEdates = false;
		  }
		  else{
				$showBCEdates = true;
		  }
		  
		  Zend_Loader::loadClass('TabOut_Table');
		 
		  $tableObj = new TabOut_Table;
		  $tableObj->showTable = false;
		  $tableObj->setSize = $setSize;
		  $tableObj->page = $page;
		  $tableObj->limitingProjArray = $projArray;
		  $tableObj->limitingSourceTabArray = $sourceIDarray; //if false, do not limit by source tables
		  $tableObj->showUnitTypeFields = $showUnitTypeFields;
		  $tableObj->limitUnitTypeFields = $limitUnitTypeFields;
		  $tableObj->showSourceFields = $showSourceFields;
		  $tableObj->showBP = $showBPdates;
		  $tableObj->showBCE = $showBCEdates;
		  $tableObj->showLDSourceValues = true;
		  $tableObj->tableID = false;
		  $tableObj->DBtableID = $tableName;
		  
		  //$tableObj->recordQueries = true;
		  $tableObj->sortForSourceVars = " var_tab.sort_order, sCount DESC, var_tab.var_label ";
		  $linkedFields = $tableObj->getLinkedVariables($classUUID);
		  $tableArray = $tableObj->makeTableArray($classUUID);
		  
		  $this->_helper->viewRenderer->setNoRender();
		  $location = "../table/publish?table=".$tableName;
		  header("Location: ".$location);
		  echo "Table created. ";
	 }
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 //get form to create metadata and publish a table
	 function publishAction(){
		 
		  Zend_Loader::loadClass('TabOut_TablePublish');
		  Zend_Loader::loadClass('TabOut_TableFiles');
		  Zend_Loader::loadClass('dbXML_dbLinks'); //needed for dublin core relations
		  
		  $tablePubObj = new TabOut_TablePublish;
		  $requestParams =  $this->_request->getParams();
		  if(isset($requestParams['table'])){
			 $tablePubObj->penelopeTabID = $requestParams['table'];
		  }
		  else{
				return $this->render('table-index');
		  }
		  
		  $tablePubObj->getMakeMetadata(); // get saved metadata, or auto-generate to begin metadata creation
		  if(isset($requestParams["format"])){
				$this->_helper->viewRenderer->setNoRender();
				
				header('Content-Type: application/json; charset=utf8');
				
				if($requestParams["format"] == "jsonld"){
					 $tablePubObj->generateJSON_LD();
					 echo Zend_Json::encode($tablePubObj->JSON_LD);
				}
				else{
					 echo Zend_Json::encode($tablePubObj->metadata);
				}
		  }
		  else{
				$this->view->tablePubObj = $tablePubObj;
		  }
	 }
	 
	 
	 //create and save CSV versions of the table
	 function tableFilesAction(){
		  
		  Zend_Loader::loadClass('TabOut_TableFiles');
		  Zend_Loader::loadClass('TabOut_TablePublish');
		  
		  $this->_helper->viewRenderer->setNoRender();
		  
		  $requestParams =  $this->_request->getParams();
		  $tableFiles = new TabOut_TableFiles;
		  
		  
		  if(isset($requestParams['table'])){
			 $tableFiles->penelopeTabID = $requestParams['table'];
		  }
		  else{
				return $this->render('table-index');
		  }
		  
		  $csv = $tableFiles->makeSaveFiles();
		  header("Content-type: application/octet-stream");
		  header("Content-Disposition: attachment; filename=\"OpenContext_data.csv\"");
		  echo $csv;
		  
	 }
	 
	 
	 
	 //update metadata for a published table
	 function autoPersonsProjectsAction(){
		  
		  Zend_Loader::loadClass('TabOut_TablePublish');
		  Zend_Loader::loadClass('TabOut_TableFiles');
		  Zend_Loader::loadClass('dbXML_dbLinks'); //needed for dublin core relations
		  
		  $tablePubObj = new TabOut_TablePublish;
		  $requestParams =  $this->_request->getParams();
		  
		  if(isset($requestParams['table'])){
			 $tablePubObj->penelopeTabID = $requestParams['table'];
			 $tablePubObj->requestParams = $requestParams;
		  }
		  else{
				return $this->render('index');
		  }
		  
		  $tablePubObj->autoMetadata();
		  $this->_helper->viewRenderer->setNoRender();
		  $location = "../table/publish?table=".$tablePubObj->penelopeTabID;
		  header("Location: ".$location);
		  echo "Persons, projects metadata updated with results of autogeneration. ";
	 }
	 
	 
	 //remove person from metadata
	 function removePersonAction(){
		  
		  Zend_Loader::loadClass('TabOut_TablePublish');
		  Zend_Loader::loadClass('TabOut_TableFiles');
		  Zend_Loader::loadClass('dbXML_dbLinks'); //needed for dublin core relations
		  
		  $tablePubObj = new TabOut_TablePublish;
		  $requestParams =  $this->_request->getParams();
		  
		  if(isset($requestParams['table'])){
			 $tablePubObj->penelopeTabID = $requestParams['table'];
			 $tablePubObj->requestParams = $requestParams;
		  }
		  else{
				return $this->render('index');
		  }
		  
		  $tablePubObj->removePerson();
		  $this->_helper->viewRenderer->setNoRender();
		  $location = "../table/publish?table=".$tablePubObj->penelopeTabID;
		  header("Location: ".$location);
		  echo "Person removal attempt. ";
	 }
	 
	 //remove person from metadata
	 function consolidatePersonsAction(){
		  
		  Zend_Loader::loadClass('TabOut_TablePublish');
		  Zend_Loader::loadClass('TabOut_TableFiles');
		  Zend_Loader::loadClass('dbXML_dbLinks'); //needed for dublin core relations
		  
		  $tablePubObj = new TabOut_TablePublish;
		  $requestParams =  $this->_request->getParams();
		  
		  if(isset($requestParams['table'])){
			 $tablePubObj->penelopeTabID = $requestParams['table'];
			 $tablePubObj->requestParams = $requestParams;
		  }
		  else{
				return $this->render('index');
		  }
		  
		  $tablePubObj->consolidatePersons();
		  $this->_helper->viewRenderer->setNoRender();
		  $location = "../table/publish?table=".$tablePubObj->penelopeTabID;
		  header("Location: ".$location);
		  echo "Person removal attempt. ";
	 }
	 
	 //add person to metadata
	 function addPersonAction(){
		  
		  Zend_Loader::loadClass('TabOut_TablePublish');
		  Zend_Loader::loadClass('TabOut_TableFiles');
		  Zend_Loader::loadClass('dbXML_dbLinks'); //needed for dublin core relations
		  Zend_Loader::loadClass('dbXML_dbPerson'); //needed for looking up persons
		  
		  $tablePubObj = new TabOut_TablePublish;
		  $requestParams =  $this->_request->getParams();
		  
		  if(isset($requestParams['table'])){
			 $tablePubObj->penelopeTabID = $requestParams['table'];
			 $tablePubObj->requestParams = $requestParams;
		  }
		  else{
				return $this->render('index');
		  }
		  
		  $tablePubObj->addPerson();
		  $this->_helper->viewRenderer->setNoRender();
		  $location = "../table/publish?table=".$tablePubObj->penelopeTabID;
		  header("Location: ".$location);
		  echo "Person adding attempt. ";
		  //echo print_r($tablePubObj);
	 }
	 
	 
	 
	 
	 //update metadata for a published table
	 function postMetadataAction(){
		  
		  Zend_Loader::loadClass('TabOut_TablePublish');
		  Zend_Loader::loadClass('TabOut_TableFiles');
		  Zend_Loader::loadClass('dbXML_dbLinks'); //needed for dublin core relations
		  
		  $tablePubObj = new TabOut_TablePublish;
		  $requestParams =  $this->_request->getParams();
		  
		  if(isset($requestParams['table'])){
			 $tablePubObj->penelopeTabID = $requestParams['table'];
			 $tablePubObj->requestParams = $requestParams;
		  }
		  else{
				return $this->render('table-index');
		  }
		  
		  $tablePubObj->addUpdateMetadata(); //update the table metadata based on posted parameters
		  $this->_helper->viewRenderer->setNoRender();
		  $location = "../table/publish?table=".$tablePubObj->penelopeTabID;
		  header("Location: ".$location);
		  echo "Metadata updated based on posted values. ";
		  
	 }
	 
	 
	  //update metadata for a published table
	 function publishTableAction(){
		  
		  Zend_Loader::loadClass('TabOut_TablePublish');
		  Zend_Loader::loadClass('TabOut_TableFiles');
		  Zend_Loader::loadClass('dbXML_dbLinks'); //needed for dublin core relations
		  
		  $destinationURI = "http://opencontext/publish/table-publish";
		  
		  $tablePubObj = new TabOut_TablePublish;
		  $requestParams =  $this->_request->getParams();
		  
		  if(isset($requestParams['pubURI'])){
				$destinationURI = $requestParams['pubURI'];
		  }
		  
		  if(isset($requestParams['table'])){
				$tablePubObj->penelopeTabID = $requestParams['table'];
				$ok = $tablePubObj->getSavedMetadata(); // get saved metadata
		  }
		  elseif(isset($requestParams['uri'])){
				$ok = $tablePubObj->getSavedMetadataByURI($requestParams['uri']);
		  }
		  else{
				return $this->render('table-index');
		  }
		  
		  if($ok){
				$tablePubObj->generateJSON_LD();
				$output = $tablePubObj->publishTableJSON($destinationURI);
		  }
		  else{
				header('HTTP/1.1 400 Bad Request');
		  }
		  
		  $this->_helper->viewRenderer->setNoRender();
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_Json::encode($output);
	 }
	 
	 
	 
	 
	 
	 //get old table metadata to update
	  //update metadata for a published table
	 function oldMetadataAction(){
		  
		  Zend_Loader::loadClass('TabOut_UpdateOld');
		  Zend_Loader::loadClass('TabOut_TablePublish');
		  Zend_Loader::loadClass('TabOut_TableFiles');
		  Zend_Loader::loadClass('dbXML_dbLinks'); //needed for dublin core relations
		  
		  $tableOldObj = new TabOut_UpdateOld;
		  $requestParams =  $this->_request->getParams();
		  
		  if(isset($requestParams['uri'])){
			 $tableOldObj->oldURI = $requestParams['uri'];
			 $tableOldObj->requestParams = $requestParams;
		  }
		  else{
				return $this->render('index');
		  }
		  
		  $tableOldObj->getParseJSON();
		  $tableOldObj->processOldData();
		  
		  $requestParams["format"] = "json";
		  if(isset($requestParams["format"])){
				$this->_helper->viewRenderer->setNoRender();
				header('Content-Type: application/json; charset=utf8');
				$output = array("old" => $tableOldObj->oldTableData,
									 "new" => $tableOldObj->newMetadata);
				echo Zend_Json::encode($output);
		  }
		  else{
				$this->view->tableOldObj = $tableOldObj;
		  }
		  
	 }
	 
	 //get all table - record associations {
	  function allOldTableRecordsAction(){
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('TabOut_UpdateOld');
		  Zend_Loader::loadClass('TabOut_OldTables');
		  Zend_Loader::loadClass('TabOut_TablePublish');
		  Zend_Loader::loadClass('TabOut_TableFiles');
		  
		  if(isset($requestParams['uri'])){
				$startURI = $requestParams['uri'];
		  }
		  else{
				$startURI = "http://opencontext/table-browse/.json";
		  }
		  
		  $oldTables = new TabOut_OldTables;
		  $oldTables->URIstartTableJSON = $startURI;
		  $oldTables->saveAllTableRecordAssociations();
		  
		  
		  header('Content-Type: application/json; charset=utf8');
		  $output = array($oldTables->doneList);
		  echo Zend_Json::encode($output);
		  
	 }
	 
	 
	 //deletes a table
	  function deleteTableAction(){
		  $this->_helper->viewRenderer->setNoRender();
		  
		  $requestParams =  $this->_request->getParams();
		  Zend_Loader::loadClass('TabOut_Tables');
		  $tablePubObj = new TabOut_Tables;
		  
		  if(isset($requestParams['table'])){
				$tablePubObj->deleteTable($requestParams['table']);
		  }
		  
		  $location = "../table/";
		  header("Location: ".$location);
	 }
	 
	 
	 
	 
	 //get the list of output tables
	 function indexAction(){
		  
		  Zend_Loader::loadClass('TabOut_Tables');
		  $tablePubObj = new TabOut_Tables;
		  $tablePubObj->getTables();
		  $this->view->tablePubObj = $tablePubObj;
		  
	 }
	 
}//end class