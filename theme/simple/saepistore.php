<?php 
// Bootstrap when accessed directly
$__root = dirname(__DIR__, 2);
if (!isset($weburl)) { @include_once $__root . DIRECTORY_SEPARATOR . 'config.php'; }
if (!function_exists('getsettings')) { @include_once $__root . DIRECTORY_SEPARATOR . 'fungsi.php'; }
if (session_status() === PHP_SESSION_NONE) { @session_start(); }
if (!isset($settings) || !is_array($settings)) { $settings = getsettings(); }
if (!isset($datasponsor) || !is_array($datasponsor)) { $datasponsor = []; }
if (!isset($datamember) || !is_array($datamember)) { $datamember = []; }
if (!isset($menu) || !is_array($menu)) { @include_once $__root . DIRECTORY_SEPARATOR . 'menudata.php'; }
@include_once $__root . DIRECTORY_SEPARATOR . 'plugin' . DIRECTORY_SEPARATOR . 'epi-whatsapp-login' . DIRECTORY_SEPARATOR . 'helpers.php';

// Akses: hanya untuk member premium yang login
$isPremium = (isset($datamember['mem_status']) && intval($datamember['mem_status']) === 2);
if (!$isPremium) {
  if (!isset($datamember['mem_id'])) {
    $redir = $weburl.'login?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? ($weburl.'epistore'));
    header('Location: '.$redir);
    exit;
  }
  header('HTTP/1.1 403 Forbidden');
  echo '<div class="container py-5"><div class="alert alert-warning">Halaman hanya untuk member Premium. Silakan upgrade akun Anda.</div></div>';
  exit;
}

if (!function_exists('epi_normalize_phone')) {
  function epi_normalize_phone($raw){
    $p = preg_replace('/\D+/', '', (string)$raw);
    if ($p === '') { return $p; }
    if (strpos($p, '62') === 0) { $p = substr($p, 2); }
    if (strpos($p, '0') === 0) { $p = ltrim($p, '0'); }
    return $p;
  }
}

$head['pagetitle'] = 'Daftar EPIS';
$head['container'] = 'container';
$head['description'] = 'Temukan EPIS resmi di seluruh Indonesia lengkap dengan kontak WhatsApp.';
  $head['scripthead'] = '
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
  <style>
    :root { --epi-gold: #D4AF37; --epi-white: #F8F8F8; --epi-black: #0B0B0B; }
    h1, .h1 { font-family: "Playfair Display", serif; }
    body, .card { font-family: "Poppins", sans-serif; font-size:16px; }
    .btn-whatsapp { background-color: #FFFFFF; color: #000000; height: 40px; padding: 4px 8px; border: 1px solid #E0E0E0; display: inline-flex; align-items: center; justify-content: center; gap: 8px; }
    .epi-card-title { font-weight: 600; color: var(--epi-black); }
    .epi-section { background: var(--epi-white); }
    .map-container { min-height: 420px; height: 50vh; border: 1px solid #eee; border-radius: 8px; overflow: hidden; position: relative; background: #f8f8f8; display: flex; align-items: center; justify-content: center; }
    @media (max-width: 576px) { .map-container { height: 60vh; min-height: 360px; } }
    .map-legend { margin-top: 8px; background: #fff; border: 1px solid #E0E0E0; border-radius: 6px; padding: 8px 12px; font-size: 14px; }
    .map-legend .legend-item { display: inline-flex; align-items: center; gap: 8px; margin-right: 16px; }
    .legend-dot { width: 16px; height: 16px; border-radius: 50%; display: inline-block; }
    .legend-dot.store { background: var(--epi-gold); border: 1px solid #b38e1e; }
    .legend-dot.main { background: #fff; border: 2px solid var(--epi-gold); box-shadow: 0 0 0 2px rgba(212,175,55,0.25) inset; }
    header.epi-header { margin-top: 24px; }
    main.epi-section { padding: 16px; border-top: 1px solid #E0E0E0; }
    .epi-marker { width: 32px; height: 32px; background: var(--epi-gold); border-radius: 50%; box-shadow: 2px 2px 0 rgba(0,0,0,0.25); border: 1px solid #b38e1e; }
    .epi-marker-main { width: 32px; height: 32px; background: #fff; border-radius: 50%; box-shadow: 2px 2px 0 rgba(0,0,0,0.25); border: 3px solid var(--epi-gold); }
    .leaflet-marker-icon { border: none !important; box-shadow: none !important; }
    .leaflet-marker-icon.epi-marker { background: var(--epi-gold) !important; border: 1px solid #b38e1e !important; }
    .epi-badge { position:absolute; top:8px; right:8px; background:#D4AF37; color:#0B0B0B; font-weight:700; font-size:12px; padding:4px 8px; border-radius:12px; border:1px solid #7A6229; }
    .epi-marker-wrap { position: relative; width: 24px; height: 38px; }
    .epi-code-label { position:absolute; bottom:36px; left:50%; transform:translateX(-50%); background:#0B0B0B; color:#F8F8F8; font-size:8px; line-height:1; padding:2px 4px; border-radius:10px; border:1px solid #D4AF37; white-space:nowrap; }
    .gm-ui-close { display:none !important; }
    .mapboxgl-popup-close-button { display:none !important; }
    .epi-shadow { position:absolute; width:18px; height:6px; background:rgba(0,0,0,0.25); border-radius:50%; left:50%; transform:translate(-50%,0); bottom:0; filter:blur(1px); }
    .epi-marker-wrap svg { position:absolute; left:0; top:0; filter:drop-shadow(1px 1px 0 rgba(0,0,0,0.25)); }
  </style>
  <link href="'.$weburl.'fontawesome/css/fontawesome.min.css" rel="stylesheet" />
  <link href="'.$weburl.'fontawesome/css/brands.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tK8Hk1JYBT6LJx2oE0Xg=" crossorigin=""/>
  <link href="'.$weburl.'assets/leaflet/leaflet-local-enhanced.css" rel="stylesheet" />
';

showheader($head);
?>

<header class="mb-3 epi-header">
  <h1 class="display-6">Daftar EPIS</h1>
</header>

<main class="epi-section">
  <section id="epis-list" class="mb-4">
      <form action="" method="get" class="mb-3" role="search" aria-label="Pencarian EPI Store">
        <div class="input-group">
          <label class="visually-hidden" for="q">Cari EPI Store</label>
          <input type="text" class="form-control" id="q" name="q" placeholder="Cari berdasarkan nama EPI Store" value="<?= htmlspecialchars($_GET['q'] ?? '', ENT_QUOTES); ?>" aria-describedby="btnCari">
          <button class="btn btn-dark" id="btnCari" type="submit" title="Cari">Cari</button>
        </div>
      </form>

      <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
        <?php
          $perPage = 20;
          if (isset($_GET['start']) && is_numeric($_GET['start']) && $_GET['start'] > 0) {
            $start = (intval($_GET['start']) - 1) * $perPage;
            $page  = intval($_GET['start']);
          } else {
            $start = 0; $page = 1;
          }
          $where = "WHERE `status`=1";
          if (isset($_GET['q']) && trim($_GET['q']) !== '') {
            $s = cek($_GET['q']);
            $where .= " AND (`nama_store` LIKE '%".$s."%' OR `manager_nama` LIKE '%".$s."%')";
          }
          $rows = [];
          $err  = '';
          try {
            $rows = db_select("SELECT `id`,`nama_store`,`manager_nama`,`wa_nomor`,`lat`,`lng`,`nomor_kode` FROM `sa_epistore` $where ORDER BY `nama_store` ASC LIMIT ".$start.",".$perPage);
          } catch (\Throwable $e) { $err = $e->getMessage(); }

          if ($rows === false) { $err = db_error(); $rows = []; }

          if (count($rows) > 0) {
            foreach ($rows as $r) {
              $nama   = htmlspecialchars($r['nama_store'] ?? '', ENT_QUOTES);
              $manajer= htmlspecialchars($r['manager_nama'] ?? '', ENT_QUOTES);
              $waRaw  = $r['wa_nomor'] ?? '';
              $wa     = epi_normalize_phone($waRaw);
              $walink = 'https://wa.me/62'. $wa;
              $waValid = ($wa !== '' && preg_match('/^\d{8,15}$/', $wa));
              echo '<div class="col">
                <div class="card h-100 shadow-sm position-relative">
                  <span class="epi-badge">'.htmlspecialchars($r['nomor_kode'] ?? '', ENT_QUOTES).'</span>
                  <div class="card-body">
                    <div class="epi-card-title">'.$nama.'</div>
                    <div class="text-muted">Manager: '.$manajer.'</div>
                  </div>
                  <div class="card-footer bg-transparent border-0">
                    '.($waValid
                      ? '<a href="'.htmlspecialchars($walink, ENT_QUOTES).'" target="_blank" rel="noopener" class="btn btn-whatsapp w-100" aria-label="Hubungi WhatsApp"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i><span>WhatsApp</span></a>'
                      : '<button type="button" class="btn btn-whatsapp w-100" aria-label="Nomor WhatsApp tidak valid" disabled><i class="fa-brands fa-whatsapp" aria-hidden="true"></i><span>WhatsApp</span></button>'
                    ).'
                  </div>
                </div>
              </div>';
            }
          } else {
            echo '<div class="col-12"><div class="alert alert-warning">Belum ada data EPI Store atau terjadi kesalahan. '.htmlspecialchars($err, ENT_QUOTES).'</div></div>';
          }
        ?>
      </div>

      <?php
        $total = intval(db_var("SELECT COUNT(*) FROM `sa_epistore` $where"));
        $pages = max(1, ceil($total / $perPage));
        echo '<nav aria-label="Navigasi halaman" class="mt-3"><ul class="pagination">';
        if ($pages > 10) {
          if ($page <= 4){
            for ($i=1;$i<=5;$i++) {
              $active = ($i==$page)?' active':'';
              echo '<li class="page-item'.$active.'"><a class="page-link" href="?start='.$i.'&q='.urlencode($_GET['q'] ?? '').'">'.$i.'</a></li>';
            }
            echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
            echo '<li class="page-item"><a class="page-link" href="?start='.$pages.'&q='.urlencode($_GET['q'] ?? '').'">'.$pages.'</a></li>';
          } elseif ($page >= 5 && $page <= ($pages-5)) {
            echo '<li class="page-item"><a class="page-link" href="?start=1&q='.urlencode($_GET['q'] ?? '').'">1</a></li>';
            echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
            for ($i=($page-2);$i<=($page+2);$i++) {
              $active = ($i==$page)?' active':'';
              echo '<li class="page-item'.$active.'"><a class="page-link" href="?start='.$i.'&q='.urlencode($_GET['q'] ?? '').'">'.$i.'</a></li>';
            }
            echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
            echo '<li class="page-item"><a class="page-link" href="?start='.$pages.'&q='.urlencode($_GET['q'] ?? '').'">'.$pages.'</a></li>';
          } else {
            echo '<li class="page-item"><a class="page-link" href="?start=1&q='.urlencode($_GET['q'] ?? '').'">1</a></li>';
            echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
            for ($i=($pages-5);$i<=$pages;$i++) {
              $active = ($i==$page)?' active':'';
              echo '<li class="page-item'.$active.'"><a class="page-link" href="?start='.$i.'&q='.urlencode($_GET['q'] ?? '').'">'.$i.'</a></li>';
            }
          }
        } else {
          for ($i=1;$i<=$pages;$i++) {
            $active = ($i==$page)?' active':'';
            echo '<li class="page-item'.$active.'"><a class="page-link" href="?start='.$i.'&q='.urlencode($_GET['q'] ?? '').'">'.$i.'</a></li>';
          }
        }
        echo '</ul></nav>';
      ?>
  </section>

  <section id="epis-map" class="mt-2">
    <div class="map-container w-100" id="epistoreMap" role="region" aria-label="Peta EPI Store">
      <div class="text-center text-muted p-4 w-100">
        <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
        <div>Memuat peta...</div>
      </div>
    </div>
    <div class="map-legend" aria-label="Legenda Peta">
      <span class="legend-item"><span class="legend-dot store" aria-hidden="true"></span><span>EPI Store</span></span>
      <span class="legend-item"><span class="legend-dot main" aria-hidden="true"></span><span>Toko Utama</span></span>
    </div>
  </section>
</main>

<footer class="mt-4 text-muted small">
  <div class="d-flex justify-content-between">
    <span>&copy; <?= date('Y'); ?> EPI — All Rights Reserved</span>
    <span>EPIC Hub v<?= htmlspecialchars($settings['ver'] ?? '1.0', ENT_QUOTES); ?></span>
  </div>
</footer>

<?php
// Siapkan data marker aman dalam JSON
$markerData = [];
if (is_array($rows) && count($rows) > 0) {
  foreach ($rows as $r) {
    if (isset($r['lat'], $r['lng']) && is_numeric($r['lat']) && is_numeric($r['lng'])) {
      $nama = htmlspecialchars($r['nama_store'] ?? '', ENT_QUOTES);
      $kode = htmlspecialchars($r['nomor_kode'] ?? '', ENT_QUOTES);
      $wa   = epi_normalize_phone($r['wa_nomor'] ?? '');
      $wal  = 'https://wa.me/62'. $wa;
      $markerData[] = [
        'lat' => (float)$r['lat'],
        'lng' => (float)$r['lng'],
        'title' => $nama,
        'wa' => htmlspecialchars($wal, ENT_QUOTES),
        'kode' => $kode
      ];
    }
  }
}

// Gunakan heredoc untuk memudahkan quoting JS
$markersJson = json_encode($markerData);
$provider = $settings['map_provider'] ?? 'osm'; // Default ke OSM untuk memastikan peta selalu muncul
$apikey   = ($settings['map_api_key'] ?? getenv('MAPBOX_TOKEN') ?? '');
$zoom     = intval($settings['map_zoom'] ?? 4);
$mainLat  = isset($settings['map_main_lat']) ? floatval($settings['map_main_lat']) : null;
$mainLng  = isset($settings['map_main_lng']) ? floatval($settings['map_main_lng']) : null;
$mainLatJson = json_encode($mainLat);
$mainLngJson = json_encode($mainLng);

// Debug info untuk development
error_log("Map Provider: " . $provider);
error_log("Map API Key: " . substr($apikey, 0, 10) . "...");
error_log("Markers count: " . count($markerData));

if ($provider === 'google' && !empty($apikey)) {
  $footer['scriptfoot'] = <<<HTML
    <script src="https://maps.googleapis.com/maps/api/js?key={$apikey}"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCmZ3rEK7OGa+4+6k9G1A7rVQvQvQvZ8ZkZf2F0u8=" crossorigin=""></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tK8Hk1JYBT6LJx2oE0Xg=" crossorigin=""/>
    <script>
      console.log('Provider: Google Maps dengan fallback Leaflet');
      document.addEventListener('DOMContentLoaded', function() {
        var el = document.getElementById('epistoreMap');
        if (!el) return;
        
        function initLeaflet() {
          try {
            // Hapus loading indicator
            el.innerHTML = '';
            
            var bounds = L.latLngBounds([[6,95],[-11,141]]);
            var map = L.map(el, { scrollWheelZoom: true, zoomControl: true, maxBounds: bounds, maxBoundsViscosity: 1.0 }).setView([-2.5, 118], {$zoom});
            var layer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(map);
            layer.on('tileerror', function(){
              var warn = document.createElement('div');
              warn.className = 'alert alert-warning m-2 p-2';
              warn.innerText = 'Gagal memuat tile peta. Periksa koneksi internet.';
              el.appendChild(warn);
            });
            var markers = {$markersJson};
            var mainLat = {$mainLatJson}, mainLng = {$mainLngJson};
            var group = [];
            if (Array.isArray(markers) && markers.length) {
              markers.forEach(function(m){
                var html = '<div class="epi-marker-wrap">'
                  + '<svg width="24" height="38" viewBox="0 0 24 38" aria-hidden="true">'
                  + '<defs><linearGradient id="epiPinGrad" x1="0" y1="0" x2="0" y2="1">'
                  + '<stop offset="0%" stop-color="#D4AF37"/>'
                  + '<stop offset="100%" stop-color="#C0C0C0"/>'
                  + '</linearGradient></defs>'
                  + '<path d="M12 2C6.48 2 2 6.48 2 12c0 9 10 24 10 24s10-15 10-24c0-5.52-4.48-10-10-10z" fill="url(#epiPinGrad)" stroke="#7A6229" stroke-width="1.5" />'
                  + '<circle cx="12" cy="12" r="4" fill="#fff" opacity="0.85" />'
                  + '</svg>'
                  + '<span class="epi-code-label">'+(m.kode||'')+'</span>'
                  + '<span class="epi-shadow"></span>'
                  + '</div>';
                var icon = L.divIcon({ className: 'epi-marker', html: html, iconSize: [24,38], iconAnchor: [12,38] });
                var mk = L.marker([m.lat, m.lng], { icon: icon }).addTo(map);
                mk.bindTooltip(m.title, { direction: 'top', offset: [0,-20], permanent: false });
                mk.bindPopup('<div><strong>'+m.title+'</strong></div><div style="margin-top:6px"><a href="'+m.wa+'" target="_blank" rel="noopener" style="display:inline-block;padding:6px 10px;border:1px solid #D4AF37;border-radius:10px;text-decoration:none;font-weight:600;color:#0B0B0B">WhatsApp</a></div>', { closeButton: false });
                group.push(mk.getLatLng());
              });
            }
            if (Number.isFinite(mainLat) && Number.isFinite(mainLng)) {
              var icon = L.divIcon({ className: 'epi-marker-main', html: '', iconSize: [32,32], iconAnchor: [16,16] });
              var mk = L.marker([mainLat, mainLng], { icon: icon }).addTo(map);
              mk.bindTooltip('Toko Utama', { direction: 'top', offset: [0,-20], permanent: false });
              group.push(mk.getLatLng());
            }
            if (group.length) { map.fitBounds(L.latLngBounds(group), { padding: [20,20] }); } else { map.fitBounds(bounds); }
            window.addEventListener('resize', function(){ setTimeout(function(){ map.invalidateSize(); }, 150); });
          } catch (e) {
            console.error('Error inisialisasi Leaflet fallback:', e);
            el.innerHTML = '<div class="alert alert-danger m-3">Error: ' + e.message + '</div>';
          }
        }
        
        // Coba Google Maps dulu
        if (typeof google !== 'undefined' && google.maps) {
          try {
            // Hapus loading indicator
            el.innerHTML = '';
            
            var bounds = new google.maps.LatLngBounds(new google.maps.LatLng(6,95), new google.maps.LatLng(-11,141));
            var center = { lat: -2.5, lng: 118 };
            var map = new google.maps.Map(el, { center: center, zoom: {$zoom}, gestureHandling: 'greedy' });
            var markers = {$markersJson};
            var mainLat = {$mainLatJson}, mainLng = {$mainLngJson};
            var goldIcon = {
              path: 'M12 2C6.48 2 2 6.48 2 12c0 9 10 24 10 24s10-15 10-24c0-5.52-4.48-10-10-10z',
              fillColor: '#D4AF37', fillOpacity: 1, strokeColor: '#7A6229', strokeWeight: 2, scale: 1,
              anchor: new google.maps.Point(12, 38),
              labelOrigin: new google.maps.Point(12, 0)
            };
            var group = [];
            if (Array.isArray(markers)) {
              markers.forEach(function(m){
                var mk = new google.maps.Marker({ position: {lat: m.lat, lng: m.lng}, map: map, icon: goldIcon, title: m.title, label: (m.kode||'') });
                var infowin = new google.maps.InfoWindow({ content: '<div><strong>'+m.title+'</strong></div><div style="margin-top:6px"><a href="'+m.wa+'" target="_blank" rel="noopener" style="display:inline-block;padding:6px 10px;border:1px solid #D4AF37;border-radius:10px;text-decoration:none;font-weight:600;color:#0B0B0B">WhatsApp</a></div>' });
                mk.addListener('click', function(){ infowin.open(map, mk); });
                group.push(mk.getPosition());
              });
            }
            if (Number.isFinite(mainLat) && Number.isFinite(mainLng)) {
              var mainIcon = { path: google.maps.SymbolPath.CIRCLE, fillColor: '#FFFFFF', fillOpacity: 1, strokeColor: '#D4AF37', strokeWeight: 3, scale: 10 };
              var mainMk = new google.maps.Marker({ position: {lat: mainLat, lng: mainLng}, map: map, icon: mainIcon, title: 'Toko Utama' });
              group.push(mainMk.getPosition());
            }
            if (group.length) {
              var gb = new google.maps.LatLngBounds();
              group.forEach(function(p){ gb.extend(p); });
              map.fitBounds(gb);
            } else {
              map.fitBounds(bounds);
            }
            console.log('Google Maps berhasil dimuat');
          } catch (e) {
            console.warn('Google Maps gagal, fallback ke Leaflet:', e);
            initLeaflet();
          }
        } else {
          console.warn('Google Maps tidak tersedia, menggunakan Leaflet');
          initLeaflet();
        }
      });
    </script>
  HTML;
} elseif ($provider === 'mapbox' && !empty($apikey)) {
  $footer['scriptfoot'] = <<<HTML
    <link href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css" rel="stylesheet" />
    <script src="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js"></script>
    <script>
      console.log('Provider: Mapbox GL');
      document.addEventListener('DOMContentLoaded', function(){
        var el = document.getElementById('epistoreMap');
        if (!el) return;
        try {
          el.innerHTML = '';
          mapboxgl.accessToken = '{$apikey}';
          var map = new mapboxgl.Map({ container: el, style: 'mapbox://styles/mapbox/streets-v11', center: [118, -2.5], zoom: {$zoom} });
          map.addControl(new mapboxgl.NavigationControl(), 'bottom-right');
          var markers = {$markersJson};
          var mainLat = {$mainLatJson}, mainLng = {$mainLngJson};
          var bounds = new mapboxgl.LngLatBounds([95, -11], [141, 6]);
          if (Array.isArray(markers)) {
            markers.forEach(function(m){
              var el = document.createElement('div');
              el.className = 'epi-marker-wrap';
              el.innerHTML = '<svg width="24" height="38" viewBox="0 0 24 38" aria-hidden="true">'
                + '<defs><linearGradient id="epiPinGradMb" x1="0" y1="0" x2="0" y2="1">'
                + '<stop offset="0%" stop-color="#D4AF37"/>'
                + '<stop offset="100%" stop-color="#C0C0C0"/>'
                + '</linearGradient></defs>'
                + '<path d="M12 2C6.48 2 2 6.48 2 12c0 9 10 24 10 24s10-15 10-24c0-5.52-4.48-10-10-10z" fill="url(#epiPinGradMb)" stroke="#7A6229" stroke-width="1.5" />'
                + '<circle cx="12" cy="12" r="4" fill="#fff" opacity="0.85" />'
                + '</svg>'
                + '<span class="epi-code-label">'+(m.kode||'')+'</span>'
                + '<span class="epi-shadow"></span>';
              new mapboxgl.Marker({ element: el })
                .setLngLat([m.lng, m.lat])
                .setPopup(new mapboxgl.Popup({ closeButton:false }).setHTML('<div><strong>'+m.title+'</strong></div><div style="margin-top:6px"><a href="'+m.wa+'" target="_blank" rel="noopener" style="display:inline-block;padding:6px 10px;border:1px solid #D4AF37;border-radius:10px;text-decoration:none;font-weight:600;color:#0B0B0B">WhatsApp</a></div>'))
                .addTo(map);
              bounds.extend([m.lng, m.lat]);
            });
          }
          if (Number.isFinite(mainLat) && Number.isFinite(mainLng)) {
            new mapboxgl.Marker({ color: '#FFFFFF' }).setLngLat([mainLng, mainLat]).addTo(map);
            bounds.extend([mainLng, mainLat]);
          }
          map.fitBounds(bounds, { padding: 20 });
        } catch(e) {
          el.innerHTML = '<div class="alert alert-danger m-3">Gagal memuat Mapbox GL: '+ e.message +'</div>';
        }
      });
    </script>
  HTML;
} else {
  // Default ke OSM untuk memastikan peta selalu muncul
  $tile = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
  if ($provider === 'mapbox' && !empty($apikey)) {
    $tile = 'https://api.mapbox.com/styles/v1/mapbox/streets-v11/tiles/{z}/{x}/{y}?access_token='.$apikey;
  }
  $footer['scriptfoot'] = <<<HTML
    <script>
      function loadLocalLeaflet() {
        console.log('CDN Leaflet gagal dimuat, menggunakan fallback lokal');
        var script = document.createElement('script');
        script.src = '{$weburl}assets/leaflet/leaflet-local.js';
        script.onload = function() {
          console.log('Leaflet lokal berhasil dimuat');
          window.dispatchEvent(new Event('leafletLoaded'));
        };
        document.head.appendChild(script);
      }
    </script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCmZ3rEK7OGa+4+6k9G1A7rVQvQvQvZ8ZkZf2F0u8=" crossorigin="" onerror="this.onerror=null; loadLocalLeaflet();"></script>
    <script src="{$weburl}assets/leaflet/leaflet-local.js"></script>
    <script>
      console.log('Provider: Leaflet/OSM (default)');
      document.addEventListener('DOMContentLoaded', function() {
        var el = document.getElementById('epistoreMap');
        if (!el) return;
        var provider = '{$provider}';
        var apikey = '{$apikey}';
        
        function initGoogleFallback(){
          var key = apikey;
          if (!key) { el.innerHTML = '<div class="alert alert-warning m-3">Leaflet tidak tersedia dan API key Google kosong. Isi API key pada pengaturan.</div>'; return; }
          var s = document.createElement('script');
          s.src = 'https://maps.googleapis.com/maps/api/js?key=' + key;
          s.onload = function(){
            try {
              el.innerHTML = '';
              var bounds = new google.maps.LatLngBounds(new google.maps.LatLng(6,95), new google.maps.LatLng(-11,141));
              var center = { lat: -2.5, lng: 118 };
              var map = new google.maps.Map(el, { center: center, zoom: {$zoom}, gestureHandling: 'greedy' });
              var markers = {$markersJson};
              var mainLat = {$mainLatJson}, mainLng = {$mainLngJson};
              var goldIcon = { path: google.maps.SymbolPath.CIRCLE, fillColor: '#D4AF37', fillOpacity: 1, strokeColor: '#b38e1e', strokeWeight: 1, scale: 8 };
              var group = [];
              if (Array.isArray(markers)) {
                markers.forEach(function(m){
                  var mk = new google.maps.Marker({ position: {lat: m.lat, lng: m.lng}, map: map, icon: goldIcon, title: m.title });
                  var infowin = new google.maps.InfoWindow({ content: '<strong>'+m.title+'</strong><br/><a href="'+m.wa+'" target="_blank" rel="noopener">WhatsApp</a>' });
                  mk.addListener('mouseover', function(){ infowin.open(map, mk); });
                  mk.addListener('mouseout', function(){ infowin.close(); });
                  group.push(mk.getPosition());
                });
              }
              if (Number.isFinite(mainLat) && Number.isFinite(mainLng)) {
                var mainIcon = { path: google.maps.SymbolPath.CIRCLE, fillColor: '#FFFFFF', fillOpacity: 1, strokeColor: '#D4AF37', strokeWeight: 3, scale: 10 };
                var mainMk = new google.maps.Marker({ position: {lat: mainLat, lng: mainLng}, map: map, icon: mainIcon, title: 'Toko Utama' });
                group.push(mainMk.getPosition());
              }
              if (group.length) { var gb = new google.maps.LatLngBounds(); group.forEach(function(p){ gb.extend(p); }); map.fitBounds(gb); } else { map.fitBounds(bounds); }
              console.log('Google fallback aktif');
            } catch(e){ el.innerHTML = '<div class="alert alert-danger m-3">Error Google fallback: '+ e.message +'</div>'; }
          };
          s.onerror = function(){ el.innerHTML = '<div class="alert alert-danger m-3">Gagal memuat Google Maps API.</div>'; };
          document.head.appendChild(s);
        }

        function initMapboxFallback(){
          var token = apikey;
          if (!token) { el.innerHTML = '<div class="alert alert-warning m-3">Leaflet tidak tersedia dan Mapbox token kosong. Isi token pada pengaturan.</div>'; return; }
          var css = document.createElement('link');
          css.rel = 'stylesheet';
          css.href = 'https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css';
          document.head.appendChild(css);
          var js = document.createElement('script');
          js.src = 'https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js';
          js.onload = function(){
            try {
              el.innerHTML = '';
              mapboxgl.accessToken = token;
              var map = new mapboxgl.Map({ container: el, style: 'mapbox://styles/mapbox/streets-v11', center: [118, -2.5], zoom: {$zoom} });
              var markers = {$markersJson};
              var mainLat = {$mainLatJson}, mainLng = {$mainLngJson};
              var bounds = new mapboxgl.LngLatBounds([95, -11], [141, 6]);
              if (Array.isArray(markers)) {
                markers.forEach(function(m){
              var el = document.createElement('div');
              el.className = 'epi-marker-wrap';
              var pin = document.createElement('span');
              pin.className = 'epi-pin';
              var lab = document.createElement('span');
              lab.className = 'epi-code-label';
              lab.textContent = (m.kode||'');
              el.appendChild(pin);
              el.appendChild(lab);
              new mapboxgl.Marker({ element: el }).setLngLat([m.lng, m.lat]).setPopup(new mapboxgl.Popup({ closeButton:false }).setHTML('<div><strong>'+m.title+'</strong></div><div style="margin-top:6px"><a href="'+m.wa+'" target="_blank" rel="noopener" style="display:inline-block;padding:6px 10px;border:1px solid #D4AF37;border-radius:10px;text-decoration:none;font-weight:600;color:#0B0B0B">WhatsApp</a></div>')).addTo(map);
                  bounds.extend([m.lng, m.lat]);
                });
              }
              if (Number.isFinite(mainLat) && Number.isFinite(mainLng)) {
                new mapboxgl.Marker({ color: '#FFFFFF' }).setLngLat([mainLng, mainLat]).addTo(map);
                bounds.extend([mainLng, mainLat]);
              }
              map.fitBounds(bounds, { padding: 20 });
              console.log('Mapbox fallback aktif');
            } catch(e){ el.innerHTML = '<div class="alert alert-danger m-3">Error Mapbox fallback: '+ e.message +'</div>'; }
          };
          js.onerror = function(){ el.innerHTML = '<div class="alert alert-danger m-3">Gagal memuat Mapbox GL JS.</div>'; };
          document.head.appendChild(js);
        }
        
        function initMap() {
          // Hapus loading indicator
          el.innerHTML = '';
          
          // Pastikan Leaflet tersedia
          if (typeof L === 'undefined') {
            console.error('Leaflet tidak tersedia');
            if (provider === 'mapbox') { initMapboxFallback(); } else { initGoogleFallback(); }
            return;
          }
          
          try {
            var bounds = L.latLngBounds([[6,95],[-11,141]]);
            var map = L.map(el, { scrollWheelZoom: true, zoomControl: true, maxBounds: bounds, maxBoundsViscosity: 1.0, attributionControl: true }).setView([-2.5, 118], {$zoom});
            var layer = L.tileLayer('{$tile}', { attribution: '&copy; OpenStreetMap contributors' }).addTo(map);
            layer.on('tileerror', function(){
              var warn = document.createElement('div');
              warn.className = 'alert alert-warning m-2 p-2';
              warn.innerText = 'Gagal memuat tile peta. Periksa koneksi internet atau API key.';
              el.appendChild(warn);
            });
            var markers = {$markersJson};
            var mainLat = {$mainLatJson}, mainLng = {$mainLngJson};
            var group = [];
            if (Array.isArray(markers) && markers.length) {
              markers.forEach(function(m){
                var html = '<div class="epi-marker-wrap">'
                  + '<svg width="24" height="38" viewBox="0 0 24 38" aria-hidden="true">'
                  + '<defs><linearGradient id="epiPinGrad2" x1="0" y1="0" x2="0" y2="1">'
                  + '<stop offset="0%" stop-color="#D4AF37"/>'
                  + '<stop offset="100%" stop-color="#C0C0C0"/>'
                  + '</linearGradient></defs>'
                  + '<path d="M12 2C6.48 2 2 6.48 2 12c0 9 10 24 10 24s10-15 10-24c0-5.52-4.48-10-10-10z" fill="url(#epiPinGrad2)" stroke="#7A6229" stroke-width="1.5" />'
                  + '<circle cx="12" cy="12" r="4" fill="#fff" opacity="0.85" />'
                  + '</svg>'
                  + '<span class="epi-code-label">'+(m.kode||'')+'</span>'
                  + '<span class="epi-shadow"></span>'
                  + '</div>';
                var icon = L.divIcon({ className: 'epi-marker', html: html, iconSize: [24,38], iconAnchor: [12,38] });
                var mk = L.marker([m.lat, m.lng], { icon: icon }).addTo(map);
                mk.bindTooltip(m.title, { direction: 'top', offset: [0,-20], permanent: false });
                mk.bindPopup('<div><strong>'+m.title+'</strong></div><div style="margin-top:6px"><a href="'+m.wa+'" target="_blank" rel="noopener" style="display:inline-block;padding:6px 10px;border:1px solid #D4AF37;border-radius:10px;text-decoration:none;font-weight:600;color:#0B0B0B">WhatsApp</a></div>', { closeButton: false });
                group.push(mk.getLatLng());
              });
            }
            if (Number.isFinite(mainLat) && Number.isFinite(mainLng)) {
              var icon = L.divIcon({ className: 'epi-marker-main', html: '', iconSize: [32,32], iconAnchor: [16,16] });
              var mk = L.marker([mainLat, mainLng], { icon: icon }).addTo(map);
              mk.bindTooltip('Toko Utama', { direction: 'top', offset: [0,-20], permanent: false });
              group.push(mk.getLatLng());
            }
            if (group.length) { map.fitBounds(L.latLngBounds(group), { padding: [20,20] }); } else { map.fitBounds(bounds); }
            L.control.scale({metric:true, imperial:false}).addTo(map);
            map.zoomControl.setPosition('bottomright');
            window.addEventListener('resize', function(){ setTimeout(function(){ map.invalidateSize(); }, 150); });
          } catch (e) {
            console.error('Error inisialisasi Leaflet:', e);
            el.innerHTML = '<div class="alert alert-danger m-3">Error: ' + e.message + '</div>';
          }
        }
        
        // Coba inisialisasi, jika gagal tunggu event dari fallback
        if (typeof L !== 'undefined') {
          initMap();
        } else {
          window.addEventListener('leafletLoaded', initMap);
          // Timeout fallback
          setTimeout(function() {
            if (typeof L !== 'undefined') {
              initMap();
            } else {
              el.innerHTML = '<div class="alert alert-danger m-3">Gagal memuat peta. Library tidak tersedia.</div>';
            }
          }, 3000);
        }
      });
    </script>
  HTML;
}
showfooter($footer);
?>
<script>
// Emergency fallback: pastikan peta muncul bahkan jika script utama gagal
document.addEventListener('DOMContentLoaded', function() {
  setTimeout(function() {
    var mapEl = document.getElementById('epistoreMap');
    if (mapEl && mapEl.innerHTML.includes('Memuat peta')) {
      console.warn('Peta belum dimuat setelah 5 detik, coba inisialisasi emergency');
      
      // Coba inisialisasi peta sederhana
      try {
        // Hapus loading indicator
        mapEl.innerHTML = '';
        
        // Buat peta sederhana tanpa library eksternal
        mapEl.style.background = '#e0e0e0';
        mapEl.innerHTML = '<div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #666;">' +
                         '<div class="text-center">' +
                         '<i class="fas fa-map fa-3x mb-2"></i><br/>' +
                         '<small>Peta tidak dapat dimuat<br/>Periksa koneksi internet</small>' +
                         '</div></div>';
        
        console.log('Emergency fallback ditampilkan');
      } catch (e) {
        mapEl.innerHTML = '<div class="alert alert-warning m-3">Peta tidak dapat dimuat. Periksa koneksi internet.</div>';
      }
    }
  }, 5000);
});
</script>
<?php
?>
