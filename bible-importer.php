<?php

include_once "osis-importer.php";
include_once "thml-importer.php";
include_once "usfx-importer.php";
include_once "usx-importer.php";
include_once "zefania-importer.php";
include_once "import-functions.php";

/* ***** OUR DATA STRUCTURE FOR SAVING TO SQL ***** */
/*
wholeText									= []
	...["translation"] 						= Full name of translation
	...["translAbbrs"] 						= [list of abbreviation(s) of translation]
	...["uniqueId"]							= unique ID as given by XML document
	...["copyright"]						= []
		...["short"]						= short-form copyright info
		...["long"]							= long-form copyright info
		...["year"]							= copyright year
		...["owner"]						= copyright-holder
		...["rights"]						= usage rights (as best as we can determine)
	...["non-text"]							= [list of descriptions, et al, outside the text itself]
	...["numbering_system"]					= numbering system for uniquely identifying verses later
	...["books"]							= [list of {book_name} elements]
		...[{book_name}]					= []
			...["abbrs"]					= [list of abbreviation(s) of book name]
			...["non-text"]					= [list of descriptions, introductions, etc., outside the text itself]
			...["verses"]					= [list of {verse_num} elements]
				...[{verse_number}]			= []
					...["reference"]		= chapter number, verse number
					...[{word_number}]		= []
						...["value"]		= the word
						...["comparable"]	= lowercase-only version of the word
						...["strong"]		= the associated Strong's Concordance ID
						...["uniqueId"]		= the associated unique identification number, if not Strong's
						...["is_bef_punc"]	= whether or not the "word" is precedent punctuation (e.g., quote before word)
						...["is_aft_punc"]	= whether or not the "word" is subsequent punctuation (e.g., comma)
*/

/* ***** XML ***** */
$memlimit = ini_get('memory_limit');
ini_set('memory_limit', '1200M');

$allVerses = [];
$allNotes = [];
$metadata = [];
$hexaData = [];

$sourceFile = "xml/eng-rv_osis.xml"; // file path to upload?
$xmlParser = xml_parser_create();
xml_parse_into_struct($xmlParser, implode("", file($sourceFile)), $values, $indices);
switch($values[0]['tag']) {
    // Open Scripture Information Standard (OSIS) --> http://crosswire.org/osis/OSIS%202.1.1%20User%20Manual%2006March2006.pdf
    case 'OSIS':
        osisImport($values, $indices);
        osis2hexa();
        break;
    // Theological Markup Language (ThML) --> https://www.ccel.org/ThML/
    case 'THML':
        thmlImport($values, $indices);
        break;
    // Zefania --> http://bgfdb.de/zefaniaxml/bml/
    case 'XMLBIBLE':
    case 'X':
        zefaniaImport($values, $indices);
        break;
    // Unified Scripture Format XML (USFX) --> https://ebible.org/usfx/usfx.htm
    case 'USFX':
        usfxImport($values, $indices);
        break;
    // XML Scripture Encoding Model (XSEM) --> https://scripts.sil.org/cms/scripts/page.php?site_id=nrsi&id=XSEM
    case 'SCRIPTURE':
        xsemImport($values, $indices);
        break;
    // Unified Scripture XML (USX) --> https://ubsicap.github.io/usx/
    case 'USX':
        usxImport($values, $indices);
        break;
    default:
        echo "Error! Not an accepted file format.";
}
// do stuff
//print_r($allVerses);
print_r($hexaData);

// load hexaData into the database

ini_set('memory_limit', $memlimit);


// apparently some TEI Bibles exist... do we want to deal with those?

/* ***** NON-XML ***** */
	// check errors using foreach (libxml_get_errors() as $error) $error->message
	
	// General Bible Format (GBF) --> https://ebible.org/bible/gbf.htm
	
	// Unified Standard Format Markers (USFM) --> https://ubsicap.github.io/usfm/about/index.html
