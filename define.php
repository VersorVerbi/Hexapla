<?php

require_once "sql-functions.php";
/**
 * @var UserSettings $currentUser
 * @var resource $db
 */

$sourceWords = json_decode($_POST['sourceWords']);
$translationId = $_POST['tid'];
$literalWords = json_decode($_POST['literalWords']);
$langId = $_POST['langId'];

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
    - if no definition in database, get from external API and add to database --> keep in mind that caching may not be permitted by API
    - echo json_encode'd array of definitions w/ dictionary info, lang info, and definition
*/