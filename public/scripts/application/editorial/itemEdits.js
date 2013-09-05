function autoSuggestPaths(){
   
    var filenameDom = document.getElementById('MediaNewFileName');
    var prefixDom = document.getElementById('MediaNewPathPrefix');
    
    var filename = encodeURIComponent(filenameDom.value);
    var prefixPath = prefixDom.value;
    
    var fullDom = document.getElementById('MediaNewFull');
    var previewDom = document.getElementById('MediaNewPreview');
    var thumbDom = document.getElementById('MediaNewThumb');
    
    fullDom.value = prefixPath + "/full/" + filename;
    previewDom.value = prefixPath + "/preview/" + filename;
    thumbDom.value = prefixPath + "/thumbs/" + filename;
}




//checks on the new media files, if they are there
function checkNewFiles(){
    
    var fullDom = document.getElementById('MediaNewFull');
    var previewDom = document.getElementById('MediaNewPreview');
    var thumbDom = document.getElementById('MediaNewThumb');
    
    var fullfile = fullDom.value;
    var preview = previewDom.value;
    var thumb = thumbDom.value;
    
    var checkURI = "../editorial/check-media-files";
    
    var myAjax = new Ajax.Request(checkURI,
        {   method: 'get',
            parameters:
                {fullfile: fullfile,
                preview: preview,
                thumb: thumb
                },
        onComplete: checkFilesDone }
    );
    
}

//displays results on checking on new media
function checkFilesDone(response){
    var respData = JSON.parse(response.responseText);
    var i = 0;
    for (i=0; i< respData.length; i++){
        var fileType = respData[i].filetype;
        var actDomID = fileType + "-newStatus";
        var actDom = document.getElementById(actDomID);
        var bytes = respData[i].bytes;
        var outputMessage = "<button class=\"btn btn-danger btn-mini\">Not Found!</button>";
        if(bytes > 0){
            var outputMessage = "<button class=\"btn btn-success btn-mini\">" + respData[i].human + "</button>";
        }
        actDom.innerHTML = outputMessage;
    }
}    

function updateClass(classLabel){
    var labelDom = document.getElementById('itemClassName');
    var uuidDom = document.getElementById('itemClassUUID');
    labelDom.innerHTML = classLabel;
    var selectedClassUUID = getCheckedRadio("itemClass");
    uuidDom.value = selectedClassUUID;
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

