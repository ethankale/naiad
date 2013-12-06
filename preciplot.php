<?php
//Include the code
require_once 'includes/phplot.php';
require_once 'includes/wqinc.php';
require_once 'includes/plot_base.php';

$sites=$_GET["siteid"];//"CMH03";
$stdate=$_GET["stdate"];//"2007-02-01 00:00:00";
$enddate=$_GET["enddate"];//"2010-10-01 00:00:00";
$mtypeid=$_GET["mtypeid"];//"FLOW";
$example_data=array();
$xmin=$stdate?strtotime($stdate):NULL;
$xmax=$enddate?strtotime($enddate):NULL;
$stdate=$stdate?$stdate:"1900";
$enddate=$enddate?$enddate:"3999";

$cumulative=true;


// get site description for title
$squery = "Select Station_Name from precipitation_stations where StationID=?";
$sstmt = mysqli_prepare($mysqlid, $squery); 
mysqli_stmt_bind_param($sstmt, "s", $sid);
$site_names=array();
for ($i=0; $i < sizeof($sites); $i++) 
{
	$sid = $sites[$i];
	mysqli_stmt_execute($sstmt);
	mysqli_stmt_bind_result($sstmt, $site_desc);
	mysqli_stmt_fetch($sstmt);
	$site_names[]=$site_desc;
}
mysqli_stmt_close($sstmt);

//get data
$date_format = "%Y-%m-%d %H:%i"; //ungrouped
//$date_format = "%Y-%m-%d %H:00"; //Hourly
//$date_format = "%Y-%m-%d"; //Daily
//$date_format = "%Y-%m-01"; //Monthly
$query = "select date_format(pmdate,'$date_format') AS `day`,sum(inches) AS precip FROM precipitation_measurements 
	WHERE PS_ID=? and date_format(pmdate,'$date_format')>=? and date_format(pmdate,'$date_format')<=?  
	group by date_format(pmdate,'$date_format') order by day";
$stmt = mysqli_prepare($mysqlid, $query); 
mysqli_stmt_bind_param($stmt, "sss", $sid, $stdate, $enddate);

for ($i=0; $i < sizeof($sites); $i++) 
{
	$sid = $sites[$i];
	mysqli_stmt_execute($stmt);
	mysqli_stmt_bind_result($stmt, $mtime, $value);
	$v=0;
	while(mysqli_stmt_fetch($stmt))
	{
		$lbl=strtotime($mtime);
		if ($cumulative) {$v+=$value;}
		else {$v=$value;}
		$ar = array('',$lbl,'','','','');
		$ar[2+$i] = $v;
		$example_data[]=$ar;

	}
}
mysqli_stmt_close($stmt);

//print $query;
//print_r($example_data);
//Define the object
$plot = new PHPlot($default_width,$default_height);
$plot->SetPlotAreaWorld($xmin, NULL, $xmax, NULL);
//Define some data
$plot->SetDataColors(array('blue','DarkGreen','red','purple','orange'));

$plot->SetPlotType('lines');
$plot->SetDataType('data-data');
$plot->SetDefaultTTFont($default_font);

$plot->SetDataValues($example_data);

//Set titles
if (strlen($site_desc)>15) $site_desc=preg_replace("/\\(/","\n(",$site_desc);
$plot->SetTitle("Monitoring data from $site_names[0]");
$plot->SetXTitle('Date');
$plot->SetYTitle("Precipitation (inches)");


//Turn off X axis ticks and labels because they get in the way:
$plot->SetXLabelType('time','%m-%d %H:%M');
$plot->SetXDataLabelPos('none');
$plot->SetNumXTicks(6);
if (sizeof($sites)>1){
	$plot->SetTitle("Monitoring data");
	$plot->SetLegend($site_names);
	$plot->SetLegendPixels(400, 0);
}
//Draw it
$plot->DrawGraph();

//print_r($example_data);