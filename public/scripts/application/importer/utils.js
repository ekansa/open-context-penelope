function contains(a, obj){
  for(var i = 0; i < a.length; i++) {
    if(a[i] === obj){
      return true;
    }
  }
  return false;
}

function getProjectTable()
{
    var table = document.createElement("table");
    dojo.attr(table, "class", "tblGeneric");
    dojo.attr(table, "cellSpacing", "0");
    dojo.attr(table, "cellpadding", "2");
    dojo.attr(table, "width", "100%");

    var row1 = table.insertRow(0);
    dojo.attr(row1, "class", "tblGenericRowOdd");
    var cell1 = row1.insertCell(0);
    cell1.innerHTML = "<strong>Project</strong>";
    
    var cell2 = row1.insertCell(1);
    cell2.innerHTML = "<strong>File Name</strong>";
    
    var cell3 = row1.insertCell(2);
    cell3.innerHTML = "<strong>Columns</strong>";
    
    var cell4 = row1.insertCell(3);
    cell4.innerHTML = "<strong>Rows</strong>";
    
    var row2 = table.insertRow(1);
    var cell5 = row2.insertCell(0);
    cell5.innerHTML = importer.currentProject.name;
    
    var cell6 = row2.insertCell(1);
    //alert(importer.currentProject.dataTableDesc);
    var desc = importer.currentProject.dataTableDesc;
    if(desc == null || desc == "")
        desc = "&nbsp;";
    //alert(desc.length + " - " + desc);
    //if(desc.length > 15)
    //    desc = desc.substring(0, 15) + "...";
    cell6.innerHTML = desc;
    
    var cell7 = row2.insertCell(2);
    cell7.innerHTML = importer.currentProject.numCols > 0 ? importer.currentProject.numCols : "&nbsp;";;
    
   
    var cell8 = row2.insertCell(3);
    cell8.innerHTML = importer.currentProject.numRows > 0 ? importer.currentProject.numRows : "&nbsp;";;
    
    //alert(table.innerHTML );
    
    return table;
}


function createFloatingPane(id, styleString, title, isClosable)
{
    //alert('create a floating pane!');
    var floatingPane = new dojox.layout.FloatingPane({
        style: styleString,
        id: id,
        dockable: false,
        closable: isClosable,
        title: title
    }, document.createElement('div'));

    //workaround for IE:
    if(BrowserDetect.browser == "Explorer")
        floatingPane.canvas.style.overflow = "auto";

    //startup and add to DOM:
    document.body.appendChild(floatingPane.domNode);
    floatingPane.startup();
    return floatingPane;
}

function createHelpMenuContainer(title)
{
    var help = dojo.byId("help")
    var pos = dojo.coords(dojo.byId("contentCell"));
    var left = pos.x + 200;
    if(help != null)
        help.destroy();
    styleString = 'width: 375px; height: 350px; left: ' + left + 'px; top: ' + pos.y + 'px;';
    help = createFloatingPane('help', styleString, title, true);
    return help;
}


function createTooltipDialog(parentContainerID, description)
{
    //var foo = new dijit.TooltipDialog(
    /*var tooltipDialog = new dijit.TooltipDialog({}, document.createElement('div'));
    dojo.byId(parentContainerID).appendChild(tooltipDialog.domNode);
    tooltipDialog.startup();
    return tooltipDialog;*/
    
    var tooltip = new dijit.Tooltip(
        {connectId: [parentContainerID],
            label: description
        }
    );
    //document.body.appendChild(tooltip.domNode);
    return tooltip;
}

function utils_writeListToContainer(theArray, theContainer)
{
    var ul = document.createElement("ul");
    theContainer.appendChild(ul);
    var messageStr = "";
    for(i=0; i < theArray.length; i++)
    {
        var li = document.createElement("li");
        li.innerHTML = theArray[i];
        ul.appendChild(li);
    }
}