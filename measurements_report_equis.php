<?php
/**
 * Displays measurements data
 * Inputs to the page are Monitoring site(s); fields of interest; start and end date; display type
 * Output is a table or spreadsheet download 
 */

$wbquery="SELECT wbody_type, wbody_name FROM waterbodies WHERE waterbody_id=?";
$wbstmt = mysqli_prepare($mysqlid,$wbquery);
if($wbstmt==false) {printf("Errormessage: %s\n", mysqli_error($mysqlid));exit;}
mysqli_stmt_bind_param($wbstmt, "i", $_GET["waterbodyid"]);
mysqli_stmt_execute($wbstmt);
mysqli_stmt_bind_result($wbstmt, $wbody_type, $wbody_name);
mysqli_stmt_fetch($wbstmt);
mysqli_stmt_close($wbstmt);
$default_personnel = get_general_value_table("def_equis_personnel", $mysqlid);

// read desired output method and set pagename and output type
if ($_GET['downloadcsv']=="true") {
	$output_type=OF_CSV;
	$page_title="storet_".$wbody_name.".csv";
}
else {
	$page_title='Measurements Data';
	$output_type=OF_TBL;
}
//send the page/file header info
output_start($output_type, $page_title, "includes/qp_header.php");


$relink_csv='<a href="'.$_SERVER["REQUEST_URI"].'&downloadcsv=true">Download as .csv</a><br>';
output_line($output_type, $relink_csv,false);

// Initiate variables
$headers=array();			//column headers for output
$bind_param = array();		//array to hold the parameters to bind for the main search
$bind_param_type = "";		//string to define types for bound parameters

$headers[0]="Project_ID";
$headers[1]="Project_Station_ID";
$headers[2]="EQUiS_Location_ID";
$headers[3]="Date";
$headers[4]="Military Time";
$headers[5]="Project_Site_ID";
$headers[6]="MPCA_Site_ID";
$headers[7]="Sample_Depth";
$headers[8]="Sample_Depth_Upper";
$headers[9]="Sample_Depth_Lower";
$headers[10]="QA_Sample_Type";
$headers[11]="Sample_Collection_Procedure";
$headers[12]="Gear_Configuration_ID";
$headers[13]="Project_Personnel_Name";
$headers[14]="Lab_ID";
$headers[15]="Lab_Sample_ID";
$headers[16]="Comments";

$datarow_template=array();
$datarow_template["Project_ID"]="";
$datarow_template["Project_Station_ID"]="";
$datarow_template["EQUiS_Location_ID"]="";
$datarow_template["Date"]="";
$datarow_template["Military_Time"]="";
$datarow_template["Project_Site_ID"]="";
$datarow_template["MPCA_Site_ID"]="";
$datarow_template["Sample_Depth"]="";
$datarow_template["Sample_Depth_Upper"]="";
$datarow_template["Sample_Depth_Lower"]="";
$datarow_template["QA_Sample_Type"]="";
$datarow_template["Sample_Collection_Procedure"]="";
$datarow_template["Gear_Configuration_ID"]="";
$datarow_template["Project_Personnel_Name"]="";
$datarow_template["Lab_ID"]="";
$datarow_template["Lab_Sample_ID"]="";
$datarow_template["Comments"]="";

$data_type_base = DATA_STRING.DATA_STRING.DATA_STRING.DATA_STRING.DATA_STRING.DATA_STRING.DATA_STRING.DATA_FLOAT2.DATA_FLOAT2.DATA_FLOAT2.
	DATA_STRING.DATA_STRING.DATA_STRING.DATA_STRING.DATA_STRING.DATA_STRING.DATA_STRING;

//import siteid and measurement_types to local arrays
$sites=$_GET["siteid"];	
$mtypes=array();
$mheader=array();
$units=array_fill(0,17,""); // blank columns 
$units[7]="m"; // depth column
$units[8]="m"; // depth column
$units[9]="m"; // depth column

if ($wbody_type=="L") $check_col="lake";
elseif ($wbody_type=="S") $check_col="stream";
else $check_type=0; // set query to return no rows if neither type (0=1)
$typequery = "SELECT mtypeid,storet_header, units FROM measurement_type WHERE $check_col=1 AND active=1 ORDER BY  disp_order ASC, l_profile DESC, mtypeid";
$res = mysqli_query($mysqlid, $typequery);
while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC))
{
	$mtypes[]=$row['mtypeid'];
	$mheader[]=$row['storet_header'];
	$units[]=$row['units'];
}
//make arrays for sample depths
$sdu=array(); // array for sample depth upper Key = procedure; value = sample depth upper 
$sdl=array(); // array for sample depth lower Key = procedure; value = sample depth lower 
$sdepthquery = "SELECT proc_id, sample_depth_upper, sample_depth_lower FROM sample_procs WHERE active=1"; 
$res = mysqli_query($mysqlid, $sdepthquery);
while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC))
{
	$sdu[$row['proc_id']]=$row['sample_depth_upper'];
	$sdl[$row['proc_id']]=$row['sample_depth_lower'];
}
/*******************************************/
$headers = array_merge($headers,$mheader); 		// place the measurement types in the column headers
$vdata_empty = array_fill_keys($mtypes, NULL); 	// create empty array with types as keys for use as template in data loop

$bind_param[0]=" "; 		// placeholder for site id in first position
$bind_param_type = "s";		// first bound parameter will be siteid (string)

//create measurement types IN clause for SQL; add to the bound parameters array and type definition string 
$mtypein = " mtypeid IN (".substr(str_repeat("?,", sizeof($mtypes)),0,-1).") "; 
$bind_param = array_merge($bind_param,$mtypes); 
$bind_param_type .= str_repeat("s", sizeof($mtypes));


// string to hold date clauses ; if start or end date are present add to sql query, bound param array, type string
$datewhere=""; 
if (preg_match ('`^([0-9]{1,2})/([0-9]{1,2})/([0-9]{4})$`', $_GET['stdate'], $parts))
   	$_GET['stdate'] = sprintf("%04d-%02d-%02d", $parts[3],$parts[1],$parts[2]);
if (preg_match ('`^([0-9]{1,2})/([0-9]{1,2})/([0-9]{4})$`', $_GET['enddate'], $parts))
   	$_GET['enddate'] = sprintf("%04d-%02d-%02d", $parts[3],$parts[1],$parts[2]);

if($_GET["stdate"]) {
	$sd = $_GET["stdate"]." 00:00:00";
	$datewhere .= " AND mtime>=? ";
	$bind_param[]= $sd;
	$bind_param_type.= "s";
}

if($_GET["enddate"]) {
	$_GET['enddate'] = login_results_date($_GET['enddate'], $log_user, $mysqlid);
	$ed = $_GET["enddate"]." 23:59:59";
	$datewhere .= " AND mtime<=? ";
	$bind_param[]= $ed;
	$bind_param_type.= "s";
}
if($_GET["storet_proj_id"] && $_GET["storet_proj_id"] != "ANY") {
	$datewhere .= " AND proj_id = ? ";
	$bind_param[]= $_GET["storet_proj_id"];
	$bind_param_type.= "s";
}
//prepare the query statements;
$sitequery="SELECT site_description, project_station_id, storet_station_id, mpca_site_id, project_site_id FROM monitoring_sites WHERE siteid=?";
$sitestmt = mysqli_prepare($mysqlid,$sitequery);
if($sitestmt==false) {printf("Error message: %s\n", mysqli_error($mysqlid));exit;}
$measquery = "SELECT m_id, mtime, value, detection_limit, depth, duplicate, lab_id, lab_sample_id, mnotes, siteid, mtypeid, proj_id, proc_id, gear_id, collected_by, user_entry, user_update
	FROM measurements WHERE siteid=? and $mtypein $datewhere order by mtime, depth, duplicate"; 
$measstmt = mysqli_prepare($mysqlid, $measquery); 
if($measstmt==false) {printf("Error message: %s\n", mysqli_error($mysqlid));exit;}
/*
 * loop through the sites provided to run the query and display results
 */
foreach ($sites as $siteid)
{
	mysqli_stmt_bind_param($sitestmt, "s", $siteid);
	mysqli_stmt_execute($sitestmt);
	mysqli_stmt_bind_result($sitestmt, $site_description, $project_station_id, $storet_station_id, $mpca_site_id, $project_site_id);
	mysqli_stmt_fetch($sitestmt);
	mysqli_stmt_free_result($sitestmt);
	$datarow_site = $datarow_template;
	$datarow_site["Project_Station_ID"]=$project_station_id?$project_station_id:$site_description;
	$datarow_site["EQUiS_Location_ID"]=$storet_station_id;
	$datarow_site["MPCA_Site_ID"]=$mpca_site_id;
	$datarow_site["Project_Site_ID"]=$project_site_id;
	
	$bind_param[0] = $siteid; //place the proper siteid in the bound para array index=0
	
	dynamic_mysqli_bind_param($measstmt, $bind_param_type, $bind_param);
	mysqli_stmt_execute($measstmt);
	mysqli_stmt_bind_result($measstmt, $m_id, $mtime, $value, $detlimit, $depth, $duplicate, $lab_id, $lab_sample_id, $mnotes, $siteid, 
		$mtypeid, $proj_id, $proc_id, $gear_id, $collected_by, $user_entry, $user_update);
	
	//initiate variables for results loop
	$fulldata = array();	//data will be held here
	$oldday="";				//previous result daytime - used to identify date alterations 
	$olddepth="";			//previous result depth - used to identify new profile layer
	$olddup=""; 
	$datarow=array();		//array to hold data for each daystamp
	
	//first iteration - set the date variables - (mtime/olday) display headers
	
	if(mysqli_stmt_fetch($measstmt)) {
		$datarow=array_merge($datarow_site,$vdata_empty);
		$datarow["Sample_Depth"]=$depth;
		$oldday=$mtime;
		$olddepth=$depth;
		$datarow[$mtypeid] = $value;
		list($datarow["Date"],$datarow["Military_Time"])=explode(" " ,$mtime);
		if ($proc_id) {
			$datarow["Sample_Collection_Procedure"]=$proc_id;
			$datarow["Sample_Depth_Upper"] =key_exists($proc_id, $sdu)?$sdu[$proc_id]:NULL;
			$datarow["Sample_Depth_Lower"] =key_exists($proc_id, $sdl)?$sdl[$proc_id]:NULL;
		}
		else {$datarow["Sample_Depth_Lower"]=$datarow["Sample_Depth_Upper"]=NULL;}
		if ($proj_id) $datarow["Project_ID"]=$proj_id;
		if ($gear_id) $datarow["Gear_Configuration_ID"]=$gear_id;
		if ($lab_id) $datarow["Lab_ID"]=$lab_id;
		if ($collected_by) $datarow["Project_Personnel_Name"]=$collected_by;
		elseif(!$datarow["Project_Personnel_Name"]) $datarow["Project_Personnel_Name"]=$default_personnel;
		if ($lab_sample_id) $datarow["Lab_Sample_ID"]=$lab_sample_id;
		if ($mnotes) $datarow["Comments"]=$mnotes;
	}
	$irow=0;
	while(mysqli_stmt_fetch($measstmt)) {
		if ($detlimit==1) $value="<$value";
		if ($mtime == $oldday && $duplicate==$olddup && $depth==$olddepth)
		{
			$datarow["Sample_Depth"]=$depth;
			$datarow[$mtypeid] = $value;
			if ($proc_id) {
				$datarow["Sample_Collection_Procedure"]=$proc_id;
				$datarow["Sample_Depth_Upper"] =key_exists($proc_id, $sdu)?$sdu[$proc_id]:NULL;
				$datarow["Sample_Depth_Lower"] =key_exists($proc_id, $sdl)?$sdl[$proc_id]:NULL;
			}
			else {$datarow["Sample_Depth_Lower"]=$datarow["Sample_Depth_Upper"]=NULL;}
			if ($proj_id) $datarow["Project_ID"]=$proj_id;
			if ($gear_id) $datarow["Gear_Configuration_ID"]=$gear_id;
			if ($lab_id) $datarow["Lab_ID"]=$lab_id;
			if ($collected_by) $datarow["Project_Personnel_Name"]=$collected_by;
			elseif(!$datarow["Project_Personnel_Name"]) $datarow["Project_Personnel_Name"]=$default_personnel;
			if ($lab_sample_id) $datarow["Lab_Sample_ID"]=$lab_sample_id;
			if ($mnotes) $datarow["Comments"].=$mnotes;
			
		}
		else {
			//output_row($output_type, $datarow, $data_type);
			$fulldata[$irow]=$datarow;
			$irow++;
			$datarow=array_merge($datarow_site,$vdata_empty);
			list($datarow["Date"],$datarow["Military_Time"])=explode(" " ,$mtime);
			$datarow[$mtypeid] = $value;
			$datarow["Sample_Depth"]=$depth;		
			if ($proc_id) {
				$datarow["Sample_Collection_Procedure"]=$proc_id;
				$datarow["Sample_Depth_Upper"] =key_exists($proc_id, $sdu)?$sdu[$proc_id]:NULL;
				$datarow["Sample_Depth_Lower"] =key_exists($proc_id, $sdl)?$sdl[$proc_id]:NULL;
			}
			else {$datarow["Sample_Depth_Lower"]=$datarow["Sample_Depth_Upper"]=NULL;}
			if ($proj_id) $datarow["Project_ID"]=$proj_id;
			if ($gear_id) $datarow["Gear_Configuration_ID"]=$gear_id;
			if ($lab_id) $datarow["Lab_ID"]=$lab_id;
			if ($collected_by) $datarow["Project_Personnel_Name"]=$collected_by;
			elseif(!$datarow["Project_Personnel_Name"]) $datarow["Project_Personnel_Name"]=$default_personnel;
			if ($lab_sample_id) $datarow["Lab_Sample_ID"]=$lab_sample_id;
			if ($mnotes) $datarow["Comments"].=$mnotes;
			if ($duplicate) $datarow["QA_Sample_Type"]="Duplicate";
			$oldday=$mtime;
			$olddepth=$depth;
			$olddup=$duplicate;
		}
	}
	$data_type = $data_type_base.(str_repeat(DATA_FLOAT2, count($mtypes)));
	$fulldata[$irow]=$datarow;
	mysqli_stmt_free_result($measstmt);
	output_header($output_type, $headers,NULL,$tabletitle);
	$oldday="";
	output_row($output_type, $units, str_repeat(DATA_STRING, count($units)));	
	foreach ($fulldata as $row) {
		output_row($output_type, $row, $data_type);
		
	}
	output_footer($output_type);
	
}

mysqli_stmt_close($sitestmt);
mysqli_stmt_close($measstmt);
//

 
exit;
	
?>	