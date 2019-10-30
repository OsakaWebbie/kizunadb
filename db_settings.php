<?php
include("functions.php");
include("accesscontrol.php");

header1(_("Database Settings"));
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
  if ((evt.keyCode == 13) && (node.type=="text") && node.name!="textinput1")  {return false;}
}
document.onkeypress = stopRKey;

function showSpinner(el) {
  el.append('<span class="spinner"><img src="graphics/ajax-loader.gif" alt="Loading..." /></span>');
}

function hideSpinner(el) {
  el.find(".spinner").remove();
}

$(document).ready(function(){
  //$( "#attemplate,#dashboard" ).resizable();
<?php if($_SESSION['lang']=="ja_JP") echo "  $.datepicker.setDefaults( $.datepicker.regional[\"ja\"] );\n"; ?>
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
            alert("<?=_("Your login has timed out - please refresh the page.")?>");
          } else if (data.alert === "PCNOTFOUND") {
            alert("<?=_("This postal code has not yet been used in an address.")?>");
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
      $("#usefor").val("");
      $("#cat_del").prop('disabled', true);
    } else {
      showSpinner($('#catid'));
      $.getJSON("ajax_request.php?req=Category&catid="+$('#catid').val(), function(data) {
        hideSpinner($('#catid'));
        if (data.alert === "NOSESSION") {
          alert("<?=_("Your login has timed out - please refresh the page.")?>");
        } else {
          $('#catform').populate(data, {resetForm:false});
          $('#usefor').val(data.usefor); // I don't know why populate didn't take care of this
          $("#cat_del").prop('disabled', false);
        }
      });
    }
  });

// AJAX call for Action Types
  $("#atypeid").change(function(){
    if ($("#atypeid").val() == "new") {
      $("#atype").val("");
      $("#atcolor").val("FFFFFF");
      $("#atcolor_button").css("background-color","#FFFFFF");
      $("#at_del").prop('disabled', true);
    } else {
      showSpinner($('#atypeid'));
      $.getJSON("ajax_request.php?req=AType&atypeid="+$('#atypeid').val(), function(data) {
        hideSpinner($('#atypeid'));
        if (data.alert) {
          alert(data.alert);
        } else {
          $('#atform').populate(data, {resetForm:false});
          $("#atcolor_button").css("background-color","#"+data.atcolor);
          $("#at_del").prop('disabled', false);
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
          alert("<?=_("Your login has timed out - please refresh the page.")?>");
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
          alert("<?=_("Your login has timed out - please refresh the page.")?>");
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
      $("#username, #new_userid, #old_userid, #new_pw1, #new_pw2, #dashboard").val("");
      $("#language").val($_SESSION['lang']);
      $("#admin").prop("checked", true);
      $("#hidedonations").prop("checked", <?=($_SESSION['hidedonations_default']=="yes" ? "true" : "false")?>);
      $("#user_del").prop('disabled', true);
    } else {
      showSpinner($('#userid'));
      $.getJSON("ajax_request.php?req=UserLogin&userid="+$('#userid').val(), function(data) {
        hideSpinner($('#userid'));
        if (data.alert === "NOSESSION") {
          alert("<?=_("Your login has timed out - please refresh the page.")?>");
        } else {
          $("#admin,#hidedonations").prop("checked", false);
          $('#userform').populate(data, {resetForm:false});
          $("#language").val(data.language);
          $("#user_del").prop('disabled', false);
          $("#login").html(data.login); //it seems populate doesn't work well with non-inputs, so adding these by hand
          $("#login_num").html(data.login_num);
          $("#login_years").html(JSON.stringify(data.login_years, null, 4)); //login_years is an array of year => num_logins
        }
      });
    }
  });
});

function validate(form) {
  switch(form) {
  case "cat":
    if (document.catform.category.value == "") {
      alert("<?=_("Category name cannot be blank.")?>");
      return false;
    }
    if (document.catform.usefor.value == "") {
      alert("<?=_("Please choose an application.")?>");
      return false;
    }
    break;
  case "event":
    if (document.eventform.event.value == "") {
      alert("<?=_("Event name cannot be blank.")?>");
      return false;
    }
    if (document.eventform.eventstartdate.value == "") {
      alert("<?=_("Start date cannot be blank.")?>");
      return false;
    }
    break;
    if (document.eventform.eventenddate.value == "") {
      alert("<?=_("End date cannot be blank.")?>");
      return false;
    }
    break;
  case "pc":
    if (document.pcform.prefecture.value == "" || document.pcform.shikucho == ""
    || (document.pcform.romaji && document.pcform.romaji == "")) {
      alert("<?=_("All fields must be filled in.")?>");
      return false;
    }
    break;
  case "at":
    if (document.atform.at.value == "") {
      alert("<?=_("Action Type name cannot be blank.")?>");
      return false;
    }
    break;
  case "user":
    if (document.userform.username.value == "") {
      alert("<?=_("User Name cannot be blank.")?>");
      return false;
    } else if (document.userform.new_userid.value == "") {
      alert("<?=_("UserID cannot be blank.")?>");
      return false;
    } else if (document.userform.userid.selectedIndex == 0 && document.userform.new_pw1.value == "") {
      alert("<?=_("You must enter a password for a new user.")?>");
      return false;
    } else if (document.userform.new_pw1.value != "" && document.userform.new_pw1.value != document.userform.new_pw2.value) {
      alert("<?=_("The two password entries don't match.")?>");
      return false;
    }
    break;
  }
}
</script>
<?php header2(1); ?>
<h1 id="title"><?=_("Database Settings")?></h1>

<!-- POSTAL CODES -->

<form action="do_maint.php" method="post" name="pcform" id="pcform" onSubmit="return validate('pc');">
  <fieldset><legend><?=_("Edit Postal Code Data")?></legend>
  <span class="input postalcode"><label for="postalcode"><?=_("Postal Code")?>: </label><input type="text"
  id="postalcode" name="postalcode" style="width:5em" maxlength="8"></span>
  <div id="pctext" style="display:none">
    <p class="alert"><?=_("(The Japanese data here is originally from the Japan post office database, so be sure you are right before disagreeing with them!)")?></p>
    <span class="input prefecture"><label for="prefecture"><?=_("Prefecture")?>:<input type="text" id="prefecture" name="prefecture" style="width:4em" maxlength=12></label></span>
    <span class="input shikucho"><label for="shikucho"><?=_("City, etc.")?>:<input type="text" id="shikucho" name="shikucho" style="width:20em" maxlength=54></label></span>
<?php
if ($_SESSION['romajiaddresses'] == "yes") {
?>
    <span class="input romaji"><label for="romaji"><?=_("Romaji of Postal Text")?>:</label>
    <textarea id="romaji" name="romaji" style="height:4em;width:20em"></textarea>
    <span class="comment"><?=_("(reverse order from Japanese, carriage return after first item)")?></span></span>
<?php
} //end of if romajiaddresses=yes
?>
    <input type="submit" name="pc_upd" id="pc_upd" value="<?=_("Update Postal Code Data")?>">
  </div>
</fieldset></form>

<!-- CATEGORIES -->

<form action="do_maint.php" method="post" name="catform" id="catform" onSubmit="return validate('cat');">
  <fieldset><legend><?=_("Category Management")?></legend>
  <p><?=_("Select a category to rename or delete. Or select &quot;New Category&quot; and type a new category name.")?></p>
  <select id="catid" name="catid" size="1">
    <option value="new"><?=_("New Category...")?></option>
<?php
$result = sqlquery_checked("SELECT CategoryID,Category FROM category ORDER BY Category");
while ($row = mysqli_fetch_object($result))  echo "    <option value=\"".$row->CategoryID."\">".$row->Category."</option>\n";
?>
  </select>
  <label class="label-n-input"><?=_("Category Name")?>: <input type="text"
  id="category" name="category" style="width:20em" maxlength="45"></label>
  <label class="label-n-input"><?=_("Application")?>:
    <select id="usefor" name="usefor" size="1">
      <option value=""><?=_("Select...")?></option>
      <option value="P"><?=_("Individuals only")?></option>
      <option value="O"><?=_("Organizations only")?></option>
      <option value="OP"><?=_("All records")?></option>
    </select>
  </label>
  <div class="submits"><input type="submit" id="cat_add_upd" name="cat_add_upd" value="<?=_("Add or Rename")?>">
  <input type="submit" id="cat_del" name="cat_del" value="<?=_("Delete")?>" disabled></div>
</fieldset></form>

<!-- ACTION TYPES -->

<form action="do_maint.php" method="post" name="atform" id="atform" onSubmit="return validate('at');">
  <fieldset><legend><?=_("Action Types")?></legend>
  <p><?=_("Fill in the information to add a new Action Type (the color is optional). Or select an existing entry to make changes or delete.")?></p>
  <select id="atypeid" name="atypeid" size="1">
    <option value="new"><?=_("New Action Type...")?></option>
<?php
$result = sqlquery_checked("SELECT * FROM actiontype ORDER BY ActionType");
while ($row = mysqli_fetch_object($result))  echo "    <option value=\"".$row->ActionTypeID."\" style=\"background-color:#".$row->BGColor."\">".$row->ActionType."</option>\n";
?>
  </select>
  <label class="label-n-input"><?=_("Action Type Name")?>: <input type="text"
  id="atype" name="atype" style="width:20em" maxlength="30"></label>
  <div class="color_section"><label class="label-n-input"><?=_("Optional background color (choose something light)")?>: <input
  name="atcolor_button" id="atcolor_button" type="button" value="<?=_("Click to pick a color...")?>">
  <input type="text" id="atcolor" name="atcolor" value="FFFFFF" style="width:5em"></label>
  <script type="text/javascript">
  var colorBtn = document.getElementById("atcolor_button");
  var jsc = new jscolor.color(colorBtn, {valueElement:'atcolor',pickerMode:'HSV',pickerOnfocus:false});
  $(colorBtn).click(function (evt) { jsc.showPicker(); evt.stopPropagation(); });
  $("body").mouseup(function () { jsc.hidePicker(); });
  </script></div>
  <label class="label-n-input"><?=_("Template")?>: <textarea id="attemplate" name="attemplate" rows="3" cols="50"></textarea></label>
  <div class="submits"><input type="submit" id="at_add_upd" name="at_add_upd" value="<?=_("Add or Update")?>">
  <input type="submit" id="at_del" name="at_del" value="<?=_("Delete")?>" disabled></div>
</fieldset></form>

<?php
if ($_SESSION['donations']) {
?>
<!-- DONATION TYPES -->

<form action="do_maint.php" method="post" name="dtform" id="dtform">
  <fieldset><legend><?=_("Donation Types")?></legend>
  <p><?=_("Fill in the information to add a new Donation Type. Or select an existing entry to make changes or delete.")?></p>
  <select id="dtypeid" name="dtypeid" size="1">
    <option value="new"><?=_("New Donation Type...")?></option>
<?php
$result = sqlquery_checked("SELECT * FROM donationtype ORDER BY DonationType");
while ($row = mysqli_fetch_object($result))  echo "    <option value=\"".$row->DonationTypeID."\" style=\"background-color:#".$row->BGColor."\">".$row->DonationType."</option>\n";
?>
  </select>
  <label class="label-n-input"><?=_("Donation Type Name")?>: <input type="text"
  id="dtype" name="dtype" style="width:20em" maxlength="100"></label>
  <div class="color_section"><label class="label-n-input"><?=_("Optional background color (choose something light)")?>: <input
  id="dtcolor_button" name="dtcolor_button" id="dtcolor_button" type="button" value="<?=_("Click to pick a color...")?>">
  <input type="text" id="dtcolor" name="dtcolor" value="FFFFFF" style="width:5em"></label>
  <script type="text/javascript">
  var colorBtn2 = document.getElementById("dtcolor_button");
  var jsc2 = new jscolor.color(colorBtn2, {valueElement:'dtcolor',pickerMode:'HSV',pickerOnfocus:false});
  $(colorBtn2).click(function (evt2) { jsc2.showPicker(); evt2.stopPropagation(); });
  $("body").mouseup(function () { jsc2.hidePicker(); });
  </script></div>
  <div class="submits"><input type="submit" id="dt_add_upd" name="dt_add_upd" value="<?=_("Add or Update")?>">
  <input type="submit" id="dt_del" name="dt_del" value="<?=_("Delete")?>" disabled></div>
</fieldset></form>
<?php
} //end of if donations on
?>

<!-- EVENTS -->

<form action="do_maint.php" method="post" name="eventform" id="eventform" onSubmit="return validate('event');">
  <fieldset><legend><?=_("Event Management")?></legend>
  <p><?=_("Fill in the information to add a new event.&nbsp; Or select an event, and modify its info ".
  "(name, remarks, active status, and/or start date).&nbsp; Or select a category to delete and press Delete.")?></p>
  <select id="eventid" name="eventid" size="1">
    <option value="new"><?=_("New Event...")?></option>
<?php
$result = sqlquery_checked("SELECT EventID,Event,Active FROM event ORDER BY Event");
while ($row = mysqli_fetch_object($result))  echo "    <option class=\"".($row->Active ? "active" : "inactive").
"\" value=\"".$row->EventID."\">".$row->Event."</option>\n";
?>
  </select>
  <label class="label-n-input"><?=_("Event")?>: <input type="text" id="event" name="event" style="width:20em" maxlength="50"></label>
  <label class="label-n-input"><?=_("Start Date")?>: <input type="text" name="eventstartdate"
  id="eventstartdate" style="width:6em" maxlength="10"></label>
  <label class="label-n-input"><?=_("End Date")?>: <input type="text" name="eventenddate"
  id="eventenddate" style="width:6em" maxlength="10"></label>
<!--  <label class="label-n-input"><input type="checkbox" id="active" name="active" value="checkboxValue"
  checked><?php //echo _("Currently Ongoing"); ?></label> -->
  <label class="label-n-input"><input type="checkbox" id="usetimes" name="usetimes" value="checkboxValue"><?=_("Use Times")?></label>
  <label class="label-n-input"><?=_("Description")?>: <textarea id="remarks" name="remarks" rows="3" cols="50"></textarea></label>
  <div class="submits"><input type="submit" id="event_add_upd" name="event_add_upd" value="<?=_("Add or Update")?>">
  <input type="submit" id="event_del" name="event_del" value="<?=_("Delete")?>" disabled></div>
</fieldset></form>

<?php
if ($_SESSION['admin'] == 1) {
?>
<!-- USERS -->

<form action="do_maint.php" method="post" name="userform" id="userform" autocomplete="off" onSubmit="return validate('user');">
  <fieldset><legend><?=_("User Management")?></legend>
  <p><?=_("Fill in the information to add a new user.  Or select an existing user to make changes or delete.".
  "NOTE: You cannot see the existing password, but you can enter a new one if the user forgot his/her password.")?></p>
  <select id="userid" name="userid" size="1">
    <option value="new"><?=_("New User...")?></option>
<?php
$result = sqlquery_checked("SELECT UserID,UserName FROM user ORDER BY UserName");
while ($row = mysqli_fetch_object($result))  echo "    <option value=\"".$row->UserID."\">".$row->UserName."</option>\n";
?>
  </select>
  <input type="hidden" id="old_userid" name="old_userid" value="">
  <label class="label-n-input"><?=_("Name")?>: <input type="text"
  id="username" name="username" style="width:10em" maxlength="30"></label>
  <label class="label-n-input"><?=_("UserID (to log in)")?>: <input type="text"
  id="new_userid" name="new_userid" style="width:5em" maxlength="16">
  <span class="comment"><?=_("(max. 16 English characters, no spaces or punctuation)")?></span></label>
  <label class="label-n-input"><?=_("Language for Interface")?>: <select id="language" name="language" size="1">
    <option value="en_US"<?php if($_SESSION['lang']=="en_US") echo " selected"; ?>><?= _("English")?></option>
    <option value="ja_JP"<?php if($_SESSION['lang']=="ja_JP") echo " selected"; ?>><?=_("Japanese")?></option>
  </select></label>
  <label class="label-n-input"><input type="checkbox" id="admin" name="admin" value="checkboxValue"><?=_("Admin Privileges")?></label>
<?php if ($_SESSION['donations'] == "yes") { ?>
  <label class="label-n-input"><input type="checkbox" id="hidedonations" name="hidedonations" value="checkboxValue"
<?php if ($_SESSION['hidedonations_default'] == "yes") echo " checked"; ?>><?=_("Hide Donation Info")?></label>
<?php } //if donations is on ?>
  <label class="label-n-input"><?=_("New Password")?>: <input type="password"
  id="new_pw1" name="new_pw1" style="width:10em">
  <span class="comment"><?=_("(leave blank if not changing password)")?></span></label>
  <label class="label-n-input"><?=_("New Password again")?>: <input type="password"
  id="new_pw2" name="new_pw2" style="width:10em"></label>
  <br />
  <label class="label-n-input"><?=_("PHP for Dashboard")?>: <textarea id="dashboard" name="dashboard" style="height:3em;width:70%"></textarea></label>
  <br/>
  Last Login:<span class="comment" id="login"> <?=_("LoginTime")?> </span>
  Total Logins:<span class="comment" id="login_num"> <?=_("LoginNum")?> </span>
  Logins by Year:<span class="comment" id="login_years"> <?=_("LoginYears")?> </span>
  <br /><input type="submit" id="user_add_upd" name="user_add_upd" value="<?=_("Add or Update")?>">
  <input type="submit" id="user_del" name="user_del" value="<?=_("Delete")?>" disabled>
</fieldset></form>
<?php
} //end of if admin=1

footer();
?>
