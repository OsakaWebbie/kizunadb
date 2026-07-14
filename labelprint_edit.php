<?php
/*****
labelprint_edit.php — editor + live visual preview for label-sheet layouts (`labelprint` table).

Part of the envelope/label layout UI (GitHub issue #52). Self-contained: this page renders the
form + SVG preview AND handles its own save/delete POST at the top (no ajax_action.php case). The
live mock is drawn client-side by js/layout_preview.js, re-running print_label.php's textpos math.
*****/
include("functions.php");
include("accesscontrol.php");

/* ---------- in-file save / delete handler (AJAX POST) ---------- */

// Sanitised `col=value` assignments for every non-key labelprint field.
function lp_fields() {
  $paper = (($_POST['PaperSize'] ?? 'a4') === 'letter') ? 'letter' : 'a4';
  $ints = ['NumRows', 'NumCols', 'AddrPointSize', 'NJAddrPointSize', 'NamePointSize'];
  $decs = ['PageMarginTop', 'PageMarginLeft', 'LabelWidth', 'LabelHeight',
           'GutterX', 'GutterY', 'AddrMarginLeft', 'AddrMarginRight'];
  $f = ["PaperSize='$paper'"];
  foreach ($ints as $k) $f[] = "`$k`=" . intval($_POST[$k] ?? 0);
  foreach ($decs as $k) $f[] = "`$k`=" . (float)($_POST[$k] ?? 0);
  return implode(',', $f);
}

function lp_exists($name) {
  $r = sqlquery_checked("SELECT 1 FROM labelprint WHERE LabelType='" . h2d($name) . "'");
  return mysqli_num_rows($r) > 0;
}

if (!empty($_POST['op'])) {
  header('Content-Type: text/plain; charset=utf-8');
  $op = $_POST['op'];
  $name = trim($_POST['LabelType'] ?? '');
  $orig = trim($_POST['orig'] ?? '');

  if ($op === 'delete') {
    if ($orig === '') { echo _('Nothing to delete.'); exit; }
    sqlquery_checked("DELETE FROM labelprint WHERE LabelType='" . h2d($orig) . "' LIMIT 1");
    echo '*' . _('Deleted.');
    exit;
  }

  if ($name === '') { echo _('Please enter a label type name.'); exit; }
  $set = lp_fields();

  if ($op === 'saveasnew' || $orig === '') {                 // insert a brand-new preset
    if (lp_exists($name)) { echo sprintf(_('A label type named "%s" already exists.'), $name); exit; }
    sqlquery_checked("INSERT INTO labelprint SET LabelType='" . h2d($name) . "', $set");
    echo '*' . _('Saved.');
    exit;
  }

  // op === 'save' — update the loaded row (a changed name is a rename of its primary key)
  if ($name !== $orig && lp_exists($name)) {
    echo sprintf(_('A label type named "%s" already exists.'), $name);
    exit;
  }
  sqlquery_checked("UPDATE labelprint SET LabelType='" . h2d($name) . "', $set WHERE LabelType='" . h2d($orig) . "' LIMIT 1");
  echo '*' . _('Saved.');
  exit;
}

/* ---------- normal page render ---------- */

$presets = [];
$r = sqlquery_checked("SELECT * FROM labelprint ORDER BY LabelType");
while ($row = mysqli_fetch_object($r)) $presets[$row->LabelType] = $row;

// Real records from the Basket, so the user can preview against their own (esp. long) data.
$basketSamples = [];
if (!empty($_SESSION['basket'])) {
  $ids = implode(',', array_map('intval', $_SESSION['basket']));
  if ($ids !== '') {
    $bq = "SELECT h.NonJapan, pc.Prefecture, pc.ShiKuCho, h.Address, h.PostalCode, "
        . "IF(h.LabelName<>'', h.LabelName, IF(h.NonJapan, CONCAT(p.Title,' ',p.FullName), CONCAT(p.FullName,p.Title))) AS Name "
        . "FROM person p LEFT JOIN household h ON p.HouseholdID=h.HouseholdID "
        . "LEFT JOIN postalcode pc ON h.PostalCode=pc.PostalCode "
        . "WHERE p.PersonID IN ($ids) AND h.Address IS NOT NULL AND h.Address<>'' "
        . "AND (h.NonJapan=1 OR h.PostalCode<>'') ORDER BY FIND_IN_SET(p.PersonID,'$ids')";
    $br = sqlquery_checked($bq);
    while ($row = mysqli_fetch_object($br)) {
      $basketSamples[] = [
        'japan' => $row->NonJapan ? false : true,
        'postalcode' => $row->PostalCode, 'prefecture' => $row->Prefecture,
        'shikucho' => $row->ShiKuCho, 'address' => $row->Address, 'name' => $row->Name,
      ];
    }
  }
}
$basketCount = count($basketSamples);

$initial = $_GET['type'] ?? '';
if (!isset($presets[$initial])) $initial = '__new__';   // default to New on first open

pageheader(_("Label Layout Editor"), 1);

// One labelled number input row.
function lp_num($id, $label, $step = '0.1') {
  echo '<label class="lp-row"><span>' . $label . '</span>'
     . '<input type="number" step="' . $step . '" min="0" id="f_' . $id . '" class="lp-field" name="' . $id . "\"></label>\n";
}
?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Source+Sans+3:ital,wght@0,400;0,700;1,400&display=swap');
.lp-layout { display:flex; flex-wrap:wrap; gap:1.5em; align-items:flex-start; }
.lp-form { flex:1 1 360px; max-width:520px; }
.lp-preview-pane { flex:1 1 300px; position:sticky; top:1em; }
.lp-form fieldset { margin:0 0 1em; border:1px solid #ccc; border-radius:4px; }
.lp-form legend { font-weight:bold; padding:0 0.4em; }
.lp-row { display:flex; align-items:center; gap:0.75em; margin:0.35em 0; }
.lp-row > span { flex:0 0 8em; }
.lp-row input[type=text], .lp-row textarea { flex:1 1 auto; width:auto; min-width:0; }
.lp-row textarea { resize:vertical; font-family:inherit; }
.lp-row input[type=number] { flex:0 0 5.5em; width:5.5em; }
.lp-row select { flex:0 0 auto; }
.lp-row-ta { align-items:flex-start; }
.lp-grid { display:grid; grid-template-columns:1fr 1fr; gap:0 1.5em; }
.lp-buttons { margin:1em 0; display:flex; gap:0.75em; flex-wrap:wrap; }
.lp-preview-pane h3 { margin-top:0; }
#lp-preview { display:inline-block; }
.lp-svg { background:#fff; box-shadow:0 1px 6px rgba(0,0,0,.25); }
.lp-svg .lp-paper { fill:#fff; stroke:#bbb; stroke-width:1; }
.lp-svg .lp-label { fill:#f7fbff; stroke:#8ab4d8; stroke-width:0.75; }
.lp-svg .lp-label.lp-overflow { fill:#fff0f0; stroke:#d64545; stroke-width:1.25; }
.lp-svg .lp-text { overflow:hidden; color:#222; padding:0 1px; font-family:'Source Sans 3', sans-serif; }
.lp-svg .lp-gap { height:0.45em; }
.lp-hint { color:#666; font-size:0.85em; }
#status-msg { position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); padding:10px 16px;
  background:#2e7d32; color:#fff; border-radius:4px; box-shadow:0 2px 8px rgba(0,0,0,.2); z-index:10000; display:none; }
</style>

<h1 id="title"><?=_("Label Layout Editor")?></h1>
<p class="lp-hint"><?=_("Pick a saved layout to edit, or choose \"New…\" and start from a common product. The preview updates as you type.")?></p>

<div class="lp-layout">
  <div class="lp-form">
    <div class="lp-row">
      <span><?=_("Layout")?></span>
      <select id="f_preset">
<?php
foreach ($presets as $k => $v) {
  echo '        <option value="' . htmlspecialchars($k, ENT_QUOTES) . '"'
     . ($k === $initial ? ' selected' : '') . '>' . htmlspecialchars($k, ENT_QUOTES) . "</option>\n";
}
?>
        <option value="__new__"<?=($initial === '__new__' ? ' selected' : '')?>><?=_("New…")?></option>
      </select>
    </div>
    <div class="lp-row" id="starterrow" style="display:none">
      <span><?=_("Start from")?></span>
      <select id="f_starter">
        <option value=""><?=_("Select a common product…")?></option>
        <optgroup label="<?=_("Japan (A4)")?>">
          <option value="aone_28939">A-One 28939 — 10面 (A4)</option>
          <option value="aone_31512">A-One 31512 — 12面 (A4)</option>
          <option value="aone_28648">A-One 28648 — 24面 (A4)</option>
          <option value="askul_ma506t">Askul MA-506T — 24面 (A4)</option>
        </optgroup>
        <optgroup label="<?=_("US Letter")?>">
          <option value="avery_5160">Avery 5160 — 30 / sheet</option>
          <option value="avery_5161">Avery 5161 — 20 / sheet</option>
        </optgroup>
      </select>
    </div>

    <label class="lp-row" id="namerow"><span><?=_("Layout name")?></span>
      <input type="text" id="f_LabelType" name="LabelType" maxlength="100"></label>

    <fieldset>
      <legend><?=_("Sheet")?></legend>
      <label class="lp-row"><span><?=_("Paper size")?></span>
        <select id="f_PaperSize" class="lp-field" name="PaperSize">
          <option value="a4">A4</option>
          <option value="letter"><?=_("US Letter")?></option>
        </select></label>
      <div class="lp-grid">
<?php
lp_num('NumRows', _('Rows'), '1');
lp_num('NumCols', _('Columns'), '1');
lp_num('LabelWidth', _('Label width'));
lp_num('LabelHeight', _('Label height'));
lp_num('GutterX', _('Gutter across'));
lp_num('GutterY', _('Gutter down'));
lp_num('PageMarginTop', _('Page margin top'));
lp_num('PageMarginLeft', _('Page margin left'));
?>
      </div>
      <p class="lp-hint"><?=_("\"Label width/height\" is the size of one label; the gutter is the gap to the next one.")?></p>
    </fieldset>

    <fieldset>
      <legend><?=_("Text &amp; fonts")?></legend>
      <div class="lp-grid">
<?php
lp_num('AddrMarginLeft', _('Text inset left'));
lp_num('AddrMarginRight', _('Text inset right'));
lp_num('AddrPointSize', _('Address size (Japan)'), '1');
lp_num('NamePointSize', _('Name size (Japan)'), '1');
lp_num('NJAddrPointSize', _('Address size (foreign)'), '1');
?>
      </div>
    </fieldset>

    <fieldset>
      <legend><?=_("Preview sample")?></legend>
      <div class="lp-row" style="flex-wrap:wrap">
        <label><input type="radio" name="samplelang" value="japan" class="lp-sample" checked> <?=_("Japan (sample)")?></label>
        <label><input type="radio" name="samplelang" value="foreign" class="lp-sample"> <?=_("Foreign (sample)")?></label>
        <label<?=($basketCount ? '' : ' style="color:#999"')?>><input type="radio" name="samplelang" value="basket" class="lp-sample"<?=($basketCount ? '' : ' disabled')?>> <?=sprintf(_("From Basket (%d)"), $basketCount)?></label>
      </div>
      <div class="lp-row"><label><input type="checkbox" id="s_wrap_pc" class="lp-sample" checked> <?=_("Japan postal code on its own line")?></label></div>
      <div id="sample_jp">
        <label class="lp-row"><span><?=_("Postal code")?></span><input type="text" id="s_pc" class="lp-sample" value="859-3616"></label>
        <label class="lp-row"><span><?=_("Prefecture")?></span><input type="text" id="s_pref" class="lp-sample" value="長崎県"></label>
        <label class="lp-row"><span><?=_("City, etc.")?></span><input type="text" id="s_shikucho" class="lp-sample" value="東彼杵郡川棚町白石郷"></label>
        <label class="lp-row lp-row-ta"><span><?=_("Rest of Address")?></span><textarea id="s_addr" class="lp-sample" rows="2"></textarea></label>
        <label class="lp-row lp-row-ta"><span><?=_("Label Name")?></span><textarea id="s_name" class="lp-sample" rows="3"></textarea></label>
      </div>
      <div id="sample_fg" style="display:none">
        <label class="lp-row lp-row-ta"><span><?=_("Label Name")?></span><textarea id="s_fname" class="lp-sample" rows="2"></textarea></label>
        <label class="lp-row lp-row-ta"><span><?=_("Address")?></span><textarea id="s_faddr" class="lp-sample" rows="3"></textarea></label>
      </div>
      <p class="lp-hint" id="basket_hint" style="display:none"><?=_("Showing your Basket records, repeating to fill the sheet.")?></p>
    </fieldset>

    <div class="lp-buttons">
      <button type="button" id="btn_save"><?=_("Save")?></button>
      <button type="button" id="btn_saveasnew" disabled><?=_("Save as New")?></button>
      <button type="button" id="btn_delete"><?=_("Delete")?></button>
    </div>
  </div>

  <div class="lp-preview-pane">
    <h3><?=_("Preview")?></h3>
    <div id="lp-preview"></div>
    <p class="lp-hint"><?=_("A red outline means the text overflows that label at the chosen font size.")?></p>
  </div>
</div>

<div id="del_dialog" title="<?=_("Delete Layout")?>" style="display:none"><p></p></div>
<div id="status-msg"></div>

<?php
load_scripts(['jquery', 'jqueryui']);
$v = @filemtime(__DIR__ . '/js/layout_preview.js');
echo '<script src="js/layout_preview.js?v=' . $v . "\"></script>\n";
?>
<script>
$(function () {
  var presets = <?=json_encode($presets, JSON_UNESCAPED_UNICODE)?>;
  var initial = <?=json_encode($initial)?>;
  var basketSamples = <?=json_encode($basketSamples, JSON_UNESCAPED_UNICODE)?>;

  // Multi-line sample defaults (set in JS to avoid <textarea> whitespace pitfalls).
  var SAMPLE_DEFAULTS = {
    s_addr:  '1-18\nグリーンハイツ川棚 203号室',
    s_name:  '山田 太郎様\n山田 由美子様\nご家族の皆様',
    s_fname: 'Mr. & Mrs. John Q. Sample',
    s_faddr: '123 Main Street, Apt. 4\nSpringfield, IL 62704\nU.S.A.'
  };

  // Starter catalog: pitch encoded in Label width/height, gutter 0 (matches print_label.php).
  var STARTERS = {
    aone_28939:   {PaperSize:'a4',    NumRows:5,  NumCols:2, PageMarginTop:21.2, PageMarginLeft:18.6, LabelWidth:86.4,  LabelHeight:50.8, GutterX:0, GutterY:0, AddrMarginLeft:4,   AddrMarginRight:3, AddrPointSize:12, NJAddrPointSize:12, NamePointSize:15},
    aone_31512:   {PaperSize:'a4',    NumRows:6,  NumCols:2, PageMarginTop:21.2, PageMarginLeft:13.0, LabelWidth:92.0,  LabelHeight:42.3, GutterX:0, GutterY:0, AddrMarginLeft:5,   AddrMarginRight:5, AddrPointSize:11, NJAddrPointSize:11, NamePointSize:13},
    aone_28648:   {PaperSize:'a4',    NumRows:8,  NumCols:3, PageMarginTop:21.2, PageMarginLeft:9.3,  LabelWidth:64.0,  LabelHeight:33.9, GutterX:0, GutterY:0, AddrMarginLeft:4,   AddrMarginRight:4, AddrPointSize:9,  NJAddrPointSize:9,  NamePointSize:11},
    askul_ma506t: {PaperSize:'a4',    NumRows:8,  NumCols:3, PageMarginTop:13.0, PageMarginLeft:0.0,  LabelWidth:70.0,  LabelHeight:34.0, GutterX:0, GutterY:0, AddrMarginLeft:5,   AddrMarginRight:5, AddrPointSize:10, NJAddrPointSize:10, NamePointSize:12},
    avery_5160:   {PaperSize:'letter',NumRows:10, NumCols:3, PageMarginTop:12.7, PageMarginLeft:4.7,  LabelWidth:66.7,  LabelHeight:25.4, GutterX:3.2, GutterY:0, AddrMarginLeft:2.5, AddrMarginRight:3, AddrPointSize:9,  NJAddrPointSize:9,  NamePointSize:11},
    avery_5161:   {PaperSize:'letter',NumRows:10, NumCols:2, PageMarginTop:12.7, PageMarginLeft:4.9,  LabelWidth:101.6, LabelHeight:25.4, GutterX:3.0, GutterY:0, AddrMarginLeft:3,   AddrMarginRight:3, AddrPointSize:10, NJAddrPointSize:10, NamePointSize:12}
  };

  var FIELDS = ['PaperSize','NumRows','NumCols','PageMarginTop','PageMarginLeft','LabelWidth',
    'LabelHeight','GutterX','GutterY','AddrMarginLeft','AddrMarginRight','AddrPointSize',
    'NJAddrPointSize','NamePointSize'];

  var origName = '';                         // loaded preset key ('' means New)
  var previewEl = document.getElementById('lp-preview');

  function gv(id) { var e = document.getElementById(id); return e ? e.value : ''; }
  function sv(id, val) { var e = document.getElementById(id); if (e) e.value = val; }

  function loadValues(obj) {
    FIELDS.forEach(function (k) { if (obj[k] !== undefined && obj[k] !== null) sv('f_' + k, obj[k]); });
  }
  function readParams() { var p = {}; FIELDS.forEach(function (k) { p[k] = gv('f_' + k); }); return p; }

  function readSamples() {
    var src = $('input[name=samplelang]:checked').val();
    if (src === 'basket') return basketSamples.length ? basketSamples : [{ japan: true, name: '', address: '' }];
    if (src === 'foreign') return [{ japan: false, name: gv('s_fname'), address: gv('s_faddr') }];
    return [{ japan: true, postalcode: gv('s_pc'), prefecture: gv('s_pref'),
              shikucho: gv('s_shikucho'), address: gv('s_addr'), name: gv('s_name') }];
  }

  function redraw() {
    LayoutPreview.renderLabelSheet(readParams(), readSamples(), previewEl,
      { wrapPc: document.getElementById('s_wrap_pc').checked });
  }

  function rebuildPresetOptions(selectKey) {
    var keys = Object.keys(presets).sort();
    var $sel = $('#f_preset').empty();
    keys.forEach(function (k) { $sel.append($('<option>').val(k).text(k)); });
    $sel.append($('<option>').val('__new__').text(<?=json_encode(_("New…"))?>));
    $sel.val(selectKey);
  }

  function updateSaveAsNew() {
    var changed = origName !== '' && $.trim(gv('f_LabelType')) !== origName;
    $('#btn_saveasnew').prop('disabled', !changed);
  }

  function selectPreset(key) {
    if (key === '__new__') {
      origName = ''; sv('f_LabelType', '');
      FIELDS.forEach(function (k) { if (k !== 'PaperSize') sv('f_' + k, ''); });   // start blank
      $('#starterrow').show(); $('#f_starter').val('');
      $('#btn_delete').prop('disabled', true);
    } else {
      origName = key; sv('f_LabelType', key); loadValues(presets[key]);
      $('#starterrow').hide();
      $('#btn_delete').prop('disabled', false);
    }
    updateSaveAsNew();
    redraw();
  }

  var toastTimer;
  function toast(msg) {
    clearTimeout(toastTimer);
    $('#status-msg').text(msg).fadeIn(120);
    toastTimer = setTimeout(function () { $('#status-msg').fadeOut(400); }, 1600);
  }

  function save(op) {
    var name = $.trim(gv('f_LabelType'));
    var data = { op: op, LabelType: name, orig: origName };
    FIELDS.forEach(function (k) { data[k] = gv('f_' + k); });
    $.post('labelprint_edit.php', data, function (resp) {
      if (resp.charAt(0) !== '*') { alert(resp); return; }
      toast(resp.substring(1));
      if (op === 'delete') {
        delete presets[origName];
        rebuildPresetOptions('__new__');
        selectPreset('__new__');
      } else {
        var saved = { LabelType: name };
        FIELDS.forEach(function (k) { saved[k] = gv('f_' + k); });
        if (op === 'save' && origName && origName !== name) delete presets[origName];
        presets[name] = saved;
        origName = name;
        rebuildPresetOptions(name);
        $('#starterrow').hide();
        $('#btn_delete').prop('disabled', false);
        updateSaveAsNew();
      }
    });
  }

  // --- events ---
  $('#f_preset').on('change', function () { selectPreset(this.value); });
  $('#f_starter').on('change', function () {
    if (this.value && STARTERS[this.value]) { loadValues(STARTERS[this.value]); redraw(); }
  });
  $('#f_LabelType').on('input', updateSaveAsNew);
  $('.lp-field').on('input change', redraw);
  $('.lp-sample').on('input change', function () {
    var src = $('input[name=samplelang]:checked').val();
    $('#sample_jp').toggle(src === 'japan');
    $('#sample_fg').toggle(src === 'foreign');
    $('#basket_hint').toggle(src === 'basket');
    redraw();
  });

  $('#btn_save').button().on('click', function () {
    if ($.trim(gv('f_LabelType')) === '') { alert(<?=json_encode(_("Please enter a label type name."))?>); return; }
    save('save');
  });
  $('#btn_saveasnew').button().on('click', function () {
    if ($.trim(gv('f_LabelType')) === '') { alert(<?=json_encode(_("Please enter a label type name."))?>); return; }
    save('saveasnew');
  });
  $('#btn_delete').button().on('click', function () {
    if (origName === '') return;
    $('#del_dialog p').text(<?=json_encode(_("Delete the layout \"%s\"? This cannot be undone."))?>.replace('%s', origName));
    $('#del_dialog').dialog({
      modal: true, resizable: false, width: 360,
      buttons: [
        { text: <?=json_encode(_("Delete"))?>, click: function () { $(this).dialog('close'); save('delete'); } },
        { text: <?=json_encode(_("Cancel"))?>, click: function () { $(this).dialog('close'); } }
      ]
    });
  });

  // --- init ---
  Object.keys(SAMPLE_DEFAULTS).forEach(function (id) {
    var e = document.getElementById(id);
    if (e && !e.value) e.value = SAMPLE_DEFAULTS[id];
  });
  selectPreset(initial);
  $('#f_preset').val(initial);
});
</script>
<?php
footer();
?>
