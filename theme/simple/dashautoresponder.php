<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
if ($datamember['mem_role'] < 9) { die(); exit(); }
$head['pagetitle']='Integrasi dg Autoresponder';
showheader($head);

if (isset($_POST) && count($_POST) > 2) {
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

$notif = array(
					array('daftar','Subscribe saat Registrasi'),
					array('upgrade','Subscribe saat Upgrade'),
					array('order','Subscribe saat Order Produk','
								<code>[idorder]</code>: Nomor ID Invoice
								<br/><code>[hrgunik]</code>: Harga dengan kode unik
								<br/><code>[hrgproduk]</code>: Harga produk asli
								<br/><code>[namaproduk]</code>: Nama Produk
								<br/><code>[urlproduk]</code>: kode URL Produk
								'),
					array('prosesorder','Subscribe saat Proses Order','
								<code>[idorder]</code>: Nomor ID Invoice
								<br/><code>[hrgunik]</code>: Harga dengan kode unik
								<br/><code>[hrgproduk]</code>: Harga produk asli
								<br/><code>[namaproduk]</code>: Nama Produk
								<br/><code>[urlproduk]</code>: kode URL Produk
								')
				);
echo '
<form action="" method="post">
<div class="card">
  <div class="card-header">
      Setting Autoresponder
  </div>
  <div class="card-body">
  	<div class="table-responsive">
		<table class="table table-hover table-bordered">
			<tbody>';
foreach ($notif as $notif) {
	if (isset($notif[2]) && !empty($notif[2])) {
		$shortcode = '<small class="form-text text-muted"><strong>Shortcode Khusus:</strong><br/>'.$notif[2].'</small><br/>';
	} else {
		$shortcode = '';
	}
	echo '
				<tr><td>
					<a class="info" data-target="konten_'.$notif[0].'">'.$notif[1].'</a>
					<div class="konten_'.$notif[0].' konten mt-2">
						<div class="mb-3 row">
					    <label class="col-sm-3 col-form-label">Action</label>
					    <div class="col-sm-9">
					      <input type="text" class="form-control" name="form_action_'.$notif[0].'" value="'.($settings['form_action_'.$notif[0]]??='').'">
					    </div>
					  </div>';
					  for ($i=1; $i <= 10 ; $i++) { 
					  	echo '
					  <div class="mb-3 row">
					  	<div class="col">
					  		<input type="text" class="form-control" name="form_field_'.$notif[0].$i.'" 
					  		value="'.($settings['form_field_'.$notif[0].$i]??='').'" placeholder="Field Name" />
					  	</div>
					  	<div class="col">
						  	<input type="text" class="form-control" name="form_value_'.$notif[0].$i.'" 
						  	value="'.($settings['form_value_'.$notif[0].$i]??='').'" placeholder="Value" />
					  	</div>		  	
					  </div>
					  	';
					  }
					  echo '
					  '.$shortcode.'
					</div>
					
				</td></tr>';
}
echo '
			</tbody>
		</table>
		</div>
	</div>
</div>
	';

echo '
<input type="submit" class="btn btn-success" value=" SIMPAN "/>
</form>';
?>

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
      			$scmember .= '<code>[member_'.$form['ff_field'].']</code> : '.$form['ff_label'].'<br/>';
      			$scsponsor .= '<code>[sponsor_'.$form['ff_field'].']</code> : '.$form['ff_label'].'<br/>';
      		}
      	}
      }
      ?>
  </div>
  <div class="card-body">
  	<div class="row">
  		<div class="col-sm-6">
  			<strong>Data Member yang mendaftar / upgrade:</strong><br/>
  			<code>[member_nama]</code> : Nama member<br/>
  			<code>[member_email]</code> : Email member<br/>
  			<code>[member_whatsapp]</code> : WhatsApp member<br/>
  			<code>[member_kodeaff]</code> : URL Affiliasi member<br/>
  			<?php echo $scmember;?>
  		</div>
  		<div class="col-sm-6">
  			<strong>Data sponsor dari member yang mendaftar / upgrade:</strong><br/>
  			<code>[sponsor_nama]</code> : Nama sponsor<br/>
  			<code>[sponsor_email]</code> : Email sponsor<br/>
  			<code>[sponsor_whatsapp]</code> : WhatsApp sponsor<br/>
  			<code>[sponsor_kodeaff]</code> : URL Affiliasi sponsor<br/>
  			<?php echo $scsponsor;?>
  		</div>
  	</div>
  </div>
</div>
<?php showfooter(); ?>
