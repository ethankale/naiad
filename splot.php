<?php
//Include the code
require_once 'includes/phplot.php';
require_once 'includes/wqinc.php';
require_once 'includes/plot_base.php';

$sites=$_GET["siteid"];//"CMH03";
$stdate=$_GET["stdate"];//"2007-02-01 00:00:00";
$enddate=$_GET["enddate"];//"2010-10-01 00:00:00";
$mtypeid=$_GET["s_mtypeid"];//"FLOW";
$example_data=array();
$xmin=$stdate?strtotime($stdate):NULL;
$xmax=$enddate?strtotime($enddate):NULL;
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

// get site description for title
$squery = "Select site_description,monitor_type from monitoring_sites where siteid=?";
$sstmt = mysqli_prepare($mysqlid, $squery); 
mysqli_stmt_bind_param($sstmt, "s", $sid);
$site_names=array();
for ($i=0; $i < sizeof($sites); $i++) 
{
	$sid = $sites[$i];
	mysqli_stmt_execute($sstmt);
	mysqli_stmt_bind_result($sstmt, $site_desc, $monitor_type);
	mysqli_stmt_fetch($sstmt);
	$site_names[]=$site_desc;
}
mysqli_stmt_close($sstmt);
if ($monitor_type=='S')
{
	$l_multi_depth=0;
}

//get data
$query = "select mtime, value, depth from measurements where siteid=? AND mtypeid=? and mtime > ? and mtime < ? order by mtime, depth";
$stmt = mysqli_prepare($mysqlid, $query); 
mysqli_stmt_bind_param($stmt, "ssss", $sid, $mtypeid, $stdate, $enddate);
for ($i=0; $i < sizeof($sites); $i++) 
{
	$sid = $sites[$i];
	mysqli_stmt_execute($stmt);
	mysqli_stmt_bind_result($stmt, $mtime, $value, $depth);
	while(mysqli_stmt_fetch($stmt))
	{
		$lbl=strtotime($mtime);
		if (!$l_multi_depth) {
			$ar = array('',$lbl,'','','');
			$ar[2+$i] = $value;
			$example_data[]=$ar;
		}
		else 
		{
			if ($depth==0) $v1=$value;
			else 
			{
				$v2=$value;
				$example_data[]=array('',$lbl,$v1,$v2);
			}
		}
	}
}
mysqli_stmt_close($stmt);


//print_r($example_data);
//Define the object
$plot = new PHPlot($default_width,$default_height);
$plot->SetPlotAreaWorld($xmin, NULL, $xmax, NULL);
//Define some data
$plot->SetDataColors(array('blue','DarkGreen','red'));

$plot->SetPlotType('linepoints');
$plot->SetDataType('data-data');
$plot->SetDefaultTTFont($default_font);

$plot->SetDataValues($example_data);

//Set titles
if (strlen($site_desc)>15) $site_desc=preg_replace("/\\(/","\n(",$site_desc);
$plot->SetTitle("Monitoring data from $site_desc");
$plot->SetXTitle('Date');
$plot->SetYTitle("$mtname ($units)");


//Turn off X axis ticks and labels because they get in the way:
$plot->SetXLabelType('time','%Y-%m-%d');
$plot->SetXDataLabelPos('none');
$plot->SetNumXTicks(5);

if (sizeof($sites)>1){
	$plot->SetTitle("Monitoring data");
	$plot->SetLegend($site_names);
	$plot->SetLegendPixels(400, 0);
}
//Draw it
$plot->DrawGraph();
