<?php
class GenericFunctions
{
    /** This function was derived from Eric Kansa's
     * gen_uuid() function.
     */
    public static function generateUUID()    
    {
        $rawid = strtoupper(md5(uniqid(rand(), true)));
	$workid = $rawid;
	$byte = hexdec( substr($workid,12,2) );
	$byte = $byte & hexdec("0f");
	$byte = $byte | hexdec("40");
	$workid = substr_replace($workid, strtoupper(dechex($byte)), 12, 2);
    
	// build a human readable version
	$rid = substr($rawid, 0, 8).'-'
        .substr($rawid, 8, 4).'-'
        .substr($rawid,12, 4).'-'
        .substr($rawid,16, 4).'-'
        .substr($rawid,20,12);
            
            
            // build a human readable version
            $wid = substr($workid, 0, 8).'-'
        .substr($workid, 8, 4).'-'
        .substr($workid,12, 4).'-'
        .substr($workid,16, 4).'-'
        .substr($workid,20,12);
         
        return $wid;   
    }    
}
