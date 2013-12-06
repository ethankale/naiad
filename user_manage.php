<?php
$page_title='User Management';
require_once 'includes/wqinc.php';
$pagelevel = PAGE_ADMIN;

require_once 'includes/qp_header.php';

login_check($pagelevel, $log_user);

if ($_GET['action']=="edit" && $_GET['userid']>0)
{
	//$edit_user=new MCWDUser($_GET['userid']);
	$log_user->show_profile_edit_form($_SERVER['SCRIPT_NAME'],$_GET['userid']);
}

if ($_GET['action']=="delete" && $_GET['userid']>0)
{
	$log_user->delete_account($_GET['userid']);
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

if ($_GET['action']=="new")
{
	$log_user->show_registration_form($_SERVER['SCRIPT_NAME']);
}

if ($_POST['action'] =='insert'){
	//process the user registration
	$returned=$log_user->process_registration();
	if($returned===true){
		echo "User successfully added.<br>";
	}else{
		echo "There was a problem adding the user: ".$returned." Please try again!<br>";
		$log_user->show_registration_form($_SERVER['SCRIPT_NAME']);
	}
}
?>




<a href="user_manage.php?action=new">New User</a><br>
<?php 

$userA=$log_user->all_users();
print "<table class='listtable'>
 <tr><th>UserID</th><th>First Name</th><th>Last Name</th><th>Email</th><th>Admin</th></tr>";
foreach($userA as $userID=>$user){
	print " <tr><td>$userID</td><td>".$user['fname']."</td><td>".$user['lname']."</td><td>".$user['email']."</td><td>".($user['is_admin']==0?"No":"Yes")."</td>";
	print "  <td><a href='user_manage.php?action=edit&userid=$userID'>edit<a>&nbsp</td>";
	print "  <td><a href='user_manage.php?action=delete&userid=$userID'>delete<a></td></tr>";
}
echo "</table>";


















require_once 'includes/qp_footer.php';
?>	