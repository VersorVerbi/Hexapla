function addDefiners() {
    let translations = document.getElementsByClassName('textArea');
    for (let t = 0; t < translations.length; t++) {
        let words = translations[t].getElementsByTagName('span');
        for (let w = 0; w < words.length; w++) {
            words[w].addEventListener('click', define)
        }
    }
}

function addReferences(referenceData, sectionDiv) {
    let refList = document.createElement('dl');
    for (let s = 0; s < referenceData.length; s++) {
        let ref = document.createElement('dt');
        ref.innerText = referenceData[s]['ref'];
        let txt = document.createElement('dd');
        for (let p = 0; p < referenceData[s]['target'] + 10; p++) {
            if (referenceData[s][p] === undefined) continue;
            let span = document.createElement('span');
            if (referenceData[s][p][1] === 'Opening' || referenceData[s][p][1] === 'NotPunctuation') span.innerText = ' ';
            span.innerText += referenceData[s][p][0];
            if (parseInt(referenceData[s]['target']) === p) span.classList.add('crossRefWord');
            txt.appendChild(span);
        }
        refList.appendChild(ref);
        refList.appendChild(txt);
    }
    sectionDiv.appendChild(refList);
}

async function define(ev) {
    ev.stopPropagation();
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
    let crossRefsDiv = document.getElementById('crossref');
    emptyBox(crossRefsDiv);

    let wordSetup = fetch('/Hexapla/word-setup.php', { // RELATIVE-URL
        method: 'POST',
        mode: 'same-origin',
        redirect: 'error',
        body: form
    });
    wordSetup.then(wordResult => wordResult.json().then(async wordData => {
        let newForm = new FormData();
        newForm.append('sourceWords', JSON.stringify(wordData['sourceWords']));
        newForm.append('tid', wordData['tid']);
        newForm.append('literalWords', JSON.stringify(wordData['literalWords']));
        newForm.append('langId', wordData['langId']);
        let definitions = await fetch('/Hexapla/define.php', { // RELATIVE-URL
            method: 'POST',
            mode: 'same-origin',
            redirect: 'error',
            body: newForm
        });
        let crossRefs = fetch('/Hexapla/cross-refs.php', { // RELATIVE-URL
            method: 'POST',
            mode: 'same-origin',
            redirect: 'error',
            body: newForm
        });
        sidebarLoading('crossref');
        definitions.json().then(defnData => { // text().then(defnData => console.log(defnData));/*
            if (defnData['literalLang'] !== null) { // TODO: handle Oxford API data
                if (defnData['literalLang']['dir'] === 'rtl') {
                    curDefns.classList.add('rtl');
                }
                document.getElementById('curLangTitle').innerText = defnData['literalLang']['name'];
                let definitionList = document.createElement('dl');
                createDefinitionObjects(defnData['literal'], definitionList);
                curDefns.appendChild(definitionList);
                curDefns.classList.remove('hidden');
            }
            if (defnData['source']) {
                if (Object.keys(defnData['source']).length > 0) {
                    let definitionList = document.createElement('dl');
                    createDefinitionObjects(defnData['source'], definitionList);
                    sourceDefns.appendChild(definitionList);
                    sourceDefns.classList.remove('hidden');
                }
            }
            document.getElementById('loading').classList.add('hidden');
            showSidebar('dictionary');
        });
        crossRefs.then(crResult => {
            crResult.text().then(txt => {
                console.log(txt)
                let crData = JSON.parse(txt);
                // FIXME: exclude current verse(s)
                // TODO: add links to other verses
                if (crData['source'].length > 0) {
                    let sourceSection = document.createElement('div');
                    let sourceTitle = document.createElement('h3');
                    sourceTitle.innerText = joinObj(sourceWords, true);
                    sourceSection.appendChild(sourceTitle);
                    addReferences(crData['source'], sourceSection);
                    crossRefsDiv.appendChild(sourceSection);
                }
                if (crData['literal'].length > 0 && crData['source'].length > 0) {
                    crossRefsDiv.appendChild(document.createElement('hr'));
                }
                if (crData['literal'].length > 0) {
                    let literalSection = document.createElement('div');
                    let literalTitle = document.createElement('h3');
                    literalTitle.innerText = wordData['literalWords'].join(', ');
                    literalSection.appendChild(literalTitle);
                    addReferences(crData['literal'], literalSection);
                    crossRefsDiv.appendChild(literalSection);
                }
                sidebarLoading('crossref');
                tabNotify('crossref');
            });
        });
    }));
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