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

function bookFromReference($ref): string {
    $pattern = '/^((?:(?:\d+|[[:alpha:]]+)\s?)?[[:alpha:]]+)/u';
    preg_match($pattern, $ref, $matches);
    return $matches[0];
}

function refArrayFromReference($ref, $book, $bookId): array {
    $ref = str_replace($book, '', $ref);
    $ref = trim($ref, ".:;, \t\n\r\0\x0B");
    $split = splitChapterVerse($ref);
    if (count($split) == 1) {
        $criteria['section_id'] = $bookId;
        if (getCount($db, 'loc_subsection', $criteria) == 1) {
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

function splitChapterVerse($cv): array {
    $out = explode(':', $cv);
    if (count($out) > 1) return $out;
    $out = explode('.', $cv);
    if (count($out) > 1) return $out;
    $out = explode(',', $cv);
    return $out;
}

function getLocation(&$db, $reference) {
    checkPgConnection($db);
    $book = bookFromReference($reference);
    $bookId = getBookId($db, $book);
    if ($bookId < 0) return -1;
    $cv = refArrayFromReference($reference, $book, $bookId);
    $chapterId = getChapterId($db, $bookId, $cv[0]);
    if ($chapterId < 0) return -1;
    return getVerseId($db, $chapterId, $cv[1]);
}

function getBookId(&$db, $bookName): int {
    checkPgConnection($db);
    $columns['section_id'] = true;
    $columns['is_primary'] = true;
    $criteria['term'] = $bookName;
    $results = getData($db, LOC_SECT_TERM_TABLE(), $columns, $criteria);
    while (($row = pg_fetch_assoc($results)) !== false) {
        if ($row['is_primary']) {
            break;
        }
    }
    return ($row !== false ? $row['section_id'] : -1);
}

function getChapterId(&$db, $bookId, $chapter): int {
    checkPgConnection($db);
    $columns['id'] = true;
    $criteria['section_id'] = $bookId;
    $criteria['position'] = $chapter;
    $results = getData($db, LOC_SUBSECT_TABLE(), $columns, $criteria);
    $row = pg_fetch_assoc($results);
    return ($row !== false ? $row['id'] : -1);
}

function getVerseId(&$db, $chapterId, $verse): int {
    checkPgConnection($db);
    $columns['id'] = true;
    $criteria['subsection_id'] = $chapterId;
    $criteria['position'] = $verse;
    $results = getData($db, LOC_TABLE(), $columns, $criteria);
    $row = pg_fetch_assoc($results);
    return ($row !== false ? $row['id'] : -1);
}

/**
 * @param resource|null $db
 * @param string $reference Should be the STANDARDIZED reference of the verse
 * @param array $indexArray
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
    if (strpos($reference, "Numbers 13") !== false) {
        //echo "Ref ID: " . $refId . "\n";
    }
    $indexArray[$reference] = $refId;
    return $refId;
}

function getConversionsByDisplayRef($convList) {
    if (count($convList) == 0) return [];
    $columns['loc_id'] = true;
    $columns['display_name'] = true;
    $criteria['id'] = $convList;
    $results = getData($db, LOC_CONV_TABLE(), $columns, $criteria);
    $indexedArray = [];
    while (($row = pg_fetch_assoc($results)) !== false) {
        $indexedArray[$row['display_name']][] = $row['loc_id'];
    }
    return $indexedArray;
}

function free(&$array) {
    $array = null;
    unset($array);
}

function getBookProperName(&$db, $bookId): string {
    checkPgConnection($db);
    $sql = 'SELECT term FROM public.loc_section_term WHERE loc_section_term.id = (SELECT primary_term_id FROM public.loc_section WHERE loc_section.id = $1)';
    $results = pg_query_params($db, $sql, [$bookId]);
    $row = pg_fetch_assoc($results);
    return (($row === false) ? '' : $row['term']);
}

function getStandardizedReference(&$db, $roughReference, &$bookName = '', &$chapterNumber = '', &$verseNumber = ''): string {
    checkPgConnection($db);
    $bookName = '';
    $chapterNumber = '';
    $verseNumber = '';
    $bookAbbr = bookFromReference($roughReference);
    $bookId = getBookId($db, $bookAbbr);
    $bookName = getBookProperName($db, $bookId);
    $cv = refArrayFromReference($roughReference, $bookAbbr, $bookId);
    $chapterNumber = $cv[0];
    $verseNumber = $cv[1];
    $outRef = $bookName . ' ' . $chapterNumber . ':' . $verseNumber;
    return $outRef;
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