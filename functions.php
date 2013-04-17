<?php

//DEPRECATED
function print_header($title,$color,$nav) {
  header1($title);
  header2($nav);
  echo "<table border=\"0\" cellspacing=\"0\" cellpadding=\"0\" bgcolor=\"white\"><tr><td>";
}

function header1($title) {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<? echo $_SESSION['lang']; ?>" lang="<? echo $_SESSION['lang']; ?>" dir="ltr" >
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<link rel="icon" type="image/x-icon" href="/kizunaicon.ico" />
<link rel="shortcut icon" type="image/x-icon" href="/kizunaicon.ico" />
<?
  echo "<title>{$_SESSION['dbtitle']}: $title</title>\n";
}

function header2($nav=0) {
  echo "<link rel=\"stylesheet\" href=\"css/print.css\" type=\"text/css\" media=\"print\" />\n";
  echo "</head>\n";
  $fileroot = substr($_SERVER['PHP_SELF'],(strrpos($_SERVER['PHP_SELF'],"/")+1),(strrpos($_SERVER['PHP_SELF'],".")-strrpos($_SERVER['PHP_SELF'],"/")-1));
  echo "<body class=\"".$fileroot.($nav?" full":" simple")."\">\n";
  
  if ($nav) {
    $navmarkup = "<ul class=\"nav\">\n";
    if ($_SESSION['hasdashboard']) {
      $navmarkup .= "  <li class=\"menu-dashboard\"><a href=\"dashboard.php\" target=\"_top\">"._("Dashboard")."</a></li>\n";
    }
    $navmarkup .= "  <li class=\"menu-search\"><a href=\"search.php\" target=\"_top\">"._("Search")."</a></li>\n";
    $navmarkup .= "  <li class=\"menu-edit\"><a href=\"edit.php\" target=\"_top\">"._("New Person/Org")."</a></li>\n";
    $navmarkup .= "  <li class=\"menu-multiselect\"><a href=\"multiselect.php\" target=\"_top\">"._("Multi-Select")."</a></li>\n";
    $navmarkup .= "  <li class=\"menu-contact\"><a href=\"contact.php\" target=\"_top\">"._("Contacts")."</a></li>\n";
    if ($_SESSION['donations'] == "yes") {
      $navmarkup .= "  <li class=\"menu-donations\"><a href=\"donations.php\" target=\"_top\">"._("Donations & Pledges")."</a></li>\n";
    }
    $navmarkup .= "  <li class=\"menu-eventattend\"><a href=\"event_attend.php\" target=\"_top\">"._("Event Attendance")."</a></li>\n";
    $navmarkup .= "  <li class=\"menu-birthday\"><a href=\"birthday.php\" target=\"_top\">"._("Birthdays")."</a></li>\n";
    $navmarkup .= "  <li class=\"menu-maintenance\"><a href=\"maintenance.php\" target=\"_top\">"._("DB Maintenance")."</a></li>\n";
    if ($_SESSION['admin'] == 1) {
      $navmarkup .= "  <li class=\"menu-sqlquery\"><a href=\"sqlquery.php\" target=\"_top\">"._("(Freeform SQL)")."</a></li>\n";
    }
    $navmarkup .= "  <li class=\"menu-logout\"><a href=\"index.php?logout=1\" target=\"_top\">"._("Log Out")." (".$_SESSION['username'].")</a></li>\n</ul>\n";
    echo "<div id=\"scrollnav\">\n".$navmarkup."</div>\n";  //navbar that only appears when scrolled
    
    echo "<div id=\"main-container\">\n";
    echo $navmarkup;  //main navbar
  }
  ?>
<script>
$(function() {
  $(window).scroll(function() {
    if ($(this).scrollTop() > 150 && !$('#scrollnav').hasClass('visible')) {
      $('#scrollnav').addClass('visible');
    } else if ($(this).scrollTop() <= 150 && $('#scrollnav').hasClass('visible')) {
      $('#scrollnav').removeClass('visible');
    }
  });
});
</script>
<?
  echo "<div id=\"content\">\n";
}

function print_nav() {
  $navmarkup = "<ul class=\"nav\">\n";
  if ($_SESSION['hasdashboard']) {
    $navmarkup .= "  <li class=\"menu-dashboard\"><a href=\"dashboard.php\" target=\"_top\">"._("Dashboard")."</a></li>\n";
  }
  $navmarkup .= "  <li class=\"menu-search\"><a href=\"search.php\" target=\"_top\">"._("Search")."</a></li>\n";
  $navmarkup .= "  <li class=\"menu-edit\"><a href=\"edit.php\" target=\"_top\">"._("New Person/Org")."</a></li>\n";
  $navmarkup .= "  <li class=\"menu-multiselect\"><a href=\"multiselect.php\" target=\"_top\">"._("Multi-Select")."</a></li>\n";
  $navmarkup .= "  <li class=\"menu-contact\"><a href=\"contact.php\" target=\"_top\">"._("Contacts")."</a></li>\n";
  if ($_SESSION['donations'] == "yes") {
    $navmarkup .= "  <li class=\"menu-donations\"><a href=\"donations.php\" target=\"_top\">"._("Donations & Pledges")."</a></li>\n";
  }
  $navmarkup .= "  <li class=\"menu-eventattend\"><a href=\"event_attend.php\" target=\"_top\">"._("Event Attendance")."</a></li>\n";
  $navmarkup .= "  <li class=\"menu-birthday\"><a href=\"birthday.php\" target=\"_top\">"._("Birthdays")."</a></li>\n";
  $navmarkup .= "  <li class=\"menu-maintenance\"><a href=\"maintenance.php\" target=\"_top\">"._("DB Maintenance")."</a></li>\n";
  if ($_SESSION['admin'] == 1) {
    $navmarkup .= "  <li class=\"menu-sqlquery\"><a href=\"sqlquery.php\" target=\"_top\">"._("(Freeform SQL)")."</a></li>\n";
  }
  $navmarkup .= "  <li class=\"menu-logout\"><a href=\"index.php?logout=1\" target=\"_top\">"._("Log Out")." (".$_SESSION['username'].")</a></li>\n</ul>\n";
  
  echo $navmarkup."<div id=\"scrollnav\">\n".$navmarkup."</div>\n";
  ?>
<script>
$(function() {
  $(window).scroll(function() {
    if ($(this).scrollTop() > 150 && !$('#scrollnav').hasClass('visible')) {
      $('#scrollnav').addClass('visible');
    } else if ($(this).scrollTop() <= 150 && $('#scrollnav').hasClass('visible')) {
      $('#scrollnav').removeClass('visible');
    }
  });
});
</script>
<?
}

// Function print_footer: sends final html
function footer($nav=0) {
  if ($nav) {
    //print_nav();
  }
  echo "</div>\n"; //end of content div
  echo "</div></body></html>";  //end of main-container div
}
//DEPRECATED
function print_footer() {
  echo "</td></tr></table>";
  footer(0);
}

// Function to shorten the repeated checks for SQL errors
function sqlquery_checked($sql) {
  $result = mysql_query($sql);
  if ($result === false ){
     die("<pre style=\"font-size:15px;\"><strong>SQL Error ".mysql_errno()." in file ".$PHP_SELF.": ".mysql_error()."</strong><br>$sql</pre>");
  }
  return $result;
}

function today() {
return date("Y-m-d",mktime(gmdate("H")+9));
}

// Function showfile: for "including" a non-PHP file (HTML, JS, etc.)
function showfile($filename) {
  if (!$file = fopen($filename,"r")) {
    echo "<br><font color=red>Could not open file '$filename'!</font><br>";
  } else {
    fpassthru($file);
  }
}

// DEPRECATED
function db2table($text) {
//  $text = ereg_replace("\r\n|\n|\r","<br>",$text);
//  $text = ereg_replace("<br> ","<br>&nbsp;",$text);
  return d2h($text);
}

function d2h($text) {
  return nl2br(htmlspecialchars($text, ENT_QUOTES, mb_internal_encoding()));
}

function d2f($text) {
  return str_replace('"','\"',$text);
}

function d2j($text) {  //makes carriage returns safe for JSON
  return preg_replace("/\r\n|\n|\r/","\\n",$text);
}

// DEPRECATED
function post2form($text) {
//  $text = ereg_replace("\'","'",$text);
  return stripslashes($text);
}

function h2d($text) {
  //return ereg_replace("'","\\'",ereg_replace("\"","\\\"",$text));
  return mysql_real_escape_string($text);
}

function escape_quotes($text) {
  $text = str_replace("\"","\\\"",$text);
  return $text;
}

// Function readable_name: returns name and optionally ID, adding "furigana" if the first character is not Roman alphabet and breaking if desired
function readable_name($name,$furigana,$pid=0,$org=0,$break="",$reverse=0) {
  if ($pid && ($_SESSION['showid']=="yes" || $org)) {
    $text = ($reverse?$furigana:$name)." ["._("ID").": ".$pid."]";
  } else {
    $text = ($reverse?$furigana:$name);
  }
//  if (!ereg("^[a-zA-Z]",$name)) {  //name starts with a non-alphabet character
  if (mb_strlen($name) != strlen($name)) {  //name has multi-byte characters in it
    $text .= $break." (".($reverse?$name:$furigana).")";
    if (strpos($break,"<span>")) $text .= "</span>";
    if (strpos($break,"<div>")) $text .= "</div>";
  }
  return $text;
}

// DEPRECATED
function readable_name_2line($name,$furigana) {
  return readable_name($name,$furigana,0,0,"<br />");
}

// Function age: takes birthdate in the form YYYY-MM-DD as argument, returns age
function age($birthdate) {
  $ba = explode("-",$birthdate);
  $ta = explode("-",date("Y-m-d",mktime(gmdate("H")+9)));
  $age = $ta[0] - $ba[0];
  if (($ba[1] > $ta[1]) || (($ba[1] == $ta[1]) && ($ba[2] > $ta[2]))) --$age;
  return $age;
}

// Function code_display: escapes HTML tag codes so that HTML code can be displayed as is
function code_display($code) {
  $code = str_replace("\<","&lt;",$code);
  $code = str_replace("\>","&gt;",$code);
  return "<code>$code</code>";
}

function i18n($text) {
  // to catch ones I already did this way
  return(_($text));
}

function url2link($text) {
  return preg_replace('@(https?://([-\w\.]+)+(:\d+)?(/([\w/_\.]*((\?|#)[^<\.,;\s]+)?)?)?)@', '<a href="$1">$1</a>', $text);
}

function email2link($text) {
  return preg_replace('/\b([A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4})\b/i', '<a href="mailto:$0">$0</a>', $text);
}

// Get client login credentials and connect to client database
$hostarray = explode(".",$_SERVER['HTTP_HOST']);
include("/var/www/".$hostarray[0]."/kizuna_connect.php");
$connection = mysql_connect("localhost","kz_".$client,$pass) or die("Failed to connect user "."kz_".$client." (".$pass.").");
mysql_select_db("kizuna_".$client,$connection);

/* for certain versions of SQL, this next is needed, but others would give an error, */
/* so no error checking is done */
mysql_query("set names 'utf8'");

/* Set internal character encoding to UTF-8 */
mb_internal_encoding("UTF-8");
mb_regex_encoding("UTF-8");

sqlquery_checked("SET SESSION group_concat_max_len = 4096");

?>
