function addTl(ev) {
    ev.preventDefault();
    let targetBox = document.getElementsByClassName('potentialTl')[0];
    nomoreTl();
    let sourceId = ev.dataTransfer.getData('text/plain');
    let source = document.getElementById(sourceId);
    source.parentNode.removeChild(source);
    targetBox.innerHTML = ev.dataTransfer.getData('text/html')
    targetBox.classList.add('occupied');
}

function potentialTl(ev) {
    ev.preventDefault();

    let boxes = document.getElementsByClassName('tlBox');
    for (let b = 0; b < boxes.length; b++) {
        if (boxes[b].childElementCount === 0) {
            boxes[b].classList.add('potentialTl');
            break;
        }
    }
}

function nomoreTl(ev) {
    if (ev) {
        ev.preventDefault();
    }
    let boxes = document.getElementsByClassName('tlBox');
    for (let b = 0; b < boxes.length; b++) {
        boxes[b].classList.remove('potentialTl');
    }
}

function closeTlConfig() {
    let cfg = document.getElementById('translationController');
    cfg.classList.add('hidden');
    //TODO: actually handle changes, et al
}

//TODO: handle moving between boxes, deletion, et al
//TODO: include "notes" option and make it available based on whether logged in
//TODO: otherwise handle the translation list