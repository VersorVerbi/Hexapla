<?php
namespace Hexapla;

require_once "../cookie-functions.php";

if (count($_POST) > 0) {
    // secret cookies!
    if (isset($_POST['set'])) {
        setHexCookie($_POST['name'], $_POST['value']);
    } else {
        echo getCookie($_POST['name']);
    }
} elseif (isset($_GET['set'])) {
    setHexCookie($_GET['name'], $_GET['value']);
} else {
    echo getCookie($_GET['name']);
}

die(); // return to API caller