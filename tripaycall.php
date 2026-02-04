<?php
include('fungsi.php');
$json = file_get_contents('php://input');

// Ambil callback signature
$callbackSignature = isset($_SERVER['HTTP_X_CALLBACK_SIGNATURE'])
    ? $_SERVER['HTTP_X_CALLBACK_SIGNATURE']
    : '';

// Isi dengan private key anda
$privateKey = getsettings('tripay_private');

// Generate signature untuk dicocokkan dengan X-Callback-Signature
$signature = hash_hmac('sha256', $json, $privateKey);

// Validasi signature
if ($callbackSignature !== $signature) {
    exit(json_encode([
        'success' => false,
        'message' => 'Invalid signature',
        'callbackSignature' => $callbackSignature,
        'signature' => $signature
    ]));
}

$data = json_decode($json,TRUE);

if (JSON_ERROR_NONE !== json_last_error()) {
    exit(json_encode([
        'success' => false,
        'message' => 'Invalid data sent by payment gateway',
    ]));
}

// Hentikan proses jika callback event-nya bukan payment_status
if ('payment_status' !== $_SERVER['HTTP_X_CALLBACK_EVENT']) {
    exit(json_encode([
        'success' => false,
        'message' => 'Unrecognized callback event: ' . $_SERVER['HTTP_X_CALLBACK_EVENT'],
    ]));
}

if ($data['is_closed_payment'] == 1 && $data['status'] == 'PAID') {
	$paycode = array(); # array('CODE'=>'alamat file proses plugin/toko/prosesorder.php');
  $paycode = apply_filter('paycode',$paycode);
  if (count($paycode) > 0) {
    foreach ($paycode as $key => $fileproses) {
      $lencode = strlen($key);
      if (substr($data['merchant_ref'], 0,$lencode) == $key) {
        $idinvoice = intval(substr($data['merchant_ref'], $lencode));
        $staff = 0;
        include($fileproses);
        $proses = 1;
        break;
      }
    }
  }

  if (!isset($proses)) {
    $idinvoice = intval($data['merchant_ref']);
  	$staff = 0;
  	include('prosesorder.php');
  }

} else {
	print_r($data);
}
