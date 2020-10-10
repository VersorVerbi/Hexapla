<?php

/**
 * Given a string, returns a string containing only the numeric digits (0-9)
 * @uses preg_replace()
 * @param string $str
 * @return string
 */
function numbersOnly($str) {
    return preg_replace('/\D/u', '', $str);
}

/**
 * @param $ref
 * @return string|null
 */
function bookFromReference($ref): ?string {
    $pattern = '/^((?:(?:\d+|[[:alpha:]]+)\s?)*[[:alpha:]]+(?:\s?\([[:alpha:]]+\))?)(?=\s*\d+|\W)/u';
    preg_match($pattern, $ref, $matches);
    if (count($matches) === 0) {
        return null;
    }
    return $matches[0];
}

/**
 * @param $ref
 * @param $book
 * @param $bookId
 * @return array
 */
function refArrayFromReference($ref, $book, $bookId): array {
    $ref = str_replace($book, '', $ref);
    $ref = cvTrim($ref);
    $split = splitChapterVerse($ref);
    if (count($split) == 1) {
        $criteria[HexaplaLocSubsection::SECTION_ID] = $bookId;
        if (getCount($db, HexaplaTables::LOC_SUBSECTION, $criteria) == 1) {
            $chapter = 1;
            $verse = $split[0];
        }
    }
    if (!isset($chapter)) {
        $chapter = $split[0];
    }
    if (!isset($verse)) {
        $verse = (count($split) > 1 ? $split[1] : 1);
    }
    return [$chapter, $verse];
}

/**
 * @param $cv
 * @return array
 */
function splitChapterVerse($cv): array {
    $out = explode(':', $cv);
    if (count($out) > 1) return $out;
    $out = explode('.', $cv);
    if (count($out) > 1) return $out;
    $out = explode(',', $cv);
    return $out;
}

/**
 * @param $db
 * @param $reference
 * @return int
 */
function getLocation(&$db, $reference) {
    checkPgConnection($db);
    $book = bookFromReference($reference);
    $bookId = getBookId($db, $book);
    if ($bookId < 0) return -1;
    $cv = refArrayFromReference($reference, $book, $bookId);
    $chapterId = getChapterId($db, $bookId, $cv[0]);
    if ($chapterId < 0) return -1;
    $verseId = getVerseId($db, $chapterId, $cv[1]);
    if ($verseId < 0) {
        throw new HexaplaException('Location ' . $reference . ' could not be found.', 2, null, debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2));
    }
    return $verseId;
}

/**
 * @param $db
 * @param $bookName
 * @return int
 */
function getBookId(&$db, $bookName): int {
    checkPgConnection($db);
    $columns[] = HexaplaLocSectionTerm::SECTION_ID;
    $columns[] = HexaplaLocSectionTerm::IS_PRIMARY;
    $criteria['term'] = $bookName;
    $results = getData($db, HexaplaTables::LOC_SECTION_TERM, $columns, $criteria);
    if (!$results) {
        return -1;
    }
    while (($row = pg_fetch_assoc($results)) !== false) {
        if ($row['is_primary']) {
            break;
        }
    }
    return ($row !== false ? $row[HexaplaLocSectionTerm::SECTION_ID] : -1);
}

/**
 * @param $db
 * @param $bookId
 * @param $chapter
 * @return int
 */
function getChapterId(&$db, $bookId, $chapter): int {
    checkPgConnection($db);
    $columns[] = HexaplaLocSubsection::ID;
    $criteria['section_id'] = $bookId;
    $criteria['position'] = $chapter;
    $results = getData($db, HexaplaTables::LOC_SUBSECTION, $columns, $criteria);
    $row = pg_fetch_assoc($results);
    return ($row !== false ? $row['id'] : -1);
}

/**
 * @param $db
 * @param $chapterId
 * @param $verse
 * @return int
 */
function getVerseId(&$db, $chapterId, $verse): int {
    checkPgConnection($db);
    $columns[] = HexaplaLocation::ID;
    $criteria['subsection_id'] = $chapterId;
    $criteria['position'] = $verse;
    $results = getData($db, HexaplaTables::LOCATION, $columns, $criteria);
    $row = pg_fetch_assoc($results);
    return ($row !== false ? $row['id'] : -1);
}

/**
 * @param resource|null $db
 * @param string $reference Should be the STANDARDIZED reference of the verse
 * @param array $indexArray
 * @param array $conversionIndex
 * @return int
 */
function locationWithIndex(&$db, $reference, &$indexArray, $conversionIndex) {
    if (strpos($reference, "Esther 10") !== false) {
        print_r($reference);
        print_r($indexArray[$reference]);
        print_r($conversionIndex[$reference]);
    }
    if (isset($indexArray[$reference])) {
        return $indexArray[$reference];
    } elseif (isset($conversionIndex[$reference])) {
        // TODO: This requires $ref and source data to have the same string format -- will this be done?
        $refId = implode(',', $conversionIndex[$reference]);
    } else {
        $refId = getLocation($db, $reference);
    }
    $indexArray[$reference] = $refId;
    return $refId;
}

/**
 * @param $convList
 * @return array
 */
function getConversionsByDisplayRef($convList) {
    if (count($convList) == 0) return [];
    $columns[] = HexaplaConversion::LOCATION_ID;
    $columns[] = HexaplaConversion::DISPLAY_NAME;
    $criteria['id'] = $convList;
    $results = getData($db, HexaplaTables::LOC_CONVERSION, $columns, $criteria);
    $indexedArray = [];
    while (($row = pg_fetch_assoc($results)) !== false) {
        $indexedArray[$row['display_name']][] = $row['loc_id'];
    }
    return $indexedArray;
}

/**
 * @param $array
 */
function free(&$array) {
    $array = null;
    unset($array);
}

/**
 * @param $db
 * @param $bookId
 * @return string
 */
function getBookProperName(&$db, $bookId): string {
    checkPgConnection($db);
    $sql = 'SELECT term FROM public.loc_section_term WHERE loc_section_term.id = (SELECT primary_term_id FROM public.loc_section WHERE loc_section.id = $1)';
    $results = pg_query_params($db, $sql, [$bookId]);
    $row = pg_fetch_assoc($results);
    return (($row === false) ? '' : $row['term']);
}

/**
 * @param $db
 * @param $roughReference
 * @param string $bookName
 * @param string $chapterNumber
 * @param string $verseNumber
 * @return string
 */
function getStandardizedReference(&$db, $roughReference, &$bookName = '', &$chapterNumber = '', &$verseNumber = ''): string {
    checkPgConnection($db);
    $bookName = '';
    $chapterNumber = '';
    $verseNumber = '';
    $bookAbbr = bookFromReference($roughReference);
    if (is_null($bookAbbr)) { return ''; }
    $bookId = getBookId($db, $bookAbbr);
    $bookName = getBookProperName($db, $bookId);
    $cv = refArrayFromReference($roughReference, $bookAbbr, $bookId);
    $chapterNumber = $cv[0];
    $verseNumber = $cv[1];
    return $bookName . ' ' . $chapterNumber . ':' . $verseNumber;
}

/**
 * @param array $row from the conversion tests table
 */
function rowIsEsther($row) {
    return (bookIsEsther($row['book1name']) || bookIsEsther($row['book2name']));
}

/**
 * @param string $bookName name of a book
 */
function bookIsEsther($bookName) {
    return in_array($bookName, array('Esther', 'Esther (Greek)'));
}

/**
 * @param string $bookName either "Esther" or "Esther (Greek)"
 */
function reverseEsther($bookName) {
    if ($bookName === 'Esther') {
        return 'Esther (Greek)';
    } else {
        return 'Esther';
    }
}

function cvTrim($reducedReference) {
    return trim($reducedReference, ".:;, \t\n\r\0\x0B");
}

/**
 * @param array $arr Ideally, array of booleans, but will accept any truthy/falsey array
 * @return int Number of truthy (as opposed to falsey) values in an array
 */
function num_true($arr) {
    if (is_null($arr) || !isset($arr) || count($arr) === 0) {
        return 0;
    }
    $num = 0;
    foreach ($arr as $itm) {
        if ($itm) $num++;
    }
    return $num;
}

/**
 * @param array $array Original associative array
 * @param array $exceptKeys Numeric array of keys to remove from the original array
 * @return array The original array, except the specified keys
 */
function array_except($array, $exceptKeys) {
    return array_diff_key($array, array_flip($exceptKeys));
}

function hasNoValue($val) {
    if (is_null($val)) return true;
    if (strlen($val) === 0) return true;
    if ($val === 'NULL') return true;
    return false;
}