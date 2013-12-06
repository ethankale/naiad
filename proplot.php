<?php
//Include the code
require_once 'includes/phplot.php';
require_once 'includes/wqinc.php';
require_once 'includes/plot_base.php';

$sites=$_GET["siteid"];
$stdate=$_GET["stdate"];
$enddate=$_GET["enddate"];
$mtypeid=$_GET["p_mtypeid"];
$siteid=$sites[0];
	if (preg_match ('`^([0-9]{1,2})/([0-9]{1,2})/([0-9]{4})$`', $_GET['stdate'], $parts))
	{
    	//check weather the date is valid of not checkdate($parts[2],$parts[3],$parts[1
    	$_GET['stdate'] = sprintf("%04d-%02d-%02d", $parts[3],$parts[1],$parts[2]);
	}

$example_data=array();
//$xmin=$stdate?strtotime($stdate):NULL;
//$xmax=$enddate?strtotime($enddate):NULL;
$stdate=$stdate?$stdate."00:00:00":"1900";
$enddate=$enddate?$enddate."23:59:59":"3999";

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
$maxdepth = 0;
$oldtime="";
$days=array();
$i=1;
while(mysqli_stmt_fetch($stmt))
{
	$lbl=strtotime($mtime);
	$ar = array('','','','','','','','','','');
	if ($oldtime!=$mtime) {
		$i++;
		$days[]=strftime("%m/%d/%Y",strtotime($mtime));
		$oldtime=$mtime;
	}
	$ar[1]=$depth;
	$ar[$i]=$value;
	$example_data[]=$ar;
	$maxdepth=($depth>$maxdepth)?$depth:$maxdepth;
}
$maxdepth=ceil($maxdepth);
$ticksize = ceil($maxdepth/10);
array_shift($example_data);
mysqli_stmt_close($stmt);
if(sizeof($example_data) < 1){
	print "No data found for these parameters, please try again.";
	exit;
}

//print_r($example_data);
//Define the object
$plot = new PHPlot($default_width,$default_height);
$plot->SetPlotAreaWorld(0, NULL, $maxdepth, NULL);
$plot->SetXTickIncrement($ticksize);
//Define some data
$plot->SetDataColors(array('blue','DarkGreen','red','purple','orange'));

$plot->SetPlotType('linepoints');
$plot->SetDataType('data-data');
$plot->SetDefaultTTFont($default_font);

$plot->SetDataValues($example_data);
//Set titles
if (strlen($site_desc)>15) $site_desc=preg_replace("/\\(/","\n(",$site_desc);
$plot->SetTitle("Monitoring data from $site_desc");
$plot->SetXTitle("Depth (m)");
$plot->SetYTitle("$mtname ($units)");
$plot->SetPrecisionX(2);

//Turn off X axis ticks and labels because they get in the way:
$plot->SetTitle("Monitoring data from $site_desc");
$plot->SetLegend($days);
$plot->SetLegendPixels(501, 0);


//Draw it
$plot->DrawGraph();
