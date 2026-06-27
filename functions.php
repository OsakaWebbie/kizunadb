<?php

error_reporting(E_ALL ^ E_NOTICE);
ini_set('display_errors',0);
ini_set('log_errors', '1');
ini_set('error_log', dirname(__DIR__).'/log/error.log');   // app-owned, outside webroot; FPM's default destination is unreliable

// --- Centralized failure handling -----------------------------------------
// Logs full detail for the developer and shows users a deliberate notice,
// instead of raw PHP/SQL output bleeding into pages, PDFs, or AJAX responses.
// App-neutral except the log label below; copy verbatim to sister apps.

function error_context() {   // where is this request's output going?
  $self = basename($_SERVER['PHP_SELF'] ?? '');
  if (str_starts_with($self, 'ajax_')) return 'json';
  if (strcasecmp($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '', 'XMLHttpRequest') === 0) return 'json';
  foreach (headers_list() as $h)
    if (stripos($h, 'content-type:') === 0)
      return stripos($h, 'text/html') !== false ? 'html' : 'binary';
  return 'html';
}

// $logmsg: developer-facing, always logged. $devextra (e.g. the SQL): shown on screen only to dev.
function fail($logmsg, $devextra = '') {
  static $already = false;
  if ($already) return;            // guard against exception-then-shutdown double-fire
  $already = true;

  $ref = strtoupper(substr(md5($logmsg.microtime(true)), 0, 6));
  error_log('KizunaDB [ref '.$ref.']: '.$logmsg.($devextra !== '' ? ' || '.$devextra : ''));

  if (!headers_sent()) http_response_code(500);
  $dev = (($_SESSION['userid'] ?? '') === 'dev');
  $usermsg = sprintf(_('Something broke on my end — not your fault. Please tell the developer what you were doing (reference: %s).'), $ref);

  switch (error_context()) {
    case 'json':
      if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');
      echo json_encode([
        'success' => false,
        'ref'     => $ref,
        'error'   => $dev ? $logmsg.($devextra !== '' ? "\n\n".$devextra : '') : $usermsg,
      ]);
      break;

    case 'binary':   // a PDF/audio/CSV stream; can't inject readable output once it's flowing
      if (!headers_sent()) {
        header('Content-Type: text/plain; charset=utf-8', true);
        echo $dev ? $logmsg."\n\n".$devextra : $usermsg;
      }
      break;

    default:         // html
      if ($dev) {
        echo '<pre style="white-space:pre-wrap;font-size:15px;font-weight:bold;color:#85001f">'
           . htmlspecialchars($logmsg, ENT_QUOTES, 'UTF-8').'</pre>';
        if ($devextra !== '')
          echo '<pre style="white-space:pre-wrap">'.htmlspecialchars($devextra, ENT_QUOTES, 'UTF-8').'</pre>';
      } else {
        echo '<div style="margin:1em auto;max-width:40em;padding:1em 1.25em;border:2px solid #85001f;'
           . 'border-radius:6px;background:#fff5f5;font-size:16px;line-height:1.5">'
           . $usermsg
           . '<p style="margin:1em 0 0">'
           . sprintf(_("%sReturn to the home page%s, or press your browser's Back button to try again."), '<a href="index.php">', '</a>')
           . '</p></div>';
      }
  }
  exit;
}

set_exception_handler(function (Throwable $e) {
  fail(get_class($e).': '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine(),
       $e->getTraceAsString());
});

register_shutdown_function(function () {
  $e = error_get_last();
  if ($e && ($e['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR)))
    fail('Fatal: '.$e['message'].' in '.$e['file'].':'.$e['line']);
});

// Maintenance mode check - blocks POST requests during migration
// Enable: touch /var/www/kizunadb/MAINTENANCE_MODE
// Bypass: echo "YOUR.IP" > /var/www/kizunadb/MAINTENANCE_BYPASS_IPS
// Disable: rm /var/www/kizunadb/MAINTENANCE_MODE
$maintenance_file = '/var/www/kizunadb/MAINTENANCE_MODE';
if (file_exists($maintenance_file)) {
    $bypass = false;
    $bypass_file = '/var/www/kizunadb/MAINTENANCE_BYPASS_IPS';
    if (file_exists($bypass_file)) {
        $allowed = file($bypass_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $bypass = in_array($_SERVER['REMOTE_ADDR'], $allowed);
    }
    if (!$bypass && $_SERVER['REQUEST_METHOD'] === 'POST') {
        header('HTTP/1.1 503 Service Unavailable');
        header('Content-Type: text/html; charset=utf-8');
        die('<html><head><title>Maintenance</title></head><body style="font-family:sans-serif;text-align:center;padding:50px">
            <h1>System Maintenance / メンテナンス中</h1>
            <p>Write operations temporarily disabled. Please try again soon.</p>
            <p>データの変更は一時的に停止しています。しばらくお待ちください。</p>
            </body></html>');
    }
}

/*** CSS_BUNDLE: emit the standard static stylesheet bundle (shared by pageheader() ***/
/*** and the login head in accesscontrol.php). All files are static & cacheable.      ***/
/*** Order: reset, stock jquery-ui, then style.css (our theme incl. the jQuery UI     ***/
/*** retint+sheen) so it wins the cascade, then the client's optional                 ***/
/*** css/custom/<client>/colors.css (palette overrides). ***/
function css_bundle() {
  $client = explode(".",$_SERVER['HTTP_HOST'])[0];
  $dir = __DIR__;                       // filesystem base (public/) for filemtime/is_file
  $customfs  = "$dir/css/custom/$client";
  $customurl = "css/custom/$client";    // webroot-relative for hrefs
  // favicon: per-client (css/custom/<client>/favicon.ico) else the site default (favicon.ico)
  $favfs  = is_file("$customfs/favicon.ico") ? "$customfs/favicon.ico" : "$dir/favicon.ico";
  $favurl = is_file("$customfs/favicon.ico") ? "$customurl/favicon.ico" : "favicon.ico";
  echo '<link rel="icon" type="image/x-icon" href="'.$favurl.'?v='.filemtime($favfs).'">'."\n";
  echo '<link rel="stylesheet" type="text/css" href="css/reset.css">'."\n";
  // stock jquery-ui BEFORE style.css so style.css (our theme + the jQuery UI retint) wins the cascade
  echo '<link rel="stylesheet" type="text/css" href="css/jquery-ui.min.css">'."\n";
  echo '<link rel="stylesheet" type="text/css" href="css/style.css?v='.filemtime("$dir/css/style.css").'">'."\n";
  if (is_file("$customfs/colors.css"))            // per-client palette overrides, win over style.css
    echo '<link rel="stylesheet" type="text/css" href="'.$customurl.'/colors.css?v='.filemtime("$customfs/colors.css").'">'."\n";
  echo '<link rel="stylesheet" type="text/css" href="css/tablesorter.css?v='.filemtime("$dir/css/tablesorter.css").'">'."\n";
  echo '<link rel="stylesheet" type="text/css" href="css/jquery.multiselect.css">'."\n";
  echo '<link rel="stylesheet" type="text/css" href="css/jquery.multiselect.filter.css">'."\n";
}

/*** PAGEHEADER: single-call replacement for header1()+header2(). ***/
/*** (Can't be named header() — that's a built-in PHP function.) ***/
/*** Emits the full <head> incl. the static CSS bundle + per-client overrides, ***/
/*** then opens <body> + (optional) nav + #content. Page-specific <style>/<script> ***/
/*** go AFTER the call (in <body>), where they still override the main stylesheet. ***/
function pageheader($title, $nav=0) {
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="expires" content="0">
<title><?=$title.(isset($_SESSION['dbtitle']) ? ' ('.$_SESSION['dbtitle'].')' : '')?></title>
<?php
  css_bundle();   /* emits the favicon (client-aware) + the stylesheet bundle */
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
<?=(!empty($_SESSION['donations']) ? '          <li><a href="donation.php" target="_top">'._('Donations &amp; Pledges').'</a></li>' : '')?>
          <li><a href="attendance.php" target="_top"><?=_('Event Attendance')?></a></li>
          <li><a href="birthday.php" target="_top"><?=_('Birthdays')?></a></li>
        </ul>
      </li>
      <li class="hassub">
        <a href="#"><?=_('Batch/Basket').' (<span class="basketcount">'.count($_SESSION['basket'] ?? []).'</span>)'?> &#x25BC;</a>
        <ul class="nav-sub">
          <li class="basket-list"><a class="basket-list" href="list.php?basket=1"><?=_('List Basket contents')?></a></li>
          <li><a href="batch.php?basket=1" target="_top"><?=_('Batch Processing')?></a></li>
          <li class="basket-empty"><a class="ajaxlink basket-empty" href="#"><?=_('Empty the Basket')?></a></li>
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
  <input type="hidden" id="pids-for-basket" value="">
<?php
  }  // end of if $nav
  echo "<div id=\"content\">\n";
}  // end of pageheader()

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
        $(document).ajaxError(function(event, jqXHR) {
          if (jqXHR.statusText === 'abort') return;   // user navigated away mid-request
          var msg = (jqXHR.responseJSON && jqXHR.responseJSON.error)
            ? jqXHR.responseJSON.error
            : '<?=_("Something went wrong. Please try again, and let the developer know if it keeps happening.")?>';
          alert(msg);
        });

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
                url: "ajax_action.php?action=SwitchLang&lang=<?=$_SESSION['lang']=='en_US'?'ja_JP':'en_US' ?>",
                success: function() {
                    location.reload(true);
                }
            });
        });

        /* event handling for submenus (must be JS because of menu links that don't refresh the page) */
        /* delegated binding so it works for AJAX-loaded content too */
        var hoverTimer;
        /* hover to open/close - exclude #nav-mobile */
        /* Use delay only for button-based menus to avoid touch/click conflict */
        $(document).on("mouseenter", ".hassub:not(#nav-mobile .hassub)", function() {
          var $ul = $("ul", this);
          if ($(this).find("> button").length > 0) {
            hoverTimer = setTimeout(function() {
              $ul.show();
            }, 200);
          } else {
            $ul.show();
          }
        })
        .on("mouseleave", ".hassub:not(#nav-mobile .hassub)", function(){
          clearTimeout(hoverTimer);
          $("ul",this).hide();
        });
        /* click to toggle - all menus including #nav-mobile */
        $(document).on("click", ".hassub > a, .hassub > button", function(event) {
          event.preventDefault();
          clearTimeout(hoverTimer);
          $(this).siblings("ul").toggle();
        });
        /* click outside hassub to close any open menus (but not nav-mobile which has its own handling) */
        $(document).on("click", function(event) {
          if (!$(event.target).closest(".hassub").length) {
            $(".hassub").not("#nav-mobile .hassub").find("ul").hide();
          }
        });

        $(document).on("click", ".ajaxlink", function(event) {
          event.preventDefault();
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

        /* BASKET MANAGEMENT */

        // Make the basket contain only these PIDs (any previous contents are replaced)
        $('.basket-empty').click(function() {
          $.post("basket.php", { empty:1 }, function(r) {
            if (!isNaN(r)) {
              $('span.basketcount').html(r);
              $('.basket-list,.basket-empty,.basket-rem').toggleClass('disabledlink', ($('span.basketcount').html() === '0'));
            }
            else { alert(r); }
          }, "text");
        });

        //set initial state of basket links
        $('.basket-list,.basket-empty,.basket-rem').toggleClass('disabledlink', ($('span.basketcount').html() === '0'));

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

    /* for use in detecting changes to #pids-for-basket value; from https://stackoverflow.com/a/41589301/1436451 */
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

/*** LOAD SCRIPTS - common location for version #, and makes sure only loaded once ***/
/*** pass array of script name roots ***/
$scripts_loaded = array();
function load_scripts($scripts) {
  global $scripts_loaded;
  $dir = __DIR__;                       // filesystem base (public/) for filemtime cache-busting
  foreach ($scripts as $script) {
    if (empty($scripts_loaded[$script])) {
      switch ($script) {
        case 'jquery':
          echo '<script type="text/JavaScript" src="js/jquery-3.6.0.min.js?v='.filemtime("$dir/js/jquery-3.6.0.min.js").'"></script>'."\n";
          break;
        case 'jqueryui':
          echo '<script type="text/JavaScript" src="js/jquery-ui.min.js?v='.filemtime("$dir/js/jquery-ui.min.js").'"></script>'."\n";
          break;
        case 'tablesorter':
          echo '<script type="text/JavaScript" src="js/jquery.tablesorter.min.js?v='.filemtime("$dir/js/jquery.tablesorter.min.js").'"></script>'."\n";
          // empties sort with the column (top when ascending) instead of the fork's always-at-bottom default
          echo '<script>$.tablesorter.defaults.emptyTo = "none";</script>'."\n";
          break;
        case 'table2csv':
          echo '<script type="text/JavaScript" src="js/table2CSV.js?v='.filemtime("$dir/js/table2CSV.js").'"></script>'."\n";
          break;
        case 'expanding':
          echo '<script type="text/JavaScript" src="js/expanding.js?v='.filemtime("$dir/js/expanding.js").'"></script>'."\n";
          break;
        case 'multiselect':
          echo '<script type="text/JavaScript" src="js/jquery.multiselect.js?v='.filemtime("$dir/js/jquery.multiselect.js").'"></script>'."\n";
          echo '<script type="text/JavaScript" src="js/jquery.multiselect.filter.js?v='.filemtime("$dir/js/jquery.multiselect.filter.js").'"></script>'."\n";
          break;
        case 'multiselect-classes':
          echo '<script type="text/JavaScript" src="js/jquery.multiselect-classes.js?v='.filemtime("$dir/js/jquery.multiselect-classes.js").'"></script>'."\n";
          echo '<script type="text/JavaScript" src="js/jquery.multiselect.filter.js?v='.filemtime("$dir/js/jquery.multiselect.filter.js").'"></script>'."\n";
          break;
        case 'datepicker-ja':
          echo '<script type="text/JavaScript" src="js/i18n/datepicker-ja.js?v='.filemtime("$dir/js/i18n/datepicker-ja.js").'"></script>'."\n";
          break;
        case 'readmore':
          echo '<script type="text/JavaScript" src="js/readmore.js?v='.filemtime("$dir/js/readmore.js").'"></script>'."\n";
          break;
        case 'functions':
          echo '<script type="text/JavaScript" src="js/functions.js?v='.filemtime("$dir/js/functions.js").'"></script>'."\n";
          break;
      }
      $scripts_loaded[$script] = 1;
    }
  }
}

// function sqlquery_checked: shorten the repeated checks for SQL errors
function sqlquery_checked($sql) {
  global $db;
  try {
    return mysqli_query($db, $sql);
  } catch (mysqli_sql_exception $e) {
    fail('SQL error in '.basename($_SERVER['PHP_SELF']).': '.$e->getMessage(), $sql);
  }
}

// Find existing person/org records that look like potential duplicates of the given
// field values. Shared by get_duplicates.php (the new-entry check in edit.php) and by
// merge_duplicates.php (the duplicate finder). Pass RAW (un-escaped) values; normalization
// and escaping happen here. $excludePid omits a record from matching itself.
// Returns a mysqli result of person.* + household.* + postalcode.* (matches across name,
// and optionally narrowed by postal code, or broadened by cell phone / email).
function find_duplicate_persons($fullname, $furigana, $postalcode, $cellphone, $email, $excludePid = 0) {
  $fullname = h2d(str_replace(" ", "", $fullname));
  $furigana = h2d(str_replace(",", "", str_replace(" ", "", $furigana)));
  $cond = "(LOWER(REPLACE(FullName,' ',''))=LOWER(REPLACE('".$fullname."',' ',''))".
      " OR LOWER(REPLACE(REPLACE(Furigana,' ',''),',',''))=LOWER(REPLACE(REPLACE('".$furigana."',' ',''),',','')))";
  if ($postalcode != "") {
    $cond .= " AND (household.PostalCode='".h2d($postalcode)."' OR household.PostalCode IS NULL OR household.PostalCode = '')";
  }
  if ($cellphone != "") {
    $cond .= " OR (REPLACE(person.CellPhone,'-','')=REPLACE('".h2d($cellphone)."','-',''))";
  }
  if ($email != "") {
    $cond .= " OR (LOWER(person.Email)=LOWER('".h2d($email)."'))";
  }
  $sql = "SELECT DISTINCT person.*, household.*, postalcode.* FROM person ".
      "LEFT JOIN household ON person.HouseholdID=household.HouseholdID ".
      "LEFT JOIN postalcode ON household.PostalCode=postalcode.PostalCode ".
      "WHERE (".$cond.")";
  if ($excludePid) {
    $sql .= " AND person.PersonID <> ".intval($excludePid);
  }
  return sqlquery_checked($sql);
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
// Pin the connection collation to match the schema (all utf8mb4 columns are
// utf8mb4_unicode_ci). Without this, string literals default to
// utf8mb4_general_ci, which is harmless against unicode_ci columns (the column
// collation wins) but caused "Illegal mix of collations" once we also start
// comparing literals to each other or to ascii columns. Keep mysqli_set_charset
// above (don't switch to SET NAMES) so escaping stays correct.
mysqli_query($db, "SET collation_connection = 'utf8mb4_unicode_ci'");

// Set internal character encoding to UTF-8
//die('current internal_encoding is '.mb_internal_encoding().' and current regex_encoding is '.mb_regex_encoding());
//mb_internal_encoding("UTF-8");
//mb_regex_encoding("UTF-8");

//sqlquery_checked("SET SESSION group_concat_max_len = 4096");
