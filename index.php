<?php
include "dbconnect.php";

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
    <link type="text/css" rel="stylesheet" href="" />
    <script type="text/javascript" src="scripts/"></script>
</head>
<body>
    <div id="wrap">
        <?php include "header.php"; ?>
        <div id="page">
            <?php include $toLoad; ?>
        </div>
    </div>
</body>
</html>