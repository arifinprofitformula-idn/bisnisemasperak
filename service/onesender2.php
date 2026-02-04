<?php
/*
Service Name: OneSender v2
Service URL: https://onesender.net
API Documentation: https://onesender.net/docs/
Field API Server: field1
Field API Key: field2
*/
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
 
# Handle Send Message

if (isset($settings['set_field1']) && $settings['set_field1'] != '' && isset($nohp) && isset($pesan)) {
	$api_url = trim($settings['set_field1']);
	if ( '/' == substr($api_url, -1) ) {
		$api_url .= 'api/v1/messages';
	} else if ( '/api' == substr($api_url, -4) ) {
		$api_url .= '/v1/messages';
	}
	$api_key = $settings['set_field2'];
	$curl = curl_init();
	/*
	if (strpos($pesan, '[button]') !== false) {
		
		# Format Button
		# [button]Button Satu|Button Dua|Button Tiga[/button]
		
		$button = GetBetween($pesan,'[button]','[/button]');
		if ($button != '') {
	    $txtpesan = str_replace('[button]'.$button.'[/button]', '', $pesan);
	    if (trim($txtpesan) == '') {
				$txtpesan = '---';
			}
	    $expbutton = explode('|', $button);
	    $thebutton = array();
	    $i = 1;
	    foreach ($expbutton as $butcode) {
	        $arrbutton = array('type'  => 'reply',
	                           'reply' => array('id'=>'rep'.$i,'title'=>$butcode));
	        array_push($thebutton, $arrbutton);
	        $i++;
	    }
    	$message = array(
            'recipient_type' => 'individual',
            'to'             => $nohp,
            'type'           => 'interactive',
            'interactive'    => array( 
                'type' => 'button',
                'body' => array( 'text' => $txtpesan),
                'action' => array(
                        'buttons' => $thebutton
                    )
            ),
        	);
		}
		
	} elseif (strpos($pesan, '[link]') !== false) {
		# Format Button
		# [link]
		# Type|Text Link|URL
		# reply|Text Reply|xxx
		# link|Goto Google|https://google.com
		# call|Call Support|628970097777
		# [/link]

		$link = GetBetween($pesan,'[link]','[/link]');
		$txtpesan = str_replace('[link]'.$link.'[/link]', '', $pesan);
		if (trim($txtpesan) == '') {
			$txtpesan = '---';
		}
		if ($link != '') {
			$linklist = explode("\n", $link);
			$thebutton = array();
			foreach ($linklist as $linklist) {
				$itemlink = explode('|', $linklist);
				if (count($itemlink) == 3) {
					$arrbutton = array(
							'type' => trim($itemlink[0]),
							'parameter' => array(
								'title' => trim($itemlink[1]),
								'value' => trim($itemlink[2])
							)
					);
					array_push($thebutton, $arrbutton);
				}
			}

			$message = array(
        'recipient_type' 	=> 'individual',
        'to'							=> $nohp,
        'type'           	=> 'interactive_dev',
        'interactive_dev' => array(                 									
					'body' 		=> array( 
												'type' => 'text',
												'parameter' => array('value' => $txtpesan)
												),
					'action'	=> array(
												'buttons' => $thebutton
												)
  										),
        		);
			if (isset($gambar) && !empty($gambar)) {
				$message['interactive_dev']['header'] = array(
					'type' => 'image',
					'parameter' => array('value' => $urlgambar.'gambar/'.$gambar)												
				);
			}		
		}
	} else {
	*/
		if ($gambar == '') {
			$message = array(
		        'recipient_type' => 'individual',
		        'to' 			 => $nohp,
		        'type' 			 => 'text',
		        'text' 			 => array( 'body' => stripslashes($pesan) ),
		    );
		    
		} else {
			$message = array(
		        'recipient_type' => 'individual',
		        'to' 			 => $nohp,
		        'type' 			 => 'image',
		        'image' 		 => array( 'link' => $urlgambar.'gambar/'.$gambar,
		        'caption' => stripslashes($pesan) ),
		    );
		}
	// }

	$postfield = json_encode($message);
	curl_setopt_array($curl, array(
	  CURLOPT_URL => $api_url,
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => '',
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 0,
	  CURLOPT_FOLLOWLOCATION => true,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => 'POST',
	  CURLOPT_POSTFIELDS => $postfield,
	  CURLOPT_HTTPHEADER => array(
	    'Authorization: Bearer '.$api_key
	  ),
	));

	$return = curl_exec($curl);

	curl_close($curl);
}
?>