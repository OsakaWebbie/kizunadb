<?php
// Logged-out password recovery. Two views in one page:
//   no token  -> "forgot": ask for a UserID and email a reset link
//   ?token=.. -> "reset":  validate the token and set a new password
include("functions.php");
include("mailer.php");
session_start();

$token = $_REQUEST['token'] ?? '';
$mode  = ($token !== '') ? 'reset' : 'forgot';

$user  = null;   // reset: the token's user, if the token is valid
$done  = false;  // reset: password was set
$sent  = false;  // forgot: request processed
$error = '';

if ($mode === 'reset' && ctype_xdigit($token)) {
  $hash_esc = mysqli_real_escape_string($db, hash('sha256', $token));
  $result = sqlquery_checked("SELECT UserID, UserName, Language FROM user ".
      "WHERE ResetTokenHash='$hash_esc' AND ResetExpires > NOW()");
  $user = mysqli_fetch_object($result);
}

// UI language: the recipient's own language on a valid reset token, else English (like the login page).
$lang = ($mode === 'reset' && $user) ? $user->Language : 'en_US';
setlocale(LC_ALL, $lang.".utf8");
textdomain("default");
bindtextdomain("default", "locale");
bind_textdomain_codeset("default", "utf8");

if ($mode === 'reset' && $user && isset($_POST['reset_submit'])) {
  $pw1 = $_POST['new_pw1'] ?? '';
  $pw2 = $_POST['new_pw2'] ?? '';
  if ($pw1 !== $pw2) {
    $error = _('The two password entries do not match.');
  } elseif (password_grade($pw1) < 1) {
    $error = _('Please choose a stronger password (at least "Fair").');
  } else {
    $pw1_esc = mysqli_real_escape_string($db, $pw1);
    $uid_esc = mysqli_real_escape_string($db, $user->UserID);
    sqlquery_checked("UPDATE user SET Password=PASSWORD('$pw1_esc'), ResetTokenHash=NULL, ResetExpires=NULL WHERE UserID='$uid_esc'");
    $done = true;
  }
} elseif ($mode === 'forgot' && isset($_POST['forgot_submit'])) {
  $userid = trim($_POST['usr'] ?? '');
  if ($userid !== '') {
    $uid_esc = mysqli_real_escape_string($db, $userid);
    $result = sqlquery_checked("SELECT UserID, Email, Language FROM user WHERE UserID='$uid_esc'");
    if ($row = mysqli_fetch_object($result)) {
      if (trim($row->Email) !== '') {
        send_password_link($row->UserID, $row->Email, $row->Language, 'reset');
      }
    }
  }
  $sent = true;   // always show the same result, whether or not the account/email existed
}

$pagetitle = ($mode === 'reset') ? _('Set Password') : _('Forgot Password');
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?=$pagetitle?> - KizunaDB</title>
<?php css_bundle(); ?>
  <style>
    #nav-main ul { padding-top:6px; }
    #content { padding:25px 15px; }
    #content p { max-width:34em; margin:0 auto 18px; text-align:left; }
    .pw-set { text-align:left; }
    #content .pw-set p { margin:0 0 18px; }
    form label { display:block; font-size:110%; line-height:1.5em; font-weight:bold; margin-bottom:18px; }
    form input { font-weight:bold; }
    #submit { padding:5px 20px; margin-bottom:10px; font-size:18px; }
    #nav-trigger span::after { display:none; }
  </style>
</head>
<body class="passwordreset full">
  <div id="main-container">
    <nav id="nav-main">
      <ul class="nav"><li><a href="#" style="font-size:24px; font-weight:bold; text-decoration:none; cursor:default"><?=$pagetitle?></a></li></ul>
    </nav>
    <div id="nav-trigger"><img src="graphics/kizunadb-logo.png" alt="Logo"><span style="cursor:default; font-size:24px"><?=$pagetitle?></span></div>
    <div id="content" style="text-align:center;">
<?php
if ($mode === 'reset') {
  if (!$user) { ?>
      <p><?=_('This password link is invalid or has expired.')?></p>
      <p style="text-align:center"><a href="passwordreset.php"><?=_('Request a new link')?></a></p>
<?php } elseif ($done) { ?>
      <p><?=_('Your password has been set. You can now log in.')?></p>
      <p style="text-align:center"><a href="index.php"><?=_('Go to login')?></a></p>
<?php } else { ?>
      <div class="pw-set">
<?php   if ($error) echo '      <p style="color:red">'.htmlspecialchars($error)."</p>\n"; ?>
      <p><?=sprintf(_('Hello %s, please choose a new password.'), htmlspecialchars($user->UserName, ENT_QUOTES))?></p>
      <form name="rform" method="post" action="passwordreset.php" autocomplete="off" onsubmit="return pwEntryOK();">
        <input type="hidden" name="token" value="<?=htmlspecialchars($token, ENT_QUOTES)?>">
        <label><?=_('New Password')?>: <input type="password" id="new_pw1" name="new_pw1" autocomplete="new-password" required></label>
        <label><?=_('New Password again')?>: <input type="password" id="new_pw2" name="new_pw2" autocomplete="new-password" required></label>
        <?php include("passwordentry.php"); ?>
        <input id="submit" type="submit" name="reset_submit" value="<?=_('Set Password')?>">
      </form>
      </div>
<?php }
} else {   // forgot mode
  if ($sent) { ?>
      <p><?=_('If an account with that UserID has an email address on file, a message with a link to reset your password has been sent to that address. Please check your email.')?></p>
      <p style="text-align:center"><a href="index.php"><?=_('Return to login')?></a></p>
<?php } else { ?>
      <p><?=_('Enter your UserID. If your account has an email address on file, we will send a link to reset your password.')?></p>
      <form name="fform" method="post" action="passwordreset.php">
        <label><?=_('UserID')?>: <input type="text" name="usr" autofocus></label>
        <input id="submit" type="submit" name="forgot_submit" value="<?=_('Send reset link')?>">
      </form>
      <p style="text-align:center"><a href="index.php"><?=_('Return to login')?></a></p>
<?php }
}
?>
<?php footer(); ?>