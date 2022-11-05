<?php

/**
 * \file
 * \brief Change status of term while reading
 * 
 * Call: set_word_status.php?...
 *      ... tid=[textid]&wid=[wordid]&status=1..5/98/99
 * 
 * @package Lwt
 * @author  LWT Project <lwt-project@hotmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @since   1.0.3
 */

require_once 'inc/session_utility.php';

/**
 * Get various data for the word corresponding to the ID.
 * 
 * @param string $wid ID of the word
 * 
 * @return array{0: string, 1: string, 2: string} The word in plain text, 
 * his translation and his romanization
 * 
 * @global string $tbpref 
 */
function get_word_data($wid)
{
    global $tbpref;
    $sql = 'SELECT WoText, WoTranslation, WoRomanization 
    FROM words WHERE WoID = ' . $wid;
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    if (!$record) {
        my_die("Word not found in set_word_status.php"); 
    }
    $word = $record['WoText'];
    $trans = repl_tab_nl($record['WoTranslation']) . getWordTagList($wid, ' ', 1, 0);
    $roman = $record['WoRomanization'];
    mysqli_free_result($res);
    return array($word, $trans, $roman);
}

/**
 * Edit the word from the database.
 * 
 * @param string $wid    ID of the word to delete
 * @param string $status New status to set
 * 
 * @return string Some edit message, number of affected rows or error message
 * 
 * @global string $tbpref 
 */
function set_word_status_database($wid, $status)
{
    global $tbpref;
    $m1 = runsql(
        'UPDATE words 
        SET WoStatus = ' . $status . ', WoStatusChanged = NOW(),' . make_score_random_insert_update('u') . ' 
        WHERE WoID = ' . $wid, 
        'Status changed'
    );
    return $m1;
}

/**
 * Do the JavaScript action for changing display of the word.
 * 
 * @param string $tid    Text ID
 * @param string $wid    ID of the word that changed status
 * @param string $status New status
 * @param string $word   Word in plain text
 * @param string $trans  Translation of the word
 * @param string $roman  Romanization of the word
 * 
 * @return void 
 */
function set_word_status_javascript($tid, $wid, $status, $word, $trans, $roman)
{
    ?>
<script type="text/javascript">
    //<![CDATA[
    let context = window.parent.document.getElementById('frame-l');
    let contexth = window.parent.document.getElementById('frame-h');
    let status = '<?php echo $status; ?>';
    let title = '';
    if (!window.parent.JQ_TOOLTIP) {
        title = make_tooltip(
            <?php echo prepare_textdata_js($word); ?>, <?php echo prepare_textdata_js($trans); ?>, <?php echo prepare_textdata_js($roman); ?>, status
        );
    }
    $('.word<?php echo $wid; ?>', context)
    .removeClass('status98 status99 status1 status2 status3 status4 status5')
    .addClass('status<?php echo $status; ?>')
    .attr('data_status','<?php echo $status; ?>')
    .attr('title',title);
    $('#learnstatus', contexth).html('<?php echo addslashes(texttodocount2($tid)); ?>');
    window.parent.document.getElementById('frame-l').focus();
    window.parent.setTimeout('cClick()', 100);
    //]]>
</script>
    <?php
}

/**
 * Echo the HTML content of the page.
 * 
 * @param string $tid    Text ID
 * @param string $wid    ID of the word that changed status
 * @param string $status New status
 * @param string $word   Word in plain text
 * @param string $trans  Translation of the word
 * @param string $roman  Romanization of the word
 * 
 * @return void 
 */
function set_word_status_display_page($tid, $wid, $status, $word, $trans, $roman)
{
    pagestart("Term: " . $word, false);
    echo '<p>OK, this term has status ' . get_colored_status_msg($status) . ' from now!</p>';
    set_word_status_javascript($tid, $wid, $status, $word, $trans, $roman);
    pageend();

}


/**
 * Complete workflow for updating a word.
 * It edits the database, show the success message
 * and do JavaScript action to change its display.
 * 
 * @param string $textid ID of the affected text
 * @param string $wordid ID of the word to update
 * @param string $status New status for this word
 * 
 * @return void
 * 
 * @since 2.0.4-fork
 */
function do_set_word_status($textid, $wordid, $status)
{
    list($word, $trans, $roman) = get_word_data($wordid);
    set_word_status_database($wordid, $status);
    set_word_status_display_page($textid, $wordid, $status, $word, $trans, $roman);
}


if (getreq('tid') != '' && getreq('wid') != '' && getreq('status') != '') {
    do_set_word_status(getreq('tid'), getreq('wid'), getreq('status'));
}

?>
