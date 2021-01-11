<?php

class userSettings
{
    // TODO: complete
    const USE_SAVED_TRANSLATIONS = 'saved';
    const USE_LAST_TRANSLATIONS = 'last';

    private $tlSetting;
    private $tlList;

    function __construct() {
        $this->tlSetting = self::USE_SAVED_TRANSLATIONS;
        $this->tlList = '1^2^3^4';
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
}