<?php

namespace Hexapla;

function usx_import($simpleXml) {
  
}

// should return our internal identification for each book
function usx_bookcode($code) {  // or do we just add these to the database and deal with them that way?
  switch($code) {
    case 'GEN': //Genesis
    case 'EXO': //Exodus
    case 'LEV': //Leviticus
    case 'NUM': //Numbers
    case 'DEU': //Deuteronomy
    case 'JOS': //Joshua
    case 'JDG': //Judges
    case 'RUT': //Ruth
    case '1SA': //1 Samuel
    case '2SA': //2 Samuel
    case '1KI': //1 Kings
    case '2KI': //2 Kings
    case '1CH': //1 Chronicles
    case '2CH': //2 Chronicles
    case 'EZR': //Ezra
    case 'NEH': //Nehemiah
    case 'EST': //Esther (Hebrew)
    case 'JOB': //Job
    case 'PSA': //Psalms
    case 'PRO': //Proverbs
    case 'ECC': //Ecclesiastes
    case 'SNG': //Song of Songs
    case 'ISA': //Isaiah
    case 'JER': //Jeremiah
    case 'LAM': //Lamentations
    case 'EZK': //Ezekiel
    case 'DAN': //Daniel (Hebrew)
    case 'HOS': //Hosea
    case 'JOL': //Joel
    case 'AMO': //Amos
    case 'OBA': //Obadiah
    case 'JON': //Jonah
    case 'MIC': //Micah
    case 'NAM': //Nahum
    case 'HAB': //Habakkuk
    case 'ZEP': //Zephaniah
    case 'HAG': //Haggai
    case 'ZEC': //Zechariah
    case 'MAL': //Malachi
    case 'MAT': //Matthew
    case 'MRK': //Mark
    case 'LUK': //Luke
    case 'JHN': //John
    case 'ACT': //Acts
    case 'ROM': //Romans
    case '1CO': //1 Corinthians
    case '2CO': //2 Corinthians
    case 'GAL': //Galatians
    case 'EPH': //Ephesians
    case 'PHP': //Philippians
    case 'COL': //Colossians
    case '1TH': //1 Thessalonians
    case '2TH': //2 Thessalonians
    case '1TI': //1 Timothy
    case '2TI': //2 Timothy
    case 'TIT': //Titus
    case 'PHM': //Philemon
    case 'HEB': //Hebrews
    case 'JAS': //James
    case '1PE': //1 Peter
    case '2PE': //2 Peter
    case '1JN': //1 John
    case '2JN': //2 John
    case '3JN': //3 John
    case 'JUD': //Jude
    case 'REV': //Revelation
    case 'TOB': //Tobit
    case 'JDT': //Judith
    case 'ESG': //Esther Greek
    case 'WIS': //Wisdom of Solomon
    case 'SIR': //Sirach (Ecclesiasticus)
    case 'BAR': //Baruch
    case 'LJE': //Letter of Jeremiah
    case 'S3Y': //Song of 3 Young Men
    case 'SUS': //Susanna
    case 'BEL': //Bel and the Dragon
    case '1MA': //1 Maccabees
    case '2MA': //2 Maccabees
    case '3MA': //3 Maccabees
    case '4MA': //4 Maccabees
    case '1ES': //1 Esdras (Greek)
    case '2ES': //2 Esdras (Latin)
    case 'MAN': //Prayer of Manasseh
    case 'PS2': //Psalm 151
    case 'ODA': //Odes
    case 'PSS': //Psalms of Solomon
    case 'EZA': //Apocalypse of Ezra
    case '5EZ': //5 Ezra
    case '6EZ': //6 Ezra
    case 'DAG': //Daniel Greek
    case 'PS3': //Psalms 152-155
    case '2BA': //2 Baruch (Apocalypse)
    case 'LBA': //Letter of Baruch
    case 'JUB': //Jubilees
    case 'ENO': //Enoch
    case '1MQ': //1 Meqabyan
    case '2MQ': //2 Meqabyan
    case '3MQ': //3 Meqabyan
    case 'REP': //Reproof
    case '4BA': //4 Baruch
    case 'LAO': //Laodiceans
    case 'XXA': //Extra A (e.g. a hymnal)
    case 'XXB': //Extra B
    case 'XXC': //Extra C
    case 'XXD': //Extra D
    case 'XXE': //Extra E
    case 'XXF': //Extra F
    case 'XXG': //Extra G
    case 'FRT': //Front Matter
    case 'BAK': //Back Matter
    case 'OTH': //Other Matter
    case 'INT': //Introduction
    case 'CNC': //Concordance
    case 'GLO': //Glossary
    case 'TDX': //Topical Index
    case 'NDX': //Names Index
  }
}
