<?php
$hexsp = $settings['bgsocialproof'] ??= '#000000';
list($r, $g, $b) = sscanf($hexsp, "#%02x%02x%02x");
$bgsocialproof = 'rgba('.$r.', '.$g.', '.$b.', 0.8)'; 

$hexs = $settings['bgsponsor'] ??= '#000000';
list($r, $g, $b) = sscanf($hexs, "#%02x%02x%02x");
$bgsponsor = 'rgba('.$r.', '.$g.', '.$b.', 0.6)';

if (isset($datasponsor)) {
	$extsponsor = extractdata($datasponsor);
}

$style = '	
	<style type="text/css">
		.social-proof {
			font-family: \'Open Sans\', sans-serif;
			position: fixed;
			bottom: '.($settings["jarakbwh"] ?? "80").'px;
			right: 10px;
			z-index: 9999;
			padding: 10px;
		}
		.social-proof-box { 
			background: '.$bgsocialproof.';
			color: '.($settings["txtsocialproof"] ?? "#ffffff").';			
		}
		.social-proof-box a { color: '.($settings["txtsocialproof"] ?? "#ffffff").'; }
		.box { 
			background: '.$bgsponsor.';
			color: '.($settings["txtsponsor"] ?? "#ffffff").';			
		}
		.box a { color: '.($settings["txtsponsor"] ?? "#ffffff").'; }		
	</style>';

$header = '
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>'.$titlepage.'</title>
	<meta name="description" content="'.$discpage.'" />
	<link rel="shortcut icon" type="image/x-icon" href="'.$weburl.$favicon.'" />
	<meta property="og:title" content="'.$titlepage.'"/>
	<meta property="og:description" content="'.$discpage.'"/>
	<meta property="og:url" content="'.$visiturl.'"/>
	<meta property="og:image" content="'.$weburl.$logoweb.'"/>
	<meta property="og:type" content="website" />

	<!-- Bootstrap Core CSS -->
	<link href="'.$weburl.'bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">
	<link href="'.$weburl.'fontawesome/css/fontawesome.min.css" rel="stylesheet" />
	<link href="'.$weburl.'fontawesome/css/regular.min.css" rel="stylesheet" />
	<link href="'.$weburl.'fontawesome/css/solid.min.css" rel="stylesheet" /> 
	<link href="https://fonts.googleapis.com/css?family=Open+Sans" rel="stylesheet">
	<link rel="stylesheet" type="text/css" href="'.$weburl.'theme/'.($settings["theme"] ?? "simple").'/style.css">
	'.$style;
if (isset($extsponsor['fbpixel'])) {
	$header .= '
		<!-- Meta Pixel Code -->
		<script>
		!function(f,b,e,v,n,t,s)
		{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
		n.callMethod.apply(n,arguments):n.queue.push(arguments)};
		if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version=\'2.0\';
		n.queue=[];t=b.createElement(e);t.async=!0;
		t.src=v;s=b.getElementsByTagName(e)[0];
		s.parentNode.insertBefore(t,s)}(window, document,\'script\',
		\'https://connect.facebook.net/en_US/fbevents.js\');
		fbq(\'init\', \''.($extsponsor['fbpixel'] ?? ''). '\');
		fbq(\'track\', \'PageView\');
		</script>
		<noscript><img height="1" width="1" style="display:none"
		src="https://www.facebook.com/tr?id=396425529796614&ev=PageView&noscript=1"
		/></noscript>';  	  
}

if (isset($extsponsor['gtm']) && !empty($extsponsor['gtm'])) {
	$header .= '
		<!-- Google Tag Manager -->
		<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({\'gtm.start\':
		new Date().getTime(),event:\'gtm.js\'});var f=d.getElementsByTagName(s)[0],
		j=d.createElement(s),dl=l!=\'dataLayer\'?\'&l=\'+l:\'\';j.async=true;j.src=
		\'https://www.googletagmanager.com/gtm.js?id=\'+i+dl;f.parentNode.insertBefore(j,f);
		})(window,document,\'script\',\'dataLayer\',\'GTM-'.$extsponsor['gtm']??=''.'\');</script>
		<!-- End Google Tag Manager-->
	';
}

$footer = '';

if (isset($settings['boxsocialproof']) && !empty($settings['boxsocialproof'])) {
	$newmember = db_select("SELECT * FROM `sa_member` ORDER BY `mem_tgldaftar` DESC LIMIT 0,10");
	$listproof = '';
	foreach ($newmember as $newmember) {
		$member = $settings['boxsocialproof'];			
		$memdata = extractdata($newmember);			
		foreach ($memdata as $key => $value) {				
			if (!empty($value)) {
				$member = str_replace('['.$key.']',addslashes($value),$member);
			} else {
				$member = str_replace('['.$key.']','',$member);
			}
		}
		$listproof .= "'".$member."',";
	}

	$listproof = substr($listproof, 0,-1);
	$footer .= '
<div class="social-proof">
  <div class="social-proof-box">
    <span class="social-proof-name"></span>	    
  </div>
</div>';
}

if (isset($settings['boxsponsor']) && !empty($settings['boxsponsor']) && isset($extsponsor)) {
    $isibox = $settings['boxsponsor'];
    foreach ($extsponsor as $key => $value) {
        $isibox = str_replace('['.$key.']', ($value??=''), $isibox);
    }
    $footer .= '<div class="box"><div id="textbox">'.$isibox.'</div></div>';
}

if (isset($datamember['mem_status']) && $datamember['mem_status'] > 1 && isset($datasponsor['mem_id'])) {
    $ownerId = (int)$datasponsor['mem_id'];
    $counts = db_select("SELECT `sa_member`.`mem_status` AS `status`, COUNT(*) AS `jmldl` FROM `sa_sponsor` LEFT JOIN `sa_member` ON `sa_sponsor`.`sp_mem_id` = `sa_member`.`mem_id` WHERE `sa_sponsor`.`sp_sponsor_id` = ".$ownerId." GROUP BY `sa_member`.`mem_status`");
    $jumlah = array(0,0,0);
    if ($counts && is_array($counts)) { foreach ($counts as $row) { $jumlah[$row['status']] = (int)$row['jmldl']; } }
    $visitors30 = (int)(db_var("SELECT COALESCE(SUM(`count`),0) FROM `sa_visitor` WHERE `id_sponsor` = ".$ownerId." AND `visit_date` BETWEEN DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND CURDATE()") ?: 0);
    $registrants30 = (int)(db_var("SELECT COUNT(*) FROM `sa_member` LEFT JOIN `sa_sponsor` ON `sa_sponsor`.`sp_mem_id` = `sa_member`.`mem_id` WHERE `sa_sponsor`.`sp_sponsor_id` = ".$ownerId." AND DATE(`mem_tgldaftar`) BETWEEN DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND CURDATE()") ?: 0);

    $footer .= '<div class="card mt-3" style="border-color:#D4AF37"><div class="card-header" style="background:#D4AF37;color:#0B0B0B">Ringkasan Referral</div><div class="card-body"><div class="row"><div class="col-6">Free Member</div><div class="col-6 text-end">'.number_format($jumlah[1]).'</div></div><div class="row"><div class="col-6">Premium Member</div><div class="col-6 text-end">'.number_format($jumlah[2]).'</div></div><div class="row"><div class="col-6">Total Member</div><div class="col-6 text-end">'.number_format($jumlah[1]+$jumlah[2]).'</div></div><hr/><div class="row"><div class="col-6">Pengunjung 30 hari</div><div class="col-6 text-end">'.number_format($visitors30).'</div></div><div class="row"><div class="col-6">Pendaftar 30 hari</div><div class="col-6 text-end">'.number_format($registrants30).'</div></div></div></div>';
}


$footer .= '
	<script type="text/javascript">		
		var screenHeight = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;
		document.getElementById("myIframe").style.height = (screenHeight) + "px";
		const names = ['.($listproof??='').'];
		function getRandomName() {
		  const randomIndex = Math.floor(Math.random() * names.length);
		  return names[randomIndex];
		}

		function displaySocialProof() {
		  const box = document.querySelector(\'.social-proof-box\');
		  const name = document.querySelector(\'.social-proof-name\');
		  const randomName = getRandomName();
		  name.innerText = randomName;
		  box.style.display = \'inline-block\';
		  setTimeout(function() {
		    box.style.display = \'none\';
		    setTimeout(displaySocialProof, 2000);
		  }, 5000);
		}

		window.onload = displaySocialProof;
	</script>
	';

if (isset($page) && isset($showpage)) {
	$metode = $page['page_method']??=1;
	$fr = $page['page_fr']??='';
	$defaulttarget = $weburl.'salesletter/contoh';
	$targeturl = $page['page_iframe']??=$defaulttarget;
} else {
	if (!isset($settings['metodelp']) || empty($settings['metodelp'])) {
		$settings['metodelp'] = 1;
		$newsettings['metodelp'] = 1;
		updatesettings($newsettings);
	}

	$metode = $settings['metodelp']??=1;
	$fr = $settings['fr']??='';
	switch ($metode) {
		case '1':	$targeturl = $settings['homepage']; break;
		case '2': $targeturl = $settings['urltarget']; break;
		case '3': $targeturl = $settings['urlredirect']; break;
		default: $targeturl = $settings['homepage']; break;
	}
}

switch ($metode) {
	case '1':
		$showlp = $header.'
		<div id="myDiv">
			<iframe id="myIframe" src="'.$targeturl.'"></iframe>
		</div>'.
		$footer.
		'</body>
		</html>';
		break;
	case '2':			
		if (isset($targeturl) && !empty($targeturl)) {
			$showlp = getCachedData($targeturl);
			if (isset($extsponsor)) {
				#$sponsor = extractdata($datasponsor);			

				if (isset($fr) && !empty($fr)) {
	    		$fr = unserialize($fr);
	    		if (is_array($fr) && count($fr) > 0) {
		    		foreach ($fr as $fr) {
		    			$showlp = str_replace($fr['find'], $fr['replace'], $showlp);
		    		}
	    		}
	    	}	

	    	foreach ($extsponsor as $key => $value) {
					$showlp = str_replace('['.$key.']', ($value??=''), $showlp);
				}    	
			}
		}
		break;
	case '3' :			
		if (isset($targeturl) && !empty($targeturl)) {
			header("Location:".$targeturl);
		}
		die();
		break;
	default:
		if (function_exists('lp_'.$metode)) {
			$showlp = call_user_func('lp_'.$metode);
		} else {
			$showlp = '';
		}
		break;
}

echo $showlp;
