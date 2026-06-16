<?php
 include("functions.php");
 include("accesscontrol.php");

pageheader("SQL Query", 1);
?>
<style>
/* page-specific rules (moved from style.php) */
body.sqlquery h2 { text-align:center; margin-bottom:10px; }
body.sqlquery input#submit { margin:10px auto 5px auto; padding:5px 40px 5px 40px; font-size: 1.5em; font-weight:bold; }
body.sqlquery form { margin:10px auto 5px auto; }
body.sqlquery #mainTable tbody td { vertical-align:top; }
</style>
<?php
if (!empty($_POST['query'])) {
  $query = stripslashes($_POST['query']);
  $result = mysqli_query($db, $query);
  try {
    $result = mysqli_query($db, $query);
  } catch (mysqli_sql_exception $e) {
    // PHP 8.1+ throws instead of returning false; the $result===false block below shows the error.
    $result = false;
  }
?>
<?php

  echo "<h2>Results of this query:</h2>\n";
  echo "<form action=\"sqlquery.php\" method=\"post\">\n";
  echo "<textarea name=\"query\" style=\"height:5em;width:100%\">".$query."</textarea>\n";
  echo "<p class=\"comment\">NOTE: If you include hyperlink tags, they must be by themselves (i.e. the column data must start with '&lt;a').</p>\n";
  echo "<input type=\"submit\" name=\"submit\" id=\"submit\" value=\"Do this new (modified) query!\" /></form>\n";

  if ($result === false ){
     echo "<pre style=\"font-size:15px;\"><strong>SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</strong></pre>";
     load_scripts(['jquery', 'tablesorter', 'table2csv']);
     footer();
     exit;
  }
  
  echo "Results of query <em>".$query."</em>:<hr>\n";
  if (strtoupper(substr($query,0,6)) == "UPDATE") {
    echo mysqli_affected_rows($db)." records successfully updated.";
  } elseif (strtoupper(substr($query,0,6)) == "INSERT") {
    echo mysqli_affected_rows($db)." records successfully inserted.";
  } elseif (strtoupper(substr($query,0,6)) == "DELETE") {
    echo mysqli_affected_rows($db)." records successfully deleted.";
  } elseif (strtoupper(substr($query,0,6)) == "SELECT") {
    $fields = mysqli_num_fields($result);
    $rows = mysqli_num_rows($result);
    echo "<p>$rows records returned.</p>";
?>
  <form action="download.php" method="post" target="_top">
    <input type="hidden" id="csvtext" name="csvtext" value="">
    <input type="submit" id="csvfile" name="csvfile" value="<?=_("Download a CSV file of this table")?>" onclick="getCSV();">
  </form>
<?php
    echo "<table id=\"mainTable\" class=\"tablesorter\">\n  <thead>\n    <tr>\n";
    for ($i=0; $i<$fields; $i++) {
      echo ("      <th nowrap>".mysqli_fetch_field_direct($result,$i)->name."</th>\n");
    }
    echo "    </tr>\n  </thead>\n  <tbody>\n";
    while ($row_array = mysqli_fetch_row($result)) {
      echo "  <tr>\n";
      for ($i=0; $i<$fields; $i++) {
        if (substr($row_array[$i],0,2)=="<a") {
          echo ("    <td nowrap>".$row_array[$i]."</td>\n");
        } elseif (mysqli_fetch_field_direct($result,$i)->name=="PersonID") {
          echo ("    <td><a href=\"individual.php?pid=".$row_array[$i]."\" target=\"_blank\">".$row_array[$i]."</a></td>\n");
        } else {
          echo ("    <td>".d2h($row_array[$i])."</td>\n");
        }
      }
      echo "  </tr>\n";
    }
    echo "  </tbody>\n</table>\n";
  } else {
    echo "Something unknown succeeded - return value ".$result.".";
  }
} else {
  echo "<h2>SQL Query in Kizuna Database</h2>";
  echo "<form action=\"sqlquery.php\" method=\"post\">\n";
  echo "<textarea name=\"query\"  style=\"height:5em;width:100%\"></textarea>";
  echo "<p class=\"comment\">NOTE: If you include hyperlink tags, they must be by themselves (i.e. the column data must start with '&lt;a').</p>\n";
  echo "<input type=\"submit\" name=\"submit\" id=\"submit\" value=\"Do the Query!\" /></form>";
}

load_scripts(['jquery', 'tablesorter', 'table2csv']);
?>
<script type="text/javascript">
$(document).ready(function() {
  $("#mainTable").tablesorter({
  });
});
function getCSV() {
  $('#csvtext').val($('#mainTable').table2CSV({delivery:'value'}));
}
</script>
<?php
footer();
?>
