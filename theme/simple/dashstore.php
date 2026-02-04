<?php 
if (!defined('IS_IN_SCRIPT')) { define('IS_IN_SCRIPT', true); }
if (!isset($datamember['mem_role']) || $datamember['mem_role'] < 5) { die(); exit(); }

$__root = dirname(__DIR__, 2);
@include_once $__root . DIRECTORY_SEPARATOR . 'plugin' . DIRECTORY_SEPARATOR . 'epi-whatsapp-login' . DIRECTORY_SEPARATOR . 'helpers.php';

$head['pagetitle'] = 'Daftar EPI Store';
$head['scripthead'] = '<link href="'.$weburl.'fontawesome/css/brands.min.css" rel="stylesheet">';
showheader($head);

$okmsg = $errmsg = '';

// Delete action
if (isset($_GET['del']) && is_numeric($_GET['del'])) {
  $id = intval($_GET['del']);
  $cek = db_query("DELETE FROM `sa_epistore` WHERE `id`=".$id);
  if ($cek === false) {
    $errmsg = db_error();
  } else {
    $okmsg = 'EPI Store ID: '.$id.' telah dihapus.';
  }
}

// Save action
if (isset($_POST['aksi']) && $_POST['aksi'] === 'save') {
  $nama_store   = trim($_POST['nama_store'] ?? '');
  $manager_nama = trim($_POST['manager_nama'] ?? '');
  $wa_nomor     = trim($_POST['wa_nomor'] ?? '');
  $provinsi     = trim($_POST['provinsi'] ?? '');
  $kota         = trim($_POST['kota'] ?? '');
  $lat          = trim($_POST['lat'] ?? '');
  $lng          = trim($_POST['lng'] ?? '');
  $nomor_kode   = strtoupper(trim($_POST['nomor_kode'] ?? ''));

  if ($lat !== '' && $lng !== '' && is_numeric($lat) && is_numeric($lng)) {
    $latNum = floatval($lat); $lngNum = floatval($lng);
    if ($latNum >= 95 && $latNum <= 141 && $lngNum >= -11 && $lngNum <= 6) {
      $tmp = $lat; $lat = $lng; $lng = $tmp;
      $okmsg = ($okmsg !== '' ? $okmsg.' ' : '').'Koordinat lat/lng tertukar, sistem telah mengoreksi.';
    }
  }

  $errors = [];
  if ($nama_store === '' || strlen($nama_store) < 3) { $errors[] = 'Nama EPI Store minimal 3 karakter.'; }
  if ($manager_nama === '' || strlen($manager_nama) < 3) { $errors[] = 'Nama Store Manager minimal 3 karakter.'; }
  $wa_norm = epi_normalize_phone($wa_nomor);
  if ($wa_norm === '' || !preg_match('/^\d{8,15}$/', $wa_norm)) { $errors[] = 'Nomor WhatsApp tidak valid.'; }
  if ($lat !== '' && (!is_numeric($lat) || $lat < -90 || $lat > 90)) { $errors[] = 'Latitude tidak valid.'; }
  if ($lng !== '' && (!is_numeric($lng) || $lng < -180 || $lng > 180)) { $errors[] = 'Longitude tidak valid.'; }
  if ($lat !== '' && is_numeric($lat)) { $ln = floatval($lat); if ($ln < -11 || $ln > 6) { $errors[] = 'Latitude harus dalam wilayah Indonesia (-11..6).'; } }
  if ($lng !== '' && is_numeric($lng)) { $lnx = floatval($lng); if ($lnx < 95 || $lnx > 141) { $errors[] = 'Longitude harus dalam wilayah Indonesia (95..141).'; } }
  if ($nomor_kode !== '' && !preg_match('/^EPIS\d{2}$/', $nomor_kode)) { $errors[] = 'Format nomor kode harus EPIS diikuti 2 angka, contoh EPIS01.'; }
  if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $id = intval($_GET['edit']);
    if ($nomor_kode !== '') {
      $cnt = intval(db_var("SELECT COUNT(*) FROM `sa_epistore` WHERE `nomor_kode`='".cek($nomor_kode)."' AND `id`<>".$id));
      if ($cnt > 0) { $errors[] = 'Nomor kode sudah digunakan. Gunakan nomor lain.'; }
    }
  } else {
    if ($nomor_kode !== '') {
      $cnt = intval(db_var("SELECT COUNT(*) FROM `sa_epistore` WHERE `nomor_kode`='".cek($nomor_kode)."'"));
      if ($cnt > 0) { $errors[] = 'Nomor kode sudah digunakan. Gunakan nomor lain.'; }
    }
  }

  if (count($errors) === 0) {
    $now = date('Y-m-d H:i:s');
    if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
      $id = intval($_GET['edit']);
      if ($nomor_kode === '') {
        $last = intval(db_var("SELECT MAX(CAST(SUBSTRING(`nomor_kode`,5) AS UNSIGNED)) FROM `sa_epistore` WHERE `nomor_kode` REGEXP '^EPIS[0-9]{2}$'"));
        $nomor_kode = 'EPIS'.str_pad($last+1, 2, '0', STR_PAD_LEFT);
      }
      $q = "UPDATE `sa_epistore` SET 
            `nama_store`='".cek($nama_store)."',
            `manager_nama`='".cek($manager_nama)."',
            `wa_nomor`='".cek($wa_norm)."',
            `provinsi`='".cek($provinsi)."',
            `kota`='".cek($kota)."',
            `lat`=".(($lat!=='')?floatval($lat):'NULL').",
            `lng`=".(($lng!=='')?floatval($lng):'NULL').",
            `nomor_kode`='".cek($nomor_kode)."',
            `updated_at`='".$now."'
          WHERE `id`=".$id;
      $cek = db_query($q);
      if ($cek === false) { $errmsg = db_error(); } else { $okmsg = 'Data EPI Store diperbarui.'; }
    } else {
      if ($nomor_kode === '') {
        $last = intval(db_var("SELECT MAX(CAST(SUBSTRING(`nomor_kode`,5) AS UNSIGNED)) FROM `sa_epistore` WHERE `nomor_kode` REGEXP '^EPIS[0-9]{2}$'"));
        $nomor_kode = 'EPIS'.str_pad($last+1, 2, '0', STR_PAD_LEFT);
      }
      $q = "INSERT INTO `sa_epistore` (`nama_store`,`manager_nama`,`wa_nomor`,`provinsi`,`kota`,`lat`,`lng`,`status`,`created_at`,`updated_at`,`nomor_kode`) VALUES (
            '".cek($nama_store)."','".cek($manager_nama)."','".cek($wa_norm)."','".cek($provinsi)."','".cek($kota)."',".(($lat!=='')?floatval($lat):'NULL').",".(($lng!=='')?floatval($lng):'NULL').",1,'".$now."','".$now."','".cek($nomor_kode)."')";
      $cek = db_query($q);
      if ($cek === false) { $errmsg = db_error(); } else { $okmsg = 'EPI Store baru ditambahkan.'; }
    }
  } else {
    $errmsg = implode('<br/>', $errors);
  }
}

if ($okmsg !== '') {
  echo '<div class="alert alert-success alert-dismissible fade show" role="alert"><strong>Ok!</strong> '.htmlspecialchars($okmsg, ENT_QUOTES).'<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
}
if ($errmsg !== '') {
  echo '<div class="alert alert-danger alert-dismissible fade show" role="alert"><strong>Error!</strong> '.htmlspecialchars($errmsg, ENT_QUOTES).'<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
}

if (isset($_GET['edit'])) {
  $editData = [
    'nama_store' => '', 'manager_nama' => '', 'wa_nomor' => '',
    'provinsi' => '', 'kota' => '', 'lat' => '', 'lng' => '', 'nomor_kode' => ''
  ];
  if (is_numeric($_GET['edit'])) {
    $row = db_row('SELECT * FROM `sa_epistore` WHERE `id`='.intval($_GET['edit']));
    if ($row) { $editData = $row; }
  }
  ?>
  <form action="?edit=<?= htmlspecialchars($_GET['edit'], ENT_QUOTES); ?>" method="post">
    <input type="hidden" name="aksi" value="save">
    <div class="card mb-3">
      <div class="card-header"><?= is_numeric($_GET['edit']) ? 'Edit' : 'Tambah'; ?> EPI Store</div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Nama EPI Store</label>
            <input type="text" name="nama_store" class="form-control" required minlength="3" value="<?= htmlspecialchars($editData['nama_store'] ?? '', ENT_QUOTES); ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Nomor Kode (EPISxx)</label>
            <input type="text" name="nomor_kode" class="form-control" pattern="EPIS[0-9]{2}" title="Format EPIS diikuti 2 angka, contoh EPIS01" value="<?= htmlspecialchars($editData['nomor_kode'] ?? '', ENT_QUOTES); ?>">
            <small class="text-muted">Kosongkan untuk otomatis diisi urutan berikutnya.</small>
          </div>
          <div class="col-md-6">
            <label class="form-label">Nama Store Manager</label>
            <input type="text" name="manager_nama" class="form-control" required minlength="3" value="<?= htmlspecialchars($editData['manager_nama'] ?? '', ENT_QUOTES); ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Nomor WhatsApp</label>
            <input type="text" name="wa_nomor" class="form-control" inputmode="numeric" pattern="[0-9]+" title="Masukkan angka saja" required value="<?= htmlspecialchars($editData['wa_nomor'] ?? '', ENT_QUOTES); ?>">
            <small class="text-muted">Gunakan format Indonesia, mis. 0812xxxx atau 62xxxx (akan dinormalisasi).</small>
          </div>
          <div class="col-md-6">
            <label class="form-label">Provinsi</label>
            <input type="text" name="provinsi" class="form-control" value="<?= htmlspecialchars($editData['provinsi'] ?? '', ENT_QUOTES); ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Kota/Kabupaten</label>
            <input type="text" name="kota" class="form-control" value="<?= htmlspecialchars($editData['kota'] ?? '', ENT_QUOTES); ?>">
          </div>
          <div class="col-12">
            <label class="form-label">Pilih Kabupaten/Kota (Auto isi koord.)</label>
            <div class="input-group">
              <span class="input-group-text">Cari</span>
              <input type="text" id="kabkotaFilter" class="form-control" placeholder="Ketik nama kabupaten/kota">
            </div>
            <select id="kabkotaSelect" class="form-select mt-2" size="8" aria-label="Daftar kabupaten/kota">
              <option value="">Memuat data...</option>
            </select>
            <small class="text-muted">Memilih kabupaten/kota akan otomatis mengisi Latitude dan Longitude.</small>
          </div>
          <div class="col-md-3">
            <label class="form-label">Latitude</label>
            <input type="text" name="lat" class="form-control" value="<?= htmlspecialchars($editData['lat'] ?? '', ENT_QUOTES); ?>">
            <small class="text-muted">Contoh Banda Aceh: 5.5483</small>
          </div>
          <div class="col-md-3">
            <label class="form-label">Longitude</label>
            <input type="text" name="lng" class="form-control" value="<?= htmlspecialchars($editData['lng'] ?? '', ENT_QUOTES); ?>">
            <small class="text-muted">Contoh Banda Aceh: 95.3238</small>
          </div>
        </div>
      </div>
      <div class="card-footer d-flex justify-content-between">
        <a href="<?= $weburl.'dashboard/daftar-epi-store'; ?>" class="btn btn-secondary">Batal</a>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </div>
    <script>
      (function(){
        const sel = document.getElementById('kabkotaSelect');
        const flt = document.getElementById('kabkotaFilter');
        const lat = document.querySelector('input[name="lat"]');
        const lng = document.querySelector('input[name="lng"]');
        const kota = document.querySelector('input[name="kota"]');
        const prov = document.querySelector('input[name="provinsi"]');
        let data = [];
        let view = [];
        function render(list){
          sel.innerHTML = '';
          const frag = document.createDocumentFragment();
          list.forEach(d=>{
            const opt = document.createElement('option');
            opt.value = d.nama;
            opt.textContent = d.provinsi+' — '+d.nama;
            opt.dataset.lat = d.lat;
            opt.dataset.lng = d.lng;
            opt.dataset.prov = d.provinsi;
            frag.appendChild(opt);
          });
          sel.appendChild(frag);
        }
        function filter(){
          const q = flt.value.trim().toLowerCase();
          if (!q){ view = data; render(view); return; }
          const res = data.filter(d=> (d.nama+' '+d.provinsi).toLowerCase().includes(q));
          view = res; render(view);
        }
        flt.addEventListener('input', filter);
        sel.addEventListener('change', function(){
          const opt = sel.options[sel.selectedIndex];
          if (!opt) return;
          lat.value = opt.dataset.lat || '';
          lng.value = opt.dataset.lng || '';
          kota.value = opt.value || '';
          prov.value = opt.dataset.prov || '';
        });
        fetch('<?= $weburl ?>assets/geo/kabkota.json').then(r=>r.json()).then(j=>{ data = Array.isArray(j)? j : (Array.isArray(j.data)? j.data: []); view = data; render(view); }).catch(()=>{ sel.innerHTML = '<option>Gagal memuat data kabupaten/kota</option>'; });
      })();
    </script>
  </form>
  <?php
} else {
  ?>
  <form action="" method="get">
    <div class="card mb-3">
      <div class="card-body">
        <div class="row">
          <div class="col-sm-9">
            <div class="input-group">
              <input type="text" class="form-control" name="cari" value="<?= htmlspecialchars($_GET['cari'] ?? '', ENT_QUOTES); ?>" placeholder="Cari nama store / manager">
              <button class="btn btn-secondary" type="submit">Cari</button>
            </div>
          </div>
          <div class="col-sm-3 text-end">
            <a href="daftar-epi-store?edit=new" class="btn btn-success">Tambah</a>
          </div>
        </div>
      </div>
    </div>
  </form>

  <div class="table-responsive">
  <table class="table table-hover table-bordered">
    <thead class="table-secondary">
      <tr>
        <th>ID</th>
        <th>Nomor</th>
        <th>Nama EPI Store</th>
        <th class="d-none d-sm-table-cell">Store Manager</th>
        <th class="d-none d-sm-table-cell">WhatsApp</th>
        <th class="d-none d-sm-table-cell">Provinsi/Kota</th>
        <th>&nbsp;</th>
      </tr>
    </thead>
    <tbody>
      <?php 
      $jmlperpage = 20;
      if (isset($_GET['start']) && is_numeric($_GET['start'])) { $start = (intval($_GET['start']) - 1) * $jmlperpage; $page = intval($_GET['start']); } else { $start = 0; $page = 1; }
      $where = 'WHERE `status`=1';
      if (isset($_GET['cari']) && $_GET['cari'] !== '') {
        $s = cek($_GET['cari']);
        $where .= " AND (`nama_store` LIKE '%".$s."%' OR `manager_nama` LIKE '%".$s."%')";
      }
      $data = db_select("SELECT * FROM `sa_epistore` $where ORDER BY `nama_store` ASC LIMIT ".$start.",".$jmlperpage);
      if (is_array($data) && count($data) > 0) {
        foreach ($data as $d) {
          $wa = epi_normalize_phone($d['wa_nomor'] ?? '');
          echo '<tr>';
          echo '<td>'.intval($d['id']).'</td>';
          echo '<td><span class="badge bg-warning text-dark">'.htmlspecialchars($d['nomor_kode'] ?? '', ENT_QUOTES).'</span></td>';
          echo '<td><a href="daftar-epi-store?edit='.intval($d['id']).'">'.htmlspecialchars($d['nama_store'] ?? '', ENT_QUOTES).'</a></td>';
          echo '<td class="d-none d-sm-table-cell">'.htmlspecialchars($d['manager_nama'] ?? '', ENT_QUOTES).'</td>';
          echo '<td class="d-none d-sm-table-cell"><a href="https://wa.me/'.$wa.'" target="_blank" rel="noopener">'.$wa.'</a></td>';
          $lok = trim(($d['provinsi'] ?? '').' / '.($d['kota'] ?? '')); 
          echo '<td class="d-none d-sm-table-cell">'.htmlspecialchars($lok, ENT_QUOTES).'</td>';
          echo '<td class="text-end">';
          echo '<a href="#" data-bs-toggle="modal" data-bs-target="#konfirmasi" data-bs-nama="'.htmlspecialchars($d['nama_store'] ?? '', ENT_QUOTES).'" data-bs-id="'.intval($d['id']).'"><i class="fa-solid fa-trash-can text-danger" title="Delete"></i></a>';
          echo '</td>';
          echo '</tr>';
        }
      } else {
        echo '<tr><td colspan="6">Belum ada EPI Store</td></tr>';
      }
      ?>
    </tbody>
  </table>
  </div>
  <?php
  $jml = db_var("SELECT COUNT(*) FROM `sa_epistore` $where");
  $jmlpage = max(1, ceil($jml/$jmlperpage));
  echo '<nav aria-label="Page navigation" class="mt-3"><ul class="pagination">';
  if ($jmlpage > 10) {
    if ($page <= 4){
      for ($i=1;$i<=5;$i++) {
          $active = ($i==$page)?' active':'';
          echo '<li class="page-item'.$active.'"><a class="page-link" href="?start='.$i.'">'.$i.'</a></li>';
      }
      echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
      echo '<li class="page-item"><a class="page-link" href="?start='.$jmlpage.'">'.$jmlpage.'</a></li>';
    } elseif ($page >= 5 && $page <= ($jmlpage-5)) {
      echo '<li class="page-item"><a class="page-link" href="?start=1">1</a></li>';
      echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
      for ($i=($page-2);$i<=($page+2);$i++) {
          $active = ($i==$page)?' active':'';
          echo '<li class="page-item'.$active.'"><a class="page-link" href="?start='.$i.'">'.$i.'</a></li>';
      }
      echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
      echo '<li class="page-item"><a class="page-link" href="?start='.$jmlpage.'">'.$jmlpage.'</a></li>';
    } else {
      echo '<li class="page-item"><a class="page-link" href="?start=1">1</a></li>';
      echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
      for ($i=($jmlpage-5);$i<=$jmlpage;$i++) {
          $active = ($i==$page)?' active':'';
          echo '<li class="page-item'.$active.'"><a class="page-link" href="?start='.$i.'">'.$i.'</a></li>';
      }
    }
  } else {
    for ($i=1;$i<=$jmlpage;$i++) {
        $active = ($i==$page)?' active':'';
        echo '<li class="page-item'.$active.'"><a class="page-link" href="?start='.$i.'">'.$i.'</a></li>';
    }
  }
  echo '</ul></nav>';
}

$footer['konfirm'] = "⚠️ Anda akan menghapus <strong>'+nama+'</strong>.";
showfooter($footer);
?>
