//important:
//  refer to the page numbers by their variables so if the order changes,
//  it's not a hassle to re-number pages.
var PROJECT_PAGE_ID       = 0;
var METADATA_PAGE_ID      = 1;
var DATATABLE_PAGE_ID     = 2;
var LICENSE_PAGE_ID       = 3;
var USER_PAGE_ID          = 4;
var CLASSIFY_PAGE_ID      = 5;
var ANNOTATE_PAGE_ID      = 6;
var RELATIONSHIPS_PAGE_ID = 7;
var PROPERTY_PAGE_ID      = 8;
var TRANSFORM_PAGE_ID     = 9;
var EDIT_PAGE_ID          = 10;
var XMLFEED_PAGE_ID       = 11;

function tabContainerChanged()
{
    //only execute if the selected tab has changed:
    if(importer.currentTabID == mainTabContainer.selectedChildWidget.id)
        return;
    initializeTab(mainTabContainer.selectedChildWidget.id)
}

function getPage(pageID)
{
    if(importer.currentProject.uuID == -1)
    {
        alert("Please select a project");
        return;
    }
    var url;
    
    //append query parameters:
    var params = "";
    if(importer.currentProject.uuID != -1)
        params += '/projectUUID/' + importer.currentProject.uuID;
    if(importer.currentProject.dataTableName != null && importer.currentProject.dataTableName != "")
        params += '/dataTableName/' + importer.currentProject.dataTableName;
    switch(pageID)
    {
        case PROJECT_PAGE_ID:
            url = '/project/index' + params;
            break;
        case METADATA_PAGE_ID:
            url = '/metadata/index' + params;
            break;
        case DATATABLE_PAGE_ID:                 
            url = '/datatable/index' + params;
            break;
        case LICENSE_PAGE_ID:                 
            url = '/license/index' + params;
            break;
        case USER_PAGE_ID:                 
            url = '/user/index' + params;
            break;
        case CLASSIFY_PAGE_ID:                 
            url = '/classify/index' + params;
            break;
        case ANNOTATE_PAGE_ID:                 
            url = '/annotate/index' + params;
            break;
        case RELATIONSHIPS_PAGE_ID:                 
            url = '/relationships/index' + params;
            break;
        case PROPERTY_PAGE_ID:                 
            url = '/property/index' + params;
            break;
        case TRANSFORM_PAGE_ID:                 
            url = '/transform/index' + params;
            break;
        case EDIT_PAGE_ID:                 
            url = '/edit/index' + params;
            break;
        case XMLFEED_PAGE_ID:                 
            url = '/xmlfeed/index' + params;
            break;
    }
    //alert(url)
    top.location.href = importer.rootURI + url;
}

function advance(currentPageID)
{
    //make sure that modified data is saved if user navigates away from page:
    if(currentPageID == CLASSIFY_PAGE_ID)
        saveClassifyData();
    if(currentPageID == ANNOTATE_PAGE_ID)
        saveClassData();
        
    //go to next page:
    ++currentPageID;
    getPage(currentPageID);
}

function previous(currentPageID)
{
    //make sure that modified data is saved if user navigates away from page:
    if(currentPageID == CLASSIFY_PAGE_ID)
        saveClassifyData();
    if(currentPageID == ANNOTATE_PAGE_ID)
        saveClassData();
        
    //go to previous page:
    --currentPageID;
    getPage(currentPageID);
}


function displayWizardHeader(headerText)
{
    //alert(headerText);
    /*var titleSection    = dojo.byId(mainTabContainer.selectedChildWidget.id + "_headerText");
    var projectSection  = dojo.byId(mainTabContainer.selectedChildWidget.id + "_project");
    var helpSection     = dojo.byId(mainTabContainer.selectedChildWidget.id + "_help");*/
    
    var titleSection    = dojo.byId("headerText");
    var projectSection  = dojo.byId("projectNum");
    var helpSection     = dojo.byId("helpCell"); 
        
    //1.  set project data:
    var table = getProjectTable();
    projectSection.innerHTML = ""; //todo:  does this cause a memory leak?
    projectSection.appendChild(table);
    
    //return if other sections of the header have already been populated:
    if(titleSection.innerHTML.length > 0)
        return;
    
    //2.  set page title:
    titleSection.innerHTML = headerText;

    //add help image:
    var img = document.createElement("img");
    img.src = "/public/images/help_get.jpg";  
    helpSection.appendChild(img);
    //return;
    
    //based on the selected tab, update the help accordingly:
    var helpText;
    var helpMenuContainer;
    //alert(importer.currentPageID);
    switch(importer.currentPageID)
    {
        case PROJECT_PAGE_ID:
            dojo.connect(img, 'onclick',
                function(event)
                {
                    //alert('event fired!');
                    helpText =  "text text text text text text text text text text text text text text text text text text text text text text text text text.";
                    helpMenuContainer = createHelpMenuContainer("HELP:  Creating or Selecting a Project");
                    helpMenuContainer.setContent(helpText);
                }
            );
            break;
        case METADATA_PAGE_ID:
            dojo.connect(img, 'onclick',
                function(event)
                {
                    //alert('event fired!');
                    helpText =  "text text text text text text text text text text text text text text text text text text text text text text text text text.";
                    helpMenuContainer = createHelpMenuContainer("HELP:  Describing the Project");
                    helpMenuContainer.setContent(helpText);
                }
            );
            break;
        case DATATABLE_PAGE_ID:
            dojo.connect(img, 'onclick',
                function(event)
                {
                    //alert('event fired!');
                    helpText =  "text text text text text text text text text text text text text text text text text text text text text text text text text.";
                    helpMenuContainer = createHelpMenuContainer("HELP:  Creating or Selecting a Dataset");
                    helpMenuContainer.setContent(helpText);
                }
            );
            break;
        case LICENSE_PAGE_ID:
            dojo.connect(img, 'onclick',
                function(event)
                {
                    helpText =  "Open Context requires all content it contains to carry a <a href='http://creativecommons.org/' target='_blank'>" + 
                                "Creative Commons</a> license. These copyright licenses allow legal sharing and" + 
                                "re-use of content in Open Context and beyond. All Creative Commons licenses" + 
                                "require attribution. Future copies and works based on your content must" + 
                                "clearly attribute and cite you for your contribution. Please select a license" + 
                                "for your uploaded file. (The license will also apply to media identified in this table.";
                    helpMenuContainer = createHelpMenuContainer("HELP:  Selecting a License");
                    helpMenuContainer.setContent(helpText);
                }
            );
            break;
        case USER_PAGE_ID:
            dojo.connect(img, 'onclick',
                function(event)
                {
                    helpText = "text text text text text text text text text text text text text text text text text text text text text text";
                    helpMenuContainer = createHelpMenuContainer("HELP:  Selecting a Data Owner");
                    helpMenuContainer.setContent(helpText);
                }
            );
            break;
        case CLASSIFY_PAGE_ID:
            dojo.connect(img, 'onclick', displayHelpMenu);
            break;
        case ANNOTATE_PAGE_ID:
            dojo.connect(img, 'onclick',
                function(event)
                {
                    helpText = "Icons, simple classifications, and additional annotations help make it " +
                    "easier for others to browse and use your data. Select a field in the area below and " +
                    "choose the best fitting icon and classification. You also have additional options. " +
                    "Clear labeling of items makes your data easier for others to navigate and use. " +
                    "You may also type a note or keywords to further describe items in a field.";
                    helpMenuContainer = createHelpMenuContainer("HELP:  Annotating Your Data");
                    helpMenuContainer.setContent(helpText);
                }
            );
            break;
        case RELATIONSHIPS_PAGE_ID:
            dojo.connect(img, 'onclick',
                function(event)
                {
                    helpText = "text text text text text text text text text text text text text text text text text text text text text text";
                    helpMenuContainer = createHelpMenuContainer("HELP:  Linking Your Data and Establishing Relationships");
                    helpMenuContainer.setContent(helpText);
                }
            );
            break;
        case PROPERTY_PAGE_ID:
            dojo.connect(img, 'onclick',
                function(event)
                {
                    helpText = "Properties describe locations and objects, people, or media resources. Assign your property fields (shown in the Step 1 table) to the appropriate field (shown in Step 2 table) that these properties describe.";
                    helpMenuContainer = createHelpMenuContainer("HELP:  Assigning Properties");
                    helpMenuContainer.setContent(helpText);
                }
            );
            break;
        case TRANSFORM_PAGE_ID:
            dojo.connect(img, 'onclick',
                function(event)
                {
                    helpText = "text text text text text text text text text text text text text text text text text text text text text text text text text text text text text text text text text text text text text text ";
                    helpMenuContainer = createHelpMenuContainer("HELP:  Finalizing the Data Import");
                    helpMenuContainer.setContent(helpText);
                }
            );
            break;
        case EDIT_PAGE_ID:
            dojo.connect(img, 'onclick',
                function(event)
                {
                    helpText = "text text text text text text text text text text text text text text text text text text text text text text text text text text text text text text text text text text text text text text ";
                    helpMenuContainer = createHelpMenuContainer("HELP:  Reviewing / Editing Imported Data");
                    helpMenuContainer.setContent(helpText);
                }
            );
        case XMLFEED_PAGE_ID:
            dojo.connect(img, 'onclick',
                function(event)
                {
                    helpText = "text text text text text text text text text text text text text text text text text text text text text text text text text text text text text text text text text text text text text text ";
                    helpMenuContainer = createHelpMenuContainer("HELP:  Generating XML Documents");
                    helpMenuContainer.setContent(helpText);
                }
            );
            break; 
    }
}

function wizardInitData()
{
    initDataUploadTab();
}

function wizardInitLicense()
{
    /*if(importer.dataTableProcessed)
    {
        mainTabContainer.selectChild(mainTabContainer.selectedChildWidget.id);
        return;
    }*/
    if(importer.currentProject.id == -1)
    {
        mainTabContainer.selectChild(step1Pane);                           
        importer.currentTabID = "step1Pane";
    }
    else
    {
        displayWizardHeader("SELECT / UPDATE LICENSE");
        initLicense();
    }
}

function wizardInitDataOwner()
{
    if(importer.currentProject.id == -1)
    {
        mainTabContainer.selectChild(step1Pane);                           
        importer.currentTabID = "step1Pane";
    }
    else
    {
        displayWizardHeader("SELECT DATA OWNER");
        initUsersPane();
    }
}

function wizardInitClassify()
{
    if(importer.currentProject.id == -1)
    {
        mainTabContainer.selectChild(step1Pane);                           
        importer.currentTabID = "step1Pane";
    }
    else
    {
        initFieldSummaryPane();
    }
}

function wizardInitAnnotate()
{
    if(importer.currentProject.id == -1)
    {
        mainTabContainer.selectChild(step1Pane);                           
        importer.currentTabID = "step1Pane";
    }
    else
    {
        initAnnotateObjectsPane();
    }
}


function wizardInitProperties()
{
    if(importer.currentProject.id == -1)
    {
        mainTabContainer.selectChild(step1Pane);                           
        importer.currentTabID = "step1Pane";
    }
    else
    {
        //initRelationshipsPane();
        initPropertiesPane();
    }      
}

function wizardInitTransform()
{
    if(importer.currentProject.id == -1)
    {
        mainTabContainer.selectChild(step1Pane);                           
        importer.currentTabID = "step1Pane";
    }
    else
    {
        initTransformPane();
    }      
}

function wizardInitEdit()
{
    if(importer.currentProject.id == -1)
    {
        mainTabContainer.selectChild(step1Pane);                           
        importer.currentTabID = "step1Pane";
    }
    else
    {
        initEditPane();
    }      
}



function displayState()
{
    dojo.byId('spanUserName').innerHTML = importer.userName;
    dojo.byId('spanProjectSelected').innerHTML = importer.currentProject.name; // + " - " + importer.currentProject.dataTableName;
    
    //update project table:
    var projectSection  = dojo.byId("projectNum");
    var table = getProjectTable();
    
    projectSection.innerHTML = ""; //todo:  does this cause a memory leak?
    //alert("here "+projectSection.innerHTML);
    projectSection.appendChild(table);
    updateNavigation();
}

