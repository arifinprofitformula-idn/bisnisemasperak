<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
if (isset($_POST['username']) && filter_var($_POST['username'],FILTER_VALIDATE_EMAIL) 
	&& isset($_POST['password']) && !empty($_POST['password'])) {
	$datamember = db_row("SELECT * FROM `sa_member` WHERE `mem_email`='".cek($_POST['username'])."'");
	if (isset($datamember['mem_email'])) {
		if (validate_password($_POST['password'],$datamember['mem_password'])) {
      $id = $datamember['mem_id'];
      $hash = sha1(rand(0,500).microtime().SECRET);
      $signature = sha1(SECRET . $hash . $id);
      $cookie = base64_encode($signature . "-" . $hash . "-" . $id);
      setcookie('authentication', $cookie,time()+36000,'/');
      db_query("UPDATE `sa_member` SET `mem_lastlogin`='".date('Y-m-d H:i:s')."' WHERE `mem_id`=".$id);
      if (isset($_GET['redirect'])) {
      	if (substr($_GET['redirect'],0,1) == '/') {
      		$gored = substr($_GET['redirect'],1);
      	} else {
      		$gored = $_GET['redirect'];
      	}
        header('Location:'.$weburl.$gored);
      } else {
      	header('Location:'.$weburl.'dashboard');
      }
      echo 'Login berhasil';
    } else {
        $error = 'Email atau Password anda salah.';
    }
	} else {
		$error = 'Email anda salah.';
	}
}
?>
<!DOCTYPE html>
<html class="full" lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/x-icon" href="<?= $weburl.$favicon;?>" />
    <meta name="description" content="">
    <meta name="author" content="">
    <title>Login — EPI Hub</title>
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#DAA520">
    <link rel="apple-touch-icon" href="/upload/epic-hub.jpg">
    <!-- Bootstrap Core CSS -->
    <link href="/bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome: gunakan aset lokal agar webfonts tidak error di preview -->
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
            font-size: 2.0rem;
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
        
        @media (max-width: 576px) {
            .login-container {
                margin: 20px;
                padding: 30px 25px;
            }
            
            .welcome-title {
                font-size: 2rem;
            }
        }
    </style>
    <style>
      /* PWA Loader & Prompt */
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
    </script>
</head>

<body class="with-pwa-loader">
  <!-- PWA loading overlay (center) -->
  <div id="pwa-loader" class="pwa-loader" aria-hidden="true">
    <img src="/upload/logoweb.jpg" alt="Loading" />
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
				  <strong>Error!</strong> '.$error.'.
				  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
				</div>'; } 
				?>
	      <form action="" method="post">
		      <div class="text-center mb-4">
		          <!-- Logo EPI -->
		      	<div class="mb-3">
		      		<img src="<?= $weburl; ?>upload/logo-webb.jpg" alt="EPI Logo" class="img-fluid" style="max-height: 80px; margin-bottom: 20px;">
		      	</div>
		      	<h1 class="welcome-title">WELCOME BACK TO EPIC HUB!</h1>
                <p class="welcome-subtitle">Login to your account</p>
		      </div>
            
              <div class="mb-3">
                    <label for="staticEmail" class="form-label">Alamat Email</label>
                    <input type="email" class="form-control" name="username" placeholder="Masukkan email Anda..." value="<?= isset($_GET['email']) ? htmlspecialchars($_GET['email'], ENT_QUOTES) : '' ?>" required>
                  </div>
				  
				  <div class="mb-4">
				    <label for="inputPassword" class="form-label">Password</label>
				    <div class="password-wrapper">
					      <input type="password" id="password" class="form-control" name="password" placeholder="Masukkan password Anda..." required>
					      <span class="toggle-password" id="togglePassword" onclick="togglePassword()">
					      	<i class="fas fa-eye"></i>
					      </span>
	            </div>
				  </div>
				  
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="" id="rememberMe">
                        <label class="form-check-label" for="rememberMe">
                            Remember me
                        </label>
                    </div>
                    <a href="reset" class="text-link">Lupa Password?</a>
                </div>
                
				  <input type="submit" class="btn btn-login w-100 mb-4" value="LOGIN SEKARANG">				  
				</form>
				
	</div>
	<script src="/bootstrap-5.3.3/js/bootstrap.bundle.min.js"></script>

  


  <!-- PWA: SW registration, loader hide, and install prompt handling -->
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
  <script>
    (function(){
      var refEl = document.querySelector('input[name="kodeaff"], [aria-label*="Kode Referral"], .referral-field');
      if (refEl && refEl.closest) { var p = refEl.closest('.mb-3'); if (p) { p.remove(); } else { refEl.remove(); } }
      var txt = document.body ? (document.body.textContent || '') : '';
      window.__loginReferralCheck = { field: !!refEl, text: /Masukkan\s+Kode\s+Referral/i.test(txt) };
    })();
  </script>
</body>
</html>
