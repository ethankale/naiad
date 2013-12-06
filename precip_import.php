<?php
/**
 * this file will convert hundredth inch precip ticks into 15 minute precip 
 * records and insert into precip_measurements
 */
$page_title='Measurements Upload Form';
require_once 'includes/wqinc.php';
$pagelevel = PAGE_USER;

require_once 'includes/qp_header.php';

login_check($pagelevel, $log_user);

//get and check input vars

$uploaddir = 'up_data/';
$uploadfilename=basename($_FILES['datafile']['name']);
$uploadfile = $uploaddir . $uploadfilename;

if (move_uploaded_file($_FILES['datafile']['tmp_name'], $uploadfile)) {
    echo "File  successfully uploaded.\n";
} else {
    echo "Possible file upload attack!\n";
}
$precipdata = file($uploadfile);


$header_rows = $_POST["header_rows"];
$stationid = $_POST["stationid"];

//$path = $_GET["filename"];

print "Parsing data in $uploadfilename<br>\n";
print "found ".(sizeof($precipdata)-$header_rows)." lines of data<br>\n";

//print $precipdata[0];
//Get the day and hour/minute of the start of the datafile
list ($start, $y, $trash) = explode(",",$precipdata[$header_rows]);
list ($sday,$stime) = explode(" ",$start);
print "Day: " . $sday . " Time: " . $stime . "\n";
list ($shh,$smm,$sss) = explode(":",$stime);  
print "Hour: " . $shh . "Minute: " . $smm . "Second: " . $sss . "\n";

$startmin=floor($smm/15)*15; //get the minute for the start of the 15 minute block
$starttime = strtotime("$sday $shh:$startmin:00"); // set timestamp based on starttime 
$cyclelength = 15*60; // cycle length of 15 minutes

$precip_station_data = array(); //create array for station data
$cyclestart=$starttime; //set the beginning of first cycle to start time
$cycleend=$cyclestart + $cyclelength; // set end of first cycle
$precip=0; //init precip amount to 0
print "Converting to 15 minute increment data";flush();
$tick = max(floor(sizeof($precipdata)/20),1);
/**
 * loop for handling the conversion from tipping bucket increments to 15 minute blocks
 * For each row in the data, if:
 *   span is less than cycle (less than 15 minutes), increment the precip
 *   counter (if there is an event) for each entry and keep looping until the next 15 minute
 *   counter is hit;
 * If span is greater than cycle (more than 15  minutes), then
 *   ignore precip val, unless end of current span happens to coincide with
 *   the middle of a cycle?
 */
for ($i=$header_rows; $i < sizeof($precipdata); $i++)
{
	list($daytime,$meterval,$trash) = explode(",",$precipdata[$i]);
	 $t = strtotime($daytime);
	if ($t < $cycleend) 
	{
		if ($meterval > 0) {$precip +=.01;}
	}
	else {
		$precip_station_data[]="$cycleend,$precip";
		$precip=0;
		$i--;   //set back to check this value again after the loop
		$cyclestart = $cycleend;
		$cycleend += $cyclelength;
	}
	if ($i%$tick == 0) {print "."; flush();}
}
print " conversion complete<br>\n";flush();
print "Station data contains ".(sizeof($precip_station_data))." rows of data.<br>\n";

$PS_ID = $stationid;
$qdc="Select * from precipitation_measurements WHERE ";
$dup=0;
$dups=array();
$s=sizeof($precip_station_data);
for($i=0; $i<$s; $i++)
{
	list($dt,$pi)=explode(",",$precip_station_data[$i]);
	$dtf =  strftime ("%Y-%m-%d %H:%M:%S",$dt);
	$q=$qdc. " pmdate='$dtf' and PS_ID='$PS_ID'";
	//print $q."<br>\n";
	$res=mysqli_query($mysqlid,$q);
	if (mysqli_num_rows($res)>0) {
		$dups[]=$precip_station_data[$i];
		unset($precip_station_data[$i]);
	}
}
print "<br>\n".sizeof($dups)." duplicates.";
print "<br>\n".sizeof($precip_station_data)." new entries.";

if (sizeof($dups)>1 && sizeof($precip_station_data)==0) 
{
	print "<br>\nThis file apears to have been uploaded already.";
	exit;
}

print "Storing to database";flush();
$tick = floor(sizeof($precip_station_data)/20);
$ins_start = microtime(TRUE);
$qi= "INSERT INTO precipitation_measurements (pmdate, inches, PS_ID) VALUES ";
$q=$qi;
$i=0;
foreach ($precip_station_data as $row) 
{
	list($dt,$pi)=explode(",",$row);
	$dtf =  strftime ("%Y-%m-%d %H:%M:%S",$dt);
	$q.= "('$dtf','$pi','$PS_ID'), ";
	if ($i%100==99) {
		$q=substr($q, 0, -2);
		mysqli_query($mysqlid,$q);
		$q=$qi;
	}
	if ($i%$tick == 0) {print "."; flush();}
	$i++;
}
if (strlen($q) > strlen($qi)) { //check length of $q - see if there are additional rows to insert
	$q=substr($q, 0, -2);
	mysqli_query($mysqlid,$q);
}


$ins_end = microtime(TRUE);
$dur = $ins_end-$ins_start;
print "Stored ".$i." rows to database<br>\n";flush();
print "File uploaded and converted successfully<br>\n";
print "<a href=\"#dups\" onclick=\"document.getElementById('dups').display='';\">See Duplicates</a>";
print "<div id='dups' name='dups' style='display:none'>";
for ($i=0;$i<sizeof($dups);$i++)
{
	list($dt,$pi)=explode(",",$dups[$i]);
	$dtf =  strftime ("%Y-%m-%d %H:%M:%S",$dt);
	print "$dtf - $pi<br>\n";
}
print "</div>";
//print "Duration: ".$dur." sec\n";
//print ($i/$dur)." rows per second.\n";
unlink($uploadfile);
require_once 'includes/qp_footer.php';
