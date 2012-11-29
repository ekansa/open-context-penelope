<?php
class Table_Resource extends Zend_Db_Table_Abstract
{
    protected $_name = 'resource';
    protected $_primary = 'uuid';
    
    /* Fields in resource:
      -----------------------
        project_id
        source_id	  	  	 
        uuid  	 
        res_number	  	 
        res_label	  	  	 
        res_path_source 
        res_filename
        res_path_destination 
        res_archml_type  	 
        mime_type	 
        res_thumb	  	  	 
        res_preview	  	  	 
        res_fullfile	  	  	 
        ia_meta	  	  	 
        ia_thumb	  	  	 
        ia_preview	  	  	 
        ia_fullfile	  	  	 
        last_modified_timestamp
        
    */
}