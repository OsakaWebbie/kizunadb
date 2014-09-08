<?php
include("functions.php");
include("accesscontrol.php");
if ($_SESSION['userid']== "dev" || $_SESSION['userid']== "karen") {
  error_reporting(E_ALL);
  ini_set('display_errors',1);
}

header1($_SESSION['username']._("'s Dashboard"));
echo "<link rel=\"stylesheet\" href=\"style.php?jquery=1&table=1\" type=\"text/css\" />\n";
echo "<script type=\"text/JavaScript\" src=\"js/jquery.js\"></script>\n";
echo "<script type=\"text/JavaScript\" src=\"js/jquery-ui.js\"></script>\n";
if (!$_SESSION['hasdashboard']) {
  header2(1);
  echo "<h3>"._("You don't have a dashboard yet.  If you would like one, talk to your KizunaDB administrator.")."</h3>\n";
  footer(0);
} else {
  $result = sqlquery_checked("SELECT DashboardHead,DashboardBody FROM login WHERE UserID='".$_SESSION['userid']."'");
  $code = mysql_fetch_object($result);
  eval($code->DashboardHead);
  header2(1);
  echo "<h1 id=\"title\">".$_SESSION['dbtitle'].": ".$_SESSION['username']._("'s Dashboard")."</h1>\n";
  eval($code->DashboardBody);
  footer(1);
}
