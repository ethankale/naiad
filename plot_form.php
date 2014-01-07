<?php
$page_title='Measurements Graphing';
require_once 'includes/wqinc.php';
$pagelevel = PAGE_USER;

require_once 'includes/qp_header.php';
?>

<h1>Measurements Graphing

<form name="f1" id="f1">
<table class="formtable">

    <tr>
    <td class="tdright">Monitoring Site</td>
    <td>
        <select name="siteid" id="siteid" style="width:300px" onchange="show_measurements();">
        <!-- Fill in a select box with a list of all of the site ids in the db. -->
        <?php 
        $query = "SELECT `siteid`, CONCAT(`wbody_name`, ' - ', `siteid`) as theName
            FROM `monitoring_sites` 
            LEFT JOIN `waterbodies`
              ON `monitoring_sites`.`waterbody_id` = `waterbodies`.`waterbody_id`
            ORDER BY `waterbodies`.`wbody_name`, `monitoring_sites`.`siteid`";
        $res = mysqli_query($mysqlid, $query);

        while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) {
            echo '<option value="' . $row["siteid"] . '">' . $row["theName"] . '</option>';
        }
        
        mysqli_free_result($res);
        ?>
        
        </select>
    </td>
    </tr>
    
    <tr>
    <td class="tdright">Parameter</td>
    <td>
        <select name="mtypeid" id="mtypeid" style="width:300px">
        <?php
        $query = "SELECT `mtypeid` , `mtname`
            FROM `measurement_type`";
        $res = mysqli_query($mysqlid, $query);

        while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC)) {
            echo '<option value="' . $row["mtypeid"] . '">' . $row["mtname"] . '</option>';
        }
        
        mysqli_free_result($res);
        ?>
        </select>
    </td>
    </tr>
    <tr><td class="tdright">Start Date</td><td><input type="text" name="stdate" id="stdate" size="15" class="calendarSelectDate"></td></tr>
    <tr><td class="tdright">End Date</td><td><input type="text" name="enddate" id="enddate" size="15" class="calendarSelectDate"></td></tr>
    <tr>
    <td></td>
    <td><button type="button" onclick="graph();">Graph</button></td>
    </tr>
    </table>
</form>
<div id="calendarDiv"></div>

<script language="javascript">

var theData = [];

    function graph() {
        theData = [];
        
        var url = "measurements_ajax.php?";
        url = $("#mtypeid").val().length > 0 ? url + "&mtypeid=" + $("#mtypeid").val() : url;
        url = $("#siteid").val().length > 0 ? url + "&siteid=" + $("#siteid").val() : url;
        url = $("#stdate").val().length > 0 ? url + "&minDate=" + $("#stdate").val() : url;
        url = $("#enddate").val().length > 0 ? url + "&maxDate=" + $("#enddate").val() : url;
        
        
        $.getJSON(url, function( data ) {
            
            theData = data;
        });
    }

</script>

<?php 
require_once 'includes/qp_footer.php';
?>

