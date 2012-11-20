var recordsToBeProcessed;
var totalRecordsProcessed   = 0;
var listProject;
var listSpace;
var listPeople;
var listResources;  
var listProperties;

function initXmlPane()
{
    //alert("initializing!");
    displayWizardHeader("GENERATE XML DOCUMENTS");
    displayState();
    displayXMLStatus();
}

function displayXMLStatus()
{
    //alert(importer.currentProject.uuID);
    var myAjax = new Ajax.Request('/xmlfeed/get-counts',
        { method: 'get', parameters: {projectUUID: importer.currentProject.uuID },
        onComplete: displayXMLStatusComplete }
    ); 
}


function displayXMLStatusComplete(response)
{
    try
    {
        //alert(response.responseText);
        container = document.getElementById("xmlStatusTable");
        container.innerHTML = "";
        xmlCountsObj   = dojo.fromJson(response.responseText);
        var xmlCounts = [
                         ['Project Object**', 'project_count'],
                         ['Spatial Objects', 'space_count'],
                         //['Diary Objects', 'diary_count'],
                         ['People', 'person_count'],
                         ['Resource Objects', 'resource_count'],
                         ['Property Objects', 'property_count']];
            
        var table = document.createElement("table");
        dojo.attr(table, "class", "tblGeneric");
        dojo.attr(table, "cellSpacing", "0");
        dojo.attr(table, "cellpadding", "2");
        //dojo.attr(table, "width", "100%");
        dojo.attr(table, "align", "center");
    
        var row = table.insertRow(0);
        dojo.attr(row, "class", "tblGenericRowOdd");
        
        var cella = row.insertCell(0);
        cella.innerHTML = "<strong>Object Type</strong>";
        
        var cellb = row.insertCell(1);
        cellb.innerHTML = "<strong>Unprocessed Records</strong>";
        
        var cellc = row.insertCell(2);
        cellc.innerHTML = "<strong>Processed Records</strong>";
        
        var totalNumberOfRecordsToBeProcessed = 0;
        for(i=0; i < xmlCounts.length; i++)
        {
            var row = table.insertRow(i+1);
            var cella = row.insertCell(0);
            cella.innerHTML = xmlCounts[i][0];
            
            var cellb = row.insertCell(1);
            var intVal      = parseInt(xmlCountsObj[xmlCounts[i][1] + "_pending"]);
            cellb.innerHTML = intVal;
            totalNumberOfRecordsToBeProcessed = totalNumberOfRecordsToBeProcessed + intVal;
            
            
            var cellc = row.insertCell(2);
            cellc.innerHTML = xmlCountsObj[xmlCounts[i][1] + "_completed"];  
        }
        
        //if recordsToBeProcessed is null, initialize it:
        if(recordsToBeProcessed == null)
            recordsToBeProcessed = totalNumberOfRecordsToBeProcessed;    
        
        container.appendChild(table);
        container.appendChild(document.createTextNode("** = Gets regenerated each time new data is converted to XML."))
    }
    catch(err)
    {
        alert(response.responseText);   
    }
}

function processObjects()
{
    var recordsToBeProcessed;
    var totalRecordsProcessed   = 0;
    var listProject             = null;
    var listSpace               = null;
    var listPeople              = null;
    
    processProjectObject();   
}

function processProjectObject()
{
    //alert(importer.currentProject.uuID);
    var myAjax = new Ajax.Request('/xmlfeed/process-project',
        { method: 'get', parameters: {projectUUID: importer.currentProject.uuID },
        onComplete: processProjectComplete }
    ); 
}

function processProjectComplete(response)
{
    try
    {
        var fileNames   = dojo.fromJson(response.responseText);
        var container = document.getElementById("xmlTransformResultsProject");
        
        if(listProject == null)
        {
            container.innerHTML = "<h2>Project XML Files</h2>";
            listProject = document.createElement("ul");
            container.appendChild(listProject);
        }
        displayResults(fileNames, listProject);
    
        processSpaceObjects();
    }
    catch(err)
    {
        alert(response.responseText);   
    }
}

function processSpaceObjects()
{
    //alert('processSpaceObjects(): ' + importer.currentProject.uuID);
    var myAjax = new Ajax.Request('/xmlfeed/process-space',
        { method: 'get', parameters: {projectUUID: importer.currentProject.uuID },
        onComplete: processSpaceComplete }
    ); 
}

function processSpaceComplete(response)
{
    try
    {
        //alert('processSpaceComplete(): ' + response.responseText);
        var fileNames   = dojo.fromJson(response.responseText);
        var container = document.getElementById("xmlTransformResultsSpace");
        
        if(listSpace == null)
        {
            container.innerHTML = "<h2>Spatial Containment XML Files</h2>";
            listSpace = document.createElement("ul");
            container.appendChild(listSpace);
        }
        displayResults(fileNames, listSpace);
        
        //keep processing until there are no more space objects left to process:
        if(fileNames.length > 0)
            processSpaceObjects();
        else
            processPeopleObjects()
    }
    catch(err)
    {
        alert(response.responseText);   
    }
}


function processPeopleObjects()
{
    //alert('processPeopleObjects(): ' + importer.currentProject.uuID);
    //var container = document.getElementById("xmlTransformResults");
    //container.innerHTML += "<h2>People XML Files</h2>";
    var myAjax = new Ajax.Request('/xmlfeed/process-people',
        { method: 'get', parameters: {projectUUID: importer.currentProject.uuID },
        onComplete: processPeopleObjectsComplete }
    );    
}


function processPeopleObjectsComplete(response)
{
    //alert(response.responseText);
    try
    {
        var fileNames   = dojo.fromJson(response.responseText);
        var container = document.getElementById("xmlTransformResultsPeople");
        if(listPeople == null)
        {
            container.innerHTML = "<h2>People XML Files</h2>";
            listPeople = document.createElement("ul");
            container.appendChild(listPeople);
        }
        displayResults(fileNames, listPeople);
        
        //keep processing until there are no more space objects left to process:
        if(fileNames.length > 0)
            processPeopleObjects();
        else
            processResourceObjects();
    }
    catch(err)
    {
        alert(response.responseText);   
    }
}

function processResourceObjects()
{
    var myAjax = new Ajax.Request('/xmlfeed/process-resources',
        { method: 'get', parameters: {projectUUID: importer.currentProject.uuID },
        onComplete: processResourcesComplete }
    );    
}

function processResourcesComplete(response)
{
    try
    {
        var fileNames   = dojo.fromJson(response.responseText);
        var container = document.getElementById("xmlTransformResultsResources");
        if(listResources == null)
        {
            container.innerHTML = "<h2>Property XML Files</h2>";
            listResources = document.createElement("ul");
            container.appendChild(listResources);
        }
        displayResults(fileNames, listResources);
        
        //keep processing until there are no more space objects left to process:
        if(fileNames.length > 0)
            processResourceObjects();
        else
            processPropertyObjects();
    }
    catch(err)
    {
        alert(response.responseText);   
    }
}

function processPropertyObjects()
{
    //alert('Processing property objects');
    var myAjax = new Ajax.Request('/xmlfeed/process-properties',
        { method: 'get', parameters: {projectUUID: importer.currentProject.uuID },
        onComplete: processPropertiesComplete }
    );    
}

function processPropertiesComplete(response)
{
    try
    {
        var fileNames   = dojo.fromJson(response.responseText);
        var container = document.getElementById("xmlTransformResultsProperties");
        if(listProperties == null)
        {
            container.innerHTML = "<h2>Property XML Files</h2>";
            listProperties = document.createElement("ul");
            container.appendChild(listProperties);
        }
        displayResults(fileNames, listProperties);
        
        //keep processing until there are no more space objects left to process:
        if(fileNames.length > 0)
            processPropertyObjects();
        else
            alert("Done!  Note:  Property XML not fully implemented.");
    }
    catch(err)
    {
        alert(response.responseText);   
    }
}

function updateProgressBar(numFilesProcessed)
{
    totalRecordsProcessed = totalRecordsProcessed + numFilesProcessed;
    var progressNum = totalRecordsProcessed/recordsToBeProcessed*100;
    //alert(progressNum + " - " + totalRecordsProcessed + " - " + recordsToBeProcessed);
    dataTransformProgress.update({ progress: progressNum });
}

function displayResults(fileNames, list)
{
    for(i=0; i < fileNames.length; i++)
    {        
        var li  = document.createElement("li");
        list.appendChild(li);
        var a   = document.createElement("a"); 
        dojo.attr(a, "href", "/public/xmlFiles/" +  fileNames[i]);
        dojo.attr(a, "innerHTML", fileNames[i]);
        dojo.attr(a, "target", "_blank");
        li.appendChild(a);    
    }
    //update the number of records needed to be processed:
    updateProgressBar(fileNames.length);
    displayXMLStatus();    
}