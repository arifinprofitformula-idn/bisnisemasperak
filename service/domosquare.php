<?php
/*
Service Name: Domosquare
Service URL: https://billingotomatis.com
API Documentation: https://www.domosquare.com/tutorial/billingotomatis/api-wa-gateway-billingotomatis.html
Field API Server: field1
Field API ID: field2
Field API Key: field3
*/
if (!defined('IS_IN_SCRIPT')) { die();  exit; } 
# Handle Send Message
if (isset($settings['set_field1']) && $settings['set_field1'] != '' && isset($nohp) && isset($pesan)) {
	$var['api_id'] = $settings['set_field2'];
	$var['api_key'] = $settings['set_field3'];
	$var['phone'] = $nohp;
	
	if ($gambar != '') {
		#mengirimkan gambar/attachment
		$var['mime'] = 'image/png';
		$var['filename'] = 'gambar.png';
		$pic_dir = str_replace('service/domosquare.php','gambar',__FILE__);
		$gambar = $pic_dir.'/'.$gambar;
		$var['filedata'] = base64_encode(file_get_contents($gambar));
	}

	# Handle Button
	if (strpos($pesan, '[button]') !== false) {
		$button = GetBetween($pesan,'[button]','[/button]');
		if ($button != '') {
	    $pesan = str_replace('[button]'.$button.'[/button]', '', $pesan);
	    if (trim($pesan) == '') {
				$pesan = '---';
			}
	    $expbutton = explode('|', $button);
	    $thebutton = array();
	    $i = 1;
	    $newbutton = '';
	    foreach ($expbutton as $butcode) {
	      $newbutton .= '**'.$butcode."\n";
	    }

	    if ($newbutton != '') { 	    	
	    	$pesan .= '[button_list]'."\n".$newbutton.'[/button_list]'; 
	    }
	  }
	} elseif (strpos($pesan, '[link]') !== false) {
		$link = GetBetween($pesan,'[link]','[/link]');
		$pesan = str_replace('[link]'.$link.'[/link]', '', $pesan);
		if (trim($pesan) == '') {
			$pesan = '---';
		}
		if ($link != '') {
			$linklist = explode("\n", $link);
			$thebutton = '';
			foreach ($linklist as $linklist) {
				$itemlink = explode('|', $linklist);
				if (count($itemlink) == 3) {
					switch ($itemlink[0]) {
						case 'link':
							$thebutton .= '**'.$itemlink[1].'|'.$itemlink[2].'|url'."\n";
							break;
						case 'call':
							$thebutton .= '**'.$itemlink[1].'|'.$itemlink[2].'|number'."\n";
							break;
						case 'reply':
							$thebutton .= '**'.$itemlink[1]."\n";
					}					
				}
			}
			if ($thebutton != '') { 	    	
	    	$pesan .= '[button_list]'."\n".$thebutton.'[/button_list]';
	    }
		}
	}

	$var['text'] = stripslashes($pesan);
	$ch = curl_init($settings['set_field1']);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $var);
	$return = curl_exec($ch);
	curl_close($ch);
}
?>