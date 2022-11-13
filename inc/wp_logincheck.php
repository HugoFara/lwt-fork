<?php

/**
 * \file
 * \brief WordPress Login Check
 * 
 * To be inserted in "connect.inc.php" when LWT used with WordPress
 * 
 * @package Lwt
 * @author  HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/html/wp__logincheck_8php.html
 * @since   2.0.3-fork
 */

require_once __DIR__ . '/start_session.php';

/** The ACTUAL database name that will be used for this wordpress user! */
$dbname = null;

$lwtwpuser = $_SESSION['LWT-WP-User'];
if (isset($lwtwpuser)) {
    global $dbname;
    $dbname = "{$rootdbname}_{$lwtwpuser}";
    $tbpref = '';
} else {
    $url = '';
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        $url = $_SERVER['REQUEST_URI'];
    } else if (isset($_SERVER['HTTP_REFERER'])) {
        $url = $_SERVER['HTTP_REFERER'];
    }
    if (strpos($url, "/") !== false) {
        $url = substr($url, strrpos($url, '/') + 1);
    }
    header("Location: ./wp_lwt_start.php?rd=". urlencode($url));
    exit();
}

?>