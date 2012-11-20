function showAddDataImage(value)
{
    return "<a href='#'>" + value + "</a>";
}
function deleteRecordButton()
{
    return "<a href='javascript:showDeleteDialog();'>delete</a>";
}

function reviewProcessedTablesButton(value)
{
    if(value > 0)
        return value +  '&nbsp;<a href="javascript:getPage(TRANSFORM_PAGE_ID)">modify</a>';
    else
        return value;
}

/*function editRecordButton(value)
{

    if(value)
        return "<a href='javascript:editData();'>edit project</a>";
    else
        return "<a href='javascript:addData();'>upload data</a>**"; //<br />**data still needed";
}*/

/*function editRecordButton(value)
{

    if(value)
    {
        var optButtons = "<a href='javascript:editData();'>Edit project</a><br/><a href='javascript:editProject();'>Describe Project</a><br/>";
	optButtons += "<a href='javascript:tabMod();'>Modify last import</a>";
	return optButtons;
    }
    else{
        var optButtons = "<a href='javascript:addData();'>Upload data</a><br/><a href='javascript:editProject();'>Describe Project</a><br/>";
	optButtons += "<a href='javascript:tabMod();'>Modify last import</a>";
	return optButtons;
    }
}*/

function projectStatus(value)
{
    if(value)
        return "";
    else
        return "no unprocessed tables"; //<br />**data still needed";
}

//-------------------------------------------------------------------------------------------


function selectProjectInGrid()
{
    //select the appropriate item:
    var makeSelection = function(items, request)
    {
        for (var i = 0; i < items.length; i++)
            if(items[i].uuID == importer.currentProject.uuID)
            {                
                gridProjectList.selection.setSelected(items[i], true);
                return;
            }
    }
    var request = gridProjectList.store.fetch({onComplete: makeSelection});
}

function setActiveProject()
{
    var selectedItem                            = gridProjectList.selection.getSelected()[0]; //gets the first item selected
    //alert(gridProjectList.store.getValues(selectedItem, "hasDataRecords"));
    
    importer.currentProject.id              = gridProjectList.store.getValues(selectedItem, "id");
    importer.currentProject.uuID            = gridProjectList.store.getValues(selectedItem, "uuID");
    importer.currentProject.name            = gridProjectList.store.getValues(selectedItem, "projectName");
    importer.currentProject.shortDesc       = gridProjectList.store.getValues(selectedItem, "shortDesc");
    importer.currentProject.longDesc        = gridProjectList.store.getValues(selectedItem, "longDesc");
    importer.currentProject.hasDataRecords  = gridProjectList.store.getValues(selectedItem, "hasDataRecords");
    importer.currentProject.licenseID       = gridProjectList.store.getValues(selectedItem, "licenseID");//not sure why the "[0]" is necessary.
    importer.currentProject.dataTableName   = gridProjectList.store.getValues(selectedItem, "dataTableName");
    importer.currentProject.dataTableDesc   = gridProjectList.store.getValues(selectedItem, "dataTableDesc");
    importer.currentProject.fileName        = gridProjectList.store.getValues(selectedItem, "fileName");
    importer.currentProject.numRows         = gridProjectList.store.getValues(selectedItem, "numRows");
    importer.currentProject.numCols         = gridProjectList.store.getValues(selectedItem, "numCols");
    displayState();
}

function clearActiveProject()
{
    importer.currentProject.id                  = -1;
    importer.currentProject.uuID                = null;
    importer.currentProject.name                = "None Selected";  
    importer.currentProject.shortDesc           = null;
    importer.currentProject.longDesc            = null;
    importer.currentProject.hasDataRecords      = false;
    importer.currentProject.dataTableName       = null;
    importer.currentProject.dataTableDesc       = null;
    importer.currentProject.fileName            = null;
    importer.currentProject.licenseID           = 0;
    importer.currentProject.numRows             = 0;
    importer.currentProject.numCols             = 0;
    
    dojo.byId('spanProjectSelected').innerHTML  = importer.currentProject.name;
    gridProjectList.selection.clear();
    
    displayState();
}

function editData()
{
    //setActiveProject();
    //getPage(1);
    /*setActiveProject();
    mainTabContainer.forward();
    initDataUploadTab();*/
}

function addData()
{
    editData();
}

function addNewProject()
{
    //alert("add project");
    //return;
    var pName = $('projectName').value;
    $('projectName').value = "";
    var myAjax = new Ajax.Request('/project/add-project',
        {method: 'get', parameters: {projectName: pName },
        onComplete: projectConfirm }
    );
}
function projectConfirm(response)
{
    //document.getElementById("errorMessage").innerHTML = response.responseText;
    //return;
    //alert(response.responseText);
    var newItem = theDataStore.newItem(dojo.fromJson(response.responseText));
    gridProjectList.selection.clear();
    gridProjectList.selection.setSelected(newItem, true);
    setActiveProject();    
}

function showDeleteDialog()
{
    setActiveProject();
    var items = gridProjectList.selection.getSelected();
    if(items.length)
    {
        //var selectedItem = gridProjectList.selection.getSelected()[0]; //gets the first item selected
        //var projectName = gridProjectList.store.getValues(selectedItem, "projectName");
        dojo.byId('removalText').innerHTML = "Are you sure you want to remove <strong><em>" +
            importer.currentProject.name +
            "</em></strong> and it's corresponding data from the system?";
        dijit.byId('dialogDelete').show();   
    }
    else
    {
        alert("No selection could be found.");
    }            
}

function removeProject()
{
    //var selectedItem = gridProjectList.selection.getSelected()[0];
    //var id = gridProjectList.store.getValues(selectedItem, "id");
    var myAjax = new Ajax.Request('/project/remove-project',
        {method: 'get', parameters: {projectID: importer.currentProject.id },
        onComplete: removeProjectConfirm }
    );
}

function removeProjectConfirm(response)
{
    var selectedItem = gridProjectList.selection.getSelected()[0];
    theDataStore.deleteItem(selectedItem);
    clearActiveProject();
    
}



function getUser()
{
    var myAjax = new Ajax.Request('/utils/get-user',
        {method: 'get', onComplete: displayUser}
    );
}

function displayUser(response)
{
    //alert(response.responseText);
    var user = dojo.fromJson(response.responseText);
    //alert(user);
    $('userDiv').innerHTML = "First Name: " + user.firstName;
    $('userDiv').innerHTML += "<ul>";
    alert(user.projects.values);
    for(project in user.projects)
    {
        $('userDiv').innerHTML += "<li>" + user.projects[project].projectName + "</li>";
    }
    $('userDiv').innerHTML += "</ul>";            
}


