<?php

use JetBrains\PhpStorm\Pure;

class hexaNote extends hexaVerseObject {
    /** @var string Text of the note on this reference */
    private string $noteText;
    /** @var string|null Target reference */
    private ?string $crossReference;
    /** @var int|string|null Target reference location ID(s) */
    private null|int|string $crossRefId;

    /**
     * hexaNote constructor.
     * @param string $reference
     * @param string $text
     * @param null $crossReference
     */
    #[Pure] public function __construct(string $reference, $text = '', $crossReference = null) {
        $this->noteText = $text;
        $this->crossReference = $crossReference;
        parent::__construct($reference);
    }

    /**
     * @return bool
     */
    public function hasText() {
        return isset($this->noteText);
    }

    /**
     * @param array $criteria
     * @param false $forSearch
     */
    public function toCriteria(array &$criteria, $forSearch = false): void
    {
        if (!$forSearch) {
            if (isset($this->noteText)) $criteria[HexaplaNoteText::VALUE] = $this->noteText;
            if (isset($this->crossReference)) $criteria[HexaplaNoteCrossRef::REFERENCE_ID] = $this->crossReference;
        }
        parent::toCriteria($criteria, $forSearch);
    }
}