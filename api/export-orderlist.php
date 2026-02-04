<?php
$__root = dirname(__DIR__, 1);
@include_once $__root . DIRECTORY_SEPARATOR . 'config.php';
@include_once $__root . DIRECTORY_SEPARATOR . 'fungsi.php';

if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }

// Resolve logged-in user
$currentUserId = 0;
try { if (function_exists('is_login')) { $uid = is_login(); if ($uid) { $currentUserId = (int)$uid; } } } catch (Throwable $e) {}
if ($currentUserId <= 0 && isset($_SESSION['member']['mem_id'])) { $currentUserId = (int)$_SESSION['member']['mem_id']; }
if ($currentUserId <= 0) {
  http_response_code(403);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok'=>false,'message'=>'Forbidden']);
  exit;
}

// Inputs
$format = isset($_GET['format']) && in_array($_GET['format'], ['csv','xlsx']) ? $_GET['format'] : 'csv';
$status = (isset($_GET['status']) && $_GET['status'] !== '' && is_numeric($_GET['status'])) ? (int)$_GET['status'] : null;
$cari = isset($_GET['cari']) ? trim((string)$_GET['cari']) : '';

// Build where
$where = " WHERE o.`order_idmember`=".$currentUserId;
if ($status !== null) { $where .= " AND o.`order_status`=".$status; }
if ($cari !== '') {
  $s = cek($cari);
  if (is_numeric($cari)) { $where .= " AND o.`order_id`=".(int)$cari; }
  else { $where .= " AND (p.`page_judul` LIKE '%".$s."%' OR p.`page_diskripsi` LIKE '%".$s."%' OR p.`page_url` LIKE '%".$s."%')"; }
}

$sql = "SELECT o.`order_id`, o.`order_tglorder`, o.`order_hargaunik` AS `harga`, o.`order_status`, m.`mem_nama`, p.`page_judul`
        FROM `sa_order` o
        LEFT JOIN `sa_member` m ON m.`mem_id`=o.`order_idmember`
        LEFT JOIN `sa_page` p ON p.`page_id`=o.`order_idproduk`".$where." ORDER BY o.`order_tglorder` DESC";

$rows = db_select($sql);
if (!is_array($rows)) { $rows = []; }

// Prepare export rows
$data = [];
$data[] = ['ID Order', 'Tanggal Order', 'Nama', 'Produk', 'Harga', 'Status'];
foreach ($rows as $r) {
  $id = (int)($r['order_id'] ?? 0);
  $tgl = (string)($r['order_tglorder'] ?? '');
  $tglFmt = '';
  if ($tgl !== '') { $ts = strtotime($tgl); $tglFmt = $ts ? date('d/m/Y', $ts) : $tgl; }
  $nama = (string)($r['mem_nama'] ?? '');
  $produk = (string)($r['page_judul'] ?? '');
  $harga = (int)($r['harga'] ?? 0);
  $statusTxt = ((int)($r['order_status'] ?? 0) === 1) ? 'Lunas' : 'Belum Lunas';
  $data[] = [$id, $tglFmt, $nama, $produk, $harga, $statusTxt];
}

// Output
if (function_exists('ob_get_level')) { while (ob_get_level() > 0) { @ob_end_clean(); } }
@ini_set('display_errors','0');
date_default_timezone_set('Asia/Jakarta');
$fnameBase = 'OrderList_'.date('Ymd');

if ($format === 'csv') {
  header('Content-Type: text/csv; charset=UTF-8');
  header('Content-Disposition: attachment; filename="'.$fnameBase.'.csv"');
  header('Cache-Control: no-store, max-age=0');
  $out = fopen('php://output','w');
  fwrite($out, "\xEF\xBB\xBF");
  foreach ($data as $row) { fputcsv($out, $row); }
  fclose($out);
  exit;
} else {
  $xlsxOk = false;
  @include_once $__root.DIRECTORY_SEPARATOR.'xlsxgen.php';
  if (class_exists('SimpleXLSXGen')) {
    $xlsx = SimpleXLSXGen::fromArray($data);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="'.$fnameBase.'.xlsx"');
    $xlsx->downloadAs($fnameBase.'.xlsx');
    $xlsxOk = true; exit;
  } elseif (class_exists('\\Shuchkin\\XLSXGen')) {
    $clazz = '\\Shuchkin\\XLSXGen';
    $xlsx = call_user_func([$clazz, 'fromArray'], $data);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="'.$fnameBase.'.xlsx"');
    $xlsx->saveAs('php://output');
    $xlsxOk = true; exit;
  }
  if (!$xlsxOk) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="'.$fnameBase.'.csv"');
    $out = fopen('php://output','w');
    fwrite($out, "\xEF\xBB\xBF");
    foreach ($data as $row) { fputcsv($out, $row); }
    fclose($out); exit;
  }
}
?>
