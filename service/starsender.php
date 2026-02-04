<?php
/*
Service Name: Starsender
Service URL: https://lutvi.com/starsender
API Documentation: https://starsender.online/rest-api
Field API Key: field1
*/
# Handle Kirim Pesan
if (isset($settings['set_field1']) && $settings['set_field1'] != '' && isset($nohp) && isset($pesan)) {
	if (isset($gambar) && $gambar != '') {		
		$gambar = $urlgambar.'gambar/'.$gambar;
	}

	if (strpos($pesan, '[button]') !== false) {
		$button = GetBetween($pesan,'[button]','[/button]');		
	}

	if (isset($button) && $button != '') {
		$pesan = str_replace('[button]'.$button.'[/button]', '', $pesan);
		$data = [
		  "tujuan" => $nohp,
		  "message" => $pesan,
		  "button" => $button
		];

		if (isset($gambar) && $gambar != '') {
			$data['file_url'] = $gambar;
		}

		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => 'https://starsender.online/api/sendButton',
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => '',
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => 'POST',
		  CURLOPT_POSTFIELDS => $data,
		  CURLOPT_HTTPHEADER => array(
		    'apikey: '.$settings['set_field1']
		  ),
		));

		$return = curl_exec($curl);

		curl_close($curl);
	} else {

		$curl = curl_init();
		curl_setopt_array($curl, array(
		  CURLOPT_URL => 'https://starsender.online/api/sendFiles?message='.rawurlencode($pesan).'&tujuan='.rawurlencode($nohp.'@s.whatsapp.net'),
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => '',
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => 'POST',
		  CURLOPT_POSTFIELDS => array('file'=> $gambar),
		  CURLOPT_HTTPHEADER => array(
		    'apikey: '.$settings['set_field1']
		  ),
		));

		$return = curl_exec($curl);

		curl_close($curl);
	}
}
