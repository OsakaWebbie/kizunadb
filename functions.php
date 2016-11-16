<?php

//DEPRECATED
function print_header($title,$color,$nav) {
  header1($title);
  header2($nav);
  echo "<table border=\"0\" cellspacing=\"0\" cellpadding=\"0\" bgcolor=\"white\"><tr><td>";
}

function header1($title) {
?>
<? /* <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<? echo $_SESSION['lang']; ?>" lang="<? echo $_SESSION['lang']; ?>" dir="ltr"> */ ?>
<!DOCTYPE html>
<html>
<head>
<? //<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /> ?>
<meta charset="UTF-8">
<? //<meta http-equiv="Content-Script-Type" content="text/javascript" /> ?>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="icon" type="image/x-icon" href="/kizunaicon.ico">
<? //<link rel="shortcut icon" type="image/x-icon" href="/kizunaicon.ico" /> ?>
<?
  echo "<title>{$_SESSION['dbtitle']}: $title</title>\n";
}

function header2($nav=0) {
  echo "<link rel=\"stylesheet\" href=\"css/print.css\" type=\"text/css\" media=\"print\">\n";
  echo "</head>\n";
  $fileroot = substr($_SERVER['PHP_SELF'],(strrpos($_SERVER['PHP_SELF'],"/")+1),(strrpos($_SERVER['PHP_SELF'],".")-strrpos($_SERVER['PHP_SELF'],"/")-1));
  echo "<body class=\"".$fileroot.($nav?" full":" simple")."\">\n";
  
  if ($nav) {
    $navmarkup = "<ul class=\"nav\">\n";
    if ($_SESSION['hasdashboard']) {
      $navmarkup .= "  <li><a href=\"dashboard.php\" target=\"_top\">"._("Dashboard")."</a></li>\n";
    }
    $navmarkup .= "  <li><a href=\"search.php\" target=\"_top\">"._("Search")."</a></li>\n";
    $navmarkup .= "  <li><a href=\"edit.php\" target=\"_top\">"._("New Person/Org")."</a></li>\n";
    $navmarkup .= "  <li><a href=\"multiselect.php\" target=\"_top\">"._("Multi-Select")."</a></li>\n";
    $navmarkup .= "  <li><a href=\"contact.php\" target=\"_top\">"._("Contacts")."</a></li>\n";
    if ($_SESSION['donations'] == "yes") {
      $navmarkup .= "  <li><a href=\"donations.php\" target=\"_top\">"._("Donations &amp; Pledges")."</a></li>\n";
    }
    $navmarkup .= "  <li><a href=\"event_attend.php\" target=\"_top\">"._("Event Attendance")."</a></li>\n";
    $navmarkup .= "  <li><a href=\"birthday.php\" target=\"_top\">"._("Birthdays")."</a></li>\n";
    $navmarkup .= "  <li><a href=\"db_settings.php\" target=\"_top\">"._("DB Settings")."</a></li>\n";
    if ($_SESSION['admin'] == 1) {
      $navmarkup .= "  <li><a href=\"sqlquery.php\" target=\"_top\">"._("(Freeform SQL)")."</a></li>\n";
    }
    $navmarkup .= "  <li><a class=\"switchlang\" href=\"#\">".
    ($_SESSION['lang']=='en_US'?'日本語':'English')."</a></li>\n";
    $navmarkup .= "  <li class=\"menu-usersettings\"><a href=\"user_settings.php\" target=\"_top\">"._("User Settings")."<span> (".$_SESSION['username'].")</span></a></li>\n";
    $navmarkup .= "  <li><a href=\"index.php?logout=1\" target=\"_top\">"._("Log Out")."</a></li>\n</ul>\n";
    echo "<nav id=\"scrollnav\"></nav>\n";  //only appears when scrolled
    
    echo "<div id=\"main-container\">\n";
    echo "<nav id=\"nav-main\">\n$navmarkup</nav>\n";  //main nav for large screens
    echo "<div id=\"nav-trigger\"><img src=\"graphics/kizunadb-logo.png\" alt=\"Logo\"><span>Menu</span></div>\n";  //button for narrow screens
    echo "<nav id=\"nav-mobile\"></nav>\n";  //vertical menu for narrow screens
  }
  echo "<div id=\"content\">\n";
}

// Function print_footer: sends final html
function footer($nav=0) {
  echo "  <div style=\"clear:both\"></div>\n";
  echo "</div>\n"; //end of content div
  echo "</div>\n"; //end of main-container div

  // ANNOUNCEMENTS ABOUT NEW FEATURES, ETC., IF ANY
  if (isset($_SESSION['announcements'])) {
    echo "<style>\n  div.announcement { border:solid 2px lightgray; padding:5px; margin:5px; }\n";
    echo "  h4.announcedate,p.announcetext,body.dashboard h4.announcedate,body.dashboard p.announcetext { text-align:left; }\n</style>\n";
    echo '<div id="announcements" title="'._("Recent Announcements")."\">\n";
    foreach($_SESSION['announcements'] as $announcement) {
      echo "  <div class=\"announcement\">\n";
      echo "    <h4 class=\"announcedate\">".substr($announcement->AnnounceTime,0,10)."</h4>\n";
      echo "    <p class=\"announcetext\">".$announcement->HTML."</p>\n";
      echo "  </div>\n";
    }
    echo "</div>\n";
  } //end if announcements
?>
<script type="text/javascript">
if (window.jQuery) { //really simple files that don't have jQuery don't need this stuff either
  $(function() {
    $(window).scroll(function() {
      if ($(this).scrollTop() > 150 && !$('#scrollnav').hasClass('visible')) {
        $('#scrollnav').addClass('visible');
      } else if ($(this).scrollTop() <= 150 && $('#scrollnav').hasClass('visible')) {
        $('#scrollnav').removeClass('visible');
      }
    });

    $("#nav-mobile").html($("#nav-main").html());
    $("#scrollnav").html($("#nav-main").html());
    $("#nav-trigger").click(function(){
      if ($("nav#nav-mobile ul").hasClass("expanded")) {
        $("nav#nav-mobile ul.expanded").removeClass("expanded").slideUp(250);
        $(this).removeClass("open");
      } else {
        $("nav#nav-mobile ul").addClass("expanded").slideDown(250);
        $(this).addClass("open");
      }
    });

    $('.switchlang').click(function(event) {
      event.preventDefault();
      $.ajax({
        type: "POST",
        url: "ajax_actions.php?action=SwitchLang&lang=<? echo $_SESSION['lang']=='en_US'?'ja_JP':'en_US'; ?>",
        success: function() {
          location.reload(true);
        }
      });
    });
<? if (isset($_SESSION['announcements'])) { ?>
    $('#announcements').dialog({
      modal: true,
      buttons: [{
        text: "<? echo _("OK, I got it!"); ?>",
        click: function() {
          $( this ).dialog( "close" );
        }
      }],
      width: 460
    });
<?
  unset($_SESSION['announcements']); //now that it's shown, get rid of it
}
?>
  });
}
</script>
</body>
</html>
<?
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
     die("<pre style=\"font-size:15px;\"><strong>SQL Error ".mysql_errno()." in file ".$_SERVER['PHP_SELF'].": ".mysql_error()."</strong><br>$sql</pre>");
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
  if ($birthdate=='' || $birthdate=='0000-00-00') return '';
  $ba = explode("-",$birthdate);
  if ($ba[0]=='1900') return '?';
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

function showvar($varname) {
  global $$varname;
  echo "<h3>&#36;$varname</h3>\n<pre style=\"margin-left:2em\">";
  var_dump($$varname);
  echo '</pre>';
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
