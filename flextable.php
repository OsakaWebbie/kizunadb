<?php
require_once("functions.php");
require_once("accesscontrol.php");

// AJAX stuff
if (!empty($_GET['addcol'])) {

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

  // additional standard person/household columns if not already present
  $selects = '|'.implode('|',array_column($opt->cols, 'sel')).'|';
  //echo "Selects: $selects<br>";
  if (strpos($selects,'person.PersonID')===FALSE) $opt->cols[] = (object) [ 'sel' => 'person.PersonID', 'show'=>FALSE ];
  if (strpos($selects,'person.FullName')===FALSE) $opt->cols[] = (object) [ 'sel' => 'person.FullName', 'show'=>FALSE ];
  if (strpos($selects,'person.Furigana')===FALSE) $opt->cols[] = (object) [ 'sel' => 'person.Furigana', 'show'=>FALSE ];
  if (strpos($selects,'person.Email')===FALSE) $opt->cols[] = (object) [ 'sel' => 'person.Email', 'show'=>FALSE ];
  if (strpos($selects,'person.CellPhone')===FALSE) $opt->cols[] = (object) [ 'sel' => 'person.CellPhone', 'show'=>FALSE ];
  if (strpos($selects,'person.HouseholdID')===FALSE) $opt->cols[] = (object) [ 'sel' => 'person.HouseholdID', 'show'=>FALSE ];
  if (strpos($selects,'household.Phone')===FALSE) $opt->cols[] = (object) [ 'sel' => 'household.Phone', 'show'=>FALSE ];
  if (strpos($selects,'household.AddressComp')===FALSE) $opt->cols[] = (object) [ 'sel' => 'household.AddressComp',
      'label' => 'Address', 'show'=>FALSE ];
  if (strpos($selects,'|person.Birthdate|')===FALSE) $opt->cols[] = (object) [ 'sel' => 'person.Birthdate',
      'show'=>FALSE, 'classes'=>'center' ];
  if (strpos($selects,'TIMESTAMPDIFF(YEAR,person.Birthdate')===FALSE)
      $opt->cols[] = (object) [ 'sel' => "IF(person.Birthdate='0000-00-00','',TIMESTAMPDIFF(YEAR,person.Birthdate,CURDATE()))",
      'label' => 'Age', 'show'=>FALSE, 'classes'=>'center', 'table'=>'person' ];
  if (strpos($selects,'person.Sex')===FALSE) $opt->cols[] = (object) [ 'sel' => 'person.Sex', 'show'=>FALSE ];
  if (strpos($selects,'person.Country')===FALSE) $opt->cols[] = (object) [ 'sel' => 'person.Country', 'show'=>FALSE ];
  if (strpos($selects,'person.URL')===FALSE) $opt->cols[] = (object) [ 'sel' => 'person.URL', 'show'=>FALSE ];
  if (strpos($selects,'person.Remarks')===FALSE) $opt->cols[] = (object) [ 'sel' => 'person.Remarks', 'show'=>FALSE ];
  if (strpos($selects,'GROUP_CONCAT(Category')===FALSE) $opt->cols[] = (object) [
      'sel' => "GROUP_CONCAT(Category ORDER BY Category SEPARATOR '\\n')", 'label' => 'Categories', 'show'=>FALSE,
      'join'=>'LEFT JOIN percat ON person.PersonID=percat.PersonID LEFT JOIN category ON percat.CategoryID=category.CategoryID' ];

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
    if (!isset($col->classes)) $col->classes = '';
    if (!isset($col->total)) $col->total = FALSE;
    if ($col->sel == 'person.Name') $namecol_index = $index;
  }

  $opt->ids = trim($opt->ids, ',');
  list ($keytable,$keycol) = explode('.',$opt->keyfield,2);

  /***** SQL: BUILD AND RUN QUERY *****/

  $sql = 'SELECT ';
  $groupby = '';
  $joins = ($opt->keyfield=='person.PersonID') ? '' : 'LEFT JOIN person ON person.PersonID='.$keytable.'.PersonID '.
      'LEFT JOIN household ON household.HouseholdID=person.HouseholdID ';

  // Columns and expressions for SELECT
  foreach ($opt->cols AS $col) {
    if ($col->sel=='person.Name') continue;
    elseif ($col->show || $col->sel=='person.PersonID' || $col->sel=='person.FullName' || $col->sel=='person.Furigana' || $col->sel=='person.HouseholdID') {
      $sql .= $col->sel." AS '".str_replace(' ','',$col->label)."', ";
      if (!empty($col->join) && strpos($joins,$col->join)===FALSE) $joins .= $col->join.' ';
    }
  }
  if (strpos($selects,'|'.$opt->keyfield.'|') === FALSE) $sql .= $opt->keyfield.', ';
  $sql = substr($sql,0,-2);  // remove last comma and space
  if (preg_match('#(GROUP_CONCAT|MAX|MIN|COUNT|SUM|AVERAGE)#i',$sql)===1) $groupby = ' GROUP BY '.$opt->keyfield;
  $sql .= ' FROM '.$keytable.' '.$joins.' WHERE '.$opt->keyfield.' IN ('.$opt->ids.')'.$groupby;
  //echo '<h4>Passed parameters:</h4><xmp>'.var_dump($opt).'</xmp>';
  //echo '<h4>SQL:</h4><xmp style="white-space:pre-wrap">'.$sql.'</xmp>';
  $result = sqlquery_checked($sql);

  /***** BUTTONS: column selector, bucket links, multi-select link, and CSV *****/

  ?>
  <div>
    <h3 style="display:inline-block; margin-right:2em"><?=$opt->heading?></h3>
    <div class="button-block" style="display:inline-block">
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
      <button id="<?=$opt->tableid?>-csv"><?=_('Download CSV')?></button>
    </div>
  </div>

  <div id="<?=$opt->tableid?>-colsel" style="display:none; padding:5px 15px 15px 15px">
    <form style="line-height:2em">
  <?php
  foreach ($opt->cols as $index => $col ) {
    echo '<label><input type="checkbox" id="'.classize($col->label).'-show" name="'.classize($col->label).'-show" class="colsel-checkbox"';
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
        if ($col->sel=='person.Name') {
          echo '<th class="name-for-csv loaded" style="display:none">'._($col->label).' ('.($_SESSION['furiganaisromaji']=='yes' ? _('Romaji') : _('Furigana')).')</th>';
          echo '<th class="name-for-display loaded">'._($col->label).' ('.($_SESSION['furiganaisromaji']=='yes' ? _('Romaji') : _('Furigana')).')</th>';
        } else {
          echo '<th class="'.str_replace(' ','-',strtolower($col->label)).($col->show?' loaded':'').'"'.
              ($col->show?'':' style="display:none"').'>'._($col->label).'</th>';
        }
      }
      ?>
    </tr></thead>
    <tbody>
    <?php

    /***** TABLE BODY *****/

    $pids = ','; //need boundary for duplicate check
    while ($row = mysqli_fetch_object($result)) {
      if (!empty($row->PersonID) && strpos($pids,','.$row->PersonID.',') === FALSE) $pids .= $row->PersonID.',';
      echo "  <tr>\n";
      foreach ($opt->cols as $col) {
        // determine SQL table and set class for this cell
        if (!empty($col->table)) {  // explicitly specified (needed in the case of expressions)
          $table = $col->table;
        } elseif (strpos($col->sel, '.') === FALSE) {  // no table in SELECT, so assume $keytable
          $table = $keytable;
        } else {  // assume table.Column format
          $table = substr($col->sel, 0, strpos($col->sel, '.'));
        }
        if ($table == 'person') $cellclass = 'pid' . $row->PersonID;
        elseif ($table == 'household') $cellclass = 'hid' . $row->HouseholdID;
        else $cellclass = 'key' . $row->$keycol;

        if ($col->sel == 'person.Name') {
          echo '    <td class="name-for-csv pid-'.$row->PersonID.'" style="display:none">'.readable_name($row->FullName,$row->Furigana)."</td>\n";
          echo '    <td class="name-for-display pid-'.$row->PersonID.'" nowrap><span style="display:none">'.$row->Furigana.'</span>'.
              '<a href="individual.php?pid='.$row->PersonID.'">'.readable_name($row->FullName,$row->Furigana,0,0,'<br>')."</a></td>\n";
        } else {
          echo '    <td class="' . $cellclass . '"' . ($col->show ? '' : ' style="display:none"') . '>';
          if ($col->show || $col->sel=='person.PersonID' || $col->sel=='person.FullName' || $col->sel=='person.Furigana' || $col->sel=='person.HouseholdID') {
            if ($col->sel == 'person.Photo') echo ($row->Photo == 1) ? '<img src="photo.php?f=p' . $row->PersonID . '" width=50>' : '';
            else echo $row->{str_replace(' ', '', $col->label)};
          }
          echo "</td>\n";
        }
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

  var $opt = <?=json_encode($opt)?>;
  //console.log($opt);

  $(function() {
    /*** jQuery UI styling ***/
    $('button[id^=<?=$opt->tableid?>]').button();
    $('#<?=$opt->tableid?>-colsel input').checkboxradio();

    $('#<?=$opt->tableid?>-table').tablesorter();

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
      $("#<?=$opt->tableid?>-table .name-for-display").hide();
      $("#<?=$opt->tableid?>-table .name-for-csv").show();
      $('#<?=$opt->tableid?>-csv').val($('#<?=$opt->tableid?>-table').table2CSV({delivery:'value'}));
      $("#<?=$opt->tableid?>-table .name-for-csv").hide();
      $("#<?=$opt->tableid?>-table .name-for-display").show();
    });
  });

</script>
  <?php
}

/*** adds spaces to make label more readable ***/
function tableof($text) {
  return _(preg_replace('#(?<!^)([A-Z][a-z]|(?<=[a-z])[A-Z])#',' ',
      strpos($col->sel,'.')===FALSE ? $col->sel : substr($col->sel,strpos($col->sel,'.'))));
}

/*** adds spaces to make label more readable ***/
function classize($text) {
  return preg_replace('#[^a-z-]#','',str_replace(' ','-',strtolower($text)));
}