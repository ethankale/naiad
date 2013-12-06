<?php
/*
 * Page for adding and editing measurement types
 */
$page_title='Duplicate Administration';
require_once 'includes/wqinc.php';
$pagelevel = PAGE_ADMIN;

require_once 'includes/qp_header.php';

login_check($pagelevel, $log_user);
if ($_POST['faction']== "del")
{
	$del_ids = $_POST['dups_to_del'];
	$delid=-1;
	$query = "DELETE FROM measurements WHERE m_id=?";
	$stmt = mysqli_prepare($mysqlid, $query);
	mysqli_stmt_bind_param($stmt, 'i', $delid);
	for($i=0; $i< sizeof($del_ids);$i++)
	{
		$delid=$del_ids[$i];
		mysqli_stmt_execute($stmt);	
		if (mysqli_stmt_errno($stmt))
		{
			printf("Error: %d.\n", mysqli_stmt_errno($stmt)); 
			printf("Error: %s.\n", mysqli_stmt_error($stmt));
		}
		else print "Measurement ".$delid." deleted</br>\n";
	}
	
	require_once 'includes/qp_footer.php';
}
if (!$_GET['year']) {
	print "<form action=\"dup_check.php\" method=\"GET\">
	Select Waterbody Type: <input type=\"radio\" name=\"wbtype\" value=\"S\">Stream  <input type=\"radio\" name=\"wbtype\" value=\"L\">Lake <br>
	Specify year to search: <select name=\"year\">\n<option value='-1'>ALL</option>";
	for ($i=date('Y'); $i>1974; $i--)
	{
		print "<option value=$i>$i</option>\n";
	}
	print "</select><br>\n";
	print 'Site ID (optional) <select name="siteid">
	<option value="">Select</option>';
	$query = "SELECT siteid,site_description FROM monitoring_sites ORDER BY siteid aSC";
	$res = mysqli_query($mysqlid, $query);
	while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC))
	{
		print '<option value="'.$row['siteid'].'">'.$row['siteid']. " - ". substr($row['site_description'],0,30). "</option>\n";
	}
	mysqli_free_result($res);

	print "</select><br>\n";
	
	print '<button type="submit">Submit</button></form>';
	require_once 'includes/qp_footer.php';
	exit;
}
?><style>
table {
  border-collapse: collapse;
}

table tbody {
  border: 1px solid #999999;
}
</style>
<a href="dup_check.php">Search again</a>
<?php
print "<form action=\"dup_check.php\" method=\"POST\">\n";
print '<input type="hidden" name="faction" value="del">';
print "<table>\n";
print "<tr><td><b>SiteID</b></td><td>count</td><td>time</td><td>&nbsp;Type</td><td>dup</td><td>Depth</td><td>Meas ID</td><td>Value</td><td>Lab ID</td><td>Notes</td><td>&nbsp</td></tr>\n";
if ($_GET['wbtype']==S) $wbtype="S";	
if ($_GET['wbtype']==L) $wbtype="L";
if ($_GET['siteid']) {
	$sid = preg_replace('/\W/', '',substr($_GET['siteid'], 0,7));
	if ($sid) $sidstr=" AND siteid='$sid' ";
	else $sidstr="";
}
$query = "SELECT * FROM monitoring_sites WHERE monitor_type = '$wbtype' $sidstr ORDER BY siteid ASC";
$res = mysqli_query($mysqlid, $query);
$group="";
if ($_GET['year']!=-1) 
{
	$yr = intval($_GET['year']);
	$yr2 = $yr+1;
	$yrs = " and mtime > \"$yr-01-01\" and mtime < \"$yr2-01-01\"";
}
else {$yrs="";}
	
while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC))
{
	$sid = $row['siteid'];
	$s_desc = $row['site_description'];

	$query2 = "SELECT COUNT( value ) AS dupcount, mtime, mtypeid, duplicate, depth
		FROM  `measurements` WHERE  siteid = '$sid'  $yrs
		GROUP BY mtime, mtypeid, duplicate, depth ORDER BY dupcount DESC , mtime ASC";
//	$stmt = mysqli_prepare($mysqlid, $query2);
//	if($stmt==false) {printf("Errormessage: %s\n", mysqli_error($mysqlid));exit;}
	
//	mysqli_stmt_bind_param($stmt, "s", $sid);	
//	mysqli_stmt_execute($stmt);
//	mysqli_stmt_bind_result($stmt, $dupcount, $mtime, $mtypeid, $duplicate, $depth);
	$res2 = mysqli_query($mysqlid, $query2);
	while($row2 = mysqli_fetch_array($res2, MYSQLI_ASSOC)) {
		$dupcount = $row2['dupcount'];
		$mtime = $row2['mtime'];
		$mtypeid = $row2['mtypeid'];
		$duplicate = $row2['duplicate'];
		$depth = $row2['depth'];
				
		if ($dupcount == 1) continue;
		print "<tbody></tr><td><b>$sid</b></td><td>$dupcount</td><td>$mtime</td><td>&nbsp;$mtypeid&nbsp;</td><td>$duplicate</td><td>$depth</td><td colspan=5>&nbsp;</td></tr>\n";
		if (is_null($depth)) {
			$query3="SELECT m_id, value, detection_limit, lab_sample_id, mnotes from measurements where siteid = ? AND mtime=? AND mtypeid=? AND duplicate=? AND ISNULL(depth)";
			$stmt2 = mysqli_prepare($mysqlid, $query3);
			if($stmt2==false) {printf("Errormessage: %s\n", mysqli_error($mysqlid));exit;}
			mysqli_stmt_bind_param($stmt2, "sssi", $sid,$mtime, $mtypeid, $duplicate);	
		}
		else 
		{
			$query3="SELECT m_id, value, detection_limit, lab_sample_id, mnotes from measurements where siteid = ? AND mtime=? AND mtypeid=? AND duplicate=? AND (ABS(depth-?) < .001)";
			$stmt2 = mysqli_prepare($mysqlid, $query3);
			if($stmt2==false) {printf("Errormessage: %s\n", mysqli_error($mysqlid));exit;}
			mysqli_stmt_bind_param($stmt2, "sssid", $sid,$mtime, $mtypeid, $duplicate, $depth);	
		}
		mysqli_stmt_execute($stmt2);
		mysqli_stmt_bind_result($stmt2, $mid, $value, $detlim, $lsid, $notes);
		while(mysqli_stmt_fetch($stmt2)) {
			print "<tr><td colspan=6>&nbsp;</td><td>$mid</td><td>".($detlim?"&lt;":"").round($value,5)."</td><td>$lsid</td><td>$notes</td>";
//			if ($dupcount == 2 && $value==$ovalue && $detlim == $odetlim && $lsid==$olsid && $notes == $onotes)
//			{	$chk==true; }
			print "<td><input type='checkbox' name='dups_to_del[]' value='$mid'".($chk?" checked":"")."></td></tr>\n";
//			$ovalue=$value; 
//			$odetlim = $detlim; 
//			$olsid=$lsid;
//			$onotes = $notes;
		}
//		$ovalue=""; 
//		$odetlim=0; 
//		$olsid="";
//		$onotes="";
		print "</tbody>\n";
		mysqli_stmt_close($stmt2); 
		
	}
	
}
print '</table><button type="submit">Submit</button></form>';
require_once 'includes/qp_footer.php';