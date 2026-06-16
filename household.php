<?php
include("functions.php");
include("accesscontrol.php");

pageheader(_("Household Information"), 1);
?>
<style>
.hh-layout {
  display: flex;
  flex-wrap: wrap;
  gap: 1.5em;
  margin-bottom: 1.5em;
  align-items: flex-start;
}
.hh-info { flex: 1 1 280px; }
.section figure {
  border: 1px solid #ccc;
  max-width: 600px;
  margin: 0 0 0.5em;
  padding: 0;
  text-align: center;
}
.section figure img { display: block; width: 100%; height: auto; }
.section figcaption { padding: 0.3em 0.5em; }
</style>
<?php

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
  echo "<script type='text/javascript'>\nwindow.location=\"household.php?hhid=$hhid\";\n</script>\n";
  exit;
}

$result = sqlquery_checked("SELECT household.*, postalcode.* FROM household LEFT JOIN postalcode "
."ON household.PostalCode=postalcode.PostalCode WHERE HouseholdID=$hhid");
if (mysqli_num_rows($result) == 0) {
  echo("<b>Failed to find a record for HouseholdID $hhid.</b>");
  exit;
}
$hh = mysqli_fetch_object($result);

echo "<h1 id='title'>"._("Household Information")."</h1>\n";
echo "<div class='hh-layout'>\n";

// Left: address and contact info
echo "<div class='hh-info'>\n";
if ($hh->NonJapan) {    // There is a non-Japanese address
  echo "<h3>"._("Address").":</h3>\n<p>".d2h($hh->LabelName)."<br>\n".d2h($hh->Address)."</p>\n";
} elseif ($hh->PostalCode) {    // There is a Japanese address
  echo "<h3>"._("Address").":</h3>\n<p>$hh->PostalCode $hh->Prefecture$hh->ShiKuCho "
    .d2h($hh->Address)."<br>\n".d2h($hh->LabelName)."</p>\n";
  if ($_SESSION['romajiaddresses']=="yes") {
    echo "<h3>"._("Romaji Address").":</h3>\n<p>".d2h($hh->RomajiAddress)." ".d2h($hh->Romaji)
      ." $hh->PostalCode</p>\n";
  }
} else {
  echo "<p>"._("No address listed.")."</p>\n";
}
if ($hh->Phone) echo "<h3>"._("Phone").": ".d2h($hh->Phone)."</h3>\n";
if ($hh->FAX) echo "<p>"._("FAX").": ".d2h($hh->FAX)."</p>\n";
echo "<p class='comment' style='margin:15px 0 5px 0'>"._('To change the above information, select any member below, '.
    'and on the page that opens, click "Edit This Record".')."</p>\n";
echo "</div>\n";

// Right: photo and upload form
echo "<div class='section'>\n";
if ($hh->Photo) {
  echo "<figure>\n";
  echo "<img src='photo.php?f=h$hhid' alt='"._("Household photo")."'>\n";
  if ($hh->PhotoCaption) echo "<figcaption>".d2h($hh->PhotoCaption)."</figcaption>\n";
  echo "</figure>\n";
} else {
  echo "<p style='text-align:center;margin:10px 0 20px 0'>("._("No photo").")</p>\n";
}
?>
<form name='photoform' enctype='multipart/form-data' action='household.php' method='POST' onsubmit='return validate();'>
<p style='display:flex;align-items:center;gap:0.75em'><label><?=_("Upload photo")?>: <input name='photofile' id='photofile' type='file' accept='image/jpeg'></label>
<img id='preview-img' style='display:none;height:50px;width:auto' alt='<?=_("Preview")?>'></p>
<p><label><?=_("Caption")?>: <input type='text' name='caption' value='<?=htmlspecialchars($hh->PhotoCaption, ENT_QUOTES)?>' size='40'></label></p>
<input type='hidden' name='photo' value='<?=(int)$hh->Photo?>'>
<input type='hidden' name='hhid' value='<?=(int)$hhid?>'>
<p style='text-align:center'><input type='submit' id='newphoto' name='newphoto' value='<?=_("Update Photo and Caption")?>'></p>
</form>
</div>

</div><?php // end .hh-layout ?>
<div class='section'><h3 class='section-title'><?=_("Household Members")?></h3>
<?php

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
    'classes' => 'center sorter-digit',
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

  echo "<h3><a href='edit.php?hhid=$hhid'>"._("Add a New Member to this Household")."</a></h3>";
  echo "</div>";
}

load_scripts(['jquery', 'jqueryui']);
?>
<script type="text/javascript">
var jpg_regexp = /\.[Jj][Pp][Gg]$/;
function validate() {
  if ((document.photoform.photofile.value) && (!jpg_regexp.test(document.photoform.photofile.value))) {
    alert(<?php echo json_encode(_("Only JPG files can be accepted for photos.")); ?>);
    document.photoform.photofile.value = "";
    return false;
  } else {
    return true;
  }
}
$(document).ready(function() {
  $('#newphoto').button();
  document.getElementById('photofile').addEventListener('change', function() {
    var file = this.files[0];
    if (file) {
      document.getElementById('preview-img').src = URL.createObjectURL(file);
      document.getElementById('preview-img').style.display = '';
    } else {
      document.getElementById('preview-img').style.display = 'none';
    }
  });
});
</script>
<?php
footer();
?>
