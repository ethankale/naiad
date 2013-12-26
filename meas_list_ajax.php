<?php
include 'db.conf';

header('Content-type: application/json');

$con = new PDO("mysql:host=$dbserver;dbname=$dbschema", $dbuser, $dbpass);

$json = '';

//Get a list of measurements taken at the selected sites

$sites=$_GET["siteid"];

$sitessql = implode("','", $sites);

//echo $sitessql;

if (strlen($sitessql) > 1) {
    
    //$stmt = $con->prepare("SELECT `mtypeid`, `mtname`, sum(`cnt`) as `cnt` FROM `meas_at_site` WHERE `siteid` IN ('$sitessql') GROUP BY `mtypeid`,`mtname`");
    $stmt = $con->prepare("
        SELECT `meas`.`mtypeid`, `measurement_type`.`mtname` FROM (
          SELECT `mtypeid`, `siteid`
          FROM `measurements` 
          WHERE `siteid` IN ('$sitessql') 
          GROUP BY `mtypeid`
        ) AS meas
        LEFT JOIN `measurement_type`
        ON `meas`.`mtypeid` = `measurement_type`.`mtypeid`");
    $stmt->execute();

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $json=json_encode($results);

    echo $json;
    
    //Close connection.  Probably unnecessary.
    $con = null;
};
    
?>