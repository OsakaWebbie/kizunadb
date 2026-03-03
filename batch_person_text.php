<?php
include("functions.php");
include("accesscontrol.php");
$ajax = !empty($_REQUEST['ajax']);

if (!$ajax) {
  header1(_("Person Info (Text)"));
?>
<link rel="stylesheet" href="style.php" type="text/css" />
<?php
  header2(0);
}

// Fetch category list once for the inline category selectors
$cat_options_a = "<option value=''>"._("Select a Category...")."</option>\n";
$cat_options_b = "<option value=''> </option>\n";
$catresult = sqlquery_checked("SELECT CategoryID, Category FROM category ORDER BY Category");
while ($catrow = mysqli_fetch_object($catresult)) {
  $opt = "<option value=\"".$catrow->CategoryID."\">".$catrow->Category."</option>\n";
  $cat_options_a .= $opt;
  $cat_options_b .= $opt;
}
?>
<form action="person_text.php" method="post" name="optionsform" target="_blank" onsubmit="return prepareCats();">
  <input type="hidden" name="pid_list" value="<?=$pid_list?>">
  <div style="display:flex; flex-wrap:wrap; gap:1.5em; align-items:flex-start;">
    <div>
      <div style="display:flex; gap:1em; padding-left:1.8em; margin-bottom:3px;">
        <b style="min-width:15em"><?=_("Field to Show:")?></b>
        <b><?=_("Where to Place (HTML only):")?></b>
      </div>
<?php
for ($i=1; $i<7; $i++) {
  echo "      <div style='margin-bottom:2px;'>\n";
  echo "        <div style='white-space:nowrap;'>\n";
  echo "          $i: <select name='field$i' size='1' style='min-width:15em' onchange='toggleCatSpec($i);'>\n";
  echo "          <option value=''> </option>\n";
  echo "          <option value='FullName'>"._("Name")."</option>\n";
  echo "          <option value='readable'>".sprintf(_("Name (%s, if Japanese)"),$_SESSION['furiganaisromaji']=="yes"?_("Romaji"):_("Furigana"))."</option>\n";
  echo "          <option value='furigana'>".sprintf(_("%s (Name, if Japanese)"),$_SESSION['furiganaisromaji']=="yes"?_("Romaji"):_("Furigana"))."</option>\n";
  echo "          <option value='address'>"._("Address")."</option>\n";
  if ($_SESSION['romajiaddresses'] == "yes") {
    echo "          <option value='romajiaddress'>"._("Romaji Address")."</option>\n";
  }
  echo "          <option value='postalcode'>"._("Postal Code Only")."</option>\n";
  echo "          <option value='phones'>"._("Landline Phone and/or Cell Phone")."</option>\n";
  echo "          <option value='Phone'>"._("Landline Phone")."</option>\n";
  echo "          <option value='CellPhone'>"._("Cell Phone")."</option>\n";
  echo "          <option value='fax'>FAX</option>\n";
  echo "          <option value='Sex'>"._("Sex")."</option>\n";
  echo "          <option value='Email'>"._("Email Address")."</option>\n";
  echo "          <option value='birthday'>"._("Birthday")."</option>\n";
  echo "          <option value='Birthdate'>"._("Birthdate")."</option>\n";
  echo "          <option value='age'>"._("Age")."</option>\n";
  echo "          <option value='birthdate-age'>"._("Birthdate (Age)")."</option>\n";
  echo "          <option value='URL'>URL</option>\n";
  echo "          <option value='Country'>"._("Home Country")."</option>\n";
  echo "          <option value='photo'>"._("Photo")."</option>\n";
  echo "          <option value='Remarks'>"._("Remarks")."</option>\n";
  echo "          <option value='category'>"._("Mark if in Category")."</option>\n";
  echo "          <option value=' '>"._("Blank Line")."</option>\n";
  echo "          </select>\n";
  echo "          <select name='layout$i' size='1'>\n";
  echo "          <option value='<br>'>"._("On a new line")."</option>\n";
  echo "          <option value=' ('>"._("Same line, in ( )")."</option>\n";
  echo "          <option value='###'>"._("Same line, after ###")."</option>\n";
  echo "          <option value='<br>('>"._("New line, in ( )")."</option>\n";
  echo "          <option value='<br>&nbsp;&nbsp;&nbsp;'>"._("New line, indented")."</option>\n";
  echo "          </select>\n";
  echo "        </div>\n";
  echo "        <div id='catspec-$i' style='display:none; margin:3px 0 4px 1.8em;'>\n";
  echo "          <div class='label-n-input'><select name='selectA$i' size='1'>$cat_options_a</select>\n";
  echo "          <label><input type='radio' name='choiceA$i' value='in' checked>"._("In")."</label>\n";
  echo "          <label><input type='radio' name='choiceA$i' value='out'>"._("Not In")."</label></div>\n";
  echo "          <div class='label-n-input'><label><input type='radio' name='logical$i' value=' OR ' checked>OR</label>\n";
  echo "          <label><input type='radio' name='logical$i' value=' AND '>AND</label></div>\n";
  echo "          <div class='label-n-input'><select name='selectB$i' size='1'>$cat_options_b</select>\n";
  echo "          <label><input type='radio' name='choiceB$i' value='in' checked>"._("In")."</label>\n";
  echo "          <label><input type='radio' name='choiceB$i' value='out'>"._("Not In")."</label></div>\n";
  echo "          <label class='label-n-input'>"._("Mark with").": <input type='text' name='mark$i' value='*' size='3'></label>\n";
  echo "          &nbsp;<label class='label-n-input'>"._("XML tag").": <input type='text' name='tag$i' value='cat$i' size='8'></label>\n";
  echo "          <input type='hidden' name='cat$i' value=''>\n";
  echo "        </div>\n";
  echo "      </div>\n";
}
?>
    </div>
    <div>
      <p><b><?=_("Output Format:")?></b></p>
      <label class="label-n-input"><input type="radio" name="format" value="html" checked><?=_("HTML (regular)")?></label>
      <label class="label-n-input"><input type="radio" name="format" value="tab"><?=_("Tab delimited")?></label>
      <div class="comment"><?=_("(Tab: select View Source in browser before copying)")?></div>
      <label class="label-n-input"><input type="checkbox" name="include_empties"><?=_("Include Fields Even if Blank")?></label><br>
      <input type="submit" name="submit" value="<?=_("Make Page to Copy or Print")?>">
    </div>
  </div>
</form>
<?php if (!$ajax) load_scripts(['jquery', 'jqueryui']); ?>
<script>
$(function(){ $("input[type=submit]").button(); });

var catFieldAlert = <?=json_encode(_("Please select a category for field %s."))?>;

function toggleCatSpec(i) {
  if ($("[name='field"+i+"']").val() === "category") {
    $("#catspec-"+i).show();
  } else {
    $("#catspec-"+i).hide();
  }
}

function prepareCats() {
  for (var i = 1; i <= 6; i++) {
    if ($("[name='field"+i+"']").val() === "category") {
      var selectA = $("[name='selectA"+i+"']").val();
      if (!selectA) {
        alert(catFieldAlert.replace('%s', i));
        return false;
      }
      var choiceA = $("[name='choiceA"+i+"']:checked").val();
      var val = choiceA + "=" + selectA;
      var selectB = $("[name='selectB"+i+"']").val();
      if (selectB) {
        var logical = $("[name='logical"+i+"']:checked").val();
        var choiceB = $("[name='choiceB"+i+"']:checked").val();
        val += logical + choiceB + "=" + selectB;
      }
      $("[name='cat"+i+"']").val(val);
    }
  }
  return true;
}
</script>
<?php if (!$ajax) footer(); ?>