<?php
/*****
merge_duplicates.php — guided reconciliation of two duplicate person/org records.

Two display states (plus the POST commit, added in a later phase):
  1. SELECTION  (?from=X)        — find/choose the second record to merge with X.
  2. COMPARE    (?a=A&b=B)       — side-by-side chooser; A (lower ID) is kept, B is removed.

The lower PersonID is ALWAYS A (kept, shown left) — it is the older record. The user usually
arrives from the newer record (B) they want to retire; ordering is normalized here regardless.
*****/

include("functions.php");
include("accesscontrol.php");

// ----- load a person row by id, or null -----
function mc_person($pid) {
  $r = sqlquery_checked("SELECT * FROM person WHERE PersonID=".intval($pid));
  return mysqli_num_rows($r) ? mysqli_fetch_object($r) : null;
}

// ----- household summary (label name, address, phone) for the chooser / candidate display -----
function mc_household_summary($hhid) {
  if (!$hhid) return '';
  $r = sqlquery_checked("SELECT h.NonJapan, h.PostalCode, h.Address, h.LabelName, h.Phone, ".
      "CONCAT(p.Prefecture, p.ShiKuCho) AS pref ".
      "FROM household h LEFT JOIN postalcode p ON h.PostalCode=p.PostalCode WHERE h.HouseholdID=".intval($hhid));
  if (!mysqli_num_rows($r)) return '';
  $h = mysqli_fetch_object($r);
  // Build the address line, substituting a "no address" note when it is empty
  if ($h->NonJapan) {
    $addr = d2h($h->Address);
  } else {
    $addr = trim(($h->PostalCode ? '〒'.$h->PostalCode.' ' : '').$h->pref.' '.d2h($h->Address));
  }
  if ($addr === '') $addr = '('._("No address listed.").')';
  $parts = array();
  if ($h->NonJapan) {                       // foreign address: label name above the address
    if ($h->LabelName !== '') $parts[] = d2h($h->LabelName);
    $parts[] = $addr;
  } else {                                   // Japanese address: address above the label name
    $parts[] = $addr;
    if ($h->LabelName !== '') $parts[] = d2h($h->LabelName);
  }
  if ($h->Phone !== '') $parts[] = _("Landline Phone").': '.d2h($h->Phone);
  return implode('<br>', $parts);
}

// ----- one household option for the chooser: address summary (or a "no address" note when the
// household has no visible details), plus its ID and a "view details" link so the user can see who
// else lives there. An address-less household still matters: it connects people who live together. -----
function mc_household_option($hhid) {
  $hhid = intval($hhid);
  if (!$hhid) return '';
  $sum = mc_household_summary($hhid);
  return ($sum !== '' ? $sum.'<br>' : '').
      '<span class="mc-cat-src">ID: '.$hhid.
      ' (<a href="household.php?hhid='.$hhid.'" target="_blank">'._("view details in new tab").'</a>)</span>';
}

// ----- human-readable file size -----
function mc_filesize($bytes) {
  if ($bytes < 1024) return $bytes.' B';
  $units = array('KB', 'MB', 'GB');
  $i = -1;
  do { $bytes /= 1024; $i++; } while ($bytes >= 1024 && $i < 2);
  return round($bytes, 1).' '.$units[$i];
}

// ----- human-readable display of a single person field value (raw -> shown text) -----
function mc_fielddisp($key, $val) {
  if ($key == 'Sex') return $val == 'F' ? _("Female") : ($val == 'M' ? _("Male") : '');
  if ($key == 'Organization') return $val ? _("Organization") : _("Individual");
  if ($key == 'Birthdate') return substr($val, 0, 4) == '0000' ? '' : $val;
  return $val;
}

// ----- render one A/B chooser row; $htmlA/$htmlB are ready-to-output HTML; $default is 'a' or 'b' -----
function mc_radio_row($key, $label, $htmlA, $htmlB, $default) {
  ?>
  <div class="mc-fieldrow">
    <div class="mc-fieldlabel"><?=$label?></div>
    <label class="mc-opt mc-opt-a"><input type="radio" name="f[<?=$key?>]" value="a"<?=$default == 'a' ? ' checked' : ''?>> <span><?=$htmlA?></span></label>
    <label class="mc-opt mc-opt-b"><input type="radio" name="f[<?=$key?>]" value="b"<?=$default == 'b' ? ' checked' : ''?>> <span><?=$htmlB?></span></label>
  </div>
  <?php
}

// ----- render a "move/keep/delete per record" subsection. Each $rows entry is
// ['side'=>'a'|'b', 'id'=>scalar, 'cells'=>[html,...]]. Posts as $prefix[side][id]=keep|move|delete.
// Returns true if it rendered (rows non-empty), false otherwise. -----
function mc_record_section($title, $headers, $rows, $prefix, $sortable = false) {
  if (empty($rows)) return false;
  $hasA = $hasB = false;
  foreach ($rows as $row) { if ($row['side'] == 'a') $hasA = true; else $hasB = true; }
?>
  <div class="section mc-aux">
    <h3 class="section-title"><?=$title?></h3>
    <div class="mc-bulk">
<?php if ($hasA): ?>
      <span class="mc-bulk-grp"><?=_("Set all A to:")?>
        <button type="button" class="mc-bulk-btn" data-side="a" data-set="keep"><?=_("Keep")?></button>
        <button type="button" class="mc-bulk-btn" data-side="a" data-set="delete"><?=_("Delete")?></button>
      </span>
<?php endif; if ($hasB): ?>
      <span class="mc-bulk-grp"><?=_("Set all B to:")?>
        <button type="button" class="mc-bulk-btn" data-side="b" data-set="move"><?=_("Move to A")?></button>
        <button type="button" class="mc-bulk-btn" data-side="b" data-set="delete"><?=_("Delete")?></button>
      </span>
<?php endif; ?>
    </div>
    <table class="mc-auxtable<?=$sortable ? ' tablesorter' : ''?>">
      <thead><tr><th><?=_("From")?></th><?php foreach ($headers as $h) echo '<th>'.$h.'</th>'; ?><th class="sorter-false"><?=_("What to do")?></th></tr></thead>
      <tbody>
<?php foreach ($rows as $row): $side = $row['side']; $nm = $prefix.'['.$side.']['.$row['id'].']'; ?>
        <tr class="mc-side-<?=$side?>">
          <td><span class="merge-tag merge-tag-<?=$side?>"><?=strtoupper($side)?></span></td>
<?php foreach ($row['cells'] as $cell) echo '          <td>'.$cell."</td>\n"; ?>
          <td class="mc-do">
<?php if ($side == 'a'): ?>
            <label><input type="radio" name="<?=$nm?>" value="keep" checked> <?=_("Keep")?></label>
            <label><input type="radio" name="<?=$nm?>" value="delete"> <?=_("Delete")?></label>
<?php else: ?>
            <label><input type="radio" name="<?=$nm?>" value="move" checked> <?=_("Move to A")?></label>
            <label><input type="radio" name="<?=$nm?>" value="delete"> <?=_("Delete")?></label>
<?php endif; ?>
          </td>
        </tr>
<?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php
  return true;
}

// ----- render a simple one-message page and stop -----
function mc_message_exit($msg) {
  pageheader(_("Merge Duplicate Records"), 1);
  echo '<h1 id="title">'._("Merge Duplicate Records").'</h1><p>'.$msg.'</p>';
  footer();
  exit;
}

// ----- apply move/keep/delete to one "side" of perorg.
// $selfcol holds THIS record (PersonID for memberships, OrgID for members); $othercol is the partner. -----
function mc_apply_perorg_side($db, $posted, $selfcol, $othercol, $A, $B) {
  if (empty($posted) || !is_array($posted)) return;
  foreach (array('a', 'b') as $side) {
    if (empty($posted[$side]) || !is_array($posted[$side])) continue;
    foreach ($posted[$side] as $otherid => $act) {
      $otherid = intval($otherid);
      if (!$otherid) continue;
      if ($side === 'b') {
        if ($act === 'move') {
          mysqli_query($db, "UPDATE IGNORE perorg SET $selfcol=$A WHERE $selfcol=$B AND $othercol=$otherid");
          mysqli_query($db, "DELETE FROM perorg WHERE $selfcol=$B AND $othercol=$otherid");
        } elseif ($act === 'delete') {
          mysqli_query($db, "DELETE FROM perorg WHERE $selfcol=$B AND $othercol=$otherid");
        }
      } elseif ($act === 'delete') {   // side 'a'
        mysqli_query($db, "DELETE FROM perorg WHERE $selfcol=$A AND $othercol=$otherid");
      }
    }
  }
}

// ===================== POST: perform the merge =====================
if (!empty($_POST['docommit'])) {
  global $db;   // connection from functions.php; declared so direct mysqli_* calls resolve it
  $A = intval($_POST['a']);
  $B = intval($_POST['b']);
  if ($A > $B) { $t = $A; $A = $B; $B = $t; }
  $perA = mc_person($A);
  $perB = mc_person($B);
  if (!$perA || !$perB || $A < 1 || $A == $B) {
    mc_message_exit(_("Invalid records; the merge was not performed."));
  }
  // re-check the donations gate server-side
  if (empty($_SESSION['donations']) && mysqli_num_rows(sqlquery_checked(
      "SELECT 1 FROM donation WHERE PersonID IN ($A,$B) ".
      "UNION SELECT 1 FROM pledge WHERE PersonID IN ($A,$B) LIMIT 1"))) {
    mc_message_exit(_("These records include donation or pledge information, which you do not have ".
        "permission to handle. Please ask a user with donation access to perform this merge."));
  }

  // capture upload filenames up front (the on-disk file is u{UploadID}.{ext}; see download.php)
  $uploadFiles = array();
  $ru = sqlquery_checked("SELECT UploadID, FileName FROM upload WHERE PersonID IN ($A,$B)");
  while ($u = mysqli_fetch_object($ru)) $uploadFiles[$u->UploadID] = $u->FileName;

  $filesToDelete = array();
  $photoChoice = (isset($_POST['f']['Photo'])) ? $_POST['f']['Photo'] : 'a';

  mysqli_begin_transaction($db);
  try {
    // 1. person field choices -> UPDATE the survivor (A)
    $set = array();
    $f = (isset($_POST['f']) && is_array($_POST['f'])) ? $_POST['f'] : array();
    foreach (array('FullName','Furigana','Sex','Relation','Title','CellPhone','Email','Birthdate','Country','URL') as $fld) {
      if (isset($f[$fld]) && $f[$fld] === 'b') $set[] = "$fld='".h2d($perB->$fld)."'";
    }
    if (isset($f['Organization']) && $f['Organization'] === 'b') $set[] = "Organization=".($perB->Organization ? 1 : 0);
    if (isset($f['HouseholdID']) && $f['HouseholdID'] === 'b') $set[] = "HouseholdID=".intval($perB->HouseholdID);
    if (isset($_POST['remarks'])) $set[] = "Remarks='".h2d($_POST['remarks'])."'";
    if ($photoChoice === 'b') $set[] = "Photo=1";
    elseif ($photoChoice === 'none') $set[] = "Photo=0";
    if (!empty($set)) {
      mysqli_query($db, "UPDATE person SET ".implode(', ', $set)." WHERE PersonID=$A LIMIT 1");
    }

    // 2. Categories. If the chooser was shown, the survivor's set is exactly the checked boxes.
    // If it was hidden (identical sets, or none at all), keep A's categories and just drop B's.
    if (!empty($_POST['cat_shown'])) {
      mysqli_query($db, "DELETE FROM percat WHERE PersonID IN ($A,$B)");
      if (!empty($_POST['cat']) && is_array($_POST['cat'])) {
        $vals = array();
        foreach ($_POST['cat'] as $cid => $on) {
          $cid = intval($cid);
          if ($cid) $vals[] = "($A,$cid)";
        }
        if ($vals) mysqli_query($db, "INSERT IGNORE INTO percat(PersonID,CategoryID) VALUES ".implode(',', $vals));
      }
    } else {
      mysqli_query($db, "DELETE FROM percat WHERE PersonID=$B");
    }

    // 3+4. perorg, both sides, then clear any direct A<->B link
    mc_apply_perorg_side($db, $_POST['pmem'] ?? null, 'PersonID', 'OrgID', $A, $B);
    mc_apply_perorg_side($db, $_POST['pomem'] ?? null, 'OrgID', 'PersonID', $A, $B);
    // sweep any perorg row still referencing B: matched A/B pairs hidden from the UI, direct
    // A<->B links, and 'move' rows skipped by UPDATE IGNORE on collision. A's own rows are untouched.
    mysqli_query($db, "DELETE FROM perorg WHERE PersonID=$B OR OrgID=$B");

    // 5. single-key tables: action / donation / pledge / upload
    $simple = array('act'=>array('action','ActionID'), 'don'=>array('donation','DonationID'),
                    'plg'=>array('pledge','PledgeID'), 'upl'=>array('upload','UploadID'));
    foreach ($simple as $prefix => $tk) {
      list($table, $key) = $tk;
      if (empty($_POST[$prefix]) || !is_array($_POST[$prefix])) continue;
      foreach (array('a', 'b') as $side) {
        if (empty($_POST[$prefix][$side]) || !is_array($_POST[$prefix][$side])) continue;
        foreach ($_POST[$prefix][$side] as $id => $act) {
          $id = intval($id);
          if (!$id) continue;
          if ($act === 'move') {
            mysqli_query($db, "UPDATE $table SET PersonID=$A WHERE $key=$id AND PersonID=$B");
          } elseif ($act === 'delete') {
            if ($prefix === 'upl' && isset($uploadFiles[$id])) {
              $ext = strtolower(pathinfo($uploadFiles[$id], PATHINFO_EXTENSION));
              $filesToDelete[] = 'u'.$id.($ext !== '' ? '.'.$ext : '');
            }
            mysqli_query($db, "DELETE FROM $table WHERE $key=$id AND PersonID IN ($A,$B)");
          }
        }
      }
    }

    // 6. attendance (grouped per event; composite key needs collision-safe move)
    if (!empty($_POST['att']) && is_array($_POST['att'])) {
      foreach (array('a', 'b') as $side) {
        if (empty($_POST['att'][$side]) || !is_array($_POST['att'][$side])) continue;
        $pid = ($side === 'a') ? $A : $B;
        foreach ($_POST['att'][$side] as $eid => $act) {
          $eid = intval($eid);
          if (!$eid) continue;
          if ($act === 'move') {
            mysqli_query($db, "UPDATE IGNORE attendance SET PersonID=$A WHERE PersonID=$B AND EventID=$eid");
            mysqli_query($db, "DELETE FROM attendance WHERE PersonID=$B AND EventID=$eid");
          } elseif ($act === 'delete') {
            mysqli_query($db, "DELETE FROM attendance WHERE PersonID=$pid AND EventID=$eid");
          }
        }
      }
    }
    // sweep B's attendance for events hidden because A and B had identical dates (B is leaving)
    mysqli_query($db, "DELETE FROM attendance WHERE PersonID=$B");

    // 7. finally remove B. RESTRICT FKs mean this fails (and we roll back) if any reference lingers.
    mysqli_query($db, "DELETE FROM person WHERE PersonID=$B LIMIT 1");

    mysqli_commit($db);
  } catch (mysqli_sql_exception $e) {
    mysqli_rollback($db);
    fail('Merge failed for '.$A.'/'.$B, $e->getMessage());
  }

  // ----- post-commit file operations (filesystem isn't transactional; best-effort) -----
  $photodir = CLIENT_PATH."/photos/";
  if ($photoChoice === 'b' && is_file($photodir."p$B.jpg")) {
    @copy($photodir."p$B.jpg", $photodir."p$A.jpg");
  } elseif ($photoChoice === 'none' && is_file($photodir."p$A.jpg")) {
    @unlink($photodir."p$A.jpg");
  }
  if (is_file($photodir."p$B.jpg")) @unlink($photodir."p$B.jpg");   // B is gone

  $uploaddir = CLIENT_PATH."/uploads/";
  foreach ($filesToDelete as $fn) {
    if (is_file($uploaddir.$fn)) @unlink($uploaddir.$fn);
  }

  header("Location: individual.php?pid=$A");
  exit;
}

// ----- resolve which two records (if any) are involved -----
$from = intval($_GET['from'] ?? 0);   // origin record (selection state)
$pick = intval($_GET['pick'] ?? 0);   // the manually-entered partner of $from
$a    = intval($_GET['a'] ?? 0);
$b    = intval($_GET['b'] ?? 0);

// a partner picked alongside the origin record -> form the pair
if ($from && $pick && $pick != $from) {
  $a = min($from, $pick);
  $b = max($from, $pick);
}
// normalize: lower id is always A (kept)
if ($a && $b && $a > $b) { $tmp = $a; $a = $b; $b = $tmp; }

$mode = '';
$errmsg = '';

if ($a && $b && $a != $b) {
  // ===================== COMPARE MODE =====================
  $perA = mc_person($a);
  $perB = mc_person($b);
  if (!$perA || !$perB) {
    $mode = 'error';
    $errmsg = _("One or both of the selected records could not be found.");
  } elseif (empty($_SESSION['donations']) &&
      mysqli_num_rows(sqlquery_checked(
        "SELECT 1 FROM donation WHERE PersonID IN ($a,$b) ".
        "UNION SELECT 1 FROM pledge WHERE PersonID IN ($a,$b) LIMIT 1"))) {
    // Donations gate: a user who cannot see donations must not merge records that have any.
    $mode = 'error';
    $errmsg = _("These records include donation or pledge information, which you do not have ".
        "permission to handle. Please ask a user with donation access to perform this merge.");
  } else {
    $mode = 'compare';
  }
} elseif ($from) {
  // ===================== SELECTION MODE =====================
  $perX = mc_person($from);
  if (!$perX) {
    $mode = 'error';
    $errmsg = _("The record you started from could not be found.");
  } else {
    $mode = 'select';
  }
} else {
  $mode = 'error';
  $errmsg = _("No records were specified to merge.");
}

pageheader(_("Merge Duplicate Records"), 1);
?>
<style>
  .merge-intro { margin:1em 0; }
  .merge-warning { color:#b00; font-weight:bold; margin:0.5em 0; }
  .merge-cols { display:flex; flex-wrap:wrap; gap:1.5em; align-items:stretch; margin-bottom:1.5em; }
  .merge-col { flex:1 1 320px; border:1px solid #ccc; border-radius:6px; padding:0.8em 1em; }
  .merge-col h2 { margin:0 0 0.4em; font-size:1.1em; }
  .merge-a { border-color:#7a9; background:#f4faf6; }
  .merge-b { border-color:#c99; background:#fdf6f4; }
  .merge-col .merge-name { font-size:1.2em; margin-bottom:0.3em; }
  .merge-col .merge-view { font-size:0.7em; font-weight:normal; white-space:nowrap; }
  .merge-tag { display:inline-block; font-size:0.75em; padding:1px 6px; border-radius:3px; color:#fff; vertical-align:middle; }
  .merge-a .merge-tag, .merge-tag-a { background:#4a8; }
  .merge-b .merge-tag, .merge-tag-b { background:#c66; }
  tr.mc-side-a { background: #f7fbf8; }
  tr.mc-side-b {background: #fdf8f7; }
  /* selection state */
  .dup { border:1px solid #ccc; border-radius:6px; padding:0.6em 1em; margin:0.6em 0; }
  .dup .name { font-size:1.1em; margin-bottom:0.2em; }
  .dup .address, .dup .cellphone, .dup .email { color:#555; font-size:0.9em; }
  .dup .button_section { margin-top:0.4em; }
  .merge-fallbacks { margin-top:1.5em; padding-top:1em; border-top:1px solid #ddd; }
  .merge-fallbacks form { margin:0.5em 0; }
  /* compare state - field chooser */
  .mc-fields { margin-bottom:1.5em; }
  .mc-fieldrow { display:grid; grid-template-columns:9em 1fr 1fr; gap:0.3em 1em; align-items:start; padding:0.45em 0; border-bottom:1px solid #eee; }
  .mc-fieldlabel { font-weight:bold; padding-top:0.2em; }
  .mc-opt { display:block; padding:0.2em 0.5em; border-radius:4px; cursor:pointer; border-left:3px solid transparent; }
  .mc-opt > input { vertical-align:top; }
  .mc-opt > span { display:inline-block; vertical-align:top; }   /* wrap/2nd lines hang under the 1st line, not the radio */
  .mc-opt-a:hover { background:#eef7f0; }
  .mc-opt-b:hover { background:#f7eeee; }
  .mc-opt input:checked + span { font-weight:bold; }
  .mc-opt-a input:checked ~ span, .text-a { color:#276; }
  .mc-opt-b input:checked ~ span, .text-b { color:#a33; }
  .mc-blank { color:#999; font-style:italic; font-weight:normal; }
  .mc-remarks { grid-template-columns:9em 1fr; }
  .mc-remarks textarea { width:100%; min-height:6em; box-sizing:border-box; }
  .mc-photo-thumb { display:block; max-height:80px; margin-top:0.25em; }
  .mc-note { color:#666; padding:0.5em 0; }
  @media (max-width:600px) {
    .mc-fieldrow, .mc-remarks { grid-template-columns:1fr; }
  }
  /* compare state - auxiliary records */
  .mc-aux { margin-bottom:1.2em; }
  .mc-bulk { font-size:0.85em; color:#555; margin:0 0 0.4em; }
  .mc-auxtable th, .mc-auxtable td { border:1px solid #e0e0e0; padding:0.3em 0.5em; text-align:left; vertical-align:top; }
  .mc-auxtable th { background:#f4f4f4; font-size:0.9em; }
  .mc-auxtable td:first-child, .mc-auxtable th:first-child { width:2.5em; text-align:center; }
  .mc-auxtable td.mc-do { white-space:nowrap; }
  .mc-bulk-grp { display:inline-block; margin-right:1.5em; white-space:nowrap; }
  .mc-do label { display:inline-block; margin-right:0.8em; white-space:nowrap; }
  .mc-cats { display:flex; flex-wrap:wrap; gap:0.3em 1.2em; }
  .mc-cats label { display:block; }
  .mc-cat-src { color:#666; font-size:0.85em; }
  .mc-desc { max-width:28em; }
  .mc-aux-heading { margin-top:1.5em; border-top:2px solid #ccc; padding-top:0.6em; }
  .mc-submit { margin:1.5em 0; text-align:center; }
  #mc-go { font-size:1.05em; padding:0.4em 1.2em; }
  .mc-pick-name { margin-left:0.6em; color:#276; font-weight:bold; }
  @media (max-width:700px) {
    .mc-do label { display:block; margin-bottom:0.2em; }
    .mc-bulk-grp { display:block; margin-bottom:0.3em; }
  }
</style>
<h1 id="title"><?=_('Merge Duplicate Records')?></h1>
<?php

if ($mode == 'error') {
  echo '<h4 style="margin-top:20px">'.$errmsg.'</h4>';

} elseif ($mode == 'compare') {
  // ---- intro + soft org-mismatch warning ----
  ?>
  <p class="merge-intro"><?=_('The record shown on the left (lower ID, therefore likely the original) will be kept. Below are the fields and/or auxiliary info that are different between the two. '.
      'The default is to keep everything on the left ("A") plus any items on the right ("B") that would not overlap. '.
      'Adjust as desired, then press the button at the bottom to complete the merge and delete the record on the right.')?></p>
  <?php
  if ($perA->Organization != $perB->Organization) {
    echo '<p class="merge-warning">'._("Warning: one of these records is an organization and the other is an individual — please make sure you really want them to become one entity.").'</p>';
  }
  ?>
  <div class="merge-cols">
    <div class="merge-col merge-a">
      <h2><span class="merge-tag"><?=_("KEEP")?></span> <?=sprintf(_('ID %d'), $perA->PersonID)?></h2>
      <div class="merge-name"><?=ruby_name($perA->FullName, $perA->Furigana)?> <a class="merge-view" href="individual.php?pid=<?=$perA->PersonID?>" target="_blank">(<?=_("view details in new tab")?>)</a></div>
    </div>
    <div class="merge-col merge-b">
      <h2><span class="merge-tag"><?=_("REMOVE")?></span> <?=sprintf(_('ID %d'), $perB->PersonID)?></h2>
      <div class="merge-name"><?=ruby_name($perB->FullName, $perB->Furigana)?> <a class="merge-view" href="individual.php?pid=<?=$perB->PersonID?>" target="_blank">(<?=_("view details in new tab")?>)</a></div>
    </div>
  </div>

  <form id="mergeform" method="post" action="merge_duplicates.php">
    <input type="hidden" name="a" value="<?=$perA->PersonID?>" />
    <input type="hidden" name="b" value="<?=$perB->PersonID?>" />

    <div class="section">
      <h3 class="section-title"><?=_("Personal Information")?></h3>
      <div class="mc-fields">
<?php
        $blank = '<span class="mc-blank">'._("(blank)").'</span>';
        $shown = 0;

        $simpleFields = array(
          'FullName'     => _("Full Name"),
          'Furigana'     => _("Furigana"),
          'Sex'          => _("Sex"),
          'Relation'     => _("Relation in Household"),
          'Title'        => _("Title"),
          'CellPhone'    => _("Cell Phone"),
          'Email'        => _("Email"),
          'Birthdate'    => _("Birthdate"),
          'Country'      => _("Home Country"),
          'URL'          => _("URL"),
          'Organization' => _("Organization?"),
        );
        foreach ($simpleFields as $key => $label) {
          if ($perA->$key === $perB->$key) continue;   // identical -> hide
          $va = mc_fielddisp($key, $perA->$key);
          $vb = mc_fielddisp($key, $perB->$key);
          $htmlA = ($va === '') ? $blank : d2h($va);
          $htmlB = ($vb === '') ? $blank : d2h($vb);
          $default = ($va !== '') ? 'a' : 'b';
          mc_radio_row($key, $label, $htmlA, $htmlB, $default);
          $shown++;
        }

        // Household: each side shows its address summary (or a "no address" note), its ID, and a
        // "view details" link, so the user can inspect who else is in that household before choosing.
        if ($perA->HouseholdID !== $perB->HouseholdID) {
          $htmlA = $perA->HouseholdID ? mc_household_option($perA->HouseholdID) : $blank;
          $htmlB = $perB->HouseholdID ? mc_household_option($perB->HouseholdID) : $blank;
          $default = $perA->HouseholdID ? 'a' : 'b';
          mc_radio_row('HouseholdID', _("Household"), $htmlA, $htmlB, $default);
          $shown++;
        }

        // Photo: only a real choice when B has one to bring over
        if ($perB->Photo) {
?>
        <div class="mc-fieldrow">
          <div class="mc-fieldlabel"><?=_("Photo")?></div>
          <?php if ($perA->Photo): ?>
          <label class="mc-opt mc-opt-a"><input type="radio" name="f[Photo]" value="a" checked> <span><img class="mc-photo-thumb" src="photo.php?f=p<?=$perA->PersonID?>" alt="" /></span></label>
          <?php else: ?>
          <div></div>
          <?php endif; ?>
          <label class="mc-opt mc-opt-b"><input type="radio" name="f[Photo]" value="b"<?=$perA->Photo ? '' : ' checked'?>> <span><img class="mc-photo-thumb" src="photo.php?f=p<?=$perB->PersonID?>" alt="" /></span></label>
          <div class="mc-fieldlabel"></div>
          <label class="mc-opt" style="grid-column:2 / span 2"><input type="radio" name="f[Photo]" value="none"> <?=_("Use no photo")?></label>
        </div>
<?php
          $shown++;
        }

        // Remarks: combined and freely editable; shown only when they differ
        if ($perA->Remarks !== $perB->Remarks) {
          if ($perA->Remarks === '')      $pre = $perB->Remarks;
          elseif ($perB->Remarks === '')  $pre = $perA->Remarks;
          else $pre = $perA->Remarks."\n\n".$perB->Remarks;
?>
        <div class="mc-fieldrow mc-remarks">
          <div class="mc-fieldlabel"><?=_("Remarks")?></div>
          <div>
            <p class="mc-note"><?=_("Edit freely to combine both records' remarks as you wish.")?></p>
            <textarea name="remarks"><?=htmlspecialchars($pre, ENT_QUOTES)?></textarea>
          </div>
        </div>
<?php
          $shown++;
        }

        if ($shown == 0) {
          echo '<p class="mc-note">'._("The personal information fields are identical; nothing to choose here.").'</p>';
        }
?>
      </div>
    </div>

<?php
    // ===================== Section 2: auxiliary records =====================
    $A = (int)$perA->PersonID;
    $B = (int)$perB->PersonID;
    $auxShown = false;
?>
    <h2 class="mc-aux-heading"><?=_('Auxiliary Information')?></h2>
    <p class="merge-intro"><?=_('If there are differences in auxiliary info, tables are displayed here for you to choose what happens to each item. '.
        'By default, items from both A and B will be combined on A. If there are any unwanted/redundant items, switch them to "Delete".')?></p>
<?php
    // ---- Categories (percat): merged checkbox list, shown only if the two differ. A category
    // can't repeat, so identical sets need no decision. Checked = applies to the survivor. ----
    $res = sqlquery_checked("SELECT c.CategoryID, c.Category, ".
        "MAX(pc.PersonID=$A) AS inA, MAX(pc.PersonID=$B) AS inB ".
        "FROM category c JOIN percat pc ON c.CategoryID=pc.CategoryID AND pc.PersonID IN ($A,$B) ".
        "GROUP BY c.CategoryID, c.Category ORDER BY c.Category");
    $cats = array(); $catDiff = false;
    while ($r = mysqli_fetch_object($res)) { $cats[] = $r; if ($r->inA != $r->inB) $catDiff = true; }
    if ($catDiff) {
      $auxShown = true;
?>
    <div class="section mc-aux">
      <h3 class="section-title"><?=_("Categories")?></h3>
      <input type="hidden" name="cat_shown" value="1" />
      <p class="mc-note"><?=_("Checked categories will apply to the surviving record.")?>
        <span class="mc-bulk"><button type="button" class="mc-catbulk-btn" data-set="all"><?=_("Check all")?></button>
        <button type="button" class="mc-catbulk-btn" data-set="none"><?=_("Uncheck all")?></button></span></p>
      <div class="mc-cats">
<?php foreach ($cats as $r):
          $src = ($r->inA && $r->inB) ? 'A+B' : ($r->inA ? 'A' : 'B'); ?>
        <label><input type="checkbox" class="mc-cat" name="cat[<?=$r->CategoryID?>]" value="1" checked> <?=d2h($r->Category)?> <span class="mc-cat-src">(<?=$src?>)</span></label>
<?php endforeach; ?>
      </div>
    </div>
<?php
    }

    // ---- Organizations this record belongs to (perorg, PersonID side) ----
    // Skip orgs BOTH A and B already belong to (identical - no choice to make) and any direct
    // A<->B link (excluded by the OrgID filter); both are handled automatically at commit.
    $res = sqlquery_checked("SELECT po.OrgID, org.FullName, org.Furigana, po.Leader, IF(po.PersonID=$A,'a','b') AS side ".
        "FROM perorg po JOIN person org ON po.OrgID=org.PersonID ".
        "WHERE po.PersonID IN ($A,$B) AND po.OrgID NOT IN ($A,$B) ORDER BY side, org.Furigana");
    $raw = array(); $byPartner = array();
    while ($r = mysqli_fetch_object($res)) { $raw[] = $r; $byPartner[$r->OrgID][$r->side] = true; }
    $rows = array();
    foreach ($raw as $r) {
      if (!empty($byPartner[$r->OrgID]['a']) && !empty($byPartner[$r->OrgID]['b'])) continue;  // shared = identical
      $nm = ruby_name($r->FullName, $r->Furigana, $r->OrgID).($r->Leader ? ' '._("[Leader]") : '');
      $rows[] = array('side'=>$r->side, 'id'=>$r->OrgID, 'cells'=>array($nm));
    }
    $auxShown = mc_record_section(_("Organizations this record belongs to"), array(_("Organization")), $rows, 'pmem', true) || $auxShown;

    // ---- Members of this organization (perorg, OrgID side) ----
    $res = sqlquery_checked("SELECT po.PersonID AS MemID, mem.FullName, mem.Furigana, po.Leader, IF(po.OrgID=$A,'a','b') AS side ".
        "FROM perorg po JOIN person mem ON po.PersonID=mem.PersonID ".
        "WHERE po.OrgID IN ($A,$B) AND po.PersonID NOT IN ($A,$B) ORDER BY side, mem.Furigana");
    $raw = array(); $byPartner = array();
    while ($r = mysqli_fetch_object($res)) { $raw[] = $r; $byPartner[$r->MemID][$r->side] = true; }
    $rows = array();
    foreach ($raw as $r) {
      if (!empty($byPartner[$r->MemID]['a']) && !empty($byPartner[$r->MemID]['b'])) continue;  // shared = identical
      $nm = ruby_name($r->FullName, $r->Furigana, $r->MemID).($r->Leader ? ' '._("[Leader]") : '');
      $rows[] = array('side'=>$r->side, 'id'=>$r->MemID, 'cells'=>array($nm));
    }
    $auxShown = mc_record_section(_("Members of this organization"), array(_("Member")), $rows, 'pomem', true) || $auxShown;

    // ---- Actions ----
    $res = sqlquery_checked("SELECT a.ActionID, a.ActionDate, t.ActionType, a.Description, IF(a.PersonID=$A,'a','b') AS side ".
        "FROM action a LEFT JOIN actiontype t ON a.ActionTypeID=t.ActionTypeID ".
        "WHERE a.PersonID IN ($A,$B) ORDER BY side, a.ActionDate DESC");
    $rows = array();
    while ($r = mysqli_fetch_object($res)) {
      $rows[] = array('side'=>$r->side, 'id'=>$r->ActionID,
          'cells'=>array(d2h($r->ActionDate), d2h($r->ActionType), '<div class="mc-desc readmore">'.d2h($r->Description).'</div>'));
    }
    $auxShown = mc_record_section(_("Actions"), array(_("Date"), _("Action Type"), _("Description")), $rows, 'act', true) || $auxShown;

    // ---- Donations & Pledges (only when this user may see donations) ----
    if (!empty($_SESSION['donations'])) {
      $mark = $_SESSION['currency_mark'] ?? '';
      $dec = isset($_SESSION['currency_decimals']) ? intval($_SESSION['currency_decimals']) : 0;

      $res = sqlquery_checked("SELECT d.DonationID, d.DonationDate, dt.DonationType, d.Amount, d.Description, IF(d.PersonID=$A,'a','b') AS side ".
          "FROM donation d LEFT JOIN donationtype dt ON d.DonationTypeID=dt.DonationTypeID ".
          "WHERE d.PersonID IN ($A,$B) ORDER BY side, d.DonationDate DESC");
      $rows = array();
      while ($r = mysqli_fetch_object($res)) {
        $rows[] = array('side'=>$r->side, 'id'=>$r->DonationID,
            'cells'=>array(d2h($r->DonationDate), d2h($r->DonationType),
                $mark.' '.number_format($r->Amount, $dec), d2h($r->Description)));
      }
      $auxShown = mc_record_section(_("Donations"), array(_("Date"), _("Donation Type"), _("Amount"), _("Description")), $rows, 'don', true) || $auxShown;

      $res = sqlquery_checked("SELECT p.PledgeID, dt.DonationType, p.Amount, p.StartDate, p.EndDate, p.PledgeDesc, IF(p.PersonID=$A,'a','b') AS side ".
          "FROM pledge p LEFT JOIN donationtype dt ON p.DonationTypeID=dt.DonationTypeID ".
          "WHERE p.PersonID IN ($A,$B) ORDER BY side, p.StartDate DESC");
      $rows = array();
      while ($r = mysqli_fetch_object($res)) {
        $dates = $r->StartDate.($r->EndDate != '0000-00-00' ? ' ～ '.$r->EndDate : '');
        $rows[] = array('side'=>$r->side, 'id'=>$r->PledgeID,
            'cells'=>array(d2h($r->DonationType), d2h($r->PledgeDesc), $mark.' '.number_format($r->Amount, $dec), $dates));
      }
      $auxShown = mc_record_section(_("Pledges"), array(_("Donation Type"), _("Description"), _("Amount"), _("Dates")), $rows, 'plg', true) || $auxShown;
    }

    // ---- Event attendance (grouped per event). An event whose A and B dates are identical is
    // skipped (attendance can't repeat, so there is nothing to decide). ----
    sqlquery_checked("SET SESSION group_concat_max_len = 1000000");
    $res = sqlquery_checked("SELECT e.EventID, e.Event, ".
        "SUM(at.PersonID=$A) AS aCnt, SUM(at.PersonID=$B) AS bCnt, ".
        "GROUP_CONCAT(IF(at.PersonID=$A, at.AttendDate, NULL) ORDER BY at.AttendDate) AS aDates, ".
        "GROUP_CONCAT(IF(at.PersonID=$B, at.AttendDate, NULL) ORDER BY at.AttendDate) AS bDates ".
        "FROM attendance at JOIN event e ON at.EventID=e.EventID ".
        "WHERE at.PersonID IN ($A,$B) GROUP BY e.EventID, e.Event ORDER BY e.Event");
    $rows = array();
    while ($r = mysqli_fetch_object($res)) {
      if ($r->aCnt && $r->bCnt && $r->aDates === $r->bDates) continue;   // identical dates -> skip
      foreach (array('a' => $r->aDates, 'b' => $r->bDates) as $side => $dateStr) {
        if ($dateStr === null) continue;
        $dates = explode(',', $dateStr);
        $n = count($dates);
        // match individual.php: a single date stands alone; a range shows first～last [Nx]
        $datecell = ($n == 1) ? d2h($dates[0]) : (d2h($dates[0]).'～<br>'.d2h(end($dates)).' ['.$n.'x]');
        $rows[] = array('side'=>$side, 'id'=>$r->EventID, 'cells'=>array(d2h($r->Event), $datecell));
      }
    }
    $auxShown = mc_record_section(_("Event Attendance"), array(_("Event"), _("Dates")), $rows, 'att', true) || $auxShown;

    // ---- Uploaded files (show size + file time so the user can spot true duplicate files) ----
    $res = sqlquery_checked("SELECT UploadID, DATE(UploadTime) AS UploadDate, FileName, Description, IF(PersonID=$A,'a','b') AS side ".
        "FROM upload WHERE PersonID IN ($A,$B) ORDER BY side, UploadTime DESC");
    $rows = array();
    while ($r = mysqli_fetch_object($res)) {
      $ext = strtolower(pathinfo($r->FileName, PATHINFO_EXTENSION));
      $path = CLIENT_PATH."/uploads/u".$r->UploadID.($ext !== '' ? '.'.$ext : '');
      $meta = is_file($path)
        ? ' <span class="mc-cat-src">('.mc_filesize(filesize($path)).', '.date('Y-m-d H:i', filemtime($path)).')</span>'
        : '';
      $rows[] = array('side'=>$r->side, 'id'=>$r->UploadID,
          'cells'=>array(d2h($r->UploadDate), d2h($r->FileName).$meta, d2h($r->Description)));
    }
    $auxShown = mc_record_section(_("Uploaded Files"), array(_("Date"), _("File"), _("Description")), $rows, 'upl', true) || $auxShown;

    if (!$auxShown) {
      echo '<p class="mc-note">'._("There are no differences in the auxiliary information to review.").'</p>';
    }
?>
    <div class="mc-submit">
      <input type="hidden" name="docommit" value="1" />
      <button type="submit" id="mc-go"><?=sprintf(_('Merge: keep ID %1$d, delete ID %2$d'), $perA->PersonID, $perB->PersonID)?></button>
    </div>
  </form>

  <div id="mc-confirm" style="display:none">
    <p><?=sprintf(_('The record with <span class="text-b" style="font-weight:bold">ID %1$d (%2$s)</span> will be permanently deleted, '.
        'and your choices will be applied to the record with <span class="text-a" style="font-weight:bold">ID %3$d (%4$s)</span>. Are you sure?'),
        $perB->PersonID, ruby_name($perB->FullName, $perB->Furigana), $perA->PersonID, ruby_name($perA->FullName, $perA->Furigana))?></p>
  </div>
  <?php

} elseif ($mode == 'select') {
  // ---- find likely duplicates of X ----
  $xpostal = '';
  if ($perX->HouseholdID) {
    $r = sqlquery_checked("SELECT PostalCode FROM household WHERE HouseholdID=".$perX->HouseholdID);
    if (mysqli_num_rows($r)) $xpostal = mysqli_fetch_object($r)->PostalCode;
  }
  $result = find_duplicate_persons($perX->FullName, $perX->Furigana, $xpostal,
      $perX->CellPhone, $perX->Email, $perX->PersonID);
  ?>
  <p class="merge-intro"><?=sprintf(_('Looking for records that may be duplicates of %s (ID %d):'),
      ruby_name($perX->FullName, $perX->Furigana), $perX->PersonID)?></p>
  <?php
  $numCand = mysqli_num_rows($result);
  if ($numCand == 0) {
    echo '<p>'._("No likely duplicates were found automatically. Enter the ID of the other record below.").'</p>';
  } else {
    while ($row = mysqli_fetch_object($result)) {
      $ca = min($perX->PersonID, $row->PersonID);
      $cb = max($perX->PersonID, $row->PersonID);
      ?>
      <?php $hs = mc_household_summary($row->HouseholdID); ?>
      <div class="dup">
        <div class="name"><a href="individual.php?pid=<?=$row->PersonID?>" target="_blank"><?=ruby_name($row->FullName, $row->Furigana, $row->PersonID)?></a></div>
        <?php if ($hs !== ''): ?><div class="address"><?=$hs?></div><?php endif; ?>
        <?php if ($row->CellPhone): ?><div class="cellphone"><?=_("Cell Phone")?>: <?=$row->CellPhone?></div><?php endif; ?>
        <?php if ($row->Email): ?><div class="email"><?=_("Email")?>: <?=$row->Email?></div><?php endif; ?>
        <div class="button_section">
          <a class="linkbutton" href="merge_duplicates.php?a=<?=$ca?>&b=<?=$cb?>"><?=_("Select this record")?></a>
        </div>
      </div>
      <?php
    }
  }

  // ---- manual id entry (with live name preview, like individual.php's #orgid) ----
  ?>
  <div class="merge-fallbacks">
    <form method="get" action="merge_duplicates.php">
      <input type="hidden" name="from" value="<?=$perX->PersonID?>" />
      <label><?=$numCand ? _("Or enter the ID of the other record") : _("Enter the ID of the other record")?>:
        <input type="text" name="pick" id="mc-pick" style="width:7em" autocomplete="off" required /></label>
      <span id="mc-pick-name" class="mc-pick-name"></span>
      <button type="submit"><?=_("Go to merge page")?></button>
    </form>
  </div>
  <?php
}

load_scripts(['jquery', 'jqueryui', 'tablesorter', 'readmore']);
?>
<script type="text/javascript">
$(document).ready(function(){
  $('.linkbutton, .merge-fallbacks button').button();
  $('.mc-bulk-btn, .mc-catbulk-btn').button();

  // bulk set per-side radios (scoped to the subsection)
  $('.mc-bulk-btn').on('click', function(){
    var side = $(this).data('side'), set = String($(this).data('set'));
    $(this).closest('.mc-aux').find('tr.mc-side-' + side + ' input[type=radio][value="' + set + '"]').prop('checked', true);
  });
  // bulk check/uncheck for the category list
  $('.mc-catbulk-btn').on('click', function(){
    var chk = $(this).data('set') === 'all';
    $(this).closest('.mc-aux').find('input.mc-cat').prop('checked', chk);
  });

  // sortable auxiliary tables (the "What to do" column is marked sorter-false)
  $('.mc-auxtable.tablesorter').tablesorter();

  // expand/collapse long action descriptions
  $('.mc-aux .readmore').readmore({
    speed: 75, collapsedHeight: 100, heightMargin: 0,
    moreLink: '<a href="#"><?=_("[Read more]")?></a>',
    lessLink: '<a href="#"><?=_("[Close]")?></a>'
  });

  // live name preview for the manually-entered ID (selection state)
  if ($('#mc-pick').length) {
    $('#mc-pick').on('input propertychange', function(){
      if (/\D/g.test(this.value)) this.value = this.value.replace(/\D/g, '');
      if (this.value != '') {
        $('#mc-pick-name').load('ajax_request.php', {req: 'PersonName', pid: $('#mc-pick').val()});
      } else {
        $('#mc-pick-name').empty();
      }
    });
  }

  // confirmation before performing the merge
  if ($('#mc-confirm').length) {
    $('#mc-go').button();
    $('#mc-confirm').dialog({
      autoOpen: false, modal: true, width: 440,
      title: '<?=_("Confirm merge")?>',
      buttons: [
        { text: '<?=_("Yes, merge")?>', click: function(){ $('#mergeform')[0].submit(); } },
        { text: '<?=_("Cancel")?>', click: function(){ $(this).dialog('close'); } }
      ],
      // focus Cancel (the safe choice) on open, so the highlight + Enter default isn't the merge
      open: function(){ $(this).parent().find('.ui-dialog-buttonpane button').eq(1).focus(); }
    });
    $('#mergeform').on('submit', function(e){
      e.preventDefault();
      $('#mc-confirm').dialog('open');
    });
  }
});
</script>
<?php
footer();
?>
