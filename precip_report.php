<?php

require_once 'includes/wqinc.php';
$pagelevel = PAGE_USER;

require_once 'includes/outputformat.php';

$downloadcsvGet = isset($_GET['downloadcsv']) ? $_GET['downloadcsv'] : null;
$report_typeGet = isset($_GET["report_type"]) ? $_GET["report_type"] : null;
$stationidGet   = isset($_GET["stationid"])   ? $_GET["stationid"] : null;
$inGet          = isset($_GET["in"])          ? $_GET["in"] : null;
$tempGet        = isset($_GET["temp"])        ? $_GET["temp"] : null;
$wind_speedGet  = isset($_GET["wind_speed"])  ? $_GET["wind_speed"] : null;
$wind_dirGet    = isset($_GET["wind_dir"])    ? $_GET["wind_dir"] : null;
$air_pressGet   = isset($_GET["air_press"])   ? $_GET["air_press"] : null;


if ($downloadcsvGet=="true") {
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

$elink          ="precip_add.php?action=edit&";
$outputfields   = "";
$headers        = array();
$h              = array();

if ($inGet=="true") {
    $outputfields .= "inches, ";
};
if ($tempGet=="true") {
    $outputfields .= "air_temp_f, ";
};
if ($wind_speedGet=="true") {
    $outputfields .= "wind_speed_mph, ";
};
if ($wind_dirGet=="true") {
    $outputfields .= "wind_dir, ";
};
if ($air_pressGet=="true") {
    $outputfields .= "pressure_mmhg, ";
};

$PS_ID = $stationidGet;

if ($report_typeGet =="raw") {
    
    $stdate  = isset($_GET["stdate"])  ? $_GET["stdate"] . " 00:00:00"  : "1980-01-01";
    $enddate = isset($_GET["enddate"]) ? $_GET["enddate"] . " 23:59:59" : "2020-12-31";
    
    $headers[]="date";
    if ($inGet=="true")         $headers[]="inches";
    if ($tempGet=="true")       $headers[]="air_temp_f";
    if ($wind_speedGet=="true") $headers[]="wind_speed_mph";
    if ($wind_dirGet=="true")   $headers[]="wind_dir";
    if ($air_pressGet=="true")  $headers[]="pressure_mmhg";
    output_header($output_type, $headers);

    $query = "SELECT inches, air_temp_f, wind_speed_mph, wind_dir, pressure_mmhg, pmdate, pm_id from precipitation_measurements WHERE PS_ID=? and pmdate>=? and pmdate<=? order by pmdate";
    $stmt = mysqli_prepare($mysqlid, $query); 
    if($stmt==false) {printf("Errormessage: %s\n", mysqli_error($mysqlid));exit;}
    mysqli_stmt_bind_param($stmt, "sss", $PS_ID, $stdate, $enddate);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $inches, $air_temp_f, $wind_speed_mph, $wind_dir, $pressure_mmhg, $pmdate, $pm_id);
    while (mysqli_stmt_fetch($stmt))
    {
        $data=array();
        $data_type="";
        $data[]=$pmdate; $data_type.=DATA_DAYTIME;
        
        if ($inGet=="true")         {$data[]=$inches; $data_type.=DATA_FLOAT2;}
        if ($tempGet=="true")       {$data[]=$air_temp_f; $data_type.=DATA_FLOAT2;}
        if ($wind_speedGet=="true") {$data[]=$wind_speed_mph; $data_type.=DATA_FLOAT2;}
        if ($wind_dirGet=="true")   {$data[]=$wind_dir; $data_type.=DATA_FLOAT2;}
        if ($air_pressGet=="true")  {$data[]=$pressure_mmhg; $data_type.=DATA_FLOAT2;}
        
        $data["edit_link"] = $elink."pm_id=$pm_id";
        output_row($output_type, $data, $data_type);
    }
    
}

// IMPORTANT NOTE: This is a monthly average, BUT it averages from the
//  start date to the end date, EVEN IF THAT DATE IS IN THE MIDDLE OF
//  A MONTH.  If you want the WHOLE month, make sure that your query
// extends from the first to the last day.

if ($report_typeGet =="daily" || $report_typeGet =="monthly") {
    $stdate  = isset($_GET["stdate"])  ? $_GET["stdate"]  : "1980-01-01";
    $enddate = isset($_GET["enddate"]) ? $_GET["enddate"] : "2020-12-31";

    if ($report_typeGet == "daily") {
        $date_format = "%Y-%m-%d";
    };
    if ($report_typeGet == "monthly") {
        $date_format = "%Y-%m";
    };
    
    $headers[]="date";
    if ($inGet=="true")         $headers[]="inches";
    if ($tempGet=="true")       array_push($headers, "air_temp_f (max)","air_temp_f (avg)","air_temp_f (min)");
    if ($wind_speedGet=="true") array_push($headers, "wind_speed_mph (max)","wind_speed_mph (avg)","wind_speed_mph (min)");
    if ($wind_dirGet=="true")   $headers[]="wind_dir (avg)";
    if ($air_pressGet=="true")  array_push($headers, "pressure_mmhg (max)","pressure_mmhg (avg)","pressure_mmhg (min)");
    output_header($output_type, $headers);
    
    $select_block = " date_format(pmdate,'$date_format') AS `day`,sum(inches) AS precip,max(air_temp_f) AS max_T,min(air_temp_f) AS min_T,avg(air_temp_f) AS avg_T,
        max(wind_speed_mph) AS max_wind,min(wind_speed_mph) AS min_wind,avg(wind_speed_mph) AS avg_wind,avg(wind_dir) AS avg_wind_dir,
        max(pressure_mmhg) AS max_press,min(pressure_mmhg) AS min_press,avg(pressure_mmhg) AS avg_press";
    
    $query = "SELECT $select_block from precipitation_measurements WHERE PS_ID=? and date_format(pmdate,'%Y-%m-%d')>=? and date_format(pmdate,'%Y-%m-%d')<=? group by date_format(pmdate,'$date_format')order by day";
    //var_dump($report_typeGet);
    //var_dump($query, $PS_ID, $stdate, $enddate);
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
        
        if ($inGet=="true")         {$data[]=$precip; $data_type.=DATA_FLOAT2;}
        if ($tempGet=="true")       {array_push($data, $max_T,$avg_T,$min_T); $data_type.=DATA_FLOAT2.DATA_FLOAT2.DATA_FLOAT2;}
        if ($wind_speedGet=="true") {array_push($data, $max_wind,$avg_wind,$min_wind); $data_type.=DATA_FLOAT2.DATA_FLOAT2.DATA_FLOAT2;}
        if ($wind_dirGet=="true")   {$data[]=$avg_wind_dir; $data_type.=DATA_FLOAT2;}
        if ($air_pressGet=="true")  {array_push($data, $max_press,$avg_press,$min_press); $data_type.=DATA_FLOAT2.DATA_FLOAT2.DATA_FLOAT2;}
        
        output_row($output_type, $data, $data_type);
    }
}
output_footer($output_type);
output_end($output_type, 'includes/qp_footer.php');

?>