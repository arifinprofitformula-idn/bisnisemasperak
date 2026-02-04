<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');
require_once '../config.php';
require_once '../fungsi.php';
require_once '../plugin/epi-discount/engine.php';

try {
  if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'Method not allowed']); exit; }
  $code = isset($_GET['code']) ? trim($_GET['code']) : '';
  $pid = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
  $qty = isset($_GET['qty']) ? max(1,(int)$_GET['qty']) : 1;
  $base = isset($_GET['base']) ? (int)$_GET['base'] : 0;
  $display = isset($_GET['display']) ? (int)$_GET['display'] : 0;
  if ($base<=0 && $display<=0) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Base or display price required']); exit; }
  $user = is_login() ? getdatamember(is_login()) : null;
  $eng = new EpiDiscountEngine(); $rules = $eng->loadRules();
  $res = $eng->calculateSingle($rules, ['base'=>$base,'display'=>$display,'promo'=>$code,'product_id'=>$pid,'qty'=>$qty,'member'=>$user]);
  echo json_encode(['success'=>true,'data'=>$res]);
} catch (Throwable $e) {
  http_response_code(500); echo json_encode(['success'=>false,'message'=>'Server error']);
}

