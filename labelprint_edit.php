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

// Renumber every layout's ListOrder (from 1) to match the drag-sorted names posted from the editor.
function lp_apply_order() {
  $order = json_decode($_POST['order'] ?? '', true);
  if (!is_array($order)) return;
  $i = 1;
  foreach ($order as $nm) {
    if (trim($nm) === '') continue;
    sqlquery_checked("UPDATE labelprint SET ListOrder=$i WHERE LabelType='" . h2d($nm) . "' LIMIT 1");
    $i++;
  }
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

  if ($name === '') { echo _('Please enter a layout name.'); exit; }
  $set = lp_fields();

  if ($op === 'saveasnew' || $orig === '') {                 // insert a brand-new preset
    if (lp_exists($name)) { echo sprintf(_('A layout named "%s" already exists.'), $name); exit; }
    // ListOrder=0 is a placeholder to satisfy the NOT NULL column; lp_apply_order() below resets it
    // (and every row) to its drag-sorted position.
    sqlquery_checked("INSERT INTO labelprint SET LabelType='" . h2d($name) . "', ListOrder=0, $set");
    lp_apply_order();
    echo '*' . _('Saved.');
    exit;
  }

  // op === 'save' — update the loaded row (a changed name is a rename of its primary key)
  if ($name !== $orig && lp_exists($name)) {
    echo sprintf(_('A layout named "%s" already exists.'), $name);
    exit;
  }
  sqlquery_checked("UPDATE labelprint SET LabelType='" . h2d($name) . "', $set WHERE LabelType='" . h2d($orig) . "' LIMIT 1");
  lp_apply_order();
  echo '*' . _('Saved.');
  exit;
}

/* ---------- normal page render ---------- */

$presets = [];
$r = sqlquery_checked("SELECT * FROM labelprint ORDER BY ListOrder, LabelType");
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
  echo '<label class="lp-row"><span>' . $label . ':</span>'
     . '<input type="number" step="' . $step . '" min="0" id="f_' . $id . '" class="lp-field" name="' . $id . "\"></label>\n";
}
?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Source+Sans+3:ital,wght@0,400;0,700;1,400&display=swap');
.lp-top { margin-bottom:1em; }
.lp-order-info { color:#666; }
.lp-order-num { font-weight:bold; color:#222; }
.lp-sortable { list-style:none; margin:0; padding:0; max-height:60vh; overflow-y:auto; }
.lp-sortable li { padding:0.3em 0.6em; margin:0.2em 0; border:1px solid #ccc; border-radius:3px;
  background:#f6f6f6; cursor:grab; user-select:none; }
.lp-sortable li.lp-current { border-color:var(--primary-medium); background:var(--highlight); font-weight:bold; }
.lp-sortable li.lp-placeholder { border-style:dashed; }
.lp-sortable li.ui-sortable-helper { cursor:grabbing; }
.lp-sort-gap { border:1px dashed var(--primary-medium); border-radius:3px; margin:0.2em 0; min-height:1.6em;
  background:color-mix(in srgb, var(--highlight) 45%, White); }
.lp-layout { display:flex; flex-wrap:wrap; gap:1.5em; align-items:flex-start; }
.lp-form { flex:1 1 320px; min-width:0; max-width:820px; }
.lp-preview-pane { flex:0 0 auto; position:sticky; top:5em; text-align:center; }
.lp-form fieldset { margin:0 0 1em; border:1px solid #ccc; border-radius:4px; }
.lp-form legend { font-weight:bold; padding:0 0.4em; }
.lp-row { display:flex; align-items:center; gap:0.5em; margin:0.35em 0; }
.lp-row > span { flex:0 0 auto; }
.lp-row input[type=text], .lp-row textarea { flex:1 1 auto; width:auto; min-width:0; }
.lp-row textarea { resize:vertical; font-family:inherit; font-size:0.9em; }
.lp-row input[type=number] { flex:0 0 4em; width:4em; }
.lp-row select { flex:0 1 auto; min-width:0; max-width:100%; }
/* textarea sits beside its label; wraps to label-above only when it can't stay ~300px wide.
   Small row-gap so a wrapped label hugs its own textarea rather than the next field. */
.lp-row-ta { flex-wrap:wrap; align-items:flex-start; row-gap:0.2em; }
.lp-row-ta textarea { flex:1 1 300px; min-width:0; }
/* Numeric fields flow and wrap; each label sizes to its own text (no fixed column) */
.lp-grid { display:flex; flex-wrap:wrap; gap:0.3em 1.4em; }
.lp-grid .lp-row { margin:0.3em 0; }
/* Preview-sample: postal code stays compact, address flows in beside it */
.lp-samplerow { display:flex; flex-wrap:wrap; gap:0.2em 1.4em; align-items:flex-start; margin:0.35em 0; }
.lp-samplerow > .lp-row { margin:0; }
.lp-samplerow .lp-addr { flex:1 1 300px; min-width:0; }
#s_pc { flex:0 0 6em; width:6em; }
.lp-buttons { margin:1em 0; display:flex; gap:0.75em; flex-wrap:wrap; }
.lp-preview-pane h3 { margin-top:0; }
#lp-preview { display:inline-block; }
.lp-pager { margin-top:0.5em; }
.lp-pager button { min-width:2.2em; }
.lp-svg { background:#fff; box-shadow:0 1px 6px rgba(0,0,0,.25); text-align:left; }
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
<p class="lp-hint"><?=_('Pick a saved layout to edit, or choose "New…" and start from a common product. The preview updates automatically.').' '.
  _("A red outline means the text overflows that label at the chosen text size.")?></p>

<div class="lp-top">
  <div class="lp-row" style="flex-wrap:wrap">
    <span><?=_("Layout")?>:</span>
    <select id="f_preset">
<?php
foreach ($presets as $k => $v) {
  echo '      <option value="' . htmlspecialchars($k, ENT_QUOTES) . '"'
     . ($k === $initial ? ' selected' : '') . '>' . htmlspecialchars($k, ENT_QUOTES) . "</option>\n";
}
?>
      <option value="__new__"<?=($initial === '__new__' ? ' selected' : '')?>><?=_("New…")?></option>
    </select>
    <span class="lp-order-info"><?=_('Menu order')?>: <span id="lp-order-num">—</span></span>
    <button type="button" id="btn_reorder"><?=_('Re-order…')?></button>
  </div>
  <div class="lp-row" id="starterrow" style="display:none">
    <span><?=_("Start from")?>:</span>
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

  <label class="lp-row" id="namerow"><span><?=_("Layout name")?>:</span>
    <input type="text" id="f_LabelType" name="LabelType" maxlength="100" style="flex:0 1 340px"></label>
</div>

<div class="lp-layout">
  <div class="lp-form">
    <fieldset>
      <legend><?=_("Sheet")?></legend>
      <label class="lp-row"><span><?=_("Paper size")?>:</span>
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
      <legend><?=_("Label text")?></legend>
      <div class="lp-grid">
<?php
lp_num('AddrMarginLeft', _('Text inset left'));
lp_num('AddrMarginRight', _('Text inset right'));
lp_num('AddrPointSize', _('Address text size (Japan)'), '1');
lp_num('NamePointSize', _('Name text size (Japan)'), '1');
lp_num('NJAddrPointSize', _('Address text size (foreign)'), '1');
?>
      </div>
    </fieldset>

    <fieldset>
      <legend><?=_("Preview sample")?></legend>
      <div class="lp-row" style="flex-wrap:wrap;row-gap:0.2em">
        <label><input type="radio" name="samplelang" value="japan" class="lp-sample" checked> <?=_("Japan (sample)")?></label>
        <label><input type="radio" name="samplelang" value="foreign" class="lp-sample"> <?=_("Foreign (sample)")?></label>
        <label<?=($basketCount ? '' : ' style="color:#999"')?>><input type="radio" name="samplelang" value="basket" class="lp-sample"<?=($basketCount ? '' : ' disabled')?>> <?=sprintf(_("From Basket (%d)"), $basketCount)?></label>
      </div>
      <div class="lp-row"><label><input type="checkbox" id="s_wrap_pc" class="lp-sample" checked> <?=_("Japan postal code on its own line")?></label></div>
      <div id="sample_jp">
        <div class="lp-samplerow">
          <label class="lp-row"><span><?=_("Postal code")?>:</span><input type="text" id="s_pc" class="lp-sample" value="859-3616"></label>
          <label class="lp-row lp-row-ta lp-addr"><span><?=_("Address")?>:</span><textarea id="s_addr" class="lp-sample" rows="3"></textarea></label>
        </div>
        <label class="lp-row lp-row-ta"><span><?=_("Label Name")?>:</span><textarea id="s_name" class="lp-sample" rows="3"></textarea></label>
      </div>
      <div id="sample_fg" style="display:none">
        <label class="lp-row lp-row-ta"><span><?=_("Label Name")?>:</span><textarea id="s_fname" class="lp-sample" rows="2"></textarea></label>
        <label class="lp-row lp-row-ta"><span><?=_("Address")?>:</span><textarea id="s_faddr" class="lp-sample" rows="3"></textarea></label>
      </div>
      <p class="lp-hint" id="basket_hint" style="display:none"><?=_('Paging through your Basket records.')?></p>
    </fieldset>

    <div class="lp-buttons">
      <button type="button" id="btn_save"><?=_("Save")?></button>
      <button type="button" id="btn_saveasnew"><?=_("Save as New")?></button>
      <button type="button" id="btn_delete"><?=_("Delete")?></button>
    </div>
  </div>

  <div class="lp-preview-pane">
    <h3><?=_("Preview")?></h3>
    <div id="lp-preview"></div>
    <div class="lp-pager" id="lp-pager" style="display:none">
      <button type="button" id="pg_prev">&#8249;</button>
      <span id="pg_label"></span>
      <button type="button" id="pg_next">&#8250;</button>
    </div>
  </div>
</div>

<div id="del_dialog" title="<?=_("Delete Layout")?>" style="display:none"><p></p></div>
<div id="order_dialog" title="<?=_('Menu order')?>" style="display:none">
  <p class="lp-hint"><?=_('Drag to arrange. The new order takes effect when you save the layout.')?></p>
  <ol id="lp-order" class="lp-sortable"></ol>
</div>
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
    s_addr:  '長崎県東彼杵郡川棚町白石郷 1-18\nグリーンハイツ川棚 203号室',
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

  var BLANK = { japan: true, name: '', address: '' };

  var origName = '';                         // loaded preset key ('' means New)
  var pageIdx = 0;
  var previewEl = document.getElementById('lp-preview');

  function gv(id) { var e = document.getElementById(id); return e ? e.value : ''; }
  function sv(id, val) { var e = document.getElementById(id); if (e) e.value = val; }

  function loadValues(obj) {
    FIELDS.forEach(function (k) { if (obj[k] !== undefined && obj[k] !== null) sv('f_' + k, obj[k]); });
  }
  function readParams() { var p = {}; FIELDS.forEach(function (k) { p[k] = gv('f_' + k); }); return p; }

  function sampleSrc() { return $('input[name=samplelang]:checked').val(); }

  function readSamples() {
    var src = sampleSrc();
    if (src === 'basket') return basketSamples.length ? basketSamples : [BLANK];
    if (src === 'foreign') return [{ japan: false, name: gv('s_fname'), address: gv('s_faddr') }];
    return [{ japan: true, postalcode: gv('s_pc'), address: gv('s_addr'), name: gv('s_name') }];
  }

  function perPage() {
    var r = Math.max(0, Math.round(parseFloat(gv('f_NumRows')) || 0));
    var c = Math.max(0, Math.round(parseFloat(gv('f_NumCols')) || 0));
    return r * c;
  }
  function pageCount() {
    var per = perPage();
    if (sampleSrc() !== 'basket' || !per) return 1;
    return Math.max(1, Math.ceil(basketSamples.length / per));
  }

  function redraw() {
    var samples = readSamples(), per = perPage(), pages = pageCount();
    if (pageIdx >= pages) pageIdx = 0;
    // Basket rows are real records: print each once and leave the rest of the sheet empty, the way
    // it would really come out. The two canned samples repeat to fill the sheet instead.
    if (sampleSrc() === 'basket' && per) {
      samples = samples.slice(pageIdx * per, pageIdx * per + per);
      while (samples.length < per) samples.push(BLANK);
    }
    LayoutPreview.renderLabelSheet(readParams(), samples, previewEl,
      { wrapPc: document.getElementById('s_wrap_pc').checked });
    $('#lp-pager').toggle(pages > 1);
    $('#basket_hint').toggle(pages > 1);
    if (pages > 1) $('#pg_label').text((pageIdx + 1) + ' / ' + pages);
  }

  function rebuildPresetOptions(selectKey) {
    var $sel = $('#f_preset').empty();
    orderedNames().forEach(function (k) { $sel.append($('<option>').val(k).text(k)); });
    $sel.append($('<option>').val('__new__').text(<?=json_encode(_("New…"))?>));
    $sel.val(selectKey);
  }

  // Save writes back to the loaded row, so it needs one; Save as New creates a row, so it needs a
  // name that isn't taken by the loaded one (any name at all when starting from New).
  function updateButtons() {
    var isNew = origName === '';
    var renamed = !isNew && $.trim(gv('f_LabelType')) !== origName;
    $('#btn_save').button('option', 'disabled', isNew);
    $('#btn_saveasnew').button('option', 'disabled', !isNew && !renamed);
    $('#btn_delete').button('option', 'disabled', isNew);
  }

  // --- pulldown order (drag to arrange) ---
  var NEWLABEL = <?=json_encode(_('(new layout)'))?>;
  function orderedNames() {
    var keys = Object.keys(presets);
    keys.sort(function (a, b) { return (presets[a].ListOrder - presets[b].ListOrder) || a.localeCompare(b); });
    return keys;
  }
  // Rebuild the sortable list from presets. The row being edited is marked lp-current; when creating
  // a new layout it appears as a draggable dashed placeholder (data-name='') the user can position.
  function rebuildOrderList() {
    var $ol = $('#lp-order').empty(), isNew = (origName === '');
    orderedNames().forEach(function (k) {
      var $li = $('<li>').attr('data-name', k).text(k);
      if (!isNew && k === origName) $li.addClass('lp-current');
      $ol.append($li);
    });
    if (isNew) {
      var nm = $.trim(gv('f_LabelType'));
      $ol.prepend($('<li>').addClass('lp-current lp-placeholder').attr('data-name', '').text(nm || NEWLABEL));
    }
  }
  function updateCurrentOrderLabel() {
    $('#lp-order li.lp-current').text($.trim(gv('f_LabelType')) || NEWLABEL);
  }
  // The dragged order of names, substituting the saved name for the current row. For "save as new"
  // the source row stays put and the new name is inserted right after it.
  function collectOrder(op, savedName) {
    var order = [], seen = {};
    function push(nm) { if (nm && !seen[nm]) { seen[nm] = 1; order.push(nm); } }
    $('#lp-order li').each(function () {
      if (this.classList.contains('lp-current')) {
        if (op === 'saveasnew') { push(this.getAttribute('data-name')); push(savedName); return; }
        push(savedName); return;
      }
      push(this.getAttribute('data-name'));
    });
    return order;
  }
  // The compact clue beside the Layout menu: the current/new layout's position in the (possibly
  // just-dragged) order list — i.e. the spot it will occupy once the layout is saved.
  function updateOrderNum() {
    var idx = $('#lp-order li.lp-current').index();
    $('#lp-order-num').text(idx >= 0 ? idx + 1 : '—');
  }

  function selectPreset(key) {
    pageIdx = 0;
    if (key === '__new__') {
      origName = ''; sv('f_LabelType', '');
      FIELDS.forEach(function (k) { if (k !== 'PaperSize') sv('f_' + k, ''); });   // start blank
      $('#starterrow').show(); $('#f_starter').val('');
    } else {
      origName = key; sv('f_LabelType', key); loadValues(presets[key]);
      $('#starterrow').hide();
    }
    updateButtons();
    rebuildOrderList();
    updateOrderNum();
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
    var order = op === 'delete' ? [] : collectOrder(op, name);
    var data = { op: op, LabelType: name, orig: origName, order: JSON.stringify(order) };
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
        order.forEach(function (nm, i) { if (presets[nm]) presets[nm].ListOrder = i + 1; });  // server renumbered from 1
        rebuildPresetOptions(name);
        rebuildOrderList();
        updateOrderNum();
        $('#starterrow').hide();
        updateButtons();
      }
    });
  }

  // --- events ---
  $('#f_preset').on('change', function () { selectPreset(this.value); });
  $('#f_starter').on('change', function () {
    if (this.value && STARTERS[this.value]) { loadValues(STARTERS[this.value]); redraw(); }
  });
  $('#f_LabelType').on('input', function () { updateButtons(); updateCurrentOrderLabel(); });
  $('.lp-field').on('input change', redraw);
  $('.lp-sample').on('input change', function () {
    var src = sampleSrc();
    $('#sample_jp').toggle(src === 'japan');
    $('#sample_fg').toggle(src === 'foreign');
    pageIdx = 0;
    redraw();
  });
  $('#pg_prev').on('click', function () { var n = pageCount(); pageIdx = (pageIdx - 1 + n) % n; redraw(); });
  $('#pg_next').on('click', function () { var n = pageCount(); pageIdx = (pageIdx + 1) % n; redraw(); });

  $('#btn_save').button().on('click', function () {
    if ($.trim(gv('f_LabelType')) === '') { alert(<?=json_encode(_('Please enter a layout name.'))?>); return; }
    save('save');
  });
  $('#btn_saveasnew').button().on('click', function () {
    if ($.trim(gv('f_LabelType')) === '') { alert(<?=json_encode(_('Please enter a layout name.'))?>); return; }
    save('saveasnew');
  });
  $('#btn_delete').button().on('click', function () {
    if (origName === '') return;
    $('#del_dialog p').text(<?=json_encode(_('Delete the layout "%s"?'))?>.replace('%s', origName));
    $('#del_dialog').dialog({
      modal: true, resizable: false, width: 360,
      buttons: [
        { text: <?=json_encode(_("Delete"))?>, click: function () { $(this).dialog('close'); save('delete'); } },
        { text: <?=json_encode(_("Cancel"))?>, click: function () { $(this).dialog('close'); } }
      ]
    });
  });
  // Re-order opens the drag list in a dialog; the arrangement is applied with the next Save. The list
  // keeps its state between opens (no rebuild here), so a pending drag survives until Save or Cancel.
  $('#btn_reorder').button().on('click', function () {
    $('#lp-order').sortable('refresh');
    $('#order_dialog').dialog({
      modal: true, width: 380,
      buttons: [
        { text: <?=json_encode(_('Done'))?>, click: function () { updateOrderNum(); $(this).dialog('close'); } },
        { text: <?=json_encode(_('Cancel'))?>, click: function () { rebuildOrderList(); updateOrderNum(); $(this).dialog('close'); } }
      ]
    });
  });

  // --- init ---
  Object.keys(SAMPLE_DEFAULTS).forEach(function (id) {
    var e = document.getElementById(id);
    if (e && !e.value) e.value = SAMPLE_DEFAULTS[id];
  });
  $('#lp-order').sortable({ placeholder: 'lp-sort-gap', forcePlaceholderSize: true, axis: 'y' });
  selectPreset(initial);
  $('#f_preset').val(initial);
});
</script>
<?php
footer();
?>
