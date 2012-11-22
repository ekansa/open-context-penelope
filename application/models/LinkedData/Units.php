<?php

class LinkedData_Units  {
             
    public $allUnits = array("version" => 1,
                       "standards" => array(
                        
								array(
                                "sType" => "Count",
                                "units" => array(
                                    array("name" => "counting measure", "abrv" => "count",
                                          "uri" => "http://www.freebase.com/view/en/counting_measure",
                                          "reqType" => "integer")
									)
								),
                        
                                array(
                                "sType" => "Mass",
                                "units" => array(
                                    array("name" => "milligram", "abrv" => "mg",
                                          "uri" => "http://www.freebase.com/view/m/01x32f_",
                                          "reqType" => "float"),
                                    array("name" => "gram", "abrv" => "g",
                                          "uri" => "http://www.freebase.com/view/en/gram",
                                          "reqType" => "float"),
                                    array("name" => "kilogram", "abrv" => "kg",
                                          "uri" =>"http://www.freebase.com/view/en/kilogram",
                                          "reqType" => "float")
                                    )
                                ),
                                
                                array(
                                "sType" => "Length",
                                "units" => array(
                                     array("name" => "micrometer / micron", "abrv" => "µm",
                                          "uri" => "http://www.freebase.com/view/en/micrometer",
                                          "reqType" => "float"),
                                    array("name" => "millimeter", "abrv" => "mm",
                                          "uri" => "http://www.freebase.com/view/en/millimeter",
                                          "reqType" => "float"),
                                    array("name" => "centimeter", "abrv" => "cm",
                                          "uri" =>"http://www.freebase.com/view/en/centimeter",
                                          "reqType" => "float"),
                                    array("name" => "meter", "abrv" => "m",
                                          "uri" => "http://www.freebase.com/view/en/meter",
                                          "reqType" => "float"),
                                    array("name" => "kilometer", "abrv" => "km",
                                          "uri" => "http://www.freebase.com/view/en/kilometer",
                                          "reqType" => "float")
                                    )
                                ),
                                
                                array(
                                "sType" => "Area",
                                "units" => array(
                                    array("name" => "square meter", "abrv" => "m<sup>2</sup>",
                                          "uri" => "http://www.freebase.com/view/en/square_meter",
                                          "reqType" => "float"),
                                    array("name" => "hectare", "abrv" => "ha",
                                          "uri" =>"http://www.freebase.com/view/en/hectare",
                                          "reqType" => "float"),
                                    array("name" => "square kilometer", "abrv" => "km<sup>2</sup>",
                                          "uri" =>"http://www.freebase.com/view/en/square_kilometer",
                                          "reqType" => "float")
                                    )
                                ),
                                
                                array(
                                "sType" => "Volume",
                                "units" => array(
                                    array("name" => "milliliter", "abrv" => "mL",
                                          "uri" => "http://www.freebase.com/view/en/milliliter",
                                          "reqType" => "float"),
                                    array("name" => "liter", "abrv" => "L",
                                          "uri" =>"http://www.freebase.com/view/en/liter",
                                          "reqType" => "float"),
                                    array("name" => "cubic metre", "abrv" => "m<sup>3</sup>",
                                          "uri" =>"http://www.freebase.com/view/en/cubic_metre",
                                          "reqType" => "float")
                                    )
                                ),
                                
                                
								array(
                                "sType" => "Density",
                                "units" => array(
                                    array("name" => "number density (count / liter)", "abrv" => "count per liter",
                                          "uri" => "http://www.freebase.com/view/en/number_density",
                                          "reqType" => "float"),
									array("name" => "density (grams per liter)", "abrv" => "grams per liter",
                                          "uri" => "http://sw.opencyc.org/2008/06/10/concept/Mx4rHs7hMuxiQdaeRI29oZztbw",
                                          "reqType" => "float"),
                                    array("name" => "density (grams per cubic centimeter)", "abrv" => "g / cm<sup>3</sup>",
                                          "uri" =>"http://www.freebase.com/view/en/gram_per_cubic_centimeter",
                                          "reqType" => "float"),
                                    array("name" => "density (kilograms per cubic meter)", "abrv" => "kg / m<sup>3</sup>",
                                          "uri" =>"http://www.freebase.com/view/en/kilogram_per_cubic_metre",
                                          "reqType" => "float")
                                    )
                                ),
								
								
                                array(
                                "sType" => "Geospatial",
                                "units" => array(
                                    array("name" => "latitude (WGS84)", "abrv" => "lat",
                                          "uri" => "http://www.w3.org/2003/01/geo/wgs84_pos#lat",
                                          "reqType" => "float"),
                                    array("name" => "longitude (WGS84)", "abrv" => "lon",
                                          "uri" =>"http://www.w3.org/2003/01/geo/wgs84_pos#long",
                                          "reqType" => "float"),
                                    array("name" => "altitude (WGS84)", "abrv" => "alt (m)",
                                          "uri" =>"http://www.w3.org/2003/01/geo/wgs84_pos#alt",
                                          "reqType" => "float")
                                    )
                                ),
                                
                                
                                array(
                                "sType" => "Chronology",
                                "units" => array(
                                    array("name" => "calendar year", "abrv" => "year",
                                          "uri" => "http://www.freebase.com/view/en/calendar_year",
                                          "reqType" => "integer"),
                                    array("name" => "calendar date", "abrv" => "date",
                                          "uri" =>"http://www.freebase.com/view/en/calendar_date",
                                          "reqType" => "calendar")
                                    )
                                )
                            )
                       );
	
    
	public function get_unit_from_abrev($abrv){
		
		$allUnits = $this->allUnits;
		$unitData = false;

		foreach($allUnits["standards"] as $standardArray){
			//echo "<br/><br/><br/>".print_r($standardArray);
			foreach($standardArray["units"] as $unit){
				if($abrv == $unit["abrv"] || $abrv == $unit["name"] ){
					$unitData = $unit;
				}
			}
		}
		
		return $unitData;
	}
    
    

}  
