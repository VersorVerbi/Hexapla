<?php
require_once "sql-functions.php";
require_once "general-functions.php";

$sourceWords = json_decode($_POST['sourceWords']);
$translationId = $_POST['tid'];
$text = $_POST['text'];

$strongsResource = getData($db, HexaplaTables::LANG_LEMMA, [HexaplaLangLemma::STRONG_ID, HexaplaLangLemma::UNICODE_VALUE], [HexaplaLangLemma::STRONG_ID => $sourceWords]);
if ($strongsResource === false || ($row = pg_fetch_assoc($strongsResource)) === false) {
    $sourceArray = $sourceWords;
} else {
    do {
        $sourceArray[$row[HexaplaLangLemma::STRONG_ID]] = $row[HexaplaLangLemma::UNICODE_VALUE];
    } while (($row = pg_fetch_assoc($strongsResource)) !== false);
}

// convert text to array of words
$literalWords = explode(' ', preg_replace('/' . nonwordRegexPattern() . '/u', '', $text));

// get language from translation ID
$langId = getLanguageOfVersion($db, $translationId);
if ($langId === null) {
    die(); // TODO: use error handling instead?
}

echo json_encode(['sourceWords' => $sourceArray, 'tid' => $translationId, 'literalWords' => $literalWords, 'langId' => $langId]);