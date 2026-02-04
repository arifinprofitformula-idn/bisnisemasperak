<?php
if (!defined('IS_IN_SCRIPT')) { define('IS_IN_SCRIPT', true); }
// Admin Settings page for EPI WhatsApp Login (consistent dashboard layout)

$settings = getsettings();
$head = array(
  'pagetitle' => 'WhatsApp Login (Settings)'
);
showheader($head);

// Akses admin saja
if (isset($datamember['mem_role']) && $datamember['mem_role'] < 9) {
  echo '<div class="alert alert-danger">Akses ditolak. Halaman ini khusus Admin.</div>';
  showfooter();
  return;
}

// Handle submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nonceOk = epi_nonce_check('whatslogin_settings', $_POST['nonce'] ?? '');
  if (!$nonceOk) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">'
       . '<strong>Error!</strong> Invalid CSRF token.'
       . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
       . '</div>';
  } else {
    $new = array();
    // Paksa hanya Dripsender
    $new['wa_provider'] = 'dripsender';
    $new['starsender_apikey'] = '';
    $new['starsender_host'] = '';
    $new['dripsender_apikey'] = trim($_POST['dripsender_apikey'] ?? '');
    $new['dripsender_host'] = trim($_POST['dripsender_host'] ?? '');
    $new['tz'] = trim($_POST['tz'] ?? 'Asia/Jakarta');

    $saved = updatesettings($new);
    if ($saved === false) {
      echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">'
         . '<strong>Error!</strong> ' . htmlspecialchars(db_error())
         . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
         . '</div>';
    } else {
      echo '<div class="alert alert-success alert-dismissible fade show" role="alert">'
         . '<strong>Ok!</strong> Setting telah disimpan.'
         . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
         . '</div>';
    }
  }
}

$starsenderKey = $settings['starsender_apikey'] ?? getenv('STARSENDER_API_KEY') ?: '';
$starsenderHost = $settings['starsender_host'] ?? getenv('STARSENDER_HOST') ?: 'https://starsender.online';
$dripsenderKey = $settings['dripsender_apikey'] ?? getenv('DRIPSENDER_API_KEY') ?: '';
$dripsenderHost = $settings['dripsender_host'] ?? getenv('DRIPSENDER_HOST') ?: 'https://api.dripsender.id';
$tz = $settings['tz'] ?? (getenv('TZ') ?: 'Asia/Jakarta');
?>

<div class="row g-3">
  <div class="col-12 col-lg-7">
    <form method="post" action="">
      <div class="card">
        <div class="card-header">
          Konfigurasi WhatsApp Login
        </div>
        <div class="card-body">
          <div class="mb-3 row">
            <label class="col-sm-3 col-form-label">Provider</label>
            <div class="col-sm-9">
              <input type="text" class="form-control" value="Dripsender" disabled aria-label="Provider" />
              <input type="hidden" name="wa_provider" value="dripsender" />
              <input type="hidden" name="starsender_apikey" value="" />
              <input type="hidden" name="starsender_host" value="" />
            </div>
          </div>
          <div class="mb-3 row">
            <label class="col-sm-3 col-form-label">Dripsender API Key</label>
            <div class="col-sm-9">
              <input type="text" name="dripsender_apikey" value="<?= htmlspecialchars($dripsenderKey); ?>" class="form-control" placeholder="{{DRIPSENDER_API_KEY}}" />
            </div>
          </div>
          <div class="mb-3 row">
            <label class="col-sm-3 col-form-label">Dripsender Host</label>
            <div class="col-sm-9">
              <input type="text" name="dripsender_host" value="<?= htmlspecialchars($dripsenderHost); ?>" class="form-control" placeholder="https://api.dripsender.id" />
            </div>
          </div>
          <div class="mb-3 row">
            <label class="col-sm-3 col-form-label">Timezone</label>
            <div class="col-sm-6">
              <input type="text" name="tz" value="<?= htmlspecialchars($tz); ?>" class="form-control" placeholder="Asia/Jakarta" />
              <small class="text-muted">Digunakan untuk timestamp OTP.</small>
            </div>
          </div>
          <input type="hidden" name="nonce" value="<?= htmlspecialchars(epi_nonce_create('whatslogin_settings')); ?>" />
        </div>
        <div class="card-footer text-end">
          <button class="btn btn-primary" type="submit">Simpan</button>
        </div>
      </div>
    </form>
  </div>

  <div class="col-12 col-lg-5">
    <div class="card">
      <div class="card-header">Test Kirim OTP</div>
      <div class="card-body">
        <form method="post" action="<?= $weburl; ?>whatsapp-login" class="row g-2" aria-label="Form uji kirim OTP">
          <div class="col-12">
            <div class="input-group">
              <span class="input-group-text">+62</span>
              <input type="text" name="phone" class="form-control" placeholder="812xxxxxxx" required aria-label="Nomor WA tanpa 0" />
            </div>
          </div>
          <input type="hidden" name="action" value="otp_request" />
          <input type="hidden" name="nonce" value="<?= htmlspecialchars(epi_nonce_create('otp_request')); ?>" />
          <div class="col-12 text-end">
            <button class="btn btn-success" type="submit">Kirim OTP</button>
          </div>
        </form>
        <p class="mt-3 text-muted">Env fallback: DRIPSENDER_API_KEY, DRIPSENDER_HOST, TZ.</p>
      </div>
    </div>
  </div>
</div>

<?php showfooter(); ?>