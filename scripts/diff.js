async function getDiffCookies() {
    let output = {};
    return fetch('/Hexapla/cookies.php?name=hexaplaWord') // RELATIVE-URL
        .then(result => result.text().then(data1 => {
            output.word = +data1;
            return output;
        }))
        .then(() =>
            fetch('/Hexapla/cookies.php?name=hexaplaCaseSens') // RELATIVE-URL
                .then(result => result.text().then(data2 => {
                    output.case = +data2;
                    return output;
                }))
        );
}

function addDiff(button) {
    let remove = swapAddButton(button);
    let section = button.closest('.version');
    if (remove) {
        turnOffDiffing(section, [section]);
        // TODO: remove diff#L/R from companion version
        // TODO: what happens if this is the base version?
    } else {
        getDiffCookies().then(cookies => {

            resetWordHovers();
        });
        // TODO: rework this + diffAll to be more explicit on which versions are involved (e.g., class names)
        //      then simplify both so this makes more sense
    }
}

function turnOffDiffing(parentElement, versions) {
    let removeArray = ['diff2L', 'diff2R', 'diff3L', 'diff3R', 'diff4L', 'diff4R', 'diff5L', 'diff5R', 'diff6L', 'diff6R'];
    let removeQuery = '.' + removeArray.join(', .');
    let diffedWords = parentElement.querySelectorAll(removeQuery);
    for (let w = 0; w < diffedWords.length; w++) {
        diffedWords[w].classList.remove(...removeArray);
    }
    for (let v = 0; v < versions.length; v++) {
        hideNotice(versions[v].id);
    }
    let diffButtons = parentElement.getElementsByClassName('diffButton');
    for (let b = 0; b < diffButtons.length; b++) {
        diffButtons[b].disabled = false; // TODO: is this ever "always on" for versions that don't allow diffing?
        if (diffButtons[b].getElementsByClassName('icofont-minus').length) swapAddButton(diffButtons[b]);
    }
}

function diffAll() {
    let diffedStuff = document.querySelectorAll('#my-notes-container, .version .diffButton .icofont-minus, .version .diffButton[disabled]');
    let versions = document.querySelectorAll('.version');
    if (diffedStuff.length === versions.length) { // if diff is turned on everywhere, turn it off
        turnOffDiffing(document, versions);
        return;
    } // otherwise, turn it on everywhere
    getDiffCookies().then(cookies => {
        let versions = document.getElementsByClassName('version');
        let baseVersion, baseLang;
        let diffs = 2;
        for (let v = 0; v < versions.length; v++) {
            let diffBtn = versions[v].getElementsByTagName('button')[0];
            let id = versions[v].id;
            if (id === 'my-notes-container') {
                continue;
            }
            if (typeof baseVersion === 'undefined') {
                if (versions[v].dataset.canDiff > 0) {
                    baseVersion = versions[v].getElementsByClassName('textArea')[0];
                    baseLang = versions[v].dataset.lang;
                    swapAddButton(diffBtn);
                } else {
                    showNotice(id, "This version is not allowed to be diffed. <a href=''>Why?</a>", 2);
                    diffBtn.disabled = true;
                }
            } else {
                // noinspection JSUnusedAssignment
                if (versions[v].dataset.lang !== baseLang) {
                    showNotice(id, "We can only show differences in a single language. To switch from #lang1 to #lang2, click <a href=''>here</a>.", 2);
                    diffBtn.disabled = true;
                } else {
                    let textArea = versions[v].getElementsByClassName('textArea')[0];
                    // noinspection JSUnusedAssignment
                    let results = diff(baseVersion, textArea, cookies.word, cookies.case, 'diff' + diffs + 'L', 'diff' + diffs++ + 'R');
                    // noinspection JSUnusedAssignment
                    baseVersion.innerHTML = results[0].innerHTML;
                    textArea.innerHTML = results[1].innerHTML;
                    swapAddButton(diffBtn);
                }
            }
        }
        resetWordHovers();
    });
}


function diff(div1, div2, byWord, caseSensitive, ltClass, rtClass) {
    let outStrs = [];
    let lcs, obj1, obj2;
    let finalArray = new Array(2);
    obj1 = getTagContent(div1, byWord);
    obj1.unshift("");
    obj2 = getTagContent(div2, byWord);
    obj2.unshift("");
    // we add an irrelevant blank to the beginning so i/j can always match the LCS array
    // without having to modify them with magic numbers

    // prepare for the longest common subsequence (LCS) framework
    lcs = createLcsArray(obj1.getDiffLength(), obj2.getDiffLength());
    lcsPrep(lcs, obj1, obj2);

    // populate the LCS table
    populateLcsTable(lcs, obj1, obj2, caseSensitive);

    // create the output array
    finalArray[0] = new Array(obj1.getDiffLength());
    finalArray[1] = new Array(obj2.getDiffLength());
    outputDiff(lcs, obj1, obj2, obj1.getDiffLength(), obj2.getDiffLength(), caseSensitive, finalArray);
    outStrs.push(getOutput(finalArray[0], div1, obj1, ltClass, byWord));
    outStrs.push(getOutput(finalArray[1], div2, obj2, rtClass, byWord));

    return outStrs;
}

// TODO: sometimes a space has highlighting for no reason?

function getTagContent(tagObj, byWord) {
    let breakoutNodes = new DiffList();
    let kids = tagObj.childNodes;
    for (let k = 0; k < kids.length; k++) {
        if (kids[k].nodeType === Node.ELEMENT_NODE) {
            if (kids[k].innerHTML !== kids[k].innerText) {
                breakoutNodes.push(...getTagContent(kids[k], byWord));
            } else {
                breakoutNodes.push(...putTextInNodes(kids[k].innerText, kids[k], byWord));
            }
        } else if (kids[k].nodeType === Node.TEXT_NODE) {
            breakoutNodes.push(...putTextInNodes(kids[k].data, kids[k], byWord));
        }
    }
    return breakoutNodes;
}

function putTextInNodes(nodeText, node, byWord) {
    nodeText = nodeText.replace(/\s+/g, ' ');
    let nodeList = new DiffList();
    let nodeType = node.nodeType;
    let nodeName, dce, className;
    if (nodeType === Node.ELEMENT_NODE) {
        dce = document.createElement;
        nodeName = node.nodeName;
        className = node.className;
    } else if (nodeType === Node.TEXT_NODE) {
        dce = document.createTextNode;
        nodeName = "";
        className = null;
    }
    let list = nodeText.split(/\s/);
    for (let i = 0; i < list.length; i++) {
        let chunkTag = dce.call(document, nodeName);
        if (className !== null) {
            chunkTag.className = className;
        }
        chunkTag.textContent = list[i];
        if (chunkTag.textContent !== "") {
            nodeList.push(new DiffNode(chunkTag, byWord));
        }
        if (i < list.length - 1) {
            let spaceTag = dce.call(document, nodeName);
            if (className !== null) {
                spaceTag.className = className;
            }
            spaceTag.textContent = ' ';
            nodeList.push(new DiffNode(spaceTag, byWord, true));
        }
    }
    return nodeList;
}

function createLcsArray(dim1, dim2) {
    let lcs = new Array(dim1);
    for (let i = 0; i < lcs.length; i++) {
        lcs[i] = new Array(dim2);
    }
    return lcs;
}

function outputDiff(lcs, obj1, obj2, i, j, caseSensitive, finalArray) {
    if (i > 0 && j > 0 && myLocaleCompare(wordCharactersOnly(obj1.get(i).item), wordCharactersOnly(obj2.get(j).item), caseSensitive) === 0) {
        // I and J are identical. Reduce both. Add arr1[i] //TODO: what does this mean?
        finalArray[0][i] = 0;
        finalArray[1][j] = 0;
        outputDiff(lcs, obj1, obj2, i - 1, j - 1, caseSensitive, finalArray);
    } else if (j > 0 && (i === 0 || lcs[i][j - 1] >= lcs[i - 1][j])) {
        // I and J are different. Reducing J. Add arr2[j]
        finalArray[1][j] = 1;
        outputDiff(lcs, obj1, obj2, i, j - 1, caseSensitive, finalArray);
    } else if (i > 0 && (j === 0 || lcs[i][j - 1] < lcs[i - 1][j])) {
        // I and J are different. Reducing I. Add arr1[i]
        finalArray[0][i] = 1;
        outputDiff(lcs, obj1, obj2, i - 1, j, caseSensitive, finalArray);
    }
    // now I and J are both zero
}

function populateLcsTable(lcs, obj1, obj2, caseSensitive) {
    // TODO: what's happening here? add comments
    for (let i = 1; i < obj1.getDiffLength(); i++) {
        for (let j = 1; j < obj2.getDiffLength(); j++) {
            if (myLocaleCompare(wordCharactersOnly(obj1.get(i).item), wordCharactersOnly(obj2.get(j).item), caseSensitive) === 0) {
                lcs[i][j] = lcs[i - 1][j - 1] + 1;
            } else {
                lcs[i][j] = Math.max(lcs[i - 1][j], lcs[i][j - 1]);
            }
        }
    }
}

function lcsPrep(lcs, obj1, obj2) {
    lcs[0][0] = 0;
    for (let i = 1; i < obj1.getDiffLength(); i++) {
        lcs[i][0] = 0;
    }
    for (let j = 1; j < obj2.getDiffLength(); j++) {
        lcs[0][j] = 0;
    }
}

function getOutput(finalSet, sourceDiv, obj, classToAdd, byWord) {
    let output = document.createElement('div');
    for (let i = 1; i < finalSet.length; i++) {
        let chunk = obj.get(i);
        let text = chunk.item;
        let node = chunk.node;
        let newNode;
        if (node.nodeType === Node.ELEMENT_NODE) {
            if (['div', 'span'].includes(node.tagName.toLowerCase())) {
                newNode = node.cloneNode();
                newNode.textContent = text;
                if (finalSet[i] !== 0) {
                    newNode.classList.add(classToAdd);
                }
            } else {
                continue; // ignore tags we wouldn't use (security!)
            }
        } else {
            if (finalSet[i] === 0 || (byWord && text === " ")) {
                newNode = document.createTextNode(text);
            } else {
                newNode = document.createElement('span');
                newNode.classList.add(classToAdd);
                newNode.textContent = text;
            }
        }
        output.appendChild(newNode);
    }
    return output;
}

class DiffNode {
    constructor(sourceNode, byWord, isSpace = false) {
        this.node = sourceNode;
        if (isSpace) {
            this.length = 1;
        } else if (byWord) {
            this.words = sourceNode.textContent.split(/\s/);
            this.length = this.words.length;
        } else {
            this.length = sourceNode.textContent.length;
        }
    }
}

class DiffList extends Array {
    constructor() {
        super();
    }

    getDiffLength() {
        let len = 0;
        this.forEach(item => {
            len += item.length;
        })
        return len;
    }

    get(i) {
        let item, sourceNode;
        let k = 0;

        for (let j = 0; j < this.length; j++) {
            if (typeof this[j].words === 'undefined') {
                if (this[j].length + k < i) {
                    k += this[j].length;
                } else {
                    let letter = i - k - 1;
                    item = this[j].node.textContent.substr(letter, 1);
                    sourceNode = this[j].node;
                    break;
                }
            } else {
                if (this[j].length + k < i) {
                    k += this[j].length;
                } else {
                    item = this[j].words[i - k - 1];
                    sourceNode = this[j].node;
                    break;
                }
            }
        }

        return {'item': item, 'node': sourceNode};
    }
}