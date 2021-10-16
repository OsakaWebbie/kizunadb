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
  ctl (TRUE): allow user to hide/show this column
  sort (0): use 1, 2, etc. to indicate initial sorting
  classes (''): e.g. sorter-false sorter-digit
  total (FALSE): put a sum of this column in the table footer
***/

function flextable($opt) {

  echo '<xmp>'.var_dump($opt).'</xmp>';
  //return;

  /***** FILL IN DEFAULTS *****/

  if (empty($opt->ids)) die('"ids" property missing.');
  if (!isset($opt->keyfield)) $opt->keyfield = 'person.PersonID';
  if (!isset($opt->joins)) $opt->joins = '';
  if (!isset($opt->header)) $opt->header = '';
  if (!isset($opt->rowcolor)) $opt->rowcolor = '';
  foreach ($opt->cols AS $col) {
    if (empty($col->sel)) die('"sel" property missing from a column.');
    if (empty($col->label)) $col->label = 'person.PersonID';
    if (!isset($col->show)) $col->show = TRUE;
    if (!isset($col->ctl)) $col->ctl = TRUE;
    if (!isset($col->sort)) $col->sort = 0;
    if (!isset($col->classes)) $col->classes = '';
    if (!isset($col->total)) $col->total = FALSE;
  }
  $opt->ids = trim($opt->ids, ',');
  list ($keytable,$keycol) = explode('.',$opt->keyfield,2);

  /***** SQL: BUILD AND RUN QUERY *****/

  $sql = 'SELECT ';
  $groupby = '';

  // loop through requested columns
  foreach ($opt->cols AS $col) {
    if ($col->sel=='person.NameCombo') {  // special case that needs CSV-friendly column
      $sql .= 'person.PersonID, person.FullName, person.Furigana, ';
    } else {
      $sql .= $col->sel." AS '".str_replace(' ','',$col->label)."', ";
    }
  }
  if (strpos($sql,$opt->keyfield) === FALSE) $sql .= $opt->keyfield.', ';
  $sql = substr($sql,0,-2);  // remove last comma and space
  if (preg_match('#(GROUP_CONCAT|MAX|MIN|COUNT|SUM|AVERAGE)#i',$sql)===1) $groupby = ' GROUP BY '.$opt->keyfield;
  $sql .= ' FROM '.$keytable.' '.$opt->joins.' WHERE '.$opt->keyfield.' IN ('.$opt->ids.')'.$groupby;
  $result = sqlquery_checked($sql);
  echo '<xmp style="white-space:pre-wrap">'.$sql.'</xmp>';

  /***** BUTTONS: hide/show options, bucket links, multi-select link, and CSV *****/

  ?>
  <h3><?=$opt->heading?>
    <button id="<?=$opt->tableid?>-colsel"><?=_('Show/Hide Columns'.' ▼')?></button>
    <button id="<?=$opt->tableid?>-bucket"><?=_('Bucket').' ('.count($_SESSION['bucket']).') ▼'?></button>
    <a href="">
      <button id="<?=$opt->tableid?>-ms" onclick="location.href = 'multiselect.php?';" title="<?=_('Go to Multi-Select with these entries preselected')?>"><?=_('To Multi-Select')?></button>
    </a>
    <button id="<?=$opt->tableid?>-csv"><?=_('Download CSV')?></button>
  </h3>
  <?php

  /***** TABLE *****/

  ?>
  <table id="<?=$opt->tableid?>-table" class="tablesorter">
    <thead><tr>
      <?php
      foreach ($opt->cols AS $col) {
        if ($col->sel=='person.NameCombo') {
          echo '<th class="name-for-csv" style="display:none">'._($col->label).' ('.($_SESSION['furiganaisromaji']=='yes' ? _('Romaji') : _('Furigana')).')</th>';
          echo '<th class="name-for-display">'._($col->label).' ('.($_SESSION['furiganaisromaji']=='yes' ? _('Romaji') : _('Furigana')).')</th>';
        } else {
          echo '<th class="'.str_replace(' ','-',strtolower($col->label)).'"'.($col->show?'':' style="display:none"').'>'._($col->label).'</th>';
        }
      }
      ?>
    </tr></thead>
    <tbody>
    <?php
    $id_array = explode(',',$opt->ids);
    while ($row = mysqli_fetch_object($result)) {
      //echo "<xmp>".var_dump($row)."</xmp>";
      $thisid = $row->$keycol;
      echo "  <tr>\n";
      foreach ($opt->cols as $col) {
        if ($col->sel == 'person.NameCombo') {
          echo '    <td class="name-for-csv key-'.$thisid.'" style="display:none">'.readable_name($row->FullName,$row->Furigana).'</td>';
          echo '<td class="name-for-display key-'.$thisid.'" nowrap><span style="display:none">'.$row->Furigana.'</span>';
          echo '<a href="individual.php?pid='.$row->PersonID."\">".readable_name($row->FullName,$row->Furigana,0,0,"<br>")."</a></td>\n";
        } else {
          echo '    <td class="'.str_replace(' ', '-', strtolower($col->label)).' key-'.$thisid.'"' . ($col->show ? '' : ' style="display:none"') . '>' .
              $row->{str_replace(' ','',$col->label)} . "</td>\n";
        }
      }
      echo "  </tr>\n";
    }
    ?>

    </tbody>
  </table>

  <script>

<?php
load_scripts(array('jquery','jqueryui','tablesorter','table2csv'));

/***** "SEND" INFO TO JAVASCRIPT *****/

?>


    var $opt = <?=json_encode($opt)?>;
    console.log($opt);

    if ($('script[src="js/jquery.tablesorter.js"]').length === 0) {
      $.getScript('js/jquery.tablesorter.js');
      alert("loaded tablesorter");
    }

    $(function() {
      $('#pids-for-bucket').val('<?=implode(',',$pids)?>');

      $("#<?=$opt->tableid?>").tablesorter({
        sortList:[[2,0]],
        headers:{14:{sorter:false}}
      });

    });

    function getCSV() {
      $(".name-for-display, .selectcol").hide();
      $(".name-for-csv").show();
      $('#<?=$opt->tableid?>-csv').val($('#<?=$opt->tableid?>-table').table2CSV({delivery:'value'}));
      $(".name-for-csv").hide();
      $(".name-for-display, .selectcol").show();
    }
  </script>
  <?php
}