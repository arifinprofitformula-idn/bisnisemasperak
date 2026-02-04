<?php
/*
Service Name: WABiz
Service URL: https://partner.waplus.biz.id/cafebisnis
API Documentation: https://m2.notifwabiz.my.id/app/docapi.txt
Field API Device Number: field1
Field API API Key: field2
*/
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
if (isset($settings['set_field1']) && $settings['set_field1'] != '' && isset($nohp) && isset($pesan)) {
  $data = array(
      'api_key' => $settings['set_field2'],
      'sender' => $settings['set_field1'],
      'message' => $pesan,
      'number' => $nohp
  );

  $urlapp = 'https://m2.notifwabiz.my.id/send-message';

  if (isset($gambar) && $gambar != '') {
    $data['url'] = $urlgambar.'gambar/'.$gambar;
    $data['media_type'] = 'image';
    $data['caption'] = $pesan;
    $urlapp = 'https://m2.notifwabiz.my.id/send-media';
  }

  $curl = curl_init();                                                    
  curl_setopt_array($curl, array(
    CURLOPT_URL => $urlapp,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_HTTPHEADER => array(
      'Content-Type: application/json'
    ),
  ));
  
  $return = curl_exec($curl);  
  curl_close($curl);
}
?>