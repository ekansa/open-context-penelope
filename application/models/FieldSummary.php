<?php
class FieldSummary
{
    public $id;
    public $tableName;
    public $fieldName;
    public $fieldLabel;
    public $fieldLabelComments;
    public $fieldType;
    public $linkNumber;
    public $linkMethod;
    public $linkType;
    public $linkField;
    public $obsGroup;
    public $propertyType;
    public $propertyDescription;
    public $varID;
    public $classID;
    public $fieldNotes;
    public $fieldKeywords;
    public $geoType;
    
    public function init()
    {
        $db = Zend_Registry::get('db');
        $sql = "SET collation_connection = utf8_unicode_ci;";
		  $db->query($sql, 2);
		  $sql = "SET NAMES utf8;";
		  $db->query($sql, 2);
        $sql = "
        UPDATE field_summary
        SET field_type = ''
        WHERE field_type IS NULL;
        
        UPDATE field_summary
        SET prop_type = ''
        WHERE prop_type IS NULL;
        
        ";
        $db->query($sql, 2);
        
    }
    
    function FieldSummary($_data) //where $_data is a "ResultRowObject"
    {
        $this->id                   = $_data->id;
        $this->tableName            = $_data->source_id;
        $this->fieldName            = $_data->field_name;
        $this->fieldLabel           = $_data->field_label;
        $this->fieldType            = $_data->field_type;                
        $this->fieldLabelComments   = $_data->field_lab_com;              
        $this->linkNumber           = $_data->linked_num;         
        $this->linkMethod           = $_data->link_meth;                
        $this->linkType             = $_data->link_type;                 
        $this->linkField            = $_data->link_t_field;               
        $this->obsGroup             = $_data->obs_group;             
        $this->propertyType         = $_data->prop_type;               
        $this->propertyDescription  = $_data->prop_desc;           
        $this->varID                = $_data->variable_uuid;             
        $this->classID              = $_data->class_uuid;                
        $this->fieldNotes           = $_data->field_notes;               
        $this->fieldKeywords        = $_data->field_keywords;                
        $this->geoType              = $_data->geo_type; 
    }
    
}