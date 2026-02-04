<?php
/*
Service Name: Fonnte
Service URL: https://fonnte.com
API Documentation: https://docs.fonnte.com/
Field API Token: field1
*/

if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
$curl = curl_init();
$token = $settings['set_field1'];

$curl = curl_init();
$datasend = array(
'target' => $nohp,
'message' => stripslashes($pesan),
'schedule' => '0',
'delay' => '0',
'countryCode' => '62'
);

curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://api.fonnte.com/send',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS => $datasend,
  CURLOPT_HTTPHEADER => array(
    'Authorization: '.$token
  ),
));

$return = curl_exec($curl);

curl_close($curl);