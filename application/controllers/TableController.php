<?php

/** Zend_Controller_Action */
//require_once 'Zend/Controller/Action.php';
//require_once 'OpenContext/Controller/Action/Helper/SolrAccess.php';

//error_reporting(E_ALL ^ E_NOTICE);
// increase the memory limit
ini_set("memory_limit", "4024M");
// set maximum execution time to no limit
ini_set("max_execution_time", "0");

class TableController extends Zend_Controller_Action {
    
    function init()
    {  
        require_once 'App/Util/GenericFunctions.php';
    }
    
	 
	 
	  //load up old space data from XML documents
	 function classAction(){
		          
        
        //class of items to export in a table
        $classUUID = "881CEDA3-C445-4C9C-4D4B-634BD2963892"; //animal bone
		  //$classUUID = "A2017643-0086-4D98-4932-E4AD3884E99D"; //pottery
		  
		  $page = 1;
		  $setSize = 20000;
		  
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
		  $limitingProjArray = array("731B0670-CE2A-414A-8EF6-9C050A1C60F5", "8894EEC0-DC96-4304-1EFC-4572FD91717A");
		 
		 
		  $tableObj = new TabOut_Table;
		  $tableObj->setSize = $setSize;
		  $tableObj->page = $page;
		  $tableObj->limitingProjArray = $limitingProjArray;
		  $tableObj->showSourceFields = true;
		  $tableObj->showBP = true;
		  $tableObj->showBCE = false;
		  $tableObj->showLDSourceValues = false;
		  $tableObj->tableID = "levent";
		  //$tableObj->recordQueries = true;
		  $tableObj->sortForSourceVars = " var_tab.sort_order, sCount DESC, var_tab.var_label ";
		  $linkedFields = $tableObj->getLinkedVariables($classUUID);
		  $tableArray = $tableObj->makeTableArray($classUUID);
		  //$tableArray = $tableObj->queries;
		  
		  if($format == "json"){
				$this->_helper->viewRenderer->setNoRender();
				$output = array("tableData" => $tableArray, "linkedFields" => $linkedFields);
				header('Content-Type: application/json; charset=utf8');
				echo Zend_Json::encode($output);
		  }
		  else{
				$this->view->tableArray = $tableArray;
		  }
	 }
	 

	 //load up old space data from XML documents
	 function classSaveAction(){
		          
        
        //class of items to export in a table
        $classUUID = "881CEDA3-C445-4C9C-4D4B-634BD2963892"; //animal bone
		  //$classUUID = "A2017643-0086-4D98-4932-E4AD3884E99D"; //pottery
		  
		  $page = 1;
		  $setSize = 300000;
		  $setSize = 30;
		  
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
		  $limitingProjArray = array("1B426F7C-99EC-4322-4069-E8DBD927CCF1");
		 
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
									"02594C48-7497-40D7-11AE-AB942DC513B8" => "Çatalhöyük Area TP"
									);
		 
		 
		  foreach($projArray as $key => $name){
				$limitingProjArray[] = $key;
		  }
		 
		 
		  $tableObj = new TabOut_Table;
		  $tableObj->setSize = $setSize;
		  $tableObj->page = $page;
		  $tableObj->limitingProjArray = $limitingProjArray;
		  $tableObj->showUnitTypeFields = true;
		  $tableObj->limitWithUnitTypeFields = true;
		  $tableObj->showSourceFields = false;
		  $tableObj->showBP = false;
		  $tableObj->showBCE = true;
		  $tableObj->showLDSourceValues = true;
		  $tableObj->tableID = "measurement-use";
		  //$tableObj->recordQueries = true;
		  $tableObj->sortForSourceVars = " var_tab.sort_order, sCount DESC, var_tab.var_label ";
		  $linkedFields = $tableObj->getLinkedVariables($classUUID);
		  $tableArray = $tableObj->makeTableArray($classUUID);
		  //$tableArray = $tableObj->queries;
		  
		  if($format == "json"){
				$this->_helper->viewRenderer->setNoRender();
				$output = array("tableData" => $tableArray, "linkedFields" => $linkedFields);
				header('Content-Type: application/json; charset=utf8');
				echo Zend_Json::encode($output);
		  }
		  elseif($format == "save"){
				$this->_helper->viewRenderer->setNoRender();
				$output = $tableObj->makeHTML($tableArray);
				$saveOK = $tableObj->saveFile("public/xml/".$tableObj->tableID.".html", $output);
				header('Content-Type: application/json; charset=utf8');
				echo Zend_Json::encode(array("saveOK" => $saveOK,
											  "rowCount" => count($tableArray),
											  "linkedFields" => $linkedFields)
											  );
		  }
		  elseif($format == "excel"){
				$this->_helper->viewRenderer->setNoRender();
				$tableObj->ExcelSanitize = true;
				$output = $tableObj->makeHTML($tableArray);
				$saveOK = $tableObj->saveFile("public/xml/".$tableObj->tableID."-excel.html", $output);
				header('Content-Type: application/json; charset=utf8');
				echo Zend_Json::encode(array("saveOK" => $saveOK,
											  "rowCount" => count($tableArray),
											  "linkedFields" => $linkedFields)
											  );
		  }
		  else{
				$this->view->tableArray = $tableArray;
		  }
	 }



	 
	 //save all the data to a table
	 // penelope.oc/table/class-db-save-all
	 
	 function classDbSaveAllAction(){
		          
        
        //class of items to export in a table
        //$classUUID = "881CEDA3-C445-4C9C-4D4B-634BD2963892"; //animal bone
		  $classUUID = "A2017643-0086-4D98-4932-E4AD3884E99D"; //pottery
		  
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
		 
		  $projArray = array("4B16F48E-6F5D-41E0-F568-FCE64BE6D3FA" => 'Stonewares'
									);
		 
		  foreach($projArray as $key => $name){
				$limitingProjArray[] = $key;
		  }
		 
		  $tableObj = new TabOut_Table;
		  $tableObj->setSize = $setSize;
		  $tableObj->page = $page;
		  $tableObj->limitingProjArray = $limitingProjArray;
		  $tableObj->showUnitTypeFields = false;
		  $tableObj->limitUnitTypeFields = false;
		  $tableObj->showSourceFields = true;
		  $tableObj->showBP = false;
		  $tableObj->showBCE = true;
		  $tableObj->showLDSourceValues = true;
		  $tableObj->tableID = false;
		  $tableObj->DBtableID = "z_ex_grave";
		  
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
	  
	 function classDbSaveUnitTypeAction(){
		          
        
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
		 
		  $tableObj = new TabOut_Table;
		  $tableObj->setSize = $setSize;
		  $tableObj->page = $page;
		  $tableObj->limitingProjArray = $limitingProjArray;
		  $tableObj->showUnitTypeFields = true;
		  $tableObj->limitUnitTypeFields = true;
		  $tableObj->showSourceFields = false;
		  $tableObj->showBP = false;
		  $tableObj->showBCE = true;
		  $tableObj->showLDSourceValues = true;
		  $tableObj->tableID = false;
		  $tableObj->DBtableID = "z_ex_measurements_f";
		  
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
	 

	 
	 
	  function classDbSaveLdAction(){
		          
        
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
				'75CF7E0D-BC0F-4DB7-857C-27E438C6B8FE', 
				'CD08D2A0-17B5-473E-5AEB-DDEFCE220166', 
				'8370AFD9-96BB-4860-0DBE-8C0A756C1A09', 
				'EF963E0C-76A8-4A4C-BAE2-0DED1826829B', 
				'119E12B9-D4E9-4983-7C9D-4C616B1BCDF8', 
				'826BA030-82B5-423C-2B6F-CD2175EA51B4', 
				'B3402259-F484-41C3-B753-E05284727005', 
				'8F012F99-7BAB-41D2-4CC0-51F3A2E75B3A', 
				'758A86A1-C622-4777-8F6C-2E3BA7EAEED2', 
				'35A38FD5-9AC1-40EC-BE15-556CA9A57A55', 
				'88A50194-E7E5-448E-3A87-0C4E327CDF63', 
				'E4FEC21E-3F7B-4673-6515-1F540CC72FC8', 
				'E3E19CF8-BC45-4C16-0295-16197C506411', 
				'7D20C41E-7C66-4C71-EC06-101C6438C121', 
				'D3270852-C698-4063-510F-64C06595704B', 
				'0EA6C2B7-2B47-4BC5-A4F4-C2D7F1066500', 
				'294FD05A-B388-403A-F37F-45E2569E8DB1', 
				'E3257157-06A5-4008-3033-DA50F88BC50E', 
				'0B5D081A-3DE8-4631-FCAE-6016A0A5E6B9', 
				'C89496C7-CD9D-4E57-560A-F4E197CF6FCE', 
				'7F199E15-A159-4117-BE06-C7621E970151', 
				'7C2574BC-15A4-441D-F861-B92CB1B01495', 
				'A341EBD2-EC37-49E3-4DD3-8C98F2391537', 
				'D6E3B1F4-D5CF-4F0F-BD50-AFE39C83D081', 
				'EA7718AA-10A4-48C5-58D7-56E2133D0C7E', 
				'A9A6CA3E-838B-461B-C3CC-90EDD2B8347A', 
				'38C3E8C8-4A37-4496-AF41-C17FBB9D1A8B', 
				'E236C2AB-1876-44A2-F0AC-3360DB64D55E', 
				'A01FFBEA-CA8A-4403-C7EA-E06893230A3F', 
				'0DB6871E-72B2-49E6-34E9-E798996FDA84', 
				'7BAACF8D-1D61-427E-F31C-4CC663BD94AB', 
				'3D76B1CA-9942-431B-415D-F1982A80DA75', 
				'80E79EF5-92B4-4F57-B6F7-5BD4753778FE', 
				'5D0B1B4B-1A1E-402B-39E0-F769621F5C82', 
				'EBE352EF-AFB4-4A29-C829-94DFDCC54465', 
				'36932E06-3D82-4B63-2ED0-637B6A59FDF2', 
				'08D7C9EA-6DA7-4A5F-82FD-5CEC10EAF0E3', 
				'E03FA554-3522-4FE9-CCDB-0617B8EBC9FE', 
				'92CBFB8C-E03C-4931-12BB-1292E3CB140E', 
				'002DB99C-0B06-4CA2-AAE9-A790FBFF2F74', 
				'ED1CB30F-7972-4CD5-0495-4B8BA7C61D49', 
				'B0879166-96BE-47F4-A908-323DA9F0507B', 
				'235DC1A6-7764-42AA-BE39-ED7B2835B073', 
				'7566796D-6D4A-4C3E-C8F0-D9C5F3DBEC2B', 
				'88A79DFC-5CEB-436F-B4C5-5FFF20F27DEE', 
				'E229E8B3-4417-405F-46D9-3261A3C3E75A', 
				'7AC4F814-C49E-4F20-4C91-629FE3C1D34C', 
				'44F190A3-9013-4F5A-1F69-A3B60007502D', 
				'4E7F0A0B-68F2-4E38-8B33-3788653363B7', 
				'439DDE92-17A8-479C-6AD6-3C500A4F578F', 
				'CA54F470-4320-48AF-6422-FB59FC4F2313', 
				'F6969D83-ABCF-4380-3870-946C051214AC', 
				'6B843810-8EB4-478B-553F-A6A21F1A9028', 
				'71DD0327-DA6A-4579-98E7-73430B2EA871', 
				'DDF9C149-8ABB-4904-7451-B557D2A4CCA6', 
				'1C44984B-AA7B-424D-5E99-24D641520D8E', 
				'13BA464D-CCCE-492E-6043-6302E559EC4D', 
				'9D92C1E6-FFBE-428F-D68A-D61A058E76F8', 
				'B3586BB6-EF97-44A2-EBCD-98628D35CFC6', 
				'A35BB892-F6B0-4509-3948-2ED9D8216B9F', 
				'3F5C61FF-6A93-41A8-67F5-1FA19B1F763C', 
				'CF199077-9035-4374-5CA3-E52EAD378F9D', 
				'AAD0C75C-854E-499A-BC5C-9E57B303C89C', 
				'CB4C5083-1F85-4452-7E88-03D584AA5565', 
				'38189574-AD03-4EEF-2862-344815953E6D', 
				'98EF600A-DC1E-43E8-1FB8-41EDCD965691', 
				'C14E093B-184E-4829-A2DB-19B3752ECA37', 
				'785822A9-C82B-4881-5446-0731B93DD920', 
				'9790F526-07CD-41AE-1E78-036895B25A9E', 
				'3235E4DE-7340-43AA-8BF3-CBB03526355A', 
				'D0112624-041F-4A58-9CF2-167DEB500CDD', 
				'276B45C9-09F5-417B-E98A-D2A88A7A16CD', 
				'38708EAD-EC3E-41D2-6844-43A31083E06C', 
				'C04F693F-3751-4962-5656-4EDA791C88DD', 
				'0E41B05A-C182-4408-A448-7F5FDC4EB705', 
				'DA5E9601-CB2A-412B-26E1-0A8AC2B2A910', 
				'F3D366FD-1C3A-4904-CF83-87C0FA64A604', 
				'4E5462C1-3770-43C3-C218-604BE0A0C64A', 
				'2F98F990-7A7A-44A2-ADD2-62250D8761C8', 
				'16_DT_Var', 
				'23_DT_Var', 
				'846830BD-49A6-4E03-6AD6-CF8C1D8B28AB', 
				'13AA05A5-3E0C-472A-1051-66981C511A6E', 
				'0AE0BF8D-7345-4881-2E00-42EA9DF9953A', 
				'3F21125D-FCA8-43D6-CBC2-C73A77D9A0B8', 
				'AC9C2D22-B7C1-4D13-63B6-D9D4C340EC72', 
				'413802AA-68DC-4F35-C6B5-9188A0C25F08', 
				'F9CA3F20-F82B-4C15-CE33-6FE06AA7AB15', 
				'B90A3EA8-1F7C-4334-9D90-7F486A846577', 
				'F30DCEC1-5014-4D9A-2DB3-A09DDCA66CDD', 
				'1FF4C642-7435-4267-3155-E311BEC3F369', 
				'32888569-D192-4390-51E6-2781BB31B28C', 
				'539E8E65-42B2-4BB9-CB0D-23D76FF47E26', 
				'733848FF-00D4-4906-3E3F-9BC516C2B329', 
				'121_DT_Var', 
				'122_DT_Var', 
				'123_DT_Var', 
				'124_DT_Var', 
				'EAF6F45C-7DBA-4747-6BAE-3D7CE0ED8DF2', 
				'BBD444E7-D5FA-4AB8-84B6-46EB8DD5F3DF', 
				'160_DT_Var', 
				'17_DT_Var', 
				'158_DT_Var', 
				'3FA4724A-7A1C-488C-0316-13011CF837B6', 
				'E9CD6357-B3AA-4D8E-CEF2-44541DB0B91B', 
				'E866F0CC-EB9F-4FD0-BD96-72513C5F537F', 
				'C3ACF0CD-E7ED-4ACE-0819-23E521CEDF4E', 
				'588FFA62-F284-4075-BE42-DB34C457F2D3', 
				'2DCCC619-E021-46B8-B649-0925131EAB11', 
				'9D4624E3-B303-4870-843D-C4EC5E32285C', 
				'766A41EB-2052-4F1A-32FD-3387065C981C', 
				'CFAFEE06-B090-4D88-E8FE-210D4A4D47D5', 
				'B456DA90-411E-470B-D5C0-B005B98ACDC7', 
				'D14E9A37-B791-447D-14D5-E5AF8A0396E5', 
				'9DA3B79A-F48F-4866-9281-B9E5CE28FFB9', 
				'DB3BC050-FA13-45EE-7C06-37D9CDC64678', 
				'282DA03B-B8BD-4427-9AE0-E8B6D63616CD', 
				'52554B1C-5309-4D5C-B232-1176B7D3844B', 
				'1B12B145-0327-4D06-2DDD-B059E95C94DE', 
				'E8D3171D-C041-41B6-AE2F-1A8C8556C335', 
				'FD02DB59-A9A8-48C1-7B1D-946B56BA67C8', 
				'94D58EC3-A874-4B3D-8E54-97C4913E2E80', 
				'39B94333-AFE9-4AB9-503D-E2C2532054B2', 
				'7729096E-10F3-41CA-3726-F63866D1285B', 
				'8E7D03D4-C5A5-47B1-7F4F-04B05FACCB72', 
				'D1D8CD2D-6D3B-4927-F51B-2679577EEB5F', 
				'82AEBCA5-240B-4C76-D214-2CC02E3DCBBD', 
				'68097BCA-BA86-46D7-78E5-D4DC45B3FA9D', 
				'8CB11175-24AC-4CCA-E5DD-B51CE055DC99', 
				'D114891B-E779-4F44-F054-1F940B5A2AD7', 
				'8BF49BC6-B3C9-4D58-D657-5261EDF11510', 
				'B8DD7610-A02D-468F-295E-87AF5D8D845D', 
				'6916E68F-79C8-427D-60CD-8539596E65A6', 
				'B90AB7CF-9FF4-4532-FEB6-9190129C8D70', 
				'7E63EBC9-6FB9-4551-2236-D3D07A9FA449', 
				'C1569B5E-A2DB-4B4E-065A-94AE6CF98815', 
				'BF0BB778-76DB-485F-F641-1000F635B91A', 
				'A49234A3-0060-428B-EFBB-A9615055A02A', 
				'333BB12C-CBB2-4A62-AB3D-7B723813C022', 
				'DE3A7E5A-0EFF-4EF9-6B3C-58E1ECEAE0F1', 
				'8D8614B9-19AE-4A70-15E6-B9EAE29094C0', 
				'3A92A5B3-64B9-4A07-2734-BCD66FA28EB0', 
				'BF2B8514-C0EC-4BCB-65B8-4A7D05B08233', 
				'D9DD69C4-4011-4D43-B413-109DB1A46A23', 
				'E9B8CEE0-B5B1-4CB9-C0A3-8CC175A5866B', 
				'99DC43D9-D05C-4E27-9481-C43C49C6E64B', 
				'02EF3EF2-A3B0-4088-1E6D-2288CEA68864', 
				'ABD5B6DD-359B-4925-17DF-45FA07C7384B', 
				'C4EA49BE-C356-466F-9B7A-0CF8D93A9293', 
				'E0F0E362-371D-49F8-D64D-A27C7EDBB211', 
				'0133DFFF-7C28-4324-95BC-07E5A61553A6', 
				'626CD22C-D5A5-43C9-F1F0-051824A4304F', 
				'656D38B6-427E-46DC-92F5-C5F5715D576A', 
				'36AA19BB-997E-4F4A-086C-FEE3FDDBB808', 
				'4C175060-C611-4C8D-162A-73BFEA5E5DB8', 
				'C22F9D2D-B052-49E2-4158-5A7532545A4F', 
				'CB93F03E-F8BC-4ECF-8F0F-D1F9DE2386EB', 
				'15057A0D-637C-45E5-162D-4C8B73792F32', 
				'FF6D5ACB-8CE1-45A0-27C2-8BD512046901', 
				'87C942CD-C265-4841-0067-BEC5392B531B', 
				'3FCA36BD-E30E-46C5-309B-730D347D8494', 
				'2FA7707A-2675-4658-A2C0-10DF3F29C68C', 
				'019D96EB-3814-40AC-09C8-21D8BD2B5673', 
				'B55BBFBD-9078-4794-F263-5350B7FE3588', 
				'0E5572CC-62F4-4C0D-022D-3A00E3F3E18B', 
				'9AF8E81B-3B55-419C-DB24-7047E97D2EC1', 
				'1B1E847A-CEC9-4372-BAA7-D4D4C14FF413', 
				'0A635B46-F29D-45EC-93ED-BAA1AB126C53', 
				'8D28A0A4-E4D0-43AD-157B-559AB78DB718', 
				'FC6C2045-7A11-47E5-D90D-BE969DC38B18', 
				'19D6A695-D4C0-4D59-EB27-9A9F94FA4E4E', 
				'27BA3B9C-D4AD-4427-CDED-6FF54F94C5E3', 
				'9B462BBD-76C2-48AF-73F1-6452AC3E01DD', 
				'DF047C57-5AF8-4292-EBBA-19ED1FC7714E', 
				'8AB73207-3C54-45A4-FA96-09E18361C013', 
				'AD18A96D-2ABE-4E9B-76F0-E7F991B1D226', 
				'4227975E-E43C-4440-6E8B-715361E21558', 
				'775AC7C2-EFA8-40AA-9BB7-AF03B0689A23', 
				'21F673F5-7FEF-4F6F-34AD-FF10E442CDC7', 
				'973D9B6A-48A6-41BE-F155-450549820095', 
				'1B6D2A5C-2E73-4E91-31C5-AB81E2DA7E67', 
				'CF1FBC21-CE18-432E-2F08-F974E3DEC509', 
				'C092CCB4-489A-44D2-1BE2-4149CFBAB8FB', 
				'8B36D5C7-30C7-4062-3F76-6C31A6F3408F', 
				'C75F7B00-A03B-4A23-2B6F-8EF0C549A805', 
				'3F3E125B-E7C4-45DA-880C-64ED2D081E3F', 
				'A10066AC-CCC8-4AAF-0BDE-70D2A2F12FB7', 
				'4F2EBF61-1C7C-4A22-DC85-8D1078814956', 
				'E7363214-D08F-4320-24D9-299DD67EEF25', 
				'29F67D7B-EA3C-472B-DB9C-62F68BF69BD0', 
				'D7919ED8-6707-4295-7271-6CDCD162FA1C', 
				'388EEF09-DD9E-427E-16CD-B6528C4BB06B', 
				'DC8E2DD7-476B-4E12-03AA-94171E49622A', 
				'CA82F530-CFD3-4EC1-B86C-4B0DB5AAF35B', 
				'F4174917-ABC1-46C6-0985-B4B6E4F5E641', 
				'33E9FB66-F8B8-451A-AD61-56FDE6D679B9', 
				'9EC38934-C43C-4D8D-43A7-CD37CD36EFDD', 
				'C68A31B6-DD6B-4D1F-754C-E097287A2250', 
				'143CB7F1-FD42-40C6-7F05-D937C0AEB965', 
				'1B26B53B-F73B-4CDA-EB19-4271D7D55867', 
				'FECB3532-A835-4450-CE4A-5D26245A4BD3', 
				'FB341F11-382E-442E-67EC-1B9DE69D93AE', 
				'E12ED40C-6910-41F8-014A-C758C8D11C37', 
				'48689ED2-EE80-4CC1-FB14-A645FAFEBCA6', 
				'CA6798A8-3EC2-47AD-918F-6FEC10958D21', 
				'75B10789-A265-4414-F99C-FC49BE989AA3', 
				'B3D554F9-1D5F-4018-0EBA-5E68F0BF313C', 
				'150C8D34-5FC6-4D77-7ED3-F0F6C4C1EE64', 
				'0222BB3C-AC5F-4987-8A3D-DA913FCBD851', 
				'90AD6DA6-DC61-4101-194F-2EBB7F7667B9', 
				'B5F982E4-DA7E-4F37-0314-4429D6073F5E', 
				'51F7AA98-ECDB-4F0E-576B-E1E9F224B437', 
				'F6AA83F5-A79E-45D0-35DA-4CC1230897DE', 
				'D3617CE2-78C9-4FB6-8059-342B5F396A25', 
				'65995C1C-20A9-463A-B413-88F453B6F141', 
				'1CCD7FD8-DD74-48DD-4254-EC543AC7C5C8', 
				'D8D26774-5E74-49A2-D61D-F1CAE38BFB02', 
				'0E67C7E0-1F49-4EAA-1710-924EEE238B15', 
				'A98B0A62-93A3-473B-4611-C17B008EC9A8', 
				'C777E887-6C6A-409B-40F1-6285E9741415', 
				'DE97DA3C-2618-42AB-CFE3-F0455D8C6CDE', 
				'F0ECC438-6F52-41AB-2131-8CB743E7AE96', 
				'D4B2418D-D71C-4373-66C4-390C362339C4', 
				'9112C90F-A5EC-4808-72FD-A994B14C21F0', 
				'3FF9AD1B-8C47-4724-81C5-63429623963C', 
				'9B30D62C-C6AC-46AC-5F03-F805B5D47D71', 
				'3C9D89B1-F84C-4C84-314B-710C3E08D1E7', 
				'A9B70AD2-274D-4221-4514-5C9CB49618ED', 
				'9180C1F2-AA02-48A4-F4A0-8AB754C2C197', 
				'84991745-542A-4248-FE51-BBBE3E9EA3BB', 
				'FB853E29-01C7-4BBE-3413-74FBC763D112', 
				'04AEA888-2B87-4338-7CF8-F2E834633045', 
				'F1A4BB5A-1D82-4E5F-1BD0-53710E5F63CA', 
				'E3ECF520-4DFA-486F-77D3-CCC39AB60772', 
				'86090C36-22D2-4E6B-CCBC-007A8351A6D6', 
				'0E156F43-BFAF-48DB-7171-D82DA63B7203', 
				'A07683D6-578A-4369-E577-5F678EA80AB9', 
				'B531899F-EE15-4ED3-4BAA-BEAC45C20776', 
				'F50E8D1A-C048-4576-09C0-5DD7E2D0E21E', 
				'BCA67560-B42D-4734-F2C8-44233A996482', 
				'5E1BDCF6-1759-4672-77C7-420B3FA24E5C', 
				'E369DB66-BA23-4C70-1178-0F5D3E775280', 
				'59AD61A4-E67D-49D8-03EB-03EA7F4D9052', 
				'3FCF4A1D-44E9-4EBD-F08A-809700592CDE', 
				'3212E561-6467-4FFE-0A45-39EA21F2F9C6', 
				'8FC19EB6-DC65-4C8D-9A7D-00D296506D09', 
				'07DEA586-4AFC-485F-0E4B-06A2DC4FC0EE', 
				'E14A3A11-6422-445D-5795-258D33E74003', 
				'A95CF0CA-78E3-439C-452F-2EF302B5DDE5', 
				'B3AF3958-2C7F-4BA4-4A78-F73FBA36A758', 
				'BDDC29AD-C2C5-428B-485E-2625C0BEC85B', 
				'B2544AB2-F668-4177-A8D2-CB34799EE1B6', 
				'52D8B039-3955-4D64-B78D-D1349897EE97', 
				'0E7329F4-3596-4C53-71FA-27FFB9B0BEC7', 
				'F2C083B1-C4B8-4BB8-B9DF-B9A9C12251E5', 
				'DBC162ED-52C3-4635-BCD9-7EC219A58E59', 
				'313CF6FE-7682-4190-32B7-C9E3733F550D', 
				'9068ACE9-20ED-4C00-0276-DC67EB46F700', 
				'EBCE57FC-EE32-44A7-AC6E-BB44A25761D0', 
				'BCF9A3F4-C6C2-4DA2-9DFB-9EBDE2F704D7', 
				'CFA2DDC2-2E96-4E3F-7E09-4295ED9D310F', 
				'8E67B60A-C3D1-4669-4461-9C1A66EF5BC6', 
				'DF628892-1D5B-4E7E-F035-C1E9058202B9', 
				'F8771EC9-664C-4E10-181D-DE0E79535D1D', 
				'B7A3A9D0-9B10-46FC-E75E-5A63F4672016', 
				'A94972D1-5C93-4373-F7B1-6DFA8D509B03', 
				'65D0093F-2AFC-43EF-1F22-B35F0DC121E0', 
				'11953AE9-B3EB-49DD-06EA-F7726F45E7BB', 
				'B0B548CF-7C72-4E31-4F9D-545B93E201D6', 
				'4E9CB046-0722-48DA-6EF7-9C8D09F3A8C5', 
				'06C3A2B8-E7E9-4995-8EF8-613F6CA084DA', 
				'785AF11C-BB7F-4C29-0F4E-B0190CF46B7B', 
				'5BC681F8-45C9-43A0-7F00-F805429E8A40', 
				'D088445E-FBBB-4173-A4D3-9434A8C672A1', 
				'D773488D-B2F9-4CFD-D8A3-A48A1B9AE6C8', 
				'4D1EF96C-4F62-421F-EBE9-D0D76C7425BB', 
				'BB5704D2-D8F7-4659-21B8-BCE28F350576', 
				'341082BE-B6AE-4FDB-9C5E-1047D2243E3A', 
				'A5DD786E-7444-44A5-12F7-BA327F600504', 
				'4BB4D0DB-9B50-4599-20EB-2B3BBA42C12B', 
				'D31182A6-E48E-4F13-D040-0633604AD814', 
				'F30F6C13-1DBD-4BF9-7FD0-8B5916C5BA89', 
				'393F6E53-3D7F-443A-CAD0-672EB3124C54', 
				'B2AF7A21-2117-49EC-B1CE-05C33BA00A1D', 
				'B1211194-B96E-4444-6CE6-F8B72E3A1D3A', 
				'7EEAC09B-B0CD-4865-01F5-7E3EDB3968BE', 
				'3227ACE5-E128-42F5-CF02-10A34ECE4D03', 
				'3FBC3511-30A2-4C8A-6BDA-10D6C0F5603C', 
				'72F04D4D-F3DE-451B-AA07-E9A045B254DC', 
				'D60F3E65-A732-4676-2293-CBCA4CE5D47E', 
				'BF9A9078-A6AE-4840-F8AB-32966934892B', 
				'6FA7D726-DBD3-4C2C-664E-9C521F17CF1A', 
				'FFC37731-D1FF-476D-13C3-041C417ADDA2', 
				'92D52507-29F5-48DC-ABD9-A93CE3BE1A47', 
				'F3713D81-77FB-4319-AA0F-009BB96C8CC3', 
				'359476BF-CB8F-48F7-E90B-802480C48832', 
				'BB57DB1E-5BD6-4B63-911D-62BD1411A1B6', 
				'5AB50A02-2AE6-41D6-4EF6-5C7F9BC01A39', 
				'3923D6A1-2798-4C50-7AC7-FD423715BDB4', 
				'CB0B2A3E-D9DF-4934-42E3-F9AAFD455E72', 
				'5496FBB4-C842-4240-AB34-B10379C8D6FD', 
				'8D95945F-933B-4657-EBDD-1D2CDB11B865', 
				'DD885DEB-8478-4A0A-D9CC-3A50B2A410B2', 
				'3922601E-234B-45F5-DE63-C092FA9ABCD8', 
				'10761877-DE40-4E3A-1AA0-C5046785ADF7', 
				'F3CAD689-4997-49D2-A3B4-8BD8E8346B6B', 
				'4BBCD0B2-C5F8-46B8-ABE6-2E904D4F2B84', 
				'1331B965-BE90-493F-D413-09CF4D2C5A13', 
				'A7AC6438-2416-401A-F38A-D89343347BB5', 
				'E45E1C90-91EC-42E1-7983-BFDE04D6EA43', 
				'6A02AE7B-B85B-4D35-E424-AC0F9D1C61A8', 
				'D133A946-9DAC-4EA8-3E91-C0DFFB87521D', 
				'9E7C3D85-7761-4647-87E1-1E12DD8914C5', 
				'E7CA4EC3-AB3F-49A9-E85B-A51676A7C98B', 
				'BDAC0E7D-C39B-491B-0B8B-36B90819FF7A', 
				'D596B2F6-807D-496F-EE67-CFBD975D43C4', 
				'9122C711-2373-4E70-269A-C182CBDD6917', 
				'CEEB64A8-B415-43CC-80BF-F0C71785A5B8', 
				'5418E86A-F5F4-4B21-FDA4-1C33B5D90BCD', 
				'51DA3E74-564B-462A-0ADD-11975F4B2F3A', 
				'59EC172E-F893-4C03-B6E6-4783DF7511BC', 
				'EAB01691-D57B-4056-0EB0-1D937D4AC0A3', 
				'AAB9F613-6899-4EC4-FC16-1B8960602CFF', 
				'B45733F0-71AE-4227-8791-8A8C466C661D', 
				'D0B9D131-708B-4D66-5512-ABF73AD0113F', 
				'9B9F997A-AB5E-4C0B-D6F8-4B9D8351AB4A', 
				'E6E25901-3132-4517-6BF1-00311E483860', 
				'64B8DDF0-B21C-4502-AAD2-CEBA50EFF2F1', 
				'89CD4C8F-1F1C-4587-1095-E54258C99A41', 
				'1461D302-BF50-41B6-5550-8BA8DC9FABB4', 
				'7EC78F1C-3E90-4977-2AD5-CE60B3BB2262', 
				'F7A95FB9-20D9-40EA-5029-663FC6F2B1BD', 
				'5F12A304-A483-457D-BDB3-93031BAD506D', 
				'E1FEEE1A-5F92-4341-65CB-0E3E53803AB0', 
				'8A62EC03-5D97-45E4-7B33-BE24E97C0191', 
				'FE16D4E0-87B6-4BFA-F41D-0088857E9F7A', 
				'E2666F85-DFD0-4D4F-4DEF-07327BC84381', 
				'E74BD6EC-5153-4874-A92A-1D514B9F61EC', 
				'5B205CAB-629D-4E11-8D22-7DBD47E611C8', 
				'8715ABBD-03F3-4699-7100-BF43864AFAA3', 
				'FB31C3E2-3280-4E1D-6EF5-61C559CE7D7C', 
				'49028DA0-5399-4CEF-8A85-238011D3DCBD', 
				'A8CF87C3-D625-444E-4049-0CC53B6EDAE6', 
				'63CADAEF-B86F-4C49-9DE1-CD27D9D02721', 
				'DDCD1123-E2D1-42A3-7800-9A9226327571', 
				'75C83B52-BF9C-432B-575C-5FE08B302155', 
				'A131581B-E47D-45DC-568F-BFC3C17C528D', 
				'13F05EC1-AA25-46AB-1691-170545624447', 
				'2C3A6206-3B94-4F1B-361B-2B317FAAC234', 
				'66B3CDE4-18C3-4476-BB89-DA79D0DD0B8B', 
				'004ADEC8-4230-496B-A624-6C648C1D9CF8', 
				'3FC81D94-9B67-42FD-5FDC-60FE37B1FA4D', 
				'F3B33754-8FF2-4E6A-3A4F-362D2B369F56', 
				'8F997F64-602D-4DE6-6EC1-69860DA6E054', 
				'D4919F03-1970-4665-FD5F-106A1A72801D', 
				'EFBD1970-D515-467C-2903-B763816D319D', 
				'0E0FD8B0-A434-4674-A8DB-2E6273F215D0', 
				'2EE08A8E-2D89-4BF7-7027-26F75BB4A34C', 
				'A2559A08-5D65-43BE-2377-D7D9642F80EA', 
				'347470E3-52C1-4220-1452-814B6B5964FB', 
				'A0EC4858-8E7D-45F9-8659-3712065726EB', 
				'039A811D-B223-4D8F-0CC3-EE8B4893757E', 
				'5BCC13F6-81EA-48BD-7B7E-4A3825EC4DB4', 
				'D899BB35-5B61-47AF-11AC-53A7AB0F6901', 
				'6BF7AFF5-24D5-49C6-BCA1-B494451FE0B9', 
				'02A4D2A1-1EAA-4ABD-DF15-4E8127045130', 
				'05FE1F55-9ACC-44C3-09C0-47F5C28B10E4', 
				'BC4A6118-5A75-48DB-3BAC-1919F922B124', 
				'1E66D7D0-8D30-4739-1800-0AA0A79A4FF7', 
				'A803D518-8A4E-4EF9-D086-05EBD38372CD', 
				'B382810F-D569-4B62-9A0B-4BFFD9DEDF42', 
				'DE3BADC1-2461-43BE-0886-B9D834718AB3', 
				'63312D0C-51A8-403C-9787-D7A413C757C1', 
				'0181E428-323F-4598-F53E-A379B2FA84A0', 
				'5990AB0A-2651-46F0-1C19-61AE539EB78C', 
				'B49A5171-4093-41F2-87D9-431971D382DF', 
				'FCEB51FF-9674-4D75-D4F9-FFC92B3FDBA3', 
				'0B80E191-BA8F-48E1-6D6D-4DEF40A004AD', 
				'AD46CA3B-8328-4641-69BA-6100C4250357', 
				'8DF8272C-7F57-421D-0DB4-2502389EA1C3', 
				'F088E700-31C8-4DE3-3183-6EF1AE4E7075', 
				'632CD825-3CE6-4630-ACD8-C5952E85FA56', 
				'F1D5674E-8911-4990-1B6E-3CA1A3990E3D', 
				'E50FB9FE-BFE7-4CEE-2F20-503639B850E7', 
				'8F0A8516-07B1-4162-24F2-0D1765C6E198', 
				'1261F53B-A94F-45D4-4C62-A8185B42182B', 
				'B6579BCC-59BF-47EF-BD4C-DC7518047857', 
				'C4360573-7165-42FD-9951-E2606067F955', 
				'4A91D5DB-FC19-4A39-9CF4-0132D0DBC59B', 
				'E67B665C-F71A-436F-9F67-4128D1805AC3', 
				'4BE1228F-EF4F-43B3-B8AF-D784732D029D', 
				'9C60E3D9-94B8-42E3-E5BB-3F719C13C5A1', 
				'82F7E433-BFC6-4C17-0EF6-2CBC6704B521', 
				'CC2D577D-DE66-4D80-1E4B-912BB9B1D99E', 
				'FA0CA57D-DCC6-4763-F978-C1AF0B6EE0D1', 
				'11F8E4C4-AE3F-46C3-CC49-BCABD5C5309B', 
				'DCD9B983-5D2B-45E5-0D9F-A078D1340281', 
				'3F2DA6B9-D3FA-4B1C-5D9A-74EE66773CDB', 
				'7A9268C0-6C97-4004-0E89-449D45DE0E47', 
				'8F9E66FE-6185-4C21-2E26-72FE40DB98F3', 
				'C13598EF-BBB9-43E6-2DEF-A44B95FFC23E', 
				'707C66EC-1892-4D63-6074-DCAA569A3502', 
				'F78BBB1D-1EDD-4F85-5815-CCC921435DD0', 
				'5F0B40D6-8385-4750-01AC-8FAF0423AFDB', 
				'CF9774EB-C738-4E49-7D04-83C3DF9BECC8', 
				'B4DD63FF-DBEF-433D-7FDE-BB35F89DE9FE', 
				'1CF74FD2-C118-46F8-BA88-9AFC4E8B22A1', 
				'93CC1B85-9085-43B6-48B7-72D239C5C733', 
				'63DB3371-C35B-4A85-BF45-24EA4BE7D1BE', 
				'2A5D3AED-33F7-450D-9064-EE35F3C5C260', 
				'FBBC7B59-81CF-4FDF-4411-B88AA2734688', 
				'2FFBDC72-FB4F-4C8B-8DA1-2078B32FF9C4', 
				'TESTVAR0000000343', 
				'TESTVAR0000000339', 
				'TESTVAR0000000338', 
				'A98DCE4C-C689-4F9D-331F-35A6D70BF902', 
				'17545077-CBCF-4939-3A38-C1A7D2E4A2CB', 
				'A4AC1F01-D066-4D2D-2116-BAA5A78B2591', 
				'27C4189A-D983-4C0B-ED02-46AEA7066624', 
				'7CD9283B-8AD6-4C05-2356-8F1F06D298E0', 
				'CE2D775F-C73C-4EC2-1D4F-4DF91422706A', 
				'AF08D935-0B8C-4936-6736-BA6C196BE619', 
				'97A887B6-D8A2-456C-4249-061FCF38B1C2', 
				'D755D1F4-B4BE-4D15-120A-E83957CF1674', 
				'7196B946-0B58-4837-7347-D9F0D9EAD453', 
				'1AB4B4A6-C2D7-4FE8-3F9A-170C7D723E7C', 
				'93DB99EE-0293-4AFB-90E2-30A512F08437', 
				'D56FF55F-65B4-4D5B-601C-F001B2F0E8FD', 
				'561AF928-A5CF-4863-03E5-D733D6F4D984', 
				'E494F961-2EC6-4B07-4705-111AB819F686', 
				'AC943240-C5AB-4EF4-0E88-D8D4B5AFE893', 
				'4FCCE35B-67CB-42F5-F148-BACB17FF4EE2', 
				'CDAC5D8B-BDDC-45A0-099A-089431121B27', 
				'5C493D2E-944A-44EF-BE18-C1C021ABD9AF', 
				'24CC3DCE-7FD2-4665-048C-E350008C19ED', 
				'758F9AF6-0377-48E5-F8D0-B78FBB64F56E', 
				'896CB511-3FD7-4DE0-FBD3-0E1CC4B91691', 
				'922F67AF-A85C-473C-2CA6-16C4BEAC4227', 
				'6F63C809-D79C-4E55-FF18-CBE69CB87EA0', 
				'23D10031-AD64-44ED-6FCD-FA6AC0F3C81A', 
				'FCA015BB-C714-4266-B8F2-72FA4FDF50E7', 
				'766D7A19-CCC3-4118-E294-D6CDF7612D3E', 
				'A5CDAF60-F859-4BFA-E191-E84ECAC03114', 
				'59816168-2467-4CE2-09A7-5F6DB802A625', 
				'2882F39D-F860-4A60-37BD-2DB903A92D69', 
				'1F261E34-6104-4FE5-A641-FA8D23F4625C', 
				'5BCB5333-5179-48FD-23A2-4B1566F24251', 
				'DF25CF1C-E34D-427F-CFB0-E527C9ACCEA5', 
				'8FD405D4-D623-4AE0-436C-6478BE8BA1C0', 
				'AB338A42-B7AB-40C6-3FBB-69BD7C25D01A', 
				'61C1678E-3D79-47EB-F146-DD40D923C07D', 
				'8A0DDB3B-1D01-4F00-BF75-D30B9F1A4334', 
				'2AD39910-99B3-4F7F-3B45-E921D6EF5C16', 
				'685E0416-9E17-40EA-EBD5-F2F180B1DAC8', 
				'1C871CE1-5D28-401A-ED07-D4AC3CD877D3', 
				'5678403F-A38A-4DBC-6206-34DC89EE0B06', 
				'422F2E58-10B9-4C07-60C7-D75912AC853C', 
				'8BF52FC0-560E-427B-7D9E-E6DB7DE37DCC', 
				'BBB6DB74-9B84-4199-64D1-CD8063E898B9', 
				'08EC89DD-9C6E-424E-B937-74E13FFE1407', 
				'B2F397BE-D9CC-4B01-0B2A-D2BD8397E454', 
				'B7672B58-81D1-48F1-51F4-203C73B1D00A', 
				'9FF94963-3C4A-43EC-EB21-B389B04678AF', 
				'5265D4FF-D17C-4E48-D14A-A70931BAB88A', 
				'1375E4C6-4542-4840-18BF-98DBC38DB141', 
				'5A70CDDB-E0E3-45E8-9AFC-C0815DDE5D9A', 
				'C5B01DE8-5B59-4898-E77D-80A49E701662', 
				'80D4B5F1-7E8E-497D-8D8F-E0C5F7EBD7CB', 
				'BEE37966-508F-4484-01FD-164C67F56333', 
				'EDAE23EA-FE68-4CDC-10DC-F7C063A479E9', 
				'BDD390AB-7F85-45E0-8CFF-28B82AFB8B9C', 
				'09C234D4-A23F-435F-C46E-F170D13129F0', 
				'D099D725-E30A-4095-2F64-ED7E901C6700', 
				'B5C5D9F5-661A-4A01-0612-53E003091FA5', 
				'AB31571E-D147-484F-7C49-80C64B922A9E', 
				'35BBD44B-ED02-4704-8087-DCE0AEE562E7', 
				'BA05B03B-2BA5-4B03-40BC-DC7234D85BBA', 
				'DB609D52-4CAA-4306-4016-C848500AAA39', 
				'F972671A-E90B-404E-67F2-E72B656C8F21', 
				'8ECD9E05-D4EF-47C7-BF50-81F19152318C', 
				'3D346931-083D-4F79-0409-E56CDA5DE6CB', 
				'3862D4BE-72C5-4CD0-617F-3250C7A2A830', 
				'67F02690-9277-4DE2-94CA-8F659F5E612E'
									);
		  
		  
		  $limitURIs = array(
				0 => array("http://eol.org/pages/328663",
							  "http://eol.org/pages/4445650",
							  "http://eol.org/pages/42318"),
				1 => array("http://purl.obolibrary.org/obo/UBERON_0001684",
							  "http://purl.obolibrary.org/obo/UBERON_0003268")
		  );
		 
		 
		 
		 
		  $tableObj = new TabOut_Table;
		  $tableObj->setSize = $setSize;
		  $tableObj->page = $page;
		  $tableObj->limitingProjArray = $limitingProjArray;
		  $tableObj->limitingTypeURIs = $limitURIs ;
		  $tableObj->limitingVarArray = $limitVars ;
		  $tableObj->showUnitTypeFields = false;
		  $tableObj->limitUnitTypeFields = false;
		  $tableObj->showSourceFields = true;
		  $tableObj->showBP = false;
		  $tableObj->showBCE = true;
		  $tableObj->showLDSourceValues = true;
		  $tableObj->tableID = false;
		  $tableObj->DBtableID = "z_ex_pigs_use";
		  
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
		  $showLDSourceValues = false; //boolen, show the original value linked to linked data
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
				$limitUnitTypeFields = $requestParams["showLDSourceValues"];
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
		  $tableObj->setSize = $setSize;
		  $tableObj->page = $page;
		  $tableObj->limitingProjArray = $projArray;
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
	 function tableCsvAction(){
		  
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
		  
		  $csv = $tableFiles->makeSaveCSV();
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
	 
	 
	 
	 //get the list of output tables
	 function indexAction(){
		  
		  Zend_Loader::loadClass('TabOut_Tables');
		  $tablePubObj = new TabOut_Tables;
		  $tablePubObj->getTables();
		  $this->view->tablePubObj = $tablePubObj;
		  
	 }
	 
}//end class