<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
if ($datamember['mem_role'] < 9) { die(); exit(); }
$head['pagetitle'] ='Setting Payment';
$head['scripthead'] = '
<link href="'.$weburl.'editor/css/froala_editor.pkgd.min.css" rel="stylesheet" type="text/css" />
<link href="'.$weburl.'editor/css/froala_style.min.css" rel="stylesheet" type="text/css" />
<style>
.card-header .fas.fa-caret-down {
  transition: transform 0.2s;
}

.card-header.collapsed .fas.fa-caret-down {
  transform: rotate(-90deg);
}
a[id="fr-logo"] {
  height:1px !important;
}
p[data-f-id="pbf"] {
  height:1px !important;
}
a[href*="www.froala.com"] {
  height:1px !important;
  background: #fff !important
}
</style>';
// Export CSV for Admin Finance Log
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
if (isset($_GET['export_finance_log']) && $_GET['export_finance_log']=='1') {
    $lim = (int)($_GET['log_limit'] ?? 1000); if ($lim<1) { $lim=1000; } if ($lim>20000) { $lim=20000; }
    $w = [];
    if (!empty($_GET['log_action'])) { $w[] = "`action`='".cek($_GET['log_action'])."'"; }
    if (!empty($_GET['log_q'])) { $q = cek($_GET['log_q']); $w[] = "(`order_id` LIKE '%".$q."%' OR `admin_wa` LIKE '%".$q."%' OR `ip` LIKE '%".$q."%' OR `info` LIKE '%".$q."%')"; }
    $whereLog = count($w)>0 ? ('WHERE '.implode(' AND ',$w)) : '';
    $logs = db_select("SELECT `created_at`,`action`,`admin_wa`,`order_id`,`changed_by`,`ip`,`info` FROM `epi_admin_finance_log` ".$whereLog." ORDER BY `created_at` DESC, `id` DESC LIMIT ".$lim);
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="admin_finance_log.csv"');
    echo "created_at,action,admin_wa,order_id,changed_by,ip,info\n";
    if (is_array($logs)) {
        foreach($logs as $lg){
            $row = [
                (string)$lg['created_at'], (string)$lg['action'], (string)$lg['admin_wa'], (int)$lg['order_id'], (int)$lg['changed_by'], (string)$lg['ip'], str_replace(["\r","\n"],' ', (string)$lg['info'])
            ];
            $csv = array_map(function($v){ return '"'.str_replace('"','""',$v).'"'; }, $row);
            echo implode(',', $csv)."\n";
        }
    }
    exit;
}
showheader($head);
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
if (isset($_POST) && count($_POST) > 0) {
    $prev = getsettings();
    $newsettings = $_POST;
    $newsettings = str_replace('<p data-f-id="pbf" style="text-align: center; font-size: 14px; margin-top: 30px; opacity: 0.65; font-family: sans-serif;">Powered by <a href="https://www.froala.com/wysiwyg-editor?pb=1" title="Froala Editor">Froala Editor</a></p>','',$newsettings);
    if (!isset($_POST['tripay_sandbox'])) { $newsettings['tripay_sandbox'] = 0; }

    if (isset($_POST['enable_wa_admin'])) {
        $wa = preg_replace('/[^0-9]/','', $_POST['wa_admin'] ?? '');
        if (!empty($wa)) {
            if (strlen($wa) < 10 || strlen($wa) > 15) {
                echo '<div class="alert alert-danger alert-dismissible fade show" role="alert"><strong>Error!</strong> Nomor WhatsApp admin harus 10–15 digit.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            } else {
                $newsettings['wa_admin'] = $wa;
            }
        } else {
            $newsettings['wa_admin'] = '';
        }
    } else {
        $newsettings['wa_admin'] = '';
    }

    $settings = updatesettings($newsettings);
    if ($settings !== false) {
        $old = preg_replace('/[^0-9]/','', $prev['wa_admin'] ?? '');
        $cur = preg_replace('/[^0-9]/','', $settings['wa_admin'] ?? '');
        if ($old !== $cur) {
            db_query("INSERT INTO `epi_admin_finance_log` (`action`,`admin_wa`,`changed_by`,`old_value`,`new_value`,`ip`) VALUES ('number_changed','".cek($cur)."',".(int)$datamember['mem_id'].",'".cek($old)."','".cek($cur)."','".cek(realIP())."')");
        }
    }

    if (isset($_POST['enable_bank']) && isset($_POST['bank_code'])) {
        $code = strtolower(cek($_POST['bank_code']));
        $acc = preg_replace('/[^0-9]/','', $_POST['bank_account'] ?? '');
        $name = cek($_POST['bank_name'] ?? '');
        $valid = (strlen($acc) >= 8 && strlen($acc) <= 20 && strlen($name) >= 3);
        $banks = isset($settings['bank_accounts']) ? json_decode($settings['bank_accounts'], true) : [];
        if (!is_array($banks)) { $banks = []; }
        if ($valid) {
            $mapNames = ['bca'=>'Bank Central Asia (BCA)','mandiri'=>'Bank Mandiri','bsi'=>'Bank Syariah Indonesia (BSI)','bri'=>'Bank Rakyat Indonesia (BRI)','bni'=>'Bank Negara Indonesia (BNI)'];
            $exists = false;
            foreach ($banks as &$b) { if ($b['code'] === $code) { $b['account'] = $acc; $b['owner'] = $name; $exists=true; break; } }
            if (!$exists) { $banks[] = ['code'=>$code,'account'=>$acc,'owner'=>$name,'label'=>($mapNames[$code]??strtoupper($code))]; }
            $newsettings['bank_accounts'] = json_encode($banks);
            $settings = updatesettings($newsettings);
            $dir = __DIR__.'/../../upload/settings'; if (!is_dir($dir)) { @mkdir($dir,0777,true); }
            @file_put_contents($dir.'/bank_accounts.json', json_encode(['updated_at'=>date('c'),'data'=>$banks]));
        } else {
            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert"><strong>Error!</strong> Format rekening tidak valid.</div>';
        }
    }

    if ($settings === false) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert"><strong>Error!</strong> '.db_error().'<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    } else {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert"><strong>Ok!</strong> Setting telah disimpan.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    }
}
?>

<form action="" method="post">
<div class="card mb-3">
  <div class="card-header" onclick="toggleCardBody('manual')">
    <i class="fas fa-caret-down"></i> Setting Pembayaran
  </div>
  <div class="card-body" id="manual">
  	<div class="mb-3 row">
	    <label class="col-sm-2 col-form-label">Kode Unik</label>
	    <div class="col-sm-10">
	      <select name="kodeunik" class="form-select">
	      	<?php
	      	$unik = array('','','');
	      	if (isset($settings['kodeunik'])) { $unik[$settings['kodeunik']] = ' selected'; }	      	
	      	?>
	      	<option value="0"<?= $unik[0]; ?>>Tanpa Kode Unik (100,000)</option>
	      	<option value="1"<?= $unik[1]; ?>>Kurangi dari harga asli (99,003)</option>
	      	<option value="2"<?= $unik[2]; ?>>Tambah dari harga asli (100,003)</option>
	      </select>
	    </div>
	  </div>
	  <div class="mb-3 row">
	    <label class="col-sm-2 col-form-label">Instruksi Pembayaran Manual</label>
	    <div class="col-sm-10">
	      <textarea class="form-control" id="editor" rows="5"  name="carapembayaran"><?= htmlspecialchars($settings['carapembayaran'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
      <small class="form-text text-muted">Petunjuk cara pembayaran di halaman invoice. Gunakan shortcode:<br/>
      <code>[harga]</code> untuk menampilkan harga akhir yang dibayar (Harga Tampil; sudah termasuk diskon bila ada; 0 jika gratis)<br/>
      <code>[harga_normal]</code> untuk menampilkan harga normal sebelum diskon<br/>
      <code>[diskon]</code> untuk menampilkan nilai diskon (0 jika tidak ada)<br/>
      <code>[hargaunik]</code> untuk menampilkan jumlah transfer dengan kode unik (mengacu Harga Tampil; 0 jika gratis)<br/>
      <code>[hargacopy]</code> untuk menampilkan jumlah transfer dengan kode unik tanpa penanda ribuan<br/>
      <code>[namaproduk]</code> untuk menampilkan nama produk<br/>
      <code>[copy data="ISI_DATA"]</code> untuk menampilkan tombol copy isi data</small>
	    </div>
	  </div>
  </div>
</div>
<div class="card mb-3">
  <div class="card-header" onclick="toggleCardBody('waadmin')">
    <i class="fas fa-caret-down"></i> Nomor WhatsApp Admin Finance
  </div>
  <div class="card-body" id="waadmin">
    <div class="form-check form-switch mb-3">
      <input class="form-check-input" type="checkbox" name="enable_wa_admin" value="1" id="chkWaAdmin" <?= !empty($settings['wa_admin']) ? 'checked' : '';?>>
      <label class="form-check-label" for="chkWaAdmin">Aktifkan (Opsional)</label>
    </div>
    <div class="mb-3 row">
      <label class="col-sm-2 col-form-label">Nomor Aktif</label>
      <div class="col-sm-10">
        <div class="form-text"><?= !empty($settings['wa_admin']) ? '+'.htmlspecialchars($settings['wa_admin']) : 'Belum diatur'; ?></div>
      </div>
    </div>
    <div class="mb-3 row">
      <label class="col-sm-2 col-form-label">Nomor WhatsApp Admin</label>
      <div class="col-sm-10">
        <input type="text" class="form-control" name="wa_admin" value="<?= $settings['wa_admin'] ??= '';?>" pattern="^[0-9]{10,15}$" placeholder="Contoh: 6281234567890">
        <small class="form-text text-muted">Masukkan tanpa spasi/simbol. Minimal 10, maksimal 15 digit.</small>
        <div class="mt-2"><button type="submit" name="save_wa_admin" value="1" class="btn btn-primary">Simpan</button></div>
      </div>
    </div>
    <hr/>
    <div class="mb-2"><strong>Log Admin Finance</strong></div>
    <form method="get" class="mb-3">
      <div class="row g-2 align-items-end">
        <div class="col-sm-3">
          <label class="form-label">Action</label>
          <select name="log_action" class="form-select">
            <?php $actSel=''; if (isset($_GET['log_action'])) { $actSel = cek($_GET['log_action']); } ?>
            <option value="">Semua</option>
            <option value="number_changed"<?= ($actSel==='number_changed'?' selected':''); ?>>Perubahan Nomor</option>
            <option value="notify_confirm_new"<?= ($actSel==='notify_confirm_new'?' selected':''); ?>>Notifikasi Konfirmasi Baru</option>
          </select>
        </div>
        <div class="col-sm-3">
          <label class="form-label">Cari (Order/No/IP)</label>
          <input type="text" name="log_q" class="form-control" value="<?= htmlspecialchars($_GET['log_q'] ?? '', ENT_QUOTES); ?>">
        </div>
        <div class="col-sm-2">
          <label class="form-label">Tampilkan</label>
          <select name="log_limit" class="form-select">
            <?php $limSel = (int)($_GET['log_limit'] ?? 50); foreach([20,50,100,200] as $l){ echo '<option value="'.$l.'"'.($limSel===$l?' selected':'').'>'.$l.'</option>'; } ?>
          </select>
        </div>
        <div class="col-12 col-sm-3 d-grid d-sm-flex gap-2 justify-content-sm-end">
          <button type="submit" class="btn btn-secondary">Filter</button>
          <?php $qs = $_GET; $qs['export_finance_log']=1; $exportLink = '?'.http_build_query($qs); ?>
          <a href="<?= htmlspecialchars($exportLink, ENT_QUOTES); ?>" class="btn btn-outline-success">Export CSV</a>
        </div>
      </div>
    </form>
    <?php 
      $lim = (int)($_GET['log_limit'] ?? 50); if ($lim<1) { $lim=50; } if ($lim>500) { $lim=500; }
      $w = [];
      if (!empty($_GET['log_action'])) { $w[] = "`action`='".cek($_GET['log_action'])."'"; }
      if (!empty($_GET['log_q'])) { $q = cek($_GET['log_q']); $w[] = "(`order_id` LIKE '%".$q."%' OR `admin_wa` LIKE '%".$q."%' OR `ip` LIKE '%".$q."%' OR `info` LIKE '%".$q."%')"; }
      $whereLog = count($w)>0 ? ('WHERE '.implode(' AND ',$w)) : '';
      $logs = db_select("SELECT * FROM `epi_admin_finance_log` ".$whereLog." ORDER BY `created_at` DESC, `id` DESC LIMIT ".$lim);
    ?>
    <div class="table-responsive">
      <table class="table table-hover table-bordered">
        <thead class="table-secondary"><tr>
          <th>Waktu</th>
          <th>Action</th>
          <th>No Admin</th>
          <th>Order</th>
          <th>Changed By</th>
          <th>IP</th>
          <th>Info</th>
        </tr></thead>
        <tbody>
          <?php if (is_array($logs) && count($logs)>0) { foreach($logs as $lg){ echo '<tr>'
            .'<td>'.htmlspecialchars((string)($lg['created_at'] ?? ''), ENT_QUOTES).'</td>'
            .'<td>'.htmlspecialchars((string)($lg['action'] ?? ''), ENT_QUOTES).'</td>'
            .'<td>'.htmlspecialchars((string)($lg['admin_wa'] ?? ''), ENT_QUOTES).'</td>'
            .'<td>'.(int)$lg['order_id'].'</td>'
            .'<td>'.(int)$lg['changed_by'].'</td>'
            .'<td>'.htmlspecialchars((string)($lg['ip'] ?? ''), ENT_QUOTES).'</td>'
            .'<td>'.htmlspecialchars((string)($lg['info'] ?? ''), ENT_QUOTES).'</td>'
            .'</tr>'; } } else { echo '<tr><td colspan="7" class="text-center text-muted">Belum ada log</td></tr>'; } ?>
        </tbody>
      </table>
    </div>
    <small class="form-text text-muted">Menampilkan <?= (int)$lim; ?> data terbaru.</small>
  </div>
</div>
<div class="card mb-3">
  <div class="card-header" onclick="toggleCardBody('bankindo')">
    <i class="fas fa-caret-down"></i> Bank Nasional Indonesia
  </div>
  <div class="card-body" id="bankindo">
    <div class="form-check form-switch mb-3">
      <input class="form-check-input" type="checkbox" name="enable_bank" value="1" id="chkBank" <?= !empty($settings['bank_accounts']) ? 'checked' : '';?>>
      <label class="form-check-label" for="chkBank">Aktifkan (Opsional)</label>
    </div>
    <div class="mb-3 row">
      <label class="col-sm-2 col-form-label">Pilih Bank</label>
      <div class="col-sm-10">
        <select name="bank_code" class="form-select">
          <?php $opt = ['bca'=>'Bank Central Asia (BCA)','mandiri'=>'Bank Mandiri','bsi'=>'Bank Syariah Indonesia (BSI)','bri'=>'Bank Rakyat Indonesia (BRI)','bni'=>'Bank Negara Indonesia (BNI)']; foreach($opt as $k=>$v){ echo '<option value="'.$k.'">'.$v.'</option>'; } ?>
        </select>
      </div>
    </div>
    <div class="mb-3 row">
      <label class="col-sm-2 col-form-label">Nomor Rekening</label>
      <div class="col-sm-10">
        <input type="text" name="bank_account" class="form-control" pattern="^[0-9]{8,20}$">
        <small class="form-text text-muted">Hanya angka, 8–20 digit</small>
      </div>
    </div>
    <div class="mb-3 row">
      <label class="col-sm-2 col-form-label">Nama Pemilik</label>
      <div class="col-sm-10">
        <input type="text" name="bank_name" class="form-control" minlength="3">
      </div>
    </div>
    <div class="mb-3">
      <small class="form-text text-muted">Isi jika ingin menambah/mengubah rekening aktif.</small>
    </div>
    <hr/>
    <div class="table-responsive">
      <table class="table table-hover table-bordered">
        <thead class="table-secondary">
          <tr><th>Bank</th><th>Nomor Rekening</th><th>Nama Pemilik</th><th class="text-end">Action</th></tr>
        </thead>
        <tbody>
          <?php $banks = isset($settings['bank_accounts']) ? json_decode($settings['bank_accounts'], true) : []; if (is_array($banks)) { foreach($banks as $b){ echo '<tr><td>'.htmlspecialchars($b['label']).'</td><td>'.htmlspecialchars($b['account']).'</td><td>'.htmlspecialchars($b['owner']).'</td><td class="text-end"><a href="?delbank='.$b['code'].'" class="btn btn-sm btn-outline-danger">Hapus</a></td></tr>'; } } ?>
        </tbody>
      </table>
    </div>
    <small class="form-text text-muted">Perubahan akan tersimpan di database dan file JSON untuk sinkronisasi real-time.</small>
  </div>
</div>
<div class="card">
  <div class="card-header" onclick="toggleCardBody('tripay')">
    <i class="fas fa-caret-down"></i> Integrasi Payment Gateway Tripay
  </div>
  <div class="card-body" id="tripay">
	  <div class="mb-3 row">
	    <label class="col-sm-2 col-form-label">Kode Merchant</label>
	    <div class="col-sm-10">
	      <input type="text" class="form-control" name="tripay_merchant" value="<?= $settings['tripay_merchant'] ??= '';?>">
  </div>
</div>
	  <div class="mb-3 row">
	    <label class="col-sm-2 col-form-label">API Key</label>
	    <div class="col-sm-10">
	      <input type="text" class="form-control" name="tripay_api" value="<?= $settings['tripay_api'] ??= '';?>">
	    </div>
	  </div>
	  <div class="mb-3 row">
	    <label class="col-sm-2 col-form-label">Private Key</label>
	    <div class="col-sm-10">
	      <input type="text" class="form-control" name="tripay_private" value="<?= $settings['tripay_private'] ??= '';?>">
	    </div>
	  </div>
	  <div class="mb-3 row">
	    <label class="col-sm-2 col-form-label">URL Callback</label>
	    <div class="col-sm-10">
	      <input type="text" class="form-control" value="<?= $weburl;?>tripaycall.php" disabled>
	      <div class="form-text">Copy Paste ke <code>Merchant > Detail</code> di dashboard Tripay</div>
	    </div>
	  </div>
	  <div class="mb-3 row">
	    <label class="col-sm-2 col-form-label">Mode Sandbox</label>
	    <div class="col-sm-10">
	    	<div class="form-check">
		      <input type="checkbox" class="form-check-input" name="tripay_sandbox" value="1" 
		      <?php if (isset($settings['tripay_sandbox']) && $settings['tripay_sandbox'] == 1) { echo ' checked'; } ?>>
		    </div>
	    </div>
	  </div>
	  <div class="mb-3 row">
	  	<div class="col">Dapatkan akun <a href="https://tripay.co.id/?ref=TP28329" target="_blank">Tripay di sini</a></div>
	  </div>
	</div>
</div>
<div class="d-grid gap-2">
  <input type="submit" value="Simpan Semua Pengaturan" class="btn btn-success mt-3">
  </div>
</form>
<?php 
$footer['scriptfoot'] = '
<script type="text/javascript" src="'.$weburl.'editor/js/froala_editor.pkgd.min.js"></script>
<script>
  document.addEventListener(\'DOMContentLoaded\', function () {
    new FroalaEditor(\'#editor\', {
      imageUploadURL: \''.$weburl.'upload_image.php\',
      imageUploadParams: {
        id: \'my_editor\'
      },
      codeViewKeepOriginal: true,
      htmlUntouched: true,
      htmlAllowedTags: [\'.*\'], // Allow all HTML tags
      htmlAllowedAttrs: [\'.*\'], // Allow all attributes
      htmlRemoveTags: [],
      events: {
        \'image.beforeUpload\': function (files) {
          var editor = this;

          // Create a FormData object.
          var formData = new FormData();

          // Append the uploaded image to the form data.
          formData.append(\'file\', files[0]);

          // Get the article title and append it to the form data.
          // var judulArtikel = document.getElementById(\'judul\').value;
          formData.append(\'judul\', \'payment\');

          // Make the AJAX request.
          fetch(\''.$weburl.'upload_image.php\', {
            method: \'POST\',
            body: formData
          })
          .then(response => response.json())
          .then(data => {
            if (data.link) {
              // Insert the image into the editor.
              editor.image.insert(data.link, null, null, editor.image.get());
            } else {
              console.error(\'Upload failed:\', data.error);
            }
          })
          .catch(error => {
            console.error(\'Error:\', error);
          });

          // Prevent the default behavior.
          return false;
        }
      }
    });
  });

function toggleCardBody(boxId) {
  const cardBody = document.getElementById(boxId);
  const cardHeader = cardBody.previousElementSibling;

  if (cardBody.style.display === \'none\' || cardBody.style.display === \'\') {
    cardBody.style.display = \'block\';
    cardHeader.classList.remove(\'collapsed\');
  } else {
    cardBody.style.display = \'none\';
    cardHeader.classList.add(\'collapsed\');
  }
}
</script>';
showfooter($footer); ?>
