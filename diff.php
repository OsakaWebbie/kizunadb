<?php
include("functions.php");
include("accesscontrol.php");

header1("List for merging");
header2();
echo "<h2>Links to 'merge' screen for old records that need checking/combining</h2>\n";

$res = sqlquery_checked("select FullName,PersonID from kizuna_crashdonors.person where PersonID in (select PersonID from percat where CategoryID=80)");
while ($source = mysql_fetch_object($res)) {
  echo "<a href=\"merge.php?sourcedb=kizuna_crashdonors&destdb=kizuna_crash&sourceid=".$source->PersonID."&destid=".$source->PersonID.
  "\" target=\"_blank\">".$source->FullName."</a><br>\n";
}
footer();
?>
