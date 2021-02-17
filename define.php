<?php

$sourceWords = json_decode($_POST['sourceWords']);
$translationId = $_POST['tid'];
$text = $_POST['text'];

print_r($sourceWords);
print_r($translationId);
print_r($text);

/* TODO:
    - get lang from translation id
    - use sourceWords to get definition from Strong's ID -> lemma -> definition
    - if current lang is Greek (or Hebrew/Latin/...?) use word form search -> lemma -> definition
    - if no definition in database, get from external API and add to database
    - echo json_encode'd array of definitions w/ dictionary info, lang info, and definition
*/