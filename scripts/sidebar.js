document.addEventListener("DOMContentLoaded", function() {
    let tabBtns = document.getElementsByClassName('menu-tab');
    for (let tb = 0; tb < tabBtns.length; tb++) {
        tabBtns[tb].addEventListener('click', function () {
            let currentTab = document.querySelector('.activated.menu-tab');
            if (currentTab === this) {
                hideMenu();
                return;
            }
            showMenu(this);
        });
    }

    document.getElementById('wrap').addEventListener('click', hideMenu);

    let toggles = document.getElementsByClassName('toggleRecorder');
    Array.from(toggles).forEach(toggleBox => {
        toggleBox.addEventListener('change', toggleButton.bind(document.getElementById(toggleBox.id + '-label')));
    });


    document.getElementById('pin-sidebar-label').title = "Keep this menu open";
    document.getElementById('pin-sidebar').addEventListener('change', function() {
        let mainWrap = document.getElementById('wrap');
        let label = document.getElementById('pin-sidebar-label');
        if (this.checked) {
            // add class to body
            mainWrap.classList.add('permaMenu');
            // remove listener
            mainWrap.removeEventListener('click', hideMenu);
            label.title = "Allow this menu to close"
        } else {
            // remove class
            mainWrap.classList.remove('permaMenu');
            // readd listener
            document.getElementById('wrap').addEventListener('click', hideMenu);
            label.title = "Keep this menu open";
        }
    });

    document.getElementById('scroll-together-label').title = "Scroll all versions together";
    document.getElementById('scroll-together').addEventListener('change', function() {
        let label = document.getElementById('scroll-together-label');
        if (this.checked) {
            // TODO: do the thing
            label.title = "Scroll each version separately";
        } else {
            // TODO: undo the thing
            label.title = "Scroll all versions together";
        }
    });

    document.getElementById('case-sensitive-diff-label').title = "Treat case as a difference between versions";
    document.getElementById('case-sensitive-diff').addEventListener('change', function() {
        let label = document.getElementById('case-sensitive-diff-label');
        if (this.checked) {
            // TODO: do the thing
            // TODO: add user setting API that sets cookies and updates database for these settings?
            label.title = "Ignore case when showing differences between versions";
        } else {
            // TODO: undo the thing
            label.title = "Treat case as a difference between versions";
        }
    });

    let shadePickers = document.querySelectorAll('input[name="shade-selection"]');
    for (let s = 0; s < shadePickers.length; s++) {
        shadePickers[s].addEventListener('change', function() {
            if (this.checked) {
                document.body.classList.add('themeChange');
                document.body.classList.remove(...shadeList);
                document.body.classList.add(this.value);
                fetch('/Hexapla/cookies.php?name=hexaplaTheme').then(data => data.text().then(doShadeSwap.bind(null, this.value))); // RELATIVE-URL
                fetch('/Hexapla/cookies.php?set&name=hexaplaShade&value=' + shadeList.indexOf(this.value)).then( // RELATIVE-URL
                    () => setTimeout(() => document.body.classList.remove('themeChange'), 100));
            }
        });
    }

    // FIXME: figure out why some elements (notably transitioned elements like sidebar) don't transition colors

    document.getElementById('theme-selection').addEventListener('change', function() {
        document.body.classList.add('themeChange');
        document.getElementById('themeCss').href = "styles/" + this.value + ".css";
        fetch('/Hexapla/cookies.php?name=hexaplaShade').then(data => data.text().then(doThemeSwap.bind(null, this.value))); // RELATIVE-URL
        fetch('/Hexapla/cookies.php?set&name=hexaplaTheme&value=' + themeList.indexOf(this.value)).then( // RELATIVE-URL
            () => {
                if (this.value === 'liturgical') {
                    fetchLiturgicalColor()
                        .then(className => document.body.classList.add(className))
                        .catch(() => document.body.classList.add('green'));
                }
            }).finally(() => setTimeout(() => document.body.classList.remove('themeChange'), 100));
    });
});

function hideMenu() {
    let menu = document.getElementsByClassName('active')[0];
    if (menu) {
        menu.classList.remove('active');
    }
    // delay this so it doesn't disappear in front of the user
    setTimeout(function () {
        let menus = document.getElementsByClassName('activated');
        for (let m = 0; m < menus.length; m++) {
            menus[m].classList.remove('activated');
        }
    }, 1000);
}

function showSidebar(sidebarName) {
    showMenu(document.querySelector('[data-mnu=' + sidebarName + ']'));
}

function showMenu(tab) {
    let currentlyActive = document.getElementsByClassName('activated');
    for (let c = currentlyActive.length - 1; c >= 0; c--) {
        currentlyActive[c].classList.remove('activated');
    }
    let target = tab.dataset.mnu;
    let targetSidebar = document.getElementById(target);
    document.getElementById('menuwrap').classList.add('active');
    targetSidebar.classList.add('activated');
    tab.classList.add('activated');
}

function doShadeSwap(shadeName, themeId) {
    swapTinySkins(themeList[themeId], shadeName);
}

function doThemeSwap(themeName, shadeId) {
    swapTinySkins(themeName, shadeList[shadeId]);
}