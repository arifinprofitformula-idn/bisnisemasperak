<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
@require_once dirname(__DIR__,2).'/plugin/epi-role-manager/index.php';
$roleCode = (int)($datamember['mem_role'] ?? 1);
$allow = in_array($roleCode, array(6,9), true);
if (!$allow && function_exists('epi_role_permissions_for_member')) {
  $perms = epi_role_permissions_for_member($datamember);
  if (is_array($perms) && !empty($perms['bayar'])) { $allow = true; }
}
// Log akses
@db_query("CREATE TABLE IF NOT EXISTS `epi_admin_finance_log` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `action` VARCHAR(64) NOT NULL,
  `admin_wa` VARCHAR(32) NULL,
  `changed_by` INT NULL,
  `info` VARCHAR(255) NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `ip` VARCHAR(64) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$__wa = isset($datamember['mem_whatsapp']) ? formatwa($datamember['mem_whatsapp']) : (isset($settings['whatsapp']) ? formatwa($settings['whatsapp']) : '');
@db_query("INSERT INTO `epi_admin_finance_log` (`action`,`admin_wa`,`changed_by`,`info`,`ip`) VALUES ('page_access','".cek($__wa)."',".(int)$datamember['mem_id'].",'bayar allow=".($allow?'true':'false')." role=".$roleCode."','".cek(realIP())."')");
if (!$allow) { echo '<div class="alert alert-danger">Akses ditolak. Role Anda tidak memiliki izin untuk halaman Bayar. Silakan minta Administrator mengaktifkan permission <code>bayar</code> di halaman Setting Role.</div>'; showfooter(); return; }
$head['pagetitle']='Pembayaran Komisi';
showheader($head);

// --- Global UI Styles & Scripts (Sticky Header, etc) ---
echo '
<style>
	/* Container Table dengan Freeze Header & Column */
	.table-freeze-container {
		max-height: 75vh;
		overflow: auto;
		position: relative;
		border: 1px solid #dee2e6;
		border-radius: 0.375rem;
		box-shadow: 0 .125rem .25rem rgba(0,0,0,.075);
		background-color: #fff;
		scroll-behavior: smooth;
	}
	.table-freeze-container .table-bordered { border: 0; }
	.table-freeze-container thead th {
		position: sticky;
		top: 0;
		z-index: 20;
		background-color: #e2e3e5;
		color: #383d41;
		box-shadow: inset 0 -2px 0 #adb5bd;
		border-bottom: 0;
		text-align: center;
		vertical-align: middle;
	}
	.table-freeze-container tbody td.sticky-col {
		position: sticky;
		left: 0;
		z-index: 10;
		background-color: #fff;
		border-right: 2px solid #dee2e6;
		transition: background-color 0.15s ease-in-out;
	}
	.table-freeze-container tbody tr:hover td.sticky-col { background-color: #ececec; }
	.table-freeze-container thead th.sticky-col {
		left: 0;
		z-index: 30;
		background-color: #e2e3e5;
		border-right: 2px solid #dee2e6;
	}
	.pagination-container { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-top: 1rem; }
	@media (max-width: 576px) { .pagination-container { justify-content: center; text-align: center; } }
	.loading-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.7); z-index: 9999; display: none; align-items: center; justify-content: center; backdrop-filter: blur(2px); }
</style>
<div class="loading-overlay" id="loadingOverlay">
	<div class="text-center">
		<div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;"></div>
		<div class="mt-2 fw-bold text-dark">Memuat data...</div>
	</div>
</div>
';

// Filter tipe dan status payout
$tipe = isset($_GET['tipe']) && in_array($_GET['tipe'], array('sponsor','kontributor')) ? $_GET['tipe'] : 'sponsor';
$status = isset($_GET['status']) && in_array($_GET['status'], array('pending','paid')) ? $_GET['status'] : 'pending';
// Periode filter untuk status paid
$start = (isset($_GET['start']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['start'])) ? (string)$_GET['start'] : '';
$end   = (isset($_GET['end']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['end'])) ? (string)$_GET['end'] : '';

 
if (isset($_GET['detil']) && is_numeric($_GET['detil'])) {
	$id_detil = (int)$_GET['detil'];
	$datasponsor = db_row("SELECT * FROM `sa_member` WHERE `mem_id`=".$id_detil);
	echo '<h3>Data Komisi '.$datasponsor['mem_nama'].'</h3>';
	
	// --- Pagination Logic ---
	$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
	if ($page < 1) $page = 1;
	
	$limit_options = [50, 100, 200, 300, 400, 500];
	$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
	if (!in_array($limit, $limit_options)) $limit = 50;
	
	$offset = ($page - 1) * $limit;
	
	// Count Total
	$total_rows = (int)db_var("SELECT COUNT(*) FROM `sa_laporan` WHERE `lap_idsponsor`=".$id_detil." AND `lap_code`=2");
	$total_pages = ceil($total_rows / $limit);
	
	// Opening Balance Calculation
	// Calculate sum of all previous transactions to set initial saldo for this page
	$saldo = 0;
	if ($offset > 0) {
		// Use a subquery to sum the first N rows (ordered by ID ASC)
		$saldo = (int)db_var("SELECT SUM(`lap_masuk`) - SUM(`lap_keluar`) FROM (SELECT `lap_masuk`, `lap_keluar` FROM `sa_laporan` WHERE `lap_idsponsor`=".$id_detil." AND `lap_code`=2 ORDER BY `lap_id` ASC LIMIT ".$offset.") AS tmp");
	}
	
	// Fetch Data for Current Page
	$data = db_select("SELECT * FROM `sa_laporan` 
		LEFT JOIN `sa_member` ON `sa_member`.`mem_id` = `sa_laporan`.`lap_idmember`
		WHERE `lap_idsponsor`=".$id_detil." AND `lap_code`=2
		ORDER BY `sa_laporan`.`lap_id` ASC
		LIMIT $limit OFFSET $offset");
	
	// --- UI Styles & Scripts (Moved to top) ---

	// --- Controls (Limit Selector) ---
	echo '<div class="card mb-3 border-0 shadow-sm"><div class="card-body p-2">';
	echo '<form method="get" class="d-flex align-items-center" onsubmit="document.getElementById(\'loadingOverlay\').style.display=\'flex\'">';
	echo '<input type="hidden" name="detil" value="'.$id_detil.'">';
	echo '<span class="me-2 text-muted">Tampilkan</span>';
	echo '<select name="limit" class="form-select form-select-sm d-inline-block w-auto border-primary" onchange="document.getElementById(\'loadingOverlay\').style.display=\'flex\'; this.form.submit();">';
	foreach ($limit_options as $opt) {
		$sel = ($limit == $opt) ? 'selected' : '';
		echo '<option value="'.$opt.'" '.$sel.'>'.$opt.'</option>';
	}
	echo '</select>';
	echo '<span class="ms-2 text-muted">baris per halaman</span>';
	echo '</form>';
	echo '</div></div>';
	
	// --- Table ---
	echo '
	<div class="table-freeze-container">
	<table class="table table-hover table-bordered mb-0 align-middle">
	<thead class="table-secondary">
		<tr>
			<th class="sticky-col text-center" width="60">No</th>
			<th class="text-center" style="min-width: 110px;">Tanggal</th>
			<th class="text-center" style="min-width: 250px;">Keterangan</th>			
			<th class="text-center" style="min-width: 130px;">Pemasukan</th>
			<th class="text-center" style="min-width: 130px;">Pengeluaran</th>
			<th class="text-center" style="min-width: 130px;">Saldo</th>
		</tr>
	</thead>
	<tbody>';
	
	if (count($data) > 0) {
		$no = $offset + 1;
		foreach ($data as $row) {
			$saldo = $saldo + $row['lap_masuk'] - $row['lap_keluar'];
			if ($row['lap_masuk'] > 0) {
				$keterangan = $row['lap_keterangan'].' '.$row['mem_nama'].' Level: '.$row['lap_level'];
			} else {
				$keterangan = $row['lap_keterangan'];
			}
			echo '
		<tr>
			<td class="sticky-col text-center fw-bold text-secondary">'.$no.'</td>
			<td>'.$row['lap_tanggal'].'</td>
			<td>'.$keterangan.'</td>			
			<td class="text-end text-success">'.($row['lap_masuk']>0 ? number_format($row['lap_masuk']) : '-').'</td>
			<td class="text-end text-danger">'.($row['lap_keluar']>0 ? number_format($row['lap_keluar']) : '-').'</td>
			<td class="text-end fw-bold">'.number_format($saldo).'</td>
		</tr>';
			$no++;
		}
	} else {
		echo '<tr><td colspan="6" class="text-center p-5 text-muted"><i class="fas fa-inbox fa-3x mb-3"></i><br>Tidak ada data ditemukan pada halaman ini</td></tr>';
	}
	echo '
	</tbody>
	</table>
	</div>
	';
	
	// --- Footer Pagination ---
	$start_entry = ($total_rows > 0) ? $offset + 1 : 0;
	$end_entry = min($offset + $limit, $total_rows);
	$base_url = '?detil='.$id_detil.'&limit='.$limit;
	
	echo '<div class="pagination-container">';
	echo '<div class="text-muted small">Menampilkan <strong>'.$start_entry.'</strong> sampai <strong>'.$end_entry.'</strong> dari <strong>'.$total_rows.'</strong> data</div>';
	
	if ($total_pages > 1) {
		echo '<nav><ul class="pagination pagination-sm mb-0 shadow-sm">';
		
		// Prev
		$prev_disabled = ($page <= 1) ? 'disabled' : '';
		$prev_url = $base_url.'&page='.($page-1);
		echo '<li class="page-item '.$prev_disabled.'"><a class="page-link" href="'.$prev_url.'" onclick="document.getElementById(\'loadingOverlay\').style.display=\'flex\'"><i class="fas fa-chevron-left"></i></a></li>';
		
		// Pages (Smart Logic: 1, ..., p-1, p, p+1, ..., last)
		$start_page = max(1, $page - 2);
		$end_page = min($total_pages, $page + 2);
		
		if ($start_page > 1) {
			 echo '<li class="page-item"><a class="page-link" href="'.$base_url.'&page=1" onclick="document.getElementById(\'loadingOverlay\').style.display=\'flex\'">1</a></li>';
			 if ($start_page > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
		}
		
		for ($i = $start_page; $i <= $end_page; $i++) {
			$active = ($i == $page) ? 'active' : '';
			echo '<li class="page-item '.$active.'"><a class="page-link" href="'.$base_url.'&page='.$i.'" onclick="document.getElementById(\'loadingOverlay\').style.display=\'flex\'">'.$i.'</a></li>';
		}
		
		if ($end_page < $total_pages) {
			if ($end_page < $total_pages - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
			echo '<li class="page-item"><a class="page-link" href="'.$base_url.'&page='.$total_pages.'" onclick="document.getElementById(\'loadingOverlay\').style.display=\'flex\'">'.$total_pages.'</a></li>';
		}
		
		// Next
		$next_disabled = ($page >= $total_pages) ? 'disabled' : '';
		$next_url = $base_url.'&page='.($page+1);
		echo '<li class="page-item '.$next_disabled.'"><a class="page-link" href="'.$next_url.'" onclick="document.getElementById(\'loadingOverlay\').style.display=\'flex\'"><i class="fas fa-chevron-right"></i></a></li>';
		
		echo '</ul></nav>';
	}
	echo '</div>'; // End pagination-container
	
} else {

	# Pencairan Komisi
    if (isset($_POST['cair']) && is_numeric($_POST['cair']) && isset($_POST['idsponsor']) && is_numeric($_POST['idsponsor'])) {
        $ids = (int)$_POST['idsponsor'];
        $req = (int)$_POST['cair'];
        $tipePost = (isset($_POST['tipe']) && in_array($_POST['tipe'], array('sponsor','kontributor'))) ? $_POST['tipe'] : $tipe;
        $pType   = ($tipePost==='kontributor') ? 'contrib' : 'sponsor';
        $lapCode = ($tipePost==='kontributor') ? 3 : 2;
        $saldoRow = db_row("SELECT SUM(`lap_masuk`)-SUM(`lap_keluar`) AS `komisi` FROM `sa_laporan` WHERE `lap_code`=".$lapCode." AND `lap_idsponsor`=".$ids." GROUP BY `lap_idsponsor`");
        $saldo = isset($saldoRow['komisi']) ? (int)$saldoRow['komisi'] : 0;
        $sumPending = (int)db_var("SELECT COALESCE(SUM(`amount`),0) FROM `epi_commission_payout` WHERE `receiver_id`=".$ids." AND `type`='".cek($pType)."' AND `status` IN ('requested','pending','processed')");
        $pendingRows = db_select("SELECT `id`,`amount`,`created_at`,`status` FROM `epi_commission_payout` WHERE `receiver_id`=".$ids." AND `type`='".cek($pType)."' AND `status` IN ('requested','pending','processed') ORDER BY `created_at` ASC");
        $pph = isset($settings['pph21_percent']) ? (float)$settings['pph21_percent'] : 0.0; if ($pph < 0) { $pph = 0.0; } if ($pph > 100) { $pph = 100.0; }
        $sumGross = 0; $sumNet = 0;
        if (is_array($pendingRows)) { foreach ($pendingRows as $pr) { $amt=(int)$pr['amount']; $sumGross += $amt; $sumNet += max(0, $amt - (int)round($amt*($pph/100.0))); } }
        $expectedNet = max(0, min($sumNet, $saldo));
        if ($req !== $expectedNet) {
            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert"><strong>Error!</strong> Nominal Proses Bayar harus sama dengan total pending bersih: '.number_format($expectedNet).'<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        } elseif ($sumGross > 0) {
            $allowed = $sumGross;
            $now = date('Y-m-d H:i:s');

            // START TRANSACTION to prevent race conditions
            db_query("START TRANSACTION");
            $ok = true;
            $totalNetPaid = 0;
            $totalTaxPaid = 0;
            $remain = $allowed;
            
            if (is_array($pendingRows)) {
                foreach ($pendingRows as $pr) {
                    if ($remain <= 0) { break; }
                    $pid = (int)$pr['id'];
                    $amt = (int)$pr['amount'];
                    
                    if ($remain >= $amt) {
                        // Full Payment for this request
                        $taxAmt = (int)round($amt * ($pph/100.0));
                        $netAmt = max(0, $amt - $taxAmt);
                        
                        $u = db_query("UPDATE `epi_commission_payout` SET `status`='paid',`paid_at`='".$now."',`paid_by`=".$datamember['mem_id'].",`gross_amount`=".$amt.",`tax_percent`=".number_format($pph,2,'.','').",`tax_amount`=".$taxAmt.",`net_amount`=".$netAmt." WHERE `id`=".$pid);
                        if (!$u) { $ok = false; break; }
                        
                        $oldSt = isset($pr['status']) ? strtolower(trim((string)$pr['status'])) : 'pending';
                        $allowedSt = array('requested','pending','processed','paid');
                        if (!in_array($oldSt, $allowedSt, true)) { $oldSt = 'pending'; }
                        db_query("INSERT INTO `epi_commission_payout_log` (`payout_id`,`admin_id`,`old_status`,`new_status`,`note`) VALUES (".$pid.",".(int)$datamember['mem_id'].",'".cek($oldSt)."','paid','gross=".$amt.",tax=".$taxAmt.",net=".$netAmt."')");
                        
                        // Ledger Entry (Debit)
                        $refPay = "PAYOUT-".$pid;
                        $refTax = "TAX-".$pid;
                        
                        // Debit Net
                        $ins1 = db_query("INSERT INTO `sa_laporan` (`lap_idmember`,`lap_idsponsor`,`lap_tanggal`,`lap_masuk`,`lap_keluar`,`lap_code`,`lap_keterangan`,`lap_reference`,`payout_id`) VALUES (0,".$ids.",'".$now."',0,".$netAmt.",".$lapCode.",'Pencairan Komisi #".$pid."','".$refPay."',".$pid.")");
                        if (!$ins1) { $ok = false; break; }
                        
                        // Debit Tax
                        if ($taxAmt > 0) {
                            $ins2 = db_query("INSERT INTO `sa_laporan` (`lap_idmember`,`lap_idsponsor`,`lap_tanggal`,`lap_masuk`,`lap_keluar`,`lap_code`,`lap_keterangan`,`lap_reference`,`payout_id`) VALUES (0,".$ids.",'".$now."',0,".$taxAmt.",".$lapCode.",'Potongan PPh21 #".$pid."','".$refTax."',".$pid.")");
                            // Credit Tax to Admin
                            db_query("INSERT INTO `sa_laporan` (`lap_idmember`,`lap_idsponsor`,`lap_tanggal`,`lap_masuk`,`lap_keluar`,`lap_code`,`lap_keterangan`,`lap_reference`,`payout_id`) VALUES (".$ids.",0,'".$now."',".$taxAmt.",0,1,'Potongan PPh21 Ditahan #".$pid."','".$refTax."',".$pid.")");
                            if (!$ins2) { $ok = false; break; }
                        }
                        
                        $totalNetPaid += $netAmt;
                        $totalTaxPaid += $taxAmt;
                        $remain -= $amt;
                        
                    } else {
                        // Partial Payment (Split Request)
                        $payAmt = $remain;
                        $pendingAmt = $amt - $remain;
                        
                        $taxAmt = (int)round($payAmt * ($pph/100.0));
                        $netAmt = max(0, $payAmt - $taxAmt);
                        
                        // Update current to Paid (Partial)
                        $u = db_query("UPDATE `epi_commission_payout` SET `amount`=".$payAmt.",`status`='paid',`paid_at`='".$now."',`paid_by`=".$datamember['mem_id'].",`gross_amount`=".$payAmt.",`tax_percent`=".number_format($pph,2,'.','').",`tax_amount`=".$taxAmt.",`net_amount`=".$netAmt." WHERE `id`=".$pid);
                        if (!$u) { $ok = false; break; }
                        
                        // Create new Pending for remainder
                        db_query("INSERT INTO `epi_commission_payout` (`lap_id`,`order_id`,`receiver_id`,`type`,`amount`,`status`,`created_at`) VALUES (NULL, NULL, ".$ids.", '".cek($pType)."', ".$pendingAmt.", 'pending', '".$now."')");
                        
                        $refPay = "PAYOUT-".$pid."-PARTIAL";
                        $refTax = "TAX-".$pid."-PARTIAL";
                        
                        $ins1 = db_query("INSERT INTO `sa_laporan` (`lap_idmember`,`lap_idsponsor`,`lap_tanggal`,`lap_masuk`,`lap_keluar`,`lap_code`,`lap_keterangan`,`lap_reference`,`payout_id`) VALUES (0,".$ids.",'".$now."',0,".$netAmt.",".$lapCode.",'Pencairan Komisi (Partial) #".$pid."','".$refPay."',".$pid.")");
                        if (!$ins1) { $ok = false; break; }

                        if ($taxAmt > 0) {
                             db_query("INSERT INTO `sa_laporan` (`lap_idmember`,`lap_idsponsor`,`lap_tanggal`,`lap_masuk`,`lap_keluar`,`lap_code`,`lap_keterangan`,`lap_reference`,`payout_id`) VALUES (0,".$ids.",'".$now."',0,".$taxAmt.",".$lapCode.",'Potongan PPh21 (Partial) #".$pid."','".$refTax."',".$pid.")");
                             db_query("INSERT INTO `sa_laporan` (`lap_idmember`,`lap_idsponsor`,`lap_tanggal`,`lap_masuk`,`lap_keluar`,`lap_code`,`lap_keterangan`,`lap_reference`,`payout_id`) VALUES (".$ids.",0,'".$now."',".$taxAmt.",0,1,'Potongan PPh21 Ditahan #".$pid."','".$refTax."',".$pid.")");
                        }
                        
                        $totalNetPaid += $netAmt;
                        $totalTaxPaid += $taxAmt;
                        $remain = 0;
                    }
                }
            }
            
            if ($ok) {
                db_query("COMMIT");
                $remainingPending = (int)db_var("SELECT COALESCE(SUM(`amount`),0) FROM `epi_commission_payout` WHERE `receiver_id`=".$ids." AND `type`='".cek($pType)."' AND `status` IN ('requested','pending','processed')");
                $datalain = array('komisi' => number_format($allowed), 'pph21' => number_format($totalTaxPaid), 'net' => number_format($totalNetPaid));
                sa_notif('cair_komisi',$ids,$datalain);
                
                $msg = 'Pencairan Komisi berhasil. Gross: '.number_format($allowed).', PPh21: '.number_format($totalTaxPaid).', Net: '.number_format($totalNetPaid).'.';
                if ($remainingPending === 0) { $msg .= ' Semua pending selesai.'; }
                
                echo '<div class="alert alert-success alert-dismissible fade show" role="alert"><strong>Sukses!</strong> '.$msg.'<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                echo '<script>setTimeout(function(){ var qs="?status='.( ($remainingPending===0)?'paid':$status ).'&tipe='.$tipe.'"; window.location.replace(window.location.pathname+qs); }, 1000);</script>';
            } else {
                db_query("ROLLBACK");
                echo '<div class="alert alert-danger alert-dismissible fade show" role="alert"><strong>Gagal!</strong> Terjadi kesalahan database saat memproses transaksi.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            }
        } else {
            if ($sumPending === 0) {
                echo '<div class="alert alert-success alert-dismissible fade show" role="alert"><strong>Ok!</strong> Semua transaksi pending telah dibayar.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                echo '<script>setTimeout(function(){ var qs="?status=paid&tipe='.$tipe.'"; window.location.replace(window.location.pathname+qs); }, 800);</script>';
            } else {
                echo '<div class="alert alert-danger alert-dismissible fade show" role="alert"><strong>Error!</strong> Nominal melebihi saldo/komisi pending. Tersedia: '.number_format($saldo).'<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            }
        }
    }

    // Pembatalan Pembayaran Komisi (membatalkan batch terakhir yang ditandai 'paid')
    if (isset($_POST['action']) && $_POST['action']==='cancel_payout' && isset($_POST['receiver_id']) && is_numeric($_POST['receiver_id'])) {
        $rid = (int)$_POST['receiver_id'];
        $tipePost = (isset($_POST['tipe']) && in_array($_POST['tipe'], array('sponsor','kontributor'))) ? $_POST['tipe'] : $tipe;
        $pType   = ($tipePost==='kontributor') ? 'contrib' : 'sponsor';
        $lapCode = ($tipePost==='kontributor') ? 3 : 2;
        $postedTs = isset($_POST['batch_ts']) ? trim((string)$_POST['batch_ts']) : '';
        $lastRow = db_row("SELECT MAX(`paid_at`) AS `ts` FROM `epi_commission_payout` WHERE `receiver_id`=".$rid." AND `type`='".cek($pType)."' AND `status`='paid'");
        $lastTs = isset($lastRow['ts']) ? trim((string)$lastRow['ts']) : '';
        if (!empty($postedTs) && $lastTs!=='' && $postedTs!==$lastTs) {
            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert"><strong>Error!</strong> Nomor transaksi tidak sesuai dengan batch terakhir.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            return;
        }
        // Validasi satu kali pembatalan per transaksi (cek log berdasarkan ts)
        if (!empty($postedTs)) {
            $ever = (int)db_var("SELECT COUNT(*) FROM `epi_commission_payout_log` l JOIN `epi_commission_payout` p ON p.`id`=l.`payout_id` WHERE p.`receiver_id`=".$rid." AND p.`type`='".cek($pType)."' AND l.`note` LIKE 'cancel ts=".cek($postedTs)."%'");
            if ($ever>0) {
                echo '<div class="alert alert-warning alert-dismissible fade show" role="alert"><strong>Perhatian!</strong> Anda hanya dapat melakukan pembatalan satu kali untuk transaksi ini.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                return;
            }
        }
        if (empty($lastTs)) {
            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert"><strong>Error!</strong> Tidak ada pembayaran yang bisa dibatalkan.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        } else {
            $rows = db_select("SELECT `id`,`amount`,`gross_amount`,`tax_amount`,`net_amount`,`paid_at` FROM `epi_commission_payout` WHERE `receiver_id`=".$rid." AND `type`='".cek($pType)."' AND `status`='paid' AND `paid_at`='".cek($lastTs)."'");
            if ($rows === false || !is_array($rows) || count($rows)===0) {
                echo '<div class="alert alert-danger alert-dismissible fade show" role="alert"><strong>Error!</strong> Data batch pembayaran tidak ditemukan.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            } else {
                // VERIFIKASI INTEGRITAS DATA (Security Check)
                // Pastikan setiap payout yang akan dibatalkan MEMILIKI record pengeluaran (expense) di sa_laporan.
                // Jika tidak ada expense tapi kita refund, saldo user akan bertambah secara tidak sah.
                $verifyOk = true;
                foreach ($rows as $rVerify) {
                    $qidCheck = (int)$rVerify['id'];
                    // Cek by payout_id ATAU lap_reference (fallback compatibility)
                    $chk = db_row("SELECT `lap_id` FROM `sa_laporan` WHERE (`payout_id`=$qidCheck OR `lap_reference`='PAYOUT-$qidCheck' OR `lap_reference` LIKE 'PAYOUT-$qidCheck-%') AND `lap_keluar` > 0");
                    if (!$chk) {
                        $verifyOk = false;
                        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert"><strong>Security Alert!</strong> Integritas data tidak valid. Transaksi Payout #'.$qidCheck.' tidak memiliki data pengeluaran (expense) di buku besar. Pembatalan ditolak untuk mencegah anomali saldo.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                        break;
                    }
                }
                if (!$verifyOk) { return; }

                $sumGross = 0; $sumTax = 0; $sumNet = 0; $pph = isset($settings['pph21_percent']) ? (float)$settings['pph21_percent'] : 0.0; if ($pph<0) { $pph=0.0; } if ($pph>100) { $pph=100.0; }
                foreach ($rows as $r) {
                    $amt = (int)($r['gross_amount'] ?? $r['amount']);
                    $taxAmt = isset($r['tax_amount']) && is_numeric($r['tax_amount']) ? (int)$r['tax_amount'] : (int)round($amt*($pph/100.0));
                    $netAmt = isset($r['net_amount']) && is_numeric($r['net_amount']) ? (int)$r['net_amount'] : max(0, $amt - $taxAmt);
                    $sumGross += $amt; $sumTax += $taxAmt; $sumNet += $netAmt;
                }
                $now = date('Y-m-d H:i:s');
                $reason = isset($_POST['alasan']) ? trim((string)$_POST['alasan']) : '';
                $transNo = 'TX-'.(string)$rid.'-'.($pType==='contrib'?'KONTR':'SPON').'-'.date('YmdHis', strtotime($lastTs));
                
                // START TRANSACTION
                db_query("START TRANSACTION");
                $okAll = true;
                $processedNet = 0;
                $processedTax = 0;
                $processedGross = 0;

                // Ensure schema supports canceled status & reason columns
                $colStatX = db_row("SHOW COLUMNS FROM `epi_commission_payout` LIKE 'status'");
                if (is_array($colStatX) && isset($colStatX['Type'])) {
                    $t = strtolower($colStatX['Type']);
                    if (strpos($t, "enum(") !== false && (strpos($t, "'canceled'") === false || strpos($t, "'rejected'") === false)) {
                        db_query("ALTER TABLE `epi_commission_payout` MODIFY `status` ENUM('requested','pending','processed','paid','rejected','canceled') NOT NULL DEFAULT 'pending'");
                    } elseif (preg_match('/^varchar\((\d+)\)/', $t, $m)) {
                        if ((int)$m[1] < 16) { db_query("ALTER TABLE `epi_commission_payout` MODIFY `status` VARCHAR(32) NOT NULL DEFAULT 'pending'"); }
                    }
                }
                $colCancelReason = db_row("SHOW COLUMNS FROM `epi_commission_payout` LIKE 'cancel_reason'");
                if (!is_array($colCancelReason) || !isset($colCancelReason['Field'])) { db_query("ALTER TABLE `epi_commission_payout` ADD `cancel_reason` VARCHAR(255) NULL"); }
                $colCanceledAt = db_row("SHOW COLUMNS FROM `epi_commission_payout` LIKE 'canceled_at'");
                if (!is_array($colCanceledAt) || !isset($colCanceledAt['Field'])) { db_query("ALTER TABLE `epi_commission_payout` ADD `canceled_at` DATETIME NULL"); }
                // Ensure log ENUMs include canceled
                $colOldX = db_row("SHOW COLUMNS FROM `epi_commission_payout_log` LIKE 'old_status'");
                if (is_array($colOldX) && isset($colOldX['Type'])) { $tt=strtolower($colOldX['Type']); if (strpos($tt, "enum(")!==false && strpos($tt, "'canceled'")===false) { db_query("ALTER TABLE `epi_commission_payout_log` MODIFY `old_status` ENUM('requested','pending','processed','paid','canceled','rejected') NOT NULL"); } }
                $colNewX = db_row("SHOW COLUMNS FROM `epi_commission_payout_log` LIKE 'new_status'");
                if (is_array($colNewX) && isset($colNewX['Type'])) { $tn=strtolower($colNewX['Type']); if (strpos($tn, "enum(")!==false && strpos($tn, "'canceled'")===false) { db_query("ALTER TABLE `epi_commission_payout_log` MODIFY `new_status` ENUM('requested','pending','processed','paid','canceled','rejected') NOT NULL"); } }
                
                foreach ($rows as $r) {
                    $qid = (int)$r['id'];
                    $amt = (int)($r['gross_amount'] ?? $r['amount']);
                    $taxAmt = isset($r['tax_amount']) && is_numeric($r['tax_amount']) ? (int)$r['tax_amount'] : (int)round($amt*($pph/100.0));
                    $netAmt = isset($r['net_amount']) && is_numeric($r['net_amount']) ? (int)$r['net_amount'] : max(0, $amt - $taxAmt);
                    
                    $u = db_query("UPDATE `epi_commission_payout` SET `status`='canceled',`canceled_at`='".cek($now)."',`cancel_reason`='".cek($reason)."',`paid_at`=NULL,`paid_by`=NULL,`gross_amount`=NULL,`tax_percent`=NULL,`tax_amount`=NULL,`net_amount`=NULL WHERE `id`=".$qid);
                    if ($u === false) { $okAll = false; break; }
                    
                    db_query("INSERT INTO `epi_commission_payout_log` (`payout_id`,`admin_id`,`old_status`,`new_status`,`note`) VALUES (".$qid.",".(int)$datamember['mem_id'].",'paid','canceled','cancel ts=".cek($lastTs)." reason=".cek($reason)."')");
                    
                    // Reversal Ledger (Credit) linked to Payout ID
                    $refRefund = "REFUND-PAYOUT-".$qid;
                    $refTaxRefund = "REFUND-TAX-".$qid;
                    
                    // Refund Net
                    $ins1 = db_query("INSERT INTO `sa_laporan` (`lap_idmember`,`lap_idsponsor`,`lap_tanggal`,`lap_masuk`,`lap_keluar`,`lap_code`,`lap_keterangan`,`lap_reference`,`payout_id`) VALUES (0,".$rid.",'".$now."',".$netAmt.",0,".$lapCode.",'Pembatalan Pencairan Komisi #".$qid."','".$refRefund."',".$qid.")");
                    if (!$ins1) { $okAll = false; break; }
                    
                    // Refund Tax
                    if ($taxAmt > 0) {
                        $ins2 = db_query("INSERT INTO `sa_laporan` (`lap_idmember`,`lap_idsponsor`,`lap_tanggal`,`lap_masuk`,`lap_keluar`,`lap_code`,`lap_keterangan`,`lap_reference`,`payout_id`) VALUES (0,".$rid.",'".$now."',".$taxAmt.",0,".$lapCode.",'Pembatalan Potongan PPh21 #".$qid."','".$refTaxRefund."',".$qid.")");
                         // Reverse Admin Credit (Debit Admin) - Actually we usually just ignore admin side or debit it. 
                         // To balance admin books: Debit Admin (lap_keluar)
                         db_query("INSERT INTO `sa_laporan` (`lap_idmember`,`lap_idsponsor`,`lap_tanggal`,`lap_masuk`,`lap_keluar`,`lap_code`,`lap_keterangan`,`lap_reference`,`payout_id`) VALUES (".$rid.",0,'".$now."',0,".$taxAmt.",1,'Pembatalan PPh21 Ditahan #".$qid."','".$refTaxRefund."',".$qid.")");
                        if (!$ins2) { $okAll = false; break; }
                    }
                    
                    $processedNet += $netAmt;
                    $processedTax += $taxAmt;
                    $processedGross += $amt;
                }
                
                if (!$okAll) {
                    db_query("ROLLBACK");
                    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert"><strong>Error!</strong> Gagal memperbarui status pembayaran. '.htmlspecialchars((string)db_error()).'<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                } else {
                    db_query("COMMIT");
                    
                    $adminWa = isset($datamember['mem_whatsapp']) ? formatwa($datamember['mem_whatsapp']) : (isset($settings['whatsapp']) ? formatwa($settings['whatsapp']) : '');
                    db_query("INSERT INTO `epi_admin_finance_log` (`action`,`admin_wa`,`changed_by`,`info`,`ip`) VALUES ('cancel_payout','".cek($adminWa)."',".(int)$datamember['mem_id'].",'rid=".$rid.",type=".cek($pType).",ts=".cek($lastTs).",rows=".count($rows).",net=".$processedNet.",reason=".cek($reason).",transno=".cek($transNo)."','".cek(realIP())."')");
                    // Notifikasi WA ke member & admin menggunakan template cancel_payout
                    $helpContact = !empty($adminWa) ? $adminWa : (isset($settings['wa_admin']) ? formatwa($settings['wa_admin']) : (isset($settings['whatsapp']) ? formatwa($settings['whatsapp']) : ''));
                    $datalain = array('transno'=>$transNo, 'alasan'=>($reason!==''?$reason:'Tidak ada'), 'help_contact'=>$helpContact);
                    sa_notif('cancel_payout',$rid,$datalain);
                    echo '<div class="alert alert-success alert-dismissible fade show" role="alert"><strong>Ok!</strong> Pembatalan pembayaran berhasil. Gross: '.number_format($processedGross).', PPh21: '.number_format($processedTax).', Net: '.number_format($processedNet).'.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                    echo '<script>setTimeout(function(){ var qs="?status=pending&tipe='.( $tipe ).'"; window.location.replace(window.location.pathname+qs); }, 800);</script>';
                }
            }
        }
    }

	$where = '';
	if (isset($settings['minkomisi']) && $settings['minkomisi'] > 0) {
		$minkomisi = $settings['minkomisi'];
		$infominkomisi = '<br/>Hanya memunculkan member yang mendapat komisi lebih dari '.number_format($minkomisi);
	} else {
		$minkomisi = 0;
		$infominkomisi = '';
	}
    $whereMember = '';
    if (isset($_GET['cari']) && !empty($_GET['cari'])) {
        $s = cek($_GET['cari']);
        $whereMember = " AND (m.`mem_nama` LIKE '%".$s."%' 
                            OR m.`mem_email` LIKE '%".$s."%' 
                            OR m.`mem_whatsapp` LIKE '%".$s."%' 
                            OR m.`mem_datalain` LIKE '%".$s."%' 
                            OR m.`mem_kodeaff` LIKE '%".$s."%')";
        $having = '';
    } else {
        $having = " HAVING `komisi` > ".$minkomisi;
    }

    // Ambil payout komisi dan agregasi per sponsor
    $sqlType   = ($tipe==='kontributor') ? " AND p.`type`='contrib'" : " AND p.`type`='sponsor'";
    $rawPending = db_select("SELECT p.`receiver_id`,p.`amount`,p.`created_at`,m.`mem_nama`,m.`mem_datalain` FROM `epi_commission_payout` p LEFT JOIN `sa_member` m ON m.`mem_id`=p.`receiver_id` WHERE p.`status` IN ('requested','pending','processed')".$sqlType.$whereMember." ORDER BY p.`created_at` DESC");
    if ($rawPending === false) { echo '<div class="alert alert-warning">Gagal memuat data pending. Silakan muat ulang halaman.</div>'; $rawPending = array(); }
    $periodFilter = ($status==='paid' && $start && $end) ? " AND DATE(p.`paid_at`) BETWEEN '".cek($start)."' AND '".cek($end)."'" : "";
    $rawPaid = db_select("SELECT p.`receiver_id`,p.`amount`,p.`paid_at`,m.`mem_nama`,m.`mem_datalain` FROM `epi_commission_payout` p LEFT JOIN `sa_member` m ON m.`mem_id`=p.`receiver_id` WHERE p.`status`='paid'".$sqlType.$whereMember.$periodFilter." ORDER BY p.`paid_at` DESC");
    if ($rawPaid === false) { echo '<div class="alert alert-warning">Gagal memuat data paid. Silakan muat ulang halaman.</div>'; $rawPaid = array(); }
    // Ambil seluruh riwayat paid (tanpa filter periode) untuk menentukan status terakhir yang valid
    $rawPaidAll = db_select("SELECT p.`receiver_id`,p.`paid_at` FROM `epi_commission_payout` p LEFT JOIN `sa_member` m ON m.`mem_id`=p.`receiver_id` WHERE p.`status`='paid'".$sqlType.$whereMember." ORDER BY p.`paid_at` DESC");
    if ($rawPaidAll === false) { $rawPaidAll = array(); }
    $group = array();
    $pph = isset($settings['pph21_percent']) ? (float)$settings['pph21_percent'] : 0.0; if ($pph<0) { $pph=0.0; } if ($pph>100) { $pph=100.0; }
    $lastPendingTsAll = array();
    $paidInPeriod = array();
    if (is_array($rawPending)) {
        foreach ($rawPending as $row) {
            $rid = (int)$row['receiver_id'];
            if (!isset($group[$rid])) { $group[$rid] = array('receiver_id'=>$rid,'mem_nama'=>$row['mem_nama'],'mem_datalain'=>$row['mem_datalain'],'total_pending_net'=>0,'total_pending_gross'=>0,'total_paid'=>0,'last_ts'=>$row['created_at']); }
            $amt = (int)$row['amount'];
            $taxAmt = (int)round($amt * ($pph/100.0));
            $netAmt = max(0, $amt - $taxAmt);
            $group[$rid]['total_pending_gross'] += $amt;
            $group[$rid]['total_pending_net'] += $netAmt;
            if (strtotime($row['created_at']) > strtotime($group[$rid]['last_ts'])) { $group[$rid]['last_ts'] = $row['created_at']; }
            if (!isset($lastPendingTsAll[$rid]) || strtotime($row['created_at']) > strtotime($lastPendingTsAll[$rid])) { $lastPendingTsAll[$rid] = $row['created_at']; }
        }
    }
    $lastPaidTsAll = array();
    if (is_array($rawPaidAll)) {
        foreach ($rawPaidAll as $row) {
            $rid = (int)$row['receiver_id'];
            $ts = $row['paid_at'];
            if (!isset($lastPaidTsAll[$rid]) || strtotime($ts) > strtotime($lastPaidTsAll[$rid])) { $lastPaidTsAll[$rid] = $ts; }
        }
    }
    if (is_array($rawPaid)) {
        foreach ($rawPaid as $row) {
            $rid = (int)$row['receiver_id'];
            if (!isset($group[$rid])) { $group[$rid] = array('receiver_id'=>$rid,'mem_nama'=>$row['mem_nama'],'mem_datalain'=>$row['mem_datalain'],'total_pending_net'=>0,'total_pending_gross'=>0,'total_paid'=>0,'last_ts'=>$row['paid_at']); }
            $group[$rid]['total_paid'] += (int)$row['amount'];
            $ts = !empty($row['paid_at']) ? $row['paid_at'] : $group[$rid]['last_ts'];
            if (strtotime($ts) > strtotime($group[$rid]['last_ts'])) { $group[$rid]['last_ts'] = $ts; }
            $paidInPeriod[$rid] = true;
        }
    }
    // Tentukan status terakhir per receiver
    $lastStatusByRid = array();
    foreach ($group as $rid => $g) {
        $lp = isset($lastPendingTsAll[$rid]) ? $lastPendingTsAll[$rid] : '';
        $lq = isset($lastPaidTsAll[$rid]) ? $lastPaidTsAll[$rid] : '';
        if ($lq !== '' && ($lp === '' || strtotime($lq) >= strtotime($lp))) {
            $lastStatusByRid[$rid] = 'paid';
        } else {
            $lastStatusByRid[$rid] = 'pending';
        }
    }
    $data = array_values($group);
    if ($status === 'paid') {
        $data = array_values(array_filter($data, function($row) use ($lastStatusByRid, $paidInPeriod){
            $rid = (int)$row['receiver_id'];
            return (isset($lastStatusByRid[$rid]) && $lastStatusByRid[$rid]==='paid' && isset($paidInPeriod[$rid]));
        }));
    } else {
        $data = array_values(array_filter($data, function($row){
            return (int)($row['total_pending_net'] ?? 0) > 0;
        }));
    }
    $exportCount = count($data);
    $isFiltered = (!empty($_GET['cari']) || ($status==='paid' && !empty($start) && !empty($end)));

    // Ekspor data CSV/XLSX (Nama Member, Detil Rekening, Total Proses Bayar)
    if (isset($_GET['export']) && in_array($_GET['export'], array('csv','xlsx'))) {
        $rowsExport = array();
        $rowsExport[] = array('Nama Member','Detil Rekening','Total Proses Bayar');
        if (count($data) > 0) {
            foreach ($data as $row) {
                $datalainExp = extractdata(array('mem_datalain'=>$row['mem_datalain']));
                $name = trim((string)($row['mem_nama'] ?? ''));
                $rek  = trim((string)($datalainExp['rekening'] ?? ''));
                $amt  = ($status==='paid') ? (float)($row['total_paid'] ?? 0) : (float)($row['total_pending_net'] ?? 0);
                if ($name==='') { $name = 'N/A'; }
                if ($rek==='')  { $rek  = 'N/A'; }
                $rowsExport[] = array($name, $rek, number_format($amt, 2, '.', ''));
            }
        }
        // Footer sesuai spesifikasi
        $todayText = date('d-m-Y');
        $rowsExport[] = array('', '', '');
        $rowsExport[] = array('Tanggal Pengajuan Pencairan', $todayText, '');
        $rowsExport[] = array('', '', '');
        $rowsExport[] = array('Bima Galang Buana', '', '');
        $rowsExport[] = array('', '', '');
        $rowsExport[] = array('Sales & Marketing Team', '', '');

        // Penamaan file
        $fnameBase = 'Laporan_Pengajuan_Pembayaran_'.date('d-m-Y');
        if (function_exists('ob_get_level') && ob_get_level() > 0) { @ob_clean(); }
        @ini_set('display_errors', '0');

        if ($_GET['export'] === 'csv') {
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="'.$fnameBase.'.csv"');
            header('Cache-Control: no-store, max-age=0');
            $out = fopen('php://output','w');
            foreach ($rowsExport as $r) { fputcsv($out, $r); }
            fclose($out); exit;
        } else {
            // XLSX via SimpleXLSXGen jika tersedia, fallback CSV
            $xlsxOk = false;
            $clazzSimple = 'SimpleXLSXGen';
            $clazzNs = '\\Shuchkin\\XLSXGen';
            @include_once dirname(__DIR__,2).DIRECTORY_SEPARATOR.'xlsxgen.php';
            if (class_exists($clazzSimple)) {
                $xlsx = $clazzSimple::fromArray($rowsExport);
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment; filename="'.$fnameBase.'.xlsx"');
                $xlsx->downloadAs($fnameBase.'.xlsx');
                $xlsxOk = true; exit;
            } elseif (class_exists($clazzNs)) {
                $xlsx = $clazzNs::fromArray($rowsExport);
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment; filename="'.$fnameBase.'.xlsx"');
                $xlsx->saveAs('php://output');
                $xlsxOk = true; exit;
            }
            if (!$xlsxOk) {
                header('Content-Type: text/csv; charset=UTF-8');
                header('Content-Disposition: attachment; filename="'.$fnameBase.'.csv"');
                $out = fopen('php://output','w');
                foreach ($rowsExport as $r) { fputcsv($out, $r); }
                fclose($out); exit;
            }
        }
    }
	
    $periodInputs = ($status==='paid' ? '<div class="col-md-4"><label class="form-label">Periode Mulai</label><input type="date" name="start" class="form-control" value="'.htmlspecialchars($start).'" /></div><div class="col-md-4"><label class="form-label">Periode Selesai</label><input type="date" name="end" class="form-control" value="'.htmlspecialchars($end).'" /></div>' : '');
    echo '
    <form action="" method="get">
    <div class="card mb-3">
        <div class="card-body">
          <div class="row g-2 align-items-end">        
            <div class="col-md-4">
              <label class="form-label">Tipe Komisi</label>
              <select name="tipe" class="form-select">
                <option value="sponsor"'.($tipe==='sponsor'?' selected':'').'>Komisi Pereferral</option>
                <option value="kontributor"'.($tipe==='kontributor'?' selected':'').'>Komisi Kontributor</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Status Pembayaran</label>
              <select name="status" class="form-select">
                <option value="pending"'.($status==='pending'?' selected':'').'>Pending</option>
                <option value="paid"'.($status==='paid'?' selected':'').'>Sudah Transfer</option>
              </select>
            </div>
            '.$periodInputs.'
            <div class="col-md-4">
              <button type="submit" class="btn-apply" aria-label="Terapkan filter">Terapkan</button>
            </div>
          </div>
          <div class="mt-3 d-flex align-items-center gap-3">
            <div class="d-flex gap-2">
              <a class="btn-export" data-export-count="'.$exportCount.'" data-export-mode="'.($isFiltered?'filtered':'all').'" href="?tipe='.urlencode($tipe).'&status='.urlencode($status).'&start='.urlencode($start).'&end='.urlencode($end).'&cari='.urlencode(isset($_GET['cari'])?$_GET['cari']:'').'&export=xlsx" aria-label="Export Excel">Export Excel</a>
              <a class="btn-export secondary" data-export-count="'.$exportCount.'" data-export-mode="'.($isFiltered?'filtered':'all').'" href="?tipe='.urlencode($tipe).'&status='.urlencode($status).'&start='.urlencode($start).'&end='.urlencode($end).'&cari='.urlencode(isset($_GET['cari'])?$_GET['cari']:'').'&export=csv" aria-label="Export CSV">Export CSV</a>
            </div>
            <span class="export-indicator'.($isFiltered?' filtered':'').'">'.($isFiltered?('Export data terfilter: '.$exportCount.' record'):('Export seluruh data: '.$exportCount.' record')).'</span>
          </div>
        </div>
    </div>
    </form>
    ';
    $thead = ($status==='paid') ? '<tr><th class="text-center">Nama</th><th class="d-none d-sm-table-cell text-center">Rekening</th><th class="text-center">Total Pending</th><th class="text-center">Total Paid</th><th class="text-center">Aksi</th><th class="text-center">Status Terakhir</th></tr>' : '<tr><th class="text-center">Nama</th><th class="d-none d-sm-table-cell text-center">Rekening</th><th class="text-center">Total Pending</th><th class="text-center">Total Paid</th><th class="text-center">Proses Bayar</th><th class="text-center">Status Terakhir</th></tr>';
    echo '<div class="table-freeze-container"><table class="table table-hover table-bordered mb-0 align-middle"><thead class="table-secondary">'.$thead.'</thead><tbody>';
    if (count($data) > 0) {
        foreach ($data as $row) {
            $datalain = extractdata(array('mem_datalain'=>$row['mem_datalain']));
            $rek = $datalain['rekening'] ?? '';
            $defaultPay = (int)$row['total_pending_net'];
            $lp = db_row("SELECT `paid_at` FROM `epi_commission_payout` WHERE `receiver_id`=".(int)$row['receiver_id']." AND `status`='paid' ORDER BY `paid_at` DESC LIMIT 1");
            $paidAt = isset($lp['paid_at']) && !empty($lp['paid_at']) ? date('d/m/Y H:i:s', strtotime($lp['paid_at'])) : '';
            echo '
        <tr>
            <td>
                <a href="?detil='.$row['receiver_id'].'" target="_blank">'.$row['mem_nama'].'</a>
                <span class="d-sm-none">
                    <br/>';
            $ktp = '';
            $ktp = isset($datalain['fotoktp']) ? $datalain['fotoktp'] : $ktp;
            if (!empty($ktp)) {
              $ktp = trim($ktp);
              if (!preg_match('/^https?:\/\//i', $ktp)) {
                if (!preg_match('/^upload\//', $ktp)) { $ktp = 'upload/'.$ktp; }
                $ktp = $weburl.ltrim($ktp,'/');
              }
            }
            echo $rek.'<div class="mt-1 d-flex gap-2"><button type="button" class="btn btn-ktp btn-outline-secondary" '.(empty($ktp)?'disabled aria-disabled="true"':'data-ktp="'.htmlspecialchars($ktp).'"').' >Lihat KTP</button><button type="button" class="btn btn-ktp btn-outline-secondary btn-upload-ktp" data-receiver="'.(int)$row['receiver_id'].'" '.(!empty($ktp)?'disabled aria-disabled="true"':'').' >Upload KTP</button></div>';
            echo '
                </span>
            </td>
            <td class="d-none d-sm-table-cell">';
            echo $rek;
            echo '<div class="mt-1 d-flex gap-2"><button type="button" class="btn btn-ktp btn-outline-secondary" '.(empty($ktp)?'disabled aria-disabled="true"':'data-ktp="'.htmlspecialchars($ktp).'"').' >Lihat KTP</button><button type="button" class="btn btn-ktp btn-outline-secondary btn-upload-ktp" data-receiver="'.(int)$row['receiver_id'].'" '.(!empty($ktp)?'disabled aria-disabled="true"':'').' >Upload KTP</button></div>';
            echo '</td>
            <td class="text-end">'.number_format((int)($row['total_pending_net'] ?? 0)).'</td>
            <td class="text-end">'.number_format($row['total_paid']).'</td>
            '.($status==='paid'
            ? '<td>
              <form action="" method="post" class="cancel-payout-form d-flex flex-wrap gap-2 align-items-center">
                <input type="hidden" name="action" value="cancel_payout" />
                <input type="hidden" name="tipe" value="'.($tipe==='kontributor'?'kontributor':'sponsor').'" />
                <input type="hidden" name="receiver_id" value="'.$row['receiver_id'].'" />
                <input type="hidden" name="batch_ts" value="'.(isset($lp['paid_at']) ? htmlspecialchars($lp['paid_at']) : '').'" />
                <input type="text" name="alasan" class="form-control form-control-sm" placeholder="Alasan pembatalan (opsional)" aria-label="Alasan pembatalan (opsional)" />
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" value="1" id="confirm_once_'.$row['receiver_id'].'" name="confirm_once" required />
                  <label class="form-check-label" for="confirm_once_'.$row['receiver_id'].'">Saya mengerti pembatalan hanya satu kali</label>
                </div>
                <button type="submit" class="btn btn-ktp btn-outline-secondary">Batal Bayar</button>
                <small class="text-muted">Membatalkan pembayaran terakhir</small>
              </form>
            </td>'
            : '<td>
              <form action="" method="post" class="d-flex flex-wrap gap-2 align-items-center">
                <input type="hidden" name="tipe" value="'.($tipe==='kontributor'?'kontributor':'sponsor').'" />
                <input type="hidden" name="idsponsor" value="'.$row['receiver_id'].'" />
                <input type="number" name="cair" min="'.$defaultPay.'" max="'.$defaultPay.'" value="'.$defaultPay.'" class="form-control form-control-sm" placeholder="Nominal" required readonly aria-label="Jumlah harus sama dengan pending" />
                <small class="text-muted">Tersedia: '.number_format($defaultPay).'</small>
                <button type="submit" class="btn btn-ktp btn-outline-secondary">Proses Bayar</button>
                <button type="button" class="btn btn-ktp btn-outline-secondary btn-reject-req" data-receiver="'.$row['receiver_id'].'" data-type="'.($tipe==='kontributor'?'contrib':'sponsor').'">Tolak Pengajuan</button>
              </form>
            </td>').' 
            <td>'.(!empty($paidAt)?('<span class="badge bg-success badge-mini">Sudah Transfer</span><br/><small class="text-muted">'.$paidAt.'</small>'):'').'</td>
        </tr>
            ';
        }   
    }
    if (count($data) === 0) { echo '<tr><td colspan="6"><div class="alert alert-info">Tidak ada data untuk filter ini. Coba ubah filter atau periode.</div></td></tr>'; }
    echo '
	</tbody>
	</table>
    <small>Untuk memunculkan rekening, <a href="'.$weburl.'dashboard/form?edit=new">tambah setting form</a> dengan setting:<br/>
    - Field : <code>Custom Field</code><br/>
    - Custom Field: <code>rekening</code>'.$infominkomisi.'
    </small>
    </div>';
?>
    <style>
    .btn-ktp{ color:#D4AF37; border-color:#D4AF37; padding:.125rem .5rem; font-size:.875rem; }
    .badge-mini{ padding:.15rem .35rem; font-size:.75rem; }
    .btn-ktp:hover{ text-decoration:underline; filter:brightness(0.95); }
    .ktp-modal{ position:fixed; inset:0; display:none; align-items:center; justify-content:center; z-index:1050; opacity:0; }
    .ktp-modal.show{ display:flex; opacity:1; animation:fadein .2s ease-in; }
    .ktp-modal.closing{ display:flex; opacity:0; animation:fadeout .2s ease-out; }
    .ktp-backdrop{ position:absolute; inset:0; background:rgba(0,0,0,.5); }
    .ktp-dialog{ position:relative; background:#fff; border-radius:.5rem; box-shadow:0 10px 30px rgba(0,0,0,.2); max-width:80vw; max-height:80vh; overflow:auto; }
    .ktp-close{ position:absolute; top:.5rem; right:.5rem; border:none; background:transparent; font-size:1.25rem; line-height:1; cursor:pointer; }
    .ktp-content{ padding:1rem; min-width:280px; min-height:180px; }
    .ktp-loading{ display:flex; align-items:center; justify-content:center; height:120px; }
    .ktp-error{ color:#dc3545; }
    #ktpImage{ max-width:1000px; max-height:1000px; width:100%; height:auto; display:none; }
    .modal-open-ktp{ overflow:hidden !important; height:100%; }
    @keyframes fadein{ from{opacity:0} to{opacity:1} }
    @keyframes fadeout{ from{opacity:1} to{opacity:0} }
    .btn-export{ display:inline-block; padding:.5rem .9rem; border:none; border-radius:.5rem; background:#D4AF37; color:#0B0B0B; text-decoration:none; font-weight:600; box-shadow:0 .3rem 0 #b18c2c, 0 .3rem .6rem rgba(0,0,0,.25); transform:translateY(0); transition:transform .1s ease, box-shadow .1s ease, filter .15s ease; }
    .btn-export:hover{ filter:brightness(.97); }
    .btn-export:active{ transform:translateY(.25rem); box-shadow:0 .05rem 0 #b18c2c, 0 .1rem .3rem rgba(0,0,0,.25); }
    .btn-export.secondary{ background:#0B0B0B; color:#F8F8F8; box-shadow:0 .3rem 0 #000000, 0 .3rem .6rem rgba(0,0,0,.35); }
    .btn-export.secondary:hover{ filter:brightness(1.05); }
    .btn-export:focus-visible{ outline:2px solid #0B0B0B; outline-offset:2px; }
    .btn-apply{ display:inline-block; padding:.5rem .9rem; border:none; border-radius:.5rem; background:#D4AF37; color:#0B0B0B; text-decoration:none; font-weight:600; box-shadow:0 .3rem 0 #b18c2c, 0 .3rem .6rem rgba(0,0,0,.25); transform:translateY(0); transition:transform .1s ease, box-shadow .1s ease, filter .15s ease; }
    .btn-apply:hover{ filter:brightness(.97); }
    .btn-apply:active{ transform:translateY(.25rem); box-shadow:0 .05rem 0 #b18c2c, 0 .1rem .3rem rgba(0,0,0,.25); }
    .btn-apply:focus-visible{ outline:2px solid #0B0B0B; outline-offset:2px; }
    .export-indicator{ font-size:.85rem; padding:.2rem .5rem; border-radius:.35rem; background:#F8F8F8; color:#0B0B0B; border:1px solid #ddd; }
    .export-indicator.filtered{ background:#fff3cd; color:#664d03; border-color:#ffecb5; }
    </style>
    <div id="ktpModal" class="ktp-modal" aria-hidden="true">
      <div class="ktp-backdrop"></div>
      <div class="ktp-dialog" role="dialog" aria-modal="true" aria-labelledby="ktpTitle">
        <button type="button" class="ktp-close" aria-label="Close">&#215;</button>
        <div id="ktpContent" class="ktp-content">
          <div class="ktp-loading" id="ktpLoading">Loading...</div>
          <img id="ktpImage" alt="Foto KTP" />
          <div id="ktpError" class="ktp-error d-none">File KTP tidak ditemukan</div>
        </div>
      </div>
    </div>
    <div id="ktpUploadModal" class="ktp-modal" aria-hidden="true">
      <div class="ktp-backdrop"></div>
      <div class="ktp-dialog" role="dialog" aria-modal="true" aria-labelledby="ktpUploadTitle">
        <button type="button" class="ktp-close" aria-label="Close">&#215;</button>
        <div class="ktp-content">
          <h5 id="ktpUploadTitle" class="mb-2">Upload KTP</h5>
          <div class="mb-2">
            <input type="file" id="ktpFileInput" accept=".jpg,.jpeg,.png,.pdf" class="form-control" aria-label="Pilih file KTP (JPG/PNG/PDF, maks 2MB)">
            <div class="form-text">Format: JPG/PNG/PDF, ukuran maksimal 2MB</div>
          </div>
          <div class="mb-2" id="ktpUploadPreviewWrap" style="display:none;">
            <img id="ktpUploadPreview" alt="Preview KTP" style="max-width:100%;height:auto;" />
          </div>
          <div class="d-flex align-items-center gap-2">
            <button type="button" id="ktpUploadSubmit" class="btn btn-ktp btn-outline-secondary"><span class="spinner-border spinner-border-sm d-none" id="ktpUploadSpin"></span> Simpan</button>
            <button type="button" id="ktpUploadCancel" class="btn btn-ktp btn-outline-secondary">Batal</button>
          </div>
          <div class="progress mt-2" style="height:8px;">
            <div id="ktpUploadProgress" class="progress-bar" role="progressbar" style="width:0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
          </div>
          <div id="ktpUploadStatus" class="mt-2" aria-live="polite"></div>
        </div>
      </div>
    </div>
    <div id="rejectModal" class="ktp-modal" aria-hidden="true">
      <div class="ktp-backdrop"></div>
      <div class="ktp-dialog" role="dialog" aria-modal="true" aria-labelledby="rejectTitle">
        <button type="button" class="ktp-close" aria-label="Close">&#215;</button>
        <div class="ktp-content">
          <h5 id="rejectTitle" class="mb-2">Tolak Pengajuan Pencairan</h5>
          <div class="mb-2">
            <label for="rejectReason" class="form-label">Alasan pembatalan (wajib)</label>
            <textarea id="rejectReason" class="form-control" rows="3" placeholder="Tulis alasan..."></textarea>
            <div class="form-text">Alasan wajib diisi untuk pencatatan sistem.</div>
          </div>
          <div class="d-flex align-items-center gap-2">
            <button type="button" id="rejectSubmit" class="btn btn-ktp btn-outline-secondary">Tolak Pengajuan</button>
            <button type="button" id="rejectCancel" class="btn btn-ktp btn-outline-secondary">Kembali</button>
          </div>
          <div id="rejectStatus" class="mt-2" aria-live="polite"></div>
        </div>
      </div>
    </div>
    <script>
    (function(){
      var modal=document.getElementById('ktpModal');
      var img=document.getElementById('ktpImage');
      var loading=document.getElementById('ktpLoading');
      var err=document.getElementById('ktpError');
      // Business logic: show Upload KTP button only if KTP not available or view button disabled
      // Access: page already restricted to roles 6/9; server endpoint re-validates role
      function open(url){ err.classList.add('d-none'); img.style.display='none'; loading.style.display='flex'; modal.classList.remove('closing'); modal.classList.add('show'); document.body.classList.add('modal-open-ktp'); img.src=''; setTimeout(function(){ img.src=url; },0); document.querySelector('.ktp-close').focus(); }
      function close(){ modal.classList.add('closing'); setTimeout(function(){ modal.classList.remove('show'); modal.classList.remove('closing'); img.src=''; document.body.classList.remove('modal-open-ktp'); }, 200); }
      document.addEventListener('click', function(e){ var b=e.target.closest('.btn-ktp'); if(!b){ return; } var url=b.getAttribute('data-ktp'); if(!url){ return; } e.preventDefault(); var isImg=/\.(jpg|jpeg|png)(\?.*)?$/i.test(url); var isPdf=/\.pdf(\?.*)?$/i.test(url); if(isImg){ open(url); } else if(isPdf){ window.open(url, '_blank'); } else { err.textContent='Data KTP tidak valid'; err.classList.remove('d-none'); } });
      modal.addEventListener('click', function(e){ if(e.target.classList.contains('ktp-backdrop')){ close(); } });
      document.querySelector('.ktp-close').addEventListener('click', function(){ close(); });
      document.addEventListener('keydown', function(e){ if(e.key==='Escape'){ close(); } });
      img.addEventListener('load', function(){ loading.style.display='none'; img.style.display='block'; });
      img.addEventListener('error', function(){ loading.style.display='none'; err.classList.remove('d-none'); });
      var payForms=document.querySelectorAll('form.d-flex');
      payForms.forEach(function(f){ f.addEventListener('submit', function(e){ var inp=f.querySelector('input[name="cair"]'); if(!inp){ return; } var mv=inp.getAttribute('max'); var vv=inp.value; if(String(mv)!==String(vv)){ e.preventDefault(); alert('Nominal harus sama dengan pending bersih ('+mv+').'); } }); });
      var cancelForms=document.querySelectorAll('form.cancel-payout-form');
      cancelForms.forEach(function(f){ f.addEventListener('submit', function(e){
        if(!navigator.onLine){ e.preventDefault(); alert('Koneksi terputus. Periksa jaringan Anda, lalu coba lagi.'); return; }
        var once = f.querySelector('input[name="confirm_once"]');
        if(!once || !once.checked){ e.preventDefault(); alert('Anda hanya dapat melakukan pembatalan satu kali untuk transaksi ini'); return; }
        var ok1 = confirm('Peringatan: Anda hanya dapat melakukan pembatalan satu kali untuk transaksi ini.');
        if(!ok1){ e.preventDefault(); return; }
        var ok2 = confirm('Yakin membatalkan pembayaran terakhir?\nTindakan ini akan mengembalikan status ke Pending.');
        if(!ok2){ e.preventDefault(); }
      }); });
      document.addEventListener('click', function(e){
        var link = e.target.closest('.btn-export');
        if(!link){ return; }
        var cnt = parseInt(link.getAttribute('data-export-count')||'0',10);
        var mode = (link.getAttribute('data-export-mode')||'all').toLowerCase();
        var msg = (mode==='filtered') ? ('Export data terfilter ('+cnt+' record). Lanjutkan?') : ('Export seluruh data ('+cnt+' record). Lanjutkan?');
        if(!confirm(msg)){ e.preventDefault(); }
      });

      // Upload KTP modal logic
      var uploadModal=document.getElementById('ktpUploadModal');
      var uploadClose=uploadModal.querySelector('.ktp-close');
      var fileInput=document.getElementById('ktpFileInput');
      var previewImg=document.getElementById('ktpUploadPreview');
      var previewWrap=document.getElementById('ktpUploadPreviewWrap');
      var uploadBtn=document.getElementById('ktpUploadSubmit');
      var cancelBtn=document.getElementById('ktpUploadCancel');
      var statusBox=document.getElementById('ktpUploadStatus');
      var spin=document.getElementById('ktpUploadSpin');
      var currentReceiver=null; // state
      var currentType=null; // state
      function openUpload(receiver){ currentReceiver=receiver; statusBox.textContent=''; previewWrap.style.display='none'; previewImg.src=''; fileInput.value=''; spin.classList.add('d-none'); uploadModal.classList.remove('closing'); uploadModal.classList.add('show'); document.body.classList.add('modal-open-ktp'); fileInput.focus(); }
      function closeUpload(){ uploadModal.classList.add('closing'); setTimeout(function(){ uploadModal.classList.remove('show'); uploadModal.classList.remove('closing'); document.body.classList.remove('modal-open-ktp'); currentReceiver=null; },200); }
      document.addEventListener('click', function(e){ var btn=e.target.closest('.btn-upload-ktp'); if(!btn){ return; } e.preventDefault(); if(btn.hasAttribute('disabled')){ return; } var rid=btn.getAttribute('data-receiver'); if(!rid){ return; } openUpload(rid); });
      // Reject request modal logic
      var rejectModal=document.getElementById('rejectModal');
      var rejectClose=rejectModal.querySelector('.ktp-close');
      var rejectReason=document.getElementById('rejectReason');
      var rejectSubmit=document.getElementById('rejectSubmit');
      var rejectCancel=document.getElementById('rejectCancel');
      var rejectStatus=document.getElementById('rejectStatus');
      function openReject(receiver,type){ currentReceiver=receiver; currentType=type; rejectStatus.textContent=''; rejectReason.value=''; rejectModal.classList.remove('closing'); rejectModal.classList.add('show'); document.body.classList.add('modal-open-ktp'); rejectReason.focus(); }
      function closeReject(){ rejectModal.classList.add('closing'); setTimeout(function(){ rejectModal.classList.remove('show'); rejectModal.classList.remove('closing'); document.body.classList.remove('modal-open-ktp'); currentReceiver=null; currentType=null; },200); }
      document.addEventListener('click', function(e){ var btn=e.target.closest('.btn-reject-req'); if(!btn){ return; } e.preventDefault(); var rid=btn.getAttribute('data-receiver'); var typ=btn.getAttribute('data-type'); if(!rid || !typ){ return; } openReject(rid, typ); });
      rejectModal.addEventListener('click', function(e){ if(e.target.classList.contains('ktp-backdrop')){ closeReject(); } });
      rejectClose.addEventListener('click', function(){ closeReject(); });
      rejectCancel.addEventListener('click', function(){ closeReject(); });
      rejectSubmit.addEventListener('click', function(){ var reason=(rejectReason.value||'').trim(); if(!currentReceiver || !currentType){ rejectStatus.textContent='Target transaksi tidak valid.'; return; } if(reason===''){ rejectStatus.textContent='Alasan pembatalan wajib diisi.'; return; } rejectSubmit.setAttribute('disabled','disabled'); rejectStatus.textContent='Mengirim pembatalan...'; var fd=new FormData(); fd.append('receiver_id', String(currentReceiver)); fd.append('type', String(currentType)); fd.append('reason', reason); fd.append('ts', String(Date.now())); var xhr=new XMLHttpRequest(); xhr.open('POST', '<?=$weburl?>api/cancel-payout-request.php', true); xhr.onreadystatechange=function(){ if(xhr.readyState===4){ try{ var j=JSON.parse(xhr.responseText||'{}'); if(j && j.ok){ rejectStatus.textContent='Pengajuan berhasil ditolak.'; closeReject(); setTimeout(function(){ window.location.reload(); }, 400); } else { rejectStatus.textContent=(j && j.message)?j.message:'Gagal menolak pengajuan.'; } } catch(e){ rejectStatus.textContent='Respons tidak valid.'; } rejectSubmit.removeAttribute('disabled'); } }; xhr.onerror=function(){ rejectStatus.textContent='Koneksi gagal. Coba lagi.'; rejectSubmit.removeAttribute('disabled'); }; xhr.send(fd); });
      uploadModal.addEventListener('click', function(e){ if(e.target.classList.contains('ktp-backdrop')){ closeUpload(); } });
      uploadClose.addEventListener('click', function(){ closeUpload(); });
      cancelBtn.addEventListener('click', function(){ closeUpload(); });
      fileInput.addEventListener('change', function(){ var f=fileInput.files && fileInput.files[0]; if(!f){ statusBox.textContent=''; previewWrap.style.display='none'; return; } var ext=(f.name||'').split('.').pop().toLowerCase(); var validExt=['jpg','jpeg','png','pdf']; var validType=(f.type||'').toLowerCase(); var isImg=(validExt.indexOf(ext)>=0) && (validType.indexOf('image/')===0) && ext!=='pdf'; var isPdf=(ext==='pdf' || validType==='application/pdf'); var sizeOk=f.size<= (2*1024*1024); if(!(isImg||isPdf)){ statusBox.textContent='Format tidak didukung. Gunakan JPG/PNG/PDF.'; previewWrap.style.display='none'; return; } if(!sizeOk){ statusBox.textContent='Ukuran file melebihi 2MB.'; previewWrap.style.display='none'; return; } if(isImg){ var reader=new FileReader(); reader.onload=function(ev){ previewImg.src=ev.target.result; previewWrap.style.display='block'; }; reader.readAsDataURL(f); statusBox.textContent=''; } else { previewWrap.style.display='none'; statusBox.textContent='File PDF dipilih. Preview tidak tersedia.'; } });
      uploadBtn.addEventListener('click', function(){ var f=fileInput.files && fileInput.files[0]; if(!currentReceiver){ statusBox.textContent='Target member tidak valid.'; return; } if(!f){ statusBox.textContent='Pilih file KTP terlebih dahulu.'; return; } var ext=(f.name||'').split('.').pop().toLowerCase(); var validExt=['jpg','jpeg','png','pdf']; var validType=(f.type||'').toLowerCase(); var isImg=(validExt.indexOf(ext)>=0) && (validType.indexOf('image/')===0) && ext!=='pdf'; var isPdf=(ext==='pdf' || validType==='application/pdf'); var sizeOk=f.size<= (2*1024*1024); if(!(isImg||isPdf)){ statusBox.textContent='Format tidak didukung. Gunakan JPG/PNG/PDF.'; return; } if(!sizeOk){ statusBox.textContent='Ukuran file melebihi 2MB.'; return; } if(!confirm('Konfirmasi upload KTP?')){ return; } uploadBtn.setAttribute('disabled','disabled'); spin.classList.remove('d-none'); statusBox.textContent='Mengunggah...'; var fd=new FormData(); fd.append('file', f); fd.append('member_id', String(currentReceiver)); fd.append('ajax','1'); var prog=document.getElementById('ktpUploadProgress'); prog.style.width='0%'; prog.setAttribute('aria-valuenow','0');
        var xhr=new XMLHttpRequest();
        xhr.open('POST', '<?=$weburl?>upload_ktp.php', true);
        xhr.upload.onprogress=function(e){ if(e && e.lengthComputable){ var p=Math.round((e.loaded/e.total)*100); prog.style.width=p+'%'; prog.setAttribute('aria-valuenow', String(p)); } };
        xhr.onreadystatechange=function(){ if(xhr.readyState===4){ try{ var j=JSON.parse(xhr.responseText||'{}'); if(j && j.ok){ statusBox.textContent='Berhasil diupload.'; var rows=document.querySelectorAll('tr'); var targetBtnView=null; var targetBtnUpload=null; rows.forEach(function(tr){ var up=tr.querySelector('.btn-upload-ktp[data-receiver="'+String(currentReceiver)+'"]'); if(up){ targetBtnUpload=up; var view=tr.querySelector('.btn-ktp[data-ktp]'); if(view){ targetBtnView=view; } } }); if(targetBtnView){ targetBtnView.setAttribute('data-ktp', j.url); targetBtnView.removeAttribute('disabled'); targetBtnView.removeAttribute('aria-disabled'); } if(targetBtnUpload){ targetBtnUpload.setAttribute('disabled','disabled'); targetBtnUpload.setAttribute('aria-disabled','true'); } closeUpload(); setTimeout(function(){ window.location.reload(); }, 300); } else { statusBox.textContent=(j && j.message)?j.message:'Gagal mengupload.'; } } catch(e){ statusBox.textContent='Respons tidak valid.'; } uploadBtn.removeAttribute('disabled'); spin.classList.add('d-none'); } };
        xhr.onerror=function(){ statusBox.textContent='Koneksi gagal. Coba lagi.'; uploadBtn.removeAttribute('disabled'); spin.classList.add('d-none'); };
        xhr.send(fd);
      });
    })();
    </script>
<?php
}
showfooter();
