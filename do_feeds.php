<?php
/**
 * \file
 * \brief Prepare RSS feeds.
 * 
 * @package Lwt
 * @author https://github.com/andreask7 andreask7
 * @license Unlicense <http://unlicense.org/>
 * @since  1.6.0-fork
 */

require_once 'inc/session_utility.php';

$currentlang = validateLang(processDBParam("filterlang", 'currentlanguage', '', 0));
$currentsort = processDBParam("sort", 'currentrsssort', '2', 1);
$currentquery = processSessParam("query", "currentrssquery", '', 0);
$currentquerymode = processSessParam("query_mode", "currentrssquerymode", 'title,desc,text', 0);
$currentregexmode = getSettingWithDefault("set-regex-mode");
$currentpage = processSessParam("page", "currentrsspage", '1', 1);
$currentfeed = processSessParam("selected_feed", "currentrssfeed", '', 0);
$wh_query = $currentregexmode . 'like ' .  convert_string_to_sqlsyntax(($currentregexmode == '') ? (str_replace("*", "%", mb_strtolower($currentquery, 'UTF-8'))) : ($currentquery));
switch($currentquerymode){
case 'title,desc,text':
    $wh_query=' and (FlTitle ' . $wh_query . ' or FlDescription ' . $wh_query . ' or FlText ' . $wh_query . ')';
    break;
case 'title':
    $wh_query=' and (FlTitle ' . $wh_query . ')';
    break;
}
pagestart('My ' . getLanguage($currentlang) . ' Feeds', true);

if($currentquery!=='') {
    if($currentregexmode!=='') {
        if(@mysqli_query($GLOBALS["DBCONNECTION"], 'select "test" rlike ' . convert_string_to_sqlsyntax($currentquery))===false) {
            $currentquery='';
            $wh_query = '';
            unset($_SESSION['currentwordquery']);
            if(isset($_REQUEST['query'])) { echo '<p id="hide3" style="color:red;text-align:center;">+++ Warning: Invalid Search +++</p>'; 
            }
        }
    }
}
else { $wh_query = ''; 
}

$message = '';
$edit_text=0;

$doc = null;
$text_item = null;
if (isset($_REQUEST['marked_items'])) {
    $marked_items = implode(',', $_REQUEST['marked_items']);
    $res = do_mysqli_query("SELECT * FROM (SELECT * FROM feedlinks WHERE FlID IN ($marked_items) ORDER BY FlNfID) A left join newsfeeds ON NfID=FlNfID");
    $count=$message1=$message2=$message3=$message4=0;
    while($row = mysqli_fetch_assoc($res)){
        if(get_nf_option($row['NfOptions'], 'edit_text')==1) {
            if($edit_text==1) { $count++; 
            }
            else{
                echo '<form class="validate" action="do_feeds.php" method="post">';
                $edit_text=1;
            }
        }
        $doc[0]=array('link' => empty($row['FlLink'])?('#'.$row['FlID']):$row['FlLink'],
        'title' => $row['FlTitle'],
        'audio' => $row['FlAudio'],
        'text' => $row['FlText']);
        $NfName = $row['NfName'];
        $nf_id=$row['NfID'];
        $nf_options=$row['NfOptions'];
        if(!$nf_tag_name=get_nf_option($nf_options, 'tag')) {
            $nf_tag_name = mb_substr($NfName, 0, 20, "utf-8");
        }
        if(!$nf_max_texts=get_nf_option($nf_options, 'max_texts')) {
            $nf_max_texts=getSettingWithDefault('set-max-texts-per-feed');
        }
        $texts=get_text_from_rsslink($doc, $row['NfArticleSectionTags'], $row['NfFilterTags'], get_nf_option($nf_options, 'charset'));
        if(isset($texts['error'])) {
            echo $texts['error']['message'];
            foreach($texts['error']['link'] as $err_links){
                runsql('UPDATE feedlinks SET FlLink=CONCAT(" ",FlLink) where FlLink in ('.convert_string_to_sqlsyntax($err_links).')', "");
            }
            unset($texts['error']);
        }

        if(get_nf_option($nf_options, 'edit_text')==1) {
            foreach($texts as $text){
                ?>
         <table class="tab3" cellspacing="0" cellpadding="5">
          <tr>
          <td class="td1 right"><input class="markcheck" type="checkbox" name="Nf_count[<?php echo $count; ?>]" value="<?php echo $count; ?>" checked="checked" />&nbsp; &nbsp; &nbsp; Title:</td>
       <td class="td1"><input type="text" class="notempty" name="feed[<?php echo $count; ?>][TxTitle]" value="<?php echo tohtml($text['TxTitle']); ?>" maxlength="200" size="60" /> <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" /></td>
       </tr>
        <tr>
        <td class="td1 right">Language:</td>
        <td class="td1">
        <select name="feed[<?php echo $count; ?>][TxLgID]" class="notempty setfocus">
                <?php
                $result = do_mysqli_query("SELECT LgName,LgID FROM languages where LgName<>'' ORDER BY LgName");
                while($row_l = mysqli_fetch_assoc($result)){
                    echo '<option value="' . $row_l['LgID'] . '"';
                    if($row['NfLgID']===$row_l['LgID']) {
                        echo ' selected="selected"';
                    }
                    echo '>' . $row_l['LgName'] . '</option>';
                }
                mysqli_free_result($result);
                ?>
        </select>
        </td>
        </tr>
          <tr>
          <td class="td1 right">Text:</td>
          <td class="td1">
          <textarea <?php echo getScriptDirectionTag($row['NfLgID']); ?> name="feed[<?php echo $count; ?>][TxText]" class="notempty checkbytes" cols="60" rows="20"><?php echo tohtml($text['TxText']); ?></textarea> <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
          </td>
          </tr>
          <tr>
          <td class="td1 right">Source URI:</td>
          <td class="td1"><input type="text" class="checkurl" name="feed[<?php echo $count; ?>][TxSourceURI]" value="<?php echo $text['TxSourceURI']; ?>" maxlength="1000" size="60" /></td>
          </tr>
        <tr>
        <td class="td1 right">Tags:</td>
        <td class="td1"><ul name="feed[<?php echo $count; ?>][TagList][]" style="width:340px;margin-top:0px;margin-bottom:0px;margin-left:2px;"><li>
                <?php echo $nf_tag_name; ?></li></ul>
        <input type="hidden" name="feed[<?php echo $count; ?>][Nf_ID]" value="<?php echo $nf_id; ?>" />
        <input type="hidden" name="feed[<?php echo $count; ?>][Nf_Max_Texts]" value="<?php echo $nf_max_texts; ?>" />
        </td>
        </tr>
          <tr>
          <td class="td1 right">Audio-URI:</td>
          <td class="td1"><input type="text" name="feed[<?php echo $count; ?>][TxAudioURI]" value="<?php echo $text['TxAudioURI']; ?>" maxlength="200" size="60" />        
          </td>
          </tr>
          </table>
                <?php
            }
        }
        else{
            do_mysqli_query('insert ignore into tags2 (T2Text) values("' . $nf_tag_name . '")');
            foreach($texts as $text){
                echo '<div class="msgblue"><p class="hide_message">+++ "' . $text['TxTitle']. '" added! +++</p></div>';
                do_mysqli_query('INSERT INTO texts (TxLgID,TxTitle,TxText,TxAudioURI,TxSourceURI)VALUES ('.$row['NfLgID'].',' . convert_string_to_sqlsyntax($text['TxTitle']) .','. convert_string_to_sqlsyntax($text['TxText']) .','. convert_string_to_sqlsyntax($text['TxAudioURI']) .','.convert_string_to_sqlsyntax($text['TxSourceURI']) .')');
                $id = (int)get_last_key();
                splitCheckText(
                    get_first_value(
                        'select TxText as value from texts where TxID = ' . $id
                    ), 
                    get_first_value(
                        'select TxLgID as value from texts where TxID = ' . $id
                    ), 
                    $id 
                );
                runsql('insert into texttags (TtTxID, TtT2ID) select ' . $id . ', T2ID from tags2 where T2Text = "' . $nf_tag_name .'"', "");        
            }
            get_texttags(1);
            $result=do_mysqli_query("SELECT TtTxID FROM texttags join tags2 on TtT2ID=T2ID WHERE T2Text='". $nf_tag_name ."'");
            $text_count=0;
            while($row = mysqli_fetch_assoc($result)){
                $text_item[$text_count++]=$row['TtTxID'];
            }
            mysqli_free_result($result);
            if(isset($text_item)) {
                sort($text_item, SORT_NUMERIC);
                if($text_count>$nf_max_texts) {
                    $text_item=array_slice($text_item, 0, $text_count-$nf_max_texts);
                    foreach ($text_item as $text_ID) {
                        $temp = runsql(
                            'delete from textitems2 where Ti2TxID = ' . $text_ID, 
                            ""
                        );
                        if (is_numeric($temp)) {
                            $message3 += (int) $temp;
                        }
                        $temp = runsql(
                            'delete from sentences where SeTxID = ' . $text_ID, 
                            ""
                        );
                        if (is_numeric($temp)) {
                            $message2 += (int) $temp;
                        }
                        $temp = runsql(
                            'INSERT INTO archivedtexts (
                                AtLgID, AtTitle, AtText, 
                                AtAnnotatedText, AtAudioURI, AtSourceURI
                            ) 
                            SELECT TxLgID, TxTitle, TxText, 
                            TxAnnotatedText, TxAudioURI, TxSourceURI 
                            FROM texts 
                            WHERE TxID = ' . $text_ID, 
                            ""
                        );
                        if (is_numeric($temp)) {
                            $message4 += (int) $temp;
                        }
                        $id = get_last_key();
                        runsql(
                            'INSERT INTO archtexttags (AgAtID, AgT2ID) 
                            SELECT ' . $id . ', TtT2ID 
                            FROM texttags 
                            WHERE TtTxID = ' . $text_ID, 
                            ""
                        );    
                        $temp = runsql('DELETE FROM texts WHERE TxID = ' . $text_ID, "");
                        if (is_numeric($temp)) {
                            $message1 += (int) $temp;
                        }
                        adjust_autoincr('texts', 'TxID');
                        adjust_autoincr('sentences', 'SeID');
                        runsql(
                            "DELETE texttags 
                            FROM (" 
                                . $tbpref . "texttags 
                                LEFT JOIN texts 
                                ON TtTxID = TxID
                            ) 
                            WHERE TxID IS NULL", 
                            ''
                        );        
                    }
                }
            }
        }
    }
    mysqli_free_result($res);
    if ($message4>0 || $message1>0) { 
        $message = "Texts archived: " . $message1 . " / Sentences deleted: " . $message2 . " / Text items deleted: " . $message3; 
    }
    if($edit_text==1) {
        ?>
   <input id="markaction" type="submit" value="Save" />
   <input type="button" value="Cancel" onclick="location.href='do_feeds.php';" />
   <input type="hidden" name="checked_feeds_save" value="1" />
   </form>

   <script type="text/javascript">
   $(document).ready( function() {
    $(document).scrollTo($('table').eq(0));
   });
   $('input[type="checkbox"]').change(function(){
   var feed = '[name^=feed\\['+ $(this).val() +'\\]';
   if(this.checked){
   $(feed+']').prop('disabled', false);
   $(feed+'\\[TxTitle\\]],'+feed+'\\[TxText\\]]').addClass("notempty");
   $('ul'+feed+']').css("background","");
   $('ul'+feed+'] li.tagit-new input').prop('disabled', false).addClass("ui-widget-content");
   $('ul'+feed+'] a').css("display", ""); 
   $('ul'+feed+'] li').css("color", "").css("background", ""); 
   }
   else{
   $(feed+']').prop('disabled', true).removeClass("notempty");
   var bg=$('textarea'+feed+']').css("background");
   $('ul'+feed+']').css("background",bg);
   $('ul'+feed+'] li.tagit-new input').prop('disabled', true).removeClass("ui-widget-content");
   $('ul'+feed+'] a').css("display", "none"); 
   $('ul'+feed+'] li').css("color", $('textarea'+feed+']').css("color")).css("background", "transparent"); 
   }
   });
   $('ul[name^="feed"]').each(function() {
    var tagrepl=$(this).attr('name');
    $(this).tagit({
    availableTags : TEXTTAGS, 
    fieldName: tagrepl
   });});
   </script>
        <?php
    }
    if($edit_text==1) {
        echo '</form>';
    }
    ?>
<script type="text/javascript">
$(".hide_message").delay(2500).slideUp(1000);
</script>
    <?php
}




if (isset($_REQUEST['checked_feeds_save'])) {
    $message=write_rss_to_db($_REQUEST['feed']);
    ?>
<script type="text/javascript">
$(".hide_message").delay(2500).slideUp(1000);
</script>
    <?php
}
if(isset($_SESSION['feed_loaded'])) {
    foreach($_SESSION['feed_loaded'] as $lf){
        if (substr($lf, 0, 5) == "Error" ) { echo "\n<div class=\"red\"><p>"; 
        }
        else { echo "\n<div class=\"msgblue\"><p class=\"hide_message\">"; 
        }
        echo "+++ ",$lf," +++</p></div>";
    }
    ?>
<script type="text/javascript">
$(".hide_message").delay(2500).slideUp(1000);
</script>
    <?php
    unset($_SESSION['feed_loaded']);

}
echo error_message_with_hide($message, 0);

if(isset($_REQUEST['load_feed']) || isset($_REQUEST['check_autoupdate']) || (isset($_REQUEST['markaction']) && $_REQUEST['markaction']=='update')) {
    load_feeds($currentfeed);
}
else
if(empty($edit_text)) {
    ?>

<div style="display: flex; justify-content: space-evenly; width: 850px;">
    <div title="Import of a single text, max. 65,000 bytes long, with optional audio">
        <a href="<?php echo $_SERVER['PHP_SELF']; ?>?new=1">
            <img src="icn/plus-button.png">
            Standard Text Import...
        </a>
    </div>
    <div>
        <a href="edit_feeds.php?manage_feeds=1">
            <img src="icn/plus-button.png" title="manage feeds" alt="manage feeds" />
            Manage Feeds ...
        </a>
    </div>
    <div>
        <a href="edit_texts.php?query=&amp;page=1">
            <img src="icn/drawer--plus.png">
            Active Texts
        </a>
    </div>
    <div>
        <a href="edit_archivedtexts.php?query=&amp;page=1">
            <img src="icn/drawer--minus.png">
            Archived Texts
        </a>
    </div>
</div>
<form name="form1" action="#" onsubmit="document.form1.querybutton.click(); return false;">
<table class="tab1" cellspacing="0" cellpadding="5"><tr>
<th class="th1" colspan="4">Filter <img src="icn/funnel.png" title="Filter" alt="Filter" />&nbsp;
<input type="button" value="Reset All" onclick="resetAll('do_feeds.php');" /></th>
</tr><tr><td class="td1 center" style="width:30%;">
Language:&nbsp;<select name="filterlang" onchange="{setLang(document.form1.filterlang,'do_feeds.php?page=1%26selected_feed=0');}">
    <?php	echo get_languages_selectoptions($currentlang, '[Filter off]'); ?></select>
</td><td class="td1 center" colspan="3">
<select name="query_mode" onchange="{val=document.form1.query.value;mode=document.form1.query_mode.value; location.href='do_feeds.php?page=1&amp;query=' + val + '&amp;query_mode=' + mode;return false;}">
<option value="title,desc,text"<?php if($currentquerymode=="title,desc,text") { echo ' selected="selected"'; 
} ?>>Title, Desc., Text</option>
<option disabled="disabled">------------</option>
<option value="title"<?php if($currentquerymode=="title") { echo ' selected="selected"'; 
} ?>>Title</option>
</select><?php
if($currentregexmode=='') { echo '<span style="vertical-align: middle"> (Wildc.=*): </span>'; 
}
elseif($currentregexmode=='r') { echo '<span style="vertical-align: middle"> RegEx Mode: </span>';
} else { echo '<span style="vertical-align: middle"> RegEx(CS) Mode: </span>'; 
}?>
<input type="text" name="query" value="<?php echo tohtml($currentquery); ?>" maxlength="50" size="15" />&nbsp;
<input type="button" name="querybutton" value="Filter" onclick="{val=document.form1.query.value;val=encodeURIComponent(val); location.href='do_feeds.php?page=1&amp;query=' + val;return false;}" />&nbsp;
<input type="button" value="Clear" onclick="{location.href='do_feeds.php?page=1&amp;query=';return false;}" />
</td></tr><tr>
<td class="td1 center" colspan="2" style="width:70%;"><?php
if(!empty($currentlang)) {
    $result = do_mysqli_query("SELECT NfName,NfID,NfUpdate FROM newsfeeds WHERE NfLgID=$currentlang ORDER BY NfUpdate DESC");
}
else{
    $result = do_mysqli_query("SELECT NfName,NfID,NfUpdate FROM newsfeeds ORDER BY NfUpdate DESC");
}
if(!mysqli_data_seek($result, 0)) {
    echo ' no feed available</td><td class="td1"></td></tr></table></form>';
}
if(mysqli_data_seek($result, 0)) {
    ?>Newsfeed:<select name="selected_feed" onchange="{val=document.form1.selected_feed.value; location.href='do_feeds.php?page=1&amp;selected_feed=' + val;return false;}">
<option value="0">[Filter off]</option>
    <?php
    $temp='';
    $time='';
    while($row = mysqli_fetch_assoc($result)){
        echo '<option value="' . $row['NfID'] . '"';
        if($currentfeed===$row['NfID']) {
            echo ' selected="selected"';
            $time=$row['NfUpdate'];
        }
        echo '>' . tohtml($row['NfName']) . '</option>';
        $temp.= ',' . $row['NfID'];
    }
    mysqli_free_result($result);
    echo '</select></td><td class="td1 center" colspan="2">';
    if($currentfeed==0 || strpos($temp, $currentfeed)===false) { 
        $currentfeed = substr($temp, 1); 
    }

    if(strpos($currentfeed, ',')===false) {
        echo '<a href="' . $_SERVER['PHP_SELF'] . '?page=1&amp;load_feed=1&amp;selected_feed=' . $currentfeed . '"><span title="update feed">  <img src="icn/arrow-circle-135.png" alt="-" /></span></a>';
    } else {
        echo '<a href="edit_feeds.php?multi_load_feed=1&amp;selected_feed=' . $currentfeed . '"> update multiple feeds</a>';
    }
    if ($time) {
        $diff=time() - (int) $time;
        print_last_feed_update($diff);
    }
    echo '</td></tr>';
    $sql = 'SELECT count(*) AS value FROM feedlinks 
    WHERE FlNfID in ('.$currentfeed.')'. $wh_query;
    $recno = (int)get_first_value($sql);
    if ($debug) { 
        echo $sql . ' ===&gt; ' . $recno; 
    }
    if($recno) {
        $maxperpage = (int)getSettingWithDefault('set-articles-per-page');
        $pages = $recno == 0 ? 0 : (intval(($recno-1) / $maxperpage) + 1);
        if ($currentpage < 1) { 
            $currentpage = 1; 
        }
        if ($currentpage > $pages) {
            $currentpage = $pages; 
        }
        $limit = 'LIMIT ' . (($currentpage-1) * $maxperpage) . ',' . $maxperpage;        
        $sorts = array('FlTitle','FlDate DESC','FlDate ASC');
        $lsorts = count($sorts);
        if ($currentsort < 1) { 
            $currentsort = 1; 
        }
        if ($currentsort > $lsorts) { 
            $currentsort = $lsorts; 
        }            
        echo '<tr><th class="th1" style="width:30%;"> '. $total=$recno .' articles ';///
        echo '</th><th class="th1">';
        makePager($currentpage, $pages, 'do_feeds.php', 'form1');    ?>

  </th>
  <th class="th1" colspan="2" nowrap="nowrap">
  Sort Order:
  <select name="sort" onchange="{val=document.form1.sort.options[document.form1.sort.selectedIndex].value; location.href='do_feeds.php?page=1&amp;sort=' + val;return false;}"><?php echo get_textssort_selectoptions($currentsort); ?></select>
  </th>
  </tr>
  </table></form>
  <form name="form2" action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post">
  <table class="tab1" cellspacing="0" cellpadding="5">
  <tr><th class="th1" colspan="2">Multi Actions <img src="icn/lightning.png" title="Multi Actions" alt="Multi Actions" /></th></tr>
  <tr><td class="td1 center" style="width:30%;">
  <input type="button" value="Mark All" onclick="selectToggle(true,'form2');" />
  <input type="button" value="Mark None" onclick="selectToggle(false,'form2');" />
  </td><td class="td1 center">
  Marked Texts:&nbsp; 
  <input id="markaction" type="submit" value="Get Marked Texts" />&nbsp;&nbsp;
  </td></tr></table>
  <table  class="tab1 sortable" cellspacing="0" cellpadding="5">    
  <tr>
  <th class="th1 sorttable_nosort">Mark</th>
  <th class="th1 clickable">Articles</th>
  <th class="th1 sorttable_nosort">Link</th>
  <th class="th1 clickable" style="min-width:90px;">Date</th>
  </tr>    
        <?php
        $result = do_mysqli_query("SELECT FlID, FlTitle, FlLink, FlDescription, FlDate, FlAudio,TxID, AtID FROM feedlinks left join texts on TxSourceURI=trim(FlLink) left join archivedtexts on AtSourceURI=trim(FlLink) WHERE FlNfID in ($currentfeed) ".$wh_query." ORDER BY " . $sorts[$currentsort-1] . " ". $limit);
        while($row = mysqli_fetch_assoc($result)){
            echo  '<tr>';
            if ($row['TxID']) {
                echo '<td class="td1 center"><a href="do_text.php?start=' . $row['TxID'] . '" ><img src="icn/book-open-bookmark.png" title="Read" alt="-" /></a>'; 
            }
            elseif ($row['AtID']) {
                echo '<td class="td1 center"><span title="archived"><img src="icn/status-busy.png" alt="-" /></span>';
            } elseif(!empty($row['FlLink']) && str_starts_with($row['FlLink'], ' ')) {
                echo '<td class="td1 center"><img class="not_found" name="' . $row['FlID'] . '" title="download error" src="icn/exclamation-button.png" alt="-" />';
            } else {
                echo '<td class="td1 center"><input type="checkbox" class="markcheck" name="marked_items[]" value="' . $row['FlID'] . '" />'; 
            }
            echo '</td>';
            echo  '<td class="td1 center">';
            echo  '<span title="' . htmlentities($row['FlDescription'], ENT_QUOTES, 'UTF-8', false) . '"><b>' . $row['FlTitle'] . '</b></span>';
            if($row['FlAudio']) {
                echo '<a href="' . $row['FlAudio'] . '" onclick="window.open(this.href, \'child\', \'scrollbars,width=650,height=600\'); return false;">  <img src="'; print_file_path('icn/speaker-volume.png'); echo '" alt="-" /></a>';
            }
            echo '</td>';
            echo '<td class="td1 center" style="vertical-align: middle">';
            if(!empty($row['FlLink']) && !str_starts_with(trim($row['FlLink']), '#')) {
                echo '<a href="' . trim($row['FlLink']) . '"  title="' . trim($row['FlLink']) . '" onclick="window.open(\'' . $row['FlLink'] . '\');return false;"><img src="icn/external.png" alt="-" /></a>'; 
            }
            echo  '</td><td class="td1 center">' . $row['FlDate'] . '</td>';
            echo '</tr>';
        }
        mysqli_free_result($result);
        echo '</table>';
        echo '</form>';
        if($pages > 1) {
            echo '<form name="form3" method="get" action =""><table class="tab1" cellspacing="0" cellpadding="5"><tr><th class="th1" style="width:30%;">';
            echo $total;
            echo '</th><th class="th1">';
            makePager($currentpage, $pages, 'do_feeds.php', 'form3');
            echo '</th></tr></table></form>';
        }
    }
    else { 
        echo '</table></form>'; 
    }
}
?>
<script type="text/javascript">
$('img.not_found').on('click', function () {
    var id = $(this).attr('name');
    $(this).after('<label class="wrap_checkbox" for="'+id+'"><span></span></label>');
    $(this).replaceWith( '<input type="checkbox" class="markcheck" onchange="markClick()" id=' + id +' value=' + id +' name="marked_items[]" />' );
    $(":input,.wrap_checkbox span,a:not([name^=rec]),select").each(function (i) { $(this).attr('tabindex', i + 1); });
});
</script>
    <?php
}

pageend();

?>
