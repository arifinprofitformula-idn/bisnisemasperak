<?php
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
// Akses kontrol sederhana: hanya admin/staff (sesuaikan dengan sistem role Anda)
$me = isset($datamember) && is_array($datamember) ? $datamember : array();
if (!isset($me['mem_status']) || (int)$me['mem_status'] < 2) { echo '<div class="alert alert-danger">Akses ditolak. Hanya admin/staff.</div>'; showfooter(); return; }

$head['pagetitle'] = 'Laporan Komisi Kontributor';
showheader($head);

// Filter
$pid = isset($_GET['pid']) ? (int)$_GET['pid'] : 0; // product/page id
$uid = isset($_GET['uid']) ? (int)$_GET['uid'] : 0; // contributor user id
$start = isset($_GET['start']) ? $_GET['start'] : '';
$end   = isset($_GET['end']) ? $_GET['end']   : '';
$sort  = isset($_GET['sort']) ? $_GET['sort'] : 'total_masuk';
$dir   = (isset($_GET['dir']) && strtolower($_GET['dir'])==='asc') ? 'ASC' : 'DESC';
$page  = isset($_GET['page']) ? max(1,(int)$_GET['page']) : 1;
$limit = 20; $offset = ($page-1)*$limit;
$export = isset($_GET['export']) ? $_GET['export'] : '';

// UI Filter
echo '<h4>Laporan Komisi Kontributor</h4>';
echo '<form class="row g-2 mb-3" method="get" action="">';
echo '  <div class="col-12 col-md-2"><input type="number" class="form-control" name="pid" placeholder="ID Produk" value="'.($pid?:'').'" /></div>';
echo '  <div class="col-12 col-md-2"><input type="number" class="form-control" name="uid" placeholder="ID Kontributor" value="'.($uid?:'').'" /></div>';
echo '  <div class="col-12 col-md-3"><input type="date" class="form-control" name="start" value="'.htmlspecialchars($start).'" /></div>';
echo '  <div class="col-12 col-md-3"><input type="date" class="form-control" name="end" value="'.htmlspecialchars($end).'" /></div>';
echo '  <div class="col-12 col-md-2 d-grid"><button class="btn btn-primary" type="submit">Filter</button></div>';
echo '</form>';

// Ringkasan per produk dan kontributor
$where = ' WHERE `sa_laporan`.`lap_code`=3 ';
if ($pid > 0) { $where .= ' AND `sa_order`.`order_idproduk`='.$pid.' '; }
if ($uid > 0) { $where .= ' AND `sa_laporan`.`lap_idsponsor`='.$uid.' '; }
if ($start !== '') { $where .= " AND DATE(`sa_laporan`.`lap_tanggal`) >= '".addslashes($start)."' "; }
if ($end !== '')   { $where .= " AND DATE(`sa_laporan`.`lap_tanggal`) <= '".addslashes($end)."' "; }

$sortAllowed = array('total_masuk','trx','page_id','mem_id');
if (!in_array($sort,$sortAllowed)) { $sort = 'total_masuk'; }

$sql = "SELECT `sa_page`.`page_id`,`sa_page`.`page_judul`,`sa_member`.`mem_id`,`sa_member`.`mem_nama`,
               SUM(`sa_laporan`.`lap_masuk`) AS `total_masuk`, COUNT(*) AS `trx`
        FROM `sa_laporan`
        LEFT JOIN `sa_order` ON `sa_order`.`order_id` = `sa_laporan`.`lap_idorder`
        LEFT JOIN `sa_page` ON `sa_page`.`page_id` = `sa_order`.`order_idproduk`
        LEFT JOIN `sa_member` ON `sa_member`.`mem_id` = `sa_laporan`.`lap_idsponsor`
        $where
        GROUP BY `sa_page`.`page_id`,`sa_member`.`mem_id`
        ORDER BY `$sort` $dir
        LIMIT $limit OFFSET $offset";
$rows = db_select($sql);

// Export Excel
if ($export === 'xlsx') {
  $exportRows = db_select(str_replace("LIMIT $limit OFFSET $offset","",$sql));
  $data = array(array('Produk','ID Produk','Kontributor','ID Kontributor','Total Komisi','Transaksi'));
  foreach ($exportRows as $r) {
    $data[] = array($r['page_judul'], $r['page_id'], $r['mem_nama'], $r['mem_id'], (int)$r['total_masuk'], (int)$r['trx']);
  }
  require_once dirname(__DIR__).'/xlsxgen.php';
  $clazz = '\\Shuchkin\\XLSXGen';
  if (!class_exists($clazz)) { echo '<div class="alert alert-warning">Exporter XLSX tidak tersedia.</div>'; showfooter(); exit; }
  $xlsx = $clazz::fromArray($data);
  $fname = 'laporan_kontributor_'.date('Ymd_His').'.xlsx';
  header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
  header('Content-Disposition: attachment; filename="'.$fname.'"');
  $xlsx->saveAs('php://output');
  exit;
}

echo '<div class="d-flex justify-content-between align-items-center mb-2">';
echo '<p class="text-muted mb-0">Sumber: lap_code=3 (CONTRIB). Gunakan filter di atas untuk melihat per produk/kontributor/periode.</p>';
echo '<div><a class="btn btn-sm btn-outline-success" href="?pid='.$pid.'&uid='.$uid.'&start='.$start.'&end='.$end.'&sort='.$sort.'&dir='.$dir.'&export=xlsx">Export Excel</a> <a class="btn btn-sm btn-outline-secondary" href="#" onclick="window.print();return false;">Cetak/PDF</a></div>';
echo '</div>';

echo '<div class="table-responsive">';
echo '<table class="table table-hover table-bordered">';
echo '<thead class="table-secondary"><tr><th>Produk</th><th>Kontributor</th><th class="text-end">Total Komisi</th><th class="text-end">Transaksi</th><th>Aksi</th></tr></thead><tbody>';
foreach ($rows as $r) {
  echo '<tr>';
  echo '<td>'.htmlspecialchars($r['page_judul']).' (ID: '.$r['page_id'].')</td>';
  echo '<td>'.htmlspecialchars($r['mem_nama']).' (ID: '.$r['mem_id'].')</td>';
  echo '<td class="text-end">'.number_format((int)$r['total_masuk']).'</td>';
  echo '<td class="text-end">'.(int)$r['trx'].'</td>';
  echo '<td><a class="btn btn-sm btn-outline-primary" href="dashkontributor.php?pid='.$r['page_id'].'&uid='.$r['mem_id'].'&start='.$start.'&end='.$end.'">Detil</a></td>';
  echo '</tr>';
}
echo '</tbody></table></div>';

// Pagination sederhana
$countSql = "SELECT COUNT(*) AS c FROM (
  SELECT 1
  FROM `sa_laporan`
  LEFT JOIN `sa_order` ON `sa_order`.`order_id` = `sa_laporan`.`lap_idorder`
  $where
  GROUP BY `sa_order`.`order_idproduk`,`sa_laporan`.`lap_idsponsor`
) t";
$cnt = db_row($countSql);
$totalPages = isset($cnt['c']) ? (int)ceil($cnt['c'] / $limit) : 1;
echo '<nav><ul class="pagination">';
for ($p=1; $p<=$totalPages; $p++) {
  $active = ($p==$page)?' active':'';
  echo '<li class="page-item'.$active.'"><a class="page-link" href="?pid='.$pid.'&uid='.$uid.'&start='.$start.'&end='.$end.'&sort='.$sort.'&dir='.$dir.'&page='.$p.'">'.$p.'</a></li>';
}
echo '</ul></nav>';

// Detil transaksi (jika filter aktif)
if ($pid > 0 || $uid > 0) {
  $detWhere = ' WHERE `sa_laporan`.`lap_code`=3 ';
  if ($pid > 0) { $detWhere .= ' AND `sa_order`.`order_idproduk`='.$pid.' '; }
  if ($uid > 0) { $detWhere .= ' AND `sa_laporan`.`lap_idsponsor`='.$uid.' '; }
  if ($start !== '') { $detWhere .= " AND DATE(`sa_laporan`.`lap_tanggal`) >= '".addslashes($start)."' "; }
  if ($end !== '')   { $detWhere .= " AND DATE(`sa_laporan`.`lap_tanggal`) <= '".addslashes($end)."' "; }

  $detSql = "SELECT `sa_laporan`.*,`sa_order`.`order_idproduk`,`sa_page`.`page_judul`,`sa_member`.`mem_nama` AS `buyer_nama`
             FROM `sa_laporan`
             LEFT JOIN `sa_order` ON `sa_order`.`order_id`=`sa_laporan`.`lap_idorder`
             LEFT JOIN `sa_page` ON `sa_page`.`page_id`=`sa_order`.`order_idproduk`
             LEFT JOIN `sa_member` ON `sa_member`.`mem_id`=`sa_laporan`.`lap_idmember`
             $detWhere
             ORDER BY `sa_laporan`.`lap_tanggal` DESC";
  $det = db_select($detSql);
  echo '<h5 class="mt-4">Detil Transaksi</h5>';
  echo '<div class="table-responsive">';
  echo '<table class="table table-hover table-bordered">';
  echo '<thead class="table-secondary"><tr><th>Tanggal</th><th>Produk</th><th>Pembeli</th><th class="text-end">Komisi</th><th>Keterangan</th></tr></thead><tbody>';
  foreach ($det as $d) {
    echo '<tr>';
    echo '<td>'.date('d-m-Y H:i', strtotime($d['lap_tanggal'])).'</td>';
    echo '<td>'.htmlspecialchars($d['page_judul']).'</td>';
    echo '<td>'.htmlspecialchars($d['buyer_nama']).'</td>';
    echo '<td class="text-end">'.number_format((int)$d['lap_masuk']).'</td>';
    echo '<td>'.htmlspecialchars($d['lap_keterangan']).'</td>';
    echo '</tr>';
  }
  echo '</tbody></table></div>';
}

showfooter();
?>
