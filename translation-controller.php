<?php
require_once "sql-functions.php";
$versionsList = getVersions($db);
if (isset($_GET['t'])) {
    $tList = $_GET['t'];
} elseif ($currentUser->useSavedTl()) {
    $tList = $currentUser->savedTls();
} else {
    $tList = $_COOKIE['hexaTlCookie'];
}
?>
<div id="translationController" class="popup hidden">
    <div id="tlConHeader">
        <h3>Translation Grid</h3>
        <button id="closeTlCon" class="miniButton" title="Close" onclick="closeTlConfig()">
            <span class="icofont-close"></span>
        </button>
    </div>
    <div id="translGrid">
        <?php
        for ($t = 1; $t < 7; $t++) {
            echo "<div id='tl$t' class='tlBox";
            if (!is_null($version = getVersionFromList(piece($tList, '^', $t), $versionsList))) {
                echo " occupied'>";
                echo makeDraggableVersion($version);
            } else {
                echo "'>";
            }
            echo "</div>";
        }
        ?>
    </div>
    <div id="translList">
        <h4>Available Translations</h4>
        <?php
            $lastLang = '';
            foreach ($versionsList as $version) {
                if ($version['lang'] !== $lastLang) {
                    echo "<div class='langGroup'>" . $version['lang'] . "</div>";
                    $lastLang = $version['lang'];
                }
                if (!inStringList($version['id'], $tList, '^')) {
                    echo makeDraggableVersion($version);
                }
            }
        ?>
    </div>
</div>

<script type="text/javascript">
    let draggables = document.querySelectorAll('[draggable="true"]');
    for (let d = 0; d < draggables.length; d++) {
        draggables[d].addEventListener('dragstart', draggableStart);
    }
    dropZoneSetup(document.getElementById('translGrid'), potentialTl, nomoreTl, addTl, ev => { ev.preventDefault(); });
    dropZoneSetup(document.getElementById('translList'), potentialRemoveTl, keepTl, removeTl, ev => { ev.preventDefault(); });

</script>