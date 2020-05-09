<?php

abstract class BibleXMLReader extends XMLReader {
    private $testResults;
    abstract public function runTests();
    abstract public function exportAndUpload();
}

class PositionException extends Exception {
    public function __construct($message = "", $code = 0, Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
    // code 1: not on a verse
    // code 2: on a verse element, but an ending one
}