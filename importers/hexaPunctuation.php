<?php

use JetBrains\PhpStorm\Pure;

class hexaPunctuation extends hexaWord {
    /** @var bool If true, follows the previous text exactly (no space before); if false, precedes the following
     *            text exactly (no space after). */
    private bool $endingPunctuation;
    const OPENERS = ['<', '(', '[', '{', '«','‘','“',"⸂","⸀"];

    /**
     * hexaPunctuation constructor.
     * @param string $myRef
     * @param string $val
     * @param int $myPos
     * @param string $strongs
     * @param string $lemma
     * @param bool $endingPunctuation
     */
    #[Pure] public function __construct(string $myRef, string $val, int $myPos, $strongs = '', $lemma = '', $endingPunctuation = true) {
        $this->endingPunctuation = $endingPunctuation;
        parent::__construct($myRef, $val, $myPos, $strongs, $lemma);
    }

    public function getEndingPunc(): bool {
        return $this->endingPunctuation;
    }

    #[Pure] public function getTotalLength(): int {
        return strlen($this->getText());
    }

    /**
     * @param array $criteria SQL criteria array with column names as keys and values as values
     * @param bool $forSearch True if only returning search variables
     */
    public function toCriteria(array &$criteria, $forSearch = false): void {
        if (!$forSearch) {
            if (isset($this->endingPunctuation)) $criteria[HexaplaTextValue::PUNCTUATION] = ($this->endingPunctuation ? HexaplaPunctuation::CLOSING : HexaplaPunctuation::OPENING);
        }
        parent::toCriteria($criteria, $forSearch);
    }

    public function upload($db, $version, $punc = "NotPunctuation"): void {
        $punc = ($this->endingPunctuation ? "Closing" : "Opening");
        parent::upload($db, $version, $punc);
    }
}