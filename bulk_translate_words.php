<?php

require_once 'inc/session_utility.php';

$tid = $_REQUEST['tid'];
$sl = null;
$tl = null;
if(isset($_REQUEST["sl"])) {
    $sl=$_REQUEST["sl"];
    $tl=$_REQUEST["tl"];
    setcookie("googtrans", '/'.$sl.'/'.$tl, time() + 60, "/");
}
if (isset($_REQUEST["offset"])) { 
    $pos = $_REQUEST["offset"]; 
}
if (isset($_REQUEST['term'])) {
    $cnt = 0;
    $sqlarr = array();
    foreach ($_REQUEST['term'] as $row){
        $sqlarr[] =  '(' . 
        convert_string_to_sqlsyntax($row['lg']) . ',' . 
        convert_string_to_sqlsyntax(mb_strtolower($row['text'], 'UTF-8')) . ',' . 
        convert_string_to_sqlsyntax($row['text']) . ',' . 
        convert_string_to_sqlsyntax($row['status']) . ',' . 
        (
            (!isset($row['trans']) || $row['trans']=='') ?  
            '"*"' : 
            convert_string_to_sqlsyntax($row['trans'])
        ) . 
        ', 
        "", 
        "", 
        NOW(), ' . 
        make_score_random_insert_update('id') . 
        ')';
        $cnt++;
    }
    $sqltext = "INSERT INTO words (
        WoLgID, WoTextLC, WoText, WoStatus, WoTranslation, WoSentence, 
        WoRomanization, WoStatusChanged," .  
        make_score_random_insert_update('iv') . "
    ) VALUES " . rtrim(implode(',', $sqlarr), ',');
    runsql($sqltext, '');
    $tooltip_mode = getSettingWithDefault('set-tooltip-mode');
    $max = get_first_value("SELECT max(WoID) AS value FROM words");
    $res = do_mysqli_query(
        "SELECT WoID, WoTextLC, WoStatus, WoTranslation 
        FROM words 
        where WoID > $max"
    );
    pagestart($cnt . ' New Word' . ($cnt != 1 ? 's' : '') . ' Saved', false);
    echo '<p id="displ_message"><img src="icn/waiting2.gif" /> Updating Texts</p>';
    flush();
    echo '<script type="text/javascript">var context = window.parent.document;';
    while ($record = mysqli_fetch_assoc($res)){
        $hex = strToClassName(prepare_textdata($record["WoTextLC"]));
        echo '$(".TERM',$hex,'",context)
        .removeClass("status0")
        .addClass("status',$record["WoStatus"],'")
        .addClass("word',$record["WoID"],'")
        .attr("data_wid","',$record["WoID"],'")
        .attr("data_status","',$record["WoStatus"],'")
        .attr("data_trans",',prepare_textdata_js($record["WoTranslation"]),')',"\n";
        if ($tooltip_mode == 1) { 
            echo '.each(
                function(){
                    this.title = make_tooltip(
                        $(this).text(), $(this).attr(\'data_trans\'), 
                        $(this).attr(\'data_rom\'), $(this).attr(\'data_status\')
                    );
                }
            )'; 
        } else {
            echo ".attr('title','')"; 
        }
        echo ";\n";
    }
    echo "</script>";
    mysqli_free_result($res);
    flush();
    do_mysqli_query(
        "UPDATE textitems2 
        JOIN words 
        ON lower(Ti2Text)=WoTextLC AND Ti2WordCount=1 AND Ti2LgID=WoLgID AND WoID>$max 
        SET Ti2WoID = WoID"
    );
    echo "<script type=\"text/javascript\">
    $('#learnstatus', window.parent.document)
    .html('",addslashes(texttodocount2($tid)),"');
    $('#displ_message').remove();";
    if (!isset($pos)) {
        echo "window.parent.document.getElementById('frame-l').focus();
        window.parent.setTimeout('cClick()', 100);";
    }
    echo "</script>";
    flush();
    if (isset($pos)) {
        $pos -= $cnt;
    }
} else {
    pagestart_nobody('Translate New Words');
}
if(isset($pos)) {
    $cnt = 0;
    $offset = '';
    $limit = (int)getSettingWithDefault('set-ggl-translation-per-page') + 1;
    $sql = 'select LgName, LgDict1URI, LgDict2URI, LgGoogleTranslateURI 
    from languages, texts 
    where LgID = TxLgID and TxID = ' . $tid;
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    $wb1 = isset($record['LgDict1URI']) ? $record['LgDict1URI'] : "";
    $wb2 = isset($record['LgDict2URI']) ? $record['LgDict2URI'] : "";
    $wb3 = isset($record['LgGoogleTranslateURI']) ? $record['LgGoogleTranslateURI'] : "";
    ?>
<style>
    body {
        top:0px ! important;
    }
    td.td1{
        vertical-align:middle ! important;
    }
    span.dict1,span.dict2,span.dict3 {
        opacity:0.1;
        cursor: pointer;
    }
    .dict{
        position:absolute;
        z-index:10;
        right:0;
        top:0px;
    }
    span.dict1.hover,span.dict2.hover,span.dict3.hover {
        opacity:1;
        color:red;
        background-color:#666;
        border-radius:2px;
    }
    input[name="WoTranslation"] {
        border: 1px solid red;
    }
    .del_trans{
        position:absolute;
        top:0px;
        right:0px;
        cursor:pointer;
    }
    .del_trans:after{
        content:url(icn/broom.png);
        opacity:0.2;
    }
    .del_trans:hover:after{
        opacity:1;
    }
    .trans{
        position:relative;
    }
</style>
<script type="text/javascript" src="js/jquery.hoverIntent.js" charset="utf-8"></script>
<script type="text/javascript">
    WBLINK1 = '<?php echo $wb1; ?>';
    WBLINK2 = '<?php echo $wb2; ?>';
    WBLINK3 = '<?php echo $wb3; ?>';
    $('h3,h4,title').addClass('notranslate');
$(window).load(function() {
    $('[name="form1"]').submit(function() {
        $('[name="WoTranslation"]').attr('name',$('[name="WoTranslation"]').attr('data_name'));
        window.parent.frames['ru'].location.href = 'empty.html';
        return true;
    });

    $('td').hoverIntent({
        over: function() {$( this ).addClass('hover');}, 
        out: function() {$( this ).removeClass('hover');}, 
        interval: 150,
        selector:"span.dict1, span.dict2, span.dict3"
    });

    $('td').on(
        'click',
        'span.dict1, span.dict2, span.dict3',
        function(){
            if($(this).hasClass( "dict1" )) 
                WBLINK=WBLINK1;
            if($(this).hasClass( "dict2" ))
                WBLINK=WBLINK2;
            if($(this).hasClass( "dict3" ))
                WBLINK=WBLINK3;
            if ((WBLINK.substr(0,8) == '*http://') || (WBLINK.substr(0,9) == '*https://')) {
                owin(createTheDictUrl(
                    WBLINK.replace('*',''), $(this).parent().prev().text()
                    ));
            } else {
                window.parent.frames['ru'].location.href = createTheDictUrl(
                    WBLINK, $(this).parent().prev().text()
                );
            }
            $('[name="WoTranslation"]')
            .attr('name',$('[name="WoTranslation"]')
            .attr('data_name'));
            el=$(this).parent().parent().next().children();
            el.attr('data_name',el.attr('name'));
            el.attr('name','WoTranslation');
        }
    ).on(
        'click',
        '.del_trans',
        function(){$(this).prev().val('').focus();});

    var myVar = setInterval(function(){
        if ($( ".trans>font" ).length == $( ".trans" ).length) {
            $('.trans').each(function() {
                var txt=$(this).text();
                var cnt= $(this).attr('id').replace('Trans_', '');
                $(this).addClass('notranslate')
                .html(
                    '<input type="text" name="term[' + cnt + '][trans]"  value="' 
                    + txt + '" maxlength="100" size="35"></input><div class="del_trans"></div>'
                );
            });
            $('.term').each(function(){
                txt=$(this).text();
                $(this).parent().css('position','relative');
                $(this).after(
                    '<div class="dict">' +
                    // Untested code
                     (WBLINK1 ? '<span class="dict1">D1</span>' : '') +
                     (WBLINK2 ? '<span class="dict2">D2</span>' : '') +
                     (WBLINK1 ? '<span class="dict3">GTr</span>' : '') + // Use WBLINK3 instead?
                    '</div'
                );
                /* Previous version of the code 
                $(this).after('<div class="dict"><?php 
                if(!empty($wb1)) { 
                    echo '<span class="dict1">D1</span>'; 
                }
                if(!empty($wb2)) { 
                    echo '<span class="dict2">D2</span>'; 
                }
                if(!empty($wb1)) {
                    echo '<span class="dict3">GTr</span>'; 
                } 
                ?></div>');*/            
            });
            $('iframe,#google_translate_element').remove();
            selectToggle(true,'form1');
            $('[name^=term]').prop('disabled', false);
            clearInterval(myVar);
        }
    }, 300);
});

$(document).ready( function() {
    window.parent.frames['ru'].location.href = 'empty.html';
    $('input[type="checkbox"]').change(function(){
        var v = parseInt($(this).val());
        var e = '[name=term\\[' + v + '\\]\\[text\\]],[name=term\\[' + v + 
        '\\]\\[lg\\]],[name=term\\[' + v + '\\]\\[status\\]]';
        if(this.checked){
            $(e).prop('disabled', false);
            $('#Trans_'+v+' input').prop('disabled', false);
            if($('input[type="checkbox"]:checked').length) {
                $('input[type="submit"]').val('Save');
            }
        } else{
            $(e).prop('disabled', true);
            $('#Trans_'+v+' input').prop('disabled', true);
            if(!$('input[type="checkbox"]:checked').length) {
                if(!$('input[name="offset"]').length) 
                    v='End';
                else 
                    v='Next';
                $('input[type="submit"]').val(v);
            }
        }
    });
});
function googleTranslateElementInit() {
  new google.translate.TranslateElement({
      pageLanguage: '<?php echo $sl; ?>', 
      layout: google.translate.TranslateElement.InlineLayout.SIMPLE, 
      includedLanguages: '<?php echo $tl; ?>', 
      autoDisplay: false
    }, 'google_translate_element');
}
</script>
<script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
<script type="text/javascript">
    function markAll() {
        $('input[type^=submit]').val('Save');
        selectToggle(true, 'form1');
        $('[name^=term]').prop('disabled', false);
    }

    function markNone() {
        let v;
        if(!$('input[name^=offset]').length) {
            v = 'End'; 
        } else {
            v = 'Next';
        } 
        $('input[type^=submit]').val(v);
        selectToggle(false,'form1');
        $('[name^=term]').prop('disabled', true);
    }

    function changeTermToggles(elem) {
        const v = elem.val();
        if (v==6) {
            $('.markcheck:checked').each(function() {
                e=$('#Term_' + elem.val()).children('.term');
                e.text(e.text().toLowerCase());
                $('#Text_' + elem.val()).val(e.text().toLowerCase());
            });
            elem.prop('selectedIndex',0);
            return false;
        } 
        if(v==7){
            $('.markcheck:checked').each(function() {
                $('#Trans_' + elem.val() + ' input').val('*');
            });
            elem.prop('selectedIndex',0);
            return false;
        }
        $('.markcheck:checked').each(function() {
            $('#Stat_' + elem.val()).val(v);
        });
        elem.prop('selectedIndex', 0);
        return false;
    }
</script>
    <form name="form1" action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post">
    <span class="notranslate">
        <div id="google_translate_element"></div>
        <table class="tab3" cellspacing="0">
        <tr class="notranslate">
            <th class="th1 center" colspan="3">
                <input type="button" value="Mark All" onclick="markAll()" />
                <input type="button" value="Mark None" onclick="markNone()" />
                <br />
            </th>
        </tr>
        <tr class="notranslate">
            <td class="td1">Marked Terms: </td>
            <td class="td1">
                <select onchange="changeTermToggles($(this));">
                    <option value="0" selected="selected">[Choose...]</option>
                    <option value="1">Set Status To [1]</option>
                    <option value="2">Set Status To [2]</option>
                    <option value="3">Set Status To [3]</option>
                    <option value="4">Set Status To [4]</option>
                    <option value="5">Set Status To [5]</option>
                    <option value="99">Set Status To [WKn]</option>
                    <option value="98">Set Status To [Ign]</option>
                    <option value="6">Set To Lowercase</option>
                    <option value="7">Delete Translation</option>
                </select>
            </td>
            <td class="td1" style="min-width: 45px;">
                <input  type="submit" value="Save" />
            </td>
        </tr>
    </table>
    </span>
    <table class="tab3" cellspacing="0">
        <tr class="notranslate">
            <th class="th1">Mark</th>
            <th class="th1" style="min-width:5em;">Term</th>
            <th class="th1">Translation</th>
            <th class="th1">Status</th>
        </tr>
    <?php
    $res = do_mysqli_query(
        'select Ti2Text as word,Ti2LgID,min(Ti2Order) as pos 
        from textitems2 
        where Ti2WoID = 0 and Ti2TxID = ' . $tid . ' AND Ti2WordCount =1 
        group by LOWER(Ti2Text) 
        order by pos 
        limit ' . $pos . ',' . $limit
    );
    while($record = mysqli_fetch_assoc($res)){
        if(++$cnt<$limit) {
            $value=tohtml($record['word']);
            echo '<tr>
            <td class="td1 center notranslate">
                <input name="marked[', $cnt ,']" type="checkbox" class="markcheck" checked="checked" value="', $cnt , '" />
            </td>
            <td id="Term_', $cnt ,'" class="td1 left notranslate">
                <span class="term">',$value,'</span>
            </td>
            <td class="td1 right trans" id="Trans_', $cnt ,'">',mb_strtolower($value, 'UTF-8'),'</td>
            <td class="td1 center notranslate">
                <select id="Stat_', $cnt ,'" name="term[', $cnt ,'][status]">
                    <option value="1" selected="selected">[1]</option>
                    <option value="2">[2]</option>
                    <option value="3">[3]</option>
                    <option value="4">[4]</option>
                    <option value="5">[5]</option>
                    <option value="99">[WKn]</option>
                    <option value="98">[Ign]</option>
                </select>
                <input type="hidden" id="Text_', $cnt ,'" name="term[', $cnt ,'][text]" value="',$value,'" />
                <input type="hidden" name="term[', $cnt ,'][lg]" value="',tohtml($record['Ti2LgID']),'" />
            </td>
            </tr>',"\n";
        }
        else { 
            $offset = '<input type="hidden" name="offset" value="' . ($pos + $limit - 1) . '" />
            <input type="hidden" name="sl" value="' . $sl . '" />
            <input type="hidden" name="tl" value="' . $tl . '" />'; 
        }
    }
    mysqli_free_result($res);
    ?>
    </table>
    <input type="hidden" name="tid" value="<?php echo $tid ?>" />
    <?php echo $offset ?>
    </form>
    <?php
}
pageend();
?>
