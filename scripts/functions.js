function substr_count(haystack, needle) {
    let re = new RegExp(needle, 'g');
    return (haystack.match(re) || []).length;
}

function emptyBox(box, except = null) { // per benchmarking, this is the fastest way to clear children, at least in FF and Chrome at the time
    let kid = box.firstChild;
    while (kid) {
        while (except !== null && kid.id === except) {
            kid = kid.nextSibling;
            if (!kid) {
                return;
            }
        }
        kid.remove();
        kid = box.firstChild;
    }
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

function showHideStrongs(show, evt) {
    let cls = this.classList;
    for (let c = 0; c < cls.length; c++) {
        if (['H','G'].includes(cls[c].substring(0,1).toUpperCase())) {
            if (show) {
                highlightWords(cls[c]);
            } else {
                clearWords(cls[c]);
            }
        }
    }
}

function showNotice(translationId, noticeHTML, noticeLevel) {
    let noticeDiv = document.querySelector('#' + translationId + ' .resultNotice');
    noticeDiv.innerHTML = noticeHTML;
    noticeDiv.classList.add(noticeClasses(noticeLevel));
    noticeDiv.classList.remove('hidden');
}

function hideNotice(translationId) {
    let noticeDiv = document.querySelector('#' + translationId + ' .resultNotice');
    noticeDiv.classList.add('hidden');
    noticeDiv.classList.remove(...noticeClasses());
}

function noticeClasses(noticeLevel = 0) {
    if (noticeLevel > 0) {
        switch (noticeLevel) {
            case 1:
                return 'noticeFyi';
            case 2:
                return 'noticeWarning';
            case 3:
                return 'noticeError';
            default:
                return '';
        }
    }
    return ['noticeFyi', 'noticeWarning', 'noticeError'];
}

function myLocaleCompare(str1, str2, caseSensitive) {
    if (caseSensitive) {
        return str1.localeCompare(str2);
    } else {
        return str1.toLowerCase().localeCompare(str2.toLowerCase());
    }
}

function wordCharactersOnly(str) {
    let pattern = new RegExp(/(?!\p{L})(?!\p{M})./gu);
    return str.replaceAll(pattern, "");
}

function swapAddButton(btn) {
    let remove = false;
    let removeClass = "icofont-minus";
    let addClass = "icofont-plus";
    let modSpan = btn.getElementsByClassName(addClass)[0];
    if (typeof modSpan === 'undefined') {
        remove = true;
        modSpan = btn.getElementsByClassName(removeClass)[0];
    }
    if (remove) {
        btn.title = btn.title.replace(/^Hide\b/, "Show");
        modSpan.classList.remove(removeClass);
        modSpan.classList.add(addClass);
    } else {
        btn.title = btn.title.replace(/^Show\b/, "Hide");
        modSpan.classList.remove(addClass);
        modSpan.classList.add(removeClass);
    }
    return remove;
}

function toggleButton() {
    toggleClass(this, 'clicked');
}

function toggleClass(element, className) {
    if (element.classList.contains(className)) {
        element.classList.remove(className);
    } else {
        element.classList.add(className);
    }
}

function fetchLiturgicalColor() {
    let target = '/Hexapla/liturgical-color.php?date='; // RELATIVE-URL
    let date = new Date();
    target = target + date.getDate() + '-' + (date.getMonth() + 1) + '-' + date.getFullYear();
    return fetch(target).then(result => result.text());
}

function joinObj(obj, withPropLabel, separator = ',') {
    let str = '';
    let i = 0;
    for (let prop in obj) {
        if (obj.hasOwnProperty(prop)) {
            if (withPropLabel) {
                str += prop + ': ';
            }
            str += obj[prop];
        }
        if (i++ > 0) str += separator;
    }
    str.substring(0, str.length - 2);
    return str;
}

function toTitleCase(str) {
    let arr = str.split(' ');
    let output = '';
    for (let i = 0; i < arr.length; i++) {
        output += arr[i].substring(0, 1).toUpperCase() + arr[i].substring(1) + ' ';
    }
    return output.substring(0, output.length - 1);
}

async function saveNote(locIds, noteText, noteId = null, lastSaveElement) {
    let waitForIt = (lastSaveElement === null);
    let frm = new FormData();
    frm.append('loc_id', locIds);
    frm.append('note', noteText);
    if (noteId !== null) {
        frm.append('note_id', noteId);
    }
    let saveOperation = await fetch('/Hexapla/save-notes.php', { // RELATIVE-URL
        method: 'POST',
        mode: 'same-origin',
        redirect: 'error',
        body: frm
    });
    if (waitForIt) {
        let data = await saveOperation.json();
        if (!data) {
            showAutosaveError();
        } else {
            document.getElementById('currentNoteId').value = data;
        }
    } else {
        saveOperation.json().then(data => {
            if (data) {
                lastSaveElement.innerText = new Date().toTimeString();
                lastSaveElement.id = 'autosave_time';
                let outer = document.createElement('span');
                outer.innerText = 'Autosaved at ';
                outer.appendChild(lastSaveElement);
                showNotice('my-notes-container', outer.outerHTML, 1);
                document.getElementById('currentNoteId').value = data;
            } else {
                showAutosaveError();
            }
        }).catch(() => {
            showAutosaveError();
        });
    }
}

async function autosave(synchronous) {
    let input = document.getElementById('my-notes');
    if (input) {
        if (input.value.length > 0) {
            let loc_ids = document.getElementById('currentLocationIds').value;
            if (loc_ids.length === 0) {
                if (!synchronous) showAutosaveError();
            } else {
                let note_id = document.getElementById('currentNoteId').value;
                if (note_id.length === 0) note_id = null;
                if (!synchronous) {
                    let last_saved = document.getElementById('autosave_time');
                    if (!last_saved) last_saved = document.createElement('span');
                    saveNote(loc_ids, input.value, note_id, last_saved);
                } else {
                    await saveNote(loc_ids, input.value, note_id, null);
                }
            }
        }
        if (!synchronous) setTimeout(autosave, 300000);
    }
}

function showAutosaveError() {
    showNotice('my-notes-container',
        'Autosave has <strong>failed</strong>. Please copy your notes to a secure location until our system is working again.',
        3);
}

function numbersOnly(str) {
    let output = '';
    for (let i = 0; i < str.length; i++) {
        let char = str.substr(i, 1);
        if (isNaN(char)) continue;
        output += char;
    }
    return parseInt(output);
}