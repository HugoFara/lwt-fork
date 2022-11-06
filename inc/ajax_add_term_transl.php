<?php
/**
 * \file
 * \brief Add a translation to term.
 * 
 * Call: inc/ajax_add_term_transl.php
 * 
 * @package Lwt
 * @author  LWT Project <lwt-project@hotmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/html/ajax__add__term__transl_8php.html
 * @since   1.5.0
 */

require_once __DIR__ . '/session_utility.php';

/**
 * Add the translation for a new term.
 * 
 * @param string $text Associated text
 * @param int    $lang Language ID
 * @param string $data Translation
 * 
 * @return string Error message if failure, lowercase $text otherwise
 * 
 *
 */
function add_new_term_transl($text, $lang, $data) 
{

    $textlc = mb_strtolower($text, 'UTF-8');
    $dummy = runsql(
        'INSERT INTO words (
            WoLgID, WoTextLC, WoText, WoStatus, WoTranslation, 
            WoSentence, WoRomanization, WoStatusChanged, 
            ' .  make_score_random_insert_update('iv') . '
        ) VALUES( ' . 
        $lang . ', ' .
        convert_string_to_sqlsyntax($textlc) . ', ' .
        convert_string_to_sqlsyntax($text) . ', 1, ' .        
        convert_string_to_sqlsyntax($data) . ', ' .
        convert_string_to_sqlsyntax('') . ', ' .
        convert_string_to_sqlsyntax('') . ', NOW(), ' .  
        make_score_random_insert_update('id') . ')', ""
    );
    if (!is_numeric($dummy) || (int)$dummy != 1) {
        return "";
    }
    $wid = get_last_key();
    do_mysqli_query(
        'UPDATE textitems2 
        SET Ti2WoID = ' . $wid . ' 
        WHERE Ti2LgID = ' . $lang . ' AND LOWER(Ti2Text) =' . convert_string_to_sqlsyntax_notrim_nonull($textlc)
    );
    return $textlc;
}

/**
 * Edit the translation for an existing term.
 * 
 * @param int    $wid       Word ID
 * @param string $new_trans New translation
 * 
 * @return string WoTextLC, lower version of the word
 * 
 *
 */
function edit_term_transl($wid, $new_trans)
{

    $oldtrans = get_first_value(
        "SELECT WoTranslation AS value 
        FROM words 
        WHERE WoID = " . $wid
    );
    
    $oldtransarr = preg_split('/[' . get_sepas()  . ']/u', $oldtrans);
    array_walk($oldtransarr, 'trim_value');
    
    if (!in_array($new_trans, $oldtransarr)) {
        if (trim($oldtrans) == '' || trim($oldtrans) == '*') {
            $oldtrans = $new_trans;
        } else {
            $oldtrans .= ' ' . get_first_sepa() . ' ' . $new_trans;
        }
        runsql(
            'UPDATE words 
            SET WoTranslation = ' . convert_string_to_sqlsyntax($oldtrans) . ' 
            WHERE WoID = ' . $wid, ""
        );
    }
    return get_first_value(
        "SELECT WoTextLC AS value 
        FROM words 
        WHERE WoID = " . $wid
    );
}

/**
 * Add or edit a term translation.
 * 
 * @param int    $wid  Word ID
 * @param string $data Translation 
 * 
 * @return string Database alteration message
 * 
 *
 */
function do_ajax_add_term_transl($wid, $data)
{

    chdir('..');
    /// Save data
    $success = "";
    if ($wid == 0) {
        /**
         * @var string $text Text, only for only wid=0 (new)
         */
        $text = trim($_POST['text']);
        /**
         * @var int $lang Language ID only wid=0 (lang-id)
         */
        $lang = (int)$_POST['lang'];
        $success = add_new_term_transl($text, $lang, $data);
    } else {
        $cnt_words = (int)get_first_value(
            "SELECT COUNT(WoID) AS value 
            FROM words 
            WHERE WoID = " . $wid
        );
        if ($cnt_words == 1) {
            $success = edit_term_transl($wid, $data);
        }
    }
    return $success;
}

if (isset($_POST['id']) && isset($_POST['data'])) {
    echo do_ajax_add_term_transl((int)$_POST['id'], trim($_POST['data']));
}

?>
