<?php
if (!defined('IS_IN_SCRIPT')) { define('IS_IN_SCRIPT', true); }
// Ensure session is started for nonce and rate limit storage
if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }

function epi_nonce_create($action){
  $token = sha1($action . '-' . microtime(true) . '-' . rand(0,999999));
  $_SESSION['epi_nonce'][$action] = $token;
  return $token;
}

function epi_nonce_check($action,$token){
  return isset($_SESSION['epi_nonce'][$action]) && hash_equals($_SESSION['epi_nonce'][$action], $token);
}

function epi_generate_request_id(){
  return substr(hash('sha256', uniqid('req_', true) . microtime(true)),0,32);
}

function epi_rate_limit_key($scope){
  $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
  // Pastikan label <= 50 char untuk kolom set_label (varchar(50))
  return 'epi_rl_' . substr(sha1($scope.'|'.$ip), 0, 32);
}

function epi_rate_limit_check($scope, $maxPerMinute = 5){
  $key = epi_rate_limit_key($scope);
  $now = time();
  // Perbaikan kolom: gunakan set_label sesuai skema sa_setting
  $row = db_row("SELECT * FROM `sa_setting` WHERE `set_label`='".cek($key)."'");
  if (!$row) {
    updatesettings([$key => json_encode(['count'=>1,'ts'=>$now])]);
    return true;
  }
  $data = json_decode($row['set_value'] ?? '{}', true);
  if (!is_array($data)) { $data = ['count'=>1,'ts'=>$now]; }

  if (($now - ($data['ts'] ?? 0)) > 60) {
    $data = ['count'=>1,'ts'=>$now];
    updatesettings([$key => json_encode($data)]);
    return true;
  }

  if (($data['count'] ?? 0) >= $maxPerMinute) {
    return false;
  }

  $data['count'] = ($data['count'] ?? 0) + 1;
  updatesettings([$key => json_encode($data)]);
  return true;
}

function epi_log($requestId, $action, $maskedPhone, $status, $info = null){
  db_query("INSERT INTO `epi_login_otp_log` (`request_id`,`action`,`masked_phone`,`status`,`info`,`created_at`) VALUES ('".cek($requestId)."','".cek($action)."','".cek($maskedPhone)."','".cek($status)."','".cek($info)."','".date('Y-m-d H:i:s')."')");
}

// Normalisasi nomor WA: buang karakter non-digit, hilangkan awalan 62 atau 0
function epi_normalize_phone($raw){
  $p = preg_replace('/\D+/', '', (string)$raw);
  if ($p === '') { return $p; }
  if (strpos($p, '62') === 0) { $p = substr($p, 2); }
  if (strpos($p, '0') === 0) { $p = ltrim($p, '0'); }
  return $p;
}
?>