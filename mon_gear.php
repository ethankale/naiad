<?php
$page_title='Gear Configuration';
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
		$query = "INSERT INTO mon_gear (gear_id, description,lake, stream,  active) VALUES (?, ?, ?, ?, ?)";
		$stmt = mysqli_prepare($mysqlid, $query);
		mysqli_stmt_bind_param($stmt, 'ssiii', $gear_id,$description,$lake, $stream, $active);	
	}
	if($_POST['faction']=="edit") 
	{
		$action_result="updated";
		$query = "UPDATE mon_gear SET gear_id=?,description=?,lake=?, stream=?, active=? WHERE gear_id =?";
		$stmt = mysqli_prepare($mysqlid, $query);
		mysqli_stmt_bind_param($stmt, 'ssiiis', $gear_id,$description,$lake, $stream, $active, $oldgear_id);
		$oldgear_id=$_POST['oldgear_id'];
	}
	$gear_id=$_POST['gear_id'];
	$description=$_POST['description'];
	$lake=($_POST['lake']==1)?1:0;
	$stream=($_POST['stream']==1)?1:0;
	$active=($_POST['active']==1)?1:0;
	mysqli_stmt_execute($stmt);
	if (mysqli_stmt_errno($stmt)){ 
		printf("Error: %d.\n", mysqli_stmt_errno($stmt)); 
		printf("Error: %s.\n", mysqli_stmt_error($stmt));
	}
	else print "<p>Gear Configuration $gear_id $action_result</p>";
}

if ($_POST['faction']=="delete" && $_POST['gear_id'])
{
	$query = "DELETE FROM mon_gear WHERE gear_id=?";
	$stmt = mysqli_prepare($mysqlid, $query);
	mysqli_stmt_bind_param($stmt, 's', $_POST['gear_id']);
	mysqli_stmt_execute($stmt);	
	if (mysqli_stmt_errno($stmt) == 1451){
		print "<p>".$_POST['gear_id']." can not be deleted because it has measurements associated with it.</p>"; 
	}
	else if (mysqli_stmt_errno($stmt))
	{
		printf("Error: %d.\n", mysqli_stmt_errno($stmt)); 
		printf("Error: %s.\n", mysqli_stmt_error($stmt));
	}
	else print "<p>Gear Configuration ".$_POST['gear_id']." deleted</p>";
}
if ($_GET['faction']=="new" || $_GET['faction']=="edit")
{
	$action_descriptor = "New";
	if ($_GET['faction']=="edit" && $_GET['gear_id'])
	{
		$action_descriptor = "Edit";
		$query = "SELECT gear_id, description,lake,stream,active FROM mon_gear WHERE gear_id=?";
		$stmt = mysqli_prepare($mysqlid, $query); 
		if($stmt==false) {printf("Errormessage: %s\n", mysqli_error($mysqlid));exit;}
		mysqli_stmt_bind_param($stmt, "s", $_GET['gear_id']);	
		mysqli_stmt_execute($stmt);
		mysqli_stmt_bind_result($stmt, $gear_id,$description,$lake, $stream, $active);
		mysqli_stmt_fetch($stmt);
		mysqli_stmt_close($stmt);
		
		
	}
	?>
	<form action="mon_gear.php" name="f1" id="f1" method="POST">
	<input type="hidden" name="faction" value="<?php print $_GET['faction'];?>">
	<?php 
		if($_GET['faction']=="edit") {print "<input type=\"hidden\" name=\"oldgear_id\" value=\"$gear_id\">\n";}
	?>
	<h2><?php print $action_descriptor; ?>  Gear Configuration</h2>
	<table style="width:550px">
	<tr><td class="tdright">Gear Configuration ID</td><td><input name="gear_id" type="text" style="width:400px" value="<?php print "$gear_id";?>"></td></tr>
	<tr><td class="tdright">Description</td><td><input name="description" type="text" style="width:400px" value="<?php print "$description";?>"></td></tr>
	<tr><td class="tdright">Type available for:</td><td><input type="checkbox" name="lake" value="1" <?php if ($lake== 1) print " checked"?>> Lakes<br>
	 	<input type="checkbox" name="stream" value="1" <?php if ($stream== 1) print " checked"?>> Streams</td></tr>
	<tr><td class="tdright">&nbsp;</td><td><input type="checkbox" name="active" value="1" <?php if ($active== 1) print " checked"?>> Gear Configuration is active</td></tr>
	</table>
<?php		
	$allow_delete=false;
	if ($_GET['faction']=="edit" && $_GET['gear_id'])
	{
		$action_descriptor = "Edit";
		$query = "SELECT COUNT(m.value) AS datapoints, DATE_FORMAT(MIN(m.mtime), '%c/%e/%Y') AS firstpoint, DATE_FORMAT( MAX(m.mtime), '%c/%e/%Y') AS lastpoint 
			FROM measurements m WHERE m.gear_id = ? group by m.gear_id";
		$stmt = mysqli_prepare($mysqlid, $query); 
		if($stmt==false) {printf("Errormessage: %s\n", mysqli_error($mysqlid));exit;}
		mysqli_stmt_bind_param($stmt, "s", $_GET['gear_id']);	
		mysqli_stmt_execute($stmt);
		mysqli_stmt_bind_result($stmt, $datapoints,$firstpoint,$lastpoint);
		mysqli_stmt_fetch($stmt);
		mysqli_stmt_close($stmt);
		if 	($datapoints > 0)
		{
			print "There are $datapoints data values for $gear_id from $firstpoint to $lastpoint<br>\n";

			$allow_delete=false;
			
		}
		else 
		{
			print "There are no data values associated with this method.<br><br>\n";
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
<tr><th colspan=3>Gear Configuration</th></tr>
<tbody><tr><td colspan=2><a href="mon_gear.php?faction=new">New Gear Configuration</a></td></tr>
<?php 
$mt_tr=array();
$mt_types=array();
$query = "SELECT * FROM mon_gear ORDER BY gear_id ASC";
$res = mysqli_query($mysqlid, $query);
$group="";
while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC))
{
	print "<tr><td>".$row['gear_id']."</td>
		<td><a href=\"mon_gear.php?faction=edit&gear_id=".$row['gear_id']."\">edit</a></td></tr>";
	
}


mysqli_free_result($res);
?></tbody>
</table>

<?php
require_once 'includes/qp_footer.php';
?>	