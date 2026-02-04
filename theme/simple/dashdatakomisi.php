<?php
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
if (!isset($datamember['mem_role']) || (int)$datamember['mem_role'] < 5) { die(); exit(); }

$head = array();
$head['pagetitle'] = 'Data Komisi';
showheader($head);

$pph = isset($settings['pph21_percent']) ? (float)$settings['pph21_percent'] : 0.0;
if ($pph < 0) { $pph = 0.0; }
if ($pph > 100) { $pph = 100.0; }
$pphSql = number_format($pph, 2, '.', '');

$sponsorNet = (int)db_var("SELECT COALESCE(SUM(`lap_masuk`)-SUM(`lap_keluar`),0) FROM `sa_laporan` WHERE `lap_code`=2");
$contribNet = (int)db_var("SELECT COALESCE(SUM(`lap_masuk`)-SUM(`lap_keluar`),0) FROM `sa_laporan` WHERE `lap_code`=3");
$reservedSponsor = (int)db_var("SELECT COALESCE(SUM(`amount`),0) FROM `epi_commission_payout` WHERE `type`='sponsor' AND `status` IN ('requested','processed','paid')");
$reservedContrib = (int)db_var("SELECT COALESCE(SUM(`amount`),0) FROM `epi_commission_payout` WHERE `type`='contrib' AND `status` IN ('requested','processed','paid')");
$notReqSponsor = max(0, $sponsorNet - $reservedSponsor);
$notReqContrib = max(0, $contribNet - $reservedContrib);
$pendingSponsor = (int)db_var("SELECT COALESCE(SUM(`amount`),0) FROM `epi_commission_payout` WHERE `type`='sponsor' AND `status` IN ('requested','pending','processed')");
$pendingContrib = (int)db_var("SELECT COALESCE(SUM(`amount`),0) FROM `epi_commission_payout` WHERE `type`='contrib' AND `status` IN ('requested','pending','processed')");
$paidSponsor = (int)db_var("SELECT COALESCE(SUM(`net_amount`),0) FROM `epi_commission_payout` WHERE `type`='sponsor' AND `status`='paid'");
$paidContrib = (int)db_var("SELECT COALESCE(SUM(`net_amount`),0) FROM `epi_commission_payout` WHERE `type`='contrib' AND `status`='paid'");
$lastPaid = db_row("SELECT MAX(`paid_at`) AS `ts` FROM `epi_commission_payout` WHERE `status`='paid'");
$lastPaidTs = isset($lastPaid['ts']) && !empty($lastPaid['ts']) ? date('d/m/Y H:i:s', strtotime($lastPaid['ts'])) : '';
$tsHtml = ($lastPaidTs!=='') ? ('<div class="text-muted small mt-2">Terakhir bayar: '.$lastPaidTs.'</div>') : '';

$q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
if (strlen($q) > 100) { $q = substr($q, 0, 100); }

$jenis = isset($_GET['jenis']) ? strtolower(trim((string)$_GET['jenis'])) : 'all';
if (!in_array($jenis, array('all','pereferral','kontributor'), true)) { $jenis = 'all'; }

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) { $page = 1; }
$limit = 20;
$offset = ($page - 1) * $limit;

$export = isset($_GET['export']) ? strtolower(trim((string)$_GET['export'])) : '';
if (!in_array($export, array('csv','xlsx'), true)) { $export = ''; }

$whereName = '';
if ($q !== '') {
    $s = cek($q);
    $whereName = " WHERE m.`mem_nama` LIKE '%".$s."%'";
}

$ledgerSponsor = "SELECT `lap_idsponsor` AS `mem_id`, COALESCE(SUM(`lap_masuk`)-SUM(`lap_keluar`),0) AS `balance_gross`, MAX(`lap_tanggal`) AS `last_ts` FROM `sa_laporan` WHERE `lap_code`=2 GROUP BY `lap_idsponsor`";
$ledgerContrib = "SELECT `lap_idsponsor` AS `mem_id`, COALESCE(SUM(`lap_masuk`)-SUM(`lap_keluar`),0) AS `balance_gross`, MAX(`lap_tanggal`) AS `last_ts` FROM `sa_laporan` WHERE `lap_code`=3 GROUP BY `lap_idsponsor`";

$payoutSponsor = "SELECT `receiver_id` AS `mem_id`,\n"
    ." SUM(CASE WHEN `status` IN ('requested','pending','processed') THEN `amount` ELSE 0 END) AS `pending_gross`,\n"
    ." SUM(CASE WHEN `status` IN ('requested','pending','processed') THEN (`amount` - ROUND(`amount` * ".$pphSql." / 100)) ELSE 0 END) AS `pending_net`,\n"
    ." SUM(CASE WHEN `status`='paid' THEN COALESCE(`net_amount`, (`amount` - ROUND(`amount` * ".$pphSql." / 100))) ELSE 0 END) AS `paid_net`,\n"
    ." MAX(CASE WHEN `status`='paid' THEN COALESCE(`paid_at`,`created_at`) WHEN `status` IN ('requested','pending','processed') THEN `created_at` ELSE NULL END) AS `last_ts`\n"
    ." FROM `epi_commission_payout` WHERE `type`='sponsor' GROUP BY `receiver_id`";

$payoutContrib = "SELECT `receiver_id` AS `mem_id`,\n"
    ." SUM(CASE WHEN `status` IN ('requested','pending','processed') THEN `amount` ELSE 0 END) AS `pending_gross`,\n"
    ." SUM(CASE WHEN `status` IN ('requested','pending','processed') THEN (`amount` - ROUND(`amount` * ".$pphSql." / 100)) ELSE 0 END) AS `pending_net`,\n"
    ." SUM(CASE WHEN `status`='paid' THEN COALESCE(`net_amount`, (`amount` - ROUND(`amount` * ".$pphSql." / 100))) ELSE 0 END) AS `paid_net`,\n"
    ." MAX(CASE WHEN `status`='paid' THEN COALESCE(`paid_at`,`created_at`) WHEN `status` IN ('requested','pending','processed') THEN `created_at` ELSE NULL END) AS `last_ts`\n"
    ." FROM `epi_commission_payout` WHERE `type`='contrib' GROUP BY `receiver_id`";

$baseSponsor = "SELECT\n"
    ." m.`mem_id`,\n"
    ." m.`mem_nama`,\n"
    ." m.`mem_whatsapp`,\n"
    ." 'Pereferral' AS `jenis`,\n"
    ." GREATEST(COALESCE(l.`balance_gross`,0) - COALESCE(p.`pending_gross`,0), 0) AS `belum_gross`,\n"
    ." (GREATEST(COALESCE(l.`balance_gross`,0) - COALESCE(p.`pending_gross`,0), 0) - ROUND(GREATEST(COALESCE(l.`balance_gross`,0) - COALESCE(p.`pending_gross`,0), 0) * ".$pphSql." / 100)) AS `belum_net`,\n"
    ." COALESCE(p.`pending_net`,0) AS `pending_net`,\n"
    ." COALESCE(p.`paid_net`,0) AS `paid_net`,\n"
    ." ((GREATEST(COALESCE(l.`balance_gross`,0) - COALESCE(p.`pending_gross`,0), 0) - ROUND(GREATEST(COALESCE(l.`balance_gross`,0) - COALESCE(p.`pending_gross`,0), 0) * ".$pphSql." / 100)) + COALESCE(p.`pending_net`,0) + COALESCE(p.`paid_net`,0)) AS `total_net`,\n"
    ." GREATEST(COALESCE(l.`last_ts`,'1970-01-01 00:00:00'), COALESCE(p.`last_ts`,'1970-01-01 00:00:00')) AS `last_ts`\n"
    ." FROM `sa_member` m\n"
    ." LEFT JOIN (".$ledgerSponsor.") l ON l.`mem_id`=m.`mem_id`\n"
    ." LEFT JOIN (".$payoutSponsor.") p ON p.`mem_id`=m.`mem_id`\n"
    .$whereName;

$baseContrib = "SELECT\n"
    ." m.`mem_id`,\n"
    ." m.`mem_nama`,\n"
    ." m.`mem_whatsapp`,\n"
    ." 'Kontributor' AS `jenis`,\n"
    ." GREATEST(COALESCE(l.`balance_gross`,0) - COALESCE(p.`pending_gross`,0), 0) AS `belum_gross`,\n"
    ." (GREATEST(COALESCE(l.`balance_gross`,0) - COALESCE(p.`pending_gross`,0), 0) - ROUND(GREATEST(COALESCE(l.`balance_gross`,0) - COALESCE(p.`pending_gross`,0), 0) * ".$pphSql." / 100)) AS `belum_net`,\n"
    ." COALESCE(p.`pending_net`,0) AS `pending_net`,\n"
    ." COALESCE(p.`paid_net`,0) AS `paid_net`,\n"
    ." ((GREATEST(COALESCE(l.`balance_gross`,0) - COALESCE(p.`pending_gross`,0), 0) - ROUND(GREATEST(COALESCE(l.`balance_gross`,0) - COALESCE(p.`pending_gross`,0), 0) * ".$pphSql." / 100)) + COALESCE(p.`pending_net`,0) + COALESCE(p.`paid_net`,0)) AS `total_net`,\n"
    ." GREATEST(COALESCE(l.`last_ts`,'1970-01-01 00:00:00'), COALESCE(p.`last_ts`,'1970-01-01 00:00:00')) AS `last_ts`\n"
    ." FROM `sa_member` m\n"
    ." LEFT JOIN (".$ledgerContrib.") l ON l.`mem_id`=m.`mem_id`\n"
    ." LEFT JOIN (".$payoutContrib.") p ON p.`mem_id`=m.`mem_id`\n"
    .$whereName;

$union = '';
if ($jenis === 'pereferral') {
    $union = $baseSponsor;
} elseif ($jenis === 'kontributor') {
    $union = $baseContrib;
} else {
    $union = "(".$baseSponsor.") UNION ALL (".$baseContrib.")";
}

$outerWhere = " WHERE t.`total_net` > 0";
$sql = "SELECT * FROM (".$union.") t".$outerWhere." ORDER BY t.`last_ts` DESC, t.`mem_nama` ASC, t.`jenis` ASC";
if ($export === '') {
    $sql .= " LIMIT ".$limit." OFFSET ".$offset;
}

$countSql = "SELECT COUNT(*) FROM (".$union.") t".$outerWhere;
$totalRows = (int)db_var($countSql);
$totalPages = ($totalRows > 0) ? (int)ceil($totalRows / $limit) : 1;
if ($page > $totalPages) { $page = $totalPages; $offset = ($page - 1) * $limit; }

$rows = db_select($sql);
if ($rows === false) { $rows = array(); }

if ($export !== '') {
    $rowsExport = array();
    $rowsExport[] = array('ID Member','Nama Member','Komisi Belum Diajukan','Komisi Pending','Komisi Dibayarkan','Total Komisi Diterima','Jenis Komisi');
    foreach ($rows as $r) {
        $rowsExport[] = array(
            (int)($r['mem_id'] ?? 0),
            (string)($r['mem_nama'] ?? ''),
            (int)($r['belum_net'] ?? 0),
            (int)($r['pending_net'] ?? 0),
            (int)($r['paid_net'] ?? 0),
            (int)($r['total_net'] ?? 0),
            (string)($r['jenis'] ?? ''),
        );
    }

    $fnameBase = 'Data_Komisi_'.date('Ymd_His');
    if (function_exists('ob_get_level') && ob_get_level() > 0) { @ob_clean(); }
    @ini_set('display_errors', '0');

    if ($export === 'csv') {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="'.$fnameBase.'.csv"');
        header('Cache-Control: no-store, max-age=0');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        foreach ($rowsExport as $line) { fputcsv($out, $line); }
        fclose($out);
        exit;
    }

    $xlsxOk = false;
    $clazzSimple = 'SimpleXLSXGen';
    $clazzNs = '\\Shuchkin\\XLSXGen';
    @include_once dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'xlsxgen.php';
    if (class_exists($clazzSimple)) {
        $xlsx = $clazzSimple::fromArray($rowsExport);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'.$fnameBase.'.xlsx"');
        $xlsx->downloadAs($fnameBase.'.xlsx');
        $xlsxOk = true;
        exit;
    } elseif (class_exists($clazzNs)) {
        $xlsx = $clazzNs::fromArray($rowsExport);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'.$fnameBase.'.xlsx"');
        $xlsx->saveAs('php://output');
        $xlsxOk = true;
        exit;
    }
    if (!$xlsxOk) {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="'.$fnameBase.'.csv"');
        header('Cache-Control: no-store, max-age=0');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        foreach ($rowsExport as $line) { fputcsv($out, $line); }
        fclose($out);
        exit;
    }
}

$qs = array();
if ($q !== '') { $qs['q'] = $q; }
if ($jenis !== 'all') { $qs['jenis'] = $jenis; } else { $qs['jenis'] = 'all'; }

$qsExportCsv = $qs; $qsExportCsv['export'] = 'csv';
$qsExportXlsx = $qs; $qsExportXlsx['export'] = 'xlsx';

$qsBase = $qs;

$startRow = ($totalRows > 0) ? ($offset + 1) : 0;
$endRow = min($offset + $limit, $totalRows);

echo '<style>'
  .'.btn-apply{ display:inline-block; padding:.5rem .9rem; border:none; border-radius:.5rem; background:#D4AF37; color:#0B0B0B; text-decoration:none; font-weight:600; box-shadow:0 .3rem 0 #b18c2c, 0 .3rem .6rem rgba(0,0,0,.25); transform:translateY(0); transition:transform .1s ease, box-shadow .1s ease, filter .15s ease; }'
  .'.btn-apply:hover{ filter:brightness(.97); }'
  .'.btn-apply:active{ transform:translateY(.25rem); box-shadow:0 .05rem 0 #b18c2c, 0 .1rem .3rem rgba(0,0,0,.25); }'
  .'.btn-apply:focus-visible{ outline:2px solid #0B0B0B; outline-offset:2px; }'
  .'.btn-followup{ display:inline-flex; align-items:center; justify-content:center; padding:.35rem .6rem; border:none; border-radius:.5rem; background:#D4AF37; color:#0B0B0B; text-decoration:none; font-weight:600; font-size:.875rem; box-shadow:0 .2rem 0 #b18c2c, 0 .2rem .45rem rgba(0,0,0,.2); transform:translateY(0); transition:transform .1s ease, box-shadow .1s ease, filter .15s ease; white-space:nowrap; }'
  .'.btn-followup:hover{ filter:brightness(.97); }'
  .'.btn-followup:active{ transform:translateY(.2rem); box-shadow:0 .05rem 0 #b18c2c, 0 .1rem .3rem rgba(0,0,0,.2); }'
  .'.btn-followup:focus-visible{ outline:2px solid #0B0B0B; outline-offset:2px; }'
  .'.btn-followup.disabled, .btn-followup[aria-disabled="true"]{ opacity:.6; pointer-events:none; }'
  .'.komisi-actionbar{ gap:10px; }'
  .'.komisi-actionbar .btn-apply{ width:100%; text-align:center; }'
  .'@media (min-width: 576px){ .komisi-actionbar .btn-apply{ width:auto; } }'
  .'.export-indicator{ font-size:.85rem; padding:.2rem .5rem; border-radius:.35rem; background:#F8F8F8; color:#0B0B0B; border:1px solid #ddd; }'
  .'.export-indicator.filtered{ background:#fff3cd; color:#664d03; border-color:#ffecb5; }'
  .'</style>';

echo '<div class="row g-3 mb-3 align-items-stretch">
  <div class="col-12 col-sm-6 col-lg-3">
    <div class="card shadow-sm h-100" style="border-left:4px solid #D4AF37;">
      <div class="card-header fw-bold"><i class="fas fa-coins" aria-hidden="true"></i> Seluruh Komisi</div>
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="badge bg-dark">Pereferal</span>
          <span class="h6 mb-0">Rp '.number_format($sponsorNet,0,',','.').'</span>
        </div>
        <div class="d-flex justify-content-between align-items-center">
          <span class="badge bg-warning text-dark">Kontributor</span>
          <span class="h6 mb-0">Rp '.number_format($contribNet,0,',','.').'</span>
        </div>
      </div>
    </div>
  </div>
  <div class="col-12 col-sm-6 col-lg-3">
    <div class="card shadow-sm h-100" style="border-left:4px solid #D4AF37;">
      <div class="card-header fw-bold"><i class="fas fa-paper-plane" aria-hidden="true"></i> Komisi Belum Diajukan</div>
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="badge bg-dark">Pereferal</span>
          <span class="h6 mb-0">Rp '.number_format($notReqSponsor,0,',','.').'</span>
        </div>
        <div class="d-flex justify-content-between align-items-center">
          <span class="badge bg-warning text-dark">Kontributor</span>
          <span class="h6 mb-0">Rp '.number_format($notReqContrib,0,',','.').'</span>
        </div>
      </div>
    </div>
  </div>
  <div class="col-12 col-sm-6 col-lg-3">
    <div class="card shadow-sm h-100" style="border-left:4px solid #D4AF37;">
      <div class="card-header fw-bold"><i class="fas fa-hourglass-half" aria-hidden="true"></i> Komisi Pending</div>
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="badge bg-dark">Pereferal</span>
          <span class="h6 mb-0">Rp '.number_format($pendingSponsor,0,',','.').'</span>
        </div>
        <div class="d-flex justify-content-between align-items-center">
          <span class="badge bg-warning text-dark">Kontributor</span>
          <span class="h6 mb-0">Rp '.number_format($pendingContrib,0,',','.').'</span>
        </div>
      </div>
    </div>
  </div>
  <div class="col-12 col-sm-6 col-lg-3">
    <div class="card shadow-sm h-100" style="border-left:4px solid #D4AF37;">
      <div class="card-header fw-bold"><i class="fas fa-check-circle" aria-hidden="true"></i> Komisi Sudah Dibayarkan</div>
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="badge bg-dark">Pereferal</span>
          <span class="h6 mb-0">Rp '.number_format($paidSponsor,0,',','.').'</span>
        </div>
        <div class="d-flex justify-content-between align-items-center">
          <span class="badge bg-warning text-dark">Kontributor</span>
          <span class="h6 mb-0">Rp '.number_format($paidContrib,0,',','.').'</span>
        </div>
        '.$tsHtml.'
      </div>
    </div>
  </div>
</div>';

echo '<h4 class="mb-2">Data Komisi</h4>';

echo '<form class="card mb-3" method="get" action="">'
    .'<div class="card-body">'
      .'<div class="row g-2 align-items-end">'
        .'<div class="col-12 col-md-6">'
          .'<label class="form-label">Cari Nama Member</label>'
          .'<input type="text" class="form-control" name="q" value="'.htmlspecialchars($q, ENT_QUOTES).'" placeholder="Ketik nama member..." />'
        .'</div>'
        .'<div class="col-12 col-md-6">'
          .'<label class="form-label">Jenis Komisi</label>'
          .'<select class="form-select" name="jenis">'
            .'<option value="all"'.($jenis==='all'?' selected':'').'>Semua</option>'
            .'<option value="pereferral"'.($jenis==='pereferral'?' selected':'').'>Pereferral</option>'
            .'<option value="kontributor"'.($jenis==='kontributor'?' selected':'').'>Kontributor</option>'
          .'</select>'
        .'</div>'
      .'</div>'
      .'<div class="komisi-actionbar mt-3 d-flex flex-wrap align-items-center">'
        .'<button type="submit" class="btn-apply" aria-label="Terapkan filter">Terapkan</button>'
        .'<a class="btn-apply" href="?'.http_build_query($qsExportCsv).'" aria-label="Export CSV" title="Export data komisi ke CSV" data-bs-toggle="tooltip" data-bs-placement="top">Export CSV</a>'
        .'<a class="btn-apply" href="?'.http_build_query($qsExportXlsx).'" aria-label="Export Excel" title="Export data komisi ke Excel (XLSX)" data-bs-toggle="tooltip" data-bs-placement="top">Export Excel</a>'
      .'</div>'
      .'<div class="text-muted small mt-2">Menampilkan '.$startRow.'-'.$endRow.' dari '.$totalRows.' baris</div>'
    .'</div>'
  .'</form>';

echo '<div class="table-responsive">'
    .'<table class="table table-hover table-bordered align-middle">'
      .'<thead class="table-secondary">'
        .'<tr>'
          .'<th>ID</th>'
          .'<th>Nama Member</th>'
          .'<th class="text-end"><span class="badge bg-secondary">Belum Diajukan</span></th>'
          .'<th class="text-end"><span class="badge bg-warning text-dark">Pending</span></th>'
          .'<th class="text-end"><span class="badge bg-success">Dibayarkan</span></th>'
          .'<th class="text-end">Total Komisi Diterima</th>'
          .'<th>Jenis Komisi</th>'
          .'<th>Action</th>'
        .'</tr>'
      .'</thead>'
      .'<tbody>';

if (count($rows) === 0) {
    echo '<tr><td colspan="8" class="text-center text-muted">Data tidak ditemukan.</td></tr>';
} else {
    foreach ($rows as $r) {
        $memId = (int)($r['mem_id'] ?? 0);
        $nama = (string)($r['mem_nama'] ?? '');
        $waRaw = isset($r['mem_whatsapp']) ? trim((string)$r['mem_whatsapp']) : '';
        $wa = $waRaw !== '' ? (string)formatwa($waRaw) : '';
        $belum = (int)($r['belum_net'] ?? 0);
        $pending = (int)($r['pending_net'] ?? 0);
        $paid = (int)($r['paid_net'] ?? 0);
        $total = (int)($r['total_net'] ?? 0);
        $jenisLabel = (string)($r['jenis'] ?? '');

        $outstanding = max(0, $belum + $pending);
        $amountText = 'Rp '.number_format($outstanding, 0, ',', '.');
        $msg = 'Halo '.$nama.', saat ini Anda mempunyai Komisi yang belum dicairkan sejumlah '.$amountText.'. Silakan lakukan pencairan segera, kunjungi halaman Komisi pada https://bisnisemasperak.com/dashboard/laporankomisi';
        $waUrl = ($wa !== '') ? ('https://wa.me/'.$wa.'?text='.rawurlencode($msg)) : '';
        $canFollow = ($waUrl !== '' && $outstanding > 0);

        $jenisBadge = ($jenisLabel === 'Kontributor') ? '<span class="badge bg-warning text-dark">Kontributor</span>' : '<span class="badge bg-dark">Pereferral</span>';
        echo '<tr>'
            .'<td>'.(int)$memId.'</td>'
            .'<td>'.htmlspecialchars($nama, ENT_QUOTES).'</td>'
            .'<td class="text-end text-secondary">Rp '.number_format($belum, 0, ',', '.').'</td>'
            .'<td class="text-end text-warning">Rp '.number_format($pending, 0, ',', '.').'</td>'
            .'<td class="text-end text-success">Rp '.number_format($paid, 0, ',', '.').'</td>'
            .'<td class="text-end fw-bold">Rp '.number_format($total, 0, ',', '.').'</td>'
            .'<td>'.$jenisBadge.'</td>'
            .'<td>'.($canFollow
                ? ('<a class="btn-followup" href="'.htmlspecialchars($waUrl, ENT_QUOTES).'" target="_blank" rel="noopener" title="Follow up via WhatsApp" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Follow up via WhatsApp">Follow Up</a>')
                : ('<span class="btn-followup disabled" aria-disabled="true" title="'.($waUrl===''?'Nomor WhatsApp belum tersedia':'Tidak ada komisi belum dicairkan').'" data-bs-toggle="tooltip" data-bs-placement="top">Follow Up</span>')
            ).'</td>'
            .'</tr>';
    }
}

echo '</tbody></table></div>';

echo '<script>document.addEventListener("DOMContentLoaded", function(){ var tooltipTriggerList = [].slice.call(document.querySelectorAll("[data-bs-toggle=\\"tooltip\\"]")); tooltipTriggerList.forEach(function (el) { try { new bootstrap.Tooltip(el); } catch(e){} }); });</script>';

if ($export === '' && $totalPages > 1) {
    $mkLink = function($p) use ($qsBase){
        $qsX = $qsBase;
        $qsX['page'] = $p;
        return '?'.http_build_query($qsX);
    };

    $prev = max(1, $page - 1);
    $next = min($totalPages, $page + 1);
    $start = max(1, $page - 2);
    $end = min($totalPages, $page + 2);

    echo '<nav aria-label="Pagination">'
        .'<ul class="pagination justify-content-end">'
          .'<li class="page-item'.($page<=1?' disabled':'').'"><a class="page-link" href="'.htmlspecialchars($mkLink($prev), ENT_QUOTES).'">Sebelumnya</a></li>';

    for ($p = $start; $p <= $end; $p++) {
        echo '<li class="page-item'.($p===$page?' active':'').'"><a class="page-link" href="'.htmlspecialchars($mkLink($p), ENT_QUOTES).'">'.$p.'</a></li>';
    }

    echo '<li class="page-item'.($page>=$totalPages?' disabled':'').'"><a class="page-link" href="'.htmlspecialchars($mkLink($next), ENT_QUOTES).'">Berikutnya</a></li>'
        .'</ul>'
      .'</nav>';
}

showfooter();
