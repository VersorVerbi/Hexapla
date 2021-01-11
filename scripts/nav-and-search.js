async function doSearch(event) {
    event.preventDefault();
    let formData = new FormData(document.getElementById('searchform'));
    let translations, currentSearch, newSearch;
    for (let dataPair of formData.entries()) {
        if (dataPair[0] === 'translations') {
            translations = dataPair[1];
        } else if (dataPair[0] === 'currentSearch') {
            currentSearch = dataPair[1];
        } else if (dataPair[0] === 'searchbox') {
            newSearch = dataPair[1];
        }
    }
    if (translations.length === 0) {
        translations = '1';
        formData.set('translations', translations);
    }
    if (currentSearch.length !== 0) {
        let currentSplit = currentSearch.split('|');
        let srch = currentSplit[0];
        let tls = currentSplit[1];
        if (translations === tls && newSearch === srch) {
            return; // nothing has changed
        }
    }
    let ts = translations.split('^');
    let hasAllTranslations = true;
    for (let t = 0; t < ts.length; t++) {
        let translationCheckId = 't' + ts[t];
        let translationCheck = document.getElementById(translationCheckId);
        if (translationCheck === null) {
            hasAllTranslations = false;
            break;
        }
    }
    // TODO: handle when we have more translation boxes than requested translations
    if (!hasAllTranslations) {
        let newParents = await fetch('results.php?translations=' + translations, {
            method: 'POST',
            mode: 'same-origin',
            redirect: 'error',
            body: ''
        });
        newParents.text().then(async data => {
            let pg = document.getElementById('page');
            pg.classList.add('hidden');
            pg.innerHTML = data;
            document.getElementById('loading').classList.remove('hidden');
            await completeSearch(formData);
        })
    } else {
        document.getElementById('loading').classList.remove('hidden');
        // we only need to empty things here because the above creates new boxes whole-cloth
        for (let t = 0; t < ts.length; t++) {
            emptyBox(document.getElementById('t' + ts[t]).getElementsByClassName('textArea')[0]);
        }
        await completeSearch(formData);
    }
}

async function completeSearch(formData) {
    let searchResults = await fetch('search.php', {
        method: 'POST',
        mode: 'same-origin',
        redirect: 'error',
        body: formData
    });
    searchResults.json().then(data => {
        let results = data;
        for (let i = 0; i < Object.keys(results).length - 2; i++) { // leave room for 'alts' and 'title'
            let parent = document.getElementById(results[i]['parent']).getElementsByClassName('textArea')[0];
            if (results[i]['rtl'] && !parent.classList.contains('rtl')) {
                parent.classList.add('rtl');
            }
            let span = document.createElement('span');
            if (results[i]['class'].length > 0) span.classList.add(results[i]['class']);
            if (results[i]['space-before']) {
                let space = document.createTextNode(' ');
                parent.appendChild(space);
            }
            span.innerText = results[i]['val'];
            parent.appendChild(span);
        }
        if (results['alts'] !== null) {
            let disambig = document.getElementById('disambiguation');
            disambig.classList.remove('hidden');
            disambig.innerHTML = results['alts'];
        } else {
            let disambig = document.getElementById('disambiguation');
            disambig.innerHTML = '';
            disambig.classList.add('hidden');
        }

        document.getElementById('title').innerText = results['title'];

        let hasMultiTransl = false;
        for (let dataPair of formData.entries()) {
            if (dataPair[0] === 'translations') {
                hasMultiTransl = (substr_count(dataPair[1], '^') > 0);
                break;
            }
        }
        if (hasMultiTransl) {
            let spans = document.getElementsByTagName('span');
            for (let s = 0; s < spans.length; s++) {
                spans[s].addEventListener('mouseover', function () {
                    let className = this.classList.item(0);
                    highlightWords(className);
                });
                spans[s].addEventListener('mouseout', function () {
                    let className = this.classList.item(0);
                    clearWords(className);
                });
            }
        }
        let pg = document.getElementById('page');
        pg.classList.add('results');
        pg.classList.remove('hidden');
        document.getElementById('loading').classList.add('hidden');
    });
}