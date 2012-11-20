function initEditPane()
{
    displayWizardHeader("EDIT IMPORTED DATA");
    //alert(importer.currentProject.uuID);
    destroyEditTrees();
    dojo.byId('treeContainerCellNew').innerHTML = "";
    dojo.byId('editPane').innerHTML = "";
    populateEditTree();
    displayState();
}

function populateEditTree()
{
    var myAjax = new Ajax.Request('/edit/populate-tree',
        { method: 'get', parameters: {projectUUID: importer.currentProject.uuID },
        onComplete: displayEditTree }
    );  
}

var editTrees = new Array();
var recordStoreArray = new Array();
function displayEditTree(response)
{
    //alert(response.responseText);
    //destroy all of the relationship trees before creating new ones:
    destroyEditTrees();
    
    if(response.responseText == "")
    {
        alert("no relationships have been established");
        return;
    }

    var dataStoreObjects = dojo.fromJson(response.responseText);
    
    //alert("encoded");
    
    //there can be more than one relationship tree:
    for(i=0; i < dataStoreObjects.length; i++)
    {
        var dataStoreObject = dataStoreObjects[i];
        var dataStore       = dataStoreObject.dataStore;
        var dataStoreName   = dataStoreObject.dataStoreName;
        var theRecordStore = new dojo.data.ItemFileWriteStore({data: dataStore, clearOnClose: true});
	recordStoreArray[i]	= { name: dataStoreName, store : theRecordStore};
        
        //alert("theRecordStore: " + theRecordStore);
        var rootID = "root";
        
        if(i==0){
            rootID = "space_root";
        }
        
        var treeModel = new dijit.tree.ForestStoreModel({
            store: theRecordStore,
            query: {type: '0'},
            rootId: rootID,
            rootLabel: dataStoreName,
            childrenAttrs: ["children"]
        });
        
        //dynamically create the treeDiv each time because it is destroyed
        //in the "destroyRecursive" function call:
        var treeDiv = document.createElement("div");
        treeDiv.id = 'editTreeContainer_' + i;
        dojo.byId('treeContainerCellNew').appendChild(treeDiv);
        
        if(i ==0 ) {                        
            
            //editTrees[i] = new dijit.TreeCustom(
            editTrees[i] = new dijit.Tree(
                {
                    id:         new Date(), //hack (SV):  by adding an ID function, the tree will load as collapsed every time.
                    model:      treeModel,
                    onClick:    ChildSetParamsShowDetail,
                    //onOpen:     ChildSetParamsShowDetail, //expandNode,
                    showRoot:   true
                },
            treeDiv);
            
            //alert('expanding...');
            //alert(editTrees[i].id);
            //var tree = dijit.byId('treeNode');
            /*dojo.forEach(editTrees[i].getChildren(), function(n) {
                this._expandNode(n);
            }, editTrees[i]);
            alert('expanded');*/
        }
        else{
            editTrees[i] = new dijit.Tree(
            {
                model: treeModel,
                onClick: setParamsShowDetail,
                //onClick: LazySetParamsShowDetail,
                showRoot: true
            },
        treeDiv);
        }
    }
    
    space_nodes = editTrees[0];
    //alert(recordStoreArray);
}

function destroyEditTrees()
{
    //destroy all of the relationship trees before creating new ones:
    if(editTrees == null)
        return;
    //    editTree.destroyRecursive(true);
    var numTrees = editTrees.length;
    if(numTrees > 0)
    {
        for(i = numTrees-1; i >= 0; --i)
            editTrees[i].destroyRecursive(true);
        editTrees = new Array();
    }
}

var shown_item_id = null;
var shown_item_name = null;
function ChildSetParamsShowDetail(arg, node)
{
    //alert(arg);
    //alert(node);
    if(arg.root == null || !arg.root)
    {
        importer.actNode = arg;
        //alert(arg.isStub);
        if(arg.isStub)
        {
            addChildren(arg.id, arg.type);
        }
        shown_item_id = arg.id;
        shown_item_name = arg.name;
        showDetail(arg.id, 'Locations or Objects');
    }
}

function expandNode(arg, node)
{
    //alert("expandNode");
    //alert(arg);
    //alert(node);
    if(arg.root == null || !arg.root)
    {
        importer.actNode = arg;
        //alert(arg.isStub);
        if(arg.isStub)
        {
            addChildren(arg.id, arg.type);
        }
        shown_item_id = arg.id;
        shown_item_name = arg.name;
        //showDetail(arg.id, 'Locations or Objects');
    }
    //alert('expand node successful!');
}




function setParamsShowDetail(arg)
{
    /*var str = "";
    for(var a in arg)
    {
	str += a + " " + arg[a];
    }
    alert(str);*/
    if(arg.root == null || !arg.root){
	shown_item_name = arg.name;
        showDetail(arg.id, arg.objectType);
    }
}

function addChildren(uuID, nodeLevel)
{
    //alert("adding uuid: " + uuID);
    var objectType = 'Locations or Objects';
    var myAjax = new Ajax.Request('/edit/obtain-children',
        { method: 'get', parameters: {
            uuID: uuID,
            objectType: objectType,
            level: nodeLevel
        },
        onComplete: addToTree }
    ); 
}


function addToTree(response)
{
    //alert(response.responseText);
    var newObjects = dojo.fromJson(response.responseText);
    var newChildren = newObjects;
    var oldTree = space_nodes;
    var oldNode = importer.actNode;
    
    //alert(oldNode.name + " - " + newObjects.length);
    //if(oldNode.children.length > 0 && newObjects.length > 0)
    //    oldTree.model.store.deleteItem(oldNode.children[0]);
    //alert('removed');
    if(newObjects.length>0){
        for(i=0; i < newObjects.length; i++){
            if(i<3){
                //alert("Index: " + i + " " + newObjects[i].id + " digit id:" + oldNode.id );
            }
            var newNode = oldTree.model.store.newItem(newObjects[i],{parent:oldNode, attribute: "children" } );
            //var pasteNode =  oldTree.model.store.pasteItem(/*Item*/ newNode, /*Item*/ allRoot, /*Item*/ oldNode, /*Boolean*/ false);
        }
        oldNode.isStub = false;
    }
    //alert('add to tree complete');
}//end add to tree function


function showDetail(uuID, objectType)
{
    //alert(uuID);
    shown_item_id  = uuID;
    var myAjax = new Ajax.Request('/edit/get-context-details',
        { method: 'get', parameters: {
            uuID: uuID,
            objectType: objectType,
            projectUUID: importer.currentProject.uuID
        },
        onComplete: showDetailComplete }
    ); 
}

function showDetailComplete(response)
{
    dojo.byId('editPane').innerHTML = response.responseText;
    //return;
    var contextObject = dojo.fromJson(response.responseText);
    var container = dojo.byId('editPane');
    
    //Clear out container:
    container.innerHTML = '';
    
    //Header
    var headerDiv = document.createElement("div");
    dojo.attr(headerDiv, "class", "titlePane");
    dojo.attr(headerDiv, "style", {height: "30px", verticalAlign: "middle"});
    container.appendChild(headerDiv);
    
    //Add class image and 'Location or Object' name:
    if(contextObject.classIconSmall != null)
    {
        var img = document.createElement("img");
        img.src = '/public/images/classes/' + contextObject.classIconSmall;
        dojo.attr(img, "align", "absmiddle");
        headerDiv.appendChild(img);
    }
    headerDiv.innerHTML += '&nbsp;' + contextObject.name;
    
    if(contextObject.objectType == 'Locations or Objects'){
	headerDiv.innerHTML += '&nbsp;<a href="' + importer.rootURI + '/preview/space?UUID=' + shown_item_id + '">' + shown_item_id + '</a>';
    }
    
    //Debug Div:
    /*var debugDiv = document.createElement("debugDiv");
    debugDiv.id = "debugDiv";
    container.appendChild(debugDiv);*/
    
    //Body
    var bodyDiv = document.createElement("div");    
    dojo.attr(bodyDiv, "class", "containerPane");  
    dojo.attr(bodyDiv, "style", {height: "340px", overflow: "auto"});
    container.appendChild(bodyDiv);
    
    //Add class name:
    if(contextObject.classLabel != null)
        bodyDiv.innerHTML += '<strong>Class: </strong>' + contextObject.classLabel + '<br />';
    
    //Add notes:
    if(contextObject.notes != null)
        bodyDiv.innerHTML += '<strong>Notes: </strong>' + contextObject.notes + '<br />';
    
    //Add Centering Div:
    var bodyDivCenter = document.createElement("div");    
    dojo.attr(bodyDivCenter, "style", {textAlign: "center"});
    bodyDiv.appendChild(bodyDivCenter);
    
    //Add Object-Specific Values:
    if(contextObject.objectSpecificProperties.length > 0)
        createTableFromArray2x2(bodyDivCenter, contextObject.objectType + '-Specific Values', contextObject.objectSpecificProperties, contextObject.objectType, contextObject);
    
    
    if(contextObject.objectType == 'Locations or Objects')
    {
        //Add Parent Locations and Objects:
        addParents(bodyDivCenter, 'Links to Item Parent', contextObject.parentItems, 'Locations or Objects', contextObject);
        
        //Add Child Locations and Objects:
        addChildrenLinks(bodyDivCenter, 'Links to Children Locations and Objects', contextObject.childrenItems, 'Locations or Objects', contextObject);
    }
    
    if(contextObject.objectType == 'People'){
        prepEditPerson(bodyDivCenter);
    }
    
    
    //Add Properties:
    createTableFromArray2x2(bodyDivCenter, 'Descriptive Properties', contextObject.descriptiveProperties, 'Property', contextObject);
    
    //Add People:
    //createTableFromArray2x2(bodyDivCenter, 'People', contextObject.people, contextObject.objectType);
    addLink(bodyDivCenter, 'Person', contextObject.people, 'Person', contextObject);
    
    //Add Locations:
    addLink(bodyDivCenter, 'Links to Other Locations and Objects', contextObject.locationsObjects, 'Locations or Objects', contextObject);
    
    //Add Resources:
    addLink(bodyDivCenter, 'Media (various)', contextObject.resources, 'Media (various)', contextObject);
    
    //Add Diaries:
    addLink(bodyDivCenter, 'Diary / Narrative', contextObject.diaries, 'Diary / Narrative', contextObject);
}

function createTableFromArray2x2(containerDiv, title, properties, updateType, contextObject)
{
    var uuID 		= contextObject.uuID;
    var originObjectType= contextObject.objectType;
    
    var headerDiv = document.createElement("div");
    headerDiv.innerHTML = title;        
    dojo.attr(headerDiv, "class", "tblHeaderBlue");
    if(BrowserDetect.browser == "Explorer")
        dojo.attr(headerDiv, "style", {width: "540px"});
    containerDiv.appendChild(headerDiv);
        
    var table = document.createElement("table");
    dojo.attr(table, "class", "tblGeneric");
    dojo.attr(table, "cellSpacing", "0");
    dojo.attr(table, "cellpadding", "2");
    if(BrowserDetect.browser == "Explorer")
        dojo.attr(table, "style", {width: "540px"});
    else        
        dojo.attr(table, "style", {width: "100%"});
    
    containerDiv.appendChild(table);
    containerDiv.appendChild(document.createElement("br"));
    
    //if no records found:
    if(properties.length == 0)
    {
        var row = table.insertRow(0);
        var cell1 = row.insertCell(0);
        cell1.innerHTML = "No data was found.";
    }
    //for each data record:
    for (j=0; j < properties.length; j++)
    {
	var k = 0;
        var property = properties[j];
        var row = table.insertRow(j);
	if(property.propertyUUID != null)
	{
	    var cell0 = row.insertCell(k);
	    dojo.attr(cell0, "style", {width: "20px"});
	    var imgHTML = "<img src='/public/scripts/dojoroot/dojox/image/resources/images/close_dark.png' border='0' align='absbottom' />";
	    var anchor = document.createElement("a")
	    anchor.innerHTML = imgHTML;
	    anchor.title = 'remove property from object';
	    anchor.href = "javascript:removePropertyOrLink('" + property.variableName + " / " + property.value + "','" + uuID + "', '" + originObjectType  + "', '" + property.propertyUUID + "','', 'property')";
	    cell0.appendChild(anchor);
	    ++k;
	}
	
        var cell1 = row.insertCell(k);
        dojo.attr(cell1, "style", {width: "200px", fontWeight: "bold"});
        cell1.innerHTML = property.variableName;
	++k;
	
        var cell2 = row.insertCell(k);   
	cell2.innerHTML = property.value;
	++k;
        
	var cell3 = row.insertCell(k);
        dojo.attr(cell3, "style", {width: "75px"});
        anchor = document.createElement("a");
        
    
	//alert(updateType + " = " + property.field + " - " + property.value);
	switch(updateType)
	{
	    case "Person":
		//alert(property.value);
		anchor.href ="javascript:initEditContextDialog('" +
		    property.propertyUUID + "', '" +
		    property.objectUUID + "', '" +
		    property.objectType + "', '" +
		    updateType + "','" +
		    property.field + "','" +
		    property.value +
		"')";
		break;
	    default:
		anchor.href ="javascript:initEditContextDialog('" +
		    property.propertyUUID + "', '" +
		    property.objectUUID + "', '" +
		    property.objectType + "', '" +
		    updateType + "','" +
		    property.field +
		"','')";
		break;
	}
	

        anchor.innerHTML = 'edit';
        cell3.appendChild(anchor);
    }
}

function removeProperty(objectUUID, propertyUUID)
{
    
}



function addParents(containerDiv, title, properties, targetObjectType, contextObject)
{
    //alert("addParents");
    //alert(properties.length);
    var uuID 		= contextObject.uuID;
    var originObjectType= contextObject.objectType;
    var headerDiv 	= document.createElement("div");
    headerDiv.innerHTML = title;        
    dojo.attr(headerDiv, "class", "tblHeaderBlue");
    if(BrowserDetect.browser == "Explorer")
        dojo.attr(headerDiv, "style", {width: "540px"});
    
    var buttonDiv = document.createElement("div");
    containerDiv.appendChild(buttonDiv);
    editContextButton(buttonDiv, targetObjectType, uuID, originObjectType);
    containerDiv.appendChild(headerDiv);

    var table = document.createElement("table");
    dojo.attr(table, "class", "tblGeneric");
    dojo.attr(table, "cellSpacing", "0");
    dojo.attr(table, "cellpadding", "2");
    if(BrowserDetect.browser == "Explorer"){
        dojo.attr(table, "style", {width: "540px"});
        dojo.attr(buttonDiv, "style", "width:540px;text-align:left;");
    }
    else{        
        dojo.attr(table, "style", {width: "100%"});
        dojo.attr(buttonDiv, "style", "width:100%;text-align:left;");
    }
    
    containerDiv.appendChild(table);
    containerDiv.appendChild(document.createElement("br"));
    
    //if no records found:
    if(properties.length == 0)
    {
        var row = table.insertRow(0);
        var cell1 = row.insertCell(0);
        cell1.innerHTML = "No children items found.";
	//addNewLinkButton(cell1, targetObjectType, uuID, originObjectType);
    }
    //for each data record:
    //property.linkName, property.name, property.id, property.objectType
    var row = table.insertRow(0);
    var prefixSlash = "";
    
    for (j=0; j < properties.length; j++)
    {
        var property = properties[j];
        var cell = row.insertCell(j);
        dojo.attr(cell, "style", {textAlign: "left"});
        anchor = document.createElement("a");
        anchor.innerHTML = prefixSlash + property.name;
        anchor.href = "javascript:showDetail('" + property.id + "', '" + targetObjectType + "')";
        cell.appendChild(anchor);
        
        if(j>0)
        {
            prefixSlash = "/ ";
        }
    }
    /*
    var row = table.insertRow(1);
    var cell = row.insertCell(0);    
    dojo.attr(cell, "style", {textAlign: "center"});
    editContextButton(cell, targetObjectType, uuID, originObjectType);
    */
    
    //alert('made it to the end');
}



function addChildrenLinks(containerDiv, title, properties, targetObjectType, contextObject)
{
    var uuID 		= contextObject.uuID;
    var originObjectType= contextObject.objectType;
    var headerDiv 	= document.createElement("div");
    headerDiv.innerHTML = title;        
    dojo.attr(headerDiv, "class", "tblHeaderBlue");
    if(BrowserDetect.browser == "Explorer")
        dojo.attr(headerDiv, "style", {width: "540px"});
    containerDiv.appendChild(headerDiv);

    var table = document.createElement("table");
    dojo.attr(table, "class", "tblGeneric");
    dojo.attr(table, "cellSpacing", "0");
    dojo.attr(table, "cellpadding", "2");
    if(BrowserDetect.browser == "Explorer")
        dojo.attr(table, "style", {width: "540px"});
    else        
        dojo.attr(table, "style", {width: "100%"});
    
    containerDiv.appendChild(table);
    containerDiv.appendChild(document.createElement("br"));
    
    //if no records found:
    if(properties.length == 0)
    {
        var row = table.insertRow(0);
        var cell1 = row.insertCell(0);
        cell1.innerHTML = "No children items found.";
	//addNewLinkButton(cell1, targetObjectType, uuID, originObjectType);
    }
    //for each data record:
    //property.linkName, property.name, property.id, property.objectType
    for (j=0; j < properties.length; j++)
    {
        var property = properties[j];
        var row = table.insertRow(j);
        var cell = row.insertCell(0);
	var imgHTML = "<img src='/public/scripts/dojoroot/dojox/image/resources/images/close_dark.png' border='0' align='absbottom' />";
	var anchor = document.createElement("a")
	anchor.innerHTML = imgHTML;
	anchor.title = 'remove child item';
	anchor.href = "javascript:removeChild('" + property.name + "','" + uuID + "', '" + property.id + "')";
	cell.appendChild(anchor);
	cell.appendChild(document.createTextNode(' '));
	
        anchor = document.createElement("a");
        anchor.innerHTML = property.name;
        anchor.href = "javascript:showDetail('" + property.id + "', '" + targetObjectType + "')";
        cell.appendChild(anchor);
        
	cell.innerHTML += ' (' + property.linkClass + ' with ' + property.linkName + ')';
    }
    
}



function addLink(containerDiv, title, properties, targetObjectType, contextObject)
{
    var uuID 		= contextObject.uuID;
    var originObjectType= contextObject.objectType;
    var headerDiv 	= document.createElement("div");
    headerDiv.innerHTML = title;        
    dojo.attr(headerDiv, "class", "tblHeaderBlue");
    if(BrowserDetect.browser == "Explorer")
        dojo.attr(headerDiv, "style", {width: "540px"});
    containerDiv.appendChild(headerDiv);

    var table = document.createElement("table");
    dojo.attr(table, "class", "tblGeneric");
    dojo.attr(table, "cellSpacing", "0");
    dojo.attr(table, "cellpadding", "2");
    if(BrowserDetect.browser == "Explorer")
        dojo.attr(table, "style", {width: "540px"});
    else        
        dojo.attr(table, "style", {width: "100%"});
    
    containerDiv.appendChild(table);
    containerDiv.appendChild(document.createElement("br"));
    
    //if no records found:
    if(properties.length == 0)
    {
        var row = table.insertRow(0);
        var cell1 = row.insertCell(0);
        cell1.innerHTML = "No data was found.";
	//addNewLinkButton(cell1, targetObjectType, uuID, originObjectType);
    }
    //for each data record:
    //property.linkName, property.name, property.id, property.objectType
    for (j=0; j < properties.length; j++)
    {
        var property = properties[j];
        var row = table.insertRow(j);
        var cell = row.insertCell(0);
	var imgHTML = "<img src='/public/scripts/dojoroot/dojox/image/resources/images/close_dark.png' border='0' align='absbottom' />";
	var anchor = document.createElement("a")
	anchor.innerHTML = imgHTML;
	anchor.title = 'remove link from object';
	anchor.href = "javascript:removePropertyOrLink('" + property.name + "','" + uuID + "', '" + originObjectType  + "', '" + property.id + "','" + property.linkName + "', 'link')";
	cell.appendChild(anchor);
	cell.appendChild(document.createTextNode(' '));
	
        anchor = document.createElement("a");
        anchor.innerHTML = property.name;
        anchor.href = "javascript:showDetail('" + property.id + "', '" + targetObjectType + "')";
        cell.appendChild(anchor);
        
	cell.innerHTML += ' (' + property.linkName + ')';
    }
    var rowIndex = (properties.length == 0 ? 1 : properties.length)
    var row = table.insertRow(rowIndex);
    var cell = row.insertCell(0);    
    dojo.attr(cell, "style", {textAlign: "left"});
    addNewLinkButton(cell, targetObjectType, uuID, originObjectType);
}

var removeLinkConfirmDlg;
function removePropertyOrLink(propertyName, originUUID, originObjectType, targetUUID, linkName, propertyOrLink)
{
     if(dijit.byId('doLinkDelete') != null)
	dijit.byId('doLinkDelete').destroyRecursive();
    if(dijit.byId('cancelLinkDelete') != null)
	dijit.byId('cancelLinkDelete').destroyRecursive();
    if(dijit.byId('removeLinkConfirmDlg') != null)
	dijit.byId('removeLinkConfirmDlg').destroyRecursive();

    var dialogTitle = ""
    if(propertyOrLink == 'link')
	dialogTitle = "Remove Link Confirmation";
    else
	dialogTitle = "Remove Property Confirmation";
    
    removeLinkConfirmDlg = new dijit.Dialog({
	title: 	dialogTitle,
	style: 	"width: 300px; height: 200x",
	id:	"removeLinkConfirmDlg"
    });

    var theDiv 	= document.createElement("div");
    theDiv.id 	= document.createElement("removeLinkDiv");
    theDiv.innerHTML = "Are you sure you want to remove the \"" + propertyName + "\" " + propertyOrLink + " from the current object?<br />"
    theDiv.innerHTML += "<div style='text-align: center'><button id='doLinkDelete'></button>&nbsp;<button id='cancelLinkDelete'></button></div>";
    
    removeLinkConfirmDlg.setContent(theDiv);

    if(propertyOrLink == 'link')
    {
	var btnDoLinkDelete = new dijit.form.Button({
		    label: "Delete Link",
		    onClick: function()
		    {
			var myAjax = new Ajax.Request('/edit/remove-link',
			    { method: 'get', parameters: {
				projectUUID: importer.currentProject.uuID,
				originObjectType: originObjectType,
				originUUID: originUUID,
				targetUUID: targetUUID,
				linkType: linkName
			    },
			    onComplete: removeLinkComplete }
			);
		    }
	}, "doLinkDelete");
    }
    else
    {
	var btnDoLinkDelete = new dijit.form.Button({
		    label: "Delete Property",
		    onClick: function()
		    {
			//alert(importer.currentProject.uuID + " - " + originObjectType + " - " + originUUID + " - " + targetUUID);
			var myAjax = new Ajax.Request('/edit/remove-property',
			    { method: 'get', parameters: {
				projectUUID: importer.currentProject.uuID,
				originObjectType: originObjectType,
				uuID: originUUID,
				propertyUUID: targetUUID
			    },
			    onComplete: removeLinkComplete }
			);
		    }
	}, "doLinkDelete");
    }

    var btnCancelLinkDelete = new dijit.form.Button({
                label: "Cancel",
		onClick: function()
		{
		    removeLinkConfirmDlg.hide();    
		}
    }, "cancelLinkDelete");
   
    removeLinkConfirmDlg.show();    
}

function removeLinkComplete(response)
{
    //alert(response.responseText);
    
    var contextObject = dojo.fromJson(response.responseText);
    showDetail(contextObject.uuID, contextObject.objectType);
    dijit.byId('removeLinkConfirmDlg').hide();
}

function addNewLinkButtonSarah(cell, targetObjectType, uuID, originObjectType)
{
    for(i=0; i < recordStoreArray.length; i++)
    {
        var rs = recordStoreArray[i];            
        if(rs.name.indexOf(targetObjectType) != -1)
	{            
            var addNewLinkButtonWorker = function(items, request)
            {
                //alert(items.length + " - " + rs.name);
                if(items.length > 0)
                {
                    //alert(targetObjectType);
                    var imgHTML = "<img src='/public/scripts/dojoroot/dojo/resources/images/dndCopy.png' border='0' align='absmiddle' />";
                    anchor = document.createElement("a")
                    anchor.innerHTML = imgHTML;
                    anchor.href = "javascript:editItemContext('" + rs.name + "', '" + uuID + "','" + originObjectType + "')";
                    anchor.title = 'add new link to object';
                    cell.appendChild(anchor);
                    var textNode = document.createTextNode(" add new " + rs.name + " link");
                    cell.appendChild(textNode);
                    cell.appendChild(document.createElement("br"));
                    //updateItem(items[i]);
                }
                else
                {
                    var textNode = document.createTextNode("there are no \"" + targetObjectType + "\" objects in the database for this project.");
                    if(cell.innerHTML.length == 0)
                        cell.appendChild(textNode);    
                }
            }
            //alert("fetching...");
            var request = rs.store.fetch({onComplete: addNewLinkButtonWorker});        
        }
    }
}

function addNewLinkButton(cell, targetObjectType, uuID, originObjectType)
{
    var imgHTML = "<img src='/public/scripts/dojoroot/dojo/resources/images/dndCopy.png' border='0' align='absmiddle' />";
    anchor = document.createElement("a")
    anchor.innerHTML = imgHTML;
    anchor.href = "javascript:editItemContext('" + targetObjectType + "', '" + uuID + "','" + originObjectType + "')";
    anchor.title = 'add new link to object';
    cell.appendChild(anchor);
    var textNode = document.createTextNode(" add new " + targetObjectType + " link");
    cell.appendChild(textNode);    
}



function editContextButton(cell, targetObjectType, uuID, originObjectType)
{
    var imgHTML = "<img src='/public/images/edit_context_icon.png' border='0' align='absmiddle' />";
    anchor = document.createElement("a")
    anchor.innerHTML = imgHTML;
    anchor.href = "javascript:editItemContext('" + targetObjectType + "', '" + uuID + "','" + originObjectType + "')";
    anchor.title = 'Merge, change parents, add links to: ' + shown_item_name;
    cell.appendChild(anchor);
    var textNode = document.createTextNode('Edit context of ' + shown_item_name);
    cell.appendChild(textNode);    
}

function prepEditPerson(containerDiv)
{
    var uuID 		= shown_item_id;
    
    var buttonDiv = document.createElement("div");
    dojo.attr(buttonDiv, "style", {textAlign: "left"});
    containerDiv.appendChild(buttonDiv);
    editPersonButton(buttonDiv, uuID);
    containerDiv.appendChild(buttonDiv);
}

function editPersonButton(cell, uuID)
{
   
    var imgHTML = "<img src='/public/images/edit_context_icon.png' border='0' align='absmiddle' />";
    anchor = document.createElement("a")
    anchor.innerHTML = imgHTML;
    anchor.href = "javascript:getPeopleList('" + uuID + "', '" + shown_item_name + "')";
    anchor.title = 'Merge, split combined people, delete, edit links to: ' + shown_item_name;
    cell.appendChild(anchor);
    var textNode = document.createTextNode('Edit this person: ' + shown_item_name);
    cell.appendChild(textNode);
    //alert("here " +uuID);
}


//this gets a list of people relating to this project
var act_person_id = null;
var act_person_name = null;
var personList = null;
var selpersonList = null;

function getPeopleList(uuID, shown_item_name){
    
    selpersonList = null;
    personList = null;
    selpersonListUL = null;
    act_person_id = uuID;
    act_person_name = shown_item_name;
    var myAjax = new Ajax.Request('importer/edit-transformed-data/getpeoplelist',
	{
	    method: 'get',
	    parameters: {
            projectUUID: importer.currentProject.uuID
	},
	onComplete: newPersonList }
    );    
}


function newPersonList(response){
    personList = null;
    personList = dojo.fromJson(response.responseText);
    editPersonWin();
}

function usePerson(use_id, use_name){
    var newPerson = new Array(use_id, use_name);
    if(selpersonList != null){
        selpersonList.push(newPerson);
    }
    else{
        selpersonList = new Array(newPerson);
    }
    
    dojo.byId("selPersonArea").innerHTML = null;
    var selPeopleTitle = document.createElement("p");
    selPeopleTitle.innerHTML = "Selected people to use (" + selpersonList.length + ")";
    dojo.byId("selPersonArea").appendChild(selPeopleTitle);
    selpersonListUL = document.createElement("ul");
    
    var newListInnerHTML = "";
    for (pp=0; pp < selpersonList.length; pp++){
        var selpersonItem = document.createElement("li");
        selpersonItem.innerHTML = selpersonList[pp][1];
        selpersonListUL.appendChild(selpersonItem);
    }
    
    dojo.byId("selPersonArea").appendChild(selpersonListUL);
    var selPeopleOptions = document.createElement("p");
    var selPeopleAction = document.createElement("a");
   
   if(selpersonList.length>1){
    selPeopleAction.href = "javascript:splitPerson(" + selpersonList.length + ")";
    selPeopleAction.innerHTML = "Spit <em>" + act_person_name + "</em> into these " + selpersonList.length + " persons";
   }
   else{
    selPeopleAction.href = "javascript:mergePerson()";
    selPeopleAction.innerHTML = "Merge <em>" + act_person_name + "</em> into " + selpersonList[0][1]; 
   }
   
    selPeopleOptions.appendChild(selPeopleAction);
    dojo.byId("selPersonArea").appendChild(selPeopleOptions);
}


function splitPerson(numPeople){
    
    var okContinueBox = confirm("Do you really want to split " + act_person_name + " into " +  numPeople + " selected persons?");
    
    var keepIDs = "";
    for (pp=0; pp < selpersonList.length; pp++){
        if(pp==0){
            keepIDs = selpersonList[pp][0];
        }
        else{
            keepIDs = keepIDs + "," + selpersonList[pp][0];
        }
    }
    
    if (okContinueBox == true){
 
        var myAjax = new Ajax.Request('importer/edit-transformed-data/multipersonsplit',
	    {
		method: 'post',
		parameters: {
		    badID: act_person_id,
		    goodID: keepIDs,
                    projectUUID: importer.currentProject.uuID
		},
		onComplete: splitPersonResults }
	    );
    }
    
    
}


function splitPersonResults(response){
    linkSelector.close();
    var keepIDs = dojo.fromJson(response.responseText);
    var outputView = "Success good ids: ";
    for (pp=0; pp < keepIDs.length; pp++){
        outputView = outputView  + " " + pp +":" + keepIDs[pp];
    }
    alert(outputView);
    initEditPane();
}


/*
---------------------------------------------------------------
this brings up a window with people listed and edit options for the selected person

----------------------------------------------------------------
*/
var selPeopleActDiv      = null;
function editPersonWin(){
    
    var personName = act_person_name;
    
    if(linkSelector != null)
    {
	linkSelector.destroy();
	linkSelector = null;
    }
    
    var pos = dojo.coords(dojo.byId(mainTabContainer.selectedChildWidget.id));
    var left = pos.x + 240;
    var top = pos.y + 100;
    var styleString = 'width: 475px; height: 400px; left: ' + left + 'px; top: ' + top + 'px;';
    var linkSelector = createFloatingPane('linkSelector', styleString, 'People', true);
    var mainDiv = document.createElement("div");
    var headerLinkDiv = document.createElement("div");
    selPeopleActDiv = document.createElement("div");
    selPeopleActDiv.setAttribute("style", "width:220px;float:right;");
    selPeopleActDiv.setAttribute("id", "selPersonArea");
    var bodyLinkDiv 	= document.createElement("div");
    bodyLinkDiv.setAttribute("style", "width:220px;float:left;margin-left:10px;height:380px;overflow:auto;");
    var bodyLinkTitleDiv= document.createElement("div");
    var personListDiv 	= document.createElement("div");
    var personListUL = document.createElement("ul");
    personListDiv.appendChild(personListUL);
    linkSelector.setContent(mainDiv);
    mainDiv.appendChild(headerLinkDiv);
    headerLinkDiv.innerHTML = "<strong>Edit " + personName + "<br /></strong>";
    mainDiv.appendChild(bodyLinkDiv);
    bodyLinkDiv.appendChild(bodyLinkTitleDiv);
    bodyLinkTitleDiv.innerHTML = "<br /><strong>(1) Select a Person:<br /></strong>";
    bodyLinkDiv.appendChild(personListDiv);
    
    //prepare list of selected people
    mainDiv.appendChild(selPeopleActDiv);
    
    //list of people to select
    for (j=0; j < personList.length; j++){
        var personItemDiv = document.createElement("li");
        var personADiv = document.createElement("a");
        personADiv.href = "javascript:usePerson('" + personList[j].id + "', '" + personList[j].name + "')";
        personADiv.innerHTML = personList[j].name;
        personItemDiv.appendChild(personADiv);
        personListUL.appendChild(personItemDiv);
    }
    
    
}





/*
---------------------------------------------------------------
this brings up a tree to edit context relations for a viewed item

----------------------------------------------------------------
*/
var linkSelector 	= null;
var linkSelectorTree	= null;
var headerLinkDiv	= null;
var selSpaceActDiv      = null;
function editItemContext(targetObjectType, uuID, originObjectType)
{
    //alert('1');
    var treeDiv;
    if(linkSelectorTree != null)
	linkSelectorTree.destroyRecursive(true);
	
    if(linkSelector != null)
    {
	linkSelector.destroy();
	linkSelector = null;
    }
    //alert('2');
    for(i=0; i < recordStoreArray.length; i++)
    {
	var rs = recordStoreArray[i];
        //alert(rs.name + " - " + targetObjectType);
	if(rs.name.indexOf(targetObjectType) != -1)
	{	    
	    //alert('3');
	    //create a floating pane:
	    if(linkSelector == null)
	    {
		
                var pos = dojo.coords(dojo.byId("contentCell"));
                var left = pos.x + 240;
		var top = pos.y + 200;
		styleString = 'width: 275px; height: 200px; left: ' + left + 'px; top: ' + top + 'px;';
		linkSelector = createFloatingPane('linkSelector', styleString, targetObjectType, true);
		var mainDiv 	= document.createElement("div");
		headerLinkDiv 	= document.createElement("div");
		selSpaceActDiv = document.createElement("div");
                selSpaceActDiv.setAttribute("style", "width:220px;float:right;");
                bodyLinkDiv 	= document.createElement("div");
                bodyLinkDiv.setAttribute("style", "width:220px;float:left;margin-left:10px;height:380px;overflow:auto;");
		bodyLinkTitleDiv= document.createElement("div");
		treeDiv 	= document.createElement("div");
		linkSelector.setContent(mainDiv);
		mainDiv.appendChild(headerLinkDiv);
                headerLinkDiv.innerHTML = "<strong>Edit Spatial Relationships<br /></strong>";
		mainDiv.appendChild(bodyLinkDiv);
		bodyLinkDiv.appendChild(bodyLinkTitleDiv);
		bodyLinkTitleDiv.innerHTML = "<br /><strong>(1) Select a Location or Object:<br /></strong><p><a href='javascript:editGeoSpace(\""+ uuID + "\")'>Update GeoSpatial Coordinates</a></p>";
		bodyLinkDiv.appendChild(treeDiv);		
		//alert('4');
	    }
	    var treeModel = new dijit.tree.ForestStoreModel({
		store: rs.store,
		query: {type: '0'},
		rootId: "root",
		rootLabel: targetObjectType,
		childrenAttrs: ["children"]
	    });
	    
	    //alert('5');

            /*
	    var linkTree = new dijit.Tree(
		{
		    model: treeModel,
		    onClick: function(arg)
		    {
			var myAjax = new Ajax.Request('/edit/add-object-link',
			    { method: 'get', parameters: {
				projectUUID: importer.currentProject.uuID,
				originUUID: uuID,
				originObjectType: originObjectType,
				targetObjectType: targetObjectType,
				targetUUID: arg.id,
				linkType: dijit.byId('ddRelationshipLink').value
			    },
			    onComplete: addLinkComplete }
			);
			linkSelector.hide();
			
			
		    },
		    showRoot: false
		},
		treeDiv
	    );
            */
	    
            var linkTree = new dijit.Tree(
		{
		    model: treeModel,
		    onClick: ChildShowEditOptions,
		    showRoot: false
		},
		treeDiv
	    );
            
            
	    		
	    //link section:
            mainDiv.appendChild(selSpaceActDiv);
            /*
	    linkTypeTitleDiv.innerHTML = "<br/><strong>2) Select a Link Type:<br /></strong>";
	    var myAjax = new Ajax.Request('importer/relationships/get-relationships-for-dropdown',
	    {
		method: 'get',
		parameters: {
		    dataTableName: importer.currentProject.dataTableName,
		    dialogType: 'link'
		},
		onComplete: showAddLinksRelationshipDD }
	    );
            */
	    break;
	}
    }
}





function ChildShowEditOptions(arg, node)
{
    
    if(arg.root == null || !arg.root){
        importer.actNode = arg;
        //alert(arg.isStub);
        if(arg.isStub){
            addChildren(arg.id, arg.type);
        }
        ShowEditOptions(arg.id, arg.name);
    }
}

var EditPartnerName = null;
function ShowEditOptions(spaceID, spaceLabel){
    //alert(spaceID + " " + spaceLabel);
    EditPartnerName = spaceLabel;
    var optionTitle = "<br/><strong>(2) Select an action for <em>" + shown_item_name + "</em>:</strong><br />";
    var optionChoices ="<p><a href='javascript:mergeItems(\""+ shown_item_id  +"\", \""+ spaceID +"\")'>Merge with "+ spaceLabel + "</a></p>";
    optionChoices += "<p><a href='javascript:newItemParent(\""+ shown_item_id  +"\", \""+ spaceID +"\")'>Become contained within "+ spaceLabel + "</a></p><br/>";
    
    selSpaceActDiv.innerHTML = optionTitle + optionChoices;
    
	    var myAjax = new Ajax.Request('importer/relationships/get-relationships-for-dropdown',
	    {
		method: 'get',
		parameters: {
		    dataTableName: importer.currentProject.dataTableName,
		    dialogType: 'link'
		},
		onComplete: showAddLinksRelationshipDD }
	    );
    
    
}


function newItemParent(childID, newParentID){
    
    var okContinueBox = confirm("Do you really want to make " + shown_item_name + " a child of " +  EditPartnerName);
    
    if (okContinueBox == true){
 
        var myAjax = new Ajax.Request(importer.rootURI+'/edit-transformed-data/change-parents',
	    {
		method: 'post',
		parameters: {
		    childID: childID,
		    newParentID: newParentID,
                    projectUUID: importer.currentProject.uuID
		},
		onComplete: parentItemResults }
	    );
    }
    
}

function parentItemResults(response){
    linkSelector.close();
    var theRecords = dojo.fromJson(response.responseText);
    alert(shown_item_name + " now a child of: " +  EditPartnerName);
    initEditPane();
}



function mergeItems(oldID, keepID){
    
    var okContinueBox = confirm("Do you really want to merge " + shown_item_name + " into " +  EditPartnerName);
    
    if (okContinueBox == true){
 
        var myAjax = new Ajax.Request(importer.rootURI+'/edit-transformed-data/merge-items',
	    {
		method: 'post',
		parameters: {
		    oldID: oldID,
		    keepID: keepID,
                    projectUUID: importer.currentProject.uuID
		},
		onComplete: mergeItemsResults }
	    );
    }
    
}

function editGeoSpace(itemID){
    // programatically created FloatingPane with srcNode ref

    var styleString = 'width: 275px; height: 200px; left: ' + 240 + 'px; top: ' + 200 + 'px;';
    var geoPane = createFloatingPane('geoPane', styleString, "Add Geo Coordinates and Chronology", true);
    var mainDiv 	= document.createElement("div");
    var bodyLinkDiv 	= document.createElement("div");
    var geoForm = "<table>";
    geoForm = geoForm + "<tr><td COLSPAN='2'><em>Update / Add Coordinates</em></td></tr>";
    geoForm = geoForm + "<tr><td>Lat:</td><td><input id='geoLat' type='text' name='geoLat' /></td></tr>";
    geoForm = geoForm + "<tr><td>Lon:</td><td><input id='geoLon' type='text' name='geoLon' /></td></tr>";
    geoForm = geoForm + "<tr><td COLSPAN='2'><a href='javascript:addGeoSpace(\""+ itemID + "\")'>Update GeoSpatial Coordinates</a></td></tr>";
    geoForm = geoForm + "<tr><td COLSPAN='2'>&nbsp;</td></tr>";
    geoForm = geoForm + "<tr><td COLSPAN='2'><em>Update / Add Chronology (Years BCE/CE)</em></td></tr>";
    geoForm = geoForm + "<tr><td>Start Year:</td><td><input id='chronoStart' type='text' name='chronoStart' /></td></tr>";
    geoForm = geoForm + "<tr><td>End Year:</td><td><input id='chronoEnd' type='text' name='chronoEnd' /></td></tr>";
    geoForm = geoForm + "<tr><td COLSPAN='2'><a href='javascript:addTime(\""+ itemID + "\")'>Update Chronology</a></td></tr>";
    geoForm = geoForm + "</table>";
    bodyLinkDiv.innerHTML = geoForm;
    mainDiv.appendChild(bodyLinkDiv);
    geoPane.setContent(mainDiv); 
}


function addGeoSpace(itemID){
    var geoLat = $('geoLat').value;
    var geoLon = $('geoLon').value;
    //alert("geolat=" + geoLat + " geoLon=" + geoLon);
    var myAjax = new Ajax.Request('importer/edit-transformed-data/add-geo',
    {
	method: 'post',
	parameters: {
	itemID: itemID,
	geoLat: geoLat,
        geoLon: geoLon,
        projectUUID: importer.currentProject.uuID
    },
	onComplete: initEditPane }
    );
    
}

function addTime(itemID){
    var chronoStart = $('chronoStart').value;
    var chronoEnd = $('chronoEnd').value;
    //alert("geolat=" + geoLat + " geoLon=" + geoLon);
    var myAjax = new Ajax.Request('importer/edit-transformed-data/add-time',
    {
	method: 'post',
	parameters: {
	itemID: itemID,
	chronoStart: chronoStart,
        chronoEnd: chronoEnd,
        projectUUID: importer.currentProject.uuID
    },
	onComplete: initEditPane }
    );
    
}

function mergeItemsResults(response){
    linkSelector.close();
    var theRecords = dojo.fromJson(response.responseText);
    alert(shown_item_name + " merged into: " +  EditPartnerName);
    initEditPane();
}


function multipersonSplit(badID, goodIDs){
      
    var okContinueBox = confirm("Do you really want to split " + shown_pers_name + " into others");
    
    if (okContinueBox == true){
 
        var myAjax = new Ajax.Request('importer/edit-transformed-data/multipersonsplit',
	    {
		method: 'post',
		parameters: {
		    oldID: badID,
		    keepID: goodIDs,
                    projectUUID: importer.currentProject.uuID
		},
		onComplete: mergeItemsResults }
	    );
    }
    
}








var ddRelationshipNew;
function showAddLinksRelationshipDD(response)
{
    //alert(response.responseText);
    if(dijit.byId('ddRelationshipLink') != null)
	dijit.byId('ddRelationshipLink').destroyRecursive(); 

    var ddRelationshipLink 	= document.createElement("input");
    ddRelationshipLink.id	= 'ddRelationshipLink';
    selSpaceActDiv.appendChild(ddRelationshipLink);

    var theRecords      = dojo.fromJson(response.responseText);
    var theRecordStore  = new dojo.data.ItemFileWriteStore({data: theRecords, clearOnClose: true});

    ddRelationshipNew	= new dijit.form.ComboBox(
	{
	    id: "ddRelationshipLink",
	    name: "ddRelationshipLink",
	    store: theRecordStore,
	    searchAttr: "RELATIONSHIP_TYPE",
	    disabled: false
	}, "ddRelationshipLink");  
    ddRelationshipNew.attr('value', theRecords.items[0].RELATIONSHIP_TYPE);
}

function addLinkComplete(response)
{
    //alert(response.responseText);
    var contextObject = dojo.fromJson(response.responseText);
    showDetail(contextObject.uuID, contextObject.objectType);
}


function initEditContextDialog(propertyUUID, objectUUID, objectType, updateType, fieldName, valText)
{
    //alert(importer.currentProject.uuID);
    var myAjax = new Ajax.Request('/edit/get-context-attribute',
        { method: 'get', parameters:
	    {
		projectUUID: importer.currentProject.uuID,
		propertyUUID: propertyUUID,
		objectUUID: objectUUID,
		objectType: objectType,
		updateType: updateType,
		fieldName: fieldName,
		valText: valText
	    },
        onComplete: editContextDialogShow }
    );
}


function editContextDialogShow(response)
{
    //alert(response.responseText);
    var property = dojo.fromJson(response.responseText);
    //alert(property.objectType);
    
    var propertyUUID 	= property.propertyUUID;
    var objectUUID	= property.objectUUID;
    var varText		= property.variableName;
    var valText		= property.value;
    var objectType	= property.objectType;
    var updateType	= property.updateType;
    var fieldName	= property.field;
    var valCount	= property.valCount;
    
    var dialog = dijit.byId('dialogEditContext');    
    dojo.attr(dialog, 'title', 'Update Selected Property');
    dialog.show();
    
    if(updateType == 'Property')
    {
	dojo.attr(dojo.byId('variableSpace'), "style", {visibility: "visible", height: "auto", overflow: "auto"});
	dojo.attr(dojo.byId('allValuesSpace'), "style", {visibility: "visible", height: "auto", overflow: "auto"});	
    }
    else
    {
	dojo.attr(dojo.byId('variableSpace'), "style", {visibility: "hidden", height: "1px", overflow: "hidden"});
	dojo.attr(dojo.byId('allValuesSpace'), "style", {visibility: "hidden", height: "1px", overflow: "hidden"});	
    }
    
    if(valText.length > 40)
	dojo.attr(dojo.byId('valText'), "style", {height: "150px"});
    else
	dojo.attr(dojo.byId('valText'), "style", {height: "25px"});
    
    dojo.byId('propertyUUID').value	= propertyUUID;
    dojo.byId('objectUUID').value	= objectUUID;
    dojo.byId('varText').value		= varText;
    dojo.byId('valText').value		= valText;
    dojo.byId('oldValText').value	= valText;
    dojo.byId('objectType').value	= objectType;
    dojo.byId('updateType').value	= updateType;
    dojo.byId('fieldName').value	= fieldName;
    if(valCount != null && valCount.length > 0)
	dojo.byId('valCount').innerHTML	= ' (' + valCount + ' value(s) will be updated).';
    else
	dojo.byId('valCount').innerHTML	= '';
}


function updateContextItem()
{
    var txtPropertyUUID     = dojo.byId('propertyUUID');
    var txtObjectUUID       = dojo.byId('objectUUID');
    var txtVarText          = dojo.byId('varText');
    var txtValText          = dojo.byId('valText');
    var txtOldValText       = dojo.byId('oldValText');
    var txtObjectType       = dojo.byId('objectType');
    var txtUpdateType       = dojo.byId('updateType');
    var txtFieldName        = dojo.byId('fieldName');
    var rbYes               = dojo.byId('updateAllYes');
    var rbNo                = dojo.byId('updateAllNo');
    var updateAll           = rbYes.checked ? true : false;
    //alert(updateAll);
    
    var myAjax = new Ajax.Request('/edit/update-context-item',
        { method: 'get', parameters: {
            propertyUUID: txtPropertyUUID.value,
            objectUUID: txtObjectUUID.value,
            varText: txtVarText.value,
            valText: txtValText.value,
            oldValText: txtOldValText.value,
            objectType: txtObjectType.value,
	    updateType: txtUpdateType.value,
	    fieldName: txtFieldName.value,
            updateAll: updateAll
        },
        onComplete: updateContextItemComplete }
    );
}

function updateContextItemComplete(response)
{
    alert(response.responseText);
    
    var contextObject = dojo.fromJson(response.responseText);
    showDetail(contextObject.uuID, contextObject.objectType);
    dijit.byId('dialogEditContext').hide();
}

