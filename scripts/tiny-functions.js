function killTheTinyMouse() {
    let tmce = tinymce.get('my-notes');
    if (tmce) {
        tmce.save();
        tmce.remove();
        tmce.destroy();
    }
}

function init_tinymce(selector, skin) {
    tinymce.init({ selector: selector,
        menu: {
            myNotes: {title: 'My Notes', items: 'newdocument' },
        },
        menubar: 'myNotes | edit format',
        height: '100%',
        resize: false,
        skin_url: '/Hexapla/styles/skins/ui/' + skin, // RELATIVE-URL
        skin: skin,
        content_css: '/Hexapla/styles/skins/content/' + skin + '/content.css', // RELATIVE-URL
        setup: editor => {
            editor.on('input', () => {
                editor.save();
            });
        },
    });
}

function swapTinySkins(theme, shade) {
    let themeInput = document.getElementById('currentTinyMCETheme');
    themeInput.value = toTitleCase(theme.replace('-', '') + ' ' + shade);
    killTheTinyMouse();
    init_tinymce('#my-notes', themeInput.value);
}