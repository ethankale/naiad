<?php
$page_title='User Management';
require_once 'includes/wqinc.php';
$pagelevel = PAGE_USER;

if ($_GET["action"]=='logout')
{
	$log_user->log_out();
	header("Location: ./");
	exit;
}
if ($_POST['action']=='login') 
{
	if ($log_user->process_login()===true)
	{
		header("Location: ".$_GET['linkback']);
		exit;
	}
	else {
		require_once 'includes/qp_header.php';
		print "<h2> Incorrect username or Password, please try again</h2>";
		require_once 'includes/qp_footer.php';
		exit;
	}
}


require_once 'includes/qp_header.php';

login_check($pagelevel, $log_user);

if ($_GET['action']=="edit" && $_GET['userid']>0)
{
	//$edit_user=new MCWDUser($_GET['userid']);
	$log_user->show_profile_edit_form($_SERVER['SCRIPT_NAME'],$_GET['userid']);
}

if ($_POST['action']=="update")
{
	$returned=$log_user->process_profile_edit();
	if($returned===true){
		echo "The profile was succefully updated!<br>";
	}else{
		echo "The profile could not be updated for some reason. ".$returned." Try again.<br>";
		$log_user->show_profile_edit_form($_SERVER['SCRIPT_NAME']);
	}
}
else
{
	//$edit_user=new MCWDUser($_GET['userid']);
	$log_user->show_profile_edit_form($_SERVER['SCRIPT_NAME']);
}



require_once 'includes/qp_footer.php';
?>	