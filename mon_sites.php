<?php
$page_title='Monitoring Stations';
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
		$query = "INSERT INTO monitoring_sites (siteid,latitude,longitude,site_description,monitor_start,monitor_end,monitor_type,project_station_id,storet_station_id,mpca_site_id,project_site_id,waterbody_id) 
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
		$stmt = mysqli_prepare($mysqlid, $query);
		mysqli_stmt_bind_param($stmt, 'sddssssssssi', $siteid,$latitude,$longitude,$site_description,$monitor_start,$monitor_end,$monitor_type,$project_station_id,$storet_station_id,$mpca_site_id,$project_site_id,$waterbody_id);	
	}
	if($_POST['faction']=="edit") 
	{
		$action_result="updated";
		$query = "UPDATE monitoring_sites SET siteid=?,latitude=?,longitude=?,site_description=?,monitor_start=?,monitor_end=?,
			monitor_type=?,project_station_id=?,storet_station_id=?,mpca_site_id=?,project_site_id=?,waterbody_id=? WHERE siteid=?";
		$stmt = mysqli_prepare($mysqlid, $query);
		mysqli_stmt_bind_param($stmt, 'sddssssssssis', $siteid,$latitude,$longitude,$site_description,$monitor_start,$monitor_end,$monitor_type,$project_station_id,$storet_station_id,$mpca_site_id, $project_site_id,$waterbody_id,$oldsiteid);
		$oldsiteid=$_POST['oldsiteid'];
	}
	$siteid=$_POST['siteid'];
	$latitude=$_POST['latitude'];
	$longitude=$_POST['longitude'];
	$site_description=$_POST['site_description'];
	$monitor_start=$_POST['monitor_start'];
	$monitor_end=$_POST['monitor_end'];
	if ($_POST['ongoing']=="true") {$monitor_end=NULL;}
	$monitor_type=$_POST['monitor_type'];
	$project_station_id=$_POST['project_station_id'];
	$storet_station_id=$_POST['storet_station_id'];
	$mpca_site_id=$_POST['mpca_site_id'];
	$project_site_id=$_POST['project_site_id'];
	$waterbody_id=$_POST['waterbody_id'];
	mysqli_stmt_execute($stmt);
	if (mysqli_stmt_errno($stmt)){ 
		printf("Error: %d.\n", mysqli_stmt_errno($stmt)); 
		printf("Error: %s.\n", mysqli_stmt_error($stmt));
	}
	else print "<p>Monitoring site $siteid $action_result</p>";
}
if ($_POST['faction']=="delete" && $_POST['siteid'])
{
	$query = "DELETE FROM monitoring_sites WHERE siteid=?";
	$stmt = mysqli_prepare($mysqlid, $query);
	mysqli_stmt_bind_param($stmt, 's', $_POST['siteid']);
	mysqli_stmt_execute($stmt);	
	if (mysqli_stmt_errno($stmt) == 1451){
		print "<p>".$_POST['siteid']." can not be deleted because it has data associated with it.</p>"; 
	}
	else if (mysqli_stmt_errno($stmt))
	{
		printf("Error: %d.\n", mysqli_stmt_errno($stmt)); 
		printf("Error: %s.\n", mysqli_stmt_error($stmt));
	}
	else print "<p>Monitoring site ".$_POST['siteid']." deleted</p>";
}

if ($_GET['faction']=="new" || $_GET['faction']=="edit")
{
	$action_descriptor = "New";
	if ($_GET['faction']=="edit" && $_GET['siteid'])
	{
		$action_descriptor = "Edit";
		$query = "SELECT  siteid, latitude, longitude, site_description, monitor_start, monitor_end, monitor_type, project_station_id, storet_station_id, mpca_site_id, project_site_id, waterbody_id FROM monitoring_sites WHERE siteid=?";
		$stmt = mysqli_prepare($mysqlid, $query); 
		if($stmt==false) {printf("Errormessage: %s\n", mysqli_error($mysqlid));exit;}
		mysqli_stmt_bind_param($stmt, "s", $_GET['siteid']);	
		mysqli_stmt_execute($stmt);
		mysqli_stmt_bind_result($stmt, $siteid,$latitude,$longitude,$site_description,$monitor_start,$monitor_end,$monitor_type,$project_station_id,$storet_station_id,$mpca_site_id,$project_site_id,$waterbody_id);
		mysqli_stmt_fetch($stmt);
		mysqli_stmt_close($stmt);
		
		
	}
	?>
	<form action="mon_sites.php" name="f1" id="f1" method="POST">
	<input type="hidden" name="faction" value="<?php print $_GET['faction'];?>">
	<?php 
		if($_GET['faction']=="edit") {print "<input type=\"hidden\" name=\"oldsiteid\" value=\"$siteid\">\n";}
	?>
	<h2><?php print $action_descriptor; ?> Monitoring Site</h2>
	<table style="width:550px">
	<tr><td class="tdright">MCWD Site ID</td><td><input name="siteid" type="text" style="width:60px" maxlength="7" value="<?php print "$siteid";?>"></td></tr>
	<tr><td class="tdright">Latitiude</td><td><input name="latitude" type="text" style="width:80px" maxlength="10" value="<?php printf("%.5f",$latitude);?>"></td></tr>
	<tr><td class="tdright">Longitude</td><td><input name="longitude" type="text" style="width:80px" maxlength="10" value="<?php printf("%.5f",$longitude);?>"></td></tr>
	<tr><td class="tdright">Site Description</td><td><input name="site_description" type="text" style="width:400px" value="<?php print "$site_description";?>"></td></tr>
	<tr><td class="tdright">Monitoring Start</td><td><input name="monitor_start" type="text" style="width:100px" maxlength="17" value="<?php print "$monitor_start";?>" class="calendarSelectDate"></td></tr>
	<tr><td class="tdright">Monitoring End</td><td><input name="monitor_end" type="text" style="width:100px" maxlength="17" value="<?php print "$monitor_end";?>" class="calendarSelectDate"><br>
				<input type="checkbox" name="ongoing" value="true" <?php if ($monitor_end===NULL)print " checked"?>> Ongoing </td></tr>
	<tr><td class="tdright">Monitor Type</td><td><input name="monitor_type" type="radio" value="L" <?php print ($monitor_type=="L"?"checked":"");?>> Lake  &nbsp; 
		<input name="monitor_type" type="radio" value="S" <?php print ($monitor_type=="S"?"checked":"");?>> Stream  &nbsp; 
		<input name="monitor_type" type="radio" value="P" <?php print ($monitor_type=="P"?"checked":"");?>> Precip</td></tr>
	<tr><td class="tdright">Project Station ID</td><td><input name="project_station_id" type="text" style="width:250px" value="<?php print "$project_station_id";?>"></td></tr>
	<tr><td class="tdright">EQUiS Location ID</td><td><input name="storet_station_id" type="text" style="width:150px" value="<?php print "$storet_station_id";?>"></td></tr>
	<tr><td class="tdright">MPCA Site ID</td><td><input name="mpca_site_id" type="text" style="width:80px" maxlength="10" value="<?php print "$mpca_site_id";?>"></td></tr>
	<tr><td class="tdright">Project Site ID</td><td><input name="project_site_id" type="text" style="width:150px" value="<?php print "$project_site_id";?>"></td></tr>
	<tr><td class="tdright">Waterbody</td><td><select name="waterbody_id">
<?php 
$query = "SELECT * FROM waterbodies ORDER BY wbody_name ASC";
$res = mysqli_query($mysqlid, $query);
while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC))
{
	print '<option value="'.$row['waterbody_id'].'"'.($row['waterbody_id']==$waterbody_id?" selected":"") .'>'.$row['wbody_name']. "</option>\n";
}


mysqli_free_result($res);
?>
</select></td></tr>	
	</table>
<?php 
	$allow_delete=false;
	if ($_GET['faction']=="edit" && $_GET['siteid'])
	{
		$action_descriptor = "Edit";
		$query = "SELECT count(*) as datapoints, DATE_FORMAT(MIN(mtime),'%c/%e/%Y') as firstpoint, DATE_FORMAT(MAX(mtime),'%c/%e/%Y') as lastpoint FROM measurements WHERE siteid=? GROUP BY siteid";
		$stmt = mysqli_prepare($mysqlid, $query); 
		if($stmt==false) {printf("Errormessage: %s\n", mysqli_error($mysqlid));exit;}
		mysqli_stmt_bind_param($stmt, "s", $_GET['siteid']);	
		mysqli_stmt_execute($stmt);
		mysqli_stmt_bind_result($stmt, $datapoints,$firstpoint,$lastpoint);
		mysqli_stmt_fetch($stmt);
		mysqli_stmt_close($stmt);
		if 	($datapoints > 0)
		{
			print "There are $datapoints data values at this site from $firstpoint to $lastpoint<br>\n";
/*			if ($wbody_type=="L") {
				$check_col="lake";
			}
			elseif ($wbody_type=="S") {
				$check_col="stream";
			}*/
			list($m,$d,$y)=explode("/",$firstpoint);
			$sdate=$y."-01-01";
			list($m,$d,$y)=explode("/",$lastpoint);
			$edate=$y."-12-31";
			$link = "measurements_report.php?storet_output=true&wbody_type=$monitor_type"."&waterbodyid=$waterbody_id"."&siteid[]=$siteid"."&stdate=$sdate"."&enddate=$edate";
			print "<a href=\"$link\">See all data from this site</a><br><br>\n";
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
	if (confirm('Are you sure you want to delete this site?')) 
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
<tr><th colspan=3>Monitoring Sites</th></tr>

<tbody><tr><td colspan=3><a href="mon_sites.php?faction=new">New Monitoring Site</a></td></tr>
<?php 
$mt_tr=array();
$mt_types=array();
$query = "SELECT * FROM monitoring_sites ORDER BY monitor_type ASC, siteid ASC";
$res = mysqli_query($mysqlid, $query);
$group="";
while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC))
{
	if ($group != $row["monitor_type"]) {
		$group=$row["monitor_type"];
		if ($row["monitor_type"]=="S") $hstring="Streams";
		if ($row["monitor_type"]=="L") $hstring="Lakes";
		print "</tbody>\n";
		print "<tr><td></td><td colspan=2><br><b>$hstring</b> &nbsp;<a href='#' onclick=\"document.getElementById('table_".$row["monitor_type"]."').style.display='';\";>Show</a>\n";
		print "\t<a href='#' onclick=\"document.getElementById('table_".$row["monitor_type"]."').style.display='none';\";>Hide</a></td></tr>";
		print "<tbody id=\"table_".$row["monitor_type"]."\" style=\"display:$def_vis\">\n";
	}
	print "<tr><td>".$row['siteid']."</td><td>".$row['site_description']."</td>
		<td><a href=\"mon_sites.php?faction=edit&siteid=".$row['siteid']."\">edit</a></td></tr>";
	
}


mysqli_free_result($res);
?></tbody>
</table>

<?php
require_once 'includes/qp_footer.php';
?>	