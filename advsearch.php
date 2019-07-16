<?php

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

include "dbconnect.php";

$db = getDbConnection();

$q = $db->query("Call GetSectionsAndSubsections();");
$ss = $q->fetch_all(MYSQLI_NUM);
do {} while ($db->next_result());

$q = $db->query("Call DocGroupList();");
$dg = $q->fetch_all(MYSQLI_NUM);
do {} while ($db->next_result());

$q = $db->query("Call DocGroupDetails();");
$dd = $q->fetch_all(MYSQLI_NUM);
do {} while ($db->next_result());

$q = $db->query("Call DocList();");
$dl = $q->fetch_all(MYSQLI_NUM);
do {} while ($db->next_result());

$q = $db->query("Call AllTranslations();");
$ts = $q->fetch_all(MYSQLI_NUM);
do {} while ($db->next_result());

?>

<HTML>
    <HEAD>
        <TITLE>Advanced Search</TITLE>
        <link type="text/css" rel="stylesheet" href="mainstyle.css" />
    </HEAD>
    <BODY>
        <?php include "menu.php"; ?>
        <DIV id="wrap">
            <H3 class="page">Advanced Search</H3>
            <DIV id="pagewrap">
                <form action="search.php" method="get"> <!-- maybe we shouldn't have this bit? Or have this for generics and a second form for submit? -->
                    <!-- quicksearch option? -->
                    
                    <!-- book filter/sorter -->
                    <input type="radio" name="docOpt" id="allDocs" checked="true" onclick="getBooks();" /><label for="allDocs">All documents</label>
                    <input type="radio" name="docOpt" id="group" onclick="getBooks();" />
                    <label for="group">
                        Documents by group:
                        <select name="docGroups" id="docGroups">
                            <option value="-1"></option>
                            <?php
                            for ($i = 0; $i < count($dg); $i++) {
                                echo "<option value='" . $dg[$i][0] . "'>" . $dg[$i][1] . "</option>";
                            }
                            ?>
                        </select>
                    </label><br />
                    <!-- book -->
                    <select id="book" name="book" onchange="updateBook();">
                        <option value="-1"></option>
                        <?php foreach ($dl as $d) {
                            echo "<option value='" . $d[0] . "'>" . $d[1] . "</option>";
                        } ?>
                    </select>
                    <!-- section/chapter -->
                    <select id="section" name ="section" onchange="updateSection();"></select> :
                    <!-- subsection/verse -->
                    <select id="opensub" name="opensub" onchange="updateSub();"></select> -
                    <!-- to subsection/verse -->
                    <select id="closesub" name="closesub"></select><br />
                    <!-- translations --> <!-- translation filter options -->
                    <select id="translation" name="translation" onchange="addTranslation(this);"></select>
                    <input type="radio" name="trOpt" id="all" checked="true" onclick="getTranslations();" /><label for="all">All translations</label>
                    <input type="radio" name="trOpt" id="origOnly" onclick="getTranslations();" /><label for="origOnly">Original language only</label>
                    <input type="radio" name="trOpt" id="pub" onclick="getTranslations();" />
                    <label for="pub">
                        Published 
                        <select id="pubCp" name="pubCp" onchange="cpChange(this);">
                            <option value="-2">before</option>
                            <option value="-1">before or during</option>
                            <option value="0">in the year</option>
                            <option value="1">during or after</option>
                            <option value="2">after</option>
                            <option value="3">between</option>
                        </select>
                        <input type="number" name="year" id="year" min="0" max="2017" step="1" data-tip="Entering year 0 will include all dates BC" onchange="yearUpdate();" />
                        <span id="betweenOnly" class="hidden">and <input type="number" name="year2" id="year2" min="0" max="2017" step="1" data-tip="Entering year 0 will include all dates BC" /></span>
                    </label>
                    <!-- translation list --><!-- update these when a book is chosen that does not apply -->
                </form>
            </DIV>
        </DIV>
        <!-- javascript -->
        <script type="text/javascript">
            var ss = new Array();
            <?php foreach ($ss as $i => $s) { ?>
                ss[<?php echo $i; ?>] = new Array();
                ss[<?php echo $i; ?>].push(<?php echo $s[0]; ?>);
                ss[<?php echo $i; ?>].push(<?php echo $s[1]; ?>);
                ss[<?php echo $i; ?>].push(<?php echo $s[2]; ?>);
            <?php } ?>
            
            var dg = new Array();
            <?php foreach ($dg as $i => $g) { ?>
                dg[<?php echo $i; ?>] = new Array();
                dg[<?php echo $i; ?>].push(<?php echo $g[0]; ?>);
                dg[<?php echo $i; ?>].push("<?php echo $g[1]; ?>");
            <?php } ?>
            
            var dd = new Array();
            <?php foreach ($dd as $i => $e) { ?>
                dd[<?php echo $i; ?>] = new Array();
                dd[<?php echo $i; ?>].push(<?php echo $e[0]; ?>);
                dd[<?php echo $i; ?>].push(<?php echo $e[1]; ?>);
                dd[<?php echo $i; ?>].push(<?php echo $e[2]; ?>);
            <?php } ?>
            
            var dl = new Array();
            <?php foreach ($dl as $i => $d) { ?>
                dl[<?php echo $i; ?>] = new Array();
                dl[<?php echo $i; ?>].push(<?php echo $d[0]; ?>);
                dl[<?php echo $i; ?>].push("<?php echo $d[1]; ?>");
                dl[<?php echo $i; ?>].push(<?php echo $d[2]; ?>);
            <?php } ?>
            
            var ts = new Array();
            <?php foreach ($ts as $i => $t) { ?>
                ts[<?php echo $i; ?>] = new Array();
                ts[<?php echo $i; ?>].push(<?php echo $t[0]; ?>);
                ts[<?php echo $i; ?>].push("<?php echo $t[1]; ?>");
                ts[<?php echo $i; ?>].push(<?php echo $t[2]; ?>);
                ts[<?php echo $i; ?>].push(<?php echo $t[3]; ?>);
                ts[<?php echo $i; ?>].push(new Date("<?php echo $t[4]; ?>"));
            <?php } ?>
            
            var book;
            var section;
            var sectionCount;
            var subCount;
            var startSub;
            
            function getIndexOf(haystack, needle, startIndex) {
                for (var i = startIndex; i < haystack.length; i++) {
                    if (haystack[i][0] == needle) {
                        return i;
                    }
                }
                return -1;
            }
            
            function getLastIndexOf(haystack, needle, startIndex) {
                var found = false;
                for (var i = startIndex; i < haystack.length; i++) {
                    if (!found) {
                        if (haystack[i][0] == needle) {
                            found = true;
                        }
                    } else {
                        if (haystack[i][0] != needle) {
                            return i - 1;
                        }
                    }
                }
                
                if (found) {
                    return i - 1;
                } else {
                    return -1;
                }
            }
            
            function getSubIndexOf(haystack, needle1, needle2, startIndex) {
                for (var i = startIndex; i < haystack.length; i++) {
                    if (haystack[i][0] == needle1) {
                        var j = i;
                        do {
                            if (haystack[j][1] == needle2) {
                                return j;
                            }
                        } while (haystack[++j][0] == needle1);
                        return -1;
                    }
                }
                return -1;
            }
            
            function getYear(date) {
                return date.getFullYear();
            }
            
            function getSections() {
                var lastSection = getLastIndexOf(ss, book, 0);
                sectionCount = ss[lastSection][1];
                var sectionSelector = document.getElementById("section");
                
                var blank = document.createElement("option");
                blank.value = -1;
                sectionSelector.appendChild(blank);
                
                for (var i = 1; i <= sectionCount; i++) {
                    var opt = document.createElement("option");
                    opt.value = i;
                    opt.innerHTML = i;
                    sectionSelector.appendChild(opt);
                }
            }
            
            function getSubs(closeOnly) {
                var subIndex = getSubIndexOf(ss, book, section, 0);
                subCount = ss[subIndex][2];
                var subSelector1 = document.getElementById("opensub");
                var subSelector2 = document.getElementById("closesub");
                
                for (var i = 1; i <= subCount; i++) {
                    var opt = document.createElement("option");
                    opt.value = i;
                    opt.innerHTML = i;
                    if (!closeOnly) subSelector1.appendChild(opt);
                    var opt2 = opt.cloneNode(true);
                    subSelector2.appendChild(opt2);
                }
            }
            
            function getTranslations() {
                var i;
                var bookIndex = getIndexOf(dl, book, 0);
                var origLang = dl[bookIndex][2];
                
                var origOnly = document.getElementById("origOnly").checked;
                var byPub = document.getElementById("pub").checked;
                
                var translations = document.getElementById("translation");
                if (translations.options.length > 0) {
                    var trValue = translations.options[translations.selectedIndex].value;
                    while (translations.options.length > 0) {
                        translations.options[0] = null;
                    }
                }
                
                var blank = document.createElement("option");
                blank.value = -1;
                translations.appendChild(blank);
                
                var useTranslation;
                var comparisonSelector = document.getElementById("pubCp");
                var comparison = parseInt(comparisonSelector.options[comparisonSelector.selectedIndex].value);
                var val1 = parseInt(document.getElementById("year").value);
                var val2 = parseInt(document.getElementById("year2").value);
                
                for (i = 0; i < ts.length; i++) {
                    useTranslation = 0;
                    if (ts[i][2] == book) {
                        if (origOnly) {
                            useTranslation = ts[i][3] == origLang;
                        } else if (byPub && ((comparison < 3 && val1 != "") || (comparison == 3 && val1 != "" && val2 != ""))) {
                            if (val1 < 1 && comparison <= 0) {
                                val1 = 1;
                                comparison = -1;
                            }
                            var checkVal = getYear(ts[i][4]);
                            switch(comparison) {
                                case -2:
                                    useTranslation = checkVal < val1;
                                    break;
                                case -1:
                                    useTranslation = checkVal <= val1;
                                    break;
                                case 0:
                                    useTranslation = checkVal == val1;
                                    break;
                                case 1:
                                    useTranslation = checkVal >= val1;
                                    break;
                                case 2:
                                    useTranslation = checkVal > val1;
                                    break;
                                case 3:
                                    useTranslation = checkVal > val1 && checkVal < val2;
                                    break;
                            }
                        } else {
                            useTranslation = 1;
                        }
                        
                        if (useTranslation) {
                            var opt = document.createElement("option");
                            opt.value = ts[i][0];
                            opt.innerHTML = ts[i][1];
                            translations.appendChild(opt);
                        }
                    }
                }
                
                for (i = 0; i < translations.options.length; i++) {
                    if (translations.options[i].value == trValue) {
                        translations.selectedIndex = i;
                        break;
                    }
                }
            }
            
            function getBooks() {
                
            }
            
            function updateBook() {
                var bookSelector = document.getElementById("book");
                book = bookSelector.options[bookSelector.selectedIndex].value;
                
                getSections();
                getTranslations();
            }
            
            function updateSection() {
                var sectionSelector = document.getElementById("section");
                section = sectionSelector.options[sectionSelector.selectedIndex].value;
                
                getSubs(0);
            }
            
            function updateSub() {
                var i;
                var closeSelector = document.getElementById("closesub");
                var closeValue = closeSelector.options[closeSelector.selectedIndex].value;
                while (closeSelector.options.length > 0) {
                    closeSelector.options[0] = null;
                }
                
                getSubs(1);
                
                var openSelector = document.getElementById("opensub");
                var openValue = openSelector.options[openSelector.selectedIndex].value;
                for (i = 1; i < openValue; i++) {
                    closeSelector.options[0] = null;
                }
                for (i = 0; i < closeSelector.options.length; i++) {
                    if (closeSelector.options[i].value == closeValue) {
                        closeSelector.selectedIndex = i;
                        break;
                    }
                }
            }
            
            function cpChange(el) {
                var bwO = document.getElementById("betweenOnly");
                if (el.options[el.selectedIndex].value == 3) {
                    bwO.classList.remove("hidden");
                } else {
                    if (!bwO.classList.contains("hidden"))
                        bwO.classList.add("hidden");
                }
                document.getElementById("pub").checked = true;
                
                var year1 = document.getElementById("year");
                var year2 = document.getElementById("year2");
                if (year1 != "" && el.options[el.selectedIndex].value != 3)
                    getTranslations();
                else if (year1 != "" && year2 != "" && el.options[el.selectedIndex].value == 3)
                    getTranslations();
            }
            
            function yearUpdate() {
                var year1 = document.getElementById("year");
                var year2 = document.getElementById("year2");
                
                if (parseInt(year1.value) != NaN) {
                    year2.min = year1.value;
                    document.getElementById("pub").checked = true;
                    var cp = document.getElementById("pubCp");
                    if (cp.options[cp.selectedIndex].value == 3 && year2.value == "") {
                        // do nothing
                    } else {
                        getTranslations();
                    }
                }
            }
        </script>
    </BODY>
</HTML>