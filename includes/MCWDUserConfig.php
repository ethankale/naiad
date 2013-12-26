<?php
//for more info, go to http://www.bobk4.com/simpleuser

    //the initial values of these fields are set to "..." so the scripts will know if you actually editted them or not.
    //SET ALL OF THE FOLLOWING VALUES before running anything else

    //where your user database is located
    define("USERDBHOST",$dbserver);
    //the user database login
    define("USERDBUSER",$dbuser);
    //user database password
    define("USERDBPASS",$dbpass);
    //name of user database (all lowercase please)
    define("USERDBNAME",$dbschema);
    //prefix to put on user tables (all lowercase please)
    define("USERTABLEPREFIX","wqdb_user_");

    //set this to a value that is not already being used in your $_SESSION. the user info will be stored here
    //for example, it's pre-set to "user" so logged-in user info will be stored in $_SESSION['user']
    //userID will be $_SESSION['user']['userID']
    define("USERSESSION","WQ_User");

    //the minimum required length for password
    define("MINPASSLENGTH",6);
    define("USERLOGINPAGE","user.php");

?>