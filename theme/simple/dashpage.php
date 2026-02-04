<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
@require_once dirname(__DIR__,2).'/plugin/epi-role-manager/index.php';

$roleCode = (int)($datamember['mem_role'] ?? 1);
$allow = ($roleCode >= 9);
if (!$allow && $roleCode >= 5 && function_exists('epi_role_permissions_for_member')) {
  $perms = epi_role_permissions_for_member($datamember);
  if (is_array($perms) && !empty($perms['page'])) { $allow = true; }
}

$head['pagetitle']='Setting Page';
showheader($head);

?>
<div id="pageOfflineAlert" class="alert alert-warning d-none" role="alert">
  Koneksi internet terputus. Halaman mungkin tidak dapat memuat data terbaru.
</div>
<div id="settingPageLoading" class="d-none position-fixed top-0 start-0 w-100 h-100" style="background: rgba(255,255,255,.75); z-index: 2000;">
  <div class="d-flex h-100 w-100 align-items-center justify-content-center">
    <div class="text-center">
      <div class="spinner-border" role="status" aria-hidden="true"></div>
      <div class="mt-2">Memuat...</div>
    </div>
  </div>
</div>
<script>
(function(){
  function setOffline(isOffline){
    var el = document.getElementById('pageOfflineAlert');
    if (!el) return;
    if (isOffline) el.classList.remove('d-none');
    else el.classList.add('d-none');
  }
  window.addEventListener('offline', function(){ setOffline(true); });
  window.addEventListener('online', function(){ setOffline(false); });
  setOffline(!navigator.onLine);

  document.addEventListener('submit', function(e){
    var form = e.target;
    if (!form || !form.getAttribute) return;
    if (form.getAttribute('data-page-loading') !== 'settingpage') return;
    var overlay = document.getElementById('settingPageLoading');
    if (overlay) overlay.classList.remove('d-none');
  }, true);
})();
</script>
<?php

if (!$allow) {
  echo '<div class="alert alert-danger">Akses ditolak. Role Anda tidak memiliki izin untuk halaman Setting Page. Silakan minta Administrator mengaktifkan permission <code>page</code> di halaman Setting Role.</div>';
  showfooter();
  return;
}

if (isset($_GET['del']) && is_numeric($_GET['del'])) {
	$cek = db_query("DELETE FROM `sa_page` WHERE `page_id`=".$_GET['del']);
	if ($cek === false) {
		echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
		  <strong>Error!</strong> '.db_error().'
		  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>';
	} else {
		echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
		  <strong>Ok!</strong> Page telah dihapus.
		  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>';
	}
}

if (isset($_GET['edit'])) {
	include('dashpageadd.php');
} else {

?>
<form action="" method="get" data-page-loading="settingpage">
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
	    	<a href="page?edit=new" class="btn btn-success">Tambah Page</a>
	    </div>
	  </div>
	</div>
</div>
</form>

<div class="table-responsive">
<table class="table table-hover table-bordered">
	<thead class="table-secondary">
		<tr>
			<th>Judul Page</th>
			<th>&nbsp;</th>
		</tr>
	</thead>
	<tbody class="table-group-divider">
		<?php 
		$jmlperpage = 15;
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
			$where .= "AND (`page_judul` LIKE '%".$s."%' 
								OR `page_diskripsi` LIKE '%".$s."%' 
								OR `page_url` LIKE '%".$s."%' 
								OR `page_iframe` LIKE '%".$s."%')";
		}

		$data = db_select("SELECT * FROM `sa_page` WHERE `pro_harga` IS NULL ".$where." ORDER BY `page_judul` LIMIT ".$start.",".$jmlperpage);
		if ($data === false) {
			echo '<tr><td colspan="2"><div class="alert alert-danger mb-0"><strong>Error!</strong> '.htmlspecialchars(db_error()).'</div></td></tr>';
			$data = array();
		}
		if (count($data) > 0) {			
			foreach ($data as $data) {
				echo '
			<tr>
			<td>
			<a href="#" class="info" data-target="konten'.$data['page_id'].'">'.$data['page_judul'].'</a>
			<div class="konten'.$data['page_id'].' konten mt-2">
				Alamat Page: 
					<a href="'.$weburl.$datamember['mem_kodeaff'].'/'.$data['page_url'].'" target="_blank">
					'.$weburl.$datamember['mem_kodeaff'].'/'.$data['page_url'].'</a><br/>
				URL Sales Page: <a href="'.$data['page_iframe'].'" target="_blank">'.$data['page_iframe'].'</a>
			</div>
			</td>
			<td class="text-end">
				<a href="page?edit='.$data['page_id'].'#form"><i class="fa-solid fa-pen-to-square text-success"></i></a>
				&nbsp; 
					<a href="#" data-bs-toggle="modal" data-bs-target="#konfirmasi" data-bs-nama="'.$data['page_judul'].'" 
					data-bs-id="'.$data['page_id'].'"><i class="fa-solid fa-trash-can text-danger"></i></a>
			</td>
			</tr>';
			}  				
		} else {
			echo '<tr><td colspan="2"><div class="text-center py-4">'
				.'<div class="fw-semibold">Belum ada landing page</div>'
				.'<div class="text-muted">Klik <strong>Tambah Page</strong> untuk membuat landing page pertama.</div>'
				.'<div class="mt-2"><a href="page?edit=new" class="btn btn-success">Tambah Page</a></div>'
				.'</div></td></tr>';
		}
		?>
	</tbody>
</table>
</div>

<?php
$totalRows = db_var("SELECT count(*) FROM `sa_page` WHERE `pro_harga` IS NULL ".$where);
if (!isset($totalRows) || !is_numeric($totalRows)) { $totalRows = 0; }
$jmlpage = (int)ceil(((int)$totalRows)/$jmlperpage);
if ($jmlpage < 1) { $jmlpage = 1; }
if ($jmlpage > 1) {
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

}
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
<?php 
}

$footer['konfirm'] = "⚠️ Anda akan menghapus page <strong>'+nama+'</strong>";
showfooter($footer);
?>
