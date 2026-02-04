<?php
require_once dirname(__DIR__).'/fungsi.php';
require_once dirname(__DIR__).'/plugin/epi-role-manager/index.php';

function assertTrue($cond,$msg){ if(!$cond){ echo "[FAIL] $msg\n"; } else { echo "[OK] $msg\n"; } }

// Prepare fake member context
$GLOBALS['datamember'] = array('mem_id'=>999,'mem_role'=>6);

// Seed permissions
db_query("DELETE FROM `epi_role_permissions` WHERE `role_code`='6'");
db_query("INSERT INTO `epi_role_permissions` (`role_code`,`menu_key`,`allowed`,`version`) VALUES ('6','lapkeuangan',1,1),('6','bayar',1,1),('6','orderlist',0,1)");

// Build menu
require_once dirname(__DIR__).'/menudata.php';
$filtered = apply_filter('menu',$menu);

// Expected: 'orderlist' removed for role 6, while 'lapkeuangan' present
assertTrue(isset($filtered['manage']['submenu']['lapkeuangan']) || isset($filtered['settings']['submenu']['lapkeuangan']), 'lapkeuangan visible');
assertTrue(!isset($filtered['manage']['submenu']['orderlist']), 'orderlist hidden');

// Test API payload validation
$_SERVER['REQUEST_METHOD']='GET'; $_GET['role']='6';
include dirname(__DIR__).'/api/role-permissions.php';
?>

