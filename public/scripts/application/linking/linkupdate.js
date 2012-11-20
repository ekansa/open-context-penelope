
var allProperties = new Array();
var baseURI;
var currentPropIndex;
var currentPropTotal;
var currentPropDone;

//start by getting list of properties to process
function start(){
    baseURI = dojo.byId('inputBaseURI').value;
    //alert(baseURI);
    
    var actLink = host + 'linked-data/get-property-list';
    //alert("Active Link: "+ actLink);
    
    var myAjax = new Ajax.Request(actLink,
        {method: 'get',
        parameters:{
            baseURI:   baseURI  
        },
        onComplete: setProperties }
    );
    
}

//start by getting list of not yet done properties to process
function remainder(){
    baseURI = dojo.byId('inputBaseURI').value;
    //alert(baseURI);
    
    var actLink = host + 'linked-data/not-done-links';
    //alert("Active Link: "+ actLink);
    
    var myAjax = new Ajax.Request(actLink,
        {method: 'get',
        parameters:{
            baseURI:   baseURI  
        },
        onComplete: setProperties }
    );
    
}



//populate array of properties from JSON response
function setProperties(response){
    var itemList = dojo.fromJson(response.responseText);
    //alert("Response Count: " + itemList.length);
    var i = 0;
    for (i=0; i< itemList.length; i++){
        allProperties[i] = itemList[i];
    }//end loop
    
    currentPropIndex = 0;
    dojo.byId('propIndex').innerHTML = currentPropIndex;
    dojo.byId('propCount').innerHTML = allProperties.length;
    //alert("Property Count: " + allProperties.length);
    allPropertiesLink();
}


//this function loops through all the properties to do
function allPropertiesLink(){
    currentPropID  = allProperties[currentPropIndex];
    prepPropertyLink(currentPropID);
    dojo.byId('propIndex').innerHTML = currentPropIndex;
}


function prepPropertyLink(currentPropID){
    var actLink = host + 'linked-data/prep-property';
    var myAjax = new Ajax.Request(actLink,
        {method: 'get',
        parameters:{
            baseURI:   baseURI,
            id: currentPropID
        },
        onComplete: finishPropPrep}
    );
}


function finishPropPrep(response){
    
    var data = dojo.fromJson(response.responseText);
    currentPropTotal = data.numItems;
    currentPropDone = 0;
    currentPropID = data.propertyUUID;
    
    dojo.byId('numItems').innerHTML = currentPropTotal;
    dojo.byId('numDone').innerHTML = currentPropDone;
    
    dojo.byId('propID').innerHTML =  currentPropID;
    dojo.byId('varID').innerHTML = data.variableUUID;
    dojo.byId('varLabel').innerHTML = data.varRelations.linkedLabel;
    dojo.byId('valLabel').innerHTML = data.valRelations.linkedLabel;
    dojo.byId('varVocab').innerHTML = data.varRelations.vocabulary;
    dojo.byId('valVocab').innerHTML = data.valRelations.vocabulary;
    
    processItems(currentPropID);
    
}//end function


//now process spatial items related to a property
function processItems(currentPropID){
    
    var actLink = host + 'linked-data/process-property';
    var myAjax = new Ajax.Request(actLink,
        {method: 'get',
        parameters:{
            baseURI:   baseURI,
            id: currentPropID
        },
        onComplete: finishPropProcess}
    );
    
}//end function


function finishPropProcess(response){
    
    var data = dojo.fromJson(response.responseText);
    var currentPropID = data.propertyUUID;
    
    if(data.numDone == 0){
        currentPropDone = currentPropTotal;
    }
    else{
         currentPropDone = currentPropDone + data.numDone;
    }
    
    dojo.byId('numDone').innerHTML = currentPropDone;
    var percentDone = currentPropDone / currentPropTotal;
    percentDone = Math.round((percentDone * 100));
    percentDone = percentDone  + " %";
    dojo.byId('donePercent').innerHTML = percentDone;
    
    if(currentPropDone < currentPropTotal){
        processItems(currentPropID);
    }
    else{
        currentPropIndex = currentPropIndex + 1;
        if(currentPropIndex  < allProperties.length){
            allPropertiesLink();
        }
        else{
            alert("All properties done!");
        }
    }
    
}

