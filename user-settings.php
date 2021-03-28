<?php

class UserSettings
{
    // TODO: complete
    const USE_SAVED_TRANSLATIONS = 'saved';
    const USE_LAST_TRANSLATIONS = 'last';

    private string $tlSetting;
    private string $tlList;
    private int $allowsBehavior;
    private int $id;

    function __construct() {
        $this->tlSetting = self::USE_LAST_TRANSLATIONS;
        $this->tlList = '1^2^3^4';
        $this->allowsBehavior = 1;
        $this->id = 1;
    }

    public function set_tlSetting($setting) {
        $this->tlSetting = $setting;
    }

    public function useSavedTl() {
        return $this->tlSetting === self::USE_SAVED_TRANSLATIONS;
    }

    public function useLastTl() {
        return $this->tlSetting === self::USE_LAST_TRANSLATIONS;
    }

    public function savedTls() {
        return $this->tlList;
    }

    public function canWriteNotes() {
        return $this->allowsBehavior & AllowedBehaviors::CAN_WRITE_NOTES;
    }

    public function id() {
        return $this->id;
    }
}

class AllowedBehaviors {
    const CAN_WRITE_NOTES = 1;
    const CAN_DIFF = 2;
    const CAN_FOCUS = 4;
    const CAN_UPLOAD = 8;
    const CAN_PARSE = 16;
}