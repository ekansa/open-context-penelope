
function updateNavigation()
{
    var myAjax = new Ajax.Request(importer.rootURI + '/utils/get-navigation',
        {
            method: 'get',
            parameters:
            {
                projectUUID: importer.currentProject.uuID,
                dataTableName: importer.currentProject.dataTableName,
                controllerName: importer.controllerName
            },
        onComplete: updateNavigationComplete }
    );    
}

function updateNavigationComplete(response)
{
    //alert(response.responseText);
    //clear out contents:
    dojo.require("dijit.Tooltip");
    document.getElementById("navContainer").innerHTML = "";
    
    navigationObj   = dojo.fromJson(response.responseText);
    pages           = navigationObj.pages;
    enabledPageIDs  = navigationObj.enabledPageIDs;
    checklist       = navigationObj.checklist;
    errorMessages   = navigationObj.errorMessages;
    warningMessages = navigationObj.warningMessages;
    successMessages = navigationObj.successMessages;
    currentPageID   = navigationObj.currentPageID;
    
    var table = document.createElement("table");
    dojo.attr(table, "class", "navTableNew");
    dojo.attr(table, "cellSpacing", "0");
    dojo.attr(table, "cellpadding", "0");
    dojo.attr(table, "style", "width: 190px;");
    //dojo.attr(table, "border", "1");
    
    for(i=0; i < pages.length; i++)
    {
        var row = table.insertRow(i);
        
        //create first cell:
        var cell0 = row.insertCell(0);
        dojo.attr(cell0, "style", "width:18px; vertical-align: middle; text-align: center;padding-right: 2px;");
        var msgDiv = document.createElement("div");
        dojo.attr(msgDiv, "id", "nav_div0_" + i); 
        cell0.appendChild(msgDiv);
        //dojo.attr(msgDiv, "style", "border: solid 1px #000");       
        //dojo.attr(cell0, "title", messages[pages[i]]);
        //if(checklist[pages[i]])
        if(errorMessages[pages[i]] != null)
        {
            var img = document.createElement("img");
            dojo.attr(img, "src", "/public/images/errorIcon.png");      
            msgDiv.appendChild(img); 
        }
        else if(warningMessages[pages[i]] != null)
        {            
            var img = document.createElement("img");
            dojo.attr(img, "src", "/public/images/warning.png");            
            msgDiv.appendChild(img);    
        }
        else if(successMessages[pages[i]] != null)
        {
            var img = document.createElement("img");
            dojo.attr(img, "src", "/public/images/checkmarkSmall.png");      
            //dojo.attr(img, "src", "/public/scripts/dojoroot/dojox/off/resources/checkmark.png");
            msgDiv.appendChild(img);
        }
        else
        {
            /*var msg = document.createElement("div");  
            dojo.attr(msg, "innerHTML", "*");
            dojo.attr(msg, "class", "asterik");
            msgDiv.appendChild(msg);*/
            //var img = document.createElement("img");
            //dojo.attr(img, "src", "/public/images/warning.png");            
            //msgDiv.appendChild(img);
            msgDiv.innerHTML = "&nbsp;";
        }
        
        //create second cell:
        var cell1 = row.insertCell(1);
        dojo.attr(cell1, "id", "nav_cell1_" + i);
        if(i == currentPageID)
        {
            dojo.attr(cell1, "class", "cell1 cell1Current");
        }
        else if(contains(enabledPageIDs, i))
        {
            dojo.attr(cell1, "class", "cell1 cell1Active");
            dojo.attr(cell1, "onmouseover", "doHoverCell1(this," + i + ")");
            dojo.attr(cell1, "onmouseout", "clearHoverCell1(this," + i + ")");
            dojo.attr(cell1, "onclick", "getPage(" + i + ")");
        }
        else
        {
            dojo.attr(cell1, "class", "cell1 cell1Disabled");    
        }
        cell1.innerHTML = (i+1);
        
        
        //create third cell:
        var cell2 = row.insertCell(2);        
        dojo.attr(cell2, "id", "nav_cell2_" + i);
        if(i == currentPageID)
        {
            dojo.attr(cell2, "class", "cell2 cell2Current");
        }
        else if(contains(enabledPageIDs, i))
        {
            dojo.attr(cell2, "class", "cell2");
            dojo.attr(cell2, "onmouseover", "doHoverCell2(this," + i + ")");
            dojo.attr(cell2, "onmouseout", "clearHoverCell2(this," + i + ")");
            dojo.attr(cell2, "onclick", "getPage(" + i + ")");
        }
        else
        {
            dojo.attr(cell2, "class", "cell2 cell2Disabled");    
        }
        cell2.innerHTML = pages[i];   
    }
    document.getElementById("navContainer").appendChild(table);
    appendToolTips(errorMessages, pages);
    appendToolTips(warningMessages, pages);
    appendToolTips(successMessages, pages);
    
}

function appendToolTips(messages, pages)
{
    if(messages.length == 0)
        return;
    //alert(messages + ' - ' + pages);
    for(i=0; i < pages.length; i++)
    {
        var msg = messages[pages[i]];
        if(msg != null && msg.length > 0)
        {
            //alert(msg);
            dojo.attr(document.getElementById("nav_div0_" + i), "style", "cursor: help;"); 
            new dijit.Tooltip({
               connectId: ["nav_div0_" + i],
               label: msg,
               position: "above"
            });
        }
    }
}


function doHoverCell1(elem, idx)
{
    //alert('hi');
    elem.style.cursor                                       = "pointer";
    elem.className                                          = "cell1 cell1Current";
    document.getElementById("nav_cell2_" + idx).className   = "cell2 cell2Current";
}

function clearHoverCell1(elem, idx)
{
    elem.style.cursor                                       = "auto";
    elem.className                                          = "cell1 cell1Active";
    document.getElementById("nav_cell2_" + idx).className   = "cell2 cell2Active";    
}

function doHoverCell2(elem, idx)
{
    elem.style.cursor                                       = "pointer";
    elem.className                                          = "cell2 cell2Current";
    document.getElementById("nav_cell1_" + idx).className   = "cell1 cell1Current";
}

function clearHoverCell2(elem, idx)
{
    elem.style.cursor                                       = "auto";
    elem.className                                          = "cell2 cell2Active";
    document.getElementById("nav_cell1_" + idx).className   = "cell1 cell1Active";
}

function disableForm()
{
    dojo.query('button', document).forEach(
        function(inputElem){
            dijit.byId(inputElem.id).attr("disabled",true);
        }
      )
}

function enableForm()
{
    dojo.query('button', document).forEach(
        function(inputElem){
            dijit.byId(inputElem.id).attr("disabled",false);
        }
      )
}
