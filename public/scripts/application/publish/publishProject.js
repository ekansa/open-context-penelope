
var actTabs = new Array();
var limitList = "";

function addTab(tabID)
{
    
    var output = "";
    var numSel = actTabs.length;
    actTabs[numSel] = tabID;
    var i = 0;
    for (i=0;i<=numSel;i++){
        output += actTabs[i] + " ";
    }
    alert("Added, now have: " + output);
}


function removeTab(tabID)
{
    var output = "";
    actTabs.splice(actTabs.indexOf(tabID), 1);

    var numSel = actTabs.length;
    for (i=0;i<=numSel;i++){
        var outText = actTabs[i];
        output += actTabs[i] + " ";
    }
    
    alert("Removed, now have: " + output);
}



var baseURI;
var tabsList;

/*
 STEP 1: make a string of the tab array
 then get summary of item counts
*/
function publishProj(){
    var projID = dojo.byId('projID').value;
    baseURI = dojo.byId('host').value;
    
    tabsList = "";
    var numSel = actTabs.length;
    for (i=0;i<=numSel;i++){
        var outText = actTabs[i];
        tabsList += actTabs[i] + ";";
    }
    
    getItemSums();
    
}


var doUpdate; //check if an update needs to be done
/*
 STEP 2: get summary of item counts
*/
function getItemSums()
{
    var projID = dojo.byId('projID').value;
    var pubURI = dojo.byId('pubURI').value;
    
    doUpdate = dojo.byId('upDateT').checked;
    
    if(dojo.byId('upDateT').checked){
        limitList = dojo.byId('limitList').value;
    }
    
    //alert(projID);
    var viewURL = baseURI + "publish/item-sum?projectUUID=" + projID;
    viewURL += "&pubURI=" + pubURI;
    viewURL += "&tabIDs=" + tabsList;
    
    dojo.byId('getListURL').innerHTML = "<p class='bodyText'><a href='"+viewURL+"'>"+viewURL+"</a></p>";
    
    
    var myAjax = new Ajax.Request(baseURI + 'publish/item-sum',
        {method: 'get', parameters:
        {pubURI: pubURI,
         projectUUID: projID,
         limitList: limitList,
         tabIDs: tabsList
        },
        onComplete: setTotals }
    );
}

/*
 STEP 3: make a string of the tab array
 then get summary of item counts
*/
var CountDone = new Array();
var CountTotal = new Array();

function setTotals(response){
    //alert(response.responseText);
    var itemTotals = dojo.fromJson(response.responseText);
    CountTotal['person'] = parseFloat(itemTotals.person);
    CountTotal['space'] = parseFloat(itemTotals.space);
    CountTotal['prop'] = parseFloat(itemTotals.prop);
    CountTotal['media'] = parseFloat(itemTotals.media);
    CountTotal['doc'] = parseFloat(itemTotals.doc);
    CountTotal['upSpace'] = parseFloat(itemTotals.upSpace);
    
    if(doUpdate){
        //CountTotal['prop'] = 0;
    }
    
    CountTotal['grand'] = CountTotal['person']+CountTotal['space']+CountTotal['prop']+CountTotal['media']+CountTotal['doc']+CountTotal['upSpace'];
    
    dojo.byId('persTotal').innerHTML = CountTotal['person'];
    dojo.byId('spaceTotal').innerHTML = CountTotal['space'];
    dojo.byId('propTotal').innerHTML = CountTotal['prop'];
    dojo.byId('mediaTotal').innerHTML = CountTotal['media'];
    dojo.byId('docTotal').innerHTML = CountTotal['doc'];
    dojo.byId('upSpaceTotal').innerHTML = CountTotal['upSpace'];
    dojo.byId('allTotal').innerHTML = CountTotal['grand'];
    
    CountDone["person"] =0;
    CountDone["space"] =0;
    CountDone["prop"] =0;
    CountDone["media"] =0;
    CountDone["doc"] =0;
    CountDone["upSpace"] =0;
    
    if(CountTotal['person'] == 0){
        personDone = true;
    }
    else{
        personDone = false;
    }
    
    if(CountTotal['space'] == 0){
        spaceDone = true;
    }
    else{
        spaceDone = false;
    }
    
    if(CountTotal['prop'] == 0){
        propDone = true;
    }
    else{
        propDone = false;
    }
    
    if(CountTotal['media'] == 0){
        mediaDone = true;
    }
    else{
        mediaDone = false;
    }
    
    if(CountTotal['doc'] == 0){
        docDone = true;
    }
    else{
        docDone = false;
    }
    
    if(CountTotal['upSpace'] == 0){
        upSpaceDone = true;
    }
    else{
        upSpaceDone = false;
    }
    
    var confText = "You have selected "+ CountTotal['grand'] + " items to publish, including: ";
    confText += CountTotal['person'] + " Persons, ";
    confText += CountTotal['space'] + " Location / objects, ";
    confText += CountTotal['prop'] + " Properties, ";
    confText += CountTotal['media'] + " Media items. ";
    confText += CountTotal['doc'] + " Document items. ";
    confText += CountTotal['upSpace'] + " Location / objects (to update) items. ";
    
    var checkCont = confirm(confText);
    
    if(checkCont){
        publishItems();
    }
    
}


/* STEP 4: Get select next type of data to publish
*/
var actType;
function publishItems(){

    actType = false;

    if(upSpaceDone == false){
        actType = "upSpace"; 
    }
    if(docDone == false){
        actType = "doc"; 
    }
    if(mediaDone == false){
        actType = "media"; 
    }
    if(spaceDone == false){
        actType = "space";
    }
    if(propDone == false){
        actType = "prop"; 
    }
    if(personDone == false){
        actType = "person"; //do these first
    }
    
    var statusUpdate = "<p class='bodyText'>Working on: " + actType  + " items</p>";
    dojo.byId('contUpdate').innerHTML = statusUpdate;
    
    if(actType != false){
        getItemIDs(actType);
    }
}

/* STEP 5: get next item id to publish
*/
function getItemIDs(itemType)
{
    var projID = dojo.byId('projID').value;
    var pubURI = dojo.byId('pubURI').value;

    var viewURL = baseURI + "publish/item-list?projectUUID=" + projID;
    viewURL += "&pubURI=" + pubURI;
    viewURL += "&itemType=" + itemType + "&tabIDs=" + tabsList;
    
    dojo.byId('getListURL').innerHTML = "<p class='bodyText'><a href='"+viewURL+"'>"+viewURL+"</a></p>";
    //alert(baseURI + 'publish/item-list');
    var myAjax = new Ajax.Request(baseURI + 'publish/item-list',
        {method: 'get', parameters:
        {pubURI: pubURI,
        projectUUID: projID,
         itemType: itemType,
         limitList: limitList,
         tabIDs: tabsList
        },
        onComplete: actIDListSetUp }
    );
}

/*STEP 5B: set up next item list
*/
var ActingList =new Array();
var ActItemCounter;
var ActMaxCounter;
function actIDListSetUp(response){
    
    var itemList = dojo.fromJson(response.responseText);
   
    ActMaxCounter = itemList.items.length;
    actType = itemList.itemType;
    
    if(ActMaxCounter >0){
        var i = 0;
        for (i=0; i< ActMaxCounter; i++){
            var actUUID = itemList.items[i].itemUUID;
            ActingList[i] = actUUID;
        }
        ActItemCounter = 0;
        IterateActItems();
    }
    else{
        CountDone[actType] = CountDone[actType] + 1000; 
        indexFinish();
    }
}

/*STEP 5C: publish the next Item ID
*/
function IterateActItems(){
    //var itemList = ActingList;
    
    if(ActItemCounter < ActMaxCounter){
        
        var statusUpdate = "<p class='bodyText'>Working on : " + (ActItemCounter + 1) + " of " + ActMaxCounter + " "+ actType  + " items</p>";
        dojo.byId('contUpdate').innerHTML = statusUpdate;
        actIDList(ActingList[ActItemCounter], actType);
    }
    else{
        var statusUpdate = "<p class='bodyText'>Getting next set of items</p>";
        dojo.byId('contUpdate').innerHTML = statusUpdate;
        publishItems();
    }
}

function actIDList(itemUUID, itemType){
    
    var prefixURI_val = dojo.byId('prefixURI').value;
    var suffixURI_val = dojo.byId('suffixURI').value;
    var doPrefix = false;
    if((prefixURI_val.length > 4)&&(itemType == 'space')){
        doPrefix = true;
    }
    
    var projID = dojo.byId('projID').value;
    var pubURI = dojo.byId('pubURI').value;
    var statusUpdate = "<p class='bodyText'>Publishing item: " + itemUUID + " (" + itemType + ")</p>";
    dojo.byId('nextItem').innerHTML = statusUpdate;
    
    if(itemUUID != undefined){
        if( itemType == "upSpace" ){
            doUpdate = true;
        }
            
        if(!doPrefix){    
            var myAjax = new Ajax.Request(baseURI + 'publish/publishdoc',
                {method: 'post', parameters:
                {pubURI: pubURI,
                 projectUUID: projID,
                 itemType: itemType,
                 itemUUID: itemUUID,
                 doUpdate: doUpdate
                },
                onComplete: postPublish }
            );
        }
        else{
            var myAjax = new Ajax.Request(baseURI + 'publish/publishdoc',
                {method: 'post', parameters:
                {pubURI: pubURI,
                 projectUUID: projID,
                 itemType: itemType,
                 itemUUID: itemUUID,
                 prefixURI: prefixURI_val,
                 suffixURI: suffixURI_val,
                 doUpdate: doUpdate
                },
                onComplete: postPublish }
            );   
        }
    }
    else{
        indexFinish();
    }
}



/*STEP LAST: Finish updating solr index
*/

function indexFinish(){
    
    var pubURI = dojo.byId('pubURI').value;
    var statusUpdate = "<p class='bodyText'>Finishing index updates</p>";
    dojo.byId('nextItem').innerHTML = statusUpdate;
        
        var myAjax = new Ajax.Request(pubURI,
            {method: 'post', parameters:
            {index: true
            },
            onComplete: postPublish }
        );
  
}




function postPublish(response){
    
    var perDone = new Array();
    var divWidthPx;
    var divWidth;
    //alert(response.responseText);

    //deal with people items
    if(actType == 'person'){
        CountDone["person"]++;
    }    
    
    if(CountDone["person"] >= CountTotal["person"]){
        personDone = true;
        perDone['person'] = 100;
        divWidthPx = 500;
    }
    else{
        personDone = false;
        perDone['person'] = Math.round((CountDone["person"] / CountTotal["person"])*100);    
        divWidthPx = Math.round((CountDone["person"] / CountTotal["person"])*500);
    }    
    divWidth = divWidthPx + "px";
    dojo.byId('persZone').style.width = divWidth;
    dojo.byId('persZone').innerHTML = perDone['person'] + "%";
   
   
   
   
    //deal with space items 
    if(actType == 'space'){
        CountDone["space"]++;
    }
    if(CountDone["space"] >= CountTotal["space"]){
        spaceDone = true;
        perDone['space'] = 100;
        divWidthPx = 500;
    }
    else{
        spaceDone = false;
        perDone['space'] = Math.round((CountDone["space"] / CountTotal["space"])*100);
        divWidthPx = Math.round((CountDone["space"] / CountTotal["space"])*500);
    }    
    
    divWidth = divWidthPx + "px";
    dojo.byId('spaceZone').style.width = divWidth;
    dojo.byId('spaceZone').innerHTML = perDone['space'] + "%";
    
    
    
    //deal with prop items 
    if(actType == 'prop'){
        CountDone["prop"]++;
    }
    
    if(CountDone["prop"] >= CountTotal["prop"]){
        propDone = true;
        perDone['prop'] = 100;
        divWidthPx =500;
    }
    else{
        propDone = false;
        perDone['prop'] = Math.round((CountDone["prop"] / CountTotal["prop"])*100);    
        divWidthPx = Math.round((CountDone["prop"] / CountTotal["prop"])*500);
    }    
    divWidth = divWidthPx + "px";
    dojo.byId('propZone').style.width = divWidth;
    dojo.byId('propZone').innerHTML = perDone['prop'] + "%";
    
    
    //deal with media items 
    if(actType == 'media'){
        CountDone["media"]++;
    }
    
    if(CountDone["media"] >= CountTotal["media"]){
        mediaDone = true;
        perDone['media'] = 100;
        divWidthPx = 500;
    }
    else{
        mediaDone = false;
        perDone['media'] = Math.round((CountDone["media"] / CountTotal["media"])*100);
        divWidthPx = Math.round((CountDone["media"] / CountTotal["media"])*500);
    }    
    divWidth = divWidthPx + "px";
    dojo.byId('mediaZone').style.width = divWidth;
    dojo.byId('mediaZone').innerHTML = perDone['media'] + "%";
    
    
    
    //deal with doc items 
    if(actType == 'doc'){
        CountDone["doc"]++;
    }
    
    if(CountDone["doc"] >= CountTotal["doc"]){
        docDone = true;
        perDone['doc'] = 100;
        divWidthPx = 500;
    }
    else{
        docDone = false;
        perDone['doc'] = Math.round((CountDone["doc"] / CountTotal["doc"])*100);
        divWidthPx = Math.round((CountDone["doc"] / CountTotal["doc"])*500);
    }    
    divWidth = divWidthPx + "px";
    dojo.byId('docZone').style.width = divWidth;
    dojo.byId('docZone').innerHTML = perDone['doc'] + "%";
    
    
    
    //deal with space updates items 
    if(actType == 'upSpace'){
        CountDone["upSpace"]++;
    }
    
    if(CountDone["upSpace"] >= CountTotal["upSpace"]){
        upSpaceDone = true;
        perDone['upSpace'] = 100;
        divWidthPx = 500;
    }
    else{
        upSpaceDone = false;
        perDone['upSpace'] = Math.round((CountDone["upSpace"] / CountTotal["upSpace"])*100);
        divWidthPx = Math.round((CountDone["upSpace"] / CountTotal["upSpace"])*500);
    }    
    divWidth = divWidthPx + "px";
    dojo.byId('upSpaceZone').style.width = divWidth;
    dojo.byId('upSpaceZone').innerHTML = perDone['upSpace'] + "%";
    
    
    
   CountDone["grand"] = CountDone["person"]+ CountDone["space"]+ CountDone["prop"] + CountDone["media"] + CountDone["doc"] + CountDone["upSpace"];
    
    if(CountDone["grand"] >= CountTotal['grand']){
        actType = false;
        perDone['grand'] = 100;
        divWidthPx = 500;
    }
    else{
        perDone['grand'] = Math.round((CountDone["grand"] / CountTotal["grand"])*100);
        divWidthPx = Math.round((CountDone["grand"] / CountTotal["grand"])*500);
    }    
    divWidth = divWidthPx + "px";
    dojo.byId('totalZone').style.width = divWidth;
    dojo.byId('totalZone').innerHTML = perDone['grand'] + "% ("+ CountDone["grand"] +" of " + CountTotal['grand'] +")";
    
    var itemResponse = dojo.fromJson(response.responseText);
    
    var statusUpdate = "<p class='bodyText'>" + itemResponse.itemUUID + " (" + itemResponse.itemType + ") Status: "+itemResponse.pubStatus+", "+itemResponse.error+"</p>";
    //statusUpdate += "<p class='bodyText'><a href='" + itemResponse.req_uri + "'>" + itemResponse.req_uri + "</a></p>";
    dojo.byId('lastItem').innerHTML = statusUpdate;
    
    
    
    
    if(CountDone["grand"] < CountTotal['grand']){
        //publishItems();
        ActItemCounter++;
        
        if((actType == 'space')||(actType == 'media')){
           delayIterate();
        }
        else{
            IterateActItems();
        }
    }
    else{
        indexFinish();
    }
    
}


function delayIterate(){
    setTimeout("IterateActItems()", 1000);
}


