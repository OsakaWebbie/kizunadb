<?php
include("functions.php");
include("accesscontrol.php");

// Inputs — shared by the processing branch and the (re-)rendered form
$pid_list  = isset($_POST['pid_list']) ? preg_replace('/[^0-9,]/', '', $_POST['pid_list']) : '';
$atid      = isset($_POST['atid']) ? (int)$_POST['atid'] : 0;
$adate_raw = $_POST['adate'] ?? '';
$desc_raw  = $_POST['desc'] ?? '';
$mode      = $_POST['mode'] ?? '';       // '' = initial check, 'unique', or 'all'

$show_dialog  = false;
$partial_info = '';                      // dialog list rows (one per existing partial-overlap action)
$unique = $partial = $perfect = [];      // PersonIDs by category

if (!empty($_POST['process'])) {
  $adate    = h2d($adate_raw);
  $desc_esc = h2d(str_replace("\r", "", $desc_raw));
  $pid_array = array_values(array_filter(array_map('intval', explode(',', $pid_list))));

  // Categorize every selected person without adding anything yet:
  //   unique  = no action of this person+date+type (won't appear in the result at all)
  //   perfect = already has an identical action (skip always)
  //   partial = has this date+type but only different description(s)
  // IsExact uses the DB collation (utf8mb4_unicode_ci = case/accent-insensitive), like
  // ajax_request.php's ActionDup.
  if ($pid_array) {
    $pid_in = implode(',', $pid_array);
    $res = sqlquery_checked(
      "SELECT action.PersonID, person.FullName, action.Description,".
      " (REPLACE(action.Description, CHAR(13), '')='$desc_esc') AS IsExact".
      " FROM action LEFT JOIN person ON action.PersonID=person.PersonID".
      " WHERE action.PersonID IN ($pid_in) AND action.ActionTypeID=$atid AND action.ActionDate='$adate'".
      " ORDER BY action.PersonID");
    $descs_by_pid = [];   // pid => [existing descriptions]
    $name_by_pid  = [];   // pid => FullName
    $has_exact    = [];   // pid => true if an identical action already exists
    while ($row = mysqli_fetch_object($res)) {
      $descs_by_pid[$row->PersonID][] = $row->Description;
      $name_by_pid[$row->PersonID]    = $row->FullName;
      if ($row->IsExact) $has_exact[$row->PersonID] = true;
    }
    foreach ($pid_array as $pid) {
      if (empty($descs_by_pid[$pid])) {
        $unique[] = $pid;
      } elseif (!empty($has_exact[$pid])) {
        $perfect[] = $pid;
      } else {
        $partial[] = $pid;
        foreach ($descs_by_pid[$pid] as $d) {
          $partial_info .= "<tr><td><a href='individual.php?pid=$pid' target='_blank'>".d2h($name_by_pid[$pid])."</a></td>".
                           "<td class='readmore-wrapper'><div class='readmore'>".d2h($d)."</div></td></tr>\n";
        }
      }
    }
  }

  if ($mode === '' && count($partial) > 0) {
    $show_dialog = true;   // ask the user first — falls through to render the pre-filled form + dialog
  } else {
    // Add per the user's choice (or, when there are no partials, just the uniques).
    // Exact duplicates are never added.
    $do_add  = ($mode === 'all') ? array_merge($unique, $partial) : $unique;
    foreach ($do_add as $pid) {
      sqlquery_checked("INSERT INTO action (PersonID,ActionTypeID,ActionDate,Description) VALUES".
                       " ($pid,$atid,'$adate','$desc_esc')");
    }
    $added   = count($do_add);
    $skipped = count($pid_array) - $added;
    echo "<h3>".sprintf(_('%d new records successfully added.'), $added)."</h3>\n";
    if ($skipped > 0) {
      echo "<p>".sprintf(_('%d not added due to pre-existing records.'), $skipped)."</p>\n";
    }
    exit;
  }
}
?>
  <h1><?=_("Add an Action for All")?></h1>
  <h3><?=_('Choose action type and date, and fill in a description if desired:')?></h3>
  <p class="comment"><?=_('Exact duplicates (same person, date, type, and description) are skipped automatically.')?></p>
  <form action="<?=$_SERVER['PHP_SELF']?>" method="post" name="actionform" id="actionform" onsubmit="return validate();">
    <input type="hidden" name="pid_list" value="<?=htmlspecialchars($pid_list, ENT_QUOTES)?>">
    <input type="hidden" name="process" value="1">
    <input type="hidden" name="mode" id="confirm-mode" value="">
    <label class="label-n-input"><?=_("Action Type")?>: <select id="atid" name="atid" size="1">
      <option value=""<?=$atid ? '' : ' selected'?>><?=_('Select...')?></option>
<?php
$result = sqlquery_checked("SELECT * FROM actiontype ORDER BY ActionType");
while ($row = mysqli_fetch_object($result)) {
  echo '      <option value="'.$row->ActionTypeID.'"'.(($atid && $row->ActionTypeID == $atid) ? ' selected' : '').'>'.d2h($row->ActionType)."</option>\n";
}
?>
    </select></label>
    <label class="label-n-input"><?=_("Date")?>: <input type="text" id="adate" name="adate" value="<?=htmlspecialchars($adate_raw, ENT_QUOTES)?>" style="width:6em"></label>
    <label class="label-n-input"><?=_("Description")?>: <textarea id="desc" name="desc" style="height:4em;width:30em"><?=htmlspecialchars($desc_raw, ENT_QUOTES)?></textarea></label>
    <input type="submit" value="<?=_("Save Action Info")?>">
  </form>
<?php
if ($show_dialog) {
  $tn = sqlquery_checked("SELECT ActionType FROM actiontype WHERE ActionTypeID=$atid");
  $typeName = ($tr = mysqli_fetch_object($tn)) ? d2h($tr->ActionType) : '';
  $date_h   = htmlspecialchars($adate_raw, ENT_QUOTES);
  $nUnique  = count($unique);
  $nPartial = count($partial);
  $nPerfect = count($perfect);
  $nTotal   = $nUnique + $nPartial + $nPerfect;
  if ($nPerfect > 0) {
    // Wording is the same whether or not there are uniques — only the buttons below differ.
    $dialog_msg = sprintf(_('Of the %1$d total selected, %2$d already have an identical Action and will be skipped. Additionally, the %3$d listed below have one or more Actions with type "%4$s" on %5$s but a different description. What do you want to do?'),
                          $nTotal, $nPerfect, $nPartial, $typeName, $date_h);
  } elseif ($nUnique > 0) {
    $dialog_msg = sprintf(_('Of the %1$d total selected, the %2$d listed below already have one or more Actions with type "%3$s" on %4$s but a different description. What do you want to do?'),
                          $nTotal, $nPartial, $typeName, $date_h);
  } else {
    $dialog_msg = sprintf(_('All %1$d already have one or more Actions with type "%2$s" on %3$s but a different description, as seen below. What do you want to do?'),
                          $nTotal, $typeName, $date_h);
  }
?>
<div id="batch-dup-dialog" style="display:none">
  <p><?=$dialog_msg?></p>
  <div class="dialog-choices" style="margin: 8px 0">
    <button type="button" id="dup-cancel"><?=_('Cancel')?></button>
<?php if ($nUnique > 0) { ?>
    <button type="button" id="dup-unique"><?=sprintf(_('Add only unique (%d)'), $nUnique)?></button>
<?php } ?>
    <button type="button" id="dup-all"><?=sprintf(_('Add all (%d)'), $nUnique + $nPartial)?></button>
  </div>
  <p class="comment"><?=_('(Click a name to view in a new tab)')?></p>
  <table class="tablesorter">
    <thead><tr><th class="no-arrows"><?=_('Name')?></th><th class="no-arrows"><?=_('Description')?></th></tr></thead>
    <tbody>
<?=$partial_info?>
    </tbody>
  </table>
</div>
<?php } ?>

<script>
$(function(){
  $("#actionform input[type=submit]").button();
  $(document).ajaxError(function(e, xhr, settings, exception) {
    alert('Error calling ' + settings.url + ': ' + exception);
  });
  $("#adate").datepicker({ dateFormat: 'yy-mm-dd' });
  if ($("#adate").val()=="") $("#adate").datepicker('setDate', new Date());

  $("#atid").change(function(){  //insert template text in Action description when applicable ActionType is selected
    if (!$.trim($("#desc").val())) {
      $("#desc").load("ajax_request.php",{'req':'ActionTemplate','atid':$("#atid").val()}, function() {
        $(this).change();
      });
    }
  });

  if ($("#batch-dup-dialog").length) {
    $("#batch-dup-dialog").dialog({
      modal: true,
      width: Math.min(700, $(window).width() - 30),
      title: "<?=_('Some already have a similar action')?>",
      close: function(){ $(this).dialog('destroy').remove(); },
      open: function(){
        $(this).find(".dialog-choices button").button();
        $(this).find(".readmore").readmore({
          speed: 75,
          collapsedHeight: 50,
          heightMargin: 0,
          moreLink: '<a href="#"><?=_("[Read more]")?></a>',
          lessLink: '<a href="#"><?=_("[Close]")?></a>'
        });
      }
    });
    $("#dup-cancel").on("click", function(){ $("#batch-dup-dialog").dialog('close'); });
    $("#dup-unique").on("click", function(){ batchConfirm('unique'); });
    $("#dup-all").on("click",    function(){ batchConfirm('all'); });
  }
});

// A dialog button was chosen: stamp the mode and resubmit the (still-filled) form.
function batchConfirm(mode){
  $("#confirm-mode").val(mode);
  $("#batch-dup-dialog").dialog('close');
  $("#actionform").trigger('submit');
}

function validate() {
  if (document.actionform.atid.value == "") {
    alert("<?=_("Please select an Action Type.")?>");
    return false;
  }
  return true;
}
</script>