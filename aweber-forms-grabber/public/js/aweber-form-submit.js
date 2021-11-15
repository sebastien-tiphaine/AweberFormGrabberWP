AweberFormGrabberCheckMyForm = function(oForm){
    var reqf = oForm.elements.namedItem('meta_required');
    if(!reqf || !reqf.value){ return true;}
    var reqlist = reqf.value.split(',');
    if(!reqlist){ return true ;}
    var isValid = true;
    var arrayLength = reqlist.length;
    var emailReg = RegExp('^[a-zA-Z0-9\.\-]{3,}@[a-zA-Z0-9\-]{3,}\.[a-zA-Z0-9\-]{2,}(\.[a-zA-Z0-9\-]{2,})*$');
    for (var i = 0; i < arrayLength; i++) {
        oItm = oForm.elements.namedItem(reqlist[i]);
        if(!oItm || oItm.type != 'text') continue;
        if(!oItm.value || oItm.value == '' || oItm.value.length < 2){
            oItm.style.border = '3px solid red';
            isValid = false;
            continue;
        }
        if(reqlist[i] == 'email' && !emailReg.test(oItm.value)){
                oItm.style.border = '3px solid red';
                isValid = false;
                continue;
        }
        oItm.style = '';
    }
    
    return isValid;
}
