function varLookUp(){
    
    var pubURI = "../editorial/var-lookup";
    var varTextDom = document.getElementById('varLook');
    var varTextLookup = varTextDom.value;
    var varProjValue = getCheckedRadio("varProj");
    var varTypeValue = getCheckedRadio("varType");
    var varClassValue = getCheckedRadio("varClass");

    var renderDiv = document.getElementById('vars');
    renderDiv.innerHTML = "<img src=\"../public/images/general/loaderb64.gif\" />";
    
    var myAjax = new Ajax.Request(pubURI,
        {   method: 'get',
            parameters:
                {q: varTextLookup,
                projectUUID: varProjValue,
                varType: varTypeValue,
                classUUID: varClassValue
                },
        onComplete: varLookUpDone }
    );
    
}

function varLookUpDone(response){
    var respData = JSON.parse(response.responseText);
    var renderDiv = document.getElementById('vars');
    var output = "<p>Variables:</p><ul>";
    
    var i = 0;
    for (i=0; i< respData.length; i++){
        var actVarUUID = respData[i].varUUID;
        var actVarLabel = respData[i].varLabel;
        //output += "<li><a id=" + actVarUUID + " href=\"javascript:varUUIDnote('" + actVarUUID + "');\">" + actVarLabel + "</a></li>";
        output += "<li><a id=" + actVarUUID + " href=\"../editorial/variables?tab=notes&varUUID=" + actVarUUID + "\">" + actVarLabel + "</a></li>";
    }
    
    output += "</ul>";
    renderDiv.innerHTML = output;
}

//add the variable UUID to the form for adding a note
function varUUIDnote(actVarUUID){
    var renderDom = document.getElementById('noteVarUUID');
    renderDom.value = actVarUUID;    
    var labDom = document.getElementById('noteVarLabel');
    var labSource = document.getElementById(actVarUUID);
    labDom.innerHTML = labSource.innerHTML;
}
//get the checked value from a radio list
function getCheckedRadio(radioName) {
    var radios = document.getElementsByName(radioName);
    var radioValue = false;
    for(var i = 0; i < radios.length; i++){
        if(radios[i].checked){
            radioValue = radios[i].value;
        }
    }
    return radioValue;
}

function valsLookUp(){
    
    var pubURI = "../editorial/var-vals";
    var valTextDom = document.getElementById('valLook');
    var valTextLookup = valTextDom.value;

    var varUUIDdom = document.getElementById('valsVarUUID');
    var varUUID = varUUIDdom.value; 
    
    var renderDiv = document.getElementById('vals');
    renderDiv.innerHTML = "<img src=\"../public/images/general/loaderb64.gif\" />";
    
    var myAjax = new Ajax.Request(pubURI,
        {   method: 'get',
            parameters:
                {q: valTextLookup,
                varUUID: varUUID
                },
        onComplete: valsLookUpDone }
    );
    
}

function valsLookUpDone(response){
    var respData = JSON.parse(response.responseText);
    var renderDiv = document.getElementById('vals');
    var output = "<h6>Values:</h6>";
    output += "<table class=\"table table-bordered table-condensed table-striped\">";
    output += "<thead>";
    output += "<tr>";
    output += "<th>Select</th>";
    output += "<th>Property UUID</th>";
    output += "<th>Property String</th>";
    output += "<th>Property Text</th>";
    output += "<th>Property Link URI</th>";
    output += "<th>Property Link Label</th>";
    output += "<th>Property Note</th>";
    output += "<th>Property Example</th>";
    output += "</tr>";
    output += "</thead>";
    output += "<tbody>";
    var i = 0;
    for (i=0; i< respData.length; i++){
        var actPropUUID = respData[i].propUUID;
        var actVal = respData[i].val;
        var actValText = respData[i].val_text;
        output += "<tr>";
        output += "<td><button class=\"btn btn-inverse\" id=" + actPropUUID + " onclick=\"javascript:propSelect('" + actPropUUID + "');\">&#10004;</button></td>";
        output += "<td><p><small>" + actPropUUID + "</small></p></td>";
        output += "<td>" + actValText + "</td>";
        output += "<td id=\"val-" + actPropUUID + "\">"
        output += "<input id=\"val-c-" + actPropUUID  + "\" type=\"text\" value=\"" + actVal + "\" /><br/>";
        output += "<button class=\"btn btn-mini\" onclick=\"javascript:changePropVal('" + actPropUUID  + "');\">Update</button>";
        output += "</td>";
        output += "<td>" +  "</td>";
        output += "<td>" +  "</td>";
        output += "<td>" +  "</td>";
        output += "<td><a href=\"../editorial/items?uuid=" + respData[i].exampleUUID + "\">Example</a></td>";
        output += "</tr>";
    }
    output += "</tbody>";
    output += "</table>";
    renderDiv.innerHTML = output;
}


function propSelect(propUUID){
    
    var propValDom = document.getElementById(('val-c-' + propUUID));
    var propValue = propValDom.value;
    
    var chronoPropUUIDdom = document.getElementById('chronoPropUUID');
    chronoPropUUIDdom.value = propUUID;
    
    var chronoPropValDom = document.getElementById('chronoPropVal');
    chronoPropValDom.value = propValue;
    
    var chronoPropUUIDdom = document.getElementById('LinkPropUUID');
    chronoPropUUIDdom.value = propUUID;
    
    var chronoPropValDom = document.getElementById('LinkPropVal');
    chronoPropValDom.value = propValue;
}



function changePropVal(actPropUUID){
    
    var pubURI = "../editorial/update-prop-val";
    var valTextDom = document.getElementById('val-c-' + actPropUUID);
    var valTextNew = valTextDom.value;

    var myAjax = new Ajax.Request(pubURI,
        {   method: 'post',
            parameters:
                {   propUUID: actPropUUID,
                    newPropValue: valTextNew
                },
        onComplete: changePropValDone }
    );
    
}


function changePropValDone(response){
    var respData = JSON.parse(response.responseText);
    if(respData.changedSubjects >0 ){
        alert("Items changed: " + respData.changedSubjects);
        valsLookUp();
    }
}

