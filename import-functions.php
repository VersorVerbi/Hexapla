<?php

/**
 * Using an XML values array (output of xml_parse_into_struct), returns a specific value set as defined by
 * a given indices array.
 * @param array $xmlArray Values output from xml_parse_into_struct or some sub-array thereof
 * @param array $indices List of indices to reach lower levels of the values array in the order they occur, e.g.,
 *                       if you want to reach $xmlArray[3]['attributes']['TYPE'], the $indices array should be:
 *                       [3,'attributes','TYPE']
 * @param mixed $ret Output variable; set to either the array or the value at the node desired
 * @return int 1 if there was an error ($xmlArray is missing a given index), 0 if successful
 */
function xml_get_value($xmlArray, $indices, &$ret) {
    if (!array_key_exists($indices[0], $xmlArray)) {
        return 1;
    } elseif (count($indices) > 1) {
        return xml_get_value($xmlArray[$indices[0]], array_slice($indices, 1), $ret);
    } else {
        $ret = $xmlArray[$indices[0]];
        return 0;
    }
}

/**
 * Using a subnode of an XML values array (output of xml_parse_into_struct), returns a specific attribute from that
 * node's attributes.
 * @param array $xmlArray A node of an XML array that has or should have attributes
 * @param string $attr The name of the attribute we want to retrieve
 * @param string $ret The value of the requested attribute
 * @return int 1 if there was an error (no attributes on the node or attribute not found), 0 if successful
 */
function xml_get_attribute($xmlArray, $attr, &$ret) {
    if (!array_key_exists('attributes', $xmlArray)) {
        return 1;
    } else {
        return xml_get_value($xmlArray['attributes'], array($attr), $ret);
    }
}

/**
 * Using a subnode of an XML values array (output of xml_parse_into_struct), returns an array of attribute values from
 * that node's attributes.
 * @param array $xmlArray A node of an XML array that has or should have attributes
 * @param array $attrList An array of attribute names to retrieve
 * @param array $arrayRet An array of attribute values in the format $array['attrName']=attrValue
 * @return int 1 if there was an error (none of the requested attributes existed), 0 if successful at least once
 */
function xml_get_attribute_set($xmlArray, $attrList, &$arrayRet) {
    $arrayRet = [];
    foreach ($attrList as $attr) {
        if (xml_get_attribute($xmlArray, $attr, $ret) === 0) {
            $arrayRet[$attr] = $ret;
        }
    }
    if (count($arrayRet) === 0) {
        return 1;
    } else {
        return 0;
    }
}

/**
 * @return string Pattern for identifying word strings in regex (internationally capable)
 */
function wordRegexPattern() {
    return '(?:\p{L}|\p{M}|[\'-])+';
}

/**
 * @return string Pattern for identifying non-word strings (excluding spaces) in regex (internationally capable)
 */
function nonwordRegexPattern() {
    return '(?:\p{P}|\p{N}+|\p{S})';
}

function isStrongsNumber($strNum) {
    $matchesPattern = preg_match('/(H|G)\d{1,4}/u', $strNum) !== false;
    if (!$matchesPattern) {
        return false;
    }
    $num = intval(mb_substr($strNum, 1));
    if (mb_substr($strNum, 0, 1) === 'H') {
        $matchesNumbers = ($num >= 1) && ($num <= 8674);
    } else {
        // Greek Strong's numbers 2717 and 3203-3302 do not exist
        $matchesNumbers = ($num >= 1) && ($num <= 5624) && ($num !== 2717) && (($num < 3203) || ($num > 3302));
    }
    return $matchesNumbers;
}

/**
 * Given a numeric array, returns the last index
 * @param array $array A numerical array
 * @return int|null The last index in the array, -1 if the array is empty, or null if the array is associative
 */
function getLastIndex(array $array) {
    if (count($array) === 0) {
        return -1;
    }
    if (array_keys($array) !== range(0, count($array) - 1)) {
        return null;
    }
    end($array);
    return key($array);
}

function noWordSeparatorWritingSystems() {
    return '/\p{Devanagari}|\p{Ethiopic}|\p{Gujarati}|\p{Han}|\p{Hanunoo}|\p{Hiragana}|\p{Katakana}|\p{Khmer}|\p{Lao}|\p{Myanmar}|\p{Runic}|\p{Tai_le}|\p{Balinese}|\p{Batak}|\p{Javanese}|\p{Vai}|\p{Thai}|\p{Yi}/u';
}