<?php

namespace Hexapla;

?>

<div id="nextPassage" class="<?php echo !isset($bookname) ? "hidden" : ""; ?>"><span></span></div>
<div id="prevPassage" class="<?php echo !isset($bookname) ? "hidden" : ""; ?>"><span></span></div>
<div id="addTranslation" class="<?php echo (!isset($translations) || count($translations) >= 6 ? "hidden" : ""); ?>"><span></span></div>
