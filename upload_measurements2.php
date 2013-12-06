<?php
$page_title='Measurements Upload Form';
require_once 'includes/wqinc.php';
$pagelevel = PAGE_USER;

require_once 'includes/qp_header.php';

login_check($pagelevel, $log_user);

$uploaddir = 'up_data/';
$uploadfilename=basename($_FILES['datafile']['name']);
$uploadfile = $uploaddir . $uploadfilename;

if (move_uploaded_file($_FILES['datafile']['tmp_name'], $uploadfile)) {
    print "File successfully uploaded.\n";
} else {
	$dir = getcwd();
	print $_FILES['datafile']['tmp_name']."\n$dir\n";
	$dd = "$dir/".$uploaddir;
	print $dd . (is_writable($dd)?"writable":"locked")."<br>\n";
    print "The file did not upload correctly. Please verify that you selected a file to upload. Very large files (>2 MB) may need to be split up.\n";
    require_once 'includes/qp_footer.php';
    exit;
}
print "<pre>";
$sites = file($uploadfile);
$header_row=$_POST['header_row']-1; //correct for zero index
$header_rows=$_POST['first_data_row']-1;
$delimiter=$_POST['delimiter'];
if ($delimiter=='\t') $delimiter="\t";
$encaps=$_POST['encaps'];

print "parsing data in ".basename($uploadfile)."\n";
print "found ".(sizeof($sites))." lines of data\n";
print $sites[$header_row]."\n";
print $sites[$header_rows]."\n";
$headers=str_getcsv($sites[$header_row],$delimiter,$encaps);
$select_opts="<option value='-1'>Skip</option>\n";
for ($i=0; $i < sizeof($headers); $i++) {
	$select_opts.="<option value='$i'>".$headers[$i]."</option>\n";
}



$block_cols["projid"]=1;
$block_cols["date"]=4;
$block_cols["time"]=5;
$block_cols["depth"]=7;
$block_cols["duplicate"]=8;
$block_cols["collection_proc"]=9;
$block_cols["lab_id"]=11;
$block_cols["lab_sample_id"]=12;
$block_cols["mnotes"]=13;
?>
</pre>
<form action="upload_measurements3.php" method="POST" name="f1" id="f1" onsubmit=""  enctype="multipart/form-data">
<input type="hidden" name="report_type" value="raw">
<input type="hidden" name="datafile" value="<?php print basename($uploadfile);?>">
<input type="hidden" name="wbody_type" value="<?php print $_POST['wbody_type'];?>">
<input type="hidden" name="header_row" value="<?php print $_POST['header_row'];?>">
<input type="hidden" name="first_data_row" value="<?php print $_POST['first_data_row'];?>">
<input type="hidden" name="delimiter" value="<?php print $_POST['delimiter'];?>">
<input type="hidden" name="encaps" value='<?php print $_POST['encaps'];?>'>
<table class="formtable">
	<tr><th colspan=6>Water Quality Measurements Data Upload</th></tr>
	<tr><td colspan=6>Match fields with the associated columns in the uploaded file</td></tr>
	<tr><td>MCWD Site ID</td><td><select name="siteid_col"><?php print $select_opts;?></select></td><td colspan=3 class="notes">The MCWD monitoring site ID*</td></tr>
	<tr><td>Project Station ID</td><td><select name="proj_stat_id_col"><?php print $select_opts;?></select></td><td colspan=3 class="notes">Project Station Name*</td></tr>
	<tr><td>Date</td><td><select name="date_col"><?php print $select_opts;?></select></td><td colspan=3 class="notes">Column with date in format mm/dd/yyyy</td></tr>
	<tr><td>Time</td><td><select name="time_col"><?php print $select_opts;?></select></td><td colspan=3 class="notes">Military time</td></tr>
	<tr><td>Depth</td><td><select name="depth_col"><?php print $select_opts;?></select></td><td colspan=3 class="notes">Depth (Skip for streams)</td></tr>
	<tr><td>Duplicate</td><td><select name="dup_col"><?php print $select_opts;?></select></td><td colspan=3 class="notes">Column indicating Duplicate - should contain "dup" somewhere in field</td></tr>
	<tr><td>Collection Proc.</td><td><select name="coll_proc_col"><?php print $select_opts;?></select></td><td colspan=3 class="notes"></td></tr>
	<tr><td>Lab ID</td><td><select name="lab_id_col"><?php print $select_opts;?></select></td><td colspan=3 class="notes"></td></tr>
	<tr><td>Lab Sample ID</td><td><select name="lab_sample_id_col"><?php print $select_opts;?></select></td><td colspan=3 class="notes"></td></tr>
	<tr><td>Notes</td><td><select name="notes_col"><?php print $select_opts;?></select></td><td colspan=3 class="notes"></td></tr>	
	<tr><td><b>Measurement</b></td><td></td><td class="notes">units</td><td class="notes">Scale*</td><td class="notes">Measurement Details</td></tr>
<?php 
	if ($_POST['wbody_type']=="L") {
		$check_col="lake";
	}
	elseif ($_POST['wbody_type']=="S") {
		$check_col="stream";
	}
	else $check_type=0; // set query to return no rows if neither type (0=1)
	$typequery = "SELECT mtypeid, mtname, units FROM measurement_type WHERE $check_col=1 ORDER BY  l_profile DESC, mtypeid";
	$res = mysqli_query($mysqlid, $typequery);

	while ($mtrow = mysqli_fetch_array($res, MYSQLI_NUM))
	{
		print "	<tr><td>$mtrow[0]</td><td><select name=\"".$mtrow[0]."_col\">$select_opts</select></td><td class=\"notes\">$mtrow[2]</td><td><input type='text' name='".$mtrow[0]."_col_scale' style='width:40px'></td><td class=\"notes\">$mtrow[1]</td></tr>\n";	
	}
?>	
	<tr><td colspan=5 class="notes">*Scale: If data in file is not in the same units as expected by the system, enter a scaling factor in this field, if left blank scale will be one</tr>	
	<tr><td></td><td><button type="button" onclick="fsubmit(document.getElementById('f1'));">Submit</button></td></tr>
	
</table>
</form>
<script language="javascript">
function fsubmit(form_sub)
{
	if (form_sub.siteid_col.selectedIndex < 1 && form_sub.proj_stat_id_col.selectedIndex < 1 ){
		alert("Please select a column with the MCWD Site ID or Project Station ID.");
	}
	else if (form_sub.date_col.selectedIndex < 1 || form_sub.time_col.selectedIndex < 1 ){
		alert("Please select the columns with the date and military time.");
	}
	else form_sub.submit();
	

}
</script>
<?php 
require_once 'includes/qp_footer.php';
?>