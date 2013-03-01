var fieldList;
var theRecordStore;
var gridFieldSummary;
var doubleclickText = "<em>double-click to edit...</em>";
var fieldCounter = document.createElement("span");
var help;

function initFieldSummaryPane()
{
    //dojo.byId('step5Pane_header').innerHTML = "CLASSIFY AND DESCRIBE FIELDS";
    displayWizardHeader("CLASSIFY AND DESCRIBE FIELDS");
    
    //if the fields haven't yet been assigned a datatype, auto-calculate now,
    //then populate the data store:
    var myAjax = new Ajax.Request('/classify/calc-data-types',
        { method: 'get', parameters: {dataTableName: importer.currentProject.dataTableName },
        onComplete: getFieldSummaryDataStore }
    );

}


function getFieldSummaryDataStore(response)
{
    //alert(response.responseText);
    if(dijit.byId('gridFieldSummary') != null)
        dijit.byId('gridFieldSummary').destroy();
        
    var myAjax = new Ajax.Request('/classify/get-field-summary-datastore',
        { method: 'get', parameters: {dataTableName: importer.currentProject.dataTableName },
        onComplete: showFieldSummaryGrid }
    );
    
    //only populate once:
    if(dojo.byId('fieldOptionsContent').getElementsByTagName("div").length == 0)
    {
        var myAjax1 = new Ajax.Request('/classify/get-field-types',
            { method: 'get', parameters: {dataTableName: importer.currentProject.dataTableName },
            onComplete: displayFieldTypes }
        );
    }
}

function displayFieldTypes(response)
{
    fieldList = dojo.fromJson(response.responseText);
    var spanContainer = document.createElement("div");

    for(var i=0; i < fieldList.length; i++)
    {
        //create div:
        var divContainer = document.createElement("div");
        var divContainerID = "divContainer_" + i;
        dojo.attr(divContainer, "id", divContainerID);

        //add image:
        var img = document.createElement("img");
        img.src = "/public/images/help_get_small.jpg";  
        
        //add link:
        var aLink = document.createElement("a");
        aLink.href = "javascript:displayHelpMenuDetail(" + i + ")";
        aLink.appendChild(img);
        divContainer.appendChild(aLink);
        
        //add another link:        
        var link = document.createElement("a");
        link.href = "javascript:populateDatastoreWithSelection('" + fieldList[i].name + "')";
        link.innerHTML = fieldList[i].name;
        divContainer.appendChild(link);
        
        spanContainer.appendChild(divContainer);
    }
    
    spanContainer.appendChild(document.createElement("br"));
    spanContainer.appendChild(fieldCounter);
    //fieldOptions.setContent(spanContainer);
    dojo.byId('fieldOptionsContent').appendChild(spanContainer);
}


function displayHelpMenuDetail(idx)
{
    var pos = dojo.coords(dojo.byId('fieldOptionsContent'));
    var left = pos.x - 375;
    //alert(pos.x + ", " + pos.y);
    if(help != null)
        help.destroy();
    //styleString = 'width: 375px; height: 150px; left: 300px; top: 100px;';    
    styleString = 'width: 375px; height: 150px; left: ' + left + 'px; top: ' + pos.y + 'px;';
    help = createFloatingPane('help', styleString, fieldList[idx].name, true);
    help.setContent(fieldList[idx].description);   
}

function displayHelpMenu()
{
    var table = document.createElement("table");
    dojo.attr(table, "class", "tundra");
    dojo.attr(table, "cellSpacing", "0");
    for(var i=0; i < fieldList.length; i++)
    {
        //add row:
        var lastRow = table.rows.length;
        var row = table.insertRow(lastRow);
        if(i % 2 == 0)
            dojo.attr(row, "class", "dojoxGridRowOdd");
        
        //add cells:
        var cell1 = row.insertCell(0);
        dojo.attr(cell1, "class", "dojoxGridCell");
        cell1.innerHTML = fieldList[i].name;
        
        var cell2 = row.insertCell(1);
        dojo.attr(cell2, "class", "dojoxGridCell");
        cell2.innerHTML = fieldList[i].description;
    }

    var pos = dojo.coords(dojo.byId(mainTabContainer.selectedChildWidget.id));
    var left = pos.x + 200;
    if(help != null)
        help.destroy();
    styleString = 'width: 375px; height: 350px; left: ' + left + 'px; top: ' + pos.y + 'px;';
    help = createFloatingPane('help', styleString, 'HELP:  Field Classification Types', true);
    help.setContent(table);
}

function populateDatastoreWithSelection(fieldType)
{
    var selectedItems = gridFieldSummary.selection.getSelected();
    if(selectedItems != null && selectedItems.length > 0)
    {
        for(var i=0; i < selectedItems.length; i++)
            gridFieldSummary.store.setValues(selectedItems[i], "field_type", fieldType);
        getNumFieldsToClassify();
    }
    else
    {
        alert("Please select one or more rows from the table to the left.");
    }
}

function showFieldSummaryGrid(response)
{
    //alert(response.responseText);
    //1.  Initialize the data store from the ajax response object:
    var datagridHelper      = dojo.fromJson(response.responseText);
    var theRecords          = datagridHelper.dataRecords;
    var dataRecordsLayout   = datagridHelper.layout;
    
    dataRecordsLayout[1].type = dojo.fromJson(dataRecordsLayout[1].type); //a hack:  ensure that the "edit type" is being evaluated as a type rather than a string!
    dataRecordsLayout[3].type = dojo.fromJson(dataRecordsLayout[3].type); // ""
    theRecordStore          = new dojo.data.ItemFileWriteStore({data: theRecords, clearOnClose: true});
    var dataRecordsView     = { rows: [ dataRecordsLayout ] };
    var dataRecordsStruct   = [ dataRecordsView ];
    
    //2.  pre-populate record store with double-click instructions:
    var addDoubleclickText = function(items, request){
        for (var i = 0; i < items.length; i++){
           var item = items[i];
           if(theRecordStore.getValues(item, "prop_desc") == null || theRecordStore.getValues(item, "prop_desc")[0].length == 0)
            theRecordStore.setValues(item, "prop_desc", doubleclickText);
        }
    }
    var request = theRecordStore.fetch({onComplete: addDoubleclickText});

    //3. Create a new grid and add it to the page:
    gridFieldSummary = new dojox.grid.DataGrid({
        id: 'gridFieldSummary',
        store: theRecordStore,
        clientSort: true,
        rowSelector: '20px',
        structure: dataRecordsStruct //,
        //singleClickEdit: true
    }, document.createElement('div'));
    dojo.byId("fieldSummaryGridContainer").appendChild(gridFieldSummary.domNode);
    gridFieldSummary.startup();
    
    //4.  Add event handlers:
    dojo.connect(gridFieldSummary, 'onRowDblClick',
        function(event){ editDescriptionCell(event); }         
    );    
    dojo.connect(gridFieldSummary, 'onApplyCellEdit',
        function(event){ gridFieldSummary_applyEdit(event); }         
    );

    //5.  Determine number of fields left to classify:
    getNumFieldsToClassify();
}

function editDescriptionCell(evt)
{
    var isFieldDescription = evt.cell.name == "Field Description";
    if(!isFieldDescription)
        return;
    //alert(evt.cell.name);
    var item = gridFieldSummary.selection.getSelected()[0];
    if(gridFieldSummary.store.getValues(item, "prop_desc") == doubleclickText)
        gridFieldSummary.store.setValues(item, "prop_desc", "");
}

function gridFieldSummary_applyEdit(editText)
{
    if(editText == "")
    {
        var item = gridFieldSummary.selection.getSelected()[0];
        gridFieldSummary.store.setValues(item, "prop_desc", doubleclickText);
    }
    getNumFieldsToClassify();
}

function saveClassifyData()
{
    //create serialized json object to send to the server:
    if(theRecordStore != null)
        var request = theRecordStore.fetch({onComplete: createFieldJSONString});
    //alert(theRecordStore);

}

var classifyCounter;
var classifyCounterBatch;
var numItemsToClassify;
function createFieldJSONString(items, request)
{
    classifyCounter         = 0;
    classifyCounterBatch    = 0;
    numItemsToClassify      = items.length;
    var jsonObject          = new Array();
    for (classifyCounter = 0; classifyCounter < items.length; classifyCounter++)
    {
        var item = items[classifyCounter];
        var row = new Object();
        row.pk_field        = theRecordStore.getValues(item, "pk_field")[0];
        row.field_label     = theRecordStore.getValues(item, "field_label")[0];
        row.field_type      = theRecordStore.getValues(item, "field_type")[0];
        row.prop_type       = theRecordStore.getValues(item, "prop_type")[0];
        row.prop_desc       = theRecordStore.getValues(item, "prop_desc")[0];
        if(row.prop_desc == doubleclickText)
            row.prop_desc   = "";
        //alert(classifyCounterBatch);
        jsonObject[classifyCounterBatch] = row;
        ++classifyCounterBatch;
        
        //send off to server every 2 records -- too many records at a time freaks out IE:
        if(classifyCounterBatch == 2)
        {
            //alert(dojo.toJson(jsonObject));        
            //send json data to server:
            var myAjax = new Ajax.Request('/classify/save-fields-datastore',
                { method: 'get', parameters: {datastore: dojo.toJson(jsonObject) },
                onComplete: saveConfirmation }
            );
            jsonObject = new Array();
            classifyCounterBatch = 0;
        }
    }
    if(jsonObject.length > 0)
    {
        //alert("This isn't working in IE");
        //alert(dojo.toJson(jsonObject));
            
        //send json data to server:
        var myAjax = new Ajax.Request('/classify/save-fields-datastore',
            { method: 'get', parameters: {datastore: dojo.toJson(jsonObject) },
            onComplete: saveConfirmation }
        );
    }
}

var numFieldsClassified = 0;
function saveConfirmation(response)
{
    //alert(response.responseText);
    var numRows = parseInt(response.responseText);
    numFieldsClassified += numRows;
    //alert(numFieldsClassified + " - " + numItemsToClassify);
    if(numFieldsClassified == numItemsToClassify)
    {
        alert("fields successfully updated.");
        numFieldsClassified = 0;
    }
    updateNavigation();
}

function getNumFieldsToClassify()
{
    var classCount = 0;
    var descCount = 0;
    var updateClassifyCounter = function(items, request){
        
        for (var i = 0; i < items.length; i++){
            var item = items[i];
            //alert(theRecordStore.getValues(item, "field_type"));
            //alert(theRecordStore.getValues(item, "prop_desc"));
            if(!!theRecordStore.getValues(item, "field_type")){
                if(theRecordStore.getValues(item, "field_type") == null){
                    ++classCount;
                }
                else{
                    if(theRecordStore.getValues(item, "field_type")[0].length == 0){
                        ++classCount;
                    }
                }
            }
            if(theRecordStore.getValues(item, "prop_desc") == doubleclickText){
                ++descCount;
            }
        }
    }
    var request = theRecordStore.fetch({onComplete: updateClassifyCounter });
    fieldCounter.innerHTML = "&nbsp;&nbsp; Fields left to classify: " + classCount + "<br />";
    fieldCounter.innerHTML += "&nbsp;&nbsp; Fields left to describe: " + descCount + "<br />";
}

function setDataTypes()
{
    //alert("set datatypes!");
        
    //send json data to server:
    var myAjax = new Ajax.Request('/classify/calc-data-types',
        { method: 'get', parameters: { dataTableName: importer.currentProject.dataTableName },
        onComplete: setDataTypesConfirm }
    );
}

function setDataTypesConfirm(response)
{
    alert(response.responseText);    
}
