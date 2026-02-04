<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
if ($datamember['mem_role'] < 6) { die(); exit(); }
$head['pagetitle']='Laporan Keuangan';
showheader($head);

$bulanlist = db_select("SELECT DATE_FORMAT( `lap_tanggal`,  '%Y-%m' ) AS `bulan` FROM  `sa_laporan` 		
		GROUP BY `bulan` ORDER BY `bulan` DESC");

$namabulan = array(
	'01' => 'Januari',
	'02' => 'Februari',
	'03' => 'Maret',
	'04' => 'April',
	'05' => 'Mei',
	'06' => 'Juni',
	'07' => 'Juli',
	'08' => 'Agustus',
	'09' => 'September',
	'10' => 'Oktober',
	'11' => 'Nopember',
	'12' => 'Desember'	
);
$tahun = date('Y');
$bulan = date('m');

if (isset($_GET['detil'])) {
	$exp = explode('-',$_GET['detil']);
	if (is_numeric($exp[0]) && is_numeric($exp[1])) {
		$tahun = $exp[0];
		$bulan = $exp[1];
	}
}

$data = db_select("SELECT * FROM `sa_laporan` 
	LEFT JOIN `sa_member` ON `sa_member`.`mem_id` = `lap_idmember`
	WHERE MONTH(`lap_tanggal`) = ".$bulan." AND YEAR(`lap_tanggal`) = ".$tahun."
	AND `lap_code`=1
	ORDER BY `lap_tanggal`");
echo '
<form action="" method="get">
<div class="card mb-3">
	<div class="card-body">
	  <div class="row">	    
	    <div class="col">
	    	<div class="input-group">				  
				  <select class="form-select" name="detil">';
				  foreach ($bulanlist as $list) {
				  	$ex = explode('-', $list['bulan']);
				  	echo '<option value="'.$list['bulan'].'"';
				  	if ($list['bulan'] == $tahun.'-'.$bulan) { echo ' selected'; }
				  	echo '>'.$namabulan[$ex[1]].' '.$ex[0].'</option>';
				  }
echo '
				  </select>
				  <input type="submit" value=" Pilih Bulan " class="btn btn-secondary">
				  
				</div>	      
	    </div>
	  </div>
	</div>
</div>
</form>
<h4>Laporan '.$namabulan[$bulan].' '.$tahun.'</h4>
<div class="mb-2">
  <form id="exportForm" action="'.$weburl.'api/export-lapkeuangan.php" method="get" target="exportFrame" class="d-inline-flex align-items-end gap-2">
    <input type="hidden" name="detil" value="'.htmlspecialchars($tahun.'-'.$bulan, ENT_QUOTES).'" />
    <div class="col-auto">
      <label class="form-label">Format</label>
      <select name="format" class="form-select form-select-sm" required>
        <option value="xlsx">XLSX (Excel)</option>
        <option value="csv">CSV</option>
      </select>
    </div>
    <div class="col-auto">
      <button type="submit" id="btnExport" class="btn btn-export">Export</button>
    </div>
    <div id="exportLoading" class="text-muted ms-2" style="display:none">Mengekspor...</div>
  </form>
  <iframe id="exportFrame" name="exportFrame" style="display:none"></iframe>
  <style>
    .btn-export{ padding:.25rem .5rem; font-size:.875rem; line-height:1.2; border:1px solid #D4AF37; color:#D4AF37; background:#fff; border-radius:.25rem; }
    .btn-export:hover{ filter:brightness(.95); }
  </style>
</div>
<div class="table-responsive">
<table class="table table-hover table-bordered">
<thead class="table-secondary">
	<tr>
		<th>Tanggal</th>
		<th>Keterangan</th>		
		<th class="text-end">Pemasukan</th>
		<th class="text-end">Pengeluaran</th>
		<th class="text-end">Saldo</th>
	</tr>
</thead>
<tbody>';
if (count($data) > 0) {
	$saldo = 0;
	foreach ($data as $data) {
		$saldo = $saldo + ($data['lap_masuk']-$data['lap_keluar']);
		echo '
	<tr>
		<td>'.$data['lap_tanggal'].'</td>
		<td>'.$data['lap_keterangan'].' '.$data['mem_nama'].'</td>		
		<td class="text-end">'.number_format($data['lap_masuk']).'</td>
		<td class="text-end">'.number_format($data['lap_keluar']).'</td>
		<td class="text-end">'.number_format($saldo).'</td>
	</tr>
		';
	}
}
echo '
</tbody>
</table>
</div>
';
echo '
<script>
  (function(){
    var f=document.getElementById("exportForm");
    var btn=document.getElementById("btnExport");
    var ld=document.getElementById("exportLoading");
    var frame=document.getElementById("exportFrame");
    if(f&&btn&&ld&&frame){
      f.addEventListener("submit", function(){ btn.disabled=true; ld.style.display="inline"; setTimeout(function(){ btn.disabled=false; ld.style.display="none"; }, 8000); });
      frame.addEventListener("load", function(){ btn.disabled=false; ld.style.display="none"; });
    }
  })();
</script>
';

// Rekap Komisi Kontributor (12 bulan terakhir)
$rekap = db_select("SELECT DATE_FORMAT(`lap_tanggal`,'%Y-%m') AS `periode`, SUM(`lap_masuk`) AS `total` FROM `sa_laporan` WHERE `lap_code`=3 GROUP BY `periode` ORDER BY `periode` DESC LIMIT 12");
echo '
<div class="row mt-3">
  <div class="col-md-6">
    <div class="card">
      <div class="card-header">Rekap Komisi Kontributor</div>
      <div class="card-body">
        <div class="table-responsive">
        <table class="table table-sm">
          <thead>
            <tr><th>Periode</th><th class="text-end">Total Komisi</th></tr>
          </thead>
          <tbody>';
          if (is_array($rekap)) { foreach ($rekap as $r) { echo '<tr><td>'.htmlspecialchars($r['periode']).'</td><td class="text-end">'.number_format((int)$r['total']).'</td></tr>'; } }
          echo '
          </tbody>
        </table>
        </div>
      </div>
    </div>
  </div>';

// Riwayat Pembayaran Komisi Kontributor (terbaru)
$riwayat = db_select("SELECT p.`paid_at`,m.`mem_nama`,p.`amount`,p.`status` FROM `epi_commission_payout` p LEFT JOIN `sa_member` m ON m.`mem_id`=p.`receiver_id` WHERE p.`type`='contrib' AND p.`status`='paid' ORDER BY p.`paid_at` DESC LIMIT 20");
echo '
  <div class="col-md-6">
    <div class="card">
      <div class="card-header">Riwayat Pembayaran Komisi Kontributor</div>
      <div class="card-body">
        <div class="table-responsive">
        <table class="table table-sm">
          <thead>
            <tr><th>Tanggal</th><th>Nama</th><th class="text-end">Nominal</th><th>Status</th></tr>
          </thead>
          <tbody>';
          if (is_array($riwayat)) { foreach ($riwayat as $rw) { echo '<tr><td>'.htmlspecialchars($rw['paid_at']).'</td><td>'.htmlspecialchars($rw['mem_nama']).'</td><td class="text-end">'.number_format((int)$rw['amount']).'</td><td>'.htmlspecialchars($rw['status']).'</td></tr>'; } }
          echo '
          </tbody>
        </table>
        </div>
      </div>
    </div>
  </div>
</div>
';

showfooter();
