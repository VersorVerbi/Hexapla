function configTls() {
    let tlCfg = document.getElementById('translationController');
    tlCfg.classList.remove('hidden');
}

function addTl(ev) {
    ev.preventDefault();
    let targetBox = document.getElementsByClassName('potentialTl')[0];
    nomoreTl(ev);
    let sourceId = ev.dataTransfer.getData('text/plain');
    let source = document.getElementById(sourceId);
    source.parentNode.classList.remove('occupied');
    source.parentNode.removeChild(source);
    targetBox.innerHTML = ev.dataTransfer.getData('text/html')
    targetBox.classList.add('occupied');
    targetBox.firstChild.removeEventListener('dragstart', draggableStart);
    targetBox.firstChild.addEventListener('dragstart', draggableStart);

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

function potentialRemoveTl(ev) {
    ev.preventDefault();
    // TODO: add some signifier to explain what will happen?
}

function keepTl(ev) {
    ev.preventDefault();
    // TODO: cancel signifier if we add one
}

function returnVersion(dropObject, langTarget) {
    let list = document.getElementById('translList');
    let options = list.getElementsByTagName('div');
    let inLang = false;
    let o;
    for (o = 0; o < options.length; o++) {
        if (inLang) {
            if (options[o].classList.contains('langGroup') || options[o].innerText > dropObject.innerText) {
                list.insertBefore(dropObject, options[o]);
                break;
            }
        } else if (options[o].classList.contains('langGroup')) {
            inLang = (options[o].innerText === langTarget);
        }
    }
    if (o === options.length) {
        list.appendChild(dropObject);
    }
}

function removeTl(ev) {
    ev.preventDefault();
    let sourceId = ev.dataTransfer.getData('text/plain');
    let source = document.getElementById(sourceId);
    source.parentNode.classList.remove('occupied');
    source.parentNode.removeChild(source);
    if (sourceId === 'notes') {
        document.getElementById('show-notes').click();
    } else {
        let dropObject = document.createElement('div');
        dropObject.innerHTML = ev.dataTransfer.getData('text/html');
        dropObject = dropObject.firstChild;
        let langTarget = dropObject.dataset.lang;
        returnVersion(dropObject, langTarget);
        dropObject.removeEventListener('dragstart', draggableStart);
        dropObject.addEventListener('dragstart', draggableStart);
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
    let selectedList = document.getElementsByClassName('occupied');
    let translationArray = [];
    for (let o = 0; o < selectedList.length; o++) {
        translationArray.push(selectedList[o].firstChild.id);
    }
    document.getElementById('translations').value = translationArray.join('^');
    // TODO: handle refresh? re-search?

    fetch('/Hexapla/cookies.php?set&name=hexaplaTls&value=' + translationArray.join('^'));
}

function dropZoneSetup(zone, onEnter, onExit, onDrop, onOver) {
    zone.addEventListener('dragenter', onEnter);
    zone.addEventListener('dragexit', onExit);
    zone.addEventListener('drop', onDrop);
    zone.addEventListener('dragover', onOver);
}

function draggableStart(ev) {
    ev.dataTransfer.setData('text/plain', ev.target.id);
    ev.dataTransfer.setData('text/html', ev.target.outerHTML);
    ev.dataTransfer.dropEffect = 'move';
    ev.target.classList.add('pickedUp');
}

//TODO: otherwise handle the translation list