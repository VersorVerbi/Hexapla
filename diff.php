<?php

function noSpecialChars($str1, $str2, &$arr1, &$arr2) { // language variable
    $noSpecs1 = preg_replace('/[^ A-Za-z\'’]/',' ',$str1); // base special characters on language
    $noSpecs2 = preg_replace('/[^ A-Za-z\'’]/',' ',$str2); // "
    $arr1 = explode(' ', $noSpecs1);
    $arr2 = explode(' ', $noSpecs2);
    array_unshift($arr1,"");
    array_unshift($arr2,"");
}

function noHtml($str) {
    $lt = 0;
    $gt = 0;
    //echo "String at first: $str<BR>"; $i = 1;
    while (strpos($str, '<', $lt) !== FALSE) {
        $lt = strpos($str, '<', $lt);
        $gt = strpos($str, '>', $lt);
        $str = substr_replace($str, "", $lt, $gt - $lt + 1);
        //echo "String after loop " . $i++ . ": $str<BR>";
    }
    //echo "<BR>";
    return $str;
}

function diff($str1, $str2, $byWord, $caseSensitive, &$outStrs, $ltClass, $rtClass) {
    // strip out HTML? Put it back?
    $noHtml1 = noHtml($str1);
    $noHtml2 = $str2;
    
    // by word
    if ($byWord) {
        // strip special characters to evaluate by word
        noSpecialChars($noHtml1, $noHtml2, $arr1, $arr2);
        
        // prepare the longest common subsequence (LCS) framework
        for ($i = 0; $i < count($arr1); $i++) {
            $lcs[$i][0] = 0;
        }
        for ($j = 0; $j < count($arr2); $j++) {
            $lcs[0][$j] = 0;
        }
        
        // populate the LCS table
        for ($i = 1; $i < sizeof($arr1); $i++) {
            for ($j = 1; $j < sizeof($arr2); $j++) {
                if (myStrCp($arr1[$i], $arr2[$j], $caseSensitive) == 0) {
                    $lcs[$i][$j] = $lcs[$i-1][$j-1] + 1;
                } else {
                    $lcs[$i][$j] = max($lcs[$i-1][$j],$lcs[$i][$j-1]);
                }
            }
        }
        
        // create the output array
        if (sizeof($arr1) > 256 || sizeof($arr2) > 256) {
            // abort
            $outStrs[0] = $str1;
            $outStrs[1] = $str2;
            return -1;
        }
        outputDiffArr($lcs, $arr1, $arr2, sizeof($arr1) - 1, sizeof($arr2) - 1, $finalArr, $caseSensitive);
        $ltOutput = getOutputWords($finalArr[0], $str1, $arr1, $ltClass);
        $rtOutput = getOutputWords($finalArr[1], $str2, $arr2, $rtClass);
        
    // by character
    } else {
        // no need to strip special characters in character diffing
        // prepare the longest common subsequence (LCS) framework
        for ($i = 0; $i < strlen($str1); $i++) {
            $lcs[$i][0] = 0;
        }
        for ($j = 0; $j < strlen($str2); $j++) {
            $lcs[0][$j] = 0;
        }
        
        // populate the LCS table
        for ($i = 1; $i <= strlen($noHtml1); $i++) {
            for ($j = 1; $j <= strlen($noHtml2); $j++) {
                $cpl = substr($noHtml1, $i, 1); // cpl = compare left
                $cpr = substr($noHtml2, $j, 1); // cpr = compare right
                if (myStrCp($cpl, $cpr, $caseSensitive) == 0) {
                    $lcs[$i][$j] = $lcs[$i-1][$j-1] + 1;
                } else {
                    $lcs[$i][$j] = max($lcs[$i-1][$j],$lcs[$i][$j-1]);
                }
            }
        }
        
        // create the output array
        if (strlen($noHtml1) > 256 || strlen($noHtml2) > 256) {
            // abort
            $outStrs[0] = $str1;
            $outStrs[1] = $str2;
            return -1;
        }
        outputDiff($lcs, $noHtml1, $noHtml2, strlen($noHtml1) - 1, strlen($noHtml2) - 1, $finalArr, $caseSensitive);
        $ltOutput = getOutput($finalArr[0], $str1, $ltClass);
        $rtOutput = getOutput($finalArr[1], $str2, $rtClass);
    }
    
    $outStrs[0] = $ltOutput;
    $outStrs[1] = $rtOutput;
    return 0;
}

function getOutputWords($finalGroup, $str, $arr, $class) {
    $output = "";
    $lastEnd = 0;
    //echo "Initial output string: $str<BR>";
    for ($i = 1; $i < sizeof($finalGroup); $i++) {
        // get START
        $pattern = '/\\b' . $arr[$i] . '\\b/';
        if ($i == 1) {
            preg_match($pattern, $str, $matches, PREG_OFFSET_CAPTURE);
        } elseif (!empty($arr[$i])) {
            preg_match($pattern, $str, $matches, PREG_OFFSET_CAPTURE, $lastEnd); 
        } else {
            continue;
        }
        if (empty($matches)) continue;
        $start = $matches[0][1];
        
        // get END
        if ($i < sizeof($arr)-1) {
            for ($j = $i + 1; $j < sizeof($arr); $j++) {
                if (!empty($arr[$j])) {
                    $pattern = '/\\b' . $arr[$j] . '\\b/';
                    preg_match($pattern, $str, $matches, PREG_OFFSET_CAPTURE, $start);
                    // print_r($matches); echo "<BR><BR>";
                    if (empty($matches)) continue;
                    $end = $matches[0][1];
                    break;
                }
            }
            if ($j == sizeof($arr)) {
                $end = strlen($str);
            }
        } else {
            $end = strlen($str);
        }
        
        $textToAdd = substr($str, $start, $end - $start);
        
        //echo "Piece: " . $arr[$i] . "<BR />";
        //echo "Start: $start || End: $end<BR />";
        //echo "Text to Add: $textToAdd<BR /><BR />";
        
        if ($finalGroup[$i] == 0) {
            $output .= $textToAdd;
        } else {
            $output .= '<span class="';
            $output .= $class;
            $space = strpos($textToAdd, ' ');
            if ($space !== FALSE) {
                $output .= '">' . substr($textToAdd, 0, $space) . '</span>' . substr($textToAdd, $space, strlen($textToAdd) - $space);
            } else {
                $output .= '">' . $textToAdd . '</span>';
            }
        }
        
        //echo "Output after loop $i: $output<BR>";
        
        $lastEnd = $end;
        
        //echo $output . "<BR /><BR />";
    }
    //echo "<BR>";
    return $output;
}

function getOutput($finalGroup, $str, $class) {
    $output = "";
    //echo "<PRE>";print_r($finalGroup);echo "</PRE>";
    for ($i = 0; $i < strlen($str); $i++) {
        if ($finalGroup[$i] == 0) {
            for ($j = $i; $j < strlen($str); $j++) {
                if ($finalGroup[$j] != 0) { break; }
                $output .= substr($str, $j, 1);
            }
            $i = $j - 1;
        } else {
            $output .= '<span class="';
            $output .= $class;
            $output .= '">';
            for ($j = $i; $j < strlen($str); $j++) {
                if ($finalGroup[$j] == 0) { break; }
                $output .= substr($str, $j, 1);
            }
            $i = $j - 1;
            $output .= '</span>';
        }
    }
    return $output;
}

function outputDiffArr($lcs, $arr1, $arr2, $i, $j, &$final, $caseSensitive) {
    //echo "Starting outputDiffArr for the " . ++$GLOBALS['n'] . "th time<BR />";
    /*if ($GLOBALS['n'] == 1) {
        echo "Arr1 = " . implode(' ',$arr1) . "<BR />";
        echo "Arr2 = " . implode(' ',$arr2) . "<BR />";
    }*/
    //echo "i = $i and j = $j<BR />";
    if ($i > 0 && $j > 0 && myStrCp($arr1[$i], $arr2[$j], $caseSensitive) == 0) {
        //echo "i and j are identical. Reducing both. Adding " . $arr1[$i] . "<BR /><BR />";
        $final[0][$i] = 0;
        $final[1][$j] = 0;
        return outputDiffArr($lcs, $arr1, $arr2, $i-1, $j-1, $final, $caseSensitive);
    } elseif ($j > 0 && ($i == 0 || $lcs[$i][$j-1] >= $lcs[$i-1][$j])) {
        //echo "i and j are different. Reducing j. Adding " . $arr2[$j] . "<BR /><BR />";
        $final[1][$j] = 1;
        return outputDiffArr($lcs, $arr1, $arr2, $i, $j-1, $final, $caseSensitive);
    } elseif ($i > 0 && ($j == 0 || $lcs[$i][$j-1] < $lcs[$i-1][$j])) {
        //echo "i and j are different. Reducing i. Adding " . $arr1[$i] . "<BR /><BR />";
        $final[0][$i] = 1;
        return outputDiffArr($lcs, $arr1, $arr2, $i-1, $j, $final, $caseSensitive);
    } else {
        //echo "i and j are zero.<BR /><BR />";
        return "";
    }
}

function outputDiff($lcs, $str1, $str2, $i, $j, &$final, $caseSensitive) {
    //echo "Started outputDiff " . $GLOBALS['recurCount']++ . " time(s)<BR />";
    //echo "Str1 = $str1 and Str2 = $str2 and i = $i and j = $j<BR />";
    $ltStr = substr($str1, $i, 1);
    $rtStr = substr($str2, $j, 1);
    //echo "Comparing $ltStr and $rtStr...<BR />";
    if ($i >= 0 && $j >= 0 && myStrCp($ltStr, $rtStr, $caseSensitive) == 0) {
    //    echo "Values are identical. Reducing both i and j. Adding <span class='normal'>$ltStr</span>.<BR /><BR />";
        $final[0][$i] = 0;
        $final[1][$j] = 0;
        return outputDiff($lcs, $str1, $str2, $i-1, $j-1, $final, $caseSensitive);
    } elseif ($j >= 0 && ($i < 0 || $lcs[$i][$j-1] >= $lcs[$i-1][$j])) {
    //    echo "Values differ. Adding str1 value.<BR /><BR />";
        $final[1][$j] = 1;
        return outputDiff($lcs, $str1, $str2, $i, $j-1, $final, $caseSensitive);
    } elseif ($i >= 0 && ($j < 0 || $lcs[$i][$j-1] < $lcs[$i-1][$j])) {
    //    echo "Values differ. Adding str2 value.<BR /><BR />";
        $final[0][$i] = 1;
        return outputDiff($lcs, $str1, $str2, $i-1, $j, $final, $caseSensitive);
    } else {
    //    echo "To zero. Finished.";
        return "";
    }
}

function myStrCp($str1, $str2, $caseSensitive) {
    if ($caseSensitive) {
        return strcmp($str1, $str2);
    } else {
        return strcasecmp($str1, $str2);
    }
}

?>