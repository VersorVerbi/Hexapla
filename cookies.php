<?php

if (isset($_POST)) {
    // secret cookies!
    if (isset($_POST['set'])) {
        setcookie($_POST['name'], $_POST['value']);
    } else {
        echo getCookie($_POST['name']);
    }
} elseif (isset($_GET['set'])) {
    setcookie($_GET['name'], $_GET['value']);
} else {
    echo getCookie($_GET['name']);
}

die(); // return to API caller

function getCookie($name) {
    return $_COOKIE[$name];
}