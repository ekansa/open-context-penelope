function initRelationshipsPane()
{
    //dojo.byId('step5Pane_header').innerHTML = "CLASSIFY AND DESCRIBE FIELDS";
    displayWizardHeader("ESTABLISH RELATIONSHIPS");
    
    //enable / disable the "fieldLinksButton"
    var myAjax = new Ajax.Request('/relationships/has-field-links',
        { method: 'get', parameters: {dataTableName: importer.currentProject.dataTableName },
        onComplete: enableFieldLinksButton }
    );
    
    populateTree();
    
    var myAjax = new Ajax.Request('/relationships/get-other-relationships',
        { method: 'get', parameters: {dataTableName: importer.currentProject.dataTableName },
        onComplete: displayOtherRelationships }
    );
    
    var myAjax = new Ajax.Request('/relationships/query-for-relationships',
        { method: 'get', parameters: {dataTableName: importer.currentProject.dataTableName },
        onComplete: showRelationships }
    );
    
    displayState();
}

function displayOtherRelationships(response)
{
    //alert(response.responseText);
    var recordArray = dojo.fromJson(response.responseText);

    //remove child nodes:
    var containerNode = dojo.byId("otherRelationshipsContainerCell");
    var len = containerNode.childNodes.length;
    for(var i = (len-1); i >= 0; i--)
    {
        containerNode.removeChild(containerNode.childNodes[i]);
    }
    
    for(i=0; i < recordArray.length; i++)
    {
        var records = recordArray[i];
          
        var headerText = document.createElement("div");
        headerText.innerHTML = "<br />Linking Relationship #" + (i+1);
        containerNode.appendChild(headerText);
        
        var table = document.createElement("table");
        dojo.attr(table, "class", "tblGeneric");
        dojo.attr(table, "cellSpacing", "0");
        dojo.attr(table, "cellpadding", "0");
        dojo.attr(table, "width", "100%");
        
        for(j=0; j < records.length; j++)
        {
            if(j==0)
            {
                var row = table.insertRow(j);
                
                dojo.attr(row, "class", "tblGenericRowOdd");
                var cell1 = row.insertCell(0);
                cell1.innerHTML = "<strong>" + trim(records[j]['origin'], 20) + "</strong>";
                
                var cell2 = row.insertCell(1);
                cell2.innerHTML = "<strong>" + records[j]['verb'] + "</strong>";
                
                var cell3 = row.insertCell(2);
                cell3.innerHTML = "<strong>" + trim(records[j]['target'], 20) + "</strong>";
            }
            else
            {                
                var row = table.insertRow(j);
                
                var cell1 = row.insertCell(0);
                cell1.innerHTML = trim(records[j]['origin'], 20);
                
                var cell2 = row.insertCell(1);
                dojo.attr(cell2, "align", "center");
                if(records[j]['fieldLink'])
                    cell2.innerHTML = records[j]['verb'];
                else
                    cell2.innerHTML = "<img src='/public/images/linkArrowLight.gif' />";
                
                var cell3 = row.insertCell(2);
                cell3.innerHTML = trim(records[j]['target'], 20);
            }
        }
        containerNode.appendChild(table);
    }
}

function trim(val, len)
{
    if (val.length < len)
        return val;
    return val.substr(0, len) + "...";
}

function enableFieldLinksButton(response)
{
    //alert(response.responseText);
    var numLinkFields = dojo.fromJson(response.responseText);
    var isDisabled = (numLinkFields == 0);
    //alert(numLinkFields + " - " + isEnabled + " - " + fieldLinksButton);
    fieldLinksButton.setDisabled(isDisabled);
}

var gridRelationships;
function showRelationships(response)
{
    //alert("showRelationships: " + response.responseText);
    if(gridRelationships != null && dojo.byId("relationshipsCell").innerHTML.length > 0)
    {
        //alert(dojo.byId("relationshipsCell").innerHTML.length);
        //alert("destroying grid");
        gridRelationships.destroy();
        gridRelationships = null;
        //alert("grid destroyed");
    }
    
    if(response.responseText == "")
    {
        dojo.byId("relationshipsCell").innerHTML = "<br /><br /><br /><br /><br /><br /><div style='padding:20px;'>No relationships have been established. " +
        "Please use the buttons below to create relationships among objects.</div>";
        return;    
    }
    //1.  Initialize the data store from the ajax response object:
    dojo.byId("relationshipsCell").innerHTML = "";
    //alert(response.responseText);
    var datagridHelper              = dojo.fromJson(response.responseText);
    var theRecords                  = datagridHelper.dataRecords;
    var dataRecordsLayout           = datagridHelper.layout;
    dataRecordsLayout[0].formatter  = dojo.fromJson(dataRecordsLayout[0].formatter); 
    
    var theRecordStore              = new dojo.data.ItemFileWriteStore({data: theRecords, clearOnClose: true});
    var dataRecordsView             = { rows: [ dataRecordsLayout ] };
    var dataRecordsStruct           = [ dataRecordsView ];
    
    //2. Create a new grid and add it to the page:
    gridRelationships = new dojox.grid.DataGrid({
        id: 'gridRelationships',
        store: theRecordStore,
        clientSort: true,
        //rowSelector: '20px',
        structure: dataRecordsStruct,
        singleClickEdit: true
    }, document.createElement('div'));
    dojo.byId("relationshipsCell").appendChild(gridRelationships.domNode);
    gridRelationships.startup();
    
    dojo.connect(gridRelationships, 'onCellClick',
        function(event){ deleteRelationshipDialog(event); }         
    ); 
    //alert("gridRelationships started");
}

var gridLinkObjects;
function showLinkObjects(response)
{
    //alert(response.responseText);
    if(gridLinkObjects != null)
        gridLinkObjects.destroy();

    //1.  Initialize the data store from the ajax response object:
    var datagridHelper      = dojo.fromJson(response.responseText);
    var theRecords          = datagridHelper.dataRecords;
    var dataRecordsLayout   = datagridHelper.layout;
    
    var theRecordStore              = new dojo.data.ItemFileWriteStore({data: theRecords, clearOnClose: true});
    var dataRecordsView             = { rows: [ dataRecordsLayout ] };
    var dataRecordsStruct           = [ dataRecordsView ];

    //2. Create a new grid and add it to the page:
    gridLinkObjects = new dojox.grid.DataGrid({
        id: 'gridLinkObjects',
        store: theRecordStore,
        clientSort: true,
        //rowSelector: '20px',
        structure: dataRecordsStruct,
        singleClickEdit: true
    }, document.createElement('div'));
    dojo.byId("gridLinkObjectsContainer").appendChild(gridLinkObjects.domNode);
    gridLinkObjects.startup();
    //alert("showLinkObjects: linkObjectsStarted");
    populateTree();
}

function populateTree()
{
    var myAjax = new Ajax.Request('/relationships/populate-tree',
        { method: 'get', parameters: {dataTableName: importer.currentProject.dataTableName },
        onComplete: displayTree }
    );  
}

var relationshipTrees = new Array();
function displayTree(response)
{
    //alert(response.responseText);
    
    //destroy all of the relationship trees before creating new ones:
    destroyTrees();
    
    if(response.responseText == "")
    {
        //alert("no relationships have been established");
        return;
    }
    
    //alert("still displaying tree!");
    var dataStores = dojo.fromJson(response.responseText);
    
    //there can be more than one relationship tree:
    for(i=0; i < dataStores.length; i++)
    {
        var theRecordStore = new dojo.data.ItemFileWriteStore({data: dataStores[i], clearOnClose: true});
        var ordinal = i+1;
        var treeModel = new dijit.tree.ForestStoreModel({
            store: theRecordStore,
            query: {type: '0'},
            rootId: "root",
            rootLabel:"Containment Relationship #" + ordinal,
            childrenAttrs: ["children"]
        });
        
        //dynamically create the treeDiv each time because it is destroyed
        //in the "destroyRecursive" function call:
        var treeDiv = document.createElement("div");
        treeDiv.id = 'treeContainer' + i;
        dojo.byId('treeContainerCell').appendChild(treeDiv);
                                
        relationshipTrees[i] = new dijit.Tree({model: treeModel}, treeDiv);    
    }   
}

function destroyTrees()
{
    //destroy all of the relationship trees before creating new ones:
    var numTrees = relationshipTrees.length;
    if(numTrees > 0)
    {
        for(i = numTrees-1; i >= 0; --i)
            relationshipTrees[i].destroyRecursive(true);
        relationshipTrees = new Array();
    }
}


/*****************************/
/* Dropdowns and Dialog Boxes*/
/*****************************/

var dialogType; //global variable:
function initAddRelationshipDialog(relationshipType)
{
    dialogType = relationshipType;
    dijit.byId('dialogAddRelationship').show();
    var allProps    = true;
    if(dialogType == "containment")
        allProps = false;

    //if the fields haven't yet been assigned a datatype, auto-calculate now,
    //then populate the data store:
    var myAjax = new Ajax.Request('/relationships/get-fields-for-dropdowns',
        {   method: 'get',
            parameters: {
                dataTableName: importer.currentProject.dataTableName,
                allProperties: allProps,
                excludeValue: ""
            },
            onComplete: refreshOriginDD }
    );
}

var ddOrigin;
function refreshOriginDD(response)
{
    //alert(response.responseText);
    var theRecords      = dojo.fromJson(response.responseText);
    var theRecordStore  = new dojo.data.ItemFileWriteStore({data: theRecords, clearOnClose: true});
    if(ddOrigin == null)
    {
        ddOrigin     = new dijit.form.FilteringSelect({id: "ddOrigin",
                                name: "ddOrigin",
                                store: theRecordStore,
                                forceValidOption: true,
                                invalidMessage: "Please select a valid 'origin' field", 
                                searchAttr: "field_label",
                                onChange: onchange_makeRelationshipSelection},
                            "ddOrigin");
    }
    else
    {
        ddOrigin.store = theRecordStore;
    }
    
    //alert("query for relationships...");
    var myAjax = new Ajax.Request('/relationships/get-relationships-for-dropdown',
        {
            method: 'get',
            parameters: {
                dataTableName: importer.currentProject.dataTableName,
                dialogType: dialogType
            },
            onComplete: refreshRelationshipsDD }
    );
    
}

var ddRelationship;
function refreshRelationshipsDD(response)
{
    //alert(response.responseText);
    var theRecords      = dojo.fromJson(response.responseText);
    var theRecordStore  = new dojo.data.ItemFileWriteStore({data: theRecords, clearOnClose: true});
    //alert(theRecordStore);
    if(ddRelationship == null)
    {
        ddRelationship     = new dijit.form.ComboBox({id: "ddRelationship",
                                name: "ddRelationship",
                                store: theRecordStore,
                                searchAttr: "RELATIONSHIP_TYPE",
                                onChange: onchange_doTargetRefresh,
                                disabled: false},
                            "ddRelationship");
    }
    else
    {
        ddRelationship.store = theRecordStore;
    }    
    ddRelationship.attr('value', theRecords.items[0].RELATIONSHIP_TYPE);
    
    queryForAllProperties = true;
    if(dialogType == "containment")
        queryForAllProperties = false;
    var excludeValue            = (ddOrigin != null ? ddOrigin.attr('displayedValue') : ""); 
    //alert("excludeValue: " + excludeValue);
    var myAjax = new Ajax.Request('/relationships/get-fields-for-dropdowns',
        { method: 'get',
            parameters: {
                dataTableName: importer.currentProject.dataTableName,
                allProperties: queryForAllProperties,
                excludeValue: excludeValue
            },
        onComplete: refreshTargetDD }
    );
}

var ddTarget;
function refreshTargetDD(response)
{
    var theRecords      = dojo.fromJson(response.responseText);
    var theRecordStore  = new dojo.data.ItemFileWriteStore({data: theRecords, clearOnClose: true});
    if(ddTarget == null)
    {
        ddTarget     = new dijit.form.FilteringSelect({id: "ddTarget",
                                name: "ddTarget",
                                store: theRecordStore,
                                forceValidOption: true, 
                                invalidMessage: "Please select a valid 'target' field", 
                                searchAttr: "field_label",
                                disabled: false},
                        "ddTarget");
    }
    else
    {
        ddTarget.store       = theRecordStore;
        ddTarget.setDisabled(false);
        
        //only refresh the target value if it's currently null and the selected
        //target value doesn't exist in the datasource.
        if(ddTarget.attr('displayedValue') == "")
        {
            ddTarget.attr('value', theRecords.items[0].id);
            return;
        }
        else
        {
            var isValid = false;
            for(n=0; n < theRecords.items.length; n++)
            {
                if(theRecords.items[n].id == ddTarget.attr('value'))
                {
                    isValid = true;
                    break;
                }
            }
            if(!isValid)
                ddTarget.attr('value', theRecords.items[0].id);    
        }
    }   
}


function onchange_makeRelationshipSelection()
{
    //alert("onchange_makeRelationshipSelection");
    if(dijit.byId('ddOrigin') == "")
    {
        alert("Please select a 'linking' field");
        return;
    }
    else
    {
        ddRelationship.setDisabled(false);
        onchange_doTargetRefresh();

    }  
}

function onchange_doTargetRefresh()
{
    //alert("onchange_doTargetRefresh");    
    queryForAllProperties = true;
    if(dialogType == "containment")
        queryForAllProperties = false;
    var excludeValue            = (ddOrigin != null ? ddOrigin.attr('displayedValue') : "");
    //alert("excludeValue: " + excludeValue);

    var myAjax = new Ajax.Request('/relationships/get-fields-for-dropdowns',
        { method: 'get',
            parameters: {
                dataTableName: importer.currentProject.dataTableName,
                allProperties: queryForAllProperties,
                excludeValue: excludeValue
            },
        onComplete: refreshTargetDD }
    );
    //ddTarget.setDisabled(false);
}

function addRelationship()
{
    var message = "";
    if(dijit.byId('ddOrigin') == "")
        message += "Please select a 'linking' field\n";
    if(dijit.byId('ddRelationship').value == "")
        message += "Please select a relationship type\n";
    if(dijit.byId('ddTarget') == "")
        message += "Please select a 'linked' field\n";
    if(message.length > 0)
    {
        alert(message);
        return;
    }
    /*alert(importer.currentProject.dataTableName + "\n" + 
        dijit.byId('ddOrigin') + "\n" + 
        dijit.byId('ddRelationship').value + "\n" + 
        dijit.byId('ddTarget') + "\n" + 
        dialogType
    );*/
    
    var myAjax = new Ajax.Request('/relationships/is-valid-relationship',
        {   method: 'get',
            parameters: {
                dataTableName:  importer.currentProject.dataTableName,
                origin:         dijit.byId('ddOrigin'),
                relationship:   dijit.byId('ddRelationship').value,
                target:         dijit.byId('ddTarget'),
                dialogType:     dialogType
            },
            onComplete: confirmAddRelationship
        }
    );

}

function confirmAddRelationship(response)
{
    if(response.responseText == "")
    {
        var myAjax = new Ajax.Request('/relationships/add-relationship',
            {   method: 'get',
                parameters: {
                    dataTableName:  importer.currentProject.dataTableName,
                    origin:         dijit.byId('ddOrigin'),
                    relationship:   dijit.byId('ddRelationship').value,
                    target:         dijit.byId('ddTarget'),
                    dialogType:     dialogType
                },
                onComplete: initRelationshipsPane
            }
        );
        dijit.byId('dialogAddRelationship').hide();
    }
    else
    {
        var errorText = response.responseText + "\n";
        errorText += ddOrigin.attr('displayedValue')  + " / " + ddTarget.attr('displayedValue')  + "\n";
        alert(errorText);
    }
    //initRelationshipsPane();
}

function deleteRelBut()
{
    //since I couldn't get the link click to work, I'm leveraging the
    //datagrid's onCellClick event and making it look like a link:
    return "<a href='#'>remove</a>";
}

function deleteRelationshipDialog(evt)
{
    //dude, this is a total hack...but it works:
    if(evt.cell.name == '&nbsp;') { deleteRelDlg(); }
}

function deleteRelDlg()
{
    //alert('showDelRelDlg');
    var items = gridRelationships.selection.getSelected();
    if(items.length)
    {
        dojo.byId('removeRelationshipText').innerHTML = "Are you sure you want to remove <strong><em>" +
            items[0].parent_name + " " + items[0].verb + " " + items[0].child_name + 
            "</em></strong> from this dataset?";
        dijit.byId('dialogRemoveRelationship').show();   
    }
    else
    {
        alert("No selection could be found.");
    }          
}

function removeRelationship()
{
    var items = gridRelationships.selection.getSelected();
    var myAjax = new Ajax.Request('/relationships/remove-relationship',
        {   method: 'get',
            parameters: {
                dataTableName:  importer.currentProject.dataTableName,
                id:             items[0].id
            },
            onComplete: removeRelationshipComplete
        }
    );
    dijit.byId('dialogRemoveRelationship').hide(); 
}

function removeRelationshipComplete(response)
{
    //alert(response.responseText);
    initRelationshipsPane();
}

