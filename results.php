<?php
require_once "sql-functions.php";

if (isset($_GET['translations'])) {
    $ts = explode('^', $_GET['translations']);
    $versionData = getVersionData($db, $ts);

    foreach($ts as $t) {
        ?>
<div id="t<?php echo $t; ?>" class="version of<?php echo count($ts); ?>"
     data-can-diff="<?php echo $versionData[$t]['perm'] & HexaplaPermissions::DIFF; ?>"
     data-lang="<?php echo $versionData[$t]['lang']; ?>">
    <h4><?php echo $versionData[$t]['term'];?></h4>
    <button id="diff<?php echo $t; ?>" class="icoButton" title="Show differences" onclick="addDiff(this)"><span class="icofont-opposite"></span><span class="tinyMod icofont-plus"></span></button>
    <div class="textArea"></div>
    <div class="resultNotice hidden"></div>
</div>
        <?php
    }
} else {
    ?><div></div><?php
}