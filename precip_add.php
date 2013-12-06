<?php
$page_title='Precipitation Data Entry';
require_once 'includes/wqinc.php';
$pagelevel = PAGE_USER;

require_once 'includes/qp_header.php';

login_check($pagelevel, $log_user);

if ($_POST['submit']=="Submit" && ($_POST['action']=="new" || $_POST['action']=="edit"))
{
	$throw_error=false;
	$stime=$_POST['shr'].":".$_POST['smin'];
	$sdate=$_POST['sdate'];
	$pmdate=$sdate." ".$stime;
	$inches=strlen($_POST['inches'])?$_POST['inches']:NULL;
	$air_temp_f=strlen($_POST['air_temp_f'])?$_POST['inches']:NULL;
	$wind_speed_mph=strlen($_POST['wind_speed_mph'])?$_POST['inches']:NULL;
	$wind_dir=strlen($_POST['wind_dir'])?$_POST['inches']:NULL;
	$pressure_mmhg=strlen($_POST['pressure_mmhg'])?$_POST['inches']:NULL;
	$ps_id=$_POST['ps_id'];	
	
	if($_POST['action']=="new") 
	{
		$query_check = "SELECT pmdate from precipitation_measurements WHERE pmdate=? AND PS_ID=?";
		$checkstmt = mysqli_prepare($mysqlid,$query_check);
		mysqli_stmt_bind_param($checkstmt, 'ss', $pmdate, $ps_id);
		mysqli_stmt_execute($checkstmt);
		if (mysqli_stmt_fetch($checkstmt)) {
			print "Data already exists for $ps_id at $pmdate.";
			exit;
		}
		
		$action_result="added";
		$query = "INSERT INTO precipitation_measurements (pmdate, inches, air_temp_f, wind_speed_mph, wind_dir, pressure_mmhg, PS_ID) 
			VALUES (?, ?, ?, ?, ?, ?, ?)";
		$stmt = mysqli_prepare($mysqlid, $query);
		mysqli_stmt_bind_param($stmt, 'sddddds', $pmdate, $inches, $air_temp_f, $wind_speed_mph, $wind_dir, $pressure_mmhg, $ps_id);	
	}
	if($_POST['action']=="edit") 
	{
		$action_result="updated";
		$query = "UPDATE precipitation_measurements SET pmdate=?, inches=?, air_temp_f=?, wind_speed_mph=?, wind_dir=?, pressure_mmhg=?, PS_ID=? WHERE PM_ID=?";
		$stmt = mysqli_prepare($mysqlid, $query);
		mysqli_stmt_bind_param($stmt, 'sdddddsi', $pmdate, $inches, $air_temp_f, $wind_speed_mph, $wind_dir, $pressure_mmhg, $ps_id, $pm_id);
		$pm_id=$_POST['pm_id'];
	}

	
	

	mysqli_stmt_execute($stmt);
	if (mysqli_stmt_errno($stmt)){ 
		printf("Error: %s.\n", mysqli_stmt_error($stmt));
	}
	else {
		print "<p>Precipitation Measurement $action_result</p>";
		$inches="";
		$air_temp_f="";
		$wind_speed_mph="";
		$wind_dir="";
		$pressure_mmhg="";
		if ($_POST['action']=="new")
		{
			list ($shr, $smin, $ssec) = explode(":",$stime);
			$smin=$smin+15;
			if ($smin>59) {
				$smin-=60;
				$shr++;
			}
			if ($shr>23)
			{
				$shr-=24;
				$sdate=strftime("%Y-%m-%d",(strtotime($sdate)+86400)); //add 86400sec (1 day) to timestamp and get new date) 
			}
			$stime=sprintf("%02d:%02d",$shr,$smin);
		}
		else {
			$stime="";
			$sdate="";
		}
	}
}

if ($_GET['action']=="new" || $_GET['action']=="edit" || !$_GET['action'])
{
	if ($_GET['action']=="edit" && $_GET['pm_id'])
	{
		$query = "SELECT PM_ID, pmdate, inches, air_temp_f, wind_speed_mph, wind_dir, pressure_mmhg, PS_ID FROM precipitation_measurements WHERE PM_ID=?";
		$stmt = mysqli_prepare($mysqlid, $query); 
		if($stmt==false) {printf("Errormessage: %s\n", mysqli_error($mysqlid));exit;}
		mysqli_stmt_bind_param($stmt, "s", $_GET['pm_id']);	
		mysqli_stmt_execute($stmt);
		mysqli_stmt_bind_result($stmt, $pm_id, $pmdate, $inches, $air_temp_f, $wind_speed_mph, $wind_dir, $pressure_mmhg, $ps_id);
		mysqli_stmt_fetch($stmt);
		mysqli_stmt_close($stmt);
		if ($inches) $inches=sprintf("%.2f",$inches);
		if ($air_temp_f) $air_temp_f=sprintf("%.2f",$air_temp_f);
		if ($wind_speed_mph) $wind_speed_mph=sprintf("%.2f",$wind_speed_mph);
		list ($sdate, $stime)= explode(" ",$pmdate);
		list ($shr,$smin,$ssec)= explode(":",$stime);
		
	}
	?>
	<form action="precip_add.php" method="POST">
	<input type="hidden" name="action" value="<?php print $_GET['action']?$_GET['action']:"new";?>">
	<?php 
		if($_GET['action']=="edit") {print "<input type=\"hidden\" name=\"pm_id\" value=\"$pm_id\">\n";}
	?>
	<h2>New Monitoring Site</h2>
	<table style="width:650px" class="entrytable">
	<tr><td class="tdright">Precip Station</td><td><select name="ps_id"><option value="">Select Station</option>
<?php 
$query = "SELECT StationID, Station_Name FROM precipitation_stations ORDER BY Station_Name ASC";
$res = mysqli_query($mysqlid, $query);
while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC))
{
	print '<option value="'.$row['StationID'].'"'.($row['StationID']==$ps_id?" selected":"") .'>'.$row['Station_Name']. "</option>\n";
}


mysqli_free_result($res);
?>
</select></td></tr>		<tr><td class="tdright">Measurement Date</td><td><input type="text" name="sdate" size="15" class="calendarSelectDate" value="<?php print $sdate;?>"></td></tr>
	<tr><td class="tdright">Measurement Time (military)</td><td>
	<select name="shr" style="width:40px;"><?php for ($i=0;$i<24;$i++){ print "<option".($shr==$i?" selected":"").">".sprintf("%02d",$i)."</option>\n"; }?></select>
	<select name="smin" style="width:40px;"><?php for ($i=0;$i<60;$i+=15) { print "<option".($smin==$i?" selected":"").">".sprintf("%02d",$i)."</option>\n";}?></select>
	</td></tr>
	<tr><td class="tdright">Precipitation (in)</td><td><input name="inches" type="text" style="width:40px" value="<?php print "$inches";?>"></td></tr>
	<tr><td class="tdright">Temperature (F)</td><td><input name="air_temp_f" type="text" style="width:40px" value="<?php print "$air_temp_f";?>"></td></tr>
	<tr><td class="tdright">Wind Speed (mph)</td><td><input name="wind_speed_mph" type="text" style="width:40px" value="<?php print "$wind_speed_mph";?>"></td></tr>
	<tr><td class="tdright">Wind Direction (dep)</td><td><input name="wind_dir" type="text" style="width:40px" value="<?php print "$wind_dir";?>"></td></tr>
	<tr><td class="tdright">Pressure (mm Hg)</td><td><input name="pressure_mmhg" type="text" style="width:40px" value="<?php print "$pressure_mmhg";?>"></td></tr>

	</table>
	<input type="submit" name="submit" value="Submit"><input type="button" name="cancel" value="Cancel" onclick="location.href='<?php print $_SERVER["PHP_SELF"]?>'">
	<div id="calendarDiv"></div></form>

	<?php
	$def_vis="none";
}


?>

<?php
require_once 'includes/qp_footer.php';
?>	