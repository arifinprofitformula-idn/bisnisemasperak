<?php
/*
Name: EPI Role Manager
Description: Role & permission management for EPIC Hub
*/
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
// Prevent double-loading to avoid function redeclaration and duplicate hooks
if (defined('EPI_ROLE_MANAGER_LOADED')) { return; }
define('EPI_ROLE_MANAGER_LOADED', true);

add_filter('menu', function($menu){
  global $datamember, $settings;
  // Ensure role catalog includes Admin Staff (code '5')
  @db_query("CREATE TABLE IF NOT EXISTS `epi_roles` (`role_code` VARCHAR(1) NOT NULL, `name` VARCHAR(64) NOT NULL, `level` TINYINT NOT NULL DEFAULT 1, PRIMARY KEY (`role_code`)) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4");
  @db_query("INSERT INTO `epi_roles` (`role_code`,`name`,`level`) VALUES ('5','Admin Staff',5) ON DUPLICATE KEY UPDATE `name`=VALUES(`name`),`level`=VALUES(`level`)");
  // Inject Setting Role entry regardless of login; visibility handled by permission (9)
  if (isset($menu['settings']) && isset($menu['settings']['submenu']) && is_array($menu['settings']['submenu'])) {
    $menu['settings']['submenu']['settingrole'] = array('Setting Role','plugin/epi-role-manager/dashrole.php',9);
  } else {
    // Fallback: add top-level when settings group unavailable
    $menu['settingrole'] = array('label' => 'Setting Role', 'slug' => 'settingrole', 'file' => 'plugin/epi-role-manager/dashrole.php');
  }

  $role = isset($datamember['mem_role']) ? (int)$datamember['mem_role'] : 1;
  // Permission-based menu filtering (non-admin)
  if ($role < 9) {
    $perm = epi_role_permissions_for_member($datamember);
    // Walk through menu and remove disallowed submenu items except mandatory
    $mandatory = epi_mandatory_menu_keys();
    foreach ($menu as $key => $group) {
      if (isset($group['submenu']) && is_array($group['submenu'])) {
        foreach ($group['submenu'] as $slug => $conf) {
          $label = isset($conf[0]) ? $conf[0] : '';
          // permission key uses slug
          if (!in_array($slug, $mandatory, true)) {
            if (!epi_is_allowed($perm, $slug)) {
              unset($menu[$key]['submenu'][$slug]);
            }
          }
        }
      }
    }
  }
  return $menu;
});

if (!function_exists('epi_mandatory_menu_keys')) {
function epi_mandatory_menu_keys(){
  return array('home','profil','logout','orderanda','product');
}
}

if (!function_exists('epi_role_permissions_for_member')) {
function epi_role_permissions_for_member($member){
  $roleCode = (string)($member['mem_role'] ?? '1');
  $exists = db_var("SHOW TABLES LIKE 'epi_role_permissions'");
  if (!$exists) { return array(); }
  $rows = db_select("SELECT `menu_key`,`allowed` FROM `epi_role_permissions` WHERE `role_code`='".cek($roleCode)."'") ?: array();
  $out = array();
  foreach ($rows as $r) { $out[$r['menu_key']] = (int)$r['allowed'] === 1; }
  return $out;
}
}

if (!function_exists('epi_is_allowed')) {
function epi_is_allowed($permMap, $menuKey){
  if (!is_array($permMap)) { return true; }
  if (array_key_exists($menuKey, $permMap)) { return (bool)$permMap[$menuKey]; }
  return true;
}
}

// Utility: check admin
if (!function_exists('epi_require_admin')) {
function epi_require_admin(){
  global $datamember; 
  if (!isset($datamember['mem_role'])){
    try {
      if (function_exists('is_login')){
        $uid = is_login();
        if ($uid && function_exists('getdatamember')){ $row = getdatamember((int)$uid); if (is_array($row) && isset($row['mem_role'])) { $datamember = $row; } }
      }
    } catch (Throwable $e) { /* ignore */ }
    if ((!isset($datamember['mem_role']) || !(int)$datamember['mem_role'])){
      if (isset($_SESSION['member']) && is_array($_SESSION['member'])) { $datamember = $_SESSION['member']; }
    }
  }
  if (!isset($datamember['mem_role']) || (int)$datamember['mem_role'] < 9) { http_response_code(403); header('Content-Type: application/json'); echo json_encode(array('status'=>false,'message'=>'Forbidden')); die(); }
}
}

// Utility: JSON response
if (!function_exists('epi_json')) {
function epi_json($data){ header('Content-Type: application/json'); echo json_encode($data); die(); }
}
