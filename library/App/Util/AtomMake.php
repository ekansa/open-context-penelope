<?php
class AtomMake
{
    
    //URIs for XML Schema referenced in Open Context
        const gmlURI = 'http://www.opengis.net/gml';//URI to GML namespace
        const dcURI = 'http://purl.org/dc/elements/1.1/';//URI to Dublin Core namespace
        const atomURI = "http://www.w3.org/2005/Atom"; // namespace uri for Atom
        const xhtmlURI = "http://www.w3.org/1999/xhtml";
        const georssURI = "http://www.georss.org/georss";
        const kmlURI = "http://www.opengis.net/kml/2.2";
        
        const archSpaceURI = 'http://ochre.lib.uchicago.edu/schema/SpatialUnit/SpatialUnit.xsd';//URI to ArchaeoML spatial Schema
        const archProjURI ='http://ochre.lib.uchicago.edu/schema/Project/Project.xsd';//URI to ArchaeoML project schema
        const archPersURI = "http://ochre.lib.uchicago.edu/schema/Person/Person.xsd"; //URI to the ArchaeoML person Schema
        const archPropURI = "http://ochre.lib.uchicago.edu/schema/Project/Variable.xsd"; //URI to the ArchaeoML person Schema
        const archMediaURI = "http://ochre.lib.uchicago.edu/schema/Resource/Resource.xsd";
        
        const ocSpaceURI = "http://opencontext.org/schema/space_schema_v1.xsd"; //URI to open context space schema
        const ocProjURI = 'http://opencontext.org/schema/project_schema_v1.xsd';//URI to open context project schema
        const ocPersURI = "http://opencontext.org/schema/person_schema_v1.xsd"; //URI to open context person Schema
        const ocPropURI = "http://opencontext.org/schema/property_schema_v1.xsd"; //URI to open context person Schema
        const ocMediaURI = "http://opencontext.org/schema/resource_schema_v1.xsd";
        
        const db_ocSpaceURI = "http://opencontext.org/schema/space_schema_v1.xsd"; //URI to open context space schema
    
    
    
    
    
    public static function spatialAtomCreate($archaeML_string){
		
		$baseURI = "http://opencontext.org";
		$baseURI .= "/subjects/";
		
		//$archaeML_string = str_replace(self::db_ocSpaceURI, self::ocSpaceURI, $archaeML_string);
		
		$spatialItem = simplexml_load_string($archaeML_string);
	
		// Register OpenContext's namespace
		$spatialItem->registerXPathNamespace("oc", self::ocSpaceURI);
		
		// Register OpenContext's namespace
		$spatialItem->registerXPathNamespace("arch", self::archSpaceURI);
	
		// Register Dublin Core's namespace
		$spatialItem->registerXPathNamespace("dc", self::dcURI);
	
		// Register the GML namespace
		$spatialItem->registerXPathNamespace("gml", self::gmlURI);
		
		
		// get the item_label
		foreach($spatialItem->xpath("//arch:spatialUnit/@UUID") as $item_result) {
			$uuid = $item_result."";
		}
	
	
		// get the item_label
		foreach ($spatialItem->xpath("//arch:spatialUnit/arch:name/arch:string") as $item_label) {
			$item_label = $item_label."";
			//$item_label = "blank";
		}
		
		//project name
		foreach ($spatialItem->xpath("//arch:spatialUnit/oc:metadata/oc:project_name") as $project_name) {
			$project_name = $project_name."";
		}
	
		// get the item class
		foreach ($spatialItem->xpath("//arch:spatialUnit/oc:item_class/oc:name") as $item_class) {
			$item_class = $item_class."";
		}
	
		if (!$spatialItem->xpath("//arch:spatialUnit/oc:context/oc:tree")) {
			$default_context_path = "ROOT";  // note: variable $default_context_path used later in abreviated Atom feed
		}	
	
		// For non-root-level items:
		// Get the default context path (there should only be one.)
		// Also index the hierarchy levels - def_context_*
		$j = 0; //used to generate 'def_context_*' fields in solr
		$default_context_path = "";
		if ($spatialItem->xpath("//arch:spatialUnit/oc:context/oc:tree[@id='default']")) {
			foreach ($spatialItem->xpath("//arch:spatialUnit/oc:context/oc:tree[@id='default']/oc:parent/oc:name") as $path) {
				$default_context_path .= $path . "/";
		
			}
		}
	
		
		
		$user_tags = array();
		$count_public_tags = 0; // value used to help calculate interest_score	
		if ($spatialItem->xpath("//arch:spatialUnit/oc:social_usage/oc:user_tags/oc:tag")) {
			foreach ($spatialItem->xpath("//arch:spatialUnit/oc:social_usage/oc:user_tags/oc:tag[@type='text' and @status='public']") as $tag) { // loop through and get the tags. 
				foreach ($tag->xpath("oc:name") as $user_tag) {
					$user_tags[] .= $user_tag; // array of tags to be used later for Atom feed entry
				}
				foreach ($tag->xpath("oc:tag_creator/oc:creator_name") as $tag_creator_name) {
					$tag_creator_name = $tag_creator_name."";
				}
				foreach ($tag->xpath("oc:tag_creator/oc:set_label") as $tag_set_label) {
					$tag_set_label = $tag_set_label."";
				}
			$count_public_tags++; // used to help calculate interest_score	
			}
		}
	
	
		$creators = $spatialItem->xpath("//arch:spatialUnit/oc:metadata/dc:creator");
		
		foreach ($spatialItem->xpath("//arch:spatialUnit/oc:metadata/oc:geo_reference") as $geo_reference) {
			foreach ($geo_reference->xpath("oc:geo_lat") as $geo_lat) {
				$geo_lat = (string)$geo_lat;
			}
			foreach ($geo_reference->xpath("oc:geo_long") as $geo_long) {
				$geo_long = (string)$geo_long;
			}
		}//end loop through geo
	
		// polygon
		$geo_polygon = false;
		if ($spatialItem->xpath("//arch:spatialUnit/oc:metadata/oc:geo_reference/oc:metasource[@ref_type='self']")) {
			$self_geo_reference = true; // this value is used to calculate interesting_score.
			
			if ($spatialItem->xpath("//arch:spatialUnit/oc:metadata/oc:geo_reference/gml:Polygon/gml:exterior/gml:LinearRing/gml:posList")) {
				$geo_polygon = true; // this value is used to calculate interesting_score. and also in the Atom generation code
				foreach ($spatialItem->xpath("//arch:spatialUnit/oc:metadata/oc:geo_reference/gml:Polygon/gml:exterior/gml:LinearRing/gml:posList") as $polygon_pos_list ) {
					//echo "polygon_pos_list: " . $polygon_pos_list;
					//echo "<br/>";
				}
			}
		}
	
	
	
	
		// Atom 
		// opening entry element
		$atom_entry = "<?xml version='1.0' encoding='utf-8'?><entry xmlns='".self::atomURI."'
		xmlns:georss='".self::georssURI."'
		xmlns:kml='".self::kmlURI."'
		xmlns:gml='".self::gmlURI."'>";
	
		// title of the Atom entry
		$atom_title = "<title>" . $project_name . ": " . $item_label . " (" . $item_class . ")" . "</title>";
		// append title to entry
		$atom_entry .= $atom_title;
	
		// uri of Open Context content
		$atom_entry .= "<link href='" . $baseURI . $uuid . "'" . "/>";
	
		// id of entry
		$atom_entry .= "<id>" . $baseURI . $uuid . "</id>";
	
		// required updated element - the code below uses the current timestamp.Q: is there are reliable way to get info about when the item was last updated? 
		$atom_entry .= "<updated>" . date("Y-m-d\TH:i:s\-07:00") . "</updated>";
	
		// append one or more author elements to the entry
		foreach ($creators as $creator) {
			$atom_entry .= "<author><name>" . $creator . "</name></author>";
		}
	
		// append one or more contributor elements to the entry.
		$contributors = $spatialItem->xpath("/arch:spatialUnit/oc:metadata/dc:contributor");
		foreach ($contributors as $contributor) {
			$atom_entry .= "<contributor><name>" . $contributor . "</name></contributor>";
		}
	
		// append a category element
		$atom_entry .= "<category term='" . $item_class . "'/>";
	
		// Append a GeoRSS point
		$atom_entry .= "<georss:point>" . $geo_lat . " " . $geo_long . "</georss:point>";
	
		// if there's a GML polygon, add it to the entry
		//only grab the polygon if the metasource ref_type=self
		foreach ($spatialItem->xpath("//arch:spatialUnit/oc:metatdata/oc:geo_reference/gml:Polygon/gml:exterior/gml:LinearRing/gml:posList") as $posList) {
			$atom_entry .= "<georss:where><gml:Polygon><gml:exterior><gml:LinearRing><gml:posList>" . $posList . "</gml:posList></gml:LinearRing></gml:exterior></gml:Polygon></georss:where>";
		}
	
		// open atom content element and the initial div 
		
		
		$atom_content = "<content type='xhtml'><xhtml:div xmlns:xhtml='".self::xhtmlURI."'>"; 
		
		
	
	
	
		// open the item_lft_cont div  
		$atom_content .= "<xhtml:div class='item_lft_cont'>";
	
		// append the class icon (uri)
		foreach ($spatialItem->xpath("//arch:spatialUnit/oc:item_class/oc:iconURI") as $iconURI) {
			$class_icon_div = "<xhtml:div class='class_icon'><xhtml:img src='" . $iconURI . "' alt='" . $item_class . "'/></xhtml:div>";
			$atom_content .= $class_icon_div;
		}
	
		// append the project name to the atom content element
		$project_name_div = "<xhtml:div class='project_name'>" . $project_name . "</xhtml:div>";
		$atom_content .= $project_name_div;
	
		// open the item_mid_cont div 
		$atom_content .= "<xhtml:div class='item_mid_cont'>";
	
		// open the item_mid_up_cont div
		$atom_content .= "<xhtml:div class='item_mid_up_cont'>";
	
		// class name
		$class_name_div = "<xhtml:div class='class_name'>" . $item_class . "</xhtml:div>";
		$atom_content .= $class_name_div;
	
	
		// item label; note: removed htmlentities because it causes problems with numeric entities such as &#xC7;atalh&#xF6;y&#xFC; from '2_Global_Space'
		//$item_label_div = "<xhtml:div class='item_label'>" . $item_label . "</xhtml:div>";
		$item_label_div = "<xhtml:div class='item_label'>" . str_replace("& ", "&amp; ", $item_label) . "</xhtml:div>"; 
												    
		$atom_content .= $item_label_div;
	
		// close the item_mid_up_con div
		$atom_content .= "</xhtml:div>";
	
		// open the context div
		$atom_content .= "<xhtml:div class='context'>Context: ";
	
		$escapePath = htmlspecialchars($default_context_path."" );
		// context (path) // note: XML escaping this value prevents XML parsing errors
		$display_context_path = "";
		
		if(strlen($escapePath)>0){
			//$display_context_path = substr($escapePath, 0, -1);
			$display_context_path = mb_substr($escapePath, 0, -1);
		}
		$display_context_path = str_replace("/", "</xhtml:span> / <xhtml:span class='item_parent'>", $display_context_path);
		$display_context_path = "<xhtml:span class='item_root_parent'>" . $display_context_path . "</xhtml:span>";
		$atom_content .= $display_context_path;
	
		// close the context div
		$atom_content .= "</xhtml:div>";
	
		// close the item_mid_cont div
		$atom_content .= "</xhtml:div>";
	
		// user generated tags
		if ($user_tags) {
			$all_user_tags_div = "<xhtml:div class='all_user_tags'>User Created Tags: ";
			foreach ($user_tags as $user_tag) {
				$user_tag_div = "<xhtml:span class='user_tag'>" . $user_tag . "</xhtml:span>, ";
				$all_user_tags_div .= $user_tag_div;
			}
			$user_tags = array (); // re-initalize the $user_tags array in preparation for the next spatial item
			$all_user_tags_div = substr($all_user_tags_div, "", -2);
			// close all_user_tags_div
			$all_user_tags_div .= "</xhtml:div>";
			$atom_content .= $all_user_tags_div;
		}
	
		// close the item_lft_cont div 
		$atom_content .= "</xhtml:div>";
	
		// thumbnail (uri) (note: since there may be more than one thumbnail image, we use the first one in the array)
		// if the item has one or more thumbnail images
		if ($spatialItem->xpath("//arch:spatialUnit/arch:observations/arch:observation/arch:links/oc:media_links/oc:link/oc:thumbnailURI")) {
			// store the image URIs in an array
			$thumbnailURIs = $spatialItem->xpath("//arch:spatialUnit/arch:observations/arch:observation/arch:links/oc:media_links/oc:link/oc:thumbnailURI");
			// display just the first image in the array.
			$item_thumb_div = "<xhtml:div class='item_thumb'><xhtml:a href='" .$baseURI. $uuid . "'><xhtml:img src='" . $thumbnailURIs[0] . "' class='thumbimage' alt='Thumbmail image'/></xhtml:a></xhtml:div>";
			$atom_content .= $item_thumb_div;
		} else {
			$item_thumb_div = "<xhtml:div class='item_thumb'></xhtml:div>";
			$atom_content .= $item_thumb_div;
		}
	
		// close the atom content element
		$atom_content .= "</xhtml:div></content>";
		//echo "atom_content: " . $atom_content;
		
		$atom_content_with_prefixes = $atom_content;
		
		// remove the xhtml: prefix for the abbreviated Atom feed. 
		$atom_content = str_replace('xhtml:', '', $atom_content);
		$atom_content = str_replace('xmlns:xhtml', 'xmlns', $atom_content);
		
		// append the content element to the atom entry
		$atom_entry .= $atom_content;
	
		// close the atom entry
		$atom_entry .= "</entry>";
	
		
		
		$atomFullDoc = new DOMDocument("1.0", "utf-8");
	
		$root = $atomFullDoc->createElementNS("http://www.w3.org/2005/Atom", "feed");
		
		// add newlines and indent the output - this is at least useful for debugging and making the output easier to read
		$atomFullDoc->formatOutput = true;
		
		$root->setAttribute("xmlns:georss", self::georssURI);
		$root->setAttribute("xmlns:gml", self::gmlURI);
		$root->setAttribute("xmlns:arch", self::archSpaceURI);
		$root->setAttribute("xmlns:oc", self::ocSpaceURI);
		$root->setAttribute("xmlns:dc", self::dcURI);
		$root->setAttribute("xmlns:xhtml", self::xhtmlURI);
		
		$atomFullDoc->appendChild($root);
		
		// Create feed title (as opposed to an entry title)
		$feedTitle = $atomFullDoc->createElement("title");
		$feedTitleText = $atomFullDoc->CreateTextNode( $project_name . ": " . $item_label . " (" . $item_class . ")" );	
		$feedTitle->appendChild($feedTitleText);
		$root->appendChild($feedTitle);
	
		//echo $atomFullDoc->saveXML();
		
		// Create feed updated element (as opposed to the entry updated element)
		$feedUpdated = $atomFullDoc->createElement("updated");
	
		// Retrieve the current date and time. Format it in RFC 3339 format. Store it in a text node 
		$feedUpdatedText = $atomFullDoc->CreateTextNode(date("Y-m-d\TH:i:s\-07:00"));
	
		// Append the text node the updated element
		$feedUpdated->appendChild($feedUpdatedText);
	
		// Append the updated node to the root element
		$root->appendChild($feedUpdated);
	
		// feed (self) link element
		$entryLink = $atomFullDoc->createElement("link");
		$entryLink->setAttribute("rel", "self");
		$entryLink->setAttribute("href", $baseURI . $uuid . ".atom");
		$root->appendChild($entryLink);
		
		// feed link element
		$entryLink = $atomFullDoc->createElement("link");
		$entryLink->setAttribute("rel", "alternate");
		$entryLink->setAttribute("href", $baseURI . $uuid);
		$root->appendChild($entryLink);
		
		// feed id
		$feedId = $atomFullDoc->createElement("id");
		$feedIdText = $atomFullDoc->createTextNode($baseURI . $uuid);
		$feedId->appendChild($feedIdText);
		$root->appendChild($feedId);
		
		// Entry
		$entry = $atomFullDoc->createElement("entry");
		
		// Create entry title (as opposed to an feed title)
		$entryTitle = $atomFullDoc->createElement("title");
		$entryTitleText = $atomFullDoc->CreateTextNode( $project_name . ": " . $item_label . " (" . $item_class . ")" );	
		$entryTitle->appendChild($entryTitleText);
		$entry->appendChild($entryTitle);
		
		// entry link element
		$entryLink = $atomFullDoc->createElement("link");
		$entryLink->setAttribute("rel", "alternate");
		$entryLink->setAttribute("href", $baseURI . $uuid);
		$entry->appendChild($entryLink);
		
		// entry id
		$entryId = $atomFullDoc->createElement("id");
		$entryIdText = $atomFullDoc->createTextNode($baseURI . $uuid);
		$entryId->appendChild($entryIdText);
		$entry->appendChild($entryId);
		
		// Create entry updated element (as opposed to the feed updated element)
		$entryUpdated = $atomFullDoc->createElement("updated");
		$entryUpdatedText = $atomFullDoc->CreateTextNode(date("Y-m-d\TH:i:s\-07:00"));
		$entryUpdated->appendChild($entryUpdatedText);
		$entry->appendChild($entryUpdated);
		
		// category
		$entryCategory = $atomFullDoc->createElement("category");
		$entryCategory->setAttribute("term", $item_class);
		$entry->appendChild($entryCategory);
		
		$georssPoint = $atomFullDoc->createElement("georss:point");
		$georssPointText = $atomFullDoc->CreateTextNode($geo_lat . " " . $geo_long);
		$georssPoint->appendChild($georssPointText);
		$entry->appendChild($georssPoint);
		
		// if there's a polygon, create the appropriate nodes
		if ($geo_polygon) {
			
			$gmlPosList = $atomFullDoc->createElement("gml:posList");
			$gmlPosListText = $atomFullDoc->CreateTextNode($polygon_pos_list);  // get the pos list from the abbreviated atom feed 
			$gmlPosList->appendChild($gmlPosListText);
			
			$gmlLinearRing = $atomFullDoc->createElement("gml:LinearRing");
			$gmlLinearRing->appendChild($gmlPosList);
			
			
			$gmlExterior = $atomFullDoc->createElement("gml:exterior");
			$gmlExterior->appendChild($gmlLinearRing);
			
			$gmlPolygon = $atomFullDoc->createElement("gml:Polygon");
			$gmlPolygon->appendChild($gmlExterior);
			
			$georssWhere = $atomFullDoc->createElement("georss:where");
			$georssWhere->appendChild($gmlPolygon);
			
			$entry->appendChild($georssWhere);
			
		}
		
		
		
			
			
		foreach ($creators as $creator) {
			// Create the feed's author element. (Note: This is required by Atom unless there's an author element in every entry.) 
			$author = $atomFullDoc->createElement("author");
			// Create name element as child of author
			$name = $atomFullDoc->createElement("name");
			// Set auther for feed (as opposed to entry)
			$nameText = $atomFullDoc->createTextNode($creator);
			// Append the author name text as a child of name
			$name->appendChild($nameText);
			// Append the name element to the author element
			$author->appendChild($name);
			// Append the author to the entry node
			$entry->appendChild($author);
			// Append the entry node to the root node
			
		}
		
		// Contributors
		$contributors = $spatialItem->xpath("/spatialUnit/oc:metadata/dc:contributor");
		foreach ($contributors as $contrib)  {
			$contributor = $atomFullDoc->createElement("contributor");
			// Create name element as child of contributor
			$name = $atomFullDoc->createElement("name");
			// Set auther for feed (as opposed to entry)
			$nameText = $atomFullDoc->createTextNode($contrib);
			// Append the contributor name text as a child of name
			$name->appendChild($nameText);		
			// Append the name element to the author element
			$contributor->appendChild($name);
			// Append the author to the entry node
			$entry->appendChild($contributor);
		}
		
		// Create a document fragment to hold the XHTML content element
		$contentFragment = $atomFullDoc->createDocumentFragment();
		
		// add the XHTML content string
		$contentFragment->appendXML($atom_content_with_prefixes);  // $atom_content from short atom entry
		
		$entry->appendChild($contentFragment);
	
		// Create a document fragment to hold the full spatial XML
		$spatialUnitFragment = $atomFullDoc->createDocumentFragment();
		
		// remove the spatial item's prologue and store the spatial item as a string
		$spatialUnitXML = str_replace('<?xml version="1.0"?>', "", $spatialItem->asXML());
		$spatialUnitXML = str_replace('<?xml version="1.0" encoding="utf-8"?>', "", $spatialUnitXML);
		//echo htmlentities($spatialUnitXML);
		
		// add the spatial unit XML to the document fragment
		$spatialUnitFragment->appendXML($spatialUnitXML);
		
		// append the document fragment to the entry
		$entry->appendChild($spatialUnitFragment);
		
		$root->appendChild($entry);
		
		$atomFullDocString = $atomFullDoc->saveXML();
		//remove redundant namespace declaration
		$atomFullDocString = str_replace('<content xmlns:xhtml="http://www.w3.org/1999/xhtml" type="xhtml">', '<content type="xhtml">', $atomFullDocString);
			
		return $atomFullDocString;
		
	}//end make spatialAtomCreate function
    
    
    
    
    
    
    public static function resourceAtomCreate($resXML_string){
		
		$baseURI = "http://opencontext.org";
		$baseURI .= "/media/";
		$media_dom = new DOMDocument("1.0", "utf-8");
		$media_dom->loadXML($resXML_string);
		
		$xpath = new DOMXpath($media_dom);
			
		// Register OpenContext's namespace
		$xpath->registerNamespace("arch", self::archMediaURI);
		$xpath->registerNamespace("oc", self::ocMediaURI);
		$xpath->registerNamespace("dc", self::dcURI);
			
		$query = "/arch:resource/arch:name/arch:string";
		$result_title = $xpath->query($query, $media_dom);
			
		if($result_title != null){
		    $media_item_name = $result_title->item(0)->nodeValue;
		}
		
		$query = "/arch:resource/@UUID";
		$result_res = $xpath->query($query, $media_dom);
			
		if($result_res != null){
		    $media_id = $result_res->item(0)->nodeValue;
		}
		
		$query = "//oc:metadata/oc:project_name";
		$result_proj = $xpath->query($query, $media_dom);
			
		if($result_proj != null){
		    $project_name = $result_proj->item(0)->nodeValue;
		}
		
		$query = "//oc:metadata/oc:geo_reference/oc:geo_lat";
		$result_lat = $xpath->query($query, $media_dom);
			
		if($result_lat != null){
		    $geo_lat = $result_lat->item(0)->nodeValue;
		}
		
		$query = "//oc:metadata/oc:geo_reference/oc:geo_long";
		$result_lon = $xpath->query($query, $media_dom);
			
		if($result_lon != null){
		    $geo_lon = $result_lon->item(0)->nodeValue;
		}
		$geo_point = $geo_lat." ".$geo_lon;
		
		
		$query = "//oc:metadata/dc:creator";
		$result_create = $xpath->query($query, $media_dom);
		$author_array = array();	
			
		foreach($result_create AS $res_creators){
		    $author_array[] = $res_creators->nodeValue;
		}
		
		$query = "//oc:metadata/dc:contributor";
		$result_contrib = $xpath->query($query, $media_dom);	
		$contributor_array = array();
			
		foreach($result_contrib AS $act_contrib){
		    $contributor_array[] = $act_contrib->nodeValue;
		}
	
		$query = "arch:resource/@type";
		$result_type = $xpath->query($query, $media_dom);
			
		if($result_type != null){
		    $media_type = $result_type->item(0)->nodeValue;
		    $media_type = ucwords($media_type);
		}
	
		$query = "//oc:space_links/oc:link/oc:name";
		$result_lspace = $xpath->query($query, $media_dom);
			
		if($result_lspace != null){
		    $linked_space = $result_lspace->item(0)->nodeValue;
		}
	
		$query = "//oc:space_links/oc:link/oc:item_class/oc:name";
		$result_lspace_class = $xpath->query($query, $media_dom);
			
		if($result_lspace_class != null){
		    $l_sp_class = $result_lspace_class->item(0)->nodeValue;
		    $l_sp_class = ucwords($l_sp_class);
		}
		
		
		$query = "//arch:content/arch:externalFileInfo/arch:fileFormat";
		$result_mime = $xpath->query($query, $media_dom);
			
		if($result_mime != null){
		    $mime_type = $result_mime->item(0)->nodeValue;
		    $mime_type = trim($mime_type);
		}
		
		$query = "//arch:content/arch:externalFileInfo/arch:previewURI";
		$result_prev = $xpath->query($query, $media_dom);
			
		if($result_prev != null){
		    $previewURI = $result_prev->item(0)->nodeValue;
		}
		
		$query = "//arch:content/arch:externalFileInfo/arch:resourceURI";
		$result_full = $xpath->query($query, $media_dom);
			
		if($result_full != null){
		    $fullURI = $result_full->item(0)->nodeValue;
		}
		
		
		
		//done querying old xml version
		$media_entry_title = $project_name." media item: ".$media_item_name." (".$media_type."), linked with: ".$linked_space." (".$l_sp_class.")";
		$media_feed_title = "Open Context Media Resource: ".$media_item_name." (".$project_name.")";
		
		//echo "<br/>".$media_feed_title."<br/>".$media_entry_title."<br/>";
		
		
		$atomFullDoc = new DOMDocument("1.0", "utf-8");
		$root = $atomFullDoc->createElementNS(self::atomURI, "feed");
		
		// add newlines and indent the output - this is at least useful for debugging and making the output easier to read
		$atomFullDoc->formatOutput = true;
		
		$root->setAttribute("xmlns:georss", self::georssURI);
		$root->setAttribute("xmlns:gml", self::gmlURI);
	       
		$atomFullDoc->appendChild($root);
	
		// Feed Title 
		$feedTitle = $atomFullDoc->createElement("title");
		$feedTitleText = $atomFullDoc->createTextNode($media_feed_title);
		$feedTitle->appendChild($feedTitleText);
		$root->appendChild($feedTitle);
		
		// Feed updated element (as opposed to the entry updated element)
		$feedUpdated = $atomFullDoc->createElement("updated");
		// Retrieve the current date and time. Format it in RFC 3339 format. Store it in a text node 
		$feedUpdatedText = $atomFullDoc->createTextNode(date("Y-m-d\TH:i:s\-07:00"));   
		// Append the text node the updated element
		$feedUpdated->appendChild($feedUpdatedText);
		// Append the updated node to the root element
		$root->appendChild($feedUpdated);
		
		$linkURI = $baseURI . $media_id. ".atom";
		// feed (self) link element
		$feedLink = $atomFullDoc->createElement("link");
		$feedLink->setAttribute("rel", "self");
		$feedLink->setAttribute("href", $linkURI);
		$root->appendChild($feedLink);
		
		// feed id
		$feedId = $atomFullDoc->createElement("id");
		$feedIdText = $atomFullDoc->createTextNode($baseURI . $media_id);
		$feedId->appendChild($feedIdText);
		$root->appendChild($feedId);
		
		foreach($author_array AS $act_creator){
		    $author_el = $atomFullDoc->createElement("author");
		    $name_el = $atomFullDoc->createElement("name");
		    $name_text = $atomFullDoc->createTextNode($act_creator);
		    $name_el->appendChild($name_text);
		    $author_el->appendChild($name_el);
		    $root->appendChild($author_el);
		}
		
		
		$feed_entry = $atomFullDoc->createElement("entry");
		$root->appendChild($feed_entry);
		
		$point = $atomFullDoc->createElement("georss:point");
		$point_text = $atomFullDoc->createTextNode($geo_point);
		$point->appendChild($point_text);
		$feed_entry->appendChild($point);
		
		$entry_title_el = $atomFullDoc->createElement("title");
		$entry_title_text = $atomFullDoc->createTextNode($media_entry_title);
		$entry_title_el->appendChild($entry_title_text);
		$feed_entry->appendChild($entry_title_el);
		
		$entry_id_el = $atomFullDoc->createElement("id");
		$entry_id_text = $atomFullDoc->createTextNode($baseURI . $media_id);
		$entry_id_el->appendChild($entry_id_text);
		$feed_entry->appendChild($entry_id_el);
		
		// Feed updated element (as opposed to the entry updated element)
		$entryUpdated = $atomFullDoc->createElement("updated");
		// Retrieve the current date and time. Format it in RFC 3339 format. Store it in a text node 
		$entryUpdatedText = $atomFullDoc->createTextNode(date("Y-m-d\TH:i:s\-07:00"));   
		// Append the text node the updated element
		$entryUpdated->appendChild($entryUpdatedText);
		// Append the updated node to the root element
		$feed_entry->appendChild($entryUpdated);
		
		
		
		foreach($author_array AS $act_creator){
		    $author_el = $atomFullDoc->createElement("author");
		    $name_el = $atomFullDoc->createElement("name");
		    $name_text = $atomFullDoc->createTextNode($act_creator);
		    $name_el->appendChild($name_text);
		    $author_el->appendChild($name_el);
		    $feed_entry->appendChild($author_el);
		}
		
		foreach($contributor_array AS $act_contrib){
		    $author_el = $atomFullDoc->createElement("contributor");
		    $name_el = $atomFullDoc->createElement("name");
		    $name_text = $atomFullDoc->createTextNode($act_contrib);
		    $name_el->appendChild($name_text);
		    $author_el->appendChild($name_el);
		    $feed_entry->appendChild($author_el);
		}
		
		$enclosureLink = $atomFullDoc->createElement("link");
		$enclosureLink->setAttribute("rel", "enclosure");
		$enclosureLink->setAttribute("title", $media_item_name." - primary file");
		$enclosureLink->setAttribute("type", $mime_type);
		$enclosureLink->setAttribute("href", $fullURI);
		$feed_entry->appendChild($enclosureLink);
		
		$content_el = $atomFullDoc->createElement("content");
		$content_el->setAttribute("type", "xhtml");
		
		$content_div_text =
		'
		<div xmlns="'.self::xhtmlURI.'">
		<h1>Media Resource Preview</h1>
		<img src="'.$previewURI.'" title="Media preview"/>
		</div>
		';
		
		// add the XHTML content string
		$contentFragment = $atomFullDoc->createDocumentFragment();
		$contentFragment->appendXML($content_div_text);  // $atom_content from short atom entry
		$content_el->appendChild($contentFragment);
		$feed_entry->appendChild($content_el);
		
		//now add ArchaeoML String
		$media_archaeoML = str_replace('<?xml version="1.0"?>', "", $resXML_string);
		$media_archaeoML = str_replace('<?xml version="1.0" encoding="utf-8"?>', "", $media_archaeoML);
		$arch_contentFragment = $atomFullDoc->createDocumentFragment();
		$arch_contentFragment->appendXML($media_archaeoML);
		$feed_entry->appendChild($arch_contentFragment);
		
		$atom_xml_string = $atomFullDoc->saveXML();
		
		$atom_xml_string = str_replace("<default:", "<", $atom_xml_string);
		$atom_xml_string = str_replace("</default:", "</", $atom_xml_string);
		$atom_xml_string = str_replace('<content xmlns:default="http://www.w3.org/1999/xhtml"', "<content ", $atom_xml_string);
		
		return $atom_xml_string;
		
		
		
	}//end make resourceAtomCreate function
    
    
    
    
}
