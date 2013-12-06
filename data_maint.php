<?php
$page_title='Data Maintenance';
require_once 'includes/wqinc.php';
$pagelevel = PAGE_ADMIN;

require_once 'includes/qp_header.php';

login_check($pagelevel, $log_user);
?>
<a href="del_meas.php">Delete Measurement Values</a><br><br>
<a href="dup_check.php">Correct Duplicate Values</a><br><br>
<a href="mon_projects.php">Monitoring Projects</a><br><br>
<a href="mon_procedures.php">Sample Procedures</a><br><br>
<a href="mon_gear.php">Gear Configurations</a><br><br>
<a href="settings.php">General System Settings</a><br><br>
<?php
require_once 'includes/qp_footer.php';
?>	