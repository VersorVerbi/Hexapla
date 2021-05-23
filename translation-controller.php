<?php
namespace Hexapla;

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
    <div id="tlConHeader" class="headerRow">
        <h3>Translation Grid</h3>
        <button type="button" id="closeTlCon" class="miniButton closeButton" title="Close" onclick="closeTlConfig()">
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
        <div id="showNotesContainer" class="<?php echo (!$currentUser->canWriteNotes() ? 'hidden': ''); ?>">
            <input
                    type="checkbox"
                    class="toggleRecorder"
                    id="show-notes"
                    name="show-notes"
                <?php echo (inStringList('notes', $tList, '^') ? 'checked' : ''); ?>/>
            <label
                    for="show-notes"
                    id="show-notes-label"
                    class="fullToggle">
                Include My Notes
            </label>
        </div>
    </div>
</div>

<?php
// FIXME: adding >6 translations just removes them irretrievably from the translation list
// TODO: removing a middle translation should slide everything else up (except notes)