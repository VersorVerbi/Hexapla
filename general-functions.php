<?php

use JetBrains\PhpStorm\Pure;

/**
 * Given a string, returns a string containing only the numeric digits (0-9)
 * @param string $str
 * @return string
 * @uses preg_replace()
 */
function numbersOnly(string $str): string
{
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
#[Pure] function splitChapterVerse($cv): array {
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
 * @throws HexaplaException
 * @throws HexaplaException
 */
function getLocation(&$db, $reference): int
{
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

#[Pure] function noQuotes($val): string
{
    return trim($val, '"');
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
        if (pg_bool($row[HexaplaLocSectionTerm::IS_PRIMARY])) {
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
 * @param $convList
 * @return array
 */
function getConversionsByDisplayRef($convList): array
{
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
 * @return bool
 */
#[Pure] function rowIsEsther(array $row): bool
{
    return (bookIsEsther($row['book1name']) || bookIsEsther($row['book2name']));
}

/**
 * @param string $bookName name of a book
 * @return bool
 */
#[Pure] function bookIsEsther(string $bookName): bool
{
    return in_array($bookName, array('Esther', 'Esther (Greek)'));
}

/**
 * @param string $bookName either "Esther" or "Esther (Greek)"
 * @return string
 */
function reverseEsther(string $bookName): string
{
    if ($bookName === 'Esther') {
        return 'Esther (Greek)';
    } else {
        return 'Esther';
    }
}

#[Pure] function cvTrim($reducedReference): string
{
    return trim($reducedReference, ".:;, \t\n\r\0\x0B");
}

/**
 * @param array $arr Ideally, array of booleans, but will accept any truthy/falsey array
 * @return int Number of truthy (as opposed to falsey) values in an array
 */
#[Pure] function num_true(array $arr): int
{
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
#[Pure] function array_except(array $array, array $exceptKeys): array
{
    return array_diff_key($array, array_flip($exceptKeys));
}

#[Pure] function hasNoValue($val): bool
{
    if (is_null($val)) return true;
    if (strlen($val) === 0) return true;
    if ($val === 'NULL') return true;
    return false;
}

/**
 * @param string $targetId
 * @param array $versionList
 * @return array|null
 */
function getVersionFromList($targetId, $versionList) {
    foreach ($versionList as $version) {
        if ($version['id'] === $targetId) {
            return $version;
        }
    }
    return null;
}

function makeDraggableVersion($versionObject) {
    return "<div id='" . $versionObject['id'] . "' class='transl' draggable='true' data-lang='" . $versionObject['lang'] ."'>" . $versionObject['terms']['Primary'][0] . (isset($versionObject['terms']['Abbreviation']) ? " (" . $versionObject['terms']['Abbreviation'][0] . ")" : "") . "</div>";
}

function piece($stringList, $delimiter, $target) {
    $list = explode($delimiter, $stringList);
    if (!isset($list[$target - 1])) {
        return null;
    }
    return $list[$target - 1];
}

function inStringList($target, $stringList, $delimiter) {
    $list = explode($delimiter, $stringList);
    return in_array($target, $list);
}

function hebrewTransliterate($hebrewString) {
    // RELATIVE-URL: update this to linux filepaths
    return exec("\"C:\\Program Files\\nodejs\\node\" -e \"console.log(require('C:/xampp/node_modules/hebrew-transliteration').transliterate('" . $hebrewString . "', { isSimple: true }))\"");
}

/**
 * @return string Pattern for identifying word strings in regex (internationally capable)
 */
function wordRegexPattern(): string
{
    return '(?:\p{L}|\p{M}|[\'-])+';
}

/**
 * @return string Pattern for identifying non-word strings (excluding spaces) in regex (internationally capable)
 */
function nonwordRegexPattern(): string
{
    // TODO: account for weird punctuation like 'right quotation apostrophe' instead of regular apostrophe (we remove characters from words based on this regex rather than the one above)
    return '(?:\p{P}|\p{N}+|\p{S})';
}

function debug($variable) {
    echo '<pre>';
    print_r($variable);
    echo '</pre>';
}

function strongsListPattern($strongArray) {
    $patternArray = [];
    foreach($strongArray as $strong) {
        $patternArray[] = '\b' . $strong . '\b';
    }
    return '/' . implode('|', $patternArray) . '/';
}

function toTitleCase($str) {
    $output = [];
    $arr = explode(' ', $str);
    foreach($arr as $word) {
        $newWord = strtoupper($word[0]);
        $newWord .= substr($word, 1);
        $output[] = $newWord;
    }
    return implode(' ', $output);
}