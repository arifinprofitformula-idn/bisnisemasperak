<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
$head['pagetitle']='Daftar Produk yg Tersedia';
showheader($head);
?>
<?php
// Handle cancel order action for current member (backend validation)
if (isset($_GET['cancel']) && is_numeric($_GET['cancel'])) {
  $oid = (int)$_GET['cancel'];
  $row = db_row("SELECT `order_id`,`order_idmember`,`order_idproduk` FROM `sa_order` WHERE `order_id`=".$oid." AND `order_idmember`=".$datamember['mem_id']." AND `order_status`=0");
  if ($row && isset($row['order_id'])) {
    $det = db_row("SELECT o.`order_id`,o.`order_idmember`,p.`page_judul`,p.`page_url` FROM `sa_order` o LEFT JOIN `sa_page` p ON p.`page_id`=o.`order_idproduk` WHERE o.`order_id`=".$oid);
    $invUrl = rtrim($weburl,'/').'/invoice/'.(int)$oid;
    $prodUrl = isset($det['page_url']) ? (rtrim($weburl,'/').'/order/'.$det['page_url']) : $invUrl;
    $datalain = array(
      'idorder' => (string)$oid,
      'namaproduk' => (string)($det['page_judul'] ?? ''),
      'urlproduk' => (string)$prodUrl,
      'halaman_invoice' => (string)$invUrl,
      'alasan' => ''
    );
    // Hapus order pending milik member, bersihkan data terkait agar tidak muncul sebagai pending lagi
    $ok = db_query("DELETE FROM `sa_order` WHERE `order_id`=".$oid);
    if ($ok === false) {
      echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">'
        .'<strong>Error!</strong> Tidak dapat membatalkan order: '.htmlspecialchars(db_error()).'
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    } else {
      @db_query("DELETE FROM `sa_laporan` WHERE `lap_idorder`=".$oid);
      @db_query("DELETE FROM `epi_payment_confirm` WHERE `order_id`=".$oid);
      @db_query("DELETE FROM `epi_payment_confirm_log` WHERE `order_id`=".$oid);
      sa_notif('cancel_order', (int)$datamember['mem_id'], $datalain);
      echo '<div class="alert alert-success alert-dismissible fade show" role="alert">'
        .'<strong>Ok!</strong> Order #'.(int)$oid.' berhasil dibatalkan.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    }
  } else {
    echo '<div class="alert alert-warning alert-dismissible fade show" role="alert">'
      .'<strong>Perhatian!</strong> Order tidak ditemukan atau sudah diproses.
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
  }
}
?>

<div class="table-responsive">
<table class="table table-hover table-bordered">
	<thead class="table-secondary">
		<tr>
			<th>Nama Produk</th>
			<th class="text-end">Harga</th>
			<th class="text-end">Terjual</th>
			<th>Aksi</th>
		</tr>
	</thead>
	<tbody class="table-group-divider">
		<?php 
		$data = db_select("SELECT * FROM `sa_page` WHERE `pro_harga` IS NOT NULL AND `pro_status`=1");
		// Precompute total penjualan per produk untuk badge 'Produk Laris'
		$produksale = array();
		$saleRows = db_select("SELECT `order_idproduk`, COUNT(*) AS `jmlsale` FROM `sa_order` WHERE `order_status`=1 GROUP BY `order_idproduk`");
		if ($saleRows && count($saleRows) > 0) {
			foreach ($saleRows as $sr) { $produksale[(int)$sr['order_idproduk']] = (int)$sr['jmlsale']; }
		}
        $order = db_select("SELECT * FROM `sa_order` WHERE `order_idmember`=".$datamember['mem_id']." AND `order_status`=1");
        if (count($order) > 0) {
            foreach ($order as $order) {
                $orderlist[$order['order_idproduk']] = 1;
            }
        }
        // Pending orders (status 0) for current member, mapped by product
        $pendingRows = db_select("SELECT `order_id`,`order_idproduk` FROM `sa_order` WHERE `order_idmember`=".$datamember['mem_id']." AND `order_status`=0 ORDER BY `order_id` DESC");
        $pendingByProduct = array();
        if ($pendingRows && count($pendingRows) > 0) { foreach ($pendingRows as $pr) { $pendingByProduct[(int)$pr['order_idproduk']] = (int)$pr['order_id']; } }
		if (count($data) > 0) {
			foreach ($data as $data) {
				if ($settings['khususpremium'] == 1 && $datamember['mem_status'] < 2) {
					$urlaff = '<em>URL Affiliasi khusus Premium Member</em>';
				} elseif ($data['pro_status'] == 0) {
					$urlaff = '<em>Produk dinonaktifkan</em>';
				} else {
					$urlaff = '<a href="'.$weburl.$datamember['mem_kodeaff'].'/'.$data['page_url'].'" target="_blank">
					'.$weburl.$datamember['mem_kodeaff'].'/'.$data['page_url'].'</a>
					&nbsp;&nbsp;<a onclick="copyToClipboard(\''.$weburl.$datamember['mem_kodeaff'].'/'.$data['page_url'].'\')" 
            style="text-decoration:none;cursor: pointer;" title="Copy to Clipboard">
          <i class="fa-regular fa-copy"></i></a> 
					';
				}
				echo '
			<tr>
			<td>
            <a href="#" class="info" data-target="konten'.$data['page_id'].'">'.$data['page_judul'].'</a> <span class="badge referral-badge" data-target="konten'.$data['page_id'].'" style="color:#0B0B0B !important; background:#FFF7E0; border:1px solid #E6C76A; padding:0;">Klik Disini untuk Info Referral</span>
			<div class="konten'.$data['page_id'].' konten mt-2">
				<p>';
				if (isset($data['pro_img']) && !empty($data['pro_img'])) {
					// Gambar produk: full width untuk mobile/tablet, 150px float-left untuk desktop (>=992px)
					echo '<img src="'.$weburl.'upload/'.$data['pro_img'].'" class="product-card-image" alt="'.htmlspecialchars($data['page_judul']).'"/>';
				}
				echo '
				'.htmlspecialchars($data['page_diskripsi']).'</p>
				<p>URL Affiliasi: '.$urlaff.'</p>

				';

				// Tambahan info di dalam td: Url Order, Harga Produk, Harga Promo, Komisi per Penjualan, Jumlah Penjualan, Kupon Aktif
				$hargaProduk = isset($data['pro_harga']) ? (int)$data['pro_harga'] : 0;
				$hargaPromo = null;
				if (isset($data['pro_harga_display']) && $data['pro_harga_display'] !== '' && is_numeric($data['pro_harga_display'])) {
					$hargaPromo = (int)$data['pro_harga_display'];
				}
                // Komisi per Penjualan (percent atau fixed)
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

				$jumlahPenjualan = isset($produksale[$data['page_id']]) ? (int)$produksale[$data['page_id']] : 0;

				echo 'URL Checkout: <a href="'.$weburl.$datamember['mem_kodeaff'].'/order/'.$data['page_url'].'" target="_blank">'.$weburl.$datamember['mem_kodeaff'].'/order/'.$data['page_url'].'</a>&nbsp;&nbsp;<a onclick="copyToClipboard(\''.$weburl.$datamember['mem_kodeaff'].'/order/'.$data['page_url'].'\')" style="text-decoration:none;cursor: pointer;" title="Copy to Clipboard"><i class="fa-regular fa-copy"></i></a><br/>';
				echo 'Harga Produk: '.number_format($hargaProduk).'<br/>';
				echo 'Harga Promo: '.($hargaPromo !== null ? number_format($hargaPromo) : '-').'<br/>';
                echo '<span class="fw-semibold">Komisi per Penjualan:</span> '.$komisiPerPenjualan.'<br/>';
				echo '<strong>Total Penjualan:</strong> '.number_format($jumlahPenjualan);
				if ($jumlahPenjualan > 30) { echo ' <span class="badge bg-warning text-dark"><i class="fa-solid fa-fire me-1"></i>Produk Laris</span>'; }
				echo '<br/>';

				// Kupon Aktif (tabel: Kode Kupon | Masa Berlaku, format DD/MM/YYYY HH:mm)
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

            echo '</td>
            <td class="text-end">';
            $hargaNormal = (isset($data['pro_harga']) && is_numeric($data['pro_harga'])) ? $data['pro_harga'] : 0;
            $hargaTampilRaw = (isset($data['pro_harga_display']) && $data['pro_harga_display'] !== '' && is_numeric($data['pro_harga_display'])) ? $data['pro_harga_display'] : null;
            $hargaTampil = ($hargaTampilRaw !== null) ? $hargaTampilRaw : $hargaNormal;

            // Aturan tampilan harga:
            // - Jika hanya harga normal (tidak ada harga tampil), tampilkan satu harga normal tanpa coret.
            // - Harga coret hanya ditampilkan jika harga tampil ada dan nilainya berbeda dari harga normal.
            if ($hargaTampilRaw !== null && $hargaTampil != $hargaNormal) {
              // Tampilkan harga normal dicoret dan harga tampil menonjol (warna hijau sesuai permintaan)
              echo '<div><span class="text-muted" style="text-decoration: line-through;">'.number_format($hargaNormal).'</span></div>';
              echo '<div><span class="fw-bold text-success" style="font-size:1.15em;">'.number_format($hargaTampil).'</span></div>';
            } else {
              // Tampilkan satu harga normal saja (tanpa coret)
              echo '<div><span class="fw-bold text-dark" style="font-size:1.05em;">'.number_format($hargaNormal).'</span></div>';
            }
            echo '</td>';

            // Kolom Terjual: total penjualan + ikon api sesuai threshold
            $jumlahPenjualanCol = isset($produksale[$data['page_id']]) ? (int)$produksale[$data['page_id']] : 0;
            $iconCount = 1;
            if ($jumlahPenjualanCol > 50) { $iconCount = 5; }
            elseif ($jumlahPenjualanCol >= 30) { $iconCount = 3; }
            echo '<td class="text-end align-middle"><div class="sales-column"><span class="sales-count fw-bold">'.number_format($jumlahPenjualanCol).'</span><span class="sales-icons" aria-label="Tingkat laris">';
            for ($i = 0; $i < $iconCount; $i++) { echo '<i class="fa-solid fa-fire text-warning"></i>'; }
            echo '</span></div></td>';

            echo '<td class="text-end">';
            if (isset($orderlist[$data['page_id']]) || ((isset($data['pro_free_access']) ? $data['pro_free_access'] : 0) == 1)) {
                echo '<a href="'.$weburl.'dashboard/akses/'.$data['page_url'].'" class="btn btn-sm member-order-btn" target="_blank"><span class="btn-icon" aria-hidden="true">🔑</span><span class="btn-text">Akses</span></a>'; 
            } elseif ($data['pro_status'] == 1) {
                $pid = (int)$data['page_id'];
                $pendingId = isset($pendingByProduct[$pid]) ? (int)$pendingByProduct[$pid] : 0;
                if ($pendingId > 0) {
                    echo '<div class="d-inline-flex flex-wrap gap-1 justify-content-end">'
                        .'<a href="'.$weburl.'invoice/'.$pendingId.'" class="btn btn-sm member-order-btn member-order-complete" data-complete-order="'.$pendingId.'" target="_blank"><span class="btn-icon" aria-hidden="true">✅</span><span class="btn-text">Lanjut Order</span></a>'
                        .'<a href="#" class="btn btn-sm member-order-btn member-order-cancel" data-cancel-order="'.$pendingId.'"><span class="btn-icon" aria-hidden="true">✖</span><span class="btn-text">Batal Order</span></a>'
                        .'</div>';
                } else {
                    echo '<a href="'.$weburl.'order/'.$data['page_url'].'" class="btn btn-sm member-order-btn" target="_blank"><span class="btn-icon" aria-hidden="true">🛒</span><span class="btn-text">Order</span></a>';
                }
            }
            echo '
            </td>
            </tr>';
			}  				
		}
		?>
	</tbody>
</table>
</div>
<div class="mt-2">
  <div class="alert alert-light border" role="note" aria-label="Keterangan ikon api">
    <div class="d-flex align-items-center mb-1 small legend-item">
      <span class="me-2" aria-hidden="true"><i class="fa-solid fa-fire text-warning"></i></span>
      <span>1 icon api: <em>Produk baru dan mulai ada penjualan</em></span>
    </div>
    <div class="d-flex align-items-center mb-1 small legend-item">
      <span class="me-2" aria-hidden="true">
        <i class="fa-solid fa-fire text-warning"></i>
        <i class="fa-solid fa-fire text-warning"></i>
        <i class="fa-solid fa-fire text-warning"></i>
      </span>
      <span>3 icon api: <em>Produk cukup diminati dan potensi laris dipasarkan</em></span>
    </div>
    <div class="d-flex align-items-center small legend-item">
      <span class="me-2" aria-hidden="true">
        <i class="fa-solid fa-fire text-warning"></i>
        <i class="fa-solid fa-fire text-warning"></i>
        <i class="fa-solid fa-fire text-warning"></i>
        <i class="fa-solid fa-fire text-warning"></i>
        <i class="fa-solid fa-fire text-warning"></i>
      </span>
      <span>5 icon api: <em>Produk sangat diminati dan berpotensi tinggi laris dipasarkan. Rekomendasi untuk Anda ikut jualan produknya</em></span>
    </div>
  </div>
  <!-- End legend -->
</div>
<script>
// Styling tambahan dan interaksi untuk daftar produk (member)
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

    /* Referral badge (padding 0, smaller than product label) */
    .referral-badge { padding:0; font-size:.8rem; background:#FFF7E0; border:1px solid #E6C76A; color:#0B0B0B; vertical-align:middle; margin-left:.35rem; cursor:pointer; }
    .badge.referral-badge { color:#0B0B0B !important; background-color:#FFF7E0 !important; border-color:#E6C76A !important; }

    /* Order button green with icon (match /product info styling) */
    .member-order-btn { background-image: linear-gradient(to bottom, rgba(255,255,255,.22), rgba(255,255,255,0) 45%), linear-gradient(to bottom, #49a749 0%, #2f7d2f 100%); color:#fff; border:0; box-shadow: 0 2px 0 rgba(0,0,0,.15), 0 8px 16px rgba(0,0,0,.08); }
    .member-order-btn:hover { filter: brightness(1.02); }
    .member-order-btn .btn-icon, .member-order-btn .btn-text { color:#fff !important; }
    .btn-sm.member-order-btn { padding:.35rem .6rem; }

    /* Complete and Cancel variants (simple, follow member-order-btn rules) */
    .member-order-btn.member-order-complete { background-image: linear-gradient(to bottom, rgba(255,255,255,.22), rgba(255,255,255,0) 45%), linear-gradient(to bottom, #49a749 0%, #2f7d2f 100%); }
    .member-order-btn.member-order-cancel { background-image: linear-gradient(to bottom, rgba(255,255,255,.18), rgba(255,255,255,0) 45%), linear-gradient(to bottom, #dd6b20 0%, #c53030 100%); }

    /* Gambar produk: fit ke lebar kolom, square 1:1, maksimum 300x300px */
    .product-card-image { display:block; width:100%; max-width:300px; aspect-ratio: 1/1; height:auto; object-fit:cover; margin:0 auto .5rem; border-radius:6px; }
    .konten p, .konten a { overflow-wrap:anywhere; word-break:break-word; }

    /* Kolom Terjual: angka + ikon api responsif (rata kanan) */
    .sales-column { display:inline-flex; align-items:center; gap:.35rem; flex-wrap:wrap; justify-content:flex-end; }
    .sales-count { font-size:1rem; }
    .sales-icons i { font-size:1rem; line-height:1; }
    @media (max-width: 576px) {
      .sales-count { font-size:.95rem; }
      .sales-icons i { font-size:.95rem; }
    }

    /* Legend ikon api: pada mobile/tablet, teks di bawah ikon agar ikon tampil penuh sejajar */
    .legend-item { gap:.5rem; }
    @media (max-width: 992px) {
      .legend-item { flex-direction: column; align-items: flex-start; }
      .legend-item .me-2 { display: inline-block; white-space: nowrap; }
    }
  `;
  document.head.appendChild(style);

  // Event handler: klik judul produk atau badge referral (toggle dengan animasi halus)
  document.addEventListener('click', function(e){
    var raw = e.target;
    var trigger = raw && (raw.matches('a.info, .referral-badge') ? raw : raw.closest('.referral-badge'));
    if (!trigger) return;
    if (!(trigger.matches('a.info') || trigger.matches('.referral-badge'))) return;
    e.preventDefault();
    e.stopPropagation();
    var tname = trigger.getAttribute('data-target');
    if (!tname) return;
    var esc = (typeof CSS !== 'undefined' && typeof CSS.escape === 'function') ? CSS.escape : function(s){ return String(s).replace(/[^a-zA-Z0-9_-]/g, function(ch){ return '\\' + ch; }); };
    var panel = document.querySelector('div.' + esc(tname));
    if (!panel) return;
    var row = trigger.closest('tr');

    var currentlyOpen = panel.classList.contains('is-open') && panel.style.display !== 'none';

    function closePanel(p){
      p.classList.remove('is-open');
      var r = p.closest('tr');
      if (r) r.classList.remove('produk-aktif');
      p.addEventListener('transitionend', function te(ev){
        if (ev.propertyName === 'opacity') { p.style.display = 'none'; p.removeEventListener('transitionend', te); }
      });
    }

    if (currentlyOpen) {
      trigger.setAttribute('aria-expanded', 'false');
      closePanel(panel);
      if (row) row.classList.remove('produk-aktif');
      return;
    }

    document.querySelectorAll('.konten.is-open').forEach(function(p){
      closePanel(p);
    });
    document.querySelectorAll('table.table tbody tr.produk-aktif').forEach(function(r){ r.classList.remove('produk-aktif'); });

    panel.style.removeProperty('display');
    // Force reflow then add class for transition
    void panel.offsetWidth;
    panel.classList.add('is-open');
    trigger.setAttribute('aria-expanded', 'true');
    if (row) row.classList.add('produk-aktif');
  }, false);
})();
</script>
<!-- Modal konfirmasi batal order -->
<div class="modal fade" id="cancelOrderModal" tabindex="-1" aria-labelledby="cancelOrderLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="cancelOrderLabel">Batalkan Order</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">Apakah Anda yakin ingin membatalkan order ini? Tindakan ini tidak dapat diulang.</div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
        <button type="button" class="btn btn-danger" data-confirm-cancel>Ya, Batalkan</button>
      </div>
    </div>
  </div>
  </div>
<script>
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
          animation: modalAppear 0.3s ease-out forwards;
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
<script>
// Interaksi tombol Selesaikan dan Batal Order dengan state & loading
document.addEventListener('click', function(e){
  var complete = e.target.closest('[data-complete-order]');
  if (complete) {
    // Loading visual sebelum navigasi
    complete.classList.add('disabled');
    complete.style.opacity = '0.85';
    complete.querySelector('.btn-text')?.insertAdjacentText('beforebegin','');
    // Navigasi tetap melalui href default
    return;
  }
  var cancelBtn = e.target.closest('[data-cancel-order]');
  if (cancelBtn) {
    e.preventDefault();
    var oid = cancelBtn.getAttribute('data-cancel-order');
    var modalEl = document.getElementById('cancelOrderModal');
    if (!modalEl) return;
    modalEl.setAttribute('data-oid', oid);
    try { var m = new bootstrap.Modal(modalEl); m.show(); } catch(err) { if (confirm('Batalkan order #'+oid+'?')) { window.location.href='?cancel='+encodeURIComponent(oid); } }
  }
});

document.querySelector('[data-confirm-cancel]')?.addEventListener('click', function(){
  var btn = this; var modalEl = btn.closest('#cancelOrderModal'); if (!modalEl) return;
  var oid = modalEl.getAttribute('data-oid'); if (!oid) return;
  btn.disabled = true; btn.textContent = 'Memproses...';
  window.location.href='?cancel='+encodeURIComponent(oid);
});
</script>
<?php showfooter(); ?>
