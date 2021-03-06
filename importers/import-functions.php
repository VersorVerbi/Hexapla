<?php

namespace Hexapla;

use JetBrains\PhpStorm\Pure;
use DateTime, DateInterval;

require_once "../sql-functions.php";
require_once "../general-functions.php";
require_once "../HexaplaException.php";

/**
 * Using an XML values array (output of xml_parse_into_struct), returns a specific value set as defined by
 * a given indices array.
 * @param array $xmlArray Values output from xml_parse_into_struct or some sub-array thereof
 * @param array $indices List of indices to reach lower levels of the values array in the order they occur, e.g.,
 *                       if you want to reach $xmlArray[3]['attributes']['TYPE'], the $indices array should be:
 *                       [3,'attributes','TYPE']
 * @param mixed $ret Output variable; set to either the array or the value at the node desired
 * @return int 1 if there was an error ($xmlArray is missing a given index), 0 if successful
 * @uses array_key_exists(), xml_get_value(), array_slice()
 */
function xml_get_value(array $xmlArray, array $indices, mixed &$ret): int
{
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
 * @param string|null $ret The value of the requested attribute
 * @return int 1 if there was an error (no attributes on the node or attribute not found), 0 if successful
 * @uses array_key_exists(), xml_get_value()
 */
function xml_get_attribute(array $xmlArray, string $attr, string|null &$ret): int
{
    if (!array_key_exists('attributes', $xmlArray)) {
        return 1;
    } else {
        return xml_get_value($xmlArray['attributes'], array($attr), $ret);
    }
}

/**
 * @param array $xmlArray
 * @param array $indices
 * @param mixed $targetRet
 * @return bool
 * @uses xml_get_value()
 */
function xml_value_is(array $xmlArray, array $indices, mixed $targetRet): bool
{
    xml_get_value($xmlArray, $indices, $ret);
    return ($ret === $targetRet);
}

/**
 * @param array $xmlArray
 * @param string $attr
 * @param mixed $targetRet
 * @return bool
 * @uses xml_get_attribute()
 */
function xml_attribute_is(array $xmlArray, string $attr, mixed $targetRet): bool
{
    xml_get_attribute($xmlArray, $attr, $ret);
    return ($ret === $targetRet);
}

/**
 * Using a subnode of an XML values array (output of xml_parse_into_struct), returns an array of attribute values from
 * that node's attributes.
 * @param array $xmlArray A node of an XML array that has or should have attributes
 * @param array $attrList An array of attribute names to retrieve
 * @param array|null $arrayRet An array of attribute values in the format $array['attrName']=attrValue
 * @return int 1 if there was an error (none of the requested attributes existed), 0 if successful at least once
 * @uses xml_get_attribute(), count()
 */
function xml_get_attribute_set(array $xmlArray, array $attrList, array|null &$arrayRet): int
{
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
 * Given a string, determines whether that string is a properly formatted Strong's Number
 * @param string $strNum The string to check
 * @return bool True if the given string is a Strong's Number; false otherwise
 * @uses preg_match(), intval(), utf8_substr()
 */
function isStrongsNumber(string $strNum): bool
{
    $matchesPattern = preg_match('/^([HG])\d{1,4}$/u', $strNum) !== false;
    if (!$matchesPattern) {
        return false;
    }
    $num = intval(utf8_substr($strNum, 1));
    if (utf8_substr($strNum, 0, 1) === 'H') {
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
function getLastIndex(array $array): ?int
{
    if (count($array) === 0) {
        return -1;
    }
    if (array_keys($array) !== range(0, count($array) - 1)) {
        return null;
    }
    end($array);
    return key($array);
}

function noWordSeparatorWritingSystems(): string
{
    return '/\p{Devanagari}|\p{Ethiopic}|\p{Gujarati}|\p{Han}|\p{Hanunoo}|\p{Hiragana}|\p{Katakana}|\p{Khmer}|\p{Lao}|\p{Myanmar}|\p{Runic}|\p{Tai_le}|\p{Balinese}|\p{Batak}|\p{Javanese}|\p{Vai}|\p{Thai}|\p{Yi}/u';
}

class HexaplaErrorLog {
    private string $logFile;

    public function __construct($fileName) {
        $this->logFile = $fileName;
    }

    /**
     * @param HexaplaException $exception
     * @param string $extraMessage
     */
    public function log(HexaplaException $exception, $extraMessage = '') {
        file_put_contents($this->logFile,
            date('Y-m-d H:i:s e') . ' ' .
            get_class($exception) . ': ' .
            $exception->getCode() . ': ' . $exception->getFile() . ' line ' . $exception->getLine() . ': ' .
            $exception->getMessage() .
            (strlen($extraMessage) > 0 ? ' | ADDITIONAL NOTES: ' . $extraMessage : '') . "\n",
            FILE_APPEND);
        print_r($exception->getLocals());
    }
}

class PerformanceLogger {
    /** @var string $logFile */
    private string $logFile;
    /** @var int $lastLog */
    private int $lastLog;
    /** @var bool $isActive */
    private $isActive;
    /** @var bool $verbose */
    private bool $verbose;
    /** @var DateTime $veryBeginning */
    private DateTime $veryBeginning;

    public function __construct($logFile, $isActive = true) {
        $this->logFile = $logFile;
        $this->isActive = $isActive;
        $this->verbose = false;
        $this->veryBeginning = new DateTime();
    }

    public function log($msg = "", $first = false) {
        if (!$this->isActive) { return; }
        $now = microtime(true) * 1000;
        if (isset($this->lastLog)) {
            $micros = intdiv($now - $this->lastLog, 1);
        } else {
            $micros = 0;
        }
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        file_put_contents(
            $this->logFile,
            date('Y-m-d H:i:s e') . ': ' . $micros  . 'ms later' . (strlen($msg) > 0 ? ': ' . $msg : '') . "\n" .
            ($this->verbose ? print_r($backtrace[1], true) . "\n" : '') .
            'Memory Usage: ' . memory_get_usage() . ' / ' . memory_get_usage(true) . "\n",
            ($first ? 0 : FILE_APPEND));
        if (!$GLOBALS['DEBUG'] && $micros > 1000 && !str_contains($msg, "Conversions") && !str_contains($msg, " days,") && !str_contains($msg, "existingDataCheck")) {
            throw new HexaplaException("taking too long --> ", -1, null, debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2));
        }
        $this->lastLog = microtime(true) * 1000;
    }

    public function activate() {
        $this->isActive = true;
    }

    public function deactivate() {
        $this->isActive = false;
    }

    public function close() {
        $end = new DateTime();
        /** @var DateInterval $diff */
        $diff = date_diff($end, $this->veryBeginning, true);
        $this->log($diff->format("%d days, %h hours, %i minutes, %s seconds, %f microseconds"));
    }
}

#[Pure] function stripStrongsNums($strNum): string
{
    if (is_numeric($strNum)) {
        // TODO: assume Greek for now
        $strNum = 'G' . $strNum;
    }
    $num = substr($strNum, 1);
    if (is_numeric($num)) {
        return substr($strNum, 0, 1) . +$num;
    }
    return "";
}