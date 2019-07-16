<?php

include "dbconnect.php";

$db = getDbConnection();

$type = $_GET['type'];
switch($type) {
    case 1:
        $typeName = " Document";
        break;
    case 2:
        $typeName = "n Author";
        break;
    case 3:
        $typeName = " Language";
        break;
    case 4:
        $typeName = " Translation";
        break;
    /*case 5:
        $typeName = " Section";
        break;*/
    case 6:
        $typeName = " Text";
        break;
    default:
        break;
}

$docs = FALSE;
$authors = FALSE;
$langs = FALSE;
$transls = FALSE;

if ($type == 4 || $type == 6) {
    $sql = "SELECT id, name FROM Document ORDER BY id;";
    $docs = $db->query($sql);
}

if ($type == 1) {
    $sql = "SELECT id, name FROM Author ORDER BY name;";
    $authors = $db->query($sql);
}

if ($type == 1 || $type == 2 || $type == 4) {
    $sql = "SELECT id, name FROM language ORDER BY name;";
    $langs = $db->query($sql);
}

if ($type == 6) {
    $sql = "SELECT id, name, docId FROM Translation, Translates WHERE Translates.translId = Translation.id ORDER BY name;";
    $transls = $db->query($sql);
}

function printOptionRow($row) {
    echo '<option value="' . $row['id'] . '">' . $row['name'] . '</option>';
}

?>
<html>
<head>
    <title>Load a<?php echo $typeName ?> into Hexapla</title>
    <script type="text/javascript" src="loadInput.js"></script>
    <script type="text/javascript">
        <?php if ($transls !== FALSE) { ?>
            var translations = <?php echo json_encode(mysqli_fetch_all($transls, MYSQLI_NUM)); ?>;
        <?php } ?>
    </script>
    <link rel="stylesheet" type="text/css" href="style.css" />
</head>

<body>
    <div class="head"></div>
    <div class="wrap">
    <?php include "admnav.html"; ?>
    <div class="adminwrap">
    <form action="load.php" method="post">
        <input type="hidden" name="type" id="type" value="<?php echo $type; ?>" />
    <table>
        <tr>
            <td colspan="2"><h3>Load a<?php echo $typeName ?> into Hexapla</h3></td>
        </tr>
        <tr>
            <?php
    
        // ----- NAME ENTRY -----
    
                if ($type != 6) {
                    ?>
            <td><label for="name">Name</label></td>
            <td><input class="textin" type="text" name="name" id="name" /></td>
                    <?php
                }
        
        // ----- DOCUMENT SELECT -----
        
                if ($type == 4 || $type == 6) {
                    ?>
            <td><label for="doc">Document:</label></td>
            <td>
                <select class="textin"  id="doc" name="doc<?php if ($type == 4) { echo '[]"'; } else { ?>" onchange="setDoc()" <?php } if ($type == 4) { ?>multiple<?php } ?>>
                    <?php if ($type != 4) { ?>
                    <option selected value=""></option>
                    <?php }
                    while ($row = mysqli_fetch_assoc($docs)) {
                        printOptionRow($row);
                    }
                    ?>
                </select>
            </td>
                    <?php
                }
                
                if ($type == 6) {
                    ?>
            <td><label for="transl">Translation / Version:</label></td>
            <td>
                <select class="textin" name="transl" id="transl">
                    <option selected value=""></option>
                </select>
            </td>
                    <?php
                }
                
        // CLOSE FIRST ROW AND OPEN SECOND
                ?>
        </tr>
        <tr>
                <?php
        
        // ----- AUTHOR SELECT -----
        
                if ($type == 1) {
                    ?>
            <td><label for="author">Author:</label></td>
            <td>
                <select class="textin" name="author" id="author">
                    <option selected value=""></option>
                    <?php
                    while ($row = mysqli_fetch_assoc($authors)) {
                        printOptionRow($row);
                    }
                    ?>
                </select>
            </td>
                    <?php
                }
        
        // ----- LANGUAGE SELECT -----
        
                if ($type == 1 || $type == 2 || $type == 4) {
                    ?>
            <td><label for="lang">Language:</label></td>
            <td>
                <select class="textin" name="lang<?php if ($type == 2) { echo '[]'; } ?>" id="lang" <?php if($type == 2) { ?>multiple<?php } ?>>
                    <?php if ($type != 2) { ?>
                    <option selected value=""></option>
                    <?php }
                    while ($row = mysqli_fetch_assoc($langs)) {
                        printOptionRow($row);
                    }
                    ?>
                </select>
            </td>
                    <?php
                }
        
        // ----- PUBLICATION DATE ENTRY -----
        
                if ($type == 1 || $type == 4) {
                    if ($type == 1) {
                        ?>
        </tr>
        <tr>
                        <?php
                    }
                    ?>
            <td><label for="pub">Publication Date:</label></td>
            <td><input class="textin" type="date" name="pub" id="pub" /></td>
                    <?php
                }
            
        // CLOSE SECOND ROW ?>
        </tr>
                <?php
        
        // ----- REMAINING OPTIONS -----
        
                if ($type == 4) {
                    ?>
        <tr>
            <td><label for="abbrev">Abbreviation:</label></td>
            <td><input class="textin" type="text" name="abbrev" id="abbrev" /></td>
        </tr>
                    <?php
                }
        
                if ($type == 6) {
                    
                    // SECTION SELECT
                    ?>
        <tr>
            <td><label for="section">Chapter / Section:</label></td>
            <td><input class="textin" type="text" name="section" id="section" /></td>
        </tr>
                    <?php
                    // CONTENT START
                    ?>
        <tr>
            <td><label for="contentStart">First Verse / Paragraph Number:</label></td>
            <td><input class="textin" type="number" name="contentStart" id="contentStart" /></td>
                    <?php
                    // CONTENT END
                    ?>
            <td><label for="contentEnd">Last Verse / Paragraph Number:</label></td>
            <td><input class="textin" type="number" name="contentEnd" id="contentEnd" /></td>
        </tr>
                    <?php
                    // CONTENT
                    ?>
        <tr>
            <td colspan="4"><label for="content">Content:</label></td>
        </tr>
        <tr>
            <td colspan="4"><textarea name="content" id="content"></textarea></td>
        </tr>
                    <?php
                    // IS ORIGINAL
                    ?>
        <tr>
            <td colspan="4">
                <input type="checkbox" name="orig" id="orig" />
                <label for="orig">This text is from the original version of this document</label>
            </td>
        </tr>
                    <?php
                }
            ?>
    </table>
        <input type="submit" value="Submit" />
    </form>
    </div>
    </div>
</body>
</html>