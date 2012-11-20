var dataTableStore;
function initDataUploadTab()
{
    //alert("initDataUploadTab");
    displayWizardHeader("VIEW UPLOADED DATA");
    
    //hide form:
    dojo.byId('ifrPhoto').style.visibility = "hidden";
    dojo.byId('ifrPhoto').style.height = "1px";
    
    //show datagrid:
    dojo.byId('editProjectContainer').style.visibility = "visible";
    dojo.byId('editProjectContainer').style.height = "300px";

    //get data table list:
    var myAjax = new Ajax.Request('/datatable/get-data-table-list',
        {method: 'get', parameters: {projectID: importer.currentProject.id },
        onComplete: initDataTableDD }
    );
}

var ddDataTable;
function initDataTableDD(response)
{
    //alert(response.responseText);
    var theRecords      = dojo.fromJson(response.responseText);
    dataTableStore      = new dojo.data.ItemFileWriteStore({data: theRecords, clearOnClose: true});
    if(theRecords.items.length == 0)
    {
        showUploadForm();
        return;
    }
    if(ddDataTable == null)
    {
        ddDataTable     = new dijit.form.FilteringSelect({id: "ddDataTable",
                                name: "ddDataTable",
                                store: dataTableStore,
                                forceValidOption: true,
                                searchAttr: "description"},
                            "ddDataTable");
        //add onchange event after selection is initialized.
        ddDataTable.attr('onChange', retrieveDataTable); 
    }
    else
    {
        ddDataTable.store = dataTableStore;      
    }
    
    if(importer.currentProject.dataTableName != null && importer.currentProject.dataTableName.length > 0)
        ddDataTable.attr('value', importer.currentProject.dataTableName);
    else
        ddDataTable.attr('value', theRecords.items[0].source_id);
    /*else
    {
        theRecords.items[0].unshift('Please select a table');
        ddDataTable.attr('text', 'Please select a table');
    }
    return;*/
    //else
    //    ddDataTable.attr('value', theRecords.items[0].source_id);
    
    
    //if data tables exist, retrieve them.  Otherwise, show the "upload table" dialog:
    //if there is data to display, load the datagrid:
    //alert(importer.currentProject.hasDataRecords);
    //alert(theRecords.items);
    if(theRecords.items.length > 0)
    {
        //alert(ddDataTable.attr('value'));
        if(ddDataTable.attr('value').length > 0)
            retrieveDataTable();   
    }
    else //otherwise, show the upload form:
    {
        showUploadForm();   
    }   
}

function showUploadForm()
{
    dojo.byId('editProjectContainer').style.overflow = "hidden";
    dojo.byId('editProjectContainer').style.visibility = "hidden";
    dojo.byId('editProjectContainer').style.height = "1px";
    
    dojo.byId('ifrPhoto').style.visibility = "visible";
    dojo.byId('ifrPhoto').style.height = "350px";
    dojo.byId('ifrPhoto').src = "/uploaddata?projectID=" + importer.currentProject.id + "&projectUUID=" + importer.currentProject.uuID;   
}

function doDataTableUpdate(items, request)
{
    var tableName = ddDataTable.attr('value');
    //alert(items.length);
    for(i=0; i < items.length; i++)
    {
        if(items[i].source_id == tableName)
        {
            importer.currentProject.hasDataRecords  = true;
            importer.currentProject.dataTableName   = items[i].source_id;
            importer.currentProject.dataTableDesc   = items[i].description;
            importer.currentProject.fileName        = items[i].filename;
            importer.currentProject.numRows         = items[i].numrows;
            importer.currentProject.numCols         = items[i].numcols;
            importer.currentProject.licenseID       = items[i].fk_license;
            importer.dataTableProcessed		    = false;
            displayState();
        }
    } 
   
}

function updateActiveDataTable()
{
     dataTableStore.fetch({
        onComplete: doDataTableUpdate 
    });    
}

function retrieveDataTable()
{
    //set the active data table:
    //importer.currentProject.dataTableName = ddDataTable.attr('value');
    updateActiveDataTable();
    displayWizardHeader("VIEW UPLOADED DATA");
    
    //hide form:
    dojo.byId('ifrPhoto').style.visibility = "hidden";
    dojo.byId('ifrPhoto').style.height = "1px";
    
    //show datagrid:
    dojo.byId('editProjectContainer').style.visibility = "visible";
    dojo.byId('editProjectContainer').style.height = "90%";
    
    
    
    //make ajax call:
    dojo.byId('ifrPhoto').style.height = "1px";
    dojo.byId('ifrPhoto').style.visibility = "hidden";
    dojo.byId("editDataGridContainer").innerHTML = "<div style='text-align:center;'>loading data...</div>";
    var myAjax = new Ajax.Request('/datatable/display-data',
        {method: 'get', parameters: {dataTableName: importer.currentProject.dataTableName },
        onComplete: showData }
    );   
}

function showData(response)
{
    //alert(response.responseText);
    
    var datagridHelper      = dojo.fromJson(response.responseText);
    var theRecords          = datagridHelper.dataRecords;
    var dataRecordsLayout   = datagridHelper.layout;
    var theRecordStore      = new dojo.data.ItemFileWriteStore({data: theRecords, clearOnClose: true});
    var dataRecordsView     = { rows: [ dataRecordsLayout ] };
    var dataRecordsStruct   = [ dataRecordsView ];
    
    if(dijit.byId('gridData') != null)
        dijit.byId('gridData').destroy();

    // create a new grid:
    var gridData = new dojox.grid.DataGrid({
        id: 'gridData',
        store: theRecordStore,
        clientSort: true,
        rowSelector: '20px',
        structure: dataRecordsStruct
    }, document.createElement('div'));
    
    dojo.byId("editDataGridContainer").innerHTML = "";
    dojo.byId("editDataGridContainer").appendChild(gridData.domNode);
    // Call startup, in order to render the grid:
    gridData.startup();
  
    updateNavigation();
}

function dataSuccessfullyUploaded(tableName)
{
    //alert(tableName);
    importer.currentProject.dataTableName = tableName;
    //alert(tableName);
    //alert("dataSuccessfullyUploaded()");
    /*var selectedItem = gridProjectList.selection.getSelected()[0]; //gets the first item selected
    gridProjectList.store.setValues(selectedItem, "hasDataRecords", "true");
    gridProjectList.store.setValues(selectedItem, "dataTableName", tableName);
    var numTables = parseInt(gridProjectList.store.getValues(selectedItem, "numTables"));
    gridProjectList.store.setValues(selectedItem, "numTables", numTables+1);*/
    //setActiveProject();
    initDataUploadTab();
}
 