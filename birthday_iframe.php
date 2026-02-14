<?php
 include("functions.php");
 include("accesscontrol.php");

print_header("","#FFFFFF",0);

if (!$startmonth) {
  exit("You can't run this by itself - start from birthday.php.");
}

// fill in days in case of whole_month
if ($wholemonth) {
  $startday = 1;
  $max_days = array(31, 29, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
  $endday = $max_days[$endmonth-1];
}

// Display header and build SQL statement
if (!$catlist) {
  echo "<b>All birthdays between {$startmonth}/{$startday} and {$endmonth}/{$endday}:</b><br>&nbsp;<br>\n";
  $sql = "SELECT PersonID, FullName, Furigana, Photo, Birthdate FROM person p WHERE ";
} else {
  // Add to SQL statement to limit to selected categories
  $sql = "SELECT DISTINCT p.PersonID, p.FullName, p.Furigana, p.Photo, p.Birthdate FROM person p, percat c "
  . "WHERE p.PersonID=c.PersonID AND c.CategoryID IN ({$catlist}) AND ";
  // List categories by name for use in header
  $sql2 = "SELECT * FROM category WHERE CategoryID IN ({$catlist}) ORDER BY Category";
  $result = sqlquery_checked($sql2);
  $row = mysqli_fetch_object($result);  // Get the first one; no preceding comma
  $cat_names = $row->Category;
  while ($row = mysqli_fetch_object($result)) {
    $cat_names .= ", " . $row->Category;
  }
  echo "<b>Birthdays between {$startmonth}/{$startday} and {$endmonth}/{$endday} ".
     "for those in categories <i>$cat_names</i>:</b><br>&nbsp;<br>\n";
}
// Add bucket filter
if (!empty($bucket) && !empty($_SESSION['bucket'])) {
  $sql .= "p.PersonID IN (" . implode(',', $_SESSION['bucket']) . ") AND ";
}
// Finish WHERE clause
$startcombo = str_pad($startmonth,2,"0", STR_PAD_LEFT) . str_pad($startday,2,"0", STR_PAD_LEFT);
$endcombo = str_pad($endmonth,2,"0", STR_PAD_LEFT) . str_pad($endday,2,"0", STR_PAD_LEFT);
if (($startmonth>$endmonth) || ($startmonth==$endmonth && $startday>$endday)) {
  // Date range crosses year boundary
  $sql .= "((DATE_FORMAT(Birthdate,'%Y%m%d') >= CONCAT(YEAR(Birthdate),'{$startcombo}') ";
  $sql .= "AND DATE_FORMAT(Birthdate,'%Y%m%d') <= CONCAT(YEAR(Birthdate),'1231')) ";
  $sql .= "OR (DATE_FORMAT(Birthdate,'%Y%m%d') >= CONCAT(YEAR(Birthdate),'0101') ";
  $sql .= "AND DATE_FORMAT(Birthdate,'%Y%m%d') <= CONCAT(YEAR(Birthdate),'{$endcombo}')))";
} else {
  $sql .= "DATE_FORMAT(Birthdate,'%Y%m%d') >= CONCAT(YEAR(Birthdate),'{$startcombo}') ";
  $sql .= "AND DATE_FORMAT(Birthdate,'%Y%m%d') <= CONCAT(YEAR(Birthdate),'{$endcombo}')";
}

$sql .= " ORDER BY month(Birthdate), dayofmonth(Birthdate)";
$result = sqlquery_checked($sql);
echo "<form action=\"multiselect.php\" method=GET target=\"_top\">\n";
echo "<center><input type=submit value=\"Go to Multi-Select\"><br></center>";

// Create table
echo "<table border=1 cellspacing=0 cellpadding=2><thead><tr><th>Name</th><th>Photo</th>";
echo "<th>Birthdate</th><th>Age after<br>Birthday</th>\n</thead><tbody>\n";
while ($row = mysqli_fetch_object($result)) {
  echo "<tr><td nowrap><a href=\"individual.php?pid=".$row->PersonID."\" target=\"_blank\">";
  echo readable_name($row->FullName, $row->Furigana)."</a></td><td align=center>";
  echo ($row->Photo == 1) ? "<img border=0 src=\"photos/p".$row->PersonID.".jpg\" width=50>" : "&nbsp;";
  echo "</td>\n";
  if (substr($row->Birthdate,0,4) == "1900") {  // Year born is not known
    echo "<td align=center>????" . substr($row->Birthdate,4) . "</td><td>&nbsp;</td></tr>\n";
  } else {
    $ba = explode("-",$row->Birthdate);
    $ta = explode("-",date("Y-m-d",mktime(0, 0, 0, date("m")+4, date("d"),  date("Y"))));
    $age = $ta[0] - $ba[0];
//    $age = "This=".$ta[0].", Born=".$ba[0];
    if (($ba[1] > $ta[1]) || (($ba[1] == $ta[1]) && ($ba[2] > $ta[2]))) --$age;
    echo "<td align=center>" . $row->Birthdate . "</td><td align=center>" . $age . "</td></tr>\n";
  }
  echo "</tr>\n";
  $pid_list .= $row->PersonID.",";
}
echo "</table>\n";
echo "<input type=hidden name=pids value=\"".rtrim($pid_list,',')."\"></form>\n";

print_footer();
?>
