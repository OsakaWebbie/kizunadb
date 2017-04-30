<?php
include("functions.php");
include("accesscontrol.php");

$result = sqlquery_checked("SELECT Language FROM login WHERE UserID='".$_SESSION['userid']."'");
$row = mysqli_fetch_object($result);
$default_lang = $row->Language;
header1(_("User Settings"));
?>
<link rel="stylesheet" href="style.php?jquery=1" type="text/css" />
<?php header2(1); ?>
<h1 id="title"><?=_("User Settings")?></h1>

<!-- USER LANGUAGE -->

<form action="do_maint.php?page=user_settings" method="post" name="myuserform" id="myuserform" onsubmit="return validate('user');">
  <fieldset><legend><?=_("My User Settings")?></legend>
  <label class="label-n-input"><?=_("Default Language")?>: <select id="mylanguage" name="language" size="1">
    <option value="en_US"<?php if($default_lang=="en_US") echo " selected"; ?>><?=_("English")?></option>
    <option value="ja_JP"<?php if($default_lang=="ja_JP") echo " selected"; ?>><?=_("Japanese")?></option>
  </select></label>
  <input type="submit" name="user_upd" value="<?=_("Save Changes")?>">
</fieldset></form>

<!-- PASSWORD -->

<form action="do_maint.php?page=user_settings" method="post" name="pwform" autocomplete="off" onsubmit="return validate('pwd');">
  <fieldset><legend><?=_("Change My Password")?></legend>
  <label class="label-n-input"><?=_("Old")?>: <input type="password" id="old_pw" name="old_pw" style="width:8em"></label>
  <label class="label-n-input"><?=_("New")?>: <input type="password" id="new_pw1" name="new_pw1" style="width:8em"></label>
  <label class="label-n-input"><?=_("New again")?>: <input type="password" id="new_pw2" name="new_pw2" style="width:8em"></label>
  <input type="submit" id="pw_upd" name="pw_upd" value="<?=_("Change Password")?>">
</fieldset></form>

<script type="text/JavaScript" src="js_procedural/jquery.js"></script>
<script type="text/JavaScript" src="js_procedural/jquery-ui.js"></script>
<script type="text/JavaScript" src="js_procedural/functions.js"></script>

<script type="text/javascript">
function validate(form) {
  switch(form) {
  case "pwd":
    if (document.pwform.old_pw.value == "") {
      alert("<?=_("You must enter your current password for validation.")?>");
      return false;
    }
    if (document.pwform.new_pw1.value != document.pwform.new_pw2.value) {
      alert("<?=_("The two new password entries do not match.")?>");
      return false;
    }
    break;
  }
}
</script>

<?php
footer();
?>
