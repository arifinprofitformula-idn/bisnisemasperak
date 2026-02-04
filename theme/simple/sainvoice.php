<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); } 
if (isset($slug[2]) && is_numeric($slug[2])) :

  $order = db_row("SELECT * FROM `sa_order` 
      LEFT JOIN `sa_member` ON `sa_member`.`mem_id` = `sa_order`.`order_idmember`
      LEFT JOIN `sa_page` ON `sa_page`.`page_id` = `sa_order`.`order_idproduk`
      WHERE `sa_order`.`order_id`=".$slug[2]);
  if (isset($order['order_id'])) :
    if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
    if (empty($_SESSION['epi_nonce'])) { $_SESSION['epi_nonce'] = sha1(SECRET . microtime(true) . rand(1000,9999)); }
    if (isset($_GET['act']) && $_GET['act'] == 'ubahpembayaran') {
      if (isset($datamember['mem_id']) && $datamember['mem_id'] == $order['order_idmember']) {
        db_query("UPDATE `sa_order` SET `order_trx` ='' WHERE `order_id`=".$order['order_id']." AND `order_idmember`=".$datamember['mem_id']);
        echo db_error();
        $order['order_trx'] = '';
      } else {
        echo 'Data member tidak ada';
      }
    }
?>
<!DOCTYPE html>
<html class="full" lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/x-icon" href="<?=$weburl;?>img/<?=$favicon;?>" />
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Invoice <?=$order['page_judul'];?></title>

    <?php
    // Fallback IDs for Meta Pixel and GTM
    $pixelId = '';
    $gtmId = '';
    if (isset($datasponsor['fbpixel']) && !empty($datasponsor['fbpixel'])) {
        $pixelId = $datasponsor['fbpixel'];
    } elseif (isset($settings['fbpixel']) && !empty($settings['fbpixel'])) {
        $pixelId = $settings['fbpixel'];
    }
    if (isset($datasponsor['gtm']) && !empty($datasponsor['gtm'])) {
        $gtmId = $datasponsor['gtm'];
    } elseif (isset($settings['gtm']) && !empty($settings['gtm'])) {
        $gtmId = $settings['gtm'];
    }

	// Compute prices for event values (honor coupon used at order time)
	$hargaNormal = (isset($order['order_harga']) && is_numeric($order['order_harga'])) ? (int)$order['order_harga'] : ((isset($order['pro_harga']) && is_numeric($order['pro_harga'])) ? (int)$order['pro_harga'] : 0);
	$couponCode  = isset($order['order_promo_code']) ? trim($order['order_promo_code']) : '';
	$storedDisplay = (isset($order['order_price_display']) && is_numeric($order['order_price_display'])) ? (int)$order['order_price_display'] : null;
	$baseDisplay   = (isset($order['pro_harga_display']) && is_numeric($order['pro_harga_display'])) ? (int)$order['pro_harga_display'] : $hargaNormal;
	$isFreeProduct = ($hargaNormal > 0 && (int)$baseDisplay === 0);
	$isLunasDisplay = ((int)($order['order_status'] ?? 0) === 1) || $isFreeProduct;
	// If the stored display price is missing (older orders), recompute effective price using the saved coupon code
	$eff = epi_effective_price((int)$hargaNormal, (int)$baseDisplay, $couponCode, (int)$order['order_idproduk'], 1);
	$hargaTampil = ($storedDisplay !== null) ? $storedDisplay : (int)$eff['price'];
    ?>

    <?php if (!empty($pixelId)) : ?>
    <!-- Meta Pixel Code -->
    <script>
    !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
    n.push=n; n.loaded=!0; n.version='2.0'; n.queue=[]; t=b.createElement(e); t.async=!0;
    t.src=v; s=b.getElementsByTagName(e)[0]; s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init','<?= trim($pixelId) ?>');
    fbq('track','PageView');
    </script>
    <noscript>
    <img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=<?= urlencode(trim($pixelId)) ?>&ev=PageView&noscript=1"/>
    </noscript>
    <?php endif; ?>

    <?php if (!empty($gtmId)) : ?>
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[]; w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'}); var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'? '&l='+l : ''; j.async=true; j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl; f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-<?= htmlspecialchars($gtmId, ENT_QUOTES) ?>');</script>
    <!-- End Google Tag Manager -->
    <?php endif; ?>

    <?php if (!empty($pixelId)) : ?>
    <!-- Pixel Events -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
      try {
        fbq('track', 'InitiateCheckout', {
          content_name: '<?= addslashes($order['page_judul']) ?>',
          value: <?= is_numeric($hargaTampil) ? $hargaTampil : 0 ?>,
          currency: 'IDR'
        });
      } catch(e) {}
    });
    </script>
    <?php endif; ?>

    <?php if (!empty($pixelId) && isset($order['order_status']) && $order['order_status'] == 1) : ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
      try {
        fbq('track', 'Purchase', {
          content_name: '<?= addslashes($order['page_judul']) ?>',
          value: <?= is_numeric($hargaTampil) ? $hargaTampil : 0 ?>,
          currency: 'IDR'
        });
      } catch(e) {}
    });
    </script>
    <?php endif; ?>

    <!-- Bootstrap Core CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
    <link href="<?=$weburl;?>fontawesome/css/fontawesome.min.css" rel="stylesheet" />
    <link href="<?=$weburl;?>fontawesome/css/regular.min.css" rel="stylesheet" />
</head>
<body class="invoice">
<?php if (!empty($gtmId)) : ?>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-<?= htmlspecialchars($gtmId, ENT_QUOTES) ?>" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
<?php endif; ?>
	<div class="container p-md-3 mt-3 mb-3">
    <style>
      .invoice .card{border-radius:12px;box-shadow:0 10px 24px rgba(0,0,0,.08);border:1px solid rgba(0,0,0,.06)}
      .invoice .card-header{background:#fff;border-bottom:1px solid rgba(0,0,0,.06);font-weight:600}
      .invoice .hero{border-radius:10px;padding:14px 16px;background:#eaf8ec;border:1px solid #cde9d3;color:#0b5137}
      .invoice .summary-box{border-radius:10px;background:#fff9f0;border:1px solid #ffe6bf;padding:16px;margin-top:12px}
      .invoice .summary-row{display:flex;justify-content:space-between;align-items:center;padding:6px 0}
      .invoice .summary-total{display:flex;justify-content:space-between;align-items:center;padding:12px 0;font-weight:700;color:#b8860b;border-top:1px solid #ffe6bf;margin-top:8px}
      .invoice .notice{border-radius:10px;background:#fff8e6;border:1px solid #ffe2a8;color:#7a5a00;padding:12px 14px;margin:14px 0}
      .invoice .notice.success{background:#d1e7dd;border:1px solid #badbcc;color:#0f5132}
      .invoice .method-card{display:flex;align-items:center;gap:14px;border:1px solid rgba(0,0,0,.08);border-radius:12px;padding:16px;background:#fff;box-shadow:0 6px 18px rgba(0,0,0,.06);text-decoration:none;color:#212529}
      .invoice .method-card img{width:56px;height:56px;object-fit:contain}
      .invoice .method-card .title{font-weight:600}
      .invoice .method-card .subtitle{color:#6c757d;font-size:.9rem}
      .invoice .method-card .account{font-weight:600}
      .invoice .wa-preview{font-size:.95rem;color:#6c757d}
      .invoice .typing{border-right:2px solid #6c757d;white-space:pre-wrap;overflow:hidden}
      .invoice .summary-box ul{padding-left:20px;margin-bottom:0}
      .invoice .summary-box li{margin-bottom:6px}
      .invoice .ref-badge{display:inline-block;padding:.25rem .5rem;background:#FFF7E0;border:1px solid #E6C76A;border-radius:.25rem;color:#0B0B0B;font-weight:600}
    </style>
	<div class="hero mb-3">
	  <?php if ($isLunasDisplay) { echo '<strong>Pembelian sudah aktif.</strong> Terima kasih.'; } else { echo '<strong>Checkout berhasil.</strong> Silakan lanjutkan pembayaran untuk mengaktifkan akses.'; } ?>
	</div>
	<div class="card">
	  <div class="card-header"><h1 class="h1 m-0">Pembayaran</h1><div class="small text-muted"><?php if ($isLunasDisplay) { echo 'Pembelian produk '.htmlspecialchars($order['page_judul']).' Anda sudah aktif.'; } else { echo 'Selesaikan pembayaran untuk mengaktifkan pembelian produk '.htmlspecialchars($order['page_judul']).' Anda'; } ?></div></div>
	  <div class="card-body">
    		<div class="row mt-3">
          <div class="col-md-6 p-md-5">
            <!-- Logo website pojok kiri atas -->
            <div class="mb-2"><img src="<?= $weburl.'upload/logo-webb.jpg'; ?>" alt="Logo Website" style="height:60px; max-width:100%;"></div>
            <h1 class="h3">Invoice #<?=str_pad($order['order_id'],4,0,STR_PAD_LEFT);?></h1>
            <?php
              $importantNote = '';
              if (isset($order['order_important_note']) && (string)$order['order_important_note'] !== '') { $importantNote = trim((string)$order['order_important_note']); }
              if ($importantNote === '') {
                $dl = isset($order['mem_datalain']) ? (string)$order['mem_datalain'] : '';
                $pt = '/\[order_note_'.(int)$order['order_id'].'\|(.*?)\]/';
                if ($dl !== '' && preg_match($pt, $dl, $m)) { $importantNote = trim((string)$m[1]); }
              }
              if ($importantNote !== '') { echo '<div class="notice mt-2"><strong>Catatan Penting:</strong> '.htmlspecialchars($importantNote, ENT_QUOTES).'</div>'; }
            ?>
			<?php if ($isLunasDisplay) { echo '<div style="font-size:24px;font-weight:700" class="text-success">LUNAS</div>';} ?>
          </div>
          <div class="col-md-6 p-md-5 text-end">
            <strong>Ditagihkan kepada:</strong><br>
            <?php 
            if ($iduser = is_login()) {
              if ($iduser == $order['order_idmember']) {
                echo '                
                '.$order['mem_nama'].'<br/>
                WA: <a href="https://wa.me/'.$order['mem_whatsapp'].'">'.$order['mem_whatsapp'].'</a>';
              } else {
                echo sensor($order['mem_nama']);
              }
            } else {
              echo sensor($order['mem_nama']);
            }
            ?>
            <?php 
              $spInfo = db_row("SELECT `sa_member`.`mem_nama` FROM `sa_sponsor` LEFT JOIN `sa_member` ON `sa_member`.`mem_id`=`sa_sponsor`.`sp_sponsor_id` WHERE `sa_sponsor`.`sp_mem_id`=".(int)$order['order_idmember']);
              $refNama = isset($spInfo['mem_nama']) && !empty($spInfo['mem_nama']) ? $spInfo['mem_nama'] : '-';
              echo '<div class="mt-2"><span class="ref-badge">Info Pereferral: '.htmlspecialchars($refNama).'</span></div>';
            ?>
          </div>
        </div>
        <div class="summary-box">
          <h2 class="h4 m-0 mb-2">Ringkasan Pembelian</h2>
          <?php
          // Harga Normal = harga produk sebenarnya (manage produk)
          $hargaProduk = (isset($order['order_harga']) && is_numeric($order['order_harga'])) ? (int)$order['order_harga'] : ((isset($order['pro_harga']) && is_numeric($order['pro_harga'])) ? (int)$order['pro_harga'] : 0);
          // Kupon yang digunakan saat order (dihormati di invoice)
          $couponCode  = isset($order['order_promo_code']) ? trim($order['order_promo_code']) : '';
          // Harga promo dasar (tanpa kupon) diambil dari konfigurasi produk; fallback ke harga normal
          $hargaPromoBase = (isset($order['pro_harga_display']) && is_numeric($order['pro_harga_display'])) ? (int)$order['pro_harga_display'] : $hargaProduk;
          // Diskon promo adalah selisih harga normal dan harga promo dasar
          $diskonPromo   = max(0, $hargaProduk - $hargaPromoBase);
          // Diskon kupon yang tersimpan saat checkout (jika ada)
          $storedDisplay = (isset($order['order_price_display']) && is_numeric($order['order_price_display'])) ? (int)$order['order_price_display'] : null;
          $diskonKuponStored = (isset($order['order_discount']) && is_numeric($order['order_discount'])) ? (int)$order['order_discount'] : null;
          $diskonKupon   = (!empty($couponCode)) ? (($diskonKuponStored !== null) ? $diskonKuponStored : 0) : 0;
          // Jika kolom tersimpan belum ada namun harga tampil tersimpan tersedia,
          // turunkan nilai diskon kupon dari selisih (harga normal -> harga tampil) dikurangi diskon promo dasar
          if ($diskonKupon === 0 && !empty($couponCode) && $diskonKuponStored === null && $storedDisplay !== null) {
            $derived = ($hargaProduk > $storedDisplay) ? ($hargaProduk - $storedDisplay) : 0;
            $diskonKupon = max(0, $derived - $diskonPromo);
          }
          // Fallback terakhir: gunakan kalkulasi helper jika tersedia
          if ($diskonKupon === 0 && !empty($couponCode)) {
            $eff = epi_effective_price((int)$hargaProduk, (int)$hargaPromoBase, $couponCode, (int)$order['order_idproduk'], 1);
            if (isset($eff['discount']) && is_numeric($eff['discount'])) { $diskonKupon = (int)$eff['discount']; }
          }
          // Harga setelah kupon (tidak negatif)
          $hargaSetelahKupon = max(0, (int)$hargaPromoBase - (int)$diskonKupon);
          // Kode unik: selisih nominal antara total transfer (unik) vs harga setelah kupon
          $kodeUnikMode = isset($settings['kodeunik']) ? (int)$settings['kodeunik'] : 0;
          $hasUnik = ($kodeUnikMode !== 0) && isset($order['order_hargaunik']) && is_numeric($order['order_hargaunik']);
          $uniqNominal = $hasUnik ? abs((int)$order['order_hargaunik'] - (int)$hargaSetelahKupon) : 0;
          // Pastikan selisih kode unik berada pada rentang 0..999 (3 digit)
          $uniqNominal = min(999, max(0, (int)$uniqNominal));
          if ($hasUnik) {
            if ($kodeUnikMode === 1) { $totalBayar = max(0, (int)$hargaSetelahKupon - $uniqNominal); }
            elseif ($kodeUnikMode === 2) { $totalBayar = (int)$hargaSetelahKupon + $uniqNominal; }
            else { $totalBayar = (int)$hargaSetelahKupon; }
          } else { $totalBayar = (int)$hargaSetelahKupon; }
          // Total diskon (informasi) = promo + kupon
          $diskonTotal = max(0, (int)$diskonPromo + (int)$diskonKupon);
          $kodeUnikLabel = 'Kode Unik';
          if ($kodeUnikMode === 1) { $kodeUnikLabel = 'Kode Unik (Kurangi)'; }
          elseif ($kodeUnikMode === 2) { $kodeUnikLabel = 'Kode Unik (Tambah)'; }
          ?>
          <div class="summary-row"><div>Nama Produk</div><div class="fw-semibold"><?= htmlspecialchars($order['page_judul']);?></div></div>
          <?php if (!empty($couponCode)) : ?>
          <div class="summary-row"><div>Kode Promo</div><div class="fw-semibold"><?= htmlspecialchars($couponCode); ?></div></div>
          <?php endif; ?>
          <div class="summary-row"><div>Harga Normal</div><div>Rp <?= number_format($hargaProduk);?></div></div>
          <?php if ($diskonPromo > 0) : ?>
          <div class="summary-row"><div>Diskon Promo</div><div>− Rp <?= number_format($diskonPromo);?></div></div>
          <?php endif; ?>
          <?php if ($diskonKupon > 0) : ?>
          <div class="summary-row"><div>Diskon Kupon</div><div>− Rp <?= number_format($diskonKupon);?></div></div>
          <?php endif; ?>
          <div class="summary-row"><div>Harga Promo</div><div>Rp <?= number_format($hargaPromoBase);?></div></div>
          <?php if ($hasUnik) : ?>
          <div class="summary-row"><div><?= htmlspecialchars($kodeUnikLabel); ?></div><div><?= $kodeUnikMode === 1 ? '− Rp '.number_format($uniqNominal) : '+ Rp '.number_format($uniqNominal); ?></div></div>
          <?php endif; ?>
          <div class="summary-total">
            <div>Total Pembayaran</div>
            <div class="d-flex align-items-center gap-2">
              <span id="totalAmountText">Rp <?= number_format($totalBayar);?></span>
              <button type="button" class="btn btn-sm btn-outline-secondary" id="btnCopyTotal"
                data-amount="Rp <?= number_format($totalBayar);?>">Copy Total</button>
            </div>
          </div>
        </div>
        
        <?php
		  $isLunas = $isLunasDisplay;
		  $noticeClass = $isLunas ? 'notice success' : 'notice';
		  $noticeText = $isLunas
			? 'Pembelian Anda sudah aktif, silakan akses produk <strong>'.htmlspecialchars($order['page_judul']).'</strong> yang telah Anda beli pada tombol AKSES PRODUK'
			: 'Pembelian Anda akan segera aktif setelah pembayaran dikonfirmasi.';
		?>
        <div class="<?= $noticeClass; ?>"><?= $noticeText; ?></div>
        <?php 
          $confirm = db_row("SELECT * FROM `epi_payment_confirm` WHERE `order_id`=".(int)$order['order_id']." ORDER BY `id` DESC LIMIT 1");
          if ($order['order_status'] == 2) {
            echo '<div class="alert alert-danger">Invoice ini telah dibatalkan secara otomatis karena tidak dibayarkan dalam 48 jam.</div>';
          }
		  if ($order['order_status'] == 0 && !$isFreeProduct) {
			if ($confirm && isset($confirm['id'])) {
			  $st = (int)$confirm['status'];
			  if ($st === 0) {
				echo '<div class="alert alert-info">Status Verifikasi: Menunggu ditinjau admin.</div>';
			  } elseif ($st === 1) {
				echo '<div class="alert alert-success">Status Verifikasi: Diterima. Pesanan sedang diproses.</div>';
			  } elseif ($st === -1) {
				$note = htmlspecialchars($confirm['verified_note'] ?? '');
				echo '<div class="alert alert-warning">Status Verifikasi: Ditolak. Alasan: '.$note.'<br><a class="btn btn-sm btn-outline-warning mt-2" href="'.$weburl.'konfirmasi/'.(int)$order['order_id'].'#banding">Ajukan Banding</a></div>';
			  }
			} else {
			  echo '<div class="alert alert-secondary">Belum ada konfirmasi pembayaran. <a class="btn btn-sm btn-primary ms-2" href="'.$weburl.'konfirmasi/'.(int)$order['order_id'].'">Konfirmasi Pembayaran Anda</a></div>';
			}
		  }
		?>
        <div class="row mt-3">
          <div class="col-12 p-md-4 mb-3">
          <?php
          if ($order['order_status'] == 2) {
            echo '<div class="alert alert-light border">Pembayaran tidak tersedia karena invoice telah dibatalkan.</div>';
		  } elseif ($order['order_status'] == 0 && !$isFreeProduct) {
			if (isset($settings['tripay_sandbox']) && $settings['tripay_sandbox'] == 1) {
			  $urlapi = 'api-sandbox';
			} else {
			  $urlapi = 'api'; 
			}

            if (empty($order['order_trx'])) {
              include('payment.php');
            } elseif ($order['order_trx'] == 'manual') {
              if (isset($settings['carapembayaran']) && !empty($settings['carapembayaran'])) { 
                // Hitung Harga Normal, Harga Promo, Diskon Kupon, dan Harga Akhir sesuai aturan
                $hargaNormal = (isset($order['order_harga']) && is_numeric($order['order_harga'])) ? (int)$order['order_harga'] : ((isset($order['pro_harga']) && is_numeric($order['pro_harga'])) ? (int)$order['pro_harga'] : 0);
                $couponCode  = isset($order['order_promo_code']) ? trim($order['order_promo_code']) : '';
                $hargaPromoBase = (isset($order['pro_harga_display']) && is_numeric($order['pro_harga_display'])) ? (int)$order['pro_harga_display'] : $hargaNormal;
                $diskonPromo = max(0, $hargaNormal - $hargaPromoBase);
                $storedDisplay = (isset($order['order_price_display']) && is_numeric($order['order_price_display'])) ? (int)$order['order_price_display'] : null;
                $diskonKuponStored = (isset($order['order_discount']) && is_numeric($order['order_discount'])) ? (int)$order['order_discount'] : null;
                $diskonKupon = (!empty($couponCode)) ? (($diskonKuponStored !== null) ? $diskonKuponStored : 0) : 0;
                if ($diskonKupon === 0 && !empty($couponCode) && $diskonKuponStored === null && $storedDisplay !== null) {
                  $derived = ($hargaNormal > $storedDisplay) ? ($hargaNormal - $storedDisplay) : 0;
                  $diskonKupon = max(0, $derived - $diskonPromo);
                }
                if ($diskonKupon === 0 && !empty($couponCode)) {
                  $eff = epi_effective_price((int)$hargaNormal, (int)$hargaPromoBase, $couponCode, (int)$order['order_idproduk'], 1);
                  if (isset($eff['discount']) && is_numeric($eff['discount'])) { $diskonKupon = (int)$eff['discount']; }
                }
                $hargaTampil   = max(0, (int)$hargaPromoBase - (int)$diskonKupon);
                $diskon        = ($hargaNormal > $hargaTampil) ? ($hargaNormal - $hargaTampil) : 0;

                $manual = $settings['carapembayaran'];
                // Shortcode harga akhir (harga tampil)
                $manual = str_replace('[harga]', number_format($hargaTampil), $manual);
                // Shortcode harga normal (sebelum diskon)
                $manual = str_replace('[harga_normal]', number_format($hargaNormal), $manual);
                // Shortcode nilai diskon (0 jika tidak ada)
                $manual = str_replace('[diskon]', number_format($diskon), $manual);
                // Shortcode harga unik (jumlah transfer dengan kode unik; 0 jika gratis)
                $manual = str_replace('[hargaunik]', number_format($order['order_hargaunik']), $manual);
                // Shortcode harga unik tanpa pemisah ribuan (angka mentah)
                $manual = str_replace('[hargacopy]', $order['order_hargaunik'], $manual);
                // Shortcode nama produk
                $manual = str_replace('[namaproduk]', $order['page_judul'], $manual);

                // Proses shortcode [copy data="..."] menjadi tombol copy
                $manual = copycode($manual);  
                // Back link
                echo '<div class="mb-3"><a href="'.$weburl.'invoice/'.$order['order_id'].'?act=ubahpembayaran" class="text-decoration-none">&larr; Kembali ke Metode Pembayaran</a></div>';
                // Info manual + WA
                $sp = db_row("SELECT `sa_member`.`mem_whatsapp`,`sa_member`.`mem_nama` FROM `sa_sponsor` LEFT JOIN `sa_member` ON `sa_member`.`mem_id`=`sa_sponsor`.`sp_sponsor_id` WHERE `sa_sponsor`.`sp_mem_id`=".$order['order_idmember']);
                $waSponsor = isset($sp['mem_whatsapp']) ? formatwa($sp['mem_whatsapp']) : '';
                $waAdmin = '';
                if (isset($settings['wa_admin']) && !empty($settings['wa_admin'])) { $waAdmin = formatwa($settings['wa_admin']); }
                elseif (isset($settings['whatsapp']) && !empty($settings['whatsapp'])) { $waAdmin = formatwa($settings['whatsapp']); }
                else { $waAdmin = $waSponsor; }
                echo '<div class="alert alert-warning d-flex justify-content-between align-items-center" role="alert"><div><img src="'.$weburl.'img/info-payment.png" alt="Info Pembayaran" style="height:22px;width:22px;object-fit:contain;" class="me-2"> Informasi Pembayaran (Manual)</div></div>';
                // Total box
                $kodeUnikModeBox = isset($settings['kodeunik']) ? (int)$settings['kodeunik'] : 0;
                $hasUnikBox = ($kodeUnikModeBox !== 0) && isset($order['order_hargaunik']) && is_numeric($order['order_hargaunik']);
                $uniqNominalBox = $hasUnikBox ? abs((int)$order['order_hargaunik'] - (int)$hargaTampil) : 0;
                $uniqNominalBox = min(999, max(0, (int)$uniqNominalBox));
                if ($hasUnikBox) {
                  if ($kodeUnikModeBox === 1) { $totalManual = max(0, (int)$hargaTampil - $uniqNominalBox); }
                  elseif ($kodeUnikModeBox === 2) { $totalManual = (int)$hargaTampil + $uniqNominalBox; }
                  else { $totalManual = (int)$hargaTampil; }
                } else { $totalManual = (int)$hargaTampil; }
                echo '<div class="summary-box"><div class="text-center"><div class="fw-semibold mb-1">Total Pembayaran</div><div class="d-flex align-items-center justify-content-center gap-2"><div class="h4 text-warning mb-0">Rp '.number_format($totalManual).'</div><button type="button" class="btn btn-sm btn-outline-secondary" onclick="copyToClipboard(\'Rp '.number_format($totalManual).'\', this)">Copy Total</button></div><div class="text-muted">Pastikan transfer sesuai nominal transfer di atas untuk mempercepat verifikasi</div></div></div>';
                // Bank tujuan + instruksi dari settings
                $banks = isset($settings['bank_accounts']) ? json_decode($settings['bank_accounts'],true) : [];
                if (is_array($banks) && count($banks)>0) {
                  $logoMap = [
                    'bca' => $weburl.'img/bank/bca.png',
                    'mandiri' => $weburl.'img/bank/mandiri.png',
                    'bri' => $weburl.'img/bank/bri.png',
                    'bni' => $weburl.'img/bank/bni.png',
                    'bsi' => $weburl.'img/bank/bsi.png'
                  ];
                  echo '<div class="mt-3"><div class="card"><div class="card-header"><img src="'.$weburl.'img/info-bank.png" alt="Info Bank" style="height:24px;width:24px;object-fit:contain;" class="me-2"> Pilih Rekening Tujuan</div><div class="card-body"><div id="bankList">';
                  foreach($banks as $b){ $code = isset($b['code']) ? strtolower($b['code']) : ''; $logo = isset($logoMap[$code]) ? $logoMap[$code] : ''; $img = $logo ? '<img src="'.$logo.'" alt="Logo '.htmlspecialchars($b['label']).'" loading="lazy" onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'inline-block\';" />' : ''; $fallback = '<i class="fas fa-building-columns" style="display:' . ($logo ? 'none' : 'inline-block') . '"></i>'; echo '<div class="method-card mb-2">'.$img.$fallback.'<div><div class="title">'.htmlspecialchars($b['label']).'</div><div class="subtitle">a.n. '.htmlspecialchars($b['owner']).'</div><div class="account">No. Rekening: <span class="font-monospace">'.htmlspecialchars($b['account']).'</span></div></div><div class="ms-auto"><a onclick="copyToClipboard(\''.htmlspecialchars($b['account']).'\', this)" class="btn btn-sm btn-outline-secondary">Copy Nomor</a></div></div>'; }
                  echo '</div></div></div></div>';
                  $instrNominal = isset($totalBayar) ? (int)$totalBayar : (isset($totalManual) ? (int)$totalManual : (int)$hargaTampil);
                  $waDisplay = !empty($waAdmin) ? '+'.htmlspecialchars($waAdmin) : 'Nomor admin belum diatur';
                  echo '<div class="mt-3"><div class="card"><div class="card-header"><img src="'.$weburl.'img/info-payment.png" alt="Instruksi Pembayaran" style="height:22px;width:22px;object-fit:contain;" class="me-2"> Instruksi Pembayaran</div><div class="card-body"><ol class="mb-0"><li>Transfer sesuai nominal <span class="fw-semibold">Rp '.number_format($instrNominal).'</span> ke salah satu rekening di atas</li><li>Simpan bukti transfer (screenshot/foto struk)</li><li>Klik tombol <span class="fw-semibold">Konfirmasi Pembayaran Anda</span> di halaman ini untuk unggah bukti dan isi form konfirmasi</li><li>Tunggu verifikasi admin (estimasi 1×24 jam)</li><li>Anda akan menerima notifikasi via WhatsApp/Email setelah verifikasi</li></ol></div></div></div>';
                } else {
                   echo '<div class="mt-3"><div class="card"><div class="card-header"><img src="'.$weburl.'img/info-bank.png" alt="Info Bank" style="height:24px;width:24px;object-fit:contain;" class="me-2"> Pilih Rekening Tujuan</div><div class="card-body">'.$manual.'</div></div></div>';
                   $instrNominal = isset($totalBayar) ? (int)$totalBayar : (isset($totalManual) ? (int)$totalManual : (int)$hargaTampil);
                   $waDisplay = !empty($waAdmin) ? '+'.htmlspecialchars($waAdmin) : 'Nomor admin belum diatur';
                   echo '<div class="mt-3"><div class="card"><div class="card-header"><img src="'.$weburl.'img/info-payment.png" alt="Instruksi Pembayaran" style="height:22px;width:22px;object-fit:contain;" class="me-2"> Instruksi Pembayaran</div><div class="card-body"><ol class="mb-0"><li>Transfer sesuai nominal <span class="fw-semibold">Rp '.number_format($instrNominal).'</span></li><li>Simpan bukti transfer (screenshot/foto struk)</li><li>Klik tombol <span class="fw-semibold">Konfirmasi Pembayaran Anda</span> di halaman ini untuk unggah bukti dan isi form konfirmasi</li><li>Tunggu verifikasi admin (estimasi 1×24 jam)</li><li>Anda akan menerima notifikasi via WhatsApp/Email setelah verifikasi</li></ol></div></div></div>';
                }
                // Upload / Edit / Hapus bukti transfer (server-side)
                if (false) {
                  if (!db_var("SHOW COLUMNS FROM `sa_order` LIKE 'order_bukti'")) { db_query("ALTER TABLE `sa_order` ADD `order_bukti` VARCHAR(200) NULL"); }
                  if (!db_var("SHOW COLUMNS FROM `sa_order` LIKE 'order_buktitgl'")) { db_query("ALTER TABLE `sa_order` ADD `order_buktitgl` DATETIME NULL"); }
                  if (!db_var("SHOW COLUMNS FROM `sa_order` LIKE 'order_bukti_note'")) { db_query("ALTER TABLE `sa_order` ADD `order_bukti_note` VARCHAR(200) NULL"); }
                  if (!db_var("SHOW COLUMNS FROM `sa_order` LIKE 'order_bukti_name'")) { db_query("ALTER TABLE `sa_order` ADD `order_bukti_name` VARCHAR(200) NULL"); }
                  if (!db_var("SHOW COLUMNS FROM `sa_order` LIKE 'order_bukti_size'")) { db_query("ALTER TABLE `sa_order` ADD `order_bukti_size` INT(11) NULL"); }
                  if (!db_var("SHOW COLUMNS FROM `sa_order` LIKE 'order_bukti_type'")) { db_query("ALTER TABLE `sa_order` ADD `order_bukti_type` VARCHAR(64) NULL"); }
                  // CSRF sederhana menggunakan nonce sesi
                  $csrfOk = (isset($_POST['csrf']) && isset($_SESSION['epi_nonce']) && hash_equals((string)$_SESSION['epi_nonce'], (string)$_POST['csrf']));
                  if (!$csrfOk) { $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH'])==='xmlhttprequest') || (isset($_POST['ajax']) && $_POST['ajax']=='1'); if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['ok'=>false,'error'=>'csrf_invalid','message'=>'Session expired/invalid','code'=>0]); exit; } echo '<div class="alert alert-danger">Session expired/invalid. Silakan refresh halaman.</div>'; }
                  else {
                    $dir = __DIR__.'/../../upload/transfer'; if (!is_dir($dir)) { @mkdir($dir,0777,true); }
                    $action = isset($_POST['action']) ? $_POST['action'] : '';
                    if ($action === 'upload_bukti' && isset($_FILES['bukti'])) {
                      $file = $_FILES['bukti']; $ok=1; $ext=strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                      $mime = isset($file['type']) ? strtolower($file['type']) : '';
                      $uploadErr = isset($file['error']) ? (int)$file['error'] : 0;
                      if ($uploadErr !== 0) {
                        $ok=0;
                        $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH'])==='xmlhttprequest') || (isset($_POST['ajax']) && $_POST['ajax']=='1');
                        $msg = 'Upload gagal';
                        if ($uploadErr === 1 || $uploadErr === 2) { $msg = 'Ukuran file melebihi batas'; }
                        elseif ($uploadErr === 3) { $msg = 'Upload terputus (partial)'; }
                        elseif ($uploadErr === 4) { $msg = 'Tidak ada file yang diunggah'; }
                        elseif ($uploadErr === 6) { $msg = 'Folder temporary server tidak tersedia'; }
                        elseif ($uploadErr === 7) { $msg = 'Gagal menulis file ke disk'; }
                        elseif ($uploadErr === 8) { $msg = 'Upload dihentikan oleh ekstensi'; }
                        error_log('[upload_bukti] php_upload_error code: '.$uploadErr);
                        if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['ok'=>false,'error'=>'php_upload_error','message'=>$msg,'code'=>$uploadErr]); exit; }
                        echo '<div class="alert alert-danger">'.$msg.'</div>';
                      }
                      $allowedExt  = ['jpg','jpeg','png','pdf'];
                      $allowedMime = ['image/jpeg','image/png','application/pdf'];
                      $validType = in_array($ext,$allowedExt) || in_array($mime,$allowedMime);
                      if (!$validType) { $ok=0; error_log('[upload_bukti] invalid type: '.($mime).' ext: '.$ext); $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH'])==='xmlhttprequest') || (isset($_POST['ajax']) && $_POST['ajax']=='1'); $msg = 'Format file tidak didukung. Gunakan JPEG/JPG/PNG/PDF (≤1MB).'; if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['ok'=>false,'error'=>'invalid_type','message'=>$msg,'code'=>0]); exit; } echo '<div class="alert alert-warning">'.$msg.'</div>'; }
                      if ($file['size']>1024*1024) { $ok=0; error_log('[upload_bukti] too large: '.(int)$file['size']); $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH'])==='xmlhttprequest') || (isset($_POST['ajax']) && $_POST['ajax']=='1'); $msg = 'Ukuran file melebihi 1MB.'; if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['ok'=>false,'error'=>'too_large','message'=>$msg,'code'=>0]); exit; } echo '<div class="alert alert-warning">'.$msg.'</div>'; }
                      if ($ok) {
                        $name='bukti_'.$order['order_id'].'_'.time().'.'.$ext;
                        $dest = $dir.'/'.$name;
                        $saved = false;
                        if (is_uploaded_file($file['tmp_name'])) {
                          if (move_uploaded_file($file['tmp_name'],$dest)) { $saved = true; }
                          else {
                            // Fallback: copy stream if move_uploaded_file fails (Windows/permissions edge-cases)
                            $data = @file_get_contents($file['tmp_name']);
                            if ($data !== false && @file_put_contents($dest, $data) !== false) { $saved = true; }
                          }
                        }
                        if ($saved) {
                          // Hapus file bukti lama bila ada dan berada di dalam folder upload/transfer
                          if (!empty($order['order_bukti'])) {
                            $oldRel = (string)$order['order_bukti'];
                            $oldAbs = realpath(__DIR__.'/../../'.str_replace(['..','\\'],['','.'], $oldRel));
                            $baseDir = realpath(__DIR__.'/../../upload/transfer');
                            if ($oldAbs && $baseDir && strpos($oldAbs, $baseDir) === 0) { @unlink($oldAbs); }
                          }
                          $q = "UPDATE `sa_order` SET `order_bukti`='upload/transfer/".$name."',`order_buktitgl`=NOW(),`order_bukti_note`='".cek($_POST['catatan'] ?? '') ."',`order_bukti_name`='".cek($file['name'])."',`order_bukti_size`=".(int)$file['size'].",`order_bukti_type`='".cek($mime)."' WHERE `order_id`=".$order['order_id'];
                          $res = db_query($q);
                          $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH'])==='xmlhttprequest') || (isset($_POST['ajax']) && $_POST['ajax']=='1');
                          if ($res === false) { @unlink($dir.'/'.$name); if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['ok'=>false,'error'=>'db_save_failed']); exit; } echo '<div class="alert alert-danger">Gagal menyimpan ke database.</div>'; } else { if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['ok'=>true,'url'=>$weburl.'upload/transfer/'.$name,'name'=>$file['name'],'size'=>(int)$file['size'],'type'=>$mime]); exit; } echo '<div class="alert alert-info">Bukti transfer berhasil diupload. Tim kami akan memverifikasi.</div><script>window.__uploadedFileUrl=\'' . addslashes($weburl.'upload/transfer/'.$name) . '\';</script>'; }
                          // Refresh data order untuk menampilkan status terbaru
                          $order = db_row("SELECT * FROM `sa_order` LEFT JOIN `sa_member` ON `sa_member`.`mem_id`=`sa_order`.`order_idmember` LEFT JOIN `sa_page` ON `sa_page`.`page_id`=`sa_order`.`order_idproduk` WHERE `order_id`=".$order['order_id']);
                        } else {
                          $err = 'Gagal mengunggah file. Pastikan folder upload/transfer dapat ditulis.';
                          error_log('[upload_bukti] save failed: '.$err);
                          $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH'])==='xmlhttprequest') || (isset($_POST['ajax']) && $_POST['ajax']=='1');
                          if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['ok'=>false,'error'=>'save_failed']); exit; }
                          echo '<div class="alert alert-danger">'.$err.'</div>';
                        }
                      }
                    } elseif ($action === 'delete_bukti') {
                      // Hapus file fisik jika ada dan berada di folder upload/transfer
                      if (!empty($order['order_bukti'])) {
                        $pathRel = $order['order_bukti'];
                        $pathAbs = realpath(__DIR__.'/../../'.str_replace(['..','\\'],['','.'], $pathRel));
                        if ($pathAbs && strpos($pathAbs, realpath(__DIR__.'/../../upload/transfer')) === 0) { @unlink($pathAbs); }
                      }
                      db_query("UPDATE `sa_order` SET `order_bukti`=NULL, `order_buktitgl`=NULL, `order_bukti_note`=NULL WHERE `order_id`=".$order['order_id']);
                      $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH'])==='xmlhttprequest') || (isset($_POST['ajax']) && $_POST['ajax']=='1');
                      if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['ok'=>true,'deleted'=>true]); exit; }
                      echo '<div class="alert alert-info">Bukti transfer dihapus. Anda bisa mengunggah ulang.</div>';
                      $order['order_bukti']=null; $order['order_buktitgl']=null; $order['order_bukti_note']=null;
                    } elseif ($action === 'update_bukti') {
                      db_query("UPDATE `sa_order` SET `order_bukti_note`='".cek($_POST['catatan'] ?? '') ."',`order_buktitgl`=NOW() WHERE `order_id`=".$order['order_id']);
                      $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH'])==='xmlhttprequest') || (isset($_POST['ajax']) && $_POST['ajax']=='1');
                      if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['ok'=>true,'updated'=>true]); exit; }
                      echo '<div class="alert alert-success">Catatan bukti transfer diperbarui.</div>';
                      $order['order_bukti_note'] = $_POST['catatan'] ?? '';
                    }
                  }
                }
                $sudah = false;
                if ($sudah) { echo '<div class="alert alert-primary mb-3" role="alert"><img src="'.$weburl.'img/info.png" alt="Info" style="height:16px;width:16px;object-fit:contain;" class="me-1"> Bukti transfer sudah diupload pada '.($order['order_buktitgl'] ?? '').'.</div>'; }
                $namaUser = isset($order['mem_nama']) ? $order['mem_nama'] : '';
                $nominalProduk = 'Rp '.number_format($totalManual);
                if (is_array($banks) && count($banks)>0) { $detailBayar = $banks[0]['label'].' - '.$banks[0]['account']; } else { $detailBayar = 'Transfer Bank Manual'; }
                $emailUser = isset($order['mem_email']) ? $order['mem_email'] : '';
                $previewMsgHtml = 'Halo min, saya sudah melakukan transfer sejumlah <strong>'.$nominalProduk.'</strong> atas nama '.htmlspecialchars($namaUser).' '.(!empty($emailUser)?'('.htmlspecialchars($emailUser).') ':'').'transfer melalui bank '.$detailBayar.', mohon segera aktifkan pembelian saya';
                $waMsgText = 'Halo min, saya sudah melakukan transfer sejumlah *'.$nominalProduk.'* atas nama '.$namaUser.(!empty($emailUser)?' ('.$emailUser.')':'').' transfer melalui bank '.$detailBayar.', mohon segera aktifkan pembelian saya';
                // UI Upload/Preview/Edit Bukti Transfer
                if (false) {
                  $buktiUrl = $weburl.(string)$order['order_bukti']; $isPdf = (strtolower(pathinfo((string)$order['order_bukti'], PATHINFO_EXTENSION)) === 'pdf');
                  echo '<div class="mt-3">'
                     .'<div class="card">'
                     .'<div class="card-header"><img src="'.$weburl.'img/bukti-transfer.png" alt="Bukti Transfer" style="height:22px;width:22px;object-fit:contain;" class="me-2"> Bukti Transfer</div>'
                     .'<div class="card-body">'
                     .'<div class="d-flex flex-wrap gap-2 mb-3">'
                     .'<button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewBuktiModal"><i class="fas fa-eye me-1"></i> Lihat Bukti Transfer</button>'
                     .'<button type="button" class="btn btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#editBukti"><i class="fas fa-edit me-1"></i> Edit Bukti Transfer</button>'
                     .'</div>'
                     .'<div class="collapse" id="editBukti">'
                     .'<form action="" method="post" enctype="multipart/form-data" id="formEditBukti">'
                     .'<input type="hidden" name="csrf" value="'.htmlspecialchars((isset($_SESSION['epi_nonce']) && is_scalar($_SESSION['epi_nonce'])) ? (string)$_SESSION['epi_nonce'] : '', ENT_QUOTES).'">'
                     .'<div class="mb-2">'
                    .'<label class="form-label">Unggah ulang bukti (JPEG/JPG/PNG/PDF, maks 1MB)</label>'
                    .'<input type="file" name="bukti" accept=".pdf,.jpg,.jpeg,.png" class="form-control">'
                     .'<div class="form-text">File saat ini: '.htmlspecialchars(basename((string)$order['order_bukti'])).'</div>'
                     .'</div>'
                     .'<div class="mb-2">'
                     .'<label class="form-label">Catatan</label>'
                     .'<textarea name="catatan" class="form-control" rows="3" placeholder="Tambahkan catatan...">'.htmlspecialchars($order['order_bukti_note'] ?? '').'</textarea>'
                     .'</div>'
                     .'<div class="d-flex flex-wrap gap-2">'
                     .'<button type="submit" name="action" value="upload_bukti" class="btn btn-primary"><span class="spinner-border spinner-border-sm me-2 d-none" id="spinUpload"></span>Simpan Bukti</button>'
                     .'<button type="submit" name="action" value="update_bukti" class="btn btn-outline-success">Simpan Catatan</button>'
                     .'<button type="submit" name="action" value="delete_bukti" class="btn btn-outline-danger" onclick="return confirm(\'Hapus bukti transfer?\')"><i class="fas fa-trash me-1"></i> Hapus Bukti</button>'
                     .'</div>'
                     .'</form>'
                     .'<div id="uploadStatus" class="mt-2" aria-live="polite"></div><div class="progress mt-2" style="height:8px;"><div id="uploadProgress" class="progress-bar" role="progressbar" style="width:0%" aria-valuemin="0" aria-valuemax="100" aria-label="Progress upload"></div></div>'
                     .'</div>'
                     .'</div>'
                     .'</div>'
                     .'</div>';
                  // Modal Preview
                  echo '<div class="modal fade" id="viewBuktiModal" tabindex="-1" aria-hidden="true">'
                     .'<div class="modal-dialog modal-dialog-centered modal-lg">'
                     .'<div class="modal-content">'
                     .'<div class="modal-header"><h5 class="modal-title">Preview Bukti Transfer</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>'
                     .'<div class="modal-body">'
                     .($isPdf ? '<object data="'.$buktiUrl.'" type="application/pdf" width="100%" height="500" aria-label="Preview PDF"><a class="btn btn-primary" target="_blank" href="'.$buktiUrl.'">Buka PDF</a></object>'
                              : '<img src="'.$buktiUrl.'" alt="Bukti Transfer" style="max-width:100%;height:auto;border-radius:8px" loading="lazy"/>')
                     .'</div>'
                     .'<div class="modal-footer"><a class="btn btn-outline-secondary" href="'.$buktiUrl.'" target="_blank">Buka di Tab Baru</a></div>'
                     .'</div>'
                     .'</div>'
                     .'</div>';
                } if (false) {
                  // Form upload awal jika belum ada bukti
                  echo '<div class="mt-3"><div class="card"><div class="card-header"><img src="'.$weburl.'img/bukti-transfer.png" alt="Bukti Transfer" style="height:22px;width:22px;object-fit:contain;" class="me-2"> Upload Bukti Transfer</div><div class="card-body">'
                      .'<form action="" method="post" enctype="multipart/form-data" id="formUploadBukti">'
                      .'<input type="hidden" name="csrf" value="'.htmlspecialchars((isset($_SESSION['epi_nonce']) && is_scalar($_SESSION['epi_nonce'])) ? (string)$_SESSION['epi_nonce'] : '', ENT_QUOTES).'">'
                    .'<div class="mb-2"><input type="file" name="bukti" accept=".pdf,.jpg,.jpeg,.png" class="form-control" required></div>'
                      .'<div class="mb-2"><textarea name="catatan" class="form-control" rows="3" placeholder="Tambahkan catatan jika diperlukan..."></textarea></div>'
                      .'<button type="submit" name="action" value="upload_bukti" class="btn btn-primary"><span class="spinner-border spinner-border-sm me-2 d-none" id="spinUploadInit"></span>Upload Bukti Transfer</button>'
                      .'<div id="uploadStatus" class="mt-2" aria-live="polite"></div><div class="progress mt-2" style="height:8px;"><div id="uploadProgress" class="progress-bar" role="progressbar" style="width:0%" aria-valuemin="0" aria-valuemax="100" aria-label="Progress upload"></div></div>'
                      .'</form>'
                    .'<div class="mt-3"><div class="alert alert-secondary"><strong>Panduan upload:</strong><br>- Siapkan file JPEG/JPG/PNG/PDF dengan ukuran ≤1MB.<br>- Klik "Upload Bukti Transfer" dan tunggu sampai muncul verifikasi hijau.<br>- Jika gagal dan tidak ada perubahan, cek format/ukuran dan coba ulangi lagi.<br>- Anda juga bisa kirim bukti transfer via CS kami melalui tombol WhatsApp di bawah.</div></div>'
                      .'</div></div></div>';
                }
                // Client-side validation & UX
                if(false) echo <<<JS
<script>
(function(){
  function validateFile(input){
    const f=input.files[0]; if(!f){return true;}
    const okTypes=["image/jpeg","image/png","application/pdf"];
    const name=(f.name||'').toLowerCase();
    const ext=name.split('.').pop();
    const okExt=["jpg","jpeg","png","pdf"];
    const typeOk=(okTypes.indexOf(f.type)!==-1) || (okExt.indexOf(ext)!==-1);
    if(!typeOk){ alert("Format file harus JPEG/JPG/PNG/PDF"); input.value=""; return false;}
    if(f.size>1024*1024){ alert("Ukuran file maksimal 1MB"); input.value=""; return false;}
    return true;
  }
  function setBadge(state,msg){
    var el=document.getElementById("uploadStatus"); if(!el) return;
    var html="";
    if(state==="loading"){ html='<span class="badge bg-warning text-dark"><span class="spinner-border spinner-border-sm me-1"></span>'+msg+'</span>'; }
    else if(state==="success"){ html='<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>'+msg+'</span>'; }
    else if(state==="error"){ html='<span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i>'+msg+'</span>'; }
    el.innerHTML=html;
  }
  function verifyUpload(url){
    setBadge('loading','Memverifikasi unggahan...');
    try{
      fetch(url,{method:'HEAD',cache:'no-store'}).then(function(r){
        if(r && r.ok){ setBadge('success','Upload berhasil diverifikasi'); }
        else { setBadge('error','Upload gagal diverifikasi'); }
      }).catch(function(){ setBadge('error','Upload gagal diverifikasi'); });
    }catch(e){ setBadge('error','Upload gagal diverifikasi'); }
  }
  const form1=document.getElementById("formUploadBukti");
  const form2=document.getElementById("formEditBukti");
  [form1,form2].forEach(function(frm){ if(!frm) return;
    const fileInput=frm.querySelector("input[type=file]");
    if(fileInput){ fileInput.addEventListener("change",function(){ validateFile(fileInput); }); }
    frm.addEventListener("submit",function(ev){
      ev.preventDefault();
      const btn=frm.querySelector("button[type=submit][name=action]");
      const spin=frm.querySelector("#"+(frm.id==="formUploadBukti"?"spinUploadInit":"spinUpload"));
      const prog=document.getElementById("uploadProgress");
      if(spin){ spin.classList.remove("d-none"); }
      if(btn){ btn.setAttribute("disabled","disabled"); btn.classList.add("disabled"); }
      setBadge('loading','Mengunggah berkas...');
      const fd=new FormData(frm);
      fd.append('ajax','1');
      const xhr=new XMLHttpRequest();
      xhr.open('POST', window.location.href, true);
      xhr.setRequestHeader('X-Requested-With','XMLHttpRequest');
      xhr.upload.onprogress=function(e){ if(e.lengthComputable && prog){ var p=Math.round((e.loaded/e.total)*100); prog.style.width=p+'%'; prog.setAttribute('aria-valuenow',p); }};
      xhr.onreadystatechange=function(){ if(xhr.readyState===4){ if(spin){ spin.classList.add("d-none"); } if(btn){ btn.removeAttribute("disabled"); btn.classList.remove("disabled"); }
          try{ var j=JSON.parse(xhr.responseText||'{}'); if(j && j.ok){ setBadge('success','Upload berhasil'); prog.style.width='100%'; if(j.url){ verifyUpload(j.url); } setTimeout(function(){ window.location.reload(); }, 600); }
          else { var msg = (j && (j.message||j.error)) ? ('Upload gagal: '+(j.message||j.error)) : 'Upload gagal'; setBadge('error', msg); }
          }catch(e){ setBadge('error','Upload gagal'); }
        }};
      xhr.onerror=function(){ setBadge('error','Jaringan bermasalah. Coba lagi.'); };
      xhr.send(fd);
    });
  });
  if(window.__uploadedFileUrl){ verifyUpload(window.__uploadedFileUrl); }
})();
</script>
JS;
                
                echo '<div class="mt-3"><div class="text-center"><div class="text-muted mb-2">Jika sudah melakukan pembayaran, silakan konfirmasikan pembayaran Anda melalui tombol berikut:</div><a class="btn btn-primary" href="'.$weburl.'konfirmasi/'.$order['order_id'].'"><img src="'.$weburl.'/img/konfirmasi-payment.png" alt="Konfirmasi" style="height:20px;width:20px;object-fit:contain" class="me-1"> Konfirmasi Pembayaran Anda</a></div></div>';
              }            
            } else {
              $apiKey = $settings['tripay_api'];
              $payload = ['reference' => $order['order_trx']];

              $curl = curl_init();

              curl_setopt_array($curl, [
                  CURLOPT_FRESH_CONNECT  => true,
                  CURLOPT_URL            => 'https://tripay.co.id/'.$urlapi.'/transaction/detail?'.http_build_query($payload),
                  CURLOPT_RETURNTRANSFER => true,
                  CURLOPT_HEADER         => false,
                  CURLOPT_HTTPHEADER     => ['Authorization: Bearer '.$apiKey],
                  CURLOPT_FAILONERROR    => false,
                  CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4
              ]);

              $response = curl_exec($curl);
              $error = curl_error($curl);

              curl_close($curl);

              $hasil = empty($error) ? $response : $error;
              $arrhasil = json_decode($hasil,TRUE);

              if (isset($arrhasil['data'])) {
                $datatri = $arrhasil['data'];                
                $carabayar = '
              <h3>Cara Pembayaran</h3>
              <div class="accordion" id="metodebayar">';
                if ($datatri['payment_method'] == 'SHOPEEPAY' || $datatri['payment_method'] == 'OVO' || $datatri['payment_method'] == 'DANA') {
                  
                  $detil = '<a href="'.$datatri['checkout_url'].'" target="_blank" class="btn btn-success">Lanjutkan Pembayaran '.$datatri['payment_name'].'</a>';

                } else {
                  if (isset($datatri['qr_url'])) {
                  $detil = '<img src="'.$datatri['qr_url'].'" alt="QR" class="img-fluid"/>';
                  } else {
                    $detil = '
                    <div class="table-responsive">
                      <table class="table table-striped table-bordered">
                        <thead class="table-dark">
                          <th>'.$datatri['payment_name'].'</th>
                        </thead>
                        <tbody>
                          <tr><td>No. Reference</td></tr>
                          <tr><td>'.$datatri['reference'].'</td></tr>
                          <tr><td>No. Virtual Account</td></tr>
                          <tr><td><a onclick="copyToClipboard(\''.$datatri['pay_code'].'\', this)" style="text-decoration:none;cursor: pointer;" 
              title="Copy to Clipboard">'.$datatri['pay_code'].' &nbsp; <i class="fa-regular fa-copy"></i></a></td></tr>
                          <tr><td>Jumlah Transfer</td></tr>
                          <tr><td><a onclick="copyToClipboard(\''.$datatri['amount'].'\', this)" style="text-decoration:none;cursor: pointer;" 
              title="Copy to Clipboard">'.number_format($datatri['amount']).' &nbsp; <i class="fa-regular fa-copy"></i></a></td></tr>                      
                        </tbody>
                      </table>
                    </div>
                    ';
                  }
                }

                $acc = 1;
                foreach ($datatri['instructions'] as $instruksi) {
                  if ($acc == 1) { $s = ' show'; } else { $s = ''; }
                  $carabayar .= '
                  <div class="accordion-item">
                    <h2 class="accordion-header">
                      <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse'.$acc.'" aria-expanded="true" aria-controls="collapse'.$acc.'">
                        '.$instruksi['title'].'
                      </button>
                    </h2>
                    <div id="collapse'.$acc.'" class="accordion-collapse collapse'.$s.'" data-bs-parent="#metodebayar">
                      <div class="accordion-body">
                        <ol>';
                        foreach ($instruksi['steps'] as $step) {
                          $carabayar .= '<li>'.$step.'</li>';
                        }
                        $carabayar .= '
                        </ol>
                      </div>
                    </div>
                  </div>
                  ';
                  $acc++;
                }
                
                $carabayar .= '</div>';
              } else {
                include('payment.php');
              }              
            }
          }

          echo $detil??=''; 
          ?>
          </div>
        </div>
        <script>
        (function(){
          try {
            setInterval(function(){
              fetch('<?=$weburl;?>upload/settings/bank_accounts.json', {cache:'no-store'})
                .then(function(r){ return r.json(); })
                .then(function(j){ var cont = document.getElementById('bankList'); if (!cont||!j||!j.data) return; var html=''; var logoMap = {bca:'<?=$weburl;?>img/bank/bca.png', mandiri:'<?=$weburl;?>img/bank/mandiri.png', bri:'<?=$weburl;?>img/bank/bri.png', bni:'<?=$weburl;?>img/bank/bni.png', bsi:'<?=$weburl;?>img/bank/bsi.png'}; j.data.forEach(function(b){ var code=(b.code||'').toString().toLowerCase(); var logo=logoMap[code]||''; var img = logo ? '<img src="'+logo+'" alt="Logo '+(b.label||code)+'" loading="lazy" onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'inline-block\';" />' : ''; var fallback = '<i class="fas fa-building-columns" style="display:'+(logo?'none':'inline-block')+'"></i>'; html += '<div class="method-card mb-2">'+img+fallback+'<div><div class="title">'+(b.label||code)+'</div><div class="subtitle">a.n. '+(b.owner||'')+'</div><div class="account">No. Rekening: <span class="font-monospace">'+(b.account||'')+'</span></div></div><div class="ms-auto"><a onclick="copyToClipboard(\''+(b.account||'')+'\', this)" class="btn btn-sm btn-outline-secondary">Copy Nomor</a></div></div>'; }); cont.innerHTML = html; });
            }, 60000);
          } catch(e){}
        })();
        </script>
        <div class="row mt-3">
          <div class="col p-md-3">
            <?php
            echo $carabayar??='';            
            ?>
          </div>
        </div>
        <div class="row mt-3">
          <div class="col text-center">
			<?php if ($isLunasDisplay) : ?>
			  <a href="<?=$weburl.'dashboard/akses/'.$order['page_url'];?>" class="btn btn-primary me-2">AKSES PRODUK</a>
			<?php endif; ?>
            <a href="<?=$weburl;?>dashboard">Back to Dashboard</a>
          </div>
        </div>
      </div>
    </div>
  </div>
<script>
  async function copyToClipboard(text, btnEl) {
    try {
      // Gunakan Clipboard API modern jika tersedia
      if (navigator.clipboard && window.isSecureContext) {
        await navigator.clipboard.writeText(text);
      } else {
        // Fallback untuk browser lama
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        document.execCommand('copy');
        textArea.remove();
      }
      
      // Indikator langsung pada tombol (jika disediakan)
      try {
        if (btnEl) {
          var original = btnEl.textContent;
          btnEl.style.transition = 'all 200ms ease';
          btnEl.classList.remove('btn-outline-secondary');
          btnEl.classList.add('btn-success');
          btnEl.textContent = 'Link Tersalin';
          setTimeout(function(){
            btnEl.textContent = original;
            btnEl.classList.remove('btn-success');
            btnEl.classList.add('btn-outline-secondary');
          }, 2000);
        }
      } catch(e) { /* noop */ }

    } catch (err) {
      console.error('Gagal menyalin teks: ', err);
      // Fallback jika semua metode gagal
      alert('Gagal menyalin teks. Silakan salin manual.');
    }
  }
  // Pasang handler untuk tombol Copy Total di ringkasan
  (function(){
    var btnTotal = document.getElementById('btnCopyTotal');
    if (btnTotal) {
      btnTotal.addEventListener('click', function(){
        var amount = btnTotal.getAttribute('data-amount') || (document.getElementById('totalAmountText')?.textContent || '');
        copyToClipboard(amount, btnTotal);
      });
    }
  })();
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" 
integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous"></script>
</body>
</html>
<?php
  else:
    echo 'Invoice tidak ditemukan';
  endif;
endif;
?>
