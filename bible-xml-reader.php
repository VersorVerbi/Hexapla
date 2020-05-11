<?php

/**
 * Class BibleXMLReader
 */
abstract class BibleXMLReader extends XMLReader {

    /** @var array */
    protected $testResults;
    /** @var array */
    protected $conversions;
    /** @var HexaplaErrorLog */
    protected $errorLog;
    /** @var string */
    private $encoding;
    /** @var string */
    private $file;
    /** @var int */
    private $options;

    public function __construct($errorLogger) {
        $this->errorLog = $errorLogger;
    }

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
     *
     */
    protected function returnToStart() {
        $this->close();
        $this->open($this->file, $this->encoding, $this->options);
    }

    /**
     *
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
     * @param $testIndex
     * @param $ref1
     * @param $testType
     * @param $ref2
     * @param $row
     * @throws NoOppositeTypeException
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

class PositionException extends Exception {
    public function __construct($message = "", $code = 0, Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
    // code 1: not on a verse
    // code 2: on a verse element, but an ending one
}