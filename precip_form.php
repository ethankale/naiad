<?php
$page_title='Precipitation Data Request Form';
require_once 'includes/wqinc.php';
$pagelevel = PAGE_USER;

require_once 'includes/qp_header.php';

?>

<form action="precip_report.php" method="get" name="f1" id="f1" onsubmit="return validate(this);">
<input type="hidden" name="report_type" value="raw">
<table class="formtable">
<tr><th colspan=2>Precipitation Data Download</th></tr>
<tr><td class="tdright">Station</td><td><select name="stationid">
<option value="">Select a precipitation station</option>
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
<tr><td class="tdright"></td><td><input type="checkbox" name="in" value="true" checked>Inches of Rain</td></tr>
<tr><td class="tdright"></td><td><input type="checkbox" name="temp" value="true">Air Temp</td></tr>
<tr><td class="tdright"></td><td><input type="checkbox" name="wind_speed" value="true">Wind Speed</td></tr>
<tr><td class="tdright"></td><td><input type="checkbox" name="wind_dir" value="true">Wind Direction</td></tr>
<tr><td class="tdright"></td><td><input type="checkbox" name="air_press" value="true">Air Pressure</td></tr>
<tr><td class="tdright">Start Date</td><td><input type="text" name="stdate" size="15" class="calendarSelectDate"></td></tr>
<tr><td class="tdright">End Date</td><td><input type="text" name="enddate" size="15" class="calendarSelectDate"></td></tr>

<tr><td class="tdright"></td><td><input type="radio" name="report_type" value="raw">Raw data</td></tr>
<tr><td class="tdright"></td><td><input type="radio" name="report_type" value="daily">Daily data</td></tr>
<tr><td class="tdright"></td><td><input type="radio" name="report_type" value="monthly">Monthly data</td></tr>
<tr><td class="tdright"></td><td><input type="checkbox" name="downloadcsv" value="true">Download CSV</td></tr>

<tr><td></td><td><button type="submit">Submit</button></td></tr>
</table>
</form>
<div id="calendarDiv"></div>
<script language="javascript">
function validate(form_sub)
{

	//alert 
	if (form_sub.stationid.selectedIndex==0) 
	{
		alert("Please select a precipitation station.");
		return false
	}
	if (form_sub.stdate.value.length > 7 && form_sub.enddate.value.length > 7 && form_sub.stdate.value > form_sub.enddate.value)
	{
		alert("Please make sure the start date is before the end date.");
		return false;
	}
	return true;
}
</script>
<?php 
require_once 'includes/qp_footer.php';
?>

