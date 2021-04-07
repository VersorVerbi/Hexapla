<?php

namespace Hexapla;

//include_once "osis-importer.php";
//include_once "thml-importer.php";
//include_once "usfx-importer.php";
//include_once "usx-importer.php";
//include_once "zefania-importer.php";
use Exception, XMLReader, TypeError;

include_once "import-functions.php";
include_once "../general-functions.php";
include_once "../sql-functions.php";
include_once "osis-reader.php";
require_once "import-classes.php";

$DEBUG = true;
$VERBOSE = false;
$PERF_TESTING = true;

header('Content-type: text/html; charset=utf-8');
ini_set("default_charset", 'utf-8');
mb_internal_encoding('utf-8');

$memlimit = ini_get('memory_limit');
ini_set('memory_limit', '-1');
//ini_set('max_execution_time', '30000');

$hexaData = new hexaText();

/* ***** XML ***** */
$sourceFile = "../xml/VUC/latVUC_osis.xml"; // file path to upload?
$initialReader = new XMLReader();
$initialReader->open($sourceFile);
$initialReader->read();
$firstTag = strtolower($initialReader->localName);
$initialReader->close();
try {
    switch ($firstTag) {
        // Open Scripture Information Standard (OSIS) --> http://crosswire.org/osis/OSIS%202.1.1%20User%20Manual%2006March2006.pdf
        case 'osis':
            $reader = new OSISReader();
            $reader->formatStyle = OSIS_FORMAT_ENUM::WITH_OVERLAP;
            break;
        // Theological Markup Language (ThML) --> https://www.ccel.org/ThML/
        case 'thml':
            break;
        // Zefania --> http://bgfdb.de/zefaniaxml/bml/
        case 'xmlbible':
        case 'x':
            break;
        // Unified Scripture Format XML (USFX) --> https://ebible.org/usfx/usfx.htm
        case 'usfx':
            break;
        // XML Scripture Encoding Model (XSEM) --> https://scripts.sil.org/cms/scripts/page.php?site_id=nrsi&id=XSEM
        case 'scripture':
            break;
        // Unified Scripture XML (USX) --> https://ubsicap.github.io/usx/
        case 'usx':
            break;
        default:
            throw new TypeError('Not an accepted file format');
    }
} catch(TypeError $e) {
    echo $e->getMessage();
}
try {
    $reader->set_perfLog(new PerformanceLogger('hexaPerf.txt', $PERF_TESTING));
    $reader->set_errorLog(new HexaplaErrorLog('hexaErrorLog.txt'));
    $reader->openThis($sourceFile, 'utf-8', LIBXML_PARSEHUGE);
    $reader->runTests($db);
    $reader->loadMetadata($db);
    $reader->identifyNumberSystem($db);
    $reader->exportAndUpload($db);
} catch(HexaplaException $h) {
    $reader->errorLog->log($h);
} catch(Exception $e) {
    $reader->errorLog->log(HexaplaException::toHexaplaException($e));
} finally {
    $reader->close(true);
    ini_set('memory_limit', $memlimit);
}
die(0);

// apparently some TEI Bibles exist... do we want to deal with those?

/* ***** NON-XML ***** */
	// check errors using foreach (libxml_get_errors() as $error) $error->message
	
	// General Bible Format (GBF) --> https://ebible.org/bible/gbf.htm
	
	// Unified Standard Format Markers (USFM) --> https://ubsicap.github.io/usfm/about/index.html