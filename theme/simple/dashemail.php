<?php 
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }
if ($datamember['mem_role'] < 5) { die(); exit(); }

// Include EmailService untuk SMTP tanpa mailketing
require_once(__DIR__ . '/../../EmailService.php');

$head['pagetitle']='Setting Email';
$head['scripthead'] = '
<link href="'.$weburl.'editor/css/froala_editor.pkgd.min.css" rel="stylesheet" type="text/css" />
<link href="'.$weburl.'editor/css/froala_style.min.css" rel="stylesheet" type="text/css" />
<style type="text/css">
a[id="fr-logo"] {
  height:1px !important;
  color:#ffffff !important;
}
#Layer_1 { height:1px !important; }
p[data-f-id="pbf"] {
  height:1px !important;
}
a[href*="www.froala.com"] {
  height:1px !important;
  background: #fff !important;
  pointer-events: none;
}
#fr-logo {
    visibility: hidden;
}
.smtp-info { background: #d1ecf1; color: #0c5460; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
</style>';
showheader($head);

// Disable mailketing secara permanen saat akses halaman ini
try {
    db_query("UPDATE epi_mailketing_config SET is_enabled = '0' WHERE id = 1");
} catch (Exception $e) {
    // Tabel tidak ada, tidak masalah
}

if (isset($_POST) && count($_POST) > 0) {
	// Handle test SMTP connection
	if (isset($_POST['test_smtp_connection'])) {
		$emailService = new EmailService();
		$test_email = $_POST['test_email_address'] ?? $datamember['mem_email'];
		$subject = "Test Koneksi SMTP - " . date('Y-m-d H:i:s');
		$message = "<h3>Test Koneksi SMTP Berhasil!</h3>";
		$message .= "<p>Email ini dikirim dari dashboard admin SimpleAff Plus.</p>";
		$message .= "<p>Waktu: " . date('Y-m-d H:i:s') . "</p>";
		$message .= "<p>Jika Anda menerima email ini, konfigurasi SMTP sudah benar.</p>";
		
		$result = $emailService->sendEmail($test_email, $subject, $message);
		
		if ($result['status'] === true) {
			echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
			  <strong>✅ Sukses!</strong> Test email berhasil dikirim ke ' . htmlspecialchars($test_email) . '! ' . ($result['message'] ?? '') . '
			  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
			</div>';
		} else {
			echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
			  <strong>❌ Gagal!</strong> Test email gagal dikirim ke ' . htmlspecialchars($test_email) . '. Error: ' . ($result['message'] ?? 'Unknown error') . '
			  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
			</div>';
		}
	} else {
		// Handle normal settings save
		$post = str_replace('<p data-f-id="pbf" style="text-align: center; font-size: 14px; margin-top: 30px; opacity: 0.65; font-family: sans-serif;">Powered by <a href="https://www.froala.com/wysiwyg-editor?pb=1" title="Froala Editor">Froala Editor</a></p>','',$_POST);
		$settings = updatesettings($post);
		if ($settings === false) {
			echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
			  <strong>Error!</strong> '.db_error().'
			  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
			</div>';
		} else {
			echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
			  <strong>Ok!</strong> Setting Email telah disimpan. Sistem menggunakan SMTP tanpa mailketing.
			  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
			</div>';
		}
	}
} elseif (isset($_GET['test']) && !empty($_GET['test'])) {
	if (isset($settings['judul_'.$_GET['test']]) && isset($settings['isi_'.$_GET['test']])) {
		// Gunakan EmailService untuk test email
		$emailService = new EmailService();
		$cek = $emailService->sendEmail(
			$datamember['mem_email'], 
			$settings['judul_'.$_GET['test']], 
			$settings['isi_'.$_GET['test']], 
			'test'
		);

		if ($cek['status'] !== true) {
			echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
			  '.($cek['message']??='').'
			  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
			</div>';
		} else {
			echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
			  Email test berhasil dikirim via SMTP! '.($cek['message']??='').'
			  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
			</div>';
		}		
	}
}
?>
<div class="smtp-info">
    <strong>📧 Status Email System:</strong> Sistem menggunakan SMTP untuk notifikasi dan reset password. 
    Mailketing telah dinonaktifkan secara permanen untuk keamanan dan reliability.
</div>

<form action="" method="post">
<div class="card">
  <div class="card-header">
      Setting Email SMTP
  </div>
  <div class="card-body">
  	<div class="table-responsive">
		<table class="table table-hover table-bordered">
			<tbody>
				<tr><td>
					<a class="info" data-target="kontensetting">Setting Email</a>
					<div class="kontensetting konten mt-2">
						<div class="mb-3 row">
					    <label class="col-sm-2 col-form-label">Alamat Email</label>
					    <div class="col-sm-10">
					      <input type="text" class="form-control" name="smtp_from" value="<?= $settings['smtp_from'] ??= '';?>">
					    </div>
					  </div>
					  <div class="mb-3 row">
					    <label class="col-sm-2 col-form-label">Nama Pengirim</label>
					    <div class="col-sm-10">
					      <input type="text" class="form-control" name="smtp_sender" value="<?= $settings['smtp_sender'] ??= '';?>">
					    </div>
					  </div>
					  <div class="mb-3 row">
					    <label class="col-sm-2 col-form-label">Outgoing Server</label>
					    <div class="col-sm-10">
					      <input type="text" class="form-control" name="smtp_server" value="<?= $settings['smtp_server'] ??= '';?>">
					    </div>
					  </div>
					  <div class="mb-3 row">
					    <label class="col-sm-2 col-form-label">SMTP Port</label>
					    <div class="col-sm-3">
					      <input type="text" class="form-control" name="smtp_port" value="<?= $settings['smtp_port'] ??= '';?>">
					    </div>
					  </div>
					  <div class="mb-3 row">
					    <label class="col-sm-2 col-form-label">SMTP Secure</label>
					    <div class="col-sm-3">
					      <select name="smtp_secure" class="form-select">
					      	<?php 
					      	$securesel = array('ssl'=>'SSL','tls'=>'TLS','false'=>'false');
					      	foreach ($securesel as $key => $value) {
					      		echo '<option value="'.$key.'"';
					      		if (isset($settings['smtp_secure']) && $settings['smtp_secure'] == $key) {
					      			echo ' selected';
					      		}
					      		echo '>'.$value.'</option>';
					      	}
						      ?>
					     	</select>
					    </div>
					  </div>
					  <div class="mb-3 row">
					    <label class="col-sm-2 col-form-label">SMTP Authentication</label>
					    <div class="col-sm-3">
					      <select name="smtp_auth" class="form-select">
					      	<?php if (isset($settings['smtp_auth']) && $settings['smtp_auth'] == 'false') {
					      		$sel1 = '';
					      		$sel2 = ' selected';
					      	} else {
					      		$sel1 = ' selected';
					      		$sel2 = '';
					      	}
					      	?>
						      <option value="true"<?php echo $sel1;?>>true</option>
						      <option value="false"<?php echo $sel2;?>>false</option>
					     	</select>
					    </div>
					  </div>	  
					  <div class="mb-3 row">
					    <label class="col-sm-2 col-form-label">Username</label>
					    <div class="col-sm-10">
					      <input type="text" class="form-control" name="smtp_username" value="<?= $settings['smtp_username'] ??= '';?>">
					    </div>
					  </div>
					  <div class="mb-3 row">
					    <label class="col-sm-2 col-form-label">Password</label>
					    <div class="col-sm-10">
					      <input type="password" class="form-control" name="smtp_password" value="<?= $settings['smtp_password'] ??= '';?>">
					    </div>
					  </div>	  
			  </div>
			</td></tr>
				<tr><td>
					<a class="info" data-target="testsmtp">🧪 Test Koneksi SMTP</a>
					<div class="testsmtp konten mt-2">
						<form method="POST" action="">
							<div class="row">
								<div class="col-md-8">
									<label for="test_email_address" class="form-label">Email Tujuan Test</label>
									<input type="email" class="form-control" id="test_email_address" name="test_email_address" 
										   value="<?= $datamember['mem_email']; ?>" 
										   placeholder="Masukkan email untuk test">
									<small class="form-text text-muted">Email test akan dikirim ke alamat ini untuk memverifikasi konfigurasi SMTP.</small>
								</div>
								<div class="col-md-4 d-flex align-items-end">
									<button type="submit" name="test_smtp_connection" class="btn btn-primary w-100">
										📧 Kirim Test Email
									</button>
								</div>
							</div>
						</form>
					</div>
				</td></tr>
				<tr><td>
					<a class="info" data-target="smtpstatus">📊 Status & Monitoring SMTP</a>
					<div class="smtpstatus konten mt-2">
						<?php
						// Check SMTP configuration status
						$smtp_configured = !empty($settings['smtp_server']) && !empty($settings['smtp_username']);
						
						// Get recent email logs if table exists
						$recent_logs = [];
						$log_table_exists = false;
						try {
							$check_table = db_query("SHOW TABLES LIKE 'epi_email_logs'");
							if (mysqli_num_rows($check_table) > 0) {
								$log_table_exists = true;
								$logs_result = db_query("SELECT * FROM epi_email_logs ORDER BY created_at DESC LIMIT 5");
								while ($log = mysqli_fetch_assoc($logs_result)) {
									$recent_logs[] = $log;
								}
							}
						} catch (Exception $e) {
							// Table doesn't exist or error occurred
						}
						?>
						
						<div class="row">
							<div class="col-md-6">
								<h6>Status Konfigurasi</h6>
								<ul class="list-unstyled">
									<li><span class="badge <?= $smtp_configured ? 'bg-success' : 'bg-warning'; ?>">
										<?= $smtp_configured ? '✅ SMTP Terkonfigurasi' : '⚠️ SMTP Belum Lengkap'; ?>
									</span></li>
									<li class="mt-2"><strong>Server:</strong> <?= htmlspecialchars($settings['smtp_server'] ?: 'Belum diset'); ?></li>
									<li><strong>Port:</strong> <?= htmlspecialchars($settings['smtp_port'] ?: 'Belum diset'); ?></li>
									<li><strong>Security:</strong> <?= htmlspecialchars($settings['smtp_secure'] ?: 'Belum diset'); ?></li>
									<li><strong>Username:</strong> <?= htmlspecialchars($settings['smtp_username'] ?: 'Belum diset'); ?></li>
								</ul>
							</div>
							<div class="col-md-6">
								<h6>Log Email Terbaru</h6>
								<?php if ($log_table_exists && !empty($recent_logs)): ?>
									<div class="table-responsive">
										<table class="table table-sm">
											<thead>
												<tr>
													<th>Waktu</th>
													<th>Penerima</th>
													<th>Status</th>
												</tr>
											</thead>
											<tbody>
												<?php foreach ($recent_logs as $log): ?>
												<tr>
													<td><?= date('d/m H:i', strtotime($log['created_at'] ?? '')); ?></td>
													<td><?= htmlspecialchars($log['recipient'] ?? 'N/A'); ?></td>
													<td>
														<span class="badge <?= ($log['status'] ?? '') === 'success' ? 'bg-success' : 'bg-danger'; ?>">
															<?= ($log['status'] ?? '') === 'success' ? 'Sukses' : 'Gagal'; ?>
														</span>
													</td>
												</tr>
												<?php endforeach; ?>
											</tbody>
										</table>
									</div>
								<?php else: ?>
									<p class="text-muted">Belum ada log email atau tabel log belum dibuat.</p>
									<small class="text-info">Log email akan muncul setelah mengirim email pertama.</small>
								<?php endif; ?>
							</div>
						</div>
					</div>
				</td></tr>
				<tr><td>
					<a class="info" data-target="smtpdocs">📚 Dokumentasi & Quick Actions</a>
					<div class="smtpdocs konten mt-2">
						<div class="row">
							<div class="col-md-6">
								<h6>Provider SMTP Populer</h6>
								<div class="table-responsive">
									<table class="table table-sm">
										<thead>
											<tr>
												<th>Provider</th>
												<th>Server</th>
												<th>Port</th>
												<th>Security</th>
											</tr>
										</thead>
										<tbody>
											<tr>
												<td><strong>Gmail</strong></td>
												<td>smtp.gmail.com</td>
												<td>587</td>
												<td>TLS</td>
											</tr>
											<tr>
												<td><strong>Outlook</strong></td>
												<td>smtp-mail.outlook.com</td>
												<td>587</td>
												<td>TLS</td>
											</tr>
											<tr>
												<td><strong>Yahoo</strong></td>
												<td>smtp.mail.yahoo.com</td>
												<td>587</td>
												<td>TLS</td>
											</tr>
											<tr>
												<td><strong>Mailgun</strong></td>
												<td>smtp.mailgun.org</td>
												<td>587</td>
												<td>TLS</td>
											</tr>
										</tbody>
									</table>
								</div>
							</div>
							<div class="col-md-6">
								<h6>Quick Actions</h6>
								<div class="d-grid gap-2">
									<button type="button" class="btn btn-outline-success btn-sm" onclick="scrollToTestForm()">
										✉️ Test SMTP (Form di Atas)
									</button>
									<button type="button" class="btn btn-outline-warning btn-sm" onclick="showTroubleshooting()">
										🛠️ Troubleshooting
									</button>
								</div>
								
								<div id="troubleshooting" style="display: none;" class="mt-3">
									<h6>Troubleshooting Umum</h6>
									<ul class="small">
										<li><strong>Error 535:</strong> Username/password salah</li>
										<li><strong>Error 587:</strong> Port atau security salah</li>
										<li><strong>Timeout:</strong> Firewall atau server down</li>
										<li><strong>Gmail:</strong> Gunakan App Password, bukan password biasa</li>
										<li><strong>Hosting:</strong> Pastikan port 587/465 tidak diblokir</li>
									</ul>
								</div>
							</div>
						</div>
						
						<div class="alert alert-info mt-3">
							<h6>💡 Tips Keamanan</h6>
							<ul class="mb-0 small">
								<li>Gunakan App Password untuk Gmail/Outlook, bukan password utama</li>
								<li>Aktifkan 2FA pada akun email provider</li>
								<li>Gunakan dedicated email untuk sistem, bukan email personal</li>
								<li>Monitor log email secara berkala</li>
							</ul>
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
								'),
					array('prosesorder','Proses Order',2,'
								<code>[idorder]</code>: Nomor ID Invoice
								<br/><code>[hrgunik]</code>: Harga dengan kode unik
								<br/><code>[hrgproduk]</code>: Harga produk asli
								<br/><code>[namaproduk]</code>: Nama Produk
								<br/><code>[urlproduk]</code>: kode URL Produk
								'),
					array('cair_komisi','Pencairan Komisi',1,'<code>[komisi]</code>: Jumlah Komisi yg ditransfer')
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
								<input type="text" class="form-control mb-2" name="judul_'.$notif[0].'_'.$target[$i].'" value="'.($settings['judul_'.$notif[0].'_'.$target[$i]] ??= '').'">
					      <textarea class="form-control ckeditor" rows="5" id="editor" data-judul="isi_'.$notif[0].'_'.$target[$i].'" name="isi_'.$notif[0].'_'.$target[$i].'">'.
					      htmlspecialchars($settings['isi_'.$notif[0].'_'.$target[$i]]  ?? '', ENT_QUOTES, 'UTF-8').'</textarea>
					      '.$shortcode.'
					      <a href="?test='.$notif[0].'_'.$target[$i].'" class="btn btn-primary mt-1">Test Email</a>
							</div>
						</td></tr>
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
<script>
function showTroubleshooting() {
	var div = document.getElementById('troubleshooting');
	if (div.style.display === 'none') {
		div.style.display = 'block';
	} else {
		div.style.display = 'none';
	}
}

function scrollToTestForm() {
	// Buka section test SMTP jika belum terbuka
	var testSection = document.querySelector('.testsmtp');
	var testLink = document.querySelector('a[data-target="testsmtp"]');
	
	if (testSection && testSection.style.display === 'none') {
		testLink.click();
	}
	
	// Scroll ke form test
	setTimeout(function() {
		var testForm = document.getElementById('test_email_address');
		if (testForm) {
			testForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
			testForm.focus();
		}
	}, 300);
}
</script>

<?php 
$footer['scriptfoot'] = '
<script type="text/javascript" src="'.$weburl.'editor/js/froala_editor.pkgd.min.js"></script>
<script>
  document.addEventListener(\'DOMContentLoaded\', function () {
    new FroalaEditor(\'#editor\', {
      imageUploadURL: \''.$weburl.'upload_image.php\',
      imageUploadParams: {
        id: \'my_editor\'
      },
      codeViewKeepOriginal: true,
      htmlUntouched: true,
      htmlAllowedTags: [\'.*\'], // Allow all HTML tags
      htmlAllowedAttrs: [\'.*\'], // Allow all attributes
      htmlRemoveTags: [],
      events: {
        \'image.beforeUpload\': function (files) {
          var editor = this;

          // Create a FormData object.
          var formData = new FormData();

          // Append the uploaded image to the form data.
          formData.append(\'file\', files[0]);

          // Get the article title and append it to the form data.
          var namafile = document.querySelector(\'#editor\').getAttribute(\'data-judul\');
          formData.append(\'judul\', namafile);

          // Make the AJAX request.
          fetch(\''.$weburl.'upload_image.php\', {
            method: \'POST\',
            body: formData
          })
          .then(response => response.json())
          .then(data => {
            if (data.link) {
              // Insert the image into the editor.
              editor.image.insert(data.link, null, null, editor.image.get());
            } else {
              console.error(\'Upload failed:\', data.error);
            }
          })
          .catch(error => {
            console.error(\'Error:\', error);
          });

          // Prevent the default behavior.
          return false;
        }
      }
    });
  });
</script>';
showfooter($footer); ?>