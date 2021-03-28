<?php

use JetBrains\PhpStorm\Pure;

include_once "../sql-functions.php";
include_once "../general-functions.php";
include_once "import-functions.php";
include_once "../lib/portable-utf8.php";

/**
 * Class BibleXMLReader
 */
abstract class BibleXMLReader extends XMLReader {

    /** @var array $testResults This should be a pseudo-numeric associative array with keys as test record IDs from the database and values as true (if that test passed) or false (if it didn't) */
    protected array $testResults;
    /** @var array $conversions This is an associative array in the format $conversions[$reference] = $location_id */
    protected array $conversions;
    /** @var HexaplaErrorLog $errorLog A class for logging errors to a file for later review */
    public HexaplaErrorLog $errorLog;
    /** @var PerformanceLogger $perfLog A class for logging performance data to a file for later review */
    protected PerformanceLogger $perfLog;
    /** @var int */
    protected int $numberSystem;
    /** @var int */
    protected int $version;
    /** @var string */
    protected string $title;
    /** @var array $existingRows Associative array in the format $existingRows[$location_id][$position] = $text_id */
    protected array $existingRows;
    /** @var string|null */
    private ?string $encoding;
    /** @var string */
    private string $file;
    /** @var int */
    private int $options;
    /** @var array $conversionResults This should be a pseudo-numeric associative array with keys as conversion record IDs from the database and values as true (if that conversion applies) or false (if it doesn't) */
    private array $conversionResults = [];
    private bool $beganTransaction = false;
    private int|string $lastBook = -1;
    private int|string $lastChapter = -1;

    /**
     * @param string $URI
     * @param string|null $encoding
     * @param int $options
     * @return bool
     */
    public function openThis(string $URI, ?string $encoding = null, $options = 0): bool {
        $this->encoding = $encoding;
        $this->options = $options;
        $this->file = $URI;
        return $this->open($URI, $encoding, $options);
    }

    public function close($final = false): bool
    {
        if ($final) $this->perfLog->close();
        return parent::close();
    }

    public function set_errorLog($errorLogger) {
        $this->errorLog = $errorLogger;
        $this->perfLog->log();
    }

    public function set_perfLog($perfLog) {
        $this->perfLog = $perfLog;
        $this->perfLog->log("", true);
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
        $this->perfLog->log("start returnToStart");
        $this->close();
        $this->openThis($this->file, $this->encoding, $this->options);
        $this->perfLog->log("finish returnToStart");
    }

    /**
     * @param resource|null $db
     * @throws HexaplaException
     * @throws HexaplaException
     */
    protected function setUpConversions(&$db) {
        $this->perfLog->log("start setUpConversions");
        checkPgConnection($db);
        $conversionSearch = getData($db, HexaplaTables::LOC_CONV_USES_TEST);
        while (($row = pg_fetch_assoc($conversionSearch)) !== false) {
            if (!isset($this->conversionResults[$row[HexaplaLocConvUsesTest::CONVERSION_ID]])) {
                $this->conversionResults[$row[HexaplaLocConvUsesTest::CONVERSION_ID]] = true;
                $result = true;
            } else {
                $result = $this->conversionResults[$row[HexaplaLocConvUsesTest::CONVERSION_ID]];
            }
            if (pg_bool($row[HexaplaLocConvUsesTest::REVERSED])) {
                $result = $result && !$this->testCheck($row[HexaplaLocConvUsesTest::TEST_ID]);
            } else {
                $result = $result && $this->testCheck($row[HexaplaLocConvUsesTest::TEST_ID]);
            }
            $this->conversionResults[$row[HexaplaLocConvUsesTest::CONVERSION_ID]] = $result;
        }
        $conversionDetails = getData($db, HexaplaTables::LOC_CONVERSION);
        while (($row = pg_fetch_assoc($conversionDetails)) !== false) {
            if ($this->conversionCheck($row[HexaplaConversion::ID])) {
                $this->conversions[$row[HexaplaConversion::DISPLAY_NAME]] = $row[HexaplaConversion::LOCATION_ID];
            }
        }
        $this->perfLog->log("finish setUpConversions");
    }

    private function testCheck($testId) {
        if (isset($this->testResults[$testId])) {
            return $this->testResults[$testId];
        } else {
            return false;
        }
    }

    private function conversionCheck($convId) {
        if (isset($this->conversionResults[$convId])) {
            return $this->conversionResults[$convId];
        } else {
            return false;
        }
    }

    /**
     * @param resource|null $db
     * @throws HexaplaException
     * @throws HexaplaException
     */
    public function identifyNumberSystem(&$db) {
        // note: bitwise assignment operators work as expected with boolean values
        // but I acknowledge that it isn't exactly best practice
        $this->perfLog->log("start identifyNumberSystem");
        checkPgConnection($db);
        $allNs = getData($db, HexaplaTables::LOC_NUMBER_SYSTEM, [HexaplaNumberSystem::ID]);
        $columns[] = HexaplaNumSysUsesConv::CONVERSION_ID;
        while (($nsRow = pg_fetch_assoc($allNs)) !== false) {
            unset($criteria);
            $criteria[HexaplaNumSysUsesConv::NUMBER_SYSTEM_ID] = $nsRow[HexaplaNumberSystem::ID];
            $nsUses = getData($db, HexaplaTables::LOC_NUMSYS_USES_CONV, $columns, $criteria);
            while (($row = pg_fetch_assoc($nsUses)) !== false) {
                if (!$this->conversionResults[$row[HexaplaNumSysUsesConv::CONVERSION_ID]]) {
                    continue 2; // don't bother with this number system, since it can't match
                }
            }
            $countEqual = (num_true($this->conversionResults) === pg_num_rows($nsUses));
            try {
                if (!$countEqual) {
                    break;
                } elseif (isset($this->numberSystem)) {
                    throw new TooManyMatchesError("number system", 0, null, get_defined_vars());
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
            free($insert);
            foreach ($this->conversionResults as $cid => $isUsed) {
                if ($isUsed) {
                    $insertChunk[HexaplaNumSysUsesConv::NUMBER_SYSTEM_ID] = $this->numberSystem;
                    $insertChunk[HexaplaNumSysUsesConv::CONVERSION_ID] = $cid;
                    $insert[] = $insertChunk;
                }
            }
            putData($db, HexaplaTables::LOC_NUMSYS_USES_CONV, $insert, null);
        }
        free($criteria);
        free($insert);
        $criteria[HexaplaSourceVersion::ID] = $this->version;
        $insert[HexaplaSourceVersion::NUMBER_SYSTEM_ID] = $this->numberSystem;
        update($db, HexaplaTables::SOURCE_VERSION, $insert, $criteria);
        $this->perfLog->log("finish identifyNumberSystem");
    }

    /**
     * @param array $testIndex
     * @param string $ref1
     * @param int $testType
     * @param string $ref2
     * @param array $row Associative array of a row of results from the test table
     * @throws NoOppositeTypeException Indicates that the given test type has no reverse test type
     */
    protected function testComparisonToIndex(array &$testIndex, string $ref1, int $testType, string $ref2, array $row) {
        $testIndex[$ref1][$testType][$ref2] = [
            'id' => $row[HexaplaLocTest::ID],
            'multi1' => $row[HexaplaLocTest::MULTIPLIER_1],
            'multi2' => $row[HexaplaLocTest::MULTIPLIER_2]];
        $testIndex[$ref2][HexaplaTests::opposite($testType)][$ref1] = [
            'id' => $row[HexaplaLocTest::ID],
            'multi1' => $row[HexaplaLocTest::MULTIPLIER_2],
            'multi2' => $row[HexaplaLocTest::MULTIPLIER_1]];
    }

    /**
     * Meant to be used in a loop uploading verses. Side-effect: begins and commits transactions for each book.
     * @param $db
     * @param $ref
     */
    protected function beginCommitChapter($db, $ref) {
        getStandardizedReference($db, $ref, $book, $chapter);
        if ($book !== $this->lastBook || $chapter !== $this->lastChapter) {
            if ($this->beganTransaction) {
                commit($db);
                $this->beganTransaction = false;
            }
            $this->lastBook = $book;
            $this->lastChapter = $chapter;
            begin($db);
            $this->beganTransaction = true;
        }
    }

    protected function existingDataCheck($db) {
        $this->perfLog->log("begin existingDatacheck");
        checkPgConnection($db);
        $columns[] = HexaplaTextValue::ID;
        $columns[] = HexaplaTextValue::POSITION;
        $columns[] = HexaplaTextValue::LOCATION_ID;
        $criteria[HexaplaTextValue::VERSION_ID] = $this->version;
        $existingText = getData($db, HexaplaTables::TEXT_VALUE, $columns, $criteria);
        while (($row = pg_fetch_assoc($existingText)) !== false) {
            $this->existingRows[$row[HexaplaTextValue::LOCATION_ID]][$row[HexaplaTextValue::POSITION]] = $row[HexaplaTextValue::ID];
        }
        $this->perfLog->log("finish existingDataCheck");
    }
}

class HexaplaStandardMetadata {
    /** @var hexaName|null */
    private ?hexaName $title;
    /** @var hexaName|null */
    private ?hexaName $translation;
    /** @var hexaCopyright|null */
    private ?hexaCopyright $copyright;
    /** @var array Metadata of unclear origins or purpose */
    private array $metadata;
    /** @var hexaName|null */
    private ?hexaName $language;

    /**
     * hexaText constructor.
     */
    #[Pure] public function __construct() {
        $this->title = new hexaName();
        $this->translation = new hexaName();
        $this->language = new hexaName();
        $this->copyright = new hexaCopyright();
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
     * @param hexaName $newTranslation
     */
    public function setTranslation(hexaName $newTranslation) {
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
    public function setCopyright(hexaCopyright $newCopyright) {
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
        $criteria[HexaplaSourceVersion::LANGUAGE_ID] = 4; //$this->language; // TODO: We haven't resolved this anywhere
        $criteria[HexaplaSourceVersion::ALLOWS_ACTIONS] = HexaplaPermissions::READ + HexaplaPermissions::NOTE + HexaplaPermissions::FOCUS + HexaplaPermissions::DIFF; // TODO: handle when diffing isn't allowed
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
        $options = array_merge($options, $this->title->getAbbreviations());
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
class PositionException extends HexaplaException {
    /**
     * PositionException constructor.
     * @param string $message
     * @param int $code 1: Not on a verse; 2: On an ending verse element
     * @param Throwable|null $previous
     * @param array $locals
     */
    #[Pure] public function __construct($message = "", $code = 0, Throwable $previous = null, $locals = []) {
        parent::__construct($message, $code, $previous, $locals);
    }
}

/**
 * Class TooManyMatchesError
 */
class TooManyMatchesError extends HexaplaException {
    /**
     * TooManyMatchesError constructor.
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     * @param array $locals
     */
    #[Pure] public function __construct($message = "", $code = 0, Throwable $previous = null, $locals = []) {
        $message = "Too many $message matches!";
        parent::__construct($message, $code, $previous, $locals);
    }
}