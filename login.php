<?php
$page_title='Login';
require_once 'includes/wqinc.php';
$pagelevel = PAGE_OPEN;

if ($log_user->process_login()===true)
{
	header("Location: ".$_GET['linkback']);
exit;
}
else print "you be fubar";

