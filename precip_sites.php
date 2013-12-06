<?php
$page_title='Precipitation Monitoring Stations';
require_once 'includes/wqinc.php';
$pagelevel = PAGE_ADMIN;

require_once 'includes/qp_header.php';

login_check($pagelevel, $log_user);

if ($_POST['submit']=="Submit" && ($_POST['action']=="new" || $_POST['action']=="edit"))
{
	$throw_error=false;
	if($_POST['action']=="new") 
	{
		$action_result="added";
		$query = "INSERT INTO precipitation_stations (StationID , Station_Name , Description , Latitude , Longitude) 
			VALUES (?, ?, ?, ?, ?)";
		$stmt = mysqli_prepare($mysqlid, $query);
		mysqli_stmt_bind_param($stmt, 'sssdd', $StationID , $Station_Name , $Description , $Latitude , $Longitude);	
	}
	if($_POST['action']=="edit") 
	{
		$action_result="updated";
		$query = "UPDATE precipitation_stations SET StationID=?,Station_Name=?,Description=?,Latitude=?,Longitude=? WHERE StationID=?";
		$stmt = mysqli_prepare($mysqlid, $query);
		mysqli_stmt_bind_param($stmt, 'sssdds', $StationID , $Station_Name , $Description , $Latitude , $Longitude, $oldsiteid);
		$oldsiteid=$_POST['oldsiteid'];
	}
	$StationID=$_POST['StationID'];
	$Station_Name=$_POST['Station_Name'];
	$Description=$_POST['Description'];
	$Latitude=$_POST['Latitude'];
	$Longitude=$_POST['Longitude'];
	mysqli_stmt_execute($stmt);
	if (mysqli_stmt_errno($stmt)){ 
		printf("Error: %d.\n", mysqli_stmt_errno($stmt)); 
		printf("Error: %s.\n", mysqli_stmt_error($stmt));
	}
	else print "<p>Monitoring site $siteid $action_result</p>";
}

if ($_GET['action']=="new" || $_GET['action']=="edit")
{
	$action_descriptor = "New";
	if ($_GET['action']=="edit" && $_GET['StationID'])
	{
		$action_descriptor = "Edit";
		$query = "SELECT * FROM precipitation_stations WHERE StationID=?";
		$stmt = mysqli_prepare($mysqlid, $query); 
		if($stmt==false) {printf("Errormessage: %s\n", mysqli_error($mysqlid));exit;}
		mysqli_stmt_bind_param($stmt, "s", $_GET['StationID']);	
		mysqli_stmt_execute($stmt);
		mysqli_stmt_bind_result($stmt, $StationID,$Station_Name,$Description,$latitude,$longitude);
		mysqli_stmt_fetch($stmt);
		mysqli_stmt_close($stmt);
		
		
	}
	?>
	<form action="precip_sites.php" method="POST">
	<input type="hidden" name="action" value="<?php print $_GET['action'];?>">
	<?php 
		if($_GET['action']=="edit") {print "<input type=\"hidden\" name=\"oldsiteid\" value=\"$StationID\">\n";}
	?>
	<h2><?php print $action_descriptor; ?>  Precipitation Station</h2>
	<table style="width:550px">
	<tr><td class="tdright">MCWD Station ID</td><td><input name="StationID" type="text" style="width:60px" maxlength="6" value="<?php print "$StationID";?>"></td></tr>
	<tr><td class="tdright">Station Name</td><td><input name="Station_Name" type="text" style="width:400px" value="<?php print "$Station_Name";?>"></td></tr>
	<tr><td class="tdright">Description</td><td><input name="Description" type="text" style="width:400px" value="<?php print "$Description";?>"></td></tr>
	<tr><td class="tdright">Latitiude</td><td><input name="Latitude" type="text" style="width:80px" maxlength="10" value="<?php printf("%.5f",$latitude);?>"></td></tr>
	<tr><td class="tdright">Longitude</td><td><input name="Longitude" type="text" style="width:80px" maxlength="10" value="<?php printf("%.5f",$longitude);?>"></td></tr>
	</table>
<?php 
	$allow_delete=false;
/*	if ($_GET['faction']=="edit" && $_GET['siteid'])
	{*/
		$action_descriptor = "Edit";
		$query = "SELECT count(*) as datapoints, DATE_FORMAT(MIN(pmdate),'%c/%e/%Y') as firstpoint, DATE_FORMAT(MAX(pmdate),'%c/%e/%Y') as lastpoint FROM precipitation_measurements WHERE PS_ID=? GROUP BY PS_ID";
		$stmt = mysqli_prepare($mysqlid, $query); 
		if($stmt==false) {printf("Errormessage: %s\n", mysqli_error($mysqlid));exit;}
		mysqli_stmt_bind_param($stmt, "s", $StationID);	
		mysqli_stmt_execute($stmt);
		mysqli_stmt_bind_result($stmt, $datapoints,$firstpoint,$lastpoint);
		mysqli_stmt_fetch($stmt);
		mysqli_stmt_close($stmt);
		if 	($datapoints > 0)
		{
			print "There are precipitation values at this site from $firstpoint to $lastpoint<br>\n";
			$link = "precip_report.php?report_type=raw&stationid=$StationID&in=true&temp=true&wind_speed=true&wind_dir=true&air_press=true&stdate=&enddate=";
			print "<a href=\"$link\">See all data from this site</a><br><br>\n";
			$allow_delete=false;
			
		}
		else 
		{
			print "There are no data values associated with this site.<br><br>\n";
			$allow_delete=true;
		}
		
	//}
?>	
	<input type="submit" name="submit" value="Submit"><input type="button" name="cancel" value="Cancel" onclick="location.href='<?php print $_SERVER["PHP_SELF"]?>'">
	<div id="calendarDiv"></div></form>

	<?php
	$def_vis="none";
}


?>

<table width="500px" class="listtable">
<tr><th colspan=3>Precipitation Station</th></tr>

<tbody><tr><td colspan=3><a href="precip_sites.php?action=new">Precipitation Station</a></td></tr>
<?php 
$mt_tr=array();
$mt_types=array();
$query = "SELECT * FROM precipitation_stations ORDER BY StationID ASC";
$res = mysqli_query($mysqlid, $query);
$group="";
while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC))
{
	print "<tr><td>".$row['StationID']."</td><td>".$row['Station_Name']."</td>
		<td><a href=\"precip_sites.php?action=edit&StationID=".$row['StationID']."\">edit</a></td></tr>";
	
}


mysqli_free_result($res);
?></tbody>
</table>

<?php
require_once 'includes/qp_footer.php';
?>	