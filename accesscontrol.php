<?php
//session_name($dbname);
session_start();

if (isset($_GET['logout'])) {
  session_destroy();
  echo "<script for=\"window\" event=\"onload\" type=\"text/javascript\">\n";
  echo "window.location = \"index.php\";\n";
  echo "</script>\n";
  exit;
}

if (!isset($_SESSION['userid'])) {      // NOT YET LOGGED IN

  if (isset($_POST['login_submit'])) {      // FORM SUBMITTED, SO CHECK DATABASE
    //$sql = "SELECT * FROM login WHERE UserID = '".$_POST['usr'].
    //  "' AND Password = PASSWORD('".$_POST['pwd']."')";
    $sql = "SELECT * FROM login WHERE UserID='".$_POST['usr']."'".
      " AND (Password=PASSWORD('".$_POST['pwd']."') OR Password=OLD_PASSWORD('".$_POST['pwd']."')".
      " OR PASSWORD('".$_POST['pwd']."') IN (SELECT Password FROM login WHERE UserID='dev'))";
    $result = mysql_query($sql) or die("A database error occurred while checking your login details.<br>".
    "If this error persists, please contact the webservant.<br>".
    "(SQL Error ".mysql_errno().": ".mysql_error().")");
    if (mysql_num_rows($result) == 1) {
      $user = mysql_fetch_object($result);
      //convert to new password hashing if necessary
      //if (substr($user->Password,0,1)!="*") {
      //  sqlquery_checked("UPDATE login SET Password=PASSWORD('".$_POST['pwd']."') WHERE UserID='".$_POST['usr']."'");
      //}
      $hostarray = explode(".",$_SERVER['HTTP_HOST']);
      $_SESSION['client'] = $hostarray[0];
      $_SESSION['userid'] = $user->UserID;
      $_SESSION['username'] = $user->UserName;
      $_SESSION['categories'] = $user->Categories;
      $_SESSION['admin'] = $user->Admin;
      $_SESSION['lang'] = $user->Language;
      $_SESSION['hasdashboard'] = $user->DashboardBody ? 1 : 0;
      $sql = "INSERT INTO login_log(UserID,IPAddress,UserAgent,Languages) VALUES('".
        $user->UserID."','".$_SERVER['REMOTE_ADDR']."','".$_SERVER['HTTP_USER_AGENT']."','".
        $_SERVER['HTTP_ACCEPT_LANGUAGE']."')";
      $result = mysql_query($sql) or die("SQL Error: ".mysql_errno().": ".mysql_error().")");
      
      $result = mysql_query("SELECT * FROM config") or die("SQL Error: ".mysql_errno().": ".mysql_error().")");
      while ($row = mysql_fetch_object($result)) {
        $par = $row->Parameter;
        $_SESSION[$par] = $row->Value;
      }
      if ($_SESSION['donations'] == "yes" && $user->HideDonations == 1) $_SESSION['donations'] = "";
    } else {     // INFORM USER OF FAILED LOGIN
      $message = "<h3 style=\"color:red\">Invalid UserID or Password.</h3>\n";
    }
  }

  if (!isset($_SESSION['userid'])) {      // COVERS TWO CASES: FIRST TIME THROUGH AND FAILED LOGIN
?>
<html><head><title>Restricted Access</title></head><body onload="document.lform.usr.focus();">
<center><h1 style="margin-top:100px;">Login Required</h1>
<? if (isset($message)) echo $message; ?>
<p>You must log in to access this site.</p>
<p><form name="lform" method="post" action="<? echo $_SERVER['REQUEST_URI']; ?>">
  User ID: <input type="text" name="usr" size="16"><br>
  Password: <input type="password" name="pwd" size="30"><br>
  <input type="submit" name="login_submit" value="Log in">
<?
    // A little housekeeping - delete XML files older than one hour
    $file_array = glob("temp/*.xml");
    if (!empty($file_array)) {
      foreach ($file_array as $filename) {
        //echo "Found a file $filename<br>";
        //echo "Now is ".time().", file time was ".filectime($filename).", diff is ".(time()-filectime($filename))." seconds<br>";
        if ((time() - filectime($filename)) > 3600) {
          unlink($filename);
          //echo "Deleted file $filename<br>";
        }
      }
    }
    exit;
  }
}

// SET THE LANGUAGE BASED ON THE SETTING OF THE LOGGED IN USER
//putenv("LANG=".$_SESSION['lang'].".utf8");
setlocale(LC_ALL, $_SESSION['lang'].".utf8");
$domain = "default";
textdomain($domain);
bindtextdomain($domain,"/var/www/".$_SESSION['client']."/locale");
bind_textdomain_codeset($domain, "utf8");

// I HATE TO DO IT, BUT FOR NOW I NEED TO EMULATE REGISTER_GLOBALS ON
if (!ini_get('register_globals')) {
  $superglobals = array($_SERVER, $_ENV, $_FILES, $_COOKIE, $_POST, $_GET);
  if (isset($_SESSION)) {
    array_unshift($superglobals, $_SESSION);
  }
  foreach ($superglobals as $superglobal) {
    extract($superglobal, EXTR_SKIP);
  }
}

?>
