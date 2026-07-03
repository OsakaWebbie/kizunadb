<?php
include("functions.php");
include("accesscontrol.php");

// ********** MY SETTINGS (LANGUAGE + EMAIL) **********
if (!empty($_POST['user_upd'])) {
  $language   = in_array($_POST['language'] ?? '', array('en_US', 'ja_JP')) ? $_POST['language'] : 'en_US';
  $userid_esc = mysqli_real_escape_string($db, $_SESSION['userid']);
  $email_esc  = mysqli_real_escape_string($db, trim($_POST['email'] ?? ''));
  sqlquery_checked("UPDATE user SET Language='$language', Email='$email_esc' WHERE UserID='$userid_esc'");
  $_SESSION['lang'] = $language;
  setlocale(LC_ALL, $_SESSION['lang'].".utf8");
  header('Location: '.$_SERVER['PHP_SELF'].'?msg='._('Settings successfully changed.'));
  exit;

// ********** MY PASSWORD **********
} elseif (!empty($_POST['pw_upd'])) {
  $userid_esc = mysqli_real_escape_string($db, $_SESSION['userid']);
  $old_pw_esc = mysqli_real_escape_string($db, $_POST['old_pw'] ?? '');
  $result = sqlquery_checked("SELECT UserID FROM user WHERE UserID='$userid_esc' AND Password=PASSWORD('$old_pw_esc')");
  if (mysqli_num_rows($result) == 0) {
    header('Location: '.$_SERVER['PHP_SELF'].'?err='._("Sorry, but your old password entry was incorrect, so the password was not changed."));
    exit;
  } elseif (($_POST['new_pw1'] ?? '') != ($_POST['new_pw2'] ?? '')) {
    header('Location: '.$_SERVER['PHP_SELF'].'?err='._("The two new password entries do not match."));
    exit;
  } elseif (password_grade($_POST['new_pw1'] ?? '') < 1) {
    header('Location: '.$_SERVER['PHP_SELF'].'?err='._('Please choose a stronger password (at least "Fair").'));
    exit;
  } else {
    $new_pw1_esc = mysqli_real_escape_string($db, $_POST['new_pw1'] ?? '');
    sqlquery_checked("UPDATE user SET Password=PASSWORD('$new_pw1_esc') WHERE UserID='$userid_esc'");
    header('Location: '.$_SERVER['PHP_SELF'].'?msg='._("Password successfully changed."));
    exit;
  }
}

$result = sqlquery_checked("SELECT Language, Email FROM user WHERE UserID='".$_SESSION['userid']."'");
$row = mysqli_fetch_object($result);
$default_lang = $row->Language;
$default_email = $row->Email;
pageheader(_("User Settings"), 1); ?>
<h1 id="title"><?=_("User Settings")?></h1>
<?php if (!empty($_GET['err'])) echo '<p style="color:red">'.htmlspecialchars($_GET['err']).'</p>';
    elseif (!empty($_GET['msg'])) echo '<p>'.htmlspecialchars($_GET['msg']).'</p>';
?>

<!-- USER LANGUAGE -->

<form method="post" name="myuserform" id="myuserform">
  <fieldset><legend><?=_("My User Settings")?></legend>
  <label class="label-n-input"><?=_("Default Language")?>: <select id="mylanguage" name="language" size="1">
    <option value="en_US"<?php if($default_lang=="en_US") echo " selected"; ?>><?=_("English")?></option>
    <option value="ja_JP"<?php if($default_lang=="ja_JP") echo " selected"; ?>><?=_("Japanese")?></option>
  </select></label>
  <label class="label-n-input"><?=_('Email')?>: <input type="email" id="email" name="email" style="width:18em" maxlength="70" value="<?=htmlspecialchars($default_email, ENT_QUOTES)?>">
  <span class="comment"><?=_('(used for "forgot password" capability)')?></span></label>
  <input type="submit" name="user_upd" value="<?=_("Save Changes")?>">
</fieldset></form>

<!-- PASSWORD -->

<form method="post" name="pwform" autocomplete="off" onsubmit="return validate('pwd');">
  <fieldset><legend><?=_("Change My Password")?></legend>
  <label class="label-n-input"><?=_("Old")?>: <input type="password" id="old_pw" name="old_pw" style="width:8em"></label>
  <label class="label-n-input"><?=_("New")?>: <input type="password" id="new_pw1" name="new_pw1" style="width:8em" autocomplete="new-password"></label>
  <label class="label-n-input"><?=_("New again")?>: <input type="password" id="new_pw2" name="new_pw2" style="width:8em" autocomplete="new-password"></label>
  <?php include("passwordentry.php"); ?>
  <input type="submit" id="pw_upd" name="pw_upd" value="<?=_("Change Password")?>">
</fieldset></form>

<?php load_scripts(['jquery', 'jqueryui', 'functions']); ?>
<script type="text/javascript">
function validate(form) {
  switch(form) {
  case "pwd":
    if (document.pwform.old_pw.value == "") {
      alert("<?=_("You must enter your current password for validation.")?>");
      return false;
    }
    if (document.pwform.new_pw1.value == "" || document.pwform.new_pw2.value == "") {
      alert("<?=_("You must enter a new password in both fields.")?>");
      return false;
    }
    if (!pwEntryOK()) return false;
    break;
  }
}
</script>

<?php
footer();
?>