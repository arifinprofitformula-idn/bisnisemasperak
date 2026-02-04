<?php
if (!defined('IS_IN_SCRIPT')) { define('IS_IN_SCRIPT', true); }

function env($key, $default = null){
  return getenv($key) ?: $default;
}

function epi_gateway_send_wa($message, $to, $provider = null){
  $country = '62';
  $normalized = epi_normalize_phone($to);
  $masked = epi_mask_phone($normalized);
  $provider = $provider ?: (getsettings('wa_provider') ?: 'starsender');

  if ($provider === 'dripsender') {
    $apikey = getsettings('dripsender_apikey') ?: env('DRIPSENDER_API_KEY');
    $host = getsettings('dripsender_host') ?: env('DRIPSENDER_HOST','https://api.dripsender.id');
    // Dripsender kompatibel dengan SAP: endpoint /send dan payload api_key/phone/text
    $payload = [
      'api_key' => $apikey,
      'phone'   => $country.$normalized,
      'text'    => $message
    ];
    $url = rtrim($host,'/').'/send';
    $res = epi_http_post_json($url, $payload, ['Content-Type: application/json']);
    // Evaluasi keberhasilan berdasar body
    $data = is_array($res['data'] ?? null) ? $res['data'] : [];
    $success = null;
    if (array_key_exists('status', $data)) {
      $v = $data['status'];
      $success = is_bool($v) ? $v : (is_string($v) ? in_array(strtolower($v), ['true','success','ok','sent']) : (is_numeric($v) ? intval($v) === 1 : null));
    } elseif (array_key_exists('success', $data)) {
      $v = $data['success'];
      $success = is_bool($v) ? $v : (is_string($v) ? in_array(strtolower($v), ['true','success','ok','sent']) : (is_numeric($v) ? intval($v) === 1 : null));
    }
    if ($success !== null) { $res['ok'] = !!$success; }
    $msg = $data['message'] ?? ($data['error'] ?? null);
    epi_log('-', 'send_wa', $masked, $res['ok']?'sent':'failed', json_encode(['provider'=>'dripsender','status'=>$res['status'] ?? null,'msg'=>$msg]));
    return $res;
  } else {
    // starsender v3 default
    $apikey = getsettings('starsender_apikey') ?: env('STARSENDER_API_KEY');
    $host = getsettings('starsender_host') ?: env('STARSENDER_HOST','https://starsender.online');
    $payload = [
      'to' => $country.$normalized,
      'message' => $message
    ];
    $url = rtrim($host,'/').'/api/sendText';
    $headers = [
      'Accept: application/json',
      'Content-Type: application/json',
      'Authorization: Bearer '.$apikey
    ];
    $res = epi_http_post_json($url, $payload, $headers);
    // Evaluasi keberhasilan berdasar body
    $data = is_array($res['data'] ?? null) ? $res['data'] : [];
    $success = null;
    if (array_key_exists('status', $data)) {
      $v = $data['status'];
      $success = is_bool($v) ? $v : (is_string($v) ? in_array(strtolower($v), ['true','success','ok','sent']) : (is_numeric($v) ? intval($v) === 1 : null));
    } elseif (array_key_exists('success', $data)) {
      $v = $data['success'];
      $success = is_bool($v) ? $v : (is_string($v) ? in_array(strtolower($v), ['true','success','ok','sent']) : (is_numeric($v) ? intval($v) === 1 : null));
    }
    if ($success !== null) { $res['ok'] = !!$success; }
    $msg = $data['message'] ?? ($data['error'] ?? null);
    epi_log('-', 'send_wa', $masked, $res['ok']?'sent':'failed', json_encode(['provider'=>'starsender','status'=>$res['status'] ?? null,'msg'=>$msg]));
    return $res;
  }
}

function epi_http_post_json($url, $payload, $headers = []){
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
  curl_setopt($ch, CURLOPT_TIMEOUT, 20);
  $response = curl_exec($ch);
  $errno = curl_errno($ch);
  $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  if ($errno) {
    return ['ok'=>false,'status'=>$status,'error'=>true,'response'=>null,'data'=>null];
  }
  $data = json_decode($response, true);
  return ['ok'=>($status>=200 && $status<300),'status'=>$status,'data'=>$data,'response'=>$response];
}
?>