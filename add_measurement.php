<?php
$page_title='Measurements Data Entry Form';
$jquery=true;
require_once 'includes/wqinc.php';
$pagelevel = PAGE_USER;

require_once 'includes/qp_header.php';
login_check($pagelevel, $log_user);

//get the default lab from the database
$default_lab_id = get_default_lab_id($mysqlid);
$default_project = get_general_value_table("def_project", $mysqlid);

//set the form directives to false
$act_wbtype =false;
$act_wbody  =false;
$act_site   =false;
$act_input  =false;
$act_edit   =false;

//create empty arrays for insert and error fields
$inserts        =array();
$edits          =array();
$errors         =array();
$delete_keys    =array();
$hidden_block   ="";

//Avoid undefined index errors/warnings using isset() for all post/get variables.
$editReturnGet  = isset($_GET['edit_return']) ? $_GET['edit_return'] : null;
$editReturnPost = isset($_POST['edit_return']) ? $_POST['edit_return'] : null;

if($editReturnGet)  {
    $edit_return = ($editReturnGet);
}elseif ($editReturnPost) {
    $edit_return = ($editReturnPost);
}else {
    $edit_return = null;
};

$factionGet     = isset($_GET['faction']) ? $_GET['faction'] : null;
$factionPost    = isset($_POST['faction']) ? $_POST['faction'] : null;
$editPost       = isset($_POST['edit']) ? $_POST['edit'] : null;
$siteidGet      = isset($_GET['siteid']) ? $_GET['siteid'] : null;
$mtimeGet       = isset($_GET['mtime']) ? $_GET['mtime'] : null;
$collectByPost  = isset($_POST['collected_by']) ? $_POST['collected_by'] : null;
$sdatePost      = isset($_POST['sdate']) ? $_POST['sdate'] : null;

$wbody_type     = null;

if($edit_return) {$hidden_block.="<input type='hidden' name='edit_return' value='$edit_return'>\n";}

if($factionGet=="edit" && $siteidGet && $mtimeGet)
{
    $act_input  =true;
    $act_site   =true;
    $act_wbody  =true;
    $act_wbtype =true;
    $act_edit   =true;
    
    $wb_query = "SELECT w.waterbody_id, w.wbody_type FROM monitoring_sites m INNER JOIN waterbodies w ON w.waterbody_id=m.waterbody_id WHERE m.siteid=?";
    $wb_stmt = mysqli_prepare($mysqlid, $wb_query);
    
    mysqli_stmt_bind_param($wb_stmt, 's', $siteidGet);
    mysqli_stmt_execute($wb_stmt);
    mysqli_stmt_bind_result($wb_stmt, $waterbody_id,$wbody_type);
    mysqli_stmt_fetch($wb_stmt);
    mysqli_stmt_close($wb_stmt);
    
    $_POST['waterbody_id']  = $waterbody_id;
    $_POST['wbody_type']    = $wbody_type;
    $_POST['siteid']        = isset($siteid) ? $siteid : $siteidGet ;
    
    if (!strpos($mtimeGet, ":"))
    {
        $mtimeGet=substr($mtimeGet, 0,(strlen($mtimeGet)-2)).":".substr($mtimeGet,-2);
    }
    
    //print $_GET['stime'];
    $edit_params= array($siteidGet, $mtimeGet);
    if ($wbody_type != "S" && isset($_GET['depth']))
    {
        $wherecl = "AND (ABS(depth-?) < .001)"; // floating point correction
        $edit_params[] =  round($_GET['depth'],6);
        $ep_types = 'ssd';
    }
    else 
    {
        $ep_types = 'ss';
        $wherecl = "";
    }    
    $load_query = "select m_id, mtime,value,detection_limit,depth, duplicate, collection_proc, lab_id, lab_sample_id, mnotes, siteid, mtypeid, gear_id, proj_id, proc_id, collected_by 
        from measurements where siteid=? AND mtime=? $wherecl";
    $load_stmt = mysqli_prepare($mysqlid, $load_query);
    dynamic_mysqli_bind_param($load_stmt, $ep_types, $edit_params);
    mysqli_stmt_execute($load_stmt);
    mysqli_stmt_bind_result($load_stmt, $m_id, $mtime,$value,$detection_limit,$depth, $duplicate, $collection_proc, $lab_id, $lab_sample_id, $mnotes, $siteid, $mtypeid, $gear_id, $proj_id, $proc_id, $collected_by);
    
    while(mysqli_stmt_fetch($load_stmt)) {
        $_POST['depth']         = round($depth,6);
        list($sdatePost,$_POST['stime'])=explode(" ", $mtime);
        $_POST['siteid']        = $siteid;
        $_POST['proc_id']       = $proc_id;
        $_POST['collected_by']  = $collected_by;
        if ($duplicate) {
            $_POST['dup_'.$mtypeid]         = "true";
            $_POST['d_val_'.$mtypeid]       = ($detection_limit?"<":"").sprintf("%.5f",$value);
            $_POST['d_labid_'.$mtypeid]     = $lab_id;
            $_POST['d_lab_s_id_'.$mtypeid]  = $lab_sample_id;
            $_POST['d_proc_'.$mtypeid]      = $collection_proc;
            $_POST['d_note_'.$mtypeid]      = $mnotes;
            $_POST['d_gear_'.$mtypeid]      = $gear_id;
            $_POST['d_proj_'.$mtypeid]      = $proj_id;
            $_POST['d_mid_'.$mtypeid]       = $m_id;
            
        }
        else {
            $_POST['val_'.$mtypeid]         = ($detection_limit?"<":"").sprintf("%.5f",$value);
            $_POST['labid_'.$mtypeid]       = $lab_id;
            $_POST['lab_s_id_'.$mtypeid]    = $lab_sample_id;
            $_POST['proc_'.$mtypeid]        = $collection_proc;
            $_POST['note_'.$mtypeid]        = $mnotes;
            $_POST['gear_'.$mtypeid]        = $gear_id;
            $_POST['proj_'.$mtypeid]        = $proj_id;
            $_POST['mid_'.$mtypeid]         = $m_id;
        }

    }
    $errors["edit"]="true";
    
}

//set the form directives according to the faction post variable
//the later process actions (input...) also have the lesser directives
//also import some of the $_POST variables to the local space if needed
switch ($factionPost) {
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
if ($editPost=="true")
{
    $act_edit=true;
}

/**
 *  if input is set read in all data, check for errors and store
 */
if ($act_input)
{
    $waterbody_id = $_POST['waterbody_id'];
    $wbody_type = $_POST['wbody_type'];
    $siteid = $_POST['siteid'];
    if (preg_match ('`^([0-9]{1,2})/([0-9]{1,2})/([0-9]{4})$`', $sdatePost, $parts))
    {
        //check whether the date is valid of not checkdate($parts[2],$parts[3],$parts[1
        $sdatePost = sprintf("%04d-%02d-%02d", $parts[3],$parts[1],$parts[2]);
    }
    if (!strpos($_POST['stime'], ":"))
    {
        $_POST['stime'] = substr($_POST['stime'], 0,(strlen($_POST['stime'])-2)).":".substr($_POST['stime'],-2);
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
    $proc_id=$_POST['proc_id']?$_POST['proc_id']:NULL;
    $collected_by=$_POST['collected_by'];
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
    $typequery = "SELECT mtypeid $col_string FROM measurement_type WHERE $check_col=1 ORDER BY  mtypeid";
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
    foreach ($meas_types as $measurement) {
        $val = isset($_POST["val_".$measurement["mtypeid"]]) ? $_POST["val_".$measurement["mtypeid"]] : "";

        $procMeasPost   = isset($_POST["proc_".$measurement["mtypeid"]]) ? $_POST["proc_".$measurement["mtypeid"]] : null;
        $midMeasPost    = isset($_POST["mid_".$measurement["mtypeid"]]) ? $_POST["mid_".$measurement["mtypeid"]] : null;
        $dupMeasPost    = isset($_POST["dup_".$measurement["mtypeid"]]) ? $_POST["dup_".$measurement["mtypeid"]] : null;

        if ($val==="")
        {
            if ($midMeasPost) $delete_keys[]=$_POST["mid_".$measurement["mtypeid"]];
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
        if (!$_POST["gear_".$measurement["mtypeid"]]) { $_POST["gear_".$measurement["mtypeid"]] = NULL; }
        if (!$_POST["proj_".$measurement["mtypeid"]]) { $_POST["proj_".$measurement["mtypeid"]] = NULL; }
        

        
        $inserts[$measurement["mtypeid"]]=array("mtypeid"=>$measurement["mtypeid"],"value"=>$val, "detection_limit"=>$detlim, 
            "duplicate"=>0, "collection_proc"=>$procMeasPost, "lab_id"=>$_POST["labid_".$measurement["mtypeid"]], 
            "lab_sample_id"=>$_POST["lab_s_id_".$measurement["mtypeid"]], "mnotes"=>$_POST["note_".$measurement["mtypeid"]], 
            "m_id"=>$midMeasPost, 
            "gear_id"=>($_POST["gear_".$measurement["mtypeid"]] ? $_POST["gear_".$measurement["mtypeid"]] : NULL), 
            "proj_id"=>($_POST["proj_".$measurement["mtypeid"]] ? $_POST["proj_".$measurement["mtypeid"]] : NULL) 
        );
        if ($dupMeasPost=="true")
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
                "mnotes"=>$_POST["d_note_".$measurement["mtypeid"]], "m_id"=>$_POST["d_mid_".$measurement["mtypeid"]], 
                "proj_id"=>($_POST["d_proj_".$measurement["mtypeid"]]?$_POST["d_proj_".$measurement["mtypeid"]]:NULL), 
                "gear_id"=>($_POST["d_gear_".$measurement["mtypeid"]]?$_POST["d_gear_".$measurement["mtypeid"]]:NULL)
            );
            
        }
    }
    
// if no entries in errors write the data to the measurements table 
    if (sizeof($errors)==0) {
        $ins=$eds=$dels=0;
        $iquery = "INSERT INTO measurements (mtime,value,detection_limit,depth, duplicate, collection_proc, lab_id, lab_sample_id, mnotes, 
                siteid, mtypeid, gear_id, proj_id, proc_id, collected_by, user_entry)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $stmt = mysqli_prepare($mysqlid, $iquery);
        $uid = $log_user->get_userID();
        foreach ($inserts as $key =>$meas) { 
            if ($meas["m_id"]) {
                $edits[$key] = $meas;
                continue; // if a key exists, add to edits list and skip insert
            }
            mysqli_stmt_bind_param($stmt, 'sdidissssssssssi', $mtime,$meas["value"],$meas["detection_limit"],$depth,
                $meas["duplicate"], $meas["collection_proc"], $meas["lab_id"], $meas["lab_sample_id"], $meas["mnotes"], $siteid, 
                $meas["mtypeid"], $meas["gear_id"], $meas["proj_id"],$proc_id, $collected_by, $uid);    
            if (!mysqli_stmt_execute($stmt)) {
                print "error ". mysqli_stmt_errno($stmt). mysqli_stmt_error($stmt);
            }
            else $ins++;
        }
        if (sizeof($edits)>0){
            $iquery = "UPDATE measurements SET mtime=?,value=?,detection_limit=?,depth=?, duplicate=?, collection_proc=?, lab_id=?, 
                    lab_sample_id=?,mnotes=?, siteid=?, mtypeid=?, gear_id=?, proj_id=?, proc_id=?, collected_by=?, user_update=?
                WHERE m_id=?";
            $stmt = mysqli_prepare($mysqlid, $iquery);
            foreach ($edits as $key =>$meas) {
                mysqli_stmt_bind_param($stmt, 'sdidissssssssssii', $mtime,$meas["value"],$meas["detection_limit"],$depth,$meas["duplicate"],
                    $meas["collection_proc"],$meas["lab_id"], $meas["lab_sample_id"], $meas["mnotes"], $siteid, $meas["mtypeid"],
                    $meas["gear_id"], $meas["proj_id"], $proc_id, $collected_by, $uid, $meas["m_id"]);    
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
        print ($ins?"$ins data values inserted":"").($ins&&$eds?" and ":" ").($eds?"$eds data values updated":""). " at ".$_POST['siteid']."<br>\n";
        $inserts=array();
        if ($eds==0)
        {
            if ($wbody_type=="L") {
                $_POST['depth']++;
            }
            if ($wbody_type=="S") {
                $siteid=NULL;
                $_POST['stime']='';
            }
            
        }
        else {
            
            if ($wbody_type=="L") {
                $_POST['sdate']='';
                $_POST['stime']='';
                $_POST['depth']="";
            }
            if ($wbody_type=="S") {
                $siteid=NULL;
                $_POST['stime']='';
            }
            
        }
        //$depth=null;
    }
    
}

if ($edit_return) {print "<a href='".urldecode($edit_return)."'>Return to search</a>";}
?>

<form action="add_measurement.php" method="POST" name="f1" id="f1" onsubmit="">
<input type="hidden" name="faction" id ="faction" value="">
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
if (isset($errors["General"]))
{
    print "<tr><td>&nbsp;</td><td class='inputerror'>".$errors["General"]."</td></tr>\n";
}?>

<tr><td>Waterbody Type</td><td><input type="radio" name="wbody_type" class="wbody_type" id="wbody_type_l" value="L" <?php if ($wbody_type==="L")print " checked"?>>Lake &nbsp;
     <input type="radio" name="wbody_type" class="wbody_type" id="wbody_type_s" value="S" <?php if ($wbody_type==="S")print " checked"?>>Stream</td></tr>
<tr><td>Waterbody</td><td><select name="waterbody_id" id="waterbody_id" class="record_locator" >

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
<tr><td>Monitoring site</td><td><select name="siteid" id="siteid" class="record_locator" style="width:300">

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

$sdatePost = isset($_POST['sdate']) ? $_POST['sdate'] : null;
$stimePost = isset($_POST['stime']) ? $_POST['stime'] : null;
$depthPost = isset($_POST['depth']) ? $_POST['depth'] : null;

?>

</select></td></tr>
<tr><td>Measurement Date</td><td><input type="text" name="sdate" size="15" class="calendarSelectDate" value="<?php print$sdatePost;?>"></td></tr>
<tr><td>Measurement Time (military)</td><td><input type="text" name="stime" size="15" class="record_locator" value="<?php print$stimePost;?>"></td></tr>
<tr><td>Depth</td><td><input name="depth" type="text" style="width:60px" maxlength="7" class="record_locator" <?php print "value='".$depthPost."'"; if ($wbody_type=="S") print " readonly";?>></td></tr>

<tbody id=datatable>

<?php 
if ($act_wbody)
{
    print "<tr><td>Collected By</td><td><input type=\"text\" name=\"collected_by\" size=\"10\" value=\"".$collectByPost."\"></td></tr>\n";
    //create option list values for gear and project
    $proj_ar = array();
    $gear_ar = array();
    if ($wbody_type=="L") { $check_col="lake"; }
    elseif ($wbody_type=="S") { $check_col="stream"; }
    else {$check_col=0;}
    $sql_proj = "SELECT * FROM mon_projects WHERE active=1 ORDER BY proj_id"; 
    $res_proj = mysqli_query($mysqlid,$sql_proj);
    print mysqli_error($mysqlid);
    while($row=mysqli_fetch_array($res_proj)) 
    { $proj_ar[]=$row['proj_id']; }
    mysqli_free_result($res_proj);
    $spar = sizeof($proj_ar);
    $sql_gear = "SELECT * FROM mon_gear WHERE active=1 AND $check_col=1 ORDER BY gear_id"; 
    $res_gear = mysqli_query($mysqlid,$sql_gear);
    while($row=mysqli_fetch_array($res_gear)) 
    { $gear_ar[]=$row['gear_id']; }
    mysqli_free_result($res_gear);
    $sgar = sizeof($gear_ar);

    //print procedure pulldown
    print "<tr><td>Procedure</td><td><select name='proc_id'><option value=''></option>";
    $sql_proc = "SELECT * FROM sample_procs WHERE active=1 AND $check_col=1 ORDER BY proc_id"; 
    $res_proc = mysqli_query($mysqlid,$sql_proc);
    while($row=mysqli_fetch_array($res_proc)) 
    { print "<option value='".$row['proc_id']."'".($row['proc_id']==$_POST['proc_id']?" selected":"").">".$row['proc_id']."</option>\n"; }
    mysqli_free_result($res_gear);
    
    print"</select></td></tr>\n";
    
    
    print "<tr><td colspan=2><a name=\"entrytop\" />\n<table class='entrytable' id='t2' >\n ";
    if ($wbody_type=="L") {
        $check_col="lake";
        $col_string=", l_collection_method, l_lower_bound, l_upper_bound";
    }
    elseif ($wbody_type=="S") {
        $check_col="stream";
        $col_string=", s_collection_method, s_lower_bound, s_upper_bound";
    }
    else $check_col=0; // set query to return no rows if neither type (0==1)
    $typequery = "SELECT mtypeid, mtname, units $col_string,  active FROM measurement_type WHERE $check_col=1 ORDER BY  active DESC, mtypeid ";
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
            print " <tr id='$mtypeid"."_error'>\n  <td class='tdright'>&nbsp</td><td class=\"inputerror\" colspan=3>$error</td><td><input type='checkbox' name='override_$mtypeid' value='true'> Override</td>";
            if ($log_user->is_admin()) print"<td>&nbsp;</td>";
            print "</tr>\n";
        }

        // Print a header row with the title of the measurement type, then 
        // fill in required form elements.
        
        // Start by adding update info, if needed.
        
        $updateInfo = "&nbsp";
        
        $displayMeasurementType = "";
        if (!key_exists("value",$entry))
        {
            $displayMeasurementType = "style='{display:none}'";
        }
        
        if ($log_user->is_admin() && key_exists("m_id",$entry))
        {
            $uc_mid = intval($entry['m_id']);
            $adsql = "SELECT user_entry,user_update FROM measurements where m_id=$uc_mid";
            $adres = mysqli_query($mysqlid, $adsql);
            $adrow = mysqli_fetch_array($adres, MYSQLI_ASSOC);
            $enterer = $log_user->user_info($adrow['user_entry']);
            $updater = $log_user->user_info($adrow['user_update']);
            if((strlen($enterer['email']) > 0) && strlen($updater['email']) > 0 )$updateInfo = " Entered by: ".$enterer['email']."; updated by: ".$updater['email'];
            mysqli_free_result($adres);
        }
        //Header
        print " <tr id='$mtypeid"."_h' ><th colspan=3 style=\"width:400px\" class='meas_head' >$mtypeid</th></tr> \n";
        print " <tr id='$mtypeid"."_userData' > <td class='subhead'>$updateInfo</td>\n";
        print "<tbody id='$mtypeid"."_vals' style='$disp' $displayMeasurementType>";
        //Value
        print " <tr id='$mtypeid"."_1' style=''>\n  <td class='tdright'>&nbsp</td>\n";
        print "   <td width='75px'>Value:</td> <td width='300px'><input name=\"val_$mtypeid\" type=\"text\" style=\"width:60px\" maxlength=\"8\" value=\"".((key_exists("detection_limit",$entry)&&$entry["detection_limit"])?"<":"").(key_exists("value",$entry)?$entry['value']:"")."\"><span class='units'>".$mtrow[2]."</span></td></tr>\n";
        //Duplicate checkbox
        print " <tr id='$mtypeid"."_2' style=''>\n <td class='tdright'>&nbsp</td>\n";
        print "   <td width='75px'>Dup:</td> <td><input type='checkbox' name='dup_$mtypeid' id='dup_$mtypeid' value='true' ".((isset($d_entry["duplicate"]) ? $d_entry["duplicate"] : null)==1?"checked":"")." onchange=\"show_dup(this.checked,'$mtypeid' )\" ></td></tr>\n";
        //Lab Sample ID
        print " <tr id='$mtypeid"."_3' style='' >\n  <td class='tdright'>&nbsp;</td>\n";
//        print "   <td width='80px'>Procedure:</td><td colspan=3> <input name=\"proc_$mtypeid\" type=\"text\" style=\"width:280px\" value='".(key_exists("collection_proc",$entry)?$entry['collection_proc']:$mtrow[3])."'>\n";
        print "   <td width='75px'>Lab Sample ID: </td> <td><input name=\"lab_s_id_$mtypeid\" type=\"text\" style=\"width:60px\" value=\"".(key_exists("lab_sample_id",$entry)?$entry['lab_sample_id']:"")."\"></td></tr>\n";    
        //Gear
        print " <tr id='$mtypeid"."_4' style='' >\n  <td class='tdright'>&nbsp;</td>\n";
        print "   <td width='75px'>Gear: </td> <td><select name=\"gear_$mtypeid\" style=\"width:290px\">\n<option value=''></option>";
            $gear_def = key_exists("gear_id",$entry)?$entry['gear_id']:$mtrow[3];
            for ($gpi=0;$gpi < $sgar; $gpi++) 
                {print "<option value='".$gear_ar[$gpi]."'".($gear_def==$gear_ar[$gpi]?" selected":"").">".$gear_ar[$gpi]."</option>"; } 
            print "</select></td></tr>\n";
        //Project
        print " <tr id='$mtypeid"."_5' style='' >\n  <td class='tdright'>&nbsp;</td>\n";
        print "   <td width='75px'>Project: </td> <td><select name=\"proj_$mtypeid\" style=\"width:290px\">\n<option value=''></option>";
            $proj_def = key_exists("proj_id",$entry)?$entry['proj_id']:$default_project;
            for ($gpi=0;$gpi < $spar; $gpi++) 
                {print "<option value='".$proj_ar[$gpi]."'".($proj_def==$proj_ar[$gpi]?" selected":"").">".$proj_ar[$gpi]."</option>"; } 
            print "</select></td></tr>\n";
        //Lab
        print " <tr id='$mtypeid"."_6' style='' >\n  <td class='tdright'>&nbsp;</td>\n";
        print "   <td width='75px'>Lab: </td> <td><input name=\"labid_$mtypeid\" type=\"text\" style=\"width:290px\" maxlength='8' value='".(key_exists("lab_id",$entry)?$entry['lab_id']:$default_lab_id)."'></td>\n";
        print " </tr>\n";
        //Notes
        print " <tr id='$mtypeid"."_7' style='' class='brow'>\n <td class='tdright'>&nbsp</td>\n";
        print "   <td>Notes: </td> <td><input name=\"note_$mtypeid\" type=\"text\" style=\"width:290px\" value=\"".(key_exists("mnotes",$entry)?$entry['mnotes']:"")."\"></td></tr>\n";
        
        //Admin and error stuff
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
        
        print "</tbody>";
        
        //Rows below here belong to duplicates.  They're hidden by default.
        
        $dupUpdateInfo = "&nbsp";
        
        $displayDuplicate = "";
        if (!((isset($d_entry["duplicate"]) ? $d_entry["duplicate"] : null) == 1))
        {
            $displayDuplicate = "style='{display:none}'";
        }
        
        if ($log_user->is_admin() && key_exists("m_id",$d_entry))
        {
            $uc_mid = intval($d_entry['m_id']);
            $adsql = "SELECT user_entry,user_update FROM measurements where m_id=$uc_mid";
            $adres = mysqli_query($mysqlid, $adsql);
            $adrow = mysqli_fetch_array($adres, MYSQLI_ASSOC);
            $enterer = $log_user->user_info($adrow['user_entry']);
            $updater = $log_user->user_info($adrow['user_update']);
            if((strlen($enterer['email']) > 0) && strlen($updater['email']) > 0 )$dupUpdateInfo = " Entered by: ".$enterer['email']."; updated by: ".$updater['email'];
            mysqli_free_result($adres);
        }
        //Header
        print "<tbody id='$mtypeid"."_dupVals' style=$dup_disp >";
        print " <tr id='$mtypeid"."_h2' ><th colspan=3 class='meas_subhead'>Duplicate</th></tr>\n";
        print " <tr id='$mtypeid"."_userData' > <td class='subhead'>$dupUpdateInfo</td>\n";
        
        //Value
        print " <tr id='$mtypeid"."_8' >\n  <td class='tdright'>&nbsp</td>\n";
        print "   <td width='75px'>Value:</td><td width='300px'><input name=\"d_val_$mtypeid\" type=\"text\" style=\"width:60px\" maxlength=\"8\" value=\"".((key_exists("detection_limit",$d_entry)&&$d_entry["detection_limit"])?"<":"").(key_exists("value",$d_entry)?$d_entry['value']:"")."\"><span class='units'>".$mtrow[2]."</span></td></tr>\n";
        //Lab Sample ID
        print " <tr id='$mtypeid"."_9' >\n  <td class='tdright'>&nbsp</td>\n";
        print "   <td width='75px'>Lab Sample ID: </td> <td><input name=\"d_lab_s_id_$mtypeid\" type=\"text\" style=\"width:60px\" value=\"".(key_exists("lab_sample_id",$d_entry)?$d_entry['lab_sample_id']:"")."\"></td> </tr>\n";
        //Gear
        print " <tr id='$mtypeid"."_10' >\n  <td class='tdright'>&nbsp</td>\n";
        print "   <td  width='75px'>Gear: </td> <td><select name=\"d_gear_$mtypeid\" style=\"width:290px\">\n<option value=''></option>";
            $gear_def = key_exists("gear_id",$d_entry)?$d_entry['gear_id']:$mtrow[3];
            for ($gpi=0;$gpi < $sgar; $gpi++) 
            {print "<option value='".$gear_ar[$gpi]."'".($proj_def==$proj_ar[$gpi]?" selected":"").">".$gear_ar[$gpi]."</option>"; } 
            print "</select></td></tr>\n";
        //Project
        print " <tr id='$mtypeid"."_11' >\n  <td class='tdright'>&nbsp</td>\n";
        print "   <td>Project: </td> <td><select name=\"d_proj_$mtypeid\" style=\"width:290px\">\n<option value=''></option>";
            $proj_def = key_exists("proj_id",$d_entry)?$d_entry['proj_id']:$default_project;
            for ($gpi=0;$gpi < $spar; $gpi++) 
            {print "<option value='".$proj_ar[$gpi]."'".($proj_def==$proj_ar[$gpi]?" selected":"").">".$proj_ar[$gpi]."</option>"; } 
            print "</select></td></tr>\n";
        //Lab
        print " <tr id='$mtypeid"."_12' >\n  <td class='tdright'>&nbsp</td>\n";
        print "   <td>Lab: </td> <td><input  name=\"d_labid_$mtypeid\" type=\"text\" style=\"width:290px\" maxlength='8' value='".(key_exists("lab_id",$d_entry)?$d_entry['lab_id']:$default_lab_id)."'></td></tr>\n";
        
//        print "  <td width='65px'>Procedure:</td><td colspan=3> <input name=\"d_proc_$mtypeid\" type=\"text\" style=\"width:280px\" value='".(key_exists("collection_proc",$d_entry)?$d_entry['collection_proc']:$mtrow[3])."'>\n";
    
        //Notes
        print " <tr id='$mtypeid"."_13'  class='brow'>\n  <td class='tdright'>&nbsp</td>\n";
        print "   <td>Notes: </td> <td><input name=\"d_note_$mtypeid\" type=\"text\" style=\"width:290px\" value=\"".(key_exists("mnotes",$d_entry)?$d_entry['mnotes']:"")."\"></td></tr>\n";
        
        if ($act_edit && isset($d_entry['m_id']))
        {
            print "<input type=\"hidden\" name=\"d_mid_$mtypeid\" value=\"".(key_exists("m_id",$d_entry)?$d_entry['m_id']:"")."\">\n";
        }
        
        print "</tbody>";

    }


mysqli_free_result($res);

print "</table>";
print "<a href=\"#entrytop\" onclick=\"show_inactive();\" id=\"showlink\">Show All Measurement Types</a>";
print "<a href=\"#entrytop\" onclick=\"hide_inactive();\" id=\"hidelink\" style=\"display:none\">Hide Inactive Measurement Types</a>";
print "</td></tr>\n";
print '<script language="javascript">var form_needed=false;</script>';
}
else {print '<script language="javascript">var form_needed=true;</script>';}
?>
</table>
<?php 
if ($act_wbody){?>
<input type="button" name="" value="Save" onclick="document.f1.faction.value='input';fsubmit(document.f1);">
<?php 
}
else {
?>
<input type="button" name="" value="Load Form">
<?php
}
?>
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
    rows = new Array("<?php //print implode('","', $inactive_rows);?>");
    for (i=0; i<rows.length; i=i+1)
    {
        document.getElementById(rows[i]).style.display="";
    }
    document.getElementById('hidelink').style.display="";
    document.getElementById('showlink').style.display="none";
}
    
function hide_inactive()
{
    rows = new Array("<?php// print implode('","', $inactive_rows);?>");
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
        $("#" + row + '_dupVals').show();
    }
    else 
    {
        $("#" + row + '_dupVals').hide();
    }
}

function toggle_row(row)
{
    if (($("#" + row + "_1 input").val() == ""))
    {
        $("#" + row + "_vals").toggle();
        //console.log(row);
    }
}

</script>

<script type="text/javascript">
var ajax_source ="add_meas_JSON_response.php";
function check_records()
{
    var dataString = 'section=check_record&';
    if ($("input[name=wbody_type]:checked").val())
        { dataString = dataString + '&wbody_type='+ $("input[name=wbody_type]:checked").val(); }
    if ($("#waterbody_id").val())
        { dataString = dataString + '&waterbody_id='+ $("#waterbody_id").val(); }
    else {return;}
    if ($("#siteid").val())
        { dataString = dataString + '&siteid='+ $("#siteid").val(); }
    else {return;}
            
    if ($("input[name=sdate]").val())
        { dataString = dataString + '&sdate='+ $("input[name=sdate]").val(); }
    else {return;}
    if ($("input[name=stime]").val())
        { dataString = dataString + '&stime='+ $("input[name=stime]").val(); }
    else {return;}

    if ($("input[name=wbody_type]:checked").val() == "L")
    {
        if(isNaN($("input[name=depth]").val()) || jQuery.trim($("input[name=depth]").val()).length ==0)
            { return; }
            dataString = dataString + '&depth='+ $("input[name=depth]").val();
    }

    $.ajax
    ({
        type: "GET",
        url: ajax_source,
        data: dataString,
        cache: false,
        success: function(str)
        {
            if (str=="error") alert(str);
            else if (str>0) {
                if (confirm("Data exists for this site/date combination. Load for editing or cancel to change location or date?"))
                {
                    url = document.location.protocol +'//'+ document.location.host + document.location.pathname;
                    url = url + "?faction=edit&siteid=" + $("#siteid").val() + "&mtime=" + $("input[name=sdate]").val() + "%20" + $("input[name=stime]").val() + "&depth=" + $("input[name=depth]").val();
                    //alert(url);
                    
                    document.location = url; 
                }
            }
            else if (str==0) 
            {
                if (form_needed == true) 
                {
                    document.getElementById('faction').value="siteselect";
                    document.getElementById('f1').submit();
                }
                //alert("new");
            } 
        } 
    });
}

$(document).ready(function()
{
    $(".wbody_type").change(function()
    {
        var id=$(this).val();
        var dataString = 'section=wbody&wbody_type='+ id;

        $.ajax
        ({
            type: "GET",
            url: ajax_source,
            data: dataString,
            cache: false,
            success: function(html)
            {
                $("#waterbody_id").html(html);
            } 
        });
    });
    

    $("#waterbody_id").change(function()
    {
        var id=$(this).val();
        var dataString = 'section=site&waterbody_id='+ id;
        $.ajax
        ({
            type: "GET",
            url: ajax_source,
            data: dataString,
            cache: false,
            success: function(html)
            {
                $("#siteid").html(html);
            } 
        });
    });

    $(".record_locator").change(function ()
    {
        check_records();
    });
    $(".calendarSelectDate").change(function ()
    {
        check_records();
    });
    
    //Hide or display the measurement type in question.
    $("[id$=_h] th").click(function() {
        toggle_row($.trim($(this).text()));
    });
    
});
</script>
<?php 
require_once 'includes/qp_footer.php';
?>