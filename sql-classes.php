<?php

namespace Hexapla;

use ArrayObject;

require_once "HexaplaException.php";

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
    const USER_SETTINGS = 'user_setting';
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
    const DIFF = 1;
    const FOCUS = 2;
    const NOTE = 4;
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
    const EXPIRES = 'expires';
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