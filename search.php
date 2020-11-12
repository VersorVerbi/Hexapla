<?php
include_once "sql-functions.php";

$search = $_POST['searchbox'];
$translationList = $_POST['translations'];

$results = fullSearch($db, $search, $translationList, $alternatives);
$output = [];

while (($row = pg_fetch_array($results, NULL, PGSQL_NUM)) !== false) {
    $data = resolveMore($row[0]);
    $chunk['parent'] = 't' . $data[5];
    $chunk['class'] = $data[6];
    $chunk['val'] = $data[2];
    $chunk['space-before'] = ($data[4] !== HexaplaPunctuation::CLOSING);
    $output[] = $chunk;
}

echo json_encode($output);
?>