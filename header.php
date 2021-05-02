<?php

namespace Hexapla;
/** @var string $title */
$extendedTitle = (strlen($title) > 0);
?>


<div id="header">
    <h3>
        <a href="?page=home"<?php echo ($extendedTitle ? '' : ' class="onHome"'); ?>>Modern Hexapla</a>
        <span id="title"<?php echo ($extendedTitle ? '' : ' class="hidden"'); ?>><?php echo ($extendedTitle ? $title : ''); ?></span>
    </h3>
    <?php include "nav.php"; ?>
    <?php include "search-box.html"; ?>
</div>