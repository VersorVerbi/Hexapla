<?php
/**
 * Based on the OSIS XML standard, uses given XML data to add metadata, verses, and notes to the global variables
 * $metadata, $allVerses, and $allNotes. After import is complete, global variables will have the following structure:
 * $metadata:
 *      one or more of the following indices
 *      TITLE, CREATOR, DESCRIPTION, PUBLISHER, LANGUAGE, RIGHTS, REFSYSTEM
 * $allVerses:
 *      [OSIS-type Bible reference] = text of verse
 * $allNotes:
 *      [OSIS-type Bible reference]
 *          [#] = text of note
 * @uses osisMetadataOptions, mb_regex_set_options(), osisGetMetadata(), osisWorkAnalyzer, count(), xml_get_value()
 * @uses hexaWord, explode(), utf8_strlen(), hexaNote, osisHandleWord(), preg_split(), noWordSeparatorWritingSystems()
 * @uses utf8_split(), getLastIndex(), createHexaWords()
 * @param array $values Value array from xml_parse_into_struct; source XML assumed to meet OSIS standard
 * @param array $indices Index array from xml_parse_into_struct; source XML assumed to meet OSIS standard
 * @param hexaText $hexaData
 */
function osisImport($values, $indices, &$hexaData): void {
    mb_regex_set_options('mub');
    // metadata to get
    osisGetMetadata($values, $indices, $hexaData,
        array(
            new osisMetadataOptions('TITLE', 0),
            new osisMetadataOptions('CREATOR'),
            new osisMetadataOptions('DESCRIPTION', -1, '\n'),
            new osisMetadataOptions('PUBLISHER', -1, '\n'),
            new osisMetadataOptions('LANGUAGE'),
            new osisMetadataOptions('RIGHTS'),
            new osisMetadataOptions('REFSYSTEM')
        )
    );

    // works, workPrefixes
    $workAnalyzer = new osisWorkAnalyzer($values, $indices);

    $inNote = false;
    $isCrossRef = false;
    $verseCount = 0;
    $noteText = '';
    $crossRef = '';
    $noteRef = '';
    $verseWords = [];
    for ($idx = 0; $idx < count($indices['VERSE']) - 1; $idx += 2) {
        $start = $indices['VERSE'][$idx];
        $end = $indices['VERSE'][$idx + 1];
        unset($verseId);
        $verseCount++;
        for ($valIdx = $start; $valIdx < $end; $valIdx++) {
            xml_get_value($values, array($valIdx, 'tag'), $xmlTag);
            if ($xmlTag == 'VERSE') {
                if (xml_get_value($values, array($valIdx,'attributes','SID'), $ret) === 0) {
                    $verseId = explode(".seID.", $ret)[0];
                    if (count($verseWords) > 0) {
                        /** @var hexaWord $wordObj */
                        foreach($verseWords as $wordObj) {
                            $hexaData->addWord($wordObj);
                        }
                    }
                    $verseWords = null;
                    unset($verseWords);
                    $verseWords = [];
                }
            } elseif ($xmlTag == 'NOTE') {
                if (xml_get_value($values, array($valIdx, 'type'), $ret) === 0) {
                    $noteType = $ret;
                    if ($noteType == "open") {
                        $inNote = true;
                        if (xml_get_value($values, array($valIdx, 'attributes', 'TYPE'), $ret) === 0 && $ret == 'crossReference') {
                            $isCrossRef = true;
                        } else {
                            $isCrossRef = false;
                        }
                        $noteText = '';
                        $crossRef = '';
                        $note = null;
                        unset($note);
                    } elseif ($noteType == 'close') {
                        $inNote = false;
                        $isCrossRef = false;
                        if (utf8_strlen($noteText) > 0) {
                            $note = new hexaNote($noteRef, $noteText);
                        } elseif (utf8_strlen($crossRef) > 0) {
                            $note = new hexaNote($noteRef, '', $crossRef);
                        }
                        if (isset($note)) {
                            $hexaData->addNote($note);
                        }
                    }
                }
                if (xml_get_value($values, array($valIdx, 'attributes', 'OSISREF'), $ret) === 0) {
                    $noteRef = $ret;
                    xml_get_value($values[$valIdx]['attributes'], array('OSISID'), $noteId);
                    if (xml_get_value($values, array($valIdx, 'value'), $ret) === 0 && !$isCrossRef) {
                        $noteText = $ret;
                    } else {
                        $noteText = '';
                    }
                } elseif (xml_get_value($values, array($valIdx, 'value'), $ret) === 0 && isset($noteRef) && isset($noteId) && !$isCrossRef) {
                    $noteText .= $ret;
                }
            } elseif ($xmlTag == 'REFERENCE') {
                if ($inNote && $isCrossRef && isset($noteRef) && isset($noteId) && xml_get_value($values, array($valIdx, 'attributes', 'OSISREF'), $ret) === 0 && $ret != $noteRef) {
                    $crossRef .= $ret . ";";
                } else {
                    continue;
                }
            } else {
                if ($inNote && isset($noteRef) && isset($noteId) && xml_get_value($values, array($valIdx, 'value'), $ret) === 0) {
                    $noteText .= $ret . " ";
                } elseif (isset($verseId) && xml_get_value($values, array($valIdx, 'value'), $ret) === 0) {
                    if ($xmlTag == 'W' || $xmlTag == 'WORD') {
                        osisHandleWord($ret, $verseId, $valIdx, $verseWords, $values, $workAnalyzer);
                    } else {
                        $split = preg_split('/\s/u', $ret);
                        if ((count($split) == 1) && (preg_match(noWordSeparatorWritingSystems(), $ret) === 1)) {
                            $split = utf8_split($ret);
                        }
                        foreach ($split as $word) {
                            $key = getLastIndex($verseWords) + 1;
                            createHexaWords($word, $verseId, $key, $verseWords);
                        }
                    }
                }
            }
        }
    }
    return;
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
        $newWord = new hexaPunctuation($verseId, $matches[0][0], $key++); // assume this is ending punctuation
        $verseWords[] = $newWord;
        return;
    }
    if (preg_match("/^($nonWordPattern)+/u", $word, $matches) === 1) {
        $newWord = new hexaPunctuation($verseId, $matches[0][0], $key++, '', '', false);
        $verseWords[] = $newWord;
    }
    $wordOnly = preg_replace("/$nonWordPattern|\s/u", '', $word);
    if (utf8_strlen($wordOnly) > 0) {
        $newWord = new hexaWord($verseId, $wordOnly, $key++, $strongsNum, '');
        $verseWords[] = $newWord;
    }
    if (preg_match("/($nonWordPattern)+$/u", $word, $matches) === 1) {
        $newWord = new hexaPunctuation($verseId, $matches[0][0], $key++);
        $verseWords[] = $newWord;
    }
    return;
}

/**
 * Given a word (W or WORD element value), retrieves relevant metadata from that element.
 * @uses explode(), getLastIndex(), createHexaWords(), osisWorkAnalyzer
 * @param string $wordValue Word value returned from the XML values array
 * @param string $verseId String ID of the current verse
 * @param int $valIdx Current index of the XML $values array
 * @param array $verseWords Output array of hexaWord and hexaPunctuation objects
 * @param array $values xml_parse_into_struct value array
 * @param osisWorkAnalyzer $workAnalyzer Work analyzer created from this XML document
 */
function osisHandleWord(string $wordValue, string $verseId, int $valIdx, array &$verseWords, array $values, osisWorkAnalyzer $workAnalyzer): void {
    $split = explode(' ', $wordValue);
    foreach ($split as $word) {
        $key = getLastIndex($verseWords) + 1;
        createHexaWords($word, $verseId, $key, $verseWords, $workAnalyzer->getStrongsNumber($values[$valIdx]));
    }
    return;
}

/**
 * Retrieves specified metadata from XML arrays and saves to our formatted array
 * @uses hexaText, hexaCopyright, osisMetadataOptions, array_key_exists(), xml_get_value(), count(), substr()
 * @uses utf8_strlen(), hexaName
 * @param array $values Value array output of xml_parse_into_struct
 * @param array $indices Index array output of xml_parse_into_struct
 * @param hexaText $hexaData Output object
 * @param array $meta2get List of osisMetadataOptions objects
 */
function osisGetMetadata(array $values, array $indices, hexaText &$hexaData, array $meta2get): void {
    $copyright = new hexaCopyright();
    /** @var osisMetadataOptions $meta */
    foreach ($meta2get as $meta) {
        $name = $meta->getName();
        $index = $meta->getIndex();
        $delim = $meta->getDelim();
        if (array_key_exists($name, $indices)) {
            $value = '';
            if ($index >= 0) {
                if (xml_get_value($values, array($indices[$name][$index], 'value'), $ret) === 0) {
                    $value = $ret;
                }
            } else {
                if (count($indices[$name]) > 0) {
                    $value = '';
                    for ($index = 0; $index < count($indices[$name]); $index++) {
                        if (xml_get_value($values, array($indices[$name][$index], 'value'), $ret) === 0) {
                            $value .= $delim . $ret;
                        }
                    }
                    $value = substr($value, utf8_strlen($delim));
                }
            }
            if ($name == 'TITLE') {
                $title = new hexaName($value);
                $hexaData->setTitle($title);
            } elseif ($name == 'PUBLISHER') {
                $copyright->setPublisher($value);
            } elseif ($name == 'RIGHTS') {
                $copyright->setRights($value);
            } else {
                $hexaData->addMetadata($name, $value);
            }
        }
    }
    $hexaData->setCopyright($copyright);
}