<?php

namespace Hexapla;

use JetBrains\PhpStorm\Pure;

/**
 * Class hexaText
 */
class hexaText {
    /** @var hexaName|null */
    private ?hexaName $title;
    /** @var hexaName|null */
    private ?hexaName $translation;
    /** @var hexaCopyright|null */
    private ?hexaCopyright $copyright;
    /** @var array Array of hexaWord/hexaPunctuation objects */
    private array $wordList;
    /** @var string Unique identification piece for this text (usually provided by the source) */
    private string $uniqueId;
    /** @var array Metadata of unclear origins or purpose */
    private array $metadata;
    /** @var array Array of hexaNote objects */
    private array $nonCanonicalText;
    /** @var array Array of conversion test results in the format $testResults['testId'] = true || false */
    private array $testResults;
    /** @var array Array of conversions used in the format $conversionsUsed['conversionId'] = true || false */
    private array $conversionsUsed;
    /** @var array Array of number systems used to identify which, if any, match this struct */
    private array $numberSystems;
    /** @var int ID of the number system used by this struct */
    private int $officialNumberSystem;

    /**
     * hexaText constructor.
     */
    #[Pure] public function __construct() {
        $this->title = new hexaName();
        $this->translation = new hexaName();
        $this->copyright = new hexaCopyright();
        $this->wordList = [];
        $this->uniqueId = '';
        $this->metadata = [];
        $this->nonCanonicalText = [];
    }

    /**
     * Meant for printing debug data
     * @return string Closing <pre> tag
     */
    public function __toString(): string
    {
        echo "<pre>";
        print_r($this->title);
        print_r($this->translation);
        print_r($this->copyright);
        print_r($this->metadata);
        foreach($this->wordList as $word) {
            print_r($word);
        }
        foreach($this->nonCanonicalText as $note) {
            print_r($note);
        }
        print_r($this->metadata);
        return "</pre>";
    }

    /**
     * @param hexaWord $word A word to add to the list of words in this text
     */
    public function addWord(hexaWord $word) {
        getStandardizedReference($db, $word->getReference(), $book, $chapter, $verse);
        $this->wordList[$book][$chapter][$verse][] = $word;
    }

    /**
     * @param hexaNote $note A note to add to the list of notes in this text
     */
    public function addNote(hexaNote $note) {
        $this->nonCanonicalText[] = $note;
    }

    /**
     * @param string $metaname The name/key of this metadatum
     * @param string $metadatum The content of this metadatum
     */
    public function addMetadata(string $metaname, string $metadatum) {
        $this->metadata[$metaname] = $metadatum;
    }

    /**
     * @param hexaName $newTitle
     */
    public function setTitle(hexaName $newTitle) {
        $this->title = $newTitle;
    }

    /**
     * @param hexaCopyright $newCopyright
     */
    public function setCopyright(hexaCopyright $newCopyright) {
        $this->copyright = $newCopyright;
    }
}