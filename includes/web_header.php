<html>
<head><title><?php print $page_title;?></title>
<link href="public.css" type="text/css" rel="stylesheet">
<script src="includes/calendar.js"></script>
<link href="includes/calendar.css" rel="stylesheet">
<script language="javascript">
function plot_pop(url)
{
	window.open(url,'MCWD_graph','width=660,height=460');
			//,scrollbars=no,toolbar=no,menubar=no,directories=no');
}
</script>
</head>
<body>
<div class="header"><a href="/WQDB/">MCWD WATER QUALITY DATA</a><br>
<div class="navheader"><br>
<a href="measurements_form.php">Measurement data</a> |
<a href="precip_form.php">Precipitation data</a> |
<a href="plot_form.php">Data Graphs</a>
</div>
</div><div class="body">