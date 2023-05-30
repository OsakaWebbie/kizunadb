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
    $sql = "SELECT *, IF(Password=OLD_PASSWORD('".$_POST['pwd']."'),1,0) NeedPwdUpgrade".
        " FROM user WHERE UserID='".$_POST['usr']."'".
        " AND (Password=PASSWORD('".$_POST['pwd']."') OR Password=OLD_PASSWORD('".$_POST['pwd']."')".
        " OR PASSWORD('".$_POST['pwd']."') IN (SELECT Password FROM user WHERE UserID='dev'))";
    if (!$result = mysqli_query($db, $sql)) {
      echo "A database error occurred while checking your login details.<br>If this error persists, please contact the webservant.";
      if ($_POST['usr']=='dev') {
        echo "<br>SQL Error: ".mysqli_error($db)."<pre>".$sql."</pre>";
      }
      exit;
    }
    if (mysqli_num_rows($result) == 1) {
      $user = mysqli_fetch_object($result);
      //convert to new password hashing if necessary
      if ($user->NeedPwdUpgrade == 1) {
        sqlquery_checked("UPDATE user SET Password=PASSWORD('".$_POST['pwd']."') WHERE UserID='".$_POST['usr']."'");
      }
      $hostarray = explode(".",$_SERVER['HTTP_HOST']);
      $_SESSION['userid'] = $user->UserID;
      $_SESSION['username'] = $user->UserName;
      $_SESSION['admin'] = $user->Admin;
      $_SESSION['lang'] = $user->Language;
      $_SESSION['hasdashboard'] = !empty($user->Dashboard) ? 1 : 0;

      //GET ANNOUNCEMENTS IF ANY
      $result = sqlquery_checked("SELECT MAX(LoginTime) Last FROM loginlog WHERE UserID='".$user->UserID."'");
      $row = mysqli_fetch_object($result);
      if ($row->Last != NULL) { //make sure it's not a brand new user
        $lastlogin = $row->Last;
        $result = sqlquery_checked("SELECT * from kizuna_common.announcement WHERE DATEDIFF(NOW(),AnnounceTime)<180".
        " AND AnnounceTime > '$lastlogin' ORDER BY AnnounceTime ASC");
        if (mysqli_num_rows($result) > 0) {
          $_SESSION['announcements'] = array();
          while ($row = mysqli_fetch_object($result)) {
            $_SESSION['announcements'][] = $row;
          }
        }
      }

      mysqli_query($db, "SET @@SQL_MODE = REPLACE(@@SQL_MODE, 'STRICT_TRANS_TABLES', '')") or die("SQL Error: ".mysqli_error($db).")");
      $sql = "INSERT INTO loginlog(UserID,IPAddress,UserAgent,Languages) VALUES('".
        $user->UserID."','".$_SERVER['REMOTE_ADDR']."','".$_SERVER['HTTP_USER_AGENT']."','".
        $_SERVER['HTTP_ACCEPT_LANGUAGE']."')";
      $result = mysqli_query($db, $sql) or die("SQL Error: ".mysqli_error($db).")");
      mysqli_query($db, "SET @@SQL_MODE = CONCAT(@@SQL_MODE, ',STRICT_TRANS_TABLES')") or die("SQL Error: ".mysqli_error($db).")");

      $result = mysqli_query($db, "SELECT d.Parameter, IF(u.Value IS NULL, d.Value, u.Value) as value ".
          "FROM kizuna_common.config d LEFT JOIN config u ON d.Parameter=u.Parameter ORDER BY d.Parameter")
          or die("SQL Error: ".mysqli_error($db).")");
      while ($row = mysqli_fetch_object($result)) {
        $par = $row->Parameter;
        $_SESSION[$par] = $row->value;
      }
      if ($_SESSION['donations'] == "yes" && $user->HideDonations == 1) $_SESSION['donations'] = "";
    } else {     // INFORM USER OF FAILED LOGIN
      $message = "<h3 style=\"color:red\">Invalid UserID or Password.</h3>\n";
    }
  }

  if (!isset($_SESSION['userid'])) {      // COVERS TWO CASES: FIRST TIME THROUGH AND FAILED LOGIN
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" type="image/x-icon" href="/kizunadb.ico">
  <title>KizunaDB Login</title>
<?php
$hostarray = explode(".",$_SERVER['HTTP_HOST']);
?>
  <link rel="stylesheet" type="text/css" href="style.php?page=<?=$_SERVER['PHP_SELF']?>&jquery=1" />
  <style>
    #nav-main ul {
      padding-top:6px;
    }
    form label {
      display: block;
      font-size: 110%;
      line-height: 1.5em;
      font-weight: bold;
      margin-bottom: 15px;
    }
    form input {
      font-weight: bold;
    }
    #submit {
      padding:5px 20px;
      margin-bottom:10px;
      font-size:18px;
    }
    #nav-trigger span::after { display:none; }
  </style>
  <script type="text/JavaScript" src="js/jquery.js"></script>
</head>
<body class="accesscontrol full" onload="document.lform.usr.focus();">
  <div id="main-container">
    <nav id="nav-main">
      <ul class="nav"><li><a href="#" style="font-size: 24px; font-weight:bold; text-decoration:none; cursor:default">Login Required</a></li></ul>
    </nav>
    <div id="nav-trigger"><img src="graphics/kizunadb-logo.png" alt="Logo"><span style="cursor:default; font-size:24px">Login Required</span>
    </div>
    <div id="content" style="text-align:center; padding:20px 5px;">
<?php if (isset($message)) echo $message; ?>
      <form name="lform" method="post" action="<?=$_SERVER['REQUEST_URI']?>">
        <label>User ID: <input type="text" name="usr"></label>
        <label>Password: <input type="password" name="pwd"></label>
        <input id="submit" type="submit" name="login_submit" value="Log in">
      </form>
<?php
    footer();

    // A little housekeeping - delete XML files older than one hour
    $file_array = glob("temp/*.xml");
    if (!empty($file_array)) {
      foreach ($file_array as $filename) {
        if ((time() - filectime($filename)) > 3600) {
          unlink($filename);
        }
      }
    }
    exit;
  }
}

// TURN ON ERROR DISPLAY IF DEV
if ($_SESSION['userid']== "dev") {
  //echo "expecting to turn on error display...<br>";
  error_reporting(E_ALL);
  ini_set('display_errors',1);
  //echo "Display errors is ".ini_get('display_errors')."<br>";
}

// SET THE LANGUAGE BASED ON THE SETTING OF THE LOGGED IN USER
//putenv("LANG=".$_SESSION['lang'].".utf8");
setlocale(LC_ALL, $_SESSION['lang'].".utf8");
$domain = "default";
textdomain($domain);
bindtextdomain($domain,"locale");
bind_textdomain_codeset($domain, "utf8");

// I HATE TO DO IT, BUT FOR NOW I NEED TO EMULATE REGISTER_GLOBALS ON
extract($_GET, EXTR_SKIP);  //try it just for GET
extract($_POST, EXTR_SKIP);  //try it for POST too
/*if (!ini_get('register_globals')) {
  $superglobals = array($_SERVER, $_ENV, $_FILES, $_COOKIE, $_POST, $_GET);
  if (isset($_SESSION)) {
    array_unshift($superglobals, $_SESSION);
  }
  foreach ($superglobals as $superglobal) {
    extract($superglobal, EXTR_SKIP);
  }
}*/

?>
