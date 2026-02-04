<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../fungsi.php';

function assertEqual($a,$b,$msg){ echo ($a === $b) ? "OK: {$msg}\n" : ("FAIL: {$msg} => got '".var_export($a,true)."' expected '".var_export($b,true)."'\n"); }

if (!isset($con)) {
  echo "FAIL: mysqli connection not available\n";
  exit(1);
}

$now = date('Y-m-d H:i:s');
$createdOrders = array();

function insertOrderRow($memberId, $productId, $tglOrder, $hargaNormal, $hargaFinal, $trx, $status) {
  global $con, $createdOrders;
  $memberId = (int)$memberId;
  $productId = (int)$productId;
  $hargaNormal = (int)$hargaNormal;
  $hargaFinal = (int)$hargaFinal;
  $trx = mysqli_real_escape_string($con, (string)$trx);
  $status = (int)$status;
  $tglOrder = mysqli_real_escape_string($con, (string)$tglOrder);
  $q = "INSERT INTO `sa_order` (`order_idmember`,`order_idsponsor`,`order_idproduk`,`order_tglorder`,`order_harga`,`order_hargaunik`,`order_trx`,`order_status`) VALUES (".
       $memberId.",0,".$productId.",'".$tglOrder."',".$hargaNormal.",".$hargaFinal.",'".$trx."',".$status.")";
  $ok = mysqli_query($con, $q);
  if (!$ok) { echo "FAIL: insertOrderRow db error: ".mysqli_error($con)."\n"; exit(1); }
  $oid = (int)mysqli_insert_id($con);
  $createdOrders[] = $oid;
  return $oid;
}

$pid = 999999;
$oidPaid = insertOrderRow(1, $pid, $now, 10000, 10123, 'manual', 1);
$oidFree = insertOrderRow(1, $pid, $now, 25000, 0, 'free', 1);

$omset = mysqli_query($con, "SELECT SUM(CASE WHEN `order_hargaunik`=0 THEN 0 ELSE `order_harga` END) AS s FROM `sa_order` WHERE `order_status`=1 AND `order_idproduk`=".(int)$pid);
if (!$omset) { echo "FAIL: omset query error: ".mysqli_error($con)."\n"; exit(1); }
$row = mysqli_fetch_assoc($omset);
$sumOmset = isset($row['s']) ? (int)$row['s'] : 0;
assertEqual($sumOmset, 10000, 'Omset mengecualikan transaksi gratis (final Rp0)');

$disk = mysqli_query($con, "SELECT SUM(CASE WHEN `order_hargaunik`=0 THEN 0 WHEN `order_harga` > `order_hargaunik` THEN (`order_harga` - `order_hargaunik`) ELSE 0 END) AS s FROM `sa_order` WHERE `order_status`=1 AND `order_idproduk`=".(int)$pid);
if (!$disk) { echo "FAIL: diskon query error: ".mysqli_error($con)."\n"; exit(1); }
$row2 = mysqli_fetch_assoc($disk);
$sumDisk = isset($row2['s']) ? (int)$row2['s'] : 0;
assertEqual($sumDisk, 0, 'Total diskon mengecualikan transaksi gratis');

$isGratis = function($hargaNormal, $hargaFinal, $trx) {
  $hargaNormal = (int)$hargaNormal;
  $hargaFinal = (int)$hargaFinal;
  $trx = (string)$trx;
  return ($hargaFinal === 0) && ($trx === 'free' || $hargaNormal > 0);
};
assertEqual($isGratis(25000, 0, 'free'), true, "Deteksi 'Gratis' untuk harga final 0 + trx free");
assertEqual($isGratis(0, 0, ''), false, "Bukan 'Gratis' jika harga normal 0 tanpa trx free");

if (count($createdOrders) > 0) {
  mysqli_query($con, "DELETE FROM `sa_order` WHERE `order_id` IN (".implode(',', array_map('intval',$createdOrders)).")");
}

echo "Done.\n";
?>
