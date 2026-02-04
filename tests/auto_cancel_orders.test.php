<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../fungsi.php';

function assertEqual($a,$b,$msg){ echo ($a === $b) ? "OK: {$msg}\n" : ("FAIL: {$msg} => got '".var_export($a,true)."' expected '".var_export($b,true)."'\n"); }

define('EPI_AUTOCANCEL_NO_EXEC', true);
require_once __DIR__.'/../jobs/auto_cancel_orders.php';

if (!function_exists('epi_run_auto_cancel_orders')) {
  echo "FAIL: auto-cancel functions not loaded\n";
  exit(1);
}

epi_auto_cancel_orders_ensure_schema();

$now = date('Y-m-d H:i:s');
$batchId = 'test_batch_' . date('Ymd_His');

$createdOrders = array();
$createdConfirms = array();

function insertOrder($memberId, $tglOrder, $tglUpdated) {
  global $con, $createdOrders;
  $memberId = (int)$memberId;
  $q = "INSERT INTO `sa_order` (`order_idmember`,`order_idsponsor`,`order_idproduk`,`order_tglorder`,`order_harga`,`order_hargaunik`,`order_trx`,`order_status`,`order_idstaff`,`order_updated_at`) VALUES (".
       $memberId.",0,0,'".mysqli_real_escape_string($con,$tglOrder)."',10000,0,'manual',0,0,'".mysqli_real_escape_string($con,$tglUpdated)."')";
  $ok = mysqli_query($con, $q);
  if (!$ok) { echo "FAIL: insertOrder db error: ".mysqli_error($con)."\n"; exit(1); }
  $oid = (int)mysqli_insert_id($con);
  $createdOrders[] = $oid;
  return $oid;
}

function insertConfirm($orderId, $status, $createdAt) {
  global $con, $createdConfirms;
  $orderId = (int)$orderId;
  $status = (int)$status;
  $q = "INSERT INTO `epi_payment_confirm` (`order_id`,`invoice_no`,`atas_nama`,`status`,`created_at`) VALUES (".
       $orderId.",'INVTEST','Tester',".$status.",'".mysqli_real_escape_string($con,$createdAt)."')";
  $ok = mysqli_query($con, $q);
  if (!$ok) { echo "FAIL: insertConfirm db error: ".mysqli_error($con)."\n"; exit(1); }
  $cid = (int)mysqli_insert_id($con);
  $createdConfirms[] = $cid;
  return $cid;
}

function getOrderStatus($orderId) {
  global $con;
  $orderId = (int)$orderId;
  $row = mysqli_query($con, "SELECT `order_status` FROM `sa_order` WHERE `order_id`=".$orderId." LIMIT 1");
  if (!$row) { echo "FAIL: getOrderStatus db error: ".mysqli_error($con)."\n"; exit(1); }
  $data = mysqli_fetch_assoc($row);
  return isset($data['order_status']) ? (int)$data['order_status'] : null;
}

function getLastAutoCancelAction($orderId) {
  global $con;
  $orderId = (int)$orderId;
  $row = mysqli_query($con, "SELECT `action` FROM `epi_auto_cancel_log` WHERE `order_id`=".$orderId." ORDER BY `id` DESC LIMIT 1");
  if (!$row) { echo "FAIL: getLastAutoCancelAction db error: ".mysqli_error($con)."\n"; exit(1); }
  $data = mysqli_fetch_assoc($row);
  return isset($data['action']) ? (string)$data['action'] : null;
}

$t49 = date('Y-m-d H:i:s', strtotime($now) - (49*3600));
$t47 = date('Y-m-d H:i:s', strtotime($now) - (47*3600));
$t72 = date('Y-m-d H:i:s', strtotime($now) - (72*3600));
$t70 = date('Y-m-d H:i:s', strtotime($now) - (70*3600));

$oid1 = insertOrder(1, $t49, $t49);
$r1 = epi_run_auto_cancel_orders(array('now'=>$now,'batch_id'=>$batchId,'send_notif'=>false));
assertEqual(getOrderStatus($oid1), 2, 'Auto-cancel setelah 48 jam tanpa konfirmasi');
assertEqual(getLastAutoCancelAction($oid1), 'cancel', 'Log action cancel untuk auto-cancel');

$oid2 = insertOrder(1, $t49, $t49);
insertConfirm($oid2, 0, $t47);
$r2 = epi_run_auto_cancel_orders(array('now'=>$now,'batch_id'=>$batchId,'send_notif'=>false));
assertEqual(getOrderStatus($oid2), 0, 'Tidak auto-cancel jika ada konfirmasi sebelum 48 jam');
assertEqual(getLastAutoCancelAction($oid2), 'skip_confirm', 'Log action skip_confirm untuk invoice terkonfirmasi');

$oid3 = insertOrder(1, $t72, $t72);
insertConfirm($oid3, 0, $t70);
$r3 = epi_run_auto_cancel_orders(array('now'=>$now,'batch_id'=>$batchId,'send_notif'=>false));
assertEqual(getOrderStatus($oid3), 0, 'Tetap MENUNGGU KONFIRMASI meski verifikasi admin > 48 jam');
assertEqual(getLastAutoCancelAction($oid3), 'skip_confirm', 'Log action skip_confirm untuk konfirmasi pending lama');

if (count($createdOrders) > 0) {
  mysqli_query($con, "DELETE FROM `epi_auto_cancel_log` WHERE `order_id` IN (".implode(',', array_map('intval',$createdOrders)).")");
  mysqli_query($con, "DELETE FROM `epi_payment_confirm_log` WHERE `order_id` IN (".implode(',', array_map('intval',$createdOrders)).")");
}
if (count($createdConfirms) > 0) {
  mysqli_query($con, "DELETE FROM `epi_payment_confirm` WHERE `id` IN (".implode(',', array_map('intval',$createdConfirms)).")");
}
if (count($createdOrders) > 0) {
  mysqli_query($con, "DELETE FROM `sa_order` WHERE `order_id` IN (".implode(',', array_map('intval',$createdOrders)).")");
}

echo "Done.\n";
?>
