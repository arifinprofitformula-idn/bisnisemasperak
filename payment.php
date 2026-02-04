<?php
if (isset($_GET['pay']) && !empty($_GET['pay'])) {
  if ($_GET['pay'] == 'manual') {
    if (isset($settings['carapembayaran'])) {
      // Hitung Harga Normal, Diskon, dan Harga Tampil sesuai aturan baru (hormati kupon tersimpan di order)
      $hargaNormal = (isset($order['order_harga']) && is_numeric($order['order_harga'])) ? (int)$order['order_harga'] : ((isset($order['pro_harga']) && is_numeric($order['pro_harga'])) ? (int)$order['pro_harga'] : 0);
      $couponCode  = isset($order['order_promo_code']) ? trim($order['order_promo_code']) : '';
      $storedDisplay = (isset($order['order_price_display']) && is_numeric($order['order_price_display'])) ? (int)$order['order_price_display'] : null;
      $baseDisplay   = (isset($order['pro_harga_display']) && is_numeric($order['pro_harga_display'])) ? (int)$order['pro_harga_display'] : $hargaNormal;
      $eff           = epi_effective_price((int)$hargaNormal, (int)$baseDisplay, $couponCode, (int)$order['order_idproduk'], 1);
      $hargaTampil   = ($storedDisplay !== null) ? $storedDisplay : (int)$eff['price'];
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
      db_query("UPDATE `sa_order` SET `order_trx`='manual' WHERE `order_id`=".$order['order_id']);
      $sukses = 1;
    }
  } else {
    $apiKey       = $settings['tripay_api'];
    $privateKey   = $settings['tripay_private'];
    $merchantCode = $settings['tripay_merchant'];
    $merchantRef  = str_pad($order['order_id'],4,0,STR_PAD_LEFT);
    // Gunakan harga tampil (harga akhir) sebagai amount sesuai aturan baru, hormati kupon tersimpan
    $hargaNormal = (isset($order['order_harga']) && is_numeric($order['order_harga'])) ? (int)$order['order_harga'] : ((isset($order['pro_harga']) && is_numeric($order['pro_harga'])) ? (int)$order['pro_harga'] : 0);
    $couponCode  = isset($order['order_promo_code']) ? trim($order['order_promo_code']) : '';
    $storedDisplay = (isset($order['order_price_display']) && is_numeric($order['order_price_display'])) ? (int)$order['order_price_display'] : null;
    $baseDisplay   = (isset($order['pro_harga_display']) && is_numeric($order['pro_harga_display'])) ? (int)$order['pro_harga_display'] : $hargaNormal;
    $eff           = epi_effective_price((int)$hargaNormal, (int)$baseDisplay, $couponCode, (int)$order['order_idproduk'], 1);
    $hargaTampil   = ($storedDisplay !== null) ? $storedDisplay : (int)$eff['price'];
    $amount        = $hargaTampil;

    # Instruksi Pembayaran
    $data = [
        'method'         => $_GET['pay'],
        'merchant_ref'   => $merchantRef,
        'amount'         => $amount,
        'customer_name'  => $order['mem_nama'],
        'customer_email' => $order['mem_email'],
        'customer_phone' => $order['mem_whatsapp'],
        'order_items'    => [
            [
                'sku'         => 'PRO-'.$order['page_id'],
                'name'        => $order['page_judul'],
                'price'       => $hargaTampil,
                'quantity'    => 1,
                'product_url' => $weburl.'produk/'.$order['page_url']
            ]
        ],
        'return_url'   => $weburl.'thanks',
        'expired_time' => (time() + (24 * 60 * 60)), // 24 jam
        'signature'    => hash_hmac('sha256', $merchantCode.$merchantRef.$amount, $privateKey)
    ];

    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_FRESH_CONNECT  => true,
        CURLOPT_URL            => 'https://tripay.co.id/'.$urlapi.'/transaction/create',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => false,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer '.$apiKey],
        CURLOPT_FAILONERROR    => false,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($data),
        CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4
    ]);

    $response = curl_exec($curl);
    $error = curl_error($curl);

    curl_close($curl);
    $hasil = empty($error) ? $response : $error;
    $arrhasil = json_decode($hasil,TRUE);


    if (isset($arrhasil['success']) && $arrhasil['success'] == 1) {
      # Simpan ke database
      db_query("UPDATE `sa_order` SET `order_trx`='".$arrhasil['data']['reference']."' WHERE `order_id`=".$order['order_id']);
      $sukses = 1;
    }
  }

  if (isset($sukses)) {
    echo '
      <script type="text/javascript">
      <!--
      window.location = "'.$weburl.'invoice/'.$order['order_id'].'"
      //-->
      </script>';
  }
} else {
  echo '
  <div class="d-grid gap-3">';
  if (isset($settings['carapembayaran'])) { echo '
    <a href="?pay=manual" class="method-card">
    <img src="'.$weburl.'img/bank-transfer.jpg" alt=""/>
    <div><div class="title">Transfer Bank</div><div class="subtitle">Transfer ke rekening</div></div>
    </a>'; 
  }
  if (isset($settings['tripay_merchant']) && !empty($settings['tripay_merchant'])) {
    if (isset($settings['tripay_sandbox']) && $settings['tripay_sandbox'] == 1) {
      $urlapi = 'api-sandbox';
    } else {
      $urlapi = 'api'; 
    }

    # Daftar Metode Pembayaran
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_FRESH_CONNECT  => true,
      CURLOPT_URL            => 'https://tripay.co.id/'.$urlapi.'/merchant/payment-channel',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_HEADER         => false,
      CURLOPT_HTTPHEADER     => ['Authorization: Bearer '.$settings['tripay_api']],
      CURLOPT_FAILONERROR    => false,
      CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4
    ));

    $response = curl_exec($curl);
    $error = curl_error($curl);

    curl_close($curl);

    $hasil = empty($error) ? $response : $error;
    $arrhasil = json_decode($hasil,TRUE);
    
    if (isset($arrhasil['success']) && $arrhasil['success'] == 1) {
      
      foreach ($arrhasil['data'] as $payment) {
        $fee = $payment['fee_customer']['flat'] + (($payment['fee_customer']['percent']/100) * $hargaTampil);
        echo '
        <a href="?pay='.$payment['code'].'" class="method-card">
        <img src="'.$payment['icon_url'].'" alt=""/>
        <div><div class="title">'.$payment['name'].'</div><div class="subtitle">Fee admin: Rp. '.number_format($fee).'</div></div>
        </a>';
      }            
    } else {
      echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Error!</strong> '.$arrhasil['message'].'
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>';
    }
  }
  echo '</div>';
}
?>
