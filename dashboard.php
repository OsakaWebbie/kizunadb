<?php
include("functions.php");
include("accesscontrol.php");
if ($_SESSION['admin'] && isset($_GET['user'])) {  /* to test or view other user's dashboards */
  $user = $_GET['user'];
  $result = sqlquery_checked("SELECT UserName,Dashboard,DashboardCode FROM user WHERE UserID='$user'");
  ($row = mysqli_fetch_object($result)) || die("No record found for user '$user'.");

  $hasdashboard = ($row->Dashboard != '' || $row->DashboardCode != '');
  $username = $row->UserName;
} else {
  $user = $_SESSION['userid'];
  $hasdashboard = $_SESSION['hasdashboard'];
  if ($hasdashboard) {
    $result = sqlquery_checked("SELECT Dashboard,DashboardCode FROM user WHERE UserID='".$_SESSION['userid']."'");
    $row = mysqli_fetch_object($result);
    $username = $_SESSION['username'];
  }
}
header1(_("Dashboard"));
?>
<link rel="stylesheet" href="style.php?jquery=1&table=1" type="text/css">
<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/jquery-ui.js"></script>
<?php
if (!$hasdashboard) {
  header2(1);
  echo "<h3>"._("You don't have a dashboard yet.  If you would like one, talk to your KizunaDB administrator.")."</h3>\n";
  footer();
} else {
  header2(1);
  echo "<h1 id='title'>" . $_SESSION['dbtitle'] . ": " . $username . _("'s Dashboard") . "</h1>\n";
  if ($row->Dashboard != '') {
    $files = explode(',',$row->Dashboard);
    foreach ($files as $file) {
      //$file = CLIENT_PATH.'/dashboard/'.$file.'.php';
      if (file_exists(CLIENT_PATH.'/dashboard/'.$file.'.php')) {
        include(CLIENT_PATH.'/dashboard/'.$file.'.php');
      } else {
        echo '<div>Dashboard file'.$file.'.php was not found.</div>';
      }
    }
  } else {
    eval($row->DashboardCode);
  }
  echo "<div style='clear:both'></div>";
  footer();
}
