<?php
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

if (!isset($_GET['page'])) {
    $page = 'home';
} else {
    $page = $_GET['page'];
}
$toLoad = "";
switch($page) {
    case 'other':
        $toLoad = 'cant-find.html';
        break;
    case 'help':
        $toLoad = 'how-to-help.html';
        break;
    case 'home':
        $toLoad = 'home-page.html';
        break;
    case 'search':
        $search = $_GET['search'];
        $tls = $_GET['vers']; // TODO: handle abbreviation listings in addition to id listings
        $toLoad = "results.php";
        break;
    default:
        $toLoad = 'error404.html';
}

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
    <title>Modern Hexapla</title>
    <?php
    foreach($GLOBALS['themes'] as $thm) {
        echo "<link rel=\"preload\" as=\"style\" href=\"styles/$thm.css\" />";
    }
    ?>
    <link type="text/css" rel="stylesheet" href="styles/icofont.min.css" />
    <link id="themeCss" type="text/css" rel="stylesheet" href="styles/<?php echo $theme; ?>.css" />
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
        <style type="text/css">
            .jsOnly {
                display: none !important;
            }
        </style>
    </noscript>
</head>
<body class="<?php echo $shade; ?> <?php echo ($theme === 'liturgical' ? 'themeChange' : ''); ?>">
    <div id="wrap">
        <?php include "header.php"; ?>
        <?php include "translation-controller.php"; ?>
        <div id="page">
            <?php include $toLoad; ?>
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
        }
        ?>

        init_tinymce('#my-notes', <?php echo json_encode($tinySkin); ?>);

        document.getElementById('currentTinyMCETheme').value = <?php echo json_encode($tinySkin); ?>;
    </script>
</body>
</html>


<?php
/* TODO list
    - add/remove notices with JS --> call JS functions when appropriate (when is it appropriate besides diffing?)
    - add diff --> add button and code to run diff code | include byWord and caseSensitive options | add way to turn OFF diffing
    - add sidebar --> enable all functionality
    - add click->dictionary+toggle for words --> finish getting data from all sources
    - reload, add more translations
    - add page content + how can I help? page
    - handle commentary text --> display it too
    - crossrefs -> literal
    - add user functionality
        -> user notes
            --> if save notes to 10 verses, then notes_on_loc lists all 10 for that verse ID and we just update the note once
            --> export functionality
        -> qualifications - contributions?
        -> also remove/destroy the tinymce so we can init a new one after loading new text
    - permalinks --> translation permalinks->tl-config screen
    - accessibility
    - noJS versions of everything
    - some sort of test suite?
*/