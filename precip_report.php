<?php

require_once 'includes/wqinc.php';
$pagelevel = PAGE_USER;

require_once 'includes/outputformat.php';
if ($_GET['downloadcsv']=="true") {
	$output_type=OF_CSV;
	$page_title="precip".$_GET["stationid"].".csv";
}
else {
	$page_title='Precipitation Data Request Form';
	$output_type=OF_TBL;
}
output_start($output_type, $page_title, "includes/qp_header.php");

$relink_csv='<a href="'.$_SERVER["REQUEST_URI"].'&downloadcsv=true">Download as .csv</a><br>';
output_line($output_type, $relink_csv,false);

$elink="precip_add.php?action=edit&";
$outputfields = "";
$headers=array();
$h=array();
if (key_exists("in", $_GET) && $_GET["in"]=="true") {$outputfields .= "inches, ";$h[0]=true; }
if (key_exists("temp", $_GET) && $_GET["temp"]=="true") {$outputfields .= "air_temp_f, ";$h[1]=true;}
if (key_exists("wind_speed", $_GET) && $_GET["wind_speed"]=="true") {$outputfields .= "wind_speed_mph, ";$h[2]=true;}
if (key_exists("wind_dir", $_GET) && $_GET["wind_dir"]=="true") {$outputfields .= "wind_dir, ";$h[3]=true;}
if (key_exists("air_press", $_GET) && $_GET["air_press"]=="true") {$outputfields .= "pressure_mmhg, ";$h[4]=true;}

$PS_ID = $_GET["stationid"];

if ($_GET["report_type"] =="raw") {
	if($_GET["stdate"]) {$stdate= $_GET["stdate"]." 00:00:00";}
	else {$stdate = "1980-01-01";}

	if($_GET["enddate"]) {$enddate= $_GET["enddate"]." 23:59:59";}
	else {$enddate = "2020-12-31";}
	
	$headers[]="date";
	if ($h[0]) $headers[]="inches";
	if ($h[1]) $headers[]="air_temp_f";
	if ($h[2]) $headers[]="wind_speed_mph";
	if ($h[3]) $headers[]="wind_dir";
	if ($h[4]) $headers[]="pressure_mmhg";
	output_header($output_type, $headers);

	$query = "SELECT inches, air_temp_f, wind_speed_mph, wind_dir, pressure_mmhg, pmdate, pm_id from precipitation_measurements WHERE PS_ID=? and pmdate>=? and pmdate<=? order by pmdate";
	$stmt = mysqli_prepare($mysqlid, $query); 
	if($stmt==false) {printf("Errormessage: %s\n", mysqli_error($mysqlid));exit;}
	mysqli_stmt_bind_param($stmt, "sss", $PS_ID,$stdate,$enddate);	
	mysqli_stmt_execute($stmt);
	mysqli_stmt_bind_result($stmt, $inches, $air_temp_f, $wind_speed_mph, $wind_dir, $pressure_mmhg, $pmdate, $pm_id);
	while (mysqli_stmt_fetch($stmt))
	{
		$data=array();
		$data_type="";
		$data[]=$pmdate; $data_type.=DATA_DAYTIME;
		if ($h[0]) {$data[]=$inches; $data_type.=DATA_FLOAT2;}
		if ($h[1]) {$data[]=$air_temp_f; $data_type.=DATA_FLOAT2;}
		if ($h[2]) {$data[]=$wind_speed_mph; $data_type.=DATA_FLOAT2;}
		if ($h[3]) {$data[]=$wind_dir; $data_type.=DATA_FLOAT2;}
		if ($h[4]) {$data[]=$pressure_mmhg; $data_type.=DATA_FLOAT2;}
		$data["edit_link"] = $elink."pm_id=$pm_id";
		output_row($output_type, $data, $data_type);
	}
	
}

if ($_GET["report_type"] =="daily" || $_GET["report_type"] =="monthly") {
	if ($_GET["report_type"] =="daily") {
		$date_format = "%Y-%m-%d";
		if($_GET["stdate"]) {$stdate= $_GET["stdate"];}
		else {$stdate = "1980-01-01";}
	
		if($_GET["enddate"]) {$enddate= $_GET["enddate"];}
		else {$enddate = "2020-12-31";}	
//		$view = "precip_daily_view";
	}
	if ($_GET["report_type"] =="monthly") {
		$date_format = "%Y-%m";
		if($_GET["stdate"]) {$stdate= $_GET["stdate"];}
		else {$stdate = "1980-01";}
	
		if($_GET["enddate"]) {$enddate= $_GET["enddate"];}
		else {$enddate = "2020-12";}	
//		$view = "precip_monthly_view";
	}
	
	
	$headers[]="date";
	if ($h[0]) $headers[]="inches";
	if ($h[1]) array_push($headers, "air_temp_f (max)","air_temp_f (avg)","air_temp_f (min)");
	if ($h[2]) array_push($headers, "wind_speed_mph (max)","wind_speed_mph (avg)","wind_speed_mph (min)");
	if ($h[3]) $headers[]="wind_dir (avg)";
	if ($h[4]) array_push($headers, "pressure_mmhg (max)","pressure_mmhg (avg)","pressure_mmhg (min)");
	output_header($output_type, $headers);
	
	$select_block = " date_format(pmdate,'$date_format') AS `day`,sum(inches) AS precip,max(air_temp_f) AS max_T,min(air_temp_f) AS min_T,avg(air_temp_f) AS avg_T,
		max(wind_speed_mph) AS max_wind,min(wind_speed_mph) AS min_wind,avg(wind_speed_mph) AS avg_wind,avg(wind_dir) AS avg_wind_dir,
		max(pressure_mmhg) AS max_press,min(pressure_mmhg) AS min_press,avg(pressure_mmhg) AS avg_press";
	
	$query = "SELECT $select_block from precipitation_measurements WHERE PS_ID=? and date_format(pmdate,'$date_format')>=? and date_format(pmdate,'$date_format')<=? group by date_format(pmdate,'$date_format')order by day";
	$stmt = mysqli_prepare($mysqlid, $query); 
	if($stmt==false) {printf("Errormessage: %s\n", mysqli_error($mysqlid));exit;}
	mysqli_stmt_bind_param($stmt, "sss", $PS_ID,$stdate,$enddate);	
	mysqli_stmt_execute($stmt);
	$row=array();
	mysqli_stmt_bind_result($stmt, $day, $precip, $max_T, $min_T, $avg_T, $max_wind, $min_wind, $avg_wind, $avg_wind_dir, $max_press, $min_press,$avg_press);

	while (mysqli_stmt_fetch($stmt))
	{
		$data=array();
		$data_type="";
		$data[]=$day; $data_type.=DATA_DAYTIME;
		
		if ($h[0]) {$data[]=$precip; $data_type.=DATA_FLOAT2;}
		if ($h[1]) {array_push($data, $max_T,$avg_T,$min_T); $data_type.=DATA_FLOAT2.DATA_FLOAT2.DATA_FLOAT2;}
		if ($h[2]) {array_push($data, $max_wind,$avg_wind,$min_wind); $data_type.=DATA_FLOAT2.DATA_FLOAT2.DATA_FLOAT2;}
		if ($h[3]) {$data[]=$avg_wind_dir; $data_type.=DATA_FLOAT2;}
		if ($h[4]) {array_push($data, $max_press,$avg_press,$min_press); $data_type.=DATA_FLOAT2.DATA_FLOAT2.DATA_FLOAT2;}
		output_row($output_type, $data, $data_type);
	}
}
output_footer($output_type);
output_end($output_type, 'includes/qp_footer.php');

?>	