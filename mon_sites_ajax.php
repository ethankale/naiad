<?php
include 'db.conf';

header('Content-type: application/json');

$con = new PDO("mysql:host=$dbserver;dbname=$dbschema", $dbuser, $dbpass);

$json   = '';
$qry    = '';
$sites  = array();

// Get site info, either from supplied list of IDs or all sites.
if (isset($_GET["siteid"])) {
    
    $sites      = $_GET["siteid"];
    $inQuery    = implode(",", array_fill(0, count($sites), '?'));
    
    $qry    = 'SELECT `siteid`, `latitude`, `longitude`, `site_description`
        FROM `monitoring_sites`
        WHERE `siteid` IN (' . $inQuery . ')';
    //echo $qry;
} else {
    
    $qry    = 'SELECT `siteid`, `latitude`, `longitude`, `site_description`
        FROM `monitoring_sites`';
    
};

$stmt = $con->prepare($qry);
$stmt->execute($sites);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

$json=json_encode($results);

echo $json;

$con = null;

?>