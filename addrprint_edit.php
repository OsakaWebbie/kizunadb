<?php
/*****
addrprint_edit.php — editor + live visual preview for envelope/postcard layouts (`addrprint`).

Part of the envelope/label layout UI (GitHub issue #52). Self-contained: renders the form + SVG
preview AND handles its own save/delete POST at the top (no ajax_action.php case). The live mock is
drawn client-side by js/layout_preview.js (renderEnvelope), re-running print_addr.php's picture math.
*****/
include("functions.php");
include("accesscontrol.php");

/* ---------- serve a client graphics file (return-address PNGs) for the live preview ---------- */
if (isset($_GET['img'])) {
  $img = (string)$_GET['img'];
  if (strpos($img, '..') === false
      && preg_match('#^/var/www/kizunadb/(?:staging/)?client/[^/]+/graphics/[^/]+\.(png|jpe?g|gif)$#i', $img)
      && is_file($img)) {
    $path = $img;                                   // absolute path from the LaTeX (validated)
  } else {
    $path = CLIENT_PATH . '/graphics/' . basename($img);   // fall back to current client's graphics
  }
  if (is_file($path)) {
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    header('Content-Type: ' . ($ext === 'png' ? 'image/png' : ($ext === 'gif' ? 'image/gif' : 'image/jpeg')));
    header('Cache-Control: private, max-age=60');
    readfile($path);
  } else {
    http_response_code(404);
  }
  exit;
}

/* ---------- in-file save / delete handler (AJAX POST) ---------- */

function ap_fields() {
  $stamps = ['none', 'betsunou', 'yuumail_betsunou', 'kounou', 'yuumail_kounou'];
  $stamp = in_array($_POST['DefaultStamp'] ?? 'none', $stamps, true) ? $_POST['DefaultStamp'] : 'none';
  $ints = ['ListOrder', 'PaperHeight', 'PaperWidth', 'PaperBottomMargin', 'PaperLeftMargin', 'PCPointSize',
    'Tategaki', 'AddrPointSize', 'AddrLineLength', 'AddrPositionX', 'AddrPositionY', 'NamePointSize',
    'NameLineLength', 'NameWidth', 'NamePositionX', 'NamePositionY', 'NJAddrPointSize', 'NJAddrHeight',
    'NJAddrPositionX', 'NJAddrPositionY', 'NJRetAddrLeftMargin', 'NJRetAddrTopMargin'];
  $decs = ['PCTopMargin', 'PCLeftMargin', 'PCSpacing', 'PCExtraSpace'];
  $txt = ['RetAddrContent', 'NJRetAddrContent', 'Custom'];
  $f = ["DefaultStamp='" . h2d($stamp) . "'"];
  foreach ($ints as $k) $f[] = "`$k`=" . intval($_POST[$k] ?? 0);
  foreach ($decs as $k) $f[] = "`$k`=" . (float)($_POST[$k] ?? 0);
  foreach ($txt as $k) $f[] = "`$k`='" . h2d($_POST[$k] ?? '') . "'";
  return implode(',', $f);
}

function ap_exists($name) {
  $r = sqlquery_checked("SELECT 1 FROM addrprint WHERE AddrPrintName='" . h2d($name) . "'");
  return mysqli_num_rows($r) > 0;
}

if (!empty($_POST['op'])) {
  header('Content-Type: text/plain; charset=utf-8');
  $op = $_POST['op'];
  $name = trim($_POST['AddrPrintName'] ?? '');
  $orig = trim($_POST['orig'] ?? '');

  if ($op === 'delete') {
    if ($orig === '') { echo _('Nothing to delete.'); exit; }
    sqlquery_checked("DELETE FROM addrprint WHERE AddrPrintName='" . h2d($orig) . "' LIMIT 1");
    echo '*' . _('Deleted.');
    exit;
  }

  if ($name === '') { echo _('Please enter a layout name.'); exit; }
  $set = ap_fields();

  if ($op === 'saveasnew' || $orig === '') {
    if (ap_exists($name)) { echo sprintf(_('A layout named "%s" already exists.'), $name); exit; }
    sqlquery_checked("INSERT INTO addrprint SET AddrPrintName='" . h2d($name) . "', $set");
    echo '*' . _('Saved.');
    exit;
  }

  if ($name !== $orig && ap_exists($name)) {
    echo sprintf(_('A layout named "%s" already exists.'), $name);
    exit;
  }
  sqlquery_checked("UPDATE addrprint SET AddrPrintName='" . h2d($name) . "', $set WHERE AddrPrintName='" . h2d($orig) . "' LIMIT 1");
  echo '*' . _('Saved.');
  exit;
}

/* ---------- normal page render ---------- */

$presets = [];
$r = sqlquery_checked("SELECT * FROM addrprint ORDER BY ListOrder, AddrPrintName");
while ($row = mysqli_fetch_object($r)) $presets[$row->AddrPrintName] = $row;

// Return-address blocks are client-specific and shared across layouts: default a New preset's
// return address from an existing row so the user isn't retyping LaTeX.
$templateRet = ''; $templateNJRet = '';
foreach ($presets as $v) {
  if ($templateRet === '' && trim($v->RetAddrContent) !== '') $templateRet = $v->RetAddrContent;
  if ($templateNJRet === '' && trim($v->NJRetAddrContent) !== '') $templateNJRet = $v->NJRetAddrContent;
}
// Generic placeholders when the client has no existing layout to copy from.
if ($templateRet === '')
  $templateRet = "\\fontsize{10}{11}\\selectfont\\makebox(60,12)[rt]{\n\\begin{minipage}<y>[t]{60mm}\n"
    . "〒000-0000 何何県何何市\\\\\n何何区何何町 0-0-00\\\\\nYour Name\n\\end{minipage}}";
if ($templateNJRet === '')
  $templateNJRet = "\\fontsize{10}{11}\\selectfont\\makebox(35,80)[rt]{\n\\begin{minipage}<t>[t]{80mm}\n"
    . "Your Name\\\\\n0-0-00 Something-machi\\\\\nSomething-ku, Somewhere 000-0000\\\\\nJAPAN\n\\end{minipage}}";

// Real records from the Basket (one envelope each; page through them in the preview).
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

$initial = $_GET['name'] ?? '';
if (!isset($presets[$initial])) $initial = '__new__';   // default to New on first open

pageheader(_("Envelope Layout Editor"), 1);

function ap_num($id, $label, $step = '1') {
  echo '<label class="lp-row"><span>' . $label . '</span>'
     . '<input type="number" step="' . $step . '" id="f_' . $id . '" class="lp-field" name="' . $id . "\"></label>\n";
}
?>
<style>
.lp-layout { display:flex; flex-wrap:wrap; gap:1.5em; align-items:flex-start; }
.lp-form { flex:1 1 380px; max-width:560px; }
.lp-preview-pane { flex:0 0 auto; position:sticky; top:1em; text-align:center; }
.lp-form fieldset { margin:0 0 1em; border:1px solid #ccc; border-radius:4px; }
.lp-form legend { font-weight:bold; padding:0 0.4em; }
.lp-row { display:flex; align-items:center; gap:0.75em; margin:0.35em 0; }
.lp-row > span { flex:0 0 8.5em; }
.lp-row input[type=text], .lp-row textarea { flex:1 1 auto; width:auto; min-width:0; }
.lp-row textarea { resize:vertical; font-family:monospace; font-size:0.85em; }
.lp-row input[type=number] { flex:0 0 5em; width:5em; }
.lp-row select { flex:0 0 auto; }
.lp-row-ta { align-items:flex-start; }
.lp-grid { display:grid; grid-template-columns:1fr 1fr; gap:0 1.5em; }
.lp-buttons { margin:1em 0; display:flex; gap:0.75em; flex-wrap:wrap; }
.lp-preview-pane h3 { margin-top:0; }
#lp-preview { display:inline-block; }
.lp-pager { margin-top:0.5em; }
.lp-pager button { min-width:2.2em; }
.lp-svg { background:#fff; box-shadow:0 1px 6px rgba(0,0,0,.25); text-align:left; }
.lp-svg .lp-paper { fill:#fff; stroke:#bbb; stroke-width:1; }
.lp-svg .lp-block { fill:none; stroke: #0000ff; stroke-width:0.5; stroke-dasharray:2 2; }
.lp-svg .lp-name { stroke: #006e00; }
.lp-svg .lp-ret { stroke: #a600a6; }
.lp-svg .lp-block.lp-overflow { stroke:Red; stroke-width:1; stroke-dasharray:none; }
.lp-svg .lp-text { overflow:hidden; color:#222; line-height:1.15; }
.lp-svg .lp-vwrap { writing-mode:vertical-rl; text-orientation:mixed; height:100%; }
.lp-svg .lp-vname { display:flex; justify-content:center; align-items:flex-start; }
.lp-svg .lp-sideways { writing-mode:vertical-rl; text-orientation:sideways; height:100%; }
.lp-svg .lp-pcdigit { fill:#000; font-family:sans-serif; }
.lp-svg .lp-rettext { font-size:7px; white-space:pre-line; overflow:hidden; line-height:1.15; }
.lp-hint { color:#666; font-size:0.85em; }
#status-msg { position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); padding:10px 16px;
  background:#2e7d32; color:#fff; border-radius:4px; box-shadow:0 2px 8px rgba(0,0,0,.2); z-index:10000; display:none; }
</style>

<h1 id="title"><?=_("Envelope Layout Editor")?></h1>
<p class="lp-hint"><?=_("Pick a saved layout to edit, or choose \"New…\". The preview updates as you type. Dashed boxes show where each block is positioned; a red box means the text overflows it.")?></p>

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
        <option value=""><?=_("Select a common size…")?></option>
        <option value="naga3_tate"><?=_("長形3号 縦書き (235×120)")?></option>
        <option value="naga3_yoko"><?=_("長形3号 横書き (235×120)")?></option>
        <option value="kaku6_yoko"><?=_("角形6号 横書き (229×162)")?></option>
        <option value="hagaki"><?=_("はがき (148×100)")?></option>
        <option value="nenga"><?=_("年賀状 (148×100)")?></option>
      </select>
    </div>

    <label class="lp-row"><span><?=_("Layout name")?></span>
      <input type="text" id="f_AddrPrintName" name="AddrPrintName" maxlength="40"></label>

    <fieldset>
      <legend><?=_("Paper")?></legend>
      <div class="lp-grid">
<?php
ap_num('PaperWidth', _('Paper width'));
ap_num('PaperHeight', _('Paper height'));
ap_num('PaperLeftMargin', _('Left margin'));
ap_num('PaperBottomMargin', _('Bottom margin'));
?>
      </div>
      <label class="lp-row"><span><?=_("Vertical (tategaki)")?></span>
        <span style="flex:1 1 auto"><input type="checkbox" id="f_Tategaki"> <?=_("Japanese address & name run vertically")?></span></label>
    </fieldset>

    <fieldset>
      <legend><?=_("Postal code")?></legend>
      <div class="lp-grid">
<?php
ap_num('PCPointSize', _('Font size (pt)'));
ap_num('PCLeftMargin', _('Left margin'), '0.1');
ap_num('PCTopMargin', _('From bottom edge'), '0.1');
ap_num('PCSpacing', _('Digit spacing'), '0.1');
ap_num('PCExtraSpace', _('Extra gap at hyphen'), '0.1');
?>
      </div>
    </fieldset>

    <fieldset>
      <legend><?=_("Address block")?></legend>
      <div class="lp-grid">
<?php
ap_num('AddrPointSize', _('Font size (pt)'));
ap_num('AddrLineLength', _('Line length / wrap'));
ap_num('AddrPositionX', _('Position X (from left)'));
ap_num('AddrPositionY', _('Position Y (from bottom)'));
?>
      </div>
    </fieldset>

    <fieldset>
      <legend><?=_("Name block")?></legend>
      <div class="lp-grid">
<?php
ap_num('NamePointSize', _('Font size (pt)'));
ap_num('NameLineLength', _('Line length / wrap'));
ap_num('NameWidth', _('Box width'));
ap_num('NamePositionX', _('Center X (from left)'));
ap_num('NamePositionY', _('Position Y (from bottom)'));
?>
      </div>
    </fieldset>

    <fieldset>
      <legend><?=_("Return address (LaTeX)")?></legend>
      <label class="lp-row lp-row-ta"><span><?=_("Content")?></span><textarea id="f_RetAddrContent" class="lp-field" rows="4"></textarea></label>
      <p class="lp-hint"><?=_("Raw LaTeX at the paper's left/bottom margin. A \\includegraphics image renders in the preview — set width=Nmm or height=Nmm for exact sizing. Otherwise the PNG's DPI is used. Text shows at its \\fontsize.")?></p>
    </fieldset>

    <fieldset>
      <legend><?=_("Non-Japan (foreign) addresses")?></legend>
      <div class="lp-grid">
<?php
ap_num('NJAddrPointSize', _('Font size (pt)'));
ap_num('NJAddrHeight', _('Block height'));
ap_num('NJAddrPositionX', _('Position X (from left)'));
ap_num('NJAddrPositionY', _('Position Y (from bottom)'));
ap_num('NJRetAddrLeftMargin', _('Return addr. X'));
ap_num('NJRetAddrTopMargin', _('Return addr. Y'));
?>
      </div>
      <label class="lp-row lp-row-ta"><span><?=_("Return address")?></span><textarea id="f_NJRetAddrContent" class="lp-field" rows="3"></textarea></label>
    </fieldset>

    <fieldset>
      <legend><?=_("Advanced")?></legend>
      <label class="lp-row"><span><?=_("Pulldown order")?></span><input type="number" step="1" id="f_ListOrder" class="lp-field" name="ListOrder" style="flex:0 0 5em;width:5em"></label>
      <label class="lp-row"><span><?=_("Default stamp")?></span>
        <select id="f_DefaultStamp" class="lp-field" name="DefaultStamp">
          <option value="none"><?=_("None")?></option>
          <option value="betsunou"><?=_("Standard mail")?></option>
          <option value="yuumail_betsunou"><?=_("'Yuu-mail'")?></option>
          <option value="kounou"><?=_("Standard mail w/ contract")?></option>
          <option value="yuumail_kounou"><?=_("'Yuu-mail' w/ contract")?></option>
        </select></label>
      <label class="lp-row"><span><?=_("Custom code")?></span><input type="text" id="f_Custom" name="Custom" maxlength="255"></label>
    </fieldset>

    <fieldset>
      <legend><?=_("Preview sample")?></legend>
      <div class="lp-row" style="flex-wrap:wrap">
        <label><input type="radio" name="samplelang" value="japan" class="lp-sample" checked> <?=_("Japan (sample)")?></label>
        <label><input type="radio" name="samplelang" value="foreign" class="lp-sample"> <?=_("Foreign (sample)")?></label>
        <label<?=($basketCount ? '' : ' style="color:#999"')?>><input type="radio" name="samplelang" value="basket" class="lp-sample"<?=($basketCount ? '' : ' disabled')?>> <?=sprintf(_("From Basket (%d)"), $basketCount)?></label>
      </div>
      <div class="lp-row"><label><input type="checkbox" id="s_kanji" class="lp-sample" checked> <?=_("Use kanji for numbers (vertical)")?></label></div>
      <div id="sample_jp">
        <label class="lp-row"><span><?=_("Postal code")?></span><input type="text" id="s_pc" class="lp-sample" value="859-3616"></label>
        <label class="lp-row"><span><?=_("Prefecture")?></span><input type="text" id="s_pref" class="lp-sample" value="長崎県"></label>
        <label class="lp-row"><span><?=_("City, etc.")?></span><input type="text" id="s_shikucho" class="lp-sample" value="東彼杵郡川棚町白石郷"></label>
        <label class="lp-row lp-row-ta"><span><?=_("Address")?></span><textarea id="s_addr" class="lp-sample" rows="2"></textarea></label>
        <label class="lp-row lp-row-ta"><span><?=_("Label Name")?></span><textarea id="s_name" class="lp-sample" rows="3"></textarea></label>
      </div>
      <div id="sample_fg" style="display:none">
        <label class="lp-row lp-row-ta"><span><?=_("Label Name")?></span><textarea id="s_fname" class="lp-sample" rows="2"></textarea></label>
        <label class="lp-row lp-row-ta"><span><?=_("Address")?></span><textarea id="s_faddr" class="lp-sample" rows="3"></textarea></label>
      </div>
      <p class="lp-hint" id="basket_hint" style="display:none"><?=_("Paging through your Basket records — one envelope each.")?></p>
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
    <div class="lp-pager" id="lp-pager" style="display:none">
      <button type="button" id="pg_prev">&#8249;</button>
      <span id="pg_label"></span>
      <button type="button" id="pg_next">&#8250;</button>
    </div>
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
  var templateRet = <?=json_encode($templateRet, JSON_UNESCAPED_UNICODE)?>;
  var templateNJRet = <?=json_encode($templateNJRet, JSON_UNESCAPED_UNICODE)?>;

  var SAMPLE_DEFAULTS = {
    s_addr:  '1-18\nグリーンハイツ川棚 203号室',
    s_name:  '山田 太郎様\n山田 由美子様\nご家族の皆様',
    s_fname: 'Mr. & Mrs. John Q. Sample',
    s_faddr: '123 Main Street, Apt. 4\nSpringfield, IL 62704\nU.S.A.'
  };

  // Starter catalog: common Japanese envelope/postcard sizes (geometry only; the return address
  // comes from the New-preset carry-over). Values captured from real presets; postal-code numbers
  // are tuned to the red boxes pre-printed on those products, so edit with care.
  var STARTERS = {
    naga3_tate: {Tategaki:1,PaperHeight:235,PaperWidth:120,PaperBottomMargin:10,PaperLeftMargin:10,
      PCPointSize:20,PCTopMargin:219,PCLeftMargin:69,PCSpacing:6.8,PCExtraSpace:1.0,
      AddrPointSize:18,AddrLineLength:160,AddrPositionX:100,AddrPositionY:200,NamePointSize:30,NameLineLength:150,NameWidth:60,NamePositionX:60,NamePositionY:190,
      NJAddrPointSize:28,NJAddrHeight:130,NJAddrPositionX:75,NJAddrPositionY:160,NJRetAddrLeftMargin:80,NJRetAddrTopMargin:150,DefaultStamp:'none'},
    naga3_yoko: {Tategaki:0,PaperHeight:235,PaperWidth:120,PaperBottomMargin:10,PaperLeftMargin:10,
      PCPointSize:20,PCTopMargin:219,PCLeftMargin:69,PCSpacing:6.8,PCExtraSpace:1.0,
      AddrPointSize:16,AddrLineLength:100,AddrPositionX:10,AddrPositionY:170,NamePointSize:22,NameLineLength:100,NameWidth:90,NamePositionX:15,NamePositionY:140,
      NJAddrPointSize:28,NJAddrHeight:130,NJAddrPositionX:75,NJAddrPositionY:160,NJRetAddrLeftMargin:80,NJRetAddrTopMargin:150,DefaultStamp:'none'},
    kaku6_yoko:    {Tategaki:0,PaperHeight:229,PaperWidth:162,PaperBottomMargin:10,PaperLeftMargin:10,
      PCPointSize:28,PCTopMargin:200,PCLeftMargin:80,PCSpacing:8.0,PCExtraSpace:4.0,
      AddrPointSize:18,AddrLineLength:130,AddrPositionX:15,AddrPositionY:170,NamePointSize:26,NameLineLength:130,NameWidth:80,NamePositionX:30,NamePositionY:170,
      NJAddrPointSize:18,NJAddrHeight:100,NJAddrPositionX:90,NJAddrPositionY:190,NJRetAddrLeftMargin:122,NJRetAddrTopMargin:143,DefaultStamp:'none'},
    hagaki:     {Tategaki:1,PaperHeight:148,PaperWidth:100,PaperBottomMargin:8,PaperLeftMargin:8,
      PCPointSize:20,PCTopMargin:132,PCLeftMargin:47,PCSpacing:6.8,PCExtraSpace:0.9,
      AddrPointSize:14,AddrLineLength:100,AddrPositionX:90,AddrPositionY:125,NamePointSize:20,NameLineLength:85,NameWidth:50,NamePositionX:50,NamePositionY:110,
      NJAddrPointSize:18,NJAddrHeight:105,NJAddrPositionX:62,NJAddrPositionY:115,NJRetAddrLeftMargin:62,NJRetAddrTopMargin:65,DefaultStamp:'none'},
    nenga:      {Tategaki:1,PaperHeight:148,PaperWidth:100,PaperBottomMargin:8,PaperLeftMargin:8,
      PCPointSize:20,PCTopMargin:132,PCLeftMargin:47,PCSpacing:6.8,PCExtraSpace:0.9,
      AddrPointSize:14,AddrLineLength:100,AddrPositionX:90,AddrPositionY:125,NamePointSize:20,NameLineLength:85,NameWidth:50,NamePositionX:60,NamePositionY:115,
      NJAddrPointSize:18,NJAddrHeight:105,NJAddrPositionX:62,NJAddrPositionY:115,NJRetAddrLeftMargin:62,NJRetAddrTopMargin:65,DefaultStamp:'none'}
  };
  function loadStarter(o) {
    Object.keys(o).forEach(function (k) {
      if (k === 'Tategaki') document.getElementById('f_Tategaki').checked = (o.Tategaki == 1);
      else sv('f_' + k, o[k]);                                  // geometry only — return address untouched
    });
    document.getElementById('s_kanji').checked = document.getElementById('f_Tategaki').checked;
  }

  // All addrprint fields except the key (AddrPrintName) and Tategaki (checkbox, handled apart).
  var FIELDS = ['ListOrder','DefaultStamp','PaperHeight','PaperWidth','PaperBottomMargin','PaperLeftMargin',
    'PCPointSize','PCTopMargin','PCLeftMargin','PCSpacing','PCExtraSpace','AddrPointSize','AddrLineLength',
    'AddrPositionX','AddrPositionY','NamePointSize','NameLineLength','NameWidth','NamePositionX','NamePositionY',
    'RetAddrContent','NJAddrPointSize','NJAddrHeight','NJAddrPositionX','NJAddrPositionY','NJRetAddrLeftMargin',
    'NJRetAddrTopMargin','NJRetAddrContent','Custom'];

  var origName = '';
  var pageIdx = 0;
  var previewEl = document.getElementById('lp-preview');

  function gv(id) { var e = document.getElementById(id); return e ? e.value : ''; }
  function sv(id, val) { var e = document.getElementById(id); if (e) e.value = val; }
  function tategaki() { return document.getElementById('f_Tategaki').checked; }

  function loadValues(obj) {
    FIELDS.forEach(function (k) { if (obj[k] !== undefined && obj[k] !== null) sv('f_' + k, obj[k]); });
    document.getElementById('f_Tategaki').checked = (String(obj.Tategaki) == '1');
  }
  function readParams() {
    var p = {}; FIELDS.forEach(function (k) { p[k] = gv('f_' + k); });
    p.Tategaki = tategaki() ? 1 : 0;
    return p;
  }

  function readSamples() {
    var src = $('input[name=samplelang]:checked').val();
    if (src === 'basket') return basketSamples.length ? basketSamples : [{ japan: true, name: '', address: '' }];
    if (src === 'foreign') return [{ japan: false, name: gv('s_fname'), address: gv('s_faddr') }];
    return [{ japan: true, postalcode: gv('s_pc'), prefecture: gv('s_pref'),
              shikucho: gv('s_shikucho'), address: gv('s_addr'), name: gv('s_name') }];
  }

  function redraw() {
    var samples = readSamples();
    if (pageIdx >= samples.length) pageIdx = 0;
    LayoutPreview.renderEnvelope(readParams(), samples[pageIdx], previewEl,
      { maxWidth: 380, maxHeight: 600, kanjiNumbers: document.getElementById('s_kanji').checked,
        imgBase: 'addrprint_edit.php?img=', stampBase: 'graphics/' });
    if (samples.length > 1) {
      $('#lp-pager').show();
      $('#pg_label').text((pageIdx + 1) + ' / ' + samples.length);
    } else {
      $('#lp-pager').hide();
    }
  }

  function rebuildPresetOptions(selectKey) {
    var keys = Object.keys(presets);
    keys.sort(function (a, b) { return (presets[a].ListOrder - presets[b].ListOrder) || a.localeCompare(b); });
    var $sel = $('#f_preset').empty();
    keys.forEach(function (k) { $sel.append($('<option>').val(k).text(k)); });
    $sel.append($('<option>').val('__new__').text(<?=json_encode(_("New…"))?>));
    $sel.val(selectKey);
  }

  function updateSaveAsNew() {
    var changed = origName !== '' && $.trim(gv('f_AddrPrintName')) !== origName;
    $('#btn_saveasnew').prop('disabled', !changed);
  }

  function selectPreset(key) {
    pageIdx = 0;
    if (key === '__new__') {
      origName = ''; sv('f_AddrPrintName', '');
      FIELDS.forEach(function (k) { sv('f_' + k, ''); });         // start blank
      sv('f_DefaultStamp', 'none');
      document.getElementById('f_Tategaki').checked = true;       // most envelopes are vertical
      sv('f_RetAddrContent', templateRet);                        // carry over the return address
      sv('f_NJRetAddrContent', templateNJRet);
      document.getElementById('s_kanji').checked = true;
      $('#starterrow').show(); $('#f_starter').val('');
      $('#btn_delete').prop('disabled', true);
    } else {
      origName = key; sv('f_AddrPrintName', key); loadValues(presets[key]);
      document.getElementById('s_kanji').checked = tategaki();    // kanji default follows Tategaki
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
    var name = $.trim(gv('f_AddrPrintName'));
    var data = { op: op, AddrPrintName: name, orig: origName, Tategaki: tategaki() ? 1 : 0 };
    FIELDS.forEach(function (k) { data[k] = gv('f_' + k); });
    $.post('addrprint_edit.php', data, function (resp) {
      if (resp.charAt(0) !== '*') { alert(resp); return; }
      toast(resp.substring(1));
      if (op === 'delete') {
        delete presets[origName];
        rebuildPresetOptions('__new__'); selectPreset('__new__');
      } else {
        var saved = { AddrPrintName: name, Tategaki: tategaki() ? 1 : 0 };
        FIELDS.forEach(function (k) { saved[k] = gv('f_' + k); });
        if (op === 'save' && origName && origName !== name) delete presets[origName];
        presets[name] = saved; origName = name;
        rebuildPresetOptions(name);
        $('#btn_delete').prop('disabled', false);
        updateSaveAsNew();
      }
    });
  }

  // --- events ---
  $('#f_preset').on('change', function () { selectPreset(this.value); });
  $('#f_AddrPrintName').on('input', updateSaveAsNew);
  $('#f_Tategaki').on('change', function () {
    document.getElementById('s_kanji').checked = this.checked;   // kanji default follows Tategaki
    redraw();
  });
  $('#f_starter').on('change', function () {
    if (this.value && STARTERS[this.value]) { loadStarter(STARTERS[this.value]); redraw(); }
  });
  $('.lp-field').on('input change', redraw);
  $('.lp-sample').on('input change', function () {
    var src = $('input[name=samplelang]:checked').val();
    $('#sample_jp').toggle(src === 'japan');
    $('#sample_fg').toggle(src === 'foreign');
    $('#basket_hint').toggle(src === 'basket');
    pageIdx = 0;
    redraw();
  });
  $('#pg_prev').on('click', function () { var n = readSamples().length; pageIdx = (pageIdx - 1 + n) % n; redraw(); });
  $('#pg_next').on('click', function () { var n = readSamples().length; pageIdx = (pageIdx + 1) % n; redraw(); });

  $('#btn_save').button().on('click', function () {
    if ($.trim(gv('f_AddrPrintName')) === '') { alert(<?=json_encode(_("Please enter a layout name."))?>); return; }
    save('save');
  });
  $('#btn_saveasnew').button().on('click', function () {
    if ($.trim(gv('f_AddrPrintName')) === '') { alert(<?=json_encode(_("Please enter a layout name."))?>); return; }
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
