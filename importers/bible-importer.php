<?php

//include_once "osis-importer.php";
//include_once "thml-importer.php";
//include_once "usfx-importer.php";
//include_once "usx-importer.php";
//include_once "zefania-importer.php";
use JetBrains\PhpStorm\Pure;

include_once "import-functions.php";
include_once "general-functions.php";
include_once "sql-functions.php";
include_once "user-functions.php";
include_once "osis-reader.php";

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
$sourceFile = "xml/OSMHB/osmhb.xml"; // file path to upload?
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
            $reader->formatStyle = OSIS_FORMAT_ENUM::SIMPLE_STRUCT;
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


class hexaVerseObject {
    /** @var string Source reference (e.g., "Gen3:12" or "Matthew 26:14") */
    private string $reference;
    /** @var int|string Unique location ID(s) for this reference object */
    private int|string $locationId;

    /**
     * hexaVerseObject constructor.
     * @param string $reference
     */
    public function __construct(string $reference) {
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
     * @param bool $forSearch True if only returning SEARCH criteria, not ALL -- but we don't use that here
     * @noinspection PhpUnusedParameterInspection
     */
    public function toCriteria(array &$criteria, $forSearch = false): void {
        if (isset($this->locationId)) $criteria[HexaplaTextValue::LOCATION_ID] = $this->locationId;
    }
}

class hexaWord extends hexaVerseObject {
    /** @var int 0-indexed position in a verse */
    private int $position;
    /** @var string Value of this word/text/punctuation */
    private string $text;
    /** @var string Strong's Number */
    private string $strongs;
    /** @var string Source lemma */
    private string $lemma;

    /**
     * hexaWord constructor.
     * @param string $myRef
     * @param string $val
     * @param int $myPos
     * @param string $strongs
     * @param string $lemma
     */
    #[Pure] public function __construct(string $myRef, string $val, int $myPos, $strongs = '', $lemma = '') {
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

    #[Pure] public function getTotalLength(): int {
        return strlen($this->text) + 1; // extra 1 for space
    }

    /**
     * @param array $criteria SQL criteria array with column names as keys and values as values
     * @param bool $forSearch True if we only want to return SEARCH variables, not ALL variables
     */
    public function toCriteria(array &$criteria, $forSearch = false): void {
        if (!$forSearch && !isset($criteria[HexaplaTextValue::PUNCTUATION])) $criteria[HexaplaTextValue::PUNCTUATION] = HexaplaPunctuation::NOT;
        if (isset($this->position)) $criteria[HexaplaTextValue::POSITION] = $this->position;
        if (!$forSearch) {
            if (isset($this->text)) $criteria[HexaplaTextValue::VALUE] = $this->text;
            if (isset($this->strongs) && utf8_strlen($this->strongs) > 0) $criteria[HexaplaTextStrongs::STRONG_ID] = $this->strongs;
        }
        // others should be taken care of elsewhere / already
        parent::toCriteria($criteria, $forSearch);
    }

    public function upload($db, $version, $punc = "NotPunctuation"): void {/*
        $criteria['location_id'] = $this->getLocationId();
        $criteria['position'] = $this->position;
        $criteria['version_id'] = $version;
        $results = getData($db, TEXT_VALUE_TABLE(), [], $criteria);
        $row = pg_fetch_assoc($results);
        if ($row !== false) {
            // handle updates instead of skipping
            return;
        }
        $encoding = iconv_get_encoding('ouput_encoding');
        $valArray[HexaplaTextValue::POSITION] = $this->position;
        $valArray[HexaplaTextValue::VALUE] = iconv($encoding, 'UTF-8', $this->text);
        $valArray[HexaplaTextValue::LOCATION_ID] = $this->getLocationId();
        $valArray[HexaplaTextValue::PUNCTUATION] = $punc;
        $valArray[HexaplaTextValue::VERSION_ID] = $version;
        pg_insert($db, TEXT_VALUE_TABLE(), $valArray);
        if ($GLOBALS['DEBUG']) {
            if ($valArray['location_id'] === -1) {
                print_r($this);
                die(-1);
            }
        }
        if (strlen($this->strongs) > 0) {

        }
        return;*/
    }
}

class hexaPunctuation extends hexaWord {
    /** @var bool If true, follows the previous text exactly (no space before); if false, precedes the following
     *            text exactly (no space after). */
    private bool $endingPunctuation;

    /**
     * hexaPunctuation constructor.
     * @param string $myRef
     * @param string $val
     * @param int $myPos
     * @param string $strongs
     * @param string $lemma
     * @param bool $endingPunctuation
     */
    #[Pure] public function __construct(string $myRef, string $val, int $myPos, $strongs = '', $lemma = '', $endingPunctuation = true) {
        $this->endingPunctuation = $endingPunctuation;
        parent::__construct($myRef, $val, $myPos, $strongs, $lemma);
    }

    public function getEndingPunc(): bool {
        return $this->endingPunctuation;
    }

    #[Pure] public function getTotalLength(): int {
        return strlen($this->getText());
    }

    /**
     * @param array $criteria SQL criteria array with column names as keys and values as values
     * @param bool $forSearch True if only returning search variables
     */
    public function toCriteria(array &$criteria, $forSearch = false): void {
        if (!$forSearch) {
            if (isset($this->endingPunctuation)) $criteria[HexaplaTextValue::PUNCTUATION] = ($this->endingPunctuation ? HexaplaPunctuation::CLOSING : HexaplaPunctuation::OPENING);
        }
        parent::toCriteria($criteria, $forSearch);
    }

    public function upload($db, $version, $punc = "NotPunctuation"): void {
        $punc = ($this->endingPunctuation ? "Closing" : "Opening");
        parent::upload($db, $version, $punc);
    }
}

class hexaNote extends hexaVerseObject {
    /** @var string Text of the note on this reference */
    private string $noteText;
    /** @var string|null Target reference */
    private ?string $crossReference;
    /** @var int|string|null Target reference location ID(s) */
    private null|int|string $crossRefId;

    /**
     * hexaNote constructor.
     * @param string $reference
     * @param string $text
     * @param null $crossReference
     */
    #[Pure] public function __construct(string $reference, $text = '', $crossReference = null) {
        $this->noteText = $text;
        $this->crossReference = $crossReference;
        parent::__construct($reference);
    }
}

/**
 * Class hexaName
 */
class hexaName {
    /** @var string Official name of this thing */
    private string $name;
    /** @var array List of string abbreviations or alternative names */
    private array $abbreviationList;

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
    // --Commented out by Inspection (12/12/2020 9:27 AM):const PUBLIC_DOMAIN = "Public domain";
    const COPYRIGHTED = "Copyright";
    /** @var int|null Current copyright year (not "original publication date," so we don't need to worry about BC) */
    private ?int $year;
    /** @var string|null The name of the publishing company or person(s) */
    private ?string $publisher;
    /** @var string|null textual representation of rights restrictions or availability */
    private ?string $rights;
    /** @var string|null "Public domain" or "copyrighted," usually */
    private ?string $type;

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
     * @param string $newRights
     */
    public function setRights(string $newRights) {
        $this->rights = $newRights;
    }

    /**
     * @param string $newPublisher
     */
    public function setPublisher(string $newPublisher) {
        $this->publisher = $newPublisher;
    }

    /**
     * @return string|null
     */
    public function getPublisher(): ?string
    {
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
    private ?hexaName $title;
    /** @var hexaName|null */
    private ?hexaName $translation;
    /** @var hexaCopyright|null */
    private ?hexaCopyright $copyright;
    /** @var array Array of hexaWord/hexaPunctuation objects */
    private array $wordList;
    /** @var string Unique identification piece for this text (usually provided by the source) */
    private string $uniqueId;
    /** @var array Metadata of unclear origins or purpose */
    private array $metadata;
    /** @var array Array of hexaNote objects */
    private array $nonCanonicalText;
    /** @var array Array of conversion test results in the format $testResults['testId'] = true || false */
    private array $testResults;
    /** @var array Array of conversions used in the format $conversionsUsed['conversionId'] = true || false */
    private array $conversionsUsed;
    /** @var array Array of number systems used to identify which, if any, match this struct */
    private array $numberSystems;
    /** @var int ID of the number system used by this struct */
    private int $officialNumberSystem;

    /**
     * hexaText constructor.
     */
    #[Pure] public function __construct() {
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
    public function __toString(): string
    {
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
    public function addWord(hexaWord $word) {
        getStandardizedReference($db, $word->getReference(), $book, $chapter, $verse);
        $this->wordList[$book][$chapter][$verse][] = $word;
    }

    /**
     * @param hexaNote $note A note to add to the list of notes in this text
     */
    public function addNote(hexaNote $note) {
        $this->nonCanonicalText[] = $note;
    }

    /**
     * @param string $metaname The name/key of this metadatum
     * @param string $metadatum The content of this metadatum
     */
    public function addMetadata(string $metaname, string $metadatum) {
        $this->metadata[$metaname] = $metadatum;
    }

    /**
     * @param hexaName $newTitle
     */
    public function setTitle(hexaName $newTitle) {
        $this->title = $newTitle;
    }

    /**
     * @param hexaCopyright $newCopyright
     */
    public function setCopyright(hexaCopyright $newCopyright) {
        $this->copyright = $newCopyright;
    }
}