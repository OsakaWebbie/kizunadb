<?php
include("functions.php");
include("accesscontrol.php");

header1(_("Database Settings"));
?>
<link rel="stylesheet" href="style.php?jquery=1" type="text/css" />
<style>
#status-msg {
  position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);
  padding: 10px 16px; background: #2e7d32; color: white; border-radius: 4px;
  box-shadow: 0 2px 8px rgba(0,0,0,.2); z-index: 10000; display: none;
}
</style>
<?php header2(1); ?>
<div id="at_del_dialog" title="<?=_("Delete Action Type")?>"><div id="at_del_dialog_body"></div></div>
<div id="dt_del_dialog" title="<?=_("Delete Donation Type")?>"><div id="dt_del_dialog_body"></div></div>
<div id="event_del_dialog" title="<?=_("Delete Event")?>"><div id="event_del_dialog_body"></div></div>
<h1 id="title"><?=_("Database Settings")?></h1>

<!-- POSTAL CODES -->

<form name="pcform" id="pcform">
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
    <button type="button" id="pc_upd"><?=_("Update Postal Code Data")?></button>
  </div>
</fieldset></form>

<!-- CATEGORIES -->

<form name="catform" id="catform">
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
  id="category" name="category" style="width:20em" maxlength="60"></label>
  <label class="label-n-input"><?=_("Application")?>:
    <select id="usefor" name="usefor" size="1">
      <option value=""><?=_("Select...")?></option>
      <option value="P"><?=_("Individuals only")?></option>
      <option value="O"><?=_("Organizations only")?></option>
      <option value="OP"><?=_("All records")?></option>
    </select>
  </label>
  <div class="submits"><button type="button" id="cat_add_upd"><?=_("Add or Rename")?></button>
  <button type="button" id="cat_del" disabled><?=_("Delete")?></button></div>
</fieldset></form>

<!-- ACTION TYPES -->

<form name="atform" id="atform">
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
  id="atype" name="atype" style="width:20em" maxlength="60"></label>
  <div class="color_section"><label class="label-n-input"><?=_("Optional background color (choose something light)")?>: <input
  name="atcolor_button" id="atcolor_button" type="button" value="<?=_("Click to pick a color...")?>">
  <input type="text" id="atcolor" name="atcolor" value="FFFFFF" style="width:5em"></label></div>
  <label class="label-n-input"><?=_("Template")?>: <textarea id="attemplate" name="attemplate" rows="3" cols="50"></textarea></label>
  <div class="submits"><button type="button" id="at_add_upd"><?=_("Add or Update")?></button>
  <button type="button" id="at_del" disabled><?=_("Delete")?></button></div>
</fieldset></form>

<?php
if ($_SESSION['donations']) {
?>
<!-- DONATION TYPES -->

<form name="dtform" id="dtform">
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
  <input type="text" id="dtcolor" name="dtcolor" value="FFFFFF" style="width:5em"></label></div>
  <div class="submits"><button type="button" id="dt_add_upd"><?=_("Add or Update")?></button>
  <button type="button" id="dt_del" disabled><?=_("Delete")?></button></div>
</fieldset></form>
<?php
} //end of if donations on
?>

<!-- EVENTS -->

<form name="eventform" id="eventform">
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
  <label class="label-n-input"><?=_("Event")?>: <input type="text" id="event" name="event" style="width:20em" maxlength="60"></label>
  <label class="label-n-input"><?=_("Start Date")?>: <input type="text" name="eventstartdate"
  id="eventstartdate" style="width:6em" maxlength="10"></label>
  <label class="label-n-input"><?=_("End Date")?>: <input type="text" name="eventenddate"
  id="eventenddate" style="width:6em" maxlength="10"></label>
<!--  <label class="label-n-input"><input type="checkbox" id="active" name="active" value="checkboxValue"
  checked><?php //echo _("Currently Ongoing"); ?></label> -->
  <label class="label-n-input"><input type="checkbox" id="usetimes" name="usetimes"><?=_("Use Times")?></label>
  <label class="label-n-input"><?=_("Description")?>: <textarea id="remarks" name="remarks" rows="2" cols="50" maxlength="255"></textarea></label>
  <div class="submits"><button type="button" id="event_add_upd"><?=_("Add or Update")?></button>
  <button type="button" id="event_del" disabled><?=_("Delete")?></button></div>
</fieldset></form>

<?php
if ($_SESSION['admin'] == 1) {
?>
<!-- USERS -->

<form name="userform" id="userform" autocomplete="off">
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
  <label class="label-n-input"><input type="checkbox" id="admin" name="admin"><?=_("Admin Privileges")?></label>
<?php if ($_SESSION['donations'] == "yes") { ?>
  <label class="label-n-input"><input type="checkbox" id="hidedonations" name="hidedonations"
<?php if ($_SESSION['hidedonations_default'] == "yes") echo " checked"; ?>><?=_("Hide Donation Info")?></label>
<?php } //if donations is on ?>
  <label class="label-n-input"><?=_("New Password")?>: <input type="password"
  id="new_pw1" name="new_pw1" style="width:10em">
  <span class="comment"><?=_("(leave blank if not changing password)")?></span></label>
  <label class="label-n-input"><?=_("New Password again")?>: <input type="password"
  id="new_pw2" name="new_pw2" style="width:10em"></label><br />
  <label class="label-n-input"><?=_("Dashboard Files")?>: <textarea id="dashboard" name="dashboard" style="height:2em;width:80%"></textarea></label>
  <div id="loginstats" class="comment"></div>
  <br /><button type="button" id="user_add_upd"><?=_("Add or Update")?></button>
  <button type="button" id="user_del" disabled><?=_("Delete")?></button>
</fieldset></form>
<?php
} //end of if admin=1
?>
<?php load_scripts(array('jquery', 'jqueryui', 'datepicker-ja', 'functions')); ?>
<script type="text/javascript" src="jscolor/jscolor.js"></script>
<script type="text/javascript">

function showStatus(msg) {
  var $s = $('#status-msg');
  if (!$s.length) $s = $('<div id="status-msg">').appendTo('body');
  $s.text(msg).fadeIn(100).delay(1500).fadeOut(400);
}
function selectUpsertOption($select, id, text, extraAttrs) {
  var $opt = $select.find('option[value="' + id + '"]');
  if ($opt.length) $opt.text(text);
  else $opt = $('<option>').val(id).text(text).appendTo($select);
  if (extraAttrs) $opt.attr(extraAttrs);
  selectSortOptions($select);
  $select.val(id);
}
function selectRemoveOption($select, id) {
  $select.find('option[value="' + id + '"]').remove();
  $select.val('new');
}
function selectSortOptions($select) {
  var $first = $select.find('option[value="new"]').detach();
  var opts = $select.find('option').get().sort(function(a, b) {
    return a.text.localeCompare(b.text);
  });
  $select.empty().append($first).append(opts);
}
function resetEntityForm($select) { $select.val('new').trigger('change'); }
function cloneSelectExcept(sourceSel, excludeId, newName) {
  var $clone = $(sourceSel).clone().attr('id', newName).attr('name', newName);
  $clone.find('option[value="' + excludeId + '"], option[value="new"]').remove();
  $clone.prepend('<option value=""><?=_("Select...")?></option>');
  $clone.val('');
  return $clone;
}

function stopRKey(evt) {
  var evt = (evt) ? evt : ((event) ? event : null);
  var node = (evt.target) ? evt.target : ((evt.srcElement) ? evt.srcElement : null);
  // Only block Enter key for text inputs within this page's forms (not quick search or other forms)
  if ((evt.keyCode == 13) && (node.type=="text") && node.name!="textinput1") {
    var form = node.form || $(node).closest('form')[0];
    if (form && ["pcform","catform","atform","dtform","eventform","userform"].includes(form.id)) {
      return false;
    }
  }
}
document.onkeypress = stopRKey;

$(document).ready(function(){
  // Color picker for Action Type (moved from inline script)
  var colorBtn = document.getElementById('atcolor_button');
  if (colorBtn) {
    var jsc = new jscolor.color(colorBtn, {valueElement:'atcolor',pickerMode:'HSV',pickerOnfocus:false});
    $(colorBtn).click(function(evt) { jsc.showPicker(); evt.stopPropagation(); });
    $('body').mouseup(function() { jsc.hidePicker(); });
  }
  // Color picker for Donation Type (moved from inline script)
  var colorBtn2 = document.getElementById('dtcolor_button');
  if (colorBtn2) {
    var jsc2 = new jscolor.color(colorBtn2, {valueElement:'dtcolor',pickerMode:'HSV',pickerOnfocus:false});
    $(colorBtn2).click(function(evt2) { jsc2.showPicker(); evt2.stopPropagation(); });
    $('body').mouseup(function() { jsc2.hidePicker(); });
  }
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
        $('#prefecture').addClass('is-loading');
        $.getJSON("ajax_request.php?req=PC&pc="+$('#postalcode').val(), function(data) {
          $('#prefecture').removeClass('is-loading');
          if (data.alert) {
            if (data.alert === 'NOSESSION') {
              alert('<?=_("Your login has timed out - please refresh the page.")?>');
            } else {
              alert(data.alert);
            }
            $('#pctext').hide();
            $('#prefecture,#shikucho,#romaji').val("");
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
  $(document).on('input paste', '#postalcode', function(){ $('#postalcode').keyup(); });
  $('#loadingPC').hide().ajaxStart(function() {$(this).show(); }).ajaxStop(function() { $(this).hide(); });

  $('#pc_upd').click(function() {
    if (validate('pc') === false) return;
    var $prefecture = $('#prefecture');
    $prefecture.addClass('is-loading');
    $.post('ajax_action.php', {
      action:     'PostalCodeSave',
      postalcode: $('#postalcode').val(),
      prefecture: $prefecture.val(),
      shikucho:   $('#shikucho').val(),
      romaji:     $('#romaji').val()
    }, function(data) {
      $prefecture.removeClass('is-loading');
      if (data.alert === 'NOSESSION') {
        alert('<?=_("Your login has timed out - please refresh the page.")?>');
        return;
      }
      if (data.alert || data.error) { alert(data.alert || data.error); return; }
      showStatus(data.message);
    }, 'json').fail(function(jqxhr, textStatus, error) {
      $prefecture.removeClass('is-loading');
      alert('AJAX Error: ' + textStatus + ', ' + error);
    });
  });

// AJAX call for Categories
  $("#catid").change(function(){
    if ($("#catid").val() == "new") {
      $("#category").val("");
      $("#usefor").val("");
      $("#cat_del").prop('disabled', true);
    } else {
      $('#category').addClass('is-loading');
      $.getJSON("ajax_request.php?req=Category&catid="+$('#catid').val(), function(data) {
        $('#category').removeClass('is-loading');
        if (data.alert === "NOSESSION") {
          alert("<?=_("Your login has timed out - please refresh the page.")?>");
        } else {
          $('#category').val(data.category);
          $('#usefor').val(data.usefor);
          $('#cat_del').data('name', data.category).data('percat-count', data.percat_count);
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
      $('#atype').addClass('is-loading');
      $.getJSON("ajax_request.php?req=AType&atypeid="+$('#atypeid').val(), function(data) {
        $('#atype').removeClass('is-loading');
        if (data.alert) {
          alert(data.alert);
        } else {
          $('#atype').val(data.atype);
          $('#atcolor').val(data.atcolor);
          $('#attemplate').val(data.attemplate);
          $("#atcolor_button").css("background-color","#"+data.atcolor);
          $('#at_del').data('name', data.atype).data('action-count', data.action_count);
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
      $('#dtype').addClass('is-loading');
      $.getJSON("ajax_request.php?req=DType&dtypeid="+$('#dtypeid').val(), function(data) {
        $('#dtype').removeClass('is-loading');
        if (data.alert === "NOSESSION") {
          alert("<?=_("Your login has timed out - please refresh the page.")?>");
        } else {
          $('#dtype').val(data.dtype);
          $('#dtcolor').val(data.dtcolor);
          $("#dtcolor_button").css("background-color","#"+data.dtcolor);
          $('#dt_del').data('name', data.dtype).data('donation-count', data.donation_count).data('pledge-count', data.pledge_count);
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
      $('#event').addClass('is-loading');
      $.getJSON("ajax_request.php?req=Event&eventid="+$('#eventid').val(), function(data) {
        $('#event').removeClass('is-loading');
        if (data.alert === "NOSESSION") {
          alert("<?=_("Your login has timed out - please refresh the page.")?>");
        } else {
          $("#active,#usetimes").prop("checked", false);
          $('#event').val(data.event);
          $('#eventstartdate').val(data.eventstartdate);
          $('#eventenddate').val(data.eventenddate);
          if (data.usetimes == 1) $('#usetimes').prop('checked', true);
          $('#remarks').val(data.remarks);
          $('#event_del').data({
            name:               data.event,
            'attendance-count': data.attendance_count,
            'attend-first':     data.attend_first,
            'attend-last':      data.attend_last
          });
          $("#event_del").prop('disabled', false);
        }
      });
    }
  });

// AJAX call for Users
  $("#userid").change(function(){
    if ($("#userid").val() == "new") {
      $("#username, #new_userid, #old_userid, #new_pw1, #new_pw2, #dashboard").val("");
      $("#language").val("<?=$_SESSION['lang']?>");
      $("#admin").prop("checked", false);
      $("#hidedonations").prop("checked", <?=($_SESSION['hidedonations_default']=="yes" ? "true" : "false")?>);
      $("#user_del").prop('disabled', true);
      $("#loginstats").html("");
    } else {
      $('#username').addClass('is-loading');
      $.getJSON("ajax_request.php?req=User&userid="+$('#userid').val(), function(data) {
        $('#username').removeClass('is-loading');
        if (data.alert === "NOSESSION") {
          alert("<?=_("Your login has timed out - please refresh the page.")?>");
        } else {
          $("#admin,#hidedonations").prop("checked", false);
          $('#username').val(data.username);
          $('#new_userid').val(data.userid);
          $('#old_userid').val(data.userid);
          $('#language').val(data.language);
          if (data.admin == 1) $('#admin').prop('checked', true);
          if (data.hidedonations == 1) $('#hidedonations').prop('checked', true);
          $('#dashboard').val(data.dashboard);
          $("#loginstats").html(data.loginstats);
          $('#user_del').data('name', data.username).data('userid', data.userid);
          $("#user_del").prop('disabled', data.userid == '<?=addslashes($_SESSION["userid"])?>');

        }
      });
    }
  });

  $('#cat_add_upd').click(function() {
    if (validate('cat') === false) return;
    var $category = $('#category');
    $category.addClass('is-loading');
    $.post('ajax_action.php', {
      action:   'CategorySave',
      catid:    $('#catid').val(),
      category: $category.val(),
      usefor:   $('#usefor').val()
    }, function(data) {
      $category.removeClass('is-loading');
      if (data.alert === 'NOSESSION') {
        alert('<?=_("Your login has timed out - please refresh the page.")?>');
        return;
      }
      if (data.alert || data.error) { alert(data.alert || data.error); return; }
      selectUpsertOption($('#catid'), data.catid, data.category);
      resetEntityForm($('#catid'));
      showStatus(data.message);
    }, 'json').fail(function(jqxhr, textStatus, error) {
      $category.removeClass('is-loading');
      alert('AJAX Error: ' + textStatus + ', ' + error);
    });
  });
  $('#cat_del').click(function() {
    var name = $(this).data('name'), n = $(this).data('percat-count');
    var msg = '<?=_("Delete category \"%s\"?")?>'.replace('%s', name);
    if (n > 0) msg += '\n\n' + '<?=_("%d people are currently in this category and will lose it.")?>'.replace('%d', n);
    if (!confirm(msg)) return;
    var catid = $('#catid').val();
    var $category = $('#category');
    $category.addClass('is-loading');
    $.post('ajax_action.php', {
      action: 'CategoryDelete',
      catid:  catid
    }, function(data) {
      $category.removeClass('is-loading');
      if (data.alert === 'NOSESSION') {
        alert('<?=_("Your login has timed out - please refresh the page.")?>');
        return;
      }
      if (data.alert || data.error) { alert(data.alert || data.error); return; }
      selectRemoveOption($('#catid'), catid);
      resetEntityForm($('#catid'));
      showStatus(data.message);
    }, 'json').fail(function(jqxhr, textStatus, error) {
      $category.removeClass('is-loading');
      alert('AJAX Error: ' + textStatus + ', ' + error);
    });
  });

  function doActionTypeDelete(atypeid, new_atypeid, prepend, prepend_text) {
    var $atype = $('#atype');
    $atype.addClass('is-loading');
    $.post('ajax_action.php', {
      action:       'ActionTypeDelete',
      atypeid:      atypeid,
      new_atypeid:  new_atypeid,
      prepend:      prepend ? 1 : 0,
      prepend_text: prepend_text
    }, function(data) {
      $atype.removeClass('is-loading');
      if (data.alert === 'NOSESSION') {
        alert('<?=_("Your login has timed out - please refresh the page.")?>');
        return;
      }
      if (data.alert || data.error) { alert(data.alert || data.error); return; }
      selectRemoveOption($('#atypeid'), atypeid);
      resetEntityForm($('#atypeid'));
      showStatus(data.message);
    }, 'json').fail(function(jqxhr, textStatus, error) {
      $atype.removeClass('is-loading');
      alert('AJAX Error: ' + textStatus + ', ' + error);
    });
  }

  $('#at_del_dialog').dialog({
    autoOpen: false,
    modal:    true,
    width:    Math.min(500, $(window).width() - 40),
    buttons: {
      '<?=_("Reassign and Delete")?>': function() {
        var new_atypeid = $('#new_atypeid').val();
        if (!new_atypeid) { alert('<?=_("Please select a type to reassign to.")?>'); return; }
        var prepend      = $('#at_prepend').is(':checked');
        var prepend_text = prepend ? $('#at_prepend_text').val() : '';
        $(this).dialog('close');
        doActionTypeDelete($('#atypeid').val(), new_atypeid, prepend, prepend_text);
      },
      '<?=_("Cancel")?>': function() { $(this).dialog('close'); }
    }
  });

  $('#at_add_upd').click(function() {
    if (validate('at') === false) return;
    var $atype = $('#atype');
    $atype.addClass('is-loading');
    $.post('ajax_action.php', {
      action:     'ActionTypeSave',
      atypeid:    $('#atypeid').val(),
      atype:      $atype.val(),
      atcolor:    $('#atcolor').val(),
      attemplate: $('#attemplate').val()
    }, function(data) {
      $atype.removeClass('is-loading');
      if (data.alert === 'NOSESSION') {
        alert('<?=_("Your login has timed out - please refresh the page.")?>');
        return;
      }
      if (data.alert || data.error) { alert(data.alert || data.error); return; }
      selectUpsertOption($('#atypeid'), data.atypeid, data.atype, {'style': 'background-color:#' + data.atcolor});
      resetEntityForm($('#atypeid'));
      showStatus(data.message);
    }, 'json').fail(function(jqxhr, textStatus, error) {
      $atype.removeClass('is-loading');
      alert('AJAX Error: ' + textStatus + ', ' + error);
    });
  });

  $('#at_del').click(function() {
    var name    = $(this).data('name'), n = $(this).data('action-count');
    var atypeid = $('#atypeid').val();
    if (n == 0) {
      if (!confirm('<?=_("Are you sure you want to delete action type \"%s\"?")?>'.replace('%s', name))) return;
      doActionTypeDelete(atypeid, 0, false, '');
      return;
    }
    var viewLink = '<a href="action_list.php?listtype=Normal&amp;atype[]=' + atypeid +
                   '" target="_blank"><?=_("click here to view actions in a new tab")?></a>';
    var intro = '<?=_('This type has been used on %1$d action records (%2$s). They are important history, so before deleting the type, '.
    'the records need to be reassigned to another type:')?>'
      .replace('%1$d', n).replace('%2$s', viewLink);
    var $body = $('#at_del_dialog_body');
    $body.html('<p>' + intro + '</p>');
    $body.append(
      $('<p class="dlg-choice">').append(
        $('<label>').append(
          '<?=_("Reassign to:")?> ',
          cloneSelectExcept('#atypeid', atypeid, 'new_atypeid')
        )
      )
    );
    $body.append(
      $('<p class="dlg-choice">').html(
        '<label><input type="checkbox" id="at_prepend" checked> <?=_("Prepend this text to action descriptions:")?></label> ' +
        '<input type="text" id="at_prepend_text" style="width:20em" value="">'
      )
    );
    $('#at_prepend_text').val('(' + name + ') ');
    $('#at_del_dialog').dialog('option', 'title', '<?=_("Delete action type \"%s\"?")?>'.replace('%s', name));
    $('#at_del_dialog').dialog('open');
  });

  function doDonationTypeDelete(dtypeid, new_dtypeid, prepend, prepend_text) {
    var $dtype = $('#dtype');
    $dtype.addClass('is-loading');
    $.post('ajax_action.php', {
      action:       'DonationTypeDelete',
      dtypeid:      dtypeid,
      new_dtypeid:  new_dtypeid,
      prepend:      prepend ? 1 : 0,
      prepend_text: prepend_text
    }, function(data) {
      $dtype.removeClass('is-loading');
      if (data.alert === 'NOSESSION') {
        alert('<?=_("Your login has timed out - please refresh the page.")?>');
        return;
      }
      if (data.alert || data.error) { alert(data.alert || data.error); return; }
      selectRemoveOption($('#dtypeid'), dtypeid);
      resetEntityForm($('#dtypeid'));
      showStatus(data.message);
    }, 'json').fail(function(jqxhr, textStatus, error) {
      $dtype.removeClass('is-loading');
      alert('AJAX Error: ' + textStatus + ', ' + error);
    });
  }

  $('#dt_del_dialog').dialog({
    autoOpen: false,
    modal:    true,
    width:    Math.min(500, $(window).width() - 40),
    buttons: {
      '<?=_("Reassign and Delete")?>': function() {
        var new_dtypeid = $('#new_dtypeid').val();
        if (!new_dtypeid) { alert('<?=_("Please select a type to reassign to.")?>'); return; }
        var prepend      = $('#dt_prepend').is(':checked');
        var prepend_text = prepend ? $('#dt_prepend_text').val() : '';
        $(this).dialog('close');
        doDonationTypeDelete($('#dtypeid').val(), new_dtypeid, prepend, prepend_text);
      },
      '<?=_("Cancel")?>': function() { $(this).dialog('close'); }
    }
  });

  $('#dt_add_upd').click(function() {
    if (validate('dt') === false) return;
    var $dtype = $('#dtype');
    $dtype.addClass('is-loading');
    $.post('ajax_action.php', {
      action:  'DonationTypeSave',
      dtypeid: $('#dtypeid').val(),
      dtype:   $dtype.val(),
      dtcolor: $('#dtcolor').val()
    }, function(data) {
      $dtype.removeClass('is-loading');
      if (data.alert === 'NOSESSION') {
        alert('<?=_("Your login has timed out - please refresh the page.")?>');
        return;
      }
      if (data.alert || data.error) { alert(data.alert || data.error); return; }
      selectUpsertOption($('#dtypeid'), data.dtypeid, data.dtype, {'style': 'background-color:#' + data.dtcolor});
      resetEntityForm($('#dtypeid'));
      showStatus(data.message);
    }, 'json').fail(function(jqxhr, textStatus, error) {
      $dtype.removeClass('is-loading');
      alert('AJAX Error: ' + textStatus + ', ' + error);
    });
  });

  $('#dt_del').click(function() {
    var name = $(this).data('name'), nd = $(this).data('donation-count'), np = $(this).data('pledge-count');
    var dtypeid = $('#dtypeid').val();
    if (nd == 0 && np == 0) {
      if (!confirm('<?=_("Are you sure you want to delete donation type \"%s\"?")?>'.replace('%s', name))) return;
      doDonationTypeDelete(dtypeid, 0, false, '');
      return;
    }
    var donLink = '<a href="donation_list.php?listtype=Normal&amp;dtype[]=' + dtypeid +
                  '" target="_blank"><?=_("click here to view donations in a new tab")?></a>';
    var pleLink = '<a href="pledge_list.php?listtype=Normal&amp;closed=yes&amp;dtype[]=' + dtypeid +
                  '" target="_blank"><?=_("click here to view pledges in a new tab")?></a>';
    var intro;
    if (nd > 0 && np > 0) {
      intro = '<?=_('This type has been used on %1$d donations (%2$s) and %3$d pledges (%4$s). They are important history, '.
      'so before deleting the type, the records need to be reassigned to another type:')?>'
        .replace('%1$d', nd).replace('%2$s', donLink).replace('%3$d', np).replace('%4$s', pleLink);
    } else if (nd > 0) {
      intro = '<?=_('This type has been used on %1$d donations (%2$s). They are important history, so before deleting the type, '.
      'the records need to be reassigned to another type:')?>'
        .replace('%1$d', nd).replace('%2$s', donLink);
    } else {
      intro = '<?=_('This type has been used on %1$d pledges (%2$s). They are important history, so before deleting the type, '.
      'the records need to be reassigned to another type:')?>'
        .replace('%1$d', np).replace('%2$s', pleLink);
    }
    var $body = $('#dt_del_dialog_body').empty();
    $body.html('<p>' + intro + '</p>');
    $body.append(
      $('<p class="dlg-choice">').append(
        $('<label>').append(
          '<?=_("Reassign to:")?> ',
          cloneSelectExcept('#dtypeid', dtypeid, 'new_dtypeid')
        )
      )
    );
    $body.append(
      $('<p class="dlg-choice">').html(
        '<label><input type="checkbox" id="dt_prepend" checked> <?=_("Prepend this text to donation descriptions:")?></label> ' +
        '<input type="text" id="dt_prepend_text" style="width:10em" value="">'
      )
    );
    $('#dt_prepend_text').val('(' + name + ') ');
    $('#dt_del_dialog').dialog('option', 'title', '<?=_("Delete donation type \"%s\"?")?>'.replace('%s', name));
    $('#dt_del_dialog').dialog('open');
  });

  function doEventDelete(eventid, mode, new_eventid) {
    var $event = $('#event');
    $event.addClass('is-loading');
    $.post('ajax_action.php', {
      action:      'EventDelete',
      eventid:     eventid,
      mode:        mode,
      new_eventid: new_eventid
    }, function(data) {
      $event.removeClass('is-loading');
      if (data.alert === 'NOSESSION') {
        alert('<?=_("Your login has timed out - please refresh the page.")?>');
        return;
      }
      if (data.alert || data.error) { alert(data.alert || data.error); return; }
      selectRemoveOption($('#eventid'), eventid);
      resetEntityForm($('#eventid'));
      showStatus(data.message);
    }, 'json').fail(function(jqxhr, textStatus, error) {
      $event.removeClass('is-loading');
      alert('AJAX Error: ' + textStatus + ', ' + error);
    });
  }

  $('#event_del_dialog').dialog({
    autoOpen: false,
    modal:    true,
    width:    Math.min(500, $(window).width() - 40),
    buttons: {
      '<?=_("Confirm")?>': function() {
        var mode = $('input[name="event_del_mode"]:checked').val();
        if (mode === 'reassign') {
          var new_eventid = $('#new_eventid').val();
          if (!new_eventid) { alert('<?=_("Please select an event to reassign to.")?>'); return; }
          $(this).dialog('close');
          doEventDelete($('#eventid').val(), 'reassign', new_eventid);
        } else {
          $(this).dialog('close');
          doEventDelete($('#eventid').val(), 'cascade', 0);
        }
      },
      '<?=_("Cancel")?>': function() { $(this).dialog('close'); }
    }
  });

  $('#event_add_upd').click(function() {
    if (validate('event') === false) return;
    var $event = $('#event');
    $event.addClass('is-loading');
    $.post('ajax_action.php', {
      action:         'EventSave',
      eventid:        $('#eventid').val(),
      event:          $event.val(),
      eventstartdate: $('#eventstartdate').val(),
      eventenddate:   $('#eventenddate').val(),
      usetimes:       $('#usetimes').is(':checked') ? 1 : 0,
      remarks:        $('#remarks').val()
    }, function(data) {
      $event.removeClass('is-loading');
      if (data.alert === 'NOSESSION') {
        alert('<?=_("Your login has timed out - please refresh the page.")?>');
        return;
      }
      if (data.alert || data.error) { alert(data.alert || data.error); return; }
      selectUpsertOption($('#eventid'), data.eventid, data.event, {'class': data.active ? 'active' : 'inactive'});
      resetEntityForm($('#eventid'));
      showStatus(data.message);
    }, 'json').fail(function(jqxhr, textStatus, error) {
      $event.removeClass('is-loading');
      alert('AJAX Error: ' + textStatus + ', ' + error);
    });
  });

  $('#event_del').click(function() {
    var name = $(this).data('name'), n = $(this).data('attendance-count');
    var eventid = $('#eventid').val();
    if (n == 0) {
      if (!confirm('<?=_("Are you sure you want to delete event \"%s\"?")?>'.replace('%s', name))) return;
      doEventDelete(eventid, 'cascade', 0);
      return;
    }
    var first = $(this).data('attend-first'), last = $(this).data('attend-last');
    var dateRange = (first === last) ? first : first + ' &ndash; ' + last;
    var viewLink = '<a href="attend_detail.php?startdate=&amp;enddate=&amp;eid=' + eventid +
                   '" target="_blank"><?=_("click here to view attendance in a new tab")?></a>';
    var intro = '<?=_('This event has been used for %1$d attendance records, %2$s (%3$s). You can reassign the attendance records to another event to consolidate, '.
    'or delete them completely. They are history, so it is best not to delete them, but here are your choices:')?>'
      .replace('%1$d', n).replace('%2$s', dateRange).replace('%3$s', viewLink);
    var $body = $('#event_del_dialog_body');
    $body.html('<p>' + intro + '</p>');
    $body.append(
      $('<p class="dlg-choice">').append(
        $('<label>').append(
          $('<input>').attr({type: 'radio', name: 'event_del_mode', id: 'event_del_mode_reassign', value: 'reassign'}).prop('checked', true),
          ' <?=_("Reassign these records to another event:")?> ',
          cloneSelectExcept('#eventid', eventid, 'new_eventid')
        )
      )
    );
    $body.append(
      $('<p class="dlg-choice">').append(
        $('<label>').append(
          $('<input>').attr({type: 'radio', name: 'event_del_mode', value: 'cascade'}),
          ' ' + '<?=_("Delete all %d attendance records along with the event.")?>'.replace('%d', n)
        )
      )
    );
    $('#event_del_dialog').dialog('option', 'title', '<?=_("Delete event \"%s\"?")?>'.replace('%s', name));
    $('#event_del_dialog').dialog('open');
  });

  $('#user_add_upd').click(function() {
    if (validate('user') === false) return;
    var $username = $('#username');
    $username.addClass('is-loading');
    $.post('ajax_action.php', {
      action:        'UserSave',
      userid:        $('#userid').val(),
      old_userid:    $('#old_userid').val(),
      new_userid:    $('#new_userid').val(),
      username:      $username.val(),
      language:      $('#language').val(),
      admin:         $('#admin').is(':checked') ? 1 : 0,
      hidedonations: $('#hidedonations').length ? ($('#hidedonations').is(':checked') ? 1 : 0) : 0,
      dashboard:     $('#dashboard').val(),
      new_pw1:       $('#new_pw1').val(),
      new_pw2:       $('#new_pw2').val()
    }, function(data) {
      $username.removeClass('is-loading');
      if (data.alert === 'NOSESSION') {
        alert('<?=_("Your login has timed out - please refresh the page.")?>');
        return;
      }
      if (data.alert || data.error) { alert(data.alert || data.error); return; }
      var sentOldUserid = $('#old_userid').val();
      selectUpsertOption($('#userid'), data.userid, data.username);
      if (sentOldUserid && sentOldUserid !== data.userid) {
        $('#userid').find('option[value="' + sentOldUserid + '"]').remove();
      }
      if (data.sessionUpdated) { window.location.reload(); return; }
      resetEntityForm($('#userid'));
      $('#new_pw1, #new_pw2').val('');
      showStatus(data.message);
    }, 'json').fail(function(jqxhr, textStatus, error) {
      $username.removeClass('is-loading');
      alert('AJAX Error: ' + textStatus + ', ' + error);
    });
  });

  $('#user_del').click(function() {
    var name = $(this).data('name'), uid = $(this).data('userid');
    if (!confirm('<?=_('Are you sure you want to delete user "%1$s" (UserID: %2$s)?')?>'.replace('%1$s', name).replace('%2$s', uid))) return;
    var $username = $('#username');
    $username.addClass('is-loading');
    $.post('ajax_action.php', {
      action:     'UserDelete',
      old_userid: uid
    }, function(data) {
      $username.removeClass('is-loading');
      if (data.alert === 'NOSESSION') {
        alert('<?=_("Your login has timed out - please refresh the page.")?>');
        return;
      }
      if (data.alert || data.error) { alert(data.alert || data.error); return; }
      selectRemoveOption($('#userid'), uid);
      resetEntityForm($('#userid'));
      showStatus(data.message);
    }, 'json').fail(function(jqxhr, textStatus, error) {
      $username.removeClass('is-loading');
      alert('AJAX Error: ' + textStatus + ', ' + error);
    });
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
    if (document.pcform.prefecture.value == "" || document.pcform.shikucho.value == ""
    || (document.pcform.romaji && document.pcform.romaji.value == "")) {
      alert("<?=_("All fields must be filled in.")?>");
      return false;
    }
    break;
  case "at":
    if (document.atform.atype.value == "") {
      alert("<?=_("Action Type name cannot be blank.")?>");
      return false;
    }
    break;
  case "dt":
    if (document.dtform.dtype.value == "") {
      alert("<?=_("Donation Type name cannot be blank.")?>");
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
<?php footer(); ?>