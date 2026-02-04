<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
if (empty($_SESSION['epi_nonce'])) { $_SESSION['epi_nonce'] = sha1(SECRET . microtime(true) . rand(1000,9999)); }
$orderId = (isset($slug[2]) && is_numeric($slug[2])) ? (int)$slug[2] : 0;
$order = $orderId ? db_row("SELECT * FROM `sa_order` 
  LEFT JOIN `sa_member` ON `sa_member`.`mem_id` = `sa_order`.`order_idmember`
  LEFT JOIN `sa_page` ON `sa_page`.`page_id` = `sa_order`.`order_idproduk`
  WHERE `sa_order`.`order_id`=".$orderId) : false;
$viewerId = isset($datamember['mem_id']) ? (int)$datamember['mem_id'] : 0;
$viewerRole = isset($datamember['mem_role']) ? (int)$datamember['mem_role'] : 0;
$isLoggedIn = ($viewerId > 0);
$canAccess = true;
if ($isLoggedIn && $viewerRole < 5 && $order) {
  $canAccess = ((int)$order['order_idmember'] === $viewerId);
}
$settings = getsettings();
if (!db_var("SHOW TABLES LIKE 'epi_payment_confirm'")) {
  db_query("CREATE TABLE IF NOT EXISTS `epi_payment_confirm` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `invoice_no` VARCHAR(32) NOT NULL,
    `atas_nama` VARCHAR(100) NOT NULL,
    `bank_code` VARCHAR(32) NULL,
    `bank_label` VARCHAR(100) NULL,
    `bank_account` VARCHAR(50) NULL,
    `bank_owner` VARCHAR(100) NULL,
    `transfer_date` DATE NULL,
    `nominal` INT NULL,
    `nominal_expected` INT NULL,
    `file_path` VARCHAR(255) NULL,
    `file_name` VARCHAR(200) NULL,
    `file_size` INT NULL,
    `file_type` VARCHAR(64) NULL,
    `status` TINYINT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL,
    `created_ip` VARCHAR(64) NULL,
    `user_agent` VARCHAR(255) NULL,
    KEY(`order_id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
if (!db_var("SHOW TABLES LIKE 'epi_admin_finance_log'")) {
  db_query("CREATE TABLE IF NOT EXISTS `epi_admin_finance_log` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `action` VARCHAR(64) NULL,
    `admin_wa` VARCHAR(20) NULL,
    `order_id` INT NULL,
    `changed_by` INT NULL,
    `old_value` VARCHAR(20) NULL,
    `new_value` VARCHAR(20) NULL,
    `info` VARCHAR(255) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `ip` VARCHAR(64) NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
// Ensure columns for admin verification and appeal exist
if (!db_var("SHOW COLUMNS FROM `epi_payment_confirm` LIKE 'verified_by'")) { db_query("ALTER TABLE `epi_payment_confirm` ADD `verified_by` INT NULL"); }
if (!db_var("SHOW COLUMNS FROM `epi_payment_confirm` LIKE 'verified_note'")) { db_query("ALTER TABLE `epi_payment_confirm` ADD `verified_note` VARCHAR(255) NULL"); }
if (!db_var("SHOW COLUMNS FROM `epi_payment_confirm` LIKE 'appeal_msg'")) { db_query("ALTER TABLE `epi_payment_confirm` ADD `appeal_msg` VARCHAR(255) NULL"); }
if (!db_var("SHOW COLUMNS FROM `epi_payment_confirm` LIKE 'appeal_at'")) { db_query("ALTER TABLE `epi_payment_confirm` ADD `appeal_at` DATETIME NULL"); }
if (!db_var("SHOW TABLES LIKE 'epi_payment_confirm_log'")) {
  db_query("CREATE TABLE IF NOT EXISTS `epi_payment_confirm_log` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `confirm_id` INT NULL,
    `order_id` INT NULL,
    `action` VARCHAR(32) NULL,
    `message` VARCHAR(255) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `ip` VARCHAR(64) NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
$banks = isset($settings['bank_accounts']) ? json_decode($settings['bank_accounts'], true) : [];
$hargaProduk = ($order && isset($order['order_harga']) && is_numeric($order['order_harga'])) ? (int)$order['order_harga'] : (($order && isset($order['pro_harga']) && is_numeric($order['pro_harga'])) ? (int)$order['pro_harga'] : 0);
$couponCode = $order ? trim((string)($order['order_promo_code'] ?? '')) : '';
$hargaPromoBase = ($order && isset($order['pro_harga_display']) && is_numeric($order['pro_harga_display'])) ? (int)$order['pro_harga_display'] : $hargaProduk;
$storedDisplay = ($order && isset($order['order_price_display']) && is_numeric($order['order_price_display'])) ? (int)$order['order_price_display'] : null;
$diskonPromo = max(0, $hargaProduk - $hargaPromoBase);
$diskonKuponStored = ($order && isset($order['order_discount']) && is_numeric($order['order_discount'])) ? (int)$order['order_discount'] : null;
$diskonKupon = (!empty($couponCode)) ? (($diskonKuponStored !== null) ? $diskonKuponStored : 0) : 0;
if ($diskonKupon === 0 && !empty($couponCode) && $diskonKuponStored === null && $storedDisplay !== null) {
  $derived = ($hargaProduk > $storedDisplay) ? ($hargaProduk - $storedDisplay) : 0;
  $diskonKupon = max(0, $derived - $diskonPromo);
}
if ($diskonKupon === 0 && !empty($couponCode)) {
  $eff = epi_effective_price((int)$hargaProduk, (int)$hargaPromoBase, $couponCode, (int)($order['order_idproduk'] ?? 0), 1);
  if (isset($eff['discount']) && is_numeric($eff['discount'])) { $diskonKupon = (int)$eff['discount']; }
}
$hargaTampil = max(0, (int)$hargaPromoBase - (int)$diskonKupon);
$kodeUnikMode = isset($settings['kodeunik']) ? (int)$settings['kodeunik'] : 0;
$hasUnik = ($kodeUnikMode !== 0) && $order && isset($order['order_hargaunik']) && is_numeric($order['order_hargaunik']);
$uniqNominal = $hasUnik ? abs((int)$order['order_hargaunik'] - (int)$hargaTampil) : 0;
$uniqNominal = min(999, max(0, (int)$uniqNominal));
if ($hasUnik) {
  if ($kodeUnikMode === 1) { $totalBayar = max(0, (int)$hargaTampil - $uniqNominal); }
  elseif ($kodeUnikMode === 2) { $totalBayar = (int)$hargaTampil + $uniqNominal; }
  else { $totalBayar = (int)$hargaTampil; }
} else { $totalBayar = (int)$hargaTampil; }
$errMsg = '';
$okMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!$order || !$canAccess) {
    $errMsg = !$order ? 'Invoice tidak ditemukan.' : 'Akses ditolak.';
    goto end_post_process;
  }
  $csrfOk = (isset($_POST['csrf']) && isset($_SESSION['epi_nonce']) && hash_equals((string)$_SESSION['epi_nonce'], (string)$_POST['csrf']));
  if (!$csrfOk) { $errMsg = 'Sesi tidak valid.'; }
  else {
    if (isset($_POST['action']) && $_POST['action']==='appeal') {
      $msg = isset($_POST['appeal_msg']) ? trim($_POST['appeal_msg']) : '';
      if (strlen($msg) < 5) { $errMsg = 'Mohon jelaskan alasan banding Anda.'; }
      else {
        $last = db_row("SELECT * FROM `epi_payment_confirm` WHERE `order_id`=".$orderId." ORDER BY `id` DESC LIMIT 1");
        if ($last && (int)$last['status'] === -1) {
          $ok = db_query("UPDATE `epi_payment_confirm` SET `appeal_msg`='".cek($msg)."',`appeal_at`='".date('Y-m-d H:i:s')."' WHERE `id`=".(int)$last['id']);
          if ($ok === false) { $errMsg = 'Gagal menyimpan banding.'; }
          else {
            db_query("INSERT INTO `epi_payment_confirm_log` (`confirm_id`,`order_id`,`action`,`message`,`ip`) VALUES (".(int)$last['id'].",".$orderId.",'appeal_request','".cek($msg)."','".cek(realIP())."')");
            // Notify admin tentang banding
            $sp = db_row("SELECT `sa_member`.`mem_whatsapp`,`sa_member`.`mem_nama` FROM `sa_sponsor` LEFT JOIN `sa_member` ON `sa_member`.`mem_id`=`sa_sponsor`.`sp_sponsor_id` WHERE `sa_sponsor`.`sp_mem_id`=".(int)($order['order_idmember'] ?? 0));
            $waAdmin = '';
            if (isset($settings['wa_admin']) && !empty($settings['wa_admin'])) { $waAdmin = formatwa($settings['wa_admin']); }
            elseif (isset($settings['whatsapp']) && !empty($settings['whatsapp'])) { $waAdmin = formatwa($settings['whatsapp']); }
            elseif (isset($sp['mem_whatsapp'])) { $waAdmin = formatwa($sp['mem_whatsapp']); }
            if (!empty($waAdmin)) {
              $msgAdmin = 'Banding verifikasi untuk #'.(string)$orderId.' oleh '.(string)($order['mem_nama'] ?? '').":\n".(string)$msg;
              @kirimwa($waAdmin, $msgAdmin);
            }
            $okMsg = 'Banding Anda telah dikirim. Tim admin akan meninjau kembali.';
          }
        } else { $errMsg = 'Tidak ada verifikasi yang ditolak untuk diajukan banding.'; }
      }
      // Skip proses konfirmasi normal ketika action=appeal
      goto end_post_process;
    }
    $inv = isset($_POST['invoice']) ? trim($_POST['invoice']) : '';
    $atasNama = isset($_POST['atas_nama']) ? preg_replace('/[^A-Za-z\s]/','', $_POST['atas_nama']) : '';
    $bankCode = isset($_POST['bank_code']) ? trim($_POST['bank_code']) : '';
    $bankLabel = isset($_POST['bank_label']) ? trim($_POST['bank_label']) : '';
    $bankAccount = isset($_POST['bank_account']) ? preg_replace('/[^0-9]/','', $_POST['bank_account']) : '';
    $bankOwner = isset($_POST['bank_owner']) ? preg_replace('/[^A-Za-z\s]/','', $_POST['bank_owner']) : '';
    $tgl = isset($_POST['transfer_date']) ? trim($_POST['transfer_date']) : '';
    $nominal = isset($_POST['nominal']) ? (int)preg_replace('/[^0-9]/','', $_POST['nominal']) : 0;
    $expected = $totalBayar;
    $minDate = date('Y-m-d', strtotime('-30 days'));
    $maxDate = date('Y-m-d');
    $validInv = ($order && (string)$order['order_id'] === (string)$inv);
    $validNama = (bool)preg_match('/^[A-Za-z\s]{3,}$/', $atasNama);
    $validTanggal = ($tgl >= $minDate && $tgl <= $maxDate);
    $tol = 0.10;
    $minNom = (int)floor($expected * (1 - $tol));
    $maxNom = (int)ceil($expected * (1 + $tol));
    $validNominal = ($nominal >= $minNom && $nominal <= $maxNom);
    $file = $_FILES['bukti'] ?? null;
    $fileOk = false;
    $savedPath = '';
    $savedName = '';
    $savedSize = 0;
    $savedType = '';
    if ($file && isset($file['error']) && (int)$file['error'] === 0) {
      $nameLower = strtolower((string)$file['name']);
      $ext = pathinfo($nameLower, PATHINFO_EXTENSION);
      $mime = strtolower((string)$file['type']);
      $allowedExt = ['jpg','jpeg','png','pdf'];
      $allowedMime = ['image/jpeg','image/png','application/pdf'];
      $typeOk = in_array($ext,$allowedExt) || in_array($mime,$allowedMime);
      $sizeOk = ((int)$file['size'] <= 1024*1024);
      if ($typeOk && $sizeOk) {
        $dir = __DIR__.'/../../upload/transfer'; if (!is_dir($dir)) { @mkdir($dir,0777,true); }
        $newName = 'confirm_'.$orderId.'_'.time().'.'.$ext;
        $dest = $dir.'/'.$newName;
        $saved = false;
        if (is_uploaded_file($file['tmp_name'])) {
          if (move_uploaded_file($file['tmp_name'],$dest)) { $saved = true; }
          else { $data = @file_get_contents($file['tmp_name']); if ($data !== false && @file_put_contents($dest, $data) !== false) { $saved = true; } }
        }
        if ($saved) { $fileOk = true; $savedPath = 'upload/transfer/'.$newName; $savedName = (string)$file['name']; $savedSize = (int)$file['size']; $savedType = $mime ?: $ext; }
      }
    }
    if (!$validInv) { $errMsg = 'Nomor invoice tidak valid.'; }
    elseif (!$validNama) { $errMsg = 'Nama rekening tidak valid.'; }
    elseif (!$validTanggal) { $errMsg = 'Tanggal transfer tidak valid.'; }
    elseif (!$validNominal) { $errMsg = 'Nominal transfer di luar toleransi.'; }
    elseif (!$fileOk) { $errMsg = 'Bukti transfer tidak valid.'; }
    else {
      $dup = db_row("SELECT * FROM `epi_payment_confirm` WHERE `order_id`=".$orderId." ORDER BY `id` DESC LIMIT 1");
      if ($dup && strtotime((string)$dup['created_at']) > time()-300) { $errMsg = 'Konfirmasi sudah terkirim. Tunggu beberapa saat.'; }
      else {
        $ip = realIP();
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $q = "INSERT INTO `epi_payment_confirm` (`order_id`,`invoice_no`,`atas_nama`,`bank_code`,`bank_label`,`bank_account`,`bank_owner`,`transfer_date`,`nominal`,`nominal_expected`,`file_path`,`file_name`,`file_size`,`file_type`,`status`,`created_ip`,`user_agent`) VALUES (
          ".$orderId.", '".cek($inv)."', '".cek($atasNama)."', '".cek($bankCode)."', '".cek($bankLabel)."', '".cek($bankAccount)."', '".cek($bankOwner)."', '".cek($tgl)."', ".$nominal.", ".$expected.", '".cek($savedPath)."', '".cek($savedName)."', ".$savedSize.", '".cek($savedType)."', 0, '".cek($ip)."', '".cek($ua)."')";
        $res = db_query($q);
        if ($res === false) { $errMsg = 'Gagal menyimpan konfirmasi.'; }
        else {
          $cid = db_insert("SELECT LAST_INSERT_ID()");
          db_query("INSERT INTO `epi_payment_confirm_log` (`confirm_id`,`order_id`,`action`,`message`,`ip`) VALUES (".(int)$cid.",".$orderId.",'submit','konfirmasi_diterima','".cek($ip)."')");
          // WhatsApp notify Admin when new verification request arrives
          $sp = db_row("SELECT `sa_member`.`mem_whatsapp`,`sa_member`.`mem_nama` FROM `sa_sponsor` LEFT JOIN `sa_member` ON `sa_member`.`mem_id`=`sa_sponsor`.`sp_sponsor_id` WHERE `sa_sponsor`.`sp_mem_id`=".(int)($order['order_idmember'] ?? 0));
          $waAdmin = '';
          if (isset($settings['wa_admin']) && !empty($settings['wa_admin'])) { $waAdmin = formatwa($settings['wa_admin']); }
          elseif (isset($settings['whatsapp']) && !empty($settings['whatsapp'])) { $waAdmin = formatwa($settings['whatsapp']); }
          elseif (isset($sp['mem_whatsapp'])) { $waAdmin = formatwa($sp['mem_whatsapp']); }
          if (!empty($waAdmin) && strlen(preg_replace('/[^0-9]/','',$waAdmin))>=10 && strlen(preg_replace('/[^0-9]/','',$waAdmin))<=15) {
            $bankLine = trim($bankLabel.' - '.$bankOwner.' - '.$bankAccount);
            $tpl = isset($settings['wa_confirm_new_admin']) ? (string)$settings['wa_confirm_new_admin'] : '';
            if ($tpl !== '') {
              $msgAdmin = $tpl;
              $msgAdmin = str_replace('[idorder]', (string)$orderId, $msgAdmin);
              $msgAdmin = str_replace('[atasnama]', (string)$atasNama, $msgAdmin);
              $msgAdmin = str_replace('[nominal]', 'Rp '.number_format($nominal), $msgAdmin);
              $msgAdmin = str_replace('[banklabel]', (string)$bankLabel, $msgAdmin);
              $msgAdmin = str_replace('[bankowner]', (string)$bankOwner, $msgAdmin);
              $msgAdmin = str_replace('[bankaccount]', (string)$bankAccount, $msgAdmin);
              $msgAdmin = str_replace('[review_url]', rtrim($weburl,'/').'/dashboard/orderlist?cari='.(string)$orderId, $msgAdmin);
            } else {
              $msgAdmin = 'Konfirmasi pembayaran baru #'.(string)$orderId.' oleh '.(string)($order['mem_nama'] ?? '')."\n".'Nominal: Rp '.number_format($nominal)."\n".'Tujuan: '.$bankLine."\n".'Review: '.rtrim($weburl,'/').'/dashboard/orderlist?cari='.(string)$orderId;
            }
            $ret = @kirimwa($waAdmin, $msgAdmin);
            db_query("INSERT INTO `epi_admin_finance_log` (`action`,`admin_wa`,`order_id`,`info`,`ip`) VALUES ('notify_confirm_new','".cek($waAdmin)."',".$orderId.",'".cek(substr((string)$ret,0,230))."','".cek(realIP())."')");
          }
          $okMsg = 'Konfirmasi pembayaran berhasil dikirim.';
        }
      }
    }
  }
}
end_post_process:
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH'])==='xmlhttprequest');
  if ($isAjax) {
    header('Content-Type: application/json');
    if (!empty($errMsg)) { echo json_encode(['ok'=>false,'message'=>$errMsg]); } else { echo json_encode(['ok'=>true,'message'=>$okMsg]); }
    exit;
  }
}
?><!DOCTYPE html>
<html class="full" lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Konfirmasi Pembayaran <?= $order ? '#'.str_pad($order['order_id'],4,0,STR_PAD_LEFT) : '' ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= $weburl; ?>fontawesome/css/fontawesome.min.css" rel="stylesheet" />
  <link href="<?= $weburl; ?>fontawesome/css/regular.min.css" rel="stylesheet" />
</head>
<body class="invoice">
<div class="container p-md-3 mt-3 mb-3">
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <div>
        <h1 class="h1 m-0">Konfirmasi Pembayaran</h1>
        <div class="small text-muted">Lengkapi data untuk verifikasi pembayaran</div>
      </div>
      <div>
        <a href="<?= $weburl; ?>dashboard" class="btn btn-outline-secondary btn-sm">Kembali ke Dashboard</a>
      </div>
    </div>
    <div class="card-body">
      <?php if (!$order) : ?>
        <div class="alert alert-danger">Invoice tidak ditemukan.</div>
      <?php elseif (!$canAccess) : ?>
        <div class="alert alert-danger">Akses ditolak.</div>
      <?php else: ?>
        <?php if (!empty($errMsg)) : ?><div class="alert alert-danger" id="alertErr"><?= htmlspecialchars($errMsg); ?></div><?php endif; ?>
        <?php if (!empty($okMsg)) : ?><div class="alert alert-success" id="alertOk"><?= htmlspecialchars($okMsg); ?></div><?php endif; ?>
        <div class="row">
          <div class="col-md-6 p-md-4">
            <div class="mb-2"><img src="<?= $weburl.'upload/logo-webb.jpg'; ?>" alt="Logo" style="height:60px; max-width:100%;"></div>
            <div class="summary-box">
              <div class="summary-row"><div>Invoice</div><div class="fw-semibold">#<?= str_pad($order['order_id'],4,0,STR_PAD_LEFT); ?></div></div>
              <div class="summary-row"><div>Produk</div><div class="fw-semibold"><?= htmlspecialchars($order['page_judul']); ?></div></div>
              <div class="summary-row"><div>Nominal Seharusnya</div><div id="expectedNom">Rp <?= number_format($totalBayar); ?></div></div>
            </div>
          </div>
          <div class="col-md-6 p-md-4">
            <?php if (empty($okMsg)) : ?>
            <form action="" method="post" enctype="multipart/form-data" id="confirmForm" novalidate>
              <input type="hidden" name="csrf" value="<?= htmlspecialchars((string)$_SESSION['epi_nonce'], ENT_QUOTES); ?>">
              <div class="mb-3">
                <label class="form-label">Nomor Invoice *</label>
                <input type="text" class="form-control" name="invoice" id="invoice" value="<?= htmlspecialchars((string)$order['order_id']); ?>" readonly>
                <div class="form-text">Diisi otomatis dari halaman invoice</div>
              </div>
              <div class="mb-3">
                <label class="form-label">Atas Nama Rekening *</label>
                <input type="text" class="form-control" name="atas_nama" id="atas_nama" placeholder="Pemilik Rekening" required>
                <div class="invalid-feedback">Nama hanya huruf dan spasi.</div>
              </div>
              <div class="mb-3">
                <label class="form-label">Transfer ke *</label>
                <?php if (is_array($banks) && count($banks) > 0) : $first = $banks[0]; ?>
                  <select class="form-select" name="bank_code" id="bank_code" required>
                    <?php foreach($banks as $b): $code = strtolower($b['code'] ?? ''); ?>
                      <option value="<?= htmlspecialchars($code); ?>" data-label="<?= htmlspecialchars($b['label'] ?? ''); ?>" data-account="<?= htmlspecialchars($b['account'] ?? ''); ?>" data-owner="<?= htmlspecialchars($b['owner'] ?? ''); ?>"><?= htmlspecialchars(($b['label'] ?? '').' - '.($b['owner'] ?? '').' - '.($b['account'] ?? '')); ?></option>
                    <?php endforeach; ?>
                  </select>
                  <input type="hidden" name="bank_label" id="bank_label" value="<?= htmlspecialchars($first['label'] ?? ''); ?>">
                  <input type="hidden" name="bank_account" id="bank_account" value="<?= htmlspecialchars($first['account'] ?? ''); ?>">
                  <input type="hidden" name="bank_owner" id="bank_owner" value="<?= htmlspecialchars($first['owner'] ?? ''); ?>">
                <?php else: ?>
                  <input type="text" class="form-control" name="bank_label" id="bank_label" placeholder="Nama Bank" required>
                  <input type="text" class="form-control mt-2" name="bank_account" id="bank_account" placeholder="No. Rekening" required>
                  <input type="text" class="form-control mt-2" name="bank_owner" id="bank_owner" placeholder="Nama Penerima" required>
                  <input type="hidden" name="bank_code" id="bank_code" value="manual">
                <?php endif; ?>
              </div>
              <div class="mb-3">
                <label class="form-label">Tanggal Transfer *</label>
                <input type="date" class="form-control" name="transfer_date" id="transfer_date" required>
                <div class="invalid-feedback">Format penulisan tanggal, bulan/tanggal/tahun</div>
              </div>
              <div class="mb-3">
                <label class="form-label">Jumlah Transfer *</label>
                <input type="number" class="form-control" name="nominal" id="nominal" placeholder="Contoh => 10000" required>
                <div class="form-text">Nominal harus sesuai dengan yang ditransfer</div>
                <div class="invalid-feedback">Perhatikan penulisan nominal transfer.</div>
              </div>
              <div class="mb-3">
                <label class="form-label">Bukti Transfer *</label>
                <input type="file" class="form-control" name="bukti" id="bukti" accept=".jpg,.jpeg,.png,.pdf" required>
                <div class="form-text">Format: JPG, JPEG, PNG, PDF (maks 1MB)</div>
                <div id="preview" class="mt-2"></div>
              </div>
              <button type="submit" class="btn btn-primary w-100" id="submitBtn" disabled>
                <span class="spinner-border spinner-border-sm me-2 d-none" id="spin"></span>
                Kirim Bukti Pembayaran
              </button>
            </form>
            <?php 
            $last = db_row("SELECT * FROM `epi_payment_confirm` WHERE `order_id`=".$orderId." ORDER BY `id` DESC LIMIT 1");
            if ($last && (int)$last['status'] === -1) : ?>
            <div class="card mt-3" id="banding">
              <div class="card-header">Ajukan Banding Verifikasi</div>
              <div class="card-body">
                <form action="" method="post">
                  <input type="hidden" name="csrf" value="<?= htmlspecialchars((string)$_SESSION['epi_nonce'], ENT_QUOTES); ?>">
                  <input type="hidden" name="action" value="appeal">
                  <div class="mb-3">
                    <label class="form-label">Alasan Banding</label>
                    <textarea class="form-control" name="appeal_msg" rows="3" required placeholder="Jelaskan alasan dan bukti tambahan jika ada"></textarea>
                  </div>
                  <button type="submit" class="btn btn-outline-warning">Kirim Banding</button>
                </form>
              </div>
            </div>
            <?php endif; ?>
            <?php else: ?>
            <div class="card">
              <div class="card-body">
                <div class="alert alert-success mb-3">Konfirmasi pembayaran berhasil dikirim.</div>
                <p class="mb-2">Terima kasih! Bukti pembayaran Anda sudah kami terima.</p>
                <p class="mb-2">Tim Finance Emas Perak Indonesia akan memproses verifikasi maksimal <strong>1×24 jam (hari kerja)</strong>.</p>
                <p class="mb-0">Mohon menunggu notifikasi berikutnya. Jika ada kendala, silakan hubungi Admin Finance melalui WhatsApp.</p>
              </div>
            </div>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<script>
(function(){
  var f = document.getElementById('confirmForm'); if(!f) return;
  var btn = document.getElementById('submitBtn');
  var nm = document.getElementById('atas_nama');
  var bc = document.getElementById('bank_code');
  var bl = document.getElementById('bank_label');
  var ba = document.getElementById('bank_account');
  var bo = document.getElementById('bank_owner');
  var dt = document.getElementById('transfer_date');
  var nom = document.getElementById('nominal');
  var buk = document.getElementById('bukti');
  var expText = document.getElementById('expectedNom');
  var exp = (expText ? (expText.textContent||'').replace(/[^0-9]/g,'') : '0'); exp = parseInt(exp||'0',10);
  var minDate = new Date(); minDate.setDate(minDate.getDate()-30);
  var maxDate = new Date();
  dt.setAttribute('min', minDate.toISOString().split('T')[0]);
  dt.setAttribute('max', maxDate.toISOString().split('T')[0]);
  function validNama(){ return /^[A-Za-z\s]{3,}$/.test(nm.value.trim()); }
  function validTanggal(){ var v = dt.value; return !!v && v >= dt.min && v <= dt.max; }
  function validNominal(){ var v = parseInt((nom.value||'').toString().replace(/[^0-9]/g,''),10); if(!v) return false; var min = Math.floor(exp*0.9); var max = Math.ceil(exp*1.1); return v>=min && v<=max; }
  function validFile(){ var f=buk.files[0]; if(!f) return false; var okExt=['jpg','jpeg','png','pdf']; var ext=(f.name||'').split('.').pop().toLowerCase(); var okType=['image/jpeg','image/png','application/pdf']; var typeOk = okExt.indexOf(ext)!==-1 || okType.indexOf(f.type)!==-1; return typeOk && f.size<=1024*1024; }
  function updateHidden(){ if(bc){ var opt = bc.options[bc.selectedIndex]; if(opt){ bl.value = opt.getAttribute('data-label')||''; ba.value = opt.getAttribute('data-account')||''; bo.value = opt.getAttribute('data-owner')||''; } } }
  function refresh(){ var ok = validNama() && validTanggal() && validNominal() && validFile(); btn.disabled = !ok; nm.classList.toggle('is-invalid', !validNama()); dt.classList.toggle('is-invalid', !validTanggal()); nom.classList.toggle('is-invalid', !validNominal()); }
  if(bc){ bc.addEventListener('change', function(){ updateHidden(); }); updateHidden(); }
  nm.addEventListener('input', refresh);
  dt.addEventListener('change', refresh);
  nom.addEventListener('input', refresh);
  buk.addEventListener('change', function(){ refresh(); var f=buk.files[0]; var p=document.getElementById('preview'); if(!p) return; p.innerHTML=''; if(!f) return; var ext=(f.name||'').split('.').pop().toLowerCase(); if(ext==='pdf'){ p.innerHTML='<div class="alert alert-secondary">PDF terpilih: '+(f.name||'')+'</div>'; } else { var r=new FileReader(); r.onload=function(e){ var img=document.createElement('img'); img.src=e.target.result; img.style.maxWidth='100%'; img.style.height='auto'; img.style.borderRadius='8px'; p.innerHTML=''; p.appendChild(img); }; r.readAsDataURL(f); } });
  refresh();
  f.addEventListener('submit', function(ev){ ev.preventDefault(); if(btn.disabled) return; btn.disabled = true; document.getElementById('spin').classList.remove('d-none'); var fd=new FormData(f); var xhr=new XMLHttpRequest(); xhr.open('POST', window.location.href, true); xhr.onreadystatechange=function(){ if(xhr.readyState===4){ document.getElementById('spin').classList.add('d-none'); btn.disabled=false; try{ var j=JSON.parse(xhr.responseText||'{}'); }catch(e){ j=null; }
      var err=document.getElementById('alertErr'); var ok=document.getElementById('alertOk'); if(err){ err.remove(); } if(ok){ ok.remove(); }
      if(j && j.ok){ var wrap=document.createElement('div'); wrap.className='card'; var body=document.createElement('div'); body.className='card-body'; var ok=document.createElement('div'); ok.className='alert alert-success mb-3'; ok.textContent=j.message||'Konfirmasi berhasil.'; var p1=document.createElement('p'); p1.className='mb-2'; p1.textContent='Terima kasih! Bukti pembayaran Anda sudah kami terima.'; var p2=document.createElement('p'); p2.className='mb-2'; p2.innerHTML='Tim Finance Emas Perak Indonesia akan memproses verifikasi maksimal <strong>1×24 jam (hari kerja)</strong>.'; var p3=document.createElement('p'); p3.className='mb-0'; p3.textContent='Mohon menunggu notifikasi berikutnya. Jika ada kendala, silakan hubungi Admin Finance melalui WhatsApp.'; body.appendChild(ok); body.appendChild(p1); body.appendChild(p2); body.appendChild(p3); wrap.appendChild(body); f.style.display='none'; f.parentNode.insertBefore(wrap, f); }
      else { var d=document.createElement('div'); d.className='alert alert-danger'; d.textContent=(j && j.message)?j.message:'Konfirmasi gagal.'; f.parentNode.insertBefore(d, f); }
    }}; xhr.setRequestHeader('X-Requested-With','XMLHttpRequest'); xhr.send(fd); });
})();
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
