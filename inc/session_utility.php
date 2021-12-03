<?php

/**
 * \file
 * \brief All the files needed for a LWT session.
 * 
 * By requiring this file, you start a session, connect to the 
 * database and declare a lot of useful functions.
 * 
 * @author https://github.com/HugoFara/lwt/graphs/contributors GitHub contributors
 */

require 'kernel_utility.php';
require_once "db_accessors.php";


/**
 * Return the list of all tags.
 * 
 * @param  int $refresh If true, refresh all tags for session
 * @global string $tbpref Table name prefix
 * @return string[] All tags
 */
function get_tags($refresh = 0) 
{
    global $tbpref;
    if (isset($_SESSION['TAGS']) 
        && is_array($_SESSION['TAGS']) 
        && isset($_SESSION['TBPREF_TAGS']) 
        && $_SESSION['TBPREF_TAGS'] == $tbpref . url_base() 
        && $refresh == 0
    ) {
            return $_SESSION['TAGS'];
    }
    $tags = array();
    $sql = 'SELECT TgText FROM ' . $tbpref . 'tags ORDER BY TgText';
    $res = do_mysqli_query($sql);
    while ($record = mysqli_fetch_assoc($res)) {
        $tags[] = $record["TgText"];
    }
    mysqli_free_result($res);
    $_SESSION['TAGS'] = $tags;
    $_SESSION['TBPREF_TAGS'] = $tbpref . url_base();
    return $_SESSION['TAGS'];
}

/**
 * Return the list of all text tags.
 * 
 * @param  int $refresh If true, refresh all text tags for session
 * @global string $tbpref Table name prefix
 * @return string[] All text tags
 */
function get_texttags($refresh = 0) 
{
    global $tbpref;
    if (isset($_SESSION['TEXTTAGS'])) {
        if (is_array($_SESSION['TEXTTAGS'])) {
            if (isset($_SESSION['TBPREF_TEXTTAGS'])) {
                if($_SESSION['TBPREF_TEXTTAGS'] == $tbpref . url_base()) {
                    if ($refresh == 0) { 
                        return $_SESSION['TEXTTAGS']; 
                    }
                }
            }
        }
    }
    $tags = array();
    $sql = 'SELECT T2Text FROM ' . $tbpref . 'tags2 ORDER BY T2Text';
    $res = do_mysqli_query($sql);
    while ($record = mysqli_fetch_assoc($res)) {
        $tags[] = $record["T2Text"];
    }
    mysqli_free_result($res);
    $_SESSION['TEXTTAGS'] = $tags;
    $_SESSION['TBPREF_TEXTTAGS'] = $tbpref . url_base();
    return $_SESSION['TEXTTAGS'];
}

// -------------------------------------------------------------

function getTextTitle($textid) 
{
    global $tbpref;
    $text = get_first_value(
        "SELECT TxTitle AS value 
        FROM " . $tbpref . "texts 
        WHERE TxID=" . $textid
    );
    if (!isset($text)) { 
        $text = "?"; 
    }
    return $text;
}

// -------------------------------------------------------------

function get_tag_selectoptions($v,$l) 
{
    global $tbpref;
    if (! isset($v) ) { $v = ''; 
    }
    $r = "<option value=\"\"" . get_selected($v, '');
    $r .= ">[Filter off]</option>";
    if ($l == '') {
        $sql = "select TgID, TgText from " . $tbpref . "words, " . $tbpref . "tags, " . $tbpref . "wordtags where TgID = WtTgID and WtWoID = WoID group by TgID order by UPPER(TgText)"; 
    }
    else {
        $sql = "select TgID, TgText from " . $tbpref . "words, " . $tbpref . "tags, " . $tbpref . "wordtags where TgID = WtTgID and WtWoID = WoID and WoLgID = " . $l . " group by TgID order by UPPER(TgText)"; 
    }
    $res = do_mysqli_query($sql);
    $cnt = 0;
    while ($record = mysqli_fetch_assoc($res)) {
        $d = $record["TgText"];
        $cnt++;
        $r .= "<option value=\"" . $record["TgID"] . "\"" . get_selected($v, $record["TgID"]) . ">" . tohtml($d) . "</option>";
    }
    mysqli_free_result($res);
    if ($cnt > 0) {
        $r .= "<option disabled=\"disabled\">--------</option>";
        $r .= "<option value=\"-1\"" . get_selected($v, -1) . ">UNTAGGED</option>";
    }
    return $r;
}

// -------------------------------------------------------------

function get_texttag_selectoptions($v,$l) 
{
    global $tbpref;
    if (!isset($v) ) {
        $v = ''; 
    }
    $r = "<option value=\"\"" . get_selected($v, '');
    $r .= ">[Filter off]</option>";
    if ($l == '') {
        $sql = "select T2ID, T2Text from " . $tbpref . "texts, " . $tbpref . "tags2, " . $tbpref . "texttags where T2ID = TtT2ID and TtTxID = TxID group by T2ID order by UPPER(T2Text)"; 
    }
    else {
        $sql = "select T2ID, T2Text from " . $tbpref . "texts, " . $tbpref . "tags2, " . $tbpref . "texttags where T2ID = TtT2ID and TtTxID = TxID and TxLgID = " . $l . " group by T2ID order by UPPER(T2Text)"; 
    }
    $res = do_mysqli_query($sql);
    $cnt = 0;
    while ($record = mysqli_fetch_assoc($res)) {
        $d = $record["T2Text"];
        $cnt++;
        $r .= "<option value=\"" . $record["T2ID"] . "\"" . get_selected($v, $record["T2ID"]) . ">" . tohtml($d) . "</option>";
    }
    mysqli_free_result($res);
    if ($cnt > 0) {
        $r .= "<option disabled=\"disabled\">--------</option>";
        $r .= "<option value=\"-1\"" . get_selected($v, -1) . ">UNTAGGED</option>";
    }
    return $r;
}

// -------------------------------------------------------------

function get_txtag_selectoptions($l,$v)
{
    global $tbpref;
    $text_tags=array();
    if (!isset($v) ) { 
        $v = ''; 
    }
    $u ='';
    $r = "<option value=\"&amp;texttag\"" . get_selected($v, '');
    $r .= ">[Filter off]</option>";
    $sql = 'SELECT IFNULL(T2Text, 1) AS TagName, TtT2ID AS TagID, GROUP_CONCAT(TxID ORDER BY TxID) AS TextID FROM ' . $tbpref . 'texts';
    $sql .= ' LEFT JOIN ' . $tbpref . 'texttags ON TxID = TtTxID';
    $sql .= ' LEFT JOIN ' . $tbpref . 'tags2 ON TtT2ID = T2ID';
    if($l) { $sql .= ' WHERE TxLgID='.$l; 
    }
    $sql .= ' GROUP BY UPPER(TagName)';
    $res = do_mysqli_query($sql);
    while ($record = mysqli_fetch_assoc($res)) {
        if($record['TagName']==1) {
            $u ="<option disabled=\"disabled\">--------</option><option value=\"" . $record['TextID'] . "&amp;texttag=-1\"" . get_selected($v, "-1") . ">UNTAGGED</option>";
        }
        else {
            $r .= "<option value=\"" .$record['TextID']."&amp;texttag=". $record['TagID'] . "\"" . get_selected($v, $record['TagID']) . ">" . $record['TagName'] . "</option>";
        }
    }
    mysqli_free_result($res);
    return $r.$u;
}

// -------------------------------------------------------------

function get_archivedtexttag_selectoptions($v,$l) 
{
    global $tbpref;
    if (! isset($v) ) { $v = ''; 
    }
    $r = "<option value=\"\"" . get_selected($v, '');
    $r .= ">[Filter off]</option>";
    if ($l == '') {
        $sql = "select T2ID, T2Text from " . $tbpref . "archivedtexts, " . $tbpref . "tags2, " . $tbpref . "archtexttags where T2ID = AgT2ID and AgAtID = AtID group by T2ID order by UPPER(T2Text)"; 
    }
    else {
        $sql = "select T2ID, T2Text from " . $tbpref . "archivedtexts, " . $tbpref . "tags2, " . $tbpref . "archtexttags where T2ID = AgT2ID and AgAtID = AtID and AtLgID = " . $l . " group by T2ID order by UPPER(T2Text)"; 
    }
    $res = do_mysqli_query($sql);
    $cnt = 0;
    while ($record = mysqli_fetch_assoc($res)) {
        $d = $record["T2Text"];
        $cnt++;
        $r .= "<option value=\"" . $record["T2ID"] . "\"" . get_selected($v, $record["T2ID"]) . ">" . tohtml($d) . "</option>";
    }
    mysqli_free_result($res);
    if ($cnt > 0) {
        $r .= "<option disabled=\"disabled\">--------</option>";
        $r .= "<option value=\"-1\"" . get_selected($v, -1) . ">UNTAGGED</option>";
    }
    return $r;
}

// -------------------------------------------------------------

function saveWordTags($wid) 
{
    global $tbpref;
    runsql("DELETE from " . $tbpref . "wordtags WHERE WtWoID =" . $wid, '');
    if (!isset($_REQUEST['TermTags'])  
        || !is_array($_REQUEST['TermTags'])  
        || !isset($_REQUEST['TermTags']['TagList'])  
        || !is_array($_REQUEST['TermTags']['TagList'])
    ) {
         return;
    }
    $cnt = count($_REQUEST['TermTags']['TagList']);
    if ($cnt > 0 ) {
        for ($i=0; $i<$cnt; $i++) {
            $tag = $_REQUEST['TermTags']['TagList'][$i];
            if(!in_array($tag, $_SESSION['TAGS'])) {
                runsql(
                    'insert into ' . $tbpref . 'tags (TgText) values(' . 
                    convert_string_to_sqlsyntax($tag) . ')', ""
                );
            }
            runsql(
                'INSERT INTO ' . $tbpref . 'wordtags (WtWoID, WtTgID) 
                SELECT ' . $wid . ', TgID 
                FROM ' . $tbpref . 'tags 
                WHERE TgText = ' . convert_string_to_sqlsyntax($tag), ""
            );
        }
        get_tags($refresh = 1);  // refresh tags cache
    }
}

// -------------------------------------------------------------

function saveTextTags($tid) 
{
    global $tbpref;
    runsql("DELETE from " . $tbpref . "texttags WHERE TtTxID =" . $tid, '');
    if (isset($_REQUEST['TextTags'])) {
        if (is_array($_REQUEST['TextTags'])) {
            if (isset($_REQUEST['TextTags']['TagList'])) {
                if (is_array($_REQUEST['TextTags']['TagList'])) {
                    $cnt = count($_REQUEST['TextTags']['TagList']);
                    if ($cnt > 0 ) {
                        for ($i=0; $i<$cnt; $i++) {
                            $tag = $_REQUEST['TextTags']['TagList'][$i];
                            if(! in_array($tag, $_SESSION['TEXTTAGS'])) {
                                runsql(
                                    'insert into ' . $tbpref . 'tags2 (T2Text) values(' . 
                                    convert_string_to_sqlsyntax($tag) . ')', ""
                                );
                            }
                            runsql(
                                'INSERT INTO ' . $tbpref . 'texttags (TtTxID, TtT2ID) 
                                SELECT ' . $tid . ', T2ID 
                                FROM ' . $tbpref . 'tags2 
                                WHERE T2Text = ' . convert_string_to_sqlsyntax($tag), 
                                ""
                            );
                        }
                        get_texttags($refresh = 1);  // refresh tags cache
                    }
                }
            }
        }
    }
}

// -------------------------------------------------------------

function saveArchivedTextTags($tid) 
{
    global $tbpref;
    runsql("DELETE from " . $tbpref . "archtexttags WHERE AgAtID =" . $tid, '');
    if (isset($_REQUEST['TextTags'])) {
        if (is_array($_REQUEST['TextTags'])) {
            if (isset($_REQUEST['TextTags']['TagList'])) {
                if (is_array($_REQUEST['TextTags']['TagList'])) {
                    $cnt = count($_REQUEST['TextTags']['TagList']);
                    if ($cnt > 0 ) {
                        for ($i=0; $i<$cnt; $i++) {
                            $tag = $_REQUEST['TextTags']['TagList'][$i];
                            if(! in_array($tag, $_SESSION['TEXTTAGS'])) {
                                runsql(
                                    'insert into ' . $tbpref . 'tags2 (T2Text) values(' . 
                                    convert_string_to_sqlsyntax($tag) . ')', ""
                                );
                            }
                            runsql(
                                'INSERT INTO ' . $tbpref . 'archtexttags (AgAtID, AgT2ID) 
                                SELECT ' . $tid . ', T2ID 
                                FROM ' . $tbpref . 'tags2 
                                WHERE T2Text = ' . convert_string_to_sqlsyntax($tag), 
                                ""
                            );
                        }
                        get_texttags($refresh = 1);  // refresh tags cache
                    }
                }
            }
        }
    }
}

// -------------------------------------------------------------

function getWordTags($wid) 
{
    global $tbpref;
    $r = '<ul id="termtags">';
    if ($wid > 0) {
        $sql = 'select TgText from ' . $tbpref . 'wordtags, ' . $tbpref . 'tags where TgID = WtTgID and WtWoID = ' . $wid . ' order by TgText';
        $res = do_mysqli_query($sql);
        while ($record = mysqli_fetch_assoc($res)) {
            $r .= '<li>' . tohtml($record["TgText"]) . '</li>';
        }
        mysqli_free_result($res);
    }
    $r .= '</ul>';
    return $r;
}

// -------------------------------------------------------------

function getTextTags($tid) 
{
    global $tbpref;
    $r = '<ul id="texttags">';
    if ($tid > 0) {
        $sql = 'select T2Text from ' . $tbpref . 'texttags, ' . $tbpref . 'tags2 where T2ID = TtT2ID and TtTxID = ' . $tid . ' order by T2Text';
        $res = do_mysqli_query($sql);
        while ($record = mysqli_fetch_assoc($res)) {
            $r .= '<li>' . tohtml($record["T2Text"]) . '</li>';
        }
        mysqli_free_result($res);
    }
    $r .= '</ul>';
    return $r;
}

// -------------------------------------------------------------

function getArchivedTextTags($tid) 
{
    global $tbpref;
    $r = '<ul id="texttags">';
    if ($tid > 0) {
        $sql = 'select T2Text from ' . $tbpref . 'archtexttags, ' . $tbpref . 'tags2 where T2ID = AgT2ID and AgAtID = ' . $tid . ' order by T2Text';
        $res = do_mysqli_query($sql);
        while ($record = mysqli_fetch_assoc($res)) {
            $r .= '<li>' . tohtml($record["T2Text"]) . '</li>';
        }
        mysqli_free_result($res);
    }
    $r .= '</ul>';
    return $r;
}

// -------------------------------------------------------------

function addtaglist($item, $list) 
{
    global $tbpref;
    $tagid = get_first_value('select TgID as value from ' . $tbpref . 'tags where TgText = ' . convert_string_to_sqlsyntax($item));
    if (! isset($tagid)) {
        runsql('insert into ' . $tbpref . 'tags (TgText) values(' . convert_string_to_sqlsyntax($item) . ')', "");
        $tagid = get_first_value('select TgID as value from ' . $tbpref . 'tags where TgText = ' . convert_string_to_sqlsyntax($item));
    }
    $sql = 'select WoID from ' . $tbpref . 'words LEFT JOIN ' . $tbpref . 'wordtags ON WoID = WtWoID AND WtTgID = ' . $tagid . ' WHERE WtTgID IS NULL AND WoID in ' . $list;
    $res = do_mysqli_query($sql);
    $cnt = 0;
    while ($record = mysqli_fetch_assoc($res)) {
        $cnt += runsql('insert ignore into ' . $tbpref . 'wordtags (WtWoID, WtTgID) values(' . $record['WoID'] . ', ' . $tagid . ')', "");
    }
    mysqli_free_result($res);
    get_tags($refresh = 1);
    return "Tag added in $cnt Terms";
}

// -------------------------------------------------------------

function addarchtexttaglist($item, $list) 
{
    global $tbpref;
    $tagid = get_first_value('select T2ID as value from ' . $tbpref . 'tags2 where T2Text = ' . convert_string_to_sqlsyntax($item));
    if (! isset($tagid)) {
        runsql('insert into ' . $tbpref . 'tags2 (T2Text) values(' . convert_string_to_sqlsyntax($item) . ')', "");
        $tagid = get_first_value('select T2ID as value from ' . $tbpref . 'tags2 where T2Text = ' . convert_string_to_sqlsyntax($item));
    }
    $sql = 'select AtID from ' . $tbpref . 'archivedtexts LEFT JOIN ' . $tbpref . 'archtexttags ON AtID = AgAtID AND AgT2ID = ' . $tagid . ' WHERE AgT2ID IS NULL AND AtID in ' . $list;
    $res = do_mysqli_query($sql);
    $cnt = 0;
    while ($record = mysqli_fetch_assoc($res)) {
        $cnt += runsql('insert ignore into ' . $tbpref . 'archtexttags (AgAtID, AgT2ID) values(' . $record['AtID'] . ', ' . $tagid . ')', "");
    }
    mysqli_free_result($res);
    get_texttags($refresh = 1);
    return "Tag added in $cnt Texts";
}

// -------------------------------------------------------------

function addtexttaglist($item, $list) 
{
    global $tbpref;
    $tagid = get_first_value('select T2ID as value from ' . $tbpref . 'tags2 where T2Text = ' . convert_string_to_sqlsyntax($item));
    if (! isset($tagid)) {
        runsql('insert into ' . $tbpref . 'tags2 (T2Text) values(' . convert_string_to_sqlsyntax($item) . ')', "");
        $tagid = get_first_value('select T2ID as value from ' . $tbpref . 'tags2 where T2Text = ' . convert_string_to_sqlsyntax($item));
    }
    $sql = 'select TxID from ' . $tbpref . 'texts  LEFT JOIN ' . $tbpref . 'texttags ON TxID = TtTxID AND TtT2ID = ' . $tagid . ' WHERE TtT2ID IS NULL AND TxID in ' . $list;
    $res = do_mysqli_query($sql);
    $cnt = 0;
    while ($record = mysqli_fetch_assoc($res)) {
        $cnt += runsql('insert ignore into ' . $tbpref . 'texttags (TtTxID, TtT2ID) values(' . $record['TxID'] . ', ' . $tagid . ')', "");
    }
    mysqli_free_result($res);
    get_texttags($refresh = 1);
    return "Tag added in $cnt Texts";
}

// -------------------------------------------------------------

function removetaglist($item, $list) 
{
    global $tbpref;
    $tagid = get_first_value(
        'SELECT TgID AS value
        FROM ' . $tbpref . 'tags
        WHERE TgText = ' . convert_string_to_sqlsyntax($item)
    );
    if (! isset($tagid)) { 
        return "Tag " . $item . " not found"; 
    }
    $sql = 'select WoID from ' . $tbpref . 'words where WoID in ' . $list;
    $res = do_mysqli_query($sql);
    $cnt = 0;
    while ($record = mysqli_fetch_assoc($res)) {
        $cnt++;
        runsql(
            'DELETE FROM ' . $tbpref . 'wordtags
            WHERE WtWoID = ' . $record['WoID'] . ' AND WtTgID = ' . $tagid, 
            ""
        );
    }
    mysqli_free_result($res);
    return "Tag removed in $cnt Terms";
}

// -------------------------------------------------------------

function removearchtexttaglist($item, $list) 
{
    global $tbpref;
    $tagid = get_first_value('select T2ID as value from ' . $tbpref . 'tags2 where T2Text = ' . convert_string_to_sqlsyntax($item));
    if (! isset($tagid)) { return "Tag " . $item . " not found"; 
    }
    $sql = 'select AtID from ' . $tbpref . 'archivedtexts where AtID in ' . $list;
    $res = do_mysqli_query($sql);
    $cnt = 0;
    while ($record = mysqli_fetch_assoc($res)) {
        $cnt++;
        runsql('delete from ' . $tbpref . 'archtexttags where AgAtID = ' . $record['AtID'] . ' and AgT2ID = ' . $tagid, "");
    }
    mysqli_free_result($res);
    return "Tag removed in $cnt Texts";
}

// -------------------------------------------------------------

function removetexttaglist($item, $list) 
{
    global $tbpref;
    $tagid = get_first_value('select T2ID as value from ' . $tbpref . 'tags2 where T2Text = ' . convert_string_to_sqlsyntax($item));
    if (!isset($tagid)) { 
        return "Tag " . $item . " not found"; 
    }
    $sql = 'select TxID from ' . $tbpref . 'texts where TxID in ' . $list;
    $res = do_mysqli_query($sql);
    $cnt = 0;
    while ($record = mysqli_fetch_assoc($res)) {
        $cnt++;
        runsql('delete from ' . $tbpref . 'texttags where TtTxID = ' . $record['TxID'] . ' and TtT2ID = ' . $tagid, "");
    }
    mysqli_free_result($res);
    return "Tag removed in $cnt Texts";
}

// -------------------------------------------------------------

function load_feeds($currentfeed)
{
    global $tbpref;
    $cnt=0;
    $ajax=$feeds=array();
    echo '<script type="text/javascript">';
    if (isset($_REQUEST['check_autoupdate'])) {
        $c_feeds=array();
        $result = do_mysqli_query("SELECT * FROM " . $tbpref . "newsfeeds where `NfOptions` like '%autoupdate=%'");
        while($row = mysqli_fetch_assoc($result)){
            if($autoupdate=get_nf_option($row['NfOptions'], 'autoupdate')) {
                if(strpos($autoupdate, 'h')!==false) {
                    $autoupdate=str_replace('h', '', $autoupdate);
                    $autoupdate=60 * 60 * $autoupdate;
                }
                elseif(strpos($autoupdate, 'd')!==false) {
                    $autoupdate=str_replace('d', '', $autoupdate);
                    $autoupdate=60 * 60 * 24 * $autoupdate;
                }
                elseif(strpos($autoupdate, 'w')!==false) {
                    $autoupdate=str_replace('w', '', $autoupdate);
                    $autoupdate=60 * 60 * 24 * 7 * $autoupdate;
                }
                else { 
                    continue; 
                }
                if(time()>($autoupdate + $row['NfUpdate'])) {
                    $ajax[$cnt]=  "$.ajax({type: 'POST',beforeSend: function(){ $('#feed_" . $row['NfID'] . "').replaceWith( '<div id=\"feed_" . $row['NfID'] . "\" class=\"msgblue\"><p>". addslashes($row['NfName']).": loading</p></div>' );},url:'inc/ajax_load_feed.php', data: { NfID: '".$row['NfID']."', NfSourceURI: '". $row['NfSourceURI']."', NfName: '". addslashes($row['NfName'])."', NfOptions: '". $row['NfOptions']."', cnt: '". $cnt."' },success:function (data) {feedcnt+=1;$('#feedcount').text(feedcnt);$('#feed_" . $row['NfID'] . "').replaceWith( data );}})";
                    $cnt+=1;
                    $feeds[$row['NfID']]=$row['NfName'];
                }
            }
        }
        mysqli_free_result($result);
    }
    else{
        $sql="SELECT * FROM " . $tbpref . "newsfeeds WHERE NfID in ($currentfeed)";
        $result = do_mysqli_query($sql);
        while($row = mysqli_fetch_assoc($result)){
            $ajax[$cnt]=  "$.ajax({type: 'POST',beforeSend: function(){ $('#feed_" . $row['NfID'] . "').replaceWith( '<div id=\"feed_" . $row['NfID'] . "\" class=\"msgblue\"><p>". addslashes($row['NfName']).": loading</p></div>' );},url:'inc/ajax_load_feed.php', data: { NfID: '".$row['NfID']."', NfSourceURI: '". $row['NfSourceURI']."', NfName: '". addslashes($row['NfName'])."', NfOptions: '". $row['NfOptions']."', cnt: '". $cnt."' },success:function (data) {feedcnt+=1;$('#feedcount').text(feedcnt);$('#feed_" . $row['NfID'] . "').replaceWith( data );}})";
            $cnt+=1;
            $feeds[$row['NfID']]=$row['NfName'];
        }
        mysqli_free_result($result);
    }
    if(!empty($ajax)) {
        $z=array();
        for($i=1;$i<=$cnt;$i++){
            $z[]='a'.$i;
        }
        echo "feedcnt=0;\n";
        echo '$(document).ready(function(){ $.when(',implode(',', $ajax),").then(function(",implode(',', $z),"){window.location.replace(\"",$_SERVER['PHP_SELF'],"\");});});";
    }
    else { echo "window.location.replace(\"",$_SERVER['PHP_SELF'],"\");"; 
    }
    echo "\n</script>\n";
    if($cnt!=1) { 
        echo "<div class=\"msgblue\"><p>UPDATING <span id=\"feedcount\">0</span>/",$cnt," FEEDS</p></div>"; 
    }
    foreach($feeds as $k=>$v){
        echo "<div id='feed_$k' class=\"msgblue\"><p>". $v.": waiting</p></div>";
    }
    echo "<div class=\"center\"><button onclick='window.location.replace(\"",$_SERVER['PHP_SELF'],"\");'>Continue</button></div>";
}

// -------------------------------------------------------------


function write_rss_to_db($texts)
{
    global $tbpref;
    $texts=array_reverse($texts);
    $message1=$message2=$message3=$message4=0;
    foreach($texts as $text){
        $Nf_ID[]=$text['Nf_ID'];
    }
    $Nf_ID=array_unique($Nf_ID);
    $Nf_tag='';
    foreach($Nf_ID as $feed_ID){
        foreach($texts as $text){
            if($feed_ID==$text['Nf_ID']) {
                if($Nf_tag!='"'.implode('","', $text['TagList']).'"') {
                    $Nf_tag= '"'.implode('","', $text['TagList']).'"';
                    foreach($text['TagList'] as $tag){
                        if(! in_array($tag, $_SESSION['TEXTTAGS'])) {
                            do_mysqli_query('insert into ' . $tbpref . 'tags2 (T2Text) values (' . convert_string_to_sqlsyntax($tag) . ')');
                        }
                    }
                    $nf_max_texts=$text['Nf_Max_Texts'];
                }
                echo '<div class="msgblue"><p class="hide_message">+++ "' . $text['TxTitle']. '" added! +++</p></div>';
                do_mysqli_query('INSERT INTO ' . $tbpref . 'texts (TxLgID,TxTitle,TxText,TxAudioURI,TxSourceURI)VALUES ('.$text['TxLgID'].',' . convert_string_to_sqlsyntax($text['TxTitle']) .','. convert_string_to_sqlsyntax($text['TxText']) .','. convert_string_to_sqlsyntax($text['TxAudioURI']) .','.convert_string_to_sqlsyntax($text['TxSourceURI']) .')');
                $id = get_last_key();
                splitCheckText(
                    get_first_value(
                        'select TxText as value from ' . $tbpref . 'texts where TxID = ' . $id
                    ), 
                    get_first_value(
                        'select TxLgID as value from ' . $tbpref . 'texts where TxID = ' . $id
                    ), 
                    $id 
                );
                do_mysqli_query('insert into ' . $tbpref . 'texttags (TtTxID, TtT2ID) select ' . $id . ', T2ID from ' . $tbpref . 'tags2 where T2Text in (' . $Nf_tag .')');        
            }
        }
        get_texttags(1);
        $result=do_mysqli_query("SELECT TtTxID FROM " . $tbpref . "texttags join " . $tbpref . "tags2 on TtT2ID=T2ID WHERE T2Text in (". $Nf_tag .")");
        $text_count=0;
        while($row = mysqli_fetch_assoc($result)){
            $text_item[$text_count++]=$row['TtTxID'];
        }
        mysqli_free_result($result);
        if($text_count>$nf_max_texts) {
            sort($text_item, SORT_NUMERIC);
            $text_item=array_slice($text_item, 0, $text_count-$nf_max_texts);
            foreach ($text_item as $text_ID){
                $message3 += runsql(
                    'delete from ' . $tbpref . 'textitems2 where Ti2TxID = ' . $text_ID, 
                    ""
                );
                $message2 += runsql(
                    'delete from ' . $tbpref . 'sentences where SeTxID = ' . $text_ID, 
                    ""
                );
                $message4 += runsql('insert into ' . $tbpref . 'archivedtexts (AtLgID, AtTitle, AtText, AtAnnotatedText, AtAudioURI, AtSourceURI) select TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI, TxSourceURI from ' . $tbpref . 'texts where TxID = ' . $text_ID, "");
                $id = get_last_key();
                runsql('insert into ' . $tbpref . 'archtexttags (AgAtID, AgT2ID) select ' . $id . ', TtT2ID from ' . $tbpref . 'texttags where TtTxID = ' . $text_ID, "");    
                $message1 += runsql('delete from ' . $tbpref . 'texts where TxID = ' . $text_ID, "");
                //                $message .= $message4 . " / " . $message1 . " / " . $message2 . " / " . $message3;
                adjust_autoincr('texts', 'TxID');
                adjust_autoincr('sentences', 'SeID');
                runsql("DELETE " . $tbpref . "texttags FROM (" . $tbpref . "texttags LEFT JOIN " . $tbpref . "texts on TtTxID = TxID) WHERE TxID IS NULL", '');        
            }
        }
    }
    if ($message4>0 || $message1>0) { 
        return "Texts archived: " . $message1 . " / Sentences deleted: " . $message2 . " / Text items deleted: " . $message3; 
    }
    else { 
        return ''; 
    }
}

// -------------------------------------------------------------

function print_last_feed_update($diff)
{
    $periods = array(
    array(60 * 60 * 24 * 365 , 'year'),
    array(60 * 60 * 24 * 30 , 'month'),
    array(60 * 60 * 24 * 7, 'week'),
    array(60 * 60 * 24 , 'day'),
    array(60 * 60 , 'hour'),
    array(60 , 'minute'),
    array(1 , 'second'),
    );
    if($diff>=1) {
        for($key=0;$key<7;$key++){
            $x=intval($diff/$periods[$key][0]);
            if($x>=1) {
                echo " last update: $x ";
                print_r($periods[$key][1]);
                if($x>1) { echo 's'; 
                }echo ' ago';break;
            }
        }
    }
    else { echo ' up to date'; 
    }
}

// -------------------------------------------------------------

function get_nf_option($str,$option)
{
    $arr=explode(',', $str);
    if($option=='all') { $all=array(); 
    }
    foreach($arr as $value){
        $res=explode('=', $value);
        if(trim($res[0])==$option) { return $res[1]; 
        }
        if($option=='all') { $all[$res[0]]=$res[1]; 
        }
    }
    if($option=='all') { return $all; 
    }
    return null;
}

// -------------------------------------------------------------

function get_links_from_new_feed($NfSourceURI)
{
    $rss = new DOMDocument('1.0', 'utf-8');
    if (!$rss->load($NfSourceURI, LIBXML_NOCDATA | ENT_NOQUOTES)) { 
        return false; 
    }
    $rss_data = array();
    $desc_count=0;
    $desc_nocount=0;
    $enc_count=0;
    $enc_nocount=0;
    if ($rss->getElementsByTagName('rss')->length !== 0) {
        $feed_tags = array(
            'item' => 'item',
            'title' => 'title',
            'description' => 'description',
            'link' => 'link'
        );
    }
    elseif ($rss->getElementsByTagName('feed')->length !== 0) {
        $feed_tags = array(
            'item' => 'entry',
            'title' => 'title',
            'description' => 'summary',
            'link' => 'link'
        );
    }
    else { 
        return false; 
    }
    foreach ($rss->getElementsByTagName($feed_tags['item']) as $node) {
        $item = array ( 
            'title' => preg_replace(
                array('/\s\s+/','/\ \&\ /','/\"/'), 
                array(' ',' &amp; ','\"'), 
                trim($node->getElementsByTagName($feed_tags['title'])->item(0)->nodeValue)
            ),
            'desc' => preg_replace(
                array('/\s\s+/','/\ \&\ /','/\<[^\>]*\>/','/\"/'), 
                array(' ',' &amp; ','','\"'), 
                trim($node->getElementsByTagName($feed_tags['description'])->item(0)->nodeValue)
            ),
            'link' => trim(
                ($feed_tags['item']=='entry') ? 
                ($node->getElementsByTagName($feed_tags['link'])->item(0)->getAttribute('href')) : 
                ($node->getElementsByTagName($feed_tags['link'])->item(0)->nodeValue)
            ),
        );
        if ($feed_tags['item']=='item') {
            foreach($node->getElementsByTagName('encoded') as $txt_node) {
                if($txt_node->parentNode===$node) {
                    $item['encoded'] = $txt_node->ownerDocument->saveHTML($txt_node);
                    $item['encoded'] = mb_convert_encoding(
                        html_entity_decode($item['encoded'], ENT_NOQUOTES, "UTF-8"), 
                        "HTML-ENTITIES", "UTF-8"
                    );
                }
            }
            foreach($node->getElementsByTagName('description') as $txt_node) {
                if($txt_node->parentNode===$node) {
                    $item['description'] = $txt_node->ownerDocument->saveHTML($txt_node);
                    $item['description'] = mb_convert_encoding(
                        html_entity_decode($item['description'], ENT_NOQUOTES, "UTF-8"), 
                        "HTML-ENTITIES", "UTF-8"
                    );
                }
            }
            if (isset($item['desc'])) {
                if(mb_strlen($item['desc'], "UTF-8")>900) { 
                    $desc_count++; 
                }
                else { 
                    $desc_nocount++; 
                }
            }
            if (isset($item['encoded'])) {
                if(mb_strlen($item['encoded'], "UTF-8")>900) { 
                    $enc_count++; 
                }
                else { 
                    $enc_nocount++; 
                }
            }
        }
        if ($feed_tags['item']=='entry') {
            foreach($node->getElementsByTagName('content') as $txt_node) {
                if($txt_node->parentNode===$node) {
                    $item['content'] = $txt_node->ownerDocument->saveHTML($txt_node);
                    $item['content'] = mb_convert_encoding(
                        html_entity_decode($item['content'], ENT_NOQUOTES, "UTF-8"),
                        "HTML-ENTITIES", "UTF-8"
                    );
                }
            }
            if (isset($item['content'])) {
                if (mb_strlen($item['content'], "UTF-8")>900) { 
                    $desc_count++; 
                }
                else { 
                    $desc_nocount++; 
                }
            }
        }
        if ($item['title'] != "" && $item['link'] != "") { 
            array_push($rss_data, $item); 
        }
    }
    if ($desc_count > $desc_nocount) {
        $source=($feed_tags['item']=='entry')?('content'):('description');
        $rss_data['feed_text']=$source;
        foreach ($rss_data as $i=>$val){
            $rss_data[$i]['text']=$rss_data[$i][$source];
        }
    }
    else if ($enc_count > $enc_nocount) {
        $rss_data['feed_text']='encoded';
        foreach ($rss_data as $i=>$val){
            $rss_data[$i]['text']=$rss_data[$i]['encoded'];
        }
    }
    /*
    for ($i = 0; $i < count($rss_data); $i++){
        unset($rss_data[$i]['encoded']);
        unset($rss_data[$i]['description']);
        unset($rss_data[$i]['content']);
    }*/
    $rss_data['feed_title']=$rss->getElementsByTagName('title')->item(0)->nodeValue;
    if ($feed_tags['item']=='entry') {
        $rss->getElementsByTagName('feed')->item(0)->getAttribute('lang');
    } else {
        $rss->getElementsByTagName('language')->item(0)->nodeValue; 
    }
    return $rss_data;
}

// -------------------------------------------------------------

function get_links_from_rss($NfSourceURI,$NfArticleSection)
{
    $rss = new DOMDocument('1.0', 'utf-8');
    if(!$rss->load($NfSourceURI, LIBXML_NOCDATA | ENT_NOQUOTES)) { 
        return false; 
    }
    $rss_data = array();
    if($rss->getElementsByTagName('rss')->length !== 0) {$feed_tags=array('item' => 'item','title' => 'title','description' => 'description','link' => 'link','pubDate' => 'pubDate','enclosure' => 'enclosure','url' => 'url');
    }
    elseif($rss->getElementsByTagName('feed')->length !== 0) {$feed_tags=array('item' => 'entry','title' => 'title','description' => 'summary','link' => 'link','pubDate' => 'published','enclosure' => 'link','url' => 'href');
    }
    else { return false; 
    }
    foreach ($rss->getElementsByTagName($feed_tags['item']) as $node) {
        $item = array (
        'title' => preg_replace(array('/\s\s+/','/\ \&\ /'), array(' ',' &amp; '), trim($node->getElementsByTagName($feed_tags['title'])->item(0)->nodeValue)),
        'desc' => isset($node->getElementsByTagName($feed_tags['description'])->item(0)->nodeValue)?preg_replace(array('/\ \&\ /','/<br(\s+)?\/?>/i','/<br [^>]*?>/i','/\<[^\>]*\>/','/(\n)[\s^\n]*\n[\s]*/'), array(' &amp; ',"\n","\n",'','$1$1'), trim($node->getElementsByTagName($feed_tags['description'])->item(0)->nodeValue)):'',
        'link' => trim(($feed_tags['item']=='entry')?($node->getElementsByTagName($feed_tags['link'])->item(0)->getAttribute('href')):($node->getElementsByTagName($feed_tags['link'])->item(0)->nodeValue)),
        'date' => isset($node->getElementsByTagName($feed_tags['pubDate'])->item(0)->nodeValue)?trim($node->getElementsByTagName($feed_tags['pubDate'])->item(0)->nodeValue):null,
        );
        $pubDate = date_parse_from_format('D, d M Y H:i:s T', $item['date']);
        if($pubDate['error_count']>0) {
            $item['date'] = date("Y-m-d H:i:s", time()-count($rss_data));
        }
        else{
            $item['date'] = date("Y-m-d H:i:s", mktime($pubDate['hour'], $pubDate['minute'], $pubDate['second'], $pubDate['month'], $pubDate['day'], $pubDate['year']));
        }
        if(strlen($item['desc'])>1000) { $item['desc']=mb_substr($item['desc'], 0, 995, "utf-8") . '...'; 
        }
        if ($NfArticleSection) {
            foreach ($node->getElementsByTagName($NfArticleSection) as $txt_node) {
                if($txt_node->parentNode===$node) {
                    $item['text'] = $txt_node->ownerDocument->saveHTML($txt_node);
                    $item['text']=mb_convert_encoding(html_entity_decode($item['text'], ENT_NOQUOTES, "UTF-8"), "HTML-ENTITIES", "UTF-8");
                    //$item['text']=str_replace ('"','\"',$item['text']);///////////////
                }
            }
        }
        $item['audio'] = "";
        foreach($node->getElementsByTagName($feed_tags['enclosure']) as $enc){
            $type=$enc->getAttribute('type');
            if($type=="audio/mpeg") { $item['audio']=$enc->getAttribute($feed_tags['url']); 
            }
        }
        if($item['title']!="" && ($item['link']!="" || ($NfArticleSection!="" && !empty($item['text'])))) { array_push($rss_data, $item); 
        }
    }
    return $rss_data;
}

// -------------------------------------------------------------

function get_text_from_rsslink($feed_data,$NfArticleSection,$NfFilterTags,$NfCharset=null)
{
    global $tbpref;
    foreach ($feed_data as $key =>$val){
        if(strncmp($NfArticleSection, 'redirect:', 9)==0) {    
            $dom = new DOMDocument;
            $HTMLString = file_get_contents(trim($feed_data[$key]['link']));
            $dom->loadHTML($HTMLString);
            $xPath = new DOMXPath($dom);
            $redirect = explode(" | ", $NfArticleSection, 2);
            $NfArticleSection=$redirect[1];
            $redirect = substr($redirect[0], 9);
            $feed_host = parse_url(trim($feed_data[$key]['link']));
            foreach($xPath->query($redirect) as $node){
                $len=$node->attributes->length;
                for($i=0;$i<$len;$i++){
                    if($node->attributes->item($i)->name=='href') {
                        $feed_data[$key]['link'] = $node->attributes->item($i)->value;
                        if(strncmp($feed_data[$key]['link'], '..', 2)==0) {
                            $feed_data[$key]['link'] = 'http://'.$feed_host['host'] . substr($feed_data[$key]['link'], 2);
                        }
                    }
                }    
            }
            unset($dom);
            unset($HTMLString);
            unset($xPath);
        }
        $data[$key]['TxTitle'] = $feed_data[$key]['title'];
        $data[$key]['TxAudioURI'] = isset($feed_data[$key]['audio'])?$feed_data[$key]['audio']:(null);
        $data[$key]['TxText'] = "";
        if(isset($feed_data[$key]['text'])) {
            if($feed_data[$key]['text']=="") {
                unset($feed_data[$key]['text']);
            }
        }
        if(isset($feed_data[$key]['text'])) {
            $link = trim($feed_data[$key]['link']);
            if(substr($link, 0, 1)=='#') {
                runsql('UPDATE ' . $tbpref . 'feedlinks SET FlLink=' . convert_string_to_sqlsyntax($link) . ' where FlID = ' .substr($link, 1), "");
            }
            $data[$key]['TxSourceURI'] = $link;
            $HTMLString=str_replace(array('>','<'), array('> ',' <'), $feed_data[$key]['text']);//$HTMLString=str_replace (array('>','<'),array('> ',' <'),$HTMLString);
        }
        else{
            $data[$key]['TxSourceURI'] = $feed_data[$key]['link'];
            $context = stream_context_create(array('http' => array('follow_location' => true )));
            $HTMLString = file_get_contents(trim($data[$key]['TxSourceURI']), false, $context);
            if(!empty($HTMLString)) {
                $encod  = '';
                if(empty($NfCharset)) {
                    
                    $header=get_headers(trim($data[$key]['TxSourceURI']), 1);
                    foreach($header as $k=>$v){
                        if(strtolower($k)=='content-type') {
                            if(is_array($v)) {
                                $encod=$v[count($v)-1];
                            }
                            else{
                                $encod=$v;
                            }
                            $pos = strpos($encod, 'charset=');
                            if(($pos!==false) && (strpos($encod, 'text/html;')!==false)) {
                                $encod=substr($encod, $pos+8);    
                                break;
                            }
                            else { $encod=''; 
                            }
                        }
                        
                    }
                }
                else{
                    if($NfCharset!='meta') { $encod  = $NfCharset; 
                    }
                }
                
                if(empty($encod)) {
                    $doc = new DomDocument;
                    $previous_value = libxml_use_internal_errors(true);
                    $doc->loadHTML($HTMLString);
                    /*
                    if (!$doc->loadHTML($HTMLString)) {
                    foreach (libxml_get_errors() as $error) {
                    // handle errors here
                    }*/
                    libxml_clear_errors();
                    libxml_use_internal_errors($previous_value);
                    $nodes=$doc->getElementsByTagName('meta');
                    foreach($nodes as $node){
                        $len=$node->attributes->length;
                        for($i=0;$i<$len;$i++){
                            if($node->attributes->item($i)->name=='content') {
                                $pos = strpos($node->attributes->item($i)->value, 'charset=');
                                if($pos) {
                                    $encod=substr($node->attributes->item($i)->value, $pos+8);
                                    unset($doc);
                                    unset($nodes);
                                    break 2;    
                                }
                            }
                        }    
                    }
                    if(empty($encod)) {
                        foreach($nodes as $node){
                            $len=$node->attributes->length;
                            if($len=='1') {
                                if($node->attributes->item(0)->name=='charset') {

                                    $encod=$node->attributes->item(0)->value;
                                    break;    
                                }
                            }
                        }    
                    }
                }
                unset($doc);
                unset($nodes);
                if(empty($encod)) {
                    mb_detect_order("ASCII,UTF-8,ISO-8859-1,windows-1252,iso-8859-15");
                    $encod  = mb_detect_encoding($HTMLString);
                }
                $chset=$encod;
                switch($encod){
                case 'windows-1253':
                    $chset='el_GR.utf8';
                    break;
                case 'windows-1254':
                    $chset='tr_TR.utf8';
                    break;
                case 'windows-1255':
                    $chset='he.utf8';
                    break;
                case 'windows-1256':
                    $chset='ar_AE.utf8';
                    break;
                case 'windows-1258':
                    $chset='vi_VI.utf8';
                    break;
                case 'windows-874':
                    $chset='th_TH.utf8';
                    break;
                }
                $HTMLString = '<meta http-equiv="Content-Type" content="text/html; charset='. $chset .'">' .$HTMLString;
                if($encod!=$chset) { $HTMLString = iconv($encod, 'utf-8', $HTMLString); 
                }
                else { $HTMLString=mb_convert_encoding($HTMLString, 'HTML-ENTITIES', $encod); 
                }
            }
        }
        $HTMLString=str_replace(array('<br />','<br>','</br>','</h','</p'), array("\n","\n","","\n</h","\n</p"), $HTMLString);
        $dom = new DOMDocument();
        $previous_value = libxml_use_internal_errors(true);

        $dom->loadHTML('<?xml encoding="UTF-8">' . $HTMLString);
        foreach ($dom->childNodes as $item){/////////////////////////////////
            if ($item->nodeType == XML_PI_NODE) {
                $dom->removeChild($item); // remove hack
            }
        }
        $dom->encoding = 'UTF-8'; // insert proper    //////////////////////////////

        /*
        if (!$dom->loadHTML($HTMLString)) {
        foreach (libxml_get_errors() as $error) {
        // handle errors here
        }*/
        libxml_clear_errors();
        libxml_use_internal_errors($previous_value);
        $filter_tags = explode("!?!", rtrim("//img | //script | //meta | //noscript | //link | //iframe!?!".$NfFilterTags, "!?!"));
        foreach (explode("!?!", $NfArticleSection) as $article_tag) {
            if($article_tag=='new') {
                foreach ($filter_tags as $filter_tag){
                    $nodes=$dom->getElementsByTagName($filter_tag);
                    $domElemsToRemove = array();
                    foreach ( $nodes as $domElement ) {
                        $domElemsToRemove[] = $domElement;
                    }
                    foreach ($domElemsToRemove as $node) {
                        $node->parentNode->removeChild($node);
                    }
                }
                $nodes=$dom->getElementsByTagName('*');
                foreach ( $nodes as $node ) {
                    $node->removeAttribute('onclick');
                }
                $str=$dom->saveHTML($dom);
                //$str=mb_convert_encoding(html_entity_decode($str, ENT_NOQUOTES, "UTF-8"),"HTML-ENTITIES","UTF-8");
                return preg_replace(array('/\<html[^\>]*\>/','/\<body\>/'), array('',''), $str);
            }
        }
        $selector = new DOMXPath($dom);
        foreach ($filter_tags as $filter_tag){
            foreach ($selector->query($filter_tag) as $node) {
                $node->parentNode->removeChild($node);
            }
        }
        if(isset($feed_data[$key]['text'])) {
            foreach ($selector->query($NfArticleSection) as $text_temp) {
                if(isset($text_temp->nodeValue)) {
                    $data[$key]['TxText'] .= mb_convert_encoding($text_temp->nodeValue, "HTML-ENTITIES", "UTF-8");
                }
            }
            $data[$key]['TxText'] = html_entity_decode($data[$key]['TxText'], ENT_NOQUOTES, "UTF-8");
        }        
        else{
            $article_tags = explode("!?!", $NfArticleSection);if(strncmp($NfArticleSection, 'redirect:', 9)==0) { unset($article_tags[0]); 
            }
            foreach ($article_tags as $article_tag) {
                foreach ($selector->query($article_tag) as $text_temp) {
                    if(isset($text_temp->nodeValue)) {
                        $data[$key]['TxText'].= $text_temp->nodeValue;
                    }
                }
            }
        }        
                
        if($data[$key]['TxText']=="") {
            unset($data[$key]);
            if(!isset($data['error']['message'])) { $data['error']['message']=''; 
            }
            $data['error']['message'].= '"<a href=' . $feed_data[$key]['link'] .' onclick="window.open(this.href, \'child\'); return false">'  . $feed_data[$key]['title'] . '</a>" has no text section!<br />';
            $data['error']['link'][]=$feed_data[$key]['link'];
        }
        else{
            $data[$key]['TxText']=trim(preg_replace(array('/[\r\t]+/','/(\n)[\s^\n]*\n[\s]*/','/\ \ +/'), array(' ','$1$1',' '), $data[$key]['TxText']));
            //$data[$key]['TxText']=trim(preg_replace(array('/[\s^\n]+/','/(\n)[\s^\n]*\n[\s]*/','/\ +/','/[ ]*(\n)/'), array(' ','$1$1',' ','$1'), $data[$key]['TxText']));
        }
    }
    return $data;
}


// -------------------------------------------------------------

function stripTheSlashesIfNeeded($s) 
{
    if (function_exists("get_magic_quotes_gpc")) {
        if (get_magic_quotes_gpc()) {
            return stripslashes($s); 
        }
        else { 
            return $s; 
        }
    } else {
        return $s;
    }
}

/**
 * Return navigation arrows to previous and next texts.
 * 
 * @param  string $textid  ID of the current text
 * @param  string $url     Base URL to append before $textid
 * @param  bool   $onlyann Restrict to annotated texts only
 * @param  string $add     Some content to add before the output
 * @return string Arrows to previous and next texts.
 */
function getPreviousAndNextTextLinks($textid, $url, $onlyann, $add) 
{
    global $tbpref;
    $currentlang = validateLang(
        processDBParam("filterlang", 'currentlanguage', '', 0)
    );
    $wh_lang = '';
    if ($currentlang != '') {
        $wh_lang = ' AND TxLgID=' . $currentlang;
    }

    $currentquery = processSessParam("query", "currenttextquery", '', 0);
    $currentquerymode = processSessParam(
        "query_mode", "currenttextquerymode", 'title,text', 0
    );
    $currentregexmode = getSettingWithDefault("set-regex-mode");
    $wh_query = $currentregexmode . 'LIKE ';
    if ($currentregexmode == '') {
        $wh_query .= convert_string_to_sqlsyntax(
            str_replace("*", "%", mb_strtolower($currentquery, 'UTF-8'))
        );
    } else {
        $wh_query .= convert_string_to_sqlsyntax($currentquery);
    }
    switch ($currentquerymode) {
    case 'title,text':
        $wh_query=' AND (TxTitle ' . $wh_query . ' OR TxText ' . $wh_query . ')';
        break;
    case 'title':
        $wh_query=' AND (TxTitle ' . $wh_query . ')';
        break;
    case 'text':
        $wh_query=' AND (TxText ' . $wh_query . ')';
        break;
    }
    if ($currentquery=='') { 
        $wh_query = ''; 
    }

    $currenttag1 = validateTextTag(
        processSessParam("tag1", "currenttexttag1", '', 0), 
        $currentlang
    );
    $currenttag2 = validateTextTag(
        processSessParam("tag2", "currenttexttag2", '', 0), 
        $currentlang
    );
    $currenttag12 = processSessParam("tag12", "currenttexttag12", '', 0);
    if ($currenttag1 == '' && $currenttag2 == '') {
        $wh_tag = ''; 
    }
    else {
        if ($currenttag1 != '') {
            if ($currenttag1 == -1) {
                $wh_tag1 = "group_concat(TtT2ID) IS NULL"; 
            }
            else {
                $wh_tag1 = "concat('/',group_concat(TtT2ID separator '/'),'/') like '%/" . $currenttag1 . "/%'"; 
            }
        }
        if ($currenttag2 != '') {
            if ($currenttag2 == -1) {
                $wh_tag2 = "group_concat(TtT2ID) IS NULL"; 
            }
            else {
                $wh_tag2 = "concat('/',group_concat(TtT2ID separator '/'),'/') like '%/" . $currenttag2 . "/%'"; 
            }
        }
        if ($currenttag1 != '' && $currenttag2 == '') {    
            $wh_tag = " having (" . $wh_tag1 . ') '; 
        }
        elseif ($currenttag2 != '' && $currenttag1 == '') {    
            $wh_tag = " having (" . $wh_tag2 . ') ';
        } else {
            $wh_tag = " having ((" . $wh_tag1 . ($currenttag12 ? ') AND (' : ') OR (') . $wh_tag2 . ')) '; 
        }
    }

    $currentsort = processDBParam("sort", 'currenttextsort', '1', 1);
    $sorts = array('TxTitle','TxID desc','TxID asc');
    $lsorts = count($sorts);
    if ($currentsort < 1) { 
        $currentsort = 1; 
    }
    if ($currentsort > $lsorts) { 
        $currentsort = $lsorts; 
    }

    if ($onlyann) { 
        $sql = 
        'SELECT TxID 
        FROM (
            (' . $tbpref . 'texts 
                LEFT JOIN ' . $tbpref . 'texttags ON TxID = TtTxID
            ) 
            LEFT JOIN ' . $tbpref . 'tags2 ON T2ID = TtT2ID
        ), ' . $tbpref . 'languages 
        WHERE LgID = TxLgID AND LENGTH(TxAnnotatedText) > 0 ' 
        . $wh_lang . $wh_query . ' 
        GROUP BY TxID ' . $wh_tag . ' 
        ORDER BY ' . $sorts[$currentsort-1]; 
    }
    else {
        $sql = 
        'SELECT TxID 
        FROM (
            (' . $tbpref . 'texts 
                LEFT JOIN ' . $tbpref . 'texttags ON TxID = TtTxID
            ) 
            LEFT JOIN ' . $tbpref . 'tags2 ON T2ID = TtT2ID
        ), ' . $tbpref . 'languages 
        WHERE LgID = TxLgID ' . $wh_lang . $wh_query . ' 
        GROUP BY TxID ' . $wh_tag . ' 
        ORDER BY ' . $sorts[$currentsort-1]; 
    }

    $list = array(0);
    $res = do_mysqli_query($sql);
    while ($record = mysqli_fetch_assoc($res)) {
        array_push($list, ($record['TxID']+0));
    }
    mysqli_free_result($res);
    array_push($list, 0);
    $listlen = count($list);
    for ($i=1; $i < $listlen-1; $i++) {
        if($list[$i] == $textid) {
            if ($list[$i-1] !== 0) {
                $title = tohtml(getTextTitle($list[$i-1]));
                $prev = '<a href="' . $url . $list[$i-1] . '" target="_top"><img src="icn/navigation-180-button.png" title="Previous Text: ' . $title . '" alt="Previous Text: ' . $title . '" /></a>';
            }
            else {
                $prev = '<img src="icn/navigation-180-button-light.png" title="No Previous Text" alt="No Previous Text" />'; 
            }
            if ($list[$i+1] !== 0) {
                $title = tohtml(getTextTitle($list[$i+1]));
                $next = '<a href="' . $url . $list[$i+1] . '" target="_top"><img src="icn/navigation-000-button.png" title="Next Text: ' . $title . '" alt="Next Text: ' . $title . '" /></a>';
            }
            else {
                $next = '<img src="icn/navigation-000-button-light.png" title="No Next Text" alt="No Next Text" />'; 
            }
            return $add . $prev . ' ' . $next;
        }
    }
    return $add . '<img src="icn/navigation-180-button-light.png" title="No Previous Text" alt="No Previous Text" /> <img src="icn/navigation-000-button-light.png" title="No Next Text" alt="No Next Text" />';
}


// -------------------------------------------------------------

function url_base() 
{
    $url = parse_url("http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
    $r = $url["scheme"] . "://" . $url["host"];
    if (isset($url["port"])) { 
        $r .= ":" . $url["port"]; 
    }
    if(isset($url["path"])) {
        $b = basename($url["path"]);
        if (substr($b, -4) == ".php" || substr($b, -4) == ".htm" || substr($b, -5) == ".html") { 
            $r .= dirname($url["path"]); 
        }
        else {
            $r .= $url["path"]; 
        }
    }
    if (substr($r, -1) !== "/") { 
        $r .= "/"; 
    }
    return $r;
}


/**
 * Return an HTML formatted logo of the application.
 * 
 * @global string $tbref Table name prefix (optional)
 */
function echo_lwt_logo() 
{
    global $tbpref;
    $pref = substr($tbpref, 0, -1);
    if ($pref == '') { 
        $pref = 'Default Table Set'; 
    }
    echo '<img class="lwtlogo" src="';
    print_file_path('img/lwt_icon.png'); 
    echo '" title="LWT - Current Table Set: ' . tohtml($pref) . 
    '" alt="LWT - Current Table Set: ' . tohtml($pref) . '" />';
}

// -------------------------------------------------------------

function get_execution_time()
{
    static $microtime_start = null;
    if($microtime_start === null) {
        $microtime_start = microtime(true);
        return 0.0;
    }
    return microtime(true) - $microtime_start;
}

// -------------------------------------------------------------

function getprefixes() 
{
    $prefix = array();
    $res = do_mysqli_query(str_replace('_', "\\_", "SHOW TABLES LIKE " . convert_string_to_sqlsyntax_nonull('%_settings')));
    while ($row = mysqli_fetch_row($res)) {
        $prefix[] = substr($row[0], 0, -9); 
    }
    mysqli_free_result($res);
    return $prefix;
}

// -------------------------------------------------------------

function selectmediapath($f) 
{
    $exists = file_exists('media');
    if ($exists) {
        if (is_dir('media')) { $msg = ''; 
        }
        else { $msg = '<br />[Error: ".../' . basename(getcwd()) . '/media" exists, but it is not a directory.]'; 
        }
    } else {
        $msg = '<br />[Directory ".../' . basename(getcwd()) . '/media" does not yet exist.]';
    }
    $r = '<br /> or choose a file in ".../' . basename(getcwd()) . '/media" (only mp3, ogg, wav files shown): ' . $msg;
    if ($msg == '') {
        $r .= '<br /><select name="Dir" onchange="{val=this.form.Dir.options[this.form.Dir.selectedIndex].value; if (val != \'\') this.form.' . $f . '.value = val; this.form.Dir.value=\'\';}">';
        $r .= '<option value="">[Choose...]</option>';
        $r .= selectmediapathoptions('media');
        $r .= '</select> ';
    }
    $r .= ' &nbsp; &nbsp; <span class="click" onclick="do_ajax_update_media_select();"><img src="icn/arrow-circle-135.png" title="Refresh Media Selection" alt="Refresh Media Selection" /> Refresh</span>';
    return $r;
}

// -------------------------------------------------------------

function selectmediapathoptions($dir) 
{
    $is_windows = ("WIN" == strtoupper(substr(PHP_OS, 0, 3)));
    $mediadir = scandir($dir);
    $r = '<option disabled="disabled">-- Directory: ' . tohtml($dir) . ' --</option>';
    foreach ($mediadir as $entry) {
        if ($is_windows) { $entry = mb_convert_encoding($entry, 'UTF-8', 'ISO-8859-1'); 
        }
        if (substr($entry, 0, 1) != '.') {
            if (! is_dir($dir . '/' . $entry)) {
                $ex = substr($entry, -4);
                if ((strcasecmp($ex, '.mp3') == 0) 
                    || (strcasecmp($ex, '.ogg') == 0) 
                    || (strcasecmp($ex, '.wav') == 0)
                ) {
                    $r .= '<option value="' . tohtml($dir . '/' . $entry) . '">' . tohtml($dir . '/' . $entry) . '</option>'; 
                }
            }
        }
    }
    foreach ($mediadir as $entry) {
        if (substr($entry, 0, 1) != '.') {
            if (is_dir($dir . '/' . $entry)) { $r .= selectmediapathoptions($dir . '/' . $entry); 
            }
        }
    }
    return $r;
}

// -------------------------------------------------------------

function get_seconds_selectoptions($v) 
{
    if (! isset($v) ) { $v = 5; 
    }
    $r = '';
    for ($i=1; $i <= 10; $i++) {
        $r .= "<option value=\"" . $i . "\"" . get_selected($v, $i);
        $r .= ">" . $i . " sec</option>";
    }
    return $r;
}

// -------------------------------------------------------------

function get_playbackrate_selectoptions($v) 
{
    if (! isset($v) ) { $v = '10'; 
    }
    $r = '';
    for ($i=5; $i <= 15; $i++) {
        $text = ($i<10 ? (' 0.' . $i . ' x ') : (' 1.' . ($i-10) . ' x ') ); 
        $r .= "<option value=\"" . $i . "\"" . get_selected($v, $i);
        $r .= ">&nbsp;" . $text . "&nbsp;</option>";
    }
    return $r;
}

// -------------------------------------------------------------

function error_message_with_hide($msg,$noback) 
{
    if (trim($msg) == '') { return ''; 
    }
    if (substr($msg, 0, 5) == "Error" ) {
        return '<p class="red">*** ' . tohtml($msg) . ' ***' . 
        ($noback ? 
        '' : 
        '<br /><input type="button" value="&lt;&lt; Go back and correct &lt;&lt;" onclick="history.back();" />' ) . 
        '</p>'; 
    }
    else {
        return '<p id="hide3" class="msgblue">+++ ' . tohtml($msg) . ' +++</p>'; 
    }
}

// -------------------------------------------------------------

function errorbutton($msg) 
{
    if (substr($msg, 0, 5) == "Error" ) {
        return '<input type="button" value="&lt;&lt; Back" onclick="history.back();" />'; 
    }
    else {
        return ''; 
    }
} 

// -------------------------------------------------------------

function optimizedb() 
{
    global $tbpref;
    adjust_autoincr('archivedtexts', 'AtID');
    adjust_autoincr('languages', 'LgID');
    adjust_autoincr('sentences', 'SeID');
    adjust_autoincr('texts', 'TxID');
    adjust_autoincr('words', 'WoID');
    adjust_autoincr('tags', 'TgID');
    adjust_autoincr('tags2', 'T2ID');
    adjust_autoincr('newsfeeds', 'NfID');
    adjust_autoincr('feedlinks', 'FlID');
    $sql = 
    'SHOW TABLE STATUS 
    WHERE Engine IN ("MyISAM","Aria") AND ((Data_free / Data_length > 0.1 AND Data_free > 102400) OR Data_free > 1048576) AND Name';
    if(empty($tbpref)) { 
        $sql.= " NOT LIKE '\_%'"; 
    }
    else { 
        $sql.= " LIKE " . convert_string_to_sqlsyntax(rtrim($tbpref, '_')) . "'\_%'"; 
    }
    $res = do_mysqli_query($sql);
    while($row = mysqli_fetch_assoc($res)) {
        runsql('OPTIMIZE TABLE ' . $row['Name'], '');
    }
    mysqli_free_result($res);
}

// -------------------------------------------------------------

function remove_soft_hyphens($str) 
{
    return str_replace('­', '', $str);  // first '..' contains Softhyphen 0xC2 0xAD
}

// -------------------------------------------------------------

function limitlength($s, $l) 
{
    if (mb_strlen($s, 'UTF-8') <= $l) { return $s; 
    }
    return mb_substr($s, 0, $l, 'UTF-8');
}

// -------------------------------------------------------------

function adjust_autoincr($table,$key) 
{
    global $tbpref;
    $val = get_first_value('select max(' . $key .')+1 as value from ' . $tbpref .  $table);
    if (! isset($val)) { $val = 1; 
    }
    $sql = 'alter table ' . $tbpref . $table . ' AUTO_INCREMENT = ' . $val;
    $res = do_mysqli_query($sql);
}

// -------------------------------------------------------------

function replace_supp_unicode_planes_char($s) 
{
    return preg_replace('/[\x{10000}-\x{10FFFF}]/u', "\xE2\x96\x88", $s); 
    /* U+2588 = UTF8: E2 96 88 = FULL BLOCK = ⬛︎  */ 
}

// -------------------------------------------------------------

function makeCounterWithTotal($max, $num) 
{
    if ($max == 1) { return ''; 
    }
    if ($max < 10) { return $num . "/" . $max; 
    }
    return substr(
        str_repeat("0", strlen($max)) . $num,
        -strlen($max)
    )  . 
    "/" . $max;
}

// -------------------------------------------------------------

function encodeURI($url) 
{
    $reserved = array(
    '%2D'=>'-','%5F'=>'_','%2E'=>'.','%21'=>'!', 
    '%2A'=>'*', '%27'=>"'", '%28'=>'(', '%29'=>')'
    );
    $unescaped = array(
    '%3B'=>';','%2C'=>',','%2F'=>'/','%3F'=>'?','%3A'=>':',
    '%40'=>'@','%26'=>'&','%3D'=>'=','%2B'=>'+','%24'=>'$'
    );
    $score = array(
    '%23'=>'#'
    );
    return strtr(rawurlencode($url), array_merge($reserved, $unescaped, $score));
}
 
// -------------------------------------------------------------

function showRequest() 
{
    $olderr = error_reporting(0);
    echo "<pre>** DEBUGGING **********************************\n";
    echo '$GLOBALS...'; print_r($GLOBALS);
    echo 'get_version_number()...'; echo get_version_number() . "\n";
    echo 'get_magic_quotes_gpc()...'; 
    if (function_exists("get_magic_quotes_gpc")) {
        echo (get_magic_quotes_gpc() ? "TRUE" : "FALSE") . "\n";
    } else {
        echo "NOT EXISTS (FALSE)\n";
    }
    echo "********************************** DEBUGGING **</pre>";
    error_reporting($olderr);
}

// -------------------------------------------------------------

function remove_spaces($s, $remove) 
{
    if ($remove) { 
        return str_replace(' ', '', $s);  // '' enthält &#x200B;
    }    else {
        return $s; 
    }
}


// -------------------------------------------------------------

function get_sepas() 
{
    static $sepa;
    if (!$sepa) {
        $sepa = preg_quote(getSettingWithDefault('set-term-translation-delimiters'), '/');
    }
    return $sepa;
}

// -------------------------------------------------------------

function get_first_sepa() 
{
    static $sepa;
    if (!$sepa) {
        $sepa = mb_substr(
            getSettingWithDefault('set-term-translation-delimiters'),
            0, 1, 'UTF-8'
        );
    }
    return $sepa;
}

/** 
 * Convert a setting to 0 or 1
 *
 * @param  string $key The input value
 * @param  string $dft Default value to use
 * @return int 0 or 1
 */
function getSettingZeroOrOne($key, $dft) 
{
    $r = getSetting($key);
    $r = ($r == '' ? $dft : ((((int) $r) !== 0) ? 1 : 0));
    return $r;
}

/**
 * Get a setting from the database. It can also check for its validity.
 * 
 * @param  string $key Setting key. If $key is 'currentlanguage' or 
 *                     'currenttext', we validate language/text.
 * @return string $val Value in the database if found, or an empty string
 * @global string $tbpref Table name prefix
 */
function getSetting($key) 
{
    global $tbpref;
    $val = get_first_value(
        'SELECT StValue AS value 
        FROM ' . $tbpref . 'settings 
        WHERE StKey = ' . convert_string_to_sqlsyntax($key)
    );
    if (isset($val)) {
        $val = trim($val);
        if ($key == 'currentlanguage' ) { 
            $val = validateLang($val); 
        }
        if ($key == 'currenttext' ) { 
            $val = validateText($val); 
        }
        return $val;
    }
    else { 
        return ''; 
    }
}

/**
 * Get the settings value for a specific key. Return a default value when possible
 * 
 * @param  string $key Settings key
 * @return string Requested setting, or default value, or ''
 * @global string $tbpref Table name prefix
 */
function getSettingWithDefault($key) 
{
    global $tbpref;
    $dft = get_setting_data();
    $val = get_first_value(
        'SELECT StValue AS value
         FROM ' . $tbpref . 'settings
         WHERE StKey = ' . convert_string_to_sqlsyntax($key)
    );
    if (isset($val) && $val != '') {
        return trim($val); 
    }
    if (array_key_exists($key, $dft)) { 
        return $dft[$key]['dft']; 
    }
    return '';
    
}

// -------------------------------------------------------------

function get_mobile_display_mode_selectoptions($v) 
{
    if (!isset($v)) { 
        $v = "0"; 
    }
    $r  = "<option value=\"0\"" . get_selected($v, "0");
    $r .= ">Auto</option>";
    $r .= "<option value=\"1\"" . get_selected($v, "1");
    $r .= ">Force Non-Mobile</option>";
    $r .= "<option value=\"2\"" . get_selected($v, "2");
    $r .= ">Force Mobile</option>";
    return $r;
}

// -------------------------------------------------------------

function get_sentence_count_selectoptions($v) 
{
    if (!isset($v)) {
        $v = 1; 
    }
    $r  = "<option value=\"1\"" . get_selected($v, 1);
    $r .= ">Just ONE</option>";
    $r .= "<option value=\"2\"" . get_selected($v, 2);
    $r .= ">TWO (+previous)</option>";
    $r .= "<option value=\"3\"" . get_selected($v, 3);
    $r .= ">THREE (+previous,+next)</option>";
    return $r;
}

// -------------------------------------------------------------

function get_words_to_do_buttons_selectoptions($v) 
{
    if (!isset($v)) {
        $v = "1"; 
    }
    $r  = "<option value=\"0\"" . get_selected($v, "0");
    $r .= ">I Know All &amp; Ignore All</option>";
    $r .= "<option value=\"1\"" . get_selected($v, "1");
    $r .= ">I Know All</option>";
    $r .= "<option value=\"2\"" . get_selected($v, "2");
    $r .= ">Ignore All</option>";
    return $r;
}

// -------------------------------------------------------------

function get_regex_selectoptions($v) 
{
    if (!isset($v)) {
        $v = ""; 
    }
    $r  = "<option value=\"\"" . get_selected($v, "");
    $r .= ">Default</option>";
    $r .= "<option value=\"r\"" . get_selected($v, "r");
    $r .= ">RegEx</option>";
    $r .= "<option value=\"COLLATE 'utf8_bin' r\"" . get_selected($v, "COLLATE 'utf8_bin' r");
    $r .= ">RegEx CaseSensitive</option>";
    return $r;
}

// -------------------------------------------------------------

function get_tooltip_selectoptions($v) 
{
    if (!isset($v)) {
        $v = 1; 
    }
    $r  = "<option value=\"1\"" . get_selected($v, 1);
    $r .= ">Native</option>";
    $r .= "<option value=\"2\"" . get_selected($v, 2);
    $r .= ">JqueryUI</option>";
    return $r;
}

// -------------------------------------------------------------

function get_themes_selectoptions($v)
{
    $themes = glob('themes/*', GLOB_ONLYDIR);
    $r = '<option value="themes/Default/">Default</option>';
    foreach($themes as $theme){
        if($theme!='themes/Default') {
            $r.= '<option value="'.$theme.'/" '. get_selected($v, $theme.'/');
            $r .= ">". str_replace(array('themes/','_'), array('',' '), $theme) ."</option>";
        }
    }
    return $r;
}

/**
 * Save the setting identified by a key with a specific value.
 * 
 * @param string $k Setting key
 * @param mixed  $v Setting value, will get converted to string
 * 
 * @global string $tbpref Table name prefix
 * 
 * @return string Error or success message
 */
function saveSetting($k, $v) 
{
    global $tbpref;
    $dft = get_setting_data();
    if (!isset($v)) {
        return ''; 
    }
    $v = stripTheSlashesIfNeeded($v);
    if ($v === '') {
        return '';
    }
    runsql(
        'DELETE FROM ' . $tbpref . 'settings 
        WHERE StKey = ' . convert_string_to_sqlsyntax($k), 
        ''
    );
    if (array_key_exists($k, $dft) && $dft[$k]['num']) {
        $v = (int)$v;
        if ($v < $dft[$k]['min']) { 
            $v = $dft[$k]['dft']; 
        }
        if ($v > $dft[$k]['max']) { 
            $v = $dft[$k]['dft']; 
        }
    }
    $dum = runsql(
        'INSERT INTO ' . $tbpref . 'settings (StKey, StValue) values(' .
        convert_string_to_sqlsyntax($k) . ', ' . 
        convert_string_to_sqlsyntax($v) . ')', 
        ''
    );
    return $dum;
}

// -------------------------------------------------------------

function processSessParam($reqkey,$sesskey,$default,$isnum) 
{
    $result = '';
    if(isset($_REQUEST[$reqkey])) {
        $reqdata = stripTheSlashesIfNeeded(trim($_REQUEST[$reqkey]));
        $_SESSION[$sesskey] = $reqdata;
        $result = $reqdata;
    }
    elseif(isset($_SESSION[$sesskey])) {
        $result = $_SESSION[$sesskey];
    }
    else {
        $result = $default;
    }
    if($isnum) {
        $result = (int)$result; 
    }
    return $result;
}

// -------------------------------------------------------------

function processDBParam($reqkey,$dbkey,$default,$isnum) 
{
    $result = '';
    $dbdata = getSetting($dbkey);
    if(isset($_REQUEST[$reqkey])) {
        $reqdata = stripTheSlashesIfNeeded(trim($_REQUEST[$reqkey]));
        saveSetting($dbkey, $reqdata);
        $result = $reqdata;
    }
    elseif($dbdata != '') {
        $result = $dbdata;
    }
    else {
        $result = $default;
    }
    if($isnum) { 
        $result = (int)$result; 
    }
    return $result;
}

// -------------------------------------------------------------

function validateLang($currentlang) 
{
    global $tbpref;
    $sql = 
    'SELECT count(LgID) AS value 
    FROM ' . $tbpref . 'languages 
    WHERE LgID=' . ((int)$currentlang);
    if ($currentlang != '') {
        if (get_first_value($sql) == 0
        ) {  
            $currentlang = ''; 
        } 
    }
    return $currentlang;
}

// -------------------------------------------------------------

function validateText($currenttext) 
{
    global $tbpref;
    if ($currenttext != '') {
        if (get_first_value(
            'select count(TxID) as value from ' . $tbpref . 'texts where TxID=' . 
            ((int)$currenttext) 
        ) == 0
        ) {  
            $currenttext = ''; 
        } 
    }
    return $currenttext;
}

// -------------------------------------------------------------

function validateTag($currenttag,$currentlang) 
{
    global $tbpref;
    if ($currenttag != '' && $currenttag != -1) {
        if ($currentlang == '') {
            $sql = "select (" . $currenttag . " in (select TgID from " . $tbpref . "words, " . $tbpref . "tags, " . $tbpref . "wordtags where TgID = WtTgID and WtWoID = WoID group by TgID order by TgText)) as value"; 
        }
        else {
            $sql = "select (" . $currenttag . " in (select TgID from " . $tbpref . "words, " . $tbpref . "tags, " . $tbpref . "wordtags where TgID = WtTgID and WtWoID = WoID and WoLgID = " . $currentlang . " group by TgID order by TgText)) as value"; 
        }
        $r = get_first_value($sql);
        if ($r == 0 ) { $currenttag = ''; 
        } 
    }
    return $currenttag;
}

// -------------------------------------------------------------

function validateArchTextTag($currenttag,$currentlang) 
{
    global $tbpref;
    if ($currenttag != '' && $currenttag != -1) {
        if ($currentlang == '') {
            $sql = "select (" . $currenttag . " in (select T2ID from " . $tbpref . "archivedtexts, " . $tbpref . "tags2, " . $tbpref . "archtexttags where T2ID = AgT2ID and AgAtID = AtID group by T2ID order by T2Text)) as value"; 
        }
        else {
            $sql = "select (" . $currenttag . " in (select T2ID from " . $tbpref . "archivedtexts, " . $tbpref . "tags2, " . $tbpref . "archtexttags where T2ID = AgT2ID and AgAtID = AtID and AtLgID = " . $currentlang . " group by T2ID order by T2Text)) as value"; 
        }
        $r = get_first_value($sql);
        if ($r == 0 ) { $currenttag = ''; 
        } 
    }
    return $currenttag;
}

// -------------------------------------------------------------

function validateTextTag($currenttag,$currentlang) 
{
    global $tbpref;
    if ($currenttag != '' && $currenttag != -1) {
        if ($currentlang == '') {
            $sql = "select (" . $currenttag . " in (select T2ID from " . $tbpref . "texts, " . $tbpref . "tags2, " . $tbpref . "texttags where T2ID = TtT2ID and TtTxID = TxID group by T2ID order by T2Text)) as value"; 
        }
        else {
            $sql = "select (" . $currenttag . " in (select T2ID from " . $tbpref . "texts, " . $tbpref . "tags2, " . $tbpref . "texttags where T2ID = TtT2ID and TtTxID = TxID and TxLgID = " . $currentlang . " group by T2ID order by T2Text)) as value"; 
        }
        $r = get_first_value($sql);
        if ($r == 0 ) { $currenttag = ''; 
        } 
    }
    return $currenttag;
}

// -------------------------------------------------------------

function getWordTagList($wid, $before=' ', $brack=1, $tohtml=1) 
{
    global $tbpref;
    $r = get_first_value(
        "SELECT IFNULL(" . ($brack ? "CONCAT('['," : "") . 
        "GROUP_CONCAT(DISTINCT TgText ORDER BY TgText separator ', ')" . 
        ($brack ? ",']')" : "") . ",'') as value 
        FROM ((" . $tbpref . "words 
        LEFT JOIN " . $tbpref . "wordtags 
        ON WoID = WtWoID) 
        LEFT JOIN " . $tbpref . "tags 
        ON TgID = WtTgID) 
        WHERE WoID = " . $wid
    );
    if ($r != '') { 
        $r = $before . $r; 
    }
    if ($tohtml) { 
        $r = tohtml($r); 
    }
    return $r;
}

/**
 * Return the last inserted ID in the database
 * 
 * @return string|null
 */
function get_last_key() 
{
    return get_first_value('SELECT LAST_INSERT_ID() AS value');        
}

/**
 * If $value is true, return an HTML-style checked attribute.
 * 
 * @param  mixed $value Some value that can be evaluated as a boolean
 * @return string ' checked="checked" ' if value is true, '' otherwise 
 */
function get_checked($value) 
{
    if (!isset($value)) { 
        return ''; 
    }
    if ($value) { 
        return ' checked="checked" '; 
    }
    return '';
}

// -------------------------------------------------------------

function get_selected($value, $selval) 
{
    if (!isset($value)) { 
        return ''; 
    }
    if ($value == $selval) { 
        return ' selected="selected" '; 
    }
    return '';
}

// -------------------------------------------------------------

function make_status_controls_test_table($score, $status, $wordid) 
{
    if ($score < 0 ) { 
        $scoret = '<span class="red2">' . get_status_abbr($status) . '</span>'; 
    }
    else {
        $scoret = get_status_abbr($status); 
    }
        
    if ($status <= 5 || $status == 98 ) { 
        $plus = '<img src="icn/plus.png" class="click" title="+" alt="+" onclick="changeTableTestStatus(' . $wordid .',true);" />'; 
    }
    else {
        $plus = '<img src="'.get_file_path('icn/placeholder.png').'" title="" alt="" />'; 
    }
    if ($status >= 1 ) { 
        $minus = '<img src="icn/minus.png" class="click" title="-" alt="-" onclick="changeTableTestStatus(' . $wordid .',false);" />'; 
    }
    else {
        $minus = '<img src="'.get_file_path('icn/placeholder.png').'" title="" alt="" />'; 
    }
    return ($status == 98 ? '' : $minus . ' ') . $scoret . ($status == 99 ? '' : ' ' . $plus);
}

// -------------------------------------------------------------

function get_languages_selectoptions($v,$dt) 
{
    global $tbpref;
    $sql = "select LgID, LgName from " . $tbpref . "languages where LgName<>'' order by LgName";
    $res = do_mysqli_query($sql);
    if (! isset($v) || trim($v) == '' ) {
        $r = "<option value=\"\" selected=\"selected\">" . $dt . "</option>";
    } else {
        $r = "<option value=\"\">" . $dt . "</option>";
    }
    while ($record = mysqli_fetch_assoc($res)) {
        $d = $record["LgName"];
        if (strlen($d) > 30 ) { $d = substr($d, 0, 30) . "..."; 
        }
        $r .= "<option value=\"" . $record["LgID"] . "\" " . get_selected($v, $record["LgID"]);
        $r .= ">" . tohtml($d) . "</option>";
    }
    mysqli_free_result($res);
    return $r;
}

// -------------------------------------------------------------

function get_languagessize_selectoptions($v) 
{
    if (! isset($v) ) { $v = 100; 
    }
    $r = "<option value=\"100\"" . get_selected($v, 100);
    $r .= ">100 %</option>";
    $r .= "<option value=\"150\"" . get_selected($v, 150);
    $r .= ">150 %</option>";
    $r .= "<option value=\"200\"" . get_selected($v, 200);
    $r .= ">200 %</option>";
    $r .= "<option value=\"250\"" . get_selected($v, 250);
    $r .= ">250 %</option>";
    return $r;
}

// -------------------------------------------------------------

function get_wordstatus_radiooptions($v) 
{
    if (! isset($v) ) { $v = 1; 
    }
    $r = "";
    $statuses = get_statuses();
    foreach ($statuses as $n => $status) {
        $r .= '<span class="status' . $n . '" title="' . tohtml($status["name"]) . '">';
        $r .= '&nbsp;<input type="radio" name="WoStatus" value="' . $n . '"';
        if ($v == $n) { $r .= ' checked="checked"'; 
        }
        $r .= ' />' . tohtml($status["abbr"]) . "&nbsp;</span> ";
    }
    return $r;
}

// -------------------------------------------------------------

function get_wordstatus_selectoptions($v, $all, $not9899, $off=true) 
{
    if (! isset($v) ) {
        if ($all ) { $v = ""; 
        }
        else { $v = 1; 
        }
    }
    $r = "";
    if ($all && $off) {
        $r .= "<option value=\"\"" . get_selected($v, '');
        $r .= ">[Filter off]</option>";
    }
    $statuses = get_statuses();
    foreach ($statuses as $n => $status) {
        if ($not9899 && ($n == 98 || $n == 99)) { continue; 
        }
        $r .= "<option value =\"" . $n . "\"" . get_selected($v, $n!=0?$n:'0');
        $r .= ">" . tohtml($status['name']) . " [" . 
        tohtml($status['abbr']) . "]</option>";
    }
    if ($all) {
        $r .= '<option disabled="disabled">--------</option>';
        $status_1_name = tohtml($statuses[1]["name"]);
        $status_1_abbr = tohtml($statuses[1]["abbr"]);
        $r .= "<option value=\"12\"" . get_selected($v, 12);
        $r .= ">" . $status_1_name . " [" . $status_1_abbr . ".." . 
        tohtml($statuses[2]["abbr"]) . "]</option>";
        $r .= "<option value=\"13\"" . get_selected($v, 13);
        $r .= ">" . $status_1_name . " [" . $status_1_abbr . ".." . 
        tohtml($statuses[3]["abbr"]) . "]</option>";
        $r .= "<option value=\"14\"" . get_selected($v, 14);
        $r .= ">" . $status_1_name . " [" . $status_1_abbr . ".." . 
        tohtml($statuses[4]["abbr"]) . "]</option>";
        $r .= "<option value=\"15\"" . get_selected($v, 15);
        $r .= ">Learning/-ed [" . $status_1_abbr . ".." . 
        tohtml($statuses[5]["abbr"]) . "]</option>";
        $r .= '<option disabled="disabled">--------</option>';
        $status_2_name = tohtml($statuses[2]["name"]);
        $status_2_abbr = tohtml($statuses[2]["abbr"]);
        $r .= "<option value=\"23\"" . get_selected($v, 23);
        $r .= ">" . $status_2_name . " [" . $status_2_abbr . ".." . 
        tohtml($statuses[3]["abbr"]) . "]</option>";
        $r .= "<option value=\"24\"" . get_selected($v, 24);
        $r .= ">" . $status_2_name . " [" . $status_2_abbr . ".." . 
        tohtml($statuses[4]["abbr"]) . "]</option>";
        $r .= "<option value=\"25\"" . get_selected($v, 25);
        $r .= ">Learning/-ed [" . $status_2_abbr . ".." . 
        tohtml($statuses[5]["abbr"]) . "]</option>";
        $r .= '<option disabled="disabled">--------</option>';
        $status_3_name = tohtml($statuses[3]["name"]);
        $status_3_abbr = tohtml($statuses[3]["abbr"]);
        $r .= "<option value=\"34\"" . get_selected($v, 34);
        $r .= ">" . $status_3_name . " [" . $status_3_abbr . ".." . 
        tohtml($statuses[4]["abbr"]) . "]</option>";
        $r .= "<option value=\"35\"" . get_selected($v, 35);
        $r .= ">Learning/-ed [" . $status_3_abbr . ".." . 
        tohtml($statuses[5]["abbr"]) . "]</option>";
        $r .= '<option disabled="disabled">--------</option>';
        $r .= "<option value=\"45\"" . get_selected($v, 45);
        $r .= ">Learning/-ed [" .  tohtml($statuses[4]["abbr"]) . ".." . 
        tohtml($statuses[5]["abbr"]) . "]</option>";
        $r .= '<option disabled="disabled">--------</option>';
        $r .= "<option value=\"599\"" . get_selected($v, 599);
        $r .= ">All known [" . tohtml($statuses[5]["abbr"]) . "+" . 
        tohtml($statuses[99]["abbr"]) . "]</option>";
    }
    return $r;
}

// -------------------------------------------------------------

function get_annotation_position_selectoptions($v)
{
    if (! isset($v) ) { $v = 1; 
    }
    $r = "<option value=\"1\"" . get_selected($v, 1);
    $r .= ">Behind</option>";
    $r .= "<option value=\"3\"" . get_selected($v, 3);
    $r .= ">In Front Of</option>";
    $r .= "<option value=\"2\"" . get_selected($v, 2);
    $r .= ">Below</option>";
    $r .= "<option value=\"4\"" . get_selected($v, 4);
    $r .= ">Above</option>";
    return $r;
}

// -------------------------------------------------------------

function get_paging_selectoptions($currentpage, $pages) 
{
    $r = "";
    for ($i=1; $i<=$pages; $i++) {
        $r .= "<option value=\"" . $i . "\"" . get_selected($i, $currentpage);
        $r .= ">$i</option>";
    }
    return $r;
}

// -------------------------------------------------------------

function get_wordssort_selectoptions($v) 
{
    if (! isset($v) ) { $v = 1; 
    }
    $r  = "<option value=\"1\"" . get_selected($v, 1);
    $r .= ">Term A-Z</option>";
    $r .= "<option value=\"2\"" . get_selected($v, 2);
    $r .= ">Translation A-Z</option>";
    $r .= "<option value=\"3\"" . get_selected($v, 3);
    $r .= ">Newest first</option>";
    $r .= "<option value=\"7\"" . get_selected($v, 7);
    $r .= ">Oldest first</option>";
    $r .= "<option value=\"4\"" . get_selected($v, 4);
    $r .= ">Oldest first</option>"; 
    $r .= "<option value=\"5\"" . get_selected($v, 5);
    $r .= ">Status</option>";
    $r .= "<option value=\"6\"" . get_selected($v, 6);
    $r .= ">Score Value (%)</option>";
    $r .= "<option value=\"7\"" . get_selected($v, 7);
    $r .= ">Word Count Active Texts</option>";
    return $r;
}

// -------------------------------------------------------------

function get_tagsort_selectoptions($v) 
{
    if (! isset($v) ) { $v = 1; 
    }
    $r  = "<option value=\"1\"" . get_selected($v, 1);
    $r .= ">Tag Text A-Z</option>";
    $r .= "<option value=\"2\"" . get_selected($v, 2);
    $r .= ">Tag Comment A-Z</option>";
    $r .= "<option value=\"3\"" . get_selected($v, 3);
    $r .= ">Newest first</option>";
    $r .= "<option value=\"4\"" . get_selected($v, 4);
    $r .= ">Oldest first</option>";
    return $r;
}

// -------------------------------------------------------------

function get_textssort_selectoptions($v) 
{ 
    if (! isset($v) ) { $v = 1; 
    }
    $r  = "<option value=\"1\"" . get_selected($v, 1);
    $r .= ">Title A-Z</option>";
    $r .= "<option value=\"2\"" . get_selected($v, 2);
    $r .= ">Newest first</option>"; 
    $r .= "<option value=\"3\"" . get_selected($v, 3);
    $r .= ">Oldest first</option>"; 
    return $r;
}

// -------------------------------------------------------------

function get_yesno_selectoptions($v) 
{
    if (! isset($v) ) { $v = 0; 
    }
    $r  = "<option value=\"0\"" . get_selected($v, 0);
    $r .= ">No</option>";
    $r .= "<option value=\"1\"" . get_selected($v, 1);
    $r .= ">Yes</option>";
    return $r;
}

// -------------------------------------------------------------

function get_andor_selectoptions($v) 
{
    if (! isset($v) ) { $v = 0; 
    }
    $r  = "<option value=\"0\"" . get_selected($v, 0);
    $r .= ">... OR ...</option>";
    $r .= "<option value=\"1\"" . get_selected($v, 1);
    $r .= ">... AND ...</option>";
    return $r;
}

// -------------------------------------------------------------

function get_set_status_option($n, $suffix = "") 
{
    return "<option value=\"s" . $n . $suffix . "\">Set Status to " .
    tohtml(get_status_name($n)) . " [" . tohtml(get_status_abbr($n)) .
    "]</option>";
}

// -------------------------------------------------------------

function get_status_name($n) 
{
    $statuses = get_statuses();
    return $statuses[$n]["name"];
}

// -------------------------------------------------------------

function get_status_abbr($n) 
{
    $statuses = get_statuses();
    return $statuses[$n]["abbr"];
}

// -------------------------------------------------------------

function get_colored_status_msg($n) 
{
    return '<span class="status' . $n . '">&nbsp;' . tohtml(get_status_name($n)) . '&nbsp;[' . tohtml(get_status_abbr($n)) . ']&nbsp;</span>';
}

// -------------------------------------------------------------

function get_multiplewordsactions_selectoptions() 
{
    $r = "<option value=\"\" selected=\"selected\">[Choose...]</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"test\">Test Marked Terms</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"spl1\">Increase Status by 1 [+1]</option>";
    $r .= "<option value=\"smi1\">Reduce Status by 1 [-1]</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= get_set_status_option(1);
    $r .= get_set_status_option(5);
    $r .= get_set_status_option(99);
    $r .= get_set_status_option(98);
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"today\">Set Status Date to Today</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"lower\">Set Marked Terms to Lowercase</option>";
    $r .= "<option value=\"cap\">Capitalize Marked Terms</option>";
    $r .= "<option value=\"delsent\">Delete Sentences of Marked Terms</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"addtag\">Add Tag</option>";
    $r .= "<option value=\"deltag\">Remove Tag</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"exp\">Export Marked Terms (Anki)</option>";
    $r .= "<option value=\"exp2\">Export Marked Terms (TSV)</option>";
    $r .= "<option value=\"exp3\">Export Marked Terms (Flexible)</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"del\">Delete Marked Terms</option>";
    return $r;
}

// -------------------------------------------------------------

function get_multipletagsactions_selectoptions() 
{
    $r = "<option value=\"\" selected=\"selected\">[Choose...]</option>";
    $r .= "<option value=\"del\">Delete Marked Tags</option>";
    return $r;
}

// -------------------------------------------------------------

function get_allwordsactions_selectoptions() 
{
    $r = "<option value=\"\" selected=\"selected\">[Choose...]</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"testall\">Test ALL Terms</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"spl1all\">Increase Status by 1 [+1]</option>";
    $r .= "<option value=\"smi1all\">Reduce Status by 1 [-1]</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= get_set_status_option(1, "all");
    $r .= get_set_status_option(5, "all");
    $r .= get_set_status_option(99, "all");
    $r .= get_set_status_option(98, "all");
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"todayall\">Set Status Date to Today</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"lowerall\">Set ALL Terms to Lowercase</option>";
    $r .= "<option value=\"capall\">Capitalize ALL Terms</option>";
    $r .= "<option value=\"delsentall\">Delete Sentences of ALL Terms</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"addtagall\">Add Tag</option>";
    $r .= "<option value=\"deltagall\">Remove Tag</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"expall\">Export ALL Terms (Anki)</option>";
    $r .= "<option value=\"expall2\">Export ALL Terms (TSV)</option>";
    $r .= "<option value=\"expall3\">Export ALL Terms (Flexible)</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"delall\">Delete ALL Terms</option>";
    return $r;
}

// -------------------------------------------------------------

function get_alltagsactions_selectoptions() 
{
    $r = "<option value=\"\" selected=\"selected\">[Choose...]</option>";
    $r .= "<option value=\"delall\">Delete ALL Tags</option>";
    return $r;
}

/// Returns options for an HTML dropdown to choose a text along a criterion
function get_multipletextactions_selectoptions() 
{
    $r = "<option value=\"\" selected=\"selected\">[Choose...]</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"test\">Test Marked Texts</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"addtag\">Add Tag</option>";
    $r .= "<option value=\"deltag\">Remove Tag</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"rebuild\">Reparse Texts</option>";
    $r .= "<option value=\"setsent\">Set Term Sentences</option>";
    $r .= "<option value=\"setactsent\">Set Active Term Sentences</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"arch\">Archive Marked Texts</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"del\">Delete Marked Texts</option>";
    return $r;
}

// -------------------------------------------------------------

function get_multiplearchivedtextactions_selectoptions() 
{
    $r = "<option value=\"\" selected=\"selected\">[Choose...]</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"addtag\">Add Tag</option>";
    $r .= "<option value=\"deltag\">Remove Tag</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"unarch\">Unarchive Marked Texts</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"del\">Delete Marked Texts</option>";
    return $r;
}

// -------------------------------------------------------------

function get_texts_selectoptions($lang, $v) 
{
    global $tbpref;
    if (! isset($v) ) { $v = ''; 
    }
    if (! isset($lang) ) { $lang = ''; 
    }    
    if ($lang=="" ) { 
        $l = ""; 
    }    
    else { 
        $l = "and TxLgID=" . $lang; 
    }
    $r = "<option value=\"\"" . get_selected($v, '');
    $r .= ">[Filter off]</option>";
    $sql = "select TxID, TxTitle, LgName from " . $tbpref . "languages, " . $tbpref . "texts where LgID = TxLgID " . $l . " order by LgName, TxTitle";
    $res = do_mysqli_query($sql);
    while ($record = mysqli_fetch_assoc($res)) {
        $d = $record["TxTitle"];
        if (mb_strlen($d, 'UTF-8') > 30 ) { $d = mb_substr($d, 0, 30, 'UTF-8') . "..."; 
        }
        $r .= "<option value=\"" . $record["TxID"] . "\"" . get_selected($v, $record["TxID"]) . ">" . tohtml(($lang!="" ? "" : ($record["LgName"] . ": ")) . $d) . "</option>";
    }
    mysqli_free_result($res);
    return $r;
}

// -------------------------------------------------------------

function print_file_path($filename)
{
    echo get_file_path($filename);
}
// -------------------------------------------------------------

function get_file_path($filename)
{
    $file=getSettingWithDefault('set-theme-dir').preg_replace('/.*\//', '', $filename);
    if(file_exists($file)) { return $file; 
    }
    else{
        return $filename;
    }
}

// -------------------------------------------------------------

function makePager($currentpage, $pages, $script, $formname) 
{
    if ($currentpage > 1) { 
        ?>
   &nbsp; &nbsp;<a href="<?php echo $script; ?>?page=1"><img src="icn/control-stop-180.png" title="First Page" alt="First Page" /></a>&nbsp;
<a href="<?php echo $script; ?>?page=<?php echo $currentpage-1; ?>"><img  src="icn/control-180.png" title="Previous Page" alt="Previous Page" /></a>&nbsp;
        <?php
    } else {
        ?>
   &nbsp; &nbsp;<img src="<?php print_file_path('icn/placeholder.png');?>" alt="-" />&nbsp;
<img src="<?php print_file_path('icn/placeholder.png');?>" alt="-" />&nbsp;
        <?php
    } 
    ?>
Page
    <?php
    if ($pages==1) { 
        echo '1'; 
    }
    else {
        ?>
<select name="page" onchange="{val=document.<?php echo $formname; ?>.page.options[document.<?php echo $formname; ?>.page.selectedIndex].value; location.href='<?php echo $script; ?>?page=' + val;}"><?php echo get_paging_selectoptions($currentpage, $pages); ?></select>
        <?php
    }
    echo ' of ' . $pages . '&nbsp; ';
    if ($currentpage < $pages) { 
        ?>
<a href="<?php echo $script; ?>?page=<?php echo $currentpage+1; ?>"><img src="icn/control.png" title="Next Page" alt="Next Page" /></a>&nbsp;
<a href="<?php echo $script; ?>?page=<?php echo $pages; ?>"><img src="icn/control-stop.png" title="Last Page" alt="Last Page" /></a>&nbsp; &nbsp;
        <?php 
    } else {
        ?>
<img src="<?php print_file_path('icn/placeholder.png');?>" alt="-" />&nbsp;
<img src="<?php print_file_path('icn/placeholder.png');?>" alt="-" />&nbsp; &nbsp; 
        <?php
    }
}

// -------------------------------------------------------------

function makeStatusCondition($fieldname, $statusrange) 
{
    if ($statusrange >= 12 && $statusrange <= 15) {
        return '(' . $fieldname . ' between 1 and ' . ($statusrange % 10) . ')';
    } elseif ($statusrange >= 23 && $statusrange <= 25) {
        return '(' . $fieldname . ' between 2 and ' . ($statusrange % 10) . ')';
    } elseif ($statusrange >= 34 && $statusrange <= 35) {
        return '(' . $fieldname . ' between 3 and ' . ($statusrange % 10) . ')';
    } elseif ($statusrange == 45) {
        return '(' . $fieldname . ' between 4 and 5)';
    } elseif ($statusrange == 599) {
        return $fieldname . ' in (5,99)';
    } else {
        return $fieldname . ' = ' . $statusrange;
    }
}

// -------------------------------------------------------------

function checkStatusRange($currstatus, $statusrange) 
{
    if ($statusrange >= 12 && $statusrange <= 15) {
        return ($currstatus >= 1 && $currstatus <= ($statusrange % 10));
    } elseif ($statusrange >= 23 && $statusrange <= 25) {
        return ($currstatus >= 2 && $currstatus <= ($statusrange % 10));
    } elseif ($statusrange >= 34 && $statusrange <= 35) {
        return ($currstatus >= 3 && $currstatus <= ($statusrange % 10));
    } elseif ($statusrange == 45) {
        return ($currstatus == 4 || $currstatus == 5);
    } elseif ($statusrange == 599) {
        return ($currstatus == 5 || $currstatus == 99);
    } else {
        return ($currstatus == $statusrange);
    }
}

/**
 * Adds HTML attributes to create a filter over words learning status.
 *
 * @param  int $status Word learning status ([1-5]|98|99|599) 
 *                     - 599 is a special status combining 5 and 99 statuses
 *                     - '' return an empty string 
 * @return string CSS class filter to exclude $status
 */
function makeStatusClassFilter($status) 
{
    if ($status == '') { 
        return ''; 
    }
    $liste = array(1,2,3,4,5,98,99);
    if ($status == 599) {
        makeStatusClassFilterHelper(5, $liste);
        makeStatusClassFilterHelper(99, $liste);
    } elseif ($status < 6 || $status > 97) { 
        makeStatusClassFilterHelper($status, $liste);
    } else {
        $from = (int) ($status / 10);
        $to = $status - ($from*10);
        for ($i = $from; $i <= $to; $i++) {
            makeStatusClassFilterHelper($i, $liste); 
        }
    }
    // Set all statuses that are not -1
    $r = '';
    foreach ($liste as $v) {
        if ($v != -1) { 
            $r .= ':not(.status' . $v . ')'; 
        }
    }
    return $r;
}

/**
 * Replace $status in $array by -1
 * 
 * @param int   $status A value in $array
 * @param Any[] $array  Any array of values
 */
function makeStatusClassFilterHelper($status, &$array) 
{
    $pos = array_search($status, $array);
    if ($pos !== false) {
        $array[$pos] = -1; 
    }
}

/**
 * Create and verify a dictionary URL link
 *
 * @param string $u Dictionary URL. It may contain ### that will get parsed
 * @param string $t
 */

function createTheDictLink($u, $t) 
{
    // Case 1: url without any ###: append UTF-8-term
    // Case 2: url with one ###: substitute UTF-8-term
    // Case 3: url with two ###enc###: substitute enc-encoded-term
    // see http://php.net/manual/en/mbstring.supported-encodings.php for supported encodings
    $url = trim($u);
    $trm = trim($t);
    $pos = stripos($url, '###');
    if ($pos !== false) {  // ### found
        $pos2 = strripos($url, '###');
        if (($pos2-$pos-3) > 1 ) {  // 2 ### found
            $enc = trim(substr($url, $pos+3, $pos2-$pos-3));
            $r = substr($url, 0, $pos);
            $r .= urlencode(mb_convert_encoding($trm, $enc, 'UTF-8'));
            if (($pos2+3) < strlen($url)) { 
                $r .= substr($url, $pos2+3); 
            }
        } 
        elseif ($pos == $pos2 ) {  // 1 ### found
            $r = str_replace("###", ($trm == '' ? '+' : urlencode($trm)), $url);
        }
    }
    else {  // no ### found
        $r = $url . urlencode($trm); 
    }
    return $r;
}

// -------------------------------------------------------------

function createDictLinksInEditWin($lang,$word,$sentctljs,$openfirst) 
{
    global $tbpref;
    $sql = 'select LgDict1URI, LgDict2URI, LgGoogleTranslateURI from ' . $tbpref . 'languages where LgID = ' . $lang;
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    $wb1 = isset($record['LgDict1URI']) ? $record['LgDict1URI'] : "";
    $wb2 = isset($record['LgDict2URI']) ? $record['LgDict2URI'] : "";
    $wb3 = isset($record['LgGoogleTranslateURI']) ? $record['LgGoogleTranslateURI'] : "";
    mysqli_free_result($res);
    $r ='';
    if ($openfirst) {
        $r .= '<script type="text/javascript">';
        $r .= "\n//<![CDATA[\n";
        $r .= makeOpenDictStrJS(createTheDictLink($wb1, $word));
        $r .= "//]]>\n</script>\n";
    }
    $r .= 'Lookup Term: ';
    $r .= makeOpenDictStr(createTheDictLink($wb1, $word), "Dict1"); 
    if ($wb2 != "") { 
        $r .= makeOpenDictStr(createTheDictLink($wb2, $word), "Dict2"); 
    } 
    if ($wb3 != "") { 
        $r .= makeOpenDictStr(createTheDictLink($wb3, $word), "GTr") . ' | Sent.: ' . makeOpenDictStrDynSent($wb3, $sentctljs, "GTr"); 
    } 
    return $r;
}

// -------------------------------------------------------------

function makeOpenDictStr($url, $txt) 
{
    $r = '';
    if ($url != '' && $txt != '') {
        if(substr($url, 0, 1) == '*') {
            $r = ' <span class="click" onclick="owin(' . prepare_textdata_js(substr($url, 1)) . ');">' . tohtml($txt) . '</span> ';
        } 
        else {
            $r = ' <a href="' . $url . '" target="ru">' . tohtml($txt) . '</a> ';
        } 
    }
    return $r;
}

// -------------------------------------------------------------

function makeOpenDictStrJS($url) 
{
    $r = '';
    if ($url != '') {
        if(substr($url, 0, 1) == '*') {
            $r = "owin(" . prepare_textdata_js(substr($url, 1)) . ");\n";
        } 
        else {
            $r = "top.frames['ru'].location.href=" . prepare_textdata_js($url) . ";\n";
        } 
    }
    return $r;
}

// -------------------------------------------------------------

function makeOpenDictStrDynSent($url, $sentctljs, $txt) 
{
    $r = '';
    if ($url != '') {
        if (substr($url, 0, 7) == 'ggl.php') {
            $url = str_replace('?', '?sent=1&', $url);
        }
        if(substr($url, 0, 1) == '*') {
            $r = '<span class="click" onclick="translateSentence2(' . prepare_textdata_js(substr($url, 1)) . ',' . $sentctljs . ');">' . tohtml($txt) . '</span>';
        } 
        else {
            $r = '<span class="click" onclick="translateSentence(' . prepare_textdata_js($url) . ',' . $sentctljs . ');">' . tohtml($txt) . '</span>';
        } 
    }
    return $r;
}

// -------------------------------------------------------------

function createDictLinksInEditWin2($lang,$sentctljs,$wordctljs) 
{
    global $tbpref;
    $sql = 'SELECT LgDict1URI, LgDict2URI, LgGoogleTranslateURI 
    FROM ' . $tbpref . 'languages 
    WHERE LgID = ' . $lang;
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    $wb1 = isset($record['LgDict1URI']) ? $record['LgDict1URI'] : "";
    if (substr($wb1, 0, 1) == '*') { 
        $wb1 = substr($wb1, 1); 
    }
    $wb2 = isset($record['LgDict2URI']) ? $record['LgDict2URI'] : "";
    if(substr($wb2, 0, 1) == '*') {
        $wb2 = substr($wb2, 1); 
    }
    $wb3 = isset($record['LgGoogleTranslateURI']) ? $record['LgGoogleTranslateURI'] : "";
    if(substr($wb3, 0, 1) == '*') {
        $wb3 = substr($wb3, 1); 
    }
    mysqli_free_result($res);
    $r ='';
    $r .= 'Lookup Term: ';
    $r .= '<span class="click" onclick="translateWord2(' . prepare_textdata_js($wb1) . ',' . $wordctljs . ');">Dict1</span> ';
    if ($wb2 != "") { 
        $r .= '<span class="click" onclick="translateWord2(' . prepare_textdata_js($wb2) . ',' . $wordctljs . ');">Dict2</span> '; 
    }
    if ($wb3 != "") { 
        $r .= '<span class="click" onclick="translateWord2(' . prepare_textdata_js($wb3) . ',' . $wordctljs . ');">GTr</span> | Sent.: <span class="click" onclick="translateSentence2(' . prepare_textdata_js((substr($wb3, 0, 7) == 'ggl.php')?str_replace('?', '?sent=1&', $wb3):$wb3) . ',' . $sentctljs . ');">GTr</span>'; 
    }
    return $r;
}

// -------------------------------------------------------------

function makeDictLinks($lang,$wordctljs) 
{
    global $tbpref;
    $sql = 'select LgDict1URI, LgDict2URI, LgGoogleTranslateURI from ' . $tbpref . 'languages where LgID = ' . $lang;
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    $wb1 = isset($record['LgDict1URI']) ? $record['LgDict1URI'] : "";
    if(substr($wb1, 0, 1) == '*') { $wb1 = substr($wb1, 1); 
    }
    $wb2 = isset($record['LgDict2URI']) ? $record['LgDict2URI'] : "";
    if(substr($wb2, 0, 1) == '*') { $wb2 = substr($wb2, 1); 
    }
    $wb3 = isset($record['LgGoogleTranslateURI']) ? $record['LgGoogleTranslateURI'] : "";
    if(substr($wb3, 0, 1) == '*') { $wb3 = substr($wb3, 1); 
    }
    mysqli_free_result($res);
    $r ='<span class="smaller">';
    $r .= '<span class="click" onclick="translateWord3(' . prepare_textdata_js($wb1) . ',' . $wordctljs . ');">[1]</span> ';
    if ($wb2 != "") { 
        $r .= '<span class="click" onclick="translateWord3(' . prepare_textdata_js($wb2) . ',' . $wordctljs . ');">[2]</span> '; 
    }
    if ($wb3 != "") { 
        $r .= '<span class="click" onclick="translateWord3(' . prepare_textdata_js($wb3) . ',' . $wordctljs . ');">[G]</span>'; 
    } 
    $r .= '</span>';
    return $r;
}

// -------------------------------------------------------------

function createDictLinksInEditWin3($lang,$sentctljs,$wordctljs) 
{
    global $tbpref;
    $sql = 'select LgDict1URI, LgDict2URI, LgGoogleTranslateURI from ' . $tbpref . 'languages where LgID = ' . $lang;
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    
    $wb1 = isset($record['LgDict1URI']) ? $record['LgDict1URI'] : "";
    if(substr($wb1, 0, 1) == '*') { 
        $f1 = 'translateWord2(' . prepare_textdata_js(substr($wb1, 1)); 
    }
    else { 
        $f1 = 'translateWord(' . prepare_textdata_js($wb1); 
    }
        
    $wb2 = isset($record['LgDict2URI']) ? $record['LgDict2URI'] : "";
    if(substr($wb2, 0, 1) == '*') { 
        $f2 = 'translateWord2(' . prepare_textdata_js(substr($wb2, 1)); 
    }
    else { 
        $f2 = 'translateWord(' . prepare_textdata_js($wb2); 
    }

    $wb3 = isset($record['LgGoogleTranslateURI']) ? $record['LgGoogleTranslateURI'] : "";
    if(substr($wb3, 0, 1) == '*') {
        $f3 = 'translateWord2(' . prepare_textdata_js(substr($wb3, 1));
        $f4 = 'translateSentence2(' . prepare_textdata_js(substr($wb3, 1));
    } else {
        $f3 = 'translateWord(' . prepare_textdata_js($wb3);
        $f4 = 'translateSentence(' . prepare_textdata_js((substr($wb3, 0, 7) == 'ggl.php')?str_replace('?', '?sent=1&', $wb3):$wb3);
    }

    mysqli_free_result($res);
    $r ='';
    $r .= 'Lookup Term: ';
    $r .= '<span class="click" onclick="' . $f1 . ',' . $wordctljs . ');">Dict1</span> ';
    if ($wb2 != "") { 
        $r .= '<span class="click" onclick="' . $f2 . ',' . $wordctljs . ');">Dict2</span> '; 
    }
    if ($wb3 != "") { 
        $r .= '<span class="click" onclick="' . $f3 . ',' . $wordctljs . ');">GTr</span> | Sent.: <span class="click" onclick="' . $f4 . ',' . $sentctljs . ');">GTr</span>'; 
    } 
    return $r;
}

// -------------------------------------------------------------

function checkTest($val, $name) 
{
    if (! isset($_REQUEST[$name])) { return ' '; 
    }
    if (! is_array($_REQUEST[$name])) { return ' '; 
    }
    if (in_array($val, $_REQUEST[$name])) { return ' checked="checked" '; 
    }
    else { return ' '; 
    }
}

// -------------------------------------------------------------

function strToHex($string)
{
    $hex='';
    for ($i=0; $i < strlen($string); $i++)
    {
        $h = dechex(ord($string[$i]));
        if (strlen($h) == 1 ) { 
            $hex .= "0" . $h; 
        }
        else {
            $hex .= $h; 
        }
    }
    return strtoupper($hex);
}

/**
 * Escapes everything to "¤xx" but not 0-9, a-z, A-Z, and unicode >= (hex 00A5, dec 165)
 * 
 * @param string $string String to escape
 */
function strToClassName($string)
{
    $length = mb_strlen($string, 'UTF-8');
    $r = '';
    for ($i=0; $i < $length; $i++)
    {
        $c = mb_substr($string, $i, 1, 'UTF-8');
        $o = ord($c);
        if (($o < 48)  
            || ($o > 57 && $o < 65)  
            || ($o > 90 && $o < 97)  
            || ($o > 122 && $o < 165)
        ) {
            $r .= '¤' . strToHex($c); 
        }
        else { 
            $r .= $c; 
        }
    }
    return $r;
}

// -------------------------------------------------------------

function anki_export($sql) 
{
    // WoID, LgRightToLeft, LgRegexpWordCharacters, LgName, WoText, WoTranslation, WoRomanization, WoSentence, taglist
    $res = do_mysqli_query($sql);
    $x = '';
    while ($record = mysqli_fetch_assoc($res)) {
        if ('MECAB'== strtoupper(trim($record['LgRegexpWordCharacters']))) {
            $termchar = '一-龥ぁ-ヾ';
        } else {
            $termchar = $record['LgRegexpWordCharacters'];
        }
        $rtlScript = $record['LgRightToLeft'];
        $span1 = ($rtlScript ? '<span dir="rtl">' : '');
        $span2 = ($rtlScript ? '</span>' : '');
        $lpar = ($rtlScript ? ']' : '[');
        $rpar = ($rtlScript ? '[' : ']');
        $sent = tohtml(repl_tab_nl($record["WoSentence"]));
        $sent1 = str_replace(
            "{", '<span style="font-weight:600; color:#0000ff;">' . $lpar, str_replace(
                "}", $rpar . '</span>', 
                mask_term_in_sentence($sent, $termchar)
            )
        );
        $sent2 = str_replace("{", '<span style="font-weight:600; color:#0000ff;">', str_replace("}", '</span>', $sent));
        $x .= $span1 . tohtml(repl_tab_nl($record["WoText"])) . $span2 . "\t" . 
        tohtml(repl_tab_nl($record["WoTranslation"])) . "\t" . 
        tohtml(repl_tab_nl($record["WoRomanization"])) . "\t" . 
        $span1 . $sent1 . $span2 . "\t" . 
        $span1 . $sent2 . $span2 . "\t" . 
        tohtml(repl_tab_nl($record["LgName"])) . "\t" . 
        tohtml($record["WoID"]) . "\t" . 
        tohtml($record["taglist"]) .  
        "\r\n";
    }
    mysqli_free_result($res);
    header('Content-type: text/plain; charset=utf-8');
    header("Content-disposition: attachment; filename=lwt_anki_export_" . date('Y-m-d-H-i-s') . ".txt");
    echo $x;
    exit();
}

// -------------------------------------------------------------

function tsv_export($sql) 
{
    // WoID, LgName, WoText, WoTranslation, WoRomanization, WoSentence, WoStatus, taglist
    $res = do_mysqli_query($sql);
    $x = '';
    while ($record = mysqli_fetch_assoc($res)) {
        $x .= repl_tab_nl($record["WoText"]) . "\t" . 
        repl_tab_nl($record["WoTranslation"]) . "\t" . 
        repl_tab_nl($record["WoSentence"]) . "\t" . 
        repl_tab_nl($record["WoRomanization"]) . "\t" . 
        $record["WoStatus"] . "\t" . 
        repl_tab_nl($record["LgName"]) . "\t" . 
        $record["WoID"] . "\t" . 
        $record["taglist"] . "\r\n";
    }
    mysqli_free_result($res);
    header('Content-type: text/plain; charset=utf-8');
    header("Content-disposition: attachment; filename=lwt_tsv_export_" . date('Y-m-d-H-i-s') . ".txt");
    echo $x;
    exit();
}

// -------------------------------------------------------------

function flexible_export($sql) 
{
    // WoID, LgName, LgExportTemplate, LgRightToLeft, WoText, WoTextLC, WoTranslation, WoRomanization, WoSentence, WoStatus, taglist
    $res = do_mysqli_query($sql);
    $x = '';
    while ($record = mysqli_fetch_assoc($res)) {
        if (isset($record['LgExportTemplate'])) {
            $woid = $record['WoID'] + 0;
            $langname = repl_tab_nl($record['LgName']);
            $rtlScript = $record['LgRightToLeft'];
            $span1 = ($rtlScript ? '<span dir="rtl">' : '');
            $span2 = ($rtlScript ? '</span>' : '');
            $term = repl_tab_nl($record['WoText']);
            $term_lc = repl_tab_nl($record['WoTextLC']);
            $transl = repl_tab_nl($record['WoTranslation']);
            $rom = repl_tab_nl($record['WoRomanization']);
            $sent_raw = repl_tab_nl($record['WoSentence']);
            $sent = str_replace('{', '', str_replace('}', '', $sent_raw));
            $sent_c = mask_term_in_sentence_v2($sent_raw);
            $sent_d = str_replace('{', '[', str_replace('}', ']', $sent_raw));
            $sent_x = str_replace('{', '{{c1::', str_replace('}', '}}', $sent_raw));
            $sent_y = str_replace('{', '{{c1::', str_replace('}', '::' . $transl . '}}', $sent_raw));
            $status = $record['WoStatus'] + 0;
            $taglist = trim($record['taglist']);
            $xx = repl_tab_nl($record['LgExportTemplate']);    
            $xx = str_replace('%w', $term, $xx);        
            $xx = str_replace('%t', $transl, $xx);        
            $xx = str_replace('%s', $sent, $xx);        
            $xx = str_replace('%c', $sent_c, $xx);        
            $xx = str_replace('%d', $sent_d, $xx);        
            $xx = str_replace('%r', $rom, $xx);        
            $xx = str_replace('%a', $status, $xx);        
            $xx = str_replace('%k', $term_lc, $xx);        
            $xx = str_replace('%z', $taglist, $xx);        
            $xx = str_replace('%l', $langname, $xx);        
            $xx = str_replace('%n', $woid, $xx);        
            $xx = str_replace('%%', '%', $xx);        
            $xx = str_replace('$w', $span1 . tohtml($term) . $span2, $xx);        
            $xx = str_replace('$t', tohtml($transl), $xx);        
            $xx = str_replace('$s', $span1 . tohtml($sent) . $span2, $xx);        
            $xx = str_replace('$c', $span1 . tohtml($sent_c) . $span2, $xx);        
            $xx = str_replace('$d', $span1 . tohtml($sent_d) . $span2, $xx);        
            $xx = str_replace('$x', $span1 . tohtml($sent_x) . $span2, $xx);        
            $xx = str_replace('$y', $span1 . tohtml($sent_y) . $span2, $xx);        
            $xx = str_replace('$r', tohtml($rom), $xx);        
            $xx = str_replace('$k', $span1 . tohtml($term_lc) . $span2, $xx);        
            $xx = str_replace('$z', tohtml($taglist), $xx);        
            $xx = str_replace('$l', tohtml($langname), $xx);        
            $xx = str_replace('$$', '$', $xx);        
            $xx = str_replace('\\t', "\t", $xx);        
            $xx = str_replace('\\n', "\n", $xx);        
            $xx = str_replace('\\r', "\r", $xx);        
            $xx = str_replace('\\\\', '\\', $xx);        
            $x .= $xx;
        }
    }
    mysqli_free_result($res);
    header('Content-type: text/plain; charset=utf-8');
    header("Content-disposition: attachment; filename=lwt_flexible_export_" . date('Y-m-d-H-i-s') . ".txt");
    echo $x;
    exit();
}

// -------------------------------------------------------------

function mask_term_in_sentence_v2($s) 
{
    $l = mb_strlen($s, 'utf-8');
    $r = '';
    $on = 0;
    for ($i=0; $i < $l; $i++) {
        $c = mb_substr($s, $i, 1, 'UTF-8');
        if ($c == '}') { 
            $on = 0;
            continue;
        }
        if ($c == '{') {
            $on = 1;
            $r .= '[...]';
            continue;
        }
        if ($on == 0) {
            $r .= $c;
        }
    }
    return $r;
}

/**
 * Replace all white space characters by a simple space ' '.
 * The output string is also trimmed.
 * 
 * @param  string $s String to parse
 * @return string String with only simple whitespaces.
 */
function repl_tab_nl($s) 
{
    $s = str_replace(array("\r\n", "\r", "\n", "\t"), ' ', $s);
    $s = preg_replace('/\s/u', ' ', $s);
    $s = preg_replace('/\s{2,}/u', ' ', $s);
    return trim($s);
}

// -------------------------------------------------------------

function mask_term_in_sentence($s,$regexword) 
{
    $l = mb_strlen($s, 'utf-8');
    $r = '';
    $on = 0;
    for ($i=0; $i < $l; $i++) {
        $c = mb_substr($s, $i, 1, 'UTF-8');
        if ($c == '}') { $on = 0; 
        }
        if ($on) {
            if (preg_match('/[' . $regexword . ']/u', $c)) {
                $r .= '•';
            } else {
                $r .= $c;
            }    
        }
        else {
            $r .= $c;
        }
        if ($c == '{') { 
            $on = 1; 
        }
    }
    return $r;
}

/**
 * Compute the word statistics about a specific text ID.
 * 
 * It is useful for unknown percent with this fork.
 *
 * @param  string $textID identifier for this text
 * @return int[6] Total number of words, number of expression, statistics, 
 * total unique, number of unique expressions, unique statistics
 * @global string $tbpref Table name prefix
 */
function textwordcount($textID) 
{
    global $tbpref;
    $r = $total = $total_unique = $expr = $expr_unique = $stat = $stat_unique = array();
    $i = array(1,2,3,4,5,99,98);
    $res = do_mysqli_query(
        'select Ti2TxID as text, count(distinct lower(Ti2Text)) as value, count(lower(Ti2Text)) as total
		 from ' . $tbpref . 'textitems2
		 where Ti2WordCount = 1 and Ti2TxID in(' . $textID . ')
		 group by Ti2TxID'
    );
    while ($record = mysqli_fetch_assoc($res)) {
        $total[$record['text']] = $record['total'];
        $total_unique[$record['text']] = $record['value'];
    }
    mysqli_free_result($res);
    $res = do_mysqli_query(
        'select Ti2TxID as text, count(distinct Ti2WoID) as value, count(Ti2WoID) as total
		 from ' . $tbpref . 'textitems2
		 where Ti2WordCount > 1 and Ti2TxID in(' . $textID . ')
		 group by Ti2TxID'
    );
    while ($record = mysqli_fetch_assoc($res)) {
        $expr[$record['text']] = $record['total'];
        $expr_unique[$record['text']] = $record['value'];
    }
    mysqli_free_result($res);
    $res = do_mysqli_query(
        'select Ti2TxID as text, count(distinct Ti2WoID) as value, count(Ti2WoID) as total, WoStatus as status
		 from ' . $tbpref . 'textitems2, ' . $tbpref . 'words
		 where Ti2WoID!=0 and Ti2TxID in(' . $textID . ')
		 and Ti2WoID=WoID
		 group by Ti2TxID,WoStatus'
    );
    while ($record = mysqli_fetch_assoc($res)) {
        $stat[$record['text']][$record['status']]=$record['total'];
        $stat_unique[$record['text']][$record['status']]=$record['value'];
    }
    mysqli_free_result($res);
    $r = array('total'=>$total,'expr'=>$expr,'stat'=>$stat,'totalu'=>$total_unique,'expru'=>$expr_unique,'statu'=>$stat_unique);
    echo json_encode($r);
}

// -------------------------------------------------------------

function texttodocount($text) 
{
    global $tbpref;
    return '<span title="To Do" class="status0">&nbsp;' . 
    (get_first_value('SELECT count(DISTINCT LOWER(Ti2Text)) as value FROM ' . $tbpref . 'textitems2 WHERE Ti2WordCount=1 and Ti2WoID=0 and Ti2TxID=' . $text)) . '&nbsp;</span>';
}

// -------------------------------------------------------------

function texttodocount2($text) 
{
    global $tbpref;
    $c = get_first_value('SELECT count(DISTINCT LOWER(Ti2Text)) as value FROM ' . $tbpref . 'textitems2 WHERE Ti2WordCount=1 and Ti2WoID=0 and Ti2TxID=' . $text);
    if ($c > 0 ) { 
        $show_buttons=getSettingWithDefault('set-words-to-do-buttons');
        $dict = get_first_value('select LgGoogleTranslateURI as value from ' . $tbpref . 'languages, ' . $tbpref . 'texts where LgID = TxLgID and TxID = ' . $text);
        $tl=preg_replace('/.*[?&]tl=([a-zA-Z\-]*)(&.*)*$/', '$1', $dict);
        $sl=preg_replace('/.*[?&]sl=([a-zA-Z\-]*)(&.*)*$/', '$1', $dict);
        $res = '<span title="To Do" class="status0">&nbsp;' . $c . '&nbsp;</span>&nbsp;';
        if(1==1) { 
            $res .='<img src="icn/script-import.png" onclick="{top.frames[\'ro\'].location.href=\'bulk_translate_words.php?tid=10&offset=0&sl=fr&tl=en\';}" style="cursor: pointer;vertical-align:middle" title="Lookup New Words" alt="Lookup New Words" />&nbsp;&nbsp;&nbsp;'; 
        }
        // if(1==1)$res .='<img src="icn/script-import.png" onclick="{top.frames[\'ro\'].location.href=\'bulk_translate_words.php?tid=' . $text . '&offset=0&sl=' . $sl . '&tl=' . $tl . '\';}" style="cursor: pointer;vertical-align:middle" title="Lookup New Words" alt="Lookup New Words" />&nbsp;&nbsp;&nbsp;';
        if($show_buttons!=2) { 
            $res .='<input type="button" onclick="iknowall(' . $text . ');" value=" I KNOW ALL " />'; 
        }
        if($show_buttons!=1) { 
            $res.='<input type="button" onclick="ignoreall(' . $text . ');" value=" IGNORE ALL " />'; 
        }
        return $res    ;
    }
    else {
        return '<span title="To Do" class="status0">&nbsp;' . $c . '&nbsp;</span>'; 
    }
}

// -------------------------------------------------------------
// @return array[2] [0]=html, word in bold, [1]=text, word in {} 
function getSentence($seid, $wordlc,$mode) 
{
    global $tbpref;
    $res = do_mysqli_query('select concat(\'​\',group_concat(Ti2Text order by Ti2Order asc SEPARATOR \'​\'),\'​\') as SeText, Ti2TxID as SeTxID, LgRegexpWordCharacters, LgRemoveSpaces, LgSplitEachChar from ' . $tbpref . 'textitems2, ' . $tbpref . 'languages where Ti2LgID = LgID and Ti2WordCount<2 and Ti2SeID= ' . $seid);
    $record = mysqli_fetch_assoc($res);
    $removeSpaces = $record["LgRemoveSpaces"];
    $splitEachChar = $record['LgSplitEachChar'];
    $txtid = $record["SeTxID"];
    if(($removeSpaces==1 && $splitEachChar==0) || 'MECAB'== strtoupper(trim($record["LgRegexpWordCharacters"]))) {
        $text = $record["SeText"];
        $wordlc = '[​]*' . preg_replace('/(.)/u', "$1[​]*", $wordlc);
        $pattern = '/(?<=[​])(' . $wordlc . ')(?=[​])/ui';
    }
    else{
        $text = str_replace(array('​​','​','\r'), array('\r','','​'), $record["SeText"]);
        if($splitEachChar==0) {
            $pattern = '/(?<![' . $record["LgRegexpWordCharacters"] . '])(' . remove_spaces($wordlc, $removeSpaces) . ')(?![' . $record["LgRegexpWordCharacters"] . '])/ui';
        }
        else { $pattern ='/(' .  $wordlc . ')/ui'; 
        }
    }
    $se = str_replace('​', '', preg_replace($pattern, '<b>$0</b>', $text));
    $sejs = str_replace('​', '', preg_replace($pattern, '{$0}', $text));
    if ($mode > 1) {
        if($removeSpaces==1 && $splitEachChar==0) {
            $prevseSent = get_first_value('select concat(\'​\',group_concat(Ti2Text order by Ti2Order asc SEPARATOR \'​\'),\'​\') as value from ' . $tbpref . 'sentences, ' . $tbpref . 'textitems2 where Ti2SeID = SeID and SeID < ' . $seid . ' and SeTxID = ' . $txtid . " and trim(SeText) not in ('¶','') group by SeID order by SeID desc");
        }
        else{
            $prevseSent = get_first_value('select SeText as value from ' . $tbpref . 'sentences where SeID < ' . $seid . ' and SeTxID = ' . $txtid . " and trim(SeText) not in ('¶','') order by SeID desc");
        }
        if (isset($prevseSent)) {
            $se = preg_replace($pattern, '<b>$0</b>', $prevseSent) . $se;
            $sejs = preg_replace($pattern, '{$0}', $prevseSent) . $sejs;
        }
        if ($mode > 2) {
            if($removeSpaces==1 && $splitEachChar==0) {
                $nextSent = get_first_value('select concat(\'​\',group_concat(Ti2Text order by Ti2Order asc SEPARATOR \'​\'),\'​\') as  value from ' . $tbpref . 'sentences, ' . $tbpref . 'textitems2 where Ti2SeID = SeID and SeID > ' . $seid . ' and SeTxID = ' . $txtid . " and trim(SeText) not in ('¶','') group by SeID order by SeID asc");
            }
            else{
                $nextSent = get_first_value('select SeText as value from ' . $tbpref . 'sentences where SeID > ' . $seid . ' and SeTxID = ' . $txtid . " and trim(SeText) not in ('¶','') order by SeID asc");
            }
            if (isset($nextSent)) {
                $se .= preg_replace($pattern, '<b>$0</b>', $nextSent);
                $sejs .= preg_replace($pattern, '{$0}', $nextSent);
            }
        }
    }
    mysqli_free_result($res);
    if($removeSpaces==1) {
        $se = str_replace('​', '', $se);
        $sejs = str_replace('​', '', $sejs);
    }
    /* Not merged from official. Works better?
    $nextseid = get_first_value('select SeID as value from ' . $tbpref . 'sentences where SeID > ' . $seid . ' and SeTxID = ' . $txtid . " and trim(SeText) not in ('¶','') order by SeID asc");
    if (isset($nextseid)) $seidlist .= ',' . $nextseid;
    }
    }
    $sql2 = 'SELECT TiText, TiTextLC, TiWordCount, TiIsNotWord FROM ' . $tbpref . 'textitems WHERE TiSeID in (' . $seidlist . ') and TiTxID=' . $txtid . ' order by TiOrder asc, TiWordCount desc';
    $res2 = do_mysqli_query($sql2);
    $sejs=''; 
    $se='';
    $notfound = 1;
    $jump=0;
    while ($record2 = mysqli_fetch_assoc($res2)) {
    if ($record2['TiIsNotWord'] == 1) {
    $jump--;
    if ($jump < 0) {
                $sejs .= $record2['TiText']; 
                $se .= tohtml($record2['TiText']);
    } 
    }    else {
    if (($jump-1) < 0) {
                if ($notfound) {
                    if ($record2['TiTextLC'] == $wordlc) { 
                        $sejs.='{'; 
                        $se.='<b>'; 
                        $sejs .= $record2['TiText']; 
                        $se .= tohtml($record2['TiText']); 
                        $sejs.='}'; 
                        $se.='</b>';
                        $notfound = 0;
                        $jump=($record2['TiWordCount']-1)*2; 
                    }
                }
                if ($record2['TiWordCount'] == 1) {
                    if ($notfound) {
                        $sejs .= $record2['TiText']; 
                        $se .= tohtml($record2['TiText']);
                        $jump=0;  
                    }    else {
                        $notfound = 1;
                    }
                }
    } else {
                if ($record2['TiWordCount'] == 1) $jump--; 
    }
    }
    }
    mysqli_free_result($res2);
    */
    return array($se,$sejs); // [0]=html, word in bold
                             // [1]=text, word in {} 
}

/** 
 * Returns path to the MeCab application.
 * MeCab can split Japanese text word by word
 *
 * @param  String $mecab_args Arguments to add
 * @return String OS-compatible command
 */
function get_mecab_path($mecab_args = '') 
{
    $os = strtoupper(substr(PHP_OS, 0, 3));
    if ($os == 'LIN') {
        return 'mecab' . str_replace('\\', '\\\\', $mecab_args); 
    }
    if ($os == 'WIN') {
        return '"%ProgramFiles%/MeCab/bin/mecab.exe"' . $mecab_args; 
    }
}

// -------------------------------------------------------------

function get20Sentences($lang, $wordlc, $wid, $jsctlname, $mode) 
{
    global $tbpref;
    $r = '<p><b>Sentences in active texts with <i>' . tohtml($wordlc) . '</i></b></p><p>(Click on <img src="icn/tick-button.png" title="Choose" alt="Choose" /> to copy sentence into above term)</p>';
    if(empty($wid)) {
        $sql = 'SELECT DISTINCT SeID, SeText FROM ' . $tbpref . 'sentences, ' . $tbpref . 'textitems2 WHERE lower(Ti2Text) = ' . convert_string_to_sqlsyntax($wordlc) . ' AND Ti2WoID = 0 AND SeID = Ti2SeID AND SeLgID = ' . $lang . ' order by CHAR_LENGTH(SeText), SeText limit 0,20';
    }
    else if($wid==-1) {
        $res = do_mysqli_query('select LgRegexpWordCharacters,LgRemoveSpaces from ' . $tbpref . 'languages where LgID = ' . $lang);
        $record = mysqli_fetch_assoc($res);
        mysqli_free_result($res);
        $removeSpaces = $record["LgRemoveSpaces"];
        if('MECAB'== strtoupper(trim($record["LgRegexpWordCharacters"]))) {
            $mecab_file = sys_get_temp_dir() . "/" . $tbpref . "mecab_to_db.txt";
            //$mecab_args = ' -F {%m%t\\t -U {%m%t\\t -E \\n ';
            // For instance, "このラーメン" becomes "この    6    68\nラーメン    7    38"
            $mecab_args = ' -F %m\\t%t\\t%h\\n -U %m\\t%t\\t%h\\n -E EOS\\t3\\t7\\n ';
            if(file_exists($mecab_file)) { 
                unlink($mecab_file); 
            }
            $fp = fopen($mecab_file, 'w');
            fwrite($fp, $wordlc . "\n");
            fclose($fp);
            $mecab = get_mecab_path($mecab_args);
            $handle = popen($mecab . $mecab_file, "r");
            if (!feof($handle)) {
                $row = fgets($handle, 256);
                //$mecab_str = "\t" . str_replace(array('þ',"\n"),array('',''),preg_replace(array('$ÿ記号[^\t]*\t$u','$ÿ名詞-数\t$u','$[0-9a-zA-Z]ÿ[^\t]*\t$u','$ÿ[^\t]*\t$u'),array('','','',"\t"), $row));
                /*$mecab_str = "\t" . str_replace(
                array('{',"\n"), array('',''),
                preg_replace_callback(
                '$(([267])|[0-9])\t$u', 
                function ($matches){
                if(isset($matches[2])) return "\t"; else return "";
                }, 
                $row)
                );*/
                // Format string removing numbers. 
                // MeCab tip: 2 = hiragana, 6 = kanji, 7 = katakana
                $mecab_str = "\t" . preg_replace_callback(
                    '([267]?)\t[0-9]+$', 
                    function ($matches) {
                        return isset($matches[1]) ? "\t" : "";
                    }, 
                    $row
                ); 
            }
            pclose($handle);
            unlink($mecab_file);
            $sql 
            = 'SELECT SeID, SeText, concat("\\t",group_concat(Ti2Text
             ORDER BY Ti2Order asc SEPARATOR "\\t"),"\\t") val
             FROM ' . $tbpref . 'sentences, ' . $tbpref . 'textitems2
             WHERE lower(SeText)
             LIKE ' . convert_string_to_sqlsyntax('%' . $wordlc . '%') . '
             AND SeID = Ti2SeID AND SeLgID = ' . $lang . ' AND Ti2WordCount<2
             GROUP BY SeID
             HAVING val 
             LIKE ' . convert_string_to_sqlsyntax_notrim_nonull('%' . $mecab_str . '%') . '
             ORDER BY CHAR_LENGTH(SeText), SeText 
             LIMIT 0,20';
        } else {
            if (!($removeSpaces==1)) {
                $pattern = convert_regexp_to_sqlsyntax(
                    '(^|[^' . $record["LgRegexpWordCharacters"] . '])'
                     . remove_spaces($wordlc, $removeSpaces)
                     . '([^' . $record["LgRegexpWordCharacters"] . ']|$)'
                );
            }
            else {
                $pattern = convert_string_to_sqlsyntax($wordlc);
            }
            $sql 
            = 'SELECT DISTINCT SeID, SeText
             FROM ' . $tbpref . 'sentences
             WHERE SeText rlike ' . $pattern . ' AND SeLgID = ' . $lang . '
             ORDER BY CHAR_LENGTH(SeText), SeText 
             LIMIT 0,20';
        }
    }
    else {
        $sql 
        = 'SELECT DISTINCT SeID, SeText
         FROM ' . $tbpref . 'sentences, ' . $tbpref . 'textitems2
         WHERE Ti2WoID = ' . $wid . ' AND SeID = Ti2SeID AND SeLgID = ' . $lang . '
         ORDER BY CHAR_LENGTH(SeText), SeText
         LIMIT 0,20';
    }
    $res = do_mysqli_query($sql);
    $r .= '<p>';
    $last = '';
    while ($record = mysqli_fetch_assoc($res)) {
        if ($last != $record['SeText']) {
            $sent = getSentence($record['SeID'], $wordlc, $mode);
            if(mb_strstr($sent[1], '}', 'UTF-8')) {
                $r .= '<span class="click" onclick="{' . $jsctlname . '.value=' . prepare_textdata_js($sent[1]) . '; makeDirty();}"><img src="icn/tick-button.png" title="Choose" alt="Choose" /></span> &nbsp;' . $sent[0] . '<br />';
            }
        }
        $last = $record['SeText'];
    }
    mysqli_free_result($res);
    $r .= '</p>';
    return $r;
}

// -------------------------------------------------------------

function getsqlscoreformula($method) 
{
    // $method = 2 (today)
    // $method = 3 (tomorrow)
    // Formula: {{{2.4^{Status}+Status-Days-1} over Status -2.4} over 0.14325248}
        
    if ($method == 3) { 
        return '
        GREATEST(-125, CASE 
            WHEN WoStatus > 5 THEN 100 
            WHEN WoStatus = 1 THEN ROUND(-7 -7 * DATEDIFF(NOW(),WoStatusChanged)) 
            WHEN WoStatus = 2 THEN ROUND(3.4 - 3.5 * DATEDIFF(NOW(),WoStatusChanged)) 
            WHEN WoStatus = 3 THEN ROUND(17.7 - 2.3 * DATEDIFF(NOW(),WoStatusChanged)) 
            WHEN WoStatus = 4 THEN ROUND(44.65 - 1.75 * DATEDIFF(NOW(),WoStatusChanged)) 
            WHEN WoStatus = 5 THEN ROUND(98.6 - 1.4 * DATEDIFF(NOW(),WoStatusChanged)) 
        END)';
    }
    elseif ($method == 2) { 
        return '
        GREATEST(-125, CASE 
            WHEN WoStatus > 5 THEN 100
            WHEN WoStatus = 1 THEN ROUND(-7 * DATEDIFF(NOW(),WoStatusChanged))
            WHEN WoStatus = 2 THEN ROUND(6.9 - 3.5 * DATEDIFF(NOW(),WoStatusChanged))
            WHEN WoStatus = 3 THEN ROUND(20 - 2.3 * DATEDIFF(NOW(),WoStatusChanged))
            WHEN WoStatus = 4 THEN ROUND(46.4 - 1.75 * DATEDIFF(NOW(),WoStatusChanged))
            WHEN WoStatus = 5 THEN ROUND(100 - 1.4 * DATEDIFF(NOW(),WoStatusChanged))
        END)';
    } 
    return '0'; 
    
    
}

// -------------------------------------------------------------

function areUnknownWordsInSentence($sentno) 
{
    global $tbpref;
    $x = get_first_value(
        "SELECT distinct Ti2Text AS value 
        FROM " . $tbpref . "textitems2 
        WHERE Ti2SeID = " . $sentno . " AND Ti2WordCount = 1 AND Ti2WoID = 0 
        LIMIT 1"
    );
    //    $x = get_first_value("SELECT distinct ifnull(WoTextLC,'') as value FROM (" . $tbpref . "textitems left join " . $tbpref . "words on (TiTextLC = WoTextLC) and (TiLgID = WoLgID)) where TiSeID = " . $sentno . " AND TiWordCount = 1 AND TiIsNotWord = 0 order by WoTextLC asc limit 1");
    // echo $sentno . '/' . isset($x) . '/' . $x . '/';
    if (isset($x) && $x == '') {
        return true;
    }
    return false;
}


/// Return a dictionary of languages name - id
function get_languages() 
{
    global $tbpref;
    $langs = array();
    $sql = "SELECT LgID, LgName FROM " . $tbpref . "languages WHERE LgName<>''";
    $res = do_mysqli_query($sql);
    while ($record = mysqli_fetch_assoc($res)) {
        $langs[$record['LgName']] = $record['LgID'];
    }
    mysqli_free_result($res);
    return $langs;
}

/**
 * Reload $setting_data if necessary
 * 
 * @return array $setting_data
 */
function get_setting_data() 
{
    static $setting_data;
    if (!$setting_data) {
        $setting_data = array(
        'set-text-h-frameheight-no-audio' => 
        array("dft" => '140', "num" => 1, "min" => 10, "max" => 999),
        'set-text-h-frameheight-with-audio' => 
        array("dft" => '200', "num" => 1, "min" => 10, "max" => 999),
        'set-text-l-framewidth-percent' => 
        array("dft" => '50', "num" => 1, "min" => 5, "max" => 95),
        'set-text-r-frameheight-percent' => 
        array("dft" => '50', "num" => 1, "min" => 5, "max" => 95),
        'set-test-h-frameheight' => 
        array("dft" => '140', "num" => 1, "min" => 10, "max" => 999),
        'set-test-l-framewidth-percent' => 
        array("dft" => '50', "num" => 1, "min" => 5, "max" => 95),
        'set-test-r-frameheight-percent' => 
        array("dft" => '50', "num" => 1, "min" => 5, "max" => 95),
        'set-words-to-do-buttons' => 
        array("dft" => '1', "num" => 0),
        'set-tooltip-mode' => 
        array("dft" => '2', "num" => 0),
        'set-display-text-frame-term-translation' => 
        array("dft" => '1', "num" => 0),
        'set-text-frame-annotation-position' => 
        array("dft" => '2', "num" => 0),
        'set-test-main-frame-waiting-time' => 
        array("dft" => '0', "num" => 1, "min" => 0, "max" => 9999),
        'set-test-edit-frame-waiting-time' => 
        array("dft" => '500', "num" => 1, "min" => 0, "max" => 99999999),
        'set-test-sentence-count' => 
        array("dft" => '1', "num" => 0),
        'set-tts' => 
        array("dft" => '1', "num" => 0),
        'set-term-sentence-count' => 
        array("dft" => '1', "num" => 0),
        'set-archivedtexts-per-page' => 
        array("dft" => '100', "num" => 1, "min" => 1, "max" => 9999),
        'set-texts-per-page' => 
        array("dft" => '10', "num" => 1, "min" => 1, "max" => 9999),
        'set-terms-per-page' => 
        array("dft" => '100', "num" => 1, "min" => 1, "max" => 9999),
        'set-tags-per-page' => 
        array("dft" => '100', "num" => 1, "min" => 1, "max" => 9999),
        'set-articles-per-page' => 
        array("dft" => '10', "num" => 1, "min" => 1, "max" => 9999),
        'set-feeds-per-page' => 
        array("dft" => '50', "num" => 1, "min" => 1, "max" => 9999),
        'set-max-articles-with-text' => 
        array("dft" => '100', "num" => 1, "min" => 1, "max" => 9999),
        'set-max-articles-without-text' => 
        array("dft" => '250', "num" => 1, "min" => 1, "max" => 9999),
        'set-max-texts-per-feed' => 
        array("dft" => '20', "num" => 1, "min" => 1, "max" => 9999),
        'set-ggl-translation-per-page' => 
        array("dft" => '100', "num" => 1, "min" => 1, "max" => 9999),
        'set-regex-mode' => 
        array("dft" => '', "num" => 0),
        'set-theme_dir' => 
        array("dft" => 'themes/default/', "num" => 0),
        'set-text-visit-statuses-via-key' => 
        array("dft" => '', "num" => 0),
        'set-term-translation-delimiters' => 
        array("dft" => '/;|', "num" => 0),
        'set-mobile-display-mode' => 
        array("dft" => '0', "num" => 0),
        'set-similar-terms-count' => 
        array("dft" => '0', "num" => 1, "min" => 0, "max" => 9)
        );
    }
    return $setting_data;
}

// -------------------------------------------------------------

function reparse_all_texts() 
{
    global $tbpref;
    runsql('TRUNCATE ' . $tbpref . 'sentences', '');
    runsql('TRUNCATE ' . $tbpref . 'textitems2', '');
    adjust_autoincr('sentences', 'SeID');
    set_word_count();
    $sql = "select TxID, TxLgID from " . $tbpref . "texts";
    $res = do_mysqli_query($sql);
    while ($record = mysqli_fetch_assoc($res)) {
        $id = $record['TxID'];
        splitCheckText(
            get_first_value('select TxText as value from ' . $tbpref . 'texts where TxID = ' . $id), $record['TxLgID'], $id 
        );
    }
    mysqli_free_result($res);
}

/**
 * Get language name from its ID 
 * 
 * @param  int $lid Language ID
 * @return string Language name
 * @global string $tbpref Table name prefix
 */ 
function getLanguage($lid) 
{
    global $tbpref;
    if (!isset($lid) || trim($lid) == '' || !is_numeric($lid)) { 
        return ''; 
    }
    $r = get_first_value(
        "SELECT LgName AS value 
        FROM " . $tbpref . "languages 
        WHERE LgID='" . $lid . "'"
    );
    if (isset($r)) { 
        return $r; 
    }
    return '';
}

// -------------------------------------------------------------

function getScriptDirectionTag($lid) 
{
    global $tbpref;
    if (!isset($lid) ) { 
        return ''; 
    }
    if (trim($lid) == '' ) { 
        return ''; 
    }
    if (!is_numeric($lid) ) {
        return ''; 
    }
    $r = get_first_value("select LgRightToLeft as value from " . $tbpref . "languages where LgID='" . $lid . "'");
    if (isset($r) ) {
        if ($r) { 
            return ' dir="rtl" '; 
        } 
    }
    return '';
}

/**
 * Parse the input text.
 * 
 * @param string $text Text to parse
 * @param string $lid  Language ID (LgID from languages table)
 * @param int    $id   References whether the text is new to the database
 *                     $id = -1     => Check, return protocol
 *                     $id = -2     => Only return sentence array
 *                     $id = TextID => Split: insert sentences/textitems entries in DB
 */
function splitCheckText($text, $lid, $id) 
{
    global $tbpref;
    $wo = $nw = $mw = $wl = array();
    $wl_max = 0;
    $set_wo_sql = $set_wo_sql_2 = $del_wo_sql = $init_var = $mw_sql = $sql = '';
    $sql = "SELECT * FROM " . $tbpref . "languages WHERE LgID=" . $lid;
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    if ($record == false) { 
        my_die("Language data not found: $sql"); 
    }
    $removeSpaces = $record['LgRemoveSpaces'];
    $splitEachChar = $record['LgSplitEachChar'];
    $splitSentence = $record['LgRegexpSplitSentences'];
    $noSentenceEnd = $record['LgExceptionsSplitSentences'];
    $termchar = $record['LgRegexpWordCharacters'];
    $replace = explode("|", $record['LgCharacterSubstitutions']);
    $rtlScript = $record['LgRightToLeft'];
    mysqli_free_result($res);
    $s = prepare_textdata($text);
    //if(is_callable('normalizer_normalize')) $s = normalizer_normalize($s);

    $file_name = sys_get_temp_dir() . "/" . $tbpref . "tmpti.txt";
    do_mysqli_query('TRUNCATE TABLE ' . $tbpref . 'temptextitems');

    $s = str_replace(array('}','{'), array(']','['), $s);    // because of sent. spc. char
    foreach ($replace as $value) {
        $fromto = explode("=", trim($value));
        if(count($fromto) >= 2) {
            $s = str_replace(trim($fromto[0]), trim($fromto[1]), $s);
        }
    }

    if ('MECAB'== strtoupper(trim($termchar))) {
        //$mecab_args = ' -F %m\\t%F-[0,1,2,3]\\n -U %m\\t%F-[0,1,2,3]\\n -E ¶\\t記号-句点\\n ';
        $mecab_args = ' -F %m\\t%t\\t%h\\n -U %m\\t%t\\t%h\\n -E EOS\\t3\\t7\\n ';
        $mecab = get_mecab_path($mecab_args);
        $s = preg_replace('/[ \t]+/u', ' ', $s);
        $s = trim($s);
        if ($id == -1) { echo "<div id=\"check_text\" style=\"margin-right:50px;\"><h4>Text</h4><p>" . str_replace("\n", "<br /><br />", tohtml($s)). "</p>"; 
        }
        $handle = popen($mecab .' -o ' . $file_name, 'w');
        $write = fwrite($handle, $s);
        pclose($handle);

        runsql(
            "CREATE TEMPORARY TABLE IF NOT EXISTS " . $tbpref . "temptextitems2
             (TiCount smallint(5) unsigned NOT NULL,
             TiSeID mediumint(8) unsigned NOT NULL,
             TiOrder smallint(5) unsigned NOT NULL,
             TiWordCount tinyint(3) unsigned NOT NULL,
             TiText varchar(250) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL
            ) DEFAULT CHARSET=utf8", 
            ''
        );
        do_mysqli_query('SET @a:=0, @g:=0, @s:=' . ($id>0?'(SELECT ifnull(max(`SeID`)+1,1) FROM `' . $tbpref . 'sentences`)':1) . ',@d:=0,@h:=0,@i:=0;');
        $delim = '\n';
        //$sql= 'LOAD DATA LOCAL INFILE ' . convert_string_to_sqlsyntax($file_name) . ' INTO TABLE ' . $tbpref . 'temptextitems2 FIELDS TERMINATED BY \'\\t\' LINES TERMINATED BY \'' . $delim . '\' (@c,@f) set TiSeID = if(@g=2 OR @c="¶",@s:=@s+(@d:=@h)+1,@s), TiCount = (@d:=@d+CHAR_LENGTH(@c))+1-CHAR_LENGTH(@c), TiOrder = if(case when @f like \'記号-句点\' then @g:=2  when @f like \'記号%\' then @g:=1 when @f like \'名詞-数\' then @g:=1 when @c rlike \'[0-9a-zA-Z]+\' then @g:=1 else @g:=@h end is null, null, @a:=@a+if((@i=1) and (@g=1),0,1)+if((@i=0) and (@g=0),1,0)), TiText = @c, TiWordCount= case when (@i:=@g) is NULL then NULL when @g=0 then 1 else 0 end';
        $sql 
        = 'LOAD DATA LOCAL INFILE ' . convert_string_to_sqlsyntax($file_name) . '
         INTO TABLE ' . $tbpref . 'temptextitems2
         FIELDS TERMINATED BY \'\\t\' LINES
         TERMINATED BY \'' . $delim . '\' (@c,@e,@f)
         SET TiSeID = if(@g=2 or (@f="7" and @c="EOS"), @s:=@s+(@d:=@h)+1,@s),
          TiCount = (@d:=@d+CHAR_LENGTH(@c))+1-CHAR_LENGTH(@c),
          TiOrder = if(
            CASE
                WHEN @f = \'7\' then if(@c="EOS",(@g:=2) and (@c:="¶"),@g:=2) 
                WHEN LOCATE(@e,\'267\') then @g:=@h else @g:=1 end is null, null, @a:=@a+if((@i=1) and (@g=1),0,1)+if((@i=0) and (@g=0),1,0)), TiText = @c, TiWordCount=
                    CASE 
                        WHEN (@i:=@g) IS NULL THEN NULL
                        WHEN @g=0 THEN 1 ELSE 0 
                    END';
        do_mysqli_query($sql);
        do_mysqli_query('DELETE FROM ' . $tbpref . 'temptextitems2 WHERE TiOrder=@a');
        do_mysqli_query('INSERT INTO ' . $tbpref . 'temptextitems (TiCount, TiSeID, TiOrder, TiWordCount, TiText) SELECT min(TiCount) s, TiSeID, TiOrder, TiWordCount, group_concat(TiText order by TiCount SEPARATOR \'\') FROM ' . $tbpref . 'temptextitems2 WHERE 1 group by TiOrder');
        do_mysqli_query('DROP TABLE ' . $tbpref . 'temptextitems2');
    }
    else{
        $s = str_replace("\n", " ¶", $s);
        $s = trim($s);
        if ($splitEachChar) {
            $s = preg_replace('/([^\s])/u', "$1\t", $s);
        }
        $s = preg_replace('/\s+/u', ' ', $s);
        if ($id == -1) { echo "<div id=\"check_text\" style=\"margin-right:50px;\"><h4>Text</h4><p " .  ($rtlScript ? 'dir="rtl"' : '') . ">" . str_replace("¶", "<br /><br />", tohtml($s)). "</p>"; 
        }
        //    "\r" => Sentence delimiter, "\t" and "\n" => Word delimiter
        $s = preg_replace_callback(
            "/(\S+)\s*((\.+)|([$splitSentence]))([]'`\"”)‘’‹›“„«»』」]*)(?=(\s*)(\S+|$))/u", function ($matches) use ($noSentenceEnd) {
                //var_dump($matches);
                if (!strlen($matches[6]) && strlen($matches[7]) && preg_match('/[a-zA-Z0-9]/', substr($matches[1], -1))) { 
                    return preg_replace("/[.]/", ".\t", $matches[0]); 
                }
                if (is_numeric($matches[1])) {
                    if (strlen($matches[1])<3) { 
                        return $matches[0]; 
                    }
                }
                else if ($matches[3] && (preg_match('/^[B-DF-HJ-NP-TV-XZb-df-hj-np-tv-xz][b-df-hj-np-tv-xzñ]*$/u', $matches[1]) || preg_match('/^[AEIOUY]$/', $matches[1]))) { 
                    return $matches[0]; 
                }
                if (preg_match('/[.:]/', $matches[2])) {
                    if(preg_match('/^[a-z]/', $matches[7])) {
                        return $matches[0]; 
                    }
                }
                if ($noSentenceEnd != '' && preg_match('/^(' . $noSentenceEnd . ')$/', $matches[0])) {
                    return $matches[0]; 
                }
                return $matches[0]."\r";
            }, $s
        );
        $s = str_replace(array("¶"," ¶"), array("¶\r","\r¶"), $s);
        $s = preg_replace(array('/([^' . $termchar . '])/u','/\n([' . $splitSentence . '][\'`"”)\]‘’‹›“„«»』」]*)\n\t/u','/([0-9])[\n]([:.,])[\n]([0-9])/u'), array("\n$1\n","$1","$1$2$3"), $s);
        if($id == -2) {
            return explode("\r", remove_spaces(str_replace(array("\r\r","\t","\n"), array("\r","",""), $s), $removeSpaces));
        }

        $fp = fopen($file_name, 'w');
        fwrite($fp, remove_spaces(preg_replace("/(\n|^)(?!1\t)/u", "\n0\t", trim(preg_replace(array("/\r(?=[]'`\"”)‘’‹›“„«»』」 ]*\r)/u",'/[\n]+\r/u','/\r([^\n])/u',"/\n[.](?![]'`\"”)‘’‹›“„«»』」]*\r)/u","/(\n|^)(?=.?[$termchar][^\n]*\n)/u"), array("","\r","\r\n$1",".\n","\n1\t"), str_replace(array("\t","\n\n"), array("\n",""), $s)))), $removeSpaces));
        fclose($fp);
        do_mysqli_query('SET @a=0, @b=' . ($id>0?'(SELECT ifnull(max(`SeID`)+1,1) FROM `' . $tbpref . 'sentences`)':1) . ',@d=0,@e=0;');
        $sql= 'LOAD DATA LOCAL INFILE '. convert_string_to_sqlsyntax($file_name) . ' INTO TABLE ' . $tbpref . 'temptextitems FIELDS TERMINATED BY \'\\t\' LINES TERMINATED BY \'\\n\' (@w,@c) set TiSeID = @b, TiCount = (@d:=@d+CHAR_LENGTH(@c))+1-CHAR_LENGTH(@c), TiOrder = if(@c like "%\\r",case when (@c:=REPLACE(@c,"\\r","")) is NULL then NULL when (@b:=@b+1) is NULL then NULL when @d:= @e is NULL then NULL else @a:=@a+1 end, @a:=@a+1), TiText = @c,TiWordCount=@w';
        do_mysqli_query($sql);
    }
    unlink($file_name);

    if ($id==-1) {//check text
    
        $res = do_mysqli_query('SELECT GROUP_CONCAT(TiText order by TiOrder SEPARATOR "") Sent FROM ' . $tbpref . 'temptextitems group by TiSeID');
        echo '<h4>Sentences</h4><ol>';
        while($record = mysqli_fetch_assoc($res)){
            echo "<li>" . tohtml($record['Sent']) . "</li>";
        }
        mysqli_free_result($res);
        echo '</ol>';
        $res = do_mysqli_query('SELECT count(`TiOrder`) cnt, if(0=TiWordCount,0,1) as len, lower(TiText) as word, WoTranslation from ' . $tbpref . 'temptextitems left join ' . $tbpref . 'words on lower(TiText)=WoTextLC and WoLgID=' . $lid . ' group by lower(TiText)');
        while($record = mysqli_fetch_assoc($res)){
            if($record['len']==1) {
                $wo[]= array(tohtml($record['word']),$record['cnt'],tohtml($record['WoTranslation']));
            }
            else{
                $nw[]= array(tohtml($record['word']),tohtml($record['cnt']));
            }
        }
        mysqli_free_result($res);
        echo "<script type=\"text/javascript\">\nWORDS = ", json_encode($wo), ";\nNOWORDS = ", json_encode($nw), ";\n</script>";
    }//check text end

    $res = do_mysqli_query("SELECT WoWordCount as len, count(WoWordCount) as cnt FROM " . $tbpref . "words where WoLgID = " . $lid . " and WoWordCount > 1 group by WoWordCount");
    while($record = mysqli_fetch_assoc($res)){
        if($wl_max < $record['len']) { $wl_max = $record['len']; 
        }
        $wl[] = $record['len'];
        $mw_sql .= ' WHEN ' . $record['len'] . ' THEN @a' . ($record['len'] * 2 - 1);
    }
    mysqli_free_result($res);
    $sql = '';
    if(!empty($wl)) {//text has expressions
        do_mysqli_query('SET GLOBAL max_heap_table_size = 1024 * 1024 * 1024 * 2');
        do_mysqli_query('SET GLOBAL tmp_table_size = 1024 * 1024 * 1024 * 2');
        for ($i=$wl_max*2 -1; $i>1; $i--) {
            $set_wo_sql .= 'WHEN (@a' . strval($i) . ':=@a' . strval($i-1) . ') IS NULL THEN NULL ';
            $set_wo_sql_2 .= 'WHEN (@a' . strval($i) . ':=@a' . strval($i-2) . ') IS NULL THEN NULL ';
            $del_wo_sql .= 'WHEN (@a' . strval($i) . ':=@a0) IS NULL THEN NULL ';
            $init_var .= '@a' . strval($i) . '=0,';
        }
        do_mysqli_query('set ' . $init_var . '@a1=0,@a0=0,@b=0,@c="",@d=0,@e=0,@f="",@h=0;');
        do_mysqli_query('CREATE TEMPORARY TABLE IF NOT EXISTS ' . $tbpref . 'numbers( n  tinyint(3) unsigned NOT NULL);');
        do_mysqli_query('TRUNCATE TABLE ' . $tbpref . 'numbers');
        do_mysqli_query('INSERT IGNORE INTO ' . $tbpref . 'numbers(n) VALUES (' . implode('),(', $wl) . ');');
        $sql = (($id>0)?'SELECT straight_join WoID, sent, TiOrder - (2*(n-1)) TiOrder, n TiWordCount,word':'SELECT straight_join count(WoID) cnt, n as len, lower(WoText) as word, WoTranslation');
        $sql .= ' FROM (SELECT straight_join if(@b=TiSeID and @h=TiOrder,if((@h:=TiOrder+@a0) is null,TiSeID,TiSeID),if(@b=TiSeID, IF((@d=1) and (0<>TiWordCount), CASE ' . $set_wo_sql_2 . ' WHEN (@a1:=TiCount+@a0) IS NULL THEN NULL WHEN (@b:=TiSeID+@a0) IS NULL THEN NULL WHEN (@h:=TiOrder+@a0) IS NULL THEN NULL WHEN (@c:=concat(@c,TiText)) IS NULL THEN NULL WHEN (@d:=(0<>TiWordCount)+@a0) IS NULL THEN NULL ELSE TiSeID END, CASE ' . $set_wo_sql . ' WHEN (@a1:=TiCount+@a0) IS NULL THEN NULL WHEN (@b:=TiSeID+@a0) IS NULL THEN NULL WHEN (@h:=TiOrder+@a0) IS NULL THEN NULL WHEN (@c:=concat(@c,TiText)) IS NULL THEN NULL WHEN (@d:=(0<>TiWordCount)+@a0) IS NULL THEN NULL ELSE TiSeID END), CASE '  . $del_wo_sql . ' WHEN (@a1:=TiCount+@a0) IS NULL THEN NULL WHEN (@b:=TiSeID+@a0) IS NULL THEN NULL WHEN (@h:=TiOrder+@a0) IS NULL THEN NULL WHEN (@c:=concat(TiText,@f)) IS NULL THEN NULL WHEN (@d:=(0<>TiWordCount)+@a0) IS NULL THEN NULL ELSE TiSeID END)) sent, if(@d=0,NULL,if(CRC32(@z:=substr(@c,case n' . $mw_sql . ' end))<>CRC32(lower(@z)),@z,"")) word,if(@d=0 or ""=@z,NULL,lower(@z)) lword, TiOrder,n FROM ' . $tbpref . 'numbers , ' . $tbpref . 'temptextitems) ti, ' . $tbpref . 'words where lword is not null and WoLgID=' . $lid . ' and WoTextLC=lword and WoWordCount=n' . (($id>0)?' union all ':' group by WoID order by WoTextLC');
    }//text has expressions end
    if($id>0) {
        do_mysqli_query('ALTER TABLE ' . $tbpref . 'textitems2 ALTER Ti2LgID SET DEFAULT ' . $lid . ', ALTER Ti2TxID SET DEFAULT ' . $id);
        do_mysqli_query('insert into ' . $tbpref . 'textitems2 (Ti2WoID, Ti2SeID, Ti2Order, Ti2WordCount, Ti2Text) ' . $sql . 'select  WoID, TiSeID, TiOrder, TiWordCount, TiText FROM ' . $tbpref . 'temptextitems left join ' . $tbpref . 'words on lower(TiText) = WoTextLC and TiWordCount=1 and WoLgID = ' . $lid . ' order by TiOrder,TiWordCount');
        do_mysqli_query('ALTER TABLE ' . $tbpref . 'sentences ALTER SeLgID SET DEFAULT ' . $lid . ', ALTER SeTxID SET DEFAULT ' . $id);
        do_mysqli_query('set @a=0;');
        do_mysqli_query('INSERT INTO ' . $tbpref . 'sentences ( SeOrder, SeFirstPos, SeText) SELECT @a:=@a+1, min(if(TiWordCount=0,TiOrder+1,TiOrder)),GROUP_CONCAT(TiText order by TiOrder SEPARATOR "") FROM ' . $tbpref . 'temptextitems group by TiSeID');
        do_mysqli_query('ALTER TABLE ' . $tbpref . 'textitems2 ALTER Ti2LgID DROP DEFAULT, ALTER Ti2TxID DROP DEFAULT');
        do_mysqli_query('ALTER TABLE ' . $tbpref . 'sentences ALTER SeLgID DROP DEFAULT, ALTER SeTxID DROP DEFAULT');
    }
    if($id==-1) {//check text
        if(!empty($wl)) {
            $res = do_mysqli_query($sql);
            while($record = mysqli_fetch_assoc($res)){
                $mw[]= array(tohtml($record['word']),$record['cnt'],tohtml($record['WoTranslation']));
            }
            mysqli_free_result($res);
        }
        echo "<script type=\"text/javascript\">\nMWORDS = ", json_encode($mw), ";\n";
        if($rtlScript) {
            echo '$(function() {$("li").attr("dir","rtl");});';
        }
        ?>
   h='<h4>Word List <span class="red2">(red = already saved)</span></h4><ul class="wordlist">';
   $.each(WORDS,function(k,v){h+= '<li><span' + (v[2]==""?"":' class="red2"') + '>[' + v[0] + '] — ' + v[1] + (v[2]==""?"":' — ' + v[2]) + '</span></li>';});
   $('#check_text').append(h);
   h='</ul><p>TOTAL: ' + WORDS.length +'</p><h4>Expression List</span></h4><ul class="expressionlist">';
   $.each(MWORDS,function(k,v){h+= '<li><span>[' + v[0] + '] — ' + v[1] + (v[2]==""?"":' — ' + v[2]) + '</span></li>';});
   $('#check_text').append(h);
   h='</ul><p>TOTAL: ' + MWORDS.length +'</p><h4>Non-Word List</span></h4><ul class="nonwordlist">';
   $.each(NOWORDS,function(k,v){h+= '<li>[' + v[0] + '] — ' + v[1] + '</li>';});
   $('#check_text').append(h + '</ul><p>TOTAL: ' + NOWORDS.length +'</p>');
   </script>

        <?php
    }//check text end
    do_mysqli_query('TRUNCATE TABLE ' . $tbpref . 'temptextitems');
}

/**
 * Insert an expression to the database using MeCab.
 * 
 * @param string $textlc 
 * @param string $lid    Language ID
 * @param string $wid    Word ID
 * @param int    $mode 
 * 
 * @global string $tbpref Table name prefix
 */
function insertExpressionFromMeCab($textlc, $lid, $wid, $len, $mode)
{
    global $tbpref;

    $db_to_mecab = sys_get_temp_dir() . "/" . $tbpref . "db_to_mecab.txt";
    $mecab_to_db = sys_get_temp_dir() . "/" . $tbpref . "mecab_to_db.txt";
    $mecab_args = ' -F %m\\t%t\\t%h\\n -U %m\\t%t\\t%h\\n -E EOS\\t3\\t7\\n ';
    $mecab_expr = '';
    /*if(!is_dir(sys_get_temp_dir() . "/lwt")) {
        mkdir(sys_get_temp_dir() . "/lwt", 0777);
        chmod(sys_get_temp_dir() . "/lwt", 0777);
    }*/
    if(file_exists($db_to_mecab)) { 
        unlink($db_to_mecab); 
    }
    $mecab = get_mecab_path($mecab_args);
    do_mysqli_query(
        'SELECT 0 SeID, 0 SeTxID, 0 SeFirstPos, ' . convert_string_to_sqlsyntax_notrim_nonull($textlc) . ' SeText 
        UNION SELECT SeID,SeTxID,SeFirstPos,SeText 
        FROM sentences 
        WHERE SeText 
        LIKE ' . convert_string_to_sqlsyntax_notrim_nonull('%' . $textlc . '%') . ' 
        INTO outfile ' . convert_string_to_sqlsyntax($db_to_mecab)
    );
    $handle = popen($mecab . $db_to_mecab, "r");
    $fp = fopen($mecab_to_db, 'w');
    if(!feof($handle)) {
        return;
    }

    while (!feof($handle)) {
        $row = fgets($handle, 4096);
        //$arr  = explode("ÿ名詞-数\t",$row , 4);
        //if(!empty($arr[3])) $sent = preg_replace(array('$ÿ記号[^\t]*\t$u','$ÿ名詞-数\t$u','$[0-9a-zA-Z]ÿ[^\t]*\t$u','$ÿ[^\t]*\t$u'),array('','','',"\t"), $arr[3]);
        $arr  = explode("4\t", $row, 4);
        if(!empty($arr[3])) { 
            $sent = preg_replace_callback(
                '([267])?\t[0-9]+$', 
                function ($matches) {
                    return isset($matches[1]) ? "\t" : "";
                }, 
                $arr[3]
            );
            if(empty($mecab_expr)) {
                $mecab_expr = trim($sent) . "\t";
            }
            else if(!empty($arr[0])) {
                $first_pos = str_replace('{', '', $arr[2]);
                while(($seek = mb_strrpos($sent, $mecab_expr))!==false){
                    $sentid = str_replace('{', '', $arr[0]);
                    $txtid = str_replace('{', '', $arr[1]);
                    $sent =  mb_substr($sent, 0, $seek);
                    $pos = ( mb_substr_count($sent, "\t") * 2) + $first_pos;
                    fwrite($fp, $txtid . "\t" . $sentid . "\t" . $pos . "\n");
                    if($mode==0 && $txtid==$_REQUEST["tid"]) {
                        $sid[$pos]=$sentid;
                        if(getSettingZeroOrOne('showallwords', 1)) {
                            $appendtext[$pos]='&nbsp;' . $len . '&nbsp';
                        }
                        else { $appendtext[$pos]= $textlc; 
                        }
                    }
                }
            }
        }
    }
    pclose($handle);
    fclose($fp);
    do_mysqli_query(
        'ALTER TABLE ' . $tbpref . 'textitems2
         ALTER Ti2WoID SET DEFAULT ' . $wid . ',
         ALTER Ti2LgID SET DEFAULT ' . $lid . ',
         ALTER Ti2WordCount SET DEFAULT ' . $len . ',
         ALTER Ti2Text SET DEFAULT ""'
    );
    do_mysqli_query(
        'LOAD DATA LOCAL INFILE ' . convert_string_to_sqlsyntax($mecab_to_db) . '
         INTO TABLE ' . $tbpref . 'textitems2 (Ti2TxID,Ti2SeID,Ti2Order)'
    );
    do_mysqli_query(
        'ALTER TABLE ' . $tbpref . 'textitems2
         ALTER Ti2WoID DROP DEFAULT,
         ALTER Ti2LgID DROP DEFAULT,
         ALTER Ti2WordCount DROP DEFAULT,
         ALTER Ti2Text DROP DEFAULT'
    );
    unlink($mecab_to_db);
    unlink($db_to_mecab);
    
}


/**
 * Prepare a JavaScript dialog to insert a new expression
 */
function new_expression_interactable($hex, $appendtext, $sid, $len) 
{
    //$attrs = ' class="click mword ' . (getSettingZeroOrOne('showallwords', 1) ? 'm':'') . 'wsty'
    //$attrs .= ' TERM' . $hex . ' word + woid +  status' + status + '" data_trans="' + trans + '" data_rom="' + roman + '" data_code="' . $len '. " data_status="' + status + '" data_wid="' + woid + '" title="' + title + '"';
    $showAllWords = json_encode(getSettingZeroOrOne('showallwords', 1) ? false : true);
    ?>
<script type="text/javascript">
    newExpressionInteractable(
        <?php echo json_encode($appendtext); ?>, 
        //?php echo json_encode($sid); ?>,
        ' class="click mword <?php echo $showAllWords?'':'m'; ?>wsty TERM<?php echo $hex; ?> word' + 
        woid + ' status' + status + '" data_trans="' + trans + '" data_rom="' + 
        roman + '" data_code="<?php echo $len; ?>" data_status="' + 
        status + '" data_wid="' + woid + 
        '" title="' + title + '"',
        <?php echo json_encode($hex); ?>,
        <?php echo json_encode($len); ?>, 
        <?php echo $showAllWords; ?>
        );
    <?php /*
    var obj = <?php echo json_encode($appendtext); ?>;
    var sid = <?php echo json_encode($sid); ?>;
    var attrs = ' class="click mword <?php echo getSettingZeroOrOne('showallwords', 1)?'m':''; ?>wsty TERM<?php echo $hex; ?> word' + woid + ' status' + status + '" data_trans="' + trans + '" data_rom="' + roman + '" data_code="<?php echo $len; ?>" data_status="' + status + '" data_wid="' + woid + '" title="' + title + '"';
    for( key in obj ) {
    var text_refresh = 0;
    if($('span[id^="ID-'+ key +'-"]', context).not(".hide").length ){if(!($('span[id^="ID-'+ key +'-"]', context).not(".hide").attr('data_code')><?php echo $len; ?>)){text_refresh = 1;}}
    $('#ID-' + key + '-' + <?php
    echo prepare_textdata_js($len); ?>, context).remove();
    var i = '';
    for(j=<?php echo $len - 1; ?>;j>0;j=j-1){
    if(j==1)i='#ID-' + key + '-1';
    if($('#ID-' + key + '-' + j,context).length){
    i = '#ID-' + key + '-' + j;
    break;
    }
    }
    var ord_class='order' + key;
    $(i, context).before('<span id="ID-' + key + '-' + <?php
    echo prepare_textdata_js($len); ?> + '"' + attrs + '>' + obj[ key ] + '</span>');
    el = $('#ID-' + key + '-' + <?php
    echo prepare_textdata_js($len); ?>, context);
    el.addClass(ord_class).attr('data_order',key);
    var txt = el.nextUntil($('#ID-' + (parseInt(key) + <?php echo $len * 2 -1; ?>) + '-1', context),'[id$="-1"]').map(function() {return $( this ).text();}).get().join( "" );
    var pos = $('#ID-' + key + '-1', context).attr('data_pos');
    el.attr('data_text',txt).attr('data_pos',pos);
    <?php if(!getSettingZeroOrOne('showallwords', 1)) { ?>
    if(text_refresh == 1){
        refresh_text(el);
    }else el.addClass('hide');
    <?php 
    } ?>
    }*/?>
 </script>
    <?php
    flush();
}

/**
 * Alter the database when to add a new word
 * 
 * @param  string $textlc 
 * @param  string $lid    Language ID
 * @param  string $len
 * @param  int    $mode   Function mode
 *                        - 0: Default mode, do nothing special
 *                        - 1: Runs an expresion inserter interactable 
 *                        - 2: Return the sql output
 * @global string $tbpref Table name prefix
 */
function insertExpressions($textlc, $lid, $wid, $len, $mode) 
{
    global $tbpref;
    $wis = $textlc;
    if ($mode == 0) { 
        $hex = strToClassName(prepare_textdata($textlc)); 
    }
    $sql = "select * from " . $tbpref . "languages where LgID=" . $lid;
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    $termchar = $record['LgRegexpWordCharacters'];
    $splitEachChar = $record['LgSplitEachChar'];
    $removeSpaces = $record["LgRemoveSpaces"];
    $rtlScript = $record['LgRightToLeft'];
    mysqli_free_result($res);
    $appendtext = array();
    $sid = array();
    $sqlarr = array();
    if ($splitEachChar) {
        $textlc = preg_replace('/([^\s])/u', "$1 ", $textlc);
    }
    if ($removeSpaces==1 && $splitEachChar==0) {
        $rSflag = '';
    }

    if ('MECAB'== strtoupper(trim($termchar))) {
        insertExpressionFromMeCab($textlc, $lid, $wid, $len, $mode);
    }
    else{
        $ti=array();
        if($removeSpaces==1 && $splitEachChar==0) {
            $sql = "SELECT group_concat(Ti2Text order by Ti2Order SEPARATOR ' ') AS SeText, SeID, SeTxID, SeFirstPos FROM " . $tbpref . "textitems2," . $tbpref . "sentences where SeID=Ti2SeID and SeLgID = " . $lid . " and Ti2LgID = " . $lid . " and SeText like " . convert_string_to_sqlsyntax_notrim_nonull("%" .  $wis . "%") . " and Ti2WordCount < 2 group by SeID";
        }
        else {
            $sql = "SELECT * FROM " . $tbpref . "sentences where SeLgID = " . $lid . " and SeText like " . convert_string_to_sqlsyntax_notrim_nonull("%" .  $wis . "%");
        }
        $res=do_mysqli_query($sql);
        $notermchar='/[^' . $termchar . '](' . $textlc . ')[^' . $termchar . ']/ui';
        while($record = mysqli_fetch_assoc($res)){
            $string = ' ' . ($splitEachChar?preg_replace('/([^\s])/u', "$1 ", $record['SeText']):$record['SeText']) . ' ';
            if($removeSpaces==1 && $splitEachChar==0) {
                if(empty($rSflag)) {
                    $rSflag = preg_match('/(?<=[ ])(' . preg_replace('/(.)/ui', "$1[ ]*", $textlc) . ')(?=[ ])/ui', $string, $ma);
                    if(!empty($ma[1])) {
                        $textlc = trim($ma[1]);
                        $notermchar='/[^' . $termchar . '](' . $textlc . ')[^' . $termchar . ']/ui';
                    }
                }
            }
            $txtid = $record['SeTxID'];
            $sentid = $record['SeID'];
            $last_pos = mb_strripos($string, $textlc, 0,  'UTF-8');
            while($last_pos!==false){
                $matches=array();
                if($splitEachChar || $removeSpaces || preg_match($notermchar, '  ' . $string, $matches, 0, $last_pos - 1)==1) {
                    $string = mb_substr($string, 0, $last_pos, 'UTF-8');
                    $cnt = preg_match_all('/([' . $termchar . ']+)/u', $string, $ma);
                    $pos=2*$cnt+$record['SeFirstPos'];
                    $txt='';
                    if($matches[1]!=$textlc) { $txt=$splitEachChar?$wis:$matches[1]; 
                    }
                    $sqlarr[] = '(' . $wid . ',' . $lid . ',' . $txtid . ',' . $sentid . ',' . $pos . ',' . $len . ',' . convert_string_to_sqlsyntax_notrim_nonull($txt) . ')';
                    if($mode==0 && $txtid==$_REQUEST["tid"]) {
                        $sid[$pos]=$record['SeID'];
                        if(getSettingZeroOrOne('showallwords', 1)) {
                            $appendtext[$pos]='&nbsp;' . $len . '&nbsp';
                        }
                        else { $appendtext[$pos]=$splitEachChar || $removeSpaces?$wis:$matches[1]; 
                        }
                    }
                }
                else { $string = mb_substr($string, 0, $last_pos, 'UTF-8'); 
                }
                $last_pos = mb_strripos($string, $textlc, 0,  'UTF-8');
            }
        }
        mysqli_free_result($res);
    }
    if(!empty($sqlarr)) {
        $sqltext = '';
        if ($mode != 2) {
            $sqltext .= 
            'INSERT INTO ' . $tbpref . 'textitems2
             (Ti2WoID,Ti2LgID,Ti2TxID,Ti2SeID,Ti2Order,Ti2WordCount,Ti2Text)
             VALUES ';
        }
        $sqltext .= rtrim(implode(',', $sqlarr), ',');
        unset($sqlarr);
    }

    if ($mode == 0) {
        new_expression_interactable($hex, $appendtext, $sid, $len);
    }
    if ($mode == 2) { 
        return $sqltext; 
    }
    if (isset($sqltext)) {
        do_mysqli_query($sqltext);
    }
}

// -------------------------------------------------------------

function restore_file($handle, $title) 
{
    global $tbpref;
    global $debug;
    global $dbname;
    $message = "";
    $lines = 0;
    $ok = 0;
    $errors = 0;
    $drops = 0;
    $inserts = 0;
    $creates = 0;
    $start = 1;
    while (! gzeof($handle)) {
        $sql_line = trim(
            str_replace(
                "\r", "",
                str_replace(
                    "\n", "",
                    gzgets($handle, 99999)
                )
            )
        );
        if ($sql_line != "") {
            if($start) {
                if (strpos($sql_line, "-- lwt-backup-") === false and strpos($sql_line, "-- lwt-exp_version-backup-") === false) {
                    $message = "Error: Invalid " . $title . " Restore file (possibly not created by LWT backup)";
                    $errors = 1;
                    break;
                }
                $start = 0;
                continue;
            }
            if (substr($sql_line, 0, 3) !== '-- ' ) {
                $res = do_mysqli_query(insert_prefix_in_sql($sql_line)); // merge conflict
                $res = mysqli_query($GLOBALS['DBCONNECTION'], insert_prefix_in_sql($sql_line));
                $lines++;
                if ($res == false) { $errors++; 
                }
                else {
                    $ok++;
                    if (substr($sql_line, 0, 11) == "INSERT INTO") { $inserts++; 
                    }
                    elseif (substr($sql_line, 0, 10) == "DROP TABLE") { $drops++;
                    } elseif (substr($sql_line, 0, 12) == "CREATE TABLE") { $creates++;
                    }
                }
                // echo $ok . " / " . tohtml(insert_prefix_in_sql($sql_line)) . "<br />";
            }
        }
    } // while (! feof($handle))
    gzclose($handle);
    if ($errors == 0) {
        runsql('DROP TABLE IF EXISTS ' . $tbpref . 'textitems', '');
        check_update_db($debug, $tbpref, $dbname);
        reparse_all_texts();
        optimizedb();
        get_tags($refresh = 1);
        get_texttags($refresh = 1);
        $message = "Success: " . $title . " restored - " .
        $lines . " queries - " . $ok . " successful (" . $drops . "/" . $creates . " tables dropped/created, " . $inserts . " records added), " . $errors . " failed.";
    } else {
        if ($message == "") {
            $message = "Error: " . $title . " NOT restored - " .
            $lines . " queries - " . $ok . " successful (" . $drops . "/" . $creates . " tables dropped/created, " . $inserts . " records added), " . $errors . " failed.";
        }
    }
    return $message;
}


// -------------------------------------------------------------

function set_word_count() 
{
    global $tbpref;
    $sqlarr = array();
    $i=0;
    $min=0;
    $max=0;

    if (get_first_value('SELECT (@m := group_concat(LgID)) value FROM ' . $tbpref . 'languages WHERE UPPER(LgRegexpWordCharacters)="MECAB"')) {
        $db_to_mecab = sys_get_temp_dir() . "/" . $tbpref . "db_to_mecab.txt";
        $mecab_to_db = sys_get_temp_dir() . "/" . $tbpref . "mecab_to_db.txt";
        $mecab_args = ' -F %m%t\\t -U %m%t\\t -E \\n ';
        /*if(!is_dir(sys_get_temp_dir() . "/lwt")) {
            mkdir(sys_get_temp_dir() . "/lwt", 0777);
            chmod(sys_get_temp_dir() . "/lwt", 0777);
        }*/
        if (file_exists($db_to_mecab)) { 
            unlink($db_to_mecab); 
        }

        $mecab = get_mecab_path($mecab_args);

        do_mysqli_query(
            'SELECT WoID, WoTextLC FROM ' . $tbpref . 'words 
            WHERE WoLgID in(@m) AND WoWordCount = 0 
            into outfile ' . convert_string_to_sqlsyntax($db_to_mecab)
        );
        $handle = popen($mecab . $db_to_mecab, "r");
        $fp = fopen($mecab_to_db, 'w');
        if (!feof($handle)) {
            while (!feof($handle)) {
                $row = fgets($handle, 1024);
                $arr  = explode("4\t", $row, 2);
                //var_dump($arr);
                if (!empty($arr[1])) {
                    $cnt = substr_count(preg_replace('$[^267]\t$u', '', $arr[1]), "\t");
                    if(empty($cnt)) { $cnt =1; 
                    }
                    fwrite($fp, $arr[0] . "\t" . $cnt . "\n");
                }
            }
            pclose($handle);
            fclose($fp);
            do_mysqli_query('CREATE TEMPORARY TABLE ' . $tbpref . 'mecab ( MID mediumint(8) unsigned NOT NULL, MWordCount tinyint(3) unsigned NOT NULL, PRIMARY KEY (MID)) CHARSET=utf8');
            do_mysqli_query('LOAD DATA LOCAL INFILE ' . convert_string_to_sqlsyntax($mecab_to_db) . ' INTO TABLE ' . $tbpref . 'mecab (MID, MWordCount)');
            do_mysqli_query('UPDATE ' . $tbpref . 'words join ' . $tbpref . 'mecab on MID = WoID SET WoWordCount = MWordCount');
            do_mysqli_query('DROP TABLE ' . $tbpref . 'mecab');

            unlink($mecab_to_db);
            unlink($db_to_mecab);
        }
    }
    $sql= "select WoID, WoTextLC, LgRegexpWordCharacters, LgSplitEachChar from " . $tbpref . "words, " . $tbpref . "languages where WoWordCount=0 and WoLgID = LgID order by WoID";
    $result = do_mysqli_query($sql);
    while($rec = mysqli_fetch_assoc($result)){
        if ($rec['LgSplitEachChar']) {
            $textlc = preg_replace('/([^\s])/u', "$1 ", $rec['WoTextLC']);
        }
        else{
            $textlc = $rec['WoTextLC'];
        }
        $sqlarr[]= ' WHEN ' . $rec['WoID'] . ' THEN ' . preg_match_all('/([' . $rec['LgRegexpWordCharacters'] . ']+)/u', $textlc, $ma);
        if(++$i % 1000 == 0) {
            if(!empty($sqlarr)) {
                $max=$rec['WoID'];
                $sqltext = "UPDATE  " . $tbpref . "words SET WoWordCount  = CASE WoID";
                $sqltext .= implode(' ', $sqlarr) . ' END where WoWordCount=0 and WoID between ' . $min . ' and ' . $max;
                do_mysqli_query($sqltext);
                $min=$max;
            }
            $sqlarr = array();
        }
    }
    mysqli_free_result($result);
    if(!empty($sqlarr)) {
        $sqltext = "UPDATE  " . $tbpref . "words SET WoWordCount  = CASE WoID";
        $sqltext .= implode(' ', $sqlarr) . ' END where WoWordCount=0';
        do_mysqli_query($sqltext);
    }
}

// -------------------------------------------------------------

function recreate_save_ann($textid, $oldann) 
{
    global $tbpref;
    $newann = create_ann($textid);
    // Get the translations from $oldann:
    $oldtrans = array();
    $olditems = preg_split('/[\n]/u', $oldann);
    foreach ($olditems as $olditem) {
        $oldvals = preg_split('/[\t]/u', $olditem);
        if ($oldvals[0] > -1) {
            $trans = '';
            if (count($oldvals) > 3) { $trans = $oldvals[3]; 
            }
            $oldtrans[$oldvals[0] . "\t" . $oldvals[1]] = $trans;
        }
    }
    // Reset the translations from $oldann in $newann and rebuild in $ann:
    $newitems = preg_split('/[\n]/u', $newann);
    $ann = '';
    foreach ($newitems as $newitem) {
        $newvals = preg_split('/[\t]/u', $newitem);
        if ($newvals[0] > -1) {
            $key = $newvals[0] . "\t";
            if (isset($newvals[1])) { $key .= $newvals[1]; 
            }
            if (array_key_exists($key, $oldtrans)) {
                $newvals[3] = $oldtrans[$key];
            }
            $item = implode("\t", $newvals);
        } else {
            $item = $newitem;
        }
        $ann .= $item . "\n";
    }
    $dummy = runsql(
        'update ' . $tbpref . 'texts set ' .
        'TxAnnotatedText = ' . convert_string_to_sqlsyntax($ann) . ' where TxID = ' . $textid, ""
    );
    return get_first_value("select TxAnnotatedText as value from " . $tbpref . "texts where TxID = " . $textid);
}

// -------------------------------------------------------------

function create_ann($textid) 
{
    global $tbpref;
    $ann = '';
    $sql = 'select CASE WHEN Ti2WordCount>0 THEN Ti2WordCount ELSE 1 END as Code, CASE WHEN CHAR_LENGTH(Ti2Text)>0 THEN Ti2Text ELSE WoText END as TiText, Ti2Order, CASE WHEN Ti2WordCount > 0 THEN 0 ELSE 1 END as TiIsNotWord, WoID, WoTranslation from (' . $tbpref . 'textitems2 left join ' . $tbpref . 'words on (Ti2WoID = WoID) and (Ti2LgID = WoLgID)) where Ti2TxID = ' . $textid . ' order by Ti2Order asc, Ti2WordCount desc';
    $savenonterm = '';
    $saveterm = '';
    $savetrans = '';
    $savewordid = '';
    $until = 0;
    $res = do_mysqli_query($sql);
    while ($record = mysqli_fetch_assoc($res)) {
        $actcode = $record['Code'] + 0;
        $order = $record['Ti2Order'] + 0;
        if ($order <= $until ) {
            continue;
        }
        if ($order > $until ) {
            $ann = $ann . process_term($savenonterm, $saveterm, $savetrans, $savewordid, $order);
            $savenonterm = '';
            $saveterm = '';
            $savetrans = '';
            $savewordid = '';
            $until = $order;
        }
        if ($record['TiIsNotWord'] != 0) {
            $savenonterm = $savenonterm . $record['TiText'];
        }
        else {
            $until = $order + 2 * ($actcode-1);
            $saveterm = $record['TiText'];
            $savetrans = '';
            if(isset($record['WoID'])) {
                $savetrans = $record['WoTranslation'];
                $savewordid = $record['WoID'];
            }
        }
    } // while
    mysqli_free_result($res);
    $ann .= process_term($savenonterm, $saveterm, $savetrans, $savewordid, $order);
    return $ann;
}


// -------------------------------------------------------------

function insert_prefix_in_sql($sql_line) 
{
    global $tbpref;
    //                                 123456789012345678901
    if     (substr($sql_line, 0, 12) == "INSERT INTO ") {
        return substr($sql_line, 0, 12) . $tbpref . substr($sql_line, 12); 
    }
    elseif (substr($sql_line, 0, 21) == "DROP TABLE IF EXISTS ") {
        return substr($sql_line, 0, 21) . $tbpref . substr($sql_line, 21);
    } elseif (substr($sql_line, 0, 14) == "CREATE TABLE `") {
        return substr($sql_line, 0, 14) . $tbpref . substr($sql_line, 14);
    } elseif (substr($sql_line, 0, 13) == "CREATE TABLE ") {
        return substr($sql_line, 0, 13) . $tbpref . substr($sql_line, 13);
    } else {
        return $sql_line; 
    }
}

// -------------------------------------------------------------

function create_save_ann($textid) 
{
    global $tbpref;
    $ann = create_ann($textid);
    $dummy = runsql(
        'update ' . $tbpref . 'texts set ' .
        'TxAnnotatedText = ' . convert_string_to_sqlsyntax($ann) . ' where TxID = ' . $textid, ""
    );
    return get_first_value("select TxAnnotatedText as value from " . $tbpref . "texts where TxID = " . $textid);
}

// -------------------------------------------------------------

function process_term($nonterm, $term, $trans, $wordid, $line) 
{
    $r = '';
    if ($nonterm != '') { $r = $r . "-1\t" . $nonterm . "\n"; 
    }
    if ($term != '') { $r = $r . $line . "\t" . $term . "\t" . trim($wordid) . "\t" . get_first_translation($trans) . "\n"; 
    }
    return $r;
}

// -------------------------------------------------------------

function get_first_translation($trans) 
{
    $arr = preg_split('/[' . get_sepas()  . ']/u', $trans);
    if (count($arr) < 1) { return ''; 
    }
    $r = trim($arr[0]);
    if ($r == '*') { $r =""; 
    }
    return $r;
}

// -------------------------------------------------------------

function get_annotation_link($textid) 
{
    global $tbpref;
    if (get_first_value('select length(TxAnnotatedText) as value from ' . $tbpref . 'texts where TxID=' . $textid) > 0) { 
        return ' &nbsp;<a href="print_impr_text.php?text=' . $textid . '" target="_top"><img src="icn/tick.png" title="Annotated Text" alt="Annotated Text" /></a>'; 
    }
    else { 
        return ''; 
    }
}

/**
 * Like trim, but in place (modify variable)
 * 
 * @param string $value Value to be trimmed
 */
function trim_value(&$value) 
{ 
    $value = trim($value); 
}

/** 
 * Parses text be read by an automatic audio player.
 * 
 * Some non-phonetic alphabet will need this, currently only Japanese
 * is supported, using MeCaB.
 *
 * @param  string $text Text to be converted
 * @param  string $lang Language code (usually BCP 47 or ISO 639-1)
 * @return string Parsed text in a phonetic format.
 */
function phonetic_reading($text, $lang) 
{
    global $tbpref;
    // Many languages are already phonetic
    if ($lang != 'ja' && $lang != 'jp-JP' && $lang != 'jp' ) {
        return $text;
    }

    // Japanes is on exception
    $mecab_file = sys_get_temp_dir() . "/" . $tbpref . "mecab_to_db.txt";
    $mecab_args = ' -O yomi ';
    if (file_exists($mecab_file)) { 
        unlink($mecab_file); 
    }
    $fp = fopen($mecab_file, 'w');
    fwrite($fp, $text . "\n");
    fclose($fp);
    $mecab = get_mecab_path($mecab_args);
    $handle = popen($mecab . $mecab_file, "r");
    /// Output string
    $mecab_str = '';
    while (($line = fgets($handle, 4096)) !== false) {
        $mecab_str .= $line; 
    }
    if (!feof($handle)) {
        echo "Error: unexpected fgets() fail\n";
    }
    pclose($handle);
    unlink($mecab_file);
    return $mecab_str;
}


// -------------------------------------------------------------

function make_score_random_insert_update($type) 
{
    // $type='iv'/'id'/'u'
    if ($type == 'iv') {
        return ' WoTodayScore, WoTomorrowScore, WoRandom ';
    } elseif ($type == 'id') {
        return ' ' . getsqlscoreformula(2) . ', ' . getsqlscoreformula(3) . ', RAND() ';
    } elseif ($type == 'u') {
        return ' WoTodayScore = ' . getsqlscoreformula(2) . ', WoTomorrowScore = ' . getsqlscoreformula(3) . ', WoRandom = RAND() ';
    } else {
        return '';
    }
}

// -------------------------------------------------------------

function refreshText($word,$tid) 
{
    global $tbpref;
    // $word : only sentences with $word
    // $tid : textid
    // only to be used when $showAll = 0 !
    $out = '';
    $wordlc = trim(mb_strtolower($word, 'UTF-8'));
    if ($wordlc == '') { return ''; 
    }
    $sql = 'SELECT distinct TiSeID FROM ' . $tbpref . 'textitems WHERE TiIsNotWord = 0 and TiTextLC = ' . convert_string_to_sqlsyntax($wordlc) . ' and TiTxID = ' . $tid . ' order by TiSeID';
    $res = do_mysqli_query($sql);
    $inlist = '(';
    while ($record = mysqli_fetch_assoc($res)) { 
        if ($inlist == '(') { 
            $inlist .= $record['TiSeID']; 
        }
        else {
            $inlist .= ',' . $record['TiSeID']; 
        }
    }
    mysqli_free_result($res);
    if ($inlist == '(') { 
        return ''; 
    }
    else {
        $inlist =  ' where TiSeID in ' . $inlist . ') '; 
    }
    $sql = 'select TiWordCount as Code, TiOrder, TiIsNotWord, WoID from (' . $tbpref . 'textitems left join ' . $tbpref . 'words on (TiTextLC = WoTextLC) and (TiLgID = WoLgID)) ' . $inlist . ' order by TiOrder asc, TiWordCount desc';

    $res = do_mysqli_query($sql);        

    $hideuntil = -1;
    $hidetag = "removeClass('hide');";

    while ($record = mysqli_fetch_assoc($res)) {  // MAIN LOOP
        $actcode = $record['Code'] + 0;
        $order = $record['TiOrder'] + 0;
        $notword = $record['TiIsNotWord'] + 0;
        $termex = isset($record['WoID']);
        $spanid = 'ID-' . $order . '-' . $actcode;

        if ($hideuntil > 0 ) {
            if ($order <= $hideuntil ) {
                $hidetag = "addClass('hide');"; 
            }
            else {
                $hideuntil = -1;
                $hidetag = "removeClass('hide');";
            }
        }

        if ($notword != 0) {  // NOT A TERM
            $out .= "$('#" . $spanid . "',context)." . $hidetag . "\n";
        }  

        else {   // A TERM
            if ($actcode > 1) {   // A MULTIWORD FOUND
                if ($termex) {  // MULTIWORD FOUND - DISPLAY 
                    if ($hideuntil == -1) { $hideuntil = $order + ($actcode - 1) * 2; 
                    }
                    $out .= "$('#" . $spanid . "',context)." . $hidetag . "\n";
                }
                else {  // MULTIWORD PLACEHOLDER - NO DISPLAY 
                    $out .= "$('#" . $spanid . "',context).addClass('hide');\n";
                }  
            } // ($actcode > 1) -- A MULTIWORD FOUND

            else {  // ($actcode == 1)  -- A WORD FOUND
                $out .= "$('#" . $spanid . "',context)." . $hidetag . "\n";
            }  
        }
    } //  MAIN LOOP
    mysqli_free_result($res);
    return $out;
}

/** 
 * Create an HTML media player, audio or video.
 * 
 * @param string $path   URL or local file path
 * @param int    $offset Offset from the beginning of the video
 */ 
function makeMediaPlayer($path, $offset=0) 
{
    if ($path == '') {
        return;
    }
    /**
    * @var string $extension File extension (if exists) 
    */
    $extension = substr($path, -4);
    if ($extension == '.mp3' || $extension == '.wav' || $extension == '.ogg') {
        makeAudioPlayer($path, $offset);
    } else {
        makeVideoPlayer($path, $offset);
    }
}


/** 
 * Create an embed video player
 * 
 * @param string $path   URL or local file path
 * @param int    $offset Offset from the beginning of the video
 */ 
function makeVideoPlayer($path, $offset=0) 
{
    if (preg_match(
        "/(?:https:\/\/)?www\.youtube\.com\/watch\?v=([\d\w]+)/iu", 
        $path, $matches
    )
    ) {
        // Youtbe video
        $domain = "https://www.youtube.com/embed/";
        $id = $matches[1];
        $url = $domain . $id . "?t=" . $offset;
    } if (preg_match(
        "/(?:https:\/\/)?youtu\.be\/([\d\w]+)/iu", 
        $path, $matches
    )
    ) {
        // Youtbe video
        $domain = "https://www.youtube.com/embed/";
        $id = $matches[1];
        $url = $domain . $id . "?t=" . $offset;
    } else if (preg_match(
        "/(?:https:\/\/)?dai\.ly\/([^\?]+)/iu", 
        $path, $matches
    )
    ) {
        // Dailymotion
        $domain = "https://www.dailymotion.com/embed/video/";
        $id = $matches[1];
        $url = $domain . $id;
    } else if (preg_match(
        "/(?:https:\/\/)?vimeo\.com\/(\d+)/iu",
        // Vimeo 
        $path, $matches
    )
    ) {
        $domain = "https://player.vimeo.com/video/";
        $id = $matches[1];
        $url = $domain . $id . "#t=" . $offset . "s";
    } else {
        $url = $path;
    }
    ?> 
<iframe style="width: 100%; height: 30%;" 
src="<?php echo $url ?>" 
title="Video player"
frameborder="0" 
allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
allowfullscreen type="text/html">
</iframe>
    <?php
}


/** 
 * Create an HTML audio player.
 * 
 * @param string $audio  Audio URL
 * @param int    $offset Offset from the beginning of the video
 */ 
function makeAudioPlayer($audio, $offset=0) 
{
    if ($audio == '') {
        return;
    }
    $repeatMode = getSettingZeroOrOne('currentplayerrepeatmode', 0);
    ?>
<link type="text/css" href="<?php print_file_path('css/jplayer.css');?>" rel="stylesheet" />
<script type="text/javascript" src="js/jquery.jplayer.js"></script>
<table align="center" style="margin-top:5px;" cellspacing="0" cellpadding="0">
<tr>
<td class="center borderleft" style="padding-left:10px;">
<span id="do-single" class="click<?php echo ($repeatMode ? '' : ' hide'); ?>" 
    style="color:#09F;font-weight: bold;" title="Toggle Repeat (Now ON)">↻
</span>
<span id="do-repeat" class="click<?php echo ($repeatMode ? ' hide' : ''); ?>"
    style="color:grey;font-weight: bold;" title="Toggle Repeat (Now OFF)">↻</span>
<div id="playbackrateContainer" 
    style="font-size: 80%;position:relative;-webkit-touch-callout: none;-webkit-user-select: none;-khtml-user-select: none;-moz-user-select: none;-ms-user-select: none;user-select: none;">
</div>
</td>
<td class="center bordermiddle">&nbsp;</td>
<td class="bordermiddle">
<div id="jquery_jplayer_1" class="jp-jplayer">
</div>
<div class="jp-audio-container">
    <div id="jp_container_1" class="jp-audio">
        <div class="jp-type-single">
            <div id="jp_interface_1" class="jp-interface">
                <ul class="jp-controls">
                    <li><a href="#" class="jp-play">play</a></li>
                    <li><a href="#" class="jp-pause">pause</a></li>
                    <li><a href="#" class="jp-stop">stop</a></li>
                    <li><a href="#" class="jp-mute">mute</a></li>
                    <li><a href="#" class="jp-unmute">unmute</a></li>
                </ul>
                <div class="jp-progress-container">
                    <div class="jp-progress">
                        <div class="jp-seek-bar">
                            <div class="jp-play-bar">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="jp-volume-bar-container">
                    <div class="jp-volume-bar">
                        <div class="jp-volume-bar-value">
                        </div>
                    </div>
                </div>
                <div class="jp-current-time">
                </div>
                <div class="jp-duration">
                </div>

            </div>
            <div id="jp_playlist_1" class="jp-playlist">
            </div>
        </div>
    </div>
</div>
</td>
<td class="center bordermiddle">&nbsp;</td>
<td class="center bordermiddle">
    <?php
    $currentplayerseconds = getSetting('currentplayerseconds');
    if ($currentplayerseconds == '') { 
        $currentplayerseconds = 5; 
    }
    ?>
<!-- Not merge from master branch (with official)
<select id="backtime" name="backtime" onchange="{do_ajax_save_setting('currentplayerseconds',document.getElementById('backtime').options[document.getElementById('backtime').selectedIndex].value);}"><?php echo get_seconds_selectoptions($currentplayerseconds); ?></select><br />
<span id="backbutt" class="click" title="Rewind n seconds">⇤</span>&nbsp;&nbsp;<span id="forwbutt" class="click" title="Forward n seconds">⇥</span>
-->
<select id="backtime" name="backtime">
    <?php echo get_seconds_selectoptions($currentplayerseconds); ?>
</select><br />
<span id="backbutt" class="click">
    <img src="icn/arrow-circle-225-left.png" alt="Rewind n seconds" title="Rewind n seconds" />
</span>&nbsp;&nbsp;
<span id="forwbutt" class="click">
    <img src="icn/arrow-circle-315.png" alt="Forward n seconds" title="Forward n seconds" />
</span>
<span id="playTime" class="hide"></span>
</td>
<td class="center bordermiddle">&nbsp;</td>
<td class="center borderright" style="padding-right:10px;">
    <?php
    $currentplaybackrate = getSetting('currentplaybackrate');
    if ($currentplaybackrate == '') { 
        $currentplaybackrate = 10; 
    }
    ?>
<select id="playbackrate" name="playbackrate">
    <?php echo get_playbackrate_selectoptions($currentplaybackrate); ?>
</select><br />
<span id="slower" class="click">
    <img src="icn/minus.png" alt="Slower" title="Slower" style="margin-top:3px" />
</span>&nbsp;<span id="stdspeed" class="click">
    <img src="icn/status-away.png" alt="Normal" title="Normal" style="margin-top:3px" />
</span>&nbsp;<span id="faster" class="click">
    <img src="icn/plus.png" alt="Faster" title="Faster" style="margin-top:3px" />
</span>
</td>
</tr>
<!-- Audio controls before page loading -->
<script type="text/javascript" src="js/audio_controller.js"></script>
<!-- Audio controls once that page was loaded -->
<script type="text/javascript">
//<![CDATA[
    $(document).ready(function(){
      $("#jquery_jplayer_1").jPlayer({
    ready: function () {
      $(this).jPlayer("setMedia", { <?php 
        $audio = trim($audio);
        if (strcasecmp(substr($audio, -4), '.mp3') == 0) { 
            echo 'mp3: ' . prepare_textdata_js(encodeURI($audio)); 
        } elseif (strcasecmp(substr($audio, -4), '.ogg') == 0) { 
            echo 'oga: ' . prepare_textdata_js(encodeURI($audio))  . ", " . 
            'mp3: ' . prepare_textdata_js(encodeURI($audio)); 
        } elseif (strcasecmp(substr($audio, -4), '.wav') == 0) {
            echo 'wav: ' . prepare_textdata_js(encodeURI($audio))  . ", " . 
            'mp3: ' . prepare_textdata_js(encodeURI($audio)); 
        } else {
            echo 'mp3: ' . prepare_textdata_js(encodeURI($audio)); 
        }
        ?> }).jPlayer("pause",<?php echo $offset; ?>);
      if ($('#jquery_jplayer_1').data().jPlayer.status.playbackRateEnabled) {
          $("#playbackrateContainer").css("margin-top",".2em")
          .html('<span id="pbSlower" style="position:absolute;top: 0; left: 0; bottom: 0; right: 50%;" title="Slower" onclick="click_slower();">&nbsp;</span><span id="pbFaster" style="position:absolute;top: 0; left: 50%; bottom: 0; right: 0;" title="Faster" onclick="click_faster();">&nbsp;</span><span class="ui-widget ui-state-default ui-corner-all" style="padding-left: 0.2em;padding-right: 0.2em;color:grey"><span id="playbackSlower" style="padding-right: 0.15em;">≪</span><span id="pbvalue">1.0</span><span id="playbackFaster" style="padding-left: 0.15em;">≫</span></span>')
          .css("cursor","pointer");
      }
    },
    swfPath: "js",
    noVolume: {
        ipad: /^no$/, iphone: /^no$/, ipod: /^no$/, 
        android_pad: /^no$/, android_phone: /^no$/, 
        blackberry: /^no$/, windows_ce: /^no$/, iemobile: /^no$/, webos: /^no$/, 
        playbook: /^no$/
    }
  });

  $("#jquery_jplayer_1").bind($.jPlayer.event.timeupdate, function(event) { 
      $("#playTime").text(Math.floor(event.jPlayer.status.currentTime));
    });
  
  $("#jquery_jplayer_1").bind($.jPlayer.event.play, function(event) { 
      set_current_playbackrate();
      // console.log("play");
    });
  
  $("#slower").click(click_slower);
  $("#faster").click(click_faster);
  $("#stdspeed").click(click_stdspeed);
  $("#backbutt").click(click_back);
  $("#forwbutt").click(click_forw);
  $("#do-single").click(click_single);
  $("#do-repeat").click(click_repeat);
  $("#playbackrate").change(set_new_playbackrate);
  $("#backtime").change(set_new_playerseconds);
  
    <?php echo ($repeatMode ? "click_repeat();\n" : ''); ?>
});
//]]>
</script>
    <?php
}


// -------------------------------------------------------------

function framesetheader($title) 
{
    @header('Expires: Wed, 11 Jan 1984 05:00:00 GMT');
    @header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    @header('Cache-Control: no-cache, must-revalidate, max-age=0');
    @header('Pragma: no-cache');
    ?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <link rel="stylesheet" type="text/css" href="<?php print_file_path('css/styles.css');?>" />
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon"/>
    <!-- 
        <?php echo file_get_contents("UNLICENSE.md");?> 
    -->
    <title>LWT :: <?php echo tohtml($title); ?></title>
</head>
    <?php
}

?>