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
$headers[2]="STORET_Station_ID";
$headers[3]="MPCA_Site_ID";
$headers[4]="Date";
$headers[5]="Military Time";
$headers[6]="Sample Depth";
$headers[7]="QA_Sample_Type";
$headers[8]="Sample_Collection_Procedure";
$headers[9]="Project_Personnel_Name";
$headers[10]="Lab_ID";
$headers[11]="Lab_Sample_ID";
$headers[12]="Comments";

$datarow_template=array();
$datarow_template["Project_ID"]="MCWD";
$datarow_template["Project_Station_ID"]="";
$datarow_template["STORET_Station_ID"]="";
$datarow_template["MPCA_Site_ID"]="";
$datarow_template["Date"]="";
$datarow_template["Military_Time"]="";
$datarow_template["Sample_Depth"]="";
$datarow_template["QA_Sample_Type"]="";
$datarow_template["Sample_Collection_Procedure"]="";
$datarow_template["Project_Personnel_Name"]="MCWD";
$datarow_template["Lab_ID"]="";
$datarow_template["Lab_Sample_ID"]="";
$datarow_template["Comments"]="";

$data_type_base = DATA_STRING.DATA_STRING.DATA_STRING.DATA_STRING.DATA_STRING.DATA_STRING.DATA_FLOAT2.DATA_STRING.
	DATA_STRING.DATA_STRING.DATA_STRING.DATA_STRING.DATA_STRING;

//import siteid and measurement_types to local arrays
$sites=$_GET["siteid"];	
$mtypes=array();
$mheader=array();
$units=array_fill(0,13,""); // blank columns 
$units[6]="m"; // depth column

if ($wbody_type=="L") $check_col="lake";
elseif ($wbody_type=="S") $check_col="stream";
else $check_type=0; // set query to return no rows if neither type (0=1)
$typequery = "SELECT mtypeid,storet_header, units FROM measurement_type WHERE $check_col=1 ORDER BY  l_profile DESC, mtypeid";
$res = mysqli_query($mysqlid, $typequery);
while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC))
{
	$mtypes[]=$row['mtypeid'];
	$mheader[]=$row['storet_header'];
	$units[]=$row['units'];
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

//prepare the query statements;
$sitequery="SELECT project_station_id, storet_station_id, mpca_site_id FROM monitoring_sites WHERE siteid=?";
$sitestmt = mysqli_prepare($mysqlid,$sitequery);
if($sitestmt==false) {printf("Error message: %s\n", mysqli_error($mysqlid));exit;}
$measquery = "SELECT * FROM measurements WHERE siteid=? and $mtypein $datewhere order by mtime, depth, duplicate"; 
$measstmt = mysqli_prepare($mysqlid, $measquery); 
if($measstmt==false) {printf("Error message: %s\n", mysqli_error($mysqlid));exit;}
/*
 * loop through the sites provided to run the query and display results
 */
foreach ($sites as $siteid)
{
	mysqli_stmt_bind_param($sitestmt, "s", $siteid);
	mysqli_stmt_execute($sitestmt);
	mysqli_stmt_bind_result($sitestmt, $project_station_id, $storet_station_id, $mpca_site_id);
	mysqli_stmt_fetch($sitestmt);
	mysqli_stmt_free_result($sitestmt);
	$datarow_site = $datarow_template;
	$datarow_site["Project_Station_ID"]=$project_station_id;
	$datarow_site["STORET_Station_ID"]=$storet_station_id;
	$datarow_site["MPCA_Site_ID"]=$mpca_site_id;

	$bind_param[0] = $siteid; //place the proper siteid in the bound para array index=0
	
	dynamic_mysqli_bind_param($measstmt, $bind_param_type, $bind_param);
	mysqli_stmt_execute($measstmt);
	mysqli_stmt_bind_result($measstmt, $m_id, $mtime, $value, $detlimit, $depth, $duplicate, $collection_proc, $lab_id, $lab_sample_id, $mnotes, $siteid, $mtypeid);
	
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
		if ($collection_proc) $datarow["Sample_Collection_Procedure"]=$collection_proc;
		if ($lab_id) $datarow["Lab_ID"]=$lab_id;
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
			if ($collection_proc) $datarow["Sample_Collection_Procedure"]=$collection_proc;
			if ($lab_id) $datarow["Lab_ID"]=$lab_id;
			if ($lab_sample_id) $datarow["Lab_Sample_ID"]=$lab_sample_id;
			
		}
		else {
			//output_row($output_type, $datarow, $data_type);
			$fulldata[$irow]=$datarow;
			$irow++;
			$datarow=array_merge($datarow_site,$vdata_empty);
			list($datarow["Date"],$datarow["Military_Time"])=explode(" " ,$mtime);
			$datarow[$mtypeid] = $value;
			$datarow["Sample_Depth"]=$depth;		
			if ($collection_proc) $datarow["Sample_Collection_Procedure"]=$collection_proc;
			if ($lab_id) $datarow["Lab_ID"]=$lab_id;
			if ($lab_sample_id) $datarow["Lab_Sample_ID"]=$lab_sample_id;
			if ($mnotes) $datarow["Comments"]=$mnotes;
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