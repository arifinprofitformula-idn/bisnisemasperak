<?php
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
if ($iduser = is_login()) {
	#$datamember = db_row("SELECT * FROM `sa_member` LEFT JOIN `sa_sponsor` ON `sa_sponsor`.`sp_mem_id`=`sa_member`.`mem_id` WHERE `mem_id`=".$iduser);
	if (isset($slug[2]) && !empty($slug[2])) {	
		openpage($slug[2]);
	} else {		
		include('theme/'.$settings['theme'].'/dashhome.php');
	}
} else {
	$redirect = '';
	if (isset($slug[2]) && $slug[2] != '') {
		$redirect = "?redirect=".$rekues['path'];
	} 

	header("Location:".$weburl."login".$redirect);
}