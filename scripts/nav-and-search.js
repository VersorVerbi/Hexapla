async function doSearch() {
    event.preventDefault();
    let formData = new FormData(document.getElementById('searchform'));
    let translations;
    for (let dataPair of formData.entries()) {
        if (dataPair[0] === 'translations') {
            translations = dataPair[1];
            break;
        }
    }
    if (translations.length === 0) {
        translations = '1';
        formData.set('translations', translations);
    }
    let ts = translations.split('^');
    let translationCheckId = 't' + ts[0];
    let translationCheck = document.getElementById(translationCheckId);
    // TODO: how are we handling adding/removing translations?
    if (translationCheck === null) {
        let newParents = await fetch('results.php?translations=' + translations, {
            method: 'POST',
            mode: 'same-origin',
            redirect: 'error',
            body: ''
        });
        newParents.text().then(async data => {
            document.getElementById('page').innerHTML = data;
            document.getElementById('loading').classList.remove('hidden');
            await completeSearch(formData);
        })
    } else {
        document.getElementById('loading').classList.remove('hidden');
        // we only need to empty things here because the above creates new boxes whole-cloth
        for (let t = 0; t < ts.length; t++) {
            emptyBox(document.getElementById('t' + ts[t]));
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
            let parent = document.getElementById(results[i]['parent']);
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

        document.getElementById('loading').classList.add('hidden');
    });
}


function highlightWords(className) {
    let words = document.getElementsByClassName(className);
    for (let w = 0; w < words.length; w++) {
        words[w].classList.add('hovered');
    }
}

function clearWords(className) {
    let words = document.getElementsByClassName(className);
    for (let w = 0; w < words.length; w++) {
        words[w].classList.remove('hovered');
    }
}

function emptyBox(box) { // per benchmarking, this is the fastest way to clear children, at least in FF and Chrome at the time
    let kid = box.firstChild;
    while (kid) {
        kid.remove();
        kid = box.firstChild;
    }
}

function configTls() {
    let tlCfg = document.getElementById('translationController');
    tlCfg.classList.remove('hidden');
}