<?php

/** Zend_Controller_Action */
//require_once 'Zend/Controller/Action.php';
//require_once 'OpenContext/Controller/Action/Helper/SolrAccess.php';

//error_reporting(E_ALL ^ E_NOTICE);

class pdfController extends Zend_Controller_Action {
    
    
    
    //make sure all connections are UTF-8 OK
    private function setUTFconnection($db){
	$sql = "SET collation_connection = utf8_unicode_ci;";
	$db->query($sql, 2);
	$sql = "SET NAMES utf8;";
	$db->query($sql, 2);
    }
    
    private function testLineBreak($string){
	$output = false;
	if(mb_stristr($string, "\r")){
		$output = true;
	}
	elseif(mb_stristr($string, "\n")){
		$output = true;
	}
	return $output;
    }
    
    public function testAction(){
	$this->_helper->viewRenderer->setNoRender();
	
	$textRaw = file_get_contents('public/xmlFiles/Web2_Book_working-2.pdf.txt');
	
	$dideroPos = stripos($textRaw, "Diderot");
	$firstPart = substr($textRaw, 0, ($dideroPos - 635));
	$text = str_replace($firstPart, "", $textRaw);
	unset($textRaw);
	unset($firstPart);

	
	$i = 0;
	$textLen = strlen($text)-6;
	$newText = "";
	$pagesArray = array();
	while($i < $textLen){
		$pageFound = false;
		$testChar = substr($text, $i, 1);
		if(is_numeric($testChar) && $testChar != 0){
			$testCharB = substr($text, $i+1, 1);
			$testCharC = substr($text, $i+2, 1);
			$testCharD = substr($text, $i+3, 1);
			$testCharE = substr($text, $i+4, 1);
			$testCharF = substr($text, $i+5, 1);
			$testCharG = substr($text, $i+6, 1);
			
			if(is_numeric($testCharB)){
				if(is_numeric($testCharC)){
					$afterNum1 = $testCharD;
					$afterNum2 = $testCharE;
					$afterNum3 = $testCharF;
					$afterNum4 = $testCharG;
					$addVal = 6;
				}
				else{
					$afterNum1 = $testCharC;
					$afterNum2 = $testCharD;
					$afterNum3 = $testCharE;
					$afterNum4 = $testCharF;
					$addVal = 5;
				}
			}
			else{
				$afterNum1 = $testCharB;
				$afterNum2 = $testCharC;
				$afterNum3 = $testCharD;
				$afterNum4 = $testCharE;
				$addVal = 4;
			}
			
			if($this->testLineBreak($afterNum1) && $this->testLineBreak($afterNum2) && $this->testLineBreak($afterNum3) && $this->testLineBreak($afterNum4)){
				$pageFound = true;
				$currentPageNumber = trim($testChar.$testCharB.$testCharC);
				$currentPageNumber = $currentPageNumber - 0;
			}
			else{
				$addVal = 0;
			}
			
		}
		
		if($pageFound){
			$toNewText = $testChar.$testCharB.$testCharC.$testCharD;
			//$toNewText =  "<currentPage>".$currentPageNumber."</currentPage>".$testChar.$testCharB.$testCharC.$testCharD."<PAGE>";
			//echo $toNewText;
			$newText .= $toNewText;
			$i = $i + $addVal;
			$pagesArray[$currentPageNumber] = $newText;
			$newText = "";
		}
		else{
			$toNewText = $testChar;
			//echo $toNewText;
			$newText .= $toNewText;
			$i++;
		}
		
		
		/*
		echo $testChar;
		$i++;
		*/
	}
	
	
	
	
	$db = Zend_Registry::get('db');
	$referenceSection = false;
	foreach($pagesArray as $pageNum => $page){
		
		$page = $this->keyphrases($page);
		$wordLines = preg_split("/((\r(?!\n))|((?<!\r)\n)|(\r\n))/", $page);
		echo "<br/></br><strong>Page: ".$pageNum."</strong>";
		echo "<br/>";
		$uniqueWords = array();
		
		$numLines = count($wordLines);
		$wordLineCount = 0;
		foreach($wordLines as $wordline){
			
			$wordLineCount ++;
			$wordline = str_replace("2.0", "2_0", $wordline);
			$wordline = str_replace("1.0", "1_0", $wordline);
			$wordline = str_replace("3.0", "3_0", $wordline);
			if(stristr($wordline, "referencescited")){
				$referenceSection = $pageNum."-".$numLines."-".$wordLineCount;
				echo "<em>".$referenceSection."</em>";
			}
			if($referenceSection != false){
				if(strstr($wordline, "SECTION") || strstr($wordline, "CHAPTER") ){
					$referenceSection = false;
				}
			}
			
			$delimiters = array(" ",
					    ","
					    );
			
			$wordsTry = $this->explodeX($delimiters, $wordline);
			$words = array();
			foreach($wordsTry as $tempWord){
				if(!stristr($tempWord, "http://")){
					//$tempWord = htmlentities($tempWord);
					$tempWord = str_replace(chr(147), "", $tempWord); //left quote
					$tempWord = str_replace(chr(148), " ", $tempWord); //right quote
					$tempWord = str_replace(chr(146)."s", " ", $tempWord); //right quote
					
					$tempWord = str_replace("(", " ", $tempWord);
					$tempWord = str_replace(")", " ", $tempWord);
					$tempWord = str_replace(":", " ", $tempWord);
					$tempWord = str_replace("?", " ", $tempWord);
					
					$tempWordLen = strlen($tempWord);
					$lastLetter = substr($tempWord, ($tempWordLen-1), 1);
					$alphastring = preg_replace("/[^a-zA-Z0-9]/", "", $lastLetter);
					if(strlen($alphastring)<1){
						$tempWord = substr($tempWord, 0, ($tempWordLen-1));
					}
					
					
					if(stristr($tempWord, ".") || stristr($tempWord, " ")){
						$delimiters = array(" ", ".");
						$temp2Words = $this->explodeX($delimiters,$tempWord);
						foreach($temp2Words as $temp2word){
							if((strlen($tempWord)>0) && !is_numeric($temp2word)){
								$words[] = $temp2word;
							}
						}
					}
					else{
						if((strlen($tempWord)>0) && !is_numeric($tempWord)){
							$words[] = $tempWord;
						}
					}
				}
				else{
					if((strlen($tempWord)>0) && !is_numeric($tempWord)){
						$words[] = $tempWord;
					}
				}
			}
			
			
			foreach($words as $word){
				$word = strtolower($word);
				
				if($referenceSection != false){
					$word = "|REFSEC|".$word;
				}
				
				if(array_key_exists($word, $uniqueWords)){
					$uniqueWords[$word] = $uniqueWords[$word] + 1;
				}
				else{
					$uniqueWords[$word] = + 1;
				}
			}
		}
		
		
		foreach($uniqueWords as $wordKey => $wordCount){
			if(!$this->is_stop_word($wordKey, $db)){
				$this->save_word($wordKey, $pageNum, $wordCount, $referenceSection, $db);
				echo $wordKey."[$wordCount]";
			}
		}
		
		
	}//end loop through pages
	
	$sql = "DELETE FROM book_index WHERE refsec != 0";
	$where = "refsec != 0";
	$db->delete("book_index", $where);
	
    }//end function
    
    
    
    private function is_stop_word($word, $db){
	
	$output = false;
	if(strlen($word)>1){
		$word = strtolower($word);
		$word = addslashes($word);
		$sql = "SELECT id FROM stop_words WHERE word LIKE '".$word."' LIMIT 1; ";
		
		$result = $db->fetchAll($sql, 2);
		if($result){
			$output = true;
		}
	}
	else{
		$output = true;
	}
	
	return $output;
    }
    
    private function save_word($word, $page, $pageCount, $referenceSection, $db){
	$idHash = sha1($page."_".$word);
	$data = array("id_hash" => $idHash,
		      "word" => $word,
		      "page" => $page,
		      "pageCount" => $pageCount,
		      "refsec" => $referenceSection
		      );
	
	$db->insert("book_index", $data);
	
    }
    
    
    
    
   
   private function keyphrases($page){
	$phraseArray = array("Web 2.0", "information overload", "Google Earth", "Google Maps", "Google Docs", "Open Context", "California Digital Library",
			     "Semantic Web", "Linked Open Data", "Linked Data", "Open Data", "Dublin Core", "Social Web",
			     "World Heritage", "cultural heritage", "Web services", "Natural language processing", "information retrieval",
			     "artificial intelligence", "information extraction", "Ordnance Survey", "born digital",
			     "University of British Columbia", "Digital Objects", "digital museums", "resource discovery",
			     "heritage management", "cultural resource", "visual representations", "Virtual excavations", "virtual site",
			     "virtual museum", "virtual archaeology", "virtual reality", "virtual reconstruction", "virtual world",
			     "World Wide Web", "University of California", "Los Angeles", "Inukshuk High School", "High School",
			     "Cambridge University", "Museum of Archaeology and Anthropology", "Social Web", "University of Reading",
			     "Scottish Urban Archaeological Trust", "Mobile Web", "JavaScript Object Notation", "International Council for Archaeozoology",
			     "social media", "knowledge management", "Creative Commons", "Near East", "Alexandria Archive", "tacit knowledge",
			     "Society for American Archaeology", "Stoa Consortium", "Ancient World Bloggers Group", "long tail",
			     "University College London", "Scalable Vector Graphics", "Visual Basic", "Social Network",
			     "National Parks Service", "data publication", "University of Southampton", "British Columbia",
			     "gray literature", "grey literature", "Virtual Research Environment for Archaeology", "Web 1.0", "Web 3.0",
			     "Public Library of Science", "Pyramid Texts", "Encyclopedia of Egyptology", "Digital Library", "Primary data",
			     "Text Encoding Initiative", "time map", "application programming", "Old Kingdom", "Middle Kingdom", "New Kingdom",
			     "United Kingdom", "European Union", "Badè Museum", "Representational State Transfer", "Really Simple Syndication"
,			     "Brigham Young University", "open access", "Harvard University", "Stanford University", "Museum Anthropology",
			     "open archaeology prize", 	"PubMed Central", "National Endowment for the Humanities", "National Science Foundation",
			     "Internet Archive", "Wayback Machine", "Tim O’Reilley", "National Park Service", "New Mexico", "United States",
			     "English Heritage", "Perseus Project", "Digital Archaeological Archive of Comparative Slavery",  "Mellon Foundation",
			     "Association of Research Libraries", "Archaeology Data Service", "Digital Antiquity", "Open Geospatial Consortium",
			     "Midwestern Taxonomic System", "Hadrian’s Wall", "University of Birmingham",  "Byzantine Empire", "Portable Antiquities Scheme",
			     "service-oriented architecture", "service oriented architecture", "data preservation",  "data sharing", "social tagging",
			     "crowd source", "intellectual property", "web developers", "Web services", "scholarly communications", "scholarly media",
			     "Institute of Museum and Library Services", "application program interfaces", "Faceted Classification", "Faceted search",
			     "born digital", "faceted browsers", "faceted browse", "Joint Information Systems Committee", "archival resource keys",
			     "Arts and Humanities Research Council", "Named-entity recognition", "knowledge engineering", "geographic information systems"
			     );
			    
	
	foreach($phraseArray as $searchPhrase){
		$replacePhrase = str_ireplace(" ", "_", $searchPhrase);
		$page = str_ireplace($searchPhrase, $replacePhrase, $page);
	}
	
	return $page;
   }//end function
   
   
   
   /* This function finds numeric characters in a name
     so that they can be used for sorting
    */
    private function explodeX($delimiters,$string){
	$return_array = Array($string); // The array to return
	$d_count = 0;
	while (isset($delimiters[$d_count])) // Loop to loop through all delimiters
	{
	    $new_return_array = Array(); 
	    foreach($return_array as $el_to_split) // Explode all returned elements by the next delimiter
	    {
		$put_in_new_return_array = explode($delimiters[$d_count],$el_to_split);
		foreach($put_in_new_return_array as $substr) // Put all the exploded elements in array to return
		{
		    $new_return_array[] = $substr;
		}
	    }
	    $return_array = $new_return_array; // Replace the previous return array by the next version
	    $d_count++;
	}
	return $return_array; // Return the exploded elements
    }
   
   
   //review data
   function reviewAction(){
	$this->_helper->viewRenderer->setNoRender();
	$db = Zend_Registry::get('db');
	$page = $_REQUEST["page"];
	if(isset($_REQUEST["numChanges"])){
		$numChanges = $_REQUEST["numChanges"];
	}
	else{
		$numChanges = "[none]";
	}
	
	$sql = "SELECT * FROM book_index WHERE page = $page";
	$result = $db->fetchAll($sql, 2);
	echo "Changes: ".$numChanges." <a href='review?page=".($page+1)."'>Next Page</a>";
	echo "<form action='review-do' method='get' >".chr(13);
	echo "<input type='hidden' name='page' value='".$page."' />";
	echo "<table>".chr(13);
	echo "<th><td>ID</td><td>ID(second)</td><td>Word</td><td>Page</td><td>PageCount</td></th>".chr(13);
	$lastId  = 0;
	foreach($result as $row){
		echo "<tr id='".$row["id"]."'>";
		echo "<td><strong>".$row["id"]."</strong> A:<input type='checkbox' name='idA' value='".$row["id"]."' /></td>";
		echo "<td>B: <input type='checkbox' name='idB' value='".$row["id"]."' /></td>";
		if($row["keywordvotes"]>0){
			echo "<td><strong><a href='vote-word?lastID=".$lastId."&page=".$page."&id=".$row["id"]."'>".$row["word"]."</a></strong> (".$row["keywordvotes"].")</td>";
		}
		else{
			echo "<td><a href='vote-word?lastID=".$lastId."&page=".$page."&id=".$row["id"]."'>".$row["word"]."</a></td>";
		}
		
		echo "<td>".$row["page"]."</td>";
		echo "<td>".$row["pageCount"]." <a href='delete-word?lastID=".$lastId."&page=".$page."&id=".$row["id"]."'>Delete</a></td>";
		echo "<tr>".chr(13);
		$lastId = $row["id"];
	}
	echo "</table>".chr(13);
	echo '<input type="submit" value="Submit" />';
	echo "</form>";
	echo "<a href='review?page=".($page+1)."'>Next Page</a>";
   }
   
   
   
   function deleteWordAction(){
	$this->_helper->viewRenderer->setNoRender();
	$db = Zend_Registry::get('db');
	$page = $_REQUEST["page"];
	$id = $_REQUEST["id"];
	if(isset($_REQUEST["lastID"])){
		$lastId = $_REQUEST["lastID"];
	}
	else{
		$lastId = false;
	}
	
	$sql = "SELECT * FROM book_index WHERE id = $id LIMIT 1; ";
	//echo "<br/>".$sql;
	$result = $db->fetchAll($sql, 2);
	$badWord = $result[0]["word"];
	
	$sql = "SELECT * FROM book_index WHERE word = '$badWord' ";
	$result = $db->fetchAll($sql, 2);
	$numChanges = 0;
	foreach($result as $row){
		$badWordId = $row['id'];
		$where = array();
		$where[] = "id = $badWordId ";
		$db->delete("book_index", $where);
		$numChanges = $numChanges + 1;
	}
	
	if($page !=0){
		$pageURI = "http://penelope2.oc/pdf/review?try=".md5($sql)."&page=".$page."&numChanges=".$numChanges."#".($id+1);
	}
	else{
		$pageURI = "http://penelope2.oc/pdf/word-zap?try=".md5($sql)."&page=0&numChanges=".$numChanges;
		if($lastId != false){
			$pageURI .= "#".$lastId;
		}
	}
	
	header('Location: '.$pageURI);
   }
   
   function copyWordAction(){
	$this->_helper->viewRenderer->setNoRender();
	$db = Zend_Registry::get('db');
	$page = $_REQUEST["page"];
	$id = $_REQUEST["id"];
	
	$sql = "SELECT * FROM book_index_2 WHERE id = $id LIMIT 1; ";
	//echo "<br/>".$sql;
	$result = $db->fetchAll($sql, 2);
	$badWord = $result[0]["word"];
	
	$sql = "SELECT * FROM book_index_2 WHERE word = '$badWord' ";
	$result = $db->fetchAll($sql, 2);
	$numChanges = 0;
	foreach($result as $row){
		$badWordId = $row['id'];
		$badwordPage = $row['page'];
		$padWordPageCount = $row['pageCount'];
		//unset($row['id']);
		$data = $row;
		$db->insert("book_index", $data);
		
		$where = array();
		$where[] = "id = $badWordId ";
		$db->delete("book_index_2", $where);
		$numChanges = $numChanges + 1;
	}
	
	if($page !=0){
		$pageURI = "http://penelope2.oc/pdf/review?try=".md5($sql)."&page=".$page."&numChanges=".$numChanges."#".($id+1);
	}
	else{
		$pageURI = "http://penelope2.oc/pdf/word-zap?try=".md5($sql)."&page=0&numChanges=".$numChanges;
	}
	
	header('Location: '.$pageURI);
   }
   
   
   function voteWordAction(){
	$this->_helper->viewRenderer->setNoRender();
	$db = Zend_Registry::get('db');
	$page = $_REQUEST["page"];
	$id = $_REQUEST["id"];
	
	$sql = "SELECT * FROM book_index WHERE id = $id LIMIT 1; ";
	//echo "<br/>".$sql;
	$result = $db->fetchAll($sql, 2);
	$goodWord = addslashes($result[0]["word"]);
	
	$sql = "SELECT * FROM book_index WHERE word = '$goodWord' ";
	$result = $db->fetchAll($sql, 2);
	$numChanges = 0;
	foreach($result as $row){
		$goodWordId = $row['id'];
		$keycount = $row['keywordvotes'];
		$keycount++;
		$where = array();
		$where[] = "id = $goodWordId ";
		$data = array();
		$data['keywordvotes'] = $keycount;
		$db->update("book_index", $data, $where);
	}
	
	if($page !=0){
		$pageURI = "http://penelope2.oc/pdf/review?try=".md5($sql)."&page=".$page."&numChanges=".$numChanges."#".($id);
	}
	else{
		$pageURI = "http://penelope2.oc/pdf/word-zap?try=".md5($sql)."&page=0&numChanges=".$numChanges;
	}
	
	header('Location: '.$pageURI);
   }
   
   
   
   function reviewDoAction(){
	$this->_helper->viewRenderer->setNoRender();
	$db = Zend_Registry::get('db');
	$page = $_REQUEST["page"];
	$requestParams = $this->_request->getParams();
	//echo print_r($requestParams);
	if(isset($requestParams["id"])){
		$idArray = $requestParams["id"];
		$firstID = $idArray[0];
		$secondID = $idArray[1];
	}
	else{
		$firstID = $_REQUEST["idA"];
		$secondID = $_REQUEST["idB"];
	}
	
	$sql = "SELECT * FROM book_index WHERE id = $firstID LIMIT 1; ";
	//echo "<br/>".$sql;
	$result = $db->fetchAll($sql, 2);
	$firstWord = $result[0]["word"];
	$firstWordCount = $result[0]["pageCount"];
	
	$sql = "SELECT * FROM book_index WHERE id = $secondID LIMIT 1; ";
	//echo "<br/>".$sql;
	$result = $db->fetchAll($sql, 2);
	$secondWord = $result[0]["word"];
	$secondWordCount = $result[0]["pageCount"];
	
	$numChanges = 0;
	
	$data = array("word" => $firstWord.$secondWord,
		      "pageCount" => $firstWordCount + $secondWordCount
		);
	$where = array();
	$where[] = "id = $firstID ";
	$db->update("book_index", $data, $where);
	unset($where);
	$where = array();
	$where[] = "id = $secondID ";
	$db->delete("book_index", $where);
	$numChanges = $numChanges + 1;
	
	unset($data);
	$data = array("word_a" => $firstWord, "word_b" => $secondWord);
	$db->insert("book_index_edits", $data);
	unset($data);

	//now fix others just like this
	$sql = "SELECT * FROM book_index WHERE word = '$firstWord' ";
	$result = $db->fetchAll($sql, 2);
	
	foreach($result as $row){
		$firstWordId = $row['id'];
		$firstWordCount = $row['pageCount'];
		$testSecondId = $firstWordId + 1;
		$sql = "SELECT * FROM book_index WHERE id =  $testSecondId LIMIT 1";
		$resultB = $db->fetchAll($sql, 2);
		if($resultB[0]["word"] == $secondWord){
			$secondWordCount = $resultB[0]["pageCount"];
			$data = array("word" => $firstWord.$secondWord,
				      "pageCount" => $firstWordCount + $secondWordCount
				      );
			$where = array();
			$where[] = "id = $firstWordId ";
			$db->update("book_index", $data, $where);
			unset($where);
			$where = array();
			$where[] = "id = $testSecondId ";
			$db->delete("book_index", $where);
			$numChanges = $numChanges + 1;
		}
		
	}
	$pageURI = "http://penelope2.oc/pdf/review?try=".md5($firstWord.$secondWord)."&page=".$page."&numChanges=".$numChanges."#".($firstID);
	header('Location: '.$pageURI);
   }
   
   //saved changes are stored in the book-index-edits table. this redoes the saved edits for the whole book.
   function savedChangesAction(){
	$this->_helper->viewRenderer->setNoRender();
	$db = Zend_Registry::get('db');
	
	$numChanges = 0;
	$sql = "SELECT * FROM book_index_edits ";
	
	$resultA = $db->fetchAll($sql, 2);
	foreach($resultA as $rowA){
		$firstWord = $rowA["word_a"];
		$secondWord = $rowA["word_b"];
		$sql = "SELECT * FROM book_index WHERE word = '$firstWord' ";
		$resultB = $db->fetchAll($sql, 2);
		if($resultB){
			$loopCount = 0;
			foreach($resultB as $rowB){
				$firstWordId = $rowB['id'];
				$firstWordPage = $rowB['page'];
				$firstWordCount = $rowB["pageCount"];
				
				echo "<br/>WordA: ".$firstWord." <strong>".$numChanges."<strong>";
				if($loopCount == 0){
					$loopCount++;
					$sql = "SELECT * FROM book_index WHERE word = '$secondWord' AND page = $firstWordPage LIMIT 1;";
					echo "<br/>".$sql;
					$resultC = $db->fetchAll($sql, 2);
					if($resultC){
						
						$secondWordID = $resultC[0]['id'];
						$secondWordCount = $resultC[0]["pageCount"];
						$data = array("word" => $firstWord.$secondWord,
							      "pageCount" => $firstWordCount + $secondWordCount
							      );
						$where = array();
						$where[] = "id = $firstWordId ";
						$db->update("book_index", $data, $where);
						unset($where);
						$where = array();
						$where[] = "id = $secondWordID ";
						$db->delete("book_index", $where);
						$numChanges = $numChanges + 1;
					}
				}//end case for looking on the same page
				else{
					$loopCount++;
					$checkSecondID = $firstWordId + 1;
					$sql = "SELECT * FROM book_index WHERE word = '$secondWord' AND id = $checkSecondID LIMIT 1;";
					echo "<br/><em>".$sql."</em>";
					$resultC = $db->fetchAll($sql, 2);
					if($resultC){
						$secondWordID = $resultC[0]['id'];
						$secondWordCount = $resultC[0]["pageCount"];
						$data = array("word" => $firstWord.$secondWord,
							      "pageCount" => $firstWordCount + $secondWordCount
							      );
						$where = array();
						$where[] = "id = $firstWordId ";
						$db->update("book_index", $data, $where);
						unset($where);
						$where = array();
						$where[] = "id = $secondWordID ";
						$db->delete("book_index", $where);
						$numChanges = $numChanges + 1;
					}
					
					
				}
			}
		}
	}//end loop resultA
	
	$sql = "SELECT * FROM book_index WHERE word = '$firstWord' ";
	$result = $db->fetchAll($sql, 2);
	
	foreach($result as $row){
		$firstWordId = $row['id'];
		$firstWordCount = $row['pageCount'];
		$testSecondId = $firstWordId + 1;
		$sql = "SELECT * FROM book_index WHERE id =  $testSecondId LIMIT 1";
		$resultB = $db->fetchAll($sql, 2);
		if($resultB[0]["word"] == $secondWord){
			$secondWordCount = $resultB[0]["pageCount"];
			$data = array("word" => $firstWord.$secondWord,
				      "pageCount" => $firstWordCount + $secondWordCount
				      );
			$where = array();
			$where[] = "id = $firstWordId ";
			$db->update("book_index", $data, $where);
			unset($where);
			$where = array();
			$where[] = "id = $testSecondId ";
			$db->delete("book_index", $where);
			$numChanges = $numChanges + 1;
		}
		
	}
	$pageURI = "http://penelope2.oc/pdf/review?page=1&numChanges=".$numChanges;
	//header('Location: '.$pageURI);
   }
   
   
   function wordZapAction(){
	
	
	$this->_helper->viewRenderer->setNoRender();
	$db = Zend_Registry::get('db');
	
	if(isset($_REQUEST["numChanges"])){
		$numChanges = $_REQUEST["numChanges"];
	}
	else{
		$numChanges = "[none]";
	}
	
	/*
	$sql = "SELECT book_index.id, book_index.word, SUM(book_index.pageCount) as totalCount
	FROM book_index
	LEFT JOIN book_index_terms ON book_index.word = book_index_terms.original_word
	WHERE book_index.keywordvotes >0 AND book_index_terms.fixed_word IS NULL
	GROUP BY book_index.word
	ORDER BY book_index.word
	";
	
	$sql = "SELECT *
	FROM book_index_terms
	WHERE book_index_terms.updated = 1
	ORDER BY book_index_terms.original_word
	";
	*/
	
	$sql = "SELECT *
	FROM book_index_terms 
	WHERE updated = 0
	";
	
	
	$result = $db->fetchAll($sql, 2);
	echo "Changes: ".$numChanges;
	
	echo "<table>".chr(13);
	echo "<th><tr><td>ID</td><td>Original Word</td><td>Fixed Word</td><td style='width:250px; text-align:right;'>DELETE</td></tr></th>".chr(13);
	$i=0;
	$lastId = 0;
	//$result = array();
	foreach($result as $row){
		
		
		echo "<tr id='".$row["id"]."'>";
		echo "<td>".$row["id"]."</td>";
		echo "<td>".$row["original_word"]."</td>";
		echo "<td style='width:250px; text-align:center;'><form method='post' action='fix-word'>";
		echo "<input type='hidden' name='id' value='".$row["id"]."' />";
		echo "<input type='text' name='fixedWord' value='".$row["fixed_word"]."' />";
		echo ' <input type="submit" value="Submit" />';
		echo "</form></td>";
		
		echo "<td style='width:250px; text-align:right;'><a href='delete-term?lastID=".$lastId."&id=".$row["id"]."'>Delete</a></td>";
		
		
		echo "</tr>".chr(13);
		
		$lastId = $row["id"];
		if($i>=200){
			
		}
		
		
		
		/*
		$data = array("original_id" => $row["id"],
			      "original_word" => $row["word"],
			      "fixed_word" => $row["word"],
			      "updated" => 0
			      );
		
		$db->insert("book_index_terms", $data);
		*/
		
	$i++;
	}
	echo "</table>".chr(13);

   }
   
   
   //this changes a word to normalize it, make it fixed
   function fixWordAction(){
	$this->_helper->viewRenderer->setNoRender();
	$db = Zend_Registry::get('db');
	
	$id = $_REQUEST['id'];
	$fixedWord = $_REQUEST['fixedWord'];
	//echo $id." ".$fixedWord;
	
	$where = array();
	$where[] = "id = ".$id ;
	
	$data = array("fixed_word" => $fixedWord,
		      "updated" => 1
		      );
	
	$db->update("book_index_terms", $data, $where);
	
	$pageURI = "http://penelope2.oc/pdf/word-zap";
	header('Location: '.$pageURI);
   }
   
   
   
   //this changes a word to normalize it, make it fixed
   function deleteTermAction(){
	$this->_helper->viewRenderer->setNoRender();
	$db = Zend_Registry::get('db');
	
	$id = $_REQUEST['id'];
	$lastID = $_REQUEST['lastID'];
	
	//echo $id." ".$fixedWord;
	
	$where = array();
	$where[] = "id = ".$id ;
	
	$db->delete("book_index_terms", $where);
	
	$pageURI = "http://penelope2.oc/pdf/word-zap#".$lastID;
	header('Location: '.$pageURI);
   }
   
   
   /*
   
   SELECT `word`, SUM(`pageCount`) 
FROM `book_index` 
WHERE 1
GROUP BY `word`
ORDER BY SUM(`pageCount`) DESC


SELECT id, word, SUM(pageCount) as totalCount
FROM book_index 
WHERE 1
GROUP BY word
ORDER BY word

   
   
   */
   
   
   
   function makeIndexAction(){
	$this->_helper->viewRenderer->setNoRender();
	$db = Zend_Registry::get('db');
	
	$sql = "SELECT DISTINCT fixed_word
	FROM book_index_terms
	ORDER BY fixed_word
	";
	
	$result = $db->fetchAll($sql, 2);
	foreach($result as $row){
		$indexTerm = $row["fixed_word"];
		echo "<p>".$indexTerm." ";
		$indexTerm = addslashes($indexTerm);
		
		$sql = "SELECT book_index.page
		FROM book_index
		JOIN book_index_terms ON book_index_terms.original_word = book_index.word
		WHERE book_index_terms.fixed_word = '$indexTerm'
		GROUP BY book_index.page
		ORDER BY book_index.page
		";
		
		$resultB = $db->fetchAll($sql, 2);
		//echo "<ul style='list-style-type: none;'>";
		
		echo "<br/><em>";
		$startedContinueRange = false;
		$i = 0;
		$numPages = count($resultB);
		while($i < $numPages){
			
			$currentPage = $resultB[$i]["page"];
			
			if(($i + 1) < $numPages){
				$nextPage = $resultB[$i+1]["page"];
			}
			else{
				$nextPage = $currentPage;
			}
			
			if(!$startedContinueRange){
				if($i != 0){
					$output = ", ".$currentPage;
				}
				else{
					$output = $currentPage;
				}
				if($nextPage == $currentPage + 1){
					$startedContinueRange = true;
				}
			}
			else{
				if($nextPage == $currentPage + 1){
					$output = "";
				}
				else{
					$output = "&#8211;".$currentPage;
					$startedContinueRange = false;
				}
			}
			
		
			echo $output;
			//echo " ($currentPage: $startedContinueRange: $nextPage)";
		$i++;
		}
		echo "</em>";
		//echo "</ul>";
	}
	
	echo "</p>";
	
   }
   
   
    

}