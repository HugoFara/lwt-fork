<?php

/**
 * \file
 * \brief Database restore.
 * 
 * @package Lwt
 * @license Unlicense <http://unlicense.org/>
 */

require_once 'database_connect.php';


/**
 * Install the demo db.
 * 
 * @global string $tbpref Table name prefix
 * 
 * @return string message.
 */
function install_demo_db(): string 
{
    $file = getcwd() . '/db/install_demo_db.sql';
    if (! file_exists($file) ) {
        return "Error: File ' . $file . ' does not exist";
    }

    $handle = fopen($file, "r");
    if ($handle === false) {
        return "Error: File ' . $file . ' could not be opened";
    }

    global $tbpref;
    global $debug;
    global $dbname;
    $message = "";
    $lines = 0;
    $ok = 0;
    $errors = 0;
    $firsterr = "";
    $drops = 0;
    $inserts = 0;
    $creates = 0;
    $start = 1;
    while (! feof($handle)) {
        $sql_line = trim(
            str_replace(
                "\r", "",
                str_replace(
                    "\n", "",
                    fgets($handle, 99999)
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
                $res = mysqli_query($GLOBALS['DBCONNECTION'], insert_prefix_in_sql($sql_line));
                $lines++;
                if ($res == false) {
                    $errors++;
                    if ($firsterr == "") {
                        $firsterr = $sql_line . ' => ' . mysqli_error($GLOBALS['DBCONNECTION']);
                    }
                }
                else {
                    $ok++;
                    if (substr($sql_line, 0, 11) == "INSERT INTO") { 
                        $inserts++; 
                    }
                    elseif (substr($sql_line, 0, 10) == "DROP TABLE") { 
                        $drops++;
                    } elseif (substr($sql_line, 0, 12) == "CREATE TABLE") { 
                        $creates++;
                    }
                }
                // echo $ok . " / " . tohtml(insert_prefix_in_sql($sql_line)) . "<br />";
            }
        }
    } // while (! feof($handle))
    fclose($handle);
    if ($errors == 0) {
        runsql('DROP TABLE IF EXISTS ' . $tbpref . 'textitems', '');
        check_update_db($debug, $tbpref, $dbname);
        reparse_all_texts();
        optimizedb();
        get_tags(1);
        get_texttags(1);
        $message = "Success: Demo database installed - " .
        $lines . " queries - " . $ok . 
        " successful (" . $drops . "/" . $creates . " tables dropped/created, " . $inserts . " records added), " . 
        $errors . " failed.";
    } else if ($message == "") {
        $message = "Error: Demo database NOT restored - " .
        $lines . " queries - " . $ok . 
        " successful (" . $drops . "/" . $creates . " tables dropped/created, " . $inserts . " records added), " . 
        $errors . " failed.  First error: " . $firsterr;
    }
    return $message;
}


/**
 * Restore db from a given gzip file handle.
 * 
 * @global string $tbpref Table name prefix
 * 
 * @return string message.
 */
function restore_gzip($handle, $title): string 
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
                do_mysqli_query(insert_prefix_in_sql($sql_line)); // merge conflict
                $res = mysqli_query($GLOBALS['DBCONNECTION'], insert_prefix_in_sql($sql_line));
                $lines++;
                if ($res == false) { $errors++; 
                }
                else {
                    $ok++;
                    if (substr($sql_line, 0, 11) == "INSERT INTO") { 
                        $inserts++; 
                    }
                    elseif (substr($sql_line, 0, 10) == "DROP TABLE") { 
                        $drops++;
                    } elseif (substr($sql_line, 0, 12) == "CREATE TABLE") { 
                        $creates++;
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
        get_tags(1);
        get_texttags(1);
        $message = "Success: " . $title . " restored - " .
        $lines . " queries - " . $ok . 
        " successful (" . $drops . "/" . $creates . " tables dropped/created, " . $inserts . " records added), " . 
        $errors . " failed.";
    } else if ($message == "") {
        $message = "Error: " . $title . " NOT restored - " .
        $lines . " queries - " . $ok . 
        " successful (" . $drops . "/" . $creates . " tables dropped/created, " . $inserts . " records added), " . 
        $errors . " failed.";
    }
    return $message;
}

?>