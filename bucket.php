<?php
/*****
Handler for AJAX calls to retrieve or modify contents of the user's "bucket", a collection
of PersonID values for use in limited searches and Multi-Select.
*****/

include('functions.php');
session_start();
if (!isset($_SESSION['userid'])) {      // NOT YET LOGGED IN
  die(_('Your login has timed out - please refresh the page.'));
}

// retrieval for use in Multi-Select or List
if (!empty($_GET['get'])) {
  die(strval(implode(',',$_SESSION['bucket'])));
}

// bucket modifiers
if (!empty($_POST['set'])) {
  $_SESSION['bucket'] = explode(',',$_POST['set']);
} elseif (!empty($_POST['add'])) {
  $_SESSION['bucket'] = array_unique(array_merge($_SESSION['bucket'],explode(',',$_POST['add'])));
} elseif (!empty($_POST['rem'])) {
  $_SESSION['bucket'] = array_diff($_SESSION['bucket'],explode(',',$_POST['rem']));
} elseif (!empty($_POST['empty'])) {
  $_SESSION['bucket'] = array();
}
die(strval(count($_SESSION['bucket'])));