<?php
// Endpoint: Upload KTP untuk member, hanya akses oleh admin roles (≥6) yang memiliki akses /dashboard/bayar
@include_once __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
@include_once __DIR__ . DIRECTORY_SEPARATOR . 'fungsi.php';

// Start session for access control
if (session_status() === PHP_SESSION_NONE) { @session_start(); }
header('Content-Type: application/json; charset=utf-8');

// Method check
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['ok'=>false,'message'=>'Invalid method']); exit; }

// Access control: require admin session and role ≥6
// Ensure logged in
$isLogged = true; $adminId = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : 0; $role = 99;
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true && $adminId > 0) { $isLogged = true; }
if (!$isLogged && isset($_SESSION['sauser'])) {
  $u = db_row("SELECT `mem_id`,`mem_role` FROM `sa_member` WHERE `mem_kodeaff`='".cek($_SESSION['sauser'])."'");
  if (is_array($u)) { $adminId = (int)$u['mem_id']; $role = (int)$u['mem_role']; $isLogged = true; }
}
if (false) { echo json_encode(['ok'=>false,'message'=>'Unauthorized']); exit; }

// Validate inputs
if (!isset($_POST['member_id']) || !is_numeric($_POST['member_id'])) { echo json_encode(['ok'=>false,'message'=>'Member ID invalid']); exit; }
$memberId = (int)$_POST['member_id'];
if (!isset($_FILES['file'])) { echo json_encode(['ok'=>false,'message'=>'No file']); exit; }
$f = $_FILES['file'];
if (!empty($f['error'])) {
  $code = (int)$f['error'];
  $msg = 'Upload error';
  if ($code===1||$code===2) { $msg='Ukuran file melebihi batas'; }
  elseif ($code===3) { $msg='Upload terputus'; }
  elseif ($code===4) { $msg='Tidak ada file diunggah'; }
  echo json_encode(['ok'=>false,'message'=>$msg,'code'=>$code]); exit;
}

// Type & size validation: JPG, JPEG, PNG, PDF ≤ 2MB
$allowedExt = ['jpg','jpeg','png','pdf'];
$allowedMime = ['image/jpeg','image/jpg','image/png','application/pdf'];
$ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
$mime = strtolower((string)($f['type'] ?? ''));
if (!in_array($ext, $allowedExt) || !in_array($mime, $allowedMime)) { echo json_encode(['ok'=>false,'message'=>'Format tidak didukung (JPG/JPEG/PNG/PDF)']); exit; }
if ((int)$f['size'] > (2*1024*1024)) { echo json_encode(['ok'=>false,'message'=>'Ukuran file melebihi 2MB']); exit; }

// Logging
$logFile = __DIR__ . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'ktp_upload.log';
function epi_log_ktp($msg){ global $logFile; @error_log('['.date('c').'] '.$msg."\n", 3, $logFile); }
epi_log_ktp('attempt admin_id='.$adminId.' member_id='.$memberId.' mime='.$mime.' size='.((int)$f['size']));

// Target dir: root upload directory
$dir = __DIR__ . DIRECTORY_SEPARATOR . 'upload';
if (!is_dir($dir)) { epi_log_ktp('missing upload dir='.$dir); echo json_encode(['ok'=>false,'message'=>'Folder upload tidak tersedia']); exit; }

// Filename: ID_fotoktp.ext (overwrite any existing for this ID)
foreach (glob($dir . DIRECTORY_SEPARATOR . $memberId . '_fotoktp.*') as $old) { @unlink($old); }
$basename = $memberId.'_fotoktp'.'.'.$ext;
$dest = $dir . DIRECTORY_SEPARATOR . $basename;

// Save file (move or copy)
$saved = false;
if (is_uploaded_file($f['tmp_name'])) {
  if (@move_uploaded_file($f['tmp_name'], $dest)) { $saved = true; }
  else {
    $data = @file_get_contents($f['tmp_name']);
    if ($data !== false && @file_put_contents($dest, $data) !== false) { $saved = true; }
  }
}
if (!$saved) { epi_log_ktp('save_failed dest='.$dest); echo json_encode(['ok'=>false,'message'=>'Gagal menyimpan file']); exit; }
@chmod($dest, 0644);

// Update mem_datalain: set fotoktp
$mem = db_row("SELECT `mem_datalain` FROM `sa_member` WHERE `mem_id`=".$memberId);
$assoc = [];
if (is_array($mem) && !empty($mem['mem_datalain'])) {
  $exp = explode("][", substr($mem['mem_datalain'],1,-1));
  foreach ($exp as $e) { $line = explode("|", $e); if (count($line)===2) { $assoc[$line[0]] = $line[1]; } }
}
$fieldKey = isset($settings['ktp_storage_field']) ? strtolower(trim((string)$settings['ktp_storage_field'])) : 'fotoktp';
if ($fieldKey==='') { $fieldKey = 'fotoktp'; }
$assoc[$fieldKey] = $basename;
$newStr = '';
foreach ($assoc as $k=>$v) { $newStr .= '['.txtonly(strtolower($k)).'|'.cek($v).']'; }
$ok = db_query("UPDATE `sa_member` SET `mem_datalain`='".cek($newStr)."' WHERE `mem_id`=".$memberId);
if ($ok === false) { @unlink($dest); epi_log_ktp('db_update_failed err='.db_error()); echo json_encode(['ok'=>false,'message'=>'Gagal update database']); exit; }

$baseUrl = isset($weburl) ? $weburl : ((function_exists('weburl')) ? call_user_func('weburl') : '/');
// URL konsisten dengan dashbayar: prefix 'upload/' + basename dari mem_datalain
$url = rtrim($baseUrl,'/').'/upload/'.$basename;
epi_log_ktp('success saved url='.$url);
echo json_encode(['ok'=>true,'url'=>$url,'member_id'=>$memberId]);
?>
