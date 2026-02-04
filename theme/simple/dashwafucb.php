<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
if ($datamember['mem_role'] < 9) { die(); exit(); }
$head['pagetitle']='Integrasi WAFUCB';
showheader($head);

if (isset($_POST['wafucb_key']) && !empty($_POST['wafucb_key'])) {
	if (!isset($_POST['wafucb_val_daftar'])) { $_POST['wafucb_val_daftar'] = 0;	}
	if (!isset($_POST['wafucb_val_upgrade'])) { $_POST['wafucb_val_upgrade'] = 0;	}
	$settings = updatesettings($_POST);
	if ($settings === false) {
		echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
		  <strong>Error!</strong> '.db_error().'
		  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>';
	} else {
		echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
		  <strong>Ok!</strong> Setting telah disimpan.
		  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>';
	}
}
?>
<form action="" method="post">
<div class="card">
  <div class="card-header">
      Setting WAFUCB
  </div>
  <div class="card-body">
	  <div class="mb-3 row">
	    <label class="col-sm-3 col-form-label">WAFUCB ID</label>
	    <div class="col-sm-3">
	      <input type="number" class="form-control" name="wafucb_id" value="<?= $settings['wafucb_id'] ??= '';?>">
	      <small class="form-text text-muted">Cek di menu <a href="https://wafucb.my.id/dashboard/profil" target="_blank">profil WAFUCB</a></small>
	    </div>
	  </div>
	  <div class="mb-3 row">
	    <label class="col-sm-3 col-form-label">WAFUCB Key</label>
	    <div class="col-sm-5">
	      <input type="text" class="form-control" name="wafucb_key" value="<?= $settings['wafucb_key'] ??= '';?>">
	    </div>
	  </div>
	<?php
  if (isset($settings['wafucb_key']) && $settings['wafucb_key'] != '' 
  	&& isset($settings['wafucb_id']) && is_numeric($settings['wafucb_id'])) {

  	$channel = getchannel();	 
  	if (is_array($channel) && count($channel) > 0) {	  		
  		$listform = array(	
				'daftar' => 'Free Member',	
				'upgrade' => 'Premium Member',
				'order' => 'Order baru',
				'prosesorder' => 'Order telah diproses'
			);

  		foreach ($listform as $keyform => $valform) {
		  	echo '
		  	<div class="mb-3 row">
					<label class="col-sm-3 col-form-label">Channel '.$valform.'</label>
				  <div class="col-sm-5">
					  <select name="wafucb_'.$keyform.'" class="form-select">
					  	<option value=""></option>
					  ';
					  foreach ($channel as $listchan) {
					  	echo '<option value="'.$listchan['id'].'"';
					  	if (isset($settings['wafucb_'.$keyform]) && $settings['wafucb_'.$keyform] == $listchan['id']) {
					  		echo ' selected';
					  	}
					  	echo '>'.$listchan['nama'].'</option>';
					  }
					echo '
					  </select>
					</div>
					<div class="col-sm-4">
						<div class="form-check">
						  <input class="form-check-input" type="checkbox" name="wafucb_val_'.$keyform.'" value="1"';
					  	if (isset($settings['wafucb_val_'.$keyform]) && $settings['wafucb_val_'.$keyform] == 1) {
					  		echo ' checked';
					  	}
					  	echo '/>
						  <label class="form-check-label" for="flexCheckDefault">
						    Wajib Validasi
						  </label>
						</div>
				  </div>
				</div>
		  	';
		  }
  	} else {
  		echo $channel;
  	}

	  
	}
  ?>
 	<input type="submit" class="btn btn-success" name="" value=" SIMPAN ">
 </div>
</div>
</form>

<div class="card mt-3">
  <div class="card-header">
      Daftar Shortcode
      <?php
      $scmember = $scsponsor = '';
      $form = db_select("SELECT * FROM `sa_form` ORDER BY `ff_sort`");
      if (count($form) > 0) {
      	$default = array('nama','email','whatsapp','kodeaff');
      	foreach ($form as $form) {
      		if (!in_array($form['ff_field'], $default)) {
      			$scmember .= '<code>'.$form['ff_field'].'</code><br/>';
      			$scsponsor .= '<code>sp'.$form['ff_field'].'</code><br/>';
      		}
      	}
      }
      ?>
  </div>
  <div class="card-body">
  	<div class="row">
  		<div class="col">
  			Untuk menggunakan data2 Simple Aff, edit channel WAFUCB lalu masukkan field-field ini di opsi Custom Field:
  		</div>
  	</div>
  	<div class="row">
  		<div class="col">
  			<strong>Field Data Member</strong><br/>
  			<code>nama</code><br/>
  			<code>email</code><br/>
  			<code>whatsapp</code><br/>
  			<code>kodeaff</code><br/>
  			<?php echo $scmember;?>
  		</div>
  		<div class="col">
  			<strong>Field Data Sponsor</strong><br/>
  			<code>spnama</code><br/>
  			<code>spemail</code><br/>
  			<code>spwhatsapp</code><br/>
  			<code>spkodeaff</code><br/>
  			<?php echo $scsponsor;?>
  		</div>
  		<div class="col">
  			<strong>Field Data Order</strong><br/>
  			<code>idorder</code>
				<br/><code>hrgunik</code>
				<br/><code>hrgproduk</code>
				<br/><code>namaproduk</code>
				<br/><code>urlproduk</code>
  		</div>
  				
  	</div>
  </div>
</div>


<?php showfooter(); ?>