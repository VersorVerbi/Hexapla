let homeurl = "http://localhost/hexa/";
let scrollCookieName = "hexaScroll";
let translationCookieName = "hexaTlCookie";
let menuCookieName = "hexaMenu";
let groupCookieName = "hexaDocGroup";

let scrollValue = getCookieValue(scrollCookieName);
let scrollMenuItem = document.getElementById("scroll");
if (scrollMenuItem) scrollMenuItem.checked = parseInt(scrollValue) > 0;

let menuValue = getCookieValue(menuCookieName);
document.getElementById("menuup").checked = parseInt(menuValue) > 0;
if (parseInt(menuValue)) {
    document.getElementById("menuwrap").classList.add("active");
}

let groupValue = getCookieValue(groupCookieName);
let groupSelect = document.getElementById("docgroup");
if (groupSelect) {
    if (groupValue == null) {
        groupSelect.selectedIndex = 0;
    } else {
        for (let i = 0; i < groupSelect.childElementCount; i++) {
            if (groupSelect.options[i].value == parseInt(groupValue)) {
                groupSelect.selectedIndex = i;
                break;
            }
        }
    }
}

function getCookieValue(nm) {
    let allCookies = document.cookie;
    let cookieLoc = allCookies.indexOf(nm);
    let cookieVal = allCookies.substr(cookieLoc, allCookies.indexOf(";", cookieLoc) - cookieLoc);
    if (cookieVal.indexOf(nm) === -1) {
        return null;
    } else {
        cookieVal = cookieVal.substr(cookieVal.indexOf("=")+1);
        return cookieVal;
    }
}

function replaceGetVar(getletName, val, url) {
    if (typeof url === 'undefined') {
        url = window.location.href;
    }
    let getIndex = url.indexOf(getVarName + "=");
    let valIndex = getIndex + getVarName.length + 1;
    let end = url.indexOf("&",valIndex);
    if (end === -1) end = url.length;
    return url.substr(0, valIndex) + val + (url.substr(end));
}

let menuButtons = document.getElementsByClassName("menu-tab");
if (menuButtons) {
    for (let i = 0; i < menuButtons.length; i++) {
        menuButtons[i].addEventListener("click", function () {
            let par = document.getElementById("menuwrap");
            let tabButton = this;
            if (par.classList.contains("active")) {
                if (tabButton.classList.contains("activator")) {
                    par.classList.remove("active");
                    setTimeout(function() {
                        document.getElementById(tabButton.dataset.mnu).classList.remove("activated");
                        tabButton.classList.remove("activator");
                    }, 1000);
                } else {
                    let currentTab = document.getElementsByClassName("activator")[0];
                    document.getElementById(currentTab.dataset.mnu).classList.remove("activated");
                    currentTab.classList.remove("activator");
                    tabButton.classList.add("activator");
                    document.getElementById(tabButton.dataset.mnu).classList.add("activated");
                }
            } else {
                par.classList.add("active");
                tabButton.classList.add("activator");
                document.getElementById(tabButton.dataset.mnu).classList.add("activated");
            }
        });
    }
}

let searchForm = document.getElementById("searchform");
if (searchForm) {
    searchForm.addEventListener("submit", function(event) {
        event.preventDefault();
        let searchval = document.getElementById("searchbox").value;
        searchval = searchval.replace(new RegExp('\\s','g'), "+");
        let tlCookieVal = getCookieValue(translationCookieName);
        let translations = "";
        if (tlCookieVal == null) {
            translations = "1";
            document.cookie = translationCookieName + "=1";
        } else {
            translations = tlCookieVal;
        }
        if (window.location.href.indexOf("search") !== -1) {
            let outHref = replaceGetVar("search", searchval);
            outHref = replaceGetVar("tr", translations, outHref);
            window.location = outHref;
        } else {
            let baseHref = homeurl + "search.php?";
            window.location = baseHref + "search=" + searchval + "&tr=" + translations;
        }
    });
}

if (scrollMenuItem) {
    scrollMenuItem.addEventListener("click", function() {
        // check the scroll cookie
        let scrollValue = getCookieValue(scrollCookieName);
        if (parseInt(scrollValue)) {
            document.cookie = scrollCookieName + "=0";
            scrollValue = 0;
        } else {
            let scrollboxes = document.getElementsByClassName("scrollbox");
            if (scrollboxes !== undefined) {
                // reset all scrolls
                for (let i = 0; i < scrollboxes.length; i++) {
                    scrollboxes[i].scrollTop = 0;
                }
            }
            // save cookie that enables synced scrolling
            document.cookie = scrollCookieName + "=1";
            scrollValue = 1;
        }
    });
}

let menuUp = document.getElementById("menuup");
if (menuUp) {
    menuUp.addEventListener("click", function() {
        let menuCookieValue = getCookieValue(menuCookieName);
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
        let newValue = groupSelect.options[groupSelect.selectedIndex].value;
        document.cookie = groupCookieName + "=" + newValue;
    });
}