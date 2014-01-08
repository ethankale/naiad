<html>
<head><title><?php print $page_title;?></title>
<link href="public.css" type="text/css" rel="stylesheet">
<script src="includes/jquery-1.6.1.min.js"></script>

<!-- Calendar javascript library -->
<script src="includes/calendar.js"></script>
<link href="includes/calendar.css" rel="stylesheet">

<!-- Leaflet (mapping) javascript library -->
<script src="http://cdn.leafletjs.com/leaflet-0.7.1/leaflet.js"></script>
<link rel="stylesheet" href="http://cdn.leafletjs.com/leaflet-0.7.1/leaflet.css" />


<script language="javascript">
function plot_pop(url)
{
    window.open(url,'MCWD_graph','width=660,height=460');
            //,scrollbars=no,toolbar=no,menubar=no,directories=no');
}
</script>
</head>
<body>
<div class="header"><?php echo ORG ?> Water Quality Data<br>
<?php if ( $log_user->is_logged_in() || isset($_COOKIE["access"])) {?><div class="navheader"><?php 
print $log_user->greeting("user.php","user.php?action=logout");
?><br><br></div><?php } ?>
<div class="navheader">
<a href="measurements_form.php">Measurement data</a> |
<a href="precip_form.php">Precipitation data</a> |
<a href="plot_form.php">Data Graphs</a>
<?php if($log_user->is_logged_in()) {?> |
<a href="add_measurement.php">Measurement entry</a> |
<a href="upload_measurements.php">Measurement upload</a> |
<a href="precip_add.php">Precip entry</a> |
<a href="precip_upload.php">Precip upload</a><?php }?>
</div>
<?php if($log_user->is_admin()) {?><div class="navheader"><b>ADMIN</b> <a href="waterbodies.php">Waterbodies</a> | 
<a href="mon_sites.php">Monitoring Sites</a> |
<a href="meas_types.php">Measurement types</a> |
<a href="precip_sites.php">Precipitation Stations</a> |
<a href="data_maint.php">Data Maintenance</a> |
<a href="user_manage.php">Users</a>
</div><?php }?>
</div><div class="body">