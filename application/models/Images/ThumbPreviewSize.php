<?php

/*
 This searches a directory for images and then makes good quality thumb and preview size versions
 
*/

class Images_ThumbPreviewSize {
 
 
	 const thumbWidth = 120;
	 const previewWidth = 480;
	 
	 
	 function makeDirectoryPreviews($directory){
		  
		  $fileArray = $this->directoryToArray($directory, true);
		  foreach($fileArray as $filename){
				
				$picture = false;
				if(stristr($filename, ".jpg")||stristr($filename, ".tif")){
					 $picture = true;
				}
				
				if($picture){
					 $destinationFile = $filename;
					 $this->resizeSaveImage($filename, $destinationFile, self::previewWidth);
				}
		  }
		  
		  return $fileArray;
	 }
	 
	 function makeDirectoryThumbs($directory){
		  
		  $fileArray = $this->directoryToArray($directory, true);
		  foreach($fileArray as $filename){
				
				$picture = false;
				if(stristr($filename, ".jpg")||stristr($filename, ".tif")){
					 $picture = true;
				}
				
				if($picture){
					 $destinationFile = $filename;
					 $this->resizeSaveImage($filename, $destinationFile, self::thumbWidth);
				}
		  }
		  
		  return $fileArray;
	 }
	 
	 function fullfileSaveImage($filename, $destinationFile){
		  $output = false;
		  @$source = imagecreatefromjpeg($filename);
		  if($source){
			   $output = true;
			   imagejpeg($source, $destinationFile, 100 );		
			   //memory clean up.
			   imagedestroy($source);
		  }
		  return $output;
	 }
	 
	 
	 //save a preview size file
	 function savePreviewImage($filename, $destinationFile){
		  $this->resizeSaveImage($filename, $destinationFile, self::previewWidth);
	 }
	 
	 //save a thumbnail size file
	 function saveThumbnailImage($filename, $destinationFile){
		  $this->resizeSaveImage($filename, $destinationFile, self::thumbWidth);
	 }
	 
	 
	 //assuming the file really exists, this copies the file resized based on width
	 function resizeSaveImage($filename, $destinationFile, $newWidth){
		  
		  list($width, $height) = getimagesize($filename);
		  if($width > $newWidth){
				$percent =  $newWidth / $width;
				$newHeight = $height * $percent;
				
				// Load
				$resizedImage = imagecreatetruecolor($newWidth, $newHeight);
				$source = imagecreatefromjpeg($filename);
				
				// Resize
				imagecopyresized($resizedImage, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
				
				// Output
				imagejpeg($resizedImage, $destinationFile, 100 );
				
				//memory clean up.
				imagedestroy($source);
				imagedestroy($resizedImage);
		  }
	 }
	 
	 
 
 
	 //make an array of files in a directory
	 function directoryToArray($directory, $recursive = true) {
		  $array_items = array();
		  if ($handle = opendir($directory)) {
			  while (false !== ($file = readdir($handle))) {
				  if ($file != "." && $file != "..") {
					  if (is_dir($directory. "/" . $file)) {
						  if($recursive) {
							  $array_items = array_merge($array_items, $this->directoryToArray($directory. "/" . $file, $recursive));
						  }
						  $file = $directory . "/" . $file;
						  $array_items[] = preg_replace("/\/\//si", "/", $file);
					  } else {
						  $file = $directory . "/" . $file;
						  $array_items[] = preg_replace("/\/\//si", "/", $file);
					  }
				  }
			  }
			  closedir($handle);
		  }
		  return $array_items;
	 }


}//end class

?>
