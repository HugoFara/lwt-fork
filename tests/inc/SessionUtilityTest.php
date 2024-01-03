<?php declare(strict_types=1);

require __DIR__ . "/../../connect.inc.php";
$GLOBALS['dbname'] = "test_" . $dbname;
require_once __DIR__ . '/../../inc/session_utility.php';

use PHPUnit\Framework\TestCase;

class SessionUtilityTest extends TestCase
{
    public function testInstallDemoDB()
    {
        truncateUserDatabase();
        $filename = getcwd() . '/install_demo_db.sql';
        $this->assertFileExists($filename);
        $this->assertFileIsReadable($filename);
        $handle = fopen($filename, "r");
        $message = restore_file($handle, "Demo Database");
        $this->assertStringStartsNotWith("Error: ", $message);
    }
}
?>
