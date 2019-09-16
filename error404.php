<?php

$errType = $_GET['err'];
switch($errType) {
    case 1:
        $errTitle = "No Such Book";
        $errStr = "That book could not be found in our database. Please try again.";
        break;
    case 2:
        $errTitle = "Bad Search";
        $errStr = "That search did not match the standard format ([BOOK] [CHAPTER] or [BOOK] [CHAPTER]:[VERSE(S)]). Please try again.";
        break;
    case 3:
        $errTitle = "Book Not Covered by Translation";
        $errStr = "One of the translations you have selected does not include that book. Please try again.";
        break;
    case 4:
        $errTitle = "No Such Passage";
        $errStr = "That passage could not be found in our database. Please try again.";
        break;
    default:
        $errTitle = "";
        $errStr = "The page you're looking for could not be found. Return <a href=\"index.php\">home</a> or open the menu and try searching for a passage.";
        break;
}

$title = "Error 404 $errTitle";

?>

<HTML>
    <HEAD>
        <TITLE><?php echo $title; ?></TITLE>
        <link type="text/css" rel="stylesheet" href="style/mainstyle.css" />
    </HEAD>
    <BODY>
        <?php include "menu.php"; ?>
        <DIV id="wrap">
            <div class="page">
                <?php include "header.php"; ?>
            </div>
            <DIV id="pagewrap">
                <?php echo $errStr; ?>
            </DIV>
        </DIV>
        <script type="text/javascript" src="script/basescript.js"></script>
    </BODY>
</HTML>