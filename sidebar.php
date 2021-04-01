<?php
require_once "dbconnect.php";
/** @var UserSettings $currentUser */
?>

<div id="menuwrap">
    <div class="menu-tab" id="menu-btn-wrap" data-mnu="menu" title="Options">
        <span class="tab-button" id="menu-button"></span>
    </div>
    <div class="menu-tab" id="dict-btn-wrap" data-mnu="dictionary" title="Dictionary">
        <span class="tab-button" id="dict-button"></span>
    </div>
    <div class="menu-tab" id="crossref-btn-wrap" data-mnu="crossref" title="Cross-References">
        <span class="tab-button" id="ref-button"></span>
    </div>
    <div class="sidebar" id="menutop">
        <input class="toggleRecorder" id="pin-sidebar" type="checkbox" name="pin-sidebar" /><label for="pin-sidebar" id="pin-sidebar-label" class="toggle"><span class="icofont-tack-pin"></span></label>
        <input class="toggleRecorder" id="scroll-together" type="checkbox" name="scroll-together" /><label for="scroll-together" id="scroll-together-label" class="toggle"><span></span></label>
        <input class="toggleRecorder" id="case-sensitive-diff" type="checkbox" name="case-sensitive-diff" <?php echo ($currentUser->diffsCaseSens() ? 'checked' : ''); ?> /><label for="case-sensitive-diff" id="case-sensitive-diff-label" class="toggle <?php echo ($currentUser->diffsCaseSens() ? 'clicked' : ''); ?>">Aa</label>
        <div class="topButtons"><div id="do-login" class="sidebarButton" title="Log In"><span class="icofont-login"></span></div></div>
    </div>
    <div class="sidebar" id="menu">
        <ul>
            <li><a href="javascript:void(0)">Advanced Search</a></li>
            <hr />
            <h4>Options</h4>
            <li>
                <span>Show differences by:</span><br />
                <input type="radio" name="diff-by-word" id="word-diff" value="word-diff" <?php echo ($currentUser->diffsByWord() ? 'checked' : ''); ?> /><label for="word-diff">Word</label>
                <input type="radio" name="diff-by-word" id="char-diff" value="char-diff" <?php echo ($currentUser->diffsByWord() ? '' : 'checked'); ?>/><label for="char-diff">Character</label>
            </li>
            <li>
                <label for="theme-selection">Theme: </label>
                <select id="theme-selection" name="theme-selection">
                    <?php
                    foreach($GLOBALS['themes'] as $thm) {
                        // FIXME: fix when the cookie is null?
                        echo "<option value=\"$thm\" " . ($thm === $GLOBALS['themes'][getCookie(HexaplaCookies::THEME)] ? 'selected' : '') . ">" . toTitleCase($thm) . "</option>";
                    }
                    ?>
                </select>
            </li>
            <li>
                <span>Shade: </span>
                <input type="radio" name="shade-selection" id="dark-shade" value="dark" <?php echo ($GLOBALS['shades'][getCookie(HexaplaCookies::SHADE)] === 'dark' ? 'checked' : ''); ?> /><label for="dark-shade">Dark</label>
                <input type="radio" name="shade-selection" id="light-shade" value="light" <?php echo ($GLOBALS['shades'][getCookie(HexaplaCookies::SHADE)] === 'light' ? 'checked' : ''); ?> /><label for="light-shade">Light</label>
            </li>
            <!-- TODO: add option to keep diff turned on? -->
            <!-- TODO: add option for which document group to loop through -->
            <!-- TODO: add cookies / user settings to handle these -->
            <!-- TODO: add note export button (w/ popup) here -->
            <hr />
            <h4>Navigation</h4>
            <li><a href="">About Us</a></li>
            <li><a href="">How You Can Help</a></li>
            <li><a href="">Copyright Information</a></li>
            <li><a href="">Privacy Policy</a></li>
            <li><a href="">Terms of Service</a></li>
        </ul>
    </div>
    <div class="sidebar" id="dictionary">
        <div id="curLangDefn" class="hidden">
            <h3 id="curLangTitle"></h3>
        </div>
        <hr class="hidden" />
        <div id="sourceLangDefn" class="hidden">
            <h3 id="sourceLangTitle">Source Language</h3>
        </div>
    </div>
    <div class="sidebar" id="crossref">

    </div>
    <div class="sidebar" id="sidecover">

    </div>
    <div class="sidebar" id="menubottom">
        <h4><a href="index.php">ModernHexapla.com</a></h4>
    </div>
</div>