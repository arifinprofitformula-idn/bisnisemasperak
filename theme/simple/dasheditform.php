<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
if ($datamember['mem_role'] < 9) { die(); exit(); }
if (isset($_GET['id']) && is_numeric($_GET['id'])) {		
	$editform = db_row("SELECT * FROM `sa_form` 
		WHERE `ff_id`=".$_GET['id']);
	if (isset($editform['ff_label'])) {
		$head['pagetitle'] = 'Edit Isian Form '.$editform['ff_label'];
	} else {
		$head['pagetitle'] = 'Tambah Isian Form';		
	}
} else {
	$head['pagetitle'] = 'Tambah Isian Form';			
}

showheader($head);

if (isset($_POST['label']) && isset($_POST['field'])) {
	$registrasi = $profil = $network = $required = 0;
	if (isset($_POST['registrasi'])) { $registrasi = 1; }
	if (isset($_POST['profil'])) { $profil = 1; }
	if (isset($_POST['network'])) { $network = 1; }
	if (isset($_POST['required'])) { $required = 1; }
	if ($_POST['field'] == 'custom') {
		$fieldname = txtonly(strtolower($_POST['custom']));
	} else {
		$fieldname = cek($_POST['field']);
	}
	$jmlform = db_var("SELECT count(*) FROM `sa_form`");
	
	if (isset($_GET['id']) && is_numeric($_GET['id'])) {
		
		# Update Data
		$cek = db_query("UPDATE `sa_form` SET 
			`ff_label` = '".cek($_POST['label'])."',
			`ff_field` = '".$fieldname."',
			`ff_type` = '".cek($_POST['type'])."',
			`ff_keterangan` = '".cek($_POST['keterangan'])."',
			`ff_options` = '".cek($_POST['options'])."',
			`ff_profil` = '".$profil."',
			`ff_registrasi` = '".$registrasi."',
			`ff_network` = '".$network."',
			`ff_required` = '".$required."'
			WHERE `ff_id`=".$_GET['id']);

		$editform = db_row("SELECT * FROM `sa_form` WHERE `ff_id`=".$_GET['id']);
	} else {

		# Input Data	
		$cek = db_insert("INSERT INTO `sa_form` 
			(`ff_label`,`ff_field`,`ff_type`,`ff_keterangan`,`ff_options`,`ff_profil`,`ff_registrasi`,`ff_network`,`ff_required`,`ff_sort`) 
			VALUES ('".cek($_POST['label'])."','".$fieldname."','".cek($_POST['type'])."','".cek($_POST['keterangan'])."',
				'".cek($_POST['options'])."',".$profil.",".$registrasi.",".$network.",".$required.",".$jmlform.")
			");
	}

	if ($cek === false) {
		echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
		  <strong>Error!</strong> '.db_error().'
		  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>';
	} else {
		echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
		  <strong>Ok!</strong> Form telah disimpan.
		  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>';
	}
}


?>
<form action="" method="post">
<div class="card">
  <div class="card-header">
    <?php echo $head['pagetitle'];?>
  </div>
  <div class="card-body">
	  <div class="mb-3 row">
	    <label class="col-sm-2 col-form-label">Label</label>
	    <div class="col-sm-10">
	      <input type="text" class="form-control" name="label" value="<?= $editform['ff_label'] ??= '';?>">
	    </div>
	  </div>
	  <div class="mb-3 row">
	    <label class="col-sm-2 col-form-label">Keterangan</label>
	    <div class="col-sm-10">
	      <input type="text" class="form-control" name="keterangan" value="<?= $editform['ff_keterangan'] ??= '';?>">
	    </div>
	  </div>
	  <div class="mb-3 row">
	    <label class="col-sm-2 col-form-label">Field</label>
	    <div class="col-sm-4">
	      <select class="form-select" id="nameInput" name="field">
          <?php
          $arrfield = array('nama'=>'Nama Lengkap','email'=>'Alamat Email','whatsapp'=>'No. Whatsapp','kodeaff'=>'URL Affiliasi','password'=>'Password','sponsor'=>'Kode Sponsor','custom'=>'Custom Field');
          foreach ($arrfield as $key => $value) {
          	echo '<option value="'.$key.'"';
          	if (isset($editform['ff_field']) && $editform['ff_field'] == $key) {
          		echo ' selected';
          		$default = 1;
          	}

          	if (isset($editform['ff_field']) && !isset($default) && $key == 'custom') { 
          		echo ' selected'; 
          		$custom = 1;
          	}

          	echo '>'.$value.'</option>';
          }
          ?>
        </select>
	    </div>
	  </div>
	  <div class="mb-3 row d-none" id="customInputContainer">
	    <label class="col-sm-2 col-form-label">Custom Field</label>
	    <div class="col-sm-4">
	      <input type="text" class="form-control" id="customInput" name="custom"  value="<?= $editform['ff_field'] ??= '';?>" placeholder="Masukkan nama field custom">
	    </div>
	  </div>
	  <div class="mb-3 row">
	    <label class="col-sm-2 col-form-label">Type</label>
	    <div class="col-sm-4">
	      <select name="type" id="typeInput" class="form-select">
	      	<?php
          $arrfield = array('text'=>'Input Text','date'=>'Input Date/Time','email'=>'Input Email','file'=>'Input File','number'=>'Input Number','password'=>'Input Password','kodeaff'=>'Input URL Affiliasi','textarea'=>'Textarea','select'=>'Select Option');
          foreach ($arrfield as $key => $value) {
          	echo '<option value="'.$key.'"';
          	if (isset($editform['ff_type']) && $editform['ff_type'] == $key) {
          		echo ' selected';
          		$default = 1;
          	}
          	echo '>'.$value.'</option>';
          }
          ?>
	      </select>
	    </div>
	  </div>
	  <div class="mb-3 row d-none" id="optionsInputContainer">
	    <label class="col-sm-2 col-form-label">Options</label>
	    <div class="col-sm-4">
	      <textarea class="form-control" id="optionsInput" name="options" rows="3"><?= $editform['ff_options'] ??= '';?></textarea>
	      <div class="form-text">Satu Opsi per baris .</div>
	    </div>
	  </div>
	  <div class="mb-3 row">
	    <label class="col-sm-2 col-form-label">Show In</label>
	    <div class="col-sm-10">
	      <div class="form-check">
				  <input class="form-check-input" type="checkbox" name="profil" value="1" <?php 
				  if (isset($editform['ff_profil']) && $editform['ff_profil'] == 1) { echo ' checked'; }?>>
				  <label class="form-check-label">
				    Profil
				  </label>
				</div>
				<div class="form-check">
				  <input class="form-check-input" type="checkbox" name="registrasi" value="1" <?php 
				  if (isset($editform['ff_registrasi']) && $editform['ff_registrasi'] == 1) { echo ' checked'; }?>>
				  <label class="form-check-label">
				    Registrasi
				  </label>
				</div>
				<div class="form-check">
				  <input class="form-check-input" type="checkbox" name="network" value="1" <?php 
				  if (isset($editform['ff_network']) && $editform['ff_network'] == 1) { echo ' checked'; }?>>
				  <label class="form-check-label">
				    Network
				  </label>
				</div>
				<div class="form-check">
				  <input class="form-check-input" type="checkbox" name="required" value="1" <?php 
				  if (isset($editform['ff_required']) && $editform['ff_required'] == 1) { echo ' checked'; }?>>
				  <label class="form-check-label">
				    Wajib Diisi
				  </label>
				</div>
						
	    </div>
	  </div>
	  <input type="submit" value="Simpan" class="btn btn-success">
	</div>
</div>
</form>
<?php showfooter(); ?>