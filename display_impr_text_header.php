<?php
/**
 * \file
 * \brief Display an improved annotated text (top frame)
 * 
 * Call: display_impr_text_header.php?text=[textid]
 * 
 * @package Lwt
 * @author  LWT Project <lwt-project@hotmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/html/display__impr__text__header_8php.html
 * @since   1.5.0
 */

require_once 'inc/session_utility.php';

/**
 * Return the useful data for the header part of a printed text.
 * 
 * @param int $textid Text ID
 * 
 * @return array{0: string, 1: string, 2: string} Text title, 
 * text audio and source URI
 * 
 *
 */
function do_diplay_impr_text_header_data($textid)
{


    $sql = 
    'SELECT TxLgID, TxTitle, TxAudioURI, TxSourceURI 
    FROM texts
    WHERE TxID = ' . $textid;
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);


    if (isset($record['TxAudioURI'])) {
        $audio = trim($record['TxAudioURI']);
    } else {
        $audio = '';
    }

    $title = $record['TxTitle'];
    $sourceURI = $record['TxSourceURI'];
    mysqli_free_result($res); 

    saveSetting('currenttext', $textid);
    return array($title, $audio, $sourceURI);
}

/**
 * Echo JavaScript area containing behaviors to show/hide 
 * translations and annotations.
 * 
 * @return void
 */
function do_diplay_impr_text_header_js()
{

    ?>

<script type="text/javascript">
    //<![CDATA[

    /** Hide traslations. */
    function do_hide_t() {
        $('#showt').show(); 
        $('#hidet').hide();
        $('.anntermruby')
        .css('color','#E5E4E2').css('background-color', '#E5E4E2');
    }

    /** Show translations. */
    function do_show_t() {
        $('#showt').hide(); 
        $('#hidet').show();
        $('.anntermruby')
        .css('color','inherit').css('background-color', '');
    }

    /** Hide annotations. */
    function do_hide_a() {
        $('#show').show(); 
        $('#hide').hide(); 
        $('.anntransruby2')
        .css('color','#C8DCF0').css('background-color', '#C8DCF0');
    }

    /** Show annotations. */
    function do_show_a() {
        $('#show').hide(); 
        $('#hide').show();
        $('.anntransruby2')
        .css('color','').css('background-color', '');
    }
    //]]>
</script>

    <?php

}

/**
 * Make the header content to display a printed text.
 * 
 * @param string $title     Text title
 * @param int    $textid    Text ID
 * @param string $audio     Audio URI
 * @param string $sourceURI Text source link
 * 
 * @return void
 */
function do_diplay_impr_text_header_content($title, $textid, $audio, $sourceURI)
{
    ?>
<h2 class="center" style="margin:5px;margin-top:-10px;">
    <img id="hidet" style="margin-bottom:-5px;" class="click" src="icn/light-bulb-T.png" title="Toggle Text Display (Now ON)" alt="Toggle Text Display (Now ON)" onclick="do_hide_t();" />
    <img id="showt" style="display:none; margin-bottom:-5px;" class="click" src="icn/light-bulb-off-T.png" title="Toggle Text Display (Now OFF)" alt="Toggle Text Display (Now OFF)" onclick="do_show_t();" />
    <img id="hide" style="margin-bottom:-5px;" class="click" src="icn/light-bulb-A.png" title="Toggle Annotation Display (Now ON)" alt="Toggle Annotation Display (Now ON)" onclick="do_hide_a();" />
    <img id="show" style="display:none; margin-bottom:-5px;" class="click" src="icn/light-bulb-off-A.png" title="Toggle Annotation Display (Now OFF)" alt="Toggle Annotation Display (Now OFF)" onclick="do_show_a();" />
     &nbsp; &nbsp; 
     <?php
        echo tohtml($title);
        if (isset($sourceURI)) {
            echo ' <a href="' . $sourceURI . '" target="_blank">
            <img src="'.get_file_path('icn/chain.png').'" title="Text Source" alt="Text Source" />
            </a>';
        }
        echo getPreviousAndNextTextLinks($textid, 'display_impr_text.php?text=', true, ' &nbsp; &nbsp; ');
        ?>
     <img class="click" src="icn/cross.png" title="Close Window" alt="Close Window" onclick="top.close();" />
</h2>
    <?php
    makeMediaPlayer($audio);

    echo '</table>';

    //pageend();
}

/**
 * Main function to generate a complete header for a specific text.
 * 
 * @param int $textid Text ID.
 * 
 * @return void
 */
function do_diplay_impr_text_header_main($textid)
{
    do_diplay_impr_text_header_js();
    list($title, $audio, $sourceURI) = do_diplay_impr_text_header_data($textid);
    do_diplay_impr_text_header_content($title, $textid, $audio, $sourceURI);
}

?>
