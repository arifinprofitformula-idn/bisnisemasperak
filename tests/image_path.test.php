<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../fungsi.php';

function assertTrue($cond,$msg){ echo $cond ? "OK: {$msg}\n" : "FAIL: {$msg}\n"; }

$uploadDir = dirname(__DIR__).DIRECTORY_SEPARATOR.'img';
@mkdir($uploadDir,0755,true);
$tmp = $uploadDir.DIRECTORY_SEPARATOR.'test-image.png';
@file_put_contents($tmp, "PNG");

assertTrue(epi_image_exists('test-image.png')===true,'exists true');
echo epi_image_safe_url('test-image.png')."\n";
@unlink($tmp);
assertTrue(epi_image_exists('missing.png')===false,'exists false');
echo epi_image_safe_url('missing.png')."\n";
echo "Done.\n";
?>
