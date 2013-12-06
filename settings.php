<?php
$page_title='System Settings';
require_once 'includes/wqinc.php';
$pagelevel = PAGE_ADMIN;

require_once 'includes/qp_header.php';

login_check($pagelevel, $log_user);

if ($_POST['sub']=="Submit" &&  $_POST['faction']=="edit")
{
	$throw_error=false;
	$action_result="updated";
	$query = "UPDATE general_values SET fieldvalue=? WHERE fieldname =?";
	$stmt = mysqli_prepare($mysqlid, $query);
	mysqli_stmt_bind_param($stmt, 'ss', $fieldvalue , $fieldname);
	$fieldvalue=$_POST['fieldvalue'];
	$fieldname=$_POST['fieldname'];
	mysqli_stmt_execute($stmt);
	if (mysqli_stmt_errno($stmt)){ 
		printf("Error: %d.\n", mysqli_stmt_errno($stmt)); 
		printf("Error: %s.\n", mysqli_stmt_error($stmt));
	}
	else print "<p>$fieldname $action_result</p>";
}

if ($_POST['sub']=="Submit" &&  $_POST['faction']=="new")
{
	$throw_error=false;
	$action_result="added";
	$query = "INSERT INTO general_values (fieldname, fieldvalue) VALUES (?,?)";
	$stmt = mysqli_prepare($mysqlid, $query);
	mysqli_stmt_bind_param($stmt, 'ss', $fieldname, $fieldvalue);
	$fieldvalue=$_POST['fieldvalue'];
	$fieldname=$_POST['fieldname'];
	mysqli_stmt_execute($stmt);
	if (mysqli_stmt_errno($stmt)){ 
		printf("Error: %d.\n", mysqli_stmt_errno($stmt)); 
		printf("Error: %s.\n", mysqli_stmt_error($stmt));
	}
	else print "<p>$fieldname $action_result</p>";
}

if ($_GET['faction']=="edit")
{
	$action_descriptor = "Edit";
	$fieldvalue = get_general_value_table($_GET['fieldname'],$mysqlid);		
	?>
	<form action="settings.php" name="f1" id="f1" method="POST">
	<input type="hidden" name="faction" value="edit">
	<input type="hidden" name="fieldname" value="<?php print $_GET['fieldname'];?>">
	<h2>Edit Setting</h2>
	<table style="width:550px">
	<tr><td class="tdright"><?php print $_GET['fieldname'];?></td><td><input name="fieldvalue" type="text" style="width:400px" value="<?php print "$fieldvalue";?>"></td></tr>
	</table>	
	<input type="submit" name="sub" value="Submit"><input type="button" name="cancel" value="Cancel" onclick="location.href='<?php print $_SERVER["PHP_SELF"]?>'">


	<?php
	$def_vis="none";
}
if ($_GET['faction']=="new")
{
	$action_descriptor = "New";
	?>
	<form action="settings.php" name="f1" id="f1" method="POST">
	<input type="hidden" name="faction" value="new">
	<h2>Edit Setting</h2>
	<table style="width:550px">
	<tr><td class="tdright">name</td><td><input name="fieldname" type="text" style="width:400px""></td></tr>
	<tr><td class="tdright">value</td><td><input name="fieldvalue" type="text" style="width:400px""></td></tr>
	</table>	
	<input type="submit" name="sub" value="Submit"><input type="button" name="cancel" value="Cancel" onclick="location.href='<?php print $_SERVER["PHP_SELF"]?>'">


	<?php
	$def_vis="none";
}

?>

<table width="500px" class="listtable">
<tr><th colspan=3>Settings</th></tr>
<?php 
$mt_tr=array();
$mt_types=array();
$query = "SELECT * FROM general_values ORDER BY fieldname ASC";
$res = mysqli_query($mysqlid, $query);
$group="";
while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC))
{
	print "<tr><td>".$row['fieldname']."</td>
		<td><a href=\"settings.php?faction=edit&fieldname=".$row['fieldname']."\">edit</a></td></tr>";
	
}


mysqli_free_result($res);
?></tbody>
</table>

<?php
require_once 'includes/qp_footer.php';
?>	