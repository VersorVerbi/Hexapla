<?php

header("content-type: text/html; charset=UTF-8");
ini_set('allow_url_fopen', 'on');

$files = new DirectoryIterator(getcwd() . "\\xml");

if (!$files->valid()) $files->next();

for(; $files->valid(); $files->next()) {
    if (!$files->isFile() || $files->getExtension() != "xml") {
        continue;
    }
    $xml = simplexml_load_file($files->getPath() . "\\" . $files->getFilename());
    
    $header = dom_import_simplexml($xml->teiHeader->fileDesc);
    $titles = $header->getElementsByTagName('title');
    $authors = $header->getElementsByTagName('author');

    $text = $xml->text->body->div->div;

    $file = fopen($titles[0]->nodeValue . ".csv", "w");

    foreach($text as $bookElement) {
        $bookNo = $bookElement['n'];
        foreach ($bookElement->div as $chapter) {
            $chapterNo = $chapter['n'];
            $sectionNo = $chapter->milestone['n'];
            $sectionText = "";
            $p = dom_import_simplexml($chapter->p);
            foreach($p->childNodes as $i => $child) {
                if ($child->nodeName != "milestone") {
                    $sectionText .= $child->nodeValue;
                } else {
                    echo "saved section $sectionNo in chapter $chapterNo<br />";
                    $line = array($bookNo, $chapterNo, $sectionNo, $sectionNo, ""/*translId*/, $sectionText);
                    fputcsv($file,$line);
                    $sectionText = "";
                    $sectionNo = $child->getAttribute('n');    
                }
                if ($i == ($p->childNodes->length - 1)) {
                    echo $i . "<br />";
                    $line = array($bookNo, $chapterNo, $sectionNo, $sectionNo, ""/*translId*/, $sectionText);
                    fputcsv($file,$line);
                }
            }
        }
    }

    fclose($file);
    
    break; // temporary: turn this off once we're sure things work
}

?>