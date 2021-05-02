<?php

namespace Hexapla;

require_once "../dbconnect.php";
require_once "../sql-functions.php";
/**
 * @var UserSettings $currentUser
 * @var resource $db
 */

$locations = explode('^', $_POST['loc_id']);

// FIXME: updates are not working...?

if (isset($_POST['note_id'])) {
    // TODO: get number of locations set and, if MORE, update
    // TODO: if there are >1 note grouped together, update one and delete the rest
    $noteId = $_POST['note_id'];
    update($db, HexaplaTables::USER_NOTES, [HexaplaUserNotes::VALUE => $_POST['note']], [HexaplaUserNotes::ID => $noteId, HexaplaUserNotes::USER_ID => $currentUser->id()]);
} else {
    $noteId = putData($db, HexaplaTables::USER_NOTES, [HexaplaUserNotes::VALUE => $_POST['note'], HexaplaUserNotes::USER_ID => $currentUser->id()]);
    $insertArray = [];
    foreach($locations as $location) {
        $insertArray[] = [HexaplaUserNotesLocation::NOTE_ID => $noteId, HexaplaUserNotesLocation::LOCATION_ID => $location];
    }
    putData($db, HexaplaTables::USER_NOTES_LOCATION, $insertArray, null);
}

echo ($noteId >= 0 ? json_encode($noteId) : json_encode(false));