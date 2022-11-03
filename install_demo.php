<?php

/**************************************************************
Call: install_demo.php
Install LWT Demo Database
 ***************************************************************/

require_once 'inc/session_utility.php';
require 'inc/db_restore.php';

$message = '';

if (isset($_REQUEST['install_new'])) {
    $message = install_new_db();
} 
if (isset($_REQUEST['install_demo'])) {
    $message = install_demo_db();
} 

pagestart('Install LWT Database', true);

echo error_message_with_hide($message, 1);

$langcnt = get_first_value('select count(*) as value from ' . $tbpref . 'languages');

if ($tbpref == '') { 
    $prefinfo = "(Default Table Set)"; 
}
else {
    $prefinfo = "(Table Set: <i>" . tohtml(substr($tbpref, 0, -1)) . "</i>)"; 
}

?>
<form enctype="multipart/form-data" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" onsubmit="return confirm('Are you sure?');">
<table class="tab3" cellspacing="0" cellpadding="5">
<tr>
<th class="th1 center">Install Database</th>
<td class="td1">
<p class="smallgray2">
The database <i><?php echo tohtml($dbname); ?></i> <?php echo $prefinfo; ?> will be <b>replaced</b> with a new database.

<?php 
if ($langcnt > 0 ) { 
    ?>
    <br />The existent database will be <b>overwritten!</b>
    <?php 
} 
?>

</p>
<p><br /><span class="red2" style="align:left;">YOU MAY LOSE DATA - BE CAREFUL!</span></p>
<p><input type="submit" name="install_new" value="Install new empty LWT database" /></p>
<p><input type="submit" name="install_demo" value="Install LWT database with demo data" /></p>
</td>
</tr>
<tr>
<td class="td1 right" colspan="2"> 
<input type="button" value="&lt;&lt; Back to LWT Main Menu" onclick="location.href='index.php';" /></td>
</tr>
</table>
</form>

<?php

pageend();

?>
