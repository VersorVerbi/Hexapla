<?php

include 'dbconnect.php';

$loadType = $_POST['type'];
switch($loadType) {
    case 1: // new document
        $name = $_POST['name'];
        $author = $_POST['author'];
        $lang = $_POST['lang'];
        $pub = $_POST['pub'];
        
        $table = 'Document';
        $cols = 'name, langId, authId, publicationDate';
        $vals = [$name, $lang, $author, $pub];
        //$valtypes = ['s','i','i','s'];
        break;
    case 2: // new author
        $name = $_POST['name'];
        $lang = $_POST['lang'];
        
        $table = 'Author';
        $cols = 'name';
        $vals = [$name];
        //$valtypes = ['s'];
        break;
    case 3: // new language
        $name = $_POST['name'];
        
        $table = 'Language';
        $cols = 'name';
        $vals = [$name];
        //$valtypes = ['s'];
        break;
    case 4: // new translation
        $name = $_POST['name'];
        $doc = $_POST['doc'];
        $lang = $_POST['lang'];
        $pub = $_POST['pub'];
        $abbr = $_POST['abbrev'];
        
        $table = 'Translation';
        $cols = 'name, langId, publicationDate, alias';
        $vals = [$name, $lang, $pub, $abbr];
        //$valtypes = ['s','i','s','s'];
        break;
    /*case 5: // new section
        $name = $_POST['name'];
        $doc = $_POST['doc'];
        
        $table = 'Section';
        $cols = 'name, docId';
        $vals = $name . ', ' . $doc;
        break;*/
    case 6: // new text
        $doc = $_POST['doc'];
        $section = $_POST['section'];
        $contentStart = $_POST['contentStart'];
        $contentEnd = $_POST['contentEnd'];
        $transl = $_POST['transl'];
        $content = $_POST['content'];
        if (isset($_POST['orig'])) {
            $orig = $_POST['orig'];
        } else {
            $orig = 0;
        }
        
        $table = 'Text';
        $cols = 'docId, sectionId, contentStartId, contentEndId, translId, content, isOriginal';
        $vals = [$doc,$section,$contentStart,$contentEnd,$transl,$content,$orig];
        //$valtypes = ['i','i','i','i','i','s','i'];
        break;
    default: // invalid load
        break;
}

$sql = "INSERT INTO " . $table . " (" . $cols . ") VALUES (";
for ($i = 1; $i <= sizeof($vals); $i++) {
    if ($i > 1) {
        $sql .= ',';
    }
    $sql .= "$$i";
}
$sql .= ");";

//echo $sql;

$db = getDbConnection();
$stmt = pg_prepare($db, "", $sql);

$result = pg_execute($db,"", $vals);
if ($result === FALSE) {
    echo "Error: " . pg_last_error($db);
}

if ($loadType == 2) {
    $id = pg_last_oid($result);
    for ($i = 0; $i < sizeof($lang); $i++) {
        $sql = "INSERT INTO WritesIn (authorId, langId) VALUES (" . $id . ", $1);";
        $stmt = pg_prepare($db, "", $sql);
        $result = pg_execute($db, "", [$lang[$i]]);
        if ($result === FALSE) {
            echo "Error: " . pg_last_error($db);
        }
    }
} else if ($loadType == 4) {
    $id = pg_last_oid($result);
    for ($i = 0; $i < sizeof($doc); $i++) {
        $sql = "INSERT INTO Translates (translId, docId) VALUES (" . $id . ", $1);";
        $stmt = pg_prepare($db, "", $sql);
        $result = pg_execute($db, "", [$doc[$i]]);
        if ($result === FALSE) {
            echo "Error: " . pg_last_error($db);
        }
    }
}

header("Location: loadInput.php?type=$loadType");

exit;

?>