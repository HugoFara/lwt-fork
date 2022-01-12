<?php

/**
 * \file
 * \brief Change status of term while testing
 * 
 * Call: set_test_status.php?wid=[wordid]&stchange=+1/-1
 *       set_test_status.php?wid=[wordid]&status=1..5/98/99
 * 
 * @package Lwt
 * @author  LWT Project <lwt-project@hotmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/html/do__test__header_8php.html
 * @since   1.0.3
 */

require_once 'inc/session_utility.php';

$status = (int)getreq('status');
$wid = (int)getreq('wid');

$oldstatus = (int)get_first_value(
    "SELECT WoStatus AS value FROM " . $tbpref . "words 
    WHERE WoID = " . $wid
);

$oldscore = (int)get_first_value(
    'SELECT greatest(0,round(WoTodayScore,0)) AS value FROM ' . $tbpref . 'words 
    WHERE WoID = ' . $wid
);

if (getreq('stchange') != '') {
    $stchange = $status - $oldstatus;
    if ($stchange <= 0) { 
        $stchange=-1; 
    }
    if ($stchange > 0) { 
        $stchange=1; 
    }
    
} else {
    $stchange = (int)getreq('stchange');
    $status = $oldstatus + $stchange;
    if ($status < 1) { 
        $status=1; 
    }
    if ($status > 5) { 
        $status=5; 
    }
    
}

$word = get_first_value(
    "SELECT WoText AS value FROM " . $tbpref . "words 
    WHERE WoID = " . $wid
);
pagestart("Term: " . $word, false);

runsql(
    'UPDATE ' . $tbpref . 'words SET WoStatus = ' . 
    $status . ', WoStatusChanged = NOW(),' . make_score_random_insert_update('u') . ' 
    WHERE WoID = ' . $wid, 
    'Status changed'
);
    
$newscore = (int)get_first_value(
    'SELECT greatest(0,round(WoTodayScore,0)) AS value 
    FROM ' . $tbpref . 'words where WoID = ' . $wid
);

function do_set_test_status_html($status, $oldstatus, $newscore, $oldscore) 
{
    if ($oldstatus == $status) {
        echo '<p>Status ' . get_colored_status_msg($status) . ' not changed.</p>'; 
    } else {
        echo '<p>Status changed from ' . get_colored_status_msg($oldstatus) . ' to ' . get_colored_status_msg($status) . '.</p>'; 
    }

    echo "<p>Old score was " . $oldscore . ", new score is now " . $newscore . ".</p>";
}

function set_test_status_change_progress($stchange)
{
    $totaltests = $_SESSION['testtotal'];
    $wrong = $_SESSION['testwrong'];
    $correct = $_SESSION['testcorrect'];
    $notyettested = $totaltests - $correct - $wrong;
    if ($notyettested > 0 ) {
        if ($stchange >= 0 ) { 
            $_SESSION['testcorrect']++; 
        }
        else {
            $_SESSION['testwrong']++; 
        }
    }
}        

function do_set_test_status_javascript($wid, $status, $stchange)
{
?>
<script type="text/javascript">
    //<![CDATA[
    const context = window.parent;
    $('.word<?php echo $wid; ?>', context)
    .removeClass('todo todosty')
    .addClass('done<?php echo ($stchange >= 0 ? 'ok' : 'wrong'); ?>sty')
    .attr('data_status','<?php echo $status; ?>')
    .attr('data_todo','0');
    // Waittime <= 0 causes the page to loop-reloading
    const waittime = <?php 
    echo json_encode((int)getSettingWithDefault('set-test-main-frame-waiting-time')); 
    ?> + 500;
    if (waittime <= 0) {
        console.log("aaa");
        window.parent.location.reload();
    } else {
        console.log("bbb");
        setTimeout('window.parent.location.reload();', waittime);
    }
    //]]>
</script>
<?php
}

do_set_test_status_html($status, $oldstatus, $newscore, $oldscore);
set_test_status_change_progress($stchange);
do_set_test_status_javascript($wid, $status, $stchange);
pageend();

?>