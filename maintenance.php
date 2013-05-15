<?php
include("functions.php");
include("accesscontrol.php");

header1(_("Database Maintenance"));
?>
<link rel="stylesheet" href="style.php?jquery=1" type="text/css" />
<script type="text/javascript" src="jscolor/jscolor.js"></script>
<script type="text/JavaScript" src="js/jquery.js"></script>
<script type="text/JavaScript" src="js/jquery-ui.js"></script>
<script type="text/JavaScript" src="js/jquery.populate.js"></script>
<script type="text/JavaScript" src="js/functions.js"></script>

<script type="text/javascript">

function stopRKey(evt) {
  var evt = (evt) ? evt : ((event) ? event : null);
  var node = (evt.target) ? evt.target : ((evt.srcElement) ? evt.srcElement : null);
  if ((evt.keyCode == 13) && (node.type=="text"))  {return false;}
}
document.onkeypress = stopRKey;

function showSpinner(el) {
  el.append('<span class="spinner"><img src="graphics/ajax-loader.gif" alt="Loading..." /></span>');
}

function hideSpinner(el) {
  el.find(".spinner").remove();
}

$(document).ready(function(){
  //$( "#cttemplate,#dashboardhead,#dashboardbody" ).resizable();
<? if($_SESSION['lang']=="ja_JP") echo "  $.datepicker.setDefaults( $.datepicker.regional[\"ja\"] );\n"; ?>
  $('#eventstartdate').datepicker({ dateFormat: 'yy-mm-dd'});
  $("#eventenddate").datepicker({ dateFormat: 'yy-mm-dd' });

// AJAX call for PostalCode
  var oldPostalCode = $('#postalcode').val();
  var pc_check = /^[0-9]{3}-[0-9]{4}$/;
  $('#postalcode').keyup(function(){  //fill other fields when applicable Postal Code is typed
    var newPostalCode = $('#postalcode').val();
    if (newPostalCode != oldPostalCode) {
      oldPostalCode = newPostalCode;
      if (pc_check.test($('#postalcode').val())) {
        showSpinner($('#postalcode'));
        $.getJSON("ajax_request.php?req=PC&pc="+$('#postalcode').val(), function(data) {
          hideSpinner($('#postalcode'));
          if (data.alert === "NOSESSION") {
            alert("<? echo _("Your login has timed out - please refresh the page."); ?>");
          } else if (data.alert === "PCNOTFOUND") {
            alert("<? echo _("This postal code has not yet been used in an address."); ?>");
          } else {
            $('#pctext').show();
            $('#postalcode').blur();
            //$('#pcform').populate(data, {resetForm:false});
            $('#prefecture').val(data.prefecture);
            $('#shikucho').val(data.shikucho);
            $('#romaji').val(j2t(data.romaji));
            //$('#romaji').val(j2t($('#romaji').val()));
          }
        });
      } else {
        if ($('#pctext').is(":visible")) {
          $('#pctext').hide();
          $('#prefecture,#shikucho,#romaji').val("");
        }
      }
    }
  });
  $('#postalcode').live('input paste',function(){ $('#postalcode').keyup(); });
  $('#loadingPC').hide().ajaxStart(function() {$(this).show(); }).ajaxStop(function() { $(this).hide(); });

// AJAX call for Categories
  $("#catid").change(function(){
    if ($("#catid").val() == "new") {
      $("#category").val("");
      $("#catorgs,#catpeople").prop("checked", true);
      $("#cat_del").prop('disabled', true);
    } else {
      showSpinner($('#catid'));
      $.getJSON("ajax_request.php?req=Category&catid="+$('#catid').val(), function(data) {
        hideSpinner($('#catid'));
        if (data.alert === "NOSESSION") {
          alert("<? echo _("Your login has timed out - please refresh the page."); ?>");
        } else {
          $("#catorgs,#catpeople").prop("checked", false);
          $('#catform').populate(data, {resetForm:false});
          $("#cat_del").prop('disabled', false);
        }
      });
    }
  });

// AJAX call for Contact Types
  $("#ctypeid").change(function(){
    if ($("#ctypeid").val() == "new") {
      $("#ctype").val("");
      $("#ctcolor").val("FFFFFF");
      $("#ctcolor_button").css("background-color","#FFFFFF");
      $("#ct_del").prop('disabled', true);
    } else {
      showSpinner($('#ctypeid'));
      $.getJSON("ajax_request.php?req=CType&ctypeid="+$('#ctypeid').val(), function(data) {
        hideSpinner($('#ctypeid'));
        if (data.alert) {
          alert(data.alert);
        } else {
          $('#ctform').populate(data, {resetForm:false});
          $("#ctcolor_button").css("background-color","#"+data.ctcolor);
          $("#ct_del").prop('disabled', false);
        }
      });
    }
  });

// AJAX call for Donation Types
  $("#dtypeid").change(function(){
    if ($("#dtypeid").val() == "new") {
      $("#dtype").val("");
      $("#dtcolor").val("FFFFFF");
      $("#dtcolor_button").css("background-color","#FFFFFF");
      $("#dt_del").prop('disabled', true);
    } else {
      showSpinner($('#dtypeid'));
      $.getJSON("ajax_request.php?req=DType&dtypeid="+$('#dtypeid').val(), function(data) {
        hideSpinner($('#dtypeid'));
        if (data.alert === "NOSESSION") {
          alert("<? echo _("Your login has timed out - please refresh the page."); ?>");
        } else {
          $('#dtform').populate(data, {resetForm:false});
          $("#dtcolor_button").css("background-color","#"+data.dtcolor);
          $("#dt_del").prop('disabled', false);
        }
      });
    }
  });

// AJAX call for Events
  $("#eventid").change(function(){
    if ($("#eventid").val() == "new") {
      $("#event, #eventstartdate, #eventenddate, #remarks").val("");
      $("#active").prop("checked", true);
      $("#usetimes").prop("checked", false);
      $("#event_del").prop('disabled', true);
    } else {
      showSpinner($('#eventid'));
      $.getJSON("ajax_request.php?req=Event&eventid="+$('#eventid').val(), function(data) {
        hideSpinner($('#eventid'));
        if (data.alert === "NOSESSION") {
          alert("<? echo _("Your login has timed out - please refresh the page."); ?>");
        } else {
          $("#active,#usetimes").prop("checked", false);
          $('#eventform').populate(data, {resetForm:false});
          $("#event_del").prop('disabled', false);
        }
      });
    }
  });

// AJAX call for Users
  $("#userid").change(function(){
    if ($("#userid").val() == "new") {
      $("#username, #new_userid, #old_userid, #new_pw1, #new_pw2, #dashboardhead, #dashboardbody").val("");
      $("#language").val($_SESSION['lang']);
      $("#admin").prop("checked", true);
      $("#hidedonations").prop("checked", <? echo ($_SESSION['hidedonations_default']=="yes" ? "true" : "false"); ?>);
      $("#login_del").prop('disabled', true);
    } else {
      showSpinner($('#userid'));
      $.getJSON("ajax_request.php?req=Login&userid="+$('#userid').val(), function(data) {
        hideSpinner($('#userid'));
        if (data.alert === "NOSESSION") {
          alert("<? echo _("Your login has timed out - please refresh the page."); ?>");
        } else {
          $("#admin,#hidedonations").prop("checked", false);
          $('#loginform').populate(data, {resetForm:false});
          $("#language").val(data.language);
          $("#login_del").prop('disabled', false);
        }
      });
    }
  });
});

function validate(form) {
  switch(form) {
  case "cat":
    if (document.catform.cat_text.value == "") {
      alert("<? echo _("Category name cannot be blank."); ?>");
      return false;
    }
    if (!document.catform.orgs.checked && !document.catform.people.checked) {
      alert("<? echo _("You must check at least one 'Use For' checkbox."); ?>");
      return false;
    }
    break;
  case "event":
    if (document.eventform.event.value == "") {
      alert("<? echo _("Event name cannot be blank."); ?>");
      return false;
    }
    if (document.eventform.eventstartdate.value == "") {
      alert("<? echo _("Start date cannot be blank."); ?>");
      return false;
    }
    break;
    if (document.eventform.eventenddate.value == "") {
      alert("<? echo _("End date cannot be blank."); ?>");
      return false;
    }
    break;
  case "pwd":
    if (document.pwform.old_pw.value == "") {
      alert("<? echo _("You must enter your current password for validation."); ?>");
      return false;
    }
    if (document.pwform.new_pw1.value != document.pwform.new_pw2.value) {
      alert("<? echo _("The two new password entries do not match."); ?>");
      return false;
    }
    break;
  case "pc":
    if (document.pcform.prefecture.value == "" || document.pcform.shikucho == ""
    || (document.pcform.romaji && document.pcform.romaji == "")) {
      alert("<? echo _("All fields must be filled in."); ?>");
      return false;
    }
    break;
  case "ct":
    if (document.ctform.ct.value == "") {
      alert("<? echo _("Contact Type name cannot be blank."); ?>");
      return false;
    }
    break;
  case "login":
    if (document.loginform.username.value == "") {
      alert("<? echo _("User Name cannot be blank."); ?>");
      return false;
    } else if (document.loginform.new_userid.value == "") {
      alert("<? echo _("UserID cannot be blank."); ?>");
      return false;
    } else if (document.loginform.userid.selectedIndex == 0 && document.loginform.new_pw1.value == "") {
      alert("<? echo _("You must enter a password for a new user."); ?>");
      return false;
    } else if (document.loginform.new_pw1.value != "" && document.loginform.new_pw1.value != document.loginform.new_pw2.value) {
      alert("<? echo _("The two password entries don't match."); ?>");
      return false;
    }
    break;
  }
}
</script>
<? header2(1); ?>
<h1 id="title"><? echo _("Database Maintenance"); ?></h1>

<!-- POSTAL CODES -->

<form action="do_maint.php" method="post" name="pcform" id="pcform" onSubmit="return validate('pc');">
  <fieldset><legend><? echo _("Edit Postal Code Data"); ?></legend>
  <span class="input postalcode"><label for="postalcode"><? echo _("Postal Code"); ?>: </label><input type="text"
  id="postalcode" name="postalcode" style="width:5em" maxlength="8"></span>
  <div id="pctext" style="display:none">
    <p class="alert"><? echo _("(The Japanese data here is originally from the Japan post office database, so be sure you are right before disagreeing with them!)"); ?></p>
    <span class="input prefecture"><label for="prefecture"><? echo _("Prefecture"); ?>:<input type="text" id="prefecture" name="prefecture" style="width:4em" maxlength=12></label></span>
    <span class="input shikucho"><label for="shikucho"><? echo _("City, etc."); ?>:<input type="text" id="shikucho" name="shikucho" style="width:20em" maxlength=54></label></span>
<?
if ($_SESSION['romajiaddresses'] == "yes") {
?>
    <span class="input romaji"><label for="romaji"><? echo _("Romaji of Postal Text"); ?>:</label>
    <textarea id="romaji" name="romaji" style="height:4em;width:20em"></textarea>
    <span class="comment"><? echo _("(reverse order from Japanese, carriage return after first item)"); ?></span></span>
<?
} //end of if romajiaddresses=yes
?>
    <input type="submit" name="pc_upd" id="pc_upd" value="<? echo _("Update Postal Code Data"); ?>">
  </div>
</fieldset></form>

<!-- CATEGORIES -->

<form action="do_maint.php" method="post" name="catform" id="catform" onSubmit="return validate('cat');">
  <fieldset><legend><? echo _("Category Management"); ?></legend>
  <p><? echo _("Select a category to rename or delete. Or select &quot;New Category&quot; and type a new category name."); ?></p>
  <select id="catid" name="catid" size="1">
    <option value="new"><? echo _("New Category..."); ?></option>
<?
$result = sqlquery_checked("SELECT CategoryID,Category FROM category ORDER BY Category");
while ($row = mysql_fetch_object($result))  echo "    <option value=\"".$row->CategoryID."\">".$row->Category."</option>\n";
?>
  </select>
  <label class="label-n-input"><? echo _("Category Name"); ?>: <input type="text"
  id="category" name="category" style="width:20em" maxlength="45"></label>
  <label class="label-n-input"><input type="checkbox" id="catorgs" name="catorgs" value="checkboxValue" checked><? echo _("Use for Organizations"); ?></label>
  <label class="label-n-input"><input type="checkbox" id="catpeople" name="catpeople" value="checkboxValue" checked><? echo _("Use for Individuals"); ?></label>
  <div class="submits"><input type="submit" id="cat_add_upd" name="cat_add_upd" value="<? echo _("Add or Rename"); ?>">
  <input type="submit" id="cat_del" name="cat_del" value="<? echo _("Delete"); ?>" disabled></div>
</fieldset></form>

<!-- CONTACT TYPES -->

<form action="do_maint.php" method="post" name="ctform" id="ctform" onSubmit="return validate('ct');">
  <fieldset><legend><? echo _("Contact Types"); ?></legend>
  <p><? echo _("Fill in the information to add a new Contact Type (the color is optional). Or select an existing entry to make changes or delete."); ?></p>
  <select id="ctypeid" name="ctypeid" size="1">
    <option value="new"><? echo _("New Contact Type..."); ?></option>
<?
$result = sqlquery_checked("SELECT * FROM contacttype ORDER BY ContactType");
while ($row = mysql_fetch_object($result))  echo "    <option value=\"".$row->ContactTypeID."\" style=\"background-color:#".$row->BGColor."\">".$row->ContactType."</option>\n";
?>
  </select>
  <label class="label-n-input"><? echo _("Contact Type Name"); ?>: <input type="text"
  id="ctype" name="ctype" style="width:20em" maxlength="30"></label>
  <div class="color_section"><label class="label-n-input"><? echo _("Optional background color (choose something light)"); ?>: <input
  name="ctcolor_button" id="ctcolor_button" type="button" value="<? echo _("Click to pick a color..."); ?>">
  <input type="text" id="ctcolor" name="ctcolor" value="FFFFFF" style="width:5em"></label>
  <script type="text/javascript">
  var colorBtn = document.getElementById("ctcolor_button");
  var jsc = new jscolor.color(colorBtn, {valueElement:'ctcolor',pickerMode:'HSV',pickerOnfocus:false});
  $(colorBtn).click(function (evt) { jsc.showPicker(); evt.stopPropagation(); });
  $("body").mouseup(function () { jsc.hidePicker(); });
  </script></div>
  <label class="label-n-input"><? echo _("Template"); ?>: <textarea id="cttemplate" name="cttemplate" rows="3" cols="50"></textarea></label>
  <div class="submits"><input type="submit" id="ct_add_upd" name="ct_add_upd" value="<? echo _("Add or Update"); ?>">
  <input type="submit" id="ct_del" name="ct_del" value="<? echo _("Delete"); ?>" disabled></div>
</fieldset></form>

<?
if ($_SESSION['donations']) {
?>
<!-- DONATION TYPES -->

<form action="do_maint.php" method="post" name="dtform" id="dtform">
  <fieldset><legend><? echo _("Donation Types"); ?></legend>
  <p><? echo _("Fill in the information to add a new Donation Type. Or select an existing entry to make changes or delete."); ?></p>
  <select id="dtypeid" name="dtypeid" size="1">
    <option value="new"><? echo _("New Donation Type..."); ?></option>
<?
$result = sqlquery_checked("SELECT * FROM donationtype ORDER BY DonationType");
while ($row = mysql_fetch_object($result))  echo "    <option value=\"".$row->DonationTypeID."\" style=\"background-color:#".$row->BGColor."\">".$row->DonationType."</option>\n";
?>
  </select>
  <label class="label-n-input"><? echo _("Donation Type Name"); ?>: <input type="text"
  id="dtype" name="dtype" style="width:20em" maxlength="100"></label>
  <div class="color_section"><label class="label-n-input"><? echo _("Optional background color (choose something light)"); ?>: <input
  id="dtcolor_button" name="dtcolor_button" id="dtcolor_button" type="button" value="<? echo _("Click to pick a color..."); ?>">
  <input type="text" id="dtcolor" name="dtcolor" value="FFFFFF" style="width:5em"></label>
  <script type="text/javascript">
  var colorBtn2 = document.getElementById("dtcolor_button");
  var jsc2 = new jscolor.color(colorBtn2, {valueElement:'dtcolor',pickerMode:'HSV',pickerOnfocus:false});
  $(colorBtn2).click(function (evt2) { jsc2.showPicker(); evt2.stopPropagation(); });
  $("body").mouseup(function () { jsc2.hidePicker(); });
  </script></div>
  <div class="submits"><input type="submit" id="dt_add_upd" name="dt_add_upd" value="<? echo _("Add or Update"); ?>">
  <input type="submit" id="dt_del" name="dt_del" value="<? echo _("Delete"); ?>" disabled></div>
</fieldset></form>
<?
} //end of if donations on
?>

<!-- EVENTS -->

<form action="do_maint.php" method="post" name="eventform" id="eventform" onSubmit="return validate('event');">
  <fieldset><legend><? echo _("Event Management"); ?></legend>
  <p><? echo _("Fill in the information to add a new event.&nbsp; Or select an event, and modify its info ".
  "(name, remarks, active status, and/or start date).&nbsp; Or select a category to delete and press Delete."); ?></p>
  <select id="eventid" name="eventid" size="1">
    <option value="new"><? echo _("New Event..."); ?></option>
<?
$result = sqlquery_checked("SELECT EventID,Event,Active FROM event ORDER BY Event");
while ($row = mysql_fetch_object($result))  echo "    <option class=\"".($row->Active ? "active" : "inactive").
"\" value=\"".$row->EventID."\">".$row->Event."</option>\n";
?>
  </select>
  <label class="label-n-input"><? echo _("Event"); ?>: <input type="text" id="event" name="event" style="width:20em" maxlength="50"></label>
  <label class="label-n-input"><? echo _("Start Date"); ?>: <input type="text" name="eventstartdate"
  id="eventstartdate" style="width:6em" maxlength="10"></label>
  <label class="label-n-input"><? echo _("End Date"); ?>: <input type="text" name="eventenddate"
  id="eventenddate" style="width:6em" maxlength="10"></label>
<!--  <label class="label-n-input"><input type="checkbox" id="active" name="active" value="checkboxValue"
  checked><? //echo _("Currently Ongoing"); ?></label> -->
  <label class="label-n-input"><input type="checkbox" id="usetimes" name="usetimes" value="checkboxValue"><? echo _("Use Times"); ?></label>
  <label class="label-n-input"><? echo _("Description"); ?>: <textarea id="remarks" name="remarks" rows="3" cols="50"></textarea></label>
  <div class="submits"><input type="submit" id="event_add_upd" name="event_add_upd" value="<? echo _("Add or Update"); ?>">
  <input type="submit" id="event_del" name="event_del" value="<? echo _("Delete"); ?>" disabled></div>
</fieldset></form>

<!-- USER LANGUAGE -->

<form action="do_maint.php" method="post" name="myuserform" id="myuserform" onsubmit="return validate('user');">
  <fieldset><legend><? echo _("My User Settings"); ?></legend>
  <label class="label-n-input"><? echo _("Language for Interface"); ?>: <select id="mylanguage" name="language" size="1">
    <option value="en_US"<? if($_SESSION['lang']=="en_US") echo " selected"; ?>><? echo _("English"); ?></option>
    <option value="ja_JP"<? if($_SESSION['lang']=="ja_JP") echo " selected"; ?>><? echo _("Japanese"); ?></option>
  </select></label>
  <input type="submit" name="user_upd" value="<? echo _("Save Changes"); ?>"> 
</fieldset></form>

<!-- PASSWORD -->

<form action="do_maint.php" method="post" name="pwform" autocomplete="off" onsubmit="return validate('pwd');">
  <fieldset><legend><? echo _("Change My Password"); ?></legend>
  <label class="label-n-input"><? echo _("Old"); ?>: <input type="password" id="old_pw" name="old_pw" style="width:8em"></label>
  <label class="label-n-input"><? echo _("New"); ?>: <input type="password" id="new_pw1" name="new_pw1" style="width:8em"></label>
  <label class="label-n-input"><? echo _("New again"); ?>: <input type="password" id="new_pw2" name="new_pw2" style="width:8em"></label>
  <input type="submit" id="pw_upd" name="pw_upd" value="<? echo _("Change Password"); ?>"> 
</fieldset></form>

<?
if ($_SESSION['admin'] == 1) {
?>
<!-- LOGIN USERS -->

<form action="do_maint.php" method="post" name="loginform" id="loginform" autocomplete="off" onSubmit="return validate('login');">
  <fieldset><legend><? echo _("User (Login) Management"); ?></legend>
  <p><? echo _("Fill in the information to add a new user.  Or select an existing user to make changes or delete.".
  "NOTE: You cannot see the existing password, but you can enter a new one if the user forgot his/her password."); ?></p>
  <select id="userid" name="userid" size="1">
    <option value="new"><? echo _("New User..."); ?></option>
<?
$result = sqlquery_checked("SELECT UserID,UserName FROM login ORDER BY UserName");
while ($row = mysql_fetch_object($result))  echo "    <option value=\"".$row->UserID."\">".$row->UserName."</option>\n";
?>
  </select>
  <input type="hidden" id="old_userid" name="old_userid" value="">
  <label class="label-n-input"><? echo _("Name"); ?>: <input type="text"
  id="username" name="username" style="width:10em" maxlength="30"></label>
  <label class="label-n-input"><? echo _("UserID (to log in)"); ?>: <input type="text"
  id="new_userid" name="new_userid" style="width:5em" maxlength="16">
  <span class="comment"><? echo _("(max. 16 English characters, no spaces or punctuation)"); ?></span></label>
  <label class="label-n-input"><? echo _("Language for Interface"); ?>: <select id="language" name="language" size="1">
    <option value="en_US"<? if($_SESSION['lang']=="en_US") echo " selected"; ?>><? echo _("English"); ?></option>
    <option value="ja_JP"<? if($_SESSION['lang']=="ja_JP") echo " selected"; ?>><? echo _("Japanese"); ?></option>
  </select></label>
  <label class="label-n-input"><input type="checkbox" id="admin" name="admin" value="checkboxValue"><? echo _("Admin Privileges"); ?></label>
<? if ($_SESSION['donations'] == "yes") { ?>
  <label class="label-n-input"><input type="checkbox" id="hidedonations" name="hidedonations" value="checkboxValue"
<? if ($_SESSION['hidedonations_default'] == "yes") echo " checked"; ?>><? echo _("Hide Donation Info"); ?></label>
<? } //if donations is on ?>
  <label class="label-n-input"><? echo _("New Password"); ?>: <input type="password"
  id="new_pw1" name="new_pw1" style="width:10em">
  <span class="comment"><? echo _("(leave blank if not changing password)"); ?></span></label>
  <label class="label-n-input"><? echo _("New Password again"); ?>: <input type="password"
  id="new_pw2" name="new_pw2" style="width:10em"></label><br />
  <label class="label-n-input"><? echo _("PHP for Dashboard Head"); ?>: <textarea id="dashboardhead" name="dashboardhead" style="height:3em;width:70%"></textarea></label>
  <label class="label-n-input"><? echo _("PHP for Dashboard Body"); ?>: <textarea id="dashboardbody" name="dashboardbody" style="height:3em;width:70%"></textarea></label>
  <br /><input type="submit" id="login_add_upd" name="login_add_upd" value="<? echo _("Add or Update"); ?>">
  <input type="submit" id="login_del" name="login_del" value="<? echo _("Delete"); ?>" disabled>
</fieldset></form>
<?
} //end of if admin=1

footer(1);
?>
