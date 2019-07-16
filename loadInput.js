function setDoc() {
    var elDoc = document.getElementById('doc');
    var docId = elDoc.options[elDoc.selectedIndex].value;
    
    var elTransl = document.getElementById('transl');
    
    if (elTransl != null) {
        removeNonBlanks(elTransl);
        
        addOptions(elTransl, translations, docId);
    }
}

function removeNonBlanks(elSel) {
    for (var i = 0; i < elSel.children.length; i++) {
        var opt = elSel.children[i];
        if (opt.value != "") {
            elSel.removeChild(opt);
            i--;
        }
    }
}

function addOptions(elSel, arr, docId) {
    for (var i = 0; i < arr.length; i++) {
        if (arr[i][2] == docId) {
            var newOpt = document.createElement('option');
            newOpt.value = arr[i][0];
            var newText = document.createTextNode(arr[i][1]);
            newOpt.appendChild(newText);
            elSel.appendChild(newOpt);
        }
    }
}