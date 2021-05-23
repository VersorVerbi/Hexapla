<?php

namespace Hexapla;

require_once "../dbconnect.php";
require_once "../sql-functions.php";
/** @var $currentUser UserSettings */

$db = $db ?? null;

$email = $_POST['email'];
$password = $_POST['password'];

echo json_encode(register($db, $email, $password));