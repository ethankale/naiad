<?php
$page_title='Measurements Graphing';
require_once 'includes/wqinc.php';
$pagelevel = PAGE_USER;

require_once 'includes/qp_header.php';
?>
<script src="http://d3js.org/d3.v3.min.js"></script>
<style>

#graph {
  font: 10px sans-serif;
}

.axis path,
.axis line {
  fill: none;
  stroke: #000;
  shape-rendering: crispEdges;
}

.dot {
  stroke: #000;
}

</style>
<h1>Measurements Graphing

<form name="f1" id="f1">
<table class="formtable">

    <tr>
    <td class="tdright">Monitoring Site</td>
    <td>
        <select name="siteid" id="siteid" style="width:300px" >
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
<div id=graph></div>
<div id="calendarDiv"></div>

<script language="javascript">

// Graphing variables.  Outside $(document).ready() because they need to be global.
var theData = [];

var margin = {top: 20, right: 20, bottom: 30, left: 40},
    width = 700 - margin.left - margin.right,
    height = 500 - margin.top - margin.bottom;

var x = d3.time.scale()
    .range([0, width]);

var y = d3.scale.linear()
    .range([height, 0]);

var color = d3.scale.category10();

var xAxis = d3.svg.axis()
    .scale(x)
    .orient("bottom");

var yAxis = d3.svg.axis()
    .scale(y)
    .orient("left");

var format = d3.time.format("%Y-%m-%d");

var idfn = function(d) { return d.id};

var svg = "";


// Wait until the document is ready to set up the graphing area.
$(document).ready( function() {
    
    svg = d3.select("#graph").append("svg")
      .attr("width", width + margin.left + margin.right)
      .attr("height", height + margin.top + margin.bottom)
    .append("g")
      .attr("transform", "translate(" + margin.left + "," + margin.top + ")");
    
    svg.append("g")
      .attr("class", "x axis")
      .attr("transform", "translate(0," + height + ")")
      .call(xAxis)
    .append("text")
      .attr("class", "label")
      .attr("x", width)
      .attr("y", -6)
      .style("text-anchor", "end")
      .text("Date");

    svg.append("g")
      .attr("class", "y axis")
      .call(yAxis)
    .append("text")
      .attr("class", "label")
      .attr("transform", "rotate(-90)")
      .attr("y", 6)
      .attr("dy", ".71em")
      .style("text-anchor", "end")
      .text("X Axis (unit)");
});

function graph() {
    theData = [];
    
    // Add the get variables to the URL by grabbing form input values.
    var url = "measurements_ajax.php?";
    url = $("#mtypeid").val().length > 0 ? url + "&mtypeid=" + $("#mtypeid").val() : url;
    url = $("#siteid").val().length > 0 ? url + "&siteid=" + $("#siteid").val() : url;
    url = $("#stdate").val().length > 0 ? url + "&minDate=" + $("#stdate").val() : url;
    url = $("#enddate").val().length > 0 ? url + "&maxDate=" + $("#enddate").val() : url;
    
    // Query the data and add the graph
    d3.json(url, function(error, data) {
        theData = data;
        
        x.domain(d3.extent(theData, function(d) { return d.value; })).nice();
        y.domain(d3.extent(theData, function(d) { return d.theTime; })).nice();
        
        var points = svg.selectAll(".dot").data(data, idfn);
        
        points.enter().append("circle")
          .attr("class", "dot")
          .attr("r", 3.5)
          .attr("cx", function(d) { return x(d.value); })
          .attr("cy", function(d) { return y(d.theTime); });
          //.style("fill", function(d) { return color(d.species); });
          
        points.exit()
          .remove();
    });
}

</script>

<?php 
require_once 'includes/qp_footer.php';
?>

