<?php
include("functions.php");
include("accesscontrol.php");
if ($_SESSION['userid']=="karen" || $_SESSION['userid']=="dev") {
  phpinfo();
}
?>
