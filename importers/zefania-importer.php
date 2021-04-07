<?php

namespace Hexapla;

function import_zefania($simpleXml) {
  /*$attr = $simpleXml->attributes();
  $_IMPORTDATA['name'] = $attr->biblename;
  $_IMPORTDATA['version'] = $attr->version;
  
  $info = zefania_getElement($simpleXml, 'INFORMATION');
  /* title, creator, description, publisher, subject, contributors, date, type, format, identifier, source, language, coverage, rights */
  /*
  $books = zefania_getElement($simpleXml, 'BIBLEBOOK');
  foreach ($books as $book) {
    $bookAttr = $book->attributes(); // bname (full name), bnumber (some unambiguous number?), bsname (short name)
    $chapters = zefania_getElement($book, 'CHAPTER');
    foreach ($chapters as $chapter) {
      $chapterNumber = $chapter->attributes()->cnumber;
      // include PROLOG for chapters? or REMARKs?
      $verses = zefania_getElement($chapter, 'VERS');
      foreach ($verses as $verse) {
        // do stuff with verses
        // GRAM (grammatical info), NOTE (annotation), STYLE (??), SUP (superscript?), XREF (cross-reference), BR
      }
    }
  }*/
}
/*
function zefania_getElement($containingElement, $elementName) {
  if ($containingElement->{strtoupper($elementName)}->count() > 0) {
    return $containingElement->{strtoupper($elementName)};
  } else if ($containingElement->{strtolower($elementName)}->count() > 0) {
    return $containingElement->{strtolower($elementName)};
  } else {
    return $containingElement->{substr(strtolower($elementName), 0, 1)};
  }
}
*/