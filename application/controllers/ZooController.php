<?php

/** Zend_Controller_Action */
//require_once 'Zend/Controller/Action.php';
//require_once 'OpenContext/Controller/Action/Helper/SolrAccess.php';

//error_reporting(E_ALL ^ E_NOTICE);
// increase the memory limit
ini_set("memory_limit", "1024M");
// set maximum execution time to no limit
ini_set("max_execution_time", "0");

class ZooController extends Zend_Controller_Action {
    
    function init()
    {  
        require_once 'App/Util/GenericFunctions.php';
    }
	 
	 
	 
	  //add links from media items back to diary items
	 function pcTbScrapePropsAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Murlo');
		  Zend_Loader::loadClass('dataEdit_Property');
		  
		  $murloObj = new ProjEdits_Murlo;
		  
		  $output = $murloObj->TBaddDiaryProperties();
		  
		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
	 }
	 
	 
	 
	 
	 
	 
	 
	  //add links from media items back to diary items
	 function pcTbScrapePagesDiaryAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Murlo');
		  Zend_Loader::loadClass('dataEdit_Link');
		  
		  $murloObj = new ProjEdits_Murlo;
		  
		  $output = $murloObj->TBscrapeDiary();
		  
		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
	 }
	 
	 
	 
	 
	 
	 
	 
	  //add links from media items back to diary items
	 function pcTbScrapeCleanAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Murlo');
		  
		  $murloObj = new ProjEdits_Murlo;
		  
		  $output = $murloObj->TBScrapeClean();
		  
		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
	 }
	 
    
	 
	  //add links from media items back to diary items
	 function pcTbScrapeParseAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Murlo');
		  
		  $murloObj = new ProjEdits_Murlo;
		  $murloObj->linkFix();
		  $output = $murloObj->TBscrapeParse();
		  
		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
	 }
	 
	 
	  //add links from media items back to diary items
	 function pcTbAttributeAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Murlo');
		  Zend_Loader::loadClass('dataEdit_Link');
		  
		  $murloObj = new ProjEdits_Murlo;
		  $typeToAttribute = 'Diary / Narrative';
		  //$typeToAttribute = 'Media (various)';
		  
		  $output = $murloObj->TBauthorLink($typeToAttribute );
		  
		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
	 }
	 
	 
	 
	 //add links from media items back to diary items
	 function pcTbMediaLinkAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Murlo');
		  Zend_Loader::loadClass('dataEdit_Link');
		  
		  $murloObj = new ProjEdits_Murlo;
		  $output = $murloObj->TBmediaLink();
		  
		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
	 }
	 
	 
	 
	  function pcTbTextCleanAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Murlo');
		  $jsonURL = "http://penelope.oc/csv-export/TrenchesGeo.geojson";
		  //$jsonURL = "http://penelope.oc/csv-export/murlo-trenches-b.txt";
		  
		  $murloObj = new ProjEdits_Murlo;
		  $output = $murloObj->TBtransClean();
		  
		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
	 }
	 
	 
	 function pcTbNamesAction(){
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Murlo');
		  $murloObj = new ProjEdits_Murlo;
		  header('Content-Type: text/html; charset=utf8');
		  echo $murloObj->TBauthors();
	 }
	 
	 
	 function pcTbImagesAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Murlo');
		  $jsonURL = "http://penelope.oc/csv-export/TrenchesGeo.geojson";
		  //$jsonURL = "http://penelope.oc/csv-export/murlo-trenches-b.txt";
		  
		  $murloObj = new ProjEdits_Murlo;
		  $output = $murloObj->TBimagePageNumbers();
		  
		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
	 }
	 
	 
	 
	 
	 
	 function pcGeoJsonFindsAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Murlo');
		  $jsonURL = "http://penelope.oc/csv-export/ArtifactsGeoJSON.json";
		  //$jsonURL = "http://penelope.oc/csv-export/murlo-trenches-b.txt";
		  
		  $murloObj = new ProjEdits_Murlo;
		  $output = $murloObj->findsGeoJsonAdd($jsonURL);
		  
		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
	 }
	 
	 function pcGeoAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Murlo');
		  $jsonURL = "http://penelope.oc/csv-export/TrenchesGeo.geojson";
		  //$jsonURL = "http://penelope.oc/csv-export/murlo-trenches-b.txt";
		  
		  $murloObj = new ProjEdits_Murlo;
		  $output = $murloObj->geoJsonAdd($jsonURL);
		  
		  header('Content-Type: application/json; charset=utf8');
		  
		  echo Zend_Json::encode($output);
	 }
	 
	 //use a solr query to republish a list of items
	 function republishSolrAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();
		  
		  $solrQuery = "http://localhost:8983/solr/select?facet=true&facet.mincount=1&fq=%7B%21cache%3Dfalse%7Dproject_name%3AMurlo++%26%26+NOT+project_id%3A0+NOT+def_context_0%3AItaly+%26%26+%28+%28item_type%3Aspatial%29+%29&facet.field=def_context_0&facet.field=project_name&facet.field=item_class&facet.field=time_span&facet.field=geo_point&facet.query=image_media_count%3A%5B1+TO+%2A%5D&facet.query=other_binary_media_count%3A%5B1+TO+%2A%5D&facet.query=diary_count%3A%5B1+TO+%2A%5D&sort=interest_score+desc&wt=json&json.nl=map&q=%2A%3A%2A&start=0&rows=200";
		  $solrQuery = "http://localhost:8983/solr/select?facet=true&facet.mincount=1&fq=%7B%21cache%3Dfalse%7DNOT+project_id%3A0+%26%26+%28+%28item_type%3Aspatial%29+%29&facet.field=def_context_1&facet.field=project_name&facet.field=item_class&facet.field=time_span&facet.field=geo_point&facet.field=geo_path&facet.query=image_media_count%3A%5B1+TO+%2A%5D&facet.query=other_binary_media_count%3A%5B1+TO+%2A%5D&facet.query=diary_count%3A%5B1+TO+%2A%5D&sort=interest_score+desc&wt=json&json.nl=map&q=%28+%28default_context_path%3AItaly%2F%2A+%29+%7C%7C+%28default_context_path%3AItaly+%29%29+%26%26+%28geo_path%3A12023202222130310%2A%29&start=0&rows=400";
		  
		  $respJSONstring = file_get_contents($solrQuery);
		  $solrJSON = Zend_Json::decode($respJSONstring);
		  $output = array();
		  $localPubBaseURI = "http://penelope.oc/publish/publishdoc?projectUUID=DF043419-F23B-41DA-7E4D-EE52AF22F92F&itemType=space&doUpdate=true&itemUUID=";
		  $ocPubBaseURI = "http://penelope.oc/publish/publishdoc?projectUUID=DF043419-F23B-41DA-7E4D-EE52AF22F92F&itemType=space&doUpdate=true&pubURI=http://opencontext.org/publish/item-publish&itemUUID=";
		  
		  foreach($solrJSON["response"]["docs"] as $doc){
				
				$uuid = $doc["uuid"];
				$pubResp = array();
				$resp = file_get_contents($localPubBaseURI.$uuid);
				$pubResp["local"] = Zend_Json::decode($resp);
				//sleep(1);
				/*
				$resp = file_get_contents($ocPubBaseURI.$uuid);
				$pubResp["oc"] = Zend_Json::decode($resp);
				*/
				$output[$uuid] = $pubResp;
				unset($pubResp);
		  }
		  
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_Json::encode($output);
	 }
	 
	 
	 //load up old space data from XML documents
	 function addSpaceHierarchyAction(){
		  
		  //this line is necessary for ajax calls:
        $this->_helper->viewRenderer->setNoRender();        
        
        //get selected root item then add it and all children to database
        $baseURL = "http://opencontext/subjects/";
		  $baseMediaURL = "http://opencontext/media/";
        $rootUUID = "5D6B6454-017A-43C1-9F15-6DFE36C3558F";
		  
		  if(isset($_GET["root"])){
				$rootUUID = $_GET["root"];
		  }
		  
		  Zend_Loader::loadClass('PublishedData_Hierarchy');
        Zend_Loader::loadClass('PublishedData_Space');
        Zend_Loader::loadClass('PublishedData_Observe');
		  Zend_Loader::loadClass('PublishedData_Properties');
		  Zend_Loader::loadClass('PublishedData_Links');
		  Zend_Loader::loadClass('PublishedData_Resource');
		  Zend_Loader::loadClass('dbXML_xmlSpace');
		  Zend_Loader::loadClass('dbXML_xmlMedia');
		  
		  $hierarchyObj = new PublishedData_Hierarchy;
		  $hierarchyObj->baseSpaceURI = $baseURL;
		  $hierarchyObj->baseMediaURI = $baseMediaURL;
		  $hierarchyObj->addHierarchy($rootUUID);
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_Json::encode(array("done" => $hierarchyObj->doneURIs, "errors" => $hierarchyObj->errors));
	 }
	 
	 
	 function propertyGetAction(){
		  $baseURL = "http://opencontext/subjects/";
		  Zend_Loader::loadClass('PublishedData_Space');
        Zend_Loader::loadClass('PublishedData_Observe');
		  Zend_Loader::loadClass('PublishedData_Properties');
		  Zend_Loader::loadClass('dbXML_xmlSpace');
		  
		  $spaceXML = new dbXML_xmlSpace;
		  $namespaces = $spaceXML->nameSpaces();
		  
		  $this->_helper->viewRenderer->setNoRender();        
		  $db = Zend_Registry::get('db');
		  $sql = "SELECT uuid, project_id, source_id
		  FROM space
		  WHERE (project_id = '3' OR project_id = 'TESTPRJ0000000004') AND class_uuid = '881CEDA3-C445-4C9C-4D4B-634BD2963892' ";
		  
		  $sql = "SELECT uuid, project_id, source_id
		  FROM space
		  WHERE 1 ";
		  
		  
		  $result = $db->fetchAll($sql);
		  foreach($result as $row){
				$uuid = $row["uuid"];
				$projectID = $row["project_id"];
				$sourceID = $row["source_id"];
				
				$itemURL = $baseURL.$uuid.".xml";
				@$xmlString = file_get_contents($itemURL);
				if($xmlString != false){
					 @$itemXML = simplexml_load_string($xmlString);
					 if($itemXML != false){
						  
						  foreach($namespaces as $prefix => $nsURI){
								$itemXML->registerXPathNamespace($prefix, $nsURI);
						  }
						  
						  $propsObj = new PublishedData_Properties;
						  $propsObj->startDB();
						  $propsObj->itemUUID = $uuid ;
						  $propsObj->projectUUID = $projectID;
						  $propsObj->sourceID = $sourceID;
						  $properties = $propsObj->itemPropsRetrieve($itemXML);
						  $propsObj->saveData($properties);
						  
						  echo"<br/> Done with: <a href='http://penelope.oc/preview/space?UUID=".$uuid."'>".$uuid."</a>";
					 }
				}
		  }//end loop
	 }//end function
	 
	 
	 
	 
	 
	 function redoBoneAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();    
		  
		  $badUUIDs = array(
	 		'01441BAE-754C-4E62-6BA4-124BFDF3EEBC', 
		  '0200669B-35A6-43EB-7A41-91AE643D382D', 
		  '047AFCCA-AA60-43D4-047E-FF7DA51B593E', 
		  '04A63CD6-7FF8-4180-7D79-BA3C3C66595D', 
		  '059E4CDF-DDA2-49BF-A8D2-9AD85D77ADF6', 
		  '0607E92B-3D69-4AA5-53DE-4E6FB67FC9B9', 
		  '08A06A64-93E9-4931-E79E-F0617D31D4F7', 
		  '0C4B1D47-540C-4761-2AD6-E414A852A3D6', 
		  '108D6BEC-E96F-4A1D-AEAE-4F6DAC86A014', 
		  '12C8FAD4-2A68-4FCF-4ACD-5746EA5D9AB9', 
		  '14724176-1470-4EAB-3AA5-F080E24DF4BC', 
		  '15C344F0-44E2-4BDE-9506-51C8AA994541', 
		  '16963252-985A-464F-EB3E-F51DEE2B33DB', 
		  '16F42A27-CE3E-4CAF-FBA8-77A9A2C9C36C', 
		  '181C0884-910C-42E7-6D9B-7510C49D8565', 
		  '19163DDE-37AF-4D61-1E8A-AB8346D7605B', 
		  '1CCD6761-12C8-401F-0F2F-4C6C48041AF2', 
		  '1CDBFC9D-6E1C-42DA-AD11-7756A9B42DEF', 
		  '1FA1323E-5674-4603-2F40-7849CF8E5DBB', 
		  '21988064-0F1F-4A5D-AECD-BF55BB0CA2E1', 
		  '242F2DA4-EB88-4A0C-0826-A96A247BC69B', 
		  '250EA659-3C7C-4CB0-E6E9-2A84B33946D8', 
		  '25139E00-1569-4586-5597-4FF7B72335D4', 
		  '2823BAC8-7571-4FFC-D2FD-B31E7440F60C', 
		  '292ECCF7-63A4-43CD-8605-56411AF81D12', 
		  '2DEFF61C-7016-4BFE-9E1B-B1666527C021', 
		  '32B7EC55-8D57-4F2C-A627-368CFB35C354', 
		  '340D2E85-3FA2-499C-BDC6-7D42DE8B3F1C', 
		  '343871AE-758F-44C1-B4E8-41DE73246F15', 
		  '348E355C-67CE-4B8A-2BA2-9D3BBB350BCC', 
		  '363D0E3E-BFBB-48CC-D743-ACED753F69A2', 
		  '3849D365-513B-44CC-28B9-349BAA2BD0FB', 
		  '38DFEAA4-E8E3-4A50-ED0D-406B1A8D183B', 
		  '3ACA5C4D-DC04-4FAF-83C4-EF2BDE9E8C6F', 
		  '3B70AE9C-3874-4503-C019-D8E790ABBF54', 
		  '3E15833D-6BBF-46AA-8B39-6F939495C52A', 
		  '41530EDC-6017-40D9-57E8-0158E8A37882', 
		  '4224A848-447A-45D6-17C6-0F58A32F28FE', 
		  '4316715A-17DF-416E-A02D-EF6CAE9D147A', 
		  '44C5229B-F1D7-4D7A-E397-DE61DA0D8F68', 
		  '4639210E-7107-44F3-8909-160F07518857', 
		  '46F8E4F3-E16F-4B98-4CEB-061C4EAFCA17', 
		  '4D62F3A0-AB5C-4281-A6A9-8EF358F66A56', 
		  '4FA60232-AE64-4229-3C66-CCB484A4DD7A', 
		  '5446567D-CD42-4528-BFDF-01430C8B2F89', 
		  '567FCC18-03F7-49B4-ED61-F0346BD57CD1', 
		  '5AD7AE95-F044-4646-8AC7-4295E4EF3724', 
		  '5D1853DB-439A-40C9-6318-2C3A0D6AE54A', 
		  '66CF332B-D95B-4286-B771-228F90B2EE9D', 
		  '67388B7F-4C27-4F4F-C0D4-7268E6BFBDED', 
		  '688E93C7-5E6D-42AE-B7F3-FC1618ED2E73', 
		  '6C64F66E-8DC0-4972-7EA3-135C6702C4F1', 
		  '6E1F08CE-8FD0-43D2-3A21-1B5887204EA7', 
		  '6E44EEDD-C941-4841-E1ED-B0589BB86832', 
		  '6F07D707-9F61-4327-684C-BCB675F2AE08', 
		  '6F0F00AB-91DE-4D94-1139-6C88903D383E', 
		  '76F0D616-250D-4AC4-466E-BB2CB6BE6376', 
		  '7E6A88E7-F3E3-49F5-793D-E54E3FF4265B', 
		  '83B92423-794A-4FF4-5569-0E1341E7E675', 
		  '84345D49-2DB3-4C7A-05EF-8C1E817E0C6A', 
		  '8779B999-D1B8-4694-DDB7-CE5F6E61D7BE', 
		  '889763AA-0D0E-4D55-C3F8-95C543B59489', 
		  '8913B5ED-5C7C-4BB7-7DB7-06B1BDBFFB1F', 
		  '8C6F0BC8-3189-49CD-7928-C2B7E062E719', 
		  '8CCF6C8E-55C0-4D91-83ED-C298645998DE', 
		  '8EC793FE-1FA8-45C6-EB12-0C3D90E2795F', 
		  '8FE01A8F-B20A-4348-4D34-03F365CFD3A1', 
		  '912D181E-DFE8-4965-D83E-B6C98C3B72EB', 
		  '914E4FDE-8B9A-4602-D3F8-21FDB3788E45', 
		  '92587F94-E920-48D2-4FF1-B4D4F6B9C361', 
		  '94819171-7079-4024-3B22-8C8E7E3C9B5F', 
		  '96F898BF-A473-42FF-C6F1-89ECCDB1F39C', 
		  '9745844F-6CBB-4A6B-13B3-1FC7FAB3EA32', 
		  '9822EE58-1E2F-42F9-6502-F5EAE0D2CA39', 
		  '9B81BC5D-2723-4B96-910C-FB306BFEA7CA', 
		  'A3A5684C-9B04-4629-5B9D-E7181EE6AB9E', 
		  'A500860F-2349-423D-788F-E28D465E054B', 
		  'A85C37A5-BC95-4338-99DC-99DE61B41EB6', 
		  'AFDADE4D-258E-4CA5-E9EC-4816CC4F70EF', 
		  'B54F1D45-AA29-4CBF-8029-3870E47AEDBD', 
		  'B56D1197-8E1E-47FD-DF69-EBD0A5BC1543', 
		  'B6E99761-7C2F-4783-E854-E666058F9536', 
		  'B89AF4FA-BD0B-448E-E955-E129229209E1', 
		  'B95A67B3-89E8-4BBD-74A1-419A3A9A1206', 
		  'BA6761E0-F87D-444E-0590-56E883251CCC', 
		  'C277864E-B0F9-4E3F-5D6D-4E1068C94152', 
		  'C486EB00-6290-478B-D1E2-E1E00F41895C', 
		  'C49F1AA0-0E50-40BF-7E1A-115BB32BD7BA', 
		  'C78FACC3-4B62-436F-3CCB-D150457C8BFE', 
		  'CA804F46-3C71-454D-FF31-E5B2384B7468', 
		  'CB513F76-084E-45F5-39EA-878FD0FC8F48', 
		  'CB879D7C-3524-4968-C1B8-DA9109FEBD49', 
		  'CD2B56AE-619E-46B6-5B26-55F3A9FD9029', 
		  'CDA374A3-7F5A-4F76-C479-27BA66872D94', 
		  'D07ADDC8-3496-4EA4-10BB-39D03F9C507F', 
		  'D2D8FA29-7D25-4901-A435-84128A78277F', 
		  'D300D36C-922B-461F-B467-A6E352D06773', 
		  'DBE40725-677B-4C26-92C9-CB8352A69C7C', 
		  'DF4C063E-7CE2-4955-C882-3CA420ECE0AA', 
		  'E10C2368-6A4B-4528-28FE-673F06218358', 
		  'E119757F-13BB-4CD6-FDCF-70C11B1C7EA2', 
		  'E7465FF9-B1BE-4503-3DD0-6560548EAA23', 
		  'EF221176-6028-436D-3541-C89A2AE9F20E', 
		  'F2A34C09-E652-4C1E-D1DA-A37663EED096', 
		  'F84014C3-33E1-4FDF-35C2-EB00A1535D76', 
		  'FC2C7F03-C8CA-487C-BC0E-7787371A3278', 
		  'FC504975-D305-4949-F781-92A9B53E2220', 
		  'FE70CC58-72ED-4AAD-AB6A-519699FB47CD',  
		  );
		  
		  $db = Zend_Registry::get('db');
		  Zend_Loader::loadClass('dataEdit_SpaceIdentity');
		  Zend_Loader::loadClass('dataEdit_Property');
		  
		  $sql = "SELECT field_summary.field_name, field_summary.field_label,
					 var_tab.variable_uuid, field_summary.project_id
					 FROM field_summary
					 LEFT JOIN var_tab ON (
						  var_tab.var_label LIKE field_summary.field_label
						  AND var_tab.project_id = field_summary.project_id
						  )
					 WHERE field_summary.source_id = 'z_1_ee76ce40e'
					 AND field_summary.field_type = 'Property'
					 ";
		  $tabFields = array();
		  $rawTabFields = $db->fetchAll($sql);
		  foreach($rawTabFields as $field){
				if($field["variable_uuid"]){
					 $fieldLabel = $field["field_name"];
					 $tabFields[$fieldLabel] = $field["variable_uuid"];
				}
		  }
		  
		  
		  $spaceEdit = new dataEdit_SpaceIdentity;
		  $propEdit = new dataEdit_Property;
		  
		  $spaceEdit->actSourceTab = 'z_1_ee76ce40e';
		  $output = array();
		  foreach($badUUIDs as $uuid){
				
				$sql = "UPDATE space SET source_id = 'z_1_ee76ce40e' WHERE uuid = '".$uuid."' LIMIT 1;";
				$db->query($sql, 2);
				
				$sql = "SELECT source_id, space_label, full_context, class_uuid, project_id
				FROM space
				WHERE uuid = '$uuid' LIMIT 1;
				";
				
				$result = $db->fetchAll($sql);
				if($result){
					 $itemLabel = $result[0]["space_label"];
					 $itemContext = $result[0]["full_context"];
					 $sourceID = $result[0]["source_id"];
					 $classUUID = $result[0]["class_uuid"];
					 $projectUUID = $result[0]["project_id"];
					 
					 $sourceIDs = $spaceEdit->getSourceIDs($itemLabel, $itemContext, $sourceID, $classUUID);
					 //$sourceData = $spaceEdit->itemDuplicateNoObs($uuid, $sourceIDs);
					 
					 foreach($sourceData as $subjectUUID => $idArray){
						  
						  //delete the old observations
						  $where = "subject_uuid = '$subjectUUID' ";
						  //$db->delete("observe", $where);
						  
						  $id = $idArray["id"];
						  
						  $sql = "SELECT * FROM z_1_ee76ce40e AS otab WHERE id = $id LIMIT 1;";
						  $originalData = $db->fetchAll($sql);
						  foreach($originalData as $oRow){
								foreach($oRow as $fieldKey => $value){
									 if($value){
										  if(array_key_exists($fieldKey, $tabFields)){
												$variableUUID = $tabFields[$fieldKey];
												$valueUUID = $propEdit->get_make_ValID($value, $projectUUID);
												$propUUID = $propEdit->get_make_PropID($variableUUID, $valueUUID, $projectUUID);
												
												$hashObs = md5($projectUUID . "_" . $subjectUUID . "_" . 1 . "_" . $propUUID);
												$data = array("project_id" => $projectUUID,
																  "source_id" => $sourceID,
																  "hash_obs" => $hashObs,
																  "subject_type" => "Locations or Objects",
																  "subject_uuid" => $subjectUUID,
																  "obs_num" => 1,
																  "property_uuid" => $propUUID
																  );
												try{
													 //$db->insert('observe', $data );
												}
												catch (Exception $e) {
												
												}
												
												$output[$uuid][$id][$fieldKey] = array("link" => "http://penelope.oc/preview/space?UUID=".$subjectUUID,
																									"subjectUUID" => $subjectUUID,
																									"propUUID" => $propUUID,
																									"variableUUID" => $variableUUID,
																									"valueUUID" => $valueUUID,
																									 "value" => $value);
										  }
									 }
								}
						  }
						  
						  $firstLoop = false;
					 }//array of sourceData
					 
				}
				
		  }//end loop
		  
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_Json::encode($output);
		  
	 }
	 
	 
	 
	   //load up old space data from XML documents
	 function repubListAction(){
		  
		  //this line is necessary for ajax calls:
        $this->_helper->viewRenderer->setNoRender();        
        
		  $badListURL = "http://opencontext.org/sets/Turkey/Pinarbasi.json?projID=TESTPRJ0000000004&cat=Animal+Bone&recs=100";
		  
		  $basePublishURL = "http://penelope.oc/publish/publishdoc";
		  $params = array(
				"projectUUID" => "TESTPRJ0000000004",
				"pubURI" => "http://opencontext.org/publish/item-publish",
				"update" => "true",
				"itemType" => "space");
		  
		  $badJSON = file_get_contents($badListURL);
		  $badObj = Zend_Json::decode($badJSON);
		  $output = array();
		  foreach($badObj["results"] as $result){
				sleep(.25);
				$uri = $result["uri"];
				$uriEx = explode("/",  $uri);
				$uuid = $uriEx[(count($uriEx)-1)];
				
				$params["itemUUID"] = $uuid;
				$actURL =  $basePublishURL . "?" . http_build_query($params);
				
				$resp = file_get_contents($actURL);
				$respObj = Zend_Json::decode($resp);
				$output[] = $respObj;
		  }
		  
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_Json::encode($output);
	 }

	 
	 //publish space items associated with images
	 function spaceMediaPubAction(){
		  
		  //this line is necessary for ajax calls:
        $this->_helper->viewRenderer->setNoRender();        
        
		  $badListURL = "http://opencontext.org/lightbox/Italy.json?recs=100&page=257";
		  
		  $basePublishURL = "http://penelope.oc/publish/publishdoc";
		  $params = array(
				"projectUUID" => "DF043419-F23B-41DA-7E4D-EE52AF22F92F",
				"pubURI" => "http://opencontext.org/publish/item-publish",
				"update" => "true",
				"itemType" => "space");
		  
		  $paramsB = array(
				"projectUUID" => "DF043419-F23B-41DA-7E4D-EE52AF22F92F",
				"pubURI" => "http://opencontext/publish/item-publish",
				"update" => "true",
				"itemType" => "space");
		  
		  $badJSON = file_get_contents($badListURL);
		  $badObj = Zend_Json::decode($badJSON);
		  
		  $output = array();
		  $output["queries"] = "";
		  foreach($badObj["results"] as $jresult){
				sleep(.25);
				if($jresult["project"]=="Murlo"){
					 $uri = $jresult["uri"];
					 $uriEx = explode("/",  $uri);
					 $resUUID = $uriEx[(count($uriEx)-1)];
					 
					 $db = Zend_Registry::get('db');
					 $sql = "SELECT origin_uuid as uuid FROM links WHERE targ_uuid = '$resUUID' AND origin_type LIKE '%location%' ";
					 $result = $db->fetchAll($sql);
					 foreach($result as $row){
						  
						  $params["itemUUID"] = $row["uuid"];
						  $actURL =  $basePublishURL . "?" . http_build_query($params);
						  
						  $resp = file_get_contents($actURL);
						  $respObj = Zend_Json::decode($resp);
						  $output[] = $respObj;
						  
						  $paramsB["itemUUID"] = $row["uuid"];
						  $actURL =  $basePublishURL . "?" . http_build_query($paramsB);
						  
						  $resp = file_get_contents($actURL);
						  $respObj = Zend_Json::decode($resp);
						  $output[] = $respObj;
						  sleep(.25);
					 }
					 
					 $output["queries"] .= " UPDATE noid_bindings SET solr_indexed = 0 WHERE itemUUID = '$resUUID' LIMIT 1; ";
					 //echo "UPDATE noid_bindings SET solr_indexed = 0 WHERE itemUUID = '$resUUID' LIMIT 1;";
					 
				}
		  }
		 
		  header('Content-Type: application/json; charset=utf8');
		 echo Zend_Json::encode($output);
	 }
	 
	 
	 
	  //publish space items associated with images
	 function personPubAction(){
		  
		  //this line is necessary for ajax calls:
        $this->_helper->viewRenderer->setNoRender();        
		  
		  $basePublishURL = "http://penelope.oc/publish/publishdoc";
		  $allParams = array();
		  $allParams[0] = array(
				"projectUUID" => "DF043419-F23B-41DA-7E4D-EE52AF22F92F",
				"pubURI" => "http://opencontext.org/publish/item-publish",
				"update" => "true",
				"itemType" => "person");
		  
		  $allParams[1]  = array(
				"projectUUID" => "DF043419-F23B-41DA-7E4D-EE52AF22F92F",
				"pubURI" => "http://opencontext/publish/item-publish",
				"update" => "true",
				"itemType" => "person");
		  
		
		  $output = array();
		  $output["queries"] = "";
		 
		  $db = Zend_Registry::get('db');
		  $sql = "SELECT uuid, project_id FROM persons WHERE
		  project_id = 'CDD40C27-62ED-4966-AF3D-E781DD0D4846'
		  OR
		  project_id = '05F5B702-2967-49B1-FEAA-9B2AA0184513'
		  OR
		  project_id = '74749949-4FD4-4C3E-C830-5AA75703E08E'
		  OR
		  project_id = 'BC90D462-6639-4087-8527-6BB9E528E07D'
		  ";
		  
		  $sql = "SELECT users.uuid, links.project_id FROM users
		  JOIN links ON users.uuid = links.targ_uuid
		  WHERE
		  users.uuid = '62EE9ABC-AD45-4F92-5A7B-B16A092CB5C2'
		  ";
		  
		  $result = $db->fetchAll($sql);
		  foreach($result as $row){
				
				foreach($allParams as $params){
					 
					 $params["itemUUID"] = $row["uuid"];
					 $params["projectUUID"] = $row["project_id"];
					 $actURL =  $basePublishURL . "?" . http_build_query($params);
					 
					 $resp = file_get_contents($actURL);
					 $respObj = Zend_Json::decode($resp);
					 $output[] = $respObj;
					
					 sleep(.25);
					 
					 $data = array("hash_key" => md5($row["uuid"]."_".$params["pubURI"]),
										"pubdest" => $params["pubURI"],
										"project_id" => $row["project_id"],
										"item_uuid" => $row["uuid"],
										"item_type" => $params["itemType"],
										"status" => "ok"
										);
					 
					 try{
						  $db->insert("published_docs", $data);
						  }
					 catch (Exception $e)  {
								//echo (string)$e;
								//die;
					 }

				}
				
		  }
		  header('Content-Type: application/json; charset=utf8');
		 echo Zend_Json::encode($output);
	 }
	 
	 

	  //load up old space data from XML documents
	 function linkBoneOntologyAction(){
		  
		  //this line is necessary for ajax calls:
        $this->_helper->viewRenderer->setNoRender();        
        
        //get selected root item then add it and all children to database
        $tableID = "z_21_df38a0fd9";
        
		  Zend_Loader::loadClass('LinkedData_BoneMeasurement');
		  
		  $linkingObj = new LinkedData_BoneMeasurement;
		  $linkingObj->doShortVariableLabels = false;
		  $varList = $linkingObj->getVarTableList($tableID);
		  $doneList = $linkingObj->processVars($varList);
		 
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_Json::encode($doneList);
	 }
	 

	 //check identifier uniqueness by seeing if the same variable is used more than once
	 function idCheckAction(){
		  
		  //this line is necessary for ajax calls:
        $this->_helper->viewRenderer->setNoRender();        
        
        //get selected root item then add it and all children to database
        $projUUID = "";
        
		  Zend_Loader::loadClass('dataEdit_SpaceIdentity');
		  
		  $editObj = new dataEdit_SpaceIdentity;
		  $editObj->projUUID = $projUUID;
		  $editObj->storeIDsWithDuplicatingVars();
		  $sourceIDs = $editObj->getSourceDataIDs();
		  $output = $editObj->fixIdentities();
		 
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_Json::encode($sourceIDs);
	 }

	 //check identifier uniqueness by seeing if the same variable is used more than once
	 function idCheckFixAction(){
		  
		  //this line is necessary for ajax calls:
        $this->_helper->viewRenderer->setNoRender();        
        
        //get selected root item then add it and all children to database
        $projUUID = "3DE4CD9C-259E-4C14-9B03-8B10454BA66E";
        
		  Zend_Loader::loadClass('dataEdit_SpaceIdentity');
		  
		  $editObj = new dataEdit_SpaceIdentity;
		  $editObj->projUUID = $projUUID;
		  $editObj->sourceLimit = "z_1_ee76ce40e"; 
		  $editObj->storeIDsWithDuplicatingVars();
		  $sourceIDs = $editObj->getSourceDataIDs();
		  $output = $editObj->fixIdentities();
		 
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_Json::encode($output);
	 }
	 
	 
	 
	 function alterPropLinksAction(){
		  
		  $this->_helper->viewRenderer->setNoRender();     
		  
		  $labelPrefix = "Bone ";
		  $changeArray = array(
		   'bone label' => ' uri '
		 );
		  
		  Zend_Loader::loadClass('LinkedData_PropSplitLink');
		  $propSplitObj = new LinkedData_PropSplitLink ;
		  $propSplitObj->projectUUID = '';
		  $propSplitObj->classUUID = '881CEDA3-C445-4C9C-4D4B-634BD2963892'; //animal bones
		  $propSplitObj->predicateURI = 'http://opencontext.org/vocabularies/open-context-zooarch/zoo-0079'; //has anatomical id
		  $propSplitObj->getVarUUIDfromPredicateURI();
		  
		  $output = array("varUUID" => $propSplitObj->actVarUUID);
		  foreach($changeArray as $itemLabel => $newURI){
				$propSplitObj->resetForLoop();
				$itemLabel = $labelPrefix.trim($itemLabel);
				$propSplitObj->newLinkURI = trim($newURI);
				$subjectUUID =  $propSplitObj->getSpaceUUIDfromLabel($itemLabel);
				$propSplitObj->subjectUUID = $subjectUUID;
				$oldPropUUID =  $propSplitObj->getPropertyUUIDfromObsVarUUID();
				$propSplitObj->oldPropUUID = $oldPropUUID;
				$oldPropertyUpdated = $propSplitObj->oldPropertyLinkURIUpToDate();
				$propSplitObj->alterObsNewLinkingProperty();
				
				$output["props"][] = array(
										  "itemLabel" => $itemLabel,
										  "subjectUUID" => $subjectUUID,
										  "link" => "http://penelope.oc/preview/space?UUID=".$subjectUUID,
										"oldPropUUID" => $oldPropUUID,
										"oldPropUpdated" => $oldPropertyUpdated,
									   "newPropUUID" => $propSplitObj->newPropUUID,
										"newLinkURI" => $propSplitObj->newLinkURI
				);
		  }
		  $output["errors"] = $propSplitObj->errors;
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_Json::encode($output);
		  
	 }
	 
	 

	 function catalAction(){
		 
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Catal');
		  
		  $catalObj = New ProjEdits_Catal;
		  $catalObj->workbookFile =  "http://penelope.oc/public/xml/catal-c-use.fods";
		  $catalObj->importTableName = "z_13_457009575";
		  //$catalObj->importTableName = false;
		  $catalObj->doCommentUpdate = true;
		  //$records = $catalObj->loadParseSaveXML();
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_Json::encode($records);
	 }
	 
	 function catalLinkAction(){
		 
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Catal');
		  $classUUID = "881CEDA3-C445-4C9C-4D4B-634BD2963892"; //animal bones
		  $projectUUID = "1B426F7C-99EC-4322-4069-E8DBD927CCF1"; //catal project id
		  $catalObj = New ProjEdits_Catal;
		  //$records = $catalObj->parentContextSelect( $classUUID, $projectUUID);
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_Json::encode($records);
	 }
	 
	 
	 function splitProjAction(){
		 
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_SplitProject');
		  
		  $projObj = New ProjEdits_SplitProject;
		  $projObj->oldProjectUUID = "731B0670-CE2A-414A-8EF6-9C050A1C60F5";
		  $projObj->newProjectUUID = "8894EEC0-DC96-4304-1EFC-4572FD91717A";
		  $projObj->oldContainText = "Turkey|xx|Okuzini Cave";
		  $projObj->newContainText = "Turkey|xx|Öküzini Cave";
		  
		  $projObj->getDistinctProperties("Turkey|xx|Okuzini Cave");
		  $records = $projObj->updateSpaceObs("Turkey|xx|Okuzini Cave");
		  $output = array("queries" => $projObj->queries, "recs" => $records);
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_Json::encode($output);
	 }
	 
	 
	 
	 
	 
	 function spaceSortAction(){
		 
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Space');
		  
		  $sortObj = New ProjEdits_Space;
		  $records = $sortObj->spaceLabelSorting();
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_Json::encode($records);
	 }

	 
	 function mediaFindLinkAction(){
		 
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Media');
		  Zend_Loader::loadClass('dbXML_dbMedia');
		  
		  $dbMedia = New dbXML_dbMedia;
		  
		  
		  $mediaObj = New ProjEdits_Media;
		  $mediaObj->mediaTypeArray = $dbMedia->mediaTypeArray;
		  $mediaObj->spaceLabelPrefix = "UNE ";
		  $mediaObj->projectUUID = "4B16F48E-6F5D-41E0-F568-FCE64BE6D3FA";
		  $mediaObj->mediaFileBaseURL = "http://artiraq.org/static/opencontext/stoneware-media/";
		  $mediaObj->mediaSearchDir = "C:\\Users\\Eric C. Kansa\\Documents\\OC Imports\\Peter Grave Data\\stoneware-media\\full\\";
		  //$directory = "C:\\about_opencontext\\kenan\\thumbs\\";
		 
		  $output = $mediaObj->findLinkCreateMedia();
		  
		  
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_Json::encode($output);
	 }
	 
	 function mediaCheckAction(){
		 
		  $this->_helper->viewRenderer->setNoRender();
		  Zend_Loader::loadClass('ProjEdits_Media');
		  Zend_Loader::loadClass('dbXML_dbMedia');
		  
		  $dbMedia = New dbXML_dbMedia;
		  
		  
		  $mediaObj = New ProjEdits_Media;
		  $mediaObj->mediaTypeArray = $dbMedia->mediaTypeArray;
		  $mediaObj->imageFileCheckLimit = " resource.project_id = 'CF6E1364-D6EF-4042-B726-82CFB73F7C9D' ";
		  $output = $mediaObj->imageFileCheck();
		  
		  
		  header('Content-Type: application/json; charset=utf8');
		  echo Zend_Json::encode($output);
	 }
	 
	 
	 
	 

}//end class