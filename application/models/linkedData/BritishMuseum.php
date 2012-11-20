<?php

/*
 Interacts with the British Museum
 right now it uses a combination of web scraping (yech) and sparql queries to get the data we need.
 
 It uses the normal collections search because I couldn't figure out how to do a good sparql search against wildcards (with stemming and all that).
 So, this first does a keyword search, and scrapes the HTML results for objectid's
 These object id's then go into a sparql query sent to the BM's sparql endpoint
 
 Thesaurus concept URIs are retrieved. If we get more than 1, then we find the term that approximately matches the initial keyword of our search
 
*/

class linkedData_BritishMuseum {
 
public $matchTerm; //term to match to the British Museum thesaurus
public $objectIDs; //array of objects returned from a keyword search
public $sparql; //sparql query, available for debugging
public $jsonObj; //json result from British museum sparql

public $colExampleURI; //uri of the human readable item with example data
public $LDcolExampleURI; //uri of item described with related data
public $LDthesaurusURI; //uri of the thesaurus concept found with related data
public $LDthesaurusLabel; //label of the theasurus concept

const baseTextSearchURL = "http://www.britishmuseum.org/research/search_the_collection_database/search_results.aspx"; //base URL for HTML text searches
const baseHumanObjectURL = "http://www.britishmuseum.org/research/search_the_collection_database/search_object_details.aspx"; //base URL for HTML representation of objects
const SPARQLendpoint = "http://collection.britishmuseum.org/Sparql"; //endpoint for sparql queries


const sparqlSleep = 1; //time to sleep before submitting a sparql query. don't want to piss of the BM with too many requests in rapid succession!

public $frontendOptions = array(
					  'lifetime' => 7200, // cache lifetime, measured in seconds, 7200 = 2 hours
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
        $startPos = strpos($html, "<div class=\"contentBox\">");
        $obParamLen = strlen("?objectid=");
        while($foundobj){
            $objectPos = strpos($html, "?objectid=", $startPos); //get the position of the object identifier from list of URLs
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


function getTypologyThesaurusLD(){
    
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
            SELECT ?s ?oThes ?oLab
            WHERE
            {
               ?s <http://collection.britishmuseum.org/id/crm/bm-extensions/codex_id> '$objectID';
               <http://collection.britishmuseum.org/id/crm/P2F.has_type> ?oThes.
                    ?oThes <http://www.w3.org/2004/02/skos/core#prefLabel> ?oLab.
            } LIMIT 10            
            ";
            $this->sparql = $sparql;
            $url = self::SPARQLendpoint."?Syntax=SparqlResults%2FJson&Query=".urlencode($sparql);
            $cache = Zend_Cache::factory('Core',
                             'File',
                             $this->frontendOptions,
                             $this->backendOptions);
		  
            $cacheID = "bmLD_".md5($url);
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
            
            $LDresult = Zend_Json::decode($json);
            $this->jsonObj = $LDresult["results"]["bindings"];
            $this->colExampleURI = self::baseHumanObjectURL."?objectid=".$objectID."&partid=1";
            
            if(count($LDresult["results"]["bindings"]) < 2){
                $this->LDcolExampleURI =  $LDresult["results"]["bindings"][0]["s"]["value"];
                $this->LDthesaurusURI =  $LDresult["results"]["bindings"][0]["oThes"]["value"];
                $this->LDthesaurusLabel =  $LDresult["results"]["bindings"][0]["oLab"]["value"];
            }
            else{
                $max_err = 3; //1 error allowed in matches
                
                foreach($LDresult["results"]["bindings"] as $res){
                    $search = new linkedData_ApproximateSearch;
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
        }
    }
    
    
}




}//end class

?>
