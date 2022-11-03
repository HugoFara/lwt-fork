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
 * Finalize db tables and optimize.
 */
function finalize_restore() {
    global $tbpref;
    global $debug;
    global $dbname;

    runsql('DROP TABLE IF EXISTS ' . $tbpref . 'textitems', '');
    check_update_db($debug, $tbpref, $dbname);
    reparse_all_texts();
    optimizedb();
    get_tags(1);
    get_texttags(1);
}


/**
 * Execute a given filename.
 * 
 * @global string $tbpref Table name prefix
 * 
 * @return [ boolean pass_or_fail, string message ]
 */
function execute_sql_file($file): array
{
    if (! file_exists($file) ) {
        return [ false, "Error: File ' . $file . ' does not exist" ];
    }

    $handle = fopen($file, "r");
    if ($handle === false) {
        return [ false, "Error: File ' . $file . ' could not be opened" ];
    }

    global $tbpref;
    $error = "";
    $failed = false;
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

        if (($sql_line != "") && (substr($sql_line, 0, 3) !== '-- ')) {
            $sql_line = insert_prefix_in_sql($sql_line);
            $res = mysqli_query($GLOBALS['DBCONNECTION'], $sql_line);
            if ($res == false) {
                $failed = true;
                $error = mysqli_error($GLOBALS['DBCONNECTION']);
                $error = $sql_line . " => " . $error;
                break;
            }
        }
    } // while (! feof($handle))
    fclose($handle);

    if ($failed) {
        return [ false, $error ];
    }

    return [ true, "" ];
}


/**
 * Install a db using a set of files.
 * 
 * @global string $tbpref Table name prefix
 * 
 * @return string message.
 */
function install_db_fileset($files, $name): string 
{
    foreach ($files as $file) {
        $fullfile = getcwd() . '/db/' . $file;
        [ $result, $error ] = execute_sql_file($fullfile);
        if (! $result) {
            return $name . " NOT installed.  Error in " . $file . ": " . $error;
        }
    }

    finalize_restore();
    return "Success: " . $name . " installed.";
}


/**
 * Install a new db, with no demo data.
 * 
 * @global string $tbpref Table name prefix
 * 
 * @return string message.
 */
function install_new_db(): string 
{
    return install_db_fileset([ 'baseline_schema.sql', 'reference_data.sql' ], "New database");
}


/**
 * Install the db, including demo data.
 * 
 * @global string $tbpref Table name prefix
 * 
 * @return string message.
 */
function install_demo_db(): string 
{
    return install_db_fileset([ 'baseline_schema.sql', 'reference_data.sql', 'demo_data.sql' ], "New database and demo data");
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
        finalize_restore();
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