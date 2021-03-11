<?php
require_once "sql-functions.php";

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
        $toLoad = 'results.php';
        break;
    default:
        $toLoad = 'error404.html';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Modern Hexapla</title>
    <link type="text/css" rel="stylesheet" href="styles/icofont.min.css" />
    <link type="text/css" rel="stylesheet" href="styles/jonah.css" />
    <script type="text/javascript" src="scripts/functions.js"></script>
    <script type="text/javascript" src="scripts/define.js"></script>
    <script type="text/javascript" src="scripts/nav-and-search.js"></script>
    <script type="text/javascript" src="scripts/tl-config.js"></script>
    <script type="text/javascript" src="scripts/diff.js"></script>
    <script type="text/javascript" src="scripts/sidebar.js"></script>
</head>
<body class="light">
    <div id="wrap">
        <?php include "header.php"; ?>
        <?php include "translation-controller.php"; ?>
        <div id="page">
            <?php include $toLoad; ?>
        </div>
    </div>
    <?php include "sidebar.php"; ?>
    <div id="loading" class="hidden"></div>
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
    - dynamic theming --> random for users w/ no cookies, no login | liturgical implementation
    - handle commentary text
    - crossrefs --> literal | add lemma to Strong's title
    - add user functionality
        -> user notes
            --> associate with all verses -- load all in order if viewing >1 verse, but then have to save all for all verses
            --> if save notes to 10 verses, then update for 1 verse, must update for all 10
            --> add it from translation config screen
            --> WYSIWYG editor
        -> qualifications - contributions?
    - permalinks
    - accessibility
*/