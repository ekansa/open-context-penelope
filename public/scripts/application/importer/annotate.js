var classPane;
var classPaneX;
var classsPaneY;
var clickText = "<em>click to edit...</em>";
var sampleDataRows;
var notesCellName       = "Field Notes (Editable)";
var itemLabelCellName   = "Item Label (Editable)";

function initAnnotateObjectsPane()
{
    
    displayWizardHeader("ANNOTATING YOUR DATA");
    var myAjax = new Ajax.Request('/annotate/get-field-summary-object-datastore',
        { method: 'get', parameters: {dataTableName: importer.currentProject.dataTableName },
        onComplete: showAnnotationGrid }
    );
    
}

var gridObjectAnnotation;
function showAnnotationGrid(response)
{
    //alert(response.responseText);
    if(gridObjectAnnotation != null)
        gridObjectAnnotation.destroy();
        
    //1.  Initialize the data store from the ajax response object:
    var datagridHelper      = dojo.fromJson(response.responseText);
    var theRecords          = datagridHelper.dataRecords;
    var dataRecordsLayout   = datagridHelper.layout;
    
    dataRecordsLayout[2].formatter  = dojo.fromJson(dataRecordsLayout[2].formatter); //a hack:  ensure that the "edit type" is being evaluated as a type rather than a string!
    //dataRecordsLayout[4].type       = dojo.fromJson(dataRecordsLayout[4].type);
    var theRecordStore              = new dojo.data.ItemFileWriteStore({data: theRecords, clearOnClose: true});
    var dataRecordsView             = { rows: [ dataRecordsLayout ] };
    var dataRecordsStruct           = [ dataRecordsView ];

    //2. Create a new grid and add it to the page:
    gridObjectAnnotation = new dojox.grid.DataGrid({
        id: 'gridObjectAnnotation',
        store: theRecordStore,
        clientSort: true,
        //rowSelector: '20px',
        structure: dataRecordsStruct,
        singleClickEdit: true
    }, document.createElement('div'));
    dojo.byId("fieldSummaryObjectGridContainer").appendChild(gridObjectAnnotation.domNode);
    gridObjectAnnotation.startup();

    //3.  Add event handlers:
    /*dojo.connect(gridObjectAnnotation, 'onRowDblClick',
        function(event){ gridObjectAnnotation_editNotesCell(event); }         
    );*/
    dojo.connect(gridObjectAnnotation, 'onRowClick',
        function(event){ gridObjectAnnotation_editNotesCell(event); }         
    ); 
    dojo.connect(gridObjectAnnotation, 'onApplyCellEdit',
        function(event){ gridObjectAnnotation_applyEdit(event); }         
    );
    
    //4.  Now query for sample data:
    var myAjax = new Ajax.Request('/annotate/get-sample-data',
        { method: 'get', parameters: {dataTableName: importer.currentProject.dataTableName },
        onComplete: showSampleData }
    );
}

function showSampleData(response)
{
    //alert(response.responseText);
    sampleDataRows = dojo.fromJson(response.responseText);
    updateDataGrid();
}

function gridObjectAnnotation_editNotesCell(evt)
{
    //alert("Editing...");
    if(evt.cell == null)
        return;
    var item = gridObjectAnnotation.selection.getSelected()[0];
    var isFieldNotes = evt.cell.name == notesCellName;
    var isFieldLabel = evt.cell.name == itemLabelCellName;
    if(isFieldNotes)
    {    
        if(gridObjectAnnotation.store.getValues(item, "field_notes") == clickText)
            gridObjectAnnotation.store.setValues(item, "field_notes", "");
    }
    else if(isFieldLabel)
    {
        if(gridObjectAnnotation.store.getValues(item, "field_lab_com") == clickText)
            gridObjectAnnotation.store.setValues(item, "field_lab_com", gridObjectAnnotation.store.getValues(item, "field_label"));   
    }
}

function gridObjectAnnotation_applyEdit(editText)
{
    var item = gridObjectAnnotation.selection.getSelected()[0];
    updateItem(item);
}

function formatClassColumn(value)
{
    var id = (new Date()).getTime();
    var linkID = "classLink_" +  id;
    if(value == null || value.length == 0)
    {
        return "<a id='" +  linkID + "' href='#' onclick='showClassLookup(\"" + linkID + "\");'>Select Image</a>";
    }
    else
    {
        var img = "<a id='" +  linkID + "' href='#' onclick='showClassLookup(\"" + linkID + "\");'>";
        img += "<img src='/public/images/classes/" + value + "' border='0' />";
        img += "</a>";
        return img;
    }
}

function updateDataGrid()
{
    //iterate through and initialized values in the datagrid:
    var modifyGrid = function(items, request)
    {
        for (var i = 0; i < items.length; i++)
            updateItem(items[i]);
    }
    var request = gridObjectAnnotation.store.fetch({onComplete: modifyGrid});
}

function updateItem(item)
{
    if(gridObjectAnnotation.store.getValues(item, "field_notes") == null ||
       gridObjectAnnotation.store.getValues(item, "field_notes")[0] == null ||
       gridObjectAnnotation.store.getValues(item, "field_notes")[0].length == 0)
    {
        gridObjectAnnotation.store.setValues(item, "field_notes", clickText);
    }
     
    if(gridObjectAnnotation.store.getValues(item, "field_lab_com") == null ||
       gridObjectAnnotation.store.getValues(item, "field_lab_com")[0] == null ||
       gridObjectAnnotation.store.getValues(item, "field_lab_com")[0].length == 0)
    {
        gridObjectAnnotation.store.setValues(item, "field_lab_com", clickText);
    }
    
    //itearate through the samples data structure and populate the appropriate datagrid cell:
    for(var j=0; j < sampleDataRows.length; j++)
    {
        var row = sampleDataRows[j];
        //alert(theRecordStore.getValues(item, "field_label") + " - " + row.field_label);
        if(gridObjectAnnotation.store.getValues(item, "field_label") == row.field_label)
        {
            //create the sample string:
            var sampleString = "";
            if(row.samples.length == 0)
            {
                sampleString = "No sample data available"; 
            }
            else
            {
                sampleString += "<table><tr><td valign='top'>";
                //alert(gridObjectAnnotation.store.getValues(item, "class_icon")[0].length);
                if(gridObjectAnnotation.store.getValues(item, "class_icon")[0].length > 1) //only display if class is defined.
                {
                    sampleString += "<strong>" + gridObjectAnnotation.store.getValues(item, "class_label") + "</strong><br />";
                    sampleString += "<img src='/public/images/classes/" + gridObjectAnnotation.store.getValues(item, "class_icon") + "' />";
                }
                else
                {
                    sampleString += "<em>Select a Class</em>";
                }
                sampleString += "</td><td valign='top'>";
                sampleString += "<ol style='margin-top:0px; margin-bottom: 0px;'>";
                //alert(row.samples.length);
                for(var k=0; k < row.samples.length; k++)
                {
                    sampleString += "<li>";
                    var prefix = gridObjectAnnotation.store.getValues(item, "field_lab_com");
                    if(prefix != clickText)
                        sampleString += prefix + " "; 
                    if(row.samples[k] != null && row.samples[k] != "" )
                        sampleString += row.samples[k];
                    else
                        sampleString += "(no data found)"
                    sampleString += "</li>";
                }
                sampleString += "</ol>";
                sampleString += "</td></tr></table>";
            }
             
            gridObjectAnnotation.store.setValues(item, "examples", sampleString);
            break;
        }
    }
}


function showClassLookup(linkID)
{
    //alert(linkID);
    var pos = dojo.coords(dojo.byId(linkID));
    classPaneX = pos.x;
    classPaneY = pos.y;
    var myAjax = new Ajax.Request('/annotate/get-class-lookup',
        { method: 'get',
        onComplete: showClasses }
    );   
}

var gridClassList;
var classPane;
function showClasses(response)
{
    //if the classPane has already been created, just reposition and display:
    if(classPane != null)
    {
        classPane.domNode.style.top = classPaneY;
        classPane.domNode.style.left = classPaneX;
        classPane.show();
        return;
    }
        
    //1.  Initialize the data store from the ajax response object:
    var datagridHelper      = dojo.fromJson(response.responseText);
    var theRecords          = datagridHelper.dataRecords;
    var dataRecordsLayout   = datagridHelper.layout;
    
    dataRecordsLayout[1].formatter  = dojo.fromJson(dataRecordsLayout[1].formatter); //a hack:  ensure that the "edit type" is being evaluated as a type rather than a string!
    var theRecordStore              = new dojo.data.ItemFileWriteStore({data: theRecords, clearOnClose: true});
    var dataRecordsView             = { rows: [ dataRecordsLayout ] };
    var dataRecordsStruct           = [ dataRecordsView ];

    //2. Create a new grid and add it to the page:
    gridClassList = new dojox.grid.DataGrid({
        jsId: 'gridClassList',
        id: 'gridClassList',
        store: theRecordStore,
        clientSort: true,
        rowSelector: '20px',
        structure: dataRecordsStruct,
        singleClickEdit: true
    }, document.createElement('div'));

    var styleString = 'width: 180px; height: 350px; left: ' + classPaneX + 'px; top: ' + classPaneY + 'px;';
    var paneTitle = "<table style='width: 170px;' cellpadding='0' cellspacing='0' border='0'><tr><td> " +
                    "Choose Icon and Class</td><td align='right'><a href='javascript:classPane.hide();'>" +
                    "<img src='/public/scripts/dojoroot/dojox/image/resources/images/close_dark.png' border='0' />" +
                    "</a></td></tr></table>";
    classPane = createFloatingPane("classPane", styleString, paneTitle, false);
    classPane.setContent(gridClassList.domNode);
    
    //attach event handler:
    dojo.connect(gridClassList, 'onRowClick',
        function(event){ gridClassList_makeSelection(event); }         
    ); 
}

function showImage(value)
{
    return "<img src='/public/images/classes/" + value + "' />";
}

function gridClassList_makeSelection(event)
{
    var item = gridObjectAnnotation.selection.getSelected()[0];
    //alert(item);
    var imageItem = gridClassList.selection.getSelected()[0];
    //alert(gridClassList.store.getValues(imageItem, "class_icon"));
    //alert(imageItem);
    var class_uuid  = gridClassList.store.getValues(imageItem, "class_uuid");
    var image       = gridClassList.store.getValues(imageItem, "sm_class_icon");
    var imageLg     = gridClassList.store.getValues(imageItem, "class_icon");
    var class_label = gridClassList.store.getValues(imageItem, "class_label");
    //alert(image);
    gridObjectAnnotation.store.setValues(item, "fk_class_uuid", class_uuid);
    gridObjectAnnotation.store.setValues(item, "sm_class_icon", image);
    gridObjectAnnotation.store.setValues(item, "class_icon", imageLg);
    gridObjectAnnotation.store.setValues(item, "class_label", class_label);
    //close the pane after the selection has been made:
    classPane.hide();
    
    updateItem(item);
}

function saveClassData()
{
    //create serialized json object to send to the server:
     
    var request = gridObjectAnnotation.store.fetch({onComplete: createClassJSONString});

}

function createClassJSONString(items, request)
{
    var jsonObject = new Array();
    for (var i = 0; i < items.length; i++)
    {            
        var row = new Object();
        var item = items[i];
        
        //fields to be committed back to the database:
        row.pk_field        = gridObjectAnnotation.store.getValues(item, "pk_field")[0];
        row.field_lab_com   = gridObjectAnnotation.store.getValues(item, "field_lab_com")[0];
        row.fk_class_uuid   = gridObjectAnnotation.store.getValues(item, "fk_class_uuid")[0];
        row.field_notes     = gridObjectAnnotation.store.getValues(item, "field_notes")[0];
        
        if(row.field_notes == clickText)
            row.field_notes = "";
        jsonObject[i] = row;
    }
    //alert(dojo.toJson(jsonObject));
        
    //send json data to server:
    var myAjax = new Ajax.Request('/annotate/save-class-datastore',
        { method: 'get', parameters: {datastore: dojo.toJson(jsonObject) },
        onComplete: saveConfirmation }
    );
}

function saveConfirmation(response)
{
    //alert(response.responseText);
    updateNavigation();
}
