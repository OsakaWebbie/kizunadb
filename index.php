<?php
include("functions.php");
include("accesscontrol.php");
header("Location: //".$_SERVER['HTTP_HOST'].($_SESSION['hasdashboard'] ? "/dashboard.php" : "/search.php"));

