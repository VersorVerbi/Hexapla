<?php
session_start();
include_once "sql-functions.php";

$search = $_POST['searchbox'];
$translationList = $_POST['translations'];

$results = fullSearch($db, $search, $translationList, $alternatives, $title);
$output = [];

while (($row = pg_fetch_array($results, NULL, PGSQL_NUM)) !== false) {
    $data = resolveMore($row[0]);
    $chunk['parent'] = 't' . $data[5];
    $chunk['class'] = $data[6];
    $chunk['val'] = $data[2];
    $chunk['space-before'] = ($data[4] !== HexaplaPunctuation::CLOSING);
    $output[] = $chunk;
}

unset($_SESSION['alts']);

if (count($alternatives) > 0) {
    $altOutput = 'Did you mean: ';
    foreach ($alternatives as $a => $alt) {
        if ($alt[2] !== $alt[1] || $alt[4] !== $alt[3]) {
            $altOutput .= '<a href="index.php?search=' . str_replace(' ', '+', $alt[5]) . '-' . $alt[2] . ':' . $alt[4] . '&alt=' . $a
                . '">' . $alt[5] . '-';
            if ($alt[2] !== $alt[1]) $altOutput .= $alt[2] . ':';
            $altOutput .=  $alt[4] . '</a>';
        } else {
            $altOutput .= '<a href="index.php?search=' . str_replace(' ', '+', $alt[5]) . '&alt=' . $a . '">' . $alt[5] . '</a>';
        }
        $_SESSION['alts'][$a] = ['book' => $alt[0], 'chb' => $alt[1], 'che' => $alt[2], 'vb' => $alt[3], 've' => $alt[4], 'disb' => $alt[5], 'dise' => $alt[6]];
        $altOutput .= '; ';
    }
    $output['alts'] = substr($altOutput, 0, -2) . '?';
} else {
    $output['alts'] = null;
}

$output['title'] = $title;

echo json_encode($output);
session_write_close();