<?php


class OSISReader extends BibleXMLReader {
    /**
     * Override
     * @param bool $skipLineBreaks
     * @param int $targetNodeType
     * @param string $targetDivType
     * @param string $targetAttributeExists
     * @return bool|void
     */
    public function read($skipLineBreaks = false, $targetNodeType = -1, $targetDivType = '', $targetAttributeExists = '') {
        if (!$skipLineBreaks && $targetNodeType < 0 && strlen($targetDivType) == 0) {
            parent::read();
            return;
        }
        do {
            parent::read();
            $done = true;
            if ($skipLineBreaks && preg_match('/^[\r\n]+$/',$this->value) > 0) {
                $done = false;
            } elseif ($targetNodeType >= 0 && $this->nodeType !== $targetNodeType) {
                $done = false;
            } elseif (strlen($targetDivType) > 0 && $this->divType() !== $targetDivType) {
                $done = false;
            } elseif (strlen($targetAttributeExists) > 0 && is_null($this->getAttribute($targetAttributeExists))) {
                $done = false;
            }
        } while (!$done);
    }

    /**
     * @param resource|null $db
     */
    public function runTests(&$db) {
        checkPgConnection($db);
        $testSearch = getData($db, HexaplaTables::LOC_TEST);
        $testIndex = [];
        // create the test index
        while ($row = pg_fetch_assoc($testSearch) !== false) {
            $ref1 = getStandardizedReference($db,
                $row[HexaplaLocTest::BOOK_1_NAME] . ' ' .
                $row[HexaplaLocTest::CHAPTER_1_NUM] . ':' .
                $row[HexaplaLocTest::VERSE_1_NUM]);
            if (!is_null($row[HexaplaLocTest::BOOK_2_NAME])) {
                $ref2 = getStandardizedReference($db,
                    $row[HexaplaLocTest::BOOK_2_NAME] . ' ' .
                    $row[HexaplaLocTest::CHAPTER_2_NUM] . ':' .
                    $row[HexaplaLocTest::VERSE_2_NUM]);
            }
            try {
                switch ($testType = $row[HexaplaLocTest::TEST_TYPE]) {
                    case HexaplaTests::LAST:
                    case HexaplaTests::NOT_EXIST:
                    case HexaplaTests::EXIST:
                        $testIndex[$ref1][$testType] = $row[HexaplaLocTest::ID];
                        break;
                    case HexaplaTests::LESS_THAN:
                    case HexaplaTests::GREATER_THAN:
                        $this->testComparisonToIndex($testIndex, $ref1, $testType, $ref2, $row);
                        break;
                    default:
                        throw new InvalidArgumentException('Broken test');
                        break;
                }
            } catch(InvalidArgumentException $e) {
                continue;
            } catch(NoOppositeTypeException $n) {
                continue;
            }
        }
        // use the test index to load necessary test data
        $testData = [];
        while ($this->read(false, 'verse', '', 'sID')) {
            $ref1 = getStandardizedReference($db, $this->getAttribute('osisID'), $book, $chapter, $verse);
            if (isset($testIndex[$ref1])) {
                foreach ($testIndex[$ref1] as $testType => $test) {
                    switch ($testType) {
                        case HexaplaTests::LAST:
                            if (!isset($testData[$book][$chapter]['last']) || $verse > $testData[$book][$chapter]['last']) {
                                $testData[$book][$chapter]['last'] = $verse;
                            }
                            break;
                        case HexaplaTests::EXIST:
                        case HexaplaTests::NOT_EXIST:
                            $testData[$ref1]['exist'] = true;
                            break;
                        case HexaplaTests::GREATER_THAN:
                        case HexaplaTests::LESS_THAN:
                            try {
                                $testData[$ref1]['length'] = mb_strlen($this->currentVerse()['text']);
                            } catch(PositionException $e) {
                                // this should never happen because of preceding code, so break visibly
                                $this->errorLog->log($e);
                                die($e->getMessage());
                            }
                            break;
                        default:
                            // do nothing
                            break;
                    }
                }
            }
        }
        // now that test data is loaded, run the tests
        foreach ($testIndex as $ref1 => $test) {
            foreach ($test as $testType => $testStep) {
                $greater = false;
                switch ($testType) {
                    case HexaplaTests::LAST:
                        getStandardizedReference($db, $ref1, $book, $chapter, $verse);
                        $this->testResults[$testStep] = ($testData[$book][$chapter] === $verse);
                        break;
                    case HexaplaTests::NOT_EXIST:
                        $this->testResults[$testStep] = !$testData[$ref1]['exists'];
                        break;
                    case HexaplaTests::EXIST:
                        $this->testResults[$testStep] = $testData[$ref1]['exists'];
                        break;
                    /** @noinspection PhpMissingBreakStatementInspection */
                    case HexaplaTests::GREATER_THAN:
                        $greater = true;
                    case HexaplaTests::LESS_THAN:
                        foreach($testStep as $ref2 => $testArray) {
                            if (!isset($this->testResults[$testArray['id']])) {
                                $num1 = $testData[$ref1]['length'] * $testArray['multi1'];
                                $num2 = $testData[$ref2]['length'] * $testArray['multi2'];
                                if ($greater) {
                                    $result = $num1 > $num2;
                                } else {
                                    $result = $num1 < $num2;
                                }
                                $this->testResults[$testArray['id']] = $result;
                            }
                        }
                        break;
                    default:
                        // do nothing
                        break;
                }
            }
        }
        // determine which conversions are used based on these tests
        $this->setUpConversions($db);
    }

    /**
     * @param resource|null $db
     */
    public function exportAndUpload(&$db) {
        checkPgConnection($db);
        $this->returnToStart(); // we need to start at the beginning to loop through the data again
        while ($this->read(false, 'verse', '', 'sID')) {
            try {
                $verseXML = $this->currentVerse();
            } catch(PositionException $e) {
                // this shouldn't happen, but if it does, just log the error and go to the next verse
                $this->errorLog->log($e);
                continue;
            }
            // TODO: handle OSIS verse XML (word elements, et al)
        }
    }

    /**
     * @throws PositionException
     * @return array Associative array with keys:
            * reference - the verse reference
            * text - the text of the verse
     */
    private function currentVerse() {
        if (strtolower($this->localName) !== 'verse') {
            throw new PositionException('Not on a verse', 1);
        }
        if (is_null($this->getAttribute('sID'))) {
            throw new PositionException('On a verse end spot', 2);
        }
        $bracketId = $this->getAttribute('sID');
        $output['reference'] = getLocation($db, $this->getAttribute('osisID'));
        while ($this->read() && $this->getAttribute('eID') !== $bracketId) {
            if ($this->hasValue) { $output['text'] .= $this->value; }
        }
        return $output;
    }

    /**
     * @return string
     */
    private function divType() {
        if ($this->nodeType !== XMLReader::ELEMENT) {
            return '';
        }
        if (strtolower($this->localName) !== 'div') {
            return '';
        }
        if (!$this->hasAttributes) {
            return '';
        }
        return $this->getAttribute('type');
    }
}