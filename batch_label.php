<?php
include("functions.php");
include("accesscontrol.php");
$ajax = !empty($_REQUEST['ajax']);

if (!$ajax) pageheader(_("Print Labels"), 0);

/* CHECK FOR RECORDS WITH NO HOUSEHOLD OR ADDRESS */
$sql = "SELECT p.PersonID, FullName, Furigana ".
    "FROM person p LEFT JOIN household h ON p.HouseholdID=h.HouseholdID ".
    "WHERE p.PersonID IN (".$pid_list.") AND (p.HouseholdID=0 OR h.Address='' ".
    "OR (h.NonJapan=0 AND h.PostalCode='')) ORDER BY FIND_IN_SET(PersonID,'".$pid_list."')";
$result = sqlquery_checked($sql);
if ($num = mysqli_num_rows($result) > 0) {
  echo "<div style=\"float:left;border:2px solid darkred;padding:4px;margin:4px\">"._("The following entries have no address:")."<br />\n";
  echo "<span style=\"font-size:0.8em\">"._("(They will not be printed unless you click on<br />each to add addresses before continuing.)")."</span>\n";
  while ($row = mysqli_fetch_object($result)) {
    echo "<br>&nbsp;&nbsp;&nbsp;";
    echo "<a href=\"individual.php?pid=".$row->PersonID."\" target=\"_blank\">";
    echo readable_name($row->FullName, $row->Furigana)."</a>\n";
  }
  echo "</div>\n";
}
/* GET NUMBERS OF ENTRIES THAT WOULD BE PRINTED */
$sql = "SELECT count(PersonID) num FROM person p LEFT JOIN household h ON p.HouseholdID=h.HouseholdID ".
    "WHERE p.PersonID IN (".$pid_list.") AND NOT (p.HouseholdID=0 OR h.Address='' ".
    "OR (h.NonJapan=0 AND h.PostalCode=''))";
$result = sqlquery_checked($sql);
$row = mysqli_fetch_object($result);
$num_individuals = $row->num;
$sql = "SELECT count(DISTINCT h.HouseholdID) num FROM person p LEFT JOIN household h ON p.HouseholdID=h.HouseholdID ".
    "WHERE p.PersonID IN (".$pid_list.") AND NOT (p.HouseholdID=0 OR h.Address='' ".
    "OR (h.NonJapan=0 AND h.PostalCode=''))";
$result = sqlquery_checked($sql);
$row = mysqli_fetch_object($result);
$num_households = $row->num;

/* BUILD FILLER (MULTIPLES) OPTION LISTS */
$printable_where = "p.PersonID IN (".$pid_list.") AND NOT (p.HouseholdID=0 OR h.Address='' ".
    "OR (h.NonJapan=0 AND h.PostalCode=''))";
$ind_opts = [];
$result = sqlquery_checked("SELECT p.PersonID, FullName, Furigana ".
    "FROM person p LEFT JOIN household h ON p.HouseholdID=h.HouseholdID ".
    "WHERE ".$printable_where." ORDER BY Furigana, p.PersonID");
while ($row = mysqli_fetch_object($result)) {
  $ind_opts[] = ['value' => $row->PersonID, 'text' => readable_name($row->FullName, $row->Furigana)];
}
$hh_opts = [];
$result = sqlquery_checked("SELECT MIN(p.PersonID) PersonID, h.LabelName ".
    "FROM person p JOIN household h ON p.HouseholdID=h.HouseholdID ".
    "WHERE ".$printable_where." GROUP BY h.HouseholdID, h.LabelName ORDER BY h.LabelName");
while ($row = mysqli_fetch_object($result)) {
  $label = preg_replace('/\r\n|\r|\n/', ' / ', $row->LabelName);
  if (mb_strlen($label) > 40) $label = mb_substr($label, 0, 39).'…';
  $hh_opts[] = ['value' => $row->PersonID, 'text' => $label];
}
$self_pid = 0;
if (!empty($_SESSION['self_pid'])) {
  $result = sqlquery_checked("SELECT p.PersonID ".
      "FROM person p LEFT JOIN household h ON p.HouseholdID=h.HouseholdID ".
      "WHERE p.PersonID=".(int)$_SESSION['self_pid']." AND NOT (p.HouseholdID=0 OR h.Address='' ".
      "OR (h.NonJapan=0 AND h.PostalCode='')) LIMIT 1");
  if ($row = mysqli_fetch_object($result)) $self_pid = (int)$row->PersonID;
}
?>
    <style>
      #filler-block { margin-top:1.2em; }
      #filler-hint { margin:0.3em 0 0.5em; }
      .filler-row { margin-bottom:0.3em; }
      .filler-row .filler-copies { width:4em; }
      #filler-add { margin-top:0.3em; }
    </style>
    <h3><?=_("Select options for label printing and click the button.")?></h3>
    <form action="print_label.php" method="post" name="optionsform" target="_blank" style="text-align:left">
      <input type="hidden" name="pid_list" value="<?=$pid_list?>">
      <div style="display:inline-block;vertical-align:middle;margin:0 2em">
        <label class="label-n-input"><input type="radio" name="name_type" value="ind" tabindex="1"><?=_("Individuals")." (".$num_individuals.")"?></label><br>
        <label class="label-n-input"><input type="radio" name="name_type" value="label" checked><?=_("Households")." (".$num_households.")"?></label>
      </div>
      <div style="display:inline-block;vertical-align:middle">
        <label class="label-n-input"><?=_("Label Type")?>: <select id="label-select" name="label_type" size="1">
<?php
$persheet_map = [];
$result = sqlquery_checked("SELECT LabelType, NumRows, NumCols FROM labelprint ORDER BY ListOrder, LabelType");
while ($row = mysqli_fetch_object($result)) {
  $persheet_map[$row->LabelType] = (int)$row->NumRows * (int)$row->NumCols;
  echo  "                  <option value=\"".$row->LabelType."\">".$row->LabelType."</option>\n";
}
?>
        </select></label> <a id="edit-labelprint" href="labelprint_edit.php" target="_blank"><?=_("Edit / preview this layout…")?></a><br>
        <label class="label-n-input"><input type="checkbox" value="yes" name="wrap_pc" checked><?=_("Japan postal code on its own line")?></label><br>
        <label class="label-n-input"><input type="checkbox" value="yes" name="nj_separate" checked><?=_("Sort by Japan/foreign")?></label>
      </div>
      <div id="filler-block">
        <div><?=_("Fill leftover labels on the last sheet (optional):")?></div>
        <div id="filler-hint" class="comment"></div>
        <div id="filler-rows">
          <div class="filler-row">
            <?php if ($self_pid): ?><label class="label-n-input"><input type="checkbox" class="filler-self"> <?=_("Self")?></label> <?php endif; ?><select name="filler_pid[]" class="filler-pid"></select>
            <label class="label-n-input"> <?=_("copies")?>: <input type="number" name="filler_copies[]" class="filler-copies" min="0" max="999"></label>
          </div>
        </div>
        <button type="button" id="filler-add"><?=_("Add another...")?></button>
      </div>
      <input type="submit" name="submit" value="<?=_("Make PDF")?>">
    </form>

<?php if (!$ajax) load_scripts(['jquery', 'jqueryui']); ?>
<script>
$(function(){
  $("input[type=submit], #filler-add").button();

  var fillerOpts = { ind: <?=json_encode($ind_opts)?>, label: <?=json_encode($hh_opts)?> };
  var perSheet = <?=json_encode($persheet_map)?>;
  var numIndividuals = <?=(int)$num_individuals?>, numHouseholds = <?=(int)$num_households?>;
  var noneText = <?=json_encode(_("(none)"))?>;
  var selfPid = <?=(int)$self_pid?>;
  var txtPerSheet = <?=json_encode(_('%1$d labels per sheet'))?>;
  var txtExtra    = <?=json_encode(_('%1$d extra'))?>;
  var txtExtras   = <?=json_encode(_('%1$d extras'))?>;
  var txtBlank    = <?=json_encode(_('%1$d blank on last sheet'))?>;
  var txtAddOne   = <?=json_encode(_('(+%1$d sheet)'))?>;
  var txtAddMany  = <?=json_encode(_('(+%1$d sheets)'))?>;

  function currentMode(){ return $("input[name=name_type]:checked").val(); }

  function rebuildFillerOptions(){
    var opts = fillerOpts[currentMode()] || [];
    $(".filler-pid").each(function(){
      var $sel = $(this), prev = $sel.val();
      $sel.empty().append($("<option>").val("").text(noneText));
      $.each(opts, function(i, o){ $sel.append($("<option>").val(o.value).text(o.text)); });
      $sel.val(prev && $sel.find('option[value="'+prev+'"]').length ? prev : "");
    });
  }

  function fillerStats(){
    var printCount = (currentMode()=="ind" ? numIndividuals : numHouseholds);
    var per = perSheet[$("#label-select").val()] || 0;
    var blank = 0;
    if (per > 0) { var used = printCount % per; blank = used==0 ? 0 : per-used; }
    var fillerTotal = 0;
    $(".filler-row").each(function(){
      var hasPid = $(this).find(".filler-self").is(":checked") || $(this).find(".filler-pid").val();
      var c = parseInt($(this).find(".filler-copies").val(), 10);
      if (hasPid && c > 0) fillerTotal += c;
    });
    return { per:per, printCount:printCount, blank:blank, fillerTotal:fillerTotal, remaining:blank-fillerTotal };
  }

  function autofillCopies($row){
    var $c = $row.find(".filler-copies");
    if (!parseInt($c.val(), 10)) { var rem = fillerStats().remaining; if (rem > 0) $c.val(rem); }
  }

  function recomputeHint(){
    var s = fillerStats();
    if (s.per <= 0) { $("#filler-hint").text(''); return; }
    var total = s.printCount + s.fillerTotal;
    var blank = (s.per - (total % s.per)) % s.per;
    var added = Math.ceil(total / s.per) - Math.ceil(s.printCount / s.per);
    var parts = [ txtPerSheet.replace('%1$d', s.per) ];
    if (s.fillerTotal > 0) parts.push((s.fillerTotal === 1 ? txtExtra : txtExtras).replace('%1$d', s.fillerTotal));
    var blankPart = txtBlank.replace('%1$d', blank);
    if (added > 0) blankPart += ' ' + (added === 1 ? txtAddOne : txtAddMany).replace('%1$d', added);
    parts.push(blankPart);
    $("#filler-hint").text(parts.join(' · '));
  }

  $('#label-select').on('change', function(){
    $('#edit-labelprint').attr('href', 'labelprint_edit.php?type=' + encodeURIComponent(this.value));
    recomputeHint();
  });
  $("input[name=name_type]").on('change', function(){ rebuildFillerOptions(); recomputeHint(); });
  $("#filler-rows").on('input', '.filler-copies', recomputeHint);
  $("#filler-rows").on('change', '.filler-pid', function(){
    if ($(this).val()) autofillCopies($(this).closest(".filler-row"));
    recomputeHint();
  });
  $("#filler-rows").on('change', '.filler-self', function(){
    var $row = $(this).closest(".filler-row");
    var $sel = $row.find(".filler-pid");
    if (this.checked) {
      $sel.prop('disabled', true);
      if (!$row.find(".filler-self-pid").length) {
        $('<input type="hidden" class="filler-self-pid" name="filler_pid[]">').val(selfPid).appendTo($row);
      }
      autofillCopies($row);
    } else {
      $sel.prop('disabled', false);
      $row.find(".filler-self-pid").remove();
    }
    recomputeHint();
  });
  $("#filler-add").on('click', function(){
    var $row = $("#filler-rows .filler-row").last().clone();
    $row.find(".filler-self").prop('checked', false);
    $row.find(".filler-pid").prop('disabled', false);
    $row.find(".filler-self-pid").remove();
    $row.find(".filler-copies").val("");
    $("#filler-rows").append($row);
    rebuildFillerOptions();
    recomputeHint();
  });

  rebuildFillerOptions();
  $('#label-select').trigger('change');
});
</script>
<?php if (!$ajax) footer(); ?>
