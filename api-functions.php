<?php

require_once "dbconnect.php";
require_once "general-functions.php";

function getDefinitionAPI($word, $langId) {
    global $oxfordAppID, $oxfordAppKey;
    if ($langId === '1') {
        // TODO: after upgrading to Developer-level API authorization, switch to "words" API instead of "lemmas" + "entries"
        $oxfordBaseUrl = 'https://od-api.oxforddictionaries.com/api/v2';
        $curl = curl_init($oxfordBaseUrl . "/lemmas/en/" . $word);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['app_id: ' . $oxfordAppID, 'app_key: ' . $oxfordAppKey]);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $lemmaResult = curl_exec($curl);
        if ($lemmaResult === false) return false;
        $lemmaResult = json_decode($lemmaResult, true);
        $lemma = $lemmaResult['results'][0]['lexicalEntries'][0]['inflectionOf'][0]['id'];
        curl_setopt($curl, CURLOPT_URL, $oxfordBaseUrl . '/entries/en-us/' . $lemma);
        $result = curl_exec($curl);
        if ($result === false) return false;
        $result = json_decode($result, true);
        $output['lemma'] = $lemma;
        $result = $result['results'][0]['lexicalEntries'][0]['entries'][0];
        $output['etymology'] = $result['etymologies'];
        foreach($result['pronunciations'] as $pronunciation) {
            if (isset($pronunciation['audioFile'])) {
                $output['pronunciation'][] = ['phonetic' => $pronunciation['phoneticSpelling'], 'link' => $pronunciation['audioFile']];
            }
        }
        foreach($result['senses'] as $sense) {
            $output['definition'][] = $sense['definitions'];
        }
        return $output;
    }
    return false;
}

function getLemmaAPI($word, $langId) {
    global $oxfordAppID, $oxfordAppKey;
    if ($langId === '1') {
        $oxfordLemmaUrl = 'https://od-api.oxforddictionaries.com/api/v2/lemmas/en/';
        $curl = curl_init($oxfordLemmaUrl . $word);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['app_id: ' . $oxfordAppID, 'app_key: ' . $oxfordAppKey]);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $lemmaResult = curl_exec($curl);
        if ($lemmaResult === false) return false;
        return $lemmaResult['results'][0]['lexicalEntries'][0]['inflectionOf'][0]['id'];
    }
}

function getInflectionsAPI($word, $langId) {
    // FIXME: this API won't work until we upgrade
    global $oxfordAppID, $oxfordAppKey;
    $results = [];
    if ($langId === '1') {
        $oxfordInflectionUrl = 'https://od-api.oxforddictionaries.com/api/v2/inflections/en-us/';
        $curl = curl_init($oxfordInflectionUrl . $word);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['app_id: ' . $oxfordAppID, 'app_key: ' . $oxfordAppKey]);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $inflectionResult = curl_exec($curl);
        if ($inflectionResult === false) return false;
        foreach ($inflectionResult['results'][0]['lexicalEntries'][0]['inflections'] as $inflection) {
            $results[] = $inflection['inflectedForm'];
        }
    }
    return $results;
}