<?php

namespace Hexapla;

require_once "sql-functions.php";
require_once "cookie-functions.php";
$db = $db ?? null;

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
    const SETTINGS_DEFAULTS = [HexaplaSettings::DEFAULT_LOAD => self::USE_LAST_TRANSLATIONS,
        HexaplaSettings::SAVED_TLS => 1,
        HexaplaSettings::DIFF_BY_WORD => true,
        HexaplaSettings::CASE_SENS_DIFF => false,
        HexaplaSettings::SCROLL_TOGETHER => false,
        HexaplaSettings::PIN_SIDEBAR => false];

    private string $tlSetting;
    private string $tlList;
    private int $allowsBehavior;
    private int $id;
    private bool $diffByWord;
    private bool $caseSensitiveDiff;
    private bool $scrollTogether;
    private bool $pinSidebar;

    public function __construct($userId, $settingsList = [], $permissions = -1) {
        $this->id = $userId;
        if ($permissions === -1) {
            $permResult = getData($db,
                HexaplaTables::USER_GROUP,
                [HexaplaUserGroup::ALLOWS_ACTIONS],
                [HexaplaTables::USER . "." . HexaplaUser::ID => $userId], [],
                [new HexaplaJoin(HexaplaTables::USER,
                    HexaplaTables::USER_GROUP,HexaplaUserGroup::ID,
                    HexaplaTables::USER, HexaplaUser::GROUP_ID)]);
            $permData = pg_fetch_assoc($permResult);
            $permissions = bindec($permData[HexaplaUserGroup::ALLOWS_ACTIONS]);
        }
        $this->allowsBehavior = $permissions;
        if (count($settingsList) === 0) {
            $setResult = getData($db, HexaplaTables::USER_SETTINGS, [], [HexaplaUserSettings::USER_ID => $userId]);
            while (($row = pg_fetch_assoc($setResult)) !== false) {
                $settingsList[$row[HexaplaUserSettings::SETTING]] = $row[HexaplaUserSettings::VALUE];
            }
        }
        foreach(self::SETTINGS_SETUP as $sqlSetting => $objSetting) {
            if (!isset($settingsList[$sqlSetting])) $settingsList[$sqlSetting] = self::SETTINGS_DEFAULTS[$sqlSetting];
            if (in_array($settingsList[$sqlSetting], ['false', 'f'])) $settingsList[$sqlSetting] = false;
            $value = $settingsList[$sqlSetting];
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

    public function set($setting, $value) {
        switch($setting) {
            case HexaplaSettings::DEFAULT_LOAD:
                $this->set_tlSetting($value);
                break;
            case HexaplaSettings::PIN_SIDEBAR:
                $this->set_pinSidebar($value);
                break;
            case HexaplaSettings::SCROLL_TOGETHER:
                $this->set_scroll($value);
                break;
            case HexaplaSettings::CASE_SENS_DIFF:
                $this->set_diffsCaseSens($value);
                break;
            case HexaplaSettings::DIFF_BY_WORD:
                $this->set_diffsByWord($value);
                break;
            case HexaplaSettings::SAVED_TLS:
                $this->set_tlList($value);
                break;
            default:
                // FIXME: error
        }
    }
}

class HexaplaThemes {
    const PARCHMENT = 'parchment';
    const LEATHER_BOUND = 'leather-bound';
    const JONAH = 'jonah';
    const LITURGICAL = 'liturgical';

    const ALL = [self::PARCHMENT, self::LEATHER_BOUND, self::JONAH, self::LITURGICAL];
}

class HexaplaShades {
    const LIGHT = 'light';
    const DARK = 'dark';

    const ALL = [self::LIGHT, self::DARK];
}