<?php
include("functions.php");
include("accesscontrol.php");
header1(_("Custom Report"));
$result = sqlquery_checked("SELECT * FROM custom WHERE CustomName='".$_POST['customname']."'");
$custom = mysqli_fetch_object($result);
if ($custom->IsTable) {
?>
<link rel="stylesheet" href="style.php?table=1" type="text/css" />
<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/tablesorter.js"></script>
<script type="text/javascript">
$(document).ready(function() {
  $("#mainTable").tablesorter({
  });
});
</script>
<?php
} else {
  echo "<style>\n".$custom->CSS."/n</style>\n";
}
header2($custom->IsTable);
$result = sqlquery_checked(str_replace("%PIDS%",$_POST['pids'],$custom->SQL));
$fields = mysqli_num_fields($result);
$rows = mysqli_num_rows($result);
if ($custom->IsTable) {
  echo "<table id=\"mainTable\" class=\"tablesorter\">\n  <thead>\n    <tr>\n";
  for ($i=0; $i<$fields; $i++) {
    echo ("      <th nowrap>".mysqli_field_name($result,$i)."</th>\n");
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

footer();
?>
