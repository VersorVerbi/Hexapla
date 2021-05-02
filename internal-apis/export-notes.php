<?php

namespace Hexapla;

require_once "../dbconnect.php";
require_once "../sql-functions.php";
/** @var $currentUser UserSettings */

$notes = getData($db,
    HexaplaTables::USER_NOTES,
    [HexaplaUserNotes::VALUE, HexaplaUserNotesLocation::LOCATION_ID],
    [HexaplaUserNotes::USER_ID => $currentUser->id()],
    [HexaplaUserNotesLocation::LOCATION_ID],
    [new HexaplaJoin(HexaplaTables::USER_NOTES_LOCATION,
        HexaplaTables::USER_NOTES, HexaplaUserNotes::ID,
        HexaplaTables::USER_NOTES_LOCATION, HexaplaUserNotesLocation::NOTE_ID)]);

// TODO: location id -> list of location ids, grouped by note id
// TODO: fast way to get references?