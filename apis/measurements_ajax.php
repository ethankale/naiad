<?php
include '../db.conf';

header('Content-type: application/json');



//Get all measurements within the specified parameters.
// Does not include duplicates.
// By default looks for surface values (between 0 & 1 meters).
// Limits to 1,000 returns (so as not to overwhelm graphing).

if (isset($_GET["mtypeid"]) && isset($_GET["siteid"])) {

    $con = new PDO("mysql:host=$dbserver;dbname=$dbschema", $dbuser, $dbpass);
    $json = '';

    $conditions = "";
    $params     = array();
    
    $sql = 'SELECT `m_id` as id, `value`, `mtypeid`, date_format(`mtime`, "%Y-%m-%d") as date, UNIX_TIMESTAMP(`mtime`) * 1000 as theTime, `monitoring_sites`.`siteid`, `waterbody_id`, `depth`, `mnotes`
        FROM `measurements` 
        LEFT JOIN `monitoring_sites`
          ON `monitoring_sites`.`siteid` = `measurements`.`siteid`
        WHERE `duplicate` = 0 ';
    
    // Populate conditions
    $conditions = ' AND `mtypeid` = :mtypeid ';
    $params['mtypeid'] = $_GET["mtypeid"];
    
    if (isset($_GET["siteid"])) {
        $conditions = $conditions . ' AND `monitoring_sites`.`siteid` = :siteid ';
        $params['siteid'] = $_GET["siteid"];
    };
    if (isset($_GET["maxDepth"])) {
        $conditions = $conditions . ' AND `depth` <= :maxDepth ';
        $params['maxDepth'] = $_GET["maxDepth"];
    };
    if (isset($_GET["minDepth"])) {
        $conditions = $conditions . ' AND `depth` >= :minDepth ';
        $params['minDepth'] = $_GET["minDepth"];
    };
    if (isset($_GET["maxDate"])) {
        $conditions = $conditions . ' AND `mtime` <= :maxDate ';
        $params['maxDate'] = $_GET["maxDate"];
    };
    if (isset($_GET["minDate"])) {
        $conditions = $conditions . ' AND `mtime` >= :minDate ';
        $params['minDate'] = $_GET["minDate"];
    };
    if (isset($_GET["wbody"])) {
        $conditions = $conditions . ' AND `waterbody_id` >= :wbody ';
        $params['wbody'] = $_GET["wbody"];
    };
    
    // Prepare & execute SQL, output JSON.
    $stmt = $con->prepare($sql . $conditions);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $json=json_encode($results);
    echo $json;
    
    //Close connection.  Probably unnecessary.
    $con = null;
};
    
?>