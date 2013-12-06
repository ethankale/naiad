<?php
require_once 'includes/wqinc.php';
if ($_GET['section']=="wbody" && $_GET['wbody_type'])
{
	$wbody_type=$_GET['wbody_type'];
	print "<option value=''>Select a waterbody</option>\n";
	$query = "SELECT waterbody_id, wbody_name FROM waterbodies WHERE wbody_type = ? ORDER BY wbody_name ASC";
	$stmt = mysqli_prepare($mysqlid, $query); 
	mysqli_stmt_bind_param($stmt, "s", $wbody_type);	
	mysqli_stmt_execute($stmt);
	mysqli_stmt_bind_result($stmt, $waterbody_id,$wbody_name);
	while(mysqli_stmt_fetch($stmt)) {
		print '<option value="'.$waterbody_id.'">'.$wbody_name. "</option>\n";
	}
	mysqli_stmt_close($stmt);
}
if ($_GET['section']=="site" && $_GET['waterbody_id'])
{
	$waterbody_id=$_GET['waterbody_id'];
	print "<option value=''>Select a site</option>\n";
	$query = "SELECT siteid,site_description FROM sites_list WHERE waterbody_id=? ORDER BY active asc, site_description asc, siteid";
	$stmt = mysqli_prepare($mysqlid, $query); 
	mysqli_stmt_bind_param($stmt, "i", $waterbody_id);	
	mysqli_stmt_execute($stmt);
	mysqli_stmt_bind_result($stmt, $listsiteid,$site_description);
	while(mysqli_stmt_fetch($stmt)) {
		$sd = substr($site_description,0,15);
		print "<option value='$listsiteid'>$sd - $listsiteid</option>\n";	
	}
	mysqli_stmt_close($stmt);
}
if ($_GET['section']=="check_record" && $_GET['waterbody_id'])
{
	$wbody_type=$_GET['wbody_type'];
	if (preg_match ('`^([0-9]{1,2})/([0-9]{1,2})/([0-9]{4})$`', $_GET['sdate'], $parts))
	{
    	//check weather the date is valid of not checkdate($parts[2],$parts[3],$parts[1
    	$_GET['sdate'] = sprintf("%04d-%02d-%02d", $parts[3],$parts[1],$parts[2]);
	}
	if (!strpos($_GET['stime'], ":"))
	{
		$_GET['stime']=sprintf("%02d",substr($_GET['stime'], 0,(strlen($_GET['stime'])-2))).":".substr($_GET['stime'],-2);
	}
	$mtime = $_GET['sdate']." ".$_GET['stime']."%";
	if ($wbody_type == "L")
	{
		$query = "SELECT count(*) FROM measurements WHERE siteid=? AND mtime LIKE ? AND (ABS(depth-?) < .001)";
		$stmt = mysqli_prepare($mysqlid, $query); 
		mysqli_stmt_bind_param($stmt, "ssd", $_GET['siteid'], $mtime , $_GET['depth'] );	
	}
	else if ($wbody_type == "S")
	{
		$query = "SELECT count(*) FROM measurements WHERE siteid=? AND mtime LIKE ? ";
		$stmt = mysqli_prepare($mysqlid, $query); 
		mysqli_stmt_bind_param($stmt, "ss", $_GET['siteid'], $mtime );	
	}
	else {print "error"; exit;}
	if($stmt==false) {printf("Errormessage: %s\n", mysqli_error($mysqlid));exit;}
	mysqli_stmt_execute($stmt);
	mysqli_stmt_bind_result($stmt, $count);
	mysqli_stmt_fetch($stmt);
	print "$count";	
	mysqli_stmt_close($stmt);
}

?>