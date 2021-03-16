<?php
function getCookie($name) {
    if (isset($_COOKIE[$name])) {
        return $_COOKIE[$name];
    }
    return "";
}

function setHexCookie($name, $value) {
    $expires = time() + (30 * 24 * 60 * 60); // 30 days
    $path = '/';
    $secure = false; // TODO: true
    $samesite = 'Strict';
    setcookie($name, $value, ['expires' => $expires, 'path' => $path, 'secure' => $secure, 'samesite' => $samesite]);
}

class HexaplaCookies {
    const THEME = 'hexaplaTheme';
    const SHADE = 'hexaplaShade';
}