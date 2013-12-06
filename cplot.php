<?php
//Include the code
require_once 'includes/phplot.php';
require_once 'includes/wqinc.php';
require_once 'includes/plot_base.php';

$sites=$_GET["siteid"];
$stdate=$_GET["stdate"];
$enddate=$_GET["enddate"];
$mtypeid=$_GET["c_mtypeid1"];
$mtypeid2=$_GET["c_mtypeid2"];
$siteid=$sites[0];

$example_data=array();
//$xmin=$stdate?strtotime($stdate):NULL;
//$xmax=$enddate?strtotime($enddate):NULL;
$stdate=$stdate?$stdate:"1900";
$enddate=$enddate?$enddate:"3999";

// get measurement name for label
$mtquery = "Select mtname,units,l_multi_depth from measurement_type where mtypeid=?";
$mtstmt = mysqli_prepare($mysqlid, $mtquery); 
mysqli_stmt_bind_param($mtstmt, "s", $mtypeid);
mysqli_stmt_execute($mtstmt);
mysqli_stmt_bind_result($mtstmt, $mtname, $units, $l_multi_depth);
mysqli_stmt_fetch($mtstmt);
mysqli_stmt_close($mtstmt);

// get measurement name for label 2
$mtquery = "Select mtname,units,l_multi_depth from measurement_type where mtypeid=?";
$mtstmt = mysqli_prepare($mysqlid, $mtquery); 
mysqli_stmt_bind_param($mtstmt, "s", $mtypeid2);
mysqli_stmt_execute($mtstmt);
mysqli_stmt_bind_result($mtstmt, $mtname2, $units2, $l_multi_depth2);
mysqli_stmt_fetch($mtstmt);
mysqli_stmt_close($mtstmt);

// get site description for title
$squery = "Select site_description from monitoring_sites where siteid=?";
$sstmt = mysqli_prepare($mysqlid, $squery); 
mysqli_stmt_bind_param($sstmt, "s", $siteid);
mysqli_stmt_execute($sstmt);
mysqli_stmt_bind_result($sstmt, $site_desc);
mysqli_stmt_fetch($sstmt);
mysqli_stmt_close($sstmt);

//get data
$query = "select mtime, value, depth, mtypeid from measurements where siteid=? AND (mtypeid=? OR mtypeid=?)and mtime > ? and mtime < ? order by mtime, depth";
$stmt = mysqli_prepare($mysqlid, $query); 
mysqli_stmt_bind_param($stmt, "sssss", $siteid, $mtypeid, $mtypeid2, $stdate, $enddate);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $mtime, $value, $depth, $mtid);
while(mysqli_stmt_fetch($stmt))
{
	$lbl=strtotime($mtime);
	if ($lbl == $oldlbl)
	{
		if ($mtid==$mtypeid) {$day_ar[1]=$value;}
		if ($mtid==$mtypeid2) {$day_ar[2]=$value;}		
	}
	else 
	{
		if (!(is_null($day_ar[1]) || is_null($day_ar[2]))) {$example_data[]=$day_ar;}
		$oldlbl=$lbl;
		$day_ar=array('',NULL,NULL);
		if ($mtid==$mtypeid) {$day_ar[1]=$value;}
		if ($mtid==$mtypeid2) {$day_ar[2]=$value;}		
	}
}
$example_data[]=$day_ar;
array_shift($example_data);
mysqli_stmt_close($stmt);


//print_r($example_data);
//Define the object
$plot = new PHPlot($default_width,$default_height);
//Define some data
$plot->SetDataColors(array('blue','DarkGreen'));

$plot->SetPlotType('points');
$plot->SetDataType('data-data');
$plot->SetDefaultTTFont($default_font);

$plot->SetDataValues($example_data);
//Set titles
if (strlen($site_desc)>15) $site_desc=preg_replace("/\\(/","\n(",$site_desc);
$plot->SetTitle("Monitoring data from $site_desc");
$plot->SetXTitle("$mtname ($units)");
$plot->SetYTitle("$mtname2 ($units2)");
$plot->SetPrecisionX(2);

//Turn off X axis ticks and labels because they get in the way:


//Draw it
$plot->DrawGraph();
