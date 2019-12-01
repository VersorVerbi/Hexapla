<?php

include "dbconnect.php";

$db = getDbConnection();

$q = pg_query($db, "Call GetSectionsAndSubsections();");
$ss = pg_fetch_all($q, PGSQL_NUM);

$q = pg_query($db, "Call DocGroupList();");
$dg = pg_fetch_all($q, PGSQL_NUM);

$q = pg_query($db, "Call DocGroupDetails();");
$dd = pg_fetch_all($q, PGSQL_NUM);

$q = pg_query($db, "Call DocList();");
$dl = pg_fetch_all($q, PGSQL_NUM);

$q = pg_query($db, "Call AllTranslations();");
$ts = pg_fetch_all($q, PGSQL_NUM);

?>

<HTML lang="en">
    <HEAD>
        <TITLE>Advanced Search</TITLE>
        <link type="text/css" rel="stylesheet" href="style/mainstyle.css" />
    </HEAD>
    <BODY>
        <?php include "menu.php"; ?>
        <DIV id="wrap">
            <H3 class="page">Advanced Search</H3>
            <DIV id="pagewrap">
                <form action="search.php" method="get"> <!-- maybe we shouldn't have this bit? Or have this for generics and a second form for submit? -->
                    <!-- quicksearch option? -->
                    
                    <!-- book filter/sorter -->
                    <input type="radio" name="docOpt" id="allDocs" checked="checked" onclick="getBooks();" /><label for="allDocs">All documents</label>
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
            let ss = [];
            <?php foreach ($ss as $i => $s) { ?>
                ss[<?php echo $i; ?>] = [];
                ss[<?php echo $i; ?>].push(<?php echo $s[0]; ?>);
                ss[<?php echo $i; ?>].push(<?php echo $s[1]; ?>);
                ss[<?php echo $i; ?>].push(<?php echo $s[2]; ?>);
            <?php } ?>
            
            let dg = [];
            <?php foreach ($dg as $i => $g) { ?>
                dg[<?php echo $i; ?>] = [];
                dg[<?php echo $i; ?>].push(<?php echo $g[0]; ?>);
                dg[<?php echo $i; ?>].push("<?php echo $g[1]; ?>");
            <?php } ?>
            
            let dd = [];
            <?php foreach ($dd as $i => $e) { ?>
                dd[<?php echo $i; ?>] = [];
                dd[<?php echo $i; ?>].push(<?php echo $e[0]; ?>);
                dd[<?php echo $i; ?>].push(<?php echo $e[1]; ?>);
                dd[<?php echo $i; ?>].push(<?php echo $e[2]; ?>);
            <?php } ?>
            
            let dl = [];
            <?php foreach ($dl as $i => $d) { ?>
                dl[<?php echo $i; ?>] = [];
                dl[<?php echo $i; ?>].push(<?php echo $d[0]; ?>);
                dl[<?php echo $i; ?>].push("<?php echo $d[1]; ?>");
                dl[<?php echo $i; ?>].push(<?php echo $d[2]; ?>);
            <?php } ?>
            
            let ts = [];
            <?php foreach ($ts as $i => $t) { ?>
                ts[<?php echo $i; ?>] = [];
                ts[<?php echo $i; ?>].push(<?php echo $t[0]; ?>);
                ts[<?php echo $i; ?>].push("<?php echo $t[1]; ?>");
                ts[<?php echo $i; ?>].push(<?php echo $t[2]; ?>);
                ts[<?php echo $i; ?>].push(<?php echo $t[3]; ?>);
                ts[<?php echo $i; ?>].push(new Date("<?php echo $t[4]; ?>"));
            <?php } ?>
            
            let book;
            let section;
            let sectionCount;
            let subCount;
            let startSub;
            
            function getIndexOf(haystack, needle, startIndex) {
                for (let i = startIndex; i < haystack.length; i++) {
                    if (haystack[i][0] == needle) {
                        return i;
                    }
                }
                return -1;
            }
            
            function getLastIndexOf(haystack, needle, startIndex) {
                let found = false;
                for (let i = startIndex; i < haystack.length; i++) {
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
                for (let i = startIndex; i < haystack.length; i++) {
                    if (haystack[i][0] == needle1) {
                        let j = i;
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
                let lastSection = getLastIndexOf(ss, book, 0);
                sectionCount = ss[lastSection][1];
                let sectionSelector = document.getElementById("section");
                
                let blank = document.createElement("option");
                blank.value = -1;
                sectionSelector.appendChild(blank);
                
                for (let i = 1; i <= sectionCount; i++) {
                    let opt = document.createElement("option");
                    opt.value = i;
                    opt.innerHTML = i.toString();
                    sectionSelector.appendChild(opt);
                }
            }
            
            function getSubs(closeOnly) {
                let subIndex = getSubIndexOf(ss, book, section, 0);
                subCount = ss[subIndex][2];
                let subSelector1 = document.getElementById("opensub");
                let subSelector2 = document.getElementById("closesub");
                
                for (let i = 1; i <= subCount; i++) {
                    let opt = document.createElement("option");
                    opt.value = i;
                    opt.innerHTML = i;
                    if (!closeOnly) subSelector1.appendChild(opt);
                    let opt2 = opt.cloneNode(true);
                    subSelector2.appendChild(opt2);
                }
            }
            
            function getTranslations() {
                let i;
                let bookIndex = getIndexOf(dl, book, 0);
                let origLang = dl[bookIndex][2];
                
                let origOnly = document.getElementById("origOnly").checked;
                let byPub = document.getElementById("pub").checked;
                
                let translations = document.getElementById("translation");
                if (translations.options.length > 0) {
                    let trValue = translations.options[translations.selectedIndex].value;
                    while (translations.options.length > 0) {
                        translations.options[0] = null;
                    }
                }
                
                let blank = document.createElement("option");
                blank.value = -1;
                translations.appendChild(blank);
                
                let useTranslation;
                let comparisonSelector = document.getElementById("pubCp");
                let comparison = parseInt(comparisonSelector.options[comparisonSelector.selectedIndex].value);
                let val1 = parseInt(document.getElementById("year").value);
                let val2 = parseInt(document.getElementById("year2").value);
                
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
                            let checkVal = getYear(ts[i][4]);
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
                            let opt = document.createElement("option");
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
                let bookSelector = document.getElementById("book");
                book = bookSelector.options[bookSelector.selectedIndex].value;
                
                getSections();
                getTranslations();
            }
            
            function updateSection() {
                let sectionSelector = document.getElementById("section");
                section = sectionSelector.options[sectionSelector.selectedIndex].value;
                
                getSubs(0);
            }
            
            function updateSub() {
                let i;
                let closeSelector = document.getElementById("closesub");
                let closeValue = closeSelector.options[closeSelector.selectedIndex].value;
                while (closeSelector.options.length > 0) {
                    closeSelector.options[0] = null;
                }
                
                getSubs(1);
                
                let openSelector = document.getElementById("opensub");
                let openValue = openSelector.options[openSelector.selectedIndex].value;
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
                let bwO = document.getElementById("betweenOnly");
                if (el.options[el.selectedIndex].value == 3) {
                    bwO.classList.remove("hidden");
                } else {
                    if (!bwO.classList.contains("hidden"))
                        bwO.classList.add("hidden");
                }
                document.getElementById("pub").checked = true;
                
                let year1 = document.getElementById("year");
                let year2 = document.getElementById("year2");
                if (year1 != "" && el.options[el.selectedIndex].value != 3)
                    getTranslations();
                else if (year1 != "" && year2 != "" && el.options[el.selectedIndex].value == 3)
                    getTranslations();
            }
            
            function yearUpdate() {
                let year1 = document.getElementById("year");
                let year2 = document.getElementById("year2");
                
                if (parseInt(year1.value) != NaN) {
                    year2.min = year1.value;
                    document.getElementById("pub").checked = true;
                    let cp = document.getElementById("pubCp");
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