<?php
include("functions.php");
include("accesscontrol.php");
if ($_SESSION['userid']== "dev" || $_SESSION['userid']== "karen") {
  error_reporting(E_ALL);
  ini_set('display_errors',1);
}

header1($_SESSION['username']._("'s Dashboard"));
echo "<link rel=\"stylesheet\" href=\"style.php?jquery=1&amp;table=1\" type=\"text/css\">\n";
echo "></script>\n";
echo "></script>\n";
if (!$_SESSION['hasdashboard']) {
  header2(1);
  echo "<h3>"._("You don't have a dashboard yet.  If you would like one, talk to your KizunaDB administrator.")."</h3>\n";
  footer();
} else {
  $result = sqlquery_checked("SELECT DashboardHead,DashboardBody FROM login WHERE UserID='".$_SESSION['userid']."'");
  $code = mysqli_fetch_object($result);
  eval($code->DashboardHead);
  header2(1);
  echo "<h1 id=\"title\">".$_SESSION['dbtitle'].": ".$_SESSION['username']._("'s Dashboard")."</h1>\n";
  eval($code->DashboardBody);
  footer();
}
