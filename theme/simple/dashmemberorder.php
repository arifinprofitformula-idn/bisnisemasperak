<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
$weburl = isset($weburl) ? $weburl : (function_exists('weburl') ? call_user_func('weburl') : '/');
if (isset($_GET['export'])) {
  $fmtRaw = strtolower(trim((string)$_GET['export']));
  $fmt = ($fmtRaw==='csv') ? 'csv' : 'xlsx';
  $qs = http_build_query(array(
    'status' => isset($_GET['status']) ? (string)$_GET['status'] : '',
    'cari'   => isset($_GET['cari'])   ? (string)$_GET['cari']   : '',
    'format' => $fmt,
  ));
  header('Location: '.$weburl.'api/export-orderlist.php?'.$qs);
  exit;
}
$head['pagetitle']='Order Anda';
showheader($head);
?>
<form action="" method="get">
<div class="card mb-3">
	<div class="card-body">
	  <div class="row">	    
	    <div class="col">
	    	<div class="input-group">
				  <input type="text" class="form-control" name="cari" value="<?= $_GET['cari'] ??= '';?>">
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
				  </select>			  
				  <input type="submit" value=" Cari " class="btn btn-secondary">
				</div>	      
</div>
</div>
</div>
</div>
</form>
<div class="d-flex justify-content-end mb-2">
  <div class="btn-group" role="group" aria-label="Export group">
    <a class="btn btn-outline-dark btn-sm" href="<?=$weburl?>api/export-orderlist.php?status=<?= urlencode(isset($_GET['status'])?$_GET['status']:'') ?>&cari=<?= urlencode(isset($_GET['cari'])?$_GET['cari']:'') ?>&format=xlsx">Export Excel</a>
    <a class="btn btn-outline-secondary btn-sm" href="<?=$weburl?>api/export-orderlist.php?status=<?= urlencode(isset($_GET['status'])?$_GET['status']:'') ?>&cari=<?= urlencode(isset($_GET['cari'])?$_GET['cari']:'') ?>&format=csv">Export CSV</a>
  </div>
  <small class="text-muted ms-2">Mengikuti filter: status & cari</small>
</div>
<div class="table-responsive">
<table class="table table-hover table-bordered">
	<thead class="table-secondary">
		<tr>
			<th>ID</th>
			<th class="d-none d-sm-table-cell">Tgl Order</th>
			<th>Produk</th>
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
		if (isset($_GET['cari']) && !empty($_GET['cari'])) {
			$s = cek($_GET['cari']);
			if (is_numeric($_GET['cari'])) {
				$where = " AND `order_id`=".$_GET['cari'];
			} else {
				$where = " AND (`sa_page`.`page_judul` LIKE '%".$s."%' 
								OR `sa_page`.`page_diskripsi` LIKE '%".$s."%'
								OR `sa_page`.`page_url` LIKE '%".$s."%')";
			}
		}

		if (isset($_GET['status']) && is_numeric($_GET['status'])) {
			$where .= " AND `sa_order`.`order_status`=".$_GET['status'];	
		}

		$order = db_select("SELECT * FROM `sa_order` 
			LEFT JOIN `sa_member` ON `sa_member`.`mem_id` = `sa_order`.`order_idmember`
			LEFT JOIN `sa_page` ON `sa_page`.`page_id` = `sa_order`.`order_idproduk`
			WHERE `sa_order`.`order_idmember`=".$datamember['mem_id'].$where."
			ORDER BY `order_tglorder` DESC
			LIMIT ".$start.",".$jmlperpage);
		if (count($order) > 0) {
			foreach ($order as $order) {
                $importantNote = '';
                if (isset($order['order_important_note']) && (string)$order['order_important_note'] !== '') { $importantNote = trim((string)$order['order_important_note']); }
                if ($importantNote === '') {
                    $dl = isset($order['mem_datalain']) ? (string)$order['mem_datalain'] : '';
                    $pt = '/\[order_note_'.(int)$order['order_id'].'\|(.*?)\]/';
                    if ($dl !== '' && preg_match($pt, $dl, $m)) { $importantNote = trim((string)$m[1]); }
                }
                echo '
                <tr>
                    <td><a href="'.$weburl.'invoice/'.$order['order_id'].'" target="_blank">'.$order['order_id'].'</td>
                    <td class="d-none d-sm-table-cell">'.$order['order_tglorder'].'</td>
                    <td>
                    <span class="d-none d-sm-block">'.$order['page_judul'].'</span>
                    <span class="d-sm-none">
                        <strong>'.$order['mem_nama'].'</strong>
                        <small>('.$order['order_tglorder'].')</small>
                        <br/>Produk: '.$order['page_judul'].'<br/>
                    Harga: '.number_format($order['order_hargaunik']).'<br/>';
					if ($order['order_status'] == 0) {
						echo '<a href="'.$weburl.'invoice/'.$order['order_id'].'" class="btn btn-sm btn-success" target="_blank">Cek Invoice</a>';
					} else {
						echo '<a href="'.$weburl.'dashboard/akses/'.$order['page_url'].'" class="btn btn-sm btn-success" target="_blank">Akses</a>';
					}
				echo '
					</span>
					</td>					
                    <td class="d-none d-sm-table-cell text-end">'.number_format($order['order_hargaunik']).'</td>
                    <td class="d-none d-sm-table-cell">';
                $confirm = db_row("SELECT * FROM `epi_payment_confirm` WHERE `order_id`=".(int)$order['order_id']." ORDER BY `id` DESC LIMIT 1");
                if (is_array($confirm) && isset($confirm['id'])) {
                    $fileUrl = !empty($confirm['file_path']) ? ($weburl.htmlspecialchars($confirm['file_path'])) : '';
                    $type = htmlspecialchars($confirm['file_type'] ?? '');
                    $nom = is_numeric($confirm['nominal'] ?? null) ? number_format((int)$confirm['nominal']) : '-';
                    $status = (int)($confirm['status'] ?? 0);
                    $stText = ($status===1 ? '<span class="badge bg-success">Diterima</span>' : ($status===-1 ? '<span class="badge bg-warning text-dark">Ditolak</span>' : '<span class="badge bg-secondary">Menunggu</span>'));
                    echo '<div class="small">'
                        .'<div><strong>'.htmlspecialchars($confirm['atas_nama'] ?? '').'</strong> '.$stText.'</div>'
                        .'<div>Tgl: '.htmlspecialchars($confirm['transfer_date'] ?? '').' &middot; Nominal: Rp '.$nom.'</div>'
                        .'<div>Tujuan: '.htmlspecialchars($confirm['bank_label'] ?? '').' - '.htmlspecialchars($confirm['bank_owner'] ?? '').' - '.htmlspecialchars($confirm['bank_account'] ?? '').'</div>'
                        .( $fileUrl ? '<div class="d-flex flex-wrap gap-1 mt-1"><a href="'.$fileUrl.'" target="_blank" class="btn btn-sm btn-outline-primary" data-preview-url="'.$fileUrl.'" data-preview-type="'.$type.'">Lihat Bukti</a></div>' : '' )
                        .( ($importantNote!=='') ? '<div class="note-line">'.htmlspecialchars($importantNote, ENT_QUOTES).'</div>' : '' )
                        .'</div>';
                } else {
                    echo '<div class="small"><span class="text-muted">&mdash;</span>'.( ($importantNote!=='') ? '<div class="note-line">'.htmlspecialchars($importantNote, ENT_QUOTES).'</div>' : '' ).'</div>';
                }
                echo '</td>
                    <td class="d-none d-sm-table-cell text-end">';
                echo '<div class="action-icons d-flex justify-content-end align-items-center gap-2">';
                if ($order['order_status'] == 0) {
                    echo '<a href="'.$weburl.'invoice/'.$order['order_id'].'" class="text-decoration-none" target="_blank" data-bs-toggle="tooltip" title="Cek Invoice"><i class="fa-solid fa-file-invoice"></i></a>';
                } else {
                    echo '<a href="'.$weburl.'dashboard/akses/'.$order['page_url'].'" class="text-decoration-none" target="_blank" data-bs-toggle="tooltip" title="Akses"><i class="fa-solid fa-arrow-right-to-bracket"></i></a>';
                }
                echo '</div>';
                echo '</td>
				</tr>
				';
			}
		}
        ?>
    </tbody>
</table>
<style>
.note-line{margin-top:.25rem;font-size:.9rem;color:#0B0B0B;border-left:3px solid #D4AF37;padding-left:.5rem;}
@media (max-width:576px){.note-line{font-size:.9rem;}}
</style>
<script>
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
tooltipTriggerList.forEach(function (el) { try { new bootstrap.Tooltip(el); } catch(e){} });
document.addEventListener('click', function(e){
  var a = e.target.closest('[data-preview-url]');
  if(!a) return;
  e.preventDefault();
  var url = a.getAttribute('data-preview-url');
  var type = (a.getAttribute('data-preview-type')||'').toLowerCase();
  var body = document.getElementById('previewBuktiBody');
  if (!body) return window.open(url, '_blank');
  body.innerHTML = '';
  if (type.indexOf('pdf') !== -1 || url.toLowerCase().endsWith('.pdf')) {
    body.innerHTML = '<object data="'+url+'" type="application/pdf" width="100%" height="480"><a class="btn btn-primary" target="_blank" href="'+url+'">Buka PDF</a></object>';
  } else {
    var img = document.createElement('img'); img.src = url; img.style.maxWidth='100%'; img.style.height='auto'; img.style.borderRadius='8px'; body.appendChild(img);
  }
  try { var m = new bootstrap.Modal(document.getElementById('previewBuktiModal')); m.show(); } catch(e){ window.open(url, '_blank'); }
});
</script>
<?php
$jmlmember = db_var("SELECT count(*) FROM `sa_order` 
			LEFT JOIN `sa_member` ON `sa_member`.`mem_id` = `sa_order`.`order_idmember`
			LEFT JOIN `sa_page` ON `sa_page`.`page_id` = `sa_order`.`order_idproduk`
			WHERE `sa_order`.`order_idmember`=".$datamember['mem_id'].$where);
$jmlpage = floor(($jmlmember/$jmlperpage)+1);
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
<div class="modal fade" id="previewBuktiModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Preview Bukti Pembayaran</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
      <div class="modal-body" id="previewBuktiBody"></div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button></div>
    </div>
  </div>
</div>
<?php showfooter(); ?>
