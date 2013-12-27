<?php 
// general functions and statup calls - should start all pages
// load db connection info and user management class
require('db.conf');
require_once 'MCWDUserConfig.php';
require_once 'MCWDUser.class.php';
session_start();
//set constant flags for output type, variable format and user levels
define("OF_CSV",1);
define("OF_TBL",2);
define("DATA_FLOAT2","f");
define("DATA_FLOAT5","d");
define("DATA_DAYTIME","t");
define("DATA_INT","i");
define("DATA_STRING","s");
define("PAGE_OPEN",0);
define("PAGE_USER",1);
define("PAGE_ADMIN",2);
define("ORG","Prior Lake - Spring Lake Watershed District");

$mysqlid = mysqli_connect($dbserver, $dbuser, $dbpass, $dbschema);
if (!$mysqlid) {
   printf("Can't connect to MySQL Server. Errorcode: %s; Host: %s\n", mysqli_connect_error(), $dbserver);
   exit;
} 

// instantiate the user class - will check the session for logged in user
$log_user=new MCWDUser();

// variation of mysqli_stmt_bind_param to allow variable length arrays
function dynamic_mysqli_bind_param($stmt, $types, $vals){
    call_user_func_array('mysqli_stmt_bind_param', array_merge(array($stmt, $types), refValues($vals)));
}

// used by above function to correct references based on PHP version
function refValues($arr)
{
    if (strnatcmp(phpversion(),'5.3') >= 0) //Reference is required for PHP 5.3+
    {
        $refs = array();
        foreach($arr as $key => $value)
            $refs[$key] = &$arr[$key];
         return $refs;
     }
     return $arr;
}

//comparison function for usort - for multidim arrays - checks $_GET for column to compare and asc/desc 
function compare_vals($a, $b) 
{
    $sortkeyGet = isset($_GET['sortkey']) ? $_GET['sortkey'] : null;
    $sortDirGet = isset($_GET['sortdir']) ? $_GET['sortdir'] : null;
    
    $key = $sortkeyGet ? $sortkeyGet : "time";
    if ($a[$key] == $b[$key]) {
        if ($GLOBALS['profile'])
            if ($a['depth'] == $b['depth']) {
                return 0;
            }
            else return (($a['depth'] < $b['depth']) ? -1 : 1);
    }
    $flip = $sortDirGet == "DESC" ? -1:1;
    return $flip*(($a[$key] < $b[$key]) ? -1 : 1);
}

//checks the user level against the permision level required by the page, displays error and aborts page if level too low.
function login_check($pagelevel,&$user)
{
    if ($pagelevel==PAGE_OPEN) {
        return;
    }
    else if ($pagelevel==PAGE_USER && $user->is_logged_in())
    {
        return;
    }
    else if ($user->is_admin())
    {
        return;
    }
    print "insufficient permissions";
    exit; 
}

//checks the user level against the permision level required by the page, displays error and aborts page if level too low.
function login_results_date($req_date, &$user, $mysqlid)
{
    if ($user->is_logged_in())
    {
        return $req_date;
    }
    $last_date = $req_date; /// commented out next line due to insert issues with general_values table
//    $last_date = get_general_value_table("open_date", $mysqlid);
    if (!$req_date)
        return $last_date;
    return min($last_date, $req_date);
}

// get a value from the general settings table
function get_general_value_table($field,$mysqlid)
{
    $query = "SELECT fieldvalue FROM general_values WHERE fieldname =?";
    $stmt = mysqli_prepare($mysqlid, $query); 
    if($stmt==false) {printf("Errormessage: %s\n", mysqli_error($mysqlid));exit;}
    mysqli_stmt_bind_param($stmt, "s", $field);    
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $val);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    return $val;

}
// get the default lab
function get_default_lab_id($mysqlid)
{
    return get_general_value_table("default_lab",$mysqlid);
}

//replace  str_getcsv if php < 5.3
if (!function_exists('str_getcsv')) { 
function str_getcsv($input, $delimiter=',', $enclosure='"', $escape=null, $eol=null) { 
    $expr="/$delimiter(?=(?:[^\"]*\"[^\"]*\")*(?![^\"]*\"))/";
    $fields = preg_split($expr,trim($input));
    $fields = preg_replace("/^\"(.*)\"$/s","$1",$fields);
    return $fields; 
} 
  
}
?>