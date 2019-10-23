<?php

/****** OUR DATA STRUCTURE FOR SAVING TO SQL ******/
/**
wholeText									= []
	...["translation"] 						= Full name of translation
	...["translAbbrs"] 						= [list of abbreviation(s) of translation]
	...["uniqueId"]							= unique ID as given by XML document
	...["copyright"]						= []
		...["short"]						= short-form copyright info
		...["long"]							= long-form copyright info
		...["year"]							= copyright year
		...["owner"]						= copyright-holder
		...["rights"]						= usage rights (as best as we can determine)
	...["non-text"]							= [list of descriptions, et al, outside the text itself]
	...["numbering_system"]					= numbering system for uniquely identifying verses later
	...["books"]							= [list of {book_name} elements]
		...[{book_name}]					= []
			...["abbrs"]					= [list of abbreviation(s) of book name]
			...["non-text"]					= [list of descriptions, introductions, etc., outside the text itself]
			...["verses"]					= [list of {verse_num} elements]
				...[{verse_number}]			= []
					...["reference"]		= chapter number, verse number
					...[{word_number}]		= []
						...["value"]		= the word
						...["comparable"]	= lowercase-only version of the word
						...["strong"]		= the associated Strong's Concordance ID
						...["uniqueId"]		= the associated unique identification number, if not Strong's
						...["is_bef_punc"]	= whether or not the "word" is precedent punctuation (e.g., quote before word)
						...["is_aft_punc"]	= whether or not the "word" is subsequent punctuation (e.g., comma)
**/

/****** XML ******/
libxml_use_internal_errors(true);
$sourceFile = ""; // file path
$parsedXml = simplexml_load_file($sourceFile);
if ($parsedXml !== false) {
	$mainElementName = strtoupper($parsedXml->getName());

	// Open Scripture Information Standard (OSIS) --> http://crosswire.org/osis/OSIS%202.1.1%20User%20Manual%2006March2006.pdf
	if ($mainElementName == "OSIS") {
		$corpores = $parsedXml->osisCorpus;
		if ($corpores->count() > 0) {
			foreach ($corpores as $corpus) {
				$header = $corpus->header;
				$texts = $corpus->osisText;
				foreach ($texts as $text) {
					$attr = $text->attributes();
					addToData("translAbbrs", $attr->osisIDWork,$wholeText);
					$bookId = $attr->osisID; // if has value...?
					// if canonical... (i.e., if actual text)
					// if not canonical... (commentary, etc.) --> $attr->annotateRef is appropriate here
					$textHeader = $text->header; // handle only if none for corpus
					$divs = $text->div;
					foreach ($divs as $div) {
						$attr = $div->attributes();
						$type = $attr->type;
						$divHead = $div->head; // element with title, probably
						// bookGroup
							// book
								// majorSection
									// section
										// subSection
											// from here, get ->verse elements, I guess?
						
						// other potential types:
						// acknowledgement, afterword, annotant, appendix, article, back, bibliography
						// body, bridge, chapter, colophon, commentary, concordance, coverPage
						// dedication, devotional, entry, front, gazetteer, glossary, imprimatur, index
						// introduction, map, outline, paragraph, part, preface, publicationData
						// summary, tableofContents, titlePage, custom types starting with "x-"
					}
					// separately handle (rare?) case of ->chapter elements -- OSIS documentation says to avoid, but uses in examples, so...?
				}
			}
		} else {
			$texts = $parsedXml->osisText;
			// refactor above and call that from here
		}
	}

	// Theological Markup Language (ThML) --> https://www.ccel.org/ThML/
	else if ($mainElementName == "THML") {
		
	}

	// Zefania --> http://bgfdb.de/zefaniaxml/bml/
	else if ($mainElementName == "XMLBIBLE" || $mainElementName == "X") {
		
	}

	// Unified Scripture Format XML (USFX) --> https://ebible.org/usfx/usfx.htm
	else if ($mainElementName == "USFX") {
		
	}

	// XML Scripture Encoding Model (XSEM) --> https://scripts.sil.org/cms/scripts/page.php?site_id=nrsi&id=XSEM
	else if ($mainElementName == "SCRIPTURE") {
		
	}

	// Unified Scripture XML (USX) --> https://ubsicap.github.io/usx/
	else if ($mainElementName == "USX") {
		
	}
}

// apparently some TEI Bibles exist... do we want to deal with those?

/****** NON-XML ******/
else {
	// check errors using foreach (libxml_get_errors() as $error) $error->message
	
	// General Bible Format (GBF) --> https://ebible.org/bible/gbf.htm
	
	// Unified Standard Format Markers (USFM) --> https://ubsicap.github.io/usfm/about/index.html
	
}


function addToData($dataKey, $dataPiece, &$data) {
	// if dataPiece is null, ignore
	// if wholeText[dataKey] is list and doesn't have dataPiece, add dataPiece
	// if wholeText[dataKey] is not list and isn't populated, set value
	// otherwise, ignore
}

function osisGetDivision($currentGroup) {
    $next = null;
    switch(strtoupper($currentGroup->getName())) {
        case "OSIS":
            $next = ($currentGroup->osisCorpus ? $currentGroup->osisCorpus : $currentGroup->osisText);
            break;
        case "OSISCORPUS":
            $next = $currentGroup->osisText;
            break;
        case "DIV":
            $next = ($currentGroup->div ? $currentGroup->div :
                        ($currentGroup->chapter ? $currentGroup->chapter : $currentGroup->verse));
            break;
        case "CHAPTER":
            $next = $currentGroup->verse;
            break;
        default: // including osisText elements
            $next = $currentGroup->div;
            break;
    }
    return $next;
}

function osisGetHeader($corpusOrText) {
    if ($corpusOrText->getName() === "osisCorpus" || $corpusOrText->getName() === "osisText") {
        return $corpusOrText->header;
    } else {
        return null;
    }
}

function osisGetAllVerses($element, &$verseCollection) {
    $oneLevelDown = osisGetDivision($element);
    if (!$oneLevelDown || $oneLevelDown->count() === 0) {
        if (osisIsCanonical($element)) {
            $verseCollection[] = $element;
        }
    } else {
        foreach ($oneLevelDown as $el) {
            osisGetAllVerses($el, $verseCollection);
        }
    }
    return;
}

function osisIsCanonical($element) {
    $attr = $element->attributes();
    $canonicalValue = (isset($attr->canonical) ? $attr->canonical : "true");
    return ($canonicalValue === "true");
}