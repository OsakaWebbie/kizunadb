<?php
include("functions.php");
include("accesscontrol.php");

if (!empty($_REQUEST['oncall'])) {  // code to only be run when specifically requested
  if (file_exists(CLIENT_PATH . '/dashboard/' . $_REQUEST['oncall'] . '.php')) {
    include(CLIENT_PATH . '/dashboard/' . $_REQUEST['oncall'] . '.php');
  } else {
    echo '<div>On-call file "' . $_REQUEST['oncall'] . '.php" was not found.<br>Path: '.CLIENT_PATH .
        '/dashboard/' . $_REQUEST['oncall'] . '.php</div>';
  }
  exit;
}

load_scripts(['jquery', 'jqueryui', 'tablesorter', 'table2csv']);

if ($_SESSION['admin'] && isset($_GET['user'])) {  /* to test or view other user's dashboards */
  $user = $_GET['user'];
  $result = sqlquery_checked("SELECT UserName,Dashboard FROM user WHERE UserID='$user'");
  ($row = mysqli_fetch_object($result)) || die("No record found for user '$user'.");

  $hasdashboard = ($row->Dashboard != '');
  $username = $row->UserName;
} else {
  $user = $_SESSION['userid'];
  $hasdashboard = $_SESSION['hasdashboard'];
  if ($hasdashboard) {
    $result = sqlquery_checked("SELECT Dashboard FROM user WHERE UserID='".$_SESSION['userid']."'");
    $row = mysqli_fetch_object($result);
    $username = $_SESSION['username'];
  }
}
pageheader(_("Dashboard"), 1);
?>
<style>
/* page-specific rules (moved from style.php) */
body.dashboard .dashboard-item { float: left; border: 2px solid var(--section-border); margin: 10px; padding: 10px; }
body.dashboard .item-icon { float: left; border: none; margin: 5px; }
body.dashboard p, body.dashboard h2, body.dashboard h3, body.dashboard h4  { text-align: center; }
body.dashboard table th, body.dashboard table td { border: 1px solid gray; padding: 5px; margin: 5px; white-space: nowrap; }
body.dashboard table th { background-color: #FFFFC0; text-align: center; }
body.dashboard table td.number { text-align: center; font-weight: bold; }
body.dashboard #attend_datesums h3 { display:none; }
</style>
<?php
if (!$hasdashboard) {
  echo "<h3>"._("You don't have a dashboard yet.  If you would like one, talk to your KizunaDB administrator.")."</h3>\n";
  load_scripts(['jquery', 'jqueryui', 'tablesorter', 'table2csv']);
  footer();
} else {
  echo "<h1 id='title'>" . $_SESSION['dbtitle'] . ": " . $username . _("'s Dashboard") . "</h1>\n";
  $files = explode(',', $row->Dashboard);
  foreach ($files as $file) {
    if (file_exists(CLIENT_PATH . '/dashboard/' . $file . '.php')) {
      include(CLIENT_PATH . '/dashboard/' . $file . '.php');
    } else {
      echo '<div>Dashboard file "' . $file . '.php" was not found.</div>';
    }
  }
  echo "<div style='clear:both'></div>";
  footer();
}

/*** Returns a single data point ***/
function single_result($sql) {
  $res = sqlquery_checked($sql);
  $data = mysqli_fetch_array($res);
  return $data[0];
}

/*** Displays sortable table from passed DB result ***/
function querytable($result, $table_id, $top='-10px') {
  $fields = mysqli_num_fields($result);
  $rows = mysqli_num_rows($result);
  if ($rows == 0) return;
  echo "<style>\n#$table_id thead th {position:sticky;top:$top;}\n</style>\n";
  echo "<table id='$table_id' class='tablesorter'>\n  <thead>\n    <tr>\n";
    for ($i=0; $i<$fields; $i++) {
    echo ("      <th>".mysqli_fetch_field_direct($result,$i)->name."</th>\n");
    }
    echo "    </tr>\n  </thead>\n  <tbody>\n";
  while ($row_array = mysqli_fetch_row($result)) {
  echo "  <tr>\n";
    for ($i=0; $i<$fields; $i++) {
      if ($row_array[$i]==NULL) $row_array[$i] = '';
      if (substr($row_array[$i],0,2)=="<a") {
      echo ("    <td nowrap>".$row_array[$i]."</td>\n");
      } elseif (mysqli_fetch_field_direct($result,$i)->name=="PersonID") {
      echo ("    <td><a href=\"individual.php?pid=".$row_array[$i]."\" target=\"_blank\">".$row_array[$i]."</a></td>\n");
      } else {
      echo ("    <td>".d2h($row_array[$i])."</td>\n");
      }
    }
    echo "  </tr>\n";
  }
  echo "  </tbody>\n</table>\n";
  ?>
  <script type="text/javascript">
    $(function() {
      $("#<?=$table_id?>").tablesorter({
      });
    });
  </script>
<?php
}

/*** Displays button that will take data from table with specified element ID and create CSV file ***/
function csv_button($id) {
  ?>
  <form id="csvform<?=$id?>" action="download.php" method="post" target="_top" style="display:inline;padding-left:2em">
    <input type="hidden" id="csvtext<?=$id?>" name="csvtext" value="">
    <input type="submit" id="csvfile<?=$id?>" name="csvfile" value="Download CSV" class="csvbutton"
           onclick="$('#csvtext<?=$id?>').val($('#<?=$id?>').table2CSV({delivery:'value'}));">
  </form>
  <?php
}
