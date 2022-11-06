<?php

/**
 * \file
 * \brief Manage languages
 * 
 * Call: edit_languages.php?....
 *      ... refresh=[langid] ... reparse all texts in lang
 *      ... del=[langid] ... do delete
 *      ... op=Save ... do insert new 
 *      ... op=Change ... do update 
 *      ... new=1 ... display new lang. screen 
 *      ... chg=[langid] ... display edit screen 
 * 
 * @package Lwt
 * @author  LWT Project <lwt-project@hotmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/html/edit__languages_8php.html
 * @since   1.0.3
 * @since   2.4.0 Refactored with functional paradigm  
 */

require_once 'inc/session_utility.php';


/**
 * Prepare a JavaScript code that checks for duplicate names in languages.
 * 
 * @return void
 */
function edit_languages_alert_duplicate()
{
  
    ?>

<script type="text/javascript">
    //<![CDATA[
    var LANGUAGES = <?php echo json_encode(get_languages()); ?>;

    /// Check if langname exists and its lang# != curr
    function check_dupl_lang(curr) {
        const l = $('#LgName').val();
        if (l in LANGUAGES) {
            if (curr != LANGUAGES[l]) {
                alert('Language "' + l + 
                '" already exists. Please change the language name!');
                $('#LgName').focus();
                return false;
            }
        }
        return true;
    }

    //]]>
</script>

    <?php  
}


/**
 * Refresh sentence and text items from a specific language.
 * 
 * @param {int} $lid Language ID
 * 
 * @return {string} Number of sentences and textitems refreshed
 * 
 * @global {string} $tbpref Database table prefix
 */
function edit_languages_refresh($lid)
{

    $message2 = runsql(
        'delete from sentences where SeLgID = ' . $lid, 
        "Sentences deleted"
    );
    $message3 = runsql(
        'delete from textitems2 where Ti2LgID = ' . $lid, 
        "Text items deleted"
    );
    adjust_autoincr('sentences', 'SeID');
    $sql = "select TxID, TxText from texts 
    where TxLgID = " . $lid . " 
    order by TxID";
    $res = do_mysqli_query($sql);
    while ($record = mysqli_fetch_assoc($res)) {
        $txtid = (int)$record["TxID"];
        $txttxt = $record["TxText"];
        splitCheckText($txttxt, $lid, $txtid);
    }
    mysqli_free_result($res);
    $message = $message2 . 
    " / " . $message3 . 
    " / Sentences added: " . get_first_value(
        'select count(*) as value 
        from sentences 
        where SeLgID = ' . $lid
    ) . 
    " / Text items added: " . get_first_value(
        'select count(*) as value 
        from textitems2 
        where Ti2LgID = ' . $lid
    );
    return $message;
}



/**
 * Delete a language.
 * 
 * @param {int} $lid Language ID
 * 
 * @return {string} Info on the number of languages deleted
 * 
 * @global {string} $tbpref Database table prefix
 */
function edit_languages_delete($lid)
{

    $anztexts = get_first_value(
        'select count(TxID) as value 
        from texts 
        where TxLgID = ' . $lid
    );
    $anzarchtexts = get_first_value(
        'select count(AtID) as value 
        from archivedtexts 
        where AtLgID = ' . $lid
    );
    $anzwords = get_first_value(
        'select count(WoID) as value 
        from words 
        where WoLgID = ' . $lid
    );
    $anzfeeds = get_first_value(
        'select count(NfID) as value 
        from newsfeeds 
        where NfLgID = ' . $lid
    );
    if ($anztexts > 0 || $anzarchtexts > 0 || $anzwords > 0 || $anzfeeds > 0) {
        $message = 'You must first delete texts, archived texts, newsfeeds and words with this language!';
    } else {
        $message = runsql(
            'UPDATE languages 
            SET LgName = "", LgDict1URI = "", LgDict2URI = "", 
            LgGoogleTranslateURI = "", LgExportTemplate = "", LgTextSize = DEFAULT, 
            LgCharacterSubstitutions = "", LgRegexpSplitSentences = "", 
            LgExceptionsSplitSentences = "", LgRegexpWordCharacters = "", 
            LgRemoveSpaces = DEFAULT, LgSplitEachChar = DEFAULT, 
            LgRightToLeft = DEFAULT where LgID = ' . $lid, 
            "Deleted"
        );
    }
    return $message;
}


/**
 * Save a new language to the database.
 * 
 * @return {string} Success or error message
 * 
 * @global {string} $tbpref Database table prefix
 */
function edit_languages_op_save()
{

    $val = get_first_value(
        'select min(LgID) as value 
        from languages 
        where LgName=""'
    );
    if (! isset($val)) {
        $message = runsql(
            'insert into languages (
                LgName, LgDict1URI, LgDict2URI, LgGoogleTranslateURI, 
                LgExportTemplate, LgTextSize, LgCharacterSubstitutions, 
                LgRegexpSplitSentences, LgExceptionsSplitSentences, 
                LgRegexpWordCharacters, LgRemoveSpaces, LgSplitEachChar, 
                LgRightToLeft
            ) values(' . 
                convert_string_to_sqlsyntax($_REQUEST["LgName"]) . ', ' .
                convert_string_to_sqlsyntax($_REQUEST["LgDict1URI"]) . ', '. 
                convert_string_to_sqlsyntax($_REQUEST["LgDict2URI"]) . ', '.
                convert_string_to_sqlsyntax($_REQUEST["LgGoogleTranslateURI"]) . ', '.
                convert_string_to_sqlsyntax($_REQUEST["LgExportTemplate"]) . ', '.
                $_REQUEST["LgTextSize"] . ', '.
                convert_string_to_sqlsyntax_notrim_nonull($_REQUEST["LgCharacterSubstitutions"]) . ', '.
                convert_string_to_sqlsyntax($_REQUEST["LgRegexpSplitSentences"]) . ', '.
                convert_string_to_sqlsyntax_notrim_nonull($_REQUEST["LgExceptionsSplitSentences"]) . ', '.
                convert_string_to_sqlsyntax($_REQUEST["LgRegexpWordCharacters"]) . ', '.
                $_REQUEST["LgRemoveSpaces"] . ', '.
                $_REQUEST["LgSplitEachChar"] . ', '.
                $_REQUEST["LgRightToLeft"] . 
            ')', 
            'Saved'
        );
    }
    else {
        $message = runsql(
            'update languages set ' . 
            'LgName = ' . convert_string_to_sqlsyntax($_REQUEST["LgName"]) . ', ' . 
            'LgDict1URI = ' . convert_string_to_sqlsyntax($_REQUEST["LgDict1URI"]) . ', ' .
            'LgDict2URI = ' . convert_string_to_sqlsyntax($_REQUEST["LgDict2URI"]) . ', ' .
            'LgGoogleTranslateURI = ' . convert_string_to_sqlsyntax($_REQUEST["LgGoogleTranslateURI"]) . ', ' .
            'LgExportTemplate = ' . convert_string_to_sqlsyntax($_REQUEST["LgExportTemplate"]) . ', ' .
            'LgTextSize = ' . $_REQUEST["LgTextSize"] . ', ' .
            'LgCharacterSubstitutions = ' . convert_string_to_sqlsyntax_notrim_nonull($_REQUEST["LgCharacterSubstitutions"]) . ', ' .
            'LgRegexpSplitSentences = ' . convert_string_to_sqlsyntax($_REQUEST["LgRegexpSplitSentences"]) . ', ' .
            'LgExceptionsSplitSentences = ' . convert_string_to_sqlsyntax_notrim_nonull($_REQUEST["LgExceptionsSplitSentences"]) . ', ' .
            'LgRegexpWordCharacters = ' . convert_string_to_sqlsyntax($_REQUEST["LgRegexpWordCharacters"]) . ', ' .
            'LgRemoveSpaces = ' . $_REQUEST["LgRemoveSpaces"] . ', ' .
            'LgSplitEachChar = ' . $_REQUEST["LgSplitEachChar"] . ', ' . 
            'LgRightToLeft = ' . $_REQUEST["LgRightToLeft"] . 
            ' where LgID = ' . $val, 
            'Saved'
        );
    }
    return $message;
}

/**
 * Edit an existing text in the database.
 * 
 * @param {int} $lid Language ID
 * 
 * @return {string} Number of texts updated and items reparsed.
 * 
 * @global {string} $tbpref Database table prefix
 */
function edit_languages_op_change($lid) 
{

    // Get old values
    $sql = "select * from languages where LgID=" . $lid;
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    if ($record == false) { 
        my_die("Cannot access language data: $sql"); 
    }
    $oldCharacterSubstitutions = $record['LgCharacterSubstitutions'];
    $oldRegexpSplitSentences = $record['LgRegexpSplitSentences'];
    $oldExceptionsSplitSentences = $record['LgExceptionsSplitSentences'];
    $oldRegexpWordCharacters = $record['LgRegexpWordCharacters'];
    $oldRemoveSpaces = $record['LgRemoveSpaces'];
    $oldSplitEachChar = $record['LgSplitEachChar'];
    mysqli_free_result($res);

    $needReParse = (
        convert_string_to_sqlsyntax_notrim_nonull($_REQUEST["LgCharacterSubstitutions"]) 
        != convert_string_to_sqlsyntax_notrim_nonull($oldCharacterSubstitutions)
    ) || (
        convert_string_to_sqlsyntax($_REQUEST["LgRegexpSplitSentences"]) != 
        convert_string_to_sqlsyntax($oldRegexpSplitSentences)
    ) || (
        convert_string_to_sqlsyntax_notrim_nonull($_REQUEST["LgExceptionsSplitSentences"]) 
        != convert_string_to_sqlsyntax_notrim_nonull($oldExceptionsSplitSentences)
    ) || (
        convert_string_to_sqlsyntax($_REQUEST["LgRegexpWordCharacters"]) != 
        convert_string_to_sqlsyntax($oldRegexpWordCharacters)
    ) || ($_REQUEST["LgRemoveSpaces"] != $oldRemoveSpaces) ||
    ($_REQUEST["LgSplitEachChar"] != $oldSplitEachChar);
    

    $message = runsql(
        'update languages set ' . 
        'LgName = ' . convert_string_to_sqlsyntax($_REQUEST["LgName"]) . ', ' . 
        'LgDict1URI = ' . convert_string_to_sqlsyntax($_REQUEST["LgDict1URI"]) . ', ' .
        'LgDict2URI = ' . convert_string_to_sqlsyntax($_REQUEST["LgDict2URI"]) . ', ' .
        'LgGoogleTranslateURI = ' . convert_string_to_sqlsyntax($_REQUEST["LgGoogleTranslateURI"]) . ', ' .
        'LgExportTemplate = ' . convert_string_to_sqlsyntax($_REQUEST["LgExportTemplate"]) . ', ' .
        'LgTextSize = ' . $_REQUEST["LgTextSize"] . ', ' .
        'LgCharacterSubstitutions = ' . convert_string_to_sqlsyntax_notrim_nonull($_REQUEST["LgCharacterSubstitutions"]) . ', ' .
        'LgRegexpSplitSentences = ' . convert_string_to_sqlsyntax($_REQUEST["LgRegexpSplitSentences"]) . ', ' .
        'LgExceptionsSplitSentences = ' . convert_string_to_sqlsyntax_notrim_nonull($_REQUEST["LgExceptionsSplitSentences"]) . ', ' .
        'LgRegexpWordCharacters = ' . convert_string_to_sqlsyntax($_REQUEST["LgRegexpWordCharacters"]) . ', ' .
        'LgRemoveSpaces = ' . $_REQUEST["LgRemoveSpaces"] . ', ' .
        'LgSplitEachChar = ' . $_REQUEST["LgSplitEachChar"] . ', ' . 
        'LgRightToLeft = ' . $_REQUEST["LgRightToLeft"] . 
        ' where LgID = ' . $lid, 
        'Updated'
    );
    
    if ($needReParse) {
        runsql(
            'delete from sentences where SeLgID = ' . $lid, 
            "Sentences deleted"
        );
        runsql(
            'delete from textitems2 where Ti2LgID = ' . $lid, 
            "Text items deleted"
        );
        adjust_autoincr('sentences', 'SeID');
        runsql(
            "UPDATE  words 
            SET WoWordCount  = 0 
            where WoLgID = " . $lid, 
            ''
        );
        init_word_count();
        $sql = "select TxID, TxText 
        from texts 
        where TxLgID = " . $lid . " 
        order by TxID";
        $res = do_mysqli_query($sql);
        $cntrp = 0;
        while ($record = mysqli_fetch_assoc($res)) {
            $txtid = (int)$record["TxID"];
            $txttxt = $record["TxText"];
            splitCheckText($txttxt, $lid, $txtid);
            $cntrp++;
        }
        mysqli_free_result($res);
        $message .= " / Reparsed texts: " . $cntrp;
    } else {
        $message .= " / Reparsing not needed";
    }
    return $message;
}

/**
 * Display a form to createa new language.
 * 
 * @return void
 */
function edit_languages_new() 
{
    ?>
    <h4>
        New Language <a target="_blank" href="docs/info.html#howtolang">
        <img src="icn/question-frame.png" title="Help" alt="Help" /></a>
    </h4>

    <script type="text/javascript" charset="utf-8">
         $(document).ready(ask_before_exiting);
     </script>   
    <form class="validate" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" onsubmit="return check_dupl_lang(0);">
        <table class="tab1" cellspacing="0" cellpadding="5">
            <tr>
                <td class="td1 center backlightyellow" style="border-top-left-radius:inherit;border-top-right-radius:inherit;" colspan="2">
                <img src="icn/wizard.png" title="Language Settings Wizard" alt="Language Settings Wizard" class="click" onclick="window.open('select_lang_pair.php', 'wizard', 'width=400, height=400, scrollbars=yes, menubar=no, resizable=yes, status=no');" /><br /><span class="click" onclick="window.open('select_lang_pair.php', 'wizard', 'width=400, height=400, scrollbars=yes, menubar=no, resizable=yes, status=no');"><img src="icn/arrow-000-medium.png" title="-&gt;" alt="-&gt;" /> <b>Language Settings Wizard</b> <img src="icn/arrow-180-medium.png" title="&lt;-" alt="&lt;-" /></span><br /><span class="smallgray">Select your native (L1) and study (L2) languages, and let the wizard set all language settings marked in yellow!<br />(You can adjust the settings afterwards.)</span></td>
            </tr>
        </table>
    
        <table class="tab1" cellspacing="0" cellpadding="5">
        <tr>
        <td class="td1 right backlightyellow">Study Language "L2":</td>
        <td class="td1">
            <input type="text" class="notempty setfocus checkoutsidebmp" data_info="Study Language" name="LgName" id="LgName" value="" maxlength="40" size="40" /> <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" /></td>
        </tr>
        <tr>
        <td class="td1 right backlightyellow">Dictionary 1 URI:</td>
        <td class="td1">
            <input type="text" class="checkdicturl notempty checkoutsidebmp" name="LgDict1URI" value="" maxlength="200" size="60" data_info="Dictionary 1 URI" /> <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" /></td>
        </tr>
        <tr>
        <td class="td1 right">Dictionary 2 URI:</td>
        <td class="td1">
            <input type="text" class="checkdicturl checkoutsidebmp" name="LgDict2URI" value="" maxlength="200" size="60" data_info="Dictionary 2 URI" /></td>
        </tr>
        <tr>
        <td class="td1 right backlightyellow">GoogleTranslate URI:</td>
        <td class="td1">
            <input type="text" class="checkdicturl checkoutsidebmp" name="LgGoogleTranslateURI" value="*http://translate.google.com/?ie=UTF-8&sl=••&tl=••&text=###" maxlength="200" size="60" data_info="GoogleTranslate URI" /></td>
        </tr>
        <tr>
        <td class="td1 right backlightyellow">Text Size:</td>
        <td class="td1"><select name="LgTextSize" class="notempty">
            <?php echo get_languagessize_selectoptions(150); ?></select>
        </td>
        </tr>
        <tr>
        <td class="td1 right">Character Substitutions:</td>
        <td class="td1">
            <input type="text" class="checkoutsidebmp" data_info="Character Substitutions" name="LgCharacterSubstitutions" value="´='|`='|’='|‘='|...=…|..=‥" maxlength="500" size="60" /></td>
        </tr>
        <tr>
        <td class="td1 right backlightyellow">RegExp Split Sentences:</td>
        <td class="td1">
            <input type="text" class="notempty checkoutsidebmp" name="LgRegexpSplitSentences" value=".!?:;" maxlength="500" size="60" data_info="RegExp Split Sentences" /> <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" /></td>
        </tr>
        <tr>
        <td class="td1 right">Exceptions Split Sentences:</td>
        <td class="td1">
            <input type="text" class="checkoutsidebmp" data_info="Exceptions Split Sentences" name="LgExceptionsSplitSentences" value="Mr.|Dr.|[A-Z].|Vd.|Vds." maxlength="500" size="60" /></td>
        </tr>
        <tr>
        <td class="td1 right backlightyellow">RegExp Word Characters:</td>
        <td class="td1">
            <input type="text" class="notempty checkoutsidebmp" data_info="RegExp Word Characters" name="LgRegexpWordCharacters" value="a-zA-ZÀ-ÖØ-öø-ȳ" maxlength="500" size="60" /> <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" /></td>
        </tr>
        <tr>
        <td class="td1 right backlightyellow">Make each character a word:</td>
        <td class="td1"><select name="LgSplitEachChar">
            <?php echo get_yesno_selectoptions(0); ?></select>
            (e.g. for Chinese, Japanese, etc.)</td>
        </tr>
        <tr>
        <td class="td1 right backlightyellow">Remove spaces:</td>
        <td class="td1">
            <select name="LgRemoveSpaces">
                <?php echo get_yesno_selectoptions(0); ?>
            </select>
            (e.g. for Chinese, Japanese, etc.)</td>
        </tr>
        <tr>
        <td class="td1 right backlightyellow">Right-To-Left Script:</td>
        <td class="td1">
            <select name="LgRightToLeft">
                <?php echo get_yesno_selectoptions(0); ?>
            </select>
            (e.g. for Arabic, Hebrew, Farsi, Urdu, etc.)</td>
        </tr>
        <tr>
        <td class="td1 right">
            Export Template 
            <img class="click" src="icn/question-frame.png" title="Help" alt="Help" onclick="oewin('export_template.html');" /> :
        </td>
        <td class="td1">
            <input type="text" class="checkoutsidebmp" data_info="Export Template" name="LgExportTemplate" value="$y\t$t\n" maxlength="1000" size="60" />
        </td>
        </tr>
        <tr>
        <td class="td1 right" colspan="2">
            <input type="button" value="Cancel" onclick="{resetDirty(); location.href='edit_languages.php';}" /> 
        <input type="submit" name="op" value="Save" /></td>
        </tr>
        </table>
        <p class="smallgray">
            <b>Important:</b>
            <br />
            The placeholders "••" for the from/sl and dest/tl language codes in the 
            URIs must be <b>replaced</b> by the actual source and target language 
            codes!<br />
            <a href="docs/info.html#howtolang" target="_blank">Please read the documentation</a>. 
            Languages with a <b>non-Latin alphabet need special attention</b>, 
            <a href="docs/info.html#langsetup" target="_blank">see also here</a>.
        </p>    
    </form>
    
    <?php
}

/**
 * Display a form to edit an existing language.
 * 
 * @param {int} $lid Language ID
 * 
 * @return void
 */
function edit_languages_change($lid)
{

    $sql = 'select * from languages where LgID = ' . $lid;
    $res = do_mysqli_query($sql);
    if ($record = mysqli_fetch_assoc($res)) {
    
        ?>
    
     <h4>Edit Language 
         <a target="_blank" href="docs/info.html#howtolang">
             <img src="icn/question-frame.png" title="Help" alt="Help" /></a>
    </h4>
     <script type="text/javascript" charset="utf-8">
         $(document).ready(ask_before_exiting);
     </script>
     <form class="validate" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" onsubmit="return check_dupl_lang(<?php echo $lid; ?>);">
     <input type="hidden" name="LgID" value="<?php echo $lid; ?>" />
     <table class="tab1" cellspacing="0" cellpadding="5">
     <tr>
     <td class="td1 right">Study Language "L2":</td>
     <td class="td1">
         <input type="text" class="notempty setfocus checkoutsidebmp" data_info="Study Language" name="LgName" id="LgName" value="<?php echo tohtml($record['LgName']); ?>" maxlength="40" size="40" /> <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" /></td>
     </tr>
     <tr>
     <td class="td1 right">Dictionary 1 URI:</td>
     <td class="td1">
         <input type="text" class="notempty checkdicturl checkoutsidebmp" name="LgDict1URI" value="<?php echo tohtml($record['LgDict1URI']); ?>"  maxlength="200" size="60" data_info="Dictionary 1 URI" /> <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" /></td>
     </tr>
     <tr>
     <td class="td1 right">Dictionary 2 URI:</td>
     <td class="td1">
         <input type="text" class="checkdicturl checkoutsidebmp" name="LgDict2URI" value="<?php echo tohtml($record['LgDict2URI']); ?>" maxlength="200" size="60" data_info="Dictionary 2 URI" /></td>
     </tr>
     <tr>
     <td class="td1 right">GoogleTranslate URI:</td>
     <td class="td1">
         <input type="text" class="checkdicturl checkoutsidebmp" name="LgGoogleTranslateURI" value="<?php echo tohtml($record['LgGoogleTranslateURI']); ?>" maxlength="200" size="60" data_info="GoogleTranslate URI" /></td>
     </tr>
     <tr>
     <td class="td1 right">Text Size:</td>
     <td class="td1">
         <select name="LgTextSize"><?php echo get_languagessize_selectoptions($record['LgTextSize']); ?></select></td>
     </tr>
     <tr>
     <td class="td1 right">Character Substitutions:</td>
     <td class="td1">
         <input type="text" class="checkoutsidebmp" data_info="Character Substitutions" name="LgCharacterSubstitutions" value="<?php echo tohtml($record['LgCharacterSubstitutions']); ?>" maxlength="500" size="60" /></td>
     </tr>
     <tr>
     <td class="td1 right">RegExp Split Sentences:</td>
     <td class="td1">
         <input type="text" class="notempty checkoutsidebmp" name="LgRegexpSplitSentences" value="<?php echo tohtml($record['LgRegexpSplitSentences']); ?>" maxlength="500" size="60" data_info="RegExp Split Sentences" /> <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" /></td>
     </tr>
     <tr>
     <td class="td1 right">Exceptions Split Sentences:</td>
     <td class="td1">
         <input type="text" class="checkoutsidebmp" data_info="Exceptions Split Sentences" name="LgExceptionsSplitSentences" value="<?php echo tohtml($record['LgExceptionsSplitSentences']); ?>" maxlength="500" size="60" /></td>
     </tr>
     <tr>
     <td class="td1 right">RegExp Word Characters:</td>
     <td class="td1">
         <input type="text" class="notempty checkoutsidebmp" data_info="RegExp Word Characters" name="LgRegexpWordCharacters" value="<?php echo tohtml($record['LgRegexpWordCharacters']); ?>" maxlength="500" size="60" /> <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" /></td>
     </tr>
     <tr>
     <td class="td1 right">Make each character a word:</td>
     <td class="td1">
         <select name="LgSplitEachChar">
             <?php echo get_yesno_selectoptions($record['LgSplitEachChar']); ?>
            </select>
         (e.g. for Chinese, Japanese, etc.)</td>
     </tr>
     <tr>
     <td class="td1 right">Remove spaces:</td>
     <td class="td1">
         <select name="LgRemoveSpaces">
             <?php echo get_yesno_selectoptions($record['LgRemoveSpaces']); ?>
            </select>
         (e.g. for Chinese, Japanese, etc.)</td>
     </tr>
     <tr>
     <td class="td1 right">Right-To-Left Script:</td>
     <td class="td1">
         <select name="LgRightToLeft">
             <?php echo get_yesno_selectoptions($record['LgRightToLeft']); ?>
            </select>
         (e.g. for Arabic, Hebrew, Farsi, Urdu,  etc.)</td>
     </tr>
     <tr>
     <td class="td1 right">
         Export Template 
         <img class="click" src="icn/question-frame.png" title="Help" alt="Help" onclick="oewin('export_template.html');" /> :
        </td>
     <td class="td1">
         <input type="text" class="checkoutsidebmp" data_info="Export Template" name="LgExportTemplate" value="<?php echo tohtml($record['LgExportTemplate']); ?>" maxlength="1000" size="60" />
        </td>
     </tr>
     <tr>
     <td class="td1 right" colspan="2">
         <input type="button" value="Cancel" onclick="{resetDirty(); location.href='edit_languages.php';}" /> 
     <input type="submit" name="op" value="Change" /></td>
     </tr>
     </table>
     <p class="smallgray">
         <b>Warning:</b> Changing certain language settings 
         (e.g. RegExp Word Characters, etc.)<br />may cause partial or complete loss 
         of improved annotated texts!
    </p>
     </form>
        <?php

    }
    mysqli_free_result($res);
}

/**
 * Display the standard page of saved languages.
 * 
 * @param {string} $message An information message to display.
 * 
 * @global {string} $tbpref Database table prefix
 * @global {int}    $debug 1 to display debugging data
 * 
 * @return void
 */
function edit_languages_display($message)
{
    global $debug;

    echo error_message_with_hide($message, 0);
    
    $current = (int) getSetting('currentlanguage');
    
    $recno = get_first_value(
        'SELECT COUNT(*) AS value 
        FROM languages 
        WHERE LgName<>""'
    ); 
    
    ?>

<p>
    <a href="<?php echo $_SERVER['PHP_SELF']; ?>?new=1">
        <img src="icn/plus-button.png" title="New" alt="New" /> New Language ...
    </a>
</p>

    <?php
    if ($recno==0) {
        ?>
<p>No languages found.</p>
        <?php
        return;
    }
    ?>

<table class="sortable tab1" cellspacing="0" cellpadding="5">
    <tr>
        <th class="th1 sorttable_nosort">Curr.<br />Lang.</th>
        <th class="th1 sorttable_nosort">Test<br />↓↓↓</th>
        <th class="th1 sorttable_nosort">Actions</th>
        <th class="th1 clickable">Language</th>
        <th class="th1 sorttable_numeric clickable">Texts,<br />Reparse</th>
        <th class="th1 sorttable_numeric clickable">Arch.<br />Texts</th>
        <th class="th1 sorttable_numeric clickable">Newsfeeds<br />(Articles)</th>
        <th class="th1 sorttable_numeric clickable">Terms</th>
        <th class="th1 sorttable_nosort">Export<br />Template?</th>
    </tr>

    <?php

    $sql = 'SELECT LgID, LgName, LgExportTemplate 
    FROM languages 
    WHERE LgName<>"" ORDER BY LgName';
    if ($debug) { 
        echo $sql; 
    }
    // May be refactored with KISS principle
    $res = do_mysqli_query(
        'select NfLgID, count(*) as value 
        from newsfeeds 
        group by NfLgID'
    );
    $newsfeedcount = null;
    while ($record = mysqli_fetch_assoc($res)) {
        $newsfeedcount[$record['NfLgID']] = $record['value'];
    }
    // May be refactored with KISS principle
    $res = do_mysqli_query(
        'SELECT NfLgID, count(*) AS value 
        FROM newsfeeds, feedlinks 
        WHERE NfID=FlNfID 
        GROUP BY NfLgID'
    );
    $feedarticlescount = null;
    while ($record = mysqli_fetch_assoc($res)) {
        $feedarticlescount[$record['NfLgID']] = $record['value'];
    }
    $res = do_mysqli_query($sql);
    while ($record = mysqli_fetch_assoc($res)) {
        $lid = (int)$record['LgID'];
        $foo = get_first_value(
            'select count(TxID) as value 
            from texts 
            where TxLgID=' . $lid
        );
        $textcount = is_numeric($foo) ? (int)$foo : 0;
        $foo = get_first_value(
            'select count(AtID) as value 
            from archivedtexts 
            where AtLgID=' . $lid
        );
        $archtextcount = is_numeric($foo) ? (int)$foo : 0;
        $foo = get_first_value(
            'select count(WoID) as value 
            from words 
            where WoLgID=' . $lid
        );
        $wordcount = is_numeric($foo) ? (int)$foo : 0;
        if (is_null($newsfeedcount) || empty($newsfeedcount)) {
            $nfcount = 0;
        } else if (isset($newsfeedcount[$lid])) {
            $nfcount = (int)$newsfeedcount[$lid];
        } else {
            $nfcount = 0;
        }
        if (is_null($feedarticlescount) || empty($feedarticlescount)) {
            $fartcount = 0;
        } else if (isset($feedarticlescount[$lid])) {
            $fartcount = (int)$feedarticlescount[$lid];
        } else {
            $fartcount = 0;
        }
        echo '<tr>';
        if ($current == $lid) {
            $tdth = 'th';
            echo '<th class="th1" style="border-top-left-radius:0;">
                <img src="icn/exclamation-red.png" title="Current Language" alt="Current Language" />
                </th>';
        } else {
            $tdth = 'td';
            echo '<td class="td1 center">
                <a href="save_setting_redirect.php?k=currentlanguage&amp;v=' . $lid . '&amp;u=edit_languages.php">
                <img src="icn/tick-button.png" title="Set as Current Language" alt="Set as Current Language" />
                </a>
                </td>';
        }
        echo '<' . $tdth . ' class="' . $tdth . '1 center"><a href="do_test.php?lang=' . $lid . '">
            <img src="icn/question-balloon.png" title="Test" alt="Test" /></a></' . $tdth . '>';
        echo '<' . $tdth . ' class="' . $tdth . '1 center" nowrap="nowrap">&nbsp;<a href="' . $_SERVER['PHP_SELF'] . '?chg=' . $lid . '">
            <img src="icn/document--pencil.png" title="Edit" alt="Edit" /></a>';
        if ($textcount == 0 && $archtextcount == 0 && $wordcount == 0 && $nfcount == 0) { 
            echo '&nbsp; <span class="click" onclick="if (confirmDelete()) location.href=\'' . $_SERVER['PHP_SELF'] . '?del=' . $lid . '\';">
                <img src="icn/minus-button.png" title="Delete" alt="Delete" /></span>'; 
        } else { 
            echo '&nbsp; <img src="icn/placeholder.png" title="Delete not possible" alt="Delete not possible" />'; 
        }
        echo '&nbsp;</' . $tdth . '>';
        echo '<' . $tdth . ' class="' . $tdth . '1 center">' . tohtml((string)$record['LgName']) . '</' . $tdth . '>';
        if ($textcount > 0) { 
            echo '<' . $tdth . ' class="' . $tdth . '1 center">
                <a href="edit_texts.php?page=1&amp;query=&amp;filterlang=' . $lid . '">' . 
            $textcount . '</a> &nbsp;&nbsp; <a href="' . $_SERVER['PHP_SELF'] . '?refresh=' . $lid . '">
                <img src="icn/lightning.png" title="Reparse Texts" alt="Reparse Texts" /></a>'; 
        } else {
            echo '<' . $tdth . ' class="' . $tdth . '1 center">0 &nbsp;&nbsp; <img src="';
            print_file_path('icn/placeholder.png');
            echo'" title="No texts to reparse" alt="No texts to reparse" />';
        }
        echo '</' . $tdth . '>';
        echo '<' . $tdth . ' class="' . $tdth . '1 center">' . 
        ($archtextcount > 0 ? '<a href="edit_archivedtexts.php?page=1&amp;query=&amp;filterlang=' . $lid . '">' . 
        $archtextcount . '</a>' : '0' ) . '</' . $tdth . '>';
        echo '<' . $tdth . ' class="' . $tdth . '1 center">' . 
        ($nfcount > 0 ? '<a href="do_feeds.php?query=&amp;selected_feed=&amp;check_autoupdate=1&amp;filterlang=' . $lid . '">' . 
        $nfcount . ' (' . $fartcount . ')</a>' : '0' ) . '</' . $tdth . '>';
        echo '<' . $tdth . ' class="' . $tdth . '1 center">' . 
        ($wordcount > 0 ? '<a href="edit_words.php?page=1&amp;query=&amp;text=&amp;status=&amp;filterlang=' . 
        $lid . '&amp;status=&amp;tag12=0&amp;tag2=&amp;tag1=">' . $wordcount . '</a>' : '0' ) . '</' . $tdth . '>';
        echo '<' . $tdth . ' class="' . $tdth . '1 center" style="border-top-right-radius:0;">' . 
        (isset($record['LgExportTemplate']) ? '<img src="icn/status.png" title="Yes" alt="Yes" />' : 
        '<img src="icn/status-busy.png" title="No" alt="No" />' ) . '</' . $tdth . '>';
        echo '</tr>';
    }
    mysqli_free_result($res);

    ?>

</table>

        <?php
    
}

/**
 * Display a variation of the edit_language page.
 * 
 * @return void
 */
function edit_languages_do_page() 
{
    pagestart('My Languages', true);
    edit_languages_alert_duplicate();
    $message = '';
    if (isset($_REQUEST['refresh'])) {
        $message = edit_languages_refresh($_REQUEST['refresh']);
    }
    if (isset($_REQUEST['del'])) {
        $message = edit_languages_delete((int)$_REQUEST['del']);
    } elseif (isset($_REQUEST['op'])) {
        // Insert new text or change an existing one 
        if ($_REQUEST['op'] == 'Save') {
            // INSERT
            $message = edit_languages_op_save();
        } elseif ($_REQUEST['op'] == 'Change') {
            // UPDATE
            $message = edit_languages_op_change((int)$_REQUEST["LgID"]);
        }
    }

    // Display part

    if (isset($_REQUEST['new'])) {
        edit_languages_new();
    } elseif (isset($_REQUEST['chg'])) {
        edit_languages_change((int)$_REQUEST['chg']);
    } else {
        edit_languages_display($message);
    }
    pageend();
}

edit_languages_do_page();
?> 
