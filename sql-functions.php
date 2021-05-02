<?php

namespace Hexapla;

use JetBrains\PhpStorm\Pure;
use ArrayObject;

require_once "dbconnect.php";
require_once "general-functions.php";
require_once "lib/portable-utf8.php";
require_once "HexaplaException.php";
require_once "betacode-functions.php";
require_once "api-functions.php";

/**
 * @param resource|null $pgConnection Connection to the PostgreSQL database; returns it if not set
 * @param string $tableName Name of the table to get ID rows from
 * @param array $searchCriteria Associative array where the key is the column name and the value is the search string
 * @param string $idColumn
 * @return false|resource Results of the SQL query; use pg_fetch functions to retrieve individual rows
 * @uses checkPgConnection(), is_null(), strlen(), getData()
 */
function getIdRows(&$pgConnection, string $tableName, array $searchCriteria = [], string $idColumn = HexaplaStandardColumns::ID): ?bool
{
    checkPgConnection($pgConnection);
    if (is_null($tableName) || strlen($tableName) === 0) {
        return null;
    }
    return getData($db, $tableName, [$idColumn], $searchCriteria);
}

/**
 * @param resource|null $pgConnection Connection to the PostgreSQL database; returns it if not set
 * @param string $tableName Name of the table to search
 * @param array $columns Array of column names as strings to get data from
 * @param array $searchCriteria Associative array where the key is the column name and the value is the search string
 * @param array $sortColumns
 * @param array $joinData
 * @param bool $stringsUseLike
 * @param bool $allOr
 * @return mixed (false|resource) Results of the SQL query; use pg_fetch functions to get individual rows
 * @uses checkPgConnection(), is_null(), strlen(), pg_query_params()
 */
function getData(&$pgConnection, string $tableName, array $columns = [], array $searchCriteria = [], array $sortColumns = [], array $joinData = [], bool $stringsUseLike = false, bool $allOr = false): mixed
{
    checkPgConnection($pgConnection);
    if (is_null($tableName) || strlen($tableName) === 0) {
        return null;
    }
    $sql = 'SELECT ';
    if (count($columns) == 0) {
        // get all
        $sql .= '*';
    } else {
        $sql .= pg_implode(',', $columns, $pgConnection);
    }
    $sql .= ' FROM public.' . pg_escape_identifier($pgConnection, $tableName);
    if (count($joinData) > 0) {
        foreach ($joinData as $tableJoin) {
            $sql .= ' JOIN ' . pg_escape_identifier($pgConnection, $tableJoin[HexaplaJoin::JOIN_TO]);
            $sql .= ' ON ' . pg_escape_identifier($pgConnection, $tableJoin[HexaplaJoin::ON_LEFT_TABLE]) . '.' . pg_escape_identifier($pgConnection, $tableJoin[HexaplaJoin::ON_LEFT]);
            $sql .= ' = ' . pg_escape_identifier($pgConnection, $tableJoin[HexaplaJoin::ON_RIGHT_TABLE]) . '.' . pg_escape_identifier($pgConnection, $tableJoin[HexaplaJoin::ON_RIGHT]);
        }
    }
    if (count($searchCriteria) > 0) {
        $sql .= buildSearch($pgConnection, $searchCriteria, $allOr, $stringsUseLike);
    }
    if (count($sortColumns) > 0) {
        $i = 1;
        $sql .= ' ORDER BY ';
        foreach($sortColumns as $coln => $direction) {
            if (strlen($direction) === 0) $direction = SortDirection::ASCENDING;
            $direction = ' ' . $direction;
            if ($i++ > 1) {
                $sql .= ', ';
            }
            $sql .= pg_escape_identifier($pgConnection, $coln) . $direction;
        }
    }
    $sql .= ';';
    return pg_query($pgConnection, $sql);
}

/**
 * @param $pgConnection
 * @param array $searchCriteria
 * @param bool $allOr
 * @param bool $stringsUseLike
 * @param bool $valuesEscaped
 * @return string SQL string portion
 */
function buildSearch($pgConnection, array $searchCriteria, bool $allOr, bool $stringsUseLike, bool $valuesEscaped = false): string
{
    $i = 1;
    $sql = '';
    foreach ($searchCriteria as $coln => $value) {
        if ($i++ === 1) {
            $sql .= ' WHERE ';
        } elseif ($allOr) {
            $sql .= ' OR ';
        } else {
            $sql .= ' AND ';
        }
        $sql .= pg_escape_identifier($pgConnection, $coln);
        if (is_array($value)) {
            if (!$stringsUseLike || numericOnly($value)) {
                if ($valuesEscaped) {
                    $sql .= ' IN (' . implode(',', $value) . ')';
                } else {
                    $sql .= ' IN (' . pg_implode(',', $value, $pgConnection, true) . ')';
                }
            } else {
                if ($valuesEscaped) {
                    $sql .= ' LIKE ANY(ARRAY[' . implode(',', $value) . '])';
                } else {
                    $sql .= ' LIKE ANY(ARRAY[' . pg_implode(',', $value, $pgConnection, true) . '])';
                }
            }
        } elseif (is_null($value)) {
            $sql .= ' IS NULL';
        } else {
            if (!$stringsUseLike || numericOnly($value)) {
                $sql .= '=';
                $sql .= ($valuesEscaped ? $value : pg_escape_literal($pgConnection, $value));
            } else {
                $sql .= ' LIKE ';
                $sql .= ($valuesEscaped ? $value : pg_escape_literal($pgConnection, $value));
            }
        }
    }
    return $sql;
}

function pg_escape_identifier($pgConnection, string $coln): string {
    $colTbl = '';
    if (str_contains($coln, '.')) {
        $split = explode('.', $coln);
        $colTbl = $split[0];
        $coln = $split[1];
    }
    return (strlen($colTbl) > 0 ? \pg_escape_identifier($pgConnection, $colTbl) . '.' : '') . \pg_escape_identifier($pgConnection, $coln);
}

/**
 * @uses is_null(), getDbConnection()
 * @param resource|null $pgConnection Either the connection to the PostgreSQL database or null; if null, gets set to that connection
 */
function checkPgConnection(&$pgConnection) {
    if (!$pgConnection) {
        $pgConnection = getDbConnection();
    }
}

/**
 * @param $pgConnection
 * @param string $tableName
 * @param array $searchCriteria
 * @return int
 */
function getCount(&$pgConnection, string $tableName, array $searchCriteria = [], bool $escapedCriteria = false): int {
    checkPgConnection($pgConnection);
    $sql = "SELECT COUNT(*) AS num_found FROM public." . pg_escape_identifier($pgConnection, $tableName);
    if (count($searchCriteria) > 0) {
        $sql .= buildSearch($pgConnection, $searchCriteria, false, false, $escapedCriteria);
    }
    $sql .= ';';
    $results = pg_query($pgConnection, $sql);
    return pg_fetch_assoc($results)['num_found'];
}

/**
 * @param resource $pgConnection
 * @param string $tableName
 * @param array $insertArray
 * @param string|null $idColumn
 * @param bool $escaped
 * @return bool|string
 */
function putData(&$pgConnection, string $tableName, array $insertArray, string|null $idColumn = HexaplaStandardColumns::ID, bool $escaped = false): bool|string
{
    checkPgConnection($pgConnection);
    $sql = 'INSERT INTO public.' . pg_escape_identifier($pgConnection, $tableName) . ' ';
    $columns = '';
    $values = '';
    $columnArray = [];
    if (is_array($starter = reset($insertArray))) {
        foreach($starter as $column => $value) {
            if ($tableName === HexaplaTables::TEXT_VALUE && $column === HexaplaTextStrongs::STRONG_ID) continue;
            $columnArray[] = $column;
        }
        $columns = '(' . pg_implode(',', $columnArray, $pgConnection) . ')';
        foreach ($insertArray as $row) {
            $valueChunk = '';
            foreach ($columnArray as $column) {
                if (isset($row[$column])) {
                    $valueChunk .= ',' . ($escaped ? $row[$column] : pg_escape_literal($row[$column]));
                } else {
                    $valueChunk .= ',NULL';
                }
            }
            $values .= ',(' . substr($valueChunk, 1) . ')';
        }
        $values = substr($values, 1);
    } else {
        foreach ($insertArray as $column => $value) {
            if ($tableName === HexaplaTables::TEXT_VALUE && $column === HexaplaTextStrongs::STRONG_ID) continue;
            $columns .= ',' . pg_escape_identifier($pgConnection, $column);
            $values .= ',' . ($escaped ? $value : pg_escape_literal($pgConnection, $value));
        }
        $columns = '(' . substr($columns, 1) . ')';
        $values = '(' . substr($values, 1) . ')';
    }
    $sql .= $columns;
    $sql .= ' VALUES ';
    $sql .= $values;
    if ($tableName === HexaplaTables::TEXT_STRONGS) {
        $sql .= ' ON CONFLICT DO NOTHING';
    } elseif ($tableName === HexaplaTables::USER_SETTINGS) {
        $sql .= ' ON CONFLICT user_setting_pkey UPDATE SET ' . pg_escape_literal($pgConnection,HexaplaUserSettings::VALUE);
        $sql .= ' = ' . ($escaped ? $insertArray[HexaplaUserSettings::VALUE] : pg_escape_literal($pgConnection, $insertArray[HexaplaUserSettings::VALUE]));
    }
    if (!is_null($idColumn)) {
        $sql .= ' RETURNING ' . pg_escape_identifier($pgConnection, $idColumn) . ';';
    } else {
        $sql .= ';';
    }
    $result = pg_query($sql);
    if ($result === false) {
        return false;
    } elseif (!is_null($idColumn)) {
        if ($tableName === HexaplaTables::TEXT_VALUE) {
            updateStrongs($insertArray, $result, $idColumn);
        }
        pg_result_seek($result, 0);
        return pg_fetch_assoc($result)[$idColumn];
    } else {
        return true;
    }
}

/**
 * @param resource $db
 * @param string $tableName
 * @param array $updates
 * @param array $criteria
 * @param string $idColumn
 * @return mixed (bool|mixed)
 */
function update(&$db, string $tableName, array $updates, array $criteria = [], string $idColumn = HexaplaStandardColumns::ID): mixed
{
    // TODO: handle situation where targeted record does not exist
    checkPgConnection($db);
    $sql = 'UPDATE public.' . pg_escape_identifier($db, $tableName) . ' SET ';
    foreach ($updates as $column => $value) {
        if ($tableName === HexaplaTables::TEXT_VALUE && $column === HexaplaTextStrongs::STRONG_ID) continue;
        $sql .= pg_escape_identifier($db, $column) . '=' . pg_escape_literal($value) . ',';
    }
    $sql = substr($sql, 0, -1);
    if (count($criteria) > 0) {
        $sql .= ' WHERE ';
        foreach ($criteria as $column => $value) {
            if ($tableName === HexaplaTables::TEXT_VALUE && $column === HexaplaTextStrongs::STRONG_ID) continue;
            if (hasNoValue($value)) continue;
            $sql .= pg_escape_identifier($db, $column) . '=' . pg_escape_literal($value) . ' AND ';
        }
        $sql = substr($sql, 0, -5);
    }
    if (!is_null($idColumn)) {
        $sql .= ' RETURNING ' . pg_escape_identifier($db, $idColumn);
    }
    $sql .= ';';
    $result = pg_query($db, $sql);
    if ($result === false) {
        return false;
    } elseif (!is_null($idColumn)) {
        if ($tableName === HexaplaTables::TEXT_VALUE) {
            updateStrongs($updates, $result, $idColumn);
        }
        $resultRow = pg_fetch_assoc($result);
        return ($resultRow !== false ? $resultRow[$idColumn] : true); // "true" in this case means the update was successful but unnecessary
    } else {
        return true;
    }
}

/**
 * @param array $insertArray
 * @param resource $insertUpdateResult
 * @param string $idColumn
 * @param bool $insert
 */
function updateStrongs(array $insertArray, $insertUpdateResult, string $idColumn, bool $insert = true) {
    $strongInserts = [];
    if (isset($insertArray[HexaplaTextStrongs::STRONG_ID]) && strlen($insertArray[HexaplaTextStrongs::STRONG_ID]) > 0) {
        $wordId = pg_fetch_assoc($insertUpdateResult)[$idColumn];
        $strongId = $insertArray[HexaplaTextStrongs::STRONG_ID];
        if (count($strongList = explode(',', $strongId)) > 1) {
            foreach ($strongList as $singleStrong) {
                $insertChunk[HexaplaTextStrongs::TEXT_ID] = $wordId;
                $insertChunk[HexaplaTextStrongs::STRONG_ID] = $singleStrong;
                $strongInserts[] = $insertChunk;
            }
        } else {
            $strongInserts[HexaplaTextStrongs::TEXT_ID] = $wordId;
            $strongInserts[HexaplaTextStrongs::STRONG_ID] = $strongId;
        }
    } elseif (is_array($starter = reset($insertArray))) {
        foreach ($insertArray as $row) {
            $wordId = pg_fetch_assoc($insertUpdateResult)[$idColumn];
            if (isset($row[HexaplaTextStrongs::STRONG_ID]) && strlen($row[HexaplaTextStrongs::STRONG_ID]) > 0) {
                $strongId = $row[HexaplaTextStrongs::STRONG_ID];
                if (count($strongList = explode(',', $strongId)) > 1) {
                    foreach ($strongList as $singleStrong) {
                        $insertChunk[HexaplaTextStrongs::TEXT_ID] = $wordId;
                        $insertChunk[HexaplaTextStrongs::STRONG_ID] = $singleStrong;
                        $strongInserts[] = $insertChunk;
                    }
                } else {
                    $insertChunk[HexaplaTextStrongs::TEXT_ID] = $wordId;
                    $insertChunk[HexaplaTextStrongs::STRONG_ID] = $strongId;
                    $strongInserts[] = $insertChunk;
                }
            }
        }
    }
    if (count($strongInserts) > 0) {
        if ($insert) {
            putData($db, HexaplaTables::TEXT_STRONGS, $strongInserts, null);
        } else {
            //TODO: delete existing data in Text_Strongs
            //TODO: reinsert new data
            putData($db, HexaplaTables::TEXT_STRONGS, $strongInserts, null);
        }
    }
}

/**
 * @param $db
 * @param $reference
 * @param $translations
 * @param $alternatives
 * @param $title
 * @return mixed (false|resource|null)
 */
function fullSearch(&$db, $reference, $translations, &$alternatives, &$title): mixed
{
    checkPgConnection($db);
    $alternatives = [];
    $title = '';
    $resolutionSql = "SELECT public.resolve_reference('$reference');";
    $resolutionResult = pg_query($db, $resolutionSql);
    if (($resolRow = pg_fetch_assoc($resolutionResult)) !== false) {
        $data = resolveMore($resolRow['resolve_reference']);
        // [0: book_id, 1: chapter_start, 2: chapter_end, 3: verse_start, 4: verse_end, 5: display_start, 6: display_end, 7: priority]
        $searchSql = "SELECT public.full_search(" . pg_escape_literal($db, $data[0]) .
            ',' . pg_escape_literal($db, $data[1]) . ',' . pg_escape_literal($db, $data[2]) .
            ',' . pg_escape_literal($db, $data[3]) . ',' . pg_escape_literal($db, $data[4]) .
            ',' . pg_escape_literal($db, $data[5]) . ',' . pg_escape_literal($db, $data[6]) .
            ',' . pg_escape_literal($db, $translations) . ');';
        $searchResult = pg_query($db, $searchSql);
        $title = $data[5];
        if ($data[2] > $data[1]) {
            $title .= '-' . $data[2] . ':' . $data[4];
        } elseif ($data[4] > $data[3]) {
            $title .= '-' . $data[4];
        }
    } else {
        return null;
    }
    while (($resolRow = pg_fetch_assoc($resolutionResult)) !== false) {
        $alternatives[] = resolveMore($resolRow['resolve_reference']);
    }
    return $searchResult;
}

/**
 * @param string $resolve_reference
 * @return array
 */
#[Pure] function resolveMore(string $resolve_reference): array
{
    return str_getcsv(trim(trim($resolve_reference, '('), ')'));
}

/**
 * @param resource $db
 * @return array
 */
function getVersions(&$db): array
{
    checkPgConnection($db);
    begin($db);
    pg_query($db, "SELECT translation_list('tcursor');");
    $result = pg_query($db, "FETCH ALL IN tcursor;");
    commit($db);
    if ($result === false) {
        return [];
    }
    $translations = [];
    while (($row = pg_fetch_assoc($result)) !== false) {
        $row['terms'] = terms_array($row['terms']);
        $translations[] = $row;
    }
    return $translations;
}

function getVersionData(&$db, $tList) {
    checkPgConnection($db);
    $results = [];
    $termResource = getData($db,
        HexaplaTables::SOURCE_VERSION_TERM,
        [HexaplaSourceVersionTerm::VERSION_ID, HexaplaSourceVersionTerm::TERM],
        [HexaplaSourceVersionTerm::VERSION_ID => $tList, HexaplaSourceVersionTerm::FLAG => HexaplaTermFlag::PRIMARY]);
    while (($row = pg_fetch_assoc($termResource)) !== false) {
        $results[$row[HexaplaSourceVersionTerm::VERSION_ID]]['term'] = $row[HexaplaSourceVersionTerm::TERM];
    }
    $dataResource = getData($db, HexaplaTables::SOURCE_VERSION,
        [HexaplaTables::SOURCE_VERSION . '.' . HexaplaSourceVersion::ID, HexaplaSourceVersion::ALLOWS_ACTIONS, HexaplaSourceVersion::LANGUAGE_ID, HexaplaLanguage::NAME],
        [HexaplaTables::SOURCE_VERSION . '.' . HexaplaSourceVersion::ID => $tList],
        [],
        [new HexaplaJoin(HexaplaTables::LANGUAGE,
            HexaplaTables::LANGUAGE, HexaplaLanguage::ID,
            HexaplaTables::SOURCE_VERSION, HexaplaSourceVersion::LANGUAGE_ID)]);
    while (($row = pg_fetch_assoc($dataResource)) !== false) {
        $results[$row[HexaplaSourceVersion::ID]]['perm'] = $row[HexaplaSourceVersion::ALLOWS_ACTIONS];
        $results[$row[HexaplaSourceVersion::ID]]['lang'] = $row[HexaplaSourceVersion::LANGUAGE_ID];
        $results[$row[HexaplaSourceVersion::ID]]['langName'] = $row[HexaplaLanguage::NAME];
    }
    return $results;
}

function getLanguageOfVersion(&$db, $versionId) {
    checkPgConnection($db);
    $langResource = getData($db, HexaplaTables::SOURCE_VERSION, [HexaplaSourceVersion::LANGUAGE_ID], [HexaplaSourceVersion::ID => $versionId]);
    $langRow = pg_fetch_assoc($langResource);
    if ($langRow === null) {
        return null;
    }
    return $langRow[HexaplaSourceVersion::LANGUAGE_ID];
}

function getStrongsDefinition(&$db, $strongArray): array {
    $definitions = [];
    checkPgConnection($db);
    $result = getData($db, HexaplaTables::LANG_DEFINITION,
        [HexaplaLangLemma::STRONG_ID, HexaplaLangLemma::UNICODE_VALUE, HexaplaTables::LANG_DEFINITION . '.' . HexaplaLangDefinition::DEFINITION],
        [HexaplaLangLemma::STRONG_ID => array_unique($strongArray)],
        [],
        [new HexaplaJoin(HexaplaTables::LANG_LEMMA,
            HexaplaTables::LANG_DEFINITION, HexaplaLangDefinition::LEMMA_ID,
            HexaplaTables::LANG_LEMMA, HexaplaLangLemma::ID)]);
    while (($row = pg_fetch_assoc($result)) !== false) {
        $definitions[$row[HexaplaLangLemma::STRONG_ID]]['lemma'] = $row[HexaplaLangLemma::UNICODE_VALUE];
        $definitions[$row[HexaplaLangLemma::STRONG_ID]]['defn'] = $row[HexaplaLangDefinition::DEFINITION];
    }
    return $definitions;
}

function getLiteralDefinition(&$db, $wordArray, $langId): array {
    //TODO: can we transition this logic into a PostgreSQL function? Too frequent code updates to handle new data here
    checkPgConnection($db);

    $definitions = [];
    $wordArray = array_unique($wordArray);
    $betacode = [];
    $itsRoman = $itsGreek = false;
    $targetTable = '';
    $targetColumns = [HexaplaLangParse::FORM, HexaplaLangParse::EXPANDED_FORM, HexaplaLangParse::BARE_FORM,
        HexaplaTables::LANG_LEMMA . "." . HexaplaLangLemma::MAX_OCCURRENCES,
        HexaplaTables::LANG_LEMMA . "." . HexaplaLangLemma::DOCUMENT_COUNT,
        HexaplaTables::LANG_LEMMA . "." . HexaplaLangLemma::ID];
    $joinData = [];
    if (in_array($langId, ['1','3'])) {
        $itsRoman = true;
        $targetTable = HexaplaTables::LANG_LEMMA;
        $targetColumns[] = HexaplaLangLemma::UNMARKED_VALUE;
        $targetColumns[] = HexaplaTables::LANG_LEMMA . "." . HexaplaLangLemma::DEFINITION;
    } elseif ($langId == '2') {
        $itsGreek = true;
        $targetTable = HexaplaTables::LANG_DEFINITION;
        $targetColumns[] = HexaplaLangLemma::UNICODE_VALUE;
        $targetColumns[] = HexaplaTables::LANG_DEFINITION . "." . HexaplaLangDefinition::DEFINITION;
        $joinData[] = new HexaplaJoin(HexaplaTables::LANG_LEMMA,
            HexaplaTables::LANG_DEFINITION, HexaplaLangDefinition::LEMMA_ID,
            HexaplaTables::LANG_LEMMA, HexaplaLangLemma::ID);
    } // else...
    $joinData[] = new HexaplaJoin(HexaplaTables::LANG_PARSE,
        HexaplaTables::LANG_LEMMA, HexaplaLangLemma::ID,
        HexaplaTables::LANG_PARSE, HexaplaLangParse::LEMMA_ID);
    $sortData = [HexaplaLangLemma::MAX_OCCURRENCES => SortDirection::DESCENDING,
        HexaplaLangLemma::DOCUMENT_COUNT => SortDirection::DESCENDING,
        HexaplaLangLemma::ID => SortDirection::ASCENDING];
    if ($itsGreek) {
        for ($w = 0; $w < count($wordArray); $w++) {
            $betacode[$w] = utf8_strtolower(uniString2Betacode($wordArray[$w]));
        }
    } else {
        $betacode = $wordArray;
    }
    // FIXME: add lang id to parse table and filter by that
    $searchCriteria = [HexaplaLangParse::FORM => $betacode, HexaplaLangParse::BARE_FORM => $betacode, HexaplaLangParse::EXPANDED_FORM => $betacode];
    $result = getData($db, $targetTable, $targetColumns, $searchCriteria, $sortData, $joinData, true, true);
    while (($row = pg_fetch_assoc($result)) !== false) {
        // find key
        $lemma = ($itsRoman ? $row[HexaplaLangLemma::UNMARKED_VALUE] : $row[HexaplaLangLemma::UNICODE_VALUE]);
        for ($w = 0; $w < count($betacode); $w++) {
            if (in_array($betacode[$w], [$row[HexaplaLangParse::FORM], $row[HexaplaLangParse::BARE_FORM], $row[HexaplaLangParse::EXPANDED_FORM]])) {
                if (!array_key_exists($wordArray[$w], $definitions)) {
                    $definitions[$wordArray[$w]]['lemma'] = $lemma;
                    $definitions[$wordArray[$w]]['defn'] = $row[HexaplaLangDefinition::DEFINITION];
                } else {
                    $definitions[$wordArray[$w]]['alternates'][] = [$lemma, $row[HexaplaLangDefinition::DEFINITION]];
                }
            }
        }
    }
    for ($w = 0; $w < count($wordArray); $w++) {
        if (!array_key_exists($wordArray[$w], $definitions)) {
            $definitions[$wordArray[$w]] = getDefinitionAPI($wordArray[$w], $langId);
        }
    }
    return $definitions;
}

function getStrongsCrossRefs(&$db, $strongData, $translId): array {
    checkPgConnection($db);
    $strongArray = array_keys($strongData);
    $verses = [];
    $query = "SELECT get_strong_cross_refs(" . pg_implode(',', $strongArray, $db, true) . "," . pg_escape_literal($db, $translId) . ");";
    $results = pg_query($db, $query);
    // structure: [text_id, text_position, text_value, location_id, punctuation, strongs_list, reference]
    while (($row = pg_fetch_row($results)) !== false) {
        $result = resolveMore($row[0]);
        $verses[$result[6]][$result[1]] = [$result[2], $result[4], $result[5]];
        if (preg_match(strongsListPattern($strongArray), ';' . $result[5] . ';')) {
            $verses[$result[6]]['target'][] = $result[1];
        }
    }
    return verseListToCrossRefs($verses);
}

function getLemmaDB(&$db, $form, $langId) {
    $sql = "SELECT " . pg_escape_identifier($db, HexaplaLangParse::LEMMA_ID) . " FROM " .
        "public." . pg_escape_identifier($db, HexaplaTables::LANG_PARSE) . " JOIN " .
        "public." . pg_escape_identifier($db, HexaplaTables::LANG_LEMMA) . " ON " .
        pg_escape_identifier($db, HexaplaTables::LANG_LEMMA . "." . HexaplaLangLemma::ID) . " = " .
        pg_escape_identifier($db, HexaplaTables::LANG_PARSE . "." . HexaplaLangParse::LEMMA_ID) . " WHERE " .
        "(" . pg_escape_identifier($db, HexaplaLangParse::EXPANDED_FORM) . " ILIKE " . pg_escape_literal($form) . " OR " .
        pg_escape_identifier($db, HexaplaLangParse::FORM) . " ILIKE " . pg_escape_literal($form) . " OR " .
        pg_escape_identifier($db, HexaplaLangParse::BARE_FORM) . " ILIKE " . pg_escape_literal($form) . ") AND " .
        pg_escape_identifier($db, HexaplaTables::LANG_LEMMA . "." . HexaplaLangLemma::LANGUAGE_ID) . " = " .
        pg_escape_literal($langId) . " ORDER BY " . pg_escape_identifier($db, HexaplaLangLemma::MAX_OCCURRENCES) . " DESC, " .
        pg_escape_identifier($db, HexaplaLangLemma::DOCUMENT_COUNT) . " DESC, " .
        pg_escape_identifier($db, HexaplaLangParse::LEMMA_ID) . " ASC LIMIT 1;";
    return pg_query($db, $sql);
}

function getLiteralCrossRefs(&$db, $wordArray, $langId, $translId): array {
    $crossRefs = $lemmas = $variants = $results = [];
    $res = null;
    checkPgConnection($db);
    if ($langId == '1') { // English
        foreach($wordArray as $word) {
            $lemmas[$word] = getLemmaAPI($word, $langId);
            $variants[$word] = getInflectionsAPI($lemmas[$word], $langId);
            $variantList = implode(';', $variants[$word]);
            $sql = "SELECT get_literal_cross_refs(" . pg_escape_literal($db, $word) . "," .
                pg_escape_literal($db, $translId) . "," . pg_escape_literal($db, $langId) . "," .
                pg_escape_literal($db, -1) . "," . pg_escape_literal($db, $variantList) . ");";
            $res = pg_query($db, $sql);
            // TODO: how to identify lemma-based words when we don't have lemma IDs?
        }
    } elseif ($langId == '2') { // Greek
        foreach($wordArray as $word) {
            $lemmaResult = getLemmaDB($db, uniString2Betacode($word), $langId);
            if (($lemmaRow = pg_fetch_assoc($lemmaResult)) !== false) {
                $lemmas[$word] = $lemmaRow[HexaplaLangParse::LEMMA_ID];
            } else {
                // TODO: throw error
                throw new HexaplaException('dat done failed yo');
            }
            $variantResult = getData($db, HexaplaTables::LANG_PARSE,
                [HexaplaLangParse::FORM],
                [HexaplaLangParse::LEMMA_ID => $lemmas[$word]]);
            while (($variantRow = pg_fetch_assoc($variantResult)) !== false) {
                $variants[$word][] = betaString2Unicode($variantRow[HexaplaLangParse::FORM]);
            }
            $variantList = implode(';', $variants[$word]);
            $sql = "SELECT get_literal_cross_refs(" . pg_escape_literal($db, $word) . "," .
                pg_escape_literal($db, $translId) . "," . pg_escape_literal($db, $langId) . "," .
                pg_escape_literal($db, $lemmas[$word]) . "," . pg_escape_literal($db, $variantList) . ");";
            $res = pg_query($db, $sql);
        }
    } else { // everything else
        foreach($wordArray as $word) {
            $sql = "SELECT get_literal_cross_refs(" . pg_escape_literal($db, $word) . "," .
                pg_escape_literal($db, $translId) . "," . pg_escape_literal($db, $langId) . ");";
            $res = pg_query($db, $sql);
        }
    }
    if (!is_null($res)) {
        // structure: [text_id, text_position, text_value, location_id, punctuation, lemma_id | null, reference]
        while (($row = pg_fetch_row($res)) !== false) {
            $result = resolveMore($row[0]);
            $verses[$result[6]][$result[1]] = [$result[2], $result[4], $result[5]];
            if (utf8_strlen($result[5]) > 0) {
                $verses[$result[6]]['target'][] = $result[1];
            }
        }
        $crossRefs = verseListToCrossRefs($verses);
    }

    // get lemmas
    // get variants
    // get locations of all variants
    return $crossRefs;
}

/**
 * @param array $verses
 * @param array $crossRefs
 * @return array
 */
function verseListToCrossRefs(array $verses): array
{
    $crossRefs = [];
    foreach ($verses as $ref => $verse) {
        foreach ($verse['target'] as $targetPosition) {
            $crossRef = [];
            $pos = max([$targetPosition - 4, 0]);
            $wordCount = 0;

            while ($verse[$pos][1] !== HexaplaPunctuation::NOT && $pos > 0) {
                if ($pos < 0) die();
                $pos--;
            }
            while ($wordCount < 7) {
                if (!isset($verse[$pos])) break;
                $crossRef[$pos] = $verse[$pos];
                if ($verse[$pos][1] === HexaplaPunctuation::NOT) $wordCount++;
                $pos++;
            }
            $crossRef['target'] = $targetPosition;
            $crossRef['ref'] = $ref;
            $crossRefs[] = $crossRef;
        }
    }
    return $crossRefs;
}

#[Pure] function numericOnly($val): bool {
    if (is_array($val)) {
        foreach ($val as $item) {
            if (!is_numeric($item)) {
                return false;
            }
        }
        return true;
    } else {
        return is_numeric($val);
    }
}

/**
 * @param resource $db
 */
function begin($db) {
    checkPgConnection($db);
    pg_query($db, "BEGIN");
}

/**
 * @param resource $db
 */
function commit($db) {
    checkPgConnection($db);
    pg_query($db, "COMMIT");
}

/**
 * @param string $glue
 * @param array $array
 * @param resource $db
 * @param boolean $literal
 * @return string
 */
function pg_implode(string $glue, array $array, $db, $literal = false): string
{
    checkPgConnection($db);
    if ($literal) {
        $formattedArray = array_map(function ($item) use ($db) {
            return pg_escape_literal($db, $item);
        }, $array);
    } else {
        $formattedArray = array_map(function ($item) use ($db) {
            return pg_escape_identifier($db, $item);
        }, $array);
    }
    return implode($glue, $formattedArray);
}

/**
 * @param string $value
 * @return bool
 */
#[Pure] function pg_bool(string $value): bool
{ // maybe this way we'll one day be able to improve PostgreSQL to return actual booleans
    return is_bool($value) ? $value : ($value === 't');
}

/**
 * @param string $aggregateString
 * @return array
 */
function pg_decode_array(string $aggregateString): array
{
    $aggregateString = trim($aggregateString, '{}');
    $inQuote = false;
    $decoded = [];
    $d = 0;
    $decoded[$d] = '';
    $aggCharArray = utf8_split($aggregateString);
    for ($c = 0; $c < count($aggCharArray); $c++) {
        switch($aggCharArray[$c]) {
            case '"':
                if ($inQuote) $inQuote = false;
                else $inQuote = true;
                break;
            case ',':
                if ($inQuote) $decoded[$d] .= $aggCharArray[$c];
                else $decoded[++$d] = '';
                break;
            default:
                $decoded[$d] .= $aggCharArray[$c];
                break;
        }
    }
    return $decoded;
}

/**
 * @param string $aggregateString
 * @return array
 */
function terms_array(string $aggregateString): array
{
    $output = [];
    $aggArray = pg_decode_array($aggregateString);
    foreach ($aggArray as $value) {
        $split = explode('|', $value);
        $type = $split[1];
        $val = $split[0];
        $output[$type][] = $val;
    }
    ksort($output, SORT_LOCALE_STRING);
    return $output;
}

/**
 * @param $db
 * @param string $email
 * @param string $password
 * @return bool
 */
function login($db, string $email, string $password): bool {
    checkPgConnection($db);
    // TODO: reimplement after adding (1) checkbox to stay logged in, (2) complete login_cookie system
    $result = getCount($db,
        HexaplaTables::USER,
        [HexaplaUser::EMAIL => pg_escape_literal($email),
            HexaplaUser::PASSWORD => "crypt(" . pg_escape_literal($password) . ")"],
        true);
    // TODO: put id, login byte string into login_cookie  $token = bin2hex(random_bytes(20));
    return $result > 0;
}

// TODO: forgot login => bin2hex(random_bytes(10)) => update password w/ crypt, update "list of generated pws" w/ crypt, force reset when logging in with generated password

/**
 * @param $db
 * @param $email
 * @param $password
 * @return int
 */
function register($db, $email, $password): int {
    checkPgConnection($db);
    return putData($db,
        HexaplaTables::USER,
        [HexaplaUser::EMAIL => pg_escape_literal($db, $email),
            HexaplaUser::PASSWORD => "crypt(" . pg_escape_literal($db, $password) . ", gen_salt('bf'))"],
            //HexaplaUser::GROUP_ID => ???]);
        HexaplaUser::ID, true);
}

#region Database Table & Enum Classes
class HexaplaJoin extends ArrayObject {
    const JOIN_TO = 'table';
    const ON_LEFT_TABLE = 'leftTbl';
    const ON_LEFT = 'left';
    const ON_RIGHT_TABLE = 'rightTbl';
    const ON_RIGHT = 'right';

    function __construct($joinTo, $leftTable, $onLeft, $rightTable, $onRight)
    {
        $array = [self::JOIN_TO => $joinTo,
            self::ON_LEFT_TABLE => $leftTable, self::ON_LEFT => $onLeft,
            self::ON_RIGHT_TABLE => $rightTable, self::ON_RIGHT => $onRight];
        $flags = 0;
        $iteratorClass = "ArrayIterator";
        parent::__construct($array, $flags, $iteratorClass);
    }
}

class HexaplaTables {
    const TEXT_VALUE = 'text_value';
    const TEXT_STRONGS = 'text_strongs';
    const LANG_DEFINITION = 'lang_definition';
    const LANG_DICTIONARY = 'lang_dictionary';
    const LANG_LEMMA = 'lang_lemma';
    const LANG_PARSE = 'lang_parse';
    const LANGUAGE = 'language';
    const LOC_TEST = 'loc_conv_test';
    const LOC_CONV_USES_TEST = 'loc_conv_uses_test';
    const LOC_CONVERSION = 'loc_conversion';
    const LOC_NUMSYS_USES_CONV = 'loc_ns_uses_conv';
    const LOC_NUMBER_SYSTEM = 'loc_number_system';
    const LOC_SECTION = 'loc_section';
    const LOC_SECTION_TERM = 'loc_section_term';
    const LOC_SUBSECTION = 'loc_subsection';
    const LOC_SUBSECTION_TERM = 'loc_subsection_term';
    const LOCATION = 'location';
    const SOURCE_METADATA = 'source_metadata';
    const SOURCE_TERM = 'source_term';
    const SOURCE_PUBLISHER = 'source_publisher';
    const SOURCE_VERSION = 'source_version';
    const SOURCE_VERSION_SEQUENCE = 'source_version_sequence';
    const SOURCE_VERSION_TERM = 'source_version_term';
    const USER = 'user';
    const USER_CREDENTIAL = 'user_credential';
    const USER_GROUP = 'user_group';
    const USER_LOGIN_COOKIES = 'user_login_cookies';
    const USER_NOTES = 'user_notes';
    const USER_NOTES_LOCATION = 'user_notes_on_loc';
    const USER_SETTINGS = 'user_settings';
    const NOTE_TEXT = 'note_text';
    const NOTE_CROSSREF = 'note_reference';
}

class HexaplaTests {
    const LAST = 'Last';
    const NOT_EXIST = 'NotExist';
    const EXIST = 'Exist';
    const LESS_THAN = 'LessThan';
    const GREATER_THAN = 'GreaterThan';

    /**
     * @param $testType
     * @return string
     * @throws NoOppositeTypeException
     */
    static public function opposite($testType): string
    {
        switch($testType) {
            case HexaplaTests::LAST:
                throw new NoOppositeTypeException("", 0, null, get_defined_vars());
            case HexaplaTests::NOT_EXIST:
                return HexaplaTests::EXIST;
            case HexaplaTests::EXIST:
                return HexaplaTests::NOT_EXIST;
            case HexaplaTests::GREATER_THAN:
                return HexaplaTests::LESS_THAN;
            case HexaplaTests::LESS_THAN:
                return HexaplaTests::GREATER_THAN;
            default:
                throw new NoOppositeTypeException("", 0, null, get_defined_vars());
        }
    }
}
class HexaplaPunctuation {
    const CLOSING = 'Closing';
    const OPENING = 'Opening';
    const NOT = 'NotPunctuation';
}

class HexaplaSettings {
    const DEFAULT_LOAD = 'DefaultLoad';
    const SAVED_TLS = 'SavedTranslations';
    const DIFF_BY_WORD = 'DiffByWord';
    const CASE_SENS_DIFF = 'CaseSensitive';
    const SCROLL_TOGETHER = 'ScrollTogether';
    const PIN_SIDEBAR = 'PinSidebar';
}

class HexaplaTermFlag {
    const NONE = 'NoFlag';
    const PRIMARY = 'Primary';
    const ABBREVIATION = 'Abbreviation';
}

class SortDirection {
    const ASCENDING = 'ASC';
    const DESCENDING = 'DESC';
}

class NoOppositeTypeException extends HexaplaException {
}

class LangDirection {
    const LTR = 'ltr';
    const RTL = 'rtl';
}

class HexaplaPermissions {
    const NOTE = 1;
    const DIFF = 2;
    const FOCUS = 4;
    const UPLOAD = 8;
    const PARSE = 16;
}
#endregion

#region Database Column Classes
interface HexaplaStandardColumns {
    const ID = 'id';
}
interface HexaplaLangColumns {
    const LANGUAGE_ID = 'lang_id';
}
interface HexaplaDefiningColumns {
    const DEFINITION = 'definition';
}
interface HexaplaValueColumns {
    const VALUE = 'value';
}
interface HexaplaStrongColumns {
    const STRONG_ID = 'strong_id';
}
interface HexaplaLemmaColumns {
    const LEMMA_ID = 'lemma_id';
}
interface HexaplaNameColumns {
    const NAME = 'name';
}
interface HexaplaTestColumns {
    const TEST_ID = 'test_id';
}
interface HexaplaConversionColumns {
    const CONVERSION_ID = 'conversion_id';
}
interface HexaplaLocationColumns {
    const LOCATION_ID = 'loc_id';
}
interface HexaplaNumberSystemColumns {
    const NUMBER_SYSTEM_ID = 'ns_id';
}
interface HexaplaPositionColumns {
    const POSITION = 'position';
}
interface HexaplaSourceColumns {
    const SOURCE_ID = 'source_id';
}
interface HexaplaSectionColumns {
    const SECTION_ID = 'section_id';
}
interface HexaplaTermColumns {
    const TERM = 'term';
}
interface HexaplaSubsectionColumns {
    const SUBSECTION_ID = 'subsection_id';
}
interface HexaplaVersionColumns {
    const VERSION_ID = 'version_id';
}
interface HexaplaUserColumns {
    const USER_ID = 'user_id';
}

interface HexaplaActionColumns {
    const ALLOWS_ACTIONS = 'allows_actions';
}

class HexaplaTextStrongs implements HexaplaStrongColumns {
    const TEXT_ID = 'text_id';
}

class HexaplaTextValue implements HexaplaStandardColumns, HexaplaValueColumns, HexaplaPositionColumns, HexaplaVersionColumns, HexaplaLocationColumns {
    const PUNCTUATION = 'punctuation';
}
class HexaplaLangDefinition implements HexaplaStandardColumns, HexaplaLangColumns, HexaplaDefiningColumns, HexaplaLemmaColumns {
    const DICTIONARY_ID = 'dict_id';
}
class HexaplaLangDictionary implements HexaplaStandardColumns, HexaplaLangColumns, HexaplaNameColumns {}
class HexaplaLangLemma implements HexaplaStandardColumns, HexaplaValueColumns, HexaplaDefiningColumns, HexaplaStrongColumns, HexaplaLangColumns {
    const UNMARKED_VALUE = 'unmarked_value';
    const UNICODE_VALUE = 'unicode_value';
    const UNMARKED_UNICODE_VALUE = 'unmarked_unicode';
    const MAX_OCCURRENCES = 'max_occ';
    const DOCUMENT_COUNT = 'doc_count';
    const IDF = 'idf';
}
class HexaplaLangParse implements HexaplaStandardColumns, HexaplaLemmaColumns {
    const MORPH_CODE = 'morph_code';
    const EXPANDED_FORM = 'expanded_form';
    const FORM = 'form';
    const BARE_FORM = 'bare_form';
    const DIALECTS = 'dialects';
    const MISC_FEATURES = 'misc_features';
}
class HexaplaLangStrongs implements HexaplaStandardColumns, HexaplaLemmaColumns {}
class HexaplaLanguage implements  HexaplaStandardColumns, HexaplaNameColumns {
    const DIRECTION = 'direction';
}
class HexaplaLocTest implements HexaplaStandardColumns {
    const BOOK_1_NAME = 'book1name';
    const CHAPTER_1_NUM = 'chapter1num';
    const VERSE_1_NUM = 'verse1num';
    const MULTIPLIER_1 = 'multiplier1';
    const TEST_TYPE = 'testtype';
    const BOOK_2_NAME = 'book2name';
    const CHAPTER_2_NUM = 'chapter2num';
    const VERSE_2_NUM = 'verse2num';
    const MULTIPLIER_2 = 'multiplier2';
}
class HexaplaLocConvUsesTest implements HexaplaConversionColumns, HexaplaTestColumns {
    const REVERSED = 'reversed';
}
class HexaplaConversion implements HexaplaStandardColumns, HexaplaLocationColumns {
    const DISPLAY_NAME = 'display_name';
}
class HexaplaNumSysUsesConv implements HexaplaConversionColumns, HexaplaNumberSystemColumns {}
class HexaplaNumberSystem implements HexaplaStandardColumns, HexaplaNameColumns {}
class HexaplaLocSection implements HexaplaStandardColumns, HexaplaPositionColumns, HexaplaSourceColumns {
    const PRIMARY_TERM_ID = 'primary_term_id';
}
class HexaplaLocSectionTerm implements HexaplaStandardColumns, HexaplaSectionColumns, HexaplaTermColumns {
    const IS_PRIMARY = 'is_primary';
}
class HexaplaLocSubsection implements HexaplaStandardColumns, HexaplaPositionColumns, HexaplaSectionColumns {}
class HexaplaLocSubsectionTerm implements HexaplaStandardColumns, HexaplaTermColumns, HexaplaSubsectionColumns {}
class HexaplaLocation implements HexaplaStandardColumns, HexaplaPositionColumns, HexaplaSubsectionColumns {}
class HexaplaNoteCrossRef implements HexaplaStandardColumns, HexaplaLocationColumns, HexaplaVersionColumns {
    const REFERENCE_ID = 'ref_id';
}
class HexaplaNoteText implements HexaplaStandardColumns, HexaplaLocationColumns, HexaplaVersionColumns {
    const VALUE = 'note'; // TODO: Standardize this
}
class HexaplaSourceMetadata implements HexaplaStandardColumns {
    const DATE = 'date';
    const AUTHOR = 'author';
    const TITLE = 'title';
}
class HexaplaSourcePublisher implements HexaplaStandardColumns, HexaplaNameColumns {}
class HexaplaSourceTerm implements HexaplaStandardColumns, HexaplaTermColumns, HexaplaSourceColumns {}
class HexaplaSourceVersion implements HexaplaStandardColumns, HexaplaUserColumns, HexaplaLangColumns, HexaplaActionColumns, HexaplaSourceColumns, HexaplaNumberSystemColumns {
    const PUBLISHER_ID = 'publisher_id';
    const COPYRIGHT = 'copyright';
}
class HexaplaSourceVersionSequence implements HexaplaSectionColumns {
    const SEQUENCE_ORDER = 'sequence_order';
}
class HexaplaSourceVersionTerm implements HexaplaStandardColumns, HexaplaVersionColumns, HexaplaTermColumns {
    const FLAG = 'flag';
}
class HexaplaUser implements HexaplaStandardColumns {
    const EMAIL = 'email';
    const PASSWORD = 'password';
    const GROUP_ID = 'group_id';
}
class HexaplaUserCredential implements HexaplaStandardColumns, HexaplaUserColumns {
    const INFO = 'info';
    const DATA = 'data';
}
class HexaplaUserGroup implements HexaplaStandardColumns, HexaplaNameColumns {
    const ALLOWS_ACTIONS = 'allowsBehavior';
}
class HexaplaUserLoginCookies implements HexaplaUserColumns {
    const COOKIE = 'cookie_string';
}
class HexaplaUserNotes implements HexaplaStandardColumns, HexaplaUserColumns {
    const VALUE = 'note_text'; // TODO: Standardize?
}
class HexaplaUserNotesLocation implements HexaplaLocationColumns {
    const NOTE_ID = 'note_id';
}
class HexaplaUserSettings implements HexaplaUserColumns, HexaplaValueColumns {
    const SETTING = 'setting';

    const PKEY = [self::USER_ID, self::SETTING];
}
#endregion