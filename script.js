function generalDiff(el) {
    var curPage = window.location.href;
    var diffLoc = curPage.indexOf("diff");
    var elNum = el.name.match(/\d/g).join("");
    var outPage = "";
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
        var diffEnd = curPage.substr(diffLoc);
        diffEnd = diffEnd.replace(new RegExp(elNum, 'g'), "");
        outPage = curPage.substr(0, diffLoc) + diffEnd;
    }
    window.location = outPage;
}

function scrollMatch(el) {
    // check the scroll cookie
    var scrollValue = getCookieValue(scrollCookieName);
    if (parseInt(scrollValue)) {
        var target = el.scrollTop;
        var scrollboxes = document.getElementsByClassName("scrollbox");
        for (var i = 0; i < scrollboxes.length; i++) {
            if (scrollboxes[i] != el) {
                scrollboxes[i].scrollTop = target;
            }
        }
    }
}

function getPassage() {
    var url = window.location.href;
    var psgIndex = url.indexOf("search=");
    var psg;
    if (url.indexOf("&", psgIndex) != -1) {
        psg = url.substr(psgIndex + 7, url.indexOf("&", psgIndex) - (psgIndex + 7));
    } else {
        psg = url.substr(psgIndex + 7);
    }
    return psg;
}

function getTranslations() {
    var url = window.location.href;
    var trIndex = url.indexOf("tr=");
    var transls;
    if (url.indexOf("&", trIndex) != -1) {
        transls = url.substr(trIndex + 3, url.indexOf("&",trIndex)-(trIndex+3));
    } else {
        transls = url.substr(trIndex + 3);
    }
    return transls;
}

function getDiffs() {
    var url = window.location.href;
    var dfIndex = url.indexOf("diff=");
    if (dfIndex == -1) {
        return null;
    }
    var diffs;
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
    var translBox = btn.parentElement.parentElement;
    var i = -1;
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
    var curTrs = getTranslations();
    var trsArr = curTrs.split("+");
    trsArr.splice(i, 1);
    var outTrs = trsArr.join("+");
    var outHref = replaceGetVar("tr", outTrs);
    
    var diffs = getDiffs();
    if (diffs != null) {
        var diffArr = diffs.split("");
        for (var j = 0; j < diffArr.length; j++) {
            if (parseInt(diffArr[j]) > trsArr.length) {
                diffArr.splice(j, 1);
                j--;
            }
        }
        diffs = diffArr.join("");
        outHref = replaceGetVar("diff", diffs, outHref);
    }
    
    var translationCookieName = "hexaTlCookie";
    document.cookie = translationCookieName + "=" + trsArr.join("");
    window.location = outHref;
    return 0;
}

function promoteTransl(btn) {
    var translBox = btn.parentElement.parentElement;
    var curTrs = getTranslations();
    var i = 0;
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
    var trsArr = curTrs.split("+");
    var tmpTrs = trsArr[0];
    trsArr[0] = trsArr[i];
    for (var j = i; j > 1; j--) {
        trsArr[j] = trsArr[j - 1];
    }
    trsArr[1] = tmpTrs;
    var outTrs = trsArr.join("+");
    var outHref = replaceGetVar("tr", outTrs);
    var translationCookieName = "hexaTlCookie";
    document.cookie = translationCookieName + "=" + trsArr.join("");
    window.location = outHref;
    return 0;
}

var scrollboxes = document.getElementsByClassName("scrollbox");
for (var j = 0; j < scrollboxes.length; j++) {
    scrollboxes[j].addEventListener("scroll", function() {
        scrollMatch(this);
    }, false);
}

var els = new Array(document.getElementById('first'), document.getElementById('second'),
                    document.getElementById('third'), document.getElementById('fourth'),
                    document.getElementById('fifth'), document.getElementById('sixth'));
for (var i = 0; i < translCount; i++) {
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
    var elSelect = document.getElementById("translSelect");
    var addition = elSelect.options[elSelect.selectedIndex].value;
    document.getElementById("translModal").classList.add("hidden");
    // add selection to URL
    var curTrs = getTranslations();
    var trsArr = curTrs.split("+");
    trsArr.push(addition);
    var outTrs = trsArr.join("+");
    var outHref = replaceGetVar("tr", outTrs);
    var translationCookieName = "hexaTlCookie";
    document.cookie = translationCookieName + "=" + outTrs;
    window.location = outHref;
});
document.getElementById("cancelAdd").addEventListener("click", function() {
    document.getElementById("translModal").classList.add("hidden");
});
var deletes = document.getElementsByClassName("del");
var promotes = document.getElementsByClassName("promote");
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
    var href = window.location.href;
    if (href.indexOf("#") != -1) href = href.replace("#","");
    window.location = href + "&decr=1"
});
document.getElementById("nextPsg").addEventListener("click", function() {
    var href = window.location.href;
    if (href.indexOf("#") != -1) href = href.replace("#","");
    window.location = href + "&incr=1"
});