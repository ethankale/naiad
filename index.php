<?php
if (isset($_GET["access"])){
	setcookie("access",1,0);
}
header("Location: measurements_form.php");