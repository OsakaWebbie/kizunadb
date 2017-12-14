<?php
include("functions.php");
include("accesscontrol.php");

//echo '<pre>'.print_r($_GET).'</pre>';
$result = sqlquery_checked("SELECT * FROM photoprint WHERE PhotoPrintName='".urldecode($_GET['photo_print_name'])."'");
$print = mysqli_fetch_object($result);
$table_width = floor(($print->PaperWidth - $print->PaperLeftMargin - $print->PaperRightMargin) * 96 / 25.4);  // assuming 96 dpi
$num_col = floor(($table_width) / ($print->PhotoWidth + $print->Gutter));
$col_width = floor($table_width / $num_col);
$cellpadding = floor($print->Gutter / 2);
$path = CLIENT_PATH."/photos/";
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="icon" type="image/x-icon" href="kizunadb.ico">
<style>
table {
    page-break-inside:avoid;
}
.caption {
    font-family: "<?=$print->Font?>";
    font-size: <?=$print->PointSize?>pt;
}
</style>
</head>
<body>
<?php

$list_no_hh = "";
$list_no_photo = "";
$num_no_hh = 0;
$num_no_photo = 0;

if (!$pid_list || $pid_list == "") {
  echo "No list of Person ID's passed from previous screen.";
  exit;
}
if ($_GET['data_type'] == "household") {
  $sql = "SELECT DISTINCT person.HouseholdID, household.Photo, PhotoCaption, LabelName ".
      "from person LEFT JOIN household ON person.HouseholdID=household.HouseholdID ".
      "WHERE person.PersonID IN (".$pid_list.") ORDER BY FIND_IN_SET(PersonID,'".$pid_list."')";
} else {
  $sql = "SELECT PersonID, FullName, Furigana, person.Photo FROM person LEFT JOIN household ".
      "ON person.HouseholdID=household.HouseholdID WHERE person.PersonID IN (".$pid_list.") ORDER BY Furigana";
}
$result = sqlquery_checked($sql);
$col=1;
$in_table = 0;
while ($row = mysqli_fetch_object($result)) {
  if ($row->Photo == 0) {
    if (($_GET['data_type'] == "household") && ($row->Members == 1)) {    //only one member, so use individual photo and info
      $sql = "SELECT PersonID,FullName,Furigana,Photo FROM person WHERE HouseholdID=".$row->HouseholdID;
      $result = sqlquery_checked($sql);
      $member = mysqli_fetch_object($result);
      if ($member->Photo == 1) {
        $photo = "p{$member->PersonID}";
        $filepath = $path.$photo.'.jpg';
        $caption = readable_name($member->FullName, $member->Furigana);
      } else if ($_GET['show_blanks']) {
        $photo = "no_photo";
        $filepath = 'graphics/no_photo.jpg';
        $caption = $row->LabelName;
      } else {
        continue;
      }
    } else if (!empty($_GET['show_blanks'])) {
      $photo = "no_photo";
      $filepath = 'graphics/no_photo.jpg';
      $caption = ($_GET['data_type'] == "household" ? $row->LabelName : readable_name($row->FullName, $row->Furigana));
    } else {
      continue;
    }
  } else {
    if ($_GET['data_type'] == "household") {
      $photo = "h{$row->HouseholdID}";
      $filepath = $path.$photo.'.jpg';
      $caption = $row->PhotoCaption;
    } else {
      $photo = "p{$row->PersonID}";
      $filepath = $path.$photo.'.jpg';
      $caption = readable_name($row->FullName, $row->Furigana);
    }
  }
  if ($col == 1) {
    echo "<table width=\"{$table_width}\" border=\"0\" cellpadding=\"{$cellpadding}\">\n  <tr>\n";
    $in_table = 1;
  }
  echo "    <td width=\"{$col_width}\" align=\"center\" valign=\"bottom\">\n";

  //some code to calculate max dimensions without distorting the aspect ratio
  $jpgsize = GetImageSize(is_file($filepath) ? $filepath : 'graphics/missing_file.jpg');
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

  echo "      <img src=\"".($photo=='no_photo'?'graphics/no_photo.jpg':"photo.php?f={$photo}")."\" height=\"{$imgheight}\" width=\"{$imgwidth}\" alt=\"Photo\"><br>\n";
  echo "      <span class=\"caption\">{$caption}</span>\n    </td>\n";
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