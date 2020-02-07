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
    $hasArray = false;
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
            $sql .= $coln;
        }
    }
    $sql .= ' FROM public."' . $tableName . '" AS ' . $tableName;
    if (count($searchCriteria) > 0) {
        $i = 1;
        foreach($searchCriteria as $coln => $value) {
            if ($i === 1) {
                $sql .= ' WHERE ';
            } else {
                $sql .= ' AND ';
            }
            if (is_array($value)) {
                $sql .= $coln . ' IN (';
                /** @noinspection PhpUnusedLocalVariableInspection */
                foreach ($value as $subvalue) {
                    $sql .= '$' . $i++;
                    // we don't actually need the subvalues right now, we just want to do this once per value
                }
                $hasArray = true;
            } elseif (is_null($value)) {
                $sql .= $coln . ' IS NULL';
                unset($searchCriteria[$coln]);
            } else {
                $sql .= $coln . '=$' . $i++;
            }
        }
    }
    $sql .= ';';
    // we need to keep SQL replacement values in order in the criteria array
    $newArray = [];
    foreach ($searchCriteria as $criterion) {
        if (is_array($criterion)) {
            foreach ($criterion as $value) {
                $newArray[] = $value;
            }
        } else {
            $newArray[] = $criterion;
        }
    }
    $searchCriteria = $newArray;
    $results = pg_query_params($pgConnection, $sql, $searchCriteria);
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
    $sql = 'SELECT COUNT(*) AS numFound FROM public."' . $tableName . '"';
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
    return pg_fetch_assoc($results)['numFound'];
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