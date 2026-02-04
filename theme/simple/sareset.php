<?php 
if (!defined("IS_IN_SCRIPT")) { die(); exit(); }
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

    <title>RESET PASSWORD</title>

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
            align-items: center;
            justify-content: center;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow: hidden; /* Hide scrollbars for background animation */
        }
        
        .reset-container {
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
        
        .btn-reset {
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

        .btn-reset::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: left 0.5s;
        }

        .btn-reset:hover::before {
            left: 100%;
        }
        
        .btn-reset:hover {
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
        
        @media (max-width: 576px) {
            .reset-container {
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
</head>

<body>
    <div class="animated-background"></div>
	<div class="reset-container">
	      <?php
		  if (isset($_POST['username']) && validemail($_POST['username'])) {
		  	$datamember = db_row("SELECT * FROM `sa_member` WHERE `mem_email`='" . cek($_POST['username']) . "'");
		  	if (isset($datamember['mem_id'])) {
		  		$kode = randomword(6);
		  		db_query("UPDATE `sa_member` SET `mem_confirm`='" . $kode . "' WHERE `mem_id`=" . $datamember['mem_id']);
		  		# Kirim Email Konfirmasi
		  		$judul_email_validasi = 'Konfirmasi Reset Password';
		  		$isi_email_validasi = '<p>Seseorang ingin melakukan reset password pada akun anda di ' . $weburl . '.</p><p>Jika itu adalah anda, silahkan klik link validasi di bawah ini:</p><p><a href="' . $weburl . 'reset?confirm=' . $kode . '">' . $weburl . 'reset?confirm=' . $kode . '</a></p>';
		  		// Gunakan EmailService untuk SMTP
		  		require_once __DIR__ . '/../../EmailService.php';
		  		$emailService = new EmailService();
		  		$emailResult = $emailService->sendEmail($datamember['mem_email'], $judul_email_validasi, $isi_email_validasi);
		  		
		  		if ($emailResult['status'] === true) {
		  			echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
						  <strong>OK!</strong> Silahkan cek inbox email anda untuk konfirmasi reset password.
						  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
						</div>';
		  		} else {
		  			echo '<div class="alert alert-warning alert-dismissible fade show" role="alert">
						  <strong>Peringatan!</strong> Reset password berhasil diproses, namun ada masalah pengiriman email: ' . $emailResult['message'] . '
						  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
						</div>';
		  		}
		  	}
		  } elseif (isset($_GET['confirm']) && strlen($_GET['confirm']) == 6) {
		  	$datamember = db_row("SELECT * FROM `sa_member` WHERE `mem_confirm`='" . cek($_GET['confirm']) . "'");
		  	if (isset($datamember['mem_id'])) {
		  		$kode = randomword(8);
		  		db_query("UPDATE `sa_member` SET `mem_confirm`='',`mem_password`='" . create_hash($kode) . "' 
		  			WHERE `mem_id`=" . $datamember['mem_id']);
		  		# Kirim Email Konfirmasi
		  		$judul_email_reset = 'Password Baru Anda';
		  		$isi_email_reset = '<p>Berikut Data Login baru anda:</p><p>Email : ' . $datamember['mem_email'] . '<br/>Password : ' . $kode . '</p><p>Silahkan login ke <a href="' . $weburl . 'dashboard">' . $weburl . 'dashboard</a></p>';
		  		// Gunakan EmailService untuk SMTP
		  		require_once __DIR__ . '/../../EmailService.php';
		  		$emailService = new EmailService();
		  		$emailResult = $emailService->sendEmail($datamember['mem_email'], $judul_email_reset, $isi_email_reset);
		  		
		  		if ($emailResult['status'] === true) {
		  			echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
						  <strong>OK!</strong> Password baru telah kami kirimkan ke email anda.
						  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
						</div>';
		  		} else {
		  			echo '<div class="alert alert-warning alert-dismissible fade show" role="alert">
						  <strong>Peringatan!</strong> Password baru berhasil dibuat, namun ada masalah pengiriman email: ' . $emailResult['message'] . '
						  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
						</div>';
		  		}
		  	}
		  }
		  ?>
	      <form action="" method="post">
		      <div class="text-center mb-4">
		          <!-- Logo EPI -->
		      	<div class="mb-3">
		      		<img src="<?= $weburl; ?>upload/logo-webb.jpg" alt="EPI Logo" class="img-fluid" style="max-height: 80px; margin-bottom: 20px;">
		      	</div>
		      	<h1 class="welcome-title">Lupa Password?</h1>
                <p class="welcome-subtitle">Masukkan email Anda untuk dapatkan password baru</p>
		      </div>
		      <div class="mb-3">
			    <label for="email" class="form-label">Email Address</label>
			    <input type="email" class="form-control" name="username" id="email" placeholder="Enter your email address" required>
			  </div>
			  <input type="submit" class="btn btn-reset w-100 mb-4" value="RESET">				  
			</form>
			<div class="text-center">
				<p class="mb-0">Ingat password Anda? <a href="login" class="text-link fw-semibold">LOGIN DISINI</a></p>
			</div>
	</div>
	<script src="<?= $weburl;?>bootstrap-5.3.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>