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
    document.getElementById('loading').classList.remove('hidden');
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
    form.append('tid', translationId.substring(1));
    form.append('text', this.innerText);

    let curDefns = document.getElementById('curLangDefn');
    emptyBox(curDefns, 'curLangTitle');
    let sourceDefns = document.getElementById('sourceLangDefn');
    emptyBox(sourceDefns, 'sourceLangTitle');
    let crossRefsDiv = document.getElementById('crossref'); // TODO: is this right?
    emptyBox(crossRefsDiv);

    let wordSetup = await fetch('/Hexapla/word-setup.php', { // TODO: correct this root-relative URL later
        method: 'POST',
        mode: 'same-origin',
        redirect: 'error',
        body: form
    });
    wordSetup.json().then(async wordData => {
        let newForm = new FormData();
        newForm.append('sourceWords', JSON.stringify(wordData['sourceWords']));
        newForm.append('tid', wordData['tid']);
        newForm.append('literalWords', JSON.stringify(wordData['literalWords']));
        newForm.append('langId', wordData['langId']);
        let definitions = await fetch('/Hexapla/define.php', { // FIXME: correct this root-relative URL later
            method: 'POST',
            mode: 'same-origin',
            redirect: 'error',
            body: newForm
        });
        let crossRefs = await fetch('/Hexapla/cross-refs.php', { // FIXME: correct this root-relative URL later
            method: 'POST',
            mode: 'same-origin',
            redirect: 'error',
            body: newForm
        });
        definitions.json().then(defnData => { // text().then(defnData => console.log(defnData));/*
            if (defnData['literalLang'] !== null) { // TODO: handle Oxford API data
                if (defnData['literalLang']['dir'] === 'rtl') { // TODO: is this right?
                    curDefns.classList.add('rtl');
                }
                document.getElementById('curLangTitle').innerText = defnData['literalLang']['name'];
                let definitionList = document.createElement('dl');
                createDefinitionObjects(defnData['literal'], definitionList);
                curDefns.appendChild(definitionList);
            }
            if (defnData['source']) {
                if (Object.keys(defnData['source']).length > 0) {
                    let definitionList = document.createElement('dl');
                    createDefinitionObjects(defnData['source'], definitionList);
                    sourceDefns.appendChild(definitionList);
                }
            }
            document.getElementById('loading').classList.add('hidden');
            showSidebar('dictionary');
        });
        crossRefs.json().then(crData => { // text().then(crData => console.log(crData));/*
            // FIXME: exclude current verse(s)
            // TODO: add links to other verses
            if (crData['source'].length > 0) {
                let sourceSection = document.createElement('div');
                let sourceTitle = document.createElement('h3');
                sourceTitle.innerText = sourceWords.join(', ');
                sourceSection.appendChild(sourceTitle);
                let refList = document.createElement('dl');
                for (let s = 0; s < crData['source'].length; s++) {
                    let ref = document.createElement('dt');
                    ref.innerText = crData['source'][s]['ref'];
                    let txt = document.createElement('dd');
                    for (let p = 0; p < crData['source'][s]['target'] + 10; p++) {
                        if (crData['source'][s][p] === undefined) continue;
                        let span = document.createElement('span');
                        if (crData['source'][s][p][1] === 'Opening' || crData['source'][s][p][1] === 'NotPunctuation') span.innerText = ' ';
                        span.innerText += crData['source'][s][p][0];
                        if (parseInt(crData['source'][s]['target']) === p) span.classList.add('crossRefWord');
                        txt.appendChild(span);
                    }
                    refList.appendChild(ref);
                    refList.appendChild(txt);
                }
                sourceSection.appendChild(refList);
                crossRefsDiv.appendChild(sourceSection);
            }
            if (crData['literal'].length > 0 && crData['source'].length > 0) {
                crossRefsDiv.appendChild(document.createElement('hr'));
            }
            if (crData['literal'].length > 0) {

            }
            // TODO: put the data in the cross-refs section
        });
    });
}

function createDefinitionObjects(oList, dList) {
    for (let prop in oList) {
        if (oList.hasOwnProperty(prop)) {
            let item = document.createElement('dt');
            item.innerText = prop + ': ' + oList[prop]['lemma'];
            let defn = document.createElement('dd');
            if (oList[prop].hasOwnProperty('etymology')) {
                let eList = document.createElement('div');
                eList.classList.add('etymology');
                for (let i = 0; i < oList[prop]['etymology'].length; i++) {
                    let etym = document.createElement('div');
                    etym.innerText = oList[prop]['etymology'][i];
                    eList.appendChild(etym);
                }
                defn.appendChild(eList);
            }
            if (oList[prop].hasOwnProperty('defn')) {
                defn.innerText = oList[prop]['defn'];
            } else if (oList[prop].hasOwnProperty('definition')) {
                let nList = document.createElement('ol');
                for (let i = 0; i < oList[prop]['definition'].length; i++) {
                    let li = document.createElement('li');
                    li.innerText = oList[prop]['definition'][i];
                    nList.appendChild(li);
                }
                defn.appendChild(nList);
            }
            dList.appendChild(item);
            dList.appendChild(defn);
        }
    }
}