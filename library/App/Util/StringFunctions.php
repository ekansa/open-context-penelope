<?php
class StringFunctions
{

    static function lastIndexOf($string,$item)    
    {    
        $index= strpos($string, $item);    
        if ($index)    
        {    
            $index=strlen($string) - strlen($item)-$index;    
            return $index;
        }
        return -1;
    }

}