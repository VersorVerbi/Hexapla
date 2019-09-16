function setDoc() {
    let elDoc = document.getElementById('doc');
    let docId = elDoc.options[elDoc.selectedIndex].value;
    
    let elTransl = document.getElementById('transl');
    
    if (elTransl != null) {
        removeNonBlanks(elTransl);
        
        addOptions(elTransl, translations, docId);
    }
}

function removeNonBlanks(elSel) {
    for (let i = 0; i < elSel.children.length; i++) {
        let opt = elSel.children[i];
        if (opt.value != "") {
            elSel.removeChild(opt);
            i--;
        }
    }
}

function addOptions(elSel, arr, docId) {
    for (let i = 0; i < arr.length; i++) {
        if (arr[i][2] == docId) {
            let newOpt = document.createElement('option');
            newOpt.value = arr[i][0];
            let newText = document.createTextNode(arr[i][1]);
            newOpt.appendChild(newText);
            elSel.appendChild(newOpt);
        }
    }
}