function addDefiners() {
    let translations = document.getElementsByClassName('textArea');
    for (let t = 0; t < translations.length; t++) {
        let words = translations[t].getElementsByTagName('span');
        for (let w = 0; w < words.length; w++) {
            words[w].addEventListener('click', define)
        }
    }
}

async function define() {
    let classes = this.classList;
    let sourceWords = [];
    let sourceLang;
    let translationId = this.closest('.version').id;
    for (let c = 0; c < classes.length; c++) {
        if (classes[c] === 'hovered') {
            continue;
        }
        if (classes[c][0].toUpperCase() === 'H') {
            sourceLang = 'Hebrew'; // TODO: make this correct
        } else if (classes[c][0].toUpperCase() === 'G') {
            sourceLang = 'Greek'; // TODO: make this correct
        }
        sourceWords.push(classes[c]);
    }

    let form = new FormData();
    form.append('sourceWords', JSON.stringify(sourceWords));
    form.append('tid', translationId);
    form.append('text', this.innerText);

    let definitions = await fetch('define.php', {
        method: 'POST',
        mode: 'same-origin',
        redirect: 'error',
        body: form
    });
    definitions.text().then(data => {
        // TODO: put definitions in sidebar and show sidebar
    });
}