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
  $where = "WHERE `pro_status`=1 AND `pro_harga` IS NOT NULL";
  if ($q !== '') { $where .= " AND `page_judul` LIKE '%{$q}%'"; }
  $rows = db_select("SELECT `page_id` AS id, `page_judul` AS name FROM `sa_page` {$where} ORDER BY `page_judul` LIMIT {$limit}") ?: [];
  echo json_encode(['success'=>true,'data'=>$rows]);
} catch (Throwable $e) {
  http_response_code(500); echo json_encode(['success'=>false,'message'=>'Server error']);
}

