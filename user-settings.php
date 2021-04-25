<?php

namespace Hexapla;

require_once "sql-functions.php";
require_once "cookie-functions.php";

class UserSettings
{
    const USE_SAVED_TRANSLATIONS = 'saved';
    const USE_LAST_TRANSLATIONS = 'last';
    const SETTINGS_SETUP = [HexaplaSettings::DEFAULT_LOAD => 'tlSetting',
        HexaplaSettings::SAVED_TLS => 'tlList',
        HexaplaSettings::DIFF_BY_WORD => 'diffByWord',
        HexaplaSettings::CASE_SENS_DIFF => 'caseSensitiveDiff',
        HexaplaSettings::SCROLL_TOGETHER => 'scrollTogether',
        HexaplaSettings::PIN_SIDEBAR => 'pinSidebar'];

    private string $tlSetting;
    private string $tlList;
    private int $allowsBehavior;
    private int $id;
    private bool $diffByWord;
    private bool $caseSensitiveDiff;
    private bool $scrollTogether;
    private bool $pinSidebar;

    public function __construct($userId, $settingsList, $permissions) {
        $this->allowsBehavior = $permissions;
        $this->id = $userId;
        foreach(self::SETTINGS_SETUP as $sqlSetting => $objSetting) {
            $value = ($settingsList[$sqlSetting] === 'false' ? false : $settingsList[$sqlSetting]);
            $this->$objSetting = $value;
            $cookie = HexaplaCookies::cookieFromSetting($sqlSetting);
            if ($cookie !== '') {
                setHexCookie($cookie, $value);
            }
        }
    }

    public function set_tlSetting($setting) {
        $this->tlSetting = $setting;
        $this->save();
    }

    public function set_tlList($list) {
        $this->tlList = $list;
        $this->save();
    }

    public function useSavedTl(): bool {
        return $this->tlSetting === self::USE_SAVED_TRANSLATIONS;
    }

    public function useLastTl(): bool {
        return $this->tlSetting === self::USE_LAST_TRANSLATIONS;
    }

    public function pinSidebar() {
        return $this->pinSidebar;
    }

    public function set_pinSidebar($pin) {
        $this->pinSidebar = $pin;
        $this->save();
    }

    public function scrollsTogether() {
        return $this->scrollTogether;
    }

    public function set_scroll($together) {
        $this->scrollTogether = $together;
        $this->save();
    }

    public function savedTls(): string {
        return $this->tlList;
    }

    public function canWriteNotes(): bool {
        return $this->allowsBehavior & HexaplaPermissions::NOTE;
    }

    public function id(): int {
        return $this->id;
    }

    public function diffsByWord(): bool {
        return $this->diffByWord;
    }

    public function diffsCaseSens(): bool {
        return $this->caseSensitiveDiff;
    }

    public function set_diffsByWord($value) {
        $this->diffByWord = $value;
        $this->save();
    }

    public function set_diffsCaseSens($value) {
        $this->caseSensitiveDiff = $value;
        $this->save();
    }

    private function save() {
        $insertArray = [];
        foreach(self::SETTINGS_SETUP as $sqlSetting => $objSetting) {
            $insertArray[] = [HexaplaUserSettings::USER_ID => $this->id,
                HexaplaUserSettings::SETTING => $sqlSetting,
                HexaplaUserSettings::VALUE => $this->$objSetting];
        }
        putData($db, HexaplaTables::USER_SETTINGS, $insertArray);
    }
}