<?php
$page_title='Waterbodies';
require_once 'includes/wqinc.php';
$pagelevel = PAGE_ADMIN;

require_once 'includes/qp_header.php';

login_check($pagelevel, $log_user);

$theFaction     = isset($_POST['faction']) ? $_POST['faction'] : null;
$theFactionGet  = isset($_GET['faction']) ? $_GET['faction'] : null;
$theSub         = isset($_POST['sub']) ? $_POST['sub'] : null;

if ($theSub=="Submit" && ($theFaction=="new" || $theFaction=="edit"))
{
    $throw_error=false;
    if($theFaction=="new") 
    {
        $action_result="added";
        $query = "INSERT INTO waterbodies (wbody_type, wbody_name, DNR_LAKE_ID) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($mysqlid, $query);
        mysqli_stmt_bind_param($stmt, 'sss', $wbody_type , $wbody_name , $DNR_LAKE_ID);    
    }
    if($theFaction=="edit") 
    {
        $action_result="updated";
        $query = "UPDATE waterbodies SET wbody_type=?,wbody_name=?,DNR_LAKE_ID=? WHERE waterbody_id =?";
        $stmt = mysqli_prepare($mysqlid, $query);
        mysqli_stmt_bind_param($stmt, 'sssi', $wbody_type , $wbody_name , $DNR_LAKE_ID , $waterbody_id);
        $waterbody_id=$_POST['waterbody_id'];
    }
    $wbody_type=$_POST['wbody_type'];
    $wbody_name=$_POST['wbody_name'];
    $DNR_LAKE_ID=$_POST['DNR_LAKE_ID'];
    mysqli_stmt_execute($stmt);
    if (mysqli_stmt_errno($stmt)){ 
        printf("Error: %d.\n", mysqli_stmt_errno($stmt)); 
        printf("Error: %s.\n", mysqli_stmt_error($stmt));
    }
    else print "<p>Waterbody $wbody_name $action_result</p>";
}

if ($theFaction=="delete" && $_POST['waterbody_id'])
{
    $query = "DELETE FROM waterbodies WHERE waterbody_id=?";
    $stmt = mysqli_prepare($mysqlid, $query);
    mysqli_stmt_bind_param($stmt, 's', $_POST['waterbody_id']);
    mysqli_stmt_execute($stmt);    
    if (mysqli_stmt_errno($stmt) == 1451){
        print "<p>".$_POST['siteid']." can not be deleted because it has monitoring sites associated with it.</p>"; 
    }
    else if (mysqli_stmt_errno($stmt))
    {
        printf("Error: %d.\n", mysqli_stmt_errno($stmt)); 
        printf("Error: %s.\n", mysqli_stmt_error($stmt));
    }
    else print "<p>Waterbody ".$_POST['siteid']." deleted</p>";
}
if ($theFactionGet=="new" || $theFactionGet=="edit")
{
    $action_descriptor = "New";
    if ($theFactionGet=="edit" && $_GET['waterbody_id'])
    {
        $action_descriptor = "Edit";
        $query = "SELECT * FROM waterbodies WHERE waterbody_id=?";
        $stmt = mysqli_prepare($mysqlid, $query); 
        if($stmt==false) {printf("Errormessage: %s\n", mysqli_error($mysqlid));exit;}
        mysqli_stmt_bind_param($stmt, "s", $_GET['waterbody_id']);    
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $waterbody_id,$wbody_type,$wbody_name,$DNR_LAKE_ID);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
        
        
    }
    ?>
    <form action="waterbodies.php" name="f1" id="f1" method="POST">
    <input type="hidden" name="faction" value="<?php print $theFactionGet;?>">
    <?php 
        if($theFactionGet=="edit") {print "<input type=\"hidden\" name=\"waterbody_id\" value=\"$waterbody_id\">\n";}
    ?>
    <h2><?php print $action_descriptor; ?>  Waterbody</h2>
    <table style="width:550px">
    <tr><td class="tdright">Waterbody Name</td><td><input name="wbody_name" type="text" style="width:400px" value="<?php print "$wbody_name";?>"></td></tr>
    <tr><td class="tdright">DNR_LAKE_ID</td><td><input name="DNR_LAKE_ID" type="text" style="width:400px" value="<?php print "$DNR_LAKE_ID";?>"></td></tr>
    <tr><td class="tdright">Waterbody Type</td><td><input name="wbody_type" type="radio" value="L" <?php print ($wbody_type=="L"?"checked":"");?>> Lake  &nbsp; 
        <input name="wbody_type" type="radio" value="S" <?php print ($wbody_type=="S"?"checked":"");?>> Stream  &nbsp; </td></tr>    </table>
<?php        
    $allow_delete=false;
    if ($theFactionGet=="edit" && $_GET['waterbody_id'])
    {
        $action_descriptor = "Edit";
        $query = "SELECT COUNT(m.value) AS datapoints, DATE_FORMAT(MIN(m.mtime), '%c/%e/%Y') AS firstpoint, DATE_FORMAT( MAX(m.mtime), '%c/%e/%Y') AS lastpoint, count(DISTINCT ms.siteid) as sites 
            FROM measurements m, monitoring_sites ms WHERE m.siteid = ms.siteid    AND ms.waterbody_id =? GROUP BY waterbody_id";
        $stmt = mysqli_prepare($mysqlid, $query); 
        if($stmt==false) {printf("Errormessage: %s\n", mysqli_error($mysqlid));exit;}
        mysqli_stmt_bind_param($stmt, "s", $_GET['waterbody_id']);    
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $datapoints,$firstpoint,$lastpoint, $sites);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
        if     ($datapoints > 0)
        {
            print "There are $datapoints data values at  $sites sites from $firstpoint to $lastpoint<br>\n";

            $allow_delete=false;
            
        }
        else 
        {
            print "There are no data values associated with this site.<br><br>\n";
            $allow_delete=true;
        }
        
    }
?>        
    <input type="submit" name="sub" value="Submit"><?php if ($allow_delete) {print "<input type=\"button\" name=\"delete\" value=\"Delete\" onclick=\"delete_site(document.forms['f1']);\">";}?>
    <input type="button" name="cancel" value="Cancel" onclick="location.href='<?php print $_SERVER["PHP_SELF"]?>'">
    <div id="calendarDiv"></div></form>
<script language="javascript">
function delete_site (form_sub)
{
    if (confirm('Are you sure you want to delete this waterbody?')) 
    {
        form_sub.faction.value='delete'; 
        form_sub.submit();
    }
}

</script>
    <?php
    $def_vis="none";
}


?>

<table width="500px" class="listtable">
<tr><th colspan=3>Waterbodies</th></tr>

<tbody><tr><td colspan=2><a href="waterbodies.php?faction=new">New Waterbody</a></td></tr>
<?php 
$mt_tr=array();
$mt_types=array();
$query = "SELECT * FROM waterbodies ORDER BY wbody_name ASC";
$res = mysqli_query($mysqlid, $query);
$group="";
while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC))
{
    print "<tr><td>".$row['wbody_name']."</td>
        <td><a href=\"waterbodies.php?faction=edit&waterbody_id=".$row['waterbody_id']."\">edit</a></td></tr>";
    
}


mysqli_free_result($res);
?></tbody>
</table>

<?php
require_once 'includes/qp_footer.php';
?>    