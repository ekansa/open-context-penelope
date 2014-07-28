<?php
/*
Gets data from Open Context for import into the Python
version

*/
class PyExport_PyProjects {
    
    public $db;
    public $atom;
	public $metadata;
	
	public $projects = array(
  array('project_id' => '01D080DF-2F6B-4F59-BCF0-87543AC89574'),
  array('project_id' => '02594C48-7497-40D7-11AE-AB942DC513B8'),
  array('project_id' => '0404C6DC-A467-421E-47B8-D68F7090FBCC'),
  array('project_id' => '05F5B702-2967-49B1-FEAA-9B2AA0184513'),
  array('project_id' => '0EE6A09E-62E5-45F0-1CB9-F5CDA44F4D9E'),
  array('project_id' => '1B426F7C-99EC-4322-4069-E8DBD927CCF1'),
  array('project_id' => '21A79037-01EA-4CBB-7048-6AA054FB4A0D'),
  array('project_id' => '295B5BF4-0F44-4698-80CD-7A39CB6F133D'),
  array('project_id' => '3'),
  array('project_id' => '3DE4CD9C-259E-4C14-9B03-8B10454BA66E'),
  array('project_id' => '3F6DCD13-A476-488E-ED10-47D25513FCB2'),
  array('project_id' => '3FAAA477-5572-4B05-8DC1-CA264FE1FC10'),
  array('project_id' => '42EAD4DB-BAED-4A58-9B9B-7EC85266D2A9'),
  array('project_id' => '497ADEAD-0C2A-4C62-FEEF-9079FB09B1A5'),
  array('project_id' => '4B16F48E-6F5D-41E0-F568-FCE64BE6D3FA'),
  array('project_id' => '4B5721E9-2BB3-423F-5D04-1B948FA65FAB'),
  array('project_id' => '59E7BFBC-2557-4FE4-FC14-284ED10D903D'),
  array('project_id' => '64013C33-4039-46C9-609A-A758CE51CA49'),
  array('project_id' => '731B0670-CE2A-414A-8EF6-9C050A1C60F5'),
  array('project_id' => '74749949-4FD4-4C3E-C830-5AA75703E08E'),
  array('project_id' => '81204AF8-127C-4686-E9B0-1202C3A47959'),
  array('project_id' => '8492AEC3-E406-44C6-03CA-2BF280D8F5B0'),
  array('project_id' => '8894EEC0-DC96-4304-1EFC-4572FD91717A'),
  array('project_id' => '8F947319-3C69-4847-B7A2-09E00ED90B32'),
  array('project_id' => '99BDB878-6411-44F8-2D7B-A99384A6CA21'),
  array('project_id' => 'A3CBADB6-6CD4-4099-9FE2-A33757EAB749'),
  array('project_id' => 'A5DDBEA2-B3C8-43F9-8151-33343CBDC857'),
  array('project_id' => 'ABABD13C-A69F-499E-CA7F-5118F3684E4D'),
  array('project_id' => 'B1DAC335-4DC6-4A57-622E-75BF28BA598D'),
  array('project_id' => 'B4345F6A-F926-4062-144E-3FBC175CC7B6'),
  array('project_id' => 'B7047162-6906-4A5E-13C0-B5B86A108510'),
  array('project_id' => 'B7F85EB6-4BF5-43FA-98E7-FF8FAF1AA452'),
  array('project_id' => 'BC90D462-6639-4087-8527-6BB9E528E07D'),
  array('project_id' => 'C5B4F73B-5EF8-4099-590E-B0275EDBA2A7'),
  array('project_id' => 'CBB6B9F7-500C-4DDD-71AA-4D5E5B96CDBB'),
  array('project_id' => 'CDD40C27-62ED-4966-AF3D-E781DD0D4846'),
  array('project_id' => 'CF179695-1E6A-440F-1DDB-4FEA7B02A5B5'),
  array('project_id' => 'CF6E1364-D6EF-4042-B726-82CFB73F7C9D'),
  array('project_id' => 'D297CD29-50CA-4B2C-4A07-498ADF3AF487'),
  array('project_id' => 'D42FC0EB-61B0-4937-700E-4EFEAB008677'),
  array('project_id' => 'D6B25EC9-2884-4E3C-00E8-0C5A6472FA63'),
  array('project_id' => 'DF043419-F23B-41DA-7E4D-EE52AF22F92F'),
  array('project_id' => 'DTrev1PRJ0000000014'),
  array('project_id' => 'F05ACE4F-9B55-48A0-D640-5276B8B899C7'),
  array('project_id' => 'GBHayPRJ0000000005'),
  array('project_id' => 'GHF1PRJ0000000025'),
  array('project_id' => 'HazorZooPRJ0000000010'),
  array('project_id' => 'HPeaZooPRJ0000000012'),
  array('project_id' => 'MHS1PRJ0000000021'),
  array('project_id' => 'PGold1PRJ0000000005'),
  array('project_id' => 'TESTPRJ0000000004')
);
	
	
	
	function get_metadata(){
		$db = $this->startDB();
		$output = array();
		$output['errors'] = array();
		$output['recs'] = array();
		foreach($this->projects as $proj){
			$uuid = $proj['project_id'];
			$json = file_get_contents("http://opencontext.org/projects/".$uuid.".json");
			$pdata = json_decode($json, 1);
			if(is_array($pdata)){
				/*
				if(isset($pdata['metadata']['subjects'])){
					if(is_array($pdata['metadata']['subjects'])){
						foreach($pdata['metadata']['subjects'] as $sub){
							$value = $sub['value'];
							$value = trim($value);
							$data = array("uuid" => $uuid,
										  "pred_temp" => "subject",
										  "object_temp_label" => $value
										  );
							$where = array();
							$where[] = "uuid = '$uuid'";
							$where[] = "pred_temp = 'subject'";
							$where[] = "object_temp_label = '$value'";
							$db->delete("oc_proj_meta", $where);
							$db->insert("oc_proj_meta", $data);
							$output['recs'][] = $data;
						}
					}
				}
				if(isset($pdata['metadata']['creators'])){
					if(is_array($pdata['metadata']['creators'])){
						foreach($pdata['metadata']['creators'] as $sub){
							$value = $sub['value'];
							$value = trim($value);
							$data = array("uuid" => $uuid,
										  "pred_temp" => "creator",
										  "object_uri" => $sub['uri'],
										  "object_temp_label" => $value
										  );
							$where = array();
							$where[] = "uuid = '$uuid'";
							$where[] = "pred_temp = 'creator'";
							$where[] = "object_temp_label = '$value'";
							$db->delete("oc_proj_meta", $where);
							$db->insert("oc_proj_meta", $data);
							$output['recs'][] = $data;
						}
					}
				}
				*/
			}
			else{
				$output['errors'][$uuid][] = "Bad JSON array";
			}
		}
		return $output;
	}
	
	
	function prep_annotations(){
		$output = array();
		$entities = array();
		$output['tabs'] = array();
		$output['tabs'][] = 'link_annotations';
		$output['tabs'][] = 'link_entities';
		
		$db = $this->startDB();
		$sql = "SELECT * FROM oc_proj_meta WHERE predicate_uri != '' ORDER BY uuid, predicate_uri";
		$result =  $db->fetchAll($sql);
		foreach($result as $row){
			$a_rec = array();
			$a_rec['subject'] = $row['uuid'];
			$a_rec['subject_type'] = 'projects';
			$a_rec['project_uuid'] = $row['uuid'];
			$a_rec['source_id'] = 'edited-proj-meta';
			$a_rec['predicate_uri'] = $row['predicate_uri'];
			$a_rec['object_uri'] = $row['object_uri'];
			$a_rec['creator_uuid'] = '';
			$output['link_annotations'][] = $a_rec;
			
			if(!array_key_exists($row['object_uri'], $entities) && !stristr($row['object_uri'], 'http://opencontext.org')){
				$entities[$row['object_uri']] = $row['object_label'];
				$e_rec = array();
				$e_rec['uri'] =  $row['object_uri'];
				$e_rec['label'] = $row['object_label'];
				$e_rec['alt_label'] = $row['object_label'];
				$e_rec['vocab_uri'] = $row['object_vocab'];
				$output['link_entities'][] = $e_rec;
			}
			
		}
		
		return $output;
	}
	
	function startDB(){ 
		  if(!$this->db){
				$db = Zend_Registry::get('db');
				$this->setUTFconnection($db);
				$this->db = $db;
		  }
		  else{
				$db = $this->db;
		  }
		  
		  return $db;
	}
	 
	function setUTFconnection($db){
		  $sql = "SET collation_connection = utf8_unicode_ci;";
		  $db->query($sql, 2);
		  $sql = "SET NAMES utf8;";
		  $db->query($sql, 2);
    }
	
}  
