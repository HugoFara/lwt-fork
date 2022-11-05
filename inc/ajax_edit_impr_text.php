<?php
/**
 * \file
 * \brief Display table for Improved Annotation (Edit Mode), 
 * 
 * Ajax call in print_impr_text.php
 * Call: inc/ajax_edit_impr_text.php?id=[textid]
 * 
 * @package Lwt
 * @author  LWT Project <lwt-project@hotmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/html/ajax__edit__impr__text_8php.html
 * @since   1.5.0
 */

require_once __DIR__ . '/session_utility.php';

/**
 * Make the translation for a word.
 * 
 * @param int      $i     Index
 * @param int|null $wid   Word ID or null 
 * @param string   $trans Translation
 * @param string   $word
 * @param int      $lang  Language ID
 * 
 * @return string HTML-formatted string
 */
function make_trans($i, $wid, $trans, $word, $lang): string 
{
    global $tbpref;    
    $trans = trim($trans);
    $widset = is_numeric($wid);
    if ($widset) {
        $alltrans = get_first_value("SELECT WoTranslation AS value FROM words WHERE WoID = " . $wid);
        $transarr = preg_split('/[' . get_sepas()  . ']/u', $alltrans);
        $r = "";
        $set = false;
        foreach ($transarr as $t) {
            $tt = trim($t);
            if ($tt == '*' || $tt == '') { 
                continue; 
            }
            if (!$set && $tt == $trans) {
                $set = true;
                $r .= 
                '<span class="nowrap">
                    <input class="impr-ann-radio" checked="checked" type="radio" name="rg' . $i . '" value="' . tohtml($tt) . '" />
                    &nbsp;' . tohtml($tt) . '
                </span>
                <br /> ';
            } else {
                $r .= 
                '<span class="nowrap">
                    <input class="impr-ann-radio" type="radio" name="rg' . $i . '" value="' . tohtml($tt) . '" />
                    &nbsp;' . tohtml($tt) . '
                </span> 
                <br />  ';
            }
        }
        if (!$set) {
            $r .= 
            '<span class="nowrap">
                <input class="impr-ann-radio" checked="checked" type="radio" name="rg' . $i . '" value="" />
                &nbsp;
                <input class="impr-ann-text" type="text" name="tx' . $i . '" id="tx' . $i . '" value="' . tohtml($trans) . '" maxlength="50" size="40" />';
        } else {
            $r .= 
            '<span class="nowrap">
                <input class="impr-ann-radio" type="radio" name="rg' . $i . '" value="" />
                &nbsp;
                <input class="impr-ann-text" type="text" name="tx' . $i . '" id="tx' . $i . '" value="" maxlength="50" size="40" />';
        }
    } else {
        $r = 
        '<span class="nowrap">
            <input checked="checked" type="radio" name="rg' . $i . '" value="" />
            &nbsp;
            <input class="impr-ann-text" type="text" name="tx' . $i . '" id="tx' . $i . '" value="' . tohtml($trans) . '" maxlength="50" size="40" />';
    }
    $r .= 
    ' &nbsp;
    <img class="click" src="icn/eraser.png" title="Erase Text Field" alt="Erase Text Field" onclick="$(\'#tx' . $i . '\').val(\'\').trigger(\'change\');" />
     &nbsp;
    <img class="click" src="icn/star.png" title="* (Set to Term)" alt="* (Set to Term)" onclick="$(\'#tx' . $i . '\').val(\'*\').trigger(\'change\');" />';
    if ($widset) {
        $r .= ' &nbsp;
        <img class="click" src="icn/plus-button.png" title="Save another translation to existent term" alt="Save another translation to existent term" onclick="addTermTranslation(' . $wid . ', \'#tx' . $i . '\',\'\',' . $lang . ');" />'; 
    } else { 
        $r .= ' &nbsp;
        <img class="click" src="icn/plus-button.png" title="Save translation to new term" alt="Save translation to new term" onclick="addTermTranslation(0, \'#tx' . $i . '\',' . prepare_textdata_js($word) . ',' . $lang . ');" />'; 
    }
    $r .= '&nbsp;&nbsp;<span id="wait' . $i . '"><img src="icn/empty.gif" /></span></span>';
    return $r;
}

/**
 * Prepare the form for printed text.
 * 
 * @param int    $textid Text ID
 * @param string $wordlc Lowercase word
 * 
 * @return string[] $r and $rr.
 * 
 * @global string $tbpref Database table prefix.
 */
function make_form($textid, $wordlc)
{ 
    global $tbpref;
    $sql = 'SELECT TxLgID, TxAnnotatedText FROM texts WHERE TxID = ' . $textid;
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    $langid = $record['TxLgID'];
    $ann = $record['TxAnnotatedText'];
    if (strlen($ann) > 0) {
        $ann = recreate_save_ann($textid, $ann);
    }
    mysqli_free_result($res);
    
    $sql = 'SELECT LgTextSize, LgRightToLeft FROM languages WHERE LgID = ' . $langid;
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    $textsize = (int)$record['LgTextSize'];
    if ($textsize > 100) { 
        $textsize = intval($textsize * 0.8); 
    }
    $rtlScript = $record['LgRightToLeft'];
    mysqli_free_result($res);
    
    $rr = "";
    $r = 
    '<form action="" method="post">
        <table class="tab1" cellspacing="0" cellpadding="5">
            <tr>
                <th class="th1 center">Text</th>
                <th class="th1 center">Dict.</th>
                <th class="th1 center">Edit<br />Term</th>
                <th class="th1 center">
                    Term Translations (Delim.: ' . tohtml(getSettingWithDefault('set-term-translation-delimiters')) . ')
                    <br />
                    <input type="button" value="Reload" onclick="do_ajax_edit_impr_text(0,\'\');" />
                </th>
            </tr>';
    $items = preg_split('/[\n]/u', $ann);
    $i = 0;
    $nontermbuffer ='';
    foreach ($items as $item) {
        $i++;
        $vals = preg_split('/[\t]/u', $item);
        if ($vals[0] > -1) {
            if ($nontermbuffer != '') {
                $r .= '<tr>
                    <td class="td1 center" style="font-size:' . $textsize . '%;">' . 
                        $nontermbuffer .
                    '</td>
                    <td class="td1 right" colspan="3">
                    <img class="click" src="icn/tick.png" title="Back to \'Display/Print Mode\'" alt="Back to \'Display/Print Mode\'" onclick="location.href=\'print_impr_text.php?text=' . $textid . '\';" />
                    </td>
                </tr>';
                $nontermbuffer ='';
            }
            $wid = null;
            $trans = '';
            if (count($vals) > 2) {
                $wid = $vals[2];
                if (is_numeric($wid)) {
                    $temp_wid = (int)get_first_value(
                        "SELECT COUNT(WoID) AS value 
                        FROM words 
                        WHERE WoID = ". $wid
                    );
                    if ($temp_wid < 1) { 
                        $wid = null; 
                    }
                }
            }
            if (count($vals) > 3) { 
                $trans = $vals[3]; 
            }
            $r .= '<tr><td class="td1 center" style="font-size:' . $textsize . '%;"' . 
            ($rtlScript ? ' dir="rtl"' : '') . '><span id="term' . $i . '">';
            $r .= tohtml($vals[1]);
            $r .= '</span></td><td class="td1 center" nowrap="nowrap">';
            $r .= makeDictLinks($langid, prepare_textdata_js($vals[1]));
            $r .= '</td><td class="td1 center"><span id="editlink' . $i . '">';
            if ($wid === null) {
                $plus = '&nbsp;';
            } else {
                $plus = '<a name="rec' . $i . '"></a>
                <span class="click" onclick="oewin(\'edit_word.php?fromAnn=\' + $(document).scrollTop() + \'&amp;wid=' . $wid . '\');">
                    <img src="icn/sticky-note--pencil.png" title="Edit Term" alt="Edit Term" />
                </span>';
            }
            $mustredo = trim($wordlc) == mb_strtolower(trim($vals[1]), 'UTF-8');
            if ($mustredo) {
                $rr .= "$('#editlink" . $i . "').html(" . prepare_textdata_js($plus) . ");"; 
            }
            $r .= $plus;
            $r .= '</span></td><td class="td1" style="font-size:90%;"><span id="transsel' . $i . '">';
            $plus = make_trans($i, $wid, $trans, $vals[1], $langid);
            if ($mustredo) { 
                $rr .= "$('#transsel" . $i . "').html(" . prepare_textdata_js($plus) . ");"; 
            }
            $r .= $plus;
            $r .= '</span></td></tr>';
        } else {
            if (trim($vals[1]) != '') {
                $nontermbuffer .= str_replace("¶", '<img src="icn/new_line.png" title="New Line" alt="New Line" />', tohtml($vals[1])); 
            }
        }
    }
    if ($nontermbuffer != '') {
        $r .= '<tr>
            <td class="td1 center" style="font-size:' . $textsize . '%;">' . 
            $nontermbuffer . 
            '</td>
            <td class="td1 right" colspan="3">
                <img class="click" src="icn/tick.png" title="Back to \'Display/Print Mode\'" alt="Back to \'Display/Print Mode\'" onclick="location.href=\'print_impr_text.php?text=' . $textid . '\';" />
            </td>
        </tr>';
    }
    $r .= '
                <th class="th1 center">Text</th>
                <th class="th1 center">Dict.</th>
                <th class="th1 center">Edit<br />Term</th>
                <th class="th1 center">
                    Term Translations (Delim.: ' . tohtml(getSettingWithDefault('set-term-translation-delimiters')) . ')
                    <br />
                    <input type="button" value="Reload" onclick="do_ajax_edit_impr_text(1e6,\'\');" />
                    <a name="bottom"></a>
                </th>
            </tr>
        </table>
    </form>\n';
    /*
    $r .= '<script type="text/javascript">' . "\n";
    $r .= '//<![CDATA[' . "\n";
    $r .= '$(document).ready( function() {' . "\n";
    $r .= "$('input.impr-ann-text').change(changeImprAnnText);\n";
    $r .= "$('input.impr-ann-radio').change(changeImprAnnRadio);\n";
    $r .= '} );' . "\n";
    $r .= '//]]>' . "\n";
    $r .= '</script>' . "\n";
    */
    return array($r, $rr);
}

/**
 * Do the AJAX modification for editing a printed text.
 * 
 * @param int    $textid Text ID
 * @param string $wordlc Word lowercase. Can be left empty.
 * 
 * @return void 
 */
function do_ajax_edit_impr_text($textid, $wordlc) 
{
    chdir('..');

    list($r, $rr) = make_form($textid, $wordlc);
    if ($wordlc == '') {
        echo "$('#editimprtextdata').html(" . prepare_textdata_js($r) . ");"; 
    } else {
        echo $rr; 
    }
}

if (isset($_POST["id"]) && isset($_POST['word'])) {
    do_ajax_edit_impr_text((int)$_POST["id"], $_POST['word']); 
}

?>
