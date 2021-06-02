<?php

namespace Hexapla;
require_once "dbconnect.php";
global $currentUser;

$setting = $_GET['setting'];
$value = $_GET['value'];

$currentUser->set($setting, $value);