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
 * Given a string, compresses space characters (regex \s) into a single space (chr(32))
 * @param string $string The string to modify
 * @param bool $allOfThem Set to true to replace every space of every type; set to false or omit to replace only 2+
 *                        spaces next to each other
 * @return string The modified string
 */
function reduceSpaces($string, $allOfThem = false) {
    return preg_replace(($allOfThem ? '/\s+/' : '/\s\s+/'), ' ', $string);
}