<?php
$versionsResource = getVersions($db);
?>
<pre>
<?php
while (($row = pg_fetch_assoc($versionsResource)) !== false) {
    $row['terms'] = terms_array($row['terms']);
    print_r($row); echo "<br />";
}
// TODO: check $_GET for specified translations
// TODO: check user settings for "always" or "last"
// TODO: check cookies for last use
?>
</pre>
<div id="translationController" class="popup hidden">
    <div id="tlConHeader">
        <h3>Translation Grid</h3>
        <button id="closeTlCon" class="miniButton" title="Close" onclick="closeTlConfig()">
            <span class="icofont-close"></span>
        </button>
    </div>
    <div id="translGrid">
        <div id="tl1" class="tlBox"></div>
        <div id="tl2" class="tlBox"></div>
        <div id="tl3" class="tlBox"></div>
        <div id="tl4" class="tlBox"></div>
        <div id="tl5" class="tlBox"></div>
        <div id="tl6" class="tlBox"></div>
    </div>
    <div id="translList">
        <h4>Available Translations</h4>
        <div id='kjv' class="transl" draggable="true">KJV</div>
    </div>
</div>

<script type="text/javascript">
    let draggables = document.querySelectorAll('[draggable="true"]');
    for (let d = 0; d < draggables.length; d++) {
        draggables[d].addEventListener('dragstart', ev => {
            ev.dataTransfer.setData('text/plain', ev.target.id);
            ev.dataTransfer.setData('text/html', ev.target.outerHTML);
            ev.dataTransfer.dropEffect = 'move';
            ev.target.classList.add('pickedUp');
        });
    }
    let dropZone = document.getElementById('translGrid');
    dropZone.addEventListener('dragenter', potentialTl);
    dropZone.addEventListener('dragexit', nomoreTl);
    dropZone.addEventListener('drop', addTl);
    dropZone.addEventListener('dragover', ev => { ev.preventDefault(); });
</script>