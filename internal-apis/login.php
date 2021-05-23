<?php

namespace Hexapla;

require_once "../dbconnect.php";
require_once "../sql-functions.php";
global $currentUser;

$db = $db ?? null;

$userId = login($db, $_POST['loginEmail'], $_POST['loginPassword'], $_POST['loginLength']) or die(json_encode(false));

$currentUser = new UserSettings($userId);

echo $userId;