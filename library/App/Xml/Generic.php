<?php

class App_Xml_Generic
{
    public static function parseXMLcoding($string)
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
        return trim($string);     
    }
    
    public static function light_parseXMLcoding($string)
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
        return $string;       
    }
    
    public static function recursiveInArray($needle, $haystack)
    {
        if(count($haystack)>0)
        { 
            foreach ($haystack as $stalk)
            {
                if ($needle === $stalk || (is_array($stalk) && App_Xml_Generic::recursiveInArray($needle, $stalk)))
                {
                    return true;
                }
            }
        }
        return false;
    }
    
    public static function generateFileName($objectID, $objType)
    {
        //get rid of spaces, forward slashes, and other special characters:
        $objType = str_replace(' ', '', $objType);
        $objType = str_replace('/', '', $objType);
        
        //return a cleaned up file name:
        return $objType . '_' . $objectID . '.xml'; 
    }
    
    public static function escapeSpacesAndSlashes($str)
    {
        //get rid of spaces, forward slashes, and other special characters:
        $str = str_replace(' ', '', $str);
        $str = str_replace('/', '', $str);
        return $str;
    }
}

