<?php

require_once "sql-functions.php";
require_once "general-functions.php";

$sourceWords = json_decode($_POST['sourceWords']);
$translationId = $_POST['tid'];
$text = $_POST['text'];

// convert text to array of words
$literalWords = explode(' ', preg_replace('/' . nonwordRegexPattern() . '/u', '', $text));

// get language from translation ID
$langId = getLanguageOfVersion($db, substr($translationId, 1));
if ($langId === null) {
    die(); // TODO: use error handling instead?
}

if (count($sourceWords) > 0) {
    $output['source'] = getStrongsDefinition($db, $sourceWords);
}

if (count($literalWords) > 0) {
    $output['literal'] = getLiteralDefinition($db, $literalWords, $langId);
}

$langData = getData($db, HexaplaTables::LANGUAGE, [HexaplaLanguage::NAME, HexaplaLanguage::DIRECTION], [HexaplaLanguage::ID => $langId]);
if (($row = pg_fetch_assoc($langData)) !== false) {
    $output['literalLang'] = ['name' => $row[HexaplaLanguage::NAME], 'dir' => $row[HexaplaLanguage::DIRECTION]];
} else {
    $output['literalLang'] = null;
}

echo json_encode($output);

/* TODO:
    - use sourceWords to get definition from Strong's ID -> lemma -> definition
    - if current lang is Greek (or Hebrew/Latin/...?) use word form search -> lemma -> definition
    - if no definition in database, get from external API and add to database
    - echo json_encode'd array of definitions w/ dictionary info, lang info, and definition
*/