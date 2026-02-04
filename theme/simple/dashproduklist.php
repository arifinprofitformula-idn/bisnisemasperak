<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
@require_once dirname(__DIR__,2).'/plugin/epi-role-manager/index.php';

/*
Root cause (blank page): file ini sebelumnya memanggil `die()` untuk role < 9.
Saat permission `manageproduk` diaktifkan untuk role non-admin (mis. Admin Staff), router sudah mengizinkan,
tetapi file ini tetap berhenti sebelum render sehingga layar tampak kosong.

Solusi: gunakan permission `manageproduk` untuk role non-admin (>=5), tetap aman untuk role di bawah staff.
*/
$roleCode = (int)($datamember['mem_role'] ?? 1);
$allow = ($roleCode >= 9);
if (!$allow && $roleCode >= 5 && function_exists('epi_role_permissions_for_member')) {
  $perms = epi_role_permissions_for_member($datamember);
  if (is_array($perms) && !empty($perms['manageproduk'])) { $allow = true; }
}

$head['pagetitle']='Manage Produk';
showheader($head);
?>
<div id="manageProdukLoading" class="d-none position-fixed top-0 start-0 w-100 h-100" style="background: rgba(255,255,255,.75); z-index: 2000;">
  <div class="d-flex h-100 w-100 align-items-center justify-content-center">
    <div class="text-center">
      <div class="spinner-border" role="status" aria-hidden="true"></div>
      <div class="mt-2">Memuat...</div>
    </div>
  </div>
</div>
<?php

if (!$allow) {
  echo '<div class="alert alert-danger">Akses ditolak. Role Anda tidak memiliki izin untuk halaman Manage Produk. Silakan minta Administrator mengaktifkan permission <code>manageproduk</code> di halaman Setting Role.</div>';
  showfooter();
  return;
}

if (isset($_GET['nonaktif']) && is_numeric($_GET['nonaktif'])) {
	$cek = db_query("UPDATE `sa_page` SET `pro_status`=0 WHERE `page_id`=".$_GET['nonaktif']);
	$action = 'dinonaktifkan';
} elseif (isset($_GET['aktif']) && is_numeric($_GET['aktif'])) {
	$cek = db_query("UPDATE `sa_page` SET `pro_status`=1 WHERE `page_id`=".$_GET['aktif']);
	$action = 'diaktifkan';
} elseif (isset($_GET['del']) && is_numeric($_GET['del'])) {
	$cek = db_query("DELETE FROM `sa_page` WHERE `page_id`=".$_GET['del']);
	$action = 'dihapus';
}

if (isset($cek)) {
	if ($cek === false) {
		echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
		  <strong>Error!</strong> '.db_error().'
		  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>';
	} else {
		echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
		  <strong>Ok!</strong> Produk telah '.$action.'.
		  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>';
	}
}
?>
<form action="" method="get" data-page-loading="manageproduk">
<div class="card mb-3">
	<div class="card-body">
	  <div class="row">	    
	    <div class="col-sm-10">
	    	<div class="input-group">
				  <input type="text" class="form-control" name="cari" value="<?= $_GET['cari'] ??= '';?>">				  
				  <button type="submit" class="btn btn-secondary">Cari</button>
				</div>	      
	    </div>
	    <div class="col-sm-2 text-end">	    	
	    	<a href="?edit=new" class="btn btn-success">Tambah Produk</a>
	    </div>
	  </div>
	</div>
</div>
</form>

<?php
// ====== FILTER & STATUS PRODUK (FULL-WIDTH CARD) ======
// Ambil pilihan filter dari GET
$selectedProduct = (isset($_GET['produk']) && is_numeric($_GET['produk'])) ? (int)$_GET['produk'] : 0;
$periode = isset($_GET['periode']) ? cek($_GET['periode']) : 'all';

// Build WHERE untuk query order
$whereOrder = "WHERE `order_status` = 1";
if ($selectedProduct > 0) {
    $whereOrder .= " AND `order_idproduk` = " . $selectedProduct;
}

// Filter periode waktu
$startDate = '';
switch ($periode) {
    case '7d':
        $startDate = date('Y-m-d 00:00:00', strtotime('-7 days'));
        break;
    case '30d':
        $startDate = date('Y-m-d 00:00:00', strtotime('-30 days'));
        break;
    case 'month':
        $startDate = date('Y-m-01 00:00:00');
        break;
    case 'year':
        $startDate = date('Y-01-01 00:00:00');
        break;
    default:
        $startDate = '';
        break;
}
if ($startDate !== '') {
    $whereOrder .= " AND `order_tglorder` >= '" . $startDate . "'";
}

// Hitung metrik: Jumlah Pembeli (distinct member), Total Omset (sum harga normal), Total Diskon, dan Gross Profit
$jmlPembeli = (int) db_var("SELECT COUNT(DISTINCT `order_idmember`) FROM `sa_order` " . $whereOrder);
// Omset = total harga normal produk (sebelum diskon), kecuali transaksi gratis (final Rp0)
$totalOmset = db_var("SELECT SUM(CASE WHEN `order_hargaunik`=0 THEN 0 ELSE `order_harga` END) FROM `sa_order` " . $whereOrder);
if (!isset($totalOmset) || !is_numeric($totalOmset)) { $totalOmset = 0; }
// Total Penggunaan Kupon/Diskon = selisih harga normal dengan harga tampil (jika ada diskon), kecuali transaksi gratis
$totalDiskon = db_var("SELECT SUM(CASE WHEN `order_hargaunik`=0 THEN 0 WHEN `order_harga` > `order_hargaunik` THEN (`order_harga` - `order_hargaunik`) ELSE 0 END) FROM `sa_order` " . $whereOrder);
if (!isset($totalDiskon) || !is_numeric($totalDiskon)) { $totalDiskon = 0; }
// Gross Profit = Omset - Total Diskon
$totalGrossProfit = (float)$totalOmset - (float)$totalDiskon;
if ($totalGrossProfit < 0) { $totalGrossProfit = 0; }

// Nama Produk untuk label
$namaProdukLabel = 'Semua Produk';
if ($selectedProduct > 0) {
    $proRow = db_row("SELECT `page_judul` FROM `sa_page` WHERE `page_id`=" . $selectedProduct);
    $namaProdukLabel = isset($proRow['page_judul']) ? $proRow['page_judul'] : 'Produk tidak ditemukan';
}

// Siapkan daftar produk untuk filter
$produkFilterList = db_select("SELECT `page_id`,`page_judul` FROM `sa_page` WHERE `pro_harga` IS NOT NULL ORDER BY `page_judul` ASC");
?>

<div class="card mb-3">
  <div class="card-body text-dark" style="color: var(--bs-emphasis-color);">
    <form action="" method="get" class="row g-2 align-items-end" data-page-loading="manageproduk">
      <div class="col-md-6">
        <label class="form-label">Produk</label>
        <select name="produk" class="form-select">
          <option value="">Semua Produk</option>
          <?php if (isset($produkFilterList) && count($produkFilterList) > 0) { foreach ($produkFilterList as $p) { ?>
            <option value="<?= $p['page_id']; ?>" <?= ($selectedProduct == $p['page_id']) ? 'selected' : ''; ?>><?= $p['page_judul']; ?></option>
          <?php } } ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Periode</label>
        <select name="periode" class="form-select">
          <option value="all" <?= ($periode == 'all') ? 'selected' : ''; ?>>Semua Waktu</option>
          <option value="7d" <?= ($periode == '7d') ? 'selected' : ''; ?>>7 Hari Terakhir</option>
          <option value="30d" <?= ($periode == '30d') ? 'selected' : ''; ?>>30 Hari Terakhir</option>
          <option value="month" <?= ($periode == 'month') ? 'selected' : ''; ?>>Bulan Ini</option>
          <option value="year" <?= ($periode == 'year') ? 'selected' : ''; ?>>Tahun Ini</option>
        </select>
      </div>
      <div class="col-md-2">
        <button type="submit" class="btn btn-primary w-100">Terapkan</button>
      </div>
    </form>
  </div>
  <div class="card-footer text-muted">
    Filter ini membantu membaca status produk yang sudah dibuat. Pilih produk dan periode untuk melihat jumlah pembeli serta total omset.
  </div>
  
</div>

<div class="card mb-3 border-primary shadow-sm">
  <div class="card-header bg-primary text-black">
    <i class="fa-solid fa-chart-line"></i> Status Produk
  </div>
  <div class="card-body text-dark" style="color: var(--bs-emphasis-color);">
    <div class="row">
      <div class="col-12">
        <div class="mb-2"><i class="fa-solid fa-box-open text-primary me-2 fa-fw"></i> <strong>Nama Produk:</strong> <?= htmlspecialchars($namaProdukLabel); ?></div>
        <div class="mb-2"><i class="fa-solid fa-users text-primary me-2 fa-fw"></i> <strong>Jumlah Pembeli:</strong> <?= number_format($jmlPembeli); ?></div>
        <div class="mb-2"><i class="fa-solid fa-coins text-primary me-2 fa-fw"></i> <strong>Total Omset:</strong> Rp <?= number_format((float)$totalOmset, 0, ',', '.'); ?></div>
        <div class="mb-2"><i class="fa-solid fa-tags text-primary me-2 fa-fw"></i> <strong>Total Penggunaan Kupon/ Diskon:</strong> Rp <?= number_format((float)$totalDiskon, 0, ',', '.'); ?></div>
        <div class="mb-2"><i class="fa-solid fa-hand-holding-dollar text-primary me-2 fa-fw"></i> <strong>Total Gross Profit:</strong> Rp <?= number_format((float)$totalGrossProfit, 0, ',', '.'); ?></div>
        <!-- Download CSV Button placed inside Status Produk card -->
        <div class="mt-3">
          <div class="row align-items-center g-2">
            <div class="col-md-8">
              <label class="form-label mb-0">Download Data Pembeli (CSV)</label>
              <div class="text-muted small">Menggunakan filter di atas (Produk & Periode). Kolom wajib: Nama Lengkap, Email, Nomor Whatsapp, Tanggal Pembelian.</div>
            </div>
            <div class="col-md-4 text-md-end">
              <button type="button" id="btnDownloadCsv" class="btn btn-outline-primary w-100" onclick="downloadBuyerCsv()" aria-live="polite">
                <span class="spinner-border spinner-border-sm me-2 d-none" id="spinnerCsv" role="status" aria-hidden="true"></span>
                Download Data Pembeli
              </button>
            </div>
          </div>
          <div id="downloadCsvAlert" class="mt-2 d-none" role="alert" aria-atomic="true"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// Download buyers CSV using current filter
function downloadBuyerCsv() {
  const btn = document.getElementById('btnDownloadCsv');
  const spin = document.getElementById('spinnerCsv');
  const alertBox = document.getElementById('downloadCsvAlert');
  if (!btn || !spin) { return; }
  btn.disabled = true; spin.classList.remove('d-none');
  if (alertBox) { alertBox.className = 'mt-2 d-none'; alertBox.innerHTML = ''; }

  const produkSel = document.querySelector('select[name="produk"]');
  const periodeSel = document.querySelector('select[name="periode"]');
  const produk = produkSel && produkSel.value ? produkSel.value : '';
  const periode = periodeSel && periodeSel.value ? periodeSel.value : 'all';
  // IMPORTANT: use absolute path so it won't be captured by current route and rewritten to index
  const url = '/api/export-buyers.php?produk=' + encodeURIComponent(produk) + '&periode=' + encodeURIComponent(periode) + '&t=' + Date.now();

  fetch(url, { method: 'GET', credentials: 'same-origin' }).then(function(resp){
      if (!resp.ok) throw new Error('Gagal mengekspor (' + resp.status + ')');
      // Ensure we really got CSV from server, not an HTML fallback
      var ctype = (resp.headers.get('Content-Type') || '').toLowerCase();
      if (ctype.indexOf('text/csv') === -1) {
        throw new Error('Respon bukan CSV. Cek sesi login atau izin akses.');
      }
      const dispo = resp.headers.get('Content-Disposition') || '';
      var filename = 'data_pembeli_' + new Date().toISOString().slice(0,10).replace(/-/g,'') + '.csv';
      var m = dispo.match(/filename="?([^";]+)"?/i);
      if (m && m[1]) filename = m[1];
      return resp.blob().then(function(blob){ return { blob: blob, filename: filename }; });
  }).then(function(res){
      var link = document.createElement('a');
      var urlBlob = URL.createObjectURL(res.blob);
      link.href = urlBlob;
      link.download = res.filename;
      document.body.appendChild(link);
      link.click();
      setTimeout(function(){ URL.revokeObjectURL(urlBlob); link.remove(); }, 1000);
      if (alertBox) { alertBox.className = 'mt-2 alert alert-success'; alertBox.innerHTML = 'File CSV berhasil disiapkan: ' + res.filename; }
  }).catch(function(err){
      if (alertBox) { alertBox.className = 'mt-2 alert alert-danger'; alertBox.innerHTML = 'Terjadi kesalahan saat menyiapkan CSV. ' + (err && err.message ? err.message : ''); }
  }).finally(function(){
      btn.disabled = false; spin.classList.add('d-none');
  });
}
</script>

<div class="table-responsive">
<table class="table table-hover table-bordered">
	<thead class="table-secondary">
		<tr>
			<th>Nama Produk</th>
		</tr>
	</thead>
	<tbody class="table-group-divider">
		<?php 
		$produksale = array();
		$salesRows = db_select("SELECT `order_idproduk`,count(*) AS `jmlsale` FROM `sa_order` WHERE `order_status`=1 GROUP BY `order_idproduk`");
		if (is_array($salesRows) && count($salesRows) > 0) {
			foreach ($salesRows as $sr) {
				$produksale[(int)$sr['order_idproduk']] = (int)$sr['jmlsale'];
			}
		}

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

		$listError = '';
		$dataRows = db_select("SELECT * FROM `sa_page` 
			WHERE `pro_harga` IS NOT NULL ".$where."
			ORDER BY `page_judul` ASC
			LIMIT ".$start.",".$jmlperpage);
		if ($dataRows === false) {
			$listError = db_error();
			$dataRows = array();
		} elseif (!is_array($dataRows)) {
			$dataRows = array();
		}
		if ($listError !== '') {
			echo '<tr><td><div class="alert alert-danger mb-0"><strong>Gagal memuat data produk.</strong> '.htmlspecialchars($listError, ENT_QUOTES).'</div></td></tr>';
		}

		if (count($dataRows) > 0) {			
			foreach ($dataRows as $data) {
				echo '
			<tr>
            <td>
            <a href="#" class="info" data-target="konten'.$data['page_id'].'">'.$data['page_judul'].'</a> <span class="badge referral-badge" data-target="konten'.$data['page_id'].'">Klik Disini untuk Info Referral</span>
			<div class="konten'.$data['page_id'].' konten mt-2">';
            if (isset($data['pro_img']) && !empty($data['pro_img'])) {
                // Gambar produk: full width untuk mobile/tablet, 300x300 crop dengan object-fit untuk desktop (>=992px)
                echo '<img src="'.$weburl.'upload/'.$data['pro_img'].'" class="product-card-image" alt="'.htmlspecialchars($data['page_judul']).'"/>';
            }
				echo '
				URL Affiliasi: 
					<a href="'.$weburl.$datamember['mem_kodeaff'].'/'.$data['page_url'].'" target="_blank">
					'.$weburl.$datamember['mem_kodeaff'].'/'.$data['page_url'].'</a>
					&nbsp;&nbsp;<a onclick="copyToClipboard(\''.$weburl.$datamember['mem_kodeaff'].'/'.$data['page_url'].'\')" style="text-decoration:none;cursor: pointer;" 
              title="Copy to Clipboard"><i class="fa-regular fa-copy"></i></a>
					<br/>
                URL Checkout: <a href="'.$weburl.$datamember['mem_kodeaff'].'/order/'.$data['page_url'].'" target="_blank">
                    '.$weburl.$datamember['mem_kodeaff'].'/order/'.$data['page_url'].'</a><br/>
                URL Sales Page: <a href="'.$data['page_iframe'].'" target="_blank">'.$data['page_iframe'].'</a><br/>
                ';

                // Informasi Harga Produk
                $hargaProduk = isset($data['pro_harga']) ? (int)$data['pro_harga'] : 0;
                echo 'Harga Produk: '.number_format($hargaProduk).'<br/>';
                // Harga Promo (harga tampil dari konfigurasi produk)
                $hargaPromo = null;
                if (isset($data['pro_harga_display']) && $data['pro_harga_display'] !== '') {
                    $hargaPromo = (int)$data['pro_harga_display'];
                }
                echo 'Harga Promo: '.($hargaPromo !== null ? number_format($hargaPromo) : '-').'<br/>';
                // Komisi per Penjualan (menyesuaikan jenis komisi: percent atau fixed)
                $komisiPerPenjualan = '-';
                if (isset($data['pro_komisi']) && !empty($data['pro_komisi'])) {
                    $komisiSet = @unserialize($data['pro_komisi']);
                    if (is_array($komisiSet)) {
                        $komisiType = isset($komisiSet['type']) && in_array($komisiSet['type'], array('percent','fixed')) ? $komisiSet['type'] : null;
                        $isPremium = (isset($datamember['mem_status']) && intval($datamember['mem_status']) >= 2);
                        $valLvl1 = $isPremium ? (float)($komisiSet['premium'][1] ?? 0) : (float)($komisiSet['free'][1] ?? 0);
                        if ($valLvl1 > 0) {
                            if ($komisiType === 'percent' || ($komisiType === null && $valLvl1 <= 100)) {
                                $basisHarga = ($hargaPromo !== null ? (int)$hargaPromo : (int)$hargaProduk);
                                $nominal = (int) floor(($basisHarga * max(0.0, min(100.0, $valLvl1))) / 100.0);
                                $komisiPerPenjualan = $valLvl1.'% (Rp '.number_format($nominal).')';
                            } else {
                                $komisiPerPenjualan = 'Rp '.number_format((int)$valLvl1);
                            }
                        }
                    }
                }
                echo '<span class="fw-semibold">Komisi per Penjualan:</span> '.$komisiPerPenjualan.'<br/>';

                // Penjualan: dipindahkan sebelum Kupon Aktif dan label ditebalkan
                $jumlahPenjualan = isset($produksale[$data['page_id']]) ? (int)$produksale[$data['page_id']] : 0;
                echo '<strong>Total Penjualan:</strong> '.number_format($jumlahPenjualan);
                if ($jumlahPenjualan > 30) {
                    echo ' <span class="badge bg-warning text-dark"><i class="fa-solid fa-fire me-1"></i>Produk Laris</span>';
                }
                echo '<br/>';

                // Kupon Aktif (tabel: Kode Kupon | Masa Berlaku, dengan format DD/MM/YYYY HH:mm)
                $couponText = 'Tidak ada';
                if (db_var("SHOW TABLES LIKE 'sa_coupon'")) {
                    $productId = (int)$data['page_id'];
                    $now = date('Y-m-d H:i:s');
                    $sqlWhere = "status=1 AND (scope_all=1 OR (product_ids IS NOT NULL AND FIND_IN_SET(".$productId.", product_ids))) AND (start_at IS NULL OR start_at <= '".$now."') AND (end_at IS NULL OR end_at >= '".$now."')";
                    $rows = db_select("SELECT code, start_at, end_at FROM sa_coupon WHERE ".$sqlWhere." ORDER BY priority DESC, code ASC");
                    if ($rows && count($rows)>0) {
                        $couponText = '';
                        $couponText .= '<div class="table-responsive"><table class="table table-sm coupon-table mb-2"><thead><tr><th style="width:40%">Kode Kupon</th><th style="width:60%">Masa Berlaku</th></tr></thead><tbody>';
                        foreach ($rows as $r) {
                            $code = htmlspecialchars($r['code'] ?? '', ENT_QUOTES);
                            $start = isset($r['start_at']) && !empty($r['start_at']) ? date('d/m/Y H:i', strtotime($r['start_at'])) : null;
                            $end   = isset($r['end_at']) && !empty($r['end_at']) ? date('d/m/Y H:i', strtotime($r['end_at'])) : null;
                            if ($start && $end) {
                                $valid = 'Berlaku: '.$start.' s.d. '.$end;
                            } elseif ($start && !$end) {
                                $valid = 'Mulai: '.$start.' (tanpa batas akhir)';
                            } elseif (!$start && $end) {
                                $valid = 'Sampai: '.$end;
                            } else {
                                $valid = 'Tanpa batas waktu';
                            }
                            $couponText .= '<tr><td><code>'.$code.'</code></td><td><span class="text-muted">'.$valid.'</span></td></tr>';
                        }
                        $couponText .= '</tbody></table></div>';
                    }
                }
                echo 'Kupon Aktif: '.($couponText === 'Tidak ada' ? $couponText : $couponText);

                echo '
                <div class="mt-2">
					<a href="?edit='.$data['page_id'].'" class="btn btn-success mr-3"><i class="fa-solid fa-pen-to-square" title="Edit produk"></i> Edit</a>
					&nbsp;';
			if ($data['pro_status'] == 1) {
				echo '
					<a href="?nonaktif='.$data['page_id'].'" class="btn btn-warning mr-3" title="Nonaktif produk"><i class="fa-regular fa-circle-stop"></i> Nonaktif</a>
					&nbsp;';
			} else {
				echo '
					<a href="?aktif='.$data['page_id'].'" class="btn btn-primary mr-3" title="Aktifkan produk"><i class="fa-regular fa-circle-play"></i> Aktifkan</a>
					&nbsp;';
			}

			echo '
					<a href="#" data-bs-toggle="modal" data-bs-target="#konfirmasi" data-bs-nama="'.$data['page_judul'].'" 
					data-bs-id="'.$data['page_id'].'" class="btn btn-danger mr-3" title="Hapus produk"><i class="fa-solid fa-trash-can"></i> Hapus</a>
				</div>
			</div>
			</td>
			</tr>';
			}  				
		} else {
			echo '<tr><td><div class="text-center py-4">'
				.'<div class="fw-semibold">Belum ada produk</div>'
				.'<div class="text-muted">Klik <strong>Tambah Produk</strong> untuk membuat produk pertama.</div>'
				.'<div class="mt-2"><a href="?edit=new" class="btn btn-success">Tambah Produk</a></div>'
				.'</div></td></tr>';
		}
		?>
	</tbody>
</table>
</div>

<script>
document.addEventListener('submit', function(e){
  var form = e.target;
  if (!form || !form.matches || !form.matches('form[data-page-loading="manageproduk"]')) { return; }
  var overlay = document.getElementById('manageProdukLoading');
  if (overlay) { overlay.classList.remove('d-none'); }
  var btn = form.querySelector('button[type="submit"], input[type="submit"]');
  if (btn) { btn.setAttribute('disabled', 'disabled'); }
}, true);
</script>
<?php

$jmlproduk = db_var("SELECT COUNT(*) FROM `sa_page` 
			WHERE `pro_harga` IS NOT NULL ".$where);
$jmlproduk = is_numeric($jmlproduk) ? (int)$jmlproduk : 0;
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
<!-- Modal -->
<div class="modal fade" id="konfirmasi" tabindex="-1" aria-labelledby="konfirmasilabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="konfirmasilabel">JUDUL</h5>
        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">ISI
      </div>
      <div class="modal-footer">        
        <a href="#" class="btn btn-secondary delbutton">Hapus</a>
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Batal</button>
      </div>
    </div>
  </div>
</div>
<script>
// Styling tambahan dan interaksi untuk daftar produk
(function(){
  // Tambahkan CSS untuk tabel kupon dan panel informasi dengan transition
  var style = document.createElement('style');
  style.textContent = `
    /* Responsive coupon table */
    .coupon-table { table-layout: fixed; width: 100%; }
    .coupon-table th, .coupon-table td { word-wrap: break-word; }
    @media (max-width: 576px) {
      .coupon-table thead { display: none; }
      .coupon-table tr { display: block; border-bottom: 1px solid #eee; }
      .coupon-table td { display: flex; justify-content: space-between; padding: .5rem .75rem; }
      .coupon-table td:first-child::before { content: 'Kode Kupon'; font-weight: 600; margin-right: .75rem; }
      .coupon-table td:last-child::before { content: 'Masa Berlaku'; font-weight: 600; margin-right: .75rem; }
    }

    /* Panel konten dengan transition */
    .konten { max-height: 0; overflow: hidden; opacity: 0; transition: max-height .3s ease, opacity .3s ease; }
    .konten.is-open { max-height: 1200px; opacity: 1; }

    /* Highlight baris aktif */
    tr.produk-aktif > td { background: #FFF7E0; transition: background-color .3s ease; }
    tr.produk-aktif a.info { font-weight: 600; color: #D4AF37; }

    /* Referral badge styling (konsisten, teks hitam, klik aktif) */
    .referral-badge { padding:0; font-size:.8rem; background:#FFF7E0; border:1px solid #E6C76A; color:#0B0B0B; vertical-align:middle; margin-left:.35rem; cursor:pointer; }
    .badge.referral-badge { color:#0B0B0B !important; background-color:#FFF7E0 !important; border-color:#E6C76A !important; }

    /* Gambar produk: responsif, square 1:1, maksimum 300x300px */
    .product-card-image { display:block; width:100%; max-width:300px; aspect-ratio:1/1; height:auto; object-fit:cover; margin:0 auto .5rem; border-radius:6px; }
  `;
  document.head.appendChild(style);

  // Event handler: klik judul produk atau badge referral (toggle)
  document.addEventListener('click', function(e){
    var raw = e.target;
    var trigger = raw && (raw.matches('a.info, .referral-badge') ? raw : raw.closest('.referral-badge'));
    if (!trigger) return;
    if (!(trigger.matches('a.info') || trigger.matches('.referral-badge'))) return;
    e.preventDefault();
    var tname = trigger.getAttribute('data-target');
    if (!tname) return;
    var esc = (typeof CSS !== 'undefined' && typeof CSS.escape === 'function') ? CSS.escape : function(s){ return String(s).replace(/[^a-zA-Z0-9_-]/g, function(ch){ return '\\' + ch; }); };
    var panel = document.querySelector('div.' + esc(tname));
    if (!panel) return;
    var row = trigger.closest('tr');
    if (panel.classList.contains('is-open')) {
      panel.classList.remove('is-open');
      if (row) { row.classList.remove('produk-aktif'); }
      return;
    }
    document.querySelectorAll('.konten.is-open').forEach(function(p){ p.classList.remove('is-open'); });
    document.querySelectorAll('table.table tbody tr.produk-aktif').forEach(function(r){ r.classList.remove('produk-aktif'); });
    panel.classList.add('is-open');
    if (row) { row.classList.add('produk-aktif'); }
  }, true);
})();

  async function copyToClipboard(text) {
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
      
      // Tampilkan notifikasi sukses
      showCopySuccessModal();
      
    } catch (err) {
      console.error('Gagal menyalin teks: ', err);
      // Fallback jika semua metode gagal
      alert('Gagal menyalin teks. Silakan salin manual.');
    }
  }

function showCopySuccessModal() {
    // Buat modal jika belum ada
    if (!document.getElementById('copySuccessModal')) {
        var modalHTML = `
            <div id="copySuccessModal" class="copy-modal-overlay" style="display: none;">
                <div class="copy-modal-content">
                    <div class="copy-modal-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="copy-modal-text">Sukses Tersalin</div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        // Tambahkan CSS untuk modal
        var style = document.createElement('style');
        style.textContent = `
            .copy-modal-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 9999;
                backdrop-filter: blur(3px);
            }
            
            .copy-modal-content {
                background: linear-gradient(135deg, #ffd700, #ffed4e, #fff8dc);
                border: 2px solid #ffd700;
                border-radius: 15px;
                padding: 30px 40px;
                text-align: center;
                box-shadow: 0 8px 25px rgba(255, 215, 0, 0.3), 
                            0 4px 12px rgba(0,0,0,0.15);
                transform: scale(0.8);
                animation: modalAppear 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55) forwards;
                max-width: 350px;
                min-width: 280px;
            }
            
            .copy-modal-icon {
                font-size: 3rem;
                color: #2d5016;
                margin-bottom: 15px;
                text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            }
            
            .copy-modal-text {
                font-size: 1.2rem;
                font-weight: 600;
                color: #1a1a1a;
                text-shadow: 0 1px 2px rgba(255, 255, 255, 0.3);
                letter-spacing: 0.5px;
            }
            
            @keyframes modalAppear {
                0% {
                    transform: scale(0.8);
                    opacity: 0;
                }
                100% {
                    transform: scale(1);
                    opacity: 1;
                }
            }
            
            @keyframes modalDisappear {
                0% {
                    transform: scale(1);
                    opacity: 1;
                }
                100% {
                    transform: scale(0.8);
                    opacity: 0;
                }
            }
            
            .copy-modal-content.disappearing {
                animation: modalDisappear 0.3s ease-in forwards;
            }
        `;
        document.head.appendChild(style);
    }
    
    // Tampilkan modal
    var modal = document.getElementById('copySuccessModal');
    var content = modal.querySelector('.copy-modal-content');
    
    modal.style.display = 'flex';
    content.classList.remove('disappearing');
    
    // Sembunyikan modal setelah 2 detik
    setTimeout(function() {
        content.classList.add('disappearing');
        setTimeout(function() {
            modal.style.display = 'none';
        }, 300);
    }, 2000);
}
</script>
<?php showfooter(); ?>
