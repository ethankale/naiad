<?php
/********************************************
Copyright (c) 2011, Bob Kennedy
kennedy7@gmail.com
http://www.bobk4.com/simpleuser

You are free to edit and redistribute this
class as you see fit as long as the
copyright statement above if left intact.
If you like it, let me know!
********************************************/

/*
class SimpleUser
This class handles the registration, editing, deleting, and authentication of user accounts, including the display of forms neccesary for those purposes.
---------------------
Variables
---------------------
private $dbname;
Name of the DB (set in SimpleUserConfig.php)

private $dbuser;
Name of DB user (set in SimpleUserConfig.php)

private $dbpass;
DB Password (set in SimpleUserConfig.php)

private $dbhost;
Address of DB host (set in SimpleUserConfig.php)

private $prefix;
Prefix to prepend to table names (set in SimpleUserConfig.php)

private $dblink;
Connection link once DB connection is established

private $user_session;
The place inn $_SESSION where user session data can be stored (set in SimpleUserConfig.php)

private $min_pass_length;
The minimum required length for passwords (set in SimpleUserConfig.php)

---------------------
Private Functions
---------------------
throw_error($msg)
Will be called when an unrecoverable error occurs. Outputs the error to the screen and exits script execution.

---------------------
Public Functions
---------------------
show_login_form($post_to)
Shows a basic form with fields to allow a user to log in. $post_to is the name of the script that will be handling the login.

show_registration_form($post_to)
Shows a basic form with fields to allow a user to create thier own account. $post_to is the name of the script that will be handling the registration.

show_profile_edit_form($post_to[, $userID])
Shows a basic form with fields to allow a user to update their profile (name, email, password, etc.) $post_to is the name of the script that will be handling the updating of the profile. $userID is the ID of a user to edit (if not the current user.) This only works if the current user is an administrator. If left blank, it will alllow the current user to change their own profile. Allows admins to give admin rights to other users.

process_login()
Authenticates the user log in and sets session variables for later use by scripts.

process_registration()
Processes the submitted form data for new user creation. Makes sure logins are unique via email_exists().

process_profile_edit()
Stores changes to a logged in user's profile. Makes sure logins are unique via email_exists().

delete_account($userID)
Will delete the account of the given userID.

email_exists($email)
Checks that the given email address exists as a login in the system. Returns true if yes, false otherwise.

get_userID()
Returns the userID for the current user.

user_info($userID)
Returns the name, email, admin status and timestamp the account was created for the given user, as an array.

is_logged_in()
Returns true if the current user is logged in, false otherwise.

is_admin()
Returns true if the current user is logged in as an administrator, false otherwise.

all_users()
Returns an array of all user info (not passwords) for every user. Only available to logged in administrators.

log_out()
Logs out the currently logged in user.

*/
require_once("MCWDUserConfig.php");
class MCWDUser{

    //set in SimpleUserConfig.php
    private $dbname;
    private $dbpass;
    private $dbhost;
    private $dblink;
    private $user_session;
    private $min_pass_length;
    public  $userID="";
    public  $email="";
    public  $admin=0;
    

    function __construct($uid=''){
        $this->dbname=USERDBNAME;
        $this->dbuser=USERDBUSER;
        $this->dbpass=USERDBPASS;
        $this->dbhost=USERDBHOST;
        $this->prefix=USERTABLEPREFIX;
        $this->user_session=USERSESSION;
        $this->min_pass_length=MINPASSLENGTH;
        //connect to db
        $this->dblink=mysqli_connect($this->dbhost,$this->dbuser,$this->dbpass,$this->dbname);
        if (!$uid) $uid=$_SESSION[$this->user_session]['userID'];

        $userinfo=$this->user_info($uid);
        if ($userinfo['userID'])
        {
            $this->userID=$userinfo['userID'];
            $this->email=$userinfo['email'];
            $this->admin=$userinfo['is_admin'];
        }
    }

    private function throw_error($msg){
        echo "<span style='color:red;'>".$msg."</span><br>";
        exit;
    }

    public function show_login_form($post_to){
        echo "<div class='login'><form action='".$post_to."' method='post'><input type='hidden' name='action' value='login'>
        Email: <input type='text' name='email'> Password: <input type='password' name='password'> <input type='submit' name='submit' value='Login'>
        </form></div>";
    }

    public function show_registration_form($post_to){
        if (!$this->is_admin()) { print "You must be an administrator to add a user."; return ;}
        echo "<form action='".$post_to."' method='post'><input type='hidden' name='action' value='insert'>
        <table>
        <tr><td class='tdright'>First Name:</td><td><input type='text' name='fname'></td></tr>
        <tr><td class='tdright'>Last Name:</td><td><input type='text' name='lname'></td></tr>
        <tr><td class='tdright'>Email:</td><td><input type='text' name='email'></td></tr>
        <tr><td class='tdright'>&nbsp;</td><td><input type='checkbox' name='is_admin' value='true'> Administrator</td></tr>
        <tr><td class='tdright'>Password:</td><td><input type='password' name='password'></td></tr>
        <tr><td class='tdright'>Confirm Password:</td><td><input type='password' name='conf_password'></td></tr>
        </table>
        <input type='submit' name='submit' value='Add User'>
        </form>";
    }
    public function show_profile_edit_form($post_to, $userID=0){
        if($userID==0)$userID=$this->get_userID();
        //make sure the user is logged in
        if($this->is_logged_in()){
            //if editing a different user's account, make sure this user is an admin
            $goahead=false;
            if($userID==$_SESSION[$this->user_session]['userID']){
                $goahead=true;
            }else{
                if($this->is_admin()){
                    $goahead=true;
                }else{
                    $goahead=false;
                }
            }
            //if it all checks out, do this
            if($goahead){
                $user=$this->user_info($userID);
                echo "This account was created ".date("h:ia n/j/Y",$user['created_timestamp'])."<br><form action='".$post_to."' method='post'>
                <input type='hidden' name='userID' value='".$userID."'>
                <input type='hidden' name='action' value='update'>
                <table>
                <tr><td class='tdright'>First Name:</td><td><input type='text' name='fname' value=\"".$user['fname']."\"></td></tr>
                <tr><td class='tdright'>Last Name:</td><td><input type='text' name='lname' value=\"".$user['lname']."\"></td></tr>";
                //if the current user is an administrator, let them give this person admin privileges";
                if($this->is_admin()){ 
                    print "<tr><td class='tdright'>Username:</td><td><input type='text' name='email' value=\"".$user['email']."\"></td></tr>\n";
                }
                else print "<tr><td class='tdright'>Username:</td><td>".$user['email']."</td></tr>\n";
                //if the current user is an administrator, let them give this person admin privileges
                if($this->is_admin()){
                    echo "<tr><td class='tdright'>&nbsp;</td><td><input type='checkbox' name='is_admin' value='true' ".($user['is_admin']==1?"checked":"")."> Administrator</td></tr>\n";
                }
                echo "<tr><td colspan=2>Leave the following blank unless you'd like to change the password</td></tr>
                <tr><td class='tdright'>New Password:</td><td><input type='password' name='new_password'></td></tr>
                <tr><td class='tdright'>Confirm Password:</td><td><input type='password' name='conf_password'></td></tr>
                </table>
                <input type='submit' name='submit' value='Save'>
                </form>";
            }else{
                $this->throw_error("You are not allowed to do that");
            }
        }else{
            $this->throw_error("You are not logged in");
        }
    }
    public function process_login(){
//        print_r($_POST);
        $sql="select userID from ".$this->prefix."user_creds where email=? and pass=?";
        $stmt = mysqli_prepare($this->dblink, $sql); 
        if($stmt==false) {$this->throw_error(sprintf("Errormessage: %s\n", mysqli_error($this->dblink)));}
        $pw=md5($_POST['password']);
        mysqli_stmt_bind_param($stmt, "ss", $_POST['email'],$pw);    
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $userID);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
        if($userID){    
            $userinfo=$this->user_info($userID);
            $_SESSION[$this->user_session]=array("userID"=>$userID);
            $this->userID=$userinfo['userID'];
            $this->email=$userinfo['email'];
            $this->admin=$userinfo['is_admin'];
            return true;
        }else{
            return "Incorrect username/password.";
        }
    }
    public function process_registration(){
        if (!$this->is_admin()) { return "You must be an administrator to add a user.";}
        if(strlen($_POST['email'])>4){
            if(strlen($_POST['conf_password'])>=$this->min_pass_length){
                if($_POST['password']==$_POST['conf_password']){
                    if(!$this->email_exists($_POST['email'])){
                        //it's all good, put it in the DB
                        $nadmin = ($_POST['is_admin']==true?"1":"0");
                        $sql="insert into ".$this->prefix."users (fname, lname, email, is_admin, created_timestamp) values (?, ?, ?, ?, ".time().")";
                        $stmt = mysqli_prepare($this->dblink, $sql); 
                        mysqli_stmt_bind_param($stmt, "sssi", $_POST['fname'],$_POST['lname'],$_POST['email'],$nadmin);    
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_close($stmt);    
                        $newID=mysqli_insert_id($this->dblink);
                        $pw = md5($_POST['password']);
                        $sql="insert into ".$this->prefix."user_creds values('$newID',?, ?)";
                        $stmt = mysqli_prepare($this->dblink, $sql); 
                        mysqli_stmt_bind_param($stmt, "ss", $_POST['email'],$pw);    
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_close($stmt);
                        return true;
                    }else{
                        return "That email address already exists in our system. Please choose a different one.";
                    }
                }else{
                    return "You didn't type the same password twice.";
                }
            }else{
                return "Your password must be longer than ".$this->min_pass_length." characters.";
            }
        }else{
            return "A valid email address is required.";
        }
    }
    public function process_profile_edit(){
        //if this isn't the profile for the current user, make sure we're logged in as an admin
        if($_POST['userID']==$this->get_userID() or ($_POST['userID']!=$this->get_userID() and $this->is_admin()) ){
            if (!$this->is_admin()) {
                $_POST['email']=$this->email;
            }
            if(strlen($_POST['email'])>4){
                if(!$this->email_exists($_POST['email'],$_POST['userID'])){
                    //do the basic info stuff, only let admins change the "is_admin" field
                    $sql="update ".$this->prefix."users set fname=?, lname=?, email=?
                    ".($this->is_admin()?", is_admin='".($_POST['is_admin']==true?"1":"0")."'":"")."
                    where userID=?";
                    $stmt = mysqli_prepare($this->dblink, $sql); 
                    mysqli_stmt_bind_param($stmt, "sssi", $_POST['fname'],$_POST['lname'],$_POST['email'],$_POST['userID']);    
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);    
                    
                    $sql="update ".$this->prefix."user_creds set email=? where userID=?";
                    $stmt = mysqli_prepare($this->dblink, $sql); 
                    mysqli_stmt_bind_param($stmt, "si", $_POST['email'],$_POST['userID']);    
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                    
                    if($_POST['userID']==$this->get_userID()){
                        $userinfo=$this->user_info($this->get_userID());
                        $this->email=$userinfo['email'];
                        $this->admin=$userinfo['is_admin'];
                    }
                }else{
                    return "That email address already exists in our system. Please choose a different one.";
                }
            }else{
                return "A valid email address is required.";
            }
            if(strlen($_POST['conf_password'])>=$this->min_pass_length and strlen($_POST['conf_password'])>0){
                if($_POST['new_password']==$_POST['conf_password']){
                    $sql="update ".$this->prefix."user_creds set pass=? where userID=?";
                    $stmt = mysqli_prepare($this->dblink, $sql); 
                    $pw=md5($_POST['new_password']);
                    mysqli_stmt_bind_param($stmt, "si", $pw,$_POST['userID']);    
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                    return true;
                }else{
                    return "You didn't type the same password twice.";
                }
            }elseif(strlen($_POST['conf_password'])>0){
                return "Your password must be longer than ".$this->min_pass_length." characters.";
            }
        }else{
            $this->throw_error("You are not allowed to do that!");
        }
        return true;
    }
    public function delete_account($userID){
        //delete the given account
        if($this->is_admin()){
            $sql="delete from ".$this->prefix."users where userID=?";
            $stmt = mysqli_prepare($this->dblink, $sql); 
            if($stmt==false) {$this->throw_error("Errormessage: %s\n", mysqli_error($this->dblink));}
            mysqli_stmt_bind_param($stmt, "i", $userID);    
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $sql="delete from ".$this->prefix."user_creds where userID=?";
            $stmt = mysqli_prepare($this->dblink, $sql);
            if($stmt==false) {$this->throw_error("Errormessage: %s\n", mysqli_error($this->dblink));} 
            mysqli_stmt_bind_param($stmt, "i", $userID);    
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            return true;
        }
    }
    public function email_exists($email,$userID=-1){
        //return true if the given email address exists in the system
        $sql="select userID from ".$this->prefix."users where email=? and userID !=?";
        $stmt = mysqli_prepare($this->dblink, $sql); 
        mysqli_stmt_bind_param($stmt, "si", $email,$userID);    
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if(mysqli_stmt_num_rows($stmt)>0){
            mysqli_stmt_close($stmt);
            return true;
        }
        mysqli_stmt_close($stmt);
        return false;
    }
    public function get_userID(){
        //return the userID of the currently logged in user
        return $_SESSION[$this->user_session]['userID'];
    }
    public function user_info($userID){
        //return all info for the given user
        $sql="select * from ".$this->prefix."users where userID=".$userID;
        //echo $sql;
        if ($rs=mysqli_query($this->dblink,$sql))
            $r=mysqli_fetch_assoc($rs);
        return $r;
    }
    public function is_logged_in(){
        //return true or false
        if(isset($_SESSION[$this->user_session]['userID']) and $_SESSION[$this->user_session]['userID']>0)return true;
        else return false;
    }
    public function is_admin(){
        //return true or false
        if($this->admin==1)return true;
        else return false;
    }
    public function all_users(){
        if (!$this->is_admin()) return false;
        //return an array of all user's info
        $sql="select * from ".$this->prefix."users";
        $rs=mysqli_query($this->dblink,$sql);
        while($r=mysqli_fetch_assoc($rs)){
            $ret[$r['userID']]=$r;
        }
        return $ret;
    }
    public function log_out(){
        //log out the current user
        unset($_SESSION[$this->user_session]);
        $_SESSION[$this->user_session]=array();
        return true;
    }
    /*new functions
     * 
     * */
    public function greeting($acct_page="", $logout="")
    {
        if ($this->userID) {
            print "You are logged in as ".$this->email;    
                    if ($acct_page) {
                print " | <a href=\"$acct_page\">My account</a>";
            }            
            if ($logout) {
                print " | <a href=\"$logout\">Logout</a>";
            }
            
        }
        else print $this->show_login_form(USERLOGINPAGE."?linkback=".$_SERVER["SCRIPT_NAME"]);
    }
}     
?>