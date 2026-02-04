<?php
require_once dirname(__DIR__).'/fungsi.php';
// Ensure RBAC utilities are available
@require_once dirname(__DIR__).'/plugin/epi-role-manager/index.php';
@ini_set('display_errors','0');
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $role = isset($_GET['role']) ? cek($_GET['role']) : '6';
  $rows = db_select("SELECT `menu_key`,`allowed`,`version` FROM `epi_role_permissions` WHERE `role_code`='".$role."'") ?: array();
  $perms = array(); $ver = array();
  foreach ($rows as $r){ $perms[$r['menu_key']] = (int)$r['allowed'] === 1; $ver[$r['menu_key']] = (int)$r['version']; }
  echo json_encode(array('status'=>true,'perms'=>$perms,'version'=>$ver));
  exit;
}

// POST: update permissions with optimistic locking
$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) { echo json_encode(array('status'=>false,'message'=>'Invalid payload')); exit; }
epi_require_admin();
$role = cek($payload['role'] ?? ''); $items = $payload['items'] ?? array(); $version = $payload['version'] ?? array();
if ($role===''){ echo json_encode(array('status'=>false,'message'=>'Missing role')); exit; }
$errors = array();
$changed = array();
foreach ($items as $it){
  $key = cek($it['key'] ?? ''); $allowed = (int)($it['allowed'] ?? 0);
  if ($key==='') { continue; }
  $cur = db_row("SELECT `allowed`,`version` FROM `epi_role_permissions` WHERE `role_code`='".$role."' AND `menu_key`='".$key."'" );
  $verCur = isset($cur['version'])?(int)$cur['version']:0; $verClient = isset($version[$key])?(int)$version[$key]:0;
  if ($verCur !== 0 && $verClient !== 0 && $verClient !== $verCur) {
    echo json_encode(array('status'=>false,'message'=>'Concurrent update detected on '.$key)); exit;
  }
  $prevAllowed = isset($cur['allowed']) ? (int)$cur['allowed'] : -1;
  if ($cur && $prevAllowed === $allowed) { continue; }
  if ($cur) {
    $ok = db_query("UPDATE `epi_role_permissions` SET `allowed`=".$allowed.", `version`=`version`+1, `updated_by`=".(int)(is_login()?:0).", `updated_at`='".date('Y-m-d H:i:s')."' WHERE `role_code`='".$role."' AND `menu_key`='".$key."' ");
  } else {
    $ok = db_query("INSERT INTO `epi_role_permissions` (`role_code`,`menu_key`,`allowed`,`version`,`updated_by`,`updated_at`) VALUES ('".$role."','".$key."',".$allowed.",1,".(int)(is_login()?:0).",'".date('Y-m-d H:i:s')."')");
  }
  if ($ok === false){ $errors[] = $key; }
  db_query("INSERT INTO `epi_audit_log` (`actor_id`,`action`,`target`,`detail`,`created_at`) VALUES (".(int)(is_login()?:0).", 'permission_update', '".$role."', '".$key."=>".$allowed."', '".date('Y-m-d H:i:s')."')");
  $changed[] = array('key'=>$key,'prev'=>$prevAllowed,'new'=>$allowed,'changed_at'=>date('Y-m-d H:i:s'));
}
if (!empty($errors)){
  echo json_encode(array('status'=>false,'message'=>'Failed to update: '.implode(',', $errors),'changed'=>$changed));
} else {
  echo json_encode(array('status'=>true,'message'=>'Permissions updated','changed'=>$changed));
}
?>
