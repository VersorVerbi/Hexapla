<?php
require_once "sql-functions.php";

if (isset($_GET['translations'])) {
    $ts = explode('^', $_GET['translations']);
    $versionNames = getVersionNames($db, $ts);
    foreach($ts as $t) {
        ?>
<div id="t<?php echo $t; ?>" class="of<?php echo count($ts); ?>">
    <h4><?php echo $versionNames[$t];?></h4>
    <div class="textArea"></div>
    <div class="resultNotice hidden"></div>
</div>
        <?php
    }
} else {
    ?><div></div><?php
}