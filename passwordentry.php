<?php
// Reusable password-strength meter + "passwords match" indicator.
// Include this right where the meter should appear (typically just after the second password field).
// Before including, optionally set:
//   $pw_field1  id of the "new password" input       (default 'new_pw1')
//   $pw_field2  id of the "confirm password" input    (default 'new_pw2')
//   $pw_require whether to block submit below "Fair"   (default true)
// Exposes window.pwEntryOK() -> bool, for the page's validate() to call. Grading must match
// password_grade() in functions.php.
$pw_field1  = $pw_field1  ?? 'new_pw1';
$pw_field2  = $pw_field2  ?? 'new_pw2';
$pw_require = isset($pw_require) ? (bool)$pw_require : true;
?>
<style>
.pw-strength { margin:4px 0 10px; font-size:90%; }
.pw-strength .pw-bar { max-width:20em; height:7px; background:#e0e0e0; border-radius:4px; overflow:hidden; }
.pw-strength .pw-bar-fill { display:block; height:100%; width:0; transition:width .2s, background-color .2s; }
.pw-strength .pw-label { display:inline-block; margin-top:2px; font-weight:bold; }
.pw-strength .pw-match { margin-top:2px; }
.pw-strength .pw-hint { margin-top:2px; font-size:85%; color:#777; }
.pw-strength.g0 .pw-bar-fill { width:25%;  background:#d64545; }
.pw-strength.g1 .pw-bar-fill { width:50%;  background:#e08a1e; }
.pw-strength.g2 .pw-bar-fill { width:75%;  background:#9bbf30; }
.pw-strength.g3 .pw-bar-fill { width:100%; background:#3a9d3a; }
.pw-strength.g0 .pw-label { color:#d64545; }
.pw-strength.g1 .pw-label { color:#c07a10; }
.pw-strength.g2 .pw-label { color:#6f8f10; }
.pw-strength.g3 .pw-label { color:#3a9d3a; }
.pw-match.ok  { color:#3a9d3a; }
.pw-match.bad { color:#d64545; }
</style>
<div id="pw-strength" class="pw-strength" aria-live="polite">
  <div class="pw-bar"><span class="pw-bar-fill"></span></div>
  <span class="pw-label"></span>
  <div class="pw-hint"></div>
  <div class="pw-match"></div>
</div>
<script type="text/javascript">
(function() {
  var f1 = document.getElementById(<?= json_encode($pw_field1) ?>);
  var f2 = document.getElementById(<?= json_encode($pw_field2) ?>);
  var wrap = document.getElementById('pw-strength');
  if (!f1 || !wrap) return;
  var require = <?= $pw_require ? 'true' : 'false' ?>;
  var LABELS = [<?= json_encode(_('Weak')) ?>, <?= json_encode(_('Fair')) ?>, <?= json_encode(_('Good')) ?>, <?= json_encode(_('Strong')) ?>];
  var MSG_WEAK    = <?= json_encode(_('Please choose a stronger password (at least "Fair").')) ?>;
  var MSG_NOMATCH = <?= json_encode(_('The two password entries do not match.')) ?>;
  var TXT_MATCH   = <?= json_encode(_('✔ Passwords match')) ?>;
  var TXT_NOMATCH = <?= json_encode(_('✘ Passwords do not match')) ?>;
  var HINT        = <?= json_encode(_('Tip: use more characters, or a mix of upper/lower case, numbers, and symbols.')) ?>;

  function grade(pw) {
    var len = pw.length;
    var points = (len >= 8 ? 1 : 0) + (len >= 12 ? 1 : 0) + (len >= 16 ? 1 : 0);
    var types = (/[a-z]/.test(pw) ? 1 : 0) + (/[A-Z]/.test(pw) ? 1 : 0)
              + (/[0-9]/.test(pw) ? 1 : 0) + (/[^a-zA-Z0-9]/.test(pw) ? 1 : 0);
    if (types > 0) points += types - 1;
    if (points <= 1) return 0;
    if (points === 2) return 1;
    return points <= 4 ? 2 : 3;
  }

  function update() {
    var p1 = f1.value;
    var label = wrap.querySelector('.pw-label');
    var hint  = wrap.querySelector('.pw-hint');
    var match = wrap.querySelector('.pw-match');
    wrap.className = 'pw-strength';
    if (p1 === '') {
      label.textContent = '';
      hint.textContent = '';
    } else {
      var g = grade(p1);
      wrap.className = 'pw-strength g' + g;
      label.textContent = LABELS[g];
      hint.textContent = (g === 0) ? HINT : '';
    }
    if (f2 && f2.value !== '') {
      if (f2.value === p1) { match.className = 'pw-match ok';  match.textContent = TXT_MATCH; }
      else                 { match.className = 'pw-match bad'; match.textContent = TXT_NOMATCH; }
    } else {
      match.className = 'pw-match';
      match.textContent = '';
    }
  }

  f1.addEventListener('input', update);
  if (f2) f2.addEventListener('input', update);
  update();

  // Called by the page's validate() before submit. Blank/blank = nothing being set (caller allows).
  window.pwEntryOK = function() {
    var p1 = f1.value, p2 = f2 ? f2.value : '';
    if (p1 === '' && p2 === '') return true;
    if (f2 && p1 !== p2) { alert(MSG_NOMATCH); return false; }
    if (require && grade(p1) < 1) { alert(MSG_WEAK + '\n\n' + HINT); return false; }
    return true;
  };
})();
</script>
