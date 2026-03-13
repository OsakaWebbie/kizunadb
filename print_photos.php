<?php
include("functions.php");
include("accesscontrol.php");

// Returns bb (PostScript points) and display dimensions (mm) for a JPEG photo.
// dvipdfmx reads image natural size from the JFIF APP0 header DPI, not EXIF.
// bb must match that natural size (px * 72/dpi) so graphicx computes the correct scale.
function photo_info($filepath, $photo_width_mm, $max_height_mm) {
  $imgsize = @getimagesize($filepath);
  if (!$imgsize || $imgsize[0] <= 0 || $imgsize[1] <= 0) {
    return ['bb' => '0 0 100 100', 'disp_w' => $photo_width_mm, 'disp_h' => $max_height_mm];
  }
  // Read DPI from JFIF APP0 header (bytes 0-17): SOI FFD8, APP0 FFE0, length, "JFIF\0",
  // version (2), units (1), Xdensity (2), Ydensity (2).
  $dpi = 72;
  $fh = @fopen($filepath, 'rb');
  if ($fh) {
    $hdr = fread($fh, 18);
    fclose($fh);
    if (strlen($hdr) >= 18
        && $hdr[0] === "\xFF" && $hdr[1] === "\xD8"    // SOI
        && $hdr[2] === "\xFF" && $hdr[3] === "\xE0"    // APP0 marker
        && substr($hdr, 6, 5) === "JFIF\x00") {        // JFIF identifier
      $units   = ord($hdr[13]);
      $x_dens  = (ord($hdr[14]) << 8) | ord($hdr[15]);
      if ($x_dens > 0) {
        if ($units === 1) $dpi = $x_dens;              // dots per inch
        elseif ($units === 2) $dpi = $x_dens * 2.54;  // dots per cm
        // units === 0 means pixel aspect ratio only — keep 72 DPI default
      }
    }
  }
  $bb_w = round($imgsize[0] * 72 / $dpi);
  $bb_h = round($imgsize[1] * 72 / $dpi);
  $aspect = $imgsize[0] / $imgsize[1];
  $disp_w = $photo_width_mm;
  $disp_h = round($photo_width_mm / $aspect, 2);
  if ($disp_h > $max_height_mm) {
    $disp_h = $max_height_mm;
    $disp_w = round($max_height_mm * $aspect, 2);
  }
  return ['bb' => "0 0 $bb_w $bb_h", 'disp_w' => $disp_w, 'disp_h' => $disp_h];
}

function latex_escape($text) {
  $text = str_replace('\\', '\textbackslash{}', $text);
  $text = str_replace(['&', '%', '$', '#', '_', '{', '}'], ['\&', '\%', '\$', '\#', '\_', '\{', '\}'], $text);
  $text = str_replace(['~', '^'], ['\textasciitilde{}', '\textasciicircum{}'], $text);
  return $text;
}

$result = sqlquery_checked("SELECT * FROM photoprint WHERE PhotoPrintName='".urldecode($_GET['photo_print_name'])."'");
$print = mysqli_fetch_object($result);

if (!$pid_list || $pid_list == "") {
  die("No list of Person IDs passed from previous screen.");
}

// Column calculation (all in mm); N-1 gutters between N columns, none at outer edges
$usable_width   = $print->PaperWidth - $print->PaperLeftMargin - $print->PaperRightMargin;
$num_col        = max(1, (int)$print->NumColumns);
$photo_width_mm = round(($usable_width - ($num_col - 1) * $print->Gutter) / $num_col, 2);

// Absolute file paths for LaTeX
$photos_path  = CLIENT_PATH . '/photos/';
$graphics_path = getcwd() . '/graphics/';

// Fetch photo data
if ($_GET['data_type'] == "household") {
  $sql = "SELECT DISTINCT person.HouseholdID, household.Photo, PhotoCaption, LabelName ".
      "from person LEFT JOIN household ON person.HouseholdID=household.HouseholdID ".
      "WHERE person.PersonID IN (".$pid_list.") ORDER BY FIND_IN_SET(PersonID,'".$pid_list."')";
} else {
  $sql = "SELECT PersonID, FullName, Furigana, person.Photo FROM person LEFT JOIN household ".
      "ON person.HouseholdID=household.HouseholdID WHERE person.PersonID IN (".$pid_list.") ORDER BY Furigana";
}
$result = sqlquery_checked($sql);

// Build ordered list of photo entries
$photos = [];
while ($row = mysqli_fetch_object($result)) {
  if ($row->Photo == 0) {
    if (($_GET['data_type'] == "household") && ($row->Members == 1)) {
      $sql2 = "SELECT PersonID,FullName,Furigana,Photo FROM person WHERE HouseholdID=".$row->HouseholdID;
      $result2 = sqlquery_checked($sql2);
      $member = mysqli_fetch_object($result2);
      if ($member->Photo == 1) {
        $filepath = $photos_path . "p{$member->PersonID}.jpg";
        $caption  = readable_name($member->FullName, $member->Furigana);
      } else if (!empty($_GET['show_blanks'])) {
        $filepath = $graphics_path . 'no_photo.jpg';
        $caption  = $row->LabelName;
      } else {
        continue;
      }
    } else if (!empty($_GET['show_blanks'])) {
      $filepath = $graphics_path . 'no_photo.jpg';
      $caption  = ($_GET['data_type'] == "household" ? $row->LabelName : readable_name($row->FullName, $row->Furigana));
    } else {
      continue;
    }
  } else {
    if ($_GET['data_type'] == "household") {
      $filepath = $photos_path . "h{$row->HouseholdID}.jpg";
      $caption  = $row->PhotoCaption;
    } else {
      $filepath = $photos_path . "p{$row->PersonID}.jpg";
      $caption  = readable_name($row->FullName, $row->Furigana);
    }
    if (!is_file($filepath)) {
      $filepath = $graphics_path . 'missing_file.jpg';
    }
  }
  $info = photo_info($filepath, $photo_width_mm, $print->PhotoMaxHeight);
  $photos[] = ['filepath' => $filepath, 'caption' => $caption] + $info;
}

if (empty($photos)) {
  die("No photos to display.");
}

$tmppath  = '/var/www/tmp/';
$fileroot = CLIENT.'-'.$_SESSION['userid'].'-photos-'.date('His');

/* ALL OUTPUT FROM NOW GOES INTO THE TEX FILE */
ob_start();
echo "\xEF\xBB\xBF";  //UTF-8 Byte Order Mark
?>
\documentclass{ujarticle}
\usepackage{plext}
\usepackage[uplatex]{otf}
\usepackage[T1]{fontenc}
\usepackage{lmodern}
\usepackage{array}
\usepackage[paperwidth=<?=$print->PaperWidth?>mm,paperheight=<?=$print->PaperHeight?>mm,top=<?=$print->PaperTopMargin?>mm,bottom=<?=$print->PaperBottomMargin?>mm,left=<?=$print->PaperLeftMargin?>mm,right=<?=$print->PaperRightMargin?>mm]{geometry}
\usepackage[dvipdfmx]{graphicx}
\pagestyle{empty}
\begin{document}
\sffamily
\gtfamily
\fontsize{<?=$print->PointSize?>}{<?=$print->PointSize*1.2?>}\selectfont
<?php
$chunks = array_chunk($photos, $num_col);
foreach ($chunks as $row_photos) {
  // Pad last row with nulls to fill the grid
  while (count($row_photos) < $num_col) {
    $row_photos[] = null;
  }
  $col_spec = '@{}'.implode('@{\hspace{'.$print->Gutter.'mm}}', array_fill(0, $num_col, '>{\centering\arraybackslash}p{'.$photo_width_mm.'mm}')).'@{}';
  echo '\noindent\begin{tabular}{'.$col_spec.'}'."\n";
  $cells = [];
  foreach ($row_photos as $photo) {
    if ($photo === null) {
      $cells[] = '';
    } else {
      $cell  = '\includegraphics[bb='.$photo['bb'].',width='.$photo['disp_w'].'mm,height='.$photo['disp_h'].'mm]{'.$photo['filepath'].'}';
      $cell .= '\newline '.latex_escape($photo['caption']);
      $cells[] = $cell;
    }
  }
  echo implode(" &\n", $cells)." \\\\\n";
  echo '\end{tabular}\par\vspace{'.$print->Gutter.'mm}'."\n";
}
?>
\end{document}
<?php
file_put_contents($tmppath.$fileroot.".tex", ob_get_contents());
ob_end_clean();

// RUN TEX COMMANDS TO MAKE PDF

if (is_file("/usr/bin/uplatex")) {
  $commandpath = "/usr/bin/";
} elseif (is_file("/usr/local/bin/uplatex")) {
  $commandpath = "/usr/local/bin/";
} else {
  die("Error: cannot find needed commands (uplatex and dvipdfmx) in /usr/bin/ or /usr/local/bin/.");
}
exec("cd $tmppath;{$commandpath}uplatex -interaction=batchmode --output-directory=$tmppath $fileroot", $output, $return);
if (!is_file("$tmppath$fileroot.dvi")) {
  die("Error processing '$tmppath$fileroot.tex':<br /><br /><pre>".print_r($output,TRUE)."</pre>");
}
exec("cd $tmppath;{$commandpath}dvipdfmx $fileroot", $output, $return);
if (!is_file("$tmppath$fileroot.pdf")) {
  die("Error processing '$tmppath$fileroot.dvi':<br /><br /><pre>".print_r($output,TRUE)."</pre>");
}

// DELIVER PDF TO BROWSER

header("Content-Type: application/pdf");
header('Content-Disposition: inline; filename="photos_'.date('Y-m-d').'.pdf"');
header("Content-Transfer-Encoding: binary");
@readfile("$tmppath$fileroot.pdf");
?>
