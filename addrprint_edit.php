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
  $ints = ['PaperHeight', 'PaperWidth', 'PaperBottomMargin', 'PaperLeftMargin', 'PCPointSize',
    'Tategaki', 'AddrPointSize', 'NamePointSize', 'RecipX', 'RecipY', 'RecipWidth', 'RecipHeight',
    'NameIndent', 'NameGap', 'RetAddrPointSize', 'StampX', 'StampY',
    'NJAddrPointSize', 'NJRecipX', 'NJRecipY', 'NJRecipWidth', 'NJRecipHeight',
    'NJRetAddrX', 'NJRetAddrY', 'NJRetAddrWidth', 'NJRetAddrPointSize'];
  $decs = ['PCX', 'PCY', 'PCSpacing', 'PCExtraSpace', 'RetAddrX', 'RetAddrY', 'RetAddrWidth'];
  $txt = ['RetAddrGraphic', 'RetAddrText', 'NJRetAddrGraphic', 'NJRetAddrText', 'Custom'];
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

// Renumber every layout's ListOrder (from 1) to match the drag-sorted names posted from the editor.
function ap_apply_order() {
  $order = json_decode($_POST['order'] ?? '', true);
  if (!is_array($order)) return;
  $i = 1;
  foreach ($order as $nm) {
    if (trim($nm) === '') continue;
    sqlquery_checked("UPDATE addrprint SET ListOrder=$i WHERE AddrPrintName='" . h2d($nm) . "' LIMIT 1");
    $i++;
  }
}

if (!empty($_POST['op'])) {
  header('Content-Type: text/plain; charset=utf-8');
  $op = $_POST['op'];
  $name = trim($_POST['AddrPrintName'] ?? '');
  $orig = trim($_POST['orig'] ?? '');

  if ($op === 'upload') {                                  // add a PNG to the client's graphics folder
    if (!isset($_FILES['pngfile']) || !is_uploaded_file($_FILES['pngfile']['tmp_name'] ?? '')) { echo _('Upload failed.'); exit; }
    $imginfo = @getimagesize($_FILES['pngfile']['tmp_name']);
    if (!$imginfo || $imginfo[2] !== IMAGETYPE_PNG) { echo _('Only PNG files are allowed.'); exit; }
    $base = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($_FILES['pngfile']['name']));
    if (!preg_match('/\.png$/i', $base)) $base .= '.png';
    $dest = CLIENT_PATH . '/graphics/' . $base;
    if (is_file($dest) && empty($_POST['replace'])) { echo 'EXISTS'; exit; }
    if (!@move_uploaded_file($_FILES['pngfile']['tmp_name'], $dest)) { echo _('Could not save the file.'); exit; }
    echo '*' . $base;
    exit;
  }

  if ($op === 'deletegraphic') {                           // remove a PNG, but only if unused by any layout
    $file = basename($_POST['graphic'] ?? '');
    if ($file === '') { echo _('No file specified.'); exit; }
    $u = sqlquery_checked("SELECT AddrPrintName FROM addrprint WHERE RetAddrGraphic='" . h2d($file) . "' OR NJRetAddrGraphic='" . h2d($file) . "' LIMIT 1");
    if (mysqli_num_rows($u) > 0) { $ur = mysqli_fetch_object($u); echo sprintf(_('Cannot delete: "%1$s" is still used by layout "%2$s".'), $file, $ur->AddrPrintName); exit; }
    $path = CLIENT_PATH . '/graphics/' . $file;
    if (is_file($path)) @unlink($path);
    echo '*' . _('File deleted.');
    exit;
  }

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
    ap_apply_order();
    echo '*' . _('Saved.');
    exit;
  }

  if ($name !== $orig && ap_exists($name)) {
    echo sprintf(_('A layout named "%s" already exists.'), $name);
    exit;
  }
  sqlquery_checked("UPDATE addrprint SET AddrPrintName='" . h2d($name) . "', $set WHERE AddrPrintName='" . h2d($orig) . "' LIMIT 1");
  ap_apply_order();
  echo '*' . _('Saved.');
  exit;
}

/* ---------- normal page render ---------- */

$presets = [];
$r = sqlquery_checked("SELECT * FROM addrprint ORDER BY ListOrder, AddrPrintName");
while ($row = mysqli_fetch_object($r)) $presets[$row->AddrPrintName] = $row;

// PNG files in the client's graphics folder (return-address logos, etc.) for the picker pulldowns.
$graphicsFiles = [];
foreach (glob(CLIENT_PATH . '/graphics/*.[pP][nN][gG]') ?: [] as $g) $graphicsFiles[] = basename($g);
sort($graphicsFiles);

// Return-address blocks are client-specific and shared across layouts: default a New preset's
// return address from an existing row so the user isn't retyping LaTeX.
$retFields = ['RetAddrGraphic', 'RetAddrText', 'RetAddrX', 'RetAddrY', 'RetAddrPointSize', 'RetAddrWidth'];
$njRetFields = ['NJRetAddrGraphic', 'NJRetAddrText', 'NJRetAddrX', 'NJRetAddrY', 'NJRetAddrPointSize', 'NJRetAddrWidth'];
$templateRet = null; $templateNJRet = null;
foreach ($presets as $v) {
  if ($templateRet === null && (trim($v->RetAddrGraphic) !== '' || trim($v->RetAddrText) !== '')) {
    $templateRet = [];
    foreach ($retFields as $k) $templateRet[$k] = $v->$k;
  }
  if ($templateNJRet === null && (trim($v->NJRetAddrGraphic) !== '' || trim($v->NJRetAddrText) !== '')) {
    $templateNJRet = [];
    foreach ($njRetFields as $k) $templateNJRet[$k] = $v->$k;
  }
}
// Generic placeholders when the client has no existing layout to copy from.
if ($templateRet === null)
  $templateRet = ['RetAddrGraphic' => '', 'RetAddrText' => "〒000-0000 何何県何何市\n何何区何何町 0-0-00\nYour Name",
    'RetAddrX' => 10, 'RetAddrY' => 10, 'RetAddrPointSize' => 10, 'RetAddrWidth' => 0];
if ($templateNJRet === null)
  $templateNJRet = ['NJRetAddrGraphic' => '', 'NJRetAddrText' => "Your Name\n0-0-00 Something-machi\nSomething-ku, Somewhere 000-0000\nJAPAN",
    'NJRetAddrX' => 10, 'NJRetAddrY' => 10, 'NJRetAddrPointSize' => 10, 'NJRetAddrWidth' => 0];

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
  echo '<label class="lp-row"><span>' . $label . ':</span>'
     . '<input type="number" step="' . $step . '" id="f_' . $id . '" class="lp-field" name="' . $id . "\"></label>\n";
}

// A graphic-file picker: pulldown (populated by JS) + auto-upload + delete. Reused for JP and NJ.
function graphic_picker($id) {
  echo '<div class="lp-row lp-graphic-tools"><span>' . _("Graphic file") . ':</span>'
     . '<select id="f_' . $id . '" name="' . $id . '" class="lp-field lp-graphic"></select>'
     . '<input type="file" accept="image/png" class="lp-upload" data-target="f_' . $id . '" style="display:none">'
     . '<button type="button" class="lp-upload-btn">' . _("Upload PNG…") . '</button>'
     . '<button type="button" class="lp-delete-btn" data-target="f_' . $id . '">' . _("Delete file") . '</button></div>' . "\n";
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
.lp-form legend { font-weight:bold; padding:0 0.4em; }
.lp-row { display:flex; align-items:center; gap:0.5em; margin:0.35em 0; }
.lp-row > span { flex:0 0 auto; }
.lp-row input[type=text], .lp-row textarea { flex:1 1 auto; width:auto; min-width:0; }
.lp-row textarea { resize:vertical; font-family:monospace; font-size:0.85em; }
.lp-row textarea:disabled { background:#eee; color:#999; cursor:not-allowed; }
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
.lp-svg .lp-block { fill:none; stroke: #0000ff; stroke-width:0.5; stroke-dasharray:2 2; }
.lp-svg .lp-name { stroke: #006e00; }
.lp-svg .lp-ret { stroke: #a600a6; }
.lp-svg .lp-block.lp-overflow { stroke:Red; stroke-width:1; stroke-dasharray:none; }
.lp-svg .lp-text { overflow:hidden; color:#222; line-height:1.15; font-family:'Source Sans 3', sans-serif; }
.lp-svg .lp-pcdigit { fill:#000; font-family:'Source Sans 3', sans-serif; }
.lp-svg .lp-rettext { font-size:7px; white-space:pre-line; overflow:hidden; line-height:1.15; }
.lp-hint { color:#666; font-size:0.85em; }
.lp-graphic-tools { flex-wrap:wrap; gap:0.5em; }
.lp-graphic-tools button { font-size:0.85em; }
#status-msg { position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); padding:10px 16px;
  background:#2e7d32; color:#fff; border-radius:4px; box-shadow:0 2px 8px rgba(0,0,0,.2); z-index:10000; display:none; }
</style>

<h1 id="title"><?=_("Envelope Layout Editor")?></h1>
<p class="lp-hint"><?=_("Pick a saved layout to edit, or choose \"New…\". The preview updates as you type. Dashed boxes show where each block is positioned; a red box means the text overflows it.")?></p>

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
    <span class="lp-order-info"><?=_("Menu order")?>: <span id="lp-order-num">—</span></span>
    <button type="button" id="btn_reorder"><?=_("Re-order…")?></button>
  </div>
  <div class="lp-row" id="starterrow" style="display:none">
    <span><?=_("Start from")?>:</span>
    <select id="f_starter">
      <option value=""><?=_("Select a common size…")?></option>
      <option value="naga3_tate"><?=_("長形3号 縦書き (235×120)")?></option>
      <option value="naga3_yoko"><?=_("長形3号 横書き (235×120)")?></option>
      <option value="kaku6_yoko"><?=_("角形6号 横書き (229×162)")?></option>
      <option value="hagaki"><?=_("はがき (148×100)")?></option>
      <option value="nenga"><?=_("年賀状 (148×100)")?></option>
    </select>
  </div>

  <label class="lp-row"><span><?=_("Layout name")?>:</span>
    <input type="text" id="f_AddrPrintName" name="AddrPrintName" maxlength="40" style="flex:0 1 340px"></label>
</div>

<div class="lp-layout">
  <div class="lp-form">
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
      <label class="lp-row"><input type="checkbox" id="f_Tategaki"> <?=_("Japanese address & name run vertically (tategaki)")?></label>
    </fieldset>

    <fieldset>
      <legend><?=_("Postal code")?></legend>
      <div class="lp-grid">
<?php
ap_num('PCPointSize', _('Font size (pt)'));
ap_num('PCX', _('Left margin'), '0.1');
ap_num('PCY', _('Top margin (baseline)'), '0.1');
ap_num('PCSpacing', _('Digit spacing'), '0.1');
ap_num('PCExtraSpace', _('Extra gap at hyphen'), '0.1');
ap_num('StampX', _('Stamp X (from left)'));
ap_num('StampY', _('Stamp Y (from top)'));
?>
      </div>
    </fieldset>

    <fieldset>
      <legend><?=_("Recipient block")?></legend>
      <div class="lp-grid">
<?php
ap_num('RecipX', _('Position X (from left)'));
ap_num('RecipY', _('Position Y (from top)'));
ap_num('RecipWidth', _('Box width'));
ap_num('RecipHeight', _('Box height'));
ap_num('AddrPointSize', _('Address font (pt)'));
ap_num('NamePointSize', _('Name font (pt)'));
ap_num('NameIndent', _('Name indent'));
ap_num('NameGap', _('Gap before name'));
?>
      </div>
      <p class="lp-hint"><?=_("Address then name flow inside one box; the name follows the address (they can't overlap). Indent shifts the name across the writing direction; gap is the space before it.")?></p>
    </fieldset>

    <fieldset>
      <legend><?=_("Return address")?></legend>
      <div class="lp-grid">
<?php
ap_num('RetAddrX', _('Position X (from left)'), '0.1');
ap_num('RetAddrY', _('Position Y (from bottom)'), '0.1');
ap_num('RetAddrWidth', _('Width (0 = auto)'), '0.1');
ap_num('RetAddrPointSize', _('Text font (pt)'));
?>
      </div>
<?php graphic_picker('RetAddrGraphic'); ?>
      <label class="lp-row lp-row-ta"><span><?=_("Or text")?>:</span><textarea id="f_RetAddrText" class="lp-field" rows="3"></textarea></label>
      <p class="lp-hint"><?=_("Anchored bottom-left. Pick or upload a graphic (it wins if set), or type plain text. Width sizes the image or wraps the text; 0 = natural image size / text to the right edge.")?></p>
    </fieldset>

    <fieldset>
      <legend><?=_("Non-Japan recipient — landscape")?></legend>
      <p class="lp-hint"><?=_("Non-Japan is authored in landscape (the envelope turned on its side): X from the left, Y from the top, width along the long edge. Recipient is name over address at one size.")?></p>
      <div class="lp-grid">
<?php
ap_num('NJRecipX', _('Position X (from left)'));
ap_num('NJRecipY', _('Position Y (from top)'));
ap_num('NJRecipWidth', _('Box width'));
ap_num('NJRecipHeight', _('Box height'));
ap_num('NJAddrPointSize', _('Font size (pt)'));
?>
      </div>
    </fieldset>

    <fieldset>
      <legend><?=_("Non-Japan return address")?></legend>
      <div class="lp-grid">
<?php
ap_num('NJRetAddrX', _('Position X (from left)'));
ap_num('NJRetAddrY', _('Position Y (from top)'));
ap_num('NJRetAddrWidth', _('Width (0 = auto)'));
ap_num('NJRetAddrPointSize', _('Text font (pt)'));
?>
      </div>
<?php graphic_picker('NJRetAddrGraphic'); ?>
      <label class="lp-row lp-row-ta"><span><?=_("Or text")?>:</span><textarea id="f_NJRetAddrText" class="lp-field" rows="4"></textarea></label>
    </fieldset>

    <fieldset>
      <legend><?=_("Advanced")?></legend>
      <label class="lp-row"><span><?=_("Default stamp")?>:</span>
        <select id="f_DefaultStamp" class="lp-field" name="DefaultStamp">
          <option value="none"><?=_("None")?></option>
          <option value="betsunou"><?=_("Standard mail")?></option>
          <option value="yuumail_betsunou"><?=_("'Yuu-mail'")?></option>
          <option value="kounou"><?=_("Standard mail w/ contract")?></option>
          <option value="yuumail_kounou"><?=_("'Yuu-mail' w/ contract")?></option>
        </select></label>
      <label class="lp-row"><span><?=_("Custom code")?>:</span><input type="text" id="f_Custom" name="Custom" maxlength="255"></label>
    </fieldset>

    <fieldset>
      <legend><?=_("Preview sample")?></legend>
      <div class="lp-row" style="flex-wrap:wrap;row-gap:0.2em">
        <label><input type="radio" name="samplelang" value="japan" class="lp-sample" checked> <?=_("Japan (sample)")?></label>
        <label><input type="radio" name="samplelang" value="foreign" class="lp-sample"> <?=_("Foreign (sample)")?></label>
        <label<?=($basketCount ? '' : ' style="color:#999"')?>><input type="radio" name="samplelang" value="basket" class="lp-sample"<?=($basketCount ? '' : ' disabled')?>> <?=sprintf(_("From Basket (%d)"), $basketCount)?></label>
      </div>
      <div class="lp-row"><label><input type="checkbox" id="s_kanji" class="lp-sample" checked> <?=_("Use kanji for numbers (vertical)")?></label></div>
      <div id="sample_jp">
        <div class="lp-samplerow">
          <label class="lp-row"><span><?=_("Postal Code")?>:</span><input type="text" id="s_pc" class="lp-sample" value="859-3616"></label>
          <label class="lp-row lp-row-ta lp-addr"><span><?=_("Address")?>:</span><textarea id="s_addr" class="lp-sample" rows="3"></textarea></label>
        </div>
        <label class="lp-row lp-row-ta"><span><?=_("Label Name")?>:</span><textarea id="s_name" class="lp-sample" rows="3"></textarea></label>
      </div>
      <div id="sample_fg" style="display:none">
        <label class="lp-row lp-row-ta"><span><?=_("Label Name")?>:</span><textarea id="s_fname" class="lp-sample" rows="2"></textarea></label>
        <label class="lp-row lp-row-ta"><span><?=_("Address")?>:</span><textarea id="s_faddr" class="lp-sample" rows="3"></textarea></label>
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
<div id="order_dialog" title="<?=_("Layout menu order")?>" style="display:none">
  <p class="lp-hint"><?=_("Drag to arrange. The new order takes effect when you Save the layout.")?></p>
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
  var templateRet = <?=json_encode($templateRet, JSON_UNESCAPED_UNICODE)?>;
  var templateNJRet = <?=json_encode($templateNJRet, JSON_UNESCAPED_UNICODE)?>;
  var graphicsFiles = <?=json_encode($graphicsFiles, JSON_UNESCAPED_UNICODE)?>;
  var MISSING = <?=json_encode(' ' . _("(missing)"))?>;

  var SAMPLE_DEFAULTS = {
    s_addr:  '長崎県東彼杵郡川棚町白石郷 1-18\nグリーンハイツ川棚 203号室',
    s_name:  '山田 太郎様\n山田 由美子様\nご家族の皆様',
    s_fname: 'Mr. & Mrs. John Q. Sample',
    s_faddr: '123 Main Street, Apt. 4\nSpringfield, IL 62704\nU.S.A.'
  };

  // Starter catalog: common Japanese envelope/postcard sizes (geometry only; the return address
  // comes from the New-preset carry-over). Values captured from real presets; postal-code numbers
  // are tuned to the red boxes pre-printed on those products, so edit with care.
  var STARTERS = {
    naga3_tate: {Tategaki:1,PaperHeight:235,PaperWidth:120,PaperBottomMargin:10,PaperLeftMargin:10,
      PCPointSize:20,PCX:66,PCY:19,PCSpacing:6.8,PCExtraSpace:1.0,StampX:7,StampY:10,
      AddrPointSize:18,NamePointSize:30,RecipX:10,RecipY:35,RecipWidth:100,RecipHeight:165,NameIndent:15,NameGap:12,
      NJAddrPointSize:14,NJRecipX:100,NJRecipY:42,NJRecipWidth:110,NJRecipHeight:50,NJRetAddrX:12,NJRetAddrY:10,NJRetAddrWidth:70,NJRetAddrPointSize:10,DefaultStamp:'none'},
    naga3_yoko: {Tategaki:0,PaperHeight:235,PaperWidth:120,PaperBottomMargin:10,PaperLeftMargin:10,
      PCPointSize:20,PCX:66,PCY:19,PCSpacing:6.8,PCExtraSpace:1.0,StampX:7,StampY:10,
      AddrPointSize:16,NamePointSize:22,RecipX:15,RecipY:80,RecipWidth:95,RecipHeight:120,NameIndent:10,NameGap:12,
      NJAddrPointSize:14,NJRecipX:100,NJRecipY:42,NJRecipWidth:110,NJRecipHeight:50,NJRetAddrX:12,NJRetAddrY:10,NJRetAddrWidth:70,NJRetAddrPointSize:10,DefaultStamp:'none'},
    kaku6_yoko:    {Tategaki:0,PaperHeight:229,PaperWidth:162,PaperBottomMargin:10,PaperLeftMargin:10,
      PCPointSize:28,PCX:77,PCY:32,PCSpacing:8.0,PCExtraSpace:4.0,StampX:7,StampY:10,
      AddrPointSize:18,NamePointSize:26,RecipX:20,RecipY:80,RecipWidth:130,RecipHeight:110,NameIndent:10,NameGap:14,
      NJAddrPointSize:16,NJRecipX:95,NJRecipY:65,NJRecipWidth:115,NJRecipHeight:60,NJRetAddrX:15,NJRetAddrY:12,NJRetAddrWidth:80,NJRetAddrPointSize:11,DefaultStamp:'none'},
    hagaki:     {Tategaki:1,PaperHeight:148,PaperWidth:100,PaperBottomMargin:8,PaperLeftMargin:8,
      PCPointSize:20,PCX:44,PCY:19,PCSpacing:6.8,PCExtraSpace:0.9,StampX:5,StampY:10,
      AddrPointSize:14,NamePointSize:20,RecipX:10,RecipY:25,RecipWidth:78,RecipHeight:105,NameIndent:12,NameGap:10,
      NJAddrPointSize:12,NJRecipX:62,NJRecipY:38,NJRecipWidth:78,NJRecipHeight:45,NJRetAddrX:8,NJRetAddrY:8,NJRetAddrWidth:55,NJRetAddrPointSize:9,DefaultStamp:'none'},
    nenga:      {Tategaki:1,PaperHeight:148,PaperWidth:100,PaperBottomMargin:8,PaperLeftMargin:8,
      PCPointSize:20,PCX:44,PCY:19,PCSpacing:6.8,PCExtraSpace:0.9,StampX:5,StampY:10,
      AddrPointSize:14,NamePointSize:20,RecipX:10,RecipY:25,RecipWidth:78,RecipHeight:105,NameIndent:14,NameGap:10,
      NJAddrPointSize:12,NJRecipX:62,NJRecipY:38,NJRecipWidth:78,NJRecipHeight:45,NJRetAddrX:8,NJRetAddrY:8,NJRetAddrWidth:55,NJRetAddrPointSize:9,DefaultStamp:'none'}
  };
  function loadStarter(o) {
    Object.keys(o).forEach(function (k) {
      if (k === 'Tategaki') document.getElementById('f_Tategaki').checked = (o.Tategaki == 1);
      else sv('f_' + k, o[k]);                                  // geometry only — return address untouched
    });
    document.getElementById('s_kanji').checked = document.getElementById('f_Tategaki').checked;
  }

  // All addrprint fields except the key (AddrPrintName) and Tategaki (checkbox, handled apart).
  var FIELDS = ['DefaultStamp','PaperHeight','PaperWidth','PaperBottomMargin','PaperLeftMargin',
    'PCPointSize','PCX','PCY','PCSpacing','PCExtraSpace','StampX','StampY','AddrPointSize','NamePointSize',
    'RecipX','RecipY','RecipWidth','RecipHeight','NameIndent','NameGap',
    'RetAddrGraphic','RetAddrText','RetAddrX','RetAddrY','RetAddrPointSize','RetAddrWidth',
    'NJAddrPointSize','NJRecipX','NJRecipY','NJRecipWidth','NJRecipHeight',
    'NJRetAddrX','NJRetAddrY','NJRetAddrWidth','NJRetAddrPointSize','NJRetAddrGraphic','NJRetAddrText','Custom'];

  var origName = '';
  var pageIdx = 0;
  var previewEl = document.getElementById('lp-preview');

  function gv(id) { var e = document.getElementById(id); return e ? e.value : ''; }
  function sv(id, val) { var e = document.getElementById(id); if (e) e.value = val; }
  function tategaki() { return document.getElementById('f_Tategaki').checked; }

  // (Re)fill every graphic pulldown from graphicsFiles, preserving each one's current value; a value
  // that names a now-missing file is kept (flagged) so it's never silently dropped.
  function rebuildGraphicOptions() {
    $('.lp-graphic').each(function () {
      var cur = this.value, $s = $(this).empty();
      $s.append($('<option>').val('').text(<?=json_encode(_("(text — no graphic)"))?>));
      graphicsFiles.forEach(function (f) { $s.append($('<option>').val(f).text(f)); });
      if (cur && graphicsFiles.indexOf(cur) === -1) $s.append($('<option>').val(cur).text(cur + MISSING));
      $s.val(cur);
    });
  }
  function ensureGraphic(id, val) {
    var s = document.getElementById(id); if (!s) return;
    val = val || '';
    if (val && !Array.prototype.some.call(s.options, function (o) { return o.value === val; }))
      s.appendChild(new Option(val + (graphicsFiles.indexOf(val) < 0 ? MISSING : ''), val));
    s.value = val;
  }

  function loadValues(obj) {
    FIELDS.forEach(function (k) { if (obj[k] !== undefined && obj[k] !== null) sv('f_' + k, obj[k]); });
    document.getElementById('f_Tategaki').checked = (String(obj.Tategaki) == '1');
    ensureGraphic('f_RetAddrGraphic', obj.RetAddrGraphic);
    ensureGraphic('f_NJRetAddrGraphic', obj.NJRetAddrGraphic);
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
    return [{ japan: true, postalcode: gv('s_pc'), address: gv('s_addr'), name: gv('s_name') }];
  }

  // A chosen graphic wins over text, so gray out the paired textarea; re-enable it for "(text — no graphic)".
  function syncGraphicText(sel) {
    var ta = document.getElementById(sel.id.replace('Graphic', 'Text'));
    if (ta) ta.disabled = sel.value !== '';
  }

  function redraw() {
    $('.lp-graphic').each(function () { syncGraphicText(this); });
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

  // --- pulldown order (drag to arrange) ---
  var NEWLABEL = <?=json_encode(_("(new layout)"))?>;
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
      var nm = $.trim(gv('f_AddrPrintName'));
      $ol.prepend($('<li>').addClass('lp-current lp-placeholder').attr('data-name', '').text(nm || NEWLABEL));
    }
  }
  function updateCurrentOrderLabel() {
    $('#lp-order li.lp-current').text($.trim(gv('f_AddrPrintName')) || NEWLABEL);
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
      origName = ''; sv('f_AddrPrintName', '');
      FIELDS.forEach(function (k) { sv('f_' + k, ''); });         // start blank
      sv('f_DefaultStamp', 'none');
      document.getElementById('f_Tategaki').checked = true;       // most envelopes are vertical
      Object.keys(templateRet).forEach(function (k) { sv('f_' + k, templateRet[k]); });  // carry over the return address
      Object.keys(templateNJRet).forEach(function (k) { sv('f_' + k, templateNJRet[k]); });
      ensureGraphic('f_RetAddrGraphic', templateRet.RetAddrGraphic);
      ensureGraphic('f_NJRetAddrGraphic', templateNJRet.NJRetAddrGraphic);
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
    var name = $.trim(gv('f_AddrPrintName'));
    var order = op === 'delete' ? [] : collectOrder(op, name);
    var data = { op: op, AddrPrintName: name, orig: origName, Tategaki: tategaki() ? 1 : 0,
                 order: JSON.stringify(order) };
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
        order.forEach(function (nm, i) { if (presets[nm]) presets[nm].ListOrder = i + 1; });  // server renumbered from 1
        rebuildPresetOptions(name);
        rebuildOrderList();
        updateOrderNum();
        $('#btn_delete').prop('disabled', false);
        updateSaveAsNew();
      }
    });
  }

  // --- events ---
  $('#f_preset').on('change', function () { selectPreset(this.value); });
  $('#f_AddrPrintName').on('input', function () { updateSaveAsNew(); updateCurrentOrderLabel(); });
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
  // Re-order opens the drag list in a dialog; the arrangement is applied with the next Save. The list
  // keeps its state between opens (no rebuild here), so a pending drag survives until Save or Cancel.
  $('#btn_reorder').button().on('click', function () {
    $('#lp-order').sortable('refresh');
    $('#order_dialog').dialog({
      modal: true, width: 380,
      buttons: [
        { text: <?=json_encode(_("Done"))?>, click: function () { updateOrderNum(); $(this).dialog('close'); } },
        { text: <?=json_encode(_("Cancel"))?>, click: function () { rebuildOrderList(); updateOrderNum(); $(this).dialog('close'); } }
      ]
    });
  });

  // --- graphic picker: upload (auto on choose, confirm-replace) + delete (guarded server-side) ---
  $('.lp-upload-btn').button().on('click', function () { $(this).siblings('.lp-upload').trigger('click'); });
  $('.lp-upload').on('change', function () {
    var target = this.dataset.target, file = this.files[0];
    this.value = '';                                        // reset so re-choosing the same file fires
    if (file) doUpload(file, target, false);
  });
  $('.lp-delete-btn').button().on('click', function () {
    var sel = document.getElementById(this.dataset.target), file = sel.value;
    if (!file) { alert(<?=json_encode(_("Select a file to delete first."))?>); return; }
    if (!confirm(<?=json_encode(_("Delete the file \"%s\" from the server?"))?>.replace('%s', file))) return;
    $.post('addrprint_edit.php', { op: 'deletegraphic', graphic: file }, function (resp) {
      if (resp.charAt(0) !== '*') { alert(resp); return; }   // e.g. still used by a layout
      var i = graphicsFiles.indexOf(file); if (i >= 0) graphicsFiles.splice(i, 1);
      sel.value = ''; rebuildGraphicOptions(); redraw(); toast(resp.substring(1));
    });
  });
  function doUpload(file, target, replace) {
    var fd = new FormData();
    fd.append('op', 'upload'); fd.append('pngfile', file);
    if (replace) fd.append('replace', '1');
    $.ajax({ url: 'addrprint_edit.php', method: 'POST', data: fd, processData: false, contentType: false })
      .done(function (resp) {
        if (resp === 'EXISTS') {
          if (confirm(<?=json_encode(_("Replace existing file \"%s\"?"))?>.replace('%s', file.name))) doUpload(file, target, true);
          return;
        }
        if (resp.charAt(0) !== '*') { alert(resp); return; }
        var nm = resp.substring(1);
        if (graphicsFiles.indexOf(nm) === -1) { graphicsFiles.push(nm); graphicsFiles.sort(); }
        rebuildGraphicOptions();
        document.getElementById(target).value = nm;
        redraw(); toast(<?=json_encode(_("Uploaded."))?>);
      });
  }

  // --- init ---
  Object.keys(SAMPLE_DEFAULTS).forEach(function (id) {
    var e = document.getElementById(id);
    if (e && !e.value) e.value = SAMPLE_DEFAULTS[id];
  });
  rebuildGraphicOptions();
  $('#lp-order').sortable({ placeholder: 'lp-sort-gap', forcePlaceholderSize: true, axis: 'y' });
  selectPreset(initial);
  $('#f_preset').val(initial);
});
</script>
<?php
footer();
?>
