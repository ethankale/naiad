<?php
$page_title='Monitoring Projects';
require_once 'includes/wqinc.php';
$pagelevel = PAGE_ADMIN;

require_once 'includes/qp_header.php';

login_check($pagelevel, $log_user);

if ($_POST['sub']=="Submit" && ($_POST['faction']=="new" || $_POST['faction']=="edit"))
{
	$throw_error=false;
	if($_POST['faction']=="new") 
	{
		$action_result="added";
		$query = "INSERT INTO mon_projects (proj_id, description, vendor, active) VALUES (?, ?, ?, ?)";
		$stmt = mysqli_prepare($mysqlid, $query);
		mysqli_stmt_bind_param($stmt, 'sssi', $proj_id,$description,$vendor,$active);	
	}
	if($_POST['faction']=="edit") 
	{
		$action_result="updated";
		$query = "UPDATE mon_projects SET proj_id=?,description=?,vendor=?,active=? WHERE proj_id =?";
		$stmt = mysqli_prepare($mysqlid, $query);
		mysqli_stmt_bind_param($stmt, 'sssis', $proj_id,$description,$vendor,$active, $oldproj_id);
		$oldproj_id=$_POST['oldproj_id'];
	}
	$proj_id=$_POST['proj_id'];
	$description=$_POST['description'];
	$vendor=$_POST['vendor'];
	$active=($_POST['active']==1)?1:0;
	mysqli_stmt_execute($stmt);
	if (mysqli_stmt_errno($stmt)){ 
		printf("Error: %d.\n", mysqli_stmt_errno($stmt)); 
		printf("Error: %s.\n", mysqli_stmt_error($stmt));
	}
	else print "<p>Monitoring Project $proj_id $action_result</p>";
}

if ($_POST['faction']=="delete" && $_POST['proj_id'])
{
	$query = "DELETE FROM mon_projects WHERE proj_id=?";
	$stmt = mysqli_prepare($mysqlid, $query);
	mysqli_stmt_bind_param($stmt, 's', $_POST['proj_id']);
	mysqli_stmt_execute($stmt);	
	if (mysqli_stmt_errno($stmt) == 1451){
		print "<p>".$_POST['proj_id']." can not be deleted because it has monitoring sites associated with it.</p>"; 
	}
	else if (mysqli_stmt_errno($stmt))
	{
		printf("Error: %d.\n", mysqli_stmt_errno($stmt)); 
		printf("Error: %s.\n", mysqli_stmt_error($stmt));
	}
	else print "<p>Monitoring Project ".$_POST['proj_id']." deleted</p>";
}
if ($_GET['faction']=="new" || $_GET['faction']=="edit")
{
	$action_descriptor = "New";
	if ($_GET['faction']=="edit" && $_GET['proj_id'])
	{
		$action_descriptor = "Edit";
		$query = "SELECT proj_id, description, vendor, active FROM mon_projects WHERE proj_id=?";
		$stmt = mysqli_prepare($mysqlid, $query); 
		if($stmt==false) {printf("Errormessage: %s\n", mysqli_error($mysqlid));exit;}
		mysqli_stmt_bind_param($stmt, "s", $_GET['proj_id']);	
		mysqli_stmt_execute($stmt);
		mysqli_stmt_bind_result($stmt, $proj_id,$description,$vendor,$active);
		mysqli_stmt_fetch($stmt);
		mysqli_stmt_close($stmt);
		
		
	}
	?>
	<form action="mon_projects.php" name="f1" id="f1" method="POST">
	<input type="hidden" name="faction" value="<?php print $_GET['faction'];?>">
	<?php 
		if($_GET['faction']=="edit") {print "<input type=\"hidden\" name=\"oldproj_id\" value=\"$proj_id\">\n";}
	?>
	<h2><?php print $action_descriptor; ?>  Monitoring Projects</h2>
	<table style="width:550px">
	<tr><td class="tdright">Project ID</td><td><input name="proj_id" type="text" style="width:400px" value="<?php print "$proj_id";?>"></td></tr>
	<tr><td class="tdright">Description</td><td><input name="description" type="text" style="width:400px" value="<?php print "$description";?>"></td></tr>
	<tr><td class="tdright">Vendor</td><td><input name="vendor" type="text" style="width:400px" value="<?php print "$vendor";?>"></td></tr>
	<tr><td class="tdright">&nbsp;</td><td><input type="checkbox" name="active" value="1" <?php if ($active== 1) print " checked"?>> Monitoring Project is active</td></tr>
	</table>
<?php		
	$allow_delete=false;
	if ($_GET['faction']=="edit" && $_GET['proj_id'])
	{
		$action_descriptor = "Edit";
		$query = "SELECT COUNT(m.value) AS datapoints, DATE_FORMAT(MIN(m.mtime), '%c/%e/%Y') AS firstpoint, DATE_FORMAT( MAX(m.mtime), '%c/%e/%Y') AS lastpoint 
			FROM measurements m WHERE m.proj_id = ? group by m.proj_id";
		$stmt = mysqli_prepare($mysqlid, $query); 
		if($stmt==false) {printf("Errormessage: %s\n", mysqli_error($mysqlid));exit;}
		mysqli_stmt_bind_param($stmt, "s", $_GET['proj_id']);	
		mysqli_stmt_execute($stmt);
		mysqli_stmt_bind_result($stmt, $datapoints,$firstpoint,$lastpoint);
		mysqli_stmt_fetch($stmt);
		mysqli_stmt_close($stmt);
		if 	($datapoints > 0)
		{
			print "There are $datapoints data values for $proj_id from $firstpoint to $lastpoint<br>\n";

			$allow_delete=false;
			
		}
		else 
		{
			print "There are no data values associated with this site.<br><br>\n";
			$allow_delete=true;
		}
		
	}
?>		
	<input type="submit" name="sub" value="Submit"><?php if ($allow_delete) {print "<input type=\"button\" name=\"delete\" value=\"Delete\" onclick=\"delete_site(document.forms['f1']);\">";}?>
	<input type="button" name="cancel" value="Cancel" onclick="location.href='<?php print $_SERVER["PHP_SELF"]?>'">
	<div id="calendarDiv"></div></form>
<script language="javascript">
function delete_site (form_sub)
{
	if (confirm('Are you sure you want to delete this waterbody?')) 
	{
		form_sub.faction.value='delete'; 
		form_sub.submit();
	}
}

</script>
	<?php
	$def_vis="none";
}


?>

<table width="500px" class="listtable">
<tr><th colspan=3>Monitoring Projects</th></tr>

<tbody><tr><td colspan=2><a href="mon_projects.php?faction=new">New Monitoring Project</a></td></tr>
<?php 
$mt_tr=array();
$mt_types=array();
$query = "SELECT * FROM mon_projects ORDER BY description ASC";
$res = mysqli_query($mysqlid, $query);
$group="";
while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC))
{
	print "<tr><td>".$row['proj_id']."</td>
		<td><a href=\"mon_projects.php?faction=edit&proj_id=".$row['proj_id']."\">edit</a></td></tr>";
	
}


mysqli_free_result($res);
?></tbody>
</table>

<?php
require_once 'includes/qp_footer.php';
?>	