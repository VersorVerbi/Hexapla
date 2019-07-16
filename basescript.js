var homeurl = "http://localhost/hexa/";
var scrollCookieName = "hexaScroll";
var translationCookieName = "hexaTlCookie";
var menuCookieName = "hexaMenu";
var groupCookieName = "hexaDocGroup";

var scrollValue = getCookieValue(scrollCookieName);
var scrollMenuItem = document.getElementById("scroll");
if (scrollMenuItem) scrollMenuItem.checked = parseInt(scrollValue) > 0;

var menuValue = getCookieValue(menuCookieName);
document.getElementById("menuup").checked = parseInt(menuValue) > 0;
if (parseInt(menuValue)) {
    document.getElementById("menuwrap").classList.add("active");
}

var groupValue = getCookieValue(groupCookieName);
var groupSelect = document.getElementById("docgroup");
if (groupSelect) {
    if (groupValue == null) {
        groupSelect.selectedIndex = 0;
    } else {
        for (var i = 0; i < groupSelect.childElementCount; i++) {
            if (groupSelect.options[i].value == parseInt(groupValue)) {
                groupSelect.selectedIndex = i;
                break;
            }
        }
    }
}

function getCookieValue(nm) {
    var allCookies = document.cookie;
    var cookieLoc = allCookies.indexOf(nm);
    var cookieVal = allCookies.substr(cookieLoc, allCookies.indexOf(";", cookieLoc) - cookieLoc);
    if (cookieVal.indexOf(nm) == -1) {
        return null;
    } else {
        cookieVal = cookieVal.substr(cookieVal.indexOf("=")+1);
        return cookieVal;
    }
}

function replaceGetVar(getVarName, val, url) {
    if (typeof url === 'undefined') {
        url = window.location.href;
    }
    var getIndex = url.indexOf(getVarName + "=");
    var valIndex = getIndex + getVarName.length + 1;
    var end = url.indexOf("&",valIndex);
    if (end == -1) end = url.length;
    return url.substr(0, valIndex) + val + (url.substr(end));
}

var menuButton = document.getElementById("menu-button");
if (menuButton) {
    menuButton.addEventListener("click", function() {
        var par = document.getElementById("menuwrap");
        if (par.classList.contains("active")) {
            par.classList.remove("active");
        } else {
            par.classList.add("active"); 
        }
    });
}

var searchForm = document.getElementById("searchform");
if (searchForm) {
    searchForm.addEventListener("submit", function(event) {
        event.preventDefault();
        var searchval = document.getElementById("searchbox").value;
        searchval = searchval.replace(new RegExp('\\s','g'), "+");
        var tlCookieVal = getCookieValue(translationCookieName);
        var translations = "";
        if (tlCookieVal == null) {
            translations = "1";
            document.cookie = translationCookieName + "=1";
        } else {
            translations = tlCookieVal;
        }
        if (window.location.href.indexOf("search") != -1) {
            var outHref = replaceGetVar("search", searchval);
            outHref = replaceGetVar("tr", translations, outHref);
            window.location = outHref;
        } else {
            var baseHref = homeurl + "search.php?";
            window.location = baseHref + "search=" + searchval + "&tr=" + translations;
        }
    });
}

if (scrollMenuItem) {
    scrollMenuItem.addEventListener("click", function() {
        // check the scroll cookie
        var scrollValue = getCookieValue(scrollCookieName);
        if (parseInt(scrollValue)) {
            document.cookie = scrollCookieName + "=0";
            scrollValue = 0;
        } else {
            var scrollboxes = document.getElementsByClassName("scrollbox");
            if (scrollboxes != undefined) {
                // reset all scrolls
                for (var i = 0; i < scrollboxes.length; i++) {
                    scrollboxes[i].scrollTop = 0;
                }
            }
            // save cookie that enables synced scrolling
            document.cookie = scrollCookieName + "=1";
            scrollValue = 1;
        }
    });
}

var menuUp = document.getElementById("menuup");
if (menuUp) {
    menuUp.addEventListener("click", function() {
        var menuCookieValue = getCookieValue(menuCookieName);
        if (parseInt(menuCookieValue)) {
            document.cookie = menuCookieName + "=0";
        } else {
            document.getElementById("menuwrap").classList.add("active");
            document.cookie = menuCookieName + "=1";
        }
    });
}

if (groupSelect) {
    groupSelect.addEventListener("change", function() {
        var newValue = groupSelect.options[groupSelect.selectedIndex].value;
        document.cookie = groupCookieName + "=" + newValue;
    });
}