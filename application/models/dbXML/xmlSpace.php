<?php

class dbXML_xmlSpace  {
    
    public $itemObj;
    public $doc;
    public $root;
    
    const NSarchaeoML = "http://ochre.lib.uchicago.edu/schema/SpatialUnit/SpatialUnit.xsd";
    const NSocSpace = "http://opencontext.org/schema/space_schema_v1.xsd";
    const NSdc = "http://purl.org/dc/elements/1.1/";
    const NSgml = "http://www.opengis.net/gml";
    const NSxhtml = "http://www.w3.org/1999/xhtml";
	 const NSkml = "http://www.opengis.net/kml/2.2";
    
    const classIconRoot = "http://opencontext.org/database/ui_images/med_oc_icons/";
    const spaceRootURI = "http://opencontext.org/subjects/";
    
    public function initialize(){
		  $itemObj = $this->itemObj;
		  $doc = new DOMDocument("1.0", "utf-8");
		  $doc->formatOutput = true;
		  $root = $doc->createElement("arch:spatialUnit");
		  $root->setAttribute("xmlns:arch", self::NSarchaeoML);
		  $root->setAttribute("xmlns:oc", self::NSocSpace);
		  $root->setAttribute("xmlns:dc", self::NSdc);
		  $root->setAttribute("xmlns:gml", self::NSgml);
		  
		  $root->setAttribute("UUID", $itemObj->itemUUID);
		  $root->setAttribute("ownedBy", $itemObj->projectUUID);
		  $doc->appendChild($root);
		  $this->doc = $doc;
		  $this->root = $root;
    }
    
    public function addNameClass(){
        
        $doc = $this->doc;
		  $root = $this->root;
		  $itemObj = $this->itemObj;
		  
		  //name construction
		  $element = $doc->createElement("arch:name");
		  $elementB = $doc->createElement("arch:string");
		  $elementBtext = $doc->createTextNode($itemObj->label);
		  $elementB->appendChild($elementBtext);
		  $element->appendChild($elementB);
		  $root->appendChild($element);
		  
		  //class construction
		  $element = $doc->createElement("oc:item_class");
		  $elementB = $doc->createElement("oc:name");
		  $elementBtext = $doc->createTextNode($itemObj->className);
		  $elementB->appendChild($elementBtext);
		  $element->appendChild($elementB);
		  
		  $elementB = $doc->createElement("oc:iconURI");
		  $elementBtext = $doc->createTextNode(self::classIconRoot.$itemObj->largeClassIcon);
		  $elementB->appendChild($elementBtext);
		  $element->appendChild($elementB);
		  
		  $root->appendChild($element);
		  
    }

    
    
    public function addObsPropsLinks(){
        
        $doc = $this->doc;
		  $root = $this->root;
		  $itemObj = $this->itemObj;
		  
		  $linkCount = false;
		  if(isset($itemObj->linksObj->links)){
				$allLinks = $itemObj->linksObj->links;
				if(is_array($allLinks)){
					 $linkCount = count($allLinks);
				}
		  }
		  
		  
		  
		  if((count($itemObj->observations)>0 && $itemObj->observations != false) || $linkCount != false){
	  
				$allProperties = $itemObj->propertiesObj->properties;
				$allLinks = $itemObj->linksObj->links;
				$allNotes = $itemObj->propertiesObj->notes;
				
				$spaceLinks = $itemObj->linksObj->spaceLinks;
				$mediaLinks = $itemObj->linksObj->mediaLinks;
				$personLinks = $itemObj->linksObj->personLinks;
				$documentLinks = $itemObj->linksObj->documentLinks;
				
	  
				$element =  $doc->createElement("arch:observations");
				
				//print XML tags for an observation even if there's no observations array and if there are links
				if(!is_array($itemObj->observations) && $linkCount != false){
					 $newObs = array();
					 $newObs[]["obs_num"] = 1;
					 $itemObj->observations = $newObs;
				}
				
				$obsDataNumbers = array();
				foreach($itemObj->observations as $obs){
					 $doObs = false;
					 if(!in_array($obs["obs_num"],$obsDataNumbers)){
						  $obsDataNumbers[] = $obs["obs_num"];
						  $doObs = true;
					 }
					 
					 if($linkCount > 0 && !$doObs){
						  $doObs = true;
					 }
					 
					 
					 if($doObs){
						  $elementB =  $doc->createElement("arch:observation");
						  $elementB->setAttribute("obsNumber", $obs["obs_num"]);
						  
						  if(isset($obs["sourceID"])){
								//add observation metadata
								$elementC = $doc->createElement("oc:obs_metadata");
								$elementD = $doc->createElement("oc:source");
								$elementDtext = $doc->createTextNode($obs["sourceID"]);
								$elementD->appendChild($elementDtext);
								$elementC->appendChild($elementD);
						 
								if(isset($obs["obs_name"])){
									$elementD = $doc->createElement("oc:name");
									$elementDtext = $doc->createTextNode($obs["obs_name"]);
									$elementD->appendChild($elementDtext);
									$elementC->appendChild($elementD);
								}
								
								if(isset($obs["obs_type"])){
									$elementD = $doc->createElement("oc:type");
									$elementDtext = $doc->createTextNode($obs["obs_type"]);
									$elementD->appendChild($elementDtext);
									$elementC->appendChild($elementD);
								}
								
								if(isset($obs["obs_note"])){
									$elementD = $doc->createElement("oc:note");
									$elementDtext = $doc->createTextNode($obs["obs_note"]);
									$elementD->appendChild($elementDtext);
									$elementC->appendChild($elementD);
								}
								
								$elementB->appendChild($elementC);
						  }
						 
						  if(is_array($allProperties)){
								if(array_key_exists($obs["obs_num"], $allProperties)){
									 $obsProps = $allProperties[$obs["obs_num"]];
									 if(count($obsProps)>0){
										  $xmlProperties = new dbXML_xmlProperties;
										  $xmlProperties->doc = $doc;
										  $xmlProperties->rootNode = $elementB;
										  $xmlProperties->properties = $obsProps;
										  $xmlProperties->addProperties();
									 }
								}
						  }
						 
						  if(is_array($allLinks)){
								if(array_key_exists($obs["obs_num"], $allLinks)){
									 $obsLinks = $allLinks[$obs["obs_num"]];
									 
									 $obsSpace = false;
									 if($spaceLinks != false){
										  if(array_key_exists($obs["obs_num"], $spaceLinks)){
												$obsSpace = $spaceLinks[$obs["obs_num"]];
										  }
									 }
									 
									 $obsMedia = false;
									 if($mediaLinks != false){
										  if(array_key_exists($obs["obs_num"], $mediaLinks)){
												$obsMedia = $mediaLinks[$obs["obs_num"]];
										  }
									 }
									 
									 $obsPersons = false;
									 if($personLinks != false){
										  if(array_key_exists($obs["obs_num"], $personLinks)){
												$obsPersons = $personLinks[$obs["obs_num"]];
										  }
									 }
									 
									 $obsDocs = false;
									 if($documentLinks != false){
										  if(array_key_exists($obs["obs_num"], $documentLinks)){
												$obsDocs  = $documentLinks[$obs["obs_num"]];
										  }
									 }
									 
									 
									 if(count($obsLinks)>0){
										  $xmlLinks = new dbXML_xmlLinks;
										  $xmlLinks->doc = $doc;
										  $xmlLinks->rootNode = $elementB;
										  $xmlLinks->links = $obsLinks;
										  $xmlLinks->spaceLinks = $obsSpace;
										  $xmlLinks->mediaLinks = $obsMedia;
										  $xmlLinks->personLinks = $obsPersons;
										  $xmlLinks->documentLinks = $obsDocs;
										  
										  $xmlLinks->addLinks();
									 }
								}
						  }
						 
						  if(is_array($allNotes)){
								if(array_key_exists($obs["obs_num"], $allNotes)){
									 $obsNotes = $allNotes[$obs["obs_num"]];
									 if(count($obsNotes)>0){
											$xmlNotes = new dbXML_xmlNotes;
											$xmlNotes->doc = $doc;
											$xmlNotes->rootNode = $elementB;
											$xmlNotes->notes = $obsNotes;
											$xmlNotes->addNotes();
									 }
								}
						  }
						 
						 $element->appendChild($elementB); //add observation to observations
					 }//end case of unique obs number
				}
				$root->appendChild($element);
		  }
		  
    }//end function
    
    
    
    
    public function addContext(){
        
        $doc = $this->doc;
		  $root = $this->root;
		  $itemObj = $this->itemObj;
			
		  if(is_array($itemObj->describeContain)){
				$xmlContain = new dbXML_xmlContext;
				$xmlContain->containment = $itemObj->describeContain;
				$xmlContain->doc = $doc;
				$xmlContain->rootNode = $root;
				$xmlContain->addContainment();
		  }//case with containment items
    
    }//end function
    
    
     public function addChildren(){
        
        $doc = $this->doc;
		  $root = $this->root;
		  $itemObj = $this->itemObj;
			
		  if(is_array($itemObj->children)){
				$treeKey = "default";
				$element = $doc->createElement("oc:children");
				$elementB = $doc->createElement("oc:tree");
				$elementB->setAttribute("id", $treeKey);
				foreach($itemObj->children as $child){
					 
					 $elementC = $doc->createElement("oc:child");
					 $elementC->setAttribute("href", self::spaceRootURI.$child["itemUUID"]);
					 
					 $elementD = $doc->createElement("oc:name");
					 $elementDtext = $doc->createTextNode($child["label"]);
					 $elementD->appendChild($elementDtext); 
					 $elementC->appendChild($elementD); //add name
					 
					 $elementD = $doc->createElement("oc:id");
					 $elementDtext = $doc->createTextNode($child["itemUUID"]);
					 $elementD->appendChild($elementDtext); 
					 $elementC->appendChild($elementD); //add ID
					 
					 $elementD = $doc->createElement("oc:item_class");
					 $elementE = $doc->createElement("oc:name");
					 $elementEtext = $doc->createTextNode($child["className"]);
					 $elementE->appendChild($elementEtext);
					 $elementD->appendChild($elementE); //add a className
					 
					 $elementE = $doc->createElement("oc:iconURI");
					 $elementEtext = $doc->createTextNode(self::classIconRoot.$child["smallClassIcon"]);
					 $elementE->appendChild($elementEtext);
					 $elementD->appendChild($elementE); //add a className
					 
					 $elementC->appendChild($elementD); //add class
					 if(isset($child["descriptor"])){
						  if($child["descriptor"] != false){
								$elementD = $doc->createElement("oc:descriptor");
								$elementDtext = $doc->createTextNode($child["descriptor"]);
								$elementD->appendChild($elementDtext); 
								$elementC->appendChild($elementD); //add descriptor
						  }
					 }
					 
						  
					 $elementB->appendChild($elementC);
				}
				$element->appendChild($elementB);
				$root->appendChild($element);
		  }//case with containment items
    
    }//end function
    
    
    
    public function addMetadata(){
        
        $doc = $this->doc;
		  $root = $this->root;
		  $itemObj = $this->itemObj;
			
		  $xmlMetadata = new dbXML_xmlMetadata;
		  $xmlMetadata->metadata = $itemObj->metadataObj;
		  $xmlMetadata->label = $itemObj->label;
		  $xmlMetadata->className = $itemObj->className;
		  $xmlMetadata->contributors = $itemObj->linksObj->contributors;
		  $xmlMetadata->itemType = "spatial";
		  $xmlMetadata->itemUUID = $itemObj->itemUUID;
		  $xmlMetadata->geoData = array("geoLat" => $itemObj->geoLat,
							  "geoLon" => $itemObj->geoLon,
							  "geoGML" => $itemObj->geoGML,
							  "geoKML" => $itemObj->geoKML,
							  "geoSource" => $itemObj->geoSource,
							  "geoSourceName" => $itemObj->geoSourceName,
							  "geoSpecificity" => $itemObj->geoSpecificity,
							  "geoNote" => $itemObj->geoNote
							  );
		  $xmlMetadata->chronoData = $itemObj->chronoArray;
		  
		  $xmlMetadata->doc = $doc;
		  $xmlMetadata->rootNode = $root;
		  $xmlMetadata->addMetadata();
	
    }//end function
    
    
	 
	 
	 
	function nameSpaces(){
		  $nameSpaceArray = array("oc" => self::NSocSpace,
					  "dc" =>	self::NSdc,
					  "arch" => self::NSarchaeoML,
					  "gml" =>  self::NSgml,
					  "kml" =>  self::NSkml,
					  "xhtml" =>  self::NSxhtml);
		  
		  return $nameSpaceArray;
    }
    
    
	 //format multiple valued variables
    function consolidateMultiValueVars($xml_string){
		  	
		  $dom = new DOMDocument("1.0", "utf-8");
		  $dom->loadXML($xml_string);
		  $dom->formatOutput = true;
		  $xpath = new DOMXpath($dom);
		  $NSarray = $this->nameSpaces();
		  foreach($NSarray as $prefix => $uri){
				$xpath->registerNamespace($prefix, $uri);
		  }
		  
		  $query = "//arch:observation";
		  $obsList = $xpath->query($query, $dom);
		  if(!is_null($obsList)){
				foreach($obsList as $obsNode){
					 $varArray = array();
					 $query = "arch:properties/arch:property/arch:variableID";
					 $varList = $xpath->query($query, $obsNode);
					 if(!is_null($varList)){
						  foreach($varList as $varNode){
								$varID =  $varNode->nodeValue;
								if(array_key_exists($varID , $varArray)){
									 $varArray[$varID]++;
								}
								else{
									 $varArray[$varID] = 1;
								}
						  }
					 }
					 if(count($varArray)>0){
						  foreach($varArray as $varID => $varCount){
								if($varCount > 1){
									   $showLink = false;
									 $query = "arch:properties/arch:property[arch:variableID = '$varID']";
									 $propList = $xpath->query($query, $obsNode);
									 if(!is_null($propList)){
										  $i = 0;
										  $propVals = array();
										  foreach($propList as $propNode){
												$propID = false;
												$query = "oc:propid";
												$propIdNode = $xpath->query($query, $propNode);
												if(!is_null($propIdNode)){
													 $propID = $propIdNode->item(0)->nodeValue;
												}
												$value = null;
												$query = "oc:show_val";
												$valNodes = $xpath->query($query, $propNode);
												if(!is_null($valNodes)){
													 $value = $valNodes->item(0)->nodeValue;
													 //$valNodes->item(0)->nodeValue = null;
													 if($i > 0){
														  $propNode->removeChild($valNodes->item(0));
													 }
												}
												if($propID != false && $value != null){
													 $propVals[$propID] = $value;
												}
												if($i == 0){
													 $query = "oc:var_label";
													 $varNodes = $xpath->query($query, $propNode);
													 $queryType = "oc:var_label/@type";
													 $varTypeNodes = $xpath->query($queryType, $propNode);
													 if(!is_null($valNodes)){
														  $varType = $varTypeNodes->item(0)->nodeValue;
														  if(!stristr( $varType, "alpha")){
															   $showLink = true;
														  }
													 }
													 
													 //$varNodes->item(0)->setAttribute("type", "alphanumeric");
													 $varNodes->item(0)->setAttribute("type", "multivalue");
												}
												$i++;
										  }
										  $i = 0;
										  if(count($propVals) > 0){
												/*
												$multiValueNode = $dom->createElement("oc:show_val");
												$multiValueNode->setAttribute("type", "xhtml");
												$divNode = $dom->createElement("div");
												$divNode->setAttribute("xmlns", "http://www.w3.org/1999/xhtml");
												$multiValueNode->appendChild($divNode);
												$divNodeA = $dom->createElement("div");
												$divNode->appendChild($divNodeA);
												$listNode = $dom->createElement("ul");
												$divNodeA->appendChild($listNode);
												foreach($propVals as $propUUID => $value){
													 $itemNode = $dom->createElement("li");
													 $itemNode->setAttribute("id", $propUUID);
													 $itemText = $dom->createTextNode($value);
													 $itemNode->appendChild($itemText);
													 $listNode->appendChild($itemNode);
												}
												$propList->item(0)->appendChild($multiValueNode);
												*/
												
												$multiValueNode = $dom->createElement("oc:show_values");
												$multiValueNode->setAttribute("showLink", $showLink);
												foreach($propVals as $propUUID => $value){
													 $itemNode = $dom->createElement("oc:show_val");
													 $itemNode->setAttribute("propUUID", $propUUID);
													 $itemText = $dom->createTextNode($value);
													 $itemNode->appendChild($itemText);
													 $multiValueNode->appendChild($itemNode);
												}
												$propList->item(0)->appendChild($multiValueNode);
										  }
									 }
								}
						  }
					 }
				}
		  }
		  
		  return $dom->saveXML();
	 }
	
	
}  
