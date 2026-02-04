<?php
###########################################
##                                       ##
##       Bismillahirrahmanirrahiim       ##
##      SimpleAff Plus versi v1.3.2      ##
##         by: Cafebisnis Online         ##
##                                       ##
###########################################

define('VERSI','1.3.2');
if (!file_exists(__DIR__ . '/config.php')) {
  include(__DIR__ . '/tutorialinstall.php');
  die();
  exit();
} else {
	include('fungsi.php');
	if (!db_var("show tables like 'sa_member'")) {
		include(__DIR__ . '/install.php');
	} else {
		openpage();
	}
}
mysqli_close($con);