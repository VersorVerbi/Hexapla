<?php


class OSISReader extends BibleXMLReader {
    public function __construct() {
        parent::__construct();
    }

    public function read($skipLineBreaks = false, $targetNodeType = -1, $targetDivType = '') {
        if (!$skipLineBreaks && $targetNodeType < 0 && strlen($targetDivType) == 0) {
            parent::read();
            return;
        }
        do {
            parent::read();
            $done = true;
            if ($skipLineBreaks && preg_match('/^[\r\n]+$/',$this->value) > 0) {
                $done = false;
            } elseif ($targetNodeType >= 0 && $this->nodeType !== $targetNodeType) {
                $done = false;
            } elseif (strlen($targetDivType) > 0 && $this->divType() !== $targetDivType) {
                $done = false;
            }
        } while (!$done);
    }

    public function runTests() {
        // TODO: Implement runTests() method.
    }

    public function exportAndUpload() {
        // TODO: Implement exportAndUpload() method.
    }

    /**
     * @throws PositionException
     * @return array Associative array with keys:
            * reference - the verse reference
            * text - the text of the verse
     */
    private function currentVerse() {
        if (strtolower($this->localName) !== 'verse') {
            throw new PositionException('Not on a verse', 1);
        }
        if (is_null($this->getAttribute('sID'))) {
            throw new PositionException('On a verse end spot', 2);
        }
        $bracketId = $this->getAttribute('sID');
        $output['reference'] = getLocation($db, $this->getAttribute('osisID'));
        while ($this->read() && $this->getAttribute('eID') !== $bracketId) {
            if ($this->hasValue) { $output['text'] .= $this->value; }
        }
        return $output;
    }

    private function divType() {
        if ($this->nodeType !== XMLReader::ELEMENT) {
            return '';
        }
        if (strtolower($this->localName) !== 'div') {
            return '';
        }
        if (!$this->hasAttributes) {
            return '';
        }
        return $this->getAttribute('type');
    }
}