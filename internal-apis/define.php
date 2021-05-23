<?php

namespace Hexapla;

require_once "../sql-functions.php";
/**
 * @var UserSettings $currentUser
 * @var resource $db
 */

$sourceWords = json_decode($_POST['sourceWords']);
// $translationId = $_POST['tid']; // unused - will we need this at any point?
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