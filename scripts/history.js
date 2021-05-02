function handleMovement(event) {
    // event.state = JSON object of browser state data we passed in earlier
    let s = event.state;
    if (!s) {
        // go to home
        location.reload();
    } else if (s.srch) {
        // do search
        let curSearchInput = document.getElementById('currentSearch');
        curSearchInput.value = s.srch.term + '|' + s.srch.tls;
        document.getElementById('searchbox').value = '';
        doSearch(null, true);
    }
}

window.addEventListener('popstate', handleMovement);

function addSearch(searchTerm, searchTranslations) {
    history.pushState({ srch: {term: searchTerm, tls: searchTranslations } }, '', '/Hexapla/'); // RELATIVE-URL
}