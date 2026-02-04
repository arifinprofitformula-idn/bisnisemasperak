<?php
if (!defined('IS_IN_SCRIPT')) { define('IS_IN_SCRIPT', true); }
// Pastikan index.php plugin dimuat agar auto-install tabel berjalan
require_once __DIR__ . '/index.php';
// Ensure OTP tables exist (idempotent guard)
if (function_exists('epi_whatsapp_login_health') && function_exists('epi_whatsapp_login_install')) {
  if (!epi_whatsapp_login_health()) { epi_whatsapp_login_install(); }
}

// Generate nonce hanya untuk GET; POST akan memverifikasi dulu baru regenerate
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  $nonce_request = epi_nonce_create('otp_request');
  $nonce_verify  = epi_nonce_create('otp_verify');
}

$step = $_GET['step'] ?? 'request';

if ($_SERVER['REQUEST_METHOD']==='POST'){
  if (isset($_POST['action']) && $_POST['action']==='otp_request'){
    if (!epi_nonce_check('otp_request', $_POST['nonce'] ?? '')){ $error='Invalid nonce'; }
    elseif (!epi_rate_limit_check('otp_request')){ $error='Rate limit exceeded'; }
    else {
      // Ensure tables before insert
      if (function_exists('epi_whatsapp_login_health') && function_exists('epi_whatsapp_login_install')) {
        if (!epi_whatsapp_login_health()) { epi_whatsapp_login_install(); }
      }
      // Normalisasi nomor: hilangkan non-digit + prefix 62/0
      $phone = epi_normalize_phone($_POST['phone'] ?? '');
      if (strlen($phone) < 8){ $error='Nomor tidak valid'; }
      else {
        $requestId = epi_generate_request_id();
        $otp = str_pad(strval(rand(0,999999)),6,'0',STR_PAD_LEFT);
        $hash = sha1(SECRET.$otp);
        $now = date('Y-m-d H:i:s');
        $exp = date('Y-m-d H:i:s', time()+300);
        db_query("INSERT INTO `epi_login_otp` (`request_id`,`phone`,`otp_code_hash`,`status`,`attempts`,`ip_address`,`user_agent`,`created_at`,`expires_at`) VALUES ('".cek($requestId)."','".cek($phone)."','".cek($hash)."','pending',0,'".cek($_SERVER['REMOTE_ADDR'] ?? '')."','".cek($_SERVER['HTTP_USER_AGENT'] ?? '')."','".cek($now)."','".cek($exp)."')");
        epi_log($requestId,'create',epi_mask_phone($phone),'pending','');
        $msg = "🔑 Kode OTP Anda: ".$otp." (berlaku 5 menit).\n🚫 Jangan bagikan kode ini. Jika tidak meminta, abaikan.\n\nAdmin Arva dari BisnisEmasPerak";
        $send = epi_gateway_send_wa($msg, $phone);
        if ($send['ok']){
          header('Location: '.$weburl.'whatsapp-login?step=verify&request_id='.$requestId);
          exit;
        } else {
          $provider = getsettings('wa_provider') ?: 'starsender';
          $error = 'Gagal mengirim OTP (provider: '.htmlspecialchars($provider).', status: '.htmlspecialchars($send['status'] ?? 'N/A').'). Periksa API key/host dan nomor.';
        }
      }
    }
  }

  if (isset($_POST['action']) && $_POST['action']==='otp_verify'){
    if (!epi_nonce_check('otp_verify', $_POST['nonce'] ?? '')){ $error='Invalid nonce'; }
    elseif (!epi_rate_limit_check('otp_verify')){ $error='Rate limit exceeded'; }
    else {
      $requestId = $_POST['request_id'] ?? '';
      $otp = preg_replace('/\D+/', '', $_POST['otp'] ?? '');
      $row = db_row("SELECT * FROM `epi_login_otp` WHERE `request_id`='".cek($requestId)."'");
      if (!$row){ $error = 'Request tidak ditemukan'; }
      elseif ($row['status']!='pending'){ $error='Request tidak berlaku'; }
      elseif (strtotime($row['expires_at']) < time()){ $error='Kode kadaluarsa'; }
      else {
        $hash = sha1(SECRET.$otp);
        if (hash_equals($row['otp_code_hash'], $hash)){
          // sukses → buat auth cookie same as salogin
          $pn = $row['phone'];
          $c1 = cek($pn);
          $c2 = cek('0'.$pn);
          $c3 = cek('62'.$pn);
          $c4 = cek('+62'.$pn);
          $member = db_row("SELECT * FROM `sa_member` WHERE `mem_whatsapp` IN ('".$c1."','".$c2."','".$c3."','".$c4."')");
          if (!$member){ $error = 'Nomor WA tidak terdaftar'; }
          else {
            $id = $member['mem_id'];
            $hash2 = sha1(rand(0,500).microtime().SECRET);
            $signature = sha1(SECRET . $hash2 . $id);
            $cookie = base64_encode($signature . "-" . $hash2 . "-" . $id);
            setcookie('authentication', $cookie,time()+36000,'/');
            db_query("UPDATE `sa_member` SET `mem_lastlogin`='".date('Y-m-d H:i:s')."' WHERE `mem_id`=".$id);
            db_query("UPDATE `epi_login_otp` SET `status`='verified' WHERE `id`=".$row['id']);
            epi_log($requestId,'verify',epi_mask_phone($pn),'verified','');
            header('Location: '.$weburl.'dashboard');
            exit;
          }
        } else {
          db_query("UPDATE `epi_login_otp` SET `attempts`=`attempts`+1 WHERE `id`=".$row['id']);
          epi_log($requestId,'verify',epi_mask_phone($row['phone']),'failed','Mismatch');
          $error='Kode OTP salah';
        }
      }
    }
  }
}
// Regenerate nonce setelah proses POST untuk render ulang form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nonce_request = epi_nonce_create('otp_request');
  $nonce_verify  = epi_nonce_create('otp_verify');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login via WhatsApp</title>
  <link href="<?= $weburl;?>bootstrap-5.3.3/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?=$weburl;?>fontawesome/css/all.css" rel="stylesheet" />
  <link href="<?=$weburl;?>fontawesome/css/brands.min.css" rel="stylesheet" />
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
    }
    .login-container {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      border-radius: 20px;
      padding: 50px 40px;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1), 0 0 0 1px rgba(255, 255, 255, 0.2);
      max-width: 640px;
      width: 100%;
      border: 1px solid rgba(218, 165, 32, 0.2);
    }
    .welcome-title { font-size: 2.5rem; font-weight: 700; color: #333; margin-bottom: 10px; }
    .welcome-subtitle { color: #666; margin-bottom: 30px; }
    .form-label { color: #333; font-weight: 600; margin-bottom: 8px; }
    .form-control { border: 2px solid #e0e0e0; border-radius: 12px; padding: 15px; font-size: 16px; transition: all 0.3s ease; background: rgba(255, 255, 255, 0.8); }
    .form-control:focus { border-color: #DAA520; box-shadow: 0 0 0 0.2rem rgba(218, 165, 32, 0.25); background: rgba(255, 255, 255, 1); }
    .divider { text-align: center; margin: 25px 0; position: relative; }
    .divider::before { content: ''; position: absolute; top: 50%; left: 0; right: 0; height: 1px; background: linear-gradient(to right, transparent, #ddd, transparent); }
    .divider span { background: rgba(255, 255, 255, 0.95); padding: 0 20px; color: #666; font-size: 14px; }

    /* Konsistensi desain tombol WhatsApp di semua step */
    .btn-whatsapp {
      background: #25D366; /* WhatsApp green */
      border: none;
      color: #fff;
      padding: 14px;
      border-radius: 12px;
      font-weight: 700;
      font-size: 16px;
      transition: all 0.2s ease;
      box-shadow: 0 4px 12px rgba(37, 211, 102, 0.35);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
    }
    .btn-whatsapp:hover { background: #1ebe57; transform: translateY(-1px); box-shadow: 0 6px 16px rgba(37, 211, 102, 0.45); color: #fff; }
    .btn-whatsapp:focus { outline: 2px solid rgba(37, 211, 102, 0.4); outline-offset: 2px; }
    .btn-whatsapp:disabled { background: #9fe3b9; box-shadow: none; cursor: not-allowed; }

    /* OTP six-input modern style */
    .otp-inputs {
      display: grid;
      grid-template-columns: repeat(6, 1fr);
      gap: 10px;
      margin-top: 6px;
    }
    .otp-input {
      height: 56px;
      width: 100%;
      text-align: center;
      font-size: 22px;
      border-radius: 12px;
      border: 2px solid #e0e0e0;
      background: rgba(255, 255, 255, 0.9);
      transition: all 0.2s ease, transform 0.1s ease;
    }
    .otp-input:focus, .otp-input.active {
      outline: none;
      border-color: #DAA520;
      box-shadow: 0 0 0 0.2rem rgba(218, 165, 32, 0.25);
      transform: translateY(-1px);
    }
    @media (max-width: 480px){
      .otp-input { height: 48px; font-size: 20px; }
    }

    @media (max-width: 576px) { .login-container { margin: 20px; padding: 30px 25px; } .welcome-title { font-size: 2rem; } }
  </style>
</head>

<body>
  <div class="login-container">
    <?php if (isset($error)) { echo '<div class="alert alert-danger alert-dismissible fade show" role="alert"><strong>Error!</strong> '.htmlspecialchars($error).'.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>'; } ?>
    <div class="text-center mb-4">
      <div class="mb-3"><img src="<?= $weburl; ?>upload/logo-webb.jpg" alt="EPI Logo" class="img-fluid" style="max-height: 80px; margin-bottom: 20px;"></div>
      <h1 class="welcome-title"><i class="fab fa-whatsapp text-success"></i> Login via WhatsApp</h1>
      <p class="welcome-subtitle">Masukkan nomor WhatsApp untuk menerima OTP</p>
    </div>
    <?php if ($step==='request') { ?>
      <form method="post">
        <input type="hidden" name="action" value="otp_request" />
        <input type="hidden" name="nonce" value="<?= htmlspecialchars($nonce_request); ?>" />
        <div class="mb-3">
          <label class="form-label">Nomor WhatsApp (Hanya nomor tanpa karakter lainnya)</label>
          <div class="input-group">
            <span class="input-group-text">+62</span>
            <input type="text" name="phone" class="form-control" placeholder="812xxxxxxx" required />
          </div>
        </div>
        <button class="btn-whatsapp w-100" type="submit" aria-label="Kirim OTP" data-loading-text="Mengirim...">
          <i class="fab fa-whatsapp" aria-hidden="true"></i>
          <span class="btn-text">Kirim OTP</span>
          <span class="spinner-border spinner-border-sm text-light d-none" role="status" aria-hidden="true"></span>
        </button>
      </form>
    <?php } else { ?>
      <form method="post" id="otpVerifyForm">
        <input type="hidden" name="action" value="otp_verify" />
        <input type="hidden" name="nonce" value="<?= htmlspecialchars($nonce_verify); ?>" />
        <input type="hidden" name="request_id" value="<?= htmlspecialchars($_GET['request_id'] ?? ''); ?>" />
        <!-- Hidden single-field OTP to keep backend compatibility -->
        <input type="hidden" name="otp" id="otpHiddenInput" value="" />

        <div class="mb-3">
          <label class="form-label">Kode OTP</label>
          <div class="otp-inputs" role="group" aria-label="Masukkan 6 digit kode OTP">
            <input class="otp-input" type="tel" inputmode="numeric" pattern="[0-9]*" maxlength="1" autocomplete="one-time-code" aria-label="Digit 1" />
            <input class="otp-input" type="tel" inputmode="numeric" pattern="[0-9]*" maxlength="1" autocomplete="one-time-code" aria-label="Digit 2" />
            <input class="otp-input" type="tel" inputmode="numeric" pattern="[0-9]*" maxlength="1" autocomplete="one-time-code" aria-label="Digit 3" />
            <input class="otp-input" type="tel" inputmode="numeric" pattern="[0-9]*" maxlength="1" autocomplete="one-time-code" aria-label="Digit 4" />
            <input class="otp-input" type="tel" inputmode="numeric" pattern="[0-9]*" maxlength="1" autocomplete="one-time-code" aria-label="Digit 5" />
            <input class="otp-input" type="tel" inputmode="numeric" pattern="[0-9]*" maxlength="1" autocomplete="one-time-code" aria-label="Digit 6" />
          </div>
          <small class="text-muted d-block mt-2">Masukkan 6 digit kode yang dikirim via WhatsApp</small>
        </div>

        <button class="btn-whatsapp w-100" type="submit" aria-label="Verifikasi OTP" data-loading-text="Memverifikasi...">
          <i class="fab fa-whatsapp" aria-hidden="true"></i>
          <span class="btn-text">Verifikasi</span>
          <span class="spinner-border spinner-border-sm text-light d-none" role="status" aria-hidden="true"></span>
        </button>

        <div class="mt-3 text-center">
          <button type="button" id="resendBtn" class="btn btn-link text-decoration-none" disabled aria-disabled="true" style="opacity:0.7;">Kirim Ulang Kode (60s)</button>
        </div>
      </form>
    <?php } ?>
  </div>
  <script src="<?= $weburl;?>bootstrap-5.3.3/js/bootstrap.bundle.min.js"></script>
  <script>
    // Disable tombol saat submit + tampilkan spinner untuk konsistensi UX
    document.addEventListener('DOMContentLoaded', function(){
      document.querySelectorAll('form').forEach(function(form){
        form.addEventListener('submit', function(){
          var btn = form.querySelector('.btn-whatsapp');
          if (!btn) return;
          btn.disabled = true;
          btn.setAttribute('aria-busy','true');
          var textSpan = btn.querySelector('.btn-text');
          if (textSpan) { textSpan.textContent = btn.dataset.loadingText || 'Memproses...'; }
          var spinner = btn.querySelector('.spinner-border');
          if (spinner) { spinner.classList.remove('d-none'); }
        });
      });
    });

    // OTP inputs behavior (6 separate inputs → single string)
    document.addEventListener('DOMContentLoaded', function(){
      var otpContainer = document.querySelector('.otp-inputs');
      if (!otpContainer) return; // hanya di step verify

      var inputs = Array.prototype.slice.call(otpContainer.querySelectorAll('.otp-input'));
      var hiddenInput = document.getElementById('otpHiddenInput');
      var form = document.getElementById('otpVerifyForm');

      // Focus ke input pertama saat load
      if (inputs.length) { inputs[0].focus(); }

      // Helper untuk join nilai dan validasi lengkap
      function getOtpString(){ return inputs.map(function(i){ return (i.value || '').replace(/\D+/g,''); }).join(''); }
      function isComplete(){ return inputs.every(function(i){ return i.value && i.value.length === 1; }); }

      // Update hidden input & auto-submit jika lengkap
      function updateStateAndMaybeSubmit(){
        hiddenInput.value = getOtpString();
        if (isComplete() && hiddenInput.value.length === 6) {
          // Trigger submit otomatis
          form.requestSubmit();
        }
      }

      // Per-input behavior
      inputs.forEach(function(input, idx){
        input.addEventListener('input', function(e){
          // Hanya digit
          var v = input.value.replace(/\D+/g,'');
          // Jika paste ke satu kotak, distribusikan
          if (v.length > 1){
            var digits = v.substring(0,6).split('');
            for (var j=0; j<digits.length && (idx + j) < inputs.length; j++){
              inputs[idx + j].value = digits[j];
            }
            var nextIndex = Math.min(idx + digits.length, inputs.length-1);
            inputs[nextIndex].focus();
          } else {
            input.value = v.substring(0,1);
            // Auto-advance
            if (input.value && idx < inputs.length - 1){ inputs[idx+1].focus(); }
          }
          updateStateAndMaybeSubmit();
        });

        input.addEventListener('keydown', function(e){
          var key = e.key;
          // Navigasi
          if (key === 'ArrowLeft' && idx > 0){ e.preventDefault(); inputs[idx-1].focus(); }
          if (key === 'ArrowRight' && idx < inputs.length-1){ e.preventDefault(); inputs[idx+1].focus(); }
          // Backspace: kalau kosong, pindah ke sebelumnya
          if (key === 'Backspace' && !input.value && idx > 0){ inputs[idx-1].focus(); inputs[idx-1].value=''; e.preventDefault(); updateStateAndMaybeSubmit(); }
        });

        input.addEventListener('focus', function(){ input.classList.add('active'); });
        input.addEventListener('blur', function(){ input.classList.remove('active'); });
      });

      // Paste seluruh kode ke container
      otpContainer.addEventListener('paste', function(e){
        var data = (e.clipboardData || window.clipboardData).getData('text');
        var digits = (data || '').replace(/\D+/g,'').substring(0,6).split('');
        if (digits.length){
          e.preventDefault();
          inputs.forEach(function(i){ i.value=''; });
          for (var k=0; k<digits.length && k<inputs.length; k++){ inputs[k].value = digits[k]; }
          inputs[Math.min(digits.length, inputs.length-1)].focus();
          updateStateAndMaybeSubmit();
        }
      });

      // Resend button countdown
      var resendBtn = document.getElementById('resendBtn');
      var remaining = 60; // detik
      if (resendBtn){
        var interval = setInterval(function(){
          remaining--;
          if (remaining <= 0){
            clearInterval(interval);
            resendBtn.textContent = 'Kirim Ulang Kode';
            resendBtn.disabled = false;
            resendBtn.setAttribute('aria-disabled','false');
            resendBtn.style.opacity = '1';
          } else {
            resendBtn.textContent = 'Kirim Ulang Kode ('+remaining+'s)';
          }
        }, 1000);
        resendBtn.addEventListener('click', function(){
          // Redirect ke step request untuk mengirim ulang OTP
          window.location.href = '<?= $weburl; ?>whatsapp-login';
        });
      }
    });
  </script>
</body>
</html>