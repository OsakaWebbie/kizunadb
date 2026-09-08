// Shared settings-page helpers (used by db_settings.php and admin_settings.php).
// A transient "saved" toast; creates #status-msg if the page hasn't styled one.
function showStatus(msg) {
  var $s = $('#status-msg');
  if (!$s.length) $s = $('<div id="status-msg">').appendTo('body');
  $s.text(msg).fadeIn(100).delay(1500).fadeOut(400);
}
// Insert or update an <option> in an entity <select>, then re-sort and select it.
function selectUpsertOption($select, id, text, extraAttrs) {
  var $opt = $select.find('option[value="' + id + '"]');
  if ($opt.length) $opt.text(text);
  else $opt = $('<option>').val(id).text(text).appendTo($select);
  if (extraAttrs) $opt.attr(extraAttrs);
  selectSortOptions($select);
  $select.val(id);
}
function selectRemoveOption($select, id) {
  $select.find('option[value="' + id + '"]').remove();
  $select.val('new');
}
// Alphabetize options, keeping the leading "new" option pinned to the top.
function selectSortOptions($select) {
  var $first = $select.find('option[value="new"]').detach();
  var opts = $select.find('option').get().sort(function(a, b) {
    return a.text.localeCompare(b.text);
  });
  $select.empty().append($first).append(opts);
}
function resetEntityForm($select) { $select.val('new').trigger('change'); }

// Returns the first date field in $form whose value won't parse, or null if all are fine.
// Keys off the .hasDatepicker class jQuery UI adds, so fields created after page load count too.
// An empty value parses as null rather than throwing, so blanks pass — check those separately.
function firstBadDate($form) {
  var bad = null;
  $form.find('.hasDatepicker').each(function() {
    try { $.datepicker.parseDate('yy-mm-dd', $(this).val()); }
    catch(error) { bad = this; return false; }
  });
  return bad;
}
