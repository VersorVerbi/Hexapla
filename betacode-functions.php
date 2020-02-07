<?php

include_once "unicode-ranges.php";
include_once "lib/portable-utf8.php";

/**
 * Takes a string of betacode and converts it to UTF-8 Unicode.
 * @uses strtoupper(), str_split(), resetBeta(), preg_match(), strpos(), beta2unicode()
 * @param $str string A complete string of betacode intended to become Greek characters
 * @return string The Greek unicode characters represented by the given betacode
 */
function betaString2Unicode($str) {
    $str = strtoupper($str);
    $betaArray = str_split($str);
    $betaOut = [];
    $curOutStr = resetBeta($checks, "", "", $betaOut);
    foreach ($betaArray as $char) {
        if ($char == '*') { // is capital
            $curOutStr = resetBeta($checks, $char, $curOutStr, $betaOut);
        } elseif (preg_match('/[A-Za-z]/', $char) === 1) { // alpha
            if (!$checks['alpha']) {
                $curOutStr .= $char;
                $checks['alpha'] = true;
            } else {
                $curOutStr = resetBeta($checks, $char, $curOutStr, $betaOut);
                $checks['alpha'] = true;
            }
        } elseif (preg_match('/[\(\)]/', $char) === 1) { // smooth or rough breathing
            if (!$checks['breathing']) {
                $curOutStr .= $char;
                $checks['breathing'] = true;
            } else {
                $curOutStr = resetBeta($checks, $char, $curOutStr, $betaOut);
                $checks['breathing'] = true;
            }
        } elseif (preg_match('/[=\/\\\]/', $char) === 1) { // acute, grave, or circumflex
            if (!$checks['accent']) {
                $curOutStr .= $char;
                $checks['accent'] = true;
            } else {
                $curOutStr = resetBeta($checks, $char, $curOutStr, $betaOut);
                $checks['accent'] = true;
            }
        } elseif (preg_match('/\||\+|_/', $char) === 1) { // iota subscript, diaeresis
            if (strpos($curOutStr, $char) === false) {
                $curOutStr .= $char;
            } else {
                $curOutStr = resetBeta($checks, $char, $curOutStr, $betaOut);
            }
        } else { // spaces or other punctuation
            $curOutStr = resetBeta($checks, $char, $curOutStr, $betaOut);
            $curOutStr = resetBeta($checks, "", $curOutStr, $betaOut); // make sure this piece is by itself
        }
    }
    resetBeta($checks, $char, $curOutStr, $betaOut);
    $finalOutStr = "";
    foreach ($betaOut as $betaChunk) {
        $finalOutStr .= beta2unicode($betaChunk);
    }
    $sigma = utf8_chr(963);
    $terminalSigma = utf8_chr(962);
    $finalOutStr = preg_replace("/$sigma(?=\W|$)/u", $terminalSigma, $finalOutStr);
    return $finalOutStr;
}

/**
 * Resets our array of boolean checks that help us track our current betacode chunk, adds the current betacode chunk
 * (if it exists) to our output array, and returns the given character as the start of a new chunk.
 * @uses strlen()
 * @param $checks array Array of booleans to indicate we do or do not have a given character type in our betacode chunk
 * @param $char string Single betacode character
 * @param $curOutStr string The string as we have calculated it so far
 * @param $betaOut array Array of betacode chunks we have identified so far
 * @return string The $char variable we were given at the start
 */
function resetBeta(&$checks, $char, $curOutStr, &$betaOut) {
    $checks['alpha'] = false;
    $checks['breathing'] = false;
    $checks['accent'] = false;
    if (strlen($curOutStr) > 0) $betaOut[] = $curOutStr;
    return $char;
}

/**
 * Takes a given string of UTF-8 unicode characters and returns the betacode representation of them. If there is no
 * betacode representation of the given character, the character itself is returned.
 * @uses grapheme_extract(), strlen(), uni2betacode()
 * @param $str string UTF-8 string of Greek characters to turn into betacode
 * @return string Betacode string that represents the given unicode string
 */
function uniString2Betacode($str) {
    $outString = "";
    $uniArray = utf8_split($str);
    foreach($uniArray as $uniChar) {
        $outString .= uni2betacode($uniChar);
    }
    return $outString;
}

/**
 * Given a single UTF-8 grapheme, returns the betacode representation. If there is no betacode representation, the
 * grapheme itself is returned.
 * @uses utf8_ord(), numInRange(), strlen()
 * @param $unicodeChar string UTF-8 Greek character
 * @return string The betacode chunk that represents the given character
 */
function uni2betacode($unicodeChar): string {
    $charCode = utf8_ord($unicodeChar);
    $output = "";
    if ($charCode == 962) {
        return "J";
    } elseif ($charCode == 894) {
        return "?";
    } elseif ($charCode == 903) {
        return ":";
    }
    // capital marker
    if (numInRange($charCode, CAPITAL_RANGE)) {
        $output .= "*";
    }
    // letter
    foreach (ALPHABET_RANGES as $letter => $range) {
        if (numInRange($charCode, $range)) {
            $output .= $letter;
            break;
        }
    }
    // breathing marker
    if (numInRange($charCode, ROUGH_RANGE)) {
        $output .= "(";
    } elseif (numInRange($charCode, SMOOTH_RANGE)) {
        $output .= ")";
    }
    // accent marker
    if (numInRange($charCode, ACUTE_RANGE)) {
        $output .= "/";
    } elseif (numInRange($charCode, GRAVE_RANGE)) {
        $output .= "\\";
    } elseif (numInRange($charCode, CIRCUMFLEX_RANGE)) {
        $output .= "=";
    }
    // length marker
    if (numInRange($charCode, MACRON_RANGE)) {
        $output .= "_";
    } elseif (numInRange($charCode, BREVE_RANGE)) {
        $output .= "^";
    }
    // iota subscript
    if (numInRange($charCode, IOTASUB_RANGE)) {
        $output .= "|";
    }
    return (strlen($output) > 0 ? $output : $unicodeChar);
}

/**
 * Determines whether a given integer appears within a given range of integers.
 * @uses getValuesFromRanges(), in_array()
 * @param $num int A UTF-8 character code value to check against a given range of integers
 * @param $range string The comma- and hyphen-delimited range of integers to check against in the format "N0,N1-N2,N3" etc.
 * @return bool True if the given number is in the given range; false otherwise
 */
function numInRange($num, $range) {
    $allValues = getValuesFromRanges($range);
    if (in_array($num, $allValues)) {
        return true;
    }
    return false;
}

/**
 * Converts a range of integers from a string to an array of individual integers.
 * @uses explode(), strpos()
 * @param $range string A comma- and hyphen-delimited range of integers in the format "N0,N1-N2,N3" etc.
 * @return array Array of integers in the range
 */
function getValuesFromRanges($range): array
{
    $allRanges = explode(",", $range);
    $allValues = [];
    foreach ($allRanges as $miniRange) {
        if (strpos($miniRange, "-") !== false) {
            $miniRangeArray = explode("-", $miniRange);
            $rangeStart = $miniRangeArray[0];
            $rangeEnd = $miniRangeArray[1];
            for ($i = $rangeStart; $i <= $rangeEnd; $i++) {
                $allValues[] = $i;
            }
        } else {
            $allValues[] = $miniRange;
        }
    }
    return $allValues;
}

/**
 * Returns English alphabetic characters only from a given string.
 * @uses strlen(), preg_match()
 * @param $str string A string that may or may not contain English alphabetic characters
 * @return string A collapsed string of just the English alphabetic characters in the given string
 */
function alphaOnly($str) {
    $out = "";
    for ($i = 0; $i < strlen($str); $i++) {
        $chr = $str[$i];
        if (preg_match('/[A-Za-z]/', $chr)) {
            $out .= $chr;
        }
    }
    return $out;
}

/**
 * Converts a given unit of betacode into the UTF-8 grapheme equivalent from Greek.
 * @uses strpos(), strlen(), count(), findRangeCommonality(), ord(), utf8_chr()
 * @param $betaChunk string A cohesive unit of betacode
 * @return string The UTF-8 Greek equivalent of the given betacode
 */
function beta2unicode($betaChunk) {
    $rangesForCommonality = [];
    if (strpos($betaChunk,'*') !== false) {
        $rangesForCommonality[] = CAPITAL_RANGE;
    }
    $letter = alphaOnly($betaChunk);
    if (strlen($letter) > 0) {
        $rangesForCommonality[] = ALPHABET_RANGES[$letter];
    }
    foreach (DIACRITIC_RANGES as $marker => $range) {
        if (strpos($betaChunk, $marker) !== false) {
            $rangesForCommonality[] = $range;
        }
    }
    if (count($rangesForCommonality) > 0) {
        $unicode = findRangeCommonality($rangesForCommonality, $betaChunk);
    } elseif ($betaChunk == '?') {
        $unicode = 894;
    } elseif ($betaChunk == ':') {
        $unicode = 908;
    } else {
        $unicode = ord($betaChunk);
    }
    return utf8_chr($unicode);
}

/**
 * Finds the single integer (UTF-8 character code) from all relevant ranges of Greek Unicode characters. Given a set of
 * ranges, finds the one character they have in common; if more than one, excludes options from unincluded ranges. Given
 * just one range, reduces to a single unique value. Given zero ranges, returns the source string. If more than one
 * integer remains despite all best efforts, returns the first one (which may or may not be effectively random).
 * @uses getValuesFromRanges(), count(), array_intersect(), array_diff(), strpos(), array_values(), utf8_ord()
 * @param $rangeSet array An array of integer range strings
 * @param $sourceStr string The source betacode string (assumed to be one character if it gets used)
 * @return int The UTF-8 Unicode character value of the given source
 */
function findRangeCommonality($rangeSet, $sourceStr): int {
    $allValues = [];
    foreach($rangeSet as $range) {
        $allValues[] = getValuesFromRanges($range);
    }
    if (count($allValues) > 1) {
        $intersect = array_intersect($allValues[0], $allValues[1]);
        for ($i = 2; $i < count($allValues); $i++) {
            $intersect = array_intersect($intersect, $allValues[$i]);
        }
        if (strpos($sourceStr, '*') === false) {
            $intersect = array_diff($intersect, getValuesFromRanges( CAPITAL_RANGE));
        }
        foreach(DIACRITIC_RANGES as $chr => $range) {
            if (strpos($sourceStr, $chr) !== false) {
                continue;
            }
            $intersect = array_diff($intersect, getValuesFromRanges($range));
        }
    } elseif (count($allValues) === 1) {
        // CAPITAL_RANGE is never by itself, so it's safe to do first
        $intersect = array_diff($allValues[0], getValuesFromRanges(CAPITAL_RANGE));
        foreach(ALPHABET_RANGES as $chr => $range) {
            if (strpos($sourceStr, $chr) !== false) {
                continue;
            }
            $intersect = array_diff($intersect, getValuesFromRanges($range));
        }
        foreach(DIACRITIC_RANGES as $chr => $range) {
            if (strpos($sourceStr, $chr) !== false) {
                continue;
            }
            $intersect = array_diff($intersect, getValuesFromRanges($range));
        }
    } else {
        $intersect = [];
    }
    return (count($intersect) > 0 ? array_values($intersect)[0] : utf8_ord($sourceStr));
}