function generalDiff(el) {
    let curPage = window.location.href;
    let diffLoc = curPage.indexOf("diff");
    let elNum = el.name.match(/\d/g).join("");
    let outPage = "";
    if (curPage.endsWith("#")) {
        curPage = curPage.substr(0, curPage.length - 1);
    }
    if (el.checked) {
        if (diffLoc == -1) {
            outPage = curPage + "&diff=" + elNum;
        } else {
            outPage = curPage + elNum;
        }
    } else {
        let diffEnd = curPage.substr(diffLoc);
        diffEnd = diffEnd.replace(new RegExp(elNum, 'g'), "");
        outPage = curPage.substr(0, diffLoc) + diffEnd;
    }
    window.location = outPage;
}

function scrollMatch(el) {
    // check the scroll cookie
    let scrollValue = getCookieValue(scrollCookieName);
    if (parseInt(scrollValue)) {
        let target = el.scrollTop;
        let scrollboxes = document.getElementsByClassName("scrollbox");
        for (let i = 0; i < scrollboxes.length; i++) {
            if (scrollboxes[i] != el) {
                scrollboxes[i].scrollTop = target;
            }
        }
    }
}

function getPassage() {
    let url = window.location.href;
    let psgIndex = url.indexOf("search=");
    let psg;
    if (url.indexOf("&", psgIndex) != -1) {
        psg = url.substr(psgIndex + 7, url.indexOf("&", psgIndex) - (psgIndex + 7));
    } else {
        psg = url.substr(psgIndex + 7);
    }
    return psg;
}

function getTranslations() {
    let url = window.location.href;
    let trIndex = url.indexOf("tr=");
    let transls;
    if (url.indexOf("&", trIndex) != -1) {
        transls = url.substr(trIndex + 3, url.indexOf("&",trIndex)-(trIndex+3));
    } else {
        transls = url.substr(trIndex + 3);
    }
    return transls;
}

function getDiffs() {
    let url = window.location.href;
    let dfIndex = url.indexOf("diff=");
    if (dfIndex == -1) {
        return null;
    }
    let diffs;
    if (url.indexOf("&", dfIndex) != -1) {
        diffs = url.substr(dfIndex + 5, url.indexOf("&", dfIndex)-(dfIndex+5));
    } else {
        diffs = url.substr(dfIndex + 5);
    }
    if (diffs.indexOf("#") != -1) {
        diffs = diffs.replace("#", "");
    }
    return diffs;
}

function deleteTransl(btn) {
    let translBox = btn.parentElement.parentElement;
    let i = -1;
    switch(translBox.id) {
        case "first":
            i = 0;
            break;
        case "second":
            i = 1;
            break;
        case "third":
            i = 2;
            break;
        case "fourth":
            i = 3;
            break;
        case "fifth":
            i = 4;
            break;
        case "sixth":
            i = 5;
            break;
    }
    let curTrs = getTranslations();
    let trsArr = curTrs.split("+");
    trsArr.splice(i, 1);
    let outTrs = trsArr.join("+");
    let outHref = replaceGetVar("tr", outTrs);
    
    let diffs = getDiffs();
    if (diffs != null) {
        let diffArr = diffs.split("");
        for (let j = 0; j < diffArr.length; j++) {
            if (parseInt(diffArr[j]) > trsArr.length) {
                diffArr.splice(j, 1);
                j--;
            }
        }
        diffs = diffArr.join("");
        outHref = replaceGetVar("diff", diffs, outHref);
    }
    
    let translationCookieName = "hexaTlCookie";
    document.cookie = translationCookieName + "=" + trsArr.join("");
    window.location = outHref;
    return 0;
}

function promoteTransl(btn) {
    let translBox = btn.parentElement.parentElement;
    let curTrs = getTranslations();
    let i = 0;
    switch(translBox.id) {
        case "second":
            i = 1;
            break;
        case "third":
            i = 2;
            break;
        case "fourth":
            i = 3;
            break;
        case "fifth":
            i = 4;
            break;
        case "sixth":
            i = 5;
            break;
        default:
            return -1;
    }
    let trsArr = curTrs.split("+");
    let tmpTrs = trsArr[0];
    trsArr[0] = trsArr[i];
    for (let j = i; j > 1; j--) {
        trsArr[j] = trsArr[j - 1];
    }
    trsArr[1] = tmpTrs;
    let outTrs = trsArr.join("+");
    let outHref = replaceGetVar("tr", outTrs);
    let translationCookieName = "hexaTlCookie";
    document.cookie = translationCookieName + "=" + trsArr.join("");
    window.location = outHref;
    return 0;
}

let scrollboxes = document.getElementsByClassName("scrollbox");
for (let j = 0; j < scrollboxes.length; j++) {
    scrollboxes[j].addEventListener("scroll", function() {
        scrollMatch(this);
    }, false);
}

let els = new Array(document.getElementById('first'), document.getElementById('second'),
                    document.getElementById('third'), document.getElementById('fourth'),
                    document.getElementById('fifth'), document.getElementById('sixth'));
for (let i = 0; i < translCount; i++) {
    els[i].classList.add('of' + translCount);
}
for (i = translCount; i < els.length; i++) {
    els[i].classList.add('hidden');
}


document.getElementById("addtl").addEventListener("click", function() {
    // show translation selection
    document.getElementById("translModal").classList.remove("hidden");
});
document.getElementById("addTlSubmit").addEventListener("click", function() {
    // hide translation selector
    let elSelect = document.getElementById("translSelect");
    let addition = elSelect.options[elSelect.selectedIndex].value;
    document.getElementById("translModal").classList.add("hidden");
    // add selection to URL
    let curTrs = getTranslations();
    let trsArr = curTrs.split("+");
    trsArr.push(addition);
    let outTrs = trsArr.join("+");
    let outHref = replaceGetVar("tr", outTrs);
    let translationCookieName = "hexaTlCookie";
    document.cookie = translationCookieName + "=" + outTrs;
    window.location = outHref;
});
document.getElementById("cancelAdd").addEventListener("click", function() {
    document.getElementById("translModal").classList.add("hidden");
});
let deletes = document.getElementsByClassName("del");
let promotes = document.getElementsByClassName("promote");
for (i = 0; i < deletes.length; i++) {
    deletes[i].addEventListener("click", function() {
        deleteTransl(this);
    });
}
for (i = 0; i < promotes.length; i++) {
    promotes[i].addEventListener("click", function() {
        promoteTransl(this);
    });
}

document.getElementById("prevPsg").addEventListener("click", function() {
    let href = window.location.href;
    if (href.indexOf("#") != -1) href = href.replace("#","");
    window.location = href + "&decr=1"
});
document.getElementById("nextPsg").addEventListener("click", function() {
    let href = window.location.href;
    if (href.indexOf("#") != -1) href = href.replace("#","");
    window.location = href + "&incr=1"
});