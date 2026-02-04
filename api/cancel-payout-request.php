<?php
@include_once __DIR__ . '/../config.php';
@include_once __DIR__ . '/../fungsi.php';
if (session_status() === PHP_SESSION_NONE) { @session_start(); }
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['ok'=>false,'message'=>'Invalid method']); exit; }

$adminId = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : 0;
$role = isset($_SESSION['admin_role']) ? (int)$_SESSION['admin_role'] : 0;
// Allow access for any user who can reach this page; do not block by role

$receiverId = isset($_POST['receiver_id']) && is_numeric($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
$type = isset($_POST['type']) ? strtolower(trim($_POST['type'])) : '';
$reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
$tsClient = isset($_POST['ts']) ? trim($_POST['ts']) : '';
if ($receiverId <= 0) { echo json_encode(['ok'=>false,'message'=>'Receiver invalid']); exit; }
if (!in_array($type, ['sponsor','contrib'])) { echo json_encode(['ok'=>false,'message'=>'Type invalid']); exit; }
if ($reason === '') { echo json_encode(['ok'=>false,'message'=>'Alasan wajib diisi']); exit; }

$now = date('Y-m-d H:i:s');
// Ensure schema supports rejected status and reason
$colStat = db_row("SHOW COLUMNS FROM `epi_commission_payout` LIKE 'status'");
if (is_array($colStat) && isset($colStat['Type'])) {
  $t = strtolower($colStat['Type']);
  if (strpos($t, "enum(") !== false && strpos($t, "'rejected'") === false) {
    db_query("ALTER TABLE `epi_commission_payout` MODIFY `status` ENUM('requested','pending','processed','paid','rejected') NOT NULL DEFAULT 'pending'");
  } elseif (preg_match('/^varchar\((\d+)\)/', $t, $m)) {
    if ((int)$m[1] < 16) { db_query("ALTER TABLE `epi_commission_payout` MODIFY `status` VARCHAR(32) NOT NULL DEFAULT 'pending'"); }
  }
}
$colReason = db_row("SHOW COLUMNS FROM `epi_commission_payout` LIKE 'reject_reason'");
if (!is_array($colReason) || !isset($colReason['Field'])) { db_query("ALTER TABLE `epi_commission_payout` ADD `reject_reason` VARCHAR(255) NULL"); }
$colRejectedAt = db_row("SHOW COLUMNS FROM `epi_commission_payout` LIKE 'rejected_at'");
if (!is_array($colRejectedAt) || !isset($colRejectedAt['Field'])) { db_query("ALTER TABLE `epi_commission_payout` ADD `rejected_at` DATETIME NULL"); }
// Ensure log accepts 'rejected'
$colOld = db_row("SHOW COLUMNS FROM `epi_commission_payout_log` LIKE 'old_status'");
if (is_array($colOld) && isset($colOld['Type'])) { $tt=strtolower($colOld['Type']); if (strpos($tt, "enum(")!==false && strpos($tt, "'rejected'")===false) { db_query("ALTER TABLE `epi_commission_payout_log` MODIFY `old_status` ENUM('requested','pending','processed','paid','rejected') NOT NULL"); } }
$colNew = db_row("SHOW COLUMNS FROM `epi_commission_payout_log` LIKE 'new_status'");
if (is_array($colNew) && isset($colNew['Type'])) { $tn=strtolower($colNew['Type']); if (strpos($tn, "enum(")!==false && strpos($tn, "'rejected'")===false) { db_query("ALTER TABLE `epi_commission_payout_log` MODIFY `new_status` ENUM('requested','pending','processed','paid','rejected') NOT NULL"); } }

$rows = db_select("SELECT `id`,`status` FROM `epi_commission_payout` WHERE `receiver_id`=".$receiverId." AND `type`='".cek($type)."' AND `status` IN ('requested','pending','processed')");
if (!is_array($rows)) { echo json_encode(['ok'=>false,'message'=>'Tidak ada pengajuan aktif']); exit; }

$count = 0;
foreach ($rows as $r) {
  $oldSt = strtolower($r['status']);
  db_query("INSERT INTO `epi_commission_payout_log` (`payout_id`,`admin_id`,`old_status`,`new_status`,`note`) VALUES (".(int)$r['id'].",".(int)$adminId.",'".cek($oldSt)."','rejected','reject ts=".cek($now)." cts=".cek($tsClient)." reason=".cek($reason)."')");
  $ok = db_query("UPDATE `epi_commission_payout` SET `status`='rejected',`reject_reason`='".cek($reason)."',`rejected_at`='".cek($now)."' WHERE `id`=".(int)$r['id']);
  if ($ok !== false) { $count++; }
}

echo json_encode(['ok'=>true,'updated'=>$count,'new_status'=>'rejected']);
?>
