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
 * @param array $values Value array from xml_parse_into_struct; source XML assumed to meet OSIS standard
 * @param array $indices Index array from xml_parse_into_struct; source XML assumed to meet OSIS standard
 */
function osisImport($values, $indices) {
    global $allVerses, $allNotes, $metadata;

    // metadata to get
    $met2get = array('TITLE', 'CREATOR', 'DESCRIPTION', 'PUBLISHER', 'LANGUAGE', 'RIGHTS', 'REFSYSTEM');
    foreach ($met2get as $meta) {
        if (array_key_exists($meta, $indices)) {
            for ($idx = 0; $idx < count($indices[$meta]); $idx++) {
                if (xml_get_value($values, array($indices[$meta][$idx], 'value'), $ret) === 0) {
                    $metadata[$meta][] = $ret;
                }
            }
        }
    }

    $inNote = false;
    $isCrossRef = false;
    for ($idx = 0; $idx < count($indices['VERSE']) - 1; $idx += 2) {
        $start = $indices['VERSE'][$idx];
        $end = $indices['VERSE'][$idx + 1];
        unset($verseId);
        for ($valIdx = $start; $valIdx < $end; $valIdx++) {
            xml_get_value($values, array($valIdx, 'tag'), $xmlTag);
            if ($xmlTag == 'VERSE') {
                if (xml_get_value($values, array($valIdx,'attributes','SID'), $ret) === 0) {
                    $verseId = $ret;
                    $allVerses[$verseId] = "";
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
                    } elseif ($noteType == 'close') {
                        $inNote = false;
                        $isCrossRef = false;
                    }
                }
                if (xml_get_value($values, array($valIdx, 'attributes', 'OSISREF'), $ret) === 0) {
                    $noteRef = $ret;
                    xml_get_value($values[$valIdx]['attributes'], array('OSISID'), $noteId);
                    if ($isCrossRef && !isset($allNotes[$noteRef]['crossRef'])) {
                        $allNotes[$noteRef]['crossRef'] = "";
                    } elseif (xml_get_value($values, array($valIdx, 'value'), $ret) === 0 && !$isCrossRef) {
                        $allNotes[$noteRef][$noteId] = $ret;
                    } else {
                        $allNotes[$noteRef][$noteId] = "";
                    }
                } elseif (xml_get_value($values, array($valIdx, 'value'), $ret) === 0 && isset($noteRef) && isset($noteId) && !$isCrossRef) {
                    $allNotes[$noteRef][$noteId] .= $ret;
                }
            } elseif ($xmlTag == 'REFERENCE') {
                if ($inNote && $isCrossRef && isset($noteRef) && isset($noteId) && xml_get_value($values, array($valIdx, 'attributes', 'OSISREF'), $ret) === 0 && $ret != $noteRef) {
                    $allNotes[$noteRef]['crossRef'] .= $ret . ";";
                } else {
                    continue;
                }
            } else {
                if ($inNote && isset($noteRef) && isset($noteId) && xml_get_value($values, array($valIdx, 'value'), $ret) === 0) {
                    $allNotes[$noteRef][$noteId] .= $ret . " ";
                } elseif (isset($verseId) && xml_get_value($values, array($valIdx, 'value'), $ret) === 0) {
                    $allVerses[$verseId] .= $ret . " ";
                }
            }
        }
        if (isset($verseId)) {
            $allVerses[$verseId] = reduceSpaces($allVerses[$verseId]);
        }
        if (isset($noteRef) && isset($noteId) && array_key_exists($noteId, $allNotes[$noteRef])) {
            $allNotes[$noteRef][$noteId] = reduceSpaces($allNotes[$noteRef][$noteId], true);
        }
    }
}

/**
 * Converts OSIS-specific global structure into standard $hexaData structure. Expects $metadata, $allVerses, and
 * $allNotes to match output described with osisImport() function. Expects $hexaData to exist and to be empty.
 */
function osis2hexa() {
    global $allVerses, $allNotes, $metadata, $hexaData;
    $hexaData['title'] = $metadata['TITLE'][0];
    $hexaData['desc'] = implode("\n", $metadata['DESCRIPTION']);
    $hexaData['file_creator'] = implode(" ", $metadata['CREATOR']);
    $hexaData['publisher'] = implode("\n", $metadata['PUBLISHER']);
    $hexaData['language'] = $metadata['LANGUAGE'];
    $hexaData['copyright'] = $metadata['RIGHTS'];
    $hexaData['reference_system'] = $metadata['REFSYSTEM'];
    foreach($allVerses as $ref => $verse) {
        $reference = explode(".seID.", $ref)[0];
        $hexaData['verses'][$reference] = $verse;
    }
    foreach($allNotes as $ref => $noteGroup) {
        foreach($noteGroup as $note) {
            $hexaData['notes'][$ref][] = $note;
        }
    }
}