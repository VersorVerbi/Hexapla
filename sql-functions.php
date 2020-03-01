<?php

include_once "oldcode/dbconnect.php";

/**
 * @uses checkPgConnection(), is_null(), strlen(), count(), is_numeric(), pg_query_params()
 * @param resource|null $pgConnection Connection to the PostgreSQL database; returns it if not set
 * @param string $tableName Name of the table to get ID rows from
 * @param array $searchCriteria Associative array where the key is the column name and the value is the search string
 * @return false|resource Results of the SQL query; use pg_fetch functions to retrieve individual rows
 */
function getIdRows(&$pgConnection, $tableName, $searchCriteria = []) {
    checkPgConnection($pgConnection);
    if (is_null($tableName) || strlen($tableName) === 0) {
        return null;
    }
    $sql = 'SELECT id FROM public."' . $tableName;
    if (count($searchCriteria) > 0) {
        $i = 1;
        foreach($searchCriteria as $coln => $val) {
            if ($i === 1) {
                $sql .= '" WHERE ';
            } else {
                $sql .= ' AND ';
            }
            if (is_numeric($val)) {
                $sql .= $coln . '=$' . $i;
            } else {
                $sql .= 'UPPER(' . $coln . ')=UPPER($' . $i . ')';
            }
            $i++;
        }
    } else {
        $sql .= '"';
    }
    $sql .= ';';
    $searchResource = pg_query_params($pgConnection, $sql, $searchCriteria);
    return $searchResource;
}

/**
 * @uses checkPgConnection(), is_null(), strlen(), pg_query_params()
 * @param resource|null $pgConnection Connection to the PostgreSQL database; returns it if not set
 * @param string $tableName Name of the table to search
 * @param array $columns Array of column names as strings to get data from
 * @param array $searchCriteria Associative array where the key is the column name and the value is the search string
 * @return false|resource Results of the SQL query; use pg_fetch functions to get individual rows
 */
function getData(&$pgConnection, $tableName, $columns = [], $searchCriteria = []) {
    checkPgConnection($pgConnection);
    if (is_null($tableName) || strlen($tableName) === 0) {
        return null;
    }
    $sql = 'SELECT ';
    $c = 0;
    if (count($columns) == 0) {
        // get all
        $sql .= '*';
    } else {
        foreach ($columns as $coln => $use) {
            if ($c++ !== 0) {
                $sql .= ', ';
            }
            $sql .= pg_escape_identifier($coln);
        }
    }
    $sql .= ' FROM public.' . pg_escape_identifier($tableName);
    if (count($searchCriteria) > 0) {
        $i = 1;
        foreach($searchCriteria as $coln => $value) {
            if ($i++ === 1) {
                $sql .= ' WHERE ';
            } else {
                $sql .= ' AND ';
            }
            if (is_array($value)) {
                $sql .= pg_escape_identifier($coln) . ' IN (';
                $subs = "";
                foreach ($value as $subvalue) {
                    $subs .= ',' . pg_escape_literal($subvalue);
                }
                $sql .= substr($subs,1) . ')';
            } elseif (is_null($value)) {
                $sql .= pg_escape_identifier($coln) . ' IS NULL';
                unset($searchCriteria[$coln]);
            } else {
                $sql .= $coln . '=' . pg_escape_literal($value);
            }
        }
    }
    $sql .= ';';
    $results = pg_query($pgConnection, $sql);
    return $results;
}

/**
 * @uses is_null(), getDbConnection()
 * @param resource|null $pgConnection Either the connection to the PostgreSQL database or null; if null, gets set to that connection
 */
function checkPgConnection(&$pgConnection) {
    if (!$pgConnection || is_null($pgConnection)) {
        $pgConnection = getDbConnection();
    }
}

/**
 * @uses checkPgConnection(), pg_query(), pg_fetch_assoc()
 * @param resource|null $pgConnection Connection to the PostgreSQL database; returns it if not set
 * @param string $tableName Name of the table to get the highest ID from
 * @param string $idColumnName Name of the ID column; 'id' by default
 * @return mixed Highest (assumed most recent) row ID of the given table
 */
function getLastInsertId(&$pgConnection, $tableName, $idColumnName = 'id') {
    checkPgConnection($pgConnection);
    $sql = 'SELECT MAX(' . $idColumnName . ') AS lastid FROM public."' . $tableName . '";';
    $res = pg_query($pgConnection, $sql);
    $row = pg_fetch_assoc($res);
    return $row['lastid'];
}

function getCount(&$pgConnection, $tableName, $searchCriteria = []) {
    checkPgConnection($pgConnection);
    $sql = 'SELECT COUNT(*) AS num_found FROM public."' . $tableName . '"';
    if (count($searchCriteria) > 0) {
        $i = 1;
        foreach ($searchCriteria as $coln => $value) {
            if ($i === 1) {
                $sql .= ' WHERE ';
            } else {
                $sql .= ' AND ';
            }
            if (is_null($value)) {
                $sql .= $coln . ' IS NULL';
            } else {
                $sql .= $coln . '=$' . $i++;
            }
        }
    }
    $sql .= ';';
    $results = pg_query_params($pgConnection, $sql, $searchCriteria);
    return pg_fetch_assoc($results)['num_found'];
}

/**
 * @param resource $db
 * @param string $tableName
 * @param array $insertArray
 * @param string $idColumn
 * @return bool|mixed
 */
function putData(&$db, $tableName, $insertArray, $idColumn = 'id') {
    checkPgConnection($db);
    $sql = 'INSERT INTO public.' . pg_escape_identifier($db, $tableName) . ' (';
    $columns = '';
    $values = '';
    foreach ($insertArray as $column => $value) {
        $columns .= ',' . pg_escape_identifier($db, $column);
        $values .= ',' . pg_escape_literal($db, $value);
    }
    $sql .= substr($columns, 1);
    $sql .= ') VALUES (';
    $sql .= substr($values, 1);
    $sql .= ') RETURNING ' . pg_escape_identifier($db, $idColumn) . ';';
    $result = pg_query($sql);
    if ($result === false) {
        return false;
    } else {
        return pg_fetch_assoc($result)[$idColumn];
    }
}


// TABLE CONSTANTS
function TEXT_VALUE_TABLE(): string {
    return 'text_value';
}

function LANG_DEFN_TABLE(): string {
    return 'lang_definition';
}

function LANG_DICT_TABLE(): string {
    return 'lang_dictionary';
}

function LANG_LEMMA_TABLE(): string {
    return 'lang_lemma';
}

function LANG_PARSE_TABLE(): string {
    return 'lang_parse';
}

function LANG_TABLE(): string {
    return 'language';
}

function LOC_CONV_TEST_TABLE(): string {
    return 'loc_conv_test';
}

function LOC_CONV_USES_TEST_TABLE(): string {
    return 'loc_conv_uses_test';
}

function LOC_CONV_TABLE(): string {
    return 'loc_conversion';
}

function LOC_NS_USES_CONV_TABLE(): string {
    return 'loc_ns_uses_conv';
}

function LOC_NS_TABLE(): string {
    return 'loc_number_system';
}

function LOC_SECTION_TABLE(): string {
    return 'loc_section';
}

function LOC_SECT_TERM_TABLE(): string {
    return 'loc_section_term';
}

function LOC_SUBSECT_TABLE(): string {
    return 'loc_subsection';
}

function LOC_SUBSECT_TERM_TABLE(): string {
    return 'loc_subsection_term';
}

function LOC_TABLE(): string {
    return 'location';
}

function SRC_META_TABLE(): string {
    return 'source_metadata';
}

function SRC_PUBLISH_TABLE(): string {
    return 'source_publisher';
}

function SRC_VERSION_TABLE(): string {
    return 'source_version';
}

function SRC_VERS_SEQ_TABLE(): string {
    return 'source_version_sequence';
}

function USER_TABLE(): string {
    return 'user';
}

function USER_CRED_TABLE(): string {
    return 'user_credential';
}

function USER_GROUP_TABLE(): string {
    return 'user_group';
}

function USER_NOTES_TABLE(): string {
    return 'user_notes';
}

function USER_NOTES_LOC_TABLE(): string {
    return 'user_notes_on_loc';
}

function NOTE_TEXT_TABLE(): string {
    return 'note_text';
}

function NOTE_CROSSREF_TABLE(): string {
    return 'note_reference';
}

function SRC_VERS_TERM_TABLE(): string {
    return 'source_version_term';
}