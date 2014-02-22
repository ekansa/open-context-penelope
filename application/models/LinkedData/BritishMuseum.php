<?php

/*
 Interacts with the British Museum
 right now it uses a combination of web scraping (yech) and sparql queries to get the data we need.
 
 It uses the normal collections search because I couldn't figure out how to do a good sparql search against wildcards (with stemming and all that).
 So, this first does a keyword search, and scrapes the HTML results for objectid's
 These object id's then go into a sparql query sent to the BM's sparql endpoint
 
 Thesaurus concept URIs are retrieved. If we get more than 1, then we find the term that approximately matches the initial keyword of our search
 
*/

class LinkedData_BritishMuseum {
 
public $matchTerm; //term to match to the British Museum thesaurus
public $objectIDs; //array of objects returned from a keyword search
public $sparql; //sparql query, available for debugging
public $jsonObj; //json result from British museum sparql

public $colExampleURI = false; //uri of the human readable item with example data
public $LDcolExampleURI = false; //uri of item described with related data
public $LDthesaurusURI = false; //uri of the thesaurus concept found with related data
public $LDthesaurusLabel = false; //label of the theasurus concept

const baseTextSearchURL = "http://www.britishmuseum.org/research/collection_online/search.aspx"; //base URL for HTML text searches
const baseHumanObjectURL = "http://www.britishmuseum.org/research/collection_online/search.aspx"; //base URL for HTML representation of objects
const SPARQLendpoint = "http://collection.britishmuseum.org/sparql"; //endpoint for sparql queries


const sparqlSleep = 1; //time to sleep before submitting a sparql query. don't want to piss of the BM with too many requests in rapid succession!

public $frontendOptions = array(
					  'lifetime' => 72000, // cache lifetime, measured in seconds, 7200 = 2 hours
					  'automatic_serialization' => true
			);
					  
public  $backendOptions = array(
				 'cache_dir' => './cache/' // Directory where to put the cache files
			);


function getItemIDsByKeyword($keyword){
    $keyword = trim($keyword);
    $this->matchTerm = strtolower($keyword);
    $objectIDs = false;
    $url = self::baseTextSearchURL."?searchText=".urlencode($keyword);
    $cache = Zend_Cache::factory('Core',
                             'File',
                             $this->frontendOptions,
                             $this->backendOptions);
		  
	 $cacheID = "bm_".md5($url);
	 if(!$cache_result = $cache->load($cacheID)) {
        @$html = file_get_contents($url);
        if($html){
            $cache->save($html, $cacheID ); //save result to the cache, only if valid JSON
        }
    }
    else{
        $html = $cache_result;
    }
    
    if($html){
        $objectIDs = array();
        $lenHTML = strlen($html);
        $foundobj = true;
        $startPos = strpos($html, "<!-- Search results... -->");
        $obParamLen = strlen("?objectId=");
        while($foundobj){
            $objectPos = strpos($html, "?objectId=", $startPos); //get the position of the object identifier from list of URLs
            if(!$objectPos || $startPos > $lenHTML){
                $foundobj = false;
            }
            else{
                $endObjPos = strpos($html, "&", $objectPos); //the object identifier ends with an &
                if(!$endObjPos){
                    $foundobj = false;
                }
                else{
                    $objectPos = $objectPos + $obParamLen;
                    $idLen = $endObjPos - $objectPos;
                    $objectID = substr($html, $objectPos, $idLen);
                    //$objectID = "$objectPos to $endObjPos";
                    if(is_numeric($objectID)){
                        if(!in_array($objectID, $objectIDs)){
                            $objectIDs[] = $objectID;
                        }
                    }
                    $startPos = $endObjPos;    
                }
            }
        }//end loop
    }//end case with html
    
	 
    $this->objectIDs = $objectIDs;
}

//old

function getTypologyThesaurusLD(){
    
    $this->colExampleURI = false;
    $this->LDcolExampleURI =  false;
    $this->LDthesaurusURI =  false;
    $this->LDthesaurusLabel =  false;
    $this->sparql = false;
    
	 $opts = array('http' =>
		  array(
			 'method'  => 'GET',
			 'header'  => "Accept: application/json;\r\n",
			 'timeout' => 60
		  )
		);
	 
	 $context = stream_context_create($opts);
	 
    if(is_array($this->objectIDs)){
        if(count($this->objectIDs)>0){
            $json = false;
            $objectIDs = $this->objectIDs;
            $objectID = $objectIDs[0];
            
				$url = "http://collection.britishmuseum.org/resource?format=json&uri=http%3A%2F%2Fcollection.britishmuseum.org%2Fid%2Fobject%2F".$objectID;
				$this->colExampleURI = "http://collection.britishmuseum.org/resource?uri=http%3A%2F%2Fcollection.britishmuseum.org%2Fid%2Fobject%2F".$objectID;
				$this->LDcolExampleURI = $url;
				
				
            $cache = Zend_Cache::factory('Core',
                             'File',
                             $this->frontendOptions,
                             $this->backendOptions);
		  
            $cacheID = "bmObj_".$objectID;
				
            if(!$cache_result = $cache->load($cacheID)) {
                sleep(self::sparqlSleep);
                @$json = file_get_contents($url, false, $context);
                if($json){
                    $cache->save($json, $cacheID ); //save result to the cache, only if valid JSON
                }
            }
            else{
                $json = $cache_result;
            }
            
				if($json){
					 @$LDresult = Zend_Json::decode($json);
					 
					 if(is_array($LDresult)){
						  
						 
						  
						  $typeArray = $this->recursiveNodeExplore($LDresult, "http://collection.britishmuseum.org/id/ontology/PX_object_type");
						  if(is_array($typeArray )){
								$this->LDthesaurusURI = $typeArray[0]["value"];
								
								$typeURL = "http://collection.britishmuseum.org/resource?format=json&uri=".urlencode($this->LDthesaurusURI);
								
								$cache = Zend_Cache::factory('Core',
											'File',
											$this->frontendOptions,
											$this->backendOptions);
				
								$cacheID = "bmType_".md5($this->LDthesaurusURI);
								if(!$cache_result = $cache->load($cacheID)) {
									 sleep(self::sparqlSleep);
									 @$jsonT = file_get_contents($typeURL, false, $context);
									 if($jsonT){
										  $cache->save($jsonT, $cacheID ); //save result to the cache, only if valid JSON
									 }
								}
								else{
									 $jsonT = $cache_result;
								}
								
								@$Tresult = Zend_Json::decode($jsonT );
								if(is_array($Tresult)){
					 
									 $labelArray = $this->recursiveNodeExplore($Tresult,"http://www.w3.org/2004/02/skos/core#prefLabel");
									 if(is_array($labelArray)){
										  $this->LDthesaurusLabel = $labelArray[0]["value"];
									 }
									 
								}
						  }
						  
					 }//end case where linked data is an array
				}
				else{
					 echo "no json! ".$url;
					 echo $json;
					 die;
				}
        }
    }
    
    
}//end function



	 function recursiveNodeExplore($arrayNode, $searchKey){
	 
		  $output = false;
		  if(is_array($arrayNode)){
				foreach($arrayNode as $key => $actVals){
					 
					 if($searchKey === $key && !$output){
						  $output = $actVals;
					 }
					 else{
						  if(!$output){
								$output = $this->recursiveNodeExplore($actVals, $searchKey);
						  }
					 }
				}
		  }
		  return $output;
	 }



function getMaterialsThesaurusLD(){
    
    $this->colExampleURI = false;
    $this->LDcolExampleURI =  false;
    $this->LDthesaurusURI =  false;
    $this->LDthesaurusLabel =  false;
    $this->sparql = false;
    
	 $opts = array('http' =>
		  array(
			 'method'  => 'GET',
			 'header'  => "Accept: application/json;\r\n",
			 'timeout' => 60
		  )
		);
	 
	 $context = stream_context_create($opts);
	 
    if(is_array($this->objectIDs)){
        if(count($this->objectIDs)>0){
            $json = false;
            $objectIDs = $this->objectIDs;
            $objectID = $objectIDs[0];
            
				$url = "http://collection.britishmuseum.org/resource?format=json&uri=http%3A%2F%2Fcollection.britishmuseum.org%2Fid%2Fobject%2F".$objectID;
				$this->colExampleURI = "http://collection.britishmuseum.org/resource?uri=http%3A%2F%2Fcollection.britishmuseum.org%2Fid%2Fobject%2F".$objectID;
				$this->LDcolExampleURI = $url;
				
				
            $cache = Zend_Cache::factory('Core',
                             'File',
                             $this->frontendOptions,
                             $this->backendOptions);
		  
            $cacheID = "bmObj_".$objectID;
				
            if(!$cache_result = $cache->load($cacheID)) {
                sleep(self::sparqlSleep);
                @$json = file_get_contents($url, false, $context);
                if($json){
                    $cache->save($json, $cacheID ); //save result to the cache, only if valid JSON
                }
            }
            else{
                $json = $cache_result;
            }
            
				if($json){
					 @$LDresult = Zend_Json::decode($json);
					 
					 if(is_array($LDresult)){
						  
						 
						  
						  $typeArray = $this->recursiveNodeExplore($LDresult, "http://erlangen-crm.org/current/P45_consists_of");
						  if(is_array($typeArray )){
								$this->LDthesaurusURI = $typeArray[0]["value"];
								
								$typeURL = "http://collection.britishmuseum.org/resource?format=json&uri=".urlencode($this->LDthesaurusURI);
								
								$cache = Zend_Cache::factory('Core',
											'File',
											$this->frontendOptions,
											$this->backendOptions);
				
								$cacheID = "bmType_".md5($this->LDthesaurusURI);
								if(!$cache_result = $cache->load($cacheID)) {
									 sleep(self::sparqlSleep);
									 @$jsonT = file_get_contents($typeURL, false, $context);
									 if($jsonT){
										  $cache->save($jsonT, $cacheID ); //save result to the cache, only if valid JSON
									 }
								}
								else{
									 $jsonT = $cache_result;
								}
								
								@$Tresult = Zend_Json::decode($jsonT );
								if(is_array($Tresult)){
					 
									 $labelArray = $this->recursiveNodeExplore($Tresult,"http://www.w3.org/2004/02/skos/core#prefLabel");
									 if(is_array($labelArray)){
										  $this->LDthesaurusLabel = $labelArray[0]["value"];
									 }
									 
								}
						  }
						  
					 }//end case where linked data is an array
				}
				else{
					 echo "no json! ".$url;
					 echo $json;
					 die;
				}
        }
    }
    
}//end function


function OLD_getMaterialsThesaurusLD(){
    
    $this->colExampleURI = false;
    $this->LDcolExampleURI =  false;
    $this->LDthesaurusURI =  false;
    $this->LDthesaurusLabel =  false;
    $this->sparql = false;
    
    if(is_array($this->objectIDs)){
        if(count($this->objectIDs)>0){
            $json = false;
            $objectIDs = $this->objectIDs;
            $objectID = $objectIDs[0];
            $sparql = "
            SELECT ?s ?oPart ?oThes ?oLab
				WHERE
				{
				  ?s <http://collection.britishmuseum.org/id/crm/bm-extensions/codex_id> '$objectID';
					  <http://collection.britishmuseum.org/id/crm/P46F.is_composed_of> ?oPart.
					  ?oPart <http://collection.britishmuseum.org/id/crm/P45F.consists_of> ?oThes.
					  ?oThes <http://www.w3.org/2004/02/skos/core#prefLabel> ?oLab.
				} LIMIT 10             
            ";
            $this->sparql = $sparql;
            $url = self::SPARQLendpoint."?Syntax=SparqlResults%2FJson&Query=".urlencode($sparql);
            $cache = Zend_Cache::factory('Core',
                             'File',
                             $this->frontendOptions,
                             $this->backendOptions);
		  
            $cacheID = "bmLD_m_".md5($url);
            if(!$cache_result = $cache->load($cacheID)) {
                sleep(self::sparqlSleep);
                @$json = file_get_contents($url);
                if($json){
                    $cache->save($json, $cacheID ); //save result to the cache, only if valid JSON
                }
            }
            else{
                $json = $cache_result;
            }
            
				$this->colExampleURI = self::baseHumanObjectURL."?objectid=".$objectID."&partid=1";
            @$LDresult = Zend_Json::decode($json);
				if(is_array($LDresult)){
					 if(is_array($LDresult["results"]["bindings"])){
						  $this->jsonObj = $LDresult["results"]["bindings"];
						  if(count($LDresult["results"]["bindings"]) == 1){
								$this->LDcolExampleURI =  $LDresult["results"]["bindings"][0]["s"]["value"];
								$this->LDthesaurusURI =  $LDresult["results"]["bindings"][0]["oThes"]["value"];
								$this->LDthesaurusLabel =  $LDresult["results"]["bindings"][0]["oLab"]["value"];
						  }
						  elseif(count($LDresult["results"]["bindings"]) > 1){
								$max_err = 3; //1 error allowed in matches
								
								foreach($LDresult["results"]["bindings"] as $res){
									 $search = new LinkedData_ApproximateSearch;
									 $vocabTerm = $res["oLab"]["value"];
									 $search->prepSearch($vocabTerm, $max_err );
									 $matches = $search->search($this->matchTerm);
									 if(count($matches)>0){
										  $this->LDcolExampleURI = $res["s"]["value"];
										  $this->LDthesaurusURI =  $res["oThes"]["value"];
										  $this->LDthesaurusLabel = $vocabTerm;
									 }
									 unset($search);
								}
								
								if(!$this->LDthesaurusURI){
									 //matches not found by this method of matching the keyword / matchterm with BM thesaurus results, so select the first choice
									 $this->LDcolExampleURI =  $LDresult["results"]["bindings"][0]["s"]["value"];
									 $this->LDthesaurusURI =  $LDresult["results"]["bindings"][0]["oThes"]["value"];
									 $this->LDthesaurusLabel =  $LDresult["results"]["bindings"][0]["oLab"]["value"];
								}
						  }
					 }//end case with binding array
				}//end case where linked data is an array
        }
    }
    
}//end function









}//end class

?>
