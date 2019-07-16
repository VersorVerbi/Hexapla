<?php

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

include "dbconnect.php";
include "diff.php";

$db = getDbConnection();

if (isset($_GET['diff'])) {
    $diff = $_GET['diff'];
    $byWord = 1; // cookie w/ 1 as default
    $caseSensitive = 0; // cookie w/ 0 as default
}

$srch = $_GET['search'];
$srchSplit = explode(' ', $srch);
if (count($srchSplit) < 2) {
    header("Location: " . $baseurl . "error404.php?err=2");
    exit;
}
$book = $srchSplit[0];
$translations = explode(' ', $_GET['tr']);
$sctnCtnt = explode(':', $srchSplit[1]);
$section = $sctnCtnt[0];
if (count($sctnCtnt) > 1) {
    $contentRange = explode('-',$sctnCtnt[1]);
    $contentStart = $contentRange[0];
    if (count($contentRange) > 1) {
        $contentEnd = $contentRange[1];
    } else {
        $contentEnd = $contentStart;
    }
} else {
    // This code will need to be changed when we have something besides the Bible in here
    $contentStart = 0;
    $contentEnd = 200;
}

$sql = "SELECT document.id,document.name,documentalias.isPrimary FROM `document`,`documentalias` WHERE documentalias.alias LIKE ? AND documentalias.docId = document.id;";
$stmt = $db->prepare($sql);
$stmt->bind_param("s",$book);
$stmt->execute();
$bookResults = $stmt->get_result();
$stmt->close();
$bookArray = $bookResults->fetch_all(MYSQLI_ASSOC);
$alts = [];

foreach ($bookArray as $book) {
    if ($book['isPrimary']) {
        $bookresult = $book['id'];
        $bookname = $book['name'];
    } else {
        $alts[] = array($book['id'],$book['name']);
    }
}

if (!$bookresult) {
    // quit with error about book not existing
    header("Location: " . $baseurl . "error404.php?err=1");
    exit;
}

$translates = 1;

$sql = "SELECT COUNT(*) FROM `translates` WHERE translates.translId = ? AND translates.docId = ?;";
for ($r = 0; $r < count($translations); $r++) {
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ii",$translations[$r],$bookresult);
    $stmt->execute();
    $stmt->bind_result($tCheck);
    $stmt->fetch();
    $stmt->close();
    $translates = $translates && $tCheck;
}

if (!$translates) {
    // quit with error about translation not including that book
    header("Location: " . $baseurl . "error404.php?err=3");
    exit;
}

$sql = "SELECT translation.name,alias,language.id FROM `translation`,`language` WHERE translation.id = ? AND translation.langId = language.id;";
for ($r = 0; $r < count($translations); $r++) {
    $stmt = $db->prepare($sql);
    $stmt->bind_param("s",$translations[$r]);
    $stmt->execute();
    $stmt->bind_result($tName, $tAlias, $tLang);
    $stmt->fetch();
    $stmt->close();
    $tAbbr[$r] = $tAlias;
    $tNames[$r] = $tName;
    $tLangs[$r] = $tLang;
}

//echo "Book ID: $bookresult<BR>Section: $section<BR>Start: $contentStart<BR>End: $contentEnd<BR>Translation IDs: " . implode('+',$translations) . "<BR><BR>";

$sql = "SELECT contentStartId,contentEndId,content FROM text WHERE docId = ? AND sectionId = ? AND contentStartId <= ? AND contentEndId >= ? AND translId = ?;";
for ($r = 0; $r < count($translations); $r++) {
    $stmt = $db->prepare($sql);
    $stmt->bind_param("iiiii",$bookresult,$section,$contentEnd,$contentStart,$translations[$r]);
    $stmt->execute();
    $contentResult = $stmt->get_result();
    $stmt->close();
    $allPassages[$r] = $contentResult->fetch_all(MYSQLI_ASSOC);
}

// TODO: quit with error when passage(s) doesn't exist

// Get ALL translations (for translation drop-down)
$sql = "SELECT translation.id, translation.name, alias, language.name FROM `translation`, `language` WHERE translation.langId = language.id ORDER BY language.name, translation.name;";
$stmt = $db->prepare($sql);
$stmt->execute();
$translResult = $stmt->get_result();
$stmt->close();
$allTranslations = $translResult->fetch_all(MYSQLI_NUM);

$title = "$bookname $section:$contentStart" . ($contentStart != $contentEnd ? "-$contentEnd" : "") . " (" . implode(', ',$tAbbr) . ")";

$diffed = [];

if (isset($diff)) {
    for ($i = 0; $i < strlen($diff); $i++) {
        if (in_array($diff[$i], $diffed)) continue;
        diffPassage($allPassages[0], $allPassages[$diff[$i]-1], $byWord, $caseSensitive, 'diff' . $diff[$i] . 'L', 'diff' . $diff[$i] . 'R');
        $diffed[] = $diff[$i];
    }
}

$primLang = $tLangs[0];

//echo "<PRE>";
//print_r($allPassages);
//echo "</PRE>";

function diffPassage(&$passageGroup1, &$passageGroup2, $byWord, $caseSensitive, $ltClass, $rtClass) {
    for ($i = 0; $i < count($passageGroup1) && $i < count($passageGroup2); $i++) {
        $passage1 = $passageGroup1[$i]['content'];
        $passage2 = $passageGroup2[$i]['content'];
        diff($passage1, $passage2, $byWord, $caseSensitive, $outArr, $ltClass, $rtClass);
        $passageGroup1[$i]['content'] = $outArr[0];
        $passageGroup2[$i]['content'] = $outArr[1];
    }
}

function printPassage($passageGroup) {
    foreach($passageGroup as $passage) {
        echo "<p><sup>" . $passage['contentStartId'] . ($passage['contentStartId'] != $passage['contentEndId'] ? '-' . $passage['contentEndId'] : '') . "</sup>" . $passage['content'] . '</p>';
    }
}

?>

<HTML>
    <HEAD>
        <TITLE><?php echo $title; ?></TITLE>
        <link type="text/css" rel="stylesheet" href="mainstyle.css" />
    </HEAD>
    <BODY>
        <?php include "menu.html"; ?>
        <div id="translModal" class="modal hidden">
            <div id="translBox" class="modal-content">
                <label for="translSelect">Pick a translation to add:</label><BR />
                <select id="translSelect" name="translSelect">
                    <?php
                        $previousLanguage = "";
                        foreach ($allTranslations as $t) {
                            if (!in_array($t[0], $translations)) {
                                if ($t[3] != $previousLanguage) {
                                    $previousLanguage = $t[3];
                                    echo "</optgroup>";
                                    echo "<optgroup label='$previousLanguage'>";
                                }
                                echo "<option value='" . $t[0] . "'>" . $t[1] . " (" . $t[2] . ")</option>";
                            }
                        }
                    ?>
                </select><BR />
                <input type="button" value="Cancel" id="cancelAdd" name="cancelAdd" />
                <input type="submit" value="OK" id="addTlSubmit" name="addTlSubmit" />
            </div>
        </div>
        <DIV id="wrap">
            <H3><?php echo $title; ?></H3>
            <DIV id="addtl" class="<?php echo count($translations) >= 6 ? "hidden" : "";?>"><span></span></DIV>
            <!-- directional buttons for next verse/chapter -->
            <DIV id="prevPsg"><span></span></DIV>
            <DIV id="nextPsg"><span></span></DIV>
            <DIV id="innerwrap">
                <DIV id="first" class="box">
                    <DIV class="scrollbox">
                        <H4><?php echo $tNames[0]; ?></H4>
                        <DIV class="del<?php echo (count($translations) > 1) ? "" : " hidden"; ?>"><span></span></DIV>
                        <?php printPassage($allPassages[0]); ?>
                    </DIV>
                    <DIV class="diff<?php echo (count($translations) > 1) ? "" : " hidden"; ?>">
                        Differences checked against this translation
                    </DIV>
                </DIV>
                <DIV id="second" class="box">
                    <DIV class="scrollbox">
                        <H4><?php echo $tNames[1]; ?></H4>
                        <DIV class="promote"><span></span></DIV> <DIV class="del"><span></span></DIV>
                        <?php printPassage($allPassages[1]); ?>
                    </DIV>
                    <DIV class="diff">
                        <input class="<?php echo ($tLangs[1] != $primLang ? " hidden" : ""); ?>" type="checkbox" onclick="generalDiff(this)" id="genDiff2" name="genDiff2"<?php echo isset($diff) ? (strpos($diff, '2') !== FALSE ? " checked" : "") : ""; ?> /><label class="<?php echo ($tLangs[1] != $primLang ? " hidden" : ""); ?>" for="genDiff2">Show differences</label>
                    </DIV>
                </DIV>
                <DIV id="third" class="box">
                    <DIV class="scrollbox">
                        <H4><?php echo $tNames[2]; ?></H4>
                        <DIV class="promote"><span></span></DIV> <DIV class="del"><span></span></DIV>
                        <?php printPassage($allPassages[2]); ?>
                    </DIV>
                    <DIV class="diff">
                        <input class="<?php echo ($tLangs[2] != $primLang ? " hidden" : ""); ?>" type="checkbox" onclick="generalDiff(this)" id="genDiff3" name="genDiff3"<?php echo isset($diff) ? (strpos($diff, '3') !== FALSE ? " checked" : "") : ""; ?> /><label class="<?php echo ($tLangs[2] != $primLang ? " hidden" : ""); ?>" for="genDiff3">Show differences</label>
                    </DIV>
                </DIV>
                <DIV id="fourth" class="box">
                    <DIV class="scrollbox">
                        <H4><?php echo $tNames[3]; ?></H4>
                        <DIV class="promote"><span></span></DIV> <DIV class="del"><span></span></DIV>
                        <?php printPassage($allPassages[3]); ?>
                    </DIV>
                    <DIV class="diff">
                        <input class="<?php echo ($tLangs[3] != $primLang ? " hidden" : ""); ?>" type="checkbox" onclick="generalDiff(this)" id="genDiff4" name="genDiff4"<?php echo isset($diff) ? (strpos($diff, '4') !== FALSE ? " checked" : "") : ""; ?> /><label class="<?php echo ($tLangs[3] != $primLang ? " hidden" : ""); ?>" for="genDiff4">Show differences</label>
                    </DIV>
                </DIV>
                <DIV id="fifth" class="box">
                    <DIV class="scrollbox">
                        <H4><?php echo $tNames[4]; ?></H4>
                        <DIV class="promote"><span></span></DIV> <DIV class="del"><span></span></DIV>
                        <?php printPassage($allPassages[4]); ?>
                    </DIV>
                    <DIV class="diff">
                        <input class="<?php echo ($tLangs[4] != $primLang ? " hidden" : ""); ?>" type="checkbox" onclick="generalDiff(this)" id="genDiff5" name="genDiff5"<?php echo isset($diff) ? (strpos($diff, '5') !== FALSE ? " checked" : "") : ""; ?> /><label class="<?php echo ($tLangs[4] != $primLang ? " hidden" : ""); ?>" for="genDiff5">Show differences</label>
                    </DIV>
                </DIV>
                <DIV id="sixth" class="box">
                    <DIV class="scrollbox">
                        <H4><?php echo $tNames[5]; ?></H4>
                        <DIV class="promote"><span></span></DIV> <DIV class="del"><span></span></DIV>
                        <?php printPassage($allPassages[5]); ?>
                    </DIV>
                    <DIV class="diff">
                        <input class="<?php echo ($tLangs[5] != $primLang ? " hidden" : ""); ?>" type="checkbox" onclick="generalDiff(this)" id="genDiff6" name="genDiff6"<?php echo isset($diff) ? (strpos($diff, '6') !== FALSE ? " checked" : "") : ""; ?> /><label class="<?php echo ($tLangs[5] != $primLang ? " hidden" : ""); ?>" for="genDiff6">Show differences</label>
                    </DIV>
                </DIV>
            </DIV>
        </DIV>
        <script type="text/javascript">
            var translCount = <?php echo count($allPassages); ?>;
        </script>
        <script type="text/javascript" src="basescript.js"></script>
        <script type="text/javascript" src="script.js"></script>
    </BODY>
</HTML>