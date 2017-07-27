<?php
 include("functions.php");
 include("accesscontrol.php");

header1("SQL Query");

if ($_POST['query']) {
  $query = stripslashes($_POST['query']);
  $result = mysqli_query($db, $query);
?>
<link rel="stylesheet" href="style.php?table=1" type="text/css" />
<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/table2CSV.js"></script>
<script type="text/javascript" src="js/tablesorter.js"></script>
<script type="text/javascript">
$(document).ready(function() {
  $("#mainTable").tablesorter({
<?php
/*  if ((strtoupper(substr($query,0,6)) == "SELECT") && (mb_eregi(".*order by (.*)",$query,$order))) {
    $order_split = explode(",",$order[0]);
    for ($i=0; $i<mysqli_num_fields($result); $i++) {
      $meta = mysqli_fetch_field($result, $i);
      if (mb_strpos($order_split[0],$meta->name)) {
        echo "    sortList:[[".$i.",".(mb_strpos("desc",$order_split[0])?"1":"0")."]]";
        break;
      }
    }
  } */
?>
  });
});
function getCSV() {
  $('#csvtext').val($('#mainTable').table2CSV({delivery:'value'}));
}
</script>
<?php
  header2(1);
  
  echo "<h2>Results of this query:</h2>\n";
  echo "<form action=\"sqlquery.php\" method=\"post\">\n";
  echo "<textarea name=\"query\" style=\"height:5em;width:100%\">".$query."</textarea>\n";
  echo "<p class=\"comment\">NOTE: If you include hyperlink tags, they must be by themselves (i.e. the column data must start with '&lt;a').</p>\n";
  echo "<input type=\"submit\" name=\"submit\" id=\"submit\" value=\"Do this new (modified) query!\" /></form>\n";

  if ($result === false ){
     echo "<pre style=\"font-size:15px;\"><strong>SQL Error ".mysql_errno($db).": ".mysqli_error($db)."</strong></pre>";
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
      echo ("      <th nowrap>".mysqli_field_name($result,$i)."</th>\n");
    }
    echo "    </tr>\n  </thead>\n  <tbody>\n";
    while ($row_array = mysqli_fetch_row($result)) {
      echo "  <tr>\n";
      for ($i=0; $i<$fields; $i++) {
        if (substr($row_array[$i],0,2)=="<a") {
          echo ("    <td nowrap>".$row_array[$i]."</td>\n");
        } elseif (mysqli_field_name($result,$i)=="PersonID") {
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
  ?> <link rel="stylesheet" href="style.php" type="text/css" /> <?php
  header2(1);
  echo "<h2>SQL Query in Contacts Database</h2>";
  echo "<form action=\"sqlquery.php\" method=\"post\">\n";
  echo "<textarea name=\"query\"  style=\"height:5em;width:100%\"></textarea>";
  echo "<p class=\"comment\">NOTE: If you include hyperlink tags, they must be by themselves (i.e. the column data must start with '&lt;a').</p>\n";
  echo "<input type=\"submit\" name=\"submit\" id=\"submit\" value=\"Do the Query!\" /></form>";
}

footer();
?>
