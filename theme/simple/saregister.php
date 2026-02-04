<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
if (!isset($idsponsor)) {
	if (isset($_COOKIE['idsponsor']) && is_numeric($_COOKIE['idsponsor'])) {
		$idsponsor = $_COOKIE['idsponsor'];
	} else {
		$idsponsor = 1;
	}
}

$datasponsor = db_row("SELECT * FROM `sa_member` WHERE `mem_id`=".$idsponsor);
$datasponsor = extractdata($datasponsor);
// Start session & set nonce for lightweight validation security
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
if (empty($_SESSION['epi_nonce'])) { $_SESSION['epi_nonce'] = sha1(SECRET . microtime(true) . rand(1000,9999)); }
?>
<!DOCTYPE html>
<html class="full" lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/x-icon" href="<?= $weburl;?>img/<?= $favicon;?>" />
    <meta name="description" content="">
    <meta name="author" content="">

    <title>REGISTRASI AKUN BARU</title>

    <!-- Bootstrap Core CSS -->
    <link href="<?= $weburl;?>bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?=$weburl;?>fontawesome/css/fontawesome.min.css" rel="stylesheet" />
	  <link href="<?=$weburl;?>fontawesome/css/regular.min.css" rel="stylesheet" />
	  <link href="<?=$weburl;?>fontawesome/css/solid.min.css" rel="stylesheet" /> 
    <style type="text/css">
        body {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 25%, #C0C0C0 75%, #A9A9A9 100%);
            min-height: 100vh;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            margin: 0;
            padding: 20px 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-y: auto;
        }
        
        .register-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 50px 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1), 
                        0 0 0 1px rgba(255, 255, 255, 0.2);
            max-width: 640px;
            width: 100%;
            border: 1px solid rgba(218, 165, 32, 0.2);
        }

        /* Form builder compatibility styles */
        .register-container .row {
            margin-bottom: 1rem;
        }

        .register-container .col-sm-4 {
            flex: 0 0 auto;
            width: 100%;
            margin-bottom: 0.5rem;
        }

        .register-container .col-sm-8 {
            flex: 0 0 auto;
            width: 100%;
        }

        .register-container .col-form-label {
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
            padding-top: 0;
            padding-bottom: 0;
        }

        .register-container .input-group-text {
            background: rgba(218, 165, 32, 0.1);
            border: 2px solid #e0e0e0;
            border-right: none;
            color: #333;
            font-weight: 600;
        }

        .register-container .input-group .form-control {
            border-left: none;
        }

        .register-container .form-select {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 15px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
        }

        .register-container .form-select:focus {
            border-color: #DAA520;
            box-shadow: 0 0 0 0.2rem rgba(218, 165, 32, 0.25);
            background: rgba(255, 255, 255, 1);
        }

        .register-container textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        .register-container .form-text {
            color: #666;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        .register-container .img-thumbnail {
            border: 2px solid rgba(218, 165, 32, 0.3);
            border-radius: 8px;
        }
        
        .welcome-badge {
            background: linear-gradient(45deg, #FFD700, #FFA500);
            color: #333;
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
        }
        
        .welcome-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }
        
        .welcome-subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        
        .form-label {
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .form-control {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 15px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
        }
        
        .form-control:focus {
            border-color: #DAA520;
            box-shadow: 0 0 0 0.2rem rgba(218, 165, 32, 0.25);
            background: rgba(255, 255, 255, 1);
        }
        
        .btn-register {
            background: linear-gradient(45deg, #DAA520, #FFD700, #FFA500, #DAA520);
            background-size: 300% 300%;
            border: none;
            color: #333;
            padding: 15px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(218, 165, 32, 0.3),
                        inset 0 1px 0 rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
            animation: buttonGlow 3s ease infinite;
        }

        .btn-register::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: left 0.5s;
        }

        .btn-register:hover::before {
            left: 100%;
        }
        
        .btn-register:hover {
            background: linear-gradient(45deg, #B8860B, #DAA520, #FFD700, #B8860B);
            background-size: 300% 300%;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(218, 165, 32, 0.4),
                        inset 0 1px 0 rgba(255, 255, 255, 0.3);
            color: #333;
        }

        @keyframes buttonGlow {
            0% {
                background-position: 0% 50%;
                box-shadow: 0 4px 15px rgba(218, 165, 32, 0.3),
                           inset 0 1px 0 rgba(255, 255, 255, 0.2);
            }
            50% {
                background-position: 100% 50%;
                box-shadow: 0 4px 20px rgba(218, 165, 32, 0.5),
                           inset 0 1px 0 rgba(255, 255, 255, 0.3),
                           0 0 20px rgba(255, 215, 0, 0.3);
            }
            100% {
                background-position: 0% 50%;
                box-shadow: 0 4px 15px rgba(218, 165, 32, 0.3),
                           inset 0 1px 0 rgba(255, 255, 255, 0.2);
            }
        }
        
        .form-check-input:checked {
            background-color: #DAA520;
            border-color: #DAA520;
        }
        
        .text-link {
            color: #666;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .text-link:hover {
            color: #DAA520;
        }
        
        .password-wrapper {
            position: relative;
        }
        
        .password-wrapper input[type="password"],
        .password-wrapper input[type="text"] {
            padding-right: 45px;
        }
        
        .password-wrapper .toggle-password {
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
            transition: color 0.3s ease;
        }
        
        .password-wrapper .toggle-password:hover {
            color: #DAA520;
        }
        
        .divider {
            text-align: center;
            margin: 25px 0;
            position: relative;
        }
        
        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(to right, transparent, #ddd, transparent);
        }
        
        .divider span {
            background: rgba(255, 255, 255, 0.95);
            padding: 0 20px;
            color: #666;
            font-size: 14px;
        }
        
        
        .sponsor-box {
            background: linear-gradient(135deg, #E8E8E8 0%, #C0C0C0 25%, #A8A8A8 50%, #C0C0C0 75%, #E8E8E8 100%);
            border: 2px solid #B8B8B8;
            border-radius: 15px;
            padding: 10px;
            text-align: center;
            margin: 15px 0;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15),
                        inset 0 1px 0 rgba(255, 255, 255, 0.8),
                        inset 0 -1px 0 rgba(0, 0, 0, 0.2);
            position: relative;
            overflow: hidden;
            font-weight: 600;
            color: #333;
            font-size: 12px;
        }
        
        .sponsor-box::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            animation: shimmer 3s infinite;
            pointer-events: none;
        }
        
        @keyframes shimmer {
            0% {
                transform: translateX(-100%) translateY(-100%) rotate(45deg);
            }
            100% {
                transform: translateX(100%) translateY(100%) rotate(45deg);
            }
        }
        
        
        @media (max-width: 576px) {
            .register-container {
                margin: 20px;
                padding: 30px 25px;
            }
            
            .welcome-title {
                font-size: 2rem;
            }
        }

        /* Background animation styles */
        .animated-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, #FFD700, #FFA500, #C0C0C0, #A9A9A9);
            background-size: 400% 400%;
            animation: gradientAnimation 3s ease infinite;
            z-index: -1;
        }

        .animated-background::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 20% 80%, rgba(255, 215, 0, 0.3) 0%, transparent 50%),
                        radial-gradient(circle at 80% 20%, rgba(192, 192, 192, 0.3) 0%, transparent 50%),
                        radial-gradient(circle at 40% 40%, rgba(255, 165, 0, 0.2) 0%, transparent 50%);
            animation: sparkleAnimation 3s ease infinite;
        }

        .animated-background::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                linear-gradient(45deg, transparent 30%, rgba(255, 255, 255, 0.1) 50%, transparent 70%),
                linear-gradient(-45deg, transparent 30%, rgba(255, 255, 255, 0.1) 50%, transparent 70%);
            animation: shineAnimation 3s ease infinite;
        }

        @keyframes gradientAnimation {
            0% {
                background-position: 0% 50%;
                filter: brightness(1) saturate(1);
            }
            33% {
                background-position: 100% 50%;
                filter: brightness(1.2) saturate(1.3);
            }
            66% {
                background-position: 50% 100%;
                filter: brightness(1.1) saturate(1.1);
            }
            100% {
                background-position: 0% 50%;
                filter: brightness(1) saturate(1);
            }
        }

        @keyframes sparkleAnimation {
            0% {
                opacity: 0.3;
                transform: scale(1) rotate(0deg);
            }
            33% {
                opacity: 0.8;
                transform: scale(1.1) rotate(120deg);
            }
            66% {
                opacity: 0.5;
                transform: scale(0.9) rotate(240deg);
            }
            100% {
                opacity: 0.3;
                transform: scale(1) rotate(360deg);
            }
        }

        @keyframes shineAnimation {
            0% {
                opacity: 0;
                transform: translateX(-100%) translateY(-100%);
            }
            33% {
                opacity: 0.6;
                transform: translateX(0%) translateY(0%);
            }
            66% {
                opacity: 0.3;
                transform: translateX(50%) translateY(50%);
            }
            100% {
                opacity: 0;
                transform: translateX(100%) translateY(100%);
            }
        }
    </style>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <script>
      function togglePassword() {
	      var passwordInput = document.getElementById("password");
	      var toggleBtn = document.getElementById("togglePassword");

	      if (passwordInput.type === "password") {
	        passwordInput.type = "text";
	        toggleBtn.innerHTML = '<i class="fas fa-eye-slash text-secondary"></i>';
	      } else {
	        passwordInput.type = "password";
	        toggleBtn.innerHTML = '<i class="fas fa-eye text-secondary"></i>';
	      }
	    }
	    function onSubmit(token) {
	     document.getElementById("registrasi").submit();
	   }
    </script>
    <script>
      // Expose validation endpoint & nonce to JS
      window.EPI_VALIDATE_URL = '<?= $weburl; ?>api/member-search.php?mode=exists';
      window.EPI_VALIDATE_NONCE = '<?= htmlspecialchars($_SESSION['epi_nonce'], ENT_QUOTES); ?>';
      function epiDebounce(fn, delay) {
        let t; return function(...args){ clearTimeout(t); t = setTimeout(()=>fn.apply(this,args), delay); };
      }
      function setInputError(input, message) {
        // Tampilkan error hanya dengan opsi Login (tanpa reset password)
        if (!input) return;
        input.classList.add('is-invalid');
        let fb = input.nextElementSibling;
        if (!fb || !fb.classList || !fb.classList.contains('invalid-feedback')) {
          fb = document.createElement('div'); fb.className = 'invalid-feedback'; input.parentNode.insertBefore(fb, input.nextSibling);
        }
        // Buat tautan Login yang auto-isi email
        const emailEl = document.querySelector('input[name="email"]');
        const currentEmail = emailEl ? emailEl.value.trim() : '';
        const loginBase = 'login';
        const loginHref = currentEmail ? (loginBase + '?email=' + encodeURIComponent(currentEmail)) : loginBase;
        fb.innerHTML = message + ' <a href="' + loginHref + '" class="fw-semibold" id="epi-inline-login">Login</a>';
        // Pastikan saat klik, email dipassing sebagai query param
        const l = fb.querySelector('#epi-inline-login');
        if (l) {
          l.addEventListener('click', function(e){
            // Jika email berubah sebelum klik, gunakan nilai terbaru
            const em = emailEl ? emailEl.value.trim() : '';
            if (em) {
              e.preventDefault();
              const url = loginBase + '?email=' + encodeURIComponent(em);
              window.location.href = url;
            }
          });
        }
      }
      function clearInputError(input) {
        if (!input) return; input.classList.remove('is-invalid');
        let fb = input.nextElementSibling; if (fb && fb.classList && fb.classList.contains('invalid-feedback')) { fb.textContent=''; }
      }
      async function checkExists(field, value) {
        if (!value) return {exists:false};
        const url = `${window.EPI_VALIDATE_URL}&field=${encodeURIComponent(field)}&value=${encodeURIComponent(value)}&n=${window.EPI_VALIDATE_NONCE}`;
        try {
          const res = await fetch(url, { method: 'GET', headers: { 'X-Requested-With': 'XMLHttpRequest' } });
          const data = await res.json();
          return data && data.ok ? data.data : {exists:false};
        } catch(e){ return {exists:false}; }
      }
      function attachRealtimeValidation() {
        const email = document.querySelector('input[name="email"]');
        const wa = document.querySelector('input[name="whatsapp"]');
        const submit = document.getElementById('formsubmit');
        const toggleSubmit = () => {
          const invalids = document.querySelectorAll('.is-invalid');
          if (submit) { submit.disabled = invalids.length > 0; }
        };
        if (email) {
          const onEmail = epiDebounce(async () => {
            clearInputError(email);
            const r = await checkExists('email', email.value.trim());
            if (r.exists) { setInputError(email, 'Email sudah terdaftar, silakan login untuk melanjutkan order.'); }
            toggleSubmit();
          }, 500);
          email.addEventListener('input', onEmail);
          email.addEventListener('blur', onEmail);
        }
        if (wa) {
          const onWa = epiDebounce(async () => {
            clearInputError(wa);
            const r = await checkExists('whatsapp', wa.value.trim());
            if (r.exists) { setInputError(wa, 'Nomor whatsapp sudah terdaftar, silakan login untuk melanjutkan order.'); }
            toggleSubmit();
          }, 500);
          wa.addEventListener('input', onWa);
          wa.addEventListener('blur', onWa);
        }
      }
      document.addEventListener('DOMContentLoaded', attachRealtimeValidation);
    </script>
    <?php
      $pixelId = '';
      if (isset($datasponsor['fbpixel']) && !empty($datasponsor['fbpixel'])) {
          $pixelId = htmlspecialchars($datasponsor['fbpixel'], ENT_QUOTES);
      } elseif (isset($settings['fbpixel']) && !empty($settings['fbpixel'])) {
          $pixelId = htmlspecialchars($settings['fbpixel'], ENT_QUOTES);
      }
      $gtmId = '';
      if (isset($datasponsor['gtm']) && !empty($datasponsor['gtm'])) {
          $gtmId = htmlspecialchars($datasponsor['gtm'], ENT_QUOTES);
      } elseif (isset($settings['gtm']) && !empty($settings['gtm'])) {
          $gtmId = htmlspecialchars($settings['gtm'], ENT_QUOTES);
      }
    ?>
    <?php if (!empty($pixelId)): ?>
  	<!-- Meta Pixel Code -->
		<script>
		!function(f,b,e,v,n,t,s)
		{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
		n.callMethod.apply(n,arguments):n.queue.push(arguments)};
		if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
		n.queue=[];t=b.createElement(e);t.async=!0;
		t.src=v;s=b.getElementsByTagName(e)[0];
		s.parentNode.insertBefore(t,s)}(window, document,'script',
		'https://connect.facebook.net/en_US/fbevents.js');
		fbq('init', '<?= $pixelId; ?>');
		fbq('track', 'PageView');
		</script>
		<noscript><img height="1" width="1" style="display:none"
		src="https://www.facebook.com/tr?id=<?= $pixelId; ?>&ev=PageView&noscript=1"
		/></noscript>
		<!-- End Meta Pixel Code -->
	<?php endif; ?>
	<?php if (!empty($gtmId)): ?>
		<!-- Google Tag Manager -->
		<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
		new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
		j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
		'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
		})(window,document,'script','dataLayer','GTM-<?= $gtmId; ?>');</script>
		<!-- End Google Tag Manager-->
	<?php endif;?>
</head>

<body>
	<?php if (!empty($gtmId)): ?>
	<!-- Google Tag Manager (noscript) -->
	<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-<?= $gtmId; ?>"
	height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
	<!-- End Google Tag Manager (noscript)-->
	<?php endif;?>
    <div class="animated-background"></div>
	<div class="register-container">
	      <?php
			  if (isset($_POST['nama']) && !empty($_POST['nama']) && isset($_POST['email']) && validemail($_POST['email'])) {
			  	
				  if (isset($settings['recap_secret']) && !empty($settings['recap_secret'])) {
						$secretKey = $settings['recap_secret'];

						// Data yang dikirimkan oleh formulir
						$recaptchaResponse = $_POST['g-recaptcha-response'];

						// Mendekripsi dan memeriksa respons reCAPTCHA menggunakan cURL
						$ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
						curl_setopt($ch, CURLOPT_POST, true);
						curl_setopt($ch, CURLOPT_POSTFIELDS, [
						    'secret' => $secretKey,
						    'response' => $recaptchaResponse,
						]);

						curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
						$response = curl_exec($ch);
						curl_close($ch);

						// Menguraikan respons JSON
						$result = json_decode($response, true);

						// Memeriksa apakah verifikasi reCAPTCHA berhasil
						if ($result && isset($result['success']) && $result['success']) {
						    // Proses formulir atau lakukan tindakan yang diinginkan di sini
						    $formok = 1;
						} else {
							echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
							  <strong>Error!</strong> Verifikasi reCAPTCHA gagal
							  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
							</div>';
						}
					} else {
						$formok = 1;
					}

			  if (isset($formok) && $formok == 1) {

				  if (db_exist("SELECT `mem_email` FROM `sa_member` WHERE `mem_email`='".cek($_POST['email'])."'")) {
						$error = 'Email sudah terdaftar, silakan login untuk melanjutkan order.';
					}

						# Cek duplikasi nomor WhatsApp
						if (!isset($error) && isset($_POST['whatsapp']) && !empty($_POST['whatsapp'])) {
							$formatted_wa = formatwa($_POST['whatsapp']);
							if (!empty($formatted_wa)) {
								if (db_exist("SELECT `mem_whatsapp` FROM `sa_member` WHERE `mem_whatsapp`='".cek($formatted_wa)."'")) {
										$error = 'Nomor whatsapp sudah terdaftar, silakan login untuk melanjutkan order.';
									}
								}
						}

						# Cek form yg required

						$req = db_select("SELECT * FROM `sa_form` WHERE `ff_registrasi`=1 AND `ff_required`=1");
						if (count($req) > 0) {
							foreach ($req as $req) {
								if (!isset($_POST[$req['ff_field']]) || empty($_POST[$req['ff_field']])) {
									$error = $req['ff_label'].' wajib diisi';
								} else {
									if ($req['ff_field'] == 'whatsapp') {
										if (empty(formatwa($_POST['whatsapp']))) {
											$error = $req['ff_label'].' wajib diisi dg format 08123456789';
										}
									}
								}
							}
						}

						if (!isset($error)) {
							if (isset($_POST['sponsor']) && !empty($_POST['sponsor'])) {
								$sponsor = db_var("SELECT `mem_id` FROM `sa_member` WHERE `mem_kodeaff`='".txtonly(strtolower($_POST['sponsor']))."'");
								
								if (is_numeric($sponsor)) {
									$idsponsor = $sponsor;
								} 
							}

							$defaultkey = array('nama','email','password','whatsapp','kodeaff');
							$datalain = '';
							
							unset($kodeaff);

							foreach ($_POST as $key => $value) {
								if (in_array($key, $defaultkey)) {
									${$key} = cek($value);
								} else {
									$datalain .= '['.txtonly(strtolower($key)).'|'.cek($value).']';
								}
							}							

							if (isset($_FILES) && count($_FILES) > 0) {
								$max_size = 1024000;
								$whitelist_ext = array('jpeg','jpg','png','gif');
								$whitelist_type = array('image/jpeg', 'image/jpg', 'image/png','image/gif');
								$pic_dir = str_replace('saregister_gold_silver.php','upload',__FILE__);
								$memberid = 'XXX'.rand(1000,9999).'XXX';
								
								if( ! file_exists( $pic_dir ) ) { mkdir( $pic_dir ); }

								foreach($_FILES as $field => $files) {
									$filename = $memberid.'_'.$field;
									$target_file = $pic_dir.'/'.$filename;
							    $uploadOk = 1;
							    $imageFileType = strtolower(pathinfo($files["name"],PATHINFO_EXTENSION));
							    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
							      $txterror = "Maaf, hanya support JPG, JPEG, PNG & GIF saja.";
							      $uploadOk = 0;
							    }
							    //Check that the file is of the right type
									if (!in_array($files["type"], $whitelist_type)) {
									  $txterror = "Maaf, hanya support JPG, JPEG, PNG & GIF saja.";
									  $uploadOk = 0;
									}
									// Check file size
							    if ($files["size"] > $max_size) {
							      $txterror = 'Maaf, gambar terlalu besar. Max. 1Mb';
							      $uploadOk = 0;
							    }
							    if ($uploadOk == 1) {
						        $file = $files["tmp_name"];
						        $target_file = $target_file.'.'.$imageFileType;
						        $img = new Imagick();
						        $img->readImage($file);
						        $width = $img->getImageWidth();
						        if ($width > 800) {
						            $width = 800;
						        }
						        $img->setimagebackgroundcolor('white');
						        //$img = $img->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
						        $img->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
						        $img->setImageCompression(Imagick::COMPRESSION_JPEG);
						        $img->setImageCompressionQuality(80);
						        $img->resizeImage($width,800,Imagick::FILTER_CATROM,1,TRUE);
						        $img->stripImage();
						        $img->writeImage($target_file);
						        #$gambar = $target_file.'.'.$imageFileType;
						        $datalain .= '['.txtonly(strtolower($field)).'|'.$filename.'.'.$imageFileType.']'; 
						    	}
								}
							}
							
							if (!isset($password) || empty($password)) { $password = randomword(); } else { $password = $_POST['password']; }

							if (!isset($kodeaff)) { $kodeaff = $nama; }
							$kodeaff = cekkodeaff(txtonly(strtolower($kodeaff)));
							if (isset($whatsapp)) { $whatsapp = formatwa($whatsapp); } else { $whatsapp = ''; }
													
							$newuserid = db_insert("INSERT INTO `sa_member` (
								`mem_nama`,`mem_email`,`mem_password`,`mem_whatsapp`,`mem_kodeaff`,
								`mem_datalain`,`mem_tgldaftar`,`mem_status`,`mem_role`) 
							VALUES ('".$nama."','".$email."','".create_hash($password)."',
								'".$whatsapp."','".$kodeaff."','".$datalain."','".date('Y-m-d H:i:s')."',
								1,1)");

							
							if (is_numeric($newuserid)) {
								$network = '['.numonly($idsponsor).']'.db_var("SELECT `sp_network` FROM `sa_sponsor` WHERE `sp_mem_id`=".$idsponsor);
								$cek = db_insert("INSERT INTO `sa_sponsor` (`sp_mem_id`,`sp_sponsor_id`,`sp_network`) VALUES ($newuserid,$idsponsor,'".$network."')");
								echo db_error();
								if (isset($memberid)) {
									$datalain = str_replace($memberid,$newuserid,$datalain);
									db_query("UPDATE `sa_member` SET `mem_datalain`='".$datalain."' WHERE `mem_id`=".$newuserid);
									$files = glob($pic_dir . '/'.$memberid.'*');					
									// Loop semua file yang ditemukan dan ganti nama file
									foreach ($files as $file) {
									    // Buat nama file baru dengan mengganti teks XXX123XXX dengan ID member baru
									    $newName = str_replace($memberid, $newuserid, $file);
									    // Ganti nama file
									    rename($file, $newName);
									}
								}
								# Kirim Notif yuk							
								$customfield['newpass'] = $password;
								sa_notif('daftar',$newuserid,$customfield);
								
							} else {
								echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
									  <strong>Error!</strong> '.db_error().'
									  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
									</div>';
							}
							
							if (isset($cek)) {
								if ($cek === false) {
									echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
									  <strong>Error!</strong> '.db_error().'
									  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
									</div>';
								} else {
									if (isset($settings['reg_sukses']) && !empty($settings['reg_sukses'])) {
										echo '
										<script type="text/javascript">
										try { if (typeof fbq === "function") { fbq("track", "Lead"); } } catch(e){}
										setTimeout(function(){ window.location = "'.$settings['reg_sukses'].'"; }, 800);
										</script>';
									} else {
										echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
										  <strong>Ok!</strong> Pendaftaran berhasil. Silahkan <a href="login">login ke dashboard</a>
										  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
										</div>';
										echo '<script type="text/javascript">try { if (typeof fbq === "function") { fbq("track", "Lead"); } } catch(e){} </script>';
									}
								}
							}							
						} else {
							echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
									  <strong>Error!</strong> '.$error.'
									  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
									</div>';
						}
					} #form ok
				}
				?>
	      <form action="" method="post" id="registrasi" onsubmit="document.getElementById('formsubmit').disabled=true;
					document.getElementById('formsubmit').value='Tunggu sebentar...';" enctype="multipart/form-data">
		      <div class="text-center mb-4">
		          <!-- Logo EPI -->
		      	<div class="mb-3">
		      		<img src="<?= $weburl; ?>upload/logo-webb.jpg" alt="EPI Logo" class="img-fluid" style="max-height: 80px; margin-bottom: 20px;">
		      	</div>
		      	<h1 class="welcome-title">FORM REGISTRASI</h1>
                <p class="welcome-subtitle">Buat Akun Baru</p>
		      </div>
		      
		      <?php 
		      echo form_builder('register'); 

		      if (isset($settings['recap_site']) && !empty($settings['recap_site'])) {
		      	echo '<button class="g-recaptcha btn btn-register w-100 mb-4" data-sitekey="'.$settings['recap_site'].'" id="formsubmit" data-callback="onSubmit" data-action="submit"> BUAT AKUN SEKARANG </button>';
		      } else {
		      	echo '<input type="submit" class="btn btn-register w-100 mb-4" id="formsubmit" value=" DAFTAR SEKARANG ">';
		      }
		      		      ?>
				  <?php 
			  		if (isset($datasponsor['nama'])) {
			  			echo '<div class="sponsor-box">';
			  			if (isset($settings['boxsponsor']) && !empty($settings['boxsponsor'])) {
								$isibox = $settings['boxsponsor'];
								foreach ($datasponsor as $key => $value) {
									$isibox = str_replace('['.$key.']', ($value??=''), $isibox);
								}
							echo $isibox;
			  			} else {
			  				echo '✨ Sponsor: '.$datasponsor['nama'].' ✨';
			  			}
			  			echo '</div>';
			  		}
				  	?>
				</form>
				
				<div class="text-center">
    <p class="mb-0">Sudah punya akun? <a href="login" class="text-link fw-semibold" onclick="event.preventDefault(); var em=document.querySelector('input[name=\"email\"]'); var v=em?em.value.trim():''; var u='login'+(v?('?email='+encodeURIComponent(v)):''); window.location.href=u;">LOGIN DISINI</a></p>
				</div>
	</div>
	<script src="<?= $weburl;?>bootstrap-5.3.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
