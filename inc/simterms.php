<?php

/**
 * \file
 * \brief PHP Utility Functions to calculate similar terms of a term.
 * 
 * @package Lwt
 * @author  LWT Project <lwt-project@hotmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/html/simterms_8php.html
 */

require_once 'session_utility.php';


/**
 * @return string[]
 *
 * @psalm-return array<0|positive-int, string>
 */
function letterPairs($str): array 
{
    $numPairs = mb_strlen($str) - 1;
    $pairs = array();
    for ($i = 0; $i < $numPairs; $i ++) {
        $pairs[$i] = mb_substr($str, $i, 2);
    }
    return $pairs;
}

/**
 * @psalm-return list<mixed>
 */
function wordLetterPairs($str): array 
{
    $allPairs = array();
    $words = explode(' ', $str);
    for ($w = 0; $w < count($words); $w ++) {
        $pairsInWord = letterPairs($words[$w]);
        for ($p = 0; $p < count($pairsInWord); $p ++) {
            $allPairs[$pairsInWord[$p]] = $pairsInWord[$p];
        }
    }
    return array_values($allPairs);
}

/**
 * Similarity ranking of two UTF-8 strings $str1 and $str2
 * 
 * @return float SimilarityRanking
 */
function getSimilarityRanking($str1, $str2) 
{
    // Source http://www.catalysoft.com/articles/StrikeAMatch.html
    // Source http://stackoverflow.com/questions/653157
    $pairs1 = wordLetterPairs($str1);
    $pairs2 = wordLetterPairs($str2);
    $union = count($pairs1) + count($pairs2);
    if ($union == 0) { 
        return 0; 
    }
    $intersection = count(array_intersect($pairs1, $pairs2));
    return 2 * $intersection / $union;
}

/**
 * For a language $lang_id and a term $compared_term (UTF-8).
 * If string is already in database, it will be excluded in results.
 *
 * @return string[] All $max_count wordids with a similarity ranking > $min_ranking, sorted decending
 *
 * @psalm-return array<positive-int, string>
 */
function get_similar_terms(
    $lang_id, $compared_term, $max_count, $min_ranking
): array { 


    $compared_term_lc = mb_strtolower($compared_term, 'UTF-8');
    $sql = "select WoID, WoTextLC from words where WoLgID = " . $lang_id . " AND WoTextLC <> " . convert_string_to_sqlsyntax($compared_term_lc);
    $res = do_mysqli_query($sql);
    $termlsd = array();
    while ($record = mysqli_fetch_assoc($res)) {
        $termlsd[$record["WoID"]] = getSimilarityRanking($compared_term_lc, $record["WoTextLC"]);
    }
    mysqli_free_result($res);
    arsort($termlsd, SORT_NUMERIC);
    $r = array();
    $i = 0;
    foreach ($termlsd as $key => $val) {
        if ($i >= $max_count || $val < $min_ranking) { 
            break; 
        }
        $i++;
        $r[$i] = $key;
    }
    return $r;
}

/**
 * Get Term and translation of terms in termid array (calculated 
 * in function get_similar_terms(...)) as string for echo
 */
function print_similar_terms($lang_id, $compared_term): string 
{

    $max_count = (int)getSettingWithDefault("set-similar-terms-count");
    if ($max_count <= 0) { 
        return ''; 
    }
    if (trim($compared_term) == '') { 
        return '&nbsp;'; 
    } 
    $compare = tohtml($compared_term);
    $termarr = get_similar_terms($lang_id, $compared_term, $max_count, 0.33);
    $rarr = array();
    foreach ($termarr as $termid) {
        $sql = "select WoText, WoTranslation, WoRomanization from words where WoID = " . $termid;
        $res = do_mysqli_query($sql);
        if ($record = mysqli_fetch_assoc($res)) {
            $term = tohtml($record["WoText"]);
            if (stripos($compare, $term) !== false) {
                $term = '<span class="red3">' . $term . '</span>'; 
            }
            else {
                $term = str_replace($compare, '<span class="red3"><u>' . $compare . '</u></span>', $term); 
            }
            $tra = $record["WoTranslation"];
            if ($tra == "*") { $tra = "???"; 
            }
            if (trim($record["WoRomanization"]) !== '') {
                $romd = " [" . $record["WoRomanization"] . "]";
                $rom = $record["WoRomanization"];
            }
            else {
                $romd = "";
                $rom = "";
            }
            $rarr[] = '<img class="clickedit" src="icn/tick-button-small.png" title="Copy → Translation &amp; Romanization Field(s)" onclick="setTransRoman(' . prepare_textdata_js($tra) . ',' . prepare_textdata_js($rom) . ');" /> ' . $term . tohtml($romd) . ' — ' . tohtml($tra) . '<br />';
        }
        mysqli_free_result($res);
    }
    if(count($rarr) == 0) {
        return "(none)"; 
    }
    else {
        return implode($rarr); 
    }
}

// -------------------------------------------------------------

function print_similar_terms_tabrow(): void 
{
    if ((int)getSettingWithDefault("set-similar-terms-count") > 0) { 
        echo '<tr><td class="td1 right">Similar<br />Terms:</td><td class="td1"><span id="simwords" class="smaller">&nbsp;</span></td></tr>'; 
    }
} 


?>
