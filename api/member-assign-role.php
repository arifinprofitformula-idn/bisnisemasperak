<?php
require_once dirname(__DIR__).'/fungsi.php';
@require_once dirname(__DIR__).'/plugin/epi-role-manager/index.php';
header('Content-Type: application/json');
epi_require_admin();
$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) { echo json_encode(array('status'=>false,'message'=>'Invalid payload')); exit; }
$ids = array_map('intval', (array)($payload['ids'] ?? array()));
$role = (int)($payload['role'] ?? 1);
if (empty($ids)) { echo json_encode(array('status'=>false,'message'=>'No members selected')); exit; }
$ok = true; foreach ($ids as $id){ if ($id>0){ $ok = $ok && db_query("UPDATE `sa_member` SET `mem_role`=".$role." WHERE `mem_id`=".$id); epi_log_action('role_assign',$id,'set role '.$role); } }
echo json_encode(array('status'=>$ok,'message'=>$ok?'Saved':'Partial failure'));

function epi_log_action($action,$target,$detail){
  $actor = is_login(); $action = cek($action); $target = (string)$target; $detail = cek($detail);
  db_query("INSERT INTO `epi_audit_log` (`actor_id`,`action`,`target`,`detail`,`created_at`) VALUES (".(int)$actor.", '".$action."', '".$target."', '".$detail."', '".date('Y-m-d H:i:s')."')");
}
?>
