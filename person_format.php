<?php
include("functions.php");
include("accesscontrol.php");
echo "<html><head>";
echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=".$_SESSION['charset']."\">\n";
echo "<title>Formatted Individual Data</title>\n";

//$pid_array = explode(",",$pid_list);
//$num_pids = count($pid_array);

$sql = "SELECT * FROM outputset LEFT JOIN output ON outputset.Class=output.Class ".
 "WHERE SetName='$outputset_name' AND ForHousehold=0 ORDER BY OrderNum";
$result = sqlquery_checked($sql);
$num_items = 0;
echo "<style>\n";
while ($row = mysqli_fetch_object($result)) {
  $class[$num_items][0] = $row->Class;
  $class[$num_items][1] = $row->OutputSQL;
  echo ".".$row->Class." { ".$row->CSS." }\n";
//$debug .= $class[num_items][1]."<br>";
  $num_items++;
}
echo "</style>\n";  
echo "</head><body>";

//echo $debug;

if (stripos($outputset_name,"table")!==FALSE) {
  echo "<table border=1 cellspacing=0 cellpadding=2 style=\"empty-cells:show;border:1px black solid\">\n";
}
//for ($pid_index=0; $pid_index<$num_pids; $pid_index++) {
  $sql = "SELECT ";
  for ($index = 0; $index < $num_items; $index++) {
    $sql .= $class[$index][1]." AS Item".$index;
    if ($index < $num_items-1) $sql .= ",";
  }
//  $sql .= " FROM person LEFT JOIN household ON person.HouseholdID=household.HouseholdID ".
//  "LEFT JOIN postalcode ON household.PostalCode=postalcode.PostalCode ".
//  "WHERE PersonID=".$pid_array[$pid_index];
  $sql .= " FROM person LEFT JOIN household ON person.HouseholdID=household.HouseholdID ".
  "LEFT JOIN postalcode ON household.PostalCode=postalcode.PostalCode ".
  "WHERE PersonID IN (".$pid_list.") ORDER BY ".$orderby;
  $result = sqlquery_checked($sql);
//echo "Numrows? (should be one): ".mysqli_num_rows($result)."<br>";
while ($per = mysqli_fetch_array($result)) {
//  $per = mysqli_fetch_array($result);
  for ($index = 0; $index < $num_items; $index++) {
    echo $per[$index]."\n";
  }
}
if (stripos($outputset_name,"table")!==FALSE) {
  echo "</table>\n";
}

echo "</body></html>";
?>
