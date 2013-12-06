<?php
/*
 * Page for adding and editing measurement types
 */
$page_title='Measurement Types';
require_once 'includes/wqinc.php';
$pagelevel = PAGE_ADMIN;

require_once 'includes/qp_header.php';

login_check($pagelevel, $log_user);

// if POST form submission update the Measurement type
if ($_POST['submit']=="Submit" && ($_POST['action']=="new" || $_POST['action']=="edit"))
{
    $throw_error=false;
    if($_POST['action']=="new") 
    {
        $action_result="added";
        $query = "INSERT INTO measurement_type (mtypeid, mtname, storet_header, units, lake, stream, l_collection_method, l_lower_bound, l_upper_bound, l_profile, l_multi_depth, s_collection_method, s_lower_bound, s_upper_bound, active, disp_order, notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($mysqlid, $query);
        mysqli_stmt_bind_param($stmt, 'ssssiisddiisddiis', $mtypeid, $mtname, $storet_header, $units, $lake, $stream, $l_collection_method, $l_lower_bound, $l_upper_bound, $l_profile, $l_multi_depth, $s_collection_method, $s_lower_bound, $s_upper_bound, $active, $disp_order, $notes);    
    }
    if($_POST['action']=="edit") 
    {
        $action_result="updated";
        $query = "UPDATE measurement_type SET mtypeid=?, mtname=?, storet_header=?, units=?, lake=?, stream=?, l_collection_method=?, l_lower_bound=?, l_upper_bound=?, 
            l_profile=?, l_multi_depth=?, s_collection_method=?, s_lower_bound=?, s_upper_bound=?, active=?, disp_order=?, notes=? WHERE mtypeid=?";
        $stmt = mysqli_prepare($mysqlid, $query);
        mysqli_stmt_bind_param($stmt, 'ssssiisddiisddiiss', $mtypeid, $mtname, $storet_header, $units, $lake, $stream, $l_collection_method, $l_lower_bound, $l_upper_bound, $l_profile, $l_multi_depth, $s_collection_method, $s_lower_bound, $s_upper_bound, $active, $disp_order, $notes, $oldmtypeid);
        $oldmtypeid=$_POST['oldmtypeid'];
    }
    $mtypeid=$_POST['mtypeid'];
    $siteid=$_POST['siteid'];
    $mtname=$_POST['mtname'];
    $storet_header=$_POST['storet_header'];
    $units=$_POST['units'];
    $lake=($_POST['lake']==1)?1:0;
    $stream=($_POST['stream']==1)?1:0;
    $l_collection_method=$_POST['l_collection_method'];
    $l_lower_bound=$_POST['l_lower_bound'];
    if ($_POST['l_lower_unbound']=="true") {$l_lower_bound=NULL;}
    $l_upper_bound=$_POST['l_upper_bound'];
    if ($_POST['l_upper_unbound']=="true") {$l_upper_bound=NULL;}
    $l_profile=($_POST['l_profile']==1)?1:0;
    $l_multi_depth=($_POST['l_multi_depth']==1)?1:0;
    $s_collection_method=$_POST['s_collection_method'];
    $s_lower_bound=$_POST['s_lower_bound'];
    if ($_POST['s_lower_unbound']=="true") {$s_lower_bound=NULL;}
    $s_upper_bound=$_POST['s_upper_bound'];
	if ($_POST['s_upper_unbound']=="true") {$s_upper_bound=NULL;}
    $active=($_POST['active']==1)?1:0;
    $disp_order=$_POST['disp_order'];
    $notes=$_POST['notes'];
    
    mysqli_stmt_execute($stmt);
    if (mysqli_stmt_errno($stmt)){ 
        printf("Error: %d.\n", mysqli_stmt_errno($stmt)); 
        printf("Error: %s.\n", mysqli_stmt_error($stmt));
    }
    else print "<p>Measurement Type $mtypeid $action_result</p>";
}

// if get fields submitted display entry form - prefilled with data if an edit
if ($_GET['action']=="new" || $_GET['action']=="edit")
{
    $action_descriptor = "New";
    if ($_GET['action']=="edit" && $_GET['mtypeid'])
    {
        $action_descriptor = "Edit";
        $query = "SELECT mtypeid, mtname, storet_header, units, lake, stream, l_collection_method, l_lower_bound, l_upper_bound, l_profile, l_multi_depth, s_collection_method, s_lower_bound, s_upper_bound, active, disp_order, notes FROM measurement_type WHERE mtypeid=?";
        $stmt = mysqli_prepare($mysqlid, $query); 
        if($stmt==false) {printf("Errormessage: %s\n", mysqli_error($mysqlid));exit;}
        mysqli_stmt_bind_param($stmt, "s", $_GET['mtypeid']);    
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $mtypeid, $mtname, $storet_header, $units, $lake, $stream, $l_collection_method, $l_lower_bound, $l_upper_bound, $l_profile, $l_multi_depth, $s_collection_method, $s_lower_bound, $s_upper_bound, $active, $disp_order, $notes);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
        
    }
    ?>

    <form action="meas_types.php" method="POST">
    <input type="hidden" name="action" value="<?php print $_GET['action'];?>">
    
    <?php 
        if($_GET['action']=="edit") {print "<input type=\"hidden\" name=\"oldmtypeid\" value=\"$mtypeid\">\n";}
    ?>
    
    <h2><?php print $action_descriptor; ?> Measurement Type</h2>
    
    <table style="width:650px">
	<tr><td class="tdright">Measurement Type Name</td><td><input name="mtname" type="text" style="width:200px" maxlength="255" value="<?php print "$mtname";?>"></td></tr>
	<tr><td class="tdright">Type Abbreviation</td><td><input name="mtypeid" type="text" style="width:60px" maxlength="7" value="<?php print "$mtypeid";?>"></td></tr>
	<tr><td class="tdright">EQUiS Column Header</td><td><input name="storet_header" type="text" style="width:200px" maxlength="255" value="<?php print "$storet_header";?>"></td></tr>
        <tr><td class="tdright">Units</td><td><input name="units" type="text" style="width:60px" maxlength="100" value="<?php print "$units";?>"></td></tr>
        <tr><td class="tdright">Type available for:</td><td><input type="checkbox" name="lake" value="1" <?php if ($lake== 1) print " checked"?>> Lakes<br>
             <input type="checkbox" name="stream" value="1" <?php if ($stream== 1) print " checked"?>> Streams</td></tr>
	<tr><td class="tdright">Lake Default Gear</td><td><input name="l_collection_method" type="text" style="width:400px" value="<?php print "$l_collection_method";?>"></td></tr>
        <tr><td class="tdright">Lake - Lower Bound</td><td><input name="l_lower_bound" type="text" style="width:80px" maxlength="10" value="<?php if($l_lower_bound || $l_lower_bound===0.0) printf("%.5f",$l_lower_bound);?>">
            <input type="checkbox" name="l_lower_unbound" value="true" <?php if ($l_lower_bound===NULL) print " checked"?>> Unbounded</td></tr>
        <tr><td class="tdright">Lake - Upper Bound</td><td><input name="l_upper_bound" type="text" style="width:80px" maxlength="10" value="<?php if($l_upper_bound || $l_upper_bound===0.0) printf("%.5f",$l_upper_bound);?>">
            <input type="checkbox" name="l_upper_unbound" value="true" <?php if ($l_upper_bound===NULL) print " checked"?>> Unbounded</td></tr>    
        <tr><td class="tdright"></td><td><input type="checkbox" name="l_profile" value="1" <?php if ($l_profile== 1) print " checked"?>> Lake Profile Data</td></tr>
        <tr><td class="tdright"></td><td><input type="checkbox" name="l_multi_depth" value="1" <?php if ($l_multi_depth== 1) print " checked"?>> Lake Multiple Depth Data</td></tr>    

	<tr><td class="tdright">Stream Default Gear</td><td><input name="s_collection_method" type="text" style="width:400px" value="<?php print "$s_collection_method";?>"></td></tr>
        <tr><td class="tdright">Stream - Lower Bound</td><td><input name="s_lower_bound" type="text" style="width:80px" maxlength="10" value="<?php if($s_lower_bound || $s_lower_bound===0.0) printf("%.5f",$s_lower_bound);?>">
            <input type="checkbox" name="s_lower_unbound" value="true" <?php if ($s_lower_bound===NULL) print " checked"?>> Unbounded</td></tr>
        <tr><td class="tdright">Stream - Upper Bound</td><td><input name="s_upper_bound" type="text" style="width:80px" maxlength="10" value="<?php if($s_upper_bound || $s_upper_bound===0.0) printf("%.5f",$s_upper_bound);?>">
            <input type="checkbox" name="s_upper_unbound" value="true" <?php if ($s_upper_bound===NULL) print " checked"?>> Unbounded</td></tr>    
        <tr><td class="tdright">Display order</td><td><input name="disp_order" type="text" style="width:60px" maxlength="7" value="<?php print "$disp_order";?>"></td></tr>
        <tr><td class="tdright">&nbsp;</td><td><input type="checkbox" name="active" value="1" <?php if ($active== 1) print " checked"?>> Measurement type is active</td></tr>
        <tr><td class="tdright">Notes</td><td><textarea name="notes" rows=3 cols=25><?php print "$notes";?></textarea> </td></tr>
    </table>
    
    <input type="submit" name="submit" value="Submit"><input type="button" name="cancel" value="Cancel" onclick="location.href='<?php print $_SERVER["PHP_SELF"]?>'">
    <div id="calendarDiv"></div></form>

    <?php
    $def_vis="none";
}


?>

<table width="500px" class="listtable">
<tr><th colspan=3>Measurement Types</th></tr>

<tbody><tr><td colspan=3><a href="meas_types.php?action=new">New Measurement Type</a></td></tr>
<?php 
// display list of measurement types
$mt_tr=array();
$mt_types=array();
$query = "SELECT * FROM measurement_type ORDER BY active DESC, disp_order ASC";
$res = mysqli_query($mysqlid, $query);
$group="";
while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC))
{
    print "<tr><td>".$row['mtypeid']."</td><td>".$row['mtname']."</td>
		<td><a href=\"meas_types.php?action=edit&mtypeid=".urlencode($row['mtypeid'])."\">edit</a></td></tr>";
    
}


mysqli_free_result($res);
?></tbody>
</table>

<?php
require_once 'includes/qp_footer.php';
?>    