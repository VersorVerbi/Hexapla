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
    let noticeDiv = document.querySelector('#t' + translationId + ' .resultNotice');
    noticeDiv.innerHTML = noticeHTML;
    noticeDiv.classList.add(noticeClasses(noticeLevel));
    noticeDiv.classList.remove('hidden');
}

function hideNotice(translationId) {
    let noticeDiv = document.querySelector('#t' + translationId + ' .resultNotice');
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