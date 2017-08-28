<?php
include("functions.php");
include("accesscontrol.php");
header1("Maintenance Processing");
?>
<link rel="stylesheet" href="style.php" type="text/css" />
<?php
header2(1);

if (isset($_POST['confirmed'])) $confirmed = $_POST['confirmed'];

// ********** POSTAL CODE  **********
if ($_POST['pc_upd']) {
  $sql = "UPDATE postalcode set Prefecture='".$_POST['prefecture']."', ShiKuCho='".$_POST['shikucho']."'";
  if ($_SESSION['romajiaddresses'])  $sql .= ", Romaji='".h2d($_POST['romaji'])."'";
  $sql .= " WHERE PostalCode='".$_POST['postalcode']."'";
  sqlquery_checked($sql);
  if (mysqli_affected_rows($db) == 1) {
    $message = _("Postal Code data successfully updated.");
  } else {
    $message = _("No data was changed.");
  }
  
// ********** CATEGORY **********
} elseif ($_POST['cat_add_upd']) {
  if ($_POST['catid']=="new") {
    sqlquery_checked("INSERT INTO category (Category,UseFor) VALUES ('".h2d($_POST['category'])."','".$_POST['usefor']."')");
    if (mysqli_affected_rows($db) == 1) $message = _("Category successfully added.");
  } else {
    sqlquery_checked("UPDATE category SET Category='".h2d($_POST['category'])."',UseFor='".$_POST['usefor']."' WHERE CategoryID=".$_POST['catid']);
    if (mysqli_affected_rows($db) == 1) $message = _("Category successfully updated.");
  }
  
} elseif ($_POST['cat_del']) {

  // if first time around, check for percat records - if none, don't need confirmation
  if (!isset($confirmed)) {
    $result = sqlquery_checked("SELECT percat.PersonID,FullName,Furigana FROM percat LEFT JOIN person ON".
    " percat.PersonID=person.PersonID WHERE CategoryID=".$_POST['catid']." ORDER BY Furigana");
    if (mysqli_num_rows($result) == 0) {
      $confirmed = 1;
    }
  }
  if (isset($confirmed)) {
    sqlquery_checked("DELETE FROM percat WHERE CategoryID=".$_POST['catid']);
    if (mysqli_affected_rows($db) > 0) {
      $message = sprintf(_("%s persons removed from category.")."\\n",mysqli_affected_rows($db));
    }
    sqlquery_checked("DELETE FROM category WHERE CategoryID=".$_POST['catid']." LIMIT 1");
    if (mysqli_affected_rows($db) == 1) {
      $message .= _("Category successfully deleted.");
    }
  } else {
  //already did query for the category's members - now tell the user and ask for confirmation
?>
<h3 class="alert"><?=_("Please Confirm Category Delete")?></font></h3>
<p><?php printf(_("The following %s entries are members of the %s category."),mysqli_num_rows($result),$_POST['category']); ?>
<?=_(" If you are sure you want to delete these category associations, click the button. (If not, just press your browser's Back button.)")?>
</p>
<form action="<?=$_SERVER['PHP_SELF']?>" method="post">
  <input type="hidden" name="catid" value="<?=$_POST['catid']?>">
  <input type="hidden" name="cat_del" value="<?=$_POST['cat_del']?>">
  <input type="hidden" name="confirmed" value="1">
  <input type="submit" value="<?=_("Yes, delete the category")?>">
</form>
<h3><?=_("Category Members")?>:</h3><ol id="catmembers">
<?php
    while ($row = mysqli_fetch_object($result)) {
      echo "<li><a href=\"individual.php?pid=".$row->PersonID."\" target=\"_blank\">".
      readable_name($row->FullName,$row->Furigana)."</a></li>\n";
    }
    echo "</ol>";
    $need_confirmation = 1;
  }
  
// ********** ACTION TYPE  **********
} elseif (isset($_POST['at_add_upd'])) {
  echo "<pre>".print_r($_POST,true)."</pre>";

  if ($_POST['atypeid'] == "new") {
    sqlquery_checked("INSERT INTO actiontype (ActionType,BGColor,Template) VALUES ('".h2d($_POST['atype'])."','".$_POST['atcolor']."','".h2d($_POST['attemplate'])."')");
    if (mysqli_affected_rows($db) == 1) {
      $message = _("New action type successfully added.");
    }
  } else {
    sqlquery_checked("UPDATE actiontype SET ActionType='".h2d($_POST['atype'])."',BGColor='".$_POST['atcolor']."',Template='".h2d($_POST['attemplate'])."' WHERE ActionTypeID=".$_POST['atypeid']);
    if (mysqli_affected_rows($db) == 1) {
      $message = _("Action Type information successfully updated.");
    }
  }

} elseif (isset($_POST['at_del'])) {

  // if first time around, check for action records - if none, don't need confirmation
  if (!isset($confirmed)) {
    $result = sqlquery_checked("SELECT count(*) AS num FROM action WHERE ActionTypeID=".$_POST['atypeid']);
    $row = mysqli_fetch_object($result);
    if ($row->num == 0) {
      $confirmed = 1;
    } else {
      $at_num = $row->num;
    }
  }
  if (isset($confirmed)) {
    if ($_POST['new_atypeid']) {
      sqlquery_checked("UPDATE action SET ActionTypeID=".$_POST['new_atypeid']." WHERE ActionTypeID=".$_POST['atypeid']);
      if (mysqli_affected_rows($db) > 0) {
        $message = sprintf(_("%s related action records updated.")."\\n",mysqli_affected_rows($db));
      }
    }
    sqlquery_checked("DELETE FROM actiontype WHERE ActionTypeID=".$_POST['atypeid']." LIMIT 1");
    if (mysqli_affected_rows($db) == 1) {
      $message .= _("Action Type successfully deleted.");
    }
  } else {
  //ask for confirmation
    $result = sqlquery_checked("SELECT * FROM actiontype ORDER BY ActionType");
    while ($row = mysqli_fetch_object($result)) {
      if ($row->ActionTypeID != $_POST['atypeid']) {
        $options .= "\n      <option value=".$row->ActionTypeID.">".$row->ActionType."</option>";
      }
    }
?>
<h3 style="color:red"><?=_("Please Confirm Action Type Delete")?></h3>
<?php printf(_("There are %s action entries of this action type.  If you delete this action type, I must assign a different action type to those action entries."),$at_num);
echo _(" If you don't want to do this, just press your browser's Back button.  To reassign the action entries, choose from the list below:"); ?>
<form action="<?=$_SERVER['PHP_SELF']?>" method="post">
  <p><?=_("New Action Type")?>:
    <select size="1" name="new_atypeid">
      <option value=""><?=_("Select...")?></option><?=$options?>
    </select>
  <input type="hidden" name="atypeid" value="<?=$_POST['atypeid']?>">
  <input type="hidden" name="at_del" value="<?=$_POST['at_del']?>">
  <input type="hidden" name="confirmed" value="1">
  <input type="submit" value="<?=_("Yes, reassign the action entries")?>">
</form>
<?php
    $need_confirmation = 1;
  }
  
// ********** DONATION TYPE  **********
} elseif ($_POST['dt_add_upd']) {

  if ($dtypeid == "new") {
    sqlquery_checked("INSERT INTO donationtype (DonationType,BGColor) VALUES ('".h2d($_POST['dtype'])."','".$_POST['dtcolor']."')");
    if (mysqli_affected_rows($db) == 1) {
      $message = _("New donation type successfully added.");
    }
  } else {
    sqlquery_checked("UPDATE donationtype SET DonationType='".h2d($_POST['dtype'])."',BGColor='".$_POST['dtcolor']."' WHERE DonationTypeID=".$_POST['dtypeid']);
    if (mysqli_affected_rows($db) == 1) {
      $message = _("Donation Type information successfully updated.");
    }
  }

} elseif ($_POST['dt_del']) {

  // if first time around, check for donation records - if none, don't need confirmation
  if (!isset($confirmed)) {
    $result = sqlquery_checked("SELECT count(*) AS num FROM donation WHERE DonationTypeID=".$_POST['dtypeid']);
    $row = mysqli_fetch_object($result);
    if ($row->num == 0) {
      $confirmed = 1;
    } else {
      $dt_num = $row->num;
    }
  }
  if (isset($confirmed)) {
    if ($_POST['new_dtypeid']) {
      $prepend_clause = '';
      if (isset($_POST['prepend']) && $_POST['prepend_text']!='' && substr($_POST['prepend_text'], -1)!=' ')
          $prepend_clause = ", Description=CONCAT('".$_POST['prepend_text'].(substr($_POST['prepend_text'], -1)!=' '?' ':'')."',Description)";
      sqlquery_checked("UPDATE donation SET DonationTypeID=".$_POST['new_dtypeid'].$prepend_clause." WHERE DonationTypeID=".$_POST['dtypeid']);
      if (mysqli_affected_rows($db) > 0) {
        $message = sprintf(_("%s related donation records updated.")."\\n",mysqli_affected_rows($db));
      }
    }
    sqlquery_checked("DELETE FROM donationtype WHERE DonationTypeID=".$_POST['dtypeid']." LIMIT 1");
    if (mysqli_affected_rows($db) == 1) {
      $message .= _("Donation Type successfully deleted.");
    }
  } else {
  //ask for confirmation
    $result = sqlquery_checked("SELECT * FROM donationtype ORDER BY DonationType");
    while ($row = mysqli_fetch_object($result)) {
      if ($row->DonationTypeID == $_POST['dtypeid']) {
        $current_dtype = $row->DonationType;
      } else {
        $options .= "\n      <option value=".$row->DonationTypeID.">".$row->DonationType."</option>";
      }
    }
?>
<h3 style="color:red"><?=_("Please Confirm Donation Type Delete")?></h3>
<? printf(_("There are %s donation entries of donation type '%s' (%sclick for list%s). ".
    "If you delete this donation type, I must assign a different donation type to these donation entries."),
    $dt_num, $current_dtype, '<a href="donation_list.php?nav=1&show_list='.
    urlencode(_("Donation List")).'&listtype=Normal&dtype[]='.$_POST['dtypeid'].'" target="_blank">', '</a>');
echo _(" If you don't want to do this, just press your browser's Back button. ".
    "To reassign the donation entries, choose from the list below:");
?>
<form action="<?=$_SERVER['PHP_SELF']?>" method="post">
  <div style="margin:10px">
    <label><?=_("New Donation Type")?>:
    <select size="1" name="new_dtypeid">
      <option value=""><?=_("Select...")?></option><?=$options?>
    </select></label>
  </div>
  <div style="margin:10px">
    <label><input type="checkbox" name="prepend" checked="checked"><?=_("Prepend this text to donation descriptions:")?></label>
    <input type="text" name="prepend_text" value="(<?=$current_dtype?>) " style="width:20em">
  </div>
  <input type="hidden" name="dtypeid" value="<?=$_POST['dtypeid']?>">
  <input type="hidden" name="dt_del" value="<?=$_POST['dt_del']?>">
  <input type="hidden" name="confirmed" value="1">
  <p><input type="submit" value="<?=_("Yes, reassign the donation entries")?>"></p>
</form>
<?php
    $need_confirmation = 1;
  }

// ********** EVENT  **********
} elseif ($_POST['event_add_upd']) {

  $active = ($_POST['active'] ? "1" : "0");
  $usetimes = ($_POST['usetimes'] ? "1" : "0");
  if ($_POST['eventid'] == "new") {
    sqlquery_checked("INSERT INTO event (Event,EventStartDate,EventEndDate,UseTimes,Remarks) ".
    "VALUES ('".h2d($_POST['event'])."','".$_POST['eventstartdate']."',".($_POST['eventenddate']?"'".$_POST['eventenddate']."'":"NULL").",$usetimes,'".h2d($_POST['remarks'])."')");
    if (mysqli_affected_rows($db) == 1) {
      $message = _("New event successfully added.");
    }
  } else {
    sqlquery_checked("UPDATE event SET UseTimes=$usetimes,Event='".h2d($_POST['event'])."',EventStartDate='".$_POST['eventstartdate']."',".
      "EventEndDate=".($_POST['eventenddate']?"'".$_POST['eventenddate']."'":"NULL").",Remarks='".h2d($_POST['remarks'])."' WHERE EventID=".$_POST['eventid']);
    if (mysqli_affected_rows($db) == 1) {
      $message = _("Event information successfully updated.");
    }
  }

} elseif ($_POST['event_del']) {

  // if first time around, check for attendance records - if none, don't need confirmation
  if (!$confirmed) {
    $result = sqlquery_checked("SELECT count(AttendDate) AS num, min(AttendDate) AS first, max(AttendDate) AS last ".
    "FROM attendance WHERE EventID=".$_POST['eventid']);
    $row = mysqli_fetch_object($result);
    if ($row->num == 0) {

      $confirmed = 1;
    } else {
      $attend_num = $row->num;
      $attend_first = $row->first;
      $attend_last = $row->last;
    }
  }
  if ($confirmed) {
    sqlquery_checked("DELETE FROM attendance WHERE EventID=".$_POST['eventid']);
    if (mysqli_affected_rows($db) > 0) {
      $message = sprintf(_("%s related attendance records deleted."),mysqli_affected_rows($db))."\\n";
    }
    sqlquery_checked("DELETE FROM event WHERE EventID=".$_POST['eventid']." LIMIT 1");
    if (mysqli_affected_rows($db) == 1) {
      $message = $message._("Event record successfully deleted.");
    }
  } else {
  //ask for confirmation
    echo "<h3 class=\"alert\">"._("Please Confirm Event Delete")."</font></h3>\n<p>";
    printf(_("There are %s attendance records for this event, during the time period %s
thru %s."),$attend_num, $attend_first, $attend_last);
    echo _(" In deleting the event, you will also delete all attendance data associated with it.  Are you sure you want to do this?  (If not, just press your browser's Back button.)"); ?>
<form action="<?=$_SERVER['PHP_SELF']?>" method="post">
  <input type="hidden" name="event_id" value="<?=$event_id?>">
  <input type="hidden" name="event_del" value="<?=$event_del?>">
  <input type="hidden" name="confirmed" value="1">
  <input type="submit" value="<?=_("Yes, delete the event and attendance records")?>">
</form>
<?php
    $need_confirmation = 1;
  }
  
// ********** MY USER  **********
} elseif ($_POST['user_upd']) {
  sqlquery_checked("UPDATE user set Language = '".$_POST['language']."' WHERE UserID = '".$_SESSION['userid']."'");
  if (mysqli_affected_rows($db) == 1) {
    $_SESSION['lang'] = $_POST['language'];
    setlocale(LC_ALL, $_SESSION['lang'].".utf8");
    $message = _("Language successfully changed.");
  }

// ********** MY PASSWORD  **********
} elseif ($_POST['pw_upd']) {
  $result = sqlquery_checked("SELECT * FROM user WHERE UserID = '".$_SESSION['userid']."'".
    " AND (Password=PASSWORD('".$_POST['old_pw']."') OR Password=OLD_PASSWORD('".$_POST['old_pw']."'))");
  if (mysqli_num_rows($result) == 0) {
    $message = _("Sorry, but your old password entry was incorrect, so the password was not changed.");
  } elseif ($new_pw1 != $new_pw2) {
    $message = _("Sorry, but the two entries for the new password did not match. Password not changed.");
  } else {
    sqlquery_checked("UPDATE user set Password = PASSWORD('$new_pw1') WHERE UserID = '".$_SESSION['userid']."'");
    if (mysqli_affected_rows($db) == 1) {
      $message = _("Password successfully changed.");
    }
  }

// ********** LOGIN  **********
} elseif ($_POST['user_add_upd']) {
  $adm = $_POST['admin'] ? "1" : "0";
  $hd = $_POST['hidedonations'] ? "1" : "0";
  if ($_POST['userid'] == "new") {
    $result = sqlquery_checked("SELECT UserName FROM user WHERE UserID='".$_POST['new_userid']."'");
    if (mysqli_num_rows($result) > 0) {
      $row = mysqli_fetch_object($result);
      $message = sprintf(_("UserID '%s' is already in use by %s. Please choose a different UserID."),
          $new_userid, $row->UserName);
    } else {
      sqlquery_checked("INSERT INTO user (UserID,UserName,Password,Admin,Language,HideDonations,DashboardCode) ".
      "VALUES ('".$_POST['new_userid']."','".h2d($_POST['username'])."',PASSWORD('".$_POST['new_pw1']."'),$adm,".
      "'".$_POST['language']."',$hd,'".h2d($_POST['dashboard'])."')");
      if (mysqli_affected_rows($db) == 1) {
        $message = _("New user successfully added.");
      }
    }
  } else { //update
    $result = sqlquery_checked("SELECT UserName FROM user WHERE UserID='".$_POST['new_userid']."'");
    if ($new_userid != $old_userid && mysqli_num_rows($result) > 0) {
      $row = mysqli_fetch_object($result);
      $message = sprintf(_("UserID '%s' is already in use by %s. Please choose a different UserID."),
          $_POST['new_userid'], $row->UserName);
    } else {
      $sql = "UPDATE user SET ";
      if ($_POST['new_userid'] != $_POST['old_userid']) {
        $sql .= "UserID='".$_POST['new_userid']."',";
      }
      $sql .= "UserName='".h2d($_POST['username'])."',";
      if ($_POST['new_pw1'] != "") {
        $sql .= "Password=PASSWORD('".$_POST['new_pw1']."'),";
      }
      $sql .= "Admin=$adm,Language='".$_POST['language']."',HideDonations=$hd,DashboardCode='".h2d($_POST['dashboard']).
	  "' WHERE UserID='".$_POST['old_userid']."'";
      $result = sqlquery_checked($sql);
      if (mysqli_affected_rows($db) == 1) {
        if ($_POST['old_userid'] == $_SESSION['userid']) {  //I'm editing me, so change the session stuff too
          $_SESSION['userid'] = $_POST['new_userid'];
          $_SESSION['username'] = $_POST['username'];
          $_SESSION['admin'] = $adm;
          $_SESSION['lang'] = $_POST['language'];
          $_SESSION['hasdashboard'] = $_POST['dashboard']!='' ? 1 : 0;
        }
        $message = _("User information successfully updated.");
      }
    }
  }
} elseif ($_POST['user_del']) {

  sqlquery_checked("DELETE FROM user WHERE UserID='{$_POST['old_userid']}'");
  if (mysqli_affected_rows($db) == 1) {
    $message = _("User successfully deleted.");
  }

// ********** CATCH ALL **********
} else {
  $message = "No match for type of update in do_maint.php.  Programming bug!";
}
if (!$need_confirmation) {
  echo "<SCRIPT FOR=window EVENT=onload LANGUAGE=\"JavaScript\">\n";
  if ($message) {
    echo "alert(\"".$message."\");\n";
  }
  echo "window.location = \"".($_GET['page']=='user_settings' ? 'user_settings' : 'db_settings').".php\";\n";
  echo "</SCRIPT>\n";
  
}
footer();
?>