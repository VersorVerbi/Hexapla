<?php

namespace Hexapla;
/** @var string $title */
?>


<div id="header">
    <?php if ($title === 'Modern Hexapla') { ?>
        <h3 id="title"><?php echo $title; ?></h3>
    <?php } else {
        // TODO: make a link back to the home/about page, then the regular title?
    } ?>
    <?php include "nav.php"; ?>
    <?php include "search-box.html"; ?>
</div>