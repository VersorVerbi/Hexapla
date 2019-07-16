<DIV id="headerSpace">
    <H3 id="pageTitle"><?php echo $title; ?></H3>
    <DIV id="nextPsg" class="<?php echo !isset($bookname) ? "hidden" : ""; ?>"><span></span></DIV>
    <DIV id="prevPsg" class="<?php echo !isset($bookname) ? "hidden" : ""; ?>"><span></span></DIV>
    <DIV id="addtl" class="<?php echo (!isset($translations) || count($translations) >= 6 ? "hidden" : ""); ?>"><span></span></DIV>
    <div class="search">
        <form action="#" id="searchform">
            <input type="text" id="searchbox" name="searchbox" placeholder="Search..." />
        </form>
    </div>
</DIV>
<!-- directional buttons for next verse/chapter -->