<?php
include("functions.php");
include("accesscontrol.php");
$ajax = !empty($_REQUEST['ajax']);

// A REQUEST TO ADD A PERORG RECORD?
if (!empty($_POST['newperorg'])) {
  $result = sqlquery_checked("SELECT * FROM person WHERE PersonID=".$_POST['orgid']." AND Organization=1");
  if (mysqli_num_rows($result) == 0) die("This ID does not point to an organization record. Use Browse if you need help.");
  $pidarray = explode(",",$pid_list);
  $added = 0;
  foreach($pidarray as $eachpid) {
    sqlquery_checked("INSERT INTO perorg(PersonID, OrgID, Leader) ".
    "VALUES($eachpid,{$_POST['orgid']},0) ON DUPLICATE KEY UPDATE Leader=Leader");
    if (mysqli_affected_rows($db) == 1)  $added++;
  }
  if (!$ajax) pageheader(_("Connect All to an Organization"), 0);
  echo "<h3>".sprintf(_("%s organization association records added."),$added)."</h3>";
  if (!$ajax) footer();
  exit;
}

if (!$ajax) pageheader(_("Connect All to an Organization"), 0);
echo "<h3>"._("Type the ID of an organization or search by name.")."</h3>\n";
echo "<p style=\"margin-bottom:10px\">"._("NOTE: A leader cannot be designated here - do that on the leader's detail page.")."</p>\n";
?>
<style>
.org-picker { margin:8px 0 0; }
#orgsearchtxt { width:12em; }
/* In-flow (not an absolute overlay) on purpose: #ResultFrame is a max-height:500px overflow-y:auto
   panel, so an absolute dropdown would be clipped by it. In-flow lets the panel grow to fit, like
   every other batch_*.php result. The picker sits below the ID/Save row, so the list grows downward
   without pushing anything. */
#org-results {
  display:none;
  width:max-content; min-width:12em; max-width:90vw; box-sizing:border-box;
  max-height:250px; overflow-y:auto;
  margin:2px 0 0; padding:0; list-style:none;
  background:var(--main-bg); border:1px solid var(--section-border);
}
#org-results li { padding:4px 6px; }
#org-results li.org-result { cursor:pointer; border-bottom:1px solid var(--table-cell-border); }
#org-results li.org-result:hover { background:var(--primary-light); }
#org-results li.org-noresults { color:Gray; font-style:italic; }
#org-results .org-result-id { color:Gray; font-size:0.9em; white-space:nowrap; }
#org-results .org-info { margin-left:6px; }
#org-results .org-open { margin-left:6px; cursor:pointer; text-decoration:none; font-weight:bold; line-height:17px; color:var(--person-info-title); }
#org-results .org-open:hover { color:var(--link-hover); }
#org-results .org-detail { margin-top:4px; padding-top:4px; border-top:1px dotted var(--input-border); font-size:0.85em; color:Gray; cursor:default; }
</style>

<form name="orgform" id="orgform" method="POST" action="<?=$_SERVER['PHP_SELF']?>" onSubmit="return ValidateOrg()">
<input type="hidden" name="pid_list" value="<?=$pid_list?>" />
<label><?=_("Organization ID")?>: <input type="text" name="orgid" id="orgid" style="width:5em;ime-mode:disabled" value=""></label>
<span id="orgname" style="color:darkred;font-weight:bold"></span>
<input type="submit" value="<?=_("Save Organization Assignment")?>" name="newperorg">
</form>

<div class="org-picker">
<label class="label-n-input" for="orgsearchtxt"><?=_("Search")?>: <input type="text" id="orgsearchtxt" autocomplete="off"></label>
<ul id="org-results"></ul>
</div>

<?php
if (!$ajax) load_scripts(['jquery', 'jqueryui']);
?>
<script>
$(document).ready(function(){
  $("input[type=submit]").button();
  $(document).ajaxError(function(e, xhr, settings, exception) {
    alert('Error calling ' + settings.url + ': ' + exception);
  });

  $("#orgid").keyup(function(){  //display Organization name when applicable ID is typed
    $("#orgname").load("ajax_request.php",{'req':'OrgName','orgid':$("#orgid").val()});
  });

  // ----- Organization search picker (shares the SelectOrg/OrgDetail endpoints with individual.php;
  //       leaner here on purpose: no auto-fit width, no running count) -----
  var orgSearchTimer = null;
  var $orgResults = $("#org-results");

  function hideOrgResults() { $orgResults.hide().empty(); }

  $("#orgsearchtxt").on("keyup", function() {
    var q = $.trim($(this).val());
    if (q.length < 2) { hideOrgResults(); return; }
    if (orgSearchTimer) clearTimeout(orgSearchTimer);
    orgSearchTimer = setTimeout(function() {
      $.getJSON("ajax_request.php", { req: "SelectOrg", q: q }, function(data) {
        if (data.alert === "NOSESSION") { alert("<?=_("Your login has timed out - please refresh the page.")?>"); return; }
        $orgResults.empty();
        if (!data.rows || !data.rows.length) {
          $orgResults.append($("<li>", { "class": "org-noresults" }).text("<?=_("No matching organizations")?>")).show();
          return;
        }
        $.each(data.rows, function(i, r) {
          var $li = $("<li>", { "class": "org-result" }).data("pid", r.pid).data("name", r.name);
          var $name = $("<span>", { "class": "org-result-name" }).text(r.name);
          $name.append($("<span>", { "class": "org-result-id" }).text(" [<?=_("ID")?>: " + r.pid + "]"));
          $li.append($name);
          $li.append($("<span>", { "class": "icon-badge org-info", title: "<?=_("Show categories & address")?>" }).text("i"));
          $li.append($("<a>", { "class": "org-open", target: "_blank",
            title: "<?=_("Open record in new tab")?>", href: "individual.php?pid=" + r.pid }).html("&#8599;"));
          $li.append($("<div>", { "class": "org-detail" }).hide());
          $orgResults.append($li);
        });
        $orgResults.show();
      });
    }, 300);
  });

  $orgResults.on("click", "li.org-result", function() {   // pick fills the add-org form
    $("#orgid").val($(this).data("pid"));
    $("#orgname").text($(this).data("name"));
    hideOrgResults();
  });

  $orgResults.on("click", ".org-info", function(e) {   // lazily fetch & toggle Categories + Address
    e.stopPropagation();
    var $li = $(this).closest("li"), $d = $li.find(".org-detail");
    if ($d.is(":visible")) { $d.slideUp(120); return; }
    if ($li.data("loaded")) { $d.slideDown(120); return; }
    $.getJSON("ajax_request.php", { req: "OrgDetail", orgid: $li.data("pid") }, function(data) {
      if (data.alert === "NOSESSION") { alert("<?=_("Your login has timed out - please refresh the page.")?>"); return; }
      $d.empty();
      if (data.categories) $d.append($("<div>").append($("<strong>").text("<?=_("Categories")?>: ")).append(document.createTextNode(data.categories)));
      if (data.address)    $d.append($("<div>").append($("<strong>").text("<?=_("Address")?>: ")).append(document.createTextNode(data.address)));
      if (!data.categories && !data.address) $d.append($("<div>").text("<?=_("(no categories or address on file)")?>"));
      $li.data("loaded", true);
      $d.slideDown(120);
    });
  });

  $orgResults.on("click", ".org-open, .org-detail", function(e) { e.stopPropagation(); });

  // close on outside click — namespaced + re-bound so reloading this fragment into batch.php doesn't stack handlers
  $(document).off("click.orgpickerbatch").on("click.orgpickerbatch", function(e) {
    if (!$(e.target).closest(".org-picker").length) hideOrgResults();
  });
});

function ValidateOrg() {
  if ($('#orgid').val() == '') {
    alert('<?=_("Please enter an Organization ID.")?>');
    $('#orgid').focus();
    return false;
  }
  if ($.trim($('#orgname').text()) == '') {
    alert('<?=_("Not a valid Organization ID. If you\'re not sure, try Search/Browse.")?>');
    $('#orgid').focus();
    return false;
  }
  return true;
}
</script>

<?php
if (!$ajax) footer();
?>
