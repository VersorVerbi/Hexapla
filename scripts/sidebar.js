document.addEventListener("DOMContentLoaded", function() {
    let tabBtns = document.getElementsByClassName('menu-tab');
    for (let tb = 0; tb < tabBtns.length; tb++) {
        tabBtns[tb].addEventListener('click', function () {
            let currentTab = document.querySelector('.activated.menu-tab');
            if (currentTab === this) {
                hideMenu();
                return;
            }
            let currentlyActive = document.getElementsByClassName('activated');
            for (let c = currentlyActive.length - 1; c >= 0; c--) {
                currentlyActive[c].classList.remove('activated');
            }
            let target = this.dataset.mnu;
            let targetSidebar = document.getElementById(target);
            document.getElementById('menuwrap').classList.add('active');
            targetSidebar.classList.add('activated');
            this.classList.add('activated');
        });
    }

    document.getElementById('wrap').addEventListener('click', hideMenu);

    document.getElementById("menuup").addEventListener('change', function() {
        let mainWrap = document.getElementById('wrap');
        if (this.checked) {
            // add class to body
            mainWrap.classList.add('permaMenu');
            // remove listener
            mainWrap.removeEventListener('click', hideMenu);
        } else {
            // remove class
            mainWrap.classList.remove('permaMenu');
            // readd listener
            document.getElementById('wrap').addEventListener('click', hideMenu);
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