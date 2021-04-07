<?php

namespace Hexapla;
require_once "sql-functions.php";
/**
 * @var UserSettings $currentUser
 * @var resource $db
 */

if (isset($_GET['translations'])) {
    $ts = explode('^', $_GET['translations']);
    $showNotes = false;
    if (in_array('notes', $ts)) {
        $ts = array_diff($ts, ['notes']);
        $showNotes = true;
    }
    $versionData = getVersionData($db, $ts);
    if ($showNotes) {
        $ts[] = 'notes';
    }

    foreach($ts as $t) {
        if (is_numeric($t)) {
        ?>
<div id="t<?php echo $t; ?>" class="version of<?php echo count($ts); ?>"
     data-can-diff="<?php echo $versionData[$t]['perm'] & HexaplaPermissions::DIFF; ?>"
     data-lang="<?php echo $versionData[$t]['lang']; ?>">
    <h4><?php echo $versionData[$t]['term'];?></h4>
    <button id="diff<?php echo $t; ?>" class="icoButton diffButton" title="Show differences" onclick="addDiff(this)"><span class="icofont-opposite"></span><span class="tinyMod icofont-plus"></span></button>
    <div class="textArea"></div>
    <div class="resultNotice hidden"></div>
</div>
        <?php
        } elseif ($t === 'notes') {
            ?>
<div id="my-notes-container" class="version of<?php echo count($ts); ?>"
    data-can-diff="false">
    <form id="my-notes-form" style="height: 100%;">
        <textarea id="my-notes" name="my-notes"></textarea>
    </form>
    <div class="resultNotice hidden"></div>
</div>
            <?php
        }
    }
} else {
    ?><div></div><?php
}