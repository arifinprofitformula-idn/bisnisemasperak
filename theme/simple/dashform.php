<?php 
if (isset($_GET['edit'])) :
	include('dashformadd.php');
else:
// Start session for CSRF protection on save
if (session_status() === PHP_SESSION_NONE) { @session_start(); }
if (!isset($_SESSION['csrf_token'])) {
  try { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); } catch (Exception $e) { $_SESSION['csrf_token'] = md5(uniqid('', true)); }
}
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
if ($datamember['mem_role'] < 9) { die(); exit(); }
$head['pagetitle']='Setting Form';
showheader($head);
if (isset($_POST['sort'])) {
    // CSRF validation
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
      if (isset($_GET['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'message' => 'CSRF token tidak valid']);
        exit;
      }
      echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Error!</strong> CSRF token tidak valid.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>';
    } else {
      // Build CASE-based UPDATE to avoid INSERT of incomplete rows in strict mode
      $orderMap = [];
      foreach ((array)$_POST['sort'] as $key => $value) {
        if (is_numeric($value)) {
          $orderMap[(int)$value] = (int)$key + 1; // 1-based index
        }
      }

      if (!empty($orderMap)) {
        $ids = implode(',', array_map('intval', array_keys($orderMap)));
        $case = '';
        foreach ($orderMap as $id => $sortNo) {
          $case .= " WHEN {$id} THEN {$sortNo}";
        }
        $sql = "UPDATE `sa_form` SET `ff_sort` = CASE `ff_id`{$case} ELSE `ff_sort` END WHERE `ff_id` IN ({$ids})";
        $cek = db_query($sql);
        if (isset($_GET['ajax'])) {
          header('Content-Type: application/json');
          if ($cek === false) {
            echo json_encode(['ok' => false, 'message' => db_error()]);
          } else {
            echo json_encode(['ok' => true, 'message' => 'Urutan form telah disimpan']);
          }
          exit;
        }
        if ($cek === false) {
          echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error!</strong> '.db_error().'
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
        } else {
          echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Ok!</strong> Urutan form telah disimpan.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
        }
      }
    }
} elseif (isset($_GET['del']) && is_numeric($_GET['del'])) {
	$cek = db_query("DELETE FROM `sa_form` WHERE `ff_id`=".$_GET['del']);
	echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
			  <strong>Ok!</strong> Isian form telah dihapus.
			  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
			</div>';
}
?>
<form action="" method="post">
<div class="card">	  
  <div class="card-body">
	  <ul id="sortable" class="ui-sortable collection">
	  	<?php 	  	
	  	$data = db_select("SELECT * FROM `sa_form` ORDER BY `ff_sort`");
	  	foreach ($data as $data) :
	  	?>
	  		
	  	<li id="ff_<?php echo $data['ff_id'];?>" data-id="<?php echo $data['ff_id'];?>" class="row form-group ui-state-default ui-sortable-handle collection-item avatar z-depth-3">
				<div class="col-9"><?php echo $data['ff_label'];?> <small>(<?php echo $data['ff_field'];?>)</small></div>				
				<div class="col-3 text-end">
				  <a href="form?edit=<?php echo $data['ff_id'];?>"><i class="fa-solid fa-pen-to-square text-success"></i></a>		  
				  &nbsp;<a href="#" data-bs-toggle="modal" data-bs-target="#konfirmasi" data-bs-nama="<?php echo $data['ff_label'];?>" 
						data-bs-id="<?php echo $data['ff_id'];?>"><i class="fa-solid fa-trash-can text-danger"></i></a>
				</div>
				<input type="hidden" name="sort[]" value="<?php echo $data['ff_id'];?>"/>
			</li>

			<?php endforeach; ?>
		</ul>	
		<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
		<div class="text-center">
			<input type="submit" value="Simpan" class="btn btn-success">
			<a href="form?edit=new" class="btn btn-primary">Tambah</a>
	</div>
</div>
</form>

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
$footer['konfirm'] = "⚠️ Anda akan menghapus isian <strong>'+nama+'</strong> dari formulir.";
showfooter($footer);
endif;
?>