<?php

class App_Constants
{
    
    //Penelope Web Root
    const PEN_WEB_ROOT = 'http://penelope.oc';
    //const PEN_WEB_ROOT = 'http://penelope.opencontext.org';
    
    //Namespace URI Constants:
    const ARCHAEOML_NS_URI_PROJECT  = 'http://ochre.lib.uchicago.edu/schema/Project/Project.xsd';
    const OC_NS_URI_PROJECT         = 'http://about.opencontext.org/schema/project_schema_v1.xsd';
    
    const ARCHAEOML_NS_URI_PERSON   = 'http://ochre.lib.uchicago.edu/schema/Person/Person.xsd';
    const OC_NS_URI_PERSON          = 'http://about.opencontext.org/schema/person_schema_v1.xsd';
    
    const ARCHAEOML_NS_URI_SPACE    = 'http://ochre.lib.uchicago.edu/schema/SpatialUnit/SpatialUnit.xsd';
    const OC_NS_URI_SPACE           = 'http://about.opencontext.org/schema/space_schema_v1.xsd';
    
    const ARCHAEOML_NS_URI_PROPERTY = 'http://ochre.lib.uchicago.edu/schema/Project/Variable.xsd';
    const OC_NS_URI_PROPERTY        = 'http://about.opencontext.org/schema/property_schema_v1.xsd'; 
    
    const ARCHAEOML_NS_URI_RESOURCE = 'http://ochre.lib.uchicago.edu/schema/Resource/Resource.xsd';
    const OC_NS_URI_RESOURCE        = 'http://about.opencontext.org/schema/resource_schema_v1.xsd';
    
    const GML_NS_URI                = 'http://www.opengis.net/gml';
    const DC_NS_URI                 = 'http://purl.org/dc/elements/1.1/';
    
    //Object URI Constants:
    const THUMB_URI         = 'http://www.opencontext.org/database/oc_media/';
    const SPACE_URI         = 'http://opencontext.org/subjects/';
    const PROJECT_URI       = 'http://opencontext.org/project/';
    const PERSONS_URI       = 'http://opencontext.org/persons/';
    const MEDIA_URI         = 'http://opencontext.org/media/';
    const DIARY_URI         = 'http://opencontext.org/documents/';
    const PROPERTY_URI      = 'http://opencontext.org/properties/';
    
    //Object Type Constants:
    const SPATIAL           = 'Locations or Objects';
    const DIARY             = 'Diary / Narrative';
    const MEDIA             = 'Media (various)';
    const PERSON            = 'Person';
    const PROJECT           = 'Project';
    const PROPERTY          = 'Property';
    
    //Variable Types:
    const ALPHANUMERIC      = 'alphanumeric';
    const INTEGER           = 'integer';
    const DECIMAL           = 'decimal';
    const BOOLEAN           = 'boolean';
    const NOMINAL           = 'nominal';
    const ORDINAL           = 'ordinal';
    const CALENDAR          = 'calendar';
    
    //Destination Directory:
    const OUTPUT_DIRECTORY  = 'C:\\Projects\\openContextLive\\public\\xmlFiles';
    
    
    
}