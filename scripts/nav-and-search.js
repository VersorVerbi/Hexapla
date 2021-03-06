async function doSearch(event, onpopstate) {
    if (event) {
        event.preventDefault();
    }
    killTheTinyMouse();
    await autosave(true);
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
    if (newSearch.length === 0 && currentSearch.length > 0) {
        let currentSplit = currentSearch.split('|');
        newSearch = currentSplit[0];
        translations = currentSplit[1];
        if (translations.length === 0) translations = '1';
        formData.set('translations', translations);
        formData.set('searchbox', newSearch);
        document.getElementById('translations').value = translations;
        document.getElementById('searchbox').value = newSearch;
    } else {
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
        let newParents = await fetch(INTERNAL_API_PATH + 'results.php?translations=' + translations, {
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

            setTimeout(autosave, 300000); // 5 minute autosave
            window.addEventListener('beforeunload', async () => {
                killTheTinyMouse();
                await autosave(true);
            });
        });
    } else {
        document.getElementById('loading').classList.remove('hidden');
        // we only need to empty things here because the above creates new boxes whole-cloth
        for (let t = 0; t < ts.length; t++) {
            emptyBox(document.getElementById('t' + ts[t]).getElementsByClassName('textArea')[0]);
        }
        await completeSearch(formData);
    }
    document.getElementById('currentSearch').value = newSearch + '|' + translations;
    if (!onpopstate) {
        addSearch(newSearch, translations);
    }
}

async function completeSearch(formData) {
    let searchResults = await fetch(INTERNAL_API_PATH + 'search.php', {
        method: 'POST',
        mode: 'same-origin',
        redirect: 'error',
        body: formData
    });
    searchResults.text().then(textData => tryParseJson(textData)).then(results => {
        let textResults = results.text;
        for (let i = 0; i < Object.keys(textResults).length; i++) {
            hideNotice(textResults[i]['parent']);
            let parent = document.getElementById(textResults[i]['parent']).getElementsByClassName('textArea')[0];
            if (textResults[i]['rtl'] && !parent.classList.contains('rtl')) {
                parent.classList.add('rtl');
            }
            let span = document.createElement('span');
            if (textResults[i]['class'].length > 0) span.classList.add(textResults[i]['class']);
            if (textResults[i]['space-before']) {
                let space = document.createTextNode(' ');
                parent.appendChild(space);
            }
            span.innerText = textResults[i]['val'];
            parent.appendChild(span);
        }
        let allTexts = document.getElementsByClassName('textArea');
        for (let i = 0; i < allTexts.length; i++) {
            if (allTexts[i].childNodes.length === 0) {
                let parent = allTexts[i].closest('.version');
                showNotice(parent.id, 'This text does not include the requested passage.', 3);
            }
        }
        let disambig = document.getElementById('disambiguation');
        if (results['alts'] !== null) {
            disambig.classList.remove('hidden');
            disambig.innerHTML = results['alts'];
        } else {
            disambig.innerHTML = '';
            disambig.classList.add('hidden');
        }

        let title = document.getElementById('title');
        title.innerText = results['title'];
        title.classList.remove('hidden');
        document.title = 'Modern Hexapla — ' + results['title'];

        let hasMultiTransl = false;
        for (let dataPair of formData.entries()) {
            if (dataPair[0] === 'translations') {
                hasMultiTransl = (substr_count(dataPair[1], '^') > 0);
                break;
            }
        }
        if (hasMultiTransl) {
            resetWordHovers();
        }
        let pg = document.getElementById('page');
        pg.classList.add('results');
        pg.classList.remove('hidden');
        document.getElementById('loading').classList.add('hidden');

        addDefiners();

        let noteHolder = document.getElementById('my-notes');
        if (noteHolder) {
            noteHolder.value = joinObj(data.myNotes, false, '');
            let maxId = 0;
            for(let prop in data.myNotes) {
                if (data.myNotes.hasOwnProperty(prop)) {
                    if (prop > maxId) {
                        maxId = prop;
                    }
                }
            }
            if (maxId > 0) {
                document.getElementById('currentNoteId').value = maxId;
            }

            // rerun this if we added a notes section
            init_tinymce('#my-notes', formData.get('currentTinyMCETheme'));
        }

        document.getElementById('currentLocationIds').value = results['loc_id'];

        getDiffCookies().then(cookies => {
            diffCtrl = new DiffControl(null, cookies, document.querySelectorAll('.version[data-can-diff="4"]'));
        });
    });
}

document.addEventListener("DOMContentLoaded", function() {
    document.getElementById('getPermalink').addEventListener('click', function() {
        let curSearch = document.getElementById('currentSearch').value.split('|');
        window.location.search = 'page=search&search=' + curSearch[0] + '&vers=' + curSearch[1];
    });
    document.getElementById('searchform').addEventListener('submit', doSearch);
});

function resetWordHovers() {
    let spans = document.querySelectorAll('.version:not(#my-notes-container) span');
    for (let s = 0; s < spans.length; s++) {
        spans[s].addEventListener('mouseover', showHideStrongs.bind(spans[s], true));
        spans[s].addEventListener('mouseout', showHideStrongs.bind(spans[s], false));
    }
}