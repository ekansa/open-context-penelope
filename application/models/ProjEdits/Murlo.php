<?php
/*
This class edits Murlo data

I'm including it, since it may be useful to adapt to other projects, also it adds a little documentation to my data wrangling

*/
class ProjEdits_Murlo  {
    
    public $projectUUID = "DF043419-F23B-41DA-7E4D-EE52AF22F92F";
    public $db;
    
	function TBMissingLinkMatch(){
		$output = array();
		$source = "Missing-link-gen";
		$output['count'] = 0;
		$output['rootTrenchCount'] = 0;
		$output['missingRootTrenchCount'] = 0;
		$output['rootCount'] = 0;
		$linkObj = new dataEdit_Link;
		$linkObj->projectUUID = $this->projectUUID;
		
		$linking = array("44501E51-F884-428A-9B38-D65C6E7A6D1B" => "FD2FA15C-22AC-4585-360F-8FF50C98791F",
						 "BE877057-B9BB-41CD-B0C5-FE783A502898" => "C334AB97-3C6F-4D01-BA1D-C245A73EDE34"
						 ); //rootDoc => "related space"
		foreach($linking as $originUUID => $targUUID){
			//$newLinkUUID = $linkObj->addLinkingRel($originUUID, "Diary / Narrative", $targUUID, "Locations or Objects", "Documents", $source);
			//$newLinkUUID = $linkObj->addLinkingRel($targUUID, "Locations or Objects", $originUUID, "Diary / Narrative", "link", $source);
		}
		//die;
		
		$db = $this->startDB();  
		$sql = "SELECT uuid FROM diary WHERE 1; ";
		$result =  $db->fetchAll($sql);
		foreach($result as $row){
			$uuid = $row['uuid'];
			
			$sql = "SELECT * FROM links
			WHERE (origin_uuid = '$uuid' AND targ_type = 'Locations or Objects')
			OR (targ_uuid = '$uuid' AND origin_type = 'Locations or Objects')";
			
			$resB = $db->fetchAll($sql);
			if(!$resB){
				$output['count']++;
				
				$sql = "SELECT lo.targ_uuid AS rootUUID, lt.origin_uuid AS spaceUUID
				FROM links AS lo
				JOIN links AS lt ON lo.targ_uuid = lt.targ_uuid
				WHERE lo.origin_uuid = '$uuid' AND lo.link_type = 'Is part of'
				AND lt.origin_type = 'Locations or Objects'
				LIMIT 1; ";
				$resC = $resB = $db->fetchAll($sql);
				if($resC){
					$rootUUID = $resC[0]['rootUUID'];
					$spaceUUID = $resC[0]['spaceUUID'];
					$newLinkUUID = $linkObj->addLinkingRel($uuid, "Diary / Narrative", $spaceUUID, "Locations or Objects", "Documents", $source);
					$output['rootTrenchCount']++;
					$output[$uuid] = array('rootUUID' => $rootUUID, 'spaceUUID' => $spaceUUID);
				}
				else{
					$output['missingRootTrenchCount']++;
					$sql = "SELECT lo.targ_uuid AS rootUUID
					FROM links AS lo
					JOIN links AS lt ON lo.targ_uuid = lt.targ_uuid
					WHERE lo.origin_uuid = '$uuid' AND lo.link_type = 'Is part of'
					LIMIT 1; ";
					$output[$uuid] = $sql;
					$resD = $db->fetchAll($sql);
					if($resD){
						$output['rootCount']++;
						$rootUUID = $resD[0]['rootUUID'];
						$output[$uuid] = array('rootUUID' => $rootUUID);
						
						$sql = "SELECT targ_uuid AS diaryUUID
						FROM links
						WHERE origin_uuid = '$rootUUID' AND link_type = 'Has part' ";
						
						$resE = $db->fetchAll($sql);
						if($resE){
							$spUUIDs = array();
							$output[$uuid]['rootPartCount'] = count($resE);
							$resE[] = array('diaryUUID' => $rootUUID);
							foreach($resE as $rowE){
								$partUUID = $rowE['diaryUUID'];
								$sql = "SELECT diary_text_original FROM diary WHERE uuid = '$partUUID' LIMIT 1; ";
								$resF = $db->fetchAll($sql);
								if($resF){
									$text = $resF[0]["diary_text_original"];
									@$xml = simplexml_load_string($text);
									if($xml){
										foreach($xml->xpath("//a/@href") as $xres){
											$url = (string)$xres;
											if(stristr($url, "http://opencontext.org/subjects/")){
												$lspuuid = str_replace("http://opencontext.org/subjects/", "", $url);
												$spUUIDs[] = $lspuuid;
											}
										}
									}
								}
							}
							$output[$uuid]['linkedSpaceUUIDs'] = $spUUIDs;
							if(count($spUUIDs)>0){
								$qChild = " (";
								$or = "";
								foreach($spUUIDs as $spUUID){
									$qChild .= $or." space_contain.child_uuid = '".$spUUID."' ";
									$or = " OR ";
								}
								$qChild .= " ) ";
								$sql = "SELECT space_contain.parent_uuid, COUNT(space_contain.parent_uuid) AS pCount
								FROM space_contain
								JOIN space ON space.uuid = space_contain.parent_uuid
								WHERE $qChild AND space.class_uuid = '27509E18-E82B-4195-140A-F23033B17EB4'
								GROUP BY space_contain.parent_uuid
								ORDER BY COUNT(space_contain.parent_uuid) DESC
								";
								$output[$uuid]['spQuery'] = $sql;
								$resG = $db->fetchAll($sql);
								if($resG){
									$linkSpaceUUID = $resG[0]["parent_uuid"];
									foreach($resE as $rowE){
										$diaryUUID = $rowE["diaryUUID"];
										$newLinkUUID = $linkObj->addLinkingRel($diaryUUID, "Diary / Narrative", $linkSpaceUUID, "Locations or Objects", "Documents", $source);
									}
								}
								
							}
						}
						
					}
				}
			}
	 
		}
		
		return $output;
	}
	
	function TB_aspFindsMatch(){
		// Looks for javascript link queries to objects, replaces with html

		$output = array();
		$db = $this->startDB();
		$sql = "SELECT uuid FROM diary WHERE 1; ";
	   
		$result =  $db->fetchAll($sql);
		foreach($result as $row){
			$uuid = $row['uuid'];
			$sql = "SELECT diary_text_original FROM diary WHERE uuid = '$uuid' LIMIT 1; ";
			$resF = $db->fetchAll($sql);
			if($resF){
				$change = false;
				$text = $resF[0]["diary_text_original"];
				$newText = $this->aspFindsLinkUpdate($text);
				if($text != $newText){
					$data = array("diary_text_original" => $newText);
					$where = "uuid = '$uuid'";
					$db->update("diary", $data, $where);
				}
			}
		}
		return $output;
	}
	
	
	
    function TBFindsMatch(){
	   $output = array();
	   $db = $this->startDB();
	   
	   $this->TB_aspFindsMatch();
	   
	   
	   $sql = "UPDATE diary
	   SET diary_text_original = REPLACE(diary_text_original, '<?xml:namespace prefix = o ns = \"urn:schemas-microsoft-com:office:office\" /?>', '')
	   WHERE 1;";
	   $db->query($sql, 1);
	   
	   //$sql = "UPDATE diary SET diary_text_original = REPLACE(diary_text_original, 'Â', '') WHERE 1; ";
	   //$db->query($sql, 1);
	   
	   $sql = "SELECT uuid FROM diary WHERE 1; ";
	   
	   //$sql = "SELECT uuid FROM diary WHERE uuid = '001F17AF-B5AE-4C53-93D2-FA080DEF68B5' ; ";
	   
	   $result =  $db->fetchAll($sql);
		foreach($result as $row){
			$uuid = $row['uuid'];
			$sql = "SELECT diary_text_original FROM diary WHERE uuid = '$uuid' LIMIT 1; ";
			$resA =  $db->fetchAll($sql);
			$rawText = $resA[0]['diary_text_original'];
			$revisedText = $this->ObjectIDLinking($rawText);
			if($rawText != $revisedText){
			   $data = array("diary_text_original" => $revisedText);
			   $where = "uuid = '$uuid'";
			   $db->update("diary", $data, $where);
			}
		}
		return $output;
    }
    
    
    
    function TBContentsExtract(){
		$output = array();
		$db = $this->startDB();
		$sql = "UPDATE diary SET diary_text_original = REPLACE(diary_text_original, 'Â', '');";
		//$db->query($sql, 1);
		
		$sql = "SELECT uuid FROM diary WHERE 1; ";
		
		$result =  $db->fetchAll($sql);
		foreach($result as $row){
			$uuid = $row['uuid'];
			$sql = "SELECT diary_text_original FROM diary WHERE uuid = '$uuid' LIMIT 1; ";
			$resA =  $db->fetchAll($sql);
			$rawText = $resA[0]['diary_text_original'];
			$useText = $this->introLinks($rawText);
			$useText = $this->textXMLify($useText);
			$useText = $this->removeBadPtags($useText);
			$useText = $this->textXMLify($useText);
			if($useText != $rawText){
				$data = array("diary_text_original" => $useText);
				$where = "uuid = '$uuid'";
				$db->update("diary", $data, $where);
			}
		}
		return $output;
    }
    
    
    
    
    
    function TBscanPageRangeExtract(){
	   $output = array();
	   $db = $this->startDB();
	   $variableUUID = "20DD8CCD-5A32-4894-5F6F-6374EF166497"; //id for page range
	   $propObj = new dataEdit_Property;
	   $propObj->projectUUID = $this->projectUUID;
	   $propObj->sourceID = "label-range";
	   
	   $sql = "SELECT uuid, res_label FROM resource
	   WHERE source_id = 'z_4_831151a85' ";
	   
	   $result =  $db->fetchAll($sql);
	   foreach($result as $row){
		  $subjectUUID = $row['uuid'];
		  $label = $row['res_label'];
		  if(stristr($label, ':')){
			 $exlab = explode(':', $label);
			 $valText = trim($exlab[1]);
			 $output[$subjectUUID] = array("label" => $label, "pages" => $valText);
			 $output[$subjectUUID]['add'] = $propObj->add_obs_varUUID_value($valText, $variableUUID, $subjectUUID, "Media (various)");
		  }
	   }
	   
	   return $output;
    }
    
    
    function TBmediaLinks(){
	   $output = array();
	   $db = $this->startDB();
	   $orderingVar = "A917F876-2276-43FF-A07C-2882609C2923"; //variable that has the sort order of pages
	   $linkObj = new dataEdit_Link;
	   $linkObj->projectUUID = $this->projectUUID;
	   $source = "PageScan medialinking";
	   
	   //get a list of diaries that have links to media resources
	   $sql = "SELECT DISTINCT origin_uuid
	   FROM links
	   WHERE origin_type = 'Diary / Narrative'
	   AND targ_type = 'Media (various)'
	   ";
	   
	   $result =  $db->fetchAll($sql);
	   foreach($result as $row){
		  $docUUID = $row['origin_uuid'];
		  
		  $sql = "SELECT resource.res_label,
		  properties.val_num,
		  links.targ_uuid AS mediaUUID
		  FROM links
		  JOIN resource ON links.targ_uuid = resource.uuid
		  LEFT JOIN observe ON observe.subject_uuid = links.targ_uuid
		  LEFT JOIN properties ON observe.property_uuid = properties.property_uuid
		  WHERE properties.variable_uuid = '$orderingVar'
		  AND links.origin_uuid = '$docUUID'
		  ORDER BY properties.val_num, resource.res_label
		  ";

		  $prevUUID = false;
		  $i = 0;
		  $resB =  $db->fetchAll($sql);
		  foreach($resB as $rowB){
			 $actUUID = $rowB['mediaUUID'];
			 if($prevUUID != false){
				$output[$actUUID][] = $linkObj->addLinkingRel($actUUID, 'Media (various)', $prevUUID, 'Media (various)', "Previous", $source);
				$output[$actUUID][] = $linkObj->addLinkingRel($prevUUID, 'Media (various)', $actUUID, 'Media (various)', "Next", $source);
			 }
			 $prevUUID = $actUUID;
		  }
	   }// end loop through diaries
	   return $output;
    }
    
    
     
	 function TBaddDiaryProperties(){
		  
		  $output = array();
		  
		  $db = $this->startDB();
		  $propObj = new dataEdit_Property;
		  $propObj->projectUUID = "DF043419-F23B-41DA-7E4D-EE52AF22F92F";
		  $propObj->sourceID = "scrape-props";
		  
		  $varArray = array("date" => "6A359C65-9F07-417A-37F1-881E48669140",
								  "StartPage" => "BECAD1AF-0245-44E0-CD2A-F2F7BD080443",
								  "EndPage" => "506924AA-B53D-41B5-9D02-9A7929EA6D6D",
								  "TrenchBookID" => "DDE6114E-9BB4-40A4-AD80-55FBEAB6663A",
								  "pcURI" => "403CE884-AA17-4932-8507-66E4FD145294"
								  );
		  
		  $varArray = array("pcURI" => "403CE884-AA17-4932-8507-66E4FD145294"
								  );
		  
		  $TBnote =
		  "<p>The Poggio Civitate team scanned and transcribed Trench Books starting in 2001. These were originally
		  made available online at the Poggio Civitate Excavations project <a href=\"http://poggiocivitate.classics.umass.edu/\">website</a>.</p>
		  <p>To improve data longevity and standards compliance, the HTML of the transcribed Trench Books
		  was substantially processed and edited prior to publication in Open Context.</p>
		  ";
		  
		  $sql = "SELECT sc.uuid, sc.TrenchBookID, sc.StartPage, sc.EndPage,
		  sc.pcURI, sc.date
		  FROM z_tb_scrape AS sc
		  LEFT JOIN observe ON observe.subject_uuid = sc.uuid
		  WHERE CHAR_LENGTH(sc.uuid) > 1
		  AND observe.subject_uuid IS NULL
		  ORDER BY sc.sort
		  ";
		  
		  $sql = "SELECT sc.uuid, sc.TrenchBookID, sc.StartPage, sc.EndPage,
		  sc.pcURI, sc.date
		  FROM z_tb_scrape AS sc
		  
		  WHERE CHAR_LENGTH(sc.uuid) > 1
		 
		  ORDER BY sc.sort
		  ";
		  
		  $result =  $db->fetchAll($sql);
		  foreach($result as $row){
				$subjectUUID = $row["uuid"];
				foreach($varArray as $varKey => $variableUUID){
					 $valText = $row[$varKey];
					 
					 if(strstr($valText, "http://")){
						  $propObj->delete_obs_varUUID($variableUUID, $subjectUUID);
						  $valText = "<div>U-Mass site version: <a href=\"".$valText."\">".$valText."</a></div>";
					 }
					 
					 $add = $propObj->add_obs_varUUID_value($valText, $variableUUID, $subjectUUID, "Diary / Narrative");
					 
					 $output[$subjectUUID][$variableUUID] = array("value" => $valText,
																				 "added" => $add);
				}
				
				//$add = $propObj->add_obs_varUUID_value($TBnote, "NOTES", $subjectUUID, "Diary / Narrative");
				$output[$subjectUUID]["note"] = $add;
		  }
		  
		  return $output;
	 }
	 
	 
	 //load the Diary table with scrapped trench books, add links 
	 function TBscrapeDiary(){
		  
		  $db = $this->startDB();
		  $output = array();
		  $source = "scraped-data";
		  
		  $linkObj = new dataEdit_Link;
		  $linkObj->projectUUID = 'DF043419-F23B-41DA-7E4D-EE52AF22F92F';
		  
		  $sql = "SELECT uuid, tbtid, tbtdid, TrenchBookID, label, pagedLabel, StartPage, EndPage,
		  pcURI, prevLink, nextLink
		  FROM z_tb_scrape
		  WHERE CHAR_LENGTH(uuid) > 1
		  ORDER BY TrenchBookID, tbtid, tbtdid
		  ";
		  
		  $result =  $db->fetchAll($sql);
		  foreach($result as $row){
				$uuid = $row["uuid"];
				$label = $row["label"];
				$pagedLabel = $row["pagedLabel"];
				if(strlen($pagedLabel) < 1){
					 $diaryLabel = $label;
				}
				else{
					 $diaryLabel = $pagedLabel;
				}
				
				$TrenchBookID = $row["TrenchBookID"];
				$StartPage = $row["StartPage"];
				$EndPage = $row["EndPage"];
				$prevLink = $row["prevLink"];
				$nextLink = $row["nextLink"];
				$tbtdid = $row["tbtdid"];
				$trenchID = $row["tbtid"];
				
				$linkedPictures = $this->getLinkedTBscans($TrenchBookID, $StartPage, $EndPage, $tbtdid, $label);
				if(is_array($linkedPictures)){
					 $source = "scrape-scans"; 
					 foreach($linkedPictures as $targUUID){
						  $output[$uuid]["scrape-scans"][] = $targUUID;
						  $newLinkUUID = $linkObj->addLinkingRel($uuid, "Diary / Narrative", $targUUID, 'Media (various)', "link", $source);
						  if($tbtdid != 0){
								//add the reciprocal link
								$newLinkUUID = $linkObj->addLinkingRel($targUUID, 'Media (various)', $uuid, "Diary / Narrative", "Scan of", $source);
						  }
					 }
				}
				
				$trenchUUID = $this->getLinkedTrench($trenchID);
				if($trenchUUID != false){
					 
					 $source = "scrape-trench";
					 $output[$uuid]["scrape-trench"][] = $trenchUUID;
					 if($tbtdid == 0){
						  $newLinkUUID = $linkObj->addLinkingRel($trenchUUID, "Locations or Objects", $uuid, "Diary / Narrative", "link", $source);
					 }
					 else{
						  $newLinkUUID = $linkObj->addLinkingRel($uuid, "Diary / Narrative", $trenchUUID, "Locations or Objects", "Documents", $source);
					 }
				}
				
				/*
				$data = array(
					"uuid" => $uuid, 
					 "project_id" => 'DF043419-F23B-41DA-7E4D-EE52AF22F92F',
					 "source_id" => 'z_4_e8169555e',
					 "diary_hash" => md5('DF043419-F23B-41DA-7E4D-EE52AF22F92F'."_".$diaryLabel),
					 "diary_label" => $diaryLabel,
					 "diary_text_original" => "pending"
				);
				
				try{
					 $db->insert("diary", $data);
					 $output[] = $data;
				} catch (Exception $e) {
					 $output[] = "UUID: ".$uuid." Failed";
				}
				*/
				
				/*
				if($row["tbtdid"] == 0){ //first page of a trench book, gets lots of links
					 $sql = "SELECT uuid, tbtid, tbtdid, TrenchBookID, label, pagedLabel, StartPage, EndPage,
					 pcURI, prevLink, NextLink
					 FROM z_tb_scrape
					 WHERE CHAR_LENGTH(uuid) > 1
					 AND tbtdid != 0
					 AND TrenchBookID = $TrenchBookID
					 ORDER BY tbtid, tbtdid
					 ";
					 
					 $resB =  $db->fetchAll($sql);
					 foreach($resB as $rowB){
						  //for the first page of a given trench book, add linking relations to all the later pages
						  $targUUID = $rowB["uuid"];
						  $newLinkUUID = $linkObj->addLinkingRel($uuid, "Diary / Narrative", $targUUID, 'Diary / Narrative', "Has part", $source);
						  $newLinkUUID = $linkObj->addLinkingRel($targUUID, "Diary / Narrative", $uuid, 'Diary / Narrative', "Is part of", $source);
					 }
				}
				
				
				if(strlen($prevLink) > 1){
					 //add a previous link
					 
					 $sql = "SELECT uuid, tbtid, tbtdid, TrenchBookID, label, pagedLabel, StartPage, EndPage,
					 pcURI, prevLink, NextLink
					 FROM z_tb_scrape
					 WHERE CHAR_LENGTH(uuid) > 1
					 AND tbtdid != 0
					 AND TrenchBookID = $TrenchBookID
					 AND pcURI LIKE '%".$prevLink."'
					 ORDER BY tbtid, tbtdid
					 ";
					 
					 $resB =  $db->fetchAll($sql);
					 foreach($resB as $rowB){
						  //for the first page of a given trench book, add linking relations to all the later pages
						  $targUUID = $rowB["uuid"];
						  $newLinkUUID = $linkObj->addLinkingRel($uuid, "Diary / Narrative", $targUUID, 'Diary / Narrative', "Previous", $source);
					 }
					 
				}
				
				
				if(strlen($nextLink) > 1){
					 //add a previous link
					 
					 $sql = "SELECT uuid, tbtid, tbtdid, TrenchBookID, label, pagedLabel, StartPage, EndPage,
					 pcURI, prevLink, NextLink
					 FROM z_tb_scrape
					 WHERE CHAR_LENGTH(uuid) > 1
					 AND tbtdid != 0
					 AND TrenchBookID = $TrenchBookID
					 AND pcURI LIKE '%".$nextLink."'
					 ORDER BY tbtid, tbtdid
					 ";
					 
					 $resB =  $db->fetchAll($sql);
					 foreach($resB as $rowB){
						  //for the first page of a given trench book, add linking relations to all the later pages
						  $targUUID = $rowB["uuid"];
						  $newLinkUUID = $linkObj->addLinkingRel($uuid, "Diary / Narrative", $targUUID, 'Diary / Narrative', "Next", $source);
						  if($row["tbtdid"] == 0){
								$newLinkUUID = $linkObj->addLinkingRel($targUUID, "Diary / Narrative", $uuid, 'Diary / Narrative', "Previous", $source);
						  }
					 }
					 
				}
				*/
		  }
		  
		  $sql = "
		  
		  SET @new_ordering = 0;

		  UPDATE z_tb_scrape AS sc
		  SET sc.sort = (@new_ordering := @new_ordering + 1)
		  WHERE CHAR_LENGTH(sc.uuid) > 1
		  ORDER BY sc.TrenchBookID, sc.StartPage, sc.EndPage;
		  
		  UPDATE diary
		  JOIN z_tb_scrape AS sc ON sc.uuid = diary.uuid
		  SET diary.sort = sc.sort;
		  
		  ";
		  
		  $db->query($sql); //add sorting information
		  
		  return $output;
	 }
	 
	 //get linked trench UUIDs
	 function getLinkedTrench($trenchID){
		  $output = false;
		  $db = $this->startDB();
		  $trench = "Tr-ID ".$trenchID;
		  
		  $sql = "SELECT uuid FROM space WHERE space_label = '$trench' LIMIT 1; ";
		  
		  $result =  $db->fetchAll($sql);
		  if($result){
				$output = $result[0]["uuid"];
		  }
		  
		  return $output;
	 }
	 
	 
	 //get linked trench book scans.
	 function getLinkedTBscans($TrenchBookID, $StartPage, $EndPage, $tbtdid, $TBLabel){
		 
		  $output = false; 
		  $db = $this->startDB();
		  $OrStart = "";
		  if($StartPage == 1){
				$OrStart = " OR StartPage = 0 ";
		  }
		  
		  
		  $sql = "SELECT uuid, label
				FROM z_tb_images
				WHERE (TrenchBookID = ".$TrenchBookID."
				OR TB_Label = '$TBLabel')
				AND
					 (
						  ((StartPage >= ".$StartPage." $OrStart)
						  AND EndPage <= ".$EndPage."
						  AND EndPage != 0
						  )
					 OR
						  (StartPage = ".$StartPage." $OrStart)
						  AND (EndPage = 0)
					 )
				";
		  
		  if($tbtdid == 0){
				
				$sql = "SELECT uuid, label
				FROM z_tb_images
				WHERE TrenchBookID = ".$TrenchBookID."
				OR TB_Label = '$TBLabel' ";
				
		  }
		  
		  //echo " ---  $sql  ---  ";
		  $result =  $db->fetchAll($sql);
		  if($result){
				$output = array();
				foreach($result as $row){
					 $output[] = $row["uuid"];
				}
		  }
		  return $output;
	 }
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 function TBscrapePagesUUID(){
		  
		  $db = $this->startDB();
		  $output = array();
		  $sql = "SELECT * FROM
		  z_tb_scrape
		  WHERE CHAR_LENGTH(uuid) < 1
		  AND tbtdid != 0
		  ORDER BY tbtid, tbtdid
		  ";
		  
		  $result =  $db->fetchAll($sql);
		  foreach($result as $row){
				$pcURI = $row["pcURI"];
				$label = $row["label"];
				$TrenchBookID = $row["TrenchBookID"];
				$StartPage = $row["StartPage"];
				$EndPage = $row["EndPage"];
				$pagedLabel = $row["label"];
				if($StartPage > 0){
					 $suffix = ":".$StartPage;
					 if($EndPage > $StartPage){
						  $suffix .= "-".$EndPage;
					 }
					 
					 $pagedLabel = $label.$suffix;
				}
				
				$where = "pcURI = '$pcURI' ";
				$data = array("pagedLabel" => $pagedLabel);
					 
				$sql = "SELECT uuid FROM diary WHERE diary_label = '".$pagedLabel."' LIMIT 1;";
				$resultB =  $db->fetchAll($sql);
				if($resultB){
					 $uuid = $resultB[0]["uuid"];
					 $data["uuid"] = $uuid;
					 $db->update("z_tb_scrape", $data, $where);
					 $output[$pcURI] = $data;
				}
				else{
					 //check to make sure we don't have exactly the same page range for the
					 //same item twice. 
					 $sql = "SELECT *
					 FROM z_tb_scrape
					 WHERE tbtdid = 0
					 AND StartPage = $StartPage
					 AND EndPage = $EndPage
					 AND TrenchBookID = $TrenchBookID
					 LIMIT 1;
					 ";
					 
					 $resultC =  $db->fetchAll($sql);
					 if(!$resultC){
						  //create a UUID for the item if it's not the same as the first trenchbook page
						  $uuid = GenericFunctions::generateUUID();
						  $data["uuid"] = $uuid;
						  $db->update("z_tb_scrape", $data, $where);
						  $output[$pcURI] = $data;
					 }
					 else{
						  $output[$pcURI] = "Duplicate of first page";
					 }
				}
				
		  }
		  
		  return $output;
	 }
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 //get rid of style long information (not going to display well)
	
	 
	 //get artifact link from old artifact url
	 function getPCIDfromArtifactLink($artifactLink){
		$output = false;
		$urlParams = $this->getURLparams($artifactLink);
		if(is_array($urlParams)){
			if(isset($urlParams["aid"])){
				$output = $urlParams["aid"];
				$objects = array();
				preg_match_all('/(\d{8})/', $output, $matches);
				foreach($matches[0] as $k=>$objectID) { 
					$output = $objectID; 
				}
			}
		}
		  
		return $output;
	}
	 
	 //get trench book page scan from link
	 function getScanIDsfromLink($scanLink){
		  $output = false;
		  $urlParams = $this->getURLparams($scanLink);
		  if(is_array($urlParams)){
				$output = $urlParams;
		  }
		  
		  return $output;
	 }
	 
	 //get trench book transcript id parameters from link
	 function getTbIDsfromLink($tbLink){
		  $output = false;
		  $urlParams = $this->getURLparams($tbLink);
		  if(is_array($urlParams)){
				$output = $urlParams;
		  }
		  
		  return $output;
	 }
	 
	 //get UUID for trench book based on TrenchBook ID and search page
	 function getUUIDfromTrenchBookIDs($tbIDs){
		  $output = false;
		  $db = $this->startDB();
		  
		  if(isset($tbIDs["searchpage"]) && isset($tbIDs["tbID"])){
				if(is_numeric($tbIDs["tbID"])){
					 if(!is_numeric($tbIDs["searchpage"])){
						  $tbIDs["searchpage"] = 1;
					 }
					 $StartPage = $tbIDs["searchpage"];
					 
					 if($StartPage > 1){
						  
						  $sql = "SELECT uuid, label, pagedLabel
						  FROM z_tb_scrape
						  WHERE TrenchBookID = ".$tbIDs["tbID"]."
						  AND
								(
									 (StartPage >= ".$StartPage." )
								)
						  ORDER BY tbtdid, StartPage, EndPage	
						  LIMIT 1;
						  ";
					 }
					 else{
						  //just get the main diary (home page for a trench book)
						  
						  $sql = "SELECT uuid, label, pagedLabel
						  FROM z_tb_scrape
						  WHERE TrenchBookID = ".$tbIDs["tbID"]."
						  ORDER BY tbtdid, StartPage, EndPage	
						  LIMIT 1;
						  ";
					 }
					 
					 $result =  $db->fetchAll($sql);
					 if($result){
						  $output = $result[0];
						  if(strlen($result[0]["pagedLabel"]) > strlen($result[0]["label"])){
								$output["label"] = $result[0]["pagedLabel"]; 
						  }
					 }
				}
		  }
		  
		  return $output;
	 }
	 
	 
	 //get the scanned trench book media uuid
	 function getUUIDfromScanIDs($scanIDs){
		  $db = $this->startDB();
		  
		  if(isset($scanIDs["searchpage"]) && isset($scanIDs["tbID"])){
				
				if(!is_numeric($scanIDs["searchpage"])){
					 $scanIDs["searchpage"] = 0;
				}
				$StartPage = $scanIDs["searchpage"];
				$EndPage = $scanIDs["searchpage"];
				$OrEnd = "";
				$OrStart = "";
				if($StartPage == 1){
					 $OrStart = " OR StartPage = 0";
				}
				
				$sql = "SELECT uuid, label
				FROM z_tb_images
				WHERE TrenchBookID = ".$scanIDs["tbID"]."
				AND
					 (
						  StartPage >= ".$StartPage." $OrStart
					 )
				ORDER BY StartPage, EndPage
				LIMIT 1;
				";
				
				$result =  $db->fetchAll($sql);
				if($result){
					 return $result[0];
				}
				else{
					 return false;
				}
		  }
		  else{
				return false;
		  }
	 }
	 
	 
	 //get the parameters in a URL
	 function getURLparams($url){
		  $output = false;
		  $urlArray = parse_url($url);
		  if(isset($urlArray["query"])){
				$qArray = explode("&", $urlArray["query"]);
				$output = array();
				foreach($qArray as $param){
				    $pEx = explode("=", $param);
				    if(count($pEx)>1){
					   $output[$pEx[0]] = $pEx[1];
				    }
				}
		  }
		  return $output;
	 }
	 
	 
	 //get image uuid and current thumbnail
	 function getImageUUIDandThumb($photoPath, $uuid){
		  $db = $this->startDB();
		  if(strstr($photoPath, "/")){
				$pEx = explode("/", $photoPath);
				$photo = $pEx[(count($pEx) - 1)];
		  }
		  else{
				$photo = $photoPath;
		  }
		  
		  $photoCap = str_replace(".jpg", ".JPG", $photo);
		  $photoLow = str_replace(".JPG", ".jpg", $photo);
		  
		  $sql = "SELECT uuid, ia_thumb AS thumb
		  FROM resource
		  WHERE res_path_source LIKE '%$photoCap'
		  OR res_path_source LIKE '%$photoLow'
		  LIMIT 1;
		  ";
		  
		  $result =  $db->fetchAll($sql);
		  if($result){
				return $result[0];
		  }
		  else{
				$this->recordMissingRef($uuid, $photo, "media");
				return false;
		  }
	 }
	 
	 
	 function recordMissingRef($uuid, $ref, $refType){
		  
		  $db = $this->startDB();
		  $data = array("uuid" => $uuid,
							 "refType" => $refType,
							 "refID" => $ref
							 );
		  try{
				$db->insert("z_miss_docrefs", $data);
		  } catch (Exception $e) {
		  
		  }
		  
	 }
	 
	 
	 
	 //get rid of quotes around text
	 function stripQuotes($text){
		  $text = str_replace("\"", "", $text);
		  $text = str_replace("'", "", $text);
		  $text = trim($text);
		  return $text;
	 }
	 
	 
	 function substr_unicode($str, $s, $l = null) {
		  return join("", array_slice(
				preg_split("//u", $str, -1, PREG_SPLIT_NO_EMPTY), $s, $l));
	 }
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 
	 	 
	 function linkFix(){
		  
		  $db = $this->startDB();
		  $sql = "SELECT tbtid, tbtdid, prevLink FROM z_tb_scrape WHERE 1";
		 
		  $result =  $db->fetchAll($sql);
		  foreach($result as $row){
				$tbtid = $row["tbtid"];
				$tbtdid = $row["tbtdid"];
				$urlSuffix = $row["prevLink"];
				if(strlen($urlSuffix)>1){
					 $url = "http://poggiocivitate.classics.umass.edu/catalog/trenchbooks/".$urlSuffix;
					 $urlArray = parse_url($url);
					 
					 $qArray = explode("&", $urlArray["query"]);
					 
					 $where = false;
					 foreach($qArray as $param){
						  $pEx = explode("=", $param);
						  $data[$pEx[0]] = $pEx[1];
						  if(!$where){
								$where = $param;
						  }
						  else{
								$where .= " AND ".$param;
						  }
					 }
					 $db = $this->startDB();
					 $sql = "SELECT * FROM z_tb_scrape WHERE $where LIMIT 1;";
					 //echo $sql;
					 //die;
					 $resultB =  $db->fetchAll($sql);
					 if(!$resultB){
						  $where = "tbtid = $tbtid AND tbtdid = $tbtdid";
						  $data = array("content" => "");
						  $db->update("z_tb_scrape", $data, $where);
					 }
				}
		  }
	 }
	 
	 function deleteDupes(){
		  $db = $this->startDB();
		  $sql = "SELECT * FROM z_tb_scrape
		  WHERE 1 ORDER BY tbtid, tbtdid
		  ";
		  
		  $result =  $db->fetchAll($sql);
		  $prevTbtid = false;
		  $prevTbtdid = false;
		  foreach($result as $row){
				$tbtid = $row["tbtid"];
				$tbtdid = $row["tbtdid"];
				if($tbtid == $prevTbtid && $tbtdid == $prevTbtdid){
					 $where = "tbtid = $tbtid AND tbtdid = $tbtdid";
					 $sql = "DELETE FROM z_tb_scrape WHERE $where LIMIT 1;";	 
					 $resultB =  $db->query($sql);
				}
				$prevTbtid = $tbtid;
				$prevTbtdid = $tbtdid;
		  }
	 }
	 
	 
	 function TBjsonAdd(){
		  
		  $jsonString = file_get_contents("http://penelope.oc/csv-export/scrape.json");
		  $jsonString = mb_convert_encoding($jsonString, 'ASCII');
				$jsonString = str_replace("???", "", $jsonString);
				$jsonString = str_replace("\r\n", "", $jsonString);
		  $jarray = Zend_Json::encode($jsonString);
		  $jarray = json_decode($jsonString, true);
		  $db = $this->startDB();
		  if(!is_array($jarray)){
				echo "crap.".substr($jsonString, 0, 300);
				die;
		  }
		  foreach($jarray["newData"] as $data){
				if(is_array($data)){
					 if(strlen($data["html"])<4){
						  $data["html"] = "no data";
					 }
					 else{
						  $db->insert("z_tb_scrape",$data);
					 }
				}
		  }
		  
	 }
	 
	 
	 //cleanup non-valid, messy HTML from original trenchbook transcripts
	 function TBscrapeParse(){
		  
		  $db = $this->startDB();
		  $output = array();
		  $continue = true;
		  
		  while($continue){
				$sql = "SELECT *
				FROM z_tb_scrape
				WHERE content = ''
				LIMIT 1;
				";
				
				$result =  $db->fetchAll($sql);
				if($result){
					 //$continue = false;
					 foreach($result as $row){
						  $tbtid = $row["tbtid"];
						  $tbtdid = $row["tbtdid"];
						  
						  $rawText = $row["html"];
						  
						  if(!stristr($rawText, "</html>")){
								if($tbtdid>0){
									 $urlSuffix = "viewtrenchbookentry.asp?tbtdid=".$tbtdid."&tbtid=".$tbtid;
								}
								else{
									 $urlSuffix = "viewtrenchbookentry.asp?tbtid=".$tbtid;
								}
								$url = "http://poggiocivitate.classics.umass.edu/catalog/trenchbooks/".$urlSuffix;
								sleep(.5);
								@$rawText = file_get_contents($url);
								if($rawText){
									 $rawText = $this->styleReduce($rawText, "TD");
									 $this->saveFile("csv-export", "TB-".$tbtid."-".$tbtdid.".html", $rawText);
									 
									 $where = "tbtid = $tbtid AND tbtdid = $tbtdid";
									 $data = array("html" => $rawText);
									 $db->update("z_tb_scrape", $data, $where);
								}
								else{
									 echo "not working: ".$url;
									 die;
								}
						  }
						  
						  //$parts = $this->contentTagAdd($rawText);
						  $parts = $this->contentTagAlt($rawText);
						  //echo print_r($parts);
						  //die;
						  $rawText = $parts["before"].$parts["after"];
						  if(!$parts["before"] || !$parts["content"] || !$parts["after"]){
								echo print_r($parts);
								die;
						  }
						  
						  $title = false;
						  $date = false;
						  $StartPage = false;
						  $EndPage = false;
						  $content = $parts["content"];
						  $nextLink = false;
						  $prevLink = false;
						  $nextLinkDate = false;
						  $prevLinkDate = false;
						  
						  $useText = tidy_repair_string($rawText,
												array( 
													 'doctype' => "omit",
													 'input-xml' => true,
													 'output-xml' => true 
												));
										  
						  @$xml = simplexml_load_string($useText);
						  if($xml){
								foreach($xml->xpath("//p[@class='title']") as $res) {
									 $title = (string)$res;
									 if(strstr($title, ":")){
										  $tEx = explode(":", $title);
										  $title = trim($tEx[0]);
										  $tEx[1] = str_replace("Entry for", "", $tEx[1]);
										  $date = date("Y-m-d", strtotime(trim($tEx[1])));
									 }
								} 
						  
								foreach($xml->xpath("//a") as $res) {
									 
									 $href = false;
									 foreach($res->xpath("@href") as $res2){
										  $href = (string)$res2;
									 }
									 $linkVal = (string)$res;
									 if(strstr($linkVal, "/")){
										  
										  $linkVal = str_replace("<", "", $linkVal);
										  $linkVal = str_replace(">", "", $linkVal);
										  $linkVal = trim($linkVal);
										  $linkValTime = strtotime($linkVal);
										  
										  if($linkValTime < strtotime($date)){
												$prevLink = $href;
												$prevLinkDate = date("Y-m-d", $linkValTime);
												$output["newData"][] = $this->TBtransScrape($prevLink);
										  }
										  if($linkValTime >= strtotime($date)){
												$nextLink = $href;
												$nextLinkDate = date("Y-m-d", $linkValTime);
												$output["newData"][] = $this->TBtransScrape($nextLink);
										  }
										  
										  $output["links"][] = array("original" => $linkVal,
																			  "link" => $href,
																			  "prev" => $prevLink,
																			  "next" => $nextLink);
									 }
								}
						  
								$pp = 1;
								$maxPage = 1500;
								
								while($pp <= $maxPage){
									 
									 foreach($xml->xpath("//a[@href='#P".$pp."']") as $res) {
										  $actPage = (string)$res;
										  $actPage += 0;
										  if(!$StartPage){
												$StartPage = $actPage;
										  }
										  else{
												if(!$EndPage){
													 $EndPage = $actPage;
												}
												if($EndPage < $actPage){
													 $EndPage = $actPage;
												}
										  }
									 
									 }
									 
									 $pp++;
								}
						  
								$where = "tbtid = $tbtid AND tbtdid = $tbtdid";
								$data = array("label" => $title,
												  "date" => $date,
												  "StartPage" => $StartPage,
												  "EndPage" => $EndPage,
												  "prevLink" => $prevLink,
												  "prevLinkDate" => $prevLinkDate,
												  "nextLink" => $nextLink,
												  "nextLinkDate" => $nextLinkDate,
												  "content" => $content
												  );
								
								$db->update("z_tb_scrape", $data, $where);
						  }
						  
						  $output[$tbtid][$tbtdid] = $data;
					 }//end loop
				}//end case with results
				else{
					 $continue = false;
				}
		  }//end loop
		  
		  return $output;
	 }
	 
	 
	 function styleReduce($rawText, $actTag){
		  $replaceText = $rawText;
		  $pos = 0;
		  $textLen = strlen($rawText);
		  if(strstr($rawText, "<".$actTag)){
				$styles = array();
				while($pos < $textLen){
					 $TDpos = strpos($rawText, "<".$actTag, $pos);
					 $endTD = strpos($rawText, ">", $TDpos);
					 $tag = substr($rawText, $TDpos, ($endTD - $TDpos));
					 $actStyle =  $this->getAttribute('style', $tag);
					 if(strlen($actStyle)>1){
						  $hashStyle = md5($actStyle);
						  if(array_key_exists($hashStyle, $styles)){
								$styleID = $styles[$hashStyle]['id'];
						  }
						  else{
								$id = count($styles) + 1;
								$styles[$hashStyle] = array("id" => $id, "style" => $actStyle);
						  }
					 }
					 if($endTD > $pos){
						  $pos = $endTD;
					 }
					 else{
						  $pos++;
					 }
				}
				$styleElement = "<style id=\"scrape-styles\" type=\"text/css\" >".chr(13);
				foreach($styles as $style){
					 $className = "scrape-st-".$style["id"];
					 $styleElement .= $actTag.".".$className." {".chr(13).$style["style"].chr(13)."}".chr(13);
					 $replaceText = str_replace("style=\"".$style["style"]."\"", "class=\"".$className."\"", $replaceText);
				}
				$styleElement .= "</style>".chr(13);
				$endBegin = "<a href=\"trenchbookdaily.asp\">Return to Trench Book Logs</a>
</td></tr></table><br>";
				$replaceText = str_replace($endBegin, $endBegin." ".$styleElement, $replaceText);
				
		  }
		  
		  return $replaceText;
	 }
	 
	 function getAttribute($attrib, $tag){
		//get attribute from html tag
		$re = '/' . preg_quote($attrib) . '=([\'"])?((?(1).+?|[^\s>]+))(?(1)\1)/is';
		if (preg_match($re, $tag, $match)) {
			return urldecode($match[2]);
		}
		return false;
	}
	 
	 
	 function contentTagAlt($rawText){
		  
		  $textLen = strlen($rawText);
		  
		  $beforeContent = "";
		  $content = "";
		  $afterContent = "";
		  $pos = 0;
		  
		  $endBegin = "<a href=\"trenchbookdaily.asp\">Return to Trench Book Logs</a>
</td></tr></table><br>";
		  $textEx = explode($endBegin, $rawText);
		  $beforeContent = $textEx[0].$endBegin;
		  $endContent = "<p>

<table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
";
		  $textEndEx = explode($endContent, $textEx[1]);
		  $content = "<div>".$textEndEx[0]."</div>";
		  if(isset($textEndEx[1])){
				$afterContent = $endContent.$textEndEx[1];
		  }
		  else{
				$endContent = "<p><a href=\"edittrenchbookdaily.asp";
				$textEndEx = explode($endContent, $textEx[1]);
				$content = "<div>".$textEndEx[0]."</div>";
				$afterContent = $endContent.$textEndEx[1];
		  }
		  
		  return array("before" => $beforeContent,
							"content" => $content,
							"after" => $afterContent);
	 }
	 
	 
	 
	 function TBtransScrape($urlSuffix){
		  ini_set('default_socket_timeout',    15);    

		  $url = "http://poggiocivitate.classics.umass.edu/catalog/trenchbooks/".$urlSuffix;
		  $urlArray = parse_url($url);
		  $qArray = explode("&", $urlArray["query"]);
		  $data = array();
		  $where = false;
		  foreach($qArray as $param){
				$pEx = explode("=", $param);
				$data[$pEx[0]] = $pEx[1];
				if(!$where){
					 $where = $param;
				}
				else{
					 $where .= " AND ".$param;
				}
		  }
		  $db = $this->startDB();
		  $sql = "SELECT * FROM z_tb_scrape WHERE $where LIMIT 1;";
		  
		  $result =  $db->fetchAll($sql);
		  if(!$result){
				//we don't have a database record for this, so add it!
				sleep(.5);
				@$html = file_get_contents($url);
				//echo $url;
				//die;
				if($html != false){
					$html = str_replace("‘", "'", $html);
					$html = str_replace("’", "'", $html);
					 //$data["html"] = $html;
					 try{
						  $db->insert("z_tb_scrape", $data);
					 } catch (Exception $e) {
						  echo (string)$e;
						  die;
					 }
					 return $data;
				}
				else{
					 return "failed retrieve";
				}
		  }
	 }
	 
	 
	 //scape the live website for transcript information
	 function TBscrape(){
		  $output = array();
		  $db = $this->startDB();
		  $i = 1;
		  $max = 1200;
		  while($i <= $max){
				$url = "http://poggiocivitate.classics.umass.edu/catalog/trenchbooks/viewtrenchbookentry.asp?tbtid=".$i;
				@$html = file_get_contents($url); 
				if($html != false){
					$html = str_replace("‘", "'", $html);
					$html = str_replace("’", "'", $html);
					$html = preg_replace('/[^(\x20-\x7F)]*/','', $html);
					 $data = array("tbtdid" => $i,
										"html" => $html);
					 $db->insert("z_tb_scrape", $data);
					 $output[$i] = $url;
				}
				else{
					 $output[$i] = "error";
				}
				
				sleep(.5);
				$i++;
		  }
		  
		  return $output;
	 }
	
	
	function trenchBookScrapeDeDuplicate($url, $contentHash = "", $needNew = false){
		$urls = array();
		$urls[] = $url;
		$db = $this->startDB();
		if(strlen($contentHash) > 1){
			//check to see if the content Hash is repeated
			$sql = "SELECT COUNT(contHash) as hashCount
			FROM z_tb_scrape
			WHERE contHash = '$contentHash'
			GROUP BY contHash
			";
			$result =  $db->fetchAll($sql);
			if($result){
				$hashCount = $result[0]["hashCount"];
				if($hashCount > 1){
					$needNew = true;
					$sql = "SELECT pcURI FROM z_tb_scrape WHERE contHash = '$contentHash' ";
					$resB = $db->fetchAll($sql);
					foreach($resB as $row){
						if(!in_array($row["pcURI"], $urls)){
							$urls[] = $row["pcURI"];
						}
					}
				}
			}
		}
		if($needNew && count($urls)> 0){
			foreach($urls as $url){
				@$html = file_get_contents($url); 
				if($html != false){
					$html = str_replace("‘", "'", $html);
					$html = str_replace("’", "'", $html);
					$data = array("html" => $html,
								  "hasGZIP" => 1,
								  "compressed" => gzcompress($html, 9));
					$where = "pcURI = '$url' ";
					$db->update("z_tb_scrape", $data, $where);
					sleep(.5);
				}
			}
		}
	}
	 
	 
	 
	 
	 //Add attibution information related to trench books
	 function TBauthorLink($typeToAttribute = "Media (various)"){
		  
		  $output = array();
		  
		  $db = $this->startDB();
		  
		  if($typeToAttribute != "Media (various)"){
				
				$sql = "SELECT links.project_id, links.targ_uuid AS newOrigin, dNewO.diary_label AS newOriginName, links.origin_uuid AS oldOrigin
				FROM links
				JOIN diary AS dNewO ON dNewO.uuid = links.targ_uuid
				WHERE links.targ_type = 'Diary / Narrative'
				AND links.origin_type = 'Diary / Narrative'
				AND links.link_type = 'Has part'
				";
				
		  }
		  else{
				
				$sql = "SELECT links.project_id, links.targ_uuid AS newOrigin, resource.res_label AS newOriginName, links.origin_uuid AS oldOrigin
				FROM links
				JOIN resource ON resource.uuid = links.targ_uuid
				WHERE links.targ_type = 'Media (various)'
				AND links.origin_type = 'Diary / Narrative'
				";
		  }
	 
		  $result =  $db->fetchAll($sql);
		  $linkObj = new dataEdit_Link;
		  $linkObj->projectUUID = $result[0]["project_id"];
		  
		  foreach($result as $row){
				
				$newOrigin = $row["newOrigin"];
				$oldOrigin = $row["oldOrigin"];
				
				$sql = "SELECT users.combined_name, users.uuid
				FROM links
				JOIN users ON links.targ_uuid = users.uuid
				WHERE links.origin_uuid = '$oldOrigin'
				LIMIT 1;
				";
				
				//echo print_r($row);
				//echo "<br/>".$sql;
				//die;
				
				$resultB =  $db->fetchAll($sql);
				if($resultB){
					 $newTarget = $resultB[0]["uuid"];
					 $row["linkedUUID"] = $newTarget;
					 $row["linkedName"] = $resultB[0]["combined_name"];
					 $newLinkUUID = $linkObj->addLinkingRel($newOrigin, $typeToAttribute, $newTarget, 'Person', "Recorded by");
					 unset($row["project_id"]);
					 $output[$newLinkUUID] = $row;
				}
		  }
		  return $output;
	 }
	 
	 
	 
	  //cleanup non-valid, messy HTML from original trenchbook transcripts
	 function TBmediaLink(){
		  
		  $output = array();
		  
		  $db = $this->startDB();
		  
		  $sql = "SELECT links.project_id, links.origin_uuid, links.targ_uuid, resource.res_label, diary.diary_label
		  FROM links
		  JOIN resource ON resource.uuid = links.targ_uuid
		  JOIN diary ON diary.uuid = links.origin_uuid
		  WHERE targ_type = 'Media (various)'
		  AND origin_type = 'Diary / Narrative'
		  ";
	 
		  $result =  $db->fetchAll($sql);
		  $linkObj = new dataEdit_Link;
		  $linkObj->projectUUID = $result[0]["project_id"];
		  
		  foreach($result as $row){
				
				$newOrigin = $row["targ_uuid"];
				$newTarget = $row["origin_uuid"];
				$newLinkUUID = $linkObj->addLinkingRel($newOrigin, 'Media (various)', $newTarget, 'Diary / Narrative', "link");
				unset($row["project_id"]);
				$output[$newLinkUUID] = $row;
		  }
		  return $output;
	 }
	 
	 //cleanup non-valid, messy HTML from original trenchbook transcripts
	 function TBtransClean(){
		  
		  $db = $this->startDB();
		  $output = array();
		  
		  $sql = "SELECT label, EntryText
		  FROM z_tb_transcripts
		  WHERE 1
		  
		  ";
		  
		  $result =  $db->fetchAll($sql);
		  foreach($result as $row){
				$label = $row["label"];
				$rawText = $row["EntryText"];
				$rawText = $this->tagLowerCase($rawText);
				$rawText = "<div>".$rawText."</div>";
				$useText = tidy_repair_string($rawText,
									 array( 
										  'doctype' => "omit",
										  'input-xml' => true,
										  'output-xml' => true 
									 ));
								
				@$xml = simplexml_load_string($useText);
				if($xml){
					$validXHTML = true;
				}
				else{
					 $validXHTML = false;
				}
				unset($xml);
				
				$where = "diary_label = '$label' ";
				$data = array("diary_text_original" => $useText);
				$db->update("diary", $data, $where);
				
				$output[$label] = array("valid" => $validXHTML, "xhtml" => $useText);
		  }
		  
		  return $output;
	 }
	 
	 
	 
	 //setup authoriship for trench books
	 function TBauthors(){
		  $output = "<table>".chr(13);
		  $db = $this->startDB();
		  $sql = "SELECT TrenchBookID, label, Authors FROM  z_tb_names ";
		  $result =  $db->fetchAll($sql);
		  foreach($result as $row){
				$id = $row["TrenchBookID"];
				$rawAuthors = $row["Authors"];
				$label = $row["label"];
				$allAuthorArray = array();
				$allAuthorArray = $this->authorSplit($allAuthorArray, ",", $rawAuthors);
				$allAuthorArray = $this->authorSplit($allAuthorArray, "/", $rawAuthors);	 
				$allAuthorArray = $this->authorSplit($allAuthorArray, " and ", $rawAuthors);
				if(count($allAuthorArray)<1){
					 $allAuthorArray[] = $rawAuthors;
				}
				
				foreach($allAuthorArray as $author){
					 $author = trim($author);
					 $output.= "<tr><td>$label</td><td>$author</td></tr>".chr(13);
				}
				
				unset($allAuthorArray);
		  }
		  
		  $output.= "</table>".chr(13);
		  return $output;
	 }//end function
	 
	 function authorSplit($allAuthorArray, $delim, $rawAuthors){
		  if(strstr($rawAuthors, $delim)){
				$tAuthEx = explode($delim, $rawAuthors);
				foreach($tAuthEx as $auth){
					 if(!in_array($auth, $allAuthorArray)){
						  $allAuthorArray[] = $auth;
					 }
				}
		  }
		  return $allAuthorArray;
	 }
	 
	 
	 //get page numbers from the filenames of trench book scans
	 function TBimagePageNumbers(){
		  $output = array();
		  $db = $this->startDB();
		  $sql = "SELECT id, TrenchBookID, TB_Label, ImagePath, InsertIm FROM z_tb_images ";
		  $result =  $db->fetchAll($sql);
		  foreach($result as $row){
				$id = $row["id"];
				$TrenchBookID = $row["TrenchBookID"];
				$TBlabel = $row["TB_Label"];
				$imgPath = $row["ImagePath"];
				
				$insert = false;
				if($row["InsertIm"] != 0){
					 $insert = true;
				}
				
				$imgEx = explode("/", $imgPath);
				$imgFile = $imgEx[count($imgEx)-1];
				$tFile = strtolower($imgFile);
				$tFile = str_replace(".jpg", "", $tFile);
				$tEx = explode("_", $tFile);
				
				$rawNumsInserts = $tEx[1];
				if(stristr($rawNumsInserts, "insert")){
					 $rawNumInsEx = explode("insert", $rawNumsInserts);
					 $rawNums = $rawNumInsEx[0];
					 if(isset($rawNumInsEx[1])){
						  $insertNum = $rawNumInsEx[1];  
					 }
					 else{
						  $insertNum = 0;
					 }
				}
				else{
					 $rawNums = $rawNumsInserts;
					 $insertNum = 0;
				}
				
				$data = array();
				if(stristr($rawNums, ",")){
					 $numEx = explode(",", $rawNums);
					 $data["StartPage"] = $numEx[0];
					 $data["EndPage"] = $numEx[1];
				}
				else{
					 $data["StartPage"] = $rawNums;
					 $data["EndPage"] = 0;
				}
				
				if(!is_numeric($data["StartPage"])){
					 $data["StartPage"] = 0;
				}
				else{
					 $data["StartPage"] += 0;
				}
				
				if(!is_numeric($data["EndPage"])){
					 $data["EndPage"] = 0;
				}
				else{
					 $data["EndPage"] += 0;
				}
				
				$data["label"] = $TBlabel;
				if($data["StartPage"] > 0 || $data["EndPage"]>0){
					 $data["label"] .= ":".$data["StartPage"];
				}
				if($data["EndPage"]>0){
					 $data["label"] .= "-".$data["EndPage"];
				}
				if($insert){
					 $data["label"] .= ", insert";
					 $data["note"] = "insert";
					 if($insertNum>0){
						  $data["label"] .= " ".$insertNum;
						  $data["note"] .= " ".$insertNum;
					 }
				}
				
				$sql = "SELECT label FROM z_tb_transcripts
				WHERE TrenchBookID = $TrenchBookID
				AND StartPage >= ".$data["StartPage"]." AND EndPage <= ".$data["EndPage"]." LIMIT 1; ";
				
				$resB = $db->fetchAll($sql);
				if($resB){
					 $data["TBtrans_Label"] = $resB[0]["label"];
				}
				
				$where = " id = $id ";
				$db->update("z_tb_images", $data, $where);
				
				$output[$imgFile] = $data;
		  }
		  
		  return $output;
	 }
	 
	 
	 //get the UUID for an artifact numeric label
	 function getPCuuid($numericLabel){
		  $db = $this->startDB();
		  $output = false;
		  if(is_numeric($numericLabel)){
				$label = "PC ".$numericLabel;
				$sql = "SELECT uuid FROM space WHERE space_label = '$label' LIMIT 1;";
				$result =  $db->fetchAll($sql);
				if($result){
					 $output = $result[0]["uuid"];
				}
		  }
		  return $output;
	 }
	 
	 
	 function geoJsonAdd($jsonFileURL){
		  
		  $output = false;
		  $db = $this->startDB();
		  $flipLatLon = false;
		  $geoJSON = false;
		  if($jsonFileURL != false){
				@$jsonString = file_get_contents($jsonFileURL);
				
				/*
				$i = 10;
				while($i < 131){
					 echo chr(13)."<br/>$i is '".chr($i)."'";
					 $i++;
				}
				
				die;
				*/
				$i = 0;
				$jsonLen = strlen($jsonString);
				$jsonString = mb_convert_encoding($jsonString, 'ASCII');
				$jsonString = str_replace("???", "", $jsonString);
				$jsonString = str_replace("\r\n", "", $jsonString);
				/*
				while($i < $jsonLen ){
					 $char = mb_substr($jsonString, $i, 1);
					 $charval = ord($char);
					 if($charval< 10 || $charval > 130){
						  $jsonString = str_replace($char, "", $jsonString);
						  $jsonLen = strlen($jsonString);
					 }
					 $i++;
				}
				echo "new json string: ".$jsonString;
				die;
				*/
				
				if($jsonString != false){
					 $jsonString = trim($jsonString);
					 $geoJSON = Zend_Json::decode($jsonString);
					 
					 if(!is_array($geoJSON)){
						  $geoJSON = json_decode($jsonString,0);
					 }
					 
					 if(is_array($geoJSON)){
						  
						  $idFeatures = array();
						  $missingArray = array();
						  foreach($geoJSON["features"] as $feature){
								
								$rawTrench = round($feature["properties"]["TrenchID"],0);
								$trenchID = "Tr-ID ".$rawTrench;
								$sql = "SELECT uuid, project_id FROM space WHERE space_label = '$trenchID' LIMIT 1;";
								//echo "<br/>$sql<br/>";
								$results =  $db->fetchAll($sql);
								if($results){
									 $spaceUUID = $results[0]["uuid"];
									 $projectID = $results[0]["project_id"];
									 //echo "<br/>$trenchID is $spaceUUID ";
									 $idFeatures["found"][$spaceUUID]["label"] = $trenchID;
									 $idFeatures["found"][$spaceUUID]["project_id"] = $projectID;
									 
									 
									 //fix reversed coordinates. lon needs to be before lat
									 if($flipLatLon){
										  $newGeo = $feature["geometry"];
										  unset($newGeo["coordinates"]);
										  
										  $newPolyCoords = array();
										  foreach($feature["geometry"]["coordinates"] as $polyCoordinates){
												$newCoordnates = array();
												foreach($polyCoordinates as $coordinates){
													 if(!is_array($coordinates[0])){
														  //see geoJSON spec, lon in first position: http://www.geojson.org/geojson-spec.html#positions
														  $newCoordinate = array($coordinates[1], $coordinates[0]);
														  $newCoordnates[] = $newCoordinate;
													 }
													 else{
														  $newSubCoordnates = array();
														  foreach($coordinates as $actcoords){
																//see geoJSON spec, lon in first position: http://www.geojson.org/geojson-spec.html#positions
																$newCoordinate = array($actcoords[1], $actcoords[0]);
																$newSubCoordnates[] =  $newCoordinate;
														  }
														  $newCoordnates[] = $newSubCoordnates; 
													 }
													 
												}
												$newPolyCoords[] = $newCoordnates;
										  }
										  $feature["geometry"]["coordinates"] = $newPolyCoords;
									 }
									 
									 $idFeatures["found"][$spaceUUID]["features"][]["geometry"] = $feature["geometry"];
									 
									 $sumLat = 0;
									 $sumLon = 0;
									 $coordCount = 0;
									 foreach($idFeatures["found"][$spaceUUID]["features"] as $geometries){
										  foreach($geometries["geometry"]["coordinates"] as $polyCoordinates){
												foreach($polyCoordinates as $coordinates){
													 if(!is_array($coordinates[0])){
														  $coordCount++;
														  $sumLon = $sumLon  + $coordinates[0]; //see geoJSON spec, lon in first position: http://www.geojson.org/geojson-spec.html#positions
														  $sumLat = $sumLat + $coordinates[1]; //see geoJSON spec, lat in second position: http://www.geojson.org/geojson-spec.html#positions
													 }
													 else{
														  foreach($coordinates as $actcoords){
																$coordCount++;
																$sumLon = $sumLon  + $actcoords[0]; //see geoJSON spec, lon in first position: http://www.geojson.org/geojson-spec.html#positions
																$sumLat = $sumLat + $actcoords[1]; //see geoJSON spec, lat in second position: http://www.geojson.org/geojson-spec.html#positions
														  }
													 }
												}
										  }
									 }
									 
									 if($coordCount > 0){
										  $idFeatures["found"][$spaceUUID]["meanLon"] = $sumLon / $coordCount;
										  $idFeatures["found"][$spaceUUID]["meanLat"] = $sumLat / $coordCount;
									 }
									 else{
										  $idFeatures["found"][$spaceUUID]["meanLon"] = false;
										  $idFeatures["found"][$spaceUUID]["meanLat"] = false;
									 }
									 
								}
								else{
									 //echo "<h4>$trenchID has no UUID </h4>";
									 $missingArray[] =  $trenchID;;
								}
						  }
						  $idFeatures["missing"] = $missingArray;
						  
						  
						  //convert multiple geographic features into a multipolygon
						  $fixFound = array();
						  foreach($idFeatures["found"] as $spaceUUID => $geoArray){
								$newGeoArray = $geoArray;
								unset($newGeoArray["features"]);
								if(count($geoArray["features"])>1){
									 
									 $allPolygons = array();
									 foreach($geoArray["features"] as $geometries){
										  $newPolygon = array();
										  foreach($feature["geometry"]["coordinates"] as $polyCoordinates){
												$newRing = array();
												foreach($polyCoordinates as $coordinates){
													 if(!is_array($coordinates[0])){
														  $newRing[] = $coordinates;
													 }
													 else{
														  $newSubRing = array();
														  foreach($coordinates as $actcoords){
																$newSubRing[] = $actcoords;
														  }
														  $newRing = array_merge($newRing, $newSubRing);
													 }
												}
												$newPolygon[] = $newRing;
										  }
										  $allPolygons[] = $newPolygon;
									 }
									 unset($geoArray["features"][0]["geometry"]["coordinates"]);
									 $newGeoArray["features"]["geometry"] = $geoArray["features"][0]["geometry"];
									 $newGeoArray["features"]["geometry"]["type"] = "MultiPolygon";
									 $newGeoArray["features"]["geometry"]["coordinates"] = $allPolygons;
								}
								else{
									 $newGeoArray["features"]["geometry"] = $geoArray["features"][0]["geometry"];
								}
								
								$fixFound[$spaceUUID] = $newGeoArray;
						  }
						  $idFeatures["found"] = $fixFound;
						  $childArray = array();
						  foreach($idFeatures["found"] as $spaceUUID => $geoArray){
								$childArray[] = "space_contain.child_uuid = '$spaceUUID' ";
								
								$data = array("uuid" => $spaceUUID,
												  "project_id" => $geoArray["project_id"],
												  "source_id" => "JSONfile",
												  "latitude" => $geoArray["meanLat"],
												  "longitude" => $geoArray["meanLon"],
												  "geojson_data"	=> Zend_Json::encode($geoArray["features"])
												  );
								
								$where = "uuid = '$spaceUUID' ";
								$db->delete("geo_space", $where);
								$db->insert("geo_space", $data);
						  }
						  
						  $topTree = false;
						  $level = 0;
						  while(!$topTree){
								$qTerms = implode(" OR ", $childArray);
								$sql = "SELECT AVG(geo_space.latitude) as meanLat,
										  AVG(geo_space.longitude) as meanLon, space_contain.parent_uuid
										  FROM geo_space
										  JOIN space_contain ON space_contain.child_uuid = geo_space.uuid
										  WHERE $qTerms
										  GROUP BY space_contain.parent_uuid
										  ";
								$results =  $db->fetchAll($sql);
								if($results){
									 $level++;
									 unset($childArray);
									 $childArray = array();
									 foreach($results as $row){
										  $parentUUID = $row["parent_uuid"];
										  $childArray[] = "space_contain.child_uuid = '$parentUUID' ";
								
										  $data = array("uuid" => $parentUUID,
															 "project_id" => $geoArray["project_id"],
															 "source_id" => "JSONfile-mean-".$level,
															 "latitude" => $row["meanLat"],
															 "longitude" => $row["meanLon"]
															 );
										  
										  $where = "uuid = '$parentUUID' ";
										  $db->delete("geo_space", $where);
										  $db->insert("geo_space", $data);
									 }
								}
								else{
									 $topTree = true;
								}
						  }

						  $output = $idFeatures;
					 }
					 else{  
						  $output = array("error" => substr($jsonString, 0, 200)."... not good json");
					 }
				}
				else{
					 $output = array("error" => $jsonFileURL." did not load");
				}
		  }
		  
		  return $output;
	 }
	 
	 
	 
	 
	 function findsGeoJsonAdd($jsonFileURL){
		  
		  $output = false;
		  $db = $this->startDB();
		  $flipLatLon = false;
		  $geoJSON = false;
		  if($jsonFileURL != false){
				@$jsonString = file_get_contents($jsonFileURL);
				
				$i = 0;
				$jsonLen = strlen($jsonString);
				$jsonString = mb_convert_encoding($jsonString, 'ASCII');
				$jsonString = str_replace("???", "", $jsonString);
				$jsonString = str_replace("\r\n", "", $jsonString);
				
				if($jsonString != false){
					 $jsonString = trim($jsonString);
					 $geoJSON = Zend_Json::decode($jsonString);
					 
					 if(!is_array($geoJSON)){
						  $geoJSON = json_decode($jsonString,0);
					 }
					 
					 if(is_array($geoJSON)){
						  
						  $idFeatures = array();
						  $missingArray = array();
						  foreach($geoJSON["features"] as $feature){
								
								$rawID = round($feature["properties"]["ArtifactID"],0);
								$findID = "PC ".$rawID;
								$sql = "SELECT uuid, project_id FROM space WHERE space_label = '$findID' LIMIT 1;";
								//echo "<br/>$sql<br/>";
								$results =  $db->fetchAll($sql);
								if($results){
									 $spaceUUID = $results[0]["uuid"];
									 $projectID = $results[0]["project_id"];
									 //echo "<br/>$trenchID is $spaceUUID ";
									 $idFeatures["found"][$spaceUUID]["label"] = $findID;
									 $idFeatures["found"][$spaceUUID]["project_id"] = $projectID;
									 
									 $idFeatures["found"][$spaceUUID]["features"][]["geometry"] = $feature["geometry"];
									 $idFeatures["found"][$spaceUUID]["meanLon"] = $feature["geometry"]["coordinates"][0];
									 $idFeatures["found"][$spaceUUID]["meanLat"] = $feature["geometry"]["coordinates"][1];
								}
								else{
									 $missingArray[] = $findID;
								}
						  }
						  $idFeatures["missing"] = $missingArray;
						  
						  foreach($idFeatures["found"] as $spaceUUID => $geoArray){
								
								$data = array("uuid" => $spaceUUID,
												  "project_id" => $geoArray["project_id"],
												  "source_id" => "FindsJSONfile",
												  "latitude" => $geoArray["meanLat"],
												  "longitude" => $geoArray["meanLon"],
												  "geojson_data"	=> Zend_Json::encode($geoArray["features"])
												  );
								
								$where = "uuid = '$spaceUUID' ";
								$db->delete("geo_space", $where);
								$db->insert("geo_space", $data);
						  }
						  
						  $output = $idFeatures;
					 }//end case with JSON being an array  
				}//end case with file opened
		  }//end case with a URL to get the file
		  
		  return $output;
	 }
	 
	 function saveFile($itemDir, $baseFilename, $fileString){
		
		  $success = false;
		
		  try{
				
				iconv_set_encoding("internal_encoding", "UTF-8");
				
				//iconv_set_encoding("internal_encoding", "UTF-8");
				//iconv_set_encoding("output_encoding", "UTF-8");
				$fp = fopen($itemDir."/".$baseFilename, 'w');
				//fwrite($fp, iconv("ISO-8859-7","UTF-8",$JSON));
				//fwrite($fp, utf8_encode($JSON));
				fwrite($fp, $fileString);
				fclose($fp);
				$success = true;
		  }
		  catch (Zend_Exception $e){
				$success = false; //save failure
				echo (string)$e;
				die;
		  }
		
		  return $success;
	 }
	 
	 
	 
	 
	 
	 
	function TBmissingGet(){
		  
		$db = $this->startDB();
		$output = array();
		$continue = true;
		
		$sql = "SELECT uuid, pcURI
		FROM z_tb_scrape WHERE html NOT LIKE '%</html>%'
		AND hasGZIP = 0;
		";
		$result =  $db->fetchAll($sql);
		foreach($result as $row){
			$url = $row["pcURI"];
			$uuid = $row["uuid"];
			@$rawText = file_get_contents($url);
			if(!$rawText){
				$output['badrequest'][] = $row;
			}
			else{
				if(!stristr($rawText, '</html>')){
					$output['badHTML'][] = $row;
				}
				else{
					$output['goodHTML'][] = $row;
					$rawText = $this->styleReduce($rawText, "TD");
					$data = array("html" => $rawText,
								  "hasGZIP" => 1,
								  "compressed" => gzcompress($rawText, 9));
					$where = "pcURI = '$url' ";
					$db->update("z_tb_scrape", $data, $where);
				}
			}
		}
		
		return $output;
	 }
	 
	 
	
	
	function TBtagFix($rawText){
		  $fixedText = $rawText;
		  $textLen = strlen($rawText);
		  if(strstr($rawText, "<TB")){
				$pos = 0;
				while($pos < $textLen){
					 $TDpos = strpos($rawText, "<TB", $pos);
					 $endTD = strpos($rawText, ">", $TDpos);
					 if($TDpos > 0 && $endTD > $TDpos){
						
						$endTD = strpos($rawText, ">", $TDpos);
						$tag = substr($rawText, $TDpos, ($endTD - $TDpos)+1);
						$replaceTag = str_replace("<TB", "", $tag);
						$replaceTag = str_replace(">", "", $replaceTag);
						$replaceTag = $this->rootTrenchBookLookup($replaceTag);
						$replaceTag = "[".$replaceTag."] ";
						$replaceTag = str_replace(",]", "]", $replaceTag);
						$replaceTag = "<span class=\"pc-trench-ref\">".$replaceTag."</span>";
						$fixedText = str_replace($tag, $replaceTag, $fixedText);
						//echo "This replaced $tag replaced by: $replaceTag ";
						//echo " --- Now this: $fixedText  ";
						//die;
					 }
					 if($endTD>$pos){
						  $pos = $endTD;
						  $pos ++;
					 }
					 else{
						  $pos ++;
					 }
				}
		  }
		  //echo " --- Now this: $fixedText  ";
		//die;
		  return $fixedText;
	 }
	 
	
	//gets rid of nasty Microsoft namespace xml declarations randomly
	//added to the HTML
	function BadXMLfix($rawText){
		$fixedText = $rawText;
		$textLen = strlen($rawText);
		if(strstr($rawText, "<?")){
			$pos = 0;
			while($pos < $textLen){
				$Bpos = strpos($rawText, "<?<", $pos);
				$endPos = strpos($rawText, ">", $Bpos);
				if($Bpos > 0 && $endPos > $Bpos){
					$badXML = substr($rawText, $Bpos, ($endPos - $Bpos)+1);
					if(substr_count($badXML, " ") < 2){
						//gets rid of tags like: <?<o:p>
						$fixedText = str_replace($badXML, "", $fixedText);
						//echo "This replaced $badXML replaced ---- ".chr(13);
						//echo " --- Now this ----- ".chr(13).chr(13);
						//die;
					}
				}
				if($endPos > $pos){
					$pos = $endPos;
					$endPos = 0;
					$pos ++;
				}
				else{
					$pos ++;
				}
			}
		}
		$rawText = $fixedText;
		$textLen = strlen($rawText);
		if(strstr($rawText, "<?")){
			$pos = 0;
			while($pos < $textLen){
				$Bpos = strpos($rawText, "<?", $pos);
				$endPos = strpos($rawText, "/>", $Bpos);
				if($Bpos > 0 && $endPos > $Bpos){
					$badXML = substr($rawText, $Bpos, ($endPos - $Bpos)+2);
					if(substr_count($badXML, "<") < 2 && substr_count($badXML, ">") < 2){
						// Gets rid of tags like <?XML:NAMESPACE PREFIX = O />
						$fixedText = str_replace($badXML, "", $fixedText);
						//echo "This replaced $badXML replaced ---- ".chr(13);
						//echo " --- Now this ----- ".chr(13).chr(13);
						//die;
					}
				}
				if($endPos > $pos){
					$pos = $endPos;
					$endPos = 0;
					$pos ++;
				}
				else{
					$pos ++;
				}
			}
		}
		
		//echo " --- Now this: $fixedText  ";
		//die;
		return $fixedText;
	}
	 
	 
	 
	 
	 
	 function rootTrenchBookLookup($refs){
		  
		  if(strstr($refs, " ")){
				$refsEx = explode(" ", $refs);
		  }
		  else{
				$refsEx = array($refs);
		  }
		  $db = $this->startDB();
		  $output = false;
		  foreach($refsEx as $ref){
				if($ref != "TB"){
					 $comma = false;
					 $actRef = str_replace("_", " ", $ref);
					 if(strstr($actRef, ",")){
						  $comma = true;
						  $actRef = str_replace(",", "", $actRef);
					 }
					 $sql = "SELECT uuid, label FROM z_tb_scrape WHERE label = 'Trench Book ".$actRef."' AND tbtdid = 0 LIMIT 1; ";
					 $result =  $db->fetchAll($sql);
					 if($result){
						  $actOut = "<a href=\"http://opencontext.org/documents/".$result[0]["uuid"]."\" title=\"Link to ".$result[0]["label"]."\" target=\"_blank\">".$result[0]["label"]."</a>";
						  if($comma){
								$actOut .= ",";
						  }
					 }
					 else{
						  $actOut = $ref;
					 }
					 
					 
					 
					 if(!$output){
						  $output = $actOut;
					 }
					 else{
						  $output .= " ".$actOut;
					 }
				}
		  }
		  
		  return $output;
	 }
	 
	 
	 
	 function tagLowerCase($text){
		  
		  $remNumTags = array("p", "P", "F");
		  $maxNum = 10;
		  $i = 1;
		  while($i <= $maxNum){
				
				foreach($remNumTags as $numTag){
					 $bad = array();
					 $bad[0] = "<".$numTag.$i.">";
					 $bad[1] = "</".$numTag.$i.">";
					 $text = str_replace($bad[0], "",  $text);
					 $text = str_replace($bad[1], "",  $text);
				}
				$i++;
		  }
		  
		  $atribs = array(" face=\"" => " style=\"font-family:");
		  
		  foreach($atribs as $key => $atrib){
				$text = str_replace($key, $atrib,  $text);
		  }
		  
		  $text = str_replace("<BR>", "<br/>", $text);
		
		  $tags = array("P" => "p",
							 "A" => "a",
							 "STRONG" => "strong",
							 "strongLOCKQUOTE" => "blockquote",
							 "BLOCKQUOTE" => "blockquote",
							 "EM" => "em",
							 "OL" => "ol",
							 "UL" => "ul",
							 "LI" => "li",
							 "TABLE" => "table",
							 "TBODY" => "tbody",
							 "TR" => "tr",
							 "TD" => "td",
							 "SPAN" => "span",
							 "B" => "strong",
							 "FONT" => "span",
							 "o:p" => "span",
							 "O:P" => "span",
							 "L" => "span class=\"locus\" ",
							 "H1" => "h5",
							 "H2" => "h5",
							 "H3" => "h5",
							 "H4" => "h5",
							 //"TB" => "span class=\"trench-book\" ",
							 "U" => "span style=\"text-decoration:underline;\"",
							 "RED" => "span style=\"color:#FF0000;\"",
							 "GREEN" => "span style=\"color:#009900;\"",
							 "BLUE" => "span style=\"color:#0000FF;\"",
							 );
		  
		  foreach($tags as $key => $tag){
				
				$bad = array();
				$bad[0] = "<".$key;
				$bad[1] = "</".$key;
				
				$good = array();
				$good[0] = "<".$tag;
				if(strstr($tag, " ")){
					 $tEx = explode(" ", $tag);
					 $good[1] = "</".$tEx[0];
				}
				else{
					 $good[1] = "</".$tag;
				}
				
				$text = str_replace($bad[0], $good[0],  $text);
				$text = str_replace($bad[1], $good[1],  $text);
		  }
		  
		  return $text;
	 }
	
	
	function introLinks($rawText){
		//make a list of introductory links
		$links = array();
		$replaceText = $rawText;
		$pos = 0;
		$textLen = strlen($rawText);
		if(strstr($rawText, "<p><a")){
			while($pos < $textLen){
				$TDpos = strpos($rawText, "<p><a", $pos);
				$endTD = strpos($rawText, "</a></p>", $TDpos);
				if($TDpos > 0 && $endTD > $TDpos){
					$tag = substr($rawText, $TDpos, ($endTD - $TDpos));
					$tag .= "</a></p>";
					$actlink =  $this->getAttribute('href', $tag);
					$actName =  $this->getAttribute('name', $tag);
					$idTag = str_replace($actlink, '', $tag); //get rid of the href so it donesn't confuse looking for an id
					$oldID =  $this->getAttribute('id', $idTag);
					if(strlen($actlink)>1){
						if(stristr($actlink, "javascript:openViewer") && stristr($actlink, "trenchbookviewer.asp")){
							//$replaceText = str_replace($tag, "", $replaceText);
							if(stristr($actlink, "'")){
								$linkex = explode("'", $actlink);
								$actlink = $linkex[1]; //get the link from the javascript open
							}
							$actName = strtolower($actName);
							$actName = str_replace("p", "", $actName);
							if(strlen($actName)<1){
								$actName = "[Trench Book Ref.]";
								$actlink = html_entity_decode($actlink);
								$params = $this->getURLparams($actlink);
								if(is_array($params)){
									if(array_key_exists("searchpage", $params)){
										$actName = $params["searchpage"];
									}
								}
							}
							
							$linkID = "tb-page-id-".$actName;
							if(array_key_exists($linkID, $links)){
								$idCount = count($links) + 1;
								$linkID = "tb-page-id-".$idCount;
							}
							
							if(strlen($oldID) < 1){
								$replaceTag = str_replace("<a", "<a id=\"".$linkID."\"", $tag);
							}
							else{
								$replaceTag = str_replace("id=\"".$oldID."\"", " id=\"".$linkID."\" ", $tag);
								$replaceTag = str_replace("id='".$oldID."'", " id=\"".$linkID."\" ", $tag);
							}
							$replaceText = str_replace($tag, $replaceTag, $replaceText);
							$links[$actlink] = array('id' => $linkID, 'name' => $actName);
						}
					}
				}
				if($endTD > $pos){
					$pos = $endTD;
					$endTD = 0;
					$pos++;
				}
				else{
					$pos++;
				}
		   }
		}
		if(count($links)>0){
			$addText = "<div>".chr(13);
			$addText .= "<!-- BEGIN Links to page refs BEGIN -->";
			$addText .= "<h5>Page References</h5>".chr(13);
			$addText .= "<ul class='page-refs'>".chr(13);
			$i = 1;
			foreach($links as $link => $larray){
			 
			   //$link = html_entity_decode($link);
			   //$link = htmlentities($link);
			   $name = $larray['name'];
			   $link = "#".$larray['id'];
			   //$addText .= "<li><a id=\"intro-link-".$i."\" href=\"".$link."\" target=\"_blank\">".$name."</a></li>".chr(13);
			   $addText .= "<li><a id=\"intro-link-".$i."\" href=\"".$link."\" >".$name."</a></li>".chr(13);
			   $i++;
			}
			$addText .= "</ul>".chr(13);
			$addText .= "<!-- END Links to page refs END -->".chr(13).chr(13);
			$replaceTextLen = strlen($replaceText);
			$addTextLen = strlen($addText);
		    if($replaceTextLen >= ($addTextLen * 4)){
				//only add the Page ref links if the text is much longer than the index
				$replaceText = substr($replaceText, 5, ($replaceTextLen - 5));
				$replaceText = $addText.$replaceText;
		    }
		}
		return $replaceText;
	}
	
	
	//make certain that ID attributes are unique
	 function uniqueIDs($xml){
			$idArray = array();
			foreach($xml->xpath("//@id") as $xres){
				$actID = (string)$xres;
				if(!stristr($actID, 'tb-page-id')){
				  $actID = "PC-TB-".$actID;
				}
				if(in_array($actID, $idArray)){
					 $actID = $actID."-".count($idArray);
					 $idArray[] = $actID;
				}
				else{
					 $idArray[] = $actID;
				}
				$xres[0] = $actID;
			}
			return $xml;
	}
	
	
	function aspFindsLinkUpdate($text, $uuid){
		@$xml = simplexml_load_string($text);
		if($xml){
			foreach($xml->xpath("//a/@href") as $xres){
				$url = (string)$xres;
				// javascript:openViewer('/catalog/viewartifactcatalog.asp?aid=19900036,')
				if(stristr($url, "javascript:openViewer('/catalog/viewartifactcatalog.asp")){
					preg_match_all('/(\d{8})/', $url, $matches);
					foreach($matches[0] as $k => $obj) { 
						$objUUID = $this->getPCuuid($obj);
						if(strlen($objUUID)>4){
							$newURL = "http://opencontext.org/subjects/".$objUUID;
							$oldAttrib = 'href="'.$url.'"';
							$newAttribs = ' target="_blank" href="'.$newURL.'" title="Link to object PC '.$obj.'" ';
							$text = str_replace($oldAttrib, $newAttribs, $text);
						}
					}
					
				}
			}
		}
		return $text;
	}
	
	function removeBadPtags($replaceText){
	   // array of OK last characters for a closing paragraph tag
	   $goodclose = array(".", ">", ":", ";", "?", "!", "-", "'", "\"", ")", "]");
	   $pos = 0;
	   $oldReplaceText = $replaceText;
	   $textLen = strlen($oldReplaceText);
	   $closepos = 0;
	   while($pos < $textLen){
		  $openpos = strpos($oldReplaceText, "<p", $pos);
		  $closepos = strpos($oldReplaceText, "</p>", $openpos);
		  if($closepos > ($pos-1)){
			 if($closepos >= $openpos + 140){
				$preclose = substr($oldReplaceText, ($closepos), 1);
				if(!in_array($preclose, $goodclose)){
				    $replaceClose = $preclose."</p>";
				    $replaceText = str_replace($replaceClose, " ", $replaceText);
				}
				$pos = $closepos;
			 }
		  }
		  $pos++;
	   }
	   return $replaceText;
    }
	
	function ObjectIDLinking($rawText){
		$objects = array();
		preg_match_all('/(\d{8})/', $rawText, $matches);
		foreach($matches[0] as $k=>$v) { 
            $objects[] = $v; 
		}
		$change = false;
		$revisedText = $rawText;
		if(count($objects) > 0){
			$textLen = strlen($rawText);
			foreach($objects as $obj){
				$objPos = strpos($rawText, $obj, 0);
				$next = "";
				$prev = "";
				if($objPos + 20 < $textLen){
					$next = substr($rawText, $objPos+8, 8);
				}
				if($objPos - 50 >= 0){
					$prev = substr($rawText, $objPos - 50, 50);
					$prevContext = substr($rawText, $objPos - 8, 8);
				}
				if(!stristr($next, "</a>") && !stristr($next, "\"") && !stristr($next, "'") && !stristr($prev, ".asp") && !stristr($prev, "href=") && !stristr($prev, "\"") && !stristr($prev, "\'") ){
					$objUUID = $this->getPCuuid($obj);
					$contextObj = $prevContext.$obj.$next;
					if(strlen($objUUID)>4){
						//found the object!
						$change = true;
						if(!stristr($rawText, $objUUID)){
							 //uuid isn't in this, so we're pretty safe to make replacements as no hyperlink currently exists
							 
							 $newLink = " <a href=\"http://opencontext.org/subjects/".$objUUID."\" title=\"Link to object PC".$obj."\" target=\"_blank\">".$obj."</a> ";
							 $revContext = str_replace($obj, $newLink, $contextObj);
							 $revisedText = str_replace($contextObj, $revContext, $revisedText);
						}
					}
				}
			}
		}
		return $revisedText;
	}
	
	function styleTweek($xpath, $dom){
		  $okStyles = array("text-decoration:underline;",
		  "color:#FF0000;",
		  "color:#009900;",
		  "color:#0000FF;");
		  
		  //get rid of these classes
		  $badClasses = array("MsoNormal",
							  "MsoNoSpacing",
							  "MsoListParagraphCxSpMiddle",
							  "MsoListParagraph",
							  "Standard");
		  
		  //get rid of presentation style attributes. this won't look very nice
		  $badAttributes = array("dir", "align", "face", "lang", "height", "width", "valign", "color",
								 "clear", "size", "cellspacing", "cellpadding", "border", "table");
		  
		  
		  foreach($badAttributes as $attrib){
				$query = "//@".$attrib;
				foreach($xpath->query($query) as $node) {
					 $node->parentNode->removeAttributeNode($node);
				}
		  }
		  
		  $query = "//@style";
		  foreach($xpath->query($query) as $node) {
				$actStyle = $node->nodeValue;
				if(!in_array($actStyle, $okStyles)){
					$node->parentNode->removeAttributeNode($node);
				}
		  }
		  
		$query = "//@class";
		foreach($xpath->query($query) as $node) {
			$actClass = $node->nodeValue;
			if(in_array($actClass , $badClasses)){
				$node->parentNode->removeAttributeNode($node);
			}
		}
		  
		return $dom;
	 }
	 
	 
	//update links in the XML
	function simpleXMLlinksSrc($xml, $uuid){
		  //$xml is a simple xml object
		  $db = $this->startDB();
		  $iterator = 0;
		  $list = $xml->xpath("//a");
			$listCount = count($list);
			unset($list);
		  foreach($xml->xpath("//a") as $xout){
				if(strlen($uuid)>4){
					$data = array("iteration" => $iterator);
					$where = "uuid = '$uuid' ";
					$db->update("z_tb_scrape", $data, $where);
				}
				$iterator++;
				if($iterator > $listCount){
					echo "Infinite XPATH encountered. Needs to be less than: ".$listCount;
					die;
				}
				else{
					$photo = false;
					$findUUID = false;
					$scanFound = false;
					$tbFound = false;
					$PCid = false;
					
					foreach($xout->xpath("@href[position() < 2]") as $xres){
						 $href = (string)$xres;
						 if(stristr($href, "javascript:viewPhoto")){
								preg_match('#\((.*?)\)#', $href, $match);
								if(count($match)>1){
									$photo = $this->stripQuotes($match[1]);
									$uuidThumb = $this->getImageUUIDandThumb($photo, $uuid);
									if($uuidThumb != false){
										  $xres[0] = "http://opencontext.org/media/".$uuidThumb["uuid"];
									}
								}
						 }
						 elseif(stristr($href, "javascript:openViewer") && stristr($href, "viewartifactcatalog") ){
							  preg_match('#\((.*?)\)#', $href, $match);
							  if(count($match)>1){
								$artifactLink = $this->stripQuotes($match[1]);
								$PCid = $this->getPCIDfromArtifactLink($artifactLink);
								if($PCid != false){
									  $findUUID = $this->getPCuuid($PCid); //get the UUID from the artifact's PC number
								}
								if($findUUID != false){
									  $xres[0] = "http://opencontext.org/subjects/".$findUUID;
								}
								else{
									  $this->recordMissingRef($uuid, $PCid, "space");
								}
							  }
						 }
						 elseif(stristr($href, "javascript:openViewer") && stristr($href, "trenchbookviewer.asp") ){
							  preg_match('#\((.*?)\)#', $href, $match);
							  $scanLink = $this->stripQuotes($match[1]);
							  $scanIDs = $this->getScanIDsfromLink($scanLink);
							  if(is_array($scanIDs)){
									$scanFound = $this->getUUIDfromScanIDs($scanIDs); //get the UUID from the artifact's PC number
									if($scanFound != false){
										 $newLink = "http://opencontext.org/media/".$scanFound['uuid'];
										 $xres[0] = $newLink;
										 
										 foreach($xml->xpath("//a[@href='$newLink']") as $xresB){
											  $linkVal = (string)$xresB;
											  if(is_numeric(trim($linkVal))){
													$xresB[0] = "Page $linkVal scan";
											  }
										 }
										 
									}
									else{
										 $this->recordMissingRef($uuid, $scanLink, "scan");
									}
							  }
						 }
						 elseif(stristr($href, "javascript:openViewer") && stristr($href, "viewtrenchbookreference.asp") ){
							  preg_match('#\((.*?)\)#', $href, $match);
							  $tbLink = $this->stripQuotes($match[1]);
							  $tbIDs = $this->getTbIDsfromLink($tbLink);
							  if(is_array($tbIDs)){
									$tbFound = $this->getUUIDfromTrenchBookIDs($tbIDs); //get the UUID from the artifact's PC number
									if($tbFound != false){
										 $newLink = "http://opencontext.org/documents/".$tbFound['uuid'];
										 $xres[0] = $newLink;
									}
									else{
										 $this->recordMissingRef($uuid, $tbLink, "diary");
									}
							  }
						 }
					}
					if($photo != false){
						 $xout->addAttribute("title", "Link to photo ".$photo);
						 $xout->addAttribute("target", "_blank");
					}
					if($findUUID != false){
						 $xout->addAttribute("title", "Link to find PC ".$PCid);
						 $xout->addAttribute("target", "_blank");
					}
					if($scanFound != false){
						 $xout->addAttribute("title", "Link to scan of ".$scanFound['label']);
						 $xout->addAttribute("target", "_blank");
					}
					if($tbFound != false){
						 $xout->addAttribute("title", "Link to transcript of ".$tbFound['label']);
						 $xout->addAttribute("target", "_blank");
					}
				}
		  }
			$iterator = 0;
			$list = $xml->xpath("//img");
			$listCount = count($list);
			unset($list);
			foreach($xml->xpath("//img") as $xout){
				if(strlen($uuid)>4){
					$data = array("iteration" => $iterator);
					$where = "uuid = '$uuid' ";
					$db->update("z_tb_scrape", $data, $where);
				}
				$iterator++;
				if($iterator > $listCount){
					echo "Infinite XPATH (//img) encountered. Needs to be less than: ".$listCount;
					die;
				}
				else{
					$photo = false;
					foreach($xout->xpath("@src") as $xres){
						$src = (string)$xres;
						if(stristr($src, ".jpg")){
							$uuidThumb = $this->getImageUUIDandThumb($src, $uuid);
							if($uuidThumb != false){
								$xres[0] = $uuidThumb["thumb"];
							}
						}
					}
					if($photo != false){
						 $xout->addAttribute("title", "Photo ".$photo);	 
					}
				}
			}
		return $xml;
	}
		
	function textXMLify($useText, $step = 'Not provided', $fixTry = true){
		//$useText = str_replace('Â', '', $useText);
		$useText = str_replace("&#13;", " ", $useText);
		libxml_use_internal_errors(true);
		@$xml = simplexml_load_string($useText);
		if($xml === false && $fixTry){
			$useText = $this->HTMLify($useText);
			@$xml = simplexml_load_string($useText);
		}
		if($xml === false) {
			echo "<h1>Failed loading XML [Step: $step]</h1>\n";
			foreach(libxml_get_errors() as $error) {
				echo "\t", $error->message;
			}
			echo $useText;
			die;
			
		}
		else{
			$dom = new DOMDocument('1.0', 'UTF-8');
			$dom->preserveWhiteSpace = false;
			$dom->formatOutput = true;
			if(!@$dom->loadXML("".$useText)){
				echo "<h1>BAD DOM XML! [Step: $step]</h1>";
				echo $useText;
				die;
			}
			unset($useText);
			$useText = $dom->saveXML($dom->documentElement);
			//$useText = preg_replace('/[^(\x20-\x7F)]*/','', $useText);
			//$useText = str_replace('Â', '', $useText);
			unset($dom);
		}
		return $useText;
	}
	
	function HTMLify($useText){ 
		$useText = tidy_repair_string($useText); //make the text parsable
		$dom= new DOMDocument('1.0', 'UTF-8');
		@$dom->loadHTML($useText);      //load it into a dom as html
		$xpath = new DOMXPath($dom);
		$dom = $this->styleTweek($xpath, $dom); //get rid of unwanted style information
		//make sure links are not empty
		$query = "//a";
		foreach($xpath->query($query) as $node) {
			if(!$node->hasChildNodes()) {
				$aText = $node->nodeValue;
				$aText = trim($aText);
				if(strlen($aText) < 1){
					$node->nodeValue = "[Link]";
				}
			}
		}
		$div = $xpath->query('/html/body/div');
		$useText = ($dom->saveXml($div->item(0))); //get rid of the html and body
		return $useText;
	} 
	
	function hideMissing($content){
		$dom = new DOMDocument('1.0', 'UTF-8');
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;
		if(!@$dom->loadXML($content)){
			echo "<h1>BAD DOM XML! Hidding Missing</h1>";
			echo $content;
			die;
		}
		else{
			$xpath = new DOMXPath($dom);
			$dom = $this->styleTweek($xpath, $dom); //get rid of unwanted style information
			//make sure links are not empty
			$query = "//a/@href";
			foreach($xpath->query($query) as $node) {
				$href = $node->nodeValue;
				if(stristr($href, "javascript") || stristr($href, '#broken-link')) {
					//link not resolable now
					$node->nodeValue = '#broken-link';
					$pnode = $node->parentNode;
					$pnode->setAttribute("style", "color:#666666;");
					$pnode->setAttribute("title", $href);
				}
			}
			$query = "//img/@src";
			foreach($xpath->query($query) as $node) {
				$src = $node->nodeValue;
				if(!stristr($src, "http://")) {
					//no image link
					$pnode = $node->parentNode;
					$pnode->setAttribute("style", "display:none;");
				}
			}
			$content = $dom->saveXML($dom->documentElement);
			//echo $content;
			//die;
		}
		return $content;
	}
	
	
	function TBprocessHideMissing(){
		$db = $this->startDB();
		$output = array();
		
		$sql = "SELECT uuid, pcURI, hasGZIP, contHash
		FROM z_tb_scrape
		WHERE uuid != ''
		AND content != ''
		";
		
		$output["sql"] = $sql;
		$result =  $db->fetchAll($sql);
		$resLen = count($result);
		$output["resCount"] = $resLen;
		$output["changCount"] = 0;
		foreach($result as $row){
			$uuid = $row['uuid'];
			$url = $row['pcURI'];
			$hasGZIP = $row['hasGZIP'];
			$contentHash = $row['contHash'];
			$resB = false;
			$sql = "SELECT content FROM z_tb_scrape WHERE pcURI = '$url' AND uuid = '$uuid' LIMIT 1;";
			$resB =  $db->fetchAll($sql);
			$oldContent = $resB[0]['content'];
			$content = $this->hideMissing($oldContent);
			if($content != $oldContent){
				$output[$uuid] = strlen($content);
				if($resLen <2){
					echo $content;
					die;
				}
				$output["changCount"]++;
				$data = array("content" => $content,
							  "contHash" => sha1($content),
							  "lprocess" => 1);
				$where = "pcURI = '$url' ";
				$db->update("z_tb_scrape", $data, $where);
			}
		}
		
		return $output;
	}
	
	
	
	
	function TBprocessCompressed(){
		$db = $this->startDB();
		$output = array();
		/*
		$sql = "SELECT uuid, pcURI, hasGZIP, contHash
		FROM z_tb_scrape
		WHERE uuid = '13FA9CB0-8393-4C60-0F3F-D916EFD83C4D'
		";
		
		$sql = "SELECT uuid, pcURI, hasGZIP, contHash
		FROM z_tb_scrape
		WHERE uuid = '6E824024-788E-4334-97F8-15B70F4306A8'
		";
		
		$sql = "SELECT uuid, pcURI, hasGZIP, contHash
		FROM z_tb_scrape
		WHERE uuid = '045441A2-B937-44F9-33D6-06044C22A325'
		";
		
		$sql = "SELECT uuid, pcURI, hasGZIP, contHash
		FROM z_tb_scrape
		WHERE uuid = '045441A2-B937-44F9-33D6-06044C22A325'
		";
		
		*/
		
		$sql = "SELECT uuid, pcURI, hasGZIP, contHash
		FROM z_tb_scrape
		WHERE uuid != ''
		AND content = ''
		";
		
		$output["sql"] = $sql;
		$result =  $db->fetchAll($sql);
		$resLen = count($result);
		foreach($result as $row){
			$uuid = $row['uuid'];
			$url = $row['pcURI'];
			$hasGZIP = $row['hasGZIP'];
			$contentHash = $row['contHash'];
			$this->trenchBookScrapeDeDuplicate($url, $contentHash);
			$resB = false;
			if($hasGZIP > 0){
				$sql = "SELECT compressed FROM z_tb_scrape WHERE pcURI = '$url' AND uuid = '$uuid' LIMIT 1;";
				$resB =  $db->fetchAll($sql);
				$html = gzuncompress($resB[0]['compressed']);
			}
			else{
				$sql = "SELECT html FROM z_tb_scrape WHERE pcURI = '$url' AND uuid = '$uuid' LIMIT 1;";
				$resB =  $db->fetchAll($sql);
				$html = $resB[0]['html'];
			}
			
			$html = str_replace("‘", "'", $html);
			$html = str_replace("’", "'", $html);
			//$html = $this->styleReduce($html, "TD");
			$output[$uuid] = false;
			
			$parts = $this->contentTagAlt($html);
			if(!$parts["before"] || !$parts["content"] || !$parts["after"]){
				//echo "crap! ".$uuid;
				//die;
			}
			
			$content = $parts["content"];
			$output[$uuid] = array('content' => substr($content, 0, 120));
			$content = $this->cleanContent($content, $uuid, $url);
			if($resLen <2){
				echo $content;
				die;
			}
			
			$data = array("content" => $content,
						  "contHash" => sha1($content),
						  "lprocess" => 1);
			$where = "pcURI = '$url' ";
			$db->update("z_tb_scrape", $data, $where);
		}
		
		return $output;
	}
	 
	function cleanContent($rawText, $uuid, $url){
		//cleans content, removing ugly tags, looks up links
		$db = $this->startDB();
		/*
		$rawText = str_replace("<?xml:namespace prefix = o ns = \"urn:schemas-microsoft-com:office:office\" /?>", '', $rawText);
		$rawText = str_replace("<?xml:namespace prefix = o ns = \"urn:schemas-microsoft-com:office:office\" /??>", '', $rawText);
		$rawText = str_replace("<?xml:namespace prefix = st1 ns = \"urn:schemas-microsoft-com:office:smarttags\" /?>", '', $rawText);
		$rawText = str_replace("<?xml:namespace prefix = st1 ns = \"urn:schemas-microsoft-com:office:smarttags\" /??>", '', $rawText);
		$rawText = str_replace("<?XML:NAMESPACE PREFIX = O /??>", '', $rawText);
		*/
		$rawText = $this->BadXMLfix($rawText);
		$rawText = $this->TBtagFix($rawText);
		$rawText = $this->tagLowerCase($rawText);
		$rawText = $this->introLinks($rawText);
		$rawText = $this->wordFix($rawText);
		
		$rawText = mb_convert_encoding($rawText, "UTF-8");
		$rawText = preg_replace('/[^(\x20-\x7F)]*/','', $rawText);
		$rawText = $this->HTMLify($rawText); //make the text parsable
		$rawText = str_replace("&#13;", " ", $rawText);
		@$xml = simplexml_load_string('<?xml version="1.0" encoding="utf-8"?> '.$rawText);
		if($xml){
			$xml = $this->uniqueIDs($xml); //make sure the IDs are unique
			$xml = $this->simpleXMLlinksSrc($xml, $uuid); //update the links, references to image srcs
			$useText = $xml->asXML();
			//$useText = preg_replace('/[^(\x20-\x7F)]*/','', $useText);
		}
		else{
			$rawText = $this->HTMLify($rawText); //make the text parsable
			$xml = $this->uniqueIDs($xml); //make sure the IDs are unique
			$xml = $this->simpleXMLlinksSrc($xml, $uuid); //update the links, references to image srcs
			$useText = $xml->asXML();
		}
		
		//$useText = mb_convert_encoding($useText, "UTF-8");
		$useText = $this->textXMLify($useText, 'First link extraction');
		
		//echo $useText;
		//die;
		
		$useText = $this->aspFindsLinkUpdate($useText, $uuid);
		//$useText = mb_convert_encoding($useText, "UTF-8");
		$useText = $this->ObjectIDLinking($useText);
		
		//$useText = mb_convert_encoding($useText, "UTF-8");
		$useText = $this->textXMLify($useText, 'Into links');
		//$useText = mb_convert_encoding($useText, "UTF-8");
		$useText = $this->removeBadPtags($useText);
		
		$useText = $this->HTMLify($useText); //make the text parsable
		//$useText = mb_convert_encoding($useText, "UTF-8");
		$useText = preg_replace('/[^(\x20-\x7F)]*/','', $useText);
		$useText = $this->textXMLify($useText, 'Bad P tags');
		
		return $useText;
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
	
	function wordFix($string){
		$search = [                 // www.fileformat.info/info/unicode/<NUM>/ <NUM> = 2018
                "\xC2\xAB",     // « (U+00AB) in UTF-8
                "\xC2\xBB",     // » (U+00BB) in UTF-8
                "\xE2\x80\x98", // ‘ (U+2018) in UTF-8
                "\xE2\x80\x99", // ’ (U+2019) in UTF-8
                "\xE2\x80\x9A", // ‚ (U+201A) in UTF-8
                "\xE2\x80\x9B", // ‛ (U+201B) in UTF-8
                "\xE2\x80\x9C", // “ (U+201C) in UTF-8
                "\xE2\x80\x9D", // ” (U+201D) in UTF-8
                "\xE2\x80\x9E", // „ (U+201E) in UTF-8
                "\xE2\x80\x9F", // ‟ (U+201F) in UTF-8
                "\xE2\x80\xB9", // ‹ (U+2039) in UTF-8
                "\xE2\x80\xBA", // › (U+203A) in UTF-8
                "\xE2\x80\x93", // – (U+2013) in UTF-8
                "\xE2\x80\x94", // — (U+2014) in UTF-8
                "\xE2\x80\xA6"  // … (U+2026) in UTF-8
				];

		$replacements = [
					"<<", 
					">>",
					"'",
					"'",
					"'",
					"'",
					'"',
					'"',
					'"',
					'"',
					"<",
					">",
					"-",
					"-"
		];

		$actText = str_replace($search, $replacements, $string);
		$textLen = strlen($actText);
		$i = 0;
		$newText = "";
		while($i < $textLen){
			$actChar = substr($actText, $i, 1);
			$actChar = preg_replace('/[^(\x20-\x7F)]*/',' ', $actChar);
			$actChar = trim($actChar);
			if(strlen($actChar)<1){
				$actChar = " ";
			}
			$newText .= $actChar;
			$i++;
		}
		return $newText;
	}

	
	
    
}  
