<?php
include("functions.php");
include("accesscontrol.php");

if (!empty($_GET['pid'])) {
  $pid = $_GET['pid'];
  $sql = "SELECT p.*, h.*, pc.*, p.Photo AS PPhoto FROM person p LEFT JOIN household h ".
      "ON p.HouseholdID=h.HouseholdID LEFT JOIN postalcode pc ON h.PostalCode=".
      "pc.PostalCode WHERE PersonID=$pid";
  $result = sqlquery_checked($sql);
  if (mysqli_num_rows($result) == 0) {
    printf(_("Failed to find a record for PersonID %s."),$pid);
    exit;
  }
  $rec = mysqli_fetch_object($result);
  
//DETERMINE IF THERE ARE MULTIPLE MEMBERS OF THE HOUSEHOLD
  if ($rec->HouseholdID) {
    $sql = "SELECT count(*) count FROM person WHERE HouseholdID=$rec->HouseholdID";
    $result = sqlquery_checked($sql);
    $hh = mysqli_fetch_object($result);
  }
} else {
  $pid = 0;
  // new member to be added to existing household
  if (!empty($_GET['hhid'])) {
    $sql = "SELECT h.* FROM household h LEFT JOIN postalcode pc ON h.PostalCode=" .
        "pc.PostalCode WHERE HouseholdID=" . $_GET['hhid'];
    $result = sqlquery_checked($sql);
    if (mysqli_num_rows($result) == 0) {
      echo("<b>Failed to find a record for HouseholdID " . $_GET['hhid'] . ".</b>");
      exit;
    }
    $rec = mysqli_fetch_object($result);
  }
}

if ($pid) {
  header1(sprintf(_("Edit %s"),$rec->FullName));
} else {
  header1(_("New Entry"));
}
?>
<link rel="stylesheet" href="style.php?jquery=1" type="text/css" />
<script type="text/javascript" src="js/functions.js"></script>
<script type="text/JavaScript" src="js/jquery.js"></script>
<script type="text/JavaScript" src="js/jquery-ui.js"></script>

<script type="text/javascript">

function stopRKey(evt) {
  var evt = (evt) ? evt : ((event) ? event : null);
  var node = (evt.target) ? evt.target : ((evt.srcElement) ? evt.srcElement : null);
  if ((evt.keyCode == 13) && (node.type=="text"))  {return false;}
}
document.onkeypress = stopRKey;

pc_regexp = /^\d\d\d-\d\d\d\d$/;
jpg_regexp = /\.[Jj][Pp][Gg]$/;
bday_regexp = /^\d{1,2}-\d{1,2}$/;
bdate_regexp = /^\d\d\d\d-\d{1,2}-\d{1,2}$/;
phone_regexp = /^[\d-+\(\)Xx\* ]*$/;

$(document).ready(function(){
  var oldPostalCode = '';
  $('#postalcode').keyup(function(){  //fill other fields when applicable Postal Code is typed
    var newPostalCode = $('#postalcode').val();
    if (newPostalCode != oldPostalCode) {
      oldPostalCode = newPostalCode;
      if (pc_regexp.test($('#postalcode').val())) {
        $.ajax({
          type: "GET",
          url: "get_postalcode.php",
          data: "pc="+$("#postalcode").val()+"&aux=1",
          dataType: "json",
          error: function(x, y, z) { alert("AJAX Error: "+y); $('#postalcode').blur();},
          success: function(data, status, z) {
            if (data.alert === "NOSESSION") {
              alert("<?=_("Your session has timed out - please refresh the page.")?>");
            } else if (data.alert != "PCNOTFOUND")  {
              $('#postalcode_display').text('〒' + $('#postalcode').val());
              $('#pctext_display').text(data.pref + data.shi);
              $('#prefecture').val(data.pref);
              $('#shikucho').val(data.shi);
              if ($('#fullname').val()!='' && $('#labelname').val()=='') {
                $('#labelname').val(($('#fullname').val()+$('#nametitle').val())).keyup();
              }
              $('#address').focus();
<?php if ($_SESSION['romajiaddresses'] == "yes") { ?>
              $('#pcromtext_display').html(d2h(data.rom));
              $('#pcrom_display').text($('#postalcode').val());
              if (data.fromaux) {
                $('#pcromtext_section').show();
                $('#pcromtext').val('').focus();
                $('#pctext_display').addClass('highlight');
              }
<?php } ?>
            }
          }
        });
      } else if ($('#postalcode_display').text() != '') {
        $('#postalcode_display').text('');
        $('#pctext_display').text('');
        $('#prefecture').val('');
        $('#shikucho').val('');
<?php if ($_SESSION['romajiaddresses'] == "yes") { ?>
        $('#pcromtext_display').text('');
        $('#pcrom_display').text('');
        $('#pcromtext_section').hide();
        $('#pcromtext_display').removeClass('highlight');
<?php } ?>
      }
    }
  });
  $('#postalcode').live('input paste',function(){ $('#postalcode').keyup(); });

  $('#mirror_address').change(function(){
    if ($(this).prop('checked')) {
      $('#romajiaddress').attr('readonly',true);
      $('#romajiaddress').val(($('#address').val()));
      $('#banchirom_display').html(d2h($('#romajiaddress').val()));
    } else {
      $('#romajiaddress').attr('readonly',false);
    }
  });

  $('#address').keyup(function(){
    $('#banchi_display').html(d2h($('#address').val()));
    if ($('#mirror_address').prop('checked')) {
      $('#romajiaddress').val(($('#address').val()));
      $('#banchirom_display').html(d2h($('#romajiaddress').val()));
    }
  });
  $('#address').live('input paste',function(){ $('#address').keyup(); });

  $('#romajiaddress').keyup(function(){ $('#banchirom_display').html(d2h($('#romajiaddress').val())); });
  $('#romajiaddress').live('input paste',function(){ $('#romajiaddress').keyup(); });
  
  $('#pcromtext').keyup(function(){ $('#pcromtext_display').html(d2h($('#pcromtext').val())); });
  $('#pcromtext').live('input paste',function(){ $('#pcromtext').keyup(); });
  
  $('#labelname').keyup(function() {
    $('#labelname_display').html(d2h($('#labelname').val()));
    $('#labelname_nonjapan_display').html(d2h($('#labelname').val()));
  });
  $('#labelname').live('input paste',function(){ $('#labelname').keyup(); });

  $('#organization').change(function() {
    if ($('#organization').is(':checked')) {
      if ($('#nametitle').val()=='様') {
        $('#nametitle').val('御中');
        if ($('#labelname').val() == $('#fullname').val()+'様') {
          if (confirm('<?=_('Should I also change end of label name to "御中"?')?>')) {
            $('#labelname').val($('#fullname').val()+'御中').keyup();
          }
        }
      }
    } else {
      if ($('#nametitle').val()=='御中') {
        $('#nametitle').val('様');
        if ($('#labelname').val() == $('#fullname').val()+'御中') {
          if (confirm('<?=_('Should I also change end of label name to "様"?')?>')) {
            $('#labelname').val($('#fullname').val()+'様').keyup();
          }
        }
      }
    }
  });
  $("#duplicates").dialog({
    autoOpen: false,
    resizable: false,
    modal: true,
    width: "auto",
    title: "<?=_('Possible Duplicates')?>"
  });
  cleanhhview();
  document.editform.fullname.focus();
});

function newhh() {
  $('#household_section input').val("");
  $('#household_section textarea').val("");
  if (document.editform.nonjapan.checked) {
    document.editform.nonjapan.checked=false;
    check_nonjapan();
  }
  $('#relation').val("Main");
  document.editform.updateper.value=1;
  document.editform.updatehh.value=0;
  cleanhhview();
}

function check_nonjapan() {
  if(document.editform.nonjapan.checked) {
    $("#address").height("5em").focus();
    $(".japanonly").hide();
    $(".nonjapanonly").show();
    if ($('#fullname').val()!='' && $('#labelname').val()=='') {
      $('#labelname').val(($('#fullname').val())).keyup();
    }
  } else {
    $(".japanonly").show();
    $("#postalcode").focus();
    $("#address").height("3em");
    $(".nonjapanonly").hide();
  }
}

function cleanhhview() {
  check_nonjapan();
  $('#postalcode').keyup();
  $('#address').keyup();
  $('#romajiaddress').keyup();
  $('#labelname').keyup();
}

function validate() {
  f = document.editform;  //just an abbreviation
  f.edit.disable = true;  //to prevent double submit

  //if ((f.updateper.value == 0) && (f.updatehh.value == 0)) {
    //alert("<?=_("No info was modified.  If you want to exit this page, just use your BACK button.")?>");
    //return false;
  //}
  if (f.fullname.value.length == 0) {
    alert("<?=_("Please enter the name!")?>");
    f.fullname.select();
    return false;
  }
  
  if (f.furigana.value.length == 0) {
    alert("<?php printf(_("Please fill in %s field.  For non-Japanese names, just repeat the name with last name first."),
    ($_SESSION['furiganaisromaji']=="yes" ? _("Romaji") : _("Furigana"))); ?>");
    f.furigana.select();
    return false;
  }

  /* clean up field contents */
  f.fullname.value = trim(fixchartypes(f.fullname.value));
  f.furigana.value = hiragana2katakana(trim(fixchartypes(f.furigana.value)));
  f.furigana.value = f.furigana.value.replace(/([ァ-ヶ]+) ([ァ-ヶ]+)/,"$1　$2");
  f.address.value = trim(fixchartypes(f.address.value));
  if (f.romajiaddress !== undefined) f.romajiaddress.value = trim(fixchartypes(f.romajiaddress.value));
  if (f.pcromaji !== undefined) f.pcromaji.value = trim(fixchartypes(f.pcromaji.value));
  f.remarks.value = trim(f.remarks.value);

  if (!document.editform.nonjapan.checked && $('#postalcode').val().length > 0) {
    if (!pc_regexp.test($('#postalcode').val())) {
      alert("<?=_("Postal Code must be in the form 999-9999.")?>");
      $('#postalcode').focus();
      return false;
    } else if ($('#postalcode_display').text() == "") {
      alert("<?=_("Postal code not found in post office database.")?>");
      $('#postalcode').focus();
      return false;
    }
    if ($('#address').val().length == 0) {
      alert("<?=_("Please complete the address.")?>");
      $('#address').focus();
      return false;
    }
<?php if ($_SESSION['romajiaddresses']=="yes") { ?>
    if ($('#romajiaddress').val().length == 0) {
      alert("<?=_("Please complete the romaji address.")?>");
      $('#address').focus();
      return false;
    }
    if ($('#pcromtext_display').text() == '' && $('#pcromtext').text() == '') {
      alert("<?=_("Please fill in romaji for the postalcode-related text.")?>");
      $('#address').focus();
      return false;
    }
<?php } //if romaji addresses ?>
  }
  if ((f.photofile.value) && (!jpg_regexp.test(f.photofile.value))) {
    alert("<?=_("Only JPG files can be accepted for photos.")?>");
    f.photofile.value = "";
    return false;
  }

<?php if ($pid && !empty($hh) && $hh->count > 1) { ?>
  if ((f.householdid.value) && (f.householdid.value == f.orig_hhid.value) && (f.updatehh.value==1)) {
    if (!confirm('<?=sprintf(_("There are %s members in this household - changing this info will affect them all."),$hh->count).
    _(" Do you want to continue? (If just this person has moved out, cancel and then select New Household.)")?>')) {
      return false;
    }
  }
<?php } //if household with multiple members ?>
  if (f.phone.value && !phone_regexp.test(f.phone.value)) {
    f.phone.value = fixchartypes(f.phone.value);
    if (f.phone.value && !phone_regexp.test(f.phone.value)) {
      alert("<?=_("Phone number can only include numbers, -, +, (), X (extension), and * (for footnote - explain in Remarks).")?>");
      f.phone.select();
      return false;
    }
  }
  if (f.fax.value && !phone_regexp.test(f.fax.value)) {
    f.fax.value = fixchartypes(f.fax.value);
    if (f.fax.value && !phone_regexp.test(f.fax.value)) {
      alert("<?=_("FAX number can only include numbers, -, +, (), X (extension), and * (for footnote - explain in Remarks).")?>");
      f.fax.select();
      return false;
    }
  }
  if (f.cellphone.value && !phone_regexp.test(f.cellphone.value)) {
    f.cellphone.value = fixchartypes(f.cellphone.value);
    if (f.cellphone.value && !phone_regexp.test(f.cellphone.value)) {
      alert("<?=_("Cell Phone number can only include numbers, -, +, and ().")?>");
      f.cellphone.select();
      return false;
    }
  }
  if (f.birthdate.value && !bdate_regexp.test(f.birthdate.value)) {
    if (bday_regexp.test(f.birthdate.value)) {
      f.birthdate.value = "1900-"+f.birthdate.value;
    } else {
      alert("<?=_("Birthdate must be in the form of either YYYY-MM-DD or MM-DD.")?>");
      f.birthdate.select();
      return false;
    }
  }
<?php if (!$pid) {  /* if new entry, check for duplicates */ ?>
  if ($("#duplicates").html() != $("#fullname").val()+"/"+$("#furigana").val()+"/"+$("#postalcode").val()+"/"+
  $("#cellphone").val()+"/"+$("#email").val()+"/"+"CHECKED") {
    $("#duplicates").load("get_duplicates.php",{
      'fullname':$("#fullname").val(),
      'furigana':$("#furigana").val(),
      'postalcode':$("#postalcode").val(),
      'cellphone':$("#cellphone").val(),
      'email':$("#email").val()
    },function(response, status, xhr) {
      switch(status) {
      case "error":
      case "timeout":
        if (!confirm("<?php
        echo _("There was an error checking for similar existing entries (to avoid duplicates). ".
        "Continue anyway? (To try checking again, click Cancel and then Save Changes again.)");
        ?> ("+xhr.status+" "+xhr.statusText+")")) return false;
      case "parsererror":
        alert("Karen: there was a parse error when checking for duplicates");
        return false;
        break;
      default:
        if ($("#duplicates").html() == "NODUPS") {
          $("#duplicates").html($("#fullname").val()+"/"+$("#furigana").val()+"/"+$("#postalcode").val()+"/"+
          $("#cellphone").val()+"/"+$("#email").val()+"/"+"CHECKED");
          $("#editform").submit();
        } else {
          $("#duplicates").dialog("open");
          $("#duplicates #continue").click(function(){
            $("#duplicates").dialog("close");
            $("#duplicates").html($("#fullname").val()+"/"+$("#furigana").val()+"/"+$("#postalcode").val()+"/"+
            $("#cellphone").val()+"/"+$("#email").val()+"/"+"CHECKED");
            $("#editform").submit();
          });
        }
      }
    });
    return false;
  }
<?php } /* end of if not pid */ ?>
  return true;  //everything is cool
}
</script>
<?php
header2(1);
echo "<h1 id=\"title\">".($pid ? sprintf(_("Edit %s"),$rec->FullName) : _("New Entry"))."</h1>\n";
?>
<div id="duplicates" style="display:none"></div>
<form name="editform" id="editform" enctype="multipart/form-data" method="post" action="do_edit.php" onsubmit="return validate();">
<input type="hidden" name="pid" id="pid" value="<?=$pid?>" />
<input type="hidden" name="MAX_FILE_SIZE" value="5000000" />
<input type="hidden" name="prefecture" id="prefecture" value="" />
<input type="hidden" name="shikucho" id="shikucho" value="" />
<input type="hidden" name="updateper" value="0" />
<input type="hidden" name="updatehh" value="0" />
<div id="name_section">
  <span class="label-n-input"><label for="fullname"><?=_("Full Name")?>: </label>
  <input name="fullname" id="fullname" type="text" style="width:15em" maxlength="100" value="<?=$pid?$rec->FullName:''?>"
  onchange="editform.updateper.value=1;" style="ime-mode:auto;" /></span><br />
  <span class="label-n-input"><input type="checkbox" name="organization" id="organization"
  <?php if (!empty($rec->Organization)) echo " checked"; ?> onchange="editform.updateper.value=1;"><label for="organization"><?php
  echo _("Organization (church, company, etc.)"); ?></label></span>
</div>

<div id="furigana_section">
  <label class="label-n-input"><?=($_SESSION['furiganaisromaji']=='yes' ? _('Romaji') : _('Furigana'))?>:
    <input name="furigana" id="furigana" type="text" style="width:15em;ime-mode:<?=
    ($_SESSION['furiganaisromaji']=='yes' ? 'disabled' : 'auto')?>" maxlength="100"
           value="<?=$pid?$rec->Furigana:''?>" onchange="editform.updateper.value=1;" />
  </label><br />
  <span class="comment"><?=($_SESSION['furiganaisromaji']=='yes') ?
        _('("Last name, first name" - don\'t forget the comma!)') :
        _('(for non-Japanese names: full name with last name first, for sorting)')?></span>
</div>

<div id="title_section">
  <label class="label-n-input"><?=_("Title")?>:
    <input name="title" id="nametitle" type="text" style="width:3em;ime-mode:auto;" maxlength="6"
    value="<?=(!empty($rec->Title) ? $rec->Title : "様")?>" onchange="editform.updateper.value=1;" />
  </label>
</div>

<div id="household_section">
  <input type="hidden" name="householdid" value="<?=!empty($rec->HouseholdID)?$rec->HouseholdID:''?>">
  <input type="hidden" name="orig_hhid" value="<?=!empty($rec->HouseholdID)?$rec->HouseholdID:''?>">
  <div id="household_setup">
    <button id="existing_hh" type="button"
    onclick="window.open('selecthh.php?fullname='+document.editform.fullname.value+
    '&furigana='+document.editform.furigana.value,'selecthh','scrollbars=yes,width=750,height=600');">
    <?=_("Select An Existing Household")?></button>
    <?php
    if (!empty($rec->HouseholdID)) {
      echo "<button id=\"new_hh\" type=\"button\" onclick=\"newhh();\" tabindex=\"0\">".
      _("New Household")."</button>\n";
    }
    ?>
  </div>
  <div id="address_section">
    <div id="address_display">
      <div id="jp_address_display">
        <span id="labelname_nonjapan_display" class="nonjapanonly"></span><span class="nonjapanonly"><br /></span>
        <span class="japanonly"><span id="postalcode_display"></span>&nbsp;<span id="pctext_display">
        </span>&nbsp;</span><span id="banchi_display"></span>
        <span class="japanonly"><br />
        <span id="labelname_display"></span></span>
      </div>
<?php if ($_SESSION['romajiaddresses'] == "yes") { ?>
      <div id="pcromtext_section" style="display:none">
        <label class="japanonly" for="pcromtext" id="pcromtextlabel">
          <?=_("Romaji for PostalCode-related text (<span class=\"highlight\">highlighted</span> above)")?>:<br />
          <textarea name="pcromtext" id="pcromtext" style="height:2.2em;width:300px;ime-mode:disabled;"
          onchange="editform.updatehh.value=1;"><?=!empty($rec->HouseholdID)?$rec->Romaji:''?></textarea>
        </label>
        <div class="comment"><?=_("(Community/town name on first line, then ward, city, etc. in reverse order)")?></div>
      </div>
      <div id="rom_address_display" class="japanonly">
        <span id="banchirom_display"></span>&nbsp;<span id="pcromtext_display"></span>&nbsp;<span id="pcrom_display"></span>
      </div>
<?php } ?>
    </div>
    <div id="address_input">
      <label for="nonjapan">
        <input type="checkbox" name="nonjapan"
        <?php if (!empty($rec->NonJapan)) echo " checked"; ?> onclick="check_nonjapan();" onchange="editform.updatehh.value=1;" tabindex="0" /><?=_("Non-Japan Address")?>
      </label><br />
      <label for="postalcode" class="japanonly"><?=_("Postal Code")?>:
        <input name="postalcode" id="postalcode" type="text" style="width:5em;ime-mode:disabled;" maxlength="8"
        value="<?=!empty($rec->HouseholdID)?$rec->PostalCode:''?>" onchange="editform.updatehh.value=1;" />
        <span class="comment">(<a href="<?=_("https://yubin.senmon.net/en/index.html")?>" target="_blank"><?=_("Lookup")?></a>)</span><br>
      </label>
      <label for="address" id="addresslabel">
        <span class="japanonly"><?=_("Rest of Address")?></span><span
        class="nonjapanonly"><?=_("Address")?></span>:<br />
        <textarea name="address" id="address" style="height:3em;width:300px;ime-mode:auto;"
        onchange="editform.updatehh.value=1;"><?=!empty($rec->HouseholdID)?$rec->Address:''?></textarea>
      </label>
      <label for="labelname" id="labelnamelabel"><?=_("Label Name")?>:<br />
        <textarea name="labelname" id="labelname" style="height:3em;width:300px;ime-mode:auto;"
        onchange="editform.updatehh.value=1;"><?=!empty($rec->HouseholdID)?$rec->LabelName:''?></textarea>
      </label>
<?php if ($_SESSION['romajiaddresses'] == "yes") { ?>
      <span id="romajiaddress_section">
        <label for="romajiaddress" id="romajiaddresslabel" class="japanonly"><?=_("Romaji rest of address")?>:</label>
        <label class="label-n-input japanonly" style="margin-left:2em"><input type="checkbox" name="mirror_address" id="mirror_address"
        <?=((!$pid || $rec->Address==$rec->RomajiAddress)?"checked=\"checked\"":"")?>><?=_("Mirror Japanese address")?></label><br>
        <textarea name="romajiaddress" id="romajiaddress" class="japanonly" style="height:2.2em;width:300px;ime-mode:disabled;"
        onchange="editform.updatehh.value=1;"
        <?=(!$pid || $rec->Address==$rec->RomajiAddress)?" readonly=\"readonly\"":""?>><?=!empty($rec->HouseholdID)?$rec->RomajiAddress:''?></textarea>
      </span>
<?php } ?>
    </div>
    <div id="householdfinal_section">
      <label for="phone" class="label-n-input"><?=_("Landline Phone")?>: <input name="phone"
      type="tel" style="width:10em;ime-mode:disabled;" maxlength="20" value="<?=!empty($rec->HouseholdID)?$rec->Phone:''?>"
      onchange="editform.updatehh.value=1;" /></label>
      <label for="fax" class="label-n-input"><?=_("FAX")?>: <input name="fax"
      type="tel" style="width:10em;ime-mode:disabled;" maxlength="20" value="<?=!empty($rec->HouseholdID)?$rec->FAX:''?>"
      onchange="editform.updatehh.value=1;" /></label>
      <label for="relation" class="label-n-input"><?=_("This person's relation to household")?>: <select
      name="relation" id="relation" size="1" onchange="editform.updateper.value=1;"><option
      value="Main"<?php if ($pid && $rec->Relation=="Main") echo " selected"; ?>><?=_("Main Member")?></option><option
      value="Spouse"<?php if ($pid && $rec->Relation=="Spouse") echo " selected"; ?>><?=_("Spouse")?></option><option
      value="Child"<?php if ($pid && $rec->Relation=="Child") echo " selected"; ?>><?=_("Child")?></option><option
      value="Parent"<?php if ($pid && $rec->Relation=="Parent") echo " selected"; ?>><?=_("Parent")?></option><option
      value="Other"<?php if ($pid && $rec->Relation=="Other") echo " selected"; ?>><?=_("Other Member")?></option></select></label>
    </div>
  </div>
</div>

<label for="cellphone" class="label-n-input"><?=_("Cell Phone")?>: <input id="cellphone" name="cellphone"
type="tel" style="width:10em;ime-mode:disabled;" maxlength="20" value="<?=$pid?$rec->CellPhone:''?>"
onchange="editform.updateper.value=1;" /></label>

<label for="email" class="label-n-input"><?=_("Email")?>: <input id="email" name="email"
type="email" style="width:25em;ime-mode:disabled;" maxlength="70" value="<?=$pid?$rec->Email:''?>"
onchange="editform.updateper.value=1;" /></label>

<label for="sex" class="label-n-input"><?=_("Sex")?>: <select name="sex" size="1"
onchange="editform.updateper.value=1;"><option></option><option
value="F"<?php if ($pid && $rec->Sex=="F") echo " selected"; ?> ><?=_("Female")?></option><option
value="M"<?php if ($pid && $rec->Sex=="M") echo " selected"; ?> ><?=_("Male")?></option></select></label>

<label for="birthdate" class="label-n-input"><?=_("Birthdate")?>: <input name="birthdate"
type="text" style="width:6em;ime-mode:disabled;" maxlength="10"
value="<?php if ($pid && $rec->Birthdate != "0000-00-00") echo str_replace("1900-","",$rec->Birthdate); ?>"
onchange="editform.updateper.value=1;" /><span
class="comment">&lt;--&nbsp;<?=_("YYYY-MM-DD, or just MM-DD if year unknown")?></span></label>

<label for="URL" class="label-n-input"><?=_("URL")?>: <input name="URL" type="url"
style="width:30em;ime-mode:auto;" maxlength="150" value="<?=$pid?$rec->URL:''?>"
onchange="editform.updateper.value=1;" /></label>

<label for="country" class="label-n-input"><?=_("Home Country")?>: <input name="country"
type="text" style="width:10em;ime-mode:auto;" maxlength="30" value="<?=$pid?$rec->Country:''?>"
onchange="editform.updateper.value=1;" /></label>

<label for="photofile" class="label-n-input"><?=_("Upload photo")?>: <input name="photofile" id="photofile"
type="file" accept=".jpg,.jpeg" style="width:20em" onchange="editform.updateper.value=1;" />
<button type="button" id="photoclear"><?=_('Clear file')?></button>
<?php
$photoimg = ($pid && $rec->PPhoto) ? '<img src="photo.php?f=p'.$pid.'" height="50"/>' : '';
if ($pid && $rec->PPhoto) echo "<span class=\"comment\">".
_("(photo already exists, but you can replace it if you want)")."</span>"; ?></label>
<output id="thumbnail"><?=(($pid && $rec->PPhoto)?'<img src="photo.php?f=p'.$pid.'" height="50"/>':'')?></output>
<script> /* http://www.onlywebpro.com/2012/01/24/create-thumbnail-preview-of-images-using-html5-api/ */
  if (window.FileReader) {
    function handleFileSelect(evt) {
      var files = evt.target.files;
      var f = files[0];
      var reader = new FileReader();
      reader.onload = (function() {
        return function(e) {
          document.getElementById('thumbnail').innerHTML = ['<img src="', e.target.result,'" height="50"/>'].join('');
        };
      })(f);
      reader.readAsDataURL(f);
    }
  } else {
    <?=($_SESSION['userid']=='dev' ? "alert('This browser does not support FileReader');" : '')?>
  }
  document.getElementById('photofile').addEventListener('change', handleFileSelect, false);
  $(document).ready(function(){
    $("#photoclear").click(function(){
      $("#photofile").val('');
      document.getElementById('thumbnail').innerHTML = '<?=$photoimg?>';
    });
  });
</script>
<br>
<input type="submit" name="edit" id="submit_button" value="<?=_("Save Changes")?>" />
<label for="remarks" id="remarks_label"><?=_("Remarks")?>: <textarea name="remarks" id="remarks"
onchange="editform.updateper.value=1;"><?=$pid?$rec->Remarks:''?></textarea></label>
</form>

<?php
print_footer();
?>
