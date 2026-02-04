<?php
/*
Service Name: Dripsender
Service URL: https://dripsender.id/
API Documentation: https://docs.dripsender.id/
Field API Key: field1
*/
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }

if (isset($settings['set_field1']) && $settings['set_field1'] != '' && isset($nohp) && isset($pesan)) {
	$api_key = trim($settings['set_field1']);
	
	$message = array(
                'api_key'	=> $api_key,
                'phone'		=> $nohp,
                'text' 		=> stripslashes($pesan)
            );
	if (isset($gambar) && $gambar != '') {		
		$message['media_url'] = $urlgambar.'gambar/'.$gambar;
	} 

	$message = json_encode($message);
	$curl = curl_init();
	curl_setopt_array($curl, array(
	  CURLOPT_URL => 'https://api.dripsender.id/send',
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => '',
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 0,
	  CURLOPT_FOLLOWLOCATION => true,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => 'POST',
	  CURLOPT_POSTFIELDS => $message,
	  CURLOPT_HTTPHEADER => array('Content-Type: application/json')
	));

	$return = curl_exec($curl);
	curl_close($curl);
}