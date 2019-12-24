<?php

include_once "osis-importer.php";
include_once "thml-importer.php";
include_once "usfx-importer.php";
include_once "usx-importer.php";
include_once "zefania-importer.php";
include_once "import-functions.php";
include_once "general-functions.php";

header('Content-type: text/html; charset=utf-8');
ini_set("default_charset", 'utf-8');
mb_internal_encoding('utf-8');

$memlimit = ini_get('memory_limit');
ini_set('memory_limit', '-1');
ini_set('max_execution_time', '3000');

$hexaData = new hexaText();

/* ***** XML ***** */
$sourceFile = "xml/engDRA_osis.xml"; // file path to upload?
$xmlParser = xml_parser_create();
xml_parser_set_option($xmlParser, XML_OPTION_TARGET_ENCODING, 'utf-8');
xml_parse_into_struct($xmlParser, implode("", file($sourceFile)), $values, $indices);
switch($values[0]['tag']) {
    // Open Scripture Information Standard (OSIS) --> http://crosswire.org/osis/OSIS%202.1.1%20User%20Manual%2006March2006.pdf
    case 'OSIS':
        osisImport($values, $indices, $hexaData);
        $values = null;
        $indices = null;
        unset($values);
        unset($indices);
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
echo $hexaData;

// load hexaData into the database

ini_set('memory_limit', $memlimit);


// apparently some TEI Bibles exist... do we want to deal with those?

/* ***** NON-XML ***** */
	// check errors using foreach (libxml_get_errors() as $error) $error->message
	
	// General Bible Format (GBF) --> https://ebible.org/bible/gbf.htm
	
	// Unified Standard Format Markers (USFM) --> https://ubsicap.github.io/usfm/about/index.html


class hexaVerseObject {
    /** @var string Source reference (e.g., "Gen3:12" or "Matthew 26:14") */
    private $reference;

    /**
     * hexaVerseObject constructor.
     * @param string $reference
     */
    public function __construct($reference) {
        $this->reference = $reference;
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
}

class hexaNote extends hexaVerseObject {
    /** @var string Text of the note on this reference */
    private $noteText;
    /** @var string|null Target reference */
    private $crossReference;

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
}

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
}

class hexaCopyright {
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
}

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
        $this->wordList[] = $word;
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
}