<?php

//include_once "osis-importer.php";
//include_once "thml-importer.php";
//include_once "usfx-importer.php";
//include_once "usx-importer.php";
//include_once "zefania-importer.php";
include_once "import-functions.php";
include_once "general-functions.php";
include_once "sql-functions.php";
include_once "user-functions.php";
include_once "osis-reader.php";

$DEBUG = true;
$VERBOSE = false;
$REPLACE = false;

header('Content-type: text/html; charset=utf-8');
ini_set("default_charset", 'utf-8');
mb_internal_encoding('utf-8');

//$memlimit = ini_get('memory_limit');
//ini_set('memory_limit', '-1');
//ini_set('max_execution_time', '30000');

$hexaData = new hexaText();

//TODO: Redo this with XMLReader instead of XMLParser

/* ***** XML ***** */
$sourceFile = "xml/engDRA_osis_1Cor.xml"; // file path to upload?
$initialReader = new XMLReader();
$initialReader->open($sourceFile);
$initialReader->read();
$firstTag = strtolower($initialReader->localName);
$initialReader->close();
try {
    switch ($firstTag) {
        case 'osis':
            $reader = new OSISReader();
            break;
        case 'thml':
            break;
        case 'xmlbible':
        case 'x':
            break;
        case 'usfx':
            break;
        case 'scripture':
            break;
        case 'usx':
            break;
        default:
            throw new TypeError('Not an accepted file format');
    }
} catch(TypeError $e) {
    echo $e->getMessage();
}
$reader->set_errorLog(new HexaplaErrorLog('hexaErrorLog.txt'));
$reader->open($sourceFile, 'utf-8',LIBXML_PARSEHUGE);
$reader->loadMetadata($db);
$reader->runTests($db);
$reader->identifyNumberSystem($db);
$reader->exportAndUpload($db);
$reader->close();
die(0);

/*
$xmlParser = xml_parser_create();
xml_parser_set_option($xmlParser, XML_OPTION_TARGET_ENCODING, 'utf-8');
xml_parse_into_struct($xmlParser, implode("", file($sourceFile)), $values, $indices);
switch($values[0]['tag']) {
    // Open Scripture Information Standard (OSIS) --> http://crosswire.org/osis/OSIS%202.1.1%20User%20Manual%2006March2006.pdf
    case 'OSIS':
        osisImport($values, $indices, $hexaData);
        free($values);
        free($indices);
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
}*/

// do stuff
//echo $hexaData;

// load hexaData into the database
$hexaData->upload();

ini_set('memory_limit', $memlimit);


// apparently some TEI Bibles exist... do we want to deal with those?

/* ***** NON-XML ***** */
	// check errors using foreach (libxml_get_errors() as $error) $error->message
	
	// General Bible Format (GBF) --> https://ebible.org/bible/gbf.htm
	
	// Unified Standard Format Markers (USFM) --> https://ubsicap.github.io/usfm/about/index.html


class hexaVerseObject {
    /** @var string Source reference (e.g., "Gen3:12" or "Matthew 26:14") */
    private $reference;
    /** @var int|string Unique location ID(s) for this reference object */
    private $locationId;

    /**
     * hexaVerseObject constructor.
     * @param string $reference
     */
    public function __construct($reference) {
        $this->reference = $reference;
    }

    public function setLocationId($newLoc) {
        $this->locationId = intval($newLoc);
    }

    public function getLocationId(): int {
        return $this->locationId;
    }

    public function getReference(): string {
        return $this->reference;
    }

    /**
     * @param array $criteria SQL criteria array with column names as keys and values as values
     */
    public function toCriteria(&$criteria): void {
        if (isset($this->locationId)) $criteria[HexaplaTextValue::LOCATION_ID] = $this->locationId;
        return;
    }
}

class hexaWord extends hexaVerseObject {
    /** @var int 0-indexed position in a verse */
    private $position;
    /** @var string Value of this word/text/punctuation */
    private $text;
    /** @var string Strong's Number */
    private $strongs;
    /** @var string Source lemma */
    private $lemma;

    /**
     * hexaWord constructor.
     * @param string $myRef
     * @param string $val
     * @param int $myPos
     * @param string $strongs
     * @param string $lemma
     */
    public function __construct($myRef, $val, $myPos, $strongs = '', $lemma = '') {
        $this->position = $myPos;
        $this->text = $val;
        $this->strongs = $strongs;
        $this->lemma = $lemma;
        parent::__construct($myRef);
    }

    public function getPosition(): int {
        return $this->position;
    }

    public function getText(): string {
        return $this->text;
    }

    public function getStrongs(): string {
        return $this->strongs;
    }

    public function getLemma(): string {
        return $this->lemma;
    }

    public function getTotalLength(): int {
        return strlen($this->text) + 1; // extra 1 for space
    }

    /**
     * @param array $criteria SQL criteria array with column names as keys and values as values
     */
    public function toCriteria(&$criteria): void {
        if (isset($this->position)) $criteria[HexaplaTextValue::POSITION] = $this->position;
        if (isset($this->text)) $criteria[HexaplaTextValue::VALUE] = $this->text;
        if (isset($this->strongs) && utf8_strlen($this->strongs) > 0) $criteria[HexaplaTextValue::STRONG_ID] = $this->strongs;
        if (!isset($criteria[HexaplaTextValue::PUNCTUATION])) $criteria[HexaplaTextValue::PUNCTUATION] = HexaplaPunctuation::NOT;
        // others should be taken care of elsewhere / already
        parent::toCriteria($criteria);
        return;
    }

    public function upload($db, $version, $punc = "NotPunctuation"): void {
        $criteria['location_id'] = $this->getLocationId();
        $criteria['position'] = $this->position;
        $criteria['version_id'] = $version;
        $results = getData($db, TEXT_VALUE_TABLE(), [], $criteria);
        $row = pg_fetch_assoc($results);
        if ($row !== false) {
            // TODO: handle updates instead of skipping
            return;
        }
        $encoding = iconv_get_encoding('ouput_encoding');
        $valArray['position'] = $this->position;
        $valArray['value'] = iconv($encoding, 'UTF-8', $this->text);
        $valArray['location_id'] = $this->getLocationId();
        $valArray['punctuation'] = $punc;
        if (strlen($this->strongs) > 0) $valArray['strong_id'] = $this->strongs;
        $valArray['version_id'] = $version;
        pg_insert($db, TEXT_VALUE_TABLE(), $valArray);
        if ($GLOBALS['DEBUG']) {
            if ($valArray['location_id'] === -1) {
                print_r($this);
                die(-1);
            }
        }
        return;
    }
}

class hexaPunctuation extends hexaWord {
    /** @var bool If true, follows the previous text exactly (no space before); if false, precedes the following
     *            text exactly (no space after). */
    private $endingPunctuation;

    /**
     * hexaPunctuation constructor.
     * @param string $myRef
     * @param string $val
     * @param int $myPos
     * @param string $strongs
     * @param string $lemma
     * @param bool $endingPunctuation
     */
    public function __construct($myRef, $val, $myPos, $strongs = '', $lemma = '', $endingPunctuation = true) {
        $this->endingPunctuation = $endingPunctuation;
        parent::__construct($myRef, $val, $myPos, $strongs, $lemma);
    }

    public function getEndingPunc(): bool {
        return $this->endingPunctuation;
    }

    public function getTotalLength(): int {
        return strlen($this->getText());
    }

    /**
     * @param array $criteria SQL criteria array with column names as keys and values as values
     */
    public function toCriteria(&$criteria): void {
        if (isset($this->endingPunctuation)) $criteria[HexaplaTextValue::PUNCTUATION] = ($this->endingPunctuation ? HexaplaPunctuation::CLOSING : HexaplaPunctuation::OPENING);
        parent::toCriteria($criteria);
        return;
    }

    public function upload($db, $version, $punc = "NotPunctuation"): void {
        $punc = ($this->endingPunctuation ? "Closing" : "Opening");
        parent::upload($db, $version, $punc);
        return;
    }
}

class hexaNote extends hexaVerseObject {
    /** @var string Text of the note on this reference */
    private $noteText;
    /** @var string|null Target reference */
    private $crossReference;
    /** @var int|string|null Target reference location ID(s) */
    private $crossRefId;

    /**
     * hexaNote constructor.
     * @param string $reference
     * @param string $text
     * @param string|null $crossReference
     */
    public function __construct($reference, $text = '', $crossReference = null) {
        $this->noteText = $text;
        $this->crossReference = $crossReference;
        parent::__construct($reference);
    }

    public function setText($newText) {
        $this->noteText = $newText;
    }

    public function setCrossRef($newRef) {
        $this->crossReference = $newRef;
    }

    public function getText(): string {
        return $this->noteText;
    }

    public function getCrossRef(): string {
        return $this->crossReference;
    }

    public function setCrossRefId($newRefId) {
        $this->crossRefId = $newRefId;
    }

    public function getCrossRefId() {
        return $this->crossRefId;
    }

    public function upload($db, $versionId): void {
        if (!is_null($this->crossReference)) {
            $valArray['loc_id'] = $this->getLocationId();
            $valArray['ref_id'] = $this->crossRefId;
            $valArray['version_id'] = $versionId;
            pg_insert($db, NOTE_CROSSREF_TABLE(), $valArray);
            free($valArray);
        }
        if (strlen($this->noteText) > 0) {
            $valArray['loc_id'] = $this->getLocationId();
            $valArray['note'] = $this->noteText;
            $valArray['version_id'] = $versionId;
            pg_insert($db, NOTE_TEXT_TABLE(), $valArray);
            free($valArray);
            return;
        }
    }
}

/**
 * Class hexaName
 */
class hexaName {
    /** @var string Official name of this thing */
    private $name;
    /** @var array List of string abbreviations or alternative names */
    private $abbreviationList;

    /**
     * hexaName constructor.
     * @param string $name Official name
     * @param array $abbrs Variable number of individual abbreviation/alternative name arguments
     */
    public function __construct($name = '', ...$abbrs) {
        $this->name = $name;
        $this->abbreviationList = $abbrs;
    }

    /**
     * @param string $newName
     */
    public function setName($newName) {
        $this->name = $newName;
    }

    /**
     * @param string $newAbbr
     */
    public function addAbbreviation($newAbbr) {
        $this->abbreviationList[] = $newAbbr;
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getAbbreviations(): array {
        return $this->abbreviationList;
    }
}

/**
 * Class hexaCopyright
 */
class hexaCopyright {
    const PUBLIC_DOMAIN = "Public domain";
    const COPYRIGHTED = "Copyright";
    /** @var int|null Current copyright year (not "original publication date," so we don't need to worry about BC) */
    private $year;
    /** @var string|null The name of the publishing company or person(s) */
    private $publisher;
    /** @var string|null textual representation of rights restrictions or availability */
    private $rights;
    /** @var string|null "Public domain" or "copyrighted," usually */
    private $type;

    /**
     * hexaCopyright constructor.
     */
    public function __construct() {
        $this->type = null;
        $this->year = null;
        $this->publisher = null;
        $this->rights = null;
    }

    /**
     * @param string $newType
     */
    public function setType($newType) {
        $this->type = $newType;
    }

    /**
     * @param int $newYear
     */
    public function setYear($newYear) {
        $this->year = $newYear;
    }

    /**
     * @param string $newRights
     */
    public function setRights($newRights) {
        $this->rights = $newRights;
    }

    /**
     * @param string $newPublisher
     */
    public function setPublisher($newPublisher) {
        $this->publisher = $newPublisher;
    }

    /**
     * @return string
     */
    public function getType(): string {
        return $this->type;
    }

    /**
     * @return int
     */
    public function getYear(): int {
        return $this->year;
    }

    /**
     * @return string
     */
    public function getRights(): string {
        return $this->rights;
    }

    /**
     * @return string
     */
    public function getPublisher(): string {
        return $this->publisher;
    }

    public function __toString(): string {
        $output = '';
        if ($this->type === $this::COPYRIGHTED) {
            $output .= utf8_chr(169); // copyright symbol U+00A9
            $output .= ' ' . $this->year . "\n";
        }
        $output .= $this->rights;
        return $output;
    }
}

/**
 * Class hexaText
 */
class hexaText {
    /** @var hexaName|null */
    private $title;
    /** @var hexaName|null */
    private $translation;
    /** @var hexaCopyright|null */
    private $copyright;
    /** @var array Array of hexaWord/hexaPunctuation objects */
    private $wordList;
    /** @var string Unique identification piece for this text (usually provided by the source) */
    private $uniqueId;
    /** @var array Metadata of unclear origins or purpose */
    private $metadata;
    /** @var array Array of hexaNote objects */
    private $nonCanonicalText;
    /** @var array Array of conversion test results in the format $testResults['testId'] = true || false */
    private $testResults;
    /** @var array Array of conversions used in the format $conversionsUsed['conversionId'] = true || false */
    private $conversionsUsed;
    /** @var array Array of number systems used to identify which, if any, match this struct */
    private $numberSystems;
    /** @var int ID of the number system used by this struct */
    private $officialNumberSystem;

    /**
     * hexaText constructor.
     */
    public function __construct() {
        $this->title = new hexaName();
        $this->translation = new hexaName();
        $this->copyright = new hexaCopyright();
        $this->wordList = [];
        $this->uniqueId = '';
        $this->metadata = [];
        $this->nonCanonicalText = [];
    }

    /**
     * Meant for printing debug data
     * @return string Closing <pre> tag
     */
    public function __toString() {
        echo "<pre>";
        print_r($this->title);
        print_r($this->translation);
        print_r($this->copyright);
        print_r($this->metadata);
        foreach($this->wordList as $word) {
            print_r($word);
        }
        foreach($this->nonCanonicalText as $note) {
            print_r($note);
        }
        print_r($this->metadata);
        return "</pre>";
    }

    /**
     * @param hexaWord $word A word to add to the list of words in this text
     */
    public function addWord($word) {
        getStandardizedReference($db, $word->getReference(), $book, $chapter, $verse);
        $this->wordList[$book][$chapter][$verse][] = $word;
    }

    /**
     * @param hexaNote $note A note to add to the list of notes in this text
     */
    public function addNote($note) {
        $this->nonCanonicalText[] = $note;
    }

    /**
     * @param string $metaname The name/key of this metadatum
     * @param string $metadatum The content of this metadatum
     */
    public function addMetadata($metaname, $metadatum) {
        $this->metadata[$metaname] = $metadatum;
    }

    /**
     * @param hexaName $newTitle
     */
    public function setTitle($newTitle) {
        $this->title = $newTitle;
    }

    /**
     * @param hexaName $newTranslation
     */
    public function setTranslation($newTranslation) {
        $this->translation = $newTranslation;
    }

    /**
     * @param hexaCopyright $newCopyright
     */
    public function setCopyright($newCopyright) {
        $this->copyright = $newCopyright;
    }

    /**
     * @param string $newId
     */
    public function setId($newId) {
        $this->uniqueId = $newId;
    }

    /**
     * @return hexaName
     */
    public function getTitle(): hexaName {
        return $this->title;
    }

    /**
     * @return hexaName
     */
    public function getTranslation(): hexaName {
        return $this->translation;
    }

    /**
     * @return hexaCopyright
     */
    public function getCopyright(): hexaCopyright {
        return $this->copyright;
    }

    /**
     * @return array
     */
    public function getWords(): array {
        return $this->wordList;
    }

    /**
     * @return string
     */
    public function getId(): string {
        return $this->uniqueId;
    }

    /**
     * @return array
     */
    public function getMetadata(): array {
        return $this->metadata;
    }

    /**
     * @return array
     */
    public function getNotes(): array {
        return $this->nonCanonicalText;
    }

    /**
     * @param resource $conversionUses Results of searching for all Conversion-uses-Tests data
     */
    private function discernConversions($conversionUses): void {
        while (($row = pg_fetch_assoc($conversionUses)) !== false) {
            $check = true;
            if (isset($this->conversionsUsed[$row['conversion_id']])) {
                $check = $this->conversionsUsed[$row['conversion_id']];
            }
            if (!$check) continue; // this will never be true again, so don't bother
            if ($row['reversed'] === 't') {
                $check = $check && !$this->testResults[$row['test_id']];
            } else {
                $check = $check && $this->testResults[$row['test_id']];
            }
            $this->conversionsUsed[$row['conversion_id']] = $check;
            if ($GLOBALS['DEBUG'] && $GLOBALS['VERBOSE']) {
                if ($row['conversion_id'] == 10176) {
                    echo 'Conversion Info! ';
                    print_r($row);
                    echo 'Current result: ';
                    print_r($this->conversionsUsed[$row['conversion_id']]);
                    echo "\n";
                }
            }
        }
        return;
    }

    /**
     * @param resource $nsData Results of searching for all Number-System-uses-Conversions data
     */
    private function identifyNumberSystem($nsData): void {
        $counts = [];
        while (($row = pg_fetch_assoc($nsData)) !== false) {
            if (!isset($this->numberSystems[$row[HexaplaNumSysUsesConv::NUMBER_SYSTEM_ID]])) {
                $check = true;
                $counts[$row[HexaplaNumSysUsesConv::NUMBER_SYSTEM_ID]] = 0;
            } else {
                $check = $this->numberSystems[$row[HexaplaNumSysUsesConv::NUMBER_SYSTEM_ID]];
            }
            $counts[$row[HexaplaNumSysUsesConv::NUMBER_SYSTEM_ID]] += 1;
            if (!$check) continue; // this will never be true again, so don't bother
            $check = $check && $this->conversionsUsed[$row[HexaplaNumSysUsesConv::CONVERSION_ID]];
            $this->numberSystems[$row[HexaplaNumSysUsesConv::NUMBER_SYSTEM_ID]] = $check;
        }
        $newConversions = [];
        foreach ($this->conversionsUsed as $convId => $convUsed) {
            if ($convUsed) {
                $newConversions[] = $convId;
            }
        }
        if ($GLOBALS['DEBUG'] && $GLOBALS['VERBOSE']) {
            echo 'Number System info! ';
            print_r($newConversions);
        }
        foreach ($this->numberSystems as $nsId => $isUsed) {
            if ($isUsed) {
                $criteria['ns_id'] = $nsId;
                //$associatedConversions = getCount($db, LOC_NS_USES_CONV_TABLE(), $criteria);
                $associatedConversions = $counts[$nsId];
                if ($associatedConversions == count($newConversions)) {
                    $this->officialNumberSystem = $nsId;
                    break;
                }
            }
        }
        if (!isset($this->officialNumberSystem)) {
            $needNew = false;
            foreach ($this->conversionsUsed as $convUsed) {
                if ($convUsed) {
                    $needNew = true;
                    break;
                }
            }
            if (!$needNew) {
                $this->officialNumberSystem = 1; // Standard
            } else {
                $db = getDbConnection();
                $insertArray['name'] = $this->title->getName();
                $newNsId = putData($db, LOC_NS_TABLE(), $insertArray);
                unset($insertArray);
                $insertArray['ns_id'] = $newNsId;
                foreach ($newConversions as $convId) {
                    $insertArray['conversion_id'] = $convId;
                    pg_insert($db, LOC_NS_USES_CONV_TABLE(), $insertArray);
                }
                $this->officialNumberSystem = $newNsId;
            }
        }
        return;
    }

    /**
     *
     */
    public function upload(): void {
        // TODO: If we're missing critical data, ask the user for it
        // Step 1: Identify number system
        //$this->evaluateTests(getData($db, LOC_CONV_TEST_TABLE()));
        //$this->discernConversions(getData($db, LOC_CONV_USES_TEST_TABLE()));
        //$this->identifyNumberSystem(getData($db, LOC_NS_USES_CONV_TABLE()));
        //if ($GLOBALS['DEBUG'] && $GLOBALS['VERBOSE']) echo 'Number System: ' . $this->officialNumberSystem . "\n";
        // Step 2: Upload source metadata
        $columns['id'] = true;
        $criteria['name'] = $this->copyright->getPublisher();
        $publisherSearch = getData($db, HexaplaTables::SOURCE_PUBLISHER, $columns, $criteria);
        $row = pg_fetch_assoc($publisherSearch);
        if ($row === false) {
            $publisherId = putData($db, HexaplaTables::SOURCE_PUBLISHER, $criteria);
        } else {
            $publisherId = $row['id'];
        }
        if ($GLOBALS['DEBUG'] && $GLOBALS['VERBOSE']) echo "Publisher ID: " . ($publisherId === false ? "false" : $publisherId) . "\n";

        $srcArray['publisher_id'] = $publisherId;
        $srcArray['lang_id'] = 1; // TODO: how do we know this?
        $srcArray['allows_actions'] = CAN_READ() + CAN_NOTE() + CAN_FOCUS() + CAN_DIFF(); // TODO: handle when diffing isn't allowed
        $srcArray['copyright'] = $this->copyright->getRights();
        $srcArray['user_id'] = CURRENT_USER(); // document owner/uploader
        $srcArray['source_id'] = 1;
        $versionId = putData($db, HexaplaTables::SOURCE_VERSION, $srcArray);
        if ($GLOBALS['DEBUG'] && $GLOBALS['VERBOSE']) echo "Version ID: " . ($versionId === false ? "false" : $versionId) . "\n";
        free($srcArray);
        free($columns);
        free($criteria);
        $srcArray['version_id'] = $versionId;
        $srcArray['term'] = $this->title->getName();
        pg_insert($db, HexaplaTables::SOURCE_VERSION_TERM, $srcArray);
        foreach ($this->title->getAbbreviations() as $abbr) {
            $srcArray['term'] = $abbr;
            pg_insert($db, HexaplaTables::SOURCE_VERSION_TERM, $srcArray);
        }
        free($srcArray);
        // Step 3: Identify location IDs & upload data
        $quickIndex = [];
        $conversions = [];
        foreach ($this->conversionsUsed as $convId => $isUsed) {
            if ($isUsed) {
                $conversions[] = $convId;
            }
        }
        $indexedConversions = getConversionsByDisplayRef($conversions);
        free($conversions);
        if ($GLOBALS['DEBUG'] && $GLOBALS['VERBOSE']) {
            echo "Indexed conversions: ";
            print_r($indexedConversions);
        }
        /** @var hexaWord $word */
        $i = 0;
        foreach ($this->wordList as $book => $bookContents) {
            foreach ($bookContents as $chapter => $chapterContents) {
                foreach ($chapterContents as $verse => $verseContents) {
                    foreach ($verseContents as $position => $word) {
                        $ref = getStandardizedReference($db, $word->getReference());
                        $word->setLocationId(locationWithIndex($db, $ref, $quickIndex, $indexedConversions));
                        if ($GLOBALS['DEBUG'] && $GLOBALS['VERBOSE']) {
                            if ($i % 100 === 0) {
                                print_r($word);
                            }
                        }
                        $word->upload($db, $versionId);
                        $i++;
                    }
                }
            }
        }
        free($this->wordList);
        /** @var hexaNote $note */
        foreach ($this->nonCanonicalText as $note) {
            $ref = getStandardizedReference($db, $word->getReference());
            $note->setLocationId(locationWithIndex($db, $ref, $quickIndex, $indexedConversions));
            $crossRef = $note->getCrossRef();
            $note->setCrossRefId(locationWithIndex($db, $crossRef, $quickIndex, $indexedConversions));
            $note->upload($db, $versionId);
        }
        free($this->nonCanonicalText);
        free($quickIndex);
        free($indexedConversions);
    }

    /**
     * @param resource $testData Results of searching for all Conversion Test data
     */
    private function evaluateTests($testData): void {
        while (($row = pg_fetch_assoc($testData)) !== false) {
            $repeatBecauseEsther = rowIsEsther($row);
            $reversedEstherOne = false;
            $reversedEstherTwo = false;
            $reversedBoth = false;
            do {
                if (isset($this->testResults[$row['id']]) && $this->testResults[$row['id']] === false && $repeatBecauseEsther) {
                    // this will only happen if we've already run the test once
                    // the first run was with both original names (e.g., Esther and Esther)
                    // the second run is with name 1 reversed and name 2 normal (e.g., Esther (Greek) and Esther)
                    // the third run is with name 1 reversed and name 2 reversed (e.g., Esther (Greek) and Esther (Greek))
                    // the fourth run is with name 1 normal and name 2 reversed (e.g., Esther and Esther (Greek))
                    // if ANY of these return true, the test is considered true
                    if (bookIsEsther($row['book1name']) && !$reversedEstherOne) {
                        $row['book1name'] = reverseEsther($row['book1name']);
                        $reversedEstherOne = true;
                    } elseif (bookIsEsther($row['book2name']) && !$reversedEstherTwo) {
                        $row['book2name'] = reverseEsther($row['book2name']);
                        $reversedEstherTwo = true;
                    } elseif (bookIsEsther($row['book1name']) && bookIsEsther($row['book2name']) && !$reversedBoth) {
                        $row['book1name'] = reverseEsther($row['book1name']);
                        $reversedBoth = true;
                    } else {
                        break;
                    }
                }
                $reverse = false;
                $greater = true;
                switch ($row['testtype']) {
                    case 'Last':
                        $this->testResults[$row['id']] =
                            ($this->lastVerse($row['book1name'], $row['chapter1num']) == $row['verse1num']);
                        break;
                    /** @noinspection PhpMissingBreakStatementInspection */
                    case 'NotExist':
                        $reverse = true;
                    case 'Exist':
                        $this->testResults[$row['id']] =
                            $this->verseExists($row['book1name'], $row['chapter1num'], $row['verse1num']);
                        if ($reverse) $this->testResults[$row['id']] = !$this->testResults[$row['id']];
                        break;
                    /** @noinspection PhpMissingBreakStatementInspection */
                    case 'LessThan':
                        $greater = false;
                    case 'GreaterThan':
                        $this->testResults[$row['id']] =
                            $this->lengthComparison($row['book1name'], $row['chapter1num'], $row['verse1num'],
                                $row['book2name'], $row['chapter2num'], $row['verse2num'], $greater,
                                $row['multiplier1'], $row['multiplier2']);
                        break;
                    default:
                        $this->testResults[$row['id']] = false;
                }
            } while ($repeatBecauseEsther && !$this->testResults[$row['id']]);
            if ($GLOBALS['DEBUG'] && $GLOBALS['VERBOSE']) {
                if (in_array($row['id'], array(250, 1085, 1086, 1219))) {
                    echo 'Test info! ';
                    print_r($row);
                    echo 'Test results: ';
                    print_r($this->testResults[$row['id']]);
                    echo "\n";
                }
            }
        }
        return;
    }

    /**
     * Returns the last verse (technically, the count of verses) in the given book & chapter combo
     * @param string $book The proper name of the book
     * @param int $chapter The chapter number
     * @return int The highest verse number in the chapter
     */
    private function lastVerse($book, $chapter): int {
        $maxVerse = -1;
        if (isset($this->wordList[$book])) {
            if (isset($this->wordList[$book][$chapter])) {
                foreach ($this->wordList[$book][$chapter] as $verse => $words) {
                    if ($verse > $maxVerse) {
                        $maxVerse = $verse;
                    }
                }
            }
        }
        return $maxVerse;
    }

    /**
     * Determines whether the given verse exists in this text
     * @param string $book The proper name of the book
     * @param int $chapter The chapter number
     * @param int $verse The verse number
     * @return bool True if the verse exists; false otherwise
     */
    private function verseExists($book, $chapter, $verse): bool {
        return isset($this->wordList[$book][$chapter][$verse]);
    }

    /**
     * Determines whether one verse is longer than another
     * @param string $book1 The proper name of the book of the first verse to compare
     * @param int $chapter1 The chapter number of the first verse to compare
     * @param int $verse1 The verse number of the first verse to compare
     * @param string $book2 The proper name of the book of the second verse to compare
     * @param int $chapter2 The chapter number of the second verse to compare
     * @param int $verse2 The verse number of the second verse to compare
     * @param bool $oneGreater Set to true if the first verse should be longer than the second; set to false for the reverse
     * @param float $oneMultiplier Length multiplier for the first verse
     * @param float $twoMultiplier Length multiplier for the second verse
     * @return bool True if both verses exist and the requested length comparison fits; false otherwise
     */
    private function lengthComparison(string $book1, int $chapter1, int $verse1,
                                     string $book2, int $chapter2, int $verse2,
                                     bool $oneGreater = true,
                                     float $oneMultiplier = 1, float $twoMultiplier = 1): bool {
        /** @var hexaWord $word1 */
        $words1 = $this->getWord($book1, $chapter1, $verse1);
        /** @var hexaWord $word2 */
        $words2 = $this->getWord($book2, $chapter2, $verse2);
        if (is_null($words1) || is_null($words2)) {
            return false;
        }
        $length1 = 0;
        $length2 = 0;
        foreach ($words1 as $word1) {
            $length1 += $word1->getTotalLength();
        }
        foreach ($words2 as $word2) {
            $length2 += $word2->getTotalLength();
        }
        $length1 *= $oneMultiplier;
        $length2 *= $twoMultiplier;
        if ($oneGreater) {
            return $length1 > $length2;
        } else {
            return $length2 > $length1;
        }
    }

    /**
     * @param $book
     * @param $chapter
     * @param $verse
     * @return hexaWord|array|null
     */
    private function getWord($book, $chapter, $verse, $position = -1) {
        if (isset($this->wordList[$book])) {
            if (isset($this->wordList[$book][$chapter])) {
                if (isset($this->wordList[$book][$chapter][$verse])) {
                    if ($position >= 0 && isset($this->wordList[$book][$chapter][$verse][$position])) {
                        return $this->wordList[$book][$chapter][$verse][$position]; // this specific word
                    } elseif ($position < 0) {
                        return $this->wordList[$book][$chapter][$verse]; // all words in this verse
                    }
                }
            }
        }
        return null; // word/verse does not exist
    }
}