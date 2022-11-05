<?php

/**
 * \file
 * \brief Responsible for drawing the header when reading texts
 * 
 * Call: do_text_header.php?text=[textid]
 * 
 * @package Lwt
 * @author  LWT Project <lwt-project@hotmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/html/do__text__header_8php.html
 * @since   1.0.3
 */

require_once 'inc/session_utility.php';
// To get the BCP 47 language tag
require_once 'inc/langdefs.php' ;

/**
 * Get the text and language data associated with the text.
 *
 * @param string $textid ID of the text
 *
 * @global string $tbpref Table name prefix
 *
 * @since 2.0.3-fork
 *
 * @return null|string[]
 *
 * @psalm-return array<string, string>|null
 */
function getData($textid): ?array
{
    global $tbpref;
    $sql = 
    'SELECT LgName, TxLgID, TxText, TxTitle, TxAudioURI, TxSourceURI, TxAudioPosition 
    FROM texts 
    JOIN languages ON TxLgID = LgID 
    WHERE TxID = ' . $textid;
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    mysqli_free_result($res);
    return $record;
}

/**
 * Print the main title row.
 *
 * @param int    $textid Text ID
 * @param string $langid Language ID to navigate between 
 *                       texts of same language
 *
 * @since 2.0.4-fork
 */
function do_header_row($textid, $langid): void
{
    ?>
<h4>
    <a href="edit_texts.php" target="_top">
        <?php echo_lwt_logo(); ?> LWT
    </a>
    &nbsp; | &nbsp;
    <?php 
    quickMenu();
    echo getPreviousAndNextTextLinks(
        $textid, 'do_text.php?start=', false, '&nbsp; | &nbsp;'
    );
    ?>&nbsp; | &nbsp;
    <a href="do_test.php?text=<?php echo $textid; ?>" target="_top">
        <img src="icn/question-balloon.png" title="Test" alt="Test" />
    </a>&nbsp;
    <a href="print_text.php?text=<?php echo $textid; ?>" target="_top">
        <img src="icn/printer.png" title="Print" alt="Print" />
    </a>
    <?php echo get_annotation_link($textid); ?>&nbsp;
    <a target="_top" href="edit_texts.php?chg=<?php echo $textid; ?>">
        <img src="icn/document--pencil.png" title="Edit Text" alt="Edit Text" />
    </a>&nbsp; | &nbsp;
    <a 
        href="new_word.php?text=<?php echo $textid; ?>&amp;lang=<?php echo $langid; ?>" 
        target="ro" onclick="showRightFrames();"
    >
        <img src="icn/sticky-note--plus.png" title="New Term" alt="New Term" />
    </a>
</h4>
    <?php
}

/**
 * Print the title of the text.
 *
 * @param string $title     Title of the text
 * @param string $sourceURI URL of the text (if any)
 *
 * @since 2.0.4-fork
 */
function do_title($title, $sourceURI): void
{
    ?>
<table>
    <tr>
        <td>
            <h3>READ&nbsp;▶</h3>
        </td>
        <td class="width99pc">
            <h3>
                <?php 
                echo tohtml($title);
                if (isset($sourceURI) && substr(trim($sourceURI), 0, 1) != '#') { 
                    ?>
                    <a href="<?php echo $sourceURI ?>" target="_blank">
                        <img src="<?php echo get_file_path('icn/chain.png') ?>" title="Text Source" alt="Text Source" />
                    </a>
                    <?php 
                } 
                ?>
            </h3>
        </td>
    </tr>
</table>
    <?php
}

/**
 * Prepare user settings for this text.
 *
 * @param string $textid Text ID
 *
 * @since 2.0.4-fork
 */
function do_settings($textid): void
{
    // User settings
    $showAll = getSettingZeroOrOne('showallwords', 1);
    $showLearning = getSettingZeroOrOne('showlearningtranslations', 1);

    ?>
<table class="width99pc">
    <tr>
        <td>TO DO:
            <span id="learnstatus"><?php echo texttodocount2($textid); ?></span>
        </td>
        <td 
        title="[Show All] = ON: ALL terms are shown, and all multi-word terms are shown as superscripts before the first word. The superscript indicates the number of words in the multi-word term.
[Show All] = OFF: Multi-word terms now hide single words and shorter or overlapping multi-word terms.">
            Show All&nbsp;
            <input type="checkbox" id="showallwords" <?php echo get_checked($showAll); ?> onclick="showAllwordsClick();" />
        </td>
        <td 
        title="[Learning Translations] = ON: Terms with Learning Level&nbsp;1 display their translations under the term.
[Learning Translations] = OFF: No translations are shown in the reading mode.">
            Learning Translations&nbsp;
            <input type="checkbox" id="showlearningtranslations" <?php echo get_checked($showLearning); ?> onclick="showAllwordsClick();" />
        </td>
        <td id="thetextid" class="hide"><?php echo $textid; ?></td>
        <td><button id="readTextButton">Read in browser</button></td>
    </tr>
</table>
    <?php
}

/**
 * Prints javascript data and function to read text
 * in your browser.
 *
 * @param string $text         Text to read
 * @param string $languageName Full name of the language (i. e.: "English")
 *
 * @global array $langDefs Definition of all languages. Normally $langDefs[$languageName][1] -> $languageCode
 *
 * @since 2.0.3-fork
 */
function browser_tts($text, $languageName): void
{
    global $langDefs;

    /** 
     * @var string $languageCode BCP 47 convention (i. e.: en-US) is suggested.
     * Two-letter language code is enough (i. e. "en") 
     */
    $languageCode = $langDefs[$languageName][1];
    /**
    * @var string $phoneticText Phonetic reading for this text 
    */
    $phoneticText = phonetic_reading($text, $languageCode);
    ?>
<script type="text/javascript">

    /// Main object for text-to-speech interaction with SpeechSynthesisUtterance
    var text_reader = {
        /// The text to read
        text: <?php echo json_encode($phoneticText); ?>,

        /// {string} ISO code for the language
        lang: <?php echo json_encode($languageCode); ?>,

        /// {string} Rate at wich the speech is done
        rate: 0.8,

        /**
         * Reads a text using the browser text reader.
         * 
         * @deprecated Since 2.3.0-fork, use of window.readTextAloud is recommended instead. 
         */
        readTextAloud: function () {
            var msg = new SpeechSynthesisUtterance(this.text);
            console.log('This function is deprecated, do not use it!')
            msg.text = this.text;
            msg.lang = this.lang;
            msg.rate = this.rate;
            window.speechSynthesis.cancel();
            window.speechSynthesis.speak(msg);
        },

    };

    /** Check browser compatibility before reading */
    function init_reading() {
        if (!('speechSynthesis' in window)) {
            alert('Your browser does not support speechSynthesis!');
        } else {
            readRawTextAloud(text_reader.text, text_reader.lang);
        }
    }

    /** 
     * Change the annotations display mode 
     * 
     * @param {string} mode The new annotation mode
     */
    function annotationModeChanged(mode) {
        console.log(mode);

    }
</script>
    <?php
}

/**
 * Save the position of the audio reading for a text.
 *
 * @param string $textid ID of the text
 *
 * @since 2.0.4-fork
 */
function save_audio_position($textid): void
{
    ?>

<script type="text/javascript">
    /**
     * Save audio position
     */
    function saveAudioPosition() {
        if ($("#jquery_jplayer_1") === null || $("#jquery_jplayer_1").length == 0) {
            return;
        }
        var pos = $("#jquery_jplayer_1").data("jPlayer").status.currentTime;
        $.ajax({
            type: "POST",
            url:'inc/ajax_save_text_position.php', 
            data: { 
                id: '<?php echo $textid; ?>', 
                audioposition: pos
            }, 
            async: false
        });
    }

    $(window).on('beforeunload', saveAudioPosition);

    // We need to capture the text-to-speach event manually for Chrome
    $(document).ready(function() {
        $('#readTextButton').on('click', init_reading)
    });
</script>
    <?php
}

/**
 * Main function for displaying header. It will print HTML content.
 *
 * @param string $textid    ID of the requiered text
 * @param bool   $only_body If true, only show the inner body. If false, create a 
 *                          complete HTML document.
 *
 * @since 2.0.3-fork
 */
function do_text_header_content($textid, $only_body=true): void
{
    $record = getData($textid);
    $title = $record['TxTitle'];
    $media = $record['TxAudioURI'];
    if (!isset($media)) { 
        $media = '';
    }
    $media = trim($media);
    
    
    saveSetting('currenttext', $textid);

    if (!$only_body) {
        pagestart_nobody($title, 'html, body {margin-bottom:0;}');
    }
    save_audio_position($textid);
    do_header_row((int) $textid, $record['TxLgID']);
    do_title($title, $record['TxSourceURI']);
    do_settings($textid);
    makeMediaPlayer($media, $record['TxAudioPosition']);
    browser_tts($record["TxText"], $record["LgName"]);
    if (!$only_body) {
        pageend();
    }
}

// Show the content automatically if text is in the request
if (false && getreq('text')) {
    do_text_header_content(getreq('text'), false);
}
?>