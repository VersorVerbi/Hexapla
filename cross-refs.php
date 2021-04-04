<?php

require_once "sql-functions.php";
/**
 * @var UserSettings $currentUser
 * @var resource $db
 */

$output = [];

$sourceWords = json_decode($_POST['sourceWords']);
$translationId = $_POST['tid'];
$literalWords = json_decode($_POST['literalWords']);
$langId = $_POST['langId'];

if (count($sourceWords) > 0) {
    $output['source'] = getStrongsCrossRefs($db, $sourceWords, $translationId);
} else {
    $output['source'] = [];
}

if (count($literalWords) > 0) {
    $output['literal'] = getLiteralCrossRefs($db, $literalWords, $langId, $translationId);
} else {
    $output['literal'] = [];
}

echo json_encode($output);