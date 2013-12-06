<?php
//Include the code
require_once 'includes/phplot.php';
require_once 'includes/wqinc.php';
require_once 'includes/plot_base.php';

$siteid=$_GET["siteid"];
$stdate=$_GET["stdate"];
$enddate=$_GET["enddate"];
$mtypeid=$_GET["mtypeid"];
$example_data=array();
$xmin=$stdate?strtotime($stdate):NULL;
$xmax=$enddate?strtotime($enddate):NULL;
$stdate=$stdate?$stdate:"1900";
$enddate=$enddate?$enddate:"3999";

// get site description for title
$squery = "Select site_description, monitor_type from monitoring_sites where siteid=?";
$sstmt = mysqli_prepare($mysqlid, $squery); 
mysqli_stmt_bind_param($sstmt, "s", $siteid);
mysqli_stmt_execute($sstmt);
mysqli_stmt_bind_result($sstmt, $site_desc, $mon_type);
mysqli_stmt_fetch($sstmt);
mysqli_stmt_close($sstmt);

// get measurement name for label
$mtquery = "Select mtname,units,l_multi_depth from measurement_type where mtypeid=?";
$mtstmt = mysqli_prepare($mysqlid, $mtquery); 
mysqli_stmt_bind_param($mtstmt, "s", $mtypeid);
mysqli_stmt_execute($mtstmt);
mysqli_stmt_bind_result($mtstmt, $mtname, $units, $l_multi_depth);
mysqli_stmt_fetch($mtstmt);
mysqli_stmt_close($mtstmt);

if ($mon_type!='L')
{
	$l_multi_depth=0;
}

//get data
$query = "select mtime, value, depth from measurements where siteid=? AND mtypeid=? and mtime > ? and mtime < ? order by mtime, depth";
$stmt = mysqli_prepare($mysqlid, $query); 
mysqli_stmt_bind_param($stmt, "ssss", $siteid, $mtypeid, $stdate, $enddate);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $mtime, $value, $depth);
//$numReturns = mysqli_affected_rows($mysqlid);
while(mysqli_stmt_fetch($stmt))
{
	$lbl=strtotime($mtime);
	if (!$l_multi_depth) {
		$example_data[]=array('',$lbl,$value);
	}
	else 
	{
		if ($depth<2) $v1=$value;
		else 
		{
			$v2=$value;
			$example_data[]=array('',$lbl,$v1,$v2);
		}
	}
}

$numReturns = count($example_data);

mysqli_stmt_close($stmt);

if(sizeof($example_data) < 1){
	print "No data found for these parameters, please try again.";
	print "$siteid, $mtypeid, $stdate, $enddate";
	exit;
}
if ($numReturns > 0) {

  //print_r($example_data);
  //Define the object
  $plot = new PHPlot($default_width,$default_height);
  $plot->SetPlotAreaWorld($xmin, NULL, $xmax, NULL);
  $plot->SetYTickLabelPos('both');
  //$plot->SetMarginsPixels(NULL, 25);
  //Define some data
  $plot->SetDataColors(array('blue','DarkGreen'));

  $plot->SetPlotType('linepoints');
  $plot->SetDataType('data-data');

  $plot->SetDataValues($example_data);
  $plot->SetDefaultTTFont($default_font);
  //Set titles
  if (strlen($site_desc)>15) $site_desc=preg_replace("/\\(/","\n(",$site_desc);
  $plot->SetTitle("Monitoring data from $site_desc");
  $plot->SetXTitle('Date');
  $plot->SetYTitle("$mtname ($units)");


  //Turn off X axis ticks and labels because they get in the way:
  $plot->SetXLabelType('time','%Y-%m-%d');
  $plot->SetXDataLabelPos('none');
  $plot->SetNumXTicks(5);

  //Draw it
  $plot->DrawGraph();
}
else {
  printf("<h1>No data selected for the plot!</h1>");
};
