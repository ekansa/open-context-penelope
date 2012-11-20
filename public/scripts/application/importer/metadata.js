function initMetadataPane()
{
   //editProject();
   
   //alert(importer.currentProject.uuID);
   var myAjax = new Ajax.Request(importer.rootURI + '/metadata/get-project',
    {
	method: 'get',
	parameters: { projectUUID: importer.currentProject.uuID },
	onComplete: initMetadataForm }
    );
   displayState();
}

function initMetadataForm(response)
{
    //alert(response.responseText);
    var project = dojo.fromJson(response.responseText);
    dojo.byId('projName').value = project.name;
    dojo.byId('projSDes').value = project.shortDesc;
    dojo.byId('projLDes').value = project.longDesc;
    
    dojo.byId('rootName').value     = project.parentContextName;
    dojo.byId('rootID').value       = project.parentContextID;
    dojo.byId('rootClass').value    = project.parentContextClass;
    dojo.byId('noData').value       = project.noDataMessage;
    dojo.byId('projCreat').value    = project.metadata.creatorsString;
    dojo.byId('projSubs').value     = project.metadata.subjectsString;
   
    /*
    //alert(p);
    var metadata = project.metadataEntries;
    //alert(metadata);
    //return;
    for(i=0; i < metadata.length; i++)
    {
        alert(metadata[i].dcField + " - " + metadata[i].dcValue);
    }*/
}


function editProject(){
    //setActiveProject();
    //alert('describe project');
    var projID = importer.currentProject.uuID; 
    var myAjax = new Ajax.Request('/metadata/describe-project',
    {
	method: 'get',
	parameters: {
	projectUUID: projID
    },
	//onComplete: wizardInitData }
        onComplete: editProjectPane }
    );
}


function editProjectPane(response){
    //alert(response.responseText);
    
    var projData = dojo.fromJson(response.responseText);
    
    var styleString = 'width: 525px; height: 500px; left: ' + 240 + 'px; top: ' + 200 + 'px;';
    var projPane = createFloatingPane('projPane', styleString, "Add / Edit Project Description", true);
    var mainDiv 	= document.createElement("div");
    var bodyLinkDiv 	= document.createElement("div");
    var projForm = "<table>";
    projForm = projForm + "<tr><td COLSPAN='2'><em>Add Project Description</em></td></tr>";
    projForm = projForm + "<tr><td>Name:</td><td><input id='projName' type='text' name='projName' value='" + importer.currentProject.name + "'/></td></tr>";
    projForm = projForm + "<tr><td COLSPAN='2'>&nbsp;</td></tr>";
    projForm = projForm + "<tr><td>Short Description:</td><td><textarea id='projSDes' rows='2' cols='35' name='projSDes'>"+projData.short_des+"</textarea></td></tr>";
    projForm = projForm + "<tr><td COLSPAN='2'>&nbsp;</td></tr>";
    projForm = projForm + "<tr><td>Long Description:</td><td><textarea id='projLDes' rows='10' cols='45' name='projLDes'>"+projData.long_des+"</textarea></td></tr>";
    projForm = projForm + "<tr><td COLSPAN='2'>&nbsp;</td></tr>";
    projForm = projForm + "<tr><td>Parent Context (Name):</td><td><input id='rootName' type='text' name='rootName' /></td></tr>";
    projForm = projForm + "<tr><td>Parent Context (ID):</td><td><input id='rootID' type='text' name='rootID' /></td></tr>";
    projForm = projForm + "<tr><td>Parent Context (Class):</td><td><input id='rootClass' type='text' name='rootClass' /></td></tr>";
    projForm = projForm + "<tr><td>No Data Message:</td><td><textarea id='noData' rows='2' cols='35' name='noData'></textarea></td></tr>";
    projForm = projForm + "<tr><td>Creators (';' seperated):</td><td><textarea id='projCreat' rows='2' cols='35' name='projCreat'></textarea></td></tr>";
    projForm = projForm + "<tr><td>Subjects (';' seperated):</td><td><textarea id='projSubs' rows='2' cols='35' name='projSubs'></textarea></td></tr>";
    projForm = projForm + "<tr><td COLSPAN='2'>&nbsp;</td></tr>";
    projForm = projForm + "<tr><td COLSPAN='2'><a href='javascript:doProjEdits();'>Update Project Description</a></td></tr>";
    projForm = projForm + "</table>";
    bodyLinkDiv.innerHTML = projForm;
    mainDiv.appendChild(bodyLinkDiv);
    projPane.setContent(mainDiv);
}

function doProjEdits(){
    var projName = $('projName').value;
    var projSDes = $('projSDes').value;
    var projLDes = $('projLDes').value;
    var rootName = $('rootName').value;
    var rootID = $('rootID').value;
    var rootClass = $('rootClass').value;
    var noData = $('noData').value;
    var projCreat = $('projCreat').value;
    var projSubs = $('projSubs').value;
    var projID = importer.currentProject.uuID; 
    
    //alert("geolat=" + geoLat + " geoLon=" + geoLon);
    var myAjax = new Ajax.Request(importer.rootURI + '/metadata/edit-project',
    {
	method: 'post',
	parameters: {
	projectUUID: projID,
	projName: projName,
        projSDes: projSDes,
        projLDes: projLDes,
        rootName: rootName,
        rootID: rootID,
        rootClass: rootClass,
        noData: noData,
        projCreat: projCreat,
        projSubs: projSubs
    },
	//onComplete: wizardInitData }
        onComplete: projResults }
    );
    
}

function projResults(response){
    alert(response.responseText);
    wizardInitData();
}

/*function tabMod(){
    //setActiveProject();
    var projID = importer.currentProject.uuID;
    var myAjax = new Ajax.Request('/project/get-processed-tables',
    {
	method: 'get',
	parameters: {
	projectUUID: projID
    },
	//onComplete: wizardInitData }
        onComplete: projTabsResult }
    );
}

function projTabsResult(response){
    var projTabData = dojo.fromJson(response.responseText);
    
    var styleString = 'width: 525px; height: 500px; left: ' + 240 + 'px; top: ' + 200 + 'px;';
    var projPane = createFloatingPane('projPane', styleString, "Add / Edit Project Description", true);
    var mainDiv 	= document.createElement("div");
    var bodyLinkDiv 	= document.createElement("div");
    var projTabForm = "<table>";
    for(var i=0; i < projTabData.length; i++){
        projTabForm += "<tr><td><a href='javascript:editTab(\""+ projTabData[i].source_id +"\", \""+ projTabData[i].filename +"\")'>"+ projTabData[i].filename +" (" + projTabData[i].source_id + ")</a></td></tr>";
    }
    
    projTabForm = projTabForm + "</table>";
    bodyLinkDiv.innerHTML = projTabForm;
    mainDiv.appendChild(bodyLinkDiv);
    projPane.setContent(mainDiv);
}*/
