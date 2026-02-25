<?php
include("functions.php");
include("accesscontrol.php");
header1(_("Batch Processing"));
?>
<link rel="stylesheet" href="style.php?jquery=1" type="text/css" />
<style>
#batch-container { display:flex; flex-wrap:wrap; align-items:flex-start; }
#batch-chooser { flex:1; min-width:520px; margin-right:1em; }
#batch-actions { flex:1; min-width:280px; }
#batch-chooser, #batch-actions { margin-top:0; margin-bottom:0; }
.batch-search-section { margin-bottom:0.5em; }
#shuttle { display:flex; align-items:flex-start; gap:4px; }
#shuttle-btns { display:flex; flex-direction:column; gap:4px; padding-top:1.5em; }
#shuttle-btns button { font-size:1.1em; font-weight:900; transform: scaleY(1.3); margin-top:1em; padding:0.4em 0.4em; }
.select-block { flex:1; min-width:0; text-align:center; }
#available, #selected { width:100%; height:300px; }
.action-btn { margin:2px 2px 2px 0; }
div.buttongroup { margin:6px 0 0 0; padding:0; }
</style>
<?php
// Build preselected list from ?pids= or basket
$preselected_js = '[]';
$preselected_pids = '';
if (!empty($_REQUEST['pids'])) {
    $preselected_pids = $_REQUEST['pids'];
} elseif (!empty($_SESSION['basket'])) {
    $preselected_pids = implode(',', $_SESSION['basket']);
}
if ($preselected_pids) {
    $preselected_pids = preg_replace('/[^0-9,]/', '', $preselected_pids);
    if ($preselected_pids) {
        $sql = "SELECT PersonID, FullName, Furigana FROM person WHERE PersonID IN ($preselected_pids) ORDER BY Furigana, PersonID";
        $result = sqlquery_checked($sql);
        $preselected_arr = [];
        while ($row = mysqli_fetch_object($result)) {
            $preselected_arr[] = ['pid' => (int)$row->PersonID, 'name' => readable_name($row->FullName, $row->Furigana)];
        }
        $preselected_js = json_encode($preselected_arr);
    }
}
load_scripts(['jquery', 'jqueryui']);
?>
<?php header2(1); ?>

<h1 id="title"><?=_("Batch Processing")?></h1>
<div id="batch-container">
  <fieldset id="batch-chooser">
    <legend><?=_("1. Choose people/orgs")?></legend>
    <div class="batch-search-section">
      <label><?=_("Category")?>:
        <select id="catlist" size="1">
          <option value=""><?=_("Choose a category...")?></option>
<?php
$sql = "SELECT * FROM category ORDER BY Category";
$result = sqlquery_checked($sql);
while ($row = mysqli_fetch_object($result)) {
    echo "          <option value=\"$row->CategoryID\">".d2h($row->Category)."</option>\n";
}
?>
        </select>
      </label>
    </div>
    <div class="batch-search-section">
      <label><?=_("Search by name")?>:
        <input type="text" id="batch-search-text" maxlength="50" style="width:12em">
      </label>
      <button id="text-search"><?=_("Search")?></button>
      <div class="comment"><?=_("To add or remove individual items, click a name to highlight and/or use Ctrl/Cmd or Shift while ".
          "clicking to highlight additional names, then click <strong>&gt;</strong> or <strong>&lt;</strong>. ".
          "To add/remove all, click <strong>&gt;&gt;</strong> or <strong>&lt;&lt;</strong>.")?></div>
    </div>
    <div id="shuttle">
      <div class="select-block">
        <h4><?=_("Available")?> (<span id="avail_count">0</span>)</h4>
        <select id="available" multiple></select>
      </div>
      <div id="shuttle-btns">
        <button id="add-sel">&gt;</button>
        <button id="add-all">&gt;&gt;</button>
        <button id="rem-sel">&lt;</button>
        <button id="rem-all">&lt;&lt;</button>
      </div>
      <div class="select-block">
        <h4><?=_("Selected")?> (<span id="selected_count">0</span>)</h4>
        <select id="selected" multiple></select>
      </div>
    </div>
  </fieldset>
  <fieldset id="batch-actions">
    <legend><?=_("2. Choose batch actions")?></legend>
    <div class="buttongroup">
      <h3><?=_("Batch Data Entry")?></h3>
      <button class="action-btn" data-url="batch_attendance.php"><?=_("Record Attendance")?></button>
      <button class="action-btn" data-url="batch_action.php"><?=_("Add an Action for All")?></button>
      <button class="action-btn" data-url="batch_category.php"><?=_("Add All to a Category")?></button>
      <button class="action-btn" data-url="batch_cat_remove.php"><?=_("Remove All from a Category")?></button>
      <button class="action-btn" data-url="batch_organization.php"><?=_("Connect All to an Organization")?></button>
    </div>
    <div class="buttongroup">
      <h3><?=_("Reports")?></h3>
      <button class="action-btn" data-url="batch_person_text.php"><?=_("Person Info (Text)")?></button>
      <button class="action-btn" data-url="batch_custom.php"><?=_("Custom Report")?></button>
      <button class="action-btn" data-url="batch_person_xml.php"><?=_("Person Info (XML)")?></button>
      <button class="action-btn" data-url="batch_person_format.php"><?=_("Person Info (Formatted)")?></button>
      <button class="action-btn" data-url="batch_household_text.php"><?=_("Household Info (Text)")?></button>
      <button class="action-btn" data-url="batch_household_format.php"><?=_("Household Info (Formatted)")?></button>
      <button class="action-btn" data-url="batch_overview.php"><?=_("Overview Pages")?></button>
    </div>
    <div class="buttongroup">
      <h3><?=_("Basket")?></h3>
      <button type="button" id="basket-add"><?=_("Add to Basket")?></button>
      <button type="button" id="basket-rem"><?=_("Remove from Basket")?></button>
      <button type="button" id="basket-set"><?=_("Set Basket to these only")?></button>
    </div>
    <div class="buttongroup">
      <h3><?=_("Specialized Output")?></h3>
      <button class="action-btn" data-url="batch_label.php"><?=_("Print Labels")?></button>
      <button class="action-btn" data-url="batch_printaddr.php"><?=_("Print Envelopes/Postcards")?></button>
      <button class="action-btn" data-url="batch_photos.php"><?=_("Print Photos")?></button>
      <button class="action-btn" data-url="batch_email.php"><?=_("Prepare Email")?></button>
    </div>
  </fieldset>
</div>
<div id="ResultFrame"></div>

<script>
$(document).ready(function(){

  var currentResults = [];
  var selected = <?=$preselected_js?>;

  // Initialize selected display
  rebuildSelected();

  // Apply jQuery UI button styling
  $("#shuttle-btns button, .action-btn, #basket-add, #basket-rem, #basket-set").button();

  // Load available box by category — auto-trigger on change, no Load button
  $("#catlist").on("change", function() {
    var catid = $(this).val();
    if (!catid) { currentResults = []; rebuildAvail(); return; }
    $("#batch-search-text").val("");
    $.getJSON("ajax_request.php", {req: "BatchPersonSearch", catid: catid}, populateAvail);
  });

  // Load available box by text search
  function doTextSearch() {
    var q = $.trim($("#batch-search-text").val());
    if (q.length < 2) { alert("<?=_("Please enter at least 2 characters to search.")?>"); return; }
    $("#catlist").val("");
    $.getJSON("ajax_request.php", {req: "BatchPersonSearch", q: q}, populateAvail);
  }
  $("#batch-search-text").on("keydown", function(e) {
    if (e.which === 13) { e.preventDefault(); doTextSearch(); }
  });
  $("#text-search").on("click", doTextSearch);

  function populateAvail(data) {
    if (data.alert) { alert(data.alert); return; }
    currentResults = data.results || [];
    rebuildAvail();
  }

  function rebuildAvail() {
    var selPids = selected.map(function(x){ return x.pid; });
    $("#available").empty();
    $.each(currentResults, function(i, item) {
      if (selPids.indexOf(item.pid) === -1) {
        $("#available").append($("<option>").val(item.pid).text(item.name));
      }
    });
    $("#avail_count").text($("#available option").length);
  }

  function rebuildSelected() {
    var prevSelected = $("#selected option:selected").map(function(){ return parseInt($(this).val()); }).get();
    $("#selected").empty();
    $.each(selected, function(i, item) {
      var opt = $("<option>").val(item.pid).text(item.name);
      if (prevSelected.indexOf(item.pid) !== -1) opt.prop("selected", true);
      $("#selected").append(opt);
    });
    updateCount();
  }

  function updateCount() {
    $("#selected_count").text(selected.length);
  }

  function clearResultFrame() {
    $("#ResultFrame").empty();
  }

  // Shuttle: add highlighted items
  $("#add-sel").on("click", function() {
    $("#available option:selected").each(function() {
      selected.push({pid: parseInt($(this).val()), name: $(this).text()});
    });
    rebuildAvail();
    rebuildSelected();
    clearResultFrame();
  });

  // Shuttle: add all items
  $("#add-all").on("click", function() {
    $("#available option").each(function() {
      selected.push({pid: parseInt($(this).val()), name: $(this).text()});
    });
    rebuildAvail();
    rebuildSelected();
    clearResultFrame();
  });

  // Shuttle: remove highlighted from selected box
  $("#rem-sel").on("click", function() {
    var toRemove = $("#selected option:selected").map(function(){ return parseInt($(this).val()); }).get();
    selected = selected.filter(function(x){ return toRemove.indexOf(x.pid) === -1; });
    rebuildAvail();
    rebuildSelected();
    clearResultFrame();
  });

  // Shuttle: remove all from selected box
  $("#rem-all").on("click", function() {
    selected = [];
    rebuildAvail();
    rebuildSelected();
    clearResultFrame();
  });

  // Action buttons: load batch_*.php into #ResultFrame
  $(".action-btn").on("click", function() {
    if (!selected.length) { alert("<?=_("Please select at least one person/org.")?>"); return; }
    var pids = selected.map(function(x){ return x.pid; }).join(",");
    var url = $(this).data("url");
    $("#ResultFrame").html("<p><?=_("Loading...")?></p>");
    $.post(url, {pid_list: pids, ajax: 1}, function(r) {
      $("#ResultFrame").html(r);
    });
  });

  // Event delegation: re-submit forms loaded into #ResultFrame
  $("#ResultFrame").on("submit", "form", function(e) {
    var target = $(this).attr("target") || "";
    if (target === "_blank" || target === "_top") return;
    e.preventDefault();
    var url = $(this).attr("action");
    var data = $(this).serialize() + "&ajax=1";
    $.post(url, data, function(r) { $("#ResultFrame").html(r); });
  });

  // Basket buttons
  function getSelectedPids() {
    return selected.map(function(x){ return x.pid; }).join(",");
  }

  $("#basket-add").on("click", function() {
    var pids = getSelectedPids();
    if (!pids) { alert("<?=_("Please select at least one person/org.")?>"); return; }
    $.post("basket.php", { add: pids }, function(r) {
      if (!isNaN(r)) { $('span.basketcount').html(r); } else { alert(r); }
    }, "text");
  });

  $("#basket-rem").on("click", function() {
    var pids = getSelectedPids();
    if (!pids) { alert("<?=_("Please select at least one person/org.")?>"); return; }
    $.post("basket.php", { rem: pids }, function(r) {
      if (!isNaN(r)) { $('span.basketcount').html(r); } else { alert(r); }
    }, "text");
  });

  $("#basket-set").on("click", function() {
    var pids = getSelectedPids();
    if (!pids) { alert("<?=_("Please select at least one person/org.")?>"); return; }
    $.post("basket.php", { set: pids }, function(r) {
      if (!isNaN(r)) { $('span.basketcount').html(r); } else { alert(r); }
    }, "text");
  });

});
</script>
<?php footer(); ?>