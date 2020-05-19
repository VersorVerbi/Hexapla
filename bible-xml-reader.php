<?php

include_once "sql-functions.php";
include_once "general-functions.php";
include_once "import-functions.php";
include_once "lib/portable-utf8.php";

/**
 * Class BibleXMLReader
 */
abstract class BibleXMLReader extends XMLReader {

    /** @var array $testResults This should be a pseudo-numeric associative array with keys as test record IDs from the database and values as true (if that test passed) or false (if it didn't) */
    protected $testResults;
    /** @var array $conversions This should be a pseudo-numeric associative array with keys as conversion record IDs from the database and values as true (if that conversion applies) or false (if it doesn't) */
    protected $conversions;
    /** @var HexaplaErrorLog $errorLog A class for logging errors to a file for later review */
    protected $errorLog;
    /** @var int */
    protected $numberSystem;
    /** @var int */
    protected $version;
    /** @var string */
    protected $title;
    /** @var string */
    private $encoding;
    /** @var string */
    private $file;
    /** @var int */
    private $options;

    /**
     * @param string $URI
     * @param string|null $encoding
     * @param int $options
     * @return bool
     */
    public function open($URI, $encoding = null, $options = 0) {
        $this->encoding = $encoding;
        $this->options = $options;
        $this->file = $URI;
        return parent::open($URI, $encoding, $options);
    }

    public function set_errorLog($errorLogger) {
        $this->errorLog = $errorLogger;
    }

    /**
     * @param resource|null $db
     * @return void
     */
    abstract public function runTests(&$db);

    /**
     * @param resource|null $db
     * @return void
     */
    abstract public function exportAndUpload(&$db);

    /**
     * This function MUST set the protected $title and $version properties
     * @param resource|null $db
     * @return void
     */
    abstract public function loadMetadata(&$db);

    /**
     * This function returns the XMLReader to the beginning of the source XML
     */
    protected function returnToStart() {
        $this->close();
        $this->open($this->file, $this->encoding, $this->options);
    }

    /**
     * @param resource|null $db
     */
    protected function setUpConversions(&$db) {
        checkPgConnection($db);
        $conversionSearch = getData($db, HexaplaTables::LOC_CONV_USES_TEST);
        while (($row = pg_fetch_assoc($conversionSearch)) !== false) {
            if (!isset($this->conversions[$row[HexaplaLocConvUsesTest::CONVERSION_ID]])) {
                $this->conversions[$row[HexaplaLocConvUsesTest::CONVERSION_ID]] = true;
                $result = true;
            } else {
                $result = $this->conversions[$row[HexaplaLocConvUsesTest::CONVERSION_ID]];
            }
            if ($row[HexaplaLocConvUsesTest::REVERSED]) {
                $result = $result && !$this->testResults[$row[HexaplaLocConvUsesTest::TEST_ID]];
            } else {
                $result = $result && $this->testResults[$row[HexaplaLocConvUsesTest::TEST_ID]];
            }
            $this->conversions[$row[HexaplaLocConvUsesTest::CONVERSION_ID]] = $result;
        }
    }

    /**
     * @param resource|null $db
     */
    public function identifyNumberSystem(&$db) {
        // note: bitwise assignment operators work as expected with boolean values
        // but I acknowledge that it isn't exactly best practice
        checkPgConnection($db);
        $allNs = getData($db, HexaplaTables::LOC_NUMBER_SYSTEM, [HexaplaNumberSystem::ID]);
        $columns[] = HexaplaNumSysUsesConv::CONVERSION_ID;
        while (($nsRow = pg_fetch_assoc($allNs)) !== false) {
            unset($criteria);
            $criteria[HexaplaNumSysUsesConv::NUMBER_SYSTEM_ID] = $nsRow[HexaplaNumberSystem::ID];
            $nsUses = getData($db, HexaplaTables::LOC_NUMSYS_USES_CONV, $columns, $criteria);
            while (($row = pg_fetch_assoc($nsUses)) !== false) {
                if (!$this->conversions[$row[HexaplaNumSysUsesConv::CONVERSION_ID]]) {
                    continue 2; // don't bother with this number system, since it can't match
                }
            }
            $countEqual = (num_true($this->conversions) === pg_num_rows($nsUses));
            try {
                if (!$countEqual) {
                    break;
                } elseif (isset($this->numberSystem)) {
                    throw new TooManyMatchesError("number system");
                } else {
                    $this->numberSystem = $nsRow[HexaplaNumberSystem::ID];
                }
            } catch(TooManyMatchesError $e) {
                $this->errorLog->log($e);
                // this probably means we need to create our own
                // OR it means there's bad data in the database that needs to be dealt with manually
                break;
            }
        }
        if (!isset($this->numberSystem)) {
            // we need a new number system
            $insert[HexaplaNumberSystem::NAME] = $this->title; // this should have been set by the loadMetadata routine already
            $this->numberSystem = putData($db, HexaplaTables::LOC_NUMBER_SYSTEM, $insert);
        }
    }

    /**
     * @param array $testIndex
     * @param string $ref1
     * @param int $testType
     * @param string $ref2
     * @param array $row Associative array of a row of results from the test table
     * @throws NoOppositeTypeException Indicates that the given test type has no reverse test type
     */
    protected function testComparisonToIndex(&$testIndex, $ref1, $testType, $ref2, $row) {
        $testIndex[$ref1][$testType][$ref2] = [
            'id' => $row[HexaplaLocTest::ID],
            'multi1' => $row[HexaplaLocTest::MULTIPLIER_1],
            'multi2' => $row[HexaplaLocTest::MULTIPLIER_2]];
        $testIndex[$ref2][HexaplaTests::opposite($testType)][$ref1] = [
            'id' => $row[HexaplaLocTest::ID],
            'multi1' => $row[HexaplaLocTest::MULTIPLIER_2],
            'multi2' => $row[HexaplaLocTest::MULTIPLIER_1]];
    }
}

class HexaplaStandardMetadata {
    /** @var hexaName|null */
    private $title;
    /** @var hexaName|null */
    private $translation;
    /** @var hexaCopyright|null */
    private $copyright;
    /** @var array Metadata of unclear origins or purpose */
    private $metadata;
    /** @var hexaName|null */
    private $language;

    /**
     * hexaText constructor.
     */
    public function __construct() {
        $this->title = new hexaName();
        $this->translation = new hexaName();
        $this->language = new hexaName();
        $this->copyright = new hexaCopyright();
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
     * @param hexaName $newLanguage
     */
    public function setLanguage(hexaName $newLanguage) {
        $this->language = $newLanguage;
    }

    /**
     * @param hexaCopyright $newCopyright
     */
    public function setCopyright($newCopyright) {
        $this->copyright = $newCopyright;
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
     * @return hexaName
     */
    public function getLanguage(): hexaName {
        return $this->language;
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
    public function getMetadata(): array {
        return $this->metadata;
    }

    /**
     * @param resource|null $db
     * @return int Version ID
     */
    public function upload(&$db): int {
        checkPgConnection($db);
        // Publisher
        $columns[] = HexaplaSourcePublisher::ID;
        $criteria[HexaplaSourcePublisher::NAME] = $this->copyright->getPublisher();
        $check = getData($db, HexaplaTables::SOURCE_PUBLISHER, $columns, $criteria);
        if (($row = pg_fetch_assoc($check)) !== false) {
            $publisherId = $row[HexaplaSourcePublisher::ID];
        } else {
            $publisherId = putData($db, HexaplaTables::SOURCE_PUBLISHER, $criteria);
        }
        free($criteria);
        free($columns);

        // Version
        $columns[] = HexaplaSourceVersion::ID;
        $criteria[HexaplaSourceVersion::COPYRIGHT] = $this->copyright->__toString();
        $criteria[HexaplaSourceVersion::LANGUAGE_ID] = 1; //$this->language; // TODO: We haven't resolved this anywhere
        $criteria[HexaplaSourceVersion::ALLOWS_ACTIONS] = CAN_READ() + CAN_NOTE() + CAN_FOCUS() + CAN_DIFF(); // TODO: handle when diffing isn't allowed
        $criteria[HexaplaSourceVersion::PUBLISHER_ID] = $publisherId;
        $criteria[HexaplaSourceVersion::SOURCE_ID] = 1; // i.e., the Bible
        $check = getData($db, HexaplaTables::SOURCE_VERSION, $columns, $criteria);
        if (($row = pg_fetch_assoc($check)) !== false) {
            $versionId = $row[HexaplaSourceVersion::ID];
        } else {
            $criteria[HexaplaSourceVersion::USER_ID] = CURRENT_USER(); // i.e., uploader
            $versionId = putData($db, HexaplaTables::SOURCE_VERSION, $criteria);
        }
        free($criteria);
        free($columns);

        // Version Name
        $columns[] = HexaplaSourceVersionTerm::TERM;
        $options[] = $this->title->getName();
        array_merge($options, $this->title->getAbbreviations());
        $criteria[HexaplaSourceVersionTerm::TERM] = $options;
        $criteria[HexaplaSourceVersionTerm::VERSION_ID] = $versionId;
        $termData = getData($db, HexaplaTables::SOURCE_VERSION_TERM, $columns, $criteria);
        $currentTerms = [];
        while (($row = pg_fetch_assoc($termData)) !== false) {
            $currentTerms[] = $row[HexaplaSourceVersionTerm::TERM];
        }
        unset($criteria[HexaplaSourceVersionTerm::TERM]);
        if (count($currentTerms) === 0 || !in_array($this->title->getName(), $currentTerms)) {
            $criteria[HexaplaSourceVersionTerm::TERM] = $this->title->getName(); // TODO: Also need IS_PRIMARY here?
            putData($db, HexaplaTables::SOURCE_VERSION_TERM, $criteria);
        }
        foreach($this->title->getAbbreviations() as $abbreviation) {
            if (count($currentTerms) === 0 || !in_array($abbreviation, $currentTerms)) {
                $criteria[HexaplaSourceVersionTerm::TERM] = $abbreviation;
                putData($db, HexaplaTables::SOURCE_VERSION_TERM, $criteria);
            }
        }

        return $versionId;
    }
}

/**
 * Class PositionException
 */
class PositionException extends Exception {
    /**
     * PositionException constructor.
     * @param string $message
     * @param int $code 1: Not on a verse; 2: On an ending verse element
     * @param Throwable|null $previous
     */
    public function __construct($message = "", $code = 0, Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}

/**
 * Class TooManyMatchesError
 */
class TooManyMatchesError extends Exception {
    /**
     * TooManyMatchesError constructor.
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct($message = "", $code = 0, Throwable $previous = null) {
        $message = "Too many $message matches!";
        parent::__construct($message, $code, $previous);
    }
}