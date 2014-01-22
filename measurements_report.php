<?php
/**
 * Displays measurements data
 * Inputs to the page are Monitoring site(s); fields of interest; start and end date; display type
 * Output is a table or spreadsheet download 
 */
// include headers
require_once 'includes/wqinc.php';
$pagelevel = PAGE_USER;
require_once 'includes/outputformat.php';

// read desired output method and set pagename and output type
if (isset($_GET['storet_output'])) {
    if ($_GET['storet_output']=="true")
    {
        require_once 'measurements_report_equis.php';
        exit;
    };
};

if ((isset($_GET['downloadcsv']) ? $_GET['downloadcsv'] : null) =="true") {
    $output_type=OF_CSV;
    $page_title="meas".$_GET["waterbodyid"].".csv";
}
else {
    $page_title='Measurements Data';
    $output_type=OF_TBL;
}
//send the page/file header info
$output = output_start($output_type, $page_title, "includes/qp_header.php");

$elink="add_measurement.php?faction=edit&";
$graphlink="plot.php?";

$relink_csv='<a href="'.$_SERVER["REQUEST_URI"].'&downloadcsv=true">Download as .csv</a>';
output_line($output_type, $relink_csv,false, $output);

$research_link='<a href="measurements_form.php?'.$_SERVER["QUERY_STRING"].'">Search again</a>';
output_line($output_type, $research_link,false,$output);
$edit_return = urlencode ("measurements_report.php?".$_SERVER["QUERY_STRING"]);
$profile=0;

if ((isset($_GET['lake_profiles']) ? $_GET['lake_profiles'] : null)==1) $profile=1;
else $profile=0;

// Initiate variables
$headers            = array("Site ID", "Day");   //column headers for output; site id and date always first
$bind_param         = array();   //array to hold the parameters to bind for the main search
$bind_param_type    = "";        //string to define types for bound parameters

if ($profile) {$headers[]="Depth";}

//import siteid and measurement_types to local arrays
$sites  = $_GET["siteid"];
$mtypes = $_GET["mtypeid"];
//$headers = array_merge($headers,$mtypes);         // place the measurement types in the column headers
//$vdata_empty = array_fill_keys($mtypes, NULL);     // create empty array with types as keys for use as template in data loop

$bind_param[0]=" ";         // placeholder for site id in first position
$bind_param_type = "s";        // first bound parameter will be siteid (string)

// get the units from measurement types table for units row in output
$units=array("",""); // first two entries are blank for site id and date columns
$graphlinks=array(""); // first entry is blank for date column
if (preg_match ('`^([0-9]{1,2})/([0-9]{1,2})/([0-9]{4})$`', $_GET['stdate'], $parts))
       $_GET['stdate'] = sprintf("%04d-%02d-%02d", $parts[3],$parts[1],$parts[2]);
if (preg_match ('`^([0-9]{1,2})/([0-9]{1,2})/([0-9]{4})$`', $_GET['enddate'], $parts))
       $_GET['enddate'] = sprintf("%04d-%02d-%02d", $parts[3],$parts[1],$parts[2]);
if($_GET["stdate"]) $sd = $_GET["stdate"]." 00:00:00"; else $sd="";
$_GET['enddate'] = login_results_date($_GET['enddate'], $log_user, $mysqlid);
if($_GET["enddate"]) $ed = $_GET["enddate"]." 23:59:59";else $ed="";


if ($profile) {$units[]="m";$graphlinks[]="";} // in profile add depth in meters as second column
$unitquery = "SELECT units, l_multi_depth FROM measurement_type WHERE mtypeid=?"; 
$unitstmt = mysqli_prepare($mysqlid, $unitquery); 
mysqli_stmt_bind_param($unitstmt, "s", $mtypeid);
$multi_mts=array();
foreach ($mtypes as $mtypeid)
{
    mysqli_stmt_execute($unitstmt);
    mysqli_stmt_bind_result($unitstmt, $unit, $l_multi_depth);
    mysqli_stmt_fetch($unitstmt);
    mysqli_stmt_free_result($unitstmt);
    
    $units[]        = $unit;
    $headers[]      = $mtypeid;
    $vdata_empty[$mtypeid]=NULL;
    $graphlinks[]   = "<a href=\"javascript:plot_pop('".$graphlink."siteid=$sites[0]&stdate=$sd&enddate=$ed&mtypeid=$mtypeid')\">graph</a>";
    
    if (!$profile && $_GET['wbody_type']=='L' && $l_multi_depth)
    {
        $multi_mts[]    = $mtypeid;
        $headers[]      = $mtypeid."-M";
        $vdata_empty[$mtypeid."-M"]=NULL;
        $units[]        = $unit;
        $graphlinks[]   = "";
        $headers[]      = $mtypeid."-B";
        $vdata_empty[$mtypeid."-B"]=NULL;
        $units[]        = $unit;
        $graphlinks[]   = "";
    }    
}
mysqli_stmt_close($unitstmt);
//create measurement types IN clause for SQL; add to the bound parameters array and type definition string 
$mtypein = " mtypeid IN (".substr(str_repeat("?,", sizeof($mtypes)),0,-1).") "; 
$bind_param = array_merge($bind_param,$mtypes); 
$bind_param_type .= str_repeat("s", sizeof($mtypes));

$editable= false;
switch ($_GET['averaging']) {
    case "d":
        $view = "meas_daily";
        $headers[1]="Day";
        if($_GET["stdate"]) $sd = date_format(date_create_from_format("Y-m-d",$_GET["stdate"]),"Y-m-d");
        if($_GET["enddate"]) $ed = date_format(date_create_from_format("Y-m-d",$_GET["enddate"]),"Y-m-d");
        break;
    case "w":
        $view = "meas_weekly";
        $headers[1]="Week";
        if($_GET["stdate"]) $sd = date_format(date_create_from_format("Y-m-d",$_GET["stdate"]),"o-W");
        if($_GET["enddate"]) $ed = date_format(date_create_from_format("Y-m-d",$_GET["enddate"]),"o-W");
        break;
    case "m":
        $view = "meas_monthly";
        $headers[1]="Month";
        if($_GET["stdate"]) $sd = date_format(date_create_from_format("Y-m-d",$_GET["stdate"]),"Y-m");
        if($_GET["enddate"]) $ed = date_format(date_create_from_format("Y-m-d",$_GET["enddate"]),"Y-m");
        break;
    case "y":
        $view = "meas_yearly";
        $headers[1]="Year";
        if($_GET["stdate"]) $sd = date_format(date_create_from_format("Y-m-d",$_GET["stdate"]),"Y");
        if($_GET["enddate"]) $ed = date_format(date_create_from_format("Y-m-d",$_GET["enddate"]),"Y");
        break;
    default:
        $view = "meas_all";
        $headers[1]="Day";
        if($_GET["stdate"]) $sd = $_GET["stdate"]." 00:00:00";
        if($_GET["enddate"]) $ed = $_GET["enddate"]." 23:59:59";
        $editable=true;
        break;
}
// string to hold date clauses ; if start or end date are present add to sql query, bound param array, type string
$datewhere=""; 
if($_GET["stdate"]) {
    $datewhere .= " AND timeframe>=? ";
    $bind_param[]= $sd;
    $bind_param_type.= "s";
}

if($_GET["enddate"]) {
    $datewhere .= " AND timeframe<=? ";
    $bind_param[]= $ed;
    $bind_param_type.= "s";
}

if($_GET["storet_proj_id"] && $_GET["storet_proj_id"] != "ANY") {
    $datewhere .= " AND proj_id = ? ";
    $bind_param[]= $_GET["storet_proj_id"];
    $bind_param_type.= "s";
}



$sitequery="SELECT siteid, latitude, longitude, site_description, monitor_start, monitor_end, monitor_type, 
    project_station_id, storet_station_id, mpca_site_id, waterbody_id FROM monitoring_sites WHERE siteid=?";
$sitestmt = mysqli_prepare($mysqlid,$sitequery);
    
$measquery = "SELECT * FROM $view WHERE siteid=? and $mtypein $datewhere order by timeframe, duplicate, depth"; 
$measstmt = mysqli_prepare($mysqlid, $measquery) or die(mysqli_error($mysqlid)); 

/*
 * loop through the sites provided to run the query and display results
 */
foreach ($sites as $siteid)
{

    if($sitestmt==false) {printf("Error message: %s\n", mysqli_stmt_errno($sitestmt));exit;}
    mysqli_stmt_bind_param($sitestmt, "s", $siteid);
    mysqli_stmt_execute($sitestmt);
    mysqli_stmt_bind_result($sitestmt, $siteid, $latitude, $longitude, $site_description, $monitor_start, $monitor_end, $monitor_type, $project_station_id, $storet_station_id, $mpca_site_id, $waterbody_id );
    mysqli_stmt_fetch($sitestmt);
    mysqli_stmt_free_result($sitestmt);
    $tabletitle = "$siteid - $site_description - $storet_station_id";
    //if 
    $site_info="$site_description <br>Location: ".round($latitude,3)."N, ".round($longitude,3)."W<br> Monitoring started: $monitor_start ".($monitor_end?"Ended $monitor_end":"Ongoing")."<br> STORET ID: $storet_station_id  MPCA Site ID: $mpca_site_id";
    $bind_param[0] = $siteid; //place the proper siteid in the bound para array index=0
    
    if($measstmt==false) {printf("Errormessage: %s\n", mysqli_stmt_errno($measstmt));exit;}
    dynamic_mysqli_bind_param($measstmt, $bind_param_type, $bind_param);
    mysqli_stmt_execute($measstmt);
    mysqli_stmt_bind_result($measstmt, $mtime, $value, $detlimit, $depth, $siteid, $mtypeid, $mnotes, $duplicate, $proj_id);
    
    //initiate variables for results loop
    $fulldata   = array();          //data will be held here.  Array of arrays, with sub arrays equivalent to rows.  
                                    //  Sub arrays contain 2 elements (time and edit link), plus one (string) element per measurement type, with value, notes, and depth separated by | (vertical pipe).
                                    //  Notes and depth are not added if they return null.
    $oldday     ="";                //previous result daytime - used to identify date alterations 
    $olddepth   ="";                //previous result depth - used to identify new profile layer 
    $olddup     ="";                //previous result duplicate - used to identify new row 
    $datarow    =array();           //array to hold data for each daystamp
    $datatype   ="";                // string to identify output display type
    $vdata      = $vdata_empty;    //set the valuedata array to the template (with measurement type keys
    
    //first iteration - set the date variables - (mtime/olday) display headers
    
    if(mysqli_stmt_fetch($measstmt)) {
        if ($detlimit==1) $value="<$value";
        $datarow["id"]      =$siteid;
        $datarow["time"]    =$mtime;
        if ($profile) {$datarow["depth"]=$depth;}
        $oldday=$mtime;
        $vdata[$mtypeid] = $value.($mnotes?"|$mnotes":"");
        if ($editable) {$edit_link=array("edit_link"=>("$elink"."siteid=$siteid&mtime=$mtime&duplicate=$duplicate".(($_GET['wbody_type']=='L')?"&depth=$depth":"")).($edit_return?"&edit_return=$edit_return":""));}
        else {$edit_link=array();}
    }
    else {
        // May need a conditional & two statements here, one for CSV and one for table.
        output_line($output_type, "No data at $site_description with your parameters",false, $output);
        continue;
    }
    
    $irow=0;
    while(mysqli_stmt_fetch($measstmt)) {
        if ($detlimit==1) $value="<$value";
        if ($mtime == $oldday && $duplicate==$olddup && (!$profile || $depth==$olddepth)) 
        {
            if ($profile && $depth==$olddepth) {$datarow["depth"]=$depth;}// $datarow[0]="";$datarow[1]="";}
            if (!$profile && $depth >1 && in_array($mtypeid, $multi_mts))
            {
                $depth= round($depth,3);
                
                if ($vdata[$mtypeid."-B"]) {
                    $vdata[$mtypeid."-M"]=$vdata[$mtypeid."-B"];
                }
                
                $vdata[$mtypeid."-B"] = $value.($mnotes?"|$depth m- $mnotes":"|$depth m");
            }
            else {
                $vdata[$mtypeid] = $value.($mnotes?"|$mnotes":"");
            }
        }
        else {
            //end of row clean up
            $datarow = array_merge($datarow,$vdata,$edit_link);
            $fulldata[$irow]=$datarow;
            $irow++;
            $datarow=array();
            $vdata = $vdata_empty;
            $datatype="";
            
            // new row
            $datarow["id"]      =$siteid;
            $datarow["time"]    =$mtime;
            if ($editable) {
                $edit_link=array("edit_link"=>("$elink"."siteid=$siteid&mtime=$mtime&duplicate=$duplicate".(($_GET['wbody_type']=='L')?"&depth=$depth":"")).($edit_return?"&edit_return=$edit_return":""));
            }else {
                $edit_link=array();
            }
            $vdata[$mtypeid] = $value.($mnotes?"|$mnotes":"");
            if ($profile) {$datarow["depth"]=$depth; }
            $oldday     = $mtime;
            $olddepth   = $depth;
            $olddup     = $duplicate;
        }
        
    }
    //print_r($fulldata);
    $datarow        = array_merge($datarow,$vdata,$edit_link);
    $data_type      = DATA_STRING.DATA_DAYTIME.(str_repeat(DATA_FLOAT5, count($units)));
    $fulldata[$irow]=$datarow;
    
    
    //output_header() and output_row() functions are kept in \includes\outputformat.php.
    mysqli_stmt_free_result($measstmt);
    $sort_headers = array_keys($fulldata[0]);

    output_site_info($output_type, $tabletitle, $site_info, $output);
    output_header($output_type, $headers, $sort_headers,"",$units, $output);

    if (!$profile && $output_type!=OF_CSV) {output_row($output_type, $graphlinks, str_repeat(DATA_STRING, count($graphlinks)), $output);}
    usort($fulldata, "compare_vals");
    $oldday="";
    
    //var_dump($data_type);
    
    foreach ($fulldata as $row) {
        
        if ($row["time"] == $oldday && $output_type!=OF_CSV) {
            $row["time"]="";
        }else $oldday=$row["time"];
        
        output_row($output_type, $row, $data_type, $output);
        
    }
    output_footer($output_type);
    
}
//
mysqli_stmt_close($sitestmt);
mysqli_stmt_close($measstmt);
exit;



?>