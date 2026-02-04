<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
$head['pagetitle']='Pengaturan Peta';
showheader($head);

$providers = ['osm' => 'OpenStreetMap (Leaflet)', 'google' => 'Google Maps', 'mapbox' => 'Mapbox'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['map_provider'])) {
  $errors = [];
  $data   = [];

  $prov = strtolower(trim($_POST['map_provider']));
  if (!in_array($prov, array_keys($providers))) { $errors[] = 'Jenis peta tidak valid.'; } else { $data['map_provider'] = $prov; }

  $api  = trim($_POST['map_api_key'] ?? '');
  if ($prov === 'google' || $prov === 'mapbox') {
    if ($api === '') { $errors[] = 'API key wajib diisi untuk penyedia yang dipilih.'; }
  }
  if ($api !== '') { $data['map_api_key'] = $api; }

  $zoom = intval($_POST['map_zoom'] ?? 4);
  if ($zoom < 2 || $zoom > 18) { $errors[] = 'Zoom default harus antara 2–18.'; } else { $data['map_zoom'] = (string)$zoom; }

  $lat  = trim($_POST['map_main_lat'] ?? '');
  $lng  = trim($_POST['map_main_lng'] ?? '');
  if ($lat !== '' && (!is_numeric($lat) || $lat < -11 || $lat > 6)) { $errors[] = 'Latitude toko utama harus dalam batas Indonesia (-11 s.d 6).'; }
  if ($lng !== '' && (!is_numeric($lng) || $lng < 95 || $lng > 141)) { $errors[] = 'Longitude toko utama harus dalam batas Indonesia (95 s.d 141).'; }
  if ($lat !== '') { $data['map_main_lat'] = (string)floatval($lat); }
  if ($lng !== '') { $data['map_main_lng'] = (string)floatval($lng); }

  $color = trim($_POST['map_theme_color'] ?? '');
  if ($color !== '' && !preg_match('/^#?[0-9a-fA-F]{6}$/', $color)) { $errors[] = 'Warna tema harus hex 6 digit.'; }
  if ($color !== '') { $data['map_theme_color'] = ltrim($color, '#'); }

  if (count($errors) === 0) {
    $settings = updatesettings($data);
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert"><strong>Ok!</strong> Pengaturan peta disimpan.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
  } else {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert"><strong>Error!</strong> '.htmlspecialchars(implode('\n', $errors), ENT_QUOTES).'<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
  }
}

$map_provider = $settings['map_provider'] ?? 'osm';
$map_api_key  = $settings['map_api_key'] ?? '';
$map_zoom     = intval($settings['map_zoom'] ?? 4);
$map_lat      = $settings['map_main_lat'] ?? '';
$map_lng      = $settings['map_main_lng'] ?? '';
$map_color    = '#'.($settings['map_theme_color'] ?? 'D4AF37');
?>

<div class="card">
  <div class="card-header">Pengaturan Peta Indonesia</div>
  <div class="card-body">
    <form action="" method="post">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Jenis Peta</label>
          <select name="map_provider" class="form-select" required>
            <?php foreach ($providers as $key => $label) { echo '<option value="'.$key.'"'.($map_provider===$key?' selected':'').'>'.$label.'</option>'; } ?>
          </select>
          <small class="text-muted">Pilih penyedia peta yang digunakan.</small>
        </div>
        <div class="col-md-6">
          <label class="form-label">API Key</label>
          <input type="text" name="map_api_key" class="form-control" value="<?= htmlspecialchars($map_api_key, ENT_QUOTES); ?>" placeholder="Isi jika menggunakan Google/Mapbox">
          <small class="text-muted">Wajib untuk Google Maps/Mapbox. Kosongkan jika memakai OpenStreetMap.</small>
        </div>
        <div class="col-md-4">
          <label class="form-label">Zoom Default</label>
          <input type="number" name="map_zoom" class="form-control" min="2" max="18" value="<?= $map_zoom; ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Latitude Toko Utama</label>
          <input type="text" name="map_main_lat" class="form-control" value="<?= htmlspecialchars($map_lat, ENT_QUOTES); ?>" placeholder="-6.200000">
        </div>
        <div class="col-md-4">
          <label class="form-label">Longitude Toko Utama</label>
          <input type="text" name="map_main_lng" class="form-control" value="<?= htmlspecialchars($map_lng, ENT_QUOTES); ?>" placeholder="106.816666">
        </div>
        <div class="col-md-6">
          <label class="form-label">Warna Tema Peta</label>
          <div class="input-group">
            <span class="input-group-text">#</span>
            <input type="text" name="map_theme_color" class="form-control" value="<?= htmlspecialchars(ltrim($map_color,'#'), ENT_QUOTES); ?>" placeholder="D4AF37" maxlength="6" pattern="[0-9a-fA-F]{6}">
          </div>
          <small class="text-muted">Format hex 6 digit, contoh: D4AF37 (gold).</small>
        </div>
      </div>
      <div class="mt-3">
        <button type="submit" class="btn btn-secondary">Simpan</button>
      </div>
    </form>
  </div>
</div>

<?php showfooter(); ?>

