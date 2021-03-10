<?php
require_once "sql-functions.php";
require_once "general-functions.php";

$sourceWords = json_decode($_POST['sourceWords']);
$translationId = $_POST['tid'];
$text = $_POST['text'];

// convert text to array of words
$literalWords = explode(' ', preg_replace('/' . nonwordRegexPattern() . '/u', '', $text));

// get language from translation ID
$langId = getLanguageOfVersion($db, $translationId);
if ($langId === null) {
    die(); // TODO: use error handling instead?
}

echo json_encode(['sourceWords' => $sourceWords, 'tid' => $translationId, 'literalWords' => $literalWords, 'langId' => $langId]);