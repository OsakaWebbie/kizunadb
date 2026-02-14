<?php
/*****
Handler for AJAX calls to retrieve or modify contents of the user's "basket", a collection
of PersonID values for use in limited searches and Multi-Select.
*****/

include('functions.php');
session_start();
if (!isset($_SESSION['userid'])) {      // NOT YET LOGGED IN
  die(_('Your login has timed out - please refresh the page.'));
}

// retrieval for use in Multi-Select or List
if (!empty($_GET['get'])) {
  die(strval(implode(',',$_SESSION['basket'])));
}

// basket modifiers
if (!empty($_POST['set'])) {
  $_SESSION['basket'] = explode(',',$_POST['set']);
} elseif (!empty($_POST['add'])) {
  $_SESSION['basket'] = array_unique(array_merge($_SESSION['basket'],explode(',',$_POST['add'])));
} elseif (!empty($_POST['rem'])) {
  $_SESSION['basket'] = array_diff($_SESSION['basket'],explode(',',$_POST['rem']));
} elseif (!empty($_POST['empty'])) {
  $_SESSION['basket'] = array();
}
die(strval(count($_SESSION['basket'])));