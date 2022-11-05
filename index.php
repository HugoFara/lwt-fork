<?php

/**
 * \file
 * \brief LWT Start Screen / Main Menu / Home
 * 
 * Call: index.php
 * 
 * @package Lwt
 * @author  LWT Project <lwt-project@hotmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/html/index_8php.html
 * @since   1.0.3
 * 
 * "Learning with Texts" (LWT) is free and unencumbered software 
 * released into the PUBLIC DOMAIN.
 * 
 * Anyone is free to copy, modify, publish, use, compile, sell, or
 * distribute this software, either in source code form or as a
 * compiled binary, for any purpose, commercial or non-commercial,
 * and by any means.
 * 
 * In jurisdictions that recognize copyright laws, the author or
 * authors of this software dedicate any and all copyright
 * interest in the software to the public domain. We make this
 * dedication for the benefit of the public at large and to the 
 * detriment of our heirs and successors. We intend this 
 * dedication to be an overt act of relinquishment in perpetuity
 * of all present and future rights to this software under
 * copyright law.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE 
 * WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE
 * AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS BE LIABLE 
 * FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN 
 * CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN 
 * THE SOFTWARE.
 * 
 * For more information, please refer to [http://unlicense.org/].
 */

 /**
  * Echo an error page if connect.inc.php was not found.
  * 
  * @return void
  */
function no_connectinc_error_page() 
{
    ?>
    <html>
        <body>
            <div style="padding: 1em; color:red; font-size:120%; background-color:#CEECF5;">
                <p>
                    <b>Fatal Error:</b> 
                    Cannot find file: "connect.inc.php". Please rename the correct file "connect_[servertype].inc.php" to "connect.inc.php"
                    ([servertype] is the name of your server: xampp, mamp, or easyphp). 
                    Please read the documentation: https://learning-with-texts.sourceforge.io
                </p>
            </div>
        </body>
    </html>
    <?php
    die('');
}

if (!file_exists('connect.inc.php')) {
    no_connectinc_error_page();
}

require_once 'inc/session_utility.php';

/**
 * Prepare the different SPAN opening tags
 * 
 * @return string[] 3 different span levels 
 * 
 * @global string $tbpref       Database table prefix
 * @global string $fixed_tbpref Fixed database table prefix
 */
function get_span_groups()
{
    global $tbpref, $fixed_tbpref;

    if ($tbpref == '') {
        $span2 = "<i>Default</i> Table Set</span>";
    } else {
        $span2 = "Table Set: <i>" . tohtml(substr($tbpref, 0, -1)) . "</i></span>";
    }

    if ($fixed_tbpref) {
        $span1 = '<span>';
        $span3 = '<span>';
    } else {
        $span1 = '<span title="Manage Table Sets" onclick="location.href=\'table_set_management.php\';" class="click">';
        if (count(getprefixes()) > 0) {
            $span3 = '<span title="Select Table Set" onclick="location.href=\'start.php\';" class="click">'; 
        } else {
            $span3 = '<span>'; 
        }    
    }
    return array($span1, $span2, $span3);
}

/**
 * Display the current text options.
 * 
 * @return void
 * 
 * @global string $tbpref Database table prefix
 */
function do_current_text_info($textid)
{
    global $tbpref;
    $txttit = get_first_value(
        'SELECT TxTitle AS value 
        FROM texts 
        WHERE TxID=' . $textid
    );
    if (!isset($txttit)) {
        return;
    } 
    $txtlng = get_first_value(
        'SELECT TxLgID AS value FROM texts WHERE TxID=' . $textid
    );
    $lngname = getLanguage($txtlng);
    $annotated = (int)get_first_value(
        "SELECT LENGTH(TxAnnotatedText) AS value 
        FROM texts 
        WHERE TxID = " . $textid
    ) > 0;
    ?>
 
 <div style="height: 85px;">
    Last Text (<?php echo tohtml($lngname); ?>):<br /> 
    <i><?php echo tohtml($txttit); ?></i>
    <br />
    <a href="do_text.php?start=<?php echo $textid; ?>">
        <img src="icn/book-open-bookmark.png" title="Read" alt="Read" />&nbsp;Read
    </a>
    &nbsp; &nbsp; 
    <a href="do_test.php?text=<?php echo $textid; ?>">
        <img src="icn/question-balloon.png" title="Test" alt="Test" />&nbsp;Test
    </a>
    &nbsp; &nbsp; 
    <a href="print_text.php?text=<?php echo $textid; ?>">
        <img src="icn/printer.png" title="Print" alt="Print" />&nbsp;Print
    </a>
    <?php
    if ($annotated) {
        ?>
    &nbsp; &nbsp; 
    <a href="print_impr_text.php?text=<?php echo $textid; ?>">
        <img src="icn/tick.png" title="Improved Annotated Text" alt="Improved Annotated Text" />&nbsp;Ann. Text
    </a>
        <?php
    }
    ?>
 </div>
    <?php
}

/**
 * Echo a select element to switch between languages.
 * 
 * @return void
 */
function do_language_selectable($langid)
{
    ?>
<div for="filterlang">Language: 
    <select id="filterlang" onchange="{setLang(document.getElementById('filterlang'),'index.php');}">
        <?php echo get_languages_selectoptions($langid, '[Select...]'); ?>
    </select>
</div>   
    <?php
}

/**
 * When on a WordPress server, make a logout button
 * 
 * @return void 
 */
function wordpress_logout_link()
{
    // ********* WORDPRESS LOGOUT *********
    if (isset($_SESSION['LWT-WP-User'])) {
        ?>

<div class="menu">
    <a href="wp_lwt_stop.php">
        <span style="font-size:115%; font-weight:bold; color:red;">LOGOUT</span> (from WordPress and LWT)
    </a>
</div>
        <?php
    }
}

/**
 * Return a lot of different server state variables.
 * 
 * @return string[]
 * 
 * @global string $tbpref Database table prefix
 * @global string $dbname Database name
 */
function get_server_data() 
{
    global $tbpref, $dbname;
    $p = convert_string_to_sqlsyntax_nonull($tbpref);
    $mb = get_first_value(
        "SELECT round(sum(data_length+index_length)/1024/1024,1) AS value 
        FROM information_schema.TABLES 
        WHERE table_schema = " . convert_string_to_sqlsyntax($dbname) . " 
        AND table_name IN (" .
            "CONCAT(" . $p . ",'archivedtexts')," .
            "CONCAT(" . $p . ",'archtexttags')," .
            "CONCAT(" . $p . ",'feedlinks')," .
            "CONCAT(" . $p . ",'languages')," .
            "CONCAT(" . $p . ",'newsfeeds')," .
            "CONCAT(" . $p . ",'sentences')," .
            "CONCAT(" . $p . ",'settings')," .
            "CONCAT(" . $p . ",'tags')," .
            "CONCAT(" . $p . ",'tags2')," .
            "CONCAT(" . $p . ",'textitems2')," .
            "CONCAT(" . $p . ",'texts')," .
            "CONCAT(" . $p . ",'texttags')," .
            "CONCAT(" . $p . ",'words')," .
            "CONCAT(" . $p . ",'wordtags')
        )"
    );
    if (!isset($mb)) { 
        $mb = '0.0'; 
    }

    $serversoft = explode(' ', $_SERVER['SERVER_SOFTWARE']);
    $apache = "Apache/?";
    // if (count($serversoft) >= 1) { Not supposed to happen
    if (substr($serversoft[0], 0, 7) == "Apache/") { 
        $apache = $serversoft[0]; 
    }
    // }
    $php = phpversion();
    $mysql = get_first_value("SELECT VERSION() as value");
    return array($p, $mb, $serversoft, $apache, $php, $mysql);
}


list($span1, $span2, $span3) = get_span_groups();

$currentlang = null;
if (is_numeric(getSetting('currentlanguage'))) {
    $currentlang = (int) getSetting('currentlanguage');
}

$currenttext = null;
if (is_numeric(getSetting('currenttext'))) {
    $currenttext = (int) getSetting('currenttext');
}

$langcnt = (int) get_first_value('SELECT COUNT(*) AS value FROM languages');

list($p, $mb, $serversoft, $apache, $php, $mysql) = get_server_data();

pagestart_nobody(
    "Home", 
    "
    .menu {
        display: flex; 
        flex-direction: column; 
        flex-wrap: wrap;
        margin-bottom: 20px;
    }

    .menu > * {
        width: 400px;
        height: 30px;
        margin: 5px;
        text-align: center;
        background-color: #8883;
        padding-top: 15px;
    }"
);
echo '<div>' . 
    echo_lwt_logo() . '<h1>' . 
        $span3 . 'Learning With Texts (LWT)</span>
    </h1>
    <h2>Home' . ($debug ? ' <span class="red">DEBUG</span>' : '') . '</h2>
</div>';

?>
<script type="text/javascript">
    //<![CDATA[
    if (!areCookiesEnabled()) 
        document.write('<p class="red">*** Cookies are not enabled! Please enable them! ***</p>');
    //]]>
</script>

<p>Welcome to your language learning app!</p> 

<div style="display: flex; justify-content: space-evenly; flex-wrap: wrap;">
    <div class="menu">
        <?php
        if ($langcnt == 0) {
            ?> 
        <div><p>Hint: The database seems to be empty.</p></div>
        <a href="install_demo.php">Install the LWT demo database, </a>
        <a href="edit_languages.php?new=1">Define the first language you want to learn.</a>
            <?php
        } else if ($langcnt > 0) {
            do_language_selectable($currentlang);
            if ($currenttext !== null) {
                do_current_text_info($currenttext);
            }
        } 
        ?>
            <a href="edit_languages.php">Languages</a>
    </div>

    <div class="menu">
        <a href="edit_texts.php">Texts</a>
        <a href="edit_archivedtexts.php">Text Archive</a>
        
        <a href="edit_texttags.php">Text Tags</a>
        <a href="check_text.php">Check a Text</a>
        <a href="long_text_import.php">Long Text Import</a>
    </div>
    
    <div class="menu">
        <a href="edit_words.php">Terms (Words and Expressions)</a>
        <a href="edit_tags.php">Term Tags</a>
        <a href="upload_words.php">Import Terms</a>
    </div>
    
    <div class="menu">
        <a href="do_feeds.php?check_autoupdate=1">Newsfeed Import</a>
        <a href="backup_restore.php">Backup / Restore / Empty Database</a>
    </div>

    <div class="menu">
        <a href="statistics.php">Statistics</a>
        <a href="docs/info.php">Help / Information</a>
    </div>

    <div class="menu">
        <a href="settings.php">Settings / Preferences</a>
        <a href="text_to_speech_settings.php">Text-to-Speech Settings</a>
        <a href="mobile.php">Mobile LWT (Deprecated)</a>
    </div>
        
    <?php wordpress_logout_link(); ?>

    <table style="width: 500px; margin: 5px;">
        <tbody>
            <tr>
                <td><a href="https://en.wikipedia.org/wiki/Database" target="_blank">Database</a> name</td>
                <td><i><?php echo $dbname; ?></i></td>
            </tr>
            <tr>
                <td>Database Location</td>
                <td><i><?php echo $server; ?></i></td>
            </tr>
            <tr>
                <td>Database Size</td>
                <td><?php echo $mb; ?> MB</td>
            </tr>
            <tr>
                <td><a href="https://en.wikipedia.org/wiki/Web_server" target="_blank">Web Server</a></td>
                <td><i><?php echo $_SERVER['HTTP_HOST']; ?></i></td>
            </tr>
            <tr>
                <td>Server Software</td>
                <td><a href="https://en.wikipedia.org/wiki/Apache_HTTP_Server" target="_blank"><?php echo $apache; ?></a></td>
            </tr>
            <tr>
                <td><a href="https://en.wikipedia.org/wiki/PHP" target="_blank">PHP</a> Version</td>
                <td><?php echo $php; ?></td>
            </tr>
            <tr>
                <td><a href="https://en.wikipedia.org/wiki/MySQL" target="_blank">MySQL</a> Version</td>
                <td><?php echo $mysql; ?></td>
            </tr>
        </tbody>
    </table>

</div>
<p>This is LWT Version <?php echo get_version(); ?></p>
<hr />
<footer>
    <table>
        <tr>
            <td class="width50px">
                <a target="_blank" href="http://unlicense.org/">
                    <img alt="Public Domain" title="Public Domain" src="img/public_domain.png" />
                </a>
            </td>
            <td>
                <p class="small">
                    <a href="https://sourceforge.net/projects/learning-with-texts/" target="_blank">"Learning with Texts" (LWT)</a> is free 
                    and unencumbered software released into the 
                    <a href="https://en.wikipedia.org/wiki/Public_domain_software" target="_blank">PUBLIC DOMAIN</a>. 
                    <a href="http://unlicense.org/" target="_blank">More information and detailed Unlicense ...</a>
                </p>
            </td>
        </tr>
    </table>
</footer>
<?php

pageend();

?>
