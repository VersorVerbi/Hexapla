<?php

function ustr_split($str) {
    $uniArray = [];
    $next = 0;
    do {
        $uniArray[] = grapheme_extract($str, 1, GRAPHEME_EXTR_MAXCHARS, $next, $next);
    } while ($next < strlen($str)); // grapheme_strlen counts the graphemes, but strlen counts the bytes, which is what $next refers to
    return $uniArray;
}