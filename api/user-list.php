<?php
require_once dirname(__DIR__).'/fungsi.php';
@require_once dirname(__DIR__).'/plugin/epi-role-manager/index.php';
header('Content-Type: application/json');
epi_require_admin();
$role = isset($_GET['role']) ? (int)$_GET['role'] : 0;
$page = isset($_GET['page']) ? max(1,(int)$_GET['page']) : 1;
$perPage = 20; $off = ($page-1)*$perPage;
$where = " WHERE `mem_role` <> 1";
if ($role>0){ $where .= " AND `mem_role`=".$role; }
$total = (int)(db_var("SELECT COUNT(*) FROM `sa_member`".$where) ?: 0);
$rows = db_select("SELECT `mem_id`,`mem_nama`,`mem_email`,`mem_role` FROM `sa_member`".$where." ORDER BY `mem_id` DESC LIMIT ".$perPage." OFFSET ".$off) ?: array();
// Fetch last updated from audit log
$ids = array_map(function($r){ return (int)$r['mem_id']; }, $rows);
$updatedMap = array();
if (!empty($ids)){
  $in = implode(',', array_map('intval',$ids));
  $log = db_select("SELECT `target`,`created_at` FROM `epi_audit_log` WHERE `action`='role_assign' AND `target` IN (".$in.") ORDER BY `id` DESC") ?: array();
  foreach ($log as $l){ $updatedMap[$l['target']] = $l['created_at']; }
}
$data = array();
foreach ($rows as $r){ $data[] = array('id'=>(int)$r['mem_id'],'nama'=>$r['mem_nama'],'email'=>$r['mem_email'],'role'=>(int)$r['mem_role'],'updated_at'=>($updatedMap[$r['mem_id']] ?? '')); }
echo json_encode(array('status'=>true,'data'=>$data,'page'=>$page,'total_pages'=>($total>0?ceil($total/$perPage):1)));
?>
