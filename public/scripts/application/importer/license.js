var selectedLicense = null;

function initLicense()
{
    dojo.byId('license').style.visibility = "hidden";
    dojo.byId('license').style.height = "100px";
    dojo.byId('license').style.overflow = "hidden";
    dojo.byId('noLicense').style.visibility = "visible";
    dojo.byId('noLicense').style.height = "auto";
        
    //if a license exists, display it:
    if(importer.currentProject.licenseID != null && importer.currentProject.licenseID != 0)
    {
        queryForLicenseByID(importer.currentProject.licenseID);  
    }
    else
    {
        dijit.byId('buttonSelectLicense').setValue("Select This License"); 
        dijit.byId('buttonRevertLicense').domNode.style.display = "none";
        dojo.byId('licenseSelectionMessage').innerHTML = "No license has been selected.";
        dijit.byId('cc_com_y').setChecked(false);
        dijit.byId('cc_com_n').setChecked(false);
    }
}


function queryForLicenseByID(licenseID)
{
    //alert("queryForLicenseByID: " + licenseID);
    //send query to server to get licence:
    var myAjax = new Ajax.Request('/license/get-license-by-id',
        { method: 'get',
          parameters: {licenseID: licenseID },
          onComplete: initLicenseAlreadyDefined}
    );  
}

function initLicenseAlreadyDefined(response)
{
    //alert(response.responseText);
    selectedLicense = dojo.fromJson(response.responseText);
    dojo.byId('licenseSelectionMessage').innerHTML = "You have chosen to license your work with a:<br />" +
    "<img align='Absmiddle' src='/public/images/creativeCommons/" + selectedLicense.imageFileName + "' /> " + 
    "<span style='font-weight:bold;font-size:1.3em;'>" + selectedLicense.name + " License</span>.";   
    displayLicense();
        
    //check appropriate buttons:
    if(selectedLicense.canBeCommercial == 1)
        dijit.byId('cc_com_y').setChecked(true);
    else
        dijit.byId('cc_com_n').setChecked(true);
    
    if(selectedLicense.modRequirement == "S")
        dijit.byId('cc_dr_sa').setChecked(true);
    else if(selectedLicense.modRequirement == "N")
        dijit.byId('cc_dr_n').setChecked(true);  
    else
        dijit.byId('cc_dr_y').setChecked(true);
    
    dijit.byId('buttonSelectLicense').setDisabled(true);
    dijit.byId('buttonRevertLicense').setDisabled(true);
    dijit.byId('buttonSelectLicense').setValue("Switch to This License");
    dijit.byId('buttonRevertLicense').domNode.style.display = "";   
}

function getLicense()
{
    //determine whether or not commercial:
    var isCommercial = dijit.byId('cc_com_y').checked ? 1 : 0;

    //determine level of reproduction:
    var reproductionLevel = 'Y';
    if(dijit.byId('cc_dr_sa').checked)
        reproductionLevel = 'S';
    else if(dijit.byId('cc_dr_n').checked)
        reproductionLevel = 'N';   
    
    queryForLicense(isCommercial, reproductionLevel);
}

function queryForLicense(isCommercial, reproductionLevel)
{
    //send query to server to get licence:
    var myAjax = new Ajax.Request('/license/get-license',
        { method: 'get',
          parameters: {isCommercial: isCommercial, reproductionLevel: reproductionLevel },
          onComplete: displayLicenseFromResponse}
    );  
}

function displayLicenseFromResponse(response)
{
    //alert(response.responseText);
    selectedLicense = dojo.fromJson(response.responseText);
    displayLicense();
    if(importer.currentProject.licenseID == 0 || selectedLicense.id != importer.currentProject.licenseID)
    {
        dojo.byId('licenseSelectionMessage').innerHTML = "Please click the 'Select This License' button above to license your work with a:<br />" +
        "<span style='font-weight:bold;font-size:1.3em;'>" + selectedLicense.name + " License</span>.";
        dijit.byId('buttonSelectLicense').setDisabled(false);
        dijit.byId('buttonRevertLicense').setDisabled(false);
        
        if(importer.currentProject.licenseID == 0)
        {
            dijit.byId('buttonRevertLicense').domNode.style.display="none";
        }
    }
    else
    {
        dojo.byId('licenseSelectionMessage').innerHTML = "You have chosen to license your work with a:<br />" +
        "<img align='Absmiddle' src='/public/images/creativeCommons/" + selectedLicense.imageFileName + "' /> " + 
        "<span style='font-weight:bold;font-size:1.3em;'>" + selectedLicense.name + " License</span>.";        
        dijit.byId('buttonSelectLicense').setDisabled(true);
        dijit.byId('buttonRevertLicense').setDisabled(true);
    }    
}

function displayLicense()
{
    //set visibility:
    dojo.byId('license').style.visibility = "visible";
    dojo.byId('license').style.height = "auto";
    dojo.byId('noLicense').style.visibility = "hidden";
    dojo.byId('noLicense').style.height = "1px";
    
    //populate data:
    dojo.byId('ccImg').src = "/public/images/creativeCommons/" + selectedLicense.imageFileName;
    dojo.byId('ccImg').align = "Absmiddle";
    dojo.byId('name').innerHTML = selectedLicense.name;
    dojo.byId('description').innerHTML = selectedLicense.description;
    dojo.byId('details').href = selectedLicense.licenseURL;
    dojo.byId('legal').href = selectedLicense.legalURL;
}

function saveLicense()
{
    if(selectedLicense == null)
    {
        alert("Please select a license.");
        return;
    }
    var licenseID = selectedLicense.id;
    var projectID = importer.currentProject.id;
     var myAjax = new Ajax.Request('/license/set-license',
        { method: 'get',
            parameters:
            {
                licenseID: licenseID,
                dataTableName: importer.currentProject.dataTableName
            },
          onComplete: confirmLicenseSelection
        }
    );  
}

function confirmLicenseSelection(response)
{
    //alert(response.responseText);
    //update underlying datastore:
    //var selectedLicenseArray = [ selectedLicense ];
    //var selectedItem = gridProjectList.selection.getSelected()[0];
    //gridProjectList.store.setValues(selectedItem, "license", selectedLicenseArray);
    
    //call the init page function:
    importer.currentProject.licenseID = selectedLicense.id;
    initLicense();
    
    //let the user know s/he's successfully updated the license:
    //alert(response.responseText);
    updateNavigation();
}
