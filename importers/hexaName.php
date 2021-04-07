<?php

namespace Hexapla;
/**
 * Class hexaName
 */
class hexaName {
    /** @var string Official name of this thing */
    private string $name;
    /** @var array List of string abbreviations or alternative names */
    private array $abbreviationList;

    /**
     * hexaName constructor.
     * @param string $name Official name
     * @param array $abbrs Variable number of individual abbreviation/alternative name arguments
     */
    public function __construct($name = '', ...$abbrs) {
        $this->name = $name;
        $this->abbreviationList = $abbrs;
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getAbbreviations(): array {
        return $this->abbreviationList;
    }
}