<?php

/*
 * \file
 * \brief Import terms from file or Text area
 * 
 *  Call: upload_words.php?....
 *      ... op=Import ... do the import 
 */

require_once 'inc/session_utility.php';

/**
 * Get the CSV from a string.
 * 
 * @param string $input Input string.
 * 
 * @return (null|string)[]|false|null
 *
 * @psalm-return false|non-empty-list<null|string>|null
 */
function my_str_getcsv($input) 
{
    $temp=fopen("php://memory", "rw");
    fwrite($temp, $input);
    fseek($temp, 0);
    $data = fgetcsv($temp);
    fclose($temp);
    return $data;
}

/*
 * Import terms to the database.
 * 
 * @param array    $fields   Fields indexes
 * @param string   $tabs     Columns separator
 * @param bool     $file_upl If the input text is an uploaded file
 * @param string[] $col      Columns names
 * @param int      $lang     Language ID
 * 
 * @return void
 * 
 *
 */
function upload_words_import_terms($fields, $tabs, $file_upl, $col, $lang)
{

    $sql = "SELECT * FROM languages WHERE LgID=$lang";
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    $removeSpaces = $record["LgRemoveSpaces"];
    $rtl = $record['LgRightToLeft'];
    $last_update = get_first_value(
        "SELECT max(WoStatusChanged) AS value FROM words"
    );
    $overwrite = $_REQUEST["Over"];
    $status = $_REQUEST["WoStatus"];
    $columns = '(' . rtrim(implode(',', $col), ',') . ')';
    $temp_tabs = $tabs;
    if ($temp_tabs == 'h') {
        $tabs = "#";  
    } else if ($temp_tabs == 'c') { 
        $tabs = ",";
    } else {
        $tabs = "\\t"; 
    }
    if ($file_upl) { 
        $file_name = $_FILES["thefile"]["tmp_name"]; 
    } else {
        $file_name = tempnam(sys_get_temp_dir(), "LWT");
        $temp = fopen($file_name, "w");
        fwrite($temp, prepare_textdata($_REQUEST["Upload"]));
        fseek($temp, 0);
        fclose($temp);
    }
    $sql = 'LOAD DATA LOCAL INFILE '. convert_string_to_sqlsyntax($file_name);
    //$sql.= ($overwrite)?' REPLACE':(' IGNORE') ;
    if ($fields["tl"]==0 and $overwrite==0) {

        if (get_first_value("SELECT @@GLOBAL.local_infile as value")) {
            $sql .= " IGNORE INTO TABLE words 
            FIELDS TERMINATED BY '$tabs' ENCLOSED BY '\"' LINES TERMINATED BY '\\n' 
            " . ($_REQUEST["IgnFirstLine"] == '1' ? "IGNORE 1 LINES" : "") . "
            $columns 
            SET WoLgID = $lang, " . 
            ($removeSpaces ? 
            'WoTextLC = LOWER(REPLACE(@wotext," ","")),
            WoText = REPLACE(@wotext, " ", "")':
            'WoTextLC = LOWER(WoText)') . ", 
            WoStatus = $status, WoStatusChanged = NOW(), " . 
            make_score_random_insert_update('u');
            runsql($sql, '');
        } else {
            $handle = fopen($file_name, 'r');
            $data_text = fread($handle, filesize($file_name));
            fclose($handle);
            $values = array();
            $i = 0;
            foreach (explode(PHP_EOL, $data_text) as $line) {
                if ($i++ == 0 && $_REQUEST["IgnFirstLine"] == '1') {
                    continue;
                }
                $row = array();
                $parsed_line = explode($tabs, $line); 
                $wotext = $parsed_line[$fields["txt"] - 1];
                // Fill WoText and WoTextLC
                if ($removeSpaces) {
                    $row[] = str_replace("", " ", $wotext);
                    $row[] = mb_strtolower(str_replace("", " ", $wotext));
                } else {
                    $row[] = $wotext;
                    $row[] = mb_strtolower($wotext);
                }
                if ($fields["tr"] != 0) {
                    $row[] = (
                        $parsed_line[$fields["tr"] - 1]
                    );
                }
                if ($fields["ro"] != 0) {
                    $row[] = (
                        $parsed_line[$fields["ro"] - 1]
                    );
                }
                if ($fields["se"] != 0) {
                    $row[] = (
                        $parsed_line[$fields["se"] - 1]
                    );
                }

                $row = array_map('convert_string_to_sqlsyntax', $row);
                $row = array_merge(
                    $row, array(
                    $lang, $status, "NOW()", 
                    getsqlscoreformula(2), getsqlscoreformula(3), "RAND()"
                    )
                );
                $values[] = "(" . implode(",", $row) . ")";
            }
            do_mysqli_query(
                "INSERT INTO words(
                    WoText, WoTextLC, " . 
                    ($fields["tr"] != 0 ? 'WoTranslation, ' : '') . 
                    ($fields["ro"] != 0 ? 'WoRomanization, ' : '') . 
                    ($fields["se"] != 0 ? 'WoSentence, ' : '') . 
                    "WoLgID, WoStatus, WoStatusChanged, 
                    WoTodayScore, WoTomorrowScore, WoRandom
                )
                VALUES " . implode(',', $values)
            );
        }
    } else {
        runsql('SET GLOBAL max_heap_table_size = 1024 * 1024 * 1024 * 2', '');
        runsql(
            "CREATE TEMPORARY TABLE IF NOT EXISTS numbers( 
                n  tinyint(3) unsigned NOT NULL
            )", 
            ''
        );
        runsql(
            "INSERT IGNORE INTO numbers(n) VALUES ('1'),('2'),('3'),
            ('4'),('5'),('6'),('7'),('8'),('9')", 
            ''
        );
        if (get_first_value("SELECT @@GLOBAL.local_infile as value")) {
            $sql .= " INTO TABLE tempwords 
            FIELDS TERMINATED BY '$tabs' ENCLOSED BY '\"' LINES TERMINATED BY '\\n' 
            " . ($_REQUEST["IgnFirstLine"] == '1' ? "IGNORE 1 LINES" : "") . 
            "$columns SET " . (
                $removeSpaces ? 
                'WoTextLC = LOWER(REPLACE(@wotext," ","")), WoText = REPLACE(@wotext," ","")':
                'WoTextLC = LOWER(WoText)'
            );
            if ($fields["tl"] != 0) { 
                $sql .= ', WoTaglist = REPLACE(@taglist, " ", ",")'; 
            }
            runsql($sql, '');
        } else {
            $handle = fopen($file_name, 'r');
            $data_text = fread($handle, filesize($file_name));
            fclose($handle);
            $values = array();
            $i = 0;
            foreach (explode(PHP_EOL, $data_text) as $line) {
                if ($i++ == 0 && $_REQUEST["IgnFirstLine"] == '1') {
                    continue;
                }
                $row = array();
                $parsed_line = explode($tabs, $line);
                $wotext = $parsed_line[$fields["txt"] - 1];
                // Fill WoText and WoTextLC
                if ($removeSpaces) {
                    $row[] = str_replace(" ", "", $wotext);
                    $row[] = mb_strtolower(str_replace(" ", "", $wotext));
                } else {
                    $row[] = $wotext;
                    $row[] = mb_strtolower($wotext);
                }
                if ($fields["tr"] != 0) {
                    $row[] = $parsed_line[$fields["tr"] - 1];
                }
                if ($fields["ro"] != 0) {
                    $row[] = $parsed_line[$fields["ro"] - 1];
                }
                if ($fields["se"] != 0) {
                    $row[] = $parsed_line[$fields["se"] - 1];
                }
                if ($fields["tl"] != 0) { 
                    $row[] = str_replace(
                        " ", ",", $parsed_line[$fields['tl'] - 1]
                    ); 
                }
                $values[] = "(" . implode(
                    ",", array_map(
                        'convert_string_to_sqlsyntax', $row
                    )
                ) . ")";
            }
            do_mysqli_query(
                "INSERT INTO tempwords(
                    WoText, WoTextLC" . 
                    ($fields["tr"] != 0 ? ', WoTranslation' : '') . 
                    ($fields["ro"] != 0 ? ', WoRomanization' : '') . 
                    ($fields["se"] != 0 ? ', WoSentence' : '') . 
                    ($fields["tl"] != 0 ? ", WoTaglist" : "") .
                ")
                VALUES " . implode(',', $values)
            );
        }
        
        if ($overwrite>3) {
            runsql(
                "CREATE TEMPORARY TABLE IF NOT EXISTS merge_words(
                    MID mediumint(8) unsigned NOT NULL AUTO_INCREMENT, 
                    MText varchar(250) NOT NULL,  
                    MTranslation  varchar(250) NOT NULL, 
                    PRIMARY KEY (MID), 
                    UNIQUE KEY (MText, MTranslation) 
                ) DEFAULT CHARSET=utf8", 
                ''
            );

            $wosep = getSettingWithDefault('set-term-translation-delimiters');
            if (empty($wosep)) {
                if (getreq("Tab") == 'h') { 
                    $wosep[0] = "#"; 
                } elseif (getreq("Tab") == 'c') { 
                    $wosep[0] = ",";
                } else { 
                    $wosep[0] = "\t"; 
                }
            }
            $seplen = mb_strlen($wosep, 'UTF-8');
            $WoTrRepl = words.WoTranslation';
            for ($i=1; $i < $seplen; $i++) {
                $WoTrRepl = 'REPLACE(
                    ' . $WoTrRepl . ', ' . 
                    convert_string_to_sqlsyntax($wosep[$i]) . ', ' . 
                    convert_string_to_sqlsyntax($wosep[0]) . '
                    )';
            }

            runsql(
                "INSERT IGNORE INTO merge_words(MText,MTranslation) 
                SELECT b.WoTextLC, 
                trim(
                    SUBSTRING_INDEX(
                        SUBSTRING_INDEX(
                            b.WoTranslation, 
                            " . convert_string_to_sqlsyntax($wosep[0]) . ", 
                            numbers.n
                        ), 
                        " . convert_string_to_sqlsyntax($wosep[0]) . 
                        ", -1
                    )
                ) name 
                FROM numbers 
                INNER JOIN (
                    SELECT words.WoTextLC as WoTextLC, $WoTrRepl as WoTranslation 
                    FROM tempwords 
                    LEFT JOIN words 
                    ON words.WoTextLC = tempwords.WoTextLC AND words.WoTranslation != '*' AND words.WoLgID = $lang
                ) b 
                ON CHAR_LENGTH(b.WoTranslation)-CHAR_LENGTH(REPLACE(b.WoTranslation, " . convert_string_to_sqlsyntax($wosep[0]) . ", ''))>= numbers.n-1 
                ORDER BY b.WoTextLC, n", 
                ''
            );

            $tesep = $_REQUEST["transl_delim"];
            if (empty($tesep)) {
                if (getreq("Tab") == 'h') { 
                    $tesep[0] = "#"; 
                } elseif (getreq("Tab") == 'c') { 
                    $tesep[0] = ",";
                } else { 
                    $tesep[0] = "\t"; 
                }
            }

            $seplen = mb_strlen($tesep, 'UTF-8');
            $WoTrRepl = tempwords.WoTranslation';
            for ($i=1; $i<$seplen; $i++) {
                $WoTrRepl = 'REPLACE(
                    ' . $WoTrRepl . ', ' . 
                    convert_string_to_sqlsyntax($tesep[$i]) . ', ' . 
                    convert_string_to_sqlsyntax($tesep[0]) . '
                    )';
            }

            runsql(
                "INSERT IGNORE INTO merge_words(MText,MTranslation) 
                SELECT tempwords.WoTextLC, 
                trim(
                    SUBSTRING_INDEX(
                        SUBSTRING_INDEX(
                            $WoTrRepl," . 
                            convert_string_to_sqlsyntax($tesep[0]) . " , 
                            numbers.n
                        ), " . 
                        convert_string_to_sqlsyntax($tesep[0]) . ", 
                        -1
                    )
                ) name 
                FROM numbers 
                INNER JOIN tempwords 
                ON CHAR_LENGTH(tempwords.WoTranslation)-CHAR_LENGTH(REPLACE($WoTrRepl, " . convert_string_to_sqlsyntax($tesep[0]) . ", ''))>= numbers.n-1 
                ORDER BY tempwords.WoTextLC, n", 
                ''
            );
            if ($wosep[0]==',' or $wosep[0]==';') { 
                $wosep = $wosep[0] . ' '; 
            } else { 
                $wosep = ' ' . $wosep[0] . ' '; 
            }
            runsql(
                "UPDATE tempwords 
                LEFT JOIN (
                    SELECT MText, GROUP_CONCAT(trim(MTranslation) 
                        ORDER BY MID 
                        SEPERATOR " . convert_string_to_sqlsyntax_notrim_nonull($wosep) . "
                    ) AS Translation 
                    FROM merge_words 
                    GROUP BY MText
                ) A 
                ON MText=WoTextLC 
                SET WoTranslation = Translation", 
                ''
            );
            runsql("DROP TABLE merge_words", '');
        }
        // */
        if ($overwrite!=3 and $overwrite!=5) {
            $sql = "INSERT " . ($overwrite != 0 ? '' : 'IGNORE ') .
            " INTO words (
                WoTextLC , WoText, WoTranslation, WoRomanization, WoSentence,
                WoStatus, WoStatusChanged, WoLgID, 
                " .  make_score_random_insert_update('iv')  . "
            ) 
            SELECT *, $lang as LgID, " . make_score_random_insert_update('id') . "
            FROM (
                SELECT WoTextLC , WoText, WoTranslation, WoRomanization, 
                WoSentence, $status AS WoStatus, 
                NOW() AS WoStatusChanged 
                FROM tempwords
            ) AS tw";
            if ($overwrite==1 or $overwrite==4) { 
                $sql .= " ON DUPLICATE KEY UPDATE " . 
                ($fields["tr"] ? "words.WoTranslation = tw.WoTranslation, ":"") . 
                ($fields["ro"]?"words.WoRomanization = tw.WoRomanization, ":'') . 
                ($fields["se"]?"words.WoSentence = tw.WoSentence, ":'') . 
                "words.WoStatus = tw.WoStatus, 
                words.WoStatusChanged = tw.WoStatusChanged"; 
            }
            if ($overwrite==2) { 
                $sql .= " ON DUPLICATE KEY UPDATE words.WoTranslation = case 
                    when words.WoTranslation = "*" then tw.WoTranslation 
                    else words.WoTranslation 
                end, 
                words.WoRomanization = case 
                    when words.WoRomanization IS NULL then tw.WoRomanization 
                    else words.WoRomanization 
                end, 
                words.WoSentence = case 
                    when words.WoSentence IS NULL then tw.WoSentence 
                    else words.WoSentence 
                end, 
                words.WoStatusChanged = case 
                    when words.WoSentence IS NULL or words.WoRomanization IS NULL or words.WoTranslation = "*" then tw.WoStatusChanged 
                    else words.WoStatusChanged 
                end";
            }
        } else {
            $sql = "UPDATE words AS a 
            JOIN tempwords AS b 
            ON a.WoTextLC = b.WoTextLC SET a.WoTranslation = CASE 
                WHEN b.WoTranslation = '' or b.WoTranslation = '*' THEN a.WoTranslation 
                ELSE b.WoTranslation 
            END, 
            a.WoRomanization = CASE 
                WHEN b.WoRomanization IS NULL or b.WoRomanization = '' THEN a.WoRomanization 
                ELSE b.WoRomanization 
            END, 
            a.WoSentence = CASE 
                WHEN b.WoSentence IS NULL or b.WoSentence = '' THEN a.WoSentence 
                ELSE b.WoSentence 
            END, 
            a.WoStatusChanged = CASE 
                WHEN (b.WoTranslation = '' OR b.WoTranslation = '*') AND (b.WoRomanization IS NULL OR b.WoRomanization = '') AND (b.WoSentence IS NULL OR b.WoSentence = '') THEN a.WoStatusChanged 
                ELSE NOW() 
            END";
        }
        runsql($sql, '');
        if ($fields["tl"]!=0) {
            runsql(
                "INSERT IGNORE INTO tags (TgText) 
                SELECT name FROM (
                    SELECT tempwords.WoTextLC, 
                    SUBSTRING_INDEX(
                        SUBSTRING_INDEX(
                            tempwords.WoTaglist, ',', 
                            numbers.n
                        ), ',', -1) name 
                    FROM numbers 
                    INNER JOIN tempwords 
                    ON CHAR_LENGTH(tempwords.WoTaglist)-CHAR_LENGTH(REPLACE(tempwords.WoTaglist, ',', ''))>=numbers.n-1 
                    ORDER BY WoTextLC, n) A",
                ''
            );
            runsql(
                "INSERT IGNORE INTO wordtags 
                select WoID,TgID 
                FROM (
                    SELECT tempwords.WoTextLC, SUBSTRING_INDEX(
                        SUBSTRING_INDEX(
                            tempwords.WoTaglist, ',', numbers.n
                        ), ',', -1) name 
                    FROM numbers 
                    INNER JOIN tempwords ON CHAR_LENGTH(tempwords.WoTaglist)-CHAR_LENGTH(REPLACE(tempwords.WoTaglist, ',', ''))>=numbers.n-1 
                    ORDER BY WoTextLC, n
                ) A, tags, words 
                WHERE name=TgText AND A.WoTextLC=words.WoTextLC AND WoLgID=$lang", 
                ''
            );
        }
        runsql("DROP TABLE numbers", '');
        runsql("TRUNCATE tempwords", '');
        if ($fields["tl"]!=0) { 
            get_tags(1); 
        }
    }
    if (!$file_upl) {
        unlink($file_name);
    }
    init_word_count();
    runsql(
        "UPDATE words 
        JOIN textitems2 
        ON WoWordCount=1 AND Ti2WoID=0 AND lower(Ti2Text)=WoTextLC AND Ti2LgID = WoLgID 
        SET Ti2WoID=WoID", 
        ''
    );
    $mwords = get_first_value(
        "SELECT count(*) AS value from words 
        WHERE WoWordCount>1 AND WoCreated > " . 
        convert_string_to_sqlsyntax($last_update)
    );
    if ($mwords > 40) {
        runsql(
            "DELETE FROM  sentences WHERE SeLgID = $lang", 
            "Sentences deleted"
        );
        runsql(
            "DELETE FROM textitems2 WHERE Ti2LgID = $lang", 
            "Text items deleted"
        );
        adjust_autoincr('sentences', 'SeID');
        $sql = "SELECT TxID, TxText FROM texts 
        WHERE TxLgID = $lang ORDER BY TxID";
        $res = do_mysqli_query($sql);
        $cntrp = 0;
        while ($record = mysqli_fetch_assoc($res)) {
            $txtid = (int) $record["TxID"];
            $txttxt = $record["TxText"];
            splitCheckText($txttxt, $lang, $txtid);
            $cntrp++;
        }
        mysqli_free_result($res);
        //$message .= " / Reparsed texts: " . $cntrp;
    } else if ($mwords!=0) {
        $sqlarr = array();
        $res = do_mysqli_query(
            "SELECT WoID, WoTextLC, WoWordCount 
            FROM words 
            WHERE WoWordCount>1 AND WoCreated > " . 
            convert_string_to_sqlsyntax($last_update)
        );
        while ($record = mysqli_fetch_assoc($res)) {
            $len = $record['WoWordCount'];
            $wid = $record['WoID'];
            $textlc = $record['WoTextLC'];
            $sqlarr[] = insertExpressions($textlc, $lang, $wid, $len, 2);
        }
        mysqli_free_result($res);
        $sqlarr = array_filter($sqlarr);
        if (!empty($sqlarr)) {
            $sqltext = "INSERT INTO textitems2 (
                Ti2WoID, Ti2LgID, Ti2TxID, Ti2SeID, Ti2Order, Ti2WordCount,
                Ti2Text
            ) VALUES " . rtrim(implode(',', $sqlarr), ',');
            do_mysqli_query($sqltext);
        }
    }
    $recno = get_first_value(
        "SELECT count(*) AS value FROM words 
        where WoStatusChanged > " . convert_string_to_sqlsyntax($last_update)
    );
    ?>
<script type="text/javascript">
function showImportedTerms(last_update, rtl, count, page) {
    $('#res_data')
    .load(
        'inc/ajax_show_imported_terms.php',
        {
            'last_update': last_update,
            'rtl': rtl,
            'count': count,
            'page': page
        }
    );
}
</script>
<form name="form1" action="#" onsubmit="showImportedTerms('<?php echo $last_update; ?>', $('#recno').text(), document.form1.page.options[document.form1.page.selectedIndex].value); return false;">
<div id="res_data">
<table class="tab1"  cellspacing="0" cellpadding="2"></table>
</div>
</form>
<script type="text/javascript">
    showImportedTerms(
        '<?php echo $last_update; ?>', '<?php echo $rtl; ?>', 
        '<?php echo $recno; ?>', '1'
    );
</script>
    <?php
}

/*
 * Import term tags only to the database.
 * 
 * @param array    $fields   Fields indexes
 * @param string   $tabs     Columns separator
 * @param bool     $file_upl If the input text is an uploaded file
 * 
 * @return void
 * 
 *
 */
function upload_words_import_tags($fields, $tabs, $file_upl)
{

    $columns = '';
    for ($j=1; $j<=$fields["tl"]; $j++) {
        $columns .= ($j==1?'(':',') . ($j==$fields["tl"]?'@taglist':'@dummy');
    }
    $columns .= ')';
    $temp_tabs = $tabs;
    $tabs = " ";
    if ($temp_tabs == 'h') {
        $tabs .= "#"; 
    } elseif ($temp_tabs == 'c') {
        $tabs .= ",";
    } else {
        $tabs .= "\\t"; 
    }
    if ($file_upl) {
        $file_name = $_FILES["thefile"]["tmp_name"]; 
    } else {
        $file_name = tempnam(sys_get_temp_dir(), "LWT");
        $temp = fopen($file_name, "w");
        fwrite($temp, prepare_textdata($_REQUEST["Upload"]));
        fseek($temp, 0);
        fclose($temp);
    }
    if (get_first_value("SELECT @@GLOBAL.local_infile as value")) {
        $sql = "LOAD DATA LOCAL INFILE " . convert_string_to_sqlsyntax($file_name) . 
        " IGNORE INTO TABLE tempwords 
        FIELDS TERMINATED BY '$tabs' ENCLOSED BY '\"' LINES TERMINATED BY '\\n'
        " . ($_REQUEST["IgnFirstLine"] == '1' ? "IGNORE 1 LINES" : "") . "
        $columns 
        SET WoTextLC = REPLACE(@taglist, ' ', ',')";
        runsql($sql, '');
    } else {
        $handle = fopen($file_name, 'r');
        $data_text = fread($handle, filesize($file_name));
        fclose($handle);
        $texts = array();
        $i = 0;
        foreach (explode(PHP_EOL, $data_text) as $line) {
            if ($i++ == 0 && $_REQUEST["IgnFirstLine"] == '1') {
                continue;
            }
            $tags = explode($tabs, $line)[$fields["tl"] - 1];
            $tags = str_replace(' ', ',', $tags);
            $texts[] = convert_string_to_sqlsyntax($tags);
        }
        do_mysqli_query(
            "INSERT INTO tempwords(WoTextLC) 
            VALUES " . implode(',', $texts)
        );
    }
    runsql(
        "CREATE TEMPORARY TABLE IF NOT EXISTS numbers( 
            n  tinyint(3) unsigned NOT NULL
        )", 
        ''
    );
    runsql(
        "INSERT IGNORE INTO numbers(n) VALUES ('1'),('2'),('3'),
        ('4'),('5'),('6'),('7'),('8'),('9')", 
        ''
    );
    runsql(
        "INSERT IGNORE INTO tags (TgText) 
        SELECT NAME FROM (
            SELECT SUBSTRING_INDEX(
                SUBSTRING_INDEX(
                    tempwords.WoTextLC, ',',  numbers.n
                ), ',', -1) name 
            FROM numbers 
            INNER JOIN tempwords 
            ON CHAR_LENGTH(tempwords.WoTextLC)-CHAR_LENGTH(REPLACE(tempwords.WoTextLC, ',', ''))>= numbers.n-1 
            ORDER BY WoTextLC, n) A", 
        ''
    );
    runsql("DROP TABLE numbers", '');
    runsql("TRUNCATE tempwords", '');
    get_tags(1);
    if (!$file_upl) {
        unlink($file_name);
    }
}

/*
 * Import terms of tags to the database.
 * 
 * @return void
 * 
 *
 */
function upload_words_import()
{

    $tabs = $_REQUEST["Tab"];
    $lang = $_REQUEST["LgID"];
    $sql = "SELECT * FROM languages WHERE LgID=$lang";
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    $removeSpaces = $record["LgRemoveSpaces"];

    $col = array();
    $col[1] = $_REQUEST["Col1"];
    $col[2] = $_REQUEST["Col2"];
    $col[3] = $_REQUEST["Col3"];
    $col[4] = $_REQUEST["Col4"];
    $col[5] = $_REQUEST["Col5"];
    $col = array_unique($col);

    $fields = array("txt"=>0,"tr"=>0,"ro"=>0,"se"=>0,"tl"=>0);
    $file_upl = (
        isset($_FILES["thefile"]) && 
        $_FILES["thefile"]["tmp_name"] != "" && 
        $_FILES["thefile"]["error"] == 0
    );

    $max = max(array_keys($col));
    for ($j=1; $j<=$max; $j++) {
        if (!isset($col[$j])) {
            $col[$j]='@dummy'; 
        } else {
            switch ($col[$j]){
            case 'w':
                $col[$j]=$removeSpaces?'@wotext':'WoText';
                $fields["txt"]=$j;
                break;
            case 't':
                $col[$j]='WoTranslation';
                $fields["tr"]=$j;
                break;
            case 'r':
                $col[$j]='WoRomanization';
                $fields["ro"]=$j;
                break;
            case 's':
                $col[$j]='WoSentence';
                $fields["se"]=$j;
                break;
            case 'g':
                $col[$j]='@taglist';
                $fields["tl"]=$j;
                break;
            case 'x':
                if ($j==$max) { 
                    unset($col[$j]); 
                } else { 
                    $col[$j] = '@dummy'; 
                }
                break;
            }
        }
    
    }
    if ($fields["txt"] > 0) {
        upload_words_import_terms($fields, $tabs, $file_upl, $col, $lang);
    } else if ($fields["tl"] > 0) {
        upload_words_import_tags($fields, $tabs, $file_upl);
    }
}


/*
 * Display the main for adding new words.
 * 
 * @return void
 */
function upload_words_display()
{

    ?>

    <form enctype="multipart/form-data" class="validate" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" >
    <table class="tab3" cellspacing="0" cellpadding="5">
    <tr>
        <td class="td1 center"><b>Language:</b></td>
        <td class="td1">
        <select name="LgID" class="notempty setfocus">
        <?php
        echo get_languages_selectoptions(getSetting('currentlanguage'), '[Choose...]');
        ?>
        </select>
        <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" /> 
        </td>
    </tr>
    <tr>
        <td class="td1 center">
            <b>Import Data:</b><br /><br />
            Format per line:<br />
            C1 D C2 D C3 D C4 D C5<br />
            <br /><b>Field Delimiter "D":</b><br />
            <select name="Tab">
                <option value="c" selected="selected">Comma "," [CSV File, LingQ]</option>
                <option value="t">TAB (ASCII 9) [TSV File]</option>
                <option value="h">Hash "#" [Direct Input]</option>
            </select>
            <br />
            <br /><b>Ignore first line</b>: 
            <select name="IgnFirstLine">
                <option value="0" selected="selected">No</option>
                <option value="1">Yes</option>
            </select>
            <br />
            <br />
            <b>Column Assignment:</b><br />
            "C1": <select name="Col1">
                <option value="w" selected="selected">Term</option>
                <option value="t">Translation</option>
                <option value="r">Romanization</option>
                <option value="s">Sentence</option>
                <option value="g">Tag List</option>
                <option value="x">Don't import</option>
            </select><br />
            "C2": <select name="Col2">
                <option value="w">Term</option>
                <option value="t" selected="selected">Translation</option>
                <option value="r">Romanization</option>
                <option value="s">Sentence</option>
                <option value="g">Tag List</option>
                <option value="x">Don't import</option>
            </select><br />
            "C3": <select name="Col3">
                <option value="w">Term</option>
                <option value="t">Translation</option>
                <option value="r">Romanization</option>
                <option value="s">Sentence</option>
                <option value="g">Tag List</option>
                <option value="x" selected="selected">Don't import</option>
            </select><br />
            "C4": <select name="Col4">
                <option value="w">Term</option>
                <option value="t">Translation</option>
                <option value="r">Romanization</option>
                <option value="s">Sentence</option>
                <option value="g">Tag List</option>
                <option value="x" selected="selected">Don't import</option>
            </select><br />
            "C5": <select name="Col5">
                <option value="w">Term</option>
                <option value="t">Translation</option>
                <option value="r">Romanization</option>
                <option value="s">Sentence</option>
                <option value="g">Tag List</option>
                <option value="x" selected="selected">Don't import</option>
            </select><br />
            <br /><b>Import Mode</b>:<br />
            <select name="Over" onchange="if(parseInt(this.value)>3){$('#imp_transl_delim').removeClass('hide');$('#imp_transl_delim input').addClass('notempty');}else{ $('#imp_transl_delim input').removeClass('notempty');$('#imp_transl_delim').addClass('hide');}">
                <option value="0" title="- don't overwrite existent terms&#x000A;- import new terms" selected="selected">Import only new terms</option>
                <option value="1" title="- overwrite existent terms&#x000A;- import new terms">Replace all fields</option>
                <option value="2" title="- update only empty fields&#x000A;- import new terms">Update empty fields</option>
                <option value="3" title="- overwrite existing terms with new not empty values&#x000A;- don't import new terms">No new terms</option>
                <option value="4" title="- add new translations to existing ones&#x000A;- import new terms">Merge translation fields</option>
                <option value="5" title="- add new translations to existing ones&#x000A;- don't import new terms">Update existing translations</option>
            </select>
            <br />
            <div class="hide" id="imp_transl_delim">Import Translation Delimiter:<br />
                <input class="notempty" type="text" name="transl_delim" style="width:4em;" value="<?php echo getSettingWithDefault('set-term-translation-delimiters'); ?>" />
                <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
            </div><br />
            <b>Important:</b><br />
            You must specify the term.<br />
            Translation, romanization, <br />sentence and tag list<br />
            are optional. The tag list <br />must be separated either<br />by spaces or commas.
        </td>
        <td class="td1">
            Either specify a <b>File to upload</b>:<br />
            <input name="thefile" type="file" /><br /><br />
            <b>Or</b> type in or paste from clipboard (do <b>NOT</b> specify file):<br />
            <textarea class="checkoutsidebmp" data_info="Upload" name="Upload" cols="60" rows="25"></textarea>
        </td>
    </tr>
    <tr>
        <td class="td1 center"><b>Status</b> for all uploaded terms:</td>
        <td class="td1">
            <select class="notempty" name="WoStatus">
                <?php echo get_wordstatus_selectoptions(null, false, false); ?>
            </select>
            <img src="icn/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
        </td>
    </tr>
    <tr>
        <td class="td1 center" colspan="2">
            <span class="red2">
                A DATABASE <input type="button" value="BACKUP" onclick="location.href='backup_restore.php';" /> MAY BE ADVISABLE!<br />
                PLEASE DOUBLE-CHECK EVERYTHING!
            </span>
            <br />
            <input type="button" value="&lt;&lt; Back" onclick="location.href='index.php';" /> &nbsp; &nbsp; &nbsp; | &nbsp; &nbsp; &nbsp; <input type="submit" name="op" value="Import" />
        </td>
    </tr>
    </table>
    </form>
    
    <p>
        Sentences should contain the term in curly brackets "... {term} ...".<br />
        If not, such sentences can be automatically created later with the <br />
        "Set Term Sentences" action in the <input type="button" value="My Texts" onclick="location.href='edit_texts.php?query=&amp;page=1';" /> screen.
    </p>

    <?php
}



pagestart('Import Terms', true);

// Import
if (isset($_REQUEST['op'])) {
    // INSERT
    if ($_REQUEST['op'] == 'Import') { 
        upload_words_import();
    } else {
        // $_REQUEST['op'] == 'Import'
        $message = 'Error: Wrong Operation: ' . $_REQUEST['op'];
        echo error_message_with_hide($message, 0);
    }
} else {
    upload_words_display();
}

pageend();

?>
