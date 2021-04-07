<?php

namespace Hexapla;
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
    if ($value === false) $value = 0; // Firefox fix to prevent "expired" empty cookie
    setcookie($name, $value, ['expires' => $expires, 'path' => $path, 'secure' => $secure, 'samesite' => $samesite]);
}

class HexaplaCookies {
    const THEME = 'hexaplaTheme';
    const SHADE = 'hexaplaShade';
    const LAST_TRANSLATIONS = 'hexaplaTls';
    const DIFF_BY_WORD = 'hexaplaWord';
    const DIFF_CASE_SENSITIVE = 'hexaplaCaseSens';
}