<?php
/*
This class edits Murlo data

I'm including it, since it may be useful to adapt to other projects, also it adds a little documentation to my data wrangling

*/
class PyExport_PyData {
    
    public $db;
    public $requestParams;
	public $requiredParmams = array("tab", "start", "recs", "after");
	
	public $oc_documents = 'oc_documents';
	public $oc_mediafiles = 'oc_mediafiles';
	public $oc_persons = 'oc_persons';
	public $oc_subjects = 'oc_subjects';
	public $oc_geospace = 'oc_geospace';
	public $oc_events = 'oc_events';
	public $oc_types = 'oc_types';
	public $oc_strings = 'oc_strings';
	public $oc_assertions = 'oc_assertions';
	public $oc_manifest = 'oc_manifest';
	
	
	public $classArray = array("Coin" => "http://opencontext.org/vocabularies/oc-general/cat-0009",
                    "Pottery" => "http://opencontext.org/vocabularies/oc-general/cat-0010",
                    "Glass" => "http://opencontext.org/vocabularies/oc-general/cat-0011",
                    "Groundstone" => "http://opencontext.org/vocabularies/oc-general/cat-0012",
                    "Small Find" => "http://opencontext.org/vocabularies/oc-general/cat-0008",
                    "Arch. Element" => "http://opencontext.org/vocabularies/oc-general/cat-0013",
                    "Objects" => "http://opencontext.org/vocabularies/oc-general/cat-0008",
					"Figurine" => "http://opencontext.org/vocabularies/oc-general/cat-0047",
					"Sculpture" => "http://opencontext.org/vocabularies/oc-general/cat-0042",
                    
                    "Animal Bone" => "http://opencontext.org/vocabularies/oc-general/cat-0015",
                    "Shell" => "http://opencontext.org/vocabularies/oc-general/cat-0016",
                    "Non Diag. Bone" => "http://opencontext.org/vocabularies/oc-general/cat-0017",
                    "Human Bone" => "http://opencontext.org/vocabularies/oc-general/cat-0018",
                    "Plant Remains" => "http://opencontext.org/vocabularies/oc-general/cat-0019",
						  "Patients" => "http://opencontext.org/vocabularies/oc-general/cat-0037", //human subject
                    
                    "Feature" => "http://opencontext.org/vocabularies/oc-general/cat-0025",
                    "Lot" => "http://opencontext.org/vocabularies/oc-general/cat-0028",
                    "Locus" => "http://opencontext.org/vocabularies/oc-general/cat-0027",
                    "Context" => "http://opencontext.org/vocabularies/oc-general/cat-0024",
                    "Sequence" => "http://opencontext.org/vocabularies/oc-general/cat-0036",
                    "Basket" => "http://opencontext.org/vocabularies/oc-general/cat-0029",
                    "Excav. Unit" => "http://opencontext.org/vocabularies/oc-general/cat-0026",
                    "Stratum" => "http://opencontext.org/vocabularies/oc-general/cat-0038",
                    
						  "Survey Unit" => "http://opencontext.org/vocabularies/oc-general/cat-0021",
                    "Trench" => "http://opencontext.org/vocabularies/oc-general/cat-0031",
                    "Square" => "http://opencontext.org/vocabularies/oc-general/cat-0034",
                    "Area" => "http://opencontext.org/vocabularies/oc-general/cat-0030",
                    "Operation" => "http://opencontext.org/vocabularies/oc-general/cat-0032",
                    "Field Project" => "http://opencontext.org/vocabularies/oc-general/cat-0033",
                    "Mound" => "http://opencontext.org/vocabularies/oc-general/cat-0041",
						  
						  "Hospital" => "http://opencontext.org/vocabularies/oc-general/cat-0040",
						  
						  "Sample" => "http://opencontext.org/vocabularies/oc-general/cat-0043",
						  "Metal" => "http://opencontext.org/vocabularies/oc-general/cat-0043",
						  "Reference Collection" => "http://opencontext.org/vocabularies/oc-general/cat-0045",
						  
						  "Region" => "http://opencontext.org/vocabularies/oc-general/cat-0046",
                    "Site" => "http://opencontext.org/vocabularies/oc-general/cat-0022"
                    );
	
	function getData($requestParams){
		$output = array();
		//$this->addClassURI();
		$ok = $this->validateRequest($requestParams);
		if(!$ok){
			$output['error'] = "Invalid request";
		}
		else{
			if($requestParams['tab'] == $this->oc_documents){
				//do diaries
				$output = $this->Docs();
			}
			elseif($requestParams['tab'] == $this->oc_mediafiles){
				//do diaries
				$output = $this->Media();
			}
			elseif($requestParams['tab'] == $this->oc_persons){
				//do people
				$output = $this->Persons();
			}
			elseif($requestParams['tab'] == $this->oc_subjects){
				//do subjects
				$output = $this->Subjects();
			}
			elseif($requestParams['tab'] == $this->oc_geospace){
				//do geospace
				$output = $this->Geospace();
			}
			elseif($requestParams['tab'] == $this->oc_events){
				//do events
				$output = $this->Events();
			}
			elseif($requestParams['tab'] == $this->oc_types){
				//do events
				$output = $this->Types();
			}
			elseif($requestParams['tab'] == $this->oc_assertions){
				//do assertions
				if($requestParams['sub'] == "contain"){
					
				}
			}
		}
		return $output;
	}
	
	
	function Docs(){
		$requestParams = $this->requestParams;
		$db = $this->startDB();
		$output = array();
		$output['requestParams'] = $requestParams;
		$output['tabs'][] = $this->oc_documents;
		$output['tabs'][] = $this->oc_manifest;
		$output[$this->oc_documents] = array();
		$output[$this->oc_manifest] = array();
		$after = $requestParams["after"];
		$start = $requestParams["start"];
		$recs = $requestParams["recs"];
		
		$sql = "SELECT * FROM
		diary
		WHERE last_modified_timestamp >= '$after'
		AND uuid NOT LIKE 'bad-%'
		ORDER BY uuid
		LIMIT $start, $recs
		";
		
		$result =  $db->fetchAll($sql);
		foreach($result as $row){
			$doc_rec = array();
			$doc_rec['uuid'] = $row['uuid'];
			$doc_rec['project_uuid'] = $row['project_id'];
			$doc_rec['source_id'] = $row['source_id'];
			$doc_rec['content'] = $row['diary_text_original'];
			$sort = $row['sort'];
			
			
			$man_rec = array();
			$man_rec['uuid'] = $row['uuid'];
			$man_rec['project_uuid'] = $row['project_id'];
			$man_rec['source_id'] = $row['source_id'];
			$man_rec['item_type'] = 'documents';
			$man_rec['label'] = $row['diary_label'];
			$man_rec['des_predicate_uuid'] = $this->get_des_predicate($row['uuid']);
			
			$output[$this->oc_documents][] = $doc_rec;
			$output[$this->oc_manifest][] = $man_rec;
		}
		return $output;
	}
	
	function Media(){
		$requestParams = $this->requestParams;
		$db = $this->startDB();
		$output = array();
		$output['requestParams'] = $requestParams;
		$output['tabs'][] = $this->oc_mediafiles;
		$output['tabs'][] = $this->oc_manifest;
		$output[$this->oc_mediafiles] = array();
		$output[$this->oc_manifest] = array();
		$after = $requestParams["after"];
		$start = $requestParams["start"];
		$recs = $requestParams["recs"];
		
		$sql = "SELECT * FROM
		resource
		WHERE last_modified_timestamp >= '$after'
		AND uuid NOT LIKE 'bad-%'
		ORDER BY uuid
		LIMIT $start, $recs
		";
		
		$result =  $db->fetchAll($sql);
		foreach($result as $row){
			$f_rec_b = array();
			$f_rec_b['uuid'] = $row['uuid'];
			$f_rec_b['project_uuid'] = $row['project_id'];
			$f_rec_b['source_id'] = $row['source_id'];
			
			$f_rec_t = $f_rec_b;
			$f_rec_t['file_type'] = 'oc-gen:thumbnail';
			$f_rec_t['file_uri'] = $row['ia_thumb'];
			$f_rec_p = $f_rec_b;
			$f_rec_p['file_type'] = 'oc-gen:preview';
			$f_rec_p['file_uri'] = $row['ia_preview'];
			$f_rec_f = $f_rec_b;
			$f_rec_f['file_type'] = 'oc-gen:fullfile';
			$f_rec_f['file_uri'] = $row['ia_fullfile'];
			
			$sort = $row['res_number'];
			
			$man_rec = array();
			$man_rec['uuid'] = $row['uuid'];
			$man_rec['project_uuid'] = $row['project_id'];
			$man_rec['source_id'] = $row['source_id'];
			$man_rec['item_type'] = 'media';
			$man_rec['label'] = $row['res_label'];
			$man_rec['class_uri'] = 'oc-gen:'.$row['res_archml_type'];
			$man_rec['des_predicate_uuid'] = $this->get_des_predicate($row['uuid']);
			
			$output[$this->oc_mediafiles][] = $f_rec_t;
			$output[$this->oc_mediafiles][] = $f_rec_p;
			$output[$this->oc_mediafiles][] = $f_rec_f;
			$output[$this->oc_manifest][] = $man_rec;
		}
		return $output;
	}
	
	function Persons(){
		$requestParams = $this->requestParams;
		$db = $this->startDB();
		$output = array();
		$output['requestParams'] = $requestParams;
		$output['tabs'][] = $this->oc_persons;
		$output['tabs'][] = $this->oc_manifest;
		$output[$this->oc_mediafiles] = array();
		$output[$this->oc_manifest] = array();
		$after = $requestParams["after"];
		$start = $requestParams["start"];
		$recs = $requestParams["recs"];
		
		//a crazy complicated query to get users linked in the data
		$sql = "SELECT users.uuid,
		users.combined_name,
		users.first_name AS given_name,
		users.last_name AS surname,
		users.mid_init,
		users.initials,
		IFNULL(lt.project_id, lo.project_id) AS project_uuid,
		IFNULL(lt.source_id, lo.source_id) AS source_id
		FROM users
		LEFT JOIN links AS lt ON users.uuid = lt.targ_uuid
		LEFT JOIN links AS lo ON users.uuid = lo.origin_uuid
		WHERE (lt.targ_uuid IS NOT NULL OR lo.origin_uuid IS NOT NULL)
		AND users.uuid NOT LIKE 'bad-%'
		GROUP BY users.uuid
		ORDER BY users.uuid
		LIMIT $start, $recs
		";
		
		$result =  $db->fetchAll($sql);
		foreach($result as $row){
			$f_rec = $row;
			$f_rec["foaf_type"] = "foaf:Person";
			$man_rec = array();
			$man_rec['uuid'] = $row['uuid'];
			$man_rec['project_uuid'] = $row['project_uuid'];
			$man_rec['source_id'] = $row['source_id'];
			$man_rec['item_type'] = 'persons';
			$man_rec['label'] = $row['combined_name'];
			$man_rec['class_uri'] = "foaf:Person";
			$man_rec['des_predicate_uuid'] = $this->get_des_predicate($row['uuid']);
			
			$output[$this->oc_persons][] = $f_rec;
			$output[$this->oc_manifest][] = $man_rec;
		}
		
		//now add the persons not in the users table
		$sql = "SELECT persons.uuid,
		persons.combined_name,
		persons.first_name AS given_name,
		persons.last_name AS surname,
		persons.mid_init,
		persons.initials,
		persons.project_id AS project_uuid,
		persons.source_id AS source_id
		FROM persons
		LEFT JOIN users ON users.uuid = persons.uuid
		WHERE (users.uuid IS NULL)
		AND persons.uuid NOT LIKE 'bad-%'
		ORDER BY persons.uuid
		LIMIT $start, $recs
		"; 
		
		$result =  $db->fetchAll($sql);
		foreach($result as $row){
			$f_rec = $row;
			$f_rec["foaf_type"] = "foaf:Person";
			$man_rec = array();
			$man_rec['uuid'] = $row['uuid'];
			$man_rec['project_uuid'] = $row['project_uuid'];
			$man_rec['source_id'] = $row['source_id'];
			$man_rec['item_type'] = 'persons';
			$man_rec['label'] = $row['combined_name'];
			$man_rec['class_uri'] = "foaf:Person";
			$man_rec['des_predicate_uuid'] = $this->get_des_predicate($row['uuid']);
			
			$output[$this->oc_persons][] = $f_rec;
			$output[$this->oc_manifest][] = $man_rec;
		}
		
		return $output;
	}
	
	function Subjects(){
		$requestParams = $this->requestParams;
		$db = $this->startDB();
		$output = array();
		$output['requestParams'] = $requestParams;
		$output['tabs'][] = $this->oc_subjects;
		$output['tabs'][] = $this->oc_manifest;
		$output[$this->oc_subjects] = array();
		$output[$this->oc_manifest] = array();
		$after = $requestParams["after"];
		$start = $requestParams["start"];
		$recs = $requestParams["recs"];
		
		$sql = "SELECT space.uuid,
		space.project_id,
		space.source_id,
		space.label_sort,
		space.space_label,
		space.full_context,
		sp_classes.class_uri
		FROM space
		JOIN sp_classes ON space.class_uuid = sp_classes.class_uuid
		WHERE space.last_modified_timestamp >= '$after'
		AND space.uuid NOT LIKE 'bad-%'
		ORDER BY space.uuid
		LIMIT $start, $recs
		";
		
		$result =  $db->fetchAll($sql);
		foreach($result as $row){
			$sub_rec = array();
			$sub_rec['uuid'] = $row['uuid'];
			$sub_rec['project_uuid'] = $row['project_id'];
			$sub_rec['source_id'] = $row['source_id'];
			$sub_rec['context'] = str_replace('|xx|', '/', $row['full_context']);
			$sort = $row['label_sort'];
			
			
			$man_rec = array();
			$man_rec['uuid'] = $row['uuid'];
			$man_rec['project_uuid'] = $row['project_id'];
			$man_rec['source_id'] = $row['source_id'];
			$man_rec['item_type'] = 'subjects';
			$man_rec['label'] = $row['space_label'];
			$man_rec['class_uri'] = $row['class_uri'];
			$man_rec['des_predicate_uuid'] = $this->get_des_predicate($row['uuid']);
			
			$output[$this->oc_subjects][] = $sub_rec;
			$output[$this->oc_manifest][] = $man_rec;
		}
		return $output;
	}
	
	
	function Geospace(){
		$requestParams = $this->requestParams;
		$db = $this->startDB();
		$output = array();
		$output['requestParams'] = $requestParams;
		$output['tabs'][] = $this->oc_geospace;
		$output[$this->oc_geospace] = array();
		$after = $requestParams["after"];
		$start = $requestParams["start"];
		$recs = $requestParams["recs"];
		
		$sql = "SELECT uuid,
		project_id,
		source_id,
		latitude,
		longitude,
		specificity,
		note,
		geojson_data
		FROM geo_space
		WHERE updated >= '$after'
		AND uuid NOT LIKE 'bad-%'
		
		ORDER BY uuid
		LIMIT $start, $recs
		";
		
		$result =  $db->fetchAll($sql);
		foreach($result as $row){
			$rec = array();
			$rec['uuid'] = $row['uuid'];
			$rec['project_uuid'] = $row['project_id'];
			$rec['source_id'] = $row['source_id'];
			$rec['item_type'] = 'subjects';
			$rec['feature_id'] = 1;
			$rec['meta_type'] = "oc-gen:discovey-location";
			$rec['latitude'] = $row['latitude'];
			$rec['longitude'] = $row['longitude'];
			$rec['specificity'] = $row['specificity'] + 0;
			$rec['note'] = $row['note'];
			$rec['ftype'] = false;
			$jstring = $row['geojson_data'];
			if(strlen($jstring)> 1){
				$json = json_decode($jstring, 1);
				$coordinates = $this->recursiveKeyRetrieve($json, 'coordinates');
				$rec['ftype'] = $this->recursiveKeyRetrieve($json, 'type');
				if(!$rec['ftype']){
					$rec['ftype'] = "Point";
					foreach($coordinates as $citem){
						if(is_array($citem)){
							$rec['ftype'] = "Polygon";
							break;
						}
					}
				}	
			}
			else{
				$rec['ftype'] = "Point";
				$coordinates = array($row['longitude'], $row['latitude']);
			}
			$costring = $this->JSONoutputString($coordinates);
			$rec['coordinates'] = $costring;
			$output[$this->oc_geospace][] = $rec;
		}
		return $output;
	}
	
	
	function Events(){
		$requestParams = $this->requestParams;
		$db = $this->startDB();
		$output = array();
		$output['requestParams'] = $requestParams;
		$output['tabs'][] = $this->oc_events;
		$output[$this->oc_events] = array();
		$after = $requestParams["after"];
		$start = $requestParams["start"];
		$recs = $requestParams["recs"];
		
		$sql = "SELECT uuid,
		project_id,
		creator_uuid AS source_id,
		start_time,
		end_time
		FROM initial_chrono_tag
		WHERE created >= '$after'
		AND uuid NOT LIKE 'bad-%'
		ORDER BY uuid
		LIMIT $start, $recs
		";
		
		$result =  $db->fetchAll($sql);
		foreach($result as $row){
			$rec = array();
			$rec['uuid'] = $row['uuid'];
			$rec['project_uuid'] = $row['project_id'];
			$rec['source_id'] = $row['source_id'];
			$rec['item_type'] = 'subjects';
			$rec['event_id'] = 1;
			$rec['meta_type'] = "oc-gen:formation-use-life";
			$rec['when_type'] = "Interval";
			$rec['feature_id'] = 1;
			$rec['earliest'] = $row['start_time'];
			$rec['start'] = $row['start_time'];
			$rec['stop'] = $row['end_time'];
			$rec['latest'] = $row['end_time'];
			
			$output[$this->oc_events][] = $rec;
		}
		return $output;
	}
	
	
	
	function Types(){
		$requestParams = $this->requestParams;
		$db = $this->startDB();
		$output = array();
		$output['requestParams'] = $requestParams;
		$output['tabs'][] = $this->oc_types;
		$output['tabs'][] = $this->oc_strings;
		$output['tabs'][] = $this->oc_manifest;
		$output[$this->oc_types] = array();
		$output[$this->oc_strings] = array();
		$output[$this->oc_manifest] = array();
		$after = $requestParams["after"];
		$start = $requestParams["start"];
		$recs = $requestParams["recs"];
		
		$sql = "SELECT properties.property_uuid AS uuid,
		properties.project_id,
		properties.source_id,
		properties.variable_uuid,
		properties.value_uuid,
		val_tab.val_text
		FROM properties
		JOIN var_tab ON properties.variable_uuid = var_tab.variable_uuid
		JOIN val_tab ON properties.value_uuid = val_tab.value_uuid
		WHERE properties.last_modified_timestamp >= '$after'
		AND properties.property_uuid NOT LIKE 'bad-%'
		AND (var_tab.var_type LIKE 'Nominal'
		OR
		var_tab.var_type LIKE 'Ordinal'
		)
		ORDER BY properties.property_uuid
		LIMIT $start, $recs
		";
		
		$result =  $db->fetchAll($sql);
		foreach($result as $row){
			$t_rec = array();
			$t_rec['uuid'] = $row['uuid'];
			$t_rec['project_uuid'] = $row['project_id'];
			$t_rec['source_id'] = $row['source_id'];
			$t_rec['predicate_uuid'] = $row['variable_uuid'];
			$t_rec['content_uuid'] = $row['value_uuid'];
			
			$s_rec = array();
			$s_rec['uuid'] = $row['value_uuid'];
			$s_rec['project_uuid'] = $row['project_id'];
			$s_rec['source_id'] = $row['source_id'];
			$s_rec['content'] = $row['val_text'];
			
			$man_rec = array();
			$man_rec['uuid'] = $row['uuid'];
			$man_rec['project_uuid'] = $row['project_id'];
			$man_rec['source_id'] = $row['source_id'];
			$man_rec['item_type'] = 'types';
			$man_rec['label'] = $row['val_text'];
			$man_rec['des_predicate_uuid'] = '';
			
			$output[$this->oc_types][] = $t_rec;
			$output[$this->oc_strings][] = $s_rec;
			$output[$this->oc_manifest][] = $man_rec;
		}
		return $output;
	}
	
	
	
	
	
	function recursiveKeyRetrieve($json, $findKey){
		//roots around looking for a key in an array (like Geojson)
		$output = false;
		if(is_array($json)){
			foreach($json as $subJSON){
				if(is_array($subJSON)){
					if(array_key_exists($findKey, $subJSON)){
						$output = $subJSON[$findKey];
						break;
					}
				}
				if(!is_array($output)){
					$output = $this->recursiveKeyRetrieve($subJSON, $findKey);
				}
			}
		}
		return $output;
	}
	
	
	function get_des_predicate($itemUUID){
		//get a uuid for a descriptive variable
		$output = "";
		$db = $this->startDB();
		
		$sql = "SELECT obs.subject_uuid, lo.labelVarUUID
		FROM observe AS obs
		JOIN properties AS props ON obs.property_uuid = props.property_uuid
		JOIN labeling_options AS lo ON lo.labelVarUUID = props.variable_uuid
		WHERE obs.subject_uuid = '$itemUUID'
		LIMIT 1;
		";
		
		$result =  $db->fetchAll($sql);
		if($result){
			$output = $result[0]['labelVarUUID'];
		}
		return $output;
	}
	
	//adds class URIs to the Penelope class list
	function addClassURI(){
		$classURIs = $this->classArray;
		$output = "";
		$db = $this->startDB();
		$sql = "SELECT class_uuid, class_label
		FROM sp_classes
		WHERE 1
		";
		$result =  $db->fetchAll($sql);
		foreach($result as $row){
			$uuid = $row["class_uuid"];
			$label = $row["class_label"];
			$lcLabel = strtolower($label);
			foreach($classURIs as $lkey => $luri){
				$lclkey = strtolower($lkey);
				if($lclkey == $lcLabel){
					$uriex = explode("/", $luri);
					$suffix = $uriex[count($uriex)-1];
					$prefURI = "oc-gen:".$suffix;
					$where = "class_uuid = '$uuid' ";
					$data = array("class_uri" => $prefURI);
					$db->update("sp_classes", $data, $where);
				}
			}
		}
		
	}
	
	
	//convert an array into a well-formatted JSON string
	function JSONoutputString($array){
		  return json_encode($array, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
		  //return json_encode($array, 0);
	}
	
	function validateRequest($requestParams){
		//makes sure the request is valid
		$ok = true;
		foreach($this->requiredParmams as $rparam){
			if(!array_key_exists($rparam, $requestParams)){
				$ok = false;
				break;
			}
		}
		if($ok){
			if(!array_key_exists("sub", $requestParams)){
				$requestParams["sub"] = false;
			}
			$this->requestParams = $requestParams;
		}
		return $ok;
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
