<?php

namespace Hexapla;

use JetBrains\PhpStorm\Pure;

class hexaWord extends hexaVerseObject {
    /** @var int 0-indexed position in a verse */
    private int $position;
    /** @var string Value of this word/text/punctuation */
    private string $text;
    /** @var string Strong's Number */
    private string $strongs;
    /** @var string Source lemma */
    private string $lemma;

    /**
     * hexaWord constructor.
     * @param string $myRef
     * @param string $val
     * @param int $myPos
     * @param string $strongs
     * @param string $lemma
     */
    #[Pure] public function __construct(string $myRef, string $val, int $myPos, $strongs = '', $lemma = '') {
        $this->position = $myPos;
        $this->text = $val;
        $this->strongs = $strongs;
        $this->lemma = $lemma;
        parent::__construct($myRef);
    }

    public function getPosition(): int {
        return $this->position;
    }

    public function getText(): string {
        return $this->text;
    }

    public function getStrongs(): string {
        return $this->strongs;
    }

    public function getLemma(): string {
        return $this->lemma;
    }

    #[Pure] public function getTotalLength(): int {
        return strlen($this->text) + 1; // extra 1 for space
    }

    /**
     * @param array $criteria SQL criteria array with column names as keys and values as values
     * @param bool $forSearch True if we only want to return SEARCH variables, not ALL variables
     */
    public function toCriteria(array &$criteria, $forSearch = false): void {
        if (!$forSearch && !isset($criteria[HexaplaTextValue::PUNCTUATION])) $criteria[HexaplaTextValue::PUNCTUATION] = HexaplaPunctuation::NOT;
        if (isset($this->position)) $criteria[HexaplaTextValue::POSITION] = $this->position;
        if (!$forSearch) {
            if (isset($this->text)) $criteria[HexaplaTextValue::VALUE] = $this->text;
            if (isset($this->strongs) && utf8_strlen($this->strongs) > 0) $criteria[HexaplaTextStrongs::STRONG_ID] = $this->strongs;
        }
        // others should be taken care of elsewhere / already
        parent::toCriteria($criteria, $forSearch);
    }

    public function upload($db, $version, $punc = "NotPunctuation"): void {/*
        $criteria['location_id'] = $this->getLocationId();
        $criteria['position'] = $this->position;
        $criteria['version_id'] = $version;
        $results = getData($db, TEXT_VALUE_TABLE(), [], $criteria);
        $row = pg_fetch_assoc($results);
        if ($row !== false) {
            // handle updates instead of skipping
            return;
        }
        $encoding = iconv_get_encoding('ouput_encoding');
        $valArray[HexaplaTextValue::POSITION] = $this->position;
        $valArray[HexaplaTextValue::VALUE] = iconv($encoding, 'UTF-8', $this->text);
        $valArray[HexaplaTextValue::LOCATION_ID] = $this->getLocationId();
        $valArray[HexaplaTextValue::PUNCTUATION] = $punc;
        $valArray[HexaplaTextValue::VERSION_ID] = $version;
        pg_insert($db, TEXT_VALUE_TABLE(), $valArray);
        if ($GLOBALS['DEBUG']) {
            if ($valArray['location_id'] === -1) {
                print_r($this);
                die(-1);
            }
        }
        if (strlen($this->strongs) > 0) {

        }
        return;*/
    }
}