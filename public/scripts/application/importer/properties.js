var doubleclickText = "<em>double-click to edit...</em>";

function initPropertiesPane()
{
    displayWizardHeader("ASSIGN PROPERTIES");
    
    //populate users dropdown:
    var myAjax = new Ajax.Request('/property/get-property-data',
        {
            method: 'get',
            parameters: {dataTableName: importer.currentProject.dataTableName },
            onComplete: showPropertiesGrid }
    );
    
    //populate non-property dropdown:
    var myAjax = new Ajax.Request('/property/get-nonproperty-data',
        {
            method: 'get',
            parameters: {dataTableName: importer.currentProject.dataTableName },
            onComplete: showNonpropertiesGrid
        }
    );
    
    //get sample properties data: 
    var myAjax = new Ajax.Request('/property/get-sample-property-data',
        {
            method: 'get',
            parameters: {dataTableName: importer.currentProject.dataTableName },
            onComplete: displaySamplePropertyData
        }
    );
     
    displayState();
}


var gridProperties;
function showPropertiesGrid(response)
{
    if(gridProperties != null)
    {
        gridProperties.destroy();
        gridProperties = null;
    }

    //1.  Initialize the data store from the ajax response object:
    var datagridHelper      = dojo.fromJson(response.responseText);
    var theRecords          = datagridHelper.dataRecords;
    var dataRecordsLayout   = datagridHelper.layout;
    
    theRecordStore          = new dojo.data.ItemFileWriteStore({data: theRecords, clearOnClose: true});
    var dataRecordsView     = { rows: [ dataRecordsLayout ] };
    var dataRecordsStruct   = [ dataRecordsView ];
    
    //2.  pre-populate record store with double-click instructions:
    /*var addDoubleclickText = function(items, request){
        for (var i = 0; i < items.length; i++){
            var item = items[i];
            if(theRecordStore.getValues(item, "objName") == null || theRecordStore.getValues(item, "objName")[0].length == 0)
                theRecordStore.setValues(item, "objName", doubleclickText);
            alert(theRecordStore.getValues(item, "objName"));
        }
    }
    var request = theRecordStore.fetch({onComplete: addDoubleclickText});*/

    
    //3. Create a new grid and add it to the page:
    gridProperties = new dojox.grid.DataGrid({
        id: 'gridProperties',
        store: theRecordStore,
        clientSort: true,
        rowSelector: '20px',
        structure: dataRecordsStruct //,
        //singleClickEdit: true
    }, document.createElement('div'));
    dojo.byId("gridPropertiesContainer").appendChild(gridProperties.domNode);
    gridProperties.startup();
    
    //4.  Add event handlers:
    /*dojo.connect(gridFieldSummary, 'onRowDblClick',
        function(event){ editDescriptionCell(event); }         
    );    
    dojo.connect(gridFieldSummary, 'onApplyCellEdit',
        function(event){ gridFieldSummary_applyEdit(event); }         
    );

    //5.  Determine number of fields left to classify:
    getNumFieldsToClassify();*/
}

var gridNonproperties;
function showNonpropertiesGrid(response)
{
    //alert(response.responseText);
    var nonPropContainer = document.getElementById("gridNonpropertiesContainer");
    nonPropContainer.innerHTML = "";
    var rows = dojo.fromJson(response.responseText);
    
    var ul = document.createElement("ul");   
    dojo.attr(ul, "style", "padding-left: 20px");       
    for(i=0; i < rows.length; i++)
    {
        var row = rows[i];
        var li = document.createElement("li");   
        //dojo.attr(li, "style", "padding-left: 0px");  
        var a = document.createElement("a");        
        dojo.attr(a, "innerHTML", row.propName1);   
        dojo.attr(a, "href", "javascript:saveFieldData(" + row.id + ");");
        
        var span = document.createElement("span");
        dojo.attr(span, "innerHTML", " (" + row.field_type + ")");
        dojo.attr(span, "style", "color: #666; font-style: italic; margin-bottom: 4px;");
        
        li.appendChild(a);
        li.appendChild(document.createElement("br"));
        li.appendChild(span);
        ul.appendChild(li);
    }
    nonPropContainer.appendChild(ul);
}

function saveFieldData(nonPropID)
{
    //create serialized json object to send to the server:
    var items           = gridProperties.selection.getSelected();
    if(items == null || items.length == 0)
    {
        alert("Please select one or more properties from the left-hand property list");
        return;
    }
    //var nonPropItem     = gridNonproperties.selection.getSelected()[0];
    //var nonPropID       = gridNonproperties.store.getValues(nonPropItem, "id")[0];
    var jsonObject = new Array();
    for (var i = 0; i < items.length; i++)
    {
        var item = items[i];
        var row = new Object();
        row.propID      = gridProperties.store.getValues(item, "id")[0];
        row.propName    = gridProperties.store.getValues(item, "propName")[0];      
        jsonObject[i]   = row;
    }
        
    //send json data to server:
    var myAjax = new Ajax.Request('/property/save-property-mappings',
        {
            method: 'get',
            parameters:
            {
                datastore: dojo.toJson(jsonObject),
                nonPropID: nonPropID,
                dataTableName: importer.currentProject.dataTableName
            },
            onComplete: savePropertyMappingsConfirmation
        }
    );
}

function savePropertyMappingsConfirmation(response)
{
    //alert(response.responseText);
    initPropertiesPane();
}

function displaySamplePropertyData(response)
{
    //alert(response.responseText);
    var recList         = dojo.fromJson(response.responseText);
    var containerNode   = dojo.byId('propSampleRecordsContainer');
    var len             = containerNode.childNodes.length;
    for(var i = (len-1); i >= 0; i--)
    {
        containerNode.removeChild(containerNode.childNodes[i]);
    }
    
    //for each non-property:
    for(i=0; i < recList.length; i++)
    {
        var records = recList[i];        
        var headerRec = records[0];
        headerDiv = document.createElement("div");
        headerDiv.innerHTML = headerRec[0];        
        dojo.attr(headerDiv, "class", "tblHeader");
        containerNode.appendChild(headerDiv);
        var table = document.createElement("table");
        dojo.attr(table, "class", "tblGeneric");
        dojo.attr(table, "cellSpacing", "0");
        dojo.attr(table, "cellpadding", "2");
        dojo.attr(table, "width", "100%");
        //for each data record:
        for (j=1; j < records.length; j++)
        {
            var rec = records[j];
            //each data record is a new table:   
            
            //alert("table");
            for(k=0; k < rec.length; k++)
            {
                var row = table.insertRow(k);
                if(k==0)
                    dojo.attr(row, "class", "tblGenericRowOdd");    
                var cell1 = row.insertCell(0);   
                cell1.innerHTML = headerRec[k];   
                var cell2 = row.insertCell(1);   
                cell2.innerHTML = rec[k];     
            }              
        }
        containerNode.appendChild(table);
        containerNode.appendChild(document.createElement("br"));
    }
}

