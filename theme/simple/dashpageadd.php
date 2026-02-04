<?php
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
@require_once dirname(__DIR__,2).'/plugin/epi-role-manager/index.php';

$roleCode = (int)($datamember['mem_role'] ?? 1);
$allow = ($roleCode >= 9);
if (!$allow && $roleCode >= 5 && function_exists('epi_role_permissions_for_member')) {
  $perms = epi_role_permissions_for_member($datamember);
  if (is_array($perms) && !empty($perms['page'])) { $allow = true; }
}

if (!$allow) {
  echo '<div class="alert alert-danger">Akses ditolak. Role Anda tidak memiliki izin untuk mengelola landing page. Silakan minta Administrator mengaktifkan permission <code>page</code> di halaman Setting Role.</div>';
  return;
}

if (isset($_POST['urlpage']) && $_POST['urlpage'] != '') {
	$judulpage = isset($_POST['judulpage']) ? trim((string)$_POST['judulpage']) : '';
	$alamatpage = isset($_POST['alamatpage']) ? trim((string)$_POST['alamatpage']) : '';
	$urlpage = isset($_POST['urlpage']) ? trim((string)$_POST['urlpage']) : '';
	if ($judulpage === '' || $alamatpage === '' || $urlpage === '') {
		echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">'
			.'<strong>Error!</strong> Judul, Alamat Page, dan URL Sales Page wajib diisi.'
			.'<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
			.'</div>';
	} else {
	# Cek apakah page_url sudah dipakai page lain atau belum
	if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
		# Edit Page
		$cek = db_query("UPDATE `sa_page` SET 
			`page_judul` = '".cek($_POST['judulpage'])."',
			`page_diskripsi` = '".cek($_POST['diskripsipage'])."',
			`page_url` = '".cekurlpage($_POST['alamatpage'],$_GET['edit'])."',
			`page_iframe` = '".cek($_POST['urlpage'])."',
			`page_method`= ".cek($_POST['metodelp']).",
			`page_fr` = '".serialize($_POST['fr'])."'
			WHERE `page_id`=".$_GET['edit']);
	} else {
		# Simpan di database
		$cek = db_query("INSERT INTO `sa_page` (`page_judul`,`page_diskripsi`,`page_url`,`page_iframe`,`page_method`,`page_fr`) VALUES 
			('".cek($_POST['judulpage'])."','".cek($_POST['diskripsipage'])."','".cekurlpage($_POST['alamatpage'])."','".cek($_POST['urlpage'])."',".cek($_POST['metodelp']).",'".serialize($_POST['fr'])."')");
	}

	if ($cek === false) {
		echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
		  <strong>Error!</strong> '.db_error().'
		  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>';
	} else {
		echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
		  <strong>Ok!</strong> Page telah disimpan.
		  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>';
	}
	}
}

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
	$page = db_row("SELECT * FROM `sa_page` WHERE `page_id`=".$_GET['edit']);
	if (!$page || !isset($page['page_id'])) {
		echo '<div class="alert alert-warning alert-dismissible fade show" role="alert">'
			.'<strong>Warning!</strong> Data landing page tidak ditemukan atau sudah dihapus.'
			.'<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
			.'</div>';
		$page = array();
	}
}
?>

<form action="" method="post">
<a name="form"></a>
<div class="card">
  <div class="card-header">
     Tambah Page
  </div>
  <div class="card-body">
	  <div class="mb-3 row">
	    <label class="col-sm-2 col-form-label">Judul Page</label>
	    <div class="col-sm-10">
	      <input type="text" class="form-control" name="judulpage" value="<?= $page['page_judul'] ??= '';?>">
	    </div>
	  </div>
	  <div class="mb-3 row">
	    <label class="col-sm-2 col-form-label">Diskripsi Page</label>
	    <div class="col-sm-10">
	      <input type="text" class="form-control" name="diskripsipage" value="<?= $page['page_diskripsi'] ??= '';?>">
	    </div>
	  </div>
	  <div class="mb-3 row">
	    <label class="col-sm-2 col-form-label">Alamat Page</label>
	    <div class="col-sm-10">
	      <div class="input-group">
			    <span class="input-group-text" id="basic-addon3"><?= $weburl.$datamember['mem_kodeaff'];?>/</span>
			    <input type="text" class="form-control" name="alamatpage" value="<?= $page['page_url'] ??= '';?>">
			  </div>
	    </div>
	  </div>
	  
	  <div class="mb-3 row">
	    <label class="col-sm-2 col-form-label">URL Sales Page</label>
	    <div class="col-sm-10">
	      <input type="text" class="form-control" name="urlpage" value="<?= $page['page_iframe'] ??= 'https://';?>">
	    </div>
	  </div>
		<div class="mb-3 row">
	    <label class="col-sm-2 col-form-label">Metode</label>
	    <div class="col-sm-5">
	    	<?php
	    	$metode = array(
	    		1 => 'Gunakan iFrame',
	    		2 => 'Inject URL',
	    		3 => 'Redirect URL'
	    	);

	    	$metode = apply_filter('page_metode_lp',$metode);

	    	echo '<select name="metodelp" id="metodelp" class="form-select">';
	    	foreach ($metode as $key => $value) {
	    		echo '<option value="'.$key.'"';
	    		if (isset($page['page_method']) && $page['page_method'] == $key) {
	    			echo ' selected';
	    		}
	    		echo '>'.$value.'</option>';
	    	}
	    	echo '</select>';
	    	?>					    	
	    </div>
	  </div>
	  <div class="mb-3 row">
	    <label class="col-sm-2 col-form-label">Find and Replace</label>
	    <div class="col-sm-10">
	    	<?php 
	    	if (isset($page['page_fr']) && !empty($page['page_fr'])) {
	    		$fr = unserialize($page['page_fr']);
	    	}
	    	for ($i=1; $i <= 5; $i++) :?>
	    	<div class="input-group">
	      	<input type="text" class="form-control" placeholder="find" name="fr[<?= $i;?>][find]" value="<?= $fr[$i]['find'] ??= '';?>">
	      	<input type="text" class="form-control" placeholder="replace" name="fr[<?= $i;?>][replace]" value="<?= $fr[$i]['replace'] ??= '';?>">
	      </div>
	    	<?php endfor; ?>
	      <small class="form-text text-muted">Ubah text landing page (hanya berlaku untuk metode Inject URL)</small>
	    </div>
	  </div>  
	  <input type="submit" class="btn btn-success" name="" value=" SIMPAN ">
	</div>
</div>
</form>
