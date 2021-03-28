<?php
require_once "dbconnect.php";
require_once "sql-functions.php";
/**
 * @var UserSettings $currentUser
 * @var resource $db
 */


$versionsList = getVersions($db);
if (isset($_GET['t'])) {
    $tList = $_GET['t'];
} elseif ($currentUser->useSavedTl()) {
    $tList = $currentUser->savedTls();
} else {
    $tList = getCookie(HexaplaCookies::LAST_TRANSLATIONS);
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
    <div id="translOptions">
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
        <div id="showNotesContainer" class="<?php if (!$currentUser->canWriteNotes()) echo "hidden"; ?>">
            <input type="checkbox" class="toggleRecorder" id="show-notes" name="show-notes" /><label for="show-notes" id="show-notes-label" class="fullToggle">Include My Notes</label>
        </div>
    </div>
</div>

<script type="text/javascript">
    let draggables = document.querySelectorAll('[draggable="true"]');
    for (let d = 0; d < draggables.length; d++) {
        draggables[d].addEventListener('dragstart', draggableStart);
    }
    dropZoneSetup(document.getElementById('translGrid'), potentialTl, nomoreTl, addTl, ev => { ev.preventDefault(); });
    dropZoneSetup(document.getElementById('translList'), potentialRemoveTl, keepTl, removeTl, ev => { ev.preventDefault(); });


    let notesLabel = document.getElementById('show-notes-label');
    if (document.getElementById('show-notes').checked) {
        notesLabel.title = "Stop showing my notes";
        notesLabel.classList.add('clicked');
    } else {
        notesLabel.title = "Use one of the version spaces to enter my own notes on each passage";
    }
    document.getElementById('show-notes').addEventListener('change', function() {
        let label = document.getElementById('show-notes-label');
        if (this.checked) {
            let targetSpot = document.getElementById('tl6');
            if (targetSpot.classList.contains('occupied')) {
                let blocker = targetSpot.getElementsByClassName('transl')[0];
                let langTarget = blocker.dataset.lang;
                returnVersion(blocker, langTarget);
                blocker.removeEventListener('dragstart', draggableStart);
                blocker.addEventListener('dragstart', draggableStart);
            } else {
                targetSpot.classList.add('occupied');
            }
            let notesBox = document.createElement('div');
            notesBox.classList.add('transl');
            notesBox.draggable = true;
            notesBox.id = 'notes';
            notesBox.innerText = 'My Notes';
            notesBox.addEventListener('dragstart', draggableStart);
            targetSpot.appendChild(notesBox);
            label.title = "Stop showing my notes";
        } else {
            let notesBox = document.getElementById('notes');
            if (notesBox) {
                notesBox.parentElement.classList.remove('occupied');
                notesBox.parentElement.removeChild(notesBox);
                notesBox.removeEventListener('dragstart', draggableStart);
            }
            label.title = "Use one of the version spaces to enter my own notes on each passage";
        }
    });
</script>

<?php
// FIXME: adding >6 translations just removes them irretrievably from the translation list
// TODO: removing a middle translation should slide everything else up (except notes)