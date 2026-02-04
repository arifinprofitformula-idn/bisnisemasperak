<?php
include('fungsi.php');
$settings = getsettings();
$moduleID = $_GET['moduleId'] ?? '';
$canvasId = $_GET['canvasId'] ?? '';

if (isset($_GET['moduleId'])) {
  $module = $_GET['moduleId'];
  $sel = array('','','','');

  if (isset($settings[$canvasId])) {
    $modsettings = json_decode($settings[$canvasId],TRUE);
    $modsettings = $modsettings['data'];
    if (isset($modsettings['lebar']) && isset($sel[$modsettings['lebar']])) {
      $sel[$modsettings['lebar']] = ' selected';
    }
  }

  echo '<form id="moduleSettingsForm">
  <div class="mb-3">
    <label for="'.$module.'Width" class="form-label">Lebar Modul</label>
    <select class="form-select" id="lebar" name="lebar">
      <option value="1" '.$sel[1].'>1</option>
      <option value="2" '.$sel[2].'>2</option>
      <option value="3" '.$sel[3].'>3</option>        
    </select>
  </div>
  ';

  if (function_exists($module.'_form')) {
    echo call_user_func($module . '_form');
  } else {
    echo global_form($module);
  }

  echo '
  <input type="hidden" name="moduleId" value="'.htmlspecialchars($moduleID).'">
  <input type="hidden" name="canvasId" value="'.htmlspecialchars($canvasId).'">
  </form>';
}

function global_form($module) {
  global $settings;
  if (isset($_GET['canvasId']) && !empty($_GET['canvasId'])) {
    $moduleID = $_GET['canvasId']??='';
    if (isset($settings[$moduleID])) {
      $modsettings = json_decode($settings[$moduleID],TRUE);
      $modsettings = $modsettings['data'];
    }
    
    return '
      <div class="mb-3">
        <label for="'.$module.'Title" class="form-label">Judul Modul</label>
        <input type="text" class="form-control" id="'.$module.'Title" name="'.$module.'Title" value="'.($modsettings[$module.'Title']??='').'" placeholder="Masukkan judul modul">
      </div>';
  }
}

// Fungsi untuk memuat form modul Informasi
function klienbaru_form() {
  global $settings;
  if (isset($_GET['canvasId']) && isset($settings[$_GET['canvasId']]) && !empty($_GET['canvasId'])) {
    $jsettings = json_decode($settings[$_GET['canvasId']],TRUE);
    $modsettings = $jsettings['data'];
  }

  return '
    <div class="mb-3">
        <label for="klienbaruTitle" class="form-label">Judul Modul</label>
        <input type="text" class="form-control" name="klienbaruTitle" value="'.($modsettings['klienbaruTitle']??='').'" placeholder="Masukkan judul modul">
    </div>
    <div class="mb-3">
        <label for="klien_jumlah" class="form-label">Jumlah List</label>
        <input type="number" class="form-control" name="klien_jumlah" value="'.($modsettings['klien_jumlah']??='').'">
    </div>
    <div class="mb-3">
        <label for="klien_format" class="form-label">Format List</label>
        <textarea class="form-control" name="klien_format" placeholder="[nama] - [tgldaftar]">'.($modsettings['klien_format']??='').'</textarea>
    </div>';
}

function leaderboard_form() {
  global $settings;
  $show = '';
  $sel = array('','','','','');
  if (isset($_GET['canvasId']) && !empty($_GET['canvasId'])) {
    $moduleID = $_GET['canvasId']??='';
    if (isset($settings[$moduleID])) {
      $modsettings = json_decode($settings[$moduleID],TRUE);
      $modsettings = $modsettings['data'];
    }    

    if (isset($modsettings['leader_type'])) { $sel[$modsettings['leader_type']] = ' selected'; }
  }

  $show = '
    <div class="mb-3">
        <label for="klienbaruTitle" class="form-label">Judul Modul</label>
        <input type="text" class="form-control" name="leaderboardTitle" value="'.($modsettings['leaderboardTitle']??='').'" placeholder="Masukkan judul modul">
    </div>
    <div class="mb-3">
        <label for="klien_format" class="form-label">Kategori</label>
        <select name="leader_type" class="form-select">
          <option value="1"'.$sel[1].'>Omset Terbanyak</option>
          <option value="2"'.$sel[2].'>Komisi Terbanyak</option>
          <option value="3"'.$sel[3].'>Rekrut Member Terbanyak</option>
        </select>
    </div>
    <div class="row mb-3">
        <label for="leader_start" class="form-label">Rentang Waktu</label>
        <div class="col">
          <input type="date" class="form-control" value="'.($modsettings['leader_start']??='').'" name="leader_start">
          <small class="text-muted">start</small>
        </div>
        <div class="col">
          <input type="date" class="form-control" value="'.($modsettings['leader_end']??='').'" name="leader_end">
          <small class="text-muted">end</small>
        </div>
    </div>
    <div class="mb-3">
        <label for="leader_jumlah" class="form-label">Jumlah List</label>
        <input type="number" class="form-control" name="leader_jumlah" value="'.($modsettings['leader_jumlah']??='').'">
    </div>
    <div class="mb-3">
        <label for="leader_format" class="form-label">Format List</label>
        <textarea class="form-control" name="leader_format" placeholder="[nama] - [jumlah]">'.($modsettings['leader_format']??='').'</textarea>
        <small class="text-muted"><code>[jumlah]</code> : jumlah omset, komisi atau rekrut (tergantung kategori yg dipilih)</small>
    </div>
    ';

  return $show;
}

function text_form() {
  global $weburl,$settings;
  $show = '';
  if (isset($_GET['canvasId']) && !empty($_GET['canvasId'])) {
    $moduleID = $_GET['canvasId']??='';
    if (isset($settings[$moduleID])) {
      $modsettings = json_decode($settings[$moduleID],TRUE);
      $modsettings = $modsettings['data'];
    }
  }

  $show = '
    <div class="mb-3">
        <label for="textTitle" class="form-label">Judul Modul</label>
        <input type="text" class="form-control" name="textTitle" value="'.($modsettings['textTitle']??='').'" placeholder="Masukkan judul modul">
    </div>
    <div class="mb-3">
        <label for="editor" class="form-label">Konten</label>
        <textarea class="form-control editor" name="text_konten" rows="5">'.($modsettings['text_konten'] ?? '').'</textarea>
        <small class="text-muted">Support kode HTML</small>

    </div>
';
  return $show;
}
?>
