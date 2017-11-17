<?php
include("functions.php");
include("accesscontrol.php");

print_header("Editing Record...","#FFFFFF",0);

echo "<h3>Editing (or adding) record...<h3>";

if (!empty($_POST['updatehh'])) {
  if (!empty($_POST['nonjapan'])) {
    $_POST['nonjapan'] = "1";
    $_POST['postalcode'] = "";  //I don't trust the field to be empty
    $_POST['romajiaddress'] = "";  //I don't trust the field to be empty
  } else {
    $_POST['nonjapan'] = "0";
  }
  if (empty($_POST['postalcode'])) {
    $_POST['prefecture'] = "";  //I don't trust the field to be empty
    $_POST['shikucho'] = "";  //I don't trust the field to be empty
    $_POST['pcromtext'] = "";  //I don't trust the field to be empty
  } else { // PC passed in
    //check to see if we need to add a PostalCode record
    $request = sqlquery_checked("SELECT * FROM postalcode WHERE PostalCode='".$_POST['postalcode']."'");
    if (mysqli_num_rows($request) == 0) {
      $sql = "INSERT INTO postalcode(PostalCode,Prefecture,ShiKuCho".(isset($_POST['pcromtext'])?",Romaji":"").")".
      " VALUES('".$_POST['postalcode']."','".$_POST['prefecture']."','".$_POST['shikucho']."'".
      (isset($_POST['pcromtext'])?",'".$_POST['pcromtext']."'":"").")";
      sqlquery_checked($sql);
      if (mysqli_affected_rows($db) > 0) echo "The new postal code record was added<br>";
    } else { // found PC record
      $row = mysqli_fetch_object($request);
      $_POST['pcromtext'] = $row->Romaji;
    }
  }
  if (!empty($_POST['householdid'])) {
    $householdid = $_POST['householdid'];
    $sql = "UPDATE household SET NonJapan=".$_POST['nonjapan'].",PostalCode='".$_POST['postalcode']."',".
    "Address='".h2d($_POST['address'])."',".
    "AddressComp='".$_POST['postalcode'].$_POST['prefecture'].$_POST['shikucho'].h2d($_POST['address'])."',";
    if ($_SESSION['romajiaddresses']=="yes") {
      $sql .= "RomajiAddress='".h2d($_POST['romajiaddress'])."',RomajiAddressComp='".
      ($_POST['nonjapan'] ? "" : trim(h2d($_POST['romajiaddress']." ".$_POST['pcromtext'])." ".$_POST['postalcode']))."',";
    }
    $sql .= "Phone='".$_POST['phone']."',FAX='".$_POST['fax']."',LabelName='".h2d($_POST['labelname'])."',UpdDate=CURDATE() ".
    "WHERE HouseholdID=".$_POST['householdid']." LIMIT 1";
    $result = sqlquery_checked($sql);
    if (mysqli_affected_rows($db) > 0) {
      echo "The household record was updated<br>";
    }
  } else {  //new household record
    if ($_SESSION['romajiaddresses']=="yes") {
      $sql = "INSERT INTO household (NonJapan,PostalCode,Address,AddressComp,RomajiAddress,RomajiAddressComp,".
      "Phone,FAX,LabelName,UpdDate) VALUES (".$_POST['nonjapan'].",'".$_POST['postalcode']."','".h2d($_POST['address'])."',".
      "'".$_POST['postalcode'].$_POST['prefecture'].$_POST['shikucho'].h2d($_POST['address'])."',".
      "'".h2d($_POST['romajiaddress'])."','".($_POST['nonjapan'] ? "" : trim(h2d($_POST['romajiaddress']." ".
      $_POST['pcromtext'])." ".$_POST['postalcode']))."','".$_POST['phone']."','".$_POST['fax']."','".h2d($_POST['labelname'])."',CURDATE())";
    } else {
      $sql = "INSERT INTO household (NonJapan,PostalCode,Address,AddressComp,Phone,FAX,LabelName,UpdDate) ".
      "VALUES (".$_POST['nonjapan'].",'".$_POST['postalcode']."','".h2d($_POST['address'])."',".
      "'".$_POST['postalcode'].$_POST['prefecture'].$_POST['shikucho'].h2d($_POST['address'])."',".
      "'".$_POST['phone']."','".$_POST['fax']."','".h2d($_POST['labelname'])."',CURDATE())";
    }
    $result = sqlquery_checked($sql);
    if (mysqli_affected_rows($db) > 0) {
      $householdid = mysqli_insert_id($db);
      $updateper = 1;   //even if no other personal data was changed, we need to add the new household id
      echo "The household record was inserted<br>";
    } else {
      echo "No household record was inserted for some reason.<br>";
    }
  }
}

if (!empty($_POST['updateper'])) {
  if (!empty($_POST['organization'])) {
    $organization = "1";
  } else {
    $organization = "0";
  }
  if (!isset($householdid) || $householdid=='') $householdid=0;
  if (!isset($_POST['birthdate']) || $_POST['birthdate']=='') $_POST['birthdate']='0000-00-00';

  if (!empty($_POST['pid'])) {
    $sql = "UPDATE person SET FullName='".h2d($_POST['fullname'])."', Furigana='".h2d($_POST['furigana'])."', Sex='".$_POST['sex']."', ".
        "HouseholdID=".$householdid.", Relation='".$_POST['relation']."', Title='".h2d($_POST['title'])."', ".
        "CellPhone='".h2d($_POST['cellphone'])."',Email='".h2d($_POST['email'])."',Birthdate='".$_POST['birthdate']."', ".
        "Country='".h2d($_POST['country'])."', URL='".h2d($_POST['URL'])."', Organization=".$organization.", ".
        "Remarks='".h2d($_POST['remarks'])."', UpdDate=CURDATE() WHERE PersonID=".$_POST['pid']." LIMIT 1";
    $result = sqlquery_checked($sql);
    if (mysqli_affected_rows($db) > 0) echo "The person record was updated<br>\n";
  } else {
    $sql = "INSERT INTO person (FullName,Furigana,Sex,HouseholdID,Relation,Title,CellPhone,Email,Birthdate,".
        "Country,URL,Organization,Remarks,UpdDate) VALUES ('".h2d($_POST['fullname'])."','".h2d($_POST['furigana'])."','".
        $_POST['sex']."',".$householdid.",'".$_POST['relation']."','".h2d($_POST['title'])."','".
        h2d($_POST['cellphone'])."','".h2d($_POST['email'])."','".$_POST['birthdate']."','".$_POST['country']."','".
        h2d($_POST['URL'])."',".$organization.",'".h2d($_POST['remarks'])."',CURDATE())";
    $result = sqlquery_checked($sql);
    if (mysqli_affected_rows($db) > 0) {
      $_POST['pid'] = mysqli_insert_id($db);
      echo "The person record was inserted<br>\n";
    } else {
      echo "No person record was inserted for some reason.<br>\n";
    }
  }

  if (is_uploaded_file($_FILES['photofile']['tmp_name'])) {
    $photofile = CLIENT_PATH."/photos/p".$_POST['pid'].".jpg";
    if (move_uploaded_file($_FILES['photofile']['tmp_name'], $photofile)) {
      echo "File is valid, and was successfully uploaded. ";
      list($width, $height) = getimagesize($photofile);
      if ($width > $_SESSION['pphoto_maxwidth']) {
        $targetheight = round($_SESSION['pphoto_targetwidth'] * ($height / $width));
        ($targetimage = imagecreatetruecolor($_SESSION['pphoto_targetwidth'], $targetheight)) or die("Failed to create new image for resizing.");
        ($origimage = imagecreatefromjpeg($photofile)) or die("Failed to create an image of the photo for resizing.");
        imagecopyresampled($targetimage, $origimage, 0, 0, 0, 0, $_SESSION['pphoto_targetwidth'], $targetheight, $width, $height) or die("Failed to resize image.");
        imagejpeg($targetimage, $photofile, 90) or die("Failed to save resized photo.");
        echo "Photo was resized to ".$_SESSION['pphoto_targetwidth']." x $targetheight.<br>";
      }
      $sql = "UPDATE person SET Photo=1 WHERE PersonID=".$_POST['pid']." LIMIT 1";
      $result = sqlquery_checked($sql);
    } else {
      echo "File upload failed.  Here's some debugging info:\n";
      print_r($_FILES);
      exit;
    }
  }
}
echo "<script for=\"window\" event=\"onload\" type=\"text/javascript\">\n";
echo "window.location = \"individual.php?pid=".$_POST['pid']."\";\n";
echo "</script>\n";

print_footer();
?>