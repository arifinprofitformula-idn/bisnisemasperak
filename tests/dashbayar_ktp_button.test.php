<?php
require_once __DIR__.'/../config.php';

function renderKtpButtons($ktpUrl, $receiverId){
  $ktp = trim((string)$ktpUrl);
  $html = '';
  $html .= '<div class="mt-1 d-flex gap-2">';
  $html .= '<button type="button" class="btn btn-ktp btn-outline-secondary" '.(empty($ktp)?'disabled aria-disabled="true"':'data-ktp="'.htmlspecialchars($ktp,ENT_QUOTES).'"').'>Lihat KTP</button>';
  $html .= '<button type="button" class="btn btn-ktp btn-outline-secondary btn-upload-ktp" data-receiver="'.(int)$receiverId.'" '.(!empty($ktp)?'disabled aria-disabled="true"':'').'>Upload KTP</button>';
  $html .= '</div>';
  return $html;
}

function assertContains($hay,$needle,$msg){ echo (strpos($hay,$needle)!==false) ? "OK: {$msg}\n" : "FAIL: {$msg} (missing '{$needle}')\n"; }
function assertNotContains($hay,$needle,$msg){ echo (strpos($hay,$needle)===false) ? "OK: {$msg}\n" : "FAIL: {$msg} (should not contain '{$needle}')\n"; }

// Case 1: KTP belum ada
$h1 = renderKtpButtons('', 123);
assertContains($h1,'>Upload KTP<','label upload exists');
assertNotContains($h1,'btn-upload-ktp" disabled','upload enabled when no KTP');
assertContains($h1,'Lihat KTP</button>','label lihat exists');
assertContains($h1,'Lihat KTP</button>','lihat button present');
assertContains($h1,'aria-disabled="true"','lihat disabled when no KTP');

// Case 2: KTP sudah ada
$h2 = renderKtpButtons('http://example.test/upload/ktp/ktp_123.jpg', 123);
assertContains($h2,'data-ktp="http://example.test/upload/ktp/ktp_123.jpg"','lihat has data-ktp when available');
assertNotContains($h2,'aria-disabled="true"','lihat enabled when KTP exists');
assertContains($h2,'btn-upload-ktp" disabled','upload disabled when KTP exists');

echo "Done.\n";
?>
