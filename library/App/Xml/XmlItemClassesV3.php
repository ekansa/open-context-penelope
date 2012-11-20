<?php //Script examined by Eric

//Copyright (C) 2007, the Alexandria Archive Institute, Inc. (AAI)
//Version July 2007
//Authored by: Eric Kansa (on behalf of the AAI)
//Open Context is a trademark of the Alexandria Archive Institute
//
//The AAI releases this Open Context software under the Free Software Foundation (FSF) 
//GNU-General Public License (Version 3). 
//See full license text here: http://www.gnu.org/copyleft/gpl.html
//
//DISCLAIMER OF LIABILITY:  The authors of this software do not and cannot 
//exercise any control whatsoever over the content of the information 
//exchanged using this software.  The authors make no warranties of any 
//kind, whether expressed or implied, for the service this software is 
//providing or for the data exchanged with the assistance of this software. 
//The authors cannot be held responsible for any claims resulting from the 
//user's conduct and/or use of the software which is in any manner unlawful 
//or which damages such user or any other party.
//
//ADDITIONAL NOTE: This software is released in draft form, with little or no
//documentation. Many scripts and functions have yet to be cleaned up, much less optimized.
//Database schema will be released shortly. 
//
//SCRIPT NOTE: this a list of variables that users can summarize relevant to a selection 
//
//SCRIPT NOTE:
//functions for XML generation
//style sheets applied from project or user preferences

$do_indents = false;

$oc_media_directory = "http://www.opencontext.org/database/oc_media/";
$aai_media_directory = "http://www.alexandriaarchive.org/opencontext/";

//prefix for open context specific xml elements
$arch_prefix = "arch:";
$archaeoml_ns_uri = "http://ochre.lib.uchicago.edu/schema/SpatialUnit/SpatialUnit.xsd";

$oc_prefix = "oc:";
$oc_ns_uri = "http://about.opencontext.org/schema/space_schema_v1.xsd";

$gml_prefix = "gml:";
$gml_ns_uri = "http://www.opengis.net/gml";


//prefix for dublin core metadata xml elements
$dc_prefix = "dc:";
$dc_ns_uri = "http://purl.org/dc/elements/1.1/";

$style_home_dir = "stylesheets";



//URL bases for Open Context hrefs
$oc_spatial_uri = "http://opencontext.org/subjects/";
$oc_spatial_ext = ""; //".atom";
$oc_persons_uri = "http://opencontext.org/persons/";
$oc_persons_ext = ""; //".atom";
$oc_media_uri = "http://opencontext.org/media/";
$oc_media_ext = ""; //".atom";
$oc_diary_uri = "http://opencontext.org/documents/";
$oc_diary_ext = ""; //".atom";
$oc_prop_uri = "http://opencontext.org/properties/";
$oc_prop_ext = ""; //".atom";


$space_tab = "space"; //name of the space_table
$sp_classes_tab = "sp_classes"; //name of the space class table
$sp_contain_tab = "space_contain";//name of the space containment table
$links_tab = "links";//name of the links table
$person_tab = "persons";//name of the person table
$resource_tab = "resource";//name of the resource table
$diary_tab = "diary";//name of the diary table
$property_tab = "properties"; //name of the property table
$variable_tab = "var_tab"; //name of the variable table
$value_tab = "val_tab"; //name of the value table
$observe_tab = "observe"; //name of the observation table
$project_tab = "projects"; //name of the project table


//this changes the names of the tables used to ask for data in the Penelope database
function do_penelope(){
    
    global $space_tab;
    global $sp_classes_tab;
    global $sp_contain_tab;
    global $links_tab;
    global $person_tab;
    global $resource_tab;
    global $diary_tab;
    global $property_tab;
    global $variable_tab;
    global $value_tab;
    global $observe_tab;
    global $project_tab;
    
    $space_tab = "space"; //name of the space_table
    $sp_classes_tab = "sp_classes"; //name of the space class table
    $sp_contain_tab = "space_contain";//name of the space containment table
    $links_tab = "links";//name of the links table
    $person_tab = "persons";//name of the person table
    $resource_tab = "resource";//name of the resource table
    $diary_tab = "diary";//name of the diary table
    $property_tab = "properties"; //name of the property table
    $variable_tab = "var_tab"; //name of the variable table
    $value_tab = "val_tab"; //name of the value table
    $observe_tab = "observe"; //name of the observation table
    $project_tab = "project_list"; //name of t
}



//this is a total hack
//this replaces project table and field names to work with Penelope
function imp_project_query_fix($sql){
    global $do_importer;
    
    //if($do_importer)
    {

        $sql = str_replace("projects", "project_list", $sql);
        $sql = str_replace("project_id,", "project_id AS project_id,", $sql);
        $sql = str_replace("proj_name", "project_name AS proj_name", $sql);
        $sql = str_replace("project_list.noprop_mes", "'No properties' AS noprop_mes", $sql);
        $sql = str_replace("project_list.license_id", "'2_License' AS license_id", $sql);
        $sql = str_replace("project_list.thumb_root", "'' AS thumb_root", $sql);
        $sql = str_replace("project_list.accession", "CURDATE()", $sql);
        $sql = str_replace("ON space.project_id = project_list.project_id ", "ON space.project_id = project_list.project_id ", $sql);
        
    }
    
    return $sql;
    
}


//this is a total hack
//this replaces project table and field names to work with Penelope
function imp_prop_query_fix($sql){
    global $do_importer;
    
    if($do_importer){

        $sql = str_replace("LEFT JOIN variable_sort ON var_tab.variable_uuid = variable_sort.variable_uuid", " ", $sql);
        $sql = str_replace("ORDER BY variable_sort.sort_order", " ", $sql);
        $sql = str_replace("DATE_FORMAT(properties.val_date, '%Y-%m-%d') as xml_date", "'' as xml_date", $sql);
        $sql = str_replace("properties.val_date =0, properties.val_num, properties.val_date", "properties.val_num =0, properties.val_num, properties.val_num", $sql);
    }
    
    return $sql;
    
}


//this is a total hack
//this replaces project table and field names to work with Penelope
function imp_res_query_fix($sql){
    global $do_importer;
    
    if($do_importer){

        $sql = str_replace("resource.res_path", "resource.res_path_source AS res_path", $sql);
        $sql = str_replace("resource.res_type", "resource.res_archml_type AS res_type", $sql);
        $sql = str_replace("ORDER BY resource.res_archml_type AS res_type", "ORDER BY resource.res_archml_type", $sql);
    }
    
    return $sql;
    
}


//this is a total hack
//this replaces project table and field names to work with Penelope
function imp_pers_query_fix($sql){
    global $do_importer;
    
    if($do_importer){
        $sql = str_replace("persons.combined_name", "persons.combined_name AS combined_name", $sql);
    }
    
    return $sql;
    
}


function imp_pers__pers_query_fix($id, $item_type, $sql){
    global $do_importer;
    
    if(($do_importer)&&($item_type == "person")){
        $sql = "(SELECT persons.combined_name AS name,
        'Participant' AS label_2,
        persons.last_name,
        persons.first_name,
        project_list.project_id AS project_id,
        project_list.project_name AS proj_name,
        'No properties' AS noprop_mes, '2_License' AS license_id,
        '' AS thumb_root, DATE_FORMAT(CURDATE(), '%M %e, %Y') as pubdate,
        DATE_FORMAT(CURDATE(), '%Y-%m-%d') as c_pubdate
        FROM persons
        LEFT JOIN project_list ON persons.project_id = project_list.project_id
        WHERE persons.uuid = '$id' LIMIT 1) 
        UNION
        (SELECT users.combined_name AS name,
        'Participant' AS label_2,
        users.last_name,
        users.first_name,
        project_list.project_id AS project_id,
        project_list.project_name AS proj_name,
        'No properties' AS noprop_mes, '2_License' AS license_id,
        '' AS thumb_root, DATE_FORMAT(CURDATE(), '%M %e, %Y') as pubdate,
        DATE_FORMAT(CURDATE(), '%Y-%m-%d') as c_pubdate
        FROM users
        JOIN links ON users.uuid = links.targ_uuid
        LEFT JOIN project_list ON links.project_id = project_list.project_id
        WHERE users.uuid = '$id' LIMIT 1) 
        ";
    }
    
    return $sql;
    
}


//this is a total hack
//this replaces project table and field names to work with Penelope
function imp_cont_query_fix($sql){
    global $do_importer;
    
    if($do_importer){
        $sql = str_replace("space_contain.project_id", "space_contain.project_id AS project_id", $sql);
        $sql = str_replace("space_contain.tree_uuid", "'0' AS tree_uuid", $sql);
        $sql = str_replace("ORDER BY '0' AS tree_uuid,", "ORDER BY ", $sql);
    }
    
    return $sql;    
}

//this is a total hack
//this replaces project table and field names to work with Penelope
function imp_cont_ID_fix($check_ID){
    global $do_importer;
    
    if($do_importer){
        if($check_ID == "[ROOT]:A5DDBEA2-B3C8-43F9-8151-33343CBDC"){
            $check_ID = "D9AE02E5-C3F3-41D0-EB3A-39798F63GGGG";
        }
    }
    return $check_ID;
}

//this is a total hack
//this replaces project table and field names to work with Penelope
function imp_dcmeta_query_fix($sql){
    global $do_importer;
    
    if($do_importer){
        $sql = str_replace("project_id", "project_id", $sql);
    }
    
    return $sql;
}


//this is a total hack
//this adds 'users' to the person tab table if they are in the links table
//this functionality is added to insure that global authors etc. are in the person's table
//in the importer
function user_person_tab($sql, $obsterm, $id){
    global $do_importer;
    global $person_tab;
    global $links_tab;
    
    if($do_importer){
        $sql = str_replace("ORDER BY Order_kind;", "", $sql);
        $sql = "(".$sql.")";
        $sql .= "UNION
                (SELECT users.uuid, 
			users.combined_name AS combined_name, $links_tab.link_type, $links_tab.link_type as Order_kind
		FROM $links_tab
		INNER JOIN users 
			ON $links_tab.targ_uuid = users.uuid
		WHERE $links_tab.origin_uuid = '$id'
                        AND users.combined_name != ''
			$obsterm
		) ORDER BY Order_kind;
		";
    
    }
    
    return $sql;
    
}




function parseXMLcoding($string)
{
    if ( strlen($string) == 0 )
        return $string;
    
    
        // convert problematic characters to XML entities ('&' => '&amp;')
        $string = htmlentities($string);
        
        // convert ISO-8859-1 entities to numerical entities ('&eacute;' => '&#233;')       
        $mapping = array();
        foreach (get_html_translation_table(HTML_ENTITIES, ENT_QUOTES) as $char => $entity){
            $mapping[$entity] = '&#' . ord($char) . ';';
        }
        $string = str_replace(array_keys($mapping), $mapping, $string);
       
        // encode as UTF-8
        $string = utf8_encode($string);
    
    //$string = str_replace("&amp;#", "&#", $string);
    //$string = str_replace("amp;#", "#", $string);
    return $string;       
}

function light_parseXMLcoding($string)
{
    if ( strlen($string) == 0 )
        return $string;
    
    libxml_use_internal_errors(true);
    $test_string = "<test>".$string."</test>";
    $doc = simplexml_load_string($test_string);
    
    if(!($doc)){
        // convert problematic characters to XML entities ('&' => '&amp;')
        $string = htmlentities($string);
        
        // convert ISO-8859-1 entities to numerical entities ('&eacute;' => '&#233;')       
        $mapping = array();
        foreach (get_html_translation_table(HTML_ENTITIES, ENT_QUOTES) as $char => $entity){
            $mapping[$entity] = '&#' . ord($char) . ';';
        }
        $string = str_replace(array_keys($mapping), $mapping, $string);
       
        // encode as UTF-8
        $string = utf8_encode($string);
    }
    //$string = str_replace("&amp;#", "&#", $string);
    //$string = str_replace("amp;#", "#", $string);
    return $string;       
}




function clean_note($note){
            $note = strtr($note, "", "\n'\n'");
	    $note = str_replace("&nbsp;"," ", $note);
	    $note = str_replace("&deg;","&#176;", $note);
            $note = str_replace("&rsquo;","&#8217;", $note);
            $note = str_replace("&lsquo;","&#8216;", $note);
            $note = str_replace("&rdquo;","&#8221;", $note);
            $note = str_replace("&ldquo;","&#8220;", $note);
            $note = str_replace("&ndash;","&#8211;", $note);
	    $note = str_replace("<br>","<br/>", $note);
            $note = str_replace("<b>","<strong>", $note);
            $note = str_replace("</b>","</strong>", $note);
            $note = str_replace("<i>","<em>", $note);
            $note = str_replace("</i>","</em>", $note);
	    //$note = str_replace("<br/>","<p> </p>", $note);
	    //$note = str_replace("<br />","<p> </p>", $note);
            //$note = str_replace("<br />","<p> </p>", $note);
	    $note = str_replace("",chr(13),$note);
            $note = str_replace("http://www.opencontext.org/database/space.php?item=","http://opencontext/subjects/", $note);
            
            return $note;
}


function recursive_in_array($needle, $haystack) {
    
    if(count($haystack)>0){
        
        foreach ($haystack as $stalk) {
            if ($needle === $stalk || (is_array($stalk) && recursive_in_array($needle, $stalk))) {
                return true;
            }
        }
    
    }//end case with array
    
    return false;
}


//-------------------------------------------
//-------------------------------------------
//This class makes the correct SQL query 
class QueryGen {

    var $do_importer; //do importer or not
    var $doType; // type of document generated

    


}//


//-------------------------------------------
//-------------------------------------------
class ItemProperties {

	var $valueID; //ArchaeoML value_id
	var $var_propid; //Open Context property id
	var $var_label; //Open Context variable label
	var $var_type; //Open Context property variable type
	var $show_val; //Open Context show value value
	var $xml_property; //simple XML object for this property
	
	//this function sets up a new property XML document
	function Init_property_xml($parent_node){
		global $archaeoml_ns_uri; //archaeoML name space
		
		$this->xml_property = $parent_node->addChild("property", "", $archaeoml_ns_uri);
		$this->var_type = "alphanumeric"; // default type
	}
	
	//this function gets the appropriate property data
	function Set_property_data($varid, $valid, $propid, $var_label, $var_type, $show_val){
	 
	 	global $archaeoml_ns_uri; //archaeoML name space
		
		$var_type = strtolower($var_type);	 
		$property_node = $this->xml_property;
		$property_node->addChild("variableID",$varid, $archaeoml_ns_uri);
				
		$this->valueID = $valid;
		$this->prop_id = $propid;
		$this->var_type = $var_type;
		$this->var_label = $var_label;
		$this->show_val = $show_val;
		$this->Assign_validate_value();
		//$this->Add_oc_ns_data();
	}
	
	
	
	//this function only creates Open Context elements if 
	//a standard ArchaeoML document is NOT requested
	function Add_oc_ns_data(){
	
		global $archaeoml_ns_uri; //archaeoML name space
		global $oc_ns_uri; //uri to the open context name space
		global $ArchaeoML_only; //if 'true' then do not make non-standard ArchaeoML
		global $oc_prop_uri; //uri base for properties
                global $oc_prop_ext; //uri extension for properties
                
		$property_node = $this->xml_property;
		$prop_id = $this->prop_id;
		$var_label = $this->var_label;
		$var_type = $this->var_type;
		$show_val = $this->show_val;
		
		$var_label = parseXMLcoding($var_label);
		$show_val = parseXMLcoding($show_val);
		//$show_val = htmlentities($show_val);
		
		//$show_val = str_replace("& ","&amp; ", $show_val);
		
		//echo $show_val."<br/>";
		
		if(!$ArchaeoML_only){
		
			$prop_id_node = $property_node->addChild("propid",$prop_id,$oc_ns_uri);
			$prop_id_node->addAttribute('href', $oc_prop_uri.$prop_id.$oc_prop_ext);
                        $var_node = $property_node->addChild("var_label",$var_label,$oc_ns_uri);
			$var_node->addAttribute('type', $var_type);
			$property_node->addChild("show_val", $show_val, $oc_ns_uri);
			
		}//end case to add alt_namespace node
	
	}//end Add_alt_ns_element function
		
	
	function Assign_validate_value(){
		
		global $archaeoml_ns_uri; //archaeoML name space
		global $oc_ns_uri; //uri to the open context name space
		
		$valueID = $this->valueID;
		$show_val = $this->show_val;
		$var_type = $this->var_type;
		$property_node = $this->xml_property;
		
		$val_type_ok = false;
		
		if($var_type == "integer"){
			$val_type_ok = true;
			$show_val = $show_val +0;
			
			
			if (intval($show_val) === $show_val) {
				$int_ok = true;
			}
			else{
				$int_ok = false;
			}
			
			if((is_int($show_val))||($int_ok)){
				$property_node->addChild("integer",$show_val,  $archaeoml_ns_uri);
				$this->Add_oc_ns_data();
			}
			else{
				$this->var_type = "alphanumeric";
				$property_node->addChild("valueID",$valueID,  $archaeoml_ns_uri);
				$this->Add_oc_ns_data();
			}
			
		}//end case for var_type = integer
		
		if($var_type == "decimal"){
			$val_type_ok = true;
			$show_val = $show_val +0;
			
			if(is_numeric($show_val)){
				$property_node->addChild("decimal",$show_val,  $archaeoml_ns_uri);
				$this->Add_oc_ns_data();
			}
			else{
				$this->var_type = "alphanumeric";
				$property_node->addChild("valueID",$valueID,  $archaeoml_ns_uri);
				$this->Add_oc_ns_data();
			}
			
		}//end case for var_type = decimal
		
		
		if($var_type == "boolean"){
			$val_type_ok = true;
			$property_node->addChild("boolean",$show_val,  $archaeoml_ns_uri);
			$this->Add_oc_ns_data();
		}//end case for var_type = bolean
		
		
		if(substr_count($var_type, "calend")>0){
			$val_type_ok = true;
			
			$xml_ok_val = date("Y-m-d\TH:i:s\Z", strtotime($show_val));
				
				if($show_val != '0000-00-00'){
					$this->var_type = "calendar";
					$property_node->addChild("date",$xml_ok_val,  $archaeoml_ns_uri);
					$this->Add_oc_ns_data();
				}
				else{
					$this->var_type = "alphanumeric";
					$property_node->addChild("valueID",$valueID,  $archaeoml_ns_uri);
					$this->Add_oc_ns_data();
				}
				
		}//end case for var_type = calendar
		
		if(($var_type == "nominal")||($var_type == "alphanumeric")||($var_type == "ordinal")){
			$val_type_ok = true;
			$property_node->addChild("valueID",$valueID,  $archaeoml_ns_uri);
			$this->Add_oc_ns_data();
		}//end case for var_type = nominal, alphanumeric, or ordinal
		
		if(!val_type_ok){
			$this->var_type = "alphanumeric";	
			$property_node->addChild("valueID",$valueID,  $archaeoml_ns_uri);
			$this->Add_oc_ns_data();
		}
		
	}//end Assign_validate_value function

}//end property class
//-------------------------------------------
//-------------------------------------------



//-------------------------------------------
//-------------------------------------------
class item_notes {

	var $obs_notes; //array of notes for this observation and item
        
        var $short_des; //short description of projects
        var $long_des; //long description of projects
    
        var $internal_doc; //diary internal doc string
        var $do_diary; //do diary procedure
        
        var $var_des; //variable description
        var $prop_des; //property description
        
        
        function Set_project_notes($short_des, $long_des){
            $this->short_des = $short_des;
            $this->long_des = $long_des;
        }
        
        function Set_property_notes($var_des, $prop_des){
            $this->var_des = $var_des;
            $this->prop_des = $prop_des;
        }
        
        function Set_diary($diary_do){
            $this->do_diary = $diary_do;
        }

	function Make_notes_xml($parent_node, $id, $act_obs=""){
		global $archaeoml_ns_uri; //archaeoML name space
		global $oc_ns_uri; //uri to the open context name space
                //echo var_dump($this->short_des);
		
                $this->get_item_notes($id, $act_obs);
		$obs_notes = $this->obs_notes;
                
		if((!empty($obs_notes))||(!empty($this->long_des))||(!empty($this->var_des))||(!empty($this->prop_des))){
			
			$notes_xml = $parent_node->addChild("notes", "",  $archaeoml_ns_uri);
			
                        if(!empty($obs_notes)){
                            foreach($obs_notes AS $act_note){
                                    $act_note_xml = $notes_xml->addChild("note", "",  $archaeoml_ns_uri);
                                    $act_note_xml->addChild("string", $act_note, $archaeoml_ns_uri);	
                            }
                        }
                        if(!empty($this->short_des)){
                            $short_note_xml = $notes_xml->addChild("note", "",  $archaeoml_ns_uri);
                            $short_note_xml->addAttribute('type', 'short_des');
                            $short_note_xml->addChild("string", $this->short_des, $archaeoml_ns_uri);
                        }
                        if(!empty($this->long_des)){
                            $long_note_xml = $notes_xml->addChild("note", "",  $archaeoml_ns_uri);
                            $long_note_xml->addAttribute('type', 'long_des');
                            $long_note_xml->addChild("string", $this->long_des, $archaeoml_ns_uri);
                        }
                        if(!empty($this->var_des)){
                            $long_note_xml = $notes_xml->addChild("note", "",  $archaeoml_ns_uri);
                            $long_note_xml->addAttribute('type', 'var_des');
                            $long_note_xml->addChild("string", $this->var_des, $archaeoml_ns_uri);
                        }
                        if(!empty($this->prop_des)){
                            $long_note_xml = $notes_xml->addChild("note", "",  $archaeoml_ns_uri);
                            $long_note_xml->addAttribute('type', 'prop_des');
                            $long_note_xml->addChild("string", $this->prop_des, $archaeoml_ns_uri);
                        }
                        
		}//end case with notes
		
	}//end Make_notes_xml function

	function get_item_notes($id, $act_obs=""){
	
		global $property_tab; //name of the property table
		global $variable_tab; //name of the variable table
		global $value_tab; //name of the value table
		global $observe_tab; //name of the observation table
		
		//if observation numbers are recognized, include in query term
		if(strlen($act_obs)<1){
			$obsterm = "";
		}
		else{
			$obsterm = "AND $observe_tab.obs_num = $act_obs ";
		}
		
		
		$notequery = "SELECT $value_tab.val_text
		FROM $observe_tab
		LEFT JOIN $property_tab ON $observe_tab.property_uuid = $property_tab.property_uuid
		LEFT JOIN $variable_tab ON $property_tab.variable_uuid = $variable_tab.variable_uuid
		LEFT JOIN $value_tab ON $property_tab.value_uuid = $value_tab.value_uuid
		WHERE $observe_tab.subject_uuid = '$id' AND $property_tab.variable_uuid = 'NOTES'
		$obsterm
		";
		
		$noteresult = mysql_query($notequery);
		$noterows = mysql_numrows($noteresult);
		
		if($noterows>0){
			$i=0;
			while ($i < $noterows) {
				$note = mysql_result($noteresult,$i,"val_text");
				$note = clean_note($note);
				$note = parseXMLcoding($note);
				//$note = htmlentities($note);
				//$note = "<![CDATA[".$note."]]>";
				
				$obs_notes[$i] = $note;
				
			$i++;
			}//end loop through notes

                if($this->do_diary){
                    $this->internal_doc = $obs_notes[0];
                    //echo $this->internal_doc;
                    unset($obs_notes[0]);
                    $this->obs_notes = $obs_notes;  
                }
		else{	
                    $this->obs_notes = $obs_notes;
                }

	    }//end case with notes
		
            //echo $this->internal_doc;
	}// end item_notes function


}//end class item_notes
//-------------------------------------------
//-------------------------------------------



//-------------------------------------------
//-------------------------------------------
class Links {

	var $oc_space_links; //Array of Open Context specific spatial link data
	var $oc_media_links; //Array of Open Context specific media link data
	var $oc_diary_links; //Array of Open Context specific diary/narrative link data
	var $oc_person_links; //Array of Open Context specific person link data
	var $dc_contributors; //Array of persons who are Dublin Core contributors

	var $link_array; //Array of standard ArchaeoML linking data 
	var $links_xml; //simple XML object for all the links
	
        var $space_do = true; //make space links, don't if person
        var $revLinks = false; //also make revers, or reciprocal media links
        
        var $root_uuid; //array of project root uuid's
        var $do_property_people = false; //do person links
        
	function Make_links_xml($parent_node, $id, $observation = ""){
	
		global $ArchaeoML_only; //if 'true' then do not make non-standard ArchaeoML
		
		$this->Init_links_xml($parent_node);
                
                if($this->space_do){
                    $this->Get_linked_spatial($id, $observation);
                }
                
                //make links for root items associated with a project
                if(!empty($this->root_uuid)){
                    $this->Get_root_spatial();
                }
                
                $this->Get_media_links($id, $observation);
                
                if(!($this->space_do)&&(count($this->oc_media_links)<1)){
                    $this->revLinks = true;
                    $this->Get_media_links($id, $observation);
                }
                
		$this->Get_diary_links($id, $observation);
		$this->Get_person_links($id, $observation);
		
                //get all people associated with a project
                if(!empty($this->root_uuid)){
                    $this->Get_person_project_links($id);
                }
                
                //get all people associated with a property
                if(($this->do_property_people)&&(empty($dc_contributors))){
                    $this->Get_property_person_links($id);
                }
                
		$this->Make_standard_links();
		
		if(!$ArchaeoML_only){
                        if($this->space_do){
                            $this->make_space_xml();
                        }
                        if(!($this->revLinks)){
                            $this->make_media_xml();
                        }
                        $this->make_diary_xml();
			$this->make_person_xml();
		}//end case where ArchaeoML only is NOT requested
	}
	
	
	function Init_links_xml($parent_node){
		global  $archaeoml_ns_uri; // uri to archaeoML namespace
		global $oc_ns_uri; //uri to the open context name space
		global $ArchaeoML_only; //if 'true' then do not make non-standard ArchaeoML
		
		$this->links_xml = $parent_node->addChild("links",  "", $archaeoml_ns_uri);
		
	}//end function initializing children XML



	function Make_standard_links(){
		
		//do this function to make standard ArchaeoML links
		
		global  $archaeoml_ns_uri; // uri to archaeoML namespace
		
		$links_xml_node = $this->links_xml;
		$link_array = $this->link_array; //array of ArchaeoML standard link data
		
		if($link_array){
			foreach($link_array AS $act_link){
				
				$act_type = $act_link["type"];
				$act_info = $act_link["info"];
				$act_id = $act_link["docID"];
				
				$act_doc_xml = $links_xml_node->addChild("docID",$act_id, $archaeoml_ns_uri);
				$act_doc_xml->addAttribute("type", $act_type);
				$act_doc_xml->addAttribute("info", $act_info);
				
			}
		}
	}//end Make standard links function



	function Get_linked_spatial($id, $act_obs=""){
	
		global $oc_ns_uri; //uri to the open context name space
		global $ArchaeoML_only; //if 'true' then do not make non-standard ArchaeoML
		
		global $links_tab;//name of the links table
		global $space_tab;//name of the space table
		global $sp_classes_tab; //name of the space_table
		global $sp_contain_tab; //name of the space_containment_table
		
		
		$link_array = $this->link_array; //array of ArchaeoML standard link data
		$link_count = count($link_array); //count ArchaeoML standard links
		if($link_count<1){
                    $link_array = array();
                }
		
		//if observation numbers are recognized, include in query term
		if(strlen($act_obs)<1){
			$obsterm = "";
		}
		else{
			$obsterm = "AND $links_tab.origin_obs = $act_obs ";
		}
		
		
		//this query gets the uuid's and names of the linked spatial items of the viewed item
		$lnquery = "SELECT $space_tab.uuid, $space_tab.space_label, 
			$sp_classes_tab.class_label, $sp_classes_tab.sm_class_icon AS icon, 
			$links_tab.link_type
		FROM $links_tab
		INNER JOIN $space_tab ON $space_tab.uuid = $links_tab.targ_uuid
		INNER JOIN $sp_classes_tab ON $space_tab.class_uuid = $sp_classes_tab.class_uuid
		WHERE $links_tab.origin_uuid = '$id' AND $links_tab.targ_type ='spatial'
			$obsterm
		ORDER BY $links_tab.link_type, $sp_classes_tab.class_label, $space_tab.space_label;
		";
		
		$lnresult = mysql_query($lnquery);
		$numln = mysql_numrows($lnresult);
		
		if($numln<1){
		
			//if you don't find links, do the reverse query
			$lnquery = "SELECT $space_tab.uuid, $space_tab.space_label, 
			$sp_classes_tab.class_label, $sp_classes_tab.sm_class_icon AS icon, 
			$links_tab.link_type
			FROM $links_tab
			INNER JOIN $space_tab ON $space_tab.uuid = $links_tab.origin_uuid
			INNER JOIN $sp_classes_tab ON $space_tab.class_uuid = $sp_classes_tab.class_uuid
			WHERE $links_tab.targ_uuid = '$id' AND $links_tab.origin_type ='spatial'
				$obsterm
			ORDER BY $links_tab.link_type, $sp_classes_tab.class_label, $space_tab.space_label;
			";
		
		
			$lnresult = mysql_query($lnquery);
			$numln = mysql_numrows($lnresult);
			
		}// end case of looking for reverse links
		
		$i=0;
                $act_space_links = array();
		while ($i < $numln) {
		
			$lnspid = mysql_result($lnresult,$i,"uuid");
			$lnspname = mysql_result($lnresult,$i,"space_label");
			$lnspclass = mysql_result($lnresult,$i,"class_label");
			$lnicon = mysql_result($lnresult,$i,"icon");
			$relation = mysql_result($lnresult,$i,"link_type");
			
			$lnspname = parseXMLcoding($lnspname);
			$lnspclass = parseXMLcoding($lnspclass);
			$relation = parseXMLcoding($relation);
			
                        
                        if(recursive_in_array($lnspid, $act_space_links)&&($relation == "Standard")){
                            $adding_ok = false;
                        }
                        else{
                            $adding_ok = true;
                        }
                        
                        if($adding_ok){
                            $act_space_links[$i] = array("id"=> $lnspid, 
				"name"=> $lnspname, 
				"relation"=> $relation,
				"class_name"=> $lnspclass,
				"class_icon"=> $lnicon
				);
                            
                            $link_array[$link_count+$i] = array("type"=> "spatialUnit",
				"info"=> $relation,
				"docID"=> $lnspid);
                            
                        }//end case where ok to add to the array
                        
		$i++;
		}
		
		if($numln>0){
			$this->oc_space_links = $act_space_links;
			$this->link_array = $link_array;
		}
		
	}//end linked_spatial

        function Get_root_spatial(){
	
		global $oc_ns_uri; //uri to the open context name space
		global $ArchaeoML_only; //if 'true' then do not make non-standard ArchaeoML
		
		global $links_tab;//name of the links table
		global $space_tab;//name of the space table
		global $sp_classes_tab; //name of the space_table
		global $sp_contain_tab; //name of the space_containment_table
		
		
		$link_array = $this->link_array; //array of ArchaeoML standard link data
                $act_space_links = $this->oc_space_links; //array of existing open context space links
                $root_uuid = $this->root_uuid; //array of root items for a project
                
		$link_count = count($link_array); //count ArchaeoML standard links
                if($link_count<1){
                    $link_array = array();
                }
                
		$oc_sp_link_count = count($act_space_links); //count Open Context space links
                if($oc_sp_link_count <1){
                    $act_space_links = array();
                }
                
                
		$where_term = " ( ";
                
		foreach($root_uuid as $act_root){
                    $act_where_term = $space_tab.".uuid = '".$act_root."' ";
                    $where_term .= $act_where_term." OR ";
                }
                
                $where_term .= $act_where_term." )"; // no dangling, just do the last term twice "or";
                
                
		//this query gets the uuid's and names of the linked spatial items of the viewed item
		$lnquery = "SELECT $space_tab.uuid, $space_tab.space_label, 
			$sp_classes_tab.class_label, $sp_classes_tab.sm_class_icon AS icon, 
			'project root' AS link_type
		FROM $space_tab
		INNER JOIN $sp_classes_tab ON $space_tab.class_uuid = $sp_classes_tab.class_uuid
		WHERE $where_term
			$obsterm
		ORDER BY $sp_classes_tab.class_label, $space_tab.space_label;
		";
		
                //echo $lnquery;
                
		$lnresult = mysql_query($lnquery);
		$numln = mysql_numrows($lnresult);
		
                $i=0;
		while ($i < $numln) {
		
			$lnspid = mysql_result($lnresult,$i,"uuid");
			$lnspname = mysql_result($lnresult,$i,"space_label");
			$lnspclass = mysql_result($lnresult,$i,"class_label");
			$lnicon = mysql_result($lnresult,$i,"icon");
			$relation = mysql_result($lnresult,$i,"link_type");
			
			$lnspname = parseXMLcoding($lnspname);
			$lnspclass = parseXMLcoding($lnspclass);
			$relation = parseXMLcoding($relation);
			
                        if(recursive_in_array($lnspid, $act_space_links)&&($relation == "Standard")){
                            $adding_ok = false;
                        }
                        else{
                            $adding_ok = true;
                        }
                        
                        if($adding_ok){
                            $act_space_links[$i+$oc_sp_link_count] = array("id"=> $lnspid, 
				"name"=> $lnspname, 
				"relation"=> $relation,
				"class_name"=> $lnspclass,
				"class_icon"=> $lnicon
				);
                            
                            $link_array[$link_count+$i] = array("type"=> "spatialUnit",
				"info"=> $relation,
				"docID"=> $lnspid);
                            
                        }//end case where ok to add to the array
                        
		$i++;
		}
		
		if($numln>0){
			$this->oc_space_links = $act_space_links;
			$this->link_array = $link_array;
		}
		
	}//end linked_root_spatial




	function make_space_xml(){
		
		global $oc_ns_uri; //uri to the open context name space
		global $ArchaeoML_only; //if 'true' then do not make non-standard ArchaeoML
		global $oc_spatial_uri; //URI base for spatial items
                global $oc_spatial_ext; //URI extension for spatial items
                
		$links_xml_node = $this->links_xml;
				
		$space_links = $this->oc_space_links;
		$count_splinks = count($space_links);
		
		if($count_splinks>0){
		
			$space_links_xml = $links_xml_node->addChild("space_links","", $oc_ns_uri);
		
			foreach($space_links AS $act_link){
				
				 $act_id = $act_link["id"];
				 $act_name = $act_link["name"];
				 $act_relation = $act_link["relation"];
				 $act_class = $act_link["class_name"];
				 $act_class_icon = $act_link["class_icon"];
				 
				 $act_space_xml = $space_links_xml->addChild("link","", $oc_ns_uri);
                                 
                                 if($act_relation == "project root"){
                                    $act_space_xml->addAttribute('project_root', $act_name);
                                 }
                                 
                                 $act_href = $oc_spatial_uri.$act_id.$oc_spatial_ext;
                                 $act_space_xml->addAttribute("href", $act_href);
				 $act_space_xml->addChild("name", $act_name, $oc_ns_uri);
				 $act_space_xml->addChild("id", $act_id , $oc_ns_uri);
				 $act_space_xml->addChild("relation", $act_relation, $oc_ns_uri);
				 $child_class = new Space_Class;
				 $child_class->Set_class_XML($act_space_xml, $act_class, $act_class_icon);
				 unset($child_class);
				 $item_context = new Context;
				 $item_context->Set_default_context_xml($act_space_xml, $act_id);
				 unset($item_context);
			
			}//end loop
		
		}//end case where $count of space_links>0
		
	}//end function make_space_xml



	function Get_person_links($id, $act_obs=""){
		
		global $oc_ns_uri; //uri to the open context name space
		global $ArchaeoML_only; //if 'true' then do not make non-standard ArchaeoML
		
		global $links_tab;//name of the links table
		global $person_tab;//name of the person table
                
		$link_array = $this->link_array; //array of ArchaeoML standard link data
		$link_count = count($link_array); //count ArchaeoML standard links
		
		
		//if observation numbers are recognized, include in query term
		if(strlen($act_obs)<1){
			$obsterm = "";
		}
		else{
			$obsterm = "AND $links_tab.origin_obs = $act_obs ";
		}
			
		//this finds people linked to a given item
		$persquery = "SELECT $person_tab.uuid, 
			$person_tab.combined_name, $links_tab.link_type, $links_tab.link_type as Order_kind
		FROM $links_tab
		INNER JOIN $person_tab 
			ON $links_tab.targ_uuid = $person_tab.uuid
		WHERE $links_tab.origin_uuid = '$id' 
			AND $links_tab.targ_type ='person'
			$obsterm
		ORDER BY Order_kind;
		";
                
                $persquery = imp_pers_query_fix($persquery);
                $persquery = user_person_tab($persquery, $obsterm, $id); //add a union query for user tab in importer
                
                //echo $persquery;
		$persresult = mysql_query($persquery);
		$numpers = mysql_numrows($persresult);

		$i=0;
		while ($i < $numpers) {

			$persid = mysql_result($persresult,$i,"uuid");
			$persname = mysql_result($persresult,$i,"combined_name");
			$relation = mysql_result($persresult,$i,"link_type");
			
			$persname = parseXMLcoding($persname);
			$relation = parseXMLcoding($relation);
			
			if($relation =="o_Creator"){
				$relation = "Creator";
			}
			
                        
                        if(recursive_in_array($persid, $link_array)&&(($relation == "Creator")||($relation == "Observer"))){
                            $adding_ok = false;
                        }
                        else{
                            $adding_ok = true;
                        }
                        
                        if($adding_ok){
                        
                            //$act_plinks[$i] = array("id"=> $persid, "name"=> $persname, "relation"=> $relation);
                            $act_plinks[] = array("id"=> $persid, "name"=> $persname, "relation"=> $relation);
                            
                            if(!$ArchaeoML_only){
                                    $this->contributor_check($persname, $relation); // chech for DC contributor status
                            }//only do this step if NOT making an ArchaeoML only document
                            
                            //$link_array[$link_count+$i] = array("type"=> "person",
                            $link_array[] = array("type"=> "person",
                                    "info"=> $relation,
                                    "docID"=> $persid);
			
                        }//end case to add unique person
		$i++;
		}
		
		if($numpers>0){
			$this->oc_person_links = $act_plinks;
			$this->link_array = $link_array;
		}

	}//end function get person links


        function Get_person_project_links($id, $act_obs=""){
		
		global $oc_ns_uri; //uri to the open context name space
		global $ArchaeoML_only; //if 'true' then do not make non-standard ArchaeoML
		
		global $links_tab;//name of the links table
		global $person_tab;//name of the person table
		
		$link_array = $this->link_array; //array of ArchaeoML standard link data
		$link_count = count($link_array); //count ArchaeoML standard links
		
                $act_plinks = $this->oc_person_links; //array of opencontext person links
                $oc_person_count = count($act_plinks); // count of opencontext person links
		
		//this finds people linked to a given project
		$persquery = "SELECT $person_tab.uuid, 
			$person_tab.combined_name, 'Project Participant' AS link_type
		FROM $person_tab
		WHERE $person_tab.project_id = '$id' 
		ORDER BY $person_tab.last_name;
		";

		$persresult = mysql_query($persquery);
		$numpers = mysql_numrows($persresult);

		$i=0;
		while ($i < $numpers) {

			$persid = mysql_result($persresult,$i,"uuid");
			$persname = mysql_result($persresult,$i,"combined_name");
			$relation = mysql_result($persresult,$i,"link_type");
			
			$persname = parseXMLcoding($persname);
			$relation = parseXMLcoding($relation);
			
			if($relation =="o_Creator"){
				$relation = "Creator";
			}
			
			//$act_plinks[$i + $oc_person_count] = array("id"=> $persid, "name"=> $persname, "relation"=> $relation);
			$act_plinks[] = array("id"=> $persid, "name"=> $persname, "relation"=> $relation);
                        
			if(!$ArchaeoML_only){
				$this->contributor_check($persname, $relation); // chech for DC contributor status
			}//only do this step if NOT making an ArchaeoML only document
			
			//$link_array[$link_count+$i] = array("type"=> "person",
                        $link_array[] = array("type"=> "person",
				"info"=> $relation,
				"docID"=> $persid);
			
		$i++;
		}
		
		if($numpers>0){
			$this->oc_person_links = $act_plinks;
			$this->link_array = $link_array;
		}

	}//end function get person links


        function Get_property_person_links($id){
            
            global $oc_ns_uri; //uri to the open context name space
            global $ArchaeoML_only; //if 'true' then do not make non-standard ArchaeoML
            
            global $links_tab;//name of the links table
            global $person_tab;//name of the person table
            global $links_tab; //name of the links table
            global $property_tab; //name of the observe table
            global $observe_tab; //name of the observe table
            
            
            $link_array = $this->link_array; //array of ArchaeoML standard link data
            $link_count = count($link_array); //count ArchaeoML standard links
            
            $act_plinks = $this->oc_person_links; //array of opencontext person links
            $oc_person_count = count($act_plinks); // count of opencontext person links
            
            //this finds people linked to a given project
            $persquery = "SELECT $person_tab.uuid, 
            $person_tab.combined_name, 'Analyst' AS link_type, count($links_tab.targ_uuid) as linkcount
            FROM $person_tab
            JOIN $links_tab ON $person_tab.uuid = $links_tab.targ_uuid
            JOIN $observe_tab ON $observe_tab.subject_uuid = $links_tab.origin_uuid
            WHERE $observe_tab.property_uuid = '$id'
            GROUP BY $person_tab.uuid
            ORDER BY linkcount DESC, $person_tab.last_name;
            ";

            $persresult = mysql_query($persquery);
            $numpers = mysql_numrows($persresult);
            
            if($numpers > 3){
                $numpers = 0;
            }
            
            $i=0;
            while ($i < $numpers) {

                    $persid = mysql_result($persresult,$i,"uuid");
                    $persname = mysql_result($persresult,$i,"combined_name");
                    $relation = mysql_result($persresult,$i,"link_type");
                    
                    $persname = parseXMLcoding($persname);
                    $relation = parseXMLcoding($relation);
                    
                    if($relation =="o_Creator"){
                            $relation = "Creator";
                    }
                    
                    //$act_plinks[$i + $oc_person_count] = array("id"=> $persid, "name"=> $persname, "relation"=> $relation);
                    $act_plinks[] = array("id"=> $persid, "name"=> $persname, "relation"=> $relation);
                    
                    if(!$ArchaeoML_only){
                            $this->contributor_check($persname, $relation); // chech for DC contributor status
                    }//only do this step if NOT making an ArchaeoML only document
                    
                    //$link_array[$link_count+$i] = array("type"=> "person",
                    $link_array[] = array("type"=> "person",
                            "info"=> $relation,
                            "docID"=> $persid);
                    
            $i++;
            }
            
            if($numpers>0){
                    $this->oc_person_links = $act_plinks;
                    $this->link_array = $link_array;
            }
            
        }//end function get property person links


	function make_person_xml(){
		
		global $oc_ns_uri; //uri to the open context name space
		global $ArchaeoML_only; //if 'true' then do not make non-standard ArchaeoML
		global $oc_persons_uri; //persons URI base
                global $oc_persons_ext; //persons URI extension
                global $cont_roles; //array of possible dublin core contributor relations
                
		$links_xml_node = $this->links_xml;
				
		$person_links = $this->oc_person_links;
		$count_plinks = count($person_links);
		
		if($count_plinks>0){
		
			$person_links_xml = $links_xml_node->addChild("person_links","", $oc_ns_uri);
		
			foreach($person_links AS $act_link){
				
				 $act_id = $act_link["id"];
				 $act_name = $act_link["name"];
				 $act_relation = $act_link["relation"];
				 
				 $act_person_xml = $person_links_xml->addChild("link","", $oc_ns_uri);
                                 $act_href = $oc_persons_uri.$act_id.$oc_persons_ext;
                                 $act_person_xml->addAttribute("href", $act_href);
                                 if(in_array($act_relation, $cont_roles)){
                                    $act_person_xml->addAttribute("cite", "contributor");
                                 }
                                 else{
                                    $act_person_xml->addAttribute("cite", "false");
                                 }
				 $act_person_xml->addChild("name", $act_name, $oc_ns_uri);
				 $act_person_xml->addChild("id", $act_id , $oc_ns_uri);
				 $act_person_xml->addChild("relation", $act_relation, $oc_ns_uri);
			
			}//end loop
		
		}//end case where $count of person_links>0
		
	}//end function make_person_xml


	function contributor_check($person, $per_role){
		global $cont_roles; //array of possible dublin core contributor relations
	
		$contributor = false;
		$num_pos = count($cont_roles);
		$i=0;
		while ($i < $num_pos){
			$act_check = $cont_roles[$i];
			if($per_role == $act_check){
				$contributor = true;
				$i = $num_pos;
			}
		$i++;	
		}//end loop

		if($contributor){
			$current_contributors = $this->dc_contributors;
			$current_contributors[] = $person;
			$this->dc_contributors = $current_contributors;
			
			//echo "Contrubutor: ".$person."<br/>";
		}//end case where this is a contributor
		
	}//end contributor_check function


	function Get_media_links($id, $act_obs=""){
		
                global $oc_ns_uri; //uri to the open context name space
		global $ArchaeoML_only; //if 'true' then do not make non-standard ArchaeoML
		
		global $links_tab;//name of the links table
		global $resource_tab;//name of the resource table
		
                
                $revLinks = $this->revLinks;
                 
		$thumb_uri = "http://www.opencontext.org/database/oc_media/"; //URI prefix for thumbnail images 
		 
		 
		$link_array = $this->link_array; //array of ArchaeoML standard link data
		$link_count = count($link_array); //count ArchaeoML standard links
		
		
		//get the project's thumbnail directory
		$dir_query = "SELECT projects.thumb_root, projects.project_id
		FROM $links_tab
		JOIN projects ON projects.project_id = $links_tab.project_id
		WHERE $links_tab.origin_uuid = '$id' OR
			$links_tab.targ_uuid = '$id'
		LIMIT 1
		";
		
                $dir_query = imp_project_query_fix($dir_query);
                
		$dirresult = mysql_query($dir_query);
		
		if((!empty($dirresult))&&(mysql_numrows($dirresult)>0)){
			$proj_media_dir = mysql_result($dirresult,0,"thumb_root");
			$iprojid = mysql_result($dirresult,0,"project_id");
			
			if($iprojid == 'A5DDBEA2-B3C8-43F9-8151-33343CBDC857'){
				$petra_fix = true;
				$petra_uri = "http://www.opencontext.org/importer/import_review/project_files/petra/";
				$aai_uri = "http://www.alexandriaarchive.org/opencontext/petra/";
			}
			
		}
		else{
			$proj_media_dir = "";
		}
		
		
		//if observation numbers are recognized, include in query term
		if(strlen($act_obs)<1){
			$obsterm = "";
		}
		else{
			$obsterm = "AND $links_tab.origin_obs = $act_obs ";
                        if($revLinks){
                            $obsterm = "AND $links_tab.targ_obs = $act_obs ";
                        }
		}
			
		//if observation numbers are recognized, include in query term

		///this finds media items linked to a given item
		$resquery = "SELECT DISTINCT $resource_tab.uuid, 
			$resource_tab.res_label, 
			$resource_tab.res_path, 
			$resource_tab.res_filename, 
			$resource_tab.res_type, 
			$links_tab.link_type,
			$resource_tab.ia_thumb
		FROM $links_tab
		INNER JOIN $resource_tab ON $links_tab.targ_uuid = $resource_tab.uuid
		WHERE $links_tab.origin_uuid = '$id'
		AND $links_tab.targ_type LIKE 'Resource%'
		$obsterm
		ORDER BY $resource_tab.res_type, $resource_tab.res_label
		";
		
		
		
                if($revLinks){
                    $resquery = "SELECT DISTINCT $resource_tab.uuid, 
			$resource_tab.res_label, 
			$resource_tab.res_path, 
			$resource_tab.res_filename, 
			$resource_tab.res_type, 
			$links_tab.link_type,
			$resource_tab.ia_thumb
                    FROM $links_tab
                    INNER JOIN $resource_tab ON $links_tab.origin_uuid = $resource_tab.uuid
                    WHERE $links_tab.targ_uuid = '$id'
                    AND $links_tab.origin_type LIKE 'Resource%'
                    $obsterm
                    ORDER BY $resource_tab.res_type, $resource_tab.res_label
                    ";
                }
                
                $resquery = imp_res_query_fix($resquery);
                //echo $resquery;
		$resresult = mysql_query($resquery);
		$numres = mysql_numrows($resresult);


		$i=0;
		while ($i < $numres) {
		
			$resid = mysql_result($resresult,$i,"uuid");
			$resname = mysql_result($resresult,$i,"res_label");
			$respath = mysql_result($resresult,$i,"res_path");
			$resfile = mysql_result($resresult,$i,"res_filename");
			$restype = mysql_result($resresult,$i,"res_type");
			$relation = mysql_result($resresult,$i,"link_type");
			$ext_thumb = mysql_result($resresult,$i,"ia_thumb");
		
			$resname = parseXMLcoding($resname);
			$relation = parseXMLcoding($relation);
                        
                        // urlencode the URL 
                        $resfile = urlencode($resfile);
                        // but replace the '+' character with a ' ' to account for URLs with spaces
                        $resfile = str_replace("+", " ", $resfile);
                        
			$restype = strtolower($restype);
		
			$respath = $proj_media_dir."/".$respath;
			$respath = str_replace("////","/",$respath);
			$respath = str_replace("///","/",$respath);
			$respath = str_replace("//","/",$respath);
			
			if(strlen($ext_thumb)>5){
				$respath ="";
				$resfile ="";
			}
			else{
				$ext_thumb = $thumb_uri.$respath.$resfile;
				$ext_thumb = str_replace("////","/",$ext_thumb);
				$ext_thumb = str_replace("///","/",$ext_thumb);
				$ext_thumb = str_replace("//","/",$ext_thumb);
                                // note: without the line below, 'http://' becomes 'http:/' as a result of the above str_replace
                                $ext_thumb = str_replace("http:/www.opencontext.org", "http://www.opencontext.org", $ext_thumb);                                 
			}
	
			
			if($petra_fix){
				$ext_thumb = str_replace($aai_uri, $petra_uri, $ext_thumb);
			}
	
	
			$ext_thumb = str_replace(" ", "%20", $ext_thumb);
	
			$act_mlinks[$i] = array("id"=> $resid, 
				"name"=> $resname,
				"filename"=> $resfile,
				"path"=> $respath,
				"media_type"=> $restype,
				"thumbnailURI"=> $ext_thumb, 
				"relation"=> $relation);
			
			$link_array[$link_count+$i] = array("type"=> "resource",
				"info"=> $relation,
				"docID"=> $resid);
			
		$i++;
		}
		
		if($numres>0){
			//echo "<br/><br/>".var_dump($act_mlinks);
			$this->oc_media_links = $act_mlinks;
			$this->link_array = $link_array;
		}

	}//end function get media links



	function make_media_xml(){
		
		global $oc_ns_uri; //uri to the open context name space
		global $ArchaeoML_only; //if 'true' then do not make non-standard ArchaeoML
		global $oc_media_uri; //uri base for media
                global $oc_media_ext; //uri extension for media
                
		$links_xml_node = $this->links_xml;
				
		$media_links = $this->oc_media_links;
		$count_mlinks = count($media_links);
		
		if($count_mlinks>0){
		
			$media_links_xml = $links_xml_node->addChild("media_links","", $oc_ns_uri);
		
			foreach($media_links AS $act_link){
				
				 $act_id = $act_link["id"];
				 $act_name = $act_link["name"];
				 $act_relation = $act_link["relation"];
				 $act_media_type = $act_link["media_type"];
				 $act_path = $act_link["path"];
				 $act_filename = $act_link["filename"];
				 $act_thumb = $act_link["thumbnailURI"];
				 
				 $act_media_xml = $media_links_xml->addChild("link","", $oc_ns_uri);
                                 $act_href = $oc_media_uri.$act_id.$oc_media_ext;
                                 $act_media_xml->addAttribute("href", $act_href);
				 $act_media_xml->addChild("name", $act_name, $oc_ns_uri);
				 $act_media_xml->addChild("id", $act_id , $oc_ns_uri);
				 $act_media_xml->addChild("relation", $act_relation, $oc_ns_uri);
				 $act_media_xml->addChild("type", $act_media_type, $oc_ns_uri);
				 $act_media_xml->addChild("path", $act_path, $oc_ns_uri);
				 $act_media_xml->addChild("filename", $act_filename, $oc_ns_uri);
				 $act_media_xml->addChild("thumbnailURI", $act_thumb, $oc_ns_uri);
			
			}//end loop
		
		}//end case where $count of media_links>0
		
	}//end function make_media_xml


	
	function Get_diary_links($id, $act_obs=""){
		
		global $oc_ns_uri; //uri to the open context name space
		global $ArchaeoML_only; //if 'true' then do not make non-standard ArchaeoML
		
		global $links_tab;//name of the links table
		global $resource_tab;//name of the resource table
		global $diary_tab; //name of the diary/narative table
		 
		$link_array = $this->link_array; //array of ArchaeoML standard link data
		$link_count = count($link_array); //count ArchaeoML standard links
		
                if($link_count<1){
                    $link_array  = array();
                }
		else{
                    $link_count++;
                }
		//if observation numbers are recognized, include in query term
		if(strlen($act_obs)<1){
			$obsterm = "";
		}
		else{
			$obsterm = "AND $links_tab.origin_obs = $act_obs ";
		}
			
		//if observation numbers are recognized, include in query term

		//this finds diaries and narratives linked to a given item
		$narquery = "SELECT $diary_tab.uuid, 
			$diary_tab.diary_label, 
			$links_tab.link_type 
		FROM $links_tab
		INNER JOIN $diary_tab ON $links_tab.targ_uuid = $diary_tab.uuid
		WHERE $links_tab.origin_uuid = '$id'
		AND $links_tab.targ_type LIKE 'Diary%'
		$obsterm 
		ORDER BY $diary_tab.diary_label
		";
		
		//echo $narquery;
		
		$nar_result = mysql_query($narquery);
		$num_nar = mysql_numrows($nar_result);


		$i=0;
                $act_narlinks = array();
		while ($i < $num_nar) {
		
			$nar_id = mysql_result($nar_result,$i,"uuid");
			$nar_name = mysql_result($nar_result,$i,"diary_label");
			$relation = mysql_result($nar_result,$i,"link_type");
			
			$nar_name= parseXMLcoding($nar_name);
			$relation = parseXMLcoding($relation);
		
                        //echo $relation;
                        $act_narlinks[] = array("id"=> $nar_id,
                        //$act_narlinks[$i] = array("id"=> $nar_id,
				"name"=> $nar_name,
				"media_type"=> "internalDocument", 
				"relation"=> $relation);
			
			
                        $link_array[] = array("type"=> "resource",
                        //$link_array[$link_count+$i] = array("type"=> "resource",
				"info"=> $relation,
				"docID"=> $nar_id);
			
		$i++;
		}
		
		if($num_nar>0){
			//echo "<br/><br/>".var_dump($act_mlinks);
			$this->oc_diary_links = $act_narlinks;
			$this->link_array = $link_array;
		}

	}//end function get diary links
	


	function make_diary_xml(){
		
		global $oc_ns_uri; //uri to the open context name space
		global $ArchaeoML_only; //if 'true' then do not make non-standard ArchaeoML
		global $oc_diary_uri; //URI base for diary items
                global $oc_diary_ext; //URI extension for diary items
                
		$links_xml_node = $this->links_xml;
				
		$diary_links = $this->oc_diary_links;
		$count_nar_links = count($diary_links);
		
		if($count_nar_links>0){
		
			$diary_links_xml = $links_xml_node->addChild("diary_links","", $oc_ns_uri);
		
			foreach($diary_links AS $act_link){
				
				 $act_id = $act_link["id"];
				 $act_name = $act_link["name"];
				 $act_relation = $act_link["relation"];
				 $act_media_type = $act_link["media_type"];
				 
				 $act_diary_xml = $diary_links_xml->addChild("link","", $oc_ns_uri);
                                 $act_href = $oc_diary_uri.$act_id.$oc_diary_ext;
                                 $act_diary_xml->addAttribute("href", $act_href);
				 $act_diary_xml->addChild("name", $act_name, $oc_ns_uri);
				 $act_diary_xml->addChild("id", $act_id , $oc_ns_uri);
				 $act_diary_xml->addChild("relation", $act_relation, $oc_ns_uri);
				 $act_diary_xml->addChild("type", $act_media_type, $oc_ns_uri);
			
			}//end loop
		
		}//end case where $count of diary_links>0
		
	}//end function make_diary_xml

}//end Links class
//-------------------------------------------
//-------------------------------------------


class ResourceDescript{
   
   var $item_id; //archaeoML item id
   var $all_dc_contributors; //array of the names of ALL dublin core contributors
   
   function make_descriptions($parent_node, $id){

		
		
	$this->xml_get_properties($parent_node, $id);
	$this->xml_get_item_links($parent_node, $id);
	$this->xml_get_item_notes($parent_node, $id);
			
    }//end function make_observations
   
   
    
}//end class ResourceMake




//-------------------------------------------
//-------------------------------------------
class Observations{

	var $non_default_obs; //array of id's for non-default observations for an item
	var $all_dc_contributors; //array of the names of ALL dublin core contributors
        var $person_do = false; // people do different observations, especially in link generation

        var $short_des; //short project description
        var $long_des; //long project description
        var $root_uuid; //array of root_ids for a project 

        var $internal_doc; //string of diary internal doc
        var $var_des; //variable description
        var $prop_des; //property description
        
        var $do_property_people; //property people

	function make_observations($parent_node, $id){

		$this->get_item_obs($id);
		$non_default_obs_count = count($non_default_obs);
		
		$obs_node = $parent_node->addChild("observations", "", $archaeoml_ns_uri);
		
		//now add default observation data
		$default_obs_node = $obs_node->addChild("observation", "", $archaeoml_ns_uri);
		$this->xml_get_properties($default_obs_node, $id);
		$this->xml_get_item_links($default_obs_node, $id);
		$this->xml_get_item_notes($default_obs_node, $id);
		
		if($non_default_obs_count>0){
		
			foreach($non_default_obs AS $act_obs){
				$act_obs_node = $obs_node->addChild("observation", "", $archaeoml_ns_uri);
				$this->xml_get_properties($act_obs_node, $id, $act_obs);
				$this->xml_get_item_links($act_obs_node, $id, $act_obs);
				$this->xml_get_item_notes($act_obs_node, $id, $act_obs);
			
			}//end loop through nondefault observations
			
		}//end case with Non default observations
		
	}//end function make_observations 


        //do this for projects
	function make_project_descriptions($parent_node, $id){
	    
	    $this->xml_get_properties($parent_node, $id);
	    $this->xml_get_project_links($parent_node, $id);
	    $this->xml_get_project_notes($parent_node, $id);
	    
	}//end function


	//do this for resources
	function make_resource_descriptions($parent_node, $id){
	    
	    $this->xml_get_properties($parent_node, $id);
	    $this->xml_get_item_links($parent_node, $id);
	    $this->xml_get_item_notes($parent_node, $id);
	    
	}//end function

        //do this for persons
	function make_person_descriptions($parent_node, $id){
	    
            $this->person_do = true;
	    $this->xml_get_properties($parent_node, $id);
	    $this->xml_get_item_notes($parent_node, $id);
            $this->xml_get_item_links($parent_node, $id);
	    
	}//end function

        //do this for diaries
	function make_diary_descriptions($parent_node, $id){
	    
	    $this->xml_get_properties($parent_node, $id);
	    $this->xml_get_item_links($parent_node, $id);
	    $this->xml_get_diary_notes($parent_node, $id);
	    
	}//end function
        
        function make_property_descriptions($parent_node, $id){
	    
            $this->do_property_people = true;
	    $this->xml_get_properties($parent_node, $id);
	    $this->xml_get_item_links($parent_node, $id);
	    $this->xml_get_property_notes($parent_node, $id);
	    
	}//end function
        

	//this function gets id's for non-default observations associated with an item
	function get_item_obs($id){

            global $links_tab;
            global $observe_tab;

		$qallobs = "
			SELECT DISTINCT $links_tab.targ_obs AS obs_numbers
			FROM $links_tab
			WHERE $links_tab.targ_uuid = '$id'
			UNION
			SELECT DISTINCT $links_tab.origin_obs AS obs_numbers
			FROM $links_tab
			WHERE $links_tab.origin_uuid = '$id'
			UNION
			SELECT DISTINCT $observe_tab.obs_num AS obs_numbers
			FROM $observe_tab
			WHERE $observe_tab.subject_uuid = '$id'
			";
			
			//print $qallobs."<br><br>";
			
			$all_xml = "";
			
			$allobsresult = mysql_query($qallobs);
			$numall_obs = mysql_numrows($allobsresult);
			$zz=0;
			$i = 0;
			while ($zz < $numall_obs) {
			
				$act_obs = mysql_result($allobsresult, $zz, "obs_numbers");
				
				if(($act_obs != "0")&&(strlen($act_obs)>0)){
					$non_default_obs[$i] = $act_obs;
					$i++;
				}
				
			$zz++;
			}
		
			if($i>0){
				$this->non_default_obs = $non_default_obs;
			}
			
	}// end get_item_obs function

	function xml_get_properties($obs_node, $id, $act_obs=""){
		
		global $property_tab; //name of the property table
		global $variable_tab; //name of the variable table
		global $value_tab; //name of the value table
		global $observe_tab; //name of the observation table
		
		global $archaeoml_ns_uri; //namespace for ArchaeoML elements
		
		//if observation numbers are recognized, include in query term
		if(strlen($act_obs)<1){
			$obsterm = "";
		}
		else{
			$obsterm = "AND $observe_tab.obs_num = $act_obs ";
		}
		
			
		$query="SELECT $property_tab.property_uuid, 
			$property_tab.val_num, 
			DATE_FORMAT($property_tab.val_date, '%Y-%m-%d') as xml_date, 
			$variable_tab.var_label, 
			$value_tab.val_text, 
			IF (
			$value_tab.val_text IS NULL , (
				IF (
				$property_tab.val_date =0, $property_tab.val_num, $property_tab.val_date)
				), 
				$value_tab.val_text
				) AS allprop, 
			$variable_tab.var_type, 
			$variable_tab.variable_uuid, 
			$value_tab.value_uuid
		
		FROM $observe_tab
		LEFT JOIN $property_tab ON $observe_tab.property_uuid = $property_tab.property_uuid
		LEFT JOIN $variable_tab ON $property_tab.variable_uuid = $variable_tab.variable_uuid
		LEFT JOIN $value_tab ON $property_tab.value_uuid = $value_tab.value_uuid
		LEFT JOIN variable_sort ON $variable_tab.variable_uuid = variable_sort.variable_uuid
		WHERE $observe_tab.subject_uuid = '$id' AND $property_tab.variable_uuid <> 'NOTES'
		$obsterm
		ORDER BY variable_sort.sort_order
		
		";
		
		$query = imp_prop_query_fix($query);
		//echo $query;
		
		$result=mysql_query($query);
		$numprop = mysql_numrows($result);
		
		if($numprop>0){
			
			//call up the properties class, you're gonna need it
			$properties_node = $obs_node->addChild("properties", "", $archaeoml_ns_uri);
			
			
			$i=0;
			while ($i < $numprop) {
			
				$act_var_label = mysql_result($result,$i,"var_label");
				$act_var_type = mysql_result($result,$i,"var_type");
				$act_varid = mysql_result($result,$i,"variable_uuid");
				$act_val_val = mysql_result($result,$i,"allprop");
				$act_valid = mysql_result($result,$i,"value_uuid");
				$act_propid = mysql_result($result,$i,"property_uuid");
				
				$property_class = new ItemProperties;
				$property_class->Init_property_xml($properties_node);
				
				$property_class->Set_property_data($act_varid, 
					$act_valid, 
					$act_propid, 
					$act_var_label,
					$act_var_type,
					$act_val_val);	
				
				unset($property_class);	
			$i++;
			}
		}//end case with properties		

	}//end function get_properties	
	
	
	//this function gets notes for a given item and observation
	function xml_get_item_notes($obs_node, $id, $act_obs=""){
	
		$notes_class = new item_notes;	
		$notes_class->Make_notes_xml($obs_node, $id, $act_obs);
		
	}
	
        //this function gets notes for a given project
	function xml_get_project_notes($obs_node, $id, $act_obs=""){
	
            $short_des = $this->short_des;
            $long_des = $this->long_des;
            
		$notes_class = new item_notes;
                $notes_class->Set_pro_notes($short_des, $long_des);
                //echo var_dump($note_class->short_des);
		$notes_class->Make_notes_xml($obs_node, $id, $act_obs);
	}
        
        function xml_get_property_notes($obs_node, $id, $act_obs=""){
	
            $var_des = $this->var_des;
            $prop_des = $this->prop_des;
            
		$notes_class = new item_notes;
                $notes_class->Set_property_notes($var_des, $prop_des);
                //echo var_dump($note_class->short_des);
		$notes_class->Make_notes_xml($obs_node, $id, $act_obs);
	}
        
        
                //this function gets notes for a given project
	function xml_get_diary_notes($obs_node, $id, $act_obs=""){
	
            $notes_class = new item_notes;
            $notes_class->Set_diary(true);
	    $notes_class->Make_notes_xml($obs_node, $id, $act_obs);
            
            if(strlen($this->internal_doc)<2){
                $this->internal_doc = $notes_class->internal_doc;
            }
	}
        
	
	//this function makes links xml for the given item and observation. It gets
	//dublin core contributor data from the links for a given obs, and adds it to 
	//the dc contributor list for ALL observations of an item
	function xml_get_item_links($obs_node, $id, $act_obs=""){
	
		$all_dc_contributors = $this->all_dc_contributors;
		
		$item_links_class = new Links;
                
                if($this->person_do){
                    $item_links_class->space_do = false;
                }
                
                if($this->do_property_people){
                    $item_links_class->space_do = false;
                    $item_links_class->do_property_people = true;
                }
                
                
		$item_links_class->dc_contributors = $all_dc_contributors;
		$item_links_class->Make_links_xml($obs_node, $id, $act_obs);
		
		$this->all_dc_contributors = $item_links_class->dc_contributors;
	}
        
        
        //do linking functions for projects
        function xml_get_project_links($obs_node, $id, $act_obs=""){
	
		$all_dc_contributors = $this->all_dc_contributors;
		
		$item_links_class = new Links;
                
                $item_links_class->root_uuid = $this->root_uuid;
                
		$item_links_class->dc_contributors = $all_dc_contributors;
		$item_links_class->Make_links_xml($obs_node, $id, $act_obs);
		
		$this->all_dc_contributors = $item_links_class->dc_contributors;
	}
        
	
	
}//end observations class
//-------------------------------------------
//-------------------------------------------


















//-------------------------------------------
//-------------------------------------------
class Space_Class {

	var $class_xml; //Simple XML object for Open Context class xml
	
	var $sm_icon_prefix = "http://www.opencontext.org/database/ui_images/oc_icons/";
	var $large_icon_prefix = "http://www.opencontext.org/database/ui_images/med_oc_icons/";
	
	//this function sets up a new class XML document
	function Item_to_class_xml($parent_node, $item_id, $icon="small"){
		
		global $archaeoml_ns_uri; //archaeoML name space
		global $oc_ns_uri; //uri to the open context name space
		global $ArchaeoML_only; //if 'true' then do not make non-standard ArchaeoML
		global $space_tab; //name of the space_table
		global $sp_classes_tab; //name of the class_table
		
		//sets paraters to get the right sized icon
		if($icon == "small"){
			$icon_prefix = $this->sm_icon_prefix;
			$icon_term = $sp_classes_tab.".sm_class_icon AS icon ";
		}
		else{
			$icon_prefix = $this->large_icon_prefix;
			$icon_term = $sp_classes_tab.".class_icon AS icon ";
		}
		
		if(!$ArchaeoML_only){
		
			$query = "SELECT $sp_classes_tab.class_label, 
			$icon_term
			FROM $space_tab
			LEFT JOIN $sp_classes_tab 
			ON $space_tab.class_uuid = $sp_classes_tab.class_uuid
			WHERE $space_tab.uuid = '$item_id'
			";
		
			$result = mysql_query($query);
			
			if(!empty($result)){
				$numrows = mysql_numrows($result);	
		
				$class_label = mysql_result($result,0,"class_label"); 
				$class_icon = mysql_result($result,0,"icon");
				$class_icon = $icon_prefix.$class_icon;
				
                                if($class_label == null){
                                    $query = "SELECT $sp_classes_tab.class_label, 
                                    $icon_term
                                    FROM $space_tab
                                    LEFT JOIN $sp_classes_tab 
                                    ON $space_tab.class_uuid = $sp_classes_tab.class_label
                                    WHERE $space_tab.uuid = '$item_id'
                                    ";
                            
                                    //echo $query;
                                    $result = mysql_query($query);
                                    if(!empty($result)){
                                        $numrows = mysql_numrows($result);	
                        
                                        $class_label = mysql_result($result,0,"class_label"); 
                                        $class_icon = mysql_result($result,0,"icon");
                                        $class_icon = $icon_prefix.$class_icon;
                                    }
                                }
                                
				$this->Set_class_XML($parent_node, $class_label, $class_icon);
			
                            }//end case where class was found
                             
                        
		}//end case where ArchaeoML only is NOT requested
		
	}//end function set XML class
	
	
        public function Alt_class_lookup($item_id, $icon="small"){
        
	    global $space_tab; //name of the space_table
	    global $sp_classes_tab; //name of the class_table
		
	    //sets paraters to get the right sized icon
	    if($icon == "small"){
		$icon_prefix = $this->sm_icon_prefix;
		$icon_term = $sp_classes_tab.".sm_class_icon AS icon ";
	    }
	    else{
		$icon_prefix = $this->large_icon_prefix;
		$icon_term = $sp_classes_tab.".class_icon AS icon ";
	    }
            $query = "SELECT $sp_classes_tab.class_label, 
                    $icon_term
                    FROM $space_tab
                    LEFT JOIN $sp_classes_tab 
                    ON $space_tab.class_uuid = $sp_classes_tab.class_label
                    WHERE $space_tab.uuid = '$item_id'
                    ";
                            
            //echo $query;
            
            $class_label = null;
            $class_icon = null;
            
            $result = mysql_query($query);
            if(!empty($result)){
                $numrows = mysql_numrows($result);
                if($numrows>0){
                    $class_label = mysql_result($result,0,"class_label"); 
                    $class_icon = mysql_result($result,0,"icon");
                    //$class_icon = $icon_prefix.$class_icon;
                }                        
            }
            
            return array("class_label"=>$class_label, "icon" => $class_icon);
        }
        
        
	//this makes XML given a given $class_label and $class_icon
	public function Set_class_XML($parent_node, $class_label, $class_icon){
	
		global $archaeoml_ns_uri; //archaeoML name space
		global $oc_ns_uri; //open context name space URI
		
		$class_icon = str_replace(" ", "%20", $class_icon);
		
		$this->class_xml = $parent_node->addChild("item_class","", $oc_ns_uri);
		$class_xml = $this->class_xml;
		$class_xml->addChild("name", $class_label, $oc_ns_uri);
		$class_xml->addChild("iconURI", $class_icon, $oc_ns_uri);
	
	}
	
}//end space_class class
//-------------------------------------------
//-------------------------------------------




//-------------------------------------------
//-------------------------------------------
class Context {


	var $chrono_ref; //True / false to look up chronological reference
	var $geo_ref; //True / false to look up geo reference
	var $default_tree_only; // True/false to only look at default tree context

	var $context_xml; //Simple XML object for Open Context context xml
	
	var $parent_paths;
	var $path_count;

	var $source_id; //id for the item who's parents are being looked up
	var $geo_array;
	var $chrono_array;	

	function Set_default_context_xml($parent_node, $id){
		$this->Init_context_xml($parent_node);
		$this->do_default_tree_only();
		$this->Make_context_xml($id);
	}

	function Set_full_item_context_xml($parent_node, $id){
		$this->source_id = $id;
		$this->Init_context_xml($parent_node);
		$this->do_chrono();
		$this->do_geo();
		$this->Make_context_xml($id);
	}

	function Init_context_xml($parent_node){
	
		global $oc_ns_uri; //uri to the open context name space
		global $ArchaeoML_only; //if 'true' then do not make non-standard ArchaeoML
		
		$this->chrono_ref = false;
		$this->geo_ref = false;
		$this->default_tree_only = false;
		
		if(!$ArchaeoML_only){
			$this->context_xml = $parent_node->addChild("context","", $oc_ns_uri);
		}//end case where ArchaeoML only is NOT requested
		
	}//end function initializing context XML


	//register request to do chronological lookup
	function do_chrono(){
		global $ArchaeoML_only;//if 'true' then do not make non-standard ArchaeoML
		
		if(!$ArchaeoML_only){
			$this->chrono_ref = true;
		}//end case where ArchaeoML only is NOT requested
	}
	
	
	
	//register request to do geographic lookup
	function do_geo(){
		global $ArchaeoML_only;//if 'true' then do not make non-standard ArchaeoML
		
		if(!$ArchaeoML_only){
			$this->geo_ref = true;
			$this->geo_array = false;
		}//end case where ArchaeoML only is NOT requested
	}
	
	//register request to only do default tree
	function do_default_tree_only(){

		$this->default_tree_only = true;
		
	}
	
	
	
	//this is the main function that 
	//finds parent items
	//then creates appropriate XML
	function Make_context_xml($id){
		global $oc_ns_uri; //uri to the open context name space
		global $ArchaeoML_only; //if 'true' then do not make non-standard ArchaeoML
		
		if(!$ArchaeoML_only){
			
			$this->path_count = 1;
			$this->parent_paths->p_0->path_name = "default";
			$this->item_parents($id);
			$this->make_parent_xml();
			
		}//end case where ArchaeoML only is NOT requested
	}//end function
	
	
	
	
	function item_parents($id, $uplevel=0, $act_path_index=0){
		
		global $oc_ns_uri; //uri to the open context name space
		global $ArchaeoML_only; //if 'true' then do not make non-standard ArchaeoML
		
		global $space_tab; //name of the space_table
		global $sp_contain_tab; //name of the space_containment_table
		global $sp_classes_tab; //name of the space_class_table
    
                
		$do_default_only = $this->default_tree_only;
		
			if($do_default_only){
				$limit_value = " LIMIT 0,1";
			}
			else{
				$limit_value = " LIMIT 0,10";
			}
			
			$tree_cond = " ";
			
			//AND $sp_contain_tab.parent_uuid NOT LIKE '[ROOT]%'
                        
			$query = "SELECT DISTINCT $sp_contain_tab.parent_uuid, $space_tab.space_label,  			
			$sp_contain_tab.project_id, $sp_contain_tab.tree_uuid,
                            $sp_classes_tab.class_label, 
	  		    $sp_classes_tab.sm_class_icon AS icon
			FROM $sp_contain_tab
			LEFT JOIN $space_tab ON $space_tab.uuid = $sp_contain_tab.parent_uuid
                        LEFT JOIN $sp_classes_tab 
				ON $space_tab.class_uuid = $sp_classes_tab.class_uuid
			WHERE $sp_contain_tab.child_uuid = '$id'
			AND $sp_contain_tab.parent_uuid != 'ALLROOT'
			$limit_value
			";

                        $query = imp_cont_query_fix($query);
			//echo $query;

			$result = mysql_query($query);
			$numpars = mysql_numrows($result);
		    
			$i=0;
			while($i<$numpars){
				$par_id = mysql_result($result, $i, "parent_uuid");
				$par_name = mysql_result($result, $i, "space_label");
				$tree_id = mysql_result($result, $i, "tree_uuid");
                                $par_class = mysql_result($result, $i, "class_label");
                                $par_class_icon = mysql_result($result, $i, "icon");
                                
                                if($par_class == null){ //in case you need to join on the class label 
                                    $alt_par_class = new Space_Class();
                                    $alt_class = $alt_par_class->Alt_class_lookup($par_id);
                                    $par_class = $alt_class["class_label"];
                                    $par_class_icon = $alt_class["icon"];
                                }
                                
                                
				$par_name = parseXMLcoding($par_name);
                                $par_class = parseXMLcoding($par_class);
				
                                $par_id = imp_cont_ID_fix($par_id);
                                
                                if(($numpars>1)&&(strlen($par_name)<1)){
                                    break;
                                }
		
				if((strlen($tree_id)<1)||($tree_id == "0")){
					if($i<1){	
						$tree_id = "default";
					}
					else{
						$tree_id = $par_id."_".$id;
					}
				}
				

				$par_level = $uplevel + 1;
				$current_parent = array("parid" => $par_id, 
							"par_name" => $par_name,
							"par_level" => $par_level,
							"par_tree" => $tree_id,
                                                        "class" => $par_class,
                                                        "icon" => $par_class_icon
							);
				
				$parents[$par_level] = $current_parent;
				
				$path_count = $path_count + $i;
				$this->path_count = $path_count;
				
				$path_index = $act_path_index + $i;
				
				$t_pindex = "p_".$path_index; 
				$prev_tpindex = "p_".($path_index-1);
				
				
				if(($uplevel == 0)&&($i<1)){
					//$tree_id = "non_default_test";
					$this->parent_paths->$t_pindex->path_name = $tree_id;
				}
		
				
				
				if($i>0){
					//load parent data from the previous path, before the 
					//split	
					$this->parent_paths->$t_pindex->path_name = $tree_id;
					
					$jj=1;
					while($jj<$par_level){
						$previous_path_data = $this->parent_paths->$prev_tpindex->par_items->$jj;
						$this->parent_paths->$t_pindex->par_items->$jj = $previous_path_data;
					$jj++;
					}
				}

				$this->parent_paths->$t_pindex->par_items->$par_level = $current_parent;
				$this->item_parents($par_id, $par_level, $path_index);
			
			$i++;
			}//loop parent items are found

			if(($numpars == 0)&&($uplevel==0)){
				$this->parent_paths = false;
			}
	}//end function item_get_parent



	function make_parent_xml(){
	
		global $oc_ns_uri; //uri to the open context name space
		global $ArchaeoML_only; //if 'true' then do not make non-standard ArchaeoML
                global $oc_spatial_uri; //href link base
                global $oc_spatial_ext; //href link extension
		
		$path_count = $this->path_count;
		$context_xml = $this->context_xml;
		$parent_paths = $this->parent_paths;
		
		$pp = 0;
		
		$default_found = false;
		
		if(!($parent_paths==false)){
		
			foreach($parent_paths AS $active_path){
			
				$act_path_id = $active_path->path_name;
				
				if($act_path_id == "default"){
					$default_found = true;
					$default_tree_node = $pp;
				}
				
				$tree_node[$pp] = $context_xml->addChild("tree","", $oc_ns_uri);
				$tree_node[$pp]->addAttribute("id", $act_path_id);
	
				$parent_items = $active_path->par_items;
				
				
				//this makes the parent items sortable
				$sortable_array = "";
				$i=0;
				foreach($parent_items AS $sact_parent){
					$sortable_array[$i] = $sact_parent;
				$i++;
				}
				
				//this makes the parent items sorted from top ranking to smaller contexts
				krsort($sortable_array);
				
				$i=0;
				foreach($sortable_array AS $act_parent){	
					//$act_parent = $parent_items[$i];
					$act_parent_node[$i] = $tree_node[$pp]->addChild("parent","", $oc_ns_uri);
				
					$act_parid = $act_parent["parid"];
                                        $act_href = $oc_spatial_uri.$act_parid.$oc_spatial_ext;
					$act_parname = $act_parent["par_name"];
					$act_parlevel = $act_parent["par_level"];
                                        $class = $act_parent["class"];
                                        $icon = $act_parent["icon"];
				
                                
                                        $act_parent_node[$i]->addAttribute("href", $act_href);
					$act_parent_node[$i]->addChild("name",$act_parname, $oc_ns_uri);
					$act_parent_node[$i]->addChild("id",$act_parid, $oc_ns_uri);
					$act_parent_node[$i]->addChild("level",$act_parlevel, $oc_ns_uri);
                                        
                                        $parent_class = new Space_Class;
                                        $icon = $parent_class->sm_icon_prefix.$icon;
					$parent_class->Set_class_XML($act_parent_node[$i], $class, $icon);
					unset($child_class);
				
				$i++;
				}//end loop through parent levels
			
			$pp++;
			}//end loop through paths
		
		
		
			//if no trees where given the id attribute with the value "default",
			//set the first tree to have the id attribute with the value "default"
			if(!$default_found){
				$tree_node[0]["id"] = "default";
				$default_tree_node = 0;
			}
		
		}//end case where there are parents
		
		if(($this->geo_ref)||($this->chrono_ref)){
		
			$act_item = $this->source_id;
			$this->check_geo_ref($act_item, true);
			$this->check_chrono_ref($act_item, true);
			
			//do this if the item itself is not geo-referenced or chrono referenced
			if(  ((!($this->geo_array))||(!($this->chrono_array)))&& (!($this->parent_paths == false)) ){
			
				$tree_node[$default_tree_node]->registerXPathNamespace('oc', $oc_ns_uri);		
				foreach($tree_node[$default_tree_node]->xpath('//oc:parent/oc:id') AS $act_id){
					$this->check_geo_ref($act_id);
					$this->check_chrono_ref($act_id);
				}//end loop through the default tree
				
			}//end case where item itself is not georeferenced
		
		
			//do this if the item itself is not chrono-referenced
			if(!($this->chrono_array)){
				
			}//end case where item itself is not chrono-referenced
		
		}//end case to do geo_ref
		
	}//end function for context_xml_assign




	function check_geo_ref($item_id, $self = false){
		
		global $space_tab; //name of the space_table
		
                
		$query = "SELECT geo_space.latitude,
			geo_space.longitude,
			geo_space.gml_data, 
			$space_tab.space_label
		FROM geo_space
		JOIN $space_tab 
			ON $space_tab.uuid = geo_space.uuid
		WHERE geo_space.uuid = '$item_id'
		LIMIT 1
		";

		$result = mysql_query($query);
		$numcords = mysql_numrows($result);
	
		if($numcords >0){
			$itemlat =  mysql_result($result, 0, "latitude");
			$itemlong =  mysql_result($result, 0, "longitude");
			$itemname = mysql_result($result, 0, "space_label");
			$itemgml = mysql_result($result, 0, "gml_data"); //list of lat-lon coordinates for gml-polygon
			
			$itemname = parseXMLcoding($itemname);
			
			if($self){
				$geo_ref_type = "self";
			}
			else{
				$geo_ref_type = "contained";
			} 
			
			$geo_data = array("lat"=>$itemlat,
				"lon"=>$itemlong,
				"gml"=>$itemgml,
				"name"=>$itemname,
				"id"=>$item_id,
				"ref_type"=>$geo_ref_type);
				
			$this->geo_array = $geo_data;
			
			//echo var_dump($geo_data);
		}//end case where a geo reference is found

	}//end assign_geo_ref function


	function Make_geo_ref_xml($parent_node){
		global $oc_ns_uri; //uri to the open context name space
		global $gml_ns_uri; //uri to GML name space, needed if there is polygon data
		global $oc_spatial_uri; //uri base for spatial items
                global $oc_spatial_ext; //uri base for spatial items
		global $ArchaeoML_only; //if 'true' then do not make non-standard ArchaeoML

		$do_geo_ref = $this->geo_ref;
		$geo_data =	$this->geo_array;

		if((!$ArchaeoML_only)&&($do_geo_ref)&&(!($do_geo_ref == false))){
			
			$itemlat = $geo_data["lat"];
			$itemlong = $geo_data["lon"];
			$itemgml = $geo_data["gml"];
			$itemname = $geo_data["name"];
			$item_id = $geo_data["id"];
			$geo_ref_type = $geo_data["ref_type"];
			
			$geo_ref_xml = $parent_node->addChild("geo_reference", "", $oc_ns_uri);
			$geo_ref_xml->addChild("geo_lat", $itemlat, $oc_ns_uri);
			$geo_ref_xml->addChild("geo_long", $itemlong, $oc_ns_uri);
			$source_xml = $geo_ref_xml->addChild("metasource", "", $oc_ns_uri);
			$source_xml->addAttribute("ref_type", $geo_ref_type);
                        $source_xml->addAttribute("href", $oc_spatial_uri.$item_id.$oc_spatial_ext);
			$source_xml->addChild("source_name", $itemname, $oc_ns_uri);
			$source_xml->addChild("source_id", $item_id, $oc_ns_uri);
			
			$itemgml = $itemgml."";
			
			//if((strlen($itemgml)>0)&&($geo_ref_type == "self")){
			if(strlen($itemgml)>0){
				$polygon_xml = $geo_ref_xml->addChild("Polygon", "", $gml_ns_uri);
				$exterior_xml = $polygon_xml->addChild("exterior", "", $gml_ns_uri);
				$lring_xml = $exterior_xml->addChild("LinearRing", "", $gml_ns_uri);
				$lring_xml->addChild("posList", $itemgml, $gml_ns_uri);
			}
			
		}
		
	}//end function 



	function check_chrono_ref($item_id, $self = false){
		
		global $space_tab; //name of the space_table
		
		$query = "SELECT initial_chrono_tag.creator_uuid,
			initial_chrono_tag.label,
			initial_chrono_tag.start_time,
			initial_chrono_tag.end_time,
			initial_chrono_tag.note_id,
			initial_chrono_tag.public,
			initial_chrono_tag.created,
			$space_tab.space_label
		FROM initial_chrono_tag
		JOIN $space_tab 
			ON $space_tab.uuid = initial_chrono_tag.uuid
		WHERE initial_chrono_tag.uuid = '$item_id'
		LIMIT 1
		";

		$result = mysql_query($query);
		$numcords = mysql_numrows($result);
	
		if($numcords >0){
		
			$tagpub =  mysql_result($result, 0, "public");
			$chrono_label =  mysql_result($result, 0, "label");
			
			if($tagpub == 1){
				$tag_status = "public";
			}
			else{
				$tag_status = "private";
			}
			
			
			$start_time =  mysql_result($result, 0, "start_time");
			$end_time =  mysql_result($result, 0, "end_time");
			$itemname = mysql_result($result, 0, "space_label");
			$itemname = parseXMLcoding($itemname);

			if($self){
				$ref_type = "self";
			}
			else{
				$ref_type = "contained";
			} 

			$creator_id =  mysql_result($result, 0, "creator_uuid");
			$note_id =  mysql_result($result, 0, "note_id");
			$chrono_created =  mysql_result($result, 0, "created");
			
			if(strtolower($creator_id)=="oc"){
				$creator_name = "Open Context Editors";
			}
			
			
			$set_created = date("Y-m-d\TH:i:s\Z", strtotime($chrono_created));
			
			$chrono_data = array("duration"=>"true",
				"start"=>$start_time,
				"end"=>$end_time,
				"ref_type"=>$ref_type,
				"name"=>$itemname,
				"id"=>$item_id);
			
			$chrono_set_data[0] = array(
				"creator_id"=>$creator_id,
				"creator_name"=>$creator_name,
				"set_id"=>$note_id,
				"set_label"=>$note_id,
				"created"=>$set_created);
			
			$chrono_tag[0] = array("type"=>"chronological",
				"status"=>$tag_status,
				"name"=>$chrono_label,
				"time"=>$chrono_data,
				"sets"=>$chrono_set_data);
			
			$this->chrono_array = $chrono_tag;
	
			//echo var_dump($geo_data);
		}//end case where a geo reference is found

	}//end assign_geo_ref function



}//end context class
//-------------------------------------------
//-------------------------------------------




//-------------------------------------------
//-------------------------------------------
//class Children extends Space_Class {
class Children {

	var $children_xml; //Simple XML object for Open Context children xml
	
	function Init_children_xml($parent_node){
	
		global $oc_ns_uri; //uri to the open context name space
		global $ArchaeoML_only; //if 'true' then do not make non-standard ArchaeoML
		
		if(!$ArchaeoML_only){
			$this->children_xml = $parent_node->addChild("children","", $oc_ns_uri);
		}//end case where ArchaeoML only is NOT requested
		
	}//end function initializing children XML
	
	
	
	//this function gets children and makes XML
	function item_children($parent_node, $id){
		
		global $oc_ns_uri; //uri to the open context name space
		global $ArchaeoML_only; //if 'true' then do not make non-standard ArchaeoML
		global $space_tab; //name of the space_table
		global $sp_classes_tab; //name of the space_table
		global $sp_contain_tab; //name of the space_containment_table
		global $oc_spatial_uri; //href link base
                global $oc_spatial_ext; //href link extension
		
		if(!$ArchaeoML_only){
		
			
			$query = "SELECT $sp_contain_tab.child_uuid,
                                $sp_contain_tab.tree_uuid, 
				$space_tab.space_label, 
				$sp_classes_tab.class_label, 
	  			$sp_classes_tab.sm_class_icon AS icon
			FROM $sp_contain_tab
			LEFT JOIN $space_tab 
				ON $space_tab.uuid = $sp_contain_tab.child_uuid
			LEFT JOIN $sp_classes_tab 
				ON $space_tab.class_uuid = $sp_classes_tab.class_uuid
			WHERE $sp_contain_tab.parent_uuid = '$id'
			ORDER BY $sp_contain_tab.tree_uuid, 
			$sp_classes_tab.class_label, 
			$space_tab.space_label
			";

                        $query = imp_cont_query_fix($query);
                        //echo $query;
                        
			$result = mysql_query($query);
			$numch = mysql_numrows($result);	
			
			//echo $query." <i>($numch)</i><br/><br/>";
			
			
			if($numch>0){
				$this->Init_children_xml($parent_node);
				$children_node = $this->children_xml;
				//echo var_dump($children_node);
			
				$i=0;
			
				$default_tree_found = false;
				$last_tree = "";
				$tree_count = 0;
			
				while($i < $numch){
					$chid = mysql_result($result, $i, "child_uuid");
					$chname = mysql_result($result, $i, "space_label");
					$class =  mysql_result($result, $i, "class_label");
					$icon =  mysql_result($result, $i, "icon");
					$tree_id = mysql_result($result, $i, "tree_uuid");
			
					$chname = parseXMLcoding($chname);
					$class = parseXMLcoding($class);
			
                                        $childHref = $oc_spatial_uri.$chid.$oc_spatial_ext; //href link extension
                                        
					if((strlen($tree_id)<1)||($tree_id == "0")){
						$tree_id = "default";
					}
			
					if($tree_id == "default"){
						$default_tree_found = true;
					}
				
					if($last_tree != $tree_id){
						$curr_tree = $tree_count;
						$tree_node[$curr_tree]= $children_node->addChild("tree","", $oc_ns_uri);
						$tree_node[$curr_tree]->addAttribute("id", $tree_id);
						$tree_count++;
						$last_tree = $tree_id;
					}
					
					$child_node = $tree_node[$curr_tree]->addChild("child","", $oc_ns_uri);
                                        $child_node->addAttribute("href", $childHref);
					$child_name = $child_node->addChild("name", $chname, $oc_ns_uri);
					$child_name = $child_node->addChild("id", $chid, $oc_ns_uri);
					$child_class = new Space_Class;
					$child_class->Set_class_XML($child_node, $class, $icon);
					unset($child_class);
				
				$i++;
				}//end loop through child items 
			
				//if no trees where given the id attribute with the value "default",
				//set the first tree to have the id attribute with the value "default"
				if(!$default_found){
					$tree_node[0]["id"] = "default";
				}
			
			}//end case where children are found
			
		}//end case where ArchaeoML only is NOT requested
		
	}//end function set item_children
	
	
}//end Children class
//-------------------------------------------
//-------------------------------------------




//-------------------------------------------
//-------------------------------------------
class Metadata{


	var $metadata_xml;
	var $coins_xml;
	
	var $item_type; // type of item (spatialUnit, resource, person, diary)
	
	var $general_metadata_array; //array of general metadata
	var $dc_metadata_array; //array of item dublin corps metadata
	
	var $coins_cite; //text string of coins metadata

	var $view_count; //number of times an item was viewed
	var $last_view; //last time the item was viewed
		
	//constants for requesting different items
	var $space_uri = "http://opencontext.org/subjects/";
	var $media_uri = "http://opencontext.org/media/";
	var $diary_uri = "http://opencontext.org/documents/";
	var $person_uri = "http://opencontext.org/persons/";
        var $project_uri = "http://opencontext.org/projects/";
        var $property_uri = "http://opencontext.org/properties/";

        var $first_name; //person's first name
        var $last_name; //person's last name
        var $combined_name; //person's or project's full name
        
        var $lic_URI; // holds license data

        var $content_counts; // holds array of project item counts
        var $short_des; //short project description
        var $long_des; //long project description

        var $proj_title; //title for projects, character coding handled differently
        var $proj_name; //name for projects, character coding handled differently
        var $projectUUID; //id for a project

        var $internal_doc; //string of internal document data for diary resources

        var $var_des; //variable description
        var $prop_des; //property description
        var $var_type; //variable type
        var $numeric_sum; //holds array of numberic summary information for a property

	function Make_basic_meta_xml($parent_node, $id, $item_type){
		
		$this->item_metadata($id, $item_type);
		$this->Make_top_attributes_xml($parent_node);
		$this->Make_item_name_xml($parent_node);

	}//end function Make_basic_meta_xml


	function Make_dc_meta_xml($parent_node, $dc_contributors){
		
		global $ArchaeoML_only;
		
		if(!empty($this->general_metadata_array)){
			
			$this->Make_project_metadata($parent_node, $dc_contributors);
			
		}//end case where there's metadata, and a 'pure' archaeoml document is NOT requested
	}//end Make_dc_meta_xml

	function Make_top_attributes_xml($parent_node){
		
		$item_meta_array = $this->general_metadata_array;
		
		$project_id = $item_meta_array["project_id"];
		$uuid = $item_meta_array["uuid"];
		
		$parent_node->addAttribute("UUID", $uuid);
		$parent_node->addAttribute("ownedBy", $project_id);

		if($this->item_type == "resource"){
			$media_type = strtolower($this->general_metadata_array["class"]);
			$parent_node->addAttribute("type", $media_type);
		}
                
                $this->projectUUID = $project_id;
	}//end Make_top_attributes_xml function


	function Make_item_name_xml($parent_node){
		global $archaeoml_ns_uri; //URI for archaeoML namespace
	
		$item_meta_array = $this->general_metadata_array;
		$item_name = $item_meta_array["name"];
		
                if(($this->var_type == "alphanumeric")&&(strlen($item_name)>60)){
                    $item_name = "(Long text value)";
                }
                
		$act_name_xml = $parent_node->addChild("name","", $archaeoml_ns_uri);
		$act_name_xml->addChild("string",$item_name, $archaeoml_ns_uri);
	}//end function Make_item_name_xml($parent_node)

        function assign_file_url($ia_uri, $respath, $filename, $full_file = false){
            
            global $oc_media_directory;
            global $aai_media_directory;	
        		
	    if(strlen($ia_uri)>5){
		$output_uri = $ia_uri;
	    }
	    else{
                $resfile = urlencode($filename);
                $resfile = str_replace("+", " ", $resfile);
                if($full_file){
                    $output_uri = $aai_media_directory."/".$respath."/".$resfile;
                }
                else{
                    $output_uri = $oc_media_directory."/".$respath."/".$resfile;
                }
                $output_uri = str_replace("////","/",$output_uri);
		$output_uri = str_replace("///","/",$output_uri);
		$output_uri = str_replace("//","/",$output_uri);
                            // note: without the line below, 'http://' becomes 'http:/' as a result of the above str_replace
                $output_uri = str_replace("http:/", "http://", $output_uri);                                 
            }
	
	    $output_uri = str_replace(" ", "%20", $output_uri);
            
        return $output_uri;
            
        }//end function


	function item_metadata($id, $item_type){
		
		$item_type = strtolower($item_type);
		$this->item_type = $item_type;
		$this->res_meta = false;
                
		$itemquery = "";
		
		if($item_type == "resource"){
			
			global $resource_tab; 
			$this->res_meta = true;
                        
			if($resource_tab == "resource"){
				$label_2_term = " ".$resource_tab.".res_type AS label_2";
			}
			else{
				$label_2_term = " ".$resource_tab.".archml_type AS label_2";
			}
			
			$cite_uri = $this->media_uri;
			$itemquery = "SELECT $resource_tab.res_label AS name,
				$label_2_term,
				projects.project_id, 
				projects.proj_name, 
				projects.noprop_mes,  
				projects.license_id, 
				projects.thumb_root,
                                projects.prev_root,
                                projects.full_root,
				DATE_FORMAT(projects.accession, '%M %e, %Y') as pubdate, 
				DATE_FORMAT(projects.accession, '%Y-%m-%d') as c_pubdate,
				$resource_tab.mime_type,
                                $resource_tab.res_path,
				$resource_tab.res_filename,
                                $resource_tab.ia_thumb,
				$resource_tab.ia_preview,
				$resource_tab.ia_fullfile
				FROM $resource_tab
				LEFT JOIN projects ON $resource_tab.project_id = projects.project_id  
				WHERE $resource_tab.uuid = '$id'
				LIMIT 1
				";
				
			//echo $itemquery;
		}//end case for spatial item
		
		
		if($item_type == "diary"){
			
			global $diary_tab; //name of the diary/narative table 
			
			$cite_uri = $this->diary_uri;
			$itemquery = "SELECT $diary_tab.diary_label AS name,
				'Document' AS label_2,
                                $diary_tab.internal_doc,
				projects.project_id, 
				projects.proj_name, 
				projects.noprop_mes,  
				projects.license_id, 
				projects.thumb_root, 
				DATE_FORMAT(projects.accession, '%M %e, %Y') as pubdate, 
				DATE_FORMAT(projects.accession, '%Y-%m-%d') as c_pubdate
				FROM $diary_tab
				LEFT JOIN projects ON $diary_tab.project_id = projects.project_id  
				WHERE $diary_tab.uuid = '$id'
				LIMIT 1
				";
		}//end case for diary item
		
		if($item_type == "person"){
			
			global $person_tab; //name of the person/organization table 
			
			$cite_uri = $this->person_uri;
			$itemquery = "SELECT $person_tab.combined_name AS name,
				'Participant' AS label_2,
                                $person_tab.last_name,
                                $person_tab.first_name,
				projects.project_id, 
				projects.proj_name, 
				projects.noprop_mes,  
				projects.license_id, 
				projects.thumb_root, 
				DATE_FORMAT(projects.accession, '%M %e, %Y') as pubdate, 
				DATE_FORMAT(projects.accession, '%Y-%m-%d') as c_pubdate
				FROM $person_tab
				LEFT JOIN projects ON $person_tab.project_id = projects.project_id  
				WHERE $person_tab.uuid = '$id'
				LIMIT 1
				";
                        
		}//end case for spatial item
                
                
                if($item_type == "project"){
			 
			$cite_uri = $this->project_uri;
			$itemquery = "SELECT projects.proj_name AS name,
                                projects.short_des AS label_2,
                                projects.long_des,
                                projects.space_cnt,
                                projects.diary_cnt,
                                projects.media_cnt,
				projects.project_id, 
				projects.proj_name, 
				projects.noprop_mes,  
				projects.license_id, 
				projects.thumb_root, 
				DATE_FORMAT(projects.accession, '%M %e, %Y') as pubdate, 
				DATE_FORMAT(projects.accession, '%Y-%m-%d') as c_pubdate
				FROM projects 
				WHERE projects.project_id = '$id'
				LIMIT 1
				";
		}//end case for spatial item
                
                if($item_type == "property"){
			 
			$cite_uri = $this->property_uri;
                        global $property_tab; //name of the property table
                        global $variable_tab; //name of the variable table
                        global $value_tab; //name of the value table
                        
			$itemquery = "SELECT $value_tab.val_text AS name,
                                $variable_tab.var_label AS label_2,
                                $variable_tab.var_des,
                                $variable_tab.var_type,
                                $variable_tab.variable_uuid,
                                $property_tab.prop_des,
                                $property_tab.val_num,
                                $property_tab.val_date,
                                projects.project_id, 
				projects.proj_name, 
				projects.noprop_mes,  
				projects.license_id, 
				projects.thumb_root, 
				DATE_FORMAT(projects.accession, '%M %e, %Y') as pubdate, 
				DATE_FORMAT(projects.accession, '%Y-%m-%d') as c_pubdate
				FROM $property_tab
                                LEFT JOIN projects ON $property_tab.project_id = projects.project_id
                                JOIN $variable_tab ON $variable_tab.variable_uuid = $property_tab.variable_uuid
                                LEFT JOIN $value_tab ON $value_tab.value_uuid = $property_tab.value_uuid
				WHERE $property_tab.property_uuid = '$id'
				LIMIT 1
				";
		}//end case for property item
                
                
		if(($itemquery == "")||($item_type == "spatialunit")){
			
			$item_type = "spatialunit";
			global $space_tab; 
			global $sp_classes_tab; //name of the class_table
			
			$cite_uri = $this->space_uri;
			$itemquery = "SELECT $space_tab.space_label AS name,
				$sp_classes_tab.class_label AS label_2,
				projects.project_id, 
				projects.proj_name, 
				projects.noprop_mes,  
				projects.license_id, 
				projects.thumb_root, 
				DATE_FORMAT(projects.accession, '%M %e, %Y') as pubdate, 
				DATE_FORMAT(projects.accession, '%Y-%m-%d') as c_pubdate
				FROM $space_tab
				LEFT JOIN $sp_classes_tab ON $sp_classes_tab.class_uuid = $space_tab.class_uuid
				LEFT JOIN projects ON $space_tab.project_id = projects.project_id  
				WHERE $space_tab.uuid = '$id'
				LIMIT 1
				";
		}//end case for spatial item
		
                $itemquery = imp_project_query_fix($itemquery);
                $itemquery = imp_pers__pers_query_fix($id, $item_type, $itemquery);
		echo $itemquery;
			
		$itemresult = mysql_query($itemquery);
                var_dump($itemquery);
                var_dump($itemresult);
                //Zend_Debug::dump
                //return;
			
		if(mysql_numrows($itemresult)<1){
			
			$itemquery = "SELECT $space_tab.space_label AS name,
				$sp_classes_tab.class_label AS label_2,
				'0' AS project_id, 
				'Open Context' AS proj_name, 
				'' AS noprop_mes,  
				'' AS license_id, 
				'' AS thumb_root, 
				'January 1, 2008' as pubdate, 
				'2008-01-01' as c_pubdate
				FROM $space_tab
				LEFT JOIN $sp_classes_tab ON $sp_classes_tab.class_uuid = $space_tab.class_uuid  
				WHERE $space_tab.uuid = '$id'
				LIMIT 1
				";
			$itemquery = imp_project_query_fix($itemquery);	
			$itemresult = mysql_query($itemquery);
		}
			
		$iname = mysql_result($itemresult,0,"name");
		$label_2 = mysql_result($itemresult,0,"label_2");
		$iproj = mysql_result($itemresult,0,"proj_name");
		$project_id = mysql_result($itemresult,0,"project_id");
		$pr_lic_id = mysql_result($itemresult,0,"license_id"); //better license id
		$thumbroot = mysql_result($itemresult,0,"thumb_root");
		$pubdate = mysql_result($itemresult,0,"pubdate");
		$c_pubdate = mysql_result($itemresult,0,"c_pubdate"); //coins metadata
		$nodata = mysql_result($itemresult,0,"noprop_mes");
		
		$iname_xml = light_parseXMLcoding($iname);
		//$iname_xml = str_replace("& ","&amp; ", $iname_xml);
		
		$iproj_xml = light_parseXMLcoding($iproj);
		//$iproj_xml = str_replace("& ","&amp; ", $iproj_xml);
		$this->proj_name = $iproj;
                
		$label_2_xml = light_parseXMLcoding($label_2);
		//$label_2_xml = str_replace("& ","&amp; ", $label_2_xml);
		
		$nodata_xml = parseXMLcoding($nodata);
		//$nodata_xml = str_replace("& ","&amp; ", $nodata_xml);
		
		
		$this->view_tracker($id, $item_type, $project_id); //gets a count of views
		$xsl_sheet = $this->xsl_select($id, $project_id, $item_type, $label_2);
			
		$cite_uri = $cite_uri.$id;
		
		$title = $iproj.": ".$iname." (".$label_2.")";
		
		$xml_pubdate = date("Y-m-d\TH:i:s\-07:00", strtotime($c_pubdate));
		
                if($item_type == "diary"){
                    $this->internal_doc = parseXMLcoding(mysql_result($itemresult,0,"internal_doc"));
                }
                
                if($item_type == "person"){
                    $this->first_name = parseXMLcoding(mysql_result($itemresult,0,"first_name"));
                    $this->last_name = parseXMLcoding(mysql_result($itemresult,0,"last_name"));
                    $this->last_name = light_parseXMLcoding(mysql_result($itemresult,0,"last_name"));
                    $this->combined_name = $iname;
                }
                
                if($item_type == "project"){
                    $content_cnt_array = array();
                    $content_cnt_array["space"] = mysql_result($itemresult,0,"space_cnt");
                    $content_cnt_array["diary"] = mysql_result($itemresult,0,"diary_cnt");
                    $content_cnt_array["media"] = mysql_result($itemresult,0,"media_cnt");
                    $this->long_des = parseXMLcoding(clean_note(mysql_result($itemresult,0,"long_des")));
                    $this->short_des = $label_2_xml; //short project description
                    $this->content_counts = $content_cnt_array;
                    $label_2_xml = "Project"; //change this since it'll be stored as a class
                    $title = $iproj.": (Overview)";
                    $this->combined_name = $iname_xml;
                    $this->proj_title = $title;
                }
                
                 if($item_type == "property"){
                    
                    if(strlen($iname)<1){
                        $val_num = mysql_result($itemresult,0,"val_num");
                        $val_date = mysql_result($itemresult,0,"val_date");
    
                        if(strlen($val_date)>0){
                            $iname = $val_date; 
                        }
                        
                        if(strlen($val_num)>0){
                            $iname = $val_num; 
                        }
                        $iname_xml = $iname;
                    }
                    
                    if(strlen($iname)>127){
                        $iname = substr($iname, 0, 127);
                        $iname_xml = light_parseXMLcoding($iname);
                    }
                    //$iname_xml = $label_2_xml.": ".$iname;
                    
                    $this->var_des = parseXMLcoding(clean_note(mysql_result($itemresult,0,"var_des")));
                    $this->prop_des = parseXMLcoding(clean_note(mysql_result($itemresult,0,"prop_des")));
                    $var_id = mysql_result($itemresult,0,"variable_uuid");
                    
                    $title = $iproj.": Overview of the Descriptive Property, '".$label_2.": ".$iname."'";
                    
                    $this->var_type = strtolower(mysql_result($itemresult,0,"var_type"));
                    
                    $this->property_sum($id, $var_id, $this->var_type, "Spatial"); //get spatial uses
                    $this->property_sum($id, $var_id, $this->var_type, "Diary"); //get Diary uses
                    $this->property_sum($id, $var_id, $this->var_type, "Person"); //get Person uses
                    $this->property_sum($id, $var_id, $this->var_type, "Resource"); //get Resource uses
                    $this->property_sum($id, $var_id, $this->var_type, "Project"); //get Resource uses
                 }
                
		if($item_type == "resource"){
			$mime_type = mysql_result($itemresult,0,"mime_type");
                        $res_path = mysql_result($itemresult,0,"res_path"); 
			$filename = mysql_result($itemresult,0,"res_filename");
                        $thumb_root = mysql_result($itemresult,0,"thumb_root");
                        $prev_root = mysql_result($itemresult,0,"prev_root");
                        $full_root = mysql_result($itemresult,0,"full_root");
                        $thumb_uri = mysql_result($itemresult,0,"ia_thumb");
			$preview_uri = mysql_result($itemresult,0,"ia_preview");
			$full_uri = mysql_result($itemresult,0,"ia_fullfile");
		
                        //use the "ia" uri, or make one from filename and path
                        $thumb_uri = $this->assign_file_url($thumb_uri, $thumb_root."/".$res_path, $filename);
                        $preview_uri = $this->assign_file_url($preview_uri, $prev_root."/".$res_path, $filename);
                        $full_uri = $this->assign_file_url($full_uri, $full_root."/".$res_path, $filename, true);
                        
                        if(strlen($mime_type)<2){
                            if($size = getimagesize($full_uri)){
                                $mime_type = $size["mime"];
                                $qmime = addslashes($mime_type);
                            }
                            else{
                                $mime_type = "not found";
                                $qmime = "not found";
                            }
                            
                            $update = "UPDATE $resource_tab
                            SET $resource_tab.mime_type = '".$qmime." '
                            WHERE $resource_tab.uuid = '$id'
			    LIMIT 1
                            ";
                            
                            mysql_query($update);
                        }
                        
                        
			$item_meta_array = array(
			"uuid" => $id,
			"name" => $iname_xml,
			"class" => $label_2_xml,
			"project_name" => $iproj_xml,
			"project_id" => $project_id,
			"license_id" => $pr_lic_id,
			"thumbroot" => $thumbroot,
			"pubdate" => $pubdate,
			"c_pubdate" => $c_pubdate,
			"xml_pubdate" => $xml_pubdate,
			"cite_uri" => $cite_uri,
			"xsl" => $xsl_sheet,
			"nodata" => $nodata_xml,
			"title" => $title,
                        "mime" => $mime_type,
                        "thumbURI" => $thumb_uri,
			"previewURI" => $preview_uri,
			"fullURI" => $full_uri
			);
		
		}
		else{
		
			$item_meta_array = array(
			"uuid" => $id,
			"name" => $iname_xml,
			"class" => $label_2_xml,
			"project_name" => $iproj_xml,
			"project_id" => $project_id,
			"license_id" => $pr_lic_id,
			"thumbroot" => $thumbroot,
			"pubdate" => $pubdate,
			"c_pubdate" => $c_pubdate,
			"xml_pubdate" => $xml_pubdate,
			"cite_uri" => $cite_uri,
			"xsl" => $xsl_sheet,
			"nodata" => $nodata_xml,
			"title" => $title
			);
		}
		
		
		$this->general_metadata_array = $item_meta_array;
		$this->Start_coins();
		
	}//end function item_metadata


        function property_sum($id, $var_id, $var_type, $sub_type = "Spatial"){
        //this function generates summary information for the property
        //it indicates how many unique values a variable has
        //it gets a min, max, and average of numeric values
            
            global $property_tab; //name of the property table
            global $variable_tab; //name of the variable table
            global $val_tab; //name of the variable table
            global $observe_tab; //name of observsation table
            global $value_tab; //name of the value table
            
            $all_property_sum = $this->numeric_sum;
            
            
            //averages and standard deviations:
            if(($var_type == 'decimal')||($var_type == 'integer')){
                $numeric_result = true; 
            }
            else{
                $numeric_result = false; 
            }
            
            //generate bar graph (categories):
            if(($var_type == 'nominal')||($var_type == 'ordinal')||($var_type == 'boolean')){
               $nom_result = true;
            }
            else{
                $nom_result = false;
            }
            
            //# of times property is used in the observation table:
            $query = "SELECT count($observe_tab.property_uuid) AS prop_count,
            $property_tab.property_uuid,
            $property_tab.val_num, $property_tab.val_date
            FROM $property_tab
            JOIN $observe_tab ON ($observe_tab.property_uuid = $property_tab.property_uuid
            AND $observe_tab.subject_type = '$sub_type')
            WHERE $property_tab.variable_uuid = '$var_id'
            GROUP BY $property_tab.property_uuid
            ORDER BY prop_count DESC;
            ";
            
            $nominal_graph = false;
            
            if($nom_result){
                $query = "SELECT count($observe_tab.property_uuid) AS prop_count,
                $property_tab.property_uuid,
                $property_tab.val_num, $property_tab.val_date, $value_tab.val_text
                FROM $property_tab
                JOIN $observe_tab ON ($observe_tab.property_uuid = $property_tab.property_uuid
                AND $observe_tab.subject_type = '$sub_type')
                JOIN $value_tab ON  $value_tab.value_uuid = $property_tab.value_uuid
                WHERE $property_tab.variable_uuid = '$var_id'
                GROUP BY $value_tab.val_text
                ORDER BY prop_count DESC;
                ";
                
                $nominal_graph = array();
            }
            
            
            //echo $query;
            $result = mysql_query($query);
            $num_results = 0;
            if(!empty($result)){
                $num_results = mysql_numrows($result);
            }
            
            $i=0;
            $val_array = array();
            $total_obs = 0;
            $total_sum = 0;
            
            while($i<$num_results){
                
                $prop_value = mysql_result($result,$i,"val_num");    
                $prop_count = mysql_result($result,$i,"prop_count");
                $act_id = mysql_result($result,$i,"property_uuid");
                
                if($nom_result && ($i < 10)){
                    $text_val = parseXMLcoding(mysql_result($result,$i,"val_text"));
                    unset($act_nom_graph);
                    $act_nom_graph = array("text" => $text_val, "count"=> $prop_count);
                    $nominal_graph[$i] = $act_nom_graph;
                }
                
                if($act_id == $id){
                    $freq_rank = $i + 1;
                    $property_value = $prop_value;
                }
                
                if($numeric_result){
                    $total_sum = $total_sum + ($prop_value *  $prop_count);   
                    $val_array[] = $prop_value;
                }
                
                $total_obs = $total_obs + $prop_count;
                
            $i++;    
            }//next result
            
            if(!$numeric_result){
                    
                $all_property_sum[$sub_type] = array( "number" => false,
                                    "freq_rank" => $freq_rank,
                                    "var_total" => $total_obs,
                                    "uniques" => $num_results,
                                    "nomgraph" => $nominal_graph
                                );
                if($num_results>0){
                    $this->numeric_sum = $all_property_sum;
                }
            }
            else{
                
                if($total_obs != 0){
                    $average_val = $total_sum / $total_obs;
                }
                rsort($val_array);
                $i = 0;
                $val_rank = 0;
                foreach($val_array as $act_val){
                    if($act_val == $property_value){
                        $val_rank = $i + 1;
                    }
                $i++; 
                }
                
                $histo_array = false;
                
                //make histogram data if enough results
                if(($num_results>=3)&&($total_obs >= 20)){
                    
                    $num_intervals = round(($total_obs / ($num_results)),0);
                    
                    if($num_intervals > $num_results){
                        $num_intervals = $num_results;
                    }
                    
                    if($num_intervals <= 3){
                        $num_intervals = round($num_results/10,0); 
                        if($num_intervals < 3){
                            $num_intervals = 3;
                        }
                    }
                    
                    if($num_intervals > 15){
                        $num_intervals = 15;
                    }
                    
                    $int_range = (max($val_array) - min($val_array))/$num_intervals;
                    
                    $histo_array = array();
                    
                    //make intervals
                    $i = 0;
                    while($i< $num_intervals){
                        unset($act_interval);
                        
                        if($i == 0){
                            $low_val = min($val_array);
                            $do_min = true;
                        }
                        else{
                            $low_val = $high_val;
                            $do_min = false;
                        }
                        
                        if($i == ($num_intervals -1)){
                            $high_val = max($val_array);
                            $do_max = true;
                        }
                        else{
                            $high_val = $low_val + $int_range;
                            $do_max = false;
                        }
                        
                        $jj = 0;
                        $found_count = 0;
                        while($jj<$num_results){
                        
                            $prop_value = mysql_result($result,$jj,"val_num");    
                            $prop_count = mysql_result($result,$jj,"prop_count");
                        
                            if($do_max){
                                if(($prop_value >= $low_val)&&($prop_value <= $high_val)){
                                    $found_count = $found_count + $prop_count;
                                }
                            }
                            else{
                                if(($prop_value >= $low_val)&&($prop_value < $high_val)){
                                    $found_count = $found_count + $prop_count;
                                }
                            }
                        
                        $jj++;
                        }//end loop
                        
                        if((!$do_max) && (!$do_min)){
                            if(round($low_val, 2) < round($high_val, 2)){
                                $low_val = round($low_val, 2);
                                $high_val = round($high_val, 2);
                            }
                        }
                        
                        if($do_min){
                            if(round($low_val, 2) < round($high_val, 2)){
                                $high_val = round($high_val, 2);
                            }
                        }
                        
                        if($do_max){
                            if(round($low_val, 2) < round($high_val, 2)){
                                $low_val = round($low_val, 2);
                            }
                        }
                        
                        $act_interval = array("low" => $low_val,
                                              "high" => $high_val,
                                              "count" => $found_count);
                        $histo_array[$i] = $act_interval;
                            
                    $i++; 
                    }
                    
                }//end case for making histogram
                
                if(empty($val_array)){
                    $val_array[0] = $property_value;
                }
                
                $all_property_sum[$sub_type] = array( "number" => true,
                                "freq_rank" => $freq_rank,
                                "var_total" => $total_obs,
                                "uniques" => $num_results,
                                "val_rank" => $val_rank,
                                "min" => min($val_array),
                                "max" => max($val_array),
                                "average" => $average_val,
                                "histo" => $histo_array
                            );
               
                if($num_results>0){
                    $this->numeric_sum = $all_property_sum;
                }
            }//end case with numeric variable
            
        }//end property_sum function



	function xsl_select($item_id, $project_id, $item_type, $class){
		
                
                global $do_importer;
                
                if(!$do_importer){
                    $item_type = strtolower($item_type);
    
                    if($item_type == "spatialunit"){
                            $item_type_term = " (xsl_list.xsl_type = '".$item_type."' OR xsl_list.xsl_type = 'spatial') ";
                    }
                    else{
                            $item_type_term = " xsl_list.xsl_type = '".$item_type."' ";
                    }
                    
                    $class = addslashes($class);
                    
                    $query_sp = "SELECT xsl_list.xsl_dir, xsl_list.xsl_filename, xsl_list.desc_children
                    as child_des
                    FROM xsl_linker
                    LEFT JOIN xsl_list ON xsl_linker.xsl_uuid = xsl_list.xsl_uuid
                    WHERE $item_type_term
                    AND (xsl_linker.project_id ='$project_id'
                    AND (xsl_linker.item_uuid = '$item_id' OR xsl_linker.item_uuid = '$class' 
                    OR xsl_linker.item_uuid = '$project_id'))
                    ORDER BY xsl_linker.style_order DESC
                    ";
                    
                    //echo $query_sp."<br/>";
                    $result = mysql_query($query_sp);
                    $numxls = mysql_numrows($result);
                    
                    if($numxls < 1){
                            //get default if no other suggested		
                            $query = "SELECT xsl_list.xsl_dir, xsl_list.xsl_filename
                            FROM xsl_list
                            WHERE xsl_list.project_id = '0'
                            AND $item_type_term
                            LIMIT 1
                            ";
                    
                            //echo "<i>".$query."</i><br/>";
                            $result = mysql_query($query);
                            $numxls = mysql_numrows($result);
                    }//end conditional
                    
                    
                    $xls_dir = mysql_result($result,0,"xsl_dir");
                    $xls_file = mysql_result($result,0,"xsl_filename");
                    
                    $output = $xls_dir."/".$xls_file;
                }
                else{
                    $output = "default/";
                }
                
		return $output;
	
	}//end xsl_select



	function Start_coins(){
		$coins_cite = 'ctx_ver=Z39.88-2004&amp;rft_val_fmt=info%3Aofi%2Ffmt%3Akev%3Amtx%3Adc&amp;rft.type=dataset&amp;';
		
		$item_meta_array = $this->general_metadata_array;
		
		$title  = rawurlencode($item_meta_array["title"]);
		$c_pubdate = rawurlencode($item_meta_array["c_pubdate"]);
		
		$coins_cite .= 'rft.title='.$title.'&amp;rft.date='.$c_pubdate;
		
		$this->coins_cite = $coins_cite; 
	}



	function Make_project_metadata($parent_node, $dc_contributors){
	
		global $oc_ns_uri; // Open Context Namespace
		global $dc_ns_uri; // Dublin Core Namespace
		global $ArchaeoML_only; //if 'true' then do not make non-standard ArchaeoML
                
                $res_meta = $this->res_meta;
		$item_meta_array  = $this->general_metadata_array;
		$coins_cite = $this->coins_cite;
		
		$title  = light_parseXMLcoding($item_meta_array["title"]);
                
		$c_pubdate = parseXMLcoding($item_meta_array["c_pubdate"]);
		
		$act_meta_xml = $parent_node->addChild("metadata","", $oc_ns_uri);
		$act_meta_xml->addChild("title", $title, $dc_ns_uri);
		$act_meta_xml->addChild("date", $c_pubdate, $dc_ns_uri);
		
                if($res_meta){
                    $act_res_meta_xml = $parent_node->addChild("DublinCoreMetadata","");
                    $act_res_meta_xml->addChild("Title", $title);
                    $act_res_meta_xml->addChild("Date", $c_pubdate);
                }
                
		$project_id = $item_meta_array["project_id"];
	
		$query = "SELECT dcmeta_proj.dc_field, dcmeta_proj.dc_value
		FROM dcmeta_proj
		WHERE  dcmeta_proj.project_id = '$project_id'
		ORDER BY dcmeta_proj.dc_field
		";
		
                $query = imp_dcmeta_query_fix($query);
                
		$result = mysql_query($query);
		$nummeta = mysql_numrows($result);
		
		$i=0;
		
		while($i<$nummeta){
		
			$dc_field = mysql_result($result,$i,"dc_field");
			$dc_value = mysql_result($result,$i,"dc_value");

			$dc_coins_value = substr($dc_value, 0, 500);
			$dc_coins_value = rawurlencode($dc_coins_value);
			$dc_value = parseXMLcoding($dc_value);

			$dc_field = strtolower($dc_field);
			$dc_field = str_replace("<dc:", "", $dc_field);
			$dc_field = str_replace(">", "", $dc_field);

			if($dc_field == "creator"){
				$dc_creator = $dc_creator.'&amp;rft.creator='.$dc_coins_value;
			}
	
			if($dc_field == "subject"){
				$dc_subject = $dc_subject.'&amp;rft.subject='.$dc_coins_value;
			}
			
			if($dc_field == "description"){
				$coins_end = $coins_end.'&amp;rft.description='.$dc_coins_value;
			}
		
			if($dc_field == "publisher"){
				$dc_pub = '&amp;rft.publisher='.$dc_coins_value;
				$dc_web = '&amp;rft.source='.$dc_coins_value;
				$coins_end = $coins_end.$dc_pub.$dc_web;
			}
			
			if($dc_field == "format"){
				$dc_format = $dc_format.'&amp;rft.format='.$dc_coins_value;
			}
			
			if($dc_field == "language"){
				$dc_lang = '&amp;rft.language='.$dc_coins_value;
				$coins_end = $coins_end.$dc_lang;
			}
			
			if($dc_field == "relation"){
				$dc_rel = '&amp;rft.relation='.$dc_coins_value;
				$coins_end = $coins_end.$dc_rel;
			}
			
			if($dc_field == "coverage"){
				$dc_cov = $dc_cov.'&amp;rft.coverage='.$dc_coins_value;		
			}
			
			if($dc_field == "rights"){
				$dc_rights = '&amp;rft.rights='.$dc_coins_value;
				$coins_end = $coins_end.$dc_rights;
			}

			$act_meta_xml->addChild($dc_field, $dc_value, $dc_ns_uri);
                        
                        if($res_meta){
                            $act_res_meta_xml->addChild(ucwords($dc_field), $dc_value);
                        }
                        
		$i++;
		}//end loop through metadata



		$dc_contribs_coins = "";
		
		if(count($dc_contributors)>0){	
			foreach($dc_contributors AS $act_contributor){
				$act_contributor_coins = rawurlencode($act_contributor);
				$dc_contribs_coins .= "&amp;rft.contributor=".$act_contributor_coins;
				$dc_value = parseXMLcoding($act_contributor);
				$act_meta_xml->addChild("contributor", $dc_value, $dc_ns_uri);
                                if($res_meta){
                                    $act_res_meta_xml->addChild("Contributor", $dc_value);
                                }
			}
		}

		$project_name = $item_meta_array["project_name"];
		$cite_uri = $item_meta_array["cite_uri"];
		$license_id = $item_meta_array["license_id"];
		$xml_pubdate = $item_meta_array["xml_pubdate"];
		$xsl_sheet = $item_meta_array["xsl"];
		$no_data_note = $item_meta_array["nodata"];
		
		$act_meta_xml->addChild("identifier", $cite_uri, $dc_ns_uri);

		$coins_uri = '&amp;rft_id='.(rawurlencode($cite_uri));
		
		$coins_cite .= $dc_creator;
		$coins_cite .= $dc_contribs_coins;
		$coins_cite .= $dc_subject;
		$coins_cite .= $dc_format;
		$coins_cite .= $dc_cov;
		$coins_cite .= $coins_end;
		$coins_cite .= $coins_uri;
		
		$this->coins_cite = $coins_cite;
		
		$cite_uri = $item_meta_array["cite_uri"];
		
		$XML_project_name = $act_meta_xml->addChild("project_name", $project_name, $oc_ns_uri);
                $XML_project_name->addAttribute("href",(($this->project_uri).($this->projectUUID)));
		$act_meta_xml->addChild("primary_xsl", $xsl_sheet, $oc_ns_uri);
		$act_meta_xml->addChild("pub_date", $xml_pubdate, $oc_ns_uri);
		$act_meta_xml->addChild("coins", $coins_cite, $oc_ns_uri);
		$act_meta_xml->addChild("no_props", $no_data_note, $oc_ns_uri);
		
		$this->make_license_xml($act_meta_xml, $license_id);
		$this->metadata_xml = $act_meta_xml;
                
                if($res_meta){
                    $act_res_meta_xml->addChild("Identifier", $cite_uri);
                    $lic_URI = $this->lic_URI;
                    $res_copy_xml = $parent_node->addChild("copyrightInfo", "");
                    $res_copy_xml->addChild("string", $lic_URI);
                    $mime_type = $item_meta_array["mime"];
                    $thumbURI = $item_meta_array["thumbURI"];
                    $previewURI = $item_meta_array["previewURI"];
                    $fullURI = $item_meta_array["fullURI"];
                    $res_content_xml = $parent_node->addChild("content", "");
                    $res_file_xml = $res_content_xml->addChild("externalFileInfo", "");
                    $res_file_xml->addChild("fileFormat", $mime_type);
                    $res_file_xml->addChild("resourceURI", $fullURI);
                    $res_file_xml->addChild("previewURI", $previewURI);
                    $res_file_xml->addChild("thumbnailURI", $thumbURI);
                }
                
	}//end function project_metadata



	function make_license_xml($parent_node, $license_id){

		global $oc_ns_uri; // Open Context Namespace
		
                $res_meta = $this->res_meta;
                
		//set defaults, we will default to the CC-By license
		$lic_name = "Creative Commons Attribution";
		$lic_vers = "3.0";
		$lic_url = "http://creativecommons.org/licenses/by/3.0/";
		$lic_purl = "http://i.creativecommons.org/l/by/3.0/88x31.png";
		
		
		$querry_lic = "SELECT licenses.license_name, licenses.license_vers, 
		licenses.license_url, licenses.lic_pict_url
		FROM licenses
		WHERE licenses.license_id = $license_id
		LIMIT 1
		";
		
		//print $querry_lic;
		
		$result = mysql_query($querry_lic);
		if($result){
			$numlic = mysql_numrows($result);
		}
		else{
			$numlic = 0;
		}
		
		if($numlic>0){
			$lic_name = mysql_result($result, 0,"license_name");
			$lic_vers = mysql_result($result, 0,"license_vers");
			$lic_url = mysql_result($result, 0,"license_url");
			$lic_purl = mysql_result($result, 0,"lic_pict_url");
			//$lic_rdf = mysql_result($result, 0,"terms_rdf");
		}
		
		$act_lic_xml = $parent_node->addChild("copyright_lic", "", $oc_ns_uri);
		$act_lic_xml->addChild("lic_name", $lic_name, $oc_ns_uri);
		$act_lic_xml->addChild("lic_vers", $lic_vers, $oc_ns_uri);
		$act_lic_xml->addChild("lic_URI", $lic_url, $oc_ns_uri);
		$act_lic_xml->addChild("lic_icon_URI", $lic_purl, $oc_ns_uri);
		
                $this->lic_URI = $lic_url;
                
	}//end make_license_xml function




	function isSpider ( $userAgent ) {
		if ( stristr($userAgent, "Googlebot")    || /* Google */
			 stristr($userAgent, "Slurp")    || /* Inktomi/Y! */
			 stristr($userAgent, "MSNBOT")    || /* MSN */
			 stristr($userAgent, "teoma")    || /* Teoma */
			 stristr($userAgent, "ia_archiver")    || /* Alexa */
			 stristr($userAgent, "Scooter")    || /* Altavista */
			 stristr($userAgent, "Mercator")    || /* Altavista */
			 stristr($userAgent, "FAST")    || /* AllTheWeb */
			 stristr($userAgent, "MantraAgent")    || /* LookSmart */
			 stristr($userAgent, "Lycos")    || /* Lycos */
			 stristr($userAgent, "ZyBorg")    /* WISEnut */
		) return TRUE;
		return FALSE;
	}
	
	//**********************************************************************************
	// THIS CODE TRACKS VIEWS OF THE ITEM                                              *
	//**********************************************************************************	
	function view_tracker($id, $item_type, $iprojid){
		
                global $do_importer;
                
		$webspider = $this->isSpider(getenv("HTTP_USER_AGENT"));
		
		$item_type = ucfirst($item_type);
		
		$timenow = time();
		$readtime = date('F j, Y', $timenow);
		
		$view_track = "SELECT view_tracker.views, 
		DATE_FORMAT( view_tracker.last_viewed, '%M %e, %Y, %l:%i%p') as lastseen
		FROM view_tracker
		WHERE view_tracker.item_uuid = '$id'
		LIMIT 1
		;
		";
		
                if(!$do_importer){
                    $vresult = mysql_query($view_track);
                    $viewrows = mysql_numrows($vresult);
                }
                else{
                    $vresult = null;
                }
                
		if (((empty($vresult))||($viewrows < 1 ) )&& (!$webspider)  )
		{
			$add_vtrack = "INSERT INTO view_tracker (project_id, item_type, item_uuid, views, last_viewed)  
			VALUES ('$iprojid', '$item_type', '$id', 1, FROM_UNIXTIME($timenow))
			;
			";
                        
                        if(!$do_importer){
                            mysql_query($add_vtrack);
                        }
                        
			$lastview = date('Y/m/d H:i:s', $timenow);
			$views = 1;
		}//end case with new view
		else
		{
			$views = mysql_result($vresult,0,"views");
			$lastview = mysql_result($vresult,0,"lastseen");
			
			$views = $views+1;
			
			$update_vtack = "UPDATE view_tracker
			SET views = $views ,
			last_viewed = FROM_UNIXTIME($timenow)
			WHERE view_tracker.item_uuid = '$id'
			AND view_tracker.item_type = '$item_type'
			AND view_tracker.project_id = '$iprojid'
			;
			";
			
			if (!$webspider)
			{
				mysql_query($update_vtack);
			}
		
		}//end case with view that gets updated
		
		
		$this->view_count = $views; //number of times an item was viewed
		$this->last_view = $lastview; //last time the item was viewed
		
	}//end view tracker function
	//**********************************************************************************
	// END OF CODE TRACKS THAT VIEWS OF THE ITEM                                              *
	//**********************************************************************************


	function make_viewcount_xml($parent_node){
		
		global $oc_ns_uri; //uri to the open context name space 
		
		$views = $this->view_count; //number of times an item was viewed
		$last_view = $this->last_view;
		
		$view_count_xml = $parent_node->addChild("item_views", "", $oc_ns_uri);
		$view_count_xml->addChild("count", $views, $oc_ns_uri);
		$view_count_xml->addChild("view_time", $last_view, $oc_ns_uri);

	}
	
}//end class Metadata
//-------------------------------------------
//-------------------------------------------


//-------------------------------------------
//-------------------------------------------
class UserTags {
	
	var $text_tags;
	
	
	function Make_user_tags_xml($parent_node, $chrono_tags, $id){
		
		global $ArchaeoML_only; //if 'true' then do not make non-standard ArchaeoML
		global $oc_ns_uri; //uri to the open context name space
		
		if(!$ArchaeoML_only){
		
			$this->get_text_tags($id);
			$text_tags = $this->text_tags;
			
			if(!empty($text_tags)){
				
				$tags_node = $parent_node->addChild("user_tags", "", $oc_ns_uri);
				$this->make_tag_xml($tags_node, $text_tags);
				
				if(!empty($chrono_tags)){
					$this->make_tag_xml($tags_node, $chrono_tags);
				}
				
			}//end case with text tags
			else{
			
				if(!empty($chrono_tags)){
					$tags_node = $parent_node->addChild("user_tags", "", $oc_ns_uri);
					$this->make_tag_xml($tags_node, $chrono_tags);
				}
				
			}//end case with no text tags
			
			
		}//end case where ArchaeoML only elements is NOT requested
		
	}//end Make_user_tags_xml function
	
	function make_tag_xml($tags_node, $tag_array){
		global $oc_ns_uri; //uri to the open context name space
		global $ArchaeoML_only; //if 'true' then do not make non-standard ArchaeoML
                global $oc_spatial_uri;//uri base for spatial items
                global $oc_spactial_ext; //uri extension for spatial items

		foreach($tag_array AS $act_tag){
		
			$tag_type = $act_tag["type"];
			$tag_status = $act_tag["status"];
			$tag_name = $act_tag["name"];
			
			$act_tag_xml = $tags_node->addChild("tag", "", $oc_ns_uri);
			$act_tag_xml->addAttribute("type", $tag_type);
			$act_tag_xml->addAttribute("status", $tag_status);
			$act_tag_xml->addChild("name", $tag_name, $oc_ns_uri);
			
			if(($tag_type == "chronological")&&(!empty($act_tag["time"]))){
	
				$chrono_data = $act_tag["time"];
	
				$chono_data_xml = $act_tag_xml->addChild("chrono", "", $oc_ns_uri);
				$chono_data_xml->addAttribute("isDuration", $chrono_data["duration"]);
				$chono_data_xml->addChild("time_start", $chrono_data["start"], $oc_ns_uri);
				$chono_data_xml->addChild("time_finish", $chrono_data["end"], $oc_ns_uri);
				$source_xml = $chono_data_xml->addChild("metasource", "", $oc_ns_uri);
				$source_xml->addAttribute("ref_type", $chrono_data["ref_type"]);
                                $source_xml->addAttribute("href", $oc_spatial_uri.$chrono_data["id"].$oc_spactial_ext);
				$source_xml->addChild("source_name", $chrono_data["name"], $oc_ns_uri);
				$source_xml->addChild("source_id", $chrono_data["id"], $oc_ns_uri);
	
			}//end case for chronological tags
			
			foreach($act_tag["sets"] AS $act_set){
			
				$creator_id = $act_set["creator_id"];
				$creator_name = $act_set["creator_name"];
				$note_id = $act_set["set_id"];
				$set_label = $act_set["set_label"];
				$set_created = $act_set["created"];
				
				$act_set_xml = $act_tag_xml->addChild("tag_creator", "", $oc_ns_uri);
				$act_set_xml->addAttribute("id", $creator_id);
				$act_set_xml->addChild("creator_name", $creator_name, $oc_ns_uri);
				$act_set_xml->addChild("set_label", $set_label, $oc_ns_uri);
				$act_set_xml->addChild("set_id", $note_id, $oc_ns_uri);
				$act_set_xml->addChild("set_date", $set_created, $oc_ns_uri);	
			
			}//end loop through tag creator sets
			
		}//end loop through tag array	
					
	}//end function 


	function get_text_tags($id){
	
            global $do_importer;
            
            if(!$do_importer){
		$tagquery ="SELECT DISTINCT user_tags.tag, user_tags.tag_id, user_tag_items.public
		FROM user_tag_items
		LEFT JOIN user_tags ON user_tags.tag_id = user_tag_items.tag_id
		WHERE user_tag_items.uuid = '$id'
		";
	
	
		$tagresult = mysql_query($tagquery);
		$numitags = mysql_numrows($tagresult); 
	
		$j=0;
		while ($j < $numitags) {
			$tag = mysql_result($tagresult,$j,"tag");
			$tagid = mysql_result($tagresult,$j,"tag_id");
			$tagpub = mysql_result($tagresult,$j,"public");
		
			if($tagpub == 1){
				$tag_status = "public";
			}
			else{
				$tag_status = "private";
			}	
			
			$qtag = addslashes($tag);
			
			$q_tag_user_note = "SELECT users.combined_name, 
			user_tag_items.creator_uuid,
			user_tag_items.note_id, 
			user_tag_notes.note_label, 
			user_tag_notes.created
			FROM user_tag_items
			JOIN user_tags ON user_tag_items.tag_id = user_tags.tag_id
			JOIN users ON (user_tag_items.creator_uuid = users.username)
			JOIN user_tag_notes ON user_tag_items.note_id = user_tag_notes.note_id
			WHERE user_tags.tag = '$qtag' AND user_tag_items.uuid = '$id'
			
			ORDER BY user_tag_items.creator_uuid, user_tag_notes.created
			";
		
			$tcn_result = mysql_query($q_tag_user_note);
			$num_tcn = mysql_numrows($tcn_result);
			
			$jj = 0;
			while($jj<$num_tcn){
			
				$note_id =  mysql_result($tcn_result,$jj,"note_id");
				$set_label =  mysql_result($tcn_result,$jj,"note_label");
				$set_created =  mysql_result($tcn_result,$jj,"created");
				$creator_id =  mysql_result($tcn_result,$jj,"creator_uuid");
				$creator_name =  mysql_result($tcn_result,$jj,"combined_name");
				
				if(strlen($creator_name) <2){
					$creator_name = $creator_id;
				}
				
				$set_created = date("Y-m-d\TH:i:s\Z", strtotime($set_created));
				
				$creator_name = parseXMLcoding($creator_name);
				$set_label = parseXMLcoding($set_label);
				
				$tag_set_data[$jj] = array("creator_id"=>$creator_id,
				"creator_name"=>$creator_name,
				"set_id"=>$note_id,
				"set_label"=>$set_label,
				"created"=>$set_created);
				
			$jj++;
			}//end loop through tag creators and notes
			
			$tag = parseXMLcoding($tag);
			$user_tags[$j] = array("type"=>"text",
				"status"=>$tag_status,
				"name"=>$tag,
				"sets"=>$tag_set_data);
			
		$j++;
		}
		
		if($numitags>0){
			$this->text_tags = $user_tags;
		}
                
            }
		
	}//end get_tags function

}//end class UserTags
//-------------------------------------------
//-------------------------------------------



//-------------------------------------------
//-------------------------------------------
//Get's pings for an item
class PingTracBack {



	function make_refs_xml($parent_node, $id, $act_obs=""){
	    global $oc_ns_uri; //URI to open context name space
	    global $links_tab;
	    global $do_importer;
                
            if(!$do_importer){        
                
		$item_type = ucfirst($item_type);
		
	
		//if observation numbers are recognized, include in query term
		if(strlen($act_obs)<1){
			$obsterm = "";
		}
		else{
			$obsterm = "AND $links_tab.origin_obs = $act_obs ";
		}
		
		
		$getpings = "SELECT external_pings.linker_url, external_pings.link_author
		FROM external_pings
		WHERE external_pings.item_uuid = '$id'
		ORDER BY external_pings.linker_date DESC
		";
		
		
		$pingresult = mysql_query($getpings);
		$numpings = mysql_numrows($pingresult);
		
		$i=0;
		
		if($numpings>0){
			$refs_xml = $parent_node->addChild("external_references", "", $oc_ns_uri);
		}
		
		while ($i < $numpings) {
		
			$ping_url = mysql_result($pingresult,$i,"linker_url");
			$ping_name = mysql_result($pingresult,$i,"link_author");
		
			$ping_name = htmlentities($ping_name);
			//$ping_name = parseXMLcoding($ping_name);	
		
			$act_ref_xml = $refs_xml->addChild("reference", "", $oc_ns_uri);
			$act_ref_xml->addChild("name", $ping_name, $oc_ns_uri);
			$act_ref_xml->addChild("ref_URI", $ping_url, $oc_ns_uri);
		
		$i++;
		}
	    
            }
		
	}//end linked_media function




}//end class PingTracBack
//-------------------------------------------
//-------------------------------------------





function simple_xml_add_nodes($simple_xml, $xml_text_to_add, $name_space, $search_element){
    
    $dom = new DOMDocument("1.0", "utf-8");
   
    
    $dom_sxe = dom_import_simplexml($simple_xml);
    $dom_sxe = $dom->importNode($dom_sxe, true);
    $dom->appendChild($dom_sxe);
    
    $add_element = $dom->getElementsByTagNameNS($name_space, $search_element);
    
    $contentFragment = $dom->createDocumentFragment();
    
    $contentFragment->appendXML($xml_text_to_add);
    $add_element->documentElement->appendChild($contentFragment);
	
    $output = simplexml_import_dom($dom);
    
    return $output;
}



