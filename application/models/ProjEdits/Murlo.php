<?php
/*
This class edits Murlo data

I'm including it, since it may be useful to adapt to other projects, also it adds a little documentation to my data wrangling

*/
class ProjEdits_Murlo  {
    
   
	
    public $db;
	 
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
		  
		  $tags = array("P" => "p",
							 "A" => "a",
							 "STRONG" => "strong",
							 "strongLOCKQUOTE" => "strong",
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
							 "U" => "span style=\"text-decoration:underline;\"",
							 "FONT" => "span",
							 "L" => "span class=\"locus\" ",
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
		  $label = "PC ".$numericLabel;
		  $sql = "SELECT uuid FROM space WHERE space_label = '$label' LIMIT 1;";
		  $result =  $db->fetchAll($sql);
		  if($result){
				$output = $result[0]["uuid"];
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
