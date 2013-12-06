<?php
$page_title='Precipitation Data Upload';
require_once 'includes/wqinc.php';
$pagelevel = PAGE_USER;

require_once 'includes/qp_header.php';

login_check($pagelevel, $log_user);
?>


<table>
<tbody id="Head">
    <tr class="tippingBucket"><th colspan=2>Tipping Bucket Upload</th></tr>
    <tr class="manual" style="display:none"><th colspan=2>Manual Data Upload</th></tr>
    <tr><td colspan=2>Format: "Month/Day/Year Hour:Minute:Second,Rainfall"</td></tr>
    <tr><td colspan=2>"Rainfall" is the amount of precip, in inches. </td></tr>
    <tr><td> &nbsp </td></tr>
    <tr><td>Data Format:</td><td><a href="#" onclick="
        $('.tippingBucket').toggle(); 
        $('.manual').toggle()
        "> Switch </a>  </td> </tr>
</tbody>
<tbody id="tippingBucketForm" class="tippingBucket">
    <form action="precip_import.php" method="post" enctype="multipart/form-data">
    

    <tr><td>File:</td>              <td><input type="file" name="datafile"> </td> </tr>
    <tr><td>Header Rows</td>        <td><input type="text" name="header_rows" value="1" size="5"> </td> </tr>
    <tr><td>Station</td>            <td><select name="stationid">
        <?php 
        $query = "SELECT StationID, Station_Name from precipitation_stations order by Station_Name";
        $res = mysqli_query($mysqlid, $query);
        while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC))
        {
            print '<option value="'.$row['StationID'].'">'.$row['Station_Name']."</option>\n";
        }

        mysqli_free_result($res);
        ?>
        </select></td></tr>
    <tr><td></td>                   <td><button type="submit">Submit</button> </td> </tr>
</form>
<tbody id="manualForm" class="manual" style="display: none">
    <form action="precip_import2.php" method="post" enctype="multipart/form-data">
    
    <tr><td>File:</td>              <td><input type="file" name="datafile"> </td> </tr>
    <tr><td>Header Rows</td>        <td><input type="text" name="header_rows" value="1" size="5"> </td> </tr>
    <tr><td>Station</td>            <td><select name="stationid">
        <?php 
        $query = "SELECT StationID, Station_Name from precipitation_stations order by Station_Name";
        $res = mysqli_query($mysqlid, $query);
        while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC))
        {
            print '<option value="'.$row['StationID'].'">'.$row['Station_Name']."</option>\n";
        }


        mysqli_free_result($res);
        ?>
        </select></td></tr>
    <tr><td></td>               <td><button type="submit">Submit</button> </td> </tr>
</table>
</form>

<?php
require_once 'includes/qp_footer.php';
?>	