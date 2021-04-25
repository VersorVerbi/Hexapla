<?php

namespace Hexapla;
require_once "sql-functions.php";
require_once "cookie-functions.php";
require_once "dbconnect.php";
/**
 * @var UserSettings $currentUser
 * @var resource $db
 */

if ($currentUser->useSavedTl()) {
    $tls = $currentUser->savedTls();
} elseif ($currentUser->useLastTl()) {
    $tls = getCookie(HexaplaCookies::LAST_TRANSLATIONS);
}

setHexCookie(HexaplaCookies::DIFF_BY_WORD, $currentUser->diffsByWord());
setHexCookie(HexaplaCookies::DIFF_CASE_SENSITIVE, $currentUser->diffsCaseSens());

if (!isset($_GET['page'])) {
    $page = 'home';
} else {
    $page = $_GET['page'];
}
$pages = [
    'help' => 'how-to-help.html',
    'home' => 'home-page.html',
    'cookies' => 'cookie-policy.html',
    'privacy' => 'privacy-policy.html',
    'terms' => 'terms-of-service.html'
];
$titles = [
    'help' => 'How Can I Help?',
    'cookies' => 'Cookie Policy',
    'privacy' => 'Privacy Policy',
    'terms' => 'Terms & Conditions'
];
if ($page === 'search') {
    $search = $_GET['search'];
    if (isset($_GET['vers'])) {
        $tls = $_GET['vers'];
    } else {
        $tls = $_GET['versions'];
        $tls = explode('^', $tls);
        $tlRes = getData($db, HexaplaTables::SOURCE_VERSION_TERM, [HexaplaSourceVersionTerm::VERSION_ID], [HexaplaSourceVersionTerm::TERM => $tls, HexaplaSourceVersionTerm::FLAG => HexaplaTermFlag::ABBREVIATION]);
        free($tls);
        $tls = [];
        while (($tlRow = pg_fetch_assoc($tlRes)) !== false) {
            $tls[] = $tlRow[HexaplaSourceVersionTerm::VERSION_ID];
        }
        $tls = implode('^', $tls);
    }
    // TODO: add GET diff option
    // TODO: (for nojs) add same-word highlight, show definition, cross-refs
    $toLoad = "results.php";
} elseif (isset($pages[$page])) {
    $toLoad = $pages[$page];
} else {
    $toLoad = 'error404.html';
}
$title = $titles[$page] ?? 'Modern Hexapla';

$GLOBALS['themes'] = ['parchment', 'leather-bound', 'jonah', 'liturgical'];
$GLOBALS['shades'] = ['light', 'dark'];
$preferredTheme = getCookie(HexaplaCookies::THEME);
$preferredShade = getCookie(HexaplaCookies::SHADE);
if (is_numeric($preferredTheme) && ($preferredTheme >= 0 || $preferredTheme < count($GLOBALS['themes']))) {
    $theme = $GLOBALS['themes'][$preferredTheme];
} else {
    $themeNo = rand(0, count($GLOBALS['themes']) - 1);
    $theme = $GLOBALS['themes'][$themeNo];
    setHexCookie(HexaplaCookies::THEME, $themeNo);
}
if (is_numeric($preferredShade) && ($preferredShade >= 0 || $preferredShade < count($GLOBALS['shades']))) {
    $shade = $GLOBALS['shades'][$preferredShade];
} else {
    $shadeNo = rand(0, count($GLOBALS['shades']) - 1);
    $shade = $GLOBALS['shades'][$shadeNo];
    setHexCookie(HexaplaCookies::SHADE, $shadeNo);
}

$tinySkin = toTitleCase(preg_replace('/-/', '', $theme) . ' ' . $shade);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Modern Hexapla<?php echo ($title === 'Modern Hexapla' ? '' : ' - ' . $title); ?></title>
    <?php
    foreach($GLOBALS['themes'] as $thm) {
        echo "<link rel=\"preload\" as=\"style\" href=\"styles/$thm.min.css\" />";
    }
    ?>

    <link type="text/css" rel="stylesheet" href="styles/icofont.min.css" />
    <link id="themeCss" type="text/css" rel="stylesheet" href="styles/<?php echo $theme; ?>.min.css" />
    <script type="text/javascript" src="scripts/functions.js"></script>
    <script type="text/javascript">
        const themeList = <?php echo json_encode($GLOBALS['themes']); ?>;
        const shadeList = <?php echo json_encode($GLOBALS['shades']); ?>;
        let isLiturgicalTheme = <?php echo json_encode($theme === 'liturgical'); ?>;
        if (isLiturgicalTheme) {
            fetchLiturgicalColor()
                .then(className => document.body.classList.add(className))
                .catch(() => document.body.classList.add('green'))
                .finally(() => setTimeout(() => document.body.classList.remove('themeChange'), 100));
        }
    </script>
    <script type="text/javascript" src="scripts/tiny-functions.js"></script>
    <script type="text/javascript" src="scripts/define.js"></script>
    <script type="text/javascript" src="scripts/nav-and-search.js"></script>
    <script type="text/javascript" src="scripts/tl-config.js"></script>
    <script type="text/javascript" src="scripts/diff.js"></script>
    <script type="text/javascript" src="scripts/sidebar.js"></script>
    <script src="https://cdn.tiny.cloud/1/ptcuvqtdffo2fe0pjk54wmk1wa867jqad8psipzfqv6wvvtm/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
    <noscript>
        <link type="text/css" rel="stylesheet" href="styles/nojs.min.css" />
    </noscript>
</head>
<body class="<?php echo $shade; ?> <?php echo ($theme === 'liturgical' ? 'themeChange' : ''); ?>">
    <noscript>
        <div id="jsWarning">
            This website works better with JavaScript enabled. Instructions on how to enable JavaScript for your
            browser can be found <a href="https://www.enable-javascript.com/" target="_blank">here</a>. To make sure we
            don't do anything nefarious with JavaScript, check out our <a href="">privacy policy</a>,
            <a href="">cookie policy</a>, and <a href="">terms of service</a>.
        </div>
    </noscript>
    <div id="wrap">
        <?php include "header.php"; ?>
        <?php include "translation-controller.php"; ?>
        <?php include "register.html"; ?>
        <?php include "login.html"; ?>
        <div id="page">
            <?php /** @noinspection PhpIncludeInspection */
            include $toLoad; ?>
        </div>
    </div>
    <?php include "sidebar.php"; ?>
    <div id="loading" class="hidden"></div>
    <script type="text/javascript">
        <?php if (isset($search)) {
        ?>
        let curSearchInput = document.getElementById('currentSearch');
        if (curSearchInput.value === '') {
            document.getElementById('currentSearch').value = <?php echo json_encode($search . "|" . $tls); ?>;
            doSearch();
        }
        <?php
        } else {
        ?>
        document.getElementById('translations').value = <?php echo json_encode($tls); ?>;
        <?php
        }
        ?>

        init_tinymce('#my-notes', <?php echo json_encode($tinySkin); ?>);

        document.getElementById('currentTinyMCETheme').value = <?php echo json_encode($tinySkin); ?>;
    </script>
</body>
</html>


<?php
/* TODO list
    - add/remove notices with JS --> call JS functions when appropriate (when is it appropriate besides diffing?) [need to turn off notices when turning off diffing]
    - add diff --> add button and code to run diff code | add way to turn OFF diffing
    - add sidebar --> enable all functionality
    - add click->dictionary+toggle for words --> finish getting data from all sources
    - reload, add more translations
    - add page content + how can I help? page --> fix example icons not showing up on template switch
    - handle commentary text --> display it too
    - add user functionality
        -> user notes
            --> delete notes subsumed into new note
            --> if save notes to 10 verses, then notes_on_loc lists all 10 for that verse ID and we just update the note once
            --> export functionality
        -> qualifications - contributions?
    - permalinks --> translation permalinks->tl-config screen | copy permalink to clipboard
    - accessibility
    - noJS versions of everything
    - some sort of test suite?
    - sometimes opener punctuation has a space after it?
    - admin pages
    - disable buttons that don't do anything on the current screen + change button font color when disabled
    - make sure language codes are in the database -- base lang code for all record in public."language", but dialect codes for each version?
    - populate translationList in header
*/