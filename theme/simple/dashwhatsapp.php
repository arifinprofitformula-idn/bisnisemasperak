<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
if ($datamember['mem_role'] < 5) { die(); exit(); }
$head['pagetitle']='Integrasi WA Gateway';
showheader($head);
$dir = __DIR__ . '/../../service';
$file = is_dir($dir) ? scandir($dir) : false;

if (!is_dir($dir)) {
    echo '<div class="alert alert-warning alert-dismissible fade show" role="alert">
        <strong>Warning!</strong> Direktori service tidak ditemukan. Path: ' . $dir . '
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>';
}

if (is_array($file) && count($file) > 3) {
	$i = 0;
	foreach ($file as $file) {
		if ($file != '.' && $file != '..' && $file != 'index.php') {			
			$konten = file_get_contents($dir.'/'.$file);
			$konten = getcontent($konten,'/*','*/');
			if ($konten != '') {
				$services[$i]['file'] = str_replace('.php','',$file);
				$konten = explode("\n",$konten);
				$j = 0;

				foreach ($konten as $line) {				
					if (strpos($line, 'Service Name') !== false) { 
						$services[$i]['name'] = str_replace('Service Name','',$line); 
						$services[$i]['name'] = trim(str_replace(':','',$services[$i]['name']));
					}
					if (strpos($line, 'Service URL') !== false) { 
						$services[$i]['url'] = str_replace('Service URL','',$line); 
						$services[$i]['url'] = trim(str_replace(':','',$services[$i]['url']));
					}
					if (strpos($line, 'API Documentation') !== false) { 
						$services[$i]['doc'] = str_replace('API Documentation','',$line); 
						$services[$i]['doc'] = trim(str_replace(':','',$services[$i]['doc']));
					}
					if (strpos($line, 'Field API') !== false) { 
						$apifield = trim(str_replace('Field API','',$line));
						$expfield = explode(':',$apifield);
						$services[$i]['data'][$j]['label'] = trim($expfield[0]);
						$services[$i]['data'][$j]['field'] = trim($expfield[1]);
						$j++;
					}
				}
			}
			$i++;
		}
	}
}

if (isset($_POST) && count($_POST) > 0) {
	$settings = updatesettings($_POST);
	if ($settings === false) {
		echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
		  <strong>Error!</strong> '.db_error().'
		  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>';
	} else {
		echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
		  <strong>Ok!</strong> Setting WhatsApp telah disimpan.
		  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>';
	}
} elseif (isset($_GET['test']) && !empty($_GET['test'])) {
	if (isset($settings['wa_'.$_GET['test']]) && isset($settings['wa_'.$_GET['test']])) {		
		echo '
			<div class="alert alert-success alert-dismissible fade show" role="alert">
			  Respon WA Gateway:<br/>
			  <code>'.kirimwa($datamember['mem_whatsapp'],$settings['wa_'.$_GET['test']]).'</code>
			  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
			</div>';
	} else {
		echo '
		<div class="alert alert-danger alert-dismissible fade show" role="alert">
			WA Gateway belum ditentukan :)
		<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>';
	}
}
?>

<form action="" method="post">
<div class="card">
  <div class="card-header">
      Notifikasi WhatsApp
  </div>
  <div class="card-body">
  	<div class="table-responsive">
		<table class="table table-hover table-bordered">
			<tbody>
				<tr><td>
					<a class="info" data-target="kontensetting">Setting WA Gateway</a>
					<div class="kontensetting konten mt-2">
						<div class="mb-3 row">
					    <label class="col-sm-3 col-form-label">Service</label>
					    <div class="col-sm-9">
					      <select name="set_service" id="service" class="form-select" required>
						      	<option value="">Pilih Layanan</option>
						      	<?php 
						      	$showfield = '';
						      	foreach ($services as $service) {
						      		echo '<option value="'.$service['file'].'"';
						      		if (isset($settings['set_service']) && $settings['set_service'] == $service['file']) { 
						      			echo ' selected';
						      			foreach ($service['data'] as $datafield) {
						      				$showfield .= '#'.str_replace('field','fieldblock',$datafield['field']).',';
						      				${$datafield['field']} = $datafield['label'];
						      			}

						      			$showfield = '$("'.substr($showfield, 0,-1).'").show();';
						      			$urlservice = str_replace('https', 'https:', $service['url']);
						      			$showurl = '<a href="'.$urlservice.'" target="_blank">'.$urlservice.'</a>';
						      		}
						      		echo '>'.$service['name'].'</option>';
						      	}
						      	?>
						      </select>
						      <small class="form-text text-muted">URL: <span id="url"><?= $showurl??='';?></span></small>
					    </div>
					  </div>
					  <div class="mb-3 row" id="fieldblock1">
							<label class="col-sm-3 col-form-label" id="field1"><?php echo $field1 ??= 'Field 1';?></label>
						    <div class="col-sm-9">
						      <input type="text" name="set_field1" class="form-control" placeholder="" value="<?php echo $settings['set_field1'] ??= '';?>" />
						    </div>
						</div>
						<div class="mb-3 row" id="fieldblock2">
							<label class="col-sm-3 col-form-label" id="field2"><?php echo $field2 ??= 'Field 2';?></label>
						    <div class="col-sm-9">
						      <input type="text" name="set_field2" class="form-control" placeholder="" value="<?php echo $settings['set_field2'] ??= '';?>" />
						    </div>
						</div>
						<div class="mb-3 row" id="fieldblock3">
							<label class="col-sm-3 col-form-label" id="field3"><?php echo $field3 ??= 'Field 3';?></label>
						    <div class="col-sm-9">
						      <input type="text" name="set_field3" class="form-control" placeholder="" value="<?php echo $settings['set_field3'] ??= '';?>" />
						    </div>
						</div>
						<div class="mb-3 row" id="fieldblock4">
							<label class="col-sm-3 col-form-label" id="field4"><?php echo $field4 ??= 'Field 4';?></label>
						    <div class="col-sm-9">
						      <input type="text" name="set_field4" class="form-control" placeholder="" value="<?php echo $settings['set_field4'] ??= '';?>" />
						    </div>
						</div>
	  
				  </div>
				</td></tr>
				<?php
                $notif = array(
                    array('daftar','Registrasi',3),
                    array('upgrade','Upgrade',2),
                    array('order','Order Produk',2,'
                                <code>[idorder]</code>: Nomor ID Invoice
                                <br/><code>[hrgunik]</code>: Harga dengan kode unik
                                <br/><code>[hrgproduk]</code>: Harga produk asli
                                <br/><code>[namaproduk]</code>: Nama Produk
                                <br/><code>[urlproduk]</code>: kode URL Produk
                                <br/><code>[diskon]</code>: Diskon Produk (promo + kupon)
                                <br/><code>[totalbayar]</code>: Total Pembayaran (final dengan kode unik)
                                <br/><code>[halaman_invoice]</code>: URL halaman Invoice
                                '),
                    array('cancel_order','Pembatalan Order Produk',3,'
                                <em>Untuk Member & Admin:</em>
                                <br/><code>[idorder]</code>: Nomor ID Invoice
                                <br/><code>[namaproduk]</code>: Nama Produk
                                <br/><code>[urlproduk]</code>: URL produk terkait
                                <br/><code>[halaman_invoice]</code>: URL halaman Invoice
                                <br/><code>[alasan]</code>: Alasan pembatalan (opsional)
                                '),
                    array('prosesorder','Proses Order',2,'
                                <code>[idorder]</code>: Nomor ID Invoice
                                <br/><code>[hrgunik]</code>: Harga dengan kode unik
                                <br/><code>[hrgproduk]</code>: Harga produk asli
                                <br/><code>[namaproduk]</code>: Nama Produk
                                <br/><code>[urlproduk]</code>: kode URL Produk
                                <br/><em>Khusus Member:</em>
                                <br/><code>[urlakses]</code>: Link akses dari field "Nama File / URL Akses" (otomatis dibentuk penuh)
                                <br/><em>Khusus Sponsor:</em>
                                <br/><code>[komisiproduk]</code>: Komisi Penjualan Produk (level-1 sesuai status Sponsor)
                                <br/><code>[komisitotal]</code>: Total komisi masuk (akumulasi semua transaksi)
                                <br/><code>[komisiditransfer]</code>: Total komisi yang sudah ditransfer ke Sponsor
                                <br/><code>[komisipotensi]</code>: Total komisi yang belum ditransfer (saldo)
                                '),
                    array('cair_komisi','Pencairan Komisi',1,'<code>[komisi]</code>: Jumlah Komisi yg ditransfer'),
                    array('confirm_new','Konfirmasi Pembayaran Baru',3,'
                                <em>Untuk Admin:</em>
                                <br/><code>[idorder]</code>: Nomor ID Invoice
                                <br/><code>[atasnama]</code>: Nama pemilik rekening pengirim
                                <br/><code>[nominal]</code>: Nominal transfer (format teks)
                                <br/><code>[banklabel]</code>: Nama bank tujuan
                                <br/><code>[bankowner]</code>: Nama pemilik rekening tujuan
                                <br/><code>[bankaccount]</code>: Nomor rekening tujuan
                                <br/><code>[review_url]</code>: URL review konfirmasi di dashboard
                                '),
                    array('verify_result','Hasil Verifikasi Pembayaran',1,'
                                <em>Untuk Member:</em>
                                <br/><code>[idorder]</code>: Nomor ID Invoice
                                <br/><code>[status]</code>: Status verifikasi (diterima/ditolak)
                                <br/><code>[alasan]</code>: Alasan jika ditolak
                                <br/><code>[next_url]</code>: URL langkah selanjutnya (invoice/konfirmasi)
                                '),
                    array('komisi_requested','Permintaan Pencairan Komisi',2,'
                                <em>Untuk Admin & Member:</em>
                                <br/><code>[amount]</code>: Nominal diminta
                                <br/><code>[type]</code>: Tipe komisi (sponsor/contrib)
                                '),
                    array('cair_komisi','Komisi Dibayarkan',3,'
                                <em>Untuk Member, Sponsor & Admin:</em>
                                <br/><code>[komisi]</code>: Komisi gross
                                <br/><code>[pph21]</code>: Potongan PPh21
                                <br/><code>[net]</code>: Komisi bersih diterima
                                '),
                    array('cancel_payout','Pembatalan Pencairan Komisi',2,'
                                <em>Untuk Member & Admin:</em>
                                <br/><code>[transno]</code>: Nomor transaksi/batch payout
                                <br/><code>[alasan]</code>: Alasan pembatalan
                                <br/><code>[help_contact]</code>: Kontak bantuan (WA admin)
                                ')
                );


				$target = array('member','sponsor','admin');

				foreach ($notif as $notif) {
					for ($i=0; $i < $notif[2]; $i++) { 						
						if (isset($notif[3]) && !empty($notif[3])) {
							$shortcode = '<small class="form-text text-muted"><strong>Shortcode Khusus:</strong><br/>'.$notif[3].'</small><br/>';
						} else {
							$shortcode = '';
						}
						echo '
						<tr><td>
							<a class="info" data-target="konten_'.$notif[0].'_'.$target[$i].'">Notif '.$notif[1].' ke '.ucwords($target[$i]).'</a>
							<div class="konten_'.$notif[0].'_'.$target[$i].' konten mt-2">								
					      <textarea class="form-control" rows="5"  name="wa_'.$notif[0].'_'.$target[$i].'">'.($settings['wa_'.$notif[0].'_'.$target[$i]] ??= '').'</textarea>
					      '.$shortcode.'
					      <a href="?test='.$notif[0].'_'.$target[$i].'" class="btn btn-primary mt-1">Test WhatsApp</a>
							</div>
						</td></td>
						';
					}
				}
				?>
			</tbody>
		</table>
		</div>
		<input type="submit" class="btn btn-success mt-3" name="" value=" SIMPAN ">  
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
<?php 
$footer['services'] = $services;
$footer['showfield'] = $showfield;
showfooter($footer); ?>
