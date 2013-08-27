
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

