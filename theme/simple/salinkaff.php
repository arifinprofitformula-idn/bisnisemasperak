<?php
if (isset($_POST['kodeaff']) && !empty($_POST['kodeaff'])) {
	if (substr($_POST['kodeaff'], 0,4) == 'http') {
		# Berarti ada http-nya jadi hapus dulu		
		$kodeaff = str_replace($weburl, '', $_POST['kodeaff']);
		if (substr($kodeaff, 0,4) == 'http') {
			# Jika masih ada, berarti dia input http padahal webnya https
			$newweb = str_replace('https://','http://',$weburl);
			$kodeaff = str_replace($newweb, '', $_POST['kodeaff']);
		}
		$kodeaff = txtonly($kodeaff);
	} else {
		$kodeaff = txtonly($_POST['kodeaff']);
	}

	# Cek apakah ada pemiliknya
	$setkhususpremium = getsettings('khususpremium');
	if ($setkhususpremium == 1) {
		# Affiliasi hanya khusus premium
		$khususpremium = " AND `mem_status` > 1";
	} else {
		$khususpremium = "";
	}

$datasponsor = db_row("SELECT * FROM `sa_member` WHERE `mem_kodeaff`='".strtolower($kodeaff)."'".$khususpremium);
if (isset($datasponsor['mem_id'])) {
    // Set cookie sponsor agar halaman homepage mengenali sponsor yang dipilih
    setcookie("idsponsor", $datasponsor['mem_id'], strtotime('+30 days'), '/');

    // Jika kode aff adalah 'admin' (mem_id = 1) atau bentrok dengan direktori fisik,
    // arahkan ke homepage agar tidak membuka listing direktori /admin/
    $docroot = realpath(__DIR__ . '/../../');
    $collidesWithDir = false;
    if ($docroot !== false) {
        $targetDir = $docroot . DIRECTORY_SEPARATOR . $datasponsor['mem_kodeaff'];
        if (is_dir($targetDir)) { $collidesWithDir = true; }
    }

    if ($datasponsor['mem_id'] == 1 || strtolower($datasponsor['mem_kodeaff']) === 'admin' || $collidesWithDir) {
        header("Location:".$weburl);
    } else {
        // Normal behaviour: redirect ke homepage dengan slug kodeaff
        header("Location:".$weburl.$datasponsor['mem_kodeaff']);
    }
    die(); exit();
} else {
    $error = 'Maaf, URL tidak valid atau sponsor anda belum melakukan upgrade';
}
}
?>
<!DOCTYPE html>
<html class="full" lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/x-icon" href="<?= $weburl.$favicon; ?>" />
    <meta name="description" content="">
    <meta name="author" content="">
    <title>Masukkan Kode Referral EPIC Hub dari EPI Channel</title>
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#DAA520">
    <link rel="apple-touch-icon" href="/upload/epic-hub.jpg">
    <!-- Bootstrap Core CSS (lokal, mengikuti salogin) -->
    <link href="/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome (lokal) -->
    <link href="/fontawesome/css/all.css" rel="stylesheet" />
    <link href="/fontawesome/css/solid.css" rel="stylesheet" />
    <style type="text/css">
        body {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 25%, #C0C0C0 75%, #A9A9A9 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-container {
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
            font-size: 2.2rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }
        .welcome-subtitle {
            color: #666;
            margin-bottom: 24px;
        }
        .form-label { color: #333; font-weight: 600; margin-bottom: 8px; }
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
        .btn-login {
            background: linear-gradient(45deg, #DAA520, #FFD700);
            border: none;
            color: #333;
            padding: 15px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(218, 165, 32, 0.3);
        }
        .btn-login:hover {
            background: linear-gradient(45deg, #B8860B, #DAA520);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(218, 165, 32, 0.4);
            color: #333;
        }
        @media (max-width: 576px) {
            .login-container { margin: 20px; padding: 30px 25px; }
            .welcome-title { font-size: 1.8rem; }
        }
    </style>
    <style>
      .site-logo{max-height:60px;height:auto;width:auto;transition:filter .2s ease}
      @media (max-width:576px){.site-logo{max-height:48px}}
      .site-logo:hover{filter:brightness(0.95)}
    </style>
    <style>
      /* PWA Loader & Prompt (mengikuti salogin) */
      .pwa-loader { position: fixed; inset: 0; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.85); z-index: 9999; backdrop-filter: blur(2px); }
      .pwa-loader img { width: clamp(56px, 18vw, 96px); height: auto; animation: floatLogo 1.8s ease-in-out infinite; border-radius: 12px; box-shadow: 0 8px 24px rgba(218,165,32,0.4); }
      body.with-pwa-loader { padding-top: 0; }
      @keyframes floatLogo { 0%{ transform: translateY(0);} 50%{ transform: translateY(-10px);} 100%{ transform: translateY(0);} }
      #pwa-install-prompt { position: fixed; left: 0; right: 0; top: 0px; display: none; z-index: 9999; }
      #pwa-install-prompt .pwa-prompt-box { margin: 0 auto; max-width: 520px; background: rgba(255,255,255,0.95); border: 1px solid rgba(218,165,32,0.2); border-radius: 12px; padding: 12px 16px; box-shadow: 0 8px 24px rgba(0,0,0,0.08); display: flex; align-items: center; gap: 10px; }
      #pwa-install-prompt .pwa-prompt-title { color: #333; font-weight: 600; }
      #pwa-install-prompt .pwa-actions { margin-left: auto; display: flex; gap: 8px; }
    </style>
    <script>
      // Tidak perlu toggle password di halaman ini
    </script>
</head>

<body class="with-pwa-loader">
  <!-- PWA loading overlay (center) -->
  <div id="pwa-loader" class="pwa-loader" aria-hidden="true">
    <?php $logoSrc = function_exists('epi_resolve_logo_src') ? epi_resolve_logo_src($weburl, isset($settings)?$settings:getsettings(), $settings['logoweb'] ?? null) : ($weburl.'img/simpleaff-logo.png'); ?>
    <img src="<?= htmlspecialchars($logoSrc, ENT_QUOTES); ?>" alt="Loading" class="site-logo" />
  </div>
  <!-- Minimal, non-intrusive install prompt at header -->
  <div id="pwa-install-prompt" role="dialog" aria-labelledby="pwaPromptTitle" aria-modal="false">
    <div class="pwa-prompt-box">
      <div class="pwa-prompt-title" id="pwaPromptTitle">Install aplikasi EPIC Hub?</div>
      <div class="pwa-actions">
        <button id="pwaInstallBtn" type="button" class="btn btn-sm btn-login">Install</button>
        <button id="pwaDismissBtn" type="button" class="btn btn-sm btn-outline-secondary">Next Time</button>
      </div>
    </div>
  </div>

  <div class="login-container">
    <?php if (isset($error) && !empty($error)) { echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
      <strong>Error!</strong> '.htmlspecialchars($error, ENT_QUOTES, 'UTF-8').'.
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>'; } ?>

    <form action="" method="post">
      <div class="text-center mb-4">
        <!-- Logo EPI -->
        <div class="mb-3">
          <?php $logoSrc = function_exists('epi_resolve_logo_src') ? epi_resolve_logo_src($weburl, isset($settings)?$settings:getsettings(), $settings['logoweb'] ?? null) : ($weburl.'img/simpleaff-logo.png'); ?>
          <img src="<?= htmlspecialchars($logoSrc, ENT_QUOTES); ?>" alt="EPI Logo" class="site-logo" style="margin-bottom:20px;">
        </div>
        <h1 class="welcome-title">Masukkan Kode Referral</h1>
        <p class="welcome-subtitle">Silakan isi kode referral sponsor Anda untuk melanjutkan</p>
      </div>

      <div class="mb-3">
        <label class="form-label">URL Affiliasi</label>
        <div class="input-group mb-3">
          <span class="input-group-text" id="basic-addon1"><?= htmlspecialchars($weburl, ENT_QUOTES, 'UTF-8');?></span>
          <input type="text" class="form-control" name="kodeaff" placeholder="koderefreral" aria-describedby="basic-addon1" required>
        </div>
      </div>

      <input type="submit" class="btn btn-login w-100 mb-2" value="SUBMIT KODE">
    </form>
  </div>

  <script src="/bootstrap-5.3.3/js/bootstrap.bundle.min.js"></script>

  <!-- PWA: SW registration, loader hide, and install prompt handling (mengikuti salogin) -->
  <script>
    // Register Service Worker (silent failure if not supported)
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', function() {
        navigator.serviceWorker.register('/sw.js').catch(function(err){ /* no-op */ });
      });
    }

    // Hide loader on full page load
    (function(){
      var loaderEl = document.getElementById('pwa-loader');
      window.addEventListener('load', function(){ if (loaderEl) { loaderEl.style.display = 'none'; document.body.classList.remove('with-pwa-loader'); } });
    })();

    // Handle install prompt (non-intrusive)
    (function(){
      var deferredPrompt;
      var promptEl = document.getElementById('pwa-install-prompt');
      var installBtn = document.getElementById('pwaInstallBtn');
      var dismissBtn = document.getElementById('pwaDismissBtn');
      window.addEventListener('beforeinstallprompt', function(e){
        e.preventDefault();
        deferredPrompt = e;
        if (promptEl) promptEl.style.display = 'block';
      });
      if (installBtn) {
        installBtn.addEventListener('click', function(){
          if (!deferredPrompt) { if (promptEl) promptEl.style.display = 'none'; return; }
          deferredPrompt.prompt();
          deferredPrompt.userChoice.finally(function(){
            deferredPrompt = null;
            if (promptEl) promptEl.style.display = 'none';
          });
        });
      }
      if (dismissBtn) {
        dismissBtn.addEventListener('click', function(){ if (promptEl) promptEl.style.display = 'none'; });
      }
    })();
  </script>
</body>
</html>
