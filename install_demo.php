<?php

/**************************************************************
Call: install_demo.php
Install LWT Demo Database
 ***************************************************************/

require_once 'inc/session_utility.php';
require 'inc/db_restore.php';

$message = '';

if (isset($_REQUEST['install'])) {
    $message = install_demo_db();
} 

pagestart('Install LWT Demo Database', true);

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
<th class="th1 center">Install Demo</th>
<td class="td1">
<p class="smallgray2">
The database <i><?php echo tohtml($dbname); ?></i> <?php echo $prefinfo; ?> will be <b>replaced</b> by the LWT demo database.

<?php 
if ($langcnt > 0 ) { 
    ?>
    <br />The existent database will be <b>overwritten!</b>
    <?php 
} 
?>

</p>
<p class="right">&nbsp;<br /><span class="red2">YOU MAY LOSE DATA - BE CAREFUL: &nbsp; &nbsp; &nbsp;</span> 
<input type="submit" name="install" value="Install LWT demo database" /></p>
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
