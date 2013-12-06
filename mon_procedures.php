<?php
$page_title='Monitoring Procedures';
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
		$query = "INSERT INTO sample_procs (proc_id, description, lake, stream,sample_depth_upper,sample_depth_lower, active) 
			VALUES (?, ?, ?, ?, ?, ?, ?)";
		$stmt = mysqli_prepare($mysqlid, $query);
		mysqli_stmt_bind_param($stmt, 'ssiiddi', $proc_id,$description,$lake, $stream, $sample_depth_upper, $sample_depth_lower, $active);	
	}
	if($_POST['faction']=="edit") 
	{
		$action_result="updated";
		$query = "UPDATE sample_procs SET proc_id=?,description=?,lake=?,stream=?,sample_depth_upper=?,sample_depth_lower=?,active=? 
			WHERE proc_id =?";
		$stmt = mysqli_prepare($mysqlid, $query);
		mysqli_stmt_bind_param($stmt, 'ssiiddis', $proc_id,$description,$lake, $stream, $sample_depth_upper, $sample_depth_lower, $active, $oldproc_id);
		$oldproc_id=$_POST['oldproc_id'];
	}
	$proc_id=$_POST['proc_id'];
	$description=$_POST['description'];
	$lake=($_POST['lake']==1)?1:0;
	$stream=($_POST['stream']==1)?1:0;
	$active=($_POST['active']==1)?1:0;
	$sample_depth_upper=$_POST['sample_depth_upper']!==""?$_POST['sample_depth_upper']:NULL;
	$sample_depth_lower=$_POST['sample_depth_lower']!==""?$_POST['sample_depth_lower']:NULL;
	mysqli_stmt_execute($stmt);
	if (mysqli_stmt_errno($stmt)){ 
		printf("Error: %d.\n", mysqli_stmt_errno($stmt)); 
		printf("Error: %s.\n", mysqli_stmt_error($stmt));
	}
	else print "<p>Sampling Procedure $proc_id $action_result</p>";
}

if ($_POST['faction']=="delete" && $_POST['proc_id'])
{
	$query = "DELETE FROM sample_procs WHERE proc_id=?";
	$stmt = mysqli_prepare($mysqlid, $query);
	mysqli_stmt_bind_param($stmt, 's', $_POST['proc_id']);
	mysqli_stmt_execute($stmt);	
	if (mysqli_stmt_errno($stmt) == 1451){
		print "<p>".$_POST['proc_id']." can not be deleted because it has measurements associated with it.</p>"; 
	}
	else if (mysqli_stmt_errno($stmt))
	{
		printf("Error: %d.\n", mysqli_stmt_errno($stmt)); 
		printf("Error: %s.\n", mysqli_stmt_error($stmt));
	}
	else print "<p>Monitoring Project ".$_POST['proc_id']." deleted</p>";
}
if ($_GET['faction']=="new" || $_GET['faction']=="edit")
{
	$action_descriptor = "New";
	if ($_GET['faction']=="edit" && $_GET['proc_id'])
	{
		$action_descriptor = "Edit";
		$query = "SELECT proc_id, description, lake, stream,sample_depth_upper,sample_depth_lower, active FROM sample_procs WHERE proc_id=?";
		$stmt = mysqli_prepare($mysqlid, $query); 
		if($stmt==false) {printf("Errormessage: %s\n", mysqli_error($mysqlid));exit;}
		mysqli_stmt_bind_param($stmt, "s", $_GET['proc_id']);	
		mysqli_stmt_execute($stmt);
		mysqli_stmt_bind_result($stmt, $proc_id,$description,$lake, $stream,$sample_depth_upper, $sample_depth_lower, $active);
		mysqli_stmt_fetch($stmt);
		mysqli_stmt_close($stmt);
		
		
	}
	?>
	<form action="mon_procedures.php" name="f1" id="f1" method="POST">
	<input type="hidden" name="faction" value="<?php print $_GET['faction'];?>">
	<?php 
		if($_GET['faction']=="edit") {print "<input type=\"hidden\" name=\"oldproc_id\" value=\"$proc_id\">\n";}
	?>
	<h2><?php print $action_descriptor; ?>  Monitoring Projects</h2>
	<table style="width:550px">
	<tr><td class="tdright">Procedure ID</td><td><input name="proc_id" type="text" style="width:400px" value="<?php print "$proc_id";?>"></td></tr>
	<tr><td class="tdright">Description</td><td><input name="description" type="text" style="width:400px" value="<?php print "$description";?>"></td></tr>
	<tr><td class="tdright">Type available for:</td><td><input type="checkbox" name="lake" value="1" <?php if ($lake== 1) print " checked"?>> Lakes<br>
	 	<input type="checkbox" name="stream" value="1" <?php if ($stream== 1) print " checked"?>> Streams</td></tr>
	<tr><td class="tdright">Sample Depth Upper</td><td><input name="sample_depth_upper" type="text" style="width:40px" value="<?php print "$sample_depth_upper";?>"></td></tr>
	<tr><td class="tdright">Sample Depth Lower</td><td><input name="sample_depth_lower" type="text" style="width:40px" value="<?php print "$sample_depth_lower";?>"></td></tr>
	<tr><td class="tdright">&nbsp;</td><td><input type="checkbox" name="active" value="1" <?php if ($active== 1) print " checked"?>> Sampling Procedure is active</td></tr>
	</table>
<?php		
	$allow_delete=false;
	if ($_GET['faction']=="edit" && $_GET['proc_id'])
	{
		$action_descriptor = "Edit";
		$query = "SELECT COUNT(m.value) AS datapoints, DATE_FORMAT(MIN(m.mtime), '%c/%e/%Y') AS firstpoint, DATE_FORMAT( MAX(m.mtime), '%c/%e/%Y') AS lastpoint 
			FROM measurements m WHERE m.proc_id = ? group by m.proc_id";
		$stmt = mysqli_prepare($mysqlid, $query); 
		if($stmt==false) {printf("Errormessage: %s\n", mysqli_error($mysqlid));exit;}
		mysqli_stmt_bind_param($stmt, "s", $_GET['proc_id']);	
		mysqli_stmt_execute($stmt);
		mysqli_stmt_bind_result($stmt, $datapoints,$firstpoint,$lastpoint);
		mysqli_stmt_fetch($stmt);
		mysqli_stmt_close($stmt);
		if 	($datapoints > 0)
		{
			print "There are $datapoints data values for $proc_id from $firstpoint to $lastpoint<br>\n";

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
<tr><th colspan=3>Sample Procedures</th></tr>
<tbody><tr><td colspan=2><a href="mon_procedures.php?faction=new">New Sample Procedure</a></td></tr>
<?php 
$mt_tr=array();
$mt_types=array();
$query = "SELECT * FROM sample_procs ORDER BY proc_id ASC";
$res = mysqli_query($mysqlid, $query);
$group="";
while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC))
{
	print "<tr><td>".$row['proc_id']."</td>
		<td><a href=\"mon_procedures.php?faction=edit&proc_id=".$row['proc_id']."\">edit</a></td></tr>";
	
}


mysqli_free_result($res);
?></tbody>
</table>

<?php
require_once 'includes/qp_footer.php';
?>	