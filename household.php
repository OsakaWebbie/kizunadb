<?php
include("functions.php");
include("accesscontrol.php");

header1(_("Household Information"));
?>
<link rel="stylesheet" href="style.php?jquery=1&table=1" type="text/css" />
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
<?php
header2(1);

if (empty($hhid)) {
  echo "HouseholdID not passed.  You cannot call this page directly.";
  exit;
}

if (!empty($newphoto)) {
  if (is_uploaded_file($_FILES['photofile']['tmp_name'])) {
    $photofile = CLIENT_PATH."/photos/h".$hhid.".jpg";
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
      $result = sqlquery_checked($sql);
    } else {
      echo "File upload failed.  Here's some debugging info:\n";
      print_r($_FILES);
      exit;
    }
  }
  $sql = "UPDATE household SET PhotoCaption='".$caption."' WHERE HouseholdID=$hhid LIMIT 1";
  $result = sqlquery_checked($sql);
  echo "<script type=\"text/javascript\">\nwindow.location=\"household.php?hhid=".$hhid."\";\n</script>\n";
  exit;
}

$result = sqlquery_checked("SELECT household.*, postalcode.* FROM household LEFT JOIN postalcode "
."ON household.PostalCode=postalcode.PostalCode WHERE HouseholdID=$hhid");
if (mysqli_num_rows($result) == 0) {
  echo("<b>Failed to find a record for HouseholdID $hhid.</b>");
  exit;
}
$hh = mysqli_fetch_object($result);

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
if ($hh->Phone) echo "Phone: <span style='color:#C00000'><b>".$hh->Phone."</b></span><br />\n";
if ($hh->FAX) echo "FAX: <span style='color:#00C000'>".$hh->FAX."</span><br />\n";
echo " &nbsp;<br /> &nbsp;<br /><span style='color:#0000C0'>(To change the above information, select any<br />\n";
echo "member below and click &quot;Edit This Record&quot;.)</span><br />&nbsp;<br />&nbsp;<br />\n";
echo "</td></tr></table>\n";
echo "<div class=\"section\"><h3 class=\"section-title\">"._("Household Members")."</h3>";

$sql = "SELECT PersonID FROM person WHERE HouseholdID=$hhid "
."ORDER BY FIELD(Relation,'Child','Spouse','Main') DESC, Birthdate";
$result = sqlquery_checked($sql);
if (mysqli_num_rows($result) == 0) {
  echo _("This household has no members!");
} else {
  // Collect PersonIDs for flextable
  $person_ids = array();
  while ($row = mysqli_fetch_object($result)) {
    $person_ids[] = $row->PersonID;
  }

  require_once("flextable.php");

  // Fallback default if config missing: name,photo,relation,age,sex
  $showcols = ',' . ($_SESSION['household_showcols'] ?? 'name,photo,relation,age,sex') . ',';

  $tableopt = (object) [
    'ids' => implode(',', $person_ids),
    'keyfield' => 'person.PersonID',
    'tableid' => 'members',
    'heading' => '',
    'order' => "FIELD(Relation,'Main','Spouse','Child','Other'), Birthdate",
    'cols' => array()
  ];

  // 1. PersonID
  $tableopt->cols[] = (object) [
    'key' => 'personid',
    'sel' => 'person.PersonID',
    'label' => _('ID'),
    'show' => (stripos($showcols, ',personid,') !== FALSE)
  ];

  // 2. Name-related columns (all hideable for flexibility)
  $tableopt->cols[] = (object) [
    'key' => 'name',
    'sel' => 'person.Name',
    'label' => _('Name'),
    'show' => (stripos($showcols, ',name,') !== FALSE)
  ];

  $tableopt->cols[] = (object) [
    'key' => 'fullname',
    'sel' => 'person.FullName',
    'label' => _('Full Name'),
    'show' => (stripos($showcols, ',fullname,') !== FALSE)
  ];

  $tableopt->cols[] = (object) [
    'key' => 'furigana',
    'sel' => 'person.Furigana',
    'label' => ($_SESSION['furiganaisromaji']=='yes' ? _('Romaji') : _('Furigana')),
    'show' => (stripos($showcols, ',furigana,') !== FALSE)
  ];

  // 3. Photo
  $tableopt->cols[] = (object) [
    'key' => 'photo',
    'sel' => 'person.Photo',
    'label' => _('Photo'),
    'show' => (stripos($showcols, ',photo,') !== FALSE),
    'sortable' => false
  ];

  // 4. Relation (with hidden sort prefix for custom FIELD ordering)
  $tableopt->cols[] = (object) [
    'key' => 'relation',
    'sel' => "CONCAT('<span style=\"display:none\">',FIELD(Relation,'Main','Spouse','Child','Other'),'</span>',person.Relation)",
    'label' => _('Relation in Household'),
    'show' => (stripos($showcols, ',relation,') !== FALSE),
    'sort' => 1
  ];

  // 5. Sex
  $tableopt->cols[] = (object) [
    'key' => 'sex',
    'sel' => 'person.Sex',
    'label' => _('Sex'),
    'show' => (stripos($showcols, ',sex,') !== FALSE)
  ];

  // 6. Age (separate column using the age calculation)
  $tableopt->cols[] = (object) [
    'key' => 'age',
    'sel' => "IF(person.Birthdate='0000-00-00','',TIMESTAMPDIFF(YEAR,person.Birthdate,CURDATE()))",
    'label' => _('Age'),
    'show' => (stripos($showcols, ',age,') !== FALSE),
    'classes' => 'center',
    'table' => 'person'
  ];

  // 7. Birthdate (just the date)
  $tableopt->cols[] = (object) [
    'key' => 'birthdate',
    'sel' => 'person.Birthdate',
    'label' => _('Birthdate'),
    'show' => (stripos($showcols, ',birthdate,') !== FALSE),
    'classes' => 'center',
    'sort' => 2
  ];

  // 8. Categories (lazy-loaded for performance)
  $tableopt->cols[] = (object) [
    'key' => 'categories',
    'sel' => "GROUP_CONCAT(Category ORDER BY Category SEPARATOR '\\n')",
    'label' => _('Categories'),
    'show' => (stripos($showcols, ',categories,') !== FALSE),
    'lazy' => TRUE,
    'join' => 'LEFT JOIN percat ON person.PersonID=percat.PersonID LEFT JOIN category ON percat.CategoryID=category.CategoryID'
  ];

  // 9. The rest (contact info, household, etc.)
  $tableopt->cols[] = (object) [
    'key' => 'cellphone',
    'sel' => 'person.CellPhone',
    'label' => _('Cell Phone')
  ];

  $tableopt->cols[] = (object) [
    'key' => 'email',
    'sel' => 'person.Email',
    'label' => _('Email'),
    'show' => (stripos($showcols, ',email,') !== FALSE)
  ];

  $tableopt->cols[] = (object) [
    'key' => 'url',
    'sel' => 'person.URL',
    'label' => _('URL'),
    'show' => (stripos($showcols, ',url,') !== FALSE)
  ];

  $tableopt->cols[] = (object) [
    'key' => 'country',
    'sel' => 'person.Country',
    'label' => _('Country'),
    'show' => (stripos($showcols, ',country,') !== FALSE)
  ];

  // 10. Remarks (last)
  $tableopt->cols[] = (object) [
    'key' => 'remarks',
    'sel' => 'person.Remarks',
    'label' => _('Remarks'),
    'show' => FALSE
  ];

  flextable($tableopt);

  echo "<h3><a href=\"edit.php?hhid=".$hhid."\">"._("Add a New Member to this Household")."</a></h3>";
  echo "</div>";
}

footer();
?>
