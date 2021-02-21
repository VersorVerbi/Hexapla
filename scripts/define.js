function addDefiners() {
    let translations = document.getElementsByClassName('textArea');
    for (let t = 0; t < translations.length; t++) {
        let words = translations[t].getElementsByTagName('span');
        for (let w = 0; w < words.length; w++) {
            words[w].addEventListener('click', define)
        }
    }
}

async function define() {
    let classes = this.classList;
    let sourceWords = [];
    let sourceLang;
    let translationId = this.closest('.version').id;
    for (let c = 0; c < classes.length; c++) {
        if (classes[c] === 'hovered') {
            continue;
        }
        if (classes[c][0].toUpperCase() === 'H') {
            sourceLang = 'Hebrew'; // TODO: make this correct
        } else if (classes[c][0].toUpperCase() === 'G') {
            sourceLang = 'Greek'; // TODO: make this correct
        }
        sourceWords.push(classes[c]);
    }

    let form = new FormData();
    form.append('sourceWords', JSON.stringify(sourceWords));
    form.append('tid', translationId);
    form.append('text', this.innerText);
    //let input = JSON.stringify({'sourceWords': sourceWords, 'tid': translationId, 'text': this.innerText});

    let definitions = await fetch('/Hexapla/define.php', { // TODO: correct this root-relative URL later
        method: 'POST',
        mode: 'same-origin',
        redirect: 'error',
        body: form
    });
    let curDefns = document.getElementById('curLangDefn');
    emptyBox(curDefns, 'curLangTitle');
    let sourceDefns = document.getElementById('sourceLangDefn');
    emptyBox(sourceDefns, 'sourceLangTitle');
    definitions.json().then(data => { // .text().then(data => { console.log(data); });
        if (data['literalLang'] !== null) {
            if (data['literalLang']['dir'] === 'rtl') { // TODO: is this right?
                curDefns.classList.add('rtl');
            }
            document.getElementById('curLangTitle').innerText = data['literalLang']['name'];
            let definitionList = document.createElement('dl');
            createDefinitionObjects(data['literal'], definitionList);
            curDefns.appendChild(definitionList);
        }
        if (data['source']) {
            if (Object.keys(data['source']).length > 0) {
                let definitionList = document.createElement('dl');
                createDefinitionObjects(data['source'], definitionList);
                curDefns.appendChild(definitionList);
            }
        }
        showSidebar('dictionary');
    });
}

function createDefinitionObjects(oList, dList) {
    for (let prop in oList) {
        if (oList.hasOwnProperty(prop)) {
            let item = document.createElement('dt');
            item.innerText = prop + ': ' + oList[prop]['lemma'];
            let defn = document.createElement('dd');
            defn.innerText = oList[prop]['defn'];
            dList.appendChild(item);
            dList.appendChild(defn);
        }
    }
}