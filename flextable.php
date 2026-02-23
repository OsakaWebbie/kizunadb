<?php
require_once("functions.php");
require_once("accesscontrol.php");

/**
 * FLEXTABLE - Flexible Table Rendering System with Lazy Loading
 *
 * USAGE:
 *   require_once("flextable.php");
 *   $tableopt = (object) [
 *     'ids' => '123,456,789',              // Comma-delimited list of record IDs to display
 *     'keyfield' => 'person.PersonID',     // table.column that IDs belong to
 *     'tableid' => 'mytable',              // HTML element ID for the table
 *     'heading' => 'Search Results',       // Optional heading text above table
 *     'order' => 'person.FullName ASC',    // Optional SQL ORDER BY clause
 *     'groupby' => 'person.HouseholdID',   // Optional SQL GROUP BY clause (auto-detected for aggregates)
 *     'cols' => array(...)                 // Array of column objects (see below)
 *   ];
 *   flextable($tableopt);
 *
 * COLUMN PROPERTIES:
 *
 * === CORE REQUIRED PROPERTIES ===
 *
 * sel (String, REQUIRED)
 *   - SQL expression or table.column to SELECT
 *   - Examples: 'person.FullName', 'TIMESTAMPDIFF(YEAR, person.Birthdate, CURDATE())'
 *   - Special computed values:
 *     * 'person.Name' - Auto-built from FullName + Furigana
 *     * 'Phones' - Auto-built from household.Phone + person.CellPhone
 *     * 'person.Photo' - Renders as <img> tag
 *
 * label (String)
 *   - Column header text displayed to user
 *   - Auto-generated if omitted for simple columns (e.g., 'person.FullName' → 'Full Name')
 *   - Required for expression columns containing '('
 *
 * key (String, REQUIRED)
 *   - Unique identifier for column, used in CSS classes and JavaScript
 *   - Examples: 'fullname', 'age', 'phones'
 *
 * === DISPLAY CONTROL ===
 *
 * show (Boolean, default: TRUE)
 *   - Whether column is visible on initial page load
 *   - Typically set by calling file based on session variable:
 *     'show' => (stripos($_SESSION['list_showcols'], ',address,') !== FALSE)
 *   - Examples: true (visible), false (hidden until user selects via column selector)
 *
 * colsel (Boolean, default: TRUE)
 *   - Whether column appears in column selector for user to show/hide
 *   - Set to false for data-only columns (e.g., hidden PersonID needed for links)
 *
 * lazy (Boolean, default: FALSE)
 *   - If true, loads data via AJAX instead of including in initial query
 *   - IMPORTANT: Only affects columns with show=TRUE
 *     * show=TRUE, lazy=FALSE: In initial query (loaded immediately)
 *     * show=TRUE, lazy=TRUE: NOT in initial query, auto-loaded via AJAX after render (shows "..." briefly)
 *     * show=FALSE: Always AJAX-loaded on demand, lazy setting doesn't matter
 *   - Use for expensive columns (GROUP_CONCAT, complex JOINs) that are shown by default
 *   - Trades faster initial page load for brief delay with "..." placeholder
 *
 * csv (Boolean, default: TRUE, FALSE for checkbox columns)
 *   - Whether column is included in CSV export
 *   - Automatically set to FALSE for checkbox columns (render: 'checkbox')
 *   - Set to FALSE for button columns (Edit, Delete) or other interactive-only columns
 *   - Example: 'csv' => false (exclude from CSV export)
 *
 * === SORTING ===
 *
 * sort (Integer, default: 0)
 *   - Initial sort order: 0=none, 1=primary ascending, -1=primary descending
 *   - Use 2/-2 for secondary sort, 3/-3 for tertiary, etc.
 *   - Example: 'sort' => -1 (sort descending on this column by default)
 *
 * sortable (Boolean, default: TRUE)
 *   - Whether user can click header to sort by this column
 *   - Set to false for composite/computed columns where sorting doesn't make sense
 *
 * === STYLING ===
 *
 * classes (String, default: '')
 *   - Space-delimited CSS classes to add to column cells
 *   - Examples: 'center', 'nowrap', 'sorter-digit'
 *
 * === JOINS AND TABLES ===
 *
 * join (String, optional)
 *   - Custom SQL JOIN clause needed for this column
 *   - Only needed for tables NOT auto-detected (person, household, postalcode are auto-detected)
 *   - Example: 'LEFT JOIN percat ON percat.PersonID=person.PersonID LEFT JOIN category ON category.CategoryID=percat.CategoryID'
 *   - JOINs are automatically deduplicated
 *
 * table (String, optional)
 *   - Explicitly specify which table column belongs to
 *   - Auto-detected from sel (e.g., 'person.Email' → 'person')
 *   - Use when sel is an expression and you want specific cell class behavior (pid/hid/key prefix)
 *
 * === RENDERING/FORMATTING ===
 *
 * render (String, optional)
 *   - Special formatting type:
 *     * 'email' - Wraps in mailto link
 *     * 'url' - Converts to clickable link
 *     * 'birthdate' - Hides year if 1900 (unknown year)
 *     * 'age' - Hides if birthdate starts with 1900
 *     * 'remarks' - Converts URLs and emails to links, nl2br
 *     * 'multiline' - Applies nl2br for newline display (use for GROUP_CONCAT columns)
 *     * 'checkbox' - Renders as interactive checkbox (requires checkbox_action and checkbox_idfield)
 *
 * checkbox_action (String, for checkboxes only)
 *   - AJAX action name to call when saving checkbox changes
 *   - Example: 'SaveMembershipStatus'
 *   - Handler in ajax_actions.php receives 'checked_ids' and 'unchecked_ids' parameters
 *
 * checkbox_idfield (String, for checkboxes only)
 *   - Field name containing ID for checkbox data-id attribute
 *   - Example: 'PersonID', 'PerCatID'
 *   - Must exist in result set
 *
 * === PERFORMANCE BEST PRACTICES ===
 *
 * When to use lazy=TRUE:
 *   - Column has expensive operations (GROUP_CONCAT, complex JOINs, large data)
 *   - Column is shown by default (show=TRUE / in showcols)
 *   - You want faster initial page load at cost of brief delay/"..." placeholder
 *
 * When NOT to use lazy=TRUE:
 *   - Column is hidden by default (show=FALSE) - redundant, already AJAX-loaded on demand
 *   - Column is fast/cheap to compute - no performance benefit
 *   - Column data is needed immediately with no delay acceptable
 *
 * Decision tree for expensive columns:
 *   - Users need it ALWAYS → show=TRUE, lazy=FALSE (pay cost upfront, no delay)
 *   - Users need it USUALLY → show=TRUE, lazy=TRUE (faster initial load, brief "..." delay)
 *   - Users need it SOMETIMES → show=FALSE (column selector, lazy doesn't matter)
 *
 * === COMMON PATTERNS ===
 *
 * Basic visible column:
 *   ['key' => 'email', 'sel' => 'person.Email', 'label' => _('Email'), 'show' => TRUE]
 *
 * Hidden data-only column:
 *   ['key' => 'personid', 'sel' => 'person.PersonID', 'label' => 'ID', 'show' => FALSE, 'colsel' => FALSE]
 *
 * Lazy-loaded expensive column shown by default:
 *   ['key' => 'categories', 'sel' => "GROUP_CONCAT(Category SEPARATOR '\\n')", 'label' => _('Categories'),
 *    'show' => TRUE, 'lazy' => TRUE, 'render' => 'multiline',
 *    'join' => 'LEFT JOIN percat ON percat.PersonID=person.PersonID LEFT JOIN category ON category.CategoryID=percat.CategoryID']
 *
 * Optional expensive column (hidden by default):
 *   ['key' => 'events', 'sel' => "GROUP_CONCAT(EventName SEPARATOR '\\n')", 'label' => _('Events'),
 *    'show' => FALSE, 'render' => 'multiline',
 *    'join' => 'LEFT JOIN attendance ON attendance.PersonID=person.PersonID LEFT JOIN event ON event.EventID=attendance.EventID']
 *   Note: lazy setting doesn't matter here - always AJAX-loaded on demand since show=FALSE
 *
 * Checkbox column:
 *   ['key' => 'active', 'sel' => 'person.Active', 'label' => _('Active'), 'show' => TRUE,
 *    'render' => 'checkbox', 'checkbox_action' => 'SaveActive', 'checkbox_idfield' => 'PersonID', 'sortable' => FALSE]
 */

// AJAX stuff - Load lazy column data
if (!empty($_REQUEST['loadcol'])) {
  ob_start();  // Start output buffering to catch any stray output
  header('Content-Type: application/json');

  // Validate session (like ajax_request.php does)
  if (empty($_SESSION['userid'])) {
    die(json_encode(['error' => 'NOSESSION']));
  }

  // Validate required parameters
  if (empty($_REQUEST['colindex']) || empty($_REQUEST['ids']) || empty($_REQUEST['coldata'])) {
    die(json_encode(['error' => 'Missing parameters']));
  }

  // Decode column definition
  $coldef = json_decode($_REQUEST['coldata']);
  if (!$coldef || empty($coldef->sel)) {
    die(json_encode(['error' => 'Invalid column definition']));
  }

  // Determine key field
  $keyfield = !empty($_REQUEST['keyfield']) ? $_REQUEST['keyfield'] : 'person.PersonID';
  list($keytable, $keycol) = explode('.', $keyfield, 2);

  // Build query for single column
  // Handle computed fields (Phones, person.Name) - select underlying fields instead
  if ($coldef->sel == 'Phones') {
    $sql = 'SELECT ' . $keyfield . ', household.Phone, person.CellPhone';
    if ($keyfield != 'person.PersonID') {
      $sql .= ', person.PersonID';
    }
    $sql .= ' FROM ' . $keytable . ' ';
  } elseif ($coldef->sel == 'person.Name') {
    $sql = 'SELECT ' . $keyfield . ', person.FullName, person.Furigana';
    if ($keyfield != 'person.PersonID') {
      $sql .= ', person.PersonID';
    }
    $sql .= ' FROM ' . $keytable . ' ';
  } else {
    $sql = 'SELECT ' . $keyfield . ', ' . $coldef->sel . ' AS colvalue';
    // Add person.PersonID if we're loading person-related columns from a non-person keyfield
    if ($keyfield != 'person.PersonID' && (strpos($coldef->sel, 'person.') === 0 || (!empty($coldef->table) && $coldef->table == 'person'))) {
      $sql .= ', person.PersonID';
    }
    // Add person.Birthdate if we're loading Age column (needed for 1900 check)
    if (preg_match('/TIMESTAMPDIFF.*Birthdate/i', $coldef->sel) || (!empty($coldef->render) && $coldef->render == 'age')) {
      $sql .= ', person.Birthdate';
    }
    // Add checkbox_idfield if we're loading a checkbox column
    if (!empty($coldef->render) && $coldef->render == 'checkbox' && !empty($coldef->checkbox_idfield)) {
      // Only add if not already the keyfield column
      if ($coldef->checkbox_idfield != $keycol) {
        $sql .= ', ' . $keytable . '.' . $coldef->checkbox_idfield;
      }
    }
    $sql .= ' FROM ' . $keytable . ' ';
  }

  // Add LEFT JOINs as needed based on column requirements
  // Check both sel and join for table references
  $join_str = $coldef->join ?? '';
  $needs_person = ($keyfield != 'person.PersonID' && (strpos($coldef->sel, 'person.') !== FALSE || strpos($join_str, 'person.') !== FALSE || $coldef->sel == 'Phones'));
  $needs_household = (strpos($coldef->sel, 'household.') !== FALSE || strpos($join_str, 'household.') !== FALSE || $coldef->sel == 'Phones');
  $needs_postalcode = (strpos($coldef->sel, 'postalcode.') !== FALSE || strpos($join_str, 'postalcode.') !== FALSE);

  // Handle dependencies: postalcode needs household, household needs person
  if ($needs_postalcode && !$needs_household) {
    $needs_household = TRUE;  // postalcode JOIN references household.PostalCode
  }
  if ($needs_household && $keyfield != 'person.PersonID' && !$needs_person) {
    $needs_person = TRUE;  // household JOIN references person.HouseholdID
  }

  if ($needs_person) {
    $sql .= 'LEFT JOIN person ON person.PersonID=' . $keytable . '.PersonID ';
  }
  if ($needs_household) {
    $sql .= 'LEFT JOIN household ON household.HouseholdID=person.HouseholdID ';
  }
  if ($needs_postalcode) {
    $sql .= 'LEFT JOIN postalcode ON household.PostalCode=postalcode.PostalCode ';
  }

  // Add column-specific JOINs (for custom cases not covered above)
  if (!empty($coldef->join)) {
    $sql .= $coldef->join . ' ';
  }

  $sql .= 'WHERE ' . $keyfield . ' IN (' . $_REQUEST['ids'] . ')';

  // Add GROUP BY if needed for aggregation functions
  if (preg_match('#(GROUP_CONCAT|MAX|MIN|COUNT|SUM|AVG)#i', $sql)) {
    $sql .= ' GROUP BY ' . $keyfield;
  }

  // Execute and return data (with error handling)
  $result = @sqlquery_checked($sql);
  if (!$result) {
    // Return SQL for debugging
    die(json_encode(['error' => 'SQL error', 'sql' => $sql, 'mysqlerror' => mysqli_error($GLOBALS['mysqli'])]));
  }
  $data = [];
  while ($row = mysqli_fetch_object($result)) {
    // Compute cell content - handle computed fields
    if ($coldef->sel == 'Phones') {
      $phone = $row->Phone ?? '';
      $cellphone = $row->CellPhone ?? '';
      if ($phone && $cellphone) {
        $cellContent = $phone . '<br>' . $cellphone;
      } else {
        $cellContent = $phone . $cellphone;
      }
    } elseif ($coldef->sel == 'person.Name') {
      $cellContent = readable_name($row->FullName ?? '', $row->Furigana ?? '', 0, 0, '<br>');
    } elseif ($coldef->sel == 'person.Photo') {
      $cellContent = (($row->colvalue ?? 0) == 1) ? '<img src="photo.php?f=p' . $row->PersonID . '" width=50>' : '';
    } else {
      $cellContent = $row->colvalue;
    }

    // Apply same rendering as main table
    // 1. Email columns
    if ($coldef->sel == 'person.Email' || (!empty($coldef->render) && $coldef->render == 'email')) {
      $cellContent = email2link($cellContent ?? '');
    }
    // 2. Birthdate - handle 1900 prefix (year unknown, show only month/day)
    elseif ($coldef->sel == 'person.Birthdate' || (!empty($coldef->render) && $coldef->render == 'birthdate')) {
      if ($cellContent && $cellContent != '0000-00-00') {
        if (substr($cellContent, 0, 4) == '1900') {
          $cellContent = substr($cellContent, 5);  // Show only MM-DD
        }
      } else {
        $cellContent = '';
      }
    }
    // 3. Age - hide if birthdate starts with 1900 (year unknown)
    elseif (preg_match('/TIMESTAMPDIFF.*Birthdate/i', $coldef->sel) || (!empty($coldef->render) && $coldef->render == 'age')) {
      // Need to fetch birthdate to check for 1900
      if (isset($row->Birthdate) && substr($row->Birthdate, 0, 4) == '1900') {
        $cellContent = '';  // Hide age when birth year is unknown
      }
      // Otherwise cellContent already has the age from the SQL
    }
    // 4. Remarks
    elseif ($coldef->sel == 'person.Remarks' || (!empty($coldef->render) && $coldef->render == 'remarks')) {
      $cellContent = email2link(url2link(d2h($cellContent ?? '')));
    }
    // 5. URL columns
    elseif ($coldef->sel == 'person.URL' || (!empty($coldef->render) && $coldef->render == 'url')) {
      $cellContent = url2link($cellContent ?? '');
    }
    // 6. GROUP_CONCAT columns (Categories, Events)
    elseif (preg_match('/GROUP_CONCAT/i', $coldef->sel) || (!empty($coldef->render) && $coldef->render == 'multiline')) {
      if (!empty($cellContent)) {
        $cellContent = d2h($cellContent);
      } else {
        $cellContent = '';
      }
    }
    // 7. Checkbox columns - render as interactive checkbox
    elseif (!empty($coldef->render) && $coldef->render == 'checkbox') {
      $checkbox_id = isset($coldef->checkbox_idfield) ? ($row->{$coldef->checkbox_idfield} ?? '') : '';
      $checked = ($cellContent == 1) ? ' checked' : '';
      $cellContent = '<input type="checkbox" class="table-checkbox" data-id="'.htmlspecialchars($checkbox_id).'"'.$checked.'>';
    }

    $data[$row->$keycol] = $cellContent;
  }

  $stray_output = ob_get_clean();  // Capture any stray output
  if ($stray_output) {
    // If there was stray output (warnings, notices, etc.), include it in error response
    die(json_encode(['error' => 'Stray output detected: *'.$stray_output.'*', 'sql' => $sql]));
  }
  die(json_encode(['success' => true, 'data' => $data]));
}

/***
Structure of the passed object:
  ids: comma-delimited list of IDs
  keyfield ('person.PersonID'): table.column that the IDs belong to
  joins (''): SQL for joins needed in query
  tableid ('maintable'): id of HTML element
  cols: array of objects (defined below)
  heading (''): optional text to the left of the buttons, above the table
  rowcolor (''): optional expression for SELECT to fetch row background color
Structure of each object in cols array:
  sel: expression for SELECT (can be person.NameCombo)
  label: label for column header
  show (TRUE): initially show this column - often based on client config settings
  colsel (TRUE): allow user to hide/show this column
  sort (0): use 1, 2, etc. to indicate initial sorting
  classes (''): e.g. sorter-false sorter-digit
  total (FALSE): put a sum of this column in the table footer
***/

function flextable($opt) {

  /***** FILL IN DEFAULTS *****/

  if (empty($opt->ids)) die('"ids" property missing.');
  if (!isset($opt->keyfield)) $opt->keyfield = 'person.PersonID';
  if (!isset($opt->header)) $opt->header = '';
  if (!isset($opt->rowcolor)) $opt->rowcolor = '';

  // Optional features (can be disabled for simple tables)
  if (!isset($opt->showColumnSelector)) $opt->showColumnSelector = TRUE;
  if (!isset($opt->showBasket)) $opt->showBasket = TRUE;
  if (!isset($opt->showCSV)) $opt->showCSV = TRUE;
  if (!isset($opt->maxnum)) $opt->maxnum = 0; // 0 = show all rows

  // No auto-column additions - calling files control all columns explicitly for clarity and ordering
  // Note: SQL auto-includes (below) still happen for computed fields like Name, Phones, Age

  foreach ($opt->cols AS $index => $col) {
    if (empty($col->sel)) die('"sel" property missing from a column.');
    if (empty($col->label)) {
      if (strpos($col->sel,'(') === FALSE) { //not an expression but just simple column
        //add spaces between words for label
        $col->label = _(preg_replace('#(?<!^)([A-Z][a-z]|(?<=[a-z])[A-Z])#',' $1',
            strpos($col->sel,'.')===FALSE ? $col->sel : substr($col->sel,strpos($col->sel,'.')+1)));
      } else {
        die('"label" property required for expression column: '.$col->sel);
      }

    }
    if (!isset($col->show)) $col->show = TRUE;
    if (!isset($col->colsel)) $col->colsel = TRUE;
    if (!isset($col->sort)) $col->sort = 0;
    if (!isset($col->sortable)) $col->sortable = TRUE;
    if (!isset($col->classes)) $col->classes = '';
    if (!isset($col->total)) $col->total = FALSE;
    if (!isset($col->lazy)) $col->lazy = FALSE;
    // csv defaults to FALSE for checkbox columns (interactive-only), TRUE for everything else
    if (!isset($col->csv)) $col->csv = (empty($col->render) || $col->render != 'checkbox');
  }

  // Safety net: If this appears to be a person table but no name columns are shown, show "name"
  $is_person_table = FALSE;
  $has_name_shown = FALSE;
  $name_col_index = NULL;
  $fullname_col_index = NULL;
  foreach ($opt->cols AS $index => $col) {
    // Check if this looks like a person table
    if (in_array($col->sel, ['person.PersonID', 'person.Name', 'person.FullName', 'person.Furigana'])) {
      $is_person_table = TRUE;
    }
    // Check if any name column is shown
    if (in_array($col->sel, ['person.Name', 'person.FullName', 'person.Furigana']) && $col->show) {
      $has_name_shown = TRUE;
    }
    // Track name column indices for fallback
    if ($col->sel == 'person.Name') $name_col_index = $index;
    if ($col->sel == 'person.FullName' && $name_col_index === NULL) $fullname_col_index = $index;
  }
  if ($is_person_table && !$has_name_shown) {
    // Force a name column to be shown
    if ($name_col_index !== NULL) {
      $opt->cols[$name_col_index]->show = TRUE;
    } elseif ($fullname_col_index !== NULL) {
      $opt->cols[$fullname_col_index]->show = TRUE;
    }
  }

  $opt->ids = trim($opt->ids, ',');
  list ($keytable,$keycol) = explode('.',$opt->keyfield,2);

  /***** SQL: BUILD AND RUN QUERY *****/

  $sql = 'SELECT ';
  $selects = '|'; // Track what's been selected to prevent duplicates
  $groupby = '';

  // Smart JOIN detection - scan all columns to determine what tables are needed
  $needs_person = FALSE;
  $needs_household = FALSE;
  $needs_postalcode = FALSE;

  foreach ($opt->cols AS $col) {
    // Check if column references person, household, or postalcode tables
    if (!$needs_person && $opt->keyfield != 'person.PersonID' && (strpos($col->sel, 'person.') !== FALSE || $col->sel == 'Phones')) {
      $needs_person = TRUE;
    }
    if (!$needs_household && (strpos($col->sel, 'household.') !== FALSE || $col->sel == 'Phones')) {
      $needs_household = TRUE;
    }
    if (!$needs_postalcode && strpos($col->sel, 'postalcode.') !== FALSE) {
      $needs_postalcode = TRUE;
    }
  }

  // Handle dependencies: postalcode needs household, household needs person
  if ($needs_postalcode && !$needs_household) {
    $needs_household = TRUE;
  }
  if ($needs_household && $opt->keyfield != 'person.PersonID' && !$needs_person) {
    $needs_person = TRUE;
  }

  // Build JOINs based on what's needed
  $joins = '';
  if ($needs_person) {
    $joins .= 'LEFT JOIN person ON person.PersonID='.$keytable.'.PersonID ';
  }
  if ($needs_household) {
    $joins .= 'LEFT JOIN household ON household.HouseholdID=person.HouseholdID ';
  }
  if ($needs_postalcode) {
    $joins .= 'LEFT JOIN postalcode ON household.PostalCode=postalcode.PostalCode ';
  }

  // Columns and expressions for SELECT
  foreach ($opt->cols AS $col) {
    // Skip person.Name and Phones - they're computed from other fields during rendering
    if ($col->sel=='person.Name' || $col->sel=='Phones') {
      $selects .= $col->sel.'|';
      // Still need to add their JOINs even though we skip the column itself
      if (!empty($col->join) && strpos($joins,$col->join)===FALSE) $joins .= $col->join.' ';
      continue;
    }

    // Special fields that need to exist without alias for internal use
    $is_special_field = ($col->sel=='person.FullName' || $col->sel=='person.Furigana' ||
                         $col->sel=='person.PersonID' || $col->sel=='person.HouseholdID' || $col->sel=='person.Birthdate');

    if ($is_special_field && strpos($selects, '|'.$col->sel.'|') === FALSE) {
      // Add without alias first for internal access (Name composite, cell classes, links)
      $sql .= $col->sel.', ';
    }

    // Now handle normally - add with alias if it's a shown column OR a data-only column (colsel=FALSE)
    if (($col->show || (isset($col->colsel) && $col->colsel === FALSE)) && !$col->lazy) {
      // Add with alias for column rendering (even special fields need this for the column display)
      $sql .= $col->sel." AS '".str_replace(' ','',$col->label)."', ";
      $selects .= $col->sel.'|';
      if (!empty($col->join) && strpos($joins,$col->join)===FALSE) $joins .= $col->join.' ';
    }
    // Special fields that are hidden still need to be tracked
    elseif ($is_special_field) {
      $selects .= $col->sel.'|';
      if (!empty($col->join) && strpos($joins,$col->join)===FALSE) $joins .= $col->join.' ';
    }
  }
  // Always include keyfield without alias
  if (strpos($selects,'|'.$opt->keyfield.'|') === FALSE) {
    $sql .= $opt->keyfield.', ';
    $selects .= $opt->keyfield.'|';
  }

  // Always include FullName and Furigana (needed for Name composite and all name links)
  $has_name_cols = (strpos($selects,'|person.FullName|') !== FALSE || strpos($selects,'|person.Name|') !== FALSE || strpos($selects,'|person.Furigana|') !== FALSE);
  if ($has_name_cols) {
    if (strpos($selects, '|person.FullName|') === FALSE) {
      $sql .= 'person.FullName, ';
      $selects .= 'person.FullName|';
    }
    if (strpos($selects, '|person.Furigana|') === FALSE) {
      $sql .= 'person.Furigana, ';
      $selects .= 'person.Furigana|';
    }
    // PersonID needed for links in name columns
    if (strpos($selects, '|person.PersonID|') === FALSE) {
      $sql .= 'person.PersonID, ';
      $selects .= 'person.PersonID|';
    }
  }

  // Always include Birthdate when a non-lazy Age column exists (needed for 1900 year check)
  $has_age_col = false;
  foreach ($opt->cols as $col) {
    if (!$col->lazy && (preg_match('/TIMESTAMPDIFF.*Birthdate/i', $col->sel) || (!empty($col->render) && $col->render == 'age'))) {
      $has_age_col = true;
      break;
    }
  }
  if ($has_age_col) {
    // Always add Birthdate separately, even if it's in the Age expression
    // (The expression is aliased, so we need the raw field for 1900 checking)
    if (strpos($selects, '|person.Birthdate|') === FALSE) {
      $sql .= 'person.Birthdate, ';
      $selects .= 'person.Birthdate|';
    }
  }

  // Always include household.Phone and person.CellPhone when non-lazy Phones combo column exists (like Name)
  $has_phones_combo = false;
  foreach ($opt->cols as $col) {
    if ($col->sel == 'Phones' && !$col->lazy) {
      $has_phones_combo = true;
      break;
    }
  }
  if ($has_phones_combo) {
    if (strpos($selects, '|household.Phone|') === FALSE) {
      $sql .= 'household.Phone, ';
      $selects .= 'household.Phone|';
    }
    if (strpos($selects, '|person.CellPhone|') === FALSE) {
      $sql .= 'person.CellPhone, ';
      $selects .= 'person.CellPhone|';
    }
  }

  // Include HouseholdID when keyfield is person.PersonID AND household columns exist
  if ($opt->keyfield == 'person.PersonID') {
    // PersonID should already be included above if name columns exist, or here if not
    // Check $selects instead of $sql to properly detect if it's already included (even with alias)
    if (strpos($selects, '|person.PersonID|') === FALSE) {
      $sql .= 'person.PersonID, ';
      $selects .= 'person.PersonID|';
    }

    // Include HouseholdID if ANY household columns exist (for cell class assignment: hid{HouseholdID})
    $has_household_cols = false;
    foreach ($opt->cols as $col) {
      if (strpos($col->sel, 'household.') !== FALSE) {
        $has_household_cols = true;
        break;
      }
    }
    if ($has_household_cols && strpos($selects, '|person.HouseholdID|') === FALSE) {
      $sql .= 'person.HouseholdID, ';
      $selects .= 'person.HouseholdID|';
    }
  }
  $sql = substr($sql,0,-2);  // remove last comma and space
  // Handle GROUP BY - custom groupby takes precedence, otherwise auto-detect for aggregates
  $groupby = '';
  if (!empty($opt->groupby)) {
    $groupby = ' GROUP BY '.$opt->groupby;
  } elseif (preg_match('#(GROUP_CONCAT|MAX|MIN|COUNT|SUM|AVERAGE)#i',$sql)===1) {
    $groupby = ' GROUP BY '.$opt->keyfield;
  }
  $sql .= ' FROM '.$keytable.' '.$joins.' WHERE '.$opt->keyfield.' IN ('.$opt->ids.')'.$groupby;
  // Add ORDER BY if specified
  if (!empty($opt->order)) {
    $sql .= ' ORDER BY '.$opt->order;
  }
  //echo '<h4>Passed parameters:</h4><xmp>'.var_dump($opt).'</xmp>';
  //echo '<h4>SQL:</h4><xmp style="white-space:pre-wrap">'.$sql.'</xmp>';
  $result = sqlquery_checked($sql);

  /***** BUTTONS: column selector, basket, batch, and CSV *****/

  ?>
  <div style="display:flex; align-items:center; gap:1em; flex-wrap:wrap;">
    <?php if (!empty($opt->heading)) { ?>
      <h3 style="margin:0"><?=$opt->heading?></h3>
    <?php } ?>
    <div class="button-block" style="display:flex; gap:1em; flex-wrap:wrap; flex:1; margin: 5px 0 10px 0">
      <?php if ($opt->showColumnSelector) { ?>
      <button id="<?=$opt->tableid?>-colsel-toggle" class="dropdown-closed"><?=_('Column Selector')?></button>
      <?php } ?>
      <?php if ($opt->showBasket) { ?>
      <div class="hassub">
        <button id="<?=$opt->tableid?>-basket-toggle" class="dropdown-closed"><?=_('Basket')?></button>
        <ul id="<?=$opt->tableid?>-basket" class="nav-sub" style="display:none">
          <li class="basket-add"><a id="<?=$opt->tableid?>-basket-add" class="ajaxlink basket-add" href="#"><?=_('Add to Basket')?></a></li>
          <li class="basket-rem"><a id="<?=$opt->tableid?>-basket-rem" class="ajaxlink basket-rem" href="#"><?=_('Remove from Basket')?></a></li>
          <li class="basket-set"><a id="<?=$opt->tableid?>-basket-set" class="ajaxlink basket-set" href="#"><?=_('Set Basket to these only')?></a></li>
        </ul>
      </div>
      <button id="<?=$opt->tableid?>-ms" title="<?=_('Go to Batch Processing page with these entries preselected')?>"><?=_('To Batch Processing')?></button>
      <?php } ?>
      <?php if ($opt->showCSV) { ?>
      <form id="<?=$opt->tableid?>-csvform" action="download.php" method="post" target="_top" style="display:inline">
        <input type="hidden" id="<?=$opt->tableid?>-csvtext" name="csvtext" value="">
        <input type="hidden" name="csvfile" value="1">
        <button type="button" id="<?=$opt->tableid?>-csv"><?=_('Download CSV')?></button>
      </form>
      <?php } ?>
      <?php
      // Check if any column uses checkbox rendering - if so, add checkbox buttons
      $has_checkbox_col = false;
      $checkbox_action = '';
      $checkbox_label = '';
      foreach ($opt->cols as $col) {
        if (!empty($col->render) && $col->render == 'checkbox') {
          $has_checkbox_col = true;
          $checkbox_action = $col->checkbox_action ?? '';
          $checkbox_label = $col->label ?? '';
          break;
        }
      }
      if ($has_checkbox_col) {
      ?>
        <span style="display:inline-flex; gap:0.5em; align-items:center; white-space:nowrap; margin-left:auto;">
          <strong><?=$checkbox_label?>:</strong>
          <button id="<?=$opt->tableid?>-checkall"><?=_('Check All')?></button>
          <button id="<?=$opt->tableid?>-savechecks" data-action="<?=$checkbox_action?>" disabled><?=_('Save Checkbox Changes')?></button>
        </span>
      <?php
      }
      ?>
    </div>
  </div>

  <?php if ($opt->showColumnSelector) { ?>
  <div id="<?=$opt->tableid?>-colsel" style="display:none; padding:5px 15px 15px 15px">
    <form style="line-height:2em">
  <?php
  foreach ($opt->cols as $index => $col ) {
    // Skip columns that shouldn't appear in column selector
    if (!$col->colsel) continue;

    echo '<label><input type="checkbox" id="'.$opt->tableid.'-col-'.$col->key.'-show" name="'.$col->key.'-show" class="colsel-checkbox"';
    if ($col->show) echo ' checked';
    echo '>' . $col->label . "</label>\n";
  }
  ?>
    </form>
  </div>
  <?php } ?>
  <?php

  /***** TABLE *****/

  ?>
  <table id="<?=$opt->tableid?>-table" class="tablesorter">
    <thead><tr>
      <?php

      /***** TABLE HEAD *****/

      foreach ($opt->cols AS $col) {
        echo '<th class="'.$col->key.($col->show?' loaded':'').($col->csv?'':' nocsv').'"'.
            ($col->show?'':' style="display:none"').'>'._($col->label).'</th>';
      }
      ?>
    </tr></thead>
    <tbody>
    <?php

    /***** TABLE BODY *****/

    $pids = ','; //need boundary for duplicate check
    $person_pids = ','; // separate collection of PersonIDs for basket/batch
    while ($row = mysqli_fetch_object($result)) {
      // Collect IDs based on the keyfield (needed for lazy column loading)
      $keyval = $row->$keycol;
      if (!empty($keyval) && strpos($pids,','.$keyval.',') === FALSE) $pids .= $keyval.',';
      // Collect PersonIDs separately for basket/batch when keyfield isn't PersonID
      if ($keycol != 'PersonID' && isset($row->PersonID) && !empty($row->PersonID)
          && strpos($person_pids,','.$row->PersonID.',') === FALSE) {
        $person_pids .= $row->PersonID.',';
      }
      echo "  <tr>\n";
      foreach ($opt->cols as $colindex => $col) {
        // For consistency with AJAX responses, always use the keyfield for cell ID class
        // Exception: when keyfield IS person.PersonID, use pid/hid for backward compatibility
        if ($opt->keyfield == 'person.PersonID') {
          // Legacy behavior: determine table from column and use pid/hid/key
          if (!empty($col->table)) {
            $table = $col->table;
          } elseif (strpos($col->sel, '.') === FALSE) {
            $table = $keytable;
          } else {
            $table = substr($col->sel, 0, strpos($col->sel, '.'));
          }
          if ($table == 'person') $cellclass = 'pid' . (isset($row->PersonID) ? $row->PersonID : $row->$keycol);
          elseif ($table == 'household') $cellclass = 'hid' . (isset($row->HouseholdID) ? $row->HouseholdID : '');
          else $cellclass = 'key' . $row->$keycol;
        } else {
          // For non-person keyfields: always use key{keyfield_value} for consistency with AJAX
          $cellclass = 'key' . $row->$keycol;
        }

        // Add column key class (to match header class for show/hide)
        // Add lazy-col class and data attribute for lazy columns
        $lazyAttr = $col->lazy ? ' lazy-col' : '';
        $dataAttr = $col->lazy ? ' data-colindex="'.$colindex.'"' : '';
        // Track if column is loaded in initial query (not lazy, and either shown or special field)
        $is_loaded = !$col->lazy && ($col->show || (isset($col->colsel) && $col->colsel === FALSE) || $col->sel=='person.PersonID' || $col->sel=='person.FullName' || $col->sel=='person.Furigana' || $col->sel=='person.Name' || $col->sel=='person.HouseholdID' || $col->sel=='person.Birthdate');
        if ($is_loaded) $dataAttr .= ' data-loaded="1"';
        // Add custom classes if specified
        // Check if readmore is in classes - if so, add readmore-wrapper to cell and remove readmore from customClasses
        $hasReadmore = !empty($col->classes) && strpos($col->classes, 'readmore') !== FALSE;
        $customClasses = !empty($col->classes) ? ' ' . $col->classes : '';
        if ($hasReadmore) {
          // Replace 'readmore' with 'readmore-wrapper' in the cell classes
          $customClasses = str_replace('readmore', 'readmore-wrapper', $customClasses);
        }
        $nocsvClass = $col->csv ? '' : ' nocsv';
        echo '    <td class="' . $cellclass . ' ' . $col->key . $lazyAttr . $customClasses . $nocsvClass . '"' . $dataAttr . ($col->show ? '' : ' style="display:none"') . '>';

        if ($col->lazy) {
          // Lazy column: show placeholder instead of data
          echo '<span class="lazy-placeholder">...</span>';
        } elseif ($col->show || (isset($col->colsel) && $col->colsel === FALSE) || $col->sel=='person.PersonID' || $col->sel=='person.FullName' || $col->sel=='person.Furigana' || $col->sel=='person.Name' || $col->sel=='person.HouseholdID' || $col->sel=='person.Birthdate') {
          // Determine cell content
          $cellContent = '';

          if ($col->sel == 'person.Photo') {
            $cellContent = (($row->Photo ?? 0) == 1) ? '<img src="photo.php?f=p' . $row->PersonID . '" width=50>' : '';
          } elseif ($col->sel == 'person.Name') {
            // Compute Name composite from FullName + Furigana
            $cellContent = readable_name($row->FullName ?? '', $row->Furigana ?? '', 0, 0, '<br>');
          } elseif ($col->sel == 'person.FullName') {
            $cellContent = $row->FullName ?? '';
          } elseif ($col->sel == 'person.Furigana') {
            $cellContent = $row->Furigana ?? '';
          } elseif ($col->sel == 'person.PersonID') {
            $cellContent = $row->PersonID ?? '';
          } elseif ($col->sel == 'person.HouseholdID') {
            $cellContent = $row->HouseholdID ?? '';
          } elseif ($col->sel == 'person.Birthdate') {
            $cellContent = $row->Birthdate ?? '';
          } elseif ($col->sel == 'Phones') {
            // Compute Phones composite from household.Phone + person.CellPhone (like list.php)
            $phone = $row->Phone ?? '';
            $cellphone = $row->CellPhone ?? '';
            if ($phone && $cellphone) {
              $cellContent = $phone . '<br>' . $cellphone;
            } else {
              $cellContent = $phone . $cellphone;  // One will be empty
            }
          } else {
            $cellContent = $row->{str_replace(' ', '', $col->label)};
          }

          // Apply universal rendering based on column type
          // 1. Email columns - wrap in mailto link
          if ($col->sel == 'person.Email' || (!empty($col->render) && $col->render == 'email')) {
            $cellContent = email2link($cellContent ?? '');
          }
          // 2. Birthdate - handle 1900 prefix (year unknown, show only month/day)
          elseif ($col->sel == 'person.Birthdate' || (!empty($col->render) && $col->render == 'birthdate')) {
            if ($cellContent && $cellContent != '0000-00-00') {
              if (substr($cellContent, 0, 4) == '1900') {
                $cellContent = substr($cellContent, 5);  // Show only MM-DD
              }
            } else {
              $cellContent = '';
            }
          }
          // 3. Age - hide if birthdate starts with 1900 (year unknown)
          elseif (preg_match('/TIMESTAMPDIFF.*Birthdate/i', $col->sel) || (!empty($col->render) && $col->render == 'age')) {
            // Only clear age if birthdate starts with 1900 (year unknown)
            // SQL IF already handles 0000-00-00 case, so $cellContent is already '' for those
            if (isset($row->Birthdate) && substr($row->Birthdate, 0, 4) == '1900') {
              $cellContent = '';  // Hide age when birth year is unknown
            }
            // Otherwise leave $cellContent as-is (already has age from SQL or is '' from SQL IF)
          }
          // 4. Remarks - wrap in email/url link converters
          elseif ($col->sel == 'person.Remarks' || (!empty($col->render) && $col->render == 'remarks')) {
            $cellContent = email2link(url2link(d2h($cellContent ?? '')));
          }
          // 5. URL columns - wrap in url2link
          elseif ($col->sel == 'person.URL' || (!empty($col->render) && $col->render == 'url')) {
            $cellContent = url2link($cellContent ?? '');
          }
          // 6. GROUP_CONCAT columns (Categories, Events) - apply d2h for newline display
          elseif (preg_match('/GROUP_CONCAT/i', $col->sel) || (!empty($col->render) && $col->render == 'multiline')) {
            if ($cellContent !== null) {
              // Handle both actual newlines and literal \n strings (in case SQL separator differs)
              $cellContent = d2h($cellContent);
            } else {
              $cellContent = '';
            }
          }
          // 7. Checkbox columns - render as interactive checkbox
          elseif (!empty($col->render) && $col->render == 'checkbox') {
            $checkbox_id = isset($col->checkbox_idfield) ? ($row->{$col->checkbox_idfield} ?? '') : '';
            $checked = ($cellContent == 1) ? ' checked' : '';
            $cellContent = '<input type="checkbox" class="table-checkbox" data-id="'.htmlspecialchars($checkbox_id).'"'.$checked.'>';
          }

          // Wrap name columns in individual.php link
          if (($col->sel == 'person.Name' || $col->sel == 'person.FullName' || $col->sel == 'person.Furigana') && !empty($cellContent)) {
            $pid = !empty($row->PersonID) ? $row->PersonID : '';
            // Hidden span for tablesorter - ONLY for Name and FullName (not Furigana itself)
            if ($col->sel == 'person.Name' || $col->sel == 'person.FullName') {
              echo '<span style="display:none">'.($row->Furigana ?? '').'</span>';
            }
            if ($hasReadmore) echo '<div class="readmore">';
            if ($pid) {
              echo '<a href="individual.php?pid='.$pid.'">'.$cellContent.'</a>';
            } else {
              echo $cellContent;
            }
            if ($hasReadmore) echo '</div>';
          } else {
            if ($hasReadmore) echo '<div class="readmore">';
            echo $cellContent;
            if ($hasReadmore) echo '</div>';
          }
        }
        echo "</td>\n";
      }
      echo "  </tr>\n";
    }
    $pids = trim($pids,',');
    $person_pids = trim($person_pids,',');
    // If keyfield IS PersonID, person_pids is just pids
    if ($keycol == 'PersonID') $person_pids = $pids;
    ?>
    </tbody>
  </table>
  <div id="<?=$opt->tableid?>-pids" style="display:none"><?=$pids?></div>
  <?php if ($keycol != 'PersonID') { ?>
  <div id="<?=$opt->tableid?>-person-pids" style="display:none"><?=$person_pids?></div>
  <?php } ?>

  <?php if ($opt->maxnum > 0) { ?>
  <button id="<?=$opt->tableid?>-showmore" style="margin-top:10px"><?=_('Show More Records')?></button>
  <?php } ?>

  <?php
  //echo '<h4>Passed parameters:</h4><xmp>'.var_dump($opt).'</xmp>';
  //echo '<h4>SQL:</h4><xmp style="white-space:pre-wrap">'.$sql.'</xmp>';

  global $scripts_loaded;
  load_scripts(array('jquery','jqueryui','tablesorter','table2csv','readmore'));
  ?>

<script>
  /***** "SEND" INFO TO JAVASCRIPT *****/

  (function() {
    var $opt = <?=json_encode($opt)?>;
    //console.log($opt);

    $(function() {
    /*** jQuery UI styling ***/
    $('button[id^=<?=$opt->tableid?>]').button();
    if ($.fn.checkboxradio) {
      $('#<?=$opt->tableid?>-colsel input').checkboxradio();
    }

    // Build tablesorter configuration
    var sortList = [];
    var headers = {};

    for (var i = 0; i < $opt.cols.length; i++) {
      // Build sortList from columns with 'sort' property
      if ($opt.cols[i].sort) {
        var sortVal = $opt.cols[i].sort;
        var direction = sortVal < 0 ? 1 : 0;  // Negative = DESC (1), Positive = ASC (0)
        var priority = Math.abs(sortVal);
        var colIndex = i;

        // If this column is hidden, try to find a related visible column to highlight
        if (!$opt.cols[i].show) {
          // For Furigana sorting, use Name or FullName if visible
          if ($opt.cols[i].sel === 'person.Furigana') {
            for (var j = 0; j < $opt.cols.length; j++) {
              if ($opt.cols[j].show && ($opt.cols[j].sel === 'person.Name' || $opt.cols[j].sel === 'person.FullName')) {
                colIndex = j;
                break;
              }
            }
          }
        }

        sortList.push([colIndex, direction, priority]);
      }

      // Build headers for non-sortable columns
      if ($opt.cols[i].sortable === false) {
        headers[i] = { sorter: false, cssHeader: 'no-arrows' };
      }
    }

    // Sort by priority (lower number = higher priority)
    sortList.sort(function(a, b) { return a[2] - b[2]; });
    // Remove priority value, leaving just [colIndex, direction]
    sortList = sortList.map(function(item) { return [item[0], item[1]]; });

    // Initialize tablesorter with configuration
    var tsConfig = {};
    if (sortList.length > 0) tsConfig.sortList = sortList;
    if (Object.keys(headers).length > 0) tsConfig.headers = headers;

    $('#<?=$opt->tableid?>-table').tablesorter(tsConfig);

    /*** Initialize readmore on all visible readmore wrappers ***/
    $('#<?=$opt->tableid?>-table .readmore').readmore({
      speed: 75,
      collapsedHeight: 100,
      heightMargin: 0,
      moreLink: '<a href="#"><?=_("[Read more]")?></a>',
      lessLink: '<a href="#"><?=_("[Close]")?></a>',
      blockProcessed: function(element, collapsible) {
        if (collapsible) {
          element.addClass('readmore-collapsed');
        }
      }
    });

    // Work around broken afterToggle callback by manually toggling the class
    $(document).on('click', '[data-readmore-toggle]', function() {
      var targetId = $(this).attr('aria-controls');
      var $target = $('#' + targetId);
      // Toggle happens after click, so we need to check current state and flip it
      if ($target.hasClass('readmore-collapsed')) {
        $target.removeClass('readmore-collapsed');
      } else {
        $target.addClass('readmore-collapsed');
      }
    });

    <?php if ($opt->maxnum > 0) { ?>
    /*** Show More/Fewer functionality ***/
    var maxnum = <?=$opt->maxnum?>;
    var $table = $('#<?=$opt->tableid?>-table');
    var $rows = $table.find('tbody tr');

    // Initially hide rows beyond maxnum
    if ($rows.length > maxnum) {
      $rows.slice(maxnum).addClass('row-hidden').hide();
      $('#<?=$opt->tableid?>-showmore').show();
    } else {
      $('#<?=$opt->tableid?>-showmore').hide();
    }

    // Toggle visibility
    $('#<?=$opt->tableid?>-showmore').on('click', function() {
      var $btn = $(this);
      var $hiddenRows = $table.find('tbody tr.row-hidden');

      if ($hiddenRows.length > 0) {
        // Show more
        $hiddenRows.removeClass('row-hidden').show();
        $btn.text('<?=_('Show Fewer Records')?>');
        $table.trigger('update'); // Update tablesorter
      } else {
        // Show fewer - hide rows beyond maxnum in current sort order
        var $allRows = $table.find('tbody tr');
        $allRows.slice(maxnum).addClass('row-hidden').hide();
        $btn.text('<?=_('Show More Records')?>');
        $table.trigger('update'); // Update tablesorter
      }
    });
    <?php } ?>

    /*** Load lazy columns that are shown by default ***/
    for (var i = 0; i < $opt.cols.length; i++) {
      if ($opt.cols[i].lazy && $opt.cols[i].show) {
        loadLazyColumn(i, $opt.cols[i]);
      }
    }

    /*** actions for row of buttons ***/

    // column selector
    $('#<?=$opt->tableid?>-colsel-toggle').click(function() {
      if ($('#<?=$opt->tableid?>-colsel').is(":hidden")) {
        $(this).removeClass('dropdown-closed').addClass('dropdown-open');
      } else {
        $(this).removeClass('dropdown-open').addClass('dropdown-closed');
      }
      $('#<?=$opt->tableid?>-colsel').slideToggle();
    });

    // Handle column show/hide and trigger lazy loading
    $('#<?=$opt->tableid?>-colsel .colsel-checkbox').change(function() {
      var colKey = $(this).attr('id').replace(/^.*-col-/, '').replace('-show', '');
      var colIndex = null;
      var colDef = null;

      // Find column definition
      for (var i = 0; i < $opt.cols.length; i++) {
        if ($opt.cols[i].key === colKey) {
          colIndex = i;
          colDef = $opt.cols[i];
          break;
        }
      }

      if (colIndex === null) return;

      // Show/hide column
      var colClass = '.' + colKey;
      if ($(this).is(':checked')) {
        $('#<?=$opt->tableid?>-table th' + colClass).show();
        $('#<?=$opt->tableid?>-table td' + colClass).show();

        // Check if column needs to be loaded via AJAX
        // This includes: lazy columns, or columns with placeholders, or empty cells that weren't in initial query
        var needsLoading = false;

        if (colDef.lazy || $('#<?=$opt->tableid?>-table td' + colClass + ' .lazy-placeholder').length > 0) {
          needsLoading = true;
        } else {
          // Check if column was already loaded in initial query
          var wasLoaded = $('#<?=$opt->tableid?>-table td' + colClass + '[data-loaded="1"]').length > 0;

          if (!wasLoaded) {
            // Check if cells are empty (column wasn't in initial query)
            var hasData = false;
            $('#<?=$opt->tableid?>-table td' + colClass).each(function() {
              if ($.trim($(this).text()).length > 0 || $(this).find('img').length > 0) {
                hasData = true;
                return false; // break
              }
            });
            if (!hasData) {
              needsLoading = true;
            }
          }
        }

        if (needsLoading) {
          loadLazyColumn(colIndex, colDef);
        }
      } else {
        $('#<?=$opt->tableid?>-table th' + colClass).hide();
        $('#<?=$opt->tableid?>-table td' + colClass).hide();
      }

      $('#<?=$opt->tableid?>-table').trigger('update');
    });

    // Load lazy column via AJAX
    function loadLazyColumn(colIndex, colDef) {
      var ids = $('#<?=$opt->tableid?>-pids').text();
      var colClass = '.' + colDef.key;

      // Show loading indicator
      $(colClass + ' .lazy-placeholder').text('Loading...');

      // Prepare column data (only necessary fields)
      var colData = JSON.stringify({
        sel: colDef.sel,
        join: colDef.join || '',
        table: colDef.table || '',
        render: colDef.render || '',
        checkbox_idfield: colDef.checkbox_idfield || ''
      });

      $.post('flextable.php', {
        loadcol: 1,
        colindex: colIndex,
        ids: ids,
        keyfield: $opt.keyfield,
        coldata: colData
      }, function(response) {
        if (response.error) {
          var errorMsg = 'Error loading column: ' + response.error;
          if (response.sql) errorMsg += '\n\nSQL: ' + response.sql;
          if (response.mysqlerror) errorMsg += '\n\nMySQL Error: ' + response.mysqlerror;
          alert(errorMsg);
          return;
        }

        // Populate cells using column class
        $('#<?=$opt->tableid?>-table td' + colClass).each(function() {
          var $cell = $(this);
          var cellClass = $cell.attr('class').match(/(?:pid|hid|key)(\d+)/);
          var content = '';
          if (cellClass && response.data[cellClass[1]] !== undefined) {
            content = response.data[cellClass[1]];
          }

          // Check if this column needs readmore wrapper
          if ($cell.hasClass('readmore-wrapper') && content) {
            $cell.html('<div class="readmore">' + content + '</div>');
            // Initialize readmore on the wrapper div
            $cell.find('.readmore').readmore({
              speed: 75,
              collapsedHeight: 100,
              heightMargin: 0,
              moreLink: '<a href="#"><?=_("[Read more]")?></a>',
              lessLink: '<a href="#"><?=_("[Close]")?></a>',
              blockProcessed: function(element, collapsible) {
                if (collapsible) {
                  element.addClass('readmore-collapsed');
                }
              }
            });
            // Note: click handler for toggle is already bound at document level above
          } else {
            $cell.html(content);
          }
          $cell.removeClass('lazy-col');
        });

        $('#<?=$opt->tableid?>-table').trigger('update');
      }, 'json').fail(function(jqXHR, textStatus, errorThrown) {
        var errorMsg = 'Failed to load column data\n\n';
        errorMsg += 'Status: ' + textStatus + '\n';
        errorMsg += 'Error: ' + errorThrown + '\n';
        errorMsg += 'HTTP Status: ' + jqXHR.status + '\n\n';
        if (jqXHR.responseText) {
          errorMsg += 'Response:\n' + jqXHR.responseText.substring(0, 500);
        }
        alert(errorMsg);
        $(colClass + ' .lazy-placeholder').text('Error');
      });
    }

   // link to go directly to batch without basket
    $('#<?=$opt->tableid?>-ms').click(function() {
      location.href = 'batch.php?pids=<?=$keycol == "PersonID" ? $pids : $person_pids?>';
    });

    // Determine the correct PID source for basket operations
    var basketPids = $('#<?=$opt->tableid?>-person-pids').length
        ? $('#<?=$opt->tableid?>-person-pids').text()
        : $('#<?=$opt->tableid?>-pids').text();

    // Add these PIDs to the existing basket
    $('#<?=$opt->tableid?>-basket-add').click(function(event) {
      $.post("basket.php", { add: basketPids }, function(r) {
        if (!isNaN(r)) {
          $('span.basketcount').html(r);
          $('.basket-list,.basket-empty,.basket-rem').toggleClass('disabledlink', ($('span.basketcount').html() === '0'));
        }
        else { alert(r); }
      }, "text");
    });

    // Remove these PIDs from the existing basket
    $('#<?=$opt->tableid?>-basket-rem').click(function(event) {
      $.post("basket.php", { rem: basketPids }, function(r) {
        if (!isNaN(r)) {
          $('span.basketcount').html(r);
          $('.basket-list,.basket-empty,.basket-rem').toggleClass('disabledlink', ($('span.basketcount').html() === '0'));
        }
        else { alert(r); }
      }, "text");
    });

    // Make the basket contain only these PIDs (any previous contents are replaced)
    $('#<?=$opt->tableid?>-basket-set').click(function(event) {
      $.post("basket.php", { set: basketPids }, function(r) {
        if (!isNaN(r)) {
          $('span.basketcount').html(r);
          $('.basket-list,.basket-empty,.basket-rem').toggleClass('disabledlink', ($('span.basketcount').html() === '0'));
        }
        else { alert(r); }
      }, "text");
    });

    // export CSV
    $('#<?=$opt->tableid?>-csv').click(function() {
      // Temporarily hide nocsv columns (checkboxes, buttons, etc.)
      var $nocsv = $('#<?=$opt->tableid?>-table .nocsv:visible');
      $nocsv.hide();
      $('#<?=$opt->tableid?>-csvtext').val($('#<?=$opt->tableid?>-table').table2CSV({delivery:'value'}));
      $nocsv.show();
      $('#<?=$opt->tableid?>-csvform').submit();
    });

    // Checkbox column handlers (if present)
    var checkboxCol = null;
    for (var i = 0; i < $opt.cols.length; i++) {
      if ($opt.cols[i].render === 'checkbox') {
        checkboxCol = $opt.cols[i];
        break;
      }
    }

    if (checkboxCol) {
      // Enable save button when any checkbox changes (use delegation for AJAX-loaded checkboxes)
      $('#<?=$opt->tableid?>-table').on('change', '.table-checkbox', function() {
        $('#<?=$opt->tableid?>-savechecks').button('enable');
      });

      // Check all button
      $('#<?=$opt->tableid?>-checkall').click(function() {
        $('#<?=$opt->tableid?>-table .table-checkbox').prop('checked', true);
        $('#<?=$opt->tableid?>-savechecks').button('enable');
      });

      // Save changes button
      $('#<?=$opt->tableid?>-savechecks').click(function() {
        var action = $(this).data('action');
        var checked_ids = [];
        var unchecked_ids = [];

        $('#<?=$opt->tableid?>-table .table-checkbox').each(function() {
          var id = $(this).data('id');
          if ($(this).is(':checked')) {
            checked_ids.push(id);
          } else {
            unchecked_ids.push(id);
          }
        });

        $.post('ajax_actions.php', {
          action: action,
          checked_ids: checked_ids.join(','),
          unchecked_ids: unchecked_ids.join(',')
        }, function(response) {
          if (response.substr(0, 1) === '*') {
            alert(response.substr(1));
            $('#<?=$opt->tableid?>-savechecks').button('disable');
          } else {
            alert(response);
          }
        });
      });
    }
  });

  })(); // End of IIFE - creates closure for each table's $opt

</script>
  <?php
}

/*** adds spaces to make label more readable ***/
function tableof($text) {
  return _(preg_replace('#(?<!^)([A-Z][a-z]|(?<=[a-z])[A-Z])#',' ',
      strpos($col->sel,'.')===FALSE ? $col->sel : substr($col->sel,strpos($col->sel,'.'))));
}