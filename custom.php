<?php
include("functions.php");
include("accesscontrol.php");
$result = sqlquery_checked("SELECT * FROM custom WHERE CustomName='".$_POST['customname']."'");
$custom = mysqli_fetch_object($result);
pageheader(_("Custom Report"), $custom->IsTable);
if (!$custom->IsTable) echo "<style>\n".$custom->CSS."/n</style>\n";
$result = sqlquery_checked(str_replace("%PIDS%",$_POST['pids'],$custom->SQL));
$fields = mysqli_num_fields($result);
$rows = mysqli_num_rows($result);
if ($custom->IsTable) {
  echo "<table id=\"mainTable\" class=\"tablesorter\">\n  <thead>\n    <tr>\n";
  for ($i=0; $i<$fields; $i++) {
    echo ("      <th nowrap>".mysqli_fetch_field_direct($result,$i)->name."</th>\n");
  }
  echo "    </tr>\n  </thead>\n  <tbody>\n";
  while ($row_array = mysqli_fetch_row($result)) {
    echo "  <tr>\n";
    for ($i=0; $i<$fields; $i++) {
      if (substr($row_array[$i],0,2)=="<a") {
        echo ("    <td nowrap>".$row_array[$i]."</td>\n");
      } else {
        echo ("    <td nowrap>".d2h($row_array[$i])."</td>\n");
      }
    }
    echo "  </tr>\n";
  }
  echo "  </tbody>\n</table>\n";
} else {  // plain output (depend on SQL and CSS to format it)
  while ($row_array = mysqli_fetch_row($result)) {
    for ($i=0; $i<$fields; $i++) {
      echo ($row_array[$i]);
    }
  }
}

if ($custom->IsTable) {
  load_scripts(['jquery', 'tablesorter']);
?>
<script type="text/javascript">
$(document).ready(function() {
  $("#mainTable").tablesorter({
  });
});
</script>
<?php
}
footer();
?>
