<?php

/**
 * \file
 * \brief Start Reading a text (frameset)
 * 
 * Call: do_text.php?text=[textid]
 *      Create the main window when reading texts.
 * 
 * @package Lwt
 * @author  LWT Project <lwt-project@hotmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/html/do__text_8php.html
 * @since   1.0.3
 */

require_once 'inc/session_utility.php'; 
require_once 'inc/mobile_interactions.php';
require_once 'do_text_header.php';
require_once 'do_text_text.php';

/**
 * Get text ID (if possible).
 * 
 * Text ID if first looked at int the 'text' parameter. If not found, then look at 'start'.
 * 
 * @return int|null Text ID or null
 */
function get_text_id()
{
    if (isset($_REQUEST['text']) && is_numeric($_REQUEST['text'])) {
        return (int)$_REQUEST['text'];
    }
    if (isset($_REQUEST['start']) && is_numeric($_REQUEST['start'])) {
        return (int)$_REQUEST['start'];
    }
    return null;
}

/**
 * Echo the page content for the mobile version of do_text.
 * 
 * @param int    $textid Text ID
 * @param string $audio  Audio URI
 * 
 * @return void
 * 
 * @deprecated Use do_text_desktop_content instead.
 * 
 * @since 2.2.1 It also calls do_frameset_mobile_css and do_frameset_mobile_js
 */
function do_text_mobile_content($textid, $audio)
{
    do_frameset_mobile_css();
    do_frameset_mobile_js($audio);
    do_frameset_mobile_page_content(
        "do_text_header.php?text=" . $textid, 
        "do_text_text.php?text=" . $textid, 
        true
    );
}

/**
 * Echo the page content for the desktop version of do_text.
 * 
 * @param int         $textid Text ID
 * @param string|null $audio  Audio URI
 * 
 * @return void
 */
function do_text_desktop_content($textid, $audio)
{
    ?>
<div style="width: 95%; height: 100%;">
    <div id="frame-h">
        <?php do_text_header_content($textid, true); ?>
    </div>
    <hr />
    <div id="frame-l">
        <?php do_text_text_content($textid, true); ?>
    </div>
</div>
<div id="frames-r" style="position: fixed; top: 0; right: -100%; width: 100%; height: 100%;" 
onclick="hideRightFrames();">
    <!-- iFrames wrapper for events -->
    <div style="margin-left: 50%; height: 99%;">
        <iframe src="empty.html" scrolling="auto" name="ro" style="height: 50%; width: 100%;">
            Your browser doesn't support iFrames, update it!
        </iframe>
        <iframe src="empty.html" scrolling="auto" name="ru" style="height: 50%; width: 100%;">
            Your browser doesn't support iFrames, update it!
        </iframe>
    </div>
</div>

    <?php
}

/**
 * Echo the text page.
 * 
 * @param int  $textit Text ID
 * @param bool $mobile Set to true if you want the mobile version of the page.
 * 
 * @since 2.2.1 The $mobile parameter is no longer required.
 * 
 * @return void
 * 
 *
 */
function do_text_page($textid)
{


    //framesetheader('Read');
    pagestart_nobody('Read');
    
    $audio = get_first_value(
        'SELECT TxAudioURI AS value 
        FROM texts 
        WHERE TxID = ' . $textid
    );
    
    if (is_mobile()) {
        do_text_mobile_content($textid, $audio);
    } else {
        // Not mobile
        do_text_desktop_content($textid, $audio);
    }
    pageend();
}

if (get_text_id() !== null) {
    do_text_page(get_text_id());
} else {
    // Document not ready
    header("Location: edit_texts.php");
    exit();
}

?>