<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');
require_once '../config.php';
require_once '../fungsi.php';
try {
  $q = isset($_GET['q']) ? cek($_GET['q']) : '';
  $limit = isset($_GET['limit']) ? max(1,(int)$_GET['limit']) : 500;
  $where = '';
  if ($q !== '') { $where = "WHERE `kat_nama` LIKE '%{$q}%'"; }
  $rows = db_select("SELECT `kat_id` AS id, `kat_nama` AS name FROM `sa_kategori` {$where} ORDER BY `kat_nama` LIMIT {$limit}") ?: [];
  // No status column; mark all as active
  foreach($rows as &$r){ $r['status'] = 1; }
  echo json_encode(['success'=>true,'data'=>$rows]);
} catch (Throwable $e) {
  http_response_code(500); echo json_encode(['success'=>false,'message'=>'Server error']);
}

