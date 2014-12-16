<?php
/*
This class process a work book full of measurements (as saved in a "fods" xml format from Libre Office)
It's specifically designed to parse through the Catalhoyuk type schema for describing measurements

I'm including it, since it may be useful to adapt to other projects

*/
class ProjEdits_CyprusNAA  {
    
   
	
     public $db;
	 public $workbookFile; //filename to parse and load into the database
	 public $importTableName; //name of the import table to add these data too
	 public $doInsert = false;
	 public $doCommentUpdate = false;
	public $project_uuid = 'ABABD13C-A69F-499E-CA7F-5118F3684E4D';
	public $old_proj = '4B16F48E-6F5D-41E0-F568-FCE64BE6D3FA';
	
	
	 const GeoUsername = "ekansa"; //geonames API name
	 const APIsleep = .5; 
	 const GeoNamesBaseURI = "http://www.geonames.org/";
	 const GeoNamesBaseAPI = "http://api.geonames.org/";
	
	
	
	
	
	function geo_uri_lookup(){
		$output = array();
		$db = $this->startDB();
		$sql = "SELECT DISTINCT field_7 AS site,
		field_8 AS gaz_label,
		field_9 AS gaz_uri
		FROM z_4_c5193ec15
		WHERE field_9 = 'http://www.geonames.org/19741'
		";
		
		$result = $db->fetchAll($sql, 2);
		foreach($result as $row){
			$uri = $row['gaz_uri'];
			$uri_type = false;
			$lon = false;
			$lat = false;
			if (stristr($uri, 'http://pleiades.stoa.org/places')){
				$uri_type = 'pleiades';
				$json_url = $uri.'/json';
				sleep(self::APIsleep);
				@$json = file_get_contents($json_url);
				if($json != false){
					$jdata = json_decode($json, 1);
					if(is_array($jdata)){
						$lat = $jdata['reprPoint'][1] + 0;
						$lon = $jdata['reprPoint'][0] + 0;
					}
				}
			}
			if (stristr($uri, 'http://www.geonames.org/')){
				$uri_type = 'geo';
				$id = str_replace('http://www.geonames.org/', '', $uri);
				$xml_url = 'http://sws.geonames.org/'.$id.'/about.rdf';
				sleep(self::APIsleep);
				@$xml = file_get_contents($xml_url);
				if($xml != false){
					$dom = new DOMDocument();
					$dom->loadXML($xml);
					$lons = $dom->getElementsByTagName('long');
					foreach ($lons as $lon_node) {
						$lon =  $lon_node->nodeValue + 0;
					}
					$lats = $dom->getElementsByTagName('lat');
					foreach ($lats as $lat_node) {
						$lat =  $lat_node->nodeValue + 0;
					}
				}
			}
			if($lon != false && $lat != false){
				$output[$uri] = array('lat' => $lat,
									  'lon' => $lon);
				
			}
		}
		return $output;
	}
	
	
	function annotate_naa_fields(){
		$output = array();
		$db = $this->startDB();
		$alt_label_prefix = 'NAA validation - ';
		$sql = "SELECT *
		FROM var_tab
		WHERE project_id = '".$this->project_uuid."'
		AND CHAR_LENGTH(var_label) <= 4
		AND var_type LIKE 'Decimal'
		";
	
		$result = $db->fetchAll($sql, 2);
		foreach($result as $row){
			$use_uuid = $row['variable_uuid'];
			$alt_label = $alt_label_prefix.$row['var_label'];
			$sql = "SELECT *
				FROM var_tab
				WHERE project_id = '".$this->old_proj."'
				AND var_label = '".$alt_label."'
				AND var_type LIKE 'Decimal'
				LIMIT 1;
				";
			
				$resb = $db->fetchAll($sql, 2);
				if($resb){
					$old_uuid = $resb[0]['variable_uuid'];
					$sql = "SELECT * FROM linked_data WHERE itemUUID = '$old_uuid' ";
					$resc = $db->fetchAll($sql, 2);
					foreach( $resc as $rowc){
						$ld = new dataEdit_LinkedData();
						$rp = array("projectUUID" => $this->project_uuid,
									"subjectUUID" =>  $use_uuid,
									"subjectType" => $rowc["itemType"],
									"sourceID" => "script",
									"predicateURI" => $rowc["linkedType"],
									"objectURI" => $rowc["linkedURI"],
									"objectLabel" => $rowc["linkedLabel"],
									"replacePredicate" => 0
									);
						$ld->requestParams = $rp;
						$output[] = $ld->addUpdateLinkedData();
					}
				}
		
		}// end loop
		
		return $output;
	}
	 
	function tp_area_chrono(){
		$db = $this->startDB();
		$output = array();
		$tpProj = "02594C48-7497-40D7-11AE-AB942DC513B8";
		$spaceTimeObj = new  dataEdit_SpaceTime;
		$spaceTimeObj->projectUUID = $tpProj;
		
		$tabs = array("z_ex_catal_tp_age",
					  "z_ex_catal_tp_age_gc",
					  "z_ex_catal_tp_main",
					  "z_ex_catal_tp_main_gc",
					  "z_ex_catal_tp_measurements",
					  "z_ex_catal_tp_measurements_gc"
					  );
		
		
		$sql = "SELECT DISTINCT unit, uuid,
		(field_3 * -1) as bc_early,
		(field_4 * -1) AS bc_late,
		bp_early, bp_late
		FROM z_20_68d76efa0
		WHERE uuid != '';
		";
	
		$result = $db->fetchAll($sql, 2);
		foreach($result as $row){
			
			$unit = $row["unit"];
			$uuid = $row["uuid"];
			$start = $row["bc_early"];
			$end = $row["bc_late"];
			$startBP = $row["bp_early"];
			$endBP = $row["bp_late"];
			
			$requestParams = array();
			$requestParams["uuid"] = $uuid;
			$requestParams["projUUID"] = $tpProj;
			$requestParams["tStart"] = $start;
			$requestParams["tEnd"] = $end;
			$spaceTimeObj->requestParams = $requestParams;
			$spaceTimeObj->chrontoTagItem();
			$output[] = $requestParams;
			
			foreach($tabs as $tab){
				$where = "field_9 = '$unit' ";
				$data = array("field_13" => $startBP,
							  "field_14" => $endBP);
				$db->update($tab, $data, $where);
			}
			
			
		}
	
		return $output;
	}
	 
	 
	 
	 
	 function labelSortUpdate($label, $fieldNum){
		  
		  $db = $this->startDB();
		  $label = addslashes($label);
		  $sql = "SELECT var_label, variable_uuid, sort_order
		  FROM var_tab
		  WHERE source_id = '".$this->importTableName."'
		  AND var_label LIKE '$label%'
		  ";
		  
		  $result = $db->fetchAll($sql, 2);
        if($result){
				foreach($result as $row){
					 $varUUID = $row["variable_uuid"];
					 $sort = $row["sort_order"];
					 if($sort <= 300){
						  $newSort = $fieldNum + 300;
					 }
					 else{
						  $newSort = ($sort + ($fieldNum + 300))/2;
						  $newSort = round($newSort, 0);
					 }
					 
					 $data = array("sort_order" => $newSort);
					 $where = "variable_uuid = '$varUUID' ";
					 $db->update("var_tab", $data, $where);
				}
		  }
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
