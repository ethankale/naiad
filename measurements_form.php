<?php
/* this page displays the search form for requesting measurement data */
$page_title='Measurements Data Request Form';
require_once 'includes/wqinc.php';
$pagelevel = PAGE_USER;

require_once 'includes/qp_header.php';
?>

<form action="measurements_report.php" method="get" name="f1" id="f1" onsubmit="return validate(this);">
<input type="hidden" name="report_type" value="raw">

<h1>Water Quality Measurements Data Download</h1>
<hr />
<table class="formtable">

<!--Waterbody type choice section.-->

<tbody id="wb_type">
    <tr><td>&nbsp;</td><td><b>Choose a Waterbody Type</b></td></tr>
    <tr><td class="tdright"></td><td><input type="radio" id="wbody_type_l" name="wbody_type" value="L" onclick="update_waterbodies('L',document.getElementById('waterbodyid'))">Lake</td></tr>
    <tr><td class="tdright"></td><td><input type="radio" id="wbody_type_s" name="wbody_type" value="S" onclick="update_waterbodies('S',document.getElementById('waterbodyid'))">Stream</td></tr>
    </tbody>

<!--Waterbody selection section.-->
    
<tbody id="wbody_select" style="display: none">
    <tr><td class="tdright">Waterbody</td><td><select name="waterbodyid" id="waterbodyid" onchange="update_sites(this.value,document.getElementById('siteid'))" style="width:300;">
    <option value="">Select Waterbody Type</option>
    </select></td></tr>
</tbody>

<!--Site selection section.-->

<tbody id="site_select" style="display: none">
    <tr><td class="tdright">Monitoring Site</td><td><select name="siteid[]" size=4 multiple id="siteid" style="width:300px" onchange="show_measurements();">
    <option value="">Select Waterbody Type</option></select></td></tr>
</tbody>

<!--Measurements selection section.-->

<tbody id="measure_select_head" style="display: none">
    <tr><td>&nbsp;</td><td>&nbsp;</td></tr>
    <tr><td>&nbsp;</td><td><b>Measurements</b></td></tr>
    <tr id="meas_header";><td>&nbsp;</td><td>Please select sites to monitor</td></tr>
    
    <tr id="meas_storet";       style="display:none;"> <td>&nbsp;</td><td><input type="checkbox" name="storet_output" value="true"  onclick="show_eq_proj(this.checked)">EQUiS Output Format</td></tr>
    <tr id="meas_storet_proj"   style="display:none;"> <td>&nbsp;</td><td>Project*: <select name="storet_proj_id"><option value="ANY">ANY</option>
    <tr id="checkall";          style="display:none;"> <td>&nbsp;</td><td><input type="checkbox" id="check_all" value="false">Check All Measurements</td></tr>

    <?php 
        $sql_proj = "SELECT * FROM mon_projects WHERE active=1 ORDER BY proj_id"; 
        $res_proj = mysqli_query($mysqlid,$sql_proj);
        print mysqli_error($mysqlid);
        while($row=mysqli_fetch_array($res_proj)) 
        { print "<option value=\"".$row['proj_id']."\">".$row['proj_id']."</option>"; }
        mysqli_free_result($res_proj);
    ?>

    </select>
    <br><br></td></tr>
    <tr id="meas_profile"; style="display:none;"><td>&nbsp;</td><td><input type="checkbox" name="lake_profiles" id="lake_profiles" value="1" onclick="show_lake_measurements(this.checked)"> Profile Measurements</td></tr>
</tbody>
<tbody id="measure_select" style="display: none">
    
    <!--<tr id="select_all_tr" >
      <td class="tdright"></td>
      <td> <input id="select_all_check" type="checkbox" class="checkall"> Check All </td>
    </tr>-->
     
    <?php 
    /* notes for javascript functions:
     * $mt_tr is an array of measurement types
     * $mt_types is an array that indicates what waterbody type a measurement is associated with 
     * $mt_profile is an array that indicates whether a measurement item is available as profile data
     * $mt_types and $mt_profile have the same index positions as $mt_tr to associate the data together 
     */

    /*
    load the measurement types and display  the associated checkboxes 
    */
    $query = "SELECT mtypeid, mtname, lake, stream, l_profile FROM measurement_type ORDER BY disp_order, active desc, mtypeid";
    $res = mysqli_query($mysqlid, $query);
    $mt_tr=array();
    $mt_types=array();
    $mt_profile=array();
    
    while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC))
    {
        print "<tr id=\"".$row["mtypeid"]."_tr\" style=\"display:none;\"><td class=\"tdright\"></td><td><input type=\"checkbox\" class=\"measurement_check\" name=\"mtypeid[]\" value=\"".$row["mtypeid"]."\" id=\"".$row["mtypeid"]."\">".$row["mtname"]."</td></tr>\n";
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

</tbody>

<!--Finally, the measurement options section.-->

<tbody id="measure_options" class="measure_options" style="display: none">
    <tr><td>&nbsp;</td><td>&nbsp;</td></tr>
    <tr><td>&nbsp;</td><td><b>Query Options</b></td></tr>
    <tr><td class="tdright">Averaging Period</td><td><input type="radio" name="averaging" id="averaging_n" value="n" checked>none<br>
        <input type="radio" name="averaging" id="averaging_w" value="w">Weekly<br>
        <input type="radio" name="averaging" id="averaging_m" value="m">Monthly<br>
        <input type="radio" name="averaging" id="averaging_y" value="y">Yearly</td></tr>
    <tr><td class="tdright">Start Date</td><td><input type="text" name="stdate" id="stdate" size="15" class="calendarSelectDate"></td></tr>
    <tr><td class="tdright">End Date</td><td><input type="text" name="enddate" id="enddate" size="15" class="calendarSelectDate"></td></tr>
    <tr><td class="tdright"></td><td><input type="checkbox" name="downloadcsv" value="true">Download CSV<br>

    </td></tr>

    <tr><td></td><td><button type="submit">Submit</button></td></tr>
</tbody>

</table>
</form>
<p class="measure_options" style="display: none"><b>*</b> For data prior to 2011 select "ANY" for project  
<div id="calendarDiv"></div>

<!--Following are the functions that control the table sections above; primarily show/hide elements.-->

<!--Some constants, loaded when the page loads.-->

<script language="javascript">
<?php 
/* notes for javascript functions:
 * $wbid_s is an array of stream waterbody ids
 * $wbid_l is an array of lake waterbody ids
 * $wbname_s is an array of stream waterbody names
 * $wbname_l is an array of lake waterbody names
 * $mt_types and $mt_profile have the same index positions as $mt_tr to associate the data together 
 * $s_sid - list of monitoring site ids
 * $s_wbid - list waterbodies the sites are associated with ($s_sid[i] matches to $s_wbid[i]) 
 * $s_name - list of monitoring site names
 * $s_type - Stream or lake (S or L);
 */

/*
 * load the waterbodies and monitoring sites into the javascript arrays 
 */
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
    $query = "SELECT siteid,site_description, waterbody_id, monitor_type FROM sites_list ORDER BY active aSC, site_description asc, siteid";
    $res = mysqli_query($mysqlid, $query);
    while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC))
    {
        $s_sid[]= $row['siteid'];
        $s_wbid[]= $row['waterbody_id'];
        $s_name[]= $row['siteid']." - ".$row['site_description'];
        $s_type[]=$row['monitor_type'];
    }
    mysqli_free_result($res);
    
// print out the javascript arrays

    print 'var site_ids=new Array("'.implode('","', $s_sid)."\");\n";
    print 'var site_wbids=new Array("'.implode('","', $s_wbid)."\");\n";
    print 'var site_names=new Array("'.implode('","', $s_name)."\");\n";
    print 'var site_types=new Array("'.implode('","', $s_type)."\");\n";
        
    print 'var mt_tr=new Array("'.implode('","', $mt_tr)."\");\n";
    print 'var mt_type=new Array("'.implode('","', $mt_types)."\");\n";
    print 'var mt_profile=new Array("'.implode('","', $mt_profile)."\");\n";
    print "var wbtype='';\n";
?>

$.ready( $('#wb_type input[id="wbody_type_l"]').prop("checked", false) );

$("#check_all").click(function () {
    $("input[name^=mtypeid]").prop('checked', $(this).prop('checked'));
});


//Called when a Waterbody Type is chosen.

function update_waterbodies(wtype, selectbox) {
    $("#wbody_select").show("fast");
    removeAllOptions(selectbox);
    addOption(selectbox, "", "Select Waterbody", "");
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
    addOption(document.getElementById('siteid'), "", "Select Waterbody", "");
    hide_measurements();

}

//Hide the measurements & options sections.

function hide_measurements()
{
    $("#measure_select, #measure_select_head, #meas_profile, #checkall, .measure_options").hide("fast");
}

//For EQuIS output, hide the profile option and the measurement types.
// We show the measure_options, because they might be hidden if
// the EQuIS box is checked  when the page is reloaded.

function show_eq_proj(chk)
{
    $('.measure_options, #checkall').show('fast');
    if (chk) {
        $('#measure_select, #meas_profile').hide('fast');
    }
    else {
        $('#measure_select, #meas_profile').show('fast');
    }
}

<!--Called when monitoring sites are selected.-->

function show_measurements()
{
    var sites = $("#siteid").val();
    
    if (sites.length < 1)
    {
        hide_measurements();
    } 
    else
    {
        var siteid = sites.join("&siteid[]=");
        $("#measure_select").html("<tr><td class='tdright'></td><td>Loading Measurements...</td></tr>");
        var url = 'meas_list_ajax.php?siteid[]=' + siteid;
        
        $.getJSON(url, function(data) { 

            $("#measure_select").html("");
            mt_tr = [];
            for (i=0; i<data.length; i++) {
                mt_tr[i] = data[i]["mtypeid"];
                var theRow = '<tr id="' +
                    mt_tr[i] + 
                    '_tr"><td class="tdright"></td><td><input type="checkbox" name="mtypeid[]" value="' +
                    mt_tr[i] +
                    '" id="' +
                    mt_tr[i] +
                    '">' +
                    data[i]["mtname"] + 
                    '</td></tr>'
                $("#measure_select").append(theRow);
            };
            
            if(!(typeof(measload)=="undefined")) {
                for (i=0; i<measload.length; i++) {
                    var measType = "#" + measload[i];
                    console.log(measType);
                    $(measType).prop("checked", true);
                }
                
                measload = undefined;
            }
            
        });
        

        
        $("#measure_select_head, measure_options, #checkall").show("fast");
        if ($('#meas_storet input:checked').length > 0)
        {
            show_eq_proj(true);
        }
        else
        {
            $("#measure_select, .measure_options, #checkall").show("fast");
        }
        
        if(document.getElementById('f1').wbody_type[0].checked ) {
            show_lake_measurements(document.getElementById('lake_profiles').checked);
        }
        
        if(document.getElementById('f1').wbody_type[1].checked ) {
            show_stream_measurements();
        }

    }
}

function show_lake_measurements(show_profile)
{
    document.getElementById('meas_header').style.display="none";
    document.getElementById('meas_storet').style.display="";
    document.getElementById('meas_storet_proj').style.display="";
    if (show_profile) {
        match="P";
    }
    else {
        match="";
    }
}

function show_stream_measurements()
{
    document.getElementById('meas_storet').style.display="";
    document.getElementById('meas_storet_proj').style.display="";
    document.getElementById('meas_header').style.display="none";
    
    document.getElementById('lake_profiles').checked=false;
    
    for (i=0;i<mt_tr.length;i++)
    {
        if (mt_type[i] == "B" || mt_type[i] == "S")
        {
            document.getElementById(mt_tr[i]+'_tr').style.display="";
        }
        else 
        {
            document.getElementById(mt_tr[i]+'_tr').style.display="none";
            document.getElementById(mt_tr[i]).checked=false;
        }
    }
}

<!--Runs after the waterbody select box is updated.-->

function update_sites(wbid, selectbox) {
    $("#site_select").show("fast");
    
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
    else if (wbid==0)
    {
        $("#site_select").hide("fast");
        hide_measurements()
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

function validate(form_sub)
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
    if (form_sub.storet_output.checked){
        if (form_sub.stdate.value.length < 7 || form_sub.enddate.value.length < 7)
        {
            alert("Please enter a start and end date.");
            return false;
        }
    }
    else {
        checks=false;
        for (i=0;i<mt_tr.length;i++)
        {
            if (document.getElementById(mt_tr[i]).checked==true) checks=true;
        }
        if (!checks) {
            alert("Please select one or more measurements to report.");
            return false;
        } 
    }
    return true;
}

<?php 
// code to check the get string if this is a linked search (such as from the search again link on the results page.
// fills in fields and runs the appropriate js functions as the page loads.
if ($_GET["wbody_type"]) {
    if ($_GET["wbody_type"] =="L") print "document.getElementById('wbody_type_l').checked=true;\nupdate_waterbodies('L',document.getElementById('waterbodyid'));\n";
    if ($_GET["wbody_type"] =="S") print "document.getElementById('wbody_type_s').checked=true;\nupdate_waterbodies('S',document.getElementById('waterbodyid'));\n";
    

    if ($_GET["waterbodyid"])
    {
        print "wb_opt=document.getElementById('waterbodyid');
            for (i=0;i<wb_opt.length;i++)
            {
                if (wb_opt[i].value==".$_GET["waterbodyid"].")
                    wb_opt.selectedIndex=i;
            }
            update_sites(".$_GET["waterbodyid"].",document.getElementById('siteid'));
            ";
    }
    if ($_GET['siteid'])
    {
        print 'var sitesload=new Array("'.implode('","', $_GET['siteid'])."\");\n";
        
        if ($_GET['mtypeid']) print 'var measload=new Array("'.implode('","', $_GET['mtypeid'])."\");\n";
        
        print "sites_opt=document.getElementById('siteid');
            for (i in sitesload)
            {
                for (j=0;j<sites_opt.length;j++)
                {
                    if (sites_opt[j].value==sitesload[i])
                        sites_opt[j].selected=true;
                }
            }
            show_measurements();
            ";
        
    }
    if ($_GET['lake_profiles']==1) print "document.getElementById('lake_profiles').checked=true; show_lake_measurements(true);\n";

    print "document.getElementById('averaging_".$_GET['averaging']."').checked=true;\n";
    
    if ($_GET['stdate']) print "document.getElementById('stdate').value='".$_GET['stdate']."';\n";
    if ($_GET['enddate']) print "document.getElementById('enddate').value='".$_GET['enddate']."';\n";
    //print "popUpCal.init();";
}
?>
</script>

<?php 
require_once 'includes/qp_footer.php';
?>

