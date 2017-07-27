<?php
include("functions.php");
include("accesscontrol.php");
header1("");
?>
<meta http-equiv="expires" content="0">
<link rel="stylesheet" href="style.php" type="text/css" />
<script type="text/JavaScript" src="js/jquery.js"></script>
<script type="text/javascript">
$(document).ready(function(){
  $("select[id^=field]").change(function() {
    var number = $(this).attr("id").substr(5,2);
    $("[id$=span"+number+"]").hide();
    switch ($(this).val()) {
    case "Category":
      $("#catspan"+number).show();
      break;
    case "Action":
      $("#actionspan"+number).show();
      break;
    case "Attendance":
      $("#attendspan"+number).show();
      break;
    case "Members":
      $("#memberspan"+number).show();
      break;
    case "Orgs":
      $("#orgspan"+number).show();
      break;
    }
  });

  $("[id^=catcombine]").change(function() {
    var number = $(this).attr("id").substr(10,2);
    if ($("[id^=catcombine]").is(':checked')) {
      $("#catcombineoptions"+number).show();
    } else {
      $("#catcombineoptions"+number).hide();
    }
  });

  $("[id^=catstyle]").change(function() {
    var number = $(this).attr("id").substr(8,2);
    if ($("#catstyle"+number+"-custom").is(':checked') && $("#cattext"+number).val()=="") {
      $("#cattext"+number).val("*");
    }
  });

  $("[id^=ctcombine]").change(function() {
    var number = $(this).attr("id").substr(9,2);
    if ($("[id^=ctcombine]").is(':checked')) {
      if ($("#actionstyle"+number+"-all").is(':checked')) $("#actionstyle"+number+"-type").click();
      $("#actionstyle"+number+"-all").parent().hide();
    } else {
      $("#actionstyle"+number+"-all").parent().show();
    }
  });

  $("[id^=actionstyle]").change(function() {
    var number = $(this).attr("id").substr(12,2);
    if ($("#actionstyle"+number+"-custom").is(':checked') && $("#actiontext"+number).val()=="") {
      $("#actiontext"+number).val("*");
    }
  });

  $("#fielddup").click(function(){  //add a new field
    var newfield = $("#set00").clone(true);
    var oldnumber = $(this).prev().attr("id").substr($(this).prev().attr("id").length-2,2);
    var newnumber = ((parseInt(oldnumber,10))<9 ? "0" : "") + ((parseInt(oldnumber,10))+1);
    newfield.attr("id",newfield.attr("id").replace("00", newnumber));  //the top level div
    newfield.find("label[for*=00]").each(function() {
      $(this).attr("for", $(this).attr("for").replace("00", newnumber));
    });
    newfield.find("[id*=00]").each(function() {
      $(this).attr("id", $(this).attr("id").replace("00", newnumber));
    });
    newfield.find("[name*=00]").each(function() {
      $(this).attr("name", $(this).attr("name").replace("00", newnumber));
    });
    $(this).before(newfield);
    $("#set"+newnumber).show();
  });
});

function validate() {
  if (!$("input[name*=tag").val().match('/^(?!XML)[a-z][\w0-9-]*/i')) {
    alert("<?=_("Invalid XML tag name (no spaces or Japanese characters allowed).")?>");
    $('#postalcode').focus();
    return false;
  }
  return true;  //everything is cool*/
}
</script>
<?php header2(0); ?>

  <form action="person_xml.php" method="post" name="optionsform" target="_blank" onsubmit="return validate();">
    <input type="hidden" name="pid_list" value="<?=$pid_list?>">
<?php
for ($i=0; $i<4; $i++) {
  $s = sprintf("%02d",$i);
?>
    <fieldset id="set<?=$s?>"<?=($i==0?" style=\"display:none\"":"")?>>

<!-- Field selector -->
      <span class="label-n-input"><label for="field<?=$s?>"><?=_("Data")?>: </label>
      <select name="field<?=$s?>" id="field<?=$s?>" size="1">
        <option value="" selected> </option>
        <option value="FullName"><?=_("Name")?></option>
        <option value="Furigana"><?=($_SESSION['furiganaisromaji']=="yes"?_("Romaji"):_("Furigana"))?></option>
        <option value="Name-Furigana"><?=sprintf("Name (%s if Japanese)",($_SESSION['furiganaisromaji']=="yes"?_("Romaji"):_("Furigana")))?></option>
        <option value="Furigana-Name"><?=sprintf("%s (Name if Japanese)",($_SESSION['furiganaisromaji']=="yes"?_("Romaji"):_("Furigana")))?></option>
        <option value="PersonID"><?=_("ID")?></option>
        <option value="Address"><?=_("Address")?></option>
<?php if ($_SESSION['romajiaddresses'] == "yes"): ?>
        <option value="RomajiAddress"><?=_("Romaji Address")?></option>
<?php endif; ?>
        <option value="PostalCode"><?=_("Postal Code Only")?></option>
        <option value="LabelName"><?=_("Label Name")?></option>
        <option value="Phone-Cell"><?=_("Landline and/or Cell Phone")?></option>
        <option value="Phone"><?=_("Landline Phone")?></option>
        <option value="CellPhone"><?=_("Cell Phone")?></option>
        <option value="FAX"><?=_("FAX")?></option>
        <option value="Email"><?=_("Email Address")?></option>
        <option value="Sex"><?=_("Sex")?></option>
        <option value="Birthdate"><?=_("Birthdate")?></option>
        <option value="Birthday"><?=_("Birthday")?></option>
        <option value="Age"><?=_("Age")?></option>
        <option value="URL"><?=_("URL")?></option>
        <option value="Country"><?=_("Home Country")?></option>
        <option value="Remarks"><?=_("Remarks")?></option>
        <option value="Category"><?=_("Categories")?></option>
        <option value="Action"><?=_("Actions")?></option>
        <option value="Attendance"><?=_("Attendance")?></option>
        <option value="Members"><?=_("Members (organizations only)")?></option>
        <option value="Orgs"><?=_("Organizations (people only)")?></option>
      </select></span>

<!-- Categories -->
      <span id="catspan<?=$s?>" style="display:none">
        <span class="label-n-input"><label for="cat<?=$s?>"><?=_("Categories")?>: </label>
        <select name="cat<?=$s?>[]" id="cat<?=$s?>" size="3" multiple="multiple">
<?php
  $result = sqlquery_checked("SELECT * FROM category ORDER BY Category");
  while ($row = mysqli_fetch_object($result)) {
    echo "          <option value=\"".$row->CategoryID."\">".d2h($row->Category)."</option>\n";
  }
?>
        </select></span>
        <span class="label-n-input"><label for="cattag<?=$s?>"><?=_("XML Tag Name")?>: </label>
        <input type="text" name="cattag<?=$s?>" id="cattag<?=$s?>" value="Category" style="width:5em;ime-mode:disabled;" /></span>
        <span class="label-n-input"><input type="checkbox" name="catcombine<?=$s?>" id="catcombine<?=$s?>" value="YES">
        <label for="catcombine<?=$s?>"><?=_("Combine in one element"); ?></label></span>
        <span id="catcombineoptions<?=$s?>" class="radiogroup" style="display:none">
          <span class="label-n-input"><input name="catstyle<?=$s?>" id="catstyle<?=$s?>-cat" value="cat" type="radio" checked>
          <label for="catstyle<?=$s?>-type"><?=_("Category names")?></label></span>
          <span class="label-n-input"><input name="catstyle<?=$s?>" id="catstyle<?=$s?>-custom" value="custom" type="radio">
          <label for="catstyle<?=$s?>-custom"><?=("Custom value text"); ?>: </label>
          <input type="text" name="cattext<?=$s?>" id="cattext<?=$s?>" style="width:3em;ime-mode:auto;" /></span>
        </span>
      </span>

<!-- Actions -->
      <span id="actionspan<?=$s?>" style="display:none">
        <span class="label-n-input"><label for="action<?=$s?>"><?=_("Action Types")?>: </label>
        <select name="action<?=$s?>[]" id="action<?=$s?>" size="3" multiple="multiple">
<?php
  $result = sqlquery_checked("SELECT * FROM actiontype ORDER BY ActionType");
  while ($row = mysqli_fetch_object($result)) {
    echo "          <option value=\"".$row->ActionTypeID."\">".d2h($row->ActionType)."</option>\n";
  }
?>
        </select></span>
        <span class="label-n-input"><label for="actiontag<?=$s?>"><?=_("XML Tag Name")?>: </label>
        <input type="text" name="actiontag<?=$s?>" id="actiontag<?=$s?>" value="Action" style="width:5em;ime-mode:disabled;" /></span>
        <span class="label-n-input"><input type="checkbox" name="ctcombine<?=$s?>" id="ctcombine<?=$s?>" value="YES">
        <label for="ctcombine<?=$s?>"><?=_("Combine in one element"); ?></label></span>
        <span class="radiogroup">
          <span class="label-n-input"><input name="actionstyle<?=$s?>" id="actionstyle<?=$s?>-all" value="all" type="radio" checked>
          <label for="actionstyle<?=$s?>-all"><?=_("All data in XML sub-elements")?></label></span>
          <span class="label-n-input"><input name="actionstyle<?=$s?>" id="actionstyle<?=$s?>-type" value="type" type="radio">
          <label for="actionstyle<?=$s?>-type"><?=_("Action type names only")?></label></span>
          <span class="label-n-input"><input name="actionstyle<?=$s?>" id="actionstyle<?=$s?>-desc" value="desc" type="radio">
          <label for="actionstyle<?=$s?>-desc"><?=_("Descriptions only")?></label></span>
          <span class="label-n-input"><input name="actionstyle<?=$s?>" id="actionstyle<?=$s?>-custom" value="custom" type="radio">
          <label for="actionstyle<?=$s?>-custom"><?=_("Custom value text")?>: </label>
          <input type="text" name="actiontext<?=$s?>" id="actiontext<?=$s?>" style="width:3em;ime-mode:auto;" /></span>
        </span>
      </span>

<!-- Attendance -->
      <span id="attendspan<?=$s?>" style="display:none">
        <span class="label-n-input"><label for="attend<?=$s?>"><?=_("Events")?>: </label>
        <select name="attend<?=$s?>[]" id="attend<?=$s?>" size="3" multiple="multiple">
<?php
  $result = sqlquery_checked("SELECT * FROM event ORDER BY Event");
  while ($row = mysqli_fetch_object($result)) {
    echo "          <option value=\"".$row->EventID."\">".d2h($row->Event)."</option>\n";
  }
?>
        </select></span>
        <span class="label-n-input"><label for="attendtag<?=$s?>"><?=_("XML Tag Name")?>: </label>
        <input type="text" name="attendtag<?=$s?>" id="attendtag<?=$s?>" value="Attendance" style="width:5em;ime-mode:disabled;" /></span>
        <span class="label-n-input"><label for="attendtext<?=$s?>"><?=_("Fill Text")?>: </label>
        <input type="text" name="attendtext<?=$s?>" id="attendtext<?=$s?>" style="width:3em;ime-mode:auto;" /></span>
      </span>

<!-- Members (of an organization) -->
      <span id="memberspan<?=$s?>" style="display:none">
        <span class="label-n-input"><label for="memberfield<?=$s?>"><?=_("Member Fields")?>: </label>
        <select name="memberfield<?=$s?>[]" id="memberfield<?=$s?>" size="3" multiple="multiple">
          <option value="FullName"><?=_("Name")?></option>
          <option value="Furigana"><?=($_SESSION['furiganaisromaji']=="yes"?_("Romaji"):_("Furigana"))?></option>
          <option value="Name-Furigana"><?=sprintf("Name (%s if Japanese)",($_SESSION['furiganaisromaji']=="yes"?_("Romaji"):_("Furigana")))?></option>
          <option value="Furigana-Name"><?=sprintf("%s (Name if Japanese)",($_SESSION['furiganaisromaji']=="yes"?_("Romaji"):_("Furigana")))?></option>
          <option value="PersonID"><?=_("ID")?></option>
          <option value="Address"><?=_("Address")?></option>
<?php if ($_SESSION['romajiaddresses'] == "yes"): ?>
          <option value="RomajiAddress"><?=_("Romaji Address")?></option>
<?php endif; ?>
          <option value="PostalCode"><?=_("Postal Code Only")?></option>
          <option value="LabelName"><?=_("Label Name")?></option>
          <option value="Phone-Cell"><?=_("Landline and/or Cell Phone")?></option>
          <option value="Phone"><?=_("Landline Phone")?></option>
          <option value="CellPhone"><?=_("Cell Phone")?></option>
          <option value="FAX"><?=_("FAX")?></option>
          <option value="Email"><?=_("Email Address")?></option>
          <option value="Sex"><?=_("Sex")?></option>
          <option value="Birthdate"><?=_("Birthdate")?></option>
          <option value="Birthday"><?=_("Birthday")?></option>
          <option value="Age"><?=_("Age")?></option>
          <option value="URL"><?=_("URL")?></option>
          <option value="Country"><?=_("Home Country")?></option>
          <option value="Remarks"><?=_("Remarks")?></option>
        </select></span>
      </span>

<!-- Organizations (of a person) -->
      <span id="orgspan<?=$s?>" style="display:none">
        <span class="label-n-input"><label for="orgfield<?=$s?>"><?=_("Org Fields")?>: </label>
        <select name="orgfield<?=$s?>[]" id="orgfield<?=$s?>" size="3" multiple="multiple">
          <option value="FullName"><?=_("Name")?></option>
          <option value="Furigana"><?=($_SESSION['furiganaisromaji']=="yes"?_("Romaji"):_("Furigana"))?></option>
          <option value="Name-Furigana"><?=sprintf("Name (%s if Japanese)",($_SESSION['furiganaisromaji']=="yes"?_("Romaji"):_("Furigana")))?></option>
          <option value="Furigana-Name"><?=sprintf("%s (Name if Japanese)",($_SESSION['furiganaisromaji']=="yes"?_("Romaji"):_("Furigana")))?></option>
          <option value="PersonID"><?=_("ID")?></option>
          <option value="Address"><?=_("Address")?></option>
<?php if ($_SESSION['romajiaddresses'] == "yes"): ?>
          <option value="RomajiAddress"><?=_("Romaji Address")?></option>
<?php endif; ?>
          <option value="PostalCode"><?=_("Postal Code Only")?></option>
          <option value="LabelName"><?=_("Label Name")?></option>
          <option value="Phone-Cell"><?=_("Landline and/or Cell Phone")?></option>
          <option value="Phone"><?=_("Landline Phone")?></option>
          <option value="CellPhone"><?=_("Cell Phone")?></option>
          <option value="FAX"><?=_("FAX")?></option>
          <option value="Email"><?=_("Email Address")?></option>
          <option value="Country"><?=_("Home Country")?></option>
          <option value="Remarks"><?=_("Remarks")?></option>
        </select></span>
      </span>
    </fieldset>
<?php
} //end of for loop
?>
    <button type="button" id="fielddup"><?=_("Add another...")?></button>
    <div id="general">
      <span class="label-n-input"><label for="orderby"><?=_("Order By")?>: </label>
      <select name="orderby" id="orderby" size="1">
        <option value="Furigana" selected><?=sprintf(_("%s (family name)"),($_SESSION['furiganaisromaji']=="yes"?_("Romaji"):_("Furigana")))?></option>
        <option value="PersonID"><?=_("ID")?></option>
        <option value="FullName"><?=_("Name")?></option>
        <option value="PostalCode,Furigana"><?=_("Postal Code")?></option>
        <option value="Email"><?=_("Email Address")?></option>
        <option value="Sex,Furigana"><?=_("Sex")?></option>
        <option value="Birthdate"><?=_("Birthdate")?></option>
        <option value="URL,Furigana"><?=_("URL")?></option>
        <option value="Country,Furigana"><?=_("Home Country")?></option>
      </select></span>
      <button type="submit" name="submit"><?=_("Make XML File")?></button>
    </div>
  </form>
<?php
footer();
?>