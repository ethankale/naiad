<?php
$page_title='Measurements Data Request Form';
require_once 'includes/wqinc.php';
$pagelevel = PAGE_USER;

require_once 'includes/qp_header.php';
?>

<form action="measurements_report.php" method="get" name="f1" id="f1" target="MCWD_graph">
<input type="hidden" name="report_type" value="raw">
<table class="formtable">
<tr><th colspan=2>Water Quality Measurements Data Download</th></tr>
<tr><td class="tdright">Select Graph </td><td><input type="radio" name="plot_type" value="s" onclick="set_plot('S',document.f1)">Simple - plot a variable over time at one or more sites</td></tr>
<tr><td class="tdright"></td><td><input type="radio" name="plot_type" value="C" onclick="set_plot('C',document.f1)">Correlation - scatter plot of two variables at a site</td></tr>
<tr><td class="tdright"></td><td><input type="radio" name="plot_type" value="P" onclick="set_plot('P',document.f1)">Profile - plot value against depth at a lake site</td></tr>
<tr><td class="tdright">&nbsp;</td><td></td></tr>
<tr><td class="tdright"></td><td><input type="radio" name="wbody_type" value="L" onclick="update_waterbodies('L',document.getElementById('waterbodyid'))">Lake</td></tr>
<tr><td class="tdright"></td><td><input type="radio" name="wbody_type" value="S" onclick="update_waterbodies('S',document.getElementById('waterbodyid'))">Stream</td></tr>
<tr><td class="tdright">Waterbody</td><td><select name="waterbodyid" id="waterbodyid" onchange="update_sites(this.value,document.getElementById('siteid'))" style="width:300;">
<option value="">Select Waterbody Type</option>
</select></td></tr>

<tr><td class="tdright">Monitoring Site</td><td><select name="siteid[]" size=4 multiple id="siteid" style="width:300px" onchange="show_measurements();">
<option value="">Select Waterbody Type</option></select></td></tr>
<tr><td>&nbsp;</td><td><b>Measurements</b></td></tr>
<tr id="meas_header";><td>&nbsp;</td><td>Please select sites to monitor</td></tr>
<!-- tr id="meas_profile"; style="display:none;"><td>&nbsp;</td><td><input type="checkbox" name="lake_profiles" id="lake_profiles" value="1" onclick="show_lake_measurements(this.checked)"> Profile Measurements</td></tr -->
<tr id="meas_select"><td>&nbsp;</td><td>
<table><tbody id="select_tbody">
<?php 
$query = "SELECT mtypeid, mtname, lake, stream, l_profile FROM measurement_type ORDER BY disp_order, active desc, mtypeid";
$res = mysqli_query($mysqlid, $query);
$mt_tr=array();
$mt_types=array();
$mt_profile=array();
while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC))
{
	//print "<tr id=\"".$row["mtypeid"]."_tr\" style=\"display:none;\"><td class=\"tdright\"></td><td><input type=\"checkbox\" name=\"mtypeid[]\" value=\"".$row["mtypeid"]."\" id=\"".$row["mtypeid"]."\">".$row["mtname"]."</td></tr>\n";
	$mt_tr[] = $row["mtypeid"];
	if ($row["lake"] && $row["stream"]) $mt_types[] = "B";
	elseif ($row["lake"])  $mt_types[] = "L";	
	elseif ($row["stream"]) $mt_types[] = "S";
	else $mt_types[]="";
	if ($row["l_profile"]) $mt_profile[] = "P";
	else $mt_profile[] = "";
}


mysqli_free_result($res);
?>
</tbody></table></td></tr>
<tr><td>&nbsp;</td><td>&nbsp;</td></tr>
<tr><td class="tdright">Start Date</td><td><input type="text" name="stdate" size="15" class="calendarSelectDate"></td></tr>
<tr><td class="tdright">End Date</td><td><input type="text" name="enddate" size="15" class="calendarSelectDate"></td></tr>
<tr><td></td><td><button type="button" onclick="fsubmit(document.f1);">Submit</button></td></tr>
</table>
</form>
<div id="calendarDiv"></div>

<script language="javascript">
<?php 
	$query = "SELECT waterbody_id, wbody_type, wbody_name FROM waterbodies  order by wbody_name";
	$res = mysqli_query($mysqlid, $query);
	$wbid_s = array();
	$wbid_l = array();
	$wbname_s = array();
	$wbname_l = array();
	
	while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC))
	{
		if ($row['wbody_type'] == 'L')
		{
			$wbid_l[]=$row['waterbody_id'];
			$wbname_l[]=$row['wbody_name'];
		}
		else if ($row['wbody_type'] == 'S')
		{
			$wbid_s[]=$row['waterbody_id'];
			$wbname_s[]=$row['wbody_name'];
		}
	}
	mysqli_free_result($res);
	print 'var stream_ids=new Array("'.implode('","', $wbid_s)."\");\n";
	print 'var stream_names=new Array("'.implode('","', $wbname_s)."\");\n";
	print 'var lake_ids=new Array("'.implode('","', $wbid_l)."\");\n";
	print 'var lake_names=new Array("'.implode('","', $wbname_l)."\");\n";

	$s_sid = array();
	$s_wbid = array();
	$s_name = array();
	$query = "SELECT siteid,site_description, waterbody_id, monitor_type FROM sites_list ORDER BY active aSC,  site_description asc, siteid";
	$res = mysqli_query($mysqlid, $query);
	while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC))
	{
		$s_sid[]= $row['siteid'];
		$s_wbid[]= $row['waterbody_id'];
		$s_name[]=$row['site_description'];
		$s_type[]=$row['monitor_type'];
	}
	mysqli_free_result($res);
	print 'var site_ids=new Array("'.implode('","', $s_sid)."\");\n";
	print 'var site_wbids=new Array("'.implode('","', $s_wbid)."\");\n";
	print 'var site_names=new Array("'.implode('","', $s_name)."\");\n";
	print 'var site_types=new Array("'.implode('","', $s_type)."\");\n";
	
	print 'var mt_tr=new Array("'.implode('","', $mt_tr)."\");\n";
	print 'var mt_type=new Array("'.implode('","', $mt_types)."\");\n";
	print 'var mt_profile=new Array("'.implode('","', $mt_profile)."\");\n";
	print "var wbtype='';\n";
	print "var plottype='';\n";
?>

var display_option = '';

function update_waterbodies(wtype, selectbox) {
	removeAllOptions(selectbox);
	addOption(selectbox, "", "Select Waterbody");
	addOption(selectbox, "-1", "Show All");
	if(wtype == 'L'){
		for (i=0;i<lake_ids.length;i++)
		{
			addOption(selectbox,lake_ids[i], lake_names[i]);
		}
		wbtype='L';
	}
	if(wtype == 'S'){
		for (i=0;i<stream_ids.length;i++)
		{
			addOption(selectbox,stream_ids[i], stream_names[i]);
		}
		wbtype='S';
	}
	removeAllOptions(document.getElementById('siteid'));
	addOption(document.getElementById('siteid'), "", "Select Waterbody");
	hide_measurements();

}

function hide_measurements()
{
	var tablebody = document.getElementById("select_tbody");
	removerows(tablebody);
	display_option='';
}

function show_measurements()
{
	document.getElementById('meas_header').style.display="none";
	if (plottype=='S' && display_option !='S') 
	{
		build_simple (wbtype);
	}
	if (plottype=='C' && display_option !='C') 
	{
		build_comp (wbtype)
	}
	if (plottype=='P' && display_option !='P') 
	{
		build_profile();
	}
}

function update_sites(wbid, selectbox) {
	removeAllOptions(selectbox);
	addOption(selectbox, "", "Select Site(s)", "");
	if (wbid==-1)
	{
		for (i=0;i<site_ids.length;i++)
		{
			if(site_types[i]==wbtype)
				addOption(selectbox,site_ids[i], site_names[i]);
		}
		return;
	}
	for (i=0;i<site_ids.length;i++)
	{
		if(site_wbids[i]==wbid)
			addOption(selectbox,site_ids[i], site_names[i]);
	}
}

function removeAllOptions(selectbox)
{
	var i;
	for(i=selectbox.options.length-1;i>=0;i--)
	{
		//selectbox.options.remove(i);
		selectbox.remove(i);
	}
}


function addOption(selectbox, value, text )
{
	var optn = document.createElement("OPTION");
	optn.text = text;
	optn.value = value;

	selectbox.options.add(optn);
}

function set_plot(ptype,form_sub)
{

	if (ptype=='S') 
	{
		form_sub.action="splot.php";
		if ((form_sub.wbody_type[0].checked || form_sub.wbody_type[1].checked) && display_option !='S')
		{
			build_simple (wbtype);
		}
	}
	if (ptype=='C') 
	{
		document.f1.action="cplot.php";
		if (display_option !='C') {	build_comp (wbtype); }
	}
	if (ptype=='P') 
	{
		document.f1.action="proplot.php";
		if (!form_sub.wbody_type[0].checked) 
		{
			form_sub.wbody_type[0].checked=true;
			update_waterbodies('L',document.getElementById('waterbodyid'));
		}
		if (display_option !='P') {	build_profile(); }
	}
	plottype=ptype;
}

function build_simple (wbtype)
{
	var tablebody = document.getElementById("select_tbody");
	removerows(tablebody);
	for (i=0;i< mt_tr.length; i++)
	{
		if ((mt_type[i]==wbtype || mt_type[i]=="B") && !(wbtype=="L" && mt_profile[i]=="P"))
		{
	        var row = document.createElement("tr");

	        var cell = document.createElement("td");
	        var input = document.createElement("input");
	        input.setAttribute("type", "radio");
	        input.setAttribute("name", "s_mtypeid");
	        input.setAttribute("value", mt_tr[i]);
	        cell.appendChild(input);
	        row.appendChild(cell);
	        
	        var titlecell = document.createElement("td");
	        titlecell.appendChild(document.createTextNode(mt_tr[i]));
	        row.appendChild(titlecell);
	        tablebody.appendChild(row);
		}
	}
	display_option='S';
}

function build_comp (wbtype)
{
	var tablebody = document.getElementById("select_tbody");
	removerows(tablebody);
    var row = document.createElement("tr");

    var cell = document.createElement("td");
    cell.setAttribute("colspan", "2");
    cell.appendChild(document.createTextNode("X-variable"));
    row.appendChild(cell);

    var cell = document.createElement("td");
    cell.setAttribute("colspan", "2");
    cell.appendChild(document.createTextNode("Y-variable"));
    row.appendChild(cell);
    tablebody.appendChild(row);
    for (i=0;i< mt_tr.length; i++)
	{
		if (mt_type[i]==wbtype || mt_type[i]=="B")
		{
	        var row = document.createElement("tr");

	        var cell = document.createElement("td");
	        var input = document.createElement("input");
	        input.setAttribute("type", "radio");
	        input.setAttribute("name", "c_mtypeid1");
	        input.setAttribute("value", mt_tr[i]);
	        cell.appendChild(input);
	        row.appendChild(cell);
	        
	        var titlecell = document.createElement("td");
	        titlecell.appendChild(document.createTextNode(mt_tr[i]));
	        row.appendChild(titlecell);

	        var cell2 = document.createElement("td");
	        var input2 = document.createElement("input");
	        input2.setAttribute("type", "radio");
	        input2.setAttribute("name", "c_mtypeid2");
	        input2.setAttribute("value", mt_tr[i]);
	        cell2.appendChild(input2);
	        row.appendChild(cell2);
	        
	        var titlecell = document.createElement("td");
	        titlecell.appendChild(document.createTextNode(mt_tr[i]));
	        row.appendChild(titlecell);

	        tablebody.appendChild(row);
		}
	}
	display_option='C';
}

function build_profile ()
{
	var tablebody = document.getElementById("select_tbody");
	removerows(tablebody);
	for (i=0;i< mt_tr.length; i++)
	{
		if (mt_profile[i]=="P")
		{
	        var row = document.createElement("tr");

	        var cell = document.createElement("td");
	        var input = document.createElement("input");
	        input.setAttribute("type", "radio");
	        input.setAttribute("name", "p_mtypeid");
	        input.setAttribute("value", mt_tr[i]);
	        cell.appendChild(input);
	        row.appendChild(cell);
	        
	        var titlecell = document.createElement("td");
	        titlecell.appendChild(document.createTextNode(mt_tr[i]));
	        row.appendChild(titlecell);
	        tablebody.appendChild(row);
		}
	}
	display_option='P';
}

function removerows (tablebody) {
	var rows = tablebody.getElementsByTagName("tr");
	while (rows.length)
		rows[0].parentNode.removeChild(rows[0]);
}

function fsubmit(form_sub)
{

	if (form_sub.siteid.selectedIndex<1)
	{
		alert("Please select a monitoring site.");
		return false;
	}
	if (form_sub.stdate.value.length > 7 && form_sub.enddate.value.length > 7 && form_sub.stdate.value > form_sub.enddate.value)
	{
		alert("Please make sure the start date is before the end date.");
		return false;
	}
	else {
		checks=false;
	//	for (i=0;i<mt_tr.length;i++)
	//	{
	//		if (document.getElementById(mt_tr[i]).checked==true) checks=true;
	//	}
	//	if (!checks) {
	//		alert("Please select one or more measurements to report.");
	//		return false;
	//	} 
	}

	window.open("",'MCWD_graph','width=640,height=430,scrollbars=no,toolbar=no,menubar=no,directories=no');
	form_sub.submit();
}

</script>

<?php 
require_once 'includes/qp_footer.php';
?>

