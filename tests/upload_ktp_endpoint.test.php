<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../fungsi.php';

function resetEnv(){
  $_SERVER = ['REQUEST_METHOD'=>'POST'];
  if (session_status()===PHP_SESSION_NONE) { @session_start(); }
  $_SESSION['admin_logged_in']=true; $_SESSION['admin_id']=1; $_SESSION['admin_role']=9;
  $_POST = ['member_id'=>'1']; $_FILES = []; $_SERVER['HTTP_X_ACCESS_TOKEN'] = hash_hmac('sha256', (string)$_SESSION['admin_id'], SECRET);
}
function mkTmp($name,$bytes, $mime){
  $dir = sys_get_temp_dir(); $p = $dir.DIRECTORY_SEPARATOR.$name; 
  $data = str_repeat("a", $bytes); file_put_contents($p, $data);
  return ['name'=>$name,'type'=>$mime,'tmp_name'=>$p,'error'=>0,'size'=>$bytes];
}
function printRes($label,$res){ echo $label.': '.json_encode($res)."\n"; }

// Valid upload
resetEnv(); $_FILES['file'] = mkTmp('x.jpg', 10000, 'image/jpeg'); ob_start(); include __DIR__.'/../upload_ktp.php'; $out1 = ob_get_clean(); printRes('valid', json_decode($out1,true));

// Invalid format
resetEnv(); $_FILES['file'] = mkTmp('x.txt', 10000, 'text/plain'); ob_start(); include __DIR__.'/../upload_ktp.php'; $out2 = ob_get_clean(); printRes('invalid_format', json_decode($out2,true));

// No auth
resetEnv(); $_SESSION=[]; $_SERVER['HTTP_X_ACCESS_TOKEN']=''; $_FILES['file'] = mkTmp('x.jpg', 10000, 'image/jpeg'); ob_start(); include __DIR__.'/../upload_ktp.php'; $out3 = ob_get_clean(); printRes('no_auth', json_decode($out3,true));

// Too large
resetEnv(); $_FILES['file'] = mkTmp('x.jpg', 3*1024*1024, 'image/jpeg'); ob_start(); include __DIR__.'/../upload_ktp.php'; $out4 = ob_get_clean(); printRes('too_large', json_decode($out4,true));

echo "Done.\n";
?>
