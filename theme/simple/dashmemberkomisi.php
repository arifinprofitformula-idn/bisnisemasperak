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
$saldoRowS = db_row("SELECT COALESCE(SUM(`lap_masuk`)-SUM(`lap_keluar`),0) AS `komisi` FROM `sa_laporan` WHERE `lap_idsponsor`=".$iduser." AND `lap_code` IN (2)");
$saldoRowC = db_row("SELECT COALESCE(SUM(`lap_masuk`)-SUM(`lap_keluar`),0) AS `komisi` FROM `sa_laporan` WHERE `lap_idsponsor`=".$iduser." AND `lap_code` IN (3)");
$komisiS = isset($saldoRowS['komisi']) ? (int)$saldoRowS['komisi'] : 0;
$komisiC = isset($saldoRowC['komisi']) ? (int)$saldoRowC['komisi'] : 0;
$reservedS = (int)db_var("SELECT COALESCE(SUM(`amount`),0) FROM `epi_commission_payout` WHERE `receiver_id`=".$iduser." AND `type`='sponsor' AND `status` IN ('requested','processed','paid')");
$reservedC = (int)db_var("SELECT COALESCE(SUM(`amount`),0) FROM `epi_commission_payout` WHERE `receiver_id`=".$iduser." AND `type`='contrib' AND `status` IN ('requested','processed','paid')");
$saldoAvailSponsor = max(0, $komisiS - $reservedS);
$saldoAvailContrib = max(0, $komisiC - $reservedC);
$saldoAvailableTotal = $saldoAvailSponsor + $saldoAvailContrib;
// Total diperoleh per tipe (hanya dari komisi penjualan berbasis order)
$sumS = db_row("SELECT COALESCE(SUM(`lap_masuk`),0) AS `masuk`, COALESCE(SUM(`lap_keluar`),0) AS `keluar` FROM `sa_laporan` WHERE `lap_idsponsor`=".$iduser." AND `lap_code`=2 AND COALESCE(`lap_idorder`,0) > 0");
$sumC = db_row("SELECT COALESCE(SUM(`lap_masuk`),0) AS `masuk`, COALESCE(SUM(`lap_keluar`),0) AS `keluar` FROM `sa_laporan` WHERE `lap_idsponsor`=".$iduser." AND `lap_code`=3 AND COALESCE(`lap_idorder`,0) > 0");
$totalSponsorMasuk = isset($sumS['masuk']) ? (int)$sumS['masuk'] : 0;
$totalContribMasuk = isset($sumC['masuk']) ? (int)$sumC['masuk'] : 0;
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
      $reservedT = (int)db_var("SELECT COALESCE(SUM(`amount`),0) FROM `epi_commission_payout` WHERE `receiver_id`=".$iduser." AND `type`='".cek($type)."' AND `status` IN ('requested','processed','paid')");
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
          $sumRequested = (int)db_var("SELECT COALESCE(SUM(`amount`),0) FROM `epi_commission_payout` WHERE `receiver_id`=".$iduser." ".(($source!=='all')?" AND `type`='".cek($reqType)."'":"")." AND `status` IN ('requested','processed','paid')");
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
        <div class="epi-card">
          <div class="epi-label">💰 Total Saldo Anda</div>
          <div class="epi-value">'.('Rp '.number_format($saldoAvailableTotal)).'</div>
          <form action="" method="post" class="mt-2" id="autoWithdrawForm">
            <div class="mb-2">
              <select name="req_type" class="form-select form-select-sm" aria-label="Pilih tipe komisi">
                <option value="sponsor"'.($reqType==='sponsor'?' selected':'').'>🏆 Penjualan (Referral)</option>
                <option value="contrib"'.($reqType==='contrib'?' selected':'').'>👥 Kontributor</option>
              </select>
            </div>
            <div class="mb-2 text-muted" aria-live="polite" id="saldoInfo">Saldo tersedia tipe terpilih: Rp '.number_format(($reqType==='sponsor'?$saldoAvailSponsor:$saldoAvailContrib)).'</div>
            <input type="hidden" name="csrf_token" value="'.htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES).'" />
            <input type="hidden" name="confirm_auto" value="0" />
            <button type="submit" name="withdraw_request" value="1" class="btn-withdraw" id="btnWithdraw"><i class="fas fa-wallet" aria-hidden="true"></i>Withdraw</button>
          </form>
        </div>
      </div>
      <div class="col-md-4">
        <div class="epi-card">
          <div class="epi-label">🛒 Saldo Penjualan</div>
          <div class="text-muted" aria-label="Saldo tersedia tipe sponsor">Tersedia</div>
          <div class="epi-value">'.('Rp '.number_format($saldoAvailSponsor)).'</div>
          <div class="mt-2 text-muted" aria-label="Total Komisi Diperoleh">Total Komisi Diperoleh</div>
          <div class="h6 fw-bold">'.('Rp '.number_format($totalSponsorMasuk)).'</div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="epi-card">
          <div class="epi-label">👥 Saldo Kontributor</div>
          <div class="text-muted" aria-label="Saldo tersedia tipe kontributor">Tersedia</div>
          <div class="epi-value">'.('Rp '.number_format($saldoAvailContrib)).'</div>
          <div class="mt-2 text-muted" aria-label="Total Komisi Diperoleh">Total Komisi Diperoleh</div>
          <div class="h6 fw-bold">'.('Rp '.number_format($totalContribMasuk)).'</div>
        </div>
      </div>
    </div>';
    echo '<script>
    (function(){
      var f = document.getElementById("autoWithdrawForm"); if(!f) return;
      var sel = f.querySelector("select[name=req_type]");
      var btn = document.getElementById("btnWithdraw");
      var info = document.getElementById("saldoInfo");
      var availS = '.(int)$saldoAvailSponsor.';
      var availC = '.(int)$saldoAvailContrib.';
      var minWd = '.(int)($settings['min_withdraw'] ?? 25000).';
      function update(){
        var t = sel.value; var av = (t==="contrib")?availC:availS;
        info.textContent = "Saldo tersedia tipe terpilih: Rp "+new Intl.NumberFormat("id-ID").format(av);
        var disabled = (av < minWd);
        btn.disabled = disabled;
        btn.classList.toggle("disabled", disabled);
        if(disabled){ btn.setAttribute("title", "Minimal pencairan Rp "+new Intl.NumberFormat("id-ID").format(minWd)+" — saldo belum mencukupi"); }
        else { btn.removeAttribute("title"); }
      }
      update(); sel.addEventListener("change", update);
      f.addEventListener("submit", function(e){
        if(!navigator.onLine){ e.preventDefault(); alert("Koneksi terputus. Periksa jaringan Anda, lalu coba lagi."); return; }
        var t = sel.value; var av = (t==="contrib")?availC:availS; if(av < minWd){ e.preventDefault(); alert("Saldo belum mencapai minimal pencairan: Rp "+new Intl.NumberFormat("id-ID").format(minWd)); return; }
        var ok = confirm("Konfirmasi Withdraw Otomatis:\nTipe: "+(t==="contrib"?"Kontributor":"Penjualan")+"\nJumlah: Rp "+new Intl.NumberFormat("id-ID").format(av)+"\n\nLanjutkan?");
        if(!ok){ e.preventDefault(); return; }
        f.querySelector("input[name=confirm_auto]").value = "1";
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
    $colCA = db_row("SHOW COLUMNS FROM `epi_commission_payout` LIKE 'canceled_at'");
    if (!is_array($colCA) || !isset($colCA['Field'])) { db_query("ALTER TABLE `epi_commission_payout` ADD `canceled_at` DATETIME NULL"); }
    $reqRows = db_select("SELECT p.`created_at`,p.`amount`,p.`gross_amount`,p.`tax_percent`,p.`tax_amount`,p.`net_amount`,p.`status`,p.`type`,p.`paid_at`,p.`reject_reason`,p.`cancel_reason`,p.`canceled_at` FROM `epi_commission_payout` p WHERE p.`receiver_id`=".$iduser.$whereType." ORDER BY p.`created_at` DESC, p.`id` DESC LIMIT 50");
    echo '
    <div class="table-responsive" style="width:100%">
      <table class="table table-hover table-bordered" style="width:100%">
        <thead class="table-secondary">
          <tr>
            <th>Waktu</th>
            <th class="text-end">Komisi (Gross)</th>
            <th class="text-end">PPh21 (%)</th>
            <th class="text-end">Potongan PPh21</th>
            <th class="text-end">Komisi Bersih</th>
            <th>Tipe</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>';
    if (is_array($reqRows) && count($reqRows)>0) {
      foreach ($reqRows as $r) {
        $st = htmlspecialchars($r['status'] ?? '', ENT_QUOTES);
        $paidInfo = (!empty($r['paid_at']) ? ('<br/><small class="text-muted">Paid: '.date('d/m/Y H:i:s', strtotime($r['paid_at'])).'</small>') : '');
        $ty = strtolower((string)($r['type'] ?? ''));
        $tyLabel = ($ty==='contrib' ? 'Kontribusi' : ($ty==='sponsor' ? 'Pereferral' : $ty));
        $gross = isset($r['gross_amount']) ? (int)$r['gross_amount'] : (int)$r['amount'];
        $pph   = isset($r['tax_percent']) ? (float)$r['tax_percent'] : (isset($settings['pph21_percent']) ? (float)$settings['pph21_percent'] : 0.0);
        if ($pph < 0) { $pph = 0.0; } if ($pph > 100) { $pph = 100.0; }
        $tax   = isset($r['tax_amount']) ? (int)$r['tax_amount'] : (int)round($gross*($pph/100.0));
        $net   = isset($r['net_amount']) ? (int)$r['net_amount'] : max(0, $gross - $tax);
        $badge = ($st==='paid'?'success':($st==='processed'?'warning':($st==='rejected'?'danger':($st==='canceled'?'danger':'secondary'))));
        $reasonLine = '';
        if ($st==='rejected' && !empty($r['reject_reason'])) { $reasonLine = '<br/><small class="text-muted">Alasan: '.htmlspecialchars($r['reject_reason'], ENT_QUOTES).'</small>'; }
        if ($st==='canceled' && !empty($r['cancel_reason'])) { $reasonLine = '<br/><small class="text-muted">Alasan: '.htmlspecialchars($r['cancel_reason'], ENT_QUOTES).'</small>'; }
        echo '<tr><td>'.htmlspecialchars(date('d/m/Y H:i:s', strtotime($r['created_at'] ?? date('Y-m-d H:i:s'))), ENT_QUOTES).$paidInfo.'</td><td class="text-end">'.number_format($gross).'</td><td class="text-end">'.number_format($pph,2).'</td><td class="text-end">'.number_format($tax).'</td><td class="text-end">'.number_format($net).'</td><td>'.htmlspecialchars($tyLabel, ENT_QUOTES).'</td><td><span class="badge bg-'.$badge.'">'.strtoupper($st).'</span>'.$reasonLine.'</td></tr>';
      }
    } else {
      echo '<tr><td colspan="4" class="text-center text-muted">Belum ada permintaan pencairan</td></tr>';
    }
    echo '</tbody></table></div>';
    $rowsS = db_select("SELECT DATE_FORMAT(`lap_tanggal`,'%Y-%m') AS `bulan`, COALESCE(SUM(`lap_masuk`)-SUM(`lap_keluar`),0) AS `komisi` FROM `sa_laporan` WHERE `lap_idsponsor`=".$iduser." AND `lap_code`=2 GROUP BY `bulan` ORDER BY `bulan` DESC");
    $rowsC = db_select("SELECT DATE_FORMAT(`lap_tanggal`,'%Y-%m') AS `bulan`, COALESCE(SUM(`lap_masuk`)-SUM(`lap_keluar`),0) AS `komisi` FROM `sa_laporan` WHERE `lap_idsponsor`=".$iduser." AND `lap_code`=3 GROUP BY `bulan` ORDER BY `bulan` DESC");
    $mapS = array(); $mapC = array(); $mon = array();
    if (is_array($rowsS)) { foreach ($rowsS as $r) { $mapS[$r['bulan']] = (int)$r['komisi']; $mon[] = $r['bulan']; } }
    if (is_array($rowsC)) { foreach ($rowsC as $r) { $mapC[$r['bulan']] = (int)$r['komisi']; $mon[] = $r['bulan']; } }
    $mon = array_values(array_unique($mon)); rsort($mon);
    echo '
    <div class="table-responsive" style="width:100%">
    	<table class="table table-hover table-bordered" style="width:100%">
    	    <thead class="table-secondary">
    	        <tr>
    	            <th>Bulan</th>
    	            <th class="text-end">Komisi Penjualan (Pereferral)</th>
    	            <th class="text-end">Komisi Kontributor</th>
    	            <th class="text-end">Total Komisi</th>
    	        </tr>
    	    </thead>
    	    <tbody>';
    foreach ($mon as $bulan) {
        $ks = isset($mapS[$bulan]) ? $mapS[$bulan] : 0;
        $kc = isset($mapC[$bulan]) ? $mapC[$bulan] : 0;
        $kt = $ks + $kc;
        echo  '
        <tr>
            <td><a href="laporankomisi?detil='.$bulan.'">'.date('F Y', strtotime($bulan)).'</a></td>
            <td class="text-end">'.number_format($ks).'</td>
            <td class="text-end">'.number_format($kc).'</td>
            <td class="text-end">'.number_format($kt).'</td>
        </tr>';
    }
    echo '
    	</tbody>
    	</table>
    	</div>';


}
showfooter();
