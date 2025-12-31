<?php

error_reporting(E_ALL ^ E_NOTICE);
ini_set('display_errors',0);

function head_content($title, $nav=0, $extracss='') {
  header1($title);
  echo '<link rel="stylesheet" href="style.php" type="text/css">'."\n";
  foreach (explode(',',$extracss) as $css) {
    switch ($css) {
      case 'jqueryui':
        echo '<link rel="stylesheet" href="css/jquery-ui-13.css" type="text/css">'."\n";
        break;
      case 'tablesorter':
        echo '<link rel="stylesheet" href="css/tablesorter.css" type="text/css">'."\n";
        break;
    }
  } //end foreach css
  header2($nav);
}
function header1($title) {
  ?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="icon" type="image/x-icon" href="kizunadb.ico">
<title><?=(isset($_SESSION['dbtitle']) ? $_SESSION['dbtitle'].': ' : '').$title?></title>
  <?php
}

function header2($nav=0) {
  echo "</head>\n";
  $fileroot = substr($_SERVER['PHP_SELF'],(strrpos($_SERVER['PHP_SELF'],"/")+1),(strrpos($_SERVER['PHP_SELF'],".")-strrpos($_SERVER['PHP_SELF'],"/")-1));
  echo "<body class=\"".$fileroot.($nav?" full":" simple")."\">\n";

  if ($nav) {  // build main desktop menu and create divs for menu for scrolling and mobile menu (duplicated by jQuery in footer)
?>
<nav id="scrollnav"></nav>

<div id="main-container">
  <nav id="nav-main">
    <ul class="nav">
      <?=(!empty($_SESSION['hasdashboard']) ? '<li><a href="dashboard.php" target="_top">'._('Dashboard').'</a></li>' : '')?>
      <li class="not-on-scroll"><form action="list.php" style="display:inline"><input class="qs-text" name="qs" maxlength="30" placeholder="<?=_('(quick search)')?>"
          style="width:7em;vertical-align:baseline" autofocus></form>(<span class="qs-hits">-</span>)</li>
      <li class="hassub">
        <a href="#"><?=_('People/Orgs')?> &#x25BC;</a>
        <ul class="nav-sub">
          <li><a href="search.php" target="_top"><?=_('Search')?></a></li>
          <li><a href="edit.php" target="_top"><?=_('New Person/Org')?></a></li>
        </ul>
      </li>
      <li class="hassub">
        <a href="#"><?=_('Aux. Searches')?> &#x25BC;</a>
        <ul class="nav-sub">
          <li><a href="action.php" target="_top"><?=_('Actions')?></a></li>
<?=(!empty($_SESSION['donations']) ? '          <li><a href="donations.php" target="_top">'._('Donations &amp; Pledges').'</a></li>' : '')?>
          <li><a href="event_attend.php" target="_top"><?=_('Event Attendance')?></a></li>
          <li><a href="birthday.php" target="_top"><?=_('Birthdays')?></a></li>
        </ul>
      </li>
      <li class="hassub">
        <a href="#"><?=_('Batch/Bucket').' (<span class="bucketcount">'.count($_SESSION['bucket']).'</span>)'?> &#x25BC;</a>
        <ul class="nav-sub">
          <li class="bucket-list"><a class="bucket-list" href="list.php?bucket=1"><?=_('List Bucket contents')?></a></li>
          <li><a href="multiselect.php" target="_top"><?=_('Multi-Select').'/'._('Batch')?></a></li>
          <li class="bucket-empty"><a class="ajaxlink bucket-empty" href="#"><?=_('Empty the Bucket')?></a></li>
        </ul>
      </li>
      <li><a href="db_settings.php" target="_top"><?=_('DB Settings')?></a></li>
<?=(!empty($_SESSION['admin']) ? '      <li><a href="sqlquery.php" target="_top">'._('(Raw SQL)').'</a></li>' : '')?>
      <li><a class="switchlang" href="#"><?=($_SESSION['lang']=='en_US'?'日本語':'English')?></a></li>
      <li class="menu-usersettings hassub">
        <a href="#"><?=_('User')?><span class="username">: <?=$_SESSION['username']?></span> &#x25BC;</a>
        <ul class="nav-sub">
          <li><a href="user_settings.php" target="_top"><?=_('User Settings')?></a></li>
          <li><a href="index.php?logout=1" target="_top"><?=_('Log Out')?></a></li>
        </ul>
      </li>
    </ul>
  </nav>

  <div id="nav-trigger"><img src="graphics/kizunadb-logo.png" alt="Logo"><span>Menu</span></div>
  <nav id="nav-mobile"></nav>
  <input type="hidden" id="pids-for-bucket" value="">
<?php
  }  // end of if $nav
  echo "<div id=\"content\">\n";
}  // end of header2()

// Function footer: sends final html
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
        $("#nav-mobile li.not-on-scroll").remove();
        $("#scrollnav li.not-on-scroll").remove();

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
                url: "ajax_actions.php?action=SwitchLang&lang=<?=$_SESSION['lang']=='en_US'?'ja_JP':'en_US' ?>",
                success: function() {
                    location.reload(true);
                }
            });
        });

        /* event handling for submenus (must be JS because of menu links that don't refresh the page) */
        $(".hassub").click(function() { $("ul",this).show(); })
        .mouseleave(function(){ $("ul",this).hide(); });

        $('.ajaxlink').click(function(event) {
          $(this).closest('ul').hide();
          $("nav#nav-mobile ul.expanded").removeClass("expanded").slideUp(250);
          $("#nav-trigger").removeClass("open");
        });

        /* QUICK SEARCH */
        var timeoutID = null;  //using setTimeout to only send after user pauses typing slightly
        $(".qs-text").keyup(function() {
          if ($(this).val().length > 2) {
            if (timeoutID != null) clearTimeout(timeoutID);
            timeoutID = setTimeout(function() { $.get("ajax_request.php?req=Quicksearch&qs="+encodeURIComponent($(".qs-text").val()), function(data) {
              if (data.alert === "NOSESSION") {
                alert("<?=_("Your login has timed out - please refresh the page.")?>");
              } else {
                $('.qs-hits').text(data.hits);
              }
            }, 'json'); },300);
          } else {
            $('.qs-hits').text('-');
          }
        });

        /* BUCKET MANAGEMENT */

        // Make the bucket contain only these PIDs (any previous contents are replaced)
        $('.bucket-empty').click(function() {
          $.post("bucket.php", { empty:1 }, function(r) {
            if (!isNaN(r)) {
              $('span.bucketcount').html(r);
              $('.bucket-list,.bucket-empty,.bucket-rem').toggleClass('disabledlink', ($('span.bucketcount').html() === '0'));
            }
            else { alert(r); }
          }, "text");
        });

        //set initial state of bucket links
        $('.bucket-list,.bucket-empty,.bucket-rem').toggleClass('disabledlink', ($('span.bucketcount').html() === '0'));

        // To prevent the href=# from scrolling to the top
        $('.disabledLink').click(function(event) {
          event.preventDefault();
        });

      /* ANNOUNCEMENTS OF NEW FEATURES OR MAJOR CHANGES */
      <?php if (isset($_SESSION['announcements'])) { ?>
        $('#announcements').dialog({
            modal: true,
            buttons: [{
                text: "<?=_("OK, I got it!") ?>",
                click: function() {
                    $( this ).dialog( "close" );
                }
            }],
            width: 460
        });
        <?php
        unset($_SESSION['announcements']); //now that it's shown, get rid of it
        }
        ?>
      });
    }

    /* for use in detecting changes to #pids-for-bucket value; from https://stackoverflow.com/a/41589301/1436451 */
    function replaceWithWrapper(obj, property, callback) {
      Object.defineProperty(obj, property, new function() {
        var _value = obj[property];
        return {
          set: function(value) {
            _value = value;
            callback(obj, property, value)
          },
          get: function() {
            return _value;
          }
        }
      });
    }

  </script>
</body>
</html>
<?php
}

//DEPRECATED
function print_header($title,$color,$nav) {
  header1($title);
  header2($nav);
  echo "<table><tr><td>";
}

//DEPRECATED
function print_footer() {
  echo "</td></tr></table>";
  footer();
}

/*** LOAD SCRIPTS - common location for version #, and makes sure only loaded once ***/
/*** pass array of script name roots ***/
$scripts_loaded = array();
function load_scripts($scripts) {
  global $scripts_loaded;
  foreach ($scripts as $script) {
    if (empty($scripts_loaded[$script])) {
      switch ($script) {
        case 'jquery':
          echo '<script type="text/JavaScript" src="js/jquery-3.6.0.js"></script>'."\n";
          break;
        case 'jqueryui':
          echo '<script type="text/JavaScript" src="js/jquery-ui-13.min.js"></script>'."\n";
          break;
        case 'tablesorter':
          echo '<script type="text/JavaScript" src="js/jquery.tablesorter.js"></script>'."\n";
          break;
        case 'table2csv':
          echo '<script type="text/JavaScript" src="js/table2CSV.js"></script>'."\n";
          break;
        case 'timepicker':
          echo '<script type="text/JavaScript" src="js/jquery.ui.timepicker.js"></script>'."\n";
          break;
        case 'readmore':
          echo '<script type="text/JavaScript" src="js/jquery.readmore.js"></script>'."\n";
          break;
        case 'expanding':
          echo '<script type="text/JavaScript" src="js/expanding.js"></script>'."\n";
          break;
      }
      $scripts_loaded[$script] = 1;
    }
  }
}

function load_jqueryui() {
  if (!empty($jqueryui_loaded)) {
    echo '<script type="text/JavaScript" src="js/jquery.js"></script>'."\n";
    $jqueryui_loaded = 1;
  }
}

// function sqlquery_checked: shorten the repeated checks for SQL errors
function sqlquery_checked($sql) {
  global $db;

  try {
    $result = mysqli_query($db, $sql);

    // Handle PHP 7.x where mysqli_query returns false on error
    if ($result === false) {
      throw new Exception(mysqli_error($db));
    }

    return $result;

  } catch (Exception $e) {
    // This catches both:
    // - PHP 8.x: mysqli_sql_exception thrown automatically
    // - PHP 7.x: our manually thrown Exception

    // Use your existing error formatting
    die('<xmp style="white-space:pre-wrap;font-size:15px;font-weight:bold">SQL Error in file '.$_SERVER['PHP_SELF'].': '.$e->getMessage().'</xmp><xmp style="white-space:pre-wrap">'.$sql.'</xmp>');
  }
}


function today() {
  return date("Y-m-d",mktime(gmdate("H")+9));
}

// DEPRECATED
function db2table($text) {
  return d2h($text);
}

function d2h($text) {
  if ($text === NULL) $text = '';
  return nl2br(htmlspecialchars($text, ENT_QUOTES, mb_internal_encoding()));
}

function d2f($text) {
  return str_replace('"','\"',$text);
}

function d2j($text) {  //makes carriage returns safe for JSON
  return preg_replace("/\r\n|\n|\r/","\\n",$text);
}

function h2d($text) {
  global $db;
  return mysqli_real_escape_string($db, $text);
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
  if (mb_strlen($name) != strlen($name)) {  //name has multi-byte characters in it
    $text .= $break." (".($reverse?$name:$furigana).")";
    if (strpos($break,"<span>")) $text .= "</span>";
    if (strpos($break,"<div>")) $text .= "</div>";
  }
  return $text;
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

function url2link($text) {
  // I have no idea how this works - I got it from https://gist.github.com/winzig/8894715 (2017/11/07)
  // removing the part that looks for URLs with no protocol (because that was too greedy).
  // I don't know why this matches on a multibyte domain name, but it does.
  return preg_replace('~\b((?:https?:(?:/{1,3}|[a-z0-9%])|[a-z0-9.\-]+[.](?:[a-z]{2,13})/)'.
      '(?:[^\s()<>{}\[\]]+|\([^\s()]*?\([^\s()]+\)[^\s()]*?\)|\([^\s]+?\))+(?:\([^\s()]*?\([^\s()]+\)[^\s()]*?\)|'.
      '\([^\s]+?\)|[^\s`!()\[\]{};:\'".,<>?«»“”‘’]))~iu',
      '<a href="$1">$1</a>', $text);
}

function email2link($text) {
  return preg_replace('/\b([a-z0-9._%+-]+@[\w.-]+\.[a-z]{2,13})\b/iu', '<a href="mailto:$0">$0</a>', $text);
}

// STUFF THAT GETS RUN RIGHT AWAY

$hostarray = explode(".",$_SERVER['HTTP_HOST']);
define('CLIENT',$hostarray[0]);
define('CLIENT_PATH',"/var/www/kizunadb/client/".CLIENT);
// Get client login credentials and connect to client database
$configfile = CLIENT_PATH."/kizunadb.ini";
if (!is_readable($configfile)) die("No KizunaDB configuration file. Notify the developer.");
$config = parse_ini_file($configfile);
$db = mysqli_connect("localhost", "kizuna_".CLIENT, $config['password'], "kizuna_".CLIENT)
    or die("Failed to connect to database. Notify the developer.");

mysqli_set_charset($db, "utf8mb4");

// Set internal character encoding to UTF-8
//die('current internal_encoding is '.mb_internal_encoding().' and current regex_encoding is '.mb_regex_encoding());
//mb_internal_encoding("UTF-8");
//mb_regex_encoding("UTF-8");

//sqlquery_checked("SET SESSION group_concat_max_len = 4096");
