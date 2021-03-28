<?php
class hexaVerseObject {
    /** @var string Source reference (e.g., "Gen3:12" or "Matthew 26:14") */
    private string $reference;
    /** @var int|string Unique location ID(s) for this reference object */
    private int|string $locationId;

    /**
     * hexaVerseObject constructor.
     * @param string $reference
     */
    public function __construct(string $reference) {
        $this->reference = $reference;
    }

    public function setLocationId($newLoc) {
        $this->locationId = intval($newLoc);
    }

    public function getLocationId(): int {
        return $this->locationId;
    }

    public function getReference(): string {
        return $this->reference;
    }

    /**
     * @param array $criteria SQL criteria array with column names as keys and values as values
     * @param bool $forSearch True if only returning SEARCH criteria, not ALL -- but we don't use that here
     * @noinspection PhpUnusedParameterInspection
     */
    public function toCriteria(array &$criteria, $forSearch = false): void {
        if (isset($this->locationId)) $criteria[HexaplaLocationColumns::LOCATION_ID] = $this->locationId;
    }
}