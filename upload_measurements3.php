<?php
$a=microtime(true);
$page_title='Measurements Upload Form';
require_once 'includes/wqinc.php';
$pagelevel = PAGE_USER;

require_once 'includes/qp_header.php';

login_check($pagelevel, $log_user); 
$uploaddir = 'up_data/';
$uploadfile = $uploaddir.$_POST['datafile'];
$header_row=$_POST['header_row']-1; //correct for zero index
$header_rows=$_POST['first_data_row']-1;
$delimiter=$_POST['delimiter'];
if ($delimiter=='\t') $delimiter="\t";
$encaps=$_POST['encaps'];
$sites = file($uploadfile);
//print "parsing data in ".basename($uploadfile)."<br>\n";
//print "found ".(sizeof($sites))." lines of data<br>\n";

$query = "SELECT siteid, project_station_id FROM monitoring_sites";
$res = mysqli_query($mysqlid, $query);
$valid_sites=array();
while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC))
{
	if ($row['project_station_id']) {
		$valid_sites[$row['project_station_id']]=$row['siteid'];
	}
	else 
	{
		$valid_sites[]=$row['siteid'];
	}
}

$query = "SELECT mtypeid FROM measurement_type where l_profile=1";
$res = mysqli_query($mysqlid, $query);
$profile_types=array();
while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC))
{
	$profile_types[]=$row['mtypeid'];
}

$block_cols=array();
$block_cols["siteid"]=intval($_REQUEST['siteid_col']);
$block_cols["proj_stat_id"]=intval($_REQUEST['proj_stat_id_col']);
$block_cols["date"]=intval($_REQUEST['date_col']);
$block_cols["time"]=intval($_REQUEST['time_col']);
$block_cols["depth"]=intval($_REQUEST['depth_col']);
$block_cols["duplicate"]=intval($_REQUEST['dup_col']);
$block_cols["collection_proc"]=intval($_REQUEST['coll_proc_col']);
$block_cols["lab_id"]=intval($_REQUEST['lab_id_col']);
$block_cols["lab_sample_id"]=intval($_REQUEST['lab_sample_id_col']);
$block_cols["mnotes"]=intval($_REQUEST['notes_col']);
foreach ($block_cols as $key => $value) {
	if ($value==-1) $block_cols[$key]="blank";
}

$meas_cols=array();	
$lower=array();
$upper=array();

if ($_POST['wbody_type']=="L") {
	$check_col="lake";
	$col_string=", l_lower_bound, l_upper_bound";
}
elseif ($_POST['wbody_type']=="S") {
	$check_col="stream";
	$col_string=", s_lower_bound, s_upper_bound";
}
else $check_type=0; // set query to return no rows if neither type (0=1)
$typequery = "SELECT mtypeid, mtname $col_string FROM measurement_type WHERE $check_col=1 ORDER BY  l_profile DESC, mtypeid";
$res = mysqli_query($mysqlid, $typequery);
while ($mtrow = mysqli_fetch_array($res, MYSQLI_NUM))
{
	if (intval($_REQUEST[$mtrow[0].'_col'])==-1) continue;
	$meas_cols[intval($_REQUEST[$mtrow[0].'_col'])]=$mtrow[0];	
	$lower[$mtrow[0]] = $mtrow[2];
	$upper[$mtrow[0]] = $mtrow[3];
}

?>
<form action="upload_measurements3.php" method="POST" name="f1" id="f1" onsubmit=""  enctype="multipart/form-data">
<input type="hidden" name="resend" value="true">
<input type="hidden" name="datafile" value="<?php print $_POST['datafile'];?>">
<input type="hidden" name="wbody_type" value="<?php print $_POST['wbody_type'];?>">
<input type="hidden" name="header_row" value="<?php print $_POST['header_row'];?>">
<input type="hidden" name="first_data_row" value="<?php print $_POST['first_data_row'];?>">
<input type="hidden" name="delimiter" value="<?php print $_POST['delimiter'];?>">
<input type="hidden" name="encaps" value='<?php print $_POST['encaps'];?>'>
<?php 

$keys = array_keys($_POST);
foreach ($keys as $key) {
	if (strpos($key, "_col"))
	{
		print "<input type=\"hidden\" name=\"$key\" value=\"$_POST[$key]\">\n";
	}
}

?>

<table class='entrytable' id='error_table'>
<tr class="brow"><td>Line</td><td>Date/Time</td><td>Site</td><td>Type</td><td>Value</td><td>Bounds</td><td>Override</td><td>New Value</td><td>Skip</td></tr>
<?php 
$hidden_block="";
$errors=0;
$store_array=array();
$dup_next=false;
$prev_depth=null;

for ($i=$header_rows; $i<sizeof($sites); $i++)
{
	$row=str_getcsv($sites[$i],$delimiter,$encaps);
	$row["blank"]="";
	$siteid = trim($row[$block_cols["siteid"]]);
	if (!$siteid && $_REQUEST['siteid_col']== -1)
	{
		$siteid = $valid_sites[$row[$block_cols["proj_stat_id"]]];

	}
	if (!$siteid) continue;
	if($_POST["skip_row_$i"]=="true")
	{
		$hidden_block .= "<input type='hidden' name='skip_row_$i' value='true'>\n";
		continue;
	}
	$m++;
	list($dm,$dd,$dy) = explode("/", $row[$block_cols["date"]]);
	$time = $row[$block_cols["time"]];
	if (!strpos($time,':')) {$time = implode(":",str_split(sprintf('%04s',$time),2));}
	$mtime = "$dy-$dm-$dd $time";
	$lab_id = trim($row[$block_cols["lab_id"]]);
	$lab_sample_id = trim($row[$block_cols["lab_sample_id"]]);
	$duplicate = (stripos($row[$block_cols["duplicate"]],"dup")!==false)?1:0;
	$collection_proc = trim($row[$block_cols["collection_proc"]]);
	$mnotes = trim($row[$block_cols["mnotes"]]);
	$depth = $row[$block_cols["depth"]];
	//////////////
	if ($_POST['wbody_type']=="L") { 
		if ($dup_next) {
			$dup = 1;
			$depth_npr = $prev_depth;
			$dup_next=false;
		}
		else if ($duplicate)
		{
			if ($depth == 0) {
				$dup_next=true;
				$dup = 0;
				$depth_npr = $depth;
			}
			else {
				$depth_npr=$prev_depth;
				$dup=1;
			}
		}
		else {
			$dup=0;
			$dup_next=false;
			$depth_npr=$depth;
		}
	}
	else {
		$dup=$duplicate;
		
	}
	////////////// 
	if ($collection_proc=="G") {$collection_proc="Grab Sample";}
//	print "$siteid - $mtime - $lab_id - $lab_sample_id - $duplicate- $collection_proc - $mnotes<br>\n";
	$meas_keys = array_keys($meas_cols);
	
	if ($_POST["new_$siteid"."_site_$i"]) {
		$siteid=$_POST["new_$siteid"."_site_$i"];
	}
	
	if (!in_array($siteid, $valid_sites))
	{
		print "<tr class='brow'> <td>".($i+1)."</td> <td>$mtime</td><td>$siteid</td><td colspan=\"3\">Invalid Site</td>
			<td>&nbsp;</td><td><input type='text' name='new_$siteid"."_site_$i' size='8'></td>
			<td><input type='checkbox' name='skip_row_$i' value='true'></td></tr>\n";
		$errors++;
	}	
	
	if ($_POST["new_$siteid"."_date_$i"]) {
		$mtime=	$_POST["new_$siteid"."_date_$i"];
	}
	$utime = strtotime($mtime);
	
	if ($row[$block_cols["date"]] && ($utime < strtotime("1967-01-01") || $utime > (time()+ 3900)))
	{
		print "<tr class='brow'> <td>".($i+1)."</td> <td>$mtime</td><td>$siteid</td><td colspan=\"3\">Invalid Date</td>
			<td>&nbsp</td><td><input type='text' name='new_$siteid"."_date_$i' size='8'></td>
			<td><input type='checkbox' name='skip_row_$i' value='true'></td></tr>\n";
		$errors++;
	}
	
	foreach($meas_keys as $j)
	{
		$mtypeid = $meas_cols[$j];
		$val = trim($row[$j]);
		if($_POST["skip_$siteid"."_$mtypeid"."_$i"]=="true")
		{
			$hidden_block .= "<input type='hidden' name='skip_$siteid"."_$mtypeid"."_$i' value='true'>\n";
			continue;
		}
		if ($mtypeid=='SD' && $depth>1) continue;
		if (strlen($val)>0)
		{
			if ($_POST[$mtypeid."_col_scale"] && is_numeric($_POST[$mtypeid."_col_scale"]))
			{
				$val = $val * $_POST[$mtypeid."_col_scale"];
			}

			$detlim=0;
			if (strpos($val,"<")!==FALSE) {
				$detlim=1;
				$val= preg_replace("/</", "", $val);
			}
			if (($lower[$mtypeid] && $val < $lower[$mtypeid]) || ($upper[$mtypeid] && $val > $upper[$mtypeid])) {
				if ($new_val=$_POST["new_$siteid"."_$mtypeid"."_$i"])
				{
					if (is_numeric($new_val) )
					{
						$val = $new_val;
						$hidden_block .= "<input type='hidden' name='new_$siteid"."_$mtypeid"."_$i' value='$val'>\n";
					}
				} 
				elseif($_POST["override_$siteid"."_$mtypeid"."_$i"]=="true"){
					$val=$val;
					$hidden_block .= "<input type='hidden' name='new_$siteid"."_$mtypeid"."_$i' value='$val'>\n";
				}
				else {
					print "<tr class='brow'> <td>".($i+1)."</td> <td>$mtime</td><td>$siteid</td><td>$mtypeid</td><td>$val</td><td>$lower[$mtypeid] - $upper[$mtypeid]</td>
						<td><input type='checkbox' name='override_$siteid"."_$mtypeid"."_$i' value='true'></td><td><input type='text' name='new_$siteid"."_$mtypeid"."_$i' size='5'></td>
						<td><input type='checkbox' name='skip_$siteid"."_$mtypeid"."_$i' value='true'></td></tr>\n";
					$errors++;
				}
			}
			$d = (in_array($mtypeid, $profile_types)) ? $depth:$depth_npr;
			$store_array[] = array($mtime,$val,$detlim,$d,$dup,$collection_proc,$lab_id,$lab_sample_id, $mnotes, $siteid, $mtypeid);		
			
		}
	}
	$prev_depth=$depth;
}
if ($errors == 0) 
{
	$stored_string="";
	$res = mysqli_query($mysqlid, "SET autocommit=0");
	$c=microtime(true);
	$inserts=0;
	$iquery = "INSERT INTO measurements (mtime,value,detection_limit,depth, duplicate, collection_proc, lab_id, lab_sample_id, mnotes, siteid, mtypeid)
		VALUES (?,?,?,?,?,?,?,?,?,?,?)";
	$stmt = mysqli_prepare($mysqlid, $iquery);
	foreach ($store_array as $row) {
		dynamic_mysqli_bind_param($stmt, 'sdidissssss', $row);
		if (!mysqli_stmt_execute($stmt)) {
			$stored_string .= "Error inserting $row[10] data taken on $row[0] at $row[9] with error:". mysqli_stmt_errno($stmt). mysqli_stmt_error($stmt)."<br>\n";
		}
		$inserts+= mysqli_stmt_affected_rows($stmt);
	}
	$res = mysqli_query($mysqlid, "COMMIT");
	$d=microtime(true);
	$ins_time=$d-$c;
	$qps=$inserts/$ins_time;
	$stored_string .= $inserts." data values stored.<br>\n";//rows added in $ins_time sec: $qps qps<br>\n";
	unlink($uploadfile);
}
else print $hidden_block;
$b=microtime(true);


?>
<tr class="brow"><td colspan=9><a href="#" onclick="overide_check(true);">Check all override</a> | <a href="#" onclick="overide_check(false);">Uncheck all override</a></td></tr>
<tr class="brow"><td colspan=9><input type="submit" Value="Store Data"></td></tr>
</table>
</form>
<script language="javascript">

function overide_check(value)
{
	frm = document.f1;
	for (i=1; i<=frm.elements.length; i=i+1)
	{
		if (frm.elements[i].type=="checkbox" && frm.elements[i].name.indexOf('verrid')>0)
		{
			frm.elements[i].checked = value;
		}
	}
}

function validate(form_sub)
{

	return true;
}
<?php 
if ($errors==0)
{
	print "document.getElementById('error_table').style.display='none';\n"; 
}
?>
</script>
<?php 
if ($errors==0)
{
	print $stored_string; 
}
else {
	print "Number of values to insert:". sizeof($store_array)."<br>\n";
	print "Flagged values: $errors <br>\nRuntime: ".($b-$a)."sec";
}
require_once 'includes/qp_footer.php';
?>