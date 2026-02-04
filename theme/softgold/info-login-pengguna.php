<?php
// path: theme/softgold/info-login-epic-hub-lite.php
// INFORMASI CARA LOGIN PENGGUNA - EPIC HUB LITE (Softgold Theme)
// Security-first: escape output, minimal DB read, privacy masking

$__root = dirname(__DIR__, 2);
@include_once $__root . DIRECTORY_SEPARATOR . 'config.php';
@include_once $__root . DIRECTORY_SEPARATOR . 'fungsi.php';

// Fetch basic settings if available
$weburl = isset($setting['weburl']) ? $setting['weburl'] : '';

// Helper: mask first 4 chars of email's local part
function maskEmailHideFirst4($email) {
    $email = trim((string)$email);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return htmlspecialchars($email);
    [$local, $domain] = explode('@', $email, 2);
    $localLen = strlen($local);
    $hideCount = min(4, $localLen);
    $maskedLocal = str_repeat('*', $hideCount) . substr($local, $hideCount);
    return htmlspecialchars($maskedLocal . '@' . $domain, ENT_QUOTES, 'UTF-8');
}

// Helper: mask last 4 digits of whatsapp
function maskWhatsappHideLast4($wa) {
    $wa = trim((string)$wa);
    // Show formatted WA, but mask last 4 characters (digits)
    $fmt = formatwa($wa);
    $len = strlen($fmt);
    $hideCount = min(4, $len);
    $visible = $len > $hideCount ? substr($fmt, 0, $len - $hideCount) : '';
    return htmlspecialchars($visible . str_repeat('*', $hideCount), ENT_QUOTES, 'UTF-8');
}

// Public password protection gate (non-auth access)
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
header('Cache-Control: no-store, max-age=0');
$PUBLIC_PAGE_PASSWORD = getenv('PUBLIC_PAGE_PASSWORD') ?: 'epicsukses';
if (empty($_SESSION['info_login_epic_hub_lite_ok'])) {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    }
    $error = '';
    $now = time();
    $attempts = $_SESSION['protect_attempts'] ?? [];
    // Purge attempts older than 10 minutes
    $attempts = array_filter($attempts, function($t) use ($now) { return ($now - $t) < 600; });
    $_SESSION['protect_attempts'] = $attempts;
    $locked = count($attempts) >= 5;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Small delay to slow brute force
        usleep(200000);
        $tokenOk = isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$_POST['csrf_token']);
        $pwd = isset($_POST['page_password']) ? trim((string)$_POST['page_password']) : '';
        if ($locked) {
            $error = 'Terlalu banyak percobaan. Coba lagi setelah 10 menit.';
        } elseif (!$tokenOk) {
            $error = 'Sesi tidak valid. Muat ulang halaman dan coba lagi.';
        } elseif (!hash_equals($PUBLIC_PAGE_PASSWORD, $pwd)) {
            $error = 'Password salah.';
            $attempts[] = $now;
            $_SESSION['protect_attempts'] = $attempts;
        } else {
            $_SESSION['info_login_epic_hub_lite_ok'] = true;
            // Regenerate CSRF token after success
            $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
        }
    }
    if (empty($_SESSION['info_login_epic_hub_lite_ok'])) {
        // Render softgold-styled password form then exit
        $css = isset($setting['weburl']) && $setting['weburl'] ? $setting['weburl'] . 'theme/softgold/style.css' : './style.css';
        echo '<!doctype html><html lang="id"><head><meta charset="utf-8"/><meta name="viewport" content="width=device-width, initial-scale=1"/><title>Halaman Dilindungi Password</title>';
        echo '<link rel="stylesheet" href="'.htmlspecialchars($css).'"/>';
        echo '<style>:root{--sg-bg:#fff7e6; --sg-card:#fff; --sg-primary:#b8860b; --sg-text:#3b3b3b; --sg-border:#ead7b0;} body{background:var(--sg-bg); color:var(--sg-text); font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,"Helvetica Neue",sans-serif;} .container{max-width:480px;margin:40px auto;padding:16px;} .card{background:var(--sg-card);border:1px solid var(--sg-border);border-radius:12px;padding:16px;box-shadow:0 2px 8px rgba(0,0,0,.04);} .btn{display:inline-block;padding:10px 14px;border-radius:8px;text-decoration:none;background:var(--sg-primary);color:#fff;} input[type=password]{width:100%;padding:10px;border:1px solid var(--sg-border);border-radius:8px;margin-top:8px;}</style></head><body><div class="container"><div class="card"><h2>Halaman Dilindungi Password</h2><p>Halaman ini dapat diakses publik dengan proteksi password sederhana.</p>';
        if (!empty($error)) { echo '<p style="color:#b00020;">'.htmlspecialchars($error).'</p>'; }
        echo '<form method="post" action="">';
        echo '<input type="hidden" name="csrf_token" value="'.htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8').'" />';
        echo '<label for="page_password">Masukkan password:</label>';
        echo '<input id="page_password" name="page_password" type="password" required placeholder="Masukkan password" />';
        echo '<div style="margin-top:12px;"><button class="btn" type="submit">Buka Halaman</button></div>';
        echo '<p style="margin-top:12px;color:#555;">Hint: Bila lupa password, hubungi Admin.</p>';
        echo '</form></div></div></body></html>';
        exit;
    }
}

// Fetch users (limit for performance)
$rows = [];
try {
    // Only select necessary columns
    $sql = "SELECT `mem_nama`,`mem_email`,`mem_whatsapp` FROM `sa_member` ORDER BY `mem_tgldaftar` DESC LIMIT 1000";
    $res = mysqli_query($con, $sql);
    if ($res) {
        while ($r = mysqli_fetch_assoc($res)) {
            $rows[] = $r;
        }
    }
} catch (Exception $e) {
    // Optional: log error silently
}

// Logo path (adjust if needed)
$logoPath = 'https://bisnisemasperak.com/upload/logoweb.png';
$softgoldCss = $weburl ? $weburl . 'theme/softgold/style.css' : './style.css';
header('X-Robots-Tag: noindex, noarchive, nosnippet');
header('Referrer-Policy: no-referrer');
// Compute embed origin for YouTube Iframe API to avoid player config error (e.g., error 153)
$origin = '';
try {
    if (!empty($weburl)) {
        $scheme = parse_url($weburl, PHP_URL_SCHEME) ?: 'https';
        $host = parse_url($weburl, PHP_URL_HOST) ?: ($_SERVER['HTTP_HOST'] ?? '');
        if ($host) { $origin = $scheme . '://' . $host; }
    } else {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if ($host) { $origin = $scheme . '://' . $host; }
    }
} catch (Throwable $e) {
    // noop: fallback to empty origin, YouTube will use referrer
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>INFORMASI CARA LOGIN PENGGUNA - EPIC HUB LITE</title>
  <link rel="stylesheet" href="<?php echo htmlspecialchars($softgoldCss); ?>" />
  <style>
    :root{
      --sg-bg:#fff7e6; --sg-card:#fff; --sg-primary:#b8860b; --sg-text:#3b3b3b; --sg-muted:#7a6a4f;
      --sg-border:#ead7b0; --sg-accent:#d4af37;
    }
    body{background:var(--sg-bg); color:var(--sg-text); font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,"Helvetica Neue",sans-serif;}
    .container{max-width:1000px; margin:0 auto; padding:16px;}
    /* Header redesigned: logo centered above title */
    .header{display:flex; flex-direction:column; align-items:center; justify-content:center; gap:10px; padding:20px 0; border-bottom:1px solid var(--sg-border);} 
    .header img{height:clamp(48px, 10vw, 72px); width:auto;}
    .title{font-weight:700; color:var(--sg-primary); text-align:center; font-size:clamp(20px, 5vw, 28px);}    
    .card{background:var(--sg-card); border:1px solid var(--sg-border); border-radius:12px; padding:16px; box-shadow:0 2px 8px rgba(0,0,0,.04); margin:16px 0;}    
    .muted{color:var(--sg-muted);}    
    .btn{display:inline-block; padding:10px 14px; border-radius:8px; text-decoration:none;}
    .btn-primary{background:var(--sg-primary); color:#fff;}
    .btn-outline{border:1px solid var(--sg-primary); color:var(--sg-primary);}    
    .btn-wa{background:#25D366; color:#fff;}
    .search{margin:12px 0;}
    .search input{width:100%; padding:10px; border:1px solid var(--sg-border); border-radius:8px;}
    table{width:100%; border-collapse:collapse; font-size:14px;}
    th,td{padding:10px; border-bottom:1px solid var(--sg-border);} 
    th{text-align:left; background:#fff9ec;}
    tr:hover{background:#fffdf5;}
    .note{background:#fffbef; border:1px dashed var(--sg-border); padding:10px; border-radius:10px;}
    /* Video monitor embed */
    .video-monitor{display:flex;justify-content:center;align-items:center;margin:14px 0 18px;}
    .monitor-frame{width:min(100%, 780px);background:#1a1a1a;border:1px solid var(--sg-border);border-radius:18px;box-shadow:0 10px 22px rgba(0,0,0,.15), inset 0 0 0 2px rgba(212,175,55,.25);padding:14px;}
    .monitor-screen{background:#000;border-radius:12px;overflow:hidden;}
    .video-container{position:relative;width:100%;padding-bottom:56.25%;}
    .video-container iframe{position:absolute;left:0;top:0;width:100%;height:100%;border:0;}
    .monitor-stand{width:120px;height:10px;background:linear-gradient(180deg,var(--sg-accent),var(--sg-primary));border-radius:14px;margin:12px auto 0;box-shadow:0 2px 8px rgba(0,0,0,.25);} 
    /* Table visibility & feedback */
    .table-wrap{transition:opacity .25s ease, max-height .25s ease; overflow:hidden;}
    .table-wrap.is-hidden{opacity:0; max-height:0; pointer-events:none;}
    .table-wrap.is-visible{opacity:1; max-height:2000px;}
    .empty-state{display:flex; align-items:center; gap:10px; padding:12px; border:1px dashed var(--sg-border); border-radius:10px; background:#fffdf5; color:var(--sg-muted);}
    .empty-state::before{content:""; width:16px; height:16px; border-radius:50%; background:var(--sg-primary); opacity:.3;}
    .status{margin-top:8px; font-size:13px; color:var(--sg-muted); min-height:18px;}
    .status.loading{color:var(--sg-primary);} 
    .status.loading::before{content:""; display:inline-block; width:12px; height:12px; border:2px solid var(--sg-primary); border-top-color:transparent; border-radius:50%; margin-right:6px; animation:spin .8s linear infinite; vertical-align:-2px;}
    @keyframes spin{to{transform:rotate(360deg);}}
    /* Mobile horizontal scroll & sticky header */
    @media (max-width: 768px) {
      .table-responsive{ overflow-x:auto; -webkit-overflow-scrolling:touch; }
      .table-responsive::-webkit-scrollbar{ height:8px; }
      .table-responsive::-webkit-scrollbar-thumb{ background: var(--sg-border); border-radius:8px; }
      #usersTable{ min-width: 700px; }
      #usersTable thead th{ position: sticky; top:0; background:#fff9ec; z-index:2; box-shadow:0 1px 0 var(--sg-border); }
      .scroll-hint{ display:flex; align-items:center; gap:6px; justify-content:flex-end; font-size:12px; color:var(--sg-muted); margin-top:6px; }
      .scroll-hint .arrow{ font-weight:700; }
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="header" role="banner">
      <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="Logo Website" />
      <h1 class="title">INFORMASI CARA LOGIN PENGGUNA - EPIC HUB LITE</h1>
    </div>

    <div class="card" aria-labelledby="info-title">
      <h2 id="info-title">Panduan Singkat</h2>
      <p class="muted"><strong>Silakan simak video panduan berikut ini:</strong></p>
      <div class="video-monitor">
        <div class="monitor-frame" role="figure" aria-label="Video panduan login EPIC Hub Lite">
          <div class="monitor-screen">
            <div class="video-container">
              <div id="ytFrame" aria-label="Pemutar video YouTube"></div>
              <noscript>
                <iframe src="https://www.youtube.com/embed/ybOwZOzh9DU?rel=0&modestbranding=1&playsinline=1" title="Panduan Cara Login Pengguna EPIC Hub" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen loading="lazy"></iframe>
              </noscript>
            </div>
          </div>
          <div class="monitor-stand" aria-hidden="true"></div>
        </div>
      </div>
      <ol>
        <li>Berikut ini adalah cara Anda untuk mengakses EPIC Hub Lite, silakan cari nama Anda pada kolom pencarian yang sudah disediakan.</li>
        <li>Perhatikan email dan nomor WhatsApp yang Anda daftarkan untuk menjadi EPI Channel.</li>
        <li>Gunakan akses untuk login EPIC Hub Lite dengan menggunakan password: <strong>4 karakter awal email</strong> dan <strong>4 karakter akhir nomor WhatsApp</strong> Anda.</li>
        <li>Contoh: email <em>epichanneljakarta@gmail.com</em> dan nomor WhatsApp <em>08123456789</em>, maka passwordnya adalah <strong>epic6789</strong>.</li>
        <li>
          Silakan login melalui tombol berikut ini: 
          <a class="btn btn-primary" href="https://bisnisemasperak.com/login" target="_blank" rel="noopener">LOGIN DISINI</a>
        </li>
        <li>Setelah berhasil login, ganti password Anda dengan password baru di halaman menu edit profil.</li>
        <li>Jika menemukan kendala login silakan kirim pesan ke Admin Arva dari bisnisemasperak.com.</li>
      </ol>
    </div>

    <div class="card" aria-labelledby="search-title">
      <h2 id="search-title">Cari Nama Anda</h2>
      <p class="muted">Gunakan kolom pencarian ini untuk menemukan data Anda dengan cepat.</p>
      <div class="search">
        <input type="text" id="searchInput" placeholder="Ketik nama lengkap atau email Anda..." aria-label="Cari pengguna" />
        <div id="searchStatus" class="status" role="status" aria-live="polite"></div>
      </div>
    </div>

    <div id="dataShield" class="card shield blur" aria-labelledby="table-title">
      <h2 id="table-title">Daftar Pengguna Terdaftar</h2>
      <p class="note">Privasi: 4 huruf awal email dan 4 angka akhir WhatsApp disembunyikan otomatis.</p>
      <div id="emptyState" class="empty-state" role="status">Data Anda Akan Tampil Disini</div>
      <div id="usersTableWrap" class="table-wrap is-hidden">
        <div class="table-responsive">
        <table id="usersTable" role="table" aria-label="Tabel pengguna">
          <thead>
            <tr>
              <th scope="col">No</th>
              <th scope="col">Nama Lengkap</th>
              <th scope="col">Email</th>
              <th scope="col">Nomor WhatsApp</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            $i = 1; 
            foreach ($rows as $r):
              $nama = htmlspecialchars($r['mem_nama'] ?? '', ENT_QUOTES, 'UTF-8');
              $emailMasked = maskEmailHideFirst4($r['mem_email'] ?? '');
              $waMasked = maskWhatsappHideLast4($r['mem_whatsapp'] ?? '');
            ?>
            <tr>
              <td data-label="No"><?php echo $i++; ?></td>
              <td data-label="Nama Lengkap"><?php echo $nama; ?></td>
              <td data-label="Email"><?php echo $emailMasked; ?></td>
              <td data-label="Nomor WhatsApp"><?php echo $waMasked; ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        </div>
        <div class="scroll-hint" aria-hidden="true"><span class="arrow">←</span> Geser untuk melihat kolom <span class="arrow">→</span></div>
      </div>
    </div>

    <div class="card" aria-labelledby="help-title">
      <h2 id="help-title">Butuh Bantuan?</h2>
      <p class="note">Sebelum menghubungi admin, pastikan Anda sudah membaca dengan detail perintah dan instruksi yang disampaikan di halaman ini (admin hanya melayani di waktu tertentu).</p>
      <a class="btn btn-wa" href="https://wa.me/6285176997327" target="_blank" rel="noopener">Chat Admin untuk Bantuan</a>
    </div>
  </div>

  <script src="https://www.youtube.com/iframe_api" referrerpolicy="origin"></script>
  <script>
    // Robust YouTube embed: prefer youtube-nocookie, fallback to youtube.com on error 150/153
    (function(){
      var ytConf = {
        videoId: 'ybOwZOzh9DU',
        hostPrimary: 'https://www.youtube-nocookie.com',
        hostFallback: 'https://www.youtube.com',
        origin: '<?php echo htmlspecialchars($origin, ENT_QUOTES, "UTF-8"); ?>'
      };
      var player = null;
      window.onYouTubeIframeAPIReady = function(){
        tryCreate(ytConf.hostPrimary);
      };
      function tryCreate(host){
        player = new YT.Player('ytFrame', {
          host: host,
          videoId: ytConf.videoId,
          playerVars: {
            modestbranding: 1,
            rel: 0,
            playsinline: 1,
            origin: ytConf.origin
          },
          events: {
            onReady: function(){ /* ready */ },
            onError: function(e){
              // 150/153: playback restricted / player config error
              if (host !== ytConf.hostFallback) {
                tryCreate(ytConf.hostFallback);
              }
            }
          }
        });
      }
    })();
  </script>
  <script>
    // Real-time search: fetch from API with caching, rate-limit, and graceful fallback
    const input = document.getElementById('searchInput');
    const statusEl = document.getElementById('searchStatus');
    const table = document.getElementById('usersTable');
    const tbody = table ? table.querySelector('tbody') : null;
    const wrap = document.getElementById('usersTableWrap');
    const emptyState = document.getElementById('emptyState');

    function showTable(show){
      if (!wrap || !emptyState) return;
      if (show){
        wrap.classList.remove('is-hidden');
        wrap.classList.add('is-visible');
        emptyState.style.display = 'none';
      } else {
        wrap.classList.remove('is-visible');
        wrap.classList.add('is-hidden');
        emptyState.style.display = '';
      }
    }

    function setStatus(text, loading=false){
      if (!statusEl) return;
      statusEl.textContent = text || '';
      statusEl.classList.toggle('loading', !!loading);
    }

    // Initial: hide table, show empty state
    showTable(false);
    setStatus('Masukkan kata kunci untuk menampilkan data');

    // Client-side cache for frequent queries (TTL 60s)
    const cache = new Map();
    function cacheSet(key, value){ cache.set(key, { value, ts: Date.now() }); }
    function cacheGet(key){
      const item = cache.get(key);
      if (!item) return null;
      if ((Date.now() - item.ts) > 60000) { cache.delete(key); return null; }
      return item.value;
    }

    // Rate limit: max 5 API hits per 30s on client side (server also enforces)
    const hits = [];
    function canHit(){
      const now = Date.now();
      while (hits.length && (now - hits[0]) > 30000) { hits.shift(); }
      return hits.length < 5;
    }
    function markHit(){ hits.push(Date.now()); }

    // Abort previous request
    let reqTimer = null; let ctrl = null;

    async function fetchResults(q){
      if (!tbody) return;
      // Empty query → reset
      if (!q){
        showTable(false);
        emptyState.textContent = 'Data Anda Akan Tampil Disini';
        setStatus('Masukkan kata kunci untuk menampilkan data');
        return;
      }

      // Use cache first
      const ck = q.toLowerCase();
      const cached = cacheGet(ck);
      if (cached) {
        renderRows(cached);
        setStatus(cached.length + ' hasil ditemukan (cache)');
        showTable(cached.length > 0);
        return;
      }

      // Client-side rate limit
      if (!canHit()){
        setStatus('Terlalu banyak pencarian. Coba lagi beberapa detik...', false);
        return;
      }
      markHit();

      // Abort previous
      if (ctrl) { ctrl.abort(); }
      ctrl = new AbortController();

      try {
        setStatus('Sedang mencari...', true);
        const resp = await fetch('/api/member-search?q=' + encodeURIComponent(q), {
          method: 'GET', signal: ctrl.signal, headers: { 'Accept': 'application/json' }
        });
        if (!resp.ok){
          if (resp.status === 429) {
            setStatus('Terlalu banyak pencarian. Coba lagi beberapa detik...', false);
          } else {
            setStatus('Terjadi kesalahan saat mencari data', false);
          }
          return;
        }
        const json = await resp.json();
        const items = (json && json.data && Array.isArray(json.data.items)) ? json.data.items : [];
        cacheSet(ck, items);
        renderRows(items);
        if (items.length > 0){
          setStatus(items.length + ' hasil ditemukan');
          showTable(true);
        } else {
          showTable(false);
          emptyState.textContent = 'Nama tidak ditemukan dalam database member EPIC Hub';
          setStatus('Tidak ada hasil');
        }
      } catch (err) {
        if (err && err.name === 'AbortError') return;
        setStatus('Terjadi kesalahan jaringan', false);
      }
    }

    function renderRows(items){
      // Replace tbody contents with server-masked results
      tbody.innerHTML = '';
      let i = 1;
      items.forEach(it => {
        const tr = document.createElement('tr');
        tr.innerHTML = '<td data-label="No">' + (i++) + '</td>' +
                       '<td data-label="Nama Lengkap">' + escapeHtml(it.nama || '') + '</td>' +
                       '<td data-label="Email">' + (it.email_masked || '') + '</td>' +
                       '<td data-label="Nomor WhatsApp">' + (it.wa_masked || '') + '</td>';
        tbody.appendChild(tr);
      });
    }

    function escapeHtml(str){
      return String(str).replace(/[&<>"']/g, function(m){ return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'})[m]; });
    }

    if (input && tbody){
      input.addEventListener('input', function(){
        const q = this.value.trim();
        clearTimeout(reqTimer);
        reqTimer = setTimeout(function(){
          fetchResults(q);
        }, 250);
      });
    }

    // Shield controls to unblur data after user confirmation
    const shield = document.getElementById('dataShield');
    const showBtn = document.getElementById('showDataBtn');
    if (showBtn && shield) {
      showBtn.addEventListener('click', () => {
        shield.classList.remove('blur');
      });
    }

    // Deterrents: disable context menu and common devtools shortcuts
    document.addEventListener('contextmenu', function(e){ e.preventDefault(); });
    document.addEventListener('keydown', function(e){
      const key = e.key.toLowerCase();
      if (e.ctrlKey && (key === 'u' || key === 's' || key === 'p' || key === 'c')) { e.preventDefault(); }
      if ((e.ctrlKey && e.shiftKey && (key === 'i' || key === 'c' || key === 'j')) || key === 'f12') { e.preventDefault(); }
    });
    document.addEventListener('copy', function(e){ e.preventDefault(); });
  </script>
</body>
</html>