<?php
// Sends the password-reset and account-setup emails (SMTP settings in mail.ini).

require_once __DIR__.'/lib/PHPMailer/Exception.php';
require_once __DIR__.'/lib/PHPMailer/PHPMailer.php';
require_once __DIR__.'/lib/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// The mail.ini settings as an assoc array (null if the file is missing).
function mail_config() {
  $file = dirname(__DIR__).'/mail.ini';
  if (!is_readable($file)) return null;
  return parse_ini_file($file);
}

// Generate a one-time token, store its sha256 hash + expiry on the user, return the raw token.
// 'setup' tokens last 7 days (admin may onboard ahead of time); reset tokens last 2 hours.
function pw_store_token($userid, $purpose) {
  global $db;
  $token = bin2hex(random_bytes(32));
  $hash  = hash('sha256', $token);
  $uid   = mysqli_real_escape_string($db, $userid);
  $interval = ($purpose === 'setup') ? 'INTERVAL 7 DAY' : 'INTERVAL 2 HOUR';
  sqlquery_checked("UPDATE user SET ResetTokenHash='$hash', ResetExpires=DATE_ADD(NOW(), $interval) WHERE UserID='$uid'");
  return $token;
}

// Base URL (scheme + host) for the current client, e.g. https://jema.kizunadb.com
function base_url() {
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  return $scheme.'://'.$_SERVER['HTTP_HOST'];
}

// Absolute link back to the password-reset page on the current client host.
function reset_link($token) {
  return base_url().'/passwordreset.php?token='.$token;
}

// Send the reset/setup email in the recipient's language. Returns true on success.
function send_account_email($userid, $email, $lang, $token, $purpose) {
  global $db;
  $cfg = mail_config();
  if (!$cfg) { error_log('KizunaDB: mail.ini missing/unreadable; cannot send account email'); return false; }

  $uid = mysqli_real_escape_string($db, $userid);
  $result = sqlquery_checked("SELECT UserName FROM user WHERE UserID='$uid'");
  $row = mysqli_fetch_object($result);
  $username = $row ? $row->UserName : $userid;
  $link = reset_link($token);

  // Compose in the recipient's language.
  $prev = setlocale(LC_ALL, 0);
  setlocale(LC_ALL, $lang.'.utf8');
  if ($purpose === 'setup') {
    $subject = _('KizunaDB: set up your account');
    // %s order: name, site URL, UserID, setup link.
    $body = sprintf(_("Hello %s,\n\nAn account has been created for you on KizunaDB:\n%s\n\nYour UserID is: %s\n\n".
        "Click the following link to choose your password and start using it. This link is valid for 7 days.\n\n%s\n"),
        $username, base_url(), $userid, $link);
  } else {
    $subject = _('KizunaDB: password reset');
    // %s order: name, UserID, reset link.
    $body = sprintf(_("Hello %s,\n\nWe received a request to reset the password for your account (UserID: %s).\n\n".
        "Click the following link to choose a new password. This link is valid for 2 hours.\n\n%s\n\n".
        "If you did not request this, you can safely ignore this email; your password will not change.\n"),
        $username, $userid, $link);
  }
  setlocale(LC_ALL, $prev);

  $mail = new PHPMailer(true);
  try {
    $mail->isSMTP();
    $mail->Host = $cfg['host'];
    $mail->Port = isset($cfg['port']) ? (int)$cfg['port'] : 587;
    if (!empty($cfg['username'])) {
      $mail->SMTPAuth = true;
      $mail->Username = $cfg['username'];
      $mail->Password = $cfg['password'] ?? '';
    }
    $secure = $cfg['secure'] ?? 'tls';
    if ($secure === 'ssl')      $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    elseif ($secure === 'tls')  $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->CharSet = 'UTF-8';
    $mail->isHTML(false);
    $mail->setFrom($cfg['from_address'] ?? $cfg['username'], $cfg['from_name'] ?? 'KizunaDB');
    $mail->addAddress($email);
    $mail->Subject = $subject;
    $mail->Body = $body;
    $mail->send();
    return true;
  } catch (Exception $e) {
    error_log('KizunaDB: account email to '.$email.' failed: '.$mail->ErrorInfo);
    return false;
  }
}

// Convenience: create+store a token and email it. Returns true on success.
function send_password_link($userid, $email, $lang, $purpose) {
  $token = pw_store_token($userid, $purpose);
  return send_account_email($userid, $email, $lang, $token, $purpose);
}
