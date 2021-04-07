<?php

namespace Hexapla;
use InvalidArgumentException, XMLReader;
include_once "bible-xml-reader.php";

class OSISReader extends BibleXMLReader {
    /** @var osisWorkAnalyzer $workAnalyzer */
    private osisWorkAnalyzer $workAnalyzer;
    /** @var int $formatStyle */
    public int $formatStyle;

    /**
     * Override
     * @param array $args
     * @return bool
     */
    public function read($args = []): bool
    {
        if (count($args) === 0) {
            return parent::read();
        }
        $skipLineBreaks = (isset($args['skipLineBreaks']) ? $args['skipLineBreaks'] : false);
        $targetNodeType = (isset($args['targetNodeType']) ? $args['targetNodeType'] : -1);
        $targetDivType = (isset($args['targetDivType']) ? $args['targetDivType'] : '');
        $targetAttributeExists = (isset($args['targetAttributeExists']) ? $args['targetAttributeExists'] : '');
        $targetElementName = (isset($args['targetElementName']) ? $args['targetElementName'] : '');
        if ($this->formatStyle === OSIS_FORMAT_ENUM::SIMPLE_STRUCT && isset($args['or'])) {
            $orArgs = $args['or'];
            $targetNodeType = (isset($orArgs['targetNodeType']) ? $orArgs['targetNodeType'] : -1);
            $targetDivType = (isset($orArgs['targetDivType']) ? $orArgs['targetDivType'] : '');
            $targetAttributeExists = (isset($args['targetAttributeExists']) ? $orArgs['targetAttributeExists'] : '');
            $targetElementName = (isset($args['targetElementName']) ? $args['targetElementName'] : '');
        }
        do {
            $output = parent::read();
            if (!$output) break;
            $done = $this->nodeMatch($skipLineBreaks, $targetNodeType, $targetDivType, $targetAttributeExists, $targetElementName);
        } while (!$done);
        return $output;
    }

    /** @noinspection PhpParameterNameChangedDuringInheritanceInspection */
    public function next($localName = null): bool
    {
        if ($this->localName === 'q' || $this->localName === 'p') {
            // these are multi-verse spanning elements that break everything else, so we need to read instead
            /** array $args */
            $args['targetElementName'] = $localName;
            return $this->read($args);
        } else {
            if (is_null($localName)) {
                return parent::next();
            } else {
                return parent::next($localName);
            }
        }
    }

    /**
     * @param resource|null $db
     * @throws HexaplaException
     * @throws HexaplaException
     */
    public function runTests(&$db) {
        $this->perfLog->log("start runTests");
        checkPgConnection($db);
        $testSearch = getData($db, HexaplaTables::LOC_TEST);
        $testIndex = [];
        // create the test index
        while (($row = pg_fetch_assoc($testSearch)) !== false) {
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
                        /** @noinspection PhpUndefinedVariableInspection -- if it crashes, it crashes */
                        $this->testComparisonToIndex($testIndex, $ref1, (int)$testType, $ref2, $row);
                        break;
                    default:
                        throw new InvalidArgumentException('Broken test');
                }
            } /** @noinspection PhpUnusedLocalVariableInspection */ catch(InvalidArgumentException | NoOppositeTypeException $e) {
                continue;
            }
        }
        // use the test index to load necessary test data
        $testData = [];
        while ($this->read(
            ['targetElementName' => OsisTags::VERSE, 'targetAttributeExists' => OsisAttributes::START_ID,
                'or' => ['targetElementName' => OsisTags::VERSE, 'targetAttributeExists' => OsisAttributes::OSIS_ID]])) {
            $ref1 = getStandardizedReference($db,
                $this->getAttribute(OsisAttributes::OSIS_ID),
                $book,
                $chapter,
                $verse);
            if (isset($testIndex[$ref1])) {
                foreach ($testIndex[$ref1] as $testType => $test) {
                    switch ($testType) {
                        case HexaplaTests::LAST:
                            if (!isset($testData[$book][$chapter]['last']) || $verse > $testData[$book][$chapter]['last']) {
                                $testData[$book][$chapter]['last'] = $verse;
                            }
                            $testData[$ref1]['last'] = $testData[$book][$chapter]['last'] == $verse;
                            break;
                        case HexaplaTests::EXIST:
                        case HexaplaTests::NOT_EXIST:
                            $testData[$ref1]['exists'] = true;
                            break;
                        case HexaplaTests::GREATER_THAN:
                        case HexaplaTests::LESS_THAN:
                            try {
                                if (!isset($testData[$ref1]['length'])) {
                                    $testData[$ref1]['length'] = utf8_strlen($this->currentVerse()['text']);
                                }
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
        // we also need to account for what might NOT exist
        foreach ($testIndex as $ref => $testList) {
            foreach ($testList as $testType => $test) {
                switch($testType) {
                    case HexaplaTests::EXIST:
                    case HexaplaTests::NOT_EXIST:
                        if (!isset($testData[$ref]['exists'])) {
                            $testData[$ref]['exists'] = false;
                        }
                        break;
                    default:
                        continue 2;
                }
            }
        }
        // now that test data is loaded, run the tests
        if (count($testData) === 0) {
            // no tests apply, so we're done
            return;
        }
        foreach ($testIndex as $ref1 => $test) {
            if (!isset($testData[$ref1])) continue;
            unset($ref2); // this is unnecessary, it just makes debugging less confusing
            foreach ($test as $testType => $testStep) {
                $greater = false;
                switch ($testType) {
                    case HexaplaTests::LAST:
                        getStandardizedReference($db,
                            $ref1,
                            $book,
                            $chapter,
                            $verse);
                        if (isset($testData[$book][$chapter]['last'])) {
                            $this->testResults[$testStep] = ($testData[$book][$chapter]['last'] === $verse);
                        } else {
                            $this->testResults[$testStep] = false;
                        }
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
                                if (!isset($testData[$ref1])) {
                                    $num1 = 0;
                                } else {
                                    $num1 = $testData[$ref1]['length'] * $testArray['multi1'];
                                }
                                if (!isset($testData[$ref2])) {
                                    $num2 = 0;
                                } else {
                                    $num2 = $testData[$ref2]['length'] * $testArray['multi2'];
                                }
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
        $this->perfLog->log("finish runTests");
    }

    /**
     * @param resource|null $db
     * @throws HexaplaException
     * @throws HexaplaException
     */
    public function exportAndUpload(&$db) {
        // TODO: Handle UPDATING notes / non-canonical text as well
        $this->existingDataCheck($db);
        //$this->perfLog->activate();
        $this->perfLog->log("start exportAndUpload");
        checkPgConnection($db);
        $this->returnToStart(); // we need to start at the beginning to loop through the data again
        while ($this->read(
            ['targetElementName' => OsisTags::VERSE, 'targetAttributeExists' => OsisAttributes::START_ID,
                'or' => ['targetElementName' => OsisTags::VERSE, 'targetAttributeExists' => OsisAttributes::OSIS_ID]])) {
            try {
                $verse = $this->currentVerse();
            } catch(PositionException $e) {
                // this shouldn't happen, but if it does, just log the error and go to the next verse
                $this->errorLog->log($e);
                continue;
            }
            //$this->perfLog->log("current verse load from XML");
            $this->beginCommitChapter($db, $verse['reference']);
            $extraVerbose = false;
            //$extraVerbose = $verse['reference'] == 'Matthew 5:3';
            if ($extraVerbose) $this->perfLog->log("commit/begin query");
            // this is finally a small enough piece of XML that we can load it into workable memory
            //$this->perfLog->log("start " . $verse['reference']);
            $parser = xml_parser_create('utf-8');
            xml_parse_into_struct($parser, $verse['xml'], $values, $index);
            if ($extraVerbose) $this->perfLog->log("parse into struct");
            $key = 0;
            $words = [];
            $notes = [];
            $inNote = false;
            for ($w = 0; $w < count($values); $w++) {
                if (xml_get_value($values[$w], array(OsisProperties::VALUE), $text) === 1) { // 1 = error
                    continue;
                }
                if (xml_value_is($values[$w], array(OsisProperties::TAG), utf8_strtoupper(OsisTags::NOTE))) {
                    if (xml_value_is($values[$w], array(OsisProperties::TYPE), 'open')) {
                        $inNote = true;
                        $this->splitAndCreateWords($text, $verse['reference'], $key, $notes, true);
                    } elseif (xml_value_is($values[$w], array(OsisProperties::TYPE), 'close')) {
                        $inNote = false;
                    } else { // cData
                        $this->splitAndCreateWords($text, $verse['reference'], $key, $notes, true);
                    }
                } elseif ($inNote) {
                    if (xml_value_is($values[$w], array(OsisProperties::TAG), utf8_strtoupper(OsisTags::CATCH_WORD)) &&
                        !xml_value_is($values[$w], array(OsisProperties::TYPE), 'close')) {
                        $text = '<strong>' . utf8_trim($text) . '</strong>';
                    }
                    $this->splitAndCreateWords($text, $verse['reference'], $key, $notes, true);
                } elseif (xml_value_is($values[$w], array(OsisProperties::TAG), utf8_strtoupper(OsisTags::VERSE))) {
                    $this->splitAndCreateWords($text, $verse['reference'], $key, $words);
                } else {
                    $strong = $this->workAnalyzer->getStrongsNumber($values[$w]);
                    createHexaWords($text, $verse['reference'], $key, $words, $strong);
                }
            }
            free($criteria);
            free($values);
            free($index);
            xml_parser_free($parser);
            if ($extraVerbose) $this->perfLog->log("createHexaWords");

            if (isset($this->conversions[$verse['reference']])) {
                $locId = $this->conversions[$verse['reference']];
            } else {
                $locId = getLocation($db, $verse['reference']);
            }
            if ($extraVerbose) $this->perfLog->log("location ID retrieval");
            $noteRefs = $noteTexts = [];
            /** @var hexaNote $note */
            foreach($notes as $note) {
                free($criteria);
                $criteria[HexaplaVersionColumns::VERSION_ID] = $this->version; // this works for NoteText and NoteCrossRef
                $note->setLocationId($locId);
                $note->toCriteria($criteria);
                if ($note->hasText()) {
                    $noteTexts[] = $criteria;
                } else {
                    $noteRefs[] = $criteria;
                }
            }
            if (count($noteTexts) > 0) {
                putData($db, HexaplaTables::NOTE_TEXT, $noteTexts);
            }
            if (count($noteRefs) > 0) {
                putData($db, HexaplaTables::NOTE_CROSSREF, $noteRefs);
            }
            free($noteTexts);
            free($noteRefs);
            free($notes);
            $insertCollection = [];
            /** @var hexaWord $word */
            foreach ($words as $word) {
                free($criteria);
                $criteria[HexaplaTextValue::VERSION_ID] = $this->version;
                $word->setLocationId($locId);
                $word->toCriteria($criteria, true);
                $insert = $criteria;
                $word->toCriteria($insert);
                if (isset($this->existingRows[$locId]) && isset($this->existingRows[$locId][$criteria[HexaplaTextValue::POSITION]])) {
                    $criteria[HexaplaTextValue::ID] = $this->existingRows[$locId][$criteria[HexaplaTextValue::POSITION]];
                    update($db, HexaplaTables::TEXT_VALUE, $insert, $criteria);
                } else {
                    $insertCollection[] = $insert;
                }
                free($insert);
                if ($extraVerbose) $this->perfLog->log("single word query");
            }
            if (count($insertCollection) > 0) {
                putData($db, HexaplaTables::TEXT_VALUE, $insertCollection);
            }
            free($insertCollection);
            free($words);
            $this->perfLog->log("finish " . $verse['reference']);
            //$this->perfLog->deactivate();
        }
        $this->beginCommitChapter($db, "");
        $this->perfLog->log("finish exportAndUpload");
    }

    /**
     * @param resource|null $db
     */
    public function loadMetadata(&$db) {
        checkPgConnection($db);
        $this->returnToStart();
        $this->read(['targetElementName' => OsisTags::HEADER]);
        $headerXML = $this->readOuterXml();
        $parser = xml_parser_create('utf-8');
        xml_parse_into_struct($parser, $headerXML, $values, $index);
        $this->workAnalyzer = new osisWorkAnalyzer($values, $index);
        $hexaData = new HexaplaStandardMetadata();
        $this->osisGetMetadata($values, $index, $hexaData,
            array(
                new osisMetadataOptions(OsisTags::TITLE, 0),
                new osisMetadataOptions(OsisTags::CREATOR),
                new osisMetadataOptions(OsisTags::DESCRIPTION, -1, '\n'),
                new osisMetadataOptions(OsisTags::PUBLISHER, -1, '\n'),
                new osisMetadataOptions(OsisTags::LANGUAGE),
                new osisMetadataOptions(OsisTags::RIGHTS),
                new osisMetadataOptions(OsisTags::REFERENCE_SYSTEM)
            )
        );
        $this->version = $hexaData->upload($db);
        xml_parser_free($parser);
    }

    /**
     * Retrieves specified metadata from XML arrays and saves to our formatted array
     * @uses hexaText, hexaCopyright, osisMetadataOptions, array_key_exists(), xml_get_value(), count(), utf8_substr()
     * @uses utf8_strlen(), hexaName
     * @param array $values Value array output of xml_parse_into_struct
     * @param array $indices Index array output of xml_parse_into_struct
     * @param HexaplaStandardMetadata $hexaData Output object
     * @param array $meta2get List of osisMetadataOptions objects
     */
    private function osisGetMetadata(array $values, array $indices, HexaplaStandardMetadata &$hexaData, array $meta2get): void {
        $copyright = new hexaCopyright();
        /** @var osisMetadataOptions $meta */
        foreach ($meta2get as $meta) {
            $name = $meta->getName();
            $index = $meta->getIndex();
            $delim = $meta->getDelim();
            if (array_key_exists($name, $indices)) {
                $value = '';
                if ($index >= 0) {
                    if (xml_get_value($values, array($indices[$name][$index], OsisProperties::VALUE), $ret) === 0) {
                        $value = $ret;
                    }
                } else {
                    if (count($indices[$name]) > 0) {
                        $value = '';
                        for ($index = 0; $index < count($indices[$name]); $index++) {
                            if (xml_get_value($values, array($indices[$name][$index], OsisProperties::VALUE), $ret) === 0) {
                                $value .= $delim . $ret;
                            }
                        }
                        $value = utf8_substr($value, utf8_strlen($delim));
                    }
                }
                if ($name === utf8_strtoupper(OsisTags::TITLE)) {
                    $hexaData->setTitle(new hexaName($value));
                    $this->title = $value;
                } elseif ($name == utf8_strtoupper(OsisTags::PUBLISHER)) {
                    $copyright->setPublisher($value);
                } elseif ($name == utf8_strtoupper(OsisTags::RIGHTS)) {
                    $copyright->setRights($value);
                } elseif ($name === utf8_strtoupper(OsisTags::LANGUAGE)) {
                    $hexaData->setLanguage(new hexaName($value));
                } else {
                    $hexaData->addMetadata($name, $value);
                }
            }
        }
        $hexaData->setCopyright($copyright);
    }

    /**
     * @throws PositionException
     * @return array Associative array with keys:
            * reference - the verse reference
            * text - the text of the verse
     */
    private function currentVerse(): array
    {
        if (strtolower($this->localName) !== OsisTags::VERSE) {
            throw new PositionException('Not on a verse', 1, null, get_defined_vars());
        }
        if ($this->formatStyle === OSIS_FORMAT_ENUM::WITH_OVERLAP && is_null($this->getAttribute(OsisAttributes::START_ID))) {
            throw new PositionException('On a verse end spot', 2, null, get_defined_vars());
        }
        $bracketId = $this->getAttribute(OsisAttributes::START_ID);
        $output['reference'] = getStandardizedReference($db, $this->getAttribute(OsisAttributes::OSIS_ID));
        $output['xml'] = '<verse>'; // this is a fake XML object to help xml_parser understand what we're doing
        if ($this->formatStyle === OSIS_FORMAT_ENUM::WITH_OVERLAP) {
            while ($this->next() && !$this->finishedVerse($bracketId)) {
                $output['xml'] .= $this->readOuterXml();
            }
        } else {
            $output['xml'] .= $this->readInnerXml();
        }
        $output['xml'] .= '</verse>';
        $output['text'] = '';
        $tempParser = xml_parser_create('utf-8');
        xml_parse_into_struct($tempParser, $output['xml'], $values);
        foreach($values as $i => $data) {
            unset($ret);
            if (xml_get_value($data, array(OsisProperties::VALUE), $ret) !== 1) {
                $output['text'] .= $ret;
            }
        }
        xml_parser_free($tempParser);
        return $output;
    }

    private function finishedVerse($id): bool
    {
        if ($this->formatStyle === OSIS_FORMAT_ENUM::WITH_OVERLAP) {
            return $this->getAttribute(OsisAttributes::END_ID) === $id;
        } else {
            return $this->nodeType === XMLReader::END_ELEMENT && $this->localName === OsisTags::VERSE;
        }
    }

    /**
     * @return string
     */
    private function divType(): string
    {
        if ($this->nodeType !== XMLReader::ELEMENT) {
            return '';
        }
        if (strtolower($this->localName) !== OsisTags::DIV) {
            return '';
        }
        if (!$this->hasAttributes) {
            return '';
        }
        return $this->getAttribute(OsisAttributes::TYPE);
    }

    /**
     * @param bool $skipLineBreaks
     * @param int $targetNodeType
     * @param string $targetDivType
     * @param string $targetAttributeExists
     * @param string $targetElementName
     * @return false
     */
    private function nodeMatch(bool $skipLineBreaks, int $targetNodeType, string $targetDivType, string $targetAttributeExists, string $targetElementName): bool
    {
        $done = true;
        if ($skipLineBreaks && preg_match('/^[\r\n]+$/', $this->value) > 0) {
            $done = false;
        } elseif ($targetNodeType >= 0 && $this->nodeType !== $targetNodeType) {
            $done = false;
        } elseif (strlen($targetDivType) > 0 && $this->divType() !== $targetDivType) {
            $done = false;
        } elseif (strlen($targetAttributeExists) > 0 && is_null($this->getAttribute($targetAttributeExists))) {
            $done = false;
        } elseif (strlen($targetElementName) > 0 && $this->localName !== $targetElementName) {
            $done = false;
        }
        return $done;
    }

    /**
     * @param bool|string $text
     * @param $reference
     * @param int $key
     * @param array $words
     * @param bool $isNote
     */
    public function splitAndCreateWords(bool|string $text, $reference, int &$key, array &$words, bool $isNote = false): void
    {
        $text = utf8_trim($text);
        if (utf8_strlen($text) > 0) {
            if ($isNote) {
                $words[] = new hexaNote($reference, $text);
            } else {
                foreach (preg_split("/\s+/u", $text) as $textPiece) {
                    createHexaWords($textPiece, $reference, $key, $words);
                }
            }
        }
    }
}

/**
 * Class osisWorkAnalyzer
 */
class osisWorkAnalyzer {
    /**
     * @var array|bool Either a list of IDs for all works in the document or FALSE, indicating there are no works.
     */
    private bool|array $allWorks;
    /**
     * @var array|bool Either a list of IDs for works in the document associated with Strong's Numbers or FALSE,
     *                 indicating there are no works.
     */
    private bool|array $strongWorks;
    /**
     * @var array|bool Either the names of potential <W> attributes containing Strong's Numbers, an empty array,
     *                 indicating we do not know it, or FALSE, indicating there are no works.
     */
    private bool|array $strongAttr;

    /**
     * osisWorkAnalyzer constructor.
     * @param array $values
     * @param array $indices
     */
    public function __construct(array $values, array $indices) {
        if (isset($indices[utf8_strtoupper(OsisTags::WORK)])) {
            $this->allWorks = [];
            $this->strongWorks = [];
            $this->strongAttr = [];
            for ($idx = $indices[utf8_strtoupper(OsisTags::WORK)][0]; $idx < $indices[utf8_strtoupper(OsisTags::WORK)][count($indices[utf8_strtoupper(OsisTags::WORK)]) - 1]; $idx++) {
                xml_get_value($values, array($idx, OsisProperties::TAG), $xmlTag);
                if ($xmlTag == utf8_strtoupper(OsisTags::WORK)) {
                    if (xml_get_value($values, array($idx, OsisProperties::ATTRIBUTES, utf8_strtoupper(OsisAttributes::OSIS_WORK)), $idCheck) === 0) {
                        $workId = $idCheck;
                        $this->allWorks[] = $workId;
                    }
                } elseif (($xmlTag == utf8_strtoupper(OsisTags::TITLE) || $xmlTag == utf8_strtoupper(OsisTags::DESCRIPTION) || $xmlTag == utf8_strtoupper(OsisTags::REFERENCE_SYSTEM)) && isset($workId)) {
                    xml_get_value($values, array($idx, OsisProperties::VALUE), $ret);
                    if (utf8_strpos(utf8_strtoupper($ret), 'STRONG') !== false) {
                        $this->strongWorks[] = $workId;
                    }
                }
            }
            if (isset($indices[utf8_strtoupper(OsisTags::WORK_PREFIX)])) {
                for ($idx = 0; $idx < count($indices[utf8_strtoupper(OsisTags::WORK_PREFIX)]); $idx++) {
                    xml_get_value($values, array($idx, OsisProperties::ATTRIBUTES, utf8_strtoupper(OsisAttributes::OSIS_WORK)), $workId);
                    if (in_array($workId, $this->strongWorks)) {
                        xml_get_value($values, array($idx, OsisProperties::ATTRIBUTES, OsisAttributes::PATH), $strongPath);
                        $strongAttr = explode('@', $strongPath)[1];
                        if (utf8_strlen($strongAttr) > 0) {
                            $this->strongAttr[] = $strongAttr;
                        }
                    }
                }
            }
        } else {
            $this->allWorks = false;
            $this->strongWorks = false;
            $this->strongAttr = false;
        }

    }

    /**
     * Given a word node, retrieves the Strong's Number based on the works and workPrefixes we know about
     * @param array $wNode
     * @return string The Strong's Number(s), comma-delimited if multiple, associated with this word node
     * @uses count(), xml_get_attribute_set(), utf8_strpos(), explode, isStrongsNumber(), utf8_strlen(), implode()
     */
    public function getStrongsNumber(array $wNode): string
    {
        $strongArray = [];
        $strongs = '';
        if (count($this->strongAttr) > 0) {
            xml_get_attribute_set($wNode, $this->strongAttr, $vals);
            foreach($vals as $val) {
                if (utf8_strpos($val, ' ') !== false) {
                    foreach(explode(' ', $val) as $num) {
                        if (isStrongsNumber($num)) {
                            $strongArray[] = $num;
                        }
                    }
                } else {
                    if (isStrongsNumber($val)) {
                        $strongs = $val;
                    }
                }
            }
        } else {
            xml_get_attribute_set($wNode,
                array(
                    utf8_strtoupper(OsisAttributes::GLOSS),
                    utf8_strtoupper(OsisAttributes::LEMMA),
                    utf8_strtoupper(OsisAttributes::MORPH),
                    utf8_strtoupper(OsisAttributes::PART_OF_SPEECH),
                    utf8_strtoupper(OsisAttributes::SOURCE),
                    utf8_strtoupper(OsisAttributes::TRANSLITERATION)),
                $vals);
            foreach($vals as $val) {
                $metaList = explode(' ', $val);
                foreach($metaList as $metaChunk) {
                    $keyValue = explode(':', $metaChunk);
                    if (count($keyValue) > 1) {
                        $num = $keyValue[1];
                    } else {
                        $num = $keyValue[0];
                    }
                    $num = stripStrongsNums($num);
                    if (isStrongsNumber($num)) {
                        $strongArray[] = $num;
                    }
                }
            }
        }
        if (count($strongArray) > 0 && utf8_strlen($strongs) == 0) {
            $strongs = implode(',', $strongArray);
        }
        return $strongs;
    }
}

class OsisTags {
    const CREATOR = 'creator';
    const DESCRIPTION = 'description';
    const DIV = 'div';
    const HEADER = 'header';
    const LANGUAGE = 'language';
    const PUBLISHER = 'publisher';
    const REFERENCE_SYSTEM = 'refSystem';
    const RIGHTS = 'rights';
    const TITLE = 'title';
    const VALUE = 'value';
    const VERSE = 'verse';
    const WORK = 'work';
    const WORK_PREFIX = 'workPrefix';
    const NOTE = 'note';
    const CATCH_WORD = 'catchword';
}

class OsisAttributes {
    const END_ID = 'eID';
    const GLOSS = 'gloss';
    const LEMMA = 'lemma';
    const MORPH = 'morph';
    const OSIS_ID = 'osisID';
    const OSIS_WORK = 'osisWork';
    const PART_OF_SPEECH = 'POS';
    const PATH = 'path';
    const SOURCE = 'src';
    const START_ID = 'sID';
    const TRANSLITERATION = 'xlit';
    const TYPE = 'type';
}

class OsisProperties {
    const ATTRIBUTES = 'attributes';
    const TAG = 'tag';
    const VALUE = 'value';
    const TYPE = 'type';
}

/**
 * Class osisMetadataOptions
 */
class osisMetadataOptions {
    /**
     * @var string The name of the metadata piece to retrieve
     */
    private string $name;
    /**
     * @var int The index to retrieve. Default -1. If >=0, osisGetMetadata will return that index; if <0,
     *          osisGetMetadata will return all indexes in a string with the delimiter in $delimiter
     */
    private int $indexRequest;
    /**
     * @var string Delimiter between sets of metadata. Ignored if $indexRequest >= 0. Default is a single space.
     */
    private string $delimiter;

    /**
     * osisMetadataOptions constructor.
     * @param string $newName
     * @param int $indexRequested
     * @param string $delim
     */
    public function __construct(string $newName, $indexRequested = -1, $delim = ' ') {
        $this->name = utf8_strtoupper($newName);
        $this->indexRequest = $indexRequested;
        $this->delimiter = $delim;
    }

    /**
     * Public getter of $this->name
     * @return string The metadata name to retrieve
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Public getter of $this->indexRequest
     * @return int The index to retrieve, or -1 to retrieve all
     */
    public function getIndex(): int
    {
        return $this->indexRequest;
    }

    /**
     * Public getter of $this->delimiter
     * @return string The delimiter to place between pieces of metadata
     */
    public function getDelim(): string
    {
        return $this->delimiter;
    }
}

/**
 * Given a substring of a verse (split by spaces), creates both punctuation and regular word objects for it.
 * @uses nonwordRegexPattern(), preg_match(), hexaPunctuation, hexaWord, utf8_strlen(), preg_replace()
 * @param string $word
 * @param string $verseId
 * @param int $key
 * @param array $verseWords
 * @param string $strongsNum
 */
function createHexaWords(string $word, string $verseId, int &$key, array &$verseWords, string $strongsNum = ''): void {
    $nonWordPattern = nonwordRegexPattern();
    if (preg_match("/^($nonWordPattern)+$/u", $word, $matches) === 1) {
        if (in_array($matches[0], hexaPunctuation::OPENERS)) {
            $ending = false;
        } else {
            $ending = true;
        }
        $newWord = new hexaPunctuation($verseId, $matches[0], $key++, '', '', $ending);
        $verseWords[] = $newWord;
        return;
    }
    if (preg_match("/^($nonWordPattern)+/u", $word, $matches) === 1) {
        $newWord = new hexaPunctuation($verseId, $matches[0], $key++, '', '', false);
        $verseWords[] = $newWord;
    }
    $trimPattern = "($nonWordPattern|\s)";
    $wordOnly = preg_replace("/(^$trimPattern*)|($trimPattern*$)/u", '', $word);
    if (utf8_strlen($wordOnly) > 0) {
        $newWord = new hexaWord($verseId, $wordOnly, $key++, $strongsNum, '');
        $verseWords[] = $newWord;
    }
    if (preg_match("/($nonWordPattern)+$/u", $word, $matches) === 1) {
        $newWord = new hexaPunctuation($verseId, $matches[0], $key++);
        $verseWords[] = $newWord;
    }
}

class OSIS_FORMAT_ENUM {
    const WITH_OVERLAP = 1;
    const SIMPLE_STRUCT = 2;
}