<?php
$page_title='Measurements Data Entry Form';
require_once 'includes/wqinc.php';
$pagelevel = PAGE_USER;

require_once 'includes/qp_header.php';
login_check($pagelevel, $log_user);

//get the default lab from the database
$default_lab_id = get_default_lab_id($mysqlid);

//set the form directives to false
$act_wbtype=false;
$act_wbody=false;
$act_site=false;
$act_input=false;
$act_edit=false;

//create empty arrays for insert and error fields
$inserts=array();
$edits=array();
$errors=array();
$delete_keys=array();
$hidden_block="";

if($_GET['faction']=="edit" && $_GET['siteid'] && $_GET['mtime'])
{
	$act_input=true;
	$act_site=true;
	$act_wbody=true;
	$act_wbtype=true;
	$act_edit=true;
	
	$wb_query = "SELECT w.waterbody_id, w.wbody_type FROM monitoring_sites m INNER JOIN waterbodies w ON w.waterbody_id=m.waterbody_id WHERE m.siteid=?";
	$wb_stmt = mysqli_prepare($mysqlid, $wb_query);
	mysqli_stmt_bind_param($wb_stmt, 's', $_GET['siteid']);
	mysqli_stmt_execute($wb_stmt);
	mysqli_stmt_bind_result($wb_stmt, $waterbody_id,$wbody_type);
	mysqli_stmt_fetch($wb_stmt);
	mysqli_stmt_close($wb_stmt);
	$_POST['waterbody_id']=$waterbody_id;
	$_POST['wbody_type']=$wbody_type;
	
	$edit_params= array($_GET['siteid'], $_GET['mtime']);
	if ($wbody_type != "S" && isset($_GET['depth']))
	{
		$wherecl = "AND depth=?";
		$edit_params[] =  $_GET['depth'];
		$ep_types = 'ssi';
	}
	else 
	{
		$ep_types = 'ss';
		$wherecl = "";
	}	
	$load_query = "select m_id, mtime,value,detection_limit,depth, duplicate, collection_proc, lab_id, lab_sample_id, mnotes, siteid, mtypeid 
		from measurements where siteid=? AND mtime=? $wherecl";
	$load_stmt = mysqli_prepare($mysqlid, $load_query);
	dynamic_mysqli_bind_param($load_stmt, $ep_types, $edit_params);
	mysqli_stmt_execute($load_stmt);
	mysqli_stmt_bind_result($load_stmt, $m_id, $mtime,$value,$detection_limit,$depth, $duplicate, $collection_proc, $lab_id, $lab_sample_id, $mnotes, $siteid, $mtypeid);
	
	while(mysqli_stmt_fetch($load_stmt)) {
		$_POST['depth']=$depth;
		list($_POST['sdate'],$_POST['stime'])=explode(" ", $mtime);
		$_POST['siteid']=$siteid;
		if ($duplicate) {
			$_POST['dup_'.$mtypeid]="true";
			$_POST['d_val_'.$mtypeid]=($detection_limit?"<":"").sprintf("%.5f",$value);
			$_POST['d_labid_'.$mtypeid]=$lab_id;
			$_POST['d_lab_s_id_'.$mtypeid]=$lab_sample_id;
			$_POST['d_proc_'.$mtypeid]=$collection_proc;
			$_POST['d_note_'.$mtypeid]=$mnotes;
			$_POST['d_mid_'.$mtypeid]=$m_id;			
			
		}
		else {
			$_POST['val_'.$mtypeid]=($detection_limit?"<":"").sprintf("%.5f",$value);
			$_POST['labid_'.$mtypeid]=$lab_id;
			$_POST['lab_s_id_'.$mtypeid]=$lab_sample_id;
			$_POST['proc_'.$mtypeid]=$collection_proc;
			$_POST['note_'.$mtypeid]=$mnotes;
			$_POST['mid_'.$mtypeid]=$m_id;			
		}

	}
	$errors["edit"]="true";
	
}

//set the form directives according to the faction post variable
//the later process actions (input...) also have the lesser directives
//also import some of the $_POST variables to the local space if needed
switch ($_POST['faction']) {
	case "input":
		$act_input=true;
	case "siteselect":
		$act_site=true;
		if ($_POST['siteid']) $siteid=$_POST['siteid'];
	case "wbody":
		$act_wbody=true;
		if ($_POST['waterbody_id']) $waterbody_id=$_POST['waterbody_id'];
	case "wbtype":
		$act_wbtype=true;
		if ($_POST['wbody_type']=="L") $wbody_type="L";
		if ($_POST['wbody_type']=="S") $wbody_type="S";
	default:
		break;
}


/**
 *  if input is set read in all data, check for errors and store
 */
if ($act_input)
{
	$waterbody_id = $_POST['waterbody_id'];
	$wbody_type = $_POST['wbody_type'];
	$siteid = $_POST['siteid'];
	if (preg_match ('`^([0-9]{1,2})/([0-9]{1,2})/([0-9]{4})$`', $_POST['sdate'], $parts))
	{
    	//check weather the date is valid of not checkdate($parts[2],$parts[3],$parts[1
    	$_POST['sdate'] = sprintf("%04d-%02d-%02d", $parts[3],$parts[1],$parts[2]);
	}
	if (!strpos($_POST['stime'], ":"))
	{
		$_POST['stime']=substr($_POST['stime'], 0,(strlen($_POST['stime'])-2)).":".substr($_POST['stime'],-2);
	}
	$mtime = $_POST['sdate']." ".$_POST['stime'];
	$depth = $_POST['depth'];
	if ($wbody_type=="L") {
		$check_col="lake";
		$col_string=", l_lower_bound, l_upper_bound";
	}
	elseif ($wbody_type=="S") {
		$check_col="stream";
		$col_string=", s_lower_bound, s_upper_bound";
	}
	else $check_type=0; // set query to return no rows if neither type (0=1)
	
	//parse for errors in information POSTed
	if(!$siteid){
		$errors["General"]="No Site Selected. ";
	} 
	if (($depth==="" || is_nan($depth) || $depth < 0) && $wbody_type=="L")
	{ 
		$errors["General"].="Please enter a valid depth. ";
	}
	$utime = strtotime($mtime);
	if (!$utime || $utime < strtotime("1967-01-01") || $utime > (time()+ 3900))
	{
		$errors["General"].="Please enter a valid day and time. ";
	}
	
	
// read in measurment types based on waterbody type 
	$typequery = "SELECT mtypeid $col_string FROM measurement_type WHERE $check_col=1 ORDER BY  l_profile DESC, mtypeid";
	$res = mysqli_query($mysqlid, $typequery);
	$meas_types=array();
	while ($mtrow = mysqli_fetch_array($res, MYSQLI_NUM))
	{
		$meas_types[]= array("mtypeid"=>$mtrow[0],"lower"=>$mtrow[1],"upper"=>$mtrow[2]);
	}
	
/** 
 * For each measurement type, check the _POST value to see if there was an entry for it
 * If so check the the value is within the bondaries for that measurement - enter a message into the error array with the typeid as key
 * Enter each element for each type into a multidimensional array, $inserts, with the mtypeid as the main key, and col names from measurements as second index
 */    
/*	$check_existing_query = "select m_id from measurements WHERE mtime=? AND depth=? AND duplicate=? and siteid=? AND mtypeid=?";
	$existing_stmt = mysqli_prepare($mysqlid, $check_existing_query);
	mysqli_stmt_bind_param($existing_stmt, "sdiss", $ce_mtime, $ce_depth,$ce_dup,$ce_siteid,$ce_mtypeid);	
	*/
	foreach ($meas_types as $measurement) {
		$val = $_POST["val_".$measurement["mtypeid"]];
		if (!isset($_POST["val_".$measurement["mtypeid"]]) || $val==="")
		{
			if ($_POST["mid_".$measurement["mtypeid"]]) $delete_keys[]=$_POST["mid_".$measurement["mtypeid"]];
			continue;// no value enter continue to next measurement type
		} 
		//see if the value begins with < - indicating below detection limit
		//set detlim flag and strip '<'
		$detlim=0;
		if (strpos($val,"<")!==FALSE) {
			$detlim=1;
			$val= preg_replace("/</", "", $val);
		}
		
		
		// verify that each value is with the appropriate bounds
		if (is_numeric($val)) {
/*			$ce_time=$measurement['mtime'];
			$ce_depth=$measurement['depth'];
			$ce_siteid=$measurement['steid'];
			$ce_mtypeid=$measurement['mtyepid'];
			$ce_dup=$measurement['duplicate'];
			mysqli_stmt_execute($stmt);	
			if (mysqli_stmt_num_rows($existing_stmt)) {}
*/
			if ((!is_null($measurement["lower"]) && $val < $measurement["lower"]) || (!is_null($measurement["upper"]) && $val > $measurement["upper"]))
			{
				if ($_POST["override_".$measurement["mtypeid"]]!= "true")
					$errors[$measurement["mtypeid"]]="Value must be between ".$measurement["lower"]." and ".$measurement["upper"]; 
				else $hidden_block .= "<input type='hidden' name='override_".$measurement["mtypeid"]."' value='true'>\n"; 
			}
		}
		else {
			$errors[$measurement["mtypeid"]]="Value is not a number";
		}
		
		$inserts[$measurement["mtypeid"]]=array("mtypeid"=>$measurement["mtypeid"],"value"=>$val, "detection_limit"=>$detlim, 
			"duplicate"=>0, "collection_proc"=>$_POST["proc_".$measurement["mtypeid"]], 
			"lab_id"=>$_POST["labid_".$measurement["mtypeid"]], "lab_sample_id"=>$_POST["lab_s_id_".$measurement["mtypeid"]],
			"mnotes"=>$_POST["note_".$measurement["mtypeid"]], "m_id"=>$_POST["mid_".$measurement["mtypeid"]]);
		if ($_POST["dup_".$measurement["mtypeid"]]=="true")
		{
			$val = $_POST["d_val_".$measurement["mtypeid"]];
			if (!isset($_POST["d_val_".$measurement["mtypeid"]]) || $val==="") {continue;} // no value enter continue to next measurement type
			//see if the value begins with < - indicating below detection limit
			//set detlim flag and strip '<'
			$detlim=0;
			if (strpos($val,"<")!==FALSE) {
				$detlim=1;
				$val= preg_replace("/</", "", $val);
			}
			// verify that each value is with the appropriate bounds
			if (is_numeric($val)) {
				if ((!is_null($measurement["lower"]) && $val < $measurement["lower"]) || (!is_null($measurement["upper"]) && $val > $measurement["upper"]))
				{
					if ($_POST["override_d_".$measurement["mtypeid"]]!= "true")
						$errors[$measurement["mtypeid"]."_D"]="Value must be between ".$measurement["lower"]." and ".$measurement["upper"]; 
					else $hidden_block .= "<input type='hidden' name='override_d_".$measurement["mtypeid"]."' value='true'>\n"; 
				}
			}
			else {
				$errors[$measurement["mtypeid"]."_D"]="Value is not a number";
			}
			
			$inserts[$measurement["mtypeid"]."_D"]=array("mtypeid"=>$measurement["mtypeid"],"value"=>$val, "detection_limit"=>$detlim, 
				"duplicate"=>1, "collection_proc"=>$_POST["d_proc_".$measurement["mtypeid"]], 
				"lab_id"=>$_POST["d_labid_".$measurement["mtypeid"]], "lab_sample_id"=>$_POST["d_lab_s_id_".$measurement["mtypeid"]],
				"mnotes"=>$_POST["d_note_".$measurement["mtypeid"]], "m_id"=>$_POST["d_mid_".$measurement["mtypeid"]]);
			
		}
	}
	
// if no entries in errors write the data to the measurements table 
	if (sizeof($errors)==0) {
		$ins=$eds=$dels=0;
		$iquery = "INSERT INTO measurements (mtime,value,detection_limit,depth, duplicate, collection_proc, lab_id, lab_sample_id, mnotes, siteid, mtypeid)
			VALUES (?,?,?,?,?,?,?,?,?,?,?)";
		$stmt = mysqli_prepare($mysqlid, $iquery);
		foreach ($inserts as $key =>$meas) {
			if ($meas["m_id"]) {
				$edits[$key] = $meas;
				continue;
			}
			mysqli_stmt_bind_param($stmt, 'sdidissssss', $mtime,$meas["value"],$meas["detection_limit"],$depth,$meas["duplicate"],
				$meas["collection_proc"], $meas["lab_id"], $meas["lab_sample_id"], $meas["mnotes"], $siteid, $meas["mtypeid"]);	
			if (!mysqli_stmt_execute($stmt)) {
				print "error ". mysqli_stmt_errno($stmt). mysqli_stmt_error($stmt);
			}
			else $ins++;
		}
		if (sizeof($edits)>0){
			$iquery = "UPDATE measurements SET mtime=?,value=?,detection_limit=?,depth=?, duplicate=?, collection_proc=?, lab_id=?, lab_sample_id=?, mnotes=?, siteid=?, mtypeid=?
				WHERE m_id=?";
			$stmt = mysqli_prepare($mysqlid, $iquery);
			foreach ($edits as $key =>$meas) {
				mysqli_stmt_bind_param($stmt, 'sdidissssssi', $mtime,$meas["value"],$meas["detection_limit"],$depth,$meas["duplicate"],
					$meas["collection_proc"], $meas["lab_id"], $meas["lab_sample_id"], $meas["mnotes"], $siteid, $meas["mtypeid"], $meas["m_id"]);	
				if (!mysqli_stmt_execute($stmt)) {
					print "error ". mysqli_stmt_errno($stmt). mysqli_stmt_error($stmt);
				}
				else $eds++;
			}
		}	
		if (sizeof($delete_keys)>0){
			$iquery = "DELETE from measurements WHERE m_id=?";
			$stmt = mysqli_prepare($mysqlid, $iquery);
			foreach ($delete_keys as $dkey) {
				mysqli_stmt_bind_param($stmt, 'i',$dkey);	
				if (!mysqli_stmt_execute($stmt)) {
					print "error ". mysqli_stmt_errno($stmt). mysqli_stmt_error($stmt);
				}
				else $dels++;
			}
		}	
		print ($ins?"$ins data values inserted":"").($ins&&$eds?" and ":" ").($eds?"$eds data values updated":""). " at ".$_POST['siteid'];
		$inserts=array();
		if ($eds==0)
		{
			if ($wbody_type=="L") {
				$depth++;
			}
			if ($wbody_type=="S") {
				$siteid=NULL;
				$_POST['stime']='';
			}
			
		}
		//$depth=null;
	}
	
}


?>

<form action="add_measurement.php" method="POST" name="f1" id="f1" onsubmit="">
<input type="hidden" name="faction" value="">
<?php 
if ($act_edit)
{
	print "<input type=\"hidden\" name=\"edit\" value=\"true\">\n";
}
print $hidden_block;
?>
<table class="formtable">
<tr><th colspan=2>Water Quality Measurements Data Entry</th></tr>
<?php 
if ($errors["General"])
{
	print "<tr><td>&nbsp;</td><td class='inputerror'>".$errors["General"]."</td></tr>\n";
}?>
<tr><td>Waterbody Type</td><td><input type="radio" name="wbody_type" value="L" onchange="document.f1.faction.value='wbtype';document.f1.submit();" onclick="this.blur();" <?php if ($wbody_type==="L")print " checked"?>>Lake &nbsp;
	 <input type="radio" name="wbody_type" value="S" onchange="document.f1.faction.value='wbtype';document.f1.submit();" onclick="this.blur();" <?php if ($wbody_type==="S")print " checked"?>>Stream</td></tr>
<tr><td>Waterbody</td><td><select name="waterbody_id" onchange="document.f1.faction.value='wbody';document.f1.submit();">
<?php 
if ($act_wbtype) {
	print "<option value=''>Select a waterbody</option>\n";
	$query = "SELECT * FROM waterbodies WHERE wbody_type = '$wbody_type' ORDER BY wbody_name ASC";
	$res = mysqli_query($mysqlid, $query);
	while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC))
	{
		print '<option value="'.$row['waterbody_id'].'"'.($row['waterbody_id']==$waterbody_id?" selected":"") .'>'.$row['wbody_name']. "</option>\n";
	}
	mysqli_free_result($res);
}
else print "<option value=''>Select a waterbody type</option>\n";
?>
</select></td></tr>	
<tr><td>Monitoring site</td><td><select name="siteid" style="width:300">
<?php 
if ($act_wbody) {
	print "<option value=''>Select a site</option>\n";
	$query = "SELECT siteid,site_description FROM sites_list WHERE waterbody_id=? ORDER BY active aSC, site_description asc, siteid";//$query = "SELECT siteid,site_description FROM sites_list WHERE waterbody_id=? ORDER BY Active DESC, siteid";
	$stmt = mysqli_prepare($mysqlid, $query); 
	if($stmt==false) {printf("Errormessage: %s\n", mysqli_error($mysqlid));exit;}
	mysqli_stmt_bind_param($stmt, "i", $waterbody_id);	
	mysqli_stmt_execute($stmt);
	mysqli_stmt_bind_result($stmt, $listsiteid,$site_description);
	while(mysqli_stmt_fetch($stmt)) {
		$sd = substr($site_description,0,15);
		print "<option value='$listsiteid'". ($listsiteid==$siteid?" selected":"") . ">$sd - $listsiteid</option>\n";		
	}
	mysqli_stmt_close($stmt);

}
else if ($act_wbtype) print "<option value=''>Select a waterbody</option>\n";
else print "<option value=''>Select a waterbody type</option>\n";
?>
</select></td></tr>	
<tr><td>Measurement Date</td><td><input type="text" name="sdate" size="15" class="calendarSelectDate" value="<?php print$_POST['sdate'];?>"></td></tr>
<tr><td>Measurement Time (military)</td><td><input type="text" name="stime" size="15" class="" value="<?php print$_POST['stime'];?>"></td></tr>
<tr><td>Depth</td><td><input name="depth" type="text" style="width:60px" maxlength="7" <?php print "value='$depth'"; if ($wbody_type=="S") print " readonly";?>></td></tr>
<?php 
if ($act_wbody)
{
	print "<tr><td colspan=2><a name=\"entrytop\" />\n<table class='entrytable' id='t2' >\n ";
	if ($wbody_type=="L") {
		$check_col="lake";
		$col_string=", l_collection_method, l_lower_bound, l_upper_bound";
	}
	elseif ($wbody_type=="S") {
		$check_col="stream";
		$col_string=", s_collection_method, s_lower_bound, s_upper_bound";
	}
	else $check_col=0; // set query to return no rows if neither type (0=1)
	$typequery = "SELECT mtypeid, mtname, units $col_string,  active FROM measurement_type WHERE $check_col=1 ORDER BY  active desc,disp_order ASC, l_profile DESC, mtypeid";
	$res = mysqli_query($mysqlid, $typequery);
	$inactive_rows = array();
	while ($mtrow = mysqli_fetch_array($res, MYSQLI_NUM))
	{
		$disp='';
		$mtypeid=$mtrow[0];
		if (key_exists($mtypeid, $inserts)){
			$entry = $inserts[$mtypeid];
		}
		else {
			$entry=array();
			if ($mtrow[6] == 0) {
				$disp='display:none';
				$inactive_rows[]="$mtypeid"."_1";
				$inactive_rows[]="$mtypeid"."_2";
			}
		}
		if (key_exists($mtypeid."_D", $inserts)){
			$d_entry = $inserts[$mtypeid."_D"];
			$dup_disp = "";
		}
		else {
			$d_entry=array();
			$dup_disp = "display:none";
		}
		if (key_exists($mtypeid,$errors)) 
		{
			$error = $errors[$mtypeid];
		}
		else $error=NULL;
		if ($error) {
			print " <tr id='$mtypeid"."_error'>\n  <td class='tdright'>&nbsp</td><td class=\"inputerror\" colspan=3>$error</td><td><input type='checkbox' name='override_$mtypeid' value='true'> Override</td></tr>\n";
		}

		print " <tr id='$mtypeid"."_1' style='$disp'>\n  <td class='tdright'>".$mtrow[0]."</td>\n";
		print "  <td width='80px'>Value:</td><td width='100px'><input name=\"val_$mtypeid\" type=\"text\" style=\"width:60px\" maxlength=\"8\" value=\"".((key_exists("detection_limit",$entry)&&$entry["detection_limit"])?"<":"").(key_exists("value",$entry)?$entry['value']:"")."\"><span class='units'>".$mtrow[2]."</span></td>\n";
		print "  <td><input type='checkbox' name='dup_$mtypeid' value='true' ".($d_entry["duplicate"]==1?"checked":"")." onchange=\"show_dup(this.checked,'$mtypeid' )\" > Dup</td>\n";
		print "  <td>Notes: <input name=\"note_$mtypeid\" type=\"text\" style=\"width:338px\" value=\"".(key_exists("mnotes",$entry)?$entry['mnotes']:"")."\"></td>\n";
		print " </tr>\n";
		print " <tr id='$mtypeid"."_2' style='$disp' class='brow'>\n  <td class='tdright'>&nbsp;</td>\n";
		print "  <td width='80px'>Procedure:</td><td colspan=3> <input name=\"proc_$mtypeid\" type=\"text\" style=\"width:280px\" value='".(key_exists("collection_proc",$entry)?$entry['collection_proc']:$mtrow[3])."'>\n";
		print "  Lab: <input name=\"labid_$mtypeid\" type=\"text\" style=\"width:60px\" maxlength='8' value='".(key_exists("lab_id",$entry)?$entry['lab_id']:$default_lab_id)."'>\n";
		print "  Lab Sample ID: <input name=\"lab_s_id_$mtypeid\" type=\"text\" style=\"width:60px\" value=\"".(key_exists("lab_sample_id",$entry)?$entry['lab_sample_id']:"")."\"></td>\n";	
		print " </tr>\n";
		if ($act_edit)
		{
			print "<input type=\"hidden\" name=\"mid_$mtypeid\" value=\"".(key_exists("m_id",$entry)?$entry['m_id']:"")."\">\n";
		}
		if (key_exists($mtypeid."_D",$errors)) 
		{
			$error = $errors[$mtypeid."_D"];
		}
		else $error=NULL;
		if ($error) {
			print " <tr id='$mtypeid"."_error_d'>\n  <td class='tdright'>&nbsp</td><td class=\"inputerror\" colspan=3>$error</td><td><input type='checkbox' name='override_d_$mtypeid' value='true'> Override</td></tr>\n";
		}		
		print " <tr id='$mtypeid"."_3' style='$dup_disp'>\n  <td class='tdright'>$mtypeid</td>\n";
		print "  <td width='80px'>Value:</td><td width='100px'><input name=\"d_val_$mtypeid\" type=\"text\" style=\"width:60px\" maxlength=\"8\" value=\"".((key_exists("detection_limit",$d_entry)&&$d_entry["detection_limit"])?"<":"").(key_exists("value",$d_entry)?$d_entry['value']:"")."\"><span class='units'>".$mtrow[2]."</span></td>\n";
		print "  <td>&nbsp;</td>\n";
		print "  <td>Notes: <input name=\"d_note_$mtypeid\" type=\"text\" style=\"width:338px\" value=\"".(key_exists("mnotes",$d_entry)?$d_entry['mnotes']:"")."\"></td>\n";
		print " </tr>\n";
		print " <tr id='$mtypeid"."_4' style='$dup_disp' class='brow'>\n  <td class='tdright'><span class='units'>(dup)</span></td>\n";
		print "  <td width='80px'>Procedure:</td><td colspan=3> <input name=\"d_proc_$mtypeid\" type=\"text\" style=\"width:280px\" value='".(key_exists("collection_proc",$d_entry)?$d_entry['collection_proc']:$mtrow[3])."'>\n";
		print "  Lab: <input name=\"d_labid_$mtypeid\" type=\"text\" style=\"width:60px\" maxlength='8' value='".(key_exists("lab_id",$d_entry)?$d_entry['lab_id']:$default_lab_id)."'>\n";
		print "  Lab Sample ID: <input name=\"d_lab_s_id_$mtypeid\" type=\"text\" style=\"width:60px\" value=\"".(key_exists("lab_sample_id",$d_entry)?$d_entry['lab_sample_id']:"")."\"></td>\n";	
		print " </tr>\n";
		if ($act_edit && $d_entry['m_id'])
		{
			print "<input type=\"hidden\" name=\"d_mid_$mtypeid\" value=\"".(key_exists("m_id",$d_entry)?$d_entry['m_id']:"")."\">\n";
		}

	}


mysqli_free_result($res);
print "</table>";
print "<a href=\"#entrytop\" onclick=\"show_inactive();\" id=\"showlink\">Show All Measurement Types</a>";
print "<a href=\"#entrytop\" onclick=\"hide_inactive();\" id=\"hidelink\" style=\"display:none\">Hide Inactive Measurement Types</a>";
print "</td></tr>\n";
}
?>
</table>
<input type="button" name="" value="Save" onclick="document.f1.faction.value='input';fsubmit(document.f1);">
</form>
<div id="calendarDiv"></div>


<script language="javascript">
function fsubmit(frm)
{
	if (!(frm.siteid.selectedIndex > 0))
	{
		alert("Please enter the monitoring site.");
		return false;
	}
	if (frm.sdate.value.length < 7)
	{
		alert("Please enter the sample date.");
		return false;
	}
	if (frm.stime.value.length < 4) 
	{
		alert("Please enter the sample time.")
		return false;
	}
	if (frm.wbody_type[0].checked && (isNaN(frm.depth.value) || frm.depth.value.length==0 || frm.depth.value<0))
	{
		alert("Please enter the sample depth.");
		return false;
	}
	frm.submit();
}

function show_inactive()
{
	rows = new Array("<?php print implode('","', $inactive_rows);?>");
	for (i=0; i<rows.length; i=i+1)
	{
		document.getElementById(rows[i]).style.display="";
	}
	document.getElementById('hidelink').style.display="";
	document.getElementById('showlink').style.display="none";
}

function hide_inactive()
{
	rows = new Array("<?php print implode('","', $inactive_rows);?>");
	for (i=0; i<rows.length; i=i+1)
	{
		document.getElementById(rows[i]).style.display="none";
	}
	document.getElementById('hidelink').style.display="none";
	document.getElementById('showlink').style.display="";
}

function show_dup(tf,row)
{
	if (tf) 
	{
		document.getElementById(row+'_3').style.display='';
		document.getElementById(row+'_4').style.display='';
	}
	else 
	{
		document.getElementById(row+'_3').style.display='none';
		document.getElementById(row+'_4').style.display='none';
	}

}

</script>

<?php 
require_once 'includes/qp_footer.php';
?>