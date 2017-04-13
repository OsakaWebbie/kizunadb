<?php
include("functions.php");
include("accesscontrol.php");
extract($_POST, EXTR_SKIP);  //make POST superglobal into plain variables for the ancient code

print_header("Editing Record...","#FFFFFF",0);

echo "<h3 color=green>Editing (or adding) record...<h3>";

if ($updatehh == "1") {
  if ($_POST['nonjapan']) {
    $_POST['nonjapan'] = "1";
    $_POST['postalcode'] = "";  //I don't trust the field to be empty
    $_POST['romajiaddress'] = "";  //I don't trust the field to be empty
  } else {
    $_POST['nonjapan'] = "0";
  }
  if ($_POST['postalcode'] == "") {
    $_POST['prefecture'] = "";  //I don't trust the field to be empty
    $_POST['shikucho'] = "";  //I don't trust the field to be empty
    $_POST['pcromtext'] = "";  //I don't trust the field to be empty
  }
  if ($_POST['postalcode'] != "") {
    //check to see if we need to add a PostalCode record
    $request = sqlquery_checked("SELECT * FROM postalcode WHERE PostalCode='".$_POST['postalcode']."'");
    if (mysqli_num_rows($request) == 0) {
      $sql = "INSERT INTO postalcode(PostalCode,Prefecture,ShiKuCho".(isset($pcromtext)?",Romaji":"").")".
      " VALUES('".$_POST['postalcode']."','".$_POST['prefecture']."','".$_POST['shikucho']."'".
      (isset($pcromtext)?",'".$_POST['pcromtext']."'":"").")";
      sqlquery_checked($sql);
      if (mysqli_affected_rows($db) > 0) echo "The new postal code record was added<br>";
    }
  }
  if ($_POST['householdid']) {
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
      $_POST['romaji'])." ".$_POST['postalcode']))."','".$_POST['phone']."','".$_POST['fax']."','".h2d($_POST['labelname'])."',CURDATE())";
    } else {
      $sql = "INSERT INTO household (NonJapan,PostalCode,Address,AddressComp,Phone,FAX,LabelName,UpdDate) ".
      "VALUES (".$_POST['nonjapan'].",'".$_POST['postalcode']."','".h2d($_POST['address'])."',".
      "'".$_POST['postalcode'].$_POST['prefecture'].$_POST['shikucho'].h2d($_POST['address'])."',".
      "'".$_POST['phone']."','".$_POST['fax']."','".h2d($_POST['labelname'])."',CURDATE())";
    }
//if ($_SESSION['userid']=="karen") die("<pre>".$sql."\n\n".$_POST['postalcode']."\n\n".$_POST['prefecture']."\n\n".$_POST['shikucho']."\n\n".h2d($_POST['address'])."</pre>");
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

if ($updateper == "1") {
  if ($organization) {
    $organization = "1";
  } else {
    $organization = "0";
  }
  if (!isset($householdid) || $householdid=='') $householdid=0;
  if (!isset($birthdate) || $birthdate=='') $birthdate='0000-00-00';

  if ($pid) {
    $sql = "UPDATE person SET FullName='".h2d($fullname)."',Furigana='".h2d($furigana)."',Sex='$sex',HouseholdID=$householdid,".
    "Relation='$relation',Title='".h2d($title)."',CellPhone='".h2d($cellphone)."',Email='".h2d($email)."',Birthdate='$birthdate',".
    "Country='".h2d($country)."',URL='".h2d($URL)."',Organization=$organization,Remarks='".h2d($remarks)."',UpdDate=CURDATE() ".
    "WHERE PersonID=$pid LIMIT 1";
    $result = sqlquery_checked($sql);
    if (mysqli_affected_rows($db) > 0) echo "The person record was updated<br>\n";
  } else {
    $sql = "INSERT INTO person (FullName,Furigana,Sex,HouseholdID,Relation,Title,CellPhone,".
    "Email,Birthdate,Country,URL,Organization,Remarks,UpdDate) VALUES ('".h2d($fullname)."','".h2d($furigana)."','$sex',".
        "$householdid,'$relation','".h2d($title)."','".h2d($cellphone)."','".h2d($email)."','$birthdate',".
    "'$country','$URL',$organization,'".h2d($remarks)."',CURDATE())";
    $result = sqlquery_checked($sql);
    if (mysqli_affected_rows($db) > 0) {
      $pid = mysqli_insert_id($db);
      echo "The person record was inserted<br>\n";
    } else {
      echo "No person record was inserted for some reason.<br>\n";
    }
  }

  if (is_uploaded_file($_FILES['photofile']['tmp_name'])) {
    $photofile = "/var/www/".$_SESSION['client']."/photos/p".$pid.".jpg";
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
      $sql = "UPDATE person SET Photo=1 WHERE PersonID=$pid LIMIT 1";
      $result = sqlquery_checked($sql);
    } else {
      echo "File upload failed.  Here's some debugging info:\n";
      print_r($_FILES);
      exit;
    }
  }
}
echo "<script for=\"window\" event=\"onload\" type=\"text/javascript\">\n";
echo "window.location = \"individual.php?pid=".$pid."\";\n";
echo "</script>\n";

print_footer();
?>