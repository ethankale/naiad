<?php
$page_title='Login';
require_once 'includes/wqinc.php';
print "<pre>\n";
$q1="UPDATE measurements set mtypeid='ECOLI' WHERE  mtime <  '2005-01-01'  AND siteid LIKE 'LDU01' AND mtypeid LIKE  'TP'";
$q2="UPDATE measurements set mtypeid='TP' WHERE  mtime <  '2005-01-01'   AND siteid LIKE 'LDU01' AND mtypeid LIKE  'CHLA'";
$q3="UPDATE measurements set mtypeid='CHLA' WHERE  mtime <  '2005-01-01'    AND siteid LIKE 'LDU01' AND mtypeid LIKE  'ECOLI'";

$q = "INSERT INTO `wqdb`.`general_values` (`fieldname`, `fieldvalue`) VALUES ('open_date', '2010-12-31')";//;SELECT m_id,mtime,siteid,depth,value from measurements where mtypeid='TP' and siteid='LSN01' order by mtime asc";
$res = mysqli_query($mysqlid, $q1);
printf("Errormessage: %s\n", mysqli_error($mysqlid));
$res = mysqli_query($mysqlid, $q2);
printf("Errormessage: %s\n", mysqli_error($mysqlid));
$res = mysqli_query($mysqlid, $q3);
printf("Errormessage: %s\n", mysqli_error($mysqlid));
exit;
while ($row = mysqli_fetch_array($res, MYSQLI_ASSOC))
{
	$siteid=$row['siteid'];
	$mtime=$row['mtime'];
	$m_id=$row['m_id'];
	$value = $row["value"];
	print "$m_id    $mtime    $value\n";
/*	$q2="SELECT m_id, mtime,siteid,depth from measurements where mtypeid='SD' and depth=0 AND siteid='$siteid' and mtime='$mtime'";
	$res2 = mysqli_query($mysqlid, $q2);
/*	if (mysqli_num_rows($res2)>0)
	{
		$q3 = "DELETE from measurements where m_id='$m_id'";
		
		$res3 = mysqli_query($mysqlid, $q3);
		print $q3."\n";
	}
	else print "none found $m_id - $siteid $mtime\n";*/
}