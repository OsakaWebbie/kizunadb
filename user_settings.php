<?php
include("functions.php");
include("accesscontrol.php");

$result = sqlquery_checked("SELECT Language FROM user WHERE UserID='".$_SESSION['userid']."'");
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

<!-- DASHBOARD -->

<?php
$result = sqlquery_checked("SELECT Dashboard FROM user WHERE UserID='".$_SESSION['userid']."'");
$row = mysqli_fetch_object($result);
$dash_current = explode(',', $row->Dashboard);

// build array of all available modules
$dash_all = array();
$files = glob(CLIENT_PATH.'/dashboard/'.'*.php');
foreach ($files as $file) {
  $tokens = token_get_all(file_get_contents($file));
  foreach( $tokens as $token )  if (in_array($token[0], array(T_COMMENT)))  break; //get just first comment
  $dash_desc = '';
  $dash_finance = 0;
  $file = substr(basename($file),0,-4);
  //echo '<pre>'.print_r($token,TRUE).'</pre>';
  preg_match('#@description=(.*)\n#', $token[1], $tmp);
  //echo '<pre>desc? '.print_r($tmp,TRUE).'</pre>';
  if (!empty($tmp))  $dash_desc = $tmp[1];
  preg_match('#@finance=(.*)\n#', $token[1], $tmp);
  //echo '<pre>finance? '.print_r($tmp,TRUE).'</pre>';
  if (!empty($tmp[1]))  $dash_finance = 1;
  $dash_all[$file] = ['desc'=>$dash_desc, 'finance'=>$dash_finance];
}
//echo '<pre>'.print_r($dash_all,TRUE).'</pre>';
//echo '<pre>'.print_r($dash_current,TRUE).'</pre>';

// display current and optional modules
?>
<ul id="dash-selected" class="ui-sortable">
  <?php
  foreach
  ?>
</ul>
<ul id="dash-unselected" class="ui-sortable">
  <?php
  foreach
  ?>
</ul>

?>

<script type="text/JavaScript" src="js/jquery.js"></script>
<script type="text/JavaScript" src="js/jquery-ui.js"></script>
<script type="text/JavaScript" src="js/functions.js"></script>

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
