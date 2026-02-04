<?php
/*
Service Name: WA Responder
Service URL: https://app.waresponder.co.id
API Documentation: https://waresponder.co.id/docs/
Field API Device ID: field1
Field API Key: field2
*/
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
 
# Handle Send Message

if (isset($settings['set_field1']) && $settings['set_field1'] != '' && isset($nohp) && isset($pesan)) {
    $device_id = $settings['set_field1'];
    $api_key = $settings['set_field2'];

    if ($gambar == '') {
        $data = [
            'api_key' => $api_key,
            'device_id' => $device_id,
            'number' => $nohp,
            'message' => $pesan
        ];

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://app.waresponder.co.id/api/text.php',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_HTTPHEADER => array(
                'multipart/form-data'
            )
        ));
        $return = curl_exec($curl);
        curl_close($curl);
    } else {
        $data = [
            'api_key' => $api_key,
            'device_id' => $device_id,
            'number' => $nohp,
            'message' => $pesan,
            'url' => $urlgambar . 'gambar/' . $gambar
        ];

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://app.waresponder.co.id/api/media.php',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_HTTPHEADER => array(
                'multipart/form-data'
            )
        ));
        $return = curl_exec($curl);
        curl_close($curl);
    }
}
?>