function showError(inputElement) {
    getSibling(inputElement, '.errorLabel').classList.remove('hidden');
    getLocalSubmit(inputElement).disabled = true;
}

function hideError(inputElement) {
    getSibling(inputElement, '.errorLabel').classList.add('hidden');
    if (!errorsExist()) {
        getLocalSubmit(inputElement).disabled = false;
    }
}

function errorsExist() {
    return document.querySelectorAll('.errorLabel:not(.hidden)').length > 0;
}

function cancelRegistration() {
    cancelForm(document.getElementById('registrationPopup'));
}

function cancelLogin() {
    cancelForm(document.getElementById('loginPopup'));
}

function cancelForm(popup) {
    popup.classList.add('hidden');
    if (errorsExist()) {
        document.querySelectorAll('.errorLabel:not(.hidden)').forEach(hideError);
    }
    clearForm(popup.getElementsByTagName('form')[0]);
}

function getLocalSubmit(el) {
    let formParent = el.closest('form');
    return formParent.querySelector('input[type=submit]');
}

function emailCheck(emailInput, mustExist = false) {
    if (!emailInput.value.includes('@')) {
        showError(emailInput);
    } else if (mustExist && emailInput.value.length <= 0) {
        showError(emailInput);
    } else {
        hideError(emailInput);
    }
}

function isFormHidden(input) {
    return input.closest('.userPopup').classList.contains('hidden');
}

document.addEventListener("DOMContentLoaded", function() {

    let get = (id) => document.getElementById(id);

    //#region Registration
    get('email').addEventListener('change', function() {
        if (isFormHidden(this)) return;
        emailCheck(this);
        if (!get('alreadyRegistered').value.includes(this.value)) {
            hideError(get('regSubmit'));
        }
    });
    get('password').addEventListener('change', function() {
        if (isFormHidden(this)) return;
        if (this.value.length < 8) {
            showError(this);
        } else {
            hideError(this);
        }
        let pw2 = get('repw');
        if (pw2.value.length > 0) {
            if (this.value !== pw2.value) {
                showError(pw2);
            } else {
                hideError(pw2);
            }
        }
    })
    get('repw').addEventListener('change', function() {
        if (isFormHidden(this)) return;
        let pw1 = get('password');
        if (this.value !== pw1.value) {
            showError(this);
        } else {
            hideError(this);
        }
    });
    get('register').addEventListener('submit', function(ev) {
        ev.preventDefault();
        // first double-check everything else
        emailCheck(get('email'), true);

        let pw1 = get('password');
        if (pw1.value.length < 8) {
            showError(pw1);
        }

        let pw2 = get('repw');
        if (pw2.value !== pw1.value) {
            showError(pw2);
        }

        if (errorsExist()) {
            return;
        }

        let reg = fetch(INTERNAL_API_PATH + 'register.php', {
            method: 'POST',
            mode: 'same-origin',
            redirect: 'error',
            body: new FormData(this)
        });
        reg.then(raw => raw.text()).then(data => tryParseJson(data)).then(registrationResult => {
            if (registrationResult) {
                get('registrationPopup').classList.add('hidden');
                get('regLoginPopup').classList.remove('hidden');
            } else {
                get('alreadyRegistered').value += '^' + email.value;
                showError(get('regSubmit'));
            }
        });
    });
    get('regLogin').addEventListener('submit', function(ev) {
        ev.preventDefault();

        let frm = new FormData(this);
        frm.append('email', get('email').value);
        frm.append('password', get('password').value);

        fetch(INTERNAL_API_PATH + 'login.php', {
            method: 'POST',
            mode: 'same-origin',
            redirect: 'error',
            body: frm
        });

        clearForm(this);
        clearForm(get('register'));
        refreshCurrentPage();
    });
    //#endregion

    //#region Login
    get('loginEmail').addEventListener('change', function () {
        if (isFormHidden(this)) return;
        emailCheck(this);
    });
    get('loginPassword').addEventListener('change', function () {
        if (isFormHidden(this)) return;
        if (this.value.length > 0) {
            hideError(this);
        }
    });
    get('login').addEventListener('submit', function (ev) {
        ev.preventDefault();

        emailCheck(get('loginEmail'), true);

        let pw = get('loginPassword');
        if (pw.value.length <= 0) {
            showError(pw);
        }

        if (errorsExist()) return;

        let login = fetch(INTERNAL_API_PATH + 'login.php', {
            method: 'POST',
            mode: 'same-origin',
            redirect: 'error',
            body: new FormData(this)
        });
        login.then(raw => raw.text()).then(data => tryParseJson(data)).then(loginResult => {
            if (loginResult) {
                get('loginPopup').classList.add('hidden');
                clearForm(this);
                refreshCurrentPage();
            } else {
                showError(get('loginSubmit'));
            }
        });
    });
    //#endregion
});