<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo $titlepage;?></title>
	<meta name="description" content="<?php echo $discpage;?>" />
	<link rel="shortcut icon" type="image/x-icon" href="<?= $weburl;?>img/<?= $favicon;?>" />
	<!-- Bootstrap Core CSS -->
  <link href="<?= $weburl;?>bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?=$weburl;?>fontawesome/css/fontawesome.min.css" rel="stylesheet" />
  <link href="<?=$weburl;?>fontawesome/css/regular.min.css" rel="stylesheet" />
  <link href="<?=$weburl;?>fontawesome/css/solid.min.css" rel="stylesheet" /> 
	<link href="https://fonts.googleapis.com/css?family=Open+Sans" rel="stylesheet">
	<link rel="stylesheet" type="text/css" href="<?php echo $weburl;?>theme/simple/style.css">
	<style type="text/css">
		.social-proof {
		  font-family: 'Open Sans', sans-serif;
		  position: fixed;
		  bottom: <?php echo $settings['jarakbwh'] ??= '80';?>px;
		  right: 10px;
		  z-index: 9999;
		  padding: 10px;
		}
		.social-proof-box { 
			background: <?php 
			$hex = $settings['bgsocialproof'] ??= '#000000';
			list($r, $g, $b) = sscanf($hex, "#%02x%02x%02x");
			echo 'rgba('.$r.', '.$g.', '.$b.', 0.8)'; ?>;
			color: <?php echo $settings['txtsocialproof'] ??= '#ffffff';?>;			
		}
		.social-proof-box a { color: <?php echo $settings['txtsocialproof'] ??= '#ffffff';?>; }
		.box { 
			background: <?php 
			$hex = $settings['bgsponsor'] ??= '#000000';
			list($r, $g, $b) = sscanf($hex, "#%02x%02x%02x");
			echo 'rgba('.$r.', '.$g.', '.$b.', 0.6)'; ?>;
			color: <?php echo $settings['txtsponsor'] ??= '#ffffff';?>;			
		}
		
		.box a { color: <?php echo $settings['txtsponsor'] ??= '#ffffff';?>; }
		
	</style>
	<?php
	// Inject Meta Pixel & GTM with fallback (sponsor first, then site-wide)
	$pixelId = '';
	if (isset($datasponsor['fbpixel']) && !empty($datasponsor['fbpixel'])) {
		$pixelId = $datasponsor['fbpixel'];
	} elseif (isset($settings['fbpixel']) && !empty($settings['fbpixel'])) {
		$pixelId = $settings['fbpixel'];
	}
	$gtmId = '';
	if (isset($datasponsor['gtm']) && !empty($datasponsor['gtm'])) {
		$gtmId = $datasponsor['gtm'];
	} elseif (isset($settings['gtm']) && !empty($settings['gtm'])) {
		$gtmId = $settings['gtm'];
	}

	if (!empty($pixelId)) {
		$pixelId = htmlspecialchars($pixelId, ENT_QUOTES);
		?>
		<!-- Meta Pixel Code -->
		<script>
		!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
		n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
		n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
		t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
		fbq('init','<?= $pixelId ?>');
		fbq('track','PageView');
		</script>
		<noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=<?= $pixelId ?>&ev=PageView&noscript=1"/></noscript>
		<?php
	}
	if (!empty($gtmId)) {
		$gtmId = htmlspecialchars($gtmId, ENT_QUOTES);
		?>
		<!-- Google Tag Manager -->
		<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
		new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
		j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
		'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
		})(window,document,'script','dataLayer','GTM-<?= $gtmId ?>');</script>
		<!-- End Google Tag Manager -->
		<?php
	}
	?>
</head>
<body>
	<?php if (!empty($gtmId)) { ?>
	<!-- Google Tag Manager (noscript) -->
	<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-<?= htmlspecialchars($gtmId, ENT_QUOTES) ?>" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
	<!-- End Google Tag Manager (noscript) -->
	<?php } ?>
	<script type="text/javascript">
	// Push page context to dataLayer for GTM/GA4
	window.dataLayer = window.dataLayer || [];
	try {
		window.dataLayer.push({
			event: 'page_context',
			page_type: 'landing_iframe',
			page_title: '<?= addslashes($titlepage) ?>',
			page_url: '<?= addslashes($weburl) ?>',
			sponsor_id: '<?= isset($datasponsor['mem_id']) ? intval($datasponsor['mem_id']) : '' ?>',
			affiliate_code: '<?= isset($datasponsor['mem_kodeaff']) ? addslashes($datasponsor['mem_kodeaff']) : '' ?>'
		});
	} catch(e) {}
	(function(){
		var f10=false,f30=false,f60=false;
		function pushDwell(t){ try{ window.dataLayer.push({event:'dwell_time', seconds:t}); }catch(e){} }
		setTimeout(function(){ if(!f10){ f10=true; pushDwell(10);} }, 10000);
		setTimeout(function(){ if(!f30){ f30=true; pushDwell(30);} }, 30000);
		setTimeout(function(){ if(!f60){ f60=true; pushDwell(60);} }, 60000);
	})();
	</script>
	<div id="myDiv">
		<iframe id="myIframe" src="<?php echo $showpage??='';?>"></iframe>
	</div>	
	<?php		
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
			$listproof .= "'".$member.",";
		}

		$listproof = substr($listproof, 0,-1);
		echo '
	<div class="social-proof">
	  <div class="social-proof-box">
		<span class="social-proof-name"></span>	    
	  </div>
	</div>';
	}

	if (isset($settings['boxsponsor']) && !empty($settings['boxsponsor']) && isset($datasponsor)) {
		$sponsor = extractdata($datasponsor);
		$isibox = $settings['boxsponsor'];
		foreach ($sponsor as $key => $value) {
			$isibox = str_replace('['.$key.']', ($value??=''), $isibox);
		}

		echo '<div class="box"><div id="textbox">'.$isibox.'</div></div>';
	}

	if (isset($datamember['mem_status']) && $datamember['mem_status'] > 1 && isset($datasponsor['mem_id'])) {
		$ownerId = (int)$datasponsor['mem_id'];
		$counts = db_select("SELECT `sa_member`.`mem_status` AS `status`, COUNT(*) AS `jmldl` FROM `sa_sponsor` LEFT JOIN `sa_member` ON `sa_sponsor`.`sp_mem_id` = `sa_member`.`mem_id` WHERE `sa_sponsor`.`sp_sponsor_id` = ".$ownerId." GROUP BY `sa_member`.`mem_status`");
		$jumlah = array(0,0,0);
		if ($counts && is_array($counts)) { foreach ($counts as $row) { $jumlah[$row['status']] = (int)$row['jmldl']; } }
		$visitors30 = (int)(db_var("SELECT COALESCE(SUM(`count`),0) FROM `sa_visitor` WHERE `id_sponsor` = ".$ownerId." AND `visit_date` BETWEEN DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND CURDATE()") ?: 0);
		$registrants30 = (int)(db_var("SELECT COUNT(*) FROM `sa_member` LEFT JOIN `sa_sponsor` ON `sa_sponsor`.`sp_mem_id` = `sa_member`.`mem_id` WHERE `sa_sponsor`.`sp_sponsor_id` = ".$ownerId." AND DATE(`mem_tgldaftar`) BETWEEN DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND CURDATE()") ?: 0);

		echo '<div class="card mt-3" style="border-color:#D4AF37"><div class="card-header" style="background:#D4AF37;color:#0B0B0B">Ringkasan Referral</div><div class="card-body"><div class="row"><div class="col-6">Free Member</div><div class="col-6 text-end">'.number_format($jumlah[1]).'</div></div><div class="row"><div class="col-6">Premium Member</div><div class="col-6 text-end">'.number_format($jumlah[2]).'</div></div><div class="row"><div class="col-6">Total Member</div><div class="col-6 text-end">'.number_format($jumlah[1]+$jumlah[2]).'</div></div><hr/><div class="row"><div class="col-6">Pengunjung 30 hari</div><div class="col-6 text-end">'.number_format($visitors30).'</div></div><div class="row"><div class="col-6">Pendaftar 30 hari</div><div class="col-6 text-end">'.number_format($registrants30).'</div></div></div></div>';
	}
	?>

	<script type="text/javascript">		
		var screenHeight = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;
		document.getElementById("myIframe").style.height = (screenHeight) + "px";
		const names = [<?php echo ($listproof??='');?>];
		function getRandomName() {
		  const randomIndex = Math.floor(Math.random() * names.length);
		  return names[randomIndex];
		}

		function displaySocialProof() {
		  const box = document.querySelector('.social-proof-box');
		  const name = document.querySelector('.social-proof-name');
		  const randomName = getRandomName();
		  name.innerText = randomName;
		  box.style.display = 'inline-block';
		  setTimeout(function() {
			box.style.display = 'none';
			setTimeout(displaySocialProof, 2000);
		  }, 5000);
		}

		window.onload = displaySocialProof;
	</script>
</body>
</html>
