<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
$head['pagetitle']='Laporan Perolehan Komisi';
showheader($head);

// CSRF init untuk form permintaan pencairan
if (session_status() === PHP_SESSION_NONE) { @session_start(); }
if (!isset($_SESSION['csrf_token'])) {
  try { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); } catch (Exception $e) { $_SESSION['csrf_token'] = md5(uniqid('', true)); }
}

// Filter sumber komisi: penjualan (2) atau kontributor (3) atau semua
$source = isset($_GET['source']) ? strtolower(trim($_GET['source'])) : 'all';
$allowed = array('all','penjualan','kontributor');
if (!in_array($source, $allowed)) { $source = 'all'; }
$codes = ($source==='penjualan') ? '2' : (($source==='kontributor') ? '3' : '2,3');

// Hitung saldo tersedia per tipe dan total
// REVISI LOGIKA: 
// 1. Saldo Ledger (sa_laporan) = (Total Masuk - Total Keluar). Keluar di sini artinya SUDAH PAID.
// 2. Reserved (epi_commission_payout) = Status 'requested', 'pending', 'processed'. JANGAN hitung 'paid' lagi agar tidak double deduct.
$saldoRowS = db_row("SELECT COALESCE(SUM(`lap_masuk`)-SUM(`lap_keluar`),0) AS `komisi` FROM `sa_laporan` WHERE `lap_idsponsor`=".$iduser." AND `lap_code` IN (2)");
$saldoRowC = db_row("SELECT COALESCE(SUM(`lap_masuk`)-SUM(`lap_keluar`),0) AS `komisi` FROM `sa_laporan` WHERE `lap_idsponsor`=".$iduser." AND `lap_code` IN (3)");
$komisiS = isset($saldoRowS['komisi']) ? (int)$saldoRowS['komisi'] : 0;
$komisiC = isset($saldoRowC['komisi']) ? (int)$saldoRowC['komisi'] : 0;

// Hitung yang sedang dalam proses pencairan (HOLD)
$reservedS = (int)db_var("SELECT COALESCE(SUM(`amount`),0) FROM `epi_commission_payout` WHERE `receiver_id`=".$iduser." AND `type`='sponsor' AND `status` IN ('requested','pending','processed')");
$reservedC = (int)db_var("SELECT COALESCE(SUM(`amount`),0) FROM `epi_commission_payout` WHERE `receiver_id`=".$iduser." AND `type`='contrib' AND `status` IN ('requested','pending','processed')");

// Saldo Real = Ledger - Reserved
$saldoAvailSponsor = max(0, $komisiS - $reservedS);
$saldoAvailContrib = max(0, $komisiC - $reservedC);
$saldoAvailableTotal = $saldoAvailSponsor + $saldoAvailContrib;

// Total diperoleh per tipe (hanya dari komisi penjualan berbasis order)
$sumS = db_row("SELECT COALESCE(SUM(`lap_masuk`),0) AS `masuk`, COALESCE(SUM(`lap_keluar`),0) AS `keluar` FROM `sa_laporan` WHERE `lap_idsponsor`=".$iduser." AND `lap_code`=2");
$sumC = db_row("SELECT COALESCE(SUM(`lap_masuk`),0) AS `masuk`, COALESCE(SUM(`lap_keluar`),0) AS `keluar` FROM `sa_laporan` WHERE `lap_idsponsor`=".$iduser." AND `lap_code`=3");
$totalSponsorMasuk = isset($sumS['masuk']) ? (int)$sumS['masuk'] : 0;
$totalContribMasuk = isset($sumC['masuk']) ? (int)$sumC['masuk'] : 0;
$totalSponsorKeluar = isset($sumS['keluar']) ? (int)$sumS['keluar'] : 0;
$totalContribKeluar = isset($sumC['keluar']) ? (int)$sumC['keluar'] : 0;
// Default tipe sesuai filter
$reqType = ($source==='penjualan') ? 'sponsor' : (($source==='kontributor') ? 'contrib' : 'sponsor');

// Proses form permintaan pencairan
if (isset($_POST['withdraw_request'])) {
  $csrfOk = (isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token']));
  if (!$csrfOk) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert"><strong>Error!</strong> CSRF token tidak valid.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
  } else {
    $colStat = db_row("SHOW COLUMNS FROM `epi_commission_payout` LIKE 'status'");
    if (is_array($colStat) && isset($colStat['Type'])) {
      $t = strtolower($colStat['Type']);
      if (strpos($t, "enum(") !== false && strpos($t, "'requested'") === false) {
        db_query("ALTER TABLE `epi_commission_payout` MODIFY `status` ENUM('requested','pending','processed','paid') NOT NULL DEFAULT 'pending'");
      } elseif (preg_match('/^varchar\((\d+)\)/', $t, $m)) {
        if ((int)$m[1] < 9) { db_query("ALTER TABLE `epi_commission_payout` MODIFY `status` VARCHAR(16) NOT NULL DEFAULT 'pending'"); }
      }
    }
    $colLap = db_row("SHOW COLUMNS FROM `epi_commission_payout` LIKE 'lap_id'");
    if (is_array($colLap) && isset($colLap['Null']) && strtoupper($colLap['Null'])==='NO') { db_query("ALTER TABLE `epi_commission_payout` MODIFY `lap_id` INT NULL"); }
    $colOrd = db_row("SHOW COLUMNS FROM `epi_commission_payout` LIKE 'order_id'");
    if (is_array($colOrd) && isset($colOrd['Null']) && strtoupper($colOrd['Null'])==='NO') { db_query("ALTER TABLE `epi_commission_payout` MODIFY `order_id` INT NULL"); }
    $type   = isset($_POST['req_type']) ? strtolower(trim($_POST['req_type'])) : $reqType;
    if (!in_array($type, array('sponsor','contrib'))) { $type = $reqType; }
    $confirmAuto = isset($_POST['confirm_auto']) && $_POST['confirm_auto']=='1';
    if (!$confirmAuto) {
      echo '<div class="alert alert-danger alert-dismissible fade show" role="alert"><strong>Error!</strong> Verifikasi diperlukan sebelum withdraw otomatis.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    } else {
      $kodePerTipe = ($type==='contrib') ? '3' : '2';
      $saldoRowT = db_row("SELECT COALESCE(SUM(`lap_masuk`)-SUM(`lap_keluar`),0) AS `komisi` FROM `sa_laporan` WHERE `lap_idsponsor`=".$iduser." AND `lap_code` IN (".$kodePerTipe.")");
      $saldoKomisiT = isset($saldoRowT['komisi']) ? (int)$saldoRowT['komisi'] : 0;
      // FIX: Jangan include 'paid' di sini karena sudah terhitung di lap_keluar
      $reservedT = (int)db_var("SELECT COALESCE(SUM(`amount`),0) FROM `epi_commission_payout` WHERE `receiver_id`=".$iduser." AND `type`='".cek($type)."' AND `status` IN ('requested','pending','processed')");
      $saldoAvailT = max(0, $saldoKomisiT - $reservedT);
      $amount = (int)$saldoAvailT;
      $minWithdraw = (int)($settings['min_withdraw'] ?? 25000);
      if ($amount < $minWithdraw) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert"><strong>Error!</strong> Saldo belum mencapai minimal pencairan: Rp '.number_format($minWithdraw).'<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        
      } else {
        $now = date('Y-m-d H:i:s');
        $colStat2 = db_row("SHOW COLUMNS FROM `epi_commission_payout` LIKE 'status'");
        $statusIns = 'requested';
        if (!is_array($colStat2) || !isset($colStat2['Type'])) { $statusIns = 'pending'; }
        else {
          $tt = strtolower($colStat2['Type']);
          if (strpos($tt, "enum(") !== false && strpos($tt, "'requested'") === false) { $statusIns = 'pending'; }
          elseif (preg_match('/^(?:tinyint|int|smallint|mediumint|bigint)/', $tt)) { $statusIns = 'pending'; }
        }
        $ok = db_query("INSERT INTO `epi_commission_payout` (`lap_id`,`order_id`,`receiver_id`,`amount`,`status`,`type`,`created_at`) VALUES (NULL,NULL,".(int)$iduser.",".(int)$amount.",'".$statusIns."','".cek($type)."','".$now."')");
        if ($ok === false) {
          echo '<div class="alert alert-danger alert-dismissible fade show" role="alert"><strong>Error!</strong> '.db_error().'<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        } else {
          $datalain = array('amount'=>number_format($amount),'type'=>$type);
          sa_notif('komisi_requested',$iduser,$datalain);
          $adminWa = isset($settings['wa_admin']) ? formatwa($settings['wa_admin']) : (isset($settings['whatsapp']) ? formatwa($settings['whatsapp']) : '');
          db_query("INSERT INTO `epi_admin_finance_log` (`action`,`admin_wa`,`changed_by`,`info`,`ip`) VALUES ('komisi_requested_auto','".cek($adminWa)."',".(int)$iduser.",'type=".cek($type).",amount=".$amount."','".cek(realIP())."')");
          echo '<div class="alert alert-success alert-dismissible fade show" role="alert"><strong>Ok!</strong> Permintaan pencairan berhasil diajukan.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
          echo '<script>setTimeout(function(){ window.location.replace(window.location.pathname+window.location.search); }, 800);</script>';
          // Refresh saldo
          $saldoRow = db_row("SELECT COALESCE(SUM(`lap_masuk`)-SUM(`lap_keluar`),0) AS `komisi` FROM `sa_laporan` WHERE `lap_idsponsor`=".$iduser." AND `lap_code` IN (".$codes.")");
          $saldoKomisi = isset($saldoRow['komisi']) ? (int)$saldoRow['komisi'] : 0;
          // FIX: Jangan include 'paid' di sini juga
          $sumRequested = (int)db_var("SELECT COALESCE(SUM(`amount`),0) FROM `epi_commission_payout` WHERE `receiver_id`=".$iduser." ".(($source!=='all')?" AND `type`='".cek($reqType)."'":"")." AND `status` IN ('requested','pending','processed')");
          $saldoTersedia = max(0, $saldoKomisi - $sumRequested);
        }
      }
    }
  }
}

if (isset($_GET['detil'])) {
	
	$exp = explode('-',$_GET['detil']);
	if (is_numeric($exp[0]) && is_numeric($exp[1])) {
		$tgl = $exp[0].'-'.$exp[1];
    $select = "SELECT * FROM `sa_laporan`
		LEFT JOIN `sa_member` ON `sa_member`.`mem_id` = `sa_laporan`.`lap_idmember` 
		WHERE `lap_idsponsor`=".$iduser.
		"\n\t\tAND `lap_code` IN (".$codes.")\n\t\tAND MONTH(`lap_tanggal`) = ".$exp[1]." AND YEAR(`lap_tanggal`) = ".$exp[0]."\n\t\tORDER BY `lap_tanggal`";
		$data = db_select($select);
		echo '
		<h4>Laporan '.date('F Y',strtotime($tgl.'-10 10:00:00')).'</h4>
		<div class="table-responsive">
		<table class="table table-hover table-bordered">
			<thead class="table-secondary">
			<tr>
				<th>Tanggal</th>
				<th>Transaksi</th>
				<th>Member</th>
				<th class="text-end">Pemasukan</th>
				<th class="text-end">Pengeluaran</th>
			</tr>
			</thead>
			<tbody>';
		foreach ($data as $data) {
			echo '
			<tr>
				<td>'.date('d-m H:i', strtotime($data['lap_tanggal'])).'</td>
				<td>'.$data['lap_keterangan'].'</td>
				<td><a href="'.$weburl.'dashboard/kliendetil?id='.$data['mem_id'].'" target="_blank">'.$data['mem_nama'].'</a></td>
				<td class="text-end">'.number_format($data['lap_masuk']).'</td>
				<td class="text-end">'.number_format($data['lap_keluar']).'</td>
			</tr>';			
		}
		echo '
			</tbody>
		</table>
		</div>
		';
	}
	
} else {
    echo '
    <style>
      .epi-card{border-radius:8px; padding:16px; box-shadow:0 2px 4px rgba(0,0,0,0.1); background:#fff;}
      .epi-label{display:flex; align-items:center; gap:8px; font-weight:600; margin-bottom:8px;}
      .epi-value{font-weight:700; font-size:clamp(1.5rem, 5vw, 2.5rem);} 
      .btn-withdraw{background:#4CAF50; color:#fff; border:none; border-radius:4px; padding:8px 16px; display:inline-block; transition:transform .15s ease;}
      .btn-withdraw:hover{transform:scale(1.02); filter:brightness(0.98);}      
      .btn-withdraw i{ margin-right:8px; color:#fff; }
      .btn-withdraw.disabled, .btn-withdraw[disabled]{ background:#808080 !important; cursor:not-allowed; }
      .epi-terms{ background:#F5F5F5; border-radius:8px; padding:16px; }
      .epi-terms .terms-title{ font-weight:600; margin-bottom:8px; display:flex; align-items:center; gap:8px; }
      .epi-terms .terms-title{ background:#fff3cd; border:1px solid #ffeeba; border-radius:6px; padding:8px; }
      .epi-terms .terms-title i{ color:#ffca2c; }
      .epi-terms .term{ margin-bottom:12px; }
      .epi-terms .check-icon{ display:inline-flex; align-items:center; justify-content:center; width:20px; height:20px; border-radius:9999px; background:#4CAF50; color:#fff; margin-right:8px; }
      .table thead.table-secondary th{ background:#f3f4f6; color:#0B0B0B; transition:background-color .15s ease; }
      .table thead.table-secondary th:hover{ background:#e6e9ee; }
    </style>
    <div class="row g-3 mb-3">
      <div class="col-md-4">
        <div class="epi-card h-100">
          <div class="epi-label">💰 Saldo Siap Cair</div>
          <div class="epi-value text-success">'.('Rp '.number_format($saldoAvailableTotal)).'</div>
          <div class="small text-muted mb-3">Total saldo yang bisa di-withdraw saat ini</div>
          
          <form action="" method="post" class="mt-auto" id="autoWithdrawForm">
            <div class="mb-2">
              <select name="req_type" class="form-select form-select-sm" aria-label="Pilih tipe komisi">
                <option value="sponsor"'.($reqType==='sponsor'?' selected':'').'>🏆 Penjualan (Referral)</option>
                <option value="contrib"'.($reqType==='contrib'?' selected':'').'>👥 Kontributor</option>
              </select>
            </div>
            <div class="mb-2 text-muted small" aria-live="polite" id="saldoInfo">Saldo tipe terpilih: Rp '.number_format(($reqType==='sponsor'?$saldoAvailSponsor:$saldoAvailContrib)).'</div>
            <input type="hidden" name="csrf_token" value="'.htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES).'" />
            <input type="hidden" name="confirm_auto" value="0" />
            <input type="hidden" name="withdraw_request" value="1" />
            <button type="submit" class="btn-withdraw w-100" id="btnWithdraw"><i class="fas fa-wallet" aria-hidden="true"></i>Withdraw Sekarang</button>
          </form>
        </div>
      </div>
      
      <div class="col-md-4">
        <div class="epi-card h-100">
          <div class="epi-label">🛒 Komisi Penjualan</div>
          
          <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="text-muted small">Total Diperoleh</span>
            <span class="fw-bold">'.number_format($totalSponsorMasuk).'</span>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="text-muted small">Sudah Dicairkan</span>
            <span class="text-danger">- '.number_format($totalSponsorKeluar).'</span>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="text-muted small">Sedang Diproses</span>
            <span class="text-warning">- '.number_format($reservedS).'</span>
          </div>
          
          <hr class="my-2">
          
          <div class="d-flex justify-content-between align-items-center">
            <span class="fw-bold text-dark">Sisa Tersedia</span>
            <span class="fw-bold text-success fs-5">'.number_format($saldoAvailSponsor).'</span>
          </div>
        </div>
      </div>
      
      <div class="col-md-4">
        <div class="epi-card h-100">
          <div class="epi-label">👥 Komisi Kontributor</div>
          
          <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="text-muted small">Total Diperoleh</span>
            <span class="fw-bold">'.number_format($totalContribMasuk).'</span>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="text-muted small">Sudah Dicairkan</span>
            <span class="text-danger">- '.number_format($totalContribKeluar).'</span>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="text-muted small">Sedang Diproses</span>
            <span class="text-warning">- '.number_format($reservedC).'</span>
          </div>
          
          <hr class="my-2">
          
          <div class="d-flex justify-content-between align-items-center">
            <span class="fw-bold text-dark">Sisa Tersedia</span>
            <span class="fw-bold text-success fs-5">'.number_format($saldoAvailContrib).'</span>
          </div>
        </div>
      </div>
    </div>';
    echo '
    <!-- Custom CSS for Modal & Shimmer -->
    <link rel="stylesheet" href="'.$weburl.'bootstrap-5.3.3/css/bootstrap.min.css">
    <script src="'.$weburl.'bootstrap-5.3.3/js/bootstrap.bundle.min.js"></script>
    <style>
      .shimmer-text {
        background: linear-gradient(90deg, #b8860b 0%, #ffd700 50%, #b8860b 100%);
        background-size: 200% auto;
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        animation: shimmer 2.5s linear infinite;
      }
      @keyframes shimmer { 0% { background-position: 0% center; } 100% { background-position: 200% center; } }
      .bg-gradient-gold { background: linear-gradient(135deg, #f0e68c 0%, #ffd700 100%); color: #000; }
      .modal-content { border: none; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
      .modal-header { border-bottom: 0; padding: 1.5rem 1.5rem 0.5rem; }
      .modal-body { padding: 1.5rem; }
      .modal-footer { border-top: 0; padding: 0 1.5rem 1.5rem; }
      .btn-gold { background-color: #ffd700; color: #000000; border: none; font-weight: bold; }
      .btn-gold:hover { background-color: #e6c200; color: #000000; }
      .text-gold-dark { color: #b8860b; }
    </style>

    <!-- Modal Konfirmasi Withdraw -->
    <div class="modal fade" id="wdModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-gradient-gold">
            <h5 class="modal-title fw-bold"><i class="fas fa-wallet me-2"></i>Konfirmasi Penarikan</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="text-center mb-4">
              <p class="text-muted small text-uppercase fw-bold mb-1">Total Penarikan</p>
              <h2 class="fw-bold display-6 shimmer-text mb-0" id="wdAmount">Rp 0</h2>
              <span class="badge bg-light text-dark border mt-2" id="wdType">Tipe Komisi</span>
            </div>
            
            <div class="card bg-light border-0 rounded-3 mb-3">
              <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                  <span class="text-muted small">Metode Bayar</span>
                  <span class="fw-bold text-dark small"><i class="fas fa-building me-1"></i>Transfer Bank</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                  <span class="text-muted small">Biaya Admin</span>
                  <span class="fw-bold text-success small">Gratis</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                  <span class="text-muted small">Pajak (PPh 21 2.5%)</span>
                  <span class="fw-bold text-danger small" id="wdTax">- Rp 0</span>
                </div>
                <hr class="my-2 opacity-25">
                <div class="d-flex justify-content-between align-items-center">
                  <span class="fw-bold text-dark">Estimasi Diterima</span>
                  <span class="fw-bold text-gold-dark fs-5" id="wdNet">Rp 0</span>
                </div>
              </div>
            </div>

            <div class="alert alert-warning border-0 d-flex align-items-start small" role="alert">
              <i class="fas fa-info-circle mt-1 me-2 text-warning"></i>
              <div>
                <strong>Penting:</strong> Pastikan data rekening di profil Anda sudah benar. Transaksi yang diproses tidak dapat dibatalkan.
              </div>
            </div>
          </div>
          <div class="modal-footer bg-white d-flex justify-content-between">
            <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batalkan</button>
            <button type="button" class="btn btn-gold rounded-pill px-4 shadow" id="btnConfirmFinal">
              Ya, Cairkan Dana <i class="fas fa-arrow-right ms-2"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <script>
    (function(){
      var f = document.getElementById("autoWithdrawForm"); if(!f) return;
      var sel = f.querySelector("select[name=req_type]");
      var btn = document.getElementById("btnWithdraw");
      var info = document.getElementById("saldoInfo");
      var availS = '.(int)$saldoAvailSponsor.';
      var availC = '.(int)$saldoAvailContrib.';
      var minWd = '.(int)($settings['min_withdraw'] ?? 25000).';
      
      // Elements for Modal
      var wdModalEl = document.getElementById("wdModal");
      var wdAmount = document.getElementById("wdAmount");
      var wdType = document.getElementById("wdType");
      var wdTax = document.getElementById("wdTax");
      var wdNet = document.getElementById("wdNet");
      var btnConfirmFinal = document.getElementById("btnConfirmFinal");
      var bsModal = null; // Bootstrap modal instance

      // Init Bootstrap Modal safely
      try {
        if(window.bootstrap && window.bootstrap.Modal) {
          bsModal = new bootstrap.Modal(wdModalEl);
        } else {
          console.warn("Bootstrap 5 JS not detected. Modal might not work.");
        }
      } catch(e) { console.error(e); }

      function formatRupiah(num) {
        return "Rp " + new Intl.NumberFormat("id-ID").format(num);
      }

      function update(){
        var t = sel.value; 
        var av = (t==="contrib") ? availC : availS;
        info.textContent = "Saldo tersedia tipe terpilih: " + formatRupiah(av);
        
        var disabled = (av < minWd);
        btn.disabled = disabled;
        btn.classList.toggle("disabled", disabled);
        if(disabled){ 
          btn.setAttribute("title", "Minimal pencairan " + formatRupiah(minWd) + " — saldo belum mencukupi"); 
        } else { 
          btn.removeAttribute("title"); 
        }
      }

      update(); 
      sel.addEventListener("change", update);

      // Handle "Withdraw Sekarang" Click
      f.addEventListener("submit", function(e){
        e.preventDefault(); // Stop default submit
        
        if(!navigator.onLine){ 
          alert("Koneksi terputus. Periksa jaringan Anda, lalu coba lagi."); 
          return; 
        }

        var t = sel.value; 
        var av = (t==="contrib") ? availC : availS;
        
        if(av < minWd){ 
          alert("Saldo belum mencapai minimal pencairan: " + formatRupiah(minWd)); 
          return; 
        }

        // Calculate values
        var tax = Math.floor(av * 0.025);
        var net = av - tax;
        var typeLabel = (t==="contrib" ? "Komisi Kontributor" : "Komisi Penjualan");

        // Populate Modal
        wdAmount.textContent = formatRupiah(av);
        wdType.textContent = typeLabel;
        wdTax.textContent = "- " + formatRupiah(tax);
        wdNet.textContent = formatRupiah(net);

        // Show Modal
        if(bsModal) {
          bsModal.show();
        } else {
          // Fallback if bootstrap JS is missing (unlikely but safe)
          var ok = confirm("Konfirmasi Withdraw:\nJumlah: "+formatRupiah(av)+"\n(Pajak 2.5%: "+formatRupiah(tax)+")\nNet: "+formatRupiah(net)+"\n\nLanjutkan?");
          if(ok) {
            f.querySelector("input[name=confirm_auto]").value = "1";
            f.submit();
          }
        }
      });

      // Handle "Konfirmasi" inside Modal
      btnConfirmFinal.addEventListener("click", function() {
        // Add loading state
        var originalText = btnConfirmFinal.innerHTML;
        btnConfirmFinal.innerHTML = "<i class=\'fas fa-spinner fa-spin me-2\'></i>Memproses...";
        btnConfirmFinal.disabled = true;
        
        f.querySelector("input[name=confirm_auto]").value = "1";
        f.submit();
      });
    })();
    </script>';
    echo '<div class="epi-terms mb-3">'
      .'<div class="terms-title"><i class="fas fa-triangle-exclamation" aria-hidden="true"></i><span>Syarat & Ketentuan Pencairan Saldo</span></div>'
      .'<div class="term"><span class="check-icon"><i class="fas fa-check" aria-hidden="true"></i></span>Anda bisa melakukan withdraw dengan minimal pencairan Rp 25.000</div>'
      .'<div class="term"><span class="check-icon"><i class="fas fa-check" aria-hidden="true"></i></span>Setiap komisi yang dicairkan akan dikenakan potongan pajak penghasilan pasal 21 sebesar 2,5%</div>'
      .'<div class="term"><span class="check-icon"><i class="fas fa-check" aria-hidden="true"></i></span>Pastikan Anda sudah mengupload foto KTP di halaman <a href="/dashboard/profil" class="link-success">profil</a></div>'
      .'<div class="term"><span class="check-icon"><i class="fas fa-check" aria-hidden="true"></i></span>Pastikan Anda sudah mengatur info Rekening di halaman <a href="/dashboard/profil" class="link-success">profil</a></div>'
      .'<div class="term"><span class="check-icon"><i class="fas fa-check" aria-hidden="true"></i></span>Proses pembayaran komisi dilakukan pada Selasa & Jumat setiap minggunya</div>'
      .'</div>';
	$data = db_select("
    SELECT SUM(`lap_masuk`) - SUM(`lap_keluar`) AS `komisi`,
           DATE_FORMAT(`lap_tanggal`, '%Y-%m') AS `bulan`
    FROM `sa_laporan` 
    WHERE `lap_idsponsor` = ".$iduser." AND `lap_code` IN (".$codes.")
    GROUP BY `bulan` ORDER BY `bulan` DESC
    	" );

	if (count($data) > 0) {
		// Inisialisasi array untuk menyimpan bulan dan komisi
		$duit = [];
		$bulan_terdaftar = [];

		foreach ($data as $data_item) {
		    $duit[$data_item['bulan']]['komisi'] = $data_item['komisi'];
		    $bulan_terdaftar[] = $data_item['bulan'];
		}

		// Menentukan bulan paling awal dan bulan paling akhir
		$bulan_terawal = min($bulan_terdaftar);
		$bulan_terakhir = max($bulan_terdaftar);

		// Convert bulan awal dan akhir ke format DateTime
		$start = new DateTime($bulan_terawal . '-01');
		$end = new DateTime($bulan_terakhir . '-01');

		// Buat array untuk menyimpan semua bulan dari terbaru ke terlama
		$bulan_list = [];

		// Loop dari bulan paling akhir ke bulan paling awal
		while ($end >= $start) {
		    $bulan_list[] = $end->format('Y-m'); // Tambahkan bulan ke array
		    $end->modify('-1 month'); // Mundur satu bulan
		}
	}
	
	echo '
    ';

    // Riwayat permintaan pencairan
    $whereType = ($source!=='all') ? (" AND p.`type`='".cek($reqType)."'") : '';
    // Ensure schema supports rejected reason display
    $colRR = db_row("SHOW COLUMNS FROM `epi_commission_payout` LIKE 'reject_reason'");
    if (!is_array($colRR) || !isset($colRR['Field'])) { db_query("ALTER TABLE `epi_commission_payout` ADD `reject_reason` VARCHAR(255) NULL"); }
    $colCR = db_row("SHOW COLUMNS FROM `epi_commission_payout` LIKE 'cancel_reason'");
    if (!is_array($colCR) || !isset($colCR['Field'])) { db_query("ALTER TABLE `epi_commission_payout` ADD `cancel_reason` VARCHAR(255) NULL"); }

    // Query untuk mengambil riwayat pencairan
    $history = db_select("SELECT p.* FROM `epi_commission_payout` p WHERE p.`receiver_id`=".$iduser.$whereType." ORDER BY p.`created_at` DESC LIMIT 20");
    if(count($history) > 0){
      echo '<div class="epi-card mt-4">';
      echo '<div class="epi-label mb-3"><i class="fas fa-history text-muted"></i> Riwayat Pencairan (Terakhir 20)</div>';
      echo '<div class="table-responsive">';
      echo '<table class="table table-hover table-striped align-middle">';
      echo '<thead class="table-light"><tr><th>Tanggal</th><th>Tipe</th><th>Jumlah</th><th>Status</th><th>Keterangan</th></tr></thead>';
      echo '<tbody>';
      foreach($history as $h){
        $st = $h['status'];
        $badge = 'secondary';
        if($st=='requested') $badge='info';
        elseif($st=='pending') $badge='warning';
        elseif($st=='processed') $badge='primary';
        elseif($st=='paid') $badge='success';
        elseif($st=='rejected' || $st=='cancelled') $badge='danger';
        
        $ket = '-';
        if($st=='rejected' && !empty($h['reject_reason'])) $ket = '<span class="text-danger small">'.$h['reject_reason'].'</span>';
        elseif($st=='cancelled' && !empty($h['cancel_reason'])) $ket = '<span class="text-danger small">'.$h['cancel_reason'].'</span>';
        elseif($st=='paid') $ket = '<span class="text-success small"><i class="fas fa-check-circle"></i> Selesai</span>';
        
        echo '<tr>';
        echo '<td>'.date('d M Y H:i', strtotime($h['created_at'])).'</td>';
        echo '<td>'.ucfirst($h['type']).'</td>';
        echo '<td class="fw-bold">Rp '.number_format($h['amount']).'</td>';
        echo '<td><span class="badge bg-'.$badge.'">'.ucfirst($st).'</span></td>';
        echo '<td>'.$ket.'</td>';
        echo '</tr>';
      }
      echo '</tbody></table>';
      echo '</div></div>';
    }

}
?>