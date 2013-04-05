<?php
//include("functions.php");
//include("accesscontrol.php");
session_start();

if (isset($_SESSION['userid'])) {
  foreach ($_GET as $key=>$val ){
    $_SESSION[$key] = $val;
  }
}
?>
