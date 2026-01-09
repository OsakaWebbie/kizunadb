<?php
require_once("functions.php");
require_once("accesscontrol.php");

// AJAX stuff - Load lazy column data
if (!empty($_REQUEST['loadcol'])) {
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
    $sql .= ' FROM ' . $keytable . ' ';
  }

  // Add LEFT JOIN for person/household if needed
  if ($keyfield != 'person.PersonID') {
    $sql .= 'LEFT JOIN person ON person.PersonID=' . $keytable . '.PersonID ';
    $sql .= 'LEFT JOIN household ON household.HouseholdID=person.HouseholdID ';
  }

  // Add column-specific JOINs
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
    // 2. Remarks
    elseif ($coldef->sel == 'person.Remarks' || (!empty($coldef->render) && $coldef->render == 'remarks')) {
      $cellContent = email2link(url2link(d2h($cellContent ?? '')));
    }
    // 3. URL columns
    elseif ($coldef->sel == 'person.URL' || (!empty($coldef->render) && $coldef->render == 'url')) {
      $cellContent = url2link($cellContent ?? '');
    }
    // 4. GROUP_CONCAT columns (Categories, Events)
    elseif (preg_match('/GROUP_CONCAT/i', $coldef->sel) || (!empty($coldef->render) && $coldef->render == 'multiline')) {
      if ($cellContent !== null) {
        $cellContent = str_replace('\n', "\n", $cellContent);
        $cellContent = d2h($cellContent);
      } else {
        $cellContent = '';
      }
    }

    $data[$row->$keycol] = $cellContent;
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
  }

  $opt->ids = trim($opt->ids, ',');
  list ($keytable,$keycol) = explode('.',$opt->keyfield,2);

  /***** SQL: BUILD AND RUN QUERY *****/

  $sql = 'SELECT ';
  $selects = '|'; // Track what's been selected to prevent duplicates
  $groupby = '';
  $joins = ($opt->keyfield=='person.PersonID') ? '' : 'LEFT JOIN person ON person.PersonID='.$keytable.'.PersonID '.
      'LEFT JOIN household ON household.HouseholdID=person.HouseholdID ';

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

    if ($is_special_field && strpos($sql, $col->sel.',') === FALSE && strpos($sql, $col->sel.' ') === FALSE) {
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

  // Always include Birthdate when an Age column exists (needed for 1900 year check)
  $has_age_col = false;
  foreach ($opt->cols as $col) {
    if (preg_match('/TIMESTAMPDIFF.*Birthdate/i', $col->sel) || (!empty($col->render) && $col->render == 'age')) {
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

  // Always include household.Phone and person.CellPhone when Phones combo column exists (like Name)
  $has_phones_combo = false;
  foreach ($opt->cols as $col) {
    if ($col->sel == 'Phones') {
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
  if (preg_match('#(GROUP_CONCAT|MAX|MIN|COUNT|SUM|AVERAGE)#i',$sql)===1) $groupby = ' GROUP BY '.$opt->keyfield;
  $sql .= ' FROM '.$keytable.' '.$joins.' WHERE '.$opt->keyfield.' IN ('.$opt->ids.')'.$groupby;
  // Add ORDER BY if specified
  if (!empty($opt->order)) {
    $sql .= ' ORDER BY '.$opt->order;
  }
  //echo '<h4>Passed parameters:</h4><xmp>'.var_dump($opt).'</xmp>';
  //echo '<h4>SQL:</h4><xmp style="white-space:pre-wrap">'.$sql.'</xmp>';
  $result = sqlquery_checked($sql);

  /***** BUTTONS: column selector, bucket links, multi-select link, and CSV *****/

  ?>
  <div style="display:flex; align-items:center; gap:1em; flex-wrap:wrap;">
    <?php if (!empty($opt->heading)) { ?>
      <h3 style="margin:0"><?=$opt->heading?></h3>
    <?php } ?>
    <div class="button-block" style="display:flex; gap:0.5em; flex-wrap:wrap; flex:1">
      <button id="<?=$opt->tableid?>-colsel-toggle" class="dropdown-closed"><?=_('Column Selector')?></button>
      <div class="hassub">
        <button id="<?=$opt->tableid?>-bucket-toggle" class="dropdown-closed"><?=_('Bucket')?></button>
        <ul id="<?=$opt->tableid?>-bucket" class="nav-sub" style="display:none">
          <li class="bucket-add"><a id="<?=$opt->tableid?>-bucket-add" class="ajaxlink bucket-add" href="#"><?=_('Add to Bucket')?></a></li>
          <li class="bucket-rem"><a id="<?=$opt->tableid?>-bucket-rem" class="ajaxlink bucket-rem" href="#"><?=_('Remove from Bucket')?></a></li>
          <li class="bucket-set"><a id="<?=$opt->tableid?>-bucket-set" class="ajaxlink bucket-set" href="#"><?=_('Set Bucket to these')?></a></li>
        </ul>
      </div>
      <button id="<?=$opt->tableid?>-ms" title="<?=_('Go to Multi-Select with these entries preselected')?>"><?=_('To Multi-Select')?></button>
      <form id="<?=$opt->tableid?>-csvform" action="download.php" method="post" target="_top" style="display:inline">
        <input type="hidden" id="<?=$opt->tableid?>-csvtext" name="csvtext" value="">
        <input type="hidden" name="csvfile" value="1">
        <button type="button" id="<?=$opt->tableid?>-csv"><?=_('Download CSV')?></button>
      </form>
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
  <?php

  /***** TABLE *****/

  ?>
  <table id="<?=$opt->tableid?>-table" class="tablesorter">
    <thead><tr>
      <?php

      /***** TABLE HEAD *****/

      foreach ($opt->cols AS $col) {
        echo '<th class="'.$col->key.($col->show?' loaded':'').'"'.
            ($col->show?'':' style="display:none"').'>'._($col->label).'</th>';
      }
      ?>
    </tr></thead>
    <tbody>
    <?php

    /***** TABLE BODY *****/

    $pids = ','; //need boundary for duplicate check
    while ($row = mysqli_fetch_object($result)) {
      // Collect IDs based on the keyfield, not hardcoded PersonID
      $keyval = $row->$keycol;
      if (!empty($keyval) && strpos($pids,','.$keyval.',') === FALSE) $pids .= $keyval.',';
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
        $customClasses = !empty($col->classes) ? ' ' . $col->classes : '';
        echo '    <td class="' . $cellclass . ' ' . $col->key . $lazyAttr . $customClasses . '"' . $dataAttr . ($col->show ? '' : ' style="display:none"') . '>';

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
              $cellContent = str_replace('\n', "\n", $cellContent);  // Convert literal \n to actual newline
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
            if ($pid) {
              echo '<a href="individual.php?pid='.$pid.'" target="_blank">'.$cellContent.'</a>';
            } else {
              echo $cellContent;
            }
          } else {
            echo $cellContent;
          }
        }
        echo "</td>\n";
      }
      echo "  </tr>\n";
    }
    $pids = trim($pids,',');
    ?>
    </tbody>
  </table>
  <div id="<?=$opt->tableid?>-pids" style="display:none"><?=$pids?></div>

  <?php
  //echo '<h4>Passed parameters:</h4><xmp>'.var_dump($opt).'</xmp>';
  //echo '<h4>SQL:</h4><xmp style="white-space:pre-wrap">'.$sql.'</xmp>';

  global $scripts_loaded;
  load_scripts(array('jquery','jqueryui','tablesorter','table2csv'));
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
        table: colDef.table || ''
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
          var cellClass = $(this).attr('class').match(/(?:pid|hid|key)(\d+)/);
          if (cellClass && response.data[cellClass[1]] !== undefined) {
            $(this).html(response.data[cellClass[1]]);
          } else {
            $(this).html('');
          }
          $(this).removeClass('lazy-col');
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

   // link to go directly to multiselect without bucket
    $('#<?=$opt->tableid?>-ms').click(function() {
      location.href = 'multiselect.php?preselected=<?=$pids?>';
    });

    // Add these PIDs to the existing bucket
    $('#<?=$opt->tableid?>-bucket-add').click(function(event) {
      $.post("bucket.php", { add:$('#<?=$opt->tableid?>-pids').text() }, function(r) {
        if (!isNaN(r)) {
          $('span.bucketcount').html(r);
          $('.bucket-list,.bucket-empty,.bucket-rem').toggleClass('disabledlink', ($('span.bucketcount').html() === '0'));
        }
        else { alert(r); }
      }, "text");
    });

    // Remove these PIDs from the existing bucket
    $('#<?=$opt->tableid?>-bucket-rem').click(function(event) {
      $.post("bucket.php", { rem:$('#<?=$opt->tableid?>-pids').text() }, function(r) {
        if (!isNaN(r)) {
          $('span.bucketcount').html(r);
          $('.bucket-list,.bucket-empty,.bucket-rem').toggleClass('disabledlink', ($('span.bucketcount').html() === '0'));
        }
        else { alert(r); }
      }, "text");
    });

    // Make the bucket contain only these PIDs (any previous contents are replaced)
    $('#<?=$opt->tableid?>-bucket-set').click(function(event) {
      $.post("bucket.php", { set:$('#<?=$opt->tableid?>-pids').text() }, function(r) {
        if (!isNaN(r)) {
          $('span.bucketcount').html(r);
          $('.bucket-list,.bucket-empty,.bucket-rem').toggleClass('disabledlink', ($('span.bucketcount').html() === '0'));
        }
        else { alert(r); }
      }, "text");
    });

    // export CSV
    $('#<?=$opt->tableid?>-csv').click(function() {
      $('#<?=$opt->tableid?>-csvtext').val($('#<?=$opt->tableid?>-table').table2CSV({delivery:'value'}));
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
      // Enable save button when any checkbox changes
      $('#<?=$opt->tableid?>-table .table-checkbox').change(function() {
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