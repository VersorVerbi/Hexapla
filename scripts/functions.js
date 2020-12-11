function substr_count(haystack, needle) {
    return (haystack.match('/' + needle + '/g') || []).length;
}

function emptyBox(box) { // per benchmarking, this is the fastest way to clear children, at least in FF and Chrome at the time
    let kid = box.firstChild;
    while (kid) {
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