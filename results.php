<?php
if (isset($_GET['translations'])) {
    $ts = explode('^', $_GET['translations']);
    foreach($ts as $t) {
        ?>
<div id="t<?php echo $t; ?>"></div>
        <?php
    }
}