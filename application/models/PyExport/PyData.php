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
	public $oc_manifest = 'oc_manifest';
	
	function getData($requestParams){
		$output = array();
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
				//do diaries
				$output = $this->Person();
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
	
	function Person(){
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
