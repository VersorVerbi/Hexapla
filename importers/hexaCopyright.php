<?php
/**
 * Class hexaCopyright
 */
class hexaCopyright {
    // --Commented out by Inspection (12/12/2020 9:27 AM):const PUBLIC_DOMAIN = "Public domain";
    const COPYRIGHTED = "Copyright";
    /** @var int|null Current copyright year (not "original publication date," so we don't need to worry about BC) */
    private ?int $year;
    /** @var string|null The name of the publishing company or person(s) */
    private ?string $publisher;
    /** @var string|null textual representation of rights restrictions or availability */
    private ?string $rights;
    /** @var string|null "Public domain" or "copyrighted," usually */
    private ?string $type;

    /**
     * hexaCopyright constructor.
     */
    public function __construct() {
        $this->type = null;
        $this->year = null;
        $this->publisher = null;
        $this->rights = null;
    }

    /**
     * @param string $newRights
     */
    public function setRights(string $newRights) {
        $this->rights = $newRights;
    }

    /**
     * @param string $newPublisher
     */
    public function setPublisher(string $newPublisher) {
        $this->publisher = $newPublisher;
    }

    /**
     * @return string|null
     */
    public function getPublisher(): ?string
    {
        return $this->publisher;
    }

    public function __toString(): string {
        $output = '';
        if ($this->type === $this::COPYRIGHTED) {
            $output .= utf8_chr(169); // copyright symbol U+00A9
            $output .= ' ' . $this->year . "\n";
        }
        $output .= $this->rights;
        return $output;
    }
}