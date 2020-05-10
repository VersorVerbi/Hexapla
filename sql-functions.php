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
 * @return bool|resource
 */
function putData(&$db, $tableName, $insertArray, $idColumn = HexaplaStandardColumns::ID) {
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

#region Database Table & Enum Classes
class HexaplaTables {
    const TEXT_VALUE = 'text_value';
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
    const SOURCE_PUBLISHER = 'source_publisher';
    const SOURCE_VERSION = 'source_version';
    const SOURCE_VERSION_SEQUENCE = 'source_version_sequence';
    const SOURCE_VERSION_TERM = 'source_version_term';
    const USER = 'user';
    const USER_CREDENTIAL = 'user_credential';
    const USER_GROUP = 'user_group';
    const USER_NOTES = 'user_notes';
    const USER_NOTES_LOCATION = 'user_notes_on_loc';
    const NOTE_TEXT = 'note_text';
    const NOTE_CROSSREF = 'note_reference';
}

class HexaplaTests {
    const LAST = 'Last';
    const NOT_EXIST = 'NotExist';
    const EXIST = 'Exist';
    const LESS_THAN = 'LessThan';
    const GREATER_THAN = 'GreaterThan';
}
class HexaplaPunctuation {
    const CLOSING = 'Closing';
    const OPENING = 'Opening';
    const NOT = 'NotPunctuation';
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

class HexaplaTextValue implements HexaplaStandardColumns, HexaplaValueColumns, HexaplaStrongColumns, HexaplaPositionColumns, HexaplaVersionColumns {
    const LOCATION_ID = 'location_id'; // TODO: Standardize this
    const PUNCTUATION = 'punctuation';
}
class HexaplaLangDefinition implements HexaplaStandardColumns, HexaplaLangColumns, HexaplaDefiningColumns, HexaplaLemmaColumns {
    const DICTIONARY_ID = 'dict_id';
}
class HexaplaLangDictionary implements HexaplaStandardColumns, HexaplaLangColumns, HexaplaNameColumns {}
class HexaplaLangLemma implements HexaplaStandardColumns, HexaplaValueColumns, HexaplaDefiningColumns, HexaplaStrongColumns {
    const UNMARKED_VALUE = 'unmarked_value';
    const UNICODE_VALUE = 'unicode_value';
    const UNMARKED_UNICODE_VALUE = 'unmarked_unicode';
}
class HexaplaLangParse implements HexaplaStandardColumns, HexaplaLemmaColumns {
    const MORPH_CODE = 'morph_code';
    const EXPANDED_FROM = 'expanded_from';
    const FORM = 'form';
    const BARE_FORM = 'bare_form';
    const DIALECTS = 'dialects';
    const MISC_FEATURES = 'misc_features';
}
class HexaplaLangStrongs implements HexaplaStandardColumns, HexaplaLemmaColumns {}
class HexaplaLanguage implements  HexaplaStandardColumns, HexaplaNameColumns {}
class HexaplaLocTest implements HexaplaStandardColumns {
    const BOOK_1_NAME = 'book1name';
    const CHAPTER_1_NUM = 'chapter1num';
    const VERSE_1_NUM = 'verse1num';
    const MULTIPLER_1 = 'multiplier1';
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
class HexaplaLocSubsectionTerm implements HexaplaStandardColumns, HexaplaTermColumns {
    const SUBSECTION_ID = 'subId'; //TODO: Standardize this
}
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
class HexaplaSourceTerm implements HexaplaStandardColumns, HexaplaTermColumns {
    const SOURCE_ID = 'sourceId'; // TODO: standardize this
}
class HexaplaSourceVersion implements HexaplaStandardColumns {
    const LANGUAGE_ID = 'langId'; // TODO: Standardize
    const ALLOWS_ACTIONS = 'allowsActions'; // TODO: Reformat this
    const SOURCE_ID = 'sourceId'; // TODO: Standardize
    const USER_ID = 'userId'; // TODO: Standardize
    const COPYRIGHT = 'copyright';
}
class HexaplaSourceVersionSequence implements HexaplaSectionColumns {
    const SEQUENCE_ORDER = 'sequence_order';
}
class HexaplaUser implements HexaplaStandardColumns {
    const NAME = 'username';
    const EMAIL = 'email';
    const PASSWORD = 'password';
    const GROUP_ID = 'groupId'; // TODO: Reformat/standardize
}
class HexaplaUserCredential implements HexaplaStandardColumns {
    const USER_ID = 'userId'; // TODO: Standardize
    const INFO = 'info';
    const DATA = 'data';
}
class HexaplaUserGroup implements HexaplaStandardColumns, HexaplaNameColumns {
    const ALLOWS_ACTIONS = 'allowsBehavior';
}
class HexaplaUserNotes implements HexaplaStandardColumns, HexaplaUserColumns {
    const VALUE = 'note_text'; // TODO: Standardize?
}
class HexaplaUserNotesLocation implements HexaplaLocationColumns {
    const NOTE_ID = 'note_id';
}
#endregion