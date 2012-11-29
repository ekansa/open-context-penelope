<?php

class dataEdit_editMedia  {
    
	const saltPrefix = "blubbie";
	const URIendpoint = "http://artiraq.org/opencontext/image-move.php";
	
    public $itemUUID;
    public $label;
    public $projectUUID;
    public $sourceID;
    
    /*
    Media Item Specific Data
    */ 
    public $archaeoMLtype; //archaeoML media resource type (image, internal documument, external document)
    public $MIMEtype; //mimtype for the file
    public $imageSize; //number of pixels
    public $fileSizeHuman; //human readable filesize
    public $fileSize; //size of the file (bytes)
    public $fileName; //name of the file
    public $fullURI; //URI to the full file version
    public $previewURI; //URI to the preview file version
    public $thumbURI; //URI to the thumbnail file version
    
    public $propertiesObj; //object for properties
    public $linksObj; // object for links
    public $metadataObj; //object for metadata
    
    public $mediaTypeArray = array(".jpg" => array("archaeoML" => "image",
						   "mime" => "image/jpeg"),
				   ".png" => array("archaeoML" => "image",
						   "mime" => "image/png"),
				   ".tif" => array("archaeoML" => "image",
						   "mime" => "image/tiff"),
				   ".tiff" => array("archaeoML" => "image",
						   "mime" => "image/tiff"),
				   ".pdf" => array("archaeoML" => "acrobat pdf",
						   "mime" => "application/pdf")
				  );
    
    
    public $dbName;
    public $dbPenelope;
    public $db;
    
    
    public $geoLat;
    public $geoLon;
    public $geoGML;
    public $geoKML;
    public $geoSource;
    public $geoSourceName;
    
    public $chronoArray; //array of chronological tags, handled differently from Geo because can have multiple
    
    
    
    
    public function initialize($db = false){
        if(!$db){
            $db_params = OpenContext_OCConfig::get_db_config();
            $db = new Zend_Db_Adapter_Pdo_Mysql($db_params);
            $db->getConnection();
        }
        
        $this->db = $db;
	
	$this->archaeoMLtype = false; //archaeoML media resource type (image, internal documument, external document)
	$this->MIMEtype = false; //mimtype for the file
	$this->imageSize = false; //number of pixels
	$this->fileSizeHuman = false;
	$this->fileSize = false; //size of the file (bytes)
	$this->fileName = false; //name of the file
	$this->fullURI = false; //URI to the full file version
	$this->previewURI = false; //URI to the preview file version
	$this->thumbURI = false; //URI to the thumbnail file version
	
	$this->propertiesObj = false;
	$this->linksObj = false;
	$this->metadataObj = false;
    }
    
    public function getByID($id){
        
        $this->itemUUID = $id;
        $found = false;
        $db = $this->db;
        
        $sql = "SELECT *
        FROM resource
        WHERE uuid = '".$this->itemUUID."' ";
        
        $result = $db->fetchAll($sql, 2);
        if($result){
			$found = true;
            $this->projectUUID = $result[0]["project_id"];
            $this->sourceID = $result[0]["source_id"];
			$this->label = $result[0]["res_label"];
			$this->fileName = $result[0]["res_filename"];
			$this->archaeoMLtype = strtolower($result[0]["res_archml_type"]);
			$this->MIMEtype = strtolower($result[0]["mine_type"]);
			$this->imageSize = $result[0]["size"]+0;
			$this->fileSize = $result[0]["filesize"]+0;
			$this->thumbURI = str_replace(" ", "%20", $result[0]["ia_thumb"]);
			$this->previewURI = str_replace(" ", "%20", $result[0]["ia_preview"]);
			$this->fullURI= str_replace(" ", "%20", $result[0]["ia_fullfile"]);
        }
        
        return $found;
    }
	
	
	public function updateFiles($newThumb, $newPreview, $newFull){
		
		$response = $this->changeServerFiles($newThumb, $newPreview, $newFull); //change filenames on server
		
		$data = array();
	
		if($response["thumb"] == $newThumb){
			$data["ia_thumb"] = $newThumb; //server responded with the new URI for the media file
		}
		if($response["preview"] == $newPreview){
			$data["ia_preview"] = $newPreview; //server responded with the new URI for the media file
		}
		if($response["full"] == $newFull){
			$data["ia_fullfile"] = $newFull; //server responded with the new URI for the media file
			
			if(strstr($this->fullURI, $this->label)){
				//old label is in the old full-file URI
				$newExplode = explode("/", $newFull);
				$newFileName = $newExplode[(count($newExplode)-1)];
				$data["res_label"] = $newFileName;
				$data["res_filename"] = $newFileName;
				$response["newLabel"] = $newFileName;
			}
			
		}
		
		if(count($data)>0){
			//if changes, update the database to reflect the changes.
			$db = $this->db;
			$where = array();
			$where = "uuid = '".$this->itemUUID."' ";
			$db->update("resource", $data, $where);
		}
		
		return $response;
	}
	
	
    public function changeServerFiles($newThumb, $newPreview, $newFull){
		
		/*
		Sends a POST request to change file names / paths on the Open Context media server
		*/
		$errors = array();
		$clientURI = self::URIendpoint;
		$clientParams = array(
				"oldThumb" => $this->thumbURI,
				"oldPreview" => $this->previewURI,
				"oldFull" =>	$this->fullURI, 
				
				"newThumb" => $newThumb,
				"newPreview" => $newPreview,
				"newFull" => $newFull,
				
				"passThumb" => sha1((self::saltPrefix).$newThumb),
				"passPreview" => sha1((self::saltPrefix).$newPreview),
				"passFull" => sha1((self::saltPrefix).$newFull)
				
				);

		$client = new Zend_Http_Client($clientURI, array(
                'maxredirects' => 0,
                'timeout'      => 20));
		
		$client->setParameterPost($clientParams);
        @$response = $client->request('POST');
        if($response){
            $responseJSON = $response->getBody();
            return Zend_Json::decode($responseJSON);
		}
		else{
			return array("thumb" => "Fail",
						 "preview" => "Fail",
						 "full" => "Fail",
						 "errors" => array("Connection failed!"));
		}
	}

    
    
    
}  
