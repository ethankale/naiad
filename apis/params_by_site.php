<?php
include '../db.conf';

header('Content-type: application/json');

//List parameters by site.
// Does not include duplicates.
// By default looks for surface values (between 0 & 1 meters).
// Limits to 1,000 returns (so as not to overwhelm graphing).

if (isset($_GET["siteid"])) {

    $con = new PDO("mysql:host=$dbserver;dbname=$dbschema", $dbuser, $dbpass);
    $json = '';

    $params     = array();
    
    $sql = 'SELECT `measurements`.`mtypeid` , `measurement_type`.`mtname`
        FROM `measurements`
        LEFT JOIN `monitoring_sites`
          ON `measurements`.`siteid` = `monitoring_sites`.`siteid`
        LEFT JOIN `measurement_type`
          ON `measurements`.`mtypeid` = `measurement_type`.`mtypeid`
        WHERE `measurements`.`siteid` = :siteid
        GROUP BY `measurements`.`mtypeid`';
    
    // Populate conditions
    $params['siteid'] = $_GET["siteid"];
    
    // Prepare & execute SQL, output JSON.
    $stmt = $con->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $json=json_encode($results);
    echo $json;
    
    //Close connection.  Probably unnecessary.
    $con = null;
};
    
?>