var progressLevel = 0;

function initTransformPane()
{
    displayWizardHeader("FINALIZE DATA IMPORT");
    showProcessedTables();
    displayState();
    
    if(importer.currentProject.dataTableName == null)
        dijit.byId('transformDataButton').attr("disabled",false);
    else
        dijit.byId('transformDataButton').attr("disabled",true);
    
    var myAjax = new Ajax.Request('/transform/has-table-been-processed',
        {
            method: 'get',
            parameters: {dataTableName: importer.currentProject.dataTableName },
            onComplete: initTransformMessage
        }
    );
    
}

function initTransformMessage(response)
{
    //alert(response.responseText);
    var isTransformed = dojo.fromJson(response.responseText);
    displayTransformMessage(isTransformed);
}

function displayTransformMessage(isTransformed)
{
    //alert(isTransformed);
    isTransformed = false;
    if(!isTransformed)
    {
        dojo.byId('transformConfirm').innerHTML = "<br /><br /><br /><br /><br /><br />" +
                "<div style='padding:20px;text-align: center;'>Click the \"Transform Data\" button to begin processing.</div>";
        dijit.byId('transformDataButton').attr("disabled",false);
    }
    else
    {
        dojo.byId('transformConfirm').innerHTML = "<br /><br /><br /><br /><br /><br />" +
                "<div style='padding:20px;text-align: center;'>The selected table has already been processed.</div>";
        dijit.byId('transformDataButton').attr("disabled",true);
    }
}


function showProcessedTables()
{
    var myAjax = new Ajax.Request('/transform/get-processed-tables',
        {
            method: 'get',
            parameters: {projectUUID: importer.currentProject.uuID },
            onComplete: showProcessedTablesCompleted
        }
    );
}

var gridProcessedTables = null;
function showProcessedTablesCompleted(response)
{
    //alert(response.responseText);
    
    if(gridProcessedTables != null)
    {
        gridProcessedTables.destroy();
        gridProcessedTables = null;
    }
    if(response.responseText == null || response.responseText.length == 0)
    {
        dojo.byId('transformedTablesDiv').innerHTML = "<br /><br /><br /><br /><br /><br />" +
            "<div style='padding:20px;text-align: center;'>No tables have been transformed yet.</div>";
        return;
    }

    //1.  Initialize the data store from the ajax response object:
    dojo.byId("transformedTablesDiv").innerHTML = "";
    //alert(response.responseText);
    var datagridHelper              = dojo.fromJson(response.responseText);
    //alert(datagridHelper);
    var theRecords                  = datagridHelper.dataRecords;
    var dataRecordsLayout           = datagridHelper.layout;
    //alert(dataRecordsLayout);
    //dataRecordsLayout[0].formatter  = dojo.fromJson(dataRecordsLayout[0].formatter); 
    
    var theRecordStore              = new dojo.data.ItemFileWriteStore({data: theRecords, clearOnClose: true});
    //alert(theRecordStore);
    var dataRecordsView             = { rows: [ dataRecordsLayout ] };
    var dataRecordsStruct           = [ dataRecordsView ];
    
    //2. Create a new grid and add it to the page:
    gridProcessedTables = new dojox.grid.DataGrid({
        id: 'gridProcessedTables',
        store: theRecordStore,
        clientSort: true,
        structure: dataRecordsStruct,
        singleClickEdit: false
    }, document.createElement('div'));
    dojo.byId("transformedTablesDiv").appendChild(gridProcessedTables.domNode);
    gridProcessedTables.startup();
    
    //dojo.connect(gridProcessedTables, 'onCellClick',
    //    function(event){ showUndoImportPanel(event); }         
    //);
    
    if(dijit.byId('doUndoImport') != null)
	dijit.byId('doUndoImport').destroyRecursive();
    transformedTablesDiv.innerHTML += "<div style='text-align: center'><button id='doUndoImport'></button></div>";
    
    var btnDoUndoImport = new dijit.form.Button({
        label: "Undo Last Import",
        onClick: function()
        {   var undoImport = function(items, request)
            {
                //for (var i = 0; i < items.length; i++)
                //    updateItem(items[i]);
                var item = items[items.length-1];
                var dataTableToUndo = gridProcessedTables.store.getValues(item, "source_id");
                var myAjax = new Ajax.Request('/transform/undo-last-import',
                    {
                        method: 'get',
                        parameters: {dataTableName: dataTableToUndo },
                        onComplete: undoImportComplete
                    }
                ); 
            }
            var request = gridProcessedTables.store.fetch({onComplete: undoImport});
        }
    }, "doUndoImport");
}

function undoImportComplete(response)
{
    //alert(response.responseText);
    showProcessedTables();
    displayState();
    displayTransformMessage(false);
    if(importer.currentProject.dataTableName == null)
        dijit.byId('transformDataButton').attr("disabled",false);
}

function showUndoImportPanel(evt)
{
    if(evt.cell.name == '&nbsp;') { alert('remove?'); }    
}

function transformData()
{
    disableForm();
    dataTransformProgress.update({ maximum: 100, progress:0 });
    progressMessage.innerHTML = "Processing variables...";
    var myAjax = new Ajax.Request('/transform/transform-variables',
        {
            method: 'get',
            parameters: {dataTableName: importer.currentProject.dataTableName },
            onComplete: transformVariablesCompleted
        }
    );    
}

function transformVariablesCompleted(response)
{
    //alert(response.responseText);
    //return;
    var theArray = dojo.fromJson(response.responseText);
    
    var container = dojo.byId("transformConfirm");
    container.innerHTML = "";
    var insertArray = theArray[0];
    var div = document.createElement("div");
    container.appendChild(div);    
    if(insertArray.length > 0)
    {
        div.innerHTML = "<strong><br />New descriptive variables: </strong><br />";
        for(var i=0; i < insertArray.length; i++)
        {
            if(i > 0)
                div.innerHTML += ", ";    
            div.innerHTML += insertArray[i];
        }
    }
    
    var updateArray = theArray[1];
    if(updateArray.length > 0)
    {
        div.innerHTML += "<br /><strong>Updated descriptive variables: </strong><br />";
        for(var i=0; i < updateArray.length; i++)
        {
            if(i > 0)
                div.innerHTML += ", ";    
            div.innerHTML += updateArray[i];
        }
    }
    progressMessage.innerHTML = "Processing properties and values...";
    div.innerHTML += "<br /><br /><strong>Preparing to Process Properties:</strong>";
    
    //update progress bar:
    dataTransformProgress.update({ progress: 10 });
    progressLevel = 10;
    
    getPropFields(); //this function gets array of property fields
}


//this function gets array of property fields
function getPropFields(){
    var myAjax = new Ajax.Request('/transform/get-prop-fields',
        {
            method: 'get',
            parameters: {dataTableName: importer.currentProject.dataTableName },
            onComplete: transformValsProps
        }
    );
}



//this function loops through property fields, transforming data
//from one property field at a time
var propArray = new Array();
var propIndex = 0;
function transformValsProps(response){
    
    //alert("Prop array:"+response.responseText);
    propArray = dojo.fromJson(response.responseText);
    
    var numberTransActions = propArray.length;
       
    progressLevel = progressLevel +5;
    dataTransformProgress.update({ progress: progressLevel });
    
    var container = dojo.byId("transformConfirm");
    var div = document.createElement("div");
    container.appendChild(div);    
    
    if(numberTransActions >0){
        
        //if there are properties, do the index =0 of the property field array
        progressMessage.innerHTML = "Begin processing values and properties...";
        //alert("Here " +numberTransActions);
        div.innerHTML += "<br /><br /><strong>Processing property field 1 of " + numberTransActions + ":</strong>";
        //alert("Now Do field_"+propArray[0]);
        var myAjax = new Ajax.Request('/transform/transform-values',
            {
                method: 'get',
                parameters: {dataTableName: importer.currentProject.dataTableName,
                            field: propArray[0]
                },
                onComplete: transformValuesCompleted
            }
        );      
    }//end case with properties
    else{
        progressLevel = 50;
        //move on and process spatial units
        progressMessage.innerHTML = "Processing spatial containment relationships...";
        div.innerHTML += "<br /><br /><strong>New Spatial Containment Relationships:</strong>";  
        
        var myAjax = new Ajax.Request('/transform/transform-spatial-relationships',
            {
                method: 'get',
                parameters: {dataTableName: importer.currentProject.dataTableName },
                onComplete: continueTransforming
            }
        );
    
    }//do spatial if no properties
    
}//end function




function transformValuesCompleted(response)
{
    //alert(response.responseText);
    var container = dojo.byId("transformConfirm");
    //container.innerHTML += response.responseText;
    //alert(response.responseText);
    var numberTransActions = propArray.length;
    
    progressLevel = progressLevel + (1)/(numberTransActions+1)*35;
    //alert(progressLevel);
    dataTransformProgress.update({ progress: progressLevel });
    propIndex = propIndex + 1;
    
    var updatedArray = dojo.fromJson(response.responseText);
    
    //var container = dojo.byId("transformConfirm");
    var table = document.createElement("table");
    dojo.attr(table, "class", "tblGeneric");
    dojo.attr(table, "cellSpacing", "0");
    dojo.attr(table, "cellpadding", "2");
    dojo.attr(table, "width", "100%");

    var row = table.insertRow(0);
    dojo.attr(row, "class", "tblGenericRowOdd");
    var cell1 = row.insertCell(0);
    cell1.innerHTML = "<strong>Name</strong>";
    
    var cell2 = row.insertCell(1);
    cell2.innerHTML = "<strong>Values</strong>";
    
    var cell3 = row.insertCell(2);
    cell3.innerHTML = "<strong>Properties</strong>";
    
    for(var i=0; i < updatedArray.length; i++)
    {
        var row = table.insertRow(i+1);
        cell1 = row.insertCell(0);
        cell1.innerHTML = updatedArray[i].property;
        
        cell2 = row.insertCell(1);
        cell2.innerHTML = updatedArray[i].numValues + " values added";
        
        cell3 = row.insertCell(2);
        cell3.innerHTML = updatedArray[i].numProperties + " properties added";  
    }
    
    container.appendChild(table);
    
    var div = document.createElement("div");
    container.appendChild(div);
    progressMessage.innerHTML = "Processing spatial containment relationships...";
    div.innerHTML += "<br /><br /><strong>New Spatial Containment Relationships:</strong>";
    
    if(propIndex < numberTransActions){
        //do the next property 
        progressMessage.innerHTML = "Continue processing values and properties...";
        div.innerHTML += "<br /><br /><strong>Processing property field "+(propIndex+1)+" of "+numberTransActions+":</strong>";
        
        var myAjax = new Ajax.Request('/transform/transform-values',
            {
                method: 'get',
                parameters: {dataTableName: importer.currentProject.dataTableName,
                            field: propArray[propIndex]
                },
                onComplete: transformValuesCompleted
            }
        );
    }
    else{
        //move on and process spatial units
        
        //reset propIndex for when the script is rerun.
        propIndex = 0;
        
        progressMessage.innerHTML = "Processing spatial containment relationships...";
        div.innerHTML += "<br /><br /><strong>New Spatial Containment Relationships:</strong>";
        
        var myAjax = new Ajax.Request('/transform/transform-spatial-relationships',
            {
                method: 'get',
                parameters: {dataTableName: importer.currentProject.dataTableName },
                onComplete: continueTransforming
            }
        );
    }//end case to process spatial units
    
    //alert(progressLevel);
    dataTransformProgress.update({ progress: progressLevel });
    
    iterations = 0;
}

//var iterations = 0;
function continueTransforming(response)
{
    //alert(response.responseText);
    //dojo.byId("transformConfirm").innerHTML += response.responseText + '<hr />';
    //if(iterations > 10)
    //    return;
    //alert(response.responseText);
    //if(response.responseText == "")
    var updatedArray = dojo.fromJson(response.responseText);
    if(updatedArray.length == 0)
    {
        //alert(response.responseText);
        transformSpatialRelationshipsCompleted(response);
        return;
    }
    //transformSpatialRelationshipsCompleted
    progressMessage.innerHTML = updatedArray[0];
    //dataTransformProgress.update({ progress: 65 });
    dojo.byId("transformConfirm").innerHTML += updatedArray[1] + "<br />";

    var myAjax = new Ajax.Request('/transform/continue-transforming-spatial-relationships',
        {
            method: 'get',
            parameters: {batchSize: 25, dataTableName: importer.currentProject.dataTableName },
            onComplete: continueTransforming //continueTransforming
        }
    );
    //++iterations;
}

function transformSpatialRelationshipsCompleted(response)
{
    //alert(response.responseText);
    
    var container = dojo.byId("transformConfirm");
    var div = document.createElement("div");
    container.appendChild(div);
    div.innerHTML = response.responseText; 

    div.innerHTML += "<br /><br /><strong>New Media / Diary Items:</strong>";    
    progressMessage.innerHTML = "Processing media and diary entries...";
    
    dataTransformProgress.update({ progress: 60 });
    
    //send out another query (do people):
    var myAjax = new Ajax.Request('/transform/transform-media-diary',
        {
            method: 'get',
            parameters: {
                dataTableName:  importer.currentProject.dataTableName,
                projectUUID:    importer.currentProject.uuID
            },
            onComplete: transformMediaDiaryCompleted
        }
    );    
}

function transformMediaDiaryCompleted(response)
{
    //alert(response.responseText);
    
    var updatedArray = dojo.fromJson(response.responseText);
    var container = dojo.byId("transformConfirm");
    utils_writeListToContainer(updatedArray, container);
    
    container.innerHTML += "<strong>New People:</strong>";    
    progressMessage.innerHTML = "Processing people...";
    
    dataTransformProgress.update({ progress: 65 });
    
    //send out another query (do people):
    var myAjax = new Ajax.Request('/transform/transform-people',
        {
            method: 'get',
            parameters: {
                dataTableName:  importer.currentProject.dataTableName,
                projectUUID:    importer.currentProject.uuID
            },
            onComplete: transformPeopleCompleted
        }
    );
}

function transformPeopleCompleted(response)
{
    //alert(response.responseText);
    var container = dojo.byId("transformConfirm");
    container.innerHTML += response.responseText;
    //return;
    var updatedArray = dojo.fromJson(response.responseText);
    //var container = dojo.byId("transformConfirm");
    utils_writeListToContainer(updatedArray, container);

    container.innerHTML += "<strong>New Links:</strong>";   
    progressMessage.innerHTML = "Processing links...";
    dataTransformProgress.update({ progress: 80 });
    
    //send out another query (do people):
    var myAjax = new Ajax.Request('/transform/transform-links',
        {
            method: 'get',
            parameters: {
                dataTableName:  importer.currentProject.dataTableName,
                projectUUID:    importer.currentProject.uuID
            },
            onComplete: transformLinksCompleted
        }
    );
}

function transformLinksCompleted(response)
{
    //alert(response.responseText);
    
    var container = dojo.byId("transformConfirm");
    container.innerHTML += response.responseText;
    
    var updatedArray = dojo.fromJson(response.responseText);
    
    utils_writeListToContainer(updatedArray, container);
    
    //var div = document.createElement("div");
    
    container.innerHTML += "<strong>New Observations:</strong>";   
    
    dataTransformProgress.update({ progress: 95 });
    progressMessage.innerHTML = "Processing observations...";
    
    //send out another query (do people):
    var myAjax = new Ajax.Request('/transform/process-observations',
        {
            method: 'get',
            parameters: {
                dataTableName:  importer.currentProject.dataTableName,
                projectUUID:    importer.currentProject.uuID
            },
            onComplete: processObservationsCompleted
        }
    );
    
}

function processObservationsCompleted(response)
{
    //alert(response.responseText);
    var updatedArray = dojo.fromJson(response.responseText);
    
    var container = dojo.byId("transformConfirm");
    //container.innerHTML += response.responseText;
    //return;

    var table = document.createElement("table");
    dojo.attr(table, "class", "tblGeneric");
    dojo.attr(table, "cellSpacing", "0");
    dojo.attr(table, "cellpadding", "2");
    dojo.attr(table, "width", "100%");

    var row = table.insertRow(0);
    dojo.attr(row, "class", "tblGenericRowOdd");
    var cell1 = row.insertCell(0);
    cell1.innerHTML = "<strong>Values</strong>";
    
    var cell2 = row.insertCell(1);
    cell2.innerHTML = "<strong>Property Name</strong>";
    
    var cell3 = row.insertCell(2);
    cell3.innerHTML = "<strong>Object Name</strong>";
    
    for(var i=0; i < updatedArray.length; i++)
    {
        var row = table.insertRow(i+1);
        cell1 = row.insertCell(0);
        cell1.innerHTML = updatedArray[i].numValues + " values added"
        
        cell2 = row.insertCell(1);
        cell2.innerHTML = updatedArray[i].propertyName;
        
        cell3 = row.insertCell(2);
        cell3.innerHTML = updatedArray[i].objectName;
    }
    
    container.appendChild(table);
    

    dataTransformProgress.update({ progress: 100 });
    progressMessage.innerHTML = "Complete";

    importer.dataTableProcessed = true;
    //var selectedItem = gridProjectList.selection.getSelected()[0]; //gets the first item selected
    //var numTablesProcessed = parseInt(gridProjectList.store.getValues(selectedItem, "numTablesProcessed"));
    //gridProjectList.store.setValues(selectedItem, "numTablesProcessed", numTablesProcessed+1);
    
    showProcessedTables();
    enableForm();
    displayState();
}