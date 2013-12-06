<?php
/**
 * this file will save manual (non tipping bucket) data
 */
$page_title='Precipitation Upload Form';
require_once 'includes/wqinc.php';
$pagelevel = PAGE_USER;

require_once 'includes/qp_header.php';

login_check($pagelevel, $log_user);

//get and check input vars

$uploaddir = 'up_data/';
$uploadfilename=basename($_FILES['datafile']['name']);
$uploadfile = $uploaddir . $uploadfilename;

if (move_uploaded_file($_FILES['datafile']['tmp_name'], $uploadfile)) {
    echo "File '$uploadfile' successfully uploaded.\n";
} else {
    echo "Possible file upload attack!\n";
    exit();
}

$precipdata = file($uploadfile);

$header_rows = $_POST["header_rows"];
$stationid = $_POST["stationid"];

print "Parsing data in $uploadfilename<br>\n";
print "found ".(sizeof($precipdata)-$header_rows)." lines of data<br>\n";



$precip_station_data = array(); //create array for station data

$precip=0; //init precip amount to 0
print "Importing data";flush();
$tick = max(floor(sizeof($precipdata)/20),1);

//Put all date/time and precip quantity pairs into an array

for ($i=$header_rows; $i < sizeof($precipdata); $i++)
{
	list($daytime,$p,$trash) = explode(",",$precipdata[$i]);
	$t = strtotime($daytime);
	$precip_station_data[]="$t,$p";
	if ($i%$tick == 0) {print "."; flush();}
}
print " Conversion complete<br>\n\n"; flush();

$qdc="Select * from precipitation_measurements WHERE ";
$PS_ID = $stationid;
$dup=0;
$dups=array();
$s=sizeof($precip_station_data);

print "Storing to database";flush();

//Loop through array, importing in chunks as we go

$tick = floor(sizeof($precip_station_data)/20);
$ins_start = microtime(TRUE);
$qi= "INSERT INTO precipitation_measurements (pmdate, inches, PS_ID) VALUES ";
$q=$qi;
$i=0;
$j=0;
foreach ($precip_station_data as $row) 
{
	list($dt,$pi)=explode(",",$row);
	$dtf =  strftime("%Y-%m-%d %H:%M:%S",$dt*1);
	$q.= "('$dtf','$pi','$PS_ID'), ";
	if ($i%100==99) {
		$q=substr($q, 0, -2);
		mysqli_query($mysqlid,$q);
		$q=$qi;
        $j++;
	}
	if ($i%$tick == 0) {print "."; flush();}
	$i++;
}
if (strlen($q) > strlen($qi)) { //check length of $q - see if there are additional rows to insert
	$q=substr($q, 0, -2);
	mysqli_query($mysqlid,$q);
    $j++;
}

$ins_end = microtime(TRUE);
$dur = $ins_end-$ins_start;
print "Stored ".$i." rows to database<br>\n";flush();
print "File uploaded successfully<br>\n";

print "</div>";
//print "Duration: ".$dur." sec\n";
//print ($i/$dur)." rows per second.\n";
unlink($uploadfile);
require_once 'includes/qp_footer.php';
