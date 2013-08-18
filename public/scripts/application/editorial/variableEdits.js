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

