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
        $valtypes = ['s','i','i','s'];
        break;
    case 2: // new author
        $name = $_POST['name'];
        $lang = $_POST['lang'];
        
        $table = 'Author';
        $cols = 'name';
        $vals = [$name];
        $valtypes = ['s'];
        break;
    case 3: // new language
        $name = $_POST['name'];
        
        $table = 'Language';
        $cols = 'name';
        $vals = [$name];
        $valtypes = ['s'];
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
        $valtypes = ['s','i','s','s'];
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
        $valtypes = ['i','i','i','i','i','s','i'];
        break;
    default: // invalid load
        break;
}

$sql = "INSERT INTO " . $table . " (" . $cols . ") VALUES (";
for ($i = 0; $i < sizeof($vals); $i++) {
    if ($i > 0) {
        $sql .= ',';
    }
    $sql .= "?";
}
$sql .= ");";

//echo $sql;

$db = getDbConnection();
$stmt = $db->prepare($sql);
if ($db->errno != 0) {
    echo "Error " . $db->errno . ": " . $db->error;
}

bindMe($stmt,$valtypes,$vals);

$stmt->execute();
if ($stmt->errno != 0) {
    echo "Error " . $stmt->errno . ": " . $stmt->error;
}

if ($loadType == 2) {
    $id = $db->insert_id;
    for ($i = 0; $i < sizeof($lang); $i++) {
        $sql = "INSERT INTO WritesIn (authorId, langId) VALUES (" . $id . ", ?);";
        $stmt = $db->prepare($sql);
        if ($db->errno != 0) {
            echo "Error " . $db->errno . ": " . $db->error;
        }
        $stmt->bind_param('s',$lang[$i]);
        if ($stmt->errno != 0) {
            echo "Error " . $stmt->errno . ": " . $stmt->error;
        }
        $stmt->execute();
        if ($stmt->errno != 0) {
            echo "Error " . $stmt->errno . ": " . $stmt->error;
        }
    }
} else if ($loadType == 4) {
    $id = $db->insert_id;
    for ($i = 0; $i < sizeof($doc); $i++) {
        $sql = "INSERT INTO Translates (translId, docId) VALUES (" . $id . ", ?);";
        $stmt = $db->prepare($sql);
        
        if ($db->errno != 0) {
            echo "Error " . $db->errno . ": " . $db->error;
        }
        $stmt->bind_param('s',$doc[$i]);
        if ($stmt->errno != 0) {
            echo "Error " . $stmt->errno . ": " . $stmt->error;
        }
        $stmt->execute();
        if ($stmt->errno != 0) {
            echo "Error " . $stmt->errno . ": " . $stmt->error;
        }
    }
}

header("Location: loadInput.php?type=$loadType");

exit;

function bindMe($stmt, $valtypes, $vals) {
    //echo "<pre>"; print_r($valtypes); print_r($vals); echo "</pre>";
    switch(sizeof($vals)) {
        case 1: // author, language
            $stmt->bind_param(implode('',$valtypes),$vals[0]);
            break;
        case 3: // translation
        case 4: // document
            $stmt->bind_param(implode('',$valtypes),$vals[0],$vals[1],$vals[2],$vals[3]);
            break;
        case 7: // text
            $stmt->bind_param(implode('',$valtypes),$vals[0],$vals[1],$vals[2],$vals[3],$vals[4],$vals[5],$vals[6]);
            break;
        default: // invalid
            break;
    }
    
    if ($stmt->errno != 0) {
        echo "Error " . $stmt->errno . ": " . $stmt->error;
    }
}

?>