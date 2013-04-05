<?php
include("functions.php");
include("accesscontrol.php");
header1(_("Household Information"));
?>
<meta http-equiv="expires" content="0">
<link rel="stylesheet" type="text/css" href="style.php?table=1" />
<script type="text/JavaScript" src="js/jquery.js"></script>
<script type="text/JavaScript" src="js/tablesorter.js"></script>
<script type="text/javascript">
jpg_regexp = /\.[Jj][Pp][Gg]$/;
function validate() {
  if ((document.photoform.photofile.value) && (!jpg_regexp.test(document.photoform.photofile.value))) {
    alert("Only JPG files can be accepted for photos.");
    document.photoform.photofile.value = "";
    return false;
  } else {
    return true;
  }
}
</script>
<? header2(1);

if (!$hhid) {
  echo "HouseholdID not passed.  You cannot call this page directly.";
  exit;
}

if ($newphoto) {
  if (is_uploaded_file($_FILES['photofile']['tmp_name'])) {
    $photofile = "/var/www/".$_SESSION['client']."/photos/h".$hhid.".jpg";
    echo "File path is $photofile.<br />";
    if (move_uploaded_file($_FILES['photofile']['tmp_name'], $photofile)) {
      echo "File is valid, and was successfully uploaded.<br />";
      list($width, $height) = getimagesize($photofile);
      if ($width > $_SESSION['hphoto_maxwidth']) {
        $targetheight = round($_SESSION['hphoto_targetwidth'] * ($height / $width));
        ($targetimage = imagecreatetruecolor($_SESSION['hphoto_targetwidth'], $targetheight)) or die("Failed to create new image for resizing.");
        ($origimage = imagecreatefromjpeg($photofile)) or die("Failed to create an image of the photo for resizing.");
        imagecopyresampled($targetimage, $origimage, 0, 0, 0, 0, $_SESSION['hphoto_targetwidth'], $targetheight, $width, $height) or die("Failed to resize image.");
        imagejpeg($targetimage, $photofile, 90) or die("Failed to save resized photo.");
        echo "Photo was resized to ".$_SESSION['hphoto_targetwidth']." x $targetheight.<br />";
      }
      $sql = "UPDATE household SET Photo=1 WHERE HouseholdID=$hhid LIMIT 1";
      if (!$result = mysql_query($sql)) {
        echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)");
        exit;
      }
    } else {
      echo "File upload failed.  Here's some debugging info:\n";
      print_r($_FILES);
      exit;
    }
  }
  $sql = "UPDATE household SET PhotoCaption='".$caption."' WHERE HouseholdID=$hhid LIMIT 1";
  if (!$result = mysql_query($sql)) {
    echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)");
    exit;
  }
  echo "<script type=\"text/javascript\">\nwindow.location=\"household.php?hhid=".$hhid."\";\n</script>\n";
  exit;
}

if (!$result = mysql_query("SELECT household.*, postalcode.* FROM household LEFT JOIN postalcode "
."ON household.PostalCode=postalcode.PostalCode WHERE HouseholdID=$hhid")) {
  echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b>");
  exit;
}
if (mysql_num_rows($result) == 0) {
  echo("<b>Failed to find a record for HouseholdID $hhid.</b>");
  exit;
}
$hh = mysql_fetch_object($result);

echo "<h1 id=\"title\">"._("Household Information")."</h1>\n";
echo "<table border=\"0\" cellpadding=\"5\" cellspacing=\"0\"><tr><td align=center valign=middle>\n";
if ($hh->Photo) {
  echo "<table border=\"1\" cellspacing=\"0\" cellpadding=\"0\"><tr><td align=center>";
  echo "<img name=photoimg border=0 src=\"photo.php?f=h".$hhid."\" width=300 hspace=5 vspace=2><br />\n";
  echo $hh->PhotoCaption."</td></tr></table><br />\n";
} else {
  echo "<p>"._("No photo")."</p>\n";
}
echo "<form name=\"photoform\" enctype=\"multipart/form-data\" action=\"household.php\" method=POST onsubmit=\"return validate();\">\n";
echo "Upload photo: <input name=photofile type=file size=40><br />\n";
echo "Caption: <input type=text name=caption value=\"$hh->PhotoCaption\" size=50><br />\n";
echo "<input type=hidden name=MAX_FILE_SIZE value=5000000>\n";
echo "<input type=hidden name=photo value=\"$hh->Photo\">\n";
echo "<input type=hidden name=hhid value=\"$hhid\">\n";
echo "<input type=submit name=newphoto value=\"Update Photo and Caption\"></form>\n";

echo "</td><td>";
if ($hh->NonJapan) {    // There is a non-Japanese address
  echo "<b>&nbsp;&nbsp;"._("Address").":</b><br />\n".db2table($hh->LabelName)."<br />\n".db2table($hh->Address);
} elseif ($hh->PostalCode) {    // There is a Japanese address
  echo "<b>&nbsp;&nbsp;Address:</b><br />\n$hh->PostalCode $hh->Prefecture$hh->ShiKuCho "
  .db2table($hh->Address)."<br />\n".db2table($hh->LabelName)."<br />\n";
  if ($_SESSION['romajiaddresses']=="yes") {
    echo "<b>&nbsp;&nbsp;Romaji Address:</b><br />\n".db2table($hh->RomajiAddress)." ".db2table($hh->Romaji)
    ." $hh->PostalCode<br />\n";
  }
} else {
  echo "No address listed.<br />\n";
}
if ($hh->Phone or $hh->FAX) echo "&nbsp;<br />\n";
if ($hh->Phone) echo "Phone: <font color=#C00000><b>".$hh->Phone."</b></font><br />\n";
if ($hh->FAX) echo "FAX: <font color=#00C000>".$hh->FAX."</font><br />\n";
echo " &nbsp;<br /> &nbsp;<br /><font color=#0000C0>(To change the above information, select any<br />\n";
echo "member below and click &quot;Edit This Record&quot;.)</font><br />&nbsp;<br />&nbsp;<br />\n";
echo "</td></tr></table>\n";
echo "<div class=\"section\"><h3 class=\"section-title\">"._("Household Members")."</h3>";

$sql = "SELECT * FROM person WHERE HouseholdID=$hhid "
."ORDER BY FIELD(Relation,'Child','Spouse','Main') DESC, Birthdate";
if (!$result = mysql_query($sql)) {
  echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)");
  exit;
}
if (mysql_num_rows($result) == 0) {
  echo _("This household has no members!");
} else {
  echo "<table id=\"member-table\" class=\"tablesorter\">\n";
  echo "<thead><tr><th>"._("Name")."</th><th>"._("Photo")."</th><th>"._("Relation in<br />Household")."</th><th>"._("Sex")."</th><th>"._("Birthdate")."</th><th>"._("Cell Phone")."</th><th>"._("Email")."</th></tr></thead>\n<tbody>";
  while ($row = mysql_fetch_object($result)) {
    echo "<tr><td class=\"name-for-display\"><span style=\"display:none\">".$row->Furigana."</span>";
    echo "<a href=\"individual.php?pid=".$row->PersonID."\">".
      readable_name($row->FullName,$row->Furigana,0,0,"<br />")."</a></td>\n";
    echo "<td class=\"photo\">".($row->Photo==1 ? "<img border=0 src=\"photo.php?f=p".$row->PersonID."\" width=50>" : "")."</td>\n";
    echo "<td class=\"relation\">".$row->Relation."</td>\n";
    echo "<td class=\"sex\">".($row->Sex ? ($row->Sex=="F"?_("Female"):_("Male")) : "")."</td>\n";
    echo "<td class=\"birthdate\">";
    if ($row->Birthdate && $row->Birthdate != "0000-00-00") {
      if (ereg("^1900-",$row->Birthdate)) {
        echo substr($row->Birthdate,5);
      } else {
        echo $row->Birthdate."<br />"._("Age")." ".age($row->Birthdate);
      }
    }
    echo "</td>\n";
    echo "<td class=\"cellphone\">".$row->CellPhone."</td>\n";
    echo "<td class=\"email\">".($row->Email ? "<a href=\"mailto:".$row->Email."\">".$row->Email."</a>" : "")."</td></tr>";
  }
  echo "  </table>\n";
  echo "<h3><a href=\"edit.php?hhid=".$hhid."\">"._("Add a New Member to this Household")."</a></h3>";
  echo "</div>";
}

footer(1);
?>
