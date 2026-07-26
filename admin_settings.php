<?php
include("functions.php");
include("accesscontrol.php");

// Admin-only page.
if (empty($_SESSION['admin'])) {
  if (!empty($_POST['ajax'])) { header('Content-Type: application/json'); die(json_encode(array('error' => _('Access denied.')))); }
  header('Location: index.php');
  exit;
}

$donations = !empty($_SESSION['donations']);

// The list-column groups this page manages, arranged by the page they appear on and in the order
// they appear there: Search Results (list.php), then the Person/Org detail page (individual.php,
// which shows an Organizations list then a Members list), then Household Information, then the
// Aux. Searches pages in menu order. Each list role is blank when the page has a single list.
// Donation-related lists are only offered when donations are enabled.
$showcols_pages = array(
  array('head' => _('Search Results'),         'lists' => array('list_showcols' => '')),
  array('head' => _('Person/Org Detail Page'), 'lists' => array('org_showcols' => _('Organizations'), 'member_showcols' => _('Members'))),
  array('head' => _('Household Information'),   'lists' => array('household_showcols' => '')),
  array('head' => _('Action List'),            'lists' => array('actionlist_showcols' => '')),
  array('head' => _('Donation List'),          'lists' => array('donationlist_showcols' => '')),
  array('head' => _('Pledge List'),            'lists' => array('pledgelist_showcols' => '')),
  array('head' => _('Attendance Summary'),     'lists' => array('attendaggr_showcols' => '')),
  array('head' => _('Birthday List'),          'lists' => array('birthdaylist_showcols' => '')),
);
// Flat param => label (page + role) for save/validation messages, and the params to process.
$showcols_labels = array();
foreach ($showcols_pages as $pg) {
  foreach ($pg['lists'] as $param => $role) {
    $showcols_labels[$param] = $pg['head'].($role !== '' ? ' — '.$role : '');
  }
}
$showcols_params = array_keys($showcols_labels);
if (!$donations) $showcols_params = array_values(array_diff($showcols_params, array('donationlist_showcols', 'pledgelist_showcols')));

// Canonical columns each "*_showcols" param can toggle: param => ordered (token => label). The token
// is the value stored in the comma-list (e.g. 'orgfullname'), not always the flextable column key
// (e.g. 'org-fullname'). Labels mirror the list pages (list.php, individual.php, action_list.php,
// donation_list.php, pledge_list.php, attend_aggregate.php, birthday.php, household.php) — keep in
// sync when a page's columns change. $base + $pick avoid repeating the shared person-column labels.
$romaji = (($_SESSION['furiganaisromaji'] ?? '') == 'yes');
$furi   = $romaji ? _('Romaji') : _('Furigana');   // parenthetical used in the "Orgs (…)" labels
$base = array(
  'personid'   => _('ID'),
  'name'       => _('Name (display)'),
  'fullname'   => _('Full Name'),
  'furigana'   => $romaji ? _('Romaji Name') : _('Furigana Name'),
  'photo'      => _('Photo'),
  'relation'   => _('Relation in Household'),
  'phones'     => _('Phones'),
  'phone'      => _('Phone'),
  'email'      => _('Email'),
  'address'    => _('Address'),
  'birthdate'  => _('Birthdate'),
  'age'        => _('Age'),
  'sex'        => _('Sex'),
  'country'    => _('Country'),
  'url'        => _('URL'),
  'remarks'    => _('Remarks'),
  'categories' => _('Categories'),
  'events'     => _('Events'),
);
$pick = function(array $tokens, array $over = array()) use ($base) {   // ordered token => label
  $out = array();
  foreach ($tokens as $t) $out[$t] = $over[$t] ?? $base[$t] ?? $t;
  return $out;
};
$defs = array(
  'list_showcols' => $pick(
    array('personid','name','fullname','furigana','photo','phones','email','address','birthdate','age','sex','country','url','remarks','categories','events','orgfullname','orgfurigana'),
    array('orgfullname' => _('Orgs').' ('._('Full Name').')', 'orgfurigana' => _('Orgs').' ('.$furi.')')
  ),
  'member_showcols' => $pick(
    array('personid','name','fullname','furigana','photo','phones','email','address','birthdate','age','sex','country','url','remarks','categories','events')
  ),
  'org_showcols' => $pick(
    array('personid','name','fullname','furigana','photo','phones','email','address','birthdate','age','sex','country','url','remarks','categories','events')
  ),
  'household_showcols' => $pick(
    array('personid','name','fullname','furigana','photo','relation','sex','age','birthdate','categories','email','url','country','remarks')
  ),
  'birthdaylist_showcols' => $pick(
    array('personid','name','fullname','furigana','birthday','ageafterbday','photo','phones','email','address','age','birthdate','sex','country','url','categories','events','remarks'),
    array('birthday' => _('Birthday'), 'ageafterbday' => _('Age after Birthday'))
  ),
  'actionlist_showcols' => $pick(
    array('personid','name','fullname','furigana','photo','phones','email','address','age','birthdate','sex','country','url','remarks','adate','atype','desc'),
    array('adate' => _('Date'), 'atype' => _('Action Type'), 'desc' => _('Description'))
  ),
  'attendaggr_showcols' => $pick(
    array('personid','name','fullname','furigana','photo','phones','email','address','birthdate','age','sex','country','url','remarks','event','first','last','count','hours'),
    array('event' => _('Event'), 'first' => _('First'), 'last' => _('Last'), 'count' => _('# Times'), 'hours' => _('Total Hours'))
  ),
  'donationlist_showcols' => $pick(
    array('personid','name','fullname','furigana','phone','email','address','country','remarks','categories','events','ddate','dtype','pledge','amount','desc','proc'),
    array('country' => _('Home Country'), 'ddate' => _('Date'), 'dtype' => _('Donation Type'), 'pledge' => _('Pledge?'), 'amount' => _('Amount'), 'desc' => _('Description'), 'proc' => _('Processed'))
  ),
  'pledgelist_showcols' => $pick(
    array('personid','name','fullname','furigana','photo','phones','email','address','sex','age','birthdate','country','url','remarks','dtype','dates','desc','amount','interval','balance','months','categories'),
    array('dtype' => _('Donation Type'), 'dates' => _('Dates'), 'desc' => _('Description'), 'amount' => _('Amount'), 'interval' => _('Interval'), 'balance' => _('Balance'), 'months' => _('Months'))
  ),
);

// ---- This page's own AJAX (config/user saves + user overview); returns JSON ----
if (!empty($_POST['ajax'])) {
  header('Content-Type: application/json');
  switch ($_POST['ajax']) {

  case 'SaveMisc':
    $updates = array();
    $dbtitle = trim($_POST['dbtitle'] ?? '');
    if (mb_strlen($dbtitle) > 200) die(json_encode(array('error' => _('The database title is too long (200 characters maximum).'))));
    $updates['dbtitle'] = $dbtitle;
    $updates['showid']  = empty($_POST['showid']) ? 'no' : 'yes';
    $photo_labels = array(
      'pphoto_maxwidth'    => _('Person photo maximum width'),
      'pphoto_targetwidth' => _('Person photo resize width'),
      'hphoto_maxwidth'    => _('Household photo maximum width'),
      'hphoto_targetwidth' => _('Household photo resize width'),
    );
    foreach ($photo_labels as $par => $label) {
      $v = intval($_POST[$par] ?? 0);
      if ($v < 1) die(json_encode(array('error' => sprintf(_('%s must be a positive number.'), $label))));
      $updates[$par] = (string)$v;
    }
    if ($donations) {
      $updates['hidedonations_default'] = empty($_POST['hidedonations_default']) ? 'no' : 'yes';
      $tpy = $_POST['pledge_tpy'] ?? '12';
      $updates['pledge_tpy'] = in_array($tpy, array('0','1','4','12'), true) ? $tpy : '12';
      $updates['pledge_tpy'] = $tpy;
    }
    foreach ($updates as $par => $val) {
      sqlquery_checked("REPLACE INTO config (Parameter, Value) VALUES ('".h2d($par)."', '".h2d($val)."')");
      $_SESSION[$par] = $val;
    }
    die(json_encode(array('message' => _('Changes saved.'))));

  case 'SaveShowcols':
    $out  = array();
    foreach ($showcols_params as $param) {
      $posted = (isset($_POST[$param]) && is_array($_POST[$param])) ? $_POST[$param] : array();
      $chosen = array();
      foreach (array_keys($defs[$param]) as $token) {         // known tokens only, canonical order
        if (in_array($token, $posted, true)) $chosen[] = $token;
      }
      if (!$chosen) die(json_encode(array('error' => sprintf(_('Please choose at least one column for "%s".'), $showcols_labels[$param]))));
      $out[$param] = implode(',', $chosen);
    }
    foreach ($out as $param => $val) {
      sqlquery_checked("REPLACE INTO config (Parameter, Value) VALUES ('".h2d($param)."', '".h2d($val)."')");
      $_SESSION[$param] = $val;
    }
    die(json_encode(array('message' => _('Changes saved.'))));

  case 'UserOverview':
    $h = '<table class="tablesorter ov-tbl"><thead>'.
      '<tr>'.
      '<th rowspan="2">'._('UserID').'</th><th rowspan="2">'._('Name').'</th>'.
      '<th rowspan="2" class="center">'._('Admin').'</th><th rowspan="2">'._('Last Login').'</th>'.
      '<th colspan="3" class="center sorter-false">'._('# Logins in prior:').'</th>'.
      '<th rowspan="2">'._('First Login').'</th>'.
      '</tr><tr>'.
      '<th class="center">'._('30d').'</th><th class="center">'._('365d').'</th>'.
      '<th class="center">'._('All').'</th>'.
      '</tr></thead><tbody>';
    $res = sqlquery_checked(
      "SELECT u.UserID, u.UserName, u.Admin, MAX(l.LoginTime) last, DATE(MIN(l.LoginTime)) first, ".
      "COUNT(l.LoginTime) total, ".
      "SUM(l.LoginTime >= NOW() - INTERVAL 30 DAY) last30, ".
      "SUM(l.LoginTime >= NOW() - INTERVAL 1 YEAR) lastyear ".
      "FROM user u LEFT JOIN loginlog l ON u.UserID=l.UserID ".
      "GROUP BY u.UserID, u.UserName, u.Admin ORDER BY u.UserID");
    while ($r = mysqli_fetch_object($res)) {
      $h .= '<tr><td>'.htmlspecialchars($r->UserID).'</td>'.
        '<td>'.htmlspecialchars($r->UserName).'</td>'.
        '<td class="center">'.($r->Admin ? '&#10004;' : '').'</td>'.
        '<td>'.($r->last !== null ? htmlspecialchars($r->last) : '—').'</td>'.
        '<td class="center">'.(int)$r->last30.'</td>'.
        '<td class="center">'.(int)$r->lastyear.'</td>'.
        '<td class="center">'.(int)$r->total.'</td>'.
        '<td>'.($r->first !== null ? htmlspecialchars($r->first) : '—').'</td></tr>';
    }
    $h .= '</tbody></table>';
    die(json_encode(array('html' => $h)));

  case 'QuicksearchFieldsSave':
    $valid     = array_keys(quicksearch_field_defs());
    $requested = array_filter(array_map('trim', explode(',', $_REQUEST['fields'] ?? '')));
    $fields    = array_values(array_intersect($valid, $requested));   // known keys only, canonical order
    if (!$fields) die(json_encode(array('error' => _('Please choose at least one field for quick search.'))));
    $value = implode(',', $fields);
    sqlquery_checked("REPLACE INTO config (Parameter, Value) VALUES ('quicksearch_fields', '".h2d($value)."')");
    $_SESSION['quicksearch_fields'] = $value;
    die(json_encode(array('message' => _('Quick Search fields updated.'))));

  case 'UserGet':
    if (($_REQUEST['userid'] ?? '') === '') die(json_encode(array('error' => _('Invalid user ID'))));
    $result = sqlquery_checked(
      "SELECT user.*, YEAR(LoginTime) loginyear, MAX(LoginTime) loginlast, COUNT(LoginTime) loginnum ".
      "FROM user LEFT JOIN loginlog ON user.UserID=loginlog.UserID ".
      "WHERE user.UserID='".h2d($_REQUEST['userid'])."' ".
      "GROUP BY user.UserID, YEAR(LoginTime) ORDER BY YEAR(LoginTime) DESC");
    if (mysqli_num_rows($result) == 0) die(json_encode(array('error' => _('Record not found.'))));
    $arr = null; $totalLogins = 0; $yearStats = array(); $lastLogin = null;
    while ($row = mysqli_fetch_object($result)) {
      if ($arr === null) {
        $arr = array('userid' => $row->UserID, 'new_userid' => $row->UserID, 'old_userid' => $row->UserID,
            'username' => $row->UserName, 'email' => $row->Email, 'language' => $row->Language,
            'new_pw1' => '', 'new_pw2' => '', 'dashboard' => $row->Dashboard,
            'admin' => $row->Admin, 'hidedonations' => $row->HideDonations);
        $lastLogin = $row->loginlast;
      }
      if ($row->loginyear !== null) {
        $totalLogins += $row->loginnum;
        $yearStats[] = $row->loginyear . ": " . $row->loginnum;
      }
    }
    if ($lastLogin === null) {
      $arr['loginstats'] = _("Never logged in");
    } else {
      $loginStats  = sprintf(_("Last login: %s"), $lastLogin);
      $loginStats .= " &bull; " . sprintf(_("Total: %d"), $totalLogins);
      if (count($yearStats) > 1) $loginStats .= " (" . implode(", ", $yearStats) . ")";
      $arr['loginstats'] = $loginStats;
    }
    die(json_encode($arr));

  case 'UserSave':
    $username   = trim($_REQUEST['username'] ?? '');
    $new_userid = trim($_REQUEST['new_userid'] ?? '');
    if ($username === '')   die(json_encode(array('success' => false, 'error' => _('User Name cannot be blank.'))));
    if ($new_userid === '') die(json_encode(array('success' => false, 'error' => _('UserID cannot be blank.'))));
    $lang_in        = $_REQUEST['language'] ?? '';
    $language       = in_array($lang_in, array('en_US', 'ja_JP')) ? $lang_in : 'en_US';
    $admin          = !empty($_REQUEST['admin']) ? 1 : 0;
    $hidedona       = !empty($_REQUEST['hidedonations']) ? 1 : 0;
    $dashboard_esc  = h2d($_REQUEST['dashboard'] ?? '');
    $new_userid_esc = h2d($new_userid);
    $new_pw1        = $_REQUEST['new_pw1'] ?? '';
    $email          = trim($_REQUEST['email'] ?? '');
    $email_esc      = h2d($email);
    if ($new_pw1 !== '' && password_grade($new_pw1) < 1) {
      die(json_encode(array('success' => false, 'error' => _('Please choose a stronger password (at least "Fair").'))));
    }
    $send_link = !empty($_REQUEST['send_setup_link']);
    if ($send_link) {
      if ($email === '') die(json_encode(array('success' => false, 'error' => _('An email address is required to send a setup link.'))));
      require_once __DIR__.'/mailer.php';
    }
    if (($_REQUEST['userid'] ?? '') == 'new') {
      if (!$send_link && $new_pw1 === '') die(json_encode(array('success' => false, 'error' => _('You must enter a password for a new user.'))));
      $result = sqlquery_checked("SELECT UserName FROM user WHERE UserID='$new_userid_esc'");
      if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_object($result);
        die(json_encode(array('success' => false, 'error' => sprintf(
          _("UserID '%s' is already in use by %s. Please choose a different UserID."), $new_userid, $row->UserName))));
      }
      $pw_sql = $send_link ? "''" : "PASSWORD('".h2d($new_pw1)."')";
      sqlquery_checked("INSERT INTO user (UserID,UserName,Email,Password,Admin,Language,HideDonations,Dashboard) VALUES ".
                       "('$new_userid_esc','".h2d($username)."','$email_esc',$pw_sql,$admin,'".h2d($language)."',$hidedona,'$dashboard_esc')");
      if ($send_link) {
        $message = send_password_link($new_userid, $email, $language, 'setup')
          ? _('New user added; a setup link was emailed to them.')
          : _('New user added, but the setup email failed to send. Check the mail settings or set a password manually.');
      } else {
        $message = _('New user successfully added.');
      }
      die(json_encode(array('success' => true, 'userid' => $new_userid, 'username' => $username,
                            'sessionUpdated' => false, 'message' => $message)));
    } else {
      $old_userid     = $_REQUEST['old_userid'] ?? '';
      $old_userid_esc = h2d($old_userid);
      if ($new_userid !== $old_userid) {
        $result = sqlquery_checked("SELECT UserName FROM user WHERE UserID='$new_userid_esc'");
        if (mysqli_num_rows($result) > 0) {
          $row = mysqli_fetch_object($result);
          die(json_encode(array('success' => false, 'error' => sprintf(
            _("UserID '%s' is already in use by %s. Please choose a different UserID."), $new_userid, $row->UserName))));
        }
      }
      $sql = 'UPDATE user SET ';
      if ($new_userid !== $old_userid) $sql .= "UserID='$new_userid_esc',";
      $sql .= "UserName='".h2d($username)."',Email='$email_esc',";
      if (!$send_link && $new_pw1 !== '') $sql .= "Password=PASSWORD('".h2d($new_pw1)."'),";
      $sql .= "Admin=$admin,Language='".h2d($language)."',HideDonations=$hidedona,Dashboard='$dashboard_esc' WHERE UserID='$old_userid_esc'";
      sqlquery_checked($sql);
      if ($new_userid !== $old_userid) {
        sqlquery_checked("UPDATE loginlog SET UserID='$new_userid_esc' WHERE UserID='$old_userid_esc'");
      }
      $sessionUpdated = false;
      $newLang        = null;
      if ($old_userid == $_SESSION['userid']) {
        if ($_SESSION['lang'] !== $language) $newLang = $language;
        $_SESSION['userid']       = $new_userid;
        $_SESSION['username']     = $username;
        $_SESSION['admin']        = $admin;
        $_SESSION['lang']         = $language;
        $_SESSION['hasdashboard'] = (($_REQUEST['dashboard'] ?? '') !== '') ? 1 : 0;
        $sessionUpdated           = true;
      }
      $link_ok = $send_link ? send_password_link($new_userid, $email, $language, 'setup') : true;
      $resp = array('success' => true, 'userid' => $new_userid, 'username' => $username,
                    'sessionUpdated' => $sessionUpdated,
                    'message' => !$send_link ? _('User information successfully updated.')
                               : ($link_ok ? _('User updated; a setup link was emailed to them.')
                                           : _('User updated, but the setup email failed to send. Check the mail settings.')));
      if ($newLang !== null) $resp['newLang'] = $newLang;
      die(json_encode($resp));
    }

  case 'UserDelete':
    $old_userid = $_REQUEST['old_userid'] ?? '';
    if ($old_userid === '') die(json_encode(array('success' => false, 'error' => _('Invalid user ID'))));
    if ($old_userid == $_SESSION['userid']) {
      die(json_encode(array('success' => false, 'error' => 'You cannot delete your own account while logged in.'))); /* should never happen */
    }
    $old_userid_esc = h2d($old_userid);
    sqlquery_checked("DELETE FROM user WHERE UserID='$old_userid_esc'");
    if (mysqli_affected_rows($db) == 1) {
      die(json_encode(array('success' => true, 'userid' => $old_userid, 'message' => _('User successfully deleted.'))));
    }
    die(json_encode(array('success' => false, 'error' => _('User not found.'))));
  }
  die(json_encode(array('error' => 'Unknown AJAX request type — please tell the developer.')));
}

pageheader(_("Admin Settings"), 1);
?>
<style>
#status-msg {
  position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);
  padding: 10px 16px; background: #2e7d32; color: white; border-radius: 4px;
  box-shadow: 0 2px 8px rgba(0,0,0,.2); z-index: 10000; display: none;
}
.sub-sep { border: 0; border-top: 1px solid #ccc; margin: 1.2em 0 .7em; }
.showcols-group { border-top: 1px solid #ccc; margin-top: .6em; padding-top: .5em; }
.showcols-group:first-of-type { border-top: 0; margin-top: 0; padding-top: 0; }
.showcols-group h4 { margin: 0 0 .3em; font-size: 1em; }
.showcols-group .list-label { font-style: italic; margin: .4em 0 .2em; }
.showcols-group .cols { display: flex; flex-wrap: wrap; }
.showcols-group .cols label { flex: 0 0 auto; min-width: 12em; margin: 2px 0; font-weight: normal; }
.num-row input[type=number] { width: 4em; }
table.ov-tbl { width: auto; max-width: 100%; margin-top: .3em; }
table.ov-tbl td.center, table.ov-tbl th.center { text-align: center; }
table.ov-tbl td.ua { max-width: 30em; font-size: 85%; word-break: break-all; }
</style>
<h1 id="title"><?=_("Admin Settings")?></h1>

<!-- USER MANAGEMENT (with User Overview) -->

<form name="userform" id="userform" autocomplete="off">
  <fieldset><legend><?=_("User Management")?></legend>
  <p><?=_("Fill in the information to add a new user.  Or select an existing user to make changes or delete.".
  "NOTE: You cannot see the existing password, but you can enter a new one if the user forgot his/her password.")?></p>
  <select id="userid" name="userid" size="1">
    <option value="new"><?=_('New User...')?></option>
<?php
$result = sqlquery_checked("SELECT UserID,UserName FROM user ORDER BY UserName");
while ($row = mysqli_fetch_object($result))  echo "    <option value=\"".$row->UserID."\">".$row->UserName."</option>\n";
?>
  </select>
  <input type="hidden" id="old_userid" name="old_userid" value="">
  <label class="label-n-input"><?=_('Name')?>: <input type="text"
  id="username" name="username" style="width:10em" maxlength="30" autocomplete="off"></label>
  <label class="label-n-input"><?=_('UserID (to log in)')?>: <input type="text"
  id="new_userid" name="new_userid" style="width:5em" maxlength="16" autocomplete="off">
  <span class="comment"><?=_('(max. 16 English characters, no spaces or punctuation)')?></span></label>
  <label class="label-n-input"><?=_('Email')?>: <input type="email"
  id="email" name="email" style="width:14em" maxlength="70">
  <span class="comment"><?=_('(used for "forgot password" capability)')?></span></label>
  <label class="label-n-input"><?=_('Language for Interface')?>: <select id="language" name="language" size="1">
    <option value="en_US"<?php if($_SESSION['lang']=='en_US') echo ' selected'; ?>><?= _('English')?></option>
    <option value="ja_JP"<?php if($_SESSION['lang']=='ja_JP') echo ' selected'; ?>><?=_('Japanese')?></option>
  </select></label>
  <label class="label-n-input"><input type="checkbox" id="admin" name="admin"><?=_('Admin Privileges')?></label>
<?php if ($_SESSION['donations'] == 'yes') { ?>
  <label class="label-n-input"><input type="checkbox" id="hidedonations" name="hidedonations"
<?php if ($_SESSION['hidedonations_default'] == "yes") echo " checked"; ?>><?=_("Hide Donation Info")?></label>
<?php } //if donations is on ?>
  <label class="label-n-input"><input type="checkbox" id="send_setup_link" name="send_setup_link"><?=_('Email a setup link (instead of entering a password here; requires Email)')?></label>
  <div id="pwfields">
  <label class="label-n-input"><?=_('New Password')?>: <input type="password"
  id="new_pw1" name="new_pw1" style="width:10em" autocomplete="new-password">
  <span class="comment"><?=_('(leave blank if not changing password)')?></span></label>
  <label class="label-n-input"><?=_('New Password again')?>: <input type="password"
  id="new_pw2" name="new_pw2" style="width:10em" autocomplete="new-password"></label><br />
  <?php include('passwordentry.php'); ?>
  </div>
  <label class="label-n-input"><?=_('Dashboard Files')?>: <textarea id="dashboard" name="dashboard" style="height:2em;width:80%"></textarea></label>
  <div id="loginstats" class="comment"></div>
  <br /><button type="button" id="user_add_upd"><?=_('Add or Update')?></button>
  <button type="button" id="user_del" disabled><?=_('Delete')?></button>
  <hr class="sub-sep">
  <button type="button" id="userov_toggle"><?=_('Show user overview')?></button>
  <div id="userov" style="display:none"></div>
</fieldset></form>

<!-- DEFAULT COLUMNS -->

<form name="showcolsform" id="showcolsform">
  <fieldset><legend><?=_("Default Columns")?></legend>
  <p><?=_("Choose which columns each kind of list shows by default. (Users can still add or remove columns on the fly with each list's column selector.)")?></p>
<?php
foreach ($showcols_pages as $pg) {
  $lists = array_intersect_key($pg['lists'], array_flip($showcols_params));   // drops donation lists when off
  if (!$lists) continue;
  echo '  <div class="showcols-group"><h4>'.$pg['head']."</h4>\n";
  foreach ($lists as $param => $role) {
    if ($role !== '') echo '    <div class="list-label">'.$role."</div>\n";
    $current = ',' . ($_SESSION[$param] ?? '') . ',';
    echo "    <div class=\"cols\">\n";
    foreach ($defs[$param] as $token => $label) {
      $checked = (stripos($current, ','.$token.',') !== FALSE) ? ' checked' : '';
      echo '      <label><input type="checkbox" name="'.$param.'[]" value="'.$token.'"'.$checked.'>'.$label."</label>\n";
    }
    echo "    </div>\n";
  }
  echo "  </div>\n";
}
?>
  <div class="submits"><button type="button" id="showcols_save"><?=_("Save Changes")?></button></div>
</fieldset></form>

<!-- QUICK SEARCH FIELDS -->

<form name="qsform" id="qsform">
  <fieldset><legend><?=_("Quick Search Fields")?></legend>
  <p><?=_("Choose which fields the Quick Search box looks in. Searching fewer fields (especially Remarks) makes Quick Search faster.")?></p>
  <div id="qsfields">
<?php
$qs_enabled = array_map('trim', explode(',', $_SESSION['quicksearch_fields'] ?? ''));
foreach (quicksearch_field_defs() as $qs_key => $qs_def) {
  echo '    <label class="label-n-input"><input type="checkbox" name="qsfield" value="'.$qs_key.'"'.
    (in_array($qs_key, $qs_enabled, true) ? ' checked' : '').'>'.$qs_def[0]."</label>\n";
}
?>
  </div>
  <div class="submits"><button type="button" id="qs_save"><?=_("Save Changes")?></button></div>
</fieldset></form>

<!-- MISC. SETTINGS -->

<form name="miscform" id="miscform">
  <fieldset><legend><?=_("Miscellaneous Settings")?></legend>
  <div><label class="label-n-input"><?=_('Database title')?>: <input type="text" id="dbtitle" name="dbtitle"
    style="width:10em" maxlength="200" value="<?=htmlspecialchars($_SESSION['dbtitle'] ?? '', ENT_QUOTES)?>">
    <span class="comment"><?=_('(shown in browser tab titles)')?></span></label></div>
  <label class="label-n-input"><input type="checkbox" id="showid" name="showid"<?=(($_SESSION['showid'] ?? '')=='yes' ? ' checked' : '')?>><?=_('Show ID on person detail page')?></label>
<?php if ($donations) { ?>
  <label class="label-n-input"><input type="checkbox" id="hidedonations_default" name="hidedonations_default"<?=(($_SESSION['hidedonations_default'] ?? '')=='yes' ? ' checked' : '')?>><?=_('New users hide donation info by default')?></label>
  <label class="label-n-input"><?=_('Default pledge interval')?>:
    <select id="pledge_tpy" name="pledge_tpy" size="1">
      <?php   $tpy = (string)($_SESSION['pledge_tpy'] ?? '12'); ?>
      <option value="12"<?=($tpy=='12' ? ' selected' : '')?>><?=_('month')?></option>
      <option value="4"<?=($tpy=='4' ? ' selected' : '')?>><?=_('quarter')?></option>
      <option value="1"<?=($tpy=='1' ? ' selected' : '')?>><?=_('year')?></option>
      <option value="0"<?=($tpy=='0' ? ' selected' : '')?>><?=_('(one time)')?></option>
    </select></label>
<?php } ?>
<?php
$pp_max = '<input type="number" id="pphoto_maxwidth" name="pphoto_maxwidth" min="1" value="'.htmlspecialchars($_SESSION['pphoto_maxwidth'] ?? '', ENT_QUOTES).'">';
$pp_tgt = '<input type="number" id="pphoto_targetwidth" name="pphoto_targetwidth" min="1" value="'.htmlspecialchars($_SESSION['pphoto_targetwidth'] ?? '', ENT_QUOTES).'">';
$hp_max = '<input type="number" id="hphoto_maxwidth" name="hphoto_maxwidth" min="1" value="'.htmlspecialchars($_SESSION['hphoto_maxwidth'] ?? '', ENT_QUOTES).'">';
$hp_tgt = '<input type="number" id="hphoto_targetwidth" name="hphoto_targetwidth" min="1" value="'.htmlspecialchars($_SESSION['hphoto_targetwidth'] ?? '', ENT_QUOTES).'">';
?>
  <div class="label-n-input num-row"><?=sprintf(_('Person photos: If width is greater than %1$spx, resize to %2$spx'), $pp_max, $pp_tgt)?></div>
  <div class="label-n-input num-row"><?=sprintf(_('Household photos: If width is greater than %1$spx, resize to %2$spx'), $hp_max, $hp_tgt)?></div>
  <div class="submits"><button type="button" id="misc_save"><?=_("Save Changes")?></button></div>
</fieldset></form>

<?php load_scripts(array('jquery', 'tablesorter', 'functions')); ?>
<script type="text/javascript">
var NOSESSION_MSG    = '<?=_("Your login has timed out - please refresh the page.")?>';
var SHOW_USEROV      = '<?=_("Show user overview")?>';
var HIDE_USEROV      = '<?=_("Hide user overview")?>';

function stopRKey(evt) {
  var node = evt.target;
  if ((evt.keyCode == 13) && (node.type=="text") && node.name!="textinput1") {
    var form = node.form || $(node).closest('form')[0];
    if (form && ["miscform","showcolsform","qsform","userform"].includes(form.id)) return false;
  }
}
document.onkeypress = stopRKey;

// Lazily load a read-only panel on first open; slide-toggle thereafter.
function lazyPanel(btnSel, boxSel, action, showText, hideText, render) {
  $(btnSel).click(function() {
    var $btn = $(this), $box = $(boxSel);
    if ($box.is(':visible')) { $box.slideUp(); $btn.text(showText); return; }
    if ($box.data('loaded')) { $box.slideDown(); $btn.text(hideText); return; }
    $btn.prop('disabled', true);
    $.post('admin_settings.php', { ajax: action }, function(data) {
      $btn.prop('disabled', false);
      if (data.alert === 'NOSESSION') { alert(NOSESSION_MSG); return; }
      if (data.alert || data.error) { alert(data.alert || data.error); return; }
      render($box, data);
      $box.data('loaded', true).slideDown();
      $btn.text(hideText);
    }, 'json').fail(function() { $btn.prop('disabled', false); });
  });
}

$(document).ready(function(){

// misc Settings
  $('#misc_save').click(function() {
    var $btn = $(this).prop('disabled', true);
    $.post('admin_settings.php', $('#miscform').serialize() + '&ajax=SaveMisc', function(data) {
      $btn.prop('disabled', false);
      if (data.alert === 'NOSESSION') { alert(NOSESSION_MSG); return; }
      if (data.alert || data.error) { alert(data.alert || data.error); return; }
      showStatus(data.message);
    }, 'json').fail(function() { $btn.prop('disabled', false); });
  });

// Default Columns
  $('#showcols_save').click(function() {
    var $btn = $(this).prop('disabled', true);
    $.post('admin_settings.php', $('#showcolsform').serialize() + '&ajax=SaveShowcols', function(data) {
      $btn.prop('disabled', false);
      if (data.alert === 'NOSESSION') { alert(NOSESSION_MSG); return; }
      if (data.alert || data.error) { alert(data.alert || data.error); return; }
      showStatus(data.message);
    }, 'json').fail(function() { $btn.prop('disabled', false); });
  });

// Quick Search Fields
  $('#qs_save').click(function() {
    var fields = $('#qsfields input:checked').map(function() { return this.value; }).get();
    if (fields.length === 0) { alert('<?=_("Please choose at least one field for quick search.")?>'); return; }
    var $btn = $('#qs_save').prop('disabled', true);
    $.post('admin_settings.php', {
      ajax:   'QuicksearchFieldsSave',
      fields: fields.join(',')
    }, function(data) {
      $btn.prop('disabled', false);
      if (data.alert === 'NOSESSION') { alert(NOSESSION_MSG); return; }
      if (data.alert || data.error) { alert(data.alert || data.error); return; }
      showStatus(data.message);
    }, 'json').fail(function(jqxhr, textStatus, error) {
      $btn.prop('disabled', false);
    });
  });

// AJAX call for Users
  $("#userid").change(function(){
    $("#send_setup_link").prop("checked", false);
    $("#pwfields").show();
    if ($("#userid").val() == "new") {
      $("#username, #new_userid, #old_userid, #email, #new_pw1, #new_pw2, #dashboard").val("");
      $("#language").val("<?=$_SESSION['lang']?>");
      $("#admin").prop("checked", false);
      $("#hidedonations").prop("checked", <?=($_SESSION['hidedonations_default']=="yes" ? "true" : "false")?>);
      $("#user_del").prop('disabled', true);
      $("#loginstats").html("");
    } else {
      $('#username').addClass('is-loading');
      $.post('admin_settings.php', { ajax: 'UserGet', userid: $('#userid').val() }, function(data) {
        $('#username').removeClass('is-loading');
        if (data.alert === 'NOSESSION') { alert(NOSESSION_MSG); return; }
        if (data.error) { alert(data.error); return; }
        $("#admin,#hidedonations").prop("checked", false);
        $('#username').val(data.username);
        $('#new_userid').val(data.userid);
        $('#old_userid').val(data.userid);
        $('#email').val(data.email);
        $('#language').val(data.language);
        if (data.admin == 1) $('#admin').prop('checked', true);
        if (data.hidedonations == 1) $('#hidedonations').prop('checked', true);
        $('#dashboard').val(data.dashboard);
        $("#loginstats").html(data.loginstats);
        $('#user_del').data('name', data.username).data('userid', data.userid);
        $("#user_del").prop('disabled', data.userid == '<?=addslashes($_SESSION["userid"])?>');
      }, 'json');
    }
  });

  $("#send_setup_link").change(function(){
    if ($(this).is(":checked")) { $("#new_pw1, #new_pw2").val(""); $("#pwfields").hide(); }
    else { $("#pwfields").show(); }
  });

  $('#user_add_upd').click(function() {
    if (validate('user') === false) return;
    var $username = $('#username');
    $username.addClass('is-loading');
    $.post('admin_settings.php', {
      ajax:          'UserSave',
      userid:        $('#userid').val(),
      old_userid:    $('#old_userid').val(),
      new_userid:    $('#new_userid').val(),
      username:      $username.val(),
      email:         $('#email').val(),
      language:      $('#language').val(),
      admin:         $('#admin').is(':checked') ? 1 : 0,
      hidedonations: $('#hidedonations').length ? ($('#hidedonations').is(':checked') ? 1 : 0) : 0,
      dashboard:     $('#dashboard').val(),
      new_pw1:       $('#new_pw1').val(),
      new_pw2:       $('#new_pw2').val(),
      send_setup_link: $('#send_setup_link').is(':checked') ? 1 : 0
    }, function(data) {
      $username.removeClass('is-loading');
      if (data.alert === 'NOSESSION') { alert(NOSESSION_MSG); return; }
      if (data.alert || data.error) { alert(data.alert || data.error); return; }
      var sentOldUserid = $('#old_userid').val();
      selectUpsertOption($('#userid'), data.userid, data.username);
      if (sentOldUserid && sentOldUserid !== data.userid) {
        $('#userid').find('option[value="' + sentOldUserid + '"]').remove();
      }
      if (data.sessionUpdated) { window.location.reload(); return; }
      resetEntityForm($('#userid'));
      $('#new_pw1, #new_pw2').val('');
      // A newly added/renamed user invalidates any already-loaded overview.
      $('#userov').data('loaded', false);
      showStatus(data.message);
    }, 'json').fail(function(jqxhr, textStatus, error) {
      $username.removeClass('is-loading');
    });
  });

  $('#user_del').click(function() {
    var name = $(this).data('name'), uid = $(this).data('userid');
    if (!confirm('<?=_('Are you sure you want to delete user "%1$s" (UserID: %2$s)?')?>'.replace('%1$s', name).replace('%2$s', uid))) return;
    var $username = $('#username');
    $username.addClass('is-loading');
    $.post('admin_settings.php', {
      ajax:       'UserDelete',
      old_userid: uid
    }, function(data) {
      $username.removeClass('is-loading');
      if (data.alert === 'NOSESSION') { alert(NOSESSION_MSG); return; }
      if (data.alert || data.error) { alert(data.alert || data.error); return; }
      selectRemoveOption($('#userid'), uid);
      resetEntityForm($('#userid'));
      $('#userov').data('loaded', false);
      showStatus(data.message);
    }, 'json').fail(function(jqxhr, textStatus, error) {
      $username.removeClass('is-loading');
    });
  });

// User Overview (inside User Management), loaded on demand
  lazyPanel('#userov_toggle', '#userov', 'UserOverview', SHOW_USEROV, HIDE_USEROV, function($box, data) {
    $box.html(data.html).find('table').tablesorter({ sortList: [[0, 0]] });
  });
});

function validate(form) {
  switch(form) {
  case "user":
    if (document.userform.username.value == "") {
      alert("<?=_("User Name cannot be blank.")?>");
      return false;
    }
    if (document.userform.new_userid.value == "") {
      alert("<?=_("UserID cannot be blank.")?>");
      return false;
    }
    if (document.getElementById('send_setup_link').checked) {
      if (document.userform.email.value.trim() == "") {
        alert("<?=_('An email address is required to send a setup link.')?>");
        return false;
      }
      break;
    }
    if (document.userform.userid.selectedIndex == 0 && document.userform.new_pw1.value == "") {
      alert("<?=_("You must enter a password for a new user.")?>");
      return false;
    }
    if (!pwEntryOK()) return false;
    break;
  }
}
</script>
<?php footer(); ?>
