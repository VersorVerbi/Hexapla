<?php

require_once "importers/import-functions.php";
require_once "sql-functions.php";

$db = null;

$sourceWords = json_decode($_POST['sourceWords']);
$translationId = $_POST['tid'];
$text = $_POST['text'];

// convert text to array of words
$literalWords = explode(' ', preg_replace('/' . nonwordRegexPattern() . '/gu', '', $text));

// get language from translation ID
$langId = getLanguageOfVersion($db, $translationId);
if ($langId === null) {
    die(); // TODO: use error handling instead?
}




/* TODO:
    - use sourceWords to get definition from Strong's ID -> lemma -> definition
    - if current lang is Greek (or Hebrew/Latin/...?) use word form search -> lemma -> definition
    - if no definition in database, get from external API and add to database
    - echo json_encode'd array of definitions w/ dictionary info, lang info, and definition
*/