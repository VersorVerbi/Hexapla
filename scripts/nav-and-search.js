async function doSearch() {
    let formData = new FormData(document.getElementById('searchform'));
    let translations;
    for (let dataPair of formData.entries()) {
        if (dataPair[0] === 'translations') {
            translations = dataPair[1];
            break;
        }
    }
    let translationCheckId = 't' + translations.split('^')[0];
    let translationCheck = document.getElementById(translationCheckId);
    if (translationCheck === null) {
        let newParents = await fetch('results.php?translations=' + translations, {
            method: 'GET',
            mode: 'same-origin',
            redirect: 'error'
        });
        newParents.text().then(async data => {
            document.getElementById('page').innerHTML = data;
            await completeSearch(formData);
        })
    } else {
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
        for (let i = 0; i < results.length; i++) {
            let parent = document.getElementById(results[i]['parent']);
            emptyBox(parent);
            let span = document.createElement('span');
            if (results[i]['class'].length > 0) span.classList.add(results[i]['class']);
            if (results[i]['space-before']) {
                let space = document.createTextNode(' ');
                parent.appendChild(space);
            }
            span.innerText = results[i]['val'];
            parent.appendChild(span);
        }

        let spans = document.getElementsByTagName('span');
        for (let s = 0; s < spans.length; s++) {
            spans[s].addEventListener('mouseover', function() {
                let className = this.classList.item(0);
                highlightWords(className);
            });
            spans[s].addEventListener('mouseout', function() {
                let className = this.classList.item(0);
                clearWords(className);
            });
        }
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