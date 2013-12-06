<?php
$page_title='Measurements Upload Form';
require_once 'includes/wqinc.php';
$pagelevel = PAGE_USER;

require_once 'includes/qp_header.php';

login_check($pagelevel, $log_user);
?>
<form action="upload_measurements2.php" method="POST" name="f1" id="f1" onsubmit=""  enctype="multipart/form-data">
<input type="hidden" name="report_type" value="raw">
<table class="formtable">
	<tr><th colspan=2>Water Quality Measurements Data Upload</th></tr>
	<tr><td>File</td><td><input type="file" name="datafile"></td></tr>
	<tr><td colspan=2>File to be uploaded should be a CSV file.</td></tr>
	<tr><td>Data Type</td><td><input type="radio" name="wbody_type" value="L" >Lake &nbsp; 	 <input type="radio" name="wbody_type" value="S" >Stream</td></tr>
	<tr><td>Header Row</td><td><input type="text" name="header_row" value="1" size=3></td></tr>
	<tr><td>First Data Row</td><td><input type="text" name="first_data_row" value="2" size=3></td></tr>
	<tr><td>Column Separator</td><td><input type="text" name="delimiter" value="," size=3></td></tr>
	<tr><td>Text Qualifier</td><td><input type="text" name="encaps" value='"' size=3></td></tr>
	<tr><td></td><td><button type="submit">Submit</button></td></tr>
</table>
</form>
<?php 
require_once 'includes/qp_footer.php';
?>