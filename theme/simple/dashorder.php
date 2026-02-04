<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
if ($datamember['mem_role'] < 5) { die(); exit(); }
$weburl = isset($weburl) ? $weburl : (function_exists('weburl') ? call_user_func('weburl') : '/');
// Early export: ensure no HTML is sent before CSV/XLSX
if (isset($_GET['export'])) {
  $fmtRaw = strtolower(trim((string)$_GET['export']));
  $fmt = ($fmtRaw==='csv') ? 'csv' : 'xlsx';
  $where = '';
  $cari = isset($_GET['cari']) ? (string)$_GET['cari'] : '';
  if ($cari !== '') {
    $s = cek($cari);
    if (is_numeric($cari)) { $where = "WHERE `sa_order`.`order_id`=".(int)$cari; }
    else {
      $where = "WHERE (`sa_member`.`mem_nama` LIKE '%".$s."%' 
                      OR `sa_member`.`mem_email` LIKE '%".$s."%' 
                      OR `sa_member`.`mem_whatsapp` LIKE '%".$s."%' 
                      OR `sa_member`.`mem_datalain` LIKE '%".$s."%' 
                      OR `sa_member`.`mem_kodeaff` LIKE '%".$s."%'
                      OR `sa_page`.`page_judul` LIKE '%".$s."%' 
                      OR `sa_page`.`page_diskripsi` LIKE '%".$s."%' 
                      OR `sa_page`.`page_url` LIKE '%".$s."%')";
    }
  }
  if (isset($_GET['status']) && $_GET['status']!=='' && is_numeric($_GET['status'])) {
    $st = (int)$_GET['status'];
    $where .= ($where==''? 'WHERE ':' AND ')."`sa_order`.`order_status`=".$st;
  }
  $from = isset($_GET['from']) ? trim((string)$_GET['from']) : '';
  $to   = isset($_GET['to'])   ? trim((string)$_GET['to'])   : '';
  $fromValid = preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) ? $from : '';
  $toValid   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)   ? $to   : '';
  if ($fromValid !== '' || $toValid !== '') {
    if ($fromValid === '' && $toValid !== '') { $fromValid = $toValid; }
    if ($toValid === '' && $fromValid !== '') { $toValid = $fromValid; }
    $cond = " (`sa_order`.`order_tglorder` >= '".cek($fromValid)." 00:00:00' AND `sa_order`.`order_tglorder` <= '".cek($toValid)." 23:59:59') ";
    $where .= ($where==''? 'WHERE ':' AND ').$cond;
  }
  $rows = db_select("SELECT `sa_order`.`order_id`,`sa_order`.`order_tglorder`,`sa_order`.`order_hargaunik` AS `harga`,`sa_order`.`order_harga` AS `harga_normal`,`sa_order`.`order_trx`,`sa_order`.`order_status`,`sa_member`.`mem_nama`,`sa_page`.`page_judul` FROM `sa_order` LEFT JOIN `sa_member` ON `sa_member`.`mem_id`=`sa_order`.`order_idmember` LEFT JOIN `sa_page` ON `sa_page`.`page_id`=`sa_order`.`order_idproduk` ".$where." ORDER BY `sa_order`.`order_tglorder` DESC");
  if (!is_array($rows)) { $rows = array(); }
  $data = array();
  $data[] = array('ID Order','Tanggal Order','Nama','Produk','Harga','Gratis','Status');
  foreach ($rows as $r) {
    $id = (int)($r['order_id'] ?? 0);
    $tgl = (string)($r['order_tglorder'] ?? '');
    $tglFmt = '';
    if ($tgl !== '') { $ts = strtotime($tgl); $tglFmt = $ts ? date('d/m/Y', $ts) : $tgl; }
    $nama = (string)($r['mem_nama'] ?? '');
    $produk = (string)($r['page_judul'] ?? '');
    $harga = (int)($r['harga'] ?? 0);
    $hargaNormal = (int)($r['harga_normal'] ?? 0);
    $trx = (string)($r['order_trx'] ?? '');
    $isGratis = ($harga === 0) && ($trx === 'free' || $hargaNormal > 0);
    $statusTxt = ((int)($r['order_status'] ?? 0) === 1) ? 'Lunas' : 'Belum Lunas';
    $data[] = array($id,$tglFmt,$nama,$produk,$harga,($isGratis?'Gratis':''),$statusTxt);
  }
  if (function_exists('ob_get_level')) { while (ob_get_level() > 0) { @ob_end_clean(); } }
  @ini_set('display_errors','0');
  date_default_timezone_set('Asia/Jakarta');
  $fnameBase = 'OrderList_'.date('Ymd');
  if ($fmt === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="'.$fnameBase.'.csv"');
    header('Cache-Control: no-store, max-age=0');
    $out = fopen('php://output','w');
    fwrite($out, "\xEF\xBB\xBF");
    foreach ($data as $row) { fputcsv($out, $row); }
    fclose($out); exit;
  } else {
    $xlsxOk = false;
    @include_once dirname(__DIR__,1).DIRECTORY_SEPARATOR.'xlsxgen.php';
    if (class_exists('SimpleXLSXGen')) {
      $xlsx = SimpleXLSXGen::fromArray($data);
      header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
      header('Content-Disposition: attachment; filename="'.$fnameBase.'.xlsx"');
      $xlsx->downloadAs($fnameBase.'.xlsx');
      $xlsxOk = true; exit;
    } elseif (class_exists('\\Shuchkin\\XLSXGen')) {
      $clazz = '\\Shuchkin\\XLSXGen';
      $xlsx = call_user_func([$clazz,'fromArray'],$data);
      header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
      header('Content-Disposition: attachment; filename="'.$fnameBase.'.xlsx"');
      $xlsx->saveAs('php://output');
      $xlsxOk = true; exit;
    }
    if (!$xlsxOk) {
      header('Content-Type: text/csv; charset=UTF-8');
      header('Content-Disposition: attachment; filename="'.$fnameBase.'.csv"');
      $out = fopen('php://output','w'); fwrite($out, "\xEF\xBB\xBF"); foreach ($data as $row) { fputcsv($out, $row); } fclose($out); exit;
    }
  }
}
$head['pagetitle']='Order List';
showheader($head);
// Ensure payment confirm tables exist to avoid fatal errors on fresh servers
if (!db_var("SHOW TABLES LIKE 'epi_payment_confirm'")) {
  db_query("CREATE TABLE IF NOT EXISTS `epi_payment_confirm` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `invoice_no` VARCHAR(32) NOT NULL,
    `atas_nama` VARCHAR(100) NULL,
    `bank_code` VARCHAR(32) NULL,
    `bank_label` VARCHAR(100) NULL,
    `bank_account` VARCHAR(50) NULL,
    `bank_owner` VARCHAR(100) NULL,
    `transfer_date` DATE NULL,
    `nominal` INT NULL,
    `nominal_expected` INT NULL,
    `file_path` VARCHAR(255) NULL,
    `file_name` VARCHAR(200) NULL,
    `file_size` INT NULL,
    `file_type` VARCHAR(64) NULL,
    `status` TINYINT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL,
    `created_ip` VARCHAR(64) NULL,
    `user_agent` VARCHAR(255) NULL,
    `verified_by` INT NULL,
    `verified_note` VARCHAR(255) NULL,
    INDEX `idx_order` (`order_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created` (`created_at`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
// Ensure critical columns exist when table was created by older code
if (!db_var("SHOW COLUMNS FROM `epi_payment_confirm` LIKE 'invoice_no'")) { db_query("ALTER TABLE `epi_payment_confirm` ADD `invoice_no` VARCHAR(32) NOT NULL AFTER `order_id`"); }
if (!db_var("SHOW COLUMNS FROM `epi_payment_confirm` LIKE 'atas_nama'")) { db_query("ALTER TABLE `epi_payment_confirm` ADD `atas_nama` VARCHAR(100) NULL"); }
if (!db_var("SHOW COLUMNS FROM `epi_payment_confirm` LIKE 'bank_code'")) { db_query("ALTER TABLE `epi_payment_confirm` ADD `bank_code` VARCHAR(32) NULL"); }
if (!db_var("SHOW COLUMNS FROM `epi_payment_confirm` LIKE 'nominal_expected'")) { db_query("ALTER TABLE `epi_payment_confirm` ADD `nominal_expected` INT NULL"); }
if (!db_var("SHOW COLUMNS FROM `epi_payment_confirm` LIKE 'file_name'")) { db_query("ALTER TABLE `epi_payment_confirm` ADD `file_name` VARCHAR(200) NULL"); }
if (!db_var("SHOW COLUMNS FROM `epi_payment_confirm` LIKE 'file_size'")) { db_query("ALTER TABLE `epi_payment_confirm` ADD `file_size` INT NULL"); }
if (!db_var("SHOW COLUMNS FROM `epi_payment_confirm` LIKE 'created_ip'")) { db_query("ALTER TABLE `epi_payment_confirm` ADD `created_ip` VARCHAR(64) NULL"); }
if (!db_var("SHOW COLUMNS FROM `epi_payment_confirm` LIKE 'user_agent'")) { db_query("ALTER TABLE `epi_payment_confirm` ADD `user_agent` VARCHAR(255) NULL"); }
if (!db_var("SHOW TABLES LIKE 'epi_payment_confirm_log'")) {
  db_query("CREATE TABLE IF NOT EXISTS `epi_payment_confirm_log` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `confirm_id` INT NULL,
    `order_id` INT NULL,
    `action` VARCHAR(64) NULL,
    `message` VARCHAR(255) NULL,
    `ip` VARCHAR(64) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_order` (`order_id`),
    INDEX `idx_confirm` (`confirm_id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

// Ensure performance index for date filtering
if (!db_var("SHOW INDEX FROM `sa_order` WHERE Key_name='idx_order_tglorder'")) {
  @db_query("ALTER TABLE `sa_order` ADD INDEX `idx_order_tglorder` (`order_tglorder`)");
}

if (isset($_GET['proses']) && is_numeric($_GET['proses'])) {
	$idinvoice = $_GET['proses'];
	$staff = $datamember['mem_id'];
	include('prosesorder.php');
	} elseif (isset($_GET['batal']) && is_numeric($_GET['batal']) && $_GET['batal'] > 0) {
		$proses = db_row("SELECT * FROM `sa_order`
				LEFT JOIN `sa_member` ON `sa_member`.`mem_id` = `sa_order`.`order_idmember`
				LEFT JOIN `sa_sponsor` ON `sa_sponsor`.`sp_mem_id`= `sa_order`.`order_idmember`
				LEFT JOIN `sa_page` ON `sa_page`.`page_id` = `sa_order`.`order_idproduk`
				WHERE `sa_order`.`order_status` = 1 AND `sa_order`.`order_id`=".$_GET['batal']);
		if (isset($proses['order_id'])) {
			$confirm = db_row("SELECT `id`,`status` FROM `epi_payment_confirm` WHERE `order_id`=".(int)$proses['order_id']." ORDER BY `id` DESC LIMIT 1");
			if ($confirm && isset($confirm['id']) && (int)$confirm['status'] === 0) {
				@db_query("INSERT INTO `epi_payment_confirm_log` (`confirm_id`,`order_id`,`action`,`message`,`ip`) VALUES (".(int)$confirm['id'].",".(int)$proses['order_id'].",'admin_set_unpaid_blocked','blocked: payment confirm pending','".cek(realIP())."')");
				echo '<div class="alert alert-warning alert-dismissible fade show" role="alert">
				  <strong>Perhatian!</strong> Invoice ini sedang <b>MENUNGGU KONFIRMASI</b> dari admin. Status tidak diubah ke BELUM LUNAS.
				  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
				</div>';
			} else {
				# Update data order
				db_query("UPDATE `sa_order` SET `order_status`=0,`order_idstaff`=".$datamember['mem_id'].",`order_tglbayar`=NULL WHERE `order_id`=".$proses['order_id']);
				@db_query("INSERT INTO `epi_payment_confirm_log` (`confirm_id`,`order_id`,`action`,`message`,`ip`) VALUES (".(isset($confirm['id'])?(int)$confirm['id']:0).",".(int)$proses['order_id'].",'admin_set_unpaid','admin: paid>unpaid','".cek(realIP())."')");
				db_query("DELETE FROM `sa_laporan` WHERE `lap_idorder`=".$_GET['batal']);
            $invUrl = rtrim($weburl,'/').'/invoice/'.(int)$proses['order_id'];
            $prodUrl = isset($proses['page_url']) ? (rtrim($weburl,'/').'/order/'.$proses['page_url']) : $invUrl;
            $datalain = array(
                'idorder' => (string)$proses['order_id'],
                'namaproduk' => (string)($proses['page_judul'] ?? ''),
                'urlproduk' => (string)$prodUrl,
                'halaman_invoice' => (string)$invUrl,
                'alasan' => ''
            );
            sa_notif('cancel_order', (int)$proses['order_idmember'], $datalain);
			echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
				  <strong>Ok!</strong> Order '.$proses['order_id'].' telah dibatalkan 🙏
				  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
				</div>';
			}
		} else {
			echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
			  <strong>Error!</strong> Order tidak ditemukan. Mungkin sudah dihapus atau sudah dibatalkan sebelumnya.
			  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
			</div>';
		}
} elseif (isset($_GET['del']) && is_numeric($_GET['del'])) {
    db_query("DELETE FROM `sa_order` WHERE `order_id`=".$_GET['del']);
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
			  <strong>Ok!</strong> Order ' . $_GET['del'] . ' telah dihapus 🙏
			  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
			</div>';
}
elseif (isset($_GET['verify']) && is_numeric($_GET['verify'])) {
    $oid = (int)$_GET['verify'];
    $status = isset($_GET['status']) ? (int)$_GET['status'] : 0;
    $note = isset($_GET['note']) ? trim($_GET['note']) : '';
    $confirm = db_row("SELECT * FROM `epi_payment_confirm` WHERE `order_id`=".$oid." ORDER BY `id` DESC LIMIT 1");
    if ($confirm && isset($confirm['id'])) {
        $ok = db_query("UPDATE `epi_payment_confirm` SET `status`=".$status.",`verified_by`=".(int)$datamember['mem_id'].",`verified_note`='".cek($note)."',`updated_at`='".date('Y-m-d H:i:s')."' WHERE `id`=".(int)$confirm['id']);
        if ($ok === false) {
            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert"><strong>Error!</strong> '.htmlspecialchars(db_error()).'<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        } else {
            db_query("INSERT INTO `epi_payment_confirm_log` (`confirm_id`,`order_id`,`action`,`message`,`ip`) VALUES (".(int)$confirm['id'].",".$oid.",'verify_".($status===1?'accept':'reject')."','".cek($note)."','".cek(realIP())."')");
            $mem = db_row("SELECT `sa_member`.`mem_whatsapp`,`sa_member`.`mem_email`,`sa_member`.`mem_nama` FROM `sa_order` LEFT JOIN `sa_member` ON `sa_member`.`mem_id`=`sa_order`.`order_idmember` WHERE `sa_order`.`order_id`=".$oid);
            if ($status === 1) { $idinvoice = $oid; $staff = $datamember['mem_id']; include('prosesorder.php'); }
            if ($mem && !empty($mem['mem_whatsapp'])) {
                $tpl = getsettings()['wa_verify_result_member'] ?? '';
                $statusText = ($status===1 ? 'diterima' : 'ditolak');
                $nextUrl = ($status===1 ? ($weburl.'invoice/'.$oid) : (rtrim($weburl,'/').'/konfirmasi/'.$oid));
                if (!empty($tpl)) {
                    $msgMember = (string)$tpl;
                    $msgMember = str_replace('[idorder]', (string)$oid, $msgMember);
                    $msgMember = str_replace('[status]', $statusText, $msgMember);
                    $msgMember = str_replace('[alasan]', (string)$note, $msgMember);
                    $msgMember = str_replace('[next_url]', $nextUrl, $msgMember);
                } else {
                    if ($status===1) { $msgMember = 'Pembayaran untuk #'.(string)$oid.' telah diterima. Akses selanjutnya: '.$nextUrl; }
                    else { $msgMember = 'Verifikasi pembayaran untuk #'.(string)$oid.' ditolak. Alasan: '.(string)$note.'. Perbaiki/unggah ulang: '.$nextUrl; }
                }
                @kirimwa($mem['mem_whatsapp'], $msgMember);
            }
            if ($status !== 1) {
                echo '<div class="alert alert-info alert-dismissible fade show" role="alert"><strong>Info!</strong> Verifikasi ditolak untuk order '.(int)$oid.' dengan alasan: '.htmlspecialchars($note).'<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
            }
        }
    } else {
        echo '<div class="alert alert-warning alert-dismissible fade show" role="alert"><strong>Perhatian!</strong> Tidak ada konfirmasi untuk order '.(int)$oid.'.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    }
}
?>
<form action="" method="get">
<div class="card mb-3">
	<div class="card-body">
	  <div class="row">	    
	    <div class="col">
            <div class="input-group order-filter">
                  <input type="text" class="form-control" name="cari" value="<?= $_GET['cari'] ??= '';?>" placeholder="Cari Nama/Email">
				  <?php 
				  $select = array('','','');
				  if (isset($_GET['status']) && is_numeric($_GET['status'])) {
				  	$select[$_GET['status']] = ' selected';
				  }
				  ?>
                  <select name="status" class="form-select">
                    	<option value="">All Order</option>
                    	<option value="0"<?=$select[0];?>>Belum Lunas</option>
                    	<option value="1"<?=$select[1];?>>Lunas</option>
                    	<option value="2"<?=$select[2] ?? '';?>>Dibatalkan</option>
                  </select>
                  <input type="date" class="form-control" name="from" value="<?= htmlspecialchars($_GET['from'] ?? '', ENT_QUOTES); ?>" title="Dari Tanggal">
                  <input type="date" class="form-control" name="to" value="<?= htmlspecialchars($_GET['to'] ?? '', ENT_QUOTES); ?>" title="Sampai Tanggal">
                  <input type="submit" value=" Filter " class="btn btn-secondary" id="btnFilter">
                  <a href="orderlist" class="btn btn-outline-secondary" id="btnReset">Reset</a>
                  <a href="orderlist?export=csv&amp;cari=<?= urlencode($_GET['cari'] ?? '') ?>&amp;status=<?= urlencode($_GET['status'] ?? '') ?>&amp;from=<?= urlencode($_GET['from'] ?? '') ?>&amp;to=<?= urlencode($_GET['to'] ?? '') ?>" class="btn btn-outline-primary">Export CSV</a>
                  <a href="orderlist?export=xlsx&amp;cari=<?= urlencode($_GET['cari'] ?? '') ?>&amp;status=<?= urlencode($_GET['status'] ?? '') ?>&amp;from=<?= urlencode($_GET['from'] ?? '') ?>&amp;to=<?= urlencode($_GET['to'] ?? '') ?>" class="btn btn-outline-success">Export Excel</a>
                </div>	      
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
			<th class="d-none d-sm-table-cell">Tgl Order</th>
			<th>Nama</th>
			<th class="d-none d-sm-table-cell">Produk</th>
			<th class="d-none d-sm-table-cell">Gratis</th>
            <th class="d-none d-sm-table-cell text-end">Harga</th>
            <th class="d-none d-sm-table-cell">Bukti Bayar</th>
            <th class="d-none d-sm-table-cell text-end">Action</th>
		</tr>
	</thead>
	<tbody>
		<?php
		$jmlperpage = 25;
		if (isset($_GET['start']) && is_numeric($_GET['start'])) {
		    $start = ($_GET['start'] - 1) * $jmlperpage;
		    $page = $_GET['start'];
		} else {
		    $start = 0;
		    $page = 1;
		}		
		
		$where = '';
		$dateErr = '';
		if (isset($_GET['cari']) && !empty($_GET['cari'])) {
			$s = cek($_GET['cari']);
			if (is_numeric($_GET['cari'])) {
				$where = "WHERE `order_id`=".$_GET['cari'];
			} else {
				$where = "WHERE (`sa_member`.`mem_nama` LIKE '%".$s."%' 
								OR `sa_member`.`mem_email` LIKE '%".$s."%' 
								OR `sa_member`.`mem_whatsapp` LIKE '%".$s."%' 
								OR `sa_member`.`mem_datalain` LIKE '%".$s."%' 
								OR `sa_member`.`mem_kodeaff` LIKE '%".$s."%'
								OR `sa_page`.`page_judul` LIKE '%".$s."%' 
								OR `sa_page`.`page_diskripsi` LIKE '%".$s."%'
								OR `sa_page`.`page_url` LIKE '%".$s."%')";
			}
		}

		if (isset($_GET['status']) && is_numeric($_GET['status'])) {
			if ($where == '') {
				$where .= "WHERE `sa_order`.`order_status`=".$_GET['status'];			
			} else {
				$where .= " AND `sa_order`.`order_status`=".$_GET['status'];			
			}
		}

        $from = isset($_GET['from']) ? trim($_GET['from']) : '';
        $to   = isset($_GET['to'])   ? trim($_GET['to'])   : '';
        $fromValid = preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) ? $from : '';
        $toValid   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)   ? $to   : '';
        if ($from !== '' && $fromValid === '') { $dateErr = 'Format tanggal awal tidak valid'; }
        if ($to !== '' && $toValid === '') { $dateErr = ($dateErr?($dateErr.'; '):'').'Format tanggal akhir tidak valid'; }
        if ($fromValid !== '' || $toValid !== '') {
            if ($fromValid === '' && $toValid !== '') { $fromValid = $toValid; }
            if ($toValid === '' && $fromValid !== '') { $toValid = $fromValid; }
            if (strtotime($toValid.' 23:59:59') < strtotime($fromValid.' 00:00:00')) {
                $dateErr = 'Tanggal akhir tidak boleh lebih awal dari tanggal awal';
            } else {
                $minmax = db_row("SELECT MIN(`order_tglorder`) AS `min_dt`, MAX(`order_tglorder`) AS `max_dt` FROM `sa_order`");
                $minDate = isset($minmax['min_dt']) && !empty($minmax['min_dt']) ? date('Y-m-d', strtotime($minmax['min_dt'])) : null;
                $maxDate = isset($minmax['max_dt']) && !empty($minmax['max_dt']) ? date('Y-m-d', strtotime($minmax['max_dt'])) : null;
                $info = '';
                if ($minDate && strtotime($fromValid) < strtotime($minDate)) { $fromValid = $minDate; $info = 'Rentang tanggal disesuaikan ke batas data tersedia'; }
                if ($maxDate && strtotime($toValid) > strtotime($maxDate)) { $toValid = $maxDate; $info = 'Rentang tanggal disesuaikan ke batas data tersedia'; }
                $cond = " (`sa_order`.`order_tglorder` >= '".cek($fromValid)." 00:00:00' AND `sa_order`.`order_tglorder` <= '".cek($toValid)." 23:59:59') ";
                if ($where == '') { $where = 'WHERE '.$cond; } else { $where .= ' AND '.$cond; }
                if ($info !== '') { echo '<div class="alert alert-info">'.htmlspecialchars($info).'</div>'; }
            }
        }

		$order = db_select("SELECT * FROM `sa_order` 
			LEFT JOIN `sa_member` ON `sa_member`.`mem_id` = `sa_order`.`order_idmember`
			LEFT JOIN `sa_page` ON `sa_page`.`page_id` = `sa_order`.`order_idproduk`
			".$where."
			ORDER BY `order_tglorder` DESC
			LIMIT ".$start.",".$jmlperpage);

        

        if (!empty($dateErr)) { echo '<div class="alert alert-warning">'.htmlspecialchars($dateErr).'</div>'; }
		if (count($order) > 0) {
			foreach ($order as $order) {
				$hargaNormalOrder = (isset($order['order_harga']) && is_numeric($order['order_harga'])) ? (int)$order['order_harga'] : 0;
				$hargaNormalProduk = (isset($order['pro_harga']) && is_numeric($order['pro_harga'])) ? (int)$order['pro_harga'] : $hargaNormalOrder;
				$hargaFinal = (isset($order['order_hargaunik']) && is_numeric($order['order_hargaunik'])) ? (int)$order['order_hargaunik'] : 0;
				$hargaPromoConfig = null;
				if (isset($order['pro_harga_display']) && $order['pro_harga_display'] !== '' && is_numeric($order['pro_harga_display'])) {
					$hargaPromoConfig = (int)$order['pro_harga_display'];
				}
				$couponCode = isset($order['order_promo_code']) ? trim((string)$order['order_promo_code']) : '';
				$orderDiscount = (isset($order['order_discount']) && is_numeric($order['order_discount'])) ? (int)$order['order_discount'] : 0;
				$trx = (string)($order['order_trx'] ?? '');
				$isGratis = ($hargaFinal === 0) && ($trx === 'free' || $hargaNormalOrder > 0);
				$isPromoZero = ($hargaPromoConfig !== null && $hargaPromoConfig === 0 && $hargaNormalProduk > 0);
				$isAksesGratis = ($isPromoZero || $isGratis || ((int)($order['pro_free_access'] ?? 0) === 1));
				$freeBadge = $isPromoZero
					? '<span class="text-success" aria-label="Promo Rp 0"><i class="fa-solid fa-circle-check"></i></span>'
					: '<span class="text-danger" aria-label="Bukan Promo Rp 0"><i class="fa-solid fa-circle-xmark"></i></span>';
				$hargaHtml = '';
				$hargaMobile = '';
				$hargaPromoBase = ($hargaPromoConfig !== null) ? (int)$hargaPromoConfig : (int)$hargaNormalProduk;
				$hargaKupon = null;
				if ($couponCode !== '') {
					if (isset($order['order_price_display']) && is_numeric($order['order_price_display'])) {
						$hargaKupon = (int)$order['order_price_display'];
					} else {
						$eff = epi_effective_price((int)$hargaNormalProduk, (int)$hargaPromoBase, $couponCode, (int)($order['order_idproduk'] ?? 0), 1);
						$hargaKupon = isset($eff['price']) && is_numeric($eff['price']) ? (int)$eff['price'] : (int)$hargaPromoBase;
					}
				}
				$hasKuponDiskon = ($couponCode !== '' && ((int)$orderDiscount > 0 || ($hargaKupon !== null && (int)$hargaKupon < (int)$hargaPromoBase)));
				if ($hasKuponDiskon) {
					$hargaBayar = ($hargaFinal > 0) ? (int)$hargaFinal : (($hargaKupon !== null) ? (int)$hargaKupon : (int)$hargaPromoBase);
					$hargaHtml = '<div class="text-muted" style="text-decoration: line-through;">Rp '.number_format($hargaNormalProduk).'</div>'
						.'<div class="fw-semibold">Rp '.number_format((int)$hargaBayar).'</div>';
					$hargaMobile = '<span class="text-muted" style="text-decoration: line-through;">Rp '.number_format($hargaNormalProduk).'</span> <span class="fw-semibold">Rp '.number_format((int)$hargaBayar).'</span>';
				} elseif ($hargaPromoConfig !== null && $hargaPromoConfig != $hargaNormalProduk) {
					$hargaHtml = '<div class="text-muted" style="text-decoration: line-through;">Rp '.number_format($hargaNormalProduk).'</div>'
						.'<div class="fw-semibold">Rp '.number_format($hargaPromoConfig).'</div>';
					$hargaMobile = '<span class="text-muted" style="text-decoration: line-through;">Rp '.number_format($hargaNormalProduk).'</span> <span class="fw-semibold">Rp '.number_format($hargaPromoConfig).'</span>';
				} else {
					$hargaHtml = '<div class="fw-semibold">Rp '.number_format($hargaNormalProduk).'</div>';
					$hargaMobile = '<span class="fw-semibold">Rp '.number_format($hargaNormalProduk).'</span>';
				}
				$confirm = null;
				$confirmUrl = rtrim($weburl,'/').'/dashboard/konfirmasi/'.(int)$order['order_id'];
				if ($isPromoZero) {
					$buktiCell = '<span class="text-muted">Tidak Ada Pembayaran</span>';
				} else {
					$confirm = db_row("SELECT * FROM `epi_payment_confirm` WHERE `order_id`=".(int)$order['order_id']." ORDER BY `id` DESC LIMIT 1");
					$buktiCell = ((int)$order['order_status'] === 0)
					  ? '<a href="'.$confirmUrl.'" class="btn btn-sm btn-outline-primary">Upload Bukti Bayar</a>'
					  : '<span class="text-muted">&mdash;</span>';
					if (is_array($confirm) && isset($confirm['id'])) {
						$fileUrl = !empty($confirm['file_path']) ? ($weburl.htmlspecialchars($confirm['file_path'])) : '';
						$type = htmlspecialchars($confirm['file_type'] ?? '');
						$nom = is_numeric($confirm['nominal'] ?? null) ? number_format((int)$confirm['nominal']) : '-';
						$buttons = '';
						if ($order['order_status'] == 0 && (int)($confirm['status'] ?? 0) === 0) {
                        $buttons = '<div class="d-flex flex-wrap gap-1 mt-1">'
                          .( $fileUrl ? '<a href="'.$fileUrl.'" target="_blank" class="btn btn-sm btn-outline-primary" data-preview-url="'.$fileUrl.'" data-preview-type="'.$type.'" data-order-id="'.(int)$order['order_id'].'">Lihat Bukti</a>' : '' )
                          .'<a href="'.$weburl.'dashboard/orderlist?verify='.$order['order_id'].'&status=1" class="btn btn-sm btn-outline-success">Terima</a>'
                          .'<a href="#" class="btn btn-sm btn-outline-danger" data-reject="'.$order['order_id'].'">Tolak</a>'
                          .'</div>';
                    } else if ($fileUrl) {
                        $buttons = '<div class="mt-1"><a href="'.$fileUrl.'" target="_blank" class="btn btn-sm btn-outline-primary" data-preview-url="'.$fileUrl.'" data-preview-type="'.$type.'">Lihat Bukti</a></div>';
                    } else if ((int)$order['order_status'] === 0) {
                        $buttons = '<div class="mt-1"><a href="'.$confirmUrl.'" class="btn btn-sm btn-outline-primary">Upload Bukti Bayar</a></div>';
                    }
						$buktiCell = '<div class="small">'
							.'<div><strong>'.htmlspecialchars($confirm['atas_nama'] ?? '').'</strong></div>'
							.'<div>Tgl: '.htmlspecialchars($confirm['transfer_date'] ?? '').' &middot; Nominal: Rp '.$nom.'</div>'
							.'<div>Tujuan: '.htmlspecialchars($confirm['bank_label'] ?? '').' - '.htmlspecialchars($confirm['bank_owner'] ?? '').' - '.htmlspecialchars($confirm['bank_account'] ?? '').'</div>'
							.$buttons
							.'</div>';
					}
				}
				$memEmail = isset($order['mem_email']) ? (string)$order['mem_email'] : '';
				$memWa = isset($order['mem_whatsapp']) ? (string)$order['mem_whatsapp'] : '';
				echo '
				<tr>
					<td><a href="'.$weburl.'invoice/'.$order['order_id'].'" target="_blank">'.$order['order_id'].'</td>
					<td class="d-none d-sm-table-cell">'.$order['order_tglorder'].'</td>
					<td>
					<span class="d-none d-sm-block">'.$order['mem_nama'].'<div class="small text-muted">'.htmlspecialchars($memEmail, ENT_QUOTES).' &middot; '.htmlspecialchars($memWa, ENT_QUOTES).'</div></span>
					<span class="d-sm-none">
					<strong>'.$order['mem_nama'].'</strong>
					<small>('.$order['order_tglorder'].')</small>
					<br/><small class="text-muted">'.htmlspecialchars($memEmail, ENT_QUOTES).' &middot; '.htmlspecialchars($memWa, ENT_QUOTES).'</small>
					<br/>Produk: '.$order['page_judul'].'<br/>
					Harga: '.$hargaMobile.'<br/>'
					.'Status: '.($isGratis ? '<span class="badge bg-warning text-dark">Gratis</span>' : '<span class="text-muted">&mdash;</span>').'<br/>';
				if ($order['order_status'] == 0) {
					if ($isAksesGratis) {
						echo '<span class="btn btn-sm btn-outline-danger disabled" aria-disabled="true">Batalkan</span>';
					} else {
						echo '<a href="'.$weburl.'dashboard/orderlist?proses='.$order['order_id'].'" class="btn btn-sm btn-success">Proses</a>';
					}
					if (!$isPromoZero && !(is_array($confirm) && isset($confirm['id']))) {
						echo ' <a href="'.$confirmUrl.'" class="btn btn-sm btn-outline-primary">Upload Bukti Bayar</a>';
					}
				} else {
					echo '<a href="'.$weburl.'dashboard/orderlist?batal='.$order['order_id'].'" class="btn btn-sm btn-warning">Batal</a>';
				}
				echo '
					&nbsp;&nbsp;<a href="#" data-bs-toggle="modal" data-bs-target="#konfirmasi" data-bs-nama="'.$order['page_judul'].' oleh '.$order['mem_nama'].'" 
					data-bs-id="'.$order['order_id'].'" class="btn btn-sm btn-danger"><i class="fa-solid fa-trash-can" title="Delete"></i></a>
					</span>
					</td>
					<td class="d-none d-sm-table-cell">'.$order['page_judul'].'</td>
					<td class="d-none d-sm-table-cell">'.$freeBadge.'</td>
					<td class="d-none d-sm-table-cell text-end">'.$hargaHtml.'</td>
				<td class="d-none d-sm-table-cell">'.$buktiCell.'</td>
				<td class="d-none d-sm-table-cell text-end">';
                echo '<div class="action-icons d-flex justify-content-end align-items-center gap-2">';
				if ($order['order_status'] == 0) {
					if ($isAksesGratis) {
						echo '<span class="text-decoration-none" data-bs-toggle="tooltip" title="Akses Aktif"><i class="fa-solid fa-ban text-danger"></i></span>';
					} else {
						echo '<a href="'.$weburl.'dashboard/orderlist?proses='.$order['order_id'].'" class="text-decoration-none" data-bs-toggle="tooltip" title="Proses"><i class="fa-solid fa-play text-success"></i></a>';
					}
				} else {
					echo '<a href="'.$weburl.'dashboard/orderlist?batal='.$order['order_id'].'" class="text-decoration-none" data-bs-toggle="tooltip" title="Batalkan"><i class="fa-solid fa-ban text-danger"></i></a>';
				}
                echo '<span data-bs-toggle="tooltip" title="Hapus"><a href="#" class="text-decoration-none" data-bs-toggle="modal" data-bs-target="#konfirmasi" data-bs-nama="'.$order['page_judul'].' oleh '.$order['mem_nama'].'" data-bs-id="'.$order['order_id'].'"><i class="fa-solid fa-trash-can"></i></a></span>';
                echo '</div>';
                echo '</tr>';
			}
		}
        ?>
    </tbody>
</table>
</div>
<style>
  .bukti-backdrop .modal-backdrop.show { opacity: 0.6; }
  .bukti-modal .modal-dialog { margin: 0; }
  .bukti-modal .modal-content { background: rgba(0,0,0,0.85); border: 0; border-radius: 0; }
  .bukti-view { position: relative; width: 100vw; height: 100vh; display: flex; align-items: center; justify-content: center; }
  .bukti-canvas { position: relative; width: calc(100vw - 40px); height: calc(100vh - 40px); padding: 20px; overflow: auto; display:flex; align-items:center; justify-content:center; }
  .bukti-img { max-width: 90vw; max-height: 85vh; width: auto; height: auto; object-fit: contain; display:block; margin: 0 auto; transform-origin: center center; }
  .btn-outline-primary, .btn-outline-success, .btn-outline-danger { font-size:14px; font-weight:500; padding:4px 8px; border-radius:4px; }
  .bukti-loader { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; }
  .bukti-toolbar { position: absolute; top: 12px; right: 12px; display: flex; gap: 8px; z-index: 10; }
  @media (max-width: 767.98px) { .bukti-toolbar { top: 8px; right: 8px; } }

  /* Order filter responsive & premium visuals */
  .order-filter { gap: .5rem; flex-wrap: wrap; align-items: stretch; }
  .order-filter .form-control, .order-filter .form-select { min-width: 160px; }
  @media (max-width: 576px) {
    .order-filter .form-control, .order-filter .form-select { min-width: 140px; }
  }
  .order-filter .btn { display:inline-flex; align-items:center; justify-content:center; padding:.45rem .75rem; font-weight:600; border-radius:6px; transition: transform .15s ease, box-shadow .15s ease, filter .15s ease, background-color .2s ease, color .2s ease; }
  #btnFilter { background-image: linear-gradient(to bottom, rgba(255,255,255,.22), rgba(255,255,255,0) 45%), linear-gradient(to bottom, #E6C76A 0%, #B8942E 100%); color:#1a1a1a; border:0; box-shadow: 0 2px 0 rgba(0,0,0,.12), 0 8px 16px rgba(0,0,0,.08); }
  #btnFilter:hover { filter: brightness(1.03); transform: translateY(-1px); box-shadow: 0 3px 0 rgba(0,0,0,.14), 0 12px 20px rgba(0,0,0,.10); }
  #btnFilter:active { transform: translateY(0); box-shadow: 0 1px 0 rgba(0,0,0,.16), 0 6px 12px rgba(0,0,0,.10); }
  #btnReset { border-color:#d0d0d0; color:#333; background:#fff; }
  #btnReset:hover { border-color:#B8942E; color:#B8942E; }
  .order-filter .btn-outline-primary { border-color:#3b82f6; color:#1f5fb8; background:#fff; }
  .order-filter .btn-outline-primary:hover { border-color:#1f5fb8; color:#1f5fb8; filter: brightness(1.03); }
  .order-filter .btn-outline-success { border-color:#2f7d2f; color:#2f7d2f; background:#fff; }
  .order-filter .btn-outline-success:hover { border-color:#2f7d2f; color:#2f7d2f; filter: brightness(1.03); }
  .order-filter .form-control, .order-filter .form-select { border-radius:6px; }
  .order-filter .form-control:focus, .order-filter .form-select:focus { box-shadow:0 0 0 .2rem rgba(212,175,55,.25); border-color:#D4AF37; }
</style>
<script>
document.getElementById('btnFilter')?.addEventListener('click', function(){ var btn=this; btn.disabled=true; btn.innerText='Loading...'; });
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
tooltipTriggerList.forEach(function (el) { try { new bootstrap.Tooltip(el); } catch(e){} });

var buktiScale = 1;
function buktiSetScale(s){
  buktiScale = Math.max(0.5, Math.min(3, s));
  var img = document.getElementById('previewBuktiImg');
  if (img) { img.style.transform = 'scale('+buktiScale+')'; }
}
document.addEventListener('click', function(e){
  var a = e.target.closest('[data-preview-url]');
  if(!a) return;
  e.preventDefault();
  var url = a.getAttribute('data-preview-url');
  var oid = a.getAttribute('data-order-id');
  var type = (a.getAttribute('data-preview-type')||'').toLowerCase();
  var img = document.getElementById('previewBuktiImg');
  var pdfWrap = document.getElementById('previewBuktiPdf');
  var loader = document.getElementById('previewBuktiLoader');
  if (!img || !loader) return window.open(url, '_blank');
  buktiSetScale(1);
  loader.style.display = 'flex';
  img.style.display = 'none';
  if (pdfWrap) { pdfWrap.innerHTML = ''; pdfWrap.style.display = 'none'; }
  if (type.indexOf('pdf') !== -1 || url.toLowerCase().endsWith('.pdf')) {
    var obj = document.createElement('object');
    obj.setAttribute('data', url);
    obj.setAttribute('type','application/pdf');
    obj.style.width = 'calc(100vw - 40px)';
    obj.style.height = 'calc(100vh - 40px)';
    obj.style.background = '#111';
    if (pdfWrap) { pdfWrap.appendChild(obj); pdfWrap.style.display = 'block'; }
    loader.style.display = 'none';
  } else {
    img.onload = function(){ loader.style.display = 'none'; img.style.display = 'block'; };
    img.onerror = function(){ loader.style.display = 'none'; img.style.display = 'block'; };
    img.src = url;
  }
  // Set order id ke tombol aksi dalam modal
  try { var m = new bootstrap.Modal(document.getElementById('previewBuktiModal')); m.show(); } catch(e){ window.open(url, '_blank'); }
});
document.getElementById('buktiZoomIn')?.addEventListener('click', function(){ buktiSetScale(buktiScale + 0.25); });
document.getElementById('buktiZoomOut')?.addEventListener('click', function(){ buktiSetScale(buktiScale - 0.25); });
document.getElementById('buktiZoomReset')?.addEventListener('click', function(){ buktiSetScale(1); });
</script>
<script>
// Enhance filter UX: always enable button, update export links on input change
(function(){
  var wrap = document.querySelector('.order-filter');
  if (!wrap) return;
  var btn = document.getElementById('btnFilter');
  if (btn) { btn.disabled = false; btn.innerText = (btn.innerText||'').trim() || 'Filter'; }
  var q = wrap.querySelector('input[name="cari"]');
  var st = wrap.querySelector('select[name="status"]');
  var f = wrap.querySelector('input[name="from"]');
  var t = wrap.querySelector('input[name="to"]');
  var csv = wrap.querySelector('a[href*="export=csv"]');
  var xls = wrap.querySelector('a[href*="export=xlsx"]');
  function buildUrl(type){
    function enc(v){ return encodeURIComponent(v||''); }
    return 'orderlist?export=' + type + '&cari=' + enc(q && q.value) + '&status=' + enc(st && st.value) + '&from=' + enc(f && f.value) + '&to=' + enc(t && t.value);
  }
  function sync(){ if (csv) csv.href = buildUrl('csv'); if (xls) xls.href = buildUrl('xlsx'); }
  ['input','change'].forEach(function(evt){ if(q) q.addEventListener(evt, sync); if(st) st.addEventListener(evt, sync); if(f) f.addEventListener(evt, sync); if(t) t.addEventListener(evt, sync); });
  sync();
})();
</script>
<script>
(function(){
  var wrap = document.querySelector('.order-filter');
  if (!wrap) return;
  var input = wrap.querySelector('input[name="cari"]');
  var status = wrap.querySelector('select[name="status"]');
  var from = wrap.querySelector('input[name="from"]');
  var to = wrap.querySelector('input[name="to"]');
  var spinner = document.getElementById('filterSpinner');
  if (!spinner && input) {
    spinner = document.createElement('span');
    spinner.id = 'filterSpinner';
    spinner.className = 'spinner-border spinner-border-sm ms-2 align-middle d-none';
    input.parentNode.insertBefore(spinner, input.nextSibling);
  }
  var timer = null; var controller = null;
  function qs(params){
    var s = [];
    for (var k in params) { if (!params.hasOwnProperty(k)) continue; var v = params[k]||''; s.push(encodeURIComponent(k)+'='+encodeURIComponent(v)); }
    return s.join('&');
  }
  function start(){ if (spinner) spinner.classList.remove('d-none'); }
  function stop(){ if (spinner) spinner.classList.add('d-none'); }
  function apply(){
    var q = (input && input.value || '').trim();
    q = q.replace(/[\u0000-\u001F]+/g,'');
    if (q.length>0 && q.length<2) { stop(); return; }
    if (controller) { try{ controller.abort(); }catch(e){} }
    controller = new AbortController();
    start();
    var url = 'orderlist?' + qs({ cari: q, status: (status&&status.value)||'', from: (from&&from.value)||'', to: (to&&to.value)||'' });
    fetch(url, { method:'GET', credentials:'same-origin', signal: controller.signal }).then(function(resp){ return resp.text(); }).then(function(html){
      var tmp = document.createElement('div'); tmp.innerHTML = html;
      var newBody = tmp.querySelector('div.table-responsive tbody');
      var curBody = document.querySelector('div.table-responsive tbody');
      if (newBody && curBody) { curBody.innerHTML = newBody.innerHTML; }
      var newPag = tmp.querySelector('nav[aria-label="Page navigation"]');
      var curPag = document.querySelector('nav[aria-label="Page navigation"]');
      if (newPag && curPag) { curPag.innerHTML = newPag.innerHTML; }
    }).catch(function(){ /* ignore */ }).finally(function(){ stop(); });
  }
  function schedule(){ clearTimeout(timer); timer = setTimeout(apply, 400); }
  if (input) { input.addEventListener('input', schedule); input.addEventListener('change', schedule); }
  if (status) { status.addEventListener('change', schedule); }
  if (from) { from.addEventListener('change', schedule); }
  if (to) { to.addEventListener('change', schedule); }
})();
</script>
<script>
document.addEventListener('click', function(e){
  var r = e.target.closest('[data-reject]');
  if(!r) return;
  e.preventDefault();
  var oid = r.getAttribute('data-reject');
  var note = prompt('Alasan penolakan?');
  if (note === null) return;
  window.location.href = '<?=$weburl;?>dashboard/orderlist?verify='+encodeURIComponent(oid)+'&status=-1&note='+encodeURIComponent(note);
});
</script>
<?php
$jmlmember = db_var("SELECT count(*) FROM `sa_order` 
			LEFT JOIN `sa_member` ON `sa_member`.`mem_id` = `sa_order`.`order_idmember`
			LEFT JOIN `sa_page` ON `sa_page`.`page_id` = `sa_order`.`order_idproduk`
			".$where);
$jmlpage = ceil($jmlmember/$jmlperpage);
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
<div class="modal fade bukti-backdrop bukti-modal" id="previewBuktiModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-fullscreen">
    <div class="modal-content">
      <div class="bukti-view">
        <div class="bukti-toolbar">
          <button type="button" class="btn btn-light" id="buktiZoomOut" title="Zoom Out">-</button>
          <button type="button" class="btn btn-light" id="buktiZoomReset" title="Reset">100%</button>
          <button type="button" class="btn btn-light" id="buktiZoomIn" title="Zoom In">+</button>
          <button type="button" class="btn btn-light" data-bs-dismiss="modal" aria-label="Close">&times;</button>
        </div>
        <div class="bukti-canvas">
          <div class="bukti-loader" id="previewBuktiLoader">
            <div class="spinner-border text-light" role="status"><span class="visually-hidden">Loading...</span></div>
          </div>
          <img id="previewBuktiImg" class="bukti-img" alt="Bukti Pembayaran" />
          <div id="previewBuktiPdf" style="display:none;"></div>
        </div>
      </div>
    </div>
  </div>
  </div>
<?php 
$footer['konfirm'] = "⚠️ Anda akan menghapus order <strong>'+nama+'</strong>";
showfooter($footer);
?>
