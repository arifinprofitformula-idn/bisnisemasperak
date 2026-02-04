<?php
// Safe image upload for Froala editor
$root = __DIR__;
@include_once $root . DIRECTORY_SEPARATOR . 'config.php';
@include_once $root . DIRECTORY_SEPARATOR . 'fungsi.php';
$logFile = $root . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'image_upload.log';
function epi_log_img($msg){ global $logFile; @error_log('['.date('c').'] '.$msg."\n", 3, $logFile); }

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  epi_log_img('Invalid method for upload_image');
  echo json_encode(['ok'=>false,'error'=>'Invalid method']); exit;
}

$maxSize = 5242880; // 5MB
$allowedExt = ['jpg','jpeg','png','gif'];
$allowedMime = ['image/jpeg','image/jpg','image/png','image/gif'];
$uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'img';
if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0755, true); }
@chmod($uploadDir, 0755);

if (!isset($_FILES['file'])) { echo json_encode(['ok'=>false,'error'=>'No file']); exit; }
$f = $_FILES['file'];
if (!empty($f['error'])) { epi_log_img('Upload error code='.$f['error']); echo json_encode(['ok'=>false,'error'=>'Upload error: '.$f['error']]); exit; }

$ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
$mime = (string)($f['type'] ?? '');
if (!in_array($ext, $allowedExt) || !in_array($mime, $allowedMime)) { epi_log_img('Unsupported type ext='.$ext.' mime='.$mime); echo json_encode(['ok'=>false,'error'=>'Unsupported type']); exit; }
if ((int)$f['size'] > $maxSize) { epi_log_img('File too large size='.$f['size']); echo json_encode(['ok'=>false,'error'=>'File too large']); exit; }

$judul = isset($_POST['judul']) ? strtolower((string)$_POST['judul']) : 'image';
$base = preg_replace('/\s+/', '-', $judul);
$base = preg_replace('/[^a-z0-9\-]+/', '', $base);
$base = preg_replace('/\-+/', '-', $base);
$base = trim($base, '-');
if ($base==='') { $base = 'image'; }
$name = $base.'-'.date('YmdHis').'-'.mt_rand(100,999).'.'.$ext;
$target = $uploadDir.DIRECTORY_SEPARATOR.$name;
$backupDir = $uploadDir.DIRECTORY_SEPARATOR.'_backup';
if (!is_dir($backupDir)) { @mkdir($backupDir, 0755, true); }

$tmp = $f['tmp_name'];
$ok = false;
if (class_exists('Imagick')) {
  try {
    $imgClass = 'Imagick';
    $img = new $imgClass();
    $img->readImage($tmp);
    $width = $img->getImageWidth(); $height = $img->getImageHeight();
    $maxW = 1600; $maxH = 1200; $ratio = min($maxW/$width, $maxH/$height, 1);
    $newW = (int)floor($width*$ratio); $newH = (int)floor($height*$ratio);
    $img->setimagebackgroundcolor('white');
    if (defined('Imagick::LAYERMETHOD_FLATTEN')) { $img->mergeImageLayers(constant('Imagick::LAYERMETHOD_FLATTEN')); }
    if ($ext==='jpg' || $ext==='jpeg') { if (defined('Imagick::COMPRESSION_JPEG')) { $img->setImageCompression(constant('Imagick::COMPRESSION_JPEG')); } $img->setImageCompressionQuality(82); }
    if (defined('Imagick::FILTER_CATROM')) { $img->resizeImage($newW, $newH, constant('Imagick::FILTER_CATROM'), 1, true); } else { $img->resizeImage($newW, $newH, 0, 1, true); }
    $img->stripImage();
    $ok = $img->writeImage($target);
  } catch (Throwable $e) { epi_log_img('Imagick failed: '.$e->getMessage()); $ok = false; }
}
if (!$ok) {
  // Fallback GD
  $img = false;
  if ($ext==='jpg' || $ext==='jpeg') { if (function_exists('imagecreatefromjpeg')) { $img = @imagecreatefromjpeg($tmp); } }
  elseif ($ext==='png') { if (function_exists('imagecreatefrompng')) { $img = @imagecreatefrompng($tmp); } }
  elseif ($ext==='gif') { if (function_exists('imagecreatefromgif')) { $img = @imagecreatefromgif($tmp); } }
  if ($img !== false) {
    $width = imagesx($img); $height = imagesy($img);
    $maxW = 1600; $maxH = 1200; $ratio = min($maxW/$width, $maxH/$height, 1);
    $newW = (int)floor($width*$ratio); $newH = (int)floor($height*$ratio);
    $tmpImg = imagecreatetruecolor($newW, $newH);
    imagecopyresampled($tmpImg, $img, 0,0,0,0, $newW,$newH, $width,$height);
    if ($ext==='jpg' || $ext==='jpeg') { $ok = imagejpeg($tmpImg, $target, 82); }
    elseif ($ext==='png') { $ok = imagepng($tmpImg, $target, 6); }
    elseif ($ext==='gif') { $ok = imagegif($tmpImg, $target); }
    @imagedestroy($tmpImg); @imagedestroy($img);
  }
  if (!$ok) {
    // backup original tmp
    $bk = $backupDir.DIRECTORY_SEPARATOR.'bk-'.date('YmdHis').'-'.mt_rand(1000,9999).'.'.$ext;
    @copy($tmp, $bk);
    epi_log_img('Processing failed; original backed up to '.$bk);
    $ok = @move_uploaded_file($tmp, $target);
  }
}

if (!$ok) { epi_log_img('Save failed for '.$target); echo json_encode(['ok'=>false,'error'=>'Save failed']); exit; }
@chmod($target, 0644);

$baseUrl = isset($weburl) ? $weburl : ((function_exists('weburl')) ? call_user_func('weburl') : '/');
$link = rtrim($baseUrl,'/').'/img/'.$name;
epi_log_img('Saved image '.$link);
echo json_encode(['ok'=>true,'link'=>$link]);
?>
