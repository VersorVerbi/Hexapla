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
            label.title = "Ignore case when showing differences between versions";
        } else {
            // TODO: undo the thing
            label.title = "Treat case as a difference between versions";
        }
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