<?php
include("functions.php");
include("accesscontrol.php");

$sql = "SELECT * FROM photoprint WHERE PhotoPrintName='".urldecode($photo_print_name)."'";
if (!$result = mysql_query($sql)) {
  echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)<br>");
  exit;
}
$print = mysql_fetch_object($result);
$table_width = floor(($print->PaperWidth - $print->PaperLeftMargin - $print->PaperRightMargin) * 96 / 25.4);  // assuming 96 dpi
$num_col = floor(($table_width) / ($print->PhotoWidth + $print->Gutter));
$col_width = floor($table_width / $num_col);
$cell_padding = floor($print->Gutter / 2);
$path = "/var/www/".$_SESSION['client']."/photos/";

echo <<<HEADER
<html>
<head>
<meta http-equiv="content-type" content="text/html;charset={$_SESSION['charset']}">
<title></title>
<style>
.table {
    page-break-inside:avoid;
}
.caption {
    font-family: "{$print->Font}";
    font-size: {$print->PointSize}pt;
}
</style>
</head>
<body bgcolor="#ffffff">
HEADER;

$list_no_hh = "";
$list_no_photo = "";
$num_no_hh = 0;
$num_no_photo = 0;

if (!$pid_list || $pid_list == "") {
  echo "No list of Person ID's passed from previous screen.";
  exit;
}
if ($data_type == "household") {
  $sql = "SELECT DISTINCT person.HouseholdID, household.Photo, PhotoCaption, LabelName ".
  "from person LEFT JOIN household ON person.HouseholdID=household.HouseholdID ".
  "WHERE person.PersonID IN (".$pid_list.") ORDER BY FIND_IN_SET(PersonID,'".$pid_list."')";
} else {
  $sql = "SELECT PersonID, FullName, Furigana, person.Photo FROM person LEFT JOIN household ".
    "ON person.HouseholdID=household.HouseholdID WHERE person.PersonID IN (".$pid_list.") ORDER BY Furigana";
}
if (!$result = mysql_query($sql)) {
  echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)<br>");
  exit;
}
$col=1;
$in_table = 0;
while ($row = mysql_fetch_object($result)) {
  if ($row->Photo == 0) {
    if (($data_type == "household") && ($row->Members == 1)) {    //only one member, so use individual photo and info
      $sql = "SELECT PersonID,FullName,Furigana,Photo FROM person WHERE HouseholdID=".$row->HouseholdID;
      if (!$result = mysql_query($sql)) {
        echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)<br>");
        exit;
      }
      $member = mysql_fetch_object($result);
      if ($member->Photo == 1) {
        $photo = "p{$member->PersonID}";
        $caption = readable_name($member->FullName, $member->Furigana);
      } else if ($show_blanks) {
        $photo = "no_photo";
        $caption = $row->LabelName;
      } else {
        continue;
      }
    } else if ($show_blanks) {
      $photo = "no_photo";
      $caption = ($data_type == "household" ? $row->LabelName : readable_name($row->FullName, $row->Furigana));
    } else {
      continue;
    }
  } else {
    if ($data_type == "household") {
      $photo = "h{$row->HouseholdID}";
      $caption = $row->PhotoCaption;
    } else {
      $photo = "p{$row->PersonID}";
      $caption = readable_name($row->FullName, $row->Furigana);
    }
  }
  if ($col == 1) {
    echo "<table width=\"{$table_width}\" border=\"0\" cellpadding=\"0\" cellpadding=\"{$cellpadding}\">\n  <tr>\n";
    $in_table = 1;
  }
  echo "    <td width=\"{$col_width}\" align=\"center\" valign=\"bottom\">\n";
    
  //some code to calculate max dimensions without distorting the aspect ratio
  $jpgsize = GetImageSize($path.$photo.".jpg");
  $jpgwidth = $jpgsize[0];
  $jpgheight = $jpgsize[1];
  $x_ratio = $print->PhotoWidth / $jpgwidth;
  $y_ratio = $print->PhotoHeight / $jpgheight;
  if( ($jpgwidth <= $print->PhotoWidth) && ($jpgheight <= $print->PhotoHeight) ) {
    $imgwidth = $jpgwidth;
    $imgheight = $jpgheight;
  } elseif (($x_ratio * $jpgheight) < $print->PhotoHeight) {
    $imgheight = ceil($x_ratio * $jpgheight);
    $imgwidth = $print->PhotoWidth;
  } else {
    $imgwidth = ceil($y_ratio * $jpgwidth);
    $imgheight = $print->PhotoHeight;
  }
    
  echo "      <img src=\"photo.php?f={$photo}\" height=\"{$imgheight}\" width=\"{$imgwidth}\" alt=\"Photo\"><br>\n";
  echo "      <font class=caption>{$caption}</font>\n    </td>\n";
  if ($col == $num_col) {
    echo "  </tr>\n</table>\n";
    $col = 1;
    $in_table = 0;
  } else {
    $col++;
  }
}
if ($in_table) {
  while ($col <= $num_col) {
    echo "    <td width=\"{$col_width}\">&nbsp;</td>\n";
    $col++;
  }
  echo "  </tr>\n</table>\n";
}

?>
</body>
</html>