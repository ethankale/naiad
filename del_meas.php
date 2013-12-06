<?php
/*
 * Page for deleting measurements
 */
$page_title='Measurement Types';
require_once 'includes/wqinc.php';
$pagelevel = PAGE_ADMIN;

require_once 'includes/qp_header.php';

login_check($pagelevel, $log_user);

if ($_POST['action']=="delete" )
{
	$siteid = $_POST['siteid'];
	$mtid=$_POST['mtid'];
	$ed = $_POST['ed'];
	$sd=$_POST['sd'];
	if ($mtid==-1) {
		$query = "DELETE FROM measurements WHERE siteid=?  AND mtime>=?  AND mtime<=?";
		$stmt = mysqli_prepare($mysqlid, $query); 
		if($stmt==false) {printf("Errormessage: %s\n", mysqli_error($mysqlid));exit;}
		mysqli_stmt_bind_param($stmt, "sss", $siteid, $sd,$ed);	
	}
	else {
		$query = "DELETE FROM measurements WHERE siteid=? AND mtypeid=? AND mtime>=?  AND mtime<=?";
		$stmt = mysqli_prepare($mysqlid, $query); 
		if($stmt==false) {printf("Errormessage: %s\n", mysqli_error($mysqlid));exit;}
		mysqli_stmt_bind_param($stmt, "ssss", $siteid,$mtid, $sd,$ed);	
	}
	
	mysqli_stmt_execute($stmt);
	print mysqli_stmt_affected_rows($stmt) . " rows deleted";
	mysqli_stmt_close($stmt);
	
	
	
}


// if POST form submission update the Measurement type
if ($_POST['submit']=="Submit" && ($_POST['action']=="confirm" ))
{
	$stdate = $_POST["stdate"];
	$enddate = $_POST["enddate"];
	$sd = $stdate." 00:00:00";
	$ed = $enddate." 23:59:59";
	$siteid = $_POST['siteid'];
	$query = "SELECT monitor_type,waterbody_id FROM monitoring_sites WHERE siteid=?";
	$stmt = mysqli_prepare($mysqlid, $query); 
	if($stmt==false) {printf("Errormessage: %s\n", mysqli_error($mysqlid));exit;}
	mysqli_stmt_bind_param($stmt, "s", $siteid);	
	mysqli_stmt_execute($stmt);
	mysqli_stmt_bind_result($stmt, $wb_type,$waterbody_id);
	mysqli_stmt_fetch($stmt);
	mysqli_stmt_close($stmt);
	
	$mtid=$_POST['mtid'];
	if ($mtid==-1) {
		$query = "SELECT count(*) as datapoints, DATE_FORMAT(MIN(mtime),'%c/%e/%Y') as firstpoint, DATE_FORMAT(MAX(mtime),'%c/%e/%Y') as lastpoint FROM measurements 
			WHERE siteid=?  AND mtime>=?  AND mtime<=? GROUP BY siteid";
		$stmt = mysqli_prepare($mysqlid, $query); 
		if($stmt==false) {printf("Errormessage: %s\n", mysqli_error($mysqlid));exit;}
		mysqli_stmt_bind_param($stmt, "sss", $siteid, $sd,$ed);	
	}
	else {
		$query = "SELECT count(*) as datapoints, DATE_FORMAT(MIN(mtime),'%c/%e/%Y') as firstpoint, DATE_FORMAT(MAX(mtime),'%c/%e/%Y') as lastpoint FROM measurements 
			WHERE siteid=? AND mtypeid=? AND mtime>=?  AND mtime<=? GROUP BY siteid";
		$stmt = mysqli_prepare($mysqlid, $query); 
		if($stmt==false) {printf("Errormessage: %s\n", mysqli_error($mysqlid));exit;}
		mysqli_stmt_bind_param($stmt, "ssss", $siteid,$mtid, $sd,$ed);	
	}
	mysqli_stmt_execute($stmt);
	mysqli_stmt_bind_result($stmt, $datapoints,$firstpoint,$lastpoint);
	mysqli_stmt_fetch($stmt);
	mysqli_stmt_close($stmt);
	if 	($datapoints > 0)
	{
		print "There are $datapoints data values at site $siteid with measurement type ".(($mtid==-1)?"ALL":$mtid). " from $firstpoint to $lastpoint<br>\n";
		list($m,$d,$y)=explode("/",$firstpoint);
		$sdate=$y."-01-01";
		list($m,$d,$y)=explode("/",$lastpoint);
		$edate=$y."-12-31";
		$link = "measurements_report.php?storet_output=true&wbody_type=$wb_type"."&waterbodyid=$waterbody_id"."&siteid[]=$siteid"."&stdate=$stdate"."&enddate=$enddate";
		print "<a href=\"$link\" target=\"_blank\">See data</a><br><br>\n";
		?>
	<form action="del_meas.php" method="POST">
	<input type="hidden" name="action" value="delete">
	<h2>Delete Measurements</h2>
	<table style="width:650px">
	<tr><td class="tdright">Site ID</td><td><input type="hidden" name="siteid" value="<?php print $siteid;?>"><?php print $siteid?></td></tr>
	<tr><td class="tdright">Measurement Type</td><td><input type="hidden" name="mtid" value="<?php print $mtid;?>"><?php print ($mtid==-1?"ALL":"$mtid");?></td></tr>
	
	<tr><td class="tdright">Start Date</td><td><input type="hidden" name="sd" value="<?php print $sd;?>"><?php print $stdate?></td></tr>
	<tr><td class="tdright">End Date</td><td><input type="hidden" name="ed" value="<?php print $ed;?>"><?php print $enddate?></td></tr>

	</table>
	<input type="submit" name="submit" value="Confirm Delete"><input type="button" name="cancel" value="Cancel" onclick="location.href='<?php print $_SERVER["PHP_SELF"]?>'">
</form>
		
		<?php 
	}
	else 
	{
		print "There are no data values associated with this site.<br>\n<a href=\"".$_SERVER["PHP_SELF"]."\">Search again</a><br><br>\n";
	}
	
}

if (!$_POST["action"])
{
?>
	<form action="del_meas.php" method="POST"  onsubmit="return validate(this);">
	<input type="hidden" name="action" value="confirm">
	<h2>Delete Measurements</h2>
	<table style="width:650px">
	<tr><td class="tdright">Site ID</td><td><select name="siteid">
	<option value="">Select</option>
	<?php 	$query = "SELECT siteid,site_description FROM monitoring_sites ORDER BY siteid aSC";
	$res = mysqli_query($mysqlid, $query);
	while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC))
	{
		print '<option value="'.$row['siteid'].'">'.$row['siteid']. " - ". substr($row['site_description'],0,30). "</option>\n";
	}
	mysqli_free_result($res);
?>
	</select></td></tr>
		<tr><td class="tdright">Measurement Type</td><td><select name="mtid">
		<option value="">Select</option>
		<option value="-1">ALL</option>
	<?php 	
	$query = "SELECT mtypeid, mtname FROM measurement_type ORDER BY disp_order, active desc, mtypeid";
	$res = mysqli_query($mysqlid, $query);
	while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC))
	{
		print '<option value="'.$row['mtypeid'].'">'.$row['mtypeid']. " - ". substr($row['mtname'],0,20). "</option>\n";
	}
	mysqli_free_result($res);
?>
	</select></td></tr>
	
	<tr><td class="tdright">Start Date</td><td><input type="text" name="stdate" id="stdate" size="15" class="calendarSelectDate"></td></tr>
	<tr><td class="tdright">End Date</td><td><input type="text" name="enddate" id="enddate" size="15" class="calendarSelectDate"></td></tr>

	</table>
	<input type="submit" name="submit" value="Submit"><input type="button" name="cancel" value="Cancel" onclick="location.href='<?php print $_SERVER["PHP_SELF"]?>'">
	<div id="calendarDiv"></div></form>
<script language="javascript">
function validate(form_sub)
{

	if (form_sub.siteid.selectedIndex<1)
	{
		alert("Please select a monitoring site.");
		return false;
	}
	if (form_sub.mtid.selectedIndex<1)
	{
		alert("Please select a measurement type.");
		return false;
	}
	if (form_sub.stdate.value.length < 7 || form_sub.enddate.value.length < 7)
	{
		alert("Please enter a start and end date.");
		return false;
	}
	if (form_sub.stdate.value > form_sub.enddate.value)
	{
		alert("Please make sure the start date is before the end date.");
		return false;
	}
	return true;
}
</script>
	<?php
	$def_vis="none";


}
?>


<?php
require_once 'includes/qp_footer.php';
?>	