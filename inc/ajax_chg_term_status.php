<?php
/**
 * \file
 * \brief Change term status (Table Test)
 * 
 * Call: inc/ajax_chg_term_status.php?id=[wordID]&data=[translation]
 * 
 * @package Lwt
 * @author  LWT Project <lwt-project@hotmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/html/ajax__chg__term__status_8php.html
 * @since   1.5.4
 */

require_once __DIR__ . '/session_utility.php';

/**
 * Check the consistency of the new status.
 * 
 * @param int  $oldstatus Old status
 * @param bool $up        True if status should incremented, false if decrementation needed
 * 
 * @return int<1, 5>|98|99 New status in the good number range.
 */
function get_new_status($oldstatus, $up)
{
    $currstatus = $oldstatus;
    if ($up) {
        $currstatus++; // 98,1,2,3,4,5 => 99,2,3,4,5,6
        if ($currstatus == 99) { 
            $currstatus = 1;  // 98->1
        } else if ($currstatus == 6) { 
            $currstatus = 99;  // 5->99 
        }    
    } else {
        $currstatus--; // 1,2,3,4,5,99 => 0,1,2,3,4,98
        if ($currstatus == 98) {
            $currstatus = 5;  // 99->5
        } else if ($currstatus == 0) {
            $currstatus = 98;  // 1->98
        }    
    }
    return $currstatus;
} 

/**
 * Save the new word status to the database.
 * 
 * @param int $wid        Word ID
 * @param int $currstatus Current status in the good value range. 
 * 
 * @return string|null HTML-formatted string with plus/minus controls if a success. 
 * 
 * @global string $tbpref Database table prefix
 */
function update_word_status($wid, $currstatus)
{
    global $tbpref;
    if (($currstatus >= 1 && $currstatus <= 5) || $currstatus == 99 || $currstatus == 98) {
        $m1 = (int)runsql(
            'UPDATE words 
            SET WoStatus = ' . $currstatus . ', WoStatusChanged = NOW(),' . make_score_random_insert_update('u') . '
            WHERE WoID = ' . $wid, ''
        );
        if ($m1 == 1) {
            $currstatus = get_first_value('SELECT WoStatus as value FROM words where WoID = ' . $wid);
            if (!isset($currstatus)) {
                return null;
            }
            return make_status_controls_test_table(1, $currstatus, $wid);
        }
    } else {
        return null;
    }
}

/**
 * Do a word status change and print the result.
 * 
 * @param int  $wid Word ID
 * @param bool $up  Should the status be incremeted or decremented
 * 
 * @return void
 * 
 * @global string $tbpref Database table prefix.
 */
function do_ajax_chg_term_status($wid, $up)
{
    global $tbpref;
    chdir('..');

    $tempstatus = get_first_value(
        'SELECT WoStatus as value 
        FROM words 
        WHERE WoID = ' . $wid
    );
    if (!isset($tempstatus)) {
        echo '';
        return;
    }
    $currstatus = get_new_status((int)$tempstatus, $up);
    echo update_word_status($wid, $currstatus);
}

if (getreq('id') != '' && getreq('data') != '') {
    do_ajax_chg_term_status((int)$_REQUEST['id'], (bool)$_REQUEST['data']);
}


?>