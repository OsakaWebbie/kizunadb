<?php
include("functions.php");
include("accesscontrol.php");
$ajax = !empty($_REQUEST['ajax']);

if (!$ajax) header1(_("Person Info (Text)"));
if (!$ajax) {
?>
<link rel="stylesheet" href="style.php" type="text/css" />
<?php
}
?>
<script>
function check_cats() {
  form = window.document.forms['optionsform'];
  fieldnumlist = "";
  need_popup = 0;
  for (index=1; index<7; index++) {
    if (form.elements["field"+index].value == "category") {
      fieldnumlist = fieldnumlist + index;
      if (form.elements["cat"+index].value == "") {
        need_popup = 1;
      }
    }
  }
  if (need_popup) {
    width = 500;
    height = 300;
    left = Math.floor( (screen.width - width) / 2);
    top = Math.floor( (screen.height - height) / 2);
    parameters = "SCROLLBARS=yes,TOP="+top+",LEFT="+left+",WIDTH="+width+",HEIGHT="+height;
    cat_window = window.open("mspersontext_cat.php?fields="+fieldnumlist, "", parameters);
    return false;
  } else {
    return true;
  }
}
</script>
<?php if (!$ajax) header2(0); ?>

<form action="person_text.php" method="post" name="optionsform" target="_blank" onsubmit="return check_cats();">
  <input type="hidden" name="pid_list" value="<?=$pid_list?>">
  <table width="639" border="0" cellspacing="0" cellpadding="5">
    <tr><td nowrap align="center" valign="top"><p><b><?=_("Field to Show:")?></b><br>
<?php
for ($i=1; $i<7; $i++) {
  echo "        $i: <select name=\"field$i\" size=\"1\">\n";
  echo "        <option value=\"\"> </option>\n";
  echo "        <option value=\"FullName\">"._("Name")."</option>\n";
  echo "        <option value=\"readable\">".sprintf(_("Name (%s, if Japanese)"),$_SESSION['furiganaisromaji']=="yes"?_("Romaji"):_("Furigana"))."</option>\n";
  echo "        <option value=\"furigana\">".sprintf(_("%s (Name, if Japanese)"),$_SESSION['furiganaisromaji']=="yes"?_("Romaji"):_("Furigana"))."</option>\n";
  echo "        <option value=\"address\">"._("Address")."</option>\n";
  if ($_SESSION['romajiaddresses'] == "yes") {
    echo "        <option value=\"romajiaddress\">"._("Romaji Address")."</option>\n";
  }
  echo "        <option value=\"postalcode\">"._("Postal Code Only")."</option>\n";
  echo "        <option value=\"phones\">"._("Landline Phone and/or Cell Phone")."</option>\n";
  echo "        <option value=\"Phone\">"._("Landline Phone")."</option>\n";
  echo "        <option value=\"CellPhone\">"._("Cell Phone")."</option>\n";
  echo "        <option value=\"fax\">FAX</option>\n";
  echo "        <option value=\"Sex\">"._("Sex")."</option>\n";
  echo "        <option value=\"Email\">"._("Email Address")."</option>\n";
  echo "        <option value=\"birthday\">"._("Birthday")."</option>\n";
  echo "        <option value=\"Birthdate\">"._("Birthdate")."</option>\n";
  echo "        <option value=\"age\">"._("Age")."</option>\n";
  echo "        <option value=\"birthdate-age\">"._("Birthdate (Age)")."</option>\n";
  echo "        <option value=\"URL\">URL</option>\n";
  echo "        <option value=\"Country\">"._("Home Country")."</option>\n";
  echo "        <option value=\"photo\">"._("Photo")."</option>\n";
  echo "        <option value=\"Remarks\">"._("Remarks")."</option>\n";
  echo "        <option value=\"category\">"._("Mark if in Category (specify later)")."</option>\n";
  echo "        <option value=\" \">"._("Blank Line")."</option>\n";
  echo "        </select>\n";
  echo "        <input type=hidden name=\"cat$i\" value=\"\">\n";
  echo "        <input type=hidden name=\"mark$i\" value=\"*\">\n";
  echo "        <input type=hidden name=\"tag$i\" value=\"cat$i\">\n";
  echo "        <br>\n";
}
?>
      </td><td nowrap align="center" valign="top"><p><b><?=_("Where to Place (HTML only):")?></b><br>
<?php
for ($i=1; $i<7; $i++) {
  echo "        <select name=\"layout$i\" size=\"1\">\n";
  echo "        <option value=\"<br>\">"._("On a new line")."</option>\n";
  echo "        <option value=\" (\">"._("Same line, in ( )")."</option>\n";
  echo "        <option value=\"###\">"._("Same line, after ###")."</option>\n";
  echo "        <option value=\"<br>(\">"._("New line, in ( )")."</option>\n";
  echo "        <option value=\"<br>&nbsp;&nbsp;&nbsp;\">"._("New line, indented")."</option>\n";
  echo "        </select><br>\n";
}
?>
          </td>
          <td valign="top">
        <p><b><?=_("Output Format:")?></b><br>
          <input type="radio" name="format" value="html" checked><?=_("HTML (regular)")?><br>
          <input type="radio" name="format" value="tab"><?=_("Tab delimited")?><br>
          <span style="font-size:0.8em;color:red"><?=_("(Tab: select View Source in browser before copying)")?></span><br>
        <input type="checkbox" name="include_empties"><?=_("Include Fields Even if Blank")?><br>
        <input type="submit" name="submit" value="<?=_("Make Page to Copy or Print")?>"></p>
          </td>
        </tr>
      </table>
    </form>
<?php if (!$ajax) footer(); ?>
