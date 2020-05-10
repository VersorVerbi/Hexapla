<?php

/**
 * Class BibleXMLReader
 */
abstract class BibleXMLReader extends XMLReader {

    /** @var array */
    private $testResults;
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
    public function returnToStart() {
        $this->close();
        $this->open($this->file, $this->encoding, $this->options);
    }
}

class PositionException extends Exception {
    public function __construct($message = "", $code = 0, Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
    // code 1: not on a verse
    // code 2: on a verse element, but an ending one
}