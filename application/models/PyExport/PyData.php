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
	public $oc_predicates = 'oc_predicates';
	public $oc_assertions = 'oc_assertions';
	public $oc_manifest = 'oc_manifest';
	public $link_entities = 'link_entities';
	public $link_annotations = 'link_annotations';
	
	public $type_mappings = array("location" => "subjects",
								 "space" => "subjects",
								 "media" => "media",
								 "diary" => "documents",
								 "person" => "persons",
								 "project" => "projects",
								 "property" => "types",
								 "prop" => "types",
								 "variable" => "predicates"
								 );
	
	public $pred_mappings = array("alpha" => "xsd:string",
								  "nominal" => "types",
								  "integer" => "xsd:integer",
								  "decimal" => "xsd:double",
								  "calend" => "xsd:date",
								  "boolean" => "xsd:boolean"
								  );
	
	public $linkAnnoType_mappings = array("type" => "skos:closeMatch",
								 "unit" => "rdfs:range",
								 "http://www.w3.org/2004/02/skos/core#closeMatch" => "skos:closeMatch",
								 "Measurement type" => "skos:closeMatch",
								 "technique" => "oc-gen:has-technique",
								 "consists of" => "cidoc-crm:P45_consists_of"
								 );
	
	public $linksToUUIDs = array();
	
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
				//do types
				$output = $this->Types();
			}
			elseif($requestParams['tab'] == $this->oc_strings){
				//do strings
				$output = $this->Strings();
			}
			elseif($requestParams['tab'] == $this->oc_predicates){
				//do assertions
				if($requestParams['sub'] == "variable"){
					$output = $this->Variables();
				}
				elseif($requestParams['sub'] == "link"){
					$output = $this->LinkPreds();
				}
			}
			elseif($requestParams['tab'] == $this->oc_assertions){
				//do assertions
				if($requestParams['sub'] == "contain"){
					$output = $this->Containment();
				}
				elseif($requestParams['sub'] == "property"){
					$output = $this->PropDescriptions();
				}
				elseif($requestParams['sub'] == "links-subjects"){
					$output = $this->Links('subjects');
				}
				elseif($requestParams['sub'] == "links-media"){
					$output = $this->Links('media');
				}
				elseif($requestParams['sub'] == "links-documents"){
					$output = $this->Links('documents');
				}
				elseif($requestParams['sub'] == "links-persons"){
					$output = $this->Links('persons');
				}
			}
			elseif($requestParams['tab'] == $this->link_entities){
				$output = $this->LinkEntities();
			}
			elseif($requestParams['tab'] == $this->link_annotations){
				$output = $this->LinkAnnotations();
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
		$projsTerm = "";
		if(isset($requestParams["project_uuids"])){
			$projs = explode(",", $requestParams["project_uuids"]);
			$projsTerm = false;
			foreach($projs as $project_uuid){
				if(!$projsTerm){
					$projsTerm = " AND ( project_id = '".$project_uuid."'";
				}
				else{
					$projsTerm .= " OR project_id = '".$project_uuid."'";
				}
			}
			$projsTerm .= ")";
		}
		
		$sql = "SELECT * FROM
		diary
		WHERE last_modified_timestamp >= '$after'
		AND uuid NOT LIKE 'bad-%' $projsTerm
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
		$projsTerm = "";
		if(isset($requestParams["project_uuids"])){
			$projs = explode(",", $requestParams["project_uuids"]);
			$projsTerm = false;
			foreach($projs as $project_uuid){
				if(!$projsTerm){
					$projsTerm = " AND ( project_id = '".$project_uuid."'";
				}
				else{
					$projsTerm .= " OR project_id = '".$project_uuid."'";
				}
			}
			$projsTerm .= ")";
		}
		
		$sql = "SELECT * FROM
		resource
		WHERE last_modified_timestamp >= '$after'
		AND uuid NOT LIKE 'bad-%' $projsTerm
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
			
			if(strlen($row['res_archml_type'])<1){
				$row['res_archml_type'] = "image";
			}
			
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
		$projsTerm = "";
		if(isset($requestParams["project_uuids"])){
			$projs = explode(",", $requestParams["project_uuids"]);
			$projsTerm = false;
			foreach($projs as $project_uuid){
				if(!$projsTerm){
					$projsTerm = " AND ( lt.project_id = '".$project_uuid."'";
				}
				else{
					$projsTerm .= " OR lt.project_id = '".$project_uuid."'";
				}
			}
			$projsTerm .= ")";
		}
		
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
		AND users.uuid NOT LIKE 'bad-%' $projsTerm
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
		
		$projsTerm = "";
		if(isset($requestParams["project_uuids"])){
			$projs = explode(",", $requestParams["project_uuids"]);
			$projsTerm = false;
			foreach($projs as $project_uuid){
				if(!$projsTerm){
					$projsTerm = " AND ( persons.project_id = '".$project_uuid."'";
				}
				else{
					$projsTerm .= " OR persons.project_id = '".$project_uuid."'";
				}
			}
			$projsTerm .= ")";
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
		AND persons.uuid NOT LIKE 'bad-%' $projsTerm
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
		
		$projsTerm = "";
		if(isset($requestParams["project_uuids"])){
			$projs = explode(",", $requestParams["project_uuids"]);
			$projsTerm = false;
			foreach($projs as $project_uuid){
				if(!$projsTerm){
					$projsTerm = " AND ( space.project_id = '".$project_uuid."'";
				}
				else{
					$projsTerm .= " OR space.project_id = '".$project_uuid."'";
				}
				$projsTerm .= " OR sc.project_id = '".$project_uuid."'";
			}
			$projsTerm .= ")";
		}
		
		$sql = "SELECT space.uuid,
		space.project_id,
		space.source_id,
		space.label_sort,
		space.space_label,
		space.full_context,
		sp_classes.class_uri
		FROM space
		JOIN sp_classes ON space.class_uuid = sp_classes.class_uuid
		LEFT JOIN space_contain AS sc ON space.uuid = sc.parent_uuid
		WHERE space.last_modified_timestamp >= '$after'
		AND space.uuid NOT LIKE 'bad-%' $projsTerm
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
		
		$projsTerm = "";
		if(isset($requestParams["project_uuids"])){
			$projs = explode(",", $requestParams["project_uuids"]);
			$projsTerm = false;
			foreach($projs as $project_uuid){
				if(!$projsTerm){
					$projsTerm = " AND ( project_id = '".$project_uuid."'";
				}
				else{
					$projsTerm .= " OR project_id = '".$project_uuid."'";
				}
			}
			$projsTerm .= ")";
		}
		
		
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
		AND uuid NOT LIKE 'bad-%' $projsTerm
		
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
		
		$projsTerm = "";
		if(isset($requestParams["project_uuids"])){
			$projs = explode(",", $requestParams["project_uuids"]);
			$projsTerm = false;
			foreach($projs as $project_uuid){
				if(!$projsTerm){
					$projsTerm = " AND ( project_id = '".$project_uuid."'";
				}
				else{
					$projsTerm .= " OR project_id = '".$project_uuid."'";
				}
			}
			$projsTerm .= ")";
		}
		
		$sql = "SELECT uuid,
		project_id,
		creator_uuid AS source_id,
		start_time,
		end_time
		FROM initial_chrono_tag
		WHERE created >= '$after'
		AND uuid NOT LIKE 'bad-%' $projsTerm
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
		
		$projsTerm = "";
		if(isset($requestParams["project_uuids"])){
			$projs = explode(",", $requestParams["project_uuids"]);
			$projsTerm = false;
			foreach($projs as $project_uuid){
				if(!$projsTerm){
					$projsTerm = " AND ( properties.project_id = '".$project_uuid."'";
				}
				else{
					$projsTerm .= " OR properties.project_id = '".$project_uuid."'";
				}
			}
			$projsTerm .= ")";
		}
		
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
		) $projsTerm
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
	
	function Strings(){
		$requestParams = $this->requestParams;
		$db = $this->startDB();
		$output = array();
		$output['requestParams'] = $requestParams;
		$output['tabs'][] = $this->oc_strings;
		$output[$this->oc_strings] = array();
		$after = $requestParams["after"];
		$start = $requestParams["start"];
		$recs = $requestParams["recs"];
		
		$projsTerm = "";
		if(isset($requestParams["project_uuids"])){
			$projs = explode(",", $requestParams["project_uuids"]);
			$projsTerm = false;
			foreach($projs as $project_uuid){
				if(!$projsTerm){
					$projsTerm = " AND ( properties.project_id = '".$project_uuid."'";
				}
				else{
					$projsTerm .= " OR properties.project_id = '".$project_uuid."'";
				}
			}
			$projsTerm .= ")";
		}
		
		$sql = "SELECT properties.property_uuid,
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
		AND (var_tab.var_type LIKE '%alpha%') $projsTerm
		ORDER BY properties.value_uuid
		LIMIT $start, $recs
		";
		
		$result =  $db->fetchAll($sql);
		foreach($result as $row){
			$s_rec = array();
			$s_rec['uuid'] = $row['value_uuid'];
			$s_rec['project_uuid'] = $row['project_id'];
			$s_rec['source_id'] = $row['source_id'];
			$s_rec['content'] = $row['val_text'];
			$output[$this->oc_strings][] = $s_rec;
		}
		return $output;
	}
	
	
	function Variables(){
		$requestParams = $this->requestParams;
		$db = $this->startDB();
		$output = array();
		$output['requestParams'] = $requestParams;
		$output['tabs'][] = $this->oc_predicates;
		$output['tabs'][] = $this->oc_manifest;
		$output[$this->oc_predicates] = array();
		$output[$this->oc_manifest] = array();
		$after = $requestParams["after"];
		$start = $requestParams["start"];
		$recs = $requestParams["recs"];
		
		$projsTerm = "";
		if(isset($requestParams["project_uuids"])){
			$projs = explode(",", $requestParams["project_uuids"]);
			$projsTerm = false;
			foreach($projs as $project_uuid){
				if(!$projsTerm){
					$projsTerm = " AND ( var_tab.project_id = '".$project_uuid."'";
				}
				else{
					$projsTerm .= " OR var_tab.project_id = '".$project_uuid."'";
				}
			}
			$projsTerm .= ")";
		}
		
		
		$sql = "SELECT
		var_tab.variable_uuid AS uuid,
		var_tab.project_id,
		var_tab.source_id,
		var_tab.var_type,
		var_tab.sort_order,
		var_tab.var_label
		FROM var_tab
		WHERE var_tab.last_modified_timestamp >= '$after'
		AND var_tab.variable_uuid NOT LIKE 'bad-%' $projsTerm
		ORDER BY var_tab.source_id, var_tab.sort_order, var_tab.var_label
		LIMIT $start, $recs
		";
		
		$result =  $db->fetchAll($sql);
		foreach($result as $row){
			$p_rec = array();
			$p_rec['uuid'] = $row['uuid'];
			$p_rec['project_uuid'] = $row['project_id'];
			$p_rec['source_id'] = $row['source_id'];
			$p_rec['data_type'] = $this->predicateTypeMap($row['var_type'], 'id');
			$p_rec['sort'] = $row['sort_order'] + 0;
			
			$man_rec = array();
			$man_rec['uuid'] = $row['uuid'];
			$man_rec['project_uuid'] = $row['project_id'];
			$man_rec['source_id'] = $row['source_id'];
			$man_rec['item_type'] = 'predicates';
			$man_rec['label'] = $row['var_label'];
			$man_rec['class_uri'] = 'variable';
			$man_rec['des_predicate_uuid'] = '';
			
			$output[$this->oc_predicates][] = $p_rec;
			$output[$this->oc_manifest][] = $man_rec;
		}
		return $output;
	}
	
	
	function LinkPreds(){
		$requestParams = $this->requestParams;
		$db = $this->startDB();
		$output = array();
		$output['requestParams'] = $requestParams;
		$output['tabs'][] = $this->oc_predicates;
		$output['tabs'][] = $this->oc_manifest;
		$output[$this->oc_predicates] = array();
		$output[$this->oc_manifest] = array();
		$after = $requestParams["after"];
		$start = $requestParams["start"];
		$recs = $requestParams["recs"];
		
		$projsTerm = "";
		if(isset($requestParams["project_uuids"])){
			$projs = explode(",", $requestParams["project_uuids"]);
			$projsTerm = false;
			foreach($projs as $project_uuid){
				if(!$projsTerm){
					$projsTerm = " AND ( links.project_id = '".$project_uuid."'";
				}
				else{
					$projsTerm .= " OR links.project_id = '".$project_uuid."'";
				}
			}
			$projsTerm .= ")";
		}
		
		$sql = "SELECT
		links.project_id,
		links.source_id,
		links.link_type,
		lr.uuid
		FROM links
		LEFT JOIN oc_linking_rels AS lr ON lr.label = links.link_type
		WHERE links.last_modified_timestamp >= '$after'
		AND (lr.project_id IS NULL OR lr.project_id = links.project_id) $projsTerm
		GROUP BY links.link_type
		ORDER BY links.link_type
		LIMIT $start, $recs
		";
		
		$result =  $db->fetchAll($sql);
		$missingUUIDs = false;
		foreach($result as $row){
			$p_rec = array();
			$uuid = $row['uuid'];
			if(strlen($uuid)<1){
				$missingUUIDs = true;
				$newUUID = GenericFunctions::generateUUID();
				$data = array('uuid' => $newUUID,
							  'project_id' => $row['project_id'],
							  'label' => $row['link_type']
							  );
				$db->insert('oc_linking_rels', $data);
			}
			
			$p_rec['uuid'] = $row['uuid'];
			$p_rec['project_uuid'] = $row['project_id'];
			$p_rec['source_id'] = $row['source_id'];
			$p_rec['data_type'] = 'id';
			$p_rec['sort'] = 0;
			
			$man_rec = array();
			$man_rec['uuid'] = $row['uuid'];
			$man_rec['project_uuid'] = $row['project_id'];
			$man_rec['source_id'] = $row['source_id'];
			$man_rec['item_type'] = 'predicates';
			$man_rec['label'] = $row['link_type'];
			$man_rec['class_uri'] = 'link';
			$man_rec['des_predicate_uuid'] = '';
			
			$output[$this->oc_predicates][] = $p_rec;
			$output[$this->oc_manifest][] = $man_rec;
		}
		if($missingUUIDs){
			//get the data again now that we've added a uuid for all linking relations
			$output = $this->LinkPreds();
		}
		return $output;
	}
	
	
	function Containment(){
		$requestParams = $this->requestParams;
		$db = $this->startDB();
		$output = array();
		$output['requestParams'] = $requestParams;
		$output['tabs'][] = $this->oc_assertions;
		$output[$this->oc_assertions] = array();
		$after = $requestParams["after"];
		$start = $requestParams["start"];
		$recs = $requestParams["recs"];
		
		$projsTerm = "";
		if(isset($requestParams["project_uuids"])){
			$projs = explode(",", $requestParams["project_uuids"]);
			$projsTerm = false;
			foreach($projs as $project_uuid){
				if(!$projsTerm){
					$projsTerm = " AND ( cs.project_id = '".$project_uuid."'";
				}
				else{
					$projsTerm .= " OR cs.project_id = '".$project_uuid."'";
				}
			}
			$projsTerm .= ")";
		}
		
		$sql = "SELECT space_contain.project_id,
		space_contain.source_id,
		space_contain.parent_uuid,
		space_contain.child_uuid,
		cs.label_sort
		FROM space_contain
		JOIN space AS ps ON ps.uuid = space_contain.parent_uuid
		JOIN space AS cs ON cs.uuid = space_contain.child_uuid
		WHERE space_contain.parent_uuid NOT LIKE 'bad-%'
		AND space_contain.child_uuid NOT LIKE 'bad-%'
		AND space_contain.parent_uuid NOT LIKE '[ROOT]%'
		AND space_contain.last_modified_timestamp >= '$after' $projsTerm
		ORDER BY space_contain.parent_uuid, cs.label_sort, cs.space_label
		LIMIT $start, $recs
		";
		
		$result =  $db->fetchAll($sql);
		foreach($result as $row){
			$a_rec = array();
			$a_rec['uuid'] = $row['parent_uuid'];
			$a_rec['subject_type'] = 'subjects';
			$a_rec['project_uuid'] = $row['project_id'];
			$a_rec['source_id'] = $row['source_id'];
			$a_rec['obs_node'] = '#contents-1';
			$a_rec['obs_num'] = 1;
			$a_rec['sort'] = 1 + $row['label_sort'];
			$a_rec['visibility'] = 1;
			$a_rec['predicate_uuid'] = 'oc-gen:contains';
			$a_rec['object_uuid'] = $row['child_uuid'];
			$a_rec['object_type'] = 'subjects';
			
			$output[$this->oc_assertions][] = $a_rec;
		}
		return $output;
	}
	
	function PropDescriptions(){
		$requestParams = $this->requestParams;
		$db = $this->startDB();
		$output = array();
		$output['requestParams'] = $requestParams;
		$output['tabs'][] = $this->oc_assertions;
		$output[$this->oc_assertions] = array();
		$after = $requestParams["after"];
		$start = $requestParams["start"];
		$recs = $requestParams["recs"];
		
		$projsTerm = "";
		if(isset($requestParams["project_uuids"])){
			$projs = explode(",", $requestParams["project_uuids"]);
			$projsTerm = false;
			foreach($projs as $project_uuid){
				if(!$projsTerm){
					$projsTerm = " AND ( observe.project_id = '".$project_uuid."'";
				}
				else{
					$projsTerm .= " OR observe.project_id = '".$project_uuid."'";
				}
			}
			$projsTerm .= ")";
		}
		
		$sql = "SELECT observe.project_id,
		observe.source_id,
		observe.subject_uuid,
		observe.subject_type,
		observe.obs_num,
		observe.property_uuid,
		properties.variable_uuid,
		properties.value_uuid,
		properties.val_num,
		properties.val_date,
		val_tab.val_text,
		var_tab.var_type,
		var_tab.sort_order
		FROM observe
		JOIN properties ON observe.property_uuid = properties.property_uuid
		JOIN var_tab ON properties.variable_uuid = var_tab.variable_uuid
		JOIN val_tab ON properties.value_uuid = val_tab.value_uuid
		WHERE observe.subject_uuid NOT LIKE 'bad-%'
		AND observe.property_uuid NOT LIKE 'bad-%'
		AND (observe.obs_num > 0 AND observe.obs_num != 100)
		AND observe.updated >= '$after' $projsTerm
		ORDER BY observe.subject_uuid, var_tab.sort_order
		LIMIT $start, $recs
		";
		
		$result =  $db->fetchAll($sql);
		foreach($result as $row){
			$a_rec = array();
			$a_rec['uuid'] = $row['subject_uuid'];
			$a_rec['subject_type'] = $this->itemTypeMap($row['subject_type']);
			$a_rec['project_uuid'] = $row['project_id'];
			$a_rec['source_id'] = $row['source_id'];
			$a_rec['obs_node'] = '#obs-'.$row['obs_num'];
			$a_rec['obs_num'] = $row['obs_num'] + 0;
			$a_rec['sort'] = 100 + $row['sort_order'];
			$a_rec['visibility'] = 1;
			$a_rec['predicate_uuid'] = $row['variable_uuid'];
			$objectType = $this->predicateTypeMap($row['var_type']);
			if($objectType == 'types'){
				$a_rec['object_uuid'] = $row['property_uuid'];
			}
			elseif($objectType == 'xsd:string'){
				$a_rec['object_uuid'] = $row['value_uuid'];
			}
			else{
				$a_rec['object_uuid'] = '';
				if($objectType == 'xsd:date'){
					if(!stristr($row['val_date'], '0000-00-00')){
						$a_rec['data_date'] = $row['val_date'];
					}
					else{
						$a_rec['data_date'] = date('Y-m-d h:i:s', strtotime($row['val_text']));
						if(is_numeric($a_rec['data_date'])){
							if($a_rec['data_date'] >= 1900 && substr_count($a_rec['data_date'], '-') < 1){
								$a_rec['data_date'] = $a_rec['data_date'].'-01-01';
							}
						}
					}
				}
				else{
					if(is_numeric($row['val_text']) && $row['val_num'] == 0){
						$a_rec['data_num'] = $row['val_text'] + 0;
					}
					elseif($objectType =='xsd:boolean'){
						if(stristr($row['val_text'], 'y') || stristr($row['val_text'], 't') || stristr($row['val_text'], 'p')){
							$a_rec['data_num'] = 1;
						}
						else{
							$a_rec['data_num'] = 0;
						}
					}
					else{
						$a_rec['data_num'] = $row['val_num']+0;	
					}
				}
			}
			$a_rec['object_type'] = $objectType;
			
			$output[$this->oc_assertions][] = $a_rec;
		}
		return $output;
	}
	
	function Links($targetType){
		$requestParams = $this->requestParams;
		$db = $this->startDB();
		$output = array();
		$output['requestParams'] = $requestParams;
		$output['tabs'][] = $this->oc_assertions;
		$output[$this->oc_assertions] = array();
		$after = $requestParams["after"];
		$start = $requestParams["start"];
		$recs = $requestParams["recs"];
		
		$projsTerm = "";
		if(isset($requestParams["project_uuids"])){
			$projs = explode(",", $requestParams["project_uuids"]);
			$projsTerm = false;
			foreach($projs as $project_uuid){
				if(!$projsTerm){
					$projsTerm = " AND ( links.project_id = '".$project_uuid."'";
				}
				else{
					$projsTerm .= " OR links.project_id = '".$project_uuid."'";
				}
			}
			$projsTerm .= ")";
		}
		
		$sql = "SELECT links.project_id,
		links.source_id,
		links.link_type,
		links.origin_type,
		links.origin_uuid,
		links.origin_obs,
		links.targ_type,
		links.targ_uuid,
		t.label_sort AS sort
		FROM links
		JOIN space AS t ON t.uuid = links.targ_uuid
		WHERE links.origin_uuid NOT LIKE 'bad-%'
		AND links.targ_uuid NOT LIKE 'bad-%'
		AND links.last_modified_timestamp >= '$after'
		ORDER BY links.origin_uuid, t.label_sort, t.space_label
		LIMIT $start, $recs
		";
		
		if($targetType == 'media'){
			$sql = "SELECT links.project_id,
			links.source_id,
			links.link_type,
			links.origin_type,
			links.origin_uuid,
			links.origin_obs,
			links.targ_type,
			links.targ_uuid,
			t.res_number AS sort
			FROM links
			JOIN resource AS t ON t.uuid = links.targ_uuid
			WHERE links.origin_uuid NOT LIKE 'bad-%'
			AND links.targ_uuid NOT LIKE 'bad-%'
			AND links.last_modified_timestamp >= '$after' $projsTerm
			ORDER BY links.origin_uuid, t.res_number, t.res_label
			LIMIT $start, $recs
			";	
		}
		elseif($targetType == 'documents'){
			
			$sql = "SELECT links.project_id,
			links.source_id,
			links.link_type,
			links.origin_type,
			links.origin_uuid,
			links.origin_obs,
			links.targ_type,
			links.targ_uuid,
			t.sort AS sort
			FROM links
			JOIN diary AS t ON t.uuid = links.targ_uuid
			WHERE links.origin_uuid NOT LIKE 'bad-%'
			AND links.targ_uuid NOT LIKE 'bad-%'
			AND links.last_modified_timestamp >= '$after' $projsTerm
			ORDER BY links.origin_uuid, t.sort, t.diary_label
			LIMIT $start, $recs
			";
			
		}
		elseif($targetType == 'persons'){
			
			$sql = "SELECT links.project_id,
			links.source_id,
			links.link_type,
			links.origin_type,
			links.origin_uuid,
			links.origin_obs,
			links.targ_type,
			links.targ_uuid,
			50 AS sort
			FROM links
			LEFT JOIN users AS t ON t.uuid = links.targ_uuid
			LEFT JOIN persons AS p ON p.uuid = links.targ_uuid
			WHERE links.origin_uuid NOT LIKE 'bad-%'
			AND links.targ_uuid NOT LIKE 'bad-%'
			AND links.targ_type LIKE '%person%'
			AND (t.uuid IS NOT NULL OR p.uuid IS NOT NULL)
			AND links.last_modified_timestamp >= '$after' $projsTerm
			ORDER BY links.origin_uuid
			LIMIT $start, $recs
			";
			
		}
		
		$result =  $db->fetchAll($sql);
		foreach($result as $row){
			$a_rec = array();
			$a_rec['uuid'] = $row['origin_uuid'];
			$a_rec['subject_type'] = $this->itemTypeMap($row['origin_type']);
			$a_rec['project_uuid'] = $row['project_id'];
			$a_rec['source_id'] = $row['source_id'];
			$a_rec['obs_node'] = '#obs-'.$row['origin_obs'];
			$a_rec['obs_num'] = $row['origin_obs']+0;
			$a_rec['sort'] = 100 + $row['sort'];
			$a_rec['visibility'] = 1;
			$a_rec['predicate_uuid'] = $this->get_linkrel_uuid($row['link_type']);
			$a_rec['object_uuid'] = $row['targ_uuid'];
			$a_rec['object_type'] = $this->itemTypeMap($row['targ_type']);
			
			$output[$this->oc_assertions][] = $a_rec;
		}
		return $output;
	}
	
	
	
	
	
	function LinkEntities(){
		$requestParams = $this->requestParams;
		$db = $this->startDB();
		$output = array();
		$output['requestParams'] = $requestParams;
		$output['tabs'][] = $this->link_entities;
		$output[$this->link_entities] = array();
		$after = $requestParams["after"];
		$start = $requestParams["start"];
		$recs = $requestParams["recs"];
		
		$projsTerm = "";
		if(isset($requestParams["project_uuids"])){
			$projs = explode(",", $requestParams["project_uuids"]);
			$projsTerm = false;
			foreach($projs as $project_uuid){
				if(!$projsTerm){
					$projsTerm = " AND ( fk_project_uuid = '".$project_uuid."'";
				}
				else{
					$projsTerm .= " OR fk_project_uuid = '".$project_uuid."'";
				}
			}
			$projsTerm .= ")";
		}
		
		
		$sql = "SELECT DISTINCT linkedURI, linkedLabel, linkedAbrv, vocabURI
		FROM linked_data
		WHERE created >= '$after' $projsTerm
		ORDER BY vocabURI, linkedLabel
		LIMIT $start, $recs
		";
		
		$result =  $db->fetchAll($sql);
		foreach($result as $row){
			$l_rec = array();
			$l_rec['uri'] = trim($row['linkedURI']);
			$l_rec['label'] = $row['linkedLabel'];
			$l_rec['alt_label'] = $row['linkedAbrv'];
			$l_rec['vocab_uri'] = $row['vocabURI'];
			
			$output[$this->link_entities][] = $l_rec;
		}
		return $output;
	}
	
	function LinkAnnotations(){
		$requestParams = $this->requestParams;
		$db = $this->startDB();
		$output = array();
		$output['requestParams'] = $requestParams;
		$output['tabs'][] = $this->link_annotations;
		$output[$this->link_annotations] = array();
		$after = $requestParams["after"];
		$start = $requestParams["start"];
		$recs = $requestParams["recs"];
		
		$projsTerm = "";
		if(isset($requestParams["project_uuids"])){
			$projs = explode(",", $requestParams["project_uuids"]);
			$projsTerm = false;
			foreach($projs as $project_uuid){
				if(!$projsTerm){
					$projsTerm = " AND ( fk_project_uuid = '".$project_uuid."'";
				}
				else{
					$projsTerm .= " OR fk_project_uuid = '".$project_uuid."'";
				}
			}
			$projsTerm .= ")";
		}
		
		
		$sql = "SELECT fk_project_uuid,
		source_id,
		itemUUID,
		itemType,
		linkedType,
		linkedURI
		FROM linked_data
		WHERE created >= '$after' $projsTerm
		ORDER BY itemUUID
		LIMIT $start, $recs
		";
		
		$result =  $db->fetchAll($sql);
		foreach($result as $row){
			$l_rec = array();
			$l_rec['subject'] = $row['itemUUID'];
			$l_rec['subject_type'] = $this->itemTypeMap($row['itemType']);
			$l_rec['project_uuid'] = $row['fk_project_uuid'];
			$l_rec['source_id'] = $row['source_id'];
			$l_rec['predicate_uri'] = $this->linkAnnotationLinkTypeMap($row['linkedType']);
			$l_rec['object_uri'] = trim($row['linkedURI']);
			
			
			$output[$this->link_annotations][] = $l_rec;
		}
		return $output;
	}
	
	
	function itemTypeMap($penelopeType){
		$output = false;
		foreach($this->type_mappings as $penKey => $python_type){
			if(stristr($penelopeType, $penKey)){
				$output = $python_type;
				break;
			}
		}
		return $output;
	}
	
	function predicateTypeMap($penelopeType, $alt_type = false){
		$output = false;
		foreach($this->pred_mappings as $penKey => $python_type){
			if(stristr($penelopeType, $penKey)){
				$output = $python_type;
				if($python_type == 'types' && $alt_type != false){
					$output = $alt_type;
				}
				break;
			}
		}
		return $output;
	}
	
	function linkAnnotationLinkTypeMap($penelopeType){
		$output = false;
		foreach($this->linkAnnoType_mappings as $penKey => $python_type){
			if(stristr($penelopeType, $penKey)){
				$output = $python_type;
				break;
			}
		}
		return $output;
	}
	
	function get_linkrel_uuid($linkrel){
		//get a uuid for a linking relation
		$output = false;
		$linksToUUIDs = $this->linksToUUIDs;
		if(array_key_exists($linkrel, $linksToUUIDs)){
			$output = $linksToUUIDs[$linkrel];
		}
		else{
			$db = $this->startDB();
			
			$sql = "SELECT *
			FROM oc_linking_rels
			WHERE label LIKE '$linkrel'
			LIMIT 1;
			";
			
			$result =  $db->fetchAll($sql);
			if($result){
				$output = $result[0]['uuid'];
				$linksToUUIDs[$linkrel] = $output;
				$this->linksToUUIDs = $linksToUUIDs;
			}
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
