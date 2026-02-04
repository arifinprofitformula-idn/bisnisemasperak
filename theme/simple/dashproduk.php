<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
if ($datamember['mem_role'] < 5) { die(); exit(); }
if (isset($_GET['edit'])) {
	include('dashprodukadd.php');
} else {
	include('dashproduklist.php');
}
?>