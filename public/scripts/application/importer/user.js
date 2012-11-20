function initUsersPane()
{
    //return;
    displayWizardHeader("DEFINE OWNERSHIP");
    
    //populate users dropdown:
    var myAjax = new Ajax.Request('/user/get-users-associated-with-project',
        {   method: 'get',
            parameters: { projectUUID:    importer.currentProject.uuID },
            onComplete: showAffiliatedUsers }
    );
   
    
    //populate roles dropdown:
    var myAjax = new Ajax.Request('/user/get-roles',
        { method: 'get', onComplete: showRoles }
    );
    
    
    //populate affiliation dropdown:
    var myAjax = new Ajax.Request('/user/get-orgs',
        { method: 'get', onComplete: refreshAffiliationsDD }
    );
    
    //display assigned users (if any):
    var myAjax = new Ajax.Request('/user/get-data-owner',
        {   method: 'get',
            parameters: {
                dataTableName:  importer.currentProject.dataTableName
            },
            onComplete: displayResponsibilities
        }
    );
}

var ddUsers;
function showAffiliatedUsers(response)
{
    //alert(response.responseText);
    var theRecords      = dojo.fromJson(response.responseText);
    var theRecordStore  = new dojo.data.ItemFileWriteStore({data: theRecords, clearOnClose: true});
    if(ddUsers == null)
    {
        ddUsers     = new dijit.form.FilteringSelect({id: "ddUsers",
                                name: "ddUsers",
                                store: theRecordStore,
                                forceValidOption: true, 
                                searchAttr: "user"},
                            "ddUsers");
    }
    else
    {
        ddUsers.store = theRecordStore;
    }
    ddUsers.attr('value', theRecords.items[0].id);    
}

var ddRoles;
function showRoles(response)
{
    //alert(response.responseText);
    var theRecords      = dojo.fromJson(response.responseText);
    var theRecordStore  = new dojo.data.ItemFileWriteStore({data: theRecords, clearOnClose: true});
    if(ddRoles == null)
    {
        ddRoles     = new dijit.form.FilteringSelect({id: "ddRoles",
                                name: "ddRoles",
                                store: theRecordStore,
                                forceValidOption: true, 
                                searchAttr: "name"},
                            "ddRoles");
    }
    else
    {
        ddRoles.store = theRecordStore;
    }
    ddRoles.attr('value', theRecords.items[0].id);    
}

var ddAffiliations;
function refreshAffiliationsDD(response)
{
    //alert(response.responseText);
    var theRecords      = dojo.fromJson(response.responseText);
    var theRecordStore  = new dojo.data.ItemFileWriteStore({data: theRecords, clearOnClose: true});
    //alert(theRecordStore);
    if(ddAffiliations == null)
    {
        ddAffiliations     = new dijit.form.ComboBox({id: "ddAffiliations",
                                name: "ddAffiliations",
                                store: theRecordStore,
                                searchAttr: "name",
                                disabled: false},
                            "ddAffiliations");
    }
    else
    {
        ddAffiliations.store = theRecordStore;
    }
    ddAffiliations.attr('value', theRecords.items[0].id);    
}

function attachUser()
{
    //alert(dijit.byId('ddUsers').getValue() + " - " + dijit.byId('ddRoles').getValue());
    var myAjax = new Ajax.Request('/user/add-responsibility',
        {   method: 'get',
            parameters: {
                dataTableName:  importer.currentProject.dataTableName,
                projectUUID:    importer.currentProject.uuID,
                personUUID:     dijit.byId('ddUsers').getValue(),
                roleID:         dijit.byId('ddRoles').getValue(),
                linkAll:        dojo.byId('linkAll').checked
            },
            onComplete: confirmDbUpdate
        }
    );
}
function confirmDbUpdate(response)
{ 
    //alert(response.responseText);
    //display assigned users (if any):
    var myAjax = new Ajax.Request('/user/get-data-owner',
        {   method: 'get',
            parameters: {
                dataTableName:  importer.currentProject.dataTableName
            },
            onComplete: displayResponsibilities
        }
    );
    updateNavigation();
}

var gridUserRoleTable
function displayResponsibilities(response)
{
    //alert("displayResponsibilities: " + response.responseText);
    if(gridUserRoleTable != null)
    {
        //alert("destroying");
        gridUserRoleTable.destroy();
        gridUserRoleTable = null;
        //alert("destroyed");
    }

    if(response.responseText == "")
    {
        return;    
    }
    //1.  Initialize the data store from the ajax response object:
    var datagridHelper              = dojo.fromJson(response.responseText);
    var theRecords                  = datagridHelper.dataRecords;
    var dataRecordsLayout           = datagridHelper.layout;
    dataRecordsLayout[0].formatter  = dojo.fromJson(dataRecordsLayout[0].formatter); 
    dataRecordsLayout[3].formatter  = dojo.fromJson(dataRecordsLayout[3].formatter); 
    
    var theRecordStore              = new dojo.data.ItemFileWriteStore({data: theRecords, clearOnClose: true});
    var dataRecordsView             = { rows: [ dataRecordsLayout ] };
    var dataRecordsStruct           = [ dataRecordsView ];
    
    //2. Create a new grid and add it to the page:
    gridUserRoleTable = new dojox.grid.DataGrid({
        id: 'gridUserRoleTable',
        store: theRecordStore,
        clientSort: true,
        //rowSelector: '20px',
        structure: dataRecordsStruct
    }, document.createElement('div'));
    dojo.byId("gridUserRoleTableCell").appendChild(gridUserRoleTable.domNode);
    gridUserRoleTable.startup();
}

function isLinked(value)
{
    if(value == 0)
        return "No:  Linked to descriptions only if no one else indicated.";
    return "Yes: Linked to all descriptions in table.";
}

function deleteResponsibiliy()
{
    return '<a href="javascript:removeResponsibility();">remove</a>';
}

function removeResponsibility()
{
    //alert('removeResponsibility');    
    var items = gridUserRoleTable.selection.getSelected();
    //alert(items[0].id);
    var myAjax = new Ajax.Request('/user/delete-responsibility',
        {   method: 'get',
            parameters: {
                dataTableName:  importer.currentProject.dataTableName,
                personUUID:     dijit.byId('ddUsers').getValue()
            },
            onComplete: confirmDbUpdate
        }
    ); 
}

function addUser()
{
    //alert(dijit.byId('ddAffiliations').value);
    var myAjax = new Ajax.Request('/user/add-new-user',
        {   method: 'get',
            parameters: {
                firstName:      dojo.byId('f_f_name').value,
                lastName:       dojo.byId('f_l_name').value,
                fullName:       dojo.byId('f_combined_name').value,
                middleInit:     dojo.byId('f_m_initial').value,
                initials:       dojo.byId('f_initals').value,
                affiliation:    dijit.byId('ddAffiliations').value,
                email:          dojo.byId('f_email').value,
                projectUUID:    importer.currentProject.uuID
            },
            onComplete: addUserConfirm
        }
    );
}

function addUserConfirm(response)
{
    alert(response.responseText);
    dijit.byId('dialogAddUser').hide();
    initUsersPane();
}


function comp_names()
{
    var fname = dojo.byId('f_f_name').value;
    var minitial = dojo.byId('f_m_initial').value;
    var lname = dojo.byId('f_l_name').value;
    var all_initials = fname.substr(0,1) + minitial + lname.substr(0,1);
    dojo.byId('f_initals').value = all_initials;
    if (minitial.length >0)
    {
            minitial = minitial +".";
    }
    var combined_name = fname + " " + minitial + " " + lname;
    dojo.byId('f_combined_name').value = combined_name;
}