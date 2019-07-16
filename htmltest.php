<?php

libxml_use_internal_errors(true);

$baseUrl = "http://www.thelatinlibrary.com";

$masterCurl = curl_init("$baseUrl/index.html");
curl_setopt($masterCurl, CURLOPT_RETURNTRANSFER, true);
$masterPage = curl_exec($masterCurl);
if ($masterPage === false) {
    echo curl_error($masterCurl);
    exit;
}
$masterDom = new DOMDocument();
$masterDom->loadHTML($masterPage);
$indexList = $masterDom->getElementsByTagName('option');
$count = 0;
$totalSize = 0;

foreach ($indexList as $fileNameOpt) {
    $fileName = $fileNameOpt->getAttribute('value');
    $author = trim($fileNameOpt->nodeValue);
    $indexCurl = curl_init("$baseUrl/$fileName");
    curl_setopt($indexCurl, CURLOPT_RETURNTRANSFER, true);
    $indexPage = curl_exec($indexCurl);
    
    $indexDom = new DOMDocument();
    $indexDom->loadHTML($indexPage);
    $pageList = $indexDom->getElementsByTagName('a');
    foreach ($pageList as $anchor) {
        $linksTo = $anchor->getAttribute('href');
        if (substr_count($linksTo, "/") > 0) {
            if ($count < 843) { // they cut me off here
                $count++;
                continue;
            }
            $pageCurl = curl_init("$baseUrl/$linksTo");
            curl_setopt($pageCurl, CURLOPT_RETURNTRANSFER, TRUE);
            $page = curl_exec($pageCurl);
            
            $dom = new DOMDocument();
            $dom->loadHTML($page);
            $pList = $dom->getElementsByTagName('p');
            $arr = [];
            $title = fillArrayWithTextAndReturnTitle($pList, $arr);
            if ($title == "") {
                $titleList = $dom->getElementsByTagName('h1');
                $title = $titleList->item(0)->nodeValue;
            }
            if ($title == "") {
                $titleList = $dom->getElementsByTagName('font');
                $title = $titleList->item(0)->nodeValue;
            }
            $title = LatinMixedCase($title);
            $title = trim($title);
            //echo "<h1>$title</h1><h2>by $author</h2><pre>";print_r($arr);echo"</pre>";
            $outFileName = $count . "-" . $title . "-" . $author . ".csv";
            $outFile = fopen($outFileName, "w");
            
            fputcsv($outFile, array($title));
            fputcsv($outFile, array($author));
            
            foreach($arr as $text) {
                $line = array(/*bookNum*/"", /*chapter*/"", /*sectionStart*/"", /*sectionEnd*/"", /*translation*/"", $text);
                fputcsv($outFile, $line);
            }
            
            $stats = fstat($outFile);
            $totalSize += $stats[7];
            $count++;
            fclose($outFile);
        }
        if ($totalSize >= 5242880) { //5 MB
            break 2;
        }
    }
}
echo "Loaded $count files";

function fillArrayWithTextAndReturnTitle($pList, &$arr) {
    foreach ($pList as $p) {
        if ($p->getAttribute('class') == NULL) {
            if ($p->firstChild->nodeName != 'table' && strlen($p->nodeValue) > 0) {
                $arr[] = $p->nodeValue;
            }
        } elseif ($p->getAttribute('class') == 'pagehead') {
            $title = $p->nodeValue;
        }
    }
    return $title;
}

function LatinMixedCase($str) {
    $commonWords = array('a','ab','ad','ante','circum','contra','cum','de','e','ex','extra',
                         'in','inter','intra','ob','per','post','prae','praeter','pro',
                         'propter','sine','sub','super','trans','versus','ac','at','atque',
                         'aut','et','nec','sed','vel'); // prepositions and coordinating conjunctions
    
    $str = strtolower($str);
    $split = explode(' ',$str);
    foreach ($split as $w => $word) {
        if (!in_array($word, $commonWords) || $w == 0) {
            $split[$w] = strtoupper(substr($word, 0, 1)) . substr($word, 1);
        }
    }
    
    $str = join(' ',$split);
    return $str;
}

?>