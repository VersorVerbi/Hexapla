<?php

namespace Hexapla;
session_start();
require_once "sql-functions.php";
require_once "dbconnect.php";
/**
 * @var UserSettings $currentUser
 * @var resource $db
 */

$search = $_POST['searchbox'];
$translationList = $_POST['translations'];
$getNotes = false;

$tListArray = explode('^',$translationList);
if (in_array('notes', $tListArray)) {
    $tListArray = array_diff($tListArray, ['notes']);
    $translationList = implode('^', $tListArray);
    $getNotes = true;
}

$results = fullSearch($db, $search, $translationList, $alternatives, $title);
$output = [];
$locationIds = [];

while (($row = pg_fetch_array($results, NULL, PGSQL_NUM)) !== false) {
    $data = resolveMore($row[0]);
    // [0: text_id, 1: position, 2: value, 3: location_id, 4: punctuation, 5: version_id, 6: strong_id, 7: lang_direction]
    $chunk['parent'] = 't' . $data[5];
    $chunk['class'] = $data[6];
    $chunk['val'] = $data[2];
    $chunk['space-before'] = ($data[4] !== HexaplaPunctuation::CLOSING);
    $chunk['rtl'] = ($data[7] === LangDirection::RTL);
    $output[] = $chunk;
    $locationIds[] = $data[3];
}

$locationIds = array_unique($locationIds);
$output['loc_id'] = implode('^', $locationIds);

if ($getNotes) {
    $notesResource = getData($db,
        HexaplaTables::USER_NOTES,
        [HexaplaUserNotes::ID, HexaplaUserNotes::VALUE],
        [HexaplaUserNotes::USER_ID => $currentUser->id(), HexaplaUserNotesLocation::LOCATION_ID => $locationIds],
        [],
        [new HexaplaJoin(HexaplaTables::USER_NOTES_LOCATION,
            HexaplaTables::USER_NOTES, HexaplaUserNotes::ID,
            HexaplaTables::USER_NOTES_LOCATION, HexaplaUserNotesLocation::NOTE_ID)]);
    $userNotes = [];
    while (($noteRow = pg_fetch_assoc($notesResource)) !== false) {
        $userNotes[$noteRow[HexaplaUserNotes::ID]] = $noteRow[HexaplaUserNotes::VALUE];
    }
    $output['myNotes'] = $userNotes;
} else {
    $output['myNotes'] = null;
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