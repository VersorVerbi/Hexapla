<?php

include "dbconnect.php";
include "diff.php";

$db = getDbConnection();

if (isset($_GET['diff'])) {
    $diff = $_GET['diff'];
    $byWord = 1; // cookie w/ 1 as default
    $caseSensitive = 0; // cookie w/ 0 as default
}

$srch = $_GET['search'];

// TODO: make more robust string division function

$srchSplit = explode(' ', $srch);
if (count($srchSplit) < 2) {
    header("Location: " . $baseurl . "error404.php?err=2");
    exit;
}
$book = $srchSplit[0];
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
$numContents = $contentEnd - $contentStart;
$translations = array_values(array_unique(explode(' ',$_GET['tr']), SORT_NUMERIC));
$primaryTranslation = $translations[0];

if (isset($_GET['incr']) || isset($_GET['decr'])) {
    $q = pg_query($db,"CALL ContentInSection($bookresult, $section);");
    $nContentInSection = pg_fetch_all($q,PGSQL_NUM)[0][0];

    $q = pg_query($db, "CALL SectionsInDocument($bookresult);");
    $nSectionInDocument = pg_fetch_all($q, PGSQL_NUM)[0][0];
    
    if (isset($_GET['incr'])) {
        if (($contentEnd == 200) || (($contentStart + $numContents) > $nContentInSection)) {
            $section++;
            if ($section > $nSectionInDocument) {
                /* USE documentgroup AND isingroup to fix this
                $bookresult++;
                $sql = "SELECT name FROM `document` WHERE id = ?;";
                $stmt = pg_prepare($db, $sql);
                $stmt->bind_param("i", $bookresult);
                $stmt->execute();
                $stmt->bind_result($sBook);
                $stmt->fetch();
                $stmt->close(); */
                
                $section = 1;
            } else {
                $sBook = $book;
            }
            $contentStart = 1;
            $contentEnd = $contentStart + $numContents;
            $targetSearch = "$sBook+$section";
        } else {
            $contentStart += $numContents;
            $contentEnd += $numContents;
        }
    }
    
    if (isset($_GET['decr'])) {
        // TODO: finish this
        
        if ($contentEnd == 200) {
            $section--;
            
        }
    }
    
    $targetUrl = $baseurl . "search.php?search=$targetSearch&tr=" . $_GET['tr'] . (isset($_GET['diff']) ? "&diff=" . $_GET['diff'] : "");
    header("Location: $targetUrl");
    exit;
}

$q = pg_query($db, "CALL BookSearch('$book');");
$bookArray = pg_fetch_all($q,PGSQL_ASSOC);

$alts = [];

foreach ($bookArray as $bookOption) {
    if ($bookOption['isPrimary']) {
        $bookresult = $bookOption['id'];
        $bookname = $bookOption['name'];
    } else {
        $alts[] = array($bookOption['id'],$bookOption['name']);
    }
}

if (!$bookresult) {
    // quit with error about book not existing
    header("Location: " . $baseurl . "error404.php?err=1");
    exit;
}

//print_r($translations);
$trl_sql_in = implode(",", $translations);
$q = pg_query($db, "CALL AtLeastOneTranslates('$trl_sql_in', $bookresult);");
$translates = pg_fetch_all($q, PGSQL_NUM)[0][0];

if (!$translates) {
    // quit with error about NONE of the translations including that book
    //header("Location: " . $baseurl . "error404.php?err=3");
    exit;
}

$q = pg_query($db, "CALL TranslationList('$trl_sql_in');");
$tOut = pg_fetch_all($q, PGSQL_NUM);

for ($r = 0; $r < count($tOut); $r++) {
    foreach ($tOut as $t) {
        if ($t[3] == $translations[$r]) {
            $tNames[$r] = $t[0];
            $tAbbr[$r] = $t[1];
            $tLangs[$r] = $t[2];
        }
    }
}

//echo "Book ID: $bookresult<BR>Section: $section<BR>Start: $contentStart<BR>End: $contentEnd<BR>Translation IDs: " . implode('+',$translations) . "<BR><BR>";

$q = pg_query($db, "CALL GetPassages($bookresult, $section, $contentEnd, $contentStart, '$trl_sql_in');");
$assocArray = pg_fetch_all($q, PGSQL_ASSOC);
//echo "<PRE>";
//print_r($assocArray);
//echo "</PRE>";
for ($r = 0; $r < count($translations); $r++) {
    foreach ($assocArray as $psg) {
        if ($psg['translId'] == $translations[$r]) {
            $allPassages[$r][] = $psg;
        }
    }
}

// TODO: quit with error when passage(s) doesn't exist

// Get ALL translations (for translation drop-down)
$q = pg_query($db, "CALL AllTranslationsForDoc($bookresult);");
$allTranslations = pg_fetch_all($q, PGSQL_NUM);

$title = "$bookname $section:$contentStart" . ($contentStart != $contentEnd ? "-$contentEnd" : "") . " (" . implode(', ',$tAbbr) . ")";

$diffed = [];
$diffResults = [];

if (isset($diff)) {
    for ($i = 0; $i < strlen($diff); $i++) {
        if (in_array($diff[$i], $diffed)) continue;
        $diffRc = diffPassage($allPassages[0], $allPassages[$diff[$i]-1], $byWord, $caseSensitive, 'diff' . $diff[$i] . 'L', 'diff' . $diff[$i] . 'R');
        $diffed[] = $diff[$i];
        $diffResults[$diff[$i] - 1] = $diffRc;
    }
}

$primLang = $tLangs[0];

//echo "<PRE>";
//print_r($allPassages);
//print_r($diffResults);
//echo "</PRE>";

function diffPassage(&$passageGroup1, &$passageGroup2, $byWord, $caseSensitive, $ltClass, $rtClass) {
    for ($i = 0; $i < count($passageGroup1) && $i < count($passageGroup2); $i++) {
        $passage1 = $passageGroup1[$i]['content'];
        $passage2 = $passageGroup2[$i]['content'];
        $rc[$i] = diff($passage1, $passage2, $byWord, $caseSensitive, $outArr, $ltClass, $rtClass);
        $passageGroup1[$i]['content'] = $outArr[0];
        $passageGroup2[$i]['content'] = $outArr[1];
    }
    return $rc;
}

function printPassage($passageGroup, $diffResult) {
    if (isset($diffResult)) {
        if (in_array(-1, $diffResult)) {
            echo "<p class='baddiff'>Notice: At least one passage in this section caused the comparison function to fail because of that passage's length. It will not display differences.</p>";
        }
    }
    foreach($passageGroup as $passage) {
        echo "<p><sup>" . $passage['contentStartId'] . ($passage['contentStartId'] != $passage['contentEndId'] ? '-' . $passage['contentEndId'] : '') . "</sup>" . $passage['content'] . '</p>';
    }
}

function getBoxId($n) {
    switch($n) {
        case 0: return "first";
        case 1: return "second";
        case 2: return "third";
        case 3: return "fourth";
        case 4: return "fifth";
        default: return "sixth";
    }
}

function printBox($n) {
    global $allPassages, $tLangs, $primLang, $translations, $tNames, $diffResults, $diff;
    $moreThanOne = count($translations) > 1;
    echo "<div id=\"" . getBoxId($n) . "\" class=\"box\">";
    echo "  <div class=\"scrollbox\">";
    echo "      <h4>" . $tNames[$n] . "</h4>";
    
    if ($moreThanOne) {
        echo "      <div class=\"del\"><span></span></div>";
        if ($n > 0) {
            echo "      <div class=\"promote\"><span></span></div>";
        }
    }
    
    if (isset($allPassages[$n])) {
        if ($n > 0) {
            if (isset($diffResults[$n])) {
                printPassage($allPassages[$n], $diffResults[$n]);
            } else {
                goto Undiffed;
            }
        } else {
Undiffed:   printPassage($allPassages[$n], null);
        }
    } else {
        echo "      <div class=\"noTextBox\">This translation does not include the given passage.</div>";
    }
    
    if ($moreThanOne) {
        echo "      <div class=\"diff\">";
        if ($n == 0) {
            echo "          Differences checked against this translation";
        } elseif ($tLangs[$n] != $primLang) {
            echo "          Language incompatible for difference checking";
        } else {
            echo "          <input type=\"checkbox\" onclick=\"generalDiff(this)\" id=\"genDiff";
            echo ($n + 1) . "\" name=\"genDiff" . ($n + 1) . "\"";
            if (isset($diff)) {
                if (strpos($diff, chr($n+49)) !== FALSE) {
                    echo " checked";
                }
            }
            echo " />";
            echo "<label for=\"genDiff" . ($n + 1) . "\">Show differences</label>";
        }
        echo "      </div>";
    }
    
    echo "  </div>";
    echo "</div>";
}

?>

<HTML>
    <HEAD>
        <TITLE><?php echo $title; ?></TITLE>
        <link type="text/css" rel="stylesheet" href="style/mainstyle.css" />
    </HEAD>
    <BODY>
        <?php include "menu.php"; ?>
        <?php include "translationModal.php"; ?>
        <DIV id="wrap">
            <?php include "header.php"; ?>
            <DIV id="innerwrap">
                <?php for ($n = 0; $n < count($translations); $n++) {
                    printBox($n);
                } ?>
            </DIV>
        </DIV>
        <script type="text/javascript">
            let translCount = <?php echo count($translations); ?>;
        </script>
        <script type="text/javascript" src="script/basescript.js"></script>
        <script type="text/javascript" src="script/script.js"></script>
    </BODY>
</HTML>