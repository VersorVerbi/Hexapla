async function getDiffCookies() {
    let output = {};
    return fetch(INTERNAL_API_PATH + 'cookies.php?name=hexaplaWord') // RELATIVE-URL
        .then(result => result.text().then(data1 => {
            if (isNaN(+data1)) data1 = (data1 === 'true' ? 1 : 0);
            output.word = +data1;
            return output;
        }))
        .then(() =>
            fetch(INTERNAL_API_PATH + 'cookies.php?name=hexaplaCaseSens') // RELATIVE-URL
                .then(result => result.text().then(data2 => {
                    if (isNaN(+data2)) data2 = (data2 === 'true' ? 1 : 0);
                    output.case = +data2;
                    return output;
                }))
        );
}

function getDiffClass(section) {
    for (let c = 0; c < section.classList.length; c++) {
        if (section.classList[c].substring(0, 3) === 'diff') {
            return section.classList[c];
        }
    }
    return '';
}

// TODO: confirm all functionality has been duplicated, then delete stuff we don't need
/*
function turnOffDiffing(parentElement, removeList) {
    let removeArray = [];
    for (let r = 0; r < removeList.length; r++) {
        removeArray.push(removeList[r]);
        removeArray.push(removeList[r].substring(0, -1) + 'L');
    }
    let removeQuery = '.' + removeArray.join(', .');
    let diffedWords = document.querySelectorAll(removeQuery);
    for (let w = 0; w < diffedWords.length; w++) {
        diffedWords[w].classList.remove(...removeArray);
    }
    parentElement.classList.remove('diffL', ...removeArray);
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
        for (let v = 0; v < versions.length; v++) {
            hideNotice(versions[v].id);
        }
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
                    versions[v].classList.add('diffL');
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
                } else if (versions[v].dataset.canDiff > 0) {
                    let textArea = versions[v].getElementsByClassName('textArea')[0];
                    let leftClass = 'diff' + diffs + 'L';
                    let rightClass = 'diff' + diffs++ + 'R';
                    // noinspection JSUnusedAssignment
                    let results = diff(baseVersion, textArea, cookies.word, cookies.case, leftClass, rightClass);
                    // noinspection JSUnusedAssignment
                    baseVersion.innerHTML = results[0].innerHTML;
                    textArea.innerHTML = results[1].innerHTML;
                    versions[v].classList.add(rightClass);
                    swapAddButton(diffBtn);
                } else {
                    showNotice(id, "This version is not allowed to be diffed. <a href=''>Why?</a>", 2);
                    diffBtn.disabled = true;
                }
            }
        }
        resetWordHovers();
    });
}

// TODO: sometimes a space has highlighting for no reason?*/

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
    constructor(...args) {
        super();
        if (args.length === 2) {
            let tagObj = args[0];
            let byWord = args[1];
            let kids = tagObj.childNodes;
            for (let k = 0; k < kids.length; k++) {
                if (kids[k].nodeType === Node.ELEMENT_NODE) {
                    if (kids[k].innerHTML !== kids[k].innerText) {
                        this.push(...new DiffList(kids[k], byWord)); // 2-param mode
                    } else {
                        this.push(...new DiffList(kids[k].innerText, kids[k], byWord)); // 3-param mode
                    }
                } else if (kids[k].nodeType === Node.TEXT_NODE) {
                    this.push(...new DiffList(kids[k].data, kids[k], byWord)); // 3-param mode
                }
            }
        } else if (args.length === 3) {
            let nodeText = args[0].replace(/\s+/g, ' ');
            let node = args[1];
            let byWord = args[2];
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
                    this.push(new DiffNode(chunkTag, byWord));
                }
                if (i < list.length - 1) {
                    let spaceTag = dce.call(document, nodeName);
                    if (className !== null) {
                        spaceTag.className = className;
                    }
                    spaceTag.textContent = ' ';
                    this.push(new DiffNode(spaceTag, byWord, true));
                }
            }
        }
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

class LongestCommonSubsequence {
    constructor(baseObj, newObj, caseSensitive) {
        this.baseDiffList = baseObj;
        this.newDiffList = newObj;
        this.baseLength = baseObj.getDiffLength();
        this.newLength = newObj.getDiffLength();
        this.table = new Array(this.baseLength);
        for (let i = 0; i < this.table.length; i++) {
            this.table[i] = new Array(this.newLength);
        }
        this.caseSensitive = caseSensitive;
    }

    prep() {
        this.table[0][0] = 0;
        for (let i = 1; i < this.baseLength; i++) {
            this.table[i][0] = 0;
        }
        for (let j = 1; j < this.newLength; j++) {
            this.table[0][j] = 0;
        }
    }

    populate() {
        // TODO: what's happening here? add comments
        for (let i = 1; i < this.baseLength; i++) {
            for (let j = 1; j < this.newLength; j++) {
                if (myLocaleCompare(wordCharactersOnly(this.baseDiffList.get(i).item), wordCharactersOnly(this.newDiffList.get(j).item), this.caseSensitive) === 0) {
                    this.table[i][j] = this.table[i - 1][j - 1] + 1;
                } else {
                    this.table[i][j] = Math.max(this.table[i - 1][j], this.table[i][j - 1]);
                }
            }
        }
    }

    outputDiff() {
        this.output = [];
        this.output[0] = new Array(this.baseLength);
        this.output[1] = new Array(this.newLength);

        this.diffRecursive(this.baseLength - 1, this.newLength - 1);

        return this.output;
    }

    diffRecursive(i, j) {
        if (i > 0 && j > 0 && myLocaleCompare(wordCharactersOnly(this.baseDiffList.get(i).item), wordCharactersOnly(this.newDiffList.get(j).item), this.caseSensitive) === 0) {
            // I and J are identical. Reduce both. Add arr1[i] //TODO: what does this mean?
            this.output[0][i] = 0;
            this.output[1][j] = 0;
            this.diffRecursive(i - 1, j - 1);
        } else if (j > 0 && (i === 0 || this.table[i][j - 1] >= this.table[i - 1][j])) {
            // I and J are different. Reducing J. Add arr2[j]
            this.output[1][j] = 1;
            this.diffRecursive(i, j - 1);
        } else if (i > 0 && (j === 0 || this.table[i][j - 1] < this.table[i - 1][j])) {
            // I and J are different. Reducing I. Add arr1[i]
            this.output[0][i] = 1;
            this.diffRecursive(i - 1, j);
        }
        // now I and J are both zero
    }
}


/**
 *
 */
class DiffControl {
    // field declarations are still experimental, so these are just comments for our notes (for now)
    // baseVersion: HTMLElement
    // versions: Array = [list of ALL HTML elements (including baseVersion)]
    // diffed: Array = [list of versions that are showing differences]
    // byWord: Boolean
    // caseSensitive: Boolean
    // baseDiffList: DiffList
    // diffLists: Array = [DiffList objects where idx = .diffed idx]
    // baseData: Array = [duplicates of original .version elements]

    static get ALL_LEVELS() {
        return [DiffControl.LEVEL_X_LEFT(2), DiffControl.LEVEL_X_RIGHT(2), DiffControl.LEVEL_X_LEFT(3),
            DiffControl.LEVEL_X_RIGHT(3), DiffControl.LEVEL_X_LEFT(4), DiffControl.LEVEL_X_RIGHT(4),
            DiffControl.LEVEL_X_LEFT(5), DiffControl.LEVEL_X_RIGHT(5), DiffControl.LEVEL_X_LEFT(6),
            DiffControl.LEVEL_X_RIGHT(6)];
    }
    static LEVEL_X_LEFT(x) { return 'diff' + x + 'L'; }
    static LEVEL_X_RIGHT(x) { return 'diff' + x + 'R'; }

    /**
     *
     * @param baseVersion
     * @param cookies
     * @param versions
     */
    constructor(baseVersion, cookies, versions) {
        this.baseVersion = baseVersion;
        this.byWord = cookies.word;
        this.caseSensitive = cookies.case;
        this.versions = [...versions];
        this.diffed = [];
        this.baseDiffList = null;
        this.diffLists = [];
        this.baseData = [];
        for(let v = 0; v < versions.length; v++) {
            let vCopy = document.createElement('div');
            vCopy.innerHTML = versions[v].innerHTML;
            this.baseData.push(vCopy);
        }
    }

    add(version) {
        if (!this.baseVersion) {
            this.changeBaseVersion(version);
            this.diffed.push(version);
        }
        if (version === this.baseVersion) {
            return;
        }
        if (version.dataset.lang !== this.baseVersion.dataset.lang) {
            // can't diff between languages
            version.getElementsByClassName('diffButton')[0].disabled = true; // there's only one in each version
            let lang1 = this.baseVersion.dataset.langName;
            let lang2 = version.dataset.langName;
            showNotice(version.id, `We can only show differences in a single language. To switch from ${lang1} to ${lang2}, click <a href=''>here</a>.`, 2);
            return;
        }
        this.diffed.push(version);
        this.diff(version);
    }

    remove(version) {
        let idx = this.diffed.indexOf(version);
        this.diffed.splice(idx, 1);
        this.diffLists.splice(idx, 1);
        if (version === this.baseVersion) {
            this.baseDiffList = null;
            let spans = document.querySelectorAll('.version span');
            for (let s = 0; s < spans.length; s++) {
                spans[s].classList.remove(...DiffControl.ALL_LEVELS);
            }
            if (this.diffed.length > 0) {
                this.baseVersion = this.diffed[0];
                if (this.diffed.length > 1) {
                    this.rediff();
                }
            } else {
                this.baseVersion = null;
            }
        } else {
            let leftClass = DiffControl.LEVEL_X_LEFT(idx + 1);
            let rightClass = DiffControl.LEVEL_X_RIGHT(idx + 1);
            let spans = document.querySelectorAll('#' + version.id + ' span, #' + this.baseVersion.id + ' span');
            for (let s = 0; s < spans.length; s++) {
                spans[s].classList.remove(leftClass, rightClass);
            }
        }
        // TODO: swap buttons back
    }

    diff(version) {
        let leftClass = DiffControl.LEVEL_X_LEFT(this.diffed.length);
        let rightClass = DiffControl.LEVEL_X_RIGHT(this.diffed.length);

        let baseText = this.baseVersion.getElementsByClassName('textArea')[0];
        let compText = version.getElementsByClassName('textArea')[0];

        let lcs, obj;
        if (!this.baseDiffList) {
            this.baseDiffList = new DiffList(baseText, this.byWord);
            this.baseDiffList.unshift("");
        }
        let diffIdx = this.diffed.indexOf(version);
        if (this.diffLists[diffIdx]) {
            obj = this.diffLists[diffIdx];
        } else {
            obj = new DiffList(compText, this.byWord);
            obj.unshift("");
            // we add an irrelevant blank to the beginning so i/j can always match the LCS array
            // without having to modify them with magic numbers

            this.diffLists.push(obj);
        }

        // prepare for the longest common subsequence (LCS) framework
        lcs = new LongestCommonSubsequence(this.baseDiffList, obj, this.caseSensitive);
        lcs.prep();

        // populate the LCS table
        lcs.populate();

        // create the output array
        let finalArray = lcs.outputDiff();

        this.baseVersion.getElementsByClassName('textArea')[0].innerHTML = this.getOutput(finalArray[0], this.baseDiffList, leftClass).innerHTML;
        version.getElementsByClassName('textArea')[0].innerHTML = this.getOutput(finalArray[1], obj, rightClass).innerHTML;
    }

    diffAll() {
        let added = true;
        if (this.versions.length === this.diffed.length || this.diffed.length === document.querySelectorAll('.diffButton:not([disabled])').length) {
            this.versions.forEach(this.remove, this);
            added = false;
            // TODO: hide notices
        } else {
            this.versions.forEach(this.add, this);
        }
        return added;
    }

    updateCookies(cookies) {
        let reload = !this.byWord && cookies.word;
        this.byWord = cookies.word;
        this.caseSensitive = cookies.case;

        // erase all existing diffs
        this.diffLists = [];
        this.baseDiffList = null;

        // switch between character diff and word diff means we need to reload the original dataset
        // because after character diff, spans only contain 1 character each
        if (reload) {
            this.diffed.forEach(value => {
                let idx = this.versions.indexOf(value);
                value.innerHTML = this.baseData[idx].innerHTML;
            });
        }

        this.rediff();
    }

    changeBaseVersion(newBase) {
        this.baseVersion = newBase;
    }

    rediff() {
        let list = [...this.diffed];
        let diffLists = [...this.diffLists];
        list.forEach(this.remove, this);
        this.diffLists = [...diffLists];
        list.forEach(this.add, this);
    }

    getOutput(finalSet, obj, classToAdd) {
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
                if (finalSet[i] === 0 || (this.byWord && text === " ")) {
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
}


let diffCtrl;
document.addEventListener('DOMContentLoaded', function() {
    getDiffCookies().then(cookies => {
        diffCtrl = new DiffControl(null, cookies, document.querySelectorAll('.version[data-can-diff="4"]'));
    });
});

function diffAll() {
    let active = diffCtrl.diffAll();
    let btns = document.querySelectorAll('.diffButton:not([disabled])');
    for (let b = 0; b < btns.length; b++) {
        let swap = false;
        if (active) {
            swap = (btns[b].getElementsByClassName('icofont-plus').length > 0);
        } else {
            swap = (btns[b].getElementsByClassName('icofont-minus').length > 0);
        }
        if (swap) swapAddButton(btns[b]);
    }
    resetWordHovers();
}

function addDiff(button) {
    let section = button.closest('.version');
    diffCtrl.add(section);
    swapAddButton(button);
    resetWordHovers();
}