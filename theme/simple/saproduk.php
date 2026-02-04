<?php 
// Bootstrap fallback when accessed directly (outside router/openpage)
$__root = dirname(__DIR__, 2);
if (!isset($weburl)) { @include_once $__root . DIRECTORY_SEPARATOR . 'config.php'; }
if (!function_exists('getsettings')) { @include_once $__root . DIRECTORY_SEPARATOR . 'fungsi.php'; }
if (session_status() === PHP_SESSION_NONE) { @session_start(); }
if (!isset($settings) || !is_array($settings)) { $settings = getsettings(); }
if (!isset($datasponsor) || !is_array($datasponsor)) { $datasponsor = []; }
if (!isset($datamember) || !is_array($datamember)) { $datamember = []; }
// Pastikan struktur menu tersedia untuk render header/nav saat akses langsung
if (!isset($menu) || !is_array($menu)) { @include_once $__root . DIRECTORY_SEPARATOR . 'menudata.php'; }

$head['pagetitle'] = ucwords(isset($settings['url_produk']) ? $settings['url_produk'] : 'produk');
$head['container'] = 'container-fluid';
$head['scripthead'] = '';

$pixelId = '';
if (!empty($datasponsor['fbpixel'])) {
    $pixelId = htmlspecialchars($datasponsor['fbpixel'], ENT_QUOTES);
} elseif (!empty($settings['fbpixel'])) {
    $pixelId = htmlspecialchars($settings['fbpixel'], ENT_QUOTES);
}
if (!empty($pixelId)) {
    $head['scripthead'] .= '
    <!-- Meta Pixel Code -->
    <script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version=\'2.0\';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,\'script\',
    \'https://connect.facebook.net/en_US/fbevents.js\');
    fbq(\'init\', \'" . $pixelId . "\');
    fbq(\'track\', \'PageView\');
    </script>
    <noscript><img height="1" width="1" style="display:none"
    src="https://www.facebook.com/tr?id=' . $pixelId . '&ev=PageView&noscript=1"
    /></noscript>
    <!-- End Meta Pixel Code -->
    ';
}

$gtmId = '';
if (!empty($datasponsor['gtm'])) {
    $gtmId = htmlspecialchars($datasponsor['gtm'], ENT_QUOTES);
} elseif (!empty($settings['gtm'])) {
    $gtmId = htmlspecialchars($settings['gtm'], ENT_QUOTES);
}
if (!empty($gtmId)) {
    $head['scripthead'] .= '
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({\'gtm.start\':
    new Date().getTime(),event:\'gtm.js\'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!=\'dataLayer\'?\'&l=\'+l:\'\';j.async=true;j.src=
    \'https://www.googletagmanager.com/gtm.js?id=\'+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,\'script\',\'dataLayer\',\'GTM-' . $gtmId . '\');</script>
    <!-- End Google Tag Manager -->
    ';
}

showheader($head);
?>
    <!-- Desktop-only visual enhancements for /product page (min-width:1024px) -->
    <style>
      /* Brand tokens */
      :root {
        --epi-gold: #D4AF37;
        --epi-gold-700: #B8942E;
        --epi-gold-100: #FFF7E0;
        --epi-black: #0B0B0B;
        --epi-white: #F8F8F8;
      }
      /* Icon base styles (all viewports) */
      .product-btn .btn-icon { display:inline-flex; align-items:center; justify-content:center; width:20px; height:20px; margin-right:.5rem; line-height:1; font-size:18px; color:#fff !important; }
      .product-btn .btn-text { display:inline-block; color:#fff !important; }
      .product-grid { overflow-x: hidden; }
      .product-card { max-width:100%; }
      /* Unified button colors (all viewports) */
      .product-btn.info-btn { border-bottom-left-radius: 6px; background-image: linear-gradient(to bottom, rgba(255,255,255,.22), rgba(255,255,255,0) 45%), linear-gradient(to bottom, #49a749 0%, #2f7d2f 100%); }
      .product-btn.primary-btn { border-bottom-right-radius: 6px; color: #1a1a1a !important; font-weight: 600; background-image: linear-gradient(to bottom, rgba(255,255,255,.22), rgba(255,255,255,0) 45%), linear-gradient(to bottom, #E6C76A 0%, #B8942E 100%); }
      /* Base 3D gradient and depth for all buttons */
      .product-btn { position: relative; border-radius: 6px; background-image: linear-gradient(to bottom, rgba(255,255,255,.22), rgba(255,255,255,0) 45%); box-shadow: 0 2px 0 rgba(0,0,0,.15), 0 8px 16px rgba(0,0,0,.08); transition: transform .15s ease, box-shadow .15s ease, filter .15s ease; padding:.65rem .95rem; }
      .product-btn:hover { transform: translateY(-1px); box-shadow: 0 3px 0 rgba(0,0,0,.16), 0 10px 20px rgba(0,0,0,.10); }
      .product-btn:active { transform: translateY(0); box-shadow: 0 1px 0 rgba(0,0,0,.18), 0 6px 12px rgba(0,0,0,.10); }
      .product-btn.is-disabled { opacity: .55; box-shadow: none; }
      /* Desktop enhancements only */
      /* Square ratio for all devices */
      .product-card .product-img { aspect-ratio: 1/1; }
      @media (min-width: 1024px) {
        /* Grid spacing and layout */
        .product-grid { row-gap: 1.25rem; }
        .product-card { border: 1px solid #e7dfc2; background: #fff; transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease; }
        .product-card:hover { transform: translateY(-2px); box-shadow: 0 8px 18px rgba(0,0,0,.08); border-color: var(--epi-gold); }

        /* Product image ratio and framing */
        .product-card .product-img { aspect-ratio: 1/1; background: #faf7ef; }
        .product-card .product-img img { width: 100%; height: 100%; object-fit: cover; border-top-left-radius: 6px; border-top-right-radius: 6px; }

        /* Title & description spacing */
        .product-card .text-center { padding: 1rem .75rem !important; }
        .product-title { font-family: 'Poppins', sans-serif; font-size: 1.125rem; margin-bottom: .5rem; text-align:left; color:#0B0B0B; font-weight:700; }
        .product-desc { font-family: 'Poppins', sans-serif; font-size: .95rem; color: #444; text-align:left; }

        /* Action bar */
        .product-card .d-flex { display: flex; align-items: stretch; }
        .product-btn { display: inline-flex; flex: 1 1 auto; align-items: center; justify-content: center; padding: .7rem 1rem; text-decoration: none; color: #fff; border: 0; }
        /* INFO: green 3D gradient */
        .product-btn.info-btn { border-bottom-left-radius: 6px; background-image: linear-gradient(to bottom, rgba(255,255,255,.22), rgba(255,255,255,0) 45%), linear-gradient(to bottom, #49a749 0%, #2f7d2f 100%); }
        .product-btn.info-btn:hover { filter: brightness(1.02); }
        /* ORDER/AKSES: gold 3D gradient on desktop */
        .product-btn.primary-btn { border-bottom-right-radius: 6px; color: #1a1a1a !important; font-weight: 600; background-image: linear-gradient(to bottom, rgba(255,255,255,.22), rgba(255,255,255,0) 45%), linear-gradient(to bottom, #E6C76A 0%, #B8942E 100%); }
        .product-btn.primary-btn:hover { filter: brightness(1.03); }

        /* Pagination refinements */
        .pagination .page-link { transition: border-color .2s ease, color .2s ease; }
        .pagination .page-item.active .page-link { background-color: var(--epi-gold); border-color: var(--epi-gold); color: #111; }
        .pagination .page-link:hover { border-color: var(--epi-gold); color: var(--epi-gold); }
      }
      /* Mobile & tablet typography (global) */
      .product-title { font-family: 'Poppins', sans-serif; font-size: 1.125rem; margin-bottom: .5rem; text-align:left; color:#0B0B0B; font-weight:700; }
      .product-desc { font-family: 'Poppins', sans-serif; font-size: .95rem; color: #333; text-align:left; }
      .product-title, .product-desc { overflow-wrap:anywhere; }
      /* Box sizing and card equal height */
      .product-card, .product-card * { box-sizing: border-box; }
      .product-grid > div { display: flex; }
      .product-grid .product-card { height: 100%; width: 100%; display: flex; flex-direction: column; }
      .product-body { flex: 1 1 auto; }
      /* Price block */
      .product-price { display:block; padding: 0 .75rem .5rem .75rem; }
      .product-price .price-row { display:flex; align-items:flex-start; justify-content:space-between; gap:.75rem; }
      .product-price .price-label-group { display:flex; flex-direction:column; align-items:flex-start; gap:.25rem; }
      .product-price .price-label { font-family: 'Poppins', sans-serif; font-size:.875rem; font-weight:600; color:#555; text-transform:uppercase; }
      .product-price .price-values { text-align:right; }
      .product-search-input { background:#FFF7E0; border:1px solid #E6C76A; color:#0B0B0B; }
      .product-search-input::placeholder { color:#777; }
      .product-search-input:focus { outline:0; box-shadow:0 0 0 .2rem rgba(212,175,55,.25); border-color:#D4AF37; }
      .price-normal { color:#666; text-decoration: line-through; font-size:.95rem; }
      .price-promo { color: var(--epi-gold); font-weight:600; font-size:1.05rem; }
      .price-current { color:#111; font-weight:600; font-size:1.05rem; }
      .price-promo-badge { display:inline-block; padding:.15rem .5rem; margin:.25rem 0 .15rem; font-size:.75rem; font-weight:600; color:#B8942E; background:#FFF7E0; border:1px solid #E6C76A; border-radius:.25rem; }
      .price-free-label { display:inline-block; margin-top:.35rem; padding:.2rem .55rem; font-size:.78rem; font-weight:700; color:#0B0B0B; background: var(--epi-gold); border:1px solid var(--epi-gold-700); border-radius:.25rem; }
      /* Actions full width */
      .product-actions { display:flex; flex-direction: column; gap:.5rem; padding: 0 .75rem .75rem; }
      .product-actions .product-btn { width: 100%; margin: 0; }
      /* Desktop: buttons side-by-side */
      @media (min-width: 1024px) {
        .product-actions { flex-direction: row; }
        .product-actions .product-btn { width: auto; flex: 1 1 0; }
      }
    </style>
    <form action="" method="get" class="px-xl-5">
    <div class="card mb-3">
      <div class="card-body">
        <div class="row">     
          <div class="col-12">
            <div class="input-group">
              <input type="text" class="form-control product-search-input" name="cari" value="<?= $_GET['cari'] ??= '';?>" placeholder="Cari produk...">
              <?php 
              $select = array('','','');
              if (isset($_GET['status']) && is_numeric($_GET['status'])) {
                $select[$_GET['status']] = ' selected';
              }
              ?>
              <input type="submit" value=" Cari " class="btn btn-secondary">
            </div>        
          </div>
        </div>
      </div>
    </div>
    </form>

    <div class="row g-3 px-xl-5 product-grid">
			<?php
        $jmlperpage = 25;
        if (isset($_GET['start']) && is_numeric($_GET['start'])) {
            $start = ($_GET['start'] - 1) * $jmlperpage;
            $page = $_GET['start'];
        } else {
            $start = 0;
            $page = 1;
        }   
        
        $where = '';
        if (isset($_GET['cari']) && !empty($_GET['cari'])) {
          $s = cek($_GET['cari']);
          $where = "AND (`page_judul` LIKE '%".$s."%' 
                    OR `page_diskripsi` LIKE '%".$s."%'
                    OR `page_url` LIKE '%".$s."%')";
        }

        $data = db_select("SELECT * FROM `sa_page` 
          WHERE `pro_harga` IS NOT NULL 
          AND `pro_status`=1 ".$where."
          ORDER BY `page_judul` ASC
          LIMIT ".$start.",".$jmlperpage);

				if (count($data) > 0) {

          foreach ($data as $data) {          
              // Tentukan apakah produk memiliki URL Sales Page yang diatur
              $hasSalesPage = (isset($data['page_iframe']) && !empty($data['page_iframe']));
              $salesHref    = $weburl.$data['page_url'];

              echo '
              <div class="col-12 col-sm-6 col-lg-3 pb-3">
                <div class="bg-light mb-4 rounded-3 shadow product-card" style="border-radius: 5px;"> <!-- Container utama dengan rounded -->
                  <div class="product-img position-relative overflow-hidden">';
              // Gambar produk dengan kondisi klik
              if ($hasSalesPage) {
                echo '<a href="'.$salesHref.'">';
              }
              if (isset($data['pro_img']) && !empty($data['pro_img'])) {
                echo '<img src="'.$weburl.'upload/'.$data['pro_img'].'" class="img-fluid w-100" style="border-top-left-radius: 5px; border-top-right-radius: 5px;" alt="'.htmlspecialchars($data['page_judul'], ENT_QUOTES).'"/>';
              }
              if ($hasSalesPage) {
                echo '</a>';
              }
              echo '
                  </div>
                  <div class="py-4 px-3 mb-2 product-body">';
              // Judul produk dengan kondisi klik
              if ($hasSalesPage) {
                echo '<a href="'.$salesHref.'" class="text-decoration-none text-dark"><h4 class="product-title">'.htmlspecialchars($data['page_judul'], ENT_QUOTES).'</h4></a>';
              } else {
                echo '<h4 class="product-title">'.htmlspecialchars($data['page_judul'], ENT_QUOTES).'</h4>';
              }
              echo '<p class="product-desc">'.htmlspecialchars($data['page_diskripsi'] ?? '', ENT_QUOTES).'</p>
                  </div>
                  '; 
              // Price block
              $basePrice = isset($data['pro_harga']) && is_numeric($data['pro_harga']) ? (int)$data['pro_harga'] : 0;
              $promoPrice = null;
              if (isset($data['pro_harga_display']) && $data['pro_harga_display'] !== '' && is_numeric($data['pro_harga_display'])) {
                $promoPrice = (int)$data['pro_harga_display'];
              }
              echo '<div class="px-3 pb-2 product-price"><div class="price-row"><div class="price-label-group"><div class="price-label">HARGA PRODUK</div>';
              if ($promoPrice !== null && $promoPrice > 0 && $promoPrice < $basePrice) { echo '<div class="price-promo-badge">PROMO HANYA</div>'; }
              if ($promoPrice !== null && (int)$promoPrice === 0 && $basePrice > 0) { echo '<div class="price-promo-badge">Akses Gratis</div>'; }
              echo '</div><div class="price-values">';
              if ($promoPrice !== null && $promoPrice > 0 && $promoPrice < $basePrice) {
                echo '<div class="price-normal">Rp '.number_format($basePrice).'</div>';
                echo '<div class="price-promo">Rp '.number_format($promoPrice).'</div>';
              } elseif ($promoPrice !== null && (int)$promoPrice === 0 && $basePrice > 0) {
                echo '<div class="price-normal">Rp '.number_format($basePrice).'</div>';
                echo '<div class="price-promo">Rp 0</div>';
              } else {
                echo '<div class="price-current">Rp '.number_format($basePrice).'</div>';
              }
              echo '</div></div></div>';
              echo '<div class="product-actions">';
              // Action buttons (desktop styling via CSS classes; mobile unchanged)
              // INFO button: disabled when no sales page
              if ($hasSalesPage) {
                echo '<a href="'.$salesHref.'" class="product-btn info-btn"><span class="btn-icon" aria-hidden="true">ℹ️</span><span class="btn-text">SELENGKAPNYA</span></a>';
              } else {
                echo '<a href="#" class="product-btn info-btn is-disabled" style="pointer-events: none; cursor: default;"><span class="btn-icon" aria-hidden="true">ℹ️</span><span class="btn-text">SELENGKAPNYA</span></a>';
              }
              echo ' 
                    ';
              // Tentukan apakah member sudah memiliki akses (order valid atau akses gratis)
              $hasAccess = false;
              if (isset($datamember['mem_id'])) {
                $hargaTampil = (isset($data['pro_harga_display']) && $data['pro_harga_display'] !== '' ? $data['pro_harga_display'] : $data['pro_harga']);
                $hasAccess = (
                  // Akses gratis eksplisit
                  (isset($data['pro_free_access']) && $data['pro_free_access'] == 1)
                  // Order sudah lunas
                  || (db_var("SELECT COUNT(*) FROM `sa_order` WHERE `order_idproduk`=".(int)$data['page_id']." AND `order_idmember`=".(int)$datamember['mem_id']." AND (`order_status`=1 OR (`order_hargaunik`=0 AND `order_trx`='free'))") > 0)
                  // Harga tampil 0 (gratis setelah diskon)
                  || ($hargaTampil == 0)
                );
              }
              $order_link = $weburl.'order/'.$data['page_url'];
              $akses_link = $weburl.'dashboard/akses/'.$data['page_url'];
              // Primary action: ORDER or AKSES with gold styling on desktop
              $orderIcon = $hasAccess ? '✔️' : '🛒';
              echo '<a href="'.($hasAccess ? $akses_link : $order_link).'" class="product-btn primary-btn"><span class="btn-icon" aria-hidden="true">'.$orderIcon.'</span><span class="btn-text">'.($hasAccess ? 'AKSES' : 'ORDER').'</span></a>
                  </div>
                </div>
              </div>
              ';
          }

				}
			?>
    </div>


  <?php
  $jmlproduk = db_var("SELECT * FROM `sa_page` 
        WHERE `pro_harga` IS NOT NULL ".$where);
  $jmlpage = ceil($jmlproduk/$jmlperpage);
  echo '
  <nav aria-label="Page navigation" class="mt-3">
    <ul class="pagination">';
  if ($jmlpage > 10) {
    if ($page <= 4){
      # Depan
      for ($i=1;$i<=5;$i++) {
          if ($i == $page) {
              echo '<li class="page-item active"><a class="page-link" href="?start='.$i.'">'.$i.'<span class="sr-only">(current)</span></a></li>';
          } else {
              echo '<li class="page-item"><a class="page-link" href="?start='.$i.'">'.$i.'</a></li>';
          }
      }
      echo '
      <li class="page-item disabled"><a class="page-link" href="#">...</a></li>
      <li class="page-item"><a class="page-link" href="?start='.$jmlpage.'">'.$jmlpage.'</a></li>';
    } elseif ($page >= 5 && $page <= ($jmlpage-5)) {
      # Tengah
      echo '<li class="page-item"><a class="page-link" href="?start=1">1</a></li>
      <li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
      for ($i=($page-2);$i<=($page+2);$i++) {
          if ($i == $page) {
              echo '<li class="page-item active"><a class="page-link" href="?start='.$i.'">'.$i.'<span class="sr-only">(current)</span></a></li>';
          } else {
              echo '<li><a class="page-link" href="?start='.$i.'">'.$i.'</a></li>';
          }
      }
      echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>
      <li class="page-item"><a class="page-link" href="?start='.$jmlpage.'">'.$jmlpage.'</a></li>';
    } else {
      # Belakang
      echo '<li class="page-item"><a class="page-link" href="?start=1">1</a></li>
      <li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
      for ($i=($jmlpage-5);$i<=$jmlpage;$i++) {
          if ($i == $page) {
              echo '<li class="page-item active"><a class="page-link" href="?start='.$i.'">'.$i.'<span class="sr-only">(current)</span></a></li>';
          } else {
              echo '<li><a class="page-link" href="?start='.$i.'">'.$i.'</a></li>';
          }
      }
    }
  } else {
    for ($i=1;$i<=$jmlpage;$i++) {
        if ($i == $page) {
            echo '<li class="page-item active"><a class="page-link" href="?start='.$i.'">'.$i.'<span class="sr-only">(current)</span></a></li>';
        } else {
            echo '<li class="page-item"><a class="page-link" href="?start='.$i.'">'.$i.'</a></li>';
        }
    }
  }

  echo '
    </ul>
  </nav>';
  ?>
    
<?php showfooter(); ?>
