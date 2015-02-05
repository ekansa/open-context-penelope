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
	
	 function tab_note(){
		  $output = array();
		  $propid =  'F3660BB9-EF17-405D-7F1D-87ABBF03EF7B';
		  $db = $this->startDB();
		  $sql = "SELECT uuid
		  FROM space
		  WHERE space_label LIKE 'Sample Tab. 3:%'
		  AND source_id = 'z_4_c5193ec15'
		  ;
		  ";
		  $db = $this->startDB();
		  $result = $db->fetchAll($sql, 2);
		  foreach($result as $row){
			   $uuid = $row['uuid'];
			   $obsHashText = md5($this->project_uuid . "_" . $uuid . "_" . "1" . "_" . $propid);
			   $where = "hash_obs = '$obsHashText' ";
			   $db->delete("observe", $where);
			   $data = array("project_id"=> $this->project_uuid,
								  "source_id"=> 'script',
								  "hash_obs" => $obsHashText,
								  "subject_type" => 'Locations or Objects',
								  "subject_uuid" => $uuid,
								  "obs_num" => 1,
								  "property_uuid" => $propid);
			   try{            
				   $db->insert("observe", $data);
				   $noteOK = true;
			   } catch (Exception $e) {
				   echo $e->getMessage(), "\n";
				   $noteOK = false;
			   }
		  }
		  return $output;
	 }
	
	
	function chrono_lookup(){
		  $output = array();
		  $db = $this->startDB();
		  $sql = "SELECT DISTINCT field_1 AS item,
		  field_42 AS tStart,
		  field_43 AS tEnd
		  FROM z_4_c5193ec15
		  ";
		  
		  $db = $this->startDB();
		  $result = $db->fetchAll($sql, 2);
		  foreach($result as $row){
			   $item = $row['item'];
			   $item = "Sample Tab. 3: ".$item;
			   $uuid = $this->getSiteUUID($item);
			   if ($uuid != false){
					$rp = array("projUUID" => $this->project_uuid,
								 "uuid" =>  $uuid,
								 'tStart' => $row['tStart'],
								 'tEnd' => $row['tEnd']);
					$st = new dataEdit_SpaceTime;
					$st->requestParams = $rp;
					$res = $st->chrontoTagItem();
					$output[$uuid] = array('site' => $item,
										   'uuid' => $uuid,
										   'time' => $rp,
										   'res' => $res);
			   }
		  }
	 return $output;
	}
	
	
	function geo_sup(){
		  $output = array();
		  $db = $this->startDB();
		  $sql = "SELECT DISTINCT field_6 AS site,
		  field_11 AS lon,
		  field_12 AS lat
		  FROM z_4_5c4dc3344
		  WHERE 1
		  ";
		  $db = $this->startDB();
		  $result = $db->fetchAll($sql, 2);
		  foreach($result as $row){
			   $site = $row['site'];
			   $uuid = $this->getSiteUUID($site);
			   $lon = $row['lon'];
			   $lat = $row['lat'];
			   if($lon != false && $lat != false && $uuid != false){
					$noteres = false;
					$ep = new dataEdit_editProperties;
					$ep->projectUUID = $this->project_uuid;
					$note_uuid = $ep->get_make_ValID('Location information provided by data contributors.');
					$note_prop = $ep->get_make_PropID('NOTES', $note_uuid);
					$obsHashText = md5($this->project_uuid . "_" . $uuid . "_" . "1" . "_" . $note_prop);
					$where = "hash_obs = '$obsHashText' ";
					$db->delete("observe", $where);
					$data = array("project_id"=> $this->project_uuid,
									   "source_id"=> 'script',
									   "hash_obs" => $obsHashText,
									   "subject_type" => 'space',
									   "subject_uuid" => $uuid,
									   "obs_num" => 1,
									   "property_uuid" => $note_prop);
					try{            
						$db->insert("observe", $data);
						$noteOK = true;
					} catch (Exception $e) {
						echo $e->getMessage(), "\n";
						$noteOK = false;
					}
				
					$rp = array("projUUID" => $this->project_uuid,
								 "uuid" =>  $uuid,
								 'lat' => $lat,
								 'lon' => $lon);
					$st = new dataEdit_SpaceTime;
					$st->requestParams = $rp;
					$res = $st->geoTagItem();
					$output[$uuid] = array('site' => $site,
										   'uuid' => $uuid,
										   'lat' => $lat,
										   'lon' => $lon,
										   'noteOK' => $noteOK,
										   'res' => $res);
			   }
		  }
	 return $output;
	 
	 
	}
	
	
	function geo_uri_lookup(){
		$output = array();
		$db = $this->startDB();
		$sql = "SELECT DISTINCT field_7 AS site,
		field_8 AS gaz_label,
		field_9 AS gaz_uri
		FROM z_4_c5193ec15
		WHERE field_9 = 'http://www.geonames.org/19741'
		";
		$sql = "SELECT DISTINCT field_7 AS site,
		field_8 AS gaz_label,
		field_9 AS gaz_uri
		FROM z_4_c5193ec15
		WHERE 1
		
		UNION
		
		SELECT DISTINCT field_6 AS site,
		field_7 AS gaz_label,
		field_36 AS gaz_uri
		FROM z_4_8ff79339a
		WHERE 1
		
		";
		$db = $this->startDB();
		$result = $db->fetchAll($sql, 2);
		foreach($result as $row){
		    $site = $row['site'];
			$uuid = $this->getSiteUUID($site);
			$gaz_label = $row['gaz_label'];
			$prop_uuid = $this->getGazRefPropUUID($gaz_label);
			$uri = $row['gaz_uri'];
			$uri_type = false;
			$lon = false;
			$lat = false;
			
			if (stristr($uri, 'http://pleiades.stoa.org/')){
				$uri_type = '<a target="_blank" href="http://pleiades.stoa.org/">Pleiades Gazetteer</a>';
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
				else{
					$pid = str_replace('http://pleiades.stoa.org/places/', '', $uri) + 0;
					$sql = "SELECT * FROM z_pleiades_basic WHERE id = $pid LIMIT 1;";
					$resp =  $db->fetchAll($sql, 2);
					if($resp){
						 $lat = $resp[0]['reprLat'] + 0;
						 $lon = $resp[0]['reprLong'] + 0;
					}
				}
			}
			if (stristr($uri, 'http://www.geonames.org/')){
				$uri_type = '<a target="_blank" href="http://pleiades.stoa.org/">Geonames.org Gazetteer</a>';
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
			if($lon != false && $lat != false && $uuid != false){
				$noteres = false;
				$ep = new dataEdit_editProperties;
				$ep->projectUUID = $this->project_uuid;
				$note_uuid = $ep->get_make_ValID('Location information from: '.$uri_type);
				$note_prop = $ep->get_make_PropID('NOTES', $note_uuid);
				$obsHashText = md5($this->project_uuid . "_" . $uuid . "_" . "1" . "_" . $note_prop);
				$where = "hash_obs = '$obsHashText' ";
				$db->delete("observe", $where);
			   $data = array("project_id"=> $this->project_uuid,
								  "source_id"=> 'script',
								  "hash_obs" => $obsHashText,
								  "subject_type" => 'space',
								  "subject_uuid" => $uuid,
								  "obs_num" => 1,
								  "property_uuid" => $note_prop);
					try{            
						$db->insert("observe", $data);
						$noteOK = true;
					} catch (Exception $e) {
						echo $e->getMessage(), "\n";
						$noteOK = false;
					}
				
				
				$ldres = false;
				if($prop_uuid != false){
					$ld = new dataEdit_LinkedData();
					$rp = array("projectUUID" => $this->project_uuid,
							    "subjectUUID" =>  $prop_uuid,
							    "subjectType" => "property",
							    "sourceID" => "script",
							    "predicateURI" => "type",
							    "objectURI" => $uri,
							    "objectLabel" => $gaz_label,
							    "replacePredicate" => 1);
				   $ld->requestParams = $rp;
				   $ldres = $ld->addUpdateLinkedData();
				}
				
				$rp = array("projUUID" => $this->project_uuid,
						    "uuid" =>  $uuid,
						    'lat' => $lat,
						    'lon' => $lon);
				$st = new dataEdit_SpaceTime;
				$st->requestParams = $rp;
				$res = $st->geoTagItem();
				$output[$uri] = array('site' => $site,
									  'uuid' => $uuid,
									  'prop_uuid' => $prop_uuid,
									  'lat' => $lat,
									  'lon' => $lon,
									  'noteOK' => $noteOK,
									  'res' => $res,
									  'ldres' => $ldres);
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
	 
	 
	 
	 function getGazRefPropUUID($label){
		  $output = false;
		  $db = $this->startDB();
		  $label = addslashes($label);
		  
		  $sql = "SELECT props.property_uuid AS uuid
		  FROM properties AS props
		  JOIN val_tab AS vt ON props.value_uuid = vt.value_uuid
		  JOIN var_tab AS vrt ON props.variable_uuid = vrt.variable_uuid
		  WHERE vt.val_text = '$label'
		  AND vrt.var_label = 'Gazetteer reference'
		  AND props.project_id = '".$this->project_uuid."'
		  LIMIT 1;
		  ";
		  
		  $result = $db->fetchAll($sql, 2);
		  if($result){
			   $output =  $result[0]["uuid"];
		  }	
		  return $output;
	 }
	 
	 
	 
	 function getSiteUUID($label){
		  $output = false;
		  $db = $this->startDB();
		  $label = addslashes($label);
		  
		  $sql = "SELECT uuid
		  FROM space
		  WHERE space_label = '$label'
		  AND project_id = '".$this->project_uuid."'
		  LIMIT 1;
		  ";
		  
		  $result = $db->fetchAll($sql, 2);
		  if($result){
			   $output =  $result[0]["uuid"];
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
